<?php
/**
 * Test Automatisé : Système d'Événements Hebdomadaires Narratifs (Chroniques du Sengoku)
 * OpenShogun - Test unitaire complet (Engine, Progression, Récompenses & Administration)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/WeeklyEventEngine.php';

echo "====================================================================\n";
echo " TEST UNITAIRE : CHRONIQUES FÉODALES & ÉVÉNEMENTS HEBDOMADAIRES     \n";
echo "====================================================================\n\n";

// 1. Initialiser une base SQLite en mémoire avec le schéma complet requis
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setConnection($sqlite);

// Schéma minimal requis pour les joueurs, planètes et héros
$sqlite->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        gold_coins INTEGER NOT NULL DEFAULT 100,
        honor_points INTEGER NOT NULL DEFAULT 50,
        honorary_title TEXT DEFAULT NULL,
        imperial_seal_until TEXT DEFAULT NULL,
        is_admin INTEGER NOT NULL DEFAULT 0,
        is_bot INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE planets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        metal REAL NOT NULL DEFAULT 500,
        crystal REAL NOT NULL DEFAULT 500,
        deuterium REAL NOT NULL DEFAULT 500,
        clay REAL NOT NULL DEFAULT 100,
        tea REAL NOT NULL DEFAULT 100,
        soybean REAL NOT NULL DEFAULT 100,
        rice_flour REAL NOT NULL DEFAULT 50,
        sake REAL NOT NULL DEFAULT 20,
        wooden_beams REAL NOT NULL DEFAULT 10,
        metal_max REAL NOT NULL DEFAULT 10000,
        crystal_max REAL NOT NULL DEFAULT 10000,
        deuterium_max REAL NOT NULL DEFAULT 10000,
        clay_max REAL NOT NULL DEFAULT 10000,
        tea_max REAL NOT NULL DEFAULT 10000,
        soybean_max REAL NOT NULL DEFAULT 10000
    );

    CREATE TABLE planet_rural_plots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        structure_type TEXT NOT NULL,
        level INTEGER NOT NULL DEFAULT 1
    );

    CREATE TABLE planet_buildings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        building_code TEXT NOT NULL,
        level INTEGER NOT NULL DEFAULT 1
    );

    CREATE TABLE planet_troops (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        planet_id INTEGER NOT NULL,
        unit_code TEXT NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE hero_samurai (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        attack_strength INTEGER NOT NULL DEFAULT 100
    );
");

// Insertion d'un utilisateur de test et de son fief
$sqlite->exec("
    INSERT INTO users (id, username, gold_coins, honor_points, is_admin)
    VALUES (1, 'NobunagaTest', 100, 50, 1);

    INSERT INTO planets (id, user_id, name, metal, crystal, deuterium, clay, tea, soybean, sake)
    VALUES (1, 1, 'Kiyosu', 800, 400, 150, 50, 30, 20, 10);

    INSERT INTO planet_rural_plots (planet_id, structure_type, level)
    VALUES (1, 'riziere', 3), (1, 'foret', 2), (1, 'fosse_argile', 2);

    INSERT INTO planet_buildings (planet_id, building_code, level)
    VALUES (1, 'hq', 4), (1, 'barracks', 2);

    INSERT INTO planet_troops (planet_id, unit_code, quantity)
    VALUES (1, 'spearman', 25), (1, 'archer', 10);

    INSERT INTO hero_samurai (user_id, attack_strength)
    VALUES (1, 350);
");

echo "[PASS] Base de données de test SQLite et tables joueurs/fiefs initialisées.\n";

// 2. Initialiser le WeeklyEventEngine (crée automatiquement les 4 tables d'événements)
$engine = new WeeklyEventEngine($sqlite);

// Vérifier la présence des tables
$tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%weekly%'")->fetchAll(PDO::FETCH_COLUMN);
assert(in_array('weekly_events', $tables), "Table weekly_events manquante.");
assert(in_array('weekly_event_tasks', $tables), "Table weekly_event_tasks manquante.");
assert(in_array('player_weekly_events', $tables), "Table player_weekly_events manquante.");
assert(in_array('player_weekly_event_tasks', $tables), "Table player_weekly_event_tasks manquante.");
echo "[PASS] Tables du système d'événements hebdomadaires créées avec succès.\n";

// 3. Créer un événement test avec 3 actes narratifs séquentiels
$eventData = [
    'slug' => 'test-okehazama',
    'title' => 'Chronique d\'Okehazama Test',
    'jp_title' => '桶狭間の戦い',
    'era_year' => '1560',
    'description' => 'Un orage soudain éclate sur les collines d\'Okehazama...',
    'image_url' => '/public/assets/carrousel/carrousel_03_conseil_guerre.jpg',
    'starts_at' => date('Y-m-d 00:00:00', strtotime('-1 day')),
    'ends_at' => date('Y-m-d 23:59:59', strtotime('+6 days')),
    'is_active' => 1,
    'reward_final_gold' => 75,
    'reward_final_honor' => 150,
    'reward_final_seal_days' => 2,
    'reward_final_title' => 'Vainqueur d\'Okehazama'
];
$eventId = $engine->saveEvent($eventData);
assert($eventId > 0, "Échec de sauvegarde de l'événement.");
echo "[PASS] Événement narratif créé (ID: $eventId).\n";

// Ajouter 3 tâches / actes narratifs
$task1Id = $engine->saveTask([
    'event_id' => $eventId,
    'step_order' => 1,
    'title' => 'Acte I : Approvisionner les Rations de Marche',
    'description' => 'Constituer une réserve d\'au moins 200 koku de riz pour l\'avant-garde.',
    'task_type' => 'stock_resource',
    'target_key' => 'deuterium',
    'target_amount' => 200,
    'reward_metal' => 100,
    'reward_gold' => 10,
    'reward_honor' => 20
]);

$task2Id = $engine->saveTask([
    'event_id' => $eventId,
    'step_order' => 2,
    'title' => 'Acte II : Rallier la Garde Rapprochée',
    'description' => 'Mobiliser une troupe de 20 lanciers Ashigaru.',
    'task_type' => 'train_troops',
    'target_key' => 'spearman',
    'target_amount' => 20,
    'reward_metal' => 200,
    'reward_deuterium' => 150,
    'reward_gold' => 20
]);

$task3Id = $engine->saveTask([
    'event_id' => $eventId,
    'step_order' => 3,
    'title' => 'Acte III : L\'Assaut de l\'Éclair',
    'description' => 'Élever le Tenshu au niveau 3 pour commander la charge.',
    'task_type' => 'build_level',
    'target_key' => 'hq',
    'target_amount' => 3,
    'reward_gold' => 30,
    'reward_honor' => 50,
    'reward_item' => 'sake:5'
]);

assert($task1Id > 0 && $task2Id > 0 && $task3Id > 0, "Échec de création des tâches.");
echo "[PASS] 3 actes narratifs associés à l'événement.\n";

// 4. Tester getActiveEvent et l'évaluation initiale de progression
$active = $engine->getActiveEvent(1, 1);
assert($active !== null, "Événement actif non trouvé.");
assert($active['id'] == $eventId, "Mauvais événement actif retourné.");
assert(count($active['tasks']) === 3, "Nombre de tâches incorrect.");
echo "[PASS] getActiveEvent retourne l'événement actif avec ses actes.\n";

// Vérifier les progrès initiaux :
// Task 1 : stock deuterium actuel = 150 / requis = 200 => non achevé (progress = 150)
// Task 2 : spearman count = 25 / requis = 20 => achevé ! (25 >= 20)
// Task 3 : hq level = 4 / requis = 3 => achevé ! (4 >= 3)
$t1 = $active['player_status']['tasks'][0];
$t2 = $active['player_status']['tasks'][1];
$t3 = $active['player_status']['tasks'][2];

assert($t1['current_progress'] == 150 && $t1['is_completed'] == 0, "Tâche 1 devrait être à 150/200 non complétée.");
assert($t2['current_progress'] >= 20 && $t2['is_completed'] == 1, "Tâche 2 devrait être complétée.");
assert($t3['current_progress'] >= 3 && $t3['is_completed'] == 1, "Tâche 3 devrait être complétée.");
echo "[PASS] Évaluation dynamique des objectifs (ressources, armée, niveau bâtiment) exacte.\n";

// 5. Tester la tentative de claim d'une tâche non terminée
$claimT1 = $engine->claimTaskReward(1, 1, $task1Id);
assert($claimT1['success'] === false, "La tâche 1 ne devrait pas pouvoir être réclamée.");
echo "[PASS] Sécurité : impossible de réclamer un acte incomplet.\n";

// 6. Compléter la tâche 1 en augmentant le stock de riz (deuterium)
$sqlite->exec("UPDATE planets SET deuterium = 250 WHERE id = 1");
$activeAfterUpdate = $engine->getActiveEvent(1, 1);
$t1Updated = $activeAfterUpdate['player_status']['tasks'][0];
assert($t1Updated['is_completed'] == 1, "La tâche 1 devrait maintenant être complétée.");
echo "[PASS] Mise à jour en temps réel : tâche 1 complétée suite à l'augmentation du stock.\n";

// 7. Réclamer les récompenses de la tâche 1
$initialGold = (int)$sqlite->query("SELECT gold_coins FROM users WHERE id = 1")->fetchColumn();
$claimRes1 = $engine->claimTaskReward(1, 1, $task1Id);
assert($claimRes1['success'] === true, "Échec de réclamation de la tâche 1.");
$newGold = (int)$sqlite->query("SELECT gold_coins FROM users WHERE id = 1")->fetchColumn();
assert($newGold === $initialGold + 10, "L'or du joueur devrait avoir augmenté de 10 Kobans.");
echo "[PASS] Récompense de l'acte I réclamée et créditée avec succès (+10 Kobans).\n";

// Tenter de réclamer à nouveau la tâche 1 -> Doit échouer
$claimT1Again = $engine->claimTaskReward(1, 1, $task1Id);
assert($claimT1Again['success'] === false, "Impossible de réclamer deux fois la même récompense.");
echo "[PASS] Protection anti-duplication : impossible de réclamer deux fois.\n";

// 8. Réclamer les tâches 2 et 3
$engine->claimTaskReward(1, 1, $task2Id);
$engine->claimTaskReward(1, 1, $task3Id);

// Vérifier que tous les actes sont complétés
$statusFinal = $engine->getActiveEvent(1, 1)['player_status'];
assert($statusFinal['all_completed'] === true, "Tous les actes devraient être complétés.");
assert($statusFinal['final_claimed'] === false, "La récompense finale ne devrait pas encore être réclamée.");
echo "[PASS] Tous les actes validés, la récompense finale est débloquée.\n";

// 9. Réclamer la récompense finale
$goldBeforeFinal = (int)$sqlite->query("SELECT gold_coins FROM users WHERE id = 1")->fetchColumn();
$claimFinalRes = $engine->claimFinalReward(1, 1, $eventId);
assert($claimFinalRes['success'] === true, "Échec de réclamation de la récompense finale.");
$goldAfterFinal = (int)$sqlite->query("SELECT gold_coins FROM users WHERE id = 1")->fetchColumn();
assert($goldAfterFinal === $goldBeforeFinal + 75, "L'or devrait avoir augmenté de 75 Kobans pour la victoire finale.");

$userRow = $sqlite->query("SELECT honorary_title, imperial_seal_until FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assert($userRow['honorary_title'] === 'Vainqueur d\'Okehazama', "Le titre honorifique n'a pas été attribué.");
assert(!empty($userRow['imperial_seal_until']), "Le Sceau Impérial n'a pas été prolongé.");
echo "[PASS] Récompense finale de la Chronique attribuée (Titre honorifique, +75 Kobans, Sceau Impérial).\n";

// 10. Tester les fonctions d'administration
$allEvents = $engine->getAllEvents();
assert(count($allEvents) >= 1, "getAllEvents devrait retourner au moins 1 événement.");

// Créer un second événement
$event2Id = $engine->saveEvent([
    'slug' => 'test-sakai',
    'title' => 'Chronique de Sakai Test',
    'jp_title' => '堺の豪商',
    'era_year' => '1569',
    'description' => 'Les maîtres de thé de la cité franche de Sakai...',
    'starts_at' => date('Y-m-d 00:00:00'),
    'ends_at' => date('Y-m-d 23:59:59', strtotime('+7 days')),
    'is_active' => 0
]);
assert($event2Id > 0, "Échec de création du second événement.");

// Basculer l'événement 2 en actif
$engine->setActiveEvent($event2Id);
$active2 = $engine->getActiveEvent(1, 1);
assert($active2['id'] == $event2Id, "L'événement 2 devrait désormais être l'événement actif.");
echo "[PASS] Bascule d'événement actif via l'administration fonctionnelle.\n";

// Supprimer le second événement
$deleteRes = $engine->deleteEvent($event2Id);
assert($deleteRes === true, "Échec de suppression de l'événement 2.");
assert($engine->getEventDetails($event2Id) === null, "L'événement supprimé ne devrait plus exister.");
echo "[PASS] Suppression d'événement via l'administration vérifiée.\n";

echo "\n====================================================================\n";
echo " TOUS LES TESTS DES CHRONIQUES FÉODALES SONT PASSÉS AVEC SUCCÈS (100%)\n";
echo "====================================================================\n";

