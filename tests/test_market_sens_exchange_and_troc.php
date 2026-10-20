<?php
/**
 * Test unitaire & fonctionnel : Bourse des Sens d'Échange & Troc 1:1:1
 * Couvre :
 * 1. Moteur MarketEngine (accessibilité dès Niv. 1, slots, getExchangeDirectionsSummary)
 * 2. Troc 1:1:1 (tiers Bois/Pierre/Riz, mode inline dans le marché)
 * 3. Bourse des Sens d'Échange (registre des flux, bouton d'inversion ⇄, filtres directionnels)
 */

echo "=== TEST : BOURSE DES SENS D'ÉCHANGE & TROC 1:1:1 ===\n\n";

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/MarketEngine.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';

// 1. Test du barème des slots et accessibilité dès le Niveau 1
echo "[1] Test des slots d'offres et accessibilité MarketEngine (Dès Niveau 1)...\n";

$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Création schéma minimal SQLite
$sqlite->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        username TEXT,
        gold_coins INTEGER DEFAULT 50
    );
    CREATE TABLE planets (
        id INTEGER PRIMARY KEY,
        user_id INTEGER,
        name TEXT,
        coord_x INTEGER DEFAULT 0,
        coord_y INTEGER DEFAULT 0,
        metal REAL DEFAULT 5000,
        crystal REAL DEFAULT 4000,
        deuterium REAL DEFAULT 3000,
        clay REAL DEFAULT 1500,
        fish REAL DEFAULT 1000,
        tea REAL DEFAULT 500,
        soybean REAL DEFAULT 400,
        rice_flour REAL DEFAULT 200,
        sake REAL DEFAULT 100,
        wooden_beams REAL DEFAULT 300,
        charcoal REAL DEFAULT 250,
        sulfur REAL DEFAULT 50,
        nitrate REAL DEFAULT 50,
        iron_sand REAL DEFAULT 50,
        metal_max INTEGER DEFAULT 20000,
        crystal_max INTEGER DEFAULT 20000,
        deuterium_max INTEGER DEFAULT 20000,
        clay_max INTEGER DEFAULT 10000,
        fish_max INTEGER DEFAULT 10000,
        tea_max INTEGER DEFAULT 10000,
        soybean_max INTEGER DEFAULT 10000,
        rice_flour_max INTEGER DEFAULT 10000,
        sake_max INTEGER DEFAULT 10000,
        wooden_beams_max INTEGER DEFAULT 10000,
        charcoal_max INTEGER DEFAULT 10000,
        sulfur_max INTEGER DEFAULT 5000,
        nitrate_max INTEGER DEFAULT 5000,
        iron_sand_max INTEGER DEFAULT 5000
    );
    CREATE TABLE planet_buildings (
        id INTEGER PRIMARY KEY,
        planet_id INTEGER,
        building_type TEXT,
        level INTEGER DEFAULT 1
    );
    CREATE TABLE market_offers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        planet_id INTEGER NOT NULL,
        offer_res TEXT NOT NULL,
        offer_amount INTEGER NOT NULL,
        demand_res TEXT NOT NULL,
        demand_amount INTEGER NOT NULL,
        ratio REAL NOT NULL,
        status TEXT NOT NULL DEFAULT 'active',
        buyer_user_id INTEGER NULL,
        buyer_planet_id INTEGER NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        completed_at TEXT NULL,
        cancelled_at TEXT NULL
    );
");

$engine = new MarketEngine($sqlite);

assert($engine->getMaxOfferSlots(0) === 0, "Niveau 0 = 0 slot.");
assert($engine->getMaxOfferSlots(1) === 1, "Niveau 1 = 1 slot (accessible immédiatement).");
assert($engine->getMaxOfferSlots(2) === 1, "Niveau 2 = 1 slot.");
assert($engine->getMaxOfferSlots(3) === 2, "Niveau 3 = 2 slots.");
assert($engine->getMaxOfferSlots(4) === 2, "Niveau 4 = 2 slots.");
assert($engine->getMaxOfferSlots(5) === 3, "Niveau 5 = 3 slots.");
assert($engine->getMaxOfferSlots(9) === 3, "Niveau 9 = 3 slots.");
assert($engine->getMaxOfferSlots(10) === 4, "Niveau 10 = 4 slots.");
assert($engine->getMaxOfferSlots(12) === 5, "Niveau 12 = 5 slots.");
echo "✓ Échelle de déblocage des slots validée (1 slot dès le Niveau 1 du marché) !\n";

// 2. Test de dépôt et calcul du récapitulatif des sens d'échange
echo "\n[2] Test du récapitulatif de la Bourse des Sens d'Échange (getExchangeDirectionsSummary)...\n";
$sqlite->exec("INSERT INTO users (id, username) VALUES (1, 'Daimyo_Oda'), (2, 'Daimyo_Takeda')");
$sqlite->exec("INSERT INTO planets (id, user_id, name) VALUES (1, 1, 'Château Gifu'), (2, 2, 'Château Kofu')");
$sqlite->exec("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (1, 'market', 2), (2, 'market', 3)");

// Insertion de plusieurs offres dans différents sens d'échange
// Sens 1 : Bois -> Pierre (2 offres)
$resOff1 = $engine->createOffer(1, 1, 'metal', 500, 'crystal', 1000);
assert($resOff1['success'] === true, "Dépôt d'offre au marché niveau 2 doit réussir.");

$sqlite->exec("INSERT INTO market_offers (user_id, planet_id, offer_res, offer_amount, demand_res, demand_amount, ratio, status)
               VALUES (2, 2, 'metal', 1000, 'crystal', 1500, 1.5, 'active')");

// Sens 2 : Pierre -> Bois (1 offre)
$sqlite->exec("INSERT INTO market_offers (user_id, planet_id, offer_res, offer_amount, demand_res, demand_amount, ratio, status)
               VALUES (2, 2, 'crystal', 800, 'metal', 800, 1.0, 'active')");

// Sens 3 : Bois -> Riz (1 offre)
$sqlite->exec("INSERT INTO market_offers (user_id, planet_id, offer_res, offer_amount, demand_res, demand_amount, ratio, status)
               VALUES (1, 1, 'metal', 300, 'deuterium', 600, 2.0, 'active')");

$summary = $engine->getExchangeDirectionsSummary();
assert(count($summary) === 3, "Il doit y avoir 3 sens d'échange distincts répertoriés.");

// Vérifier que le sens Bois -> Pierre est le premier (2 offres, 1500 unités au total)
$firstDir = $summary[0];
assert($firstDir['offer_res'] === 'metal' && $firstDir['demand_res'] === 'crystal', "Le premier sens d'échange doit être metal -> crystal.");
assert((int)$firstDir['offers_count'] === 2, "Il doit y avoir 2 offres pour metal -> crystal.");
assert((int)$firstDir['total_offered'] === 1500, "Le volume total offert doit être 1 500.");
assert((float)$firstDir['best_ratio'] === 1.5 || (float)$firstDir['best_ratio'] === 2.0, "Le meilleur ratio doit être calculé.");

echo "✓ Bourse des Sens d'Échange : getExchangeDirectionsSummary a agrégé avec succès les cours et volumes par sens d'échange !\n";

// 3. Test de rendu du Troc 1:1:1 en mode inline
echo "\n[3] Test de rendu du Troc 1:1:1 inline (views/partials/npc_exchange_modal.php)...\n";
$isInlineNpcExchange = true;
$goldCoins = 20;
$user = ['gold_coins' => 20];
$planet = ['id' => 1, 'name' => 'Château Gifu'];

ob_start();
require __DIR__ . '/../views/partials/npc_exchange_modal.php';
$htmlInlineNpc = ob_get_clean();

assert(strpos($htmlInlineNpc, 'id="inlineNpcExchangeContainer"') !== false, "Le conteneur direct inlineNpcExchangeContainer doit être présent.");
assert(strpos($htmlInlineNpc, 'Intendant du Marché Castral &bull; Troc Équilibré 1:1:1') !== false, "Le titre du troc 1:1:1 doit être présent.");
assert(strpos($htmlInlineNpc, 'distributeThirds()') !== false, "Le bouton de troc 1:1:1 en tiers doit être présent.");
assert(strpos($htmlInlineNpc, 'Troc 1:1:1 en Tiers (Bois / Pierre / Riz)') !== false, "Le libellé 'Troc 1:1:1 en Tiers' doit être présent.");
assert(strpos($htmlInlineNpc, 'Sceller le Troc 1:1:1 (3 Koban)') !== false, "Le bouton de validation directe 1:1:1 doit être présent.");
echo "✓ Panneau direct du Troc 1:1:1 validé avec succès !\n";

// 4. Test de rendu de la Bourse des Sens d'Échange (views/partials/market_p2p_tab.php)
echo "\n[4] Test de rendu de views/partials/market_p2p_tab.php...\n";
$lvl = 3;
$p2pMaxSlots = $engine->getMaxOfferSlots($lvl);
$p2pTradables = MarketEngine::getTradableResources();
$p2pActiveOffers = $engine->getActiveOffers();
$p2pExchangeDirections = $summary;
$p2pMyOffers = $engine->getUserOffers(1);
$p2pMyActiveOffersCount = 1;

ob_start();
require __DIR__ . '/../views/partials/market_p2p_tab.php';
$htmlBourse = ob_get_clean();

// Vérifications Bourse des sens d'échange
assert(strpos($htmlBourse, 'Bourse des Sens d\'Échange &amp; Troc Féodal (P2P)') !== false, "Le titre Bourse des Sens d'Échange doit être présent.");
assert(strpos($htmlBourse, 'Registre de la Bourse des Sens d\'Échange') !== false, "Le registre de la bourse des sens d'échange doit être présent.");
assert(strpos($htmlBourse, 'swapP2PExchangeDirection()') !== false, "Le bouton d'inversion rapide du sens d'échange (⇄) doit être présent.");
assert(strpos($htmlBourse, 'Inverser le Sens (⇄)') !== false, "Le libellé 'Inverser le Sens (⇄)' doit être présent.");
assert(strpos($htmlBourse, 'id="p2p_filter_direction"') !== false, "Le sélecteur de sens d'échange prédéfini doit être présent.");
assert(strpos($htmlBourse, 'swapP2PFilters()') !== false, "Le bouton d'inversion des filtres de la bourse doit être présent.");
assert(strpos($htmlBourse, 'id="p2p_filter_affordable"') !== false, "Le filtre des offres payables avec le stock disponible doit être présent.");
assert(strpos($htmlBourse, 'Sens d\'Échange') !== false, "La colonne Sens d'Échange doit être présente dans les tables.");

echo "✓ Interface de la Bourse des Sens d'Échange validée avec succès !\n";

echo "\n=======================================================\n";
echo ">>> TOUS LES TESTS BOURSE DES SENS & TROC 1:1:1 SONT 100% OK ! <<<\n";
echo "=======================================================\n";

