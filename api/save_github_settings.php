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

$githubUser = trim($_POST['github_user'] ?? '');
$githubRepo = trim($_POST['github_repo'] ?? '');
$githubRemoteUrl = trim($_POST['github_remote_url'] ?? '');

if ($githubUser !== '' && !preg_match('/^[a-zA-Z0-9-]{1,39}$/', $githubUser))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario de GitHub inválido']);
    exit;
}

if ($githubRepo !== '' && !preg_match('/^[a-zA-Z0-9._-]{1,100}$/', $githubRepo))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Repositorio inválido']);
    exit;
}

if ($githubRemoteUrl !== '' && !preg_match('#^(https://github\.com/[a-zA-Z0-9._-]+/[a-zA-Z0-9._-]+(?:\.git)?|git@github\.com:[a-zA-Z0-9._-]+/[a-zA-Z0-9._-]+\.git)$#', $githubRemoteUrl))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'URL remota de GitHub inválida']);
    exit;
}

$configFile = __DIR__ . '/../config.php';
$config = file_exists($configFile) ? require $configFile : [];

if (!is_array($config))
{
    $config = [];
}

$config['GITHUB_USER'] = $githubUser;
$config['GITHUB_REPO'] = $githubRepo;
$config['GITHUB_REMOTE_URL'] = $githubRemoteUrl;

$passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();
$availableThemes = $config['AVAILABLE_THEMES'] ?? ['dark', 'cyber', 'ubuntu', 'glass'];
$theme = $config['THEME'] ?? 'dark';

if ((file_exists($configFile) && !is_writable($configFile)) || (!file_exists($configFile) && !is_writable(dirname($configFile))))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'config.php no tiene permisos de escritura']);
    exit;
}

if (!$passwordHash || !devpanelWriteConfig($configFile, $passwordHash, array_merge($config, [
    'AVAILABLE_THEMES' => $availableThemes,
    'THEME' => $theme
])))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar la configuración']);
    exit;
}

logAction('save_github_settings', 'GitHub settings updated');

echo json_encode(['success' => true, 'message' => 'Configuración de GitHub guardada']);
