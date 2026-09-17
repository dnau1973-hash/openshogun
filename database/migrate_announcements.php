<?php
/**
 * Migration : Table de suivi de lecture des annonces de fonctionnalités (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : TABLE USER_ANNOUNCEMENT_READS ===\n";

$db = Database::getConnection();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `user_announcement_reads` (
            `user_id` INT UNSIGNED NOT NULL,
            `announcement_id` VARCHAR(64) NOT NULL,
            `read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`, `announcement_id`),
            KEY `idx_announcement_id` (`announcement_id`),
            CONSTRAINT `fk_announcement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table 'user_announcement_reads' créée ou déjà existante avec succès.\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de la création de 'user_announcement_reads': " . $e->getMessage() . "\n";
    exit(1);
}
