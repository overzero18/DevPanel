<?php

require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('logs');

$limit = max(1, min(500, (int) ($_GET['limit'] ?? 120)));
$actionFilter = trim((string) ($_GET['action'] ?? ''));
$userFilter = trim((string) ($_GET['user'] ?? ''));
$query = trim((string) ($_GET['q'] ?? ''));
$file = dirname(__DIR__, 2) . '/logs/actions.log';

function devpanelParseAuditLine(string $line): ?array
{
    if (!preg_match('/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+\[([^\]]+)\]\s+\[([^\]]+)\]\s*(.*)$/', $line, $matches))
    {
        return null;
    }

    return [
        'time' => $matches[1],
        'ip' => $matches[2],
        'user' => $matches[3],
        'action' => $matches[4],
        'details' => trim($matches[5]),
    ];
}

if (!is_file($file) || !is_readable($file))
{
    echo json_encode(['success' => true, 'items' => [], 'actions' => [], 'users' => []]);
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines = $lines === false ? [] : array_reverse($lines);
$items = [];
$actions = [];
$users = [];

foreach ($lines as $line)
{
    $item = devpanelParseAuditLine($line);

    if (!$item)
    {
        continue;
    }

    $actions[$item['action']] = true;
    $users[$item['user']] = true;

    if ($actionFilter !== '' && $item['action'] !== $actionFilter)
    {
        continue;
    }

    if ($userFilter !== '' && $item['user'] !== $userFilter)
    {
        continue;
    }

    if ($query !== '' && stripos(implode(' ', $item), $query) === false)
    {
        continue;
    }

    $items[] = $item;

    if (count($items) >= $limit)
    {
        break;
    }
}

ksort($actions);
ksort($users);

echo json_encode([
    'success' => true,
    'items' => $items,
    'actions' => array_keys($actions),
    'users' => array_keys($users),
]);
