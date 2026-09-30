<?php
/**
 * Migration : Mise en place du module Dev Team & Métiers du Jeu Vidéo
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';

echo "=== MIGRATION : MODULE DEV TEAM & MÉTIERS DU JEU VIDÉO ===\n";

try {
    $db = Database::getConnection();
    $engine = new DevTeamEngine($db);
    echo "✓ Tables dev_roles, dev_permissions, dev_role_permissions, user_dev_roles, user_dev_overrides et dev_forge_xp créées avec succès !\n";
    echo "✓ Données de référence (8 métiers et permissions RBAC) initialisées !\n";

    $roles = $db->query("SELECT id, title, icon FROM dev_roles")->fetchAll(PDO::FETCH_ASSOC);
    echo "Rôles enregistrés (" . count($roles) . ") :\n";
    foreach ($roles as $r) {
        echo "  - {$r['icon']} {$r['title']} ({$r['id']})\n";
    }

} catch (Exception $e) {
    echo "✗ Erreur lors de la migration Dev Team : " . $e->getMessage() . "\n";
    exit(1);
}
