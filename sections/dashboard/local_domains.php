<!-- Local Domains -->
<div class="row mt-5" id="local-domains">

    <div class="col-12">

        <div class="dashboard-card database-manager-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Local domains</h4>
                    <p class="text-secondary mb-0">Prepara dominios tipo proyecto.test y snippets Apache.</p>
                </div>
                <button type="button" class="btn btn-devpanel" onclick="createLocalDomain()">
                    <i class="bi bi-globe2"></i>
                    Crear dominio
                </button>
            </div>

            <div class="database-toolbar">
                <select id="localDomainProject" class="form-select">
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="text"
                    id="localDomainName"
                    class="form-control"
                    placeholder="proyecto.test">

                <button type="button" class="btn btn-outline-info" onclick="loadLocalDomains()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="database-list" id="localDomainList">
                <div class="file-manager-empty">Cargando dominios locales...</div>
            </div>

        </div>

    </div>

</div>
