<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/config.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('settings'))
{
    http_response_code(403);
    echo 'No tienes permiso para administrar ajustes.';
    exit;
}

$csrfToken = generateCsrfToken();
$githubUser = devpanelConfig('GITHUB_USER', '');
$githubRepo = devpanelConfig('GITHUB_REPO', '');
$githubRemoteUrl = devpanelConfig('GITHUB_REMOTE_URL', '');
$runtimeSettings = devpanelConfig();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">

    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">Ajustes</h1>
            <p class="text-secondary mb-0">Configuración local, permisos y GitHub del panel.</p>
        </div>
        <a href="/devpanel/doctor.php" class="btn btn-outline-info">
            <i class="bi bi-activity"></i>
            Doctor
        </a>
    </div>

    <div class="settings-layout mt-4">

        <section class="dashboard-card permissions-card" id="permissions-panel">
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
        </section>

        <section class="dashboard-card runtime-settings-card" id="runtime-settings">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Configuración local</h4>
                    <p class="text-secondary mb-0">Rutas y servicios usados por DevPanel.</p>
                </div>
                <button type="button" class="btn btn-devpanel" onclick="saveRuntimeSettings()">
                    <i class="bi bi-save"></i>
                    Guardar
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
        </section>

        <section class="dashboard-card github-settings-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">GitHub</h4>
                    <p class="text-secondary mb-0">Cada usuario configura su propio remoto local.</p>
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
            </div>
        </section>

    </div>

</div>

<?php include 'layout/footer.php'; ?>
