<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/time.php';

function devpanelDemoModeEnabled(): bool
{
    return (bool) devpanelConfig('DEVPANEL_DEMO_MODE', false);
}

function devpanelDemoProjects(): array
{
    $now = time();
    $label = static fn (int $timestamp): string => date('d/m/Y H:i', $timestamp);

    return [
        [
            'name' => 'demo-laravel-shop',
            'path' => '/demo/projects/demo-laravel-shop',
            'url' => 'http://localhost/demo-laravel-shop',
            'type' => 'Laravel',
            'size' => 42.8,
            'modified_at' => $now - 1800,
            'modified_label' => $label($now - 1800),
            'writable' => true,
            'internal' => false,
            'demo' => true,
            'git' => ['enabled' => true, 'branch' => 'main', 'dirty' => true, 'changes' => 3],
        ],
        [
            'name' => 'demo-wordpress-blog',
            'path' => '/demo/projects/demo-wordpress-blog',
            'url' => 'http://localhost/demo-wordpress-blog',
            'type' => 'WordPress',
            'size' => 96.4,
            'modified_at' => $now - 7200,
            'modified_label' => $label($now - 7200),
            'writable' => true,
            'internal' => false,
            'demo' => true,
            'git' => ['enabled' => false, 'branch' => null, 'dirty' => false, 'changes' => 0],
        ],
        [
            'name' => 'demo-static-portfolio',
            'path' => '/demo/projects/demo-static-portfolio',
            'url' => 'http://localhost/demo-static-portfolio',
            'type' => 'Static',
            'size' => 8.6,
            'modified_at' => $now - 14400,
            'modified_label' => $label($now - 14400),
            'writable' => true,
            'internal' => false,
            'demo' => true,
            'git' => ['enabled' => true, 'branch' => 'preview', 'dirty' => false, 'changes' => 0],
        ],
    ];
}
