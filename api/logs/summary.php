<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('logs');

function devpanelSummaryTail(string $file, int $limit = 220): array
{
    if (!is_file($file) || !is_readable($file))
    {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    return $lines === false ? [] : array_slice($lines, -$limit);
}

function devpanelSummarySeverity(string $line): string
{
    $lower = strtolower($line);

    if (str_contains($lower, '[command_blocked]'))
    {
        return 'security';
    }

    if (preg_match('/\b(fatal|error|failed|exception|denied)\b/', $lower))
    {
        return 'danger';
    }

    if (preg_match('/\b(warning|deprecated|permission|blocked)\b/', $lower))
    {
        return 'warning';
    }

    return 'info';
}

$hostname = gethostname() ?: '';
$mysqlDataDir = rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR);
$sources = [
    'security' => [
        'label' => 'Seguridad',
        'path' => __DIR__ . '/../../logs/actions.log',
        'match' => static fn (string $line): bool => str_contains(strtolower($line), 'blocked'),
    ],
    'permissions' => [
        'label' => 'Permisos',
        'path' => devpanelConfig('PHP_ERROR_LOG'),
        'match' => static fn (string $line): bool => str_contains(strtolower($line), 'permission') || str_contains(strtolower($line), 'permiso denegado'),
    ],
    'php' => [
        'label' => 'PHP',
        'path' => devpanelConfig('PHP_ERROR_LOG'),
        'match' => static fn (string $line): bool => true,
    ],
    'apache' => [
        'label' => 'Apache',
        'path' => devpanelConfig('APACHE_ERROR_LOG'),
        'match' => static fn (string $line): bool => true,
    ],
    'mariadb' => [
        'label' => 'MariaDB',
        'path' => "$mysqlDataDir/$hostname.err",
        'match' => static fn (string $line): bool => true,
    ],
];

$groups = [];

foreach ($sources as $key => $source)
{
    $danger = 0;
    $warning = 0;
    $security = 0;
    $latest = null;

    foreach (array_reverse(devpanelSummaryTail($source['path'])) as $line)
    {
        if (!$source['match']($line))
        {
            continue;
        }

        $severity = devpanelSummarySeverity($line);

        if ($severity === 'danger') $danger++;
        if ($severity === 'warning') $warning++;
        if ($severity === 'security') $security++;

        if ($latest === null && $severity !== 'info')
        {
            $latest = $line;
        }
    }

    $groups[] = [
        'key' => $key,
        'label' => $source['label'],
        'path' => $source['path'],
        'readable' => is_readable($source['path']),
        'danger' => $danger,
        'warning' => $warning,
        'security' => $security,
        'latest' => $latest,
    ];
}

echo json_encode([
    'success' => true,
    'groups' => $groups,
]);
