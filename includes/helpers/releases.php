<?php

require_once __DIR__ . '/config.php';

function devpanelGitHubReleases(): array
{
    $token = devpanelConfig('DEVPANEL_GITHUB_TOKEN');
    $repo = devpanelConfig('DEVPANEL_GITHUB_REPO');

    if (!$token || !$repo || strpos($repo, '/') === false)
    {
        return [];
    }

    $url = 'https://api.github.com/repos/' . $repo . '/releases?per_page=10';
    $ctx = stream_context_create([
        'http' => [
            'header' => [
                'Accept: application/vnd.github.v3+json',
                'Authorization: token ' . $token,
                'User-Agent: DevPanel',
            ],
            'timeout' => 5,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);

    if ($response === false)
    {
        return [];
    }

    $releases = json_decode($response, true);

    return is_array($releases) ? array_map(function ($release)
    {
        return [
            'name' => $release['name'] ?? $release['tag_name'] ?? 'Unknown',
            'tag' => $release['tag_name'] ?? 'Unknown',
            'version' => str_replace('v', '', $release['tag_name'] ?? ''),
            'created_at' => $release['created_at'] ?? '',
            'published_at' => $release['published_at'] ?? '',
            'body' => $release['body'] ?? '',
            'prerelease' => $release['prerelease'] ?? false,
            'draft' => $release['draft'] ?? false,
            'download_url' => devpanelGitHubReleaseZip($release['tag_name'] ?? ''),
        ];
    }, $releases) : [];
}

function devpanelGitHubReleaseZip(string $tag): string
{
    $repo = devpanelConfig('DEVPANEL_GITHUB_REPO');

    if (!$repo)
    {
        return '';
    }

    return 'https://github.com/' . $repo . '/archive/refs/tags/' . $tag . '.zip';
}

function devpanelLocalVersion(): string
{
    $versionFile = dirname(__DIR__) . '/VERSION';

    if (is_file($versionFile))
    {
        return trim(file_get_contents($versionFile));
    }

    return '1.0.0';
}
