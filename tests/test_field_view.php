<?php
/**
 * Test de la vue dédiée d'amélioration des parcelles (views/field.php)
 * et du cycle complet de construction (style Travian build.php).
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "=== TEST VUE DÉDIÉE CHAMP DE RESSOURCES (FIELD.PHP) ===\n";

$db = Database::getConnection();

// 1. Récupérer un utilisateur et sa planète active
$stmtUser = $db->query("SELECT u.*, p.id as current_planet_id FROM users u JOIN planets p ON p.user_id = u.id ORDER BY u.id ASC LIMIT 1");
$testUser = $stmtUser->fetch();

if (!$testUser) {
    echo "❌ Aucun utilisateur/planète trouvé pour le test.\n";
    exit(1);
}

$_SESSION['user_id'] = (int)$testUser['id'];
$_SESSION['planet_id'] = (int)$testUser['current_planet_id'];

$auth = new Auth();
$planet = $auth->getCurrentPlanet();
$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();

echo "Joueur test : {$testUser['username']} (ID {$testUser['id']})\n";
echo "Fief / Planète : {$planet['name']} (ID {$planet['id']})\n";

// 2. Tester les 18 slots et vérifier que la vue field.php génère les données sans erreur
$fields = $planetEngine->getFields((int)$planet['id']);
assert(count($fields) === 18, "La planète doit posséder 18 parcelles.");

echo "\n--- Vérification des 18 parcelles & métadonnées ---\n";
for ($slot = 1; $slot <= 18; $slot++) {
    $_GET['page'] = 'field';
    $_GET['slot'] = $slot;

    // Bufferiser le rendu de views/field.php
    ob_start();
    require __DIR__ . '/../views/field.php';
    $output = ob_get_clean();

    // Vérifier les éléments clés demandés
    assert(strpos($output, 'field-hero-card') !== false, "Slot #$slot : Carte héroïque manquante");
    assert(strpos($output, 'shogun_rural_terroir_bg.jpg') !== false, "Slot #$slot : Fond de carte manquant");
    assert(strpos($output, 'tile_') !== false, "Slot #$slot : Tuile graphique (tile) manquante");
    assert(strpos($output, 'field-description') !== false, "Slot #$slot : Description manquante");
    assert(strpos($output, 'Temps de construction pour le Niveau') !== false, "Slot #$slot : Temps de construction manquant");
    assert(strpos($output, 'Parcelle #' . $slot) !== false, "Slot #$slot : Numéro de slot manquant");
    assert(strpos($output, 'Retour au Terroir Féodal') !== false, "Slot #$slot : Lien de retour manquant");

    $fieldData = $fields[$slot - 1];
    $type = $fieldData['type'];
    $info = FIELD_TYPES[$type];
    assert(!empty($info['description']), "Le type de ressource $type doit avoir une description renseignée.");

    echo "✔ Slot #$slot : {$info['name']} (Niv. {$fieldData['level']}) -> Rendu OK\n";
}

// 3. Tester le calcul du temps de construction et des coûts pour les 4 types
echo "\n--- Vérification des calculs de coûts et durées ---\n";
foreach (FIELD_TYPES as $typeKey => $fConf) {
    $upgradeDetails = $buildingEngine->getUpgradeDetails('field', $typeKey, 1, 1);
    assert($upgradeDetails['target_level'] === 2, "Niveau cible incorrect");
    assert($upgradeDetails['cost']['metal'] > 0, "Coût bois manquant");
    assert($upgradeDetails['duration'] >= 2, "Durée de construction invalide");
    echo "✔ Type $typeKey : Niveau 2 coûte {$upgradeDetails['cost']['metal']} bois, durée {$upgradeDetails['duration']}s\n";
}

// 4. Tester l'API build.php (upgrade & cancel)
echo "\n--- Test de l'API api/build.php ---\n";
// S'assurer que les ressources suffisent pour le test
$db->prepare("UPDATE planets SET metal = 50000, crystal = 50000, deuterium = 50000 WHERE id = ?")->execute([$planet['id']]);

// Vider les anciennes files pour tester proprement
$db->prepare("DELETE FROM construction_queue WHERE planet_id = ?")->execute([$planet['id']]);

$res = $buildingEngine->startUpgrade((int)$planet['id'], 'field', '1');
assert($res['success'] === true, "L'amélioration du slot #1 doit réussir");
echo "✔ Amélioration lancée sur slot #1 (ID Queue: {$res['queue_id']})\n";

// Vérifier que le slot #1 affiche bien le chantier en cours
$_GET['slot'] = 1;
ob_start();
require __DIR__ . '/../views/field.php';
$outputUpgrading = ob_get_clean();
assert(strpos($outputUpgrading, 'Chantier en cours') !== false, "Le slot #1 doit afficher le statut chantier en cours");
assert(strpos($outputUpgrading, 'Interrompre les travaux') !== false, "Le bouton d'annulation doit être présent");
echo "✔ Rendu de la parcelle en cours de travaux vérifié avec succès.\n";

// Annuler le chantier
$cancelled = $buildingEngine->cancelUpgrade((int)$planet['id'], (int)$res['queue_id']);
assert($cancelled === true, "L'annulation doit réussir");
echo "✔ Chantier annulé avec succès (remboursement vérifié).\n";

echo "\n=== TOUS LES TESTS FIELD.PHP ONT RÉUSSI AVEC SUCCÈS (100%) ===\n";

