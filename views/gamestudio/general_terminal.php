<?php
/**
 * Vue Partielle MVC : Terminal Web & Console Système Game Studio
 * Fichier : views/gamestudio/general_terminal.php
 */

$sys = $terminalSysInfo ?? [];
$isPi = !empty($sys['os']) && (stripos($sys['os'], 'Raspberry') !== false || stripos($sys['os'], 'Debian') !== false);
?>

<div class="web-terminal-container">

    <!-- ── 1. EN-TÊTE PRINCIPAL ── -->
    <div class="card bg-white border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-lg rounded bg-success-lt text-success fs-1 shadow-sm">
                        <i class="fa-solid fa-terminal"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="text-dark fw-bold m-0" style="font-size: 1.4rem;">
                                Terminal Web &amp; Console Système
                            </h2>
                            <span class="badge bg-success-lt fw-bold px-2.5 py-1" id="terminalStatusBadge">
                                <i class="fa-solid fa-circle-check me-1"></i>En Ligne
                            </span>
                            <?php if ($isPi): ?>
                                <span class="badge bg-danger-lt fw-bold px-2.5 py-1">
                                    <i class="fa-brands fa-raspberry-pi me-1"></i>Raspberry Pi OS
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-secondary small mt-1">
                            Console d'administration sécurisée pour exécuter vos commandes système, déployer les commits Git et inspecter les migrations.
                        </div>
                    </div>
                </div>

                <!-- Actions Rapides En-Tête -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5" onclick="refreshTerminalSysinfo()" title="Actualiser les métriques système">
                        <i class="fa-solid fa-arrows-rotate" id="refreshSysinfoIcon"></i>
                        <span>Actualiser</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5" onclick="clearTerminalScreen()" title="Effacer l'historique d'écran">
                        <i class="fa-solid fa-eraser"></i>
                        <span>Effacer (clear)</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5" onclick="copyTerminalOutput()" title="Copier tout le contenu du terminal">
                        <i class="fa-solid fa-copy"></i>
                        <span id="copyTerminalBtnText">Copier</span>
                    </button>
                    <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" onclick="toggleTerminalFullscreen()" id="btnToggleFullscreen" title="Basculer en plein écran">
                        <i class="fa-solid fa-expand" id="fullscreenIcon"></i>
                        <span id="fullscreenText">Plein Écran</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. CARTES KPIS & MONITORING SYSTÈME ── -->
    <div class="row row-cards mb-4" id="terminalSysinfoCards">
        <!-- KPI 1 : OS & Machine -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-danger shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Système / Hôte</span>
                        <span class="avatar avatar-xs rounded bg-danger-lt text-danger">
                            <i class="fa-brands <?= $isPi ? 'fa-raspberry-pi' : 'fa-linux' ?>"></i>
                        </span>
                    </div>
                    <div class="fw-bold text-dark text-truncate" id="sys-os-title" title="<?= htmlspecialchars($sys['os'] ?? 'Linux') ?>">
                        <?= htmlspecialchars($sys['os'] ?? 'Système Hôte') ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex justify-content-between">
                        <span>Hôte : <strong><?= htmlspecialchars($sys['hostname'] ?? 'localhost') ?></strong></span>
                        <span class="badge bg-secondary-lt font-monospace"><?= htmlspecialchars($sys['arch'] ?? 'x86_64') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2 : Utilisateur & PHP -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-teal shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Exécution Web</span>
                        <span class="avatar avatar-xs rounded bg-teal-lt text-teal"><i class="fa-solid fa-user-gear"></i></span>
                    </div>
                    <div class="h3 m-0 font-monospace text-teal fw-bold" id="sys-user-name">
                        <?= htmlspecialchars($sys['user'] ?? 'www-data') ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex justify-content-between">
                        <span>PHP <?= htmlspecialchars(PHP_VERSION) ?></span>
                        <span class="badge bg-teal-lt font-monospace"><?= htmlspecialchars(PHP_SAPI) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3 : Espace Disque -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-indigo shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Espace Disque</span>
                        <span class="avatar avatar-xs rounded bg-indigo-lt text-indigo"><i class="fa-solid fa-hard-drive"></i></span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <span class="h3 m-0 font-monospace text-indigo fw-bold" id="sys-disk-free">
                            <?= htmlspecialchars($sys['disk_free'] ?? '0 Go') ?>
                        </span>
                        <span class="text-secondary small">libres / <?= htmlspecialchars($sys['disk_total'] ?? '0 Go') ?></span>
                    </div>
                    <div class="progress progress-xs mt-2" style="height: 4px;">
                        <div class="progress-bar bg-indigo" id="sys-disk-bar" style="width: <?= (int)($sys['disk_percent'] ?? 0) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4 : Mémoire Vive & Uptime -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Mémoire / Uptime</span>
                        <span class="avatar avatar-xs rounded bg-warning-lt text-warning"><i class="fa-solid fa-memory"></i></span>
                    </div>
                    <div class="h3 m-0 font-monospace text-dark fw-bold" id="sys-mem-free">
                        <?= htmlspecialchars($sys['mem_free'] ?? 'N/A') ?> <span class="small text-secondary fw-normal">dispo</span>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top text-truncate" title="Uptime serveur">
                        <i class="fa-regular fa-clock me-1 text-muted"></i>Uptime : <strong id="sys-uptime"><?= htmlspecialchars($sys['uptime'] ?? 'Inconnu') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. BOUTONS DE RACCOURCIS RAPIDES (1 CLIC) ── -->
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <span class="small text-secondary fw-bold text-uppercase d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-warning"></i>
                    <span>Raccourcis de commandes fréquentes (1 clic) :</span>
                </span>
                <span class="small text-muted font-monospace" id="terminalCurrentDirHint">
                    <i class="fa-regular fa-folder-open me-1 text-indigo"></i><?= htmlspecialchars($sys['cwd'] ?? '/var/www/html') ?>
                </span>
            </div>

            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                <!-- Groupe Git -->
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('git status')" title="Vérifier l'état de l'arbre Git">
                    <i class="fa-brands fa-git-alt text-danger me-1"></i>git status
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('git pull')" title="Récupérer les derniers commits">
                    <i class="fa-solid fa-cloud-arrow-down text-teal me-1"></i>git pull
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('git log -5 --oneline')" title="Voir les 5 derniers commits">
                    <i class="fa-solid fa-list-ol text-secondary me-1"></i>git log -5
                </button>

                <span class="text-muted mx-1">|</span>

                <!-- Groupe Migrations -->
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('php scripts/migrate.php --status')" title="Statut des migrations SQL">
                    <i class="fa-solid fa-database text-primary me-1"></i>migrate --status
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('php scripts/migrate.php')" title="Appliquer les migrations SQL en attente">
                    <i class="fa-solid fa-play text-success me-1"></i>migrate (run)
                </button>

                <span class="text-muted mx-1">|</span>

                <!-- Groupe Services & Logs -->
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('systemctl status apache2')" title="État du service Apache">
                    <i class="fa-solid fa-server text-indigo me-1"></i>apache status
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('tail -n 20 /var/log/apache2/error.log')" title="Dernières lignes log Apache">
                    <i class="fa-solid fa-file-waveform text-danger me-1"></i>tail apache.log
                </button>

                <span class="text-muted mx-1">|</span>

                <!-- Groupe Système -->
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('df -h')" title="Espace disque disponible">
                    <i class="fa-solid fa-hard-drive me-1"></i>df -h
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('free -m')" title="Mémoire vive RAM">
                    <i class="fa-solid fa-memory me-1"></i>free -m
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('whoami')" title="Utilisateur actif">
                    <i class="fa-solid fa-id-badge me-1"></i>whoami
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" onclick="runPresetCommand('ls -lah')" title="Lister les fichiers du dossier courant">
                    <i class="fa-solid fa-folder-tree me-1"></i>ls -lah
                </button>
            </div>
        </div>
    </div>

    <!-- ── 4. CONSOLE TERMINAL SOMBRE ── -->
    <div class="card border-0 shadow-lg overflow-hidden mb-4" id="terminalScreenCard" style="background: #0b0f19; border-radius: 10px;">
        <!-- Barre de titre Terminal macOS / Unix -->
        <div class="py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom border-dark select-none" style="background: #111827;">
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #ef4444;" title="Fermer (Inactif)"></span>
                <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #f59e0b;" title="Réduire"></span>
                <span class="d-inline-block rounded-circle" style="width: 11px; height: 11px; background: #10b981;" title="Agrandir"></span>
                <span class="text-white-50 font-monospace small ms-2 d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                    <i class="fa-solid fa-terminal text-success"></i>
                    <span id="terminalTitlePrompt"><?= htmlspecialchars($sys['user'] ?? 'www-data') ?>@<?= htmlspecialchars($sys['hostname'] ?? 'raspberrypi') ?>:<?= htmlspecialchars($sys['cwd'] ?? '/var/www/html') ?></span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark-lt text-white-50 font-monospace" style="font-size: 0.7rem;">Bash / Sh</span>
                <span class="badge bg-success text-white font-monospace" style="font-size: 0.65rem;">
                    <i class="fa-solid fa-bolt me-1"></i>Direct
                </span>
            </div>
        </div>

        <!-- Corps d'affichage du Terminal -->
        <div class="p-3 font-monospace text-light" id="terminalBody" style="min-height: 400px; max-height: 600px; overflow-y: auto; font-size: 0.82rem; line-height: 1.6; background: #0b0f19;">
            <!-- Message de bienvenue (MOTD) -->
            <div class="terminal-welcome text-white-50 mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                <div class="text-success fw-bold">🏯 OpenShogun — Console Web d'Administration Système</div>
                <div class="text-white-50 small">Connecté sous : <span class="text-warning"><?= htmlspecialchars($sys['user'] ?? 'www-data') ?></span> | Hôte : <span class="text-info"><?= htmlspecialchars($sys['hostname'] ?? 'raspberrypi') ?></span> (<?= htmlspecialchars($sys['os'] ?? 'Linux') ?>)</div>
                <div class="text-secondary small mt-1">Tapez une commande ou utilisez les raccourcis ci-dessus. Flèches <kbd>↑</kbd> <kbd>↓</kbd> pour l'historique, <kbd>clear</kbd> pour effacer.</div>
            </div>

            <!-- Historique des commandes exécutées dans la session -->
            <div id="terminalEntriesList"></div>

            <!-- Ligne de commande en cours de saisie -->
            <div class="d-flex align-items-center gap-2 mt-2 pt-1" id="terminalInputRow">
                <span class="text-success fw-bold select-none" id="terminalLivePrompt"><?= htmlspecialchars($sys['user'] ?? 'www-data') ?>@<?= htmlspecialchars($sys['hostname'] ?? 'raspberrypi') ?>:<span class="text-cyan">~</span>$</span>
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="terminalCommandInput" 
                           class="form-control form-control-flush font-monospace text-white p-0 bg-transparent border-0 shadow-none" 
                           style="outline: none; caret-color: #10b981; font-size: 0.85rem;" 
                           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                           placeholder="Saisissez une commande shell...">
                </div>
                <div id="terminalRunningSpinner" class="spinner-border spinner-border-sm text-success flex-shrink-0 d-none" role="status"></div>
            </div>
        </div>

        <!-- Pied du Terminal -->
        <div class="py-1.5 px-3 bg-dark border-top border-dark d-flex justify-content-between align-items-center font-monospace text-secondary small" style="font-size: 0.72rem;">
            <span><i class="fa-solid fa-keyboard me-1 text-muted"></i>Entrée pour exécuter &bull; <kbd>↑</kbd> <kbd>↓</kbd> Historique</span>
            <span id="terminalLastExecDuration">Prêt</span>
        </div>
    </div>

</div>

<style>
/* Styles spécifiques pour le terminal web */
.web-terminal-container .terminal-line {
    white-space: pre-wrap;
    word-break: break-all;
}
.web-terminal-container .terminal-cmd-echo {
    color: #38bdf8;
    font-weight: bold;
}
.web-terminal-container .terminal-output-success {
    color: #e2e8f0;
}
.web-terminal-container .terminal-output-error {
    color: #f87171;
}
.web-terminal-container .terminal-output-warn {
    color: #fbbf24;
}
.web-terminal-container .terminal-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh !important;
    z-index: 99999 !important;
    border-radius: 0 !important;
    margin: 0 !important;
}
.web-terminal-container .terminal-fullscreen #terminalBody {
    max-height: calc(100vh - 85px) !important;
    height: calc(100vh - 85px) !important;
}
</style>

<script>
// État local du terminal
const terminalState = {
    history: JSON.parse(localStorage.getItem('openshogun_term_history') || '[]'),
    historyIndex: -1,
    tempInput: '',
    isRunning: false,
    cwd: <?= json_encode($sys['cwd'] ?? '/var/www/html') ?>,
    user: <?= json_encode($sys['user'] ?? 'www-data') ?>,
    hostname: <?= json_encode($sys['hostname'] ?? 'raspberrypi') ?>
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    initTerminalInput();
});

function initTerminalInput() {
    const input = document.getElementById('terminalCommandInput');
    if (!input) return;

    // Focus automatique
    input.focus();

    // Clic n'importe où dans le terminal pour donner le focus à l'input
    const body = document.getElementById('terminalBody');
    if (body) {
        body.addEventListener('click', (e) => {
            // Ne pas refocusser si l'utilisateur est en train de sélectionner du texte
            if (window.getSelection().toString().length === 0) {
                input.focus();
            }
        });
    }

    // Gestion des touches du clavier
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const cmd = input.value.trim();
            if (cmd !== '') {
                executeTerminalCommand(cmd);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateHistory(-1);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateHistory(1);
        } else if (e.key === 'Tab') {
            e.preventDefault();
            autoCompleteCommand(input);
        }
    });
}

// Navigation dans l'historique des commandes (Flèche Haut / Flèche Bas)
function navigateHistory(direction) {
    const input = document.getElementById('terminalCommandInput');
    if (!input || terminalState.history.length === 0) return;

    if (terminalState.historyIndex === -1 && direction === -1) {
        terminalState.tempInput = input.value;
        terminalState.historyIndex = terminalState.history.length - 1;
    } else {
        terminalState.historyIndex += direction;
    }

    if (terminalState.historyIndex < 0) {
        terminalState.historyIndex = 0;
    } else if (terminalState.historyIndex >= terminalState.history.length) {
        terminalState.historyIndex = -1;
        input.value = terminalState.tempInput;
        return;
    }

    input.value = terminalState.history[terminalState.historyIndex] || '';
    // Mettre le curseur à la fin
    setTimeout(() => {
        input.selectionStart = input.selectionEnd = input.value.length;
    }, 10);
}

// Autocomplétion basique des commandes usuelles (Tab)
function autoCompleteCommand(input) {
    const val = input.value;
    const candidates = [
        'git status', 'git pull', 'git log -5 --oneline', 'git diff',
        'php scripts/migrate.php --status', 'php scripts/migrate.php',
        'systemctl status apache2', 'systemctl status mysql', 'systemctl restart apache2',
        'df -h', 'free -m', 'uptime', 'uname -a', 'whoami', 'ls -lah', 'clear'
    ];
    const match = candidates.find(c => c.startsWith(val));
    if (match) {
        input.value = match;
    }
}

// Exécution d'une commande prédéfinie (Boutons 1-clic)
function runPresetCommand(cmd) {
    const input = document.getElementById('terminalCommandInput');
    if (input) {
        input.value = cmd;
        executeTerminalCommand(cmd);
    }
}

// Exécution via API
async function executeTerminalCommand(command) {
    if (terminalState.isRunning) return;

    const input = document.getElementById('terminalCommandInput');
    const spinner = document.getElementById('terminalRunningSpinner');
    const entriesList = document.getElementById('terminalEntriesList');
    const termBody = document.getElementById('terminalBody');

    // Sauvegarde dans l'historique local
    terminalState.history.push(command);
    if (terminalState.history.length > 100) terminalState.history.shift();
    localStorage.setItem('openshogun_term_history', JSON.stringify(terminalState.history));
    terminalState.historyIndex = -1;

    // Traitement immédiat de clear
    if (command === 'clear' || command === 'cls') {
        clearTerminalScreen();
        input.value = '';
        return;
    }

    // Affichage de la ligne de commande dans l'historique d'écran
    const promptText = `${terminalState.user}@${terminalState.hostname}:${formatCwdDisplay(terminalState.cwd)}$ `;
    const rowEl = document.createElement('div');
    rowEl.className = 'terminal-entry mb-2.5';
    rowEl.innerHTML = `
        <div class="d-flex align-items-center gap-1.5 terminal-cmd-echo">
            <span class="text-success fw-bold select-none">${escapeHtml(promptText)}</span>
            <span>${escapeHtml(command)}</span>
        </div>
        <div class="terminal-cmd-output mt-1 text-white-50" id="output-loading-${Date.now()}">
            <i class="fa-solid fa-spinner fa-spin me-1 text-success"></i>Exécution en cours...
        </div>
    `;
    entriesList.appendChild(rowEl);
    termBody.scrollTop = termBody.scrollHeight;

    // État actif
    terminalState.isRunning = true;
    input.value = '';
    input.disabled = true;
    if (spinner) spinner.classList.remove('d-none');

    const durationBadge = document.getElementById('terminalLastExecDuration');
    if (durationBadge) durationBadge.innerText = 'Exécution...';

    const outputContainer = rowEl.querySelector('.terminal-cmd-output');

    try {
        const formData = new FormData();
        formData.append('action', 'execute_terminal_command');
        formData.append('command', command);

        const res = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await res.json();

        if (data.success) {
            const r = data.result || {};
            // Mise à jour de l'état
            if (r.cwd) {
                terminalState.cwd = r.cwd;
                updatePromptDisplay(r.cwd);
            }

            if (r.output === '__CLEAR__') {
                clearTerminalScreen();
                return;
            }

            const outputClass = (r.return_code === 0) ? 'terminal-output-success' : 'terminal-output-error';
            const statusIcon = (r.return_code === 0) ? '<i class="fa-solid fa-check text-success me-1"></i>' : '<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>';
            const outputText = (r.output && r.output.trim() !== '') ? escapeHtml(r.output) : '<span class="text-muted fst-italic">(Aucune sortie retournée)</span>';

            outputContainer.className = `terminal-cmd-output mt-1 ${outputClass} terminal-line`;
            outputContainer.innerHTML = outputText;

            if (durationBadge) {
                durationBadge.innerHTML = `${statusIcon}Terminé en ${r.execution_time_ms || 0} ms &bull; Code: ${r.return_code}`;
            }
        } else {
            outputContainer.className = 'terminal-cmd-output mt-1 terminal-output-error terminal-line';
            outputContainer.innerHTML = `❌ Erreur API : ${escapeHtml(data.error || 'Erreur inconnue')}`;
            if (durationBadge) durationBadge.innerText = 'Erreur d\'exécution';
        }
    } catch (err) {
        outputContainer.className = 'terminal-cmd-output mt-1 terminal-output-error terminal-line';
        outputContainer.innerHTML = `❌ Erreur Réseau / Serveur : ${escapeHtml(err.message || 'Impossible de joindre le serveur.')}`;
        if (durationBadge) durationBadge.innerText = 'Échec réseau';
    } finally {
        terminalState.isRunning = false;
        input.disabled = false;
        if (spinner) spinner.classList.add('d-none');
        input.focus();
        termBody.scrollTop = termBody.scrollHeight;
    }
}

// Mise à jour de l'affichage du prompt selon le dossier courant
function updatePromptDisplay(newCwd) {
    const formatted = formatCwdDisplay(newCwd);
    const livePrompt = document.getElementById('terminalLivePrompt');
    if (livePrompt) {
        livePrompt.innerHTML = `${escapeHtml(terminalState.user)}@${escapeHtml(terminalState.hostname)}:<span class="text-cyan">${escapeHtml(formatted)}</span>$`;
    }
    const titlePrompt = document.getElementById('terminalTitlePrompt');
    if (titlePrompt) {
        titlePrompt.innerText = `${terminalState.user}@${terminalState.hostname}:${newCwd}`;
    }
    const dirHint = document.getElementById('terminalCurrentDirHint');
    if (dirHint) {
        dirHint.innerHTML = `<i class="fa-regular fa-folder-open me-1 text-indigo"></i>${escapeHtml(newCwd)}`;
    }
}

function formatCwdDisplay(cwd) {
    if (!cwd) return '~';
    const parts = cwd.replace(/\\/g, '/').split('/');
    return parts.pop() || '~';
}

// Effacer l'écran de la console
function clearTerminalScreen() {
    const entriesList = document.getElementById('terminalEntriesList');
    if (entriesList) entriesList.innerHTML = '';
    const durationBadge = document.getElementById('terminalLastExecDuration');
    if (durationBadge) durationBadge.innerText = 'Écran effacé';
}

// Copier tout le contenu du terminal
function copyTerminalOutput() {
    const body = document.getElementById('terminalBody');
    if (!body) return;

    navigator.clipboard.writeText(body.innerText).then(() => {
        const btnText = document.getElementById('copyTerminalBtnText');
        if (btnText) {
            const orig = btnText.innerText;
            btnText.innerText = 'Copié !';
            setTimeout(() => { btnText.innerText = orig; }, 2000);
        }
    });
}

// Bascule plein écran
function toggleTerminalFullscreen() {
    const card = document.getElementById('terminalScreenCard');
    const icon = document.getElementById('fullscreenIcon');
    const text = document.getElementById('fullscreenText');
    if (!card) return;

    const isFull = card.classList.toggle('terminal-fullscreen');
    if (isFull) {
        if (icon) icon.className = 'fa-solid fa-compress';
        if (text) text.innerText = 'Réduire';
    } else {
        if (icon) icon.className = 'fa-solid fa-expand';
        if (text) text.innerText = 'Plein Écran';
    }
    const input = document.getElementById('terminalCommandInput');
    if (input) input.focus();
}

// Actualisation des métriques système
async function refreshTerminalSysinfo() {
    const icon = document.getElementById('refreshSysinfoIcon');
    if (icon) icon.classList.add('fa-spin');

    try {
        const formData = new FormData();
        formData.append('action', 'get_terminal_sysinfo');

        const res = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await res.json();

        if (data.success && data.sysinfo) {
            const s = data.sysinfo;
            const elOs = document.getElementById('sys-os-title');
            if (elOs) elOs.innerText = s.os;
            const elUser = document.getElementById('sys-user-name');
            if (elUser) elUser.innerText = s.user;
            const elDiskFree = document.getElementById('sys-disk-free');
            if (elDiskFree) elDiskFree.innerText = s.disk_free;
            const elDiskBar = document.getElementById('sys-disk-bar');
            if (elDiskBar) elDiskBar.style.width = (s.disk_percent || 0) + '%';
            const elMemFree = document.getElementById('sys-mem-free');
            if (elMemFree) elMemFree.innerHTML = `${s.mem_free} <span class="small text-secondary fw-normal">dispo</span>`;
            const elUptime = document.getElementById('sys-uptime');
            if (elUptime) elUptime.innerText = s.uptime;

            if (s.cwd) {
                terminalState.cwd = s.cwd;
                updatePromptDisplay(s.cwd);
            }
        }
    } catch (e) {
        console.error("Échec actualisation métriques terminal:", e);
    } finally {
        if (icon) icon.classList.remove('fa-spin');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
