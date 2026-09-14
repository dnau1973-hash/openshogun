<?php
/**
 * Migration pour l'interface d'administration et le système de bots
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : ADMINISTRATION & BOTS ===\n";

$db = Database::getConnection();

// 1. Ajouter la colonne is_admin et is_bot à users si inexistantes
$columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('is_admin', $columns)) {
    echo "- Ajout de la colonne 'is_admin' dans 'users'...\n";
    $db->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER alliance_id");
} else {
    echo "- Colonne 'is_admin' déjà présente dans 'users'.\n";
}

if (!in_array('is_bot', $columns)) {
    echo "- Ajout de la colonne 'is_bot' dans 'users'...\n";
    $db->exec("ALTER TABLE users ADD COLUMN is_bot TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin");
} else {
    echo "- Colonne 'is_bot' déjà présente dans 'users'.\n";
}

// 2. Créer la table game_settings
echo "- Création de la table 'game_settings' si nécessaire...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `game_settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT NOT NULL,
        `setting_type` ENUM('int', 'float', 'string', 'boolean') NOT NULL DEFAULT 'string',
        `description` VARCHAR(255) NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 3. Initialiser les variables par défaut
$defaultSettings = [
    'game_speed' => ['5', 'int', 'Multiplicateur de vitesse des constructions, chantiers, caserne et recherches'],
    'resource_speed' => ['5', 'int', 'Multiplicateur de production horaire des mines et extracteurs'],
    'fleet_speed' => ['5', 'int', 'Multiplicateur de vitesse de vol des convois spatiaux'],
    'bots_enabled' => ['1', 'boolean', 'Activation globale de l\'intelligence artificielle des bots'],
    'bot_colonize_enabled' => ['1', 'boolean', 'Autoriser les bots à coloniser de nouvelles planètes'],
    'bot_aggressiveness' => ['moderate', 'string', 'Comportement des bots (peaceful, moderate, aggressive)'],
    'bot_max_planets' => ['3', 'int', 'Nombre maximum de planètes par bot']
];

$stmtInsert = $db->prepare("
    INSERT INTO game_settings (setting_key, setting_value, setting_type, description)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE description = VALUES(description)
");

foreach ($defaultSettings as $key => [$val, $type, $desc]) {
    $stmtInsert->execute([$key, $val, $type, $desc]);
}
echo "- Variables de jeu initialisées dans 'game_settings'.\n";

// 4. Promouvoir le premier utilisateur ou un compte existant en admin si aucun admin
$adminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
if ($adminCount === 0) {
    // Vérifier si un utilisateur existe
    $firstUser = $db->query("SELECT id, username FROM users WHERE is_bot = 0 ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstUser) {
        $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$firstUser['id']]);
        echo "- Promotion du compte '{$firstUser['username']}' (ID: {$firstUser['id']}) en Administrateur Suprême.\n";
    }
} else {
    echo "- Administrateur(s) existant(s) : {$adminCount}.\n";
}

// 5. Marquer VorashOverlord comme bot s'il existe
$db->exec("UPDATE users SET is_bot = 1 WHERE username = 'VorashOverlord'");

echo "=== MIGRATION TERMINÉE AVEC SUCCÈS ===\n";

