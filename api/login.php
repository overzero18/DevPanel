<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

checkRateLimit();

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
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
}
