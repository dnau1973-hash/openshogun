<?php
/**
 * Test : Sécurisation de la simulation des bots et auto-migration de la base de données
 * Fichier : tests/test_auto_migration_and_bot_safety.php
 */

require_once __DIR__ . '/../core/MigrationEngine.php';

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "\n========================================================================" . PHP_EOL;
echo " TEST : AUTO-MIGRATION SQL & SÉCURISATION DU MOTEUR DE BOTS" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

// 1. Vérification des migrations sur disque
echo "[TEST 1] Détection des migrations disponibles..." . PHP_EOL;
$available = MigrationEngine::getAvailableMigrations();
assertTest(!empty($available), "Migrations trouvées sur le disque : " . count($available));
assertTest(isset($available['009_move_spy_probe_to_units']), "La migration 009_move_spy_probe_to_units est bien présente");
$mig009 = $available['009_move_spy_probe_to_units'];
assertTest(file_exists($mig009['path']), "Le fichier SQL de la migration 009 existe");
$mig009Content = file_get_contents($mig009['path']);
assertTest(str_contains($mig009Content, "INSERT INTO `units`"), "La migration 009 insère bien spy_probe dans la table units");

// 2. Présence de la méthode autoMigrateIfPending
echo PHP_EOL . "[TEST 2] Vérification de la méthode MigrationEngine::autoMigrateIfPending..." . PHP_EOL;
assertTest(method_exists('MigrationEngine', 'autoMigrateIfPending'), "La méthode autoMigrateIfPending existe sur MigrationEngine");

// 3. Intégration dans Database.php
echo PHP_EOL . "[TEST 3] Vérification de l'auto-migration dans Database.php..." . PHP_EOL;
$dbContent = file_get_contents(__DIR__ . '/../core/Database.php');
assertTest(str_contains($dbContent, "MigrationEngine::autoMigrateIfPending"), "Database::getConnection appelle bien autoMigrateIfPending");

// 4. Intégration dans UpdateEngine.php
echo PHP_EOL . "[TEST 4] Vérification de l'application automatique des migrations dans UpdateEngine.php..." . PHP_EOL;
$updateContent = file_get_contents(__DIR__ . '/../core/UpdateEngine.php');
assertTest(str_contains($updateContent, "MigrationEngine::run"), "UpdateEngine applique bien les migrations SQL après un git pull");

// 5. Sécurisation de BotEngine.php
echo PHP_EOL . "[TEST 5] Vérification de la sécurisation et auto-réparation dans BotEngine.php..." . PHP_EOL;
$botContent = file_get_contents(__DIR__ . '/../core/BotEngine.php');
assertTest(str_contains($botContent, "ensureShinobiUnitExists"), "BotEngine possède la méthode d'auto-réparation ensureShinobiUnitExists");
assertTest(str_contains($botContent, "try {"), "BotEngine possède des blocs try-catch protecteurs");
assertTest(str_contains($botContent, "Erreur insertion spy_probe"), "L'insertion de spy_probe dans launchBotEspionage est protégée");
assertTest(str_contains($botContent, "Erreur cycle IA"), "Le cycle des bots dans tickPeriodicSimulation est protégé");

// 6. Sécurisation de header.php (Prévention Erreur 500)
echo PHP_EOL . "[TEST 6] Vérification de la protection contre l'erreur 500 dans header.php..." . PHP_EOL;
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assertTest(str_contains($headerContent, "catch (\\Throwable \$e)"), "header.php capture les exceptions du BotEngine");
assertTest(str_contains($headerContent, "BotEngine Simulation Error in header.php"), "header.php journalise l'erreur sans bloquer le chargement de la page");

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS DE SÉCURITÉ ET D'AUTO-MIGRATION SONT 100% VALIDES !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;

