<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'output' => 'Method not allowed']);
    exit;
}

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

if (!preg_match('#^[a-zA-Z0-9/.\_\-]+$#', $remote))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Ruta remota inválida']);
    exit;
}

$tmpScript = '/tmp/devpanel_deploy_' . bin2hex(random_bytes(16)) . '.txt';

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

file_put_contents($tmpScript, $script);

$command = 'lftp -f ' . escapeshellarg($tmpScript) . ' 2>&1';
$output = shell_exec($command);

unlink($tmpScript);

logAction('deploy_ftp', "Deployed to $host");

echo json_encode(['success' => true, 'output' => $output]);