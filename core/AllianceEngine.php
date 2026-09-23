<?php
/**
 * Moteur des Alliances Féodales d'OpenShogun
 * Gestion des clans, pavillon diplomatique, capacités, invitations et pactes
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/game_constants.php';

class AllianceEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureAllianceTables();
    }

    /**
     * Auto-guérison du schéma de base de données pour les alliances
     */
    public function ensureAllianceTables(): void {
        try {
            $this->db->exec("
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

            // Vérifier colonne users.alliance_id
            $cols = $this->db->query("SHOW COLUMNS FROM users LIKE 'alliance_id'")->fetchAll();
            if (empty($cols)) {
                $this->db->exec("ALTER TABLE users ADD COLUMN alliance_id INT UNSIGNED NULL DEFAULT NULL AFTER faction");
                $this->db->exec("ALTER TABLE users ADD INDEX idx_user_alliance (alliance_id)");
            }

            // Table alliance_invitations
            $this->db->exec("
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
        } catch (Exception $e) {
            error_log("AllianceEngine::ensureAllianceTables - " . $e->getMessage());
        }
    }

    /**
     * Récupère le niveau maximal du Pavillon Diplomatique d'un joueur à travers tous ses fiefs
     */
    public function getUserMaxEmbassyLevel(int $userId): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(pb.level), 0) as max_level
            FROM planets p
            LEFT JOIN planet_buildings pb ON pb.planet_id = p.id AND pb.building_type = 'embassy'
            WHERE p.user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['max_level'] ?? 0);
    }

    /**
     * Récupère les données d'une alliance par son ID
     */
    public function getAlliance(int $allianceId): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username as leader_name, u.faction as leader_faction,
                   COUNT(m.id) as member_count,
                   COALESCE(SUM(m.points), 0) as total_points
            FROM alliances a
            JOIN users u ON a.leader_id = u.id
            LEFT JOIN users m ON m.alliance_id = a.id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        $stmt->execute([$allianceId]);
        $alliance = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$alliance) {
            return null;
        }

        $capacity = $this->getAllianceCapacity($allianceId, (int)$alliance['leader_id']);
        $alliance['capacity'] = $capacity;
        $alliance['is_full'] = ((int)$alliance['member_count'] >= $capacity);
        return $alliance;
    }

    /**
     * Récupère l'alliance d'un utilisateur
     */
    public function getUserAlliance(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT alliance_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $allianceId = $stmt->fetchColumn();
        if (!$allianceId) {
            return null;
        }
        return $this->getAlliance((int)$allianceId);
    }

    /**
     * Calcule la capacité maximale en joueurs d'une alliance
     * Règle : Indexée sur le niveau du Pavillon Diplomatique du Chef (+3 membres par niveau, max 60)
     */
    public function getAllianceCapacity(int $allianceId, ?int $leaderId = null): int {
        if ($leaderId === null) {
            $stmt = $this->db->prepare("SELECT leader_id FROM alliances WHERE id = ?");
            $stmt->execute([$allianceId]);
            $leaderId = (int)$stmt->fetchColumn();
        }

        $embassyLevel = $this->getUserMaxEmbassyLevel($leaderId);
        // Garantit au minimum le niveau 3 lors de la création
        $effectiveLevel = max(ALLIANCE_CREATION_MIN_EMBASSY_LEVEL, $embassyLevel);

        $capacity = $effectiveLevel * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL;
        return min(ALLIANCE_MAX_MEMBERS, $capacity);
    }

    /**
     * Récupère la liste ordonnée des membres d'une alliance
     */
    public function getAllianceMembers(int $allianceId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.faction, u.points, u.last_active, u.created_at,
                   u.protection_until,
                   COUNT(p.id) as planet_count,
                   (u.id = a.leader_id) as is_leader
            FROM users u
            JOIN alliances a ON u.alliance_id = a.id
            LEFT JOIN planets p ON p.user_id = u.id
            WHERE u.alliance_id = ?
            GROUP BY u.id
            ORDER BY is_leader DESC, u.points DESC, u.id ASC
        ");
        $stmt->execute([$allianceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Création d'une nouvelle alliance féodale
     * Condition stricte : Pavillon Diplomatique >= Niveau 3
     */
    public function createAlliance(int $userId, string $name, string $tag, string $description = ''): array {
        $name = trim($name);
        $tag = strtoupper(trim($tag));
        $description = trim($description);

        // 1. Vérifier si l'utilisateur est déjà dans une alliance
        $currentAlliance = $this->getUserAlliance($userId);
        if ($currentAlliance) {
            return ['success' => false, 'error' => 'Vous faites déjà partie d\'un clan féodal. Vous devez le quitter avant d\'en fonder un nouveau.'];
        }

        // 2. Vérifier le niveau du Pavillon Diplomatique
        $embassyLevel = $this->getUserMaxEmbassyLevel($userId);
        if ($embassyLevel < ALLIANCE_CREATION_MIN_EMBASSY_LEVEL) {
            return [
                'success' => false,
                'error' => "Le Pavillon Diplomatique doit atteindre le Niveau " . ALLIANCE_CREATION_MIN_EMBASSY_LEVEL . " pour fonder une Alliance Féodale (Niveau actuel : {$embassyLevel}/" . ALLIANCE_CREATION_MIN_EMBASSY_LEVEL . ")."
            ];
        }

        // 3. Valider le Nom (3 à 40 caractères)
        if (mb_strlen($name) < 3 || mb_strlen($name) > 40) {
            return ['success' => false, 'error' => 'Le nom de l\'alliance doit contenir entre 3 et 40 caractères.'];
        }

        // 4. Valider le Tag / Sigle (2 à 8 caractères alphanumériques)
        if (!preg_match('/^[A-Z0-9_\-]{2,8}$/i', $tag)) {
            return ['success' => false, 'error' => 'Le sigle (tag) doit contenir entre 2 et 8 caractères alphanumériques (ex: SHOGUN, TDK, ODA).'];
        }

        // 5. Vérifier l'unicité du Nom et du Tag
        $stmt = $this->db->prepare("SELECT id FROM alliances WHERE name = ? OR tag = ?");
        $stmt->execute([$name, $tag]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Ce nom d\'alliance ou ce sigle est déjà réservé par un autre Daimyō.'];
        }

        // 6. Insérer l'alliance
        $this->db->beginTransaction();
        try {
            $stmtInsert = $this->db->prepare("
                INSERT INTO alliances (name, tag, leader_id, description, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtInsert->execute([$name, $tag, $userId, $description]);
            $allianceId = (int)$this->db->lastInsertId();

            // Mettre à jour l'utilisateur créateur
            $this->db->prepare("UPDATE users SET alliance_id = ? WHERE id = ?")->execute([$allianceId, $userId]);

            // Annuler toute autre invitation en attente reçue par cet utilisateur
            $this->db->prepare("UPDATE alliance_invitations SET status = 'canceled' WHERE user_id = ? AND status = 'pending'")->execute([$userId]);

            $this->db->commit();

            return [
                'success' => true,
                'alliance_id' => $allianceId,
                'message' => "Le pacte féodal a été scellé ! Votre alliance [{$tag}] {$name} a été officiellement proclamée."
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la création de l\'alliance : ' . $e->getMessage()];
        }
    }

    /**
     * Inviter un Daimyō à rejoindre l'alliance
     */
    public function inviteUser(int $leaderId, string $targetUsername, string $message = ''): array {
        $targetUsername = trim($targetUsername);

        // 1. Récupérer l'alliance du chef
        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Seul le Chef d\'alliance a l\'autorité nécessaire pour envoyer des invitations.'];
        }

        // 2. Vérifier la capacité maximale
        if ($alliance['is_full']) {
            return [
                'success' => false,
                'error' => "L'alliance a atteint sa capacité maximale ({$alliance['member_count']}/{$alliance['capacity']} joueurs). Élevez le Pavillon Diplomatique pour débloquer plus de places."
            ];
        }

        // 3. Trouver le joueur cible
        $stmtTarget = $this->db->prepare("SELECT id, username, alliance_id FROM users WHERE username = ?");
        $stmtTarget->execute([$targetUsername]);
        $targetUser = $stmtTarget->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            return ['success' => false, 'error' => "Aucun Daimyō ne répond au nom de '{$targetUsername}'."];
        }

        if ((int)$targetUser['id'] === $leaderId) {
            return ['success' => false, 'error' => 'Vous êtes déjà le chef suprême de ce clan féodal.'];
        }

        if (!empty($targetUser['alliance_id'])) {
            return ['success' => false, 'error' => "Ce Daimyō est déjà lié par serment à une autre alliance."];
        }

        // 4. Vérifier si une invitation en attente existe déjà
        $stmtCheck = $this->db->prepare("
            SELECT id FROM alliance_invitations 
            WHERE alliance_id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmtCheck->execute([$alliance['id'], $targetUser['id']]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'error' => "Une invitation est déjà en cours d'acheminement vers ce Daimyō."];
        }

        // 5. Créer l'invitation
        $stmtInv = $this->db->prepare("
            INSERT INTO alliance_invitations (alliance_id, user_id, sender_id, type, message, status, created_at)
            VALUES (?, ?, ?, 'invitation', ?, 'pending', NOW())
        ");
        $stmtInv->execute([$alliance['id'], $targetUser['id'], $leaderId, $message]);

        // Envoyer une missive diplomatique automatique
        try {
            $msgSubject = "📜 Invitation d'Alliance : Reoindre [{$alliance['tag']}] {$alliance['name']}";
            $msgBody = "Salutations noble Daimyō {$targetUser['username']},\n\nLe Chef de l'alliance [{$alliance['tag']}] {$alliance['name']} vous invite officiellement à sceller un pacte féodal et rejoindre ses rangs.\n\nRendez-vous dans votre Pavillon Diplomatique pour accepter ou décliner cette offre.";
            $stmtMsg = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body, created_at) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())");
            $stmtMsg->execute([$leaderId, $targetUser['id'], $msgSubject, $msgBody]);
        } catch (Exception $e) {
            // Ignorer si la messagerie échoue
        }

        return [
            'success' => true,
            'message' => "L'invitation diplomatique a été transmise à {$targetUsername} avec succès."
        ];
    }

    /**
     * Annuler une invitation en attente
     */
    public function cancelInvitation(int $leaderId, int $invitationId): array {
        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Action non autorisée.'];
        }

        $stmt = $this->db->prepare("
            UPDATE alliance_invitations 
            SET status = 'canceled' 
            WHERE id = ? AND alliance_id = ? AND status = 'pending'
        ");
        $stmt->execute([$invitationId, $alliance['id']]);

        return ['success' => true, 'message' => 'L\'invitation a été révoquée.'];
    }

    /**
     * Récupérer les invitations reçues par un utilisateur
     */
    public function getUserPendingInvitations(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT ai.*, a.name as alliance_name, a.tag as alliance_tag, a.leader_id,
                   u.username as sender_username,
                   COUNT(m.id) as current_members
            FROM alliance_invitations ai
            JOIN alliances a ON ai.alliance_id = a.id
            JOIN users u ON ai.sender_id = u.id
            LEFT JOIN users m ON m.alliance_id = a.id
            WHERE ai.user_id = ? AND ai.status = 'pending' AND ai.type = 'invitation'
            GROUP BY ai.id
            ORDER BY ai.created_at DESC
        ");
        $stmt->execute([$userId]);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($invitations as &$inv) {
            $inv['capacity'] = $this->getAllianceCapacity((int)$inv['alliance_id'], (int)$inv['leader_id']);
            $inv['is_full'] = ((int)$inv['current_members'] >= $inv['capacity']);
        }
        return $invitations;
    }

    /**
     * Récupérer les invitations envoyées par une alliance
     */
    public function getAlliancePendingInvitations(int $allianceId): array {
        $stmt = $this->db->prepare("
            SELECT ai.*, u.username as target_username, u.points as target_points, u.faction as target_faction,
                   sender.username as sender_username
            FROM alliance_invitations ai
            JOIN users u ON ai.user_id = u.id
            JOIN users sender ON ai.sender_id = sender.id
            WHERE ai.alliance_id = ? AND ai.status = 'pending'
            ORDER BY ai.created_at DESC
        ");
        $stmt->execute([$allianceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Répondre à une invitation reçue (accepter ou refuser)
     */
    public function respondInvitation(int $userId, int $invitationId, string $action): array {
        $action = strtolower(trim($action));
        if (!in_array($action, ['accept', 'reject'])) {
            return ['success' => false, 'error' => 'Action invalide.'];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM alliance_invitations 
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$invitationId, $userId]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) {
            return ['success' => false, 'error' => 'Cette invitation n\'est plus valide ou a déjà été traitée.'];
        }

        if ($action === 'reject') {
            $this->db->prepare("UPDATE alliance_invitations SET status = 'rejected' WHERE id = ?")->execute([$invitationId]);
            return ['success' => true, 'message' => 'L\'invitation a été déclinée.'];
        }

        // Pour accepter :
        // 1. Vérifier si l'utilisateur est déjà dans une alliance
        $currentAlliance = $this->getUserAlliance($userId);
        if ($currentAlliance) {
            return ['success' => false, 'error' => 'Vous appartenez déjà à une alliance féodale.'];
        }

        // 2. Vérifier le Pavillon Diplomatique (>= Niveau 1 requis pour rejoindre)
        $embassyLevel = $this->getUserMaxEmbassyLevel($userId);
        if ($embassyLevel < ALLIANCE_JOIN_MIN_EMBASSY_LEVEL) {
            return [
                'success' => false,
                'error' => "Vous devez édifier un Pavillon Diplomatique (Niveau " . ALLIANCE_JOIN_MIN_EMBASSY_LEVEL . " minimum) dans votre cité castrale pour pouvoir ratifier un traité d'alliance."
            ];
        }

        // 3. Vérifier la capacité de l'alliance cible
        $alliance = $this->getAlliance((int)$inv['alliance_id']);
        if (!$alliance) {
            return ['success' => false, 'error' => 'L\'alliance invitante n\'existe plus.'];
        }

        if ($alliance['is_full']) {
            return [
                'success' => false,
                'error' => "Cette alliance a atteint sa capacité maximale d'accueil ({$alliance['member_count']}/{$alliance['capacity']} joueurs). Impossible de la rejoindre pour le moment."
            ];
        }

        // 4. Intégrer le membre
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE users SET alliance_id = ? WHERE id = ?")->execute([$alliance['id'], $userId]);
            $this->db->prepare("UPDATE alliance_invitations SET status = 'accepted' WHERE id = ?")->execute([$invitationId]);
            // Annuler toutes les autres invitations en cours pour ce joueur
            $this->db->prepare("UPDATE alliance_invitations SET status = 'canceled' WHERE user_id = ? AND id != ? AND status = 'pending'")->execute([$userId, $invitationId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Félicitations ! Vous avez prêté allégeance et rejoint l'alliance [{$alliance['tag']}] {$alliance['name']} !"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la ratification : ' . $e->getMessage()];
        }
    }

    /**
     * Exclure un membre de l'alliance (réservé au Chef)
     */
    public function kickMember(int $leaderId, int $targetUserId): array {
        if ($leaderId === $targetUserId) {
            return ['success' => false, 'error' => 'Le chef ne peut pas s\'exclure lui-même. Vous devez dissoudre l\'alliance ou céder le commandement.'];
        }

        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Action réservée au Chef d\'alliance.'];
        }

        $stmt = $this->db->prepare("SELECT alliance_id, username FROM users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target || (int)$target['alliance_id'] !== (int)$alliance['id']) {
            return ['success' => false, 'error' => 'Ce joueur ne fait pas partie de votre alliance.'];
        }

        $this->db->prepare("UPDATE users SET alliance_id = NULL WHERE id = ?")->execute([$targetUserId]);

        return ['success' => true, 'message' => "Le Daimyō {$target['username']} a été exclu de l'alliance."];
    }

    /**
     * Quitter une alliance féodale
     */
    public function leaveAlliance(int $userId): array {
        $alliance = $this->getUserAlliance($userId);
        if (!$alliance) {
            return ['success' => false, 'error' => 'Vous ne faites partie d\'aucune alliance.'];
        }

        if ((int)$alliance['leader_id'] === $userId) {
            return [
                'success' => false,
                'error' => 'En tant que Chef Suprême, vous ne pouvez pas simplement déserter votre alliance. Vous devez céder le commandement à un autre membre ou dissoudre l\'alliance.'
            ];
        }

        $this->db->prepare("UPDATE users SET alliance_id = NULL WHERE id = ?")->execute([$userId]);
        return ['success' => true, 'message' => "Vous avez rompu votre pacte et quitté l'alliance [{$alliance['tag']}]."];
    }

    /**
     * Céder le commandement de l'alliance à un autre membre
     */
    public function transferLeadership(int $leaderId, int $newLeaderId): array {
        if ($leaderId === $newLeaderId) {
            return ['success' => false, 'error' => 'Vous êtes déjà le chef de cette alliance.'];
        }

        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Seul le Chef actuel peut transmettre le commandement.'];
        }

        // Vérifier que le nouveau chef est bien dans l'alliance
        $stmt = $this->db->prepare("SELECT id, username, alliance_id FROM users WHERE id = ?");
        $stmt->execute([$newLeaderId]);
        $newLeader = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newLeader || (int)$newLeader['alliance_id'] !== (int)$alliance['id']) {
            return ['success' => false, 'error' => 'Le successeur désigné doit être un membre de votre alliance.'];
        }

        $this->db->prepare("UPDATE alliances SET leader_id = ? WHERE id = ?")->execute([$newLeaderId, $alliance['id']]);

        return ['success' => true, 'message' => "Le titre de Chef Suprême a été transmis avec honneur à {$newLeader['username']}."];
    }

    /**
     * Dissoudre une alliance féodale (réservé au Chef)
     */
    public function disbandAlliance(int $leaderId): array {
        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Seul le Chef suprême peut dissoudre l\'alliance.'];
        }

        $this->db->beginTransaction();
        try {
            // Libérer tous les membres
            $this->db->prepare("UPDATE users SET alliance_id = NULL WHERE alliance_id = ?")->execute([$alliance['id']]);
            // Supprimer les invitations
            $this->db->prepare("DELETE FROM alliance_invitations WHERE alliance_id = ?")->execute([$alliance['id']]);
            // Supprimer l'alliance
            $this->db->prepare("DELETE FROM alliances WHERE id = ?")->execute([$alliance['id']]);

            $this->db->commit();
            return ['success' => true, 'message' => "L'alliance [{$alliance['tag']}] {$alliance['name']} a été dissoute."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la dissolution : ' . $e->getMessage()];
        }
    }

    /**
     * Mettre à jour la description / charte féodale de l'alliance
     */
    public function updateAllianceDescription(int $leaderId, string $description): array {
        $alliance = $this->getUserAlliance($leaderId);
        if (!$alliance || (int)$alliance['leader_id'] !== $leaderId) {
            return ['success' => false, 'error' => 'Action réservée au Chef d\'alliance.'];
        }

        $this->db->prepare("UPDATE alliances SET description = ? WHERE id = ?")->execute([trim($description), $alliance['id']]);
        return ['success' => true, 'message' => 'La charte féodale a été mise à jour avec succès.'];
    }

    /**
     * Récupère le classement des alliances par points de puissance
     */
    public function getAlliancesRanking(int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT a.id, a.name, a.tag, a.created_at, a.leader_id,
                   u.username as leader_name, u.faction as leader_faction,
                   COUNT(m.id) as member_count,
                   COALESCE(SUM(m.points), 0) as total_points,
                   ROUND(COALESCE(AVG(m.points), 0)) as avg_points,
                   COUNT(p.id) as total_planets
            FROM alliances a
            JOIN users u ON a.leader_id = u.id
            LEFT JOIN users m ON m.alliance_id = a.id
            LEFT JOIN planets p ON p.user_id = m.id
            GROUP BY a.id
            ORDER BY total_points DESC, member_count DESC, a.id ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rankings as &$r) {
            $r['capacity'] = $this->getAllianceCapacity((int)$r['id'], (int)$r['leader_id']);
        }
        return $rankings;
    }

    /**
     * Recherche d'alliances
     */
    public function searchAlliances(string $query = '', int $limit = 20): array {
        $query = trim($query);
        $sql = "
            SELECT a.id, a.name, a.tag, a.description, a.leader_id,
                   u.username as leader_name, u.faction as leader_faction,
                   COUNT(m.id) as member_count,
                   COALESCE(SUM(m.points), 0) as total_points
            FROM alliances a
            JOIN users u ON a.leader_id = u.id
            LEFT JOIN users m ON m.alliance_id = a.id
        ";
        $params = [];
        if (!empty($query)) {
            $sql .= " WHERE a.name LIKE ? OR a.tag LIKE ?";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }
        $sql .= " GROUP BY a.id ORDER BY total_points DESC LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$a) {
            $a['capacity'] = $this->getAllianceCapacity((int)$a['id'], (int)$a['leader_id']);
        }
        return $results;
    }
}

