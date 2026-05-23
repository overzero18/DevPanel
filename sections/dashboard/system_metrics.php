<!-- Estadísticas sistema -->
<div class="row g-4 mb-4">

    <div class="col-md-6 col-xl-3">
        <div class="dashboard-card metric-card">
            <div class="metric-card-header">
                <span class="metric-icon metric-icon-info">
                    <i class="bi bi-cpu-fill"></i>
                </span>
                <span class="metric-label">CPU</span>
            </div>

            <h3 id="cpuLoad">--</h3>
            <p id="cpuDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="cpuBar"></span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="dashboard-card metric-card">
            <div class="metric-card-header">
                <span class="metric-icon metric-icon-warning">
                    <i class="bi bi-memory"></i>
                </span>
                <span class="metric-label">RAM</span>
            </div>

            <h3 id="ramUsage">--</h3>
            <p id="ramDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="ramBar"></span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="dashboard-card metric-card">
            <div class="metric-card-header">
                <span class="metric-icon metric-icon-success">
                    <i class="bi bi-hdd-fill"></i>
                </span>
                <span class="metric-label">Disco</span>
            </div>

            <h3 id="diskUsage">--</h3>
            <p id="diskDetail" class="metric-detail">Esperando datos</p>

            <div class="metric-progress" aria-hidden="true">
                <span id="diskBar"></span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="dashboard-card metric-card">
            <div class="metric-card-header">
                <span class="metric-icon metric-icon-primary">
                    <i class="bi bi-pc-display"></i>
                </span>
                <span class="metric-label">Host</span>
            </div>

            <h3 id="hostname" class="hostname-value">--</h3>
            <p class="metric-detail">
                Uptime: <span id="uptime">--</span>
            </p>
        </div>
    </div>

</div>
