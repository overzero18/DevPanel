<?php

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/state.php';

function devpanelUpdaterStatus(): array
{
    $root = dirname(__DIR__, 2);
    $remote = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' remote get-url origin 2>/dev/null'));
    $status = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' status --short 2>/dev/null'));
    $local = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --short HEAD 2>/dev/null'));
    $upstream = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --abbrev-ref --symbolic-full-name @{u} 2>/dev/null'));
    $behind = 0;
    $ahead = 0;

    if ($upstream !== '')
    {
        $counts = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-list --left-right --count HEAD...' . escapeshellarg($upstream) . ' 2>/dev/null'));
        if (preg_match('/^(\d+)\s+(\d+)$/', $counts, $matches))
        {
            $ahead = (int) $matches[1];
            $behind = (int) $matches[2];
        }
    }

    return [
        'version' => devpanelVersion(),
        'commit' => $local ?: devpanelGitCommit(),
        'branch' => devpanelGitBranch(),
        'remote' => $remote,
        'upstream' => $upstream,
        'dirty' => $status !== '',
        'ahead' => $ahead,
        'behind' => $behind,
        'can_update' => $remote !== '' && $upstream !== '' && $status === '',
    ];
}

function devpanelUpdaterPerform(): array
{
    $root = dirname(__DIR__, 2);
    $preCommit = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD 2>/dev/null'));

    devpanelStateUpdaterSave([
        'pre_commit' => $preCommit,
        'status' => 'in_progress',
    ]);

    $fetch = shell_exec('git -C ' . escapeshellarg($root) . ' fetch origin 2>&1');
    $pull = shell_exec('git -C ' . escapeshellarg($root) . ' pull --ff-only 2>&1');

    if ($pull === null || str_contains($pull, 'error') || str_contains($pull, 'fatal'))
    {
        devpanelStateUpdaterSave([
            'pre_commit' => $preCommit,
            'status' => 'failed',
            'error' => $pull ?: 'Unknown error',
        ]);

        return [
            'success' => false,
            'message' => $pull ?: 'Update failed',
            'pre_commit' => $preCommit,
        ];
    }

    $postCommit = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD 2>/dev/null'));

    devpanelStateUpdaterSave([
        'pre_commit' => $preCommit,
        'post_commit' => $postCommit,
        'status' => 'success',
        'completed_at' => date('Y-m-d H:i:s'),
    ]);

    return [
        'success' => true,
        'message' => 'Update completed successfully',
        'pre_commit' => $preCommit,
        'post_commit' => $postCommit,
    ];
}

function devpanelUpdaterRollback(): array
{
    $root = dirname(__DIR__, 2);
    $checkpoint = devpanelStateUpdaterCheckpoint();

    if (!$checkpoint)
    {
        return [
            'success' => false,
            'message' => 'No update in progress',
        ];
    }

    $history = devpanelStateUpdaterHistory();
    $update = null;

    foreach ($history as $item)
    {
        if ($item['id'] === $checkpoint)
        {
            $update = $item;
            break;
        }
    }

    if (!$update || !$update['pre_commit'])
    {
        return [
            'success' => false,
            'message' => 'Cannot find update checkpoint',
        ];
    }

    $reset = shell_exec('git -C ' . escapeshellarg($root) . ' reset --hard ' . escapeshellarg($update['pre_commit']) . ' 2>&1');

    if ($reset === null || str_contains($reset, 'error') || str_contains($reset, 'fatal'))
    {
        return [
            'success' => false,
            'message' => $reset ?: 'Rollback failed',
        ];
    }

    devpanelStateUpdaterSave([
        'id' => $checkpoint,
        'pre_commit' => $update['pre_commit'],
        'post_commit' => $update['post_commit'],
        'status' => 'rolled_back',
        'completed_at' => date('Y-m-d H:i:s'),
    ]);

    return [
        'success' => true,
        'message' => 'Rolled back to previous version',
        'commit' => $update['pre_commit'],
    ];
}
