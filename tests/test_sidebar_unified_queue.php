<?php
/**
 * Test de validation : Évolution du bloc "Activités & Chantiers Urbains" (Sidebar Est)
 * Vérifie l'intégration unifiée des 6 activités féodales :
 * 1. Chantiers de Bâtiments & Parcelles
 * 2. Troupes du Dojo (Barracks)
 * 3. Machines de Siège, Navires & Haras (Shipyard)
 * 4. Recherches Scientifiques de l'Académie
 * 5. Célébrations & Banquets au Donjon
 * 6. Ateliers d'Artisans & Raffinage
 */

require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';

function assertContainsStr(string $needle, string $haystack, string $msg): void {
    if (strpos($haystack, $needle) === false) {
        echo "❌ ÉCHEC: {$msg}\nAttendu: {$needle}\n";
        exit(1);
    }
    echo "✅ SUCCÈS: {$msg}\n";
}

echo "=== TEST 1 : Rendu de la file vide (Empty State Zen) ===\n";
ob_start();
$queue = [];
$barracksQueue = [];
$shipyardQueue = [];
$activeResearch = null;
$activeFeast = null;
$craftQueue = [];
$queueTitle = 'Activités & Chantiers Urbains';
$planet = ['id' => 1, 'faction' => 'terran', 'name' => 'Château d’Edo'];
$user = ['id' => 1, 'faction' => 'terran'];

include __DIR__ . '/../views/partials/urban_construction_queue.php';
$emptyHtml = ob_get_clean();

assertContainsStr('0 actif(s)', $emptyHtml, 'Affiche 0 actif(s) dans le badge d\'en-tête');
assertContainsStr('Aucune activité en cours sur ce fief', $emptyHtml, 'Affiche le message d\'état vide zen');
assertContainsStr('Bâtir', $emptyHtml, 'Présente le bouton rapide Bâtir');
assertContainsStr('Enrôler', $emptyHtml, 'Présente le bouton rapide Enrôler');
assertContainsStr('Engins Siège', $emptyHtml, 'Présente le bouton rapide Engins Siège');
assertContainsStr('Rechercher', $emptyHtml, 'Présente le bouton rapide Rechercher');

echo "\n=== TEST 2 : Rendu multi-activités consolidé (Troupes, Siège, Recherche, Fête, Artisanat, Bâtiments) ===\n";
$now = time();
$queue = [
    [
        'id' => 101,
        'planet_id' => 1,
        'build_category' => 'building',
        'target_id' => 'hq',
        'target_level' => 3,
        'is_demolition' => 0,
        'start_time' => date('Y-m-d H:i:s', $now - 60),
        'end_time' => date('Y-m-d H:i:s', $now + 300),
    ]
];
$barracksQueue = [
    [
        'id' => 201,
        'planet_id' => 1,
        'unit_type' => 'samurai_guards',
        'unit_name' => 'Gardes Samouraïs',
        'quantity' => 15,
        'completed_units' => 5,
        'start_time' => date('Y-m-d H:i:s', $now - 30),
        'end_time' => date('Y-m-d H:i:s', $now + 180),
        'duration' => 210,
    ]
];
$shipyardQueue = [
    [
        'id' => 301,
        'planet_id' => 1,
        'ship_type' => 'terran_cruiser',
        'ship_name' => 'Trébuchet Lourd Ozutsu',
        'quantity' => 2,
        'completed_units' => 0,
        'start_time' => date('Y-m-d H:i:s', $now - 20),
        'end_time' => date('Y-m-d H:i:s', $now + 600),
        'duration' => 620,
    ]
];
$activeResearch = [
    'id' => 401,
    'user_id' => 1,
    'tech_id' => 'energy_tech',
    'tech_name' => 'Tactique Bushido Avancée',
    'target_level' => 4,
    'start_time' => date('Y-m-d H:i:s', $now - 100),
    'end_time' => date('Y-m-d H:i:s', $now + 900),
    'duration' => 1000,
];
$activeFeast = [
    'feast_type' => 'great',
    'name' => 'Grand Banquet des Daimyōs',
    'start_time' => date('Y-m-d H:i:s', $now - 40),
    'end_time' => date('Y-m-d H:i:s', $now + 400),
    'culture_points' => 500,
];
$craftQueue = [
    [
        'id' => 501,
        'planet_id' => 1,
        'workshop_type' => 'grain_mill',
        'product' => 'rice_flour',
        'product_name' => 'Farine de Riz Purifiée',
        'quantity' => 100,
        'start_time' => date('Y-m-d H:i:s', $now - 10),
        'end_time' => date('Y-m-d H:i:s', $now + 120),
    ]
];

ob_start();
include __DIR__ . '/../views/partials/urban_construction_queue.php';
$fullHtml = ob_get_clean();

assertContainsStr('6 actif(s)', $fullHtml, 'Compteur total indique bien 6 actif(s)');
assertContainsStr('filterQueueSection(\'buildings\'', $fullHtml, 'Bouton filtre Bâtis présent');
assertContainsStr('filterQueueSection(\'troops\'', $fullHtml, 'Bouton filtre Dojo présent');
assertContainsStr('filterQueueSection(\'siege\'', $fullHtml, 'Bouton filtre Siège / Haras présent');
assertContainsStr('filterQueueSection(\'research\'', $fullHtml, 'Bouton filtre Savoirs présent');
assertContainsStr('filterQueueSection(\'feast\'', $fullHtml, 'Bouton filtre Festivités présent');
assertContainsStr('filterQueueSection(\'craft\'', $fullHtml, 'Bouton filtre Ateliers présent');

assertContainsStr('Tenshu (Donjon Castral)', $fullHtml, 'Affiche le nom du bâtiment en cours');
assertContainsStr('Gardes Samouraïs', $fullHtml, 'Affiche l\'unité du Dojo');
assertContainsStr('Trébuchet Lourd Ozutsu', $fullHtml, 'Affiche l\'engin de siège');
assertContainsStr('Engin(s) Siège', $fullHtml, 'Badge spécifique Engin(s) Siège présent');
assertContainsStr('Tactique Bushido Avancée', $fullHtml, 'Affiche la recherche de l\'Académie');
assertContainsStr('Grand Banquet des Daimyōs', $fullHtml, 'Affiche la fête du Tenshu');
assertContainsStr('Farine de Riz Purifiée', $fullHtml, 'Affiche le produit de l\'atelier');

echo "\n=== TOUS LES TESTS SONT VALIDES AVEC SUCCÈS ! ===\n";
