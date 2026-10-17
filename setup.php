<?php

define('AUTH_PASSWORD_FILE', __DIR__ . '/config.php');

require_once __DIR__ . '/includes/helpers/config.php';

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$setupComplete = false;
$error = '';
$success = '';

if (file_exists(AUTH_PASSWORD_FILE))
{
    $config = require AUTH_PASSWORD_FILE;
    if (!empty($config['DEVPANEL_PASSWORD']))
    {
        $setupComplete = true;
    }
}

if ($setupComplete)
{
    http_response_code(404);
    echo 'Not found';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!isset($_SESSION['setup_csrf_token']) || !hash_equals($_SESSION['setup_csrf_token'], $csrfToken))
    {
        $error = 'Solicitud de configuración no válida. Recarga la página e inténtalo de nuevo.';
    }
    elseif (empty($password))
    {
        $error = 'La contraseña es obligatoria';
    }
    elseif (strlen($password) < 12)
    {
        $error = 'La contraseña debe tener al menos 12 caracteres';
    }
    elseif ($password !== $confirmPassword)
    {
        $error = 'Las contraseñas no coinciden';
    }
    else
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $existingConfig = file_exists(AUTH_PASSWORD_FILE)
            ? require AUTH_PASSWORD_FILE
            : [];

        if (devpanelWriteConfig(AUTH_PASSWORD_FILE, $hash, $existingConfig))
        {
            unset($_SESSION['setup_csrf_token']);
            header('Location: /devpanel/login.html?setup=complete', true, 303);
            exit;
        }
        else
        {
            $error = 'Error al guardar la contraseña. Verifica permisos de carpeta.';
        }
    }
}

if (!isset($_SESSION['setup_csrf_token']))
{
    $_SESSION['setup_csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup DevPanel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .setup-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .setup-card h1 {
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }
        .setup-card p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: #667eea;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .alert {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .redirect-message {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1>🔐 DevPanel Setup</h1>
        <p>Configura tu contraseña de acceso</p>

        <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
        <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['setup_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required minlength="12" placeholder="Mínimo 12 caracteres">
                    <small class="form-text text-muted">Usa una contraseña fuerte</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" class="form-control" id="confirm_password"
                           name="confirm_password" required minlength="12"
                           placeholder="Repite la contraseña">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-lock"></i> Configurar Contraseña
                </button>
            </form>

            <hr style="margin: 30px 0;">
            <small class="text-muted d-block">
                <strong>ℹ️ Nota:</strong> Esta página solo aparece una vez. Después podrás cambiar
                la contraseña desde el panel de administración.
            </small>
    </div>
</body>
</html>
