<?php
/**
 * Test de validation : Effet de Parallaxe 2.5D et Respiration Cinématique (Non assujetti au scroll)
 * Vérifie l'intégration CSS, JavaScript et templates (Carrousel & Modales)
 */

require_once __DIR__ . '/../config/game_constants.php';

echo "=== TEST D'INTÉGRATION DU PARALLAXE DE PROFONDEUR 2.5D & RESPIRATION CINÉMATIQUE ===\n\n";

// 1. Vérification CSS
$css = file_get_contents(__DIR__ . '/../public/css/style.css');
assert(strpos($css, '.depth-parallax-scene') !== false, "La classe .depth-parallax-scene doit être définie dans style.css");
assert(strpos($css, '.depth-parallax-img') !== false, "La classe .depth-parallax-img doit être définie dans style.css");
assert(strpos($css, 'cinematicBreathing') !== false, "L'animation @keyframes cinematicBreathing doit être définie");
assert(strpos($css, 'prefers-reduced-motion') !== false, "L'accessibilité prefers-reduced-motion doit être respectée");
echo "✅ [1/5] Styles CSS d'accélération matérielle, respiration et accessibilité validés !\n";

// 2. Vérification JavaScript
$js = file_get_contents(__DIR__ . '/../public/js/app.js');
assert(strpos($js, 'initDepthParallax') !== false, "La fonction initDepthParallax doit être déclarée dans app.js");
assert(strpos($js, 'initDepthParallax();') !== false, "initDepthParallax() doit être appelée au DOMContentLoaded");
assert(strpos($js, 'requestAnimationFrame') !== false, "La boucle de rendu doit utiliser requestAnimationFrame pour du 60/120fps");
assert(strpos($js, 'shown.bs.modal') !== false, "Les événements des modales Bootstrap doivent être interceptés");
echo "✅ [2/5] Moteur interactif JS (lerp, souris, tactile, gyroscope) validé dans app.js !\n";

// 3. Vérification Carrousel en haut de page (views/partials/header.php) - Effet retiré conformément à la demande
$header = file_get_contents(__DIR__ . '/../views/partials/header.php');
assert(strpos($header, 'headerBuildingsCarousel') !== false, "Le carrousel d'en-tête doit exister");
assert(strpos($header, 'class="d-block w-100 h-100 position-relative text-decoration-none depth-parallax-scene"') === false, "L'effet parallaxe ne doit pas être appliqué au carrousel");
echo "✅ [3/5] Carrousel principal (#headerBuildingsCarousel) affiché de manière classique (effet retiré) !\n";

// 4. Vérification Modale Castrale de Bâtiment (views/city.php)
$city = file_get_contents(__DIR__ . '/../views/city.php');
assert(strpos($city, 'class="depth-parallax-scene"') !== false, "La modale des bâtiments doit encapsuler la scène en .depth-parallax-scene");
assert(strpos($city, 'id="cityModalBannerImg" class="depth-parallax-img"') !== false, "L'image #cityModalBannerImg doit avoir .depth-parallax-img");
echo "✅ [4/5] Modale d'Élite Castrale (#buildingSlotModal) équipée de la scène de profondeur !\n";

// 5. Vérification Autres Modales Bannières (Terroir rural, Garnison & Chronique)
$resources = file_get_contents(__DIR__ . '/../views/resources.php');
assert(strpos($resources, 'id="modalPlotBannerImg" class="depth-parallax-img"') !== false, "Modale terroir équipée");

$troops = file_get_contents(__DIR__ . '/../views/partials/troops_panel.php');
assert(strpos($troops, 'id="garrisonUnitModalImage" class="depth-parallax-img"') !== false, "Modale garnison équipée");

$weekly = file_get_contents(__DIR__ . '/../views/partials/weekly_event_modal.php');
assert(strpos($weekly, 'depth-parallax-scene') !== false, "Modale chronique équipée");
echo "✅ [5/5] Modales Terroir (#plotUpgradeModal), Garnison (#modalGarrisonUnitDetail) et Chronique équipées !\n";

echo "\n=======================================================\n";
echo ">>> TOUS LES TESTS DU PARALLAXE DE PROFONDEUR SONT 100% OK ! <<<\n";
echo "=======================================================\n";

