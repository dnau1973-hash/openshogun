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

$botEngine = new BotEngine();
$castleEngine = new CastleEngine();
$oasisEngine = new OasisEngine();
$supportEngine = new SupportEngine();
$updateEngine = new UpdateEngine();
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
$allowedTabs = ['game', 'bots', 'users', 'oases', 'castles', 'world', 'medals', 'support', 'announcements', 'pedagogy', 'updates', 'maintenance', 'all'];
$currentTab = $_GET['tab'] ?? 'game';
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'game';
}
$isPaneVisible = fn(string $tabKey) => ($currentTab === 'all' || $currentTab === $tabKey);
?>

<style>
.kpi-card {
    user-select: none;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 255, 255, 0.2);
}
.admin-tab-btn {
    transition: all 0.2s ease;
}
.admin-tab-btn:hover:not(.active) {
    background: rgba(255, 255, 255, 0.09) !important;
    color: #fff !important;
    border-color: rgba(255, 255, 255, 0.25) !important;
}
</style>

<div class="admin-panel" style="max-width: 1200px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- En-tête Terminal de Commandement Féodal -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(220, 38, 38, 0.3); padding-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 0.75rem;">
                <span style="color: #dc2626;">🏯</span> CONSEIL DU SHOGUNAT - ADMINISTRATION DU ROYAUME
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                Pilotage central des constantes du Japon féodal, équilibrage des vitesses et orchestration des clans autonomes (Bots).
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button onclick="runBotCycle()" class="btn btn-warning" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>⚔️</span> Exécuter un Cycle IA
            </button>
            <button onclick="generatePresetBots()" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>➕</span> Générer 3 Daimyōs IA
            </button>
        </div>
    </div>

    <!-- 5 Cartes Métriques Rapides Cliquables (Raccourcis vers Onglets) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
        <div class="card kpi-card" onclick="switchAdminTab('game')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #dc2626; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour configurer les constantes & vitesses">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Vitesse Active</span>
                <span>⚡</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626; margin-top: 0.25rem;">
                x<?= (int)($settings['game_speed'] ?? 5) ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Production: x<?= (int)($settings['resource_speed'] ?? 5) ?> | Marche: x<?= (int)($settings['fleet_speed'] ?? 5) ?></div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('bots')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #a855f7; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour gérer les bots et l'IA">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Clans IA</span>
                <span>🤖</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #c084fc; margin-top: 0.25rem;">
                <?= $totalBots ?> PNJ
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                Statut IA : <strong style="color: <?= !empty($settings['bots_enabled']) ? '#4ade80' : '#f87171' ?>;"><?= !empty($settings['bots_enabled']) ? 'Actif' : 'En sommeil' ?></strong>
            </div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('world')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #34d399; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour l'arpentage et l'expansion provinciale">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Fiefs & Domaines</span>
                <span>🗾</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #34d399; margin-top: 0.25rem;">
                <?= $totalColonies ?> / <?= $totalPlanets ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Châteaux sous contrôle des clans</div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('users')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #f59e0b; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour gérer les daimyōs joueurs et privilèges">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Daimyōs Joueurs</span>
                <span>👥</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fbbf24; margin-top: 0.25rem;">
                <?= $totalUsers ?> Joueurs
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Inscrits sur le serveur</div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('support')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #0891b2; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour traiter les bugs & suggestions">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Bugs & Idées</span>
                <span>📮</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #38bdf8; margin-top: 0.25rem;">
                <?= $supportStats['total'] ?> Demande<?= $supportStats['total'] > 1 ? 's' : '' ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                <strong style="color: <?= $supportStats['count_pending'] > 0 ? '#ef4444' : '#4ade80' ?>;">
                    <?= $supportStats['count_pending'] ?> en attente
                </strong>
                | <?= $supportStats['count_in_progress'] ?> en cours
            </div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('announcements')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #e11d48; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour gérer les annonces et fonctionnalités">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Nouveautés</span>
                <span>📢</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fb7185; margin-top: 0.25rem;">
                <?= $publishedAnnouncementsCount ?> / <?= $totalAnnouncementsCount ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                <?= $publishedAnnouncementsCount ?> publiée(s) aux joueurs
            </div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('pedagogy')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #06b6d4; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour ouvrir le manuel de conception et les prompts du jeu">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Projet Père-Fils</span>
                <span>🎓</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #67e8f9; margin-top: 0.25rem;">
                7 Modules
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                Code, Algorithmes & Prompts IA
            </div>
        </div>

        <div class="card kpi-card" onclick="switchAdminTab('updates')" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #38bdf8; padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Cliquer pour contrôler et déployer les mises à jour GitHub">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between;">
                <span>Mises à Jour Git</span>
                <span>🔄</span>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #38bdf8; margin-top: 0.25rem;">
                <?= htmlspecialchars($localGitInfo['short_sha']) ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                Branche <?= htmlspecialchars($localGitInfo['branch']) ?> | GitHub Sync
            </div>
        </div>
    </div>

    <!-- Barre de Navigation par Onglets de Paramétrage Shogunat -->
    <div class="admin-tabs-nav" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.75rem; overflow-x: auto; flex-wrap: wrap;">
        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'game') ? 'active' : '' ?>" data-tab="game" onclick="switchAdminTab('game')" style="<?= ($currentTab === 'game') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>⚡</span> Vitesses & Jeu
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #fca5a5; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);">x<?= (int)($settings['game_speed'] ?? 5) ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'bots') ? 'active' : '' ?>" data-tab="bots" onclick="switchAdminTab('bots')" style="<?= ($currentTab === 'bots') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🤖</span> Clans IA (Bots)
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #c084fc; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $totalBots ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'users') ? 'active' : '' ?>" data-tab="users" onclick="switchAdminTab('users')" style="<?= ($currentTab === 'users') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>👥</span> Daimyōs Joueurs
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #fbbf24; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $totalUsers ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'oases') ? 'active' : '' ?>" data-tab="oases" onclick="switchAdminTab('oases')" style="<?= ($currentTab === 'oases') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🌿</span> Oasis & Faune
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #4ade80; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $oasisStats['total_oases'] ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'castles') ? 'active' : '' ?>" data-tab="castles" onclick="switchAdminTab('castles')" style="<?= ($currentTab === 'castles') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🏯</span> 12 Donjons
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #f59e0b; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $spawnedCastlesCount ?>/12</span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'world') ? 'active' : '' ?>" data-tab="world" onclick="switchAdminTab('world')" style="<?= ($currentTab === 'world') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🗾</span> Provinces & Terres
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'medals') ? 'active' : '' ?>" data-tab="medals" onclick="switchAdminTab('medals')" style="<?= ($currentTab === 'medals') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🎖️</span> Médailles & Honneur
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #facc15; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($currentWeekCode) ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'support') ? 'active' : '' ?>" data-tab="support" onclick="switchAdminTab('support')" style="<?= ($currentTab === 'support') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>📮</span> Support & Suggestions
            <?php if ($supportStats['count_pending'] > 0): ?>
                <span class="badge" style="background: #ef4444; color: #fff; font-size: 0.72rem; font-weight: 800; border: 1px solid #f87171;">
                    ⚠️ <?= $supportStats['count_pending'] ?>
                </span>
            <?php else: ?>
                <span class="badge" style="background: rgba(0,0,0,0.3); color: #38bdf8; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $supportStats['total'] ?></span>
            <?php endif; ?>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'announcements') ? 'active' : '' ?>" data-tab="announcements" onclick="switchAdminTab('announcements')" style="<?= ($currentTab === 'announcements') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>📢</span> Nouveautés & Annonces
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #fb7185; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= $publishedAnnouncementsCount ?>/<?= $totalAnnouncementsCount ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'pedagogy') ? 'active' : '' ?>" data-tab="pedagogy" onclick="switchAdminTab('pedagogy')" style="<?= ($currentTab === 'pedagogy') ? 'background: linear-gradient(135deg, #0891b2, #06b6d4); color: #fff; border-color: #22d3ee; box-shadow: 0 4px 12px rgba(6, 182, 212, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🎓</span> Atelier & Pédagogie (Projet Père-Fils)
            <span class="badge" style="background: rgba(0,0,0,0.3); color: #67e8f9; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);">Code & Prompts</span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'updates') ? 'active' : '' ?>" data-tab="updates" onclick="switchAdminTab('updates')" style="<?= ($currentTab === 'updates') ? 'background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border-color: #38bdf8; box-shadow: 0 4px 12px rgba(56, 189, 248, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>🔄</span> Mises à Jour GitHub
            <span class="badge" id="admin-update-nav-badge" style="background: rgba(0,0,0,0.3); color: #38bdf8; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.1);"><?= htmlspecialchars($localGitInfo['short_sha']) ?></span>
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'maintenance') ? 'active' : '' ?>" data-tab="maintenance" onclick="switchAdminTab('maintenance')" style="<?= ($currentTab === 'maintenance') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>⚠️</span> Maintenance & Reset
        </button>

        <button type="button" class="btn admin-tab-btn <?= ($currentTab === 'all') ? 'active' : '' ?>" data-tab="all" onclick="switchAdminTab('all')" style="<?= ($currentTab === 'all') ? 'background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; border-color: #ef4444; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); font-weight: 800;' : 'background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.1); font-weight: 600;' ?> display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; padding: 0.55rem 0.95rem; border-radius: 8px; cursor: pointer; white-space: nowrap;">
            <span>📚</span> Tout Dérouler
        </button>
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
                                🏗️ Vitesse Globale (Constructions, Navires, Caserne, Recherche)
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="game_speed_range" min="1" max="100" value="<?= (int)($settings['game_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('game_speed_input').value = this.value">
                                <input type="number" id="game_speed_input" name="game_speed" min="1" max="100" 
                                       value="<?= (int)($settings['game_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('game_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Divise le temps nécessaire aux chantiers, bâtiments et académies militaires.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                ⛏️ Vitesse de Production des Ressources (Mines & Synthétiseurs)
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="resource_speed_range" min="1" max="100" value="<?= (int)($settings['resource_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('resource_speed_input').value = this.value">
                                <input type="number" id="resource_speed_input" name="resource_speed" min="1" max="100" 
                                       value="<?= (int)($settings['resource_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('resource_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Multiplie la production horaire de Titanium, Silicate et Hydrogène.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                🚀 Vitesse de Déplacement des Flottes Interstellaires
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="range" id="fleet_speed_range" min="1" max="50" value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" 
                                       style="flex: 1;" oninput="document.getElementById('fleet_speed_input').value = this.value">
                                <input type="number" id="fleet_speed_input" name="fleet_speed" min="1" max="50" 
                                       value="<?= (int)($settings['fleet_speed'] ?? 5) ?>" class="form-control" style="width: 80px; text-align: center;"
                                       oninput="document.getElementById('fleet_speed_range').value = this.value">
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Accélère la durée des trajets aller-retour pour raids, transports et colonisations.</small>
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

    <!-- Section 2 : Système d'IA & Colonisation des Bots -->
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
                                🪐 Colonisation Automatique de Nouvelles Planètes
                            </label>
                            <select name="bot_colonize_enabled" class="form-control" id="bot_colonize_enabled">
                                <option value="1" <?= !empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🟢 Autorisée (Les bots fondent des colonies)</option>
                                <option value="0" <?= empty($settings['bot_colonize_enabled']) ? 'selected' : '' ?>>🔴 Désactivée (Planète capitale uniquement)</option>
                            </select>
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Déclenche l'expansion galactique des bots vers de nouvelles coordonnées.</small>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: #fff;">
                                Nombre Max de Planètes par Bot
                            </label>
                            <input type="number" name="bot_max_planets" id="bot_max_planets" min="1" max="10" 
                                   value="<?= (int)($settings['bot_max_planets'] ?? 3) ?>" class="form-control">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Plafond d'expansion territoriale par IA (Capitale + Avant-postes).</small>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="color: #fff; font-size: 1.1rem; margin: 0;">📋 Registre des Commandants Bots en Activité</h4>
                    <button onclick="generatePresetBots()" class="btn btn-secondary" style="font-size: 0.85rem;">
                        <span>➕</span> Ajouter 3 Bots Multi-Factions
                    </button>
                </div>

                <?php if (empty($botsList)): ?>
                    <div style="text-align: center; padding: 2rem; background: rgba(0,0,0,0.2); border-radius: 8px; color: var(--text-muted);">
                        Aucun Bot PNJ actuellement déployé dans l'univers. Cliquez sur le bouton ci-dessus pour peupler la galaxie !
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 0.75rem;">Commandant PNJ</th>
                                    <th style="padding: 0.75rem;">Civilisation</th>
                                    <th style="padding: 0.75rem;">Capitale (X:Y)</th>
                                    <th style="padding: 0.75rem; text-align: center;">Colonies</th>
                                    <th style="padding: 0.75rem; text-align: right;">Points d'Empire</th>
                                    <th style="padding: 0.75rem; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($botsList as $bot): ?>
                                    <?php $fInfo = FACTIONS[$bot['faction']] ?? FACTIONS['terran']; ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 0.75rem; font-weight: 700; color: #fff;">
                                            🤖 <?= htmlspecialchars($bot['username']) ?>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span class="faction-badge <?= $bot['faction'] ?>" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                                <?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; font-family: monospace; color: #dc2626;">
                                            [<?= $bot['capital_x'] ?> : <?= $bot['capital_y'] ?>]
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <span style="background: rgba(52, 211, 153, 0.15); color: #34d399; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                                                <?= $bot['planet_count'] ?> fief(s)
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: #fbbf24;">
                                            🏆 <?= number_format($bot['points']) ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <button onclick="deleteBot(<?= $bot['id'] ?>, '<?= htmlspecialchars(addslashes($bot['username'])) ?>')" 
                                                    class="btn btn-secondary" style="font-size: 0.75rem; color: #f87171; border-color: rgba(239, 68, 68, 0.3); padding: 0.25rem 0.5rem;">
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

    <!-- Section 3 : Gestion des Daimyōs Joueurs -->
    <div class="admin-tab-pane" id="admin-tab-pane-users" data-tab="users" style="display: <?= $isPaneVisible('users') ? 'block' : 'none' ?>;">
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(220, 38, 38, 0.2);">
            <div class="card-header">
                <h3 style="color: #dc2626; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>👥</span> Gestion des Daimyōs Joueurs & Privilèges
                </h3>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                                <th style="padding: 0.75rem;">ID</th>
                                <th style="padding: 0.75rem;">Commandant</th>
                                <th style="padding: 0.75rem;">Email</th>
                                <th style="padding: 0.75rem;">Civilisation</th>
                                <th style="padding: 0.75rem; text-align: center;">Colonies</th>
                                <th style="padding: 0.75rem; text-align: right;">Points</th>
                                <th style="padding: 0.75rem; text-align: center;">Rôle</th>
                                <th style="padding: 0.75rem; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($humanUsers as $hUser): ?>
                                <?php $hfInfo = FACTIONS[$hUser['faction']] ?? FACTIONS['terran']; ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 0.75rem; color: var(--text-muted);">#<?= $hUser['id'] ?></td>
                                    <td style="padding: 0.75rem; font-weight: 700; color: #fff;">
                                        <?= htmlspecialchars($hUser['username']) ?>
                                        <?php if ((int)$hUser['id'] === (int)Auth::id()): ?>
                                            <span style="font-size: 0.75rem; color: #dc2626;">(Vous)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem; color: #94a3b8; font-size: 0.85rem;"><?= htmlspecialchars($hUser['email']) ?></td>
                                    <td style="padding: 0.75rem;">
                                        <span class="faction-badge <?= $hUser['faction'] ?>" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                            <?= $hfInfo['icon'] ?> <?= htmlspecialchars($hfInfo['name']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <span style="background: rgba(220, 38, 38, 0.15); color: #dc2626; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                                            <?= $hUser['colony_count'] ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: #fbbf24;">
                                        🏆 <?= number_format($hUser['points']) ?>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <?php if ((int)$hUser['is_admin'] === 1): ?>
                                            <span style="background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid #eab308; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                                ⭐ ADMINISTRATEUR
                                            </span>
                                        <?php else: ?>
                                            <span style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                JOUEUR
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <?php if ((int)$hUser['id'] !== (int)Auth::id()): ?>
                                            <button onclick="toggleAdmin(<?= $hUser['id'] ?>, '<?= htmlspecialchars(addslashes($hUser['username'])) ?>', <?= (int)$hUser['is_admin'] ?>)"
                                                    class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                <?= ((int)$hUser['is_admin'] === 1) ? 'Rétrograder' : 'Promouvoir Admin' ?>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

    <!-- Modale d'Édition / Création d'une Annonce (Admin) -->
    <div class="modal-overlay" id="announcementEditModal" style="display:none; position:fixed; inset:0; background:rgba(5,7,15,0.85); backdrop-filter:blur(8px); z-index:1060; align-items:center; justify-content:center; padding:1rem;">
        <div class="modal-card" style="max-width:820px; width:100%; max-height:92vh; background:#11131a; border:1px solid #e11d48; border-radius:12px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 0 40px rgba(225,29,72,0.3);">
            <div class="card-header" style="background:linear-gradient(135deg, rgba(225,29,72,0.2) 0%, #11131a 100%); border-bottom:1px solid rgba(225,29,72,0.3); padding:1.25rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="aem_modal_title" style="margin:0; color:#fff; font-size:1.2rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
                    <span>📢</span> Rédiger une Annonce
                </h3>
                <button type="button" onclick="closeAnnouncementEditModal()" style="background:transparent; border:none; color:#9ca3af; font-size:1.6rem; cursor:pointer;">&times;</button>
            </div>

            <form id="announcementEditForm" onsubmit="saveAnnouncementFromModal(event)" style="display:flex; flex-direction:column; flex:1; overflow:hidden; margin:0;">
                <input type="hidden" id="aem_id" name="id" value="">
                
                <div style="padding:1.5rem; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:1.2rem;">
                    <div style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:1rem;">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Version / Code</label>
                            <input type="text" id="aem_version" name="version" class="form-control" required placeholder="v1.3.0" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Titre de l'Annonce</label>
                            <input type="text" id="aem_title" name="title" class="form-control" required placeholder="L'Éveil du Héros & Nouveaux Bâtiments" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Date</label>
                            <input type="date" id="aem_date" name="date" class="form-control" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1.5fr 1fr 1fr; gap:1rem;">
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Badge Visuel</label>
                            <input type="text" id="aem_badge" name="badge" class="form-control" placeholder="⭐ MISE À JOUR MAJEURE" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Icône Principale</label>
                            <input type="text" id="aem_icon" name="icon" class="form-control" placeholder="⚔️" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Statut Publication</label>
                            <select id="aem_is_published" name="is_published" class="form-control" style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px;">
                                <option value="1">🟢 Validée & Publiée aux joueurs</option>
                                <option value="0">🟡 Brouillon (En attente)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#cbd5e1; margin-bottom:0.3rem;">Résumé d'accroche pour les Daimyōs</label>
                        <textarea id="aem_summary" name="summary" rows="2" class="form-control" placeholder="Décrivez succinctement l'importance de cette mise à jour pour vos joueurs..." style="width:100%; padding:0.55rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:6px; line-height:1.4;"></textarea>
                    </div>

                    <!-- Liste dynamique des fonctionnalités -->
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                            <label style="font-size:0.85rem; font-weight:800; color:#facc15; margin:0; display:flex; align-items:center; gap:0.4rem;">
                                <span>🏯</span> Fonctionnalités & Améliorations Détaillées
                            </label>
                            <button type="button" onclick="addFeatureRowToModal()" class="btn btn-secondary" style="font-size:0.78rem; padding:0.3rem 0.75rem; border-color:#facc15; color:#facc15;">
                                + Ajouter une nouveauté
                            </button>
                        </div>

                        <div id="aem_features_container" style="display:flex; flex-direction:column; gap:0.75rem;">
                            <!-- Lignes de fonctionnalités injectées en JS -->
                        </div>
                    </div>
                </div>

                <div style="background:rgba(0,0,0,0.4); border-top:1px solid rgba(255,255,255,0.08); padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary" onclick="closeAnnouncementEditModal()">Annuler</button>
                    <button type="submit" id="aem_submit_btn" class="btn btn-primary" style="background:linear-gradient(135deg, #e11d48, #be123c); border-color:#f43f5e; font-weight:700;">
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
<div class="modal-overlay" id="resetUniverseModal" style="display: none; position: fixed; inset: 0; background: rgba(5, 7, 15, 0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-card" style="max-width: 520px; width: 90%; background: rgba(20, 10, 15, 0.95); border: 2px solid #ef4444; border-radius: 12px; box-shadow: 0 0 50px rgba(239, 68, 68, 0.4); overflow: hidden;">
        <div class="card-header" style="background: rgba(239, 68, 68, 0.15); border-bottom: 1px solid rgba(239, 68, 68, 0.3); padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #ef4444; margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>💥</span> CONFIRMATION DESTRUCTIVE : RESET
            </h3>
            <button onclick="closeResetModal()" style="background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <p style="color: #fca5a5; font-size: 0.9rem; margin-bottom: 1rem;">
                Attention ! Toutes les parties en cours et données de jeu seront <strong>irréversiblement effacées</strong>. Le compte administrateur <strong>nezzar</strong> sera recréé avec le mot de passe <strong>Gabriel125#</strong>.
            </p>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 700; color: #e2e8f0; font-size: 0.85rem; margin-bottom: 0.4rem;">
                    Pour confirmer, tapez le mot <strong style="color: #ef4444;">RESET</strong> en majuscules :
                </label>
                <input type="text" id="resetKeywordInput" class="form-control" placeholder="RESET" style="border-color: #ef4444; font-family: monospace; font-size: 1.1rem; text-align: center; letter-spacing: 2px;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeResetModal()">Annuler</button>
                <button type="button" class="btn" onclick="executeUniverseReset()" style="background: #ef4444; color: #fff; font-weight: 700; padding: 0.6rem 1.5rem; border: none; border-radius: 6px; cursor: pointer;">
                    💥 Exécuter le Reset Immédiat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale d'Examen et de Traitement d'un Ticket par l'Administrateur -->
<div class="modal-overlay" id="adminTicketModal" style="display: none; position: fixed; inset: 0; background: rgba(5, 7, 15, 0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;" onclick="closeAdminTicketModal()">
    <div class="modal-card" style="max-width: 680px; width: 92%; max-height: 92vh; background: #111218; border: 2px solid #0891b2; border-radius: 12px; box-shadow: 0 0 50px rgba(8, 145, 178, 0.3); overflow: hidden; display: flex; flex-direction: column;" onclick="event.stopPropagation()">
        <div class="card-header" style="background: rgba(8, 145, 178, 0.15); border-bottom: 1px solid rgba(8, 145, 178, 0.3); padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 id="atm_header_title" style="color: #38bdf8; margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📮</span> Traitement du Ticket #<span id="atm_ticket_id"></span>
                </h3>
                <div id="atm_header_meta" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"></div>
            </div>
            <button onclick="closeAdminTicketModal()" style="background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <div class="card-body" style="padding: 1.5rem; overflow-y: auto;">
            <!-- Détails du Joueur & Fief -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.5rem; font-size: 0.82rem;">
                <div><span style="color:var(--text-muted);">Daimyō :</span> <strong id="atm_user_name" style="color:#fff;"></strong></div>
                <div><span style="color:var(--text-muted);">Clan :</span> <strong id="atm_user_faction" style="color:#fff; text-transform:uppercase;"></strong></div>
                <div><span style="color:var(--text-muted);">Fief :</span> <strong id="atm_user_planet" style="color:#fff;"></strong></div>
                <div><span style="color:var(--text-muted);">Date :</span> <strong id="atm_created_at" style="color:#fff;"></strong></div>
            </div>

            <!-- Titre & Message du Joueur -->
            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Message du Joueur :</label>
                <div id="atm_ticket_title" style="font-weight: 800; font-size: 1.05rem; color: #fff; margin: 0.25rem 0 0.5rem 0;"></div>
                <div id="atm_ticket_desc" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 8px; font-size: 0.9rem; line-height: 1.6; color: #e2e8f0; white-space: pre-line; max-height: 200px; overflow-y: auto;"></div>
            </div>

            <!-- Formulaire de Traitement Administrateur -->
            <form id="adminTicketForm" onsubmit="saveAdminTicket(event)">
                <input type="hidden" id="atm_input_ticket_id" name="ticket_id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="atm_select_status" style="display: block; font-weight: 700; font-size: 0.85rem; color: #38bdf8; margin-bottom: 0.4rem;">
                            Statut de la Demande :
                        </label>
                        <select id="atm_select_status" name="status" class="form-control" style="width: 100%; padding: 0.5rem; border-radius: 6px; background: #1e293b; color: #fff; border: 1px solid #334155;">
                            <option value="pending">⏳ En attente</option>
                            <option value="in_progress">🔍 En cours d'examen</option>
                            <option value="resolved">✅ Résolu / Corrigé</option>
                            <option value="planned">📌 Retenu (Future MAJ)</option>
                            <option value="closed">✖️ Fermé / Sans suite</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: flex-end; padding-bottom: 0.5rem;">
                        <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #e2e8f0; cursor: pointer;">
                            <input type="checkbox" id="atm_notify_user" name="notify_user" value="1" checked style="accent-color: #0891b2; width: 16px; height: 16px;">
                            Notifier le joueur par missive en jeu
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="atm_admin_response" style="display: block; font-weight: 700; font-size: 0.85rem; color: #38bdf8; margin-bottom: 0.4rem;">
                        Réponse Officielle de l'Équipe (visible par le joueur) :
                    </label>
                    <textarea id="atm_admin_response" name="admin_response" rows="4" class="form-control" placeholder="Ex: Bonjour, l'anomalie a été identifiée et corrigée dans le dernier patch. Merci pour votre aide précieuse !" style="width: 100%; padding: 0.75rem; border-radius: 6px; background: #1e293b; color: #fff; border: 1px solid #334155; font-size: 0.88rem; line-height: 1.5;"></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="deleteAdminTicketFromModal()" style="color: #f87171; border-color: rgba(239,68,68,0.3); font-size: 0.85rem;">
                        🗑️ Supprimer
                    </button>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeAdminTicketModal()">Annuler</button>
                        <button type="submit" id="atm_submit_btn" class="btn btn-primary" style="background: #0891b2; border-color: #0e7490; font-weight: 700;">
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
    const validTabs = ['game', 'bots', 'users', 'oases', 'castles', 'world', 'medals', 'support', 'announcements', 'pedagogy', 'updates', 'maintenance', 'all'];
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

    // Mettre à jour l'apparence des boutons d'onglets
    const tabBtns = document.querySelectorAll('.admin-tab-btn');
    tabBtns.forEach(btn => {
        const bTab = btn.getAttribute('data-tab');
        if (bTab === tabKey) {
            btn.classList.add('active');
            btn.style.background = 'linear-gradient(135deg, #b91c1c, #dc2626)';
            btn.style.color = '#fff';
            btn.style.borderColor = '#ef4444';
            btn.style.boxShadow = '0 4px 12px rgba(220, 38, 38, 0.35)';
            btn.style.fontWeight = '800';
        } else {
            btn.classList.remove('active');
            btn.style.background = 'rgba(255,255,255,0.04)';
            btn.style.color = '#cbd5e1';
            btn.style.borderColor = 'rgba(255,255,255,0.1)';
            btn.style.boxShadow = 'none';
            btn.style.fontWeight = '600';
        }
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
    row.style.cssText = 'background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:0.85rem; display:flex; flex-direction:column; gap:0.5rem;';

    const iconVal = f ? (f.icon || '🔹') : '🔹';
    const catVal = f ? (f.category || 'Général') : 'Général';
    const titleVal = f ? (f.title || '') : '';
    const descVal = f ? (f.description || '') : '';

    row.innerHTML = `
        <div style="display:grid; grid-template-columns: 80px 1.5fr 2fr 40px; gap:0.6rem; align-items:center;">
            <div>
                <input type="text" class="feature-icon-input form-control" placeholder="Icône" value="${escapeHtml(iconVal)}" style="padding:0.4rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:4px; text-align:center;">
            </div>
            <div>
                <input type="text" class="feature-category-input form-control" placeholder="Catégorie (ex: Cité, Héros)" value="${escapeHtml(catVal)}" style="padding:0.4rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:4px;">
            </div>
            <div>
                <input type="text" class="feature-title-input form-control" placeholder="Titre de la fonctionnalité" value="${escapeHtml(titleVal)}" required style="padding:0.4rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:4px;">
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="this.closest('.aem-feature-row').remove()" style="background:transparent; border:none; color:#f87171; font-size:1.2rem; cursor:pointer;" title="Supprimer cette fonctionnalité">&times;</button>
            </div>
        </div>
        <div>
            <textarea class="feature-desc-input form-control" rows="2" placeholder="Explications claires pour les joueurs sur le fonctionnement et les bénéfices..." style="width:100%; padding:0.45rem; background:#1e222e; color:#fff; border:1px solid #334155; border-radius:4px; font-size:0.85rem; line-height:1.4;">${escapeHtml(descVal)}</textarea>
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

