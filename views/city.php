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
    if (in_array($q['build_category'], ['field', 'rural_plot'])) {
        $fieldsInQueue++;
    } elseif ($q['build_category'] === 'building') {
        $buildingsInQueue++;
    }
}

require_once __DIR__ . '/../core/ImperialSealEngine.php';
$sealEngine = new ImperialSealEngine();
$isSealActive = $sealEngine->isSealActive((int)($user['id'] ?? 0));

$isAdmin = ((class_exists('Auth') && Auth::check() && Auth::isAdmin()) || !empty($user['is_admin']) || (($user['role'] ?? '') === 'admin'));
$isTerran = (($user['faction'] ?? 'terran') === 'terran');
$maxAllowedBuildings = $isSealActive ? 2 : 1;
$maxAllowedTotal = $isSealActive ? 2 : 1;
$canQueueNewBuilding = $isAdmin ? true : ($isTerran ? ($buildingsInQueue < $maxAllowedBuildings) : (count($queue) < $maxAllowedTotal));

// Bâtiments disponibles à la construction sur les slots libres
$availableBuildingsToConstruct = [];
foreach (BUILDINGS as $code => $bInfo) {
    $curLvl = (int)($buildings[$code] ?? 0);
    $inQueue = isset($activeBuildingQueue[$code]);
    if ($curLvl === 0 && !$inQueue) {
        $details = $buildingEngine->getUpgradeDetails('building', $code, 0, $hqLevel);
        $cost = $details['cost'];
        $canAfford = $isAdmin ? true : ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
        $availableBuildingsToConstruct[$code] = [
            'info' => $bInfo,
            'details' => $details,
            'can_afford' => $canAfford,
            'sector' => $buildingSectors[$code] ?? 'logistics'
        ];
    }
}

// Cartographie complète des données de chaque emplacement pour la modale d'élite interactive
$buildingHeroImages = [
    'hq' => '/public/assets/buildings/building_tenshu.jpg',
    'barracks' => '/public/assets/buildings/building_barracks.jpg',
    'shipyard' => '/public/assets/buildings/building_shipyard.jpg',
    'research_lab' => '/public/assets/buildings/building_research_lab.jpg',
    'radar' => '/public/assets/buildings/building_radar.jpg',
    'storage' => '/public/assets/buildings/building_storage.jpg',
    'tank' => '/public/assets/buildings/building_tank.jpg',
    'market' => '/public/assets/buildings/building_market.jpg',
    'embassy' => '/public/assets/buildings/building_embassy.jpg',
    'quantum_vault' => '/public/assets/buildings/building_quantum_vault.jpg',
    'wall' => '/public/assets/buildings/building_wall.jpg',
    'sawmill' => '/public/assets/buildings/building_sawmill.jpg',
    'stonemason' => '/public/assets/buildings/building_stonemason.jpg',
    'grain_mill' => '/public/assets/buildings/building_grain_mill.jpg',
    'blacksmith' => '/public/assets/buildings/building_blacksmith.jpg',
    'teahouse' => '/public/assets/buildings/building_teahouse.jpg',
    'tournament_square' => '/public/assets/buildings/building_tournament_square.jpg',
];

$citySlotsJsonData = [];
foreach ($citySlots as $s => $sd) {
    $code = $sd['code'];
    if ($code === 'free_plot' && isset(CITY_SLOT_LAYOUT[$s])) {
        $code = CITY_SLOT_LAYOUT[$s];
    }
    $lvl = (int)($sd['level'] ?? 0);
    if ($lvl === 0 && isset($buildings[$code])) {
        $lvl = (int)$buildings[$code];
    }

    $bInfo = BUILDINGS[$code] ?? null;
    $isEmpty = ($sd['code'] === 'free_plot' && $lvl === 0);
    $isBuildingInQ = isset($activeBuildingQueue[$code]);
    $job = $isBuildingInQ ? $activeBuildingQueue[$code] : null;
    $isDemolishing = ($isBuildingInQ && (int)($job['target_level'] ?? -1) === 0);
    $isUpgrading = ($isBuildingInQ && !$isDemolishing);

    $maxLvl = (int)($bInfo['max_level'] ?? 20);
    $isMax = ($lvl >= $maxLvl);

    $upgradeDetails = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
    $cost = $upgradeDetails['cost'];
    $duration = (int)$upgradeDetails['duration'];
    $targetLvl = (int)$upgradeDetails['target_level'];

    $canAfford = $isAdmin ? true : ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);

    $secKey = $buildingSectors[$code] ?? 'logistics';
    $secLabel = match($secKey) {
        'hq' => 'Commandement',
        'military' => 'Militaire & Défense',
        'science' => 'Savoirs & Académie',
        default => 'Logistique & Marché'
    };
    $secColor = match($secKey) {
        'hq' => '#b91c1c',
        'military' => '#ef4444',
        'science' => '#0284c7',
        default => '#16a34a'
    };

    $statData = match($code) {
        'wall' => [
            'label' => 'Bonus Défense Garnison',
            'cur' => '+' . ($lvl * 4) . '% (+' . ($lvl * 25) . ' pts mur)',
            'next' => '+' . ($targetLvl * 4) . '% (+' . ($targetLvl * 25) . ' pts mur)',
            'gain' => '+4% déf. & +25 pts structure'
        ],
        'hq' => [
            'label' => 'Réduction Temps Travaux',
            'cur' => '-' . min(80, (int)($lvl * 5)) . '%',
            'next' => '-' . min(80, (int)($targetLvl * 5)) . '%',
            'gain' => '-5% durée de tous les chantiers'
        ],
        'storage' => [
            'label' => 'Capacité Bois & Pierre',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLvl * 15000),
            'gain' => '+15,000 capacité de stockage'
        ],
        'tank' => [
            'label' => 'Capacité Silo à Riz',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLvl * 15000),
            'gain' => '+15,000 koku de réserve'
        ],
        'quantum_vault' => [
            'label' => 'Ressources Protégées (Cachette)',
            'cur' => number_format(1000 + $lvl * 1000),
            'next' => number_format(1000 + $targetLvl * 1000),
            'gain' => '+1,000 protégés du pillage'
        ],
        'barracks' => [
            'label' => 'Vitesse Entraînement Dojo',
            'cur' => 'Niveau ' . $lvl . ' (Accéléré)',
            'next' => 'Niveau ' . $targetLvl . ' (Plus Rapide)',
            'gain' => 'Cadence d\'enrôlement accrue'
        ],
        'shipyard' => [
            'label' => 'Écuries & Haras Féodaux',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLvl,
            'gain' => 'Mobilisation de cavalerie accélérée'
        ],
        'research_lab' => [
            'label' => 'Vitesse de Recherche Académique',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLvl,
            'gain' => 'Découvertes accélérées'
        ],
        'market' => [
            'label' => 'Capacité Marchande (Caravanes)',
            'cur' => ($lvl * 500) . ' ressource / convoi',
            'next' => ($targetLvl * 500) . ' ressource / convoi',
            'gain' => '+500 transport par convoi'
        ],
        'embassy' => [
            'label' => 'Influence Diplomatique de Clan',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLvl,
            'gain' => '+3 membres max dans l\'alliance'
        ],
        'sawmill' => [
            'label' => 'Bonus Bois de Cèdre',
            'cur' => '+' . ($lvl * 5) . '%',
            'next' => '+' . ($targetLvl * 5) . '%',
            'gain' => '+5% débit de scierie'
        ],
        'stonemason' => [
            'label' => 'Bonus Pierre de Taille',
            'cur' => '+' . ($lvl * 5) . '%',
            'next' => '+' . ($targetLvl * 5) . '%',
            'gain' => '+5% taille de granit'
        ],
        'grain_mill' => [
            'label' => 'Bonus Riz Impérial',
            'cur' => '+' . ($lvl * 5) . '%',
            'next' => '+' . ($targetLvl * 5) . '%',
            'gain' => '+5% mouture de riz'
        ],
        'blacksmith' => [
            'label' => 'Armement & Tamahagane',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLvl,
            'gain' => 'Forge d\'équipements supérieurs'
        ],
        'teahouse' => [
            'label' => 'Sérénité & Cérémonie du Thé',
            'cur' => '+' . ($lvl * 25) . ' sérénité',
            'next' => '+' . ($targetLvl * 25) . ' sérénité',
            'gain' => '+25 sérénité villageoise'
        ],
        'tournament_square' => [
            'label' => 'Vitesse de Marche Terrestre',
            'cur' => '+' . ($lvl * 10) . '% sur 20+ cases',
            'next' => '+' . ($targetLvl * 10) . '% sur 20+ cases',
            'gain' => '+10% cadence de marche'
        ],
        default => [
            'label' => 'Développement Féodal',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLvl,
            'gain' => 'Prestige accru du domaine'
        ]
    };

    $feature = match($code) {
        'barracks' => ($lvl > 0) ? ['url' => '?page=barracks', 'label' => 'Ouvrir le Dojo Militaire', 'icon' => 'fa-solid fa-user-ninja'] : null,
        'shipyard' => ($lvl > 0) ? ['url' => '?page=shipyard', 'label' => 'Mobiliser la Cavalerie & Engins', 'icon' => 'fa-solid fa-horse'] : null,
        'research_lab' => ($lvl > 0) ? ['url' => '?page=research', 'label' => 'Consulter l\'Académie des Savoirs', 'icon' => 'fa-solid fa-scroll'] : null,
        'market' => ($lvl > 0) ? ['url' => '?page=market', 'label' => 'Ouvrir le Marché Féodal', 'icon' => 'fa-solid fa-scale-balanced'] : null,
        'embassy' => ($lvl > 0) ? ['url' => '?page=alliance', 'label' => 'Affaires de Clan & Ambassade', 'icon' => 'fa-solid fa-torii-gate'] : null,
        'grain_mill' => ($lvl >= 10) ? ['url' => '/?page=building&code=grain_mill#craftSection', 'label' => 'Raffiner Saké & Farine de Riz', 'icon' => 'fa-solid fa-wine-bottle'] : null,
        'sawmill' => ($lvl >= 10) ? ['url' => '/?page=building&code=sawmill#craftSection', 'label' => 'Façonner les Poutres de Cèdre', 'icon' => 'fa-solid fa-hammer'] : null,
        'stonemason' => ($lvl > 0) ? ['url' => '/?page=building&code=stonemason#craftSection', 'label' => 'Raffiner Minerais & Tuiles Kawara', 'icon' => 'fa-solid fa-gem'] : null,
        'blacksmith' => ($lvl >= 15) ? ['url' => '/?page=building&code=blacksmith#craftSection', 'label' => 'Fourneaux de Prestige & Armes', 'icon' => 'fa-solid fa-fire-burner'] : null,
        default => null
    };

    $citySlotsJsonData[$s] = [
        'slot' => $s,
        'code' => $code,
        'name' => $bInfo['name'] ?? 'Bâtiment Castral',
        'desc' => $bInfo['description'] ?? '',
        'icon' => $bInfo['icon'] ?? '<i class="fa-solid fa-landmark"></i>',
        'sector' => $secKey,
        'sector_label' => $secLabel,
        'sector_color' => $secColor,
        'level' => $lvl,
        'max_level' => $maxLvl,
        'is_max' => $isMax,
        'is_empty' => $isEmpty,
        'is_upgrading' => $isUpgrading,
        'is_demolishing' => $isDemolishing,
        'queue_id' => (int)($job['id'] ?? 0),
        'finishes_at' => (int)($job['finishes_at'] ?? 0),
        'started_at' => (int)($job['started_at'] ?? 0),
        'target_level' => $targetLvl,
        'cost' => $cost,
        'duration' => $duration,
        'can_afford' => $canAfford,
        'can_queue' => $canQueueNewBuilding,
        'img_url' => $buildingHeroImages[$code] ?? '/public/assets/shogun_castle_city_bg.jpg',
        'stat_data' => $statData,
        'feature' => $feature
    ];
}
?>

<style>
.fields-viewport.rts-city-surface {
    background-image: url('/public/assets/shogun_castle_city_bg.jpg?v=<?= $bgVersion ?>') !important;
}

/* Hotspots interactifs de la Cité Castrale intégrée (sans cadre jaune au survol) */
.rts-city-surface .rts-hotspot {
    border-radius: 8px;
    background: transparent;
    border: none;
    transition: transform 0.15s ease;
}

.rts-city-surface .rts-hotspot:hover {
    background: transparent;
    border: none;
    box-shadow: none;
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
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
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
<?php
$queueTitle = 'Chantiers Urbains';
ob_start();
?>
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title d-flex align-items-center gap-2">
                    <span><i class="fa-solid fa-chess-rook text-danger me-1"></i> Cité Castrale & Palais du Daimyō - <?= htmlspecialchars($planet['name']) ?></span>
                    <?php if (!empty($planet['is_capital'])): ?>
                        <span class="badge bg-warning text-dark fw-bold" style="font-size:0.72rem;"><i class="fa-solid fa-crown me-1"></i>Capitale</span>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Renommer ce fief" onclick="openCityRenamePrompt(<?= (int)$planet['id'] ?>, <?= htmlspecialchars(json_encode($planet['name']), ENT_QUOTES, 'UTF-8') ?>)" style="font-size:0.75rem;">
                        <i class="fa-solid fa-pen me-1"></i>Renommer
                    </button>
                </h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Forteresse Principale : <strong style="color:var(--border-highlight, #c2252b);">Tenshu Niveau <?= $hqLevel ?></strong>
                    <span style="opacity:0.85;">&bull; <i class="fa-solid fa-users text-primary me-1"></i><strong><?= number_format($pop) ?></strong>/<?= number_format($popMax) ?> Habitants (<span class="text-success">+<?= $popBonus ?>% vitesse chantiers</span>)</span>
                    <?php if (!empty($planet['workforce']) && !empty($planet['workforce']['is_understaffed'])): ?>
                        <span class="badge bg-warning-lt text-warning ms-1" title="Main-d'œuvre insuffisante pour tous les postes"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sous-effectif : -<?= $planet['workforce']['understaffed_malus_pct'] ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button class="btn btn-secondary" onclick="toggleCityViewMode()" id="btn-toggle-view" style="font-size:0.8rem; padding:0.35rem 0.75rem;"><i class="fa-solid fa-clipboard-list me-1"></i>Fiches Détaillées</button>
                <a href="?page=resources" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;"><i class="fa-solid fa-wheat-awn me-1"></i>Vers les Terroirs Ruraux &rarr;</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($planet['famine_active']) && !empty($planet['famine_enabled'])): ?>
                <div style="background:#fef2f2; border:2px solid #ef4444; border-radius:8px; padding:0.6rem 1rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                    <div style="display:flex; align-items:center; gap:0.6rem; color:#991b1b; font-size:0.85rem; font-weight:700;">
                        <span style="font-size:1.4rem;"><i class="fa-solid fa-triangle-exclamation text-danger"></i></span>
                        <div>
                            <span>Disette Féodale : Vos greniers sont à sec de Farine de Riz <i class="fa-solid fa-bowl-rice text-warning"></i> !</span>
                            <div style="font-size:0.75rem; font-weight:400; color:#b91c1c;">
                                Vos régiments d'élite meurent de faim ou désertent (-<?= (float)GameConfig::get('famine_rate', 3.0) ?>%/heure). Approvisionnez d'urgence votre Meunerie !
                            </div>
                        </div>
                    </div>
                    <a href="/?page=building&code=grain_mill#craftSection" class="btn btn-sm btn-danger fw-bold" style="font-size:0.75rem; padding:0.3rem 0.8rem;">
                        <i class="fa-solid fa-bowl-rice me-1"></i>Moudre de la Farine d'Urgence &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($activeFeast): ?>
                <?php
                    $feastLabels = [
                        'matsuri' => ['Matsuri Populaire des Saisons', '<i class="fa-solid fa-fan text-warning"></i>', '#fef9c3', '#ca8a04'],
                        'warriors' => ['Banquet des Guerriers (Kanpai)', '<i class="fa-solid fa-khanda text-danger"></i>', '#fee2e2', '#dc2626'],
                        'imperial' => ['Grand Banquet Impérial', '<i class="fa-solid fa-crown text-purple"></i>', '#ede9fe', '#7c3aed']
                    ];
                    $fData = $feastLabels[$activeFeast['feast_type']] ?? ['Célébration', '<i class="fa-solid fa-champagne-glasses text-warning"></i>', '#fef9c3', '#ca8a04'];
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
                <button class="sector-btn active" id="btn-city-all" onclick="filterCitySector('all')"><i class="fa-solid fa-globe me-1"></i>Vue Globale Cité</button>
                <button class="sector-btn filter-hq" id="btn-city-hq" onclick="filterCitySector('hq')"><i class="fa-solid fa-chess-rook me-1"></i>Tenshu Donjon</button>
                <button class="sector-btn filter-military" id="btn-city-military" onclick="filterCitySector('military')"><i class="fa-solid fa-khanda me-1"></i>Dojo, Cavalerie & Remparts</button>
                <button class="sector-btn filter-science" id="btn-city-science" onclick="filterCitySector('science')"><i class="fa-solid fa-scroll me-1"></i>Savoirs & Forge</button>
                <button class="sector-btn filter-logistics" id="btn-city-logistics" onclick="filterCitySector('logistics')"><i class="fa-solid fa-boxes-stacked me-1"></i>Greniers & Réserves</button>
                <button class="sector-btn filter-gateway" id="btn-city-gateway" onclick="window.location.href='?page=resources'"><i class="fa-solid fa-wheat-awn me-1"></i>Porte du Terroir</button>
            </div>

            <!-- Viewport RTS de la Cité Castrale (shogun_castle_city_bg.jpg) -->
            <div class="fields-viewport rts-surface rts-city-surface" id="rts-city-viewport">
                <!-- Porte fortifiée vers les terroirs ruraux (en bas à droite avec Mon) -->
                <div class="rts-hotspot sector-gateway hotspot-city-slot-gateway"
                     data-sector="gateway"
                     title="Grande Porte Castrale (Retour aux Terroirs Ruraux)"
                     onclick="window.location.href='?page=resources'">
                    <div class="rts-level-bubble rts-gateway-bubble" title="Vers le Terroir"><i class="fa-solid fa-wheat-awn text-warning"></i></div>
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
                         onclick="openBuildingSlotModal(<?= $slot ?>)"
                         style="cursor: pointer;">

                        <!-- Badge féodal de niveau (ou marqueur de fondation) -->
                        <div class="rts-level-bubble <?= ($code === 'hq') ? 'rts-tenshu-bubble' : '' ?> <?= $isLevelZero ? 'level-zero' : '' ?> <?= $isDemolishing ? 'demolishing' : (($isUnderConstruction || $isUpgrading) ? 'upgrading' : '') ?>"
                             title="<?= htmlspecialchars($bInfo['name']) ?> (<?= $isLevelZero ? 'Non bâti - Cliquer pour ériger' : 'Niveau ' . $lvl ?>)">
                            <?php if ($isLevelZero): ?>
                                +
                            <?php elseif ($isUnderConstruction || $isUpgrading): ?>
                                <span class="bubble-pulse"><i class="fa-solid fa-hourglass-half"></i></span>
                            <?php elseif ($isDemolishing): ?>
                                <span class="bubble-pulse"><i class="fa-solid fa-trash-can"></i></span>
                            <?php else: ?>
                                <?= $lvl ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p style="margin-top:0.75rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                <i class="fa-solid fa-lightbulb text-warning me-1"></i> <strong>Cité Castrale & Village Féodal :</strong> Cliquez sur une bâtisse ou un emplacement pour l'élever au niveau supérieur, ou accédez directement au dojo, aux écuries et à l'académie de recherche.
            </p>

            <!-- Vue Grille des Cartes Détaillées (Repliable) -->
            <div class="city-grid" id="city-cards-grid" style="display:none; margin-top:1.5rem;">
                <?php foreach (BUILDINGS as $code => $bInfo): ?>
                    <?php
                        $lvl = $buildings[$code] ?? 0;
                        $maxLevel = (int)($bInfo['max_level'] ?? 20);
                        $isMaxLevel = ($lvl >= $maxLevel);
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
                        <span class="building-lvl-badge <?= $isMaxLevel ? 'bg-success text-white' : '' ?>">
                            <?= ($lvl > 0) ? ($isMaxLevel ? "Niveau $lvl (Max)" : "Niveau $lvl") : "Non bâti" ?>
                        </span>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem; flex:1;">
                            <?= htmlspecialchars($bInfo['description']) ?>
                        </p>

                        <div style="width:100%; display:flex; flex-direction:column; gap:0.5rem;">
                            <?php if ($code === 'shipyard' && $lvl > 0): ?>
                                <a href="?page=shipyard" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-horse me-1"></i>Mobiliser Cavalerie & Siège</a>
                            <?php elseif ($code === 'barracks' && $lvl > 0): ?>
                                <a href="?page=barracks" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-user-ninja me-1"></i>Entraîner Soldats (Dojo)</a>
                            <?php elseif ($code === 'research_lab' && $lvl > 0): ?>
                                <a href="?page=research" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-scroll me-1"></i>Académie des Savoirs</a>
                            <?php elseif ($code === 'grain_mill' && $lvl >= 10): ?>
                                <a href="/?page=building&code=grain_mill#craftSection" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-wine-bottle me-1"></i>Raffiner Saké &amp; Farine</a>
                            <?php elseif ($code === 'sawmill' && $lvl >= 10): ?>
                                <a href="/?page=building&code=sawmill#craftSection" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-hammer me-1"></i>Façonner Poutres</a>
                            <?php elseif ($code === 'stonemason' && $lvl > 0): ?>
                                <a href="/?page=building&code=stonemason#craftSection" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-gem me-1"></i>Raffiner Minerais</a>
                            <?php elseif ($code === 'blacksmith' && $lvl >= 15): ?>
                                <a href="/?page=building&code=blacksmith#craftSection" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.6rem;"><i class="fa-solid fa-fire-burner me-1"></i>Fourneaux &amp; Prestige</a>
                            <?php endif; ?>

                            <?php if ($isMaxLevel): ?>
                                <button type="button" onclick="openBuildingSlotModalByCode('<?= $code ?>')" class="btn btn-outline-success" style="font-size:0.75rem; padding:0.4rem 0.6rem; text-align:center;">
                                    <i class="fa-solid fa-crown text-warning me-1"></i>Niveau Maximum (<?= $lvl ?>)
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="openBuildingSlotModalByCode('<?= $code ?>')" class="btn btn-primary" style="font-size:0.75rem; padding:0.4rem 0.6rem; text-align:center;">
                                    <?= ($lvl === 0) ? '<i class="fa-solid fa-hammer me-1"></i>Construire' : '<i class="fa-solid fa-bolt text-warning me-1"></i>Gérer &amp; Améliorer' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php
$centerCardContent = ob_get_clean();

ob_start();
?>


<!-- ========================================================
     MODALE D'ÉLITE DU BÂTIMENT CASTRAL & EMPLACEMENT (STYLE TRAVIAN CRAFT)
     Bannière HD, dégradé sombre, comparatifs, navigation entre slots
     ======================================================== -->
<div class="modal fade" id="buildingSlotModal" tabindex="-1" aria-labelledby="cityModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: 12px;">
            <!-- Illustration d'en-tête (215px) avec overlay dégradé -->
            <div style="position: relative; width: 100%; height: 215px; overflow: hidden; background: #0f172a;">
                <img id="cityModalBannerImg" src="/public/assets/buildings/building_tenshu.jpg" alt="Édifice de la Cité" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>

                <!-- Navigation inter-slots (précédent / suivant) en haut à gauche -->
                <div style="position: absolute; top: 12px; left: 16px; z-index: 10;" class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-white border-0 py-1 px-2.5 shadow-sm" onclick="navigateCitySlotModal(-1)" title="Emplacement Précédent">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="badge bg-dark bg-opacity-75 text-white font-monospace px-2.5 py-1.5 shadow-sm border border-secondary" id="cityModalSlotNavBadge" style="font-size: 0.75rem;">
                        Slot #19 / 34
                    </span>
                    <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-white border-0 py-1 px-2.5 shadow-sm" onclick="navigateCitySlotModal(1)" title="Emplacement Suivant">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Bouton fermer blanc -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>

                <!-- Infos d'en-tête en bas de la bannière -->
                <div style="position: absolute; bottom: 12px; left: 16px; right: 16px;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                    <div style="max-width: 72%;">
                        <span id="cityModalSectorBadge" class="badge bg-danger text-white fw-bold px-2 py-1 mb-1 shadow-sm" style="font-size:0.75rem;">
                            🏯 COMMANDEMENT
                        </span>
                        <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="cityModalTitle" style="font-size: 1.25rem;">
                            Donjon Tenshu
                        </h3>
                        <div class="text-white-50 font-monospace small mt-0.5" id="cityModalSubtitle">Cité Castrale &bull; Emplacement #19</div>
                    </div>
                    <div>
                        <span id="cityModalLevelBadge" class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1 shadow-sm">
                            Niveau 1 / 20
                        </span>
                    </div>
                </div>
            </div>

            <!-- Corps de la modale -->
            <div class="modal-body p-4 bg-white">
                <!-- PANNEAU A : BÂTIMENT EXISTANT / ATTRIBUÉ -->
                <div id="cityModalExistingView">
                    <!-- Lore & Dialogue immersif -->
                    <div class="p-3 rounded border mb-3" style="background: #fdfbf7; border-left: 4px solid #b91c1c !important;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span id="cityModalIconSpan" style="font-size: 1.15rem;"><i class="fa-solid fa-chess-rook text-danger"></i></span>
                            <strong class="text-danger small" id="cityModalDescHeading">Édifice du Shogunat :</strong>
                        </div>
                        <p class="small text-secondary m-0 fst-italic" id="cityModalDesc" style="line-height: 1.5;">
                            ...
                        </p>
                    </div>

                    <!-- Encart comparatif Rendement / Avantage féodal -->
                    <div class="p-3 mb-3 rounded border" style="background: #f8fafc;" id="cityModalStatsBox">
                        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                            <span class="text-secondary small"><i class="fa-solid fa-chart-line text-success me-1"></i><span id="cityModalStatLabel">Avantage féodal :</span></span>
                            <strong class="text-dark small font-monospace" id="cityModalStatCurrent">-</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <span class="text-secondary small"><i class="fa-solid fa-arrow-trend-up text-primary me-1"></i>Prochain niveau :</span>
                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-primary small font-monospace" id="cityModalStatNext">-</strong>
                                <span class="badge bg-success-lt text-success small font-monospace" id="cityModalStatGain">+</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statut Chantier en cours (si actif) -->
                    <div id="cityModalActiveWorkBox" class="d-none p-3 mb-3 rounded border border-warning-subtle" style="background: #fffbeb;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark"><i class="fa-solid fa-hammer text-warning me-1"></i>Chantier d'élévation en cours</span>
                            <span class="font-monospace fw-bold text-danger fs-3" id="cityModalWorkCountdown">--:--:--</span>
                        </div>
                        <div class="progress mb-2" style="height: 6px; background: #fef3c7; border-radius: 3px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="cityModalWorkProgressBar" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <div class="small text-muted" id="cityModalWorkFinishLabel"></div>
                    </div>

                    <!-- Coûts requis & Durée (si pas de chantier) -->
                    <div id="cityModalUpgradeBox">
                        <div class="mb-3">
                            <label class="form-label mb-2 fw-bold text-muted small text-uppercase" style="font-size:0.75rem;">Matériaux Requis pour l'Élévation :</label>
                            <div class="d-flex flex-wrap gap-2" id="cityModalCostTags"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center text-muted small mb-0 p-2 rounded bg-light border">
                            <span><i class="fa-regular fa-clock me-1 text-primary"></i>Durée estimée des travaux :</span>
                            <strong class="font-monospace text-dark" id="cityModalDuration">00:01:30</strong>
                        </div>
                    </div>

                    <!-- Raccourci vers la fonction métier du bâtiment (Dojo, Écuries, Académie...) -->
                    <div id="cityModalFeatureBox" class="mt-3 d-none">
                        <a href="#" id="cityModalFeatureBtn" class="btn btn-outline-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2">
                            <i id="cityModalFeatureIcon" class="fa-solid fa-arrow-up-right-from-square"></i>
                            <span id="cityModalFeatureLabel">Accéder</span>
                        </a>
                    </div>
                </div>

                <!-- PANNEAU B : TERRAIN VIERGE / FONDATION D'UN NOUVEL ÉDIFICE -->
                <div id="cityModalFreePlotView" class="d-none">
                    <p class="text-secondary small mb-3">
                        Cet emplacement de votre forteresse est libre. Choisissez l'édifice féodal à ériger pour étendre la puissance de votre clan.
                    </p>

                    <!-- Filtres par secteur -->
                    <div class="d-flex gap-1.5 mb-3 flex-wrap">
                        <button type="button" class="btn btn-sm btn-secondary city-build-filter active" onclick="filterCityBuildModal('all', this)">
                            <i class="fa-solid fa-globe me-1"></i>Toutes
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger city-build-filter" onclick="filterCityBuildModal('military', this)">
                            <i class="fa-solid fa-khanda me-1"></i>Militaire & Défense
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info city-build-filter" onclick="filterCityBuildModal('science', this)">
                            <i class="fa-solid fa-scroll me-1"></i>Savoirs & Diplomatie
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success city-build-filter" onclick="filterCityBuildModal('logistics', this)">
                            <i class="fa-solid fa-boxes-stacked me-1"></i>Logistique & Marché
                        </button>
                    </div>

                    <!-- Liste défilante des structures constructibles -->
                    <div class="d-flex flex-column gap-2" style="max-height: 340px; overflow-y: auto;" id="cityModalAvailableBuildingsList">
                        <!-- Rempli dynamiquement -->
                    </div>
                </div>
            </div>

            <!-- Pied de modale avec confirmation -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Fermer
                </button>
                <div id="cityModalActionContainer" class="d-flex gap-2">
                    <!-- Boutons d'action injectés dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.CITY_SLOTS_DATA = <?= json_encode($citySlotsJsonData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.AVAILABLE_BUILDINGS = <?= json_encode($availableBuildingsToConstruct, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
window.CITY_CONFIG = {
    metal: <?= (int)$planet['metal'] ?>,
    crystal: <?= (int)$planet['crystal'] ?>,
    deuterium: <?= (int)$planet['deuterium'] ?>,
    isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
    canQueue: <?= $canQueueNewBuilding ? 'true' : 'false' ?>
};

let currentCitySlotIndex = 19;
let cityCountdownInterval = null;

function formatCityDuration(seconds) {
    if (seconds <= 0) return '00:00:00';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    const pad = n => n.toString().padStart(2, '0');
    if (days > 0) {
        return `${days}j ${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
    }
    return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
}

function openBuildingSlotModal(slotNum) {
    slotNum = parseInt(slotNum, 10);
    if (!slotNum || slotNum < 19 || slotNum > 34) slotNum = 19;
    currentCitySlotIndex = slotNum;
    renderCitySlotModal();

    const modalEl = document.getElementById('buildingSlotModal');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openBuildingSlotModalByCode(code) {
    for (const [s, data] of Object.entries(window.CITY_SLOTS_DATA)) {
        if (data.code === code) {
            openBuildingSlotModal(parseInt(s, 10));
            return;
        }
    }
    openBuildingSlotModal(19);
}

function navigateCitySlotModal(delta) {
    let nextSlot = currentCitySlotIndex + delta;
    if (nextSlot < 19) nextSlot = 34;
    if (nextSlot > 34) nextSlot = 19;
    currentCitySlotIndex = nextSlot;
    renderCitySlotModal();
}

function renderCitySlotModal() {
    const data = window.CITY_SLOTS_DATA[currentCitySlotIndex];
    if (!data) return;

    if (cityCountdownInterval) {
        clearInterval(cityCountdownInterval);
        cityCountdownInterval = null;
    }

    // Badge navigation
    const navBadge = document.getElementById('cityModalSlotNavBadge');
    if (navBadge) navBadge.textContent = `Slot #${data.slot} / 34`;

    // Bannière HD
    const bannerImg = document.getElementById('cityModalBannerImg');
    if (bannerImg) {
        bannerImg.src = data.img_url || '/public/assets/buildings/building_tenshu.jpg';
    }

    // Badge secteur
    const sectorBadge = document.getElementById('cityModalSectorBadge');
    if (sectorBadge) {
        sectorBadge.textContent = `${data.sector_label.toUpperCase()}`;
        sectorBadge.style.backgroundColor = data.sector_color || '#b91c1c';
    }

    // Titres
    const titleEl = document.getElementById('cityModalTitle');
    if (titleEl) {
        titleEl.textContent = data.is_empty ? `Emplacement #${data.slot} (Terrain Vierge)` : data.name;
    }
    const subTitleEl = document.getElementById('cityModalSubtitle');
    if (subTitleEl) {
        subTitleEl.textContent = `Cité Castrale • Emplacement #${data.slot}`;
    }

    // Badge niveau
    const lvlBadge = document.getElementById('cityModalLevelBadge');
    if (lvlBadge) {
        if (data.is_empty) {
            lvlBadge.className = 'badge bg-secondary text-white font-monospace fs-6 px-3 py-1 shadow-sm';
            lvlBadge.textContent = 'Terrain Libre';
        } else if (data.is_max) {
            lvlBadge.className = 'badge bg-success text-white font-monospace fs-6 px-3 py-1 shadow-sm';
            lvlBadge.textContent = `Niveau ${data.level} (Apogée)`;
        } else {
            lvlBadge.className = 'badge bg-warning text-dark font-monospace fs-6 px-3 py-1 shadow-sm';
            lvlBadge.textContent = `Niveau ${data.level} / ${data.max_level}`;
        }
    }

    const existingView = document.getElementById('cityModalExistingView');
    const freePlotView = document.getElementById('cityModalFreePlotView');
    const actionContainer = document.getElementById('cityModalActionContainer');

    if (data.is_empty) {
        // Affichage panneau terrain vierge
        if (existingView) existingView.classList.add('d-none');
        if (freePlotView) freePlotView.classList.remove('d-none');

        renderAvailableBuildingsList('all');

        if (actionContainer) {
            actionContainer.innerHTML = `
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Annuler
                </button>
            `;
        }
    } else {
        // Affichage panneau bâtiment existant
        if (existingView) existingView.classList.remove('d-none');
        if (freePlotView) freePlotView.classList.add('d-none');

        // Lore / Description
        const descEl = document.getElementById('cityModalDesc');
        if (descEl) descEl.textContent = data.desc || '';

        const iconSpan = document.getElementById('cityModalIconSpan');
        if (iconSpan) iconSpan.innerHTML = data.icon || '<i class="fa-solid fa-landmark"></i>';

        // Statistiques comparatives
        const statsBox = document.getElementById('cityModalStatsBox');
        if (statsBox && data.stat_data) {
            const statLabel = document.getElementById('cityModalStatLabel');
            const statCur = document.getElementById('cityModalStatCurrent');
            const statNext = document.getElementById('cityModalStatNext');
            const statGain = document.getElementById('cityModalStatGain');
            if (statLabel) statLabel.textContent = data.stat_data.label + ' :';
            if (statCur) statCur.textContent = data.stat_data.cur;
            if (statNext) statNext.textContent = data.is_max ? 'Apogée' : data.stat_data.next;
            if (statGain) {
                statGain.textContent = data.is_max ? 'Max' : (data.stat_data.gain || '+');
                statGain.style.display = data.is_max ? 'none' : 'inline-block';
            }
        }

        // Chantier actif vs boîte d'élévation
        const workBox = document.getElementById('cityModalActiveWorkBox');
        const upgradeBox = document.getElementById('cityModalUpgradeBox');

        if (data.is_upgrading) {
            if (workBox) workBox.classList.remove('d-none');
            if (upgradeBox) upgradeBox.classList.add('d-none');

            const now = Math.floor(Date.now() / 1000);
            const finishesAt = data.finishes_at || now;
            const startedAt = data.started_at || now;

            const updateWorkTimer = () => {
                const currentNow = Math.floor(Date.now() / 1000);
                const remaining = Math.max(0, finishesAt - currentNow);
                const total = Math.max(1, finishesAt - startedAt);
                const elapsed = Math.max(0, currentNow - startedAt);
                const progress = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));

                const cdEl = document.getElementById('cityModalWorkCountdown');
                if (cdEl) cdEl.textContent = formatCityDuration(remaining);

                const pBar = document.getElementById('cityModalWorkProgressBar');
                if (pBar) pBar.style.width = progress + '%';

                const finishLabel = document.getElementById('cityModalWorkFinishLabel');
                if (finishLabel) {
                    const dateObj = new Date(finishesAt * 1000);
                    finishLabel.textContent = `Chantier vers Niveau ${data.target_level}. Fin estimée : ${dateObj.toLocaleTimeString()}`;
                }
            };

            updateWorkTimer();
            cityCountdownInterval = setInterval(updateWorkTimer, 1000);

            if (actionContainer) {
                const isAdm = window.CITY_CONFIG.isAdmin;
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-warning fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5" onclick="instantFinishCityBuilding(${data.queue_id})">
                        <i class="fa-solid fa-bolt text-danger"></i>
                        <span>${isAdm ? '⚡ Finir Gratuit' : 'Terminer (2 Kobans)'}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger px-3" onclick="cancelCityBuilding(${data.queue_id}, this)">
                        <i class="fa-solid fa-ban me-1"></i> Annuler (-20%)
                    </button>
                `;
            }
        } else {
            if (workBox) workBox.classList.add('d-none');
            if (upgradeBox) upgradeBox.classList.remove('d-none');

            // Coûts
            const costContainer = document.getElementById('cityModalCostTags');
            if (costContainer) {
                costContainer.innerHTML = '';
                if (data.is_max) {
                    costContainer.innerHTML = '<span class="badge bg-success-lt fs-4 p-2"><i class="fa-solid fa-crown me-1 text-warning"></i>Apogée de l\'édifice atteinte</span>';
                } else {
                    const addTag = (icon, name, costVal, playerStock) => {
                        if (costVal <= 0) return;
                        const ok = playerStock >= costVal;
                        const tag = document.createElement('span');
                        tag.className = 'cost-chip ' + (ok ? 'affordable' : 'missing');
                        tag.innerHTML = `<i class="${icon}"></i> ${name} : <strong>${costVal.toLocaleString()}</strong>`;
                        costContainer.appendChild(tag);
                    };

                    addTag('fa-solid fa-tree text-success', 'Bois', data.cost.metal || 0, window.CITY_CONFIG.metal);
                    addTag('fa-solid fa-mountain text-secondary', 'Pierre', data.cost.crystal || 0, window.CITY_CONFIG.crystal);
                    addTag('fa-solid fa-wheat-awn text-warning', 'Riz', data.cost.deuterium || 0, window.CITY_CONFIG.deuterium);
                }
            }

            // Durée
            const durEl = document.getElementById('cityModalDuration');
            if (durEl) {
                durEl.textContent = data.is_max ? 'Apogée' : formatCityDuration(data.duration || 60);
            }

            // Boutons d'action dans le footer
            if (actionContainer) {
                if (data.is_max) {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-secondary disabled px-4" disabled>
                            <i class="fa-solid fa-check me-1"></i> Apogée atteinte
                        </button>
                    `;
                } else if (data.can_afford || window.CITY_CONFIG.isAdmin) {
                    const isAdm = window.CITY_CONFIG.isAdmin;
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm pulse-btn" onclick="upgradeCitySlot('${data.code}', ${data.slot}, this)">
                            <i class="fa-solid fa-arrow-up me-1"></i> ${isAdm ? '⚡ [Admin] Élévation Immédiate (Niv. ' + data.target_level + ')' : (data.level === 0 ? 'Construire l\'édifice (Niveau 1)' : 'Élever au Niveau ' + data.target_level)}
                        </button>
                    `;
                } else {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-outline-secondary disabled px-3" disabled style="opacity:0.7;">
                            <i class="fa-solid fa-lock me-1"></i> Ressources insuffisantes
                        </button>
                    `;
                }
            }
        }

        // Raccourci métier spécial (Dojo, Écuries, Académie...)
        const featureBox = document.getElementById('cityModalFeatureBox');
        if (featureBox) {
            if (data.feature && data.feature.url) {
                featureBox.classList.remove('d-none');
                const fBtn = document.getElementById('cityModalFeatureBtn');
                const fIcon = document.getElementById('cityModalFeatureIcon');
                const fLabel = document.getElementById('cityModalFeatureLabel');
                if (fBtn) fBtn.href = data.feature.url;
                if (fIcon) fIcon.className = data.feature.icon || 'fa-solid fa-arrow-up-right-from-square';
                if (fLabel) fLabel.textContent = data.feature.label || 'Accéder';
            } else {
                featureBox.classList.add('d-none');
            }
        }
    }
}

function filterCityBuildModal(sector, btnEl) {
    document.querySelectorAll('.city-build-filter').forEach(b => {
        b.classList.remove('active', 'btn-secondary', 'btn-danger', 'btn-info', 'btn-success');
        b.classList.add('btn-outline-secondary');
    });
    if (btnEl) {
        btnEl.classList.remove('btn-outline-secondary', 'btn-outline-danger', 'btn-outline-info', 'btn-outline-success');
        btnEl.classList.add('active', 'btn-secondary');
    }
    renderAvailableBuildingsList(sector);
}

function renderAvailableBuildingsList(sector) {
    const container = document.getElementById('cityModalAvailableBuildingsList');
    if (!container) return;

    let html = '';
    const buildings = window.AVAILABLE_BUILDINGS || {};
    const keys = Object.keys(buildings);

    if (keys.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fa-solid fa-crown text-warning fs-1 d-block mb-2"></i>
                <strong>Toutes les structures sont fondées !</strong>
            </div>
        `;
        return;
    }

    keys.forEach(bCode => {
        const item = buildings[bCode];
        if (sector !== 'all' && item.sector !== sector) return;

        const info = item.info;
        const det = item.details;
        const c = det.cost;
        const durFormatted = formatCityDuration(det.duration);
        const canAfford = item.can_afford || window.CITY_CONFIG.isAdmin;

        html += `
            <div class="p-3 rounded border d-flex justify-content-between align-items-center gap-3 flex-wrap" style="background:#f8fafc;">
                <div class="d-flex align-items-center gap-2.5 flex-grow-1 min-w-0">
                    <span style="font-size:1.6rem; min-width:32px; text-align:center;">${info.icon || '🏯'}</span>
                    <div class="min-w-0">
                        <strong class="text-dark d-block text-truncate" style="font-size:0.92rem;">${info.name}</strong>
                        <p class="small text-muted m-0 text-truncate" style="font-size:0.75rem;">${info.description}</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex gap-2 small font-monospace">
                        <span style="color:${window.CITY_CONFIG.metal >= c.metal ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-tree me-1"></i>${Number(c.metal).toLocaleString()}</span>
                        <span style="color:${window.CITY_CONFIG.crystal >= c.crystal ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-mountain me-1"></i>${Number(c.crystal).toLocaleString()}</span>
                        <span style="color:${window.CITY_CONFIG.deuterium >= c.deuterium ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-wheat-awn me-1"></i>${Number(c.deuterium).toLocaleString()}</span>
                        <span class="text-muted"><i class="fa-regular fa-clock me-1"></i>${durFormatted}</span>
                    </div>

                    <button type="button" class="btn btn-sm ${canAfford ? 'btn-primary' : 'btn-outline-secondary disabled'}" 
                            onclick="confirmBuildOnSlot('${bCode}')" ${canAfford ? '' : 'disabled'}>
                        <i class="fa-solid fa-hammer me-1"></i>Bâtir
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html || '<div class="text-center py-3 text-muted">Aucun édifice dans cette catégorie.</div>';
}

async function upgradeCitySlot(code, slot, btn) {
    if (!code || !slot) return;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Chantier en cours...';
    }

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'building');
    formData.append('target_id', code);
    formData.append('slot', slot);

    try {
        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || 'Travaux d\'élévation ordonnés avec succès !', 'success', 'Ordre de Chantier Transmis');
            }
            window.location.reload();
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i>Réessayer';
            }
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible d\'élever cet édifice.', 'warning', 'Décret Impérial');
            } else {
                alert(data.error);
            }
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        console.error('Erreur chantier:', e);
    }
}

async function confirmBuildOnSlot(code) {
    const slot = currentCitySlotIndex;
    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'building');
    formData.append('target_id', code);
    formData.append('slot', slot);

    try {
        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || 'Fondation du bâtiment initiée avec succès !', 'success', 'Ordre de Chantier Transmis');
            }
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer ce chantier.', 'warning', 'Ressources Insuffisantes');
            } else {
                alert(data.error);
            }
        }
    } catch (e) {
        console.error('Erreur fondation:', e);
    }
}

async function instantFinishCityBuilding(queueId) {
    if (!queueId) return;
    try {
        const formData = new FormData();
        formData.append('action', 'instant_finish');
        formData.append('queue_id', queueId);

        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || 'Chantier achevé instantanément avec honneur !', 'success', 'Édifice Complété');
            }
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de finir instantanément ce chantier.', 'warning', 'Kobans Insuffisants');
            }
        }
    } catch (e) {
        console.error('Erreur instant finish:', e);
    }
}

async function cancelCityBuilding(queueId, btn) {
    if (!queueId) return;
    if (typeof showModalConfirm === 'function') {
        const ok = await showModalConfirm('Voulez-vous vraiment suspendre ces travaux ? 80% des matériaux investis vous seront restitués.', 'Interruption de Chantier');
        if (!ok) return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('queue_id', queueId);

        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible d\'annuler ce chantier.', 'error');
            }
        }
    } catch (e) {
        console.error('Erreur annulation:', e);
    }
}

// Détection automatique des paramètres d'URL (?slot=X ou ?code=Y) pour ouvrir immédiatement la modale
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const slotParam = urlParams.get('slot');
    const codeParam = urlParams.get('code');

    if (slotParam) {
        openBuildingSlotModal(parseInt(slotParam, 10));
    } else if (codeParam) {
        openBuildingSlotModalByCode(codeParam);
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
        toggleBtn.innerHTML = '<i class="fa-solid fa-map me-1"></i>Masquer les Fiches';
        cardsGrid.scrollIntoView({ behavior: 'smooth' });
    } else {
        cardsGrid.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fa-solid fa-clipboard-list me-1"></i>Fiches Détaillées';
    }
}
</script>
<?php
$extraHtml = ob_get_clean();
require __DIR__ . '/partials/game_screen_template.php';
?>

