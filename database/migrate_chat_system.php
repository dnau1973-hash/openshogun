<?php
/**
 * Migration : Système de Chat entre Joueurs OpenShogun
 * Crée la table chat_messages avec ses index et contraintes.
 */

require_once __DIR__ . '/../core/Database.php';

echo "=== Migration : Système de Chat Féodal ===\n";

try {
    $db = Database::getConnection();
} catch (Exception $e) {
    echo "Erreur de connexion DB : " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `channel_type` ENUM('global', 'alliance', 'whisper') NOT NULL DEFAULT 'global',
            `channel_target_id` INT UNSIGNED NULL DEFAULT NULL,
            `sender_id` INT UNSIGNED NOT NULL,
            `recipient_id` INT UNSIGNED NULL DEFAULT NULL,
            `message` VARCHAR(1000) NOT NULL,
            `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `deleted_by` INT UNSIGNED NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_chat_global` (`channel_type`, `id`),
            KEY `idx_chat_alliance` (`channel_type`, `channel_target_id`, `id`),
            KEY `idx_chat_whisper` (`sender_id`, `recipient_id`, `id`),
            KEY `idx_chat_recipient` (`recipient_id`, `id`),
            CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "[OK] Table chat_messages créée ou déjà existante.\n";
    echo "Migration du système de chat terminée avec succès !\n";
} catch (Exception $e) {
    echo "[ERREUR] Échec de la migration chat : " . $e->getMessage() . "\n";
    exit(1);
}

