<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

echo "=== MIGRATION DE FLEET_MISSIONS POUR LE SUPPORT DES OASIS ===\n";

try {
    // 1. Rendre target_planet_id nullable
    $db->exec("ALTER TABLE `fleet_missions` MODIFY `target_planet_id` INT UNSIGNED NULL;");
    echo "- target_planet_id est maintenant NULLable.\n";

    // 2. Ajouter target_oasis_id si non existant
    $columns = $db->query("SHOW COLUMNS FROM `fleet_missions` LIKE 'target_oasis_id'")->fetchAll();
    if (empty($columns)) {
        $db->exec("
            ALTER TABLE `fleet_missions` 
            ADD COLUMN `target_oasis_id` INT UNSIGNED NULL AFTER `target_planet_id`,
            ADD CONSTRAINT `fk_fleet_target_oasis` FOREIGN KEY (`target_oasis_id`) REFERENCES `oases`(`id`) ON DELETE CASCADE;
        ");
        echo "- Colonne target_oasis_id et FK ajoutées avec succès.\n";
    } else {
        echo "- Colonne target_oasis_id déjà présente.\n";
    }

    // 3. Mettre à jour l'enum mission_type pour supporter 'occupy'
    $db->exec("
        ALTER TABLE `fleet_missions` 
        MODIFY `mission_type` ENUM('attack', 'raid', 'transport', 'spy', 'colonize', 'occupy') NOT NULL;
    ");
    echo "- fleet_missions.mission_type mis à jour avec 'occupy'.\n";

    // 4. Mettre à jour combat_reports.mission_type pour supporter 'occupy'
    $db->exec("
        ALTER TABLE `combat_reports` 
        MODIFY `mission_type` ENUM('attack', 'raid', 'spy', 'occupy') NOT NULL;
    ");
    echo "- combat_reports.mission_type mis à jour avec 'occupy'.\n";

    echo "=== MIGRATION FLEET_MISSIONS TERMINÉE AVEC SUCCÈS ===\n";
} catch (Exception $e) {
    echo "Erreur lors de la migration: " . $e->getMessage() . "\n";
    exit(1);
}

