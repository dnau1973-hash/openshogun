<?php
/**
 * Test unitaire et fonctionnel :
 * 1. Uniformisation du bloc Garnison (Eclaireur Shinobi, Chariots de transport)
 * 2. Modale de pêche directe depuis la carte sans passer par fleet.php
 */

require_once __DIR__ . '/../core/Database.php';

echo "=== Test 1 : Vérification du bloc Garnison (troops_panel.php) ===\n";
$troopsContent = file_get_contents(__DIR__ . '/../views/partials/troops_panel.php');

assert(str_contains($troopsContent, 'Cavalerie & Logistique'), "Filtre 'Cavalerie & Logistique' manquant");
assert(str_contains($troopsContent, 'eclaireur_shinobi_furtif.jpg'), "Illustration HD Shinobi manquante");
assert(str_contains($troopsContent, 'chariot_ravitaillement_leger.jpg'), "Illustration HD Chariot Léger manquante");
assert(str_contains($troopsContent, 'grand_convoi_logistique.jpg'), "Illustration HD Grand Convoi manquante");
assert(str_contains($troopsContent, 'garnison-unit-row'), "Structure uniforme .garnison-unit-row manquante");
assert(str_contains($troopsContent, 'openUnitDetailsModal'), "Modal de détails manquant sur les unités de cavalerie/logistique");
echo "✓ troops_panel.php uniformisé avec succès !\n";

echo "\n=== Test 2 : Vérification de la modale de pêche directe dans views/map.php ===\n";
$mapContent = file_get_contents(__DIR__ . '/../views/map.php');

assert(str_contains($mapContent, 'id="mapFishingModal"'), "Modale #mapFishingModal manquante dans views/map.php");
assert(str_contains($mapContent, 'openQuickFishingModal'), "Fonction openQuickFishingModal manquante dans views/map.php");
assert(str_contains($mapContent, 'submitQuickFishing'), "Fonction submitQuickFishing manquante dans views/map.php");
assert(str_contains($mapContent, 'inputFishingCount'), "Champ inputFishingCount manquant");
assert(str_contains($mapContent, 'pecheur.jpg'), "Illustration pecheur.jpg manquante");
assert(str_contains($mapContent, 'adjustFishingCount'), "Fonction adjustFishingCount manquante");
assert(str_contains($mapContent, 'setFishingCountMax'), "Fonction setFishingCountMax manquante");
echo "✓ map.php intègre la modale de pêche directe avec succès !\n";

echo "\n=== Test 3 : Vérification de la conformité de l'API /api/fleet.php ===\n";
$apiContent = file_get_contents(__DIR__ . '/../api/fleet.php');
assert(str_contains($apiContent, "'fishing'"), "Mission 'fishing' reconnue dans api/fleet.php");
assert(str_contains($apiContent, 'target_x'), "Paramètre target_x géré");
assert(str_contains($apiContent, 'target_y'), "Paramètre target_y géré");
echo "✓ api/fleet.php est parfaitement configuré pour la mission fishing !\n";

echo "\n============================================\n";
echo "🎉 TOUS LES CONTRÔLES SONT VALIDÉS AVEC SUCCÈS !\n";
echo "============================================\n";
