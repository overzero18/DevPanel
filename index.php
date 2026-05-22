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
<?php include 'layout/topbar.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<h1 class="mb-4 fw-bold">Dashboard</h1>


<!-- Estadísticas sistema -->
<div class="row g-4 mb-4">

    <!-- CPU -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-cpu-fill text-info"></i>

            <h5>CPU Load</h5>

            <h3 id="cpuLoad">
                --
            </h3>

        </div>

    </div>

    <!-- RAM -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-memory text-warning"></i>

            <h5>RAM</h5>

            <h3 id="ramUsage">
                --
            </h3>

        </div>

    </div>

    <!-- Disco -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-hdd-fill text-success"></i>

            <h5>Disco</h5>

            <h3 id="diskUsage">
                --
            </h3>

        </div>

    </div>

    <!-- Host -->
    <div class="col-md-6 col-xl-3">

        <div class="dashboard-card">

            <i class="bi bi-pc-display text-primary"></i>

            <h5>Host</h5>

            <h6 id="hostname">
                --
            </h6>

            <small id="uptime">
                --
            </small>

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
<div class="row mt-5">

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
                                <?php echo $project['name']; ?>
                            </h5>

                            <p class="small text-light mb-1">
                                <?php echo $project['path']; ?>
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
                                    class="btn btn-outline-light w-100"
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

        <div class="modal-content bg-dark text-white">

            <div class="modal-header border-secondary">

                <h5 class="modal-title">
                    Crear nuevo proyecto
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
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

            <div class="modal-footer border-secondary">

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

        <div class="dashboard-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h4 class="mb-0">
                    Logs Apache
                </h4>

                <button
                    type="button"
                    class="btn btn-outline-info"
                    onclick="loadLogs()">

                    Recargar
                </button>

            </div>

            <pre
                id="logsContainer"
                class="bg-black text-success p-3 rounded"
                style="
                    height: 400px;
                    overflow-y: auto;
                    font-size: 13px;
                ">

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

            <div id="terminal"
                style="
                    width: 100%;
                    height: 500px;
                    background: #000;
                    border-radius: 10px;
                    padding: 10px;
                ">
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

        <div class="modal-content bg-dark text-white">

            <div class="modal-header border-secondary">

                <h5 class="modal-title">
                    Exportar / Deploy
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
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

            <div class="modal-footer border-secondary">

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