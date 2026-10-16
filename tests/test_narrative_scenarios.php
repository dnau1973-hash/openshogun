<?php
/**
 * Test unitaire et d'intégration : Scénarios Narratifs & Matrice des 10 Tâches Métiers
 * Fichier : tests/test_narrative_scenarios.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScenarioEngine.php';

echo "========================================================\n";
echo "  BANC DE TESTS : NARRATIVE DESIGNER & 10 TÂCHES MÉTIERS\n";
echo "========================================================\n\n";

// 1. Initialisation base SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Table des scénarios en SQLite
$sqlite->exec("CREATE TABLE narrative_scenarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL DEFAULT 'step',
    step_number INTEGER NULL,
    title TEXT NOT NULL,
    subtitle TEXT NULL,
    era TEXT NULL,
    synopsis TEXT NOT NULL,
    speaker_name TEXT NULL,
    speaker_role TEXT NULL,
    dialogue TEXT NOT NULL,
    trigger_condition TEXT NULL,
    rewards_summary TEXT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    tasks_json TEXT NULL,
    created_by INTEGER NULL,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
)");

$engine = new ScenarioEngine();

// 2. Vérification des 10 métiers clés
$roles = ScenarioEngine::ROLES;
if (count($roles) === 10) {
    echo "[PASS] 10 Métiers clés de développement définis avec succès\n";
} else {
    echo "[FAIL] Nombre de métiers incorrect : " . count($roles) . "\n";
    exit(1);
}

// Vérifier la présence explicite des métiers demandés par l'utilisateur
$expectedRoles = ['level_designer', 'backend', 'frontend', 'network', 'artist_2d', 'artist_env', 'sound', 'narrative', 'qa', 'producer'];
foreach ($expectedRoles as $r) {
    if (isset($roles[$r])) {
        echo "  - Métier validé : {$roles[$r]['name']} ({$r})\n";
    } else {
        echo "[FAIL] Métier manquant : {$r}\n";
        exit(1);
    }
}

// 3. Récupération des scénarios pré-ensemencés
$scenarios = $engine->getScenarios();
echo "[PASS] Scénarios récupérés : " . count($scenarios) . " scénarios disponibles\n";

$stepScenarios = array_filter($scenarios, fn($s) => $s['type'] === 'step');
$eventScenarios = array_filter($scenarios, fn($s) => $s['type'] === 'special_event');

if (count($stepScenarios) >= 3 && count($eventScenarios) >= 3) {
    echo "[PASS] Répartition validée : " . count($stepScenarios) . " steps et " . count($eventScenarios) . " événements spéciaux\n";
} else {
    echo "[FAIL] Répartition insuffisante de steps ou événements\n";
    exit(1);
}

// 4. Vérification que chaque scénario possède bien ses 10 tâches métiers
$first = $scenarios[0];
if (!empty($first['tasks']) && count($first['tasks']) === 10) {
    echo "[PASS] Le scénario '{$first['title']}' possède bien 10 tâches métiers distinctes\n";
} else {
    echo "[FAIL] Le scénario ne possède pas 10 tâches métiers (Trouvé : " . count($first['tasks'] ?? []) . ")\n";
    exit(1);
}

// 5. Test de création d'un nouveau scénario personnalisé
$newScenarioData = [
    'title'             => 'Step 4 : L\'Appel aux Armes & Voie du Bushido',
    'slug'              => 'step-04-appel-aux-armes',
    'type'              => 'step',
    'step_number'       => 4,
    'subtitle'          => 'Chapitre 4 : Fondation du Dojo et formation des premiers Ashigarus',
    'era'               => '1568 - Montée en Puissance Martiale',
    'synopsis'          => 'Pour défendre le fief et projeter sa puissance, le daimyō doit construire le Dojo et entraîner ses premiers lanciers.',
    'speaker_name'      => 'Maître d\'Armes Yagyū Muneyoshi',
    'speaker_role'      => 'Grand Bretteur du Shinkage-ryū',
    'dialogue'          => 'Un samouraï sans discipline n\'est qu\'un vent mauvais. Forgez vos lances et élevez le dojo sur la colline.',
    'trigger_condition' => 'Dojo niveau 1 + 20 Ashigarus en garnison',
    'rewards_summary'   => '300 Kobans, Déverrouillage des archers Yumi',
    'status'            => 'in_production'
];

$newId = $engine->saveScenarioFull($newScenarioData);
$createdSc = $engine->getScenarioById($newId);

if ($createdSc && $createdSc['title'] === $newScenarioData['title'] && count($createdSc['tasks']) === 10) {
    echo "[PASS] Nouveau scénario créé avec succès (ID #{$newId}) avec ses 10 tâches générées automatiquement\n";
} else {
    echo "[FAIL] Échec de création du scénario\n";
    exit(1);
}

// 6. Test de mise à jour du statut d'une tâche (Back-End puis Game Designer)
$okTask1 = $engine->updateTaskStatus($newId, 1, 'in_progress'); // Task 1 : Game & Level Designer
$okTask2 = $engine->updateTaskStatus($newId, 2, 'done');        // Task 2 : Développeur Back-End

if ($okTask1 && $okTask2) {
    $scUpdated = $engine->getScenarioById($newId);
    $m = $scUpdated['metrics'];
    if ($m['done'] === 1 && $m['in_progress'] === 1 && $m['percent'] === 10) {
        echo "[PASS] updateTaskStatus() validé : Métriques d'avancement mises à jour (10% - 1 tâche Done, 1 In Progress)\n";
    } else {
        echo "[FAIL] Métriques de tâches incohérentes après mise à jour\n";
        exit(1);
    }
} else {
    echo "[FAIL] Échec updateTaskStatus\n";
    exit(1);
}

// 7. Test de duplication d'un scénario
$duplicate = $engine->duplicateScenario($newId, 1);
if ($duplicate && strpos($duplicate['title'], 'Copie') !== false && count($duplicate['tasks']) === 10) {
    echo "[PASS] duplicateScenario() validé : Scénario cloné avec succès (ID #{$duplicate['id']})\n";
} else {
    echo "[FAIL] Échec de duplication\n";
    exit(1);
}

// 8. Test d'export Markdown / GDD
$mdExport = $engine->exportMarkdown($newId);
if (strpos($mdExport, 'CAHIER DE SCÉNARISATION') !== false && strpos($mdExport, 'Matrice des 10 Tâches Métiers') !== false) {
    echo "[PASS] exportMarkdown() validé : GDD structuré généré avec succès\n";
} else {
    echo "[FAIL] Export Markdown non conforme\n";
    exit(1);
}

// 9. Test de rendu MVC de views/gamestudio/narrative_scenarios.php
ob_start();
include __DIR__ . '/../views/gamestudio/narrative_scenarios.php';
$html = ob_get_clean();

if (strlen($html) > 1000 && strpos($html, 'Scénarios de Jeu &amp; Matrice des 10 Tâches Métiers') !== false) {
    echo "[PASS] Rendu MVC views/gamestudio/narrative_scenarios.php validé (" . strlen($html) . " octets)\n";
} else {
    echo "[FAIL] Rendu MVC de la vue échoué\n";
    exit(1);
}

echo "\nTOUS LES TESTS DU MODULE NARRATIVE DESIGNER SONT PASSÉS AVEC SUCCÈS !\n";

