<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/users.php';
require_once __DIR__ . '/../../includes/projects.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

echo json_encode([
    'success' => true,
    'users' => devpanelPublicUsers(),
    'roles' => devpanelPublicRoles(),
    'permissions' => devpanelPermissionCatalog(),
    'projects' => array_map(static fn ($project) => [
        'name' => $project['name'],
        'path' => $project['path'],
    ], getProjects()),
    'current_user' => getCurrentUserName(),
    'current_role' => getCurrentUserRole(),
]);
