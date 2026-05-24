<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo isset($csrfToken) ? htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') : ''; ?>">

    <title>DevPanel</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- xterm -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/xterm/css/xterm.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/material-darker.css">

    <?php
    $devpanelAssetVersion = static function (string $path): string {
        $fullPath = dirname(__DIR__) . $path;
        return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
    };
    ?>
    <link rel="stylesheet" href="/devpanel/assets/css/style.css?v=<?php echo $devpanelAssetVersion('/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="/devpanel/assets/css/admin.css?v=<?php echo $devpanelAssetVersion('/assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="/devpanel/assets/css/docker.css?v=<?php echo $devpanelAssetVersion('/assets/css/docker.css'); ?>">
    <?php
    // Load theme CSS if helper is available
    @include_once __DIR__ . '/../includes/helpers/theme.php';
    @include_once __DIR__ . '/../includes/helpers/version.php';
    @include_once __DIR__ . '/../includes/helpers/config.php';
    if (function_exists('devpanel_print_theme_link')) {
        devpanel_print_theme_link();
    }
    ?>
</head>

<body>
    <?php if (function_exists('devpanelConfig') && devpanelConfig('DEVPANEL_MAINTENANCE_MODE', false)): ?>
        <div class="maintenance-banner">
            <i class="bi bi-cone-striped"></i>
            <?php echo htmlspecialchars(devpanelConfig('DEVPANEL_MAINTENANCE_MESSAGE', 'DevPanel está en mantenimiento local.'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="main-container d-flex">
