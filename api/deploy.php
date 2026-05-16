<?php

header('Content-Type: application/json');

$path   = $_POST['path']   ?? '';
$host   = $_POST['host']   ?? '';
$user   = $_POST['user']   ?? '';
$pass   = $_POST['pass']   ?? '';
$remote = $_POST['remote'] ?? '/';

if (!$path || !is_dir($path))
{
    echo json_encode([

        'success' => false,
        'output' => 'Ruta inválida'

    ]);

    exit;
}

if (!$host || !$user || !$pass)
{
    echo json_encode([

        'success' => false,
        'output' => 'Faltan datos FTP'

    ]);

    exit;
}

/* Crear script temporal lftp */
$tmpScript =
    '/tmp/devpanel_deploy_' .
    uniqid() .
    '.txt';

$script = <<<EOL

set ssl:verify-certificate no

open $host

user $user $pass

mirror -R \
--delete \
--verbose \
--exclude-glob node_modules \
--exclude-glob .git \
--exclude-glob vendor \
--exclude-glob logs \
"$path" "$remote"

bye

EOL;

file_put_contents($tmpScript, $script);

/* Ejecutar lftp */
$command =
    'lftp -f ' .
    escapeshellarg($tmpScript) .
    ' 2>&1';

$output = shell_exec($command);

/* Limpiar */
unlink($tmpScript);

echo json_encode([

    'success' => true,

    'output' => $output

]);