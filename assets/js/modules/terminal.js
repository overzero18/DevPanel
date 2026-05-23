let terminalCurrentCommand = '';
let terminalHistory = [];
let terminalHistoryIndex = -1;
let term = null;
let terminalLastOutput = '';
const terminalFavoriteCommands = ['pwd', 'ls', 'git status', 'git branch', 'php -v', 'composer install', 'npm install', 'npm run build', 'npm test'];
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

    if (typeof Terminal === 'undefined') {
        terminalElement.textContent = 'No se pudo cargar xterm.js';
        return;
    }

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
    resizeTerminal();

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

    window.addEventListener('resize', resizeTerminal);
}

function resizeTerminal()
{
    const terminalElement = document.getElementById('terminal');

    if (!term || !terminalElement || typeof term.resize !== 'function') {
        return;
    }

    requestAnimationFrame(() => {
        try {
            const width = Math.max(terminalElement.clientWidth - 24, 320);
            const height = Math.max(terminalElement.clientHeight - 24, 220);
            const columns = Math.max(40, Math.floor(width / 9));
            const rows = Math.max(10, Math.floor(height / 18));

            term.resize(columns, rows);
        }
        catch(error) {
            // Si el contenedor aun no tiene medidas, xterm funcionara con sus columnas por defecto.
        }
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

        const workingDirectory = document.getElementById('terminalWorkingDirectory')?.value || '';
        if (workingDirectory) {
            formData.append('cwd', workingDirectory);
        }

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

        if (data.success === false && typeof showToast === 'function') {
            showToast(`Comando terminó con código ${data.exit_code ?? 1}`, 'warning');
        }
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

function openProjectTerminal(path)
{
    const select = document.getElementById('terminalWorkingDirectory');

    if (select) {
        select.value = path;
    }

    document.getElementById('terminal')?.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    runQuickCommand('pwd');
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

    terminalLastOutput = String(output);

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

async function copyTerminalOutput()
{
    if (!terminalLastOutput) {
        if (typeof showToast === 'function') {
            showToast('No hay salida para copiar', 'warning');
        }
        return;
    }

    try {
        await navigator.clipboard.writeText(terminalLastOutput);
        if (typeof showToast === 'function') {
            showToast('Salida copiada', 'success');
        }
    }
    catch(error) {
        if (typeof showToast === 'function') {
            showToast('No se pudo copiar la salida', 'danger');
        }
    }
}

function clearTerminal()
{
    term.clear();

    terminalCurrentCommand = '';
    terminalLastOutput = '';
    writeTerminalPrompt();
}
