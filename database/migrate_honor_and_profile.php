<?php
/**
 * Migration : Tableau d'Honneur, Médailles et Fiche Joueur (Style Travian)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : TABLEAU D'HONNEUR, MÉDAILLES & PROFIL ===\n";

$db = Database::getConnection();

// 1. Ajouter la colonne bio dans users si absente
$userColumns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('bio', $userColumns)) {
    echo "- Ajout de la colonne 'bio' dans 'users'...\n";
    $db->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER alliance_id");
} else {
    echo "- Colonne 'bio' déjà présente dans 'users'.\n";
}

// 2. Créer la table user_weekly_stats
echo "- Création de la table 'user_weekly_stats'...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `user_weekly_stats` (
        `user_id` INT UNSIGNED PRIMARY KEY,
        `attack_points` INT UNSIGNED NOT NULL DEFAULT 0,
        `defense_points` INT UNSIGNED NOT NULL DEFAULT 0,
        `raid_resources` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `start_week_points` INT UNSIGNED NOT NULL DEFAULT 0,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_uws_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 3. Créer la table user_medals
echo "- Création de la table 'user_medals'...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `user_medals` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `category` ENUM('progression', 'attack', 'defense', 'raid') NOT NULL,
        `rank` TINYINT UNSIGNED NOT NULL, -- 1=Or, 2=Argent, 3=Bronze, 4-10=Top 10
        `week_code` VARCHAR(20) NOT NULL, -- ex: '2026-S37'
        `description` VARCHAR(255) NULL,
        `awarded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_um_user` (`user_id`),
        CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 4. Initialiser user_weekly_stats pour tous les utilisateurs existants
$users = $db->query("SELECT id, points FROM users")->fetchAll();
$stmtInitStats = $db->prepare("
    INSERT INTO user_weekly_stats (user_id, attack_points, defense_points, raid_resources, start_week_points)
    VALUES (?, 0, 0, 0, ?)
    ON DUPLICATE KEY UPDATE start_week_points = IF(start_week_points = 0, VALUES(start_week_points), start_week_points)
");

foreach ($users as $u) {
    $stmtInitStats->execute([$u['id'], $u['points']]);
}
echo "- Statistiques hebdomadaires initialisées pour " . count($users) . " utilisateur(s).\n";

// 5. Attribuer une médaille d'honneur de bienvenue à l'Administrateur nezzar s'il existe
$nezzar = $db->query("SELECT id FROM users WHERE username = 'nezzar'")->fetch();
if ($nezzar) {
    $checkMedal = $db->prepare("SELECT id FROM user_medals WHERE user_id = ? AND category = 'progression' AND `rank` = 1");
    $checkMedal->execute([$nezzar['id']]);
    if (!$checkMedal->fetch()) {
        $weekCode = date('Y') . "-S" . date('W');
        $db->prepare("
            INSERT INTO user_medals (user_id, category, `rank`, week_code, description)
            VALUES (?, 'progression', 1, ?, 'Pionnier Suprême - Fondateur de l\'Empire Spatial OpenGalaxy')
        ")->execute([$nezzar['id'], $weekCode]);
        echo "- Médaille d'Or d'Honneur inaugurale attribuée à 'nezzar'.\n";
    }
}

echo "=== MIGRATION TERMINÉE AVEC SUCCÈS ===\n";

