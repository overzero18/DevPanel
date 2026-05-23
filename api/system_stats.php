<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
ini_set('serialize_precision', '-1');

authenticateSession();
requirePermission('dashboard');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function bytesToGb(float $bytes): float
{
    return round($bytes / 1024 / 1024 / 1024, 2);
}

function readMemInfo(): array
{
    $memInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $values = [];

    if ($memInfo === false)
    {
        return [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'percent' => 0
        ];
    }

    foreach ($memInfo as $line)
    {
        if (preg_match('/^([A-Za-z_()]+):\s+(\d+)/', $line, $matches))
        {
            $values[$matches[1]] = (int) $matches[2];
        }
    }

    $totalMb = round(($values['MemTotal'] ?? 0) / 1024);
    $availableMb = round(($values['MemAvailable'] ?? ($values['MemFree'] ?? 0)) / 1024);
    $usedMb = max(0, $totalMb - $availableMb);
    $percent = $totalMb > 0 ? round(($usedMb / $totalMb) * 100, 1) : 0;

    return [
        'total' => $totalMb,
        'used' => $usedMb,
        'free' => $availableMb,
        'percent' => $percent
    ];
}

function countCpuCores(): int
{
    $cpuInfo = @file('/proc/cpuinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($cpuInfo === false)
    {
        return 1;
    }

    $processors = 0;

    foreach ($cpuInfo as $line)
    {
        if (str_starts_with($line, 'processor'))
        {
            $processors++;
        }
    }

    return max(1, $processors);
}

function formatUptime(): string
{
    $uptime = @file_get_contents('/proc/uptime');

    if ($uptime === false)
    {
        return '--';
    }

    $seconds = (int) floor((float) explode(' ', trim($uptime))[0]);
    $days = intdiv($seconds, 86400);
    $seconds %= 86400;
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;
    $minutes = intdiv($seconds, 60);

    $parts = [];

    if ($days > 0)
    {
        $parts[] = $days . 'd';
    }

    if ($hours > 0)
    {
        $parts[] = $hours . 'h';
    }

    $parts[] = $minutes . 'm';

    return implode(' ', $parts);
}

function readTopProcesses(): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = @proc_open(
        ['ps', '-eo', 'pid,comm,%cpu,%mem', '--sort=-%cpu'],
        $descriptors,
        $pipes
    );

    if (!is_resource($process))
    {
        return [];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $lines = array_slice(preg_split('/\R/', trim($output)), 1, 6);
    $processes = [];

    foreach ($lines as $line)
    {
        if (!preg_match('/^\s*(\d+)\s+(.+?)\s+([\d.]+)\s+([\d.]+)\s*$/', $line, $matches))
        {
            continue;
        }

        if ($matches[2] === 'ps')
        {
            continue;
        }

        $processes[] = [
            'pid' => (int) $matches[1],
            'name' => $matches[2],
            'cpu' => round((float) $matches[3], 1),
            'memory' => round((float) $matches[4], 1)
        ];
    }

    return $processes;
}

$loadAverage = sys_getloadavg() ?: [0, 0, 0];
$cores = countCpuCores();
$cpuPercent = min(100, round(($loadAverage[0] / $cores) * 100, 1));

$ram = readMemInfo();

$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsed = $diskTotal - $diskFree;
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

$disk = [
    'total' => bytesToGb($diskTotal),
    'used' => bytesToGb($diskUsed),
    'free' => bytesToGb($diskFree),
    'percent' => $diskPercent
];

echo json_encode([
    'success' => true,
    'cpu' => round($loadAverage[0], 2),
    'cpu_metrics' => [
        'load_1' => round($loadAverage[0], 2),
        'load_5' => round($loadAverage[1], 2),
        'load_15' => round($loadAverage[2], 2),
        'cores' => $cores,
        'percent' => $cpuPercent
    ],
    'ram' => $ram,
    'disk' => $disk,
    'hostname' => gethostname() ?: '--',
    'uptime' => formatUptime(),
    'processes' => readTopProcesses()
]);
