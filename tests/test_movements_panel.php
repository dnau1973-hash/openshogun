<?php
/**
 * Test de validation pour le bloc "Troupes & Expéditions en marche" dans la colonne Est
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/HeroEngine.php';

echo "=== Test 1 : Instanciation & Méthode FleetEngine::getMovementsSummary ===\n";
$db = Database::getInstance();
$planet = $db->query("SELECT * FROM planets LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$planet) {
    echo "⚠️ Aucune planète en base, test avec identifiants par défaut.\n";
    $planetId = 1;
    $userId = 1;
} else {
    $planetId = (int)$planet['id'];
    $userId = (int)$planet['user_id'];
    echo "Utilisation de la planète ID #{$planetId} (User #{$userId})\n";
}

$fleetEngine = new FleetEngine();
$summary = $fleetEngine->getMovementsSummary($userId, $planetId);

assert(array_key_exists('hero', $summary), "Clé hero manquante");
assert(array_key_exists('hero_missions', $summary), "Clé hero_missions manquante");
assert(array_key_exists('has_hero_moving', $summary), "Clé has_hero_moving manquante");
assert(array_key_exists('miner_missions', $summary), "Clé miner_missions manquante");
assert(array_key_exists('miner_missions_count', $summary), "Clé miner_missions_count manquante");
assert(array_key_exists('fisher_missions', $summary), "Clé fisher_missions manquante");
assert(array_key_exists('fisher_missions_count', $summary), "Clé fisher_missions_count manquante");
assert(array_key_exists('military_missions', $summary), "Clé military_missions manquante");
assert(array_key_exists('military_missions_count', $summary), "Clé military_missions_count manquante");
assert(array_key_exists('incoming_threats', $summary), "Clé incoming_threats manquante");
assert(array_key_exists('incoming_threats_count', $summary), "Clé incoming_threats_count manquante");
assert(array_key_exists('total_movements', $summary), "Clé total_movements manquante");

echo "✓ Structure de retour FleetEngine::getMovementsSummary() validée avec succès.\n";
echo "  - Héros : " . ($summary['hero']['name'] ?? 'Non assigné') . "\n";
echo "  - Total marches actives : " . $summary['total_movements'] . "\n";
echo "  - Mineurs des Cimes (Kōfu) en mission : " . $summary['miner_missions_count'] . "\n";
echo "  - Pêcheurs (Ryōshi) en mission : " . $summary['fisher_missions_count'] . "\n";
echo "  - Marches militaires : " . $summary['military_missions_count'] . "\n";
echo "  - Menaces / Incursions ennemies : " . $summary['incoming_threats_count'] . "\n";

echo "\n=== Test 2 : Rendu HTML de views/partials/movements_panel.php ===\n";
$user = ['id' => $userId, 'username' => 'DaimyoTest'];
$planet = ['id' => $planetId, 'name' => 'FiefTest'];

ob_start();
require __DIR__ . '/../views/partials/movements_panel.php';
$html = ob_get_clean();

assert(strlen($html) > 0, "Le rendu du partial est vide");
assert(strpos($html, 'movements-ongoing-panel') !== false, "Classe movements-ongoing-panel absente");
assert(strpos($html, 'Champion Samouraï') !== false, "Section Champion Samouraï absente");
assert(strpos($html, 'Mineurs des Cimes') !== false, "Section Mineurs des Cimes absente");
assert(strpos($html, 'Pêcheurs Féodaux') !== false, "Section Pêcheurs Féodaux absente");
assert(strpos($html, 'Marches Guerrières') !== false, "Section Marches Guerrières absente");

echo "✓ Rendu de movements_panel.php réussi sans avertissements (" . strlen($html) . " octets).\n";

echo "\n=== Test 3 : Intégration dans views/partials/resources_east_accordion.php ===\n";
$accordionCode = file_get_contents(__DIR__ . '/../views/partials/resources_east_accordion.php');
assert(strpos($accordionCode, 'id="heading-east-movements"') !== false, "heading-east-movements absent");
assert(strpos($accordionCode, 'id="east-item-movements"') !== false, "east-item-movements absent");
assert(strpos($accordionCode, 'movements_panel.php') !== false, "Inclusion movements_panel.php absente");
assert(strpos($accordionCode, 'Troupes &amp; Expéditions') !== false, "Titre Troupes & Expéditions absent");

echo "✓ Intégration dans l'accordéon Est vérifiée avec succès.\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS MOUVEMENTS SONT PASSÉS ! \n";
echo "============================================\n";
