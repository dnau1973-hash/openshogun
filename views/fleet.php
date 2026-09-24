<?php
/**
 * Vue de Gestion et Déploiement des Expéditions Militaires (OpenShogun)
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
    ORDER BY p.id ASC LIMIT 30
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
$stmtOases = $db->prepare("SELECT id, name, coord_x, coord_y, oasis_type, bonus_wood, bonus_stone, bonus_rice, owner_planet_id FROM oases ORDER BY id ASC");
$stmtOases->execute();
$knownOases = $stmtOases->fetchAll();

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

<div class="mb-3 d-print-none">
    <ul class="nav nav-tabs" data-bs-toggle="tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="#tab-manual-fleet" class="nav-link active fw-bold d-flex align-items-center gap-1" data-bs-toggle="tab" aria-selected="true" role="tab">
                <span>🚩</span> Expédition &amp; Manœuvres
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="#tab-farm-lists" class="nav-link fw-bold text-warning d-flex align-items-center gap-1" data-bs-toggle="tab" aria-selected="false" role="tab">
                <span>📜</span> Carnet de Raids (Farm List)
                <span class="badge bg-warning text-dark ms-1"><?= count($allFarmLists) ?></span>
                <?php if ($isSealActive): ?>
                    <span class="badge bg-dark text-warning border border-warning ms-1" style="font-size:0.6rem;">Sceau Actif</span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="#tab-active-missions" class="nav-link fw-bold d-flex align-items-center gap-1" data-bs-toggle="tab" aria-selected="false" role="tab">
                <span>🏇</span> Marches Actives
                <span class="badge bg-secondary ms-1"><?= count($activeMissions) ?></span>
            </a>
        </li>
    </ul>
</div>

<div class="tab-content">
    <!-- ONGLET 1 : EXPÉDITION MANUELLE -->
    <div class="tab-pane active show" id="tab-manual-fleet" role="tabpanel">
        <div class="grid-main">
            <!-- Déploiement d'armée féodale et engins -->
            <div class="card">
        <div class="card-header">
            <h2 class="card-title">🚩 Expédition Militaire & Convois Provinciaux</h2>
            <span style="font-size:0.85rem; color:var(--text-muted);">Fief d'attache : <?= htmlspecialchars($planet['name']) ?></span>
        </div>
        <div class="card-body">
            <?php if ($isMyProtectionActive): ?>
                <div class="alert alert-success d-flex align-items-center gap-3 mb-3" style="background: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.4); border-radius: 8px; padding: 0.85rem 1.1rem; color: #166534;">
                    <span style="font-size: 1.8rem; flex-shrink: 0;">🔰</span>
                    <div style="font-size: 0.88rem; line-height: 1.45;">
                        <strong style="color: #15803d; font-size: 0.95rem;">Immunité Féodale des Nouveaux Joueurs Active (Jusqu'au <?= htmlspecialchars($myProtection['until_formatted']) ?> — encore <?= htmlspecialchars($myProtection['formatted']) ?>)</strong><br>
                        Vos domaines et fiefs sont protégés contre tout raid de pillage, assaut de siège et infiltration shinobi adverse.<br>
                        <span style="color: #b45309; font-weight: 600;">⚔️ Règle martiale :</span> Vous pouvez librement explorer les aventures du Héros et pacifier les oasis sauvages. En revanche, si vous lancez un raid, un assaut ou un espionnage contre un <strong>autre seigneur joueur</strong>, votre immunité sera <strong>définitivement levée</strong>.
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($stationedShips) && empty($stationedUnits) && !$canDeployHero): ?>
                <div style="text-align:center; padding:2rem; background:rgba(255,255,255,0.02); border-radius:8px;">
                    <p style="color:var(--text-muted); margin-bottom:1rem;">Aucun régiment de guerriers, engin de siège ni héros samouraï n'est disponible dans votre garnison.</p>
                    <div style="display:flex; justify-content:center; gap:1rem;">
                        <a href="?page=shipyard" class="btn btn-primary">Atelier de Siège & Écuries</a>
                        <a href="?page=barracks" class="btn btn-secondary">Dojo Militaire</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="fleetForm" onsubmit="event.preventDefault(); submitFleet();">
                    <?php if ($preselectedMission === 'colonize' && !$hasColonistAvailable): ?>
                        <div class="alert alert-warning d-flex align-items-center gap-3 mb-4" style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:1rem 1.25rem;">
                            <span style="font-size:2rem; flex-shrink:0;">⛩️</span>
                            <div style="flex:1;">
                                <strong style="color:#b45309; font-size:1rem;">Aucun Pionnier Féodal (Colon ⛩️) en garnison !</strong>
                                <p style="color:#78350f; font-size:0.88rem; margin:0.25rem 0 0.75rem 0; line-height:1.5;">
                                    Pour ériger votre nouveau fief, vous devez d'abord former un <strong>Pionnier Féodal</strong>. Il est disponible au <strong>Donjon Tenshu</strong> (déblocage aux paliers de niveau 5, 10 et 15) ou à l'<strong>Atelier de Siège</strong>.
                                </p>
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                    <a href="?page=building&code=hq" class="btn btn-sm btn-success fw-bold">
                                        🏯 Former au Donjon Tenshu (Niv. 5+) &rarr;
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
                        <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">🐎 1. Cavalerie, Convois & Engins de Siège</h3>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem;">
                            <?php foreach ($stationedShips as $s): ?>
                                <?php 
                                    $defaultShipVal = ($preselectedMission === 'colonize' && $s['ship_code'] === 'colony_ship' && (int)$s['count'] > 0) ? '1' : '0';
                                ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.5rem 0.75rem; border-radius:6px;">
                                    <div>
                                        <strong><?= htmlspecialchars($s['name']) ?></strong>
                                        <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">
                                            (Dispo : <span id="max-<?= $s['ship_code'] ?>"><?= $s['count'] ?></span>)
                                        </span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.2rem 0.5rem;" 
                                                onclick="document.getElementById('ship-<?= $s['ship_code'] ?>').value = <?= $s['count'] ?>;">
                                            Max
                                        </button>
                                        <input type="number" id="ship-<?= $s['ship_code'] ?>" name="fleet[<?= $s['ship_code'] ?>]" 
                                                min="0" max="<?= $s['count'] ?>" value="<?= $defaultShipVal ?>"
                                                style="width:70px; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.3rem; border-radius:4px; text-align:center;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Étape 2 : Guerriers & Régiments du Dojo (Style Travian) -->
                    <?php if (!empty($stationedUnits)): ?>
                        <h3 style="font-size:0.95rem; color:#4ade80; margin-bottom:0.75rem;">⚔️ 2. Régiments de Guerriers & Samouraïs</h3>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem;">
                            <?php foreach ($stationedUnits as $u): ?>
                                <?php 
                                    $defaultUnitVal = ($preselectedMission === 'colonize' && $u['unit_code'] === 'colonizer' && (int)$u['count'] > 0) ? '1' : '0';
                                ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.5rem 0.75rem; border-radius:6px; border-left:3px solid #dc2626;">
                                    <div>
                                        <span style="font-size:1.1rem; margin-right:0.3rem;"><?= $u['icon'] ?></span>
                                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                                        <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">
                                            (Garnison : <span id="max-<?= $u['unit_code'] ?>"><?= $u['count'] ?></span>)
                                        </span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.2rem 0.5rem;" 
                                                onclick="document.getElementById('ship-<?= $u['unit_code'] ?>').value = <?= $u['count'] ?>;">
                                            Max
                                        </button>
                                        <input type="number" id="ship-<?= $u['unit_code'] ?>" name="fleet[<?= $u['unit_code'] ?>]" 
                                                min="0" max="<?= $u['count'] ?>" value="<?= $defaultUnitVal ?>"
                                                style="width:70px; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.3rem; border-radius:4px; text-align:center;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Étape 3 : Héros Samouraï Champion -->
                    <?php if ($canDeployHero): ?>
                        <h3 style="font-size:0.95rem; color:#f59e0b; margin-bottom:0.75rem;">🥋 3. Champion Samouraï (Héros de Guerre)</h3>
                        <div style="background:linear-gradient(135deg, rgba(234,179,8,0.12), rgba(15,23,42,0.6)); border:1px solid rgba(234,179,8,0.35); border-radius:8px; padding:0.85rem; margin-bottom:1.5rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <div style="width:44px; height:44px; border-radius:50%; border:2px solid #eab308; overflow:hidden; background:#000; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                                        🥋
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#f8fafc; font-size:0.95rem;">
                                            <?= htmlspecialchars($heroData['name']) ?> 
                                            <span style="background:rgba(234,179,8,0.25); color:#fde047; padding:0.1rem 0.4rem; border-radius:4px; font-size:0.75rem; border:1px solid rgba(234,179,8,0.4);">Niv. <?= $heroData['level'] ?></span>
                                        </div>
                                        <div style="font-size:0.8rem; color:#94a3b8; display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:0.25rem;">
                                            <span style="color:#ef4444;">⚔️ Force : <strong><?= $heroEffectiveStats['combat_strength'] ?></strong></span>
                                            <span style="color:#f97316;">🔥 Attaque armée : <strong>+<?= $heroEffectiveStats['offense_bonus_pct'] ?? 0 ?>%</strong></span>
                                            <span style="color:#10b981;">🛡️ Défense garnison : <strong>+<?= $heroEffectiveStats['defense_bonus_pct'] ?? 0 ?>%</strong></span>
                                            <span style="color:#22c55e;">❤️ Vie : <strong><?= $heroData['health'] ?>%</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer; background:rgba(234,179,8,0.2); padding:0.5rem 0.9rem; border-radius:6px; border:1px solid #eab308; font-weight:600; font-size:0.85rem; color:#fef08a; transition:all 0.2s;">
                                    <input type="checkbox" id="deploy_hero" name="has_hero" value="1" style="width:18px; height:18px; cursor:pointer; accent-color:#eab308;">
                                    Accompagner l'expédition
                                </label>
                            </div>
                        </div>
                    <?php elseif ($heroData && $heroData['status'] !== 'home'): ?>
                        <div style="background:rgba(0,0,0,0.2); border:1px dashed rgba(255,255,255,0.1); border-radius:6px; padding:0.6rem 0.85rem; margin-bottom:1.5rem; font-size:0.8rem; color:var(--text-muted);">
                            🥋 Samouraï Héros <strong><?= htmlspecialchars($heroData['name']) ?></strong> : Indisponible (En mission ou en aventure).
                        </div>
                    <?php elseif ($heroData && (int)$heroData['health'] <= 0): ?>
                        <div style="background:rgba(220,38,38,0.1); border:1px dashed #ef4444; border-radius:6px; padding:0.6rem 0.85rem; margin-bottom:1.5rem; font-size:0.8rem; color:#fca5a5;">
                            🥋 Samouraï Héros <strong><?= htmlspecialchars($heroData['name']) ?></strong> est tombé au combat. <a href="?page=hero" style="color:#eab308; text-decoration:underline;">Accomplir le rituel de résurrection</a>.
                        </div>
                    <?php endif; ?>

                    <!-- Étape 4 : Destination -->
                    <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">🗾 4. Destination (Fief Provincial ou Oasis Naturelle)</h3>
                    <div style="margin-bottom:1.5rem;">
                        <select id="targetSelect" style="width:100%; background:rgba(15,23,42,0.9); border:1px solid var(--border-color); color:#fff; padding:0.6rem; border-radius:6px; margin-bottom:0.75rem;">
                            <option value="">-- Sélectionner une destination féodale ou oasis --</option>
                            <optgroup label="🏯 Fiefs & Domaines Provinciaux">
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
                            <optgroup label="🌿 Oasis Naturelles & Fiefs Sauvages (Bonus de Récoltes)">
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

                    <!-- Étape 5 : Ordre de Mission -->
                    <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">⚔️ 5. Ordre Tactique de Marche</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.5rem; margin-bottom:1.5rem;">
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="raid" <?= ($preselectedMission === 'raid') ? 'checked' : '' ?>>
                            <span>⚔️ Raid de Pillage</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="attack" <?= ($preselectedMission === 'attack') ? 'checked' : '' ?>>
                            <span>💥 Assaut de Siège</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="occupy" <?= ($preselectedMission === 'occupy') ? 'checked' : '' ?>>
                            <span>🚩 Occuper / Garnison</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="spy" <?= ($preselectedMission === 'spy') ? 'checked' : '' ?>>
                            <span>🥷 Infiltration Shinobi</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="transport" <?= ($preselectedMission === 'transport') ? 'checked' : '' ?>>
                            <span>🐂 Convoi de Vivres</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="colonize" <?= ($preselectedMission === 'colonize') ? 'checked' : '' ?>>
                            <span>🏯 Fonder un Fief</span>
                        </label>
                    </div>

                    <!-- Étape 6 : Chargement de Fret (Optionnel pour transport) -->
                    <div style="background:rgba(0,0,0,0.25); padding:0.75rem; border-radius:6px; margin-bottom:1.5rem;">
                        <h4 style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">Ressources à convoyer (pour convoi de vivres) :</h4>
                        <div style="display:flex; gap:0.75rem;">
                            <input type="number" id="cargo_metal" placeholder="🪵 Bois" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                            <input type="number" id="cargo_crystal" placeholder="🪨 Pierre" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                            <input type="number" id="cargo_deuterium" placeholder="🌾 Riz" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem; font-weight:700; background: linear-gradient(135deg, #b91c1c, #dc2626); border-color:#ef4444;">
                        🚩 Lancer l'Expédition Féodale
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mouvements Actifs -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏇 Troupes & Convois en Marche</h3>
            </div>
            <div class="card-body">
                <?php if (empty($activeMissions)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1.5rem 0;">Aucune troupe ni convoi en marche.</p>
                <?php else: ?>
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
                        <div class="queue-item" style="flex-direction:column; align-items:flex-start; gap:0.4rem;">
                            <div style="display:flex; justify-content:space-between; width:100%; font-size:0.85rem;">
                                <strong style="color:<?= $isOutbound ? '#dc2626' : '#4ade80' ?>;">
                                    <?= $isOutbound ? '↗️ ' : '↙️ ' ?><?= $missionLabel ?>
                                </strong>
                                <span class="queue-timer" data-countdown="<?= $targetTime ?>">Calcul...</span>
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">
                                Destination : <?= htmlspecialchars($m['target_name']) ?> [<?= $m['tx'] ?> : <?= $m['ty'] ?>]
                            </div>
                            <div style="font-size:0.75rem; color:#94a3b8; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                <span>Effectif : <?= array_sum($fleetData) ?> guerriers & engins</span>
                                <?php if (!empty($m['has_hero'])): ?>
                                    <span style="background:rgba(234,179,8,0.2); color:#fde047; padding:0.1rem 0.4rem; border-radius:4px; font-weight:600; font-size:0.7rem; border:1px solid rgba(234,179,8,0.4);">🥋 Samouraï Héros</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

    <!-- ONGLET 2 : CARNET DE RAIDS (FARM LIST) -->
    <div class="tab-pane" id="tab-farm-lists" role="tabpanel">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title text-warning d-flex align-items-center gap-2 m-0">
                        <span>📜</span> Carnet de Raids Automatisé (Farm List Féodale)
                    </h3>
                    <div class="text-secondary small mt-1">
                        Enregistrez vos cibles récurrentes (oasis d'animaux, domaines inactifs) et lancez des vagues de pillage coordonnées en 1 clic.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning fw-bold btn-sm" onclick="openCreateFarmListModal()">
                        ➕ Nouvelle Liste de Raids
                    </button>
                    <?php if (!$isSealActive): ?>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="openImperialSealModal()">
                            👑 Sceau Impérial
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($allFarmLists)): ?>
                    <div class="text-center py-5">
                        <div class="fs-1 mb-2">📜</div>
                        <h3>Votre Carnet de Raids est vierge</h3>
                        <p class="text-secondary small mb-3">
                            Créez votre première liste de raids pour automatiser le pillage de vos cibles favorites sans recomposer vos armées à chaque fois.
                        </p>
                        <button type="button" class="btn btn-warning fw-bold" onclick="openCreateFarmListModal()">
                            ➕ Créer ma première Liste de Raids
                        </button>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($allFarmLists as $fl): ?>
                            <div class="card border shadow-sm">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#fafaf9;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-3">⚔️</span>
                                        <div>
                                            <strong class="text-dark fs-4"><?= htmlspecialchars($fl['name']) ?></strong>
                                            <div class="text-muted small">
                                                Fief de déploiement : <strong><?= htmlspecialchars($fl['source_planet_name']) ?></strong> [<?= $fl['source_coord_x'] ?>|<?= $fl['source_coord_y'] ?>]
                                                &bull; <?= count($fl['entries']) ?> cible(s) enregistrée(s)
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-success fw-bold btn-sm" 
                                                onclick="runFullFarmList(<?= $fl['id'] ?>, '<?= htmlspecialchars(addslashes($fl['name'])) ?>')"
                                                <?= empty($fl['entries']) ? 'disabled' : '' ?>>
                                            ⚡ Lancer la Tournée en 1 Clic (<?= count($fl['entries']) ?> raids)
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
                                    <table class="table table-vcenter table-striped table-hover m-0">
                                        <thead>
                                            <tr class="text-muted small" style="background:rgba(0,0,0,0.02);">
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
                                                    <td colspan="5" class="text-center py-4 text-muted small">
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
                                                            <div class="text-muted font-monospace small">
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
                                                                <div class="small text-muted"><?= date('d/m H:i', strtotime($entry['last_raid_at'])) ?></div>
                                                                <span class="badge bg-info-lt" style="font-size:0.65rem;"><?= htmlspecialchars($entry['last_status'] ?? 'achevé') ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted small italic">Jamais attaquée</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-success" 
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

    <!-- ONGLET 3 : MARCHES ACTIVES (VUE DÉTAILLÉE) -->
    <div class="tab-pane" id="tab-active-missions" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏇 Toutes les Marches et Expéditions Féodales en Cours</h3>
                <div class="card-options"><span class="badge bg-secondary"><?= count($activeMissions) ?> active(s)</span></div>
            </div>
            <div class="card-body">
                <?php if (empty($activeMissions)): ?>
                    <div class="text-center py-5 text-muted">
                        <div class="fs-1 mb-2">🚩</div>
                        <h3>Aucune troupe en marche</h3>
                        <p class="small">Toutes vos armées sont actuellement stationnées dans vos garnisons castrales.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-striped">
                            <thead>
                                <tr class="text-muted small">
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
                                        <strong><?= htmlspecialchars($m['source_name']) ?></strong> [<?= $m['sx'] ?>|<?= $m['sy'] ?>]
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($m['target_name']) ?></strong> [<?= $m['tx'] ?>|<?= $m['ty'] ?>]
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
                                        <span class="badge bg-secondary-lt"><?= $isOutbound ? 'En marche vers la cible' : 'Retour vers le fief' ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary text-white font-monospace p-2 fs-6" data-countdown="<?= $targetTime ?>">
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

<!-- ================= MODALE CRÉATION D'UNE FARM LIST ================= -->
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
                <button type="button" class="btn btn-warning fw-bold" onclick="submitCreateFarmList()">
                    📜 Établir la Liste
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALE AJOUT DE CIBLE AU CARNET ================= -->
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
                                <option value="<?= $to['type'] ?>:<?= $to['id'] ?>:<?= $to['name'] ?>:<?= $to['x'] ?>:<?= $to['y'] ?>">
                                    <?= htmlspecialchars($to['name']) ?> [<?= $to['x'] ?>|<?= $to['y'] ?>] &bull; <?= round($to['distance'], 1) ?> cases
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
                                    <span><?= $u['icon'] ?></span>
                                    <div>
                                        <strong class="small"><?= htmlspecialchars($u['name']) ?></strong>
                                        <div class="text-muted" style="font-size:0.7rem;">Dispo en garnison : <?= $u['count'] ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.65rem;" onclick="document.getElementById('farm_unit_<?= $u['unit_code'] ?>').value = 5;">5</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.65rem;" onclick="document.getElementById('farm_unit_<?= $u['unit_code'] ?>').value = 10;">10</button>
                                    <input type="number" id="farm_unit_<?= $u['unit_code'] ?>" class="form-control form-control-sm text-center farm-fleet-input" data-unit="<?= $u['unit_code'] ?>" min="0" value="0" style="width:65px;">
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($stationedShips as $s): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span>🐎</span>
                                    <div>
                                        <strong class="small"><?= htmlspecialchars($s['name']) ?></strong>
                                        <div class="text-muted" style="font-size:0.7rem;">Dispo en garnison : <?= $s['count'] ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" style="font-size:0.65rem;" onclick="document.getElementById('farm_unit_<?= $s['ship_code'] ?>').value = 5;">5</button>
                                    <input type="number" id="farm_unit_<?= $s['ship_code'] ?>" class="form-control form-control-sm text-center farm-fleet-input" data-unit="<?= $s['ship_code'] ?>" min="0" value="0" style="width:65px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="submitAddFarmEntry()">
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

    // Contrôle d'immunité féodale de la cible
    const targetSelect = document.getElementById('targetSelect');
    const selectedOpt = targetSelect.options[targetSelect.selectedIndex];
    const isTargetProtected = selectedOpt && selectedOpt.dataset.protected === '1';
    const hostileMissions = ['raid', 'attack', 'occupy', 'spy'];
    // Contrôle d'éligibilité pour la colonisation
    if (missionType === 'colonize') {
        const colonizerCnt = parseInt(document.getElementById('ship-colonizer')?.value || '0', 10);
        const colonyShipCnt = parseInt(document.getElementById('ship-colony_ship')?.value || '0', 10);
        if (colonizerCnt <= 0 && colonyShipCnt <= 0) {
            showModalAlert("Une expédition de colonisation nécessite d'inclure au moins 1 Pionnier Féodal (Colon ⛩️). Si vous n'en avez pas encore en garnison, vous devez d'abord en former un au Donjon Tenshu (Niv. 5+) ou à l'Atelier de Siège.", "warning", "Pionnier Requis");
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

