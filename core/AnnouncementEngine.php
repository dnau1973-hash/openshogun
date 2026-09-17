<?php
/**
 * AnnouncementEngine - Gestionnaire des annonces de fonctionnalités et notifications (OpenShogun)
 * Stockage persistant des annonces en JSON (config/announcements.json)
 * Suivi de lecture utilisateur en base de données (user_announcement_reads)
 */

require_once __DIR__ . '/Database.php';

class AnnouncementEngine {
    private static string $filePath = __DIR__ . '/../config/announcements.json';

    /**
     * Charge toutes les annonces depuis le fichier JSON.
     *
     * @param bool $onlyPublished Si vrai, ne retourne que les annonces validées/publiées
     * @return array
     */
    public static function getAllAnnouncements(bool $onlyPublished = false): array {
        if (!file_exists(self::$filePath)) {
            return [];
        }

        $content = @file_get_contents(self::$filePath);
        if ($content === false || empty(trim($content))) {
            return [];
        }

        $list = json_decode($content, true);
        if (!is_array($list)) {
            return [];
        }

        // Tri par date décroissante (les plus récentes en premier)
        usort($list, function ($a, $b) {
            $dateA = $a['published_at'] ?? $a['date'] ?? '';
            $dateB = $b['published_at'] ?? $b['date'] ?? '';
            return strcmp($dateB, $dateA);
        });

        if ($onlyPublished) {
            $list = array_values(array_filter($list, function ($item) {
                return !empty($item['is_published']);
            }));
        }

        return $list;
    }

    /**
     * Récupère une annonce spécifique par son identifiant.
     *
     * @param string $id
     * @return array|null
     */
    public static function getAnnouncement(string $id): ?array {
        $announcements = self::getAllAnnouncements(false);
        foreach ($announcements as $announcement) {
            if (($announcement['id'] ?? '') === $id) {
                return $announcement;
            }
        }
        return null;
    }

    /**
     * Sauvegarde (création ou mise à jour) d'une annonce dans le fichier JSON.
     *
     * @param array $data Données de l'annonce
     * @param string|null $id ID existant si modification
     * @return string ID de l'annonce enregistrée
     */
    public static function saveAnnouncement(array $data, ?string $id = null): string {
        $announcements = self::getAllAnnouncements(false);

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        if ($id === null || empty($id)) {
            // Création : génération d'un slug ID unique
            $slug = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($data['title'] ?? 'update'));
            $slug = trim($slug, '_');
            $id = 'announcement_' . date('Ymd_His') . ($slug ? '_' . substr($slug, 0, 20) : '');
            $isNew = true;
        } else {
            $isNew = false;
        }

        $record = [
            'id' => $id,
            'version' => trim($data['version'] ?? 'v1.0'),
            'title' => trim($data['title'] ?? 'Nouvelle Fonctionnalité'),
            'badge' => trim($data['badge'] ?? '✨ NOUVEAUTÉ'),
            'icon' => trim($data['icon'] ?? '📜'),
            'summary' => trim($data['summary'] ?? ''),
            'date' => !empty($data['date']) ? $data['date'] : $today,
            'is_published' => !empty($data['is_published']),
            'published_at' => !empty($data['is_published']) ? ($data['published_at'] ?? $now) : null,
            'author' => trim($data['author'] ?? 'Admin'),
            'features' => []
        ];

        // Formatage des fonctionnalités
        if (!empty($data['features']) && is_array($data['features'])) {
            foreach ($data['features'] as $f) {
                if (is_array($f) && !empty(trim($f['title'] ?? ''))) {
                    $record['features'][] = [
                        'title' => trim($f['title']),
                        'icon' => trim($f['icon'] ?? '🔹'),
                        'category' => trim($f['category'] ?? 'Général'),
                        'description' => trim($f['description'] ?? '')
                    ];
                }
            }
        }

        $found = false;
        for ($i = 0; $i < count($announcements); $i++) {
            if ($announcements[$i]['id'] === $id) {
                // Conservation des champs non modifiés si nécessaire
                if (empty($record['published_at']) && !empty($announcements[$i]['published_at']) && $record['is_published']) {
                    $record['published_at'] = $announcements[$i]['published_at'];
                }
                $announcements[$i] = $record;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $announcements[] = $record;
        }

        self::persist($announcements);
        return $id;
    }

    /**
     * Supprime une annonce du fichier JSON.
     *
     * @param string $id
     * @return bool
     */
    public static function deleteAnnouncement(string $id): bool {
        $announcements = self::getAllAnnouncements(false);
        $initialCount = count($announcements);
        $announcements = array_values(array_filter($announcements, function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));

        if (count($announcements) === $initialCount) {
            return false;
        }

        self::persist($announcements);

        // Supprimer également les traces de lecture orphelines
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM user_announcement_reads WHERE announcement_id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            // Loguer ou ignorer si la table n'est pas accessible
        }

        return true;
    }

    /**
     * Modifie le statut de publication/validation par l'administrateur.
     *
     * @param string $id
     * @param bool $isPublished
     * @return bool
     */
    public static function setPublishedStatus(string $id, bool $isPublished): bool {
        $announcements = self::getAllAnnouncements(false);
        $found = false;

        for ($i = 0; $i < count($announcements); $i++) {
            if ($announcements[$i]['id'] === $id) {
                $announcements[$i]['is_published'] = $isPublished;
                if ($isPublished && empty($announcements[$i]['published_at'])) {
                    $announcements[$i]['published_at'] = date('Y-m-d H:i:s');
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        self::persist($announcements);
        return true;
    }

    /**
     * Récupère la liste des annonces publiées non lues par un utilisateur donné.
     *
     * @param int $userId
     * @return array
     */
    public static function getUnreadForUser(int $userId): array {
        if ($userId <= 0) {
            return [];
        }

        $published = self::getAllAnnouncements(true);
        if (empty($published)) {
            return [];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT announcement_id FROM user_announcement_reads WHERE user_id = ?");
        $stmt->execute([$userId]);
        $readIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $readSet = array_flip($readIds);

        $unread = [];
        foreach ($published as $announcement) {
            $id = $announcement['id'] ?? '';
            if (!isset($readSet[$id])) {
                $unread[] = $announcement;
            }
        }

        return $unread;
    }

    /**
     * Récupère la première annonce publiée non lue pour l'utilisateur.
     *
     * @param int $userId
     * @return array|null
     */
    public static function getFirstUnreadForUser(int $userId): ?array {
        $unread = self::getUnreadForUser($userId);
        return !empty($unread) ? $unread[0] : null;
    }

    /**
     * Marque une annonce comme lue par l'utilisateur (validation de lecture).
     *
     * @param int $userId
     * @param string $announcementId
     * @return bool
     */
    public static function markAsRead(int $userId, string $announcementId): bool {
        if ($userId <= 0 || empty($announcementId)) {
            return false;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_announcement_reads (user_id, announcement_id, read_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()
        ");
        return $stmt->execute([$userId, $announcementId]);
    }

    /**
     * Statistiques de lecture de chaque annonce pour l'administration.
     *
     * @return array [announcement_id => total_reads]
     */
    public static function getReadStats(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT announcement_id, COUNT(*) as read_count 
            FROM user_announcement_reads 
            GROUP BY announcement_id
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats = [];
        foreach ($results as $row) {
            $stats[$row['announcement_id']] = (int)$row['read_count'];
        }
        return $stats;
    }

    /**
     * Écrit le tableau d'annonces de manière atomique et formatée dans le fichier JSON.
     *
     * @param array $announcements
     * @return void
     */
    private static function persist(array $announcements): void {
        $dir = dirname(self::$filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($announcements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(self::$filePath, $json, LOCK_EX);
    }
}

