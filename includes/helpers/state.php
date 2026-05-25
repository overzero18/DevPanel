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
