<?php

ini_set('session.httponly', 1);
ini_set('session.secure', 0);
ini_set('session.samesite', 'Strict');

session_start();

define('AUTH_PASSWORD_FILE', __DIR__ . '/../config.php');
define('SESSION_TOKEN_KEY', '_devpanel_auth');
define('SESSION_TIMEOUT', 3600);
define('CSRF_TOKEN_KEY', '_csrf_token');
define('LOGS_DIR', __DIR__ . '/../logs');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900);

require_once __DIR__ . '/helpers/filesystem.php';

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

function checkRateLimit()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . $ip;

    if (!isset($_SESSION[$key]))
    {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $attempts = &$_SESSION[$key];

    if (time() - $attempts['first_attempt'] > LOGIN_ATTEMPT_WINDOW)
    {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS)
    {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many login attempts. Try again later.']);
        exit;
    }
}

function recordLoginAttempt($success = false)
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . $ip;

    if (!isset($_SESSION[$key]))
    {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    if (!$success)
    {
        $_SESSION[$key]['count']++;
    }
    else
    {
        unset($_SESSION[$key]);
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
    if (!is_dir(LOGS_DIR))
    {
        mkdir(LOGS_DIR, 0755, true);
    }

    $logFile = LOGS_DIR . '/actions.log';
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION[SESSION_TOKEN_KEY]) ? 'authenticated' : 'anonymous';

    $logEntry = "[$timestamp] [$ip] [$user] [$action] $details\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function validateCommand($command)
{
    $command = trim($command);

    $allowedCommands = [
        'ls', 'cd', 'pwd', 'cat', 'grep', 'find',
        'git', 'svn',
        'npm', 'composer', 'php', 'python'
    ];

    $baseCommand = explode(' ', $command)[0];
    $baseCommand = trim($baseCommand);

    if (!in_array($baseCommand, $allowedCommands))
    {
        logAction('command_blocked', "Blocked: $command");
        return false;
    }

    return true;
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
