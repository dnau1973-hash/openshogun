<?php
/**
 * Moteur de Chat Féodal et Messagerie Instantanée OpenShogun
 * 
 * Gère les canaux de discussion en direct :
 * - Canal Général du Shogunat (Tous les Daimyōs)
 * - Canal d'Alliance (Membres du même clan)
 * - Chuchotements Privés (Direct 1-à-1)
 * Avec modération intégrée, anti-spam et polling incrémental.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../config/game_constants.php';

class ChatEngine {
    private PDO $db;
    private static bool $tablesChecked = false;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
        $this->ensureChatTables();
    }

    /**
     * Garantit automatiquement la présence de la table chat_messages
     */
    public function ensureChatTables(): void {
        if (self::$tablesChecked) return;
        self::$tablesChecked = true;

        try {
            $this->db->exec("
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
        } catch (Exception $e) {
            // Ignorer si déjà existant
        }
    }

    /**
     * Envoie un message dans un canal de discussion
     */
    public function sendMessage(
        int $senderId,
        string $channelType,
        ?int $targetId,
        ?int $recipientId,
        string $content
    ): array {
        $content = trim(strip_tags($content));
        if ($content === '') {
            return ['success' => false, 'error' => 'Le message ne peut pas être vide.'];
        }

        if (mb_strlen($content) > 1000) {
            $content = mb_substr($content, 0, 1000);
        }

        if (!in_array($channelType, ['global', 'alliance', 'whisper'], true)) {
            return ['success' => false, 'error' => 'Canal de discussion invalide.'];
        }

        // Anti-spam léger : 1 message par seconde par joueur
        $stmtRate = $this->db->prepare("
            SELECT id FROM chat_messages 
            WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 SECOND)
            LIMIT 1
        ");
        $stmtRate->execute([$senderId]);
        if ($stmtRate->fetch()) {
            return ['success' => false, 'error' => 'Veuillez patienter un instant avant d\'envoyer un nouveau message.'];
        }

        // Récupérer le joueur expéditeur et son alliance
        $stmtUser = $this->db->prepare("SELECT id, username, alliance_id, faction, is_admin, is_moderator FROM users WHERE id = ?");
        $stmtUser->execute([$senderId]);
        $sender = $stmtUser->fetch();
        if (!$sender) {
            return ['success' => false, 'error' => 'Joueur introuvable.'];
        }

        $userAllianceId = $sender['alliance_id'] ? (int)$sender['alliance_id'] : null;

        // Validation selon le type de canal
        if ($channelType === 'alliance') {
            if (!$userAllianceId) {
                return ['success' => false, 'error' => 'Vous devez appartenir à une alliance pour échanger sur ce canal.'];
            }
            $targetId = $userAllianceId;
            $recipientId = null;
        } elseif ($channelType === 'whisper') {
            if (!$recipientId || $recipientId === $senderId) {
                return ['success' => false, 'error' => 'Destinataire du chuchotement invalide.'];
            }
            // Vérifier que le destinataire existe
            $stmtRecip = $this->db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmtRecip->execute([$recipientId]);
            if (!$stmtRecip->fetch()) {
                return ['success' => false, 'error' => 'Le Daimyō destinataire n\'existe pas.'];
            }
            $targetId = null;
        } else {
            // Canal global
            $targetId = null;
            $recipientId = null;
        }

        // Insertion du message
        $stmt = $this->db->prepare("
            INSERT INTO chat_messages 
            (channel_type, channel_target_id, sender_id, recipient_id, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$channelType, $targetId, $senderId, $recipientId, $content]);
        $newMsgId = (int)$this->db->lastInsertId();

        // Mettre à jour l'activité du joueur
        $this->db->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$senderId]);

        return [
            'success' => true,
            'message_id' => $newMsgId,
            'message' => 'Message transmis avec honneur.'
        ];
    }

    /**
     * Récupère les messages d'un canal (avec support du polling incrémental par last_id)
     */
    public function getMessages(
        int $userId,
        string $channelType,
        ?int $targetId = null,
        ?int $otherUserId = null,
        int $lastId = 0,
        int $limit = 60
    ): array {
        if (!in_array($channelType, ['global', 'alliance', 'whisper'], true)) {
            return [];
        }

        $stmtUser = $this->db->prepare("SELECT id, alliance_id, is_admin, is_moderator FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $currentUser = $stmtUser->fetch();
        if (!$currentUser) return [];

        $isStaff = ((int)$currentUser['is_admin'] === 1 || (int)($currentUser['is_moderator'] ?? 0) === 1);
        $userAllianceId = $currentUser['alliance_id'] ? (int)$currentUser['alliance_id'] : null;

        $params = [];
        $where = [];

        if ($channelType === 'global') {
            $where[] = "m.channel_type = 'global'";
        } elseif ($channelType === 'alliance') {
            if (!$userAllianceId) {
                return []; // Pas d'alliance, pas de messages
            }
            $where[] = "m.channel_type = 'alliance' AND m.channel_target_id = ?";
            $params[] = $userAllianceId;
        } elseif ($channelType === 'whisper') {
            if (!$otherUserId) {
                return [];
            }
            $where[] = "m.channel_type = 'whisper' AND (
                (m.sender_id = ? AND m.recipient_id = ?) 
                OR 
                (m.sender_id = ? AND m.recipient_id = ?)
            )";
            $params[] = $userId;
            $params[] = $otherUserId;
            $params[] = $otherUserId;
            $params[] = $userId;
        }

        if ($lastId > 0) {
            $where[] = "m.id > ?";
            $params[] = $lastId;
            $order = "ASC";
        } else {
            $order = "DESC";
        }

        $whereClause = implode(" AND ", $where);
        $limitInt = max(1, min(100, $limit));

        $sql = "
            SELECT m.*,
                   u.username as sender_username,
                   u.faction as sender_faction,
                   u.is_admin as sender_is_admin,
                   u.is_moderator as sender_is_moderator,
                   u.points as sender_points,
                   a.tag as sender_alliance_tag,
                   a.name as sender_alliance_name,
                   recip.username as recipient_username
            FROM chat_messages m
            INNER JOIN users u ON m.sender_id = u.id
            LEFT JOIN users recip ON m.recipient_id = recip.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE {$whereClause}
            ORDER BY m.id {$order}
            LIMIT {$limitInt}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si récupération initiale (lastId == 0), réordonner par ID croissant pour affichage chronologique
        if ($lastId === 0) {
            $rows = array_reverse($rows);
        }

        // Formater les messages pour le client
        $results = [];
        foreach ($rows as $r) {
            $isSender = ((int)$r['sender_id'] === $userId);
            $canDelete = ($isSender || $isStaff);
            $factionData = FACTIONS[$r['sender_faction']] ?? ['name' => ucfirst($r['sender_faction']), 'icon' => '🏯'];

            $results[] = [
                'id' => (int)$r['id'],
                'channel_type' => $r['channel_type'],
                'sender_id' => (int)$r['sender_id'],
                'sender_username' => $r['sender_username'],
                'sender_faction' => $r['sender_faction'],
                'sender_faction_name' => $factionData['name'],
                'sender_faction_icon' => $factionData['icon'],
                'sender_alliance_tag' => $r['sender_alliance_tag'],
                'sender_is_admin' => (int)$r['sender_is_admin'] === 1,
                'sender_is_moderator' => (int)$r['sender_is_moderator'] === 1,
                'recipient_id' => $r['recipient_id'] ? (int)$r['recipient_id'] : null,
                'recipient_username' => $r['recipient_username'],
                'message' => (int)$r['is_deleted'] === 1 ? '<em>Message retiré par le Shogunat ou son auteur</em>' : htmlspecialchars($r['message']),
                'is_deleted' => (int)$r['is_deleted'] === 1,
                'is_self' => $isSender,
                'can_delete' => $canDelete && (int)$r['is_deleted'] === 0,
                'time_formatted' => date('H:i:s', strtotime($r['created_at'])),
                'date_formatted' => date('d/m/Y H:i', strtotime($r['created_at']))
            ];
        }

        return $results;
    }

    /**
     * Récupère la liste des conversations privées récentes du joueur
     */
    public function getRecentConversations(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                CASE WHEN m.sender_id = :u1 THEN m.recipient_id ELSE m.sender_id END AS other_user_id,
                MAX(m.id) as max_msg_id
            FROM chat_messages m
            WHERE m.channel_type = 'whisper' 
              AND (m.sender_id = :u2 OR m.recipient_id = :u3)
            GROUP BY other_user_id
            ORDER BY max_msg_id DESC
            LIMIT 20
        ");
        $stmt->execute(['u1' => $userId, 'u2' => $userId, 'u3' => $userId]);
        $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($convs as $c) {
            $otherId = (int)$c['other_user_id'];
            if (!$otherId) continue;

            $stmtInfo = $this->db->prepare("
                SELECT u.id, u.username, u.faction, u.points, u.is_admin, u.is_moderator, u.last_active,
                       a.tag as alliance_tag
                FROM users u
                LEFT JOIN alliances a ON u.alliance_id = a.id
                WHERE u.id = ?
            ");
            $stmtInfo->execute([$otherId]);
            $userInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            if (!$userInfo) continue;

            // Dernier message échangé
            $stmtLast = $this->db->prepare("
                SELECT message, sender_id, created_at, is_deleted 
                FROM chat_messages 
                WHERE id = ?
            ");
            $stmtLast->execute([$c['max_msg_id']]);
            $lastMsg = $stmtLast->fetch(PDO::FETCH_ASSOC);

            $isOnline = (time() - strtotime($userInfo['last_active'] ?? '2000-01-01')) < 300;

            $results[] = [
                'user_id' => $otherId,
                'username' => $userInfo['username'],
                'faction' => $userInfo['faction'],
                'alliance_tag' => $userInfo['alliance_tag'],
                'points' => (int)$userInfo['points'],
                'is_admin' => (int)$userInfo['is_admin'] === 1,
                'is_moderator' => (int)($userInfo['is_moderator'] ?? 0) === 1,
                'is_online' => $isOnline,
                'last_message' => $lastMsg ? ((int)$lastMsg['is_deleted'] === 1 ? 'Message retiré' : mb_substr($lastMsg['message'], 0, 50)) : '',
                'last_message_time' => $lastMsg ? date('d/m H:i', strtotime($lastMsg['created_at'])) : '',
                'last_message_is_self' => $lastMsg ? ((int)$lastMsg['sender_id'] === $userId) : false
            ];
        }

        return $results;
    }

    /**
     * Supprime logiquement un message (par son auteur ou par un membre du Staff)
     */
    public function deleteMessage(int $messageId, int $userId): array {
        $stmt = $this->db->prepare("SELECT sender_id, is_deleted FROM chat_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$msg) {
            return ['success' => false, 'error' => 'Message introuvable.'];
        }

        if ((int)$msg['is_deleted'] === 1) {
            return ['success' => false, 'error' => 'Ce message est déjà retiré.'];
        }

        $stmtUser = $this->db->prepare("SELECT is_admin, is_moderator FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $u = $stmtUser->fetch();
        $isStaff = ($u && ((int)$u['is_admin'] === 1 || (int)($u['is_moderator'] ?? 0) === 1));
        $isAuthor = ((int)$msg['sender_id'] === $userId);

        if (!$isAuthor && !$isStaff) {
            return ['success' => false, 'error' => 'Privilèges insuffisants pour supprimer ce message.'];
        }

        $upd = $this->db->prepare("UPDATE chat_messages SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $upd->execute([$userId, $messageId]);

        return ['success' => true, 'message' => 'Message retiré du chat.'];
    }

    /**
     * Récupère la liste des Daimyōs actuellement connectés pour le salon de discussion
     */
    public function getOnlineChatters(int $minutes = 5): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.faction, u.points, u.is_admin, u.is_moderator,
                   a.tag as alliance_tag
            FROM users u
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE u.is_bot = 0 
              AND u.last_active >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY u.points DESC, u.username ASC
            LIMIT 50
        ");
        $stmt->execute([$minutes]);
        $chatters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $list = [];
        foreach ($chatters as $c) {
            $factionData = FACTIONS[$c['faction']] ?? ['name' => ucfirst($c['faction']), 'icon' => '🏯'];
            $list[] = [
                'id' => (int)$c['id'],
                'username' => $c['username'],
                'faction' => $c['faction'],
                'faction_name' => $factionData['name'],
                'faction_icon' => $factionData['icon'],
                'alliance_tag' => $c['alliance_tag'],
                'points' => (int)$c['points'],
                'is_admin' => (int)$c['is_admin'] === 1,
                'is_moderator' => (int)($c['is_moderator'] ?? 0) === 1
            ];
        }

        return $list;
    }
}

