<?php

require_once __DIR__ . '/config.php';

function devpanelDoctorCommand(array $command): array
{
    $process = proc_open(
        $command,
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
        return ['ok' => false, 'output' => 'No se pudo ejecutar'];
    }

    $output = trim(stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'ok' => proc_close($process) === 0,
        'output' => $output
    ];
}

function devpanelDoctorItem(string $label, bool $ok, string $detail = '', string $severity = null): array
{
    return [
        'label' => $label,
        'ok' => $ok,
        'detail' => $detail,
        'severity' => $severity ?: ($ok ? 'ok' : 'warning')
    ];
}

function devpanelDoctorChecks(): array
{
    $projectDir = dirname(__DIR__, 2);
    $configFile = $projectDir . '/config.php';
    $htdocsPath = devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');
    $lamppPath = rtrim(devpanelConfig('LAMPP_PATH', '/opt/lampp'), DIRECTORY_SEPARATOR);
    $phpBinary = devpanelConfig('PHP_BINARY', '/opt/lampp/bin/php');
    $docker = devpanelDoctorCommand(['sh', '-lc', 'command -v docker']);
    $qrencode = devpanelDoctorCommand(['sh', '-lc', 'command -v qrencode']);
    $gitVersion = devpanelDoctorCommand(['git', '--version']);
    $fileMode = devpanelDoctorCommand(['git', '-C', $projectDir, 'config', '--get', 'core.fileMode']);
    $fileModeValue = trim($fileMode['output']);
    $fileModeSeverity = $fileModeValue === 'true' ? 'warning' : ($fileModeValue === 'false' ? 'ok' : 'info');
    $fileModeDetail = $fileModeValue === ''
        ? 'Opcional: configura core.fileMode=false para evitar cambios de permisos en Git'
        : 'core.fileMode=' . $fileModeValue;

    $items = [
        devpanelDoctorItem('Proyecto', is_dir($projectDir), $projectDir),
        devpanelDoctorItem('config.php', is_file($configFile), $configFile),
        devpanelDoctorItem('config.php escribible', is_writable($configFile), 'Permite guardar configuración desde UI'),
        devpanelDoctorItem('htdocs', is_dir($htdocsPath), $htdocsPath),
        devpanelDoctorItem('htdocs escribible', is_writable($htdocsPath), 'Permite crear/clonar proyectos desde UI'),
        devpanelDoctorItem('logs escribible', is_writable($projectDir . '/logs'), $projectDir . '/logs'),
        devpanelDoctorItem('tmp escribible', is_writable($projectDir . '/tmp'), $projectDir . '/tmp'),
        devpanelDoctorItem('LAMPP', is_file($lamppPath . '/lampp'), $lamppPath . '/lampp'),
        devpanelDoctorItem('PHP XAMPP', is_file($phpBinary), $phpBinary),
        devpanelDoctorItem('Git', $gitVersion['ok'], $gitVersion['output']),
        devpanelDoctorItem('Git fileMode', $fileModeSeverity === 'ok', $fileModeDetail, $fileModeSeverity),
        devpanelDoctorItem('Docker', $docker['ok'], $docker['ok'] ? $docker['output'] : 'No instalado o fuera de PATH', $docker['ok'] ? 'ok' : 'info'),
        devpanelDoctorItem('qrencode', $qrencode['ok'], $qrencode['ok'] ? $qrencode['output'] : 'Opcional para mostrar QR 2FA real', $qrencode['ok'] ? 'ok' : 'info'),
        devpanelDoctorItem('Apache error log', is_readable(devpanelConfig('APACHE_ERROR_LOG')), devpanelConfig('APACHE_ERROR_LOG')),
        devpanelDoctorItem('Apache access log', is_readable(devpanelConfig('APACHE_ACCESS_LOG')), devpanelConfig('APACHE_ACCESS_LOG')),
        devpanelDoctorItem('PHP error log', is_readable(devpanelConfig('PHP_ERROR_LOG')), devpanelConfig('PHP_ERROR_LOG')),
        devpanelDoctorItem('MySQL data dir', is_readable(devpanelConfig('MYSQL_DATA_DIR')), devpanelConfig('MYSQL_DATA_DIR')),
    ];

    $warnings = array_values(array_filter($items, static fn ($item) => in_array($item['severity'], ['warning', 'danger'], true)));
    $info = array_values(array_filter($items, static fn ($item) => $item['severity'] === 'info'));
    $ok = array_values(array_filter($items, static fn ($item) => $item['severity'] === 'ok'));

    return [
        'items' => $items,
        'summary' => [
            'total' => count($items),
            'ok' => count($ok),
            'warnings' => count($warnings),
            'info' => count($info),
        ],
        'commands' => [
            'Permisos base' => 'APACHE_USER=daemon ./scripts/fix-local-permissions.sh',
            'Permisos htdocs' => 'FIX_HTDOCS=1 APACHE_USER=daemon ./scripts/fix-local-permissions.sh',
            'Git fileMode' => 'git config core.fileMode false',
            'Doctor CLI' => './scripts/devpanel-doctor.sh',
            'Instalar QR 2FA' => 'sudo apt install qrencode',
            'Lint PHP' => "find . -name '*.php' -print0 | xargs -0 -n1 /opt/lampp/bin/php -l",
        ]
    ];
}
