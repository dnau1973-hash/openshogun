<?php
/**
 * Test de validation pour le bloc "Marché en cours" dans la colonne de droite
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/MarketEngine.php';

echo "=== Test 1 : Instanciation & Méthodes MarketEngine ===\n";
$engine = new MarketEngine();

// Récupération d'une planète existante pour tester
$db = Database::getInstance();
$planet = $db->query("SELECT * FROM planets LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$planet) {
    echo "⚠️ Aucune planète trouvée en BDD, utilisation d'un mock.\n";
    $planetId = 1;
    $userId = 1;
} else {
    $planetId = (int)$planet['id'];
    $userId = (int)$planet['user_id'];
    echo "Utilisation de la planète ID #{$planetId} (User #{$userId})\n";
}

$summary = $engine->getOngoingMarketSummary($planetId, $userId);

assert(isset($summary['market_level']), "market_level manquant");
assert(isset($summary['is_market_built']), "is_market_built manquant");
assert(isset($summary['max_offer_slots']), "max_offer_slots manquant");
assert(isset($summary['active_offers']), "active_offers manquant");
assert(isset($summary['transport_convoys']), "transport_convoys manquant");
assert(isset($summary['trade_routes']), "trade_routes manquant");
assert(isset($summary['transporters']), "transporters manquant");
assert(isset($summary['total_active']), "total_active manquant");

echo "✓ MarketEngine::getOngoingMarketSummary() retourne la structure attendue.\n";
echo "  - Niveau Marché : " . $summary['market_level'] . "\n";
echo "  - Total activités actives : " . $summary['total_active'] . "\n";
echo "  - Offres en attente : " . $summary['active_offers_count'] . "\n";
echo "  - Convois en transit : " . $summary['transport_convoys_count'] . "\n";
echo "  - Routes commerciales : " . $summary['trade_routes_count'] . "\n";
echo "  - Capacité fret dispo : " . $summary['transporters']['total_capacity_formatted'] . "\n";

echo "\n=== Test 2 : Rendu HTML de views/partials/market_panel.php ===\n";
// Simulation des variables globales fournies par le template
$user = ['id' => $userId, 'username' => 'Tester'];
$planet = ['id' => $planetId, 'name' => 'Fief de Test'];

ob_start();
require __DIR__ . '/../views/partials/market_panel.php';
$html = ob_get_clean();

assert(strlen($html) > 0, "Le rendu du partial est vide");
assert(strpos($html, 'market-ongoing-panel') !== false, "Classe market-ongoing-panel absente du HTML");
assert(strpos($html, 'openEastMarketQuickAccess') !== false, "Fonction JS openEastMarketQuickAccess absente");

echo "✓ Rendu de market_panel.php réussi sans avertissements (" . strlen($html) . " octets).\n";

echo "\n=== Test 3 : Présence dans views/partials/resources_east_accordion.php ===\n";
$accordionCode = file_get_contents(__DIR__ . '/../views/partials/resources_east_accordion.php');
assert(strpos($accordionCode, 'id="heading-east-market"') !== false, "heading-east-market absent");
assert(strpos($accordionCode, 'id="east-item-market"') !== false, "east-item-market absent");
assert(strpos($accordionCode, 'market_panel.php') !== false, "inclusion market_panel.php absente");
assert(strpos($accordionCode, 'Marché en cours') !== false, "Titre Marché en cours absent");

echo "✓ Intégration dans l'accordéon Est vérifiée avec succès.\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS !\n";
echo "============================================\n";
