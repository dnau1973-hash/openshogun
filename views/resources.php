<?php
/**
 * Vue des Parcelles de Ressources (Travian-Style)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();

$fields = $planetEngine->getFields((int)$planet['id']);
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

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
foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $activeFieldQueue[(int)$q['target_id']] = $q;
    }
}

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
    transition: filter 0.2s ease !important;
}

/* Au survol : aucun saut/effet yoyo, simple rehaussement de clarté doux */
.rts-hotspot:hover .rts-tile-sprite,
.rts-hotspot.highlighted .rts-tile-sprite {
    transform: translateX(-50%) !important;
    filter: drop-shadow(0 8px 14px rgba(0, 0, 0, 0.75)) brightness(1.12) !important;
}

.rts-tenshu-sprite {
    filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.65)) !important;
}

.rts-hotspot:hover {
    z-index: 50 !important;
    background: transparent !important;
}

.rts-hotspot .rts-reticle,
.rts-hotspot .rts-reticle-alt {
    opacity: 0 !important;
    transition: opacity 0.2s ease !important;
}

.rts-hotspot:hover .rts-reticle,
.rts-hotspot:hover .rts-reticle-alt,
.rts-hotspot.highlighted .rts-reticle,
.rts-hotspot.highlighted .rts-reticle-alt {
    opacity: 1 !important;
    box-shadow: inset 0 0 15px rgba(255, 255, 255, 0.1) !important;
}

.rts-hotspot .rts-badge {
    position: absolute !important;
    bottom: -6px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    white-space: nowrap !important;
    z-index: 10 !important;
    pointer-events: none !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

.rts-hotspot:hover .rts-badge,
.rts-hotspot.highlighted .rts-badge {
    border-color: #fff !important;
    box-shadow: 0 0 12px var(--sector-color) !important;
    transform: translateX(-50%) !important;
}

/* Positions calibrées sur la carte shogun_rural_terroir_bg.jpg */
.hotspot-slot-1  { left: 12.0% !important; top: 30.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 7 !important; }
.hotspot-slot-2  { left: 9.5%  !important; top: 40.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 8 !important; }
.hotspot-slot-3  { left: 11.5% !important; top: 50.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 9 !important; }
.hotspot-slot-4  { left: 20.5% !important; top: 36.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 8 !important; }
.hotspot-slot-5  { left: 24.5% !important; top: 45.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 9 !important; }
.hotspot-slot-6  { left: 21.5% !important; top: 64.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 12 !important; }
.hotspot-slot-7  { left: 29.0% !important; top: 71.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 13 !important; }
.hotspot-slot-8  { left: 31.5% !important; top: 55.6% !important; width: 11.0% !important; height: 18.6% !important; z-index: 10 !important; }
.hotspot-slot-9  { left: 39.5% !important; top: 60.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 11 !important; }
.hotspot-slot-10 { left: 49.5% !important; top: 58.6% !important; width: 11.0% !important; height: 18.6% !important; z-index: 11 !important; }
.hotspot-slot-11 { left: 60.5% !important; top: 53.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 10 !important; }
.hotspot-slot-12 { left: 59.5% !important; top: 73.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 13 !important; }
.hotspot-slot-13 { left: 68.5% !important; top: 45.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 9 !important; }
.hotspot-slot-14 { left: 73.0% !important; top: 36.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 8 !important; }
.hotspot-slot-15 { left: 80.5% !important; top: 30.6% !important; width: 11.0% !important; height: 18.6% !important; z-index: 7 !important; }
.hotspot-slot-16 { left: 73.0% !important; top: 25.1% !important; width: 11.0% !important; height: 18.6% !important; z-index: 6 !important; }
.hotspot-slot-17 { left: 64.5% !important; top: 17.6% !important; width: 11.0% !important; height: 18.6% !important; z-index: 5 !important; }
.hotspot-slot-18 { left: 56.5% !important; top: 10.6% !important; width: 11.0% !important; height: 18.6% !important; z-index: 4 !important; }

/* Tenshu et Cœur Castral Central */
.hotspot-bunker-hq {
    left: 43.5% !important;
    top: 16.0% !important;
    width: 19.0% !important;
    height: 34.0% !important;
    z-index: 7 !important;
}

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
</style>

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
                     title="Tenshu & Palais du Daimyō"
                     onclick="window.location.href='?page=city'">
                    <img src="/public/assets/tile_tenshu.png" class="rts-tile-sprite rts-tenshu-sprite" alt="Tenshu Palais" draggable="false">
                    <div class="rts-reticle"></div>
                    <div class="rts-reticle-alt"></div>
                    <div class="rts-badge">
                        <span>🏯</span>
                        <span>Tenshu</span>
                        <span class="rts-lvl-pill">Niv. <?= $hqLevel ?></span>
                    </div>
                    <div class="rts-tooltip">
                        <div class="rts-tt-title">
                            <span>🏯 Tenshu & Palais du Daimyō</span>
                            <span style="color:#dc2626;">Niv. <?= $hqLevel ?></span>
                        </div>
                        <div class="rts-tt-prod">
                            Cœur fortifié et palais du Daimyō. Accès direct aux dojos, écuries, académies et greniers du domaine.
                        </div>
                        <div class="rts-tt-cta">
                            🖱️ Cliquer pour entrer dans la Cité Castrale &rarr;
                        </div>
                    </div>
                </div>

                <!-- 18 Bâtiments de Ressources Cliquables sur le Terrain -->
                <?php foreach ($fields as $f): ?>
                    <?php 
                        $slot = (int)$f['field_slot'];
                        $type = $f['type'];
                        $lvl = (int)$f['level'];
                        $info = FIELD_TYPES[$type];
                        $details = $buildingEngine->getUpgradeDetails('field', $type, $lvl, $hqLevel);
                        $cost = $details['cost'];
                        $duration = $details['duration'];
                        $isUpgrading = isset($activeFieldQueue[$slot]);

                        $tileImg = match($type) {
                            'metal_mine' => 'tile_bucheron.png',
                            'crystal_mine' => 'tile_carriere.png',
                            'deuterium_synth' => 'tile_riziere.png',
                            'solar_plant' => 'tile_sanctuaire.png',
                            default => 'tile_bucheron.png'
                        };

                        // Calcul de la production
                        if ($type === 'solar_plant') {
                            $curProd = ($lvl > 0) ? (int)($info['base_prod'] * $lvl * pow(1.12, $lvl)) : 0;
                            $nextProd = (int)($info['base_prod'] * ($lvl + 1) * pow(1.12, $lvl + 1));
                            $prodText = "+{$curProd} ⛩️ Sérénité (Suiv : +{$nextProd})";
                        } else {
                            $curProd = ($lvl > 0) ? (int)($info['base_prod'] * $lvl * pow(1.15, $lvl)) : 0;
                            $nextProd = (int)($info['base_prod'] * ($lvl + 1) * pow(1.15, $lvl + 1));
                            $unitLabel = match($type) {
                                'metal_mine' => 'Bois/h',
                                'crystal_mine' => 'Pierre/h',
                                'deuterium_synth' => 'Riz/h',
                                default => '/h'
                            };
                            $prodText = "+{$curProd} {$unitLabel} (Suiv : +{$nextProd})";
                        }

                        $shortLabel = match($type) {
                            'metal_mine' => "Bûch. #$slot",
                            'crystal_mine' => "Carr. #$slot",
                            'deuterium_synth' => "Riz. #$slot",
                            'solar_plant' => "Sanc. #$slot",
                            default => "#$slot"
                        };

                        $canAfford = ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
                    ?>
                    <div class="rts-hotspot sector-<?= $type ?> hotspot-slot-<?= $slot ?>" 
                         data-sector="<?= $type ?>"
                         data-slot="<?= $slot ?>"
                         onclick="window.location.href='/?page=field&slot=<?= $slot ?>'">
                        
                        <!-- Image PNG transparente de la ressource -->
                        <img src="/public/assets/<?= $tileImg ?>" class="rts-tile-sprite" alt="<?= htmlspecialchars($info['name']) ?>" draggable="false">

                        <!-- Viseurs tactiques 4 coins -->
                        <div class="rts-reticle"></div>
                        <div class="rts-reticle-alt"></div>

                        <!-- Badge HUD ancré sur le bâtiment -->
                        <div class="rts-badge">
                            <span><?= $info['icon'] ?></span>
                            <span><?= $shortLabel ?></span>
                            <span class="rts-lvl-pill">Nv.<?= $lvl ?></span>
                            <?php if ($isUpgrading): ?>
                                <span class="rts-upgrading-pulse" title="Amélioration en cours">⏳</span>
                            <?php endif; ?>
                        </div>

                        <!-- Infobulle Tactique d'Amélioration (Survol) -->
                        <div class="rts-tooltip">
                            <div class="rts-tt-title">
                                <span><?= $info['icon'] ?> <?= htmlspecialchars($info['name']) ?> #<?= $slot ?></span>
                                <span style="color:var(--sector-color);">Nv. <?= $lvl ?></span>
                            </div>
                            <div class="rts-tt-prod">
                                📈 Prod : <strong><?= $prodText ?></strong>
                            </div>
                            <div class="rts-tt-cost">
                                <span style="color:var(--res-metal);">🪵 <?= number_format($cost['metal']) ?></span>
                                <span style="color:var(--res-crystal);">🪨 <?= number_format($cost['crystal']) ?></span>
                                <span style="color:var(--res-deut);">🌾 <?= number_format($cost['deuterium']) ?></span>
                                <span style="color:var(--text-muted);">⏱️ <?= gmdate('i:s', $duration) ?></span>
                            </div>
                            <div class="rts-tt-cta">
                                <?= $isUpgrading ? '⏳ Chantier en cours &rarr;' : '🔍 Inspecter & Améliorer la parcelle &rarr;' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                💡 <strong>Gestion du Domaine Féodal :</strong> Cliquez directement sur une parcelle de bûcheron, carrière, rizière ou sanctuaire pour ouvrir sa page dédiée de travaux, ou sur le Tenshu central pour visiter la cité castrale.
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
                    <span>🪵 Bois de Cèdre :</span>
                    <strong style="color:var(--res-metal);">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>🪨 Pierre de Taille :</span>
                    <strong style="color:var(--res-crystal);">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
                    <span>🌾 Riz Impérial :</span>
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

