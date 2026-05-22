<?php

require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$dockerBinary = trim(shell_exec('command -v docker') ?? '');

if ($dockerBinary === '')
{
    echo json_encode([
        'success' => true,
        'available' => false,
        'containers' => [],
        'message' => 'Docker no está instalado o no está en PATH'
    ]);
    exit;
}

$process = proc_open(
    [$dockerBinary, 'ps', '-a', '--format', '{{.Names}}|{{.Image}}|{{.Status}}'],
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
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar Docker']);
    exit;
}

$output = trim(stream_get_contents($pipes[1]));
$errorOutput = trim(stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0)
{
    echo json_encode([
        'success' => true,
        'available' => false,
        'containers' => [],
        'message' => $errorOutput !== '' ? $errorOutput : 'Docker no está disponible'
    ]);
    exit;
}

$containers = [];

foreach (preg_split('/\R/', $output) as $line)
{
    if ($line === '')
    {
        continue;
    }

    [$name, $image, $status] = array_pad(explode('|', $line, 3), 3, '');
    $containers[] = [
        'name' => $name,
        'image' => $image,
        'status' => $status
    ];
}

echo json_encode([
    'success' => true,
    'available' => true,
    'containers' => $containers
]);
