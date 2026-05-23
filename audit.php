<?php

require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('logs'))
{
    http_response_code(403);
    echo 'No tienes permiso para ver auditoría.';
    exit;
}

$csrfToken = generateCsrfToken();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">Auditoría</h1>
            <p class="text-secondary mb-0">Acciones del panel filtradas por usuario, acción y texto.</p>
        </div>
        <button type="button" class="btn btn-outline-info" onclick="loadAuditLog()">
            <i class="bi bi-arrow-clockwise"></i>
            Recargar
        </button>
    </div>

    <div class="dashboard-card mt-4">
        <div class="database-toolbar">
            <input type="search" id="auditSearch" class="form-control" placeholder="buscar">
            <select id="auditAction" class="form-select">
                <option value="">Todas las acciones</option>
            </select>
            <select id="auditUser" class="form-select">
                <option value="">Todos los usuarios</option>
            </select>
            <select id="auditLimit" class="form-select">
                <option value="50">50</option>
                <option value="120" selected>120</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="activity-list mt-3" id="auditList">
            <div class="file-manager-empty">Cargando auditoría...</div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
