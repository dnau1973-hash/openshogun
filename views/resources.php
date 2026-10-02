<?php
/**
 * Vue des Parcelles de Ressources & Carte Interactive du Terroir Rural
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../core/TerroirEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();
$terroirEngine = new TerroirEngine();

$fields = $planetEngine->getFields((int)$planet['id']);
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);
$mapZones = $terroirEngine->getMapZones((int)$planet['id'], $planet);

$queue = $buildingEngine->getQueue((int)$planet['id']);

// Indexer les champs par slot et calculer les totaux par ressource
$fieldsBySlot = [];
$fieldCounts = [
    'metal_mine' => 0,
    'crystal_mine' => 0,
    'deuterium_synth' => 0,
    'solar_plant' => 0
];
foreach ($fields as $f) {
    $sNum = (int)$f['field_slot'];
    $fieldsBySlot[$sNum] = $f;
    if (isset($fieldCounts[$f['type']])) {
        $fieldCounts[$f['type']]++;
    }
}

// Détection de l'archétype de terroir du village
$terroir = VillageFieldGenerator::detectArchetype($fields);

// Indexer la file active pour repérer les parcelles en cours d'amélioration
$activeFieldQueue = [];
$fieldsInQueue = 0;
foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $activeFieldQueue[(int)$q['target_id']] = $q;
        $fieldsInQueue++;
    }
}

$isTerran = (($user['faction'] ?? 'terran') === 'terran');
$canQueueNewField = $isTerran ? ($fieldsInQueue === 0) : (count($queue) === 0);

$bgVersion = file_exists(__DIR__ . '/../public/assets/shogun_rural_terroir_bg.jpg')
    ? filemtime(__DIR__ . '/../public/assets/shogun_rural_terroir_bg.jpg') : 1;
?>

<style>
/* Forcer la largeur maximale de la page pour profiter pleinement du terroir féodal */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

/* CARTE INTERACTIVE DU TERROIR RURAL (Panoramic Portrait / High-Angle Landscape) */
.rural-terroir-map-viewport {
    position: relative !important;
    width: 100% !important;
    max-width: 1050px !important;
    margin: 0 auto !important;
    aspect-ratio: 1696 / 2528 !important;
    background-image: url('/public/assets/shogun_rural_terroir_bg.jpg?v=<?= $bgVersion ?>') !important;
    background-size: 100% 100% !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    border-radius: 14px !important;
    border: 2px solid rgba(255, 255, 255, 0.16) !important;
    box-shadow: inset 0 0 60px rgba(0, 0, 0, 0.75), 0 12px 36px rgba(0, 0, 0, 0.45) !important;
    overflow: hidden !important;
    user-select: none !important;
}

/* Badges interactifs des zones du Terroir */
.terroir-zone-badge {
    position: absolute !important;
    transform: translate(-50%, -50%) !important;
    cursor: pointer !important;
    z-index: 20 !important;
    transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.25s ease !important;
}

.terroir-zone-badge:hover {
    transform: translate(-50%, -50%) scale(1.18) !important;
    z-index: 50 !important;
}

.zone-badge-pill {
    display: flex !important;
    align-items: center !important;
    gap: 0.55rem !important;
    padding: 0.38rem 0.85rem !important;
    border-radius: 50px !important;
    background: rgba(15, 23, 42, 0.88) !important;
    backdrop-filter: blur(8px) !important;
    border: 2px solid #eab308 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.65), 0 0 15px rgba(234, 179, 8, 0.45) !important;
    white-space: nowrap !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    transition: all 0.22s ease !important;
}

.terroir-zone-badge:hover .zone-badge-pill {
    background: rgba(15, 23, 42, 0.98) !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.85), 0 0 25px rgba(234, 179, 8, 0.95) !important;
    border-color: #ffffff !important;
}

.zone-badge-icon {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    font-size: 0.85rem !important;
    color: #ffffff !important;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.5) !important;
}

.zone-badge-rate {
    font-size: 0.75rem !important;
    opacity: 0.9 !important;
    padding-left: 0.3rem !important;
    border-left: 1px solid rgba(255, 255, 255, 0.25) !important;
    font-weight: 600 !important;
}

.zone-badge-beacon {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    width: 100% !important;
    height: 100% !important;
    transform: translate(-50%, -50%) !important;
    border-radius: 50px !important;
    border: 1.5px solid #eab308 !important;
    pointer-events: none !important;
    animation: zone-beacon-pulse 2.2s infinite ease-out !important;
}

@keyframes zone-beacon-pulse {
    0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.95; }
    100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

/* Hotspots pour la vue alternative des 18 parcelles */
.fields-viewport.rts-surface {
    position: relative !important;
    width: 100% !important;
    aspect-ratio: 16 / 9 !important;
    background-image: url('/public/assets/shogun_rural_terroir_bg_legacy.jpg') !important;
    background-size: cover !important;
    background-position: center !important;
    border-radius: 12px !important;
    border: 2px solid var(--border-color) !important;
    box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.5), 0 8px 24px rgba(0, 0, 0, 0.4) !important;
}

.rts-surface .rts-hotspot {
    position: absolute !important;
    cursor: pointer !important;
    border-radius: 8px !important;
    background: transparent !important;
    border: 1.5px solid transparent !important;
}
.rts-surface .rts-hotspot:hover {
    background: rgba(234, 179, 8, 0.14) !important;
    border: 1.5px solid rgba(234, 179, 8, 0.85) !important;
    box-shadow: 0 0 18px rgba(234, 179, 8, 0.5) !important;
}
</style>
<?= SlotPositionEngine::renderCss('resources') ?>

<div class="grid-main">
    <!-- Vue Principale du Terroir -->
    <div class="card shadow-sm">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title mb-1">
                    <i class="fa-solid fa-map-location-dot text-warning me-2"></i>Carte Interactive du Terroir Rural &mdash; <?= htmlspecialchars($planet['name']) ?>
                </h2>
                <div style="font-size:0.8rem; color:var(--text-muted);">
                    Terroir : <strong style="color:var(--border-highlight, #dc2626);"><?= $terroir['icon'] ?> <?= htmlspecialchars($terroir['name']) ?></strong>
                    &bull; 7 Zones d'activités &bull; 5 Slots de développement par zone
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Bascule entre vue interactive et vue 18 parcelles -->
                <button type="button" class="btn btn-sm btn-outline-warning" id="btn-toggle-view" onclick="toggleTerroirView()">
                    <i class="fa-solid fa-table-cells-large me-1"></i> <span id="label-toggle-view">Vue 18 Parcelles</span>
                </button>
                <a href="?page=city" class="btn btn-sm btn-secondary">Cité Castrale &rarr;</a>
            </div>
        </div>

        <div class="card-body">
            <!-- Barre de sélection rapide des 7 Zones Thématiques -->
            <div class="d-flex flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <a href="?page=view_resource&type=wood" class="btn btn-sm btn-outline-success">
                    <i class="fa-solid fa-tree me-1"></i> Forêt &amp; Bois
                </a>
                <a href="?page=view_resource&type=stone" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-mountain me-1"></i> Montagne &amp; Pierre
                </a>
                <a href="?page=view_resource&type=clay" class="btn btn-sm btn-outline-warning">
                    <i class="fa-solid fa-jar me-1"></i> Argile &amp; Poterie
                </a>
                <a href="?page=view_resource&type=rice" class="btn btn-sm btn-outline-warning">
                    <i class="fa-solid fa-wheat-awn me-1"></i> Rizières (Koku)
                </a>
                <a href="?page=view_resource&type=tea" class="btn btn-sm btn-outline-teal">
                    <i class="fa-solid fa-leaf me-1"></i> Champs de Thé
                </a>
                <a href="?page=view_resource&type=soybean" class="btn btn-sm btn-outline-warning">
                    <i class="fa-solid fa-seedling me-1"></i> Champs de Soja
                </a>
                <a href="?page=view_resource&type=village" class="btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-people-roof me-1"></i> Village &amp; Moulins
                </a>
            </div>

            <!-- 1. VUE PRINCIPALE : CARTE INTERACTIVE DU TERROIR RURAL (7 ZONES) -->
            <div id="container-interactive-map">
                <div class="rural-terroir-map-viewport">
                    <!-- 1. ⛰️ Montagne : Extraction de pierre -->
                    <div class="terroir-zone-badge"
                         style="top: 25.0%; left: 58.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>⛰️ Montagne &amp; Carrières</strong><br>Extraction de Granit &amp; Pierre de Taille<br><span class='text-info fw-bold'>Cadence : <?= $mapZones['stone']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=stone'">
                        <div class="zone-badge-beacon" style="border-color:#94a3b8;"></div>
                        <div class="zone-badge-pill" style="border-color:#94a3b8;">
                            <span class="zone-badge-icon" style="background:#475569;">
                                <i class="fa-solid fa-mountain"></i>
                            </span>
                            <span>Montagne</span>
                            <span class="zone-badge-rate text-info"><?= $mapZones['stone']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 2. 🌲 Forêt : Bois / Bûcheronnage -->
                    <div class="terroir-zone-badge"
                         style="top: 38.0%; left: 21.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🌲 Forêt &amp; Bûcherons</strong><br>Exploitation des Nobles Cèdres (Charpentes &amp; Armes)<br><span class='text-success fw-bold'>Cadence : <?= $mapZones['wood']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=wood'">
                        <div class="zone-badge-beacon" style="border-color:#22c55e;"></div>
                        <div class="zone-badge-pill" style="border-color:#22c55e;">
                            <span class="zone-badge-icon" style="background:#16a34a;">
                                <i class="fa-solid fa-tree"></i>
                            </span>
                            <span>Forêt</span>
                            <span class="zone-badge-rate text-success"><?= $mapZones['wood']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 3. 🏺 Argile : Gisement alluvial et poterie -->
                    <div class="terroir-zone-badge"
                         style="top: 51.0%; left: 81.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🏺 Gisement d'Argile &amp; Fours</strong><br>Extraction alluviale &amp; Cuisson de Tuiles Kawara<br><span class='text-warning fw-bold'><?= $mapZones['clay']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=clay'">
                        <div class="zone-badge-beacon" style="border-color:#f59e0b;"></div>
                        <div class="zone-badge-pill" style="border-color:#f59e0b;">
                            <span class="zone-badge-icon" style="background:#d97706;">
                                <i class="fa-solid fa-jar"></i>
                            </span>
                            <span>Argile</span>
                            <span class="zone-badge-rate text-warning"><?= $mapZones['clay']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 4. 🌾 Rizières : Culture du riz (nourriture vitale) -->
                    <div class="terroir-zone-badge"
                         style="top: 62.0%; left: 52.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🌾 Terrasses Rizicoles Inondées</strong><br>Culture du Riz Impérial (Koku de subsistance)<br><span class='text-warning fw-bold'>Cadence : <?= $mapZones['rice']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=rice'">
                        <div class="zone-badge-beacon" style="border-color:#eab308;"></div>
                        <div class="zone-badge-pill" style="border-color:#eab308;">
                            <span class="zone-badge-icon" style="background:#ca8a04;">
                                <i class="fa-solid fa-wheat-awn"></i>
                            </span>
                            <span>Rizières</span>
                            <span class="zone-badge-rate text-warning"><?= $mapZones['rice']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 5. 🍵 Thé : Champs de thé -->
                    <div class="terroir-zone-badge"
                         style="top: 54.0%; left: 20.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🍵 Champs de Thé &amp; Séchage</strong><br>Coteaux étagés de thé vert &amp; Matcha d'harmonie<br><span class='text-teal fw-bold'><?= $mapZones['tea']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=tea'">
                        <div class="zone-badge-beacon" style="border-color:#14b8a6;"></div>
                        <div class="zone-badge-pill" style="border-color:#14b8a6;">
                            <span class="zone-badge-icon" style="background:#0d9488;">
                                <i class="fa-solid fa-leaf"></i>
                            </span>
                            <span>Thé</span>
                            <span class="zone-badge-rate text-teal"><?= $mapZones['tea']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 6. 🫘 Soja : Champs de soja (farine, tofu) -->
                    <div class="terroir-zone-badge"
                         style="top: 72.0%; left: 19.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🫘 Champs de Soja &amp; Tofu</strong><br>Cultures légumineuses, farine végétale &amp; Miso<br><span class='text-warning fw-bold'><?= $mapZones['soybean']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 slots &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=soybean'">
                        <div class="zone-badge-beacon" style="border-color:#d97706;"></div>
                        <div class="zone-badge-pill" style="border-color:#d97706;">
                            <span class="zone-badge-icon" style="background:#b45309;">
                                <i class="fa-solid fa-seedling"></i>
                            </span>
                            <span>Soja</span>
                            <span class="zone-badge-rate text-warning"><?= $mapZones['soybean']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- 7. ⛩️ Village central : Logements, Sanctuaires shinto, Moulins -->
                    <div class="terroir-zone-badge"
                         style="top: 90.0%; left: 53.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>⛩️ Village Central &amp; Démographie</strong><br>Logements Minka, Sanctuaires &amp; Meuneries<br><span class='text-danger fw-bold'><?= $mapZones['village']['rate_label'] ?></span><br><small class='text-warning'>Cliquer pour ouvrir les 5 constructions &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=village'">
                        <div class="zone-badge-beacon" style="border-color:#ef4444;"></div>
                        <div class="zone-badge-pill" style="border-color:#ef4444;">
                            <span class="zone-badge-icon" style="background:#dc2626;">
                                <i class="fa-solid fa-people-roof"></i>
                            </span>
                            <span>Village</span>
                            <span class="zone-badge-rate text-danger"><?= $mapZones['village']['rate_label'] ?></span>
                        </div>
                    </div>

                    <!-- Repère bonus : Tenshu & Donjon Central -->
                    <div class="terroir-zone-badge"
                         style="top: 34.0%; left: 50.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>🏯 Tenshu &amp; Cité Castrale</strong><br>Donjon et forteresse du Daimyō (Niveau <?= $hqLevel ?>)<br><small class='text-danger'>Visiter la Cité &rarr;</small>"
                         onclick="window.location.href='?page=city'">
                        <div class="zone-badge-beacon" style="border-color:#dc2626;"></div>
                        <div class="zone-badge-pill" style="border-color:#dc2626;">
                            <span class="zone-badge-icon" style="background:#b91c1c;">
                                <i class="fa-solid fa-chess-rook"></i>
                            </span>
                            <span>Tenshu</span>
                            <span class="zone-badge-rate text-danger">Niv. <?= $hqLevel ?></span>
                        </div>
                    </div>

                    <!-- Repère bonus : Sanctuaire Shintō & Cerisiers -->
                    <div class="terroir-zone-badge"
                         style="top: 31.0%; left: 88.0%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong>⛩️ Sanctuaire Shinto d'Inari</strong><br>Cerisiers sacrés &amp; Ferveur passive<br><small class='text-danger'>Gérer les Sanctuaires &rarr;</small>"
                         onclick="window.location.href='?page=view_resource&type=village'">
                        <div class="zone-badge-pill" style="border-color:#f43f5e; padding: 0.35rem 0.65rem;">
                            <span class="zone-badge-icon" style="background:#e11d48; width:24px; height:24px;">
                                <i class="fa-solid fa-torii-gate"></i>
                            </span>
                            <span style="font-size:0.75rem;">Sanctuaire</span>
                        </div>
                    </div>
                </div>

                <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                    <i class="fa-solid fa-circle-info text-warning me-1"></i>
                    <strong>Exploration du Terroir :</strong> Cliquez sur une zone d'activité (Montagne, Forêt, Argile, Rizières, Thé, Soja, Village) pour gérer ses 5 parcelles de développement.
                </p>
            </div>

            <!-- 2. VUE SECONDAIRE : GRILLE DES 18 PARCELLES TRAVIAN (Masquée par défaut) -->
            <div id="container-travian-grid" style="display:none;">
                <div class="rts-sector-bar mb-2">
                    <button class="sector-btn active" id="btn-sec-all" onclick="filterSector('all')"><i class="fa-solid fa-globe me-1"></i> Vue Globale</button>
                    <button class="sector-btn filter-metal" id="btn-sec-metal_mine" onclick="filterSector('metal_mine')"><i class="fa-solid fa-tree text-success me-1"></i> Bûcherons (<?= $fieldCounts['metal_mine'] ?>)</button>
                    <button class="sector-btn filter-crystal" id="btn-sec-crystal_mine" onclick="filterSector('crystal_mine')"><i class="fa-solid fa-mountain text-secondary me-1"></i> Carrières (<?= $fieldCounts['crystal_mine'] ?>)</button>
                    <button class="sector-btn filter-deut" id="btn-sec-deuterium_synth" onclick="filterSector('deuterium_synth')"><i class="fa-solid fa-wheat-awn text-warning me-1"></i> Rizières (<?= $fieldCounts['deuterium_synth'] ?>)</button>
                    <button class="sector-btn filter-energy" id="btn-sec-solar_plant" onclick="filterSector('solar_plant')"><i class="fa-solid fa-torii-gate text-danger me-1"></i> Sanctuaires (<?= $fieldCounts['solar_plant'] ?>)</button>
                    <button class="sector-btn filter-hq" id="btn-sec-hq" onclick="filterSector('hq')"><i class="fa-solid fa-chess-rook text-danger me-1"></i> Tenshu Donjon (Centre)</button>
                </div>

                <div class="fields-viewport rts-surface">
                    <div class="rts-hotspot sector-hq hotspot-bunker-hq" data-sector="hq" onclick="window.location.href='?page=city'">
                        <div class="rts-level-bubble rts-tenshu-bubble"><?= $hqLevel ?></div>
                    </div>
                    <?php foreach ($fields as $f): ?>
                        <?php
                            $slot = (int)$f['field_slot'];
                            $type = $f['type'];
                            $lvl = (int)$f['level'];
                            $info = FIELD_TYPES[$type] ?? FIELD_TYPES['metal_mine'];
                            $isUpgrading = isset($activeFieldQueue[$slot]);
                            $isDemolishing = ($isUpgrading && (int)($activeFieldQueue[$slot]['target_level'] ?? -1) === 0);
                        ?>
                        <div class="rts-hotspot sector-<?= $type ?> hotspot-slot-<?= $slot ?> <?= $isUpgrading ? 'is-upgrading' : '' ?> <?= $isDemolishing ? 'is-demolishing' : '' ?>"
                             data-sector="<?= $type ?>"
                             data-slot="<?= $slot ?>"
                             onclick="window.location.href='/?page=field&slot=<?= $slot ?>'">
                            <div class="rts-level-bubble <?= $isDemolishing ? 'demolishing' : ($isUpgrading ? 'upgrading' : ($lvl === 0 ? 'level-zero' : '')) ?>">
                                <?= $isDemolishing ? '<i class="fa-solid fa-trash-can"></i>' : ($isUpgrading ? '<i class="fa-solid fa-hourglass-half"></i>' : ($lvl === 0 ? '+' : $lvl)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar : Files et Productions -->
    <div class="d-flex flex-column gap-3">
        <!-- Didacticiel Féodal & Quêtes du Daimyō -->
        <?php require __DIR__ . '/partials/quest_banner.php'; ?>

        <!-- File de Construction Active -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-trowel-bricks me-2 text-warning"></i>Chantiers du Domaine</h3>
            </div>
            <div class="card-body">
                <?php if (empty($queue)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1rem 0;">Aucun chantier en cours.</p>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <?php
                            if ($q['build_category'] === 'field') {
                                $tSlot = (int)$q['target_id'];
                                $tType = $fieldsBySlot[$tSlot]['type'] ?? FIELD_LAYOUT[$tSlot] ?? 'metal_mine';
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
                            $isDemolish = ((int)$q['target_level'] === 0);
                        ?>
                        <div class="queue-item" style="display: flex; flex-direction: column; align-items: stretch; gap: 0.4rem; padding: 0.75rem;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="queue-info">
                                    <h4 class="mb-0 fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($name) ?></h4>
                                    <?php if ($isDemolish): ?>
                                        <span class="badge bg-danger-lt fw-bold" style="font-size:0.7rem;"><i class="fa-solid fa-trash-can me-1"></i>Démantèlement</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt" style="font-size:0.7rem;">Niveau <?= $q['target_level'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-cancel" onclick="cancelBuild(<?= $q['id'] ?>)">Annuler</button>
                            </div>

                            <div class="queue-progress-box mt-1">
                                <div class="progress" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolish ? 'danger' : 'warning' ?> building-progress-bar"
                                         role="progressbar"
                                         style="width: <?= $qPct ?>%;"
                                         aria-valuenow="<?= $qPct ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"
                                         data-started="<?= $qStart ?>"
                                         data-finishes="<?= $qEnd ?>"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.75rem;">
                                    <span class="text-secondary fw-semibold">Avancement : <strong class="text-dark building-progress-pct"><?= $qPct ?>%</strong></span>
                                    <span class="queue-timer font-monospace fw-bold text-danger building-time-remaining" data-countdown="<?= $qEnd ?>">Calcul...</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Récapitulatif de la Production -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-chart-column me-2 text-primary"></i>Récoltes &amp; Sérénité</h3>
            </div>
            <div class="card-body" style="font-size:0.9rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        <i class="fa-solid fa-tree text-success me-1"></i> Bois de Cèdre :
                        <?php if (!empty($oasisBonuses['wood'])): ?>
                            <small style="color:#4ade80; font-size:0.75rem;">(+<?= $oasisBonuses['wood'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-metal);">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        <i class="fa-solid fa-mountain text-secondary me-1"></i> Pierre de Taille :
                        <?php if (!empty($oasisBonuses['stone'])): ?>
                            <small style="color:#60a5fa; font-size:0.75rem;">(+<?= $oasisBonuses['stone'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-crystal);">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        <i class="fa-solid fa-wheat-awn text-warning me-1"></i> Riz Impérial :
                        <?php if (!empty($oasisBonuses['rice'])): ?>
                            <small style="color:#fde047; font-size:0.75rem;">(+<?= $oasisBonuses['rice'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-deut);">+<?= number_format($planet['prod_rates']['deuterium']) ?> / h</strong>
                </div>
                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:0.75rem 0;">
                <div style="display:flex; justify-content:space-between;">
                    <span><i class="fa-solid fa-torii-gate text-danger me-1"></i> Ferveur &amp; Sérénité :</span>
                    <strong><?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?></strong>
                </div>
                <?php if ($planet['prod_rates']['energy_ratio'] < 1.0): ?>
                    <p style="color:#ef4444; font-size:0.75rem; margin-top:0.4rem; font-weight:700;">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Sérénité insuffisante : Les récoltes du domaine ne fonctionnent qu'à 10%.
                    </p>
                <?php endif; ?>
                <?php if (!empty($planet['prod_rates']['workforce']) && !empty($planet['prod_rates']['workforce']['is_understaffed'])): ?>
                    <p style="color:#f59e0b; font-size:0.75rem; margin-top:0.4rem; font-weight:700;">
                        <i class="fa-solid fa-people-carry-box text-warning me-1"></i> Sous-effectif : Manque d'ouvriers (-<?= $planet['prod_rates']['workforce']['understaffed_malus_pct'] ?>% sur le rendement).
                    </p>
                <?php endif; ?>

                <?php if (!empty($annexedOases)): ?>
                    <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:0.75rem 0;">
                    <div style="font-size:0.8rem; color:#86efac; font-weight:700; margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                        <span><i class="fa-solid fa-seedling text-success me-1"></i> Oasis Annexées (<?= count($annexedOases) ?> / 3)</span>
                        <a href="?page=map" style="color:var(--accent-color); text-decoration:none; font-size:0.75rem;">Carte Provinciale &rarr;</a>
                    </div>
                    <?php foreach ($annexedOases as $ao):
                        $bLabel = '';
                        if ($ao['bonus_rice'] > 0) $bLabel .= "+{$ao['bonus_rice']}% <i class=\"fa-solid fa-wheat-awn text-warning\"></i> ";
                        if ($ao['bonus_wood'] > 0) $bLabel .= "+{$ao['bonus_wood']}% <i class=\"fa-solid fa-tree text-success\"></i> ";
                        if ($ao['bonus_stone'] > 0) $bLabel .= "+{$ao['bonus_stone']}% <i class=\"fa-solid fa-mountain text-secondary\"></i> ";
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); border:1px solid rgba(34,197,94,0.2); padding:0.4rem 0.6rem; border-radius:6px; margin-bottom:0.4rem; font-size:0.8rem;">
                            <span><i class="fa-solid fa-seedling text-success me-1"></i> <?= htmlspecialchars($ao['name']) ?> [<?= $ao['coord_x'] ?> : <?= $ao['coord_y'] ?>]</span>
                            <strong style="color:#fde047;"><?= trim($bLabel) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel des Soldats (Style Travian) -->
        <?php require __DIR__ . '/partials/troops_panel.php'; ?>
    </div>
</div>

<script>
let currentTerroirView = 'interactive';

function toggleTerroirView() {
    const mapContainer = document.getElementById('container-interactive-map');
    const travianContainer = document.getElementById('container-travian-grid');
    const label = document.getElementById('label-toggle-view');

    if (currentTerroirView === 'interactive') {
        mapContainer.style.display = 'none';
        travianContainer.style.display = 'block';
        label.textContent = 'Carte Interactive';
        currentTerroirView = 'travian';
    } else {
        travianContainer.style.display = 'none';
        mapContainer.style.display = 'block';
        label.textContent = 'Vue 18 Parcelles';
        currentTerroirView = 'interactive';
    }
}

function filterSector(sector) {
    document.querySelectorAll('.sector-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-sec-${sector}`);
    if (activeBtn) activeBtn.classList.add('active');

    const hotspots = document.querySelectorAll('.rts-hotspot');
    hotspots.forEach(hs => {
        if (sector === 'all' || hs.dataset.sector === sector) {
            hs.style.opacity = '1';
            hs.classList.toggle('highlighted', sector !== 'all');
        } else {
            hs.style.opacity = '0.25';
            hs.classList.remove('highlighted');
        }
    });
}
</script>
