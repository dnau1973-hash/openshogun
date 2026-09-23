<?php
/**
 * Migration : Statut Modérateur & Forum Féodal Administré
 * - Ajoute users.is_moderator
 * - Crée forum_categories, forum_topics, forum_posts
 * - Insère les catégories initiales du forum
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getConnection();
    echo "=== Migration Modérateur & Forum Féodal ===\n";

    // 1. Colonne is_moderator dans users
    $cols = $db->query("SHOW COLUMNS FROM users LIKE 'is_moderator'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN is_moderator TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin");
        $db->exec("ALTER TABLE users ADD INDEX idx_user_moderator (is_moderator)");
        echo "[OK] Colonne users.is_moderator ajoutée.\n";
    } else {
        echo "[INFO] Colonne users.is_moderator déjà présente.\n";
    }

    // 2. Table forum_categories
    $db->exec("
        CREATE TABLE IF NOT EXISTS `forum_categories` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(100) NOT NULL,
          `description` VARCHAR(255) NULL,
          `icon` VARCHAR(20) NOT NULL DEFAULT '💬',
          `display_order` INT NOT NULL DEFAULT 0,
          `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table forum_categories vérifiée.\n";

    // 3. Table forum_topics
    $db->exec("
        CREATE TABLE IF NOT EXISTS `forum_topics` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `category_id` INT UNSIGNED NOT NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `title` VARCHAR(150) NOT NULL,
          `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
          `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
          `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
          `last_post_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_topic_cat` (`category_id`, `is_pinned`, `last_post_at`),
          KEY `idx_topic_user` (`user_id`),
          CONSTRAINT `fk_topic_category` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_topic_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table forum_topics vérifiée.\n";

    // 4. Table forum_posts
    $db->exec("
        CREATE TABLE IF NOT EXISTS `forum_posts` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `topic_id` INT UNSIGNED NOT NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `content` TEXT NOT NULL,
          `is_first_post` TINYINT(1) NOT NULL DEFAULT 0,
          `edited_at` DATETIME NULL DEFAULT NULL,
          `edited_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_post_topic` (`topic_id`, `created_at`),
          KEY `idx_post_user` (`user_id`),
          CONSTRAINT `fk_post_topic` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table forum_posts vérifiée.\n";

    // 5. Remplir les catégories initiales si la table est vide
    $catCount = (int)$db->query("SELECT COUNT(*) FROM forum_categories")->fetchColumn();
    if ($catCount === 0) {
        $stmtInsertCat = $db->prepare("
            INSERT INTO forum_categories (name, description, icon, display_order, is_locked)
            VALUES (?, ?, ?, ?, ?)
        ");
        $defaultCategories = [
            ['Décrets & Annonces du Shogunat', 'Communications impériales, mises à jour et directives officielles de l\'administration.', '📢', 1, 1],
            ['Ambassade & Recrutement des Alliances', 'Proclamez vos ligues féodales, recrutez des guerriers et négociez vos pactes d\'alliance.', '🎌', 2, 0],
            ['Salons de Stratégie & Tactiques Militaires', 'Arts de la guerre, compositions de cohortes, sièges de donjons et manœuvres féodales.', '⚔️', 3, 0],
            ['Maison de Thé & Sérénité (Taverne)', 'Détente, récits autour d\'un bol de matcha et discussions générales entre Daimyōs.', '🍵', 4, 0],
            ['Questions, Entraide & Chroniques Féodales', 'Posez vos questions sur les règles, la gestion castrale et venez en aide aux novices.', '🛠️', 5, 0],
        ];

        foreach ($defaultCategories as $c) {
            $stmtInsertCat->execute($c);
        }
        echo "[OK] 5 catégories initiales du forum créées.\n";
    } else {
        echo "[INFO] Catégories du forum déjà existantes ({$catCount}).\n";
    }

    echo "=== Migration terminée avec succès ! ===\n";
} catch (Exception $e) {
    echo "[ERREUR] " . $e->getMessage() . "\n";
    exit(1);
}

