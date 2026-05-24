<?php

require_once __DIR__ . '/../../includes/security.php';

authenticateSession();

header('Content-Type: application/json');

$presets = [
    [
        'name' => 'Ocean Focus',
        'description' => 'Azul claro, acento verde y densidad cómoda.',
        'settings' => ['primary' => '#0ea5e9', 'secondary' => '#14b8a6', 'density' => 'comfortable', 'sidebarWidth' => 260],
    ],
    [
        'name' => 'Forest Ops',
        'description' => 'Verde operativo para dashboards densos.',
        'settings' => ['primary' => '#22c55e', 'secondary' => '#84cc16', 'density' => 'comfortable', 'sidebarWidth' => 250],
    ],
    [
        'name' => 'Mono Compact',
        'description' => 'Escala neutral y vista compacta para portátil.',
        'settings' => ['primary' => '#94a3b8', 'secondary' => '#e2e8f0', 'density' => 'compact', 'sidebarWidth' => 240],
    ],
    [
        'name' => 'Copper Lab',
        'description' => 'Acentos cálidos sin dominar toda la interfaz.',
        'settings' => ['primary' => '#f97316', 'secondary' => '#38bdf8', 'density' => 'comfortable', 'sidebarWidth' => 270],
    ],
];

echo json_encode(['success' => true, 'presets' => $presets]);
