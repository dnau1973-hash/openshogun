<?php
/**
 * Header Partiel OpenGalaxy
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/BuildingEngine.php';
require_once __DIR__ . '/../../core/FleetEngine.php';
require_once __DIR__ . '/../../core/MessageEngine.php';
require_once __DIR__ . '/../../core/QuestEngine.php';
require_once __DIR__ . '/../../core/HeroEngine.php';
require_once __DIR__ . '/../../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$messageEngine = new MessageEngine();
$unreadMessagesCount = $messageEngine->getUnreadCount((int)$user['id']);

$questEngine = new QuestEngine();
$questSummary = ($user && $planet) ? $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']) : null;

$heroEngine = new HeroEngine();
if ($user) {
    $heroEngine->ensureAvailableAdventures((int)$user['id'], 3);
}
$heroHeader = $user ? $heroEngine->getHeroByUserId((int)$user['id']) : null;

if ($planet) {
    $planetEngine = new PlanetEngine();
    $fleetEngine = new FleetEngine();
    
    // Mettre à jour les flottes et la planète
    $fleetEngine->processFleetMissions();
    $planet = $planetEngine->updatePlanet((int)$planet['id']);
    
    // ⚔️ Simulation autonome des PNJ / Bots (espionnage, raids, chantiers)
    require_once __DIR__ . '/../../core/BotEngine.php';
    $botEngine = new BotEngine();
    $botEngine->tickPeriodicSimulation();
    
    // Vérifier les flottes en mouvement pour l'alerte HUD (avec détails des fiefs et clans)
    $db = Database::getConnection();
    $stmtMissions = $db->prepare("
        SELECT m.*, 
               u_sender.username as sender_username, u_sender.faction as sender_faction,
               p_src.name as source_planet_name, p_src.coord_x as source_coord_x, p_src.coord_y as source_coord_y,
               COALESCE(p_tgt.name, CONCAT('Oasis ', o.name), 'Province Sauvage') as target_planet_name,
               COALESCE(p_tgt.coord_x, o.coord_x, 0) as target_coord_x,
               COALESCE(p_tgt.coord_y, o.coord_y, 0) as target_coord_y
        FROM fleet_missions m
        LEFT JOIN users u_sender ON m.user_id = u_sender.id
        LEFT JOIN planets p_src ON m.source_planet_id = p_src.id
        LEFT JOIN planets p_tgt ON m.target_planet_id = p_tgt.id
        LEFT JOIN oases o ON m.target_oasis_id = o.id
        WHERE (m.user_id = ? OR m.target_planet_id = ?) AND m.status IN ('en_route', 'returning') 
        ORDER BY m.arrival_time ASC
    ");
    $stmtMissions->execute([$user['id'], $planet['id']]);
    $activeMissions = $stmtMissions->fetchAll();

    $uRows = $db->query("SELECT code, name, icon FROM units")->fetchAll(PDO::FETCH_ASSOC);
    $unitsMap = [];
    foreach ($uRows as $ur) {
        $unitsMap[$ur['code']] = $ur;
    }
    $sRows = $db->query("SELECT code, name FROM ships")->fetchAll(PDO::FETCH_ASSOC);
    $shipsMap = [];
    foreach ($sRows as $sr) {
        $shipsMap[$sr['code']] = $sr;
    }

    $incomingHostile = [];
    $incomingSpy = [];
    $outgoingMissions = [];
    foreach ($activeMissions as $m) {
        if ($m['target_planet_id'] == $planet['id'] && $m['status'] === 'en_route') {
            if ($m['mission_type'] === 'spy') {
                $incomingSpy[] = $m;
            } else {
                $incomingHostile[] = $m;
            }
        } else {
            $outgoingMissions[] = $m;
        }
    }
}

$page = $_GET['page'] ?? 'resources';
$factionInfo = FACTIONS[$user['faction']] ?? FACTIONS['terran'];
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> - Chroniques Féodales du Sengoku</title>
    <!-- Tabler UI Framework (local) -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <!-- HUD Travian Féodal (header circulaire, barres de ressources, alertes) -->
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏯</text></svg>">
</head>
<body class="antialiased">


<header class="hud-header">
    <!-- 🏯 LOGO TOUT EN HAUT CENTRÉ ET PLUS GROS -->
    <div class="hud-brand-header">
        <a href="?page=resources" class="brand-logo-link" title="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>">
            <img src="/public/assets/logo_transparent.png?v=<?= file_exists(__DIR__ . '/../../public/assets/logo_transparent.png') ? filemtime(__DIR__ . '/../../public/assets/logo_transparent.png') : 1 ?>" 
                 alt="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>" 
                 class="brand-logo-img">
        </a>
    </div>

    <!-- 🧭 BARRE DE COMMANDE TRAVIAN SENGOKU -->
    <?php 
        $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
        $hHpCol = ($hHp >= 60) ? '#16a34a' : (($hHp >= 25) ? '#d97706' : '#dc2626');
        $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
        $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
    ?>
    <div class="travian-hud-console">
        <div class="travian-bar-wrapper">
            <div class="travian-bar-inner">
            
            <!-- 🥋 GAUCHE : MÉDAILLON DU HÉROS SAMOURAÏ (STYLE TRAVIAN) -->
            <div class="travian-hero-pod">
                <a href="?page=hero" class="travian-hero-disc <?= ($page === 'hero') ? 'active' : '' ?>" title="Votre Samouraï Héros (Niveau <?= $hLvl ?> - Vitalité : <?= $hHp ?>%)">
                    <img src="/public/assets/hero_samurai.jpg" alt="🥋" class="travian-hero-img">
                    <span class="travian-hero-lvl-tag"><?= $hLvl ?></span>
                    <span class="travian-hero-hp-ring" style="border-color: <?= $hHpCol ?>;"></span>
                    <?php if ($hasPoints): ?>
                        <span class="travian-hero-bonus" title="<?= $heroHeader['unassigned_points'] ?> point(s) d'attributs à répartir !">+<?= $heroHeader['unassigned_points'] ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- 🏛️ CENTRE : COMMANDES TRAVIAN & CARTOUCHES DE RESSOURCES -->
            <div class="travian-center-stack">
                
                <!-- RANGÉE 1 : BOUTONS CIRCULAIRES & FIEF -->
                <div class="travian-nav-row">
                    <!-- 1. Terroir (Champs / Dorf 1) -->
                    <a href="?page=resources" class="travian-circle-btn <?= ($page === 'resources' || $page === 'field') ? 'active' : '' ?>" title="Terroir & Récoltes (Parcelles Rurales)">
                        <img src="/public/assets/nav_resources.jpg" alt="Terroir" class="travian-circle-img">
                        <span class="travian-tooltip">Terroir Féodal</span>
                    </a>

                    <!-- 2. Cité Castrale (Bâtiments / Dorf 2) -->
                    <a href="?page=city" class="travian-circle-btn <?= ($page === 'city') ? 'active' : '' ?>" title="Cité Castrale (Bâtiments & Châteaux)">
                        <img src="/public/assets/nav_colony.jpg" alt="Cité" class="travian-circle-img">
                        <span class="travian-tooltip">Cité Castrale</span>
                    </a>

                    <!-- 3. Provinces du Japon (Carte) -->
                    <a href="?page=map" class="travian-circle-btn <?= ($page === 'map' || $page === 'galaxy') ? 'active' : '' ?>" title="Carte des Provinces Féodales">
                        <img src="/public/assets/nav_map.jpg" alt="Carte" class="travian-circle-img">
                        <span class="travian-tooltip">Carte des Provinces</span>
                    </a>

                    <!-- 4. Tableau d'Honneur / Classement -->
                    <a href="?page=ranking" class="travian-circle-btn <?= ($page === 'ranking') ? 'active' : '' ?>" title="Classement des Daimyōs (<?= number_format($user['points']) ?> pts)">
                        <span class="travian-icon-badge">🏆</span>
                        <span class="travian-tooltip">Classement</span>
                    </a>

                    <!-- 5. Chroniques & Rapports de Guerre -->
                    <a href="?page=reports" class="travian-circle-btn <?= ($page === 'reports') ? 'active' : '' ?>" title="Chroniques de Siège & Rapports d'Infiltration">
                        <span class="travian-icon-badge">📜</span>
                        <span class="travian-tooltip">Rapports de Bataille</span>
                    </a>

                    <!-- 6. Missives des Clans -->
                    <a href="?page=messages" class="travian-circle-btn <?= ($unreadMessagesCount > 0) ? 'has-unread' : '' ?> <?= ($page === 'messages') ? 'active' : '' ?>" title="Missives & Correspondance des Clans">
                        <span class="travian-icon-badge">✉️</span>
                        <?php if ($unreadMessagesCount > 0): ?>
                            <span class="travian-wax-badge"><?= $unreadMessagesCount ?></span>
                        <?php endif; ?>
                        <span class="travian-tooltip">Missives (<?= $unreadMessagesCount ?>)</span>
                    </a>

                    <!-- 7. Didacticiel & Quêtes Féodales -->
                    <?php if ($questSummary): ?>
                        <button type="button" onclick="openQuestModal()" class="travian-circle-btn quest-btn <?= ($questSummary['claimable_count'] > 0) ? 'has-unread' : '' ?>" title="Didacticiel & Quêtes Féodales (<?= $questSummary['claimed_count'] ?>/<?= $questSummary['total_quests'] ?>)">
                            <span class="travian-icon-badge">🎯</span>
                            <?php if ($questSummary['claimable_count'] > 0): ?>
                                <span class="travian-wax-badge claimable"><?= $questSummary['claimable_count'] ?></span>
                            <?php elseif (!$questSummary['all_completed']): ?>
                                <span class="travian-wax-badge progress-badge"><?= $questSummary['claimed_count'] ?>/<?= $questSummary['total_quests'] ?></span>
                            <?php endif; ?>
                            <span class="travian-tooltip">Quêtes Féodales</span>
                        </button>
                    <?php endif; ?>

                    <!-- Plaque du Fief / Domaine & Coordonnées -->
                    <div class="travian-domain-plaque" title="Domaine Actuel & Coordonnées Cadastrales">
                        <span class="domain-crest">🏯</span>
                        <span class="domain-name"><?= htmlspecialchars($planet['name']) ?></span>
                        <span class="domain-coords">[<?= $planet['coord_x'] ?>|<?= $planet['coord_y'] ?>]</span>
                    </div>

                    <!-- 🪙 Médaillon Mon Impérial Shogun (Style Médaillon Doré Travian) -->
                    <a href="?page=ranking" class="travian-shogun-mon-pod" title="Ordre Impérial & Honneur du Shōgun (<?= number_format($user['points']) ?> points)">
                        <div class="travian-shogun-mon">
                            <span class="shogun-mon-symbol">将</span>
                        </div>
                    </a>
                </div>

                <!-- RANGÉE 2 : LES 2 CARTOUCHES DE RESSOURCES TRAVIAN -->
                <div class="travian-res-deck">
                    <!-- Cartouche 1 : Bois de Cèdre & Pierre de Taille + Entrepôt -->
                    <div class="travian-cartridge">
                        <div class="t-cap-cell" title="Capacité Maximale des Entrepôts : <?= number_format($planet['metal_max']) ?>">
                            <span class="t-cap-ico">🏛️</span>
                            <span class="t-cap-num"><?= number_format($planet['metal_max']) ?></span>
                        </div>

                        <!-- Bois de Cèdre -->
                        <div class="t-res-cell" title="Bois de Cèdre : <?= number_format((int)$planet['metal']) ?> / <?= number_format($planet['metal_max']) ?> (+<?= number_format($planet['prod_rates']['metal']) ?>/h)">
                            <span class="t-res-ico">🪵</span>
                            <div class="t-res-core">
                                <span class="t-res-val" id="res-val-metal" data-current="<?= $planet['metal'] ?>" data-max="<?= $planet['metal_max'] ?>" data-prod="<?= $planet['prod_rates']['metal'] ?>">
                                    <?= number_format((int)$planet['metal']) ?>
                                </span>
                                <div class="t-res-meter">
                                    <div class="t-meter-bar wood" id="bar-metal" style="width: <?= min(100, ($planet['metal'] / $planet['metal_max']) * 100) ?>%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pierre de Taille -->
                        <div class="t-res-cell" title="Pierre de Taille : <?= number_format((int)$planet['crystal']) ?> / <?= number_format($planet['crystal_max']) ?> (+<?= number_format($planet['prod_rates']['crystal']) ?>/h)">
                            <span class="t-res-ico">🪨</span>
                            <div class="t-res-core">
                                <span class="t-res-val" id="res-val-crystal" data-current="<?= $planet['crystal'] ?>" data-max="<?= $planet['crystal_max'] ?>" data-prod="<?= $planet['prod_rates']['crystal'] ?>">
                                    <?= number_format((int)$planet['crystal']) ?>
                                </span>
                                <div class="t-res-meter">
                                    <div class="t-meter-bar stone" id="bar-crystal" style="width: <?= min(100, ($planet['crystal'] / $planet['crystal_max']) * 100) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cartouche 2 : Riz Impérial & Sérénité + Grenier -->
                    <div class="travian-cartridge">
                        <div class="t-cap-cell" title="Capacité Maximale du Grenier à Riz : <?= number_format($planet['deuterium_max']) ?>">
                            <span class="t-cap-ico">🏯</span>
                            <span class="t-cap-num"><?= number_format($planet['deuterium_max']) ?></span>
                        </div>

                        <!-- Riz Impérial -->
                        <div class="t-res-cell" title="Riz Impérial : <?= number_format((int)$planet['deuterium']) ?> / <?= number_format($planet['deuterium_max']) ?> (+<?= number_format($planet['prod_rates']['deuterium']) ?>/h)">
                            <span class="t-res-ico">🌾</span>
                            <div class="t-res-core">
                                <span class="t-res-val" id="res-val-deut" data-current="<?= $planet['deuterium'] ?>" data-max="<?= $planet['deuterium_max'] ?>" data-prod="<?= $planet['prod_rates']['deuterium'] ?>">
                                    <?= number_format((int)$planet['deuterium']) ?>
                                </span>
                                <div class="t-res-meter">
                                    <div class="t-meter-bar crop" id="bar-deut" style="width: <?= min(100, ($planet['deuterium'] / $planet['deuterium_max']) * 100) ?>%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Ferveur & Sérénité Shinto -->
                        <div class="t-res-cell" title="Sérénité & Ferveur du Sanctuaire (<?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?>)">
                            <span class="t-res-ico">⛩️</span>
                            <div class="t-res-core">
                                <span class="t-res-val" style="color: <?= ($planet['energy_max'] >= $planet['energy_used']) ? '#15803d' : '#b91c1c' ?>;">
                                    <?= ($planet['energy_max'] - $planet['energy_used']) ?>
                                </span>
                                <div class="t-res-meter">
                                    <div class="t-meter-bar shinto" style="width: <?= min(100, ($planet['energy_used'] / max(1, $planet['energy_max'])) * 100) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ⚙️ DROITE : PROFIL DU DAIMYŌ & OUTILS SYSTÈME (STYLE TRAVIAN) -->
            <div class="travian-right-flank">
                <div class="travian-daimyo-badge">
                    <span class="faction-dot <?= htmlspecialchars($user['faction']) ?>" title="Clan <?= htmlspecialchars($factionInfo['name']) ?>"></span>
                    <span class="daimyo-name" onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)" title="Consulter votre Fiche de Daimyō">
                        <?= htmlspecialchars($user['username']) ?>
                    </span>
                    <button type="button" onclick="openEditMottoModal()" class="motto-chip" title="Modifier ma Devise">📜</button>
                </div>

                <div class="travian-sys-cluster">
                    <button type="button" id="shogun-audio-btn" class="travian-sys-btn audio-btn" onclick="window.shogunAudio && window.shogunAudio.toggle()" title="Ambiance Sonore Féodale">
                        <span id="shogun-audio-icon">🔇</span>
                    </button>
                    <a href="?page=docs" class="travian-sys-btn" title="Codex & Manuel du Jeu">📖</a>
                    <a href="?page=support" class="travian-sys-btn" title="Assistance & Signalements">📮</a>
                    <?php if ($auth->isAdmin()): ?>
                        <a href="?page=admin" class="travian-sys-btn admin" title="QG d'Administration">⚙️</a>
                    <?php endif; ?>
                    <a href="?action=logout" class="travian-sys-btn exit" title="Se déconnecter">❌</a>
                </div>
            </div>

        </div>
    </div>
</div> <!-- Fin .travian-hud-console -->

    <!-- ⚠️ Tour de Guet Féodale : Message d'alerte Unique Interactif (Sans scroll) -->
    <?php if (!empty($activeMissions)): ?>
        <?php 
            $hasHostile = count($incomingHostile) > 0;
            $hasSpy = count($incomingSpy) > 0;
            $alertClass = $hasHostile ? 'alert-threat' : ($hasSpy ? 'alert-spy' : 'alert-info');
            $closest = !empty($incomingHostile) ? $incomingHostile[0] : (!empty($incomingSpy) ? $incomingSpy[0] : $outgoingMissions[0]);
            $closestTime = ($closest['status'] === 'en_route') ? $closest['arrival_time'] : $closest['return_time'];
        ?>
        <div class="travian-alert-banner <?= $alertClass ?>" onclick="openWatchtowerModal()" title="Cliquer pour afficher le registre détaillé de la Tour de Guet (<?= count($activeMissions) ?> mouvements)">
            <div class="alert-banner-left">
                <?php if ($hasHostile): ?>
                    <span class="alert-status-badge threat">🚨 TOUR DE GUET</span>
                    <span class="alert-headline">
                        <strong><?= count($incomingHostile) ?> incursion(s) armée(s)</strong> en approche de votre fief !
                    </span>
                    <span class="alert-countdown-chip">
                        Impact dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong>
                    </span>
                <?php elseif ($hasSpy): ?>
                    <span class="alert-status-badge spy">🥷 TOUR DE GUET</span>
                    <span class="alert-headline">
                        <strong>Infiltration Shinobi détectée</strong> en direction de votre domaine !
                    </span>
                    <span class="alert-countdown-chip">
                        Arrivée dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong>
                    </span>
                <?php else: ?>
                    <span class="alert-status-badge info">🐎 EXPÉDITIONS</span>
                    <span class="alert-headline">
                        <strong><?= count($outgoingMissions) ?> troupe(s) du clan</strong> en marche sur les provinces.
                    </span>
                    <span class="alert-countdown-chip">
                        Retour dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong>
                    </span>
                <?php endif; ?>
            </div>

            <div class="alert-banner-right">
                <span class="alert-cta-btn">
                    <span>📜 Voir les détails (<?= count($activeMissions) ?>)</span>
                    <span class="alert-cta-arrow">&rarr;</span>
                </span>
            </div>
        </div>
    <?php endif; ?>

</header>

<?php
$navItems = [
    ['page' => 'resources', 'match' => ['resources','field'], 'icon' => '🌾', 'label' => 'Terroir Féodal',     'title' => 'Terroir & Récoltes'],
    ['page' => 'city',      'match' => ['city','building'],   'icon' => '🏯', 'label' => 'Cité Castrale',      'title' => 'Bâtiments & Châteaux'],
    ['page' => 'map',       'match' => ['map','galaxy'],      'icon' => '🗾', 'label' => 'Carte',               'title' => 'Carte des Provinces'],
    ['page' => 'fleet',     'match' => ['fleet'],             'icon' => '⚔️', 'label' => 'Armées',             'title' => 'Expéditions militaires'],
    ['page' => 'hero',      'match' => ['hero'],              'icon' => '🥋', 'label' => 'Héros',              'title' => 'Votre Samouraï Héros'],
    ['page' => 'ranking',   'match' => ['ranking'],           'icon' => '🏆', 'label' => 'Classement',         'title' => 'Tableau d\'honneur'],
    ['page' => 'reports',   'match' => ['reports'],           'icon' => '📜', 'label' => 'Chroniques',         'title' => 'Rapports de bataille'],
    ['page' => 'messages',  'match' => ['messages'],          'icon' => '✉️', 'label' => 'Missives',           'title' => 'Correspondance des clans',
     'badge' => $unreadMessagesCount > 0 ? $unreadMessagesCount : null],
];
?>

<!-- ===== LAYOUT TABLER PLEINE LARGEUR (pas de sidebar) ===== -->
<div class="page">
    <div class="page-wrapper">

        <!-- ── BARRE 1 : Ressources + Héros + Profil Daimyō ── -->
        <header class="navbar navbar-expand-md d-print-none"
                style="border-bottom:1px solid rgba(194,37,43,0.2);
                       background:linear-gradient(135deg,rgba(194,37,43,0.04) 0%,rgba(255,255,255,0.98) 100%);
                       padding:0; min-height:52px;">
            <div class="container-fluid px-3">

                <!-- Logo -->
                <a href="?page=resources" class="navbar-brand me-3 p-0" title="Accueil">
                    <img src="/public/assets/logo_transparent.png" alt="OpenShogun"
                         style="height:36px; width:auto; object-fit:contain;">
                </a>

                <!-- Fief & coordonnées -->
                <div class="d-flex align-items-center gap-2 me-4">
                    <div>
                        <div style="font-weight:800; font-size:0.88rem; color:#1e293b; line-height:1.1;"><?= htmlspecialchars($planet['name']) ?></div>
                        <div style="font-size:0.7rem; color:#dc2626; font-family:monospace; font-weight:700;">[<?= $planet['coord_x'] ?>|<?= $planet['coord_y'] ?>]</div>
                    </div>
                </div>

                <!-- Barres de ressources (masquées sur mobile) -->
                <div class="d-none d-md-flex align-items-center gap-3 flex-fill" style="min-width:0;">
                    <?php
                    $resItems = [
                        ['id'=>'metal',   'icon'=>'🪵', 'label'=>'Bois',   'cur'=>$planet['metal'],     'max'=>$planet['metal_max'],     'prod'=>$planet['prod_rates']['metal'],     'color'=>'#92400e', 'bar'=>'bg-warning'],
                        ['id'=>'crystal', 'icon'=>'🪨', 'label'=>'Pierre', 'cur'=>$planet['crystal'],   'max'=>$planet['crystal_max'],   'prod'=>$planet['prod_rates']['crystal'],   'color'=>'#1e40af', 'bar'=>'bg-primary'],
                        ['id'=>'deut',    'icon'=>'🌾', 'label'=>'Riz',    'cur'=>$planet['deuterium'], 'max'=>$planet['deuterium_max'], 'prod'=>$planet['prod_rates']['deuterium'], 'color'=>'#166534', 'bar'=>'bg-success'],
                    ];
                    foreach ($resItems as $r):
                        $pct = min(100, ($r['cur'] / max(1, $r['max'])) * 100);
                    ?>
                    <div class="d-flex align-items-center gap-1"
                         style="min-width:110px; max-width:190px; flex:1;"
                         title="<?= $r['label'] ?> : <?= number_format((int)$r['cur']) ?> / <?= number_format($r['max']) ?> (+<?= number_format($r['prod']) ?>/h)">
                        <span style="font-size:0.9rem;"><?= $r['icon'] ?></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:0.7rem; font-weight:700; color:<?= $r['color'] ?>; white-space:nowrap;">
                                <span id="res-val-<?= $r['id'] ?>"
                                      data-current="<?= $r['cur'] ?>"
                                      data-max="<?= $r['max'] ?>"
                                      data-prod="<?= $r['prod'] ?>"><?= number_format((int)$r['cur']) ?></span>
                                <span style="opacity:0.55; font-weight:400;">/ <?= number_format($r['max']) ?></span>
                            </div>
                            <div class="progress" style="height:4px; border-radius:2px; margin-top:2px;">
                                <div class="progress-bar <?= $r['bar'] ?>" id="bar-<?= $r['id'] ?>" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Sérénité -->
                    <?php $eBalance = $planet['energy_max'] - $planet['energy_used']; $eOk = ($eBalance >= 0); ?>
                    <div class="d-flex align-items-center gap-1"
                         title="Sérénité Shinto : <?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?>">
                        <span style="font-size:0.9rem;">⛩️</span>
                        <span style="font-size:0.8rem; font-weight:800; color:<?= $eOk ? '#166534' : '#dc2626' ?>;"><?= $eBalance ?></span>
                    </div>
                </div>

                <!-- Héros + Profil -->
                <div class="d-flex align-items-center gap-2 ms-3">
                    <?php
                    $hHp = $heroHeader ? round((float)$heroHeader['health']) : 100;
                    $hHpCol = ($hHp >= 60) ? '#16a34a' : (($hHp >= 25) ? '#d97706' : '#dc2626');
                    $hLvl = $heroHeader ? (int)$heroHeader['level'] : 1;
                    $hasPoints = ($heroHeader && (int)$heroHeader['unassigned_points'] > 0);
                    ?>
                    <a href="?page=hero" class="position-relative"
                       title="Samouraï Héros Niv.<?= $hLvl ?> — Vitalité <?= $hHp ?>%">
                        <img src="/public/assets/hero_samurai.jpg" alt="Héros"
                             style="width:34px; height:34px; border-radius:50%; border:2px solid <?= $hHpCol ?>; object-fit:cover;">
                        <span class="badge bg-dark text-white position-absolute"
                              style="bottom:-3px; right:-3px; font-size:0.58rem; padding:1px 3px; border-radius:3px;"><?= $hLvl ?></span>
                        <?php if ($hasPoints): ?>
                            <span class="badge bg-danger position-absolute"
                                  style="top:-3px; right:-3px; font-size:0.58rem; padding:1px 4px; border-radius:50%;">+</span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span style="width:8px; height:8px; border-radius:50%; display:inline-block;
                                         background:<?= $factionInfo['color'] ?? '#dc2626' ?>;"></span>
                            <span style="font-weight:700; font-size:0.85rem; color:#1e293b;"><?= htmlspecialchars($user['username']) ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" class="dropdown-item"
                               onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)">👤 Ma Fiche Daimyō</a>
                            <a href="javascript:void(0)" class="dropdown-item"
                               onclick="openEditMottoModal()">📜 Ma Devise</a>
                            <a href="?page=support" class="dropdown-item">📮 Support</a>
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item" id="shogun-audio-btn"
                                    onclick="window.shogunAudio && window.shogunAudio.toggle()">
                                <span id="shogun-audio-icon">🔇</span> Ambiance sonore
                            </button>
                            <button type="button" class="dropdown-item" onclick="toggleTheme()">🌓 Thème clair / sombre</button>
                            <div class="dropdown-divider"></div>
                            <a href="?action=logout" class="dropdown-item text-danger">🚪 Déconnexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── BARRE 2 : Navigation principale ── -->
        <nav class="navbar navbar-expand-md d-print-none"
             style="background:linear-gradient(135deg,#1a0a00 0%,#2d1200 60%,#1a0a00 100%);
                    border-bottom:2px solid rgba(194,37,43,0.5);
                    min-height:44px; padding:0;">
            <div class="container-fluid px-3">

                <!-- Toggler mobile -->
                <button class="navbar-toggler border-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#mainNavBar"
                        style="color:#f59e0b;">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavBar">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($navItems as $nav):
                            $isActive = in_array($page, $nav['match']);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 d-flex align-items-center gap-1 position-relative
                                       <?= $isActive ? 'active fw-bold' : '' ?>"
                               href="?page=<?= $nav['page'] ?>"
                               title="<?= htmlspecialchars($nav['title']) ?>"
                               style="color:<?= $isActive ? '#fbbf24' : '#e2d9c8' ?>;
                                      font-size:0.82rem; white-space:nowrap;
                                      border-bottom:<?= $isActive ? '2px solid #dc2626' : '2px solid transparent' ?>;
                                      border-radius:0;">
                                <span><?= $nav['icon'] ?></span>
                                <span><?= $nav['label'] ?></span>
                                <?php if (!empty($nav['badge'])): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:0.6rem;"><?= $nav['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>

                        <?php if ($questSummary): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 d-flex align-items-center gap-1 position-relative"
                               href="javascript:void(0)" onclick="openQuestModal()"
                               title="Didacticiel & Quêtes Féodales"
                               style="color:#e2d9c8; font-size:0.82rem; white-space:nowrap;
                                      border-bottom:2px solid transparent; border-radius:0;">
                                <span>🎯</span>
                                <span>Quêtes</span>
                                <?php if ($questSummary['claimable_count'] > 0): ?>
                                    <span class="badge bg-success ms-1" style="font-size:0.6rem;"><?= $questSummary['claimable_count'] ?></span>
                                <?php elseif (!$questSummary['all_completed']): ?>
                                    <span class="badge bg-secondary ms-1" style="font-size:0.6rem;"><?= $questSummary['claimed_count'] ?>/<?= $questSummary['total_quests'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Outils à droite -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link px-2 py-2" href="?page=docs"
                               title="Codex du Sengoku" style="color:#e2d9c8; font-size:0.82rem;">📖</a>
                        </li>
                        <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link px-2 py-2" href="?page=admin"
                               title="Administration" style="color:#fbbf24; font-size:0.82rem;">⚙️ Admin</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Bannière d'alerte Tour de Guet -->
        <?php if (!empty($activeMissions)): ?>
        <?php
            $hasHostile = count($incomingHostile) > 0;
            $hasSpy     = count($incomingSpy) > 0;
            $alertClass = $hasHostile ? 'alert-threat' : ($hasSpy ? 'alert-spy' : 'alert-info');
            $closest    = !empty($incomingHostile) ? $incomingHostile[0] : (!empty($incomingSpy) ? $incomingSpy[0] : $outgoingMissions[0]);
            $closestTime = ($closest['status'] === 'en_route') ? $closest['arrival_time'] : $closest['return_time'];
        ?>
        <div class="travian-alert-banner <?= $alertClass ?>"
             onclick="openWatchtowerModal()"
             title="Cliquer pour afficher le registre de la Tour de Guet (<?= count($activeMissions) ?> mouvements)">
            <div class="alert-banner-left">
                <?php if ($hasHostile): ?>
                    <span class="alert-status-badge threat">🚨 TOUR DE GUET</span>
                    <span class="alert-headline"><strong><?= count($incomingHostile) ?> incursion(s) armée(s)</strong> en approche !</span>
                    <span class="alert-countdown-chip">Impact dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                <?php elseif ($hasSpy): ?>
                    <span class="alert-status-badge spy">🥷 TOUR DE GUET</span>
                    <span class="alert-headline"><strong>Infiltration Shinobi détectée</strong> vers votre domaine !</span>
                    <span class="alert-countdown-chip">Arrivée dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                <?php else: ?>
                    <span class="alert-status-badge info">🐎 EXPÉDITIONS</span>
                    <span class="alert-headline"><strong><?= count($outgoingMissions) ?> troupe(s)</strong> en marche sur les provinces.</span>
                    <span class="alert-countdown-chip">Retour dans <strong data-countdown="<?= $closestTime ?>">Calcul...</strong></span>
                <?php endif; ?>
            </div>
            <div class="alert-banner-right">
                <span class="alert-cta-btn">
                    <span>📜 Détails (<?= count($activeMissions) ?>)</span>
                    <span class="alert-cta-arrow">&rarr;</span>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- CORPS DE PAGE -->
        <div class="page-body">
            <div class="container-xl <?= ($page === 'map' || $page === 'galaxy') ? 'container-fluid px-0' : '' ?>">


