<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg sticky-top app-topbar">
    <div class="container-fluid d-flex justify-content-between align-items-center">

        <!-- Left side: Hamburger + Branding -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none btn-outline-secondary" id="sidebarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand app-brand mb-0">
                <i class="bi bi-hdd-stack-fill"></i> DevPanel
            </span>
        </div>

        <!-- Right side: Theme Selector + User Menu -->
        <div class="d-flex align-items-center gap-3">

            <!-- Theme Selector -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="themeSelector" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar tema">
                    <i class="bi bi-palette"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="themeSelector">
                    <li>
                        <a class="dropdown-item theme-option" href="#" data-theme="dark">
                            <i class="bi bi-circle-fill theme-dot" style="--theme-dot-color: #0f172a;"></i> Dark
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item theme-option" href="#" data-theme="cyber">
                            <i class="bi bi-circle-fill theme-dot" style="--theme-dot-color: #00d9ff;"></i> Cyber
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item theme-option" href="#" data-theme="ubuntu">
                            <i class="bi bi-circle-fill theme-dot" style="--theme-dot-color: #dd4814;"></i> Ubuntu
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item theme-option" href="#" data-theme="glass">
                            <i class="bi bi-circle-fill theme-dot" style="--theme-dot-color: #0ea5e9;"></i> Glass
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" title="Usuario">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                    <li>
                        <a class="dropdown-item" href="/devpanel/change_password.php">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="logout(); return false;">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
// Theme Selector
document.querySelectorAll('.theme-option').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const theme = this.getAttribute('data-theme');
        changeTheme(theme);
    });
});

// Sidebar Toggle (Mobile)
const sidebarToggle = document.getElementById('sidebarToggle');
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    });
}
</script>
