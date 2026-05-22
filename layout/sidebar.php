<!-- Sidebar -->
<div class="sidebar p-0 d-flex flex-column">

    <!-- Logo Section -->
    <div class="logo p-4">
        <h3 class="fw-bold mb-0">
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
            ['url' => '/devpanel/index.php#projects', 'icon' => 'folder-fill', 'label' => 'Proyectos'],
            ['url' => '/devpanel/change_password.php', 'icon' => 'key-fill', 'label' => 'Cambiar Contraseña'],
        ];

        foreach ($links as $link) {
            $isActive = (basename($link['url']) === $currentPage) ? 'active' : '';
            echo '<li class="nav-item">';
            echo '<a href="' . $link['url'] . '" class="nav-link ' . $isActive . '">';
            echo '<i class="bi bi-' . $link['icon'] . '"></i>';
            echo '<span>' . $link['label'] . '</span>';
            echo '</a>';
            echo '</li>';
        }
        ?>

        <!-- Divider -->
        <li class="nav-item nav-separator my-3 border-top"></li>

        <!-- External Links -->
        <li class="nav-item">
            <a href="http://localhost/phpmyadmin" target="_blank" class="nav-link">
                <i class="bi bi-database-fill"></i>
                <span>phpMyAdmin</span>
                <i class="bi bi-box-arrow-up-right external-link-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="http://localhost" target="_blank" class="nav-link">
                <i class="bi bi-globe"></i>
                <span>localhost</span>
                <i class="bi bi-box-arrow-up-right external-link-icon"></i>
            </a>
        </li>

    </ul>

    <!-- Logout Button -->
    <div class="sidebar-footer p-3 border-top">
        <a href="#" onclick="logout(); return false;" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>

</div>

<div class="app-main flex-grow-1 d-flex flex-column">
