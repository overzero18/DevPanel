<!-- Terminal -->
<div class="row mt-5">

    <div class="col-12">

        <div class="dashboard-card terminal-card">

            <div class="terminal-header">

                <div>
                    <h4 class="mb-1">Terminal Linux</h4>
                    <p class="text-secondary mb-0">Comandos seguros, historial y favoritos.</p>
                </div>

                <div class="terminal-actions">
                    <select id="terminalWorkingDirectory" class="form-select terminal-project-select">
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project['path'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (!$projects): ?>
                            <option value="<?php echo htmlspecialchars(dirname(__DIR__, 2), ENT_QUOTES, 'UTF-8'); ?>">DevPanel</option>
                        <?php endif; ?>
                    </select>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('pwd')">pwd</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('ls')">ls</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('git status')">git</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('php -v')">php</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('composer install')">composer</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="runQuickCommand('npm run build')">build</button>
                    <button type="button" class="btn btn-outline-info" onclick="copyTerminalOutput()">
                        Copiar
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearTerminal()">
                        Limpiar
                    </button>
                </div>

            </div>

            <div class="terminal-favorites" id="terminalFavorites"></div>

            <div class="terminal-layout">
                <div id="terminal" class="devpanel-terminal-shell"></div>

                <aside class="terminal-history-panel">
                    <div class="terminal-history-header">
                        <h5 class="mb-0">Historial</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearTerminalHistory()">
                            Limpiar
                        </button>
                    </div>
                    <div id="terminalHistory" class="terminal-history-list"></div>
                </aside>
            </div>

        </div>

    </div>

</div>
