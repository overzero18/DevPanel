<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/helpers/releases.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$csrfToken = generateCsrfToken();
$releases = devpanelGitHubReleases();
$currentVersion = devpanelLocalVersion();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">Releases</h1>
            <p class="text-secondary mb-0">Versiones disponibles de DevPanel.</p>
        </div>
        <div class="badge badge-info">Current: v<?php echo htmlspecialchars($currentVersion); ?></div>
    </div>

    <div class="settings-layout mt-4">
        <?php if (empty($releases)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Configure GitHub repository en Settings para ver releases disponibles.
            </div>
        <?php else: ?>
            <?php foreach ($releases as $release): ?>
                <section class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h4 class="mb-1">
                                <?php echo htmlspecialchars($release['name']); ?>
                                <?php if ($release['prerelease']): ?>
                                    <span class="badge badge-warning">Beta</span>
                                <?php endif; ?>
                                <?php if ($release['draft']): ?>
                                    <span class="badge badge-secondary">Draft</span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-secondary small mb-2">
                                <i class="bi bi-tag"></i> v<?php echo htmlspecialchars($release['version']); ?>
                                • <i class="bi bi-calendar"></i>
                                <?php echo date('Y-m-d', strtotime($release['published_at'])); ?>
                            </p>
                            <?php if ($release['body']): ?>
                                <div class="release-notes mt-2 text-secondary small" style="max-height: 150px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars(substr($release['body'], 0, 500))); ?>
                                    <?php if (strlen($release['body']) > 500): ?>
                                        ...
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ms-3">
                            <a href="<?php echo htmlspecialchars($release['download_url']); ?>"
                               class="btn btn-sm btn-outline-primary"
                               target="_blank">
                                <i class="bi bi-download"></i> ZIP
                            </a>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
