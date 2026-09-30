<?php
/**
 * Migration : Métier Community Manager, Colonne newsletter_optin et Table mailing_campaigns
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';

echo "=== MIGRATION : COMMUNITY MANAGER & MAILING LIST ===\n";

try {
    $db = Database::getConnection();

    // 1. Colonne newsletter_optin dans users
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('newsletter_optin', $cols, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN newsletter_optin TINYINT(1) NOT NULL DEFAULT 0 AFTER protection_until");
        try {
            $db->exec("ALTER TABLE users ADD INDEX idx_users_newsletter (newsletter_optin)");
        } catch (Exception $e) {}
        echo "✓ Colonne `users.newsletter_optin` et index ajoutés avec succès.\n";
    } else {
        echo "ℹ Colonne `users.newsletter_optin` déjà présente.\n";
    }

    // 2. Table mailing_campaigns
    $db->exec("
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
    echo "✓ Table `mailing_campaigns` vérifiée / créée avec succès.\n";

    // 3. Synchronisation DevTeamEngine (rôle Community Manager & permissions)
    $engine = new DevTeamEngine($db);
    echo "✓ Métier Community Manager et permissions associées synchronisés avec succès.\n";

    $roles = $db->query("SELECT id, title, icon FROM dev_roles")->fetchAll(PDO::FETCH_ASSOC);
    echo "Rôles enregistrés (" . count($roles) . ") :\n";
    foreach ($roles as $r) {
        echo "  - {$r['icon']} {$r['title']} ({$r['id']})\n";
    }

} catch (Exception $e) {
    echo "✗ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
