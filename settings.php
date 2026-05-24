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

        <section class="dashboard-card runtime-settings-card" id="template-marketplace">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Marketplace de plantillas</h4>
                    <p class="text-secondary mb-0">Importa/exporta plantillas locales de proyecto en JSON.</p>
                </div>
                <button type="button" class="btn btn-outline-info" onclick="loadProjectTemplatesMarketplace()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="database-toolbar">
                <input type="file" id="templateImportFile" class="form-control" accept="application/json">
                <button type="button" class="btn btn-devpanel" onclick="importProjectTemplateFromFile()">
                    <i class="bi bi-upload"></i>
                    Importar
                </button>
            </div>

            <div class="template-preview-panel" id="templateImportPreview">
                <div class="file-manager-empty">Selecciona una plantilla para ver su contenido antes de importarla.</div>
            </div>

            <div class="database-list" id="projectTemplateMarketplace">
                <div class="file-manager-empty">Cargando plantillas...</div>
            </div>
        </section>

        <section class="dashboard-card runtime-settings-card" id="theme-customizer">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Personalizador visual</h4>
                    <p class="text-secondary mb-0">Ajusta acento, densidad y ancho de sidebar solo en este navegador.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="resetThemeCustomizer()">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset
                </button>
            </div>

            <div class="runtime-settings-grid">
                <label>
                    Color principal
                    <input type="color" id="themeAccentPrimary" class="form-control form-control-color" value="#4f9ef9">
                </label>
                <label>
                    Color secundario
                    <input type="color" id="themeAccentSecondary" class="form-control form-control-color" value="#10d981">
                </label>
                <label>
                    Densidad
                    <select id="themeDensity" class="form-select">
                        <option value="comfortable">Cómoda</option>
                        <option value="compact">Compacta</option>
                    </select>
                </label>
                <label>
                    Sidebar
                    <input type="range" id="themeSidebarWidth" class="form-range" min="220" max="320" step="10" value="260">
                </label>
            </div>
            <div class="database-toolbar mt-3">
                <button type="button" class="btn btn-outline-info" onclick="exportThemePreset()">
                    <i class="bi bi-download"></i>
                    Exportar preset
                </button>
                <input type="file" id="themePresetImportFile" class="form-control" accept="application/json">
                <button type="button" class="btn btn-outline-warning" onclick="importThemePreset()">
                    <i class="bi bi-upload"></i>
                    Importar preset
                </button>
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

        <section class="dashboard-card runtime-settings-card" id="security-settings">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Seguridad avanzada</h4>
                    <p class="text-secondary mb-0">2FA opcional y tokens locales para automatización.</p>
                </div>
                <button type="button" class="btn btn-outline-info" onclick="loadSecuritySettings()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="database-users">
                <div class="database-row">
                    <div class="database-info">
                        <i class="bi bi-shield-lock"></i>
                        <div>
                            <strong>Two-factor authentication</strong>
                            <small id="twoFactorStatus">Cargando estado...</small>
                        </div>
                    </div>
                    <div class="database-actions">
                        <button type="button" class="btn btn-sm btn-outline-info" id="twoFactorToggle" onclick="toggleTwoFactor()">
                            Cambiar
                        </button>
                    </div>
                </div>

                <div class="runtime-settings-grid mt-3" id="twoFactorSecretPanel" hidden>
                    <div>
                        <label class="form-label">QR</label>
                        <div class="two-factor-qr-frame">
                            <img id="twoFactorQr" alt="QR 2FA" width="180" height="180">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Secret TOTP</label>
                        <input type="text" class="form-control" id="twoFactorSecret" readonly>
                    </div>
                    <div>
                        <label class="form-label">URI para app autenticadora</label>
                        <input type="text" class="form-control" id="twoFactorUri" readonly>
                    </div>
                </div>
            </div>

            <div class="database-users mt-4">
                <h5 class="mb-3">API tokens</h5>
                <div class="database-toolbar">
                    <input type="text" id="apiTokenName" class="form-control" placeholder="nombre del token">
                    <select id="apiTokenRole" class="form-select">
                        <option value="viewer">viewer</option>
                        <option value="developer">developer</option>
                        <option value="admin">admin</option>
                    </select>
                    <select id="apiTokenExpiry" class="form-select">
                        <option value="7">7 días</option>
                        <option value="30" selected>30 días</option>
                        <option value="90">90 días</option>
                        <option value="365">1 año</option>
                    </select>
                    <button type="button" class="btn btn-devpanel" onclick="createApiToken()">
                        <i class="bi bi-key"></i>
                        Crear token
                    </button>
                </div>
                <div class="database-list" id="apiTokenList">
                    <div class="file-manager-empty">Cargando tokens...</div>
                </div>
            </div>

            <div class="database-users mt-4">
                <h5 class="mb-3">Configuración portable</h5>
                <div class="database-toolbar">
                    <button type="button" class="btn btn-outline-info" onclick="exportPublicConfig()">
                        <i class="bi bi-download"></i>
                        Exportar sin secretos
                    </button>
                    <input type="file" id="configImportFile" class="form-control" accept="application/json">
                    <button type="button" class="btn btn-outline-warning" onclick="importPublicConfig()">
                        <i class="bi bi-upload"></i>
                        Importar
                    </button>
                </div>
            </div>

            <div class="database-users mt-4">
                <h5 class="mb-3">Dashboard personalizable</h5>
                <div class="permission-grid" id="dashboardWidgetSettings">
                    <label class="permission-option"><input type="checkbox" value="projects" checked><div><span>Proyectos</span><small>Cards y actividad</small></div></label>
                    <label class="permission-option"><input type="checkbox" value="file-manager" checked><div><span>File Manager</span><small>Gestor en dashboard</small></div></label>
                    <label class="permission-option"><input type="checkbox" value="docker-manager" checked><div><span>Docker</span><small>Contenedores y compose</small></div></label>
                    <label class="permission-option"><input type="checkbox" value="logs-section" checked><div><span>Logs</span><small>Visor e insights</small></div></label>
                    <label class="permission-option"><input type="checkbox" value="terminal-section" checked><div><span>Terminal</span><small>Shell segura</small></div></label>
                </div>
            </div>
        </section>

    </div>

</div>

<?php include 'layout/footer.php'; ?>
