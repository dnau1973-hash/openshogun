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
/* Disposition générale du Domaine Rural */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

.grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 370px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1200px) {
    .grid-main {
        grid-template-columns: 1fr;
    }
}

/* ========================================================
   CARTE ILLUSTRÉE PANORAMIQUE FIXE 16:9 (SANS DRAG/ZOOM)
   ======================================================== */
.rural-map-card {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    background: #0f172a;
}

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
    transition: background-image 0.6s ease;
}

.rural-map-container.bg-theme-night {
    background-image: url('/public/assets/shogun_rural_terroir_night.jpg') !important;
}

.rural-map-container.bg-theme-snow {
    background-image: url('/public/assets/shogun_rural_terroir_winter.jpg') !important;
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
    z-index: 25;
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.2s ease;
    user-select: none;
}

.rural-plot-badge:hover {
    transform: translate(-50%, -50%) scale(1.12);
    z-index: 45;
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

<div class="container">

    <!-- En-tête féodal & Bannière du Domaine Rural -->
    <div class="card mb-3 shadow-sm" style="border-left: 4px solid #10b981;">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="mb-0 fs-2 fw-bold d-flex align-items-center gap-2">
                    <span>🌾</span> Domaine Rural &amp; Terroirs du Fief
                    <span class="badge bg-green-lt fw-bold font-monospace" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-map-location-dot me-1"></i>9 Domaines Stratégiques
                    </span>
                    <span class="badge bg-primary-lt fw-bold" style="font-size: 0.72rem;">
                        Progression Niveaux 20 à 100
                    </span>
                    <span class="badge bg-warning-lt fw-bold" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-clock me-1"></i>Chantiers Asynchrones
                    </span>
                </h2>
                <div class="text-secondary small mt-1">
                    Gouvernance de <strong><?= htmlspecialchars($planet['name']) ?></strong> [<?= (int)$planet['coord_x'] ?>:<?= (int)$planet['coord_y'] ?>] &bull;
                    Capacité d'habitation : <strong class="text-primary"><?= number_format($maxPop) ?> villageois</strong> &bull;
                    Main-d'œuvre active : <strong><?= number_format($workforce['assigned_workers']) ?></strong> / <?= number_format($workforce['required_workers']) ?> requis
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="?page=city" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-chess-rook me-1"></i> Cité Castrale (Dorf 2) &rarr;
                </a>
            </div>
        </div>
    </div>    

    <!-- Grille Principale (Scène Interactive à Gauche, Chantiers & Troupes à Droite) -->
    <div class="grid-main">

        <!-- ========================================================
             COLONNE GAUCHE : CARTE ILLUSTRÉE PANORAMIQUE 16:9 FIXE
             ======================================================== -->
        <div>
            <div class="rural-map-card">
                <!-- En-tête de la carte -->
                <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-dark text-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-map text-warning"></i>
                        <span class="fw-bold">Panorama Féodal du Terroir</span>
                        <span class="badge bg-dark-lt text-white-50 border border-secondary" style="font-size:0.7rem;">
                            16:9 Haute Définition
                        </span>
                        <?php if ($isTerran): ?>
                            <span class="badge bg-danger text-white fw-bold" style="font-size:0.7rem;" title="Privilège du Clan Oda : 1 chantier rural et 1 chantier urbain peuvent progresser simultanément">
                                <i class="fa-solid fa-bolt me-1"></i>Double Chantier (Clan Oda)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Sélecteur Météo & Cycle Jour/Nuit/Saisons -->
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-dark text-warning border-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="btnAtmosphereDropdown" style="font-size:0.75rem; padding: 2px 10px;" title="Changer le climat, la saison ou le cycle jour/nuit du Terroir">
                                <i class="fa-solid fa-clock text-primary me-1" id="iconAtmosphere"></i><span id="txtAtmosphereLabel">Météo : Auto</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow border-secondary" style="font-size:0.8rem; min-width: 220px;">
                                <li><h6 class="dropdown-header text-muted font-monospace"><i class="fa-solid fa-clock me-1"></i>HORLOGE RÉELLE</h6></li>
                                <li><a class="dropdown-item weather-dropdown-item active" data-theme="auto" href="javascript:void(0)" onclick="setShogunWeather('auto')"><i class="fa-solid fa-clock me-2 text-primary"></i>Cycle Réel (Heure locale)</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><h6 class="dropdown-header text-muted font-monospace"><i class="fa-solid fa-cloud-sun me-1"></i>CLIMATS DU FIEF</h6></li>
                                <li><a class="dropdown-item weather-dropdown-item" data-theme="day" href="javascript:void(0)" onclick="setShogunWeather('day')"><i class="fa-solid fa-sun me-2 text-warning"></i>Plein Jour (Estampe claire)</a></li>
                                <li><a class="dropdown-item weather-dropdown-item" data-theme="dusk" href="javascript:void(0)" onclick="setShogunWeather('dusk')"><i class="fa-solid fa-cloud-sun me-2 text-orange"></i>Crépuscule d'Ambre (Yūgure)</a></li>
                                <li><a class="dropdown-item weather-dropdown-item" data-theme="night" href="javascript:void(0)" onclick="setShogunWeather('night')"><i class="fa-solid fa-moon me-2 text-info"></i>Nuit &amp; Lanternes Allumées (Yoru)</a></li>
                                <li><a class="dropdown-item weather-dropdown-item" data-theme="snow" href="javascript:void(0)" onclick="setShogunWeather('snow')"><i class="fa-solid fa-snowflake me-2 text-cyan"></i>Hiver sous la Neige (Yuki)</a></li>
                                <li><a class="dropdown-item weather-dropdown-item" data-theme="rain" href="javascript:void(0)" onclick="setShogunWeather('rain')"><i class="fa-solid fa-cloud-rain me-2 text-teal"></i>Pluie &amp; Feuilles d'Érable (Ame)</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item weather-dropdown-item text-muted" data-theme="off" href="javascript:void(0)" onclick="setShogunWeather('off')"><i class="fa-solid fa-pause me-2"></i>Désactiver animations (Mode Zen)</a></li>
                            </ul>
                        </div>
                        <?= AiPromptHelper::renderBadge('shogun_rural_terroir_9plots.jpg', 'Panorama Stratégique des 9 Parcelles Féodales', '/public/assets/shogun_rural_terroir_9plots.jpg', '', true) ?>
                    </div>
                </div>

                <!-- Conteneur Panoramique 16:9 Fixe (sans pan/zoom) -->
                <div class="rural-map-container" id="ruralMapContainer">

                    <!-- Calque Vivant Estampe Animée (Brume, Hérons, Paysans, Charrette, Fumée, Sakura) -->
                    <?php require __DIR__ . '/partials/rural_atmosphere_overlay.php'; ?>

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
                                           '<span class="text-warning"><i class="fa-solid fa-hammer fa-spin me-1"></i>En travaux vers Niv. ' . $targetLevel . '</span><br>' .
                                           '<small class="text-muted">Cliquer pour voir l\'état du chantier</small>';
                            } else {
                                $tooltip = '<strong>' . htmlspecialchars($p['name']) . '</strong> (Niv. ' . $p['level'] . ' / ' . $p['max_level'] . ')<br>' .
                                           '<span class="text-warning">' . htmlspecialchars($p['prod_label']) . '</span><br>' .
                                           '<small class="text-muted">' . htmlspecialchars($p['worker_role']) . ' : ' . $p['workers_assigned'] . ' ouvriers<br><em>Cliquer pour gérer &amp; élever</em></small>';
                            }
                        ?>
                        <div class="rural-plot-badge <?= $isTenshu ? 'tenshu-badge' : '' ?>"
                             id="rural-badge-<?= $type ?>"
                             style="left: <?= $p['pos_x'] ?>%; top: <?= $p['pos_y'] ?>%; --badge-color: <?= $p['color'] ?>; --badge-glow: <?= $p['color'] ?>80;"
                             data-type="<?= $type ?>"
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
                             data-tile-img="<?= htmlspecialchars($p['tile_img']) ?>"
                             data-bg-img="<?= htmlspecialchars($p['bg_image']) ?>"
                             data-cost-metal="<?= (int)$cost['metal'] ?>"
                             data-cost-crystal="<?= (int)$cost['crystal'] ?>"
                             data-cost-clay="<?= (int)$cost['clay'] ?>"
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
                                            <i class="fa-solid fa-hammer fa-spin text-warning"></i>
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
        </div>

        <!-- ========================================================
             COLONNE DROITE : CHANTIERS, RÉCOLTES & GARNISONS
             ======================================================== -->
        <div class="d-flex flex-column gap-3">
            <!-- Didacticiel Féodal & Quêtes du Daimyō -->
            <?php require __DIR__ . '/partials/quest_banner.php'; ?>

            <!-- File de Construction Mutualisée : Chantiers en cours du Fief -->
            <?php 
                $queueTitle = 'Chantiers en Cours';
                require __DIR__ . '/partials/urban_construction_queue.php'; 
            ?>

            <!-- Bilan Mutualisé des Récoltes, Stocks & Oasis Annexées -->
            <?php 
                $panelTitle = 'Récoltes, Stocks & Oasis';
                require __DIR__ . '/partials/rural_harvest_resources_panel.php'; 
            ?>


            <!-- Panel des Troupes & Garnisons -->
            <?php require __DIR__ . '/partials/troops_panel.php'; ?>
        </div>
    </div>
</div>

<!-- ========================================================
     MODALE D'AMÉLIORATION INTERACTIVE D'UNE PARCELLE (TABLER)
     ======================================================== -->
<div class="modal fade" id="plotUpgradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 bg-dark text-white">
                <h4 class="modal-title d-flex align-items-center gap-2 mb-0 fs-3">
                    <span id="modalPlotIconAvatar" class="avatar avatar-sm rounded text-white bg-primary"></span>
                    <div>
                        <span id="modalPlotTitle">Parcelle</span>
                        <div class="text-muted font-monospace" style="font-size:0.72rem;" id="modalPlotJpSubtitle"></div>
                    </div>
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Visuel & Statut -->
                <div class="d-flex gap-3 mb-3">
                    <div id="modalPlotVisualThumb" class="rounded border shadow-sm flex-shrink-0" style="width: 100px; height: 100px; background-size: cover; background-position: center;"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary fw-bold" id="modalPlotLevelBadge">Niveau 1 / 30</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.82rem;" id="modalPlotDesc"></p>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:0.78rem;">
                            <i class="fa-solid fa-person-digging text-warning"></i>
                            <span><strong id="modalPlotWorkerRole">Ouvriers</strong> : <strong class="text-body" id="modalPlotWorkersCount">2</strong> requis</span>
                        </div>
                    </div>
                </div>

                <!-- Jauge de Potentiel Vertical (20 à 100) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.75rem;">
                        <span class="text-muted fw-bold">POTENTIEL FÉODAL DE LA PARCELLE</span>
                        <span class="text-primary font-monospace fw-bold" id="modalPlotPotentialText">35%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="modalPlotPotentialBar" role="progressbar" style="width: 35%;"></div>
                    </div>
                </div>

                <!-- Bénéfices / Production -->
                <div class="p-2 mb-3 rounded bg-surface-secondary border">
                    <div class="d-flex justify-content-between align-items-center" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-chart-line text-success me-1"></i> Rendement / Apport actuel :</span>
                        <strong class="text-success" id="modalPlotCurrentProd">+35 / h</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-arrow-trend-up text-primary me-1"></i> Prochain niveau :</span>
                        <strong class="text-primary" id="modalPlotNextProd">+55 / h</strong>
                    </div>
                </div>

                <!-- Statut Chantier en cours (si actif) -->
                <div id="modalPlotActiveWorkBox" class="d-none alert alert-warning p-2 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold fs-4 text-dark"><i class="fa-solid fa-hammer fa-bounce me-1"></i> Chantier en cours</span>
                        <span class="font-monospace fw-bold text-dark fs-3" id="modalPlotWorkCountdown">--:--:--</span>
                    </div>
                    <div class="progress mb-1" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="modalPlotWorkProgressBar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="small text-muted" id="modalPlotWorkFinishLabel"></div>
                </div>

                <!-- Coûts requis & Durée (si pas de chantier en cours) -->
                <div id="modalPlotNormalUpgradeBox">
                    <div class="mb-3">
                        <label class="form-label mb-2 fw-bold text-muted" style="font-size:0.75rem;">COÛTS REQUIS POUR L'ÉLÉVATION :</label>
                        <div class="d-flex flex-wrap gap-2" id="modalPlotCostTags"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted mb-3" style="font-size:0.8rem;">
                        <span><i class="fa-regular fa-clock me-1"></i> Durée estimée du chantier :</span>
                        <strong class="font-monospace text-body" id="modalPlotDuration">00:01:30</strong>
                    </div>
                </div>

                <!-- Bouton d'action -->
                <div id="modalPlotActionContainer">
                    <button type="button" class="btn btn-primary w-100" id="btnModalPlotUpgrade">
                        <i class="fa-solid fa-arrow-up me-1"></i> Élever la structure
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
    }
};
</script>
<script src="/public/js/rural_domain_map.js?v=<?= file_exists(__DIR__ . '/../public/js/rural_domain_map.js') ? filemtime(__DIR__ . '/../public/js/rural_domain_map.js') : time() ?>"></script>
