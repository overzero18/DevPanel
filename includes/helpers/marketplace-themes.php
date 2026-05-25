<?php

function devpanelCommunityPlugins(): array
{
    return [
        [
            'id' => 'devpanel-plugin-slack',
            'name' => 'Slack Notifications',
            'description' => 'Send deployment and system alerts to Slack channels',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-slack',
            'icon' => 'chat-fill',
            'tags' => ['notifications', 'integrations', 'slack'],
            'downloads' => 342,
            'rating' => 4.8,
        ],
        [
            'id' => 'devpanel-plugin-monitoring',
            'name' => 'Advanced Monitoring',
            'description' => 'Extended monitoring with Prometheus and Grafana integration',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-monitoring',
            'icon' => 'graph-up',
            'tags' => ['monitoring', 'metrics', 'prometheus'],
            'downloads' => 218,
            'rating' => 4.6,
        ],
        [
            'id' => 'devpanel-plugin-ssl',
            'name' => 'SSL Certificate Manager',
            'description' => 'Automated Let\'s Encrypt certificate management and renewals',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-ssl',
            'icon' => 'shield-lock-fill',
            'tags' => ['security', 'ssl', 'certificates'],
            'downloads' => 567,
            'rating' => 4.9,
        ],
        [
            'id' => 'devpanel-plugin-cdn',
            'name' => 'CDN Manager',
            'description' => 'CloudFlare and Bunny CDN integration for static assets',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-cdn',
            'icon' => 'cloud-fill',
            'tags' => ['cdn', 'performance', 'cloudflare'],
            'downloads' => 195,
            'rating' => 4.7,
        ],
        [
            'id' => 'devpanel-plugin-backup-s3',
            'name' => 'S3 Backup Storage',
            'description' => 'Automated backup to AWS S3, DigitalOcean Spaces, and Minio',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-backup-s3',
            'icon' => 'cloud-arrow-up-fill',
            'tags' => ['backups', 'cloud', 's3'],
            'downloads' => 423,
            'rating' => 4.8,
        ],
        [
            'id' => 'devpanel-plugin-redis',
            'name' => 'Redis Manager',
            'description' => 'Redis cache management with monitoring and clear utilities',
            'author' => 'DevPanel Community',
            'version' => '1.0.0',
            'repository' => 'https://github.com/devpanel-io/devpanel-plugin-redis',
            'icon' => 'lightning-charge-fill',
            'tags' => ['cache', 'redis', 'performance'],
            'downloads' => 156,
            'rating' => 4.7,
        ],
    ];
}

function devpanelThemeMarketplace(): array
{
    return [
        [
            'id' => 'theme-dracula',
            'name' => 'Dracula',
            'description' => 'Dark theme inspired by Dracula color scheme',
            'author' => 'DevPanel',
            'colors' => ['#282a36', '#f8f8f2', '#ff79c6'],
        ],
        [
            'id' => 'theme-solarized',
            'name' => 'Solarized',
            'description' => 'Precision colors for machines and people',
            'author' => 'DevPanel',
            'colors' => ['#fdf6e3', '#002b36', '#268bd2'],
        ],
        [
            'id' => 'theme-nord',
            'name' => 'Nord',
            'description' => 'Arctic, north-bluish color palette',
            'author' => 'DevPanel',
            'colors' => ['#2e3440', '#d8dee9', '#88c0d0'],
        ],
        [
            'id' => 'theme-gruvbox',
            'name' => 'Gruvbox',
            'description' => 'Retro groove color scheme for terminals',
            'author' => 'DevPanel',
            'colors' => ['#282828', '#ebdbb2', '#d65d0e'],
        ],
    ];
}
