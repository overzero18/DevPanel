<?php

require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

$changelogPath = __DIR__ . '/CHANGELOG.md';
$lines = is_readable($changelogPath) ? file($changelogPath, FILE_IGNORE_NEW_LINES) : [];

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">Changelog</h1>
            <p class="text-secondary mb-0">Historial local de cambios del proyecto.</p>
        </div>
        <a href="/devpanel/api/diagnostics/export.php" class="btn btn-outline-info">
            <i class="bi bi-file-zip"></i>
            Diagnóstico ZIP
        </a>
    </div>

    <section class="dashboard-card mt-4">
        <div class="database-list">
            <?php if (!$lines): ?>
                <div class="file-manager-empty">No hay changelog disponible.</div>
            <?php else: ?>
                <?php foreach ($lines as $line): ?>
                    <?php if (str_starts_with($line, '# ')): ?>
                        <h3 class="mt-2"><?php echo htmlspecialchars(ltrim($line, '# '), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php elseif (str_starts_with($line, '## ')): ?>
                        <h4 class="mt-4"><?php echo htmlspecialchars(ltrim($line, '# '), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php elseif (str_starts_with($line, '- ')): ?>
                        <div class="database-row">
                            <div class="database-info">
                                <i class="bi bi-check-circle-fill"></i>
                                <div><?php echo htmlspecialchars(substr($line, 2), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    <?php elseif (trim($line) !== ''): ?>
                        <p class="text-secondary"><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include 'layout/footer.php'; ?>
