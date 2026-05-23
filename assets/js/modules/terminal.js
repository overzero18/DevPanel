let terminalCurrentCommand = '';
let terminalHistory = [];
let terminalHistoryIndex = -1;
let term = null;
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
