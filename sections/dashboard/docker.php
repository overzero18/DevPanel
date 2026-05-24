<!-- Docker -->
<div class="row mt-5" id="docker-manager">

    <div class="col-12">

        <div class="dashboard-card docker-manager-card">

            <div class="section-title-row">
                <div>
                    <h4 class="mb-1">Docker</h4>
                    <p class="text-secondary mb-0">Contenedores, Compose y salud de servicios.</p>
                </div>
                <button type="button" class="btn btn-outline-info" onclick="loadDockerContainers()">
                    <i class="bi bi-arrow-clockwise"></i>
                    Recargar
                </button>
            </div>

            <div class="database-list" id="dockerList">
                <div class="file-manager-empty">Cargando Docker...</div>
            </div>

            <div class="database-users">
                <div class="section-title-row">
                    <div>
                        <h5 class="mb-1">Asistente Docker</h5>
                        <p class="text-secondary mb-0">Instalación, daemon, grupo de usuario y comandos recomendados.</p>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="loadDockerSetupAssistant()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="docker-setup-grid" id="dockerSetupAssistant">
                    <div class="file-manager-empty">Comprobando Docker...</div>
                </div>
            </div>

            <div class="database-users">
                <h5 class="mb-3">Docker Compose</h5>
                <div class="database-list" id="dockerComposeList">
                    <div class="file-manager-empty">Cargando compose...</div>
                </div>
            </div>

        </div>

    </div>

</div>
