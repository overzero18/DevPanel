<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? ''))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$theme = $_POST['theme'] ?? 'dark';

// List of allowed themes
$allowedThemes = ['dark', 'cyber', 'ubuntu', 'glass'];

// Validate theme
if (!in_array($theme, $allowedThemes))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit;
}

// Set theme in session
$_SESSION['theme'] = $theme;

logAction('set_theme', "Theme changed to: $theme");

echo json_encode([
    'success' => true,
    'message' => 'Theme changed successfully',
    'theme' => $theme
]);
