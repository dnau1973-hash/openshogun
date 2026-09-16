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

$botEngine = new BotEngine();
$castleEngine = new CastleEngine();
$db = Database::getConnection();

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
?>

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

    <!-- 4 Cartes Métriques Rapides -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="card" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #dc2626; padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Vitesse Globale Active</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626; margin-top: 0.25rem;">
                x<?= (int)($settings['game_speed'] ?? 5) ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Production: x<?= (int)($settings['resource_speed'] ?? 5) ?> | Marche: x<?= (int)($settings['fleet_speed'] ?? 5) ?></div>
        </div>

        <div class="card" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #a855f7; padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Clans IA Déployés</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #c084fc; margin-top: 0.25rem;">
                <?= $totalBots ?> PNJ
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                Statut IA : <strong style="color: <?= !empty($settings['bots_enabled']) ? '#4ade80' : '#f87171' ?>;"><?= !empty($settings['bots_enabled']) ? 'Actif' : 'En sommeil' ?></strong>
            </div>
        </div>

        <div class="card" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #34d399; padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Fiefs & Domaines Établis</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #34d399; margin-top: 0.25rem;">
                <?= $totalColonies ?> / <?= $totalPlanets ?>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Châteaux sous le contrôle des clans</div>
        </div>

        <div class="card" style="background: rgba(17, 18, 24, 0.85); border-left: 4px solid #f59e0b; padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Daimyōs Joueurs</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fbbf24; margin-top: 0.25rem;">
                <?= $totalUsers ?> Joueurs
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Inscrits sur le serveur</div>
        </div>
    </div>

    <!-- Section 1 : Variables de Jeu & Vitesses -->
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

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700;">
                        💾 Enregistrer les Constantes de Jeu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section 2 : Système d'IA & Colonisation des Bots -->
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

    <!-- Section 3 : Gestion des Daimyōs Joueurs -->
    <div class="card" style="border-color: rgba(220, 38, 38, 0.2);">
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

    <!-- Section 4 : 🎖️ Tableau d'Honneur & Clôture Hebdomadaire des Médailles -->
    <div class="card" style="margin-top: 2rem; border-color: rgba(234, 179, 8, 0.3); background: rgba(18, 16, 25, 0.85);">
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

    <!-- Section 5 : 🗾 Arpenteur du Shogunat & Expansion des Provinces -->
    <div class="card" style="margin-top: 2rem; margin-bottom: 2rem; border-color: rgba(52, 211, 153, 0.3);">
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

    <!-- Section 6 : 🏯 Sanctuaires des 12 Donjons Authentiques du Japon (現存十二天守) -->
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
                                            <a href="/?page=galaxy&x=<?= $curX ?>&y=<?= $curY ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;" title="Voir sur la carte">
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

    <!-- Section 7 : ⚠️ Décret Suprême - Réinitialisation Complète du Monde Féodal -->
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

<script>
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
</script>

