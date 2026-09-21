<?php
/**
 * Vue Partielle d'Administration : Gestionnaire de Mises à Jour GitHub (OpenShogun)
 */
require_once __DIR__ . '/../../core/UpdateEngine.php';

$updateEngine = new UpdateEngine();
$localInfo = $updateEngine->getLocalInfo();
?>

<div class="card" style="margin-bottom: 2rem; border-color: rgba(56, 189, 248, 0.3);">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid rgba(56, 189, 248, 0.2);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.6rem;">🔄</span>
            <div>
                <h3 style="margin: 0; color: #38bdf8; font-size: 1.2rem;">Centre de Mises à Jour & Déploiement GitHub</h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Contrôle des versions, inspection des commits et synchronisation en un clic</span>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="button" class="btn btn-secondary" onclick="checkGitHubUpdates(true)" id="btn-check-updates" style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.84rem; padding: 0.45rem 0.9rem;">
                <span id="spinner-check" style="display: none;" class="spinner-border spinner-border-sm">⏳</span>
                <span>🔍 Contrôler les Mises à Jour</span>
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- BANNIÈRE DYNAMIQUE DE STATUT -->
        <div id="update-status-banner" style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div id="banner-icon" style="font-size: 1.8rem;">📡</div>
                <div>
                    <div id="banner-title" style="font-weight: 700; color: #fff; font-size: 1rem;">
                        Version Locale Actuelle : <code style="color: #38bdf8;"><?= htmlspecialchars($localInfo['short_sha']) ?></code>
                    </div>
                    <div id="banner-desc" style="font-size: 0.82rem; color: #94a3b8; margin-top: 0.2rem;">
                        Dernier commit : <?= htmlspecialchars($localInfo['commit_message']) ?> (<?= htmlspecialchars($localInfo['human_date']) ?>)
                    </div>
                </div>
            </div>
            <div id="banner-action">
                <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                    Branche <?= htmlspecialchars($localInfo['branch']) ?>
                </span>
            </div>
        </div>

        <!-- GRILLE COMPARATIVE LOCAL VS GITHUB -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 1.75rem;">
            <!-- FIEF LOCAL -->
            <div style="background: rgba(17, 18, 24, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.5rem;">
                    <strong style="color: #cbd5e1; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>🏯</span> Serveur Local (Fief)
                    </strong>
                    <span class="badge" style="background: #1e293b; color: #94a3b8; font-size: 0.75rem;">
                        <?= htmlspecialchars($localInfo['branch']) ?>
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Commit Actif :</span>
                        <strong style="color: #67e8f9; font-family: monospace;"><?= htmlspecialchars($localInfo['short_sha']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Auteur :</span>
                        <span style="color: #fff;"><?= htmlspecialchars($localInfo['author_name']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Horodatage :</span>
                        <span style="color: #cbd5e1;"><?= htmlspecialchars($localInfo['human_date']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--text-muted);">Arbre de Travail :</span>
                        <?php if ($localInfo['is_clean']): ?>
                            <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">
                                ✔ Propre (Clean)
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3);" title="<?= htmlspecialchars(implode(', ', $localInfo['dirty_files'])) ?>">
                                ⚠️ Modifié (<?= count($localInfo['dirty_files']) ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DÉPÔT GITHUB DISTANT -->
            <div style="background: rgba(17, 18, 24, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.5rem;">
                    <strong style="color: #cbd5e1; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>🐙</span> Dépôt GitHub Officiel
                    </strong>
                    <a href="https://github.com/<?= htmlspecialchars($localInfo['repo_owner'] . '/' . $localInfo['repo_name']) ?>" target="_blank" rel="noopener" style="color: #38bdf8; font-size: 0.75rem; text-decoration: none;">
                        Ouvrir sur GitHub &nearr;
                    </a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Dépôt :</span>
                        <strong style="color: #fff;"><?= htmlspecialchars($localInfo['repo_owner'] . '/' . $localInfo['repo_name']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Branche Cible :</span>
                        <strong style="color: #a855f7;"><?= htmlspecialchars($localInfo['target_branch']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Clé Token :</span>
                        <span style="color: #94a3b8; font-family: monospace;"><?= htmlspecialchars($localInfo['masked_token']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--text-muted);">Statut Distant :</span>
                        <span id="remote-status-pill" class="badge" style="background: rgba(255,255,255,0.05); color: #94a3b8;">
                            Contrôle en attente...
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE DÉDIÉE : NOUVELLE MISE À JOUR DISPONIBLE (Affichée dynamiquement) -->
        <div id="update-action-box" style="display: none; background: rgba(30, 27, 75, 0.4); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h4 style="margin: 0; color: #c084fc; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>✨</span> Nouveaux Commits Disponibles sur GitHub
                    </h4>
                    <span id="update-behind-text" style="font-size: 0.82rem; color: #e2e8f0;">
                        Votre version locale a des commits de retard par rapport à la branche principale.
                    </span>
                </div>
                <button type="button" class="btn btn-primary" onclick="installGitHubUpdate()" id="btn-install-update" style="background: linear-gradient(135deg, #7c3aed, #9333ea); border-color: #a855f7; font-weight: 700; padding: 0.6rem 1.4rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 0 15px rgba(147, 51, 234, 0.4);">
                    <span id="spinner-install" style="display: none;" class="spinner-border spinner-border-sm">⏳</span>
                    <span>🚀 Télécharger & Déployer la Mise à Jour</span>
                </button>
            </div>

            <!-- OPTIONS AVANT INSTALLATION -->
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; font-size: 0.82rem; color: #cbd5e1;">
                <input type="checkbox" id="auto-stash-check" checked style="cursor: pointer;">
                <label for="auto-stash-check" style="cursor: pointer; margin: 0;">
                    Sauvegarder automatiquement mes modifications locales avec <code>git stash</code> avant la mise à jour (Recommandé)
                </label>
            </div>

            <!-- LISTE DES COMMITS À INSTALLER -->
            <div style="max-height: 240px; overflow-y: auto; background: rgba(0,0,0,0.3); border-radius: 6px; border: 1px solid rgba(255,255,255,0.06);">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.82rem; margin: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.04); color: #c084fc; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <th style="padding: 0.5rem 0.75rem;">SHA</th>
                            <th style="padding: 0.5rem 0.75rem;">Message du Commit</th>
                            <th style="padding: 0.5rem 0.75rem;">Auteur</th>
                            <th style="padding: 0.5rem 0.75rem; text-align: right;">Lien</th>
                        </tr>
                    </thead>
                    <tbody id="update-commits-tbody">
                        <!-- Rempli par JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CONSOLE D'EXÉCUTION TERMINAL EN DIRECT -->
        <div style="margin-bottom: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong style="color: #94a3b8; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
                    <span>💻</span> Console d'Exécution & Journal Système
                </strong>
                <button type="button" onclick="clearUpdateLogs()" class="btn btn-secondary" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">Effacer la console</button>
            </div>
            <pre id="update-console-log" style="background: #090d16; color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 8px; padding: 0.85rem 1rem; font-family: monospace; font-size: 0.78rem; min-height: 80px; max-height: 220px; overflow-y: auto; white-space: pre-wrap; line-height: 1.5; margin: 0;">Console d'administration prête. Cliquez sur "Contrôler les Mises à Jour" pour interroger GitHub.</pre>
        </div>

        <!-- FORMULAIRE DE CONFIGURATION GITHUB -->
        <div style="background: rgba(17, 18, 24, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 1.25rem;">
            <h4 style="margin: 0 0 1rem 0; color: #facc15; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                <span>⚙️</span> Paramètres de Connexion GitHub
            </h4>
            <form id="github-settings-form" onsubmit="saveGitHubSettings(event)" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div>
                    <label style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Propriétaire / Organisation GitHub</label>
                    <input type="text" name="github_repo_owner" value="<?= htmlspecialchars($localInfo['repo_owner']) ?>" required style="width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.84rem;">
                </div>
                <div>
                    <label style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Nom du Dépôt</label>
                    <input type="text" name="github_repo_name" value="<?= htmlspecialchars($localInfo['repo_name']) ?>" required style="width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.84rem;">
                </div>
                <div>
                    <label style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Branche Cible</label>
                    <input type="text" name="github_branch" value="<?= htmlspecialchars($localInfo['target_branch']) ?>" required style="width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.84rem;">
                </div>
                <div>
                    <label style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Clé Token GitHub (Personnel Access Token)</label>
                    <input type="password" name="github_token" placeholder="Laisser vide pour conserver le token actuel" style="width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.84rem;">
                </div>
                <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; margin-top: 0.25rem;">
                    <button type="submit" class="btn btn-secondary" style="font-size: 0.84rem; padding: 0.45rem 1rem;">
                        💾 Sauvegarder les Paramètres GitHub
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- LOGIQUE CLIENT DU CENTRE DE MISES À JOUR ---

function showToast(message, type = 'info') {
    let container = document.getElementById('admin-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'admin-toast-container';
        container.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; max-width: 420px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = 'padding: 12px 18px; border-radius: 8px; color: #fff; font-size: 0.9rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5), 0 8px 10px -6px rgba(0,0,0,0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); pointer-events: auto; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0; transform: translateY(16px) scale(0.95); display: flex; align-items: center; gap: 10px;';

    let icon = 'ℹ️';
    let bg = 'rgba(15, 23, 42, 0.95)';
    let borderColor = 'rgba(56, 189, 248, 0.4)';

    if (type === 'success') {
        icon = '✅';
        bg = 'rgba(6, 44, 33, 0.95)';
        borderColor = 'rgba(34, 197, 94, 0.5)';
    } else if (type === 'warning') {
        icon = '⚠️';
        bg = 'rgba(69, 26, 3, 0.95)';
        borderColor = 'rgba(245, 158, 11, 0.5)';
    } else if (type === 'error' || type === 'danger') {
        icon = '❌';
        bg = 'rgba(69, 10, 10, 0.95)';
        borderColor = 'rgba(239, 68, 68, 0.5)';
    }

    toast.style.background = bg;
    toast.style.borderColor = borderColor;
    toast.innerHTML = `<span style="font-size: 1.1rem; flex-shrink: 0;">${icon}</span><span style="flex-grow: 1; line-height: 1.4;">${escapeHtml(message)}</span>`;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0) scale(1)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px) scale(0.95)';
        setTimeout(() => toast.remove(), 350);
    }, 4500);
}
window.showToast = showToast;

function logToConsole(message, type = 'info') {
    const consoleEl = document.getElementById('update-console-log');
    if (!consoleEl) return;
    const now = new Date().toLocaleTimeString();
    const prefix = `[${now}] `;
    consoleEl.textContent += `\n${prefix}${message}`;
    consoleEl.scrollTop = consoleEl.scrollHeight;
}

function clearUpdateLogs() {
    const consoleEl = document.getElementById('update-console-log');
    if (consoleEl) {
        consoleEl.textContent = 'Console réinitialisée.';
    }
}

// 1. Vérification des mises à jour sur GitHub
async function checkGitHubUpdates(showNotification = true) {
    const btnCheck = document.getElementById('btn-check-updates');
    const spinnerCheck = document.getElementById('spinner-check');
    const pillRemote = document.getElementById('remote-status-pill');
    const bannerTitle = document.getElementById('banner-title');
    const bannerDesc = document.getElementById('banner-desc');
    const bannerIcon = document.getElementById('banner-icon');
    const bannerBox = document.getElementById('update-status-banner');
    const actionBox = document.getElementById('update-action-box');
    const navBadge = document.getElementById('admin-update-nav-badge');

    if (btnCheck) btnCheck.disabled = true;
    if (spinnerCheck) spinnerCheck.style.display = 'inline-block';
    logToConsole("Interrogation de l'API GitHub en cours...");

    try {
        const response = await fetch('/api/admin.php?action=check_github_updates');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || "Erreur lors de la vérification.");
        }

        logToConsole(`Contrôle terminé avec succès. Statut GitHub : ${data.status} (Retard: ${data.behind_by} commit(s))`);

        if (pillRemote && data.remote) {
            pillRemote.textContent = data.remote.short_sha;
            pillRemote.className = 'badge';
            pillRemote.style.background = 'rgba(56, 189, 248, 0.2)';
            pillRemote.style.color = '#38bdf8';
        }

        if (data.has_update) {
            // Mettre à jour la bannière en mode NOUVELLE VERSION
            bannerBox.style.background = 'rgba(45, 26, 15, 0.85)';
            bannerBox.style.borderColor = 'rgba(245, 158, 11, 0.5)';
            bannerIcon.textContent = '⚡';
            bannerTitle.innerHTML = `<span style="color: #fbbf24;">Mise à jour disponible ! (${data.behind_by} nouveau${data.behind_by > 1 ? 'x' : ''} commit${data.behind_by > 1 ? 's' : ''})</span>`;
            bannerDesc.textContent = `Dernier commit distant : "${data.remote ? data.remote.message : 'Nouveaux ajouts'}"`;

            if (navBadge) {
                navBadge.textContent = `⚡ +${data.behind_by}`;
                navBadge.style.background = '#f59e0b';
                navBadge.style.color = '#000';
                navBadge.style.fontWeight = 'bold';
            }

            // Remplir le tableau des commits
            const tbody = document.getElementById('update-commits-tbody');
            if (tbody && Array.isArray(data.commits)) {
                tbody.innerHTML = '';
                data.commits.forEach(c => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                    tr.innerHTML = `
                        <td style="padding: 0.5rem 0.75rem; font-family: monospace; color: #a855f7;">${c.short_sha}</td>
                        <td style="padding: 0.5rem 0.75rem; color: #fff; font-weight: 500;">${escapeHtml(c.message)}</td>
                        <td style="padding: 0.5rem 0.75rem; color: #cbd5e1;">${escapeHtml(c.author_name)}</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">
                            ${c.html_url ? `<a href="${c.html_url}" target="_blank" rel="noopener" style="color: #38bdf8; text-decoration: none;">Voir &nearr;</a>` : ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            if (actionBox) actionBox.style.display = 'block';

            if (showNotification) {
                showToast(`Nouvelle version disponible ! (${data.behind_by} commit(s))`, 'warning');
            }
        } else {
            // Mettre à jour la bannière en mode À JOUR
            bannerBox.style.background = 'rgba(6, 44, 33, 0.85)';
            bannerBox.style.borderColor = 'rgba(34, 197, 94, 0.4)';
            bannerIcon.textContent = '✨';
            bannerTitle.innerHTML = `<span style="color: #4ade80;">OpenShogun est à jour !</span>`;
            bannerDesc.textContent = `Votre version locale (${data.local.short_sha}) est synchronisée avec la branche ${data.local.target_branch}.`;

            if (navBadge) {
                navBadge.textContent = data.local.short_sha;
                navBadge.style.background = 'rgba(34, 197, 94, 0.2)';
                navBadge.style.color = '#4ade80';
            }

            if (actionBox) actionBox.style.display = 'none';

            if (showNotification) {
                showToast("Votre royaume féodal est parfaitement à jour !", 'success');
            }
        }
    } catch (err) {
        logToConsole(`Erreur : ${err.message}`, 'error');
        if (showNotification) {
            showToast(`Erreur de contrôle : ${err.message}`, 'error');
        }
    } finally {
        if (btnCheck) btnCheck.disabled = false;
        if (spinnerCheck) spinnerCheck.style.display = 'none';
    }
}

// 2. Installation de la mise à jour (git pull)
async function installGitHubUpdate() {
    if (!confirm("Voulez-vous lancer le téléchargement et l'installation de la mise à jour maintenant ?")) {
        return;
    }

    const btnInstall = document.getElementById('btn-install-update');
    const spinnerInstall = document.getElementById('spinner-install');
    const autoStash = document.getElementById('auto-stash-check') ? document.getElementById('auto-stash-check').checked : true;

    if (btnInstall) btnInstall.disabled = true;
    if (spinnerInstall) spinnerInstall.style.display = 'inline-block';

    logToConsole("Déploiement initié... Téléchargement depuis GitHub...");

    try {
        const formData = new FormData();
        formData.append('action', 'install_github_update');
        formData.append('auto_stash', autoStash ? '1' : '0');

        const response = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (Array.isArray(data.logs)) {
            data.logs.forEach(l => logToConsole(l));
        }

        if (!data.success) {
            throw new Error(data.message || "Échec de l'installation.");
        }

        showToast(data.message, 'success');
        logToConsole(`🎉 Succès : Version installée ${data.current_commit} !`);

        setTimeout(() => {
            alert(`Mise à jour réussie !\nLe jeu a été mis à jour vers le commit ${data.current_commit}.\nLa page va s'actualiser.`);
            window.location.reload();
        }, 1500);

    } catch (err) {
        logToConsole(`Erreur lors du déploiement : ${err.message}`, 'error');
        showToast(`Erreur : ${err.message}`, 'error');
    } finally {
        if (btnInstall) btnInstall.disabled = false;
        if (spinnerInstall) spinnerInstall.style.display = 'none';
    }
}

// 3. Sauvegarde des paramètres GitHub
async function saveGitHubSettings(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'save_github_settings');

    try {
        const response = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || "Erreur de sauvegarde.");
        }

        showToast(data.message, 'success');
        logToConsole("Paramètres GitHub mis à jour avec succès.");
        form.querySelector('input[name="github_token"]').value = '';
        checkGitHubUpdates(false);
    } catch (err) {
        showToast(`Erreur : ${err.message}`, 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Lancement automatique du contrôle discret au chargement de l'onglet
document.addEventListener('DOMContentLoaded', () => {
    // Si l'onglet actif est 'updates', on vérifie automatiquement
    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'updates') {
        checkGitHubUpdates(false);
    }
});
</script>

