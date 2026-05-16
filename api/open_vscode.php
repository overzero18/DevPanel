<?php

header('Content-Type: application/json');

$path = $_POST['path'] ?? '';

if (!$path || !is_dir($path))
{
    echo json_encode([

        'success' => false,
        'message' => 'Ruta inválida'

    ]);

    exit;
}

$command =
    'sudo /usr/local/bin/devpanel-open-vscode ' .
    escapeshellarg($path);

shell_exec($command);

echo json_encode([

    'success' => true

]);