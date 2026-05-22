<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();

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

$name = $_POST['name'] ?? '';
$name = trim($name);

if ($name === '')
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre del proyecto es obligatorio']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre inválido']);
    exit;
}

$path = rtrim(devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

if (is_dir($path))
{
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'El proyecto ya existe']);
    exit;
}

$createCommand = 'sudo /usr/local/bin/devpanel-create-project ' . escapeshellarg($name);
$createResult = runControlledCommand($createCommand);

if ($createResult['exit_code'] !== 0 || !is_dir($path))
{
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo crear el proyecto',
        'output' => $createResult['output']
    ]);
    exit;
}

$vscodeCommand = 'sudo /usr/local/bin/devpanel-open-vscode ' . escapeshellarg($path);
$vscodeResult = runControlledCommand($vscodeCommand);

logAction('create_project', $name);

echo json_encode([
    'success' => true,
    'message' => $vscodeResult['exit_code'] === 0
        ? 'Proyecto creado correctamente'
        : 'Proyecto creado, pero no se pudo abrir VS Code',
    'output' => $vscodeResult['output']
]);
