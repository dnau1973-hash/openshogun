<?php
/**
 * Script de migration PHP pour la table activity_logs
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION : TÉLÉMÉTRIE & LOGS D'ACTIVITÉ (ACTIVITY_LOGS) ===\n";

try {
    $db = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/migrate_activity_logs.sql');
    $db->exec($sql);
    echo "✓ Table `activity_logs` créée ou déjà existante avec succès !\n";
} catch (Exception $e) {
    echo "✗ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
