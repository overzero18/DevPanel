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

function devpanelNotificationSeverity(string $line, string $action = '', string $detail = ''): string
{
    $action = strtolower($action);
    $lower = strtolower(trim($action . ' ' . $detail));

    if ($action === 'view_logs')
    {
        return 'info';
    }

    if (str_contains($action, 'blocked') || str_contains($action, 'failed') || str_contains($action, 'error'))
    {
        return 'danger';
    }

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
            'id' => hash('sha256', $line),
            'title' => 'Evento',
            'detail' => $line,
            'date' => '',
            'severity' => devpanelNotificationSeverity($line)
        ];
    }

    $action = str_replace('_', ' ', $matches['action']);
    $detail = trim($matches['details']);

    return [
        'id' => hash('sha256', $line),
        'title' => ucfirst($action),
        'detail' => $detail !== '' ? $detail : $matches['ip'],
        'date' => $matches['date'],
        'severity' => devpanelNotificationSeverity($line, $matches['action'], $detail)
    ];
}

function devpanelNotificationStorePath(): string
{
    return __DIR__ . '/../../logs/notifications_state.json';
}

function devpanelNotificationState(): array
{
    $file = devpanelNotificationStorePath();

    if (!file_exists($file))
    {
        return ['dismissed' => []];
    }

    $state = json_decode((string) file_get_contents($file), true);

    return is_array($state) ? array_merge(['dismissed' => []], $state) : ['dismissed' => []];
}

$items = [];
$state = devpanelNotificationState();
$dismissed = array_flip($state['dismissed'] ?? []);

if (isset($dismissed['*']))
{
    echo json_encode([
        'success' => true,
        'items' => [],
        'summary' => [
            'total' => 0,
            'warnings' => 0,
            'errors' => 0,
        ]
    ]);
    exit;
}

foreach (array_reverse(devpanelNotificationTail(__DIR__ . '/../../logs/actions.log', 80)) as $line)
{
    $item = devpanelParseActionLine($line);

    if (isset($dismissed[$item['id']]))
    {
        continue;
    }

    $items[] = $item;

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

    $item = [
        'id' => hash('sha256', "permission:$label:$path"),
        'title' => "Permiso pendiente: $label",
        'detail' => $path,
        'date' => date('Y-m-d H:i:s'),
        'severity' => 'warning'
    ];

    if (!isset($dismissed[$item['id']]))
    {
        array_unshift($items, $item);
    }
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
