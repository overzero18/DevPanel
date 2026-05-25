<?php

function devpanelStatePath(): string
{
    return dirname(__DIR__, 2) . '/logs/devpanel.sqlite';
}

function devpanelStateDb(): ?SQLite3
{
    static $db = null;

    if ($db instanceof SQLite3)
    {
        return $db;
    }

    if (!class_exists('SQLite3'))
    {
        return null;
    }

    $path = devpanelStatePath();
    $dir = dirname($path);

    if (!is_dir($dir))
    {
        mkdir($dir, 0775, true);
    }

    try
    {
        $db = new SQLite3($path);
    }
    catch (Throwable $exception)
    {
        error_log('DevPanel state database unavailable: ' . $exception->getMessage());
        return null;
    }
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS api_tokens (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        prefix TEXT NOT NULL,
        hash TEXT NOT NULL,
        role TEXT NOT NULL,
        created_at TEXT,
        last_used_at TEXT,
        expires_at TEXT,
        rotated_at TEXT
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS updater_state (
        id TEXT PRIMARY KEY,
        pre_commit TEXT NOT NULL,
        post_commit TEXT,
        status TEXT NOT NULL,
        error TEXT,
        started_at TEXT,
        completed_at TEXT
    )');

    return $db;
}

function devpanelStateTokenRows(): array
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return [];
    }

    $result = $db->query('SELECT * FROM api_tokens ORDER BY created_at DESC');
    $rows = [];

    while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC)))
    {
        $rows[] = $row;
    }

    return $rows;
}

function devpanelStateUpsertToken(array $token): bool
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return false;
    }

    $stmt = $db->prepare('INSERT OR REPLACE INTO api_tokens
        (id, name, prefix, hash, role, created_at, last_used_at, expires_at, rotated_at)
        VALUES (:id, :name, :prefix, :hash, :role, :created_at, :last_used_at, :expires_at, :rotated_at)');

    foreach (['id', 'name', 'prefix', 'hash', 'role', 'created_at', 'last_used_at', 'expires_at', 'rotated_at'] as $key)
    {
        $stmt->bindValue(':' . $key, $token[$key] ?? null, SQLITE3_TEXT);
    }

    return (bool) $stmt->execute();
}

function devpanelStateFindToken(string $id): ?array
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return null;
    }

    $stmt = $db->prepare('SELECT * FROM api_tokens WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

    return is_array($row) ? $row : null;
}

function devpanelStateDeleteToken(string $id): bool
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return false;
    }

    $stmt = $db->prepare('DELETE FROM api_tokens WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);

    return (bool) $stmt->execute();
}

function devpanelStateTouchToken(string $id): bool
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return false;
    }

    $stmt = $db->prepare('UPDATE api_tokens SET last_used_at = :last_used_at WHERE id = :id');
    $stmt->bindValue(':last_used_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);

    return (bool) $stmt->execute();
}

function devpanelStateUpdaterCheckpoint(): ?string
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return null;
    }

    $result = $db->query('SELECT id, pre_commit FROM updater_state WHERE status = \'in_progress\' ORDER BY started_at DESC LIMIT 1');
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

    return is_array($row) ? $row['id'] : null;
}

function devpanelStateUpdaterSave(array $update): bool
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return false;
    }

    $id = $update['id'] ?? date('YmdHis');
    $stmt = $db->prepare('INSERT OR REPLACE INTO updater_state
        (id, pre_commit, post_commit, status, error, started_at, completed_at)
        VALUES (:id, :pre_commit, :post_commit, :status, :error, :started_at, :completed_at)');

    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $stmt->bindValue(':pre_commit', $update['pre_commit'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':post_commit', $update['post_commit'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':status', $update['status'] ?? 'pending', SQLITE3_TEXT);
    $stmt->bindValue(':error', $update['error'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':started_at', $update['started_at'] ?? date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':completed_at', $update['completed_at'] ?? null, SQLITE3_TEXT);

    return (bool) $stmt->execute();
}

function devpanelStateUpdaterHistory(): array
{
    $db = devpanelStateDb();

    if (!$db)
    {
        return [];
    }

    $result = $db->query('SELECT * FROM updater_state ORDER BY started_at DESC LIMIT 20');
    $rows = [];

    while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC)))
    {
        $rows[] = $row;
    }

    return $rows;
}
