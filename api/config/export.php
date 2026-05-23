<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

authenticateSession();
requirePermission('settings');

$config = devpanelConfig();
$export = [
    'version' => 1,
    'exported_at' => date('Y-m-d H:i:s'),
    'runtime' => array_intersect_key($config, devpanelDefaultRuntimeConfig()),
    'theme' => $config['THEME'] ?? 'dark',
    'available_themes' => $config['AVAILABLE_THEMES'] ?? ['dark', 'cyber', 'ubuntu', 'glass'],
    'roles' => $config['DEVPANEL_ROLES'] ?? [],
];

unset(
    $export['runtime']['MYSQL_PASSWORD'],
    $export['runtime']['GITHUB_USER'],
    $export['runtime']['GITHUB_REPO'],
    $export['runtime']['GITHUB_REMOTE_URL']
);

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="devpanel-config-public.json"');

echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
