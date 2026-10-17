<?php

require_once __DIR__ . '/config.php';

function devpanelPluginCatalog(): array
{
    return [
        'docker' => [
            'name' => 'Docker',
            'description' => 'Contenedores, Compose, logs y asistente de instalación.',
            'icon' => 'boxes',
            'permission' => 'docker',
            'checks' => ['docker'],
        ],
        'database' => [
            'name' => 'MariaDB',
            'description' => 'Bases de datos, usuarios, importación, exportación y backups.',
            'icon' => 'database-fill',
            'permission' => 'database',
            'checks' => ['mysql'],
        ],
        'git' => [
            'name' => 'Git',
            'description' => 'Estado, ramas, pull, push y actualización del panel.',
            'icon' => 'git',
            'permission' => 'git',
            'checks' => ['git'],
        ],
        'backups' => [
            'name' => 'Backups',
            'description' => 'Backups, restauración visual, programación e historial.',
            'icon' => 'archive-fill',
            'permission' => 'backups',
            'checks' => ['zip'],
        ],
        'terminal' => [
            'name' => 'Terminal',
            'description' => 'Terminal web segura con directorio por proyecto.',
            'icon' => 'terminal-fill',
            'permission' => 'terminal',
            'checks' => ['shell'],
        ],
        'templates' => [
            'name' => 'Plantillas',
            'description' => 'Marketplace local de plantillas de proyectos.',
            'icon' => 'layout-text-window-reverse',
            'permission' => 'projects',
            'checks' => ['filesystem'],
        ],
        'domains' => [
            'name' => 'Dominios locales',
            'description' => 'Dominios .test y vhosts para proyectos locales.',
            'icon' => 'globe2',
            'permission' => 'domains',
            'checks' => ['apache'],
        ],
    ];
}

function devpanelEnabledPluginKeys(): array
{
    $enabled = devpanelConfig('DEVPANEL_PLUGINS_ENABLED', array_keys(devpanelPluginCatalog()));

    return is_array($enabled) ? array_values(array_unique(array_filter($enabled, 'is_string'))) : [];
}

function devpanelPluginEnabled(string $key): bool
{
    return in_array($key, devpanelEnabledPluginKeys(), true);
}

function devpanelPublicPlugins(): array
{
    $enabled = devpanelEnabledPluginKeys();
    $plugins = [];

    foreach (devpanelPluginCatalog() as $key => $plugin)
    {
        $plugins[] = array_merge($plugin, [
            'key' => $key,
            'enabled' => in_array($key, $enabled, true),
        ]);
    }

    return $plugins;
}

function devpanelWritePluginConfig(array $enabled): bool
{
    $enabled = array_values(array_intersect(array_keys(devpanelPluginCatalog()), array_unique($enabled)));
    $configFile = dirname(__DIR__, 2) . '/config.php';
    $config = is_file($configFile) ? require $configFile : [];
    $config = is_array($config) ? $config : [];
    $config['DEVPANEL_PLUGINS_ENABLED'] = $enabled;
    $passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();

    return $passwordHash && devpanelWriteConfig($configFile, $passwordHash, $config);
}

function devpanelPluginMarketplace(): array
{
    // A plugin executes in the panel's trust boundary. Keep this catalog empty
    // until each entry has a reviewed, immutable source and a verification path.
    return [];
}

function devpanelPluginIdIsValid(string $pluginId): bool
{
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $pluginId) === 1;
}

function devpanelMarketplaceRepositoryAllowed(string $repositoryUrl): bool
{
    foreach (devpanelPluginMarketplace() as $plugin)
    {
        if (isset($plugin['repository']) && hash_equals($plugin['repository'], $repositoryUrl))
        {
            return true;
        }
    }

    return false;
}

function devpanelPluginInstall(string $repositoryUrl): array
{
    if (!devpanelMarketplaceRepositoryAllowed($repositoryUrl))
    {
        return [
            'success' => false,
            'message' => 'Repository is not in the reviewed plugin catalog',
        ];
    }

    $pluginDir = dirname(__DIR__, 2) . '/api/plugins';
    $tmpDir = dirname(__DIR__, 2) . '/tmp/plugin-install-' . uniqid();

    if (!is_dir($pluginDir) && !mkdir($pluginDir, 0755, true))
    {
        return [
            'success' => false,
            'message' => 'Failed to create plugin directory',
        ];
    }

    $pluginRoot = realpath($pluginDir);

    if ($pluginRoot === false)
    {
        return [
            'success' => false,
            'message' => 'Plugin directory is unavailable',
        ];
    }

    if (!mkdir($tmpDir, 0755, true))
    {
        return [
            'success' => false,
            'message' => 'Failed to create temporary directory',
        ];
    }

    $clone = shell_exec('git clone ' . escapeshellarg($repositoryUrl) . ' ' . escapeshellarg($tmpDir) . ' 2>&1');

    if ($clone === null || str_contains($clone, 'error') || str_contains($clone, 'fatal'))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => $clone ?: 'Failed to clone repository',
        ];
    }

    if (!is_file($tmpDir . '/plugin.json'))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => 'Invalid plugin: missing plugin.json',
        ];
    }

    $manifest = json_decode(file_get_contents($tmpDir . '/plugin.json'), true);

    if (!is_array($manifest) || empty($manifest['id']))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => 'Invalid plugin manifest',
        ];
    }

    $pluginId = (string) $manifest['id'];

    if (!devpanelPluginIdIsValid($pluginId))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => 'Invalid plugin ID',
        ];
    }

    $targetDir = $pluginRoot . DIRECTORY_SEPARATOR . $pluginId;

    if (is_dir($targetDir))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => 'Plugin already installed',
        ];
    }

    if (!rename($tmpDir, $targetDir))
    {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return [
            'success' => false,
            'message' => 'Failed to install plugin',
        ];
    }

    return [
        'success' => true,
        'message' => 'Plugin installed successfully',
        'plugin_id' => $pluginId,
        'manifest' => $manifest,
    ];
}

function devpanelPluginUninstall(string $pluginId): array
{
    if (!devpanelPluginIdIsValid($pluginId))
    {
        return [
            'success' => false,
            'message' => 'Invalid plugin ID',
        ];
    }

    $pluginRoot = realpath(dirname(__DIR__, 2) . '/api/plugins');

    if ($pluginRoot === false)
    {
        return [
            'success' => false,
            'message' => 'Plugin directory is unavailable',
        ];
    }

    $pluginDir = $pluginRoot . DIRECTORY_SEPARATOR . $pluginId;

    if (!is_dir($pluginDir))
    {
        return [
            'success' => false,
            'message' => 'Plugin not found',
        ];
    }

    $remove = shell_exec('rm -rf ' . escapeshellarg($pluginDir) . ' 2>&1');

    if (is_dir($pluginDir))
    {
        return [
            'success' => false,
            'message' => 'Failed to uninstall plugin',
        ];
    }

    return [
        'success' => true,
        'message' => 'Plugin uninstalled successfully',
    ];
}
