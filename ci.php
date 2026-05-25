<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/ci.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('settings'))
{
    http_response_code(403);
    echo 'No tienes permiso para ver CI.';
    exit;
}

$csrfToken = generateCsrfToken();
$ci = devpanelCiLocalStatus();
$remote = devpanelCiRemoteRuns(5);

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">CI Health</h1>
            <p class="text-secondary mb-0">Smoke tests, checks locales y estado remoto de GitHub Actions.</p>
        </div>
        <button type="button" class="btn btn-outline-info" onclick="loadCiHealth()">
            <i class="bi bi-arrow-clockwise"></i>
            Recargar
        </button>
    </div>

    <div class="settings-layout mt-4">
        <section class="dashboard-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Checks locales</h4>
                    <p class="text-secondary mb-0" id="ciLocalSummary"><?php echo (int) $ci['summary']['ok']; ?>/<?php echo (int) $ci['summary']['total']; ?> preparados.</p>
                </div>
            </div>
            <div class="database-list" id="ciLocalChecks">
                <?php foreach ($ci['checks'] as $check): ?>
                    <div class="database-row">
                        <div class="database-info">
                            <i class="bi <?php echo $check['ok'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning'; ?>"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card">
            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">GitHub Actions</h4>
                    <p class="text-secondary mb-0" id="ciRemoteSummary"><?php echo htmlspecialchars($remote['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="database-list" id="ciRemoteRuns">
                <?php if (!$remote['runs']): ?>
                    <div class="file-manager-empty"><?php echo htmlspecialchars($remote['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                    <?php foreach ($remote['runs'] as $run): ?>
                        <div class="database-row">
                            <div class="database-info">
                                <i class="bi bi-play-circle-fill"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($run['name'] ?? 'workflow', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars(($run['status'] ?? '-') . ' · ' . ($run['conclusion'] ?? 'pendiente'), ENT_QUOTES, 'UTF-8'); ?></small>
                                </div>
                            </div>
                            <?php if (!empty($run['url'])): ?>
                                <a class="btn btn-sm btn-outline-info" href="<?php echo htmlspecialchars($run['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
