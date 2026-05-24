<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('deploy');
requirePermission('deploy.run');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'output' => 'Method not allowed']);
    exit;
}

checkEndpointRateLimit('deploy', 5, 60);

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'CSRF token validation failed']);
    exit;
}

$path   = $_POST['path']   ?? '';
$host   = $_POST['host']   ?? '';
$user   = $_POST['user']   ?? '';
$pass   = $_POST['pass']   ?? '';
$remote = $_POST['remote'] ?? '/';

if (!$path || !is_dir($path))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Ruta inválida']);
    exit;
}

if (!validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'Ruta no permitida']);
    exit;
}

if (!$host || !$user || !$pass)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Faltan datos FTP']);
    exit;
}

if (
    !preg_match('/^[a-zA-Z0-9.-]{1,253}$/', $host) ||
    str_contains($host, '..') ||
    str_starts_with($host, '.') ||
    str_ends_with($host, '.')
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Host FTP inválido']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._@-]{1,128}$/', $user))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Usuario FTP inválido']);
    exit;
}

if (preg_match('/[\x00-\x1F\x7F]/', $pass) || strlen($pass) > 256)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Password FTP inválido']);
    exit;
}

if (!preg_match('#^[a-zA-Z0-9/.\_\-]+$#', $remote))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Ruta remota inválida']);
    exit;
}

$hostSafe   = sanitizeFtpCredential($host);
$userSafe   = sanitizeFtpCredential($user);
$passSafe   = sanitizeFtpCredential($pass);
$pathSafe   = escapeshellarg($path);
$remoteSafe = escapeshellarg($remote);

$script = <<<EOL
open $hostSafe
user $userSafe $passSafe
mirror -R --delete --verbose --exclude-glob node_modules --exclude-glob .git --exclude-glob vendor --exclude-glob logs $pathSafe $remoteSafe
bye
EOL;

$process = proc_open(
    'lftp -f /dev/stdin',
    [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'output' => 'Error iniciando lftp']);
    exit;
}

fwrite($pipes[0], $script);
fclose($pipes[0]);

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);

fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($process);
$output .= $errorOutput;

logAction('deploy_ftp', "Deployed to $host");

echo json_encode(['success' => $exitCode === 0, 'output' => $output]);
