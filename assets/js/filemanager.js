let currentFileManagerPath = '/opt/lampp/htdocs';
let fileManagerParentPath = null;
let activePreviewPath = null;

function getFileManagerCsrfToken()
{
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : '';
}

async function loadFileManager(path = currentFileManagerPath)
{
    const container = document.getElementById('fileManagerContent');

    if (!container) {
        return;
    }

    try
    {
        const response = await fetch(
            '/devpanel/api/filemanager/list.php?path=' + encodeURIComponent(path)
        );

        if (typeof checkAuth === 'function' && !checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            showFileManagerMessage(data.message || 'No se pudo cargar la carpeta');
            return;
        }

        currentFileManagerPath = data.currentPath;
        fileManagerParentPath = data.parentPath;
        renderFileManager(data);
    }
    catch(error)
    {
        console.error(error);
        showFileManagerMessage('Error cargando archivos');
    }
}

function renderFileManager(data)
{
    const container = document.getElementById('fileManagerContent');
    const currentPath = document.getElementById('fileManagerPath');

    currentPath.textContent = data.currentPath;
    renderFileManagerBreadcrumbs(data.breadcrumbs || []);
    container.innerHTML = '';

    if (!data.items.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Esta carpeta está vacía';
        container.appendChild(empty);
        return;
    }

    data.items.forEach(item => {
        container.appendChild(createFileManagerRow(item));
    });
}

function renderFileManagerBreadcrumbs(breadcrumbs)
{
    const container = document.getElementById('fileManagerBreadcrumbs');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    breadcrumbs.forEach((crumb, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = crumb.name;
        button.addEventListener('click', () => loadFileManager(crumb.path));
        container.appendChild(button);

        if (index < breadcrumbs.length - 1) {
            const separator = document.createElement('span');
            separator.textContent = '/';
            container.appendChild(separator);
        }
    });
}

function createFileManagerRow(item)
{
    const row = document.createElement('div');
    row.className = 'file-manager-row';

    const main = document.createElement('button');
    main.type = 'button';
    main.className = 'file-manager-item';
    main.addEventListener('click', () => handleFileManagerItemClick(item.path, item.type));

    const icon = document.createElement('i');
    icon.className = item.type === 'folder' ? 'bi bi-folder-fill' : 'bi bi-file-earmark-text';

    const nameWrap = document.createElement('span');
    nameWrap.className = 'file-manager-name';

    const name = document.createElement('strong');
    name.textContent = item.name;

    const meta = document.createElement('small');
    meta.textContent = `${item.sizeLabel || '--'} · ${item.modified}`;

    nameWrap.appendChild(name);
    nameWrap.appendChild(meta);
    main.appendChild(icon);
    main.appendChild(nameWrap);

    const actions = document.createElement('div');
    actions.className = 'file-manager-row-actions';

    if (item.type === 'file') {
        actions.appendChild(createFileActionButton('bi-eye', 'Preview', () => previewFile(item.path)));
        actions.appendChild(createFileActionButton('bi-download', 'Descargar', () => downloadFileManagerItem(item.path)));
    }

    actions.appendChild(createFileActionButton('bi-pencil', 'Renombrar', () => renameFileManagerItem(item.path, item.name)));
    actions.appendChild(createFileActionButton('bi-trash', 'Borrar', () => deleteFileManagerItem(item.path, item.name)));

    row.appendChild(main);
    row.appendChild(actions);

    return row;
}

function createFileActionButton(iconName, title, handler)
{
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-outline-secondary';
    button.title = title;
    button.innerHTML = `<i class="bi ${iconName}"></i>`;
    button.addEventListener('click', event => {
        event.stopPropagation();
        handler();
    });
    return button;
}

function handleFileManagerItemClick(path, type)
{
    if (type === 'folder')
    {
        loadFileManager(path);
        return;
    }

    previewFile(path);
}

function fileManagerGoUp()
{
    if (fileManagerParentPath) {
        loadFileManager(fileManagerParentPath);
    }
}

async function previewFile(path)
{
    try
    {
        const response = await fetch(
            '/devpanel/api/filemanager/preview.php?path=' + encodeURIComponent(path)
        );

        if (typeof checkAuth === 'function' && !checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showFilePreview(data.message || 'No se pudo previsualizar');
            return;
        }

        renderFilePreview(data);
    }
    catch(error)
    {
        console.error(error);
        showFilePreview('Error cargando preview');
    }
}

function renderFilePreview(data)
{
    activePreviewPath = data.path;
    document.getElementById('filePreviewTitle').textContent = data.name;
    document.getElementById('filePreviewMeta').textContent = `${data.mime} · ${formatFileManagerBytes(data.size)}`;

    const body = document.getElementById('filePreviewBody');
    const saveButton = document.getElementById('filePreviewSave');
    body.innerHTML = '';
    saveButton.hidden = true;

    if (data.mode === 'image') {
        const image = document.createElement('img');
        image.className = 'file-preview-image';
        image.src = data.url;
        image.alt = data.name;
        body.appendChild(image);
        return;
    }

    if (data.mode === 'text') {
        const editor = document.createElement('textarea');
        editor.id = 'filePreviewEditor';
        editor.className = 'file-preview-editor';
        editor.spellcheck = false;
        editor.value = data.content || '';
        body.appendChild(editor);
        saveButton.hidden = false;
        return;
    }

    showFilePreview(data.message || 'Preview no disponible');
}

async function saveFilePreview()
{
    const editor = document.getElementById('filePreviewEditor');

    if (!activePreviewPath || !editor) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', activePreviewPath);
    formData.append('content', editor.value);
    formData.append('csrf_token', getFileManagerCsrfToken());

    await postFileManagerAction('/devpanel/api/filemanager/save.php', formData, false);
    previewFile(activePreviewPath);
}

function downloadFileManagerItem(path)
{
    window.location.href = '/devpanel/api/filemanager/download.php?path=' + encodeURIComponent(path);
}

async function createFileManagerFolder()
{
    const name = prompt('Nombre de la carpeta');

    if (!name) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', currentFileManagerPath);
    formData.append('name', name);
    formData.append('csrf_token', getFileManagerCsrfToken());

    await postFileManagerAction('/devpanel/api/filemanager/mkdir.php', formData);
}

async function renameFileManagerItem(path, currentName)
{
    const name = prompt('Nuevo nombre', currentName);

    if (!name || name === currentName) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('name', name);
    formData.append('csrf_token', getFileManagerCsrfToken());

    await postFileManagerAction('/devpanel/api/filemanager/rename.php', formData);
}

async function deleteFileManagerItem(path, name)
{
    if (!confirm(`¿Borrar "${name}"?`)) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('path', path);
    formData.append('csrf_token', getFileManagerCsrfToken());

    await postFileManagerAction('/devpanel/api/filemanager/delete.php', formData);
}

async function uploadFileManagerFile(file)
{
    const formData = new FormData();
    formData.append('path', currentFileManagerPath);
    formData.append('csrf_token', getFileManagerCsrfToken());
    formData.append('file', file);

    await postFileManagerAction('/devpanel/api/filemanager/upload.php', formData);
}

async function postFileManagerAction(url, body, reload = true)
{
    try
    {
        const response = await fetch(url, {
            method: 'POST',
            body
        });

        if (typeof checkAuth === 'function' && !checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'No se pudo completar la acción');
            return;
        }

        if (reload) {
            loadFileManager(currentFileManagerPath);
        }
    }
    catch(error)
    {
        console.error(error);
        alert('Error ejecutando acción');
    }
}

function showFileManagerMessage(message)
{
    const container = document.getElementById('fileManagerContent');

    if (container) {
        container.textContent = message;
    }
}

function showFilePreview(message)
{
    const body = document.getElementById('filePreviewBody');
    const saveButton = document.getElementById('filePreviewSave');

    if (saveButton) {
        saveButton.hidden = true;
    }

    if (body) {
        body.textContent = message;
    }
}

function formatFileManagerBytes(bytes)
{
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = Number(bytes) || 0;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

document.addEventListener('DOMContentLoaded', () =>
{
    if (!document.getElementById('fileManagerContent')) {
        return;
    }

    const uploadInput = document.getElementById('fileManagerUpload');

    if (uploadInput) {
        uploadInput.addEventListener('change', () => {
            if (uploadInput.files.length) {
                uploadFileManagerFile(uploadInput.files[0]);
                uploadInput.value = '';
            }
        });
    }

    loadFileManager();
});
