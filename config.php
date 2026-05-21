<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$defaultTheme = 'dark';
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = $defaultTheme;
}

return [
    'DEVPANEL_PASSWORD' => '$2y$10$iu772z7xJ0FMcl8uIz6C2eJN05RmV.KvN.8Tv3XU60Xq5tXwuQW.K',
    'THEME' => $_SESSION['theme'],
    'AVAILABLE_THEMES' => ['dark', 'cyber', 'ubuntu', 'glass'],
];
