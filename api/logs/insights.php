<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('logs');

function devpanelInsightTail(string $file, int $limit = 300): array
{
    if (!file_exists($file) || !is_readable($file))
    {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    return $lines === false ? [] : array_slice($lines, -$limit);
}

function devpanelShouldIgnoreInsight(string $line): bool
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

    if (devpanelIsResolvedPermissionNoise($lower))
    {
        return true;
    }

    return false;
}

function devpanelIsResolvedPermissionNoise(string $lower): bool
{
    if (str_contains($lower, 'devpanel_template_check') || str_contains($lower, 'devpanel_check_db'))
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
    $configFile = $root . '/config.php';
    $logsDir = $root . '/logs';
    $tmpDir = $root . '/tmp';
    $htdocsPath = devpanelConfig('HTDOCS_PATH');

    if (str_contains($lower, '/config.php') && is_writable($configFile))
    {
        return true;
    }

    if (str_contains($lower, '/logs/actions.log') && is_writable($logsDir))
    {
        return true;
    }

    if (str_contains($lower, '/includes/security.php') && is_writable($logsDir))
    {
        return true;
    }

    if (str_contains($lower, '/api/filemanager/zip.php') && is_writable($tmpDir))
    {
        return true;
    }

    if (str_contains($lower, '/api/filemanager/mkdir.php') && is_writable($htdocsPath))
    {
        return true;
    }

    return false;
}

function devpanelNormalizeInsightLine(string $line): string
{
    $line = preg_replace('/^\[[^\]]+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\[pid\s+\d+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\[client\s+[^\]]+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\s+/', ' ', $line) ?? $line;

    return trim($line);
}

$hostname = gethostname() ?: '';
$mysqlDataDir = rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR);
$sources = [
    'Apache error' => devpanelConfig('APACHE_ERROR_LOG'),
    'Apache access' => devpanelConfig('APACHE_ACCESS_LOG'),
    'PHP' => devpanelConfig('PHP_ERROR_LOG'),
    'MariaDB' => "$mysqlDataDir/$hostname.err",
    'DevPanel' => __DIR__ . '/../../logs/actions.log',
];

$patterns = [
    'danger' => '/\b(fatal|error|failed|exception|blocked|denied)\b/',
    'warning' => '/\b(warning|deprecated|permission)\b/',
];

$items = [];
$grouped = [];

foreach ($sources as $label => $file)
{
    foreach (array_reverse(devpanelInsightTail($file)) as $line)
    {
        $lower = strtolower($line);
        $severity = 'info';

        if (devpanelShouldIgnoreInsight($line))
        {
            continue;
        }

        if (preg_match($patterns['danger'], $lower))
        {
            $severity = 'danger';
        }

        if ($severity === 'info')
        {
            if (preg_match($patterns['warning'], $lower))
            {
                $severity = 'warning';
            }
        }

        if ($severity === 'info')
        {
            continue;
        }

        $key = $label . '|' . $severity . '|' . devpanelNormalizeInsightLine($line);

        if (isset($grouped[$key]))
        {
            $grouped[$key]['count']++;
            continue;
        }

        $grouped[$key] = [
            'source' => $label,
            'severity' => $severity,
            'line' => $line,
            'count' => 1,
        ];
        $items = array_values($grouped);

        if (count($items) >= 30)
        {
            break 2;
        }
    }
}

$summary = ['danger' => 0, 'warning' => 0, 'info' => 0];
$occurrences = ['danger' => 0, 'warning' => 0, 'info' => 0];

foreach ($items as $item)
{
    $severity = $item['severity'] ?? 'info';
    $summary[$severity]++;
    $occurrences[$severity] += (int) ($item['count'] ?? 1);
}

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'occurrences' => $occurrences,
    'items' => $items,
]);
