<?php
/**
 * Migration : Mise en place de l'Immunité Débutant de 7 jours (Beginner Protection)
 * OpenShogun - Chroniques Féodales du Sengoku
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : IMMUNITÉ FÉODALE DES NOUVEAUX JOUEURS (7 JOURS) ===\n\n";

try {
    $db = Database::getConnection();

    // 1. Ajouter la colonne protection_until dans users si absente
    $userColumns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('protection_until', $userColumns)) {
        echo "- Ajout de la colonne 'protection_until' dans la table 'users'...\n";
        $db->exec("ALTER TABLE users ADD COLUMN protection_until DATETIME NULL DEFAULT NULL AFTER last_active");
        try {
            $db->exec("ALTER TABLE users ADD INDEX idx_protection (protection_until)");
        } catch (Exception $e) {
            // Index déjà existant ou ignoré
        }
        echo "  -> Colonne 'protection_until' ajoutée avec succès.\n";
    } else {
        echo "- La colonne 'protection_until' est déjà présente dans 'users'.\n";
    }

    // 2. Rétro-compatibilité : Accorder la protection aux joueurs humains inscrits il y a moins de 7 jours
    echo "- Vérification des joueurs récents pour activation de la protection résiduelle...\n";
    $stmtBackfill = $db->exec("
        UPDATE users 
        SET protection_until = DATE_ADD(created_at, INTERVAL 7 DAY) 
        WHERE is_bot = 0 
          AND (protection_until IS NULL) 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    echo "  -> Joueurs rétro-protégés : {$stmtBackfill}\n";

    // 3. Ajouter le paramètre beginner_protection_days dans game_settings
    echo "- Configuration de la variable de jeu 'beginner_protection_days'...\n";
    $stmtSetting = $db->prepare("
        INSERT INTO game_settings (setting_key, setting_value, setting_type, description, updated_at)
        VALUES ('beginner_protection_days', '7', 'int', 'Durée de l\'immunité féodale des nouveaux joueurs en jours (0 pour désactiver)', NOW())
        ON DUPLICATE KEY UPDATE description = VALUES(description)
    ");
    $stmtSetting->execute();
    echo "  -> Variable 'beginner_protection_days' configurée à 7 jours par défaut.\n\n";

    echo "=== MIGRATION TERMINÉE AVEC SUCCÈS ===\n";
} catch (Exception $e) {
    echo "ERREUR MIGRATION : " . $e->getMessage() . "\n";
    exit(1);
}

