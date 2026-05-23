<!-- Backups -->
<div class="row mt-5" id="backups-manager">

    <div class="col-12">

        <div class="dashboard-card database-manager-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Backups</h4>
                    <p class="text-secondary mb-0">Backups ZIP de proyectos con historial descargable.</p>
                </div>
                <button type="button" class="btn btn-devpanel" onclick="createProjectBackup()">
                    <i class="bi bi-archive"></i>
                    Crear backup
                </button>
            </div>

            <div class="database-toolbar">
                <select id="backupProject" class="form-select">
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="btn btn-outline-info" onclick="loadBackups()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="activity-item">
                <strong>Cron opcional</strong>
                <small>/opt/lampp/bin/php <?php echo htmlspecialchars(dirname(__DIR__, 2), ENT_QUOTES, 'UTF-8'); ?>/scripts/devpanel-backup-runner.php all</small>
            </div>

            <div class="database-list" id="backupList">
                <div class="file-manager-empty">Cargando backups...</div>
            </div>

        </div>

    </div>

</div>
