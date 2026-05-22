<?php

require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$csrfToken = generateCsrfToken();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (!validateCsrfToken($_POST['csrf_token'] ?? ''))
    {
        $error = 'Token CSRF inválido';
    }
    else
    {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $configPassword = getConfigPassword();

        if (!$currentPassword)
        {
            $error = 'Debes ingresar tu contraseña actual';
        }
        elseif (!password_verify($currentPassword, $configPassword))
        {
            $error = 'Contraseña actual incorrecta';
            logAction('change_password_failed', 'Invalid current password');
        }
        elseif (!$newPassword)
        {
            $error = 'Debes ingresar una nueva contraseña';
        }
        elseif (strlen($newPassword) < 12)
        {
            $error = 'La nueva contraseña debe tener al menos 12 caracteres';
        }
        elseif ($newPassword !== $confirmPassword)
        {
            $error = 'Las nuevas contraseñas no coinciden';
        }
        elseif ($currentPassword === $newPassword)
        {
            $error = 'La nueva contraseña debe ser diferente a la actual';
        }
        else
        {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
            $existingConfig = file_exists(AUTH_PASSWORD_FILE)
                ? require AUTH_PASSWORD_FILE
                : [];

            if (devpanelWriteConfig(AUTH_PASSWORD_FILE, $hash, $existingConfig))
            {
                $success = 'Contraseña cambiada correctamente. Por favor, inicia sesión nuevamente.';
                logAction('change_password_success', 'Password changed successfully');
                logout();
            }
            else
            {
                $error = 'Error al guardar la contraseña. Verifica permisos de carpeta.';
                logAction('change_password_error', 'Failed to save new password');
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Cambiar Contraseña - DevPanel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/devpanel/assets/css/style.css">
    <?php
    @include_once __DIR__ . '/includes/helpers/theme.php';
    if (function_exists('devpanel_print_theme_link')) {
        devpanel_print_theme_link();
    }
    ?>
</head>
<body>

<?php include 'layout/topbar.php'; ?>
<main class="password-page">
    <div class="password-card">
        <h1>
            <i class="bi bi-key"></i>
            Cambiar Contraseña
        </h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="text-center">
                <p>Redirigiendo a login en 3 segundos...</p>
                <script>
                    setTimeout(() => {
                        window.location.href = '/devpanel/login.html';
                    }, 3000);
                </script>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="current_password">Contraseña Actual</label>
                    <input type="password" class="form-control" id="current_password"
                           name="current_password" required placeholder="Ingresa tu contraseña actual">
                    <small class="form-text text-muted">Por seguridad, confirmaremos tu contraseña actual</small>
                </div>

                <hr>

                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" class="form-control" id="new_password"
                           name="new_password" required minlength="12"
                           placeholder="Mínimo 12 caracteres">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" class="form-control" id="confirm_password"
                           name="confirm_password" required minlength="12"
                           placeholder="Repite la nueva contraseña">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Cambiar Contraseña
                    </button>
                    <a href="/devpanel/" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </form>

            <div class="password-requirements">
                <strong>Requisitos:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Mínimo 12 caracteres</li>
                    <li>Debe ser diferente a la contraseña actual</li>
                    <li>Se requiere confirmar la contraseña actual por seguridad</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/devpanel/assets/js/app.js"></script>
</body>
</html>
