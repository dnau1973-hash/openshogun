<?php
/**
 * Vue de Gestion et Déploiement des Expéditions Militaires (OpenShogun)
 * Conforme aux standards UX/UI Tabler.io natifs (Thème clair, cartes modulaires, navigation réactive)
 */
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';

$fleetEngine = new FleetEngine();
$planetEngine = new PlanetEngine();
$heroEngine = new HeroEngine();
$sealEngine = new ImperialSealEngine();
$db = Database::getConnection();

$isSealActive = $sealEngine->isSealActive((int)$user['id']);
$allFarmLists = $sealEngine->getFarmLists((int)$user['id']);

// Assurer la disponibilité des fiefs du joueur
if (!isset($allUserPlanets) || empty($allUserPlanets)) {
    $allUserPlanets = $planetEngine->getUserPlanets($user['id']);
}

// Samouraï Héros Champion
$heroData = $heroEngine->getHeroByUserId($user['id']);
$canDeployHero = false;
$heroEffectiveStats = null;
if ($heroData && $heroData['status'] === 'home' && (int)$heroData['health'] > 0 && (int)$heroData['current_planet_id'] === (int)$planet['id']) {
    $canDeployHero = true;
    $heroEffectiveStats = $heroEngine->calculateEffectiveStats($heroData);
}

// Cavalerie, convois et engins de siège stationnés
$stmtShips = $db->prepare("
    SELECT ps.ship_code, ps.count, s.name, s.speed, s.cargo_capacity, s.attack, s.defense, s.shield 
    FROM planet_ships ps 
    JOIN ships s ON ps.ship_code = s.code 
    WHERE ps.planet_id = ? AND ps.count > 0
");
$stmtShips->execute([$planet['id']]);
$stationedShips = $stmtShips->fetchAll();

// Guerriers et soldats du Dojo stationnés
$stmtUnits = $db->prepare("
    SELECT pu.unit_code, pu.count, u.name, u.icon, u.speed, u.cargo_capacity, u.attack, u.def_infantry, u.def_mech 
    FROM planet_units pu 
    JOIN units u ON pu.unit_code = u.code 
    WHERE pu.planet_id = ? AND pu.count > 0
");
$stmtUnits->execute([$planet['id']]);
$stationedUnits = $stmtUnits->fetchAll();

// Toutes les marches et expéditions en cours du Daimyō
$stmtMissions = $db->prepare("
    SELECT fm.*, 
           p1.name as source_name, p1.coord_x as sx, p1.coord_y as sy,
           COALESCE(p2.name, CONCAT('Oasis ', o.name)) as target_name, 
           COALESCE(p2.coord_x, o.coord_x) as tx, 
           COALESCE(p2.coord_y, o.coord_y) as ty
    FROM fleet_missions fm 
    JOIN planets p1 ON fm.source_planet_id = p1.id 
    LEFT JOIN planets p2 ON fm.target_planet_id = p2.id 
    LEFT JOIN oases o ON fm.target_oasis_id = o.id
    WHERE fm.user_id = ? AND fm.status IN ('en_route', 'returning') 
    ORDER BY fm.arrival_time ASC
");
$stmtMissions->execute([$user['id']]);
$activeMissions = $stmtMissions->fetchAll();

// Liste des fiefs et domaines connus pour cible rapide avec statut d'immunité
$stmtTargets = $db->prepare("
    SELECT p.id, p.name, p.coord_x, p.coord_y, p.user_id, u.username, u.protection_until, u.created_at, u.is_bot
    FROM planets p 
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id != ? 
    ORDER BY p.id ASC LIMIT 40
");
$stmtTargets->execute([$planet['id']]);
$knownPlanets = $stmtTargets->fetchAll();
foreach ($knownPlanets as &$kp) {
    $kp['is_protected'] = !empty($kp['user_id']) ? Auth::isUserProtected($kp) : false;
}
unset($kp);

// Immunité du joueur connecté
$myProtection = Auth::getProtectionRemaining($user);
$isMyProtectionActive = $myProtection && !empty($myProtection['is_protected']);

// Liste des oasis sauvages et naturelles
$stmtOases = $db->prepare("SELECT id, name, coord_x, coord_y, oasis_type, bonus_wood, bonus_stone, bonus_rice, owner_planet_id FROM oases ORDER BY id ASC LIMIT 50");
$stmtOases->execute();
$knownOases = $stmtOases->fetchAll();

// Préparation des options de cibles triées par distance pour les modales de ravitaillement/ferme
$targetOptions = [];
foreach ($knownPlanets as $kp) {
    $dist = sqrt(pow((int)$kp['coord_x'] - (int)$planet['coord_x'], 2) + pow((int)$kp['coord_y'] - (int)$planet['coord_y'], 2));
    $targetOptions[] = [
        'type' => 'planet',
        'id' => $kp['id'],
        'name' => $kp['name'],
        'x' => $kp['coord_x'],
        'y' => $kp['coord_y'],
        'distance' => round($dist, 1)
    ];
}
foreach ($knownOases as $ko) {
    $dist = sqrt(pow((int)$ko['coord_x'] - (int)$planet['coord_x'], 2) + pow((int)$ko['coord_y'] - (int)$planet['coord_y'], 2));
    $targetOptions[] = [
        'type' => 'oasis',
        'id' => $ko['id'],
        'name' => 'Oasis ' . $ko['name'],
        'x' => $ko['coord_x'],
        'y' => $ko['coord_y'],
        'distance' => round($dist, 1)
    ];
}
usort($targetOptions, fn($a, $b) => $a['distance'] <=> $b['distance']);

$preselectedTargetType = $_GET['target_type'] ?? 'planet';
$preselectedTarget = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
$preselectedMission = $_GET['mission'] ?? 'raid';

// Prise en charge du clic sur n'importe quelle coordonnée libre de la carte [X : Y] pour coloniser
if (isset($_GET['target_x']) && isset($_GET['target_y'])) {
    $targetX = (int)$_GET['target_x'];
    $targetY = (int)$_GET['target_y'];

    // Vérifier si un fief existe déjà à ces coordonnées
    $stmtFindPlanet = $db->prepare("SELECT id FROM planets WHERE coord_x = ? AND coord_y = ?");
    $stmtFindPlanet->execute([$targetX, $targetY]);
    $foundPlanetId = $stmtFindPlanet->fetchColumn();

    if ($foundPlanetId) {
        $preselectedTargetType = 'planet';
        $preselectedTarget = (int)$foundPlanetId;
        $preselectedMission = $_GET['mission'] ?? 'colonize';
    } else {
        // Vérifier s'il s'agit d'une oasis
        $stmtFindOasis = $db->prepare("SELECT id FROM oases WHERE coord_x = ? AND coord_y = ?");
        $stmtFindOasis->execute([$targetX, $targetY]);
        $foundOasisId = $stmtFindOasis->fetchColumn();

        if ($foundOasisId) {
            $preselectedTargetType = 'oasis';
            $preselectedTarget = (int)$foundOasisId;
            $preselectedMission = $_GET['mission'] ?? 'occupy';
        } else {
            // Emplacement vierge : initialiser la terre libre à coloniser
            require_once __DIR__ . '/../core/GalaxyEngine.php';
            $terrainData = GalaxyEngine::getTerrainType($targetX, $targetY);
            $stmtCreateFree = $db->prepare("
                INSERT INTO planets 
                (name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, 'terrestrial', 2000, 1500, 1000, 0, 50, 20000, 20000, 20000, 0, UNIX_TIMESTAMP())
            ");
            $freeName = $terrainData['name'] . ' Vierges';
            $stmtCreateFree->execute([$freeName, $targetX, $targetY]);
            $createdPlanetId = (int)$db->lastInsertId();

            $preselectedTargetType = 'planet';
            $preselectedTarget = $createdPlanetId;
            $preselectedMission = $_GET['mission'] ?? 'colonize';
        }
    }
}

// Vérifier la présence d'un Pionnier Féodal (Colon) dans la garnison
$hasColonistAvailable = false;
$availableColonCount = 0;
foreach ($stationedUnits as $u) {
    if ($u['unit_code'] === 'colonizer' && (int)$u['count'] > 0) {
        $hasColonistAvailable = true;
        $availableColonCount += (int)$u['count'];
    }
}
foreach ($stationedShips as $s) {
    if ($s['ship_code'] === 'colony_ship' && (int)$s['count'] > 0) {
        $hasColonistAvailable = true;
        $availableColonCount += (int)$s['count'];
    }
}

// Emplacement du Tenshu pour orientation directe
$stmtTenshu = $db->prepare("SELECT slot FROM planet_buildings WHERE planet_id = ? AND building_type = 'hq'");
$stmtTenshu->execute([$planet['id']]);
$tenshuSlot = (int)$stmtTenshu->fetchColumn() ?: 25;
?>

<!-- EN-TÊTE DE PAGE NATIVE TABLER.IO -->
<div class="page-header d-print-none mb-3">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle text-secondary">
                <ol class="breadcrumb breadcrumb-arrows mb-1" aria-label="breadcrumbs">
                    <li class="breadcrumb-item"><a href="?page=overview">Quartier Général</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Expéditions Militaires</li>
                </ol>
            </div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span class="text-danger">🏇</span> Expéditions Féodales &amp; Mouvements de Troupes
            </h2>
        </div>
        <div class="col-auto ms-auto d-flex align-items-center gap-2">
            <span class="badge bg-blue-lt px-2 py-1 fs-5">
                🏰 Fief : <strong><?= htmlspecialchars($planet['name']) ?></strong> [<?= $planet['coord_x'] ?>:<?= $planet['coord_y'] ?>]
            </span>
            <span class="badge bg-secondary-lt px-2 py-1 fs-5">
                🏇 <?= count($activeMissions) ?> marche(s) active(s)
            </span>
        </div>
    </div>
</div>

<!-- ONGLET DE NAVIGATION TABLER -->
<div class="mb-3 d-print-none">
    <ul class="nav nav-tabs" data-bs-toggle="tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="#tab-manual-fleet" class="nav-link active fw-bold d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="true" role="tab">
                <span>🚩</span> Expédition &amp; Manœuvres
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="#tab-farm-lists" class="nav-link fw-bold text-warning d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                <span>📜</span> Carnet de Raids (Farm List)
                <span class="badge bg-warning text-dark ms-1"><?= count($allFarmLists) ?></span>
                <?php if ($isSealActive): ?>
                    <span class="badge bg-dark text-warning border border-warning ms-1" style="font-size:0.65rem;">Sceau Actif</span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="#tab-active-missions" class="nav-link fw-bold d-flex align-items-center gap-2" data-bs-toggle="tab" aria-selected="false" role="tab">
                <span>🏇</span> Marches Actives
                <span class="badge bg-secondary ms-1"><?= count($activeMissions) ?></span>
            </a>
        </li>
        <li class="nav-item ms-auto" role="presentation">
            <a href="?page=privilege#sectionTradeRoutes" class="nav-link text-primary fw-bold d-flex align-items-center gap-1">
                <span>🛣️</span> Routes Commerciales Féodales &rarr;
            </a>
        </li>
    </ul>
</div>

<div class="tab-content">
    <!-- ================================================================= -->
    <!-- ONGLET 1 : EXPÉDITION & MANŒUVRES (TABLER 2 COLONNES)            -->
    <!-- ================================================================= -->
    <div class="tab-pane active show" id="tab-manual-fleet" role="tabpanel">
        <div class="row g-3">
            <!-- COLONNE GAUCHE (Formulaire de Déploiement) -->
            <div class="col-lg-8">
                <div class="card shadow-sm border">
                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0">🚩 Préparation de l'Expédition &amp; Ordre de Marche</h3>
                            <div class="text-secondary small">Garnison actuelle de rattachement : <strong><?= htmlspecialchars($planet['name']) ?></strong></div>
                        </div>
                        <div class="card-actions">
                            <span class="badge bg-blue-lt">Unités disponibles : <?= count($stationedUnits) + count($stationedShips) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($isMyProtectionActive): ?>
                            <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
                                <span class="fs-1 flex-shrink-0">🔰</span>
                                <div class="small">
                                    <strong class="text-success d-block mb-1">Immunité Féodale des Nouveaux Joueurs Active (Jusqu'au <?= htmlspecialchars($myProtection['until_formatted']) ?> — encore <?= htmlspecialchars($myProtection['formatted']) ?>)</strong>
                                    Vos domaines et fiefs sont protégés contre tout raid de pillage, assaut de siège et infiltration shinobi adverse.<br>
                                    <span class="text-warning-emphasis fw-bold">⚔️ Règle martiale :</span> Vous pouvez librement explorer les aventures du Héros et pacifier les oasis sauvages. En revanche, si vous lancez un raid, un assaut ou un espionnage contre un <strong>autre seigneur joueur</strong>, votre immunité sera <strong>définitivement levée</strong>.
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($stationedShips) && empty($stationedUnits) && !$canDeployHero): ?>
                            <div class="text-center py-5">
                                <div class="text-secondary mb-2" style="font-size:2.5rem;">🏯</div>
                                <h4>Garnison Indisponible</h4>
                                <p class="text-secondary mb-3">Aucun régiment de guerriers, engin de siège ni héros samouraï n'est disponible dans votre garnison.</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="?page=shipyard" class="btn btn-primary">🔨 Atelier de Siège &amp; Écuries</a>
                                    <a href="?page=barracks" class="btn btn-outline-secondary">⚔️ Dojo Militaire</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form id="fleetForm" onsubmit="event.preventDefault(); submitFleet();">
                                <?php if ($preselectedMission === 'colonize' && !$hasColonistAvailable): ?>
                                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
                                        <span class="fs-1 flex-shrink-0">⛩️</span>
                                        <div class="flex-fill">
                                            <strong class="d-block mb-1">Aucun Pionnier Féodal (Colon ⛩️) en garnison !</strong>
                                            <p class="mb-2 small">
                                                Pour ériger votre nouveau fief, vous devez d'abord former un <strong>Pionnier Féodal</strong>. Il est disponible au <strong>Donjon Tenshu</strong> (déblocage aux paliers de niveau 10, 15 et 20) ou à l'<strong>Atelier de Siège</strong>.
                                            </p>
                                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                                <a href="?page=building&code=hq" class="btn btn-sm btn-success fw-bold">
                                                    🏯 Former au Donjon Tenshu (Niv. 10+) &rarr;
                                                </a>
                                                <a href="?page=shipyard" class="btn btn-sm btn-outline-secondary">
                                                    🔨 Atelier de Siège &amp; Écuries
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Étape 1 : Cavalerie et Engins de Siège -->
                                <?php if (!empty($stationedShips)): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-blue text-white rounded-pill px-2">1</span>
                                            <h4 class="fw-bold text-dark m-0">🐎 Cavalerie, Convois &amp; Engins de Siège</h4>
                                        </div>
                                        <div class="list-group list-group-flush border rounded">
                                            <?php foreach ($stationedShips as $s): ?>
                                                <?php 
                                                    $defaultShipVal = ($preselectedMission === 'colonize' && $s['ship_code'] === 'colony_ship' && (int)$s['count'] > 0) ? '1' : '0';
                                                ?>
                                                <div class="list-group-item d-flex align-items-center justify-content-between p-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="avatar avatar-sm bg-blue-lt">🐎</span>
                                                        <div>
                                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($s['name']) ?></div>
                                                            <div class="text-secondary" style="font-size:0.75rem;">
                                                                Disponible : <span class="badge bg-secondary-lt font-monospace" id="max-<?= $s['ship_code'] ?>"><?= $s['count'] ?></span>
                                                                &bull; Vitesse : <?= $s['speed'] ?> &bull; Fret : <?= $s['cargo_capacity'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size:0.75rem;" 
                                                                onclick="document.getElementById('ship-<?= $s['ship_code'] ?>').value = <?= $s['count'] ?>;">
                                                            Max
                                                        </button>
                                                        <input type="number" id="ship-<?= $s['ship_code'] ?>" name="fleet[<?= $s['ship_code'] ?>]" 
                                                               min="0" max="<?= $s['count'] ?>" value="<?= $defaultShipVal ?>"
                                                               class="form-control form-control-sm text-center font-monospace fw-bold" style="width:75px;">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Étape 2 : Guerriers & Régiments du Dojo -->
                                <?php if (!empty($stationedUnits)): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-danger text-white rounded-pill px-2">2</span>
                                            <h4 class="fw-bold text-dark m-0">⚔️ Régiments de Guerriers &amp; Samouraïs du Dojo</h4>
                                        </div>
                                        <div class="list-group list-group-flush border rounded">
                                            <?php foreach ($stationedUnits as $u): ?>
                                                <?php 
                                                    $defaultUnitVal = ($preselectedMission === 'colonize' && $u['unit_code'] === 'colonizer' && (int)$u['count'] > 0) ? '1' : '0';
                                                ?>
                                                <div class="list-group-item d-flex align-items-center justify-content-between p-2 border-start border-start-3 border-danger">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="avatar avatar-sm bg-danger-lt fs-3"><?= $u['icon'] ?></span>
                                                        <div>
                                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($u['name']) ?></div>
                                                            <div class="text-secondary" style="font-size:0.75rem;">
                                                                En garnison : <span class="badge bg-secondary-lt font-monospace" id="max-<?= $u['unit_code'] ?>"><?= $u['count'] ?></span>
                                                                &bull; Attaque : <?= $u['attack'] ?> &bull; Fret : <?= $u['cargo_capacity'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" style="font-size:0.75rem;" 
                                                                onclick="document.getElementById('ship-<?= $u['unit_code'] ?>').value = <?= $u['count'] ?>;">
                                                            Max
                                                        </button>
                                                        <input type="number" id="ship-<?= $u['unit_code'] ?>" name="fleet[<?= $u['unit_code'] ?>]" 
                                                               min="0" max="<?= $u['count'] ?>" value="<?= $defaultUnitVal ?>"
                                                               class="form-control form-control-sm text-center font-monospace fw-bold" style="width:75px;">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Étape 3 : Champion Samouraï (Héros de Guerre) -->
                                <?php if ($canDeployHero): ?>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-warning text-dark rounded-pill px-2">3</span>
                                            <h4 class="fw-bold text-dark m-0">🥋 Champion Samouraï (Héros de Guerre)</h4>
                                        </div>
                                        <div class="card bg-warning-subtle border-warning-subtle p-3 shadow-none">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar avatar-md rounded-circle bg-warning text-dark fs-2 border border-warning">🥋</span>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-4">
                                                            <?= htmlspecialchars($heroData['name']) ?>
                                                            <span class="badge bg-warning text-dark ms-1">Niv. <?= $heroData['level'] ?></span>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                                            <span class="badge bg-danger-lt">⚔️ Force : <?= $heroEffectiveStats['combat_strength'] ?></span>
                                                            <span class="badge bg-orange-lt">🔥 Attaque : +<?= $heroEffectiveStats['offense_bonus_pct'] ?? 0 ?>%</span>
                                                            <span class="badge bg-teal-lt">🛡️ Défense : +<?= $heroEffectiveStats['defense_bonus_pct'] ?? 0 ?>%</span>
                                                            <span class="badge bg-green-lt">❤️ Santé : <?= $heroData['health'] ?>%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" id="deploy_hero" name="has_hero" value="1" style="cursor:pointer; transform:scale(1.3);">
                                                    <label class="form-check-label fw-bold text-dark ms-2" for="deploy_hero" style="cursor:pointer;">
                                                        Accompagner l'expédition
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($heroData && $heroData['status'] !== 'home'): ?>
                                    <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-4">
                                        <span class="fs-4">🥋</span>
                                        <div class="small text-secondary">
                                            Champion Samouraï <strong><?= htmlspecialchars($heroData['name']) ?></strong> : Indisponible (En cours de marche ou en aventure).
                                        </div>
                                    </div>
                                <?php elseif ($heroData && (int)$heroData['health'] <= 0): ?>
                                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-4">
                                        <span class="fs-4">💀</span>
                                        <div class="small">
                                            Champion Samouraï <strong><?= htmlspecialchars($heroData['name']) ?></strong> est tombé au combat. <a href="?page=hero" class="alert-link fw-bold">Accomplir le rituel de résurrection &rarr;</a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Étape 4 : Destination -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-secondary text-white rounded-pill px-2">4</span>
                                        <h4 class="fw-bold text-dark m-0">🗾 Fief ou Oasis de Destination</h4>
                                    </div>
                                    <select id="targetSelect" class="form-select form-select-lg">
                                        <option value="">-- Sélectionner une destination féodale ou oasis --</option>
                                        <optgroup label="🏯 Fiefs &amp; Domaines Provinciaux">
                                            <?php 
                                                $targetFoundInList = false;
                                                foreach ($knownPlanets as $kp): 
                                                    $isSelected = ($preselectedTargetType === 'planet' && $preselectedTarget === (int)$kp['id']);
                                                    if ($isSelected) $targetFoundInList = true;
                                                    $protLabel = !empty($kp['is_protected']) ? ' [🔰 Protégé]' : ''; 
                                            ?>
                                                <option value="planet:<?= $kp['id'] ?>" data-protected="<?= !empty($kp['is_protected']) ? '1' : '0' ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($kp['name']) ?> [<?= $kp['coord_x'] ?> : <?= $kp['coord_y'] ?>]<?= $protLabel ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if (!$targetFoundInList && $preselectedTargetType === 'planet' && $preselectedTarget > 0): 
                                                $stmtSpecific = $db->prepare("SELECT id, name, coord_x, coord_y, user_id FROM planets WHERE id = ?");
                                                $stmtSpecific->execute([$preselectedTarget]);
                                                $sp = $stmtSpecific->fetch();
                                                if ($sp):
                                            ?>
                                                <option value="planet:<?= $sp['id'] ?>" selected>
                                                    <?= htmlspecialchars($sp['name']) ?> [<?= $sp['coord_x'] ?> : <?= $sp['coord_y'] ?>] <?= empty($sp['user_id']) ? '(Terre Vierge Libre ⛩️)' : '' ?>
                                                </option>
                                            <?php endif; endif; ?>
                                        </optgroup>
                                        <optgroup label="🌿 Oasis Naturelles Sauvages (Récoltes &amp; Animaux)">
                                            <?php foreach ($knownOases as $ko): 
                                                $bText = '';
                                                if ($ko['bonus_rice'] > 0) $bText .= "+{$ko['bonus_rice']}% Riz ";
                                                if ($ko['bonus_wood'] > 0) $bText .= "+{$ko['bonus_wood']}% Bois ";
                                                if ($ko['bonus_stone'] > 0) $bText .= "+{$ko['bonus_stone']}% Pierre ";
                                                $statusOasis = !empty($ko['owner_planet_id']) ? ' [Occupée]' : ' [Sauvage]';
                                            ?>
                                                <option value="oasis:<?= $ko['id'] ?>" <?= ($preselectedTargetType === 'oasis' && $preselectedTarget === (int)$ko['id']) ? 'selected' : '' ?>>
                                                    🌿 <?= htmlspecialchars($ko['name']) ?> [<?= $ko['coord_x'] ?> : <?= $ko['coord_y'] ?>] (<?= trim($bText) ?>)<?= $statusOasis ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>

                                <!-- Étape 5 : Ordre Tactique de Marche -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-secondary text-white rounded-pill px-2">5</span>
                                        <h4 class="fw-bold text-dark m-0">⚔️ Ordre Tactique de Marche</h4>
                                    </div>
                                    <div class="form-selectgroup form-selectgroup-pills row g-2">
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="raid" class="form-selectgroup-input" <?= ($preselectedMission === 'raid') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>⚔️</span> Raid de Pillage
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="attack" class="form-selectgroup-input" <?= ($preselectedMission === 'attack') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>💥</span> Assaut de Siège
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="occupy" class="form-selectgroup-input" <?= ($preselectedMission === 'occupy') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>🚩</span> Occuper / Garnison
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="spy" class="form-selectgroup-input" <?= ($preselectedMission === 'spy') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>🥷</span> Infiltration Shinobi
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="transport" class="form-selectgroup-input" <?= ($preselectedMission === 'transport') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>🐂</span> Convoi de Vivres
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-selectgroup-item w-100">
                                                <input type="radio" name="mission_type" value="colonize" class="form-selectgroup-input" <?= ($preselectedMission === 'colonize') ? 'checked' : '' ?>>
                                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2 py-2">
                                                    <span>🏯</span> Fonder un Fief
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Étape 6 : Chargement de Vivres & Minerais (Optionnel pour convoi) -->
                                <div class="bg-body-tertiary p-3 rounded border mb-4">
                                    <label class="form-label fw-bold small text-secondary mb-2">Chargement de Fret &amp; Tributs (Optionnel pour transport / colonisation) :</label>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">🪵 Bois</span>
                                                <input type="number" id="cargo_metal" class="form-control text-center font-monospace" min="0" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">🪨 Pierre</span>
                                                <input type="number" id="cargo_crystal" class="form-control text-center font-monospace" min="0" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white">🌾 Riz</span>
                                                <input type="number" id="cargo_deuterium" class="form-control text-center font-monospace" min="0" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <span>🚩</span> Lancer l'Expédition Féodale
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE (Marches Actives & Raccourcis) -->
            <div class="col-lg-4">
                <div class="card shadow-sm border mb-3">
                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold text-dark mb-0">🏇 Marches en Cours</h3>
                        <span class="badge bg-secondary-lt"><?= count($activeMissions) ?> active(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($activeMissions)): ?>
                            <div class="text-center py-5 text-secondary p-3">
                                <div class="fs-1 mb-2">🚩</div>
                                <div class="fw-bold">Aucune troupe en marche</div>
                                <div class="small">Toutes vos forces sont en garnison.</div>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($activeMissions as $m): ?>
                                    <?php 
                                        $isOutbound = ($m['status'] === 'en_route');
                                        $targetTime = $isOutbound ? $m['arrival_time'] : $m['return_time'];
                                        $fleetData = json_decode($m['fleet_data'], true) ?: [];
                                        $missionLabel = match($m['mission_type']) {
                                            'raid' => '⚔️ RAID',
                                            'attack' => '💥 SIÈGE',
                                            'occupy' => '🚩 OCCUPATION',
                                            'spy' => '🥷 SHINOBI',
                                            'transport' => '🐂 CONVOI',
                                            'colonize' => '🏯 EXPANSION',
                                            default => strtoupper($m['mission_type'])
                                        };
                                    ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge <?= $isOutbound ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success' ?> fw-bold">
                                                <?= $isOutbound ? '↗️ ' : '↙️ ' ?><?= $missionLabel ?>
                                            </span>
                                            <span class="badge bg-primary text-white font-monospace px-2 py-1" data-countdown="<?= $targetTime ?>">
                                                Calcul...
                                            </span>
                                        </div>
                                        <div class="fw-bold text-dark small text-truncate">
                                            &rarr; <?= htmlspecialchars($m['target_name']) ?> [<?= $m['tx'] ?> : <?= $m['ty'] ?>]
                                        </div>
                                        <div class="text-secondary small d-flex align-items-center gap-2 mt-1">
                                            <span>Effectif : <strong><?= array_sum($fleetData) ?></strong> unités</span>
                                            <?php if (!empty($m['has_hero'])): ?>
                                                <span class="badge bg-warning-lt text-dark border border-warning" style="font-size:0.65rem;">🥋 Héros</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CARTE CONSEIL STRATÉGIQUE -->
                <div class="card bg-blue-lt border-blue-lt shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <span class="fs-1 text-primary">💡</span>
                            <div>
                                <h4 class="fw-bold text-primary mb-1">Stratégie Martiale Féodale</h4>
                                <p class="small text-secondary mb-0">
                                    Pillez régulièrement les oasis sauvages pour collecter du riz et du bois sans risquer de représailles d'un seigneur voisin. Pour automatiser vos expéditions quotidiennes, configurez votre <strong>Carnet de Raids</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- ONGLET 2 : CARNET DE RAIDS (FARM LIST)                            -->
    <!-- ================================================================= -->
    <div class="tab-pane" id="tab-farm-lists" role="tabpanel">
        <div class="card shadow-sm border mb-3">
            <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <div>
                    <h3 class="card-title text-warning fw-bold d-flex align-items-center gap-2 m-0">
                        <span>📜</span> Carnet de Raids Automatisé (Farm List Féodale)
                    </h3>
                    <div class="text-secondary small mt-1">
                        Enregistrez vos cibles favorites (oasis d'animaux, domaines inactifs) et déployez des vagues coordonnées en 1 clic.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning fw-bold btn-sm shadow-sm" onclick="openCreateFarmListModal()">
                        ➕ Nouvelle Liste de Raids
                    </button>
                    <?php if (!$isSealActive): ?>
                        <a href="/?page=privilege" class="btn btn-outline-warning btn-sm">
                            👑 Sceau Impérial
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($allFarmLists)): ?>
                    <div class="text-center py-5">
                        <div class="text-warning mb-2" style="font-size:3rem;">📜</div>
                        <h3 class="fw-bold text-dark">Votre Carnet de Raids est vierge</h3>
                        <p class="text-secondary small mb-3">
                            Créez votre première liste de raids pour automatiser le pillage de vos cibles sans recomposer vos armées manuellement.
                        </p>
                        <button type="button" class="btn btn-warning fw-bold shadow-sm" onclick="openCreateFarmListModal()">
                            ➕ Créer ma première Liste de Raids
                        </button>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($allFarmLists as $fl): ?>
                            <div class="card border shadow-sm">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-body-tertiary">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-2">⚔️</span>
                                        <div>
                                            <strong class="text-dark fs-3"><?= htmlspecialchars($fl['name']) ?></strong>
                                            <div class="text-secondary small">
                                                Fief de départ : <strong><?= htmlspecialchars($fl['source_planet_name']) ?></strong> [<?= $fl['source_coord_x'] ?>|<?= $fl['source_coord_y'] ?>]
                                                &bull; <?= count($fl['entries']) ?> cible(s) enregistrée(s)
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-success fw-bold btn-sm shadow-sm" 
                                                onclick="runFullFarmList(<?= $fl['id'] ?>, '<?= htmlspecialchars(addslashes($fl['name'])) ?>')"
                                                <?= empty($fl['entries']) ? 'disabled' : '' ?>>
                                            ⚡ Lancer la Tournée (<?= count($fl['entries']) ?> raids)
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddFarmEntryModal(<?= $fl['id'] ?>)">
                                            ➕ Ajouter Cible
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteFarmList(<?= $fl['id'] ?>)" title="Supprimer la liste">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter table-hover table-striped card-table m-0">
                                        <thead>
                                            <tr class="text-secondary small">
                                                <th>Cible &amp; Coordonnées</th>
                                                <th>Distance</th>
                                                <th>Composition d'Armée Assignée</th>
                                                <th>Dernier Raid</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($fl['entries'])): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-secondary small">
                                                        Aucune cible dans cette liste. Cliquez sur « ➕ Ajouter Cible » pour commencer votre carnet.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($fl['entries'] as $entry): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                                                <span><?= $entry['target_type'] === 'oasis' ? '🌴' : '🏯' ?></span>
                                                                <span><?= htmlspecialchars($entry['target_name']) ?></span>
                                                            </div>
                                                            <div class="text-secondary font-monospace small">
                                                                [<?= $entry['coord_x'] ?>|<?= $entry['coord_y'] ?>]
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary-lt font-monospace"><?= $entry['distance'] ?> cases</span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($entry['fleet_data_arr'] as $uCode => $uQty): ?>
                                                                    <span class="badge bg-light text-dark border small">
                                                                        <strong><?= $uQty ?></strong> <?= htmlspecialchars($uCode) ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($entry['last_raid_at']): ?>
                                                                <div class="small text-secondary"><?= date('d/m H:i', strtotime($entry['last_raid_at'])) ?></div>
                                                                <span class="badge bg-info-lt" style="font-size:0.7rem;"><?= htmlspecialchars($entry['last_status'] ?? 'achevé') ?></span>
                                                            <?php else: ?>
                                                                <span class="text-secondary small fst-italic">Jamais attaquée</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-success fw-bold" 
                                                                        onclick="runFarmEntry(<?= $entry['id'] ?>, '<?= htmlspecialchars(addslashes($entry['target_name'])) ?>')"
                                                                        title="Lancer le raid maintenant">
                                                                    ⚔️ Raid
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteFarmEntry(<?= $entry['id'] ?>)"
                                                                        title="Retirer de la liste">
                                                                    ✕
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- ONGLET 3 : MARCHES ACTIVES (VUE DÉTAILLÉE TABLEAU)                -->
    <!-- ================================================================= -->
    <div class="tab-pane" id="tab-active-missions" role="tabpanel">
        <div class="card shadow-sm border">
            <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold text-dark mb-0">🏇 Toutes les Marches et Expéditions Féodales en Cours</h3>
                <span class="badge bg-secondary-lt"><?= count($activeMissions) ?> active(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeMissions)): ?>
                    <div class="text-center py-5 text-secondary">
                        <div class="fs-1 mb-2">🚩</div>
                        <h4 class="fw-bold text-dark">Aucune troupe en marche</h4>
                        <p class="small text-secondary mb-0">Toutes vos armées sont actuellement stationnées dans vos garnisons castrales.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped table-hover card-table">
                            <thead>
                                <tr class="text-secondary small">
                                    <th>Type de Mission</th>
                                    <th>Départ</th>
                                    <th>Destination</th>
                                    <th>Effectif Déployé</th>
                                    <th>Statut</th>
                                    <th class="text-end">Compte à Rebours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeMissions as $m): 
                                    $isOutbound = ($m['status'] === 'en_route');
                                    $targetTime = $isOutbound ? $m['arrival_time'] : $m['return_time'];
                                    $fleetData = json_decode($m['fleet_data'], true) ?: [];
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $isOutbound ? 'bg-danger text-white' : 'bg-success text-white' ?> fw-bold">
                                            <?= $isOutbound ? '↗️ ' : '↙️ ' ?><?= strtoupper($m['mission_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($m['source_name']) ?></strong> <span class="text-secondary font-monospace">[<?= $m['sx'] ?>|<?= $m['sy'] ?>]</span>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($m['target_name']) ?></strong> <span class="text-secondary font-monospace">[<?= $m['tx'] ?>|<?= $m['ty'] ?>]</span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong><?= array_sum($fleetData) ?></strong> unités
                                            <?php if (!empty($m['has_hero'])): ?>
                                                <span class="badge bg-warning text-dark ms-1">🥋 Héros</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-lt"><?= $isOutbound ? 'En marche vers l\'objectif' : 'Retour vers le fief' ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary text-white font-monospace p-2 fs-6 shadow-sm" data-countdown="<?= $targetTime ?>">
                                            Calcul...
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- MODALE CRÉATION D'UNE FARM LIST                                  -->
<!-- ================================================================= -->
<div class="modal modal-blur fade" id="modalCreateFarmList" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>📜</span> Fonder une Nouvelle Liste de Raids
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nom de la Liste de Raids :</label>
                    <input type="text" class="form-control" id="farm_list_name" placeholder="Ex: Raids Oasis Est, Inactifs Sud...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Fief d'origine (Garnison de départ) :</label>
                    <select class="form-select" id="farm_list_source_planet">
                        <?php foreach ($allUserPlanets as $up): ?>
                            <option value="<?= $up['id'] ?>" <?= ((int)$up['id'] === (int)$planet['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($up['name']) ?> [<?= $up['coord_x'] ?>|<?= $up['coord_y'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning fw-bold shadow-sm" onclick="submitCreateFarmList()">
                    📜 Établir la Liste
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- MODALE AJOUT DE CIBLE AU CARNET                                  -->
<!-- ================================================================= -->
<div class="modal modal-blur fade" id="modalAddFarmEntry" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>🎯</span> Ajouter une Cible de Raid au Carnet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="farm_entry_list_id" value="0">

                <div class="mb-3">
                    <label class="form-label fw-bold">Sélectionner la Cible Féodale :</label>
                    <select class="form-select" id="farm_entry_target_select" onchange="onFarmTargetSelectChange()">
                        <optgroup label="🌐 Cibles enregistrées sur vos cartes">
                            <?php foreach ($targetOptions as $to): ?>
                                <option value="<?= $to['type'] ?>:<?= $to['id'] ?>:<?= htmlspecialchars($to['name']) ?>:<?= $to['x'] ?>:<?= $to['y'] ?>">
                                    <?= htmlspecialchars($to['name']) ?> [<?= $to['x'] ?>|<?= $to['y'] ?>] &bull; <?= $to['distance'] ?> cases
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <option value="custom">✏️ Saisie manuelle de coordonnées [X|Y]</option>
                    </select>
                </div>

                <div id="farm_entry_custom_coords" class="row g-2 mb-3 d-none">
                    <div class="col-6">
                        <label class="form-label small">Coordonnée X :</label>
                        <input type="number" class="form-control form-control-sm" id="farm_custom_x" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Coordonnée Y :</label>
                        <input type="number" class="form-control form-control-sm" id="farm_custom_y" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Nom du Domaine / Cible :</label>
                        <input type="text" class="form-control form-control-sm" id="farm_custom_name" placeholder="Province Rebelle">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Régiments de Pillards à assigner par raid :</label>
                    <div class="d-flex flex-column gap-2" style="max-height: 220px; overflow-y:auto;">
                        <?php foreach ($stationedUnits as $u): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-3"><?= $u['icon'] ?></span>
                                    <div>
                                        <strong class="small text-dark"><?= htmlspecialchars($u['name']) ?></strong>
                                        <div class="text-secondary" style="font-size:0.75rem;">Dispo en garnison : <?= $u['count'] ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.7rem;" onclick="document.getElementById('farm_unit_<?= $u['unit_code'] ?>').value = 5;">5</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.7rem;" onclick="document.getElementById('farm_unit_<?= $u['unit_code'] ?>').value = 10;">10</button>
                                    <input type="number" id="farm_unit_<?= $u['unit_code'] ?>" class="form-control form-control-sm text-center farm-fleet-input font-monospace fw-bold" data-unit="<?= $u['unit_code'] ?>" min="0" value="0" style="width:65px;">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($stationedShips as $s): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-3">🐎</span>
                                    <div>
                                        <strong class="small text-dark"><?= htmlspecialchars($s['name']) ?></strong>
                                        <div class="text-secondary" style="font-size:0.75rem;">Dispo en garnison : <?= $s['count'] ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.7rem;" onclick="document.getElementById('farm_unit_<?= $s['ship_code'] ?>').value = 5;">5</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.7rem;" onclick="document.getElementById('farm_unit_<?= $s['ship_code'] ?>').value = 10;">10</button>
                                    <input type="number" id="farm_unit_<?= $s['ship_code'] ?>" class="form-control form-control-sm text-center farm-fleet-input font-monospace fw-bold" data-unit="<?= $s['ship_code'] ?>" min="0" value="0" style="width:65px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning fw-bold shadow-sm" onclick="submitAddFarmEntry()">
                    🎯 Enregistrer dans la Liste
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function submitFleet() {
    const rawTarget = document.getElementById('targetSelect').value;
    if (!rawTarget) {
        showModalAlert('Veuillez sélectionner un fief ou une oasis de destination.', 'warning');
        return;
    }

    const missionTypeEl = document.querySelector('input[name="mission_type"]:checked');
    const missionType = missionTypeEl ? missionTypeEl.value : 'raid';

    // Contrôle d'éligibilité pour la colonisation
    if (missionType === 'colonize') {
        const colonizerCnt = parseInt(document.getElementById('ship-colonizer')?.value || '0', 10);
        const colonyShipCnt = parseInt(document.getElementById('ship-colony_ship')?.value || '0', 10);
        if (colonizerCnt <= 0 && colonyShipCnt <= 0) {
            showModalAlert("Une expédition de colonisation nécessite d'inclure au moins 1 Pionnier Féodal (Colon ⛩️). Si vous n'en avez pas encore en garnison, vous devez d'abord en former un au Donjon Tenshu (Niv. 10+) ou à l'Atelier de Siège.", "warning", "Pionnier Requis");
            return;
        }
    }

    const formData = new FormData();
    const parts = rawTarget.split(':');
    if (parts.length === 2) {
        if (parts[0] === 'oasis') {
            formData.append('target_oasis_id', parts[1]);
        } else {
            formData.append('target_planet_id', parts[1]);
        }
    } else {
        formData.append('target_planet_id', rawTarget);
    }
    formData.append('mission_type', missionType);

    // Vaisseaux et Troupes
    let hasForces = false;
    document.querySelectorAll('input[name^="fleet["]').forEach(inp => {
        const cnt = parseInt(inp.value, 10);
        if (cnt > 0) {
            formData.append(inp.name, cnt);
            hasForces = true;
        }
    });

    // Samouraï Champion Héros
    const heroCheckbox = document.getElementById('deploy_hero');
    if (heroCheckbox && heroCheckbox.checked) {
        formData.append('has_hero', '1');
        hasForces = true;
    }

    if (!hasForces) {
        showModalAlert('Veuillez sélectionner au moins un régiment, engin de siège ou votre héros samouraï à déployer.', 'warning');
        return;
    }

    formData.append('cargo_metal', document.getElementById('cargo_metal').value);
    formData.append('cargo_crystal', document.getElementById('cargo_crystal').value);
    formData.append('cargo_deuterium', document.getElementById('cargo_deuterium').value);

    try {
        const res = await fetch('/api/fleet.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message || 'Expédition militaire lancée avec succès !', 'success', 'Ordre de Marche Transmis');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de lancer cette expédition.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec vos généraux.', 'error');
    }
}

// ==========================================
// GESTION DU CARNET DE RAIDS (FARM LIST)
// ==========================================
function openCreateFarmListModal() {
    const modal = new bootstrap.Modal(document.getElementById('modalCreateFarmList'));
    modal.show();
}

async function submitCreateFarmList() {
    const name = document.getElementById('farm_list_name').value.trim();
    const sourcePlanetId = document.getElementById('farm_list_source_planet').value;

    const fd = new FormData();
    fd.append('action', 'create_list');
    fd.append('name', name);
    fd.append('source_planet_id', sourcePlanetId);

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la création de la liste.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
    }
}

async function deleteFarmList(listId) {
    const confirmed = await showModalConfirm('Êtes-vous certain de vouloir supprimer cette liste de raids et toutes ses cibles assignées ?', 'Suppression de Liste');
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'delete_list');
    fd.append('list_id', listId);

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || 'Erreur lors de la suppression.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
    }
}

function openAddFarmEntryModal(listId) {
    document.getElementById('farm_entry_list_id').value = listId;
    document.querySelectorAll('.farm-fleet-input').forEach(inp => inp.value = 0);
    const modal = new bootstrap.Modal(document.getElementById('modalAddFarmEntry'));
    modal.show();
}

function onFarmTargetSelectChange() {
    const val = document.getElementById('farm_entry_target_select').value;
    const customDiv = document.getElementById('farm_entry_custom_coords');
    if (val === 'custom') {
        customDiv.classList.remove('d-none');
    } else {
        customDiv.classList.add('d-none');
    }
}

async function submitAddFarmEntry() {
    const listId = document.getElementById('farm_entry_list_id').value;
    const rawVal = document.getElementById('farm_entry_target_select').value;

    let targetType = 'planet';
    let targetId = 0;
    let targetName = '';
    let x = 0;
    let y = 0;

    if (rawVal === 'custom') {
        x = parseInt(document.getElementById('farm_custom_x').value || '0', 10);
        y = parseInt(document.getElementById('farm_custom_y').value || '0', 10);
        targetName = document.getElementById('farm_custom_name').value.trim() || `Province [${x}|${y}]`;
        targetId = 0;
    } else {
        const p = rawVal.split(':');
        targetType = p[0];
        targetId = parseInt(p[1], 10);
        targetName = p[2] || 'Cible';
        x = parseInt(p[3], 10);
        y = parseInt(p[4], 10);
    }

    const fleet = {};
    let totalAssigned = 0;
    document.querySelectorAll('.farm-fleet-input').forEach(inp => {
        const cnt = parseInt(inp.value, 10);
        if (cnt > 0) {
            fleet[inp.dataset.unit] = cnt;
            totalAssigned += cnt;
        }
    });

    if (totalAssigned <= 0) {
        showModalAlert('Veuillez affecter au moins 1 guerrier ou cavalier à cette expédition.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_entry');
    fd.append('list_id', listId);
    fd.append('target_type', targetType);
    fd.append('target_id', targetId);
    fd.append('target_name', targetName);
    fd.append('coord_x', x);
    fd.append('coord_y', y);
    fd.append('fleet', JSON.stringify(fleet));

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(`Cible ${targetName} enregistrée dans le carnet avec succès !`, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de l\'ajout de la cible.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
    }
}

async function deleteFarmEntry(entryId) {
    const fd = new FormData();
    fd.append('action', 'delete_entry');
    fd.append('entry_id', entryId);

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showModalAlert(data.error || 'Erreur.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
    }
}

async function runFarmEntry(entryId, targetName) {
    const fd = new FormData();
    fd.append('action', 'run_entry');
    fd.append('entry_id', entryId);

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message || `Raid lancé vers ${targetName} !`, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Impossible de lancer ce raid (garnison manquante ?).', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission.', 'error');
    }
}

async function runFullFarmList(listId, listName) {
    const confirmed = await showModalConfirm(`Voulez-vous déployer immédiatement tous les raids programmés de la liste « ${listName} » ?`, 'Lancement de Tournée');
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'run_all');
    fd.append('list_id', listId);

    try {
        const res = await fetch('/api/farm_list.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success', 'Tournée de Raids Déployée');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showModalAlert(data.error || 'Erreur lors du lancement de la tournée.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
    }
}
</script>
