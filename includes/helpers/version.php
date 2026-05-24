<?php

function devpanelVersion(): string
{
    $file = dirname(__DIR__, 2) . '/VERSION';
    return is_readable($file) ? trim((string) file_get_contents($file)) : '0.0.0';
}

function devpanelGitCommit(): string
{
    $root = dirname(__DIR__, 2);
    $commit = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --short HEAD 2>/dev/null'));
    return $commit !== '' ? $commit : 'unknown';
}

function devpanelGitBranch(): string
{
    $root = dirname(__DIR__, 2);
    $branch = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' branch --show-current 2>/dev/null'));
    return $branch !== '' ? $branch : 'unknown';
}
