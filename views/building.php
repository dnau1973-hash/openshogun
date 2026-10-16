<?php
/**
 * Vue Dédiée : Détail & Amélioration d'un Bâtiment de Cité Castrale (Style Travian build.php)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$planet) {
    header('Location: /?page=city');
    exit;
}

$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();

// Récupérer la cartographie des slots de la cité
$citySlots = $planetEngine->getCitySlotMap((int)$planet['id']);

// Récupérer le numéro de slot ou le code de bâtiment
$slot = isset($_GET['slot']) ? (int)$_GET['slot'] : null;
$codeParam = $_GET['code'] ?? null;

if ($codeParam && !isset($_GET['slot'])) {
    foreach ($citySlots as $s => $sd) {
        if ($sd['code'] === $codeParam) {
            $slot = $s;
            break;
        }
    }
}

if (!$slot || $slot < 19 || $slot > 34) {
    $slot = 19;
}

$slotData = $citySlots[$slot] ?? ['slot' => $slot, 'code' => CITY_SLOT_LAYOUT[$slot] ?? 'free_plot', 'level' => 0];
$code = $slotData['code'];
$lvl = (int)$slotData['level'];

// Bâtiments de la planète
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = (int)($buildings['hq'] ?? 1);

// Navigation slots urbains précédents / suivants (19 à 34)
$prevSlot = ($slot > 19) ? $slot - 1 : 34;
$nextSlot = ($slot < 34) ? $slot + 1 : 19;

// Secteurs
$buildingSectors = [
    'hq' => ['name' => 'Tenshu Donjon', 'sec' => 'sec-hq', 'icon' => '<i class="fa-solid fa-chess-rook"></i>'],
    'shipyard' => ['name' => 'Écuries & Cavalerie', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-horse"></i>'],
    'barracks' => ['name' => 'Dojo Militaire', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-user-ninja"></i>'],
    'radar' => ['name' => 'Poste de Vigie', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-eye"></i>'],
    'wall' => ['name' => 'Muraille & Remparts', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-shield-halved"></i>'],
    'research_lab' => ['name' => 'Académie des Savoirs', 'sec' => 'sec-science', 'icon' => '<i class="fa-solid fa-scroll"></i>'],
    'embassy' => ['name' => 'Pavillon Diplomatique', 'sec' => 'sec-science', 'icon' => '<i class="fa-solid fa-torii-gate"></i>'],
    'storage' => ['name' => 'Greniers de Cèdre & Pierre', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-tree"></i>'],
    'tank' => ['name' => 'Silo à Riz Impérial', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-wheat-awn"></i>'],
    'quantum_vault' => ['name' => 'Cachette Secrète', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-lock"></i>'],
    'market' => ['name' => 'Marché Castral', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-scale-balanced"></i>'],
    'sawmill' => ['name' => 'Charpenterie (Kizukuri)', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-hammer"></i>'],
    'stonemason' => ['name' => 'Taille de Granit', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-mountain"></i>'],
    'grain_mill' => ['name' => 'Meunerie de Riz', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-bowl-rice"></i>'],
    'blacksmith' => ['name' => 'Grande Forge Tamahagane', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-khanda"></i>'],
    'teahouse' => ['name' => 'Pavillon de Thé', 'sec' => 'sec-science', 'icon' => '<i class="fa-solid fa-mug-hot"></i>'],
    'tournament_square' => ['name' => 'Place d\'Exercices', 'sec' => 'sec-military', 'icon' => '<i class="fa-solid fa-bullseye"></i>'],
    'free_plot' => ['name' => 'Terrain Vierge', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-flag"></i>'],
];

$sectorInfo = $buildingSectors[$code] ?? ['name' => 'Bâtiment Castral', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-chess-rook"></i>'];

// File active de construction
$queue = $buildingEngine->getQueue((int)$planet['id']);
$activeJob = null;
$fieldsInQueue = 0;
$buildingsInQueue = 0;

foreach ($queue as $q) {
    if (in_array($q['build_category'], ['field', 'rural_plot'])) {
        $fieldsInQueue++;
    } elseif ($q['build_category'] === 'building') {
        $buildingsInQueue++;
        if ($q['target_id'] === $code) {
            $activeJob = $q;
        }
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

$isBuildingInQueue = ($code !== 'free_plot' && $activeJob !== null);
$isEmptyPlot = ($code === 'free_plot');

// Bâtiments disponibles à la construction sur cet emplacement
$availableBuildingsToConstruct = [];
if ($isEmptyPlot) {
    foreach (BUILDINGS as $bCode => $bInfo) {
        $curLvl = (int)($buildings[$bCode] ?? 0);
        $inQ = false;
        foreach ($queue as $q) {
            if ($q['build_category'] === 'building' && $q['target_id'] === $bCode) {
                $inQ = true;
                break;
            }
        }
        if ($curLvl === 0 && !$inQ) {
            $details = $buildingEngine->getUpgradeDetails('building', $bCode, 0, $hqLevel);
            $cost = $details['cost'];
            $canAfford = $isAdmin ? true : ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
            $availableBuildingsToConstruct[$bCode] = [
                'info' => $bInfo,
                'details' => $details,
                'can_afford' => $canAfford,
                'sector' => $buildingSectors[$bCode] ?? ['name' => 'Logistique', 'sec' => 'sec-logistics', 'icon' => '<i class="fa-solid fa-boxes-stacked"></i>']
            ];
        }
    }
}

// Cartographie des illustrations artistiques des bâtiments castraux
$buildingHeroImages = [
    'hq' => 'buildings/building_tenshu.jpg',
    'barracks' => 'buildings/building_barracks.jpg',
    'shipyard' => 'buildings/building_shipyard.jpg',
    'research_lab' => 'buildings/building_research_lab.jpg',
    'radar' => 'buildings/building_radar.jpg',
    'storage' => 'buildings/building_storage.jpg',
    'tank' => 'buildings/building_tank.jpg',
    'market' => 'buildings/building_market.jpg',
    'embassy' => 'buildings/building_embassy.jpg',
    'quantum_vault' => 'buildings/building_quantum_vault.jpg',
    'wall' => 'buildings/building_wall.jpg',
    'sawmill' => 'buildings/building_sawmill.jpg',
    'stonemason' => 'buildings/building_stonemason.jpg',
    'grain_mill' => 'buildings/building_grain_mill.jpg',
    'blacksmith' => 'buildings/building_blacksmith.jpg',
    'teahouse' => 'buildings/building_teahouse.jpg',
    'tournament_square' => 'buildings/building_tournament_square.jpg',
];

if (!$isEmptyPlot) {
    $bInfo = BUILDINGS[$code] ?? null;
    $lvl = (int)($buildings[$code] ?? 0);
    $maxLevel = (int)($bInfo['max_level'] ?? 20);
    $isMaxLevel = ($lvl >= $maxLevel);
    $upgradeDetails = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
    $targetLevel = $upgradeDetails['target_level'];
    $cost = $upgradeDetails['cost'];
    $duration = $upgradeDetails['duration'];
    $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';
    $tileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $tileImg)
        ? '/public/assets/tiles/' . $tileImg
        : '/public/assets/' . $tileImg;

    // Illustration dédiée haute définition
    $heroImgRel = $buildingHeroImages[$code] ?? null;
    $heroImgFile = $heroImgRel ? __DIR__ . '/../public/assets/' . $heroImgRel : null;
    $buildingIllustrationUrl = ($heroImgFile && file_exists($heroImgFile))
        ? '/public/assets/' . $heroImgRel . '?v=' . filemtime($heroImgFile)
        : null;
    $heroBgUrl = $buildingIllustrationUrl ?? ('/public/assets/shogun_castle_city_bg.jpg?v=' . (file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1));

    // Vérification des ressources
    $hasMetal = $isAdmin ? true : ($planet['metal'] >= $cost['metal']);
    $hasCrystal = $isAdmin ? true : ($planet['crystal'] >= $cost['crystal']);
    $hasDeut = $isAdmin ? true : ($planet['deuterium'] >= $cost['deuterium']);
    $canAfford = $isAdmin ? true : ($hasMetal && $hasCrystal && $hasDeut);

    $missingWaitSeconds = 0;
    if (!$canAfford) {
        $prodRates = $planet['prod_rates'];
        $metalDiff = max(0, $cost['metal'] - $planet['metal']);
        $crystalDiff = max(0, $cost['crystal'] - $planet['crystal']);
        $deutDiff = max(0, $cost['deuterium'] - $planet['deuterium']);

        $waitTimes = [];
        if ($metalDiff > 0 && ($prodRates['metal'] ?? 0) > 0) {
            $waitTimes[] = ($metalDiff / ($prodRates['metal'] / 3600));
        }
        if ($crystalDiff > 0 && ($prodRates['crystal'] ?? 0) > 0) {
            $waitTimes[] = ($crystalDiff / ($prodRates['crystal'] / 3600));
        }
        if ($deutDiff > 0 && ($prodRates['deuterium'] ?? 0) > 0) {
            $waitTimes[] = ($deutDiff / ($prodRates['deuterium'] / 3600));
        }
        if (!empty($waitTimes)) {
            $missingWaitSeconds = (int)ceil(max($waitTimes));
        }
    }

    // Statistiques comparatives actuelles vs niveau supérieur
    $statData = match($code) {
        'wall' => [
            'label' => 'Bonus Défense Garnison',
            'cur' => '+' . ($lvl * 4) . '% (+' . ($lvl * 25) . ' pts mur)',
            'next' => '+' . ($targetLevel * 4) . '% (+' . ($targetLevel * 25) . ' pts mur)',
            'gain' => '+4% déf. & +25 pts structure'
        ],
        'hq' => [
            'label' => 'Réduction Temps Travaux',
            'cur' => '-' . (int)($lvl * 20) . '%',
            'next' => '-' . (int)($targetLevel * 20) . '%',
            'gain' => '-20% temps de chantier'
        ],
        'storage' => [
            'label' => 'Capacité Bois & Pierre',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLevel * 15000),
            'gain' => '+15,000 stockage'
        ],
        'tank' => [
            'label' => 'Capacité Silo à Riz',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLevel * 15000),
            'gain' => '+15,000 koku de réserve'
        ],
        'quantum_vault' => [
            'label' => 'Ressources Protégées (Cachette)',
            'cur' => number_format(1000 + $lvl * 1000),
            'next' => number_format(1000 + $targetLevel * 1000),
            'gain' => '+1,000 protégés du pillage'
        ],
        'barracks' => [
            'label' => 'Vitesse Entraînement Dojo',
            'cur' => 'Niveau ' . $lvl . ' (Accéléré)',
            'next' => 'Niveau ' . $targetLevel . ' (Plus Rapide)',
            'gain' => 'Cadence d\'enrôlement accrue'
        ],
        'shipyard' => [
            'label' => 'Écuries & Haras Féodaux',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => 'Mobilisation de cavalerie accélérée'
        ],
        'research_lab' => [
            'label' => 'Recherche Académique',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => 'Étude accélérée des savoirs'
        ],
        'radar' => [
            'label' => 'Portée du Poste de Guet',
            'cur' => ($lvl * 3) . ' lieues',
            'next' => ($targetLevel * 3) . ' lieues',
            'gain' => '+3 lieues de vigilance'
        ],
        'embassy' => [
            'label' => 'Prestige Diplomatique & Capacité',
            'cur' => ($lvl >= 3) ? 'Alliance débloquée (' . min(60, $lvl * 3) . ' membres)' : (($lvl >= 1) ? 'Pacte accessible (Niv. 1-2)' : 'Non Bâti'),
            'next' => ($targetLevel >= 3) ? 'Alliance débloquée (' . min(60, $targetLevel * 3) . ' membres)' : 'Pacte accessible',
            'gain' => ($targetLevel === 3) ? 'Déblocage de la création d\'alliance' : '+3 places d\'alliance'
        ],
        'market' => [
            'label' => 'Marchands & Convois',
            'cur' => ($lvl * 2) . ' marchands',
            'next' => ($targetLevel * 2) . ' marchands',
            'gain' => '+2 caravanes marchandes'
        ],
        'blacksmith' => [
            'label' => 'Forge & Réduction de Tamahagane',
            'cur' => ($lvl >= 20) ? 'Armes & Armées de Prestige (Niv. 20+)' : (($lvl >= 15) ? 'Fonte de Métal débloquée (Niv. 15+)' : 'Niv. ' . $lvl . ' / 15 (Fonte verrouillée)'),
            'next' => ($targetLevel >= 20) ? 'Armes & Armées de Prestige débloquées' : (($targetLevel >= 15) ? 'Fonte de Métal débloquée' : 'Progression Fonte (Niv. ' . $targetLevel . '/15)'),
            'gain' => ($targetLevel === 15) ? 'Déblocage de la fonte du Métal' : (($targetLevel === 20) ? 'Déblocage des Armes & Armées de Prestige' : '+15% vitesse de forge')
        ],
        'grain_mill' => [
            'label' => 'Meunerie & Brasserie (Raffinage)',
            'cur' => ($lvl >= 10) ? 'Farine & Saké opérationnels (Niv. 10+)' : (($lvl >= 5) ? 'Farine de riz débloquée (Saké au Niv. 10)' : 'Niv. ' . $lvl . ' / 5 (Farine Niv. 5, Saké Niv. 10)'),
            'next' => ($targetLevel >= 10) ? 'Déblocage des Cuves de Saké (Sakagura)' : (($targetLevel >= 5) ? 'Déblocage de la Farine de riz' : 'Progression Farine (Niv. ' . $targetLevel . '/5)'),
            'gain' => ($targetLevel === 5) ? 'Déblocage de la Farine de riz' : (($targetLevel === 10) ? 'Déblocage du Saké Féodal' : '+5% récolte riz & +15% vitesse raffinage')
        ],
        default => [
            'label' => 'Niveau d\'élévation',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => '+1 niveau'
        ]
    };
}
?>
<!-- Navigation breadcrumb Tabler -->
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Cité Castrale de <?= htmlspecialchars($planet['name']) ?></div>
            <h2 class="page-title font-game">
                <?php if ($isEmptyPlot): ?>
                    <i class="fa-solid fa-flag text-danger me-1"></i>Terrain Castral #<?= $slot ?>
                    <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">Terrain Libre</span>
                <?php else: ?>
                    <?= $bInfo['icon'] ?? '<i class="fa-solid fa-chess-rook"></i>' ?> <?= htmlspecialchars($bInfo['name']) ?>
                    <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">Slot #<?= $slot ?> · Niv.<?= $lvl ?></span>
                    <?php if ($activeJob): ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem; vertical-align:middle;"><i class="fa-solid fa-hourglass-half me-1"></i>Chantier en cours</span>
                    <?php elseif ($lvl > 0): ?>
                        <span class="badge bg-success ms-1" style="font-size:0.65rem; vertical-align:middle;">Opérationnel</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem; vertical-align:middle;">Non Bâti</span>
                    <?php endif; ?>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="/?page=building&slot=<?= $prevSlot ?>" class="btn btn-outline-secondary">
                    ← Slot #<?= $prevSlot ?>
                </a>
                <a href="/?page=city" class="btn btn-secondary">
                    <i class="fa-solid fa-chess-rook me-1"></i>Vue Cité
                </a>
                <a href="/?page=building&slot=<?= $nextSlot ?>" class="btn btn-outline-secondary">
                    Slot #<?= $nextSlot ?> →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards : résumé rapide -->
<div class="row row-cards mb-3">
    <?php if ($isEmptyPlot): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-blue-lt" style="font-size:1.3rem;"><i class="fa-solid fa-flag text-danger"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Emplacement</div>
                            <div class="text-secondary">Slot Castral #<?= $slot ?> sur 34</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;"><i class="fa-solid fa-sparkles text-success"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">État du Terrain</div>
                            <div class="text-success">Prêt à bâtir</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;"><i class="fa-solid fa-chess-rook text-danger"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Tenshu Donjon</div>
                            <div class="text-warning">Niveau <?= $hqLevel ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-info-lt" style="font-size:1.3rem;"><i class="fa-solid fa-helmet-safety text-danger"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Édifices Éligibles</div>
                            <div class="text-info"><?= count($availableBuildingsToConstruct) ?> constructibles</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded" style="background:rgba(220,38,38,0.12); font-size:1.3rem;"><?= $bInfo['icon'] ?? '<i class="fa-solid fa-chess-rook"></i>' ?></span></div>
                        <div class="col">
                            <div class="font-weight-medium"><?= htmlspecialchars($statData['label']) ?></div>
                            <div class="text-secondary"><?= $statData['cur'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <?php if ($isMaxLevel): ?>
                            <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;"><i class="fa-solid fa-crown text-warning"></i></span></div>
                            <div class="col">
                                <div class="font-weight-medium">Apogée Maximale</div>
                                <div class="text-warning fw-bold"><i class="fa-solid fa-star me-1"></i>Niveau <?= $maxLevel ?> (Atteint)</div>
                            </div>
                        <?php else: ?>
                            <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;"><i class="fa-solid fa-chart-line text-success"></i></span></div>
                            <div class="col">
                                <div class="font-weight-medium">Niveau <?= $targetLevel ?> → Rendement</div>
                                <div class="text-success"><?= $statData['next'] ?> <span class="text-muted" style="font-size:0.75rem;">(<?= $statData['gain'] ?>)</span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <?php if ($isMaxLevel): ?>
                            <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;"><i class="fa-solid fa-check-double text-success"></i></span></div>
                            <div class="col">
                                <div class="font-weight-medium">Structure Parachevée</div>
                                <div class="text-success fw-bold">Édifice à son comble</div>
                                <div class="text-muted" style="font-size:0.72rem;">Aucun agrandissement requis</div>
                            </div>
                        <?php else: ?>
                            <div class="col-auto"><span class="avatar rounded bg-info-lt" style="font-size:1.3rem;"><i class="fa-solid fa-stopwatch text-info"></i></span></div>
                            <div class="col">
                                <div class="font-weight-medium">Temps de construction</div>
                                <div class="text-info font-monospace"><?= gmdate('H:i:s', $duration) ?></div>
                                <div class="text-muted" style="font-size:0.72rem;">Tenshu Niv.<?= $hqLevel ?> &bull; Bâtisseurs du clan</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;"><?= $sectorInfo['icon'] ?? '<i class="fa-solid fa-chess-rook"></i>' ?></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Secteur Castral</div>
                            <div class="text-warning"><?= htmlspecialchars($sectorInfo['name']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row row-cards">

    <?php if ($isEmptyPlot): ?>
        <!-- ==========================================
             CAS A : TERRAIN LIBRE / EMPLACEMENT VIERGE
             ========================================== -->
        <div class="col-12">
            <div class="card mb-3">
                <div style="height:140px; background:linear-gradient(135deg,rgba(71,85,105,0.15),rgba(30,41,59,0.08)); display:flex; align-items:center; justify-content:center; font-size:3.5rem;">
                    <i class="fa-solid fa-flag text-danger"></i>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="card-title mb-1">Emplacement Castral #<?= $slot ?></h3>
                            <div class="text-muted" style="font-size:0.8rem;">Cour intérieure &bull; Domaine de <?= htmlspecialchars($planet['name']) ?></div>
                        </div>
                        <a href="/?page=city" class="btn btn-outline-secondary">
                            ← Revenir à la Cité Castrale
                        </a>
                    </div>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.875rem;">
                        Cet emplacement est viabilisé au sein de l'enceinte de votre forteresse. Au fur et à mesure de l'élévation de votre Tenshu, de nouvelles structures féodales pourront y être fondées.
                    </p>
                </div>
            </div>

            <!-- Catalogue des Bâtiments Constructibles -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title"><i class="fa-solid fa-helmet-safety text-danger me-1"></i>Fonder une Nouvelle Structure Féodale</h3>
                        <div class="text-muted" style="font-size:0.8rem;">Sélectionnez l'édifice à ériger sur l'emplacement #<?= $slot ?></div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($availableBuildingsToConstruct)): ?>
                        <div class="text-center py-4 text-muted">
                            <span style="font-size:2rem;"><i class="fa-solid fa-chess-rook text-danger"></i></span>
                            <p class="mt-2 mb-0">Toutes les structures féodales uniques sont déjà érigées dans votre cité castrale.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($availableBuildingsToConstruct as $bCode => $item):
                                $info = $item['info'];
                                $det = $item['details'];
                                $c = $det['cost'];
                                $dur = $det['duration'];
                                $canAfford = $item['can_afford'];
                                $durFormatted = sprintf('%02d:%02d', floor($dur / 60), $dur % 60);
                                $bHeroImg = $buildingHeroImages[$bCode] ?? null;
                                $bHeroFile = $bHeroImg ? __DIR__ . '/../public/assets/' . $bHeroImg : null;
                                $constructTileUrl = !empty($info['tile_img'])
                                    ? (file_exists(__DIR__ . '/../public/assets/tiles/' . $info['tile_img']) ? '/public/assets/tiles/' . $info['tile_img'] : '/public/assets/' . $info['tile_img'])
                                    : null;
                            ?>
                                <div class="list-group-item p-3">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div style="width:56px; height:56px; border-radius:8px; overflow:hidden; border:1px solid var(--tblr-border-color); display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.04);">
                                                <?php if ($bHeroFile && file_exists($bHeroFile)): ?>
                                                    <img src="/public/assets/<?= $bHeroImg ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                                <?php elseif ($constructTileUrl): ?>
                                                    <img src="<?= $constructTileUrl ?>" alt="" style="width:40px; height:40px; object-fit:contain;">
                                                <?php else: ?>
                                                    <span style="font-size:1.8rem;"><?= $info['icon'] ?? '<i class="fa-solid fa-chess-rook"></i>' ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center gap-2">
                                                <h4 class="mb-0 fw-bold"><?= htmlspecialchars($info['name']) ?></h4>
                                                <span class="badge bg-secondary-lt" style="font-size:0.68rem;"><?= htmlspecialchars($item['sector']['name'] ?? 'Castral') ?></span>
                                            </div>
                                            <p class="text-muted mb-2 mt-1" style="font-size:0.8rem; line-height:1.35;">
                                                <?= htmlspecialchars($info['description']) ?>
                                            </p>
                                            <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:0.8rem;">
                                                <span class="<?= ($planet['metal'] >= $c['metal']) ? 'text-success' : 'text-danger' ?>">
                                                    <i class="fa-solid fa-tree text-success me-1"></i><?= number_format($c['metal']) ?>
                                                </span>
                                                <span class="<?= ($planet['crystal'] >= $c['crystal']) ? 'text-success' : 'text-danger' ?>">
                                                    <i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($c['crystal']) ?>
                                                </span>
                                                <span class="<?= ($planet['deuterium'] >= $c['deuterium']) ? 'text-success' : 'text-danger' ?>">
                                                    <i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($c['deuterium']) ?>
                                                </span>
                                                <span class="text-muted">
                                                    <i class="fa-solid fa-stopwatch text-info me-1"></i><?= $durFormatted ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <?php if ($canAfford && $canQueueNewBuilding): ?>
                                                <button type="button" class="btn btn-primary" onclick="launchBuildingUpgrade('<?= $bCode ?>', 1, <?= $slot ?>)">
                                                    <i class="fa-solid fa-hammer me-1"></i><?= $isAdmin ? '⚡ Bâtir (Gratuit Admin)' : 'Bâtir' ?>
                                                </button>
                                            <?php elseif (!$canAfford): ?>
                                                <button type="button" class="btn btn-secondary disabled" disabled>
                                                    Matériaux Insuffisants
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary disabled" disabled>
                                                    Chantier Occupé
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ==========================================
             CAS B : BÂTIMENT FÉODAL ACTIF
             ========================================== -->
        <div class="col-12">

            <!-- Card identité du bâtiment (Vue Pleine Largeur) -->
            <div class="card mb-3">
                <div class="row g-0 align-items-stretch">
                    <?php if ($buildingIllustrationUrl): ?>
                        <div class="col-md-4 col-lg-3 position-relative" style="cursor:pointer; overflow:hidden; min-height:220px;" title="Agrandir l'estampe" onclick="openArtworkModal('<?= $buildingIllustrationUrl ?>', '<?= htmlspecialchars(addslashes($bInfo['name'])) ?>')">
                            <img src="<?= $buildingIllustrationUrl ?>" alt="<?= htmlspecialchars($bInfo['name']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">                        
                            <!-- Badge Transparence IA Prompts (« ? ») -->
                            <?= AiPromptHelper::renderBadge($heroImgRel, $bInfo['name'], $buildingIllustrationUrl) ?>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4 col-lg-3 d-flex align-items-center justify-content-center" style="min-height:160px; background:linear-gradient(135deg,rgba(185,28,28,0.15),rgba(30,41,59,0.1)); font-size:3.5rem;">
                            <?= $bInfo['icon'] ?? '<i class="fa-solid fa-chess-rook"></i>' ?>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-8 col-lg-9">
                        <div class="card-body d-flex flex-column justify-content-between h-100">
                            <div>
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3" style="cursor:pointer; display:inline-block;" onclick="openArtworkModal('<?= $tileUrl ?>', '<?= htmlspecialchars(addslashes($bInfo['name'])) ?> &bull; Bâtiment')" title="Agrandir le bâtiment">
                                            <img src="<?= $tileUrl ?>" alt="<?= htmlspecialchars($bInfo['name']) ?>" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:2px solid var(--tblr-border-color); display:block;">
                                            <span class="badge bg-dark text-white position-absolute" style="bottom:-4px; right:-4px; font-size:0.6rem; padding:2px 4px; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.3); pointer-events:none;" title="Agrandir">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h2 class="card-title mb-0 fs-2"><?= htmlspecialchars($bInfo['name']) ?></h2>
                                            <div class="text-muted" style="font-size:0.85rem;">Emplacement Castral #<?= $slot ?> &bull; Niveau actuel : <strong class="badge bg-danger text-white fs-6 py-0 px-2"><?= $lvl ?></strong></div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="/?page=city" class="btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-arrow-left me-1"></i>Retour Cité
                                        </a>
                                        <?php if ($code !== 'hq' && $lvl > 0 && !$activeJob): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="confirmDemolishBuilding('<?= $code ?>', <?= $slot ?>, '<?= htmlspecialchars(addslashes($bInfo['name'] ?? $code)) ?>')">
                                                <i class="fa-solid fa-trash-can me-1"></i>Démanteler
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php
                                require_once __DIR__ . '/../core/PopulationEngine.php';
                                $bWorkersReq = PopulationEngine::getBuildingWorkersRequired($code, $lvl);
                                $bWorkersNext = PopulationEngine::getBuildingWorkersRequired($code, $targetLevel);
                                $bWorkforce = $planet['workforce'] ?? null;
                                $bUnderstaffed = !empty($bWorkforce['is_understaffed']);
                                $bMalusPct = (float)($bWorkforce['understaffed_malus_pct'] ?? 0);
                                ?>
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge bg-azure-lt d-inline-flex align-items-center gap-1" title="Main-d'œuvre nécessaire au fonctionnement">
                                        <i class="fa-solid fa-users text-azure"></i> <?= $bWorkersReq ?> ouvriers requis <?= (!$isMaxLevel && $targetLevel > $lvl) ? "(Niv.{$targetLevel} : {$bWorkersNext})" : '' ?>
                                    </span>
                                    <?php if ($bUnderstaffed): ?>
                                        <span class="badge bg-warning-lt text-warning d-inline-flex align-items-center gap-1" title="Le fief souffre d'un manque d'ouvriers">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Sous-effectif (-<?= $bMalusPct ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-lt text-success d-inline-flex align-items-center gap-1" title="Postes 100% pourvus">
                                            <i class="fa-solid fa-circle-check"></i> Effectif Complet
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted mb-3" style="font-size:0.9rem;"><?= htmlspecialchars($bInfo['description']) ?></p>
                            </div>

                            <!-- Raccourcis opérationnels directs -->
                            <?php if ($lvl > 0): ?>
                                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                                    <?php if ($code === 'barracks'): ?>
                                        <a href="/?page=barracks" class="btn btn-primary">
                                            <i class="fa-solid fa-user-ninja me-1"></i>Ouvrir le Dojo d'Entraînement des Troupes &rarr;
                                        </a>
                                    <?php elseif ($code === 'shipyard'): ?>
                                        <a href="/?page=shipyard" class="btn btn-primary">
                                            <i class="fa-solid fa-horse me-1"></i>Accéder aux Écuries de Cavalerie & Machines &rarr;
                                        </a>
                                    <?php elseif ($code === 'research_lab'): ?>
                                        <a href="/?page=research" class="btn btn-primary">
                                            <i class="fa-solid fa-scroll me-1"></i>Consulter l'Académie & Savoirs Féodaux &rarr;
                                        </a>
                                    <?php elseif ($code === 'embassy'): ?>
                                        <a href="/?page=alliance" class="btn btn-primary">
                                            <i class="fa-solid fa-flag me-1"></i>Ouvrir le Pavillon des Alliances Féodales &rarr;
                                        </a>
                                    <?php elseif ($code === 'grain_mill' && $lvl >= 5): ?>
                                        <a href="#craftSection" class="btn btn-success text-white">
                                            <i class="fa-solid fa-bowl-rice me-1"></i>Accéder à la Meunerie &amp; Brasserie &darr;
                                        </a>
                                    <?php elseif ($code === 'sawmill' && $lvl >= 10): ?>
                                        <a href="#craftSection" class="btn btn-warning text-dark fw-bold">
                                            <i class="fa-solid fa-hammer me-1"></i>Accéder au Façonnage de Poutres &darr;
                                        </a>
                                    <?php elseif ($code === 'stonemason' && $lvl > 0): ?>
                                        <a href="#craftSection" class="btn btn-secondary text-white fw-bold">
                                            <i class="fa-solid fa-gem me-1"></i>Accéder à l'Atelier Minéralier &darr;
                                        </a>
                                    <?php elseif ($code === 'blacksmith' && $lvl >= 15): ?>
                                        <a href="#craftSection" class="btn btn-danger text-white fw-bold">
                                            <i class="fa-solid fa-fire-burner me-1"></i>Accéder aux Fourneaux Tatara &amp; Forge &darr;
                                        </a>
                                    <?php elseif ($code === 'hq'): ?>
                                        <a href="#feastSection" class="btn btn-warning text-dark fw-bold">
                                            <i class="fa-solid fa-wine-bottle me-1"></i>Salle des Banquets &amp; Célébrations &darr;
                                        </a>
                                    <?php elseif ($code === 'market'): ?>
                                        <a href="#marketSection" class="btn btn-warning text-dark fw-bold">
                                            <i class="fa-solid fa-scale-balanced me-1"></i>Accéder au Marché &amp; Convois de Marchandises &darr;
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($code === 'hq'):
                $activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
                $tenshuLvl = max(1, $lvl);
                $matsuriCost = 100 * $tenshuLvl;
                $warriorsCost = 200 * $tenshuLvl;
                $imperialCost = 350 * $tenshuLvl;
                $currentSake = (float)($planet['sake'] ?? 0);

                $pop = (int)($planet['population'] ?? 100);
                $popMax = (int)($planet['population_max'] ?? 100);
                $popPct = ($popMax > 0) ? min(100, round(($pop / $popMax) * 100)) : 100;
                $popBonus = min(25, (int)round($pop / 100));
            ?>
            <!-- ========================================================
                 ZONE DU TENSHU : DÉMOGRAPHIE DU DOMAINE & SALLE DES BANQUETS
                 ======================================================== -->
            <!-- Carte 1 : Démographie & Main-d'Œuvre -->
            <div class="card mb-3" style="border-top: 3px solid #3b82f6;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div>
                        <h3 class="card-title text-primary d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-users text-primary"></i></span> Démographie &amp; Ouvriers du Domaine Castral
                        </h3>
                        <div class="text-secondary small mt-1">
                            La population d'artisans, bûcherons et fermiers est logée par vos parcelles d'habitation du village et soutenue par la farine de riz.
                        </div>
                    </div>
                    <span class="badge bg-blue-lt fw-bold">
                        Bonus Chantiers : +<?= $popBonus ?>% vitesse
                    </span>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-center g-3 mb-2">
                        <div class="col-sm-3 text-center border-end">
                            <div class="text-secondary small">Habitants Actuels</div>
                            <div class="fs-3 fw-bold text-primary"><?= number_format($pop) ?></div>
                            <div class="text-muted small">/ <?= number_format($popMax) ?> logements</div>
                        </div>
                        <div class="col-sm-3 text-center border-end">
                            <div class="text-secondary small">Main-d'Œuvre</div>
                            <div class="fs-3 fw-bold text-dark"><?= number_format($bWorkforce['assigned_workers'] ?? $pop) ?></div>
                            <div class="text-muted small">/ <?= number_format($bWorkforce['required_workers'] ?? 0) ?> requis (<?= number_format($bWorkforce['idle_workers'] ?? 0) ?> libres)</div>
                        </div>
                        <div class="col-sm-3 text-center border-end">
                            <div class="text-secondary small">Satisfaction</div>
                            <div class="fs-3 fw-bold text-<?= $planet['contentment_details']['badge_color'] ?? 'success' ?>"><?= (int)($planet['contentment'] ?? 85) ?>%</div>
                            <div class="text-muted small"><?= htmlspecialchars($planet['contentment_details']['status_label'] ?? 'Paisible') ?></div>
                        </div>
                        <div class="col-sm-3 text-center">
                            <div class="text-secondary small"><i class="fa-solid fa-bolt text-warning me-1"></i>Bonus Bâtisseurs</div>
                            <div class="fs-3 fw-bold text-success">+<?= $popBonus ?>%</div>
                            <div class="text-muted small">vitesse de construction</div>
                        </div>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: <?= $popPct ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Carte 2 : Salle des Banquets & Célébrations Féodales -->
            <div class="card mb-3" id="feastSection" style="border-top: 3px solid #f59e0b;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-warning d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-wine-bottle text-warning"></i></span> Salle des Banquets &amp; Célébrations Féodales du Tenshu
                        </h3>
                        <div class="text-secondary small mt-1">
                            Organisez des réceptions et fêtes grâce à vos réserves de Saké. Les festivités et leurs bienfaits sont intimement corrélés à l'élévation de votre Tenshu (Niveau <?= $tenshuLvl ?>).
                        </div>
                    </div>
                    <span class="badge bg-warning-lt fw-bold">
                        Stock Saké : <?= number_format((int)$currentSake) ?> <i class="fa-solid fa-wine-bottle text-danger"></i>
                    </span>
                </div>
                <div class="card-body">

                    <?php if ($activeFeast): ?>
                        <?php
                            $feastNames = [
                                'matsuri' => ['Matsuri Populaire des Saisons', '<i class="fa-solid fa-fan text-warning"></i>', 'bg-warning-lt text-warning'],
                                'warriors' => ['Banquet des Guerriers (Kanpai)', '<i class="fa-solid fa-khanda text-danger"></i>', 'bg-danger-lt text-danger'],
                                'imperial' => ['Grand Banquet Impérial & Diplomatique', '<i class="fa-solid fa-crown text-purple"></i>', 'bg-purple-lt text-purple']
                            ];
                            $fInfo = $feastNames[$activeFeast['feast_type']] ?? ['Festivités en cours', '<i class="fa-solid fa-champagne-glasses text-warning"></i>', 'bg-warning-lt text-warning'];
                        ?>
                        <div class="alert alert-warning border border-warning shadow-sm mb-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-size:1.8rem;"><?= $fInfo[1] ?></span>
                                    <div>
                                        <div class="fw-bold fs-4 text-warning">Célébration Active : <?= $fInfo[0] ?></div>
                                        <div class="small text-secondary">
                                            Tenshu Niveau <?= (int)$activeFeast['tenshu_level'] ?> &bull; Tous les vassaux et gens du fief célèbrent sous l'égide du Daimyō !
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning text-dark font-monospace fs-5 py-2 px-3" data-countdown="<?= $activeFeast['finishes_at'] ?>">
                                        Calcul du temps...
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Les 3 Festivités Corrélées au Tenshu -->
                    <div class="row g-3">
                        <!-- Fête 1 : Matsuri Populaire -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-fan text-warning"></i></span> Matsuri Saisonnier
                                        </h4>
                                        <span class="badge bg-success-lt">Niv. 1+</span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Ferveur populaire, danses et offrandes aux Kamis pour la prospérité des récoltes.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div><i class="fa-solid fa-cubes text-secondary me-1"></i>Prod : <strong class="text-success">+<?= 5 + $tenshuLvl ?>%</strong></div>
                                        <div><i class="fa-solid fa-torii-gate text-danger me-1"></i>Sérénité : <strong class="text-info">+<?= 10 + ($tenshuLvl * 2) ?></strong></div>
                                        <div><i class="fa-solid fa-stopwatch text-info me-1"></i>Durée : <strong>8 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $matsuriCost) ? 'text-warning' : 'text-danger' ?>"><?= number_format($matsuriCost) ?> Saké <i class="fa-solid fa-wine-bottle text-danger"></i></strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-warning w-100 text-dark fw-bold btn-sm"
                                        onclick="submitTenshuFeast('matsuri')"
                                        <?= (!$isAdmin && ($activeFeast || $currentSake < $matsuriCost)) ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-fan me-1"></i>Célébrer le Matsuri
                                </button>
                            </div>
                        </div>

                        <!-- Fête 2 : Banquet des Guerriers -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-khanda text-danger"></i></span> Banquet Guerriers
                                        </h4>
                                        <span class="badge <?= ($tenshuLvl >= 5 || $isAdmin) ? 'bg-danger-lt text-danger' : 'bg-secondary text-white' ?>">
                                            <?= ($tenshuLvl >= 5 || $isAdmin) ? 'Niv. 5+' : '<i class="fa-solid fa-lock text-muted me-1"></i>Niv. 5 requis' ?>
                                        </span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Toast d'honneur (Kanpai) aux samouraïs et vétérans pour galvaniser le moral des troupes.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div><i class="fa-solid fa-user-ninja text-danger me-1"></i>Dojo &amp; Écuries : <strong class="text-danger">-<?= 10 + $tenshuLvl ?>% durée</strong></div>
                                        <div><i class="fa-solid fa-khanda text-danger me-1"></i>Attaque Garnison : <strong class="text-danger">+5%</strong></div>
                                        <div><i class="fa-solid fa-stopwatch text-info me-1"></i>Durée : <strong>6 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $warriorsCost || $isAdmin) ? 'text-warning' : 'text-danger' ?>"><?= number_format($warriorsCost) ?> Saké <i class="fa-solid fa-wine-bottle text-danger"></i></strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-danger w-100 fw-bold btn-sm"
                                        onclick="submitTenshuFeast('warriors')"
                                        <?= (!$isAdmin && ($activeFeast || $tenshuLvl < 5 || $currentSake < $warriorsCost)) ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-khanda me-1"></i>Lever le Kanpai
                                </button>
                            </div>
                        </div>

                        <!-- Fête 3 : Banquet Impérial -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-crown text-purple"></i></span> Banquet Impérial
                                        </h4>
                                        <span class="badge <?= ($tenshuLvl >= 10 || $isAdmin) ? 'bg-purple-lt' : 'bg-secondary text-white' ?>">
                                            <?= ($tenshuLvl >= 10 || $isAdmin) ? 'Niv. 10+' : '<i class="fa-solid fa-lock text-muted me-1"></i>Niv. 10 requis' ?>
                                        </span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Réception somptueuse pour les émissaires impériaux et dignitaires des clans alliés.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div><i class="fa-solid fa-flag text-purple me-1"></i>Prestige : <strong class="text-purple">+<?= 50 + ($tenshuLvl * 5) ?> Honneur</strong></div>
                                        <div><i class="fa-solid fa-scroll text-primary me-1"></i>Savoirs &amp; Alliances : <strong>Éclat suprême</strong></div>
                                        <div><i class="fa-solid fa-stopwatch text-info me-1"></i>Durée : <strong>12 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $imperialCost || $isAdmin) ? 'text-warning' : 'text-danger' ?>"><?= number_format($imperialCost) ?> Saké <i class="fa-solid fa-wine-bottle text-danger"></i></strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-purple w-100 text-white fw-bold btn-sm" style="background:#7c3aed;"
                                        onclick="submitTenshuFeast('imperial')"
                                        <?= (!$isAdmin && ($activeFeast || $tenshuLvl < 10 || $currentSake < $imperialCost)) ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-crown me-1"></i>Décréter le Banquet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($code === 'grain_mill' && $lvl > 0): ?>
            <?php
            $planetEngine->processCraftQueue((int)$planet['id']);
            $craftQueue = $planetEngine->getCraftQueue((int)$planet['id'], 'grain_mill');
            $activeCraft = !empty($craftQueue) ? $craftQueue[0] : null;
            $pendingCrafts = count($craftQueue) > 1 ? array_slice($craftQueue, 1) : [];

            require_once __DIR__ . '/../core/ImperialSealEngine.php';
            $sealEngine = new ImperialSealEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $maxCraftQueue = $isSealActive ? 4 : 1;
            $canEnqueue = $isAdmin ? true : (count($craftQueue) < $maxCraftQueue);
            $famineUpkeep = $planetEngine->getEliteUnitsUpkeep((int)$planet['id']);
            ?>
            <!-- ========================================================
                 ATELIER DE RAFFINAGE : MOUTURE DE FARINE & BRASSERIE DE SAKÉ
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #166534;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-success d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-wine-bottle text-success"></i></span> Minoterie &amp; Brasserie Féodale (Sakagura)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Raffinez le riz brut récolté dans vos rizières pour élaborer de la farine fine et du saké traditionnel d'exception.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-success text-white fw-bold shadow-sm" title="Étage / Niveau actuel de la meunerie">
                            <i class="fa-solid fa-landmark text-white me-1"></i>Niveau <?= $lvl ?>
                        </span>
                        <?php if ($lvl < 5): ?>
                            <span class="badge bg-secondary text-white fw-bold">
                                <i class="fa-solid fa-lock text-muted me-1"></i>Farine : Niv. 5 requis (<?= $lvl ?>/5)
                            </span>
                        <?php elseif ($lvl < 10): ?>
                            <span class="badge bg-success-lt fw-bold">
                                <i class="fa-solid fa-check text-success me-1"></i>Farine Débloquée
                            </span>
                            <span class="badge bg-secondary-lt fw-bold">
                                <i class="fa-solid fa-lock text-muted me-1"></i>Saké au Niv. 10 (<?= $lvl ?>/10)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-lt fw-bold">
                                <i class="fa-solid fa-check text-success me-1"></i>Farine &amp; Saké Débloqués
                            </span>
                        <?php endif; ?>
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm" title="File de tâches automatique active grâce au Sceau Impérial">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Sceau Impérial : File de raffinage (<?= count($craftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <a href="?page=privilege" class="badge bg-secondary-lt fw-bold text-decoration-none" title="Décrétez le Sceau Impérial pour débloquer jusqu'à 4 commandes en file continue !">
                                <i class="fa-solid fa-torii-gate text-secondary me-1"></i>File Simple : <?= count($craftQueue) ?>/1 (<i class="fa-solid fa-crown text-warning"></i> Sceau : File x4)
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-success-lt fw-bold">
                            Rendement : +<?= (int)($lvl * 2) ?>%
                        </span>
                        <span class="badge bg-info-lt fw-bold">
                            Vitesse : +<?= (int)($lvl * 15) ?>%
                        </span>
                        <?php if ($famineUpkeep['famine_enabled']): ?>
                            <span class="badge bg-danger-lt fw-bold" title="Rations horaires requises pour maintenir vos troupes d'élite">
                                <i class="fa-solid fa-bowl-rice text-warning me-1"></i>Rations Élite : <?= $famineUpkeep['flour_consumption_per_hour'] ?> farine/h
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($famineUpkeep['famine_enabled'] && !empty($planet['famine_active'])): ?>
                    <div class="alert alert-danger mb-3 p-3 border-danger shadow-sm" style="border-left: 5px solid #dc2626; background: #fef2f2;">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-1"><i class="fa-solid fa-skull text-danger"></i></span>
                            <div>
                                <h4 class="m-0 fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Famine Déclarée : Rations Épuisées !</h4>
                                <div class="text-dark small mt-1">
                                    Vos réserves de farine de riz sont tombées à zéro. Vos <strong><?= $famineUpkeep['elite_units_count'] ?> soldats d'élite</strong> subissent actuellement <strong>-<?= $famineUpkeep['famine_rate'] ?>% de pertes par heure</strong> (morts de faim et désertions).
                                    Lancez sans attendre une mouture de farine pour rétablir les vivres !
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($famineUpkeep['famine_enabled'] && $famineUpkeep['elite_units_count'] > 0): ?>
                    <div class="alert alert-light mb-3 p-2 border small d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <span><i class="fa-solid fa-shield-halved text-primary"></i></span>
                            <span><strong>Régiments d'élite en garnison :</strong> <?= $famineUpkeep['elite_units_count'] ?> guerriers (consommation : <strong><?= $famineUpkeep['flour_consumption_per_hour'] ?> farine <i class="fa-solid fa-bowl-rice text-warning"></i> / heure</strong>).</span>
                        </div>
                        <span class="badge bg-warning text-dark">Péril de Famine Actif</span>
                    </div>
                    <?php endif; ?>

                    <!-- Lot 1 : En cours d'élaboration -->
                    <?php if ($activeCraft): ?>
                    <div class="alert alert-primary mb-3 p-3 border-primary shadow-sm" style="border-left: 5px solid #206bc4; background: #f0f7ff;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1"><?= $activeCraft['product'] === 'sake' ? '<i class="fa-solid fa-wine-bottle text-danger"></i>' : '<i class="fa-solid fa-bowl-rice text-warning"></i>' ?></span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 fw-bold text-dark">
                                            <?= $activeCraft['product'] === 'sake' ? 'Brassage de Saké en cuve...' : 'Mouture de Farine de Riz en cours...' ?>
                                        </h4>
                                        <span class="badge bg-primary text-white">Lot Actif (#1)</span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Production prévue : <strong class="text-primary">+<?= number_format((int)$activeCraft['produced_amount']) ?> <?= $activeCraft['product'] === 'sake' ? 'Saké <i class=\"fa-solid fa-wine-bottle text-danger\"></i>' : 'Farine <i class=\"fa-solid fa-bowl-rice text-warning\"></i>' ?></strong>
                                        &bull; Riz engagé : <strong><?= number_format((int)$activeCraft['rice_amount']) ?> <i class="fa-solid fa-wheat-awn text-warning"></i></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-1">
                                <span class="badge bg-primary text-white font-monospace fs-5 py-2 px-3 shadow-sm" data-countdown="<?= $activeCraft['finishes_at'] ?>">
                                    <i class="fa-solid fa-hourglass-half me-1"></i>En cours...
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelRiceCraft(<?= (int)$activeCraft['id'] ?>)">
                                    <i class="fa-solid fa-xmark me-1"></i>Annuler (Remboursement 80%)
                                </button>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px; background-color: #dbeafe;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar"
                                 style="width: <?= $activeCraft['progress'] ?>%;"
                                 aria-valuenow="<?= $activeCraft['progress'] ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 id="craftProgressBar">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lots Suivants en File d'Attente Séquentielle (Privilège Sceau Impérial) -->
                    <?php if (!empty($pendingCrafts)): ?>
                    <div class="card mb-3 border shadow-none" style="background:#fcfcfc;">
                        <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark d-flex align-items-center gap-2">
                                <span><i class="fa-solid fa-clipboard-list text-primary"></i></span> Lots en file d'attente automatique (<?= count($pendingCrafts) ?>)
                            </span>
                            <span class="badge bg-warning-lt text-warning fw-bold"><i class="fa-solid fa-crown text-warning me-1"></i>Privilège du Shōgun</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingCrafts as $qIdx => $pCraft): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-secondary-lt fw-bold">#<?= $qIdx + 2 ?></span>
                                    <span class="fs-2"><?= $pCraft['product'] === 'sake' ? '<i class="fa-solid fa-wine-bottle text-danger"></i>' : '<i class="fa-solid fa-bowl-rice text-warning"></i>' ?></span>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= $pCraft['product'] === 'sake' ? 'Brassage de Saké Impérial' : 'Mouture de Farine de Riz' ?>
                                        </div>
                                        <div class="text-muted small">
                                            Rendement : <strong class="text-success">+<?= number_format((int)$pCraft['produced_amount']) ?></strong> &bull; Riz réservé : <strong><?= number_format((int)$pCraft['rice_amount']) ?> <i class="fa-solid fa-wheat-awn text-warning"></i></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end d-flex align-items-center gap-3">
                                    <div class="small">
                                        <div class="text-muted">Démarrage estimé :</div>
                                        <div class="fw-bold font-monospace text-primary">
                                            dans ~<?= gmdate('i\m s\s', (int)$pCraft['starts_in']) ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelRiceCraft(<?= (int)$pCraft['id'] ?>)" title="Annuler ce lot en attente">
                                        <i class="fa-solid fa-xmark me-1"></i>Annuler
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Résumé des stocks actuels -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Brut Disponible</div>
                                <div class="fs-4 fw-bold text-success" id="craft_avail_rice"><?= number_format((int)$planet['deuterium']) ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-bowl-rice text-warning me-1"></i>Farine en Réserve</div>
                                <div class="fs-4 fw-bold text-dark" id="craft_avail_flour"><?= number_format((int)($planet['rice_flour'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['rice_flour_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-wine-bottle text-danger me-1"></i>Saké en Réserve</div>
                                <div class="fs-4 fw-bold text-warning" id="craft_avail_sake"><?= number_format((int)($planet['sake'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['sake_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deux colonnes d'ateliers : Farine (Niveau 5) & Saké (Niveau 10) -->
                    <div class="row g-3">
                        <!-- Atelier 1 : Farine de Riz (Niveau 5) -->
                        <div class="col-md-6">
                            <?php if ($lvl < 5): ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light-lt border-dashed">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-muted">
                                            <i class="fa-solid fa-lock me-1"></i>Mouture de Farine de Riz
                                        </h4>
                                        <span class="badge bg-secondary-lt">Niv. 5 requis</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        L'installation des meules hydrauliques et le tamisage fin du grain de riz (Komeko) nécessitent un atelier meunier opérationnel.
                                        Améliorez votre <strong>Meunerie &amp; Brasserie au Niveau 5</strong> pour débloquer la production de farine de riz.
                                    </p>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progression de la Meunerie</span>
                                        <strong><?= $lvl ?> / 5</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= min(100, ($lvl / 5) * 100) ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-bowl-rice text-warning"></i></span> Mouture de Farine
                                        </h4>
                                        <span class="badge bg-secondary-lt">5 Riz &rarr; 1 Farine</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Broyage des grains de riz en farine fine (Komeko) pour les vivres et rations du domaine.
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark small mb-1">Quantité de Riz à moudre :</label>
                                        <div class="input-group">
                                            <input type="number" id="rice_amount_flour" min="5" step="5" value="100" class="form-control fw-bold text-center" oninput="calcFlourPreview()" <?= !$canEnqueue ? 'disabled' : '' ?>>
                                            <span class="input-group-text small">Riz</span>
                                        </div>
                                    </div>

                                    <!-- Boutons raccourcis -->
                                    <div class="btn-group btn-group-sm w-100 mb-3">
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceFlourAmount(50)" <?= !$canEnqueue ? 'disabled' : '' ?>>50</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceFlourAmount(200)" <?= !$canEnqueue ? 'disabled' : '' ?>>200</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceFlourAmount(1000)" <?= !$canEnqueue ? 'disabled' : '' ?>>1 000</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceFlourAmount('max')" <?= !$canEnqueue ? 'disabled' : '' ?>>Max</button>
                                    </div>

                                    <div class="alert alert-info py-2 px-3 small mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Production estimée :</span>
                                            <strong class="text-primary fs-5" id="preview_flour_gain">+20 <i class="fa-solid fa-bowl-rice text-warning"></i></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de mouture :</span>
                                            <span class="fw-bold font-monospace" id="preview_flour_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$canEnqueue): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                    <i class="fa-solid fa-hourglass-half text-warning me-1"></i>File saturée (<?= count($craftQueue) ?>/<?= $maxCraftQueue ?> lots)
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-success w-100 fw-bold" onclick="submitRiceCraft('rice_flour')">
                                    <?= empty($craftQueue) ? '<i class="fa-solid fa-bowl-rice me-1"></i>Lancer la Mouture de Farine' : '<i class="fa-solid fa-plus me-1"></i>Ajouter à la File (#'.(count($craftQueue)+1).'/'.$maxCraftQueue.')' ?>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Atelier 2 : Saké Féodal (Niveau 10) -->
                        <div class="col-md-6">
                            <?php if ($lvl < 10): ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light-lt border-dashed">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-muted">
                                            <i class="fa-solid fa-lock me-1"></i>Cuves de Saké (Sakagura)
                                        </h4>
                                        <span class="badge bg-secondary-lt">Niv. 10 requis</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        La fermentation du koji et l'élevage du saké en grands fûts de cèdre exigent des cuves spécialisées et des maîtres brasseurs (Toji).
                                        Améliorez votre <strong>Meunerie &amp; Brasserie au Niveau 10</strong> pour débloquer le brassage du saké.
                                    </p>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progression de la Brasserie</span>
                                        <strong><?= $lvl ?> / 10</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= min(100, ($lvl / 10) * 100) ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-wine-bottle text-danger"></i></span> Cuves de Saké (Sakagura)
                                        </h4>
                                        <span class="badge bg-warning-lt text-warning">10 Riz &rarr; 1 Saké</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Fermentation traditionnelle en fûts de cèdre. Breuvage d'honneur pour réceptions et banquets.
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark small mb-1">Quantité de Riz à brasser :</label>
                                        <div class="input-group">
                                            <input type="number" id="rice_amount_sake" min="10" step="10" value="100" class="form-control fw-bold text-center" oninput="calcSakePreview()" <?= !$canEnqueue ? 'disabled' : '' ?>>
                                            <span class="input-group-text small">Riz</span>
                                        </div>
                                    </div>

                                    <!-- Boutons raccourcis -->
                                    <div class="btn-group btn-group-sm w-100 mb-3">
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceSakeAmount(50)" <?= !$canEnqueue ? 'disabled' : '' ?>>50</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceSakeAmount(200)" <?= !$canEnqueue ? 'disabled' : '' ?>>200</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceSakeAmount(1000)" <?= !$canEnqueue ? 'disabled' : '' ?>>1 000</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setRiceSakeAmount('max')" <?= !$canEnqueue ? 'disabled' : '' ?>>Max</button>
                                    </div>

                                    <div class="alert alert-warning py-2 px-3 small mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Production estimée :</span>
                                            <strong class="text-warning fs-5" id="preview_sake_gain">+10 <i class="fa-solid fa-wine-bottle text-danger"></i></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de fermentation :</span>
                                            <span class="fw-bold font-monospace" id="preview_sake_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$canEnqueue): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                    <i class="fa-solid fa-hourglass-half text-warning me-1"></i>File saturée (<?= count($craftQueue) ?>/<?= $maxCraftQueue ?> lots)
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-warning w-100 fw-bold text-dark" onclick="submitRiceCraft('sake')">
                                    <?= empty($craftQueue) ? '<i class="fa-solid fa-wine-bottle me-1"></i>Déclencher le Brassage du Saké' : '<i class="fa-solid fa-plus me-1"></i>Ajouter à la File (#'.(count($craftQueue)+1).'/'.$maxCraftQueue.')' ?>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($code === 'sawmill' && $lvl > 0): ?>
            <?php
            $planetEngine->processCraftQueue((int)$planet['id']);
            $woodCraftQueue = $planetEngine->getCraftQueue((int)$planet['id'], 'sawmill');
            $activeWoodCraft = !empty($woodCraftQueue) ? $woodCraftQueue[0] : null;
            $pendingWoodCrafts = count($woodCraftQueue) > 1 ? array_slice($woodCraftQueue, 1) : [];

            require_once __DIR__ . '/../core/ImperialSealEngine.php';
            $sealEngine = new ImperialSealEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $maxWoodCraftQueue = $isSealActive ? 4 : 1;
            $canEnqueueWood = $isAdmin || (count($woodCraftQueue) < $maxWoodCraftQueue);
            ?>
            <!-- ========================================================
                 ATELIER DE CHARPENTERIE : FAÇONNAGE DE POUTRES EN BOIS
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #d97706;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-warning-emphasis d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-hammer text-warning"></i></span> Atelier de Charpenterie &amp; Façonnage de Poutres (Kizukuri)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Façonnez le Bois de Cèdre brut en poutres maîtresses et madriers d'exception pour vos chantiers monumentaux et fortifications.
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap ms-auto">
                        <span class="badge bg-warning text-dark fw-bold shadow-sm" title="Étage / Niveau actuel du bâtiment">
                            <i class="fa-solid fa-landmark text-primary me-1"></i>Étage <?= $lvl ?> (Niveau <?= $lvl ?>)
                        </span>
                        <?php if ($lvl < 10 && !$isAdmin): ?>
                            <span class="badge bg-secondary text-white fw-bold">
                                <i class="fa-solid fa-lock text-muted me-1"></i>Poutres : Niv. 10 (<?= $lvl ?>/10)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-lt fw-bold">
                                <i class="fa-solid fa-check text-success me-1"></i>Poutres Actives
                            </span>
                        <?php endif; ?>
                        <?php if ($lvl < 15 && !$isAdmin): ?>
                            <span class="badge bg-secondary text-white fw-bold">
                                <i class="fa-solid fa-lock text-muted me-1"></i>Charbon : Niv. 15 (<?= $lvl ?>/15)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-dark text-white fw-bold">
                                <i class="fa-solid fa-fire text-warning me-1"></i>Charbon Actif
                            </span>
                        <?php endif; ?>
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm" title="File de tâches automatique active grâce au Sceau Impérial">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Sceau Impérial : File de charpente (<?= count($woodCraftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <a href="?page=privilege" class="badge bg-secondary-lt fw-bold text-decoration-none" title="Décrétez le Sceau Impérial pour débloquer jusqu'à 4 commandes en file continue !">
                                <i class="fa-solid fa-torii-gate text-secondary me-1"></i>File Simple : <?= count($woodCraftQueue) ?>/1 (<i class="fa-solid fa-crown text-warning"></i> Sceau : File x4)
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-warning-lt fw-bold">
                            Rendement : +<?= (int)($lvl * 2) ?>%
                        </span>
                        <span class="badge bg-info-lt fw-bold">
                            Vitesse : +<?= (int)($lvl * 15) ?>%
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Lot 1 : En cours d'élaboration -->
                    <?php if ($activeWoodCraft): ?>
                    <?php
                    $isActiveCharcoal = ($activeWoodCraft['product'] === 'charcoal');
                    $activeProdTitle = $isActiveCharcoal ? 'Production de Charbon de bois en cours...' : 'Façonnage de Poutres en bois en cours...';
                    $activeProdIcon = $isActiveCharcoal ? '<i class="fa-solid fa-fire text-danger"></i>' : '<i class="fa-solid fa-tree text-success"></i>';
                    $activeProdLabel = $isActiveCharcoal ? 'Charbon de bois 🪨' : 'Poutres en bois 🪵';
                    ?>
                    <div class="alert alert-warning mb-3 p-3 border-warning shadow-sm" style="border-left: 5px solid #d97706; background: #fffbeb;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1"><?= $activeProdIcon ?></span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 fw-bold text-dark">
                                            <?= $activeProdTitle ?>
                                        </h4>
                                        <span class="badge bg-warning text-dark fw-bold">Lot Actif (#1)</span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Production prévue : <strong class="text-warning-emphasis">+<?= number_format((int)$activeWoodCraft['produced_amount']) ?> <?= $activeProdLabel ?></strong>
                                        &bull; Bois engagé : <strong><?= number_format((int)($activeWoodCraft['cost_amount'] ?: $activeWoodCraft['rice_amount'])) ?> <i class="fa-solid fa-tree text-success"></i></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-1">
                                <span class="badge bg-warning text-dark fw-bold font-monospace fs-5 py-2 px-3 shadow-sm" data-countdown="<?= $activeWoodCraft['finishes_at'] ?>">
                                    <i class="fa-solid fa-hourglass-half me-1"></i>En cours...
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelWoodCraft(<?= (int)$activeWoodCraft['id'] ?>)">
                                    <i class="fa-solid fa-xmark me-1"></i>Annuler (Remboursement 80%)
                                </button>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px; background-color: #fef3c7;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                 role="progressbar"
                                 style="width: <?= $activeWoodCraft['progress'] ?>%;"
                                 aria-valuenow="<?= $activeWoodCraft['progress'] ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 id="woodCraftProgressBar">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lots Suivants en File d'Attente Séquentielle (Privilège Sceau Impérial) -->
                    <?php if (!empty($pendingWoodCrafts)): ?>
                    <div class="card mb-3 border shadow-none" style="background:#fcfcfc;">
                        <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark d-flex align-items-center gap-2">
                                <span><i class="fa-solid fa-clipboard-list text-primary"></i></span> Lots en file d'attente automatique (<?= count($pendingWoodCrafts) ?>)
                            </span>
                            <span class="badge bg-warning-lt text-warning fw-bold"><i class="fa-solid fa-crown text-warning me-1"></i>Privilège du Shōgun</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingWoodCrafts as $qIdx => $pCraft): ?>
                            <?php
                            $isPendingCharcoal = ($pCraft['product'] === 'charcoal');
                            $pendingTitle = $isPendingCharcoal ? 'Production de Charbon de bois' : 'Façonnage de Poutres en bois';
                            $pendingIcon = $isPendingCharcoal ? '<i class="fa-solid fa-fire text-danger"></i>' : '<i class="fa-solid fa-tree text-success"></i>';
                            $pendingLabel = $isPendingCharcoal ? 'Charbon 🪨' : 'Poutres 🪵';
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-secondary-lt fw-bold">#<?= $qIdx + 2 ?></span>
                                    <span class="fs-2"><?= $pendingIcon ?></span>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= $pendingTitle ?>
                                        </div>
                                        <div class="text-muted small">
                                            Rendement : <strong class="text-success">+<?= number_format((int)$pCraft['produced_amount']) ?> <?= $pendingLabel ?></strong> &bull; Bois réservé : <strong><?= number_format((int)($pCraft['cost_amount'] ?: $pCraft['rice_amount'])) ?> <i class="fa-solid fa-tree text-success"></i></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end d-flex align-items-center gap-3">
                                    <div class="small">
                                        <div class="text-muted">Démarrage estimé :</div>
                                        <div class="fw-bold font-monospace text-primary">
                                            dans ~<?= gmdate('i\m s\s', (int)$pCraft['starts_in']) ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelWoodCraft(<?= (int)$pCraft['id'] ?>)" title="Annuler ce lot en attente">
                                        <i class="fa-solid fa-xmark me-1"></i>Annuler
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Résumé des stocks actuels -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-tree text-success me-1"></i>Bois Brut Disponible</div>
                                <div class="fs-4 fw-bold text-warning" id="craft_avail_metal"><?= number_format((int)$planet['metal']) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)$planet['metal_max']) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-cubes text-teal me-1"></i>Poutres en Réserve</div>
                                <div class="fs-4 fw-bold text-dark" id="craft_avail_beams"><?= number_format((int)($planet['wooden_beams'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['wooden_beams_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-fire text-danger me-1"></i>Charbon en Réserve</div>
                                <div class="fs-4 fw-bold text-dark" id="craft_avail_charcoal"><?= number_format((int)($planet['charcoal'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['charcoal_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deux ateliers : Poutres (Niv 10) & Charbon de bois (Niv 15) -->
                    <div class="row g-3">
                        <!-- Atelier 1 : Façonnage de Poutres en bois (Niveau 10) -->
                        <div class="col-md-6">
                            <?php if ($lvl < 10 && !$isAdmin): ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light-lt border-dashed">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-muted">
                                            <i class="fa-solid fa-lock me-1"></i>Poutres en bois
                                        </h4>
                                        <span class="badge bg-secondary-lt">Niv. 10 requis</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        L'équarrissage et le rabotage des troncs de cèdre en poutres massives nécessitent un outillage et un savoir-faire avancé.
                                        Améliorez votre <strong>Atelier de Charpenterie au Niveau 10</strong> pour débloquer la production de poutres.
                                    </p>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progression de l'atelier</span>
                                        <strong><?= $lvl ?> / 10</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= min(100, ($lvl / 10) * 100) ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-hammer text-warning"></i></span> Poutres en bois
                                        </h4>
                                        <span class="badge bg-warning-lt text-warning">10 Bois &rarr; 1 Poutre</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Équarrissage et rabotage des troncs de cèdre en poutres solides pour les édifices et structures défensives.
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark small mb-1">Quantité de Bois de Cèdre à façonner :</label>
                                        <div class="input-group mb-2">
                                            <input type="number" id="wood_amount_beams" min="10" step="10" value="100" class="form-control fw-bold text-center" oninput="calcBeamsPreview()" <?= !$canEnqueueWood ? 'disabled' : '' ?>>
                                            <span class="input-group-text small">Bois <i class="fa-solid fa-tree text-success"></i></span>
                                        </div>
                                        <div class="btn-group btn-group-sm w-100">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(50)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>50</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(200)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>200</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(1000)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>1 000</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount('max')" <?= !$canEnqueueWood ? 'disabled' : '' ?>>Max</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="alert alert-warning py-2 px-3 small mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Production estimée :</span>
                                            <strong class="text-warning-emphasis fs-5" id="preview_beams_gain">+10 <i class="fa-solid fa-tree text-success"></i></strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de façonnage :</span>
                                            <span class="fw-bold font-monospace" id="preview_beams_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                        </div>
                                    </div>
                                    <?php if (!$canEnqueueWood): ?>
                                    <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                        <i class="fa-solid fa-hourglass-half text-warning me-1"></i>File saturée (<?= count($woodCraftQueue) ?>/<?= $maxWoodCraftQueue ?>)
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-warning w-100 fw-bold text-dark" onclick="submitWoodCraft('wooden_beams')">
                                        <?= empty($woodCraftQueue) ? '<i class="fa-solid fa-hammer me-1"></i>Lancer le Façonnage' : '<i class="fa-solid fa-plus me-1"></i>Ajouter Poutres (#'.(count($woodCraftQueue)+1).'/'.$maxWoodCraftQueue.')' ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Atelier 2 : Production de Charbon de bois (Niveau 15) -->
                        <div class="col-md-6">
                            <?php if ($lvl < 15 && !$isAdmin): ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light-lt border-dashed">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-muted">
                                            <i class="fa-solid fa-lock me-1"></i>Charbon de bois (Binchōtan)
                                        </h4>
                                        <span class="badge bg-secondary-lt">Niv. 15 requis</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        La carbonisation en meules étanches et la pyrolyse du bois de cèdre exigent un atelier hautement perfectionné.
                                        Améliorez votre <strong>Atelier de Charpenterie au Niveau 15</strong> pour débloquer la production de charbon.
                                    </p>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progression de l'atelier</span>
                                        <strong><?= $lvl ?> / 15</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-dark" style="width: <?= min(100, ($lvl / 15) * 100) ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span><i class="fa-solid fa-fire text-danger"></i></span> Charbon de bois (Sumi)
                                        </h4>
                                        <span class="badge bg-dark text-white">1 Bois &rarr; 1 Charbon</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Carbonisation à haute température du bois de cèdre pour produire un charbon épuré de qualité supérieure.
                                    </p>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark small mb-1">Quantité de Bois de Cèdre à carboniser :</label>
                                        <div class="input-group mb-2">
                                            <input type="number" id="wood_amount_charcoal" min="1" step="1" value="100" class="form-control fw-bold text-center" oninput="calcCharcoalPreview()" <?= !$canEnqueueWood ? 'disabled' : '' ?>>
                                            <span class="input-group-text small">Bois <i class="fa-solid fa-tree text-success"></i></span>
                                        </div>
                                        <div class="btn-group btn-group-sm w-100">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodCharcoalAmount(50)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>50</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodCharcoalAmount(200)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>200</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodCharcoalAmount(1000)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>1 000</button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="setWoodCharcoalAmount('max')" <?= !$canEnqueueWood ? 'disabled' : '' ?>>Max</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="alert alert-dark py-2 px-3 small mb-2" style="background-color: #f1f5f9; border-color: #cbd5e1;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-dark">Production estimée :</span>
                                            <strong class="text-dark fs-5" id="preview_charcoal_gain">+100 🪨</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de carbonisation :</span>
                                            <span class="fw-bold font-monospace" id="preview_charcoal_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                        </div>
                                    </div>
                                    <?php if (!$canEnqueueWood): ?>
                                    <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                        <i class="fa-solid fa-hourglass-half text-warning me-1"></i>File saturée (<?= count($woodCraftQueue) ?>/<?= $maxWoodCraftQueue ?>)
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-dark w-100 fw-bold text-white" onclick="submitWoodCraft('charcoal')">
                                        <?= empty($woodCraftQueue) ? '<i class="fa-solid fa-fire text-warning me-1"></i>Produire du Charbon' : '<i class="fa-solid fa-plus text-warning me-1"></i>Ajouter Charbon (#'.(count($woodCraftQueue)+1).'/'.$maxWoodCraftQueue.')' ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($code === 'stonemason' && $lvl > 0): ?>
            <?php
            $planetEngine->processCraftQueue((int)$planet['id']);
            $mineralCraftQueue = $planetEngine->getCraftQueue((int)$planet['id'], 'stonemason');
            $activeMineralCraft = !empty($mineralCraftQueue) ? $mineralCraftQueue[0] : null;
            $pendingMineralCrafts = count($mineralCraftQueue) > 1 ? array_slice($mineralCraftQueue, 1) : [];

            require_once __DIR__ . '/../core/ImperialSealEngine.php';
            $sealEngine = new ImperialSealEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $maxMineralCraftQueue = $isSealActive ? 4 : 1;
            $canEnqueueMineral = $isAdmin || (count($mineralCraftQueue) < $maxMineralCraftQueue);
            ?>
            <!-- ========================================================
                 ATELIER DU TAILLEUR DE PIERRE : RAFFINAGE MINÉRALIER
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #64748b;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-secondary-emphasis d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-gem text-secondary"></i></span> Atelier Minéralier &amp; Concassage (Ishikiri)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Raffinez vos blocs de granit et poches d'argile en minerais précieux : <strong>Soufre Volcanique</strong>, <strong>Nitrate de Potasse</strong> et <strong>Sable de Fer (Satetsu 砂鉄)</strong> pour vos manufactures de poudre et forges impériales.
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap ms-auto">
                        <span class="badge bg-secondary text-white fw-bold shadow-sm">
                            <i class="fa-solid fa-landmark me-1"></i>Niveau <?= $lvl ?>
                        </span>
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Sceau Impérial (<?= count($mineralCraftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt fw-bold">
                                File Simple : <?= count($mineralCraftQueue) ?>/1
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-warning-lt fw-bold">Rendement : +<?= (int)($lvl * 2) ?>%</span>
                        <span class="badge bg-info-lt fw-bold">Vitesse : +<?= (int)($lvl * 15) ?>%</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Lot actif -->
                    <?php if ($activeMineralCraft): ?>
                    <?php
                    $mProd = $activeMineralCraft['product'];
                    $mIcon = match($mProd) {
                        'sulfur' => '🌋',
                        'nitrate' => '🧪',
                        'iron_sand' => '💎',
                        default => '⛏️'
                    };
                    $mTitle = match($mProd) {
                        'sulfur' => 'Synthèse de Soufre Volcanique en cours...',
                        'nitrate' => 'Cristallisation de Salpêtre (Nitrate) en cours...',
                        'iron_sand' => 'Broyage & Lavage de Sable de Fer en cours...',
                        default => 'Raffinage Minéralier en cours...'
                    };
                    ?>
                    <div class="alert alert-secondary mb-3 p-3 border-secondary shadow-sm" style="border-left: 5px solid #64748b; background: #f8fafc;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1"><?= $mIcon ?></span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 fw-bold text-dark"><?= $mTitle ?></h4>
                                        <span class="badge bg-secondary text-white fw-bold">Lot Actif (#1)</span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Lot : <strong>+<?= number_format((int)$activeMineralCraft['produced_amount']) ?></strong> minerais &bull;
                                        Fin estimée : <strong><?= date('H:i:s', (int)$activeMineralCraft['finishes_at']) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelMineralCraft(<?= (int)$activeMineralCraft['id'] ?>)">
                                    <i class="fa-solid fa-xmark me-1"></i>Interrompre
                                </button>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" id="mineralCraftProgressBar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Résumé des stocks minéraux -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-volcano text-warning me-1"></i>Soufre en Réserve</div>
                                <div class="fs-4 fw-bold text-warning"><?= number_format((int)($planet['sulfur'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['sulfur_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-flask-vial text-info me-1"></i>Nitrate en Réserve</div>
                                <div class="fs-4 fw-bold text-info"><?= number_format((int)($planet['nitrate'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['nitrate_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small"><i class="fa-solid fa-gem text-secondary me-1"></i>Sable de Fer (Satetsu)</div>
                                <div class="fs-4 fw-bold text-secondary"><?= number_format((int)($planet['iron_sand'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['iron_sand_max'] ?? 15000)) ?> max</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Recettes d'Atelier : Soufre, Nitrate, Sable de fer -->
                    <div class="row g-3">
                        <!-- Recette 1 : Soufre -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>🌋</span> Soufre Volcanique
                                        </h4>
                                        <span class="badge bg-warning-lt">4 Granit &rarr; 1 Soufre</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Concassage fin de roches granitiques et pyrites pour en extraire le soufre.
                                    </p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-secondary mb-1">Granit à concasser :</label>
                                        <div class="input-group">
                                            <input type="number" id="stone_amount_sulfur" class="form-control font-monospace" min="4" step="4" value="40" oninput="calcSulfurPreview()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setMineralAmount('sulfur', 'max')">Max</button>
                                        </div>
                                    </div>
                                    <div class="bg-white p-2 rounded border mb-3 small d-flex justify-content-between align-items-center">
                                        <span>Rendement : <strong id="preview_sulfur_gain" class="text-warning">+10 🌋</strong></span>
                                        <span id="preview_sulfur_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-warning text-dark w-100 fw-bold" onclick="submitMineralCraft('sulfur')" <?= !$canEnqueueMineral ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-fire text-dark me-1"></i>Raffiner du Soufre
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Recette 2 : Nitrate -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>🧪</span> Nitrate (Salpêtre)
                                        </h4>
                                        <span class="badge bg-info-lt">3 Argile &rarr; 1 Nitrate</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Lixiviation et filtration d'argiles salpêtrées pour cristalliser le nitrate de potasse.
                                    </p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-secondary mb-1">Argile à lixivier :</label>
                                        <div class="input-group">
                                            <input type="number" id="clay_amount_nitrate" class="form-control font-monospace" min="3" step="3" value="30" oninput="calcNitratePreview()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setMineralAmount('nitrate', 'max')">Max</button>
                                        </div>
                                    </div>
                                    <div class="bg-white p-2 rounded border mb-3 small d-flex justify-content-between align-items-center">
                                        <span>Rendement : <strong id="preview_nitrate_gain" class="text-info">+10 🧪</strong></span>
                                        <span id="preview_nitrate_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-info text-white w-100 fw-bold" onclick="submitMineralCraft('nitrate')" <?= !$canEnqueueMineral ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-flask-vial me-1"></i>Raffiner du Nitrate
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Recette 3 : Sable de Fer -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>💎</span> Sable de Fer (Satetsu)
                                        </h4>
                                        <span class="badge bg-secondary-lt">5 Granit &rarr; 1 Satetsu</span>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Séparation par gravitation des sables noirs ferrugineux issus de roches magmatiques.
                                    </p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-secondary mb-1">Granit à broyer :</label>
                                        <div class="input-group">
                                            <input type="number" id="stone_amount_ironsand" class="form-control font-monospace" min="5" step="5" value="50" oninput="calcIronSandPreview()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="setMineralAmount('iron_sand', 'max')">Max</button>
                                        </div>
                                    </div>
                                    <div class="bg-white p-2 rounded border mb-3 small d-flex justify-content-between align-items-center">
                                        <span>Rendement : <strong id="preview_ironsand_gain" class="text-secondary">+10 💎</strong></span>
                                        <span id="preview_ironsand_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary text-white w-100 fw-bold" onclick="submitMineralCraft('iron_sand')" <?= !$canEnqueueMineral ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-gem me-1"></i>Raffiner du Satetsu
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($code === 'blacksmith' && $lvl > 0): ?>
            <?php
            $planetEngine->processCraftQueue((int)$planet['id']);
            $forgeCraftQueue = $planetEngine->getCraftQueue((int)$planet['id'], 'blacksmith');
            $activeForgeCraft = !empty($forgeCraftQueue) ? $forgeCraftQueue[0] : null;
            $pendingForgeCrafts = count($forgeCraftQueue) > 1 ? array_slice($forgeCraftQueue, 1) : [];

            require_once __DIR__ . '/../core/ImperialSealEngine.php';
            $sealEngine = new ImperialSealEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $maxForgeCraftQueue = $isSealActive ? 4 : 1;
            $canEnqueueForge = $isAdmin || (count($forgeCraftQueue) < $maxForgeCraftQueue);

            $hasLevel15 = $isAdmin || ($lvl >= 15);
            $hasLevel20 = $isAdmin || ($lvl >= 20);

            $availableIronSand = (float)($planet['iron_sand'] ?? 0);
            $availableCharcoal = (float)($planet['charcoal'] ?? 0);
            $availableStone = (float)($planet['crystal'] ?? 0);
            $availableMetal = (float)($planet['metal'] ?? 0);
            $availableSake = (float)($planet['sake'] ?? 0);
            $maxMetalStorage = (int)($planet['metal_max'] ?? (15000 * pow(1.5, (int)($buildings['storage'] ?? 0))));
            ?>
            <!-- ========================================================
                 GRANDE FORGE DU TAMAHAGANE : FONTE & ARMÉES DE PRESTIGE
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #b91c1c;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-danger-emphasis d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-fire text-danger"></i></span> Grande Forge du Tamahagane (Kaji-ba)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Fourneaux de réduction Tatara, affinage de l'acier d'exception et confection des armements de prestige féodaux.
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap ms-auto">
                        <span class="badge bg-danger text-white fw-bold shadow-sm">
                            <i class="fa-solid fa-landmark me-1"></i>Niveau <?= $lvl ?>
                        </span>
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Sceau Impérial (<?= count($forgeCraftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary text-white fw-bold">
                                File Simple : <?= count($forgeCraftQueue) ?>/1
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-warning text-dark fw-bold">Rendement : +<?= (int)($lvl * 2) ?>%</span>
                        <span class="badge bg-info text-white fw-bold">Vitesse : +<?= (int)($lvl * 15) ?>%</span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Si niveau < 15 : Encart de verrouillage avec jauge de progression -->
                    <?php if (!$hasLevel15): ?>
                        <div class="alert alert-secondary p-3 mb-0 border-danger shadow-sm" style="background:#fff7ed; border-left: 5px solid #ea580c;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-1 text-danger"><i class="fa-solid fa-lock"></i></span>
                                    <div>
                                        <h4 class="m-0 fw-bold text-dark">Fourneaux Tatara Verrouillés</h4>
                                        <div class="text-secondary small mt-1">
                                            La réduction de l'acier et la fonte de Métal nécessitent une chaleur d'artisanat supérieur débloquée au <strong>Niveau 15</strong>.
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-danger text-white fw-bold px-3 py-2 fs-6">Niveau <?= $lvl ?> / 15</span>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 12px;">
                                <div class="progress-bar bg-danger progress-bar-striped" style="width: <?= min(100, round(($lvl / 15) * 100)) ?>%;">
                                    <?= round(($lvl / 15) * 100) ?>%
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="p-2 border rounded bg-white small">
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-fire text-danger me-1"></i>Niveau 15 : Fonte de Métal</div>
                                        <div class="text-muted mt-1">Débloque les 2 recettes de fonte (Tatara aux Sables de Fer &amp; Réduction Rustique) pour produire directement du Métal.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 border rounded bg-white small">
                                        <div class="fw-bold text-dark"><i class="fa-solid fa-crown text-warning me-1"></i>Niveau 20 : Armes &amp; Armées de Prestige</div>
                                        <div class="text-muted mt-1">Débloque la forge des Katanas de Maître (Honneur/Prestige + Katana Tamahagane) et la Garde d'Honneur Hatamoto.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Résumé des stocks pertinents -->
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6 col-md">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-secondary small"><i class="fa-solid fa-gem text-secondary me-1"></i>Sable de Fer (Satetsu)</div>
                                    <div class="fs-4 fw-bold text-dark"><?= number_format($availableIronSand) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['iron_sand_max'] ?? 15000)) ?> max</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-secondary small"><i class="fa-solid fa-cubes-stacked text-warning me-1"></i>Charbon de Bois</div>
                                    <div class="fs-4 fw-bold text-dark"><?= number_format($availableCharcoal) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['charcoal_max'] ?? 10000)) ?> max</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-secondary small"><i class="fa-solid fa-mountain text-primary me-1"></i>Pierre de Granit</div>
                                    <div class="fs-4 fw-bold text-primary"><?= number_format($availableStone) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['crystal_max'] ?? 15000)) ?> max</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-secondary small"><i class="fa-solid fa-wine-bottle text-success me-1"></i>Saké Impérial</div>
                                    <div class="fs-4 fw-bold text-success"><?= number_format($availableSake) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['sake_max'] ?? 10000)) ?> max</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md">
                                <div class="p-2 border rounded text-center bg-light border-danger">
                                    <div class="text-danger small fw-bold"><i class="fa-solid fa-shield-halved text-danger me-1"></i>Métal en Réserve</div>
                                    <div class="fs-4 fw-bold text-danger"><?= number_format($availableMetal) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format($maxMetalStorage) ?> max</div>
                                </div>
                            </div>
                        </div>

                        <!-- Lot actif de Forge -->
                        <?php if ($activeForgeCraft): ?>
                        <?php
                        $fProd = $activeForgeCraft['product'];
                        $fIcon = match($fProd) {
                            'metal' => '⚔️',
                            'metal_crude' => '🔥',
                            'prestige_weapons' => '🗡️',
                            'prestige_army' => '🎌',
                            default => '⚙️'
                        };
                        $fTitle = match($fProd) {
                            'metal' => 'Fonte de Métal au Fourneau Tatara en cours...',
                            'metal_crude' => 'Réduction Rustique de Métal en cours...',
                            'prestige_weapons' => 'Forge de Katanas de Maître &amp; Lames Sacrées en cours...',
                            'prestige_army' => 'Mobilisation de la Garde d\'Honneur en cours...',
                            default => 'Tâche de forge en cours...'
                        };
                        ?>
                        <div class="alert alert-secondary mb-3 p-3 border-danger shadow-sm" style="border-left: 5px solid #dc2626; background: #fff5f5;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-1"><?= $fIcon ?></span>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h4 class="m-0 fw-bold text-dark"><?= $fTitle ?></h4>
                                            <span class="badge bg-danger text-white fw-bold">Lot Actif (#1)</span>
                                        </div>
                                        <div class="text-secondary small mt-1">
                                            Quantité prévue : <strong>+<?= number_format((int)$activeForgeCraft['produced_amount']) ?></strong> &bull;
                                            Fin estimée : <strong><?= date('H:i:s', (int)$activeForgeCraft['finishes_at']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelForgeCraft(<?= (int)$activeForgeCraft['id'] ?>)">
                                        <i class="fa-solid fa-xmark me-1"></i>Interrompre
                                    </button>
                                </div>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" id="forgeCraftProgressBar" style="width: 0%;"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- File d'attente secondaire (Sceau Impérial) -->
                        <?php if (!empty($pendingForgeCrafts)): ?>
                        <div class="card mb-3 border-warning bg-light-subtle">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-warning-emphasis small">
                                    <i class="fa-solid fa-layer-group me-1"></i>File d'Attente de Forge (<?= count($pendingForgeCrafts) ?> en attente)
                                </span>
                                <span class="badge bg-warning text-dark fw-bold">Sceau Impérial Actif</span>
                            </div>
                            <div class="list-group list-group-flush small">
                                <?php foreach ($pendingForgeCrafts as $qIdx => $qItem): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-secondary text-white">#<?= $qIdx + 2 ?></span>
                                            <span class="fs-5"><?= $qItem['product_icon'] ?? '⚙️' ?></span>
                                            <div>
                                                <strong><?= htmlspecialchars($qItem['product_name'] ?? 'Commande') ?></strong>
                                                <span class="text-secondary ms-2">(+<?= number_format((int)$qItem['produced_amount']) ?>)</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted"><i class="fa-solid fa-clock me-1"></i>Début à <?= date('H:i:s', (int)$qItem['started_at']) ?></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="cancelForgeCraft(<?= (int)$qItem['id'] ?>)">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- SECTION PALIER 1 : FONTE DE MÉTAL (NIVEAU 15+) -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h4 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-fire-burner text-danger"></i>
                                    Fonte &amp; Réduction de Métal (Niveau 15+)
                                </h4>
                                <span class="badge bg-success text-white fw-bold">Débloqué</span>
                            </div>

                            <div class="row g-3">
                                <!-- Recette 1 : Fonte Tatara des Maîtres -->
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-white shadow-sm" style="border-top: 3px solid #dc2626 !important;">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h5 class="fw-bold m-0 text-dark">
                                                    <span class="fs-4 me-1">⚔️</span> Fourneau Tatara des Maîtres
                                                </h5>
                                                <span class="badge bg-danger text-white fw-bold">Recette Noble</span>
                                            </div>
                                            <p class="text-secondary small mb-3">
                                                Réduction traditionnelle par flux incandescent. Consomme du <strong>Sable de Fer (Satetsu)</strong> et du <strong>Charbon de Bois</strong> pour couler un métal pur d'une densité inégalée.
                                            </p>
                                            <div class="alert alert-light p-2 border mb-3 small">
                                                <div class="d-flex justify-content-between">
                                                    <span>Coût / Fournée :</span>
                                                    <strong>1 Sable 💎 + 2 Charbon 🪨</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-success mt-1">
                                                    <span>Rendement de base :</span>
                                                    <strong>40 Métal ⚔️</strong>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary d-flex justify-content-between">
                                                    <span>Nombre de fournées (Sable engagé) :</span>
                                                    <span class="text-muted">Stock : <?= number_format($availableIronSand) ?> 💎</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" id="forge_batches_metal" class="form-control" min="1" max="1000" value="5" oninput="calcForgeMetalPreview()">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal', 5)">+5</button>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal', 10)">+10</button>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal', 25)">+25</button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="setForgeAmount('metal', 'max')">Max</button>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-3 small p-2 bg-light rounded">
                                                <span>Production estimée : <strong id="preview_forge_metal_gain" class="text-danger">+200 ⚔️</strong></span>
                                                <span id="preview_forge_metal_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-danger text-white w-100 fw-bold" onclick="submitForgeCraft('metal')" <?= !$canEnqueueForge ? 'disabled' : '' ?>>
                                                <i class="fa-solid fa-fire me-1"></i>Allumer le Fourneau Tatara
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recette 2 : Réduction Rustique aux Scories -->
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-white shadow-sm" style="border-top: 3px solid #f97316 !important;">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h5 class="fw-bold m-0 text-dark">
                                                    <span class="fs-4 me-1">🔥</span> Réduction Rustique aux Scories
                                                </h5>
                                                <span class="badge bg-warning text-dark fw-bold">Recette Rustique</span>
                                            </div>
                                            <p class="text-secondary small mb-3">
                                                Extraction à haute température à partir de concassé de <strong>Pierre de Granit</strong> et de <strong>Charbon de Bois</strong>. Idéal lorsque le sable de fer vient à manquer.
                                            </p>
                                            <div class="alert alert-light p-2 border mb-3 small">
                                                <div class="d-flex justify-content-between">
                                                    <span>Coût / Fournée :</span>
                                                    <strong>10 Pierre 🪨 + 5 Charbon 🪨</strong>
                                                </div>
                                                <div class="d-flex justify-content-between text-success mt-1">
                                                    <span>Rendement de base :</span>
                                                    <strong>25 Métal ⚔️</strong>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary d-flex justify-content-between">
                                                    <span>Nombre de fournées :</span>
                                                    <span class="text-muted">Stock : <?= number_format($availableStone) ?> 🪨</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" id="forge_batches_crude" class="form-control" min="1" max="1000" value="5" oninput="calcForgeCrudePreview()">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal_crude', 5)">+5</button>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal_crude', 10)">+10</button>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="setForgeAmount('metal_crude', 25)">+25</button>
                                                    <button type="button" class="btn btn-outline-warning text-dark" onclick="setForgeAmount('metal_crude', 'max')">Max</button>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-3 small p-2 bg-light rounded">
                                                <span>Production estimée : <strong id="preview_forge_crude_gain" class="text-warning-emphasis">+125 ⚔️</strong></span>
                                                <span id="preview_forge_crude_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-warning text-dark w-100 fw-bold" onclick="submitForgeCraft('metal_crude')" <?= !$canEnqueueForge ? 'disabled' : '' ?>>
                                                <i class="fa-solid fa-temperature-arrow-up me-1"></i>Lancer la Réduction Rustique
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION PALIER 2 : ARMES & ARMÉES DE PRESTIGE (NIVEAU 20+) -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h4 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-crown text-warning"></i>
                                    Armes &amp; Armées de Prestige (Niveau 20+)
                                </h4>
                                <?php if ($hasLevel20): ?>
                                    <span class="badge bg-success text-white fw-bold">Débloqué</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary text-white fw-bold">Niveau <?= $lvl ?> / 20</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!$hasLevel20): ?>
                                <div class="alert alert-secondary p-3 mb-0 border-warning shadow-sm" style="background:#fffbeb; border-left: 5px solid #d97706;">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-1 text-warning"><i class="fa-solid fa-lock"></i></span>
                                            <div>
                                                <h4 class="m-0 fw-bold text-dark">Prestige Féodal Verrouillé</h4>
                                                <div class="text-secondary small mt-1">
                                                    La forge des <strong>Katanas de Maître</strong> et la mobilisation de la <strong>Garde d'Honneur au Tamahagane</strong> requièrent une Grande Forge élevée au <strong>Niveau 20</strong>.
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge bg-warning text-dark fw-bold px-3 py-2 fs-6">Niveau <?= $lvl ?> / 20</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning progress-bar-striped" style="width: <?= min(100, round(($lvl / 20) * 100)) ?>%;">
                                            <?= round(($lvl / 20) * 100) ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <!-- Option A : Katanas de Maître & Lames Sacrées -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-white shadow-sm" style="border-top: 3px solid #7c3aed !important;">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5 class="fw-bold m-0 text-dark">
                                                        <span class="fs-4 me-1">🗡️</span> Katanas de Maître &amp; Lames Sacrées
                                                    </h5>
                                                    <span class="badge bg-purple text-white fw-bold">Relique &amp; Honneur</span>
                                                </div>
                                                <p class="text-secondary small mb-3">
                                                    Forge d'apparat en acier plié 100 fois par les plus grands maîtres. Octroie un immense honneur impérial et forge l'arme légendaire <strong>Katana Forgé en Tamahagane</strong> pour votre héros.
                                                </p>
                                                <div class="alert alert-light p-2 border mb-3 small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Coût / Lot :</span>
                                                        <strong>500 Métal, 10 Sable 💎, 20 Charbon 🪨, 10 Saké 🍶</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-purple mt-1 fw-bold">
                                                        <span>Récompense :</span>
                                                        <span>+150 Honneur Féodal + Relique Katana Tamahagane</span>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-secondary">Nombre de commandes de prestige :</label>
                                                    <div class="input-group">
                                                        <input type="number" id="forge_batches_prestige" class="form-control" min="1" max="10" value="1" oninput="calcForgePrestigePreview()">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('forge_batches_prestige').value=1; calcForgePrestigePreview();">1 Lot</button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('forge_batches_prestige').value=2; calcForgePrestigePreview();">2 Lots</button>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-3 small p-2 bg-light rounded">
                                                    <span>Gain : <strong id="preview_forge_prestige_gain" class="text-purple">+150 Honneur Féodal 🌟</strong></span>
                                                    <span id="preview_forge_prestige_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                                </div>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-primary w-100 fw-bold" style="background:#7c3aed; border-color:#7c3aed;" onclick="submitForgeCraft('prestige_weapons')" <?= !$canEnqueueForge ? 'disabled' : '' ?>>
                                                    <i class="fa-solid fa-khanda me-1"></i>Forger les Lames de Prestige
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Option B : Garde d'Honneur au Tamahagane -->
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-white shadow-sm" style="border-top: 3px solid #b91c1c !important;">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5 class="fw-bold m-0 text-dark">
                                                        <span class="fs-4 me-1">🎌</span> Garde d'Honneur au Tamahagane
                                                    </h5>
                                                    <span class="badge bg-danger text-white fw-bold">Régiment d'Élite</span>
                                                </div>
                                                <p class="text-secondary small mb-3">
                                                    Mobilisation d'urgence d'une garde prétorienne lourdement cuirassée d'armures en tamahagane et lames trempées, rejoignant directement votre garnison sans saturer le Dojo.
                                                </p>
                                                <div class="alert alert-light p-2 border mb-3 small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Coût / Mobilisation :</span>
                                                        <strong>800 Métal, 400 Pierre 🪨, 15 Charbon 🪨, 15 Saké 🍶</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-danger mt-1 fw-bold">
                                                        <span>Troupes recrutées :</span>
                                                        <span>10 Samouraïs Katana + 2 Gardes Hatamoto</span>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-secondary">Nombre de mobilisations :</label>
                                                    <div class="input-group">
                                                        <input type="number" id="forge_batches_army" class="form-control" min="1" max="10" value="1" oninput="calcForgeArmyPreview()">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('forge_batches_army').value=1; calcForgeArmyPreview();">1 Compagnie</button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('forge_batches_army').value=2; calcForgeArmyPreview();">2 Compagnies</button>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-3 small p-2 bg-light rounded">
                                                    <span>Renforts : <strong id="preview_forge_army_gain" class="text-danger">+10 Samouraïs &amp; +2 Hatamoto 🎌</strong></span>
                                                    <span id="preview_forge_army_time"><i class="fa-solid fa-stopwatch text-info me-1"></i>--</span>
                                                </div>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-danger text-white w-100 fw-bold" onclick="submitForgeCraft('prestige_army')" <?= !$canEnqueueForge ? 'disabled' : '' ?>>
                                                    <i class="fa-solid fa-shield me-1"></i>Mobiliser la Garde d'Honneur
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($code === 'market' && $lvl > 0): ?>
            <?php
            // Données opérationnelles du Marché Féodal (Bazar Castral)
            $myVillages = $planetEngine->getUserPlanets((int)$user['id']);
            $otherVillages = array_filter($myVillages, function($v) use ($planet) {
                return (int)$v['id'] !== (int)$planet['id'];
            });

            $stmtShips = Database::getConnection()->prepare("SELECT ship_code, count FROM planet_ships WHERE planet_id = ?");
            $stmtShips->execute([(int)$planet['id']]);
            $marketShips = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);
            $availLight = (int)($marketShips['transporter_light'] ?? 0);
            $availHeavy = (int)($marketShips['transporter_heavy'] ?? 0);
            $totalCargoCapacity = ($availLight * 5000) + ($availHeavy * 25000);
            $merchantsCount = (int)($lvl * 2);

            require_once __DIR__ . '/../core/ImperialSealEngine.php';
            require_once __DIR__ . '/../core/MarketEngine.php';
            $sealEngine = new ImperialSealEngine();
            $marketEngine = new MarketEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $allTradeRoutes = $sealEngine->getTradeRoutes((int)$user['id']);
            $marketRoutes = array_filter($allTradeRoutes, function($tr) use ($planet) {
                return (int)$tr['source_planet_id'] === (int)$planet['id'] || (int)$tr['target_planet_id'] === (int)$planet['id'];
            });

            // Données de la Bourse P2P (Niveau 10)
            $p2pMaxSlots = $marketEngine->getMaxOfferSlots($lvl);
            $p2pTradables = MarketEngine::getTradableResources();
            $p2pActiveOffers = ($lvl >= 10) ? $marketEngine->getActiveOffers() : [];
            $p2pMyOffers = ($lvl >= 10) ? $marketEngine->getUserOffers((int)$user['id']) : [];
            $p2pMyActiveOffersCount = 0;
            foreach ($p2pMyOffers as $mo) {
                if ($mo['status'] === 'active' && (int)$mo['planet_id'] === (int)$planet['id']) {
                    $p2pMyActiveOffersCount++;
                }
            }
            ?>
            <!-- ========================================================
                 SECTION MARCHÉ FÉODAL : CONVOIS DE MARCHANDISES & TROC
                 ======================================================== -->
            <div class="card mb-3" id="marketSection" style="border-top: 3px solid #f59e0b;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-warning-emphasis d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-scale-balanced text-warning"></i></span> Marché Féodal &amp; Caravanes de Marchandises
                        </h3>
                        <div class="text-secondary small mt-1">
                            Affrétez des convois logistiques pour ravitailler vos autres fiefs ou vos alliés, gérez vos routes commerciales et effectuez du troc.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-warning text-dark fw-bold">
                            Niveau <?= $lvl ?> (<?= $merchantsCount ?> caravanes)
                        </span>
                        <span class="badge bg-info-lt fw-bold" title="Chariots Légers (capacité 5 000)">
                            Chariots Légers : <?= number_format($availLight) ?>
                        </span>
                        <span class="badge bg-purple-lt fw-bold" title="Grands Convois (capacité 25 000)">
                            Grands Convois : <?= number_format($availHeavy) ?>
                        </span>
                        <span class="badge bg-success-lt fw-bold" title="Capacité d'emport totale disponible immédiatement sur ce fief">
                            Capacité Fret : <?= number_format($totalCargoCapacity) ?>
                        </span>
                    </div>
                </div>

                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs m-0" data-bs-toggle="tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-dispatch" class="nav-link active fw-bold text-dark d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="true" role="tab">
                                <span><i class="fa-solid fa-boxes-stacked text-warning"></i></span> Expédier des Marchandises
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-routes" class="nav-link fw-bold text-cyan d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                                <span><i class="fa-solid fa-route text-primary"></i></span> Routes Commerciales
                                <span class="badge bg-cyan text-white ms-1"><?= count($marketRoutes) ?></span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-npc" class="nav-link fw-bold text-purple d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                                <span><i class="fa-solid fa-scale-balanced text-warning"></i></span> Intendant du Marché (Troc 1:1:1)
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-p2p" class="nav-link fw-bold text-success d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                                <span><i class="fa-solid fa-handshake text-success"></i></span> Bourse Féodale (Troc P2P)
                                <?php if ($lvl >= 10): ?>
                                    <span class="badge bg-success-lt ms-1"><?= count($p2pActiveOffers) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt ms-1"><i class="fa-solid fa-lock me-1"></i>Niv. 10</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body pt-4">
                    <div class="tab-content">
                        <!-- ONGLET 1 : EXPÉDIER DES MARCHANDISES -->
                        <div class="tab-pane active show" id="tab-market-dispatch" role="tabpanel">
                            <?php if ($totalCargoCapacity <= 0): ?>
                            <div class="alert alert-warning mb-3 p-3 border-warning shadow-sm">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-1"><i class="fa-solid fa-horse text-danger"></i></span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Aucun chariot de ravitaillement disponible</h4>
                                            <div class="text-secondary small mt-1">
                                                Pour acheminer des denrées vers un autre domaine, vous devez disposer de <strong>Chariots Légers (5k fret)</strong> ou de <strong>Grands Convois Logistiques (25k fret)</strong> dans vos écuries.
                                            </div>
                                        </div>
                                    </div>
                                    <a href="/?page=shipyard" class="btn btn-warning text-dark fw-bold">
                                        <i class="fa-solid fa-horse me-1"></i>Forger des Convois aux Écuries &rarr;
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row g-3">
                                <!-- Colonne Gauche : Destination & Ressources -->
                                <div class="col-lg-7">
                                    <div class="border rounded p-3 bg-light mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold text-dark m-0"><i class="fa-solid fa-chess-rook text-danger me-1"></i>Fief Destinataire :</label>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="mkt_manual_coords_toggle" onchange="toggleMarketDestMode()">
                                                <label class="form-check-label small text-muted" for="mkt_manual_coords_toggle">Saisir coordonnées [X|Y]</label>
                                            </div>
                                        </div>

                                        <!-- Mode 1 : Sélection parmi mes fiefs -->
                                        <div id="mkt_select_village_box">
                                            <?php if (!empty($otherVillages)): ?>
                                                <select id="mkt_target_planet_select" class="form-select fw-bold" onchange="updateMarketCalculations()">
                                                    <?php foreach ($otherVillages as $v): ?>
                                                        <option value="<?= $v['id'] ?>" data-x="<?= $v['coord_x'] ?>" data-y="<?= $v['coord_y'] ?>">
                                                            <i class="fa-solid fa-chess-rook text-danger me-1"></i><?= htmlspecialchars($v['name']) ?> &bull; Coordonnées [<?= $v['coord_x'] ?>|<?= $v['coord_y'] ?>]
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <div class="text-muted small italic p-2 border rounded bg-white">
                                                    Vous ne possédez pour l'instant qu'un seul fief. Utilisez le mode coordonnées pour ravitailler un domaine allié ou voisin.
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Mode 2 : Saisie manuelle de coordonnées -->
                                        <div id="mkt_manual_coords_box" class="d-none">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text small">X</span>
                                                        <input type="number" id="mkt_target_x" class="form-control text-center fw-bold" placeholder="0" oninput="updateMarketCalculations()">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text small">Y</span>
                                                        <input type="number" id="mkt_target_y" class="form-control text-center fw-bold" placeholder="0" oninput="updateMarketCalculations()">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size:0.75rem;">
                                                Entrez les coordonnées féodales de la cité ou du domaine à ravitailler.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Saisie des cargaisons -->
                                    <div class="border rounded p-3 bg-white">
                                        <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-boxes-stacked text-warning me-1"></i>Chargement des Marchandises</h4>

                                        <!-- Bois -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre</label>
                                                <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_wood_label"><?= number_format((int)$planet['metal']) ?></strong></span>
                                            </div>
                                            <div class="input-group mb-1">
                                                <input type="number" id="mkt_wood" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['metal'] ?>" step="500" oninput="updateMarketCalculations()">
                                                <span class="input-group-text small">Bois</span>
                                            </div>
                                            <div class="btn-group btn-group-sm w-100">
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 0)">0</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 1000)">+1k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 5000)">+5k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 'max')">Max</button>
                                            </div>
                                        </div>

                                        <!-- Pierre -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Taille</label>
                                                <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_stone_label"><?= number_format((int)$planet['crystal']) ?></strong></span>
                                            </div>
                                            <div class="input-group mb-1">
                                                <input type="number" id="mkt_stone" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['crystal'] ?>" step="500" oninput="updateMarketCalculations()">
                                                <span class="input-group-text small">Pierre</span>
                                            </div>
                                            <div class="btn-group btn-group-sm w-100">
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 0)">0</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 1000)">+1k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 5000)">+5k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 'max')">Max</button>
                                            </div>
                                        </div>

                                        <!-- Riz -->
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial</label>
                                                <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_rice_label"><?= number_format((int)$planet['deuterium']) ?></strong></span>
                                            </div>
                                            <div class="input-group mb-1">
                                                <input type="number" id="mkt_rice" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['deuterium'] ?>" step="500" oninput="updateMarketCalculations()">
                                                <span class="input-group-text small">Riz</span>
                                            </div>
                                            <div class="btn-group btn-group-sm w-100">
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 0)">0</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 1000)">+1k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 5000)">+5k</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 'max')">Max</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colonne Droite : Bilan logistique & Déploiement -->
                                <div class="col-lg-5">
                                    <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                        <div>
                                            <h4 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                                <span><i class="fa-solid fa-clipboard-list text-primary"></i></span> Feuille de Route du Convoi
                                            </h4>

                                            <div class="list-group list-group-flush border rounded mb-3 bg-white" style="font-size:0.875rem;">
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Fret total engagé :</span>
                                                    <strong class="fs-4 text-primary" id="mkt_total_cargo_display">0</strong>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Chariots mobilisés :</span>
                                                    <span class="fw-bold text-dark" id="mkt_vehicles_needed">Aucun</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Capacité totale convoi :</span>
                                                    <span class="fw-bold text-success" id="mkt_cap_mobilized_display">0</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Ravitaillement de marche :</span>
                                                    <span class="fw-bold text-warning" id="mkt_fuel_needed_display">~5 Koku <i class="fa-solid fa-wheat-awn text-warning"></i></span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Durée estimée aller :</span>
                                                    <span class="fw-bold font-monospace text-dark" id="mkt_duration_display"><i class="fa-solid fa-stopwatch text-info me-1"></i>~calcul...</span>
                                                </div>
                                            </div>

                                            <div id="mkt_alert_status" class="alert alert-info py-2 px-3 small mb-3">
                                                Sélectionnez un fief de destination et indiquez les quantités de ressources à convoyer.
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-warning text-dark fw-bold w-100 py-3 shadow-sm" id="btnDispatchMarketCargo" onclick="submitMarketCargoDispatch()">
                                            <i class="fa-solid fa-paper-plane me-1"></i>Affréter et Expédier le Convoi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ONGLET 2 : ROUTES COMMERCIALES -->
                        <div class="tab-pane" id="tab-market-routes" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h4 class="m-0 fw-bold text-dark"><i class="fa-solid fa-route text-primary me-1"></i>Routes Commerciales liées à ce Marché</h4>
                                    <div class="text-muted small">Convois de ravitaillement programmés au départ ou à destination de ce fief.</div>
                                </div>
                                <?php if ($isSealActive): ?>
                                    <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-cyan text-white fw-bold btn-sm">
                                        <i class="fa-solid fa-plus me-1"></i>Gérer les Routes dans la Cour du Shōgun &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isSealActive): ?>
                                <div class="alert alert-warning p-3 border-warning">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-1"><i class="fa-solid fa-crown text-warning"></i></span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Privilège du Sceau Impérial Requis</h4>
                                            <div class="text-secondary small mt-1">
                                                L'automatisation des routes commerciales permet de programmer des convois logistiques récurrents toutes les 1h, 2h, 4h, 8h, etc. sans aucune action manuelle requise.
                                            </div>
                                            <a href="/?page=privilege" class="btn btn-warning text-dark fw-bold btn-sm mt-2">
                                                <i class="fa-solid fa-crown me-1"></i>Décréter le Sceau Impérial &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (empty($marketRoutes)): ?>
                                <div class="text-center py-4 text-muted bg-light rounded border">
                                    <div class="fs-1 mb-1"><i class="fa-solid fa-route text-primary"></i></div>
                                    <div class="fw-bold text-dark">Aucune route commerciale active sur ce fief</div>
                                    <div class="small mb-3">Définissez des livraisons récurrentes pour alimenter automatiquement vos provinces.</div>
                                    <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-outline-cyan btn-sm fw-bold">
                                        <i class="fa-solid fa-plus me-1"></i>Établir une Route Commerciale
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sens &amp; Fiefs</th>
                                                <th>Cargaison par Passage</th>
                                                <th>Fréquence</th>
                                                <th>Prochain Envoi</th>
                                                <th>Statut</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($marketRoutes as $tr): ?>
                                            <?php $isDepart = ((int)$tr['source_planet_id'] === (int)$planet['id']); ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $isDepart ? 'bg-primary-lt' : 'bg-success-lt' ?> me-1">
                                                        <?= $isDepart ? 'Départ <i class="fa-solid fa-arrow-up-right-from-square text-info"></i>' : 'Arrivée <i class="fa-solid fa-arrow-down-left-and-up-right-to-center text-success"></i>' ?>
                                                    </span>
                                                    <strong><?= htmlspecialchars($tr['source_name']) ?></strong> &rarr; <strong><?= htmlspecialchars($tr['target_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="small">
                                                        <?php if ($tr['wood'] > 0): ?><i class="fa-solid fa-tree text-success me-1"></i><?= number_format($tr['wood']) ?> <?php endif; ?>
                                                        <?php if ($tr['stone'] > 0): ?><i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($tr['stone']) ?> <?php endif; ?>
                                                        <?php if ($tr['rice'] > 0): ?><i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($tr['rice']) ?> <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-cyan-lt">Toutes les <?= $tr['interval_hours'] ?>h</span>
                                                </td>
                                                <td>
                                                    <span class="font-monospace small fw-bold" data-countdown="<?= strtotime($tr['next_run_at']) ?>">
                                                        <?= htmlspecialchars($tr['next_run_at']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $tr['is_active'] ? 'bg-success-lt' : 'bg-secondary-lt' ?>">
                                                        <?= $tr['is_active'] ? 'Active' : 'En pause' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fa-solid fa-gear me-1"></i>Gérer &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ONGLET 3 : INTENDANT DU MARCHÉ (TROC 1:1:1) -->
                        <div class="tab-pane" id="tab-market-npc" role="tabpanel">
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-1"><i class="fa-solid fa-scale-balanced text-warning"></i></span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Intendant du Marché Castral (Troc 1:1:1)</h4>
                                            <div class="text-secondary small mt-1" style="max-width:600px;">
                                                L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au ratio parfait de <strong>1:1:1</strong> sans aucune taxe de déperdition pour un tribut de <strong>3 Koban <i class="fa-solid fa-coins text-warning"></i></strong>.
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <?php $npcPlanetData = $sealEngine->getNpcPlanetResourceData($planet); ?>
                                        <button type="button" class="btn btn-purple text-white fw-bold shadow-sm" style="background:#7c3aed;"
                                                onclick='openNpcExchangeModal(<?= (int)$planet['id'] ?>, <?= json_encode($planet['name']) ?>, <?= json_encode($npcPlanetData['resources']) ?>, <?= json_encode($npcPlanetData['maxes']) ?>)'>
                                            <i class="fa-solid fa-scale-balanced me-1"></i>Procéder au Troc 1:1 de ce Fief &rarr;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ONGLET 4 : BOURSE FÉODALE (TROC P2P & ESCROW NIV 10) -->
                        <?php require_once __DIR__ . '/partials/market_p2p_tab.php'; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Coûts d'amélioration -->
            <?php if ($isMaxLevel): ?>
            <div class="card mb-3 border-success">
                <div class="card-header bg-success-lt d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-success"><i class="fa-solid fa-crown text-warning me-1"></i>Édifice à son Apogée Impériale</h3>
                    <span class="badge bg-success text-white">Niveau <?= $maxLevel ?> / <?= $maxLevel ?></span>
                </div>
                <div class="card-body py-4 text-center">
                    <div class="mb-2"><i class="fa-solid fa-shield-halved text-success" style="font-size:2.5rem;"></i></div>
                    <h4 class="text-dark fw-bold mb-1">Cet ouvrage architectural a atteint son niveau maximal</h4>
                    <p class="text-secondary small mb-0" style="max-width:550px; margin:0 auto;">
                        Toutes les élévations et agrandissements prévus par les traités de charpente et de fortification féodale ont été menés à leur terme. Les rendements, capacités et prérogatives de cette structure fonctionnent à plein régime.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-cubes text-secondary me-1"></i>Coûts d'Amélioration &bull; Niveau <?= $targetLevel ?></h3>
                    <div class="card-options text-muted" style="font-size:0.78rem;">Stock disponible dans vos greniers</div>
                </div>
                <div class="card-body">
                    <?php
                    $resources = [
                        ['icon'=>'<i class="fa-solid fa-tree text-success"></i>', 'name'=>'Bois de Cèdre',   'req'=>$cost['metal'],     'stock'=>$planet['metal'],     'ok'=>$hasMetal],
                        ['icon'=>'<i class="fa-solid fa-mountain text-secondary"></i>', 'name'=>'Pierre de Taille', 'req'=>$cost['crystal'],   'stock'=>$planet['crystal'],   'ok'=>$hasCrystal],
                        ['icon'=>'<i class="fa-solid fa-wheat-awn text-warning"></i>', 'name'=>'Riz Impérial',     'req'=>$cost['deuterium'], 'stock'=>$planet['deuterium'], 'ok'=>$hasDeut],
                    ];
                    foreach ($resources as $r):
                        $pct = ($r['req'] > 0) ? min(100, ($r['stock'] / $r['req']) * 100) : 100;
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <span style="font-size:1.3rem; min-width:2rem;"><?= $r['icon'] ?></span>
                        <div class="flex-fill mx-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-medium" style="font-size:0.875rem;"><?= $r['name'] ?></span>
                                <span class="text-muted" style="font-size:0.8rem;">
                                    <?= number_format($r['stock']) ?> / <strong class="<?= $r['ok'] ? 'text-success' : 'text-danger' ?>"><?= number_format($r['req']) ?></strong> requis
                                </span>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar <?= $r['ok'] ? 'bg-success' : 'bg-danger' ?>" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </div>
                        <span class="badge <?= $r['ok'] ? 'bg-success' : 'bg-danger' ?>" style="min-width:1.5rem;">
                            <?= $r['ok'] ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>

                    <!-- Durée -->
                    <div class="alert alert-info mt-3 mb-0" style="padding:0.6rem 0.85rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-stopwatch text-info me-1"></i>Durée des travaux :</span>
                            <strong class="font-monospace"><?= gmdate('H:i:s', $duration) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Décret de Construction -->
            <div class="card mb-3" id="buildActionBtn">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-hammer text-danger me-1"></i>Décret de Construction Castral</h3>
                    <?php if ($isTerran): ?>
                        <div class="card-options"><span class="badge bg-blue-lt">Clan Oda &bull; Double Chantier</span></div>
                    <?php endif; ?>
                </div>
                <div class="card-body">

                    <?php if ($activeJob): ?>
                        <?php
                            $isDemolishingJob = ((int)$activeJob['target_level'] === 0);
                            $jobNow = time();
                            $jobStart = (int)($activeJob['started_at'] ?? $jobNow);
                            $jobEnd = (int)($activeJob['finishes_at'] ?? $jobNow);
                            $jobTotal = max(1, $jobEnd - $jobStart);
                            $jobElapsed = max(0, $jobNow - $jobStart);
                            $jobPct = min(100, max(0, (int)round(($jobElapsed / $jobTotal) * 100)));
                        ?>
                        <div class="alert alert-<?= $isDemolishingJob ? 'danger' : 'warning' ?> mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-size:1.3rem;"><i class="fa-solid fa-helmet-safety text-danger"></i></span>
                                    <div>
                                        <strong><?= $isDemolishingJob ? 'Démantèlement en cours' : 'Travaux en cours' ?></strong> &rarr;
                                        <span class="badge bg-<?= $isDemolishingJob ? 'danger' : 'warning' ?> text-dark fw-bold">
                                            <?= $isDemolishingJob ? 'Niveau 0 (Raser)' : 'Niveau ' . $activeJob['target_level'] ?>
                                        </span>
                                    </div>
                                </div>
                                <span class="badge bg-white text-<?= $isDemolishingJob ? 'danger' : 'dark' ?> fw-bold font-monospace fs-4 shadow-sm building-progress-pct" id="buildingProgressPct">
                                    <?= $jobPct ?>%
                                </span>
                            </div>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">
                                <?= $isDemolishingJob
                                    ? 'Vos maîtres d\'œuvre déconstruisent cette bâtisse pour libérer l\'emplacement. Vous récupérerez 30% des matériaux.'
                                    : 'Vos bâtisseurs et charpentiers travaillent sur cette bâtisse. La forteresse bénéficiera de ses nouvelles capacités dès achèvement.' ?>
                            </p>

                            <!-- Barre de progression des travaux de construction -->
                            <div class="building-progress-wrapper p-2 bg-white rounded border shadow-sm my-2">
                                <div class="d-flex justify-content-between align-items-center mb-1 text-secondary small">
                                    <span class="fw-semibold d-flex align-items-center gap-1"><span><i class="fa-solid fa-triangle-exclamation text-warning"></i></span> Avancement du chantier</span>
                                    <span class="badge bg-<?= $isDemolishingJob ? 'danger' : 'warning' ?>-lt fw-bold font-monospace building-progress-pct"><?= $jobPct ?>%</span>
                                </div>
                                <div class="progress mb-2" style="height: 12px; background-color: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolishingJob ? 'danger' : 'warning' ?> building-progress-bar"
                                         id="buildingProgressBar"
                                         role="progressbar"
                                         style="width: <?= $jobPct ?>%; transition: width 0.3s ease;"
                                         aria-valuenow="<?= $jobPct ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"
                                         data-started="<?= $jobStart ?>"
                                         data-finishes="<?= $jobEnd ?>">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-secondary small">
                                    <span><i class="fa-solid fa-stopwatch text-info me-1"></i>Compte à rebours :</span>
                                    <span class="font-monospace fw-bold text-dark fs-4 building-time-remaining" data-countdown="<?= $jobEnd ?>">
                                        Calcul...
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-warning w-100 mb-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" onclick="instantFinishBuild(<?= (int)$activeJob['id'] ?>)">
                            <i class="fa-solid fa-bolt text-danger"></i>
                            <span><?= $isAdmin ? '⚡ Finir instantan&eacute;ment (Gratuit Admin)' : 'Finir instantan&eacute;ment (2 Kobans)' ?></span>
                            <i class="fa-solid <?= $isAdmin ? 'fa-bolt text-warning' : 'fa-coins' ?>"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100" onclick="cancelBuildingBuild(<?= (int)$activeJob['id'] ?>)">
                            <i class="fa-solid fa-ban text-danger me-1"></i><?= $isDemolishingJob ? 'Interrompre le démantèlement' : 'Interrompre les travaux (80% remboursé)' ?>
                        </button>

                    <?php elseif ($isMaxLevel): ?>
                        <div class="alert alert-success mb-3 text-center">
                            <div style="font-size:1.8rem; margin-bottom:0.5rem;"><i class="fa-solid fa-crown text-warning"></i></div>
                            <h4 class="alert-title text-success fw-bold">Apogée Castrale Atteinte</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Cet édifice prestigieux a atteint son niveau maximal (<strong>Niveau <?= $maxLevel ?></strong>). Ses capacités et rendements sont à leur apogée impériale.
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-lg w-100 disabled" disabled style="opacity:0.65; cursor:not-allowed;">
                            <i class="fa-solid fa-check-double me-1 text-success"></i> Niveau Maximum Atteint (<?= $maxLevel ?> / <?= $maxLevel ?>)
                        </button>

                    <?php elseif (!$canQueueNewBuilding): ?>
                        <div class="alert alert-secondary mb-3">
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;"><i class="fa-solid fa-helmet-safety text-danger"></i></div>
                            <h4 class="alert-title">Chantier Castral Déjà Mobilisé</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                <?php if ($isTerran): ?>
                                    Une autre bâtisse urbaine est déjà en construction dans votre cité. Le Clan Oda permet 1 bâtiment urbain et 1 parcelle rurale en simultané.
                                <?php else: ?>
                                    Vos équipes de bâtisseurs travaillent déjà sur un autre chantier du domaine. Attendez la fin des travaux en cours.
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="/?page=city" class="btn btn-secondary w-100">&larr; Voir les chantiers de la Cité</a>

                    <?php elseif (!$canAfford): ?>
                        <div class="alert alert-warning mb-3">
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;"><i class="fa-solid fa-triangle-exclamation text-danger"></i></div>
                            <h4 class="alert-title">Matériaux Insuffisants</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Vos greniers ne disposent pas encore de la quantité requise de bois, de pierre ou de riz pour ce chantier castral.
                            </p>
                            <?php if ($missingWaitSeconds > 0): ?>
                                <div class="mt-2 text-center">
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>Matériaux réunis dans : <?= gmdate('H:i:s', $missingWaitSeconds) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary w-100" disabled style="opacity:0.5; cursor:not-allowed;">
                            <?= ($lvl === 0) ? '<i class="fa-solid fa-hammer me-1"></i>Construire au Niveau 1' : '<i class="fa-solid fa-bolt text-warning me-1"></i>Améliorer au Niveau ' . $targetLevel ?>
                        </button>

                    <?php else: ?>
                        <div class="alert alert-success mb-3">
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;"><i class="fa-solid fa-chess-rook text-danger"></i></div>
                            <h4 class="alert-title">Ordre de Travaux Prêt</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Les maîtres-artisans ont dressé les plans. Vous pouvez ordonner l'élévation du <strong>Niveau <?= $targetLevel ?></strong>.
                            </p>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg w-100" id="btnLaunchBuildingUpgrade" onclick="launchBuildingUpgrade('<?= $code ?>', <?= $targetLevel ?>, <?= $slot ?>)">
                            <?= ($lvl === 0) ? ($isAdmin ? '⚡ [Admin] Ériger Immédiatement (Niveau 1 sans coût)' : '<i class="fa-solid fa-hammer me-1"></i>Ériger au Niveau 1') : ($isAdmin ? '⚡ [Admin] Élever Immédiatement (Niveau ' . $targetLevel . ' sans coût)' : '<i class="fa-solid fa-bolt text-warning me-1"></i>Élever au Niveau ' . $targetLevel) ?>
                        </button>
                    <?php endif; ?>

                    <div class="mt-3 text-center">
                        <a href="/?page=city" class="text-muted" style="font-size:0.8rem;">&larr; Revenir à la vue générale de la Cité</a>
                    </div>
                </div>
            </div>

            <!-- ZONE EXCLUSIVE DU TENSHU : PROCLAMATION & DEVISE DU DAIMYŌ -->
            <?php if ($code === 'hq'): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-danger fw-bold" style="font-size:0.75rem; text-transform:uppercase;"><i class="fa-solid fa-chess-rook me-1"></i>Siège du Commandement</div>
                            <h3 class="card-title mb-0"><i class="fa-solid fa-scroll me-1"></i>Devise & Chronique du Daimyō</h3>
                        </div>
                        <button type="button" onclick="openEditMottoModal()" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-pen me-1"></i>Modifier ma Devise
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:0.85rem;">
                            C'est du haut de ce Donjon que vous proclamez la devise qui guide vos samouraïs et inspire la crainte à vos rivaux.
                        </p>
                        <blockquote class="blockquote mb-0 ps-3 border-start border-3 border-danger" style="background:rgba(220,38,38,0.04); padding:0.75rem 1rem; border-radius:0 6px 6px 0;">
                            <div class="text-muted mb-1" style="font-size:0.75rem; text-transform:uppercase; font-weight:700;">
                                Proclamation actuelle du Daimyō <?= htmlspecialchars($user['username']) ?> :
                            </div>
                            <div id="tenshuDaimyoBioText" style="font-size:0.95rem; font-style:italic;">
                                &laquo; <?= htmlspecialchars(!empty($user['bio']) ? $user['bio'] : "Fier Daimyō au service de l'honneur de son clan et de l'Empereur.") ?> &raquo;
                            </div>
                        </blockquote>
                    </div>
                </div>

                <!-- ZONE EXCLUSIVE DU TENSHU : EXPANSION FÉODALE & COLONS -->
                <?php
                    $colonStatus = $planetEngine->getTenshuColonizerStatus((int)$planet['id']);
                    $cCosts = $colonStatus['costs'];
                    $canAffordColon = $isAdmin ? true : ($planet['metal'] >= $cCosts['metal'] && $planet['crystal'] >= $cCosts['crystal'] && $planet['deuterium'] >= $cCosts['deuterium'] && ($planet['rice_flour'] ?? 0) >= $cCosts['rice_flour']);
                ?>
                <div class="card mb-3" style="border-top: 3px solid #10b981;">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                        <div>
                            <h3 class="card-title text-success d-flex align-items-center gap-2 m-0">
                                <span><i class="fa-solid fa-torii-gate text-danger"></i></span> Expansion Coloniale : Pionniers Féodaux (Colons)
                            </h3>
                            <div class="text-secondary small mt-1">
                                Le Tenshu permet de former des <strong>Pionniers Féodaux</strong> aux paliers de niveau <strong>10, 15 et 20</strong> pour annexer de nouveaux villages.
                            </div>
                        </div>
                        <span class="badge <?= ($colonStatus['available_slots'] > 0) ? 'bg-success' : 'bg-secondary' ?> fs-5">
                            <?= $colonStatus['available_slots'] ?> Emplacement(s) libre(s)
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Paliers de déblocage -->
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-4">
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 10) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">1er Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 10) ? '<i class="fa-solid fa-lock-open text-success me-1"></i>Débloqué (Niv. 10)' : '<i class="fa-solid fa-lock text-muted me-1"></i>Tenshu Niv. 10' ?></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 15) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">2e Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 15) ? '<i class="fa-solid fa-lock-open text-success me-1"></i>Débloqué (Niv. 15)' : '<i class="fa-solid fa-lock text-muted me-1"></i>Tenshu Niv. 15' ?></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 20) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">3e Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 20) ? '<i class="fa-solid fa-lock-open text-success me-1"></i>Débloqué (Niv. 20)' : '<i class="fa-solid fa-lock text-muted me-1"></i>Tenshu Niv. 20' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Récapitulatif d'utilisation -->
                        <div class="bg-surface p-3 rounded border mb-3 small">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <div><i class="fa-solid fa-chess-rook text-danger me-1"></i>Fiefs annexés par ce Tenshu : <strong><?= $colonStatus['colonies_count'] ?></strong></div>
                                    <?php if (!empty($colonStatus['founded_colonies'])): ?>
                                        <ul class="mb-0 ps-3 mt-1 text-muted" style="font-size:0.75rem;">
                                            <?php foreach ($colonStatus['founded_colonies'] as $fc): ?>
                                                <li><?= htmlspecialchars($fc['name']) ?> [<?= $fc['coord_x'] ?>|<?= $fc['coord_y'] ?>]</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6">
                                    <div><i class="fa-solid fa-torii-gate text-danger me-1"></i>Colons en garnison dans ce fief : <strong><?= $colonStatus['stationed_colons'] ?></strong></div>
                                    <div><i class="fa-solid fa-horse text-danger me-1"></i>Colons en marche / expédition : <strong><?= $colonStatus['in_mission_colons'] ?></strong></div>
                                    <?php if ($colonStatus['queued_colons'] > 0): ?>
                                        <div><i class="fa-solid fa-hourglass-half text-info me-1"></i>Colons en cours de formation : <strong><?= $colonStatus['queued_colons'] ?></strong></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recrutement -->
                        <?php if ($colonStatus['available_slots'] > 0): ?>
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark">Équiper 1 Pionnier Féodal</strong>
                                    <span class="text-muted small"><i class="fa-solid fa-hourglass-half text-info me-1"></i><?= gmdate("H:i:s", $colonStatus['train_time']) ?> de préparation</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:0.8rem;">
                                    <span class="badge bg-secondary-lt"><i class="fa-solid fa-tree text-success me-1"></i><?= number_format($cCosts['metal']) ?> Bois</span>
                                    <span class="badge bg-secondary-lt"><i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($cCosts['crystal']) ?> Pierre</span>
                                    <span class="badge bg-secondary-lt"><i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($cCosts['deuterium']) ?> Riz</span>
                                    <span class="badge bg-secondary-lt"><i class="fa-solid fa-bowl-rice text-warning me-1"></i><?= number_format($cCosts['rice_flour']) ?> Farine de Riz</span>
                                </div>
                                <button type="button" class="btn btn-success w-100 fw-bold" onclick="trainColonizer()" <?= !$canAffordColon ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-torii-gate me-1"></i>Former 1 Pionnier Féodal (Colon)
                                </button>
                                <?php if (!$canAffordColon): ?>
                                    <div class="text-danger small mt-1 text-center">Ressources ou farine de riz insuffisantes dans vos réserves.</div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0 small">
                                Tous les emplacements d'expansion de ce Tenshu sont actuellement pourvus (<?= $colonStatus['max_slots'] ?>/<?= $colonStatus['max_slots'] ?>).
                                <?php if ($colonStatus['next_threshold']): ?>
                                    <br>Élevez votre Tenshu au <strong>Niveau <?= $colonStatus['next_threshold'] ?></strong> pour débloquer un nouvel emplacement colonial !
                                <?php else: ?>
                                    <br>Ce Tenshu a atteint son apogée maximale d'expansion (3/3). Vous pouvez développer le Tenshu de vos autres fiefs pour continuer votre expansion.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ZONE EXCLUSIVE DU TENSHU : ADMINISTRATION & STATUT DU FIEF -->
                <div class="card mb-3" style="border-top: 3px solid #f59e0b;">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h3 class="card-title text-warning d-flex align-items-center gap-2 m-0">
                            <span><i class="fa-solid fa-crown text-warning"></i></span> Statut &amp; Souveraineté du Fief
                        </h3>
                        <?php if (!empty($planet['is_capital'])): ?>
                            <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i>Capitale Officielle</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Fief Secondaire</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Renommer le village -->
                            <div class="col-md-6 border-end">
                                <label class="form-label small fw-bold text-muted">Nom du Fief :</label>
                                <div class="input-group">
                                    <input type="text" id="villageNewName" class="form-control form-control-sm" value="<?= htmlspecialchars($planet['name']) ?>" maxlength="40">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="renameVillage()">
                                        <i class="fa-solid fa-pen me-1"></i>Renommer
                                    </button>
                                </div>
                                <div class="form-text small" style="font-size:0.72rem;">Entre 2 et 40 caractères. Visible sur la carte du monde.</div>
                            </div>

                            <!-- Proclamer Capitale -->
                            <div class="col-md-6 d-flex flex-column justify-content-between">
                                <div>
                                    <label class="form-label small fw-bold text-muted">Capitale du Clan :</label>
                                    <p class="small text-muted mb-2" style="font-size:0.75rem;">
                                        La Capitale est le siège suprême de votre souveraineté. Elle est sélectionnée par défaut lors de vos connexions et ne peut être prise.
                                    </p>
                                </div>
                                <div>
                                    <?php if (!empty($planet['is_capital'])): ?>
                                        <button type="button" class="btn btn-sm btn-success w-100" disabled>
                                            <i class="fa-solid fa-check text-success me-1"></i>Ce fief est votre Capitale
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning w-100 fw-bold" onclick="proclaimCapital()">
                                            <i class="fa-solid fa-crown me-1"></i>Définir comme Capitale Officielle
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>

<!-- SCRIPT INTERACTIF -->
<script>
async function launchBuildingUpgrade(buildingCode, targetLevel, slot = null) {
    const btn = document.getElementById('btnLaunchBuildingUpgrade');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Mobilisation des bâtisseurs...';
    }

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'building');
    formData.append('target_id', buildingCode);
    if (slot !== null && slot !== undefined) {
        formData.append('slot', slot);
    }

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'ordonner ce chantier.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = (targetLevel === 1 ? '<i class="fa-solid fa-hammer me-1"></i>Ériger au Niveau 1' : '<i class="fa-solid fa-bolt text-warning me-1"></i>Élever au Niveau ' + targetLevel);
            }
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur castral.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = (targetLevel === 1 ? '<i class="fa-solid fa-hammer me-1"></i>Ériger au Niveau 1' : '<i class="fa-solid fa-bolt text-warning me-1"></i>Élever au Niveau ' + targetLevel);
        }
    }
}

async function cancelBuildingBuild(queueId) {
    const confirmed = await showModalConfirm('Voulez-vous vraiment suspendre ces travaux castraux ? 80% des matériaux vous seront restitués.', 'Interruption de Chantier');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('queue_id', queueId);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'interrompre les travaux.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission.', 'error');
    }
}

async function confirmDemolishBuilding(buildingCode, slot, buildingName) {
    const confirmed = await showModalConfirm(
        `Êtes-vous certain de vouloir démanteler définitivement ${buildingName} (Emplacement #${slot}) ?\n\nUn ordre de démolition sera lancé avec un compte à rebours. Vous récupérerez 30% des matériaux à la fin des travaux.`,
        'Démantèlement du Bâtiment'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'demolish');
    formData.append('category', 'building');
    formData.append('target_id', buildingCode);
    formData.append('slot', slot);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de démanteler cette bâtisse.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le serveur.', 'error');
    }
}

// Mise à jour des comptes à rebours et barres de progression de chantier
function updateBuildingCountdowns() {
    if (window.ProgressBar) {
        window.ProgressBar.initAll();
        window.ProgressBar.instances.forEach(pb => pb.update());
    }
}
updateBuildingCountdowns();

function openArtworkModal(imgSrc, title) {
    const modal = document.getElementById('artworkModal');
    const img = document.getElementById('artworkModalImg');
    const titleEl = document.getElementById('artworkModalTitle');
    if (modal && img && titleEl) {
        img.src = imgSrc;
        titleEl.innerText = title;
        modal.style.display = 'flex';
    }
}
function closeArtworkModal(e) {
    const modal = document.getElementById('artworkModal');
    if (modal && (!e || e.target.id === 'artworkModal')) {
        modal.style.display = 'none';
    }
}

// ==========================================
// ATELIER DE RAFFINAGE & CHARPENTERIE
// ==========================================
const isAdminUser = <?= $isAdmin ? 'true' : 'false' ?>;
const grainMillLevel = <?= ($code === 'grain_mill') ? (int)$lvl : (int)($buildings['grain_mill'] ?? 0) ?>;
const sawmillLevel = <?= ($code === 'sawmill') ? (int)$lvl : (int)($buildings['sawmill'] ?? 0) ?>;
const stonemasonLevel = <?= ($code === 'stonemason') ? (int)$lvl : (int)($buildings['stonemason'] ?? 0) ?>;
const blacksmithLevel = <?= ($code === 'blacksmith') ? (int)$lvl : (int)($buildings['blacksmith'] ?? 0) ?>;
const availableRiceStock = <?= (float)($planet['deuterium'] ?? 0) ?>;
const availableWoodStock = <?= (float)($planet['metal'] ?? 0) ?>;
const availableStoneStock = <?= (float)($planet['crystal'] ?? 0) ?>;
const availableClayStock = <?= (float)($planet['clay'] ?? 0) ?>;
const availableIronSandStock = <?= (float)($planet['iron_sand'] ?? 0) ?>;
const availableCharcoalStock = <?= (float)($planet['charcoal'] ?? 0) ?>;
const availableSakeStock = <?= (float)($planet['sake'] ?? 0) ?>;
const gameSpeed = <?= (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1) ?>;

function formatCraftDuration(sec) {
    if (sec <= 0) return '0s';
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    if (m > 0) {
        return `${m}m ${String(s).padStart(2, '0')}s`;
    }
    return `${s}s`;
}

function calcFlourPreview() {
    const input = document.getElementById('rice_amount_flour');
    const previewGain = document.getElementById('preview_flour_gain');
    const previewTime = document.getElementById('preview_flour_time');
    if (!input) return;
    const rice = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (grainMillLevel * 0.02);
    const gain = Math.floor((rice / 5) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' <i class="fa-solid fa-bowl-rice text-warning"></i>';

    if (previewTime) {
        if (rice <= 0) {
            previewTime.textContent = '<i class="fa-solid fa-stopwatch text-info me-1"></i>--';
        } else {
            const duration = Math.max(10, Math.round((rice * 0.4) / (1 + (grainMillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function calcSakePreview() {
    const input = document.getElementById('rice_amount_sake');
    const previewGain = document.getElementById('preview_sake_gain');
    const previewTime = document.getElementById('preview_sake_time');
    if (!input) return;
    const rice = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (grainMillLevel * 0.02);
    const gain = Math.floor((rice / 10) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' <i class="fa-solid fa-wine-bottle text-danger"></i>';

    if (previewTime) {
        if (rice <= 0) {
            previewTime.textContent = '<i class="fa-solid fa-stopwatch text-info me-1"></i>--';
        } else {
            const duration = Math.max(15, Math.round((rice * 0.8) / (1 + (grainMillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function setRiceFlourAmount(val) {
    const input = document.getElementById('rice_amount_flour');
    if (!input) return;
    if (val === 'max') {
        input.value = (isAdminUser && availableRiceStock < 100) ? 1000 : Math.floor(availableRiceStock);
    } else {
        input.value = val;
    }
    calcFlourPreview();
}

function setRiceSakeAmount(val) {
    const input = document.getElementById('rice_amount_sake');
    if (!input) return;
    if (val === 'max') {
        input.value = (isAdminUser && availableRiceStock < 100) ? 1000 : Math.floor(availableRiceStock);
    } else {
        input.value = val;
    }
    calcSakePreview();
}

async function cancelRiceCraft(craftId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous annuler ce raffinage en cours ? 80% du riz engagé sera restitué dans vos greniers.',
        'Annulation du raffinage'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel_craft');
    formData.append('craft_id', craftId);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible d'annuler le raffinage.", 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec les ateliers.', 'error');
    }
}

<?php if (!empty($activeCraft)): ?>
function updateCraftProgressBar() {
    const started = <?= (int)$activeCraft['started_at'] ?>;
    const finishes = <?= (int)$activeCraft['finishes_at'] ?>;
    const now = Math.floor(Date.now() / 1000);
    const total = Math.max(1, finishes - started);
    const elapsed = Math.max(0, now - started);
    const pct = Math.min(100, Math.max(0, (elapsed / total) * 100));
    const pbar = document.getElementById('craftProgressBar');
    if (pbar) {
        pbar.style.width = pct.toFixed(1) + '%';
        pbar.setAttribute('aria-valuenow', pct.toFixed(1));
    }
}
setInterval(updateCraftProgressBar, 1000);
updateCraftProgressBar();
<?php endif; ?>

async function submitRiceCraft(product) {
    const inputId = (product === 'rice_flour') ? 'rice_amount_flour' : 'rice_amount_sake';
    const input = document.getElementById(inputId);
    if (!input) return;
    const amount = parseFloat(input.value) || 0;
    const minAmount = (product === 'rice_flour') ? 5 : 10;

    if (amount < minAmount) {
        showModalAlert(`La quantité minimale de riz requise est de ${minAmount} sacs de riz.`, 'warning');
        return;
    }

    if (!isAdminUser && amount > availableRiceStock) {
        showModalAlert(`Vos greniers ne disposent que de ${Math.floor(availableRiceStock).toLocaleString('fr-FR')} unités de riz.`, 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'craft_rice');
    formData.append('product', product);
    formData.append('rice_amount', amount);
    formData.append('amount', amount);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du raffinage.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le moulin.', 'error');
    }
}

// ==========================================
// ATELIER DE CHARPENTERIE : POUTRES EN BOIS
// ==========================================
<?php if (!empty($activeWoodCraft)): ?>
function updateWoodCraftProgressBar() {
    const started = <?= (int)$activeWoodCraft['started_at'] ?>;
    const finishes = <?= (int)$activeWoodCraft['finishes_at'] ?>;
    const now = Math.floor(Date.now() / 1000);
    const total = Math.max(1, finishes - started);
    const elapsed = Math.max(0, now - started);
    const pct = Math.min(100, Math.max(0, (elapsed / total) * 100));
    const pbar = document.getElementById('woodCraftProgressBar');
    if (pbar) {
        pbar.style.width = pct.toFixed(1) + '%';
        pbar.setAttribute('aria-valuenow', pct.toFixed(1));
    }
}
setInterval(updateWoodCraftProgressBar, 1000);
updateWoodCraftProgressBar();
<?php endif; ?>

function calcBeamsPreview() {
    const input = document.getElementById('wood_amount_beams');
    const previewGain = document.getElementById('preview_beams_gain');
    const previewTime = document.getElementById('preview_beams_time');
    if (!input) return;
    const wood = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (sawmillLevel * 0.02);
    const gain = Math.floor((wood / 10) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' <i class="fa-solid fa-tree text-success"></i>';

    if (previewTime) {
        if (wood <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(10, Math.round((wood * 0.5) / (1 + (sawmillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function setWoodBeamsAmount(val) {
    const input = document.getElementById('wood_amount_beams');
    if (!input) return;
    if (val === 'max') {
        input.value = (isAdminUser && availableWoodStock < 100) ? 1000 : Math.floor(availableWoodStock);
    } else {
        input.value = val;
    }
    calcBeamsPreview();
}

function calcCharcoalPreview() {
    const input = document.getElementById('wood_amount_charcoal');
    const previewGain = document.getElementById('preview_charcoal_gain');
    const previewTime = document.getElementById('preview_charcoal_time');
    if (!input) return;
    const wood = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (sawmillLevel * 0.02);
    const gain = Math.floor(wood * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' 🪨';

    if (previewTime) {
        if (wood <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(10, Math.round((wood * 0.35) / (1 + (sawmillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function setWoodCharcoalAmount(val) {
    const input = document.getElementById('wood_amount_charcoal');
    if (!input) return;
    if (val === 'max') {
        input.value = (isAdminUser && availableWoodStock < 100) ? 1000 : Math.floor(availableWoodStock);
    } else {
        input.value = val;
    }
    calcCharcoalPreview();
}

async function submitWoodCraft(product) {
    product = product || 'wooden_beams';
    const isCharcoal = (product === 'charcoal');

    if (!isAdminUser && isCharcoal && sawmillLevel < 15) {
        showModalAlert("La production de charbon de bois n'est accessible qu'à partir du Niveau 15 de l'Atelier de Charpenterie.", 'warning');
        return;
    }
    if (!isAdminUser && !isCharcoal && sawmillLevel < 10) {
        showModalAlert("Le façonnage de poutres en bois n'est accessible qu'à partir du Niveau 10 de l'Atelier de Charpenterie.", 'warning');
        return;
    }

    const inputId = isCharcoal ? 'wood_amount_charcoal' : 'wood_amount_beams';
    const input = document.getElementById(inputId);
    if (!input) return;
    const amount = parseFloat(input.value) || 0;
    const minAmount = isCharcoal ? 1 : 10;

    if (amount < minAmount) {
        showModalAlert(`La quantité minimale de bois requise est de ${minAmount} unité(s) de Bois de Cèdre.`, 'warning');
        return;
    }

    if (!isAdminUser && amount > availableWoodStock) {
        showModalAlert(`Vos réserves ne disposent que de ${Math.floor(availableWoodStock).toLocaleString('fr-FR')} unités de Bois de Cèdre.`, 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'craft_wood');
    formData.append('product', product);
    formData.append('wood_amount', amount);
    formData.append('amount', amount);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la commande de production.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec la charpenterie.', 'error');
    }
}

async function cancelWoodCraft(craftId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous annuler cette tâche de charpenterie en cours ? 80% du bois engagé sera restitué dans vos réserves.',
        'Annulation du lot'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel_craft');
    formData.append('craft_id', craftId);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible d'annuler le façonnage.", 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec la charpenterie.', 'error');
    }
}

// ==========================================
// ATELIER MINÉRALIER : CONCASSAGE & SYNTHÈSE
// ==========================================
<?php if (!empty($activeMineralCraft)): ?>
function updateMineralCraftProgressBar() {
    const started = <?= (int)$activeMineralCraft['started_at'] ?>;
    const finishes = <?= (int)$activeMineralCraft['finishes_at'] ?>;
    const now = Math.floor(Date.now() / 1000);
    const total = Math.max(1, finishes - started);
    const elapsed = Math.max(0, now - started);
    const pct = Math.min(100, Math.max(0, (elapsed / total) * 100));
    const pbar = document.getElementById('mineralCraftProgressBar');
    if (pbar) {
        pbar.style.width = pct.toFixed(1) + '%';
        pbar.setAttribute('aria-valuenow', pct.toFixed(1));
    }
}
setInterval(updateMineralCraftProgressBar, 1000);
updateMineralCraftProgressBar();
<?php endif; ?>

function calcSulfurPreview() {
    const input = document.getElementById('stone_amount_sulfur');
    const previewGain = document.getElementById('preview_sulfur_gain');
    const previewTime = document.getElementById('preview_sulfur_time');
    if (!input) return;
    const stone = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (stonemasonLevel * 0.02);
    const gain = Math.floor((stone / 4) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' 🌋';

    if (previewTime) {
        if (stone <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(15, Math.round((stone * 0.5) / (1 + (stonemasonLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function calcNitratePreview() {
    const input = document.getElementById('clay_amount_nitrate');
    const previewGain = document.getElementById('preview_nitrate_gain');
    const previewTime = document.getElementById('preview_nitrate_time');
    if (!input) return;
    const clay = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (stonemasonLevel * 0.02);
    const gain = Math.floor((clay / 3) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' 🧪';

    if (previewTime) {
        if (clay <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(15, Math.round((clay * 0.4) / (1 + (stonemasonLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function calcIronSandPreview() {
    const input = document.getElementById('stone_amount_ironsand');
    const previewGain = document.getElementById('preview_ironsand_gain');
    const previewTime = document.getElementById('preview_ironsand_time');
    if (!input) return;
    const stone = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (stonemasonLevel * 0.02);
    const gain = Math.floor((stone / 5) * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' 💎';

    if (previewTime) {
        if (stone <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(20, Math.round((stone * 0.6) / (1 + (stonemasonLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function setMineralAmount(product, val) {
    let inputId = 'stone_amount_sulfur';
    let available = availableStoneStock;
    if (product === 'nitrate') {
        inputId = 'clay_amount_nitrate';
        available = availableClayStock;
    } else if (product === 'iron_sand') {
        inputId = 'stone_amount_ironsand';
        available = availableStoneStock;
    }
    const input = document.getElementById(inputId);
    if (!input) return;
    if (val === 'max') {
        input.value = (isAdminUser && available < 100) ? 1000 : Math.floor(available);
    } else {
        input.value = val;
    }
    if (product === 'sulfur') calcSulfurPreview();
    else if (product === 'nitrate') calcNitratePreview();
    else if (product === 'iron_sand') calcIronSandPreview();
}

async function submitMineralCraft(product) {
    let inputId = 'stone_amount_sulfur';
    let available = availableStoneStock;
    let minCost = 4;
    let matName = "Pierre de Granit";

    if (product === 'nitrate') {
        inputId = 'clay_amount_nitrate';
        available = availableClayStock;
        minCost = 3;
        matName = "Argile Alluviale";
    } else if (product === 'iron_sand') {
        inputId = 'stone_amount_ironsand';
        available = availableStoneStock;
        minCost = 5;
        matName = "Pierre de Granit";
    }

    const input = document.getElementById(inputId);
    if (!input) return;
    const amount = parseFloat(input.value) || 0;

    if (amount < minCost) {
        showModalAlert(`Veuillez engager au moins ${minCost} unités de ${matName}.`, 'warning');
        return;
    }

    if (!isAdminUser && amount > available) {
        showModalAlert(`Vos réserves ne disposent que de ${Math.floor(available).toLocaleString('fr-FR')} unités de ${matName}.`, 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'craft_mineral');
    formData.append('product', product);
    formData.append('amount', amount);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du raffinage.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec l\'atelier minéralier.', 'error');
    }
}

async function cancelMineralCraft(craftId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous annuler cette commande de raffinage minéralier ? 80% des matières premières engagées seront restituées dans vos entrepôts.',
        'Annulation du lot'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel_craft');
    formData.append('craft_id', craftId);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible d'annuler le lot.", 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec l\'atelier.', 'error');
    }
}

// ==========================================
// GRANDE FORGE DU TAMAHAGANE : FONTE & PRESTIGE
// ==========================================
<?php if (!empty($activeForgeCraft)): ?>
function updateForgeCraftProgressBar() {
    const started = <?= (int)$activeForgeCraft['started_at'] ?>;
    const finishes = <?= (int)$activeForgeCraft['finishes_at'] ?>;
    const now = Math.floor(Date.now() / 1000);
    const total = Math.max(1, finishes - started);
    const elapsed = Math.max(0, now - started);
    const pct = Math.min(100, Math.max(0, (elapsed / total) * 100));
    const pbar = document.getElementById('forgeCraftProgressBar');
    if (pbar) {
        pbar.style.width = pct.toFixed(1) + '%';
        pbar.setAttribute('aria-valuenow', pct.toFixed(1));
    }
}
setInterval(updateForgeCraftProgressBar, 1000);
updateForgeCraftProgressBar();
<?php endif; ?>

function calcForgeMetalPreview() {
    const input = document.getElementById('forge_batches_metal');
    const previewGain = document.getElementById('preview_forge_metal_gain');
    const previewTime = document.getElementById('preview_forge_metal_time');
    if (!input) return;
    const batches = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (blacksmithLevel * 0.02);
    const gain = Math.floor(batches * 40 * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' ⚔️';

    if (previewTime) {
        if (batches <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(10, Math.round((batches * 25) / (1 + (blacksmithLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function calcForgeCrudePreview() {
    const input = document.getElementById('forge_batches_crude');
    const previewGain = document.getElementById('preview_forge_crude_gain');
    const previewTime = document.getElementById('preview_forge_crude_time');
    if (!input) return;
    const batches = Math.max(0, parseFloat(input.value) || 0);
    const efficiency = 1 + (blacksmithLevel * 0.02);
    const gain = Math.floor(batches * 25 * efficiency);
    if (previewGain) previewGain.innerHTML = '+' + gain.toLocaleString('fr-FR') + ' ⚔️';

    if (previewTime) {
        if (batches <= 0) {
            previewTime.textContent = '--';
        } else {
            const duration = Math.max(10, Math.round((batches * 30) / (1 + (blacksmithLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
        }
    }
}

function calcForgePrestigePreview() {
    const input = document.getElementById('forge_batches_prestige');
    const previewGain = document.getElementById('preview_forge_prestige_gain');
    const previewTime = document.getElementById('preview_forge_prestige_time');
    if (!input) return;
    const batches = Math.max(1, parseFloat(input.value) || 1);
    const honor = batches * 150;
    if (previewGain) previewGain.innerHTML = '+' + honor.toLocaleString('fr-FR') + ' Honneur Féodal 🌟';

    if (previewTime) {
        const duration = Math.max(30, Math.round((batches * 180) / (1 + (blacksmithLevel * 0.15)) / (gameSpeed || 1)));
        previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
    }
}

function calcForgeArmyPreview() {
    const input = document.getElementById('forge_batches_army');
    const previewGain = document.getElementById('preview_forge_army_gain');
    const previewTime = document.getElementById('preview_forge_army_time');
    if (!input) return;
    const batches = Math.max(1, parseFloat(input.value) || 1);
    const sam = batches * 10;
    const hat = batches * 2;
    if (previewGain) previewGain.innerHTML = '+' + sam + ' Samouraïs &amp; +' + hat + ' Hatamoto 🎌';

    if (previewTime) {
        const duration = Math.max(45, Math.round((batches * 240) / (1 + (blacksmithLevel * 0.15)) / (gameSpeed || 1)));
        previewTime.innerHTML = '<i class="fa-solid fa-stopwatch text-info me-1"></i>' + formatCraftDuration(duration);
    }
}

function setForgeAmount(product, val) {
    let inputId = 'forge_batches_metal';
    let maxVal = 10;

    if (product === 'metal') {
        inputId = 'forge_batches_metal';
        maxVal = Math.max(1, Math.floor(Math.min(availableIronSandStock, availableCharcoalStock / 2)));
    } else if (product === 'metal_crude') {
        inputId = 'forge_batches_crude';
        maxVal = Math.max(1, Math.floor(Math.min(availableStoneStock / 10, availableCharcoalStock / 5)));
    }

    const input = document.getElementById(inputId);
    if (!input) return;
    if (val === 'max') {
        input.value = maxVal;
    } else {
        input.value = val;
    }

    if (product === 'metal') calcForgeMetalPreview();
    else if (product === 'metal_crude') calcForgeCrudePreview();
}

async function submitForgeCraft(product) {
    let inputId = 'forge_batches_metal';
    if (product === 'metal_crude') inputId = 'forge_batches_crude';
    else if (product === 'prestige_weapons') inputId = 'forge_batches_prestige';
    else if (product === 'prestige_army') inputId = 'forge_batches_army';

    const input = document.getElementById(inputId);
    const amount = input ? (parseFloat(input.value) || 1) : 1;

    if (amount <= 0) {
        showModalAlert('Veuillez indiquer un nombre de fournées supérieur à 0.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'craft_forge');
    formData.append('product', product);
    formData.append('amount', amount);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la commande de forge.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec la Grande Forge.', 'error');
    }
}

async function cancelForgeCraft(craftId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous annuler cette commande de forge ? 80% des matières premières engagées seront restituées dans vos réserves.',
        'Annulation du travail de forge'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel_craft');
    formData.append('craft_id', craftId);

    try {
        const res = await fetch('/api/craft.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || "Impossible d'annuler le lot.", 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec la Grande Forge.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    calcForgeMetalPreview();
    calcForgeCrudePreview();
    calcForgePrestigePreview();
    calcForgeArmyPreview();
});

// ==========================================
// CÉLÉBRATIONS DU TENSHU : BANQUETS DE SAKÉ
// ==========================================
async function submitTenshuFeast(feastType) {
    const names = {
        'matsuri': 'le Matsuri Populaire des Saisons',
        'warriors': 'le Banquet des Guerriers (Kanpai aux Samouraïs)',
        'imperial': 'le Grand Banquet Impérial & Diplomatique'
    };
    const feastName = names[feastType] || 'cette célébration';
    const confirmed = await showModalConfirm(
        `Voulez-vous ouvrir les fûts de Saké et proclamer ${feastName} au sein du Tenshu ?`,
        'Célébration Féodale du Daimyō'
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'start_feast');
    formData.append('feast_type', feastType);

    try {
        const res = await fetch('/api/feast.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Impossible de lancer les festivités.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le Tenshu.', 'error');
    }
}

async function trainColonizer() {
    const confirmed = await showModalConfirm(
        "Mobilisation de Pionniers Féodaux",
        "Voulez-vous mobiliser vos maîtres bâtisseurs et artisans pour équiper un Pionnier Féodal (Colon) ? Il permettra d'aller annexer un nouveau territoire vierge sur la carte.",
        "Former le Colon",
        "Annuler"
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'train_colonizer');
    formData.append('count', 1);

    try {
        const res = await fetch('/api/planet.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Impossible de recruter le colon.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le Tenshu.', 'error');
    }
}

async function renameVillage() {
    const input = document.getElementById('villageNewName');
    if (!input) return;
    const newName = input.value.trim();
    if (newName.length < 2) {
        showModalAlert('Veuillez indiquer un nom d\'au moins 2 caractères.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'rename');
    formData.append('name', newName);

    try {
        const res = await fetch('/api/planet.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || 'Erreur lors du renommage.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de connexion au serveur.', 'error');
    }
}

async function proclaimCapital() {
    const confirmed = await showModalConfirm(
        "Proclamation de la Capitale",
        "Êtes-vous certain de vouloir déplacer la Capitale officielle de votre clan vers ce fief ? L'ancien fief capitale deviendra un fief secondaire.",
        "Déclarer Capitale",
        "Annuler"
    );
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'set_capital');

    try {
        const res = await fetch('/api/planet.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la proclamation.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de connexion au serveur.', 'error');
    }
}

// ==========================================
// MARCHÉ FÉODAL : CONVOIS DE MARCHANDISES
// ==========================================
let currentPlanetCoords = {
    x: <?= (int)$planet['coord_x'] ?>,
    y: <?= (int)$planet['coord_y'] ?>
};
let availableCargoStocks = {
    wood: <?= (int)$planet['metal'] ?>,
    stone: <?= (int)$planet['crystal'] ?>,
    rice: <?= (int)$planet['deuterium'] ?>
};
let availableTransporters = {
    light: <?= (int)($availLight ?? 0) ?>,
    heavy: <?= (int)($availHeavy ?? 0) ?>,
    totalCap: <?= (int)($totalCargoCapacity ?? 0) ?>
};

function toggleMarketDestMode() {
    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;
    const selectBox = document.getElementById('mkt_select_village_box');
    const manualBox = document.getElementById('mkt_manual_coords_box');
    if (isManual) {
        selectBox?.classList.add('d-none');
        manualBox?.classList.remove('d-none');
    } else {
        selectBox?.classList.remove('d-none');
        manualBox?.classList.add('d-none');
    }
    updateMarketCalculations();
}

function setMarketResAmount(res, amount) {
    const input = document.getElementById(`mkt_${res}`);
    if (!input) return;
    const maxVal = availableCargoStocks[res] || 0;
    if (amount === 'max') {
        input.value = maxVal;
    } else if (typeof amount === 'number') {
        if (amount === 0) {
            input.value = 0;
        } else {
            const current = parseInt(input.value || 0, 10);
            input.value = Math.min(maxVal, current + amount);
        }
    }
    updateMarketCalculations();
}

function updateMarketCalculations() {
    const wood = Math.max(0, parseInt(document.getElementById('mkt_wood')?.value || 0, 10));
    const stone = Math.max(0, parseInt(document.getElementById('mkt_stone')?.value || 0, 10));
    const rice = Math.max(0, parseInt(document.getElementById('mkt_rice')?.value || 0, 10));
    const totalCargo = wood + stone + rice;

    const totalCargoDisplay = document.getElementById('mkt_total_cargo_display');
    const vehiclesNeededDisplay = document.getElementById('mkt_vehicles_needed');
    const capMobilizedDisplay = document.getElementById('mkt_cap_mobilized_display');
    const fuelNeededDisplay = document.getElementById('mkt_fuel_needed_display');
    const durationDisplay = document.getElementById('mkt_duration_display');
    const alertStatus = document.getElementById('mkt_alert_status');
    const btnDispatch = document.getElementById('btnDispatchMarketCargo');

    if (!totalCargoDisplay) return;

    totalCargoDisplay.innerText = totalCargo.toLocaleString('fr-FR');

    // Récupérer les coordonnées cibles
    let targetX = null;
    let targetY = null;
    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;

    if (isManual) {
        const inpX = document.getElementById('mkt_target_x')?.value;
        const inpY = document.getElementById('mkt_target_y')?.value;
        if (inpX !== '' && inpY !== '' && !isNaN(inpX) && !isNaN(inpY)) {
            targetX = parseInt(inpX, 10);
            targetY = parseInt(inpY, 10);
        }
    } else {
        const sel = document.getElementById('mkt_target_planet_select');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            targetX = parseInt(opt.getAttribute('data-x'), 10);
            targetY = parseInt(opt.getAttribute('data-y'), 10);
        }
    }

    // Calcul de la distance
    let distance = 5.0;
    if (targetX !== null && targetY !== null) {
        distance = Math.max(1.0, Math.round(Math.sqrt(Math.pow(targetX - currentPlanetCoords.x, 2) + Math.pow(targetY - currentPlanetCoords.y, 2)) * 100) / 100);
    }

    // Calcul des transporteurs requis
    let needHeavy = 0;
    let needLight = 0;
    let hasEnoughVehicles = false;

    if (totalCargo > 0) {
        needHeavy = Math.min(availableTransporters.heavy, Math.floor(totalCargo / 25000));
        let rem = totalCargo - (needHeavy * 25000);
        if (rem > 0) {
            if (availableTransporters.light * 5000 >= rem) {
                needLight = Math.ceil(rem / 5000);
            } else if (availableTransporters.heavy > needHeavy) {
                needHeavy++;
                needLight = 0;
            } else {
                needLight = Math.ceil(rem / 5000);
            }
        }
        const mobilizedCap = (needHeavy * 25000) + (needLight * 5000);
        hasEnoughVehicles = (mobilizedCap >= totalCargo && needHeavy <= availableTransporters.heavy && needLight <= availableTransporters.light);
    }

    const mobilizedCap = (needHeavy * 25000) + (needLight * 5000);
    capMobilizedDisplay.innerText = mobilizedCap.toLocaleString('fr-FR');

    let vehicleText = [];
    if (needHeavy > 0) vehicleText.push(`${needHeavy} Grand Convoi (25k)`);
    if (needLight > 0) vehicleText.push(`${needLight} Chariot Léger (5k)`);
    vehiclesNeededDisplay.innerText = vehicleText.length > 0 ? vehicleText.join(' + ') : 'Aucun';

    // Rations de marche requises
    const totalShips = needHeavy + needLight;
    const effectiveMarches = Math.max(1, totalShips);
    const fuelReq = Math.max(5, Math.round(distance * effectiveMarches * 1.2));
    fuelNeededDisplay.innerHTML = `~${fuelReq.toLocaleString('fr-FR')} Koku <i class="fa-solid fa-wheat-awn text-warning"></i>`;

    // Durée estimée (vitesse de base convoi = 4000)
    const speed = 4000;
    const durSec = Math.max(3, Math.round((3500 * distance) / (speed * 5)));
    const mins = Math.floor(durSec / 60);
    const secs = durSec % 60;
    durationDisplay.innerHTML = `<i class="fa-solid fa-stopwatch text-info me-1"></i>~${mins}m ${secs < 10 ? '0' : ''}${secs}s (Dist. ${distance})`;

    // Validation du bouton
    if (totalCargo <= 0) {
        alertStatus.className = 'alert alert-info py-2 px-3 small mb-3';
        alertStatus.innerText = 'Indiquez au moins une quantité de ressources à acheminer.';
        if (btnDispatch) btnDispatch.disabled = true;
    } else if (targetX === null || targetY === null || (targetX === currentPlanetCoords.x && targetY === currentPlanetCoords.y)) {
        alertStatus.className = 'alert alert-warning py-2 px-3 small mb-3';
        alertStatus.innerText = 'Veuillez sélectionner un fief destinataire distinct de ce fief.';
        if (btnDispatch) btnDispatch.disabled = true;
    } else if (!hasEnoughVehicles) {
        alertStatus.className = 'alert alert-danger py-2 px-3 small mb-3';
        alertStatus.innerText = `Capacité de transport insuffisante sur ce fief (${availableTransporters.totalCap.toLocaleString('fr-FR')} disponible / ${totalCargo.toLocaleString('fr-FR')} requis).`;
        if (btnDispatch) btnDispatch.disabled = true;
    } else if (wood > availableCargoStocks.wood || stone > availableCargoStocks.stone || (rice + fuelReq) > availableCargoStocks.rice) {
        alertStatus.className = 'alert alert-danger py-2 px-3 small mb-3';
        alertStatus.innerText = `Ressources ou rations de riz insuffisantes dans vos greniers (Ravitaillement de marche : ${fuelReq} Riz requis).`;
        if (btnDispatch) btnDispatch.disabled = true;
    } else {
        alertStatus.className = 'alert alert-success py-2 px-3 small mb-3';
        alertStatus.innerText = `Convoi prêt : ${totalCargo.toLocaleString('fr-FR')} fret acheminé vers [${targetX}|${targetY}] via ${vehicleText.join(' + ')}.`;
        if (btnDispatch) btnDispatch.disabled = false;
    }
}

async function submitMarketCargoDispatch() {
    const wood = Math.max(0, parseInt(document.getElementById('mkt_wood')?.value || 0, 10));
    const stone = Math.max(0, parseInt(document.getElementById('mkt_stone')?.value || 0, 10));
    const rice = Math.max(0, parseInt(document.getElementById('mkt_rice')?.value || 0, 10));
    const totalCargo = wood + stone + rice;

    if (totalCargo <= 0) {
        showModalAlert('Veuillez spécifier au moins une quantité de ressources.', 'warning');
        return;
    }

    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;
    let targetPlanetId = null;
    let targetX = null;
    let targetY = null;

    if (isManual) {
        targetX = document.getElementById('mkt_target_x')?.value;
        targetY = document.getElementById('mkt_target_y')?.value;
        if (!targetX || !targetY) {
            showModalAlert('Veuillez entrer les coordonnées féodales de destination [X|Y].', 'warning');
            return;
        }
    } else {
        const sel = document.getElementById('mkt_target_planet_select');
        if (!sel || !sel.value) {
            showModalAlert('Veuillez choisir un fief destinataire.', 'warning');
            return;
        }
        targetPlanetId = sel.value;
    }

    const confirmed = await showModalConfirm(
        `Confirmez-vous l'affrètement du convoi logistique de ${totalCargo.toLocaleString('fr-FR')} ressources ?`,
        'Expédition de Marchandises'
    );
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('mission_type', 'transport');
    if (targetPlanetId) fd.append('target_planet_id', targetPlanetId);
    if (targetX !== null) fd.append('target_x', targetX);
    if (targetY !== null) fd.append('target_y', targetY);
    fd.append('cargo_metal', wood);
    fd.append('cargo_crystal', stone);
    fd.append('cargo_deuterium', rice);

    const btn = document.getElementById('btnDispatchMarketCargo');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch('/api/fleet.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message || 'Le convoi de marchandises a quitté les portes du fief avec succès !', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de l\'envoi du convoi.', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec les intendants du marché.', 'error');
        if (btn) btn.disabled = false;
    }
}



// ========================================================
// BOURSE FÉODALE (TROC P2P & SÉQUESTRE)
// ========================================================
const p2pPlanetStockMap = <?= json_encode([
    'metal' => (float)$planet['metal'],
    'crystal' => (float)$planet['crystal'],
    'deuterium' => (float)$planet['deuterium'],
    'clay' => (float)($planet['clay'] ?? 0),
    'wooden_beams' => (float)($planet['wooden_beams'] ?? 0),
    'charcoal' => (float)($planet['charcoal'] ?? 0),
    'fish' => (float)($planet['fish'] ?? 0),
    'tea' => (float)($planet['tea'] ?? 0),
    'soybean' => (float)($planet['soybean'] ?? 0),
    'rice_flour' => (float)($planet['rice_flour'] ?? 0),
    'sake' => (float)($planet['sake'] ?? 0),
    'sulfur' => (float)($planet['sulfur'] ?? 0),
    'nitrate' => (float)($planet['nitrate'] ?? 0),
    'iron_sand' => (float)($planet['iron_sand'] ?? 0),
]) ?>;

function onP2POfferResChange() {
    const sel = document.getElementById('p2p_offer_res');
    if (!sel) return;
    const res = sel.value;
    const stock = p2pPlanetStockMap[res] || 0;
    const badge = document.getElementById('p2p_offer_stock_badge');
    if (badge) {
        badge.innerHTML = `Stock dispo : <strong class="text-success">${Math.floor(stock).toLocaleString('fr-FR')}</strong>`;
    }
    updateP2PRatioPreview();
}

function onP2PDemandResChange() {
    updateP2PRatioPreview();
}

function setP2POfferQuick(pct) {
    const sel = document.getElementById('p2p_offer_res');
    if (!sel) return;
    const stock = p2pPlanetStockMap[sel.value] || 0;
    const val = Math.max(1, Math.floor(stock * pct));
    const inp = document.getElementById('p2p_offer_amount');
    if (inp) {
        inp.value = val;
        updateP2PRatioPreview();
    }
}

function setP2PDemandRatio(targetRatio) {
    const offInp = document.getElementById('p2p_offer_amount');
    const demInp = document.getElementById('p2p_demand_amount');
    if (!offInp || !demInp) return;
    const offerVal = Math.max(1, parseInt(offInp.value || 1, 10));
    demInp.value = Math.max(1, Math.round(offerVal * targetRatio));
    updateP2PRatioPreview();
}

function updateP2PRatioPreview() {
    const offInp = document.getElementById('p2p_offer_amount');
    const demInp = document.getElementById('p2p_demand_amount');
    const ratioDisp = document.getElementById('p2p_ratio_display');
    const ratioHint = document.getElementById('p2p_ratio_hint');
    if (!offInp || !demInp || !ratioDisp) return;

    const offVal = Math.max(1, parseInt(offInp.value || 1, 10));
    const demVal = Math.max(1, parseInt(demInp.value || 1, 10));
    const r = (demVal / offVal).toFixed(2);

    ratioDisp.textContent = `1 : ${r}`;
    if (ratioHint) {
        if (r < 0.8) {
            ratioHint.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-gift me-1"></i>Très avantageuse</span>`;
        } else if (r > 1.5) {
            ratioHint.innerHTML = `<span class="text-warning-emphasis fw-bold"><i class="fa-solid fa-coins me-1"></i>Offre exigeante</span>`;
        } else {
            ratioHint.innerHTML = `<span class="text-muted"><i class="fa-solid fa-scale-balanced me-1"></i>Taux équilibré</span>`;
        }
    }
}

async function submitP2POffer() {
    const offRes = document.getElementById('p2p_offer_res')?.value;
    const offAmt = parseInt(document.getElementById('p2p_offer_amount')?.value || 0, 10);
    const demRes = document.getElementById('p2p_demand_res')?.value;
    const demAmt = parseInt(document.getElementById('p2p_demand_amount')?.value || 0, 10);

    if (!offRes || offAmt <= 0) {
        showModalAlert("Veuillez saisir une quantité valide de ressources à bloquer en offre.", "warning");
        return;
    }
    if (!demRes || demAmt <= 0) {
        showModalAlert("Veuillez saisir une quantité valide de ressources demandées.", "warning");
        return;
    }
    if (offRes === demRes) {
        showModalAlert("Vous ne pouvez pas échanger une ressource contre la même ressource.", "warning");
        return;
    }

    const confirmed = await showModalConfirm(
        `Confirmez-vous le dépôt de l'offre ? ${offAmt.toLocaleString('fr-FR')} unités seront immédiatement BLOQUÉES en séquestre jusqu'à l'échange ou l'annulation.`,
        "Séquestre & Dépôt d'Offre de Troc"
    );
    if (!confirmed) return;

    const btn = document.getElementById('btnSubmitP2POffer');
    if (btn) btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('offer_res', offRes);
        fd.append('offer_amount', offAmt);
        fd.append('demand_res', demRes);
        fd.append('demand_amount', demAmt);

        const res = await fetch('/api/market.php?action=create_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message || "Offre consignée avec succès dans les coffres du marché !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Erreur lors du dépôt de l'offre.", "error");
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert("Erreur de communication avec la bourse féodale.", "error");
        if (btn) btn.disabled = false;
    }
}

async function cancelP2POffer(offerId) {
    const confirmed = await showModalConfirm(
        `Voulez-vous annuler l'offre n°#${offerId} ? Vos ressources consignées seront immédiatement restituées à vos entrepôts.`,
        "Annulation de l'Offre & Restitution"
    );
    if (!confirmed) return;

    try {
        const fd = new FormData();
        fd.append('offer_id', offerId);
        const res = await fetch('/api/market.php?action=cancel_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message || "Offre annulée et ressources restituées avec succès !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Erreur lors de l'annulation de l'offre.", "error");
        }
    } catch (e) {
        showModalAlert("Erreur de communication avec le marché.", "error");
    }
}

async function acceptP2POffer(offerId, offName, offAmt, demName, demAmt) {
    const confirmed = await showModalConfirm(
        `Confirmez-vous l'échange féodal ? Vous allez donner ${demAmt.toLocaleString('fr-FR')} ${demName} pour recevoir ${offAmt.toLocaleString('fr-FR')} ${offName}.`,
        "Conclure le Troc Féodal"
    );
    if (!confirmed) return;

    try {
        const fd = new FormData();
        fd.append('offer_id', offerId);
        const res = await fetch('/api/market.php?action=accept_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message || "Accord commercial accompli avec succès !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || "Erreur lors de la conclusion de l'échange.", "error");
        }
    } catch (e) {
        showModalAlert("Erreur de communication avec le marché.", "error");
    }
}

async function refreshP2POffers() {
    const filterOff = document.getElementById('p2p_filter_offer')?.value || '';
    const filterDem = document.getElementById('p2p_filter_demand')?.value || '';
    try {
        const res = await fetch(`/api/market.php?action=list_offers&offer_res=${encodeURIComponent(filterOff)}&demand_res=${encodeURIComponent(filterDem)}`);
        const data = await res.json();
        if (data.success) {
            const tbody = document.getElementById('p2p_active_offers_tbody');
            if (!tbody) return;
            if (data.offers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Aucune offre ne correspond à vos filtres.</td></tr>`;
                return;
            }
            tbody.innerHTML = data.offers.map(o => {
                const offDef = data.tradables[o.offer_res] || { name: o.offer_res, icon: 'fa-solid fa-box' };
                const demDef = data.tradables[o.demand_res] || { name: o.demand_res, icon: 'fa-solid fa-box' };
                const curStock = p2pPlanetStockMap[o.demand_res] || 0;
                const canAccept = (curStock >= parseInt(o.demand_amount, 10));
                return `
                    <tr>
                        <td class="font-monospace text-muted">#${o.id}</td>
                        <td>
                            <strong class="text-dark">${o.seller_name}</strong>
                            <div class="text-muted small">${o.seller_planet_name} [${o.seller_x}|${o.seller_y}]</div>
                        </td>
                        <td>
                            <span class="badge bg-success text-white fs-6 py-1 px-2">
                                +${parseInt(o.offer_amount).toLocaleString('fr-FR')} <i class="${offDef.icon} ms-1"></i>
                            </span>
                            <div class="text-muted small mt-1">${offDef.name}</div>
                        </td>
                        <td>
                            <span class="badge bg-danger-lt text-danger fs-6 py-1 px-2">
                                -${parseInt(o.demand_amount).toLocaleString('fr-FR')} <i class="${demDef.icon} ms-1"></i>
                            </span>
                            <div class="text-muted small mt-1">${demDef.name}</div>
                        </td>
                        <td class="font-monospace"><strong>1 : ${parseFloat(o.ratio)}</strong></td>
                        <td>
                            <div class="small ${canAccept ? 'text-success fw-bold' : 'text-danger'}">
                                ${Math.floor(curStock).toLocaleString('fr-FR')} en réserve
                            </div>
                        </td>
                        <td class="text-end">
                            ${canAccept ? `
                                <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="acceptP2POffer(${o.id}, '${offDef.name.replace(/'/g, "\\'")}', ${o.offer_amount}, '${demDef.name.replace(/'/g, "\\'")}', ${o.demand_amount})">
                                    <i class="fa-solid fa-handshake me-1"></i>Échanger
                                </button>
                            ` : `
                                <button type="button" class="btn btn-sm btn-secondary disabled" title="Stock insuffisant">
                                    <i class="fa-solid fa-ban me-1"></i>Insuffisant
                                </button>
                            `}
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {
        console.error("Erreur actualisation offres:", e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    calcFlourPreview();
    calcSakePreview();
    calcBeamsPreview();
    calcCharcoalPreview();
    updateMarketCalculations();
    onP2POfferResChange();
});
</script>

<?php require_once __DIR__ . '/partials/npc_exchange_modal.php'; ?>

<!-- MODALE LIGHTBOX ESTAMPE HD -->
<div id="artworkModal" class="modal-overlay" style="display:none;" onclick="closeArtworkModal(event)">
    <div class="modal-card modal-card-lg" style="max-width:960px; padding:1.5rem;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--tblr-border-color); padding-bottom:0.75rem; margin-bottom:1rem;">
            <h3 id="artworkModalTitle" style="margin:0; font-size:1.1rem; font-weight:800;"><i class="fa-solid fa-palette text-info me-1"></i>Estampe Féodale Authentique</h3>
            <button type="button" class="btn-close" onclick="closeArtworkModal()"></button>
        </div>
        <div style="text-align:center;">
            <img id="artworkModalImg" src="" alt="Estampe" style="width:100%; height:auto; max-height:75vh; object-fit:contain; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        </div>
    </div>
</div>


