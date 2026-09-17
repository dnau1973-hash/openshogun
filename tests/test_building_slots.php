<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

echo "=== TEST DE CONSTRUCTION LIBRE SUR N'IMPORTE QUEL EMPLACEMENT / SILO (19-34) ===\n\n";

$db = Database::getConnection();

// 1. Trouver une planète de test (ou l'Admin)
$stmt = $db->query("SELECT p.id, p.user_id, p.name FROM planets p JOIN users u ON p.user_id = u.id WHERE u.username = 'Admin' LIMIT 1");
$planet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$planet) {
    // Prendre la première planète existante
    $stmt = $db->query("SELECT id, user_id, name FROM planets LIMIT 1");
    $planet = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$planet) {
    echo "[-] Aucune planète trouvée pour le test.\n";
    exit(1);
}

$planetId = (int)$planet['id'];
echo "[+] Planète cible: ID {$planetId} ({$planet['name']})\n";

// Donnons beaucoup de ressources pour le test
$db->prepare("UPDATE planets SET metal = 50000, crystal = 50000, deuterium = 50000, energy_used = 0, energy_max = 5000 WHERE id = ?")->execute([$planetId]);

$planetEngine = new PlanetEngine($db);
$buildingEngine = new BuildingEngine($db);

// Récupérer le plan des emplacements
$slotMap = $planetEngine->getCitySlotMap($planetId);
echo "[+] Emplacements de la ville (19 à 34) :\n";
$freeSlots = [];
foreach ($slotMap as $s => $data) {
    $bName = BUILDINGS[$data['code']]['name'] ?? ($data['code'] === 'free_plot' ? 'Terrain Libre' : $data['code']);
    if ($data['code'] === 'free_plot') {
        $freeSlots[] = $s;
        echo "   - Slot {$s}: LIBRE [{$bName}]\n";
    } else {
        echo "   - Slot {$s}: {$bName} (Niveau {$data['level']})\n";
    }
}

if (empty($freeSlots)) {
    echo "[-] Aucun slot libre pour tester.\n";
    exit(1);
}

$chosenSlot = $freeSlots[0];
echo "\n[+] Choix du slot libre pour le test : Slot {$chosenSlot}\n";

// Trouver un bâtiment non encore construit sur cette planète
$stmtBuilt = $db->prepare("SELECT building_type FROM planet_buildings WHERE planet_id = ?");
$stmtBuilt->execute([$planetId]);
$alreadyBuilt = $stmtBuilt->fetchAll(PDO::FETCH_COLUMN);

$candidateBuilding = null;
foreach (BUILDINGS as $code => $cfg) {
    if (!in_array($code, $alreadyBuilt, true)) {
        $candidateBuilding = $code;
        break;
    }
}

if (!$candidateBuilding) {
    echo "[-] Tous les bâtiments sont déjà construits sur cette planète.\n";
    exit(1);
}

echo "[+] Bâtiment candidat à construire : {$candidateBuilding} (" . BUILDINGS[$candidateBuilding]['name'] . ")\n";

// Nettoyer toute queue restante pour ce bâtiment au cas où
$db->prepare("DELETE FROM construction_queue WHERE planet_id = ? AND target_id = ?")->execute([$planetId, $candidateBuilding]);
$db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND building_type = ?")->execute([$planetId, $candidateBuilding]);

// TEST 1 : Lancer la construction de niveau 1 sur $chosenSlot
echo "\n--- TEST 1 : Lancement de la construction sur le Slot {$chosenSlot} ---\n";
$res = $buildingEngine->startUpgrade($planetId, 'building', $candidateBuilding, $chosenSlot);
if (!$res['success']) {
    echo "[-] Erreur lors du lancement de la construction : {$res['error']}\n";
    exit(1);
}
echo "[✓] Succès : {$res['message']}\n";

// Vérifier dans planet_buildings que la ligne est créée à niveau 0 avec le slot
$stmtCheck = $db->prepare("SELECT * FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
$stmtCheck->execute([$planetId, $candidateBuilding]);
$row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['slot'] !== $chosenSlot || (int)$row['level'] !== 0) {
    echo "[-] Échec de la vérification dans planet_buildings ! Row : " . json_encode($row) . "\n";
    exit(1);
}
echo "[✓] Vérifié dans la DB : Bâtiment {$candidateBuilding} réservé sur slot {$row['slot']} au niveau {$row['level']}.\n";

// Vérifier la queue
$stmtQueue = $db->prepare("SELECT * FROM construction_queue WHERE planet_id = ? AND target_id = ?");
$stmtQueue->execute([$planetId, $candidateBuilding]);
$qRow = $stmtQueue->fetch(PDO::FETCH_ASSOC);

if (!$qRow || (int)$qRow['target_level'] !== 1) {
    echo "[-] File de construction invalide !\n";
    exit(1);
}
$queueId = (int)$qRow['id'];
echo "[✓] File de construction active : ID {$queueId}, niveau cible {$qRow['target_level']}.\n";

// TEST 2 : Tenter de construire un AUTRE bâtiment sur ce même slot déjà occupé
echo "\n--- TEST 2 : Conflit de slot (tenter d'occuper le même slot) ---\n";
// Trouver un 2ème bâtiment non construit
$secondCandidate = null;
foreach (BUILDINGS as $code => $cfg) {
    if (!in_array($code, $alreadyBuilt, true) && $code !== $candidateBuilding) {
        $secondCandidate = $code;
        break;
    }
}
if ($secondCandidate) {
    try {
        $resConflict = $buildingEngine->startUpgrade($planetId, 'building', $secondCandidate, $chosenSlot);
        echo "[-] ERREUR : Le slot {$chosenSlot} a pu être réassigné alors qu'il est déjà pris !\n";
        exit(1);
    } catch (Exception $e) {
        echo "[✓] Conflit géré correctement : '{$e->getMessage()}'\n";
    }
}

// TEST 3 : Annulation de la construction au niveau 0 -> le slot doit être libéré
echo "\n--- TEST 3 : Annulation du chantier niveau 0 (libération du slot) ---\n";
$cancelled = $buildingEngine->cancelUpgrade($planetId, $queueId);
if (!$cancelled) {
    echo "[-] Échec de l'annulation du chantier !\n";
    exit(1);
}

$stmtCheckAfterCancel = $db->prepare("SELECT * FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
$stmtCheckAfterCancel->execute([$planetId, $candidateBuilding]);
$rowAfterCancel = $stmtCheckAfterCancel->fetch(PDO::FETCH_ASSOC);

if ($rowAfterCancel !== false) {
    echo "[-] La ligne de planet_buildings n'a pas été supprimée après annulation !\n";
    exit(1);
}
echo "[✓] Le slot {$chosenSlot} a bien été libéré après annulation du chantier niveau 0.\n";

// TEST 4 : Re-lancer la construction, simuler la fin et vérifier l'élévation au niveau 1
echo "\n--- TEST 4 : Acheminement complet du chantier (élévation au niveau 1) ---\n";
$res2 = $buildingEngine->startUpgrade($planetId, 'building', $candidateBuilding, $chosenSlot);
if (!$res2['success']) {
    echo "[-] Erreur relance construction : {$res2['error']}\n";
    exit(1);
}

// Mettre finishes_at dans le passé pour simuler la fin immédiate
$db->prepare("UPDATE construction_queue SET finishes_at = UNIX_TIMESTAMP() - 10 WHERE planet_id = ? AND target_id = ?")->execute([$planetId, $candidateBuilding]);

// Appeler updatePlanet pour consommer la file
$planetEngine->updatePlanet($planetId);

// Vérifier que le bâtiment est maintenant niveau 1 sur le slot $chosenSlot
$stmtFinal = $db->prepare("SELECT * FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
$stmtFinal->execute([$planetId, $candidateBuilding]);
$finalRow = $stmtFinal->fetch(PDO::FETCH_ASSOC);

if (!$finalRow || (int)$finalRow['level'] !== 1 || (int)$finalRow['slot'] !== $chosenSlot) {
    echo "[-] Échec de finalisation du bâtiment ! Données : " . json_encode($finalRow) . "\n";
    exit(1);
}
echo "[✓] Chantier achevé : {$candidateBuilding} est au Niveau 1 sur le Slot {$finalRow['slot']} !\n";

// Nettoyage après test
$db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND building_type = ?")->execute([$planetId, $candidateBuilding]);
echo "\n[✓] Nettoyage effectué. Tous les tests de slot libre sont validés avec SUCCÈS !\n";
