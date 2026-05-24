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
        loadBackupSchedules();
    }
    catch(error)
    {
        console.error(error);
        container.textContent = 'Error cargando backups';
    }
}

function escapeBackupHtml(value)
{
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function loadBackupSchedules()
{
    const container = document.getElementById('backupScheduleList');
    const help = document.getElementById('backupScheduleHelp');

    if (!container) return;

    try {
        const response = await fetch('/devpanel/api/backups/schedules.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (help && data.runner) {
            help.textContent = data.runner;
        }

        renderBackupSchedules(data.schedules || []);
    }
    catch(error) {
        console.error(error);
        container.textContent = 'Error cargando programaciones';
    }
}

function renderBackupSchedules(schedules)
{
    const container = document.getElementById('backupScheduleList');
    container.innerHTML = '';

    if (!schedules.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin backups programados.';
        container.appendChild(empty);
        return;
    }

    schedules.forEach(schedule => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = schedule.enabled ? 'bi bi-calendar-check-fill' : 'bi bi-calendar-x';
        const text = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = `${schedule.project} · ${backupFrequencyLabel(schedule.frequency)}`;
        const meta = document.createElement('small');
        meta.textContent = schedule.last_run_at
            ? `Último: ${schedule.last_run_at} · ${schedule.last_file || 'sin archivo'}`
            : 'Pendiente de primera ejecución';

        text.appendChild(title);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';

        const run = document.createElement('button');
        run.type = 'button';
        run.className = 'btn btn-sm btn-outline-info';
        run.textContent = 'Ejecutar';
        run.addEventListener('click', () => runBackupScheduleNow(schedule.id));

        const history = document.createElement('button');
        history.type = 'button';
        history.className = 'btn btn-sm btn-outline-secondary';
        history.textContent = 'Historial';
        history.addEventListener('click', () => showBackupScheduleHistory(schedule));

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.textContent = 'Quitar';
        remove.addEventListener('click', () => deleteBackupSchedule(schedule.id));
        actions.appendChild(run);
        actions.appendChild(history);
        actions.appendChild(remove);

        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function runBackupScheduleNow(id)
{
    const confirmed = await appConfirm('Ejecutar ahora esta programación de backup.', {
        title: 'Ejecutar backup',
        confirmText: 'Ejecutar'
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('action', 'run_now');
    formData.append('id', id);
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch('/devpanel/api/backups/schedules.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Backup ejecutado', data.success ? 'success' : 'danger');

        if (data.success) {
            loadBackups();
        }
    }
    catch(error) {
        console.error(error);
        showToast('Error ejecutando backup programado', 'danger');
    }
}

async function showBackupScheduleHistory(schedule)
{
    const history = schedule.history || [];

    if (!history.length) {
        await appConfirm('Esta programación todavía no tiene ejecuciones.', {
            title: `Historial ${schedule.project}`,
            confirmText: 'Cerrar'
        });
        return;
    }

    showBackupHistoryModal(`Historial ${schedule.project}`, history);
}

function backupFrequencyLabel(frequency)
{
    if (frequency === 'hourly') return 'cada hora';
    if (frequency === 'weekly') return 'semanal';
    return 'diario';
}

async function saveBackupSchedule()
{
    const path = document.getElementById('backupScheduleProject')?.value || '';
    const frequency = document.getElementById('backupScheduleFrequency')?.value || 'daily';

    if (!path) {
        showToast('Selecciona un proyecto', 'danger');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('frequency', frequency);
    formData.append('enabled', '1');
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch('/devpanel/api/backups/schedules.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Programación guardada', data.success ? 'success' : 'danger');
        if (data.success) loadBackupSchedules();
    }
    catch(error) {
        console.error(error);
        showToast('Error guardando programación', 'danger');
    }
}

async function deleteBackupSchedule(id)
{
    const confirmed = await appConfirm('¿Eliminar esta programación?', {
        title: 'Backups programados',
        confirmText: 'Eliminar'
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('action', 'delete');
    formData.append('id', id);
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch('/devpanel/api/backups/schedules.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Programación eliminada', data.success ? 'success' : 'danger');
        if (data.success) loadBackupSchedules();
    }
    catch(error) {
        console.error(error);
        showToast('Error eliminando programación', 'danger');
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

        const restoreSelected = document.createElement('button');
        restoreSelected.type = 'button';
        restoreSelected.className = 'btn btn-sm btn-outline-warning';
        restoreSelected.textContent = 'Restaurar archivos';
        restoreSelected.addEventListener('click', () => showBackupRestoreTree(backup));

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-sm btn-outline-danger';
        deleteButton.textContent = 'Borrar';
        deleteButton.addEventListener('click', () => deleteProjectBackup(backup.file));

        actions.appendChild(download);
        actions.appendChild(preview);
        actions.appendChild(restore);
        actions.appendChild(restoreNew);
        actions.appendChild(restoreSelected);
        actions.appendChild(deleteButton);
        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function restoreSelectedBackupFiles(backup)
{
    const value = await appPrompt('Rutas dentro del ZIP separadas por coma', {
        title: 'Restauración selectiva',
        placeholder: 'index.php, assets/css/style.css'
    });

    if (!value) return;

    const files = value.split(',').map(item => item.trim()).filter(Boolean);

    if (!files.length) return;

    return restoreProjectBackup(backup, false, files);
}

async function showBackupRestoreTree(backup)
{
    try {
        const response = await fetch(`/devpanel/api/backups/preview.php?file=${encodeURIComponent(backup.file)}&limit=1000`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo leer el backup', 'danger');
            return;
        }

        renderBackupRestoreTreeModal(backup, data.preview || {});
    }
    catch(error) {
        console.error(error);
        showToast('Error abriendo restauración visual', 'danger');
    }
}

function renderBackupRestoreTreeModal(backup, preview)
{
    let modalElement = document.getElementById('backupRestoreTreeModal');

    if (!modalElement) {
        modalElement = document.createElement('div');
        modalElement.className = 'modal fade';
        modalElement.id = 'backupRestoreTreeModal';
        modalElement.tabIndex = -1;
        modalElement.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content app-dialog">
                    <div class="modal-header">
                        <h5 class="modal-title" id="backupRestoreTreeTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="backup-compare-grid" id="backupRestoreSummary"></div>
                        <div class="backup-compare-grid mt-3" id="backupRestoreImpact"></div>
                        <div class="database-toolbar mt-3">
                            <input type="search" id="backupRestoreSearch" class="form-control" placeholder="Filtrar archivo">
                            <button type="button" class="btn btn-outline-info" id="backupRestoreSelectAll">Todo</button>
                            <button type="button" class="btn btn-outline-secondary" id="backupRestoreSelectChanged">Cambiados/nuevos</button>
                            <button type="button" class="btn btn-outline-secondary" id="backupRestoreSelectNone">Nada</button>
                        </div>
                        <div class="backup-restore-tree mt-3" id="backupRestoreTree"></div>
                    </div>
                    <div class="modal-footer">
                        <span class="text-secondary me-auto" id="backupRestoreSelectedCount">0 archivos</span>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-devpanel" id="backupRestoreRun">Restaurar selección</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalElement);
    }

    const files = preview.files || [];
    const diff = preview.diff_summary || {};
    modalElement.querySelector('#backupRestoreTreeTitle').textContent = `Restauración visual · ${backup.file}`;
    modalElement.querySelector('#backupRestoreSummary').innerHTML = [
        ['Idénticos', diff.same_hash || 0, 'is-ok'],
        ['Cambiados', diff.different_hash || 0, 'is-warning'],
        ['Nuevos', diff.missing || 0, 'is-info'],
        ['Total', preview.file_count || files.length, '']
    ].map(([label, value, state]) => `<div class="backup-compare-card ${state}"><span>${label}</span><strong>${value}</strong></div>`).join('');
    modalElement.querySelector('#backupRestoreImpact').innerHTML = [
        ['Antes', `${diff.same_hash || 0} iguales · ${diff.different_hash || 0} distintos`, ''],
        ['Después', `${(diff.same_hash || 0) + (diff.different_hash || 0) + (diff.missing || 0)} desde backup`, 'is-info'],
        ['Riesgo', diff.different_hash || diff.missing ? 'Sobrescribe cambios' : 'Sin cambios detectados', diff.different_hash || diff.missing ? 'is-warning' : 'is-ok']
    ].map(([label, value, state]) => `<div class="backup-compare-card ${state}"><span>${label}</span><strong>${value}</strong></div>`).join('');

    const tree = modalElement.querySelector('#backupRestoreTree');
    const search = modalElement.querySelector('#backupRestoreSearch');
    const selectedCount = modalElement.querySelector('#backupRestoreSelectedCount');

    const renderRows = () => {
        const filter = search.value.trim().toLowerCase();
        tree.innerHTML = '';

        files
            .filter(file => !filter || file.name.toLowerCase().includes(filter))
            .forEach(file => {
                const row = document.createElement('label');
                row.className = `backup-restore-row is-${file.current_state || 'missing'}`;
                row.innerHTML = `
                    <input type="checkbox" value="${escapeBackupHtml(file.name)}">
                    <span class="backup-restore-name">${escapeBackupHtml(file.name)}</span>
                    <span class="backup-restore-state">${backupFileStateLabel(file.current_state)}</span>
                    <span class="backup-restore-size">${formatBackupSize(file.size || 0)}</span>
                    <span class="backup-restore-state">${escapeBackupHtml((file.current_hash || '').slice(0, 8) || 'nuevo')} → ${escapeBackupHtml((file.backup_hash || '').slice(0, 8) || 'backup')}</span>
                    <button type="button" class="btn btn-sm btn-outline-info">Versiones</button>
                `;
                row.querySelector('button').addEventListener('click', event => {
                    event.preventDefault();
                    showBackupFileVersions(backup.project, file.name);
                });
                tree.appendChild(row);
            });

        updateSelectedCount();
    };

    const updateSelectedCount = () => {
        selectedCount.textContent = `${tree.querySelectorAll('input:checked').length} archivos seleccionados`;
    };

    const selectBy = callback => {
        tree.querySelectorAll('input').forEach(input => {
            const file = files.find(item => item.name === input.value);
            input.checked = callback(file);
        });
        updateSelectedCount();
    };

    search.oninput = renderRows;
    modalElement.querySelector('#backupRestoreSelectAll').onclick = () => selectBy(() => true);
    modalElement.querySelector('#backupRestoreSelectNone').onclick = () => selectBy(() => false);
    modalElement.querySelector('#backupRestoreSelectChanged').onclick = () => selectBy(file => file && file.current_state !== 'same_hash');
    tree.onchange = updateSelectedCount;
    modalElement.querySelector('#backupRestoreRun').onclick = () => {
        const selected = Array.from(tree.querySelectorAll('input:checked')).map(input => input.value);

        if (!selected.length) {
            showToast('Selecciona al menos un archivo', 'danger');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        restoreProjectBackup(backup, false, selected);
    };

    renderRows();
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

async function showBackupFileVersions(project, file)
{
    try {
        const response = await fetch(`/devpanel/api/backups/versions.php?project=${encodeURIComponent(project)}&file=${encodeURIComponent(file)}`);

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudieron cargar versiones', 'danger');
            return;
        }

        const versions = data.versions || [];
        const output = versions.length
            ? versions.map(item => `${item.created_at} · ${item.backup} · ${formatBackupSize(item.size)} · ${(item.sha256 || '').slice(0, 12)}`).join('\n')
            : 'Sin versiones para este archivo.';

        await appConfirm(output, {
            title: `Versiones · ${file}`,
            confirmText: 'Cerrar'
        });
    }
    catch(error) {
        console.error(error);
        showToast('Error cargando versiones', 'danger');
    }
}

function showBackupHistoryModal(title, history)
{
    let modalElement = document.getElementById('backupHistoryModal');

    if (!modalElement) {
        modalElement = document.createElement('div');
        modalElement.className = 'modal fade';
        modalElement.id = 'backupHistoryModal';
        modalElement.tabIndex = -1;
        modalElement.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content app-dialog">
                    <div class="modal-header">
                        <h5 class="modal-title" id="backupHistoryTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="database-list" id="backupHistoryList"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalElement);
    }

    modalElement.querySelector('#backupHistoryTitle').textContent = title;
    const list = modalElement.querySelector('#backupHistoryList');
    list.innerHTML = '';

    history.forEach(item => {
        const row = document.createElement('div');
        row.className = 'database-row';
        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = 'bi bi-archive-fill';
        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = item.file || 'backup';
        const meta = document.createElement('small');
        meta.textContent = `${item.created_at || '--'} · ${formatBackupSize(item.size || 0)}`;
        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';
        const download = document.createElement('a');
        download.className = 'btn btn-sm btn-outline-info';
        download.href = item.download || `/devpanel/api/backups/download.php?file=${encodeURIComponent(item.file || '')}`;
        download.textContent = 'Descargar';
        const preview = document.createElement('button');
        preview.type = 'button';
        preview.className = 'btn btn-sm btn-outline-secondary';
        preview.textContent = 'Vista';
        preview.addEventListener('click', () => previewProjectBackup({file: item.file}));
        actions.appendChild(download);
        actions.appendChild(preview);

        row.appendChild(info);
        row.appendChild(actions);
        list.appendChild(row);
    });

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
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

async function deleteProjectBackup(file)
{
    const confirmed = await appConfirm(`¿Borrar ${file}?`, {
        title: 'Borrar backup',
        confirmText: 'Borrar'
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('file', file);
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch('/devpanel/api/backups/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Backup borrado', data.success ? 'success' : 'danger');
        if (data.success) loadBackups();
    }
    catch(error) {
        console.error(error);
        showToast('Error borrando backup', 'danger');
    }
}

async function cleanupOldBackups()
{
    const keep = await appPrompt('Cuántos backups recientes quieres conservar', {
        title: 'Limpiar backups antiguos',
        defaultValue: '10',
        placeholder: '10',
        confirmText: 'Limpiar'
    });

    if (!keep) return;

    const formData = new URLSearchParams();
    formData.append('keep', keep);
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch('/devpanel/api/backups/cleanup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Limpieza completada', data.success ? 'success' : 'danger');
        if (data.success) loadBackups();
    }
    catch(error) {
        console.error(error);
        showToast('Error limpiando backups', 'danger');
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
        const diff = preview.diff_summary || {};
        const output = [
            `${preview.file_count || 0} archivos · ${formatBackupSize(preview.total_size || 0)}`,
            `${diff.same_hash || 0} idénticos · ${diff.different_hash || 0} cambiados · ${diff.missing || 0} nuevos`,
            '',
            ...files.map(file => `${file.name} · ${formatBackupSize(file.size || 0)} · ${backupFileStateLabel(file.current_state)}${file.backup_hash ? ` · ${file.backup_hash}` : ''}`),
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
    if (state === 'same_hash') return 'idéntico';
    if (state === 'different_hash') return 'contenido distinto';
    if (state === 'same_size') return 'igual tamaño';
    if (state === 'different_size') return 'cambió tamaño';
    return 'no existe';
}

async function restoreProjectBackup(backup, asNew = false, selectedFiles = [])
{
    const diffMessage = await getBackupRestoreDiffMessage(backup);
    const selectedMessage = selectedFiles.length
        ? `\n\nSolo se restaurarán: ${selectedFiles.join(', ')}`
        : '';
    const confirmed = await appConfirm(
        asNew
            ? `Se restaurará ${backup.project} desde ${backup.file} en una carpeta nueva.\n\n${diffMessage}${selectedMessage}`
            : `Se restaurará ${backup.project} desde ${backup.file}. Antes se creará un backup de seguridad del estado actual.\n\n${diffMessage}${selectedMessage}`,
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
    selectedFiles.forEach(file => formData.append('files[]', file));
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

async function getBackupRestoreDiffMessage(backup)
{
    try {
        const response = await fetch(`/devpanel/api/backups/preview.php?file=${encodeURIComponent(backup.file)}`);

        if (!checkAuth(response)) return 'No se pudo comprobar diff antes de restaurar.';

        const data = await response.json();
        const diff = data.preview?.diff_summary || {};

        return `Diff SHA-256: ${diff.same_hash || 0} idénticos, ${diff.different_hash || 0} cambiados, ${diff.missing || 0} nuevos.`;
    }
    catch(error) {
        console.error(error);
        return 'No se pudo comprobar diff antes de restaurar.';
    }
}

function formatBackupSize(bytes)
{
    const value = Number(bytes) || 0;
    if (value > 1024 * 1024) return `${(value / 1024 / 1024).toFixed(1)} MB`;
    if (value > 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${value} B`;
}
