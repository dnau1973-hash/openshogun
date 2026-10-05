<?php
/**
 * CLI Migration Runner — OpenShogun
 * Exécute et vérifie les migrations de base de données de manière idempotente
 * 
 * Usage :
 *   php scripts/migrate.php           # Applique toutes les migrations en attente
 *   php scripts/migrate.php --status  # Affiche le statut des migrations
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/MigrationEngine.php';

$isStatusOnly = in_array('--status', $argv ?? []);

echo "\n";
echo "========================================================\n";
echo "  🏯 OpenShogun — Gestionnaire Idempotent de Migrations\n";
echo "========================================================\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "❌ Erreur critique de connexion MySQL : " . $e->getMessage() . "\n\n";
    exit(1);
}

if ($isStatusOnly) {
    MigrationEngine::ensureMigrationTable($pdo);
    $applied = MigrationEngine::getAppliedMigrations($pdo);
    $available = MigrationEngine::getAvailableMigrations();

    echo "Statut des migrations disponibles :\n";
    echo str_repeat('-', 56) . "\n";
    foreach ($available as $version => $info) {
        $status = isset($applied[$version]) 
            ? "✅ Appliquée le " . $applied[$version]['applied_at'] . " (" . $applied[$version]['execution_time_ms'] . " ms)"
            : "⏳ En attente";
        printf(" • %-35s %s\n", $info['filename'], $status);
    }
    echo str_repeat('-', 56) . "\n\n";
    exit(0);
}

$logger = function(string $msg, string $type) {
    $time = date('H:i:s');
    switch ($type) {
        case 'running':
            echo "  [$time] ▶️  $msg\n";
            break;
        case 'success':
            echo "  [$time]    $msg\n";
            break;
        case 'info':
            echo "  [$time] ℹ️  $msg\n";
            break;
        case 'error':
            echo "  [$time] ❌ $msg\n";
            break;
    }
};

try {
    $result = MigrationEngine::run($pdo, $logger);
    echo "\n";
    if ($result['executed_count'] > 0) {
        echo "✅ " . $result['executed_count'] . " migration(s) appliquée(s) avec succès !\n";
    } else {
        echo "✅ Base de données déjà à jour. Aucune action requise.\n";
    }
    echo "========================================================\n\n";
    exit(0);
} catch (Throwable $e) {
    echo "\n❌ ÉCHEC CRITIQUE : " . $e->getMessage() . "\n\n";
    exit(1);
}
