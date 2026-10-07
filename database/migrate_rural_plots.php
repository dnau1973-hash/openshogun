<?php
/**
 * Migration : Table planet_rural_plots et initialisation procédurale des 9 parcelles par village
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/VillageGeneratorService.php';

echo "=== Migration : Table planet_rural_plots (9 Parcelles Uniques) ===\n";

try {
    $db = Database::getConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS `planet_rural_plots` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `planet_id` INT UNSIGNED NOT NULL,
        `slot_id` TINYINT UNSIGNED NOT NULL,
        `structure_type` VARCHAR(32) NOT NULL,
        `level` INT UNSIGNED NOT NULL DEFAULT 1,
        `max_level` INT UNSIGNED NOT NULL DEFAULT 30,
        `pos_x` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `pos_y` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
        `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
        `prod_hourly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_planet_slot` (`planet_id`, `slot_id`),
        UNIQUE KEY `uniq_planet_structure` (`planet_id`, `structure_type`),
        KEY `idx_planet_id` (`planet_id`),
        CONSTRAINT `fk_rural_plots_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "[OK] Table `planet_rural_plots` créée ou déjà existante.\n";

    // Initialisation procédurale de tous les villages existants
    $generator = new VillageGeneratorService($db);
    $count = $generator->ensureAllPlanetsInitialized();
    echo "[OK] $count village(s) vérifié(s) / initialisé(s) avec leurs 9 parcelles procédurales.\n";

} catch (Exception $e) {
    echo "[INFO] Migration hors ligne / exception : " . $e->getMessage() . "\n";
}
