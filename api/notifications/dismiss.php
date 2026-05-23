<?php

require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('dashboard');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$id = trim((string) ($_POST['id'] ?? ''));
$clearAll = ($_POST['clear_all'] ?? '') === '1';
$stateFile = __DIR__ . '/../../logs/notifications_state.json';

if (!is_dir(dirname($stateFile)) && !mkdir(dirname($stateFile), 0755, true))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar logs']);
    exit;
}

$state = file_exists($stateFile)
    ? json_decode((string) file_get_contents($stateFile), true)
    : [];

if (!is_array($state))
{
    $state = [];
}

$state = array_merge(['dismissed' => []], $state);

if ($clearAll)
{
    $state['dismissed'] = ['*'];
}
elseif (preg_match('/^[a-f0-9]{64}$/', $id))
{
    $state['dismissed'][] = $id;
    $state['dismissed'] = array_values(array_unique($state['dismissed']));
}
else
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notificación inválida']);
    exit;
}

if (file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX) === false)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar estado']);
    exit;
}

logAction('notification_dismiss', $clearAll ? 'all' : $id);

echo json_encode(['success' => true, 'message' => 'Notificación actualizada']);
