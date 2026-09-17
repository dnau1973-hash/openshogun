<?php
/**
 * Test unitaire et fonctionnel : Démolition avec compte à rebours et libération différée
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== TEST : DÉMOLITION AVEC COMPTE À REBOURS ET LIBÉRATION DIFFÉRÉE ===\n\n";

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

// Nettoyer la file de construction pour la planète de test
$db->prepare("DELETE FROM construction_queue WHERE planet_id = ?")->execute([$planetId]);

// 1. Protection du Donjon Tenshu (HQ)
echo "[1] Test de protection du Tenshu (HQ)...\n";
try {
    $buildingEngine->demolish($planetId, 'building', 'hq', 19);
    assert(false, "Le Tenshu ne doit pas pouvoir être démoli.");
} catch (Exception $e) {
    assert(strpos($e->getMessage(), 'Tenshu') !== false, "Message d'erreur attendu pour le Tenshu.");
    echo "✓ Protection du Tenshu validée : " . $e->getMessage() . "\n";
}

// 2. Lancement de la démolition d'un bâtiment urbain (Marché slot 24)
echo "\n[2] Test du compte à rebours et non-suppression immédiate (Marché slot 24)...\n";
$db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND (building_type = 'market' OR slot = 24)")->execute([$planetId]);
$db->prepare("INSERT INTO planet_buildings (planet_id, slot, building_type, level) VALUES (?, 24, 'market', 2)")->execute([$planetId]);

$beforePlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();

// Lancer la démolition
$res = $buildingEngine->demolish($planetId, 'building', 'market', 24);
assert($res['success'] === true, "L'ordre de démolition doit réussir.");
assert($res['target_level'] === 0, "Le niveau cible doit être 0.");
assert($res['duration'] > 0, "La durée de démolition doit être > 0.");

// Vérifier que la file contient bien l'ordre avec target_level = 0
$qStmt = $db->prepare("SELECT * FROM construction_queue WHERE planet_id = ? AND target_id = 'market' AND target_level = 0");
$qStmt->execute([$planetId]);
$qItem = $qStmt->fetch();
assert($qItem !== false, "Un job de démolition (target_level = 0) doit exister dans construction_queue.");
$queueId = (int)$qItem['id'];

// VÉRIFICATION CLÉ : Le bâtiment N'EST PAS encore supprimé et le slot N'EST PAS libéré !
$slotMapDuring = $planetEngine->getCitySlotMap($planetId);
assert($slotMapDuring[24]['code'] === 'market', "Le bâtiment doit TOUJOURS être présent pendant le compte à rebours.");
assert($slotMapDuring[24]['level'] === 2, "Le niveau doit toujours être 2 pendant la démolition.");
echo "✓ Ordre de démolition en cours : le bâtiment est préservé sur son slot pendant le compte à rebours.\n";

// 3. Test d'annulation de l'ordre de démolition
echo "\n[3] Test d'interruption / annulation de la démolition...\n";
$cancelled = $buildingEngine->cancelUpgrade($planetId, $queueId);
assert($cancelled === true, "cancelUpgrade doit renvoyer true.");

$qCheck = $db->query("SELECT COUNT(*) FROM construction_queue WHERE id = $queueId")->fetchColumn();
assert($qCheck == 0, "Le job de démolition doit être supprimé de la file après annulation.");

$slotMapAfterCancel = $planetEngine->getCitySlotMap($planetId);
assert($slotMapAfterCancel[24]['code'] === 'market', "Le bâtiment doit être intact après annulation.");
assert($slotMapAfterCancel[24]['level'] === 2, "Le niveau doit être préservé après annulation.");
echo "✓ Annulation réussie : le bâtiment est resté intact, le chantier est clos.\n";

// 4. Relancer la démolition et simuler l'expiration du compte à rebours
echo "\n[4] Test d'achèvement de la démolition (fin de chrono & libération effective)...\n";
$res2 = $buildingEngine->demolish($planetId, 'building', 'market', 24);
assert($res2['success'] === true);

// Avancer le temps de fin dans le passé pour simuler la fin des travaux
$db->prepare("UPDATE construction_queue SET finishes_at = UNIX_TIMESTAMP() - 10 WHERE planet_id = ? AND target_id = 'market'")->execute([$planetId]);

// Traiter la file de construction
$planetEngine->processConstructionQueue($planetId);

// Vérifier que le bâtiment est maintenant supprimé et le slot libéré
$slotMapFinished = $planetEngine->getCitySlotMap($planetId);
assert($slotMapFinished[24]['code'] === 'free_plot', "Slot 24 doit être redevenu free_plot après achèvement.");
assert($slotMapFinished[24]['level'] === 0, "Slot 24 doit avoir le niveau 0.");

$afterPlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();
assert($afterPlanet['metal'] > $beforePlanet['metal'], "Les matériaux (30% de remboursement) doivent être crédités.");
echo "✓ Compte à rebours achevé : le bâtiment a été raser, l'emplacement #24 est libéré et le remboursement 30% a été versé.\n";

// 5. Test de démolition d'une parcelle rurale (slot 10)
echo "\n[5] Test de démolition avec compte à rebours sur parcelle rurale (slot 10)...\n";
$db->prepare("DELETE FROM planet_fields WHERE planet_id = ? AND field_slot = 10")->execute([$planetId]);
$db->prepare("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (?, 10, 'crystal_mine', 3)")->execute([$planetId]);

$beforeFieldPlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();

$fieldRes = $buildingEngine->demolish($planetId, 'field', '10', 10);
assert($fieldRes['success'] === true, "La démolition de parcelle doit être initiée.");
assert($fieldRes['target_level'] === 0, "target_level doit être 0.");

// Vérifier que la parcelle rurale a toujours son niveau pendant les travaux
$fStmt = $db->prepare("SELECT level FROM planet_fields WHERE planet_id = ? AND field_slot = 10");
$fStmt->execute([$planetId]);
$fLvlDuring = (int)$fStmt->fetchColumn();
assert($fLvlDuring === 3, "La parcelle doit rester de niveau 3 pendant les travaux de démolition.");

// Avancer le temps de fin dans le passé
$db->prepare("UPDATE construction_queue SET finishes_at = UNIX_TIMESTAMP() - 10 WHERE planet_id = ? AND target_id = '10'")->execute([$planetId]);

// Traiter la file
$planetEngine->processConstructionQueue($planetId);

// Vérifier que la parcelle rurale est passée au niveau 0 (la ligne existe toujours !)
$fStmt->execute([$planetId]);
$fLvlAfter = (int)$fStmt->fetchColumn();
assert($fLvlAfter === 0, "La parcelle rurale doit être passée au niveau 0 (terrain vierge).");

$afterFieldPlanet = $db->query("SELECT metal, crystal, deuterium FROM planets WHERE id = $planetId")->fetch();
assert($afterFieldPlanet['crystal'] > $beforeFieldPlanet['crystal'], "Les matériaux de la parcelle doivent être remboursés.");
echo "✓ Parcelle rurale démantelée après compte à rebours : niveau remis à 0, slot disponible, remboursement versé.\n";

// 6. Test de sécurité sur slot déjà vierge
echo "\n[6] Test de sécurité sur slot déjà vierge...\n";
try {
    $buildingEngine->demolish($planetId, 'building', 'market', 24);
    assert(false, "La démolition d'un bâtiment déjà inexistant doit échouer.");
} catch (Exception $e) {
    echo "✓ Rejet conforme sur slot vide : " . $e->getMessage() . "\n";
}

echo "\n=========================================================\n";
echo "🎉 TOUS LES TESTS DE DÉMOLITION AVEC CHRONO SONT VALIDÉS !\n";
echo "=========================================================\n";
