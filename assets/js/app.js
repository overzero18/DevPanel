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
        if (input.type === 'checkbox') {
            formData.append(input.dataset.setting, input.checked ? '1' : '0');
        }
        else {
            formData.append(input.dataset.setting, input.value.trim());
        }
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

document.addEventListener("DOMContentLoaded", () =>
{
    if (document.getElementById('permissionsList')) {
        loadPermissions();
    }
});

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
