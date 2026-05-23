<?php

require_once __DIR__ . '/includes/helpers/doctor.php';

$doctor = devpanelDoctorChecks();
$hasConfig = is_file(__DIR__ . '/config.php');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - DevPanel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/devpanel/assets/css/style.css">
</head>
<body>
    <main class="content p-4">
        <div class="section-title-row">
            <div>
                <h1 class="mb-1 fw-bold">Instalación guiada</h1>
                <p class="text-secondary mb-0">Checklist local para dejar DevPanel listo.</p>
            </div>

            <a class="btn btn-devpanel" href="<?php echo $hasConfig ? '/devpanel/login.html' : '/devpanel/setup.php'; ?>">
                <i class="bi bi-arrow-right-circle"></i>
                <?php echo $hasConfig ? 'Ir al login' : 'Crear configuración'; ?>
            </a>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-12 col-xl-5">
                <div class="dashboard-card doctor-card">
                    <h4 class="mb-3">Pasos</h4>
                    <div class="doctor-command-list">
                        <div class="doctor-command">
                            <span>1. Configuración</span>
                            <code><?php echo $hasConfig ? 'config.php presente' : 'Abrir /devpanel/setup.php'; ?></code>
                        </div>
                        <div class="doctor-command">
                            <span>2. Permisos locales</span>
                            <code>./scripts/fix-local-permissions.sh</code>
                        </div>
                        <div class="doctor-command">
                            <span>3. XAMPP</span>
                            <code>sudo /opt/lampp/lampp start</code>
                        </div>
                        <div class="doctor-command">
                            <span>4. Smoke test</span>
                            <code>DEVPANEL_TEST_PASSWORD=... ./scripts/devpanel-api-smoke.sh</code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="dashboard-card doctor-card">
                    <h4 class="mb-3">Checks</h4>
                    <div class="doctor-check-list">
                        <?php foreach ($doctor['items'] as $item): ?>
                            <div class="doctor-check is-<?php echo htmlspecialchars($item['severity'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi <?php echo $item['severity'] === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars($item['detail'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
