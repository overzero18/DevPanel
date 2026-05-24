<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/doctor.php';

authenticateSession();
requirePermission('logs');

if (!class_exists('ZipArchive'))
{
    http_response_code(500);
    echo 'ZipArchive no está disponible en este PHP.';
    exit;
}

$root = dirname(__DIR__, 2);
$tmpDir = $root . '/tmp';

if (!is_dir($tmpDir))
{
    mkdir($tmpDir, 0775, true);
}

$zipPath = $tmpDir . '/devpanel-diagnostics-' . date('Ymd-His') . '.zip';
$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
{
    http_response_code(500);
    echo 'No se pudo crear el ZIP de diagnóstico.';
    exit;
}

$publicConfig = devpanelConfig();
foreach (['DEVPANEL_PASSWORD', 'DEVPANEL_2FA_SECRET', 'DEVPANEL_API_TOKENS', 'MYSQL_PASSWORD'] as $secretKey)
{
    unset($publicConfig[$secretKey]);
}

$zip->addFromString('doctor.json', json_encode(devpanelDoctorChecks(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$zip->addFromString('config-public.json', json_encode($publicConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$zip->addFromString('php-version.txt', PHP_VERSION . PHP_EOL);

$gitStatus = trim(shell_exec('git -C ' . escapeshellarg($root) . ' status --short 2>&1') ?? '');
$zip->addFromString('git-status.txt', $gitStatus . PHP_EOL);

$logFiles = [
    'devpanel-actions.log' => $root . '/logs/actions.log',
    'apache-error.log' => devpanelConfig('APACHE_ERROR_LOG'),
    'apache-access.log' => devpanelConfig('APACHE_ACCESS_LOG'),
    'php-error.log' => devpanelConfig('PHP_ERROR_LOG'),
];

foreach ($logFiles as $name => $path)
{
    if (is_readable($path))
    {
        $content = file($path, FILE_IGNORE_NEW_LINES);
        $tail = array_slice(is_array($content) ? $content : [], -250);
        $zip->addFromString('logs/' . $name, implode(PHP_EOL, $tail) . PHP_EOL);
    }
}

$zip->close();

logAction('diagnostics_export', basename($zipPath));

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
