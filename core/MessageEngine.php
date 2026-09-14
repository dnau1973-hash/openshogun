<?php
/**
 * Moteur de Messagerie Interstellaire entre Joueurs OpenGalaxy
 */
require_once __DIR__ . '/Database.php';

class MessageEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère le nombre de messages non lus d'un joueur
     */
    public function getUnreadCount(int $userId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM messages 
            WHERE receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère la boîte de réception (messages reçus)
     */
    public function getInbox(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   u.username as sender_name, 
                   u.faction as sender_faction,
                   u.points as sender_points
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ? AND m.deleted_by_receiver = 0
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère la boîte d'envoi (messages envoyés)
     */
    public function getOutbox(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   u.username as receiver_name, 
                   u.faction as receiver_faction,
                   u.points as receiver_points
            FROM messages m
            INNER JOIN users u ON m.receiver_id = u.id
            WHERE m.sender_id = ? AND m.deleted_by_sender = 0
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un message spécifique et le marque comme lu si l'utilisateur est le destinataire
     */
    public function getMessage(int $messageId, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   s.username as sender_name, 
                   s.faction as sender_faction,
                   r.username as receiver_name,
                   r.faction as receiver_faction
            FROM messages m
            LEFT JOIN users s ON m.sender_id = s.id
            LEFT JOIN users r ON m.receiver_id = r.id
            WHERE m.id = ? AND (
                (m.receiver_id = ? AND m.deleted_by_receiver = 0) OR 
                (m.sender_id = ? AND m.deleted_by_sender = 0)
            )
        ");
        $stmt->execute([$messageId, $userId, $userId]);
        $message = $stmt->fetch();

        if ($message) {
            // Marquer comme lu si c'est le destinataire
            if ((int)$message['receiver_id'] === $userId && !$message['is_read']) {
                $up = $this->db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
                $up->execute([$messageId]);
                $message['is_read'] = 1;
            }
        }

        return $message ?: null;
    }

    /**
     * Envoie un message à un autre joueur via son nom d'utilisateur ou son ID
     * Si $senderId est null, il s'agit d'une dépêche officielle du Système / Haut Commandement
     */
    public function sendMessage(?int $senderId, string|int $receiver, string $subject, string $body): array {
        $subject = trim($subject);
        $body = trim($body);

        if (empty($receiver)) {
            return ['success' => false, 'error' => 'Destinataire non spécifié.'];
        }

        if (empty($subject)) {
            $subject = 'Sans objet';
        }

        if (empty($body)) {
            return ['success' => false, 'error' => 'Le contenu du message ne peut pas être vide.'];
        }

        // Trouver le destinataire par ID ou par nom d'utilisateur
        if (is_numeric($receiver)) {
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([(int)$receiver]);
        } else {
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmt->execute([trim($receiver)]);
        }
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            return ['success' => false, 'error' => "Daimyō \"$receiver\" introuvable dans les provinces."];
        }

        if ($senderId !== null && (int)$targetUser['id'] === $senderId) {
            return ['success' => false, 'error' => 'Vous ne pouvez pas vous envoyer un message à vous-même.'];
        }

        $now = time();
        $ins = $this->db->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, deleted_by_receiver, deleted_by_sender, created_at)
            VALUES (?, ?, ?, ?, 0, 0, 0, ?)
        ");
        $ins->execute([$senderId, $targetUser['id'], $subject, $body, $now]);

        return [
            'success' => true,
            'message_id' => (int)$this->db->lastInsertId(),
            'receiver' => $targetUser['username']
        ];
    }

    /**
     * Envoie une transmission officielle du Système / Haut Commandement
     */
    public function sendSystemMessage(string|int $receiver, string $subject, string $body): array {
        return $this->sendMessage(null, $receiver, $subject, $body);
    }

    /**
     * Supprime un message (soft-delete ou hard-delete si les deux partis ont supprimé)
     */
    public function deleteMessage(int $messageId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $msg = $stmt->fetch();

        if (!$msg) return false;

        $isReceiver = ((int)$msg['receiver_id'] === $userId);
        $isSender = ((int)$msg['sender_id'] === $userId);

        if (!$isReceiver && !$isSender) return false;

        if ($isReceiver) {
            $this->db->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?")->execute([$messageId]);
        }
        if ($isSender) {
            $this->db->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?")->execute([$messageId]);
        }

        // Nettoyer si les deux l'ont supprimé (ou si message système supprimé par le destinataire)
        $this->db->prepare("
            DELETE FROM messages 
            WHERE id = ? AND (
                (deleted_by_receiver = 1 AND deleted_by_sender = 1) OR 
                (sender_id IS NULL AND deleted_by_receiver = 1)
            )
        ")->execute([$messageId]);

        return true;
    }

    /**
     * Récupère la liste de tous les autres commandants pour l'autocomplétion / choix
     */
    public function getAllOtherPlayers(int $currentUserId): array {
        $stmt = $this->db->prepare("
            SELECT id, username, faction, points 
            FROM users 
            WHERE id != ? 
            ORDER BY points DESC, username ASC
        ");
        $stmt->execute([$currentUserId]);
        return $stmt->fetchAll();
    }
}

