<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/state.php';

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
        'files.write' => 'Crear, editar, subir y renombrar archivos',
        'files.upload' => 'Subir archivos',
        'files.rename' => 'Renombrar archivos y carpetas',
        'files.zip' => 'Crear ZIP desde File Manager',
        'files.delete' => 'Borrar archivos y carpetas',
        'git' => 'Git clone, pull, push, ramas',
        'git.pull' => 'Git pull',
        'git.push' => 'Git push',
        'terminal' => 'Terminal web',
        'terminal.execute' => 'Ejecutar comandos en terminal',
        'logs' => 'Logs e insights',
        'backups' => 'Crear, descargar y restaurar backups',
        'backups.restore' => 'Restaurar backups',
        'backups.delete' => 'Borrar y limpiar backups',
        'domains' => 'Dominios locales y vhosts',
        'database' => 'MariaDB manager',
        'database.import' => 'Importar SQL',
        'database.export' => 'Exportar SQL',
        'docker' => 'Docker y Docker Compose',
        'docker.actions' => 'Acciones Docker start/stop/logs/compose',
        'deploy' => 'Deploy FTP/Strato',
        'deploy.run' => 'Ejecutar deploy',
        'services' => 'Control Apache/MariaDB',
        'services.control' => 'Arrancar, parar y reiniciar servicios',
        'settings' => 'Configuración, permisos y GitHub',
    ];
}

function devpanelPermissionParents(): array
{
    return [
        'files.write' => 'files',
        'files.upload' => 'files.write',
        'files.rename' => 'files.write',
        'files.zip' => 'files.write',
        'files.delete' => 'files',
        'git.pull' => 'git',
        'git.push' => 'git',
        'terminal.execute' => 'terminal',
        'backups.restore' => 'backups',
        'backups.delete' => 'backups',
        'database.import' => 'database',
        'database.export' => 'database',
        'docker.actions' => 'docker',
        'deploy.run' => 'deploy',
        'services.control' => 'services',
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
    $projectAccess = devpanelConfig('DEVPANEL_PROJECT_ACCESS', []);
    $projectAccess = is_array($projectAccess) ? $projectAccess : [];
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
            'projects' => $projectAccess[$name] ?? ['*'],
        ];
    }

    return $users;
}

function devpanelUserProjectAccess(string $username = null): array
{
    $username = $username ?: getCurrentUserName();
    $projectAccess = devpanelConfig('DEVPANEL_PROJECT_ACCESS', []);
    $projectAccess = is_array($projectAccess) ? $projectAccess : [];

    return $projectAccess[$username] ?? ['*'];
}

function devpanelUserCanAccessProject(string $projectName, string $path = '', string $username = null): bool
{
    if (!function_exists('getCurrentUserRole') || !function_exists('getCurrentUserName'))
    {
        return true;
    }

    if (getCurrentUserRole() === 'admin')
    {
        return true;
    }

    $access = devpanelUserProjectAccess($username);

    return in_array('*', $access, true)
        || in_array($projectName, $access, true)
        || ($path !== '' && in_array($path, $access, true));
}

function devpanelPublicApiTokens(): array
{
    $tokens = devpanelStateTokenRows();

    if (!$tokens)
    {
        $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);
    }

    if (!is_array($tokens))
    {
        return [];
    }

    return array_values(array_map(static function ($token) {
        return [
            'id' => $token['id'] ?? '',
            'name' => $token['name'] ?? 'token',
            'prefix' => $token['prefix'] ?? '',
            'role' => $token['role'] ?? 'viewer',
            'created_at' => $token['created_at'] ?? null,
            'last_used_at' => $token['last_used_at'] ?? null,
            'expires_at' => $token['expires_at'] ?? null,
            'expired' => devpanelApiTokenExpired($token),
        ];
    }, $tokens));
}

function devpanelWriteSecurityConfig(array $updates): bool
{
    $configFile = dirname(__DIR__, 2) . '/config.php';
    $config = file_exists($configFile) ? require $configFile : [];

    if (!is_array($config))
    {
        $config = [];
    }

    foreach ($updates as $key => $value)
    {
        $config[$key] = $value;
    }

    $passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();

    return $passwordHash && devpanelWriteConfig($configFile, $passwordHash, $config);
}

function devpanelGenerateBase32Secret(int $length = 32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';

    for ($i = 0; $i < $length; $i++)
    {
        $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $secret;
}

function devpanelApiTokenExpired(array $token): bool
{
    $expiresAt = $token['expires_at'] ?? null;

    return $expiresAt !== null && $expiresAt !== '' && strtotime((string) $expiresAt) < time();
}

function devpanelCreateApiToken(string $name, string $role, int $expiresDays = 30): ?array
{
    $config = devpanelUsersConfig();

    if (!isset($config['roles'][$role]))
    {
        return null;
    }

    $plain = 'dp_' . bin2hex(random_bytes(24));
    $expiresDays = max(1, min(365, $expiresDays));
    $expiresAt = date('Y-m-d H:i:s', time() + ($expiresDays * 86400));
    $item = [
        'id' => hash('sha256', $plain),
        'name' => $name !== '' ? substr($name, 0, 80) : 'API token',
        'prefix' => substr($plain, 0, 10),
        'hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'last_used_at' => null,
        'expires_at' => $expiresAt,
    ];

    if (!devpanelStateUpsertToken($item))
    {
        return null;
    }

    return [
        'token' => $plain,
        'item' => [
            'id' => $item['id'],
            'name' => $item['name'],
            'prefix' => $item['prefix'],
            'role' => $item['role'],
            'created_at' => $item['created_at'],
            'last_used_at' => null,
            'expires_at' => $item['expires_at'],
            'expired' => false,
        ],
    ];
}

function devpanelFindApiToken(string $id): ?array
{
    $tokens = devpanelStateTokenRows();

    if (!$tokens)
    {
        $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);
    }

    $tokens = is_array($tokens) ? $tokens : [];

    foreach ($tokens as $token)
    {
        if (($token['id'] ?? '') === $id)
        {
            return $token;
        }
    }

    return null;
}

function devpanelTouchApiToken(string $id): bool
{
    return devpanelStateTouchToken($id);
}

function devpanelDeleteApiToken(string $id): bool
{
    return devpanelStateDeleteToken($id);
}

function devpanelRotateApiToken(string $id): ?array
{
    $existing = devpanelFindApiToken($id);
    $plain = 'dp_' . bin2hex(random_bytes(24));

    if (!$existing)
    {
        return null;
    }

    devpanelStateDeleteToken($id);
    $rotated = array_merge($existing, [
        'id' => hash('sha256', $plain),
        'prefix' => substr($plain, 0, 10),
        'hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]),
        'last_used_at' => null,
        'rotated_at' => date('Y-m-d H:i:s'),
    ]);

    if (!devpanelStateUpsertToken($rotated)) return null;

    return [
        'token' => $plain,
        'item' => [
            'id' => $rotated['id'],
            'name' => $rotated['name'] ?? 'token',
            'prefix' => $rotated['prefix'],
            'role' => $rotated['role'] ?? 'viewer',
            'created_at' => $rotated['created_at'] ?? null,
            'last_used_at' => null,
            'expires_at' => $rotated['expires_at'] ?? null,
            'expired' => devpanelApiTokenExpired($rotated),
        ],
    ];
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

function devpanelWriteProjectAccessConfig(array $projectAccess): bool
{
    return devpanelWriteSecurityConfig(['DEVPANEL_PROJECT_ACCESS' => $projectAccess]);
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
