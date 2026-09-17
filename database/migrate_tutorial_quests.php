<?php
/**
 * Migration : Table des Quêtes Didacticiel Féodal & Récompenses (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : TABLE USER_QUESTS ===\n";

$db = Database::getConnection();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `user_quests` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `quest_key` VARCHAR(50) NOT NULL,
            `status` ENUM('in_progress', 'completed', 'claimed') NOT NULL DEFAULT 'in_progress',
            `progress` INT UNSIGNED NOT NULL DEFAULT 0,
            `completed_at` DATETIME NULL,
            `claimed_at` DATETIME NULL,
            UNIQUE KEY `uniq_user_quest` (`user_id`, `quest_key`),
            KEY `idx_uq_user` (`user_id`),
            KEY `idx_uq_status` (`status`),
            CONSTRAINT `fk_uq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table 'user_quests' créée ou déjà existante avec succès.\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de la création de 'user_quests': " . $e->getMessage() . "\n";
    exit(1);
}

