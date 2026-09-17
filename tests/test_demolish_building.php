<?php
/**
 * Test unitaire et fonctionnel : Démolition / suppression de bâtiments et parcelles
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== TEST : FONCTION DE DÉMOLITION / SUPPRESSION DE BÂTIMENTS ===\n\n";

$db = Database::getConnection();
$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();

// Récupérer une planète de test
$stmt = $db->query("SELECT id, metal, crystal, deuterium FROM planets LIMIT 1");
$planet = $stmt->fetch();
if (!$planet) {
    die("Aucune planète trouvée pour le test.\n");
}
$planetId = (int)$planet['id'];

// 1. Protection du Donjon Tenshu (HQ)
echo "[1] Test de protection du Tenshu (HQ)...\n";
try {
    $buildingEngine->demolish($planetId, 'building', 'hq', 19);
    assert(false, "Le Tenshu ne doit pas pouvoir être démoli.");
} catch (Exception $e) {
    assert(strpos($e->getMessage(), 'Tenshu') !== false, "Message d'erreur attendu pour le Tenshu.");
    echo "✓ Protection du Tenshu validée : " . $e->getMessage() . "\n";
}

// 2. Création puis démolition d'un bâtiment urbain (ex: Marché / slot 24)
echo "\n[2] Test de démolition d'un bâtiment urbain (Marché slot 24)...\n";
// Assurer que le slot 24 a un marché niveau 2 pour le test
$db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND (building_type = 'market' OR slot = 24)")->execute([$planetId]);
$db->prepare("INSERT INTO planet_buildings (planet_id, slot, building_type, level) VALUES (?, 24, 'market', 2)")->execute([$planetId]);

$beforePlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();
$slotMapBefore = $planetEngine->getCitySlotMap($planetId);
assert($slotMapBefore[24]['code'] === 'market', "Slot 24 doit avoir le marché.");
assert($slotMapBefore[24]['level'] === 2, "Slot 24 doit être niveau 2.");

// Exécuter la démolition
$res = $buildingEngine->demolish($planetId, 'building', 'market', 24);
assert($res['success'] === true, "La démolition doit réussir.");
assert($res['refund']['metal'] > 0, "Un remboursement de bois/métal doit être accordé.");

// Vérifications post-démolition
$slotMapAfter = $planetEngine->getCitySlotMap($planetId);
assert($slotMapAfter[24]['code'] === 'free_plot', "Slot 24 doit être redevenu free_plot.");
assert($slotMapAfter[24]['level'] === 0, "Slot 24 doit être de niveau 0.");

$afterPlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();
assert($afterPlanet['metal'] > $beforePlanet['metal'], "Les greniers doivent avoir reçu le remboursement.");
echo "✓ Bâtiment urbain démoli avec succès. L'emplacement #24 est libéré (free_plot) et matériaux récupérés.\n";

// 3. Test de démolition d'une parcelle rurale (ex: slot 10)
echo "\n[3] Test de démolition d'une parcelle rurale (slot 10)...\n";
$db->prepare("DELETE FROM planet_fields WHERE planet_id = ? AND field_slot = 10")->execute([$planetId]);
$db->prepare("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (?, 10, 'crystal_mine', 3)")->execute([$planetId]);

$fieldRes = $buildingEngine->demolish($planetId, 'field', '10', 10);
assert($fieldRes['success'] === true, "La démolition de parcelle doit réussir.");

$stmtCheck = $db->prepare("SELECT level FROM planet_fields WHERE planet_id = ? AND field_slot = 10");
$stmtCheck->execute([$planetId]);
$fRow = $stmtCheck->fetch();
assert(!$fRow || (int)$fRow['level'] === 0, "La parcelle doit être supprimée ou de niveau 0.");
echo "✓ Parcelle rurale rasée avec succès. L'emplacement #10 est libre pour une nouvelle ressource.\n";

// 4. Test d'erreur sur bâtiment déjà vierge
echo "\n[4] Test de sécurité sur slot déjà vierge...\n";
try {
    $buildingEngine->demolish($planetId, 'building', 'market', 24);
    assert(false, "La démolition d'un bâtiment inexistant doit échouer.");
} catch (Exception $e) {
    echo "✓ Rejet conforme sur slot vide : " . $e->getMessage() . "\n";
}

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS DE DÉMOLITION SONT VALIDÉS !\n";
echo "============================================\n";
