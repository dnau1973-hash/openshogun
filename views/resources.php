<?php
/**
 * Vue des 40 Parcelles de Ressources & Terroir Féodal (OpenShogun)
 * Vue hybride : Grande illustration panoramique interactive avec Grab-and-Pan + Grille tactique des 40 parcelles
 * 8 catégories thématiques × 5 parcelles dédiées (Bois, Pierre, Argile, Riz, Thé, Soja, Sérénité, Habitations)
 * Simplification du système de logement : Modèle unique « Habitation » (Capacité = 75 + Somme des niveaux × 5)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../core/TerroirEngine.php';
require_once __DIR__ . '/../core/PopulationEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();
$terroirEngine = new TerroirEngine();

// Récupérer les 40 parcelles groupées par les 8 catégories thématiques
$all40Slots = $terroirEngine->getAll40Slots((int)$planet['id'], $planet);

// Bilan démographique avec la formule simplifiée d'habitation
$villageSummary = $terroirEngine->getVillageSummary((int)$planet['id'], $planet);
$workforce = $villageSummary['workforce'];
$contentment = $villageSummary['contentment'];
$housingCap = $villageSummary['housing_cap'];
$maxPop = $housingCap['total_capacity'];

// Oasis et files d'attente
$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);
$queue = $buildingEngine->getQueue((int)$planet['id']);

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

// Calcul des cadences de production secondaire
$clayProdHourly = 0;
foreach ($all40Slots['clay']['slots'] as $cs) { $clayProdHourly += $cs['prod_hourly']; }

$teaProdHourly = 0;
foreach ($all40Slots['tea']['slots'] as $ts) { $teaProdHourly += $ts['prod_hourly']; }

$soybeanProdHourly = 0;
foreach ($all40Slots['soybean']['slots'] as $ss) { $soybeanProdHourly += $ss['prod_hourly']; }

$shrineEnergyHourly = 0;
foreach ($all40Slots['shrine']['slots'] as $shs) { $shrineEnergyHourly += $shs['prod_hourly']; }

// Détection de l'archétype de terroir du village
$fields = $planetEngine->getFields((int)$planet['id']);
$terroir = VillageFieldGenerator::detectArchetype($fields);

$isTerran = (($user['faction'] ?? 'terran') === 'terran');
?>

<style>
/* Disposition générale du Domaine Rural Féodal */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

/* Grille principale : 40 parcelles à gauche, Sidebar chantiers/troupes à droite */
.grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1200px) {
    .grid-main {
        grid-template-columns: 1fr;
    }
}


/* Barre de filtrage rapide des 8 catégories */
.filter-category-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    background: var(--tblr-card-bg, #ffffff);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.6rem 0.75rem;
}

.btn-filter-cat {
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.85rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.btn-filter-cat.active {
    box-shadow: 0 0 10px rgba(234, 179, 8, 0.35);
}

/* ========================================================
   VUE 1 : CARTE ILLUSTRÉE PANORAMIQUE & VIEWPORT DRAG-TO-PAN
   ======================================================== */
.terroir-viewport-wrapper {
    position: relative;
    width: 100%;
    height: 720px;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    user-select: none;
    cursor: grab;
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: inset 0 0 45px rgba(0, 0, 0, 0.7), 0 10px 30px rgba(0, 0, 0, 0.15);
}

.terroir-viewport-wrapper.is-dragging {
    cursor: grabbing !important;
}

.terroir-viewport-wrapper:fullscreen {
    height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
}

/* Scène panoramique contenant l'illustration 16:9 */
.terroir-stage {
    position: absolute;
    top: 0;
    left: 0;
    width: 1376px;
    height: 768px;
    background-image: url('/public/assets/terroir_panoramic_16_9.jpg');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    transform-origin: 0 0;
    will-change: transform;
}

.terroir-stage.is-animating {
    transition: transform 0.45s cubic-bezier(0.2, 0.8, 0.25, 1) !important;
}

/* Barre d'outils flottante du viewport */
.terroir-map-toolbar {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    z-index: 50;
    pointer-events: none;
}

.terroir-map-toolbar > * {
    pointer-events: auto;
}

.terroir-controls-cluster {
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.25rem;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.terroir-controls-cluster .btn {
    padding: 0.35rem 0.65rem;
    font-size: 0.78rem;
    font-weight: 700;
}

/* Tokens / Pins Interactifs des 40 Parcelles */
.map-parcel-pin {
    position: absolute;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.94);
    backdrop-filter: blur(6px);
    border: 2.5px solid var(--pin-color, #ffffff);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.65), 0 0 14px var(--pin-glow, rgba(255, 255, 255, 0.4));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    cursor: pointer;
    transform: translate(-50%, -50%) scale(1);
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.2s ease, opacity 0.25s ease;
    z-index: 20;
}

.map-parcel-pin:hover {
    transform: translate(-50%, -50%) scale(1.25);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.8), 0 0 25px var(--pin-color, #ffffff);
    z-index: 40;
}

.map-parcel-pin.is-dimmed {
    opacity: 0.2;
    transform: translate(-50%, -50%) scale(0.8);
    pointer-events: none;
}

.map-parcel-pin.is-highlighted {
    transform: translate(-50%, -50%) scale(1.35);
    box-shadow: 0 0 25px var(--pin-color, #ffffff), 0 0 45px var(--pin-color, #ffffff);
    z-index: 45;
    animation: pinPulseGlow 1.2s infinite alternate ease-in-out;
}

@keyframes pinPulseGlow {
    0% { transform: translate(-50%, -50%) scale(1.25); box-shadow: 0 0 15px var(--pin-color, #ffffff); }
    100% { transform: translate(-50%, -50%) scale(1.42); box-shadow: 0 0 35px var(--pin-color, #ffffff); }
}

.map-parcel-pin.is-upgrading {
    border-color: #f59e0b !important;
    animation: upgradingHalo 1.4s infinite;
}

@keyframes upgradingHalo {
    0% { box-shadow: 0 0 8px #f59e0b; }
    50% { box-shadow: 0 0 25px #f59e0b, 0 0 35px rgba(245, 158, 11, 0.6); }
    100% { box-shadow: 0 0 8px #f59e0b; }
}

.pin-level-badge {
    position: absolute;
    top: -9px;
    right: -10px;
    background: #0f172a;
    border: 1.5px solid var(--pin-color, #ffffff);
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 900;
    padding: 0.05rem 0.35rem;
    border-radius: 12px;
    line-height: 1.2;
    white-space: nowrap;
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
}

.pin-workers-badge {
    position: absolute;
    bottom: -9px;
    left: -10px;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #cbd5e1;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 0.05rem 0.3rem;
    border-radius: 10px;
    line-height: 1.2;
    white-space: nowrap;
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
}

.pin-rate-pill {
    position: absolute;
    bottom: -22px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #f8fafc;
    font-size: 0.62rem;
    font-weight: 800;
    padding: 0.05rem 0.4rem;
    border-radius: 4px;
    white-space: nowrap;
    pointer-events: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.6);
}

.pin-hammer-anim {
    position: absolute;
    top: -14px;
    left: -12px;
    color: #f59e0b;
    font-size: 0.85rem;
    animation: hammerSwing 0.8s infinite ease-in-out alternate;
}

@keyframes hammerSwing {
    0% { transform: rotate(-25deg); }
    100% { transform: rotate(25deg); }
}

/* Pin Donjon Tenshu au centre */
.map-castle-pin {
    position: absolute;
    transform: translate(-50%, -50%);
    z-index: 25;
    text-decoration: none !important;
}

.castle-pin-inner {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 2.5px solid #38bdf8;
    color: #38bdf8;
    padding: 0.35rem 0.75rem;
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.82rem;
    font-weight: 800;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.7), 0 0 16px rgba(56, 189, 248, 0.5);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.map-castle-pin:hover .castle-pin-inner {
    transform: scale(1.12);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 26px rgba(56, 189, 248, 0.8);
    color: #ffffff;
}

/* ========================================================
   VUE 2 : GRILLE TACTIQUE DES 40 PARCELLES (CARTES TABLER)
   ======================================================== */
.terroir-category-section {
    background: var(--tblr-card-bg, #ffffff);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.terroir-category-section:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.category-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.category-title-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.category-icon-avatar {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

.terroir-category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.9rem;
}

@media (max-width: 1400px) {
    .terroir-category-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 600px) {
    .terroir-category-grid {
        grid-template-columns: 1fr;
    }
}

.parcel-tile-card {
    border-radius: 10px;
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    background: var(--tblr-bg-surface, #ffffff);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    position: relative;
}

.parcel-tile-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.parcel-tile-card.is-upgrading {
    border-color: #f59e0b !important;
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.3) !important;
}

.parcel-card-header {
    padding: 0.5rem 0.65rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.parcel-slot-badge {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    background: rgba(15, 23, 42, 0.1);
    color: var(--tblr-body-color, #1e293b);
}

.parcel-name-text {
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
}

.parcel-visual-box {
    position: relative;
    width: 100%;
    height: 95px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 0.45rem;
    box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.45);
}

.parcel-level-badge {
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(4px);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 900;
    padding: 0.2rem 0.55rem;
    border-radius: 20px;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
}

.parcel-workers-pill {
    background: rgba(15, 23, 42, 0.82);
    backdrop-filter: blur(4px);
    color: #e2e8f0;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.18rem 0.45rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.parcel-card-body {
    padding: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-grow: 1;
}

.parcel-prod-metric {
    font-size: 0.82rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.02);
    padding: 0.3rem 0.45rem;
    border-radius: 6px;
}

.parcel-cost-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
}

.cost-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.04);
    font-weight: 600;
}

.cost-chip.affordable {
    color: var(--tblr-body-color, #1e293b);
}

.cost-chip.missing {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    font-weight: 800;
}

.btn-upgrade-parcel {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.38rem 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}
</style>

<div class="grid-main">
    <!-- COLONNE PRINCIPALE : VUE DES 40 PARCELLES -->
    <div>
        <!-- 1. En-tête principal de la vue -->
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <div>
                    <h2 class="card-title mb-1 fs-2">
                        <i class="fa-solid fa-map-location-dot text-warning me-2"></i>Domaine Rural Féodal &mdash; <?= htmlspecialchars($planet['name']) ?>
                    </h2>
                    <div class="text-muted" style="font-size:0.82rem;">
                        Terroir : <strong class="text-danger"><?= $terroir['icon'] ?> <?= htmlspecialchars($terroir['name']) ?></strong>
                        &bull; <strong>40 Parcelles d'Exploitation &amp; d'Accueil</strong> (8 Catégories &times; 5 Parcelles)
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Sélecteur de mode d'affichage : Carte Illustrée vs Grille Tactique -->
                    <div class="btn-group" role="group" aria-label="Bascule de vue">
                        <button type="button" class="btn btn-sm btn-dark active" id="btnModeMap" onclick="switchTerroirView('map')">
                            <i class="fa-solid fa-map me-1 text-warning"></i> Carte Illustrée (40)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnModeGrid" onclick="switchTerroirView('grid')">
                            <i class="fa-solid fa-table-cells me-1"></i> Grille Tactique (40)
                        </button>
                    </div>

                    <a href="?page=city" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-chess-rook me-1"></i> Cité Castrale &rarr;
                    </a>
                </div>
            </div>



            <!-- 3. Barre de filtrage rapide des 8 Catégories Thématiques & Recentrage -->
            <div class="card-footer py-2 px-3">
                <div class="filter-category-bar">
                    <span class="text-muted fw-bold me-2 align-self-center" style="font-size:0.75rem;">
                        <i class="fa-solid fa-filter me-1"></i>Filtrer &amp; Cibler :
                    </span>
                    <button type="button" class="btn-filter-cat btn-dark active" onclick="filterCategory('all', this)">
                        <i class="fa-solid fa-globe me-1"></i> Tout afficher (40)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-success" onclick="filterCategory('wood', this)">
                        <i class="fa-solid fa-tree me-1"></i> Bois (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-secondary" onclick="filterCategory('stone', this)">
                        <i class="fa-solid fa-mountain me-1"></i> Pierre (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-warning" onclick="filterCategory('clay', this)">
                        <i class="fa-solid fa-jar me-1"></i> Argile (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-warning" onclick="filterCategory('rice', this)">
                        <i class="fa-solid fa-wheat-awn me-1"></i> Riz (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-teal" onclick="filterCategory('tea', this)">
                        <i class="fa-solid fa-leaf me-1"></i> Thé (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-orange" onclick="filterCategory('soybean', this)">
                        <i class="fa-solid fa-seedling me-1"></i> Soja (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-pink" onclick="filterCategory('shrine', this)">
                        <i class="fa-solid fa-torii-gate me-1"></i> Sérénité (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-primary" onclick="filterCategory('housing', this)">
                        <i class="fa-solid fa-house-chimney me-1"></i> Habitations (5)
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================
             VUE 1 : CARTE ILLUSTRÉE PANORAMIQUE AVEC LES 40 SLOTS
             ======================================================== -->
        <div id="terroirIllustratedMapView" class="card shadow-sm mb-3 overflow-hidden">
            <div class="terroir-viewport-wrapper" id="terroirViewport">
                
                <!-- Barre d'outils flottante du viewport -->
                <div class="terroir-map-toolbar">
                    <div class="d-flex align-items-center gap-2">
                        <?= AiPromptHelper::renderBadge('terroir_panoramic_16_9.jpg', 'Panorama 16:9 des 40 Parcelles Féodales', '/public/assets/terroir_panoramic_16_9.jpg', '', true) ?>
                        <span class="badge bg-dark-lt text-white d-none d-lg-inline-block shadow-sm">
                            <i class="fa-solid fa-arrows-up-down-left-right me-1 text-warning"></i> Glisser pour explorer &bull; Molette pour zoomer
                        </span>
                    </div>
                    
                    <div class="terroir-controls-cluster">
                        <button type="button" class="btn btn-dark text-white" onclick="zoomTerroirMap(0.18)" title="Zoomer avant (+)">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white font-monospace" onclick="resetTerroirMapZoom()" title="Ajuster la vue">
                            <span id="zoomLevelIndicator">100%</span>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="zoomTerroirMap(-0.18)" title="Zoomer arrière (-)">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="centerTerroirMap()" title="Recentrer le fief">
                            <i class="fa-solid fa-crosshairs"></i>
                        </button>
                        <button type="button" class="btn btn-dark text-white" onclick="toggleTerroirFullscreen()" title="Plein écran (⛶)">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                </div>

                <!-- Scène interactive contenant l'illustration HD et les 40 pins -->
                <div class="terroir-stage" id="terroirMapStage">
                    
                    <!-- Donjon Central Tenshu -->
                    <a href="?page=city" class="map-castle-pin" style="left: 33.0%; top: 38.0%;" title="Tenshu &mdash; Cité Castrale & Cœur du Fief (Cliquer pour entrer)">
                        <div class="castle-pin-inner">
                            <i class="fa-solid fa-chess-rook"></i>
                            <span>Tenshu</span>
                        </div>
                    </a>

                    <!-- Les 40 Slots / Parcelles d'Exploitation -->
                    <?php foreach ($all40Slots as $catKey => $catGroup): ?>
                        <?php
                            $cMeta = $catGroup['meta'];
                            $cSlots = $catGroup['slots'];
                        ?>
                        <?php foreach ($cSlots as $slotIdx => $s): ?>
                            <?php
                                $isUp = !empty($s['is_upgrading']);
                                $canAfford = !empty($s['can_afford']);
                                $cost = $s['cost'];
                                $coords = $s['map_coords'] ?? ['left' => 50, 'top' => 50];
                                $tooltipTitle = '<strong>' . htmlspecialchars($s['name']) . '</strong> (Niv. ' . $s['level'] . ')<br>' .
                                                '<span class="text-warning">' . htmlspecialchars($s['prod_label']) . '</span><br>' .
                                                '<small class="text-muted">' . htmlspecialchars($s['worker_role']) . ' : ' . $s['workers'] . ' ouvriers<br><em>Cliquer pour gérer &amp; élever</em></small>';
                                $tooltipTitleAttr = htmlspecialchars($tooltipTitle, ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="map-parcel-pin <?= $isUp ? 'is-upgrading' : '' ?>"
                                 id="map-pin-<?= $catKey ?>-<?= $slotIdx ?>"
                                 style="left: <?= $coords['left'] ?>%; top: <?= $coords['top'] ?>%; --pin-color: <?= $cMeta['color'] ?>; --pin-glow: <?= $cMeta['color'] ?>80;"
                                 data-cat="<?= $catKey ?>"
                                 data-slot-idx="<?= $slotIdx ?>"
                                 data-global-idx="<?= $s['global_index'] ?>"
                                 data-res-type="<?= $catKey ?>"
                                 data-name="<?= htmlspecialchars($s['name']) ?>"
                                 data-jp-name="<?= htmlspecialchars($cMeta['jp_name']) ?>"
                                 data-category-name="<?= htmlspecialchars($cMeta['name']) ?>"
                                 data-desc="<?= htmlspecialchars($s['desc']) ?>"
                                 data-level="<?= $s['level'] ?>"
                                 data-workers="<?= $s['workers'] ?>"
                                 data-worker-role="<?= htmlspecialchars($s['worker_role']) ?>"
                                 data-prod-label="<?= htmlspecialchars($s['prod_label']) ?>"
                                 data-tile-img="<?= htmlspecialchars($s['tile_img']) ?>"
                                 data-bg-img="<?= htmlspecialchars($cMeta['bg_image']) ?>"
                                 data-cost-wood="<?= (int)($cost['metal'] ?? 0) ?>"
                                 data-cost-stone="<?= (int)($cost['crystal'] ?? 0) ?>"
                                 data-cost-clay="<?= (int)($cost['clay'] ?? 0) ?>"
                                 data-cost-rice="<?= (int)($cost['deuterium'] ?? 0) ?>"
                                 data-duration="<?= (int)($s['duration'] ?? 60) ?>"
                                 data-can-afford="<?= $canAfford ? '1' : '0' ?>"
                                 data-is-upgrading="<?= $isUp ? '1' : '0' ?>"
                                 data-color-class="<?= $cMeta['color_class'] ?>"
                                 data-color="<?= $cMeta['color'] ?>"
                                 data-icon="<?= $cMeta['icon'] ?>"
                                 data-bs-toggle="tooltip"
                                 data-bs-html="true"
                                 data-bs-placement="top"
                                 title="<?= $tooltipTitleAttr ?>"
                                 data-bs-title="<?= $tooltipTitleAttr ?>"
                                 onclick="handlePinClick(this, event)">
                                
                                <?php if ($isUp): ?>
                                    <span class="pin-hammer-anim"><i class="fa-solid fa-hammer"></i></span>
                                <?php endif; ?>

                                <i class="<?= $cMeta['icon'] ?> fs-4"></i>
                                
                                <span class="pin-level-badge <?= $isUp ? 'border-warning text-warning' : '' ?>">
                                    <?= $isUp ? 'N.' . ($s['level'] + 1) : 'N.' . $s['level'] ?>
                                </span>
                                
                                <?php if ($catKey !== 'housing'): ?>
                                    <span class="pin-workers-badge">
                                        <i class="fa-solid fa-person-digging text-warning"></i> <?= $s['workers'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="pin-workers-badge text-indigo">
                                        <i class="fa-solid fa-people-roof"></i> #<?= $slotIdx ?>
                                    </span>
                                <?php endif; ?>

                                <span class="pin-rate-pill d-none d-sm-block">
                                    <?= $s['prod_label'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- ========================================================
             VUE 2 : GRILLE TACTIQUE DES 40 PARCELLES (MASQUÉE PAR DÉFAUT)
             ======================================================== -->
        <div id="terroirTacticalGridView" style="display: none;">
            <?php foreach ($all40Slots as $catKey => $catGroup): ?>
                <?php
                    $cMeta = $catGroup['meta'];
                    $cSlots = $catGroup['slots'];
                ?>
                <div class="terroir-category-section" id="cat-section-<?= $catKey ?>" data-cat="<?= $catKey ?>">
                    
                    <!-- En-tête de la catégorie -->
                    <div class="category-section-header">
                        <div class="category-title-group">
                            <span class="category-icon-avatar bg-<?= $cMeta['color_class'] ?>-lt text-<?= $cMeta['color_class'] ?>">
                                <i class="<?= $cMeta['icon'] ?>"></i>
                            </span>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="mb-0 fw-bold fs-3"><?= htmlspecialchars($cMeta['name']) ?></h3>
                                    <span class="badge bg-<?= $cMeta['color_class'] ?>-lt fw-bold"><?= $cMeta['badge_text'] ?></span>
                                    <span class="text-muted font-monospace" style="font-size:0.75rem;"><?= $cMeta['jp_name'] ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?= htmlspecialchars($cMeta['desc']) ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="badge bg-dark-lt text-white px-3 py-2 fw-bold" style="font-size:0.8rem;">
                                5 Parcelles &bull; Bénéfice : <strong class="text-<?= $cMeta['color_class'] ?>"><?= $cMeta['res_name'] ?></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Grille des 5 Tuiles / Parcelles -->
                    <div class="terroir-category-grid">
                        <?php foreach ($cSlots as $slotIdx => $s): ?>
                            <?php
                                $isUp = !empty($s['is_upgrading']);
                                $canAfford = !empty($s['can_afford']);
                                $cost = $s['cost'];
                            ?>
                            <div class="parcel-tile-card <?= $isUp ? 'is-upgrading' : '' ?>" id="card-parcel-<?= $catKey ?>-<?= $slotIdx ?>">
                                
                                <!-- Haut de tuile : Nom et Repère -->
                                <div class="parcel-card-header">
                                    <span class="parcel-slot-badge">#<?= $s['global_index'] ?></span>
                                    <span class="parcel-name-text" title="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></span>
                                    <span class="badge bg-<?= $cMeta['color_class'] ?>-lt text-<?= $cMeta['color_class'] ?>" style="font-size:0.65rem;">Slot <?= $slotIdx ?></span>
                                </div>

                                <!-- Vignette Graphique avec Niveau & Ouvriers -->
                                <div class="parcel-visual-box" style="background-image: linear-gradient(to top, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.2) 60%), url('<?= $cMeta['bg_image'] ?>');">
                                    <span class="parcel-level-badge <?= $isUp ? 'border-warning text-warning' : '' ?>">
                                        <?= $isUp ? '<i class="fa-solid fa-hourglass-half fa-spin me-1"></i>Niv. ' . ($s['level'] + 1) : 'Niveau ' . $s['level'] ?>
                                    </span>
                                    
                                    <?php if ($catKey !== 'housing'): ?>
                                        <span class="parcel-workers-pill">
                                            <i class="fa-solid fa-person-digging text-warning"></i> <?= $s['workers'] ?> ouv.
                                        </span>
                                    <?php else: ?>
                                        <span class="parcel-workers-pill text-indigo">
                                            <i class="fa-solid fa-people-roof"></i> Foyer #<?= $slotIdx ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Corps d'informations de la tuile -->
                                <div class="parcel-card-body">
                                    
                                    <!-- Métrique de rendement / apport -->
                                    <div class="parcel-prod-metric text-<?= $cMeta['color_class'] ?>">
                                        <span><i class="<?= $cMeta['icon'] ?> me-1"></i>Apport :</span>
                                        <strong><?= $s['prod_label'] ?></strong>
                                    </div>

                                    <!-- Coûts d'élévation -->
                                    <div class="parcel-cost-row">
                                        <span class="cost-chip <?= ($planet['metal'] >= $cost['metal']) ? 'affordable' : 'missing' ?>" title="Bois de Cèdre">
                                             <i class="fa-solid fa-tree text-success"></i> <?= number_format($cost['metal']) ?>
                                        </span>
                                        <span class="cost-chip <?= ($planet['crystal'] >= $cost['crystal']) ? 'affordable' : 'missing' ?>" title="Pierre de Taille">
                                            <i class="fa-solid fa-mountain text-secondary"></i> <?= number_format($cost['crystal']) ?>
                                        </span>
                                        <span class="cost-chip <?= ($planet['deuterium'] >= $cost['deuterium']) ? 'affordable' : 'missing' ?>" title="Riz Impérial">
                                            <i class="fa-solid fa-wheat-awn text-warning"></i> <?= number_format($cost['deuterium']) ?>
                                        </span>
                                    </div>

                                    <!-- Durée & Bouton d'action -->
                                    <div class="mt-auto pt-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size:0.7rem;">
                                            <span><i class="fa-regular fa-clock me-1"></i>Durée :</span>
                                            <span class="font-monospace fw-bold"><?= gmdate('i:s', $s['duration']) ?></span>
                                        </div>

                                        <?php if ($isUp): ?>
                                            <button type="button" class="btn btn-sm btn-warning w-100 disabled" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-hourglass-half fa-spin me-1"></i> En chantier...
                                            </button>
                                        <?php elseif ($canAfford): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-<?= $cMeta['color_class'] ?> w-100 btn-upgrade-parcel"
                                                    onclick="upgradeTerroirSlot('<?= $catKey ?>', <?= $slotIdx ?>, this)">
                                                <i class="fa-solid fa-arrow-up me-1"></i> Élever Niv. <?= $s['level'] + 1 ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 disabled" style="font-size:0.75rem;" title="Ressources insuffisantes pour cette élévation">
                                                <i class="fa-solid fa-lock me-1"></i> Ressources requises
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- COLONNE LATÉRALE : CHANTIERS, DIDACTICIEL, OASIS & TROUPES -->
    <div class="d-flex flex-column gap-3">
        <!-- Didacticiel Féodal & Quêtes du Daimyō -->
        <?php require __DIR__ . '/partials/quest_banner.php'; ?>

        <!-- File de Construction Active du Domaine -->
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0 fs-3">
                    <i class="fa-solid fa-trowel-bricks me-2 text-warning"></i>Chantiers Actifs
                </h3>
                <span class="badge bg-warning-lt fw-bold"><?= count($queue) ?> en cours</span>
            </div>
            <div class="card-body p-2">
                <?php if (empty($queue)): ?>
                    <div class="text-muted text-center py-3" style="font-size:0.85rem;">
                        <i class="fa-solid fa-hammer text-secondary d-block mb-1 fs-2"></i>
                        Aucun chantier en cours sur le fief.
                    </div>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <?php
                            $name = $q['target_id'];
                            if ($q['build_category'] === 'field') {
                                $tSlot = (int)$q['target_id'];
                                $tType = FIELD_LAYOUT[$tSlot] ?? 'metal_mine';
                                $name = (FIELD_TYPES[$tType]['name'] ?? 'Parcelle') . " #{$tSlot}";
                            } else {
                                $name = BUILDINGS[$q['target_id']]['name'] ?? $q['target_id'];
                            }
                            $qNow = time();
                            $qStart = (int)($q['started_at'] ?? $qNow);
                            $qEnd = (int)($q['finishes_at'] ?? $qNow);
                            $qTotal = max(1, $qEnd - $qStart);
                            $qElapsed = max(0, $qNow - $qStart);
                            $qPct = min(100, max(0, (int)round(($qElapsed / $qTotal) * 100)));
                        ?>
                        <div class="p-2 mb-2 rounded bg-surface-secondary border" style="font-size:0.85rem;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-truncate"><?= htmlspecialchars($name) ?></strong>
                                <span class="badge bg-primary-lt">Niveau <?= $q['target_level'] ?></span>
                            </div>
                            <div class="progress progress-sm mb-1">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning building-progress-bar"
                                     style="width: <?= $qPct ?>%;"
                                     data-countdown="<?= $qEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size:0.75rem;">
                                <span class="text-muted font-monospace building-time-remaining" data-countdown="<?= $qEnd ?>">Calcul...</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="cancelBuild(<?= $q['id'] ?>)">Annuler</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bilan des Récoltes & Oasis Annexées -->
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title mb-0 fs-3">
                    <i class="fa-solid fa-chart-line me-2 text-success"></i>Récoltes &amp; Oasis
                </h3>
            </div>
            <div class="card-body p-3" style="font-size:0.85rem;">
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-tree text-success me-1"></i> Bois de Cèdre :</span>
                    <strong class="text-success">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-mountain text-secondary me-1"></i> Pierre de Taille :</span>
                    <strong class="text-secondary">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i> Riz Impérial :</span>
                    <strong class="text-warning">+<?= number_format($planet['prod_rates']['deuterium']) ?> / h</strong>
                </div>

                <?php if (!empty($annexedOases)): ?>
                    <hr class="my-2">
                    <div class="fw-bold text-success mb-2" style="font-size:0.78rem;">
                        <i class="fa-solid fa-seedling me-1"></i> Oasis Annexées (<?= count($annexedOases) ?> / 3) :
                    </div>
                    <?php foreach ($annexedOases as $ao): ?>
                        <div class="d-flex justify-content-between align-items-center p-1 rounded bg-surface-secondary mb-1" style="font-size:0.75rem;">
                            <span><?= htmlspecialchars($ao['name']) ?> [<?= $ao['coord_x'] ?>:<?= $ao['coord_y'] ?>]</span>
                            <span class="badge bg-success-lt">+<?= $ao['bonus_rice'] ?? 25 ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel des Troupes & Garnisons (Style Travian) -->
        <?php require __DIR__ . '/partials/troops_panel.php'; ?>
    </div>
</div>

<!-- ========================================================
     MODALE D'AMÉLIORATION INTERACTIVE D'UNE PARCELLE
     ======================================================== -->
<div class="modal fade" id="parcelUpgradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2" id="modalHeaderBg">
                <h4 class="modal-title d-flex align-items-center gap-2 mb-0 fs-3">
                    <span id="modalIconAvatar" class="avatar avatar-sm rounded text-white bg-dark"></span>
                    <div>
                        <span id="modalTitle">Parcelle</span>
                        <div class="text-muted font-monospace" style="font-size:0.72rem;" id="modalJpSubtitle"></div>
                    </div>
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <div class="modal-body p-3">
                <!-- Visuel & Statut -->
                <div class="d-flex gap-3 mb-3">
                    <div id="modalVisualThumb" class="rounded border shadow-sm flex-shrink-0" style="width: 100px; height: 100px; background-size: cover; background-position: center;"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary fw-bold" id="modalLevelBadge">Niveau 1</span>
                            <span class="badge bg-dark-lt text-white" id="modalCategoryBadge">Catégorie</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.82rem;" id="modalDesc"></p>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size:0.78rem;">
                            <i class="fa-solid fa-person-digging text-warning"></i>
                            <span><strong id="modalWorkerRole">Ouvriers</strong> : <strong class="text-body" id="modalWorkersCount">2</strong> requis</span>
                        </div>
                    </div>
                </div>

                <!-- Bénéfices / Production -->
                <div class="p-2 mb-3 rounded bg-surface-secondary border">
                    <div class="d-flex justify-content-between align-items-center" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-chart-line text-success me-1"></i> Rendement / Apport actuel :</span>
                        <strong class="text-success" id="modalCurrentProd">+30 / h</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.85rem;">
                        <span class="text-muted"><i class="fa-solid fa-arrow-trend-up text-primary me-1"></i> Prochain niveau :</span>
                        <strong class="text-primary" id="modalNextProd">+45 / h</strong>
                    </div>
                </div>

                <!-- Coûts requis pour l'élévation -->
                <div class="mb-3">
                    <label class="form-label mb-2 fw-bold text-muted" style="font-size:0.75rem;">COÛTS REQUIS POUR L'ÉLÉVATION :</label>
                    <div class="d-flex flex-wrap gap-2" id="modalCostTags"></div>
                </div>

                <!-- Durée du chantier -->
                <div class="d-flex justify-content-between align-items-center text-muted mb-3" style="font-size:0.8rem;">
                    <span><i class="fa-regular fa-clock me-1"></i> Durée estimée du chantier :</span>
                    <strong class="font-monospace text-body" id="modalDuration">00:01:30</strong>
                </div>

                <!-- Bouton d'action -->
                <div id="modalActionContainer">
                    <button type="button" class="btn btn-primary w-100" id="btnModalUpgrade">
                        <i class="fa-solid fa-arrow-up me-1"></i> Améliorer la parcelle
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuration & stocks pour le moteur cartographique terroir_map.js
window.TERROIR_CONFIG = {
    stocks: {
        metal: <?= (int)$planet['metal'] ?>,
        crystal: <?= (int)$planet['crystal'] ?>,
        deuterium: <?= (int)$planet['deuterium'] ?>
    }
};
</script>
<script src="/public/js/terroir_map.js?v=<?= file_exists(__DIR__ . '/../public/js/terroir_map.js') ? filemtime(__DIR__ . '/../public/js/terroir_map.js') : time() ?>"></script>
