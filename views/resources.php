<?php
/**
 * Vue du Domaine Rural Féodal & 9 Parcelles Stratégiques (OpenShogun - Dorf 1)
 * Refonte majeure : Carte panoramique 16:9 fixe (sans pan/zoom), 9 structures uniques,
 * Progression verticale 20 à 100, chantiers avec durées réelles non-instantanées.
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/VillageGeneratorService.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/PopulationEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();
$ruralPlotEngine = new RuralPlotEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;
$queue = $buildingEngine->getQueue((int)$planet['id']);

// Récupération des 9 parcelles procédurales enrichies pour la planète active
$plots = $ruralPlotEngine->getEnrichedPlots((int)$planet['id'], $planet, $hqLevel);
$housingCap = $ruralPlotEngine->getVillageHousingCapacity((int)$planet['id']);
$maxPop = $housingCap['total_capacity'];

// Démographie et satisfaction féodale
$fields = $planetEngine->getFields((int)$planet['id']);
$workforce = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop);
$activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
$contentment = PopulationEngine::calculateContentment($planet, $activeFeast, $workforce, $buildings);
$delinquency = $contentment['delinquency'] ?? PopulationEngine::calculateDelinquency($planet, $buildings, $workforce);

// Oasis annexées
$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);

$isTerran = (($user['faction'] ?? 'terran') === 'terran');
?>

<style>
/* ========================================================
   CARTE ILLUSTRÉE PANORAMIQUE FIXE 16:9 (SANS DRAG/ZOOM)
   ======================================================== */
.rural-map-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background-image: url('/public/assets/shogun_rural_terroir_9plots.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    user-select: none;
    overflow: hidden;
    border-radius: 12px;
    border: 2px solid var(--border-color, #e2e8f0);
    box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.3), 0 10px 25px rgba(60, 45, 30, 0.15);
}


@media (max-width: 768px) {
    .rural-map-container {
        min-height: 480px;
    }
}

/* ========================================================
   BADGES MINIMALISTES & COMPACTS DES 9 STRUCTURES
   ======================================================== */
.rural-plot-badge {
    position: absolute;
    transform: translate(-50%, -50%);
    cursor: pointer;
    z-index: 5;
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.2s ease;
    user-select: none;
}

.rural-plot-badge:hover {
    transform: translate(-50%, -50%) scale(1.12);
    z-index: 10;
}

.rural-badge-inner {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: rgba(15, 23, 42, 0.90);
    backdrop-filter: blur(8px);
    border: 1.5px solid var(--badge-color, #ffffff);
    border-radius: 999px;
    padding: 0.2rem 0.65rem 0.2rem 0.25rem;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.75), 0 0 10px var(--badge-glow, rgba(255, 255, 255, 0.25));
    color: #ffffff;
    white-space: nowrap;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.rural-badge-inner:hover {
    background: rgba(15, 23, 42, 0.98);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.85), 0 0 16px var(--badge-glow, rgba(255, 255, 255, 0.4));
}

.rural-badge-inner.is-upgrading {
    border-color: #f59e0b !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.75), 0 0 16px rgba(245, 158, 11, 0.65) !important;
}

.rural-badge-inner.is-tenshu {
    border-color: #ef4444;
    background: rgba(30, 27, 75, 0.92);
}

.rural-badge-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--badge-color, #ffffff);
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: bold;
    flex-shrink: 0;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}

.rural-badge-inner.is-upgrading .rural-badge-avatar {
    background: #f59e0b !important;
    color: #0f172a !important;
}

.rural-badge-level-text {
    font-size: 0.75rem;
    font-weight: 800;
    font-family: monospace;
    letter-spacing: 0.02em;
    color: #f8fafc;
    display: inline-flex;
    align-items: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.9);
}

.rural-badge-name-short {
    font-size: 0.76rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.rural-badge-action-hint {
    font-size: 0.7rem;
    color: #fca5a5;
    margin-left: 0.2rem;
}

/* Chips de coût dans la modale */
.cost-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.cost-chip.affordable {
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.1);
}

.cost-chip.missing {
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.35);
    background: rgba(239, 68, 68, 0.1);
}
</style>

<?php
$queueTitle = 'Activités & Chantiers Ruraux';
ob_start();
?>
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
                <div>
                    <h2 class="card-title d-flex align-items-center gap-2">
                        <span><i class="fa-solid fa-wheat-awn text-success me-1"></i> Domaine Rural &amp; Terroirs du Fief - <?= htmlspecialchars($planet['name']) ?></span>
                        <?php if (!empty($planet['is_capital'])): ?>
                            <span class="badge bg-warning text-dark fw-bold" style="font-size:0.72rem;"><i class="fa-solid fa-crown me-1"></i>Capitale</span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Renommer ce fief" onclick="openCityRenamePrompt(<?= (int)$planet['id'] ?>, <?= htmlspecialchars(json_encode($planet['name']), ENT_QUOTES, 'UTF-8') ?>)" style="font-size:0.75rem;">
                            <i class="fa-solid fa-pen me-1"></i>Renommer
                        </button>
                        <span class="badge bg-green-lt fw-bold font-monospace" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-map-location-dot me-1"></i>9 Terroirs
                        </span>
                        <?php if ($isTerran): ?>
                            <span class="badge bg-danger-lt text-danger fw-bold" style="font-size:0.72rem;" title="Privilège du Clan Oda : 1 chantier rural et 1 chantier urbain peuvent progresser simultanément">
                                <i class="fa-solid fa-bolt me-1"></i>Clan Oda
                            </span>
                        <?php endif; ?>
                    </h2>
                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                        Gouvernance [<?= (int)$planet['coord_x'] ?>:<?= (int)$planet['coord_y'] ?>] &bull;
                        Tenshu Donjon : <strong style="color:var(--border-highlight, #c2252b);">Niveau <?= $hqLevel ?></strong> &bull;
                        Habitants : <strong class="text-primary"><?= number_format($maxPop) ?> villageois</strong> &bull;
                        Main-d'œuvre active : <strong><?= number_format($workforce['assigned_workers']) ?></strong> / <?= number_format($workforce['required_workers']) ?> requis
                        <?php if (!empty($planet['workforce']) && !empty($planet['workforce']['is_understaffed'])): ?>
                            <span class="badge bg-warning-lt text-warning ms-1" title="Main-d'œuvre insuffisante pour tous les postes"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sous-effectif : -<?= $planet['workforce']['understaffed_malus_pct'] ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <a href="?page=city" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                        <i class="fa-solid fa-chess-rook me-1"></i>Vers la Cité Castrale &rarr;
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Conteneur Panoramique 16:9 Fixe (sans pan/zoom) -->
                <div class="rural-map-container" id="ruralMapContainer">

                    <?php foreach ($plots as $type => $p): ?>
                        <?php
                            $isMax = !empty($p['is_max']);
                            $isUpgrading = !empty($p['is_upgrading']);
                            $canAfford = !empty($p['can_afford']);
                            $cost = $p['cost'];
                            $targetLevel = $p['target_level'] ?? $p['next_level'];

                            $isTenshu = ($type === 'tenshu');

                            if ($isTenshu) {
                                $tooltip = '<strong>Tenshu (Cité Castrale)</strong><br>' .
                                           '<span class="text-warning"><i class="fa-solid fa-arrow-right me-1"></i>Entrer dans la Cité Castrale</span>';
                            } elseif ($isUpgrading) {
                                $tooltip = '<strong>' . htmlspecialchars($p['name']) . '</strong><br>' .
                                           '<span class="text-warning"><i class="fa-solid fa-hammer me-1"></i>En travaux vers Niv. ' . $targetLevel . '</span><br>' .
                                           '<small class="text-muted">Cliquer pour voir l\'état du chantier</small>';
                            } else {
                                $fervorInfo = (!empty($p['energy_cons']) && (int)$p['energy_cons'] > 0)
                                    ? '<br><span class="text-info small"><i class="fa-solid fa-bolt me-1"></i>Ferveur : ' . (int)$p['energy_cons'] . '</span>'
                                    : '';
                                $tooltip = '<strong>' . htmlspecialchars($p['name']) . '</strong> (Niv. ' . $p['level'] . ' / ' . $p['max_level'] . ')<br>' .
                                           '<span class="text-warning">' . htmlspecialchars($p['prod_label']) . '</span>' .
                                           $fervorInfo . '<br>' .
                                           '<small class="text-muted">' . htmlspecialchars($p['worker_role']) . ' : ' . $p['workers_assigned'] . ' ouvriers<br><em>Cliquer pour gérer &amp; élever</em></small>';
                            }
                        ?>
                        <div class="rural-plot-badge <?= $isTenshu ? 'tenshu-badge' : '' ?>"
                             id="rural-badge-<?= $type ?>"
                             style="left: <?= $p['pos_x'] ?>%; top: <?= $p['pos_y'] ?>%; --badge-color: <?= $p['color'] ?>; --badge-glow: <?= $p['color'] ?>80;"
                             data-type="<?= $type ?>"
                             data-category="<?= htmlspecialchars($p['category'] ?? '') ?>"
                             data-res-name="<?= htmlspecialchars($p['res_name'] ?? '') ?>"
                             data-name="<?= htmlspecialchars($p['name']) ?>"
                             data-jp-name="<?= htmlspecialchars($p['jp_name']) ?>"
                             data-desc="<?= htmlspecialchars($p['desc']) ?>"
                             data-level="<?= $p['level'] ?>"
                             data-max-level="<?= $p['max_level'] ?>"
                             data-next-level="<?= $p['next_level'] ?>"
                             data-target-level="<?= $targetLevel ?>"
                             data-is-max="<?= $isMax ? '1' : '0' ?>"
                             data-is-upgrading="<?= $isUpgrading ? '1' : '0' ?>"
                             data-queue-id="<?= (int)($p['queue_id'] ?? 0) ?>"
                             data-finishes-at="<?= (int)($p['finishes_at'] ?? 0) ?>"
                             data-started-at="<?= (int)($p['started_at'] ?? 0) ?>"
                             data-workers="<?= $p['workers_assigned'] ?>"
                             data-worker-role="<?= htmlspecialchars($p['worker_role']) ?>"
                             data-prod-label="<?= htmlspecialchars($p['prod_label']) ?>"
                             data-next-prod-label="<?= htmlspecialchars($p['next_prod_label']) ?>"
                             data-energy-cons="<?= (int)($p['energy_cons'] ?? 0) ?>"
                             data-next-energy-cons="<?= (int)($p['next_energy_cons'] ?? 0) ?>"
                             data-tile-img="<?= htmlspecialchars($p['tile_img']) ?>"
                             data-bg-img="<?= htmlspecialchars($p['bg_image']) ?>"
                             data-cost-metal="<?= (int)$cost['metal'] ?>"
                             data-cost-crystal="<?= (int)$cost['crystal'] ?>"
                             data-cost-deuterium="<?= (int)$cost['deuterium'] ?>"
                             data-duration="<?= (int)$p['duration'] ?>"
                             data-can-afford="<?= $canAfford ? '1' : '0' ?>"
                             data-color-class="<?= $p['color_class'] ?>"
                             data-color="<?= $p['color'] ?>"
                             data-icon="<?= $p['icon'] ?>"
                             data-progress-pct="<?= $p['progress_pct'] ?>"
                             data-bs-toggle="tooltip"
                             data-bs-html="true"
                             data-bs-placement="top"
                             title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                             onclick="<?= $isTenshu ? "window.location.href='/?page=city'" : "handleRuralPinClick(this, event)" ?>">

                            <?php if ($isTenshu): ?>
                                <div class="rural-badge-inner is-tenshu">
                                    <div class="rural-badge-avatar">
                                        <i class="fa-solid fa-chess-rook"></i>
                                    </div>
                                    <div class="rural-badge-level-text">
                                        <span class="rural-badge-name-short">Tenshu</span>
                                        <span class="rural-badge-action-hint"><i class="fa-solid fa-arrow-right-to-bracket"></i></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="rural-badge-inner <?= $isUpgrading ? 'is-upgrading' : '' ?>">
                                    <div class="rural-badge-avatar">
                                        <?php if ($isUpgrading): ?>
                                            <i class="fa-solid fa-hammer text-warning"></i>
                                        <?php else: ?>
                                            <i class="<?= $p['icon'] ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rural-badge-level-text">
                                        <span>Niv. <?= $p['level'] ?> / <?= $p['max_level'] ?></span>
                                        <?php if ($isUpgrading): ?>
                                            <i class="fa-solid fa-hourglass-half text-warning ms-1" style="font-size:0.65rem;" title="En travaux"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
<?php
$centerCardContent = ob_get_clean();

ob_start();
?>

<!-- ========================================================
     MODALE D'AMÉLIORATION DU TERROIR FÉODAL (STYLE ÉLITE SENGOKU)
     Illustration d'en-tête HD, dégradé sombre et badges d'élite
     ======================================================== -->
<div class="modal fade" id="plotUpgradeModal" tabindex="-1" aria-labelledby="modalPlotTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: 12px;">
            <!-- Illustration d'en-tête (215px) avec overlay dégradé -->
            <div style="position: relative; width: 100%; height: 215px; overflow: hidden; background: #0f172a;">
                <img id="modalPlotBannerImg" src="/public/assets/resources/terroir_wood.jpg" alt="Terroir Féodal" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>
                <div style="position: absolute; bottom: 12px; left: 16px; right: 16px;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                    <div style="max-width: 72%;">
                        <span id="modalPlotCategoryBadge" class="badge bg-primary text-white fw-bold px-2 py-1 mb-1 shadow-sm" style="font-size:0.75rem;">
                            🌾 TERROIR &bull; FORET
                        </span>
                        <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="modalPlotTitle" style="font-size: 1.25rem;">
                            Forêt de Cèdres
                        </h3>
                        <div class="text-white-50 font-monospace small mt-0.5" id="modalPlotJpSubtitle">杉林 (Sugi-bayashi)</div>
                    </div>
                    <div>
                        <span id="modalPlotLevelBadge" class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1 shadow-sm">
                            Niveau 1 / 100
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>
            </div>

            <!-- Corps de la modale -->
            <div class="modal-body p-4 bg-white">
                <!-- Encart Descriptif Féodal du Terroir -->
                <div class="p-3 rounded border mb-3" style="background: #fdfbf7; border-left: 4px solid #b91c1c !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span id="modalPlotIconSpan" style="font-size: 1.15rem;"><i class="fa-solid fa-tree text-success"></i></span>
                        <strong class="text-danger small" id="modalPlotWorkerRoleHeader">Exploitation Rurale :</strong>
                    </div>
                    <p class="small text-secondary m-0 fst-italic" id="modalPlotDesc" style="line-height: 1.5;">
                        ...
                    </p>
                </div>

                <!-- Main-d'œuvre & Ferveur requises -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-2 px-3 mb-3 rounded border bg-light" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-person-digging text-warning fs-3"></i>
                        <span><strong id="modalPlotWorkerRole">Ouvriers</strong> : <strong class="text-dark" id="modalPlotWorkersCount">2</strong> requis</span>
                    </div>
                    <div id="modalPlotEnergyConsBox" class="d-none align-items-center gap-2 text-info">
                        <i class="fa-solid fa-bolt text-info fs-3"></i>
                        <span><strong>Ferveur Shintō</strong> : <strong class="text-dark" id="modalPlotEnergyCons">0</strong> / h</span>
                    </div>
                </div>

                <!-- Jauge de Potentiel Féodal (20 à 100) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                        <span class="fw-bold text-uppercase" style="font-size:0.75rem;">Potentiel Féodal du Terroir</span>
                        <strong class="text-primary font-monospace" id="modalPlotPotentialText">35%</strong>
                    </div>
                    <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                        <div class="progress-bar bg-primary" id="modalPlotPotentialBar" role="progressbar" style="width: 35%;"></div>
                    </div>
                </div>

                <!-- Bénéfices / Production -->
                <div class="p-3 mb-3 rounded border" style="background: #f8fafc;">
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                        <span class="text-secondary small"><i class="fa-solid fa-chart-line text-success me-1"></i>Rendement actuel :</span>
                        <strong class="text-success small" id="modalPlotCurrentProd">+35 / h</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-secondary small"><i class="fa-solid fa-arrow-trend-up text-primary me-1"></i>Prochain niveau :</span>
                        <strong class="text-primary small" id="modalPlotNextProd">+55 / h</strong>
                    </div>
                </div>

                <!-- Statut Chantier en cours (si actif) -->
                <div id="modalPlotActiveWorkBox" class="d-none p-3 mb-3 rounded border border-warning-subtle" style="background: #fffbeb;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-hammer text-warning me-1"></i>Chantier en cours</span>
                        <span class="font-monospace fw-bold text-danger fs-3" id="modalPlotWorkCountdown">--:--:--</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px; background: #fef3c7; border-radius: 3px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="modalPlotWorkProgressBar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="small text-muted" id="modalPlotWorkFinishLabel"></div>
                </div>

                <!-- Coûts requis & Durée (si pas de chantier en cours) -->
                <div id="modalPlotNormalUpgradeBox">
                    <div class="mb-3">
                        <label class="form-label mb-2 fw-bold text-muted small text-uppercase" style="font-size:0.75rem;">Matériaux Requis pour l'Élévation :</label>
                        <div class="d-flex flex-wrap gap-2" id="modalPlotCostTags"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted small mb-0 p-2 rounded bg-light border">
                        <span><i class="fa-regular fa-clock me-1 text-primary"></i>Durée estimée des travaux :</span>
                        <strong class="font-monospace text-dark" id="modalPlotDuration">00:01:30</strong>
                    </div>
                </div>
            </div>

            <!-- Pied de modale avec confirmation -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Fermer
                </button>
                <div id="modalPlotActionContainer" class="d-flex gap-2">
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnModalPlotUpgrade">
                        <i class="fa-solid fa-arrow-up me-1"></i>Élever la structure
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.RURAL_CONFIG = {
    stocks: {
        metal: <?= (int)($planet['metal'] ?? 0) ?>,
        crystal: <?= (int)($planet['crystal'] ?? 0) ?>,
        deuterium: <?= (int)($planet['deuterium'] ?? 0) ?>
    },
    heroBonus: {
        foret: <?= (int)($planet['prod_rates']['hero_bonuses']['metal'] ?? 0) ?>,
        carriere: <?= (int)($planet['prod_rates']['hero_bonuses']['crystal'] ?? 0) ?>,
        riziere: <?= (int)($planet['prod_rates']['hero_bonuses']['deuterium'] ?? 0) ?>
    }
};
</script>
<script src="/public/js/rural_domain_map.js?v=<?= file_exists(__DIR__ . '/../public/js/rural_domain_map.js') ? filemtime(__DIR__ . '/../public/js/rural_domain_map.js') : time() ?>"></script>
<?php
$extraHtml = ob_get_clean();
require __DIR__ . '/partials/game_screen_template.php';
?>
