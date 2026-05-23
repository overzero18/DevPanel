<?php

if (!function_exists('devpanelDefaultRuntimeConfig')) {
    function devpanelDefaultRuntimeConfig()
    {
        return [
            'BASE_URL' => '/devpanel',
            'LOCALHOST_URL' => 'http://localhost',
            'PHPMYADMIN_URL' => 'http://localhost/phpmyadmin',
            'LAMPP_PATH' => '/opt/lampp',
            'HTDOCS_PATH' => '/opt/lampp/htdocs',
            'PHP_BINARY' => '/opt/lampp/bin/php',
            'APACHE_ERROR_LOG' => '/opt/lampp/logs/error_log',
            'APACHE_ACCESS_LOG' => '/opt/lampp/logs/access_log',
            'PHP_ERROR_LOG' => '/opt/lampp/logs/php_error_log',
            'MYSQL_DATA_DIR' => '/opt/lampp/var/mysql',
            'MYSQL_HOST' => '127.0.0.1',
            'MYSQL_PORT' => 3306,
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => '',
            'GITHUB_USER' => '',
            'GITHUB_REPO' => '',
            'GITHUB_REMOTE_URL' => '',
            'EXCLUDED_PROJECT_FOLDERS' => ['dashboard', 'webalizer', 'xampp', 'phpmyadmin', 'devpanel']
        ];
    }
}

if (!function_exists('devpanelConfig')) {
    function devpanelConfig($key = null, $default = null)
    {
        static $config = null;

        if ($config === null)
        {
            $fileConfig = file_exists(__DIR__ . '/../../config.php')
                ? require __DIR__ . '/../../config.php'
                : [];

            $config = array_merge(devpanelDefaultRuntimeConfig(), is_array($fileConfig) ? $fileConfig : []);
        }

        if ($key === null)
        {
            return $config;
        }

        return $config[$key] ?? $default;
    }
}

if (!function_exists('devpanelBuildConfigContent')) {
    function devpanelBuildConfigContent($passwordHash, $availableThemes = null, $defaultTheme = 'dark', $runtimeConfig = [], $users = [], $roles = null)
    {
        $availableThemes = $availableThemes ?: ['dark', 'cyber', 'ubuntu', 'glass'];
        $runtimeConfig = array_merge(devpanelDefaultRuntimeConfig(), $runtimeConfig);

        $themesExport = var_export(array_values($availableThemes), true);
        $defaultThemeExport = var_export($defaultTheme, true);
        $hashExport = var_export($passwordHash, true);
        $runtimeConfigExport = var_export($runtimeConfig, true);
        $usersExport = var_export(is_array($users) ? $users : [], true);
        $roles = is_array($roles) ? $roles : [
            'admin' => ['*'],
            'developer' => ['dashboard', 'projects', 'files', 'git', 'terminal', 'logs', 'backups', 'domains'],
            'viewer' => ['dashboard', 'logs'],
        ];
        $rolesExport = var_export($roles, true);

        return <<<PHP
<?php

\$runtimeConfig = $runtimeConfigExport;

return array_merge(\$runtimeConfig, [
    'DEVPANEL_PASSWORD' => $hashExport,
    'DEVPANEL_USERS' => $usersExport,
    'DEVPANEL_ROLES' => $rolesExport,
    'THEME' => $defaultThemeExport,
    'AVAILABLE_THEMES' => $themesExport,
]);
PHP;
    }
}

if (!function_exists('devpanelWriteConfig')) {
    function devpanelWriteConfig($configFile, $passwordHash, $existingConfig = [])
    {
        $availableThemes = $existingConfig['AVAILABLE_THEMES'] ?? ['dark', 'cyber', 'ubuntu', 'glass'];
        $defaultTheme = $existingConfig['THEME'] ?? 'dark';
        $users = $existingConfig['DEVPANEL_USERS'] ?? [];
        $roles = $existingConfig['DEVPANEL_ROLES'] ?? null;
        $runtimeConfig = array_intersect_key($existingConfig, devpanelDefaultRuntimeConfig());
        $content = devpanelBuildConfigContent($passwordHash, $availableThemes, $defaultTheme, $runtimeConfig, $users, $roles);

        return file_put_contents($configFile, $content, LOCK_EX) !== false;
    }
}
