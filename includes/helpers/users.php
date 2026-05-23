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

function devpanelPublicApiTokens(): array
{
    $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);

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

function devpanelCreateApiToken(string $name, string $role): ?array
{
    $config = devpanelUsersConfig();

    if (!isset($config['roles'][$role]))
    {
        return null;
    }

    $plain = 'dp_' . bin2hex(random_bytes(24));
    $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);
    $tokens = is_array($tokens) ? $tokens : [];
    $item = [
        'id' => hash('sha256', $plain),
        'name' => $name !== '' ? substr($name, 0, 80) : 'API token',
        'prefix' => substr($plain, 0, 10),
        'hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'last_used_at' => null,
    ];

    array_unshift($tokens, $item);

    if (!devpanelWriteSecurityConfig(['DEVPANEL_API_TOKENS' => array_slice($tokens, 0, 50)]))
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
        ],
    ];
}

function devpanelFindApiToken(string $id): ?array
{
    $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);
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

function devpanelDeleteApiToken(string $id): bool
{
    $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);
    $tokens = is_array($tokens) ? $tokens : [];
    $tokens = array_values(array_filter($tokens, static fn ($token) => ($token['id'] ?? '') !== $id));

    return devpanelWriteSecurityConfig(['DEVPANEL_API_TOKENS' => $tokens]);
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
