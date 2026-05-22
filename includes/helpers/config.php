<?php

if (!function_exists('devpanelBuildConfigContent')) {
    function devpanelBuildConfigContent($passwordHash, $availableThemes = null, $defaultTheme = 'dark')
    {
        $availableThemes = $availableThemes ?: ['dark', 'cyber', 'ubuntu', 'glass'];

        $themesExport = var_export(array_values($availableThemes), true);
        $defaultThemeExport = var_export($defaultTheme, true);
        $hashExport = var_export($passwordHash, true);

        return <<<PHP
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

\$defaultTheme = $defaultThemeExport;
if (!isset(\$_SESSION['theme'])) {
    \$_SESSION['theme'] = \$defaultTheme;
}

return [
    'DEVPANEL_PASSWORD' => $hashExport,
    'THEME' => \$_SESSION['theme'],
    'AVAILABLE_THEMES' => $themesExport,
];
PHP;
    }
}

if (!function_exists('devpanelWriteConfig')) {
    function devpanelWriteConfig($configFile, $passwordHash, $existingConfig = [])
    {
        $availableThemes = $existingConfig['AVAILABLE_THEMES'] ?? ['dark', 'cyber', 'ubuntu', 'glass'];
        $defaultTheme = $existingConfig['THEME'] ?? 'dark';
        $content = devpanelBuildConfigContent($passwordHash, $availableThemes, $defaultTheme);

        return file_put_contents($configFile, $content, LOCK_EX) !== false;
    }
}
