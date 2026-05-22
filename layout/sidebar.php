<!-- Sidebar -->
<div class="sidebar bg-dark text-white p-0 d-flex flex-column" style="width: 260px; min-height: 100vh; position: relative; z-index: 100;">

    <!-- Logo Section -->
    <div class="logo p-4 border-bottom" style="border-color: rgba(255, 255, 255, 0.1);">
        <h3 class="fw-bold mb-0" style="color: #3b82f6; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-hdd-stack-fill"></i>
            <span>DevPanel</span>
        </h3>
    </div>

    <!-- Navigation -->
    <ul class="nav flex-column flex-grow-1 p-3 gap-2">

        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $links = [
            ['url' => '/devpanel/index.php', 'icon' => 'speedometer2', 'label' => 'Dashboard'],
            ['url' => '/devpanel/projects.php', 'icon' => 'folder-fill', 'label' => 'Proyectos'],
            ['url' => '/devpanel/change_password.php', 'icon' => 'key-fill', 'label' => 'Cambiar Contraseña'],
        ];

        foreach ($links as $link) {
            $isActive = (basename($link['url']) === $currentPage) ? 'active' : '';
            echo '<li class="nav-item">';
            echo '<a href="' . $link['url'] . '" class="nav-link ' . $isActive . '" style="border-radius: 8px; padding: 12px 16px; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px; color: #cbd5e1;">';
            echo '<i class="bi bi-' . $link['icon'] . '" style="font-size: 18px;"></i>';
            echo '<span style="font-weight: 500;">' . $link['label'] . '</span>';
            echo '</a>';
            echo '</li>';
        }
        ?>

        <!-- Divider -->
        <li class="nav-item my-3" style="border-top: 1px solid rgba(255, 255, 255, 0.1);"></li>

        <!-- External Links -->
        <li class="nav-item">
            <a href="http://localhost/phpmyadmin" target="_blank" class="nav-link" style="border-radius: 8px; padding: 12px 16px; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px; color: #cbd5e1;">
                <i class="bi bi-database-fill" style="font-size: 18px;"></i>
                <span style="font-weight: 500;">phpMyAdmin</span>
                <i class="bi bi-box-arrow-up-right" style="font-size: 12px; margin-left: auto; color: #64748b;"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="http://localhost" target="_blank" class="nav-link" style="border-radius: 8px; padding: 12px 16px; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px; color: #cbd5e1;">
                <i class="bi bi-globe" style="font-size: 18px;"></i>
                <span style="font-weight: 500;">localhost</span>
                <i class="bi bi-box-arrow-up-right" style="font-size: 12px; margin-left: auto; color: #64748b;"></i>
            </a>
        </li>

    </ul>

    <!-- Logout Button -->
    <div class="p-3 border-top" style="border-color: rgba(255, 255, 255, 0.1);">
        <a href="#" onclick="logout(); return false;" class="nav-link text-danger" style="border-radius: 8px; padding: 12px 16px; transition: all 0.3s ease; display: flex; align-items: center; gap: 12px;">
            <i class="bi bi-box-arrow-right" style="font-size: 18px;"></i>
            <span style="font-weight: 500;">Cerrar sesión</span>
        </a>
    </div>

</div>

<!-- Styles for Sidebar -->
<style>
.sidebar {
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(20, 30, 50, 0.9) 100%);
    backdrop-filter: blur(12px);
    border-right: 1px solid rgba(59, 130, 246, 0.1);
}

.sidebar .nav-link {
    color: #cbd5e1 !important;
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.sidebar .nav-link:hover {
    background: rgba(59, 130, 246, 0.15) !important;
    color: #fff !important;
    transform: translateX(4px);
}

.sidebar .nav-link.active {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #3b82f6 !important;
    border-left: 3px solid #3b82f6;
    padding-left: 13px;
}

.sidebar .nav-link i {
    color: #3b82f6;
}

.sidebar .nav-link.active i {
    color: #3b82f6;
}

/* Responsive Sidebar */
@media (max-width: 991px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 999;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: 260px;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-container {
        flex-wrap: nowrap;
    }
}
</style>

<!-- Contenido -->
<div class="content flex-grow-1 p-4">