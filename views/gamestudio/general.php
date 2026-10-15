<?php
/**
 * Vue Partielle MVC : Vue Générale du Game Studio (avec Sous-Menu)
 * Fichier : views/gamestudio/general.php
 */

$currentPageSlug = $page ?? 'gamestudio';
$currentSubTab = in_array($_GET['subtab'] ?? '', ['overview', 'backups'], true) ? $_GET['subtab'] : 'overview';
?>

<div class="gamestudio-general">
    <!-- En-tête de la Vue Générale -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h3 class="card-title text-indigo d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-sliders text-indigo me-1"></i>Configuration &amp; Administration Générale
            </h3>
            <div class="text-secondary small mt-1">
                Aperçu de l'environnement serveur, accréditations de l'équipe et centre de gestion des sauvegardes de la base de données.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-code-branch me-1"></i>Branche <?= htmlspecialchars($gitInfo['branch'] ?? 'main') ?> (<?= htmlspecialchars($gitInfo['short_sha'] ?? 'HEAD') ?>)
            </span>
        </div>
    </div>

    <!-- ── SOUS-MENU DE L'ONGLET GÉNÉRAL ── -->
    <div class="card card-tabs mb-4 border-0 shadow-sm">
        <ul class="nav nav-tabs nav-fill bg-white border-bottom px-2 pt-2" role="tablist">
            <li class="nav-item">
                <a href="?page=gamestudio&tab=general&subtab=overview" 
                   class="nav-link py-2 px-3 d-flex align-items-center justify-content-center gap-2 <?= $currentSubTab === 'overview' ? 'active fw-bold text-indigo border-bottom-0' : 'text-secondary' ?>">
                    <i class="fa-solid fa-gauge text-indigo"></i>
                    <span>Synthèse &amp; Dev Team</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=gamestudio&tab=general&subtab=backups" 
                   class="nav-link py-2 px-3 d-flex align-items-center justify-content-center gap-2 <?= $currentSubTab === 'backups' ? 'active fw-bold text-primary border-bottom-0' : 'text-secondary' ?>">
                    <i class="fa-solid fa-database text-primary"></i>
                    <span>Gestion des Sauvegardes DB</span>
                    <span class="badge bg-primary text-white ms-1"><?= count($backupSets ?? []) ?></span>
                </a>
            </li>
        </ul>
    </div>

    <?php if ($currentSubTab === 'overview'): ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SOUS-ONGLET 1 : SYNTHÈSE & DEV TEAM                                -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- ── CARTES KPIS GÉNÉRALES ── -->
        <div class="row row-cards mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border-start border-2 border-indigo shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small fw-bold text-uppercase">Version du Jeu</div>
                            <span class="avatar avatar-sm rounded bg-indigo-lt text-indigo fs-3"><i class="fa-solid fa-code"></i></span>
                        </div>
                        <div class="h1 m-0 font-weight-bold text-indigo">v<?= htmlspecialchars($gameVersion ?? '1.27.0') ?></div>
                        <div class="text-secondary small mt-2 pt-2 border-top">
                            <i class="fa-solid fa-server text-muted me-1"></i>PHP <?= htmlspecialchars($phpVersion ?? PHP_VERSION) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border-start border-2 border-primary shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small fw-bold text-uppercase">Comptes Humains</div>
                            <span class="avatar avatar-sm rounded bg-primary-lt text-primary fs-3"><i class="fa-solid fa-users"></i></span>
                        </div>
                        <div class="h1 m-0 font-weight-bold text-primary"><?= number_format((int)($totalUsers ?? 0)) ?></div>
                        <div class="text-secondary small mt-2 pt-2 border-top">
                            <i class="fa-solid fa-globe text-primary me-1"></i><?= number_format((int)($totalPlanets ?? 0)) ?> fiefs colonisés
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small fw-bold text-uppercase">Clans IA (Bots)</div>
                            <span class="avatar avatar-sm rounded bg-warning-lt text-warning fs-3"><i class="fa-solid fa-robot"></i></span>
                        </div>
                        <div class="h1 m-0 font-weight-bold text-warning"><?= number_format((int)($totalBots ?? 0)) ?></div>
                        <div class="text-secondary small mt-2 pt-2 border-top">
                            <i class="fa-solid fa-bolt text-warning me-1"></i>Simulation périodique
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border-start border-2 border-purple shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small fw-bold text-uppercase">Équipe Dev Team</div>
                            <span class="avatar avatar-sm rounded bg-purple-lt text-purple fs-3"><i class="fa-solid fa-hammer"></i></span>
                        </div>
                        <div class="h1 m-0 font-weight-bold text-purple"><?= count($devMembers ?? []) ?></div>
                        <div class="text-secondary small mt-2 pt-2 border-top">
                            <i class="fa-solid fa-id-badge text-purple me-1"></i>Membres accrédités
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TABLEAU DES ACCRÉDITATIONS STUDIO & DEVS ── -->
        <div class="row row-cards mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title m-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-user-gear text-purple me-1"></i>Accréditations de la Dev Team
                        </h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Rôles &amp; Métiers</th>
                                    <th>Niveau Forge</th>
                                    <th class="text-end">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devMembers)): ?>
                                    <tr><td colspan="4" class="text-center text-muted p-3">Aucun membre accrédité pour le moment.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($devMembers as $dm): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($dm['username'] ?? '') ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($dm['email'] ?? '') ?></div>
                                            </td>
                                            <td>
                                                <?php foreach ($dm['roles'] ?? [] as $role): ?>
                                                    <span class="badge bg-purple-lt me-1"><?= htmlspecialchars($role['name'] ?? '') ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-lt fw-bold">Niveau <?= (int)($dm['forge_level']['level'] ?? 1) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success-lt"><i class="fa-solid fa-check me-1"></i>Actif</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h4 class="card-title m-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-server text-info me-1"></i>Environnement &amp; Serveur
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">Serveur HTTP</span>
                                <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/Nginx') ?></strong>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">Version PHP</span>
                                <strong><?= htmlspecialchars(PHP_VERSION) ?></strong>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">Moteur de Base de Données</span>
                                <strong>MariaDB / MySQL</strong>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">Architecture Logique</span>
                                <span class="badge bg-indigo-lt fw-bold">MVC Modulaire (OpenShogun)</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">Dossier des Vues Studio</span>
                                <code class="text-indigo">views/gamestudio/</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($currentSubTab === 'backups'): ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SOUS-ONGLET 2 : GESTION DES SAUVEGARDES & RESTAURATION             -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="card mb-4 border-primary shadow-sm" id="section-db-backup">
            <div class="card-header bg-primary-lt d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title text-primary d-flex align-items-center gap-2 m-0" style="font-size:1.15rem;">
                        <i class="fa-solid fa-database text-primary me-1"></i>Sauvegarde &amp; Restauration de la Base de Données
                    </h3>
                    <div class="text-secondary small mt-1">
                        Sauvegardes autonomes conformes aux décrets shogunaux : la structure DDL et les données DML sont exportées distinctement. Fichiers stockés dans <code>database/backups/</code> et répliqués automatiquement sur support externe USB.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary text-white" id="badgeBackupCount"><?= count($backupSets ?? []) ?> Jeu(x) de Sauvegarde</span>
                    <?php if (!empty($detectedUsbDrives)): ?>
                        <span class="badge bg-success text-white" id="badgeUsbStatus"><i class="fa-solid fa-usb me-1"></i>USB Connecté</span>
                    <?php else: ?>
                        <span class="badge bg-secondary text-white" id="badgeUsbStatus"><i class="fa-solid fa-hard-drive me-1"></i>Stockage Interne</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <!-- État du Support USB & Destination -->
                <div class="row mb-3 g-3 align-items-center">
                    <div class="col-md-7">
                        <div id="usbDetectionAlert">
                            <?php if (!empty($detectedUsbDrives)): ?>
                                <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2">
                                    <i class="fa-solid fa-usb fs-2 text-success"></i>
                                    <div>
                                        <strong>Support(s) de stockage USB externe(s) détecté(s) :</strong>
                                        <div class="font-monospace small">
                                            <?= implode(' &bull; ', array_map('htmlspecialchars', $detectedUsbDrives)) ?>
                                        </div>
                                        <span class="text-muted small">Les sauvegardes y seront dupliquées automatiquement dans <code>openshogun_backups/</code>.</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2">
                                    <i class="fa-solid fa-circle-info fs-2 text-info"></i>
                                    <div>
                                        <strong>Aucun support USB externe détecté automatiquement (/media, /mnt).</strong>
                                        <span class="text-muted small d-block">La sauvegarde sera enregistrée dans le dossier local du jeu (<code>database/backups/</code>).</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-secondary mb-1">Chemin USB / Disque externe personnalisé (optionnel) :</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fa-solid fa-folder-tree"></i></span>
                            <input type="text" id="customUsbPathInput" class="form-control form-control-sm" placeholder="ex: /media/usb-disk ou /mnt/backup" value="<?= !empty($detectedUsbDrives) ? htmlspecialchars($detectedUsbDrives[0]) : '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Déclenchement de Sauvegarde -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-light rounded border mb-4">
                    <div>
                        <div class="fw-bold text-dark"><i class="fa-solid fa-shield-halved text-success me-1"></i>Créer une nouvelle sauvegarde immédiate</div>
                        <div class="text-secondary small">Génère instantanément deux fichiers SQL distincts : <code>structure_...sql</code> (DDL) et <code>data_...sql</code> (DML).</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" onclick="refreshBackupList()" class="btn btn-outline-secondary btn-sm" id="btnRefreshBackups" title="Actualiser la liste">
                            <i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser
                        </button>
                        <button type="button" onclick="createDatabaseBackup()" id="btnCreateBackup" class="btn btn-primary fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Lancer une Sauvegarde Immédiate
                        </button>
                    </div>
                </div>

                <!-- Tableau des sauvegardes existantes -->
                <h4 class="card-title text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-secondary"></i>Historique des Sauvegardes Disponibles
                </h4>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover table-striped card-table border">
                        <thead class="table-light">
                            <tr>
                                <th>Date / Horodatage</th>
                                <th>Structure DDL</th>
                                <th>Données DML</th>
                                <th>État &amp; Intégrité</th>
                                <th class="text-end">Actions de Réintégration</th>
                            </tr>
                        </thead>
                        <tbody id="backupSetsTableBody">
                            <?php if (empty($backupSets)): ?>
                                <tr id="noBackupsRow">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-inbox fs-2 mb-2 d-block"></i>
                                        Aucune sauvegarde trouvée dans <code>database/backups/</code>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupSets as $bSet): 
                                    $hasStruct = !empty($bSet['structure']);
                                    $hasData = !empty($bSet['data']);
                                    $structFile = $hasStruct ? $bSet['structure']['filename'] : '';
                                    $dataFile = $hasData ? $bSet['data']['filename'] : '';
                                    $structSize = $hasStruct ? round($bSet['structure']['size'] / 1024, 1) . ' Ko' : '-';
                                    $dataSize = $hasData ? round($bSet['data']['size'] / 1024, 1) . ' Ko' : '-';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($bSet['date'] ?? $bSet['timestamp']) ?></div>
                                            <div class="text-muted small font-monospace"><?= htmlspecialchars($bSet['timestamp']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($hasStruct): ?>
                                                <span class="badge bg-azure-lt font-monospace text-dark">
                                                    <i class="fa-solid fa-sitemap me-1 text-primary"></i><?= htmlspecialchars($structFile) ?>
                                                </span>
                                                <span class="small text-muted ms-1">(<?= $structSize ?>)</span>
                                            <?php else: ?>
                                                <span class="text-muted small"><em>Non disponible</em></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasData): ?>
                                                <span class="badge bg-green-lt font-monospace text-dark">
                                                    <i class="fa-solid fa-database me-1 text-success"></i><?= htmlspecialchars($dataFile) ?>
                                                </span>
                                                <span class="small text-muted ms-1">(<?= $dataSize ?>)</span>
                                            <?php else: ?>
                                                <span class="text-muted small"><em>Non disponible</em></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasStruct && $hasData): ?>
                                                <span class="badge bg-success-lt text-success fw-bold">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Paire Complète
                                                </span>
                                            <?php elseif ($hasStruct): ?>
                                                <span class="badge bg-azure-lt text-primary">Structure Seule</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-lt text-warning">Données Seules</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($hasStruct && $hasData): ?>
                                                    <button type="button" class="btn btn-outline-success fw-bold" 
                                                            onclick="openRestoreModal('<?= htmlspecialchars($structFile, ENT_QUOTES) ?>', '<?= htmlspecialchars($dataFile, ENT_QUOTES) ?>', 'complete', '<?= htmlspecialchars($bSet['date'] ?? $bSet['timestamp'], ENT_QUOTES) ?>')">
                                                        <i class="fa-solid fa-rotate-left me-1"></i>Restaurer Complet
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($hasData): ?>
                                                    <button type="button" class="btn btn-outline-secondary" 
                                                            onclick="openRestoreModal('', '<?= htmlspecialchars($dataFile, ENT_QUOTES) ?>', 'data', '<?= htmlspecialchars($bSet['date'] ?? $bSet['timestamp'], ENT_QUOTES) ?>')">
                                                        <i class="fa-solid fa-database me-1"></i>Données
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($hasStruct): ?>
                                                    <button type="button" class="btn btn-outline-secondary" 
                                                            onclick="openRestoreModal('<?= htmlspecialchars($structFile, ENT_QUOTES) ?>', '', 'structure', '<?= htmlspecialchars($bSet['date'] ?? $bSet['timestamp'], ENT_QUOTES) ?>')">
                                                        <i class="fa-solid fa-sitemap me-1"></i>Structure
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── MODALE DE CONFIRMATION DE RESTAURATION ── -->
        <div class="modal modal-blur fade" id="restoreBackupModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg border-warning">
                    <div class="modal-header bg-warning-subtle">
                        <h5 class="modal-title d-flex align-items-center gap-2 text-warning fw-bold m-0">
                            <i class="fa-solid fa-rotate-left text-warning me-1"></i>Restauration de la Base de Données
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" onclick="closeRestoreModal()"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex">
                                <div><i class="fa-solid fa-triangle-exclamation text-warning fs-1"></i></div>
                                <div class="ms-2">
                                    <strong>Opération Critique :</strong> Vous vous apprêtez à réintégrer une sauvegarde dans la base de données active.
                                    Les tables et enregistrements actuels seront remplacés par l'état archivé.
                                </div>
                            </div>
                        </div>

                        <div class="card card-body bg-light mb-3 p-3 border">
                            <div class="small mb-1">Date de la sauvegarde : <strong id="restoreTargetDate">-</strong></div>
                            <div class="small mb-1">Mode sélectionné : <span class="badge bg-secondary" id="restoreTargetModeBadge">-</span></div>
                            <div class="small font-monospace text-truncate text-muted" id="restoreTargetFiles" style="white-space:pre-wrap;">-</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label font-weight-medium">
                                Pour confirmer la réintégration, tapez <code>RESTORE</code> en majuscules :
                            </label>
                            <input type="text" id="restoreKeywordInput" class="form-control text-center font-monospace" placeholder="RESTORE" autocomplete="off" style="font-size:1.1rem; letter-spacing:2px; font-weight:800;">
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between bg-light">
                        <button type="button" onclick="closeRestoreModal()" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" onclick="executeRestoreBackup()" id="btnConfirmRestore" class="btn btn-warning fw-bold text-dark">
                            <i class="fa-solid fa-rotate-left me-1"></i>Confirmer la Réintégration
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── SCRIPT JS GESTION DES SAUVEGARDES ── -->
        <script>
        let currentRestoreContext = {
            structureFile: '',
            dataFile: '',
            mode: 'complete',
            date: ''
        };

        function showBackupAlert(title, message, type = 'info') {
            if (typeof showModalAlert === 'function') {
                showModalAlert(title, message, type);
            } else if (typeof showToast === 'function') {
                showToast(message, type, title);
            } else {
                alert(title + ' : ' + message.replace(/<[^>]*>?/gm, ''));
            }
        }

        function openRestoreModal(structureFile, dataFile, mode, date) {
            currentRestoreContext = { structureFile, dataFile, mode, date };
            const dateEl = document.getElementById('restoreTargetDate');
            if (dateEl) dateEl.innerText = date || 'Sauvegarde sélectionnée';
            
            let modeLabel = 'Complète (Structure + Données)';
            if (mode === 'data') modeLabel = 'Données seules';
            if (mode === 'structure') modeLabel = 'Structure seule';
            const modeBadge = document.getElementById('restoreTargetModeBadge');
            if (modeBadge) modeBadge.innerText = modeLabel;

            let filesDesc = '';
            if (structureFile) filesDesc += `Structure : ${structureFile}\n`;
            if (dataFile) filesDesc += `Données : ${dataFile}`;
            const filesEl = document.getElementById('restoreTargetFiles');
            if (filesEl) filesEl.innerText = filesDesc;

            const keyInput = document.getElementById('restoreKeywordInput');
            if (keyInput) keyInput.value = '';

            const modalEl = document.getElementById('restoreBackupModal');
            if (modalEl) {
                if (window.bootstrap && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                }
            }
        }

        function closeRestoreModal() {
            const modalEl = document.getElementById('restoreBackupModal');
            if (modalEl) {
                if (window.bootstrap && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                }
            }
        }

        async function createDatabaseBackup() {
            const btn = document.getElementById('btnCreateBackup');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i>Sauvegarde en cours...`;
            }

            const customUsb = document.getElementById('customUsbPathInput')?.value.trim() || '';

            try {
                const formData = new FormData();
                formData.append('action', 'backup_database');
                if (customUsb) {
                    formData.append('custom_usb_path', customUsb);
                }

                const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    const r = json.result || {};
                    let usbMsg = (r.usb_found && r.usb_copies && r.usb_copies.length > 0)
                        ? `<br><i class="fa-solid fa-usb text-success me-1"></i><strong>Dupliquée sur support USB</strong> : ${r.usb_copies.map(u => u.dir).join(', ')}`
                        : `<br><i class="fa-solid fa-info-circle text-secondary me-1"></i>Stockée localement dans <code>database/backups/</code>`;
                    showBackupAlert(
                        "Sauvegarde Réussie",
                        `La structure et les données ont été sauvegardées avec succès !<br>
                         Horodatage : <strong>${r.timestamp || ''}</strong><br>
                         Structure : <code>${r.local ? r.local.structure.split('/').pop() : ''}</code><br>
                         Données : <code>${r.local ? r.local.data.split('/').pop() : ''}</code>
                         ${usbMsg}`,
                        "success"
                    );
                    await refreshBackupList();
                } else {
                    showBackupAlert("Échec de la Sauvegarde", json.error || "Une erreur est survenue lors de l'exportation de la base.", "danger");
                }
            } catch (e) {
                showBackupAlert("Erreur Réseau", "Impossible de joindre le serveur pour exécuter la sauvegarde.", "danger");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            }
        }

        async function refreshBackupList() {
            const btn = document.getElementById('btnRefreshBackups');
            if (btn) btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i>Actualisation...`;

            try {
                const res = await fetch('/api/admin.php?action=list_backups');
                const json = await res.json();
                if (json.success) {
                    renderBackupSets(json.backups, json.usb_drives);
                }
            } catch (e) {
                console.error("Erreur lors de la mise à jour des sauvegardes:", e);
            } finally {
                if (btn) btn.innerHTML = `<i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser`;
            }
        }

        function renderBackupSets(backups, usbDrives) {
            const tbody = document.getElementById('backupSetsTableBody');
            if (!tbody) return;

            const safeEscape = (str) => String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

            // Mise à jour de l'alerte USB
            const usbAlert = document.getElementById('usbDetectionAlert');
            const badgeUsb = document.getElementById('badgeUsbStatus');
            if (usbAlert && usbDrives) {
                if (usbDrives.length > 0) {
                    usbAlert.innerHTML = `
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2">
                            <i class="fa-solid fa-usb fs-2 text-success"></i>
                            <div>
                                <strong>Support(s) de stockage USB externe(s) détecté(s) :</strong>
                                <div class="font-monospace small">${usbDrives.map(u => safeEscape(u)).join(' &bull; ')}</div>
                                <span class="text-muted small">Les sauvegardes y seront dupliquées automatiquement dans <code>openshogun_backups/</code>.</span>
                            </div>
                        </div>
                    `;
                    if (badgeUsb) {
                        badgeUsb.className = "badge bg-success text-white";
                        badgeUsb.innerHTML = `<i class="fa-solid fa-usb me-1"></i>USB Connecté`;
                    }
                } else {
                    usbAlert.innerHTML = `
                        <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2">
                            <i class="fa-solid fa-circle-info fs-2 text-info"></i>
                            <div>
                                <strong>Aucun support USB externe détecté automatiquement (/media, /mnt).</strong>
                                <span class="text-muted small d-block">La sauvegarde sera enregistrée dans le dossier local du jeu (<code>database/backups/</code>).</span>
                            </div>
                        </div>
                    `;
                    if (badgeUsb) {
                        badgeUsb.className = "badge bg-secondary text-white";
                        badgeUsb.innerHTML = `<i class="fa-solid fa-hard-drive me-1"></i>Stockage Interne`;
                    }
                }
            }

            const sets = {};
            (backups.structure || []).forEach(s => {
                const ts = s.filename.replace('structure_', '').replace('.sql', '');
                sets[ts] = sets[ts] || { timestamp: ts, date: s.date, mtime: s.mtime };
                sets[ts].structure = s;
            });
            (backups.data || []).forEach(d => {
                const ts = d.filename.replace('data_', '').replace('.sql', '');
                sets[ts] = sets[ts] || { timestamp: ts, date: d.date, mtime: d.mtime };
                sets[ts].data = d;
            });

            const setList = Object.values(sets).sort((a, b) => (b.mtime || 0) - (a.mtime || 0));

            const countBadge = document.getElementById('badgeBackupCount');
            if (countBadge) countBadge.innerText = `${setList.length} Jeu(x) de Sauvegarde`;

            if (setList.length === 0) {
                tbody.innerHTML = `
                    <tr id="noBackupsRow">
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fa-solid fa-inbox fs-2 mb-2 d-block"></i>
                            Aucune sauvegarde trouvée dans <code>database/backups/</code>.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            setList.forEach(s => {
                const hasStruct = !!s.structure;
                const hasData = !!s.data;
                const structFile = hasStruct ? s.structure.filename : '';
                const dataFile = hasData ? s.data.filename : '';
                const structSize = hasStruct ? (s.structure.size / 1024).toFixed(1) + ' Ko' : '-';
                const dataSize = hasData ? (s.data.size / 1024).toFixed(1) + ' Ko' : '-';
                const dateStr = s.date || s.timestamp;

                let integrityBadge = '';
                if (hasStruct && hasData) {
                    integrityBadge = `<span class="badge bg-success-lt text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Paire Complète</span>`;
                } else if (hasStruct) {
                    integrityBadge = `<span class="badge bg-azure-lt text-primary">Structure Seule</span>`;
                } else {
                    integrityBadge = `<span class="badge bg-warning-lt text-warning">Données Seules</span>`;
                }

                html += `
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">${safeEscape(dateStr)}</div>
                            <div class="text-muted small font-monospace">${safeEscape(s.timestamp)}</div>
                        </td>
                        <td>
                            ${hasStruct ? `
                                <span class="badge bg-azure-lt font-monospace text-dark">
                                    <i class="fa-solid fa-sitemap me-1 text-primary"></i>${safeEscape(structFile)}
                                </span>
                                <span class="small text-muted ms-1">(${structSize})</span>
                            ` : '<span class="text-muted small"><em>Non disponible</em></span>'}
                        </td>
                        <td>
                            ${hasData ? `
                                <span class="badge bg-green-lt font-monospace text-dark">
                                    <i class="fa-solid fa-database me-1 text-success"></i>${safeEscape(dataFile)}
                                </span>
                                <span class="small text-muted ms-1">(${dataSize})</span>
                            ` : '<span class="text-muted small"><em>Non disponible</em></span>'}
                        </td>
                        <td>${integrityBadge}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                ${(hasStruct && hasData) ? `
                                    <button type="button" class="btn btn-outline-success fw-bold"
                                            onclick="openRestoreModal('${safeEscape(structFile)}', '${safeEscape(dataFile)}', 'complete', '${safeEscape(dateStr)}')">
                                        <i class="fa-solid fa-rotate-left me-1"></i>Restaurer Complet
                                    </button>
                                ` : ''}
                                ${hasData ? `
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="openRestoreModal('', '${safeEscape(dataFile)}', 'data', '${safeEscape(dateStr)}')">
                                        <i class="fa-solid fa-database me-1"></i>Données
                                    </button>
                                ` : ''}
                                ${hasStruct ? `
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="openRestoreModal('${safeEscape(structFile)}', '', 'structure', '${safeEscape(dateStr)}')">
                                        <i class="fa-solid fa-sitemap me-1"></i>Structure
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        async function executeRestoreBackup() {
            const keyword = document.getElementById('restoreKeywordInput')?.value.trim();
            if (keyword !== 'RESTORE') {
                showBackupAlert("Confirmation Invalide", "Vous devez impérativement saisir le mot <strong>RESTORE</strong> en majuscules pour déverrouiller la réintégration.", "danger");
                return;
            }

            const btn = document.getElementById('btnConfirmRestore');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i>Restauration en cours...`;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'restore_backup');
                formData.append('mode', currentRestoreContext.mode);
                formData.append('structure_file', currentRestoreContext.structureFile);
                formData.append('data_file', currentRestoreContext.dataFile);

                const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
                const json = await res.json();

                closeRestoreModal();

                if (json.success) {
                    showBackupAlert(
                        "Restauration Terminée",
                        json.message || "La base de données a été réintégrée avec succès !",
                        "success"
                    );
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    showBackupAlert("Échec de la Restauration", json.error || "Une erreur est survenue lors de l'application du fichier SQL.", "danger");
                }
            } catch (e) {
                closeRestoreModal();
                showBackupAlert("Erreur Réseau", "Une erreur est survenue lors de la communication avec le serveur.", "danger");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            }
        }
        </script>
    <?php endif; ?>
</div>
