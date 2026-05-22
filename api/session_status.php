<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
setSecurityHeaders();

$authenticated = isset($_SESSION[SESSION_TOKEN_KEY])
    && isset($_SESSION['auth_time'])
    && time() - $_SESSION['auth_time'] <= SESSION_TIMEOUT;

echo json_encode(['authenticated' => $authenticated]);
