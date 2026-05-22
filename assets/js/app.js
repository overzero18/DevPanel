let csrfToken = '';

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
        const response = await fetch(
            '/devpanel/api/logs.php?type=apache'
        );

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            alert(data.message);
            return;
        }

        const container =
            document.getElementById('logsContainer');

        container.textContent = data.content;

        container.scrollTop =
            container.scrollHeight;
    }
    catch(error)
    {
        console.error(error);

        alert('Error cargando logs');
    }
}

document.addEventListener("DOMContentLoaded", () =>
{
    if (!document.getElementById('logsContainer')) {
        return;
    }

    loadLogs();

    setInterval(loadLogs, 5000);
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

        document.getElementById('cpuLoad').textContent =
            data.cpu;

        document.getElementById('ramUsage').textContent =
            `${data.ram.used} MB`;

        document.getElementById('diskUsage').textContent =
            `${data.disk.free} GB libres`;

        document.getElementById('hostname').textContent =
            data.hostname;

        document.getElementById('uptime').textContent =
            data.uptime;
    }
    catch(error)
    {
        console.error(error);
    }
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
