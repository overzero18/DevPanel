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

        const restore = document.createElement('button');
        restore.type = 'button';
        restore.className = 'btn btn-sm btn-outline-warning';
        restore.textContent = 'Restaurar';
        restore.addEventListener('click', () => restoreProjectBackup(backup, false));

        const preview = document.createElement('button');
        preview.type = 'button';
        preview.className = 'btn btn-sm btn-outline-secondary';
        preview.textContent = 'Vista';
        preview.addEventListener('click', () => previewProjectBackup(backup));

        const restoreNew = document.createElement('button');
        restoreNew.type = 'button';
        restoreNew.className = 'btn btn-sm btn-outline-warning';
        restoreNew.textContent = 'Restaurar copia';
        restoreNew.addEventListener('click', () => restoreProjectBackup(backup, true));

        actions.appendChild(download);
        actions.appendChild(preview);
        actions.appendChild(restore);
        actions.appendChild(restoreNew);
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

async function previewProjectBackup(backup)
{
    try
    {
        const response = await fetch(`/devpanel/api/backups/preview.php?file=${encodeURIComponent(backup.file)}`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo abrir la vista', 'danger');
            return;
        }

        const preview = data.preview || {};
        const files = preview.files || [];
        const output = [
            `${preview.file_count || 0} archivos · ${formatBackupSize(preview.total_size || 0)}`,
            '',
            ...files.map(file => `${file.name} · ${formatBackupSize(file.size || 0)} · ${backupFileStateLabel(file.current_state)}`),
            preview.truncated ? '' : null,
            preview.truncated ? 'Vista limitada a los primeros archivos.' : null
        ].filter(Boolean).join('\n');

        await appConfirm(output || 'Backup vacío', {
            title: `Vista ${backup.file}`,
            confirmText: 'Cerrar'
        });
    }
    catch(error)
    {
        console.error(error);
        showToast('Error leyendo backup', 'danger');
    }
}

function backupFileStateLabel(state)
{
    if (state === 'same_size') return 'igual tamaño';
    if (state === 'different_size') return 'cambió tamaño';
    return 'no existe';
}

async function restoreProjectBackup(backup, asNew = false)
{
    const confirmed = await appConfirm(
        asNew
            ? `Se restaurará ${backup.project} desde ${backup.file} en una carpeta nueva.`
            : `Se restaurará ${backup.project} desde ${backup.file}. Antes se creará un backup de seguridad del estado actual.`,
        {
            title: 'Restaurar backup',
            confirmText: 'Restaurar',
            cancelText: 'Cancelar',
            danger: true
        }
    );

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('file', backup.file);
    if (asNew) formData.append('mode', 'new');
    formData.append('csrf_token', csrfToken);

    try
    {
        const response = await fetch('/devpanel/api/backups/restore.php', {
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
        showToast('Error restaurando backup', 'danger');
    }
}

function formatBackupSize(bytes)
{
    const value = Number(bytes) || 0;
    if (value > 1024 * 1024) return `${(value / 1024 / 1024).toFixed(1)} MB`;
    if (value > 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${value} B`;
}
