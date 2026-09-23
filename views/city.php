<?php
/**
 * Vue de la Cité Castrale Féodale (Travian Dorf 2 Style)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$fields = $planetEngine->getFields((int)$planet['id']);
$fieldsBySlot = [];
foreach ($fields as $f) {
    $fieldsBySlot[(int)$f['field_slot']] = $f;
}
$hqLevel = $buildings['hq'] ?? 1;
$queue = $buildingEngine->getQueue((int)$planet['id']);
$activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
$pop = (int)($planet['population'] ?? 100);
$popMax = (int)($planet['population_max'] ?? 100);
$popBonus = min(25, (int)round($pop / 100));

// Indexer la file active pour repérer les bâtiments en cours d'amélioration
$activeBuildingQueue = [];
foreach ($queue as $q) {
    if ($q['build_category'] === 'building') {
        $activeBuildingQueue[$q['target_id']] = $q;
    }
}

// Catégories de secteurs urbains
$buildingSectors = [
    'hq' => 'hq',
    'shipyard' => 'military',
    'barracks' => 'military',
    'radar' => 'military',
    'wall' => 'military',
    'research_lab' => 'science',
    'embassy' => 'science',
    'storage' => 'logistics',
    'tank' => 'logistics',
    'quantum_vault' => 'logistics',
    'market' => 'logistics',
    'sawmill' => 'logistics',
    'stonemason' => 'logistics',
    'grain_mill' => 'logistics',
    'blacksmith' => 'military',
    'teahouse' => 'science',
    'tournament_square' => 'military',
    'free_plot' => 'logistics',
];

$bgVersion = file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') 
    ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1;

// Cartographie dynamique des emplacements de la cité castrale (Slots 19 à 34)
$citySlots = $planetEngine->getCitySlotMap((int)$planet['id']);

$fieldsInQueue = 0;
$buildingsInQueue = 0;
foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $fieldsInQueue++;
    } else {
        $buildingsInQueue++;
    }
}
$isTerran = ($user['faction'] === 'terran');
$canQueueNewBuilding = $isTerran ? ($buildingsInQueue < 1) : (count($queue) === 0);

// Bâtiments disponibles à la construction sur les slots libres
$availableBuildingsToConstruct = [];
foreach (BUILDINGS as $code => $bInfo) {
    $curLvl = (int)($buildings[$code] ?? 0);
    $inQueue = isset($activeBuildingQueue[$code]);
    if ($curLvl === 0 && !$inQueue) {
        $details = $buildingEngine->getUpgradeDetails('building', $code, 0, $hqLevel);
        $cost = $details['cost'];
        $canAfford = ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
        $availableBuildingsToConstruct[$code] = [
            'info' => $bInfo,
            'details' => $details,
            'can_afford' => $canAfford,
            'sector' => $buildingSectors[$code] ?? 'logistics'
        ];
    }
}
?>

<style>
/* Forcer la largeur maximale de la page pour profiter pleinement de la cité castrale */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

.fields-viewport.rts-city-surface {
    background-image: url('/public/assets/shogun_castle_city_bg.jpg?v=<?= $bgVersion ?>') !important;
}

/* Hotspots interactifs de la Cité Castrale intégrée */
.rts-city-surface .rts-hotspot {
    border-radius: 8px;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    background: transparent;
    border: 1.5px solid transparent;
}

.rts-city-surface .rts-hotspot:hover {
    background: rgba(234, 179, 8, 0.14);
    border: 1.5px solid rgba(234, 179, 8, 0.85);
    box-shadow: 0 0 18px rgba(234, 179, 8, 0.5), inset 0 0 12px rgba(234, 179, 8, 0.2);
}

.rts-city-surface .rts-hotspot.highlighted {
    background: rgba(234, 179, 8, 0.2);
    border: 2px solid #eab308;
    box-shadow: 0 0 20px rgba(234, 179, 8, 0.75);
}

.rts-city-surface .rts-hotspot .rts-level-bubble {
    top: 6%;
    right: 8%;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.rts-city-surface .rts-hotspot:hover .rts-level-bubble {
    transform: scale(1.15);
    box-shadow: 0 0 12px rgba(234, 179, 8, 0.75);
}

.rts-city-surface .rts-hotspot .rts-level-bubble.level-zero {
    background: rgba(28, 25, 23, 0.92);
    border: 2px dashed #eab308;
    color: #fef08a;
    font-size: 1.1rem;
    font-weight: 900;
}

.rts-city-surface .rts-hotspot:hover .rts-level-bubble.level-zero {
    background: #b91c1c;
    border: 2px solid #fef08a;
    color: #ffffff;
    box-shadow: 0 0 14px rgba(185, 28, 28, 0.85);
}
</style>
<?= SlotPositionEngine::renderCss('city') ?>

<div class="grid-main">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title">🏯 Cité Castrale & Palais du Daimyō - <?= htmlspecialchars($planet['name']) ?></h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Forteresse Principale : <strong style="color:var(--border-highlight, #c2252b);">Tenshu Niveau <?= $hqLevel ?></strong> 
                    <span style="opacity:0.85;">&bull; 👥 <strong><?= number_format($pop) ?></strong>/<?= number_format($popMax) ?> Habitants (<span class="text-success">+<?= $popBonus ?>% vitesse chantiers</span>)</span>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button class="btn btn-secondary" onclick="toggleCityViewMode()" id="btn-toggle-view" style="font-size:0.8rem; padding:0.35rem 0.75rem;">📋 Fiches Détaillées</button>
                <a href="?page=resources" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">🌾 Vers les Terroirs Ruraux &rarr;</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($planet['famine_active']) && !empty($planet['famine_enabled'])): ?>
                <div style="background:#fef2f2; border:2px solid #ef4444; border-radius:8px; padding:0.6rem 1rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; animation: pulse 2s infinite;">
                    <div style="display:flex; align-items:center; gap:0.6rem; color:#991b1b; font-size:0.85rem; font-weight:700;">
                        <span style="font-size:1.4rem;">⚠️</span>
                        <div>
                            <span>Disette Féodale : Vos greniers sont à sec de Farine de Riz 🍚 !</span>
                            <div style="font-size:0.75rem; font-weight:400; color:#b91c1c;">
                                Vos régiments d'élite meurent de faim ou désertent (-<?= (float)GameConfig::get('famine_rate', 3.0) ?>%/heure). Approvisionnez d'urgence votre Meunerie !
                            </div>
                        </div>
                    </div>
                    <a href="/?page=building&code=grain_mill#craftSection" class="btn btn-sm btn-danger fw-bold" style="font-size:0.75rem; padding:0.3rem 0.8rem;">
                        🍚 Moudre de la Farine d'Urgence &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($activeFeast): ?>
                <?php 
                    $feastLabels = [
                        'matsuri' => ['Matsuri Populaire des Saisons', '🏮', '#fef9c3', '#ca8a04'],
                        'warriors' => ['Banquet des Guerriers (Kanpai)', '⚔️', '#fee2e2', '#dc2626'],
                        'imperial' => ['Grand Banquet Impérial', '👑', '#ede9fe', '#7c3aed']
                    ];
                    $fData = $feastLabels[$activeFeast['feast_type']] ?? ['Célébration', '🎉', '#fef9c3', '#ca8a04'];
                ?>
                <div style="background:<?= $fData[2] ?>; border:1px solid <?= $fData[3] ?>; border-radius:8px; padding:0.5rem 1rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                    <div style="display:flex; align-items:center; gap:0.6rem; color:#1f2937; font-size:0.85rem; font-weight:600;">
                        <span style="font-size:1.3rem;"><?= $fData[1] ?></span>
                        <span>Célébration au Tenshu en cours : <strong style="color:<?= $fData[3] ?>;"><?= $fData[0] ?></strong> (Tenshu Niv. <?= (int)$activeFeast['tenshu_level'] ?>)</span>
                    </div>
                    <a href="/?page=building&code=hq#feastSection" class="btn btn-sm btn-outline-dark" style="font-size:0.75rem; padding:0.2rem 0.6rem;">
                        Accéder au Banquet &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- Barre de Filtres Tactiques de la Cité -->
            <div class="rts-sector-bar">
                <button class="sector-btn active" id="btn-city-all" onclick="filterCitySector('all')">🌐 Vue Globale Cité</button>
                <button class="sector-btn filter-hq" id="btn-city-hq" onclick="filterCitySector('hq')">🏯 Tenshu Donjon</button>
                <button class="sector-btn filter-military" id="btn-city-military" onclick="filterCitySector('military')">🥋 Dojo, Cavalerie & Remparts</button>
                <button class="sector-btn filter-science" id="btn-city-science" onclick="filterCitySector('science')">📜 Savoirs & Forge</button>
                <button class="sector-btn filter-logistics" id="btn-city-logistics" onclick="filterCitySector('logistics')">📦 Greniers & Réserves</button>
                <button class="sector-btn filter-gateway" id="btn-city-gateway" onclick="window.location.href='?page=resources'">🌾 Porte du Terroir</button>
            </div>

            <!-- Viewport RTS de la Cité Castrale (shogun_castle_city_bg.jpg) -->
            <div class="fields-viewport rts-surface rts-city-surface" id="rts-city-viewport">
                <!-- Porte fortifiée vers les terroirs ruraux (en bas à droite avec Mon) -->
                <div class="rts-hotspot sector-gateway hotspot-city-slot-gateway" 
                     data-sector="gateway"
                     title="🌾 Grande Porte Castrale (Retour aux Terroirs Ruraux)"
                     onclick="window.location.href='?page=resources'">
                    <div class="rts-level-bubble rts-gateway-bubble" title="🌾 Vers le Terroir">🌾</div>
                </div>

                <!-- Boucle sur les 16 Bâtiments de la Cité Féodale (Slots 19 à 34) -->
                <?php foreach ($citySlots as $slot => $slotData): 
                    $code = $slotData['code'];
                    if ($code === 'free_plot' && isset(CITY_SLOT_LAYOUT[$slot])) {
                        $code = CITY_SLOT_LAYOUT[$slot];
                    }
                    $lvl = (int)($slotData['level'] ?? 0);
                    if ($lvl === 0 && isset($buildings[$code])) {
                        $lvl = (int)$buildings[$code];
                    }
                    $bInfo = BUILDINGS[$code] ?? null;
                    if (!$bInfo) continue;

                    $sector = $buildingSectors[$code] ?? 'logistics';
                    $isBuildingInQueue = isset($activeBuildingQueue[$code]);
                    $isDemolishing = ($isBuildingInQueue && (int)($activeBuildingQueue[$code]['target_level'] ?? -1) === 0);
                    $isUnderConstruction = ($lvl === 0 && $isBuildingInQueue && !$isDemolishing);
                    $isUpgrading = ($lvl > 0 && $isBuildingInQueue && !$isDemolishing);
                    $isLevelZero = ($lvl === 0 && !$isBuildingInQueue);
                ?>
                    <div class="rts-hotspot sector-<?= $sector ?> <?= $isLevelZero ? 'is-level-zero' : '' ?> hotspot-city-slot-<?= $slot ?>" 
                         data-sector="<?= $sector ?>"
                         data-slot="<?= $slot ?>"
                         data-building="<?= $code ?>"
                         title="<?= htmlspecialchars($bInfo['name']) ?> (<?= $isLevelZero ? 'Non bâti - Cliquez pour fonder' : ($isDemolishing ? 'Démantèlement en cours' : ($isUnderConstruction ? 'Chantier en cours (Niv. 1)' : 'Niveau ' . $lvl)) ?>)"
                         onclick="window.location.href='/?page=building&slot=<?= $slot ?>'">
                        
                        <!-- Badge féodal de niveau (ou marqueur de fondation) -->
                        <div class="rts-level-bubble <?= ($code === 'hq') ? 'rts-tenshu-bubble' : '' ?> <?= $isLevelZero ? 'level-zero' : '' ?> <?= $isDemolishing ? 'demolishing' : (($isUnderConstruction || $isUpgrading) ? 'upgrading' : '') ?>" 
                             title="<?= htmlspecialchars($bInfo['name']) ?> (<?= $isLevelZero ? 'Non bâti - Cliquer pour ériger' : 'Niveau ' . $lvl ?>)">
                            <?php if ($isLevelZero): ?>
                                +
                            <?php elseif ($isUnderConstruction || $isUpgrading): ?>
                                <span class="bubble-pulse">⏳</span>
                            <?php elseif ($isDemolishing): ?>
                                <span class="bubble-pulse">🗑️</span>
                            <?php else: ?>
                                <?= $lvl ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                💡 <strong>Cité Castrale & Village Féodal :</strong> Cliquez sur une bâtisse ou un emplacement pour l'élever au niveau supérieur, ou accédez directement au dojo, aux écuries et à l'académie de recherche.
            </p>

            <!-- Vue Grille des Cartes Détaillées (Repliable) -->
            <div class="city-grid" id="city-cards-grid" style="display:none; margin-top:1.5rem;">
                <?php foreach (BUILDINGS as $code => $bInfo): ?>
                    <?php 
                        $lvl = $buildings[$code] ?? 0;
                        $details = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
                        $cost = $details['cost'];
                        $duration = $details['duration'];
                        $tileImg = $bInfo['tile_img'] ?? null;
                        $tileUrl = ($tileImg && file_exists(__DIR__ . '/../public/assets/tiles/' . $tileImg))
                            ? '/public/assets/tiles/' . $tileImg
                            : (($tileImg && file_exists(__DIR__ . '/../public/assets/' . $tileImg)) ? '/public/assets/' . $tileImg : null);
                    ?>
                    <div class="building-card">
                        <div class="building-avatar" style="overflow:hidden; width:72px; height:72px; padding:4px; border-radius:12px; background:rgba(0,0,0,0.03); border:1px solid rgba(194, 37, 43, 0.2); display:flex; align-items:center; justify-content:center; margin-bottom:0.75rem;">
                            <?php if ($tileUrl): ?>
                                <img src="<?= $tileUrl ?>" alt="<?= htmlspecialchars($bInfo['name']) ?>" style="width:100%; height:100%; object-fit:contain;" loading="lazy">
                            <?php else: ?>
                                <span style="font-size:2rem;"><?= $bInfo['icon'] ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="building-title"><?= htmlspecialchars($bInfo['name']) ?></h3>
                        <span class="building-lvl-badge"><?= ($lvl > 0) ? "Niveau $lvl" : "Non bâti" ?></span>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem; flex:1;">
                            <?= htmlspecialchars($bInfo['description']) ?>
                        </p>
                        
                        <div style="width:100%; display:flex; flex-direction:column; gap:0.5rem;">
                            <?php if ($code === 'shipyard' && $lvl > 0): ?>
                                <a href="?page=shipyard" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">🐎 Mobiliser Cavalerie & Siège</a>
                            <?php elseif ($code === 'barracks' && $lvl > 0): ?>
                                <a href="?page=barracks" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">🥋 Entraîner Soldats (Dojo)</a>
                            <?php elseif ($code === 'research_lab' && $lvl > 0): ?>
                                <a href="?page=research" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">📜 Académie des Savoirs</a>
                            <?php elseif ($code === 'grain_mill' && $lvl > 0): ?>
                                <a href="/?page=building&code=grain_mill#craftSection" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">🍶 Raffiner Saké &amp; Farine</a>
                            <?php endif; ?>

                            <a href="/?page=building&code=<?= $code ?>" class="btn btn-primary" style="font-size:0.75rem; padding:0.4rem 0.6rem; text-align:center; text-decoration:none;">
                                <?= ($lvl === 0) ? '🔨 Construire' : '⚡ Améliorer le niveau' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar : File de Construction Urbaine & Régiments -->
    <div>
        <!-- Didacticiel Féodal & Quêtes du Daimyō -->
        <?php require __DIR__ . '/partials/quest_banner.php'; ?>

        <!-- File Urbaine -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏗️ Chantiers Urbains</h3>
            </div>
            <div class="card-body">
                <?php if (empty($queue)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1rem 0;">Aucune construction urbaine en cours.</p>
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
                        ?>
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?= htmlspecialchars($name) ?></h4>
                                <?php if ((int)$q['target_level'] === 0): ?>
                                    <span style="font-size:0.75rem; color:#f87171; font-weight:700;">🗑️ Démolition (Raser)</span>
                                <?php else: ?>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">Niveau <?= $q['target_level'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right;">
                                <div class="queue-timer" data-countdown="<?= $q['finishes_at'] ?>">Calcul...</div>
                                <button class="btn-cancel" onclick="cancelBuild(<?= $q['id'] ?>)">Annuler</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel des Soldats (Style Travian) -->
        <?php require __DIR__ . '/partials/troops_panel.php'; ?>
    </div>
</div>

<!-- Modale de Fondation sur Emplacement Libre (Style Travian) -->
<div class="modal-overlay" id="freeSlotModal" style="display:none; position:fixed; inset:0; background:rgba(5,7,15,0.88); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
    <div class="modal-card" style="background:#0f172a; border:1px solid #334155; border-radius:12px; width:100%; max-width:920px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.7); overflow:hidden;">
        
        <!-- En-tête -->
        <div class="card-header" style="background:rgba(15,23,42,0.95); padding:1rem 1.25rem; border-bottom:1px solid #1e293b; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 class="card-title" style="margin:0; font-size:1.2rem; color:#f8fafc; display:flex; align-items:center; gap:0.5rem;">
                    <span>🏗️</span> Fonder une Structure Féodale — Emplacement #<span id="modal-slot-title" style="color:#ef4444;">19</span>
                </h3>
                <span style="font-size:0.8rem; color:#94a3b8;">Choisissez l'édifice à bâtir sur ce terrain disponible de votre forteresse.</span>
            </div>
            <button onclick="closeBuildModal()" class="modal-close-btn" style="background:none; border:none; font-size:1.6rem; color:#94a3b8; cursor:pointer; padding:0.25rem 0.5rem; border-radius:6px; line-height:1;">&times;</button>
        </div>

        <!-- Filtres par catégorie -->
        <div style="display:flex; gap:0.5rem; padding:0.75rem 1.25rem; background:rgba(0,0,0,0.25); border-bottom:1px solid #1e293b; flex-wrap:wrap;">
            <button class="btn btn-secondary modal-filter-btn active" onclick="filterModalBuildings('all')" id="modal-filter-all" style="font-size:0.8rem; padding:0.3rem 0.75rem;">🌐 Toutes (<?= count($availableBuildingsToConstruct) ?>)</button>
            <button class="btn btn-secondary modal-filter-btn" onclick="filterModalBuildings('military')" id="modal-filter-military" style="font-size:0.8rem; padding:0.3rem 0.75rem;">🥋 Militaire & Défense</button>
            <button class="btn btn-secondary modal-filter-btn" onclick="filterModalBuildings('science')" id="modal-filter-science" style="font-size:0.8rem; padding:0.3rem 0.75rem;">📜 Savoirs & Diplomatie</button>
            <button class="btn btn-secondary modal-filter-btn" onclick="filterModalBuildings('logistics')" id="modal-filter-logistics" style="font-size:0.8rem; padding:0.3rem 0.75rem;">📦 Logistique & Marché</button>
        </div>

        <!-- Corps avec défilement de la liste des bâtiments -->
        <div class="card-body" style="padding:1rem 1.25rem; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:0.75rem;">
            <?php if (empty($availableBuildingsToConstruct)): ?>
                <div style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                    <div style="font-size:2.5rem; margin-bottom:0.75rem;">🏯</div>
                    <h4 style="color:#f8fafc; margin-bottom:0.5rem;">Toutes les structures féodales sont déjà érigées !</h4>
                    <p style="font-size:0.85rem; max-width:450px; margin:0 auto;">Vous avez déjà fondé l'ensemble des bâtiments uniques du clan. Vous pouvez améliorer leurs niveaux depuis la vue générale de la cité.</p>
                </div>
            <?php else: ?>
                <?php foreach ($availableBuildingsToConstruct as $bCode => $item): 
                    $info = $item['info'];
                    $det = $item['details'];
                    $c = $det['cost'];
                    $dur = $det['duration'];
                    $canAfford = $item['can_afford'];
                    $sec = $item['sector'];
                    $durFormatted = sprintf('%02d:%02d', floor($dur / 60), $dur % 60);
                ?>
                    <div class="modal-building-row" data-sector="<?= $sec ?>" style="background:rgba(30,41,59,0.5); border:1px solid #334155; border-radius:8px; padding:0.85rem; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; transition:border-color 0.2s;">
                        <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                            <div style="width:52px; height:52px; background:rgba(15,23,42,0.8); border:1px solid #475569; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;">
                                <?php if (!empty($info['tile_img'])): 
                                    $cityTileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $info['tile_img']) ? '/public/assets/tiles/' . $info['tile_img'] : '/public/assets/' . $info['tile_img'];
                                ?>
                                    <img src="<?= $cityTileUrl ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:44px; height:44px; object-fit:contain;">
                                <?php else: ?>
                                    <span style="font-size:1.8rem;"><?= $info['icon'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                    <h4 style="margin:0; font-size:1rem; color:#f8fafc;"><?= htmlspecialchars($info['name']) ?></h4>
                                    <span style="font-size:0.7rem; padding:0.1rem 0.4rem; border-radius:4px; background:rgba(255,255,255,0.08); color:#94a3b8; text-transform:uppercase;">
                                        <?= match($sec) { 'military' => 'Militaire', 'science' => 'Savoirs', default => 'Logistique' } ?>
                                    </span>
                                </div>
                                <p style="margin:0.25rem 0 0 0; font-size:0.8rem; color:#94a3b8; line-height:1.3;">
                                    <?= htmlspecialchars($info['description']) ?>
                                </p>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                            <!-- Coûts Niveau 1 -->
                            <div style="display:flex; gap:0.75rem; font-size:0.82rem; font-weight:600;">
                                <span style="color:<?= ($planet['metal'] >= $c['metal']) ? '#4ade80' : '#ef4444' ?>;" title="Bois de Cèdre">
                                    🪵 <?= number_format($c['metal']) ?>
                                </span>
                                <span style="color:<?= ($planet['crystal'] >= $c['crystal']) ? '#4ade80' : '#ef4444' ?>;" title="Pierre de Taille">
                                    🪨 <?= number_format($c['crystal']) ?>
                                </span>
                                <span style="color:<?= ($planet['deuterium'] >= $c['deuterium']) ? '#4ade80' : '#ef4444' ?>;" title="Koku de Riz">
                                    🌾 <?= number_format($c['deuterium']) ?>
                                </span>
                                <span style="color:#94a3b8;" title="Durée des travaux">
                                    ⏳ <?= $durFormatted ?>
                                </span>
                            </div>

                            <!-- Bouton Bâtir -->
                            <div>
                                <?php if ($canAfford && $canQueueNewBuilding): ?>
                                    <button type="button" class="btn btn-primary" onclick="confirmBuildOnSlot('<?= $bCode ?>')" style="font-size:0.85rem; padding:0.45rem 0.9rem; font-weight:600; background:linear-gradient(135deg, #b91c1c, #dc2626); border-color:#ef4444; white-space:nowrap;">
                                        🔨 Bâtir (Niveau 1)
                                    </button>
                                <?php elseif (!$canAfford): ?>
                                    <button type="button" class="btn btn-secondary" disabled style="font-size:0.8rem; padding:0.45rem 0.75rem; opacity:0.6; cursor:not-allowed; white-space:nowrap;" title="Ressources insuffisantes dans vos réserves">
                                        ⚠️ Ressources insuffisantes
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary" disabled style="font-size:0.8rem; padding:0.45rem 0.75rem; opacity:0.6; cursor:not-allowed; white-space:nowrap;" title="Chantier déjà en cours dans la forteresse">
                                        ⏳ Chantier en cours
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pied de modale -->
        <div style="background:rgba(15,23,42,0.95); padding:0.75rem 1.25rem; border-top:1px solid #1e293b; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <span style="font-size:0.8rem; color:#64748b;">
                💡 Un édifice peut être érigé sur n'importe quel emplacement disponible de votre forteresse.
            </span>
            <button class="btn btn-secondary" onclick="closeBuildModal()" style="font-size:0.8rem; padding:0.35rem 0.75rem;">Fermer</button>
        </div>
    </div>
</div>

<script>
let currentSelectedSlot = null;

function openBuildModal(slot) {
    currentSelectedSlot = slot;
    const titleEl = document.getElementById('modal-slot-title');
    if (titleEl) titleEl.innerText = slot;

    const modal = document.getElementById('freeSlotModal');
    if (modal) {
        modal.style.display = 'flex';
        filterModalBuildings('all');
    }
}

function closeBuildModal() {
    const modal = document.getElementById('freeSlotModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentSelectedSlot = null;
}

function filterModalBuildings(sector) {
    document.querySelectorAll('.modal-filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.id === 'modal-filter-' + sector);
    });

    const rows = document.querySelectorAll('.modal-building-row');
    rows.forEach(r => {
        if (sector === 'all' || r.dataset.sector === sector) {
            r.style.display = 'flex';
        } else {
            r.style.display = 'none';
        }
    });
}

async function confirmBuildOnSlot(code) {
    if (!currentSelectedSlot) return;

    const btn = event.target;
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Chantier...';

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'building');
    formData.append('target_id', code);
    formData.append('slot', currentSelectedSlot);

    try {
        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeBuildModal();
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || 'Fondation du bâtiment initiée avec succès !', 'success', 'Ordre de Chantier Transmis');
            }
            window.location.reload();
        } else {
            btn.disabled = false;
            btn.innerText = originalText;
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer ce chantier.', 'error');
            } else {
                alert(data.error);
            }
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerText = originalText;
        if (typeof showModalAlert === 'function') {
            showModalAlert('Erreur de transmission avec les maîtres d\'œuvre.', 'error');
        } else {
            alert('Erreur réseau.');
        }
    }
}

// Fermeture au clic sur le fond de modale
document.addEventListener('click', function(e) {
    const modal = document.getElementById('freeSlotModal');
    if (modal && e.target === modal) {
        closeBuildModal();
    }
});

// Fermeture par touche Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBuildModal();
    }
});

function filterCitySector(sector) {
    document.querySelectorAll('.sector-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-city-${sector}`);
    if (activeBtn) activeBtn.classList.add('active');

    const hotspots = document.querySelectorAll('#rts-city-viewport .rts-hotspot');
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

function toggleCityViewMode() {
    const cardsGrid = document.getElementById('city-cards-grid');
    const toggleBtn = document.getElementById('btn-toggle-view');
    if (!cardsGrid || !toggleBtn) return;

    if (cardsGrid.style.display === 'none') {
        cardsGrid.style.display = 'grid';
        toggleBtn.innerText = '🗺️ Masquer les Fiches';
        cardsGrid.scrollIntoView({ behavior: 'smooth' });
    } else {
        cardsGrid.style.display = 'none';
        toggleBtn.innerText = '📋 Fiches Détaillées';
    }
}
</script>
