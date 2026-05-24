<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/config.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('files'))
{
    http_response_code(403);
    echo 'No tienes permiso para usar File Manager.';
    exit;
}

$csrfToken = generateCsrfToken();
$htdocsPath = devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">File Manager</h1>
            <p class="text-secondary mb-0">Árbol, editor, preview, subida y acciones de archivos.</p>
        </div>
        <a href="/devpanel/index.php#file-manager" class="btn btn-outline-info">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
    </div>

    <?php include __DIR__ . '/sections/dashboard/file_manager.php'; ?>
</div>

<?php include 'layout/footer.php'; ?>
