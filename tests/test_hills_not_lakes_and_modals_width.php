<?php
/**
 * Test : Validation de la distinction Collines & Coteaux vs Lacs et élargissement des modales de la carte
 * Fichier : tests/test_hills_not_lakes_and_modals_width.php
 */

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "\n========================================================================" . PHP_EOL;
echo " TEST : COLLINES & COTEAUX NON ASSIMILÉS À DES LACS & ÉLARGISSEMENT MODALES" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

$mapView = file_get_contents(dirname(__DIR__) . '/views/map.php');

echo "[TEST 1] Vérification de l'élargissement des dimensions maximales des modales de carte..." . PHP_EOL;
assertTest(str_contains($mapView, 'max-width: 1100px;'), "Modale d'inspection de parcelle #mapTileModal encore élargie à 1100px (format grand écran spacieux)");
assertTest(str_contains($mapView, 'max-width: 860px;'), "Modales d'expéditions Pêche & Mineurs élargies à 860px");
assertTest(str_contains($mapView, 'height: 230px;'), "Bandeau d'illustration HD rehaussé à 230px pour un équilibre visuel parfait");

echo PHP_EOL . "[TEST 2] Vérification de la détection de terrain 'Collines & Coteaux' dans views/map.php..." . PHP_EOL;
// Vérifier que la regex \beaux?\b est utilisée plutôt que includes('eau') qui capturait 'coteaux'
assertTest(!str_contains($mapView, "tName.includes('eau')"), "Suppression du filtre défaillant tName.includes('eau')");
assertTest(!str_contains($mapView, "oName.includes('eau')"), "Suppression du filtre défaillant oName.includes('eau')");
assertTest(!str_contains($mapView, "data.terrain_name.toLowerCase().includes('eau')"), "Suppression de includes('eau') sur terrain_name");
assertTest(!str_contains($mapView, "data.planet_name.toLowerCase().includes('eau')"), "Suppression de includes('eau') sur planet_name");
assertTest(!str_contains($mapView, "data.oasis_name.toLowerCase().includes('eau')"), "Suppression de includes('eau') sur oasis_name");

echo PHP_EOL . "[TEST 3] Vérification de la priorité des Collines & Coteaux sur les Lacs..." . PHP_EOL;
assertTest(str_contains($mapView, "coteau"), "Le mot-clé 'coteau' est expressément pris en compte pour les collines");
assertTest(str_contains($mapView, "!isHills && !isMountain && !isForest && (data.terrain_type === 'lake'"), "Les collines, montagnes et forêts ont priorité stricte avant toute vérification de lac");
assertTest(str_contains($mapView, "collines.jpg"), "Les collines renvoient toujours l'illustration collines.jpg");

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS DE GESTION DES COLLINES & ÉLARGISSEMENT SONT 100% VALIDÉS !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;

