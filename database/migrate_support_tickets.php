<?php
/**
 * Migration : Création de la table support_tickets (Bugs & Suggestions)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : TABLE SUPPORT_TICKETS ===\n";

try {
    $db = Database::getConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS `support_tickets` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT UNSIGNED NOT NULL,
      `type` ENUM('bug', 'suggestion') NOT NULL DEFAULT 'bug',
      `category` VARCHAR(50) NOT NULL DEFAULT 'general',
      `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
      `title` VARCHAR(150) NOT NULL,
      `description` TEXT NOT NULL,
      `planet_id` INT UNSIGNED NULL,
      `status` ENUM('pending', 'in_progress', 'resolved', 'planned', 'closed') NOT NULL DEFAULT 'pending',
      `admin_response` TEXT NULL,
      `admin_id` INT UNSIGNED NULL,
      `responded_at` INT UNSIGNED NULL,
      `created_at` INT UNSIGNED NOT NULL,
      `updated_at` INT UNSIGNED NOT NULL,
      KEY `idx_support_user` (`user_id`),
      KEY `idx_support_status` (`status`),
      KEY `idx_support_type` (`type`),
      CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "✓ Table 'support_tickets' créée ou déjà existante avec succès !\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de la création de la table support_tickets : " . $e->getMessage() . "\n";
    exit(1);
}

