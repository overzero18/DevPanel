<?php

require_once __DIR__ . '/config.php';

function devpanelDomainsFile(): string
{
    return dirname(__DIR__, 2) . '/logs/local_domains.json';
}

function devpanelDomainsSnippetDir(): string
{
    return dirname(__DIR__, 2) . '/tmp/vhosts';
}

function devpanelLoadDomains(): array
{
    $file = devpanelDomainsFile();

    if (!file_exists($file))
    {
        return [];
    }

    $items = json_decode((string) file_get_contents($file), true);

    return is_array($items) ? $items : [];
}

function devpanelSaveDomains(array $items): bool
{
    $file = devpanelDomainsFile();

    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true))
    {
        return false;
    }

    return file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function devpanelValidateDomain(string $domain): bool
{
    return preg_match('/^[a-z0-9][a-z0-9.-]{1,120}\.test$/', $domain) === 1
        && !str_contains($domain, '..')
        && !str_starts_with($domain, '-')
        && !str_contains($domain, '/');
}

function devpanelDomainSnippet(string $domain, string $path): string
{
    $logsPath = dirname(__DIR__, 2) . '/logs';

    return <<<CONF
<VirtualHost *:80>
    ServerName $domain
    DocumentRoot "$path"

    <Directory "$path">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>

    ErrorLog "$logsPath/$domain-error.log"
    CustomLog "$logsPath/$domain-access.log" common
</VirtualHost>
CONF;
}

function devpanelUpsertDomain(string $domain, string $path): ?array
{
    if (!devpanelValidateDomain($domain) || !is_dir($path))
    {
        return null;
    }

    $realPath = realpath($path);

    if (!$realPath)
    {
        return null;
    }

    $snippetDir = devpanelDomainsSnippetDir();

    if (!is_dir($snippetDir) && !mkdir($snippetDir, 0755, true))
    {
        return null;
    }

    $snippetPath = $snippetDir . '/' . preg_replace('/[^a-z0-9.-]/', '_', $domain) . '.conf';
    $snippet = devpanelDomainSnippet($domain, $realPath);

    if (file_put_contents($snippetPath, $snippet, LOCK_EX) === false)
    {
        return null;
    }

    $item = [
        'domain' => $domain,
        'path' => $realPath,
        'snippet' => $snippetPath,
        'url' => 'http://' . $domain,
        'created_at' => date('Y-m-d H:i:s'),
        'commands' => [
            'hosts' => "echo '127.0.0.1 $domain' | sudo tee -a /etc/hosts",
            'vhost' => "sudo cp '$snippetPath' '/opt/lampp/etc/extra/devpanel-$domain.conf'",
            'include' => "echo 'Include etc/extra/devpanel-$domain.conf' | sudo tee -a /opt/lampp/etc/httpd.conf",
            'restart' => 'sudo /opt/lampp/lampp restart',
        ],
    ];

    $items = array_filter(devpanelLoadDomains(), static fn ($existing) => ($existing['domain'] ?? '') !== $domain);
    $items[] = $item;

    return devpanelSaveDomains($items) ? $item : null;
}

function devpanelFindDomain(string $domain): ?array
{
    foreach (devpanelLoadDomains() as $item)
    {
        if (($item['domain'] ?? '') === $domain)
        {
            return $item;
        }
    }

    return null;
}

function devpanelApplyDomain(string $domain): array
{
    $item = devpanelFindDomain($domain);

    if (!$item || !devpanelValidateDomain($domain))
    {
        return ['success' => false, 'message' => 'Dominio no encontrado'];
    }

    $snippetPath = $item['snippet'] ?? '';
    $targetConf = "/opt/lampp/etc/extra/devpanel-$domain.conf";
    $includeLine = "Include etc/extra/devpanel-$domain.conf";
    $commands = [
        ['sudo', '-n', 'sh', '-c', "grep -q '127.0.0.1 $domain' /etc/hosts || echo '127.0.0.1 $domain' >> /etc/hosts"],
        ['sudo', '-n', 'cp', $snippetPath, $targetConf],
        ['sudo', '-n', 'sh', '-c', "grep -q 'devpanel-$domain.conf' /opt/lampp/etc/httpd.conf || echo '$includeLine' >> /opt/lampp/etc/httpd.conf"],
        ['sudo', '-n', '/opt/lampp/lampp', 'restart'],
    ];

    foreach ($commands as $command)
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process))
        {
            return ['success' => false, 'message' => 'No se pudo ejecutar sudo', 'domain' => $item];
        }

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0)
        {
            return [
                'success' => false,
                'message' => 'Sudo no interactivo no está permitido. Usa los comandos manuales.',
                'output' => trim($output),
                'domain' => $item,
            ];
        }
    }

    return ['success' => true, 'message' => 'Dominio local aplicado', 'domain' => $item];
}
