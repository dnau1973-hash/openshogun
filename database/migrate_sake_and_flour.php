<?php
/**
 * Migration : Ajout du Saké et de la Farine de Riz sur la table planets
 */

require_once __DIR__ . '/../core/Database.php';

echo "=== Migration : Saké & Farine de Riz (planets) ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "Erreur de connexion DB : " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $db->query("SHOW COLUMNS FROM `planets` LIKE 'sake'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE `planets` ADD COLUMN `sake` DOUBLE NOT NULL DEFAULT 0 AFTER `deuterium`");
        echo "[OK] Colonne 'sake' ajoutée.\n";
    } else {
        echo "[INFO] Colonne 'sake' déjà présente.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM `planets` LIKE 'rice_flour'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE `planets` ADD COLUMN `rice_flour` DOUBLE NOT NULL DEFAULT 0 AFTER `sake`");
        echo "[OK] Colonne 'rice_flour' ajoutée.\n";
    } else {
        echo "[INFO] Colonne 'rice_flour' déjà présente.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM `planets` LIKE 'sake_max'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE `planets` ADD COLUMN `sake_max` INT UNSIGNED NOT NULL DEFAULT 10000 AFTER `deuterium_max`");
        echo "[OK] Colonne 'sake_max' ajoutée.\n";
    } else {
        echo "[INFO] Colonne 'sake_max' déjà présente.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM `planets` LIKE 'rice_flour_max'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE `planets` ADD COLUMN `rice_flour_max` INT UNSIGNED NOT NULL DEFAULT 10000 AFTER `sake_max`");
        echo "[OK] Colonne 'rice_flour_max' ajoutée.\n";
    } else {
        echo "[INFO] Colonne 'rice_flour_max' déjà présente.\n";
    }

    echo "Migration du Saké et de la Farine de Riz terminée avec succès !\n";
} catch (Exception $e) {
    echo "[ERREUR] " . $e->getMessage() . "\n";
    exit(1);
}

