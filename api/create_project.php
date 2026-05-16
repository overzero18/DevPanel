<?php

header('Content-Type: application/json');

$name = $_POST['name'] ?? '';

$name = trim($name);

if ($name === '')
{
    echo json_encode([
        'success' => false,
        'message' => 'El nombre del proyecto es obligatorio'
    ]);

    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name))
{
    echo json_encode([
        'success' => false,
        'message' => 'Nombre inválido'
    ]);

    exit;
}

$path = '/opt/lampp/htdocs/' . $name;

if (is_dir($path))
{
    echo json_encode([
        'success' => false,
        'message' => 'El proyecto ya existe'
    ]);

    exit;
}

/* Crear proyecto */
$createCommand =
    'sudo /usr/local/bin/devpanel-create-project ' .
    escapeshellarg($name);

shell_exec($createCommand);

/* Abrir VS Code automáticamente */
$vscodeCommand =
    'sudo /usr/local/bin/devpanel-open-vscode ' .
    escapeshellarg($path);

shell_exec($vscodeCommand);

echo json_encode([

    'success' => true,
    'message' => 'Proyecto creado correctamente'

]);