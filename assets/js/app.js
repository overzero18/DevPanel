document.addEventListener("DOMContentLoaded", () => {

    console.log("DevPanel iniciado 🚀");

});

function openFolder(path)
{
    fetch('/devpanel/api/open_folder.php',
    {
        method: 'POST',

        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body: 'path=' + encodeURIComponent(path)
    });
}

function openVSCode(path)
{
    fetch('/devpanel/api/open_vscode.php',
    {
        method: 'POST',

        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body: 'path=' + encodeURIComponent(path)
    });
}

function controlService(service, action)
{
    fetch('/devpanel/api/service_control.php',
    {
        method: 'POST',

        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body:
            'service=' + encodeURIComponent(service) +
            '&action=' + encodeURIComponent(action)
    })
    .then(response => response.json())
    .then(data =>
    {
        alert(data.output);

        location.reload();
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

    fetch('/devpanel/api/create_project.php',
    {
        method: 'POST',

        headers:
        {
            'Content-Type': 'application/x-www-form-urlencoded'
        },

        body: 'name=' + encodeURIComponent(name)
    })
    .then(response => response.json())
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

/* Auto cargar logs */
document.addEventListener("DOMContentLoaded", () =>
{
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

/* Auto refresh */
document.addEventListener("DOMContentLoaded", () =>
{
    loadSystemStats();

    setInterval(loadSystemStats, 5000);
});

let term;

document.addEventListener("DOMContentLoaded", () =>
{
    initTerminal();
});

function initTerminal()
{
    term = new Terminal({

        cursorBlink: true,

        theme:
        {
            background: '#000000',
            foreground: '#00ff00'
        }

    });

    term.open(document.getElementById('terminal'));

    term.write('Carlos DevPanel Terminal\\r\\n');
    term.write('$ ');

    let currentCommand = '';

    term.onData(async (data) =>
    {
        const charCode = data.charCodeAt(0);

        /* Enter */
        if (charCode === 13)
        {
            term.write('\\r\\n');

            await executeCommand(currentCommand);

            currentCommand = '';

            term.write('\\r\\n$ ');

            return;
        }

        /* Backspace */
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
        const response = await fetch(
            '/devpanel/api/terminal.php',
        {
            method: 'POST',

            headers:
            {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body:
                'command=' +
                encodeURIComponent(command)
        });

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
        const response = await fetch(
            '/devpanel/api/generate_zip.php',
        {
            method: 'POST',

            headers:
            {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body:
                'path=' +
                encodeURIComponent(path)
        });

        const data = await response.json();

        if (!data.success)
        {
            alert(data.message);

            return;
        }

        window.open(data.download, '_blank');
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

    /* ZIP */
    if (type === 'zip')
    {
        generateZip(path);

        return;
    }

    /* FTP */
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

    try
    {
        const response = await fetch(
            '/devpanel/api/deploy.php',
        {
            method: 'POST',

            headers:
            {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body: formData
        });

        const data = await response.json();

        alert(data.output || 'Deploy completado');
    }
    catch(error)
    {
        console.error(error);

        alert('Error deploy');
    }
}