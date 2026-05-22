<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/projects.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$csrfToken = generateCsrfToken();

$apacheRunning = isApacheRunning();
$mariadbRunning = isMariaDBRunning();

$projects = getProjects();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">

<h1 class="mb-4 fw-bold">Dashboard</h1>


<!-- Estadísticas sistema -->
<div class="row g-4 mb-4">

    <!-- CPU -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card metric-card">

            <div class="metric-card-header">
                <span class="metric-icon metric-icon-info">
                    <i class="bi bi-cpu-fill"></i>
                </span>
                <span class="metric-label">CPU</span>
            </div>

            <h3 id="cpuLoad">--</h3>
            <p id="cpuDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="cpuBar"></span>
            </div>

        </div>

    </div>

    <!-- RAM -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card metric-card">

            <div class="metric-card-header">
                <span class="metric-icon metric-icon-warning">
                    <i class="bi bi-memory"></i>
                </span>
                <span class="metric-label">RAM</span>
            </div>

            <h3 id="ramUsage">--</h3>
            <p id="ramDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="ramBar"></span>
            </div>

        </div>

    </div>

    <!-- Disco -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card metric-card">

            <div class="metric-card-header">
                <span class="metric-icon metric-icon-success">
                    <i class="bi bi-hdd-fill"></i>
                </span>
                <span class="metric-label">Disco</span>
            </div>

            <h3 id="diskUsage">--</h3>
            <p id="diskDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="diskBar"></span>
            </div>

        </div>

    </div>

    <!-- Host -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card metric-card">

            <div class="metric-card-header">
                <span class="metric-icon metric-icon-primary">
                    <i class="bi bi-pc-display"></i>
                </span>
                <span class="metric-label">Host</span>
            </div>

            <h3 id="hostname" class="hostname-value">--</h3>
            <p class="metric-detail">
                Uptime: <span id="uptime">--</span>
            </p>

        </div>

    </div>

</div>

<div class="row g-4 mb-4">

    <div class="col-xl-8">

        <div class="dashboard-card monitor-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Monitor Linux</h4>
                    <p class="text-secondary mb-0">Procesos con mayor uso de CPU</p>
                </div>
                <span class="live-pill">
                    <span class="status-dot"></span>
                    Tiempo real
                </span>
            </div>

            <div class="process-list" id="processList">
                <div class="process-row process-row-empty">
                    Esperando datos del sistema
                </div>
            </div>

        </div>

    </div>

    <div class="col-xl-4">

        <div class="dashboard-card monitor-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Servicios</h4>
                    <p class="text-secondary mb-0">Estado rápido del entorno local</p>
                </div>
            </div>

            <div class="service-summary">
                <div class="service-state <?php echo $apacheRunning ? 'is-online' : 'is-offline'; ?>">
                    <i class="bi bi-server"></i>
                    <span>Apache</span>
                    <strong><?php echo $apacheRunning ? 'Activo' : 'Detenido'; ?></strong>
                </div>

                <div class="service-state <?php echo $mariadbRunning ? 'is-online' : 'is-offline'; ?>">
                    <i class="bi bi-database-fill"></i>
                    <span>MariaDB</span>
                    <strong><?php echo $mariadbRunning ? 'Activo' : 'Detenido'; ?></strong>
                </div>

                <div class="service-state is-online">
                    <i class="bi bi-code-slash"></i>
                    <span>PHP</span>
                    <strong><?php echo htmlspecialchars(phpversion(), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

        </div>

    </div>

</div>
<div class="row g-4">

    <!-- Apache -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-server <?php echo $apacheRunning ? 'text-success' : 'text-danger'; ?>"></i>

            <h5>Apache</h5>

            <p class="mb-3">
                <?php echo $apacheRunning ? 'Servicio activo' : 'Servicio detenido'; ?>
            </p>

            <button
                type="button"
                class="btn btn-devpanel w-100"
                onclick="controlService(
                    'apache',
                    '<?php echo $apacheRunning ? 'stop' : 'start'; ?>'
                )">

                <?php echo $apacheRunning ? 'Detener' : 'Iniciar'; ?>

            </button>

        </div>

    </div>

    <!-- MariaDB -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-database-fill <?php echo $mariadbRunning ? 'text-success' : 'text-danger'; ?>"></i>

            <h5>MariaDB</h5>

            <p class="mb-3">
                <?php echo $mariadbRunning ? 'Base de datos activa' : 'Base de datos detenida'; ?>
            </p>

            <button
                type="button"
                class="btn btn-devpanel w-100"
                onclick="controlService(
                    'mysql',
                    '<?php echo $mariadbRunning ? 'stop' : 'start'; ?>'
                )">

                <?php echo $mariadbRunning ? 'Detener' : 'Iniciar'; ?>

            </button>

        </div>

    </div>

    <!-- PHP -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-code-slash text-warning"></i>

            <h5>PHP</h5>

            <p class="mb-3">
                PHP <?php echo phpversion(); ?>
            </p>

            <button
                type="button"
                class="btn btn-devpanel w-100">

                Información
            </button>

        </div>

    </div>

    <!-- localhost -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-globe text-primary"></i>

            <h5>Servidor</h5>

            <p class="mb-3">
                localhost activo
            </p>

            <a href="http://localhost"
                target="_blank"
                class="btn btn-devpanel w-100">

                Abrir
            </a>

        </div>

    </div>

</div>

<!-- Accesos rápidos -->
<div class="row mt-5" id="projects">

    <div class="col-12">

        <div class="dashboard-card">

            <h4 class="mb-4">
                Accesos rápidos
            </h4>

            <div class="d-flex flex-wrap gap-3">

                <a href="http://localhost/phpmyadmin"
                    target="_blank"
                    class="btn btn-devpanel">

                    <i class="bi bi-database-fill"></i>
                    phpMyAdmin
                </a>

                <a href="http://localhost"
                    target="_blank"
                    class="btn btn-devpanel">

                    <i class="bi bi-house-fill"></i>
                    localhost
                </a>

                <button
                    type="button"
                    class="btn btn-devpanel"
                    data-bs-toggle="modal"
                    data-bs-target="#createProjectModal">

                    <i class="bi bi-plus-circle-fill"></i>
                    Nuevo Proyecto
                </button>

            </div>

        </div>

    </div>

</div>

<!-- Proyectos -->
<div class="row mt-5">

    <div class="col-12">

        <div class="dashboard-card">

            <h4 class="mb-4">
                Proyectos detectados
            </h4>

            <div class="row g-4">

                <?php foreach ($projects as $project): ?>

                    <div class="col-md-6 col-xl-4">

                        <div class="dashboard-card h-100 d-flex flex-column">

                            <i class="bi bi-folder-fill text-warning"></i>

                            <h5 class="mt-3">
                                <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h5>

                            <p class="small text-secondary mb-1">
                                <?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <p class="small text-info mb-1">
                                Tipo: <?php echo $project['type']; ?>
                            </p>

                            <p class="small text-warning mb-3">
                                Tamaño: <?php echo $project['size']; ?> MB
                            </p>

                            <!-- Botones -->
                            <div class="d-grid gap-2 mt-auto">

                                <!-- Abrir proyecto -->
                                <a href="<?php echo $project['url']; ?>"
                                    target="_blank"
                                    class="btn btn-devpanel w-100">

                                    Abrir
                                </a>

                                <!-- Carpeta -->
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary w-100"
                                    data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                    onclick="openFolder(this.dataset.path)">

                                    Carpeta
                                </button>

                                <!-- VS Code -->
                                <button
                                    type="button"
                                    class="btn btn-outline-info w-100"
                                    data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                    onclick="openVSCode(this.dataset.path)">

                                    VS Code
                                </button>
                                

                                <!-- ZIP -->
                                <button
                                type="button"
                                class="btn btn-outline-warning w-100"
                                data-project="<?php echo htmlspecialchars($project['name']); ?>"
                                data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                onclick="openDeployModal(this.dataset.project, this.dataset.path)">

                                Exportar / Deploy
                            </button>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>

<!-- Modal crear proyecto -->
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Crear nuevo proyecto
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <label class="form-label">
                    Nombre del proyecto
                </label>

                <input
                    type="text"
                    id="projectName"
                    class="form-control"
                    placeholder="ejemplo: catalogo">

                <small class="text-secondary">
                    Solo letras, números, guiones y guiones bajos.
                </small>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">

                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-devpanel"
                    onclick="createProject()">

                    Crear proyecto
                </button>

            </div>

        </div>

    </div>

</div>
<!-- Logs -->
<div class="row mt-5">

    <div class="col-12">

        <div class="dashboard-card log-viewer-card">

            <div class="log-viewer-header">

                <div>
                    <h4 class="mb-1">Logs reales</h4>
                    <p class="text-secondary mb-0" id="logMeta">
                        Selecciona un log para inspeccionar el entorno.
                    </p>
                </div>

                <div class="log-actions">
                    <label class="form-check form-switch mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="logsAutoRefresh"
                            checked>
                        <span class="form-check-label">Auto</span>
                    </label>

                    <button
                        type="button"
                        class="btn btn-outline-info"
                        onclick="loadLogs()">

                        <i class="bi bi-arrow-clockwise"></i>
                        Recargar
                    </button>
                </div>

            </div>

            <div class="log-toolbar">
                <div class="log-tabs" role="tablist" aria-label="Tipos de log">
                    <button type="button" class="log-tab active" data-log-type="apache_error">Apache error</button>
                    <button type="button" class="log-tab" data-log-type="apache_access">Apache access</button>
                    <button type="button" class="log-tab" data-log-type="php">PHP</button>
                    <button type="button" class="log-tab" data-log-type="mariadb">MariaDB</button>
                    <button type="button" class="log-tab" data-log-type="devpanel">DevPanel</button>
                </div>

                <div class="log-controls">
                    <input
                        type="search"
                        id="logSearch"
                        class="form-control"
                        placeholder="Filtrar texto">

                    <select id="logLines" class="form-select">
                        <option value="50">50 líneas</option>
                        <option value="120" selected>120 líneas</option>
                        <option value="250">250 líneas</option>
                        <option value="500">500 líneas</option>
                    </select>
                </div>
            </div>

            <pre
                id="logsContainer"
                class="devpanel-log-panel">

Cargando logs...

            </pre>

        </div>

    </div>

</div>
<!-- Terminal -->
<div class="row mt-5">

    <div class="col-12">

        <div class="dashboard-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h4 class="mb-0">
                    Terminal Linux
                </h4>

                <button
                    type="button"
                    class="btn btn-outline-danger"
                    onclick="clearTerminal()">

                    Limpiar
                </button>

            </div>

            <div id="terminal" class="devpanel-terminal-shell">
            </div>

        </div>

    </div>

</div>
<!-- Modal Deploy -->
<div class="modal fade"
    id="deployModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Exportar / Deploy
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <input
                    type="hidden"
                    id="deployProjectPath">

                <!-- Tipo -->
                <div class="mb-3">

                    <label class="form-label">
                        Acción
                    </label>

                    <select
                        id="deployType"
                        class="form-select"
                        onchange="toggleDeployOptions()">

                        <option value="zip">
                            Generar ZIP
                        </option>

                        <option value="ftp">
                            Deploy FTP / Strato
                        </option>

                    </select>

                </div>

                <!-- FTP -->
                <div id="ftpOptions" style="display:none;">

                    <div class="mb-3">

                        <label class="form-label">
                            Host FTP
                        </label>

                        <input
                            type="text"
                            id="ftpHost"
                            class="form-control">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Usuario
                        </label>

                        <input
                            type="text"
                            id="ftpUser"
                            class="form-control">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            id="ftpPass"
                            class="form-control">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Ruta remota
                        </label>

                        <input
                            type="text"
                            id="ftpRemote"
                            class="form-control"
                            value="/">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">

                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-warning"
                    onclick="executeDeploy()">

                    Ejecutar
                </button>

            </div>

        </div>

    </div>

</div>
<?php include 'layout/footer.php'; ?>
