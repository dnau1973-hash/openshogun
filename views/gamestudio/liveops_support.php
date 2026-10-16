<?php
/**
 * Vue Partielle MVC : Support Daimyō & Compensations Impériales (Producteur & Live Ops)
 * Fichier : views/gamestudio/liveops_support.php
 */
require_once __DIR__ . '/../../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();

$searchQuery = trim($_GET['search_daimyo'] ?? '');
$searchResults = $liveOps->searchDaimyos($searchQuery, 15);
$history = $liveOps->getCompensationHistory(25);
$prefillUserId = isset($_GET['select_user_id']) ? (int)$_GET['select_user_id'] : null;
$prefillUsername = $_GET['select_username'] ?? '';
?>

<div class="gamestudio-liveops-support">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-purple d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-gift text-purple me-1"></i>Support Daimyō &amp; Décrets de Compensation
            </h3>
            <div class="text-secondary small mt-1">
                Inspection de domaines, attribution de dotations réparatrices et compensation collective suite à incident.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-clock-rotate-left me-1"></i><?= count($history) ?> Décret(s) Consigné(s)
            </span>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (isset($_SESSION['liveops_flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['liveops_flash']['type']) ?> alert-dismissible mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid <?= $_SESSION['liveops_flash']['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning' ?> fs-3"></i>
                <div><?= htmlspecialchars($_SESSION['liveops_flash']['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['liveops_flash']); ?>
    <?php endif; ?>

    <div class="row row-cards mb-4">
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE 1 : FORMULAIRE D'ÉMISSION DU DÉCRET DE COMPENSATION        -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark fw-bold m-0">
                        <i class="fa-solid fa-scroll text-warning me-2"></i>Émettre un Décret de Compensation Impériale
                    </h4>
                    <span class="badge bg-purple text-white">Live Ops</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary small mb-3">
                        Les richesses attribuées sont créditées immédiatement sur le fief principal du joueur (ou de chaque Daimyō en cas d'envoi collectif). Une lettre officielle du Shōgunat est expédiée dans leur boîte de messagerie.
                    </p>

                    <form method="POST">
                        <input type="hidden" name="action" value="send_compensation">

                        <!-- Sélecteur de destinataire -->
                        <div class="mb-3 p-3 rounded border bg-light">
                            <label class="form-label fw-bold text-dark mb-2">Destinataire du Décret :</label>
                            <div class="d-flex gap-3 align-items-center flex-wrap mb-2">
                                <label class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="radio" name="recipient_mode" value="single" id="modeSingle" <?= $prefillUserId ? 'checked' : 'checked' ?> onchange="toggleRecipientMode()">
                                    <span class="form-check-label fw-medium">Un Daimyō Spécifique</span>
                                </label>
                                <label class="form-check form-check-inline m-0">
                                    <input class="form-check-input text-danger" type="radio" name="recipient_mode" value="all" id="modeAll" onchange="toggleRecipientMode()">
                                    <span class="form-check-label text-danger fw-bold">⚡ Tous les Daimyōs (Attribution Collective)</span>
                                </label>
                            </div>

                            <div id="singleRecipientBox" class="mt-2">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="fa-solid fa-user"></i> ID Joueur</span>
                                            <input type="number" class="form-control" name="user_id" id="compUserId" value="<?= $prefillUserId ?: '' ?>" placeholder="ID">
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control form-control-sm" id="compUsername" value="<?= htmlspecialchars($prefillUsername) ?>" placeholder="Nom du Daimyō sélectionné" readonly style="background:#f1f5f9;">
                                    </div>
                                </div>
                                <div class="form-text text-muted small">Astuce : Utilisez la table de recherche ci-contre pour sélectionner un joueur en 1 clic.</div>
                            </div>

                            <input type="hidden" name="send_to_all" id="sendToAllHidden" value="0">
                        </div>

                        <!-- Grille des Dotations en Ressources -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark mb-2">Richesses &amp; Tributs Accordés :</label>
                            <div class="row g-2">
                                <!-- Bois -->
                                <div class="col-md-4">
                                    <label class="form-label small text-muted"><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" name="metal" id="inputMetal" value="0" min="0" step="500">
                                    </div>
                                    <div class="btn-group btn-group-xs mt-1 w-100" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputMetal', 5000)">+5k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputMetal', 20000)">+20k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputMetal', 50000)">+50k</button>
                                    </div>
                                </div>
                                <!-- Pierre -->
                                <div class="col-md-4">
                                    <label class="form-label small text-muted"><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Taille</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" name="crystal" id="inputCrystal" value="0" min="0" step="500">
                                    </div>
                                    <div class="btn-group btn-group-xs mt-1 w-100" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputCrystal', 5000)">+5k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputCrystal', 20000)">+20k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputCrystal', 50000)">+50k</button>
                                    </div>
                                </div>
                                <!-- Riz -->
                                <div class="col-md-4">
                                    <label class="form-label small text-muted"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" name="deuterium" id="inputDeut" value="0" min="0" step="500">
                                    </div>
                                    <div class="btn-group btn-group-xs mt-1 w-100" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputDeut', 5000)">+5k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputDeut', 20000)">+20k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputDeut', 50000)">+50k</button>
                                    </div>
                                </div>
                                <!-- Kobans -->
                                <div class="col-md-6 mt-3">
                                    <label class="form-label small text-muted"><i class="fa-solid fa-coins text-warning me-1"></i>Kobans d'Or (Trésorerie)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" name="gold" id="inputGold" value="0" min="0" step="1">
                                    </div>
                                    <div class="btn-group btn-group-xs mt-1 w-100" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputGold', 5)">+5</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputGold', 10)">+10</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputGold', 25)">+25</button>
                                    </div>
                                </div>
                                <!-- Honneur -->
                                <div class="col-md-6 mt-3">
                                    <label class="form-label small text-muted"><i class="fa-solid fa-award text-purple me-1"></i>Points d'Honneur Féodal</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" name="honor" id="inputHonor" value="0" min="0" step="10">
                                    </div>
                                    <div class="btn-group btn-group-xs mt-1 w-100" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputHonor', 25)">+25</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputHonor', 50)">+50</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-0" onclick="addAmount('inputHonor', 100)">+100</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Motif officiel -->
                        <div class="mb-4">
                            <label class="form-label required fw-bold text-dark">Motif du Décret Impérial</label>
                            <textarea class="form-control" name="reason" rows="2" placeholder="Ex: Dédommagement officiel suite à l'interruption de service et consolidation des remparts." required>Dédommagement officiel accordé par le Shōgunat pour manœuvre de maintenance impériale.</textarea>
                            <div class="form-text text-muted small">Ce motif est consigné dans le journal d'audit et inséré dans la lettre reçue par le joueur.</div>
                        </div>

                        <button type="submit" class="btn btn-purple text-white w-100 fw-bold py-2 shadow-sm" style="background:#7c3aed;" onclick="return confirm('Confirmez-vous l\'envoi immédiat de cette dotation impériale ?');">
                            <i class="fa-solid fa-gift me-2"></i>Signer &amp; Délivrer le Décret de Compensation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE 2 : RECHERCHE DAIMYŌ & SÉLECTION RAPIDE                    -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark fw-bold m-0">
                        <i class="fa-solid fa-magnifying-glass text-info me-2"></i>Recherche de Daimyō &amp; Fiefs
                    </h4>
                    <span class="badge bg-secondary-lt"><?= count($searchResults) ?> résultat(s)</span>
                </div>
                <div class="card-body p-3">
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="page" value="gamestudio">
                        <input type="hidden" name="tab" value="liveops_support">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_daimyo" placeholder="Pseudo, Email, Fief..." value="<?= htmlspecialchars($searchQuery) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>

                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-sm table-hover table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Daimyō</th>
                                    <th>Fief Principal</th>
                                    <th>Ressources</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($searchResults)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Aucun Daimyō trouvé pour cette recherche.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($searchResults as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($user['username']) ?></div>
                                                <span class="badge" style="font-size:0.65rem; background:rgba(0,0,0,0.06); color:#475569;">ID #<?= (int)$user['id'] ?></span>
                                            </td>
                                            <td>
                                                <div class="small fw-medium text-secondary"><?= htmlspecialchars($user['planet_name'] ?? 'Domaine') ?></div>
                                                <div class="text-muted" style="font-size:0.65rem;"><?= htmlspecialchars($user['faction'] ?? 'terran') ?></div>
                                            </td>
                                            <td style="font-size:0.75rem;">
                                                <div class="font-monospace text-success"><i class="fa-solid fa-tree me-1"></i><?= number_format((int)($user['metal'] ?? 0)) ?></div>
                                                <div class="font-monospace text-warning"><i class="fa-solid fa-wheat-awn me-1"></i><?= number_format((int)($user['deuterium'] ?? 0)) ?></div>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-purple" style="font-size:0.75rem; padding:0.2rem 0.5rem;"
                                                        onclick="selectDaimyo(<?= (int)$user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')">
                                                    <i class="fa-solid fa-arrow-left me-1"></i>Choisir
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SECTION 3 : JOURNAL D'AUDIT DES COMPENSATIONS PASSÉES ── -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="card-title text-dark fw-bold m-0">
                <i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Registre des Compensations &amp; Audit Log
            </h4>
            <span class="badge bg-secondary-lt"><?= count($history) ?> enregistrements</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Date &amp; Heure</th>
                        <th>Émetteur (Producteur)</th>
                        <th>Bénéficiaire</th>
                        <th>Dotation Créditée</th>
                        <th>Motif Officiel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fs-2 d-block mb-2"></i>
                                Aucun décret de compensation n'a encore été consigné.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="font-monospace small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-indigo-lt fw-bold"><?= htmlspecialchars($h['admin_name']) ?></span>
                                </td>
                                <td>
                                    <?php if ($h['recipient_type'] === 'all'): ?>
                                        <span class="badge bg-danger text-white"><i class="fa-solid fa-users me-1"></i>Tous les Daimyōs</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($h['recipient_name']) ?></span>
                                        <span class="badge bg-light text-secondary font-monospace" style="font-size:0.65rem;">#<?= (int)$h['recipient_id'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap font-monospace" style="font-size:0.75rem;">
                                        <?php if (!empty($h['resources']['metal'])): ?>
                                            <span class="badge bg-success-lt">🌲+<?= number_format($h['resources']['metal']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($h['resources']['crystal'])): ?>
                                            <span class="badge bg-secondary-lt">⛰️+<?= number_format($h['resources']['crystal']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($h['resources']['deuterium'])): ?>
                                            <span class="badge bg-warning-lt">🌾+<?= number_format($h['resources']['deuterium']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($h['resources']['gold'])): ?>
                                            <span class="badge bg-warning text-dark">🪙+<?= number_format($h['resources']['gold']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($h['resources']['honor'])): ?>
                                            <span class="badge bg-purple-lt">🎖️+<?= number_format($h['resources']['honor']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="small text-secondary" style="max-width:300px;">
                                    <?= htmlspecialchars($h['reason']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function selectDaimyo(userId, username) {
    document.getElementById('modeSingle').checked = true;
    toggleRecipientMode();
    document.getElementById('compUserId').value = userId;
    document.getElementById('compUsername').value = username;
}

function toggleRecipientMode() {
    const isAll = document.getElementById('modeAll').checked;
    const singleBox = document.getElementById('singleRecipientBox');
    const sendToAllHidden = document.getElementById('sendToAllHidden');
    if (isAll) {
        singleBox.style.display = 'none';
        sendToAllHidden.value = '1';
    } else {
        singleBox.style.display = 'block';
        sendToAllHidden.value = '0';
    }
}

function addAmount(inputId, amount) {
    const el = document.getElementById(inputId);
    if (el) {
        el.value = (parseInt(el.value) || 0) + amount;
    }
}
</script>

