<?php
/**
 * Migration : Système de Héros Samouraï (OpenShogun)
 * Tables heroes, hero_adventures, hero_inventory et mise à jour de fleet_missions
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION DU SYSTÈME DE HÉROS SAMOURAÏ ===\n";

$db = Database::getConnection();

try {
    // 1. Table `heroes`
    echo "1. Création de la table 'heroes'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `heroes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL UNIQUE,
            `current_planet_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(60) NOT NULL DEFAULT 'Samouraï Champion',
            `level` INT UNSIGNED NOT NULL DEFAULT 0,
            `experience` INT UNSIGNED NOT NULL DEFAULT 0,
            `health` FLOAT NOT NULL DEFAULT 100.0,
            `status` ENUM('home', 'mission', 'adventure', 'dead', 'reviving') NOT NULL DEFAULT 'home',
            `revive_finish_time` INT UNSIGNED NULL DEFAULT NULL,
            `last_health_update` INT UNSIGNED NOT NULL DEFAULT 0,
            `unassigned_points` INT UNSIGNED NOT NULL DEFAULT 4,
            `stat_strength` INT UNSIGNED NOT NULL DEFAULT 0,
            `stat_offense_bonus` INT UNSIGNED NOT NULL DEFAULT 0,
            `stat_defense_bonus` INT UNSIGNED NOT NULL DEFAULT 0,
            `stat_production` INT UNSIGNED NOT NULL DEFAULT 0,
            `production_type` ENUM('balanced', 'metal', 'crystal', 'deuterium') NOT NULL DEFAULT 'balanced',
            `equipped_weapon` VARCHAR(50) NULL DEFAULT NULL,
            `equipped_helmet` VARCHAR(50) NULL DEFAULT NULL,
            `equipped_armor` VARCHAR(50) NULL DEFAULT NULL,
            `equipped_horse` VARCHAR(50) NULL DEFAULT NULL,
            `equipped_talisman` VARCHAR(50) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_heroes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_heroes_planet` FOREIGN KEY (`current_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Table `hero_adventures`
    echo "2. Création de la table 'hero_adventures'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `hero_adventures` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `coord_x` INT NOT NULL,
            `coord_y` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
            `status` ENUM('available', 'in_progress', 'completed', 'expired') NOT NULL DEFAULT 'available',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` INT UNSIGNED NULL DEFAULT NULL,
            KEY `idx_ha_user` (`user_id`),
            KEY `idx_ha_status` (`status`),
            CONSTRAINT `fk_ha_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Table `hero_inventory`
    echo "3. Création de la table 'hero_inventory'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `hero_inventory` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `item_code` VARCHAR(50) NOT NULL,
            `item_type` ENUM('weapon', 'helmet', 'armor', 'horse', 'talisman', 'consumable') NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            `bonus_data` JSON NOT NULL,
            `is_equipped` TINYINT(1) NOT NULL DEFAULT 0,
            `acquired_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_hi_user` (`user_id`),
            CONSTRAINT `fk_hi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Mettre à jour la table `fleet_missions`
    echo "4. Mise à jour de la table 'fleet_missions'...\n";
    $cols = $db->query("SHOW COLUMNS FROM `fleet_missions` LIKE 'has_hero'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE `fleet_missions` ADD COLUMN `has_hero` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cargo_data`");
        echo "   + Colonne 'has_hero' ajoutée à fleet_missions.\n";
    }

    $colsAdv = $db->query("SHOW COLUMNS FROM `fleet_missions` LIKE 'adventure_id'")->fetchAll();
    if (empty($colsAdv)) {
        $db->exec("
            ALTER TABLE `fleet_missions` 
            ADD COLUMN `adventure_id` INT UNSIGNED NULL DEFAULT NULL AFTER `target_oasis_id`,
            ADD CONSTRAINT `fk_fleet_adventure` FOREIGN KEY (`adventure_id`) REFERENCES `hero_adventures`(`id`) ON DELETE SET NULL
        ");
        echo "   + Colonne 'adventure_id' ajoutée à fleet_missions.\n";
    }

    // Mettre à jour l'enum mission_type pour inclure 'adventure'
    $db->exec("
        ALTER TABLE `fleet_missions` 
        MODIFY `mission_type` ENUM('attack', 'raid', 'transport', 'spy', 'colonize', 'occupy', 'adventure') NOT NULL;
    ");
    echo "   + Support de 'adventure' dans fleet_missions.mission_type.\n";

    // 5. Initialiser un Samouraï Héros pour tous les utilisateurs existants
    echo "5. Initialisation des Héros pour les utilisateurs existants...\n";
    $stmtUsers = $db->query("
        SELECT u.id, u.username, u.faction, p.id as planet_id, p.coord_x, p.coord_y
        FROM users u 
        LEFT JOIN planets p ON p.user_id = u.id AND p.is_capital = 1
    ");

    $insertHero = $db->prepare("
        INSERT IGNORE INTO heroes 
        (user_id, current_planet_id, name, level, experience, health, status, last_health_update, unassigned_points) 
        VALUES (?, ?, ?, 0, 0, 100.0, 'home', UNIX_TIMESTAMP(), 4)
    ");

    $insertAdv = $db->prepare("
        INSERT INTO hero_adventures (user_id, coord_x, coord_y, name, difficulty, status) 
        VALUES (?, ?, ?, ?, ?, 'available')
    ");

    $advTemplates = [
        ['name' => 'Sanctuaire Shintō Abandonné dans la Forêt', 'diff' => 'easy', 'dx' => 2, 'dy' => 3],
        ['name' => 'Ruines d\'un Vieux Donjon Fief Noir', 'diff' => 'easy', 'dx' => -3, 'dy' => 2],
        ['name' => 'Gorge Brumeuse et Repaire de Ronins', 'diff' => 'medium', 'dx' => 4, 'dy' => -3]
    ];

    $createdHeroes = 0;
    while ($u = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
        if (!$u['planet_id']) continue;

        $heroName = "Samouraï " . ucfirst($u['username']);
        $insertHero->execute([$u['id'], $u['planet_id'], $heroName]);
        if ($insertHero->rowCount() > 0) {
            $createdHeroes++;

            // Générer 3 aventures initiales
            foreach ($advTemplates as $t) {
                $advX = $u['coord_x'] + $t['dx'];
                $advY = $u['coord_y'] + $t['dy'];
                $insertAdv->execute([$u['id'], $advX, $advY, $t['name'], $t['diff']]);
            }
        }
    }
    echo "   + {$createdHeroes} héros créé(s) avec succès.\n";

    echo "=== MIGRATION HÉROS SAMOURAÏ TERMINÉE AVEC SUCCÈS ===\n";

} catch (Exception $e) {
    echo "Erreur lors de la migration du système de héros: " . $e->getMessage() . "\n";
    exit(1);
}

