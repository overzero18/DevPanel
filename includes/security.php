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

function validateCsrfToken($token = null)
{
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
    if (!is_dir(RATE_LIMIT_DIR) && !mkdir(RATE_LIMIT_DIR, 0755, true))
    {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Rate limit storage unavailable']);
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeKey = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $action . '_' . $ip);
    $rateFile = RATE_LIMIT_DIR . '/' . hash('sha256', $safeKey) . '.json';
    $attempts = ['count' => 0, 'first_attempt' => time()];

    if (file_exists($rateFile))
    {
        $stored = json_decode(file_get_contents($rateFile), true);

        if (is_array($stored))
        {
            $attempts = array_merge($attempts, $stored);
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

    if (file_put_contents($rateFile, json_encode($attempts), LOCK_EX) === false)
    {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Rate limit storage unavailable']);
        exit;
    }
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
    }
}

function generateAuthToken()
{
    return bin2hex(random_bytes(32));
}

function login($password)
{
    $configPassword = getConfigPassword();

    if (!$configPassword || !password_verify($password, $configPassword))
    {
        recordLoginAttempt(false);
        logAction('login_failed', 'Invalid credentials');
        return false;
    }

    $_SESSION[SESSION_TOKEN_KEY] = generateAuthToken();
    $_SESSION['auth_time'] = time();
    generateCsrfToken();
    recordLoginAttempt(true);
    logAction('login_success', 'User logged in');
    return true;
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

function logAction($action, $details = '')
{
    if (!is_dir(LOGS_DIR) && !mkdir(LOGS_DIR, 0755, true))
    {
        return;
    }

    $logFile = LOGS_DIR . '/actions.log';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION[SESSION_TOKEN_KEY]) ? 'authenticated' : 'anonymous';

    $logEntry = "[$timestamp] [$ip] [$user] [$action] $details\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
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
    return [
        'pwd' => 'pwd',
        'ls' => 'ls -la',
        'ls -la' => 'ls -la',
        'git status' => 'git status --short',
        'git branch' => 'git branch',
        'php -v' => '/opt/lampp/bin/php -v',
        'composer --version' => 'composer --version',
        'npm --version' => 'npm --version',
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
