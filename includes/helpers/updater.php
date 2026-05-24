<?php

require_once __DIR__ . '/version.php';

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
