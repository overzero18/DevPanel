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

function devpanelSummaryShouldIgnore(string $line): bool
{
    $lower = strtolower($line);
    $ignoredPatterns = [
        '[command_blocked]',
        'gtk-warning',
        'failed to open display',
        'failed to initialize display server connection',
        'unsupported or missing session type',
        'nautilus-application-message',
    ];

    foreach ($ignoredPatterns as $pattern)
    {
        if (str_contains($lower, $pattern))
        {
            return true;
        }
    }

    if (str_contains($lower, '/api/system_stats.php') && str_contains($lower, 'not found') && is_file(__DIR__ . '/../system_stats.php'))
    {
        return true;
    }

    if (str_contains($lower, 'devpanel_template_check') || str_contains($lower, 'devpanel_check_db'))
    {
        return true;
    }

    if (
        (str_contains($lower, 'incorrect definition of table mysql.')
            || str_contains($lower, 'please run mysql_upgrade'))
        && is_file(rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR) . '/mysql_upgrade_info')
    )
    {
        return true;
    }

    if (
        (str_contains($lower, 'event scheduler: an error occurred when initializing system tables')
            || str_contains($lower, 'using unique option prefix')
            || str_contains($lower, 'column count of mysql.proc is wrong'))
        && is_file(rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR) . '/mysql_upgrade_info')
    )
    {
        return true;
    }

    $isPermissionNoise = str_contains($lower, 'permiso denegado')
        || str_contains($lower, 'permission denied')
        || str_contains($lower, 'failed to open stream');

    if (!$isPermissionNoise)
    {
        return false;
    }

    $root = dirname(__DIR__, 2);
    $resolvedPaths = [
        $root . '/config.php',
        $root . '/logs',
        $root . '/tmp',
        devpanelConfig('HTDOCS_PATH'),
    ];

    foreach ($resolvedPaths as $path)
    {
        if ($path && is_writable($path) && str_contains($lower, strtolower($path)))
        {
            return true;
        }
    }

    return false;
}

function devpanelMariaDbLogPath(string $mysqlDataDir, string $hostname): string
{
    $preferred = "$mysqlDataDir/$hostname.err";

    if (is_file($preferred))
    {
        return $preferred;
    }

    $matches = glob($mysqlDataDir . '/*.err');

    return $matches ? $matches[0] : $preferred;
}

$hostname = gethostname() ?: '';
$mysqlDataDir = rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR);
$mariaDbLogPath = devpanelMariaDbLogPath($mysqlDataDir, $hostname);
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
        'path' => $mariaDbLogPath,
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
        if (devpanelSummaryShouldIgnore($line))
        {
            continue;
        }

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
