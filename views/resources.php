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

/* Hotspots interactifs du Terroir Féodal intégré */
.rts-surface .rts-hotspot {
    position: absolute !important;
    cursor: pointer !important;
    border-radius: 8px !important;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease !important;
    background: transparent !important;
    border: 1.5px solid transparent !important;
}

.rts-surface .rts-hotspot:hover {
    background: rgba(234, 179, 8, 0.14) !important;
    border: 1.5px solid rgba(234, 179, 8, 0.85) !important;
    box-shadow: 0 0 18px rgba(234, 179, 8, 0.5), inset 0 0 12px rgba(234, 179, 8, 0.2) !important;
}

.rts-surface .rts-hotspot.highlighted {
    background: rgba(234, 179, 8, 0.2) !important;
    border: 2px solid #eab308 !important;
    box-shadow: 0 0 20px rgba(234, 179, 8, 0.75) !important;
}

.rts-surface .rts-hotspot .rts-level-bubble {
    top: 6% !important;
    right: 8% !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease !important;
}

.rts-surface .rts-hotspot:hover .rts-level-bubble {
    transform: scale(1.15) !important;
    box-shadow: 0 0 12px rgba(234, 179, 8, 0.75) !important;
}

.rts-surface .rts-hotspot .rts-level-bubble.level-zero {
    background: rgba(28, 25, 23, 0.92) !important;
    border: 2px dashed #eab308 !important;
    color: #fef08a !important;
    font-size: 1.1rem !important;
    font-weight: 900 !important;
}

.rts-surface .rts-hotspot:hover .rts-level-bubble.level-zero {
    background: #b91c1c !important;
    border: 2px solid #fef08a !important;
    color: #ffffff !important;
    box-shadow: 0 0 14px rgba(185, 28, 28, 0.85) !important;
}
</style>
<?= SlotPositionEngine::renderCss('resources') ?>

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
                    <div class="rts-level-bubble rts-tenshu-bubble" title="Tenshu (Niveau <?= $hqLevel ?>)">
                        <?= $hqLevel ?>
                    </div>
                </div>

                <!-- 18 Exploitations de Ressources Cliquables sur le Terroir -->
                <?php foreach ($fields as $f): ?>
                    <?php 
                        $slot = (int)$f['field_slot'];
                        $type = $f['type'];
                        $lvl = (int)$f['level'];
                        $info = FIELD_TYPES[$type] ?? FIELD_TYPES['metal_mine'];
                        $isUpgrading = isset($activeFieldQueue[$slot]);
                        $isDemolishing = ($isUpgrading && (int)($activeFieldQueue[$slot]['target_level'] ?? -1) === 0);
                    ?>
                    <div class="rts-hotspot sector-<?= $type ?> hotspot-slot-<?= $slot ?> <?= $isUpgrading ? 'is-upgrading' : '' ?> <?= $isDemolishing ? 'is-demolishing' : '' ?> <?= ($lvl === 0) ? 'is-level-zero' : '' ?>" 
                         data-sector="<?= $type ?>"
                         data-slot="<?= $slot ?>"
                         title="<?= htmlspecialchars($info['name']) ?> #<?= $slot ?> (<?= $isDemolishing ? 'Démantèlement en cours' : ($isUpgrading ? 'Chantier en cours' : ($lvl > 0 ? 'Niveau ' . $lvl : 'Niveau 0 - Prêt à être fondé')) ?>)"
                         onclick="window.location.href='/?page=field&slot=<?= $slot ?>'">
                        
                        <!-- Badge féodal de niveau (Style Travian) -->
                        <div class="rts-level-bubble <?= $isDemolishing ? 'demolishing' : ($isUpgrading ? 'upgrading' : ($lvl === 0 ? 'level-zero' : '')) ?>" title="<?= htmlspecialchars($info['name']) ?> (<?= $isDemolishing ? 'Démolition vers Niv. 0' : 'Niveau ' . $lvl ?>)">
                            <?php if ($lvl === 0 && !$isUpgrading && !$isDemolishing): ?>
                                +
                            <?php elseif ($isDemolishing): ?>
                                <span class="bubble-pulse">🗑️</span>
                            <?php elseif ($isUpgrading): ?>
                                <span class="bubble-pulse">⏳</span>
                            <?php else: ?>
                                <?= $lvl ?>
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
    <div class="d-flex flex-column gap-3">
        <!-- Didacticiel Féodal & Quêtes du Daimyō -->
        <?php require __DIR__ . '/partials/quest_banner.php'; ?>

        <!-- File de Construction Active -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏗️ Chantiers du Domaine</h3>
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

