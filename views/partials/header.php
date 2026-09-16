<?php
/**
 * Header Partiel OpenGalaxy
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/BuildingEngine.php';
require_once __DIR__ . '/../../core/FleetEngine.php';
require_once __DIR__ . '/../../core/MessageEngine.php';
require_once __DIR__ . '/../../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$messageEngine = new MessageEngine();
$unreadMessagesCount = $messageEngine->getUnreadCount((int)$user['id']);

if ($planet) {
    $planetEngine = new PlanetEngine();
    $fleetEngine = new FleetEngine();
    
    // Mettre à jour les flottes et la planète
    $fleetEngine->processFleetMissions();
    $planet = $planetEngine->updatePlanet((int)$planet['id']);
    
    // Vérifier les flottes en mouvement pour l'alerte HUD
    $db = Database::getConnection();
    $stmtMissions = $db->prepare("
        SELECT * FROM fleet_missions 
        WHERE (user_id = ? OR target_planet_id = ?) AND status IN ('en_route', 'returning') 
        ORDER BY arrival_time ASC
    ");
    $stmtMissions->execute([$user['id'], $planet['id']]);
    $activeMissions = $stmtMissions->fetchAll();
}

$page = $_GET['page'] ?? 'resources';
$factionInfo = FACTIONS[$user['faction']] ?? FACTIONS['terran'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShogun - Époque Sengoku Jidai & Stratégie des Daimyōs</title>
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏯</text></svg>">
</head>
<body>

<header class="hud-header">
    <div class="hud-top">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <div class="brand">
                <span class="brand-icon">🏯</span>
                <span>OpenShogun</span>
            </div>

            <div class="planet-selector">
                <span>🏯 <strong><?= htmlspecialchars($planet['name']) ?></strong></span>
                <span style="color: var(--border-highlight);">[<?= $planet['coord_x'] ?> : <?= $planet['coord_y'] ?>]</span>
            </div>
        </div>

        <!-- 🧭 3 Médaillons Circulaires de Navigation Féodale Travian (Terroir, Cité, Provinces) -->
        <div class="travian-nav-medallions">
            <!-- 1. Terroir & Récoltes -->
            <a href="?page=resources" class="travian-medallion <?= ($page === 'resources' || $page === 'field') ? 'active' : '' ?>" title="Terroir & Récoltes">
                <img src="/public/assets/nav_resources.jpg" alt="Terroir" class="travian-medallion-img">
                <span class="travian-medallion-tooltip">Terroir & Récoltes</span>
            </a>

            <!-- 2. Cité Castrale -->
            <a href="?page=city" class="travian-medallion <?= ($page === 'city') ? 'active' : '' ?>" title="Cité Castrale">
                <img src="/public/assets/nav_colony.jpg" alt="Cité Castrale" class="travian-medallion-img">
                <span class="travian-medallion-tooltip">Cité Castrale</span>
            </a>

            <!-- 3. Provinces du Japon -->
            <a href="?page=galaxy" class="travian-medallion <?= ($page === 'galaxy') ? 'active' : '' ?>" title="Provinces du Japon">
                <img src="/public/assets/nav_galaxy.jpg" alt="Provinces du Japon" class="travian-medallion-img">
                <span class="travian-medallion-tooltip">Provinces du Japon</span>
            </a>
        </div>

        <div class="user-profile">
            <span class="faction-badge <?= htmlspecialchars($user['faction']) ?>">
                <?= $factionInfo['icon'] ?> <?= htmlspecialchars($factionInfo['name']) ?>
            </span>
            <span style="cursor: pointer;" onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)" title="Consulter votre Fiche de Daimyō">
                Daimyō <strong><?= htmlspecialchars($user['username']) ?></strong>
            </span>
            <a href="?page=ranking" style="text-decoration: none; color: inherit;" title="Classement des Daimyōs & Tableau d'Honneur">
                <span>🏆 <?= number_format($user['points']) ?> pts</span>
            </a>
            <a href="?page=reports" class="hud-msg-btn <?= ($page === 'reports') ? 'active' : '' ?>" title="Chroniques de Siège & d'Infiltration">
                <span>📜</span>
            </a>
            <a href="?page=messages" class="hud-msg-btn <?= ($unreadMessagesCount > 0) ? 'has-unread' : '' ?> <?= ($page === 'messages') ? 'active' : '' ?>" title="Missives & Correspondance des Clans">
                <span>✉️</span>
                <?php if ($unreadMessagesCount > 0): ?>
                    <span class="hud-unread-count"><?= $unreadMessagesCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=docs" class="hud-msg-btn <?= ($page === 'docs') ? 'active' : '' ?>" title="Codex & Documentation du Jeu">
                <span>📖</span>
            </a>
            <?php if ($auth->isAdmin()): ?>
                <a href="?page=admin" class="badge" style="background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid #eab308; padding: 0.25rem 0.5rem; text-decoration: none; font-weight: 700; margin-left: 0.25rem;" title="QG d'Administration">
                    ⚙️ ADMIN
                </a>
            <?php endif; ?>
            <a href="?action=logout" style="color: #ef4444; font-size: 0.85rem; margin-left: 0.5rem;">[Quitter]</a>
        </div>
    </div>

    <!-- Barre des Ressources Féodales Travian-Style -->
    <div class="resources-bar">
        <!-- Bois de Cèdre -->
        <div class="res-item">
            <span class="res-icon">🪵</span>
            <div class="res-data" style="flex:1;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--res-metal); font-size: 0.75rem; font-weight:700;">BOIS DE CÈDRE</span>
                    <span class="res-prod">+<?= number_format($planet['prod_rates']['metal']) ?>/h</span>
                </div>
                <div class="res-value" id="res-val-metal" 
                     data-current="<?= $planet['metal'] ?>" 
                     data-max="<?= $planet['metal_max'] ?>" 
                     data-prod="<?= $planet['prod_rates']['metal'] ?>">
                    <?= number_format((int)$planet['metal']) ?>
                </div>
                <div class="res-bar-cont">
                    <div class="res-bar-fill metal" id="bar-metal" style="width: <?= min(100, ($planet['metal'] / $planet['metal_max']) * 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Pierre de Taille -->
        <div class="res-item">
            <span class="res-icon">🪨</span>
            <div class="res-data" style="flex:1;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--res-crystal); font-size: 0.75rem; font-weight:700;">PIERRE DE TAILLE</span>
                    <span class="res-prod">+<?= number_format($planet['prod_rates']['crystal']) ?>/h</span>
                </div>
                <div class="res-value" id="res-val-crystal" 
                     data-current="<?= $planet['crystal'] ?>" 
                     data-max="<?= $planet['crystal_max'] ?>" 
                     data-prod="<?= $planet['prod_rates']['crystal'] ?>">
                    <?= number_format((int)$planet['crystal']) ?>
                </div>
                <div class="res-bar-cont">
                    <div class="res-bar-fill crystal" id="bar-crystal" style="width: <?= min(100, ($planet['crystal'] / $planet['crystal_max']) * 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Riz Impérial -->
        <div class="res-item">
            <span class="res-icon">🌾</span>
            <div class="res-data" style="flex:1;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--res-deut); font-size: 0.75rem; font-weight:700;">RIZ IMPÉRIAL</span>
                    <span class="res-prod">+<?= number_format($planet['prod_rates']['deuterium']) ?>/h</span>
                </div>
                <div class="res-value" id="res-val-deut" 
                     data-current="<?= $planet['deuterium'] ?>" 
                     data-max="<?= $planet['deuterium_max'] ?>" 
                     data-prod="<?= $planet['prod_rates']['deuterium'] ?>">
                    <?= number_format((int)$planet['deuterium']) ?>
                </div>
                <div class="res-bar-cont">
                    <div class="res-bar-fill deut" id="bar-deut" style="width: <?= min(100, ($planet['deuterium'] / $planet['deuterium_max']) * 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Honneur & Sérénité -->
        <div class="res-item">
            <span class="res-icon">⛩️</span>
            <div class="res-data" style="flex:1;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color: var(--res-energy); font-size: 0.75rem; font-weight:700;">SÉRÉNITÉ & FERVEUR</span>
                    <span class="res-prod"><?= $planet['energy_used'] ?> / <?= $planet['energy_max'] ?></span>
                </div>
                <div class="res-value" style="color: <?= ($planet['energy_max'] >= $planet['energy_used']) ? '#4ade80' : '#f87171' ?>;" title="<?= ($planet['energy_max'] < $planet['energy_used']) ? 'Sérénité insuffisante : récoltes ralenties à 10%' : 'Sérénité optimale dans le domaine' ?>">
                    <?= ($planet['energy_max'] - $planet['energy_used']) ?> disp.
                    <?php if ($planet['energy_max'] < $planet['energy_used']): ?>
                        <span style="font-size: 0.65rem; background: #ef4444; color: #fff; padding: 1px 4px; border-radius: 3px; font-weight: 700;">10%</span>
                    <?php endif; ?>
                </div>
                <div class="res-bar-cont">
                    <div class="res-bar-fill energy" style="width: <?= min(100, ($planet['energy_used'] / max(1, $planet['energy_max'])) * 100) ?>%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes de Guet & Mouvements d'Armées -->
    <?php if (!empty($activeMissions)): ?>
        <div style="background: rgba(185, 28, 28, 0.4); border-bottom: 1px solid #dc2626; padding: 0.35rem 1.5rem; font-size: 0.85rem; display: flex; gap: 1.5rem; overflow-x: auto;">
            <?php foreach ($activeMissions as $m): ?>
                <?php 
                    $isIncoming = ($m['target_planet_id'] == $planet['id'] && $m['status'] === 'en_route');
                    $badgeColor = $isIncoming ? '#ef4444' : '#dc2626';
                    $targetTime = ($m['status'] === 'en_route') ? $m['arrival_time'] : $m['return_time'];
                ?>
                <div style="display: flex; align-items: center; gap: 0.4rem; white-space: nowrap;">
                    <span style="color: <?= $badgeColor ?>; font-weight: 800;">
                        <?= $isIncoming ? '⚠️ TOUR DE GUET : ARMÉE EN APPROCHE :' : '🐎 EXPÉDITION EN MARCHE :' ?>
                    </span>
                    <span><?= strtoupper($m['mission_type']) ?></span>
                    <span style="font-family: monospace; font-weight: 700; color: #fff;" data-countdown="<?= $targetTime ?>">Calcul...</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</header>

<div class="container">

