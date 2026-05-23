async function loadAuditLog()
{
    const container = document.getElementById('auditList');

    if (!container) return;

    const params = new URLSearchParams();
    const search = document.getElementById('auditSearch')?.value || '';
    const action = document.getElementById('auditAction')?.value || '';
    const user = document.getElementById('auditUser')?.value || '';
    const limit = document.getElementById('auditLimit')?.value || '120';

    if (search.trim()) params.set('q', search.trim());
    if (action) params.set('action', action);
    if (user) params.set('user', user);
    params.set('limit', limit);

    try {
        const response = await fetch(`/devpanel/api/audit/list.php?${params.toString()}`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            container.textContent = data.message || 'No se pudo cargar auditoría';
            return;
        }

        hydrateAuditSelect('auditAction', data.actions || [], 'Todas las acciones');
        hydrateAuditSelect('auditUser', data.users || [], 'Todos los usuarios');
        renderAuditItems(data.items || []);
    }
    catch(error) {
        console.error(error);
        container.textContent = 'Error cargando auditoría';
    }
}

function hydrateAuditSelect(id, values, label)
{
    const select = document.getElementById(id);

    if (!select || select.dataset.hydrated === '1') return;

    const current = select.value;
    select.innerHTML = '';

    const empty = document.createElement('option');
    empty.value = '';
    empty.textContent = label;
    select.appendChild(empty);

    values.forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    });

    select.value = current;
    select.dataset.hydrated = '1';
}

function renderAuditItems(items)
{
    const container = document.getElementById('auditList');
    container.innerHTML = '';

    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin eventos para esos filtros.';
        container.appendChild(empty);
        return;
    }

    items.forEach(item => {
        const row = document.createElement('div');
        row.className = 'activity-item';

        const title = document.createElement('strong');
        title.textContent = `${item.action} · ${item.user}`;

        const detail = document.createElement('small');
        detail.textContent = `${item.time} · ${item.ip}${item.details ? ` · ${item.details}` : ''}`;

        row.appendChild(title);
        row.appendChild(detail);
        container.appendChild(row);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('auditList')) return;

    ['auditSearch', 'auditAction', 'auditUser', 'auditLimit'].forEach(id => {
        const element = document.getElementById(id);
        if (!element) return;
        element.addEventListener(id === 'auditSearch' ? 'input' : 'change', () => {
            clearTimeout(element.auditTimer);
            element.auditTimer = setTimeout(loadAuditLog, 250);
        });
    });

    loadAuditLog();
});
