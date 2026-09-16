<?php
/**
 * Test unitaire : Tuiles de terrain procédurales et sélection des 4 quadrants
 */
if (!defined('SPEED_FACTOR')) {
    define('SPEED_FACTOR', 5);
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/Auth.php';

echo "=== TEST DU SYSTÈME DE TERRAINS & DES 4 QUADRANTS ===\n";

// 1. Vérification des fichiers d'images de tuiles
$requiredTiles = [
    'tile_plains.jpg',
    'tile_forest.jpg',
    'tile_mountain.jpg',
    'tile_lake.jpg',
    'tile_hills.jpg',
    'tile_village.jpg',
    'tile_authentic_castle.jpg'
];

echo "--- 1. Vérification des assets de tuiles (public/assets/map/) ---\n";
foreach ($requiredTiles as $t) {
    $path = __DIR__ . '/../public/assets/map/' . $t;
    if (file_exists($path) && filesize($path) > 1000) {
        echo "  ✓ Tuile [{$t}] présente (" . filesize($path) . " octets)\n";
    } else {
        echo "  ✗ ERREUR : Tuile manquante ou vide : {$path}\n";
        exit(1);
    }
}

// 2. Vérification de la génération de terrain procédurale
echo "--- 2. Vérification de la génération procédurale du terrain (GalaxyEngine) ---\n";
$t1 = GalaxyEngine::getTerrainType(5, 12);
$t2 = GalaxyEngine::getTerrainType(5, 12);
if ($t1['type'] === $t2['type'] && !empty($t1['img'])) {
    echo "  ✓ Hachage spatial déterministe validé pour (5, 12) : {$t1['name']} ({$t1['type']})\n";
} else {
    echo "  ✗ ERREUR : Incohérence déterministe dans getTerrainType\n";
    exit(1);
}

// Distribution statistique
$counts = ['plains' => 0, 'forest' => 0, 'mountain' => 0, 'hills' => 0, 'lake' => 0];
for ($i = -50; $i <= 50; $i++) {
    for ($j = -50; $j <= 50; $j++) {
        $t = GalaxyEngine::getTerrainType($i, $j);
        $counts[$t['type']] = ($counts[$t['type']] ?? 0) + 1;
    }
}
echo "  ✓ Distribution observée sur 10 000 cases : Plaines: {$counts['plains']}, Forêts: {$counts['forest']}, Montagnes: {$counts['mountain']}, Collines: {$counts['hills']}, Lacs: {$counts['lake']}\n";

// 3. Vérification des 4 quadrants
echo "--- 3. Vérification des Quadrants géographiques ---\n";
$qNO = GalaxyEngine::getQuadrant(-10, 15);
$qNE = GalaxyEngine::getQuadrant(10, 15);
$qSO = GalaxyEngine::getQuadrant(-10, -15);
$qSE = GalaxyEngine::getQuadrant(10, -15);
$qKyoto = GalaxyEngine::getQuadrant(0, 0);

if ($qNO['code'] === 'NO' && $qNE['code'] === 'NE' && $qSO['code'] === 'SO' && $qSE['code'] === 'SE' && $qKyoto['code'] === 'KYOTO') {
    echo "  ✓ Classification parfaite des 4 quadrants et du Centre (Kyoto)\n";
} else {
    echo "  ✗ ERREUR : Erreur de classification des quadrants\n";
    exit(1);
}

// 4. Test du générateur de coordonnées libres par zone
echo "--- 4. Vérification du placement par zone (VillageFieldGenerator) ---\n";
$db = Database::getConnection();

$cNO = VillageFieldGenerator::findRandomFreeCoordinates($db, 35, 'nord_ouest');
if ($cNO['x'] < 0 && $cNO['y'] > 0) {
    echo "  ✓ Placement Nord-Ouest respecté : [{$cNO['x']} : {$cNO['y']}]\n";
} else {
    echo "  ✗ ERREUR : Mauvais quadrant pour Nord-Ouest : [{$cNO['x']} : {$cNO['y']}]\n";
    exit(1);
}

$cNE = VillageFieldGenerator::findRandomFreeCoordinates($db, 35, 'nord_est');
if ($cNE['x'] > 0 && $cNE['y'] > 0) {
    echo "  ✓ Placement Nord-Est respecté : [{$cNE['x']} : {$cNE['y']}]\n";
} else {
    echo "  ✗ ERREUR : Mauvais quadrant pour Nord-Est : [{$cNE['x']} : {$cNE['y']}]\n";
    exit(1);
}

$cSO = VillageFieldGenerator::findRandomFreeCoordinates($db, 35, 'sud_ouest');
if ($cSO['x'] < 0 && $cSO['y'] < 0) {
    echo "  ✓ Placement Sud-Ouest respecté : [{$cSO['x']} : {$cSO['y']}]\n";
} else {
    echo "  ✗ ERREUR : Mauvais quadrant pour Sud-Ouest : [{$cSO['x']} : {$cSO['y']}]\n";
    exit(1);
}

$cSE = VillageFieldGenerator::findRandomFreeCoordinates($db, 35, 'sud_est');
if ($cSE['x'] > 0 && $cSE['y'] < 0) {
    echo "  ✓ Placement Sud-Est respecté : [{$cSE['x']} : {$cSE['y']}]\n";
} else {
    echo "  ✗ ERREUR : Mauvais quadrant pour Sud-Est : [{$cSE['x']} : {$cSE['y']}]\n";
    exit(1);
}

// 5. Test de l'API /api/galaxy.php avec terrains et quadrant
echo "--- 5. Vérification du retour API avec terrains et quadrant ---\n";
$galaxyEngine = new GalaxyEngine();
$sectorData = $galaxyEngine->getSectorMap(0, 0, 4);

if (isset($sectorData['quadrant']) && isset($sectorData['terrains']) && count($sectorData['terrains']) > 50) {
    echo "  ✓ Secteur (0,0, r=4) contient " . count($sectorData['terrains']) . " tuiles avec métadonnées paysagères.\n";
    echo "  ✓ Quadrant du centre : {$sectorData['quadrant']['name']} ({$sectorData['quadrant']['code']})\n";
} else {
    echo "  ✗ ERREUR : Métadonnées de terrain ou quadrant manquantes dans sectorData\n";
    exit(1);
}

echo "=== TOUS LES TESTS DE TERRAINS & QUADRANTS SONT VALIDES ! ===\n";
