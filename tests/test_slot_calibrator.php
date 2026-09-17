<?php
/**
 * Test unitaire et fonctionnel : Outil de calibration Drag & Drop des positions des slots
 */
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== TEST : MOTEUR DE CALIBRATION DES EMPLACEMENTS (SLOT POSITIONS) ===\n\n";

// 1. Vérification des positions par défaut
echo "[1] Vérification des positions par défaut pour le terroir (resources)...\n";
$resPositions = SlotPositionEngine::getPositions('resources');
assert(isset($resPositions['1']), "Le slot 1 doit être défini dans resources.");
assert(isset($resPositions['18']), "Le slot 18 doit être défini dans resources.");
assert(isset($resPositions['bunker-hq']), "Le donjon central (bunker-hq) doit être défini dans resources.");
echo "✓ " . count($resPositions) . " emplacements validés pour 'resources'.\n";

echo "\n[2] Vérification des positions par défaut pour la cité (city)...\n";
$cityPositions = SlotPositionEngine::getPositions('city');
assert(isset($cityPositions['19']), "Le slot 19 doit être défini dans city.");
assert(isset($cityPositions['34']), "Le slot 34 doit être défini dans city.");
assert(isset($cityPositions['gateway']), "La porte du terroir (gateway) doit être définie dans city.");
echo "✓ " . count($cityPositions) . " emplacements validés pour 'city'.\n";

// 2. Vérification de la génération CSS
echo "\n[3] Vérification de la génération CSS dynamique...\n";
$cssRes = SlotPositionEngine::renderCss('resources');
assert(strpos($cssRes, '.hotspot-slot-1') !== false, "CSS resources doit contenir .hotspot-slot-1");
assert(strpos($cssRes, '.hotspot-bunker-hq') !== false, "CSS resources doit contenir .hotspot-bunker-hq");
echo "✓ Rendu CSS 'resources' conforme.\n";

$cssCity = SlotPositionEngine::renderCss('city');
assert(strpos($cssCity, '.hotspot-city-slot-19') !== false, "CSS city doit contenir .hotspot-city-slot-19");
assert(strpos($cssCity, '.hotspot-city-slot-gateway') !== false, "CSS city doit contenir .hotspot-city-slot-gateway");
echo "✓ Rendu CSS 'city' conforme.\n";

// 3. Test de sauvegarde de nouvelles coordonnées
echo "\n[4] Test de sauvegarde de coordonnées calibrées...\n";
$customCity = $cityPositions;
$customCity['19']['left'] = 54.2;
$customCity['19']['top'] = 3.5;

$saveOk = SlotPositionEngine::savePositions('city', $customCity);
assert($saveOk === true, "La sauvegarde doit retourner true.");

$reloaded = SlotPositionEngine::getPositions('city');
assert($reloaded['19']['left'] == 54.2, "La position left du slot 19 doit être 54.2");
assert($reloaded['19']['top'] == 3.5, "La position top du slot 19 doit être 3.5");
echo "✓ Sauvegarde et relecture persistante dans config/slot_positions.json validées.\n";

// 4. Test de réinitialisation
echo "\n[5] Test de réinitialisation aux valeurs d'origine...\n";
SlotPositionEngine::resetPositions('city');
$resetData = SlotPositionEngine::getPositions('city');
assert($resetData['19']['left'] == 52.5, "La position réinitialisée doit être 52.5");
assert($resetData['19']['top'] == 2.4, "La position réinitialisée doit être 2.4");
echo "✓ Réinitialisation conforme.\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS DU CALIBRATEUR SONT VALIDES !\n";
echo "============================================\n";
