<?php
/**
 * Vue des Parcelles de Ressources (Travian-Style)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();

$fields = $planetEngine->getFields((int)$planet['id']);
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);

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

// Définition des 4 types d'exploitations agricoles constructibles au niveau 1
$resourceBuildings = [
    'metal_mine' => [
        'name' => 'Camp de Bûcherons',
        'sub' => 'Exploitation Forestière (Bois)',
        'icon' => '🪵',
        'sprite' => 'tile_bucheron.png',
        'desc' => 'Défrichage et abattage sylvicole. Fournit le bois indispensable à toutes les charpentes et armes féodales.',
        'details' => $buildingEngine->getUpgradeDetails('field', 'metal_mine', 0, $hqLevel)
    ],
    'crystal_mine' => [
        'name' => 'Carrière de Granit',
        'sub' => 'Extraction Minérale (Pierre)',
        'icon' => '🪨',
        'sprite' => 'tile_carriere.png',
        'desc' => 'Carrières à ciel ouvert taillant la pierre pour bâtir donjons, remparts, tours et murailles de forteresse.',
        'details' => $buildingEngine->getUpgradeDetails('field', 'crystal_mine', 0, $hqLevel)
    ],
    'deuterium_synth' => [
        'name' => 'Terrasses Rizicoles',
        'sub' => 'Culture du Riz Inondé (Riz)',
        'icon' => '🌾',
        'sprite' => 'tile_riziere.png',
        'desc' => 'Bassins étagés et canaux d\'irrigation produisant le riz (Koku) pour nourrir paysans et armées de samouraïs.',
        'details' => $buildingEngine->getUpgradeDetails('field', 'deuterium_synth', 0, $hqLevel)
    ],
    'solar_plant' => [
        'name' => 'Sanctuaire d\'Inari',
        'sub' => 'Temple Sacré & Torii (Énergie)',
        'icon' => '⛩️',
        'sprite' => 'tile_sanctuaire.png',
        'desc' => 'Pavillon sacré avec portiques Torii apportant sérénité et énergie spirituelle pour maximiser le rendement du domaine.',
        'details' => $buildingEngine->getUpgradeDetails('field', 'solar_plant', 0, $hqLevel)
    ]
];

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

/* Forcer la nouvelle image de fond du terroir féodal sans dépendance au cache navigateur */
.fields-viewport.rts-surface {
    position: relative !important;
    width: 100% !important;
    aspect-ratio: 16 / 9 !important;
    background-image: url('/public/assets/shogun_rural_terroir_bg.jpg?v=<?= $bgVersion ?>') !important;
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    border-radius: 12px !important;
    border: 2px solid var(--border-color) !important;
    box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.5), 0 8px 24px rgba(0, 0, 0, 0.4) !important;
    overflow: visible !important;
    user-select: none !important;
}

/* Forcer la taille et le comportement des sprites transparents */
.rts-hotspot {
    position: absolute !important;
    cursor: pointer !important;
    border-radius: 6px !important;
}

.rts-hotspot .rts-tile-sprite {
    position: absolute !important;
    bottom: 0 !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
    pointer-events: none !important;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.55)) !important;
}

.rts-tenshu-sprite {
    filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.65)) !important;
}

.rts-hotspot .rts-reticle,
.rts-hotspot .rts-reticle-alt {
    display: none !important;
}

</style>
<?= SlotPositionEngine::renderCss('resources') ?>
<style>
/* Tenshu et Cœur Castral Central */

.hotspot-bunker-hq .rts-badge {
    bottom: -6px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    background: rgba(15, 23, 42, 0.95) !important;
    border-color: #dc2626 !important;
    box-shadow: 0 0 15px rgba(220, 38, 38, 0.5) !important;
    font-size: 0.8rem !important;
    padding: 3px 10px !important;
}

.hotspot-bunker-hq:hover .rts-badge {
    box-shadow: 0 0 25px rgba(220, 38, 38, 0.9) !important;
    transform: translateX(-50%) scale(1.08) !important;
}

/* Tooltip position par défaut pour les parcelles */
.rts-hotspot .rts-tooltip {
    bottom: 105% !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
}

.hotspot-slot-17 .rts-tooltip,
.hotspot-slot-18 .rts-tooltip,
.hotspot-slot-1 .rts-tooltip,
.hotspot-slot-4 .rts-tooltip,
.hotspot-slot-16 .rts-tooltip {
    bottom: auto !important;
    top: 105% !important;
}

.rts-hotspot.is-upgrading .sprite-upgrading {
    opacity: 0.75 !important;
    filter: drop-shadow(0 0 12px rgba(245, 158, 11, 0.7)) !important;
}

/* Parcelles de ressources au niveau 0 (prêtes à être fondées) */
.rts-hotspot.is-level-zero .rts-tile-sprite {
    opacity: 0.55 !important;
    filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.45)) grayscale(35%) !important;
    transition: all 0.25s ease !important;
}

.rts-hotspot.is-level-zero:hover .rts-tile-sprite {
    opacity: 0.95 !important;
    filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.6)) grayscale(0%) !important;
    transform: translateX(-50%) scale(1.04) !important;
}

.rts-level-bubble.level-zero {
    background: rgba(255, 255, 255, 0.95) !important;
    border-color: #64748b !important;
    color: #334155 !important;
    font-weight: 800 !important;
}

.rts-hotspot.is-level-zero:hover .rts-level-bubble.level-zero {
    background: #22c55e !important;
    border-color: #15803d !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(34, 197, 94, 0.8) !important;
}
</style>

<?php require __DIR__ . '/partials/quest_banner.php'; ?>

<div class="grid-main">
    <!-- Vue Principale des Parcelles -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title">🌾 Terroir Agricole & Domaines Ruraux - <?= htmlspecialchars($planet['name']) ?></h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Terroir : <strong style="color:var(--border-highlight, #dc2626);"><?= $terroir['icon'] ?> <?= htmlspecialchars($terroir['name']) ?></strong> 
                    <span style="opacity:0.85;">(<?= $fieldCounts['metal_mine'] ?> 🪵 Bois, <?= $fieldCounts['crystal_mine'] ?> 🪨 Pierre, <?= $fieldCounts['deuterium_synth'] ?> 🌾 Riz, <?= $fieldCounts['solar_plant'] ?> ⛩️ Sanctuaires)</span>
                </div>
            </div>
            <a href="?page=city" class="btn btn-secondary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">Aller à la Cité Castrale &rarr;</a>
        </div>
        <div class="card-body">
            <!-- Barre de sélection et filtres tactiques des secteurs -->
            <div class="rts-sector-bar">
                <button class="sector-btn active" id="btn-sec-all" onclick="filterSector('all')">🌐 Vue Globale</button>
                <button class="sector-btn filter-metal" id="btn-sec-metal_mine" onclick="filterSector('metal_mine')">🪵 Bûcherons (<?= $fieldCounts['metal_mine'] ?>)</button>
                <button class="sector-btn filter-crystal" id="btn-sec-crystal_mine" onclick="filterSector('crystal_mine')">🪨 Carrières (<?= $fieldCounts['crystal_mine'] ?>)</button>
                <button class="sector-btn filter-deut" id="btn-sec-deuterium_synth" onclick="filterSector('deuterium_synth')">🌾 Rizières (<?= $fieldCounts['deuterium_synth'] ?>)</button>
                <button class="sector-btn filter-energy" id="btn-sec-solar_plant" onclick="filterSector('solar_plant')">⛩️ Sanctuaires (<?= $fieldCounts['solar_plant'] ?>)</button>
                <button class="sector-btn filter-hq" id="btn-sec-hq" onclick="filterSector('hq')">🏯 Tenshu Donjon (Centre)</button>
            </div>

            <!-- Viewport RTS de la Surface Rurale -->
            <div class="fields-viewport rts-surface">
                <!-- Tenshu & Donjon Central (Centre Cité) -->
                <div class="rts-hotspot sector-hq hotspot-bunker-hq" 
                     data-sector="hq"
                     title="🏯 Tenshu Donjon & Cité Castrale (Niveau <?= $hqLevel ?>)"
                     onclick="window.location.href='?page=city'">
                    <img src="/public/assets/tile_tenshu.png" class="rts-tile-sprite rts-tenshu-sprite" alt="Tenshu Palais" draggable="false">
                    
                    <!-- Badge niveau simple en haut à droite -->
                    <div class="rts-level-bubble rts-tenshu-bubble" title="Tenshu (Niveau <?= $hqLevel ?>)">
                        <?= $hqLevel ?>
                    </div>
                </div>

                <!-- 19 Bâtiments de Ressources Cliquables sur le Terrain -->
                <?php foreach ($fields as $f): ?>
                    <?php 
                        $slot = (int)$f['field_slot'];
                        $type = $f['type'];
                        $lvl = (int)$f['level'];
                        $info = FIELD_TYPES[$type] ?? FIELD_TYPES['metal_mine'];
                        $isUpgrading = isset($activeFieldQueue[$slot]);
                        $isDemolishing = ($isUpgrading && (int)($activeFieldQueue[$slot]['target_level'] ?? -1) === 0);

                        $tileImg = match($type) {
                            'metal_mine' => 'tile_bucheron.png',
                            'crystal_mine' => 'tile_carriere.png',
                            'deuterium_synth' => 'tile_riziere.png',
                            'solar_plant' => 'tile_sanctuaire.png',
                            default => 'tile_bucheron.png'
                        };
                    ?>
                    <div class="rts-hotspot sector-<?= $type ?> hotspot-slot-<?= $slot ?> <?= $isUpgrading ? 'is-upgrading' : '' ?> <?= $isDemolishing ? 'is-demolishing' : '' ?> <?= ($lvl === 0) ? 'is-level-zero' : '' ?>" 
                         data-sector="<?= $type ?>"
                         data-slot="<?= $slot ?>"
                         title="<?= htmlspecialchars($info['name']) ?> #<?= $slot ?> (<?= $isDemolishing ? 'Démantèlement en cours' : ($isUpgrading ? 'Chantier en cours' : ($lvl > 0 ? 'Niveau ' . $lvl : 'Niveau 0 - Prêt à être fondé')) ?>)"
                         onclick="window.location.href='/?page=field&slot=<?= $slot ?>'">
                        
                        <!-- Image PNG transparente de la ressource -->
                        <?php $tileFile = __DIR__ . '/../public/assets/' . $tileImg; ?>
                        <img src="/public/assets/<?= $tileImg ?>?v=<?= file_exists($tileFile) ? filemtime($tileFile) : time() ?>" class="rts-tile-sprite <?= $isUpgrading ? 'sprite-upgrading' : '' ?>" style="<?= $isDemolishing ? 'opacity:0.65; filter:grayscale(40%) sepia(20%);' : ($lvl === 0 ? 'opacity:0.85;' : '') ?>" alt="<?= htmlspecialchars($info['name']) ?>" draggable="false">

                        <!-- Badge minimaliste de niveau en hauteur et à droite du bâtiment (Style Travian) -->
                        <div class="rts-level-bubble <?= $isDemolishing ? 'demolishing' : ($isUpgrading ? 'upgrading' : ($lvl === 0 ? 'level-zero' : '')) ?>" title="<?= htmlspecialchars($info['name']) ?> (<?= $isDemolishing ? 'Démolition vers Niv. 0' : 'Niveau ' . $lvl ?>)">
                            <?= $lvl ?>
                            <?php if ($isDemolishing): ?>
                                <span class="bubble-pulse">🗑️</span>
                            <?php elseif ($isUpgrading): ?>
                                <span class="bubble-pulse">⏳</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                💡 <strong>Gestion du Terroir Féodal :</strong> Cliquez sur une exploitation existante ou un emplacement rural (Niv. 0) pour l'élever, ou sur le Tenshu central pour visiter votre cité castrale.
            </p>
        </div>
    </div>

    <!-- Sidebar : Files et Productions -->
    <div>
        <!-- File de Construction Active -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏗️ Chantiers du Domaine</h3>
                <?php if ($user['faction'] === 'terran'): ?>
                    <span title="Bonus Clan Oda" style="font-size:0.75rem; color:#93c5fd; background:rgba(59,130,246,0.2); padding:0.1rem 0.4rem; border-radius:4px;">Double Chantier (Oda)</span>
                <?php endif; ?>
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
                        ?>
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?= htmlspecialchars($name) ?></h4>
                                <?php if ((int)$q['target_level'] === 0): ?>
                                    <span style="font-size:0.75rem; color:#f87171; font-weight:700;">🗑️ Démantèlement (Raser)</span>
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

        <!-- Récapitulatif de la Production -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Récoltes & Sérénité</h3>
            </div>
            <div class="card-body" style="font-size:0.9rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        🪵 Bois de Cèdre :
                        <?php if (!empty($oasisBonuses['wood'])): ?>
                            <small style="color:#4ade80; font-size:0.75rem;">(+<?= $oasisBonuses['wood'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-metal);">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        🪨 Pierre de Taille :
                        <?php if (!empty($oasisBonuses['stone'])): ?>
                            <small style="color:#60a5fa; font-size:0.75rem;">(+<?= $oasisBonuses['stone'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-crystal);">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>
                        🌾 Riz Impérial :
                        <?php if (!empty($oasisBonuses['rice'])): ?>
                            <small style="color:#fde047; font-size:0.75rem;">(+<?= $oasisBonuses['rice'] ?>% Oasis)</small>
                        <?php endif; ?>
                    </span>
                    <strong style="color:var(--res-deut);">+<?= number_format($planet['prod_rates']['deuterium']) ?> / h</strong>
                </div>
                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:0.75rem 0;">
                <div style="display:flex; justify-content:space-between;">
                    <span>⛩️ Ferveur & Sérénité :</span>
                    <strong><?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?></strong>
                </div>
                <?php if ($planet['prod_rates']['energy_ratio'] < 1.0): ?>
                    <p style="color:#ef4444; font-size:0.75rem; margin-top:0.4rem; font-weight:700;">
                        ⚠️ Sérénité insuffisante : Les récoltes du domaine ne fonctionnent qu'à 10%.
                    </p>
                <?php endif; ?>

                <?php if (!empty($annexedOases)): ?>
                    <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:0.75rem 0;">
                    <div style="font-size:0.8rem; color:#86efac; font-weight:700; margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                        <span>🌿 Oasis Annexées (<?= count($annexedOases) ?> / 3)</span>
                        <a href="?page=map" style="color:var(--accent-color); text-decoration:none; font-size:0.75rem;">Carte Provinciale &rarr;</a>
                    </div>
                    <?php foreach ($annexedOases as $ao): 
                        $bLabel = '';
                        if ($ao['bonus_rice'] > 0) $bLabel .= "+{$ao['bonus_rice']}% 🌾 ";
                        if ($ao['bonus_wood'] > 0) $bLabel .= "+{$ao['bonus_wood']}% 🪵 ";
                        if ($ao['bonus_stone'] > 0) $bLabel .= "+{$ao['bonus_stone']}% 🪨 ";
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); border:1px solid rgba(34,197,94,0.2); padding:0.4rem 0.6rem; border-radius:6px; margin-bottom:0.4rem; font-size:0.8rem;">
                            <span>🌿 <?= htmlspecialchars($ao['name']) ?> [<?= $ao['coord_x'] ?> : <?= $ao['coord_y'] ?>]</span>
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

