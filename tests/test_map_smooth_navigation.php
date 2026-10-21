<?php
/**
 * Test Automatisé : Système de Déplacement Fluide et Naturel sur la Carte des Provinces
 */
echo "========================================================================\n";
echo " TEST : DÉPLACEMENT FLUIDE & INERTIE CINÉTIQUE SUR LA CARTE (GALAXY MAP)\n";
echo "========================================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest(bool $condition, string $description): void {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$description}\n";
    }
}

// 1. Analyse de public/js/galaxy_map.js
echo "[TEST 1] Analyse du contrôleur public/js/galaxy_map.js...\n";
$jsContent = file_get_contents(__DIR__ . '/../public/js/galaxy_map.js');

assertTest($jsContent !== false && strlen($jsContent) > 1000, "galaxy_map.js est accessible et non vide");

// Vérification de la suppression du snap-back défectueux
assertTest(!str_contains($jsContent, "setTimeout(() => {\n            this.canvas.style.transition = 'none';\n            if (coordDeltaX !== 0"), "L'ancien snap-back à rebond élastique brutal a bien été supprimé");

// Vérification de la présence de la physique et des nouvelles méthodes
assertTest(str_contains($jsContent, "this.overscan = 2;"), "Anneaux de tuiles de débordement (overscan = 2) configurés pour éliminer les bords vides");
assertTest(str_contains($jsContent, "startMomentum(vx, vy)"), "Méthode d'inertie cinétique (momentum scrolling) présente");
assertTest(str_contains($jsContent, "smoothSnapToCenter()"), "Méthode de calage magnétique doux sans rebond présente");
assertTest(str_contains($jsContent, "panByDelta(dx, dy)"), "Méthode de translation continue au pixel présente");
assertTest(str_contains($jsContent, "applyTransform()"), "Application continue de la matrice GPU translate3d présente");
assertTest(str_contains($jsContent, "onWheel(e)"), "Navigation fluide et zoom molette présents");
assertTest(str_contains($jsContent, "smoothMoveBy("), "Méthode de déplacement par pas directionnel présente");

// Vérification du support clavier complet (Flèches, ZQSD et WASD)
assertTest(str_contains($jsContent, "KeyW") && str_contains($jsContent, "KeyZ") && str_contains($jsContent, "ArrowUp"), "Support clavier complet ZQSD / WASD et Flèches directionnelles");

// 2. Vérification des styles CSS dans public/css/style.css
echo "\n[TEST 2] Analyse des styles dans public/css/style.css...\n";
$cssContent = file_get_contents(__DIR__ . '/../public/css/style.css');

assertTest(str_contains($cssContent, ".hud-dpad-nav"), "Styles du pad directionnel HUD (.hud-dpad-nav) intégrés");
assertTest(str_contains($cssContent, "cursor: grab;") && str_contains($cssContent, "cursor: grabbing;"), "Curseurs grab / grabbing configurés pour une interaction tactile claire");

// 3. Intégration dans views/map.php
echo "\n[TEST 3] Vérification de views/map.php et de son conteneur...\n";
$mapContent = file_get_contents(__DIR__ . '/../views/map.php');

assertTest(str_contains($mapContent, "new GalaxyMapController('galaxyMapContainer'"), "views/map.php instancie bien GalaxyMapController");
assertTest(str_contains($mapContent, "galaxy_map.js"), "views/map.php inclut bien le script galaxy_map.js");

echo "\n========================================================================\n";
echo " RÉSULTATS : {$testsPassed} / {$totalTests} tests réussis.\n";
echo "========================================================================\n";

if ($testsPassed === $totalTests) {
    echo " TOUS LES TESTS DE DÉPLACEMENT FLUIDE SONT 100% OK !\n";
    exit(0);
} else {
    echo " CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}

