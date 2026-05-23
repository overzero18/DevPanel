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

function showToast(message, type = 'info')
{
    let container = document.getElementById('toastContainer');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `app-toast app-toast-${type}`;

    const icon = document.createElement('i');
    icon.className = type === 'success'
        ? 'bi bi-check-circle-fill'
        : type === 'danger'
            ? 'bi bi-exclamation-triangle-fill'
            : 'bi bi-info-circle-fill';

    const text = document.createElement('span');
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.innerHTML = '<i class="bi bi-x"></i>';
    close.addEventListener('click', () => toast.remove());

    toast.appendChild(icon);
    toast.appendChild(text);
    toast.appendChild(close);
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('is-hiding');
        setTimeout(() => toast.remove(), 180);
    }, 4200);
}

function openAppDialog(options)
{
    return new Promise(resolve => {
        let modalElement = document.getElementById('appDialogModal');

        if (!modalElement) {
            modalElement = document.createElement('div');
            modalElement.className = 'modal fade';
            modalElement.id = 'appDialogModal';
            modalElement.tabIndex = -1;
            modalElement.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content app-dialog">
                        <div class="modal-header">
                            <h5 class="modal-title" id="appDialogTitle"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="appDialogMessage" class="mb-3"></p>
                            <input type="text" id="appDialogInput" class="form-control">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="appDialogCancel">Cancelar</button>
                            <button type="button" class="btn btn-devpanel" id="appDialogConfirm">Aceptar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalElement);
        }

        const title = modalElement.querySelector('#appDialogTitle');
        const message = modalElement.querySelector('#appDialogMessage');
        const input = modalElement.querySelector('#appDialogInput');
        const confirm = modalElement.querySelector('#appDialogConfirm');
        const cancel = modalElement.querySelector('#appDialogCancel');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        let settled = false;

        title.textContent = options.title || 'Confirmar';
        message.textContent = options.message || '';
        confirm.textContent = options.confirmText || 'Aceptar';
        cancel.textContent = options.cancelText || 'Cancelar';
        input.hidden = options.type !== 'prompt';
        input.value = options.defaultValue || '';
        input.placeholder = options.placeholder || '';

        const cleanup = () => {
            confirm.onclick = null;
            modalElement.removeEventListener('hidden.bs.modal', onHidden);
        };

        const finish = value => {
            settled = true;
            cleanup();
            modal.hide();
            resolve(value);
        };

        const onHidden = () => {
            if (!settled) {
                cleanup();
                resolve(options.type === 'prompt' ? null : false);
            }
        };

        confirm.onclick = () => {
            finish(options.type === 'prompt' ? input.value.trim() : true);
        };

        modalElement.addEventListener('hidden.bs.modal', onHidden);
        modal.show();

        if (options.type === 'prompt') {
            setTimeout(() => input.focus(), 180);
        }
    });
}

function appConfirm(message, options = {})
{
    return openAppDialog({
        type: 'confirm',
        title: options.title || 'Confirmar acción',
        message,
        confirmText: options.confirmText || 'Aceptar',
        cancelText: options.cancelText || 'Cancelar'
    });
}

function appPrompt(message, options = {})
{
    return openAppDialog({
        type: 'prompt',
        title: options.title || 'Nuevo valor',
        message,
        defaultValue: options.defaultValue || '',
        placeholder: options.placeholder || '',
        confirmText: options.confirmText || 'Guardar',
        cancelText: options.cancelText || 'Cancelar'
    });
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
            showToast(data.output || 'Acción completada', data.success === false ? 'danger' : 'success');
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
        showToast('Introduce un nombre de proyecto', 'danger');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('template', document.querySelector('input[name="projectTemplate"]:checked')?.value || 'php');
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
            showToast(data.message, 'danger');
            return;
        }

        showToast(data.message, 'success');

        location.reload();
    })
    .catch(error =>
    {
        console.error(error);

        showToast('Error creando proyecto', 'danger');
    });
}

async function saveGithubSettings()
{
    const formData = new URLSearchParams();
    formData.append('github_user', document.getElementById('githubUser')?.value.trim() || '');
    formData.append('github_repo', document.getElementById('githubRepo')?.value.trim() || '');
    formData.append('github_remote_url', document.getElementById('githubRemoteUrl')?.value.trim() || '');
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/save_github_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.message || 'No se pudo guardar GitHub', 'danger');
            return;
        }

        showToast(data.message, 'success');
    }
    catch(error)
    {
        console.error(error);
        showToast('Error guardando GitHub', 'danger');
    }
}

async function loadDatabases()
{
    const container = document.getElementById('databaseList');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/database/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            container.textContent = data.message || 'No se pudieron cargar las bases de datos';
            return;
        }

        renderDatabases(data.databases || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando bases de datos';
    }
}

function renderDatabases(databases)
{
    const container = document.getElementById('databaseList');
    container.innerHTML = '';

    if (!databases.length)
    {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'No hay bases de datos';
        container.appendChild(empty);
        return;
    }

    databases.forEach(database => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';

        const icon = document.createElement('i');
        icon.className = 'bi bi-database-fill';

        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = database.name;
        const meta = document.createElement('small');
        meta.textContent = `${database.tables} tablas${database.system ? ' · sistema' : ''}`;

        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';

        if (!database.system) {
            const exportButton = document.createElement('button');
            exportButton.type = 'button';
            exportButton.className = 'btn btn-sm btn-outline-info';
            exportButton.innerHTML = '<i class="bi bi-download"></i> Exportar';
            exportButton.addEventListener('click', () => {
                window.location.href = `/devpanel/api/database/export.php?name=${encodeURIComponent(database.name)}`;
            });
            actions.appendChild(exportButton);

            const importButton = document.createElement('button');
            importButton.type = 'button';
            importButton.className = 'btn btn-sm btn-outline-warning';
            importButton.innerHTML = '<i class="bi bi-upload"></i> Importar';
            importButton.addEventListener('click', () => importDatabase(database.name));
            actions.appendChild(importButton);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-sm btn-outline-danger';
            deleteButton.innerHTML = '<i class="bi bi-trash"></i> Borrar';
            deleteButton.addEventListener('click', () => deleteDatabase(database.name));
            actions.appendChild(deleteButton);
        }

        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function loadDatabaseUsers()
{
    const container = document.getElementById('databaseUsersList');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/database/users.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            container.textContent = data.message || 'No se pudieron cargar usuarios';
            return;
        }

        renderDatabaseUsers(data.users || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando usuarios';
    }
}

function renderDatabaseUsers(users)
{
    const container = document.getElementById('databaseUsersList');
    container.innerHTML = '';

    if (!users.length)
    {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'No hay usuarios';
        container.appendChild(empty);
        return;
    }

    users.forEach(user => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';

        const icon = document.createElement('i');
        icon.className = 'bi bi-person-fill';

        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = `${user.user || '(sin nombre)'}@${user.host}`;
        const meta = document.createElement('small');
        meta.textContent = user.system ? 'usuario del sistema' : 'usuario local';

        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);
        row.appendChild(info);
        container.appendChild(row);
    });
}

async function createDatabase()
{
    const name = await appPrompt('Nombre de la base de datos', {
        title: 'Crear base de datos',
        placeholder: 'mi_proyecto_db'
    });

    if (!name) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/database/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.message || 'No se pudo crear la base de datos', 'danger');
            return;
        }

        showToast(data.message, 'success');
        loadDatabases();
        loadDatabaseUsers();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error creando base de datos', 'danger');
    }
}

async function deleteDatabase(name)
{
    const confirmation = await appPrompt(`Escribe "${name}" para borrar esta base de datos`, {
        title: 'Borrar base de datos',
        placeholder: name,
        confirmText: 'Borrar'
    });

    if (!confirmation) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('confirmation', confirmation);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/database/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.message || 'No se pudo borrar la base de datos', 'danger');
            return;
        }

        showToast(data.message, 'success');
        loadDatabases();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error borrando base de datos', 'danger');
    }
}

function importDatabase(name)
{
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.sql';
    input.addEventListener('change', () => {
        if (!input.files.length) {
            return;
        }

        uploadDatabaseImport(name, input.files[0]);
    });
    input.click();
}

async function uploadDatabaseImport(name, file)
{
    const formData = new FormData();
    formData.append('name', name);
    formData.append('sql_file', file);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/database/import.php', {
            method: 'POST',
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.output || data.message || 'No se pudo importar SQL', 'danger');
            return;
        }

        showToast(data.message, 'success');
        loadDatabases();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error importando SQL', 'danger');
    }
}

async function createDatabaseUser()
{
    const username = await appPrompt('Nombre del usuario MariaDB', {
        title: 'Crear usuario',
        placeholder: 'mi_usuario'
    });

    if (!username) {
        return;
    }

    const password = await appPrompt('Contraseña del usuario', {
        title: 'Crear usuario',
        placeholder: 'mínimo 8 caracteres'
    });

    if (!password) {
        return;
    }

    const database = await appPrompt('Base de datos donde tendrá permisos', {
        title: 'Asignar permisos',
        placeholder: 'mi_base_de_datos'
    });

    if (!database) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('database', database);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/database/create_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.message || 'No se pudo crear usuario', 'danger');
            return;
        }

        showToast(data.message, 'success');
        loadDatabaseUsers();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error creando usuario', 'danger');
    }
}

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
        meta.textContent = file.path;
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
    });
}

async function runDockerCompose(path, action)
{
    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', action);
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
            title: `Docker Compose ${action}`,
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

        actions.appendChild(open);
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

async function loadBackups()
{
    const container = document.getElementById('backupList');

    if (!container) return;

    try
    {
        const response = await fetch('/devpanel/api/backups/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();
        renderBackups(data.backups || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando backups';
    }
}

function renderBackups(backups)
{
    const container = document.getElementById('backupList');
    container.innerHTML = '';

    if (!backups.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin backups todavía';
        container.appendChild(empty);
        return;
    }

    backups.forEach(backup => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';

        const icon = document.createElement('i');
        icon.className = 'bi bi-archive-fill';

        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = backup.project;
        const meta = document.createElement('small');
        meta.textContent = `${backup.created_at} · ${formatBackupSize(backup.size)}`;

        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';

        const download = document.createElement('a');
        download.href = backup.download;
        download.className = 'btn btn-sm btn-outline-info';
        download.textContent = 'Descargar';

        actions.appendChild(download);
        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function createProjectBackup()
{
    const path = document.getElementById('backupProject')?.value || '';

    if (!path) {
        showToast('Selecciona un proyecto', 'danger');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/backups/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Backup procesado', data.success ? 'success' : 'danger');

        if (data.success) loadBackups();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error creando backup', 'danger');
    }
}

function formatBackupSize(bytes)
{
    const value = Number(bytes) || 0;
    if (value > 1024 * 1024) return `${(value / 1024 / 1024).toFixed(1)} MB`;
    if (value > 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${value} B`;
}

async function loadNotifications()
{
    const list = document.getElementById('notificationsList');
    const globalList = document.getElementById('globalActivityList');
    const summary = document.getElementById('notificationsSummary');

    if (!list && !globalList) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/notifications/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            if (summary) summary.textContent = data.message || 'No se pudieron cargar eventos.';
            return;
        }

        renderNotifications(data.items || []);

        if (summary) {
            const info = data.summary || {};
            summary.textContent = `${info.total || 0} eventos · ${info.warnings || 0} avisos · ${info.errors || 0} errores`;
        }
    }
    catch(error)
    {
        console.error(error);
        if (summary) summary.textContent = 'Error cargando notificaciones.';
    }
}

function renderNotifications(items)
{
    const list = document.getElementById('notificationsList');
    const globalList = document.getElementById('globalActivityList');

    [list, globalList].forEach((container, index) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';
        const visibleItems = index === 0 ? items : items.slice(0, 5);

        if (!visibleItems.length) {
            const empty = document.createElement('div');
            empty.className = 'file-manager-empty';
            empty.textContent = 'Sin eventos recientes.';
            container.appendChild(empty);
            return;
        }

        visibleItems.forEach(item => {
            const row = document.createElement('div');
            row.className = `notification-row is-${item.severity || 'info'}`;

            const icon = document.createElement('i');
            icon.className = getNotificationIcon(item.severity);

            const body = document.createElement('div');
            body.className = 'notification-body';

            const title = document.createElement('strong');
            title.textContent = item.title || 'Evento';

            const detail = document.createElement('small');
            detail.textContent = item.detail || '--';

            const date = document.createElement('span');
            date.textContent = item.date || '';

            body.appendChild(title);
            body.appendChild(detail);
            body.appendChild(date);
            row.appendChild(icon);
            row.appendChild(body);

            if (index === 0 && item.id) {
                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'notification-dismiss';
                close.title = 'Ocultar';
                close.innerHTML = '<i class="bi bi-x"></i>';
                close.addEventListener('click', () => dismissNotification(item.id));
                row.appendChild(close);
            }

            container.appendChild(row);
        });
    });
}

async function dismissNotification(id)
{
    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('csrf_token', csrfToken);

    await postNotificationDismiss(formData);
}

async function dismissAllNotifications()
{
    const confirmed = await appConfirm('¿Ocultar todas las notificaciones actuales?', {
        title: 'Limpiar notificaciones',
        confirmText: 'Limpiar'
    });

    if (!confirmed) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('clear_all', '1');
    formData.append('csrf_token', csrfToken);

    await postNotificationDismiss(formData);
}

async function postNotificationDismiss(formData)
{
    try
    {
        const response = await fetch('/devpanel/api/notifications/dismiss.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Notificación actualizada', data.success ? 'success' : 'danger');
        loadNotifications();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error actualizando notificaciones', 'danger');
    }
}

function getNotificationIcon(severity)
{
    if (severity === 'danger') return 'bi bi-x-octagon-fill';
    if (severity === 'warning') return 'bi bi-exclamation-triangle-fill';
    if (severity === 'success') return 'bi bi-check-circle-fill';
    return 'bi bi-info-circle-fill';
}

function renderDockerContainers(containers)
{
    const container = document.getElementById('dockerList');
    container.innerHTML = '';

    if (!containers.length)
    {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'No hay contenedores';
        container.appendChild(empty);
        return;
    }

    containers.forEach(docker => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';

        const icon = document.createElement('i');
        icon.className = 'bi bi-box-seam-fill';

        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = docker.name;
        const meta = document.createElement('small');
        meta.textContent = `${docker.image} · ${docker.status}`;

        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);
        row.appendChild(info);

        const actions = document.createElement('div');
        actions.className = 'database-actions';
        actions.appendChild(createDockerActionButton('start', docker.name, 'Iniciar'));
        actions.appendChild(createDockerActionButton('stop', docker.name, 'Detener'));
        actions.appendChild(createDockerActionButton('restart', docker.name, 'Reiniciar'));
        actions.appendChild(createDockerActionButton('logs', docker.name, 'Logs'));
        row.appendChild(actions);

        container.appendChild(row);
    });
}

function createDockerActionButton(action, name, label)
{
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-outline-secondary';
    button.textContent = label;
    button.addEventListener('click', () => runDockerAction(name, action));
    return button;
}

async function runDockerAction(name, action)
{
    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('action', action);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/docker/action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Docker ejecutado', data.success ? 'success' : 'danger');

        if (action === 'logs' || !data.success) {
            await appConfirm(data.output || data.message || 'Sin salida', {
                title: `Docker ${action}`,
                confirmText: 'Cerrar'
            });
        }

        loadDockerContainers();
        loadNotifications();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error ejecutando Docker', 'danger');
    }
}

async function runGitAction(path, action)
{
    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', action);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/git/action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        const output = data.output || data.message || 'Sin salida';

        showToast(data.success ? 'Git ejecutado' : 'Git devolvió error', data.success ? 'success' : 'danger');
        await appConfirm(output, {
            title: `Git ${action}`,
            confirmText: 'Cerrar'
        });
    }
    catch(error)
    {
        console.error(error);
        showToast('Error ejecutando Git', 'danger');
    }
}

async function saveRuntimeSettings()
{
    const formData = new URLSearchParams();

    document.querySelectorAll('.runtime-setting-input').forEach(input => {
        formData.append(input.dataset.setting, input.value.trim());
    });

    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/save_runtime_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showToast(data.message || 'No se pudo guardar configuración', 'danger');
            return;
        }

        showToast(data.message, 'success');
    }
    catch(error)
    {
        console.error(error);
        showToast('Error guardando configuración', 'danger');
    }
}

async function loadPermissions()
{
    const container = document.getElementById('permissionsList');
    const summary = document.getElementById('permissionsSummary');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch('/devpanel/api/permissions.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            container.textContent = data.message || 'No se pudieron revisar permisos';
            return;
        }

        if (summary) {
            summary.textContent = `${data.summary.ok}/${data.summary.total} comprobaciones correctas`;
        }

        renderPermissions(data.items || []);
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error revisando permisos';
    }
}

function renderPermissions(items)
{
    const container = document.getElementById('permissionsList');
    container.innerHTML = '';

    items.forEach(item => {
        const row = document.createElement('div');
        row.className = `permission-row ${item.ok ? 'is-ok' : 'is-warning'}`;

        const status = document.createElement('i');
        status.className = item.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill';

        const body = document.createElement('div');
        body.className = 'permission-body';

        const title = document.createElement('strong');
        title.textContent = item.label;

        const path = document.createElement('code');
        path.textContent = item.path;

        const detail = document.createElement('small');
        const writeText = item.needs_write ? ` · escritura ${item.writable ? 'OK' : 'NO'}` : '';
        detail.textContent = `${item.exists ? 'existe' : 'no existe'} · lectura ${item.readable ? 'OK' : 'NO'}${writeText}`;

        body.appendChild(title);
        body.appendChild(path);
        body.appendChild(detail);

        if (item.hint) {
            const hint = document.createElement('small');
            hint.textContent = item.hint;
            body.appendChild(hint);
        }

        row.appendChild(status);
        row.appendChild(body);
        container.appendChild(row);
    });
}

async function loadProjectActivity()
{
    const select = document.getElementById('projectActivitySelect');

    if (!select || !select.value) {
        renderActivityEmpty('projectRecentFiles', 'No hay proyectos disponibles.');
        renderActivityEmpty('projectActions', 'No hay proyectos disponibles.');
        renderActivityEmpty('projectCommits', 'No hay proyectos disponibles.');
        return;
    }

    renderActivityEmpty('projectRecentFiles', 'Cargando archivos...');
    renderActivityEmpty('projectActions', 'Cargando acciones...');
    renderActivityEmpty('projectCommits', 'Cargando commits...');

    try
    {
        const params = new URLSearchParams();
        params.set('path', select.value);

        const response = await fetch(`/devpanel/api/projects/activity.php?${params.toString()}`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo cargar la actividad', 'danger');
            return;
        }

        renderRecentFiles(data.files || []);
        renderProjectActions(data.actions || []);
        renderProjectCommits(data.commits || []);
    }
    catch(error)
    {
        console.error(error);
        showToast('Error cargando actividad', 'danger');
    }
}

function renderActivityEmpty(id, message)
{
    const container = document.getElementById(id);

    if (!container) {
        return;
    }

    container.innerHTML = '';

    const empty = document.createElement('div');
    empty.className = 'file-manager-empty';
    empty.textContent = message;
    container.appendChild(empty);
}

function renderRecentFiles(files)
{
    const container = document.getElementById('projectRecentFiles');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!files.length) {
        renderActivityEmpty('projectRecentFiles', 'Sin archivos recientes.');
        return;
    }

    files.forEach(file => {
        const item = document.createElement('div');
        item.className = 'activity-item';

        const title = document.createElement('strong');
        title.textContent = file.path || file.name;

        const detail = document.createElement('small');
        detail.textContent = `${file.modified_label || '--'} · ${file.size || '--'}`;

        item.appendChild(title);
        item.appendChild(detail);
        container.appendChild(item);
    });
}

function renderProjectActions(actions)
{
    const container = document.getElementById('projectActions');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!actions.length) {
        renderActivityEmpty('projectActions', 'Sin acciones recientes.');
        return;
    }

    actions.forEach(action => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.textContent = action;
        container.appendChild(item);
    });
}

function renderProjectCommits(commits)
{
    const container = document.getElementById('projectCommits');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!commits.length) {
        renderActivityEmpty('projectCommits', 'Sin repositorio Git o sin commits.');
        return;
    }

    commits.forEach(commit => {
        const item = document.createElement('div');
        item.className = 'activity-item';

        const title = document.createElement('strong');
        title.textContent = commit.message || 'Commit sin mensaje';

        const detail = document.createElement('small');
        detail.textContent = `${commit.hash || '--'} · ${commit.date || '--'}`;

        item.appendChild(title);
        item.appendChild(detail);
        container.appendChild(item);
    });
}

async function runGitActionWithData(formData, title)
{
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/git/action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return null;

        const data = await response.json();
        const output = data.output || data.message || 'Sin salida';

        showToast(data.success ? 'Git ejecutado' : 'Git devolvió error', data.success ? 'success' : 'danger');
        await appConfirm(output, {
            title,
            confirmText: 'Cerrar'
        });

        return data;
    }
    catch(error)
    {
        console.error(error);
        showToast('Error ejecutando Git', 'danger');
        return null;
    }
}

async function setGitRemote(path)
{
    const remote = await appPrompt('Remote de GitHub para origin', {
        title: 'Set remote',
        defaultValue: document.getElementById('githubRemoteUrl')?.value.trim() || '',
        placeholder: 'https://github.com/usuario/repo.git'
    });

    if (!remote) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', 'set_remote');
    formData.append('remote_url', remote);

    await runGitActionWithData(formData, 'Git remote');
}

async function checkoutGitBranch(path)
{
    const branch = await appPrompt('Nombre de la rama a cambiar', {
        title: 'Cambiar rama',
        placeholder: 'main'
    });

    if (!branch) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', 'checkout');
    formData.append('branch', branch);

    await runGitActionWithData(formData, 'Git checkout');
}

async function createGitBranch(path)
{
    const branch = await appPrompt('Nombre de la nueva rama', {
        title: 'Nueva rama',
        placeholder: 'feature/nueva-funcion'
    });

    if (!branch) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('action', 'create_branch');
    formData.append('branch', branch);

    await runGitActionWithData(formData, 'Git branch');
}

async function cloneGithubRepository()
{
    const remote = await appPrompt('Remote de GitHub a clonar', {
        title: 'Clonar repositorio',
        defaultValue: document.getElementById('githubRemoteUrl')?.value.trim() || '',
        placeholder: 'https://github.com/usuario/repo.git'
    });

    if (!remote) {
        return;
    }

    const target = await appPrompt('Nombre de carpeta destino', {
        title: 'Clonar repositorio',
        placeholder: 'mi-proyecto'
    });

    if (!target) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'clone');
    formData.append('remote_url', remote);
    formData.append('target', target);

    const data = await runGitActionWithData(formData, 'Git clone');

    if (data && data.success) {
        setTimeout(() => location.reload(), 900);
    }
}

document.addEventListener("DOMContentLoaded", () =>
{
    if (!document.getElementById('databaseList')) {
        return;
    }

    loadDatabases();
    loadDatabaseUsers();
    loadLocalDomains();
    loadBackups();
    loadDockerContainers();
    loadPermissions();
    loadProjectActivity();
    loadNotifications();

    const projectActivitySelect = document.getElementById('projectActivitySelect');
    if (projectActivitySelect) {
        projectActivitySelect.addEventListener('change', loadProjectActivity);
    }
});

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
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error analizando logs';
    }
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
let terminalCurrentCommand = '';
let terminalHistory = [];
let terminalHistoryIndex = -1;
const terminalFavoriteCommands = ['pwd', 'ls', 'git status', 'git branch', 'php -v'];
const terminalPrompt = '$ ';

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
        convertEol: true,
        scrollback: 1000,

        theme:
        {
            background: terminalBackground,
            foreground: terminalForeground,
            cursor: terminalCursor
        }

    });

    term.open(terminalElement);

    writeTerminalLine('DevPanel Terminal');
    writeTerminalPrompt();

    terminalHistory = loadTerminalHistory();
    renderTerminalHistory();
    renderTerminalFavorites();

    term.onData(async (data) =>
    {
        if (data === '\u001b[A') {
            navigateTerminalHistory(-1);
            return;
        }

        if (data === '\u001b[B') {
            navigateTerminalHistory(1);
            return;
        }

        const charCode = data.charCodeAt(0);

        if (charCode === 13)
        {
            term.write('\r\n');

            await executeCommand(terminalCurrentCommand);

            terminalCurrentCommand = '';
            terminalHistoryIndex = -1;

            term.write('\r\n');
            writeTerminalPrompt();

            return;
        }

        if (charCode === 127)
        {
            if (terminalCurrentCommand.length > 0)
            {
                terminalCurrentCommand =
                    terminalCurrentCommand.slice(0, -1);

                term.write('\\b \\b');
            }

            return;
        }

        terminalCurrentCommand += data;

        term.write(data);
    });
}

async function executeCommand(command)
{
    command = command.trim();

    if (command === '') {
        return;
    }

    addTerminalHistory(command);

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

        writeTerminalOutput(data.output || '');
    }
    catch(error)
    {
        writeTerminalOutput('Error ejecutando comando');
    }
}

function runQuickCommand(command)
{
    if (!term) {
        return;
    }

    terminalCurrentCommand = '';
    term.write(`\r\n${terminalPrompt}${command}\r\n`);
    executeCommand(command).then(() => {
        term.write('\r\n');
        writeTerminalPrompt();
    });
}

function writeTerminalPrompt()
{
    term.write(terminalPrompt);
}

function writeTerminalLine(text)
{
    term.write(`${text}\r\n`);
}

function writeTerminalOutput(output)
{
    if (!output) {
        return;
    }

    const normalized = String(output)
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
        .replace(/\n/g, '\r\n');

    term.write(normalized);
}

function loadTerminalHistory()
{
    try
    {
        return JSON.parse(localStorage.getItem('devpanel_terminal_history') || '[]');
    }
    catch(error)
    {
        return [];
    }
}

function addTerminalHistory(command)
{
    terminalHistory = terminalHistory.filter(item => item !== command);
    terminalHistory.unshift(command);
    terminalHistory = terminalHistory.slice(0, 20);
    localStorage.setItem('devpanel_terminal_history', JSON.stringify(terminalHistory));
    renderTerminalHistory();
}

function renderTerminalHistory()
{
    const container = document.getElementById('terminalHistory');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!terminalHistory.length) {
        const empty = document.createElement('div');
        empty.className = 'terminal-history-empty';
        empty.textContent = 'Sin comandos todavía';
        container.appendChild(empty);
        return;
    }

    terminalHistory.forEach(command => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = command;
        button.addEventListener('click', () => runQuickCommand(command));
        container.appendChild(button);
    });
}

function renderTerminalFavorites()
{
    const container = document.getElementById('terminalFavorites');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    terminalFavoriteCommands.forEach(command => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'terminal-favorite';
        button.textContent = command;
        button.addEventListener('click', () => runQuickCommand(command));
        container.appendChild(button);
    });
}

function navigateTerminalHistory(direction)
{
    if (!terminalHistory.length) {
        return;
    }

    if (terminalHistoryIndex === -1) {
        terminalHistoryIndex = direction < 0 ? 0 : -1;
    }
    else {
        terminalHistoryIndex += direction;
    }

    terminalHistoryIndex = Math.max(0, Math.min(terminalHistory.length - 1, terminalHistoryIndex));
    replaceTerminalCommand(terminalHistory[terminalHistoryIndex]);
}

function replaceTerminalCommand(command)
{
    while (terminalCurrentCommand.length > 0) {
        terminalCurrentCommand = terminalCurrentCommand.slice(0, -1);
        term.write('\\b \\b');
    }

    terminalCurrentCommand = command;
    term.write(command);
}

function clearTerminalHistory()
{
    terminalHistory = [];
    terminalHistoryIndex = -1;
    localStorage.removeItem('devpanel_terminal_history');
    renderTerminalHistory();
}

function clearTerminal()
{
    term.clear();

    terminalCurrentCommand = '';
    writeTerminalPrompt();
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
            showToast(data.message, 'danger');

            return;
        }

       showToast('ZIP generado', 'success');
       window.location.href = data.download;
    }
    catch(error)
    {
        console.error(error);

        showToast('Error generando ZIP', 'danger');
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
            showToast(data.output || data.message, 'danger');

            return;
        }

        showToast(data.output || 'Deploy completado', 'success');
    }
    catch(error)
    {
        console.error(error);

        showToast('Error deploy', 'danger');
    }
}

async function logout()
{
    const confirmed = await appConfirm('¿Cerrar la sesión actual?', {
        title: 'Cerrar sesión',
        confirmText: 'Cerrar sesión'
    });

    if (!confirmed) {
        return;
    }

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
