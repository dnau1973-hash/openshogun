<?php
/**
 * Test unitaire et d'intégration : Unicité stricte des Aventures du Héros
 * Fichier : tests/test_hero_adventures_uniqueness.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

echo "========================================================================\n";
echo "  BANC DE TESTS : UNICITÉ STRICTE DES EXPÉDITIONS & AVENTURES DU HÉROS\n";
echo "========================================================================\n\n";

// 1. Initialisation d'une base SQLite en mémoire
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

// Création des tables minimales requises
$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    faction TEXT DEFAULT 'terran'
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT,
    coord_x INTEGER DEFAULT 500,
    coord_y INTEGER DEFAULT 500
)");

$sqlite->exec("CREATE TABLE heroes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    current_planet_id INTEGER,
    name TEXT DEFAULT 'Samouraï Champion',
    level INTEGER DEFAULT 1,
    experience INTEGER DEFAULT 0,
    health REAL DEFAULT 100.0,
    status TEXT DEFAULT 'home',
    revive_finish_time INTEGER NULL,
    last_health_update INTEGER DEFAULT 0,
    unassigned_points INTEGER DEFAULT 4,
    stat_strength INTEGER DEFAULT 0,
    stat_offense_bonus INTEGER DEFAULT 0,
    stat_defense_bonus INTEGER DEFAULT 0,
    stat_production INTEGER DEFAULT 0,
    production_type TEXT DEFAULT 'balanced',
    equipped_weapon TEXT NULL,
    equipped_helmet TEXT NULL,
    equipped_armor TEXT NULL,
    equipped_horse TEXT NULL,
    equipped_talisman TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE hero_adventures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    coord_x INTEGER NOT NULL,
    coord_y INTEGER NOT NULL,
    name TEXT NOT NULL,
    difficulty TEXT DEFAULT 'easy',
    status TEXT DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at INTEGER NULL
)");

$sqlite->exec("CREATE TABLE hero_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    item_code TEXT NOT NULL,
    item_type TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    bonus_data TEXT NOT NULL,
    is_equipped INTEGER DEFAULT 0,
    acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE fleet_missions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    source_planet_id INTEGER NOT NULL,
    adventure_id INTEGER NULL,
    mission_type TEXT NOT NULL,
    fleet_data TEXT,
    cargo_data TEXT,
    has_hero INTEGER DEFAULT 0,
    departure_time INTEGER NOT NULL,
    arrival_time INTEGER NOT NULL,
    return_time INTEGER NOT NULL,
    status TEXT DEFAULT 'en_route'
)");

require_once __DIR__ . '/../core/HeroEngine.php';
$heroEngine = new HeroEngine();

// Insertion utilisateur et fief
$sqlite->exec("INSERT INTO users (id, username, faction) VALUES (1, 'NobunagaTest', 'terran')");
$sqlite->exec("INSERT INTO planets (id, user_id, name, coord_x, coord_y) VALUES (1, 1, 'Château d''Azuchi', 500, 500)");

// TEST 1 : Création du héros et génération initiale des 3 aventures
echo "[TEST 1] Création du héros et attribution initiale des 3 aventures...\n";
$hero = $heroEngine->createHeroForUser(1, 'Héros Test', 1, 500, 500);
assert(!empty($hero['id']), "Le héros doit être créé.");

$adventures = $heroEngine->getAdventures(1);
assert(count($adventures) === 3, "Il doit y avoir exactement 3 aventures disponibles.");

$names = array_column($adventures, 'name');
$uniqueNames = array_unique($names);
assert(count($uniqueNames) === 3, "Les 3 aventures de départ doivent avoir des noms strictement distincts.");
echo "  [PASS] 3 aventures initiales générées avec des noms 100% uniques : " . implode(', ', $names) . "\n\n";

// TEST 2 : Appel répété de ensureAvailableAdventures ne doit pas dupliquer
echo "[TEST 2] Appel répété de ensureAvailableAdventures()...\n";
$heroEngine->ensureAvailableAdventures(1, 3);
$adventuresAfter = $heroEngine->getAdventures(1);
assert(count($adventuresAfter) === 3, "Il doit toujours y avoir exactement 3 aventures.");
$namesAfter = array_column($adventuresAfter, 'name');
assert(count(array_unique($namesAfter)) === 3, "Toujours 3 aventures distinctes sans doublon.");
echo "  [PASS] Aucun doublon créé lors de ré-appels successifs.\n\n";

// TEST 3 : Nettoyage automatique des doublons existants
echo "[TEST 3] Nettoyage et résorption des doublons pré-existants...\n";
// Injection délibérée d'un doublon
$sqlite->prepare("INSERT INTO hero_adventures (user_id, coord_x, coord_y, name, difficulty, status) VALUES (?, 505, 505, ?, 'easy', 'available')")
       ->execute([1, $names[0]]);

// Vérification que le doublon a été injecté
$stmtCount = $sqlite->query("SELECT COUNT(*) FROM hero_adventures WHERE user_id = 1 AND status = 'available'");
assert((int)$stmtCount->fetchColumn() === 4, "4 aventures (avec doublon) présentes.");

// Appel de getAdventures qui doit assainir et régénérer
$cleanedAdventures = $heroEngine->getAdventures(1);
assert(count($cleanedAdventures) === 3, "Après nettoyage, exactement 3 aventures doivent subsister.");
$cleanedNames = array_column($cleanedAdventures, 'name');
assert(count(array_unique($cleanedNames)) === 3, "Toutes les aventures après assainissement sont strictement uniques.");
echo "  [PASS] Doublon préexistant résorbé avec succès. Liste actuelle : " . implode(', ', $cleanedNames) . "\n\n";

// TEST 4 : Aventure en cours ('in_progress') ne doit pas être réattribuée
echo "[TEST 4] Aventure en cours de trajet : l'aventure de remplacement ne doit pas reprendre son nom...\n";
$firstAdv = $cleanedAdventures[0];
$sqlite->prepare("UPDATE hero_adventures SET status = 'in_progress' WHERE id = ?")->execute([$firstAdv['id']]);

// Appel de ensureAvailableAdventures pour compléter à 3 disponibles
$heroEngine->ensureAvailableAdventures(1, 3);
$currentAvail = $heroEngine->getAdventures(1);
assert(count($currentAvail) === 3, "3 aventures disponibles doivent être présentes en plus de celle en cours.");

$allActiveNames = array_column($currentAvail, 'name');
assert(!in_array($firstAdv['name'], $allActiveNames, true), "L'aventure en cours ('{$firstAdv['name']}') ne doit pas être réattribuée parmi les disponibles !");
$allActiveUnique = array_unique(array_merge($allActiveNames, [$firstAdv['name']]));
assert(count($allActiveUnique) === 4, "Les 4 aventures actives (3 disponibles + 1 en cours) sont toutes uniques.");
echo "  [PASS] Unicité totale respectée : aucune aventure active ne partage le même nom qu'une aventure en cours.\n\n";

// TEST 5 : Remplacement après achèvement
echo "[TEST 5] Achèvement de l'aventure et régénération via generateReplacementAdventure()...\n";
$sqlite->prepare("UPDATE hero_adventures SET status = 'completed' WHERE id = ?")->execute([$firstAdv['id']]);
$heroEngine->generateReplacementAdventure(1, 500, 500);

$finalAdventures = $heroEngine->getAdventures(1);
assert(count($finalAdventures) === 3, "Exactement 3 aventures disponibles après remplacement.");
$finalNames = array_column($finalAdventures, 'name');
assert(count(array_unique($finalNames)) === 3, "Toutes les 3 aventures sont uniques.");
echo "  [PASS] Aventure remplacée sans aucun doublon actif : " . implode(', ', $finalNames) . "\n\n";

echo "========================================================================\n";
echo " TOUS LES TESTS D'UNICITÉ DES AVENTURES DU HÉROS SONT PASSÉS ! \n";
echo "========================================================================\n";
