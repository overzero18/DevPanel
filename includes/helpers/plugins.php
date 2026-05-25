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
