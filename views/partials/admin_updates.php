<?php
/**
 * Vue Partielle d'Administration : Gestionnaire de Mises à Jour & Déploiement GitHub (OpenShogun)
 * Fichier : views/partials/admin_updates.php
 * Interface moderne, sobre et ergonomique pour le Game Studio
 */
require_once __DIR__ . '/../../core/UpdateEngine.php';

$updateEngine = new UpdateEngine();
$localInfo = $updateEngine->getLocalInfo();
$isClean = !empty($localInfo['is_clean']);
$dirtyCount = count($localInfo['dirty_files'] ?? []);
$repoFullName = htmlspecialchars(($localInfo['repo_owner'] ?? '') . '/' . ($localInfo['repo_name'] ?? ''));
$githubRepoUrl = "https://github.com/" . ($localInfo['repo_owner'] ?? 'dnau1973-hash') . "/" . ($localInfo['repo_name'] ?? 'openshogun');
?>

<div class="card border-0 shadow-sm mb-4">
    <!-- ── EN-TÊTE SOBRE & ACTIONS RAPIDES ── -->
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2.5">
            <span class="avatar avatar-sm bg-dark text-white rounded-2">
                <i class="fa-brands fa-github fs-3"></i>
            </span>
            <div>
                <h3 class="card-title text-dark fw-bold m-0" style="font-size: 1.15rem;">
                    Mises à Jour &amp; Déploiement GitHub
                </h3>
                <div class="text-secondary small mt-0.5">
                    Synchronisation Git en direct, inspection des commits et déploiement continu en 1 clic.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $githubRepoUrl ?>" target="_blank" rel="noopener noreferrer" 
               class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5" 
               title="Ouvrir le dépôt GitHub officiel dans un nouvel onglet">
                <i class="fa-brands fa-github"></i>
                <span class="d-none d-sm-inline">Dépôt GitHub</span>
                <i class="fa-solid fa-arrow-up-right-from-square small text-muted"></i>
            </a>
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-2 fw-semibold shadow-sm" 
                    onclick="checkGitHubUpdates(true)" id="btn-check-updates">
                <span id="spinner-check" style="display: none;" class="spinner-border spinner-border-sm"></span>
                <i class="fa-solid fa-arrows-rotate" id="icon-check-updates"></i>
                <span>Vérifier les Mises à Jour</span>
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- ── ALERTE PERMISSIONS LINUX (.git) SI BESOIN ── -->
        <?php if (isset($localInfo['is_git_writable']) && !$localInfo['is_git_writable']): ?>
            <div class="alert alert-danger d-flex align-items-start gap-3 mb-4 rounded-3 border-danger shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation fs-2 text-danger mt-1 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-title mb-1 text-danger fw-bold">Droits d'écriture restreints sur le dossier <code>.git</code></h5>
                    <div class="small text-secondary mb-2">
                        L'utilisateur système <code><?= htmlspecialchars($localInfo['web_user'] ?? 'www-data') ?></code> n'a pas les droits nécessaires pour écrire dans <code>.git/FETCH_HEAD</code>. La mise à jour via <code>git pull</code> nécessite une permission système.
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <code class="px-2.5 py-1.5 bg-dark text-warning rounded font-monospace small user-select-all" id="code-perm-cmd">
                            <?= htmlspecialchars($localInfo['permission_fix_cmd'] ?? 'sudo chown -R www-data:www-data /var/www/html') ?>
                        </code>
                        <button type="button" class="btn btn-sm btn-dark" onclick="copyPermissionCmd()" title="Copier la commande SSH">
                            <i class="fa-regular fa-copy me-1"></i>Copier
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── GRILLE DE 4 CARTES KPI SYNTHÉTIQUES & SOBRES ── -->
        <div class="row g-3 mb-4">
            <!-- 1. Version Active -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border bg-light-subtle h-100 rounded-3 shadow-none">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Version Locale (HEAD)
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            <code class="fs-3 fw-bold text-dark font-monospace" id="kpi-short-sha">
                                <?= htmlspecialchars($localInfo['short_sha'] ?? 'HEAD') ?>
                            </code>
                            <span class="badge bg-secondary-lt text-secondary font-monospace" style="font-size: 0.68rem;">Actif</span>
                        </div>
                        <div class="text-secondary small text-truncate mt-1" title="<?= htmlspecialchars($localInfo['commit_message'] ?? '') ?>">
                            <?= htmlspecialchars($localInfo['commit_message'] ?? 'Aucun message') ?>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($localInfo['human_date'] ?? 'Récemment') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Branche & Dépôt -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border bg-light-subtle h-100 rounded-3 shadow-none">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Branche Cible
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            <span class="badge bg-purple-lt text-purple font-monospace py-1 px-2 fs-4 fw-bold">
                                <i class="fa-solid fa-code-branch me-1"></i><?= htmlspecialchars($localInfo['branch'] ?? 'main') ?>
                            </span>
                        </div>
                        <div class="text-secondary small text-truncate mt-1" title="<?= $repoFullName ?>">
                            <?= $repoFullName ?>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-user-pen me-1"></i><?= htmlspecialchars($localInfo['author_name'] ?? 'Git') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Arbre de Travail Local -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border bg-light-subtle h-100 rounded-3 shadow-none">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Espace de Travail
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            <?php if ($isClean): ?>
                                <span class="badge bg-success-lt text-success py-1 px-2 fs-5 fw-semibold" id="kpi-workspace-badge">
                                    <i class="fa-solid fa-circle-check me-1"></i>Propre (Clean)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-lt text-warning py-1 px-2 fs-5 fw-semibold" id="kpi-workspace-badge" 
                                      title="<?= htmlspecialchars(implode(', ', $localInfo['dirty_files'] ?? [])) ?>">
                                    <i class="fa-solid fa-file-pen me-1"></i><?= $dirtyCount ?> modifiés
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-secondary small text-truncate mt-1">
                            <?= $isClean ? 'Fichiers synchronisés' : 'Modifications locales en cours' ?>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-box-archive me-1"></i>Auto-stash activé par défaut
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Statut Distant GitHub -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border bg-light-subtle h-100 rounded-3 shadow-none">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Statut Distant GitHub
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            <span id="remote-status-pill" class="badge bg-secondary-lt text-secondary py-1 px-2 fs-5 fw-semibold">
                                <i class="fa-solid fa-circle-question me-1"></i>En attente de contrôle
                            </span>
                        </div>
                        <div class="text-secondary small text-truncate mt-1" id="remote-status-detail">
                            Interrogation automatique au chargement...
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;" id="remote-last-check-text">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>Non contrôlé
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── BANNIÈRE DYNAMIQUE DE STATUT (DISCRÈTE & SOBRE) ── -->
        <div id="update-status-banner" class="alert alert-secondary d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 border py-2.5 px-3">
            <div class="d-flex align-items-center gap-3">
                <div id="banner-icon" class="fs-3 text-secondary">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
                <div>
                    <div id="banner-title" class="fw-bold text-dark">
                        Initialisation de la télémétrie Git...
                    </div>
                    <div id="banner-desc" class="small text-secondary mt-0.5">
                        Version locale active : <code class="text-primary font-monospace"><?= htmlspecialchars($localInfo['short_sha'] ?? 'HEAD') ?></code>
                        (<?= htmlspecialchars($localInfo['human_date'] ?? 'Récemment') ?>)
                    </div>
                </div>
            </div>
            <div id="banner-action" class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary-lt text-secondary font-monospace">
                    Branche <?= htmlspecialchars($localInfo['branch'] ?? 'main') ?>
                </span>
            </div>
        </div>

        <!-- ── ZONE DÉDIÉE : NOUVELLE MISE À JOUR PRÊTE AU DÉPLOIEMENT (Affichée si mise à jour dispo) ── -->
        <div id="update-action-box" class="card border border-primary shadow-sm mb-4 rounded-3" style="display: none;">
            <div class="card-header bg-primary-lt py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="card-title text-primary fw-bold d-flex align-items-center gap-2 m-0">
                        <i class="fa-solid fa-cloud-arrow-down text-primary"></i>
                        <span>Nouveaux Commits Prêts au Déploiement</span>
                    </h4>
                    <div id="update-behind-text" class="text-secondary small mt-0.5">
                        Votre version locale a des commits de retard par rapport à la branche distante.
                    </div>
                </div>
                <button type="button" class="btn btn-primary fw-bold shadow-sm d-flex align-items-center gap-2 px-3 py-2" 
                        onclick="installGitHubUpdate()" id="btn-install-update">
                    <span id="spinner-install" style="display: none;" class="spinner-border spinner-border-sm"></span>
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                    <span>Déployer la Mise à Jour Maintenant</span>
                </button>
            </div>

            <div class="card-body p-4">
                <!-- Option Auto-Stash -->
                <div class="p-3 bg-light rounded-2 border mb-3">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="auto-stash-check" checked>
                        <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="auto-stash-check">
                            Mettre automatiquement en réserve les modifications locales via <code>git stash</code> avant le déploiement (Recommandé)
                        </label>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.78rem;">
                        Préserve intacts vos fichiers locaux non committés et évite les conflits de fusion lors du <code>git pull</code>.
                    </div>
                </div>

                <!-- Table des commits à installer -->
                <div class="table-responsive border rounded-2" style="max-height: 260px; overflow-y: auto;">
                    <table class="table table-vcenter table-nowrap card-table table-hover mb-0">
                        <thead class="bg-light text-secondary small fw-bold">
                            <tr>
                                <th style="width: 100px;">SHA</th>
                                <th>Message du Commit</th>
                                <th style="width: 160px;">Auteur</th>
                                <th style="width: 80px;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="update-commits-tbody" class="font-monospace small">
                            <!-- Rempli dynamiquement par JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── CONSOLE D'EXÉCUTION & JOURNAL SYSTÈME (SOBRE & REPLIABLE) ── -->
        <div class="card border rounded-3 mb-4 shadow-none">
            <div class="card-header bg-light py-2.5 px-3 d-flex justify-content-between align-items-center cursor-pointer user-select-none" 
                 data-bs-toggle="collapse" data-bs-target="#collapseUpdateConsole" aria-expanded="true">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-terminal text-secondary small"></i>
                    <strong class="text-dark small">Console d'Exécution &amp; Journal Git</strong>
                    <span class="badge bg-secondary-lt text-secondary font-monospace" style="font-size: 0.65rem;" id="console-status-badge">
                        En attente
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                    <button type="button" class="btn btn-sm btn-ghost-secondary py-0.5 px-2 text-secondary" 
                            onclick="copyUpdateLogs()" title="Copier les logs">
                        <i class="fa-regular fa-copy me-1"></i>Copier
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary py-0.5 px-2 text-secondary" 
                            onclick="clearUpdateLogs()" title="Effacer la console">
                        <i class="fa-solid fa-trash-can me-1"></i>Effacer
                    </button>
                    <i class="fa-solid fa-chevron-down text-secondary small ms-1" id="chevronConsole"></i>
                </div>
            </div>
            <div class="collapse show" id="collapseUpdateConsole">
                <div class="card-body p-0">
                    <pre id="update-console-log" 
                         class="m-0 p-3 bg-dark font-monospace text-light" 
                         style="min-height: 90px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; font-size: 0.78rem; line-height: 1.55; border-radius: 0 0 8px 8px;">[<?= date('H:i:s') ?>] Prêt. Cliquez sur "Vérifier les Mises à Jour" pour interroger GitHub.</pre>
                </div>
            </div>
        </div>

        <!-- ── PARAMÈTRES DE CONNEXION GITHUB (VOLET REPLIABLE SOBRE) ── -->
        <div class="card border rounded-3 shadow-none">
            <div class="card-header bg-light py-2.5 px-3 d-flex justify-content-between align-items-center cursor-pointer user-select-none"
                 data-bs-toggle="collapse" data-bs-target="#collapseGitHubSettings" aria-expanded="false">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-secondary small"></i>
                    <strong class="text-dark small">Paramètres de Connexion GitHub &amp; Jeton PAT</strong>
                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.65rem;">
                        <?= htmlspecialchars($localInfo['target_branch'] ?? 'main') ?>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small d-none d-sm-inline">Configurer</span>
                    <i class="fa-solid fa-chevron-down text-secondary small" id="chevronSettings"></i>
                </div>
            </div>
            <div class="collapse" id="collapseGitHubSettings">
                <div class="card-body p-4">
                    <form id="github-settings-form" onsubmit="saveGitHubSettings(event)">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-secondary required">Propriétaire / Organisation</label>
                                <input type="text" name="github_repo_owner" 
                                       value="<?= htmlspecialchars($localInfo['repo_owner'] ?? '') ?>" 
                                       required class="form-control form-control-sm font-monospace" 
                                       placeholder="ex: dnau1973-hash">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-secondary required">Nom du Dépôt</label>
                                <input type="text" name="github_repo_name" 
                                       value="<?= htmlspecialchars($localInfo['repo_name'] ?? '') ?>" 
                                       required class="form-control form-control-sm font-monospace" 
                                       placeholder="ex: openshogun">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-secondary required">Branche Cible</label>
                                <input type="text" name="github_branch" 
                                       value="<?= htmlspecialchars($localInfo['target_branch'] ?? 'main') ?>" 
                                       required class="form-control form-control-sm font-monospace" 
                                       placeholder="main">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label small fw-semibold text-secondary">
                                    Token PAT GitHub
                                    <span class="text-muted fw-normal" style="font-size: 0.7rem;">(optionnel si public)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="github_token" id="inputGitHubToken"
                                           placeholder="<?= htmlspecialchars($localInfo['masked_token'] ?? 'Non configuré') ?>" 
                                           class="form-control form-control-sm font-monospace">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()" title="Afficher / Masquer">
                                        <i class="fa-solid fa-eye" id="iconToggleToken"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center pt-2 border-top mt-3">
                                <div class="text-muted small">
                                    <i class="fa-solid fa-shield-halved me-1 text-success"></i>Le jeton personnel GitHub est conservé de façon chiffrée et masqué dans les logs.
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">
                                    <i class="fa-solid fa-floppy-disk me-1.5"></i>Sauvegarder les Paramètres
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODALE DE CONFIRMATION DE DÉPLOIEMENT TABLER SOBRE ── -->
<div class="modal modal-blur fade" id="modal-confirm-update-deploy" tabindex="-1" role="dialog" aria-hidden="true" style="backdrop-filter: blur(4px);">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            <div class="modal-status bg-primary"></div>
            <div class="modal-body text-center py-4 px-3">
                <div class="avatar avatar-lg bg-primary-lt text-primary rounded-circle mx-auto mb-3">
                    <i class="fa-solid fa-cloud-arrow-down fs-2"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Confirmer le Déploiement</h3>
                <div class="text-secondary small">
                    Voulez-vous synchroniser et déployer la nouvelle version maintenant via <code>git pull</code> ?
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <div class="w-100 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-sm btn-primary flex-fill fw-bold" onclick="executeInstallGitHubUpdate()">
                        <i class="fa-solid fa-check me-1"></i>Déployer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── CLIENT JS : CENTRE DE MISES À JOUR & DÉPLOIEMENT GITHUB SOBRE & ERGONOMIQUE ──

function getAdminApiUrl(queryString = '') {
    const path = window.location.pathname;
    let base = path.substring(0, path.lastIndexOf('/') + 1);
    if (!base.endsWith('/')) base += '/';
    const cleanQuery = queryString ? (queryString.startsWith('?') ? queryString : '?' + queryString) : '';
    return base + 'api/admin.php' + cleanQuery;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function logToConsole(message, type = 'info') {
    const consoleEl = document.getElementById('update-console-log');
    if (!consoleEl) return;
    const now = new Date().toLocaleTimeString();
    const prefix = `[${now}] `;
    consoleEl.textContent += `\n${prefix}${message}`;
    consoleEl.scrollTop = consoleEl.scrollHeight;

    const badge = document.getElementById('console-status-badge');
    if (badge) {
        if (type === 'error') {
            badge.className = 'badge bg-danger-lt text-danger font-monospace';
            badge.textContent = 'Erreur';
        } else if (type === 'success') {
            badge.className = 'badge bg-success-lt text-success font-monospace';
            badge.textContent = 'Succès';
        } else if (type === 'working') {
            badge.className = 'badge bg-warning-lt text-warning font-monospace';
            badge.textContent = 'En cours...';
        }
    }
}

function clearUpdateLogs() {
    const consoleEl = document.getElementById('update-console-log');
    if (consoleEl) {
        const now = new Date().toLocaleTimeString();
        consoleEl.textContent = `[${now}] Console réinitialisée. Prêt.`;
    }
    const badge = document.getElementById('console-status-badge');
    if (badge) {
        badge.className = 'badge bg-secondary-lt text-secondary font-monospace';
        badge.textContent = 'Prêt';
    }
}

function copyUpdateLogs() {
    const consoleEl = document.getElementById('update-console-log');
    if (!consoleEl) return;
    navigator.clipboard.writeText(consoleEl.textContent).then(() => {
        showUpdateToast("Journal d'exécution copié dans le presse-papier.", "success");
    }).catch(() => {
        showUpdateToast("Impossible de copier le journal.", "error");
    });
}

function copyPermissionCmd() {
    const el = document.getElementById('code-perm-cmd');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        showUpdateToast("Commande SSH copiée dans le presse-papier.", "success");
    });
}

function toggleTokenVisibility() {
    const input = document.getElementById('inputGitHubToken');
    const icon = document.getElementById('iconToggleToken');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

// Notification Toast sobre (utilise window.showToast natif si disponible)
function showUpdateToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        try {
            window.showToast(message, type);
            return;
        } catch (_) {}
    }
    // Fallback minimaliste Tabler
    let container = document.getElementById('admin-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'admin-toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '99999';
        document.body.appendChild(container);
    }
    const toastEl = document.createElement('div');
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' || type === 'danger' ? 'alert-danger' : 'alert-info');
    toastEl.className = `alert ${alertClass} alert-dismissible shadow-sm fade show mb-2 py-2 px-3 rounded-2 small d-flex align-items-center gap-2`;
    toastEl.innerHTML = `<span>${escapeHtml(message)}</span><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>`;
    container.appendChild(toastEl);
    setTimeout(() => {
        toastEl.classList.remove('show');
        setTimeout(() => toastEl.remove(), 250);
    }, 4000);
}

// 1. Contrôle des mises à jour sur GitHub
async function checkGitHubUpdates(showNotification = true) {
    const btnCheck = document.getElementById('btn-check-updates');
    const spinnerCheck = document.getElementById('spinner-check');
    const iconCheck = document.getElementById('icon-check-updates');
    const pillRemote = document.getElementById('remote-status-pill');
    const remoteDetail = document.getElementById('remote-status-detail');
    const remoteLastCheck = document.getElementById('remote-last-check-text');
    const bannerBox = document.getElementById('update-status-banner');
    const bannerTitle = document.getElementById('banner-title');
    const bannerDesc = document.getElementById('banner-desc');
    const bannerIcon = document.getElementById('banner-icon');
    const actionBox = document.getElementById('update-action-box');
    const behindText = document.getElementById('update-behind-text');
    const commitsTbody = document.getElementById('update-commits-tbody');

    if (btnCheck) btnCheck.disabled = true;
    if (spinnerCheck) spinnerCheck.style.display = 'inline-block';
    if (iconCheck) iconCheck.style.display = 'none';

    logToConsole("Interrogation de l'API GitHub...", 'working');

    try {
        const response = await fetch(getAdminApiUrl('action=check_github_updates'), {
            credentials: 'same-origin'
        });

        let data = null;
        try {
            data = await response.json();
        } catch (_) {}

        if (!response.ok) {
            const serverMsg = data && data.error ? data.error : `Erreur serveur HTTP ${response.status}`;
            throw new Error(serverMsg);
        }

        if (!data || !data.success) {
            const errMsg = (data && data.error) ? data.error : "Impossible de contacter l'API GitHub.";
            throw new Error(errMsg);
        }

        const behindBy = Number(data.behind_by) || 0;
        const nowTime = new Date().toLocaleTimeString();
        if (remoteLastCheck) {
            remoteLastCheck.innerHTML = `<i class="fa-solid fa-clock-rotate-left me-1"></i>Vérifié à ${nowTime}`;
        }

        logToConsole(`Contrôle terminé. Statut distant : ${data.status || 'identical'} (Retard : ${behindBy} commit(s))`, 'success');

        const localShortSha = (data.local && data.local.short_sha) ? data.local.short_sha : 'Actuel';
        const localBranch = (data.local && data.local.target_branch) ? data.local.target_branch : 'main';

        if (data.has_update) {
            // NOUVELLE MISE À JOUR DISPONIBLE
            if (pillRemote) {
                pillRemote.className = 'badge bg-warning-lt text-warning py-1 px-2 fs-5 fw-semibold';
                pillRemote.innerHTML = `<i class="fa-solid fa-arrow-down me-1"></i>+${behindBy} commit(s)`;
            }
            if (remoteDetail) {
                remoteDetail.textContent = `${behindBy} nouveau(x) commit(s) sur ${localBranch}`;
            }

            // Bannière sobre d'alerte mise à jour
            if (bannerBox) {
                bannerBox.className = 'alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 border-warning bg-warning-lt text-dark py-2.5 px-3';
            }
            if (bannerIcon) {
                bannerIcon.className = 'fs-3 text-warning';
                bannerIcon.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i>';
            }
            if (bannerTitle) {
                bannerTitle.innerHTML = `<span class="text-dark fw-bold">Mise à jour disponible : ${behindBy} nouveau${behindBy > 1 ? 'x' : ''} commit${behindBy > 1 ? 's' : ''} sur origin/${localBranch}</span>`;
            }
            if (bannerDesc) {
                bannerDesc.textContent = `Dernier commit distant : "${data.remote ? data.remote.message : 'Nouveaux correctifs'}"`;
            }

            // Alimentation de la table des commits
            if (commitsTbody && Array.isArray(data.commits)) {
                commitsTbody.innerHTML = '';
                data.commits.forEach(c => {
                    const tr = document.createElement('tr');
                    const cUrl = c.html_url || `https://github.com/${data.local.repo_owner}/${data.local.repo_name}/commit/${c.sha}`;
                    tr.innerHTML = `
                        <td class="font-monospace">
                            <a href="${cUrl}" target="_blank" rel="noopener" class="badge bg-secondary-lt text-decoration-none">
                                ${escapeHtml(c.short_sha || '')}
                            </a>
                        </td>
                        <td class="text-dark fw-medium text-wrap" style="max-width: 480px;">
                            ${escapeHtml(c.message || '')}
                        </td>
                        <td class="text-secondary small">
                            <i class="fa-solid fa-user-pen me-1 text-muted"></i>${escapeHtml(c.author_name || '')}
                        </td>
                        <td class="text-end">
                            <a href="${cUrl}" target="_blank" rel="noopener" class="btn btn-xs btn-ghost-primary" title="Voir sur GitHub">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </td>
                    `;
                    commitsTbody.appendChild(tr);
                });
            }

            if (behindText) {
                behindText.textContent = `Votre environnement local a ${behindBy} commit(s) de retard par rapport à origin/${localBranch}.`;
            }

            if (actionBox) actionBox.style.display = 'block';

            if (showNotification) {
                showUpdateToast(`Nouvelle version prête au déploiement (${behindBy} commit(s)).`, 'warning');
            }

        } else {
            // ENVIRONNEMENT PARFAITEMENT À JOUR
            if (pillRemote) {
                pillRemote.className = 'badge bg-success-lt text-success py-1 px-2 fs-5 fw-semibold';
                pillRemote.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Synchronisé';
            }
            if (remoteDetail) {
                remoteDetail.textContent = `Synchronisé avec origin/${localBranch}`;
            }

            if (bannerBox) {
                bannerBox.className = 'alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 border-0 bg-success-lt text-success py-2.5 px-3';
            }
            if (bannerIcon) {
                bannerIcon.className = 'fs-3 text-success';
                bannerIcon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            }
            if (bannerTitle) {
                bannerTitle.innerHTML = `<span class="text-success fw-bold">OpenShogun est parfaitement à jour</span>`;
            }
            if (bannerDesc) {
                bannerDesc.textContent = `Votre version locale (${localShortSha}) correspond trait pour trait à la branche origin/${localBranch}.`;
            }

            if (actionBox) actionBox.style.display = 'none';

            if (showNotification) {
                showUpdateToast("OpenShogun est parfaitement synchronisé avec GitHub.", 'success');
            }
        }

    } catch (err) {
        logToConsole(`Erreur de contrôle : ${err.message}`, 'error');
        if (remoteDetail) remoteDetail.textContent = 'Erreur de connexion';
        if (pillRemote) {
            pillRemote.className = 'badge bg-danger-lt text-danger py-1 px-2 fs-5 fw-semibold';
            pillRemote.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Erreur';
        }
        if (showNotification) {
            showUpdateToast(`Erreur lors du contrôle : ${err.message}`, 'error');
        }
    } finally {
        if (btnCheck) btnCheck.disabled = false;
        if (spinnerCheck) spinnerCheck.style.display = 'none';
        if (iconCheck) iconCheck.style.display = 'inline-block';
    }
}

// 2. Déclenchement de la confirmation et exécution du déploiement
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

    logToConsole("Déploiement initié... Téléchargement et fusion Git (git pull)...", 'working');

    try {
        const formData = new FormData();
        formData.append('action', 'install_github_update');
        formData.append('auto_stash', autoStash ? '1' : '0');

        const response = await fetch(getAdminApiUrl(), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        let data = null;
        try {
            data = await response.json();
        } catch (_) {}

        if (!response.ok) {
            const errText = data && (data.error || data.message) ? (data.error || data.message) : `Erreur HTTP ${response.status}`;
            throw new Error(errText);
        }

        if (data && Array.isArray(data.logs)) {
            data.logs.forEach(l => logToConsole(l));
        }

        if (!data || !data.success) {
            if (data && data.fix_cmd) {
                logToConsole(`👉 Commande SSH requise : ${data.fix_cmd}`, 'error');
            }
            throw new Error((data && data.message) ? data.message : "Échec du déploiement.");
        }

        showUpdateToast(data.message || "Mise à jour déployée avec succès !", 'success');
        logToConsole(`Succès : Version déployée ${data.current_commit || ''} !`, 'success');

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
        const response = await fetch(getAdminApiUrl(), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        let data = null;
        try {
            data = await response.json();
        } catch (_) {}

        if (!response.ok) {
            const errText = data && (data.error || data.message) ? (data.error || data.message) : `Erreur HTTP ${response.status}`;
            throw new Error(errText);
        }

        if (!data || !data.success) {
            throw new Error((data && data.error) ? data.error : "Erreur de sauvegarde.");
        }

        showUpdateToast(data.message || "Paramètres GitHub enregistrés.", 'success');
        logToConsole("Configuration du dépôt GitHub mise à jour avec succès.", 'success');

        const tokInput = form.querySelector('input[name="github_token"]');
        if (tokInput) tokInput.value = '';

        checkGitHubUpdates(false);
    } catch (err) {
        showUpdateToast(`Erreur : ${err.message}`, 'error');
    }
}

// Contrôle discret automatique au chargement si l'onglet est actif
document.addEventListener('DOMContentLoaded', () => {
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') === 'updates' || params.get('subtab') === 'updates') {
            checkGitHubUpdates(false);
        }
    } catch (e) {
        console.warn("Erreur contrôle auto updates:", e);
    }
});
</script>
