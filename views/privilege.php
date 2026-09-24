<?php
/**
 * Page Dédiée : Privilèges du Shōgun & Sceau Impérial (Sengoku Plus)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user) {
    header('Location: /');
    exit;
}

$sealEngine = new ImperialSealEngine();
$sealStatus = $sealEngine->getSealStatus((int)$user['id']);
$isSealActive = $sealStatus['active'];
$goldCoins = (int)$sealStatus['gold'];
?>

<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="text-muted small">Cour Suprême du Shogunat &bull; Intendance Impériale</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span>👑</span> Les Privilèges du Shōgun (Sceau Impérial)
                <?php if ($isSealActive): ?>
                    <span class="badge bg-warning text-dark fs-5 shadow-sm">👑 Sceau Actif</span>
                <?php else: ?>
                    <span class="badge bg-secondary-lt fs-5">Régime Ordinaire</span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark text-warning p-2 fs-5 border border-warning shadow-sm">
                    🪙 <?= number_format($goldCoins) ?> Koban
                </span>
                <a href="?page=empire" class="btn btn-outline-warning">
                    👑 Grand Tableau de Bord
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ================= BANNIÈRE STATUT & TRÉSOR DU DAIMYŌ ================= -->
<div class="row row-cards mb-4">
    <!-- Colonne 1 : Statut du Sceau -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0" 
             style="background: <?= $isSealActive ? 'linear-gradient(135deg, #1c1917 0%, #292524 60%, #451a03 100%)' : 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)' ?>; color:#ffffff; border-radius:12px; overflow:hidden;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="letter-spacing:1px;">
                            <?= $isSealActive ? 'Investiture Active' : 'Sceau Inactif' ?>
                        </span>
                        <span class="fs-1">👑</span>
                    </div>
                    <h3 class="card-title text-white fs-2 mb-2">
                        <?= $isSealActive ? 'Sceau Impérial en Vigueur' : 'Aucun Sceau Proclamé' ?>
                    </h3>
                    <p class="text-secondary small mb-3" style="color: #cbd5e1 !important; line-height:1.6;">
                        <?= $isSealActive 
                            ? 'Vos maîtres d\'œuvre, intendants et généraux appliquent sans réserve les décrets impériaux sur l\'ensemble de vos domaines.' 
                            : 'Activez le Sceau Impérial pour débloquer l\'Architecte de Cour (file jusqu\'à 4 chantiers), le Carnet de Raids en 1 clic et l\'Évasion de Garnison.' ?>
                    </p>
                </div>
                <div class="p-3 rounded" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="small text-uppercase text-warning fw-bold">Durée restante</div>
                            <div class="fs-3 fw-bold font-monospace mt-1">
                                <?= htmlspecialchars($sealStatus['remaining_formatted']) ?>
                            </div>
                        </div>
                        <?php if ($isSealActive): ?>
                            <div class="text-end text-muted small">
                                Échéance : <strong><?= htmlspecialchars($sealStatus['until']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne 2 : Trésor en Koban & Tributs -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border" style="border-radius:12px;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning-lt text-warning fw-bold text-uppercase">Monnaie du Shogunat</span>
                        <span class="fs-1">🪙</span>
                    </div>
                    <h3 class="card-title text-dark fs-2 mb-1">
                        <?= number_format($goldCoins) ?> Koban
                    </h3>
                    <div class="text-secondary small mb-3" style="line-height:1.6;">
                        Le Koban est la monnaie officielle de la Cour Impériale. Elle ne dépend d'aucun paiement obligatoire : chaque daimyō gagne des Koban via les quêtes, les médailles et le tribut quotidien.
                    </div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <div class="p-3 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong class="d-block text-dark">🎁 Tribut Quotidien de Fidélité</strong>
                            <span class="small text-muted">+5 Koban offerts chaque jour de connexion</span>
                        </div>
                        <?php if (!empty($sealStatus['can_claim_daily'])): ?>
                            <button type="button" class="btn btn-warning fw-bold shadow-sm" id="btnClaimDailyGold" onclick="claimDailyGold()">
                                🪙 Réclamer mon Tribut (+5 Koban)
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success-lt p-2">✓ Tribut déjà perçu aujourd'hui</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted italic text-center">
                        🎖️ <strong>Exploits de Guerre :</strong> Chaque médaille d'honneur remportée vous rapporte <strong>+100 Koban</strong> !
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= 3 FORMULES D'INVESTITURE IMPÉRIALE ================= -->
<div class="card mb-4 shadow-sm border">
    <div class="card-header bg-warning-subtle py-3">
        <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
            <span>📜</span> Décréter ou Prolonger le Sceau Impérial
        </h3>
        <div class="card-options text-muted small">Aucun paiement en argent réel requis</div>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">
            <!-- Offre 1 : 7 Jours -->
            <div class="col-md-4">
                <div class="card h-100 border text-center p-3 hover-shadow" style="border-radius:10px; transition: transform 0.15s ease;">
                    <div class="text-secondary small fw-bold text-uppercase">Investiture Découverte</div>
                    <div class="fs-1 fw-bold text-dark my-2">7 Jours</div>
                    <div class="fs-3 fw-bold text-warning mb-2">50 Koban</div>
                    <p class="text-secondary small mb-3" style="line-height:1.5;">
                        Parfait pour un démarrage rapide, accélérer l'annexion d'un second fief ou soutenir une guerre de frontière.
                    </p>
                    <button type="button" class="btn btn-outline-warning w-100 fw-bold" onclick="activateSeal(7)">
                        👑 Proclamer pour 7 Jours
                    </button>
                </div>
            </div>

            <!-- Offre 2 : 14 Jours (Populaire) -->
            <div class="col-md-4">
                <div class="card h-100 border border-warning text-center p-3 position-relative shadow-sm" 
                     style="background: #fffdfa; border-radius:10px; border-width:2px !important;">
                    <span class="badge bg-warning text-dark position-absolute top-0 start-50 translate-middle fw-bold px-3 py-1 shadow-sm">
                        ⭐ ÉCONOMIE -10%
                    </span>
                    <div class="text-secondary small fw-bold text-uppercase mt-2">Investiture Royale</div>
                    <div class="fs-1 fw-bold text-dark my-2">14 Jours</div>
                    <div class="fs-3 fw-bold text-warning mb-2">90 Koban</div>
                    <p class="text-secondary small mb-3" style="line-height:1.5;">
                        La formule recommandée par les conseillers du Shōgun pour fortifier vos cités et planifier vos chantiers sur deux semaines.
                    </p>
                    <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm" onclick="activateSeal(14)">
                        👑 Proclamer pour 14 Jours
                    </button>
                </div>
            </div>

            <!-- Offre 3 : 30 Jours -->
            <div class="col-md-4">
                <div class="card h-100 border text-center p-3 hover-shadow" style="border-radius:10px; transition: transform 0.15s ease;">
                    <span class="badge bg-purple-lt text-purple position-absolute top-0 start-50 translate-middle fw-bold px-3 py-1">
                        👑 PRESTIGE -25%
                    </span>
                    <div class="text-secondary small fw-bold text-uppercase mt-2">Investiture Mensuelle</div>
                    <div class="fs-1 fw-bold text-dark my-2">30 Jours</div>
                    <div class="fs-3 fw-bold text-warning mb-2">150 Koban</div>
                    <p class="text-secondary small mb-3" style="line-height:1.5;">
                        La tranquillité absolue pour régner un mois complet avec la plénitude de tous les privilèges et le carnet de raids illimité.
                    </p>
                    <button type="button" class="btn btn-outline-warning w-100 fw-bold" onclick="activateSeal(30)">
                        👑 Proclamer pour 30 Jours
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= PRÉSENTATION DES 6 GRANDS PRIVILÈGES IMPÉRIAUX ================= -->
<div class="mb-4">
    <h3 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
        <span>⭐</span> Guide des 6 Grands Privilèges du Shōgun
    </h3>

    <div class="row g-3">
        <!-- 1. Architecte de Cour -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #3b82f6 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🔨</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">1. Architecte de Cour</h4>
                        <span class="badge bg-blue-lt">File Étendue (x4)</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Enchaînez jusqu'à <strong>4 constructions</strong> (jusqu'à 2 parcelles agricoles et 2 édifices urbains pour Oda, et 3 chantiers consécutifs pour les autres clans). Le chantier suivant démarre automatiquement à l'achèvement du premier !
                </p>
                <div class="mt-auto">
                    <a href="?page=city" class="btn btn-sm btn-outline-primary w-100">
                        🏯 Gérer les Bâtisseurs &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Grand Tableau de Bord -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #f59e0b !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">👑</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">2. Grand Tableau de Bord</h4>
                        <span class="badge bg-warning-lt">Vue Multi-Fiefs</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Panorama consolidé de tout votre Empire : jauges de stockage globales, productions nettes horaires, totaux impériaux et surveillance en temps réel du risque de famine féodale.
                </p>
                <div class="mt-auto">
                    <a href="?page=empire" class="btn btn-sm btn-outline-warning w-100">
                        👑 Consulter l'Empire &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Carnet de Raids -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #10b981 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">📜</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">3. Carnet de Raids (Farm List)</h4>
                        <span class="badge bg-success-lt">Attaque en 1 Clic</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Mémorisez vos cibles régulières (oasis sauvages, provinces inactives) et assignez à chacune une composition d'armée dédiée. Déployez toute votre tournée de pillage en un seul clic !
                </p>
                <div class="mt-auto">
                    <a href="?page=fleet#tab-farm-lists" class="btn btn-sm btn-outline-success w-100">
                        ⚔️ Ouvrir le Carnet de Raids &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Intendant du Marché -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">⚖️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">4. Intendant du Marché</h4>
                        <span class="badge bg-purple-lt">Troc 1:1:1 Instantané</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Rééquilibrez immédiatement vos réserves excédentaires de Bois, Pierre et Riz sans perte au ratio 1:1:1 pour un tribut de 3 Koban, et lancez immédiatement vos chantiers cruciaux.
                </p>
                <div class="mt-auto">
                    <button type="button" class="btn btn-sm btn-outline-purple w-100" 
                            onclick="openNpcExchangeModal(<?= $planet['id'] ?>, '<?= htmlspecialchars(addslashes($planet['name'])) ?>', <?= (int)$planet['metal'] ?>, <?= (int)$planet['crystal'] ?>, <?= (int)$planet['deuterium'] ?>, <?= (int)$planet['metal_max'] ?>, <?= (int)$planet['crystal_max'] ?>, <?= (int)$planet['deuterium_max'] ?>)">
                        ⚖️ Troc Rapide du Fief Actuel &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Ordre de Repli Tactique -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #ef4444 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">⛩️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">5. Ordre de Repli Tactique</h4>
                        <span class="badge bg-danger-lt">Évasion Nocturne</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Vos garnisons et votre Samouraï Héros se replient dans les collines et forêts lors des assauts nocturnes pour éviter l'anéantissement direct. Aucune perte de troupe face aux armées écrasantes !
                </p>
                <div class="mt-auto">
                    <a href="?page=empire#tab-evasion" class="btn btn-sm btn-outline-danger w-100">
                        🛡️ Configurer le Repli &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 6. Récompenses d'Honneur -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border p-3" style="border-left: 4px solid #d97706 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🎖️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">6. Récompenses de Médailles</h4>
                        <span class="badge bg-warning-lt">+100 Koban / Médaille</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Chaque exploit récompensé par la Cour Impériale (Top Hebdomadaire d'Assaut, de Défense, de Raid, de Progression ou Didacticiel) vous verse automatiquement <strong>+100 Koban</strong>.
                </p>
                <div class="mt-auto">
                    <a href="?page=ranking" class="btn btn-sm btn-outline-warning w-100">
                        🏆 Voir le Classement &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALE INTERACTIVE DU MARCHAND NPC ================= -->
<div class="modal modal-blur fade" id="modalNpcExchange" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>⚖️</span> Intendant du Marché Castral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-3">
                    L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au taux parfait de <strong>1:1:1</strong> pour un tribut de <strong>3 Koban 🪙</strong>.
                </div>

                <div class="p-2 bg-light border rounded mb-3 text-center">
                    <span class="text-muted small">Fief sélectionné : </span>
                    <strong id="npc_planet_name" class="text-dark">Fief</strong>
                    <div class="fs-4 fw-bold text-primary mt-1">
                        Total à répartir : <span id="npc_total_amount">0</span>
                    </div>
                    <div class="small text-muted">La somme totale doit être conservée au grain près.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪵 Bois de Cèdre :</span>
                        <span id="npc_wood_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_wood" oninput="onNpcRangeChange('wood')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_wood" oninput="onNpcInputChange('wood')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪨 Pierre de Taille :</span>
                        <span id="npc_stone_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_stone" oninput="onNpcRangeChange('stone')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_stone" oninput="onNpcInputChange('stone')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🌾 Riz Impérial :</span>
                        <span id="npc_rice_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_rice" oninput="onNpcRangeChange('rice')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_rice" oninput="onNpcInputChange('rice')">
                </div>

                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="distributeEvenly()">
                        ⚖️ Répartir Équitablement (1/3 chacun)
                    </button>
                </div>

                <div id="npc_diff_alert" class="alert alert-danger p-2 small d-none">
                    La somme répartie ne correspond pas au total disponible.
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <span class="small text-muted">Coût : <strong class="text-warning">3 Koban</strong> (Solde : <?= $goldCoins ?>)</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        🪙 Sceller le Troc (3 Koban)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function activateSeal(days) {
    const confirmed = await showModalConfirm(`Voulez-vous proclamer le Sceau Impérial du Shōgun pour une durée de ${days} jours ?`, 'Décret Impérial');
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'activate');
    fd.append('days', days);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Sceau Impérial Proclamé');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'activer le Sceau Impérial.', 'warning');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le Shogunat.', 'error');
    }
}

async function claimDailyGold() {
    const btn = document.getElementById('btnClaimDailyGold');
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'claim_daily');

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Tribut d\'Or Perçu');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Tribut indisponible pour l\'instant.', 'warning');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
        if (btn) btn.disabled = false;
    }
}

// Logique Intendant NPC
let currentNpcPlanetId = 0;
let currentNpcTotal = 0;
let currentNpcMax = { wood: 0, stone: 0, rice: 0 };

function openNpcExchangeModal(planetId, planetName, wood, stone, rice, maxW, maxS, maxR) {
    currentNpcPlanetId = planetId;
    currentNpcTotal = Math.floor(wood + stone + rice);
    currentNpcMax = { wood: maxW, stone: maxS, rice: maxR };

    document.getElementById('npc_planet_name').innerText = planetName;
    document.getElementById('npc_total_amount').innerText = currentNpcTotal.toLocaleString('fr-FR');

    ['wood', 'stone', 'rice'].forEach(res => {
        const r = document.getElementById(`npc_range_${res}`);
        const inp = document.getElementById(`npc_input_${res}`);
        const val = (res === 'wood') ? wood : ((res === 'stone') ? stone : rice);

        r.max = Math.min(currentNpcTotal, currentNpcMax[res]);
        r.value = val;
        inp.max = currentNpcMax[res];
        inp.value = val;
        document.getElementById(`npc_val_${res}`) && (document.getElementById(`npc_val_${res}`).innerText = val);
    });

    updateNpcDisplay();
    const modal = new bootstrap.Modal(document.getElementById('modalNpcExchange'));
    modal.show();
}

function distributeEvenly() {
    const part = Math.floor(currentNpcTotal / 3);
    const rest = currentNpcTotal - (part * 2);

    document.getElementById('npc_input_wood').value = part;
    document.getElementById('npc_input_stone').value = part;
    document.getElementById('npc_input_rice').value = rest;

    document.getElementById('npc_range_wood').value = part;
    document.getElementById('npc_range_stone').value = part;
    document.getElementById('npc_range_rice').value = rest;

    updateNpcDisplay();
}

function onNpcRangeChange(type) {
    const val = document.getElementById(`npc_range_${type}`).value;
    document.getElementById(`npc_input_${type}`).value = val;
    updateNpcDisplay();
}

function onNpcInputChange(type) {
    const val = document.getElementById(`npc_input_${type}`).value;
    document.getElementById(`npc_range_${type}`).value = val;
    updateNpcDisplay();
}

function updateNpcDisplay() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    document.getElementById('npc_wood_val').innerText = w.toLocaleString('fr-FR');
    document.getElementById('npc_stone_val').innerText = s.toLocaleString('fr-FR');
    document.getElementById('npc_rice_val').innerText = r.toLocaleString('fr-FR');

    const sum = w + s + r;
    const diff = currentNpcTotal - sum;
    const alertEl = document.getElementById('npc_diff_alert');
    const submitBtn = document.getElementById('btnSubmitNpcExchange');

    if (diff !== 0) {
        alertEl.classList.remove('d-none');
        alertEl.innerText = diff > 0 ? `Il reste ${diff.toLocaleString('fr-FR')} ressources à assigner.` : `Excédent de ${Math.abs(diff).toLocaleString('fr-FR')} ressources réparties en trop.`;
        submitBtn.disabled = true;
    } else {
        alertEl.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

async function submitNpcExchange() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    const fd = new FormData();
    fd.append('action', 'npc_exchange');
    fd.append('planet_id', currentNpcPlanetId);
    fd.append('wood', w);
    fd.append('stone', s);
    fd.append('rice', r);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du troc.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}
</script>
