<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function devpanel_get_theme()
{
    if (isset($_SESSION['theme'])) {
        return $_SESSION['theme'];
    }

    $config = file_exists(__DIR__ . '/../../config.php')
        ? require __DIR__ . '/../../config.php'
        : [];

    return $config['THEME'] ?? 'dark';
}

function devpanel_list_available_themes()
{
    return ['dark', 'cyber', 'ubuntu', 'glass'];
}

function devpanel_set_theme($theme)
{
    if (in_array($theme, devpanel_list_available_themes(), true)) {
        $_SESSION['theme'] = $theme;
        return true;
    }
    return false;
}

function devpanel_theme_css_path()
{
    $theme = devpanel_get_theme();
    return '/devpanel/themes/' . $theme . '/style.css';
}

function devpanel_print_theme_link()
{
    $path = devpanel_theme_css_path();
    echo '<link rel="stylesheet" href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '">';
}
