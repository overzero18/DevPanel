<?php

function devpanelCiWorkflowFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = glob($root . '/.github/workflows/*.yml') ?: [];

    return array_values(array_map(static fn ($file) => basename($file), $files));
}

function devpanelCiLocalStatus(): array
{
    $root = dirname(__DIR__, 2);
    $workflows = devpanelCiWorkflowFiles();
    $scripts = [
        'scripts/devpanel-ci-smoke.sh',
        'scripts/devpanel-local-smoke.sh',
        'scripts/devpanel-unit-tests.php',
        'scripts/devpanel-build-release.sh',
        'scripts/devpanel-release-check.sh',
    ];
    $checks = [];

    foreach ($scripts as $script)
    {
        $checks[] = [
            'name' => $script,
            'ok' => is_file($root . '/' . $script),
            'detail' => is_file($root . '/' . $script) ? 'Disponible' : 'No encontrado',
        ];
    }

    $checks[] = [
        'name' => 'Workflows GitHub Actions',
        'ok' => count($workflows) > 0,
        'detail' => $workflows ? implode(', ', $workflows) : 'Sin workflows',
    ];

    return [
        'success' => true,
        'workflows' => $workflows,
        'checks' => $checks,
        'summary' => [
            'total' => count($checks),
            'ok' => count(array_filter($checks, static fn ($check) => !empty($check['ok']))),
        ],
    ];
}

function devpanelCiRemoteRuns(int $limit = 5): array
{
    $root = dirname(__DIR__, 2);
    $gh = trim((string) shell_exec('command -v gh 2>/dev/null'));

    if ($gh === '')
    {
        return [
            'available' => false,
            'message' => 'GitHub CLI no está instalado o no está en PATH.',
            'runs' => [],
        ];
    }

    $cmd = sprintf(
        'cd %s && %s run list --limit %d --json name,status,conclusion,headBranch,headSha,createdAt,url 2>/dev/null',
        escapeshellarg($root),
        escapeshellcmd($gh),
        max(1, min(20, $limit))
    );
    $json = trim((string) shell_exec($cmd));
    $runs = json_decode($json, true);

    if (!is_array($runs))
    {
        return [
            'available' => false,
            'message' => 'GitHub CLI no devolvió runs. Ejecuta gh auth login si quieres ver CI remoto.',
            'runs' => [],
        ];
    }

    return [
        'available' => true,
        'message' => 'Runs remotos cargados desde GitHub CLI.',
        'runs' => $runs,
    ];
}
