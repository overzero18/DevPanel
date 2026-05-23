let activeLogType = 'apache_error';
let logsRefreshTimer = null;

async function loadLogs()
{
    try
    {
        const params = new URLSearchParams();
        const searchInput = document.getElementById('logSearch');
        const linesSelect = document.getElementById('logLines');
        const projectSelect = document.getElementById('logProject');

        params.set('type', activeLogType);
        params.set('lines', linesSelect ? linesSelect.value : '120');

        if (searchInput && searchInput.value.trim() !== '') {
            params.set('q', searchInput.value.trim());
        }

        if (projectSelect && projectSelect.value !== '') {
            params.set('project', projectSelect.value);
        }

        const response = await fetch(`/devpanel/api/logs.php?${params.toString()}`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showLogMessage(data.message || 'No se pudo cargar el log');
            return;
        }

        const container =
            document.getElementById('logsContainer');

        container.textContent = data.content;

        container.scrollTop =
            container.scrollHeight;

        updateLogMeta(data);
        loadLogInsights();
    }
    catch(error)
    {
        console.error(error);

        showLogMessage('Error cargando logs');
    }
}

async function loadLogInsights()
{
    const container = document.getElementById('logInsightsList');
    const summary = document.getElementById('logInsightsSummary');

    if (!container) return;

    try
    {
        const response = await fetch('/devpanel/api/logs/insights.php');

        if (!checkAuth(response)) return;

        const data = await response.json();
        const info = data.summary || {};
        if (summary) {
            summary.textContent = `${info.danger || 0} errores · ${info.warning || 0} avisos`;
        }
        renderLogInsights(data.items || []);
        loadLogSummary();
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error analizando logs';
    }
}

async function loadLogSummary()
{
    const container = document.getElementById('logSummaryGrid');

    if (!container) return;

    try {
        const response = await fetch('/devpanel/api/logs/summary.php');

        if (!checkAuth(response)) return;

        const data = await response.json();
        renderLogSummary(data.groups || []);
    }
    catch(error) {
        console.error(error);
        container.textContent = 'Error cargando resumen';
    }
}

function renderLogSummary(groups)
{
    const container = document.getElementById('logSummaryGrid');
    container.innerHTML = '';

    groups.forEach(group => {
        const total = Number(group.danger || 0) + Number(group.warning || 0) + Number(group.security || 0);
        const card = document.createElement('button');
        card.type = 'button';
        card.className = `log-summary-card ${group.danger > 0 ? 'is-danger' : total > 0 ? 'is-warning' : 'is-ok'}`;
        card.addEventListener('click', () => {
            if (group.key === 'apache') setActiveLogType('apache_error');
            if (group.key === 'php' || group.key === 'permissions') setActiveLogType('php');
            if (group.key === 'mariadb') setActiveLogType('mariadb');
            if (group.key === 'security') setActiveLogType('devpanel');
        });

        const title = document.createElement('strong');
        title.textContent = group.label;
        const count = document.createElement('span');
        count.textContent = total === 0 ? 'OK' : `${total} eventos`;
        const detail = document.createElement('small');
        detail.textContent = group.latest || (group.readable ? 'Sin errores recientes' : 'Log no legible');

        card.appendChild(title);
        card.appendChild(count);
        card.appendChild(detail);
        container.appendChild(card);
    });
}

function renderLogInsights(items)
{
    const container = document.getElementById('logInsightsList');
    container.innerHTML = '';

    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin errores o avisos recientes.';
        container.appendChild(empty);
        return;
    }

    items.forEach(item => {
        const row = document.createElement('div');
        row.className = `activity-item is-${item.severity || 'info'}`;
        const title = document.createElement('strong');
        title.textContent = `${item.source} · ${item.severity}${item.count > 1 ? ` · x${item.count}` : ''}`;
        const detail = document.createElement('small');
        detail.textContent = item.line;
        row.appendChild(title);
        row.appendChild(detail);
        container.appendChild(row);
    });
}

function showLogMessage(message)
{
    const container = document.getElementById('logsContainer');

    if (container) {
        container.textContent = message;
    }
}

function updateLogMeta(data)
{
    const meta = document.getElementById('logMeta');

    if (!meta) {
        return;
    }

    const filterLabel = data.filtered ? ' · filtrado' : '';
    const hiddenLabel = data.hidden_internal_lines > 0 ? ` · ${data.hidden_internal_lines} internas ocultas` : '';
    meta.textContent = `${data.label} · ${data.lines} líneas · actualizado ${data.updated_at || '--'}${filterLabel}${hiddenLabel}`;
}

function setActiveLogType(type)
{
    activeLogType = type;

    document.querySelectorAll('.log-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.logType === type);
    });

    loadLogs();
}

function resetLogsTimer()
{
    if (logsRefreshTimer) {
        clearInterval(logsRefreshTimer);
        logsRefreshTimer = null;
    }

    const autoRefresh = document.getElementById('logsAutoRefresh');

    if (autoRefresh && autoRefresh.checked) {
        logsRefreshTimer = setInterval(loadLogs, 5000);
    }
}

document.addEventListener("DOMContentLoaded", () =>
{
    if (!document.getElementById('logsContainer')) {
        return;
    }

    document.querySelectorAll('.log-tab').forEach(tab => {
        tab.addEventListener('click', () => setActiveLogType(tab.dataset.logType));
    });

    const searchInput = document.getElementById('logSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchInput.searchTimer);
            searchInput.searchTimer = setTimeout(loadLogs, 250);
        });
    }

    const linesSelect = document.getElementById('logLines');
    if (linesSelect) {
        linesSelect.addEventListener('change', loadLogs);
    }

    const projectSelect = document.getElementById('logProject');
    if (projectSelect) {
        projectSelect.addEventListener('change', loadLogs);
    }

    const autoRefresh = document.getElementById('logsAutoRefresh');
    if (autoRefresh) {
        autoRefresh.addEventListener('change', resetLogsTimer);
    }

    loadLogs();
    resetLogsTimer();
});
