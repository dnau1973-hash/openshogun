<?php
/**
 * Moteur de Gestion de l'Assistance, Remontées de Bugs & Suggestions (OpenShogun)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MailService.php';

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
        int $offset = 0,
        ?string $severity = null
    ): array {
        $sql = "
            SELECT t.*, 
                   u.username, u.email, u.faction,
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

        if (!empty($severity) && in_array($severity, self::VALID_SEVERITIES)) {
            $sql .= " AND t.severity = ?";
            $params[] = $severity;
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
                responded_at = CASE WHEN ? != '' THEN ? ELSE responded_at END, 
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

        $emailResult = null;
        // Envoi d'une notification en jeu (missive) et par e-mail au joueur si demandé
        if ($notifyUser && (!empty($adminResponseTrim) || $status !== $ticket['status'])) {
            $statusLabels = [
                'pending'     => 'En attente',
                'in_progress' => 'En cours d\'examen',
                'resolved'    => 'Résolu / Corrigé',
                'planned'     => 'Retenu pour prochaine mise à jour',
                'closed'      => 'Fermé / Sans suite'
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

            // Envoi de l'e-mail transactionnel au joueur à chaque étape du traitement
            $emailResult = $this->sendTicketEvolutionEmail(
                $ticket,
                $ticket['status'],
                $status,
                $adminResponseTrim
            );
        }

        return [
            'id'             => $ticketId,
            'status'         => $status,
            'admin_response' => $adminResponseTrim,
            'email_sent'     => !empty($emailResult['success'])
        ];
    }

    /**
     * Envoie un e-mail transactionnel immersif au joueur pour notifier chaque étape d'évolution de son ticket
     */
    public function sendTicketEvolutionEmail(
        array $ticket,
        string $oldStatus,
        string $newStatus,
        string $adminResponseTrim
    ): array {
        $toEmail = trim($ticket['email'] ?? '');
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email du joueur absente ou invalide.'];
        }

        $ticketId = (int)$ticket['id'];
        $title = (string)$ticket['title'];
        $username = (string)($ticket['username'] ?? 'Daimyō');
        $type = (string)($ticket['type'] ?? 'bug');
        $categoryKey = (string)($ticket['category'] ?? 'other');
        $categoryName = self::CATEGORIES[$categoryKey] ?? 'Général / Autre';
        $severity = (string)($ticket['severity'] ?? 'medium');

        $statusLabels = [
            'pending'     => 'En attente d\'examen',
            'in_progress' => 'En cours d\'analyse & de correction',
            'resolved'    => 'Résolu / Corrigé en production',
            'planned'     => 'Retenu pour une prochaine version',
            'closed'      => 'Fermé (Sans suite / Non reproduit)',
        ];

        $statusColors = [
            'pending'     => '#f59e0b', // Ambre
            'in_progress' => '#0ea5e9', // Bleu ciel
            'resolved'    => '#10b981', // Vert émeraude
            'planned'     => '#8b5cf6', // Violet
            'closed'      => '#64748b', // Gris ardoise
        ];

        $severityLabels = [
            'critical' => 'Critique / Bloquante',
            'high'     => 'Élevée / Majeure',
            'medium'   => 'Modérée / Normale',
            'low'      => 'Faible / Cosmétique',
        ];

        $typeLabel = ($type === 'bug') ? 'Dysfonctionnement / Bug' : 'Suggestion & Évolution';
        $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;
        $badgeColor = $statusColors[$newStatus] ?? '#0ea5e9';
        $severityLabel = $severityLabels[$severity] ?? ucfirst($severity);

        $subject = "🏯 [OpenShogun] Évolution de votre signalement #{$ticketId} : {$newStatusLabel}";

        // URL directe vers le support en jeu
        $serverHost = $_SERVER['HTTP_HOST'] ?? 'openshogun.local';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https://' : 'http://';
        $supportUrl = $protocol . $serverHost . '/index.php?page=support';

        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeResponse = nl2br(htmlspecialchars($adminResponseTrim, ENT_QUOTES, 'UTF-8'));

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
</head>
<body style="margin:0; padding:0; background-color:#0b1120; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#e2e8f0;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0b1120;">
        <tr>
            <td align="center" style="padding: 35px 15px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="620" style="max-width:620px; background-color:#1e293b; border-radius:12px; overflow:hidden; border:1px solid #334155; box-shadow:0 12px 30px rgba(0,0,0,0.6);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg, #111827 0%, #1e1b4b 100%); padding:30px 20px; border-bottom:3px solid #eab308;">
                            <div style="font-size:38px; margin-bottom:8px;">🏯</div>
                            <h1 style="margin:0; color:#f8fafc; font-size:22px; font-weight:800; letter-spacing:1px; text-transform:uppercase;">
                                OpenShogun &bull; Support &amp; Développement
                            </h1>
                            <div style="color:#fef08a; font-size:12px; font-weight:700; letter-spacing:2px; text-transform:uppercase; margin-top:5px;">
                                Notification de Suivi Féodal &bull; Ticket #{$ticketId}
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:30px 32px; color:#e2e8f0; font-size:15px; line-height:1.6;">
                            <h2 style="margin:0 0 16px 0; color:#f8fafc; font-size:18px; font-weight:700;">
                                Salutations, honorable Daimyō <span style="color:#eab308;">{$safeUsername}</span> !
                            </h2>

                            <p style="margin:0 0 18px 0; color:#cbd5e1;">
                                Votre signalement déposé auprès des intendants du Shōgunat a franchi une nouvelle étape dans son analyse et son traitement par l'équipe des développeurs.
                            </p>

                            <!-- Ticket Detail Card -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0f172a; border:1px solid #334155; border-radius:8px; margin:20px 0; overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #1e293b; background-color:#172033;">
                                        <span style="color:#94a3b8; font-size:12px; text-transform:uppercase; font-weight:700; letter-spacing:1px;">Objet du Ticket</span>
                                        <div style="color:#f8fafc; font-size:16px; font-weight:bold; margin-top:3px;">{$safeTitle}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding:4px 0; color:#94a3b8; font-size:13px;" width="40%">Nature &amp; Domaine :</td>
                                                <td style="padding:4px 0; color:#f1f5f9; font-size:13px; font-weight:600;">{$typeLabel} &bull; {$categoryName}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#94a3b8; font-size:13px;">Niveau de gravité :</td>
                                                <td style="padding:4px 0; color:#f1f5f9; font-size:13px; font-weight:600;">{$severityLabel}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; color:#94a3b8; font-size:13px;">Évolution du statut :</td>
                                                <td style="padding:6px 0;">
                                                    <span style="color:#94a3b8; font-size:12px; text-decoration:line-through;">{$oldStatusLabel}</span>
                                                    <span style="color:#64748b; font-size:12px; margin:0 4px;">&rarr;</span>
                                                    <span style="display:inline-block; background-color:{$badgeColor}; color:#ffffff; font-size:12px; font-weight:bold; padding:3px 10px; border-radius:12px;">
                                                        {$newStatusLabel}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

HTML;

        // Si une réponse officielle est fournie
        if (!empty($adminResponseTrim)) {
            $htmlBody .= <<<HTML
                            <!-- Official Developer Response -->
                            <div style="background-color:#1e1b4b; border-left:4px solid #818cf8; padding:16px 20px; border-radius:0 8px 8px 0; margin:22px 0;">
                                <div style="color:#c7d2fe; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                                    💬 Note officielle du Shōgunat &amp; Maîtres Forgerons :
                                </div>
                                <div style="color:#f8fafc; font-size:14px; line-height:1.6; font-style:italic;">
                                    « {$safeResponse} »
                                </div>
                            </div>
HTML;
        }

        $htmlBody .= <<<HTML
                            <p style="margin:22px 0 25px 0; color:#cbd5e1; font-size:14px;">
                                Vous pouvez retrouver l'ensemble de vos démarches et consulter les détails de ce ticket directement dans l'interface de votre domaine.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:25px 0 10px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$supportUrl}" target="_blank" style="background:linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:8px; font-weight:700; font-size:14px; display:inline-block; border:1px solid #14b8a6; box-shadow:0 4px 12px rgba(13,148,136,0.35);">
                                            📮 Accéder à l'Espace Support &amp; Assistance
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#0f172a; padding:22px; border-top:1px solid #334155; color:#64748b; font-size:12px; line-height:1.5;">
                            <div style="color:#94a3b8; font-weight:600; margin-bottom:4px;">
                                Conseil des Bâtisseurs &amp; Intendants &bull; OpenShogun
                            </div>
                            <div>
                                Ce message vous est adressé suite au suivi du ticket #{$ticketId} associé à votre seigneurie {$safeUsername}.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        // Corps en texte brut pour les clients e-mail sans HTML
        $textBody = "========================================================\n"
                  . "🏯 OPENSHOGUN — SUIVI & TRAITEMENT DU TICKET #{$ticketId}\n"
                  . "========================================================\n\n"
                  . "Salutations, honorable Daimyō {$username},\n\n"
                  . "Votre signalement concernant « {$title} » a franchi une nouvelle étape dans son traitement.\n\n"
                  . "DÉTAILS DU TICKET :\n"
                  . "- Référence       : #{$ticketId}\n"
                  . "- Type & Domaine  : {$typeLabel} ({$categoryName})\n"
                  . "- Gravité         : {$severityLabel}\n"
                  . "- Ancien Statut   : {$oldStatusLabel}\n"
                  . "- Nouveau Statut  : {$newStatusLabel}\n";

        if (!empty($adminResponseTrim)) {
            $textBody .= "\nNOTE OFFICIELLE DE L'ÉQUIPE DU SHŌGUNAT :\n"
                       . "« {$adminResponseTrim} »\n";
        }

        $textBody .= "\nVous pouvez consulter le suivi en jeu dans l'onglet Assistance & Suggestions (📮) :\n"
                   . "{$supportUrl}\n\n"
                   . "Que la paix et la prospérité accompagnent votre fief.\n"
                   . "-- Le Conseil des Développeurs & Intendants d'OpenShogun\n";

        try {
            return MailService::getInstance()->sendEmail($toEmail, $subject, $htmlBody, $textBody);
        } catch (Throwable $e) {
            error_log("SupportEngine: Échec de l'envoi de l'e-mail pour le ticket #{$ticketId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
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

