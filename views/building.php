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
    'hq' => ['name' => 'Tenshu Donjon', 'sec' => 'sec-hq', 'icon' => '🏯'],
    'shipyard' => ['name' => 'Écuries & Cavalerie', 'sec' => 'sec-military', 'icon' => '🐎'],
    'barracks' => ['name' => 'Dojo Militaire', 'sec' => 'sec-military', 'icon' => '🥋'],
    'radar' => ['name' => 'Poste de Vigie', 'sec' => 'sec-military', 'icon' => '🔭'],
    'wall' => ['name' => 'Muraille & Remparts', 'sec' => 'sec-military', 'icon' => '🧱'],
    'research_lab' => ['name' => 'Académie des Savoirs', 'sec' => 'sec-science', 'icon' => '📜'],
    'embassy' => ['name' => 'Pavillon Diplomatique', 'sec' => 'sec-science', 'icon' => '⛩️'],
    'storage' => ['name' => 'Greniers de Cèdre & Pierre', 'sec' => 'sec-logistics', 'icon' => '🪵'],
    'tank' => ['name' => 'Silo à Riz Impérial', 'sec' => 'sec-logistics', 'icon' => '🌾'],
    'quantum_vault' => ['name' => 'Cachette Secrète', 'sec' => 'sec-logistics', 'icon' => '🔒'],
    'market' => ['name' => 'Marché Castral', 'sec' => 'sec-logistics', 'icon' => '⚖️'],
    'sawmill' => ['name' => 'Charpenterie (Kizukuri)', 'sec' => 'sec-logistics', 'icon' => '🪵'],
    'stonemason' => ['name' => 'Taille de Granit', 'sec' => 'sec-logistics', 'icon' => '🪨'],
    'grain_mill' => ['name' => 'Meunerie de Riz', 'sec' => 'sec-logistics', 'icon' => '🍶'],
    'blacksmith' => ['name' => 'Grande Forge Tamahagane', 'sec' => 'sec-military', 'icon' => '⚔️'],
    'teahouse' => ['name' => 'Pavillon de Thé', 'sec' => 'sec-science', 'icon' => '🍵'],
    'tournament_square' => ['name' => 'Place d\'Exercices', 'sec' => 'sec-military', 'icon' => '🎯'],
    'free_plot' => ['name' => 'Terrain Vierge', 'sec' => 'sec-logistics', 'icon' => '⛳'],
];

$sectorInfo = $buildingSectors[$code] ?? ['name' => 'Bâtiment Castral', 'sec' => 'sec-logistics', 'icon' => '🏯'];

// File active de construction
$queue = $buildingEngine->getQueue((int)$planet['id']);
$activeJob = null;
$fieldsInQueue = 0;
$buildingsInQueue = 0;

foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $fieldsInQueue++;
    } else {
        $buildingsInQueue++;
        if ($q['target_id'] === $code) {
            $activeJob = $q;
        }
    }
}

$isTerran = ($user['faction'] === 'terran');
$canQueueNewBuilding = $isTerran ? ($buildingsInQueue === 0) : (count($queue) === 0);

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
            $canAfford = ($planet['metal'] >= $cost['metal'] && $planet['crystal'] >= $cost['crystal'] && $planet['deuterium'] >= $cost['deuterium']);
            $availableBuildingsToConstruct[$bCode] = [
                'info' => $bInfo,
                'details' => $details,
                'can_afford' => $canAfford,
                'sector' => $buildingSectors[$bCode] ?? ['name' => 'Logistique', 'sec' => 'sec-logistics', 'icon' => '📦']
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
    $hasMetal = $planet['metal'] >= $cost['metal'];
    $hasCrystal = $planet['crystal'] >= $cost['crystal'];
    $hasDeut = $planet['deuterium'] >= $cost['deuterium'];
    $canAfford = $hasMetal && $hasCrystal && $hasDeut;

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
            'label' => 'Prestige Diplomatique',
            'cur' => 'Rang ' . $lvl,
            'next' => 'Rang ' . $targetLevel,
            'gain' => '+1 rang de clan féodal'
        ],
        'market' => [
            'label' => 'Marchands & Convois',
            'cur' => ($lvl * 2) . ' marchands',
            'next' => ($targetLevel * 2) . ' marchands',
            'gain' => '+2 caravanes marchandes'
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
            <h2 class="page-title">
                <?php if ($isEmptyPlot): ?>
                    ⛳ Terrain Castral #<?= $slot ?>
                    <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">Terrain Libre</span>
                <?php else: ?>
                    <?= $bInfo['icon'] ?? '🏯' ?> <?= htmlspecialchars($bInfo['name']) ?>
                    <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">Slot #<?= $slot ?> · Niv.<?= $lvl ?></span>
                    <?php if ($activeJob): ?>
                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem; vertical-align:middle;">⏳ Chantier en cours</span>
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
                    🏯 Vue Cité
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
                        <div class="col-auto"><span class="avatar rounded bg-blue-lt" style="font-size:1.3rem;">⛳</span></div>
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
                        <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;">✨</span></div>
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
                        <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;">🏯</span></div>
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
                        <div class="col-auto"><span class="avatar rounded bg-info-lt" style="font-size:1.3rem;">🏗️</span></div>
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
                        <div class="col-auto"><span class="avatar rounded" style="background:rgba(220,38,38,0.12); font-size:1.3rem;"><?= $bInfo['icon'] ?? '🏯' ?></span></div>
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
                        <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;">📈</span></div>
                        <div class="col">
                            <div class="font-weight-medium">Niveau <?= $targetLevel ?> → Rendement</div>
                            <div class="text-success"><?= $statData['next'] ?> <span class="text-muted" style="font-size:0.75rem;">(<?= $statData['gain'] ?>)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-info-lt" style="font-size:1.3rem;">⏱️</span></div>
                        <div class="col">
                            <div class="font-weight-medium">Temps de construction</div>
                            <div class="text-info font-monospace"><?= gmdate('H:i:s', $duration) ?></div>
                            <div class="text-muted" style="font-size:0.72rem;">Tenshu Niv.<?= $hqLevel ?> &bull; Bâtisseurs du clan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="row align-items-center w-100 g-2">
                        <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;"><?= $sectorInfo['icon'] ?? '🏯' ?></span></div>
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
        <!-- Colonne Gauche : Information Terrain & Mini-Grille -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div style="height:140px; background:linear-gradient(135deg,rgba(71,85,105,0.15),rgba(30,41,59,0.08)); display:flex; align-items:center; justify-content:center; font-size:3.5rem;">
                    ⛳
                </div>
                <div class="card-body">
                    <h3 class="card-title mb-1">Emplacement Castral #<?= $slot ?></h3>
                    <div class="text-muted mb-3" style="font-size:0.8rem;">Cour intérieure &bull; Domaine de <?= htmlspecialchars($planet['name']) ?></div>
                    <p class="text-muted" style="font-size:0.875rem;">
                        Cet emplacement est viabilisé au sein de l'enceinte de votre forteresse. Au fur et à mesure de l'élévation de votre Tenshu, de nouvelles structures féodales pourront y être fondées.
                    </p>
                    <a href="/?page=city" class="btn btn-outline-secondary w-100 mt-2">
                        ← Revenir à la Cité Castrale
                    </a>
                </div>
            </div>

            <!-- Mini-grille des emplacements de la cité -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🗺️ Emplacements de la Cité</h3>
                    <div class="card-options text-muted" style="font-size:0.78rem;">Slots #19 à #34</div>
                </div>
                <div class="card-body p-2">
                    <div class="row g-1">
                        <?php for ($s = 19; $s <= 34; $s++):
                            $sData = $citySlots[$s] ?? ['slot' => $s, 'code' => 'free_plot', 'level' => 0];
                            $sCode = $sData['code'];
                            $sLvl  = (int)$sData['level'];
                            $isCurrent = ($s === $slot);
                            $sInfo = BUILDINGS[$sCode] ?? null;
                            $sTile = $sInfo['tile_img'] ?? 'tile_tenshu.png';
                            $sTileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $sTile)
                                ? '/public/assets/tiles/' . $sTile
                                : '/public/assets/' . $sTile;
                            $sName = $sInfo['name'] ?? ($sCode === 'free_plot' ? 'Terrain Libre' : $sCode);
                        ?>
                        <div class="col-auto">
                            <a href="/?page=building&slot=<?= $s ?>"
                               title="<?= htmlspecialchars($sName) ?> #<?= $s ?><?= ($sCode !== 'free_plot') ? ' (Niv.' . $sLvl . ')' : '' ?>"
                               style="display:block; text-align:center; width:44px; text-decoration:none;">
                                <div style="width:40px; height:40px; border-radius:6px; overflow:hidden; margin:0 auto;
                                            border:2px solid <?= $isCurrent ? '#dc2626' : 'var(--tblr-border-color)' ?>;
                                            box-shadow:<?= $isCurrent ? '0 0 0 2px rgba(220,38,38,0.3)' : 'none' ?>;
                                            background:rgba(0,0,0,0.05); display:flex; align-items:center; justify-content:center;">
                                    <?php if ($sCode === 'free_plot'): ?>
                                        <span style="font-size:1.1rem; opacity:0.6;">🏗️</span>
                                    <?php else: ?>
                                        <img src="<?= $sTileUrl ?>" alt="" style="width:100%; height:100%; object-fit:cover; opacity:<?= $isCurrent ? '1' : '0.85' ?>;">
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.6rem; color:<?= $isCurrent ? '#dc2626' : 'var(--tblr-text-muted)' ?>; font-weight:<?= $isCurrent ? '800' : '400' ?>; line-height:1.4;">
                                    #<?= $s ?> <?= ($sCode !== 'free_plot') ? 'N' . $sLvl : '—' ?>
                                </div>
                            </a>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Catalogue des Bâtiments Constructibles -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">🏗️ Fonder une Nouvelle Structure Féodale</h3>
                        <div class="text-muted" style="font-size:0.8rem;">Sélectionnez l'édifice à ériger sur l'emplacement #<?= $slot ?></div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($availableBuildingsToConstruct)): ?>
                        <div class="text-center py-4 text-muted">
                            <span style="font-size:2rem;">🏯</span>
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
                                                    <span style="font-size:1.8rem;"><?= $info['icon'] ?? '🏯' ?></span>
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
                                                    🪵 <?= number_format($c['metal']) ?>
                                                </span>
                                                <span class="<?= ($planet['crystal'] >= $c['crystal']) ? 'text-success' : 'text-danger' ?>">
                                                    🪨 <?= number_format($c['crystal']) ?>
                                                </span>
                                                <span class="<?= ($planet['deuterium'] >= $c['deuterium']) ? 'text-success' : 'text-danger' ?>">
                                                    🌾 <?= number_format($c['deuterium']) ?>
                                                </span>
                                                <span class="text-muted">
                                                    ⏱️ <?= $durFormatted ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <?php if ($canAfford && $canQueueNewBuilding): ?>
                                                <button type="button" class="btn btn-primary" onclick="launchBuildingUpgrade('<?= $bCode ?>', 1, <?= $slot ?>)">
                                                    🔨 Bâtir
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
        <!-- Colonne Gauche : Identité Bâtiment & Mini-Grille -->
        <div class="col-lg-5">

            <!-- Card identité du bâtiment -->
            <div class="card mb-3">
                <?php if ($buildingIllustrationUrl): ?>
                    <div class="card-img-top position-relative" style="position:relative; cursor:pointer; overflow:hidden; max-height:220px;" onclick="openArtworkModal('<?= $buildingIllustrationUrl ?>', '<?= htmlspecialchars(addslashes($bInfo['name'])) ?>')" title="Agrandir l'estampe">
                        <img src="<?= $buildingIllustrationUrl ?>" alt="<?= htmlspecialchars($bInfo['name']) ?>" style="width:100%; height:220px; object-fit:cover; display:block;">
                        <span class="badge bg-dark text-white" style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.75) !important; font-size:0.75rem; padding:4px 8px; border-radius:4px; backdrop-filter:blur(3px); border:1px solid rgba(255,255,255,0.3); z-index:2; display:inline-flex; align-items:center; gap:4px;">
                            🔍 Agrandir
                        </span>
                    </div>
                <?php else: ?>
                    <div style="height:120px; background:linear-gradient(135deg,rgba(185,28,28,0.15),rgba(30,41,59,0.1)); display:flex; align-items:center; justify-content:center; font-size:3rem;">
                        <?= $bInfo['icon'] ?? '🏯' ?>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="position-relative me-3" style="cursor:pointer; display:inline-block;" onclick="openArtworkModal('<?= $tileUrl ?>', '<?= htmlspecialchars(addslashes($bInfo['name'])) ?> &bull; Bâtiment')" title="Agrandir le bâtiment">
                            <img src="<?= $tileUrl ?>" alt="<?= htmlspecialchars($bInfo['name']) ?>" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:2px solid var(--tblr-border-color); display:block;">
                            <span class="badge bg-dark text-white position-absolute" style="bottom:-4px; right:-4px; font-size:0.6rem; padding:2px 4px; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.3); pointer-events:none;" title="Agrandir">
                                🔍
                            </span>
                        </div>
                        <div>
                            <h3 class="card-title mb-0"><?= htmlspecialchars($bInfo['name']) ?></h3>
                            <div class="text-muted" style="font-size:0.8rem;">Emplacement #<?= $slot ?> &bull; Niveau actuel : <strong><?= $lvl ?></strong></div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:0.875rem;"><?= htmlspecialchars($bInfo['description']) ?></p>

                    <!-- Raccourcis opérationnels directs -->
                    <?php if ($lvl > 0): ?>
                        <div class="d-flex flex-column gap-2 mb-2">
                            <?php if ($code === 'barracks'): ?>
                                <a href="/?page=barracks" class="btn btn-primary">
                                    🥋 Ouvrir le Dojo d'Entraînement des Troupes &rarr;
                                </a>
                            <?php elseif ($code === 'shipyard'): ?>
                                <a href="/?page=shipyard" class="btn btn-primary">
                                    🐎 Accéder aux Écuries de Cavalerie & Machines &rarr;
                                </a>
                            <?php elseif ($code === 'research_lab'): ?>
                                <a href="/?page=research" class="btn btn-primary">
                                    📜 Consulter l'Académie & Savoirs Féodaux &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bouton Raser (sauf Tenshu) -->
                    <?php if ($code !== 'hq' && $lvl > 0 && !$activeJob): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                onclick="confirmDemolishBuilding('<?= $code ?>', <?= $slot ?>, '<?= htmlspecialchars(addslashes($bInfo['name'] ?? $code)) ?>')">
                            💥 Démanteler le Bâtiment (récupère 30%)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mini-grille des emplacements de la cité -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🗺️ Emplacements de la Cité</h3>
                    <div class="card-options text-muted" style="font-size:0.78rem;">Slots #19 à #34</div>
                </div>
                <div class="card-body p-2">
                    <div class="row g-1">
                        <?php for ($s = 19; $s <= 34; $s++):
                            $sData = $citySlots[$s] ?? ['slot' => $s, 'code' => 'free_plot', 'level' => 0];
                            $sCode = $sData['code'];
                            $sLvl  = (int)$sData['level'];
                            $isCurrent = ($s === $slot);
                            $sInfo = BUILDINGS[$sCode] ?? null;
                            $sTile = $sInfo['tile_img'] ?? 'tile_tenshu.png';
                            $sTileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $sTile)
                                ? '/public/assets/tiles/' . $sTile
                                : '/public/assets/' . $sTile;
                            $sName = $sInfo['name'] ?? ($sCode === 'free_plot' ? 'Terrain Libre' : $sCode);
                        ?>
                        <div class="col-auto">
                            <a href="/?page=building&slot=<?= $s ?>"
                               title="<?= htmlspecialchars($sName) ?> #<?= $s ?><?= ($sCode !== 'free_plot') ? ' (Niv.' . $sLvl . ')' : '' ?>"
                               style="display:block; text-align:center; width:44px; text-decoration:none;">
                                <div style="width:40px; height:40px; border-radius:6px; overflow:hidden; margin:0 auto;
                                            border:2px solid <?= $isCurrent ? '#dc2626' : 'var(--tblr-border-color)' ?>;
                                            box-shadow:<?= $isCurrent ? '0 0 0 2px rgba(220,38,38,0.3)' : 'none' ?>;
                                            background:rgba(0,0,0,0.05); display:flex; align-items:center; justify-content:center;">
                                    <?php if ($sCode === 'free_plot'): ?>
                                        <span style="font-size:1.1rem; opacity:0.6;">🏗️</span>
                                    <?php else: ?>
                                        <img src="<?= $sTileUrl ?>" alt="" style="width:100%; height:100%; object-fit:cover; opacity:<?= $isCurrent ? '1' : '0.85' ?>;">
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.6rem; color:<?= $isCurrent ? '#dc2626' : 'var(--tblr-text-muted)' ?>; font-weight:<?= $isCurrent ? '800' : '400' ?>; line-height:1.4;">
                                    #<?= $s ?> <?= ($sCode !== 'free_plot') ? 'N' . $sLvl : '—' ?>
                                </div>
                            </a>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Coûts, Chantier & Zone Tenshu -->
        <div class="col-lg-7">

            <!-- Coûts d'amélioration -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">🧱 Coûts d'Amélioration &bull; Niveau <?= $targetLevel ?></h3>
                    <div class="card-options text-muted" style="font-size:0.78rem;">Stock disponible dans vos greniers</div>
                </div>
                <div class="card-body">
                    <?php
                    $resources = [
                        ['icon'=>'🪵', 'name'=>'Bois de Cèdre',   'req'=>$cost['metal'],     'stock'=>$planet['metal'],     'ok'=>$hasMetal],
                        ['icon'=>'🪨', 'name'=>'Pierre de Taille', 'req'=>$cost['crystal'],   'stock'=>$planet['crystal'],   'ok'=>$hasCrystal],
                        ['icon'=>'🌾', 'name'=>'Riz Impérial',     'req'=>$cost['deuterium'], 'stock'=>$planet['deuterium'], 'ok'=>$hasDeut],
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
                            <?= $r['ok'] ? '✓' : '✗' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>

                    <!-- Durée -->
                    <div class="alert alert-info mt-3 mb-0" style="padding:0.6rem 0.85rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span>⏱️ Durée des travaux :</span>
                            <strong class="font-monospace"><?= gmdate('H:i:s', $duration) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Décret de Construction -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">🔨 Décret de Construction Castral</h3>
                    <?php if ($isTerran): ?>
                        <div class="card-options"><span class="badge bg-blue-lt">Clan Oda &bull; Double Chantier</span></div>
                    <?php endif; ?>
                </div>
                <div class="card-body">

                    <?php if ($activeJob): ?>
                        <?php $isDemolishingJob = ((int)$activeJob['target_level'] === 0); ?>
                        <div class="alert alert-<?= $isDemolishingJob ? 'danger' : 'warning' ?> mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span style="font-size:1.3rem;">⏳</span>
                                <div>
                                    <strong><?= $isDemolishingJob ? 'Démantèlement en cours' : 'Travaux en cours' ?></strong> &rarr;
                                    <?= $isDemolishingJob ? 'Niveau 0 (Raser)' : 'Niveau ' . $activeJob['target_level'] ?>
                                </div>
                            </div>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">
                                <?= $isDemolishingJob
                                    ? 'Vos maîtres d\'œuvre déconstruisent cette bâtisse pour libérer l\'emplacement. Vous récupérerez 30% des matériaux.'
                                    : 'Vos bâtisseurs et charpentiers travaillent sur cette bâtisse. La forteresse bénéficiera de ses nouvelles capacités dès achèvement.' ?>
                            </p>
                            <div class="font-monospace fw-bold fs-4 text-center py-2 job-timer-display" data-countdown="<?= $activeJob['finishes_at'] ?>">
                                Calcul...
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger w-100" onclick="cancelBuildingBuild(<?= (int)$activeJob['id'] ?>)">
                            🛑 <?= $isDemolishingJob ? 'Interrompre le démantèlement' : 'Interrompre les travaux (80% remboursé)' ?>
                        </button>

                    <?php elseif (!$canQueueNewBuilding): ?>
                        <div class="alert alert-secondary mb-3">
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">🏗️</div>
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
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">⚠️</div>
                            <h4 class="alert-title">Matériaux Insuffisants</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Vos greniers ne disposent pas encore de la quantité requise de bois, de pierre ou de riz pour ce chantier castral.
                            </p>
                            <?php if ($missingWaitSeconds > 0): ?>
                                <div class="mt-2 text-center">
                                    <span class="badge bg-warning text-dark">⏳ Matériaux réunis dans : <?= gmdate('H:i:s', $missingWaitSeconds) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary w-100" disabled style="opacity:0.5; cursor:not-allowed;">
                            <?= ($lvl === 0) ? '🔨 Construire au Niveau 1' : '⚡ Améliorer au Niveau ' . $targetLevel ?>
                        </button>

                    <?php else: ?>
                        <div class="alert alert-success mb-3">
                            <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">🏯</div>
                            <h4 class="alert-title">Ordre de Travaux Prêt</h4>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Les maîtres-artisans ont dressé les plans. Vous pouvez ordonner l'élévation du <strong>Niveau <?= $targetLevel ?></strong>.
                            </p>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg w-100" id="btnLaunchBuildingUpgrade" onclick="launchBuildingUpgrade('<?= $code ?>', <?= $targetLevel ?>, <?= $slot ?>)">
                            <?= ($lvl === 0) ? '🔨 Ériger au Niveau 1' : '⚡ Élever au Niveau ' . $targetLevel ?>
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
                            <div class="text-danger fw-bold" style="font-size:0.75rem; text-transform:uppercase;">🏯 Siège du Commandement</div>
                            <h3 class="card-title mb-0">📜 Devise & Chronique du Daimyō</h3>
                        </div>
                        <button type="button" onclick="openEditMottoModal()" class="btn btn-danger btn-sm">
                            ✏️ Modifier ma Devise
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
                btn.innerText = (targetLevel === 1 ? '🔨 Ériger au Niveau 1' : '⚡ Élever au Niveau ' + targetLevel);
            }
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur castral.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerText = (targetLevel === 1 ? '🔨 Ériger au Niveau 1' : '⚡ Élever au Niveau ' + targetLevel);
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

// Mise à jour des comptes à rebours
function updateBuildingCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-countdown]').forEach(el => {
        const target = parseInt(el.getAttribute('data-countdown'), 10);
        const diff = target - now;
        if (diff <= 0) {
            el.innerText = 'Travaux achevés ! Actualisation...';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            const d = Math.floor(diff / 86400);
            const h = Math.floor((diff % 86400) / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            const timeStr = (d > 0) 
                ? `${d}j ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
                : `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            el.innerText = `⏳ Temps restant : ${timeStr}`;
        }
    });
}
setInterval(updateBuildingCountdowns, 1000);
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
</script>

<!-- MODALE LIGHTBOX ESTAMPE HD -->
<div id="artworkModal" class="modal-overlay" style="display:none;" onclick="closeArtworkModal(event)">
    <div class="modal-card modal-card-lg" style="max-width:960px; padding:1.5rem;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--tblr-border-color); padding-bottom:0.75rem; margin-bottom:1rem;">
            <h3 id="artworkModalTitle" style="margin:0; font-size:1.1rem; font-weight:800;">🎨 Estampe Féodale Authentique</h3>
            <button type="button" class="btn-close" onclick="closeArtworkModal()"></button>
        </div>
        <div style="text-align:center;">
            <img id="artworkModalImg" src="" alt="Estampe" style="width:100%; height:auto; max-height:75vh; object-fit:contain; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        </div>
    </div>
</div>


