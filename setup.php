<?php

define('AUTH_PASSWORD_FILE', __DIR__ . '/config.php');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupComplete)
{
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password))
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

        $content = <<<'PHP'
<?php

return [
    'DEVPANEL_PASSWORD' => '$hash',
];
PHP;

        $content = str_replace('$hash', $hash, $content);

        if (file_put_contents(AUTH_PASSWORD_FILE, $content))
        {
            $success = 'Contraseña configurada correctamente. Redirigiendo...';
            $setupComplete = true;
        }
        else
        {
            $error = 'Error al guardar la contraseña. Verifica permisos de carpeta.';
        }
    }
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

        <?php if ($setupComplete): ?>
            <div class="alert alert-success" role="alert">
                <strong>¡Listo!</strong> Contraseña configurada correctamente.
            </div>
            <div class="redirect-message">
                <p>Redirigiendo a login en 3 segundos...</p>
                <script>
                    setTimeout(() => {
                        window.location.href = '/devpanel/login.html';
                    }, 3000);
                </script>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = '/devpanel/login.html';
                    }, 2000);
                </script>
            <?php endif; ?>

            <form method="POST">
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
        <?php endif; ?>
    </div>
</body>
</html>
