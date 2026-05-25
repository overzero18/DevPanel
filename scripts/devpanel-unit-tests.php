<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/helpers/config.php';
require_once $root . '/includes/helpers/plugins.php';
require_once $root . '/includes/helpers/state.php';
require_once $root . '/includes/helpers/ci.php';
require_once $root . '/includes/helpers/users.php';

$failures = 0;

function devpanel_test_assert(bool $condition, string $message): void
{
    global $failures;

    if (!$condition)
    {
        $failures++;
        fwrite(STDERR, "FAIL: {$message}\n");
        return;
    }

    fwrite(STDOUT, "OK: {$message}\n");
}

devpanel_test_assert(devpanelConfig('BASE_URL') === '/devpanel', 'default BASE_URL');
devpanel_test_assert(isset(devpanelDefaultRuntimeConfig()['DEVPANEL_PLUGINS_ENABLED']), 'default plugins key exists');
devpanel_test_assert(count(devpanelPluginCatalog()) >= 5, 'plugin catalog has core modules');
devpanel_test_assert(in_array('scripts/devpanel-unit-tests.php', array_column(devpanelCiLocalStatus()['checks'], 'name'), true), 'CI local status knows unit tests');

$db = devpanelStateDb();
devpanel_test_assert($db !== null, 'SQLite state database opens');

if ($db)
{
    $plain = 'dp_' . bin2hex(random_bytes(24));
    $id = hash('sha256', $plain);
    $token = [
        'id' => $id,
        'name' => 'unit-token',
        'prefix' => substr($plain, 0, 10),
        'hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]),
        'role' => 'viewer',
        'created_at' => date('Y-m-d H:i:s'),
        'last_used_at' => null,
        'expires_at' => date('Y-m-d H:i:s', time() + 86400),
        'rotated_at' => null,
    ];

    devpanel_test_assert(devpanelStateUpsertToken($token), 'state token upsert');
    devpanel_test_assert((devpanelStateFindToken($id)['name'] ?? '') === 'unit-token', 'state token find');
    devpanel_test_assert(devpanelStateTouchToken($id), 'state token touch');
    devpanel_test_assert((devpanelStateFindToken($id)['last_used_at'] ?? '') !== '', 'state token last_used_at');
    devpanel_test_assert(devpanelStateDeleteToken($id), 'state token delete');
    devpanel_test_assert(devpanelStateFindToken($id) === null, 'state token removed');
}

exit($failures > 0 ? 1 : 0);
