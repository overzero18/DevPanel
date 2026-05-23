<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';
require_once __DIR__ . '/../includes/projects.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('logs');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'apache_error';
$lineLimit = (int) ($_GET['lines'] ?? 120);
$query = trim((string) ($_GET['q'] ?? ''));
$project = trim((string) ($_GET['project'] ?? ''));

$lineLimit = max(25, min(500, $lineLimit));

$hostname = gethostname() ?: '';
$mysqlDataDir = rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR);
$mariaDbLogPath = "$mysqlDataDir/$hostname.err";
$mariaDbMatches = is_file($mariaDbLogPath) ? [] : glob($mysqlDataDir . '/*.err');
if (!is_file($mariaDbLogPath) && $mariaDbMatches)
{
    $mariaDbLogPath = $mariaDbMatches[0];
}
$logs = [
    'apache_error' => [
        'label' => 'Apache error',
        'path' => devpanelConfig('APACHE_ERROR_LOG')
    ],
    'apache_access' => [
        'label' => 'Apache access',
        'path' => devpanelConfig('APACHE_ACCESS_LOG')
    ],
    'php' => [
        'label' => 'PHP',
        'path' => devpanelConfig('PHP_ERROR_LOG')
    ],
    'mariadb' => [
        'label' => 'MariaDB',
        'path' => $mariaDbLogPath
    ],
    'devpanel' => [
        'label' => 'DevPanel',
        'path' => __DIR__ . '/../logs/actions.log'
    ]
];

if (!isset($logs[$type]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Log inválido']);
    exit;
}

$file = $logs[$type]['path'];

if (!file_exists($file))
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Log no encontrado']);
    exit;
}

if (!is_readable($file))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos para leer este log']);
    exit;
}

function tailLogLines(string $file, int $lineLimit): array
{
    $handle = fopen($file, 'rb');

    if ($handle === false)
    {
        return [];
    }

    $buffer = '';
    $chunkSize = 4096;
    $position = filesize($file);

    while ($position > 0 && substr_count($buffer, "\n") <= $lineLimit)
    {
        $readSize = min($chunkSize, $position);
        $position -= $readSize;
        fseek($handle, $position);
        $buffer = fread($handle, $readSize) . $buffer;
    }

    fclose($handle);

    $lines = preg_split('/\R/', trim($buffer));

    if ($lines === false)
    {
        return [];
    }

    return array_slice($lines, -$lineLimit);
}

function isDevpanelInternalAccessLine(string $line): bool
{
    $internalPaths = [
        '/devpanel/api/logs.php',
        '/devpanel/api/logs/insights.php',
        '/devpanel/api/login.php',
        '/devpanel/api/logout.php',
        '/devpanel/api/system_stats.php',
        '/devpanel/api/session_status.php',
        '/devpanel/api/notifications/list.php',
        '/devpanel/api/permissions.php',
        '/devpanel/api/projects/activity.php',
        '/devpanel/api/docker/list.php',
        '/devpanel/api/docker/compose.php',
        '/devpanel/api/backups/list.php',
        '/devpanel/api/domains/list.php',
        '/devpanel/api/database/list.php',
        '/devpanel/api/database/users.php',
    ];

    foreach ($internalPaths as $path)
    {
        if (str_contains($line, $path))
        {
            return true;
        }
    }

    return false;
}

function isDevpanelResolvedApacheNoise(string $line): bool
{
    $lower = strtolower($line);
    $systemStatsEndpointExists = is_file(__DIR__ . '/system_stats.php');
    $patterns = [
        'lbmethod_heartbeat:notice',
        'ah02282: no slotmem from mod_heartmonitor',
        'mpm_prefork:notice',
        'ah00163: apache/',
        'core:notice',
        'ah00094: command line:',
        'suexec:notice',
        'ah01232: suexec mechanism enabled',
        'caught sigterm, shutting down',
        'www.example.com:443',
        'ah01906:',
        'ah01909:',
        '(98)address already in use',
        'ah00072: make_sock',
        'no listening sockets available, shutting down',
        'ah00015: unable to open logs',
    ];

    foreach ($patterns as $pattern)
    {
        if (str_contains($lower, $pattern))
        {
            return true;
        }
    }

    return $systemStatsEndpointExists
        && str_contains($lower, '/api/system_stats.php')
        && str_contains($lower, 'not found');
}

$lines = tailLogLines($file, $lineLimit);
$hiddenInternalLines = 0;
$hiddenNoiseLines = 0;

if ($type === 'apache_access' && $query === '' && $project === '')
{
    $before = count($lines);
    $lines = array_values(array_filter($lines, static fn ($line) => !isDevpanelInternalAccessLine($line)));
    $hiddenInternalLines = $before - count($lines);
}

if ($type === 'apache_error' && $query === '' && $project === '')
{
    $before = count($lines);
    $lines = array_values(array_filter($lines, static fn ($line) => !isDevpanelResolvedApacheNoise($line)));
    $hiddenNoiseLines = $before - count($lines);
}

if ($query !== '')
{
    $lines = array_values(array_filter($lines, static function ($line) use ($query) {
        return stripos($line, $query) !== false;
    }));
}

function getProjectLogNeedles(string $project): array
{
    $projects = getProjects();

    foreach ($projects as $item)
    {
        if ($item['name'] !== $project)
        {
            continue;
        }

        return array_values(array_unique(array_filter([
            $item['name'],
            '/' . $item['name'] . '/',
            rawurlencode($item['name']),
            $item['path'],
            $item['url'] ?? null,
        ])));
    }

    return [];
}

if ($project !== '')
{
    $needles = getProjectLogNeedles($project);

    if (!$needles)
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Proyecto inválido']);
        exit;
    }

    $lines = array_values(array_filter($lines, static function ($line) use ($needles) {
        foreach ($needles as $needle)
        {
            if (stripos($line, $needle) !== false)
            {
                return true;
            }
        }

        return false;
    }));
}

$content = implode("\n", $lines);
$modifiedAt = filemtime($file);

logAction('view_logs', "Viewed $type logs");

echo json_encode([
    'success' => true,
    'type' => $type,
    'label' => $logs[$type]['label'],
    'path' => $file,
    'lines' => count($lines),
    'limit' => $lineLimit,
    'hidden_internal_lines' => $hiddenInternalLines,
    'hidden_noise_lines' => $hiddenNoiseLines,
    'filtered' => $query !== '' || $project !== '',
    'project' => $project,
    'updated_at' => $modifiedAt ? date('Y-m-d H:i:s', $modifiedAt) : null,
    'size' => filesize($file),
    'content' => $content
]);
