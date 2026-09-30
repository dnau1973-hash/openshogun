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
                    `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_mc_sender` (`sender_id`),
                    KEY `idx_mc_sent_at` (`sent_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
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
    public function sendCampaign(int $senderId, string $senderName, string $subject, string $targetGroup, string $contentHtml): array {
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

            $res = $mailService->send($toEmail, $subject, $fullHtml, $plainText);
            if (!empty($res['success'])) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $status = ($successCount > 0) ? 'sent' : 'failed';

        // 3. Enregistrement dans le journal des campagnes
        try {
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
                SELECT id, sender_id, sender_name, subject, target_group, recipient_count, status, sent_at
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
     * Construit le gabarit HTML féodal pour l'e-mail de newsletter
     */
    public function buildNewsletterTemplate(string $subject, string $contentHtml, string $username, string $faction, string $senderName): string {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $gameUrl = $scheme . '://' . $host;

        $factionNames = [
            'terran'   => 'Clan Tokugawa',
            'vorash'   => 'Clan Oda',
            'aethelis' => 'Clan Takeda'
        ];
        $factionLabel = $factionNames[$faction] ?? 'Seigneur de Guerre';

        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0b0f19; padding: 30px 10px;">
                <tr>
                    <td align="center">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; width: 100%; background: #131b2e; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);">
                            
                            <!-- Header Féodal -->
                            <tr>
                                <td style="padding: 28px 30px; background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); border-bottom: 2px solid #d97706; text-align: center;">
                                    <div style="font-size: 36px; margin-bottom: 8px;">⛩️</div>
                                    <h1 style="margin: 0; font-size: 22px; color: #fbbf24; text-transform: uppercase; letter-spacing: 2px; font-weight: 800;">
                                        OpenShogun &bull; Chroniques Féodales
                                    </h1>
                                    <div style="color: #94a3b8; font-size: 12px; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;">
                                        Dépêche Officielle &bull; ' . htmlspecialchars($senderName) . '
                                    </div>
                                </td>
                            </tr>

                            <!-- Corps du Message -->
                            <tr>
                                <td style="padding: 30px 35px; line-height: 1.6; color: #cbd5e1; font-size: 15px;">
                                    <div style="margin-bottom: 20px;">
                                        <span style="font-size: 12px; font-weight: 700; color: #d97706; background: rgba(217, 119, 6, 0.15); border: 1px solid rgba(217, 119, 6, 0.3); padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                                            ' . htmlspecialchars($factionLabel) . '
                                        </span>
                                    </div>

                                    <h2 style="margin: 0 0 16px 0; color: #f8fafc; font-size: 18px; font-weight: 700;">
                                        Salutations, Honorable Daimyō ' . htmlspecialchars($username) . ',
                                    </h2>

                                    <div style="color: #e2e8f0; font-size: 15px; line-height: 1.65; margin-bottom: 25px;">
                                        ' . nl2br(htmlspecialchars($contentHtml)) . '
                                    </div>

                                    <div style="text-align: center; margin: 30px 0 15px;">
                                        <a href="' . $gameUrl . '" style="display: inline-block; background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-size: 14px; font-weight: 700; border-radius: 6px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4); text-transform: uppercase; letter-spacing: 1px;">
                                            Rejoindre le Champ de Bataille &rarr;
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Pied de Page RGPD & Légal -->
                            <tr>
                                <td style="padding: 20px 30px; background-color: #0b0f19; border-top: 1px solid #1e293b; text-align: center; color: #64748b; font-size: 12px; line-height: 1.5;">
                                    <p style="margin: 0 0 6px 0;">
                                        Cette missive a été expédiée par l\'équipe d\'OpenShogun &bull; Service Communauté &amp; Relations Joueurs.
                                    </p>
                                    <p style="margin: 0; font-size: 11px; color: #475569;">
                                        Vous recevez ce courriel car vous avez accepté de recevoir nos chroniques féodales (RGPD).<br>
                                        Vous pouvez ajuster vos préférences ou vous désinscrire à tout moment dans votre profil joueur.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
