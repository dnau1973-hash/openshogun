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
                            <?php elseif ($code === 'hq'): ?>
                                <a href="#feastSection" class="btn btn-warning text-dark fw-bold">
                                    🍶 Salle des Banquets &amp; Célébrations &darr;
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
            $craftQueue = $planetEngine->getCraftQueue((int)$planet['id']);
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
// ATELIER DE RAFFINAGE : SAKÉ & FARINE DE RIZ
// ==========================================
const grainMillLevel = <?= (int)($lvl ?? 0) ?>;
const availableRiceStock = <?= (float)($planet['deuterium'] ?? 0) ?>;
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

document.addEventListener('DOMContentLoaded', () => {
    calcFlourPreview();
    calcSakePreview();
});
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


