<?php

require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('settings'))
{
    http_response_code(403);
    echo 'No tienes permiso para administrar usuarios.';
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
            <h1 class="mb-1 fw-bold">Usuarios y roles</h1>
            <p class="text-secondary mb-0">Gestiona usuarios locales y permisos del panel.</p>
        </div>

        <button type="button" class="btn btn-outline-info" onclick="loadUsersAdmin()">
            <i class="bi bi-arrow-clockwise"></i>
            Recargar
        </button>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-12 col-xl-5">
            <div class="dashboard-card database-manager-card">
                <div class="section-title-row">
                    <div>
                        <h4 class="mb-1">Usuarios</h4>
                        <p class="text-secondary mb-0">Las contraseñas se guardan como hash bcrypt.</p>
                    </div>
                </div>

                <div class="database-toolbar">
                    <input type="text" id="adminUserName" class="form-control" placeholder="usuario">
                    <input type="password" id="adminUserPassword" class="form-control" placeholder="contraseña nueva">
                    <select id="adminUserRole" class="form-select"></select>
                    <button type="button" class="btn btn-devpanel" onclick="saveAdminUser()">
                        <i class="bi bi-save"></i>
                        Guardar
                    </button>
                </div>

                <div class="database-list" id="adminUsersList">
                    <div class="file-manager-empty">Cargando usuarios...</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="dashboard-card database-manager-card">
                <div class="section-title-row">
                    <div>
                        <h4 class="mb-1">Roles</h4>
                        <p class="text-secondary mb-0">Define qué puede hacer cada rol dentro del panel.</p>
                    </div>
                </div>

                <div class="database-toolbar">
                    <input type="text" id="adminRoleName" class="form-control" placeholder="rol">
                    <button type="button" class="btn btn-devpanel" onclick="saveAdminRole()">
                        <i class="bi bi-shield-check"></i>
                        Guardar rol
                    </button>
                </div>

                <div class="permission-grid" id="adminPermissionsList"></div>
                <div class="database-list mt-3" id="adminRolesList">
                    <div class="file-manager-empty">Cargando roles...</div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'layout/footer.php'; ?>
