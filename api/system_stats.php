<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$cpuLoad = sys_getloadavg();

$free = shell_exec('free -m');
preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free, $matches);

$totalRam = $matches[1] ?? 0;
$usedRam  = $matches[2] ?? 0;
$freeRam  = $matches[3] ?? 0;

$diskTotal = round(disk_total_space("/") / 1024 / 1024 / 1024, 2);
$diskFree  = round(disk_free_space("/") / 1024 / 1024 / 1024, 2);

$hostname = gethostname();

$uptime = shell_exec('uptime -p');

echo json_encode([
    'success' => true,
    'cpu' => round($cpuLoad[0], 2),
    'ram' => [
        'total' => $totalRam,
        'used'  => $usedRam,
        'free'  => $freeRam
    ],
    'disk' => [
        'total' => $diskTotal,
        'free'  => $diskFree
    ],
    'hostname' => $hostname,
    'uptime' => trim($uptime)
]);