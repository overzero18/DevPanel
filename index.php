<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/config.php';
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
$localhostUrl = devpanelConfig('LOCALHOST_URL', 'http://localhost');
$phpMyAdminUrl = devpanelConfig('PHPMYADMIN_URL', 'http://localhost/phpmyadmin');
$htdocsPath = devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');
$githubUser = devpanelConfig('GITHUB_USER', '');
$githubRepo = devpanelConfig('GITHUB_REPO', '');
$githubRemoteUrl = devpanelConfig('GITHUB_REMOTE_URL', '');
$runtimeSettings = devpanelConfig();

$projects = getProjects();
$projectCount = count($projects);
$projectTypes = array_count_values(array_map(function ($project) {
    return $project['type'];
}, $projects));
arsort($projectTypes);
$mainProjectType = $projectTypes ? array_key_first($projectTypes) : 'Sin proyectos';
$writableProjectCount = count(array_filter($projects, function ($project) {
    return $project['writable'];
}));

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

    <div class="col-xl-8">

        <div class="dashboard-card notifications-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Centro de notificaciones</h4>
                    <p class="text-secondary mb-0" id="notificationsSummary">Cargando eventos recientes.</p>
                </div>
                <div class="notification-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="dismissAllNotifications()">
                        <i class="bi bi-check2-all"></i>
                        Limpiar
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="loadNotifications()">
                        <i class="bi bi-arrow-clockwise"></i>
                        Recargar
                    </button>
                </div>
            </div>

            <div class="notifications-list" id="notificationsList">
                <div class="file-manager-empty">Cargando notificaciones...</div>
            </div>

        </div>

    </div>

    <div class="col-xl-4">

        <div class="dashboard-card notifications-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Actividad global</h4>
                    <p class="text-secondary mb-0">Últimos eventos del panel</p>
                </div>
            </div>

            <div class="activity-list" id="globalActivityList">
                <div class="file-manager-empty">Cargando actividad...</div>
            </div>

        </div>

    </div>

</div>

<div class="row g-4 mt-1">

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

            <a href="<?php echo htmlspecialchars($localhostUrl, ENT_QUOTES, 'UTF-8'); ?>"
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

                <a href="<?php echo htmlspecialchars($phpMyAdminUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank"
                    class="btn btn-devpanel">

                    <i class="bi bi-database-fill"></i>
                    phpMyAdmin
                </a>

                <a href="<?php echo htmlspecialchars($localhostUrl, ENT_QUOTES, 'UTF-8'); ?>"
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

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">
                        Proyectos detectados
                    </h4>
                    <p class="text-secondary mb-0">
                        <?php echo $projectCount; ?> proyectos en <?php echo htmlspecialchars($htdocsPath, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div class="project-summary">
                    <span>
                        <i class="bi bi-stack"></i>
                        <?php echo htmlspecialchars($mainProjectType, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span>
                        <i class="bi bi-pencil-square"></i>
                        <?php echo $writableProjectCount; ?> editables
                    </span>
                </div>
            </div>

            <div class="row g-4">

                <?php if (!$projects): ?>

                    <div class="col-12">
                        <div class="file-manager-empty">
                            No hay proyectos detectados todavía.
                        </div>
                    </div>

                <?php endif; ?>

                <?php foreach ($projects as $project): ?>

                    <div class="col-md-6 col-xl-4">

                        <div class="project-card h-100 d-flex flex-column">

                            <div class="project-card-header">
                                <span class="project-icon">
                                    <i class="bi bi-folder-fill"></i>
                                </span>

                                <div class="project-badges">
                                    <span><?php echo htmlspecialchars($project['type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="<?php echo $project['writable'] ? 'is-writable' : 'is-readonly'; ?>">
                                        <?php echo $project['writable'] ? 'editable' : 'solo lectura'; ?>
                                    </span>
                                </div>
                            </div>

                            <h5 class="mt-3">
                                <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h5>

                            <p class="small text-secondary mb-1">
                                <?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <div class="project-meta">
                                <span>
                                    <i class="bi bi-hdd"></i>
                                    <?php echo $project['size']; ?> MB
                                </span>
                                <span>
                                    <i class="bi bi-clock-history"></i>
                                    <?php echo htmlspecialchars($project['modified_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>

                            <?php if ($project['git']['enabled']): ?>
                                <div class="project-git-status <?php echo $project['git']['dirty'] ? 'is-dirty' : 'is-clean'; ?>">
                                    <i class="bi bi-git"></i>
                                    <span><?php echo htmlspecialchars($project['git']['branch'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong>
                                        <?php echo $project['git']['dirty']
                                            ? $project['git']['changes'] . ' cambios'
                                            : 'limpio'; ?>
                                    </strong>
                                </div>

                                <div class="project-git-actions">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="runGitAction(this.dataset.path, 'status')">
                                        Status
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-info btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="runGitAction(this.dataset.path, 'log')">
                                        Commits
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-warning btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="runGitAction(this.dataset.path, 'pull')">
                                        Pull
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-success btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="runGitAction(this.dataset.path, 'push')">
                                        Push
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-light btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="runGitAction(this.dataset.path, 'branches')">
                                        Ramas
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="setGitRemote(this.dataset.path)">
                                        Remote
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="checkoutGitBranch(this.dataset.path)">
                                        Cambiar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="createGitBranch(this.dataset.path)">
                                        Nueva rama
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Botones -->
                            <div class="project-actions mt-auto">

                                <!-- Abrir proyecto -->
                                <a href="<?php echo $project['url']; ?>"
                                    target="_blank"
                                    class="btn btn-devpanel">

                                    <i class="bi bi-box-arrow-up-right"></i>
                                    Abrir
                                </a>

                                <!-- Carpeta -->
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                    onclick="openFolder(this.dataset.path)">

                                    <i class="bi bi-folder2-open"></i>
                                    Carpeta
                                </button>

                                <!-- VS Code -->
                                <button
                                    type="button"
                                    class="btn btn-outline-info"
                                    data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                    onclick="openVSCode(this.dataset.path)">

                                    <i class="bi bi-code-square"></i>
                                    VS Code
                                </button>
                                

                                <!-- ZIP -->
                                <button
                                type="button"
                                class="btn btn-outline-warning"
                                data-project="<?php echo htmlspecialchars($project['name']); ?>"
                                data-path="<?php echo htmlspecialchars($project['path']); ?>"
                                onclick="openDeployModal(this.dataset.project, this.dataset.path)">

                                <i class="bi bi-file-earmark-zip"></i>
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

<!-- Actividad de proyectos -->
<div class="row mt-5" id="project-activity">

    <div class="col-12">

        <div class="dashboard-card project-activity-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Actividad de proyectos</h4>
                    <p class="text-secondary mb-0">Archivos recientes, acciones del panel y últimos commits.</p>
                </div>

                <div class="project-activity-controls">
                    <select id="projectActivitySelect" class="form-select">
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" class="btn btn-outline-info" onclick="loadProjectActivity()">
                        <i class="bi bi-arrow-clockwise"></i>
                        Recargar
                    </button>
                </div>
            </div>

            <div class="project-activity-grid">
                <div class="activity-column">
                    <h5>Archivos recientes</h5>
                    <div id="projectRecentFiles" class="activity-list">
                        <div class="file-manager-empty">Selecciona un proyecto.</div>
                    </div>
                </div>

                <div class="activity-column">
                    <h5>Acciones</h5>
                    <div id="projectActions" class="activity-list">
                        <div class="file-manager-empty">Sin acciones cargadas.</div>
                    </div>
                </div>

                <div class="activity-column">
                    <h5>Commits</h5>
                    <div id="projectCommits" class="activity-list">
                        <div class="file-manager-empty">Sin commits cargados.</div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- MariaDB Manager -->
<div class="row mt-5" id="mariadb-manager">

    <div class="col-12">

        <div class="dashboard-card database-manager-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">MariaDB</h4>
                    <p class="text-secondary mb-0">Bases de datos locales, creación y backups SQL.</p>
                </div>
                <button type="button" class="btn btn-devpanel" onclick="createDatabase()">
                    <i class="bi bi-database-add"></i>
                    Crear DB
                </button>
            </div>

            <div class="database-toolbar">
                <button type="button" class="btn btn-outline-info" onclick="loadDatabases()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>

                <button type="button" class="btn btn-outline-warning" onclick="createDatabaseUser()">
                    <i class="bi bi-person-plus"></i>
                    Crear usuario
                </button>
            </div>

            <div class="database-list" id="databaseList">
                <div class="file-manager-empty">Cargando bases de datos...</div>
            </div>

            <div class="database-users">
                <h5 class="mb-3">Usuarios</h5>
                <div class="database-list" id="databaseUsersList">
                    <div class="file-manager-empty">Cargando usuarios...</div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Permisos -->
<div class="row mt-5" id="permissions-panel">

    <div class="col-12">

        <div class="dashboard-card permissions-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Permisos del sistema</h4>
                    <p class="text-secondary mb-0" id="permissionsSummary">Comprobando rutas críticas.</p>
                </div>
                <button type="button" class="btn btn-outline-info" onclick="loadPermissions()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Revisar
                </button>
            </div>

            <div class="permissions-list" id="permissionsList">
                <div class="file-manager-empty">Cargando permisos...</div>
            </div>

        </div>

    </div>

</div>

<!-- Configuración -->
<div class="row mt-5" id="runtime-settings">

    <div class="col-12">

        <div class="dashboard-card runtime-settings-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Configuración</h4>
                    <p class="text-secondary mb-0">Rutas y servicios locales usados por DevPanel.</p>
                </div>
                <button type="button" class="btn btn-devpanel" onclick="saveRuntimeSettings()">
                    <i class="bi bi-save"></i>
                    Guardar configuración
                </button>
            </div>

            <div class="runtime-settings-grid">
                <?php
                $runtimeFields = [
                    'BASE_URL' => 'Base URL',
                    'LOCALHOST_URL' => 'Localhost URL',
                    'PHPMYADMIN_URL' => 'phpMyAdmin URL',
                    'LAMPP_PATH' => 'XAMPP path',
                    'HTDOCS_PATH' => 'htdocs path',
                    'PHP_BINARY' => 'PHP binary',
                    'APACHE_ERROR_LOG' => 'Apache error log',
                    'APACHE_ACCESS_LOG' => 'Apache access log',
                    'PHP_ERROR_LOG' => 'PHP error log',
                    'MYSQL_DATA_DIR' => 'MySQL data dir',
                    'MYSQL_HOST' => 'MySQL host',
                    'MYSQL_PORT' => 'MySQL port',
                    'MYSQL_USER' => 'MySQL user',
                    'MYSQL_PASSWORD' => 'MySQL password'
                ];
                ?>

                <?php foreach ($runtimeFields as $field => $label): ?>
                    <div>
                        <label class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <input
                            type="<?php echo $field === 'MYSQL_PASSWORD' ? 'password' : 'text'; ?>"
                            class="form-control runtime-setting-input"
                            data-setting="<?php echo strtolower($field); ?>"
                            value="<?php echo htmlspecialchars((string) ($runtimeSettings[$field] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

</div>

<!-- Docker -->
<div class="row mt-5" id="docker-manager">

    <div class="col-12">

        <div class="dashboard-card docker-manager-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Docker</h4>
                    <p class="text-secondary mb-0">Detección y listado básico de contenedores.</p>
                </div>
                <button type="button" class="btn btn-outline-info" onclick="loadDockerContainers()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="database-list" id="dockerList">
                <div class="file-manager-empty">Cargando Docker...</div>
            </div>

        </div>

    </div>

</div>

<!-- GitHub -->
<div class="row mt-5">

    <div class="col-12">

        <div class="dashboard-card github-settings-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">GitHub</h4>
                    <p class="text-secondary mb-0">Configura tu propio usuario y repositorio para este panel.</p>
                </div>
            </div>

            <div class="github-settings-grid">
                <div>
                    <label class="form-label">Usuario</label>
                    <input
                        type="text"
                        id="githubUser"
                        class="form-control"
                        value="<?php echo htmlspecialchars($githubUser, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="tu-usuario">
                </div>

                <div>
                    <label class="form-label">Repositorio</label>
                    <input
                        type="text"
                        id="githubRepo"
                        class="form-control"
                        value="<?php echo htmlspecialchars($githubRepo, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="tu-repo">
                </div>

                <div>
                    <label class="form-label">Remote URL</label>
                    <input
                        type="text"
                        id="githubRemoteUrl"
                        class="form-control"
                        value="<?php echo htmlspecialchars($githubRemoteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="https://github.com/usuario/repo.git">
                </div>

                <div class="github-settings-action">
                    <button type="button" class="btn btn-devpanel w-100" onclick="saveGithubSettings()">
                        <i class="bi bi-save"></i>
                        Guardar GitHub
                    </button>
                </div>

                <div class="github-settings-action">
                    <button type="button" class="btn btn-outline-info w-100" onclick="cloneGithubRepository()">
                        <i class="bi bi-cloud-download"></i>
                        Clonar
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- File Manager -->
<div class="row mt-5" id="file-manager">

    <div class="col-12">

        <div class="dashboard-card file-manager-card">

            <div class="file-manager-header">
                <div>
                    <h4 class="mb-1">File Manager</h4>
                    <p class="text-secondary mb-0" id="fileManagerPath"><?php echo htmlspecialchars($htdocsPath, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="file-manager-actions">
                    <span class="file-permission-pill" id="fileManagerPermission">--</span>

                    <input
                        type="search"
                        id="fileManagerSearch"
                        class="form-control file-manager-search"
                        placeholder="Buscar">

                    <button type="button" class="btn btn-outline-secondary" onclick="fileManagerGoUp()">
                        <i class="bi bi-arrow-up"></i>
                    </button>

                    <button type="button" class="btn btn-outline-info" onclick="loadFileManager()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <button type="button" class="btn btn-devpanel" onclick="createFileManagerFolder()">
                        <i class="bi bi-folder-plus"></i>
                        Carpeta
                    </button>

                    <button type="button" class="btn btn-devpanel" onclick="createFileManagerFile()">
                        <i class="bi bi-file-earmark-plus"></i>
                        Archivo
                    </button>

                    <label class="btn btn-devpanel mb-0">
                        <i class="bi bi-upload"></i>
                        Subir
                        <input type="file" id="fileManagerUpload" hidden>
                    </label>
                </div>
            </div>

            <div class="file-manager-breadcrumbs" id="fileManagerBreadcrumbs"></div>

            <div class="file-manager-layout">
                <aside class="file-manager-tree" id="fileManagerTree">
                    <div class="file-manager-empty">Cargando árbol...</div>
                </aside>

                <div class="file-manager-list" id="fileManagerContent">
                    <div class="file-manager-empty">Cargando archivos...</div>
                </div>

                <aside class="file-preview-panel">
                    <div class="file-preview-header">
                        <div>
                            <h5 class="mb-1" id="filePreviewTitle">Preview</h5>
                            <p class="text-secondary mb-0" id="filePreviewMeta">Selecciona un archivo</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" id="filePreviewSave" onclick="saveFilePreview()" hidden>
                            <i class="bi bi-save"></i>
                        </button>
                    </div>

                    <div class="file-preview-body" id="filePreviewBody">
                        <div class="file-preview-empty">
                            Selecciona un archivo para ver su contenido.
                        </div>
                    </div>

                    <div class="file-editor-status" id="fileEditorStatus" hidden>
                        <span id="fileEditorCursor">Ln 1, Col 1</span>
                        <span id="fileEditorCount">0 caracteres</span>
                    </div>
                </aside>
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

                    <select id="logProject" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
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

        <div class="dashboard-card terminal-card">

            <div class="terminal-header">

                <div>
                    <h4 class="mb-1">Terminal Linux</h4>
                    <p class="text-secondary mb-0">Comandos seguros, historial y favoritos.</p>
                </div>

                <div class="terminal-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('pwd')">pwd</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('ls')">ls</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('git status')">git</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('php -v')">php</button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearTerminal()">
                        Limpiar
                    </button>
                </div>

            </div>

            <div class="terminal-favorites" id="terminalFavorites"></div>

            <div class="terminal-layout">
                <div id="terminal" class="devpanel-terminal-shell"></div>

                <aside class="terminal-history-panel">
                    <div class="terminal-history-header">
                        <h5 class="mb-0">Historial</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTerminalHistory()">
                            Limpiar
                        </button>
                    </div>
                    <div id="terminalHistory" class="terminal-history-list"></div>
                </aside>
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
