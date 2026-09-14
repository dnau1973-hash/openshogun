<?php
/**
 * Test de validation de la génération aléatoire des villages et des 18 parcelles de ressources (Style Travian)
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/WorldGenerator.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "=== TEST 1 : VÉRIFICATION DES ARCHÉTYPES DE TERROIRS ===\n";
$archetypes = VillageFieldGenerator::getArchetypes();
$validTypes = ['metal_mine', 'crystal_mine', 'deuterium_synth', 'solar_plant'];

foreach ($archetypes as $key => $data) {
    $sum = array_sum($data['counts']);
    assert($sum === 18, "L'archétype {$key} doit avoir exactement 18 parcelles (actuel: {$sum})");
    foreach ($data['counts'] as $type => $cnt) {
        assert(in_array($type, $validTypes), "Type {$type} non reconnu dans {$key}");
        assert($cnt >= 1, "Chaque ressource doit avoir au moins 1 parcelle dans {$key}");
    }
    echo "  [OK] Archétype '{$key}' : {$data['name']} ({$sum} parcelles : " . 
         "{$data['counts']['metal_mine']}🪵 / {$data['counts']['crystal_mine']}🪨 / " .
         "{$data['counts']['deuterium_synth']}🌾 / {$data['counts']['solar_plant']}⛩️)\n";
}

echo "\n=== TEST 2 : GÉNÉRATION ET MÉLANGE ALÉATOIRE DES 18 SLOTS ===\n";
$dist1 = VillageFieldGenerator::generateSlotDistribution('wood');
$dist2 = VillageFieldGenerator::generateSlotDistribution('wood');

assert(count($dist1) === 18, "La distribution doit contenir 18 slots");
assert(array_keys($dist1) === range(1, 18), "Les clés doivent être exactement 1 à 18");

$woodCount1 = count(array_keys($dist1, 'metal_mine'));
assert($woodCount1 === 7, "L'archétype 'wood' doit avoir 7 bûcherons (trouvé: {$woodCount1})");

// Vérifier que le mélange aléatoire change la disposition des slots
$identicalSlots = true;
for ($try = 0; $try < 10; $try++) {
    $d = VillageFieldGenerator::generateSlotDistribution('wood');
    if ($d !== $dist1) {
        $identicalSlots = false;
        break;
    }
}
assert(!$identicalSlots, "Les slots doivent être mélangés aléatoirement");
echo "  [OK] Distribution mélangée avec succès sur les 18 emplacements (7 bois répartis dynamiquement).\n";

echo "\n=== TEST 3 : GÉNÉRATION ALÉATOIRE DES COORDONNÉES ===\n";
$db = Database::getConnection();
$coordsSample = [];
for ($i = 0; $i < 15; $i++) {
    $c = VillageFieldGenerator::findRandomFreeCoordinates($db, 40);
    assert(!($c['x'] === 0 && $c['y'] === 0), "Les coordonnées ne doivent pas être (0,0)");
    $key = "{$c['x']}:{$c['y']}";
    assert(!isset($coordsSample[$key]), "Collision détectée sur les coordonnées libres {$key}");
    $coordsSample[$key] = true;
}
echo "  [OK] 15 coordonnées uniques générées aléatoirement sans collision.\n";

echo "\n=== TEST 4 : PEUPLEMENT EN BASE ET DÉTECTION DU TERROIR ===\n";
// Créer un village fictif temporaire
$db->exec("DELETE FROM users WHERE username = 'test_travian_user'");
$db->prepare("
    INSERT INTO users (username, email, password_hash, faction, created_at, last_active) 
    VALUES ('test_travian_user', 'test_travian@opengalaxy.local', 'fakehash', 'terran', NOW(), NOW())
")->execute();
$testUserId = (int)$db->lastInsertId();

$freeCoords = VillageFieldGenerator::findRandomFreeCoordinates($db, 40);
$db->prepare("
    INSERT INTO planets (user_id, name, coord_x, coord_y, planet_type, last_resource_update)
    VALUES (?, 'Fief Test Rizières', ?, ?, 'terrestrial', UNIX_TIMESTAMP())
")->execute([$testUserId, $freeCoords['x'], $freeCoords['y']]);
$testPlanetId = (int)$db->lastInsertId();

// Peupler avec l'archétype 'rice' (Grenier Impérial : 8 rizières)
$popResult = VillageFieldGenerator::populatePlanetFields($db, $testPlanetId, 'rice', 1, false);
assert($popResult['archetype'] === 'rice');

$planetEngine = new PlanetEngine();
$fields = $planetEngine->getFields($testPlanetId);
assert(count($fields) === 18, "Doit avoir exactement 18 parcelles en base de données");

$detected = VillageFieldGenerator::detectArchetype($fields);
assert($detected['name'] === 'Grenier Impérial', "Doit détecter l'archétype 'Grenier Impérial' (obtenu: {$detected['name']})");
assert($detected['counts']['deuterium_synth'] === 8, "Doit avoir 8 rizières");
echo "  [OK] Village #{$testPlanetId} peuplé avec succès : {$detected['icon']} {$detected['name']} ({$detected['counts']['deuterium_synth']} Rizières)\n";

// Vérifier le calcul de production dynamique
$prod = $planetEngine->calculateProduction($fields);
assert($prod['deuterium'] > 0, "La production de riz doit être positive");
echo "  [OK] Production dynamique calculée : 🪵 {$prod['metal']}/h | 🪨 {$prod['crystal']}/h | 🌾 {$prod['deuterium']}/h | Sérénité max: {$prod['energy_max']}\n";

echo "\n=== TEST 5 : ENREGISTREMENT COMPLET VIA AUTH::REGISTER ===\n";
$auth = new Auth();
$db->exec("DELETE FROM users WHERE username = 'shogun_cadet'");
$regRes = $auth->register('shogun_cadet', 'shogun_cadet@shogun.local', 'Secret123#', 'terran');
assert($regRes['success'] === true, "L'enregistrement du joueur doit réussir: " . ($regRes['error'] ?? ''));

$stmtNewUser = $db->prepare("SELECT id FROM users WHERE username = 'shogun_cadet'");
$stmtNewUser->execute();
$cadetId = (int)$stmtNewUser->fetchColumn();

$stmtNewPlanet = $db->prepare("SELECT * FROM planets WHERE user_id = ?");
$stmtNewPlanet->execute([$cadetId]);
$cadetPlanet = $stmtNewPlanet->fetch();

assert(!empty($cadetPlanet), "Le joueur doit avoir un domaine castral créé");
echo "  [OK] Fief créé aux coordonnées aléatoires [{$cadetPlanet['coord_x']} : {$cadetPlanet['coord_y']}]\n";

$cadetFields = $planetEngine->getFields((int)$cadetPlanet['id']);
assert(count($cadetFields) === 18, "Le fief du joueur doit avoir 18 parcelles");
$cadetTerroir = VillageFieldGenerator::detectArchetype($cadetFields);
echo "  [OK] Terroir du joueur : {$cadetTerroir['icon']} {$cadetTerroir['name']}\n";

// Nettoyage des tests
$db->prepare("DELETE FROM planet_fields WHERE planet_id IN (?, ?)")->execute([$testPlanetId, $cadetPlanet['id']]);
$db->prepare("DELETE FROM planets WHERE id IN (?, ?)")->execute([$testPlanetId, $cadetPlanet['id']]);
$db->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$testUserId, $cadetId]);

echo "\n============================================\n";
echo "TOUS LES TESTS DE GÉNÉRATION DE VILLAGES ONT RÉUSSI AVEC SUCCÈS !\n";
echo "============================================\n";

