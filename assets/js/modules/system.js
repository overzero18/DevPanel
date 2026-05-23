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
    loadSystemHealth();
    setTimeout(loadSystemHealth, 350);

    setInterval(loadSystemStats, 5000);
});

async function loadSystemHealth()
{
    const grid = document.getElementById('systemHealthGrid');
    const summary = document.getElementById('systemHealthSummary');

    if (!grid) {
        return;
    }

    const checks = [
        checkHealthEndpoint('permissions', '/devpanel/api/permissions.php', data => {
            const items = data.items || data.permissions || [];
            const problems = Array.isArray(items)
                ? items.filter(item => item.ok === false).length
                : 0;

            return problems === 0
                ? ['is-ok', 'OK']
                : ['is-warning', `${problems} avisos`];
        }),
        checkHealthEndpoint('terminal', '/devpanel/api/terminal.php', null, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                command: 'pwd',
                csrf_token: getSystemCsrfToken()
            })
        }),
        checkHealthEndpoint('git', '/devpanel/api/terminal.php', data => {
            return data.success ? ['is-ok', 'OK'] : ['is-warning', 'Revisar'];
        }, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                command: 'git status',
                csrf_token: getSystemCsrfToken()
            })
        }),
        checkHealthEndpoint('logs', '/devpanel/api/logs/insights.php', data => {
            const danger = Number(data.summary?.danger || 0);
            const warning = Number(data.summary?.warning || 0);

            if (danger > 0) return ['is-danger', `${danger} errores`];
            if (warning > 0) return ['is-warning', `${warning} avisos`];
            return ['is-ok', 'OK'];
        })
    ];

    const results = await Promise.all(checks);
    const failed = results.filter(result => result !== 'is-ok').length;

    if (summary) {
        summary.textContent = failed === 0
            ? 'Todo lo crítico responde correctamente.'
            : `${failed} módulos necesitan revisión.`;
    }
}

async function checkHealthEndpoint(key, url, mapper = null, options = {})
{
    const element = document.querySelector(`[data-health-check="${key}"]`);

    try {
        const response = await fetch(url, options);

        if (!checkAuth(response)) {
            return 'is-danger';
        }

        if (!response.ok) {
            updateHealthItem(element, 'is-warning', 'Sin permiso');
            return 'is-warning';
        }

        const data = await response.json();
        const [state, label] = mapper
            ? mapper(data)
            : (data.success ? ['is-ok', 'OK'] : ['is-warning', 'Revisar']);

        updateHealthItem(element, state, label);
        return state;
    }
    catch(error) {
        updateHealthItem(element, 'is-danger', 'Error');
        return 'is-danger';
    }
}

function getSystemCsrfToken()
{
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : (typeof csrfToken === 'string' ? csrfToken : '');
}

function updateHealthItem(element, state, label)
{
    if (!element) {
        return;
    }

    element.classList.remove('is-pending', 'is-ok', 'is-warning', 'is-danger');
    element.classList.add(state);

    const value = element.querySelector('strong');
    if (value) {
        value.textContent = label;
    }
}
