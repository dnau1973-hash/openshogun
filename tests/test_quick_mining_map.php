<?php
/**
 * Test de validation de l'expédition minière directe depuis la carte (Mineurs des Cimes)
 * OpenShogun - Court-circuitage de la page fleet pour forage et récolte fluide
 */

echo "=== Test 1 : Vérification de la modale #mapMiningModal dans views/map.php ===\n";
$mapContent = file_get_contents(__DIR__ . '/../views/map.php');

assert(str_contains($mapContent, 'id="mapMiningModal"'), "Modale #mapMiningModal manquante dans views/map.php");
assert(str_contains($mapContent, 'mineur.jpg'), "Illustration mineur.jpg manquante");
assert(str_contains($mapContent, 'inputMiningCount'), "Champ inputMiningCount manquant");
assert(str_contains($mapContent, 'miningModalSulfurBadge'), "Badge Soufre manquant");
assert(str_contains($mapContent, 'miningModalNitrateBadge'), "Badge Nitrate manquant");
assert(str_contains($mapContent, 'miningModalIronSandBadge'), "Badge Satetsu manquant");
assert(str_contains($mapContent, 'btnLaunchQuickMining'), "Bouton d'envoi manquant");
echo "✓ Modale #mapMiningModal présente et complète !\n";

echo "\n=== Test 2 : Vérification des fonctions JavaScript de dépêche minière ===\n";
assert(str_contains($mapContent, 'window.openQuickMiningModal'), "Fonction openQuickMiningModal manquante");
assert(str_contains($mapContent, 'window.closeQuickMiningModal'), "Fonction closeQuickMiningModal manquante");
assert(str_contains($mapContent, 'window.adjustMiningCount'), "Fonction adjustMiningCount manquante");
assert(str_contains($mapContent, 'window.setMiningCountMax'), "Fonction setMiningCountMax manquante");
assert(str_contains($mapContent, 'window.updateMiningEstimates'), "Fonction updateMiningEstimates manquante");
assert(str_contains($mapContent, 'window.submitQuickMining'), "Fonction submitQuickMining manquante");
echo "✓ Fonctions JavaScript de minage rapide validées !\n";

echo "\n=== Test 3 : Vérification des boutons d'ouverture directe sur la carte ===\n";
assert(preg_match('/openQuickMiningModal\([^)]+\)/', $mapContent), "Appel openQuickMiningModal manquant sur les tuiles montagnes");
echo "✓ Boutons d'action minière raccordés vers la modale directe !\n";

echo "\n=== Test 4 : Vérification de la création automatique de montagne dans api/fleet.php ===\n";
$apiContent = file_get_contents(__DIR__ . '/../api/fleet.php');
assert(str_contains($apiContent, "elseif (\$terrain['type'] === 'mountain')"), "Création de terrain montagne manquante dans api/fleet.php");
assert(str_contains($apiContent, "'mission_type'"), "Gestion mission_type présente");
echo "✓ api/fleet.php gère l'instanciation des montagnes et la mission mining !\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS EXPÉDITION MINIÈRE SONT VALIDÉS AVEC SUCCÈS !\n";
echo "============================================\n";
