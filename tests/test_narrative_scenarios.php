<?php
/**
 * Test unitaire et d'intégration : Scénarios Narratifs, Tâches Multiples par Métier & Studio Pleine Page
 * Fichier : tests/test_narrative_scenarios.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScenarioEngine.php';

echo "========================================================================\n";
echo "  BANC DE TESTS : NARRATIVE DESIGNER (STUDIO PLEINE PAGE & MULTI-TÂCHES)\n";
echo "========================================================================\n\n";

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

// 4. Test spécifique : Tâches MULTIPLES par métier sur un scénario
echo "[TEST] Création d'un scénario avec tâches multiples par métier...\n";
$multiTasksArray = [
    // 3 Tâches distinctes pour Développeur Back-End
    [
        'task_id'      => 1,
        'role_code'    => 'backend',
        'role_name'    => 'Développeur Back-End',
        'title'        => 'Migration DDL tables support et triggers',
        'description'  => 'Créer la table narrative_steps et triggers d\'état',
        'assignee'     => 'Dev Back Senior',
        'complexity'   => 'M',
        'status'       => 'done',
        'deliverables' => 'core/ScenarioEngine.php'
    ],
    [
        'task_id'      => 2,
        'role_code'    => 'backend',
        'role_name'    => 'Développeur Back-End',
        'title'        => 'Logique de validation des conditions de victoire',
        'description'  => 'Vérifier la garnison de 20 lanciers dans BarracksEngine',
        'assignee'     => 'Dev Back Junior',
        'complexity'   => 'L',
        'status'       => 'in_progress',
        'deliverables' => 'core/BarracksEngine.php'
    ],
    [
        'task_id'      => 3,
        'role_code'    => 'backend',
        'role_name'    => 'Développeur Back-End',
        'title'        => 'Distribution transactionnelle des Kobans et récompenses',
        'description'  => 'Garantir l\'atomicité de remise des 300 Kobans',
        'assignee'     => 'Dev Back Senior',
        'complexity'   => 'S',
        'status'       => 'todo',
        'deliverables' => 'core/RewardsEngine.php'
    ],
    // 2 Tâches distinctes pour Game & Level Designer
    [
        'task_id'      => 4,
        'role_code'    => 'level_designer',
        'role_name'    => 'Game & Level Designer',
        'title'        => 'Courbe de coût d\'entraînement du lancier Ashigaru',
        'description'  => 'Équilibrer le coût en riz et fer par unité',
        'assignee'     => 'Lead Game Designer',
        'complexity'   => 'M',
        'status'       => 'done',
        'deliverables' => 'config/game_constants.php'
    ],
    [
        'task_id'      => 5,
        'role_code'    => 'level_designer',
        'role_name'    => 'Game & Level Designer',
        'title'        => 'Paliers de quêtes intermédiaires du didacticiel',
        'description'  => 'Sous-objectifs : 5 puis 10 puis 20 unités',
        'assignee'     => 'Level Designer',
        'complexity'   => 'S',
        'status'       => 'done',
        'deliverables' => 'data/quests.json'
    ],
    // 1 Tâche Front-End
    [
        'task_id'      => 6,
        'role_code'    => 'frontend',
        'role_name'    => 'Développeur Front-End',
        'title'        => 'Modale de triomphe avec animation particules',
        'description'  => 'Intégrer les confettis dorés lors de la complétion du Step 4',
        'assignee'     => 'Dev Front',
        'complexity'   => 'M',
        'status'       => 'in_progress',
        'deliverables' => 'views/partials/modal_victory.php'
    ]
];

$multiScenarioData = [
    'title'             => 'Step 4 : L\'Appel aux Armes & Voie du Bushido (Multi-Tâches)',
    'slug'              => 'step-04-appel-aux-armes-multitasks',
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
    'status'            => 'in_production',
    'tasks'             => $multiTasksArray
];

$multiId = $engine->saveScenarioFull($multiScenarioData);
$savedSc = $engine->getScenarioById($multiId);

if ($savedSc && count($savedSc['tasks']) === 6) {
    echo "[PASS] Scénario avec 6 tâches multiples sauvegardé avec succès (ID #{$multiId})\n";
} else {
    echo "[FAIL] Nombre de tâches incorrect après sauvegarde (Trouvé: " . count($savedSc['tasks'] ?? []) . ")\n";
    exit(1);
}

// 5. Test du calcul des métriques globales et ventilées par métier (by_role)
$metrics = $savedSc['metrics'];
if ($metrics['total'] === 6 && $metrics['done'] === 3 && $metrics['percent'] === 50) {
    echo "[PASS] Métriques globales validées : 3/6 tâches terminées (50%)\n";
} else {
    echo "[FAIL] Métriques globales incorrectes : Total={$metrics['total']}, Done={$metrics['done']}, Pct={$metrics['percent']}\n";
    exit(1);
}

$byRole = $metrics['by_role'];
if ($byRole['backend']['total'] === 3 && $byRole['backend']['done'] === 1 && $byRole['backend']['percent'] === 33) {
    echo "[PASS] Métriques métier Back-End validées : 3 tâches dont 1 terminée (33%)\n";
} else {
    echo "[FAIL] Métriques métier Back-End incorrectes\n";
    exit(1);
}

if ($byRole['level_designer']['total'] === 2 && $byRole['level_designer']['done'] === 2 && $byRole['level_designer']['percent'] === 100) {
    echo "[PASS] Métriques métier Level Designer validées : 2 tâches dont 2 terminées (100%)\n";
} else {
    echo "[FAIL] Métriques métier Level Designer incorrectes\n";
    exit(1);
}

// 6. Test du groupement par rôle getTasksGroupedByRole()
$grouped = $engine->getTasksGroupedByRole($savedSc['tasks']);
if (count($grouped['backend']['tasks']) === 3 && count($grouped['level_designer']['tasks']) === 2 && count($grouped['frontend']['tasks']) === 1) {
    echo "[PASS] getTasksGroupedByRole() regroupe correctement les tâches multiples par métier\n";
} else {
    echo "[FAIL] getTasksGroupedByRole() défaillant\n";
    exit(1);
}

// 7. Test de duplication de scénario avec ses tâches multiples
$duplicate = $engine->duplicateScenario($multiId, 1);
if ($duplicate && strpos($duplicate['title'], 'Copie') !== false && count($duplicate['tasks']) === 6) {
    echo "[PASS] duplicateScenario() clone fidèlement l'ensemble des 6 tâches multiples\n";
} else {
    echo "[FAIL] Échec du clonage des tâches multiples\n";
    exit(1);
}

// 8. Test d'export Markdown GDD
$mdExport = $engine->exportMarkdown($multiId);
if (strpos($mdExport, 'CAHIER DE SCÉNARISATION') !== false && strpos($mdExport, 'Matrice des 10 Tâches Métiers') !== false) {
    echo "[PASS] exportMarkdown() génère un GDD structuré complet\n";
} else {
    echo "[FAIL] Export Markdown incomplet\n";
    exit(1);
}

// 9. Test de rendu MVC : Mode Catalogue
$_GET['action'] = 'catalog';
ob_start();
include __DIR__ . '/../views/gamestudio/narrative_scenarios.php';
$htmlCatalog = ob_get_clean();

if (strlen($htmlCatalog) > 1000 && strpos($htmlCatalog, 'gamestudio-narrative-scenarios-catalog') !== false) {
    echo "[PASS] Rendu MVC Mode Catalogue validé (" . strlen($htmlCatalog) . " octets)\n";
} else {
    echo "[FAIL] Rendu MVC Mode Catalogue défaillant\n";
    exit(1);
}

// 10. Test de rendu MVC : Mode Éditeur Pleine Page Studio (action = 'edit')
$_GET['action'] = 'edit';
$_GET['id'] = $multiId;
ob_start();
include __DIR__ . '/../views/gamestudio/narrative_scenarios.php';
$htmlFullPage = ob_get_clean();

if (strlen($htmlFullPage) > 1000 && strpos($htmlFullPage, 'gamestudio-narrative-editor-fullpage') !== false && strpos($htmlFullPage, 'Matrice Transversale des 10 Métiers') !== false) {
    echo "[PASS] Rendu MVC Mode Éditeur Pleine Page validé (" . strlen($htmlFullPage) . " octets)\n";
} else {
    echo "[FAIL] Rendu MVC Mode Éditeur Pleine Page défaillant\n";
    exit(1);
}

echo "\n========================================================================\n";
echo " TOUS LES TESTS NARRATIVE DESIGNER (FULL PAGE & MULTI-TÂCHES) SONT AU VERT !\n";
echo "========================================================================\n";
