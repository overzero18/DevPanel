<?php

require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('docker');

$docker = trim(shell_exec('command -v docker') ?? '');
$compose = $docker !== '' ? trim(shell_exec($docker . ' compose version 2>&1') ?? '') : '';
$groups = trim(shell_exec('id -nG 2>/dev/null') ?? '');
$daemon = false;
$daemonOutput = 'Docker no está instalado.';

if ($docker !== '')
{
    $process = proc_open(
        [$docker, 'info', '--format', '{{.ServerVersion}}'],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        ['HOME' => sys_get_temp_dir()]
    );

    if (is_resource($process))
    {
        $output = trim(stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $daemon = proc_close($process) === 0;
        $daemonOutput = $daemon ? 'Daemon activo · ' . $output : $output;
    }
}

echo json_encode([
    'success' => true,
    'docker' => [
        'installed' => $docker !== '',
        'binary' => $docker,
        'daemon' => $daemon,
        'daemon_output' => $daemonOutput,
        'compose' => $compose !== '',
        'compose_output' => $compose,
        'user_in_group' => in_array('docker', preg_split('/\s+/', $groups) ?: [], true),
    ],
    'commands' => [
        'Instalar Docker' => 'sudo apt update && sudo apt install docker.io docker-compose-plugin',
        'Activar servicio' => 'sudo systemctl enable --now docker',
        'Permitir uso sin sudo' => 'sudo usermod -aG docker $USER',
        'Aplicar grupo' => 'newgrp docker',
        'Probar Docker' => 'docker run hello-world',
    ],
]);
