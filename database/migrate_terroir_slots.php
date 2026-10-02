<?php
/**
 * Migration : Table planet_terroir_slots pour la persistance des 5 slots de terroir
 * (Argile, Thé, Soja, Village, et extensions rurales)
 */

require_once __DIR__ . '/../core/Database.php';

echo "=== Migration : planet_terroir_slots ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "Erreur de connexion DB : " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $sql = "CREATE TABLE IF NOT EXISTS `planet_terroir_slots` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `planet_id` INT UNSIGNED NOT NULL,
        `resource_type` VARCHAR(32) NOT NULL,
        `slot_index` TINYINT UNSIGNED NOT NULL,
        `level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
        `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_planet_res_slot` (`planet_id`, `resource_type`, `slot_index`),
        KEY `idx_planet_res` (`planet_id`, `resource_type`),
        CONSTRAINT `fk_terroir_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "[OK] Table `planet_terroir_slots` créée ou déjà existante.\n";
} catch (Exception $e) {
    echo "[ERREUR] " . $e->getMessage() . "\n";
    exit(1);
}
