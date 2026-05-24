<?php

function devpanelProjectTemplates(): array
{
    return array_merge([
        'php' => [
            'label' => 'PHP básico',
            'description' => 'index.php, assets y README inicial.',
        ],
        'static' => [
            'label' => 'HTML estático',
            'description' => 'index.html con CSS/JS listo para editar.',
        ],
        'node' => [
            'label' => 'Node / Vite',
            'description' => 'package.json, src y estructura frontend mínima.',
        ],
        'laravel' => [
            'label' => 'Laravel starter',
            'description' => 'Estructura base y notas para instalar Laravel.',
        ],
        'wordpress' => [
            'label' => 'WordPress starter',
            'description' => 'Estructura base wp-content y notas de instalación.',
        ],
    ], devpanelLoadCustomProjectTemplates());
}

function devpanelProjectTemplatesFile(): string
{
    return dirname(__DIR__, 2) . '/logs/project_templates.json';
}

function devpanelLoadCustomProjectTemplates(): array
{
    $file = devpanelProjectTemplatesFile();

    if (!is_file($file))
    {
        return [];
    }

    $items = json_decode((string) file_get_contents($file), true);

    return is_array($items) ? $items : [];
}

function devpanelSaveCustomProjectTemplates(array $templates): bool
{
    $file = devpanelProjectTemplatesFile();

    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true))
    {
        return false;
    }

    return file_put_contents($file, json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function devpanelImportProjectTemplate(array $template): ?array
{
    $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower((string) ($template['key'] ?? $template['label'] ?? 'custom')));
    $files = is_array($template['files'] ?? null) ? $template['files'] : [];

    if ($key === '' || !$files)
    {
        return null;
    }

    $safeFiles = [];

    foreach ($files as $path => $content)
    {
        $path = trim(str_replace('\\', '/', (string) $path));

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..'))
        {
            continue;
        }

        $safeFiles[$path] = (string) $content;
    }

    if (!$safeFiles)
    {
        return null;
    }

    $templates = devpanelLoadCustomProjectTemplates();
    $templates[$key] = [
        'label' => substr((string) ($template['label'] ?? $key), 0, 80),
        'description' => substr((string) ($template['description'] ?? 'Plantilla importada'), 0, 180),
        'custom' => true,
        'files' => $safeFiles,
    ];

    return devpanelSaveCustomProjectTemplates($templates) ? [$key, $templates[$key]] : null;
}

function devpanelTemplateWrite(string $basePath, string $relativePath, string $content): bool
{
    $target = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
    $directory = dirname($target);

    if (!is_dir($directory) && !mkdir($directory, 0755, true))
    {
        return false;
    }

    return file_put_contents($target, $content, LOCK_EX) !== false;
}

function devpanelApplyProjectTemplate(string $path, string $name, string $template): bool
{
    $templates = devpanelProjectTemplates();

    if (!isset($templates[$template]))
    {
        return false;
    }

    $title = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    $commonReadme = "# $name\n\nProyecto generado desde DevPanel.\n";

    if ($template === 'php')
    {
        return devpanelTemplateWrite($path, 'index.php', "<?php\n\$title = '$title';\n?>\n<!doctype html>\n<html lang=\"es\">\n<head>\n    <meta charset=\"utf-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <title><?php echo \$title; ?></title>\n    <link rel=\"stylesheet\" href=\"assets/css/style.css\">\n</head>\n<body>\n    <main class=\"page\">\n        <h1><?php echo \$title; ?></h1>\n        <p>Proyecto PHP listo para construir.</p>\n    </main>\n</body>\n</html>\n")
            && devpanelTemplateWrite($path, 'assets/css/style.css', "body {\n    margin: 0;\n    min-height: 100vh;\n    display: grid;\n    place-items: center;\n    background: #101827;\n    color: #f8fafc;\n    font-family: system-ui, sans-serif;\n}\n\n.page {\n    width: min(760px, calc(100% - 32px));\n}\n")
            && devpanelTemplateWrite($path, 'README.md', $commonReadme);
    }

    if ($template === 'static')
    {
        return devpanelTemplateWrite($path, 'index.html', "<!doctype html>\n<html lang=\"es\">\n<head>\n    <meta charset=\"utf-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <title>$title</title>\n    <link rel=\"stylesheet\" href=\"assets/css/style.css\">\n</head>\n<body>\n    <main class=\"page\">\n        <h1>$title</h1>\n        <p>Proyecto HTML estático listo para editar.</p>\n    </main>\n    <script src=\"assets/js/app.js\"></script>\n</body>\n</html>\n")
            && devpanelTemplateWrite($path, 'assets/css/style.css', "body {\n    margin: 0;\n    min-height: 100vh;\n    display: grid;\n    place-items: center;\n    background: #0f172a;\n    color: #e2e8f0;\n    font-family: system-ui, sans-serif;\n}\n")
            && devpanelTemplateWrite($path, 'assets/js/app.js', "console.log('DevPanel static starter');\n")
            && devpanelTemplateWrite($path, 'README.md', $commonReadme);
    }

    if ($template === 'node')
    {
        return devpanelTemplateWrite($path, 'package.json', json_encode([
            'name' => strtolower(str_replace('_', '-', $name)),
            'version' => '0.1.0',
            'private' => true,
            'scripts' => [
                'dev' => 'vite --host 0.0.0.0',
                'build' => 'vite build',
                'preview' => 'vite preview',
            ],
            'devDependencies' => [
                'vite' => '^5.0.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n")
            && devpanelTemplateWrite($path, 'index.html', "<div id=\"app\"></div><script type=\"module\" src=\"/src/main.js\"></script>\n")
            && devpanelTemplateWrite($path, 'src/main.js', "import './style.css';\n\ndocument.querySelector('#app').innerHTML = `<main><h1>$title</h1><p>Node/Vite starter generado desde DevPanel.</p></main>`;\n")
            && devpanelTemplateWrite($path, 'src/style.css', "body {\n    margin: 0;\n    min-height: 100vh;\n    display: grid;\n    place-items: center;\n    background: #111827;\n    color: white;\n    font-family: system-ui, sans-serif;\n}\n")
            && devpanelTemplateWrite($path, 'README.md', $commonReadme . "\n## Comandos\n\n```bash\nnpm install\nnpm run dev\n```\n");
    }

    if ($template === 'laravel')
    {
        return devpanelTemplateWrite($path, 'README.md', $commonReadme . "\n## Laravel\n\nPara instalar Laravel dentro de esta carpeta:\n\n```bash\ncomposer create-project laravel/laravel .\n```\n")
            && devpanelTemplateWrite($path, '.gitkeep', '');
    }

    if ($template === 'wordpress')
    {
        return devpanelTemplateWrite($path, 'README.md', $commonReadme . "\n## WordPress\n\nDescarga WordPress y copia sus archivos aquí. La carpeta `wp-content` ya está preparada.\n")
            && devpanelTemplateWrite($path, 'wp-content/themes/.gitkeep', '')
            && devpanelTemplateWrite($path, 'wp-content/plugins/.gitkeep', '')
            && devpanelTemplateWrite($path, 'wp-content/uploads/.gitkeep', '');
    }

    if (!empty($templates[$template]['custom']) && is_array($templates[$template]['files'] ?? null))
    {
        $ok = true;

        foreach ($templates[$template]['files'] as $relativePath => $content)
        {
            $ok = devpanelTemplateWrite($path, (string) $relativePath, (string) $content) && $ok;
        }

        return $ok;
    }

    return false;
}
