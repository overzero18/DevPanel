<?php

ini_set('session.httponly', 1);
ini_set('session.secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
ini_set('session.samesite', 'Strict');

session_start();

define('AUTH_PASSWORD_FILE', __DIR__ . '/../config.php');
define('SESSION_TOKEN_KEY', '_devpanel_auth');
define('SESSION_TIMEOUT', 3600);
define('CSRF_TOKEN_KEY', '_csrf_token');
define('LOGS_DIR', __DIR__ . '/../logs');
define('RATE_LIMIT_DIR', LOGS_DIR . '/rate_limits');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900);

require_once __DIR__ . '/helpers/filesystem.php';
require_once __DIR__ . '/helpers/config.php';

function setSecurityHeaders()
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net;');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function authenticateSession()
{
    setSecurityHeaders();

    if (!isset($_SESSION[SESSION_TOKEN_KEY]))
    {
        if (authenticateApiToken())
        {
            return;
        }

        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if (time() - $_SESSION['auth_time'] > SESSION_TIMEOUT)
    {
        unset($_SESSION[SESSION_TOKEN_KEY]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }

    $_SESSION['auth_time'] = time();
}

function authenticateApiToken(): bool
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches))
    {
        return false;
    }

    $plain = trim($matches[1]);
    $tokens = devpanelConfig('DEVPANEL_API_TOKENS', []);

    if ($plain === '' || !is_array($tokens))
    {
        return false;
    }

    foreach ($tokens as $token)
    {
        $hash = $token['hash'] ?? '';

        if ($hash && password_verify($plain, $hash))
        {
            $_SERVER['DEVPANEL_API_TOKEN_AUTH'] = '1';
            $_SERVER['DEVPANEL_API_TOKEN_NAME'] = $token['name'] ?? 'token';
            $_SESSION['user_name'] = 'api:' . ($_SERVER['DEVPANEL_API_TOKEN_NAME']);
            $_SESSION['user_role'] = $token['role'] ?? 'viewer';
            return true;
        }
    }

    logAction('api_token_failed', 'Invalid bearer token');

    return false;
}

function validateCsrfToken($token = null)
{
    if (!empty($_SERVER['DEVPANEL_API_TOKEN_AUTH']))
    {
        return true;
    }

    if (!isset($_SESSION[CSRF_TOKEN_KEY]))
    {
        return false;
    }

    $token = $token ?? $_POST['csrf_token'] ?? '';

    return hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

function generateCsrfToken()
{
    if (!isset($_SESSION[CSRF_TOKEN_KEY]))
    {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function clearCsrfToken()
{
    unset($_SESSION[CSRF_TOKEN_KEY]);
}

function checkEndpointRateLimit($action, $limit = 10, $window = 60)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeKey = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $action . '_' . $ip);
    $rateFile = RATE_LIMIT_DIR . '/' . hash('sha256', $safeKey) . '.json';
    $attempts = ['count' => 0, 'first_attempt' => time()];
    $usePersistentStorage = is_dir(RATE_LIMIT_DIR) || mkdir(RATE_LIMIT_DIR, 0755, true);

    if ($usePersistentStorage && file_exists($rateFile))
    {
        $stored = json_decode(file_get_contents($rateFile), true);

        if (is_array($stored))
        {
            $attempts = array_merge($attempts, $stored);
        }
    }
    elseif (!$usePersistentStorage)
    {
        $sessionKey = 'api_rate_' . $safeKey;

        if (isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey]))
        {
            $attempts = array_merge($attempts, $_SESSION[$sessionKey]);
        }
    }

    if (time() - $attempts['first_attempt'] > $window)
    {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    if ($attempts['count'] >= $limit)
    {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Try again later.']);
        exit;
    }

    $attempts['count']++;

    if ($usePersistentStorage && file_put_contents($rateFile, json_encode($attempts), LOCK_EX) !== false)
    {
        return;
    }

    $_SESSION['api_rate_' . $safeKey] = $attempts;
}

function recordLoginAttempt($success = false)
{
    if ($success && is_dir(RATE_LIMIT_DIR))
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $safeKey = preg_replace('/[^a-zA-Z0-9_.-]/', '_', 'login_' . $ip);
        $rateFile = RATE_LIMIT_DIR . '/' . hash('sha256', $safeKey) . '.json';
        if (file_exists($rateFile))
        {
            unlink($rateFile);
        }

        unset($_SESSION['api_rate_' . $safeKey]);
    }
}

function generateAuthToken()
{
    return bin2hex(random_bytes(32));
}

function devpanelTwoFactorEnabled(): bool
{
    return (bool) devpanelConfig('DEVPANEL_2FA_ENABLED', false)
        && devpanelConfig('DEVPANEL_2FA_SECRET', '') !== '';
}

function devpanelBase32Decode(string $value): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $value = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $value) ?? '');
    $bits = '';
    $output = '';

    foreach (str_split($value) as $char)
    {
        $position = strpos($alphabet, $char);

        if ($position === false)
        {
            continue;
        }

        $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
    }

    foreach (str_split($bits, 8) as $byte)
    {
        if (strlen($byte) === 8)
        {
            $output .= chr(bindec($byte));
        }
    }

    return $output;
}

function devpanelTotpCode(string $secret, ?int $timestamp = null): string
{
    $timestamp = $timestamp ?? time();
    $counter = intdiv($timestamp, 30);
    $binaryCounter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $binaryCounter, devpanelBase32Decode($secret), true);
    $offset = ord($hash[19]) & 0x0f;
    $value = (
        ((ord($hash[$offset]) & 0x7f) << 24)
        | ((ord($hash[$offset + 1]) & 0xff) << 16)
        | ((ord($hash[$offset + 2]) & 0xff) << 8)
        | (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;

    return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
}

function devpanelVerifyTwoFactorCode(string $code): bool
{
    $secret = devpanelConfig('DEVPANEL_2FA_SECRET', '');
    $code = preg_replace('/\D/', '', $code) ?? '';

    if ($secret === '' || strlen($code) !== 6)
    {
        return false;
    }

    for ($offset = -1; $offset <= 1; $offset++)
    {
        if (hash_equals(devpanelTotpCode($secret, time() + ($offset * 30)), $code))
        {
            return true;
        }
    }

    return false;
}

function finishLogin(string $username, string $role): bool
{
    $_SESSION[SESSION_TOKEN_KEY] = generateAuthToken();
    $_SESSION['auth_time'] = time();
    $_SESSION['user_name'] = $username;
    $_SESSION['user_role'] = $role;
    generateCsrfToken();
    recordLoginAttempt(true);
    logAction('login_success', 'User logged in');

    return true;
}

function passwordRequiresTwoFactor(string $code): bool
{
    if (!devpanelTwoFactorEnabled())
    {
        return false;
    }

    if ($code === '')
    {
        $GLOBALS['devpanel_login_requires_2fa'] = true;
        return true;
    }

    if (!devpanelVerifyTwoFactorCode($code))
    {
        recordLoginAttempt(false);
        $GLOBALS['devpanel_login_invalid_2fa'] = true;
        logAction('login_2fa_failed', 'Invalid two-factor code');
        return true;
    }

    return false;
}

function login($password)
{
    $username = trim((string) ($_POST['username'] ?? 'admin'));
    $twoFactorCode = trim((string) ($_POST['two_factor_code'] ?? ''));
    $user = getConfigUser($username);

    if ($user)
    {
        $configPassword = $user['password'] ?? '';

        if (!$configPassword || !password_verify($password, $configPassword))
        {
            recordLoginAttempt(false);
            logAction('login_failed', 'Invalid credentials');
            return false;
        }

        if (passwordRequiresTwoFactor($twoFactorCode))
        {
            return false;
        }

        return finishLogin($username, $user['role'] ?? 'admin');
    }

    $configPassword = getConfigPassword();

    if (!$configPassword || !password_verify($password, $configPassword))
    {
        recordLoginAttempt(false);
        logAction('login_failed', 'Invalid credentials');
        return false;
    }

    if (passwordRequiresTwoFactor($twoFactorCode))
    {
        return false;
    }

    return finishLogin('admin', 'admin');
}

function logout()
{
    logAction('logout', 'User logged out');
    unset($_SESSION[SESSION_TOKEN_KEY]);
    clearCsrfToken();
    session_destroy();
}

function getConfigPassword()
{
    if (file_exists(AUTH_PASSWORD_FILE))
    {
        $config = require AUTH_PASSWORD_FILE;
        return $config['DEVPANEL_PASSWORD'] ?? null;
    }
    return null;
}

function getConfigUser($username)
{
    if (!file_exists(AUTH_PASSWORD_FILE))
    {
        return null;
    }

    $config = require AUTH_PASSWORD_FILE;
    $users = $config['DEVPANEL_USERS'] ?? [];

    if (!is_array($users) || !isset($users[$username]) || !is_array($users[$username]))
    {
        return null;
    }

    return $users[$username];
}

function getCurrentUserName()
{
    return $_SESSION['user_name'] ?? 'admin';
}

function getCurrentUserRole()
{
    return $_SESSION['user_role'] ?? 'admin';
}

function currentUserCan($permission)
{
    if (!file_exists(AUTH_PASSWORD_FILE))
    {
        return true;
    }

    $config = require AUTH_PASSWORD_FILE;
    $roles = $config['DEVPANEL_ROLES'] ?? ['admin' => ['*']];
    $permissions = $roles[getCurrentUserRole()] ?? ['*'];

    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function requirePermission($permission)
{
    if (currentUserCan($permission))
    {
        return;
    }

    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para esta acción']);
    exit;
}

function logAction($action, $details = '')
{
    if (!is_dir(LOGS_DIR) && !mkdir(LOGS_DIR, 0755, true))
    {
        return;
    }

    $logFile = LOGS_DIR . '/actions.log';

    if ((file_exists($logFile) && !is_writable($logFile)) || (!file_exists($logFile) && !is_writable(LOGS_DIR)))
    {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION[SESSION_TOKEN_KEY]) || !empty($_SERVER['DEVPANEL_API_TOKEN_AUTH'])
        ? getCurrentUserName() . ':' . getCurrentUserRole()
        : 'anonymous';

    $logEntry = "[$timestamp] [$ip] [$user] [$action] $details\n";

    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function runControlledCommand($command)
{
    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ],
        $pipes
    );

    if (!is_resource($process))
    {
        return ['exit_code' => 1, 'output' => 'No se pudo iniciar el comando'];
    }

    $output = stream_get_contents($pipes[1]);
    $errorOutput = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exit_code' => proc_close($process),
        'output' => $output . $errorOutput
    ];
}

function getAllowedTerminalCommands()
{
    require_once __DIR__ . '/helpers/config.php';

    $phpBinary = devpanelConfig('PHP_BINARY', '/opt/lampp/bin/php');
    $projectPath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $safeGitDirectory = escapeshellarg($projectPath);

    return [
        'pwd' => 'pwd',
        'ls' => 'ls -la',
        'ls -la' => 'ls -la',
        'git status' => 'git -c safe.directory=' . $safeGitDirectory . ' status --short',
        'git branch' => 'git -c safe.directory=' . $safeGitDirectory . ' branch',
        'php -v' => $phpBinary . ' -v',
        'composer --version' => 'composer --version',
        'composer install' => 'composer install --no-interaction --prefer-dist',
        'npm --version' => 'npm --version',
        'npm install' => 'npm install',
        'npm run build' => 'npm run build',
        'npm test' => 'npm test',
    ];
}

function getSafeTerminalCommand($command)
{
    $command = trim($command);
    $allowedCommands = getAllowedTerminalCommands();

    if (!array_key_exists($command, $allowedCommands))
    {
        logAction('command_blocked', "Blocked: $command");
        return false;
    }

    if (preg_match('/[;&|`$<>\\n\\r]/', $command))
    {
        logAction('command_blocked', "Shell operators detected: $command");
        return false;
    }

    return $allowedCommands[$command];
}

function validateCommand($command)
{
    return getSafeTerminalCommand($command) !== false;
}

function validatePath($path)
{
    $normalized = normalizarRuta($path);

    if (!esRutaPermitida($normalized))
    {
        logAction('path_blocked', "Blocked: $path");
        return false;
    }

    return true;
}

function validateService($service)
{
    $allowedServices = ['apache', 'mysql'];
    return in_array($service, $allowedServices);
}

function validateAction($action)
{
    $allowedActions = ['start', 'stop', 'restart'];
    return in_array($action, $allowedActions);
}

function sanitizeFtpCredential($credential)
{
    return escapeshellarg($credential);
}

function escapeForShell($value)
{
    return escapeshellarg($value);
}

function escapeHtml($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
