<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';
require_once __DIR__ . '/../../includes/projects.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('logs');

function devpanelInsightTail(string $file, int $limit = 300): array
{
    if (!file_exists($file) || !is_readable($file))
    {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    return $lines === false ? [] : array_slice($lines, -$limit);
}

function devpanelShouldIgnoreInsight(string $line): bool
{
    $lower = strtolower($line);

    $ignoredPatterns = [
        '[command_blocked]',
        'gtk-warning',
        'failed to open display',
        'failed to initialize display server connection',
        'unsupported or missing session type',
        'nautilus-application-message',
        'lbmethod_heartbeat:notice',
        'ah02282: no slotmem from mod_heartmonitor',
        'mpm_prefork:notice',
        'ah00163: apache/',
        'core:notice',
        'ah00094: command line:',
        'suexec:notice',
        'ah01232: suexec mechanism enabled',
        'caught sigterm, shutting down',
        'www.example.com:443',
        'ah01906:',
        'ah01909:',
        '(98)address already in use',
        'ah00072: make_sock',
        'no listening sockets available, shutting down',
        'ah00015: unable to open logs',
        'call to undefined function is_socket()',
        'cannot redeclare formatprojectmodifiedat()',
        'call to undefined function formatprojectmodifiedat()',
    ];

    foreach ($ignoredPatterns as $pattern)
    {
        if (str_contains($lower, $pattern))
        {
            return true;
        }
    }

    if (str_contains($lower, '/api/system_stats.php') && str_contains($lower, 'not found') && is_file(__DIR__ . '/../system_stats.php'))
    {
        return true;
    }

    if (devpanelIsResolvedPermissionNoise($lower))
    {
        return true;
    }

    return false;
}

function devpanelIsResolvedPermissionNoise(string $lower): bool
{
    if (str_contains($lower, 'devpanel_template_check') || str_contains($lower, 'devpanel_check_db'))
    {
        return true;
    }

    if (
        (str_contains($lower, 'incorrect definition of table mysql.')
            || str_contains($lower, 'please run mysql_upgrade'))
        && is_file(rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR) . '/mysql_upgrade_info')
    )
    {
        return true;
    }

    if (
        (str_contains($lower, 'event scheduler: an error occurred when initializing system tables')
            || str_contains($lower, 'using unique option prefix')
            || str_contains($lower, 'column count of mysql.proc is wrong'))
        && is_file(rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR) . '/mysql_upgrade_info')
    )
    {
        return true;
    }

    $isPermissionNoise = str_contains($lower, 'permiso denegado')
        || str_contains($lower, 'permission denied')
        || str_contains($lower, 'failed to open stream');

    if (!$isPermissionNoise)
    {
        return false;
    }

    $root = dirname(__DIR__, 2);
    $configFile = $root . '/config.php';
    $logsDir = $root . '/logs';
    $tmpDir = $root . '/tmp';
    $htdocsPath = devpanelConfig('HTDOCS_PATH');

    if (str_contains($lower, '/config.php') && is_writable($configFile))
    {
        return true;
    }

    if (str_contains($lower, '/logs/actions.log') && is_writable($logsDir))
    {
        return true;
    }

    if (str_contains($lower, '/includes/security.php') && is_writable($logsDir))
    {
        return true;
    }

    if (str_contains($lower, '/api/filemanager/zip.php') && is_writable($tmpDir))
    {
        return true;
    }

    if (str_contains($lower, '/api/filemanager/mkdir.php') && is_writable($htdocsPath))
    {
        return true;
    }

    return false;
}

function devpanelNormalizeInsightLine(string $line): string
{
    $line = preg_replace('/^\[[^\]]+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\[pid\s+\d+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\[client\s+[^\]]+\]\s*/', '', $line) ?? $line;
    $line = preg_replace('/\s+/', ' ', $line) ?? $line;

    return trim($line);
}

function devpanelInsightSuggestion(string $line, string $source): string
{
    $lower = strtolower($line);

    if (str_contains($lower, 'permission denied') || str_contains($lower, 'permiso denegado') || str_contains($lower, 'failed to open stream'))
    {
        return 'Revisa permisos del proyecto/ruta desde Ajustes o ejecuta el helper local de permisos.';
    }

    if (str_contains($lower, 'not found') || str_contains($lower, 'unable to stat'))
    {
        return 'Comprueba que la ruta exista y que el frontend no esté llamando a un endpoint antiguo.';
    }

    if (str_contains($lower, 'mysql') || str_contains($lower, 'mariadb') || $source === 'MariaDB')
    {
        return 'Revisa el estado de MariaDB, mysql_upgrade y permisos del directorio de datos.';
    }

    if (str_contains($lower, 'csrf'))
    {
        return 'Recarga la página y comprueba que la sesión no haya caducado.';
    }

    if (str_contains($lower, 'docker'))
    {
        return 'Abre el asistente Docker para verificar instalación, daemon y grupo del usuario.';
    }

    return 'Revisa el log completo y filtra por proyecto para acotar el origen.';
}

function devpanelDetectInsightProject(string $line, array $projects): ?string
{
    foreach ($projects as $project)
    {
        $needles = array_values(array_filter([
            $project['name'] ?? '',
            !empty($project['name']) ? '/' . $project['name'] . '/' : '',
            !empty($project['name']) ? rawurlencode($project['name']) : '',
            $project['path'] ?? '',
            $project['url'] ?? '',
        ]));

        foreach ($needles as $needle)
        {
            if ($needle !== '' && stripos($line, $needle) !== false)
            {
                return $project['name'] ?? null;
            }
        }
    }

    return null;
}

$hostname = gethostname() ?: '';
$mysqlDataDir = rtrim(devpanelConfig('MYSQL_DATA_DIR'), DIRECTORY_SEPARATOR);
$mariaDbLogPath = "$mysqlDataDir/$hostname.err";
$mariaDbMatches = is_file($mariaDbLogPath) ? [] : glob($mysqlDataDir . '/*.err');
if (!is_file($mariaDbLogPath) && $mariaDbMatches)
{
    $mariaDbLogPath = $mariaDbMatches[0];
}
$sources = [
    'Apache error' => devpanelConfig('APACHE_ERROR_LOG'),
    'Apache access' => devpanelConfig('APACHE_ACCESS_LOG'),
    'PHP' => devpanelConfig('PHP_ERROR_LOG'),
    'MariaDB' => $mariaDbLogPath,
    'DevPanel' => __DIR__ . '/../../logs/actions.log',
];

$patterns = [
    'danger' => '/\b(fatal|error|failed|exception|blocked|denied)\b/',
    'warning' => '/\b(warning|deprecated|permission)\b/',
];

$items = [];
$grouped = [];
$suggestions = [];
$projectSummary = [];
$project = trim((string) ($_GET['project'] ?? ''));
$projectNeedles = [];
$projects = getProjects();

if ($project !== '')
{
    foreach ($projects as $item)
    {
        if ($item['name'] === $project)
        {
            $projectNeedles = array_values(array_filter([
                $item['name'],
                '/' . $item['name'] . '/',
                rawurlencode($item['name']),
                $item['path'],
                $item['url'] ?? null,
            ]));
            break;
        }
    }
}

foreach ($sources as $label => $file)
{
    foreach (array_reverse(devpanelInsightTail($file)) as $line)
    {
        $lower = strtolower($line);
        $severity = 'info';

        if (devpanelShouldIgnoreInsight($line))
        {
            continue;
        }

        if ($project !== '')
        {
            $matchesProject = false;

            foreach ($projectNeedles as $needle)
            {
                if (stripos($line, $needle) !== false)
                {
                    $matchesProject = true;
                    break;
                }
            }

            if (!$matchesProject)
            {
                continue;
            }
        }

        if (preg_match($patterns['danger'], $lower))
        {
            $severity = 'danger';
        }

        if ($severity === 'info')
        {
            if (preg_match($patterns['warning'], $lower))
            {
                $severity = 'warning';
            }
        }

        if ($severity === 'info')
        {
            continue;
        }

        $key = $label . '|' . $severity . '|' . devpanelNormalizeInsightLine($line);
        $detectedProject = devpanelDetectInsightProject($line, $projects);
        $suggestion = devpanelInsightSuggestion($line, $label);

        if (isset($grouped[$key]))
        {
            $grouped[$key]['count']++;
            if ($detectedProject)
            {
                $projectSummary[$detectedProject][$severity] = ($projectSummary[$detectedProject][$severity] ?? 0) + 1;
            }
            continue;
        }

        if ($detectedProject)
        {
            $projectSummary[$detectedProject][$severity] = ($projectSummary[$detectedProject][$severity] ?? 0) + 1;
        }

        $suggestions[$suggestion] = ($suggestions[$suggestion] ?? 0) + 1;

        $grouped[$key] = [
            'source' => $label,
            'severity' => $severity,
            'line' => $line,
            'count' => 1,
            'project' => $detectedProject,
            'suggestion' => $suggestion,
        ];
        $items = array_values($grouped);

        if (count($items) >= 30)
        {
            break 2;
        }
    }
}

$summary = ['danger' => 0, 'warning' => 0, 'info' => 0];
$occurrences = ['danger' => 0, 'warning' => 0, 'info' => 0];

foreach ($items as $item)
{
    $severity = $item['severity'] ?? 'info';
    $summary[$severity]++;
    $occurrences[$severity] += (int) ($item['count'] ?? 1);
}

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'occurrences' => $occurrences,
    'project' => $project,
    'projects' => $projectSummary,
    'suggestions' => array_map(
        static fn ($message, $count) => ['message' => $message, 'count' => $count],
        array_keys($suggestions),
        array_values($suggestions)
    ),
    'items' => $items,
]);
