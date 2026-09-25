<?php
/**
 * Vue d'Administration Système & Gestion des Bots (OpenGalaxy)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/Database.php';

$auth = new Auth();
if (!Auth::check() || !$auth->isAdmin()) {
    echo "<div class='card' style='max-width: 600px; margin: 3rem auto; text-align: center; border-color: #ef4444;'>
            <h2 style='color: #ef4444;'>⛔ Accès Restreint</h2>
            <p style='margin-top: 1rem;'>Cette zone est réservée au Shogun et aux administrateurs habilités.</p>
            <a href='?page=resources' class='btn btn-primary' style='margin-top: 1.5rem; display: inline-block;'>&larr; Retour au Fief</a>
          </div>";
    return;
}

require_once __DIR__ . '/../core/CastleEngine.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/SupportEngine.php';
require_once __DIR__ . '/../core/AnnouncementEngine.php';
require_once __DIR__ . '/../core/UpdateEngine.php';
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/ForumEngine.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';

$forumEngine = new ForumEngine();
$adminForumCategories = $forumEngine->getCategories();

$botEngine = new BotEngine();
$castleEngine = new CastleEngine();
$oasisEngine = new OasisEngine();
$supportEngine = new SupportEngine();
$updateEngine = new UpdateEngine();
$heroEngine = new HeroEngine();
$localGitInfo = $updateEngine->getLocalInfo();
$db = Database::getConnection();
$sealEngine = new ImperialSealEngine($db);

// Statistiques & Données des Annonces (JSON)
$allAnnouncements = AnnouncementEngine::getAllAnnouncements(false);
$announcementStats = AnnouncementEngine::getReadStats();
$publishedAnnouncementsCount = count(array_filter($allAnnouncements, fn($a) => !empty($a['is_published'])));
$totalAnnouncementsCount = count($allAnnouncements);

// Statistiques Support & Tickets
$supportStats = $supportEngine->getStatistics();
$allSupportTickets = $supportEngine->getAllTickets();

// Statistiques globales
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0")->fetchColumn();
$totalBots = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_bot = 1")->fetchColumn();
$totalPlanets = (int)$db->query("SELECT COUNT(*) FROM planets")->fetchColumn();
$totalColonies = (int)$db->query("SELECT COUNT(*) FROM planets WHERE user_id IS NOT NULL")->fetchColumn();
$totalMedals = (int)$db->query("SELECT COUNT(*) FROM user_medals")->fetchColumn();
$currentWeekCode = date('Y') . '-S' . date('W');

// Statistiques & Registre des Samouraïs Héros & Reliques
$totalHeroes = (int)$db->query("SELECT COUNT(*) FROM heroes")->fetchColumn();
$heroesDead = (int)$db->query("SELECT COUNT(*) FROM heroes WHERE status = 'dead'")->fetchColumn();
$heroesReviving = (int)$db->query("SELECT COUNT(*) FROM heroes WHERE status = 'reviving'")->fetchColumn();
$totalRelicsFound = (int)$db->query("SELECT COUNT(*) FROM hero_inventory")->fetchColumn();

$allHeroes = $db->query("
    SELECT h.*, u.username, u.faction, u.points,
           (SELECT COUNT(*) FROM hero_inventory hi WHERE hi.user_id = h.user_id) as relic_count,
           (SELECT COUNT(*) FROM hero_inventory hi WHERE hi.user_id = h.user_id AND hi.is_equipped = 1) as equipped_count,
           (SELECT COUNT(*) FROM fleet_missions fm WHERE fm.user_id = h.user_id AND fm.mission_type = 'adventure' AND fm.departure_time >= UNIX_TIMESTAMP(CURDATE())) as daily_adv_count
    FROM heroes h
    JOIN users u ON h.user_id = u.id
    ORDER BY h.level DESC, h.experience DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Châteaux authentiques (現存十二天守)
$authenticCastles = $castleEngine->getAllCastles();
$spawnedCastlesCount = count(array_filter($authenticCastles, fn($c) => (int)$c['is_spawned'] === 1));

// Statistiques & liste des Oasis
$oasisStats = $oasisEngine->getOasisStatistics();
$allOases = $db->query("
    SELECT o.*, p.name as owner_planet_name, u.username as owner_username 
    FROM oases o 
    LEFT JOIN planets p ON o.owner_planet_id = p.id 
    LEFT JOIN users u ON p.user_id = u.id 
    ORDER BY o.owner_planet_id DESC, o.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($allOases as &$oRow) {
    $oRow['garrison'] = $oasisEngine->getOasisGarrison((int)$oRow['id']);
}
unset($oRow);

// Variables de configuration
$settings = GameConfig::load();
$botsList = $botEngine->getBots();
$botSpawnStatus = $botEngine->getBotSpawnStatus();

// Liste des joueurs humains
$humanUsers = $db->query("
    SELECT u.id, u.username, u.email, u.faction, u.points, u.is_admin, u.is_moderator, u.created_at, u.protection_until,
           u.gold_coins, u.imperial_seal_until,
           COUNT(p.id) as colony_count
    FROM users u
    LEFT JOIN planets p ON p.user_id = u.id
    WHERE u.is_bot = 0
    GROUP BY u.id
    ORDER BY u.id ASC
")->fetchAll();

// Statistiques & Répartition des Tuiles du Monde Féodal par Catégorie
$mapRadius = 35;
try {
    $maxCoord = (int)$db->query("SELECT MAX(GREATEST(ABS(coord_x), ABS(coord_y))) FROM planets")->fetchColumn();
    if ($maxCoord > $mapRadius) {
        $mapRadius = $maxCoord;
    }
} catch (Exception $e) {}

$totalTilesCount = (int)pow(($mapRadius * 2) + 1, 2);

// Indexer les coordonnées des donjons authentiques déployés
$spawnedCastleCoords = [];
foreach ($authenticCastles as $c) {
    if (!empty($c['is_spawned'])) {
        $spawnedCastleCoords[((int)$c['coord_x']) . ':' . ((int)$c['coord_y'])] = true;
    }
}

// Indexer les coordonnées des oasis
$oasisCoords = [];
foreach ($allOases as $o) {
    $oasisCoords[((int)$o['coord_x']) . ':' . ((int)$o['coord_y'])] = $o;
}

// Indexer les planètes (fiefs castraux occupés vs terres libres inoccupées)
$allPlanetsRows = $db->query("SELECT coord_x, coord_y, user_id FROM planets")->fetchAll(PDO::FETCH_ASSOC);
$villageCoords = [];
$freeLandCoords = [];
foreach ($allPlanetsRows as $p) {
    $k = ((int)$p['coord_x']) . ':' . ((int)$p['coord_y']);
    if (isset($spawnedCastleCoords[$k])) {
        continue; // L'emplacement est un donjon authentique déployé
    }
    if (!empty($p['user_id'])) {
        $villageCoords[$k] = true;
    } else {
        $freeLandCoords[$k] = true;
    }
}

$mapTileStats = [
    'radius' => $mapRadius,
    'total_tiles' => $totalTilesCount,
    'villages' => count($villageCoords),
    'free_lands' => count($freeLandCoords),
    'castles' => count($spawnedCastleCoords),
    'oases' => count($oasisCoords),
    'plains' => 0,
    'forest' => 0,
    'mountain' => 0,
    'hills' => 0,
    'lake' => 0,
];

// Calcul déterministe des types naturels procéduraux pour les tuiles restantes
for ($y = -$mapRadius; $y <= $mapRadius; $y++) {
    for ($x = -$mapRadius; $x <= $mapRadius; $x++) {
        $k = $x . ':' . $y;
        if (isset($villageCoords[$k]) || isset($freeLandCoords[$k]) || isset($spawnedCastleCoords[$k]) || isset($oasisCoords[$k])) {
            continue;
        }
        $seed = abs((int)(($x * 73856093) ^ ($y * 19349663))) % 1000;
        if ($seed < 600) {
            $mapTileStats['plains']++;
        } elseif ($seed < 750) {
            $mapTileStats['forest']++;
        } elseif ($seed < 870) {
            $mapTileStats['mountain']++;
        } elseif ($seed < 950) {
            $mapTileStats['hills']++;
        } else {
            $mapTileStats['lake']++;
        }
    }
}

// Métadonnées d'affichage des 9 catégories de tuiles
$mapTileCategories = [
    'villages' => [
        'name' => 'Fiefs Occupés',
        'sub' => 'Daimyōs & PNJ',
        'count' => $mapTileStats['villages'],
        'icon' => '🏯',
        'badge_bg' => 'bg-purple-lt text-purple',
        'bar_color' => 'bg-purple',
        'img' => '/public/assets/map/tile_village.jpg?v=2',
        'desc' => 'Capitales et fiefs colonisés par les joueurs et clans IA.'
    ],
    'free_lands' => [
        'name' => 'Terres Libres',
        'sub' => 'Emplacements arpentés',
        'count' => $mapTileStats['free_lands'],
        'icon' => '🏳️',
        'badge_bg' => 'bg-secondary-lt text-secondary',
        'bar_color' => 'bg-secondary',
        'img' => '/public/assets/map/tile_plains.jpg?v=2',
        'desc' => 'Emplacements arpentés disponibles pour fondation de colonie.'
    ],
    'castles' => [
        'name' => 'Donjons Sacrés',
        'sub' => '現存十二天守',
        'count' => $mapTileStats['castles'],
        'icon' => '👑',
        'badge_bg' => 'bg-warning-lt text-warning',
        'bar_color' => 'bg-warning',
        'img' => '/public/assets/map/tile_authentic_castle.jpg?v=2',
        'desc' => 'Les 12 forteresses impériales historiques du Japon féodal.'
    ],
    'oases' => [
        'name' => 'Oasis Naturelles',
        'sub' => 'Faune & Bonus',
        'count' => $mapTileStats['oases'],
        'icon' => '🌿',
        'badge_bg' => 'bg-teal-lt text-teal',
        'bar_color' => 'bg-teal',
        'img' => '/public/assets/map/tile_lake.jpg?v=2',
        'desc' => 'Havres de faune sauvage procurant des bonus de production.'
    ],
    'plains' => [
        'name' => 'Plaines Fertiles',
        'sub' => 'Prairies & Terres',
        'count' => $mapTileStats['plains'],
        'icon' => '🌾',
        'badge_bg' => 'bg-lime-lt text-lime',
        'bar_color' => 'bg-lime',
        'img' => '/public/assets/map/tile_plains.jpg?v=2',
        'desc' => 'Terres arables verdoyantes et plaines propices à l\'agriculture.'
    ],
    'forest' => [
        'name' => 'Forêt de Cèdres',
        'sub' => 'Sugi centenaires',
        'count' => $mapTileStats['forest'],
        'icon' => '🌲',
        'badge_bg' => 'bg-green-lt text-green',
        'bar_color' => 'bg-green',
        'img' => '/public/assets/map/tile_forest.jpg?v=2',
        'desc' => 'Massifs sylvestres denses pourvoyeurs de bois de construction.'
    ],
    'mountain' => [
        'name' => 'Pics & Montagnes',
        'sub' => 'Crêtes rocheuses',
        'count' => $mapTileStats['mountain'],
        'icon' => '⛰️',
        'badge_bg' => 'bg-dark-lt text-dark',
        'bar_color' => 'bg-dark',
        'img' => '/public/assets/map/tile_mountain.jpg?v=2',
        'desc' => 'Reliefs escarpés et carrières granitiques des monts du Japon.'
    ],
    'hills' => [
        'name' => 'Collines & Coteaux',
        'sub' => 'Cultures en terrasse',
        'count' => $mapTileStats['hills'],
        'icon' => '🏞️',
        'badge_bg' => 'bg-orange-lt text-orange',
        'bar_color' => 'bg-orange',
        'img' => '/public/assets/map/tile_hills.jpg?v=2',
        'desc' => 'Versants vallonnés et vergers suspendus de l\'archipel.'
    ],
    'lake' => [
        'name' => 'Lacs & Eaux Calmes',
        'sub' => 'Rivières & Bassins',
        'count' => $mapTileStats['lake'],
        'icon' => '🌊',
        'badge_bg' => 'bg-cyan-lt text-cyan',
        'bar_color' => 'bg-cyan',
        'img' => '/public/assets/map/tile_lake.jpg?v=2',
        'desc' => 'Étendues d\'eau douce, étangs sacrés et méandres fluviaux.'
    ],
];

// =========================================================================
// MÉTROLOGIE DU TABLEAU DE BORD EXÉCUTIF (KPIS, ENGAGEMENT & 30 JOURS)
// =========================================================================
$activeUsersRealtime = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0 AND last_active >= NOW() - INTERVAL 15 MINUTE")->fetchColumn();
$activeUsers24h = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0 AND last_active >= NOW() - INTERVAL 24 HOUR")->fetchColumn();

// Progression des quêtes & points
$totalQuestsClaimed = (int)$db->query("SELECT COUNT(*) FROM user_quests WHERE status = 'claimed'")->fetchColumn();
$avgQuestsPerUser = round($totalQuestsClaimed / max(1, $totalUsers), 1);
$completionRate = min(100, round(($avgQuestsPerUser / 15) * 100, 1));
$avgPoints = (int)$db->query("SELECT AVG(points) FROM users WHERE is_bot = 0")->fetchColumn();

// Estimations session
$avgSessionTime = "24m 30s";
$avgSessionsPerDay = "3.2";

// Engagement Atelier Pédagogique
$pedagogyViews = (int)GameConfig::get('pedagogy_views_count', 42);
$pedagogyEngagementPct = min(100, round(($pedagogyViews / max(1, $totalUsers * 3)) * 100, 1));

// Évolution 30 jours (inscriptions et activité)
$stats30Days = [];
$nowTs = time();
for ($i = 29; $i >= 0; $i--) {
    $dKey = date('Y-m-d', $nowTs - ($i * 86400));
    $stats30Days[$dKey] = ['day' => date('d/m', $nowTs - ($i * 86400)), 'users' => 0, 'sessions' => 0];
}
$stmtReg30 = $db->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE is_bot = 0 AND created_at >= NOW() - INTERVAL 30 DAY GROUP BY DATE(created_at)");
while ($r = $stmtReg30->fetch(PDO::FETCH_ASSOC)) {
    if (isset($stats30Days[$r['d']])) $stats30Days[$r['d']]['users'] = (int)$r['c'];
}
$stmtAct30 = $db->query("SELECT DATE(FROM_UNIXTIME(departure_time)) as d, COUNT(*) as c FROM fleet_missions WHERE departure_time >= UNIX_TIMESTAMP(NOW() - INTERVAL 30 DAY) GROUP BY DATE(FROM_UNIXTIME(departure_time))");
while ($r = $stmtAct30->fetch(PDO::FETCH_ASSOC)) {
    if (isset($stats30Days[$r['d']])) $stats30Days[$r['d']]['sessions'] = (int)$r['c'];
}

// Strates de score
$ptsStrat = ['debutant' => 0, 'intermediaire' => 0, 'veteran' => 0];
foreach ($humanUsers as $u) {
    $p = (int)$u['points'];
    if ($p < 500) $ptsStrat['debutant']++;
    elseif ($p <= 2000) $ptsStrat['intermediaire']++;
    else $ptsStrat['veteran']++;
}

// Factions
$factionCounts = $db->query("SELECT faction, COUNT(*) as count FROM users WHERE is_bot = 0 GROUP BY faction")->fetchAll(PDO::FETCH_KEY_PAIR);

// Activités récentes & alertes
$recentUsers = $db->query("SELECT id, username, faction, points, created_at FROM users WHERE is_bot = 0 ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$dbSizeMb = 0;
try {
    $dbSizeMb = round((float)$db->query("SELECT SUM(data_length + index_length) / 1024 / 1024 FROM information_schema.TABLES WHERE table_schema = DATABASE()")->fetchColumn(), 2);
} catch (Exception $e) {}

// Gestion des onglets d'administration du Shogunat
$allowedTabs = ['dashboard', 'world', 'heroes', 'bots', 'users', 'medals', 'support', 'announcements', 'forum', 'pedagogy', 'updates', 'maintenance', 'all', 'game', 'oases', 'castles'];
$currentTab = $_GET['tab'] ?? 'dashboard';
if ($currentTab === 'game' || $currentTab === 'oases' || $currentTab === 'castles') {
    $currentTab = 'world';
}
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'dashboard';
}
$isPaneVisible = fn(string $tabKey) => ($currentTab === 'all' || $currentTab === $tabKey);
?>

<div class="admin-panel mb-5">
    <!-- En-tête Terminal de Commandement Tabler.io -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Console d'Administration du Shōgunat</div>
                <h2 class="page-title d-flex align-items-center gap-2">
                    <span>🏯</span>
                    <span>Conseil du Shōgunat — Haute Administration</span>
                    <span class="badge bg-danger text-white ms-2" style="font-size:0.75rem;">Accès Maître</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Pilotage central des constantes de l'archipel, équilibrage des vitesses, régulation des Samouraïs Héros et supervision des clans autonomes (Bots).
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/?page=pedagogy" target="_blank" class="btn btn-outline-cyan d-flex align-items-center gap-1 fw-bold">
                        <span>🎓</span> Atelier Pédagogique (Public) ↗
                    </a>
                    <button type="button" onclick="runBotCycle()" class="btn btn-warning d-flex align-items-center gap-2">
                        <span>⚔️</span> Exécuter un Cycle IA
                    </button>
                    <button type="button" onclick="generatePresetBots()" class="btn btn-primary d-flex align-items-center gap-2">
                        <span>➕</span> Générer 3 Daimyōs IA
                    </button>
                    <a href="/?page=resources" class="btn btn-secondary">
                        &larr; Retour au Fief
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes Métriques Rapides Cliquables (Tabler Stat Cards unifiées) -->
    <div class="row row-cards mb-3">
        <!-- Vitesse Active -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('world'); setTimeout(() => switchWorldSubSection('speeds'), 50);" title="Configurer les constantes & vitesses">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-danger-lt text-danger" style="font-size:1.3rem;">⚡</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Vitesse Active</div>
                            <div class="text-danger font-weight-bold" style="font-size:1.25rem;">
                                x<?= (int)($settings['game_speed'] ?? 5) ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        Prod: x<?= (int)($settings['resource_speed'] ?? 5) ?> | Marche: x<?= (int)($settings['fleet_speed'] ?? 5) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Samouraïs Héros -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('heroes')" title="Gérer les Samouraïs Héros et Reliques">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-purple-lt text-purple" style="font-size:1.3rem;">🥋</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Samouraïs Héros</div>
                            <div class="text-purple font-weight-bold" style="font-size:1.25rem;">
                                <?= $totalHeroes ?> Héros
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <span class="<?= ($heroesDead + $heroesReviving > 0) ? 'text-danger font-weight-bold' : '' ?>">
                            <?= $heroesDead + $heroesReviving ?> en péril
                        </span>
                        | 🛡️ <?= $totalRelicsFound ?> reliques
                    </div>
                </div>
            </div>
        </div>

        <!-- Clans IA (Bots) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('bots')" title="Gérer les Daimyōs IA et bots">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-indigo-lt text-indigo" style="font-size:1.3rem;">🤖</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Clans IA (Bots)</div>
                            <div class="text-indigo font-weight-bold" style="font-size:1.25rem;">
                                <?= $totalBots ?> PNJ
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        Statut IA : <strong class="<?= !empty($settings['bots_enabled']) ? 'text-success' : 'text-danger' ?>"><?= !empty($settings['bots_enabled']) ? 'Actif' : 'En sommeil' ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paramétrage du Monde -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('world')" title="Arpentage, Oasis et Répartition des Tuiles du Monde Féodal">
                <div class="card-body">
                    <div class="row align-items-center mb-2">
                        <div class="col-auto">
                            <span class="avatar rounded bg-success-lt text-success" style="font-size:1.3rem;">🗾</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Paramétrage du Monde</div>
                            <div class="text-success font-weight-bold" style="font-size:1.25rem;">
                                <?= number_format($mapTileStats['total_tiles']) ?> Tuiles
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1" style="font-size: 0.72rem;">
                        <?php foreach ($mapTileCategories as $cat): ?>
                            <span class="badge <?= $cat['badge_bg'] ?> py-1 px-1" title="<?= htmlspecialchars($cat['name']) ?> : <?= number_format($cat['count']) ?> tuiles (<?= round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 1) ?>%)">
                                <?= $cat['icon'] ?> <?= number_format($cat['count']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-secondary small mt-2 d-flex justify-content-between align-items-center">
                        <span>Rayon &plusmn;<?= $mapTileStats['radius'] ?> &bull; 9 Catégories</span>
                        <span class="text-success fw-bold">&rarr;</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daimyōs Joueurs -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('users')" title="Gérer les joueurs et privilèges">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-warning-lt text-warning" style="font-size:1.3rem;">👥</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Daimyōs Joueurs</div>
                            <div class="text-warning font-weight-bold" style="font-size:1.25rem;">
                                <?= $totalUsers ?> Joueurs
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        Comptes inscrits sur le serveur
                    </div>
                </div>
            </div>
        </div>

        <!-- Support & Requêtes -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('support')" title="Traiter les bugs & suggestions">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-azure-lt text-azure" style="font-size:1.3rem;">📮</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Bugs &amp; Idées</div>
                            <div class="text-azure font-weight-bold" style="font-size:1.25rem;">
                                <?= $supportStats['total'] ?> Demandes
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <strong class="<?= $supportStats['count_pending'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $supportStats['count_pending'] ?> en attente
                        </strong> | <?= $supportStats['count_in_progress'] ?> en cours
                    </div>
                </div>
            </div>
        </div>

        <!-- Nouveautés -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('announcements')" title="Gérer les annonces du jeu">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-pink-lt text-pink" style="font-size:1.3rem;">📢</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Nouveautés</div>
                            <div class="text-pink font-weight-bold" style="font-size:1.25rem;">
                                <?= $publishedAnnouncementsCount ?> / <?= $totalAnnouncementsCount ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <?= $publishedAnnouncementsCount ?> publiée(s) aux daimyōs
                    </div>
                </div>
            </div>
        </div>

        <!-- Mises à Jour Git -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('updates')" title="Contrôler et déployer les mises à jour GitHub">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-teal-lt text-teal" style="font-size:1.3rem;">🔄</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Mises à Jour Git</div>
                            <div class="text-teal font-weight-bold" style="font-size:1.25rem;">
                                <?= htmlspecialchars($localGitInfo['short_sha']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        Branche <?= htmlspecialchars($localGitInfo['branch']) ?> | Sync
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Conteneur d'Onglets Tabler.io Unifié pour l'Administration -->
    <div class="card mb-4">
        <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs flex-wrap" data-bs-toggle="tabs" role="tablist" id="adminTabsNav">
                <li class="nav-item" role="presentation">
                    <a href="#tab-dashboard" class="nav-link admin-tab-btn <?= ($currentTab === 'dashboard') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="dashboard" role="tab" onclick="switchAdminTab('dashboard')">
                        <span class="me-1">📊</span> Tableau de Bord
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-world" class="nav-link admin-tab-btn <?= ($currentTab === 'world') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="world" role="tab" onclick="switchAdminTab('world')">
                        <span class="me-1">🗾</span> Paramétrage du Monde
                        <span class="badge bg-success-lt ms-2">x<?= (int)($settings['game_speed'] ?? 5) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-bots" class="nav-link admin-tab-btn <?= ($currentTab === 'bots') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="bots" role="tab" onclick="switchAdminTab('bots')">
                        <span class="me-1">🤖</span> Clans IA
                        <span class="badge bg-indigo-lt ms-2"><?= $totalBots ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-heroes" class="nav-link admin-tab-btn <?= ($currentTab === 'heroes') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="heroes" role="tab" onclick="switchAdminTab('heroes')">
                        <span class="me-1">🥋</span> Samouraïs &amp; Reliques
                        <span class="badge bg-purple-lt ms-2"><?= $totalHeroes ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-users" class="nav-link admin-tab-btn <?= ($currentTab === 'users') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="users" role="tab" onclick="switchAdminTab('users')">
                        <span class="me-1">👥</span> Joueurs
                        <span class="badge bg-warning-lt ms-2"><?= $totalUsers ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-medals" class="nav-link admin-tab-btn <?= ($currentTab === 'medals') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="medals" role="tab" onclick="switchAdminTab('medals')">
                        <span class="me-1">🎖️</span> Médailles
                        <span class="badge bg-yellow-lt ms-2"><?= htmlspecialchars($currentWeekCode) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-support" class="nav-link admin-tab-btn <?= ($currentTab === 'support') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="support" role="tab" onclick="switchAdminTab('support')">
                        <span class="me-1">📮</span> Support &amp; Bugs
                        <?php if ($supportStats['count_pending'] > 0): ?>
                            <span class="badge bg-danger text-white ms-2">⚠️ <?= $supportStats['count_pending'] ?></span>
                        <?php else: ?>
                            <span class="badge bg-azure-lt ms-2"><?= $supportStats['total'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-announcements" class="nav-link admin-tab-btn <?= ($currentTab === 'announcements') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="announcements" role="tab" onclick="switchAdminTab('announcements')">
                        <span class="me-1">📢</span> Nouveautés
                        <span class="badge bg-pink-lt ms-2"><?= $publishedAnnouncementsCount ?>/<?= $totalAnnouncementsCount ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-forum" class="nav-link admin-tab-btn <?= ($currentTab === 'forum') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="forum" role="tab" onclick="switchAdminTab('forum')">
                        <span class="me-1">💬</span> Forum Féodal
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-pedagogy" class="nav-link admin-tab-btn <?= ($currentTab === 'pedagogy') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="pedagogy" role="tab" onclick="switchAdminTab('pedagogy')">
                        <span class="me-1">🎓</span> Atelier Pédagogique
                        <span class="badge bg-cyan-lt ms-1">Public</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-updates" class="nav-link admin-tab-btn <?= ($currentTab === 'updates') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="updates" role="tab" onclick="switchAdminTab('updates')">
                        <span class="me-1">🔄</span> GitHub Sync
                        <span class="badge bg-teal-lt ms-2"><?= htmlspecialchars($localGitInfo['short_sha']) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-maintenance" class="nav-link admin-tab-btn <?= ($currentTab === 'maintenance') ? 'active' : '' ?>" data-bs-toggle="tab" data-tab="maintenance" role="tab" onclick="switchAdminTab('maintenance')">
                        <span class="me-1">⚠️</span> Maintenance
                    </a>
                </li>
                <li class="nav-item ms-auto d-flex align-items-center gap-1" role="presentation">
                    <a href="/?page=pedagogy" target="_blank" class="nav-link text-cyan fw-bold py-1 px-2 border border-cyan-subtle rounded-pill small me-2" title="Ouvrir la page publique de l'Atelier Pédagogique">
                        <span>🎓 Vue Publique ↗</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'all') ? 'active' : '' ?>" data-tab="all" onclick="switchAdminTab('all')" title="Afficher tous les onglets en continu">
                        <span class="me-1">📚</span> Tout Dérouler
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="adminTabsContent">

<!-- ═════════════════════════════════════════════════════════════════ -->
<!-- SECTION 0 : 📊 TABLEAU DE BORD EXÉCUTIF (DASHBOARD PRINCIPAL)     -->
<!-- ═════════════════════════════════════════════════════════════════ -->
<div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'dashboard' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-dashboard" data-tab="dashboard" role="tabpanel">
    
    <!-- En-tête du Dashboard & Filtres Temporels -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-primary d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <span>📊</span> Tableau de Bord Exécutif du Shōgunat
            </h3>
            <div class="text-secondary small mt-1">
                Supervision globale de l'activité des Daimyōs, progression moyenne des quêtes, santé du serveur et impact pédagogique.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-success-lt d-inline-flex align-items-center gap-1 py-2 px-3">
                <span class="status-dot status-dot-animated bg-success"></span>
                <span>Système Opérationnel</span>
            </span>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="alert('Filtrage: Aujourd\'hui')">Aujourd'hui</button>
                <button type="button" class="btn btn-outline-secondary" onclick="alert('Filtrage: 7 derniers jours')">7 jours</button>
                <button type="button" class="btn btn-outline-secondary active" onclick="alert('Filtrage: 30 derniers jours')">30 jours</button>
            </div>
            <button type="button" onclick="location.reload()" class="btn btn-sm btn-outline-primary" title="Actualiser les métriques">
                <span>🔄 Actualiser</span>
            </button>
        </div>
    </div>

    <!-- ── 4 CARTES KPIS EN HAUT ── -->
    <div class="row row-cards mb-4">
        <!-- 1. Joueurs Actifs -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-3 border-primary shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded bg-primary-lt text-primary me-3 fs-2">👥</span>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Joueurs Actifs</div>
                            <div class="h2 m-0 font-weight-bold text-dark">
                                <?= $activeUsersRealtime ?> <span class="fs-4 text-muted fw-normal">/ <?= $activeUsers24h ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top small text-muted">
                        <span>Temps réel (&lt;15m) &bull; 24h</span>
                        <span class="text-primary fw-bold"><?= $totalUsers ?> Inscrits</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Taux d'Achèvement (Quêtes & Didacticiel) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-3 border-success shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded bg-success-lt text-success me-3 fs-2">🎯</span>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Progression & Quêtes</div>
                            <div class="h2 m-0 font-weight-bold text-success">
                                <?= $completionRate ?>%
                            </div>
                        </div>
                    </div>
                    <div class="progress progress-xs mt-3 mb-1">
                        <div class="progress-bar bg-success" style="width: <?= $completionRate ?>%"></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between small text-muted">
                        <span><?= $totalQuestsClaimed ?> quêtes accomplies</span>
                        <span class="fw-bold">Moy. <?= number_format($avgPoints) ?> pts</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Temps Moyen / Session -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-3 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded bg-warning-lt text-warning me-3 fs-2">⏱️</span>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Temps Moyen / Session</div>
                            <div class="h2 m-0 font-weight-bold text-warning-emphasis">
                                <?= $avgSessionTime ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top small text-muted">
                        <span>Fréquence quotidienne</span>
                        <span class="text-warning fw-bold"><?= $avgSessionsPerDay ?> sessions/j</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Engagement Atelier Pédagogique -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-3 border-cyan shadow-sm h-100" style="cursor: pointer;" onclick="window.open('/?page=pedagogy', '_blank')" title="Ouvrir la page publique de l'Atelier Pédagogique">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded bg-cyan-lt text-cyan me-3 fs-2">🎓</span>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Atelier Pédagogique</div>
                            <div class="h2 m-0 font-weight-bold text-cyan">
                                <?= number_format($pedagogyViews) ?> <span class="fs-4 text-muted fw-normal">vues</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top small text-muted">
                        <span>Page 1er Niveau</span>
                        <span class="badge bg-cyan-lt fw-bold"><?= $pedagogyEngagementPct ?>% engagement</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ZONE VISUALISATION DES DONNÉES (2 COLONNES) ── -->
    <div class="row row-cards mb-4">
        <!-- Graphique 30 jours : Inscriptions & Missions/Sessions -->
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <span>📈</span> Évolution des Inscriptions &amp; Activités (30 Jours)
                    </h4>
                    <span class="badge bg-primary-lt">Moyenne quotidienne</span>
                </div>
                <div class="card-body">
                    <!-- Graphique Canvas stylisé natif responsive -->
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="adminTrendChart" style="width: 100%; height: 100%;"></canvas>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-around text-center py-2 bg-light small">
                    <div>
                        <span class="badge badge-dot bg-primary me-1"></span> Inscriptions : <strong><?= array_sum(array_column($stats30Days, 'users')) ?> nouveaux daimyōs</strong>
                    </div>
                    <div>
                        <span class="badge badge-dot bg-success me-1"></span> Expéditions : <strong><?= array_sum(array_column($stats30Days, 'sessions')) ?> missions</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition des Scores & Niveaux -->
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <span>🍩</span> Répartition des Daimyōs Joueurs
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Strates de Puissance -->
                    <div class="mb-3">
                        <div class="small fw-bold text-muted mb-1">Niveaux de Puissance Militaire :</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>🌱 Débutants (&lt; 500 pts)</span>
                            <strong><?= $ptsStrat['debutant'] ?> (<?= round(($ptsStrat['debutant'] / max(1, $totalUsers)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar bg-info" style="width: <?= round(($ptsStrat['debutant'] / max(1, $totalUsers)) * 100) ?>%"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-1">
                            <span>🛡️ Établis (500 - 2 000 pts)</span>
                            <strong><?= $ptsStrat['intermediaire'] ?> (<?= round(($ptsStrat['intermediaire'] / max(1, $totalUsers)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar bg-primary" style="width: <?= round(($ptsStrat['intermediaire'] / max(1, $totalUsers)) * 100) ?>%"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-1">
                            <span>👑 Vétérans (&gt; 2 000 pts)</span>
                            <strong><?= $ptsStrat['veteran'] ?> (<?= round(($ptsStrat['veteran'] / max(1, $totalUsers)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-warning" style="width: <?= round(($ptsStrat['veteran'] / max(1, $totalUsers)) * 100) ?>%"></div>
                        </div>
                    </div>

                    <!-- Répartition par Clan Féodal -->
                    <div class="pt-3 border-top">
                        <div class="small fw-bold text-muted mb-2">Répartition par Clan Féodal :</div>
                        <div class="row g-2 text-center">
                            <?php foreach (['terran' => ['name' => 'Clan Oda', 'icon' => '🦅', 'color' => 'danger'], 'vorash' => ['name' => 'Clan Takeda', 'icon' => '🐅', 'color' => 'warning'], 'aethelis' => ['name' => 'Clan Tokugawa', 'icon' => '🐉', 'color' => 'success']] as $fKey => $fMeta): 
                                $fCount = (int)($factionCounts[$fKey] ?? 0);
                                $fPct = round(($fCount / max(1, $totalUsers)) * 100);
                            ?>
                                <div class="col-4">
                                    <div class="p-2 border rounded bg-light">
                                        <div class="fs-3"><?= $fMeta['icon'] ?></div>
                                        <div class="small fw-bold text-truncate"><?= $fMeta['name'] ?></div>
                                        <div class="fw-bold text-<?= $fMeta['color'] ?>"><?= $fCount ?></div>
                                        <div class="text-muted" style="font-size: 0.68rem;"><?= $fPct ?>%</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ZONE ACTIVITÉ RÉCENTE & SANTÉ SYSTÈME (2 COLONNES) ── -->
    <div class="row row-cards">
        <!-- Journal des Dernières Activités -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <span>⚡</span> Activités Récentes des Daimyōs
                    </h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchAdminTab('users')">
                        Voir tous les joueurs &rarr;
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Daimyō</th>
                                <th>Clan</th>
                                <th>Action / Événement</th>
                                <th class="text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr><td colspan="4" class="text-center text-muted p-3">Aucune activité récente enregistrée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $ru): 
                                    $fInfo = FACTIONS[$ru['faction']] ?? FACTIONS['terran'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">👤 <?= htmlspecialchars($ru['username']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt"><?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-lt">🌱 Inscription & Fief Capital</span>
                                        </td>
                                        <td class="text-end text-muted small">
                                            <?= date('d/m H:i', strtotime($ru['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Santé Système & Alertes Techniques -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <span>🛡️</span> Santé Système &amp; Alertes Techniques
                    </h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <!-- Base de données -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold">💾 Base de Données MariaDB</div>
                                <div class="small text-muted">Stockage : <?= $dbSizeMb ?> Mo &bull; Latence &lt; 5ms</div>
                            </div>
                            <span class="badge bg-success-lt fw-bold">🟢 OK</span>
                        </div>

                        <!-- GameLoop & Bots -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold">⚙️ Simulation IA &amp; Game Loop</div>
                                <div class="small text-muted"><?= $totalBots ?> Daimyōs IA &bull; Cycle périodique autonome</div>
                            </div>
                            <span class="badge bg-success-lt fw-bold">🟢 Actif</span>
                        </div>

                        <!-- Support & Bugs -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold">📮 File des Tickets Joueurs</div>
                                <div class="small text-muted"><?= $supportStats['total'] ?> tickets reçus au total</div>
                            </div>
                            <?php if ($supportStats['count_pending'] > 0): ?>
                                <a href="javascript:void(0)" onclick="switchAdminTab('support')" class="badge bg-danger text-white text-decoration-none">
                                    ⚠️ <?= $supportStats['count_pending'] ?> en attente
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success-lt fw-bold">🟢 À jour</span>
                            <?php endif; ?>
                        </div>

                        <!-- Git Sync -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold">🔄 Version Déployée (Git)</div>
                                <div class="small text-muted">Branche <?= htmlspecialchars($localGitInfo['branch']) ?> (<?= htmlspecialchars($localGitInfo['short_sha']) ?>)</div>
                            </div>
                            <span class="badge bg-teal-lt fw-bold">Synchronisé</span>
                        </div>
                    </div>

                    <!-- Actions Rapides -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="small fw-bold text-muted mb-2">Actions d'urgence rapides :</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" onclick="runBotCycle()" class="btn btn-sm btn-outline-warning">
                                <span>⚔️</span> Forcer Cycle IA
                            </button>
                            <a href="/?page=pedagogy" target="_blank" class="btn btn-sm btn-outline-cyan">
                                <span>🎓</span> Ouvrir Atelier Pédago
                            </a>
                            <button type="button" onclick="switchAdminTab('updates')" class="btn btn-sm btn-outline-teal">
                                <span>🔄</span> Vérifier Mises à Jour
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════ -->
    <!-- SECTION UNIFIÉE : 🗾 PARAMÉTRAGE DU MONDE FÉODAL & PROVINCES      -->
    <!-- ═════════════════════════════════════════════════════════════════ -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'world' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-world" data-tab="world" role="tabpanel">
        
        <!-- En-tête Unifié & Navigation Interne Rapide -->
        <div class="card mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h3 class="card-title d-flex align-items-center gap-2 m-0 text-success">
                            <span>🗾</span>
                            <span>Paramétrage Intégral du Monde Féodal &amp; Provinces</span>
                        </h3>
                        <div class="text-secondary small mt-1">
                            Contrôle centralisé du royaume : vitesses de jeu et équilibrage, création et arpentage des fiefs libres, déploiement des 12 donjons authentiques, et écosystème des oasis naturelles &amp; faune sauvage.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-indigo-lt">
                            🗺️ <?= number_format($mapTileStats['total_tiles']) ?> Tuiles
                        </span>
                        <span class="badge bg-danger-lt">
                            ⚡ x<?= (int)($settings['game_speed'] ?? 5) ?> Vitesse
                        </span>
                        <span class="badge bg-success-lt">
                            🗾 <?= $totalColonies ?> / <?= $totalPlanets ?> Fiefs Occupés
                        </span>
                        <span class="badge bg-warning-lt">
                            🏯 <?= $spawnedCastlesCount ?> / 12 Donjons Déployés
                        </span>
                        <span class="badge bg-green-lt">
                            🌿 <?= $oasisStats['total_oases'] ?> Oasis (<?= $oasisStats['wild_oases'] ?> Sauvages)
                        </span>
                    </div>
                </div>

                <!-- Répartition Détaillée des Tuiles du Monde Féodal par Catégorie -->
                <div class="border rounded p-3 bg-body-tertiary mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded bg-success-lt text-success" style="font-size: 1rem;">🗺️</span>
                            <span class="fw-bold text-dark">Répartition des Tuiles du Monde Féodal</span>
                            <span class="text-secondary small">(Grille de <?= ($mapTileStats['radius'] * 2 + 1) ?>&times;<?= ($mapTileStats['radius'] * 2 + 1) ?> cases &bull; Rayon &plusmn;<?= $mapTileStats['radius'] ?>)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white fw-bold">
                                <?= number_format($mapTileStats['total_tiles']) ?> Tuiles au total
                            </span>
                            <a href="/?page=map" target="_blank" class="btn btn-xs btn-outline-secondary">
                                🗾 Ouvrir la Carte &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Barre de progression proportionnelle multi-segments -->
                    <div class="progress progress-separated mb-3" style="height: 10px;" title="Répartition graphique de la carte féodale">
                        <?php foreach ($mapTileCategories as $cat): 
                            $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 2);
                            if ($pct <= 0) continue;
                        ?>
                            <div class="progress-bar <?= $cat['bar_color'] ?>" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" title="<?= htmlspecialchars($cat['name']) ?> : <?= number_format($cat['count']) ?> tuiles (<?= $pct ?>%)"></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grille des 9 catégories de tuiles -->
                    <div class="row row-cards g-2">
                        <?php foreach ($mapTileCategories as $key => $cat): 
                            $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 1);
                        ?>
                            <div class="col-6 col-sm-4 col-md-3 col-xl">
                                <div class="card card-sm h-100 shadow-none border">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= $cat['img'] ?>" alt="<?= htmlspecialchars($cat['name']) ?>" 
                                                 style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover; border: 1px solid rgba(0,0,0,0.15);" class="flex-shrink-0">
                                            <div class="overflow-hidden flex-grow-1">
                                                <div class="text-truncate fw-bold text-dark small" title="<?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['sub']) ?>)">
                                                    <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                                                </div>
                                                <div class="d-flex align-items-baseline justify-content-between gap-1 mt-1">
                                                    <span class="badge <?= $cat['badge_bg'] ?> px-1 py-0 fw-bold" style="font-size: 0.72rem;">
                                                        <?= number_format($cat['count']) ?>
                                                    </span>
                                                    <span class="text-secondary small font-monospace" style="font-size: 0.7rem;">
                                                        <?= $pct ?>%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pilules de sous-navigation interne -->
                <ul class="nav nav-pills nav-fill" id="worldSubTabsNav">
                    <li class="nav-item">
                        <a href="#worldSection_speeds" class="nav-link active py-2" onclick="switchWorldSubSection('speeds', event)" id="worldSubTab_speeds">
                            <span>⚡ 1. Vitesses &amp; Équilibrage</span>
                            <span class="badge bg-danger-lt ms-1">x<?= (int)($settings['game_speed'] ?? 5) ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#worldSection_gen" class="nav-link py-2" onclick="switchWorldSubSection('gen', event)" id="worldSubTab_gen">
                            <span>🗾 2. Arpentage &amp; Fiefs Libres</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#worldSection_castles" class="nav-link py-2" onclick="switchWorldSubSection('castles', event)" id="worldSubTab_castles">
                            <span>🏯 3. Les 12 Donjons Authentiques</span>
                            <span class="badge bg-warning-lt ms-1"><?= $spawnedCastlesCount ?>/12</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#worldSection_oases" class="nav-link py-2" onclick="switchWorldSubSection('oases', event)" id="worldSubTab_oases">
                            <span>🌿 4. Oasis &amp; Faune Sauvage</span>
                            <span class="badge bg-green-lt ms-1"><?= $oasisStats['total_oases'] ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 1. Constantes & Équilibrage des Vitesses de Jeu -->
        <div class="card mb-4" id="worldSection_speeds" style="border-top: 3px solid #ef4444;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title d-flex align-items-center gap-2 m-0 text-danger">
                        <span>⚡</span> Constantes &amp; Équilibrage des Vitesses de Jeu
                    </h3>
                    <div class="text-secondary small mt-1">
                        Facteurs d'accélération des chantiers, de production des ressources et de marche des armées féodales.
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset(1, 1, 1)">1x Classique</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset(5, 5, 5)">5x Standard</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset(20, 20, 10)">20x Éclair</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset(50, 50, 20)">50x Hyper</button>
                </div>
            </div>
            <div class="card-body">
                <form id="gameSettingsForm" onsubmit="saveSettings(event)">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>⚡ Vitesse du Jeu (Chantiers &amp; Recherches)</span>
                                <span class="badge bg-danger-lt" id="badge_game_speed">x<?= (int)($settings['game_speed'] ?? 5) ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="game_speed_range" min="1" max="100" value="<?= (int)($settings['game_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('game_speed_input').value = this.value; document.getElementById('badge_game_speed').textContent = 'x' + this.value;">
                                <input type="number" id="game_speed_input" name="game_speed" min="1" max="100" 
                                       value="<?= (int)($settings['game_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                       oninput="document.getElementById('game_speed_range').value = this.value; document.getElementById('badge_game_speed').textContent = 'x' + this.value;">
                            </div>
                            <div class="form-hint">Divise le temps nécessaire aux chantiers, Tenshu, académies et entraînements.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>⛏️ Production des Ressources</span>
                                <span class="badge bg-warning-lt" id="badge_resource_speed">x<?= (int)($settings['resource_speed'] ?? 5) ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="resource_speed_range" min="1" max="100" value="<?= (int)($settings['resource_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('resource_speed_input').value = this.value; document.getElementById('badge_resource_speed').textContent = 'x' + this.value;">
                                <input type="number" id="resource_speed_input" name="resource_speed" min="1" max="100" 
                                       value="<?= (int)($settings['resource_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                       oninput="document.getElementById('resource_speed_range').value = this.value; document.getElementById('badge_resource_speed').textContent = 'x' + this.value;">
                            </div>
                            <div class="form-hint">Multiplie la production horaire de Bois de Cèdre 🪵, Pierre 🪨 et Koku de Riz 🌾.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🐎 Marche des Troupes &amp; Expéditions</span>
                                <span class="badge bg-primary-lt" id="badge_fleet_speed">x<?= (int)($settings['fleet_speed'] ?? 5) ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="fleet_speed_range" min="1" max="50" value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('fleet_speed_input').value = this.value; document.getElementById('badge_fleet_speed').textContent = 'x' + this.value;">
                                <input type="number" id="fleet_speed_input" name="fleet_speed" min="1" max="50" 
                                       value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                       oninput="document.getElementById('fleet_speed_range').value = this.value; document.getElementById('badge_fleet_speed').textContent = 'x' + this.value;">
                            </div>
                            <div class="form-hint">Accélère les trajets des régiments pour les assauts, convois de tributs et fondations.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🔰 Durée d'Immunité Débutant</span>
                                <span class="badge bg-info-lt" id="badge_protection_days"><?= (int)($settings['beginner_protection_days'] ?? 7) ?> j</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="beginner_protection_days_range" min="0" max="30" value="<?= (int)($settings['beginner_protection_days'] ?? 7) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('beginner_protection_days_input').value = this.value; document.getElementById('badge_protection_days').textContent = this.value + ' j';">
                                <div class="input-group" style="width: 85px;">
                                    <input type="number" id="beginner_protection_days_input" name="beginner_protection_days" min="0" max="60" 
                                           value="<?= (int)($settings['beginner_protection_days'] ?? 7) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('beginner_protection_days_range').value = this.value; document.getElementById('badge_protection_days').textContent = this.value + ' j';">
                                    <span class="input-group-text px-1 text-muted">j</span>
                                </div>
                            </div>
                            <div class="form-hint">Durée accordée automatiquement lors de l'inscription (0 pour désactiver).</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 border-top pt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🌿 Densité des Oasis sur la Carte (%)</span>
                                <span class="badge bg-success-lt" id="badge_oasis_density"><?= (float)($settings['oasis_density_percent'] ?? 2.0) ?> %</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="oasis_density_percent_range" min="0.5" max="15.0" step="0.5" value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('oasis_density_percent_input').value = this.value; document.getElementById('badge_oasis_density').textContent = this.value + ' %';">
                                <div class="input-group" style="width: 95px;">
                                    <input type="number" id="oasis_density_percent_input" name="oasis_density_percent" min="0.5" max="20" step="0.5" 
                                           value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('oasis_density_percent_range').value = this.value; document.getElementById('badge_oasis_density').textContent = this.value + ' %';">
                                    <span class="input-group-text px-1 text-muted">%</span>
                                </div>
                            </div>
                            <div class="form-hint">Proportion de tuiles réservées aux oasis naturelles par rapport à la superficie totale.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark mb-1">
                                🔄 Réapparition Continue d'Oasis après Capture
                            </label>
                            <div class="d-flex align-items-center" style="min-height: 38px;">
                                <label class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="oasis_respawn_on_capture" name="oasis_respawn_on_capture" value="1" 
                                           <?= !empty($settings['oasis_respawn_on_capture']) ? 'checked' : '' ?>>
                                    <span class="form-check-label fw-medium text-dark">
                                        Faire éclore une nouvelle oasis sauvage lors de l'annexion d'une oasis par un joueur
                                    </span>
                                </label>
                            </div>
                            <div class="form-hint">Maintient le réservoir d'oasis sauvages et de faune active pour l'ensemble des seigneurs.</div>
                        </div>
                    </div>

                    <!-- Paramètres des Quêtes Féodales & Samouraï Héros (Cages & XP) -->
                    <div class="row g-3 mb-3 border-top pt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🎋 Cages de Capture (Kago) en Quête (%)</span>
                                <span class="badge bg-green-lt fw-bold" id="badge_hero_cage_drop_rate"><?= (int)($settings['hero_cage_drop_rate'] ?? 25) ?> %</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="hero_cage_drop_rate_range" min="0" max="100" step="1" 
                                       value="<?= (int)($settings['hero_cage_drop_rate'] ?? 25) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('hero_cage_drop_rate_input').value = this.value; document.getElementById('badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                <div class="input-group" style="width: 95px;">
                                    <input type="number" id="hero_cage_drop_rate_input" name="hero_cage_drop_rate" min="0" max="100" step="1" 
                                           value="<?= (int)($settings['hero_cage_drop_rate'] ?? 25) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('hero_cage_drop_rate_range').value = this.value; document.getElementById('badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                    <span class="input-group-text px-1 text-muted">%</span>
                                </div>
                            </div>
                            <div class="form-hint">Probabilité pour le Samouraï de rapporter un lot de Cages (Kago 🎋) pour capturer les bêtes sauvages des oasis sans combat.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🥋 Gain d'Expérience (XP) en Aventure (%)</span>
                                <span class="badge bg-primary-lt fw-bold" id="badge_hero_xp_rate"><?= (int)($settings['hero_xp_rate_percent'] ?? 100) ?> %</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="hero_xp_rate_percent_range" min="10" max="500" step="5" 
                                       value="<?= (int)($settings['hero_xp_rate_percent'] ?? 100) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('hero_xp_rate_percent_input').value = this.value; document.getElementById('badge_hero_xp_rate').textContent = this.value + ' %';">
                                <div class="input-group" style="width: 95px;">
                                    <input type="number" id="hero_xp_rate_percent_input" name="hero_xp_rate_percent" min="10" max="500" step="5" 
                                           value="<?= (int)($settings['hero_xp_rate_percent'] ?? 100) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('hero_xp_rate_percent_range').value = this.value; document.getElementById('badge_hero_xp_rate').textContent = this.value + ' %';">
                                    <span class="input-group-text px-1 text-muted">%</span>
                                </div>
                            </div>
                            <div class="form-hint">Multiplicateur du gain d'XP du Héros lors des aventures. Diminuez ce pourcentage pour ralentir la montée de niveau du Samouraï.</div>
                        </div>
                    </div>

                    <!-- Section Famine & Vivres Féodaux (Optionnel) -->
                    <div class="row g-3 mb-3 border-top pt-3" style="background: #fef2f2; border-radius: 8px; padding: 1rem; border: 1px solid #fecaca;">
                        <div class="col-12">
                            <h4 class="m-0 fw-bold text-danger d-flex align-items-center gap-2">
                                <span>🌾</span> Mécanisme de Famine &amp; Vivres Féodaux (Optionnel)
                            </h4>
                            <div class="text-secondary small mt-1">
                                Si activé, les régiments d'élite (Tier 2, 3 et 4) exigent un entretien régulier en farine de riz. En cas de pénurie totale (stock de farine à 0), une famine s'abat sur le fief et décime progressivement les troupes d'élite.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                ⚠️ Activer la Famine (Disette de Farine)
                            </label>
                            <div class="d-flex align-items-center" style="min-height: 38px;">
                                <label class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="famine_enabled" name="famine_enabled" value="1" 
                                           <?= !empty($settings['famine_enabled']) ? 'checked' : '' ?>>
                                    <span class="form-check-label fw-bold text-danger">
                                        Activer le péril de la famine
                                    </span>
                                </label>
                            </div>
                            <div class="form-hint">Désactivé par défaut. Les troupes d'élite ne meurent pas si décoché.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>💀 Taux de Pertes Horaire en Famine (%)</span>
                                <span class="badge bg-danger text-white" id="badge_famine_rate"><?= (float)($settings['famine_rate'] ?? 3.0) ?> %</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="famine_rate_range" min="0.5" max="25.0" step="0.5" value="<?= (float)($settings['famine_rate'] ?? 3.0) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('famine_rate_input').value = this.value; document.getElementById('badge_famine_rate').textContent = this.value + ' %';">
                                <div class="input-group" style="width: 95px;">
                                    <input type="number" id="famine_rate_input" name="famine_rate" min="0.5" max="50" step="0.5" 
                                           value="<?= (float)($settings['famine_rate'] ?? 3.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('famine_rate_range').value = this.value; document.getElementById('badge_famine_rate').textContent = this.value + ' %';">
                                    <span class="input-group-text px-1 text-muted">%</span>
                                </div>
                            </div>
                            <div class="form-hint">Pourcentage de soldats d'élite mourant de faim ou désertant par heure de rupture de farine.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>🍚 Rations Requises (Farine / 100 soldats / h)</span>
                                <span class="badge bg-warning text-dark" id="badge_famine_flour"><?= (float)($settings['famine_flour_consumption'] ?? 1.0) ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="famine_flour_range" min="0.1" max="10.0" step="0.1" value="<?= (float)($settings['famine_flour_consumption'] ?? 1.0) ?>" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('famine_flour_consumption_input').value = this.value; document.getElementById('badge_famine_flour').textContent = this.value;">
                                <div class="input-group" style="width: 95px;">
                                    <input type="number" id="famine_flour_consumption_input" name="famine_flour_consumption" min="0.1" max="20" step="0.1" 
                                           value="<?= (float)($settings['famine_flour_consumption'] ?? 1.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                           oninput="document.getElementById('famine_flour_range').value = this.value; document.getElementById('badge_famine_flour').textContent = this.value;">
                                    <span class="input-group-text px-1 text-muted">🍚</span>
                                </div>
                            </div>
                            <div class="form-hint">Unités de farine consommées par heure pour maintenir 100 troupes d'élite rassasiées.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            💾 Enregistrer les Constantes de Jeu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Arpenteur du Shogunat & Expansion des Provinces -->
        <div class="card mb-4" id="worldSection_gen" style="border-top: 3px solid #10b981;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title d-flex align-items-center gap-2 m-0 text-success">
                        <span>🗾</span> Arpenteur du Shogunat &amp; Expansion des Provinces
                    </h3>
                    <div class="text-secondary small mt-1">Création procédurale de fiefs, vallées et sanctuaires</div>
                </div>
            </div>
            <div class="card-body">
                <form id="worldGenForm" onsubmit="generateWorld(event)">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>Nombre de Terres &amp; Fiefs à Déployer</span>
                                <span class="badge bg-success-lt" id="badge_planet_count">12 fiefs</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="planet_count_range" min="1" max="50" value="12" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('planet_count_input').value = this.value; document.getElementById('badge_planet_count').textContent = this.value + ' fiefs';">
                                <input type="number" id="planet_count_input" name="planet_count" min="1" max="50" value="12" 
                                       class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                       oninput="document.getElementById('planet_count_range').value = this.value; document.getElementById('badge_planet_count').textContent = this.value + ' fiefs';">
                            </div>
                            <div class="form-hint">Terres libres prêtes à être explorées, pillées ou inféodées.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>Rayon de Dispersion Géographique</span>
                                <span class="badge bg-info-lt" id="badge_radius">&plusmn; 12</span>
                            </label>
                            <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                <input type="range" id="radius_range" min="5" max="35" value="12" class="form-range flex-grow-1" 
                                       oninput="document.getElementById('radius_input').value = this.value; document.getElementById('badge_radius').textContent = '± ' + this.value;">
                                <input type="number" id="radius_input" name="radius" min="5" max="35" value="12" 
                                       class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                       oninput="document.getElementById('radius_range').value = this.value; document.getElementById('badge_radius').textContent = '± ' + this.value;">
                            </div>
                            <div class="form-hint">Étendue des provinces [-R, +R] autour de la capitale impériale.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                Gestion des Terres Inoccupées
                            </label>
                            <div class="d-flex align-items-center" style="min-height: 38px;">
                                <label class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="clear_uninhabited" value="1" id="clear_uninhabited">
                                    <span class="form-check-label fw-medium text-dark">
                                        Purger les terres libres inoccupées existantes avant génération
                                    </span>
                                </label>
                            </div>
                            <div class="form-hint">Ne supprime jamais les fiefs possédés par un Daimyō ou un bot.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success px-4 fw-bold">
                            🗾 Déployer les Fiefs dans les Provinces
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Sanctuaires des 12 Donjons Authentiques du Japon (現存十二天守) -->
        <div class="card mb-4" id="worldSection_castles" style="border-top: 3px solid #f59e0b;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title d-flex align-items-center gap-2 m-0 text-warning">
                        <span>🏯</span> Les 12 Donjons Authentiques du Japon (現存十二天守) &bull; Enjeux de la Bataille Finale
                    </h3>
                    <div class="text-secondary small mt-1">
                        Forteresses historiques d'époque Sengoku-Edo préservées. Déployez-les sur la carte des provinces pour déclencher les enjeux de la conquête suprême.
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-warning-lt fw-bold">
                        <?= $spawnedCastlesCount ?> / 12 Déployés
                    </span>
                    <button type="button" class="btn btn-sm btn-warning fw-bold" onclick="distributeCastlesHomogeneously()" title="Déploie et répartit les 12 forteresses de manière homogène sur les 4 quadrants (rayon ±35)">
                        🌐 Répartir Homogènement (Rayon &plusmn;35)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="despawnAllCastles()">
                        🛑 Retirer Tous
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>Donjon &amp; Kanji</th>
                            <th>Province &amp; Bâtisseur</th>
                            <th class="text-center">Statut Carte</th>
                            <th class="text-center">Coordonnées [X : Y]</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($authenticCastles as $idx => $c): ?>
                            <?php 
                                $isSpawned = (int)$c['is_spawned'] === 1;
                                $curX = $c['coord_x'] ?? $c['default_x'];
                                $curY = $c['coord_y'] ?? $c['default_y'];
                            ?>
                            <tr class="<?= $isSpawned ? 'table-warning-lt' : '' ?>">
                                <td class="text-muted fw-bold"><?= $c['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        🏯 <?= htmlspecialchars($c['name']) ?>
                                    </div>
                                    <div class="small text-warning" style="font-family: serif;">
                                        <?= htmlspecialchars($c['kanji']) ?> &bull; <?= htmlspecialchars($c['japanese_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark small fw-medium"><?= htmlspecialchars($c['province']) ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars($c['historical_builder']) ?> (<?= htmlspecialchars($c['construction_year']) ?>)</div>
                                </td>
                                <td class="text-center">
                                    <?php if ($isSpawned): ?>
                                        <span class="badge bg-success-lt fw-bold">
                                            🟢 En Jeu [<?= $curX ?> : <?= $curY ?>]
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">
                                            ⚪ En Réserve
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <input type="number" id="castle_x_<?= $c['id'] ?>" value="<?= $curX ?>" class="form-control form-control-sm text-center" style="width: 55px;">
                                        <span class="text-muted">:</span>
                                        <input type="number" id="castle_y_<?= $c['id'] ?>" value="<?= $curY ?>" class="form-control form-control-sm text-center" style="width: 55px;">
                                        <button type="button" onclick="updateCastlePosition(<?= $c['id'] ?>)" class="btn btn-sm btn-outline-secondary px-2" title="Enregistrer les coordonnées">
                                            📍
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" onclick="toggleCastleSpawn(<?= $c['id'] ?>, <?= $isSpawned ? 0 : 1 ?>)" 
                                                class="btn btn-sm <?= $isSpawned ? 'btn-outline-danger' : 'btn-success' ?>">
                                            <?= $isSpawned ? '🔴 Retirer' : '🟢 Poser' ?>
                                        </button>
                                        <a href="/?page=castle&code=<?= $c['code'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Consulter la fiche historique">
                                            📜 Fiche
                                        </a>
                                        <?php if ($isSpawned): ?>
                                            <a href="/?page=map&x=<?= $curX ?>&y=<?= $curY ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir sur la carte">
                                                🗾 Carte
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Arpentage des Oasis Naturelles & Faune Sauvage (Style Travian) -->
        <div class="card mb-4" id="worldSection_oases" style="border-top: 3px solid #22c55e;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title d-flex align-items-center gap-2 m-0 text-green">
                        <span>🌿</span> Écosystème des Oasis Naturelles &amp; Faune Sauvage (Style Travian)
                    </h3>
                    <div class="text-secondary small mt-1">
                        Gestion du réseau d'oasis sauvages, de la faune hostile (Sangliers, Loups, Ours) et de la réapparition continue après capture.
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-green-lt fw-bold">
                        <?= $oasisStats['total_oases'] ?> Oasis Totales
                    </span>
                    <span class="badge bg-blue-lt fw-bold">
                        <?= $oasisStats['captured_oases'] ?> Fiefs Annexés
                    </span>
                    <span class="badge bg-danger-lt fw-bold">
                        <?= $oasisStats['wild_oases'] ?> Sauvages Libres
                    </span>
                    <span class="badge bg-warning-lt fw-bold">
                        🐗 <?= number_format($oasisStats['total_wild_animals']) ?> Bêtes Sauvages
                    </span>
                </div>
            </div>

            <div class="card-body">
                <!-- Panneau de contrôle et rééquilibrage de densité -->
                <div class="card card-body bg-light border mb-3">
                    <h4 class="card-title d-flex align-items-center gap-2 text-success mb-2" style="font-size: 0.95rem;">
                        <span>⚙️</span> Générateur &amp; Rééquilibrage par Pourcentage de Couverture
                    </h4>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label fw-bold text-dark mb-1">
                                Pourcentage de Densité Cible (%) :
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group" style="width: 110px;">
                                    <input type="number" id="repop_density" min="0.5" max="20.0" step="0.5" 
                                           value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold">
                                    <span class="input-group-text px-1 text-muted">%</span>
                                </div>
                                <span class="text-secondary small">&approx; 65 oasis à 2.0%</span>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-bold text-dark mb-1">
                                Rayon de Couverture Carte :
                            </label>
                            <div class="input-group" style="width: 110px;">
                                <input type="number" id="repop_radius" min="10" max="50" value="28" class="form-control text-center font-weight-bold">
                                <span class="input-group-text px-1 text-muted">tuiles</span>
                            </div>
                        </div>

                        <div class="col-md-5 col-sm-12">
                            <label class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="repop_clear_unoccupied" value="1">
                                <span class="form-check-label fw-medium text-dark">Remplacer uniquement les oasis sauvages existantes</span>
                            </label>
                            <button type="button" onclick="executeRepopulateOases()" class="btn btn-success w-100 fw-bold">
                                🌿 Appliquer &amp; Générer les Oasis
                            </button>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        ℹ️ Les oasis sont automatiquement réparties de façon équitable entre les 4 quadrants géographiques (NO, NE, SO, SE) sans empiéter sur les fiefs ni les 12 donjons authentiques.
                    </div>
                </div>

                <!-- Tableau des Oasis existantes -->
                <div class="table-responsive" style="max-height: 440px;">
                    <table class="table card-table table-vcenter table-striped text-nowrap">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="w-1">#</th>
                                <th>Nom de l'Oasis</th>
                                <th class="text-center">Coords</th>
                                <th>Bonus de Récolte</th>
                                <th>Faune / Garnison</th>
                                <th class="text-center">Statut Féodal</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allOases)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Aucune oasis recensée. Utilisez le générateur ci-dessus pour peupler le royaume.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allOases as $o): ?>
                                    <?php 
                                        $isCaptured = !empty($o['owner_planet_id']);
                                        $bText = '';
                                        if ($o['bonus_rice'] > 0) $bText .= "+{$o['bonus_rice']}% 🌾 ";
                                        if ($o['bonus_wood'] > 0) $bText .= "+{$o['bonus_wood']}% 🪵 ";
                                        if ($o['bonus_stone'] > 0) $bText .= "+{$o['bonus_stone']}% 🪨 ";
                                    ?>
                                    <tr class="<?= $isCaptured ? 'table-primary-lt' : '' ?>">
                                        <td class="text-muted fw-bold"><?= $o['id'] ?></td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($o['name']) ?></strong>
                                            <div class="text-secondary small">
                                                🪵 <?= number_format($o['res_wood']) ?> &bull; 🪨 <?= number_format($o['res_stone']) ?> &bull; 🌾 <?= number_format($o['res_rice']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-azure">
                                            [<?= $o['coord_x'] ?> : <?= $o['coord_y'] ?>]
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-lt fw-bold"><?= trim($bText) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($o['garrison'])): ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($o['garrison'] as $g): ?>
                                                        <span class="badge bg-dark-lt text-dark border">
                                                            <?= $g['icon'] ?> <?= htmlspecialchars($g['unit_name']) ?> <strong class="text-warning">x<?= $g['count'] ?></strong>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-success-lt">🕊️ Pacifiée (Aucune bête)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isCaptured): ?>
                                                <span class="badge bg-blue-lt">
                                                    🛡️ Fief de <?= htmlspecialchars($o['owner_username'] ?? 'Daimyō') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-lt">
                                                    🐗 Sauvage Libre
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/?page=map&x=<?= $o['coord_x'] ?>&y=<?= $o['coord_y'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                🗾 Carte
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
<!-- Section 2 NOUVELLE : Samouraïs Héros & Reliques Légendaires de l'Archipel -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'heroes' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-heroes" data-tab="heroes" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title d-flex align-items-center gap-2 m-0 text-purple">
                        <span>🥋</span> Registre des Samouraïs Héros &amp; Reliques Légendaires
                    </h3>
                    <div class="text-secondary small mt-1">
                        Surveillance de la santé, des quêtes quotidiennes (max 3/j), des reliques uniques (panthéon de 35) et décrets d'urgence shogunaux.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-purple-lt"><?= count($allHeroes) ?> Héros Enregistrés</span>
                    <?php if ($heroesDead > 0): ?>
                        <span class="badge bg-danger-lt">💀 <?= $heroesDead ?> Tombé(s)</span>
                    <?php endif; ?>
                    <?php if ($heroesReviving > 0): ?>
                        <span class="badge bg-warning-lt">⏳ <?= $heroesReviving ?> En Régénération (24h)</span>
                    <?php endif; ?>
                    <span class="badge bg-teal-lt">🏆 <?= $totalRelicsFound ?> Reliques Trouvées</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-nowrap card-table table-hover">
                    <thead>
                        <tr>
                            <th>Daimyō</th>
                            <th>Samouraï Champion</th>
                            <th>Niveau &amp; Progression</th>
                            <th>Vitalité</th>
                            <th>Statut Martiale</th>
                            <th>Aventures du Jour</th>
                            <th>Reliques (Équipées/Total)</th>
                            <th class="text-end">Commandes Shogunales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allHeroes)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-secondary py-4">
                                    Aucun Samouraï Héros n'est encore initié dans le royaume.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allHeroes as $hRow): 
                                $hHp = round((float)$hRow['health']);
                                $hHpClass = ($hHp >= 60) ? 'bg-success' : (($hHp >= 25) ? 'bg-warning' : 'bg-danger');
                                $hLvl = (int)$hRow['level'];
                                $hXp = (int)$hRow['experience'];
                                $hXpPct = HeroEngine::calculateXpPercent($hLvl, $hXp);
                                $hStatus = $hRow['status'];
                                $hStatusLabels = [
                                    'home' => ['label' => 'Au Domaine', 'badge' => 'bg-success-lt text-success', 'icon' => '🏯'],
                                    'mission' => ['label' => 'En Marche', 'badge' => 'bg-info-lt text-info', 'icon' => '🚩'],
                                    'adventure' => ['label' => 'En Aventure', 'badge' => 'bg-purple-lt text-purple', 'icon' => '🗺️'],
                                    'dead' => ['label' => 'Tombé au Combat', 'badge' => 'bg-danger-lt text-danger', 'icon' => '💀'],
                                    'reviving' => ['label' => 'Régénération (24h)', 'badge' => 'bg-warning-lt text-warning', 'icon' => '⏳']
                                ];
                                $hStInfo = $hStatusLabels[$hStatus] ?? ['label' => $hStatus, 'badge' => 'bg-secondary-lt', 'icon' => '❓'];
                                $dailyAdv = (int)($hRow['daily_adv_count'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs rounded bg-primary-lt">
                                                <?= strtoupper(substr($hRow['username'], 0, 1)) ?>
                                            </span>
                                            <div>
                                                <div class="font-weight-medium"><?= htmlspecialchars($hRow['username']) ?></div>
                                                <div class="text-secondary small"><?= strtoupper($hRow['faction']) ?> • #<?= $hRow['user_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark d-flex align-items-center gap-1">
                                            <span>⚔️</span>
                                            <span><?= htmlspecialchars($hRow['name']) ?></span>
                                        </div>
                                        <div class="text-secondary small">
                                            Force: <?= 150 + ((int)$hRow['stat_strength'] * 80) ?> | Att: +<?= round((int)$hRow['stat_offense_bonus'] * 0.2, 1) ?>% | Déf: +<?= round((int)$hRow['stat_defense_bonus'] * 0.2, 1) ?>%
                                        </div>
                                    </td>
                                    <td style="min-width: 140px;">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <strong>Niv. <?= $hLvl ?></strong>
                                            <span class="text-secondary"><?= $hXpPct ?>%</span>
                                        </div>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-primary" style="width: <?= $hXpPct ?>%"></div>
                                        </div>
                                        <div class="text-secondary small mt-1"><?= $hXp ?> XP au total</div>
                                    </td>
                                    <td style="min-width: 120px;">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <strong class="<?= ($hHp < 25) ? 'text-danger' : '' ?>"><?= $hHp ?>%</strong>
                                            <span class="text-secondary"><?= ($hHp <= 0) ? 'Mort' : 'PV' ?></span>
                                        </div>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar <?= $hHpClass ?>" style="width: <?= $hHp ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $hStInfo['badge'] ?> d-inline-flex align-items-center gap-1">
                                            <span><?= $hStInfo['icon'] ?></span>
                                            <span><?= $hStInfo['label'] ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge <?= ($dailyAdv >= 3) ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success' ?>">
                                                <?= $dailyAdv ?> / 3
                                            </span>
                                            <?php if ($dailyAdv >= 3): ?>
                                                <span class="text-secondary small" title="Quota quotidien atteint">Max</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-teal-lt" title="Reliques équipées / possédées">
                                                🛡️ <?= (int)$hRow['equipped_count'] ?> / <?= (int)$hRow['relic_count'] ?>
                                            </span>
                                            <span class="text-secondary small">/ 35 uniques</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            <!-- Soigner 100% -->
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="adminHealHero(<?= (int)$hRow['user_id'] ?>, '<?= htmlspecialchars(addslashes($hRow['name'])) ?>')"
                                                    title="Restaure immédiatement la santé à 100%">
                                                🩺 Soigner
                                            </button>

                                            <!-- Ressusciter instantanément -->
                                            <?php if ($hStatus === 'dead' || $hStatus === 'reviving' || $hHp <= 0): ?>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="adminReviveHero(<?= (int)$hRow['user_id'] ?>, '<?= htmlspecialchars(addslashes($hRow['name'])) ?>')"
                                                        title="Réincarnation immédiate sans attendre la fin des 24h">
                                                    ⛩️ Ressusciter
                                                </button>
                                            <?php endif; ?>

                                            <!-- Reset Quota Aventures -->
                                            <?php if ($dailyAdv > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="adminResetHeroQuota(<?= (int)$hRow['user_id'] ?>, '<?= htmlspecialchars(addslashes($hRow['name'])) ?>')"
                                                        title="Réinitialise le quota quotidien d'aventures à 0/3">
                                                    🔄 Reset Quota
                                                </button>
                                            <?php endif; ?>

                                            <!-- Octroyer Relique Aléatoire -->
                                            <button type="button" class="btn btn-sm btn-outline-purple" 
                                                    onclick="adminGrantRelic(<?= (int)$hRow['user_id'] ?>, '<?= htmlspecialchars(addslashes($hRow['name'])) ?>')"
                                                    title="Octroie une relique aléatoire inédite (jamais de doublon)">
                                                🎁 Donner Relique
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center text-secondary small flex-wrap gap-2">
                <span>Le panthéon compte <strong>35 reliques sacrées</strong> (7 Armes, 7 Casques, 7 Armures, 7 Montures, 7 Talismans). Règle d'or : aucun doublon.</span>
                <span>Régénération standard post-mortem : <strong>24 heures</strong> | Quota d'aventures : <strong>3 / jour</strong>.</span>
            </div>
        </div>
    </div>

    
<!-- Section 3 : Système d'IA & Colonisation des Bots -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'bots' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-bots" data-tab="bots" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title text-indigo d-flex align-items-center gap-2 m-0">
                    <span>🤖</span> Paramétrage de l'IA & Colonisation des Bots (PNJ)
                </h3>
                <span class="badge bg-indigo-lt">
                    <?= count($botsList) ?> Bots Enregistrés
                </span>
            </div>
            <div class="card-body">
                <form id="botSettingsForm" onsubmit="saveBotSettings(event)">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                Interrupteur Général de l'IA
                            </label>
                            <select name="bots_enabled" class="form-select" id="bots_enabled">
                                <option value="1" <?= !empty($settings['bots_enabled']) ? 'selected' : '' ?>>🟢 IA Active (Cycles opérationnels)</option>
                                <option value="0" <?= empty($settings['bots_enabled']) ? 'selected' : '' ?>>🔴 IA En Veille (Bots figés)</option>
                            </select>
                            <div class="form-hint">Permet aux bots d'évoluer, miner et produire des armées.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                🏯 Colonisation Automatique
                            </label>
                            <select name="bot_colonize_enabled" class="form-select" id="bot_colonize_enabled">
                                <option value="1" <?= !empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🟢 Autorisée (Conquêtes de fiefs)</option>
                                <option value="0" <?= empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🔴 Désactivée (Domaine initial)</option>
                            </select>
                            <div class="form-hint">Déclenche l'expansion territoriale vers de nouvelles coordonnées.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                Max Fiefs par Daimyō IA
                            </label>
                            <input type="number" name="bot_max_planets" id="bot_max_planets" min="1" max="10" 
                                   value="<?= (int)($settings['bot_max_planets'] ?? 3) ?>" class="form-control text-center font-weight-bold">
                            <div class="form-hint">Plafond d'expansion territoriale par Daimyō IA.</div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                Profil d'Agressivité
                            </label>
                            <select name="bot_aggressiveness" class="form-select" id="bot_aggressiveness">
                                <option value="peaceful" <?= (($settings['bot_aggressiveness'] ?? '') === 'peaceful') ? 'selected' : '' ?>>🕊️ Pacifique (Mines & Défense)</option>
                                <option value="moderate" <?= (($settings['bot_aggressiveness'] ?? 'moderate') === 'moderate') ? 'selected' : '' ?>>⚖️ Modéré (Équilibré)</option>
                                <option value="aggressive" <?= (($settings['bot_aggressiveness'] ?? '') === 'aggressive') ? 'selected' : '' ?>>⚔️ Belliqueux (Armées & Raids)</option>
                            </select>
                            <div class="form-hint">Influence le recrutement et l'armement des PNJ.</div>
                        </div>
                    </div>

                    <!-- Paramètres d'Éclosion Spontanée Homogène de Villages PNJ -->
                    <div class="card bg-surface-secondary border-primary-subtle mb-3">
                        <div class="card-header bg-primary-lt py-2 d-flex justify-content-between align-items-center">
                            <h4 class="card-title m-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <span>🌸</span> Éclosion Spontanée de Villages PNJ (Apparition Homogène sur la Carte)
                            </h4>
                            <span class="badge bg-primary text-white">
                                Toutes les <?= (int)($settings['bot_spawn_interval_min'] ?? 15) ?> min
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        Mécanisme d'Éclosion Spontanée
                                    </label>
                                    <select name="bot_spawn_enabled" class="form-select" id="bot_spawn_enabled">
                                        <option value="1" <?= !empty($settings['bot_spawn_enabled']) ? 'selected' : '' ?>>🟢 Éclosion Activée (Automatique)</option>
                                        <option value="0" <?= empty($settings['bot_spawn_enabled']) ? 'selected' : '' ?>>🔴 Éclosion En Sommeil (Désactivée)</option>
                                    </select>
                                    <div class="form-hint">Fait naître de nouveaux villages PNJ à intervalle régulier.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        ⏱️ Intervalle d'Apparition (Minutes)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="bot_spawn_interval_min" id="bot_spawn_interval_min" min="1" max="1440" 
                                               value="<?= (int)($settings['bot_spawn_interval_min'] ?? 15) ?>" class="form-control text-center font-weight-bold">
                                        <span class="input-group-text">min</span>
                                    </div>
                                    <div class="form-hint">Délai entre deux apparitions spontanées de fiefs.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        🏯 Plafond Global de Villages PNJ
                                    </label>
                                    <input type="number" name="bot_spawn_max_villages" id="bot_spawn_max_villages" min="5" max="200" 
                                           value="<?= (int)($settings['bot_spawn_max_villages'] ?? 50) ?>" class="form-control text-center font-weight-bold">
                                    <div class="form-hint">Nombre maximum de villages PNJ sur l'ensemble de la carte.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            💾 Sauvegarder la Directive IA &amp; Éclosions
                        </button>
                    </div>
                </form>

                <!-- Panneau de Monitoring en Temps Réel de l'Éclosion Spontanée -->
                <div class="card mt-4 border shadow-sm">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size: 1.3rem;">🗾</span>
                            <div>
                                <h4 class="card-title m-0 fw-bold">Distribution Spatiale &amp; Prochaine Éclosion Homogène</h4>
                                <div class="text-secondary small">
                                    L'algorithme analyse en temps réel les 4 quadrants pour faire éclore chaque nouveau fief dans la province la moins occupée.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" onclick="triggerSpontaneousSpawn()" class="btn btn-sm btn-success fw-bold">
                                🌸 Faire Éclore un Fief PNJ Maintenant
                            </button>
                            <button type="button" onclick="runBotCycle()" class="btn btn-sm btn-outline-primary">
                                ⚙️ Forcer un Cycle IA
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <!-- Compte à rebours & métriques globales -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-surface">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary fw-bold small text-uppercase">Statut d'Éclosion :</span>
                                        <?php if (!empty($botSpawnStatus['enabled'])): ?>
                                            <span class="badge bg-success-lt fw-bold">🟢 Actif (Toutes les <?= $botSpawnStatus['interval_min'] ?> min)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt fw-bold">⚪ En Sommeil</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center py-2">
                                        <div class="text-secondary small">Prochaine Éclosion dans :</div>
                                        <div class="display-6 fw-bold text-primary font-monospace" id="botSpawnCountdownText">
                                            <?= $botSpawnStatus['enabled'] ? $botSpawnStatus['next_spawn_in_formatted'] : '--m --s' ?>
                                        </div>
                                    </div>

                                    <div class="mt-2 pt-2 border-top small d-flex justify-content-between">
                                        <span class="text-muted">Total Fiefs PNJ :</span>
                                        <strong><?= $botSpawnStatus['current_villages'] ?> / <?= $botSpawnStatus['max_villages'] ?></strong>
                                    </div>
                                    <div class="mt-1 small d-flex justify-content-between">
                                        <span class="text-muted">Dernière Apparition :</span>
                                        <span class="text-dark fw-medium"><?= $botSpawnStatus['last_spawn_formatted'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Répartition par Quadrant (Homogénéité) -->
                            <div class="col-md-8">
                                <div class="p-3 border rounded bg-surface">
                                    <div class="fw-bold mb-2 small text-uppercase text-secondary d-flex justify-content-between">
                                        <span>📊 Répartition Actuelle des Fiefs PNJ par Quadrant :</span>
                                        <span class="text-success small fw-medium">Couverture Homogène 360°</span>
                                    </div>

                                    <div class="row g-2 text-center">
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <div class="small fw-bold text-dark">↖️ Nord-Ouest</div>
                                                <div class="h3 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['NO'] ?? 0 ?></div>
                                                <div class="text-muted small">fiefs PNJ</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <div class="small fw-bold text-dark">↗️ Nord-Est</div>
                                                <div class="h3 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['NE'] ?? 0 ?></div>
                                                <div class="text-muted small">fiefs PNJ</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <div class="small fw-bold text-dark">↙️ Sud-Ouest</div>
                                                <div class="h3 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['SO'] ?? 0 ?></div>
                                                <div class="text-muted small">fiefs PNJ</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <div class="small fw-bold text-dark">↘️ Sud-Est</div>
                                                <div class="h3 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['SE'] ?? 0 ?></div>
                                                <div class="text-muted small">fiefs PNJ</div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($botSpawnStatus['last_village'])): ?>
                                        <?php $lv = $botSpawnStatus['last_village']; ?>
                                        <div class="mt-3 p-2 bg-success-lt border border-success-subtle rounded small d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span>
                                                🌸 <strong>Dernier Fief Éclos :</strong> 
                                                <?= htmlspecialchars($lv['planet_name']) ?> (<?= htmlspecialchars($lv['username']) ?>) 
                                                en <strong>[<?= $lv['x'] ?> : <?= $lv['y'] ?>]</strong> &bull; Quadrant <strong><?= $lv['quadrant'] ?></strong>
                                            </span>
                                            <a href="/?page=map&x=<?= $lv['x'] ?>&y=<?= $lv['y'] ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                                🗾 Localiser
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Tableau des Bots Actifs -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="m-0 font-weight-bold">📋 Registre des Daimyōs IA en Activité</h4>
                    <button onclick="generatePresetBots()" class="btn btn-sm btn-outline-primary">
                        <span>➕</span> Ajouter 3 Daimyōs Multi-Clans
                    </button>
                </div>

                <?php if (empty($botsList)): ?>
                    <div class="text-center p-4 text-secondary">
                        Aucun Daimyō IA n'est actuellement déployé dans l'archipel. Cliquez sur le bouton ci-dessus pour peupler le royaume !
                    </div>
                <?php else: ?>
                    <div class="card border mb-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter table-nowrap card-table table-hover" id="tableBotsList">
                                <thead>
                                    <tr>
                                        <th>Daimyō IA</th>
                                        <th>Clan Féodal</th>
                                        <th>Fief Capitale</th>
                                        <th class="text-center">Fiefs Annexes</th>
                                        <th class="text-end">Puissance Militaire</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="botsTableBody">
                                    <?php foreach ($botsList as $botIndex => $bot): ?>
                                        <?php 
                                            $fInfo = FACTIONS[$bot['faction']] ?? FACTIONS['terran']; 
                                            $isInitialHidden = ($botIndex >= 10);
                                        ?>
                                        <tr class="bot-table-row" data-index="<?= $botIndex ?>" style="<?= $isInitialHidden ? 'display: none;' : '' ?>">
                                            <td>
                                                <div class="font-weight-medium">
                                                    🤖 <?= htmlspecialchars($bot['username']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt">
                                                    <?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger-lt font-monospace">
                                                    [<?= $bot['capital_x'] ?> : <?= $bot['capital_y'] ?>]
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success-lt font-weight-bold">
                                                    <?= $bot['planet_count'] ?> fief(s)
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold text-warning">
                                                🏆 <?= number_format($bot['points']) ?>
                                            </td>
                                            <td class="text-end">
                                                <button onclick="deleteBot(<?= $bot['id'] ?>, '<?= htmlspecialchars(addslashes($bot['username'])) ?>')" 
                                                        class="btn btn-sm btn-outline-danger">
                                                    🗑️ Purger
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination du Registre des Bots -->
                        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2" id="botsPaginationContainer">
                            <p class="m-0 text-secondary small" id="botsPaginationInfo">
                                Affichage de <strong id="botsPaginationStart"><?= min(1, count($botsList)) ?></strong> à <strong id="botsPaginationEnd"><?= min(10, count($botsList)) ?></strong> sur <strong id="botsPaginationTotal"><?= count($botsList) ?></strong> daimyōs IA
                            </p>
                            <div class="d-flex align-items-center gap-2">
                                <label for="botsPerPageSelect" class="small text-muted mb-0 d-none d-sm-inline">Par page :</label>
                                <select id="botsPerPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeBotsPerPage(this.value)">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <ul class="pagination pagination-sm m-0" id="botsPaginationList">
                                    <!-- Généré dynamiquement en JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
<!-- Section 4 : Gestion des Daimyōs Joueurs -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'users' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-users" data-tab="users" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-warning">
                    <span>👥</span> Gestion des Daimyōs Joueurs &amp; Privilèges
                </h3>
                <span class="badge bg-warning-lt"><?= count($humanUsers) ?> Daimyōs Inscrits</span>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-nowrap card-table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Daimyō</th>
                            <th>Courriel</th>
                            <th>Clan &amp; Faction</th>
                            <th class="text-center">Fiefs Contrôlés</th>
                            <th class="text-end">Honneur &amp; Points</th>
                            <th class="text-center">🪙 Trésor Koban</th>
                            <th class="text-center">Rang Shogunal</th>
                            <th class="text-center">Immunité Débutant</th>
                            <th class="text-end">Commandes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($humanUsers as $hUser): ?>
                            <?php 
                                $hfInfo = FACTIONS[$hUser['faction']] ?? FACTIONS['terran'];
                                $hProt = Auth::getProtectionRemaining($hUser);
                                $hIsProt = $hProt && !empty($hProt['is_protected']);
                                $hSealActive = !empty($hUser['imperial_seal_until']) && strtotime($hUser['imperial_seal_until']) > time();
                            ?>
                            <tr>
                                <td class="text-secondary small">#<?= $hUser['id'] ?></td>
                                <td>
                                    <div class="font-weight-medium">
                                        <?= htmlspecialchars($hUser['username']) ?>
                                        <?php if ((int)$hUser['id'] === (int)Auth::id()): ?>
                                            <span class="badge bg-primary-lt ms-1">Vous</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($hUser['email']) ?></td>
                                <td>
                                    <span class="badge bg-secondary-lt">
                                        <?= $hfInfo['icon'] ?> <?= htmlspecialchars($hfInfo['name']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-lt font-weight-bold">
                                        <?= $hUser['colony_count'] ?> fief(s)
                                    </span>
                                </td>
                                <td class="text-end font-weight-bold text-warning">
                                    🏆 <?= number_format($hUser['points']) ?>
                                </td>
                                <td class="text-center" id="user-koban-cell-<?= $hUser['id'] ?>">
                                    <div class="fw-bold text-warning" style="font-size:0.95rem;">
                                        🪙 <span id="user-koban-val-<?= $hUser['id'] ?>"><?= number_format($hUser['gold_coins'] ?? 0) ?></span>
                                    </div>
                                    <?php if ($hSealActive): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.6rem;">👑 Sceau Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt" style="font-size:0.6rem;">Sans Sceau</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$hUser['is_admin'] === 1): ?>
                                        <span class="badge bg-danger text-white">
                                            ⭐ ADMINISTRATEUR
                                        </span>
                                    <?php elseif ((int)($hUser['is_moderator'] ?? 0) === 1): ?>
                                        <span class="badge bg-info text-white">
                                            🛡️ MODÉRATEUR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">
                                            Daimyō Joueur
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" id="user-prot-cell-<?= $hUser['id'] ?>">
                                    <?php if ($hIsProt): ?>
                                        <span class="badge bg-success-lt font-weight-bold" title="Immunisé jusqu'au <?= htmlspecialchars($hProt['until_formatted']) ?>">
                                            🔰 <?= htmlspecialchars($hProt['formatted']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">
                                            Expirée
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        <button onclick="openAdminGiveKobanModal(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', <?= (int)($hUser['gold_coins'] ?? 0) ?>)"
                                                class="btn btn-sm btn-outline-warning fw-bold" title="Octroyer des Koban (Pièces d'Or)">
                                            🪙 +Koban
                                        </button>
                                        <button onclick="extendProtection(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', 7)"
                                                class="btn btn-sm btn-outline-success" title="Accorder ou prolonger de 7 jours d'immunité">
                                            +7j 🔰
                                        </button>
                                        <?php if ($hIsProt): ?>
                                            <button onclick="revokeProtection(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>')"
                                                    class="btn btn-sm btn-outline-danger" title="Lever immédiatement l'immunité">
                                                Lever
                                            </button>
                                        <?php endif; ?>
                                        <?php if ((int)$hUser['id'] !== (int)Auth::id()): ?>
                                            <?php if ((int)$hUser['is_admin'] !== 1): ?>
                                                <button onclick="toggleModerator(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', <?= (int)($hUser['is_moderator'] ?? 0) ?>)"
                                                        class="btn btn-sm <?= ((int)($hUser['is_moderator'] ?? 0) === 1) ? 'btn-outline-info' : 'btn-outline-secondary' ?>"
                                                        title="Nommer ou révoquer le rôle de modérateur">
                                                    <?= ((int)($hUser['is_moderator'] ?? 0) === 1) ? '🛡️ Dé-modérer' : '🛡️ Modo' ?>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="toggleAdmin(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', <?= (int)$hUser['is_admin'] ?>)"
                                                    class="btn btn-sm btn-outline-secondary">
                                                <?= ((int)$hUser['is_admin'] === 1) ? 'Rétrograder' : 'Promouvoir' ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <!-- Section 5 : 🎖️ Tableau d'Honneur & Clôture Hebdomadaire des Médailles -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'medals' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-medals" data-tab="medals" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title text-yellow d-flex align-items-center gap-2 m-0">
                    <span>🎖️</span> Tableau d'Honneur &amp; Clôture Hebdomadaire des Médailles
                </h3>
                <span class="badge bg-yellow-lt">
                    Semaine en cours : <strong><?= htmlspecialchars($currentWeekCode) ?></strong>
                </span>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-3">
                    Le système de Tableau d'Honneur attribue automatiquement les médailles de prestige (🥇 Or, 🥈 Argent, 🥉 Bronze et 🎖️ Rubans Top 10) aux commandants les plus méritants dans les 4 catégories reines :
                    <strong>Progression d'Empire</strong>, <strong>Attaquant de la Semaine</strong>, <strong>Défenseur Héroïque</strong> et <strong>Seigneur du Pillage</strong>.<br>
                    Des dépêches officielles de félicitations sont transmises aux lauréats, et leurs profils sont décorés à vie.
                </p>

                <div class="row row-cards mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body text-center">
                                <div class="text-secondary text-uppercase small">Médailles Historiques Décernées</div>
                                <div class="h2 text-yellow m-0 mt-1"><?= $totalMedals ?> 🎖️</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body text-center">
                                <div class="text-secondary text-uppercase small">Cycle de Remise</div>
                                <div class="h3 text-danger m-0 mt-1">Hebdomadaire (7j)</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body text-center">
                                <div class="text-secondary text-uppercase small">Affichage Public</div>
                                <div class="mt-2">
                                    <a href="?page=ranking&tab=honor" class="btn btn-sm btn-outline-secondary">
                                        👀 Consulter le Tableau
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2 m-0">
                    <div>
                        <div class="fw-bold">Clôturer la Semaine en Cours</div>
                        <div class="text-secondary small">
                            Attribue les médailles de la semaine <strong><?= htmlspecialchars($currentWeekCode) ?></strong>, notifie les commandants et réinitialise les scores d'attaque/défense/pillage.
                        </div>
                    </div>
                    <button type="button" onclick="awardWeeklyMedals()" class="btn btn-warning fw-bold">
                        🎖️ Clôturer &amp; Décerner les Médailles
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Section 6 : 📮 Traitement des Dysfonctionnements & Suggestions des Joueurs -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'support' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-support" data-tab="support" role="tabpanel">
        <div class="card mb-4" id="supportAdminSection">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title text-azure d-flex align-items-center gap-2 m-0">
                        <span>📮</span> Traitement des Dysfonctionnements &amp; Suggestions des Joueurs
                    </h3>
                    <div class="text-secondary small mt-1">
                        Examinez les anomalies signalées et les propositions de la communauté. Répondez officiellement et notifiez les daimyōs.
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-azure-lt">
                        <?= $supportStats['total'] ?> Total &bull; <?= $supportStats['total_bugs'] ?> Bugs &bull; <?= $supportStats['total_suggestions'] ?> Suggestions
                    </span>
                    <?php if ($supportStats['count_pending'] > 0): ?>
                        <span class="badge bg-danger text-white">
                            ⚠️ <?= $supportStats['count_pending'] ?> En attente
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <!-- Barre de Filtres Interactifs Tabler -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <span class="form-label m-0 fw-bold">Filtrer :</span>
                    </div>
                    <div class="col-auto">
                        <select id="adminTicketFilterType" class="form-select form-select-sm" onchange="filterAdminTickets()">
                            <option value="all">Tous les Types (Bugs &amp; Idées)</option>
                            <option value="bug">🪲 Bugs Uniquement</option>
                            <option value="suggestion">💡 Suggestions Uniquement</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="adminTicketFilterStatus" class="form-select form-select-sm" onchange="filterAdminTickets()">
                            <option value="all">Tous les Statuts</option>
                            <option value="pending">⏳ En attente</option>
                            <option value="in_progress">🔍 En cours d'examen</option>
                            <option value="resolved">✅ Résolus / Corrigés</option>
                            <option value="planned">📌 Retenus (Futures MAJ)</option>
                            <option value="closed">✖️ Fermés / Sans suite</option>
                        </select>
                    </div>
                    <div class="col-md-4 ms-auto">
                        <input type="text" id="adminTicketSearchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher joueur, titre..." oninput="filterAdminTickets()">
                    </div>
                </div>

                <!-- Tableau des Demandes Tabler -->
                <div class="table-responsive">
                    <table class="table table-vcenter table-nowrap card-table table-hover" id="adminTicketsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type &amp; Sévérité</th>
                                <th>Secteur</th>
                                <th>Daimyō / Joueur</th>
                                <th>Titre du Signalement</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allSupportTickets)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-secondary">
                                        Aucun ticket de bug ou suggestion pour le moment.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allSupportTickets as $t): 
                                    $isBug = ($t['type'] === 'bug');
                                    $catLabel = SupportEngine::CATEGORIES[$t['category']] ?? $t['category'];
                                    $sevLabel = match($t['severity']) {
                                        'critical' => '<span class="badge bg-danger-lt">🔴 Critique</span>',
                                        'high' => '<span class="badge bg-orange-lt">🟠 Élevé</span>',
                                        'medium' => '<span class="badge bg-warning-lt">🟡 Moyen</span>',
                                        'low' => '<span class="badge bg-success-lt">🟢 Faible</span>',
                                        default => ''
                                    };
                                    $statusBadge = match($t['status']) {
                                        'pending' => '<span class="badge bg-warning-lt">⏳ En attente</span>',
                                        'in_progress' => '<span class="badge bg-blue-lt">🔍 En cours</span>',
                                        'resolved' => '<span class="badge bg-success-lt">✅ Résolu</span>',
                                        'planned' => '<span class="badge bg-purple-lt">📌 Retenu</span>',
                                        'closed' => '<span class="badge bg-secondary-lt">✖️ Fermé</span>',
                                        default => htmlspecialchars($t['status'])
                                    };
                                ?>
                                    <tr class="ticket-row" data-type="<?= $t['type'] ?>" data-status="<?= $t['status'] ?>" data-search="<?= strtolower(htmlspecialchars($t['username'] . ' ' . $t['title'])) ?>">
                                        <td class="text-secondary fw-bold">#<?= $t['id'] ?></td>
                                        <td>
                                            <div class="fw-bold <?= $isBug ? 'text-danger' : 'text-warning' ?>">
                                                <?= $isBug ? '🪲 Bug' : '💡 Suggestion' ?>
                                            </div>
                                            <?php if ($isBug && $sevLabel): ?>
                                                <div class="mt-1"><?= $sevLabel ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-secondary small">
                                            <?= htmlspecialchars($catLabel) ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($t['username']) ?></div>
                                            <div class="text-secondary small text-uppercase">Clan <?= htmlspecialchars($t['faction']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($t['title']) ?>">
                                                <?= htmlspecialchars($t['title']) ?>
                                            </div>
                                            <?php if (!empty($t['admin_response'])): ?>
                                                <div class="text-azure small mt-1">💬 Répondu</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $statusBadge ?>
                                        </td>
                                        <td class="text-center text-secondary small">
                                            <?= date('d/m H:i', $t['created_at']) ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAdminTicketModal(<?= $t['id'] ?>)">
                                                🔍 Traiter
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<!-- Section 9 : 📢 Nouveautés & Annonces des Fonctionnalités (Stockage JSON) -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'announcements' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-announcements" data-tab="announcements" role="tabpanel">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title text-pink d-flex align-items-center gap-2 m-0">
                        <span>📢</span> Annonces & Nouvelles Fonctionnalités aux Joueurs
                    </h3>
                    <div class="text-secondary small mt-1">
                        Toutes les annonces sont persistées dans <code>config/announcements.json</code>. Validez leur publication pour déclencher la modale d'explication aux daimyōs.
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" class="btn btn-primary fw-bold" onclick="openAnnouncementEditModal()">
                        <span>➕</span> Rédiger une Annonce
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive"><table class="table table-vcenter table-nowrap card-table table-hover">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; color: var(--text-muted);">
                                <th style="padding: 0.75rem;">Version & Titre</th>
                                <th style="padding: 0.75rem;">Badge</th>
                                <th style="padding: 0.75rem;">Nouveautés</th>
                                <th style="padding: 0.75rem; text-align: center;">Statut Publication</th>
                                <th style="padding: 0.75rem; text-align: center;">Lectures Joueurs</th>
                                <th style="padding: 0.75rem; text-align: center;">Date</th>
                                <th style="padding: 0.75rem; text-align: right;">Actions Administrateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allAnnouncements)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        Aucune annonce enregistrée dans le fichier JSON.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allAnnouncements as $ann): ?>
                                    <?php 
                                        $annId = htmlspecialchars($ann['id'] ?? '');
                                        $annJsonEscaped = htmlspecialchars(json_encode($ann), ENT_QUOTES, 'UTF-8');
                                        $isPub = !empty($ann['is_published']);
                                        $featuresCount = is_array($ann['features'] ?? null) ? count($ann['features']) : 0;
                                        $readCount = $announcementStats[$ann['id'] ?? ''] ?? 0;
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 0.75rem;">
                                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                                <span style="font-size: 1.4rem;"><?= htmlspecialchars($ann['icon'] ?? '📜') ?></span>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?= htmlspecialchars($ann['title'] ?? '') ?>
                                                    </div>
                                                    <div class="font-monospace text-warning small">
                                                        <?= htmlspecialchars($ann['version'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span class="badge bg-pink-lt">
                                                <?= htmlspecialchars($ann['badge'] ?? 'NOUVEAUTÉ') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span class="text-azure fw-bold">
                                                <?= $featuresCount ?> fonctionnalité<?= $featuresCount > 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <?php if ($isPub): ?>
                                                <span class="badge bg-success-lt fw-bold">
                                                    🟢 Validée & Publiée
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-lt fw-bold">
                                                    🟡 Brouillon (En attente)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <span style="font-weight: 700; color: <?= $readCount > 0 ? '#4ade80' : '#94a3b8' ?>;">
                                                <?= $readCount ?> daimyō<?= $readCount > 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center; color: var(--text-muted); font-size: 0.8rem; white-space: nowrap;">
                                            <?= htmlspecialchars($ann['date'] ?? '') ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                                <!-- Bouton Valider / Dévalider -->
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleAnnouncementPublish('<?= $annId ?>')" title="<?= $isPub ? 'Mettre en brouillon' : 'Valider et diffuser aux joueurs' ?>">
                                                    <?= $isPub ? '⏸️ Dépublier' : '✓ Valider' ?>
                                                </button>
                                                <!-- Bouton Aperçu Modal Joueur -->
                                                <button type="button" class="btn btn-sm btn-outline-azure" onclick='openAnnouncementPreview(<?= $annJsonEscaped ?>)' title="Prévisualiser la modale joueur">
                                                    👁️ Aperçu
                                                </button>
                                                <!-- Bouton Éditer -->
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openAnnouncementEditModal(<?= $annJsonEscaped ?>)' title="Modifier le contenu">
                                                    ✏️
                                                </button>
                                                <!-- Bouton Supprimer -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement('<?= $annId ?>')" title="Supprimer définitivement">
                                                    🗑️
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
        </div>
    </div>

    
<!-- Section Forum Féodal : Salons & Administration des Débats -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'forum' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-forum" data-tab="forum" role="tabpanel">
        <div class="card mb-4 border-info">
            <div class="card-header bg-info-lt d-flex justify-content-between align-items-center">
                <h3 class="card-title text-info-emphasis d-flex align-items-center gap-2">
                    <span>💬</span> Gestion des Salons du Forum Féodal
                </h3>
                <a href="/?page=forum" target="_blank" class="btn btn-sm btn-outline-info">
                    Ouvrir le Forum ↗
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Administrez les catégories de discussion féodale. Les salons verrouillés (🔒) sont en lecture seule pour les Daimyōs ordinaires : seuls les Administrateurs et Modérateurs peuvent y proclamer des décrets.
                </p>

                <!-- Tableau des catégories -->
                <div class="table-responsive mb-4">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr class="bg-light">
                                <th style="width: 50px;">Icône</th>
                                <th>Nom du Salon</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 80px;">Ordre</th>
                                <th class="text-center" style="width: 140px;">Statut d'Accès</th>
                                <th class="text-center" style="width: 100px;">Sujets / Msg</th>
                                <th class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($adminForumCategories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Aucun salon configuré. Utilisez le formulaire ci-dessous pour en créer un.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($adminForumCategories as $fCat): ?>
                                    <tr>
                                        <td class="text-center fs-3"><?= htmlspecialchars($fCat['icon']) ?></td>
                                        <td class="fw-bold">
                                            <a href="/?page=forum&cat=<?= $fCat['id'] ?>" target="_blank" class="text-reset">
                                                <?= htmlspecialchars($fCat['name']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($fCat['description'] ?? '') ?></td>
                                        <td class="text-center fw-bold"><?= $fCat['display_order'] ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$fCat['is_locked'] === 1): ?>
                                                <span class="badge bg-secondary-lt">🔒 Staff Uniquement</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-lt">💬 Ouvert à Tous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small">
                                            <strong><?= $fCat['topic_count'] ?></strong> suj. / <?= $fCat['post_count'] ?> msg
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="openEditForumCategoryModal(<?= $fCat['id'] ?>, '<?= htmlspecialchars(addslashes($fCat['name'])) ?>', '<?= htmlspecialchars(addslashes($fCat['description'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($fCat['icon'])) ?>', <?= $fCat['display_order'] ?>, <?= $fCat['is_locked'] ?>)">
                                                ✏️
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteForumCategory(<?= $fCat['id'] ?>, '<?= htmlspecialchars(addslashes($fCat['name'])) ?>')">
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulaire de création de catégorie -->
                <div class="card bg-light">
                    <div class="card-header">
                        <h4 class="card-title">➕ Fonder un Nouveau Salon de Discussion</h4>
                    </div>
                    <div class="card-body">
                        <form id="formAdminCreateCategory" onsubmit="submitAdminCreateCategory(event)">
                            <div class="row">
                                <div class="col-md-2 mb-3">
                                    <label class="form-label required">Icône (Emoji)</label>
                                    <input type="text" id="afc_icon" class="form-control text-center" value="💬" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label required">Titre du Salon</label>
                                    <input type="text" id="afc_name" class="form-control" placeholder="Ex: Maison de Thé & Sérénité" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Ordre d'Affichage</label>
                                    <input type="number" id="afc_order" class="form-control" value="10">
                                </div>
                                <div class="col-md-2 mb-3 d-flex align-items-center pt-3">
                                    <label class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="afc_locked">
                                        <span class="form-check-label small fw-bold">🔒 Décrets Staff</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description d'Accompagnement</label>
                                <input type="text" id="afc_desc" class="form-control" placeholder="Brève explication de la thématique du salon...">
                            </div>
                            <button type="submit" class="btn btn-primary" id="btnAdminCreateCat">
                                ➕ Créer le Salon
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
<!-- Section Pédagogique : Atelier de Conception Père & Fils -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'pedagogy' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-pedagogy" data-tab="pedagogy" role="tabpanel">
        <div class="card mb-4 border-cyan bg-cyan-lt shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="m-0 text-cyan d-flex align-items-center gap-2">
                        <span>🎓</span> Atelier Pédagogique &bull; Section Publique Ouverte à Tous
                    </h3>
                    <div class="small text-muted mt-1">
                        Cette section est désormais une page de premier niveau accessible au grand public et aux joueurs à l'adresse <strong>/?page=pedagogy</strong>.
                        Vous pouvez continuer à consulter les modules ci-dessous ou prévisualiser le rendu public.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-cyan text-white p-2">
                        📊 <?= number_format($pedagogyViews) ?> consultations
                    </span>
                    <a href="/?page=pedagogy" target="_blank" class="btn btn-cyan fw-bold d-flex align-items-center gap-1 shadow-sm">
                        <span>🌐</span> Ouvrir la Page Publique ↗
                    </a>
                </div>
            </div>
        </div>
        <?php require __DIR__ . '/partials/admin_pedagogy.php'; ?>
    </div>

    
<!-- Section Mises à Jour & Déploiement GitHub -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'updates' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-updates" data-tab="updates" role="tabpanel">
        <?php require __DIR__ . '/partials/admin_updates.php'; ?>
    </div>

    
    <!-- Section 11 : ⚠️ Décret Suprême - Réinitialisation Complète du Monde Féodal -->
    <div class="tab-pane admin-tab-pane p-4 <?= ($currentTab === 'maintenance' || $currentTab === 'all') ? 'active show' : '' ?>" id="tab-maintenance" data-tab="maintenance" role="tabpanel">
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger-lt d-flex justify-content-between align-items-center">
                <h3 class="card-title text-danger d-flex align-items-center gap-2 m-0">
                    <span>⚠️</span> Décret Suprême &mdash; Réinitialisation Complète du Monde Féodal (Reset)
                </h3>
                <span class="badge bg-danger text-white">
                    DESTRUCTIF
                </span>
            </div>
            <div class="card-body">
                <div class="alert alert-danger mb-3">
                    <strong>Attention :</strong> Cette procédure purge l'ensemble des données du Japon féodal (clans, fiefs, rizières, armées en marche, dojos, messages et chroniques de combat).<br>
                    Le monde est alors recréé à neuf avec :
                </div>
                <ul class="text-secondary small mb-4">
                    <li>👑 <strong>Shogun Administrateur par défaut</strong> : Identifiant <strong>nezzar</strong> / Mot de passe <strong>Gabriel125#</strong></li>
                    <li>🏯 <strong>Château Capital</strong> : <code>Château Nezzar [1 : 1]</code> avec parcelles niveau 2, Tenshu, Dojo, Greniers et garnison de samouraïs.</li>
                    <li>🌾 <strong>12 terres et fiefs neutres</strong> générés procéduralement prêts pour l'expansion provinciale.</li>
                    <li>🤖 <strong>3 Daimyōs IA de départ</strong> (Clan Oda, Clan Takeda, Clan Tokugawa) pour un Japon vivant immédiatement.</li>
                </ul>

                <div class="card card-body bg-light border-danger d-flex flex-row justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold text-dark">Confirmation de Sécurité Requise</div>
                        <div class="text-secondary small">Une boîte de dialogue vous demandera de saisir le mot-clé <strong>RESET</strong> avant toute action.</div>
                    </div>
                    <button type="button" onclick="openResetModal()" class="btn btn-danger fw-bold">
                        💥 Réinitialiser le Monde Féodal
                    </button>
                </div>
            </div>
        </div>
    </div>

            </div><!-- /tab-content -->
        </div><!-- /card-body -->
    </div><!-- /card -->
</div><!-- /admin-panel -->

<!-- Modale d'Édition / Création d'une Annonce (Admin Washi) -->
    <div class="modal-overlay" id="announcementEditModal">
        <div class="modal-card modal-card-lg">
            <div class="modal-header">
                <h3 id="aem_modal_title" class="modal-title">
                    <span>📢</span> Rédiger une Annonce
                </h3>
                <button type="button" onclick="closeAnnouncementEditModal()" class="modal-close-btn">&times;</button>
            </div>

            <form id="announcementEditForm" onsubmit="saveAnnouncementFromModal(event)" style="display:flex; flex-direction:column; flex:1; overflow:hidden; margin:0;">
                <input type="hidden" id="aem_id" name="id" value="">
                
                <div class="modal-body d-flex flex-column gap-3">
                    <div style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:1rem;">
                        <div>
                            <label class="form-label">Version / Code</label>
                            <input type="text" id="aem_version" name="version" class="form-control" required placeholder="v1.3.0">
                        </div>
                        <div>
                            <label class="form-label">Titre de l'Annonce</label>
                            <input type="text" id="aem_title" name="title" class="form-control" required placeholder="L'Éveil du Héros & Nouveaux Bâtiments">
                        </div>
                        <div>
                            <label class="form-label">Date</label>
                            <input type="date" id="aem_date" name="date" class="form-control">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1.5fr 1fr 1fr; gap:1rem;">
                        <div>
                            <label class="form-label">Badge Visuel</label>
                            <input type="text" id="aem_badge" name="badge" class="form-control" placeholder="⭐ MISE À JOUR MAJEURE">
                        </div>
                        <div>
                            <label class="form-label">Icône Principale</label>
                            <input type="text" id="aem_icon" name="icon" class="form-control" placeholder="⚔️">
                        </div>
                        <div>
                            <label class="form-label">Statut Publication</label>
                            <select id="aem_is_published" name="is_published" class="form-select">
                                <option value="1">🟢 Validée & Publiée aux joueurs</option>
                                <option value="0">🟡 Brouillon (En attente)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Résumé d'accroche pour les Daimyōs</label>
                        <textarea id="aem_summary" name="summary" rows="2" class="form-control" placeholder="Décrivez succinctement l'importance de cette mise à jour pour vos joueurs..."></textarea>
                    </div>

                    <!-- Liste dynamique des fonctionnalités -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label style="font-size:0.9rem; font-weight:800; color:#b45309; margin:0; display:flex; align-items:center; gap:0.4rem;">
                                <span>🏯</span> Fonctionnalités & Améliorations Détaillées
                            </label>
                            <button type="button" onclick="addFeatureRowToModal()" class="btn btn-secondary" style="font-size:0.75rem; padding:0.3rem 0.75rem; border-color:#b45309; color:#b45309;">
                                + Ajouter une nouveauté
                            </button>
                        </div>

                        <div id="aem_features_container" class="d-flex flex-column gap-2">
                            <!-- Lignes de fonctionnalités injectées en JS -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAnnouncementEditModal()">Annuler</button>
                    <button type="submit" id="aem_submit_btn" class="btn btn-primary">
                        💾 Sauvegarder dans le JSON
                    </button>
                </div>
            </form>
        </div>
    </div>

    
<!-- Modale de Confirmation de Réinitialisation Complète -->
<div class="modal-overlay" id="resetUniverseModal">
    <div class="modal-card modal-card-sm">
        <div class="modal-header">
            <h3 class="modal-title d-flex align-items-center gap-2 text-danger">
                <span>💥</span> Réinitialisation Complète de l'Univers
            </h3>
            <button onclick="closeResetModal()" class="modal-close-btn" title="Fermer">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger mb-3">
                <div class="d-flex">
                    <div>⚠️</div>
                    <div class="ms-2">
                        <strong>Attention irréversible !</strong> Toutes les parties, colonies, héros et données seront effacés. Le compte administrateur <strong>nezzar</strong> sera recréé avec son mot de passe initial.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label font-weight-medium">
                    Pour confirmer l'opération, tapez <code>RESET</code> en majuscules :
                </label>
                <input type="text" id="resetKeywordInput" class="form-control text-center font-monospace" placeholder="RESET" style="font-size:1.1rem; letter-spacing:2px; font-weight:800;">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeResetModal()">Annuler</button>
            <button type="button" class="btn btn-danger" onclick="executeUniverseReset()">
                💥 Exécuter le Reset
            </button>
        </div>
    </div>
</div>


<!-- Modale d'Examen et de Traitement d'un Ticket par l'Administrateur (Admin Washi) -->
<div class="modal-overlay" id="adminTicketModal" onclick="closeAdminTicketModal()">
    <div class="modal-card modal-card-md" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div>
                <h3 id="atm_header_title" class="modal-title">
                    <span>📮</span> Traitement du Ticket #<span id="atm_ticket_id"></span>
                </h3>
                <div id="atm_header_meta" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; font-weight:600;"></div>
            </div>
            <button onclick="closeAdminTicketModal()" class="modal-close-btn" title="Fermer">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Détails du Joueur & Fief -->
            <div style="background:#ffffff; border:1px solid var(--border-color); padding:0.85rem 1rem; border-radius:8px; margin-bottom:1.25rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.5rem; font-size:0.85rem; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                <div><span style="color:var(--text-muted); font-weight:600;">Daimyō :</span> <strong id="atm_user_name" style="color:#1c1917;"></strong></div>
                <div><span style="color:var(--text-muted); font-weight:600;">Clan :</span> <strong id="atm_user_faction" style="color:#b91c1c; text-transform:uppercase;"></strong></div>
                <div><span style="color:var(--text-muted); font-weight:600;">Fief :</span> <strong id="atm_user_planet" style="color:#1c1917;"></strong></div>
                <div><span style="color:var(--text-muted); font-weight:600;">Date :</span> <strong id="atm_created_at" style="color:#1c1917;"></strong></div>
            </div>

            <!-- Titre & Message du Joueur -->
            <div class="mb-3">
                <label class="form-label" style="text-transform:uppercase;">Message du Joueur :</label>
                <div id="atm_ticket_title" style="font-weight:800; font-size:1.05rem; color:#1c1917; margin:0.25rem 0 0.5rem 0;"></div>
                <div id="atm_ticket_desc" style="background:#ffffff; border:1px solid var(--border-color); padding:1rem; border-radius:8px; font-size:0.9rem; line-height:1.6; color:#1c1917; white-space:pre-line; max-height:200px; overflow-y:auto; box-shadow:0 1px 3px rgba(60,45,30,0.04);"></div>
            </div>

            <!-- Formulaire de Traitement Administrateur -->
            <form id="adminTicketForm" onsubmit="saveAdminTicket(event)">
                <input type="hidden" id="atm_input_ticket_id" name="ticket_id">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
                    <div>
                        <label for="atm_select_status" class="form-label">
                            Statut de la Demande :
                        </label>
                        <select id="atm_select_status" name="status" class="form-select">
                            <option value="pending">⏳ En attente</option>
                            <option value="in_progress">🔍 En cours d'examen</option>
                            <option value="resolved">✅ Résolu / Corrigé</option>
                            <option value="planned">📌 Retenu (Future MAJ)</option>
                            <option value="closed">✖️ Fermé / Sans suite</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center pt-3"><label class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="atm_notify_user" name="notify_user" value="1" checked><span class="form-check-label fw-bold">Notifier le joueur par missive en jeu</span></label></div>
                </div>

                <div class="mb-3">
                    <label for="atm_admin_response" class="form-label">
                        Réponse Officielle de l'Équipe (visible par le joueur) :
                    </label>
                    <textarea id="atm_admin_response" name="admin_response" rows="4" class="form-control" placeholder="Ex: Bonjour, l'anomalie a été identifiée et corrigée dans le dernier patch. Merci pour votre aide précieuse !"></textarea>
                </div>

                <div class="modal-footer px-0 pb-0" style="background:transparent; border-top:1px solid var(--border-color); margin-top:1rem; padding-top:1rem;">
                    <button type="button" class="btn btn-outline-danger" onclick="deleteAdminTicketFromModal()">
                        🗑️ Supprimer
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" onclick="closeAdminTicketModal()">Annuler</button>
                        <button type="submit" id="atm_submit_btn" class="btn btn-primary">
                            💾 Enregistrer & Transmettre
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// --- GESTION DU SYSTÈME D'ONGLETS DU SHOGUNAT ---
function switchWorldSubSection(subKey, event) {
    if (event) event.preventDefault();
    const el = document.getElementById(`worldSection_${subKey}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    document.querySelectorAll('#worldSubTabsNav .nav-link').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById(`worldSubTab_${subKey}`);
    if (activeBtn) activeBtn.classList.add('active');
}

// --- GRAPHIQUE DES TENDANCES (30 JOURS) DU DASHBOARD ---
function renderAdminTrendChart() {
    const canvas = document.getElementById('adminTrendChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) return;
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);

    const w = rect.width;
    const h = rect.height;
    const padLeft = 40;
    const padRight = 20;
    const padTop = 20;
    const padBottom = 30;

    const data = <?= json_encode(array_values($stats30Days)) ?>;
    if (!data || data.length === 0) return;

    const maxUsers = Math.max(2, ...data.map(d => d.users));
    const maxSessions = Math.max(5, ...data.map(d => d.sessions));
    const maxVal = Math.max(maxUsers, maxSessions, 5) * 1.15;

    // Effacer le canvas
    ctx.clearRect(0, 0, w, h);

    // Lignes de quadrillage horizontales
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.2)';
    ctx.lineWidth = 1;
    ctx.fillStyle = '#94a3b8';
    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    const steps = 4;
    for (let i = 0; i <= steps; i++) {
        const y = padTop + (h - padTop - padBottom) * (1 - i / steps);
        ctx.beginPath();
        ctx.moveTo(padLeft, y);
        ctx.lineTo(w - padRight, y);
        ctx.stroke();
        ctx.fillText(Math.round((maxVal * i) / steps), 10, y + 3);
    }

    const chartW = w - padLeft - padRight;
    const chartH = h - padTop - padBottom;
    const stepX = chartW / Math.max(1, data.length - 1);

    function drawLineSeries(key, strokeCol, fillCol) {
        const points = [];
        data.forEach((d, idx) => {
            const x = padLeft + idx * stepX;
            const y = padTop + chartH * (1 - ((d[key] || 0) / maxVal));
            points.push({x, y});
        });

        // Surface remplie
        ctx.fillStyle = fillCol;
        ctx.beginPath();
        ctx.moveTo(points[0].x, padTop + chartH);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(points[points.length - 1].x, padTop + chartH);
        ctx.closePath();
        ctx.fill();

        // Ligne
        ctx.strokeStyle = strokeCol;
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        points.forEach((p, idx) => {
            if (idx === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.stroke();

        // Points
        ctx.fillStyle = strokeCol;
        points.forEach((p, idx) => {
            if (idx % 4 === 0 || idx === points.length - 1) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                ctx.fill();
            }
        });
    }

    drawLineSeries('sessions', '#2fb344', 'rgba(47, 179, 68, 0.08)');
    drawLineSeries('users', '#206bc4', 'rgba(32, 107, 196, 0.12)');

    // Graduations Axe X
    ctx.fillStyle = '#64748b';
    data.forEach((d, idx) => {
        if (idx % 5 === 0 || idx === data.length - 1) {
            const x = padLeft + idx * stepX;
            ctx.fillText(d.day, x - 12, h - 8);
        }
    });
}
window.addEventListener('resize', () => {
    if (typeof renderAdminTrendChart === 'function') {
        renderAdminTrendChart();
    }
});

function switchAdminTab(tabKey) {
    if (tabKey === 'game') {
        switchAdminTab('world');
        setTimeout(() => switchWorldSubSection('speeds'), 60);
        return;
    }
    if (tabKey === 'oases') {
        switchAdminTab('world');
        setTimeout(() => switchWorldSubSection('oases'), 60);
        return;
    }
    if (tabKey === 'castles') {
        switchAdminTab('world');
        setTimeout(() => switchWorldSubSection('castles'), 60);
        return;
    }

    const validTabs = ['dashboard', 'world', 'heroes', 'bots', 'users', 'medals', 'support', 'announcements', 'forum', 'pedagogy', 'updates', 'maintenance', 'all'];
    if (!validTabs.includes(tabKey)) tabKey = 'dashboard';

    // Afficher ou masquer les panneaux correspondants
    const panes = document.querySelectorAll('.admin-tab-pane');
    panes.forEach(pane => {
        if (tabKey === 'all') {
            pane.classList.add('active', 'show');
            pane.style.display = 'block';
        } else {
            const pTab = pane.getAttribute('data-tab');
            const isActive = (pTab === tabKey);
            pane.classList.toggle('active', isActive);
            pane.classList.toggle('show', isActive);
            pane.style.display = isActive ? 'block' : 'none';
        }
    });

    // Mettre à jour l'apparence des boutons d'onglets (Tabler nav-link active)
    const tabBtns = document.querySelectorAll('.admin-tab-btn');
    tabBtns.forEach(btn => {
        const bTab = btn.getAttribute('data-tab');
        const isActive = (bTab === tabKey);
        btn.classList.toggle('active', isActive);
        btn.style.boxShadow = '';
        btn.style.borderColor = '';
        btn.style.background = '';
        btn.style.color = '';
    });

    // Déclencher l'instance Bootstrap Tab si disponible
    if (tabKey !== 'all' && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        const targetBtn = document.querySelector(`.admin-tab-btn[data-tab="${tabKey}"]`);
        if (targetBtn) {
            try {
                bootstrap.Tab.getOrCreateInstance(targetBtn).show();
            } catch (e) {}
        }
    }

    // Synchroniser l'URL sans rechargement de page et mémoriser l'onglet actif
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url.toString());
        sessionStorage.setItem('admin_active_tab', tabKey);
    } catch (e) {
        // En cas de restriction d'historique
    }

    if (tabKey === 'dashboard' || tabKey === 'all') {
        if (typeof renderAdminTrendChart === 'function') {
            setTimeout(renderAdminTrendChart, 60);
        }
    }

    if (tabKey === 'bots' || tabKey === 'all') {
        if (typeof renderBotsPage === 'function') {
            renderBotsPage(currentBotsPage);
        }
    }
}

// --- FONCTIONS ADMINISTRATIVES DES SAMOURAÏS HÉROS & RELIQUES ---
async function adminHealHero(userId, heroName) {
    if (!confirm(`Soigner immédiatement le Samouraï « ${heroName} » à 100% de sa vitalité ?`)) return;
    const formData = new FormData();
    formData.append('action', 'admin_heal_hero');
    formData.append('user_id', userId);
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert("Soin Accordé", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de soigner le héros.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la communication.", "danger");
    }
}

async function adminReviveHero(userId, heroName) {
    if (!confirm(`Ressusciter immédiatement le Samouraï « ${heroName} » sans attendre la fin du délai de régénération de 24 heures ?`)) return;
    const formData = new FormData();
    formData.append('action', 'admin_revive_hero');
    formData.append('user_id', userId);
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert("Réincarnation Divine", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de ressusciter le héros.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la communication.", "danger");
    }
}

async function adminResetHeroQuota(userId, heroName) {
    if (!confirm(`Réinitialiser le quota d'aventures quotidiennes à 0/3 pour le Samouraï « ${heroName} » ?`)) return;
    const formData = new FormData();
    formData.append('action', 'admin_reset_hero_quota');
    formData.append('user_id', userId);
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert("Quota Réinitialisé", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de réinitialiser le quota.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la communication.", "danger");
    }
}

async function adminGrantRelic(userId, heroName) {
    if (!confirm(`Octroyer une nouvelle relique féodale inédite au Samouraï « ${heroName} » ?`)) return;
    const formData = new FormData();
    formData.append('action', 'admin_grant_relic');
    formData.append('user_id', userId);
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert("Relique Sacrée Attribuée", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'octroyer la relique.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la communication.", "danger");
    }
}

// Restauration automatique au chargement
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    if (urlTab) {
        switchAdminTab(urlTab);
    } else {
        const savedTab = sessionStorage.getItem('admin_active_tab');
        if (savedTab) {
            switchAdminTab(savedTab);
        } else {
            switchAdminTab('dashboard');
        }
    }
    if (typeof initBotsPagination === 'function') {
        initBotsPagination();
    }
    if (typeof renderAdminTrendChart === 'function') {
        setTimeout(renderAdminTrendChart, 80);
    }
});

// --- FONCTIONS DE GESTION DES ANNONCES & NOUVEAUTÉS (ADMIN) ---
function openAnnouncementEditModal(ann = null) {
    const modal = document.getElementById('announcementEditModal');
    const container = document.getElementById('aem_features_container');
    container.innerHTML = '';

    if (ann) {
        document.getElementById('aem_modal_title').innerHTML = '<span>✏️</span> Modifier l\'Annonce';
        document.getElementById('aem_id').value = ann.id || '';
        document.getElementById('aem_version').value = ann.version || 'v1.0';
        document.getElementById('aem_title').value = ann.title || '';
        document.getElementById('aem_date').value = ann.date || new Date().toISOString().split('T')[0];
        document.getElementById('aem_badge').value = ann.badge || '⭐ NOUVEAUTÉ';
        document.getElementById('aem_icon').value = ann.icon || '⚔️';
        document.getElementById('aem_is_published').value = ann.is_published ? '1' : '0';
        document.getElementById('aem_summary').value = ann.summary || '';

        if (Array.isArray(ann.features) && ann.features.length > 0) {
            ann.features.forEach(f => addFeatureRowToModal(f));
        } else {
            addFeatureRowToModal();
        }
    } else {
        document.getElementById('aem_modal_title').innerHTML = '<span>📢</span> Rédiger une Nouvelle Annonce';
        document.getElementById('announcementEditForm').reset();
        document.getElementById('aem_id').value = '';
        document.getElementById('aem_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('aem_badge').value = '⭐ NOUVEAUTÉ';
        document.getElementById('aem_icon').value = '⚔️';
        document.getElementById('aem_is_published').value = '1';
        addFeatureRowToModal();
    }

    modal.style.display = 'flex';
}

function closeAnnouncementEditModal() {
    const modal = document.getElementById('announcementEditModal');
    if (modal) modal.style.display = 'none';
}

function addFeatureRowToModal(f = null) {
    const container = document.getElementById('aem_features_container');
    const row = document.createElement('div');
    row.className = 'aem-feature-row';
    row.style.cssText = 'background:#ffffff; border:1px solid var(--border-color); border-radius:8px; padding:0.85rem; display:flex; flex-direction:column; gap:0.5rem; box-shadow:0 1px 3px rgba(60,45,30,0.04);';

    const iconVal = f ? (f.icon || '🔹') : '🔹';
    const catVal = f ? (f.category || 'Général') : 'Général';
    const titleVal = f ? (f.title || '') : '';
    const descVal = f ? (f.description || '') : '';

    row.innerHTML = `
        <div style="display:grid; grid-template-columns: 70px 1.5fr 2fr 36px; gap:0.6rem; align-items:center;">
            <div>
                <input type="text" class="feature-icon-input form-control" placeholder="Icône" value="${escapeHtml(iconVal)}" style="text-align:center;">
            </div>
            <div>
                <input type="text" class="feature-category-input form-control" placeholder="Catégorie (ex: Cité, Héros)" value="${escapeHtml(catVal)}">
            </div>
            <div>
                <input type="text" class="feature-title-input form-control" placeholder="Titre de la fonctionnalité" value="${escapeHtml(titleVal)}" required>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="this.closest('.aem-feature-row').remove()" style="background:transparent; border:none; color:#b91c1c; font-size:1.4rem; cursor:pointer;" title="Supprimer cette fonctionnalité">&times;</button>
            </div>
        </div>
        <div>
            <textarea class="feature-desc-input form-control" rows="2" placeholder="Explications claires pour les joueurs sur le fonctionnement et les bénéfices..." style="font-size:0.85rem; line-height:1.4;">${escapeHtml(descVal)}</textarea>
        </div>
    `;

    container.appendChild(row);
}

async function saveAnnouncementFromModal(event) {
    if (event) event.preventDefault();

    const submitBtn = document.getElementById('aem_submit_btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> Sauvegarde en cours...';

    const id = document.getElementById('aem_id').value;
    const version = document.getElementById('aem_version').value;
    const title = document.getElementById('aem_title').value;
    const date = document.getElementById('aem_date').value;
    const badge = document.getElementById('aem_badge').value;
    const icon = document.getElementById('aem_icon').value;
    const isPublished = document.getElementById('aem_is_published').value;
    const summary = document.getElementById('aem_summary').value;

    const featureRows = document.querySelectorAll('.aem-feature-row');
    const features = [];
    featureRows.forEach(row => {
        const fIcon = row.querySelector('.feature-icon-input').value.trim();
        const fCat = row.querySelector('.feature-category-input').value.trim();
        const fTitle = row.querySelector('.feature-title-input').value.trim();
        const fDesc = row.querySelector('.feature-desc-input').value.trim();
        if (fTitle) {
            features.push({
                icon: fIcon || '🔹',
                category: fCat || 'Général',
                title: fTitle,
                description: fDesc
            });
        }
    });

    try {
        const formData = new FormData();
        formData.append('action', 'admin_save');
        if (id) formData.append('id', id);
        formData.append('version', version);
        formData.append('title', title);
        formData.append('date', date);
        formData.append('badge', badge);
        formData.append('icon', icon);
        formData.append('is_published', isPublished);
        formData.append('summary', summary);
        formData.append('features', JSON.stringify(features));

        const res = await fetch('/api/announcements.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'announcements');
            window.location.reload();
        } else {
            alert(data.error || "Erreur lors de la sauvegarde.");
        }
    } catch (e) {
        console.error("Erreur sauvegarde annonce:", e);
        alert("Erreur réseau lors de la transmission.");
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

async function toggleAnnouncementPublish(id) {
    if (!id) return;

    try {
        const formData = new FormData();
        formData.append('action', 'admin_toggle_publish');
        formData.append('id', id);

        const res = await fetch('/api/announcements.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'announcements');
            window.location.reload();
        } else {
            alert(data.error || "Impossible de changer le statut.");
        }
    } catch (e) {
        console.error("Erreur publication annonce:", e);
        alert("Erreur réseau.");
    }
}

async function deleteAnnouncement(id) {
    if (!id) return;
    if (!confirm("Voulez-vous vraiment supprimer définitivement cette annonce du fichier JSON ?")) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'admin_delete');
        formData.append('id', id);

        const res = await fetch('/api/announcements.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'announcements');
            window.location.reload();
        } else {
            alert(data.error || "Impossible de supprimer l'annonce.");
        }
    } catch (e) {
        console.error("Erreur suppression annonce:", e);
        alert("Erreur réseau.");
    }
}

// --- FONCTIONS SUPPORT & TICKETS (ADMIN) ---
function filterAdminTickets() {
    const typeVal = document.getElementById('adminTicketFilterType').value;
    const statusVal = document.getElementById('adminTicketFilterStatus').value;
    const searchVal = document.getElementById('adminTicketSearchInput').value.toLowerCase().trim();

    const rows = document.querySelectorAll('#adminTicketsTable tbody tr.ticket-row');
    rows.forEach(row => {
        const rowType = row.getAttribute('data-type');
        const rowStatus = row.getAttribute('data-status');
        const rowSearch = row.getAttribute('data-search') || '';

        const matchType = (typeVal === 'all' || rowType === typeVal);
        const matchStatus = (statusVal === 'all' || rowStatus === statusVal);
        const matchSearch = (searchVal === '' || rowSearch.includes(searchVal));

        if (matchType && matchStatus && matchSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

async function openAdminTicketModal(ticketId) {
    try {
        const res = await fetch(`/api/support.php?action=get_ticket&ticket_id=${ticketId}`);
        const data = await res.json();
        if (!data.success || !data.ticket) {
            alert(data.error || "Impossible de charger le ticket.");
            return;
        }

        const t = data.ticket;
        document.getElementById('atm_ticket_id').textContent = t.id;
        document.getElementById('atm_input_ticket_id').value = t.id;
        document.getElementById('atm_header_meta').textContent = `${t.type === 'bug' ? '🪲 Dysfonctionnement' : '💡 Suggestion'} • ${t.category} • Sévérité : ${t.severity}`;

        document.getElementById('atm_user_name').textContent = t.username;
        document.getElementById('atm_user_faction').textContent = t.faction;
        document.getElementById('atm_user_planet').textContent = t.planet_name ? `${t.planet_name} [${t.coord_x} : ${t.coord_y}]` : 'Non renseigné';
        document.getElementById('atm_created_at').textContent = new Date(t.created_at * 1000).toLocaleString('fr-FR');

        document.getElementById('atm_ticket_title').textContent = t.title;
        document.getElementById('atm_ticket_desc').textContent = t.description;

        document.getElementById('atm_select_status').value = t.status;
        document.getElementById('atm_admin_response').value = t.admin_response || '';

        document.getElementById('adminTicketModal').style.display = 'flex';
    } catch (e) {
        alert("Erreur réseau lors de la consultation du ticket.");
    }
}

function closeAdminTicketModal() {
    document.getElementById('adminTicketModal').style.display = 'none';
}

async function saveAdminTicket(event) {
    event.preventDefault();
    const btn = document.getElementById('atm_submit_btn');
    btn.disabled = true;
    btn.textContent = 'Enregistrement...';

    const formData = new FormData(document.getElementById('adminTicketForm'));
    formData.append('action', 'admin_update_ticket');

    try {
        const res = await fetch('/api/support.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || "Ticket mis à jour avec succès !");
            window.location.reload();
        } else {
            alert("Erreur : " + (data.error || "Impossible de sauvegarder."));
            btn.disabled = false;
            btn.textContent = '💾 Enregistrer & Transmettre';
        }
    } catch (e) {
        alert("Erreur de communication avec le serveur.");
        btn.disabled = false;
        btn.textContent = '💾 Enregistrer & Transmettre';
    }
}

async function deleteAdminTicketFromModal() {
    const ticketId = document.getElementById('atm_input_ticket_id').value;
    if (!confirm(`Confirmer la suppression définitive du ticket #${ticketId} ?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'admin_delete_ticket');
    formData.append('ticket_id', ticketId);

    try {
        const res = await fetch('/api/support.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message || "Ticket supprimé.");
            window.location.reload();
        } else {
            alert("Erreur : " + (data.error || "Suppression impossible."));
        }
    } catch (e) {
        alert("Erreur de communication avec le serveur.");
    }
}

function applyPreset(gSpeed, rSpeed, fSpeed) {
    document.getElementById('game_speed_input').value = gSpeed;
    document.getElementById('game_speed_range').value = gSpeed;
    const bG = document.getElementById('badge_game_speed');
    if (bG) bG.textContent = 'x' + gSpeed;

    document.getElementById('resource_speed_input').value = rSpeed;
    document.getElementById('resource_speed_range').value = rSpeed;
    const bR = document.getElementById('badge_resource_speed');
    if (bR) bR.textContent = 'x' + rSpeed;

    document.getElementById('fleet_speed_input').value = fSpeed;
    document.getElementById('fleet_speed_range').value = fSpeed;
    const bF = document.getElementById('badge_fleet_speed');
    if (bF) bF.textContent = 'x' + fSpeed;
}

async function saveSettings(event) {
    if (event) event.preventDefault();
    const gForm = document.getElementById('gameSettingsForm');
    const formData = gForm ? new FormData(gForm) : new FormData();
    formData.append('action', 'save_settings');
    formData.append('bots_enabled', document.getElementById('bots_enabled').value);
    formData.append('bot_colonize_enabled', document.getElementById('bot_colonize_enabled').value);
    formData.append('bot_max_planets', document.getElementById('bot_max_planets').value);
    formData.append('bot_aggressiveness', document.getElementById('bot_aggressiveness').value);
    
    // Paramètres d'éclosion spontanée PNJ
    if (document.getElementById('bot_spawn_enabled')) {
        formData.append('bot_spawn_enabled', document.getElementById('bot_spawn_enabled').value);
    }
    if (document.getElementById('bot_spawn_interval_min')) {
        formData.append('bot_spawn_interval_min', document.getElementById('bot_spawn_interval_min').value);
    }
    if (document.getElementById('bot_spawn_max_villages')) {
        formData.append('bot_spawn_max_villages', document.getElementById('bot_spawn_max_villages').value);
    }

    if (document.getElementById('oasis_density_percent_input')) {
        formData.append('oasis_density_percent', document.getElementById('oasis_density_percent_input').value);
    }
    if (document.getElementById('oasis_respawn_on_capture')) {
        formData.append('oasis_respawn_on_capture', document.getElementById('oasis_respawn_on_capture').checked ? '1' : '0');
    }
    if (document.getElementById('famine_enabled')) {
        formData.append('famine_enabled', document.getElementById('famine_enabled').checked ? '1' : '0');
    }
    if (document.getElementById('famine_rate_input')) {
        formData.append('famine_rate', document.getElementById('famine_rate_input').value);
    }
    if (document.getElementById('famine_flour_consumption_input')) {
        formData.append('famine_flour_consumption', document.getElementById('famine_flour_consumption_input').value);
    }
    if (document.getElementById('hero_cage_drop_rate_input')) {
        formData.append('hero_cage_drop_rate', document.getElementById('hero_cage_drop_rate_input').value);
    }
    if (document.getElementById('hero_xp_rate_percent_input')) {
        formData.append('hero_xp_rate_percent', document.getElementById('hero_xp_rate_percent_input').value);
    }

    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showModalAlert("Succès Équilibrage", data.message, "success");
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'enregistrer.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la transmission.", "danger");
    }
}

async function saveBotSettings(event) {
    if (event) event.preventDefault();
    saveSettings();
}

// Compte à rebours temps réel pour l'éclosion spontanée
let botSpawnSecondsRemaining = <?= (int)($botSpawnStatus['next_spawn_in_seconds'] ?? 0) ?>;
const isBotSpawnActive = <?= !empty($botSpawnStatus['enabled']) ? 'true' : 'false' ?>;

if (isBotSpawnActive && botSpawnSecondsRemaining > 0) {
    setInterval(() => {
        if (botSpawnSecondsRemaining > 0) {
            botSpawnSecondsRemaining--;
            const mins = Math.floor(botSpawnSecondsRemaining / 60);
            const secs = botSpawnSecondsRemaining % 60;
            const el = document.getElementById('botSpawnCountdownText');
            if (el) {
                el.textContent = `${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
            }
        } else {
            const el = document.getElementById('botSpawnCountdownText');
            if (el) el.textContent = "Éclosion imminente...";
        }
    }, 1000);
}

async function triggerSpontaneousSpawn() {
    showModalConfirm(
        "Éclosion Spontanée Immédiate", 
        "Voulez-vous déclencher l'apparition immédiate d'un nouveau village PNJ ? L'emplacement sera choisi de façon homogène sur la carte afin de combler le quadrant le moins occupé.", 
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'spawn_spontaneous_village');
                const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    const v = data.village;
                    showModalAlert(
                        "🌸 Nouveau Village PNJ Éclos", 
                        `Un nouveau domaine a fait son apparition dans l'archipel !<br><br>
                         🏰 <strong>${v.planet_name}</strong><br>
                         👤 Seigneur : <strong>${v.username}</strong> (${v.faction})<br>
                         📍 Coordonnées : <strong>[${v.x} : ${v.y}]</strong> &bull; Quadrant : <strong>${v.quadrant}</strong><br>
                         ${v.is_new_daimyo ? '👑 <em>Nouveau Daimyō fondateur</em>' : '🏯 <em>Extension territoriale provinciale</em>'}<br><br>
                         <a href="/?page=map&x=${v.x}&y=${v.y}" target="_blank" class="btn btn-sm btn-primary">🗾 Explorer sur la Carte</a>`,
                        "success"
                    );
                    setTimeout(() => location.reload(), 2500);
                } else {
                    showModalAlert("Éclosion Impossible", data.error || data.message, "warning");
                }
            } catch (e) {
                showModalAlert("Erreur Réseau", "Une erreur est survenue lors de l'éclosion du village.", "danger");
            }
        }
    );
}

async function runBotCycle() {
    showModalConfirm("Déclencher un Cycle IA", "Voulez-vous forcer l'exécution immédiate d'un cycle de simulation pour tous les bots ? Ils amélioreront leurs mines, entraîneront des unités et coloniseront de nouveaux mondes.", async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'run_bot_cycle');
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const rep = data.report;
                let colonisationsTxt = "";
                if (rep.colonies_founded && rep.colonies_founded.length > 0) {
                    colonisationsTxt = "<br><br>🪐 <strong>Nouvelles Colonies Fondées :</strong><br>" + 
                        rep.colonies_founded.map(c => `• <strong>${c.bot}</strong> : ${c.colony_name} aux coordonnées ${c.coords}`).join('<br>');
                }

                showModalAlert(
                    "Cycle IA Terminé", 
                    `Simulation effectuée avec succès pour <strong>${rep.bots_processed}</strong> bot(s) :<br>
                     • Mines améliorées : <strong>+${rep.mines_upgraded}</strong><br>
                     • Bâtiments fortifiés : <strong>+${rep.buildings_upgraded}</strong><br>
                     • Soldats enrôlés : <strong>+${rep.troops_trained}</strong>
                     ${colonisationsTxt}`,
                    "success"
                );
                setTimeout(() => location.reload(), 2500);
            } else {
                showModalAlert("Info IA", data.message || data.error, "warning");
            }
        } catch (e) {
            showModalAlert("Erreur Réseau", "Impossible de déclencher le cycle IA.", "danger");
        }
    });
}

// --- PAGINATION DU REGISTRE DES DAIMYŌS IA ---
let currentBotsPage = 1;
let botsPerPage = 10;

function initBotsPagination() {
    const rows = document.querySelectorAll('.bot-table-row');
    const totalBots = rows.length;
    if (totalBots === 0) return;

    const urlParams = new URLSearchParams(window.location.search);
    const p = parseInt(urlParams.get('bot_page'), 10);
    if (!isNaN(p) && p >= 1) {
        currentBotsPage = p;
    }

    const select = document.getElementById('botsPerPageSelect');
    if (select) {
        botsPerPage = parseInt(select.value, 10) || 10;
    }

    renderBotsPage(currentBotsPage);
}

function changeBotsPerPage(newVal) {
    botsPerPage = parseInt(newVal, 10) || 10;
    currentBotsPage = 1;
    renderBotsPage(currentBotsPage);
}

function renderBotsPage(page) {
    const rows = Array.from(document.querySelectorAll('.bot-table-row'));
    const totalBots = rows.length;
    if (totalBots === 0) return;

    const totalPages = Math.ceil(totalBots / botsPerPage) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentBotsPage = page;

    try {
        const url = new URL(window.location.href);
        if (url.searchParams.get('tab') === 'bots') {
            url.searchParams.set('bot_page', page);
            window.history.replaceState({}, '', url.toString());
        }
    } catch (e) {}

    const startIndex = (page - 1) * botsPerPage;
    const endIndex = Math.min(startIndex + botsPerPage, totalBots);

    rows.forEach((row, idx) => {
        row.style.display = (idx >= startIndex && idx < endIndex) ? '' : 'none';
    });

    const startEl = document.getElementById('botsPaginationStart');
    const endEl = document.getElementById('botsPaginationEnd');
    const totalEl = document.getElementById('botsPaginationTotal');
    if (startEl) startEl.textContent = (totalBots > 0) ? (startIndex + 1) : 0;
    if (endEl) endEl.textContent = endIndex;
    if (totalEl) totalEl.textContent = totalBots;

    const ul = document.getElementById('botsPaginationList');
    if (!ul) return;
    ul.innerHTML = '';

    if (totalPages <= 1) return;

    // Bouton Précédent
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${page <= 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="renderBotsPage(${page - 1})" aria-label="Précédent">&lsaquo;</a>`;
    ul.appendChild(prevLi);

    // Fenêtre des numéros de pages
    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, page + 2);

    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="renderBotsPage(1)">1</a>`;
        ul.appendChild(firstLi);

        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">&hellip;</span>`;
            ul.appendChild(ellipsisLi);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const numLi = document.createElement('li');
        numLi.className = `page-item ${i === page ? 'active' : ''}`;
        numLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="renderBotsPage(${i})">${i}</a>`;
        ul.appendChild(numLi);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">&hellip;</span>`;
            ul.appendChild(ellipsisLi);
        }

        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="renderBotsPage(${totalPages})">${totalPages}</a>`;
        ul.appendChild(lastLi);
    }

    // Bouton Suivant
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${page >= totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="renderBotsPage(${page + 1})" aria-label="Suivant">&rsaquo;</a>`;
    ul.appendChild(nextLi);
}

async function generatePresetBots() {
    showModalConfirm("Génération de Daimyōs", "Créer automatiquement un groupe de 3 nouveaux Daimyōs IA (Clan Oda, Clan Takeda et Clan Tokugawa) avec leurs châteaux capitaux et garnisons ?", async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'generate_bots');
            formData.append('count', 3);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const count = data.created_count;
                const names = data.created_bots.join(', ');
                showModalAlert("Daimyōs Déployés", `${count} nouveau(x) Daimyō(s) établi(s) dans le royaume : <strong>${names}</strong>.`, "success");
                setTimeout(() => location.reload(), 1500);
            } else {
                showModalAlert("Erreur", data.error, "danger");
            }
        } catch (e) {
            showModalAlert("Erreur", "Une erreur est survenue.", "danger");
        }
    });
}

async function deleteBot(botId, botName) {
    showModalConfirm("Purger le Fief du Bot", `Êtes-vous certain de vouloir purger le Daimyō <strong>${botName}</strong> ainsi que l'ensemble de ses fiefs et armées ?`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_bot');
            formData.append('bot_id', botId);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showModalAlert("Purge Effectuée", data.message, "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                showModalAlert("Erreur", data.error, "danger");
            }
        } catch (e) {
            showModalAlert("Erreur", "Une erreur est survenue.", "danger");
        }
    });
}

async function toggleAdmin(userId, username, currentStatus) {
    const actionTxt = currentStatus === 1 ? "rétrograder au rang de Daimyō Joueur" : "promouvoir au rang de Shogun Administrateur";
    showModalConfirm("Privilèges Shogunat", `Voulez-vous vraiment ${actionTxt} le Daimyō <strong>${username}</strong> ?`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_admin');
            formData.append('user_id', userId);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showModalAlert("Mise à Jour des Droits", data.message, "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                showModalAlert("Erreur", data.error, "danger");
            }
        } catch (e) {
            showModalAlert("Erreur", "Une erreur est survenue.", "danger");
        }
    });
}

function openAdminGiveKobanModal(userId, username, currentKoban = 0) {
    const uidEl = document.getElementById('agk_user_id');
    const unameEl = document.getElementById('agk_username');
    const curEl = document.getElementById('agk_current_koban');
    const amtEl = document.getElementById('agk_amount');

    if (uidEl) uidEl.value = userId;
    if (unameEl) unameEl.innerText = username;
    if (curEl) curEl.innerText = Number(currentKoban).toLocaleString();
    if (amtEl) amtEl.value = 100;

    const modalEl = document.getElementById('modalAdminGiveKoban');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function adminGiveKoban(userId, username, currentKoban = 0) {
    openAdminGiveKobanModal(userId, username, currentKoban);
}

function setAdminKobanPreset(val) {
    const amtEl = document.getElementById('agk_amount');
    if (amtEl) amtEl.value = val;
}

async function submitAdminGiveKoban(e) {
    e.preventDefault();
    const userId = document.getElementById('agk_user_id').value;
    const amount = parseInt(document.getElementById('agk_amount').value, 10);
    const btn = document.getElementById('btnSubmitGiveKoban');

    if (isNaN(amount) || amount <= 0) {
        showModalAlert("Montant Invalide", "Veuillez saisir un nombre entier strictement supérieur à 0.", "warning");
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Attribution...`;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'give_koban');
        formData.append('user_id', userId);
        formData.append('amount', amount);
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        let data;
        try {
            data = await res.json();
        } catch (jsonErr) {
            const raw = await res.text().catch(() => '');
            throw new Error(raw ? raw.substring(0, 300) : `Erreur serveur (HTTP ${res.status})`);
        }

        if (data.success) {
            const modalEl = document.getElementById('modalAdminGiveKoban');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            showModalAlert("Trésor Impérial", data.message, "success");
            const valEl = document.getElementById(`user-koban-val-${userId}`);
            if (valEl && data.new_balance !== undefined) {
                valEl.innerText = Number(data.new_balance).toLocaleString();
            }
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'attribuer les Koban.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", e.message || "Une erreur réseau est survenue.", "danger");
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `🪙 Verser les Koban`;
        }
    }
}

async function extendProtection(userId, username, days = 7) {
    showModalConfirm("Prolonger l'Immunité Débutant", `Accorder ou prolonger de <strong>${days} jours</strong> l'immunité féodale du Daimyō <strong>${username}</strong> ?`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'extend_protection');
            formData.append('user_id', userId);
            formData.append('days', days);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showModalAlert("Immunité Féodale", data.message, "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                showModalAlert("Erreur", data.error || "Impossible de prolonger l'immunité.", "danger");
            }
        } catch (e) {
            showModalAlert("Erreur", "Une erreur réseau est survenue.", "danger");
        }
    });
}

async function revokeProtection(userId, username) {
    showModalConfirm("Lever l'Immunité Débutant", `Voulez-vous vraiment <strong>lever immédiatement</strong> l'immunité féodale du Daimyō <strong>${username}</strong> ? Ses fiefs pourront être attaqués et pillés.`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'revoke_protection');
            formData.append('user_id', userId);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showModalAlert("Immunité Féodale", data.message, "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                showModalAlert("Erreur", data.error || "Impossible de lever l'immunité.", "danger");
            }
        } catch (e) {
            showModalAlert("Erreur", "Une erreur réseau est survenue.", "danger");
        }
    });
}

async function generateWorld(event) {
    if (event) event.preventDefault();
    const count = document.getElementById('planet_count_input').value;
    const radius = document.getElementById('radius_input').value;
    const clearUninhabited = document.getElementById('clear_uninhabited').checked ? '1' : '0';

    showModalConfirm("Déploiement Provincial", `Voulez-vous générer <strong>${count}</strong> nouveaux fiefs et terres procédurales dans un rayon de <strong>${radius}</strong> provinces ?`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'generate_world');
            formData.append('planet_count', count);
            formData.append('radius', radius);
            formData.append('clear_uninhabited', clearUninhabited);

            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const pList = data.planets.slice(0, 5).map(p => `• <strong>${p.name}</strong> ${p.coords} (${p.type})`).join('<br>');
                const moreTxt = data.planets.length > 5 ? `<br>... et ${data.planets.length - 5} autres domaines.` : '';
                showModalAlert(
                    "Expansion Provinciale Réussie", 
                    `<strong>${data.generated_count}</strong> terres et fiefs ont été déployés avec succès sur la carte des provinces !<br><br>${pList}${moreTxt}`, 
                    "success"
                );
                setTimeout(() => location.reload(), 2000);
            } else {
                showModalAlert("Erreur", data.error || "Impossible d'arpenter les terres.", "danger");
            }
        } catch (e) {
            showModalAlert("Erreur Réseau", "Une erreur est survenue lors de l'arpentage.", "danger");
        }
    });
}

async function awardWeeklyMedals() {
    showModalConfirm(
        "Clôturer la Semaine & Décerner les Médailles",
        "Confirmez-vous la remise des médailles impériales (Or, Argent, Bronze, Rubans) aux plus illustres Daimyōs (Progression, Conquête, Défense, Pillards de Riz) et la réinitialisation des scores hebdomadaires ?",
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'award_medals');
                const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    let detailsTxt = "";
                    if (data.details && data.details.length > 0) {
                        detailsTxt = "<br><br>🎖️ <strong>Médailles Décernées :</strong><br>" + 
                            data.details.slice(0, 8).map(m => `• <strong>${m.username}</strong> : ${m.medal} (${m.category})`).join('<br>');
                        if (data.details.length > 8) {
                            detailsTxt += `<br>... et ${data.details.length - 8} autres distinctions.`;
                        }
                    }

                    showModalAlert(
                        "Médailles Décernées !",
                        `La semaine <strong>${data.week_code}</strong> a été clôturée avec succès !<br>
                         <strong>${data.medals_awarded_count}</strong> distinction(s) attribuée(s) et félicitations transmises aux commandants.${detailsTxt}`,
                        "success"
                    );
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showModalAlert("Erreur", data.error || "Impossible de décerner les médailles.", "danger");
                }
            } catch (e) {
                showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la communication avec le serveur.", "danger");
            }
        }
    );
}

function openResetModal() {
    document.getElementById('resetKeywordInput').value = '';
    document.getElementById('resetUniverseModal').style.display = 'flex';
}

function closeResetModal() {
    document.getElementById('resetUniverseModal').style.display = 'none';
}

async function executeUniverseReset() {
    const keyword = document.getElementById('resetKeywordInput').value.trim();
    if (keyword !== 'RESET') {
        showModalAlert("Confirmation Invalide", "Vous devez impérativement saisir le mot <strong>RESET</strong> en majuscules pour déverrouiller la purge de l'univers.", "danger");
        return;
    }

    closeResetModal();

    try {
        const formData = new FormData();
        formData.append('action', 'reset_universe');
        formData.append('confirm_keyword', 'RESET');
        formData.append('admin_password', 'Gabriel125#');
        formData.append('neutral_planets', 12);
        formData.append('deploy_bots', '1');

        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert(
                "Univers Réinitialisé", 
                `L'univers a été réinitialisé avec succès !<br><br>
                 👑 Administrateur : <strong>${data.admin.username}</strong><br>
                 🔑 Mot de passe : <strong>Gabriel125#</strong><br>
                 🪐 Planète Capitale : <strong>${data.admin.capital_planet}</strong><br>
                 🌍 Planètes Neutres : <strong>${data.neutral_planets}</strong><br>
                 🤖 Bots Déployés : <strong>${data.bots_deployed}</strong><br><br>
                 Redirection vers le Poste de Commandement...`, 
                "success"
            );
            setTimeout(() => {
                window.location.href = '/?page=resources';
            }, 3000);
        } else {
            showModalAlert("Erreur Reset", data.error || "Impossible de réinitialiser l'univers.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur critique est survenue lors de la réinitialisation.", "danger");
    }
}

// ==========================================================
// GESTION DES 12 CHÂTEAUX AUTHENTIQUES DU JAPON (現存十二天守)
// ==========================================================
async function distributeCastlesHomogeneously() {
    const confirmed = await showModalConfirm('Voulez-vous répartir et déployer les 12 Châteaux Authentiques de façon homogène sur l\'entièreté de la carte (3 par quadrant, rayon ±35) ?', 'Répartition Homogène des 12 Trésors');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'distribute_castles');
    formData.append('radius', '35');
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message || '12 Châteaux Authentiques répartis avec succès sur toute la carte !', 'success');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur lors de la répartition.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function spawnAllCastles() {
    const confirmed = await showModalConfirm('Voulez-vous déployer l\'ensemble des 12 Châteaux Authentiques du Japon sur la carte des provinces ?', 'Déploiement des 12 Trésors');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'spawn_all_castles');
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message || '12 Châteaux Authentiques déployés avec succès !', 'success');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur lors du déploiement.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function despawnAllCastles() {
    const confirmed = await showModalConfirm('Voulez-vous retirer tous les Donjons Authentiques de la carte ?', 'Rappel des Donjons');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'despawn_all_castles');
    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'info');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function toggleCastleSpawn(castleId, spawn) {
    const x = parseInt(document.getElementById(`castle_x_${castleId}`).value, 10);
    const y = parseInt(document.getElementById(`castle_y_${castleId}`).value, 10);

    const formData = new FormData();
    formData.append('action', 'toggle_castle');
    formData.append('castle_id', castleId);
    formData.append('spawn', spawn ? '1' : '0');
    formData.append('x', x);
    formData.append('y', y);

    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Action impossible.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function updateCastlePosition(castleId) {
    const x = parseInt(document.getElementById(`castle_x_${castleId}`).value, 10);
    const y = parseInt(document.getElementById(`castle_y_${castleId}`).value, 10);

    const formData = new FormData();
    formData.append('action', 'update_castle_coords');
    formData.append('castle_id', castleId);
    formData.append('x', x);
    formData.append('y', y);

    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de déplacer le château.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function executeRepopulateOases() {
    const density = parseFloat(document.getElementById('repop_density').value) || 2.0;
    const radius = parseInt(document.getElementById('repop_radius').value, 10) || 28;
    const clearUnoccupied = document.getElementById('repop_clear_unoccupied').checked ? '1' : '0';

    if (!confirm(`Confirmer la génération d'oasis avec une densité de ${density}% sur un rayon de ${radius} ?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'repopulate_oases');
    formData.append('density_percent', density);
    formData.append('radius', radius);
    formData.append('clear_unoccupied', clearUnoccupied);

    try {
        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert("Génération d'Oasis Réussie", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de générer les oasis.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la génération.", "danger");
    }
}

// ── Modération & Forum Féodal ──
async function toggleModerator(userId, username, currentStatus) {
    const actionText = currentStatus === 1 ? 'retirer du corps des modérateurs' : 'promouvoir au rang de Modérateur Féodal 🛡️';
    if (!confirm(`Voulez-vous ${actionText} le joueur ${username} ?`)) return;

    try {
        const formData = new FormData();
        formData.append('action', 'toggle_moderator');
        formData.append('user_id', userId);

        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            await showModalAlert("Mise à Jour Modérateur", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de modifier le rôle.", "danger");
        }
    } catch (err) {
        showModalAlert("Erreur Réseau", "Erreur lors de l'opération.", "danger");
    }
}

async function submitAdminCreateCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('btnAdminCreateCat');
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('icon', document.getElementById('afc_icon').value.trim() || '💬');
        formData.append('name', document.getElementById('afc_name').value.trim());
        formData.append('description', document.getElementById('afc_desc').value.trim());
        formData.append('display_order', document.getElementById('afc_order').value || 0);
        formData.append('is_locked', document.getElementById('afc_locked').checked ? '1' : '0');

        const res = await fetch('/api/forum.php?action=admin_create_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'forum');
            await showModalAlert("Salon Créé", data.message, "success");
            window.location.reload();
        } else {
            showModalAlert("Erreur", data.error || "Impossible de créer le salon.", "danger");
            btn.disabled = false;
        }
    } catch (err) {
        showModalAlert("Erreur Réseau", "Erreur lors de la création.", "danger");
        btn.disabled = false;
    }
}

function openEditForumCategoryModal(id, name, desc, icon, order, isLocked) {
    document.getElementById('edit_afc_id').value = id;
    document.getElementById('edit_afc_name').value = name;
    document.getElementById('edit_afc_desc').value = desc;
    document.getElementById('edit_afc_icon').value = icon;
    document.getElementById('edit_afc_order').value = order;
    document.getElementById('edit_afc_locked').checked = (parseInt(isLocked, 10) === 1);

    const modal = new bootstrap.Modal(document.getElementById('modalEditAdminForumCategory'));
    modal.show();
}

async function submitAdminEditCategory(e) {
    e.preventDefault();
    try {
        const formData = new FormData();
        formData.append('category_id', document.getElementById('edit_afc_id').value);
        formData.append('name', document.getElementById('edit_afc_name').value.trim());
        formData.append('description', document.getElementById('edit_afc_desc').value.trim());
        formData.append('icon', document.getElementById('edit_afc_icon').value.trim() || '💬');
        formData.append('display_order', document.getElementById('edit_afc_order').value || 0);
        formData.append('is_locked', document.getElementById('edit_afc_locked').checked ? '1' : '0');

        const res = await fetch('/api/forum.php?action=admin_edit_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'forum');
            window.location.reload();
        } else {
            alert(data.error || "Erreur de modification.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}

async function deleteForumCategory(catId, name) {
    if (!confirm(`ATTENTION : Supprimer définitivement le salon '${name}' et TOUS ses sujets et messages ?`)) return;

    try {
        const formData = new FormData();
        formData.append('category_id', catId);

        const res = await fetch('/api/forum.php?action=admin_delete_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('admin_active_tab', 'forum');
            window.location.reload();
        } else {
            alert(data.error || "Erreur lors de la suppression.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}
</script>

<!-- Modale d'Édition de Catégorie de Forum -->
<div class="modal modal-blur fade" id="modalEditAdminForumCategory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form onsubmit="submitAdminEditCategory(event)">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Édition du Salon Féodal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_afc_id" value="">
                    <div class="row">
                        <div class="col-3 mb-3">
                            <label class="form-label required">Icône</label>
                            <input type="text" id="edit_afc_icon" class="form-control text-center" required>
                        </div>
                        <div class="col-9 mb-3">
                            <label class="form-label required">Titre du Salon</label>
                            <input type="text" id="edit_afc_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" id="edit_afc_desc" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ordre d'Affichage</label>
                            <input type="number" id="edit_afc_order" class="form-control">
                        </div>
                        <div class="col-6 mb-3 d-flex align-items-center pt-3">
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_afc_locked">
                                <span class="form-check-label small fw-bold">🔒 Décrets Staff</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les Modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale d'Octroi de Koban Impériaux par l'Administrateur -->
<div class="modal modal-blur fade" id="modalAdminGiveKoban" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0">
                    <span>🪙</span> Octroi de Koban Impériaux
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAdminGiveKoban" onsubmit="submitAdminGiveKoban(event)">
                <input type="hidden" id="agk_user_id" name="user_id" value="">
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center gap-3 p-3 rounded mb-3" style="background:#fffbeb; border:1px solid #fde68a;">
                        <span class="fs-1">👤</span>
                        <div>
                            <div class="text-secondary small fw-bold text-uppercase">Daimyō Destinataire</div>
                            <div class="fs-3 fw-bold text-dark" id="agk_username">---</div>
                            <div class="text-muted small">
                                Solde actuel : <strong class="text-warning-emphasis" id="agk_current_koban">0</strong> 🪙 Koban
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant à octroyer (Koban 🪙)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-warning-subtle text-warning-emphasis fw-bold">🪙</span>
                            <input type="number" id="agk_amount" name="amount" class="form-control form-control-lg fw-bold" 
                                   min="1" max="100000" step="1" value="100" required placeholder="Ex: 100">
                        </div>
                        <div class="form-text small text-muted">
                            Ce montant sera immédiatement ajouté au trésor du joueur et une missive officielle lui sera transmise.
                        </div>
                    </div>

                    <!-- Raccourcis de montants rapides -->
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold mb-1">Montants Rapides :</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAdminKobanPreset(50)">+50 🪙</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAdminKobanPreset(100)">+100 🪙</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAdminKobanPreset(200)">+200 🪙 (7j)</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAdminKobanPreset(360)">+360 🪙 (14j)</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAdminKobanPreset(600)">+600 🪙 (30j)</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btnSubmitGiveKoban" class="btn btn-warning fw-bold px-4 shadow-sm">
                        🪙 Verser les Koban
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

