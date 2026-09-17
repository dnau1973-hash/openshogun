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

                <!-- 18 Bâtiments de Ressources Cliquables sur le Terrain -->
                <?php foreach ($fields as $f): ?>
                    <?php 
                        $slot = (int)$f['field_slot'];
                        $type = $f['type'];
                        $lvl = (int)$f['level'];
                        $info = FIELD_TYPES[$type] ?? FIELD_TYPES['metal_mine'];
                        $isUpgrading = isset($activeFieldQueue[$slot]);
                        $isFreeSlot = ($lvl === 0 && !$isUpgrading);

                        $tileImg = match($type) {
                            'metal_mine' => 'tile_bucheron.png',
                            'crystal_mine' => 'tile_carriere.png',
                            'deuterium_synth' => 'tile_riziere.png',
                            'solar_plant' => 'tile_sanctuaire.png',
                            default => 'tile_bucheron.png'
                        };
                    ?>
                    <?php if ($isFreeSlot): ?>
                        <div class="rts-hotspot is-empty-plot sector-free hotspot-slot-<?= $slot ?>" 
                             data-sector="free"
                             data-slot="<?= $slot ?>"
                             title="🌱 Parcelle Disponible #<?= $slot ?> : Cliquer pour choisir la ressource à exploiter"
                             onclick="openFieldBuildModal(<?= $slot ?>)">
                            <div class="rts-level-bubble" title="Parcelle #<?= $slot ?> disponible : Cliquer pour bâtir">+</div>
                        </div>
                    <?php else: ?>
                        <div class="rts-hotspot sector-<?= $type ?> hotspot-slot-<?= $slot ?> <?= $isUpgrading ? 'is-upgrading' : '' ?>" 
                             data-sector="<?= $type ?>"
                             data-slot="<?= $slot ?>"
                             title="<?= htmlspecialchars($info['name']) ?> #<?= $slot ?> (<?= $isUpgrading ? 'Chantier en cours' : 'Niveau ' . $lvl ?>)"
                             onclick="window.location.href='/?page=field&slot=<?= $slot ?>'">
                            
                            <!-- Image PNG transparente de la ressource -->
                            <img src="/public/assets/<?= $tileImg ?>" class="rts-tile-sprite <?= $isUpgrading ? 'sprite-upgrading' : '' ?>" alt="<?= htmlspecialchars($info['name']) ?>" draggable="false">

                            <!-- Badge minimaliste de niveau en hauteur et à droite du bâtiment (Style Travian) -->
                            <div class="rts-level-bubble <?= $isUpgrading ? 'upgrading' : '' ?>" title="<?= htmlspecialchars($info['name']) ?> (Niveau <?= $lvl ?>)">
                                <?= $lvl > 0 ? $lvl : '1' ?>
                                <?php if ($isUpgrading): ?>
                                    <span class="bubble-pulse">⏳</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                💡 <strong>Gestion du Terroir Féodal :</strong> Cliquez sur une parcelle libre (<span style="color:#22c55e; font-weight:700;">+</span>) pour choisir librement la ressource à y implanter, sur une exploitation existante pour l'élever, ou sur le Tenshu central pour visiter votre cité castrale.
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

<!-- ==========================================================
     MODAL DE CHOIX LIBRE SUR PARCELLE DE RESSOURCES
     ========================================================== -->
<div id="freeFieldSlotModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.78); backdrop-filter:blur(5px); justify-content:center; align-items:center; padding:1rem;">
    <div class="modal-content card" style="max-width:820px; width:100%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; background:var(--bg-surface, #0f172a); border:1px solid #334155; box-shadow:0 25px 50px -12px rgba(0,0,0,0.85); border-radius:12px;">
        
        <!-- En-tête de la modale -->
        <div class="card-header" style="background:rgba(15,23,42,0.95); border-bottom:1px solid #1e293b; padding:1.25rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:1.25rem; color:#f8fafc; display:flex; align-items:center; gap:0.5rem;">
                    <span>🌱</span> Fonder une Exploitation Agricole — Parcelle #<span id="modal-field-slot-title" style="color:#22c55e;">1</span>
                </h3>
                <p style="margin:0.25rem 0 0 0; font-size:0.85rem; color:#94a3b8;">
                    Sélectionnez la ressource féodale que vous souhaitez exploiter sur cet emplacement rural.
                </p>
            </div>
            <button type="button" onclick="closeFieldBuildModal()" style="background:transparent; border:none; color:#94a3b8; font-size:1.5rem; cursor:pointer; padding:0.25rem 0.5rem; line-height:1;" title="Fermer (Échap)">&times;</button>
        </div>

        <!-- Corps de la modale : Liste des 4 Bâtiments de Ressources -->
        <div class="card-body" style="overflow-y:auto; padding:1.25rem; display:flex; flex-direction:column; gap:0.9rem;">
            <?php foreach ($resourceBuildings as $rCode => $rItem): 
                $det = $rItem['details'];
                $c = $det['cost'];
                $dur = $det['duration'];
                $canAfford = ($planet['metal'] >= $c['metal'] && $planet['crystal'] >= $c['crystal'] && $planet['deuterium'] >= $c['deuterium']);
                $durFormatted = sprintf('%02d:%02d', floor($dur / 60), $dur % 60);
            ?>
                <div class="modal-field-row" style="background:rgba(30,41,59,0.55); border:1px solid #334155; border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; gap:1.25rem; flex-wrap:wrap; transition:all 0.2s;">
                    <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:280px;">
                        <div style="width:60px; height:60px; background:rgba(15,23,42,0.85); border:1px solid #475569; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.4);">
                            <img src="/public/assets/<?= $rItem['sprite'] ?>" alt="<?= htmlspecialchars($rItem['name']) ?>" style="width:50px; height:50px; object-fit:contain;">
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                <h4 style="margin:0; font-size:1.05rem; color:#f8fafc; font-weight:700;">
                                    <?= htmlspecialchars($rItem['name']) ?>
                                </h4>
                                <span style="font-size:0.75rem; padding:0.15rem 0.5rem; border-radius:4px; background:rgba(34,197,94,0.15); color:#86efac; border:1px solid rgba(34,197,94,0.3); font-weight:600;">
                                    <?= htmlspecialchars($rItem['sub']) ?>
                                </span>
                            </div>
                            <p style="margin:0.35rem 0 0 0; font-size:0.8rem; color:#94a3b8; line-height:1.35;">
                                <?= htmlspecialchars($rItem['desc']) ?>
                            </p>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                        <!-- Coûts de Défrichage / Niveau 1 -->
                        <div style="display:flex; gap:0.75rem; font-size:0.85rem; font-weight:600; background:rgba(0,0,0,0.35); padding:0.45rem 0.75rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                            <span style="color:<?= ($planet['metal'] >= $c['metal']) ? '#4ade80' : '#ef4444' ?>;" title="Bois de Cèdre">
                                🪵 <?= number_format($c['metal']) ?>
                            </span>
                            <span style="color:<?= ($planet['crystal'] >= $c['crystal']) ? '#4ade80' : '#ef4444' ?>;" title="Pierre de Taille">
                                🪨 <?= number_format($c['crystal']) ?>
                            </span>
                            <span style="color:<?= ($planet['deuterium'] >= $c['deuterium']) ? '#4ade80' : '#ef4444' ?>;" title="Koku de Riz">
                                🌾 <?= number_format($c['deuterium']) ?>
                            </span>
                            <span style="color:#94a3b8;" title="Temps de défrichage">
                                ⏳ <?= $durFormatted ?>
                            </span>
                        </div>

                        <!-- Bouton Défricher / Fonder -->
                        <div>
                            <?php if ($canAfford && $canQueueNewField): ?>
                                <button type="button" class="btn btn-primary" onclick="confirmFieldBuild('<?= $rCode ?>')" style="font-size:0.85rem; padding:0.5rem 1rem; font-weight:700; background:linear-gradient(135deg, #15803d, #16a34a); border-color:#22c55e; white-space:nowrap; box-shadow:0 4px 12px rgba(34,197,94,0.3);">
                                    🌱 Fonder (Niveau 1)
                                </button>
                            <?php elseif (!$canAfford): ?>
                                <button type="button" class="btn btn-secondary" disabled style="font-size:0.8rem; padding:0.5rem 0.8rem; opacity:0.6; cursor:not-allowed; white-space:nowrap;" title="Ressources insuffisantes dans vos réserves">
                                    ⚠️ Manque de ressources
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled style="font-size:0.8rem; padding:0.5rem 0.8rem; opacity:0.6; cursor:not-allowed; white-space:nowrap;" title="Chantier agricole déjà en cours">
                                    ⏳ Chantier en cours
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pied de modale -->
        <div style="background:rgba(15,23,42,0.95); padding:0.85rem 1.5rem; border-top:1px solid #1e293b; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <span style="font-size:0.8rem; color:#64748b;">
                💡 Vous pouvez choisir librement le type d'exploitation agricole (Bois, Pierre, Riz, Sanctuaire) sur n'importe quel emplacement disponible de votre terroir.
            </span>
            <button class="btn btn-secondary" onclick="closeFieldBuildModal()" style="font-size:0.8rem; padding:0.4rem 0.9rem;">Fermer</button>
        </div>
    </div>
</div>

<script>
let currentSelectedFieldSlot = null;

function openFieldBuildModal(slot) {
    currentSelectedFieldSlot = slot;
    const titleEl = document.getElementById('modal-field-slot-title');
    if (titleEl) titleEl.innerText = slot;

    const modal = document.getElementById('freeFieldSlotModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeFieldBuildModal() {
    const modal = document.getElementById('freeFieldSlotModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentSelectedFieldSlot = null;
}

async function confirmFieldBuild(fieldType) {
    if (!currentSelectedFieldSlot) {
        showModalAlert('Aucun emplacement agricole sélectionné.', 'error');
        return;
    }

    const modal = document.getElementById('freeFieldSlotModal');
    if (modal) modal.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'field');
    formData.append('target_id', currentSelectedFieldSlot);
    formData.append('field_type', fieldType);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de lancer ce défrichage.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur castral.', 'error');
    }
}

// Fermeture par Échap ou clic extérieur
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFieldBuildModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('freeFieldSlotModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeFieldBuildModal();
            }
        });
    }
});

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

<?php if ($auth->isAdmin()): ?>
    <?php require __DIR__ . '/partials/slot_calibrator.php'; ?>
<?php endif; ?>

