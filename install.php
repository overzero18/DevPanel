<?php

require_once __DIR__ . '/includes/helpers/doctor.php';

$doctor = devpanelDoctorChecks();
$hasConfig = is_file(__DIR__ . '/config.php');
$blockingItems = array_values(array_filter($doctor['items'], static fn ($item) => in_array($item['severity'], ['warning', 'danger'], true)));
$optionalItems = array_values(array_filter($doctor['items'], static fn ($item) => $item['severity'] === 'info'));
$readyItems = array_values(array_filter($doctor['items'], static fn ($item) => $item['severity'] === 'ok'));
$readyPercent = $doctor['summary']['total'] > 0 ? (int) round(($doctor['summary']['ok'] / $doctor['summary']['total']) * 100) : 0;
$nextAction = $hasConfig ? '/devpanel/login.html' : '/devpanel/setup.php';
$nextActionLabel = $hasConfig ? 'Ir al login' : 'Crear configuración';

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
    <main class="content p-4 install-page">
        <section class="install-hero">
            <div>
                <span class="install-kicker">DevPanel setup</span>
                <h1 class="mb-2 fw-bold">Instalación guiada</h1>
                <p class="text-secondary mb-0">Deja permisos, configuración, XAMPP, pruebas y extras listos desde una sola pantalla.</p>
            </div>

            <div class="install-actions">
                <a class="btn btn-outline-info" href="/devpanel/doctor.php">
                    <i class="bi bi-activity"></i>
                    Doctor
                </a>
                <a class="btn btn-devpanel" href="<?php echo $nextAction; ?>">
                    <i class="bi bi-arrow-right-circle"></i>
                    <?php echo $nextActionLabel; ?>
                </a>
            </div>
        </section>

        <section class="install-summary-grid">
            <div class="dashboard-card install-summary-card">
                <span>Preparado</span>
                <strong><?php echo $readyPercent; ?>%</strong>
                <div class="install-progress" aria-label="Progreso de instalación">
                    <span style="width: <?php echo $readyPercent; ?>%"></span>
                </div>
            </div>
            <div class="dashboard-card install-summary-card is-ok">
                <span>Correctos</span>
                <strong><?php echo count($readyItems); ?></strong>
            </div>
            <div class="dashboard-card install-summary-card is-warning">
                <span>Revisar</span>
                <strong><?php echo count($blockingItems); ?></strong>
            </div>
            <div class="dashboard-card install-summary-card is-info">
                <span>Opcionales</span>
                <strong><?php echo count($optionalItems); ?></strong>
            </div>
        </section>

        <div class="row mt-4 g-4">
            <div class="col-12 col-xl-5">
                <div class="dashboard-card doctor-card">
                    <div class="section-title-row">
                        <div>
                            <h4 class="mb-1">Pasos recomendados</h4>
                            <p class="text-secondary mb-0">Orden pensado para una instalación local nueva.</p>
                        </div>
                    </div>
                    <div class="install-step-list">
                        <div class="install-step <?php echo $hasConfig ? 'is-ok' : 'is-active'; ?>">
                            <span>1</span>
                            <div>
                                <strong>Crear configuración privada</strong>
                                <small><?php echo $hasConfig ? 'config.php detectado.' : 'Define la contraseña local desde setup.php.'; ?></small>
                                <a href="/devpanel/setup.php">Abrir setup</a>
                            </div>
                        </div>
                        <div class="install-step <?php echo count($blockingItems) === 0 ? 'is-ok' : 'is-active'; ?>">
                            <span>2</span>
                            <div>
                                <strong>Corregir permisos locales</strong>
                                <small>Necesario para logs, tmp, configuración y creación de proyectos.</small>
                                <code>./scripts/fix-local-permissions.sh</code>
                            </div>
                        </div>
                        <div class="install-step">
                            <span>3</span>
                            <div>
                                <strong>Arrancar XAMPP</strong>
                                <small>Apache y MariaDB deben estar activos para usar el panel completo.</small>
                                <code>sudo /opt/lampp/lampp start</code>
                            </div>
                        </div>
                        <div class="install-step">
                            <span>4</span>
                            <div>
                                <strong>Ejecutar pruebas locales</strong>
                                <small>Comprueba APIs, botones críticos y pantallas principales.</small>
                                <code>DEVPANEL_TEST_PASSWORD=... ./scripts/devpanel-api-smoke.sh</code>
                                <code>DEVPANEL_TEST_PASSWORD=... ./scripts/devpanel-visual-smoke.sh</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card doctor-card mt-4">
                    <h4 class="mb-1">Comandos útiles</h4>
                    <div class="doctor-command-list">
                        <?php foreach ($doctor['commands'] as $label => $command): ?>
                            <div class="doctor-command">
                                <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                <code><?php echo htmlspecialchars($command, ENT_QUOTES, 'UTF-8'); ?></code>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="dashboard-card doctor-card">
                    <div class="section-title-row">
                        <div>
                            <h4 class="mb-1">Checks de instalación</h4>
                            <p class="text-secondary mb-0">Los avisos opcionales no bloquean el uso normal.</p>
                        </div>
                        <a href="/devpanel/install.php" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-arrow-clockwise"></i>
                            Recalcular
                        </a>
                    </div>
                    <div class="doctor-check-list">
                        <?php foreach ($doctor['items'] as $item): ?>
                            <div class="doctor-check is-<?php echo htmlspecialchars($item['severity'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi <?php echo $item['severity'] === 'ok' ? 'bi-check-circle-fill' : ($item['severity'] === 'info' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill'); ?>"></i>
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
