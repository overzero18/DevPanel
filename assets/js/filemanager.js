let currentFileManagerPath = '/opt/lampp/htdocs';

async function loadFileManager(path = currentFileManagerPath)
{
    try
    {
        const response = await fetch(

            '/devpanel/api/filemanager/list.php?path=' +
            encodeURIComponent(path)

        );

        const data = await response.json();

        if (!data.success)
        {
            alert(data.message);

            return;
        }

        currentFileManagerPath =
            data.currentPath;

        renderFileManager(data);
    }
    catch(error)
    {
        console.error(error);

        alert('Error cargando archivos');
    }
}

function renderFileManager(data)
{
    const container =
        document.getElementById('fileManagerContent');

    const currentPath =
        document.getElementById('fileManagerPath');

    currentPath.textContent =
        data.currentPath;

    container.innerHTML = '';

    data.items.forEach(item =>
    {
        const row =
            document.createElement('div');

        row.className =
            'filemanager-row';

        const icon =
            item.type === 'folder'
                ? '📁'
                : '📄';

        row.innerHTML = `

            <div class="d-flex justify-content-between align-items-center border-bottom py-2">

                <div
                    style="cursor:pointer;"
                    onclick="handleFileManagerItemClick(
                        '${item.path.replace(/'/g, "\\'")}',
                        '${item.type}'
                    )"
                >

                    ${icon} ${item.name}

                </div>

                <small class="text-muted">

                    ${item.modified}

                </small>

            </div>

        `;

        container.appendChild(row);
    });
}

function handleFileManagerItemClick(path, type)
{
    if (type === 'folder')
    {
        loadFileManager(path);
    }
}

document.addEventListener('DOMContentLoaded', () =>
{
    loadFileManager();
});