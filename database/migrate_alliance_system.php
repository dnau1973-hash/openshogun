<?php
/**
 * Migration : Système d'Alliance Féodale & Invitations
 * Table alliances, alliance_invitations et colonnes associées
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getConnection();
    echo "=== Migration Système d'Alliances Féodales ===\n";

    // 1. S'assurer que la table alliances existe
    $db->exec("
        CREATE TABLE IF NOT EXISTS `alliances` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(50) NOT NULL UNIQUE,
          `tag` VARCHAR(8) NOT NULL UNIQUE,
          `leader_id` INT UNSIGNED NOT NULL,
          `description` TEXT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_alliance_leader` (`leader_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table alliances vérifiée.\n";

    // 2. S'assurer que users possède alliance_id
    $cols = $db->query("SHOW COLUMNS FROM users LIKE 'alliance_id'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN alliance_id INT UNSIGNED NULL DEFAULT NULL AFTER faction");
        $db->exec("ALTER TABLE users ADD INDEX idx_user_alliance (alliance_id)");
        echo "[OK] Colonne users.alliance_id ajoutée.\n";
    } else {
        echo "[INFO] Colonne users.alliance_id déjà existante.\n";
    }

    // 3. Créer la table des invitations et candidatures d'alliance
    $db->exec("
        CREATE TABLE IF NOT EXISTS `alliance_invitations` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `alliance_id` INT UNSIGNED NOT NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `sender_id` INT UNSIGNED NOT NULL,
          `type` ENUM('invitation', 'application') NOT NULL DEFAULT 'invitation',
          `message` VARCHAR(255) NULL,
          `status` ENUM('pending', 'accepted', 'rejected', 'canceled') NOT NULL DEFAULT 'pending',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `idx_inv_alliance` (`alliance_id`),
          KEY `idx_inv_user` (`user_id`),
          KEY `idx_inv_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table alliance_invitations vérifiée.\n";

    echo "=== Migration terminée avec succès ! ===\n";
} catch (Exception $e) {
    echo "[ERREUR] " . $e->getMessage() . "\n";
    exit(1);
}

