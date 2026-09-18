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
</style>
<?= SlotPositionEngine::renderCss('city') ?>

<div class="grid-main">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title">🏯 Cité Castrale & Palais du Daimyō - <?= htmlspecialchars($planet['name']) ?></h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Forteresse Principale : <strong style="color:var(--border-highlight, #c2252b);">Tenshu Niveau <?= $hqLevel ?></strong> 
                    <span style="opacity:0.85;">(Cour intérieure fortifiée, dojos d'armes, arsenaux et greniers)</span>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button class="btn btn-secondary" onclick="toggleCityViewMode()" id="btn-toggle-view" style="font-size:0.8rem; padding:0.35rem 0.75rem;">📋 Fiches Détaillées</button>
                <a href="?page=resources" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">🌾 Vers les Terroirs Ruraux &rarr;</a>
            </div>
        </div>
        <div class="card-body">
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
                <!-- Porte fortifiée vers les terroirs ruraux (en bas à gauche avec pont-levis) -->
                <div class="rts-hotspot sector-gateway hotspot-city-slot-gateway" 
                     data-sector="gateway"
                     title="🌾 Grande Porte Fortifiée (Retour aux Terroirs Ruraux)"
                     onclick="window.location.href='?page=resources'">
                    <div class="rts-level-bubble rts-gateway-bubble" title="🌾 Vers le Terroir">🌾</div>
                </div>

                <!-- Boucle sur les 16 Slots de la Cité Féodale (Slots 19 à 34) -->
                <?php foreach ($citySlots as $slot => $slotData): 
                    $code = $slotData['code'];
                    $lvl = (int)$slotData['level'];
                    $isBuildingInQueue = ($code !== 'free_plot' && isset($activeBuildingQueue[$code]));
                    $isDemolishing = ($isBuildingInQueue && (int)($activeBuildingQueue[$code]['target_level'] ?? -1) === 0);
                    $isUnderConstruction = ($lvl === 0 && $isBuildingInQueue && !$isDemolishing);
                    $isEmptySlot = ($code === 'free_plot' || ($lvl === 0 && !$isBuildingInQueue));
                    $isWallSlot = ($code === 'wall' || (int)$slot === 34);
                ?>
                    <?php if ($isEmptySlot): ?>
                        <!-- Emplacement Libre / Terrain disponible pour future construction -->
                        <div class="rts-hotspot is-empty-plot hotspot-city-slot-<?= $slot ?>" 
                             data-sector="logistics"
                             data-slot="<?= $slot ?>"
                             title="Emplacement Libre #<?= $slot ?> (Terrain disponible - Cliquez pour ériger un bâtiment)"
                             onclick="openBuildModal(<?= $slot ?>)">
                            <div class="rts-level-bubble" title="Emplacement Libre #<?= $slot ?> (Cliquer pour bâtir)">+</div>
                        </div>
                    <?php elseif ($isUnderConstruction): 
                        $bInfo = BUILDINGS[$code] ?? null;
                        $sector = $buildingSectors[$code] ?? 'logistics';
                        $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';
                    ?>
                        <!-- Bâtiment en cours de fondation (Niveau 0 -> 1) -->
                        <div class="rts-hotspot sector-<?= $sector ?> hotspot-city-slot-<?= $slot ?>" 
                             data-sector="<?= $sector ?>"
                             data-slot="<?= $slot ?>"
                             data-building="<?= $code ?>"
                             title="<?= htmlspecialchars($bInfo['name'] ?? $code) ?> (Chantier en cours - Niveau 1)"
                             onclick="window.location.href='/?page=building&slot=<?= $slot ?>'">
                            
                            <?php if (!$isWallSlot): ?>
                                <img src="/public/assets/<?= $tileImg ?>" 
                                     class="rts-tile-sprite <?= ($code === 'hq') ? 'rts-tenshu-sprite' : '' ?>" 
                                     style="opacity:0.65; filter:drop-shadow(0 0 8px rgba(234,179,8,0.6));"
                                     alt="<?= htmlspecialchars($bInfo['name'] ?? $code) ?>" 
                                     draggable="false">
                            <?php endif; ?>

                            <div class="rts-level-bubble upgrading" title="Chantier de fondation en cours...">
                                <span class="bubble-pulse">⏳</span>
                            </div>
                        </div>
                    <?php else: 
                        $bInfo = BUILDINGS[$code] ?? null;
                        if (!$bInfo) continue;
                        $sector = $buildingSectors[$code] ?? 'logistics';
                        $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';
                        $isUpgrading = $isBuildingInQueue && !$isDemolishing;
                    ?>
                        <!-- Bâtiment érigé actif -->
                        <div class="rts-hotspot sector-<?= $sector ?> hotspot-city-slot-<?= $slot ?>" 
                             data-sector="<?= $sector ?>"
                             data-slot="<?= $slot ?>"
                             data-building="<?= $code ?>"
                             title="<?= htmlspecialchars($bInfo['name']) ?> (<?= $isDemolishing ? 'Démantèlement en cours' : 'Niveau ' . $lvl ?>)"
                             onclick="window.location.href='/?page=building&slot=<?= $slot ?>'">
                            
                            <?php if (!$isWallSlot): ?>
                                <!-- Sprite PNG du bâtiment féodal -->
                                <img src="/public/assets/<?= $tileImg ?>" 
                                     class="rts-tile-sprite <?= ($code === 'hq') ? 'rts-tenshu-sprite' : '' ?>" 
                                     alt="<?= htmlspecialchars($bInfo['name']) ?>" 
                                     style="<?= $isDemolishing ? 'opacity:0.65; filter:grayscale(40%) sepia(20%);' : '' ?>"
                                     draggable="false">
                            <?php endif; ?>

                            <!-- Badge minimaliste de niveau en hauteur et à droite (Style Travian) -->
                            <div class="rts-level-bubble <?= ($code === 'hq') ? 'rts-tenshu-bubble' : '' ?> <?= $isDemolishing ? 'demolishing' : ($isUpgrading ? 'upgrading' : '') ?>" 
                                 title="<?= htmlspecialchars($bInfo['name']) ?> (<?= $isDemolishing ? 'Démolition vers Niv. 0' : 'Niveau ' . $lvl ?>)">
                                <?= $lvl ?>
                                <?php if ($isDemolishing): ?>
                                    <span class="bubble-pulse">🗑️</span>
                                <?php elseif ($isUpgrading): ?>
                                    <span class="bubble-pulse">⏳</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                    ?>
                    <div class="building-card">
                        <div class="building-avatar"><?= $bInfo['icon'] ?></div>
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
                            <?php endif; ?>

                            <a href="/?page=building&code=<?= $code ?>" class="btn btn-primary" style="font-size:0.75rem; padding:0.4rem 0.6rem; text-align:center; text-decoration:none;">
                                <?= ($lvl === 0) ? '🔨 Construire' : '⚡ Consulter & Améliorer (Niv ' . ($lvl + 1) . ')' ?>
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
                                <?php if (!empty($info['tile_img'])): ?>
                                    <img src="/public/assets/<?= $info['tile_img'] ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:44px; height:44px; object-fit:contain;">
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
