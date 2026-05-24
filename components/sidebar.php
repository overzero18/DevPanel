<?php

require_once __DIR__ . '/../includes/helpers/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$localhostUrl = devpanelConfig('LOCALHOST_URL', 'http://localhost');
$phpMyAdminUrl = devpanelConfig('PHPMYADMIN_URL', 'http://localhost/phpmyadmin');

$primaryLinks = [
    [
        'url' => '/devpanel/index.php',
        'icon' => 'speedometer2',
        'label' => 'Dashboard',
        'match' => ['index.php'],
    ],
    [
        'url' => '/devpanel/projects.php',
        'icon' => 'folder-fill',
        'label' => 'Proyectos',
        'match' => ['projects.php'],
    ],
    [
        'url' => '/devpanel/filemanager.php',
        'icon' => 'files',
        'label' => 'File Manager',
        'match' => ['filemanager.php'],
    ],
    [
        'url' => '/devpanel/index.php#logsContainer',
        'icon' => 'journal-text',
        'label' => 'Logs',
        'match' => [],
    ],
    [
        'url' => '/devpanel/doctor.php',
        'icon' => 'activity',
        'label' => 'Doctor',
        'match' => ['doctor.php'],
    ],
    [
        'url' => '/devpanel/audit.php',
        'icon' => 'clipboard-data',
        'label' => 'Auditoría',
        'match' => ['audit.php'],
    ],
    [
        'url' => '/devpanel/changelog.php',
        'icon' => 'clock-history',
        'label' => 'Changelog',
        'match' => ['changelog.php'],
    ],
    [
        'url' => '/devpanel/settings.php',
        'icon' => 'sliders',
        'label' => 'Ajustes',
        'match' => ['settings.php'],
    ],
    [
        'url' => '/devpanel/users.php',
        'icon' => 'people-fill',
        'label' => 'Usuarios',
        'match' => ['users.php'],
    ],
];

$toolLinks = [
    [
        'url' => $phpMyAdminUrl,
        'icon' => 'database-fill',
        'label' => 'phpMyAdmin',
        'external' => true,
    ],
    [
        'url' => $localhostUrl,
        'icon' => 'globe',
        'label' => 'localhost',
        'external' => true,
    ],
];

$accountLinks = [
    [
        'url' => '/devpanel/change_password.php',
        'icon' => 'key-fill',
        'label' => 'Contraseña',
        'match' => ['change_password.php'],
    ],
];

function devpanel_sidebar_link($link, $currentPage)
{
    $matches = $link['match'] ?? [];
    $isActive = in_array($currentPage, $matches, true);
    $target = !empty($link['external']) ? ' target="_blank" rel="noopener noreferrer"' : '';
    $activeClass = $isActive ? ' active' : '';

    echo '<li class="nav-item">';
    echo '<a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '" class="nav-link' . $activeClass . '"' . $target . '>';
    echo '<i class="bi bi-' . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '"></i>';
    echo '<span>' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</span>';

    if (!empty($link['external'])) {
        echo '<i class="bi bi-box-arrow-up-right external-link-icon"></i>';
    }

    echo '</a>';
    echo '</li>';
}
?>

<div class="sidebar-brand">
    <div class="sidebar-brand-mark">
        <i class="bi bi-hdd-stack-fill"></i>
    </div>
    <div>
        <div class="sidebar-brand-title">DevPanel</div>
        <div class="sidebar-brand-subtitle">Local XAMPP</div>
    </div>
</div>

<div class="sidebar-status">
    <span class="status-dot"></span>
    <span>Entorno local</span>
</div>

<nav class="sidebar-nav" aria-label="Navegación principal">
    <div class="sidebar-section">
        <div class="sidebar-section-label">Panel</div>
        <ul class="nav flex-column gap-1">
            <?php foreach ($primaryLinks as $link): ?>
                <?php devpanel_sidebar_link($link, $currentPage); ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Herramientas</div>
        <ul class="nav flex-column gap-1">
            <?php foreach ($toolLinks as $link): ?>
                <?php devpanel_sidebar_link($link, $currentPage); ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Cuenta</div>
        <ul class="nav flex-column gap-1">
            <?php foreach ($accountLinks as $link): ?>
                <?php devpanel_sidebar_link($link, $currentPage); ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<div class="sidebar-footer">
    <button type="button" onclick="logout(); return false;" class="sidebar-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Cerrar sesión</span>
    </button>
</div>
