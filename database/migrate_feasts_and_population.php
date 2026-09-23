<?php
/**
 * Migration : Création de la table planet_feasts, ajout de la population et du coût en farine des unités
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== Début de la migration : Banquets, Démographie et Ravitaillement Farine ===\n";

try {
    $db = Database::getConnection();

    // 1. Table planet_feasts
    echo "1. Création de la table 'planet_feasts'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `planet_feasts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `planet_id` INT UNSIGNED NOT NULL,
            `feast_type` VARCHAR(40) NOT NULL,
            `tenshu_level` INT UNSIGNED NOT NULL DEFAULT 1,
            `started_at` INT UNSIGNED NOT NULL,
            `finishes_at` INT UNSIGNED NOT NULL,
            INDEX `idx_pf_planet` (`planet_id`),
            INDEX `idx_pf_finishes` (`finishes_at`),
            CONSTRAINT `fk_pf_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   [OK] Table 'planet_feasts' prête.\n";

    // 2. Colonne population sur planets
    echo "2. Ajout de la colonne 'population' dans la table 'planets'...\n";
    $stmt = $db->query("SHOW COLUMNS FROM `planets` LIKE 'population'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE `planets` ADD COLUMN `population` INT UNSIGNED NOT NULL DEFAULT 100 AFTER `deuterium_max`");
        echo "   [OK] Colonne 'population' ajoutée avec succès.\n";
    } else {
        echo "   [INFO] La colonne 'population' existe déjà.\n";
    }

    // 3. Colonne rice_flour_cost sur units
    echo "3. Ajout de la colonne 'rice_flour_cost' dans la table 'units'...\n";
    $stmt = $db->query("SHOW COLUMNS FROM `units` LIKE 'rice_flour_cost'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE `units` ADD COLUMN `rice_flour_cost` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `deuterium_cost`");
        echo "   [OK] Colonne 'rice_flour_cost' ajoutée avec succès.\n";
    } else {
        echo "   [INFO] La colonne 'rice_flour_cost' existe déjà.\n";
    }

    // 4. Initialisation des coûts en farine selon le Tier des unités
    echo "4. Mise à jour des coûts de farine des unités par palier...\n";
    $db->exec("UPDATE `units` SET `rice_flour_cost` = 0 WHERE `tier` = 1");
    $db->exec("UPDATE `units` SET `rice_flour_cost` = 15 WHERE `tier` = 2 AND `faction` != 'all'");
    $db->exec("UPDATE `units` SET `rice_flour_cost` = 35 WHERE `tier` = 3 AND `faction` != 'all'");
    $db->exec("UPDATE `units` SET `rice_flour_cost` = 75 WHERE `tier` = 4 AND `faction` != 'all'");
    echo "   [OK] Coûts de farine initialisés pour les unités d'élite.\n";

    echo "=== Migration terminée avec succès ! ===\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    // Si la base MySQL est éteinte, on affiche l'information calmement
    echo "Note : Si le serveur de base de données MySQL est arrêté, les moteurs de jeu intègrent un mode de repli défensif.\n";
}

