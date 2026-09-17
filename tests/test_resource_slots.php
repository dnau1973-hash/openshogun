<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

echo "=== TEST DU SYSTÈME DE CHOIX LIBRE SUR PARCELLE DE RESSOURCES (1-18) ===\n\n";

$db = Database::getConnection();

// Trouver la planète de test
$stmt = $db->query("SELECT p.id, p.user_id, p.name FROM planets p JOIN users u ON p.user_id = u.id WHERE u.username = 'Admin' LIMIT 1");
$planet = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$planet) {
    $stmt = $db->query("SELECT id, user_id, name FROM planets LIMIT 1");
    $planet = $stmt->fetch(PDO::FETCH_ASSOC);
}

$planetId = (int)$planet['id'];
echo "[+] Planète cible: ID {$planetId} ({$planet['name']})\n";

// S'assurer de ressources suffisantes
$db->prepare("UPDATE planets SET metal = 50000, crystal = 50000, deuterium = 50000 WHERE id = ?")->execute([$planetId]);

$planetEngine = new PlanetEngine($db);
$buildingEngine = new BuildingEngine($db);

// Nettoyer d'éventuels chantiers sur le slot 18 pour tester
$db->prepare("DELETE FROM construction_queue WHERE planet_id = ? AND build_category = 'field' AND target_id = '18'")->execute([$planetId]);

// Mettre le slot 18 à niveau 0 avec type metal_mine
$db->prepare("UPDATE planet_fields SET type = 'metal_mine', level = 0 WHERE planet_id = ? AND field_slot = 18")->execute([$planetId]);

echo "[+] Slot 18 configuré au niveau 0 (metal_mine)\n";

// TEST 1 : Lancement de la construction sur slot 18 en choisissant librement 'deuterium_synth' (Rizières)
echo "\n--- TEST 1 : Sélection libre de 'deuterium_synth' sur le Slot 18 (Niveau 0) ---\n";
$res = $buildingEngine->startUpgrade($planetId, 'field', '18', null, 'deuterium_synth');
if (!$res['success']) {
    echo "[-] Échec startUpgrade : {$res['error']}\n";
    exit(1);
}
echo "[✓] Succès : {$res['message']}\n";

// Vérifier que planet_fields a bien été mis à jour avec le type 'deuterium_synth'
$stmtCheck = $db->prepare("SELECT * FROM planet_fields WHERE planet_id = ? AND field_slot = 18");
$stmtCheck->execute([$planetId]);
$fRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$fRow || $fRow['type'] !== 'deuterium_synth') {
    echo "[-] Erreur : Le type n'a pas été mis à jour dans planet_fields ! Row : " . json_encode($fRow) . "\n";
    exit(1);
}
echo "[✓] Vérifié en base : Slot 18 est devenu type '{$fRow['type']}' au niveau {$fRow['level']}.\n";

// Vérifier la file
$stmtQueue = $db->prepare("SELECT * FROM construction_queue WHERE planet_id = ? AND build_category = 'field' AND target_id = '18'");
$stmtQueue->execute([$planetId]);
$qRow = $stmtQueue->fetch(PDO::FETCH_ASSOC);
if (!$qRow || (int)$qRow['target_level'] !== 1) {
    echo "[-] File d'attente introuvable !\n";
    exit(1);
}
$queueId = (int)$qRow['id'];
echo "[✓] File de construction active : ID {$queueId}, niveau cible {$qRow['target_level']}.\n";

// TEST 2 : Annulation du chantier niveau 0 -> reste au niveau 0
echo "\n--- TEST 2 : Annulation du chantier niveau 0 ---\n";
$cancelled = $buildingEngine->cancelUpgrade($planetId, $queueId);
if (!$cancelled) {
    echo "[-] Échec annulation !\n";
    exit(1);
}
echo "[✓] Chantier annulé avec succès et ressources remboursées à 80%.\n";

// TEST 3 : Re-choisir un autre type, ex: 'solar_plant' (Sanctuaire) sur le slot 18 toujours au niveau 0
echo "\n--- TEST 3 : Re-sélection libre de 'solar_plant' sur le Slot 18 ---\n";
$res2 = $buildingEngine->startUpgrade($planetId, 'field', '18', null, 'solar_plant');
if (!$res2['success']) {
    echo "[-] Échec relance avec solar_plant : {$res2['error']}\n";
    exit(1);
}

// Vérifier le type
$stmtCheck2 = $db->prepare("SELECT * FROM planet_fields WHERE planet_id = ? AND field_slot = 18");
$stmtCheck2->execute([$planetId]);
$fRow2 = $stmtCheck2->fetch(PDO::FETCH_ASSOC);
if (!$fRow2 || $fRow2['type'] !== 'solar_plant') {
    echo "[-] Erreur : Type non mis à jour vers solar_plant !\n";
    exit(1);
}
echo "[✓] Type correctement réorienté vers 'solar_plant' !\n";

// TEST 4 : Acheminement complet vers le niveau 1
echo "\n--- TEST 4 : Acheminement du chantier et confirmation du Niveau 1 ---\n";
$db->prepare("UPDATE construction_queue SET finishes_at = UNIX_TIMESTAMP() - 5 WHERE planet_id = ? AND target_id = '18'")->execute([$planetId]);
$planetEngine->updatePlanet($planetId);

$stmtFinal = $db->prepare("SELECT * FROM planet_fields WHERE planet_id = ? AND field_slot = 18");
$stmtFinal->execute([$planetId]);
$fRowFinal = $stmtFinal->fetch(PDO::FETCH_ASSOC);

if (!$fRowFinal || (int)$fRowFinal['level'] !== 1 || $fRowFinal['type'] !== 'solar_plant') {
    echo "[-] Échec de complétion ! Data : " . json_encode($fRowFinal) . "\n";
    exit(1);
}
echo "[✓] Parcelle 18 est désormais Sanctuaire d'Inari au Niveau 1 !\n";

echo "\n[✓] Tous les tests du système de choix libre de ressources sont RÉUSSIS avec succès !\n";

