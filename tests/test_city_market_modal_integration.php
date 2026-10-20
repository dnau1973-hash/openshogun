<?php
/**
 * Test de vérification de l'intégration du Marché Féodal, Bourse des Sens & Troc 1:1:1
 * dans la modale d'élite de la Cité Castrale (views/city.php)
 */

echo "=== VÉRIFICATION INTÉGRATION MODALE CITÉ CASTRALE (views/city.php) ===\n\n";

$cityContent = file_get_contents(__DIR__ . '/../views/city.php');
$indexContent = file_get_contents(__DIR__ . '/../index.php');

// 1. Vérification onglet Marché & Troc dans cityModalTabsNav
assert(strpos($cityContent, 'id="city-tab-market-li"') !== false, "L'onglet #city-tab-market-li doit être défini dans cityModalTabsNav.");
assert(strpos($cityContent, 'id="city-tab-market-btn"') !== false, "Le bouton #city-tab-market-btn doit être défini.");
assert(strpos($cityContent, 'Marché &amp; Troc Féodal') !== false, "Le libellé de l'onglet Marché & Troc Féodal doit être présent.");
echo "✓ [1/7] Onglet 'Marché & Troc Féodal' présent dans la modale !\n";

// 2. Vérification panneau city-pane-market et sous-onglets
assert(strpos($cityContent, 'id="city-pane-market"') !== false, "Le conteneur #city-pane-market doit exister dans les tab-panes.");
assert(strpos($cityContent, 'data-bs-target="#tab-market-npc"') !== false, "Le sélecteur de sous-onglet #tab-market-npc (Troc 1:1:1) doit être présent.");
assert(strpos($cityContent, 'data-bs-target="#tab-market-p2p"') !== false, "Le sélecteur de sous-onglet #tab-market-p2p (Bourse des sens) doit être présent.");
assert(strpos($cityContent, 'data-bs-target="#tab-market-dispatch"') !== false, "Le sélecteur de sous-onglet #tab-market-dispatch (Caravanes) doit être présent.");
assert(strpos($cityContent, 'data-bs-target="#tab-market-routes"') !== false, "Le sélecteur de sous-onglet #tab-market-routes (Routes) doit être présent.");
echo "✓ [2/7] Panneau #city-pane-market et les 4 sous-onglets (Troc 1:1:1, Bourse P2P, Caravanes, Routes) intégrés !\n";

// 3. Vérification inclusion des partials requis
assert(strpos($cityContent, "require __DIR__ . '/partials/npc_exchange_modal.php'") !== false, "npc_exchange_modal.php doit être inclus dans city.php.");
assert(strpos($cityContent, "require __DIR__ . '/partials/market_p2p_tab.php'") !== false, "market_p2p_tab.php doit être inclus dans city.php.");
assert(strpos($cityContent, "require __DIR__ . '/partials/market_dispatch_tab.php'") !== false, "market_dispatch_tab.php doit être inclus dans city.php.");
assert(strpos($cityContent, "require __DIR__ . '/partials/market_routes_tab.php'") !== false, "market_routes_tab.php doit être inclus dans city.php.");
assert(strpos($cityContent, "require __DIR__ . '/partials/market_scripts.php'") !== false, "market_scripts.php doit être inclus dans city.php.");
echo "✓ [3/7] Tous les partiels du Marché et de la Bourse sont inclus dans city.php !\n";

// 4. Vérification bannière d'accès direct sur l'onglet Évolution
assert(strpos($cityContent, 'id="cityModalMarketQuickBanner"') !== false, "La bannière #cityModalMarketQuickBanner doit être présente sur l'onglet Évolution.");
assert(strpos($cityContent, 'Ouvrir la Bourse &amp; Troc') !== false, "Le bouton 'Ouvrir la Bourse & Troc' doit être présent.");
echo "✓ [4/7] Bannière d'accès direct 'Ouvrir la Bourse & Troc' présente sur l'onglet Évolution !\n";

// 5. Vérification du comportement JavaScript dans renderCitySlotModal
assert(strpos($cityContent, 'const isMarketDirect = (data.code === \'market\' && data.level > 0);') !== false, "renderCitySlotModal doit détecter le marché actif.");
assert(strpos($cityContent, "document.getElementById('city-tab-market-btn')") !== false, "renderCitySlotModal doit cibler l'onglet Marché.");
assert(strpos($cityContent, 'renderCityMarketPane(data)') !== false, "renderCityMarketPane doit être appelée.");
echo "✓ [5/7] Basculement automatique sur l'onglet Marché & Troc lors de l'ouverture du slot Marché validé !\n";

// 6. Vérification des fonctions JavaScript du Marché
$scriptsContent = file_get_contents(__DIR__ . '/../views/partials/market_scripts.php');
assert(strpos($scriptsContent, 'function swapP2PExchangeDirection()') !== false, "swapP2PExchangeDirection doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function swapP2PFilters()') !== false, "swapP2PFilters doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function selectTradeDirection(') !== false, "selectTradeDirection doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function updateMarketCalculations()') !== false, "updateMarketCalculations doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function submitMarketCargoDispatch()') !== false, "submitMarketCargoDispatch doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function submitP2POffer()') !== false, "submitP2POffer doit être définie dans market_scripts.php.");
assert(strpos($scriptsContent, 'function acceptP2POffer(') !== false, "acceptP2POffer doit être définie dans market_scripts.php.");
echo "✓ [6/7] Fonctions JavaScript du Marché (⇄ inversion sens, filtres, calcul fret, offres) opérationnelles !\n";

// 7. Vérification du routage index.php
assert(strpos($indexContent, "header('Location: /?page=city&code=market')") !== false, "Routage ?page=market vers la cité avec code=market configuré.");
echo "✓ [7/7] Alias de routage index.php pour ?page=market validé !\n";

echo "\n=======================================================\n";
echo ">>> INTÉGRATION DE LA BOURSE ET DU TROC DANS LA MODALE : 100% OK ! <<<\n";
echo "=======================================================\n";
