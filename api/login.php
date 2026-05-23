<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

checkEndpointRateLimit('login', MAX_LOGIN_ATTEMPTS, LOGIN_ATTEMPT_WINDOW);

$password = $_POST['password'] ?? '';

if (!$password)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password required']);
    exit;
}

if (login($password))
{
    echo json_encode(['success' => true, 'message' => 'Logged in successfully']);
}
else
{
    http_response_code(401);
    if (!empty($GLOBALS['devpanel_login_requires_2fa']))
    {
        echo json_encode([
            'success' => false,
            'two_factor_required' => true,
            'message' => 'Código 2FA requerido',
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => !empty($GLOBALS['devpanel_login_invalid_2fa']) ? 'Código 2FA inválido' : 'Invalid password',
    ]);
}
