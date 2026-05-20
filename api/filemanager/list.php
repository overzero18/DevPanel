<?php

header('Content-Type: application/json');

require_once '../../includes/helpers/filesystem.php';

$path = $_GET['path'] ?? obtenerRutaBase();

if (!esRutaPermitida($path))
{
    echo json_encode([

        'success' => false,

        'message' => 'Ruta no permitida'

    ]);

    exit;
}

if (!is_dir($path))
{
    echo json_encode([

        'success' => false,

        'message' => 'Directorio no válido'

    ]);

    exit;
}

$items = listarArchivosDirectorio($path);

echo json_encode([

    'success' => true,

    'currentPath' => realpath($path),

    'items' => $items

]);