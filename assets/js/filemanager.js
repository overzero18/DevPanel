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

        const rowContent =
            document.createElement('div');

        rowContent.className =
            'd-flex justify-content-between align-items-center border-bottom py-2';

        const itemButton =
            document.createElement('button');

        itemButton.type =
            'button';

        itemButton.className =
            'btn btn-link p-0 text-start';

        itemButton.textContent =
            `${item.type === 'folder' ? '📁' : '📄'} ${item.name}`;

        itemButton.addEventListener('click', () =>
        {
            handleFileManagerItemClick(item.path, item.type);
        });

        const modified =
            document.createElement('small');

        modified.className =
            'text-muted';

        modified.textContent =
            item.modified;

        rowContent.appendChild(itemButton);
        rowContent.appendChild(modified);
        row.appendChild(rowContent);

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
