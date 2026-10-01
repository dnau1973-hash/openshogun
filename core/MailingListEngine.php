<?php
/**
 * MailingListEngine — Moteur de Gestion de la Mailing List & Campagnes d'E-mails
 * Module dédié au Community Manager et aux Administrateurs d'OpenShogun
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/DevTeamEngine.php';

class MailingListEngine {
    private PDO $db;
    private static bool $schemaChecked = false;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Garantit l'existence de la table mailing_campaigns et de la colonne newsletter_optin
     */
    public function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            // 1. Colonne newsletter_optin dans users
            $cols = $this->db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('newsletter_optin', $cols, true)) {
                $this->db->exec("ALTER TABLE users ADD COLUMN newsletter_optin TINYINT(1) NOT NULL DEFAULT 0 AFTER protection_until");
                try {
                    $this->db->exec("ALTER TABLE users ADD INDEX idx_users_newsletter (newsletter_optin)");
                } catch (Exception $e) {}
            }

            // 2. Table mailing_campaigns
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `mailing_campaigns` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `sender_id` int(10) unsigned NOT NULL,
                    `sender_name` varchar(100) NOT NULL,
                    `subject` varchar(255) NOT NULL,
                    `target_group` varchar(50) NOT NULL,
                    `recipient_count` int(10) unsigned NOT NULL DEFAULT 0,
                    `body_html` mediumtext NOT NULL,
                    `status` enum('draft','sent','failed') NOT NULL DEFAULT 'sent',
                    `scheduled_at` datetime DEFAULT NULL,
                    `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_mc_sender` (`sender_id`),
                    KEY `idx_mc_sent_at` (`sent_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Migration scheduled_at si la table existait déjà
            $mcCols = $this->db->query("SHOW COLUMNS FROM mailing_campaigns")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('scheduled_at', $mcCols, true)) {
                try {
                    $this->db->exec("ALTER TABLE mailing_campaigns ADD COLUMN scheduled_at DATETIME NULL AFTER status");
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {
            // Ignorer silencieusement si la base est en cours d'initialisation
        }
    }

    /**
     * Récupère la liste des abonnés avec filtres multi-critères
     */
    public function getSubscribers(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where = ["u.is_bot = 0"];
        $params = [];

        // Recherche par mot-clé (nom ou email)
        if (!empty($filters['search'])) {
            $search = '%' . trim((string)$filters['search']) . '%';
            $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }

        // Filtre Statut Newsletter (Opt-in)
        if (isset($filters['optin']) && $filters['optin'] !== '' && $filters['optin'] !== 'all') {
            $where[] = "u.newsletter_optin = ?";
            $params[] = (int)$filters['optin'];
        }

        // Filtre Statut de Compte (Actif / En attente)
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'active') {
                $where[] = "(u.is_active = 1 OR u.is_active IS NULL)";
            } elseif ($filters['status'] === 'pending') {
                $where[] = "u.is_active = 0";
            }
        }

        // Filtre Faction
        if (!empty($filters['faction']) && $filters['faction'] !== 'all') {
            $where[] = "u.faction = ?";
            $params[] = $filters['faction'];
        }

        // Filtre Rôle / Profil
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            if ($filters['role'] === 'admin') {
                $where[] = "u.is_admin = 1";
            } elseif ($filters['role'] === 'moderator') {
                $where[] = "u.is_moderator = 1";
            } elseif ($filters['role'] === 'dev_team') {
                $where[] = "u.id IN (SELECT user_id FROM user_dev_roles)";
            } elseif ($filters['role'] === 'player') {
                $where[] = "u.is_admin = 0 AND u.is_moderator = 0 AND u.id NOT IN (SELECT user_id FROM user_dev_roles)";
            }
        }

        $whereSql = implode(" AND ", $where);

        // Compte total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Récupération paginée avec rôles Dev Team concaténés
        $sql = "
            SELECT u.id, u.username, u.email, u.faction, u.created_at, u.last_active,
                   COALESCE(u.is_active, 1) as is_active,
                   COALESCE(u.newsletter_optin, 0) as newsletter_optin,
                   u.is_admin, u.is_moderator,
                   (
                       SELECT GROUP_CONCAT(CONCAT(dr.id, ':', dr.title, ':', dr.icon) SEPARATOR '||')
                       FROM user_dev_roles udr
                       JOIN dev_roles dr ON udr.role_id = dr.id
                       WHERE udr.user_id = u.id
                   ) as dev_roles_raw
            FROM users u
            WHERE {$whereSql}
            ORDER BY u.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subscribers = [];
        foreach ($rows as $r) {
            $devRoles = [];
            if (!empty($r['dev_roles_raw'])) {
                $parts = explode('||', $r['dev_roles_raw']);
                foreach ($parts as $p) {
                    $chunk = explode(':', $p, 3);
                    if (count($chunk) === 3) {
                        $devRoles[] = [
                            'id'    => $chunk[0],
                            'title' => $chunk[1],
                            'icon'  => $chunk[2]
                        ];
                    }
                }
            }

            $r['dev_roles'] = $devRoles;
            $r['is_active'] = (int)$r['is_active'] === 1;
            $r['newsletter_optin'] = (int)$r['newsletter_optin'] === 1;
            $r['is_admin'] = (int)$r['is_admin'] === 1;
            $r['is_moderator'] = (int)$r['is_moderator'] === 1;
            $subscribers[] = $r;
        }

        return [
            'subscribers' => $subscribers,
            'total'       => $total,
            'limit'       => $limit,
            'offset'      => $offset
        ];
    }

    /**
     * Statistiques globales de la mailing list
     */
    public function getStatistics(): array {
        try {
            $totalUsers = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0")->fetchColumn();
            $newsletterSubscribers = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0 AND newsletter_optin = 1")->fetchColumn();
            $activeVerified = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0 AND (is_active = 1 OR is_active IS NULL)")->fetchColumn();
            $pendingVerification = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0 AND is_active = 0")->fetchColumn();
            $totalCampaigns = (int)$this->db->query("SELECT COUNT(*) FROM mailing_campaigns")->fetchColumn();

            $optinRate = ($totalUsers > 0) ? round(($newsletterSubscribers / $totalUsers) * 100, 1) : 0.0;

            return [
                'total_users'            => $totalUsers,
                'newsletter_subscribers' => $newsletterSubscribers,
                'optin_rate_percent'     => $optinRate,
                'active_verified'        => $activeVerified,
                'pending_verification'   => $pendingVerification,
                'total_campaigns'        => $totalCampaigns
            ];
        } catch (Exception $e) {
            return [
                'total_users'            => 0,
                'newsletter_subscribers' => 0,
                'optin_rate_percent'     => 0.0,
                'active_verified'        => 0,
                'pending_verification'   => 0,
                'total_campaigns'        => 0
            ];
        }
    }

    /**
     * Bascule ou force le statut opt-in d'un utilisateur
     */
    public function toggleOptin(int $userId, ?bool $forceState = null): bool {
        if ($userId <= 0) return false;

        if ($forceState !== null) {
            $newVal = $forceState ? 1 : 0;
            $stmt = $this->db->prepare("UPDATE users SET newsletter_optin = ? WHERE id = ?");
            return $stmt->execute([$newVal, $userId]);
        }

        $stmt = $this->db->prepare("UPDATE users SET newsletter_optin = IF(newsletter_optin = 1, 0, 1) WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Envoie une campagne d'e-mail groupé
     */
    /**
     * Enregistre ou met à jour un brouillon de missive impériale
     */
    public function saveDraft(int $senderId, string $senderName, string $subject, string $targetGroup, string $contentHtml, ?string $scheduledAt = null, ?int $draftId = null): int {
        $subject = trim($subject);
        if ($subject === '') {
            $subject = 'Brouillon de missive (' . date('d/m/Y H:i') . ')';
        }
        $scheduledDate = (!empty($scheduledAt)) ? date('Y-m-d H:i:s', strtotime($scheduledAt)) : null;

        if ($draftId !== null && $draftId > 0) {
            $stmt = $this->db->prepare("
                UPDATE mailing_campaigns
                SET sender_id = ?, sender_name = ?, subject = ?, target_group = ?, body_html = ?, status = 'draft', scheduled_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$senderId, $senderName, $subject, $targetGroup, $contentHtml, $scheduledDate, $draftId]);
            return $draftId;
        }

        $stmt = $this->db->prepare("
            INSERT INTO mailing_campaigns (sender_id, sender_name, subject, target_group, recipient_count, body_html, status, scheduled_at, sent_at)
            VALUES (?, ?, ?, ?, 0, ?, 'draft', ?, NOW())
        ");
        $stmt->execute([$senderId, $senderName, $subject, $targetGroup, $contentHtml, $scheduledDate]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Récupère une campagne ou un brouillon par son ID
     */
    public function getCampaign(int $campaignId): ?array {
        if ($campaignId <= 0) return null;
        $stmt = $this->db->prepare("SELECT * FROM mailing_campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Envoie un e-mail de test personnel à l'utilisateur connecté
     */
    public function sendTestEmail(int $senderId, string $subject, string $contentHtml): array {
        $stmt = $this->db->prepare("SELECT id, username, email, faction FROM users WHERE id = ?");
        $stmt->execute([$senderId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['email'])) {
            return ['success' => false, 'error' => "Adresse e-mail introuvable pour votre compte Daimyō."];
        }

        $mailService = MailService::getInstance();
        $testSubject = "[TEST] " . (trim($subject) !== '' ? trim($subject) : 'Missive Impériale');
        $fullHtml = $this->buildNewsletterTemplate($testSubject, $contentHtml, $user['username'], $user['faction'] ?? 'terran', 'Chancellerie Impériale');
        $plainText = strip_tags(str_replace(['<br>', '<p>', '</p>'], ["\n", "\n", "\n\n"], $contentHtml));

        $res = $mailService->sendEmail($user['email'], $testSubject, $fullHtml, $plainText);
        if (!empty($res['success'])) {
            return [
                'success' => true,
                'email'   => $user['email'],
                'message' => "Missive de test expédiée avec succès à votre adresse ({$user['email']}) !"
            ];
        }

        return [
            'success' => false,
            'error'   => $res['error'] ?? "Échec lors de l'envoi de l'e-mail de test."
        ];
    }

    /**
     * Envoie une campagne d'e-mail groupé
     */
    public function sendCampaign(int $senderId, string $senderName, string $subject, string $targetGroup, string $contentHtml, ?int $draftId = null): array {
        $subject = trim($subject);
        $contentHtml = trim($contentHtml);

        if (empty($subject)) {
            return ['success' => false, 'error' => "Le sujet de la missive impériale ne peut pas être vide."];
        }
        if (empty($contentHtml)) {
            return ['success' => false, 'error' => "Le corps de la missive ne peut pas être vide."];
        }

        // 1. Détermination de la liste des destinataires
        $recipients = $this->resolveRecipients($targetGroup, $senderId);
        if (empty($recipients)) {
            return ['success' => false, 'error' => "Aucun destinataire ne correspond au ciblage choisi ('{$targetGroup}')."];
        }

        $mailService = MailService::getInstance();
        $successCount = 0;
        $failCount = 0;

        // 2. Expédition aux destinataires
        foreach ($recipients as $recipient) {
            $toEmail = $recipient['email'];
            $toUsername = $recipient['username'];
            $toFaction = $recipient['faction'] ?? 'terran';

            $fullHtml = $this->buildNewsletterTemplate($subject, $contentHtml, $toUsername, $toFaction, $senderName);
            $plainText = strip_tags(str_replace(['<br>', '<p>', '</p>'], ["\n", "\n", "\n\n"], $contentHtml));

            $res = $mailService->sendEmail($toEmail, $subject, $fullHtml, $plainText);
            if (!empty($res['success'])) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $status = ($successCount > 0) ? 'sent' : 'failed';

        // 3. Enregistrement ou mise à jour dans le journal des campagnes
        try {
            if ($draftId !== null && $draftId > 0) {
                $stmt = $this->db->prepare("
                    UPDATE mailing_campaigns 
                    SET sender_id = ?, sender_name = ?, subject = ?, target_group = ?, recipient_count = ?, body_html = ?, status = ?, sent_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $senderId,
                    $senderName,
                    $subject,
                    $targetGroup,
                    $successCount,
                    $contentHtml,
                    $status,
                    $draftId
                ]);
                $campaignId = $draftId;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO mailing_campaigns (sender_id, sender_name, subject, target_group, recipient_count, body_html, status, sent_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $senderId,
                    $senderName,
                    $subject,
                    $targetGroup,
                    $successCount,
                    $contentHtml,
                    $status
                ]);
                $campaignId = (int)$this->db->lastInsertId();
            }
        } catch (Exception $e) {
            $campaignId = 0;
        }

        // 4. Récompense gamifiée : Attribution d'XP Forge au Community Manager
        try {
            $devEngine = new DevTeamEngine($this->db);
            $xpGain = min(100, 20 + ($successCount * 2));
            $devEngine->addForgeXp($senderId, $xpGain, 'newsletter_campaign', "Expédition d'une missive communautaire : '{$subject}' ({$successCount} Daimyōs touchés)");
        } catch (Exception $e) {}

        return [
            'success'         => ($successCount > 0),
            'campaign_id'     => $campaignId,
            'recipient_count' => $successCount,
            'fail_count'      => $failCount,
            'message'         => "Missive expédiée avec succès à {$successCount} seigneur(s) féodal(aux) !" . ($failCount > 0 ? " ({$failCount} échecs)" : "")
        ];
    }

    /**
     * Résout la liste des destinataires selon le groupe cible
     */
    public function resolveRecipients(string $targetGroup, int $senderId = 0): array {
        switch ($targetGroup) {
            case 'test_self':
                $stmt = $this->db->prepare("SELECT id, username, email, faction FROM users WHERE id = ?");
                $stmt->execute([$senderId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'all_optin':
                return $this->db->query("
                    SELECT id, username, email, faction 
                    FROM users 
                    WHERE is_bot = 0 AND newsletter_optin = 1 AND (is_active = 1 OR is_active IS NULL)
                    ORDER BY id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            case 'all_active':
                return $this->db->query("
                    SELECT id, username, email, faction 
                    FROM users 
                    WHERE is_bot = 0 AND (is_active = 1 OR is_active IS NULL)
                    ORDER BY id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            case 'dev_team':
                return $this->db->query("
                    SELECT DISTINCT u.id, u.username, u.email, u.faction 
                    FROM users u
                    JOIN user_dev_roles udr ON u.id = udr.user_id
                    WHERE u.is_bot = 0
                    ORDER BY u.id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            case 'faction_terran':
                return $this->db->query("
                    SELECT id, username, email, faction 
                    FROM users 
                    WHERE is_bot = 0 AND faction = 'terran' AND newsletter_optin = 1
                    ORDER BY id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            case 'faction_vorash':
                return $this->db->query("
                    SELECT id, username, email, faction 
                    FROM users 
                    WHERE is_bot = 0 AND faction = 'vorash' AND newsletter_optin = 1
                    ORDER BY id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            case 'faction_aethelis':
                return $this->db->query("
                    SELECT id, username, email, faction 
                    FROM users 
                    WHERE is_bot = 0 AND faction = 'aethelis' AND newsletter_optin = 1
                    ORDER BY id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

            default:
                return [];
        }
    }

    /**
     * Récupère l'historique des campagnes envoyées
     */
    public function getCampaignHistory(int $limit = 20): array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, sender_id, sender_name, subject, target_group, recipient_count, status, scheduled_at, sent_at
                FROM mailing_campaigns
                ORDER BY id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Génère un export CSV de la liste des abonnés (compatible UTF-8 avec BOM)
     */
    public function exportCsv(array $filters = []): string {
        $data = $this->getSubscribers($filters, 5000, 0);
        $subscribers = $data['subscribers'];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM pour Excel
        $output .= "ID;Nom de Daimyo;Email;Faction;Statut Compte;Abonne Newsletter;Metiers Dev;Date Inscription\n";

        foreach ($subscribers as $s) {
            $rolesList = [];
            foreach ($s['dev_roles'] as $dr) {
                $rolesList[] = $dr['title'];
            }
            $rolesStr = empty($rolesList) ? 'Joueur' : implode(', ', $rolesList);

            $line = [
                $s['id'],
                '"' . str_replace('"', '""', $s['username']) . '"',
                '"' . str_replace('"', '""', $s['email']) . '"',
                $s['faction'],
                $s['is_active'] ? 'Actif' : 'En attente',
                $s['newsletter_optin'] ? 'Oui' : 'Non',
                '"' . str_replace('"', '""', $rolesStr) . '"',
                $s['created_at']
            ];

            $output .= implode(';', $line) . "\n";
        }

        return $output;
    }

    /**
     * Nettoie et sécurise le code HTML issu de l'éditeur WYSIWYG
     */
    public function sanitizeEmailHtml(string $html): string {
        $allowedTags = '<p><br><br/><strong><b><em><i><u><s><strike><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><a><hr><span><div>';
        $cleaned = strip_tags($html, $allowedTags);

        // Supprime les attributs d'événements JavaScript (onclick, onmouseover...)
        $cleaned = preg_replace('/\s*on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned);
        // Supprime les URI javascript:
        $cleaned = preg_replace('/href\s*=\s*["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $cleaned);

        return $cleaned;
    }

    /**
     * Construit le gabarit HTML féodal pour l'e-mail de newsletter (Fond clair parchemin & accents Carmin/Or)
     */
    public function buildNewsletterTemplate(string $subject, string $contentHtml, string $username = 'Daimyō', string $faction = 'terran', string $senderName = 'Le Shōgunat'): string {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $gameUrl = $scheme . '://' . $host;
        $logoUrl = $gameUrl . '/assets/logo_transparent.png';
        $sealUrl = $gameUrl . '/assets/items/sceau_chrysantheme.jpeg';
        $unsubscribeUrl = $gameUrl . '/?page=poster';

        $cleanContent = $this->sanitizeEmailHtml($contentHtml);
        if (trim($cleanContent) === '') {
            $cleanContent = '<p>' . nl2br(htmlspecialchars($contentHtml)) . '</p>';
        }

        // Factions & Bannières
        $factions = [
            'terran' => [
                'clan'  => 'Clan Tokugawa',
                'color' => '#1e3a8a',
                'bg'    => '#eff6ff',
                'border'=> '#93c5fd',
                'moto'  => 'Sagesse, Patience & Fortifications'
            ],
            'vorash' => [
                'clan'  => 'Clan Oda',
                'color' => '#991b1b',
                'bg'    => '#fef2f2',
                'border'=> '#fca5a5',
                'moto'  => 'Innovation, Artillerie & Conquête'
            ],
            'aethelis' => [
                'clan'  => 'Clan Takeda',
                'color' => '#b91c1c',
                'bg'    => '#fff1f2',
                'border'=> '#fecdd3',
                'moto'  => 'Fūrinkazan & Cavalerie Rouge'
            ],
        ];

        $clanData = $factions[$faction] ?? [
            'clan'  => 'Chancellerie Impériale',
            'color' => '#92400e',
            'bg'    => '#fffbeb',
            'border'=> '#fde68a',
            'moto'  => 'Allégeance aux Décrets du Shōgunat'
        ];

        return '<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>' . htmlspecialchars($subject) . '</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f7f3ec; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .missive-content h1, .missive-content h2, .missive-content h3 { color: #881337; font-family: Georgia, serif; margin: 18px 0 10px 0; }
        .missive-content p { margin: 0 0 14px 0; line-height: 1.68; color: #2d3748; }
        .missive-content a { color: #b91c1c; text-decoration: underline; font-weight: 600; }
        .missive-content blockquote { border-left: 3px solid #b91c1c; margin: 16px 0; padding: 10px 18px; background-color: #faf5ee; color: #4a5568; font-style: italic; }
        .missive-content ul, .missive-content ol { margin: 12px 0 16px 0; padding-left: 24px; color: #2d3748; }
        .missive-content li { margin-bottom: 6px; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-cell { padding: 24px 18px !important; }
            .header-cell { padding: 22px 15px 18px 15px !important; }
            .cta-button { width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; background-color: #f7f3ec;">

    <!-- Wrapper Table Fond Clair / Parchemin -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f7f3ec" style="background-color: #f7f3ec; margin: 0; padding: 30px 10px 40px 10px;">
        <tr>
            <td align="center" valign="top">

                <!-- Conteneur Carte Email Principale (max 640px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" class="email-container" style="max-width: 640px; width: 100%; background-color: #ffffff; border: 1px solid #e7ded0; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 22px rgba(120, 90, 60, 0.08);">
                    
                    <!-- Liseré supérieur Doré & Vermillon -->
                    <tr>
                        <td height="5" style="height: 5px; font-size: 1px; line-height: 1px; background: linear-gradient(90deg, #991b1b 0%, #d4af37 35%, #b45309 65%, #991b1b 100%);"></td>
                    </tr>

                    <!-- En-tête : Logo Officiel Centré & Identité Féodale -->
                    <tr>
                        <td align="center" class="header-cell" style="padding: 30px 25px 22px 25px; background-color: #ffffff; border-bottom: 1px solid #f0e7db; text-align: center;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="' . $gameUrl . '" target="_blank" style="text-decoration: none; display: inline-block;">
                                            <img src="' . $logoUrl . '" alt="OpenShogun — La Voie du Shogun" width="220" style="width: 220px; max-width: 85%; height: auto; display: block; margin: 0 auto 14px auto;" border="0">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <div style="font-family: Georgia, serif; font-size: 13px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;">
                                            Chroniques du Shōgunat &bull; Missive Impériale
                                        </div>
                                        <div style="display: inline-block; background-color: ' . $clanData['bg'] . '; border: 1px solid ' . $clanData['border'] . '; color: ' . $clanData['color'] . '; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.8px;">
                                            ' . htmlspecialchars($clanData['clan']) . ' &bull; ' . htmlspecialchars($clanData['moto']) . '
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Corps de la Missive -->
                    <tr>
                        <td class="content-cell" style="padding: 35px 40px 30px 40px; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.7; color: #2d3748;">
                            
                            <!-- Salutation Personnalisée -->
                            <h2 style="margin: 0 0 14px 0; color: #7f1d1d; font-family: Georgia, serif; font-size: 20px; font-weight: bold; line-height: 1.35;">
                                Salutations, Honorable Daimyō ' . htmlspecialchars($username) . ',
                            </h2>

                            <!-- Séparateur décoratif doré avec losange -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 12px 0 24px 0;">
                                <tr>
                                    <td style="border-bottom: 1px solid #ebdcc6; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                    <td style="width: 32px; text-align: center; color: #d4af37; font-size: 14px; line-height: 1; padding: 0 4px;">✦</td>
                                    <td style="border-bottom: 1px solid #ebdcc6; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            <!-- Contenu Rédigé WYSIWYG -->
                            <div class="missive-content" style="color: #2d3748; font-size: 15px; line-height: 1.7; margin-bottom: 30px;">
                                ' . $cleanContent . '
                            </div>

                            <!-- Bouton d\'Appel à l\'Action Impérial -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 35px auto 20px auto;">
                                <tr>
                                    <td align="center" style="border-radius: 6px; background-color: #991b1b;">
                                        <a href="' . $gameUrl . '" target="_blank" class="cta-button" style="display: inline-block; background-color: #991b1b; background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); border: 1px solid #d4af37; color: #ffffff !important; text-decoration: none; padding: 14px 34px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 14px; font-weight: bold; border-radius: 5px; text-transform: uppercase; letter-spacing: 1.2px; box-shadow: 0 4px 14px rgba(185, 28, 28, 0.28);">
                                            Rejoindre le Champ de Bataille &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size: 13px; color: #64748b; font-style: italic; text-align: right; margin-top: 25px; border-top: 1px dashed #e8dfd1; padding-top: 15px;">
                                Transmis sous le sceau de ' . htmlspecialchars($senderName) . '
                            </div>
                        </td>
                    </tr>

                    <!-- Pied de Page : Bandeau Décoratif Sceau Impérial, Mentions Légales & RGPD -->
                    <tr>
                        <td align="center" style="padding: 30px 25px 28px 25px; background-color: #faf7f2; border-top: 1px solid #eee5d8; text-align: center; color: #5a6a80; font-size: 12px; line-height: 1.65;">
                            
                            <!-- Bandeau décoratif Sceau du Chrysanthème -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 16px auto;">
                                <tr>
                                    <td style="border-bottom: 1px solid #e2d7c5; width: 65px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                    <td style="padding: 0 14px;" align="center">
                                        <img src="' . $sealUrl . '" alt="Sceau Impérial du Chrysanthème" width="54" height="54" style="width: 54px; height: 54px; border-radius: 50%; border: 2px solid #d4af37; display: block; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);" border="0">
                                    </td>
                                    <td style="border-bottom: 1px solid #e2d7c5; width: 65px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            <div style="font-weight: 700; color: #334155; font-size: 13px; margin-bottom: 6px;">
                                OpenShogun &bull; Chancellerie Impériale &amp; Relations Joueurs
                            </div>
                            <div style="color: #64748b; font-size: 11px; max-width: 500px; margin: 0 auto 12px auto;">
                                Vous recevez cette missive car vous êtes seigneur féodal sur OpenShogun et avez consenti à recevoir nos décrets officiels (RGPD). Vos données restent confidentielles et ne sont jamais cédées.
                            </div>
                            <div style="font-size: 11px; color: #94a3b8;">
                                <a href="' . $unsubscribeUrl . '" style="color: #991b1b; text-decoration: underline; font-weight: 600;">Se désinscrire de la liste de diffusion</a>
                                &bull;
                                <a href="' . $gameUrl . '/?page=poster" style="color: #64748b; text-decoration: underline;">Paramètres du Daimyō</a>
                                &bull;
                                <a href="' . $gameUrl . '/?page=docs" style="color: #64748b; text-decoration: underline;">Codex Féodal</a>
                            </div>
                        </td>
                    </tr>

                </table>
                <!-- Fin Conteneur Carte Email -->

            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
