<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/version.php';
require_once __DIR__ . '/includes/helpers/doctor.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$csrfToken = generateCsrfToken();
$doctor = devpanelDoctorChecks();
$system = [
    'DevPanel' => devpanelVersion(),
    'Commit' => devpanelGitCommit(),
    'Branch' => devpanelGitBranch(),
    'PHP' => PHP_VERSION,
    'Sistema' => php_uname('s') . ' ' . php_uname('r'),
    'Host' => gethostname() ?: 'unknown',
    'SAPI' => php_sapi_name(),
];

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">About</h1>
            <p class="text-secondary mb-0">Versión, sistema y estado técnico del panel.</p>
        </div>
        <a href="/devpanel/api/diagnostics/export.php" class="btn btn-outline-info">
            <i class="bi bi-file-zip"></i>
            Diagnóstico ZIP
        </a>
    </div>

    <div class="settings-layout mt-4">
        <section class="dashboard-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">System Info</h4>
                    <p class="text-secondary mb-0">Información local sin secretos.</p>
                </div>
            </div>
            <div class="database-list">
                <?php foreach ($system as $label => $value): ?>
                    <div class="database-row">
                        <div class="database-info">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Health</h4>
                    <p class="text-secondary mb-0">Resumen del doctor.</p>
                </div>
            </div>
            <div class="doctor-summary-grid">
                <div class="dashboard-card doctor-summary-card"><span>Total</span><strong><?php echo (int) $doctor['summary']['total']; ?></strong></div>
                <div class="dashboard-card doctor-summary-card is-ok"><span>OK</span><strong><?php echo (int) $doctor['summary']['ok']; ?></strong></div>
                <div class="dashboard-card doctor-summary-card is-warning"><span>Avisos</span><strong><?php echo (int) $doctor['summary']['warnings']; ?></strong></div>
                <div class="dashboard-card doctor-summary-card is-info"><span>Opcional</span><strong><?php echo (int) $doctor['summary']['info']; ?></strong></div>
            </div>
        </section>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
