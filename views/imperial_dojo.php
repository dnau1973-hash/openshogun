<?php
/**
 * views/imperial_dojo.php
 * Sanctuaire Suprême du Dōjō Impérial sur la tuile centrale [0 : 0]
 * (Tenchi no Seichi - 天地の聖地)
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialDojoEngine.php';
require_once __DIR__ . '/../core/AllianceEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

$dojoEngine = new ImperialDojoEngine();
$dojo = $dojoEngine->getDojoState();
$garrison = $dojoEngine->getGarrison();
$topContributors = !empty($dojo['controlling_alliance_id']) ? $dojoEngine->getTopContributors((int)$dojo['controlling_alliance_id']) : [];

$allianceEngine = new AllianceEngine();
$myAlliance = !empty($user['alliance_id']) ? $allianceEngine->getUserAlliance((int)$user['alliance_id']) : null;
$myAllianceId = $myAlliance ? (int)$myAlliance['id'] : 0;
$isControllingAlliance = ($myAllianceId > 0 && !empty($dojo['controlling_alliance_id']) && (int)$dojo['controlling_alliance_id'] === $myAllianceId);

$myCastlesCount = $myAllianceId > 0 ? $dojoEngine->countAllianceCastles($myAllianceId) : 0;

$currLvl = (int)$dojo['current_level'];
$targetLvl = max(1, min(10, (int)$dojo['target_level']));
$targetCfg = ImperialDojoEngine::LEVEL_CONFIG[$targetLvl];

$hp = (int)$dojo['health_points'];
$maxHp = max(1, (int)$dojo['max_health_points']);
$hpPercent = min(100, (int)round(($hp / $maxHp) * 100));

$isFrozen = ($dojo['status'] === 'frozen');
$isComplete = ($dojo['status'] === 'completed' || $currLvl >= 10);
$isBuilding = ($dojo['status'] === 'under_construction' && !empty($dojo['construction_finishes_at']));

$now = time();
$remainingSeconds = $isBuilding ? max(0, (int)$dojo['construction_finishes_at'] - $now) : 0;
?>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle text-warning fw-bold d-flex align-items-center gap-1">
                <span>⛩️</span> BATAILLE FINALE D'OPENSHOGUN &bull; POINT CULMINANT DU MONDE
            </div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <i class="fa-solid fa-torii-gate text-warning"></i>
                Le Dōjō Impérial de Kyōto (皇室道場)
                <span class="badge bg-warning-lt text-dark font-monospace">[0 : 0] Tenchi no Seichi</span>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="/?page=map&x=0&y=0" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-map-location-dot me-1"></i>Localiser sur la Carte
                </a>
                <a href="/?page=alliance" class="btn btn-secondary">
                    <i class="fa-solid fa-flag me-1"></i>Pavillon Diplomatique
                </a>
            </div>
        </div>
    </div>
</div>

<div id="dojoAlertBox"></div>

<?php if ($isFrozen): ?>
    <div class="alert alert-danger shadow-sm border-2 border-danger d-flex align-items-center gap-3 mb-4" role="alert">
        <span class="display-6"><i class="fa-solid fa-snowflake fa-spin"></i></span>
        <div>
            <h4 class="alert-title mb-1 fw-bold">CHANTIER DU DŌJŌ IMPÉRIAL GELÉ !</h4>
            <div class="text-secondary">
                L'Alliance bâtisseuse est passée sous le seuil critique des <strong>2 Châteaux Authentiques requis</strong> (Actuellement : <?= (int)$dojo['castles_owned'] ?> / 2).
                Tous les travaux sont suspendus et les ouvriers se replient derrière les remparts. Reconquérez un second château authentique pour dégeler le chantier !
            </div>
        </div>
    </div>
<?php elseif ($isComplete): ?>
    <div class="alert alert-success shadow-lg border-2 border-success d-flex align-items-center gap-3 mb-4" style="background:#f0fdf4;" role="alert">
        <span class="display-6 text-purple"><i class="fa-solid fa-crown"></i></span>
        <div>
            <h4 class="alert-title mb-1 fw-bold text-success">L'AVÈNEMENT DU NOUVEAU SHŌGUNAT EST SCIELLÉ !</h4>
            <div class="text-secondary">
                Le 10e et dernier niveau du Dōjō Impérial a été achevé sous la protection des dieux et des Tuiles Démoniaques <em>Onigawara</em> !
                L'Alliance <strong>[<?= htmlspecialchars($dojo['alliance_tag'] ?? '') ?>] <?= htmlspecialchars($dojo['alliance_name'] ?? '') ?></strong> a conquis la Victoire Suprême d'OpenShogun et entre vivante au Panthéon Féodal !
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Carte Principale de la Merveille -->
<div class="card shadow-sm border-warning mb-4" style="border-width: 2px;">
    <div class="card-body p-4" style="background: linear-gradient(135deg, rgba(254,243,199,0.4) 0%, rgba(255,255,255,0.9) 100%);">
        <div class="row align-items-center g-4">
            <div class="col-md-3 text-center">
                <div style="position:relative; width:160px; height:160px; margin:0 auto; border-radius:16px; overflow:hidden; border:3px solid #d97706; box-shadow:0 10px 25px rgba(217,119,6,0.35);">
                    <img src="/public/assets/map/tile_imperial_dojo.jpg?v=1" alt="Dōjō Impérial" style="width:100%; height:100%; object-fit:cover;">
                    <div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.75); color:#fff; font-size:0.75rem; padding:4px; font-weight:bold;">
                        Tuile Centrale [0 : 0]
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1">
                        Palier <?= $currLvl ?> / 10
                    </span>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <h3 class="fw-bold mb-1 text-dark">
                            Sanctuaire Originel de Kyōto &bull; Dōjō des Dieux
                        </h3>
                        <p class="text-secondary small mb-2">
                            Érigé sur le nombril de l'archipel, cet édifice titanesque unit la puissance des 12 Châteaux Authentiques, la foi des bâtisseurs et la protection des toitures ignifugées d'Onigawara.
                        </p>
                    </div>
                    <div>
                        <?php if (empty($dojo['controlling_alliance_id'])): ?>
                            <span class="badge bg-secondary fs-7 p-2">Terre Neutre Non Réclamée</span>
                        <?php else: ?>
                            <span class="badge bg-primary fs-7 p-2">
                                <i class="fa-solid fa-shield-halved me-1"></i>Alliance [<?= htmlspecialchars($dojo['alliance_tag']) ?>] <?= htmlspecialchars($dojo['alliance_name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jauge de Structure PV -->
                <div class="mb-3 bg-white p-3 rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-bold text-dark">
                            <i class="fa-solid fa-heart-pulse text-danger me-1"></i>Points de Solidité de Structure (PV) :
                        </span>
                        <span class="font-monospace fw-bold fs-6 <?= $hpPercent < 30 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($hp, 0, ',', ' ') ?> / <?= number_format($maxHp, 0, ',', ' ') ?> PV (<?= $hpPercent ?>%)
                        </span>
                    </div>
                    <div class="progress" style="height: 14px;">
                        <div class="progress-bar <?= $hpPercent < 30 ? 'bg-danger' : ($hpPercent < 70 ? 'bg-warning' : 'bg-success') ?> progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: <?= $hpPercent ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1 text-secondary" style="font-size: 0.75rem;">
                        <span><i class="fa-solid fa-shield-cat me-1 text-warning"></i>Bouclier Onigawara : -50% dégâts de feu &amp; bombes (Paliers 8+)</span>
                        <span>Régression de niveau si PV tombent à 0</span>
                    </div>
                </div>

                <!-- Informations Stratégiques d'Alliance -->
                <div class="row g-2 text-center">
                    <div class="col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-secondary small d-block">Sceaux de Châteaux :</span>
                            <strong class="fs-6 <?= $dojo['castles_owned'] >= 2 ? 'text-success' : 'text-danger' ?>">
                                <?= (int)$dojo['castles_owned'] ?> / 2 Châteaux Requis
                            </strong>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-secondary small d-block">Bonus Vitesse :</span>
                            <strong class="fs-6 text-primary">
                                +<?= max(0, ((int)$dojo['castles_owned'] - 2) * 15) ?>% Vitesse Chantier
                            </strong>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-secondary small d-block">Statut Actuel :</span>
                            <strong class="fs-6 text-dark font-monospace">
                                <?php if ($isBuilding): ?>
                                    ⏳ En Construction (<span id="dojoCountdown"><?= gmdate('H:i:s', $remainingSeconds) ?></span>)
                                <?php elseif ($isFrozen): ?>
                                    ❄️ Gelé (&lt; 2 châteaux)
                                <?php elseif ($isComplete): ?>
                                    👑 Victoire Suprême
                                <?php elseif (empty($dojo['controlling_alliance_id'])): ?>
                                    Terre Libre
                                <?php else: ?>
                                    En attente de matériaux
                                <?php endif; ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'Action Stratégique -->
                <div class="d-flex gap-2 justify-content-end flex-wrap mt-3 pt-3 border-top">
                    <?php if (empty($dojo['controlling_alliance_id']) && $myAllianceId > 0 && $myCastlesCount >= 2): ?>
                        <button type="button" class="btn btn-warning fw-bold text-dark" onclick="claimDojoDirect()">
                            <i class="fa-solid fa-flag-checkered me-1"></i>Revendiquer [0 : 0] pour [<?= htmlspecialchars($myAlliance['tag']) ?>]
                        </button>
                    <?php endif; ?>

                    <a href="/?page=fleet&target_x=0&target_y=0&mission=dojo_reinforce" class="btn btn-outline-primary">
                        <i class="fa-solid fa-shield-halved me-1"></i>Dépêcher Renforts de Garnison
                    </a>

                    <?php if (!$isControllingAlliance): ?>
                        <a href="/?page=fleet&target_x=0&target_y=0&mission=dojo_siege" class="btn btn-danger fw-bold">
                            <i class="fa-solid fa-bomb me-1"></i>Ordonner un Siège Destructeur
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isComplete): ?>
<!-- ============================================================ -->
<!-- LE CHANTIER TITANESQUE DU PALIER CIBLE                       -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
        <div>
            <h3 class="card-title text-warning m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-hammer text-warning"></i>
                Palier Cible <?= $targetLvl ?> : <?= htmlspecialchars($targetCfg['name']) ?>
            </h3>
            <div class="small text-muted mt-1">
                <?= htmlspecialchars($targetCfg['description']) ?>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-warning text-dark font-monospace px-3 py-1 fs-6">
                Avancement : <?= (int)$dojo['progress_percent'] ?>%
            </span>
        </div>
    </div>

    <div class="card-body p-4">
        <form id="dojoContributeForm" onsubmit="event.preventDefault(); submitContribution();">
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="font-size: 0.85rem;">
                <i class="fa-solid fa-circle-info fs-5"></i>
                <div>
                    <strong>Logistique Collaborative d'Alliance :</strong> Renseignez les quantités de matériaux à acheminer depuis votre fief actuel (<strong><?= htmlspecialchars($planet['name']) ?></strong>). Les ressources seront scellées dans le magasin collectif de [0 : 0].
                </div>
            </div>

            <!-- Grille des 8 Ressources -->
            <div class="row g-3 mb-4">
                <?php
                $resFields = [
                    'wood' => [
                        'label'      => 'Bois de Cèdre',
                        'icon'       => '🌲',
                        'stock_key'  => 'stock_wood',
                        'req'        => $targetCfg['wood'],
                        'planet_val' => (int)$planet['crystal'],
                        'unit'       => 'stères'
                    ],
                    'granite' => [
                        'label'      => 'Granit Cyclopéen',
                        'icon'       => '🪨',
                        'stock_key'  => 'stock_granite',
                        'req'        => $targetCfg['granite'],
                        'planet_val' => (int)$planet['deuterium'],
                        'unit'       => 'blocs'
                    ],
                    'metal' => [
                        'label'      => 'Acier Tamahagane',
                        'icon'       => '⚔️',
                        'stock_key'  => 'stock_metal',
                        'req'        => $targetCfg['metal'],
                        'planet_val' => (int)$planet['metal'],
                        'unit'       => 'lingots'
                    ],
                    'rice' => [
                        'label'      => 'Riz Impérial',
                        'icon'       => '🌾',
                        'stock_key'  => 'stock_rice',
                        'req'        => $targetCfg['rice'],
                        'planet_val' => (int)($planet['rice'] ?? 0),
                        'unit'       => 'sacs'
                    ],
                    'beams' => [
                        'label'      => 'Poutres de Charpente',
                        'icon'       => '🪵',
                        'stock_key'  => 'stock_beams',
                        'req'        => $targetCfg['beams'],
                        'planet_val' => (int)($planet['timber_beams'] ?? 0),
                        'unit'       => 'poutres'
                    ],
                    'tiles' => [
                        'label'      => 'Tuiles Hongawara (Kawara)',
                        'icon'       => '🧱',
                        'stock_key'  => 'stock_tiles',
                        'req'        => $targetCfg['tiles'],
                        'planet_val' => (int)($planet['tiles_kawara'] ?? 0),
                        'unit'       => 'tuiles'
                    ],
                    'oni_tiles' => [
                        'label'      => 'Tuiles Onigawara (Démoniaques)',
                        'icon'       => '👹',
                        'stock_key'  => 'stock_oni_tiles',
                        'req'        => $targetCfg['oni_tiles'],
                        'planet_val' => (int)($planet['tiles_onigawara'] ?? 0),
                        'unit'       => 'gardiennes'
                    ],
                    'gold' => [
                        'label'      => 'Kobans d\'Or Impérial',
                        'icon'       => '🪙',
                        'stock_key'  => 'stock_gold',
                        'req'        => $targetCfg['gold'],
                        'planet_val' => (int)($planet['koban_gold'] ?? 0),
                        'unit'       => 'kobans'
                    ]
                ];

                foreach ($resFields as $fKey => $fData):
                    $currentStock = (int)($dojo[$fData['stock_key']] ?? 0);
                    $reqAmount = (int)$fData['req'];
                    $stillNeeded = max(0, $reqAmount - $currentStock);
                    $pct = $reqAmount > 0 ? min(100, (int)round(($currentStock / $reqAmount) * 100)) : 100;
                    $maxDonatable = min($stillNeeded, (int)$fData['planet_val']);
                ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 bg-surface rounded border h-100 d-flex flex-column justify-content-between shadow-xs">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small d-flex align-items-center gap-1">
                                        <span><?= $fData['icon'] ?></span> <?= htmlspecialchars($fData['label']) ?>
                                    </strong>
                                    <span class="badge bg-secondary-lt font-monospace" style="font-size:0.68rem;">
                                        Fief : <?= number_format($fData['planet_val'], 0, ',', ' ') ?>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between small text-secondary mb-1" style="font-size:0.75rem;">
                                    <span>Quota Réuni :</span>
                                    <span class="font-monospace fw-bold <?= $pct >= 100 ? 'text-success' : 'text-dark' ?>">
                                        <?= number_format($currentStock, 0, ',', ' ') ?> / <?= number_format($reqAmount, 0, ',', ' ') ?>
                                    </span>
                                </div>

                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-warning' ?>"
                                         role="progressbar" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <?php if ($stillNeeded <= 0): ?>
                                    <div class="badge bg-success-lt w-100 py-1 text-center font-monospace">
                                        <i class="fa-solid fa-check me-1"></i>Quota Atteint
                                    </div>
                                <?php else: ?>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="<?= $fKey ?>" id="input_<?= $fKey ?>"
                                               class="form-control form-control-sm text-end font-monospace"
                                               min="0" max="<?= $maxDonatable ?>" value="0"
                                               placeholder="0" <?= $maxDonatable <= 0 ? 'disabled' : '' ?>>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="setMaxContribution('<?= $fKey ?>', <?= $maxDonatable ?>)"
                                                <?= $maxDonatable <= 0 ? 'disabled' : '' ?>>
                                            Max
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" onclick="setAllMaxContributions()">
                    <i class="fa-solid fa-boxes-packing me-1"></i>Remplir tous les Max Possibles
                </button>

                <button type="submit" id="btnSubmitDojo" class="btn btn-warning fw-bold text-dark px-4 shadow-sm"
                        <?= (!$isControllingAlliance && !empty($dojo['controlling_alliance_id'])) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-paper-plane me-1"></i>Acheminer les Matériaux vers [0 : 0]
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- GARNISON D'ALLIANCE & JOURNAL DE SIÈGE                      -->
<!-- ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Garnison Conjointe en Défense -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-surface-secondary py-3 d-flex justify-content-between align-items-center">
                <h4 class="card-title m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-primary"></i>
                    Garnison Fédérale de Défense sur [0 : 0]
                </h4>
                <a href="/?page=fleet&target_x=0&target_y=0&mission=dojo_reinforce" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>Renforcer
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($garrison)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="fa-solid fa-person-shelter fs-2 mb-2 d-block text-muted"></i>
                        Aucune troupe stationnée en garnison permanente sur la tuile sacrée.<br>
                        <small>Déployez des régiments de samouraïs pour repousser les bombes et les raids ennemis !</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Daimyō Protecteur</th>
                                    <th>Type de Régiment</th>
                                    <th class="text-end">Soldats en Garde</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($garrison as $gar): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($gar['username']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt"><?= htmlspecialchars($gar['unit_code']) ?></span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            <?= number_format($gar['amount'], 0, ',', ' ') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tableau d'Honneur des Bâtisseurs de l'Alliance -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-surface-secondary py-3">
                <h4 class="card-title m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-trophy text-warning"></i>
                    Tableau d'Honneur des Bâtisseurs
                </h4>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topContributors)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="fa-solid fa-scroll fs-2 mb-2 d-block text-muted"></i>
                        Aucune contribution enregistrée pour ce chantier.<br>
                        <small>Soyez le premier bâtisseur à sceller son nom dans le grand rouleau impérial !</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Rang &amp; Seigneur</th>
                                    <th class="text-center">Tuiles Posées</th>
                                    <th class="text-center">Poutres Offertes</th>
                                    <th class="text-end">Kobans d'Or</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($topContributors as $c): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?= $rank === 1 ? 'warning' : ($rank === 2 ? 'secondary' : 'dark-lt') ?> me-1">
                                                #<?= $rank++ ?>
                                            </span>
                                            <strong><?= htmlspecialchars($c['username']) ?></strong>
                                        </td>
                                        <td class="text-center font-monospace">
                                            <?= number_format($c['total_tiles'] + $c['total_oni_tiles'], 0, ',', ' ') ?> 🧱
                                        </td>
                                        <td class="text-center font-monospace">
                                            <?= number_format($c['total_beams'], 0, ',', ' ') ?> 🪵
                                        </td>
                                        <td class="text-end font-monospace text-warning fw-bold">
                                            <?= number_format($c['total_gold'], 0, ',', ' ') ?> 🪙
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let remainingCountdown = <?= $remainingSeconds ?>;
if (remainingCountdown > 0) {
    const timerInterval = setInterval(() => {
        remainingCountdown--;
        if (remainingCountdown <= 0) {
            clearInterval(timerInterval);
            window.location.reload();
        } else {
            const h = String(Math.floor(remainingCountdown / 3600)).padStart(2, '0');
            const m = String(Math.floor((remainingCountdown % 3600) / 60)).padStart(2, '0');
            const s = String(remainingCountdown % 60).padStart(2, '0');
            const el = document.getElementById('dojoCountdown');
            if (el) el.innerText = `${h}:${m}:${s}`;
        }
    }, 1000);
}

function setMaxContribution(fKey, maxVal) {
    const input = document.getElementById('input_' + fKey);
    if (input) {
        input.value = maxVal;
    }
}

function setAllMaxContributions() {
    <?php foreach ($resFields as $fKey => $fData): 
        $currentStock = (int)($dojo[$fData['stock_key']] ?? 0);
        $reqAmount = (int)$fData['req'];
        $stillNeeded = max(0, $reqAmount - $currentStock);
        $maxDonatable = min($stillNeeded, (int)$fData['planet_val']);
    ?>
        setMaxContribution('<?= $fKey ?>', <?= $maxDonatable ?>);
    <?php endforeach; ?>
}

async function submitContribution() {
    const form = document.getElementById('dojoContributeForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSubmitDojo');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Acheminement des convois...';

    try {
        const res = await fetch('/api/imperial_dojo.php?action=contribute', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message || 'Ressources acheminées avec succès !', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || 'Erreur lors du transfert.', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Acheminer les Matériaux vers [0 : 0]';
        }
    } catch (err) {
        showAlert('Erreur de communication avec le serveur.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Acheminer les Matériaux vers [0 : 0]';
    }
}

async function claimDojoDirect() {
    if (!confirm("Voulez-vous officiellement revendiquer la Terre Sacrée [0 : 0] au nom de votre alliance ?")) {
        return;
    }

    try {
        const res = await fetch('/api/imperial_dojo.php?action=claim', {
            method: 'POST'
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message || 'Terre Sacrée revendiquée avec succès !', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || 'Erreur lors de la revendication.', 'danger');
        }
    } catch (err) {
        showAlert('Erreur de communication avec le serveur.', 'danger');
    }
}

function showAlert(msg, type) {
    const box = document.getElementById('dojoAlertBox');
    if (!box) return;
    box.innerHTML = `
        <div class="alert alert-${type} alert-dismissible shadow-sm fade show" role="alert">
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

