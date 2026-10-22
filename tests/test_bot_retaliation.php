<?php
/**
 * Test Automatisé du Système de Riposte & Réciprocité des Bots IA
 * Teste :
 * 1. Création de la table `bot_retaliations`
 * 2. Réciprocité d'espionnage (Si on les espionne, ils nous espionnent)
 * 3. Contre-attaque proportionnée (Si on les attaque, ils renvoient une attaque proportionnée)
 * 4. Déclenchement automatique et passage en mission en vol
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BotEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';

$db = Database::getConnection();
$botEngine = new BotEngine();

echo "========================================================\n";
echo "⚔️ TEST DE RIPOSTE & RÉCIPROCITÉ DES CLANS IA (BOTS)\n";
echo "========================================================\n";

// 1. Initialiser la table
$botEngine->ensureRetaliationTable();
$tables = $db->query("SHOW TABLES LIKE 'bot_retaliations'")->fetchAll();
assert(!empty($tables), "La table bot_retaliations doit exister.");
echo "✓ Table bot_retaliations vérifiée avec succès !\n";

// 2. Trouver ou simuler un bot et un joueur humain
$bot = $db->query("SELECT * FROM users WHERE is_bot = 1 LIMIT 1")->fetch();
if (!$bot) {
    echo "Création d'un bot de test...\n";
    $botRes = $botEngine->createBot("BotRiposteTest_" . rand(100, 999), "terran");
    $bot = $db->query("SELECT * FROM users WHERE is_bot = 1 ORDER BY id DESC LIMIT 1")->fetch();
}
assert(!empty($bot), "Un bot doit exister pour les tests.");

$botPlanet = $db->query("SELECT * FROM planets WHERE user_id = {$bot['id']} LIMIT 1")->fetch();
assert(!empty($botPlanet), "Le bot doit posséder un fief.");

// Trouver un joueur humain
$player = $db->query("SELECT * FROM users WHERE is_bot = 0 AND (protection_until IS NULL OR protection_until <= NOW()) LIMIT 1")->fetch();
if (!$player) {
    $player = $db->query("SELECT * FROM users WHERE is_bot = 0 LIMIT 1")->fetch();
}
$playerPlanet = $db->query("SELECT * FROM planets WHERE user_id = {$player['id']} LIMIT 1")->fetch();

echo "Bot : {$bot['username']} [ID: {$bot['id']}] (Fief: {$botPlanet['name']})\n";
echo "Joueur : {$player['username']} [ID: {$player['id']}] (Fief: {$playerPlanet['name']})\n";

// 3. Test Réciprocité d'Espionnage : scheduleSpyRetaliation
$dummyMissionId = 999991;
$retaliationSpyId = $botEngine->scheduleSpyRetaliation(
    $bot,
    $botPlanet,
    $player,
    (int)$playerPlanet['id'],
    $dummyMissionId
);

assert($retaliationSpyId !== null && $retaliationSpyId > 0, "Le contre-espionnage doit être planifié.");
$spyRow = $db->query("SELECT * FROM bot_retaliations WHERE id = $retaliationSpyId")->fetch(PDO::FETCH_ASSOC);
assert($spyRow['retaliation_type'] === 'spy', "Le type de riposte doit être 'spy'.");
assert($spyRow['status'] === 'pending', "Le statut initial doit être 'pending'.");
$fleetPlanned = json_decode($spyRow['planned_fleet'], true);
assert(isset($fleetPlanned['spy_probe']) && $fleetPlanned['spy_probe'] >= 1, "La riposte doit inclure au moins 1 sonde Shinobi.");
echo "✓ Règle 1 validée : Le joueur a espionné le bot, le bot a planifié un contre-espionnage Shinobi (ID: $retaliationSpyId, sondes: {$fleetPlanned['spy_probe']}) !\n";

// 4. Test Contre-Attaque Proportionnée : scheduleAttackRetaliation
$playerAttackFleet = [
    'piquier_ashigaru_yari' => 30,
    'archer_yumi_monte' => 10
]; // Total = 40 guerriers envoyés par le joueur
$dummyAttackMissionId = 999992;

$retaliationAttackId = $botEngine->scheduleAttackRetaliation(
    $bot,
    $botPlanet,
    $player,
    (int)$playerPlanet['id'],
    'raid',
    $playerAttackFleet,
    $dummyAttackMissionId
);

assert($retaliationAttackId !== null && $retaliationAttackId > 0, "La contre-attaque doit être planifiée.");
$attackRow = $db->query("SELECT * FROM bot_retaliations WHERE id = $retaliationAttackId")->fetch(PDO::FETCH_ASSOC);
assert($attackRow['retaliation_type'] === 'raid', "Le type de riposte doit être 'raid'.");
assert($attackRow['trigger_fleet_count'] == 40, "Le nombre de troupes déclencheur doit être 40.");

$proportionalFleet = json_decode($attackRow['planned_fleet'], true);
$botTroopCount = array_sum($proportionalFleet);
echo "Troupes envoyées par le joueur : 40\n";
echo "Troupes proportionnées mobilisées par l'IA : $botTroopCount (" . json_encode($proportionalFleet) . ")\n";
assert($botTroopCount >= 34 && $botTroopCount <= 46, "L'armée de riposte doit être proportionnée (entre 34 et 46 soldats pour 40 envoyés).");
echo "✓ Règle 2 validée : Le joueur a attaqué avec 40 soldats, l'IA a mobilisé une armée proportionnée de $botTroopCount soldats !\n";

// 5. Test Exécution & Déclenchement de la Mission (processPendingRetaliations)
// Forcer l'échéance temporelle à maintenant pour simuler l'expiration du délai
$db->exec("UPDATE bot_retaliations SET scheduled_at = UNIX_TIMESTAMP() - 5 WHERE id IN ($retaliationSpyId, $retaliationAttackId)");

$processResult = $botEngine->processPendingRetaliations();
echo "Résultat de processPendingRetaliations : " . json_encode($processResult) . "\n";
assert($processResult['dispatched_total'] >= 2, "Les 2 ripostes doivent avoir été dépêchées.");

// Vérifier le statut dans bot_retaliations
$spyRowAfter = $db->query("SELECT * FROM bot_retaliations WHERE id = $retaliationSpyId")->fetch();
$attackRowAfter = $db->query("SELECT * FROM bot_retaliations WHERE id = $retaliationAttackId")->fetch();
assert($spyRowAfter['status'] === 'dispatched', "La riposte d'espionnage doit être 'dispatched'.");
assert($attackRowAfter['status'] === 'dispatched', "La contre-attaque doit être 'dispatched'.");

// Vérifier la présence dans fleet_missions
$missionsCount = (int)$db->query("
    SELECT COUNT(*) FROM fleet_missions 
    WHERE user_id = {$bot['id']} AND target_planet_id = {$playerPlanet['id']} AND status = 'en_route'
")->fetchColumn();
assert($missionsCount >= 2, "Au moins 2 missions de riposte du bot doivent être en vol vers le joueur.");
echo "✓ Règle 3 validée : Les flottes de riposte sont actuellement en vol vers le fief du joueur !\n";

// Nettoyage des tests
$db->exec("DELETE FROM fleet_missions WHERE user_id = {$bot['id']} AND target_planet_id = {$playerPlanet['id']} AND status = 'en_route'");
$db->exec("DELETE FROM bot_retaliations WHERE id IN ($retaliationSpyId, $retaliationAttackId)");

echo "\n========================================================\n";
echo "🎉 TOUS LES TESTS DE RIPOSTE DES IA SONT VALIDÉS AVEC SUCCÈS !\n";
echo "========================================================\n";
