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

/* Styles des onglets et panneaux d'élite de la modale de cité */
#cityModalTabsNav .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    border-radius: 0;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}
#cityModalTabsNav .nav-link:hover {
    color: #1e293b;
    background-color: #f1f5f9;
}
#cityModalTabsNav .nav-link.active {
    color: #0f172a !important;
    background-color: #ffffff !important;
    border-bottom: 3px solid #b91c1c !important;
}

.city-craft-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.city-craft-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.city-feast-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.city-feast-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
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

            <!-- Onglets d'action & gestion du bâtiment (Bootstrap Tabs) -->
            <ul class="nav nav-tabs nav-fill px-3 pt-2 bg-light border-bottom" id="cityModalTabsNav" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark py-2" id="city-tab-evolution-btn" data-bs-toggle="tab" data-bs-target="#city-pane-evolution" type="button" role="tab" aria-controls="city-pane-evolution" aria-selected="true">
                        <i class="fa-solid fa-arrow-up-right-dots text-danger me-1"></i>Évolution
                    </button>
                </li>
                <li class="nav-item d-none" id="city-tab-craft-li" role="presentation">
                    <button class="nav-link fw-bold text-dark py-2" id="city-tab-craft-btn" data-bs-toggle="tab" data-bs-target="#city-pane-craft" type="button" role="tab" aria-controls="city-pane-craft" aria-selected="false">
                        <i class="fa-solid fa-boxes-stacked text-success me-1"></i>Produits &amp; Ressources
                    </button>
                </li>
                <li class="nav-item d-none" id="city-tab-feast-li" role="presentation">
                    <button class="nav-link fw-bold text-dark py-2" id="city-tab-feast-btn" data-bs-toggle="tab" data-bs-target="#city-pane-feast" type="button" role="tab" aria-controls="city-pane-feast" aria-selected="false">
                        <i class="fa-solid fa-wine-bottle text-warning me-1"></i>Banquets &amp; Fêtes
                    </button>
                </li>
                <li class="nav-item d-none" id="city-tab-special-li" role="presentation">
                    <button class="nav-link fw-bold text-dark py-2" id="city-tab-special-btn" data-bs-toggle="tab" data-bs-target="#city-pane-special" type="button" role="tab" aria-controls="city-pane-special" aria-selected="false">
                        <i class="fa-solid fa-bolt text-primary me-1" id="city-tab-special-icon"></i><span id="city-tab-special-label">Activité</span>
                    </button>
                </li>
            </ul>

            <!-- Corps de la modale -->
            <div class="modal-body p-0 bg-white">
                <!-- PANNEAU A : BÂTIMENT EXISTANT / ATTRIBUÉ -->
                <div id="cityModalExistingView">
                    <div class="tab-content" id="cityModalTabContent">
                        <!-- TAB 1 : ÉVOLUTION -->
                        <div class="tab-pane fade show active p-4" id="city-pane-evolution" role="tabpanel">
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
                        </div>

                        <!-- TAB 2 : PRODUITS & RESSOURCES (CRAFT) -->
                        <div class="tab-pane fade p-4" id="city-pane-craft" role="tabpanel">
                            <div id="cityModalCraftContent">
                                <!-- Rempli dynamiquement -->
                            </div>
                        </div>

                        <!-- TAB 3 : BANQUETS & FÊTES (TENSHU) -->
                        <div class="tab-pane fade p-4" id="city-pane-feast" role="tabpanel">
                            <div id="cityModalFeastContent">
                                <!-- Rempli dynamiquement -->
                            </div>
                        </div>

                        <!-- TAB 4 : ACTIVITÉ SPÉCIFIQUE (DOJO, ÉCURIES, ACADÉMIE, MARCHÉ, EMBASSY...) -->
                        <div class="tab-pane fade p-4" id="city-pane-special" role="tabpanel">
                            <div id="cityModalSpecialContent">
                                <!-- Rempli dynamiquement -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANNEAU B : TERRAIN VIERGE / FONDATION D'UN NOUVEL ÉDIFICE -->
                <div id="cityModalFreePlotView" class="p-4 d-none">
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
    sake: <?= (int)($planet['sake'] ?? 0) ?>,
    rice_flour: <?= (int)($planet['rice_flour'] ?? 0) ?>,
    wooden_beams: <?= (int)($planet['wooden_beams'] ?? 0) ?>,
    charcoal: <?= (int)($planet['charcoal'] ?? 0) ?>,
    iron_sand: <?= (int)($planet['iron_sand'] ?? 0) ?>,
    sulfur: <?= (int)($planet['sulfur'] ?? 0) ?>,
    nitrate: <?= (int)($planet['nitrate'] ?? 0) ?>,
    roof_tiles: <?= (int)($planet['roof_tiles'] ?? 0) ?>,
    onigawara_tiles: <?= (int)($planet['onigawara_tiles'] ?? 0) ?>,
    prestige_weapons: <?= (int)($planet['prestige_weapons'] ?? 0) ?>,
    prestige_army: <?= (int)($planet['prestige_army'] ?? 0) ?>,
    hqLevel: <?= (int)$hqLevel ?>,
    activeFeast: <?= json_encode($activeFeast, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
    canQueue: <?= $canQueueNewBuilding ? 'true' : 'false' ?>
};

let currentCitySlotIndex = 19;
let cityCountdownInterval = null;
let cityFeastCountdownInterval = null;

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
    if (cityFeastCountdownInterval) {
        clearInterval(cityFeastCountdownInterval);
        cityFeastCountdownInterval = null;
    }

    // Réinitialisation de l'onglet actif sur "Évolution"
    const evoTabBtn = document.getElementById('city-tab-evolution-btn');
    if (evoTabBtn && window.bootstrap && window.bootstrap.Tab) {
        const tabInst = window.bootstrap.Tab.getOrCreateInstance(evoTabBtn);
        tabInst.show();
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

    const tabsNav = document.getElementById('cityModalTabsNav');
    const existingView = document.getElementById('cityModalExistingView');
    const freePlotView = document.getElementById('cityModalFreePlotView');
    const actionContainer = document.getElementById('cityModalActionContainer');

    if (data.is_empty) {
        // Affichage panneau terrain vierge (sans onglets)
        if (tabsNav) tabsNav.classList.add('d-none');
        if (existingView) existingView.classList.add('d-none');
        if (freePlotView) freePlotView.classList.remove('d-none');

        renderAvailableBuildingsList('all');

        if (actionContainer) {
            actionContainer.classList.remove('d-none');
            actionContainer.innerHTML = `
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Annuler
                </button>
            `;
        }
        return;
    }

    // Édifice construit / existant : afficher les onglets
    if (tabsNav) tabsNav.classList.remove('d-none');
    if (existingView) existingView.classList.remove('d-none');
    if (freePlotView) freePlotView.classList.add('d-none');

    // Visibilité conditionnelle des onglets selon le bâtiment
    const craftTabLi = document.getElementById('city-tab-craft-li');
    const feastTabLi = document.getElementById('city-tab-feast-li');
    const specialTabLi = document.getElementById('city-tab-special-li');

    const isCraftBuilding = ['grain_mill', 'sawmill', 'stonemason', 'blacksmith'].includes(data.code) && data.level > 0;
    const isFeastBuilding = (data.code === 'hq' && data.level > 0);
    const isSpecialBuilding = ['barracks', 'shipyard', 'research_lab', 'market', 'embassy', 'radar', 'wall', 'storage', 'tank', 'quantum_vault', 'teahouse', 'tournament_square'].includes(data.code) && data.level > 0;

    if (craftTabLi) craftTabLi.classList.toggle('d-none', !isCraftBuilding);
    if (feastTabLi) feastTabLi.classList.toggle('d-none', !isFeastBuilding);
    if (specialTabLi) specialTabLi.classList.toggle('d-none', !isSpecialBuilding);

    // Ajuster le libellé et l'icône de l'onglet spécial
    if (isSpecialBuilding) {
        const spIcon = document.getElementById('city-tab-special-icon');
        const spLabel = document.getElementById('city-tab-special-label');
        const spConfig = getSpecialTabMeta(data.code);
        if (spIcon) spIcon.className = spConfig.icon + ' me-1';
        if (spLabel) spLabel.textContent = spConfig.label;
    }

    // Rendu dynamique du contenu des onglets secondaires
    if (isCraftBuilding) renderCityCraftPane(data);
    if (isFeastBuilding) renderCityFeastPane(data);
    if (isSpecialBuilding) renderCitySpecialPane(data);

    // ==========================================
    // 1. RENDU DE L'ONGLET ÉVOLUTION (DE BASE)
    // ==========================================
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
}

// Configuration des métadonnées pour l'onglet spécial
function getSpecialTabMeta(code) {
    switch(code) {
        case 'barracks':
            return { label: 'Dojo & Garnison', icon: 'fa-solid fa-user-ninja text-danger' };
        case 'shipyard':
            return { label: 'Cavalerie & Flotte', icon: 'fa-solid fa-horse text-warning' };
        case 'research_lab':
            return { label: 'Académie & Savoirs', icon: 'fa-solid fa-scroll text-info' };
        case 'market':
            return { label: 'Marché & Convois', icon: 'fa-solid fa-scale-balanced text-success' };
        case 'embassy':
            return { label: 'Clan & Diplomatie', icon: 'fa-solid fa-torii-gate text-purple' };
        case 'radar':
            return { label: 'Vigie & Détection', icon: 'fa-solid fa-eye text-primary' };
        case 'wall':
            return { label: 'Fortifications', icon: 'fa-solid fa-shield-halved text-secondary' };
        case 'storage':
        case 'tank':
        case 'quantum_vault':
            return { label: 'Réserves & Silos', icon: 'fa-solid fa-boxes-stacked text-warning' };
        case 'teahouse':
            return { label: 'Cérémonie du Thé', icon: 'fa-solid fa-mug-hot text-success' };
        case 'tournament_square':
            return { label: 'Place d\'Exercices', icon: 'fa-solid fa-bullseye text-danger' };
        default:
            return { label: 'Activité', icon: 'fa-solid fa-bolt text-primary' };
    }
}

// ==========================================
// 2. RENDU DE L'ONGLET PRODUITS & RESSOURCES (CRAFT)
// ==========================================
function renderCityCraftPane(data) {
    const container = document.getElementById('cityModalCraftContent');
    if (!container) return;

    let html = '';
    const lvl = data.level;

    if (data.code === 'grain_mill') {
        html = `
            <div class="p-3 mb-3 rounded border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Stocks de Meunerie &amp; Brasserie :</span>
                    <div class="d-flex gap-3 mt-1 flex-wrap font-monospace small">
                        <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz brut : <strong>${Number(window.CITY_CONFIG.deuterium).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-bowl-rice text-dark me-1"></i>Farine de Riz : <strong>${Number(window.CITY_CONFIG.rice_flour).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-wine-bottle text-success me-1"></i>Saké Féodal : <strong>${Number(window.CITY_CONFIG.sake).toLocaleString()}</strong></span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Produit 1 : Farine de Riz -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="m-0 fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-bowl-rice text-warning fs-5"></i>
                                    Farine de Riz (Komeko)
                                </h4>
                                <span class="badge ${lvl >= 5 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">
                                    ${lvl >= 5 ? 'Opérationnel' : 'Niv. 5 Requis'}
                                </span>
                            </div>
                            <p class="small text-secondary mb-3" style="line-height:1.4;">
                                Mouture essentielle pour nourrir le peuple féodal et approvisionner les régiments d'infanterie d'Ashigaru.
                            </p>
                        </div>

                        ${lvl >= 5 ? `
                            <div>
                                <label class="form-label small text-muted mb-1">Quantité de riz à moudre :</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-grain-flour-amt" value="100" min="10" step="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-grain-flour-amt').value = Math.min(1000, window.CITY_CONFIG.deuterium)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-success w-100 fw-bold" onclick="executeCityCraft('craft_rice', 'rice_flour', 'craft-grain-flour-amt', this)">
                                    <i class="fa-solid fa-mortar-pestle me-1"></i>Moudre la Farine
                                </button>
                            </div>
                        ` : `
                            <div class="alert alert-secondary py-2 px-3 small m-0">
                                <i class="fa-solid fa-lock me-1"></i>Élevez la Meunerie au Niveau 5 pour débloquer la farine.
                            </div>
                        `}
                    </div>
                </div>

                <!-- Produit 2 : Saké Féodal -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="m-0 fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-wine-bottle text-success fs-5"></i>
                                    Saké Féodal (Nihonshu)
                                </h4>
                                <span class="badge ${lvl >= 10 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">
                                    ${lvl >= 10 ? 'Opérationnel' : 'Niv. 10 Requis'}
                                </span>
                            </div>
                            <p class="small text-secondary mb-3" style="line-height:1.4;">
                                Fermentation sacrée indispensable aux célébrations impériales, banquets du Tenshu et au moral des troupes.
                            </p>
                        </div>

                        ${lvl >= 10 ? `
                            <div>
                                <label class="form-label small text-muted mb-1">Quantité de riz à brasser :</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-grain-sake-amt" value="250" min="50" step="50">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-grain-sake-amt').value = Math.min(2500, window.CITY_CONFIG.deuterium)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-success w-100 fw-bold" onclick="executeCityCraft('craft_rice', 'sake', 'craft-grain-sake-amt', this)">
                                    <i class="fa-solid fa-wine-bottle me-1"></i>Brasser le Saké
                                </button>
                            </div>
                        ` : `
                            <div class="alert alert-secondary py-2 px-3 small m-0">
                                <i class="fa-solid fa-lock me-1"></i>Élevez la Meunerie au Niveau 10 pour brasser le Saké.
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    } else if (data.code === 'sawmill') {
        html = `
            <div class="p-3 mb-3 rounded border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Stocks de la Charpenterie (Kizukuri) :</span>
                    <div class="d-flex gap-3 mt-1 flex-wrap font-monospace small">
                        <span><i class="fa-solid fa-tree text-success me-1"></i>Bois brut : <strong>${Number(window.CITY_CONFIG.metal).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-hammer text-warning me-1"></i>Poutres traitées : <strong>${Number(window.CITY_CONFIG.wooden_beams).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-fire text-secondary me-1"></i>Charbon de bois : <strong>${Number(window.CITY_CONFIG.charcoal).toLocaleString()}</strong></span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Produit 1 : Poutres en Bois -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="m-0 fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-hammer text-warning fs-5"></i>
                                    Poutres en Bois (Kizukuri)
                                </h4>
                                <span class="badge ${lvl >= 10 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">
                                    ${lvl >= 10 ? 'Opérationnel' : 'Niv. 10 Requis'}
                                </span>
                            </div>
                            <p class="small text-secondary mb-3" style="line-height:1.4;">
                                Poutres maîtresses rabotées et équarries pour la solidité des charpentes castrales et engins de siège.
                            </p>
                        </div>

                        ${lvl >= 10 ? `
                            <div>
                                <label class="form-label small text-muted mb-1">Quantité de bois à façonner :</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-wood-beams-amt" value="100" min="10" step="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-wood-beams-amt').value = Math.min(1000, window.CITY_CONFIG.metal)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning w-100 fw-bold" onclick="executeCityCraft('craft_wood', 'wooden_beams', 'craft-wood-beams-amt', this)">
                                    <i class="fa-solid fa-hammer me-1"></i>Façonner des Poutres
                                </button>
                            </div>
                        ` : `
                            <div class="alert alert-secondary py-2 px-3 small m-0">
                                <i class="fa-solid fa-lock me-1"></i>Niveau 10 de Charpenterie requis pour équarrir les poutres.
                            </div>
                        `}
                    </div>
                </div>

                <!-- Produit 2 : Charbon de Bois -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="m-0 fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-fire text-danger fs-5"></i>
                                    Charbon de Bois (Sumiyaki)
                                </h4>
                                <span class="badge ${lvl >= 15 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">
                                    ${lvl >= 15 ? 'Opérationnel' : 'Niv. 15 Requis'}
                                </span>
                            </div>
                            <p class="small text-secondary mb-3" style="line-height:1.4;">
                                Combustible à très haute valeur calorifique pour la fonte du Tamahagane dans les fourneaux de la Grande Forge.
                            </p>
                        </div>

                        ${lvl >= 15 ? `
                            <div>
                                <label class="form-label small text-muted mb-1">Quantité de bois à carboniser :</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-wood-charcoal-amt" value="100" min="10" step="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-wood-charcoal-amt').value = Math.min(1000, window.CITY_CONFIG.metal)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger w-100 fw-bold" onclick="executeCityCraft('craft_wood', 'charcoal', 'craft-wood-charcoal-amt', this)">
                                    <i class="fa-solid fa-fire me-1"></i>Carboniser le Bois
                                </button>
                            </div>
                        ` : `
                            <div class="alert alert-secondary py-2 px-3 small m-0">
                                <i class="fa-solid fa-lock me-1"></i>Niveau 15 de Charpenterie requis pour produire du charbon.
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    } else if (data.code === 'stonemason') {
        html = `
            <div class="p-3 mb-3 rounded border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Stocks de Granit &amp; Minéraux Féodaux :</span>
                    <div class="d-flex gap-3 mt-1 flex-wrap font-monospace small">
                        <span><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre : <strong>${Number(window.CITY_CONFIG.crystal).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-gem text-danger me-1"></i>Satetsu : <strong>${Number(window.CITY_CONFIG.iron_sand).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-flask text-warning me-1"></i>Soufre : <strong>${Number(window.CITY_CONFIG.sulfur).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-vial text-info me-1"></i>Salpêtre : <strong>${Number(window.CITY_CONFIG.nitrate).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-layer-group text-dark me-1"></i>Tuiles : <strong>${Number(window.CITY_CONFIG.roof_tiles).toLocaleString()}</strong></span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Sable de fer -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="m-0 fw-bold text-dark fs-6 mb-1"><i class="fa-solid fa-gem text-danger me-1"></i>Sable de Fer (Satetsu)</h4>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Sable lourd ferreux filtré pour alimenter la forge de Tamahagane.</p>
                        </div>
                        <div>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" class="form-control font-monospace" id="craft-stone-satetsu-amt" value="100" min="10" step="10">
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-stone-satetsu-amt').value = Math.min(1000, window.CITY_CONFIG.crystal)">Max</button>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary w-100 fw-bold" onclick="executeCityCraft('craft_mineral', 'iron_sand', 'craft-stone-satetsu-amt', this)">
                                <i class="fa-solid fa-filter me-1"></i>Extraire le Satetsu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Soufre -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-flask text-warning me-1"></i>Soufre (Iō)</h4>
                                <span class="badge ${lvl >= 5 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 5 ? 'Niv. 5 OK' : 'Niv. 5 Requis'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Composé inflammable pour les engins de feu et la poudre noire.</p>
                        </div>
                        ${lvl >= 5 ? `
                            <div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-stone-sulfur-amt" value="100" min="10" step="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-stone-sulfur-amt').value = Math.min(1000, window.CITY_CONFIG.crystal)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning w-100 fw-bold" onclick="executeCityCraft('craft_mineral', 'sulfur', 'craft-stone-sulfur-amt', this)">
                                    <i class="fa-solid fa-mortar-pestle me-1"></i>Raffiner le Soufre
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Requis Niv. 5</div>`}
                    </div>
                </div>

                <!-- Salpêtre -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-vial text-info me-1"></i>Salpêtre (Shōseki)</h4>
                                <span class="badge ${lvl >= 10 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 10 ? 'Niv. 10 OK' : 'Niv. 10 Requis'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Cristallisation saline utilisée dans les fortifications et charges d'artillerie.</p>
                        </div>
                        ${lvl >= 10 ? `
                            <div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-stone-nitrate-amt" value="100" min="10" step="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-stone-nitrate-amt').value = Math.min(1000, window.CITY_CONFIG.crystal)">Max</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-info w-100 fw-bold text-white" onclick="executeCityCraft('craft_mineral', 'nitrate', 'craft-stone-nitrate-amt', this)">
                                    <i class="fa-solid fa-snowflake me-1"></i>Cristalliser Salpêtre
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Requis Niv. 10</div>`}
                    </div>
                </div>

                <!-- Tuiles Kawara -->
                <div class="col-md-6">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-layer-group text-dark me-1"></i>Tuiles Kawara (Hongawara)</h4>
                                <span class="badge ${lvl >= 20 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 20 ? 'Niv. 20 OK' : 'Niv. 20 Requis'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Cuisson des tuiles ignifuges du grand fourneau Kawara-gama.</p>
                        </div>
                        ${lvl >= 20 ? `
                            <div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" class="form-control font-monospace" id="craft-stone-tiles-amt" value="10" min="1" step="1">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('craft-stone-tiles-amt').value = 50">50</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-dark w-100 fw-bold" onclick="executeCityCraft('craft_tile', 'roof_tiles', 'craft-stone-tiles-amt', this)">
                                    <i class="fa-solid fa-fire-burner me-1"></i>Cuire Tuiles Kawara
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Niveau 20 Apogée Requis</div>`}
                    </div>
                </div>
            </div>
        `;
    } else if (data.code === 'blacksmith') {
        html = `
            <div class="p-3 mb-3 rounded border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Stocks de la Grande Forge Tatara (Kaji-ba) :</span>
                    <div class="d-flex gap-3 mt-1 flex-wrap font-monospace small">
                        <span><i class="fa-solid fa-gem text-danger me-1"></i>Satetsu : <strong>${Number(window.CITY_CONFIG.iron_sand).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-fire text-secondary me-1"></i>Charbon : <strong>${Number(window.CITY_CONFIG.charcoal).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-khanda text-warning me-1"></i>Armes Prestige : <strong>${Number(window.CITY_CONFIG.prestige_weapons).toLocaleString()}</strong></span>
                        <span><i class="fa-solid fa-shield-halved text-primary me-1"></i>Garde d'Élite : <strong>${Number(window.CITY_CONFIG.prestige_army).toLocaleString()}</strong></span>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Fonte Tamahagane -->
                <div class="col-md-4">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-fire text-danger me-1"></i>Fonte Tatara</h4>
                                <span class="badge ${lvl >= 15 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 15 ? 'Niv. 15' : 'Niv. 15'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Fonte de fer brut et Tamahagane à partir du Satetsu et Charbon.</p>
                        </div>
                        ${lvl >= 15 ? `
                            <div>
                                <button type="button" class="btn btn-sm btn-danger w-100 fw-bold" onclick="executeCityCraftDirect('craft_forge', 'metal', 1, this)">
                                    <i class="fa-solid fa-fire-burner me-1"></i>Allumer les Fourneaux
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Requis Niv. 15</div>`}
                    </div>
                </div>

                <!-- Armes d'Exception -->
                <div class="col-md-4">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-khanda text-warning me-1"></i>Armes Prestige</h4>
                                <span class="badge ${lvl >= 20 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 20 ? 'Niv. 20' : 'Niv. 20'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Forge d'équipements légendaires en acier plissé.</p>
                        </div>
                        ${lvl >= 20 ? `
                            <div>
                                <button type="button" class="btn btn-sm btn-warning w-100 fw-bold text-dark" onclick="executeCityCraftDirect('craft_forge', 'prestige_weapons', 1, this)">
                                    <i class="fa-solid fa-hammer me-1"></i>Forger Armes
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Requis Niv. 20</div>`}
                    </div>
                </div>

                <!-- Gardes d'Élite -->
                <div class="col-md-4">
                    <div class="city-craft-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h4 class="m-0 fw-bold text-dark fs-6"><i class="fa-solid fa-shield-halved text-primary me-1"></i>Garde Impériale</h4>
                                <span class="badge ${lvl >= 20 ? 'bg-success-lt text-success' : 'bg-secondary text-white'}">${lvl >= 20 ? 'Niv. 20' : 'Niv. 20'}</span>
                            </div>
                            <p class="small text-secondary mb-2" style="line-height:1.3;">Équipement complet d'armures lourdes de prestige.</p>
                        </div>
                        ${lvl >= 20 ? `
                            <div>
                                <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" onclick="executeCityCraftDirect('craft_forge', 'prestige_army', 1, this)">
                                    <i class="fa-solid fa-user-shield me-1"></i>Équiper la Garde
                                </button>
                            </div>
                        ` : `<div class="alert alert-secondary py-1 px-2 small m-0"><i class="fa-solid fa-lock me-1"></i>Requis Niv. 20</div>`}
                    </div>
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

// ==========================================
// 3. RENDU DE L'ONGLET BANQUETS & FÊTES (TENSHU)
// ==========================================
function renderCityFeastPane(data) {
    const container = document.getElementById('cityModalFeastContent');
    if (!container) return;

    const sakeStock = Number(window.CITY_CONFIG.sake || 0);
    const hqLvl = Number(window.CITY_CONFIG.hqLevel || 1);
    const activeFeast = window.CITY_CONFIG.activeFeast;

    let activeFeastHtml = '';
    if (activeFeast) {
        const feastNames = {
            'matsuri': ['Matsuri Populaire des Saisons', '<i class="fa-solid fa-fan text-warning"></i>', '+500 Points de Culture de Clan &amp; Sérénité civique'],
            'warriors': ['Banquet des Guerriers (Kanpai)', '<i class="fa-solid fa-khanda text-danger"></i>', '+1,200 Points de Culture &amp; +10% Puissance offensive'],
            'imperial': ['Grand Banquet Impérial & Diplomatique', '<i class="fa-solid fa-crown text-purple"></i>', '+2,500 Points de Culture &amp; Diplomatie suprême']
        };
        const fInfo = feastNames[activeFeast.feast_type] || ['Célébration Shogunale', '<i class="fa-solid fa-champagne-glasses text-warning"></i>', 'Festivités en cours'];

        activeFeastHtml = `
            <div class="alert alert-warning border border-warning shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:2rem;">${fInfo[1]}</span>
                        <div>
                            <strong class="d-block fs-5 text-dark">${fInfo[0]}</strong>
                            <span class="small text-muted">${fInfo[2]} &bull; Tenshu Niveau ${activeFeast.tenshu_level || hqLvl}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-uppercase fw-bold text-muted" style="font-size:0.75rem;">Temps restant :</div>
                        <span class="badge bg-danger text-white font-monospace fs-4 px-3 py-1.5 shadow-sm" id="cityModalFeastCountdown">--:--:--</span>
                    </div>
                </div>
            </div>
        `;

        const finishesAt = parseInt(activeFeast.finishes_at, 10);
        const updateFeastTimer = () => {
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, finishesAt - now);
            const cdEl = document.getElementById('cityModalFeastCountdown');
            if (cdEl) cdEl.textContent = formatCityDuration(remaining);
            if (remaining <= 0 && cityFeastCountdownInterval) {
                clearInterval(cityFeastCountdownInterval);
                cityFeastCountdownInterval = null;
            }
        };
        updateFeastTimer();
        cityFeastCountdownInterval = setInterval(updateFeastTimer, 1000);
    }

    const costMatsuri = 100 * hqLvl;
    const costWarriors = 200 * hqLvl;
    const costImperial = 350 * hqLvl;

    container.innerHTML = `
        <div class="p-3 mb-3 rounded border bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="text-muted small text-uppercase fw-bold d-block">Cave du Tenshu &amp; Prestige du Daimyō :</span>
                <div class="d-flex gap-3 mt-1 flex-wrap font-monospace small">
                    <span><i class="fa-solid fa-wine-bottle text-success me-1"></i>Saké en réserve : <strong class="text-success fs-6">${sakeStock.toLocaleString()}</strong> fûts</span>
                    <span><i class="fa-solid fa-chess-rook text-danger me-1"></i>Donjon Tenshu : <strong>Niveau ${hqLvl}</strong></span>
                </div>
            </div>
        </div>

        ${activeFeastHtml}

        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-glass-water-droplet text-warning me-1"></i>Banquets &amp; Fêtes Proclamables au Donjon :</h5>

        <div class="d-flex flex-column gap-3">
            <!-- 1. Matsuri Populaire -->
            <div class="city-feast-card p-3 rounded border" style="border-left: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:1.8rem;"><i class="fa-solid fa-fan text-warning"></i></span>
                        <div>
                            <strong class="d-block text-dark">Matsuri Populaire des Saisons</strong>
                            <p class="small text-muted m-0">Ferveur populaire, tambours Taiko et réjouissances civiques dans tout le domaine féodal.</p>
                            <span class="badge bg-warning-lt text-warning mt-1"><i class="fa-solid fa-star me-1"></i>+500 Points de Culture de Clan</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end font-monospace">
                            <span class="small text-muted d-block">Coût en Saké :</span>
                            <strong style="color:${sakeStock >= costMatsuri ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-wine-bottle me-1"></i>${costMatsuri.toLocaleString()} fûts</strong>
                        </div>
                        <button type="button" class="btn btn-sm ${sakeStock >= costMatsuri && !activeFeast ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary disabled'}" 
                                onclick="submitCityFeast('matsuri', this)" ${sakeStock >= costMatsuri && !activeFeast ? '' : 'disabled'}>
                            <i class="fa-solid fa-bullhorn me-1"></i>Proclamer
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Banquet des Guerriers -->
            <div class="city-feast-card p-3 rounded border" style="border-left: 4px solid #dc2626 !important;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:1.8rem;"><i class="fa-solid fa-khanda text-danger"></i></span>
                        <div>
                            <strong class="d-block text-dark">Banquet des Guerriers (Kanpai aux Samouraïs)</strong>
                            <p class="small text-muted m-0">Grands fûts de Saké ouverts pour les officiers et troupes d'élite du clan.</p>
                            <span class="badge bg-danger-lt text-danger mt-1"><i class="fa-solid fa-bolt me-1"></i>+1,200 Pts Culture &amp; +10% Puissance Offensive</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end font-monospace">
                            <span class="small text-muted d-block">Coût en Saké :</span>
                            <strong style="color:${sakeStock >= costWarriors ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-wine-bottle me-1"></i>${costWarriors.toLocaleString()} fûts</strong>
                        </div>
                        <button type="button" class="btn btn-sm ${sakeStock >= costWarriors && !activeFeast ? 'btn-danger fw-bold' : 'btn-outline-secondary disabled'}" 
                                onclick="submitCityFeast('warriors', this)" ${sakeStock >= costWarriors && !activeFeast ? '' : 'disabled'}>
                            <i class="fa-solid fa-champagne-glasses me-1"></i>Kanpai !
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Grand Banquet Impérial -->
            <div class="city-feast-card p-3 rounded border" style="border-left: 4px solid #7c3aed !important;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:1.8rem;"><i class="fa-solid fa-crown text-purple"></i></span>
                        <div>
                            <strong class="d-block text-dark">Grand Banquet Impérial &amp; Diplomatique</strong>
                            <p class="small text-muted m-0">Réception d'émissaires de la Cour Impériale de Kyoto et célébration de l'unification.</p>
                            <span class="badge bg-purple-lt text-purple mt-1"><i class="fa-solid fa-crown me-1"></i>+2,500 Pts Culture &amp; Diplomatie Suprême</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end font-monospace">
                            <span class="small text-muted d-block">Coût en Saké :</span>
                            <strong style="color:${sakeStock >= costImperial ? '#16a34a' : '#dc2626'};"><i class="fa-solid fa-wine-bottle me-1"></i>${costImperial.toLocaleString()} fûts</strong>
                        </div>
                        <button type="button" class="btn btn-sm ${sakeStock >= costImperial && !activeFeast ? 'btn-purple text-white fw-bold' : 'btn-outline-secondary disabled'}" 
                                onclick="submitCityFeast('imperial', this)" ${sakeStock >= costImperial && !activeFeast ? '' : 'disabled'} style="background:#7c3aed; border-color:#7c3aed;">
                            <i class="fa-solid fa-scroll me-1"></i>Célébrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ==========================================
// 4. RENDU DE L'ONGLET ACTIVITÉ SPÉCIFIQUE
// ==========================================
function renderCitySpecialPane(data) {
    const container = document.getElementById('cityModalSpecialContent');
    if (!container) return;

    let title = 'Activité Féodale';
    let icon = '<i class="fa-solid fa-bolt text-primary"></i>';
    let desc = 'Fonctions opérationnelles de cet édifice au sein de votre domaine castral.';
    let stats = [];
    let actionBtn = '';

    switch(data.code) {
        case 'barracks':
            title = 'Dojo Militaire & Garnison d\'Élite';
            icon = '<i class="fa-solid fa-user-ninja text-danger fs-1"></i>';
            desc = 'Centre d\'entraînement des redoutables fantassins du Shogunat : Ashigaru Yari, Archers Yumi, Samouraïs Katana et Shinobi de l\'ombre.';
            stats = [
                { label: 'Cadence d\'entraînement', val: `Niveau ${data.level} (Accéléré)` },
                { label: 'Troupes mobilisables', val: 'Ashigaru, Archers, Samouraïs' }
            ];
            actionBtn = `<a href="?page=barracks" class="btn btn-danger fw-bold px-4 py-2"><i class="fa-solid fa-user-ninja me-1"></i>Ouvrir le Terrain d'Enrôlement</a>`;
            break;
        case 'shipyard':
            title = 'Haras de Guerre & Chantiers Navals';
            icon = '<i class="fa-solid fa-horse text-warning fs-1"></i>';
            desc = 'Élevage des fougueux coursiers de cavalerie samouraï et assemblage des navires de guerre Atakebune de haute mer.';
            stats = [
                { label: 'Vitesse de mobilisation', val: `Niveau ${data.level}` },
                { label: 'Classes d\'unités', val: 'Cavalerie Légère & Lourde, Flottes' }
            ];
            actionBtn = `<a href="?page=shipyard" class="btn btn-warning text-dark fw-bold px-4 py-2"><i class="fa-solid fa-horse me-1"></i>Gérer les Haras &amp; Flottes</a>`;
            break;
        case 'research_lab':
            title = 'Académie des Savoirs & Parchemins';
            icon = '<i class="fa-solid fa-scroll text-info fs-1"></i>';
            desc = 'Bibliothèque des érudits impériaux. Recherche des traités tactiques, métallurgie du sabre, poliorcétique et voies spirituelles.';
            stats = [
                { label: 'Vitesse d\'étude', val: `Niveau ${data.level}` },
                { label: 'Domaines de recherche', val: 'Militaire, Économie, Siège' }
            ];
            actionBtn = `<a href="?page=research" class="btn btn-info text-white fw-bold px-4 py-2"><i class="fa-solid fa-scroll me-1"></i>Consulter l'Académie</a>`;
            break;
        case 'market':
            title = 'Marché Castral & Convois de Marchandises';
            icon = '<i class="fa-solid fa-scale-balanced text-success fs-1"></i>';
            desc = 'Place de négoce et affrètement de caravanes marchandes pour échanger vos surplus de riz, bois et fer avec les clans alliés.';
            stats = [
                { label: 'Capacité marchande convoi', val: `${data.level * 500} ressources` },
                { label: 'Caravanes simultanées', val: `${data.level} convois` }
            ];
            actionBtn = `<a href="?page=market" class="btn btn-success fw-bold px-4 py-2"><i class="fa-solid fa-scale-balanced me-1"></i>Entrer au Marché</a>`;
            break;
        case 'embassy':
            title = 'Pavillon des Alliances Féodales & Diplomatie';
            icon = '<i class="fa-solid fa-torii-gate text-purple fs-1"></i>';
            desc = 'Lieu de délibération des pactes de sang entre Daimyōs. Détermine le rang et la capacité d\'accueil de votre clan.';
            stats = [
                { label: 'Membres max du clan', val: `${3 * data.level} seigneurs` },
                { label: 'Pactes diplomatiques', val: 'Non-agression & Coalitions' }
            ];
            actionBtn = `<a href="?page=alliance" class="btn btn-purple text-white fw-bold px-4 py-2" style="background:#7c3aed; border-color:#7c3aed;"><i class="fa-solid fa-torii-gate me-1"></i>Affaires de Clan</a>`;
            break;
        case 'radar':
            title = 'Postes de Vigie & Tours de Surveillance';
            icon = '<i class="fa-solid fa-eye text-primary fs-1"></i>';
            desc = 'Réseau de guetteurs postés sur les collines et feux de signalisation alertant des approches ennemies et mouvements de troupes.';
            stats = [
                { label: 'Portée de détection', val: `${data.level * 2} lieues impériales` },
                { label: 'Précision tactique', val: 'Composition des armées en marche' }
            ];
            actionBtn = `<a href="?page=fleet" class="btn btn-primary fw-bold px-4 py-2"><i class="fa-solid fa-eye me-1"></i>Superviser les Marches</a>`;
            break;
        case 'wall':
            title = 'Murailles & Douves Castrales';
            icon = '<i class="fa-solid fa-shield-halved text-secondary fs-1"></i>';
            desc = 'Remparts cyclopéens en pierre taillée protégeant le fief des assauts et renforçant la résistance de chaque défenseur.';
            stats = [
                { label: 'Bonus défensif garnison', val: `+${data.level * 4}%` },
                { label: 'Points de structure mur', val: `+${data.level * 25} pts` }
            ];
            actionBtn = `<span class="badge bg-secondary-lt fs-6 p-2"><i class="fa-solid fa-shield-halved me-1"></i>Protection passive active sur le fief</span>`;
            break;
        case 'storage':
        case 'tank':
        case 'quantum_vault':
            title = 'Réserves & Silos Stratégiques';
            icon = '<i class="fa-solid fa-boxes-stacked text-warning fs-1"></i>';
            desc = 'Entrepôts fortifiés sécurisant les récoltes et métaux indispensables à la survie de la forteresse face aux sièges.';
            stats = [
                { label: 'Capacité totale protégée', val: `${(10000 + data.level * 15000).toLocaleString()} unités` }
            ];
            actionBtn = `<span class="badge bg-success-lt fs-6 p-2"><i class="fa-solid fa-check me-1"></i>Capacité de stockage opérationnelle</span>`;
            break;
        case 'teahouse':
            title = 'Pavillon de Thé & Cérémonie Chado';
            icon = '<i class="fa-solid fa-mug-hot text-success fs-1"></i>';
            desc = 'Havre de paix et de raffinement spirituel favorisant la sérénité du fief et l\'apaisement des rancœurs féodales.';
            stats = [
                { label: 'Bonus de Sérénité', val: `+${data.level * 25} quiétude` }
            ];
            actionBtn = `<span class="badge bg-success-lt fs-6 p-2"><i class="fa-solid fa-leaf me-1"></i>Atmosphère sereine du domaine</span>`;
            break;
        case 'tournament_square':
            title = 'Place d\'Exercices & Champs de Tir';
            icon = '<i class="fa-solid fa-bullseye text-danger fs-1"></i>';
            desc = 'Esplanade où les régiments répètent les marches forcées et les formations en phalange pour accélérer leurs déplacements.';
            stats = [
                { label: 'Vitesse de marche longue distance', val: `+${data.level * 10}% sur 20+ cases` }
            ];
            actionBtn = `<span class="badge bg-danger-lt fs-6 p-2"><i class="fa-solid fa-person-running me-1"></i>Cadence de marche bonifiée</span>`;
            break;
        default:
            title = data.name;
            icon = data.icon || '<i class="fa-solid fa-landmark text-primary fs-1"></i>';
            desc = data.desc;
    }

    let statsHtml = '';
    if (stats.length > 0) {
        statsHtml = `
            <div class="row g-2 mb-3">
                ${stats.map(st => `
                    <div class="col-sm-6">
                        <div class="p-2.5 rounded border bg-light">
                            <span class="text-muted small d-block">${st.label} :</span>
                            <strong class="text-dark font-monospace">${st.val}</strong>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    container.innerHTML = `
        <div class="p-4 rounded border mb-3" style="background:#f8fafc;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="min-width:48px; text-align:center;">${icon}</div>
                <div>
                    <h4 class="m-0 fw-bold text-dark fs-5">${title}</h4>
                    <span class="badge bg-secondary font-monospace mt-1">Niveau Actuel : ${data.level}</span>
                </div>
            </div>
            <p class="text-secondary small mb-3" style="line-height:1.5;">${desc}</p>
            ${statsHtml}
            <div class="text-center pt-2">
                ${actionBtn}
            </div>
        </div>
    `;
}

// ==========================================
// 5. EXÉCUTION DES ACTIONS AJAX (CRAFT & FEAST)
// ==========================================
async function executeCityCraft(action, product, inputId, btn) {
    const input = document.getElementById(inputId);
    const amount = input ? parseFloat(input.value) : 0;
    if (amount <= 0 && !window.CITY_CONFIG.isAdmin) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Veuillez indiquer une quantité supérieure à zéro.', 'warning');
        } else {
            alert('Quantité invalide.');
        }
        return;
    }
    await executeCityCraftDirect(action, product, amount, btn);
}

async function executeCityCraftDirect(action, product, amount, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Raffinage...';
    }

    const formData = new FormData();
    formData.append('action', action);
    formData.append('product', product);
    formData.append('amount', amount);
    formData.append('rice_amount', amount);
    formData.append('wood_amount', amount);
    formData.append('mineral_amount', amount);
    formData.append('batches', amount);

    try {
        const res = await fetch('/api/craft.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || 'Transformation féodale ordonnée avec honneur !', 'success', 'Atelier Féodal');
            }
            window.location.reload();
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-hammer me-1"></i>Réessayer';
            }
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer cette transformation.', 'warning', 'Ressources Insuffisantes');
            } else {
                alert(data.error);
            }
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        console.error('Erreur craft:', e);
    }
}

async function submitCityFeast(feastType, btn) {
    const names = {
        'matsuri': 'le Matsuri Populaire des Saisons',
        'warriors': 'le Banquet des Guerriers (Kanpai aux Samouraïs)',
        'imperial': 'le Grand Banquet Impérial & Diplomatique'
    };
    const fName = names[feastType] || 'cette célébration';

    if (typeof showModalConfirm === 'function') {
        const confirmed = await showModalConfirm(`Voulez-vous ouvrir les fûts de Saké et proclamer ${fName} au sein du Tenshu ?`, 'Célébration Shogunale');
        if (!confirmed) return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Proclamation...';
    }

    const formData = new FormData();
    formData.append('action', 'start_feast');
    formData.append('feast_type', feastType);

    try {
        const res = await fetch('/api/feast.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') {
                await showModalAlert(data.message || `Kanpai ! ${fName} a été proclamé au Donjon !`, 'success', 'Célébration Shogunale');
            }
            window.location.reload();
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bullhorn me-1"></i>Proclamer';
            }
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer cette festivité.', 'warning', 'Décret Impérial');
            } else {
                alert(data.error);
            }
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        console.error('Erreur feast:', e);
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
    // Écouteur pour ajuster l'affichage du footer selon l'onglet actif
    document.querySelectorAll('#cityModalTabsNav button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', (e) => {
            const target = e.target.getAttribute('data-bs-target');
            const actionContainer = document.getElementById('cityModalActionContainer');
            if (!actionContainer) return;
            if (target === '#city-pane-evolution') {
                actionContainer.classList.remove('d-none');
            } else {
                actionContainer.classList.add('d-none');
            }
        });
    });

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

