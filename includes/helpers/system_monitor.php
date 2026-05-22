<?php

function getAdvancedSystemStats()
{
    $stats = [];

    // CPU Usage per core
    $cpuInfo = @file_get_contents('/proc/cpuinfo');
    $cpuCount = preg_match_all('/processor/', $cpuInfo);
    $loadavg = sys_getloadavg();

    $stats['cpu'] = [
        'load_1min' => round($loadavg[0], 2),
        'load_5min' => round($loadavg[1], 2),
        'load_15min' => round($loadavg[2], 2),
        'percent' => round(($loadavg[0] / $cpuCount) * 100, 2),
        'cores' => $cpuCount
    ];

    // RAM with breakdown
    $meminfo = @file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $memTotal);
    preg_match('/MemFree:\s+(\d+)/', $meminfo, $memFree);
    preg_match('/Buffers:\s+(\d+)/', $meminfo, $buffers);
    preg_match('/Cached:\s+(\d+)/', $meminfo, $cached);

    $total = isset($memTotal[1]) ? $memTotal[1] / 1024 : 0;
    $free = isset($memFree[1]) ? $memFree[1] / 1024 : 0;
    $buffersVal = isset($buffers[1]) ? $buffers[1] / 1024 : 0;
    $cachedVal = isset($cached[1]) ? $cached[1] / 1024 : 0;
    $used = $total - $free;

    $stats['ram'] = [
        'total' => round($total),
        'used' => round($used),
        'free' => round($free),
        'buffers' => round($buffersVal),
        'cached' => round($cachedVal),
        'percent' => round(($used / $total) * 100, 2)
    ];

    // Disk by partition
    $disks = [];
    $totalDisk = disk_total_space("/");
    $freeDisk = disk_free_space("/");
    $usedDisk = $totalDisk - $freeDisk;

    $disks[] = [
        'mount' => '/',
        'total' => round($totalDisk / (1024 ** 3), 2),
        'used' => round($usedDisk / (1024 ** 3), 2),
        'free' => round($freeDisk / (1024 ** 3), 2),
        'percent' => round(($usedDisk / $totalDisk) * 100, 2)
    ];

    $stats['disk'] = $disks;

    // Temperature
    $temp = 0;
    if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
        $tempRaw = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
        $temp = round($tempRaw / 1000, 1);
    }
    $stats['temp'] = $temp;

    // Top processes
    $psOutput = @shell_exec("ps aux --no-headers | sort -k3 -nr | head -10");
    $processes = [];
    if ($psOutput) {
        $lines = explode("\n", trim($psOutput));
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', $line, 12);
            if (count($parts) >= 11) {
                $processes[] = [
                    'user' => $parts[0],
                    'pid' => $parts[1],
                    'cpu' => round($parts[2], 1),
                    'mem' => round($parts[3], 1),
                    'cmd' => substr($parts[11], 0, 30)
                ];
            }
        }
    }
    $stats['processes'] = $processes;

    // Hostname and Uptime
    $stats['hostname'] = gethostname();
    $stats['uptime'] = trim(@shell_exec('uptime -p'));

    // System Health Score
    $healthScore = 100;
    if ($stats['cpu']['percent'] > 80) $healthScore -= 15;
    if ($stats['ram']['percent'] > 80) $healthScore -= 15;
    if ($stats['disk'][0]['percent'] > 80) $healthScore -= 15;

    $stats['health'] = [
        'score' => max(0, $healthScore),
        'status' => $healthScore > 75 ? 'good' : ($healthScore > 50 ? 'warning' : 'critical')
    ];

    return $stats;
}

function recordSystemStats()
{
    $dataDir = __DIR__ . '/../../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $historyFile = $dataDir . '/stats_history.json';
    $stats = getAdvancedSystemStats();
    $stats['timestamp'] = time();

    // Read existing history
    $history = [];
    if (file_exists($historyFile)) {
        $content = file_get_contents($historyFile);
        $history = json_decode($content, true) ?? [];
    }

    // Add new stat
    $history[] = $stats;

    // Keep only last 120 entries (1 hour at 30-second intervals)
    if (count($history) > 120) {
        $history = array_slice($history, -120);
    }

    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

    return $stats;
}

function getSystemHistory()
{
    $historyFile = __DIR__ . '/../../data/stats_history.json';
    if (!file_exists($historyFile)) {
        return [];
    }

    $content = file_get_contents($historyFile);
    return json_decode($content, true) ?? [];
}
