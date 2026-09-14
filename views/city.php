<?php
/**
 * Vue de la Cité Spatiale / Infrastructures Coloniales
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
    'research_lab' => 'science',
    'embassy' => 'science',
    'storage' => 'logistics',
    'tank' => 'logistics',
    'quantum_vault' => 'logistics',
    'market' => 'logistics',
];

$shortLabels = [
    'hq' => 'Tenshu Castral',
    'shipyard' => 'Écuries & Siège',
    'barracks' => 'Dojo Militaire',
    'radar' => 'Tour Yagura',
    'research_lab' => 'Académie & Forge',
    'embassy' => 'Pavillon de Clan',
    'storage' => 'Entrepôt Bois/Pierre',
    'tank' => 'Grenier à Riz',
    'quantum_vault' => 'Cache Souterraine',
    'market' => 'Marché Féodal',
];
?>

<div class="grid-main">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏯 Cité Castrale de <?= htmlspecialchars($planet['name']) ?></h2>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button class="btn btn-secondary" onclick="toggleCityViewMode()" id="btn-toggle-view" style="font-size:0.8rem; padding:0.35rem 0.75rem;">📋 Fiches Détaillées</button>
                <a href="?page=resources" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">&larr; Aller aux Terroirs Ruraux</a>
            </div>
        </div>
        <div class="card-body">
            <!-- Barre de Filtres Tactiques de la Cité -->
            <div class="rts-sector-bar">
                <button class="sector-btn active" id="btn-city-all" onclick="filterCitySector('all')">🌐 Vue Globale Cité</button>
                <button class="sector-btn filter-hq" id="btn-city-hq" onclick="filterCitySector('hq')">🏯 Tenshu Donjon</button>
                <button class="sector-btn filter-military" id="btn-city-military" onclick="filterCitySector('military')">🥋 Dojo & Cavalerie (3)</button>
                <button class="sector-btn filter-science" id="btn-city-science" onclick="filterCitySector('science')">📜 Savoirs & Diplomatie (2)</button>
                <button class="sector-btn filter-logistics" id="btn-city-logistics" onclick="filterCitySector('logistics')">📦 Greniers & Réserves (4)</button>
                <button class="sector-btn filter-gateway" id="btn-city-gateway" onclick="filterCitySector('gateway')">🌾 Vers les Terroirs</button>
            </div>

            <!-- Viewport RTS de la Cité Castrale -->
            <div class="fields-viewport rts-surface rts-city-surface" id="rts-city-viewport">
                <!-- Porte de sortie vers le terroir rural extérieur -->
                <div class="rts-hotspot sector-gateway hotspot-city-gateway_mines" 
                     data-sector="gateway"
                     title="Porte vers les Terroirs Ruraux"
                     onclick="window.location.href='?page=resources'">
                    <div class="rts-reticle"></div>
                    <div class="rts-reticle-alt"></div>
                    <div class="rts-badge">
                        <span>🌾</span>
                        <span>Vers Terroirs</span>
                    </div>
                    <div class="rts-tooltip">
                        <div class="rts-tt-title">
                            <span>🌾 Porte vers les Terroirs Ruraux</span>
                        </div>
                        <div class="rts-tt-prod">
                            Accès aux 18 parcelles de bûcherons, carrières de pierre, rizières et sanctuaires Shintō.
                        </div>
                        <div class="rts-tt-cta">
                            🖱️ Cliquer pour inspecter le domaine rural &rarr;
                        </div>
                    </div>
                </div>

                <!-- 10 Bâtiments Urbains Cliquables sur l'Image -->
                <?php foreach (BUILDINGS as $code => $bInfo): ?>
                    <?php 
                        $lvl = $buildings[$code] ?? 0;
                        $details = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
                        $cost = $details['cost'];
                        $duration = $details['duration'];
                        $isUpgrading = isset($activeBuildingQueue[$code]);
                        $sector = $buildingSectors[$code] ?? 'logistics';
                        $short = $shortLabels[$code] ?? $bInfo['name'];

                        $canAfford = ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
                    ?>
                    <div class="rts-hotspot sector-<?= $sector ?> hotspot-city-<?= $code ?>" 
                         data-sector="<?= $sector ?>"
                         data-building="<?= $code ?>"
                         onclick="openUpgradeModal('building', '<?= $code ?>', '<?= addslashes($bInfo['name']) ?>', <?= $lvl ?>, <?= $cost['metal'] ?>, <?= $cost['crystal'] ?>, <?= $cost['deuterium'] ?>, <?= $duration ?>)">
                        
                        <!-- Réticules de ciblage RTS -->
                        <div class="rts-reticle"></div>
                        <div class="rts-reticle-alt"></div>

                        <!-- Badge HUD ancré sur le bâtiment -->
                        <div class="rts-badge">
                            <span><?= $bInfo['icon'] ?></span>
                            <span><?= $short ?></span>
                            <span class="rts-lvl-pill"><?= ($lvl > 0) ? "Nv.$lvl" : 'Non bâti' ?></span>
                            <?php if ($isUpgrading): ?>
                                <span class="rts-upgrading-pulse" title="Construction en cours">⏳</span>
                            <?php endif; ?>
                        </div>

                        <!-- Infobulle Tactique (Survol) -->
                        <div class="rts-tooltip">
                            <div class="rts-tt-title">
                                <span><?= $bInfo['icon'] ?> <?= htmlspecialchars($bInfo['name']) ?></span>
                                <span style="color:var(--sector-color);"><?= ($lvl > 0) ? "Niv. $lvl" : 'Non construit' ?></span>
                            </div>
                            <div class="rts-tt-prod">
                                <?= htmlspecialchars($bInfo['description']) ?>
                            </div>
                            <div class="rts-tt-cost">
                                <span style="color:var(--res-metal);">🪵 <?= number_format($cost['metal']) ?></span>
                                <span style="color:var(--res-crystal);">🪨 <?= number_format($cost['crystal']) ?></span>
                                <span style="color:var(--res-deut);">🌾 <?= number_format($cost['deuterium']) ?></span>
                                <span style="color:var(--text-muted);">⏱️ <?= gmdate('i:s', $duration) ?></span>
                            </div>
                            <div style="margin-top:0.4rem; display:flex; flex-direction:column; gap:0.25rem;">
                                <?php if ($code === 'shipyard' && $lvl > 0): ?>
                                    <button onclick="event.stopPropagation(); window.location.href='?page=shipyard'" class="btn btn-secondary" style="font-size:0.68rem; padding:0.2rem 0.4rem; width:100%;">
                                        🐎 Mobiliser Cavalerie & Siège
                                    </button>
                                 <?php elseif ($code === 'barracks' && $lvl > 0): ?>
                                    <button onclick="event.stopPropagation(); window.location.href='?page=barracks'" class="btn btn-secondary" style="font-size:0.68rem; padding:0.2rem 0.4rem; width:100%;">
                                        🥋 Entraîner les Guerriers (Dojo)
                                    </button>
                                 <?php elseif ($code === 'research_lab' && $lvl > 0): ?>
                                    <button onclick="event.stopPropagation(); window.location.href='?page=research'" class="btn btn-secondary" style="font-size:0.68rem; padding:0.2rem 0.4rem; width:100%;">
                                        📜 Développer les Savoirs & Forges
                                    </button>
                                <?php endif; ?>
                                <div class="rts-tt-cta">
                                    <?= ($lvl === 0) ? '🔨 Cliquer pour construire' : ($canAfford ? '⚡ Cliquer pour améliorer' : '⚠️ Ressources insuffisantes') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                💡 <strong>Cité Castrale Féodale :</strong> Cliquez directement sur le Tenshu, le dojo militaire, les écuries de guerre, l'académie des savoirs ou les greniers pour lancer une construction ou accéder aux régiments.
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
                        <span class="building-lvl-badge">Niveau <?= $lvl ?></span>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem; flex:1;">
                            <?= htmlspecialchars($bInfo['description']) ?>
                        </p>
                        
                        <div style="width:100%; display:flex; flex-direction:column; gap:0.5rem;">
                            <?php if ($code === 'shipyard' && $lvl > 0): ?>
                                <a href="?page=shipyard" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">Écuries & Siège</a>
                            <?php elseif ($code === 'barracks' && $lvl > 0): ?>
                                <a href="?page=barracks" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">Dojo Militaire</a>
                            <?php elseif ($code === 'research_lab' && $lvl > 0): ?>
                                <a href="?page=research" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;">Académie des Savoirs</a>
                            <?php endif; ?>

                            <button class="btn btn-primary" style="font-size:0.75rem; padding:0.4rem 0.6rem;"
                                    onclick="openUpgradeModal('building', '<?= $code ?>', '<?= addslashes($bInfo['name']) ?>', <?= $lvl ?>, <?= $cost['metal'] ?>, <?= $cost['crystal'] ?>, <?= $cost['deuterium'] ?>, <?= $duration ?>)">
                                <?= ($lvl === 0) ? 'Construire' : 'Améliorer (Niv ' . ($lvl + 1) . ')' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Construction Queue -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏗️ File Urbaine</h3>
            </div>
            <div class="card-body">
                <?php if (empty($queue)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1rem 0;">Aucune construction urbaine.</p>
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

