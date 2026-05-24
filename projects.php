<?php

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/projects.php';

if (!isset($_SESSION[SESSION_TOKEN_KEY]))
{
    header('Location: login.html');
    exit;
}

if (!currentUserCan('projects'))
{
    http_response_code(403);
    echo 'No tienes permiso para ver proyectos.';
    exit;
}

$csrfToken = generateCsrfToken();
$projects = getProjects();

?>

<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/topbar.php'; ?>

<div class="content flex-grow-1 p-4">
    <div class="section-title-row">
        <div>
            <h1 class="mb-1 fw-bold">Proyectos</h1>
            <p class="text-secondary mb-0"><?php echo count($projects); ?> proyectos disponibles para tu usuario.</p>
        </div>
        <a href="/devpanel/index.php#projects" class="btn btn-outline-info">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
    </div>

    <div class="row mt-4 g-4">
        <?php foreach ($projects as $project): ?>
            <div class="col-12 col-xl-6">
                <div class="dashboard-card project-detail-card">
                    <div class="section-title-row">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="text-secondary mb-0">
                                <?php echo htmlspecialchars($project['type'], ENT_QUOTES, 'UTF-8'); ?>
                                · <?php echo htmlspecialchars($project['modified_label'], ENT_QUOTES, 'UTF-8'); ?>
                                · <?php echo htmlspecialchars((string) $project['size'], ENT_QUOTES, 'UTF-8'); ?> MB
                            </p>
                        </div>
                        <span class="file-permission-pill <?php echo $project['writable'] ? 'is-ok' : 'is-warning'; ?>">
                            <?php echo $project['writable'] ? 'editable' : 'solo lectura'; ?>
                        </span>
                    </div>

                    <div class="database-list mt-3">
                        <div class="database-row">
                            <div class="database-info">
                                <i class="bi bi-git"></i>
                                <div>
                                    <strong><?php echo $project['git']['enabled'] ? htmlspecialchars($project['git']['branch'], ENT_QUOTES, 'UTF-8') : 'Sin Git'; ?></strong>
                                    <small><?php echo $project['git']['dirty'] ? (int) $project['git']['changes'] . ' cambios' : 'limpio'; ?></small>
                                </div>
                            </div>
                            <div class="database-actions">
                                <a class="btn btn-sm btn-outline-info" href="<?php echo htmlspecialchars($project['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectProjectInFileManager('<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>')">Archivos</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="openProjectTerminal('<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>')">Terminal</button>
                            </div>
                        </div>
                    </div>

                    <code class="d-block mt-3"><?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$projects): ?>
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="file-manager-empty">No hay proyectos disponibles para tu usuario.</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
