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

$slotData = $citySlots[$slot] ?? ['slot' => $slot, 'code' => 'free_plot', 'level' => 0];
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
$isEmptyPlot = ($code === 'free_plot' || ($lvl === 0 && !$isBuildingInQueue));

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
];

if (!$isEmptyPlot) {
    $bInfo = BUILDINGS[$code] ?? null;
    $lvl = (int)($buildings[$code] ?? 0);
    $upgradeDetails = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
    $targetLevel = $upgradeDetails['target_level'];
    $cost = $upgradeDetails['cost'];
    $duration = $upgradeDetails['duration'];
    $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';

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

<div class="container field-view-container" style="max-width: 1400px; margin: 0 auto; padding: 1.5rem 1rem;">

    <!-- Barre de Navigation Supérieure (Retour à la Cité + Sélecteur de Slot Urbain) -->
    <div class="field-nav-bar">
        <a href="/?page=city" class="field-back-btn">
            <span>&larr;</span>
            <span>Retour à la Cité Castrale</span>
        </a>

        <div class="field-slot-switcher">
            <a href="/?page=building&slot=<?= $prevSlot ?>" class="field-arrow-btn" title="Bâtiment précédent">
                &larr; Slot #<?= $prevSlot ?>
            </a>
            <div class="field-current-indicator">
                <span class="field-slot-badge">Slot Castral #<?= $slot ?> sur 34</span>
            </div>
            <a href="/?page=building&slot=<?= $nextSlot ?>" class="field-arrow-btn" title="Bâtiment suivant">
                Slot #<?= $nextSlot ?> &rarr;
            </a>
        </div>
    </div>

    <?php if ($isEmptyPlot): ?>
        <!-- CAS : EMPLACEMENT LIBRE / TERRAIN DISPONIBLE -->
        <div class="field-hero-card">
            <div class="field-hero-bg" style="background-image: url('/public/assets/shogun_castle_city_bg.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1 ?>');"></div>
            <div class="field-hero-overlay"></div>

            <div class="field-hero-content">
                <div class="field-tile-stage">
                    <div class="field-tile-pedestal" style="border-style:dashed;">
                        <span style="font-size:3.5rem; opacity:0.6;">🏗️</span>
                    </div>
                    <div class="field-level-emblem" style="background:linear-gradient(135deg, #475569, #334155);">
                        <span class="emblem-lvl-text">TERRAIN</span>
                        <span class="emblem-lvl-number">LIBRE</span>
                    </div>
                </div>

                <div class="field-meta-pane">
                    <div class="field-header-row">
                        <div>
                            <div class="field-type-pill sec-logistics">
                                <span>⛳</span>
                                <span>Emplacement Disponible</span>
                            </div>
                            <h1 class="field-title">Terrain Castral #<?= $slot ?></h1>
                            <span class="field-subtitle">Cour intérieure &bull; Domaine de <?= htmlspecialchars($planet['name']) ?></span>
                        </div>
                        <div class="field-status-badge ready">
                            <span>Terrain prêt à bâtir</span>
                        </div>
                    </div>

                    <p class="field-description">
                        Cet emplacement est viabilisé au sein de l'enceinte de votre forteresse. Au fur et à mesure de l'élévation de votre Tenshu (Donjon Castral), de nouvelles structures féodales pourront y être fondées.
                    </p>

                    <div style="margin-top:1.5rem;">
                        <a href="/?page=city" class="btn btn-secondary">
                            &larr; Explorer les autres édifices de la Cité
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTE DES BÂTIMENTS DISPONIBLES À LA CONSTRUCTION SUR CET EMPLACEMENT -->
        <div class="card" style="margin-top:1.5rem; background:rgba(15,23,42,0.8); border:1px solid var(--border-color); border-radius:12px; padding:1.5rem;">
            <h2 style="font-size:1.2rem; color:#f8fafc; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem;">
                <span>🏗️</span> Fonder une Nouvelle Structure Féodale sur l'Emplacement #<?= $slot ?>
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">
                Sélectionnez le bâtiment féodal de votre choix à ériger sur ce terrain viabilisé de votre forteresse.
            </p>

            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php if (empty($availableBuildingsToConstruct)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1.5rem 0;">Toutes les structures féodales uniques sont déjà érigées dans votre cité castrale.</p>
                <?php else: ?>
                    <?php foreach ($availableBuildingsToConstruct as $bCode => $item): 
                        $info = $item['info'];
                        $det = $item['details'];
                        $c = $det['cost'];
                        $dur = $det['duration'];
                        $canAfford = $item['can_afford'];
                        $durFormatted = sprintf('%02d:%02d', floor($dur / 60), $dur % 60);
                        $bHeroImg = $buildingHeroImages[$bCode] ?? null;
                        $bHeroFile = $bHeroImg ? __DIR__ . '/../public/assets/' . $bHeroImg : null;
                    ?>
                        <div style="background:rgba(30,41,59,0.5); border:1px solid #334155; border-radius:8px; padding:0.85rem; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:260px;">
                                <div style="width:58px; height:44px; background:rgba(15,23,42,0.8); border:1px solid #475569; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.4);">
                                    <?php if ($bHeroFile && file_exists($bHeroFile)): ?>
                                        <img src="/public/assets/<?= $bHeroImg ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php elseif (!empty($info['tile_img'])): ?>
                                        <img src="/public/assets/<?= $info['tile_img'] ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:36px; height:36px; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="font-size:1.5rem;"><?= $info['icon'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="margin:0; font-size:0.95rem; color:#f8fafc;"><?= htmlspecialchars($info['name']) ?></h4>
                                    <p style="margin:0.2rem 0 0 0; font-size:0.75rem; color:#94a3b8; line-height:1.3;">
                                        <?= htmlspecialchars($info['description']) ?>
                                    </p>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                                <div style="display:flex; gap:0.6rem; font-size:0.8rem; font-weight:600;">
                                    <span style="color:<?= ($planet['metal'] >= $c['metal']) ? '#4ade80' : '#ef4444' ?>;" title="Bois de Cèdre">🪵 <?= number_format($c['metal']) ?></span>
                                    <span style="color:<?= ($planet['crystal'] >= $c['crystal']) ? '#4ade80' : '#ef4444' ?>;" title="Pierre de Taille">🪨 <?= number_format($c['crystal']) ?></span>
                                    <span style="color:<?= ($planet['deuterium'] >= $c['deuterium']) ? '#4ade80' : '#ef4444' ?>;" title="Koku de Riz">🌾 <?= number_format($c['deuterium']) ?></span>
                                    <span style="color:#94a3b8;" title="Durée des travaux">⏳ <?= $durFormatted ?></span>
                                </div>

                                <div>
                                    <?php if ($canAfford && $canQueueNewBuilding): ?>
                                        <button type="button" class="field-btn-primary" style="padding:0.4rem 0.9rem; font-size:0.8rem;" onclick="launchBuildingUpgrade('<?= $bCode ?>', 1, <?= $slot ?>)">
                                            🔨 Bâtir
                                        </button>
                                    <?php elseif (!$canAfford): ?>
                                        <button type="button" class="field-btn-primary disabled" disabled style="padding:0.4rem 0.9rem; font-size:0.8rem; opacity:0.5; cursor:not-allowed;">
                                            Matériaux Insuffisants
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="field-btn-primary disabled" disabled style="padding:0.4rem 0.9rem; font-size:0.8rem; opacity:0.5; cursor:not-allowed;">
                                            Chantier Occupé
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- CAS : BÂTIMENT FÉODAL ACTIF / CONSTRUCTIBLE -->
        <div class="field-hero-card">
            <div class="field-hero-bg" style="background-image: url('<?= $heroBgUrl ?>');"></div>
            <div class="field-hero-overlay"></div>

            <div class="field-hero-content">
                <!-- TILE DU BÂTIMENT SUR SON PIÉDESTAL -->
                <div class="field-tile-stage">
                    <div class="field-tile-pedestal">
                        <img src="/public/assets/<?= $tileImg ?>" 
                             class="field-tile-img" 
                             alt="<?= htmlspecialchars($bInfo['name']) ?>" 
                             draggable="false">
                        <div class="field-tile-glow"></div>
                    </div>
                    <div class="field-level-emblem">
                        <span class="emblem-lvl-text">NIVEAU</span>
                        <span class="emblem-lvl-number"><?= $lvl ?></span>
                    </div>
                </div>

                <!-- IDENTITÉ ET DÉTAILS DU BÂTIMENT -->
                <div class="field-meta-pane">
                    <div class="field-header-row">
                        <div>
                            <div class="field-type-pill <?= $sectorInfo['sec'] ?>">
                                <span><?= $bInfo['icon'] ?></span>
                                <span><?= htmlspecialchars($sectorInfo['name']) ?></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; margin-top:0.25rem;">
                                <h1 class="field-title" style="margin:0;"><?= htmlspecialchars($bInfo['name']) ?></h1>
                                <?php if ($buildingIllustrationUrl): ?>
                                    <button type="button" onclick="openArtworkModal('<?= $buildingIllustrationUrl ?>', '<?= htmlspecialchars(addslashes($bInfo['name'])) ?>')" class="btn btn-secondary" style="font-size:0.75rem; padding:0.2rem 0.55rem; border-radius:6px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.3); color:#fde047; cursor:pointer;" title="Agrandir l'illustration artistique en haute définition">
                                        🎨 Estampe HD
                                    </button>
                                <?php endif; ?>
                            </div>
                            <span class="field-subtitle">Emplacement castral #<?= $slot ?> &bull; Cité de <?= htmlspecialchars($planet['name']) ?></span>
                        </div>

                        <?php if ($activeJob): ?>
                            <div class="field-status-badge upgrading">
                                <span class="pulse-dot"></span>
                                <span>Chantier en cours : Niveau <?= $activeJob['target_level'] ?></span>
                            </div>
                        <?php elseif ($lvl > 0): ?>
                            <div class="field-status-badge ready">
                                <span>Statut : Fortifié & Opérationnel</span>
                            </div>
                        <?php else: ?>
                            <div class="field-status-badge ready" style="background:rgba(234, 179, 8, 0.12); color:#b45309; border-color:rgba(234, 179, 8, 0.3);">
                                <span>Statut : Non Bâti</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="field-description">
                        <?= htmlspecialchars($bInfo['description']) ?>
                    </p>

                    <!-- BANDEAU DE RENDEMENT & EFFETS -->
                    <div class="field-quick-stats">
                        <div class="quick-stat-box">
                            <span class="stat-label">Effet Actuel (Niveau <?= $lvl ?>)</span>
                            <span class="stat-value"><?= $statData['cur'] ?></span>
                        </div>
                        <div class="quick-stat-box highlight">
                            <span class="stat-label">Au Niveau <?= $targetLevel ?></span>
                            <span class="stat-value"><?= $statData['next'] ?></span>
                            <span class="stat-gain">(<?= $statData['gain'] ?>)</span>
                        </div>
                    </div>

                    <!-- SHORTCUTS OPÉRATIONNELS DIRECTS -->
                    <?php if ($lvl > 0): ?>
                        <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <?php if ($code === 'barracks'): ?>
                                <a href="/?page=barracks" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    🥋 Ouvrir le Dojo d'Entraînement des Troupes &rarr;
                                </a>
                            <?php elseif ($code === 'shipyard'): ?>
                                <a href="/?page=shipyard" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    🐎 Accéder aux Écuries de Cavalerie & Machines &rarr;
                                </a>
                            <?php elseif ($code === 'research_lab'): ?>
                                <a href="/?page=research" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    📜 Consulter l'Académie & Savoirs Féodaux &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SECTION D'AMÉLIORATION & COÛTS -->
        <div class="field-upgrade-grid">
            
            <!-- Colonne Gauche : Coûts pour le Niveau Suivant -->
            <div class="field-card">
                <div class="field-card-header">
                    <h3>🧱 Coûts d'Amélioration &bull; Niveau <?= $targetLevel ?></h3>
                    <span style="font-size:0.8rem; color:var(--text-muted);">Stock disponible dans vos greniers</span>
                </div>
                <div class="field-card-body">
                    <div class="cost-grid">
                        <!-- Bois -->
                        <div class="cost-item <?= $hasMetal ? 'cost-ok' : 'cost-missing' ?>">
                            <div class="cost-icon">🪵</div>
                            <div class="cost-details">
                                <span class="cost-name">Bois de Cèdre</span>
                                <span class="cost-req"><?= number_format($cost['metal']) ?></span>
                                <span class="cost-stock">Stock : <?= number_format($planet['metal']) ?></span>
                            </div>
                            <div class="cost-check"><?= $hasMetal ? '✓' : '✗' ?></div>
                        </div>

                        <!-- Pierre -->
                        <div class="cost-item <?= $hasCrystal ? 'cost-ok' : 'cost-missing' ?>">
                            <div class="cost-icon">🪨</div>
                            <div class="cost-details">
                                <span class="cost-name">Pierre de Taille</span>
                                <span class="cost-req"><?= number_format($cost['crystal']) ?></span>
                                <span class="cost-stock">Stock : <?= number_format($planet['crystal']) ?></span>
                            </div>
                            <div class="cost-check"><?= $hasCrystal ? '✓' : '✗' ?></div>
                        </div>

                        <!-- Riz -->
                        <div class="cost-item <?= $hasDeut ? 'cost-ok' : 'cost-missing' ?>">
                            <div class="cost-icon">🌾</div>
                            <div class="cost-details">
                                <span class="cost-name">Riz Impérial</span>
                                <span class="cost-req"><?= number_format($cost['deuterium']) ?></span>
                                <span class="cost-stock">Stock : <?= number_format($planet['deuterium']) ?></span>
                            </div>
                            <div class="cost-check"><?= $hasDeut ? '✓' : '✗' ?></div>
                        </div>
                    </div>

                    <!-- Durée des Travaux -->
                    <div class="construction-time-banner">
                        <div class="time-label-group">
                            <span class="time-icon">⏱️</span>
                            <div>
                                <strong>Temps de construction pour le Niveau <?= $targetLevel ?> :</strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                    Réduit par le Tenshu (Donjon Castral Niv. <?= $hqLevel ?>) &bull; Maîtres-bâtisseurs féodaux
                                </div>
                            </div>
                        </div>
                        <div class="time-value-display">
                            <?= gmdate('H:i:s', $duration) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Lancement des Travaux / Chantier -->
            <div class="field-card">
                <div class="field-card-header">
                    <h3>🔨 Décret de Construction Castral</h3>
                    <?php if ($isTerran): ?>
                        <span title="Bonus Clan Oda" style="font-size:0.75rem; color:#93c5fd; background:rgba(59,130,246,0.2); padding:0.15rem 0.45rem; border-radius:4px;">Clan Oda (Double Chantier)</span>
                    <?php endif; ?>
                </div>
                <div class="field-card-body" style="display: flex; flex-direction: column; justify-content: space-between;">

                    <?php if ($activeJob): ?>
                        <!-- Chantier en cours sur ce bâtiment -->
                        <?php $isDemolishingJob = ((int)$activeJob['target_level'] === 0); ?>
                        <div class="field-active-job-box" style="<?= $isDemolishingJob ? 'border-color: rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.08);' : '' ?>">
                            <div class="job-status-title" style="<?= $isDemolishingJob ? 'color:#f87171;' : '' ?>">
                                <span class="job-spinner">⏳</span>
                                <?php if ($isDemolishingJob): ?>
                                    <span>Démantèlement en cours vers le <strong>Niveau 0 (Raser)</strong></span>
                                <?php else: ?>
                                    <span>Travaux en cours vers le <strong>Niveau <?= $activeJob['target_level'] ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin:0.5rem 0 1rem 0;">
                                <?php if ($isDemolishingJob): ?>
                                    Vos maîtres d'œuvre déconstruisent cette bâtisse pour libérer l'emplacement. Vous récupérerez 30% des matériaux à l'achèvement des travaux.
                                <?php else: ?>
                                    Vos bâtisseurs et charpentiers travaillent sur cette bâtisse. La forteresse bénéficiera de ses nouvelles capacités dès achèvement.
                                <?php endif; ?>
                            </p>

                            <div class="job-timer-display" data-countdown="<?= $activeJob['finishes_at'] ?>" style="<?= $isDemolishingJob ? 'color:#f87171;' : '' ?>">
                                Calcul du temps restant...
                            </div>

                            <div style="margin-top: 1.25rem;">
                                <button type="button" class="field-btn-cancel" onclick="cancelBuildingBuild(<?= (int)$activeJob['id'] ?>)">
                                    <?= $isDemolishingJob ? '🛑 Interrompre le démantèlement (Bâtiment préservé)' : '🛑 Interrompre les travaux (80% remboursé)' ?>
                                </button>
                            </div>
                        </div>

                    <?php elseif (!$canQueueNewBuilding): ?>
                        <!-- File de construction saturée -->
                        <div class="field-blocked-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🏗️</div>
                            <h4>Chantier Castral Déjà Mobilisé</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                <?php if ($isTerran): ?>
                                    Une autre bâtisse urbaine est déjà en construction dans votre cité. Le Clan Oda permet 1 bâtiment urbain et 1 parcelle rurale en simultané.
                                <?php else: ?>
                                    Vos équipes de bâtisseurs travaillent déjà sur un autre chantier du domaine. Attendez la fin des travaux en cours.
                                <?php endif; ?>
                            </p>
                            <a href="/?page=city" class="field-btn-secondary" style="margin-top:1rem; display:inline-block;">
                                Voir les chantiers de la Cité &rarr;
                            </a>
                        </div>

                    <?php elseif (!$canAfford): ?>
                        <!-- Ressources insuffisantes -->
                        <div class="field-blocked-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">⚠️</div>
                            <h4>Matériaux Insuffisants</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                Vos greniers ne disposent pas encore de la quantité requise de bois, de pierre ou de riz pour ce chantier castral.
                            </p>
                            <?php if ($missingWaitSeconds > 0): ?>
                                <div class="time-to-afford">
                                    ⏳ Matériaux réunis dans environ : <strong><?= gmdate('H:i:s', $missingWaitSeconds) ?></strong>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="field-btn-primary disabled" disabled style="margin-top:1rem; opacity:0.5; cursor:not-allowed;">
                                <?= ($lvl === 0) ? '🔨 Construire au Niveau 1' : '⚡ Améliorer au Niveau ' . $targetLevel ?>
                            </button>
                        </div>

                    <?php else: ?>
                        <!-- Prêt pour lancer les travaux -->
                        <div class="field-ready-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🏯</div>
                            <h4>Ordre de Travaux Prêt</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                Les maîtres-artisans ont dressé les plans. Vous pouvez ordonner l'élévation du <strong>Niveau <?= $targetLevel ?></strong>.
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <button type="button" class="field-btn-primary" id="btnLaunchBuildingUpgrade" onclick="launchBuildingUpgrade('<?= $code ?>', <?= $targetLevel ?>)">
                                    <?= ($lvl === 0) ? '🔨 Ériger au Niveau 1' : '⚡ Élever au Niveau ' . $targetLevel ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- ZONE DE DÉMANTÈLEMENT (Sauf Donjon Tenshu) -->
        <?php if ($code !== 'hq' && $lvl > 0 && !$activeJob): ?>
            <div class="card" style="margin-top: 1.5rem; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; padding: 1.25rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h4 style="color: #f87171; margin: 0; font-size: 0.95rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🗑️</span> Démanteler cette Bâtisse
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.35rem 0 0 0;">
                            Rase définitivement ce bâtiment pour libérer l'emplacement <strong>#<?= $slot ?></strong>. Vous récupérerez <strong>30% des matériaux</strong> de ce niveau.
                        </p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger" onclick="confirmDemolishBuilding('<?= $code ?>', <?= $slot ?>, '<?= htmlspecialchars(addslashes($bInfo['name'] ?? $code)) ?>')" style="background: linear-gradient(135deg, #991b1b, #dc2626); border: 1px solid #f87171; font-weight: 800; font-size: 0.82rem; padding: 0.55rem 1.1rem; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); transition: transform 0.15s ease;">
                            <span>💥</span> Raser le Bâtiment
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ZONE EXCLUSIVE DU TENSHU : PROCLAMATION & DEVISE DU DAIMYŌ -->
        <?php if ($code === 'hq'): ?>
            <div class="card" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(30, 27, 75, 0.4) 0%, rgba(15, 23, 42, 0.9) 100%); border: 1px solid rgba(220, 38, 38, 0.45); border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px;">
                            🏯 Résidence Suprême & Siège du Commandement
                        </span>
                        <h3 style="color: #fff; margin: 0.3rem 0 0 0; font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                            <span>📜</span> Devise & Chronique Officielle du Daimyō
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.35rem 0 0 0;">
                            C'est du haut de ce Donjon que vous proclamez la devise qui guide vos samouraïs et inspire la crainte à vos rivaux.
                        </p>
                    </div>
                    <div>
                        <button type="button" onclick="openEditMottoModal()" class="btn btn-primary" style="font-size: 0.82rem; padding: 0.5rem 1rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #b91c1c, #dc2626); border-color: #f87171; box-shadow: 0 3px 12px rgba(220, 38, 38, 0.35); cursor: pointer; border-radius: 8px;">
                            <span>✏️</span> Modifier ma Devise
                        </button>
                    </div>
                </div>

                <div style="background: rgba(0, 0, 0, 0.4); border-left: 4px solid #dc2626; border-radius: 0 8px 8px 0; padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.35rem; text-transform: uppercase; font-weight: 700;">
                        Proclamation actuelle du Daimyō <?= htmlspecialchars($user['username']) ?> :
                    </div>
                    <div id="tenshuDaimyoBioText" style="font-size: 0.95rem; color: #f1f5f9; font-style: italic; line-height: 1.6;">
                        &laquo; <?= htmlspecialchars(!empty($user['bio']) ? $user['bio'] : "Fier Daimyō au service de l'honneur de son clan et de l'Empereur.") ?> &raquo;
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

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
                btn.innerText = '⚡ Élever au Niveau ' + targetLevel;
            }
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur castral.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerText = '⚡ Élever au Niveau ' + targetLevel;
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
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            el.innerText = `⏳ Temps restant : ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
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
    <div class="modal-card modal-card-lg" style="max-width: 960px; padding: 1.5rem; background: var(--bg-surface, #fdfbf7);" onclick="event.stopPropagation()">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 id="artworkModalTitle" style="margin:0; font-size: 1.15rem; color: var(--text-main); font-weight: 800; display:flex; align-items:center; gap:0.5rem;">
                <span>🎨</span> Estampe Féodale Authentique
            </h3>
            <button type="button" class="modal-close-btn" onclick="closeArtworkModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <img id="artworkModalImg" src="" alt="Estampe" style="width: 100%; height: auto; max-height: 75vh; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.35); border: 1px solid var(--border-color);">
        </div>
    </div>
</div>

