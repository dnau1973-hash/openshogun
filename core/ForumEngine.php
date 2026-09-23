<?php
/**
 * Moteur du Forum Féodal d'OpenShogun
 * Gestion des catégories, sujets, discussions et modération (Admin & Modérateurs)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class ForumEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureForumTables();
    }

    /**
     * Auto-guérison et création automatique des tables du forum
     */
    public function ensureForumTables(): void {
        try {
            // 1. Table forum_categories
            $this->db->exec("
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

            // 2. Table forum_topics
            $this->db->exec("
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

            // 3. Table forum_posts
            $this->db->exec("
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

            // Remplir les catégories par défaut si la table est vide
            $catCount = (int)$this->db->query("SELECT COUNT(*) FROM forum_categories")->fetchColumn();
            if ($catCount === 0) {
                $stmtInsertCat = $this->db->prepare("
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
            }
        } catch (Exception $e) {
            error_log("ForumEngine::ensureForumTables - " . $e->getMessage());
        }
    }

    // ==========================================
    // GESTION DES CATÉGORIES
    // ==========================================

    /**
     * Récupère toutes les catégories avec statistiques (nb sujets, nb réponses, dernier message)
     */
    public function getCategories(): array {
        $sql = "
            SELECT c.*,
                   COUNT(DISTINCT t.id) as topic_count,
                   COUNT(p.id) as post_count,
                   MAX(t.last_post_at) as last_activity,
                   (
                       SELECT t2.title FROM forum_topics t2 
                       WHERE t2.category_id = c.id 
                       ORDER BY t2.last_post_at DESC LIMIT 1
                   ) as last_topic_title,
                   (
                       SELECT t2.id FROM forum_topics t2 
                       WHERE t2.category_id = c.id 
                       ORDER BY t2.last_post_at DESC LIMIT 1
                   ) as last_topic_id,
                   (
                       SELECT u.username FROM forum_posts p2
                       JOIN forum_topics t3 ON p2.topic_id = t3.id
                       JOIN users u ON p2.user_id = u.id
                       WHERE t3.category_id = c.id
                       ORDER BY p2.created_at DESC LIMIT 1
                   ) as last_poster_username
            FROM forum_categories c
            LEFT JOIN forum_topics t ON t.category_id = c.id
            LEFT JOIN forum_posts p ON p.topic_id = t.id
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.id ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une catégorie par son ID
     */
    public function getCategory(int $catId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM forum_categories WHERE id = ?");
        $stmt->execute([$catId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Création d'une catégorie (réservé à l'administrateur)
     */
    public function createCategory(string $name, string $desc, string $icon = '💬', int $order = 0, bool $isLocked = false): int {
        $stmt = $this->db->prepare("
            INSERT INTO forum_categories (name, description, icon, display_order, is_locked, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([trim($name), trim($desc), trim($icon) ?: '💬', $order, $isLocked ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Modification d'une catégorie
     */
    public function updateCategory(int $catId, string $name, string $desc, string $icon, int $order, bool $isLocked): bool {
        $stmt = $this->db->prepare("
            UPDATE forum_categories 
            SET name = ?, description = ?, icon = ?, display_order = ?, is_locked = ? 
            WHERE id = ?
        ");
        return $stmt->execute([trim($name), trim($desc), trim($icon) ?: '💬', $order, $isLocked ? 1 : 0, $catId]);
    }

    /**
     * Suppression d'une catégorie
     */
    public function deleteCategory(int $catId): bool {
        $stmt = $this->db->prepare("DELETE FROM forum_categories WHERE id = ?");
        return $stmt->execute([$catId]);
    }

    // ==========================================
    // GESTION DES SUJETS (TOPICS)
    // ==========================================

    /**
     * Récupère la liste paginée des sujets d'une catégorie
     */
    public function getTopics(int $categoryId, int $page = 1, int $perPage = 20): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // Total sujets
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = ?");
        $stmtCount->execute([$categoryId]);
        $total = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, ceil($total / $perPage));

        $sql = "
            SELECT t.*,
                   u.username as author_username, u.faction as author_faction,
                   u.is_admin as author_is_admin, u.is_moderator as author_is_moderator,
                   a.tag as author_alliance_tag,
                   COUNT(p.id) as post_count,
                   (
                       SELECT u2.username FROM forum_posts p2
                       JOIN users u2 ON p2.user_id = u2.id
                       WHERE p2.topic_id = t.id
                       ORDER BY p2.created_at DESC LIMIT 1
                   ) as last_poster_username
            FROM forum_topics t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            LEFT JOIN forum_posts p ON p.topic_id = t.id
            WHERE t.category_id = ?
            GROUP BY t.id
            ORDER BY t.is_pinned DESC, t.last_post_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'topics' => $topics,
            'total' => $total,
            'pages' => $totalPages,
            'current_page' => $page
        ];
    }

    /**
     * Récupère les détails d'un sujet
     */
    public function getTopic(int $topicId): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, c.is_locked as category_is_locked,
                   u.username as author_username, u.faction as author_faction,
                   u.is_admin as author_is_admin, u.is_moderator as author_is_moderator,
                   a.tag as author_alliance_tag
            FROM forum_topics t
            JOIN forum_categories c ON t.category_id = c.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE t.id = ?
        ");
        $stmt->execute([$topicId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Incrémente le compteur de vues d'un sujet
     */
    public function incrementViews(int $topicId): void {
        $stmt = $this->db->prepare("UPDATE forum_topics SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$topicId]);
    }

    /**
     * Création d'un nouveau sujet et de son premier message
     */
    public function createTopic(int $categoryId, int $userId, string $title, string $content): array {
        $title = trim($title);
        $content = trim($content);

        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
            return ['success' => false, 'error' => 'Le titre du sujet doit comporter entre 3 et 150 caractères.'];
        }
        if (mb_strlen($content) < 3) {
            return ['success' => false, 'error' => 'Le message est trop court pour proclamer un décret ou ouvrir un débat.'];
        }

        $cat = $this->getCategory($categoryId);
        if (!$cat) {
            return ['success' => false, 'error' => 'Catégorie introuvable.'];
        }

        // Si la catégorie est verrouillée, seul le staff peut y publier de nouveaux sujets
        if ((int)$cat['is_locked'] === 1 && !Auth::isUserModerator($userId)) {
            return ['success' => false, 'error' => 'Ce salon officiel est réservé aux proclamations impériales de l\'administration.'];
        }

        $this->db->beginTransaction();
        try {
            $stmtTopic = $this->db->prepare("
                INSERT INTO forum_topics (category_id, user_id, title, is_pinned, is_locked, views_count, last_post_at, created_at)
                VALUES (?, ?, ?, 0, 0, 1, NOW(), NOW())
            ");
            $stmtTopic->execute([$categoryId, $userId, $title]);
            $topicId = (int)$this->db->lastInsertId();

            $stmtPost = $this->db->prepare("
                INSERT INTO forum_posts (topic_id, user_id, content, is_first_post, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmtPost->execute([$topicId, $userId, $content]);

            $this->db->commit();
            return ['success' => true, 'topic_id' => $topicId, 'message' => 'Le sujet a été proclamé avec succès sur le forum.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la création du sujet : ' . $e->getMessage()];
        }
    }

    /**
     * Épingler / Désépingler un sujet (Modérateur ou Admin)
     */
    public function togglePinTopic(int $topicId, int $userId): array {
        if (!Auth::isUserModerator($userId)) {
            return ['success' => false, 'error' => 'Action réservée au corps de modération féodale.'];
        }

        $stmt = $this->db->prepare("SELECT is_pinned FROM forum_topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $row = $stmt->fetch();
        if (!$row) return ['success' => false, 'error' => 'Sujet introuvable.'];

        $newPinned = ((int)$row['is_pinned'] === 1) ? 0 : 1;
        $this->db->prepare("UPDATE forum_topics SET is_pinned = ? WHERE id = ?")->execute([$newPinned, $topicId]);

        $msg = ($newPinned === 1) ? 'Le sujet est désormais épinglé en tête du salon 📌.' : 'Le sujet n\'est plus épinglé.';
        return ['success' => true, 'is_pinned' => $newPinned, 'message' => $msg];
    }

    /**
     * Verrouiller / Déverrouiller un sujet (Modérateur ou Admin)
     */
    public function toggleLockTopic(int $topicId, int $userId): array {
        if (!Auth::isUserModerator($userId)) {
            return ['success' => false, 'error' => 'Action réservée au corps de modération féodale.'];
        }

        $stmt = $this->db->prepare("SELECT is_locked FROM forum_topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $row = $stmt->fetch();
        if (!$row) return ['success' => false, 'error' => 'Sujet introuvable.'];

        $newLocked = ((int)$row['is_locked'] === 1) ? 0 : 1;
        $this->db->prepare("UPDATE forum_topics SET is_locked = ? WHERE id = ?")->execute([$newLocked, $topicId]);

        $msg = ($newLocked === 1) ? 'Le sujet a été verrouillé 🔒 (plus aucune réponse autorisée).' : 'Le sujet a été déverrouillé.';
        return ['success' => true, 'is_locked' => $newLocked, 'message' => $msg];
    }

    /**
     * Supprimer un sujet (Modérateur, Admin ou Auteur si 1 seul post)
     */
    public function deleteTopic(int $topicId, int $userId): array {
        $topic = $this->getTopic($topicId);
        if (!$topic) return ['success' => false, 'error' => 'Sujet introuvable.'];

        $isStaff = Auth::isUserModerator($userId);
        $isAuthor = ((int)$topic['user_id'] === $userId);

        if (!$isStaff && !$isAuthor) {
            return ['success' => false, 'error' => 'Vous n\'avez pas l\'autorité pour supprimer ce sujet.'];
        }

        $this->db->prepare("DELETE FROM forum_topics WHERE id = ?")->execute([$topicId]);
        return ['success' => true, 'category_id' => $topic['category_id'], 'message' => 'Le sujet a été définitivement retiré des registres du forum.'];
    }

    // ==========================================
    // GESTION DES MESSAGES (POSTS)
    // ==========================================

    /**
     * Récupère la liste paginée des messages d'un sujet
     */
    public function getPosts(int $topicId, int $page = 1, int $perPage = 15): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM forum_posts WHERE topic_id = ?");
        $stmtCount->execute([$topicId]);
        $total = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, ceil($total / $perPage));

        $sql = "
            SELECT p.*,
                   u.username as author_username, u.faction as author_faction,
                   u.points as author_points, u.is_admin as author_is_admin,
                   u.is_moderator as author_is_moderator, u.created_at as author_created_at,
                   a.name as author_alliance_name, a.tag as author_alliance_tag,
                   COUNT(DISTINCT pl.id) as author_planet_count,
                   u2.username as editor_username
            FROM forum_posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            LEFT JOIN planets pl ON pl.user_id = u.id
            LEFT JOIN users u2 ON p.edited_by_user_id = u2.id
            WHERE p.topic_id = ?
            GROUP BY p.id
            ORDER BY p.created_at ASC
            LIMIT ? OFFSET ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $topicId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'posts' => $posts,
            'total' => $total,
            'pages' => $totalPages,
            'current_page' => $page
        ];
    }

    /**
     * Répondre à un sujet
     */
    public function createPost(int $topicId, int $userId, string $content): array {
        $content = trim($content);
        if (mb_strlen($content) < 2) {
            return ['success' => false, 'error' => 'Votre réponse doit contenir au moins 2 caractères.'];
        }

        $topic = $this->getTopic($topicId);
        if (!$topic) {
            return ['success' => false, 'error' => 'Sujet introuvable.'];
        }

        // Si le sujet est verrouillé, seul le staff peut y répondre
        if ((int)$topic['is_locked'] === 1 && !Auth::isUserModerator($userId)) {
            return ['success' => false, 'error' => 'Ce sujet est verrouillé 🔒. Aucune nouvelle réponse n\'est permise.'];
        }

        $this->db->beginTransaction();
        try {
            $stmtPost = $this->db->prepare("
                INSERT INTO forum_posts (topic_id, user_id, content, is_first_post, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmtPost->execute([$topicId, $userId, $content]);
            $postId = (int)$this->db->lastInsertId();

            // Mettre à jour la date de dernière activité du sujet
            $this->db->prepare("UPDATE forum_topics SET last_post_at = NOW() WHERE id = ?")->execute([$topicId]);

            $this->db->commit();
            return ['success' => true, 'post_id' => $postId, 'message' => 'Votre réponse a été inscrite au fil de discussion.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la réponse : ' . $e->getMessage()];
        }
    }

    /**
     * Éditer un message (Auteur ou Modérateur / Admin)
     */
    public function updatePost(int $postId, int $userId, string $content): array {
        $content = trim($content);
        if (mb_strlen($content) < 2) {
            return ['success' => false, 'error' => 'Le message ne peut pas être vide.'];
        }

        $stmt = $this->db->prepare("SELECT user_id, topic_id FROM forum_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if (!$post) return ['success' => false, 'error' => 'Message introuvable.'];

        $isStaff = Auth::isUserModerator($userId);
        $isAuthor = ((int)$post['user_id'] === $userId);

        if (!$isStaff && !$isAuthor) {
            return ['success' => false, 'error' => 'Vous ne pouvez modifier que vos propres messages.'];
        }

        $stmtUpdate = $this->db->prepare("
            UPDATE forum_posts 
            SET content = ?, edited_at = NOW(), edited_by_user_id = ? 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$content, $userId, $postId]);

        return ['success' => true, 'message' => 'Le message a été actualisé.'];
    }

    /**
     * Supprimer un message individuel
     */
    public function deletePost(int $postId, int $userId): array {
        $stmt = $this->db->prepare("SELECT user_id, topic_id, is_first_post FROM forum_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if (!$post) return ['success' => false, 'error' => 'Message introuvable.'];

        if ((int)$post['is_first_post'] === 1) {
            return ['success' => false, 'error' => 'Le premier message d\'un sujet ne peut pas être supprimé seul. Supprimez le sujet entier.'];
        }

        $isStaff = Auth::isUserModerator($userId);
        $isAuthor = ((int)$post['user_id'] === $userId);

        if (!$isStaff && !$isAuthor) {
            return ['success' => false, 'error' => 'Action non autorisée.'];
        }

        $this->db->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$postId]);
        return ['success' => true, 'message' => 'Le message a été effacé.'];
    }
}

