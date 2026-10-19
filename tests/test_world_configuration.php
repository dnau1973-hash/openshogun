<?php
/**
 * Test Automatisé : Configuration du Monde & Dimensionnement des Tuiles
 * Vérifie le calcul des tuiles (2R+1)², la persistance dans GameConfig,
 * les contrôles de sécurité et le rendu de la vue générale du Game Studio.
 * 
 * Fichier : tests/test_world_configuration.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';

function assertTest(bool $condition, string $title): void {
    if ($condition) {
        echo "  [PASS] " . $title . "\n";
    } else {
        echo "  [FAIL] " . $title . "\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo "  BANC DE TESTS : CONFIGURATION DU MONDE & TUILES GÉNÉRÉES\n";
echo "========================================================================\n\n";

// Initialisation d'une base SQLite en mémoire pour le banc de test
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

$sqlite->exec("CREATE TABLE game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sqlite->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    is_bot INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE planets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0
)");

$sqlite->exec("CREATE TABLE authentic_castles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0,
    is_spawned INTEGER DEFAULT 1
)");

$sqlite->exec("CREATE TABLE oases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    coord_x INTEGER DEFAULT 0,
    coord_y INTEGER DEFAULT 0
)");

// Insertion de données de test
$sqlite->exec("INSERT INTO game_settings (setting_key, setting_value, setting_type) VALUES ('world_radius', '35', 'int')");
$sqlite->exec("INSERT INTO users (id, username, is_bot) VALUES (1, 'Nezzar', 0), (2, 'TakedaBot', 1)");
$sqlite->exec("INSERT INTO planets (user_id, name, coord_x, coord_y) VALUES (1, 'Château Nezzar', 1, 1), (2, 'Fort Takeda', -12, 18), (NULL, 'Terre Libre', 15, -20)");
$sqlite->exec("INSERT INTO authentic_castles (name, coord_x, coord_y, is_spawned) VALUES ('Château de Himeji', -25, -20, 1)");
$sqlite->exec("INSERT INTO oases (name, coord_x, coord_y) VALUES ('Source Chaude de Kusatsu', 10, 15)");

GameConfig::clearCache();

// [TEST 1] Formule mathématique cartésienne du nombre total de tuiles
echo "[TEST 1] Validation de la formule géométrique (2R + 1)²...\n";
assertTest(GameConfig::getTotalTilesCount(15) === 961, "Rayon R=15 => Grille 31x31 => 961 tuiles");
assertTest(GameConfig::getTotalTilesCount(35) === 5041, "Rayon R=35 => Grille 71x71 => 5 041 tuiles (Standard Shogun)");
assertTest(GameConfig::getTotalTilesCount(50) === 10201, "Rayon R=50 => Grille 101x101 => 10 201 tuiles (Grand Monde)");
assertTest(GameConfig::getTotalTilesCount(75) === 22801, "Rayon R=75 => Grille 151x151 => 22 801 tuiles (Archipel Épique)");
assertTest(GameConfig::getTotalTilesCount(100) === 40401, "Rayon R=100 => Grille 201x201 => 40 401 tuiles (Mégamonde)");

// [TEST 2] Valeur par défaut et lecture du rayon
echo "\n[TEST 2] Lecture de la configuration du rayon via GameConfig...\n";
$initialRadius = GameConfig::getWorldRadius();
assertTest($initialRadius >= 10, "Le rayon initial est valide ($initialRadius >= 10)");
$expectedTotal = ($initialRadius * 2 + 1) * ($initialRadius * 2 + 1);
assertTest(GameConfig::getTotalTilesCount() === $expectedTotal, "getTotalTilesCount() sans paramètre utilise le rayon actif ($expectedTotal tuiles)");

// [TEST 3] Méthode GameStudioEngine::getWorldConfigData()
echo "\n[TEST 3] Extraction de la télémétrie spatiale via GameStudioEngine...\n";
$gameStudioEngine = new GameStudioEngine();
$worldData = $gameStudioEngine->getWorldConfigData();

assertTest(isset($worldData['worldRadius']), "worldRadius présent dans la télémétrie");
assertTest(isset($worldData['totalTiles']), "totalTiles présent dans la télémétrie");
assertTest(isset($worldData['gridDimension']), "gridDimension présent dans la télémétrie");
assertTest(isset($worldData['boundaryPerimeter']), "boundaryPerimeter présent dans la télémétrie");
assertTest(isset($worldData['presets']) && count($worldData['presets']) >= 5, "Au moins 5 préréglages recommandés disponibles");
assertTest(isset($worldData['humanVillages']), "humanVillages présent dans la télémétrie");
assertTest(isset($worldData['authenticCastles']), "authenticCastles présent dans la télémétrie");
assertTest(isset($worldData['biomesEstimate']), "biomesEstimate calculé");
assertTest(isset($worldData['quadrants']) && isset($worldData['quadrants']['NO']), "Quadrants tactiques calculés");

// [TEST 4] Règles de validation et sauvegarde du rayon
echo "\n[TEST 4] Contrôles de limites et sauvegarde...\n";
$resTooSmall = $gameStudioEngine->saveWorldRadius(5);
assertTest($resTooSmall['success'] === false, "Rejet d'un rayon trop petit (< 10)");

$resTooLarge = $gameStudioEngine->saveWorldRadius(250);
assertTest($resTooLarge['success'] === false, "Rejet d'un rayon trop grand (> 200)");

$testRadius = 45;
$resValid = $gameStudioEngine->saveWorldRadius($testRadius);
assertTest($resValid['success'] === true, "Sauvegarde réussie d'un nouveau rayon R=$testRadius");
assertTest(GameConfig::getWorldRadius() === $testRadius, "GameConfig::getWorldRadius() renvoie immédiatement la nouvelle valeur ($testRadius)");
assertTest(GameConfig::getTotalTilesCount() === 8281, "8 281 tuiles calculées pour R=45 (91x91)");

// Restaurer le rayon initial
$gameStudioEngine->saveWorldRadius($initialRadius);
assertTest(GameConfig::getWorldRadius() === $initialRadius, "Restauration du rayon d'origine ($initialRadius)");

// [TEST 5] Détection de sécurité anti-rétrécissement
echo "\n[TEST 5] Test de l'avertissement de sécurité si R < coordonnées maximales...\n";
$minRequired = $worldData['minRequiredRadius'];
if ($minRequired > 10) {
    $resWarn = $gameStudioEngine->saveWorldRadius(10);
    assertTest($resWarn['success'] === true && !empty($resWarn['warning']), "Avertissement généré si R=10 < coordonnée maximale ($minRequired)");
    $gameStudioEngine->saveWorldRadius($initialRadius);
} else {
    echo "  [INFO] Coordonnées maximales <= 10, avertissement non applicable directement.\n";
}

// [TEST 6] Rendu HTML de la vue partielle general_world_config.php
echo "\n[TEST 6] Test du rendu MVC de la vue Configuration du Monde...\n";
ob_start();
// Injecter les variables nécessaires
extract($gameStudioEngine->getWorldConfigData());
require __DIR__ . '/../views/gamestudio/general_world_config.php';
$html = ob_get_clean();

assertTest(strpos($html, '1. Dimension du Monde &amp; Nombre de Tuiles Générées') !== false, "Le 1er item 'Dimension du Monde & Nombre de Tuiles Générées' est bien présent");
assertTest(strpos($html, 'id="worldRadiusSlider"') !== false, "Slider interactif présent dans le DOM");
assertTest(strpos($html, 'id="worldRadiusInput"') !== false, "Input numérique de rayon présent");
assertTest(strpos($html, 'id="kpi-total-tiles"') !== false, "Élément KPI du total de tuiles présent");
assertTest(strpos($html, 'applyWorldPreset') !== false, "Fonction JS de préréglages 1-clic présente");
assertTest(strpos($html, 'Biomes Naturels Déterministes') !== false, "Section des biomes procéduraux présente");
assertTest(strpos($html, 'Découpage Tactique des 4 Quadrants') !== false, "Section des 4 quadrants tactiques présente");

echo "\n========================================================================\n";
echo " TOUS LES TESTS DE CONFIGURATION DU MONDE SONT AU VERT ! \n";
echo "========================================================================\n";
