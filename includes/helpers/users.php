<?php

require_once __DIR__ . '/config.php';

function devpanelDefaultRoles(): array
{
    return [
        'admin' => ['*'],
        'developer' => ['dashboard', 'projects', 'files', 'git', 'terminal', 'logs', 'backups', 'domains'],
        'viewer' => ['dashboard', 'logs'],
    ];
}

function devpanelPermissionCatalog(): array
{
    return [
        'dashboard' => 'Dashboard, métricas y notificaciones',
        'projects' => 'Crear proyectos y ver actividad',
        'files' => 'File Manager, VS Code y ZIP',
        'git' => 'Git clone, pull, push, ramas',
        'terminal' => 'Terminal web',
        'logs' => 'Logs e insights',
        'backups' => 'Crear, descargar y restaurar backups',
        'domains' => 'Dominios locales y vhosts',
        'database' => 'MariaDB manager',
        'docker' => 'Docker y Docker Compose',
        'deploy' => 'Deploy FTP/Strato',
        'services' => 'Control Apache/MariaDB',
        'settings' => 'Configuración, permisos y GitHub',
    ];
}

function devpanelUsersConfig(): array
{
    $config = devpanelConfig();

    return [
        'users' => is_array($config['DEVPANEL_USERS'] ?? null) ? $config['DEVPANEL_USERS'] : [],
        'roles' => is_array($config['DEVPANEL_ROLES'] ?? null) ? $config['DEVPANEL_ROLES'] : devpanelDefaultRoles(),
    ];
}

function devpanelPublicUsers(): array
{
    $config = devpanelUsersConfig();
    $users = [];

    foreach ($config['users'] as $name => $user)
    {
        if (!is_array($user))
        {
            continue;
        }

        $users[] = [
            'name' => $name,
            'role' => $user['role'] ?? 'admin',
        ];
    }

    return $users;
}

function devpanelPublicRoles(): array
{
    $config = devpanelUsersConfig();
    $catalog = devpanelPermissionCatalog();
    $roles = [];

    foreach ($config['roles'] as $name => $permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [];
        $roles[] = [
            'name' => $name,
            'permissions' => $permissions,
            'labels' => in_array('*', $permissions, true)
                ? ['Todos los permisos']
                : array_values(array_map(static fn ($permission) => $catalog[$permission] ?? $permission, $permissions)),
        ];
    }

    return $roles;
}

function devpanelWriteUsersConfig(array $users, array $roles): bool
{
    $configFile = dirname(__DIR__, 2) . '/config.php';
    $config = file_exists($configFile) ? require $configFile : [];

    if (!is_array($config))
    {
        $config = [];
    }

    $config['DEVPANEL_USERS'] = $users;
    $config['DEVPANEL_ROLES'] = $roles;

    $passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();

    return $passwordHash && devpanelWriteConfig($configFile, $passwordHash, $config);
}

function devpanelValidateUserName(string $name): bool
{
    return preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $name) === 1;
}

function devpanelNormalizePermissions(array $permissions): array
{
    $catalog = array_keys(devpanelPermissionCatalog());
    $permissions = array_values(array_unique(array_filter($permissions, static function ($permission) use ($catalog) {
        return $permission === '*' || in_array($permission, $catalog, true);
    })));

    return in_array('*', $permissions, true) ? ['*'] : $permissions;
}
