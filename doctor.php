<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/doctor.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$csrfToken = generateCsrfToken();
$doctor = devpanelDoctorChecks();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">

    <div class="doctor-page">

        <div class="section-title-row">
            <div>
                <h1 class="mb-1 fw-bold">Doctor</h1>
                <p class="text-secondary mb-0">Instalación, permisos y entorno local.</p>
            </div>

            <a href="/devpanel/doctor.php" class="btn btn-outline-info">
                <i class="bi bi-arrow-clockwise"></i>
                Revisar
            </a>
        </div>

        <div class="doctor-summary-grid">
            <div class="dashboard-card doctor-summary-card">
                <span>Total</span>
                <strong><?php echo (int) $doctor['summary']['total']; ?></strong>
            </div>

            <div class="dashboard-card doctor-summary-card is-ok">
                <span>OK</span>
                <strong><?php echo (int) $doctor['summary']['ok']; ?></strong>
            </div>

            <div class="dashboard-card doctor-summary-card is-warning">
                <span>Avisos</span>
                <strong><?php echo (int) $doctor['summary']['warnings']; ?></strong>
            </div>

            <div class="dashboard-card doctor-summary-card is-info">
                <span>Opcional</span>
                <strong><?php echo (int) $doctor['summary']['info']; ?></strong>
            </div>
        </div>

        <div class="dashboard-card doctor-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Checks del sistema</h4>
                    <p class="text-secondary mb-0">Validación rápida de dependencias y permisos.</p>
                </div>
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

        <div class="dashboard-card doctor-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Comandos recomendados</h4>
                    <p class="text-secondary mb-0">Úsalos desde una terminal local si algún check falla.</p>
                </div>
            </div>

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

</div>

<?php include 'layout/footer.php'; ?>
