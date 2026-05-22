let csrfToken = '';
let activeLogType = 'apache_error';
let logsRefreshTimer = null;

document.addEventListener("DOMContentLoaded", () => {

    console.log("DevPanel iniciado 🚀");

    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    if (tokenElement) {
        csrfToken = tokenElement.getAttribute('content');
    }

});

// ===== THEME SWITCHER =====
function changeTheme(themeName) {
    const formData = new URLSearchParams();
    formData.append('theme', themeName);
    formData.append('csrf_token', csrfToken);

    fetch('/devpanel/api/set_theme.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!checkAuth(response)) return;
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            // Reload page to apply theme
            location.reload();
        } else {
            console.error('Error changing theme:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function checkAuth(response) {
    if (response.status === 401) {
        window.location.href = '/devpanel/login.html';
        return false;
    }
    return true;
}

function addCsrfToken(formData) {
    if (csrfToken && formData instanceof URLSearchParams) {
        formData.append('csrf_token', csrfToken);
    }
    return formData;
}

function openFolder(path)
{
    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('csrf_token', csrfToken);

    fetch('/devpanel/api/open_folder.php',
    {
        method: 'POST',
        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!checkAuth(response)) return;
        return response.json();
    })
    .catch(error => console.error(error));
}

function openVSCode(path)
{
    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('csrf_token', csrfToken);

    fetch('/devpanel/api/open_vscode.php',
    {
        method: 'POST',
        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!checkAuth(response)) return;
        return response.json();
    })
    .catch(error => console.error(error));
}

function controlService(service, action)
{
    const formData = new URLSearchParams();
    formData.append('service', service);
    formData.append('action', action);
    formData.append('csrf_token', csrfToken);

    fetch('/devpanel/api/service_control.php',
    {
        method: 'POST',
        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!checkAuth(response)) return;
        return response.json();
    })
    .then(data =>
    {
        if (data) {
            alert(data.output);
            location.reload();
        }
    });
}

function createProject()
{
    const input = document.getElementById('projectName');

    const name = input.value.trim();

    if (!name)
    {
        alert('Introduce un nombre de proyecto');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('csrf_token', csrfToken);

    fetch('/devpanel/api/create_project.php',
    {
        method: 'POST',
        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!checkAuth(response)) return;
        return response.json();
    })
    .then(data =>
    {
        if (!data.success)
        {
            alert(data.message);
            return;
        }

        alert(data.message);

        location.reload();
    })
    .catch(error =>
    {
        console.error(error);

        alert('Error creando proyecto');
    });
}

async function loadLogs()
{
    try
    {
        const params = new URLSearchParams();
        const searchInput = document.getElementById('logSearch');
        const linesSelect = document.getElementById('logLines');

        params.set('type', activeLogType);
        params.set('lines', linesSelect ? linesSelect.value : '120');

        if (searchInput && searchInput.value.trim() !== '') {
            params.set('q', searchInput.value.trim());
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
    }
    catch(error)
    {
        console.error(error);

        showLogMessage('Error cargando logs');
    }
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
    meta.textContent = `${data.label} · ${data.lines} líneas · actualizado ${data.updated_at || '--'}${filterLabel}`;
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

    const autoRefresh = document.getElementById('logsAutoRefresh');
    if (autoRefresh) {
        autoRefresh.addEventListener('change', resetLogsTimer);
    }

    loadLogs();
    resetLogsTimer();
});

async function loadSystemStats()
{
    try
    {
        const response = await fetch(
            '/devpanel/api/system_stats.php'
        );

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            return;
        }

        const cpu = data.cpu_metrics || {};
        const ram = data.ram || {};
        const disk = data.disk || {};

        setText('cpuLoad', `${cpu.percent ?? data.cpu ?? '--'}%`);
        setText(
            'cpuDetail',
            `Carga ${cpu.load_1 ?? data.cpu ?? '--'} · ${cpu.cores ?? '--'} cores`
        );
        setProgress('cpuBar', cpu.percent);

        setText('ramUsage', `${ram.percent ?? '--'}%`);
        setText(
            'ramDetail',
            `${ram.used ?? '--'} MB usados de ${ram.total ?? '--'} MB`
        );
        setProgress('ramBar', ram.percent);

        setText('diskUsage', `${disk.percent ?? '--'}%`);
        setText(
            'diskDetail',
            `${disk.free ?? '--'} GB libres de ${disk.total ?? '--'} GB`
        );
        setProgress('diskBar', disk.percent);

        setText('hostname', data.hostname || '--');
        setText('uptime', data.uptime || '--');
        renderProcesses(data.processes || []);
    }
    catch(error)
    {
        console.error(error);
    }
}

function setText(id, value)
{
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function setProgress(id, value)
{
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    const percent = Number(value);
    element.style.width = `${Number.isFinite(percent) ? Math.max(0, Math.min(100, percent)) : 0}%`;
}

function renderProcesses(processes)
{
    const container = document.getElementById('processList');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!processes.length) {
        const empty = document.createElement('div');
        empty.className = 'process-row process-row-empty';
        empty.textContent = 'Sin datos de procesos';
        container.appendChild(empty);
        return;
    }

    processes.forEach(process => {
        const row = document.createElement('div');
        row.className = 'process-row';

        const identity = document.createElement('div');
        identity.className = 'process-identity';

        const name = document.createElement('strong');
        name.textContent = process.name || 'proceso';

        const pid = document.createElement('span');
        pid.textContent = `PID ${process.pid || '--'}`;

        identity.appendChild(name);
        identity.appendChild(pid);

        const metrics = document.createElement('div');
        metrics.className = 'process-metrics';

        const cpu = document.createElement('span');
        cpu.textContent = `CPU ${process.cpu ?? 0}%`;

        const memory = document.createElement('span');
        memory.textContent = `RAM ${process.memory ?? 0}%`;

        metrics.appendChild(cpu);
        metrics.appendChild(memory);

        row.appendChild(identity);
        row.appendChild(metrics);
        container.appendChild(row);
    });
}

document.addEventListener("DOMContentLoaded", () =>
{
    if (!document.getElementById('cpuLoad')) {
        return;
    }

    loadSystemStats();

    setInterval(loadSystemStats, 5000);
});

let term;

document.addEventListener("DOMContentLoaded", () =>
{
    if (!document.getElementById('terminal')) {
        return;
    }

    initTerminal();
});

function initTerminal()
{
    const terminalElement = document.getElementById('terminal');
    const rootStyles = getComputedStyle(document.documentElement);
    const terminalStyles = getComputedStyle(terminalElement);
    const terminalBackground = terminalStyles.backgroundColor || rootStyles.getPropertyValue('--bg-primary').trim() || '#000000';
    const terminalForeground = rootStyles.getPropertyValue('--text-primary').trim() || '#f8fafc';
    const terminalCursor = rootStyles.getPropertyValue('--accent-primary').trim() || '#38bdf8';

    term = new Terminal({

        cursorBlink: true,

        theme:
        {
            background: terminalBackground,
            foreground: terminalForeground,
            cursor: terminalCursor
        }

    });

    term.open(terminalElement);

    term.write('Carlos DevPanel Terminal\\r\\n');
    term.write('$ ');

    let currentCommand = '';

    term.onData(async (data) =>
    {
        const charCode = data.charCodeAt(0);

        if (charCode === 13)
        {
            term.write('\\r\\n');

            await executeCommand(currentCommand);

            currentCommand = '';

            term.write('\\r\\n$ ');

            return;
        }

        if (charCode === 127)
        {
            if (currentCommand.length > 0)
            {
                currentCommand =
                    currentCommand.slice(0, -1);

                term.write('\\b \\b');
            }

            return;
        }

        currentCommand += data;

        term.write(data);
    });
}

async function executeCommand(command)
{
    try
    {
        const formData = new URLSearchParams();
        formData.append('command', command);
        formData.append('csrf_token', csrfToken);

        const response = await fetch(
            '/devpanel/api/terminal.php',
        {
            method: 'POST',
            headers:
            {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        term.write(data.output || '');
    }
    catch(error)
    {
        term.write('\\r\\nError ejecutando comando');
    }
}

function clearTerminal()
{
    term.clear();

    term.write('$ ');
}

async function generateZip(path)
{
    try
    {
        const formData = new URLSearchParams();
        formData.append('path', path);
        formData.append('csrf_token', csrfToken);

        const response = await fetch(
            '/devpanel/api/generate_zip.php',
        {
            method: 'POST',
            headers:
            {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            alert(data.message);

            return;
        }

       window.location.href = data.download;
    }
    catch(error)
    {
        console.error(error);

        alert('Error generando ZIP');
    }
}

function openDeployModal(project, path)
{
    document.getElementById(
        'deployProjectPath'
    ).value = path;

    const modal =
        new bootstrap.Modal(
            document.getElementById('deployModal')
        );

    modal.show();
}

function toggleDeployOptions()
{
    const type =
        document.getElementById('deployType').value;

    const ftpOptions =
        document.getElementById('ftpOptions');

    ftpOptions.style.display =
        type === 'ftp'
            ? 'block'
            : 'none';
}

async function executeDeploy()
{
    const type =
        document.getElementById('deployType').value;

    const path =
        document.getElementById('deployProjectPath').value;

    if (type === 'zip')
    {
        generateZip(path);

        return;
    }

    const formData = new URLSearchParams();

    formData.append('path', path);

    formData.append(
        'host',
        document.getElementById('ftpHost').value
    );

    formData.append(
        'user',
        document.getElementById('ftpUser').value
    );

    formData.append(
        'pass',
        document.getElementById('ftpPass').value
    );

    formData.append(
        'remote',
        document.getElementById('ftpRemote').value
    );

    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch(
            '/devpanel/api/deploy.php',
        {
            method: 'POST',

            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        console.log(data);

        if (!data.success)
        {
            alert(data.output || data.message);

            return;
        }

        alert(data.output || 'Deploy completado');
    }
    catch(error)
    {
        console.error(error);

        alert('Error deploy');
    }
}

function logout()
{
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        const formData = new URLSearchParams();
        formData.append('csrf_token', csrfToken);

        fetch('/devpanel/api/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
            .then(() => {
                window.location.href = '/devpanel/login.html';
            });
    }
}
