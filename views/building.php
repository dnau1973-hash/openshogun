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

require_once __DIR__ . '/../core/ImperialSealEngine.php';
$sealEngine = new ImperialSealEngine();
$isSealActive = $sealEngine->isSealActive((int)$user['id']);

$isTerran = ($user['faction'] === 'terran');
$maxAllowedBuildings = $isSealActive ? 2 : 1;
$maxAllowedTotal = $isSealActive ? 3 : 1;
$canQueueNewBuilding = $isTerran ? ($buildingsInQueue < $maxAllowedBuildings) : (count($queue) < $maxAllowedTotal);

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
                            <?php elseif ($code === 'embassy'): ?>
                                <a href="/?page=alliance" class="btn btn-primary">
                                    🎌 Ouvrir le Pavillon des Alliances Féodales &rarr;
                                </a>
                            <?php elseif ($code === 'grain_mill'): ?>
                                <a href="#craftSection" class="btn btn-success text-white">
                                    🍶 Accéder à la Minoterie &amp; Cuves de Saké &darr;
                                </a>
                            <?php elseif ($code === 'sawmill' && $lvl >= 10): ?>
                                <a href="#craftSection" class="btn btn-warning text-dark fw-bold">
                                    🪚 Accéder au Façonnage de Poutres &darr;
                                </a>
                            <?php elseif ($code === 'hq'): ?>
                                <a href="#feastSection" class="btn btn-warning text-dark fw-bold">
                                    🍶 Salle des Banquets &amp; Célébrations &darr;
                                </a>
                            <?php elseif ($code === 'market'): ?>
                                <a href="#marketSection" class="btn btn-warning text-dark fw-bold">
                                    ⚖️ Accéder au Marché &amp; Convois de Marchandises &darr;
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
                            <span>👥</span> Démographie &amp; Ouvriers du Domaine Castral
                        </h3>
                        <div class="text-secondary small mt-1">
                            La population d'artisans, bûcherons et fermiers est logée par vos édifices et soutenue par la farine de riz.
                        </div>
                    </div>
                    <span class="badge bg-blue-lt fw-bold">
                        Bonus Chantiers : +<?= $popBonus ?>% vitesse
                    </span>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-center g-3 mb-2">
                        <div class="col-sm-4 text-center border-end">
                            <div class="text-secondary small">Habitants Actuels</div>
                            <div class="fs-3 fw-bold text-primary"><?= number_format($pop) ?></div>
                            <div class="text-muted small">/ <?= number_format($popMax) ?> logements</div>
                        </div>
                        <div class="col-sm-4 text-center border-end">
                            <div class="text-secondary small">⚡ Bonus Bâtisseurs</div>
                            <div class="fs-3 fw-bold text-success">+<?= $popBonus ?>%</div>
                            <div class="text-muted small">vitesse de construction</div>
                        </div>
                        <div class="col-sm-4 text-center">
                            <div class="text-secondary small">🍚 Rations de Farine</div>
                            <div class="fs-3 fw-bold text-dark"><?= number_format((int)($planet['rice_flour'] ?? 0)) ?></div>
                            <div class="text-muted small">subsistance assurée</div>
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
                            <span>🍶</span> Salle des Banquets &amp; Célébrations Féodales du Tenshu
                        </h3>
                        <div class="text-secondary small mt-1">
                            Organisez des réceptions et fêtes grâce à vos réserves de Saké. Les festivités et leurs bienfaits sont intimement corrélés à l'élévation de votre Tenshu (Niveau <?= $tenshuLvl ?>).
                        </div>
                    </div>
                    <span class="badge bg-warning-lt fw-bold">
                        Stock Saké : <?= number_format((int)$currentSake) ?> 🍶
                    </span>
                </div>
                <div class="card-body">

                    <?php if ($activeFeast): ?>
                        <?php 
                            $feastNames = [
                                'matsuri' => ['Matsuri Populaire des Saisons', '🏮', 'bg-warning-lt text-warning'],
                                'warriors' => ['Banquet des Guerriers (Kanpai)', '⚔️', 'bg-danger-lt text-danger'],
                                'imperial' => ['Grand Banquet Impérial & Diplomatique', '👑', 'bg-purple-lt text-purple']
                            ];
                            $fInfo = $feastNames[$activeFeast['feast_type']] ?? ['Festivités en cours', '🎉', 'bg-warning-lt text-warning'];
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
                                            <span>🏮</span> Matsuri Saisonnier
                                        </h4>
                                        <span class="badge bg-success-lt">Niv. 1+</span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Ferveur populaire, danses et offrandes aux Kamis pour la prospérité des récoltes.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div>🪵🪨🌾 Prod : <strong class="text-success">+<?= 5 + $tenshuLvl ?>%</strong></div>
                                        <div>⛩️ Sérénité : <strong class="text-info">+<?= 10 + ($tenshuLvl * 2) ?></strong></div>
                                        <div>⏱️ Durée : <strong>8 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $matsuriCost) ? 'text-warning' : 'text-danger' ?>"><?= number_format($matsuriCost) ?> Saké 🍶</strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-warning w-100 text-dark fw-bold btn-sm"
                                        onclick="submitTenshuFeast('matsuri')"
                                        <?= ($activeFeast || $currentSake < $matsuriCost) ? 'disabled' : '' ?>>
                                    🏮 Célébrer le Matsuri
                                </button>
                            </div>
                        </div>

                        <!-- Fête 2 : Banquet des Guerriers -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>⚔️</span> Banquet Guerriers
                                        </h4>
                                        <span class="badge <?= ($tenshuLvl >= 5) ? 'bg-danger-lt text-danger' : 'bg-secondary text-white' ?>">
                                            <?= ($tenshuLvl >= 5) ? 'Niv. 5+' : '🔒 Niv. 5 requis' ?>
                                        </span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Toast d'honneur (Kanpai) aux samouraïs et vétérans pour galvaniser le moral des troupes.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div>🥋 Dojo &amp; Écuries : <strong class="text-danger">-<?= 10 + $tenshuLvl ?>% durée</strong></div>
                                        <div>⚔️ Attaque Garnison : <strong class="text-danger">+5%</strong></div>
                                        <div>⏱️ Durée : <strong>6 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $warriorsCost) ? 'text-warning' : 'text-danger' ?>"><?= number_format($warriorsCost) ?> Saké 🍶</strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-danger w-100 fw-bold btn-sm"
                                        onclick="submitTenshuFeast('warriors')"
                                        <?= ($activeFeast || $tenshuLvl < 5 || $currentSake < $warriorsCost) ? 'disabled' : '' ?>>
                                    ⚔️ Lever le Kanpai
                                </button>
                            </div>
                        </div>

                        <!-- Fête 3 : Banquet Impérial -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>👑</span> Banquet Impérial
                                        </h4>
                                        <span class="badge <?= ($tenshuLvl >= 10) ? 'bg-purple-lt' : 'bg-secondary text-white' ?>">
                                            <?= ($tenshuLvl >= 10) ? 'Niv. 10+' : '🔒 Niv. 10 requis' ?>
                                        </span>
                                    </div>
                                    <p class="text-secondary small mb-2">
                                        Réception somptueuse pour les émissaires impériaux et dignitaires des clans alliés.
                                    </p>
                                    <div class="bg-white p-2 rounded border small mb-2">
                                        <div>🎌 Prestige : <strong class="text-purple">+<?= 50 + ($tenshuLvl * 5) ?> Honneur</strong></div>
                                        <div>📜 Savoirs &amp; Alliances : <strong>Éclat suprême</strong></div>
                                        <div>⏱️ Durée : <strong>12 heures</strong></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        Coût : <strong class="<?= ($currentSake >= $imperialCost) ? 'text-warning' : 'text-danger' ?>"><?= number_format($imperialCost) ?> Saké 🍶</strong>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-purple w-100 text-white fw-bold btn-sm" style="background:#7c3aed;"
                                        onclick="submitTenshuFeast('imperial')"
                                        <?= ($activeFeast || $tenshuLvl < 10 || $currentSake < $imperialCost) ? 'disabled' : '' ?>>
                                    👑 Décréter le Banquet
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
            $canEnqueue = count($craftQueue) < $maxCraftQueue;
            $famineUpkeep = $planetEngine->getEliteUnitsUpkeep((int)$planet['id']);
            ?>
            <!-- ========================================================
                 ATELIER DE RAFFINAGE : MOUTURE DE FARINE & BRASSERIE DE SAKÉ
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #166534;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-success d-flex align-items-center gap-2 m-0">
                            <span>🍶</span> Minoterie &amp; Brasserie Féodale (Sakagura)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Raffinez le riz brut récolté dans vos rizières pour élaborer de la farine fine et du saké traditionnel d'exception.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm" title="File de tâches automatique active grâce au Sceau Impérial">
                                👑 Sceau Impérial : File de raffinage (<?= count($craftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <a href="?page=privilege" class="badge bg-secondary-lt fw-bold text-decoration-none" title="Décrétez le Sceau Impérial pour débloquer jusqu'à 4 commandes en file continue !">
                                ⛩️ File Simple : <?= count($craftQueue) ?>/1 (👑 Sceau : File x4)
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-success-lt fw-bold">
                            Rendement : +<?= (int)($lvl * 2) ?>% (Niveau <?= $lvl ?>)
                        </span>
                        <span class="badge bg-info-lt fw-bold">
                            Vitesse : +<?= (int)($lvl * 15) ?>%
                        </span>
                        <?php if ($famineUpkeep['famine_enabled']): ?>
                            <span class="badge bg-danger-lt fw-bold" title="Rations horaires requises pour maintenir vos troupes d'élite">
                                🍚 Rations Élite : <?= $famineUpkeep['flour_consumption_per_hour'] ?> farine/h
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($famineUpkeep['famine_enabled'] && !empty($planet['famine_active'])): ?>
                    <div class="alert alert-danger mb-3 p-3 border-danger shadow-sm" style="border-left: 5px solid #dc2626; background: #fef2f2;">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-1">💀</span>
                            <div>
                                <h4 class="m-0 fw-bold text-danger">⚠️ Famine Déclarée : Rations Épuisées !</h4>
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
                            <span>🛡️</span>
                            <span><strong>Régiments d'élite en garnison :</strong> <?= $famineUpkeep['elite_units_count'] ?> guerriers (consommation : <strong><?= $famineUpkeep['flour_consumption_per_hour'] ?> farine 🍚 / heure</strong>).</span>
                        </div>
                        <span class="badge bg-warning text-dark">Péril de Famine Actif</span>
                    </div>
                    <?php endif; ?>

                    <!-- Lot 1 : En cours d'élaboration -->
                    <?php if ($activeCraft): ?>
                    <div class="alert alert-primary mb-3 p-3 border-primary shadow-sm" style="border-left: 5px solid #206bc4; background: #f0f7ff;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1"><?= $activeCraft['product'] === 'sake' ? '🍶' : '🍚' ?></span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 fw-bold text-dark">
                                            <?= $activeCraft['product'] === 'sake' ? 'Brassage de Saké en cuve...' : 'Mouture de Farine de Riz en cours...' ?>
                                        </h4>
                                        <span class="badge bg-primary text-white">Lot Actif (#1)</span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Production prévue : <strong class="text-primary">+<?= number_format((int)$activeCraft['produced_amount']) ?> <?= $activeCraft['product'] === 'sake' ? 'Saké 🍶' : 'Farine 🍚' ?></strong>
                                        &bull; Riz engagé : <strong><?= number_format((int)$activeCraft['rice_amount']) ?> 🌾</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-1">
                                <span class="badge bg-primary text-white font-monospace fs-5 py-2 px-3 shadow-sm" data-countdown="<?= $activeCraft['finishes_at'] ?>">
                                    ⏳ En cours...
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelRiceCraft(<?= (int)$activeCraft['id'] ?>)">
                                    ✕ Annuler (Remboursement 80%)
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
                                <span>📋</span> Lots en file d'attente automatique (<?= count($pendingCrafts) ?>)
                            </span>
                            <span class="badge bg-warning-lt text-warning fw-bold">👑 Privilège du Shōgun</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingCrafts as $qIdx => $pCraft): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-secondary-lt fw-bold">#<?= $qIdx + 2 ?></span>
                                    <span class="fs-2"><?= $pCraft['product'] === 'sake' ? '🍶' : '🍚' ?></span>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= $pCraft['product'] === 'sake' ? 'Brassage de Saké Impérial' : 'Mouture de Farine de Riz' ?>
                                        </div>
                                        <div class="text-muted small">
                                            Rendement : <strong class="text-success">+<?= number_format((int)$pCraft['produced_amount']) ?></strong> &bull; Riz réservé : <strong><?= number_format((int)$pCraft['rice_amount']) ?> 🌾</strong>
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
                                        ✕ Annuler
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
                                <div class="text-secondary small">🌾 Riz Brut Disponible</div>
                                <div class="fs-4 fw-bold text-success" id="craft_avail_rice"><?= number_format((int)$planet['deuterium']) ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small">🍚 Farine en Réserve</div>
                                <div class="fs-4 fw-bold text-dark" id="craft_avail_flour"><?= number_format((int)($planet['rice_flour'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['rice_flour_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small">🍶 Saké en Réserve</div>
                                <div class="fs-4 fw-bold text-warning" id="craft_avail_sake"><?= number_format((int)($planet['sake'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['sake_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deux colonnes d'ateliers : Farine & Saké -->
                    <div class="row g-3">
                        <!-- Atelier 1 : Farine de Riz -->
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>🍚</span> Mouture de Farine
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
                                            <strong class="text-primary fs-5" id="preview_flour_gain">+20 🍚</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de mouture :</span>
                                            <span class="fw-bold font-monospace" id="preview_flour_time">⏱️ --</span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$canEnqueue): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                    ⏳ File saturée (<?= count($craftQueue) ?>/<?= $maxCraftQueue ?> lots)
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-success w-100 fw-bold" onclick="submitRiceCraft('rice_flour')">
                                    <?= empty($craftQueue) ? '🍚 Lancer la Mouture de Farine' : '➕ Ajouter à la File (#'.(count($craftQueue)+1).'/'.$maxCraftQueue.')' ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Atelier 2 : Saké Féodal -->
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between" style="background:#fafaf9;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                            <span>🍶</span> Cuves de Saké (Sakagura)
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
                                            <strong class="text-warning fs-5" id="preview_sake_gain">+10 🍶</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                            <span>Durée de fermentation :</span>
                                            <span class="fw-bold font-monospace" id="preview_sake_time">⏱️ --</span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$canEnqueue): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                    ⏳ File saturée (<?= count($craftQueue) ?>/<?= $maxCraftQueue ?> lots)
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-warning w-100 fw-bold text-dark" onclick="submitRiceCraft('sake')">
                                    <?= empty($craftQueue) ? '🍶 Déclencher le Brassage du Saké' : '➕ Ajouter à la File (#'.(count($craftQueue)+1).'/'.$maxCraftQueue.')' ?>
                                </button>
                                <?php endif; ?>
                            </div>
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
            $canEnqueueWood = count($woodCraftQueue) < $maxWoodCraftQueue;
            ?>
            <!-- ========================================================
                 ATELIER DE CHARPENTERIE : FAÇONNAGE DE POUTRES EN BOIS
                 ======================================================== -->
            <div class="card mb-3" id="craftSection" style="border-top: 3px solid #d97706;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-warning-emphasis d-flex align-items-center gap-2 m-0">
                            <span>🪚</span> Atelier de Charpenterie &amp; Façonnage de Poutres (Kizukuri)
                        </h3>
                        <div class="text-secondary small mt-1">
                            Façonnez le Bois de Cèdre brut en poutres maîtresses et madriers d'exception pour vos chantiers monumentaux et fortifications.
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap ms-auto">
                        <span class="badge bg-warning text-dark fw-bold shadow-sm" title="Étage / Niveau actuel du bâtiment">
                            🏛️ Étage <?= $lvl ?> (Niveau <?= $lvl ?>)
                        </span>
                        <?php if ($lvl < 10): ?>
                            <span class="badge bg-secondary text-white fw-bold">
                                🔒 Poutres : Niveau 10 requis (<?= $lvl ?>/10)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-lt fw-bold">
                                ✓ Façonnage de Poutres Actif
                            </span>
                        <?php endif; ?>
                        <?php if ($isSealActive): ?>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm" title="File de tâches automatique active grâce au Sceau Impérial">
                                👑 Sceau Impérial : File de charpente (<?= count($woodCraftQueue) ?>/4)
                            </span>
                        <?php else: ?>
                            <a href="?page=privilege" class="badge bg-secondary-lt fw-bold text-decoration-none" title="Décrétez le Sceau Impérial pour débloquer jusqu'à 4 commandes en file continue !">
                                ⛩️ File Simple : <?= count($woodCraftQueue) ?>/1 (👑 Sceau : File x4)
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
                    <div class="alert alert-warning mb-3 p-3 border-warning shadow-sm" style="border-left: 5px solid #d97706; background: #fffbeb;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1">🪵</span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 fw-bold text-dark">
                                            Façonnage de Poutres en bois en cours...
                                        </h4>
                                        <span class="badge bg-warning text-dark fw-bold">Lot Actif (#1)</span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        Production prévue : <strong class="text-warning-emphasis">+<?= number_format((int)$activeWoodCraft['produced_amount']) ?> Poutres en bois 🪵</strong>
                                        &bull; Bois engagé : <strong><?= number_format((int)($activeWoodCraft['cost_amount'] ?: $activeWoodCraft['rice_amount'])) ?> 🪵</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-1">
                                <span class="badge bg-warning text-dark fw-bold font-monospace fs-5 py-2 px-3 shadow-sm" data-countdown="<?= $activeWoodCraft['finishes_at'] ?>">
                                    ⏳ En cours...
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelWoodCraft(<?= (int)$activeWoodCraft['id'] ?>)">
                                    ✕ Annuler (Remboursement 80%)
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
                                <span>📋</span> Lots en file d'attente automatique (<?= count($pendingWoodCrafts) ?>)
                            </span>
                            <span class="badge bg-warning-lt text-warning fw-bold">👑 Privilège du Shōgun</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingWoodCrafts as $qIdx => $pCraft): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-secondary-lt fw-bold">#<?= $qIdx + 2 ?></span>
                                    <span class="fs-2">🪵</span>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            Façonnage de Poutres en bois
                                        </div>
                                        <div class="text-muted small">
                                            Rendement : <strong class="text-success">+<?= number_format((int)$pCraft['produced_amount']) ?> 🪵</strong> &bull; Bois réservé : <strong><?= number_format((int)($pCraft['cost_amount'] ?: $pCraft['rice_amount'])) ?> 🪵</strong>
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
                                        ✕ Annuler
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Résumé des stocks actuels -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small">🪵 Bois de Cèdre Brut Disponible</div>
                                <div class="fs-4 fw-bold text-warning" id="craft_avail_metal"><?= number_format((int)$planet['metal']) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)$planet['metal_max']) ?> max</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded text-center bg-light">
                                <div class="text-secondary small">🪵 Poutres en bois en Réserve</div>
                                <div class="fs-4 fw-bold text-dark" id="craft_avail_beams"><?= number_format((int)($planet['wooden_beams'] ?? 0)) ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">/ <?= number_format((int)($planet['wooden_beams_max'] ?? 10000)) ?> max</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($lvl < 10): ?>
                    <!-- Atelier Verrouillé : Niveau 10 requis -->
                    <div class="card card-sm border-dashed bg-light-lt p-4 mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-1">🔒</span>
                                <div>
                                    <h4 class="m-0 fw-bold text-dark">
                                        Façonnage de Poutres en bois Verrouillé
                                    </h4>
                                    <div class="text-secondary small mt-1">
                                        L'équarrissage et le rabotage des troncs de cèdre en poutres maîtresses nécessitent un outillage et un savoir-faire avancé. 
                                        Améliorez votre <strong>Atelier de Charpenterie au Niveau 10</strong> pour débloquer la production de poutres.
                                    </div>
                                </div>
                            </div>
                            <div class="ms-auto text-end">
                                <div class="d-inline-flex align-items-center gap-2 px-3 py-2 bg-white rounded border shadow-sm">
                                    <span class="text-muted small">Niveau requis :</span>
                                    <div class="progress" style="width: 140px; height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= min(100, ($lvl / 10) * 100) ?>%;"></div>
                                    </div>
                                    <span class="badge bg-warning-lt text-dark fw-bold"><?= $lvl ?> / 10</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Atelier de Façonnage des Poutres (Débloqué Niveau 10) -->
                    <div class="border rounded p-3" style="background:#fafaf9;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="m-0 fw-bold d-flex align-items-center gap-1 text-dark">
                                <span>🪚</span> Façonnage de Poutres en bois
                            </h4>
                            <span class="badge bg-warning-lt text-warning">10 Bois de Cèdre &rarr; 1 Poutre</span>
                        </div>
                        <p class="text-secondary small mb-3">
                            Équarrissage et rabotage des troncs de cèdre en poutres solides pour les édifices et structures défensives.
                        </p>

                        <div class="row g-3 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark small mb-1">Quantité de Bois de Cèdre à façonner :</label>
                                <div class="input-group mb-2">
                                    <input type="number" id="wood_amount_beams" min="10" step="10" value="100" class="form-control fw-bold text-center" oninput="calcBeamsPreview()" <?= !$canEnqueueWood ? 'disabled' : '' ?>>
                                    <span class="input-group-text small">Bois 🪵</span>
                                </div>
                                <!-- Boutons raccourcis -->
                                <div class="btn-group btn-group-sm w-100">
                                    <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(50)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>50</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(200)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>200</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount(1000)" <?= !$canEnqueueWood ? 'disabled' : '' ?>>1 000</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setWoodBeamsAmount('max')" <?= !$canEnqueueWood ? 'disabled' : '' ?>>Max</button>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>Production estimée :</span>
                                        <strong class="text-warning-emphasis fs-5" id="preview_beams_gain">+10 🪵</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.8rem;">
                                        <span>Durée de façonnage :</span>
                                        <span class="fw-bold font-monospace" id="preview_beams_time">⏱️ --</span>
                                    </div>
                                </div>
                                <?php if (!$canEnqueueWood): ?>
                                <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                    ⏳ File saturée (<?= count($woodCraftQueue) ?>/<?= $maxWoodCraftQueue ?> lots)
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-warning w-100 fw-bold text-dark" onclick="submitWoodCraft('wooden_beams')">
                                    <?= empty($woodCraftQueue) ? '🪚 Lancer le Façonnage de Poutres' : '➕ Ajouter à la File (#'.(count($woodCraftQueue)+1).'/'.$maxWoodCraftQueue.')' ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
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
            $sealEngine = new ImperialSealEngine();
            $isSealActive = $sealEngine->isSealActive((int)$user['id']);
            $allTradeRoutes = $sealEngine->getTradeRoutes((int)$user['id']);
            $marketRoutes = array_filter($allTradeRoutes, function($tr) use ($planet) {
                return (int)$tr['source_planet_id'] === (int)$planet['id'] || (int)$tr['target_planet_id'] === (int)$planet['id'];
            });
            ?>
            <!-- ========================================================
                 SECTION MARCHÉ FÉODAL : CONVOIS DE MARCHANDISES & TROC
                 ======================================================== -->
            <div class="card mb-3" id="marketSection" style="border-top: 3px solid #f59e0b;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title text-warning-emphasis d-flex align-items-center gap-2 m-0">
                            <span>⚖️</span> Marché Féodal &amp; Caravanes de Marchandises
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

                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs px-3" data-bs-toggle="tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-dispatch" class="nav-link active fw-bold text-dark d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="true" role="tab">
                                <span>📦</span> Expédier des Marchandises
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-routes" class="nav-link fw-bold text-cyan d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                                <span>🛣️</span> Routes Commerciales
                                <span class="badge bg-cyan text-white ms-1"><?= count($marketRoutes) ?></span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-market-npc" class="nav-link fw-bold text-purple d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                                <span>⚖️</span> Intendant du Marché (Troc 1:1:1)
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <!-- ONGLET 1 : EXPÉDIER DES MARCHANDISES -->
                        <div class="tab-pane active show" id="tab-market-dispatch" role="tabpanel">
                            <?php if ($totalCargoCapacity <= 0): ?>
                            <div class="alert alert-warning mb-3 p-3 border-warning shadow-sm">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-1">🐎</span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Aucun chariot de ravitaillement disponible</h4>
                                            <div class="text-secondary small mt-1">
                                                Pour acheminer des denrées vers un autre domaine, vous devez disposer de <strong>Chariots Légers (5k fret)</strong> ou de <strong>Grands Convois Logistiques (25k fret)</strong> dans vos écuries.
                                            </div>
                                        </div>
                                    </div>
                                    <a href="/?page=shipyard" class="btn btn-warning text-dark fw-bold">
                                        🐎 Forger des Convois aux Écuries &rarr;
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row g-3">
                                <!-- Colonne Gauche : Destination & Ressources -->
                                <div class="col-lg-7">
                                    <div class="border rounded p-3 bg-light mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold text-dark m-0">🏯 Fief Destinataire :</label>
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
                                                            🏯 <?= htmlspecialchars($v['name']) ?> &bull; Coordonnées [<?= $v['coord_x'] ?>|<?= $v['coord_y'] ?>]
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
                                        <h4 class="fw-bold text-dark mb-3">📦 Chargement des Marchandises</h4>

                                        <!-- Bois -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold text-dark small m-0">🪵 Bois de Cèdre</label>
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
                                                <label class="form-label fw-bold text-dark small m-0">🪨 Pierre de Taille</label>
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
                                                <label class="form-label fw-bold text-dark small m-0">🌾 Riz Impérial</label>
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
                                                <span>📋</span> Feuille de Route du Convoi
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
                                                    <span class="fw-bold text-warning" id="mkt_fuel_needed_display">~5 Koku 🌾</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span class="text-muted">Durée estimée aller :</span>
                                                    <span class="fw-bold font-monospace text-dark" id="mkt_duration_display">⏱️ ~calcul...</span>
                                                </div>
                                            </div>

                                            <div id="mkt_alert_status" class="alert alert-info py-2 px-3 small mb-3">
                                                Sélectionnez un fief de destination et indiquez les quantités de ressources à convoyer.
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-warning text-dark fw-bold w-100 py-3 shadow-sm" id="btnDispatchMarketCargo" onclick="submitMarketCargoDispatch()">
                                            🚀 Affréter et Expédier le Convoi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ONGLET 2 : ROUTES COMMERCIALES -->
                        <div class="tab-pane" id="tab-market-routes" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h4 class="m-0 fw-bold text-dark">🛣️ Routes Commerciales liées à ce Marché</h4>
                                    <div class="text-muted small">Convois de ravitaillement programmés au départ ou à destination de ce fief.</div>
                                </div>
                                <?php if ($isSealActive): ?>
                                    <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-cyan text-white fw-bold btn-sm">
                                        ➕ Gérer les Routes dans la Cour du Shōgun &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isSealActive): ?>
                                <div class="alert alert-warning p-3 border-warning">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-1">👑</span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Privilège du Sceau Impérial Requis</h4>
                                            <div class="text-secondary small mt-1">
                                                L'automatisation des routes commerciales permet de programmer des convois logistiques récurrents toutes les 1h, 2h, 4h, 8h, etc. sans aucune action manuelle requise.
                                            </div>
                                            <a href="/?page=privilege" class="btn btn-warning text-dark fw-bold btn-sm mt-2">
                                                👑 Décréter le Sceau Impérial &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (empty($marketRoutes)): ?>
                                <div class="text-center py-4 text-muted bg-light rounded border">
                                    <div class="fs-1 mb-1">🛣️</div>
                                    <div class="fw-bold text-dark">Aucune route commerciale active sur ce fief</div>
                                    <div class="small mb-3">Définissez des livraisons récurrentes pour alimenter automatiquement vos provinces.</div>
                                    <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-outline-cyan btn-sm fw-bold">
                                        ➕ Établir une Route Commerciale
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
                                                        <?= $isDepart ? 'Départ 🛫' : 'Arrivée 🛬' ?>
                                                    </span>
                                                    <strong><?= htmlspecialchars($tr['source_name']) ?></strong> &rarr; <strong><?= htmlspecialchars($tr['target_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="small">
                                                        <?php if ($tr['wood'] > 0): ?>🪵 <?= number_format($tr['wood']) ?> <?php endif; ?>
                                                        <?php if ($tr['stone'] > 0): ?>🪨 <?= number_format($tr['stone']) ?> <?php endif; ?>
                                                        <?php if ($tr['rice'] > 0): ?>🌾 <?= number_format($tr['rice']) ?> <?php endif; ?>
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
                                                        ⚙️ Gérer &rarr;
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
                                        <span class="fs-1">⚖️</span>
                                        <div>
                                            <h4 class="m-0 fw-bold text-dark">Intendant du Marché Castral (Troc 1:1:1)</h4>
                                            <div class="text-secondary small mt-1" style="max-width:600px;">
                                                L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au ratio parfait de <strong>1:1:1</strong> sans aucune taxe de déperdition pour un tribut de <strong>3 Koban 🪙</strong>.
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-purple text-white fw-bold shadow-sm" style="background:#7c3aed;"
                                                onclick="openNpcExchangeModal(<?= $planet['id'] ?>, '<?= htmlspecialchars(addslashes($planet['name'])) ?>', <?= (int)$planet['metal'] ?>, <?= (int)$planet['crystal'] ?>, <?= (int)$planet['deuterium'] ?>, <?= (int)$planet['metal_max'] ?>, <?= (int)$planet['crystal_max'] ?>, <?= (int)$planet['deuterium_max'] ?>)">
                                            ⚖️ Procéder au Troc 1:1:1 de ce Fief &rarr;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

                <!-- ZONE EXCLUSIVE DU TENSHU : EXPANSION FÉODALE & COLONS -->
                <?php 
                    $colonStatus = $planetEngine->getTenshuColonizerStatus((int)$planet['id']);
                    $cCosts = $colonStatus['costs'];
                    $canAffordColon = ($planet['metal'] >= $cCosts['metal'] && $planet['crystal'] >= $cCosts['crystal'] && $planet['deuterium'] >= $cCosts['deuterium'] && ($planet['rice_flour'] ?? 0) >= $cCosts['rice_flour']);
                ?>
                <div class="card mb-3" style="border-top: 3px solid #10b981;">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                        <div>
                            <h3 class="card-title text-success d-flex align-items-center gap-2 m-0">
                                <span>⛩️</span> Expansion Coloniale : Pionniers Féodaux (Colons)
                            </h3>
                            <div class="text-secondary small mt-1">
                                Le Tenshu permet de former des <strong>Pionniers Féodaux</strong> aux paliers de niveau <strong>5, 10 et 15</strong> pour annexer de nouveaux villages.
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
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 5) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">1er Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 5) ? '🔓 Débloqué (Niv. 5)' : '🔒 Tenshu Niv. 5' ?></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 10) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">2e Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 10) ? '🔓 Débloqué (Niv. 10)' : '🔒 Tenshu Niv. 10' ?></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded border <?= ($tenshuLvl >= 15) ? 'border-success bg-success-lt' : 'bg-light text-muted' ?>">
                                    <div class="fw-bold" style="font-size:0.8rem;">3e Fief</div>
                                    <div class="small"><?= ($tenshuLvl >= 15) ? '🔓 Débloqué (Niv. 15)' : '🔒 Tenshu Niv. 15' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Récapitulatif d'utilisation -->
                        <div class="bg-surface p-3 rounded border mb-3 small">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <div>🏯 Fiefs annexés par ce Tenshu : <strong><?= $colonStatus['colonies_count'] ?></strong></div>
                                    <?php if (!empty($colonStatus['founded_colonies'])): ?>
                                        <ul class="mb-0 ps-3 mt-1 text-muted" style="font-size:0.75rem;">
                                            <?php foreach ($colonStatus['founded_colonies'] as $fc): ?>
                                                <li><?= htmlspecialchars($fc['name']) ?> [<?= $fc['coord_x'] ?>|<?= $fc['coord_y'] ?>]</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6">
                                    <div>⛩️ Colons en garnison dans ce fief : <strong><?= $colonStatus['stationed_colons'] ?></strong></div>
                                    <div>🏇 Colons en marche / expédition : <strong><?= $colonStatus['in_mission_colons'] ?></strong></div>
                                    <?php if ($colonStatus['queued_colons'] > 0): ?>
                                        <div>⏳ Colons en cours de formation : <strong><?= $colonStatus['queued_colons'] ?></strong></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recrutement -->
                        <?php if ($colonStatus['available_slots'] > 0): ?>
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark">Équiper 1 Pionnier Féodal</strong>
                                    <span class="text-muted small">⏳ <?= gmdate("H:i:s", $colonStatus['train_time']) ?> de préparation</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:0.8rem;">
                                    <span class="badge bg-secondary-lt">🪵 <?= number_format($cCosts['metal']) ?> Bois</span>
                                    <span class="badge bg-secondary-lt">🪨 <?= number_format($cCosts['crystal']) ?> Pierre</span>
                                    <span class="badge bg-secondary-lt">🌾 <?= number_format($cCosts['deuterium']) ?> Riz</span>
                                    <span class="badge bg-secondary-lt">🌾 <?= number_format($cCosts['rice_flour']) ?> Farine de Riz</span>
                                </div>
                                <button type="button" class="btn btn-success w-100 fw-bold" onclick="trainColonizer()" <?= !$canAffordColon ? 'disabled' : '' ?>>
                                    ⛩️ Former 1 Pionnier Féodal (Colon)
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
                            <span>👑</span> Statut &amp; Souveraineté du Fief
                        </h3>
                        <?php if (!empty($planet['is_capital'])): ?>
                            <span class="badge bg-warning text-dark fw-bold">👑 Capitale Officielle</span>
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
                                        ✏️ Renommer
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
                                            ✓ Ce fief est votre Capitale
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning w-100 fw-bold" onclick="proclaimCapital()">
                                            👑 Définir comme Capitale Officielle
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

// ==========================================
// ATELIER DE RAFFINAGE & CHARPENTERIE
// ==========================================
const grainMillLevel = <?= ($code === 'grain_mill') ? (int)$lvl : (int)($buildings['grain_mill'] ?? 0) ?>;
const sawmillLevel = <?= ($code === 'sawmill') ? (int)$lvl : (int)($buildings['sawmill'] ?? 0) ?>;
const availableRiceStock = <?= (float)($planet['deuterium'] ?? 0) ?>;
const availableWoodStock = <?= (float)($planet['metal'] ?? 0) ?>;
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
    if (previewGain) previewGain.textContent = '+' + gain.toLocaleString('fr-FR') + ' 🍚';

    if (previewTime) {
        if (rice <= 0) {
            previewTime.textContent = '⏱️ --';
        } else {
            const duration = Math.max(10, Math.round((rice * 0.4) / (1 + (grainMillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.textContent = '⏱️ ' + formatCraftDuration(duration);
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
    if (previewGain) previewGain.textContent = '+' + gain.toLocaleString('fr-FR') + ' 🍶';

    if (previewTime) {
        if (rice <= 0) {
            previewTime.textContent = '⏱️ --';
        } else {
            const duration = Math.max(15, Math.round((rice * 0.8) / (1 + (grainMillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.textContent = '⏱️ ' + formatCraftDuration(duration);
        }
    }
}

function setRiceFlourAmount(val) {
    const input = document.getElementById('rice_amount_flour');
    if (!input) return;
    if (val === 'max') {
        input.value = Math.floor(availableRiceStock);
    } else {
        input.value = val;
    }
    calcFlourPreview();
}

function setRiceSakeAmount(val) {
    const input = document.getElementById('rice_amount_sake');
    if (!input) return;
    if (val === 'max') {
        input.value = Math.floor(availableRiceStock);
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

    if (amount > availableRiceStock) {
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
    if (previewGain) previewGain.textContent = '+' + gain.toLocaleString('fr-FR') + ' 🪵';

    if (previewTime) {
        if (wood <= 0) {
            previewTime.textContent = '⏱️ --';
        } else {
            const duration = Math.max(10, Math.round((wood * 0.5) / (1 + (sawmillLevel * 0.15)) / (gameSpeed || 1)));
            previewTime.textContent = '⏱️ ' + formatCraftDuration(duration);
        }
    }
}

function setWoodBeamsAmount(val) {
    const input = document.getElementById('wood_amount_beams');
    if (!input) return;
    if (val === 'max') {
        input.value = Math.floor(availableWoodStock);
    } else {
        input.value = val;
    }
    calcBeamsPreview();
}

async function submitWoodCraft(product) {
    if (sawmillLevel < 10) {
        showModalAlert("Le façonnage de poutres en bois n'est accessible qu'à partir du Niveau 10 de l'Atelier de Charpenterie.", 'warning');
        return;
    }
    const input = document.getElementById('wood_amount_beams');
    if (!input) return;
    const amount = parseFloat(input.value) || 0;
    const minAmount = 10;

    if (amount < minAmount) {
        showModalAlert(`La quantité minimale de bois requise est de ${minAmount} unités de Bois de Cèdre.`, 'warning');
        return;
    }

    if (amount > availableWoodStock) {
        showModalAlert(`Vos réserves ne disposent que de ${Math.floor(availableWoodStock).toLocaleString('fr-FR')} unités de Bois de Cèdre.`, 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'craft_wood');
    formData.append('product', product || 'wooden_beams');
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
            showModalAlert(data.error || 'Erreur lors du façonnage.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec la charpenterie.', 'error');
    }
}

async function cancelWoodCraft(craftId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous annuler ce façonnage de poutres en cours ? 80% du bois engagé sera restitué dans vos réserves.',
        'Annulation du façonnage'
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
        "⛩️ Mobilisation de Pionniers Féodaux",
        "Voulez-vous mobiliser vos maîtres bâtisseurs et artisans pour équiper un Pionnier Féodal (Colon) ? Il permettra d'aller annexer un nouveau territoire vierge sur la carte.",
        "⛩️ Former le Colon",
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
        "👑 Proclamation de la Capitale",
        "Êtes-vous certain de vouloir déplacer la Capitale officielle de votre clan vers ce fief ? L'ancien fief capitale deviendra un fief secondaire.",
        "👑 Déclarer Capitale",
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
    fuelNeededDisplay.innerText = `~${fuelReq.toLocaleString('fr-FR')} Koku 🌾`;

    // Durée estimée (vitesse de base convoi = 4000)
    const speed = 4000;
    const durSec = Math.max(3, Math.round((3500 * distance) / (speed * 5)));
    const mins = Math.floor(durSec / 60);
    const secs = durSec % 60;
    durationDisplay.innerText = `⏱️ ~${mins}m ${secs < 10 ? '0' : ''}${secs}s (Dist. ${distance})`;

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

// ==========================================
// INTENDANT DU MARCHÉ (TROC NPC 1:1:1)
// ==========================================
let currentNpcPlanetId = <?= (int)$planet['id'] ?>;
let currentNpcTotal = 0;
let currentNpcMax = { wood: 0, stone: 0, rice: 0 };

function openNpcExchangeModal(planetId, planetName, wood, stone, rice, maxW, maxS, maxR) {
    currentNpcPlanetId = planetId;
    currentNpcTotal = Math.floor(wood + stone + rice);
    currentNpcMax = { wood: maxW, stone: maxS, rice: maxR };

    const nameEl = document.getElementById('npc_planet_name');
    if (nameEl) nameEl.innerText = planetName;
    const totEl = document.getElementById('npc_total_amount');
    if (totEl) totEl.innerText = currentNpcTotal.toLocaleString('fr-FR');

    ['wood', 'stone', 'rice'].forEach(res => {
        const r = document.getElementById(`npc_range_${res}`);
        const inp = document.getElementById(`npc_input_${res}`);
        const val = (res === 'wood') ? wood : ((res === 'stone') ? stone : rice);

        if (r) {
            r.max = Math.min(currentNpcTotal, currentNpcMax[res]);
            r.value = val;
        }
        if (inp) {
            inp.max = currentNpcMax[res];
            inp.value = val;
        }
        const valSpan = document.getElementById(`npc_val_${res}`);
        if (valSpan) valSpan.innerText = val.toLocaleString('fr-FR');
    });

    updateNpcDisplay();
    const modalEl = document.getElementById('modalNpcExchange');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function distributeEvenly() {
    const part = Math.floor(currentNpcTotal / 3);
    const rest = currentNpcTotal - (part * 2);

    ['wood', 'stone'].forEach(res => {
        const inp = document.getElementById(`npc_input_${res}`);
        const r = document.getElementById(`npc_range_${res}`);
        if (inp) inp.value = part;
        if (r) r.value = part;
    });
    const inpR = document.getElementById('npc_input_rice');
    const rR = document.getElementById('npc_range_rice');
    if (inpR) inpR.value = rest;
    if (rR) rR.value = rest;

    updateNpcDisplay();
}

function onNpcRangeChange(type) {
    const r = document.getElementById(`npc_range_${type}`);
    const inp = document.getElementById(`npc_input_${type}`);
    if (r && inp) inp.value = r.value;
    updateNpcDisplay();
}

function onNpcInputChange(type) {
    const inp = document.getElementById(`npc_input_${type}`);
    const r = document.getElementById(`npc_range_${type}`);
    if (inp && r) r.value = inp.value;
    updateNpcDisplay();
}

function updateNpcDisplay() {
    const w = parseInt(document.getElementById('npc_input_wood')?.value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone')?.value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice')?.value || 0, 10);

    const wVal = document.getElementById('npc_wood_val');
    const sVal = document.getElementById('npc_stone_val');
    const rVal = document.getElementById('npc_rice_val');
    if (wVal) wVal.innerText = w.toLocaleString('fr-FR');
    if (sVal) sVal.innerText = s.toLocaleString('fr-FR');
    if (rVal) rVal.innerText = r.toLocaleString('fr-FR');

    const sum = w + s + r;
    const diff = currentNpcTotal - sum;
    const alertEl = document.getElementById('npc_diff_alert');
    const submitBtn = document.getElementById('btnSubmitNpcExchange');

    if (alertEl && submitBtn) {
        if (diff !== 0) {
            alertEl.classList.remove('d-none');
            alertEl.innerText = diff > 0 ? `Il reste ${diff.toLocaleString('fr-FR')} ressources à assigner.` : `Excédent de ${Math.abs(diff).toLocaleString('fr-FR')} ressources réparties en trop.`;
            submitBtn.disabled = true;
        } else {
            alertEl.classList.add('d-none');
            submitBtn.disabled = false;
        }
    }
}

async function submitNpcExchange() {
    const w = parseInt(document.getElementById('npc_input_wood')?.value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone')?.value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice')?.value || 0, 10);

    const fd = new FormData();
    fd.append('action', 'npc_exchange');
    fd.append('planet_id', currentNpcPlanetId);
    fd.append('wood', w);
    fd.append('stone', s);
    fd.append('rice', r);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du troc.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    calcFlourPreview();
    calcSakePreview();
    updateMarketCalculations();
});
</script>

<!-- MODALE INTERACTIVE DU MARCHAND NPC -->
<div class="modal modal-blur fade" id="modalNpcExchange" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>⚖️</span> Intendant du Marché Castral (Troc 1:1:1)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-3">
                    L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au taux parfait de <strong>1:1:1</strong> pour un tribut de <strong>3 Koban 🪙</strong>.
                </div>

                <div class="p-2 bg-light border rounded mb-3 text-center">
                    <span class="text-muted small">Fief sélectionné : </span>
                    <strong id="npc_planet_name" class="text-dark">Fief</strong>
                    <div class="fs-4 fw-bold text-primary mt-1">
                        Total à répartir : <span id="npc_total_amount">0</span>
                    </div>
                    <div class="small text-muted">La somme totale doit être conservée au grain près.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪵 Bois de Cèdre :</span>
                        <span id="npc_wood_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_wood" oninput="onNpcRangeChange('wood')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_wood" oninput="onNpcInputChange('wood')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪨 Pierre de Taille :</span>
                        <span id="npc_stone_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_stone" oninput="onNpcRangeChange('stone')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_stone" oninput="onNpcInputChange('stone')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🌾 Riz Impérial :</span>
                        <span id="npc_rice_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_rice" oninput="onNpcRangeChange('rice')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_rice" oninput="onNpcInputChange('rice')">
                </div>

                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="distributeEvenly()">
                        ⚖️ Répartir Équitablement (1/3 chacun)
                    </button>
                </div>

                <div id="npc_diff_alert" class="alert alert-danger p-2 small d-none">
                    La somme répartie ne correspond pas au total disponible.
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <span class="small text-muted">Coût : <strong class="text-warning">3 Koban</strong></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        🪙 Sceller le Troc (3 Koban)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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


