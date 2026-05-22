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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php
    // Load theme CSS if helper is available
    @include_once __DIR__ . '/../includes/helpers/theme.php';
    if (function_exists('devpanel_print_theme_link')) {
        devpanel_print_theme_link();
    }
    ?>
</head>

<body>

    <div class="main-container d-flex">