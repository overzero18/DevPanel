<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/config.php';
require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/projects.php';
require_once __DIR__ . '/includes/helpers/project_templates.php';

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
$projectTemplates = devpanelProjectTemplates();

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

<div class="dashboard-card system-health-card mb-4">
    <div class="section-title-row">
        <div>
            <h4 class="mb-1">Estado global</h4>
            <p class="text-secondary mb-0" id="systemHealthSummary">Comprobando servicios y módulos clave.</p>
        </div>
        <button type="button" class="btn btn-outline-info" onclick="loadSystemHealth()">
            <i class="bi bi-arrow-clockwise"></i>
            Recargar
        </button>
    </div>

    <div class="system-health-grid" id="systemHealthGrid">
        <div class="system-health-item <?php echo $apacheRunning ? 'is-ok' : 'is-danger'; ?>">
            <i class="bi bi-server"></i>
            <span>Apache</span>
            <strong><?php echo $apacheRunning ? 'Activo' : 'Detenido'; ?></strong>
        </div>
        <div class="system-health-item <?php echo $mariadbRunning ? 'is-ok' : 'is-danger'; ?>">
            <i class="bi bi-database-fill"></i>
            <span>MariaDB</span>
            <strong><?php echo $mariadbRunning ? 'Activo' : 'Detenido'; ?></strong>
        </div>
        <div class="system-health-item is-pending" data-health-check="permissions">
            <i class="bi bi-shield-check"></i>
            <span>Permisos</span>
            <strong>Comprobando</strong>
        </div>
        <div class="system-health-item is-pending" data-health-check="terminal">
            <i class="bi bi-terminal"></i>
            <span>Terminal</span>
            <strong>Comprobando</strong>
        </div>
        <div class="system-health-item is-pending" data-health-check="git">
            <i class="bi bi-git"></i>
            <span>Git</span>
            <strong>Comprobando</strong>
        </div>
        <div class="system-health-item is-pending" data-health-check="logs">
            <i class="bi bi-activity"></i>
            <span>Logs</span>
            <strong>Comprobando</strong>
        </div>
    </div>
</div>

<?php include __DIR__ . '/sections/dashboard/system_metrics.php'; ?>

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
<div class="row g-4 mt-1" id="onboarding-section">
    <div class="col-12">
        <div class="dashboard-card onboarding-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Primeros pasos</h4>
                    <p class="text-secondary mb-0" id="onboardingSummary">Comprobando progreso del entorno.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="dismissOnboarding()">
                    <i class="bi bi-eye-slash"></i>
                    Ocultar
                </button>
            </div>
            <div class="onboarding-progress">
                <span id="onboardingProgressBar"></span>
            </div>
            <div class="onboarding-grid" id="onboardingChecklist">
                <div class="file-manager-empty">Cargando checklist...</div>
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
                                    <i class="bi <?php echo !empty($project['internal']) ? 'bi-speedometer2' : 'bi-folder-fill'; ?>"></i>
                                </span>

                                <div class="project-badges">
                                    <?php if (!empty($project['internal'])): ?>
                                        <span class="is-internal">panel</span>
                                    <?php endif; ?>
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

                                <button
                                    type="button"
                                    class="btn btn-outline-info"
                                    data-path="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="openProjectTerminal(this.dataset.path)">

                                    <i class="bi bi-terminal"></i>
                                    Terminal
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

<?php include __DIR__ . '/sections/dashboard/local_domains.php'; ?>
<?php include __DIR__ . '/sections/dashboard/backups.php'; ?>

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

<?php include __DIR__ . '/sections/dashboard/docker.php'; ?>

<?php include __DIR__ . '/sections/dashboard/file_manager.php'; ?>

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

                <div class="project-template-picker mt-4">
                    <label class="form-label">
                        Plantilla
                    </label>

                    <div class="project-template-grid">
                        <?php foreach ($projectTemplates as $templateKey => $template): ?>
                            <label class="project-template-option">
                                <input
                                    type="radio"
                                    name="projectTemplate"
                                    value="<?php echo htmlspecialchars($templateKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $templateKey === 'php' ? 'checked' : ''; ?>>
                                <span>
                                    <strong><?php echo htmlspecialchars($template['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars($template['description'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
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
                    class="btn btn-devpanel"
                    onclick="createProject()">

                    Crear proyecto
                </button>

            </div>

        </div>

    </div>

</div>
<!-- Logs -->
<div class="row mt-5" id="logs-section">

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

            <div class="log-insights-panel">
                <div class="section-title-row">
                    <div>
                        <h5 class="mb-1">Insights</h5>
                        <p class="text-secondary mb-0" id="logInsightsSummary">Errores y avisos recientes.</p>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="loadLogInsights()">
                        <i class="bi bi-lightning-charge"></i>
                        Analizar
                    </button>
                </div>
                <div class="activity-list" id="logInsightsList">
                    <div class="file-manager-empty">Sin análisis cargado.</div>
                </div>
            </div>

            <div class="log-health-panel">
                <div class="section-title-row">
                    <div>
                        <h5 class="mb-1">Errores por categoría</h5>
                        <p class="text-secondary mb-0">Seguridad, permisos, PHP, Apache y MariaDB.</p>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="loadLogSummary()">
                        <i class="bi bi-bar-chart"></i>
                    </button>
                </div>
                <div class="log-summary-grid" id="logSummaryGrid">
                    <div class="file-manager-empty">Cargando resumen...</div>
                </div>
            </div>

        </div>

    </div>

</div>
<?php include __DIR__ . '/sections/dashboard/terminal.php'; ?>
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
