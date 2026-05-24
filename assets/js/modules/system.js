async function loadSystemStats()
{
    try
    {
        const response = await fetch(
            '/devpanel/api/system_stats.php'
        );

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success)
        {
            return;
        }

        const cpu = data.cpu_metrics || {};
        const ram = data.ram || {};
        const disk = data.disk || {};

        setText('cpuLoad', `${cpu.percent ?? data.cpu ?? '--'}%`);
        setText(
            'cpuDetail',
            `Carga ${cpu.load_1 ?? data.cpu ?? '--'} · ${cpu.cores ?? '--'} cores`
        );
        setProgress('cpuBar', cpu.percent);

        setText('ramUsage', `${ram.percent ?? '--'}%`);
        setText(
            'ramDetail',
            `${ram.used ?? '--'} MB usados de ${ram.total ?? '--'} MB`
        );
        setProgress('ramBar', ram.percent);

        setText('diskUsage', `${disk.percent ?? '--'}%`);
        setText(
            'diskDetail',
            `${disk.free ?? '--'} GB libres de ${disk.total ?? '--'} GB`
        );
        setProgress('diskBar', disk.percent);

        setText('hostname', data.hostname || '--');
        setText('uptime', data.uptime || '--');
        renderProcesses(data.processes || []);
    }
    catch(error)
    {
        console.error(error);
    }
}

function setText(id, value)
{
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function setProgress(id, value)
{
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    const percent = Number(value);
    element.style.width = `${Number.isFinite(percent) ? Math.max(0, Math.min(100, percent)) : 0}%`;
}

function renderProcesses(processes)
{
    const container = document.getElementById('processList');

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!processes.length) {
        const empty = document.createElement('div');
        empty.className = 'process-row process-row-empty';
        empty.textContent = 'Sin datos de procesos';
        container.appendChild(empty);
        return;
    }

    processes.forEach(process => {
        const row = document.createElement('div');
        row.className = 'process-row';

        const identity = document.createElement('div');
        identity.className = 'process-identity';

        const name = document.createElement('strong');
        name.textContent = process.name || 'proceso';

        const pid = document.createElement('span');
        pid.textContent = `PID ${process.pid || '--'}`;

        identity.appendChild(name);
        identity.appendChild(pid);

        const metrics = document.createElement('div');
        metrics.className = 'process-metrics';

        const cpu = document.createElement('span');
        cpu.textContent = `CPU ${process.cpu ?? 0}%`;

        const memory = document.createElement('span');
        memory.textContent = `RAM ${process.memory ?? 0}%`;

        metrics.appendChild(cpu);
        metrics.appendChild(memory);

        row.appendChild(identity);
        row.appendChild(metrics);
        container.appendChild(row);
    });
}

document.addEventListener("DOMContentLoaded", () =>
{
    applyDashboardWidgetPreferences();

    if (document.getElementById('cpuLoad')) {
        loadOnboardingChecklist();
        loadSystemStats();
        loadSystemHealth();
        setTimeout(loadSystemHealth, 350);

        setInterval(loadSystemStats, 5000);
    }

    if (document.getElementById('security-settings')) {
        loadSecuritySettings();
        setupDashboardWidgetSettings();
        setupThemeCustomizer();
        setupTemplateImportPreview();
        loadProjectTemplatesMarketplace();
        loadThemeMarketplace();
    }
});

function dashboardWidgetStorageKey()
{
    return 'devpanel_dashboard_widgets';
}

async function loadOnboardingChecklist()
{
    const section = document.getElementById('onboarding-section');
    const container = document.getElementById('onboardingChecklist');

    if (!section || !container) return;

    if (localStorage.getItem('devpanel_onboarding_hidden') === '1') {
        section.hidden = true;
        return;
    }

    try {
        const [permissionsResponse, logsResponse, usersResponse, backupsResponse] = await Promise.all([
            fetch('/devpanel/api/permissions.php'),
            fetch('/devpanel/api/logs/summary.php'),
            fetch('/devpanel/api/users/list.php'),
            fetch('/devpanel/api/backups/list.php')
        ]);

        const permissions = await permissionsResponse.json();
        const logs = await logsResponse.json();
        const users = await usersResponse.json();
        const backups = await backupsResponse.json();
        const permissionProblems = (permissions.items || permissions.permissions || []).filter(item => item.ok === false).length;
        const checks = [
            ['Config y permisos', permissionProblems === 0, permissionProblems ? `${permissionProblems} rutas a revisar` : 'Correcto', '/devpanel/settings.php'],
            ['Usuarios/roles', (users.users || []).length > 0, `${(users.users || []).length} usuarios`, '/devpanel/users.php'],
            ['Backups', (backups.backups || []).length > 0, `${(backups.backups || []).length} backups`, '#backups-manager'],
            ['Logs limpios', Number(logs.summary?.danger || 0) === 0, `${logs.summary?.danger || 0} errores recientes`, '#logs-section'],
            ['Doctor revisado', true, 'Disponible', '/devpanel/doctor.php'],
        ];
        const completed = checks.filter(check => check[1]).length;
        const percent = Math.round((completed / checks.length) * 100);

        document.getElementById('onboardingSummary').textContent = `${completed}/${checks.length} pasos listos`;
        document.getElementById('onboardingProgressBar').style.width = `${percent}%`;
        container.innerHTML = checks.map(([label, ok, detail, href]) => `
            <a class="onboarding-item ${ok ? 'is-ok' : 'is-warning'}" href="${href}">
                <i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                <span>${escapeSystemHtml(label)}</span>
                <small>${escapeSystemHtml(detail)}</small>
            </a>
        `).join('');
    }
    catch(error) {
        console.error(error);
        container.textContent = 'No se pudo cargar el onboarding';
    }
}

function dismissOnboarding()
{
    localStorage.setItem('devpanel_onboarding_hidden', '1');
    const section = document.getElementById('onboarding-section');
    if (section) section.hidden = true;
}

function dashboardTourSteps()
{
    return [
        ['#systemHealthGrid', 'Estado global', 'Comprueba servicios, permisos, terminal, Git y logs antes de trabajar.'],
        ['#onboarding-section', 'Primeros pasos', 'Checklist rápido para terminar la instalación y mantener el panel sano.'],
        ['#projects', 'Proyectos', 'Gestiona proyectos detectados, abre terminal, archivos y acciones por proyecto.'],
        ['#backups-manager', 'Backups', 'Crea, programa, compara, versiona y restaura backups por archivo.'],
        ['#docker-manager', 'Docker', 'Revisa Docker, Compose, servicios y edita compose con validación.'],
        ['#logs-section', 'Logs inteligentes', 'Agrupa errores por categoría, proyecto y sugerencias de causa probable.'],
        ['#terminal-section', 'Terminal', 'Ejecuta comandos seguros desde el proyecto seleccionado.'],
    ];
}

function startDashboardTour(index = 0)
{
    const steps = dashboardTourSteps();
    const step = steps[index];

    if (!step) {
        localStorage.setItem('devpanel_tour_done', '1');
        showToast('Tour completado', 'success');
        return;
    }

    const [selector, title, text] = step;
    const target = document.querySelector(selector);

    if (!target) {
        startDashboardTour(index + 1);
        return;
    }

    target.scrollIntoView({behavior: 'smooth', block: 'center'});
    target.classList.add('tour-highlight');

    appConfirm(text, {
        title: `${index + 1}/${steps.length} · ${title}`,
        confirmText: index === steps.length - 1 ? 'Terminar' : 'Siguiente',
        cancelText: 'Cerrar'
    }).then(confirmed => {
        target.classList.remove('tour-highlight');
        if (confirmed) startDashboardTour(index + 1);
    });
}

function getDashboardWidgetPreferences()
{
    try {
        return JSON.parse(localStorage.getItem(dashboardWidgetStorageKey()) || '{}');
    }
    catch(error) {
        return {};
    }
}

function applyDashboardWidgetPreferences()
{
    const preferences = getDashboardWidgetPreferences();

    Object.entries(preferences).forEach(([id, visible]) => {
        const element = document.getElementById(id);
        if (element) element.hidden = visible === false;
    });
}

function setupDashboardWidgetSettings()
{
    const container = document.getElementById('dashboardWidgetSettings');

    if (!container) return;

    const preferences = getDashboardWidgetPreferences();

    container.querySelectorAll('input[type="checkbox"]').forEach(input => {
        input.checked = preferences[input.value] !== false;
        input.addEventListener('change', () => {
            const next = getDashboardWidgetPreferences();
            next[input.value] = input.checked;
            localStorage.setItem(dashboardWidgetStorageKey(), JSON.stringify(next));
            applyDashboardWidgetPreferences();
        });
    });
}

async function loadProjectTemplatesMarketplace()
{
    const container = document.getElementById('projectTemplateMarketplace');

    if (!container) return;

    try {
        const response = await fetch('/devpanel/api/templates/list.php');

        if (!checkAuth(response)) return;

        const data = await response.json();
        const templates = Object.entries(data.templates || {});
        container.innerHTML = '';

        templates.forEach(([key, template]) => {
            const row = document.createElement('div');
            row.className = 'database-row';
            row.innerHTML = `
                <div class="database-info">
                    <i class="bi bi-box-seam"></i>
                    <div>
                        <strong>${escapeSystemHtml(template.label || key)}</strong>
                        <small>${escapeSystemHtml(template.description || '')}${template.custom ? ' · personalizada' : ' · base'}</small>
                    </div>
                </div>
                <div class="database-actions">
                    ${template.custom ? `<a class="btn btn-sm btn-outline-info" href="/devpanel/api/templates/export.php?key=${encodeURIComponent(key)}">Exportar</a>` : ''}
                </div>
            `;
            container.appendChild(row);
        });

        if (!templates.length) {
            container.innerHTML = '<div class="file-manager-empty">Sin plantillas.</div>';
        }
    }
    catch(error) {
        console.error(error);
        container.textContent = 'Error cargando plantillas';
    }
}

async function importProjectTemplateFromFile()
{
    const input = document.getElementById('templateImportFile');
    const file = input?.files?.[0];

    if (!file) {
        showToast('Selecciona un JSON de plantilla', 'danger');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('template', await file.text());
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/templates/import.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Plantilla importada', data.success ? 'success' : 'danger');
        if (data.success) {
            input.value = '';
            loadProjectTemplatesMarketplace();
        }
    }
    catch(error) {
        console.error(error);
        showToast('Error importando plantilla', 'danger');
    }
}

function setupTemplateImportPreview()
{
    const input = document.getElementById('templateImportFile');

    if (!input) return;

    input.addEventListener('change', previewProjectTemplateFile);
}

async function previewProjectTemplateFile()
{
    const input = document.getElementById('templateImportFile');
    const container = document.getElementById('templateImportPreview');
    const file = input?.files?.[0];

    if (!container) return;

    if (!file) {
        container.innerHTML = '<div class="file-manager-empty">Selecciona una plantilla para ver su contenido antes de importarla.</div>';
        return;
    }

    try {
        const data = JSON.parse(await file.text());
        const files = Object.entries(data.files || {});

        container.innerHTML = `
            <div class="template-preview-header">
                <strong>${escapeSystemHtml(data.label || data.key || file.name)}</strong>
                <small>${escapeSystemHtml(data.description || 'Sin descripción')}</small>
            </div>
            <div class="template-preview-files">
                ${files.slice(0, 20).map(([path, content]) => `
                    <div class="template-preview-file">
                        <span>${escapeSystemHtml(path)}</span>
                        <small>${String(content || '').length} caracteres</small>
                    </div>
                `).join('')}
                ${files.length > 20 ? `<div class="file-manager-empty">${files.length - 20} archivos más</div>` : ''}
            </div>
        `;
    }
    catch(error) {
        container.innerHTML = '<div class="file-manager-empty">JSON inválido.</div>';
    }
}

function escapeSystemHtml(value)
{
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function themeCustomizerStorageKey()
{
    return 'devpanel_theme_customizer';
}

function getThemeCustomizerSettings()
{
    try {
        return JSON.parse(localStorage.getItem(themeCustomizerStorageKey()) || '{}');
    }
    catch(error) {
        return {};
    }
}

function applyThemeCustomizer()
{
    const settings = getThemeCustomizerSettings();
    const root = document.documentElement;

    if (settings.primary) root.style.setProperty('--accent-primary', settings.primary);
    if (settings.secondary) root.style.setProperty('--accent-secondary', settings.secondary);
    if (settings.sidebarWidth) root.style.setProperty('--sidebar-width', `${settings.sidebarWidth}px`);
    document.body.classList.toggle('density-compact', settings.density === 'compact');
}

function setupThemeCustomizer()
{
    const primary = document.getElementById('themeAccentPrimary');

    if (!primary) return;

    const secondary = document.getElementById('themeAccentSecondary');
    const density = document.getElementById('themeDensity');
    const sidebar = document.getElementById('themeSidebarWidth');
    const quickPreset = document.getElementById('themeQuickPreset');
    const settings = getThemeCustomizerSettings();

    primary.value = settings.primary || primary.value;
    secondary.value = settings.secondary || secondary.value;
    density.value = settings.density || density.value;
    sidebar.value = settings.sidebarWidth || sidebar.value;

    [primary, secondary, density, sidebar].forEach(input => {
        input.addEventListener('input', () => {
            localStorage.setItem(themeCustomizerStorageKey(), JSON.stringify({
                primary: primary.value,
                secondary: secondary.value,
                density: density.value,
                sidebarWidth: sidebar.value,
            }));
            applyThemeCustomizer();
        });
    });

    if (quickPreset) {
        quickPreset.addEventListener('change', () => {
            const presets = {
                ocean: {primary: '#0ea5e9', secondary: '#14b8a6', density: 'comfortable', sidebarWidth: 260},
                forest: {primary: '#22c55e', secondary: '#84cc16', density: 'comfortable', sidebarWidth: 250},
                mono: {primary: '#94a3b8', secondary: '#e2e8f0', density: 'compact', sidebarWidth: 240},
            };
            const preset = presets[quickPreset.value];

            if (!preset) return;

            localStorage.setItem(themeCustomizerStorageKey(), JSON.stringify(preset));
            setupThemeCustomizer();
            applyThemeCustomizer();
        });
    }

    applyThemeCustomizer();
}

function resetThemeCustomizer()
{
    localStorage.removeItem(themeCustomizerStorageKey());
    document.documentElement.style.removeProperty('--accent-primary');
    document.documentElement.style.removeProperty('--accent-secondary');
    document.documentElement.style.removeProperty('--sidebar-width');
    document.body.classList.remove('density-compact');
    setupThemeCustomizer();
    showToast('Personalización reiniciada', 'success');
}

function exportThemePreset()
{
    const settings = getThemeCustomizerSettings();
    const blob = new Blob([JSON.stringify({
        type: 'devpanel-theme-preset',
        version: 1,
        settings
    }, null, 2)], {type: 'application/json'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `devpanel-theme-preset-${Date.now()}.json`;
    link.click();
    URL.revokeObjectURL(link.href);
}

async function loadThemeMarketplace()
{
    const container = document.getElementById('themeMarketplaceList');

    if (!container) return;

    try {
        const response = await fetch('/devpanel/api/themes/marketplace.php');

        if (!checkAuth(response)) return;

        const data = await response.json();
        const presets = data.presets || [];
        container.innerHTML = '';

        if (!presets.length) {
            container.innerHTML = '<div class="file-manager-empty">Sin presets compartibles.</div>';
            return;
        }

        presets.forEach(preset => {
            const row = document.createElement('div');
            row.className = 'database-row';
            const info = document.createElement('div');
            info.className = 'database-info';
            info.innerHTML = `
                <i class="bi bi-palette-fill"></i>
                <div>
                    <strong>${escapeSystemHtml(preset.name || 'Preset')}</strong>
                    <small>${escapeSystemHtml(preset.description || '')}</small>
                </div>
            `;
            const actions = document.createElement('div');
            actions.className = 'database-actions';

            const apply = document.createElement('button');
            apply.type = 'button';
            apply.className = 'btn btn-sm btn-outline-info';
            apply.textContent = 'Aplicar';
            apply.addEventListener('click', () => applySharedThemePreset(preset.settings || {}));

            const copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'btn btn-sm btn-outline-secondary';
            copy.textContent = 'Copiar JSON';
            copy.addEventListener('click', () => copySharedThemePreset(preset));

            actions.appendChild(apply);
            actions.appendChild(copy);
            row.appendChild(info);
            row.appendChild(actions);
            container.appendChild(row);
        });
    }
    catch(error) {
        console.error(error);
        container.textContent = 'Error cargando marketplace de temas';
    }
}

function normalizeThemePresetSettings(settings)
{
    return {
        primary: /^#[0-9a-fA-F]{6}$/.test(settings.primary || '') ? settings.primary : '#4f9ef9',
        secondary: /^#[0-9a-fA-F]{6}$/.test(settings.secondary || '') ? settings.secondary : '#10d981',
        density: settings.density === 'compact' ? 'compact' : 'comfortable',
        sidebarWidth: Math.max(220, Math.min(320, Number(settings.sidebarWidth || 260))),
    };
}

function applySharedThemePreset(settings)
{
    localStorage.setItem(themeCustomizerStorageKey(), JSON.stringify(normalizeThemePresetSettings(settings)));
    setupThemeCustomizer();
    applyThemeCustomizer();
    showToast('Tema aplicado', 'success');
}

async function copySharedThemePreset(preset)
{
    const payload = JSON.stringify({
        type: 'devpanel-theme-preset',
        version: 1,
        name: preset.name || 'Preset',
        settings: normalizeThemePresetSettings(preset.settings || {})
    }, null, 2);

    try {
        await navigator.clipboard.writeText(payload);
        showToast('Preset copiado', 'success');
    }
    catch(error) {
        await appConfirm(payload, {title: 'Preset JSON', confirmText: 'Cerrar'});
    }
}

async function importThemePreset()
{
    const input = document.getElementById('themePresetImportFile');
    const file = input?.files?.[0];

    if (!file) {
        showToast('Selecciona un preset JSON', 'danger');
        return;
    }

    try {
        const data = JSON.parse(await file.text());
        const settings = data.settings || data;
        const next = normalizeThemePresetSettings(settings);

        localStorage.setItem(themeCustomizerStorageKey(), JSON.stringify(next));
        input.value = '';
        setupThemeCustomizer();
        applyThemeCustomizer();
        showToast('Preset aplicado', 'success');
    }
    catch(error) {
        console.error(error);
        showToast('Preset inválido', 'danger');
    }
}

applyThemeCustomizer();

async function loadSystemHealth()
{
    const grid = document.getElementById('systemHealthGrid');
    const summary = document.getElementById('systemHealthSummary');

    if (!grid) {
        return;
    }

    const checks = [
        checkHealthEndpoint('permissions', '/devpanel/api/permissions.php', data => {
            const items = data.items || data.permissions || [];
            const problems = Array.isArray(items)
                ? items.filter(item => item.ok === false).length
                : 0;

            return problems === 0
                ? ['is-ok', 'OK']
                : ['is-warning', `${problems} avisos`];
        }),
        checkHealthEndpoint('terminal', '/devpanel/api/terminal.php', null, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                command: 'pwd',
                csrf_token: getSystemCsrfToken()
            })
        }),
        checkHealthEndpoint('git', '/devpanel/api/terminal.php', data => {
            return data.success ? ['is-ok', 'OK'] : ['is-warning', 'Revisar'];
        }, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                command: 'git status',
                csrf_token: getSystemCsrfToken()
            })
        }),
        checkHealthEndpoint('logs', '/devpanel/api/logs/insights.php', data => {
            const danger = Number(data.summary?.danger || 0);
            const warning = Number(data.summary?.warning || 0);

            if (danger > 0) return ['is-danger', `${danger} errores`];
            if (warning > 0) return ['is-warning', `${warning} avisos`];
            return ['is-ok', 'OK'];
        })
    ];

    const results = await Promise.all(checks);
    const failed = results.filter(result => result !== 'is-ok').length;

    if (summary) {
        summary.textContent = failed === 0
            ? 'Todo lo crítico responde correctamente.'
            : `${failed} módulos necesitan revisión.`;
    }
}

async function checkHealthEndpoint(key, url, mapper = null, options = {})
{
    const element = document.querySelector(`[data-health-check="${key}"]`);

    try {
        const response = await fetch(url, options);

        if (!checkAuth(response)) {
            return 'is-danger';
        }

        if (!response.ok) {
            updateHealthItem(element, 'is-warning', 'Sin permiso');
            return 'is-warning';
        }

        const data = await response.json();
        const [state, label] = mapper
            ? mapper(data)
            : (data.success ? ['is-ok', 'OK'] : ['is-warning', 'Revisar']);

        updateHealthItem(element, state, label);
        return state;
    }
    catch(error) {
        updateHealthItem(element, 'is-danger', 'Error');
        return 'is-danger';
    }
}

function getSystemCsrfToken()
{
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : (typeof csrfToken === 'string' ? csrfToken : '');
}

function updateHealthItem(element, state, label)
{
    if (!element) {
        return;
    }

    element.classList.remove('is-pending', 'is-ok', 'is-warning', 'is-danger');
    element.classList.add(state);

    const value = element.querySelector('strong');
    if (value) {
        value.textContent = label;
    }
}

async function loadSecuritySettings()
{
    const list = document.getElementById('apiTokenList');

    if (!list) {
        return;
    }

    try {
        const response = await fetch('/devpanel/api/security/settings.php');

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo cargar seguridad', 'danger');
            return;
        }

        renderTwoFactor(data.two_factor || {});
        renderApiTokens(data.api_tokens || []);
    }
    catch(error) {
        console.error(error);
        showToast('Error cargando seguridad', 'danger');
    }
}

function renderTwoFactor(twoFactor)
{
    const status = document.getElementById('twoFactorStatus');
    const button = document.getElementById('twoFactorToggle');
    const panel = document.getElementById('twoFactorSecretPanel');
    const secret = document.getElementById('twoFactorSecret');
    const uri = document.getElementById('twoFactorUri');
    const qr = document.getElementById('twoFactorQr');
    const enabled = Boolean(twoFactor.enabled);

    if (status) {
        status.textContent = enabled
            ? 'Activo. El login pedirá código TOTP.'
            : 'Inactivo. El login usa solo contraseña.';
    }

    if (button) {
        button.textContent = enabled ? 'Desactivar' : 'Activar';
        button.dataset.enabled = enabled ? '1' : '0';
        button.className = enabled ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-outline-info';
    }

    if (panel) {
        panel.hidden = !enabled;
    }

    if (secret) {
        secret.value = twoFactor.secret || '';
    }

    if (uri) {
        const issuer = encodeURIComponent(twoFactor.issuer || 'DevPanel');
        const account = encodeURIComponent(twoFactor.account || 'admin');
        uri.value = twoFactor.secret
            ? `otpauth://totp/${issuer}:${account}?secret=${encodeURIComponent(twoFactor.secret)}&issuer=${issuer}`
            : '';
    }

    if (qr) {
        qr.src = enabled
            ? `/devpanel/api/security/two_factor_qr.php?v=${Date.now()}`
            : '';
    }
}

async function toggleTwoFactor()
{
    const button = document.getElementById('twoFactorToggle');
    const currentlyEnabled = button?.dataset.enabled === '1';
    const enabled = currentlyEnabled ? '0' : '1';
    const confirmed = await appConfirm(
        enabled === '1'
            ? 'Se activará 2FA. Guarda el secret en tu app autenticadora antes de cerrar sesión.'
            : 'Se desactivará 2FA para el login.',
        {
            title: enabled === '1' ? 'Activar 2FA' : 'Desactivar 2FA',
            confirmText: enabled === '1' ? 'Activar' : 'Desactivar',
            cancelText: 'Cancelar',
            danger: enabled !== '1'
        }
    );

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('enabled', enabled);
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/security/two_factor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Seguridad actualizada', data.success ? 'success' : 'danger');

        if (data.success) {
            renderTwoFactor(data.two_factor || {});
        }
    }
    catch(error) {
        console.error(error);
        showToast('Error guardando 2FA', 'danger');
    }
}

function renderApiTokens(tokens)
{
    const container = document.getElementById('apiTokenList');

    if (!container) return;

    container.innerHTML = '';

    if (!tokens.length) {
        const empty = document.createElement('div');
        empty.className = 'file-manager-empty';
        empty.textContent = 'Sin tokens creados.';
        container.appendChild(empty);
        return;
    }

    tokens.forEach(token => {
        const row = document.createElement('div');
        row.className = 'database-row';

        const info = document.createElement('div');
        info.className = 'database-info';
        const icon = document.createElement('i');
        icon.className = 'bi bi-key-fill';
        const text = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = token.name || 'API token';
        const meta = document.createElement('small');
        meta.textContent = `${token.prefix || 'dp_'}... · ${token.role || 'viewer'} · expira ${token.expires_at || '--'} · último uso ${token.last_used_at || '--'}${token.expired ? ' · expirado' : ''}`;
        text.appendChild(name);
        text.appendChild(meta);
        info.appendChild(icon);
        info.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'database-actions';
        const rotate = document.createElement('button');
        rotate.type = 'button';
        rotate.className = 'btn btn-sm btn-outline-warning';
        rotate.textContent = 'Rotar';
        rotate.addEventListener('click', () => rotateApiToken(token.id));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.textContent = 'Borrar';
        remove.addEventListener('click', () => deleteApiToken(token.id));
        actions.appendChild(rotate);
        actions.appendChild(remove);

        row.appendChild(info);
        row.appendChild(actions);
        container.appendChild(row);
    });
}

async function createApiToken()
{
    const name = document.getElementById('apiTokenName')?.value || 'API token';
    const role = document.getElementById('apiTokenRole')?.value || 'viewer';
    const expiresDays = document.getElementById('apiTokenExpiry')?.value || '30';
    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('role', role);
    formData.append('expires_days', expiresDays);
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/tokens/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo crear token', 'danger');
            return;
        }

        await appConfirm(`Guarda este token ahora. No volverá a mostrarse:\n\n${data.token}`, {
            title: 'API token creado',
            confirmText: 'Cerrar'
        });

        document.getElementById('apiTokenName').value = '';
        loadSecuritySettings();
    }
    catch(error) {
        console.error(error);
        showToast('Error creando token', 'danger');
    }
}

async function rotateApiToken(id)
{
    const confirmed = await appConfirm('El valor anterior dejará de funcionar y se mostrará un token nuevo una sola vez.', {
        title: 'Rotar API token',
        confirmText: 'Rotar',
        cancelText: 'Cancelar',
        danger: true
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/tokens/rotate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();

        if (!data.success) {
            showToast(data.message || 'No se pudo rotar token', 'danger');
            return;
        }

        await appConfirm(`Guarda este token nuevo ahora. No volverá a mostrarse:\n\n${data.token}`, {
            title: 'API token rotado',
            confirmText: 'Cerrar'
        });

        loadSecuritySettings();
    }
    catch(error) {
        console.error(error);
        showToast('Error rotando token', 'danger');
    }
}

async function deleteApiToken(id)
{
    const confirmed = await appConfirm('Este token dejará de funcionar inmediatamente.', {
        title: 'Borrar API token',
        confirmText: 'Borrar',
        cancelText: 'Cancelar',
        danger: true
    });

    if (!confirmed) return;

    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/tokens/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Token actualizado', data.success ? 'success' : 'danger');
        if (data.success) loadSecuritySettings();
    }
    catch(error) {
        console.error(error);
        showToast('Error borrando token', 'danger');
    }
}

function exportPublicConfig()
{
    window.location.href = '/devpanel/api/config/export.php';
}

async function importPublicConfig()
{
    const input = document.getElementById('configImportFile');
    const file = input?.files?.[0];

    if (!file) {
        showToast('Selecciona un JSON de configuración', 'warning');
        return;
    }

    const confirmed = await appConfirm('Se importarán rutas, URLs, temas y roles. No se importan contraseñas ni tokens.', {
        title: 'Importar configuración',
        confirmText: 'Importar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    const formData = new FormData();
    formData.append('config', file);
    formData.append('csrf_token', getSystemCsrfToken());

    try {
        const response = await fetch('/devpanel/api/config/import.php', {
            method: 'POST',
            body: formData
        });

        if (!checkAuth(response)) return;

        const data = await response.json();
        showToast(data.message || 'Configuración importada', data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => window.location.reload(), 700);
    }
    catch(error) {
        console.error(error);
        showToast('Error importando configuración', 'danger');
    }
}
