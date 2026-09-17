<?php
/**
 * Vue du Samouraï Héros Champion (OpenShogun - Style Travian)
 */
require_once __DIR__ . '/../core/HeroEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$heroEngine = new HeroEngine();
$planetEngine = new PlanetEngine();

$hero = $heroEngine->getHeroByUserId((int)$user['id']);
if (!$hero) {
    // Si pour une raison quelconque le héros n'est pas encore initialisé
    $heroName = "Samouraï " . ucfirst($user['username']);
    $db = Database::getConnection();
    $db->prepare("
        INSERT IGNORE INTO heroes 
        (user_id, current_planet_id, name, level, experience, health, status, last_health_update, unassigned_points) 
        VALUES (?, ?, ?, 0, 0, 100.0, 'home', UNIX_TIMESTAMP(), 4)
    ")->execute([(int)$user['id'], (int)$planet['id'], $heroName]);
    $hero = $heroEngine->getHeroByUserId((int)$user['id']);
}

$adventures = $heroEngine->getAdventures((int)$user['id']);
$inventory = $heroEngine->getInventory((int)$user['id']);
$activeTab = $_GET['tab'] ?? 'attributes';

$factionIcons = [
    'terran' => '🏯',
    'vorash' => '🐎',
    'aethelis' => '⛩️'
];
$fIcon = $factionIcons[$user['faction']] ?? '⚔️';

$health = round((float)$hero['health']);
$healthColor = ($health >= 60) ? '#22c55e' : (($health >= 25) ? '#eab308' : '#ef4444');

$statusLabels = [
    'home' => ['label' => 'Au Domaine (Garnison)', 'color' => '#22c55e', 'icon' => '🏯'],
    'mission' => ['label' => 'En Marche Militaire', 'color' => '#3b82f6', 'icon' => '🚩'],
    'adventure' => ['label' => 'En Aventure Féodale', 'color' => '#a855f7', 'icon' => '🗺️'],
    'dead' => ['label' => 'Tombé au Champ d\'Honneur', 'color' => '#ef4444', 'icon' => '💀'],
    'reviving' => ['label' => 'Rituel de Réanimation en cours', 'color' => '#facc15', 'icon' => '✨']
];
$st = $statusLabels[$hero['status']] ?? ['label' => 'Inconnu', 'color' => '#94a3b8', 'icon' => '❓'];
?>

<div class="grid-main" style="max-width: 1300px; margin: 0 auto;">

    <!-- Carte d'Identité & Tableau de Bord du Samouraï Héros -->
    <div class="card" style="border-top: 4px solid var(--red-primary, #dc2626); margin-bottom: 1.5rem;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
                
                <!-- Portrait et Blason du Champion -->
                <div style="text-align: center; min-width: 140px;">
                    <div style="width: 120px; height: 120px; margin: 0 auto; background: linear-gradient(135deg, rgba(220,38,38,0.2) 0%, rgba(15,23,42,0.9) 100%); border: 2px solid #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 4rem; box-shadow: 0 0 25px rgba(220,38,38,0.4); filter: drop-shadow(0 4px 10px rgba(0,0,0,0.6));">
                        <?= $fIcon ?>
                    </div>
                    <span class="faction-badge <?= htmlspecialchars($user['faction']) ?>" style="margin-top: 0.6rem; display: inline-block;">
                        <?= htmlspecialchars($hero['name']) ?>
                    </span>
                </div>

                <!-- Informations, Vitalité & Progression -->
                <div style="flex: 1; min-width: 300px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <div>
                            <h1 style="margin: 0; font-size: 1.6rem; font-weight: 900; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
                                <?= htmlspecialchars($hero['name']) ?>
                                <span style="font-size: 0.9rem; color: #facc15; font-weight: 700; background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; padding: 2px 8px; border-radius: 6px;">
                                    Niveau <?= $hero['level'] ?>
                                </span>
                            </h1>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                Fief d'attache : <strong><?= htmlspecialchars($hero['planet_name']) ?></strong> [<?= $hero['coord_x'] ?> : <?= $hero['coord_y'] ?>]
                            </div>
                        </div>

                        <!-- Badge de Statut Dynamique -->
                        <div>
                            <span class="badge" style="background: rgba(0,0,0,0.5); border: 1px solid <?= $st['color'] ?>; color: <?= $st['color'] ?>; font-size: 0.85rem; padding: 0.35rem 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;">
                                <span><?= $st['icon'] ?></span> <?= $st['label'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- Barre de Santé (Vitalité) -->
                    <div style="margin: 0.75rem 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <span style="color: #cbd5e1;">❤️ Vitalité & Santé du Samouraï :</span>
                            <span style="color: <?= $healthColor ?>; font-family: monospace; font-size: 0.9rem;"><?= $health ?>%</span>
                        </div>
                        <div style="height: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; overflow: hidden; border: 1px solid rgba(255,255,255,0.15);">
                            <div style="height: 100%; width: <?= $health ?>%; background: <?= $healthColor ?>; transition: width 0.4s ease; box-shadow: 0 0 10px <?= $healthColor ?>;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; display: flex; justify-content: space-between;">
                            <span>Régénération passive : +15% / 24h</span>
                            <?php if ($hero['status'] === 'dead'): ?>
                                <span style="color: #ef4444; font-weight: 700;">⚠️ Le héros est tombé au combat. Accomplissez le rituel de réanimation.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Barre d'Expérience (XP) -->
                    <div style="margin: 0.75rem 0 0 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.25rem;">
                            <span style="color: #facc15;">⭐ Progression vers Niveau <?= $hero['level'] + 1 ?> :</span>
                            <span style="color: #facc15; font-family: monospace; font-size: 0.85rem;">
                                <?= number_format($hero['experience']) ?> / <?= number_format($hero['xp_next_level']) ?> XP (<?= $hero['xp_progress_percent'] ?>%)
                            </span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?= $hero['xp_progress_percent'] ?>%; background: linear-gradient(90deg, #eab308 0%, #ca8a04 100%); transition: width 0.4s ease;"></div>
                        </div>
                    </div>

                </div>

                <!-- Bouton de Résurrection si le Héros est mort -->
                <?php if ($hero['status'] === 'dead'): ?>
                    <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; border-radius: 8px; padding: 1rem; text-align: center; min-width: 220px;">
                        <div style="font-size: 0.85rem; color: #fca5a5; font-weight: 700; margin-bottom: 0.5rem;">
                            💀 Samouraï Tombé
                        </div>
                        <p style="font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.75rem;">
                            Invoquez les esprits protecteurs pour rappeler votre champion à la vie.
                        </p>
                        <button type="button" onclick="executeReviveHero(<?= (int)$planet['id'] ?>)" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c; font-size: 0.85rem; font-weight: 800; width: 100%;">
                            ✨ Rituel de Résurrection
                        </button>
                    </div>
                <?php elseif ($hero['status'] === 'reviving'): ?>
                    <div style="background: rgba(234, 179, 8, 0.12); border: 1px solid #eab308; border-radius: 8px; padding: 1rem; text-align: center; min-width: 220px;">
                        <div style="font-size: 0.85rem; color: #facc15; font-weight: 700; margin-bottom: 0.5rem;">
                            ✨ Réanimation en cours
                        </div>
                        <div style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #fff;" data-countdown="<?= $hero['revive_finish_time'] ?>">
                            Calcul...
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Navigation par Onglets (Caractéristiques, Aventures, Arsenal) -->
        <div class="card-header" style="background: rgba(10, 15, 29, 0.95); border-top: 1px solid rgba(255,255,255,0.06); padding: 0.5rem 1.5rem; display: flex; gap: 0.5rem;">
            <a href="?page=hero&tab=attributes" class="btn <?= ($activeTab === 'attributes') ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
                🥋 Compétences & Attributs
            </a>
            <a href="?page=hero&tab=adventures" class="btn <?= ($activeTab === 'adventures') ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem; display: flex; align-items: center; gap: 0.4rem;">
                🗺️ Aventures Provinciales
                <?php if (count($adventures) > 0): ?>
                    <span class="badge" style="background: #a855f7; color: #fff; font-size: 0.7rem; padding: 1px 6px; border-radius: 10px;"><?= count($adventures) ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=hero&tab=inventory" class="btn <?= ($activeTab === 'inventory') ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem; display: flex; align-items: center; gap: 0.4rem;">
                🗡️ Arsenal & Reliques
                <?php if (count($inventory) > 0): ?>
                    <span class="badge" style="background: #eab308; color: #000; font-size: 0.7rem; padding: 1px 6px; border-radius: 10px; font-weight: 800;"><?= count($inventory) ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- CONTENU ONGLET 1 : ATTRIBUTS & COMPÉTENCES (STYLE TRAVIAN) -->
    <?php if ($activeTab === 'attributes'): ?>
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 class="card-title" style="margin: 0; font-size: 1.15rem;">
                        ⚡ Points de Compétences Féodales
                    </h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Répartissez vos 4 points gagnés à chaque niveau entre les 4 vertus de commandement du Samouraï.
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.85rem; font-weight: 800; color: #facc15;">
                        Points disponibles : <span id="unassignedDisplay"><?= $hero['unassigned_points'] ?></span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form id="heroAttributesForm" onsubmit="event.preventDefault(); submitAttributes();">
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        
                        <!-- 1. Force de Combat -->
                        <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1; min-width: 260px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.5rem;">⚔️</span>
                                    <strong style="color: #fff; font-size: 1rem;">Force de Combat Personnelle</strong>
                                </div>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4;">
                                    Augmente la puissance d'attaque et de défense pure du Samouraï en personne (+80 pts de puissance par point d'attribut).
                                </p>
                                <div style="margin-top: 0.35rem; font-size: 0.85rem; color: #60a5fa; font-weight: 700;">
                                    Puissance actuelle : <span id="effectiveStrength"><?= number_format($hero['effective']['combat_strength']) ?></span>
                                    <?php if ($hero['effective']['equipment_strength'] > 0): ?>
                                        <span style="color: #facc15; font-size: 0.75rem;">(+<?= $hero['effective']['equipment_strength'] ?> via armes)</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.1rem; font-weight: 800; color: #fff; min-width: 40px; text-align: center;" id="baseStrengthPts">
                                    <?= $hero['stat_strength'] ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('strength', -1)">-</button>
                                    <span style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #4ade80; min-width: 30px; text-align: center;" id="add-strength">0</span>
                                    <button type="button" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('strength', 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Bonus d'Attaque (Offense %) -->
                        <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1; min-width: 260px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.5rem;">🏹</span>
                                    <strong style="color: #fff; font-size: 1rem;">Bonus Offensif de l'Armée</strong>
                                </div>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4;">
                                    Augmente la force d'attaque de TOUTES les troupes qui accompagnent le héros au combat (+0.2% par point, maximum +20%).
                                </p>
                                <div style="margin-top: 0.35rem; font-size: 0.85rem; color: #f87171; font-weight: 700;">
                                    Bonus offensif actuel : <span id="effectiveOffense">+<?= $hero['effective']['offense_bonus_pct'] ?>%</span>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.1rem; font-weight: 800; color: #fff; min-width: 40px; text-align: center;" id="baseOffensePts">
                                    <?= $hero['stat_offense_bonus'] ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('offense', -1)">-</button>
                                    <span style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #4ade80; min-width: 30px; text-align: center;" id="add-offense">0</span>
                                    <button type="button" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('offense', 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Bonus de Défense (Défense %) -->
                        <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div style="flex: 1; min-width: 260px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.5rem;">🛡️</span>
                                    <strong style="color: #fff; font-size: 1rem;">Bonus Défensif de la Garnison</strong>
                                </div>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4;">
                                    Augmente la force de défense de toutes les troupes stationnées dans le domaine où réside le héros (+0.2% par point, maximum +20%).
                                </p>
                                <div style="margin-top: 0.35rem; font-size: 0.85rem; color: #34d399; font-weight: 700;">
                                    Bonus défensif actuel : <span id="effectiveDefense">+<?= $hero['effective']['defense_bonus_pct'] ?>%</span>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.1rem; font-weight: 800; color: #fff; min-width: 40px; text-align: center;" id="baseDefensePts">
                                    <?= $hero['stat_defense_bonus'] ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 0.35rem;">
                                    <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('defense', -1)">-</button>
                                    <span style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #4ade80; min-width: 30px; text-align: center;" id="add-defense">0</span>
                                    <button type="button" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('defense', 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Production Féodale du Domaine -->
                        <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
                                <div style="flex: 1; min-width: 260px;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="font-size: 1.5rem;">🌾</span>
                                        <strong style="color: #fff; font-size: 1rem;">Ressources & Prospérité du Fief</strong>
                                    </div>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4;">
                                        Le Samouraï veille sur les récoltes et artisans du fief. Augmente la production horaire du domaine.
                                    </p>
                                    <div style="margin-top: 0.35rem; font-size: 0.85rem; color: #facc15; font-weight: 700;">
                                        Apport actuel : 
                                        +<?= $hero['effective']['hourly_production']['metal'] ?> 🪵 Bois, 
                                        +<?= $hero['effective']['hourly_production']['crystal'] ?> 🪨 Pierre, 
                                        +<?= $hero['effective']['hourly_production']['deuterium'] ?> 🌾 Riz / h
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.1rem; font-weight: 800; color: #fff; min-width: 40px; text-align: center;" id="baseProdPts">
                                        <?= $hero['stat_production'] ?>
                                    </span>
                                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                                        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('production', -1)">-</button>
                                        <span style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #4ade80; min-width: 30px; text-align: center;" id="add-production">0</span>
                                        <button type="button" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-weight: 800;" onclick="adjustPoint('production', 1)">+</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Choix de l'orientation de production -->
                            <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 0.75rem; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700;">Orientation de récolte :</span>
                                
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer;">
                                    <input type="radio" name="prod_type" value="balanced" <?= ($hero['production_type'] === 'balanced') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                    <span>⚖️ Équilibrée (Tous)</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer;">
                                    <input type="radio" name="prod_type" value="metal" <?= ($hero['production_type'] === 'metal') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                    <span style="color: var(--res-metal);">🪵 Bois de Cèdre pur</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer;">
                                    <input type="radio" name="prod_type" value="crystal" <?= ($hero['production_type'] === 'crystal') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                    <span style="color: var(--res-crystal);">🪨 Pierre de Taille pure</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer;">
                                    <input type="radio" name="prod_type" value="deuterium" <?= ($hero['production_type'] === 'deuterium') ? 'checked' : '' ?> onchange="changeProductionType(this.value)">
                                    <span style="color: var(--res-deut);">🌾 Riz Impérial pur</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <!-- Bouton de confirmation des points -->
                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
                        <button type="button" class="btn btn-secondary" onclick="resetPoints()">Réinitialiser</button>
                        <button type="submit" class="btn btn-primary" id="savePointsBtn" style="padding: 0.6rem 1.5rem; font-weight: 800;">
                            ✨ Enregistrer les Attributs du Samouraï
                        </button>
                    </div>

                </form>
            </div>
        </div>

    <!-- CONTENU ONGLET 2 : AVENTURES FÉODALES (STYLE TRAVIAN) -->
    <?php elseif ($activeTab === 'adventures'): ?>
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 class="card-title" style="margin: 0; font-size: 1.15rem;">
                        🗺️ Expéditions & Aventures du Samouraï
                    </h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Envoyez votre Samouraï explorer les sanctuaires oubliés et ruines antiques pour acquérir de l'XP, du butin et des reliques.
                    </div>
                </div>
            </div>

            <div class="card-body">
                <?php if (empty($adventures)): ?>
                    <div style="text-align: center; padding: 2.5rem 1rem; background: rgba(0,0,0,0.2); border-radius: 8px;">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📜</div>
                        <h3 style="color: #fff; font-size: 1.1rem; margin: 0 0 0.5rem 0;">Aucune aventure n'est disponible pour l'instant</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; max-width: 480px; margin: 0 auto;">
                            De nouvelles rumeurs et pistes d'aventures apparaissent régulièrement à mesure que votre clan s'étend et que vos éclaireurs sillonnent les provinces.
                        </p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1rem;">
                        <?php foreach ($adventures as $adv): ?>
                            <?php 
                                $diffBadges = [
                                    'easy' => ['label' => 'Difficulté : Faible', 'color' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.1)'],
                                    'medium' => ['label' => 'Difficulté : Moyenne', 'color' => '#eab308', 'bg' => 'rgba(234, 179, 8, 0.1)'],
                                    'hard' => ['label' => 'Difficulté : Périlleuse', 'color' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)']
                                ];
                                $dbdg = $diffBadges[$adv['difficulty']] ?? $diffBadges['easy'];
                                $canStart = ($hero['status'] === 'home' && $hero['health'] >= 15.0);
                            ?>
                            <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                                <div style="padding: 1.25rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <span class="badge" style="background: <?= $dbdg['bg'] ?>; border: 1px solid <?= $dbdg['color'] ?>; color: <?= $dbdg['color'] ?>; font-size: 0.75rem; padding: 0.2rem 0.5rem; font-weight: 700;">
                                            <?= $dbdg['label'] ?>
                                        </span>
                                        <span style="font-family: monospace; font-size: 0.8rem; color: #facc15; font-weight: 700;">
                                            [<?= $adv['coord_x'] ?> : <?= $adv['coord_y'] ?>]
                                        </span>
                                    </div>

                                    <h3 style="color: #fff; font-size: 1.05rem; font-weight: 800; margin: 0 0 0.5rem 0;">
                                        ⛩️ <?= htmlspecialchars($adv['name']) ?>
                                    </h3>

                                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8; margin-top: 0.75rem; background: rgba(0,0,0,0.3); padding: 0.5rem 0.75rem; border-radius: 6px;">
                                        <span>Distance : <strong><?= $adv['distance'] ?></strong> lieues</span>
                                        <span>Marche : <strong><?= gmdate('H:i:s', $adv['duration']) ?></strong></span>
                                    </div>
                                </div>

                                <div style="background: rgba(10, 15, 29, 0.9); border-top: 1px solid rgba(255,255,255,0.05); padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.75rem; color: #cbd5e1;">Gains : XP, Vivres ou Reliques</span>
                                    <?php if ($canStart): ?>
                                        <button type="button" onclick="executeStartAdventure(<?= (int)$adv['id'] ?>)" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.35rem 0.9rem; font-weight: 800;">
                                            Partir en Aventure &rarr;
                                        </button>
                                    <?php else: ?>
                                        <button type="button" disabled class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; opacity: 0.6;" title="Le Samouraï doit être au domaine avec au moins 15% de santé">
                                            Indisponible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- CONTENU ONGLET 3 : ARSENAL & RELIQUES (INVENTAIRE) -->
    <?php elseif ($activeTab === 'inventory'): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="margin: 0; font-size: 1.15rem;">
                    🗡️ Arsenal & Reliques Ancestrales
                </h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Équipez votre Samouraï des armes légendaires et talismans découverts au cours de ses explorations.
                </div>
            </div>

            <div class="card-body">
                <!-- 5 Emplacements d'équipements actifs -->
                <h3 style="font-size: 0.95rem; color: #fff; margin-bottom: 0.75rem;">🥋 Équipement Actuel du Héros</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 2rem;">
                    
                    <?php 
                        $slots = [
                            'weapon' => ['label' => 'Arme de Poing', 'icon' => '🗡️', 'field' => 'equipped_weapon'],
                            'helmet' => ['label' => 'Casque Kabuto', 'icon' => '🪖', 'field' => 'equipped_helmet'],
                            'armor' => ['label' => 'Armure O-Yoroi', 'icon' => '🥋', 'field' => 'equipped_armor'],
                            'horse' => ['label' => 'Monture & Destrier', 'icon' => '🐎', 'field' => 'equipped_horse'],
                            'talisman' => ['label' => 'Talisman Shintō', 'icon' => '📿', 'field' => 'equipped_talisman']
                        ];
                    ?>
                    <?php foreach ($slots as $slotKey => $sl): ?>
                        <?php 
                            $eqCode = $hero[$sl['field']];
                            $equippedItem = null;
                            if ($eqCode) {
                                foreach ($inventory as $invItem) {
                                    if ($invItem['item_code'] === $eqCode && !empty($invItem['is_equipped'])) {
                                        $equippedItem = $invItem;
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid <?= $equippedItem ? '#eab308' : 'rgba(255,255,255,0.08)' ?>; border-radius: 8px; padding: 0.85rem; text-align: center;">
                            <div style="font-size: 1.8rem; margin-bottom: 0.25rem; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));">
                                <?= $sl['icon'] ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">
                                <?= $sl['label'] ?>
                            </div>
                            <?php if ($equippedItem): ?>
                                <div style="font-size: 0.85rem; font-weight: 800; color: #facc15; margin: 0.35rem 0;">
                                    <?= htmlspecialchars($equippedItem['name']) ?>
                                </div>
                                <button type="button" onclick="executeUnequip('<?= $slotKey ?>')" class="btn btn-secondary" style="font-size: 0.7rem; padding: 0.2rem 0.6rem;">
                                    Déséquiper
                                </button>
                            <?php else: ?>
                                <div style="font-size: 0.8rem; color: #64748b; font-style: italic; margin: 0.35rem 0;">
                                    Emplacement vide
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grille d'inventaire disponible -->
                <h3 style="font-size: 0.95rem; color: #fff; margin-bottom: 0.75rem;">📦 Coffre & Reliques Collectées</h3>
                <?php if (empty($inventory)): ?>
                    <div style="text-align: center; padding: 1.5rem; background: rgba(0,0,0,0.2); border-radius: 8px; color: var(--text-muted); font-size: 0.85rem;">
                        Votre coffre de reliques est vide. Envoyez votre Samouraï en aventure pour découvrir des katanas légendaires et des cuirasses impériales !
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem;">
                        <?php foreach ($inventory as $it): ?>
                            <div style="background: rgba(15,23,42,0.6); border: 1px solid <?= !empty($it['is_equipped']) ? '#22c55e' : 'rgba(255,255,255,0.08)' ?>; border-radius: 8px; padding: 0.85rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <strong style="color: #fff; font-size: 0.88rem;"><?= htmlspecialchars($it['name']) ?></strong>
                                        <?php if (!empty($it['is_equipped'])): ?>
                                            <span style="background: #22c55e; color: #fff; font-size: 0.65rem; padding: 1px 4px; border-radius: 3px; font-weight: 800;">ÉQUIPÉ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($it['description']) ?>
                                    </div>
                                </div>
                                <div>
                                    <?php if (empty($it['is_equipped'])): ?>
                                        <button type="button" onclick="executeEquip(<?= (int)$it['id'] ?>)" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.6rem; font-weight: 700;">
                                            Équiper
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Gestion de l'attribution des points
let unassignedAvailable = <?= (int)$hero['unassigned_points'] ?>;
const pointsToAdd = {
    strength: 0,
    offense: 0,
    defense: 0,
    production: 0
};

function adjustPoint(stat, delta) {
    if (delta > 0) {
        if (unassignedAvailable <= 0) return;
        pointsToAdd[stat]++;
        unassignedAvailable--;
    } else if (delta < 0) {
        if (pointsToAdd[stat] <= 0) return;
        pointsToAdd[stat]--;
        unassignedAvailable++;
    }
    updatePointsUI();
}

function resetPoints() {
    unassignedAvailable = <?= (int)$hero['unassigned_points'] ?>;
    pointsToAdd.strength = 0;
    pointsToAdd.offense = 0;
    pointsToAdd.defense = 0;
    pointsToAdd.production = 0;
    updatePointsUI();
}

function updatePointsUI() {
    document.getElementById('unassignedDisplay').innerText = unassignedAvailable;
    document.getElementById('add-strength').innerText = pointsToAdd.strength;
    document.getElementById('add-offense').innerText = pointsToAdd.offense;
    document.getElementById('add-defense').innerText = pointsToAdd.defense;
    document.getElementById('add-production').innerText = pointsToAdd.production;

    // Prévisualisation des stats
    const baseStr = <?= (int)$hero['stat_strength'] ?>;
    const baseOff = <?= (int)$hero['stat_offense_bonus'] ?>;
    const baseDef = <?= (int)$hero['stat_defense_bonus'] ?>;
    const eqStrength = <?= (int)$hero['effective']['equipment_strength'] ?>;

    const newCombatStr = 150 + ((baseStr + pointsToAdd.strength) * 80) + eqStrength;
    const newOffPct = Math.min(20, (baseOff + pointsToAdd.offense) * 0.2).toFixed(1);
    const newDefPct = Math.min(20, (baseDef + pointsToAdd.defense) * 0.2).toFixed(1);

    document.getElementById('effectiveStrength').innerText = newCombatStr.toLocaleString();
    document.getElementById('effectiveOffense').innerText = '+' + newOffPct + '%';
    document.getElementById('effectiveDefense').innerText = '+' + newDefPct + '%';
}

async function submitAttributes() {
    const total = pointsToAdd.strength + pointsToAdd.offense + pointsToAdd.defense + pointsToAdd.production;
    if (total <= 0) {
        showModalAlert("Attributs", "Veuillez attribuer au moins un point d'attribut à votre Samouraï.", "warning");
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'allocate_points');
        formData.append('strength', pointsToAdd.strength);
        formData.append('offense', pointsToAdd.offense);
        formData.append('defense', pointsToAdd.defense);
        formData.append('production', pointsToAdd.production);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Points de Samouraï Attribués", data.message, "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'enregistrer les attributs.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", "Une erreur est survenue lors de l'enregistrement.", "danger");
    }
}

async function changeProductionType(type) {
    try {
        const formData = new FormData();
        formData.append('action', 'set_production_type');
        formData.append('production_type', type);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Orientation Modifiée", data.message, "success");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert("Erreur", data.error || "Impossible de modifier la production.", "danger");
        }
    } catch (e) {
        console.error("Erreur orientation:", e);
    }
}

async function executeStartAdventure(advId) {
    try {
        const formData = new FormData();
        formData.append('action', 'start_adventure');
        formData.append('adventure_id', advId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Départ en Aventure Féodale", data.message, "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert("Aventure Impossible", data.error || "Le Samouraï ne peut pas partir.", "warning");
        }
    } catch (e) {
        showModalAlert("Erreur", "Une erreur est survenue lors du départ.", "danger");
    }
}

async function executeReviveHero(planetId) {
    try {
        const formData = new FormData();
        formData.append('action', 'revive_hero');
        formData.append('planet_id', planetId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Rituel Sacré Lancé", data.message, "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert("Rituel Impossible", data.error || "Impossible de ressusciter le héros.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", "Une erreur est survenue lors du rituel.", "danger");
    }
}

async function executeEquip(itemId) {
    try {
        const formData = new FormData();
        formData.append('action', 'equip_item');
        formData.append('item_id', itemId);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Arsenal Modifié", data.message, "success");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'équiper cet objet.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", "Erreur lors de l'équipement.", "danger");
    }
}

async function executeUnequip(slot) {
    try {
        const formData = new FormData();
        formData.append('action', 'unequip_item');
        formData.append('slot', slot);

        const res = await fetch('/api/hero.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalAlert("Arsenal Modifié", data.message, "success");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert("Erreur", data.error || "Impossible de déséquiper.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", "Erreur lors du déséquipement.", "danger");
    }
}
</script>
