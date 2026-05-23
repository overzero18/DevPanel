<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/users.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

echo json_encode([
    'success' => true,
    'users' => devpanelPublicUsers(),
    'roles' => devpanelPublicRoles(),
    'permissions' => devpanelPermissionCatalog(),
    'current_user' => getCurrentUserName(),
    'current_role' => getCurrentUserRole(),
]);
