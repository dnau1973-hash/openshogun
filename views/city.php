<?php
/**
 * Vue de la Cité Castrale Féodale (Travian Dorf 2 Style)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
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
    'free_plot' => 'logistics',
];

$bgVersion = file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') 
    ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1;
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

<?php require __DIR__ . '/partials/quest_banner.php'; ?>

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
                <?php foreach (CITY_SLOT_LAYOUT as $slot => $code): ?>
                    <?php 
                        if ($code === 'free_plot'):
                    ?>
                        <!-- Emplacement Libre / Terrain disponible pour future construction -->
                        <div class="rts-hotspot is-empty-plot hotspot-city-slot-<?= $slot ?>" 
                             data-sector="logistics"
                             data-slot="<?= $slot ?>"
                             title="Emplacement Libre #<?= $slot ?> (Terrain disponible)"
                             onclick="window.location.href='/?page=building&slot=<?= $slot ?>'">
                            <div class="rts-level-bubble" title="Emplacement Libre #<?= $slot ?>">+</div>
                        </div>
                    <?php 
                        else:
                            $bInfo = BUILDINGS[$code] ?? null;
                            if (!$bInfo) continue;
                            $lvl = (int)($buildings[$code] ?? 0);
                            $details = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
                            $cost = $details['cost'];
                            $duration = $details['duration'];
                            $isUpgrading = isset($activeBuildingQueue[$code]);
                            $sector = $buildingSectors[$code] ?? 'logistics';
                            $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';
                    ?>
                        <div class="rts-hotspot sector-<?= $sector ?> hotspot-city-slot-<?= $slot ?> <?= ($lvl === 0) ? 'is-empty-plot' : '' ?>" 
                             data-sector="<?= $sector ?>"
                             data-slot="<?= $slot ?>"
                             data-building="<?= $code ?>"
                             title="<?= htmlspecialchars($bInfo['name']) ?> (Niveau <?= $lvl ?>)"
                             onclick="window.location.href='/?page=building&slot=<?= $slot ?>'">
                            
                            <?php if ($lvl > 0 && $code !== 'wall'): ?>
                                <!-- Sprite PNG du bâtiment féodal -->
                                <img src="/public/assets/<?= $tileImg ?>" 
                                     class="rts-tile-sprite <?= ($code === 'hq') ? 'rts-tenshu-sprite' : '' ?>" 
                                     alt="<?= htmlspecialchars($bInfo['name']) ?>" 
                                     draggable="false">
                            <?php endif; ?>

                            <!-- Badge minimaliste de niveau en hauteur et à droite (Style Travian) -->
                            <div class="rts-level-bubble <?= ($code === 'hq') ? 'rts-tenshu-bubble' : '' ?> <?= $isUpgrading ? 'upgrading' : '' ?>" 
                                 title="<?= htmlspecialchars($bInfo['name']) ?> (Niveau <?= $lvl ?>)">
                                <?= ($lvl > 0) ? $lvl : '+' ?>
                                <?php if ($isUpgrading): ?>
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
        <!-- File Urbaine -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏗️ Chantiers Urbains</h3>
                <?php if ($user['faction'] === 'terran'): ?>
                    <span title="Bonus Clan Oda" style="font-size:0.75rem; color:#93c5fd; background:rgba(59,130,246,0.2); padding:0.1rem 0.4rem; border-radius:4px;">Chantier Simultané (Oda)</span>
                <?php endif; ?>
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
                                <span style="font-size:0.75rem; color:var(--text-muted);">Niveau <?= $q['target_level'] ?></span>
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

<script>

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
