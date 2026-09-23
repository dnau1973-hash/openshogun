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

$botEngine = new BotEngine();
$castleEngine = new CastleEngine();
$oasisEngine = new OasisEngine();
$supportEngine = new SupportEngine();
$updateEngine = new UpdateEngine();
$heroEngine = new HeroEngine();
$localGitInfo = $updateEngine->getLocalInfo();
$db = Database::getConnection();

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

// Liste des joueurs humains
$humanUsers = $db->query("
    SELECT u.id, u.username, u.email, u.faction, u.points, u.is_admin, u.created_at,
           COUNT(p.id) as colony_count
    FROM users u
    LEFT JOIN planets p ON p.user_id = u.id
    WHERE u.is_bot = 0
    GROUP BY u.id
    ORDER BY u.id ASC
")->fetchAll();

// Gestion des onglets d'administration du Shogunat
$allowedTabs = ['game', 'heroes', 'bots', 'users', 'oases', 'castles', 'world', 'medals', 'support', 'announcements', 'pedagogy', 'updates', 'maintenance', 'all'];
$currentTab = $_GET['tab'] ?? 'game';
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'game';
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
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('game')" title="Configurer les constantes & vitesses">
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

        <!-- Fiefs & Domaines -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm h-100" style="cursor: pointer;" onclick="switchAdminTab('world')" title="Arpentage et expansion provinciale">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar rounded bg-success-lt text-success" style="font-size:1.3rem;">🗾</span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Fiefs &amp; Domaines</div>
                            <div class="text-success font-weight-bold" style="font-size:1.25rem;">
                                <?= $totalColonies ?> / <?= $totalPlanets ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        Châteaux sous contrôle de clans
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

    <!-- Barre de Navigation par Onglets Tabler.io -->
    <div class="card mb-3">
        <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs flex-wrap" id="adminTabsNav">
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'game') ? 'active' : '' ?>" data-tab="game" onclick="switchAdminTab('game')">
                        <span class="me-1">⚡</span> Vitesses &amp; Jeu
                        <span class="badge bg-secondary-lt ms-2">x<?= (int)($settings['game_speed'] ?? 5) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'heroes') ? 'active' : '' ?>" data-tab="heroes" onclick="switchAdminTab('heroes')">
                        <span class="me-1">🥋</span> Samouraïs &amp; Reliques
                        <span class="badge bg-purple-lt ms-2"><?= $totalHeroes ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'bots') ? 'active' : '' ?>" data-tab="bots" onclick="switchAdminTab('bots')">
                        <span class="me-1">🤖</span> Clans IA
                        <span class="badge bg-indigo-lt ms-2"><?= $totalBots ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'users') ? 'active' : '' ?>" data-tab="users" onclick="switchAdminTab('users')">
                        <span class="me-1">👥</span> Joueurs
                        <span class="badge bg-warning-lt ms-2"><?= $totalUsers ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'oases') ? 'active' : '' ?>" data-tab="oases" onclick="switchAdminTab('oases')">
                        <span class="me-1">🌿</span> Oasis &amp; Faune
                        <span class="badge bg-green-lt ms-2"><?= $oasisStats['total_oases'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'castles') ? 'active' : '' ?>" data-tab="castles" onclick="switchAdminTab('castles')">
                        <span class="me-1">🏯</span> 12 Donjons
                        <span class="badge bg-orange-lt ms-2"><?= $spawnedCastlesCount ?>/12</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'world') ? 'active' : '' ?>" data-tab="world" onclick="switchAdminTab('world')">
                        <span class="me-1">🗾</span> Provinces &amp; Terres
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'medals') ? 'active' : '' ?>" data-tab="medals" onclick="switchAdminTab('medals')">
                        <span class="me-1">🎖️</span> Médailles
                        <span class="badge bg-yellow-lt ms-2"><?= htmlspecialchars($currentWeekCode) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'support') ? 'active' : '' ?>" data-tab="support" onclick="switchAdminTab('support')">
                        <span class="me-1">📮</span> Support &amp; Bugs
                        <?php if ($supportStats['count_pending'] > 0): ?>
                            <span class="badge bg-danger text-white ms-2">⚠️ <?= $supportStats['count_pending'] ?></span>
                        <?php else: ?>
                            <span class="badge bg-azure-lt ms-2"><?= $supportStats['total'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'announcements') ? 'active' : '' ?>" data-tab="announcements" onclick="switchAdminTab('announcements')">
                        <span class="me-1">📢</span> Nouveautés
                        <span class="badge bg-pink-lt ms-2"><?= $publishedAnnouncementsCount ?>/<?= $totalAnnouncementsCount ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'pedagogy') ? 'active' : '' ?>" data-tab="pedagogy" onclick="switchAdminTab('pedagogy')">
                        <span class="me-1">🎓</span> Atelier Pédagogique
                        <span class="badge bg-cyan-lt ms-2">Père-Fils</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'updates') ? 'active' : '' ?>" data-tab="updates" onclick="switchAdminTab('updates')">
                        <span class="me-1">🔄</span> GitHub Sync
                        <span class="badge bg-teal-lt ms-2"><?= htmlspecialchars($localGitInfo['short_sha']) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'maintenance') ? 'active' : '' ?>" data-tab="maintenance" onclick="switchAdminTab('maintenance')">
                        <span class="me-1">⚠️</span> Maintenance
                    </a>
                </li>
                <li class="nav-item ms-auto">
                    <a href="javascript:void(0)" class="nav-link admin-tab-btn <?= ($currentTab === 'all') ? 'active' : '' ?>" data-tab="all" onclick="switchAdminTab('all')" title="Afficher tous les onglets en continu">
                        <span class="me-1">📚</span> Tout Dérouler
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Section 1 : Variables de Jeu & Vitesses -->
    <div class="admin-tab-pane" id="admin-tab-pane-game" data-tab="game" style="display: <?= $isPaneVisible('game') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(220, 38, 38, 0.2);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: #dc2626; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>⚡</span> Constantes & Équilibrage des Vitesses de Jeu
                </h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="applyPreset(1, 1, 1)">1x Classique</button>
                    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="applyPreset(5, 5, 5)">5x Standard</button>
                    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="applyPreset(20, 20, 10)">20x Éclair</button>
                    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="applyPreset(50, 50, 20)">50x Hyper</button>
                </div>
            </div>
            <div class="card-body">
                <form id="gameSettingsForm" onsubmit="saveSettings(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                🏗️ Vitesse Globale (Bâtiments, Dojos, Chantiers Féodaux)
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="game_speed_range" min="1" max="100" value="<?= (int)($settings['game_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('game_speed_input').value = this.value">
                                <input type="number" id="game_speed_input" name="game_speed" min="1" max="100" 
                                       value="<?= (int)($settings['game_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('game_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Divise le temps nécessaire aux chantiers, Tenshu, académies et entraînements.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                ⛏️ Vitesse de Production des Ressources (Rizières, Scieries &amp; Carrières)
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="resource_speed_range" min="1" max="100" value="<?= (int)($settings['resource_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('resource_speed_input').value = this.value">
                                <input type="number" id="resource_speed_input" name="resource_speed" min="1" max="100" 
                                       value="<?= (int)($settings['resource_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('resource_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Multiplie la production horaire de Bois de Cèdre 🪵, Pierre 🪨 et Koku de Riz 🌾.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                🐎 Vitesse de Marche des Troupes &amp; Expéditions Féodales
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="fleet_speed_range" min="1" max="50" value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('fleet_speed_input').value = this.value">
                                <input type="number" id="fleet_speed_input" name="fleet_speed" min="1" max="50" 
                                       value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('fleet_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Accélère les trajets des régiments pour les assauts, convois de tributs et fondations de fiefs.</small>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4ade80;">
                                🌿 Couverture / Densité des Oasis sur la Carte (%)
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="oasis_density_percent_range" min="0.5" max="15.0" step="0.5" value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('oasis_density_percent_input').value = this.value">
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <input type="number" id="oasis_density_percent_input" name="oasis_density_percent" min="0.5" max="20" step="0.5" 
                                           value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" class="form-control" style="width: 70px; text-align: center;"
                                           oninput="document.getElementById('oasis_density_percent_range').value = this.value">
                                    <span style="color: #94a3b8; font-weight: 700;">%</span>
                                </div>
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Définit la proportion de tuiles réservées aux oasis naturelles par rapport à la superficie totale de la carte.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4ade80;">
                                🔄 Réapparition d'une Oasis après Capture
                            </label>
                            <div style="margin-top: 0.5rem;">
                                <label style="display: inline-flex; align-items: center; gap: 0.6rem; cursor: pointer; background: rgba(0,0,0,0.3); padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
                                    <input type="checkbox" id="oasis_respawn_on_capture" name="oasis_respawn_on_capture" value="1" 
                                           <?= !empty($settings['oasis_respawn_on_capture']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #16a34a;">
                                    <span style="font-size: 0.85rem; color: #fff; font-weight: 600;">
                                        Faire éclore une nouvelle oasis sauvage lors de l'annexion d'une oasis par un joueur
                                    </span>
                                </label>
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Maintient le réservoir d'oasis sauvages et de faune active pour les autres daimyōs du royaume.</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700;">
                            💾 Enregistrer les Constantes de Jeu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Section 2 NOUVELLE : Samouraïs Héros & Reliques Légendaires de l'Archipel -->
    <div class="admin-tab-pane" id="admin-tab-pane-heroes" data-tab="heroes" style="display: <?= $isPaneVisible('heroes') ? 'block' : 'none' ?>;">
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
    <div class="admin-tab-pane" id="admin-tab-pane-bots" data-tab="bots" style="display: <?= $isPaneVisible('bots') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(168, 85, 247, 0.3);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: #c084fc; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🤖</span> Paramétrage de l'IA & Colonisation des Bots (PNJ)
                </h3>
                <span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7;">
                    <?= count($botsList) ?> Bots Enregistrés
                </span>
            </div>
            <div class="card-body">
                <form id="botSettingsForm" onsubmit="saveBotSettings(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Interrupteur Général de l'IA
                            </label>
                            <select name="bots_enabled" class="form-control" id="bots_enabled">
                                <option value="1" <?= !empty($settings['bots_enabled']) ? 'selected' : '' ?>>🟢 IA Active (Cycles opérationnels)</option>
                                <option value="0" <?= empty($settings['bots_enabled']) ? 'selected' : '' ?>>🔴 IA En Veille (Bots figés)</option>
                            </select>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Permet aux bots d'évoluer, miner et produire des armées.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                🏯 Fondation Automatique de Nouveaux Fiefs
                            </label>
                            <select name="bot_colonize_enabled" class="form-control" id="bot_colonize_enabled">
                                <option value="1" <?= !empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🟢 Autorisée (Les clans IA conquièrent de nouveaux fiefs)</option>
                                <option value="0" <?= empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🔴 Désactivée (Domaine seigneurial initial uniquement)</option>
                            </select>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Déclenche l'expansion territoriale des clans IA vers de nouvelles coordonnées de la carte.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Nombre Max de Fiefs par Clan IA
                            </label>
                            <input type="number" name="bot_max_planets" id="bot_max_planets" min="1" max="10" 
                                   value="<?= (int)($settings['bot_max_planets'] ?? 3) ?>" class="form-control">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Plafond d'expansion territoriale par Daimyō IA (Fief Principal + Domaines vassaux).</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Profil d'Agressivité
                            </label>
                            <select name="bot_aggressiveness" class="form-control" id="bot_aggressiveness">
                                <option value="peaceful" <?= (($settings['bot_aggressiveness'] ?? '') === 'peaceful') ? 'selected' : '' ?>>🕊️ Pacifique (Focus Mines & Défense)</option>
                                <option value="moderate" <?= (($settings['bot_aggressiveness'] ?? 'moderate') === 'moderate') ? 'selected' : '' ?>>⚖️ Modéré (Équilibré Éco & Troupes)</option>
                                <option value="aggressive" <?= (($settings['bot_aggressiveness'] ?? '') === 'aggressive') ? 'selected' : '' ?>>⚔️ Belliqueux (Armées Lourdes & Raids)</option>
                            </select>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Influence le ratio de recrutement et d'armement des PNJ.</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700;">
                            💾 Sauvegarder la Directive IA
                        </button>
                    </div>
                </form>

                <hr style="border-color: rgba(255,255,255,0.08); margin: 1.5rem 0;">

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
                    <div class="table-responsive">
                        <table class="table table-vcenter table-nowrap card-table table-hover">
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
                            <tbody>
                                <?php foreach ($botsList as $bot): ?>
                                    <?php $fInfo = FACTIONS[$bot['faction']] ?? FACTIONS['terran']; ?>
                                    <tr>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Section 4 : Gestion des Daimyōs Joueurs -->
    <div class="admin-tab-pane" id="admin-tab-pane-users" data-tab="users" style="display: <?= $isPaneVisible('users') ? 'block' : 'none' ?>;">
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
                            <th class="text-center">Rang Shogunal</th>
                            <th class="text-end">Commandes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($humanUsers as $hUser): ?>
                            <?php $hfInfo = FACTIONS[$hUser['faction']] ?? FACTIONS['terran']; ?>
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
                                <td class="text-center">
                                    <?php if ((int)$hUser['is_admin'] === 1): ?>
                                        <span class="badge bg-danger text-white">
                                            ⭐ ADMINISTRATEUR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">
                                            Daimyō Joueur
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((int)$hUser['id'] !== (int)Auth::id()): ?>
                                        <button onclick="toggleAdmin(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', <?= (int)$hUser['is_admin'] ?>)"
                                                class="btn btn-sm btn-outline-secondary">
                                            <?= ((int)$hUser['is_admin'] === 1) ? 'Rétrograder Joueur' : 'Promouvoir Admin' ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-secondary small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 4 : 🎖️ Tableau d'Honneur & Clôture Hebdomadaire des Médailles -->
    <div class="admin-tab-pane" id="admin-tab-pane-medals" data-tab="medals" style="display: <?= $isPaneVisible('medals') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(234, 179, 8, 0.3); background: rgba(18, 16, 25, 0.85);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(234, 179, 8, 0.2);">
                <h3 style="color: #facc15; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🎖️</span> Tableau d'Honneur & Clôture Hebdomadaire des Médailles
                </h3>
                <span style="font-size: 0.8rem; background: rgba(234, 179, 8, 0.15); color: #facc15; padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid rgba(234, 179, 8, 0.3);">
                    Semaine en cours : <strong><?= htmlspecialchars($currentWeekCode) ?></strong>
                </span>
            </div>
            <div class="card-body">
                <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Le système de Tableau d'Honneur attribue automatiquement les médailles de prestige (🥇 Or, 🥈 Argent, 🥉 Bronze et 🎖️ Rubans Top 10) aux commandants les plus méritants dans les 4 catégories reines :
                    <strong>Progression d'Empire</strong>, <strong>Attaquant de la Semaine</strong>, <strong>Défenseur Héroïque</strong> et <strong>Seigneur du Pillage</strong>.<br>
                    Des dépêches officielles de félicitations sont transmises aux lauréats, et leurs profils sont décorés à vie.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Médailles Historiques Décernées</div>
                        <div style="font-size: 1.6rem; font-weight: 800; color: #facc15; margin-top: 0.25rem;"><?= $totalMedals ?> 🎖️</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Cycle de Remise</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626; margin-top: 0.5rem;">Hebdomadaire (7j)</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); text-align: center;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Affichage Public</div>
                        <div style="margin-top: 0.4rem;">
                            <a href="?page=ranking&tab=honor" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                👀 Consulter le Tableau
                            </a>
                        </div>
                    </div>
                </div>

                <div style="background: rgba(234, 179, 8, 0.08); border: 1px dashed rgba(234, 179, 8, 0.3); padding: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">Clôturer la Semaine en Cours</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">
                            Attribue les médailles de la semaine <strong><?= htmlspecialchars($currentWeekCode) ?></strong>, notifie les commandants et réinitialise les scores d'attaque/défense/pillage.
                        </div>
                    </div>
                    <button type="button" onclick="awardWeeklyMedals()" class="btn" style="background: linear-gradient(135deg, #d97706, #f59e0b); color: #000; font-weight: 800; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);">
                        🎖️ Clôturer & Décerner les Médailles
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 5 : 🗾 Arpenteur du Shogunat & Expansion des Provinces -->
    <div class="admin-tab-pane" id="admin-tab-pane-world" data-tab="world" style="display: <?= $isPaneVisible('world') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(52, 211, 153, 0.3);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: #34d399; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🗾</span> Arpenteur du Shogunat & Expansion des Provinces
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Création procédurale de fiefs, vallées et sanctuaires</span>
            </div>
            <div class="card-body">
                <form id="worldGenForm" onsubmit="generateWorld(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Nombre de Terres & Fiefs à Déployer
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="planet_count_range" min="1" max="50" value="12" style="flex: 1;" 
                                       oninput="document.getElementById('planet_count_input').value = this.value">
                                <input type="number" id="planet_count_input" name="planet_count" min="1" max="50" value="12" 
                                       class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('planet_count_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Terres libres prêtes à être explorées, pillées ou inféodées.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Rayon de Dispersion Géographique
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="radius_range" min="5" max="35" value="12" style="flex: 1;" 
                                       oninput="document.getElementById('radius_input').value = this.value">
                                <input type="number" id="radius_input" name="radius" min="5" max="35" value="12" 
                                       class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('radius_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Étendue des provinces $[-R, +R]$ autour de la capitale impériale.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Gestion des Terres Inoccupées
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.85rem; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" name="clear_uninhabited" value="1" id="clear_uninhabited">
                                Purger les terres libres inoccupées existantes avant génération
                            </label>
                            <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Ne supprime jamais les fiefs possédés par un Daimyō ou un bot.</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700; background: linear-gradient(135deg, #059669, #10b981); border-color: #34d399;">
                            🗾 Déployer les Fiefs dans les Provinces
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Section 6 : 🏯 Sanctuaires des 12 Donjons Authentiques du Japon (現存十二天守) -->
    <div class="admin-tab-pane" id="admin-tab-pane-castles" data-tab="castles" style="display: <?= $isPaneVisible('castles') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(245, 158, 11, 0.4); background: rgba(17, 18, 24, 0.95);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h3 style="color: #fbbf24; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                        <span>🏯</span> Les 12 Donjons Authentiques du Japon (現存十二天守) &bull; Enjeux de la Bataille Finale
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Forteresses historiques d'époque Sengoku-Edo préservées. Déployez-les sur la carte des provinces pour déclencher les enjeux de la conquête suprême.
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                        <?= $spawnedCastlesCount ?> / 12 Déployés
                    </span>
                    <button type="button" class="btn btn-warning" onclick="spawnAllCastles()" style="background: #f59e0b; color: #18181b; font-weight: 800; font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                        ⚡ Déployer les 12 Donjons
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="despawnAllCastles()" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; color: #f87171; border-color: rgba(239, 68, 68, 0.4);">
                        🛑 Retirer Tous
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid rgba(245, 158, 11, 0.3); color: #fbbf24; text-align: left;">
                                <th style="padding: 0.6rem;">#</th>
                                <th style="padding: 0.6rem;">Donjon & Kanji</th>
                                <th style="padding: 0.6rem;">Province & Bâtisseur</th>
                                <th style="padding: 0.6rem; text-align: center;">Statut Carte</th>
                                <th style="padding: 0.6rem; text-align: center;">Coordonnées [X : Y]</th>
                                <th style="padding: 0.6rem; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($authenticCastles as $idx => $c): ?>
                                <?php 
                                    $isSpawned = (int)$c['is_spawned'] === 1;
                                    $curX = $c['coord_x'] ?? $c['default_x'];
                                    $curY = $c['coord_y'] ?? $c['default_y'];
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.06); background: <?= $isSpawned ? 'rgba(245, 158, 11, 0.04)' : 'transparent' ?>;">
                                    <td style="padding: 0.6rem; font-weight: 700; color: #94a3b8;"><?= $c['id'] ?></td>
                                    <td style="padding: 0.6rem;">
                                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;">
                                            🏯 <?= htmlspecialchars($c['name']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #fbbf24; font-family: serif;">
                                            <?= htmlspecialchars($c['kanji']) ?> &bull; <?= htmlspecialchars($c['japanese_name']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 0.6rem;">
                                        <div style="color: #e2e8f0; font-size: 0.8rem;"><?= htmlspecialchars($c['province']) ?></div>
                                        <div style="color: var(--text-muted); font-size: 0.72rem;"><?= htmlspecialchars($c['historical_builder']) ?> (<?= htmlspecialchars($c['construction_year']) ?>)</div>
                                    </td>
                                    <td style="padding: 0.6rem; text-align: center;">
                                        <?php if ($isSpawned): ?>
                                            <span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; padding: 0.2rem 0.5rem; font-size: 0.75rem; font-weight: 700;">
                                                🟢 En Jeu [<?= $curX ?> : <?= $curY ?>]
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid #64748b; padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                                ⚪ En Réserve
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.6rem; text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                            <input type="number" id="castle_x_<?= $c['id'] ?>" value="<?= $curX ?>" style="width: 50px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); color: #fff; padding: 0.2rem 0.4rem; border-radius: 4px; text-align: center; font-size: 0.8rem;">
                                            <span style="color: var(--text-muted);">:</span>
                                            <input type="number" id="castle_y_<?= $c['id'] ?>" value="<?= $curY ?>" style="width: 50px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); color: #fff; padding: 0.2rem 0.4rem; border-radius: 4px; text-align: center; font-size: 0.8rem;">
                                            <button type="button" onclick="updateCastlePosition(<?= $c['id'] ?>)" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Enregistrer les coordonnées">
                                                📍
                                            </button>
                                        </div>
                                    </td>
                                    <td style="padding: 0.6rem; text-align: right; white-space: nowrap;">
                                        <div style="display: flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">
                                            <button type="button" onclick="toggleCastleSpawn(<?= $c['id'] ?>, <?= $isSpawned ? 0 : 1 ?>)" 
                                                    class="btn <?= $isSpawned ? 'btn-danger' : 'btn-primary' ?>" 
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.6rem;">
                                                <?= $isSpawned ? '🔴 Retirer' : '🟢 Poser' ?>
                                            </button>
                                            <a href="/?page=castle&code=<?= $c['code'] ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;" title="Consulter la fiche historique">
                                                📜 Fiche
                                            </a>
                                            <?php if ($isSpawned): ?>
                                                <a href="/?page=map&x=<?= $curX ?>&y=<?= $curY ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;" title="Voir sur la carte">
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
        </div>
    </div>

    <!-- Section 6b : 🌿 Arpentage des Oasis Naturelles & Faune Sauvage (Style Travian) -->
    <div class="admin-tab-pane" id="admin-tab-pane-oases" data-tab="oases" style="display: <?= $isPaneVisible('oases') ? 'block' : 'none' ?>;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(34, 197, 94, 0.4); background: rgba(17, 24, 20, 0.95);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="color: #4ade80; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🌿</span> Écosystème des Oasis Naturelles & Faune Sauvage (Style Travian)
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Gestion du réseau d'oasis sauvages, de la faune hostile (Sangliers, Loups, Ours) et de la réapparition continue après capture.
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                    <?= $oasisStats['total_oases'] ?> Oasis Totales
                </span>
                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                    <?= $oasisStats['captured_oases'] ?> Fiefs Annexés
                </span>
                <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                    <?= $oasisStats['wild_oases'] ?> Sauvages Libres
                </span>
                <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                    🐗 <?= number_format($oasisStats['total_wild_animals']) ?> Bêtes Sauvages
                </span>
            </div>
        </div>

        <div class="card-body">
            <!-- Panneau de contrôle et rééquilibrage de densité -->
            <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(34,197,94,0.3); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="color: #86efac; font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚙️</span> Générateur & Rééquilibrage par Pourcentage de Couverture
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; color: #e2e8f0; font-weight: 700; margin-bottom: 0.35rem;">
                            Pourcentage de Densité Cible (%) :
                        </label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="number" id="repop_density" min="0.5" max="20.0" step="0.5" 
                                   value="<?= (float)($settings['oasis_density_percent'] ?? 2.0) ?>" class="form-control" style="width: 90px; text-align: center;">
                            <span style="font-size: 0.85rem; color: #94a3b8;">% (ex: 2.0% &approx; 65 oasis)</span>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.8rem; color: #e2e8f0; font-weight: 700; margin-bottom: 0.35rem;">
                            Rayon de Couverture Carte :
                        </label>
                        <input type="number" id="repop_radius" min="10" max="50" value="28" class="form-control" style="width: 90px; text-align: center;">
                    </div>

                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8rem; color: #cbd5e1; margin-bottom: 0.5rem;">
                            <input type="checkbox" id="repop_clear_unoccupied" value="1">
                            <span>Remplacer uniquement les oasis sauvages existantes</span>
                        </label>
                        <button type="button" onclick="executeRepopulateOases()" class="btn btn-primary" style="background: #16a34a; border-color: #22c55e; font-weight: 700; width: 100%;">
                            🌿 Appliquer & Générer les Oasis
                        </button>
                    </div>
                </div>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.75rem;">
                    ℹ️ Les oasis sont automatiquement réparties de façon équitable entre les 4 quadrants géographiques (NO, NE, SO, SE) sans empiéter sur les fiefs ni les 12 donjons authentiques.
                </div>
            </div>

            <!-- Tableau des Oasis existantes -->
            <div style="overflow-x: auto; max-height: 420px;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                    <thead style="position: sticky; top: 0; background: #0f172a; z-index: 2;">
                        <tr style="border-bottom: 1.5px solid rgba(34, 197, 94, 0.4); color: #4ade80; text-align: left;">
                            <th style="padding: 0.5rem;">#</th>
                            <th style="padding: 0.5rem;">Nom de l'Oasis</th>
                            <th style="padding: 0.5rem; text-align: center;">Coords</th>
                            <th style="padding: 0.5rem;">Bonus de Récolte</th>
                            <th style="padding: 0.5rem;">Faune / Garnison</th>
                            <th style="padding: 0.5rem; text-align: center;">Statut Féodal</th>
                            <th style="padding: 0.5rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allOases)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
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
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.06); background: <?= $isCaptured ? 'rgba(59, 130, 246, 0.05)' : 'transparent' ?>;">
                                    <td style="padding: 0.5rem; color: #94a3b8; font-weight: 700;"><?= $o['id'] ?></td>
                                    <td style="padding: 0.5rem;">
                                        <strong style="color: #fff;"><?= htmlspecialchars($o['name']) ?></strong>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                                            🪵 <?= number_format($o['res_wood']) ?> &bull; 🪨 <?= number_format($o['res_stone']) ?> &bull; 🌾 <?= number_format($o['res_rice']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; font-weight: 700; color: #38bdf8;">
                                        [<?= $o['coord_x'] ?> : <?= $o['coord_y'] ?>]
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <span style="color: #fde047; font-weight: 700;"><?= trim($bText) ?></span>
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <?php if (!empty($o['garrison'])): ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                                <?php foreach ($o['garrison'] as $g): ?>
                                                    <span style="font-size: 0.75rem; background: rgba(0,0,0,0.4); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);">
                                                        <?= $g['icon'] ?> <?= htmlspecialchars($g['unit_name']) ?> <strong style="color: #fbbf24;">x<?= $g['count'] ?></strong>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #4ade80; font-size: 0.75rem;">🕊️ Pacifiée (Aucune bête)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center;">
                                        <?php if ($isCaptured): ?>
                                            <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6; padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                                🛡️ Fief de <?= htmlspecialchars($o['owner_username'] ?? 'Daimyō') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                                🐗 Sauvage Libre
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: right;">
                                        <a href="/?page=map&x=<?= $o['coord_x'] ?>&y=<?= $o['coord_y'] ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.2rem 0.5rem; text-decoration: none;">
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

    <!-- Section 7 : 📮 Traitement des Dysfonctionnements & Suggestions des Joueurs -->
    <div class="admin-tab-pane" id="admin-tab-pane-support" data-tab="support" style="display: <?= $isPaneVisible('support') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(8, 145, 178, 0.4); background: rgba(17, 18, 24, 0.95);" id="supportAdminSection">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h3 style="color: #38bdf8; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                        <span>📮</span> Traitement des Dysfonctionnements & Suggestions des Joueurs
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Examinez les anomalies signalées et les propositions de la communauté. Répondez officiellement et notifiez les daimyōs.
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <span class="badge" style="background: rgba(8, 145, 178, 0.2); color: #38bdf8; border: 1px solid #0891b2; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                        <?= $supportStats['total'] ?> Total &bull; <?= $supportStats['total_bugs'] ?> Bugs &bull; <?= $supportStats['total_suggestions'] ?> Suggestions
                    </span>
                    <?php if ($supportStats['count_pending'] > 0): ?>
                        <span class="badge" style="background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid #ef4444; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 800;">
                            ⚠️ <?= $supportStats['count_pending'] ?> En attente
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <!-- Barre de Filtres Interactifs -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem; background: rgba(0,0,0,0.3); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                    <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">Filtrer :</span>
                        
                        <select id="adminTicketFilterType" class="form-control" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.85rem;" onchange="filterAdminTickets()">
                            <option value="all">Tous les Types (Bugs & Idées)</option>
                            <option value="bug">🪲 Bugs Uniquement</option>
                            <option value="suggestion">💡 Suggestions Uniquement</option>
                        </select>

                        <select id="adminTicketFilterStatus" class="form-control" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.85rem;" onchange="filterAdminTickets()">
                            <option value="all">Tous les Statuts</option>
                            <option value="pending">⏳ En attente</option>
                            <option value="in_progress">🔍 En cours d'examen</option>
                            <option value="resolved">✅ Résolus / Corrigés</option>
                            <option value="planned">📌 Retenus (Futures MAJ)</option>
                            <option value="closed">✖️ Fermés / Sans suite</option>
                        </select>
                    </div>

                    <div style="flex: 1; max-width: 320px; min-width: 200px;">
                        <input type="text" id="adminTicketSearchInput" class="form-control" placeholder="🔍 Rechercher joueur, titre..." oninput="filterAdminTickets()" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: 100%;">
                    </div>
                </div>

                <!-- Tableau des Demandes -->
                <div style="overflow-x: auto;">
                    <table class="table" id="adminTicketsTable" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid rgba(8, 145, 178, 0.3); color: #38bdf8; text-align: left;">
                                <th style="padding: 0.6rem;">#</th>
                                <th style="padding: 0.6rem;">Type & Sévérité</th>
                                <th style="padding: 0.6rem;">Secteur</th>
                                <th style="padding: 0.6rem;">Daimyō / Joueur</th>
                                <th style="padding: 0.6rem;">Titre du Signalement</th>
                                <th style="padding: 0.6rem; text-align: center;">Statut</th>
                                <th style="padding: 0.6rem; text-align: center;">Date</th>
                                <th style="padding: 0.6rem; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allSupportTickets)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        Aucun ticket de bug ou suggestion pour le moment.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allSupportTickets as $t): 
                                    $isBug = ($t['type'] === 'bug');
                                    $catLabel = SupportEngine::CATEGORIES[$t['category']] ?? $t['category'];
                                    $sevLabel = match($t['severity']) {
                                        'critical' => '<span style="color:#ef4444; font-weight:800;">🔴 Critique</span>',
                                        'high' => '<span style="color:#f97316; font-weight:700;">🟠 Élevé</span>',
                                        'medium' => '<span style="color:#eab308; font-weight:600;">🟡 Moyen</span>',
                                        'low' => '<span style="color:#22c55e;">🟢 Faible</span>',
                                        default => ''
                                    };
                                    $statusBadge = match($t['status']) {
                                        'pending' => '<span class="badge" style="background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid #eab308;">⏳ En attente</span>',
                                        'in_progress' => '<span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6;">🔍 En cours</span>',
                                        'resolved' => '<span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e;">✅ Résolu</span>',
                                        'planned' => '<span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7;">📌 Retenu</span>',
                                        'closed' => '<span class="badge" style="background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid #64748b;">✖️ Fermé</span>',
                                        default => $t['status']
                                    };
                                ?>
                                    <tr class="ticket-row" data-type="<?= $t['type'] ?>" data-status="<?= $t['status'] ?>" data-search="<?= strtolower(htmlspecialchars($t['username'] . ' ' . $t['title'])) ?>" style="border-bottom: 1px solid rgba(255, 255, 255, 0.06); transition: background 0.15s ease;">
                                        <td style="padding: 0.6rem; font-weight: 700; color: #94a3b8;">#<?= $t['id'] ?></td>
                                        <td style="padding: 0.6rem;">
                                            <div style="font-weight: 700; color: <?= $isBug ? '#f87171' : '#fbbf24' ?>;">
                                                <?= $isBug ? '🪲 Bug' : '💡 Suggestion' ?>
                                            </div>
                                            <?php if ($isBug): ?>
                                                <div style="font-size: 0.72rem; margin-top: 2px;"><?= $sevLabel ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 0.6rem; font-size: 0.8rem; color: #cbd5e1;">
                                            <?= htmlspecialchars($catLabel) ?>
                                        </td>
                                        <td style="padding: 0.6rem;">
                                            <div style="font-weight: 700; color: #fff;"><?= htmlspecialchars($t['username']) ?></div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Clan <?= htmlspecialchars($t['faction']) ?></div>
                                        </td>
                                        <td style="padding: 0.6rem;">
                                            <div style="font-weight: 600; color: #e2e8f0; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($t['title']) ?>">
                                                <?= htmlspecialchars($t['title']) ?>
                                            </div>
                                            <?php if (!empty($t['admin_response'])): ?>
                                                <div style="font-size: 0.72rem; color: #38bdf8; margin-top: 2px;">💬 Répondu</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 0.6rem; text-align: center;">
                                            <?= $statusBadge ?>
                                        </td>
                                        <td style="padding: 0.6rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">
                                            <?= date('d/m H:i', $t['created_at']) ?>
                                        </td>
                                        <td style="padding: 0.6rem; text-align: right;">
                                            <button type="button" class="btn btn-primary" onclick="openAdminTicketModal(<?= $t['id'] ?>)" style="padding: 0.3rem 0.75rem; font-size: 0.78rem; font-weight: 700; background: #0891b2; border-color: #0e7490;">
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
    <div class="admin-tab-pane" id="admin-tab-pane-announcements" data-tab="announcements" style="display: <?= $isPaneVisible('announcements') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(225, 29, 72, 0.3);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="color: #fb7185; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                        <span>📢</span> Annonces & Nouvelles Fonctionnalités aux Joueurs
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                        Toutes les annonces sont persistées dans <code>config/announcements.json</code>. Validez leur publication pour déclencher la modale d'explication aux daimyōs.
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" class="btn btn-primary" onclick="openAnnouncementEditModal()" style="background: linear-gradient(135deg, #e11d48, #be123c); border-color: #f43f5e; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <span>➕</span> Rédiger une Annonce
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
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
                                                    <div style="font-weight: 700; color: #fff;">
                                                        <?= htmlspecialchars($ann['title'] ?? '') ?>
                                                    </div>
                                                    <div style="font-family: monospace; color: #facc15; font-size: 0.75rem;">
                                                        <?= htmlspecialchars($ann['version'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span class="badge" style="background: rgba(225, 29, 72, 0.2); color: #fb7185; border: 1px solid rgba(225, 29, 72, 0.4); font-size: 0.75rem;">
                                                <?= htmlspecialchars($ann['badge'] ?? 'NOUVEAUTÉ') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span style="color: #38bdf8; font-weight: 700;">
                                                <?= $featuresCount ?> fonctionnalité<?= $featuresCount > 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <?php if ($isPub): ?>
                                                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); font-weight: 700;">
                                                    🟢 Validée & Publiée
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.4); font-weight: 700;">
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
                                                <button type="button" class="btn btn-secondary" onclick="toggleAnnouncementPublish('<?= $annId ?>')" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 700; color: <?= $isPub ? '#facc15' : '#4ade80' ?>;" title="<?= $isPub ? 'Mettre en brouillon' : 'Valider et diffuser aux joueurs' ?>">
                                                    <?= $isPub ? '⏸️ Dépublier' : '✓ Valider' ?>
                                                </button>
                                                <!-- Bouton Aperçu Modal Joueur -->
                                                <button type="button" class="btn btn-secondary" onclick='openAnnouncementPreview(<?= $annJsonEscaped ?>)' style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color: #38bdf8;" title="Prévisualiser la modale joueur">
                                                    👁️ Aperçu
                                                </button>
                                                <!-- Bouton Éditer -->
                                                <button type="button" class="btn btn-secondary" onclick='openAnnouncementEditModal(<?= $annJsonEscaped ?>)' style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color: #cbd5e1;" title="Modifier le contenu">
                                                    ✏️
                                                </button>
                                                <!-- Bouton Supprimer -->
                                                <button type="button" class="btn btn-secondary" onclick="deleteAnnouncement('<?= $annId ?>')" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color: #f87171;" title="Supprimer définitivement">
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
                            <select id="aem_is_published" name="is_published" class="form-control">
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

    <!-- Section Pédagogique : Atelier de Conception Père & Fils -->
    <div class="admin-tab-pane" id="admin-tab-pane-pedagogy" data-tab="pedagogy" style="display: <?= $isPaneVisible('pedagogy') ? 'block' : 'none' ?>;">
        <?php require __DIR__ . '/partials/admin_pedagogy.php'; ?>
    </div>

    <!-- Section Mises à Jour & Déploiement GitHub -->
    <div class="admin-tab-pane" id="admin-tab-pane-updates" data-tab="updates" style="display: <?= $isPaneVisible('updates') ? 'block' : 'none' ?>;">
        <?php require __DIR__ . '/partials/admin_updates.php'; ?>
    </div>

    <!-- Section 8 : ⚠️ Décret Suprême - Réinitialisation Complète du Monde Féodal -->
    <div class="admin-tab-pane" id="admin-tab-pane-maintenance" data-tab="maintenance" style="display: <?= $isPaneVisible('maintenance') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border: 1px solid rgba(239, 68, 68, 0.4); background: rgba(30, 10, 15, 0.75);">
            <div class="card-header" style="border-bottom: 1px solid rgba(239, 68, 68, 0.2); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: #ef4444; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>⚠️</span> Décret Suprême - Réinitialisation Complète du Monde Féodal (Reset)
                </h3>
                <span style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                    DESTRUCTIF
                </span>
            </div>
            <div class="card-body">
                <p style="color: #fca5a5; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Cette procédure purge l'ensemble des données du Japon féodal (clans, fiefs, rizières, armées en marche, dojos, messages et chroniques de combat).<br>
                    Le monde est alors recréé à neuf avec :
                </p>
                <ul style="color: #e2e8f0; font-size: 0.85rem; margin-bottom: 1.5rem; padding-left: 1.5rem; line-height: 1.6;">
                    <li>👑 <strong>Shogun Administrateur par défaut</strong> : Identifiant <strong>nezzar</strong> / Mot de passe <strong>Gabriel125#</strong></li>
                    <li>🏯 <strong>Château Capital</strong> : <code>Château Nezzar [1 : 1]</code> avec parcelles niveau 2, Tenshu, Dojo, Greniers et garnison de samouraïs.</li>
                    <li>🌾 <strong>12 terres et fiefs neutres</strong> générés procéduralement prêts pour l'expansion provinciale.</li>
                    <li>🤖 <strong>3 Daimyōs IA de départ</strong> (Clan Oda, Clan Takeda, Clan Tokugawa) pour un Japon vivant immédiatement.</li>
                </ul>

                <div style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(239, 68, 68, 0.4); padding: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">Confirmation de Sécurité Requise</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">Une boîte de dialogue vous demandera de saisir le mot-clé <strong>RESET</strong> avant toute action.</div>
                    </div>
                    <button type="button" onclick="openResetModal()" class="btn" style="background: #ef4444; color: #fff; font-weight: 700; padding: 0.75rem 1.75rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 0 20px rgba(239, 68, 68, 0.4);">
                        💥 Réinitialiser le Monde Féodal
                    </button>
                </div>
            </div>
        </div>
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

            <div class="form-group mb-3">
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
                        <select id="atm_select_status" name="status" class="form-control">
                            <option value="pending">⏳ En attente</option>
                            <option value="in_progress">🔍 En cours d'examen</option>
                            <option value="resolved">✅ Résolu / Corrigé</option>
                            <option value="planned">📌 Retenu (Future MAJ)</option>
                            <option value="closed">✖️ Fermé / Sans suite</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center pt-3">
                        <label style="display:inline-flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#1c1917; cursor:pointer; font-weight:600;">
                            <input type="checkbox" id="atm_notify_user" name="notify_user" value="1" checked style="accent-color:#b91c1c; width:16px; height:16px;">
                            Notifier le joueur par missive en jeu
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="atm_admin_response" class="form-label">
                        Réponse Officielle de l'Équipe (visible par le joueur) :
                    </label>
                    <textarea id="atm_admin_response" name="admin_response" rows="4" class="form-control" placeholder="Ex: Bonjour, l'anomalie a été identifiée et corrigée dans le dernier patch. Merci pour votre aide précieuse !"></textarea>
                </div>

                <div class="modal-footer px-0 pb-0" style="background:transparent; border-top:1px solid var(--border-color); margin-top:1rem; padding-top:1rem;">
                    <button type="button" class="btn btn-secondary" onclick="deleteAdminTicketFromModal()" style="color:#b91c1c; border-color:#fca5a5; font-size:0.85rem;">
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
function switchAdminTab(tabKey) {
    const validTabs = ['game', 'heroes', 'bots', 'users', 'oases', 'castles', 'world', 'medals', 'support', 'announcements', 'pedagogy', 'updates', 'maintenance', 'all'];
    if (!validTabs.includes(tabKey)) tabKey = 'game';

    // Afficher ou masquer les panneaux correspondants
    const panes = document.querySelectorAll('.admin-tab-pane');
    panes.forEach(pane => {
        if (tabKey === 'all') {
            pane.style.display = 'block';
        } else {
            const pTab = pane.getAttribute('data-tab');
            pane.style.display = (pTab === tabKey) ? 'block' : 'none';
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

    // Synchroniser l'URL sans rechargement de page et mémoriser l'onglet actif
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url.toString());
        sessionStorage.setItem('admin_active_tab', tabKey);
    } catch (e) {
        // En cas de restriction d'historique
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
        }
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
    document.getElementById('resource_speed_input').value = rSpeed;
    document.getElementById('resource_speed_range').value = rSpeed;
    document.getElementById('fleet_speed_input').value = fSpeed;
    document.getElementById('fleet_speed_range').value = fSpeed;
}

async function saveSettings(event) {
    if (event) event.preventDefault();
    const formData = new FormData(document.getElementById('gameSettingsForm'));
    formData.append('action', 'save_settings');
    formData.append('bots_enabled', document.getElementById('bots_enabled').value);
    formData.append('bot_colonize_enabled', document.getElementById('bot_colonize_enabled').value);
    formData.append('bot_max_planets', document.getElementById('bot_max_planets').value);
    formData.append('bot_aggressiveness', document.getElementById('bot_aggressiveness').value);
    formData.append('oasis_density_percent', document.getElementById('oasis_density_percent_input').value);
    formData.append('oasis_respawn_on_capture', document.getElementById('oasis_respawn_on_capture').checked ? '1' : '0');

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
</script>

