async function loadDockerContainers()
{
    const container = document.getElementById('dockerList');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/docker/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success || !data.available)
        {
            container.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'file-manager-empty';
            empty.textContent = data.message || 'Docker no está disponible';
            container.appendChild(empty);
            return;
        }

        renderDockerContainers(data.containers || []);
        loadDockerCompose();
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando Docker';
    }
}

async function loadDockerCompose()
{
    const container = document.getElementById('dockerComposeList');

    if (!container) return;

    try
    {
        const response = await fetch('/devpanel/api/docker/compose.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.available) {
            container.innerHTML = `<div class="file-manager-empty">${data.message || 'Docker no disponible'}</div>`;
            return;
        }

        renderDockerCompose(data.files || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando Docker Compose';
    }
}

function renderDockerCompose(files)
{
    const container = document.getElementById('dockerComposeList');
    container.innerHTML = '';

    if (!files.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'No se detectaron archivos compose';
        container.appendChild(empty);
        return;
    }

    files.forEach(file => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = 'bi bi-diagram-3-fill';
        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = file.project;
        const meta = document.createElement('small');
        meta.textContent = file.services && file.services.length
            ? `${file.path} · servicios: ${file.services.join(', ')}`
            : file.path;
        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';
        ['up', 'down', 'ps', 'logs'].forEach(action => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-secondary';
            button.textContent = action;
            button.addEventListener('click', () => runDockerCompose(file.path, action));
            actions.appendChild(button);
        });

        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);

        if (file.services && file.services.length) {
            const services = document.createElement('div');
            services.className = 'compose-service-list';
            file.services.forEach(service => {
                const chip = document.createElement('span');
                chip.className = 'compose-service-chip';
                const status = file.status && file.status[service] ? file.status[service] : {};
                const statusText = [status.state, status.health].filter(Boolean).join('/');
                chip.textContent = statusText ? `${service} · ${statusText}` : service;

                const logs = document.createElement('button');
                logs.type = 'button';
                logs.className = 'btn btn-sm btn-outline-info';
                logs.textContent = 'logs';
                logs.addEventListener('click', () => runDockerCompose(file.path, 'logs', service));

                const restart = document.createElement('button');
                restart.type = 'button';
                restart.className = 'btn btn-sm btn-outline-warning';
                restart.textContent = 'restart';
                restart.addEventListener('click', () => runDockerCompose(file.path, 'restart', service));

                const group = document.createElement('div');
                group.className = 'compose-service-actions';
                group.appendChild(chip);
                group.appendChild(logs);
                group.appendChild(restart);
                services.appendChild(group);
            });
            container.appendChild(services);
        }
    });
}

async function runDockerCompose(path, action, service = '')
{
    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', action);
    if (service) formData.append('service', service);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/docker/compose.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Compose ejecutado', data.success ? 'success' : 'danger');
        await appConfirm(data.output || data.message || 'Sin salida', {
            title: `Docker Compose ${action}${service ? ` ${service}` : ''}`,
            confirmText: 'Cerrar'
        });
        loadDockerCompose();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error ejecutando Compose', 'danger');
    }
}
