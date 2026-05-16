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

$projectName = basename($path);

$tmpDir = __DIR__ . '/../tmp';

$zipPath = $tmpDir . '/' . $projectName . '.zip';

if (file_exists($zipPath))
{
    unlink($zipPath);
}

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE)
{
    echo json_encode([

        'success' => false,
        'message' => 'No se pudo crear ZIP'

    ]);

    exit;
}

$files = new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator($path),

    RecursiveIteratorIterator::LEAVES_ONLY

);

foreach ($files as $file)
{
    if (!$file->isDir())
    {
        $filePath = $file->getRealPath();

        $relativePath =
            substr($filePath, strlen($path) + 1);

        /* Excluir basura */
        if (
            str_contains($relativePath, 'node_modules') ||
            str_contains($relativePath, '.git') ||
            str_contains($relativePath, 'vendor')
        ) {
            continue;
        }

        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

echo json_encode([

    'success' => true,

    'download' =>
        '/devpanel/tmp/' . $projectName . '.zip'

]);