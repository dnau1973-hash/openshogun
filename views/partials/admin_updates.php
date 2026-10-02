<?php
/**
 * Vue Partielle d'Administration : Gestionnaire de Mises à Jour GitHub (OpenShogun)
 */
require_once __DIR__ . '/../../core/UpdateEngine.php';

$updateEngine = new UpdateEngine();
$localInfo = $updateEngine->getLocalInfo();
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar bg-teal-lt text-teal"><i class="fa-solid fa-arrows-rotate"></i></span>
            <div>
                <h3 class="card-title text-teal m-0">Centre de Mises à Jour &amp; Déploiement GitHub</h3>
                <div class="text-secondary small">Contrôle des versions, inspection des commits et synchronisation en un clic</div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-outline-teal" onclick="checkGitHubUpdates(true)" id="btn-check-updates">
                <span id="spinner-check" style="display: none;" class="spinner-border spinner-border-sm me-1"></span>
                <i class="fa-solid fa-magnifying-glass me-1"></i>Contrôler les Mises à Jour
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- BANNIÈRE DYNAMIQUE DE STATUT -->
        <div id="update-status-banner" class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div id="banner-icon" style="font-size: 1.8rem;"><i class="fa-solid fa-tower-broadcast text-primary"></i></div>
                <div>
                    <div id="banner-title" class="fw-bold">
                        Version Locale Actuelle : <code class="text-teal"><?= htmlspecialchars($localInfo['short_sha']) ?></code>
                    </div>
                    <div id="banner-desc" class="small text-secondary mt-1">
                        Dernier commit : <?= htmlspecialchars($localInfo['commit_message']) ?> (<?= htmlspecialchars($localInfo['human_date']) ?>)
                    </div>
                </div>
            </div>
            <div id="banner-action">
                <span class="badge bg-teal-lt">
                    Branche <?= htmlspecialchars($localInfo['branch']) ?>
                </span>
            </div>
        </div>

        <!-- GRILLE COMPARATIVE LOCAL VS GITHUB -->
        <div class="row row-cards mb-4">
            <!-- FIEF LOCAL -->
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-server text-secondary me-1"></i>Serveur Local (Fief)
                        </strong>
                        <span class="badge bg-secondary-lt">
                            <?= htmlspecialchars($localInfo['branch']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Commit Actif :</span>
                                <strong class="font-monospace text-teal"><?= htmlspecialchars($localInfo['short_sha']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Auteur :</span>
                                <span class="fw-medium"><?= htmlspecialchars($localInfo['author_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Horodatage :</span>
                                <span><?= htmlspecialchars($localInfo['human_date']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">Arbre de Travail :</span>
                                <?php if ($localInfo['is_clean']): ?>
                                    <span class="badge bg-success-lt">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i>Propre (Clean)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-lt" title="<?= htmlspecialchars(implode(', ', $localInfo['dirty_files'])) ?>">
                                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Modifié (<?= count($localInfo['dirty_files']) ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DÉPÔT GITHUB DISTANT -->
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong class="d-flex align-items-center gap-2">
                            <i class="fa-brands fa-github text-dark me-1"></i>Dépôt GitHub Officiel
                        </strong>
                        <a href="https://github.com/<?= htmlspecialchars($localInfo['repo_owner'] . '/' . $localInfo['repo_name']) ?>" target="_blank" rel="noopener" class="small text-decoration-none">
                            Ouvrir sur GitHub &nearr;
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Dépôt :</span>
                                <strong><?= htmlspecialchars($localInfo['repo_owner'] . '/' . $localInfo['repo_name']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Branche Cible :</span>
                                <strong class="text-purple"><?= htmlspecialchars($localInfo['target_branch']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Clé Token :</span>
                                <span class="font-monospace text-secondary"><?= htmlspecialchars($localInfo['masked_token']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">Statut Distant :</span>
                                <span id="remote-status-pill" class="badge bg-secondary-lt">
                                    Contrôle en attente...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ZONE DÉDIÉE : NOUVELLE MISE À JOUR DISPONIBLE (Affichée dynamiquement) -->
        <div id="update-action-box" class="card card-body bg-light border-purple mb-4" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="text-purple card-title d-flex align-items-center gap-2 m-0">
                        <i class="fa-solid fa-wand-magic-sparkles text-warning me-1"></i>Nouveaux Commits Disponibles sur GitHub
                    </h4>
                    <div id="update-behind-text" class="text-secondary small mt-1">
                        Votre version locale a des commits de retard par rapport à la branche principale.
                    </div>
                </div>
                <button type="button" class="btn btn-primary fw-bold" onclick="installGitHubUpdate()" id="btn-install-update">
                    <span id="spinner-install" style="display: none;" class="spinner-border spinner-border-sm me-1"></span>
                    <i class="fa-solid fa-cloud-arrow-down me-1"></i>Télécharger &amp; Déployer la Mise à Jour
                </button>
            </div>

            <!-- OPTIONS AVANT INSTALLATION -->
            <div class="mb-3">
                <label class="form-check form-switch m-0" for="auto-stash-check">
                    <input class="form-check-input" type="checkbox" id="auto-stash-check" checked>
                    <span class="form-check-label small">
                        Sauvegarder automatiquement mes modifications locales avec <code>git stash</code> avant la mise à jour (Recommandé)
                    </span>
                </label>
            </div>

            <!-- LISTE DES COMMITS À INSTALLER -->
            <div class="table-responsive border rounded" style="max-height: 240px; overflow-y: auto;">
                <table class="table table-vcenter table-nowrap card-table table-hover">
                    <thead>
                        <tr>
                            <th>SHA</th>
                            <th>Message du Commit</th>
                            <th>Auteur</th>
                            <th class="text-end">Lien</th>
                        </tr>
                    </thead>
                    <tbody id="update-commits-tbody">
                        <!-- Rempli par JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CONSOLE D'EXÉCUTION TERMINAL EN DIRECT -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="d-flex align-items-center gap-2 text-secondary small">
                    <i class="fa-solid fa-terminal text-success me-1"></i>Console d'Exécution &amp; Journal Système
                </strong>
                <button type="button" onclick="clearUpdateLogs()" class="btn btn-sm btn-outline-secondary">Effacer la console</button>
            </div>
            <pre id="update-console-log" class="form-control font-monospace bg-dark text-cyan p-3 m-0" style="min-height: 80px; max-height: 220px; overflow-y: auto; white-space: pre-wrap; font-size: 0.8rem; line-height: 1.5;">Console d'administration prête. Cliquez sur "Contrôler les Mises à Jour" pour interroger GitHub.</pre>
        </div>

        <!-- FORMULAIRE DE CONFIGURATION GITHUB -->
        <div class="card card-body bg-light border">
            <h4 class="card-title d-flex align-items-center gap-2 mb-3">
                <i class="fa-solid fa-gear text-secondary me-1"></i>Paramètres de Connexion GitHub
            </h4>
            <form id="github-settings-form" onsubmit="saveGitHubSettings(event)">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required">Propriétaire / Organisation GitHub</label>
                        <input type="text" name="github_repo_owner" value="<?= htmlspecialchars($localInfo['repo_owner']) ?>" required class="form-control">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required">Nom du Dépôt</label>
                        <input type="text" name="github_repo_name" value="<?= htmlspecialchars($localInfo['repo_name']) ?>" required class="form-control">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label required">Branche Cible</label>
                        <input type="text" name="github_branch" value="<?= htmlspecialchars($localInfo['target_branch']) ?>" required class="form-control">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label">Clé Token GitHub (PAT)</label>
                        <input type="password" name="github_token" placeholder="Laisser vide pour conserver" class="form-control">
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Sauvegarder les Paramètres GitHub
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- LOGIQUE CLIENT DU CENTRE DE MISES À JOUR ---

function showUpdateToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        try {
            window.showToast(message, type);
            return;
        } catch (e) {
            // fallback
        }
    }
    let container = document.getElementById('admin-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'admin-toast-container';
        container.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; max-width: 420px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = 'padding: 12px 18px; border-radius: 8px; color: #fff; font-size: 0.9rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5), 0 8px 10px -6px rgba(0,0,0,0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); pointer-events: auto; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0; transform: translateY(16px) scale(0.95); display: flex; align-items: center; gap: 10px;';

    let icon = '<i class="fa-solid fa-circle-info text-info me-1"></i>';
    let bg = 'rgba(15, 23, 42, 0.95)';
    let borderColor = 'rgba(56, 189, 248, 0.4)';

    if (type === 'success') {
        icon = '<i class="fa-solid fa-circle-check text-success me-1"></i>';
        bg = 'rgba(6, 44, 33, 0.95)';
        borderColor = 'rgba(34, 197, 94, 0.5)';
    } else if (type === 'warning') {
        icon = '<i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>';
        bg = 'rgba(69, 26, 3, 0.95)';
        borderColor = 'rgba(245, 158, 11, 0.5)';
    } else if (type === 'error' || type === 'danger') {
        icon = '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>';
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
        if (!response.ok) {
            throw new Error(`Erreur serveur HTTP ${response.status}`);
        }
        const data = await response.json();

        if (!data || !data.success) {
            const errMsg = (data && data.error) ? data.error : "Impossible de contacter l'API GitHub.";
            throw new Error(errMsg);
        }

        const behindBy = Number(data.behind_by) || 0;
        logToConsole(`Contrôle terminé avec succès. Statut GitHub : ${data.status || 'OK'} (Retard: ${behindBy} commit(s))`);

        if (pillRemote && data.remote) {
            pillRemote.textContent = data.remote.short_sha || 'GitHub';
            pillRemote.className = 'badge';
            pillRemote.style.background = 'rgba(56, 189, 248, 0.2)';
            pillRemote.style.color = '#38bdf8';
        }

        const localShortSha = (data.local && data.local.short_sha) ? data.local.short_sha : 'Actuel';
        const localBranch = (data.local && data.local.target_branch) ? data.local.target_branch : 'main';

        if (data.has_update) {
            // Mettre à jour la bannière en mode NOUVELLE VERSION
            if (bannerBox) {
                bannerBox.style.background = 'rgba(45, 26, 15, 0.85)';
                bannerBox.style.borderColor = 'rgba(245, 158, 11, 0.5)';
            }
            if (bannerIcon) bannerIcon.innerHTML = '<i class="fa-solid fa-bolt text-warning"></i>';
            if (bannerTitle) bannerTitle.innerHTML = `<span style="color: #fbbf24;">Mise à jour disponible ! (${behindBy} nouveau${behindBy > 1 ? 'x' : ''} commit${behindBy > 1 ? 's' : ''})</span>`;
            if (bannerDesc) bannerDesc.textContent = `Dernier commit distant : "${data.remote ? data.remote.message : 'Nouveaux ajouts'}"`;

            if (navBadge) {
                navBadge.innerHTML = `<i class="fa-solid fa-bolt me-1"></i>+${behindBy}`;
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
                        <td style="padding: 0.5rem 0.75rem; font-family: monospace; color: #a855f7;">${escapeHtml(c.short_sha || '')}</td>
                        <td style="padding: 0.5rem 0.75rem; color: #fff; font-weight: 500;">${escapeHtml(c.message || '')}</td>
                        <td style="padding: 0.5rem 0.75rem; color: #cbd5e1;">${escapeHtml(c.author_name || '')}</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">
                            ${c.html_url ? `<a href="${c.html_url}" target="_blank" rel="noopener" style="color: #38bdf8; text-decoration: none;">Voir &nearr;</a>` : ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            if (actionBox) actionBox.style.display = 'block';

            if (showNotification) {
                showUpdateToast(`Nouvelle version disponible ! (${behindBy} commit(s))`, 'warning');
            }
        } else {
            // Mettre à jour la bannière en mode À JOUR
            if (bannerBox) {
                bannerBox.style.background = 'rgba(6, 44, 33, 0.85)';
                bannerBox.style.borderColor = 'rgba(34, 197, 94, 0.4)';
            }
            if (bannerIcon) bannerIcon.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-warning"></i>';
            if (bannerTitle) bannerTitle.innerHTML = `<span style="color: #4ade80;">OpenShogun est à jour !</span>`;
            if (bannerDesc) bannerDesc.textContent = `Votre version locale (${localShortSha}) est synchronisée avec la branche ${localBranch}.`;

            if (navBadge) {
                navBadge.textContent = localShortSha;
                navBadge.style.background = 'rgba(34, 197, 94, 0.2)';
                navBadge.style.color = '#4ade80';
            }

            if (actionBox) actionBox.style.display = 'none';

            if (showNotification) {
                showUpdateToast("Votre royaume féodal est parfaitement à jour !", 'success');
            }
        }
    } catch (err) {
        logToConsole(`Erreur : ${err.message}`, 'error');
        if (showNotification) {
            showUpdateToast(`Erreur de contrôle : ${err.message}`, 'error');
        }
    } finally {
        if (btnCheck) btnCheck.disabled = false;
        if (spinnerCheck) spinnerCheck.style.display = 'none';
    }
}

// 2. Installation de la mise à jour (git pull) avec confirmation modale Tabler
function installGitHubUpdate() {
    const modalEl = document.getElementById('modal-confirm-update-deploy');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    } else {
        executeInstallGitHubUpdate();
    }
}

async function executeInstallGitHubUpdate() {
    const modalEl = document.getElementById('modal-confirm-update-deploy');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
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

        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data && Array.isArray(data.logs)) {
            data.logs.forEach(l => logToConsole(l));
        }

        if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : "Échec de l'installation.");
        }

        showUpdateToast(data.message || "Mise à jour installée avec succès !", 'success');
        logToConsole(`Succès : Version installée ${data.current_commit || ''} !`);

        setTimeout(() => {
            window.location.reload();
        }, 1200);

    } catch (err) {
        logToConsole(`Erreur lors du déploiement : ${err.message}`, 'error');
        showUpdateToast(`Erreur : ${err.message}`, 'error');
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
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
        const data = await response.json();

        if (!data || !data.success) {
            throw new Error((data && data.error) ? data.error : "Erreur de sauvegarde.");
        }

        showUpdateToast(data.message || "Paramètres enregistrés.", 'success');
        logToConsole("Paramètres GitHub mis à jour avec succès.");
        const tokInput = form.querySelector('input[name="github_token"]');
        if (tokInput) tokInput.value = '';
        checkGitHubUpdates(false);
    } catch (err) {
        showUpdateToast(`Erreur : ${err.message}`, 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Lancement automatique du contrôle discret au chargement de l'onglet
document.addEventListener('DOMContentLoaded', () => {
    // Si l'onglet actif est 'updates', on vérifie automatiquement
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') === 'updates') {
            checkGitHubUpdates(false);
        }
    } catch (e) {
        console.warn("Erreur contrôle auto updates:", e);
    }
});
</script>

<!-- MODALE DE CONFIRMATION DE DÉPLOIEMENT TABLER.IO -->
<div class="modal modal-blur fade" id="modal-confirm-update-deploy" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-primary"></div>
            <div class="modal-body text-center py-4">
                <i class="fa-solid fa-cloud-arrow-down text-primary mb-2" style="font-size: 2.5rem;"></i>
                <h3>Confirmer la Mise à Jour</h3>
                <div class="text-secondary">
                    Voulez-vous lancer le téléchargement (<code>git pull</code>) et l'installation de la nouvelle version maintenant ?
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">Annuler</button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-primary w-100" onclick="executeInstallGitHubUpdate()">
                                <i class="fa-solid fa-check me-1"></i>Déployer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

