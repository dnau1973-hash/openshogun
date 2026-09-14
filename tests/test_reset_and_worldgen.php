<?php
/**
 * Test Automatisé : Générateur de Monde et Réinitialisation de l'Univers
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/WorldGenerator.php';
require_once __DIR__ . '/../core/GameConfig.php';

echo "========================================================\n";
echo " TEST : GÉNÉRATEUR DE MONDE & RESET DE L'UNIVERS \n";
echo "========================================================\n\n";

$db = Database::getConnection();
$worldGen = new WorldGenerator();

// --- TEST 1 : Générateur de Monde Procédural ---
echo "--- TEST 1 : Générateur de Monde Procédural ---\n";
$genRes = $worldGen->generatePlanets(8, 12, ['terrestrial', 'oceanic', 'volcanic', 'arctic'], false);
assert($genRes['success'] === true, "Erreur lors de la génération du monde.");
echo "- Planètes générées : {$genRes['generated_count']} / 8\n";
assert($genRes['generated_count'] === 8, "Erreur: le nombre de planètes générées ne correspond pas.");

foreach ($genRes['planets'] as $p) {
    echo "  -> {$p['name']} {$p['coords']} [Type: {$p['type']}] ({$p['resources']})\n";
}
echo "✔ Test 1 réussi : Génération procédurale validée.\n\n";


// --- TEST 2 : Réinitialisation Complète de l'Univers (Full Reset) ---
echo "--- TEST 2 : Réinitialisation Complète (Reset) ---\n";
$resetRes = $worldGen->resetUniverse('Gabriel125#', 10, true);
assert($resetRes['success'] === true, "Erreur lors du reset: " . ($resetRes['error'] ?? ''));

echo "- Message de retour : {$resetRes['message']}\n";
echo "- Planètes neutres initiales : {$resetRes['neutral_planets']}\n";
echo "- Bots initiaux déployés : {$resetRes['bots_deployed']}\n";

// 1. Vérifier le compte nezzar
$nezzar = $db->query("SELECT * FROM users WHERE username = 'nezzar'")->fetch();
assert(!empty($nezzar), "Erreur: Le compte 'nezzar' n'a pas été trouvé en base.");
echo "- Compte 'nezzar' trouvé (ID: {$nezzar['id']}, Email: {$nezzar['email']})\n";

// 2. Vérifier les privilèges admin
assert((int)$nezzar['is_admin'] === 1, "Erreur: Le compte 'nezzar' doit être Administrateur (is_admin = 1).");
assert((int)$nezzar['is_bot'] === 0, "Erreur: Le compte 'nezzar' ne doit pas être un bot.");
echo "- Statut Administrateur validé (is_admin = 1)\n";

// 3. Vérifier le mot de passe Gabriel125#
$passCheck = password_verify('Gabriel125#', $nezzar['password_hash']);
assert($passCheck === true, "Erreur: Le mot de passe 'Gabriel125#' ne correspond pas au hash stocké.");
echo "- Mot de passe 'Gabriel125#' vérifié avec succès.\n";

// 4. Vérifier la planète capitale
$capPlanet = $db->query("SELECT * FROM planets WHERE user_id = {$nezzar['id']} AND is_capital = 1")->fetch();
assert(!empty($capPlanet), "Erreur: Planète capitale de nezzar introuvable.");
echo "- Planète Capitale : '{$capPlanet['name']}' aux coordonnées [{$capPlanet['coord_x']} : {$capPlanet['coord_y']}]\n";
assert((int)$capPlanet['coord_x'] === 1 && (int)$capPlanet['coord_y'] === 1, "Erreur: Coordonnées attendues [1:1]");

// 5. Vérifier les 18 parcelles de la capitale
$fieldsCount = (int)$db->query("SELECT COUNT(*) FROM planet_fields WHERE planet_id = {$capPlanet['id']}")->fetchColumn();
assert($fieldsCount === 18, "Erreur: La planète capitale doit avoir exactement 18 parcelles (trouvé {$fieldsCount}).");
echo "- 18 Parcelles de ressources vérifiées.\n";

// 6. Vérifier les troupes et vaisseaux
$unitsCount = (int)$db->query("SELECT SUM(count) FROM planet_units WHERE planet_id = {$capPlanet['id']}")->fetchColumn();
assert($unitsCount >= 40, "Erreur: Garnison de nezzar insuffisante.");
echo "- Garnison de défense de l'Admin : {$unitsCount} soldats.\n";

$shipsCount = (int)$db->query("SELECT SUM(count) FROM planet_ships WHERE planet_id = {$capPlanet['id']}")->fetchColumn();
assert($shipsCount >= 5, "Erreur: Flotte de nezzar insuffisante.");
echo "- Flotte de départ de l'Admin : {$shipsCount} vaisseaux.\n";

// 7. Test de connexion réel via Auth::login
$auth = new Auth();
$loginRes = $auth->login('nezzar', 'Gabriel125#');
assert($loginRes['success'] === true, "Erreur: Connexion avec 'nezzar' / 'Gabriel125#' a échoué : " . ($loginRes['error'] ?? ''));
echo "- Connexion authentifiée via Auth::login('nezzar', 'Gabriel125#') : SUCCÈS !\n";

echo "✔ Test 2 réussi : Réinitialisation complète et compte Admin 'nezzar' validés à 100%.\n\n";

echo "========================================================\n";
echo " TOUS LES TESTS DE MONDE ET DE RESET SONT VALIDÉS !     \n";
echo "========================================================\n";

