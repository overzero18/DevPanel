<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

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

$path = $_POST['path'] ?? '';
$action = $_POST['action'] ?? 'status';
$branch = trim($_POST['branch'] ?? '');
$remoteUrl = trim($_POST['remote_url'] ?? devpanelConfig('GITHUB_REMOTE_URL', ''));
$target = trim($_POST['target'] ?? '');

function devpanelValidateGitBranch(string $branch): bool
{
    return preg_match('/^[a-zA-Z0-9._\/-]{1,120}$/', $branch) === 1
        && !str_contains($branch, '..')
        && !str_starts_with($branch, '-')
        && !str_ends_with($branch, '/');
}

function devpanelValidateGitRemote(string $remote): bool
{
    return preg_match('#^(https://github\.com/[a-zA-Z0-9._-]+/[a-zA-Z0-9._-]+(?:\.git)?|git@github\.com:[a-zA-Z0-9._-]+/[a-zA-Z0-9._-]+\.git)$#', $remote) === 1;
}

if ($action === 'clone')
{
    if (!devpanelValidateGitRemote($remoteUrl))
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Remote GitHub inválido']);
        exit;
    }

    if ($target === '')
    {
        $target = preg_replace('/\.git$/', '', basename(str_replace(':', '/', $remoteUrl)));
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{1,100}$/', $target))
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Carpeta destino inválida']);
        exit;
    }

    $basePath = obtenerRutaBase();
    $targetPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $target;

    if (file_exists($targetPath))
    {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'La carpeta destino ya existe']);
        exit;
    }

    $commands = [
        'clone' => ['git', 'clone', $remoteUrl, $targetPath]
    ];
}
else
{
    if (!$path || !is_dir($path) || !validatePath($path) || !is_dir($path . '/.git'))
    {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Repositorio no permitido']);
        exit;
    }

    $commands = [
        'status' => ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'status', '--short', '--branch'],
        'pull' => ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'pull', '--ff-only'],
        'log' => ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'log', '--oneline', '-n', '8'],
        'push' => ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'push', '-u', 'origin', 'HEAD'],
        'branches' => ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'branch', '--all'],
    ];

    if ($action === 'set_remote')
    {
        if (!devpanelValidateGitRemote($remoteUrl))
        {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Remote GitHub inválido']);
            exit;
        }

        $commands[$action] = ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'remote', 'set-url', 'origin', $remoteUrl];
    }

    if ($action === 'checkout' || $action === 'create_branch')
    {
        if (!devpanelValidateGitBranch($branch))
        {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rama inválida']);
            exit;
        }

        $commands[$action] = $action === 'checkout'
            ? ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'checkout', $branch]
            : ['git', '-c', 'safe.directory=' . $path, '-C', $path, 'checkout', '-b', $branch];
    }
}

if (!isset($commands[$action]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción Git inválida']);
    exit;
}

$process = proc_open(
    $commands[$action],
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes,
    null,
    ['HOME' => sys_get_temp_dir()]
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo ejecutar Git']);
    exit;
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

logAction('git_action', "$action $path");

echo json_encode([
    'success' => $exitCode === 0,
    'message' => $exitCode === 0 ? 'Git ejecutado' : 'Git devolvió error',
    'output' => trim($output . $errorOutput)
]);
