async function loadLocalDomains()
{
    const container = document.getElementById('localDomainList');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/domains/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            container.textContent = data.message || 'No se pudieron cargar dominios';
            return;
        }

        renderLocalDomains(data.domains || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando dominios';
    }
}

function renderLocalDomains(domains)
{
    const container = document.getElementById('localDomainList');
    container.innerHTML = '';

    if (!domains.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin dominios locales preparados';
        container.appendChild(empty);
        return;
    }

    domains.forEach(domain => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';

        const icon = document.createElement('i');
        icon.className = 'bi bi-globe2';

        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = domain.domain;
        const meta = document.createElement('small');
        meta.textContent = domain.path;

        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';

        const open = document.createElement('a');
        open.className = 'btn btn-sm btn-outline-info';
        open.href = domain.url;
        open.target = '_blank';
        open.rel = 'noopener noreferrer';
        open.textContent = 'Abrir';

        const commands = document.createElement('button');
        commands.type = 'button';
        commands.className = 'btn btn-sm btn-outline-secondary';
        commands.textContent = 'Comandos';
        commands.addEventListener('click', () => showLocalDomainCommands(domain));

        const check = document.createElement('button');
        check.type = 'button';
        check.className = 'btn btn-sm btn-outline-info';
        check.textContent = 'Check';
        check.addEventListener('click', () => checkLocalDomain(domain));

        const apply = document.createElement('button');
        apply.type = 'button';
        apply.className = 'btn btn-sm btn-outline-warning';
        apply.textContent = 'Aplicar';
        apply.addEventListener('click', () => applyLocalDomain(domain));

        actions.appendChild(open);
        actions.appendChild(check);
        actions.appendChild(apply);
        actions.appendChild(commands);
        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function createLocalDomain()
{
    const path = document.getElementById('localDomainProject')?.value || '';
    const domain = document.getElementById('localDomainName')?.value.trim().toLowerCase() || '';

    if (!path || !domain) {
        showToast('Selecciona proyecto e introduce dominio .test', 'danger');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('domain', domain);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/domains/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo crear dominio', 'danger');
            return;
        }

        showToast(data.message, 'success');
        loadLocalDomains();
        showLocalDomainCommands(data.domain);
    }
    catch(error)
    {
        console.error(error);
        showToast('Error creando dominio local', 'danger');
    }
}

async function checkLocalDomain(domain)
{
    try
    {
        const response = await fetch(`/devpanel/api/domains/check.php?domain=${encodeURIComponent(domain.domain)}`);

        if (!checkAuth(response)) return;

        const data = await response.json();
        const detail = data.status ? ` HTTP ${data.status}` : '';
        showToast(`${data.message || 'Check completado'}${detail}`, data.success ? 'success' : 'danger');
    }
    catch(error)
    {
        console.error(error);
        showToast('Error comprobando dominio', 'danger');
    }
}

async function applyLocalDomain(domain)
{
    const confirmed = await appConfirm(`Se intentará aplicar ${domain.domain} con sudo no interactivo.`, {
        title: 'Aplicar dominio local',
        confirmText: 'Aplicar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('domain', domain.domain);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/domains/apply.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Dominio procesado', data.success ? 'success' : 'danger');

        if (!data.success && data.domain) {
            showLocalDomainCommands(data.domain);
        }
    }
    catch(error)
    {
        console.error(error);
        showToast('Error aplicando dominio', 'danger');
    }
}

async function showLocalDomainCommands(domain)
{
    const commands = domain.commands || {};
    const output = Object.entries(commands)
        .map(([label, command]) => `${label}: ${command}`)
        .join('\n');

    await appConfirm(output || 'Sin comandos', {
        title: `Activar ${domain.domain}`,
        confirmText: 'Cerrar'
    });
}
