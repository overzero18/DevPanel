<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();

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
        'path' => "$mysqlDataDir/$hostname.err"
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

$lines = tailLogLines($file, $lineLimit);

if ($query !== '')
{
    $lines = array_values(array_filter($lines, static function ($line) use ($query) {
        return stripos($line, $query) !== false;
    }));
}

if ($project !== '')
{
    $lines = array_values(array_filter($lines, static function ($line) use ($project) {
        return stripos($line, $project) !== false;
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
    'filtered' => $query !== '' || $project !== '',
    'project' => $project,
    'updated_at' => $modifiedAt ? date('Y-m-d H:i:s', $modifiedAt) : null,
    'size' => filesize($file),
    'content' => $content
]);
