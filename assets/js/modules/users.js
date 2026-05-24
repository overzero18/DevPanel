let adminUsersState = {
    users: [],
    roles: [],
    permissions: {},
    projects: [],
    currentUser: ''
};

async function loadUsersAdmin()
{
    const usersList = document.getElementById('adminUsersList');

    if (!usersList) return;

    try
    {
        const response = await fetch('/devpanel/api/users/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            usersList.textContent = data.message || 'No se pudieron cargar usuarios';
            return;
        }

        adminUsersState = {
            users: data.users || [],
            roles: data.roles || [],
            permissions: data.permissions || {},
            projects: data.projects || [],
            currentUser: data.current_user || ''
        };

        renderUsersAdmin();
        renderRolesAdmin();
        renderPermissionOptions();
    }
    catch(error)
    {
        console.error(error);
        usersList.textContent = 'Error cargando usuarios';
    }
}

function renderUsersAdmin()
{
    const list = document.getElementById('adminUsersList');
    const roleSelect = document.getElementById('adminUserRole');
    const projectSelect = document.getElementById('adminUserProjects');

    if (!list || !roleSelect || !projectSelect) return;

    roleSelect.innerHTML = '';
    adminUsersState.roles.forEach(role => {
        const option = document.createElement('option');
        option.value = role.name;
        option.textContent = role.name;
        roleSelect.appendChild(option);
    });

    projectSelect.innerHTML = '';
    const allOption = document.createElement('option');
    allOption.value = '*';
    allOption.textContent = 'Todos los proyectos';
    projectSelect.appendChild(allOption);
    adminUsersState.projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project.name;
        option.textContent = project.name;
        projectSelect.appendChild(option);
    });

    list.innerHTML = '';

    if (!adminUsersState.users.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin usuarios configurados. Se usa la contraseña principal.';
        list.appendChild(empty);
        return;
    }

    adminUsersState.users.forEach(user => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = 'bi bi-person-fill';
        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = user.name;
        const meta = document.createElement('small');
        meta.textContent = `Rol: ${user.role} · Proyectos: ${(user.projects || ['*']).includes('*') ? 'todos' : (user.projects || []).join(', ')}`;
        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';

        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-sm btn-outline-info';
        edit.textContent = 'Editar';
        edit.addEventListener('click', () => fillUserForm(user));
        actions.appendChild(edit);

        if (user.name !== adminUsersState.currentUser) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger';
            remove.textContent = 'Borrar';
            remove.addEventListener('click', () => deleteAdminUser(user.name));
            actions.appendChild(remove);
        }

        row.appendChild(info);
        row.appendChild(actions);
        list.appendChild(row);
    });
}

function fillUserForm(user)
{
    document.getElementById('adminUserName').value = user.name;
    document.getElementById('adminUserRole').value = user.role;
    document.getElementById('adminUserPassword').value = '';
    const access = user.projects || ['*'];
    [...document.getElementById('adminUserProjects').options].forEach(option => {
        option.selected = access.includes(option.value);
    });
}

async function saveAdminUser()
{
    const name = document.getElementById('adminUserName')?.value.trim() || '';
    const password = document.getElementById('adminUserPassword')?.value || '';
    const role = document.getElementById('adminUserRole')?.value || 'viewer';
    const projects = [...(document.getElementById('adminUserProjects')?.selectedOptions || [])].map(option => option.value);

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('password', password);
    formData.append('role', role);
    (projects.length ? projects : ['*']).forEach(project => formData.append('projects[]', project));
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/users/save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Usuario procesado', data.success ? 'success' : 'danger');

        if (data.success) {
            document.getElementById('adminUserPassword').value = '';
            loadUsersAdmin();
        }
    }
    catch(error)
    {
        console.error(error);
        showToast('Error guardando usuario', 'danger');
    }
}

async function deleteAdminUser(name)
{
    const confirmed = await appConfirm(`Se eliminará el usuario ${name}.`, {
        title: 'Borrar usuario',
        confirmText: 'Borrar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/users/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Usuario eliminado', data.success ? 'success' : 'danger');
        if (data.success) loadUsersAdmin();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error borrando usuario', 'danger');
    }
}

function renderPermissionOptions(selected = [])
{
    const container = document.getElementById('adminPermissionsList');

    if (!container) return;

    container.innerHTML = '';

    Object.entries(adminUsersState.permissions).forEach(([key, label]) => {
        const item = document.createElement('label');
        item.className = 'permission-option';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.value = key;
        input.checked = selected.includes('*') || selected.includes(key);

        const text = document.createElement('div');
        const title = document.createElement('span');
        title.textContent = key;
        const detail = document.createElement('small');
        detail.textContent = label;

        text.appendChild(title);
        text.appendChild(detail);
        item.appendChild(input);
        item.appendChild(text);
        container.appendChild(item);
    });
}

function renderRolesAdmin()
{
    const list = document.getElementById('adminRolesList');

    if (!list) return;

    list.innerHTML = '';

    adminUsersState.roles.forEach(role => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = 'bi bi-shield-check';
        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = role.name;
        const meta = document.createElement('small');
        meta.textContent = role.labels.join(' · ');
        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-sm btn-outline-info';
        edit.textContent = 'Editar';
        edit.addEventListener('click', () => fillRoleForm(role));
        actions.appendChild(edit);

        row.appendChild(info);
        row.appendChild(actions);
        list.appendChild(row);
    });
}

function fillRoleForm(role)
{
    document.getElementById('adminRoleName').value = role.name;
    renderPermissionOptions(role.permissions || []);
}

async function saveAdminRole()
{
    const name = document.getElementById('adminRoleName')?.value.trim() || '';
    const permissions = [...document.querySelectorAll('#adminPermissionsList input:checked')]
        .map(input => input.value);

    const formData = new URLSearchParams();
    formData.append('name', name);
    permissions.forEach(permission => formData.append('permissions[]', permission));
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/users/save_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Rol procesado', data.success ? 'success' : 'danger');
        if (data.success) loadUsersAdmin();
    }
    catch(error)
    {
        console.error(error);
        showToast('Error guardando rol', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('adminUsersList')) {
        loadUsersAdmin();
    }
});
