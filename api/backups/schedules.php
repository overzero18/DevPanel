<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    echo json_encode([
        'success' => true,
        'schedules' => devpanelLoadBackupSchedules(),
        'runner' => '/opt/lampp/bin/php ' . dirname(__DIR__, 2) . '/scripts/devpanel-backup-runner.php --due',
    ]);
    exit;
}

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

$action = $_POST['action'] ?? 'save';

if ($action === 'delete')
{
    $id = trim((string) ($_POST['id'] ?? ''));

    if ($id === '' || !devpanelDeleteBackupSchedule($id))
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo borrar la programación']);
        exit;
    }

    logAction('backup_schedule_delete', $id);
    echo json_encode(['success' => true, 'message' => 'Programación eliminada']);
    exit;
}

if ($action === 'run_now')
{
    $id = trim((string) ($_POST['id'] ?? ''));
    $result = $id !== '' ? devpanelRunBackupScheduleNow($id) : null;

    if (!$result)
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo ejecutar la programación']);
        exit;
    }

    logAction('backup_schedule_run', $result['schedule']['project'] . ' ' . $result['backup']['file']);
    echo json_encode([
        'success' => true,
        'message' => 'Backup programado ejecutado',
        'schedule' => $result['schedule'],
        'backup' => $result['backup'],
    ]);
    exit;
}

$path = trim((string) ($_POST['path'] ?? ''));
$frequency = trim((string) ($_POST['frequency'] ?? 'daily'));
$enabled = ($_POST['enabled'] ?? '1') === '1';
$schedule = devpanelSaveBackupSchedule($path, $frequency, $enabled);

if (!$schedule)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Programación inválida']);
    exit;
}

logAction('backup_schedule_save', $schedule['project'] . ' ' . $schedule['frequency']);

echo json_encode([
    'success' => true,
    'message' => 'Programación guardada',
    'schedule' => $schedule,
]);
