<?php
/**
 * Moteur de Gestion de l'Assistance, Remontées de Bugs & Suggestions (OpenShogun)
 */
require_once __DIR__ . '/Database.php';

class SupportEngine {
    private PDO $db;

    public const VALID_TYPES = ['bug', 'suggestion'];
    public const VALID_SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const VALID_STATUSES = ['pending', 'in_progress', 'resolved', 'planned', 'closed'];

    public const CATEGORIES = [
        'resources' => 'Terroir & Ressources',
        'city' => 'Cité Castrale & Bâtiments',
        'troops' => 'Dojo & Infanterie',
        'siege' => 'Atelier de Siège & Écuries',
        'hero' => 'Héros Samouraï & Aventures',
        'map' => 'Carte des Provinces & Oasis',
        'quests' => 'Quêtes & Didacticiel',
        'combat' => 'Combat & Rapports',
        'ui' => 'Interface & Affichage',
        'other' => 'Autre / Divers'
    ];

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Crée un nouveau ticket de bug ou suggestion
     */
    public function createTicket(
        int $userId,
        string $type,
        string $category,
        string $title,
        string $description,
        string $severity = 'medium',
        ?int $planetId = null
    ): array {
        $type = in_array($type, self::VALID_TYPES) ? $type : 'bug';
        $severity = in_array($severity, self::VALID_SEVERITIES) ? $severity : 'medium';
        $category = array_key_exists($category, self::CATEGORIES) ? $category : 'other';

        $title = trim($title);
        $description = trim($description);

        if (mb_strlen($title) < 4 || mb_strlen($title) > 150) {
            throw new InvalidArgumentException("Le titre doit comporter entre 4 et 150 caractères.");
        }
        if (mb_strlen($description) < 10) {
            throw new InvalidArgumentException("La description doit comporter au moins 10 caractères.");
        }

        $now = time();
        $stmt = $this->db->prepare("
            INSERT INTO support_tickets 
            (user_id, type, category, severity, title, description, planet_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $type,
            $category,
            $severity,
            $title,
            $description,
            $planetId,
            $now,
            $now
        ]);

        $ticketId = (int)$this->db->lastInsertId();

        return [
            'id' => $ticketId,
            'title' => $title,
            'type' => $type,
            'status' => 'pending'
        ];
    }

    /**
     * Récupère les tickets soumis par un joueur avec détails
     */
    public function getUserTickets(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT t.*, u_adm.username as admin_username
            FROM support_tickets t
            LEFT JOIN users u_adm ON t.admin_id = u_adm.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un ticket par son identifiant
     */
    public function getTicketById(int $ticketId): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   u.username, u.email, u.faction, u.points,
                   p.name as planet_name, p.coord_x, p.coord_y,
                   u_adm.username as admin_username
            FROM support_tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN planets p ON t.planet_id = p.id
            LEFT JOIN users u_adm ON t.admin_id = u_adm.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticketId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Récupère la liste de tous les tickets pour l'administration (avec filtres et recherche)
     */
    public function getAllTickets(
        ?string $type = null,
        ?string $status = null,
        ?string $search = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $sql = "
            SELECT t.*, 
                   u.username, u.faction,
                   u_adm.username as admin_username
            FROM support_tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN users u_adm ON t.admin_id = u_adm.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($type) && in_array($type, self::VALID_TYPES)) {
            $sql .= " AND t.type = ?";
            $params[] = $type;
        }

        if (!empty($status) && in_array($status, self::VALID_STATUSES)) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR u.username LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY 
            CASE t.status 
                WHEN 'pending' THEN 1 
                WHEN 'in_progress' THEN 2 
                WHEN 'planned' THEN 3 
                WHEN 'resolved' THEN 4 
                WHEN 'closed' THEN 5 
                ELSE 6 
            END ASC, 
            CASE t.severity 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END ASC,
            t.created_at DESC 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sanitise le HTML produit par Quill.js côté serveur (liste blanche de balises sûres).
     * Supprime tout attribut potentiellement dangereux (on*, href="javascript:…", etc.).
     *
     * @param  string $html HTML brut issu de Quill
     * @return string HTML assaini
     */
    public static function sanitizeHtml(string $html): string {
        // Liste blanche des balises autorisées et de leurs attributs
        $allowedTags = '<p><br><strong><em><u><s><ol><ul><li><blockquote><pre><code><h1><h2><h3><span><a>';

        // Supprimer d'abord les balises script/style/iframe/object complètes (avec contenu)
        $html = preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#si', '', $html);

        // Retirer les balises non autorisées
        $html = strip_tags($html, $allowedTags);

        // Supprimer les attributs dangereux sur les balises restantes :
        // on*, href="javascript:…", src="data:…", style (XSS via expression CSS)
        $html = preg_replace_callback('/<([a-z][a-z0-9]*)\s+([^>]+)>/i', function ($m) {
            $tag   = $m[1];
            $attrs = $m[2];

            // Retirer les handlers d'événements (on*)
            $attrs = preg_replace('/\s*on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);
            // Retirer href/src contenant javascript: ou data:
            $attrs = preg_replace('/\s*(href|src)\s*=\s*(?:"javascript:[^"]*"|\'javascript:[^\']*\')/i', '', $attrs);
            $attrs = preg_replace('/\s*src\s*=\s*(?:"data:[^"]*"|\'data:[^\']*\')/i', '', $attrs);
            // Retirer les attributs style (peuvent contenir expression())
            $attrs = preg_replace('/\s*style\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);

            $attrs = trim($attrs);
            return $attrs ? "<{$tag} {$attrs}>" : "<{$tag}>";
        }, $html);

        return trim($html);
    }

    /**
     * Retourne les champs éditables d'un ticket si l'utilisateur en est l'auteur.
     * Utilisé pour pré-remplir le formulaire d'édition.
     *
     * @throws RuntimeException si introuvable ou accès interdit
     */
    public function getTicketForEdit(int $ticketId, int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, type, category, severity, title, description, status, updated_at
            FROM support_tickets
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            throw new RuntimeException("Ticket #$ticketId introuvable.", 404);
        }
        if ((int)$ticket['user_id'] !== $userId) {
            throw new RuntimeException("Vous n'êtes pas l'auteur de ce ticket.", 403);
        }
        if ($ticket['status'] !== 'pending') {
            throw new RuntimeException("Ce ticket a déjà été pris en charge et ne peut plus être modifié.", 403);
        }

        return $ticket;
    }

    /**
     * Modifie un ticket par son auteur (modification autorisée uniquement si statut = pending).
     *
     * @throws RuntimeException si le ticket est introuvable, l'auteur incorrect ou le statut non éditable
     * @throws InvalidArgumentException si les données envoyées sont invalides
     */
    public function updateTicketByAuthor(
        int    $ticketId,
        int    $userId,
        string $title,
        string $description,
        string $type,
        string $category,
        string $severity = 'medium'
    ): array {
        // 1. Vérification d'accès et de statut (lève une RuntimeException si ko)
        $this->getTicketForEdit($ticketId, $userId);

        // 2. Validation et normalisation des données entrantes
        $type     = in_array($type,     self::VALID_TYPES,      true) ? $type     : 'bug';
        $severity = in_array($severity, self::VALID_SEVERITIES, true) ? $severity : 'medium';
        $category = array_key_exists($category, self::CATEGORIES)     ? $category : 'other';

        $title       = trim($title);
        $description = self::sanitizeHtml($description); // purification du HTML Quill

        if (mb_strlen($title) < 4 || mb_strlen($title) > 150) {
            throw new InvalidArgumentException("Le titre doit comporter entre 4 et 150 caractères.");
        }
        if (mb_strlen(strip_tags($description)) < 10) {
            throw new InvalidArgumentException("La description doit comporter au moins 10 caractères de contenu.");
        }

        // 3. Mise à jour en base
        $now  = time();
        $stmt = $this->db->prepare("
            UPDATE support_tickets
            SET title = ?, description = ?, type = ?, category = ?, severity = ?, updated_at = ?
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$title, $description, $type, $category, $severity, $now, $ticketId, $userId]);

        if ($stmt->rowCount() === 0) {
            // Double vérification : si aucune ligne modifiée, le ticket a peut-être changé de statut entre temps
            throw new RuntimeException("La mise à jour a échoué : le ticket n'est plus éditable ou n'existe plus.", 409);
        }

        return [
            'id'         => $ticketId,
            'title'      => $title,
            'type'       => $type,
            'category'   => $category,
            'severity'   => $severity,
            'updated_at' => $now,
        ];
    }

    /**
     * Traite un ticket : mise à jour du statut, réponse officielle et notification facultative du joueur
     */

    public function updateTicketStatus(
        int $ticketId,
        string $status,
        ?string $adminResponse = null,
        ?int $adminId = null,
        bool $notifyUser = true
    ): array {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new InvalidArgumentException("Statut invalide.");
        }

        $ticket = $this->getTicketById($ticketId);
        if (!$ticket) {
            throw new RuntimeException("Ticket introuvable.");
        }

        $now = time();
        $adminResponseTrim = trim($adminResponse ?? '');

        $stmt = $this->db->prepare("
            UPDATE support_tickets 
            SET status = ?, 
                admin_response = ?, 
                admin_id = ?, 
                responded_at = IF(? != '', ?, responded_at), 
                updated_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $status,
            $adminResponseTrim ?: null,
            $adminId,
            $adminResponseTrim,
            $now,
            $now,
            $ticketId
        ]);

        // Envoi d'une notification en jeu (missive) au joueur si demandé
        if ($notifyUser && (!empty($adminResponseTrim) || $status !== $ticket['status'])) {
            $statusLabels = [
                'pending' => 'En attente',
                'in_progress' => 'En cours d\'examen',
                'resolved' => 'Résolu / Corrigé',
                'planned' => 'Retenu pour prochaine mise à jour',
                'closed' => 'Fermé / Sans suite'
            ];
            $typeLabel = ($ticket['type'] === 'bug') ? 'Dysfonctionnement' : 'Suggestion';

            $subject = "Conseil du Shogunat : Suivi de votre " . $typeLabel . " (#" . $ticketId . ")";
            $body = "Salutations, honorable Daimyō " . htmlspecialchars($ticket['username']) . ",\n\n"
                  . "Votre demande concernant « " . htmlspecialchars($ticket['title']) . " » a été examinée par les intendants et développeurs du Shogunat.\n\n"
                  . "Nouveau statut : " . ($statusLabels[$status] ?? $status) . "\n";

            if (!empty($adminResponseTrim)) {
                $body .= "\nRéponse officielle de l'équipe :\n« " . htmlspecialchars($adminResponseTrim) . " »\n";
            }

            $body .= "\nVous pouvez consulter le détail de vos demandes dans l'onglet Assistance & Suggestions (📮).\n"
                   . "Que la paix et la prospérité accompagnent votre fief.";

            $msgStmt = $this->db->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
                VALUES (NULL, ?, ?, ?, 0, ?)
            ");
            $msgStmt->execute([
                $ticket['user_id'],
                $subject,
                $body,
                $now
            ]);
        }

        return [
            'id' => $ticketId,
            'status' => $status,
            'admin_response' => $adminResponseTrim
        ];
    }

    /**
     * Supprime un ticket
     */
    public function deleteTicket(int $ticketId): bool {
        $stmt = $this->db->prepare("DELETE FROM support_tickets WHERE id = ?");
        return $stmt->execute([$ticketId]);
    }

    /**
     * Statistiques globales des tickets
     */
    public function getStatistics(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN type = 'bug' THEN 1 ELSE 0 END) as total_bugs,
                SUM(CASE WHEN type = 'suggestion' THEN 1 ELSE 0 END) as total_suggestions,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as count_pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as count_in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as count_resolved,
                SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as count_planned,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as count_closed
            FROM support_tickets
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int)($row['total'] ?? 0),
            'total_bugs' => (int)($row['total_bugs'] ?? 0),
            'total_suggestions' => (int)($row['total_suggestions'] ?? 0),
            'count_pending' => (int)($row['count_pending'] ?? 0),
            'count_in_progress' => (int)($row['count_in_progress'] ?? 0),
            'count_resolved' => (int)($row['count_resolved'] ?? 0),
            'count_planned' => (int)($row['count_planned'] ?? 0),
            'count_closed' => (int)($row['count_closed'] ?? 0)
        ];
    }
}

