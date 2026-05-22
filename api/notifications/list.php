<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function devpanelNotificationTail(string $file, int $limit = 80): array
{
    if (!file_exists($file) || !is_readable($file))
    {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false)
    {
        return [];
    }

    return array_slice($lines, -$limit);
}

function devpanelNotificationSeverity(string $line): string
{
    $lower = strtolower($line);

    if (str_contains($lower, 'failed') || str_contains($lower, 'error') || str_contains($lower, 'blocked'))
    {
        return 'danger';
    }

    if (str_contains($lower, 'deploy') || str_contains($lower, 'zip') || str_contains($lower, 'permission'))
    {
        return 'warning';
    }

    if (str_contains($lower, 'success') || str_contains($lower, 'create') || str_contains($lower, 'save'))
    {
        return 'success';
    }

    return 'info';
}

function devpanelParseActionLine(string $line): array
{
    $pattern = '/^\[(?<date>[^\]]+)\]\s+\[(?<ip>[^\]]+)\]\s+\[(?<user>[^\]]+)\]\s+\[(?<action>[^\]]+)\]\s*(?<details>.*)$/';

    if (!preg_match($pattern, $line, $matches))
    {
        return [
            'title' => 'Evento',
            'detail' => $line,
            'date' => '',
            'severity' => devpanelNotificationSeverity($line)
        ];
    }

    $action = str_replace('_', ' ', $matches['action']);
    $detail = trim($matches['details']);

    return [
        'title' => ucfirst($action),
        'detail' => $detail !== '' ? $detail : $matches['ip'],
        'date' => $matches['date'],
        'severity' => devpanelNotificationSeverity($line)
    ];
}

$items = [];

foreach (array_reverse(devpanelNotificationTail(__DIR__ . '/../../logs/actions.log', 80)) as $line)
{
    $items[] = devpanelParseActionLine($line);

    if (count($items) >= 12)
    {
        break;
    }
}

$criticalPaths = [
    ['Config local', __DIR__ . '/../../config.php', true],
    ['htdocs', devpanelConfig('HTDOCS_PATH'), true],
    ['Logs DevPanel', __DIR__ . '/../../logs', true],
    ['Tmp DevPanel', __DIR__ . '/../../tmp', true],
];

foreach ($criticalPaths as [$label, $path, $needsWrite])
{
    $ok = file_exists($path) && is_readable($path) && (!$needsWrite || is_writable($path));

    if ($ok)
    {
        continue;
    }

    array_unshift($items, [
        'title' => "Permiso pendiente: $label",
        'detail' => $path,
        'date' => date('Y-m-d H:i:s'),
        'severity' => 'warning'
    ]);
}

echo json_encode([
    'success' => true,
    'items' => array_slice($items, 0, 16),
    'summary' => [
        'total' => count($items),
        'warnings' => count(array_filter($items, static fn ($item) => $item['severity'] === 'warning')),
        'errors' => count(array_filter($items, static fn ($item) => $item['severity'] === 'danger')),
    ]
]);
