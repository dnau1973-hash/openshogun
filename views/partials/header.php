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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> - Chroniques Féodales du Sengoku</title>
    <!-- Bootstrap 5 Grille & Utilitaires (Mise en page & Flexbox) -->
    <link rel="stylesheet" href="/public/css/bootstrap-grid.min.css?v=5.3.3">
    <link rel="stylesheet" href="/public/css/bootstrap-utilities.min.css?v=5.3.3">
    <!-- Feuille de Style Féodale Sengoku La Voie du Shogun -->
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏯</text></svg>">
</head>
<body>

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

<div class="container <?= ($page === 'map' || $page === 'galaxy') ? 'container-fullwidth' : '' ?>">

