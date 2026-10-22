<?php
/**
 * Test de validation du Boîtier de Navigation Féodale Unifié (Bas à Droite)
 * Vérifie que la Mini-Map est au cœur du boîtier et que les flèches directionnelles sont à l'extérieur.
 */

echo "=== Test 1 : Vérification de la structure HTML dans public/js/galaxy_map.js ===\n";
$jsPath = __DIR__ . '/../public/js/galaxy_map.js';
assert(file_exists($jsPath), "public/js/galaxy_map.js introuvable");
$jsContent = file_get_contents($jsPath);

assert(str_contains($jsContent, 'class="map-unified-nav-pad"'), "Boîtier .map-unified-nav-pad manquant dans galaxy_map.js");
assert(str_contains($jsContent, 'class="nav-pad-minimap-core"'), "Cœur de la mini-map .nav-pad-minimap-core manquant");

// Vérification des 4 flèches extérieures
assert(str_contains($jsContent, 'class="nav-pad-arrow arrow-up"'), "Flèche extérieure Nord (arrow-up) manquante");
assert(str_contains($jsContent, 'class="nav-pad-arrow arrow-down"'), "Flèche extérieure Sud (arrow-down) manquante");
assert(str_contains($jsContent, 'class="nav-pad-arrow arrow-left"'), "Flèche extérieure Ouest (arrow-left) manquante");
assert(str_contains($jsContent, 'class="nav-pad-arrow arrow-right"'), "Flèche extérieure Est (arrow-right) manquante");

// Vérification des 4 quadrants dans le cœur de la mini-map
assert(str_contains($jsContent, 'id="btnQuadrantNO"'), "Quadrant Nord-Ouest manquant");
assert(str_contains($jsContent, 'id="btnQuadrantNE"'), "Quadrant Nord-Est manquant");
assert(str_contains($jsContent, 'id="btnQuadrantSO"'), "Quadrant Sud-Ouest manquant");
assert(str_contains($jsContent, 'id="btnQuadrantSE"'), "Quadrant Sud-Est manquant");
assert(str_contains($jsContent, 'id="btnQuadrantKyoto"'), "Pastille Centre Kyoto manquante");

// Vérification des commandes périphériques
assert(str_contains($jsContent, 'id="btnZoomIn"'), "Bouton Zoom In manquant");
assert(str_contains($jsContent, 'id="btnZoomOut"'), "Bouton Zoom Out manquant");
assert(str_contains($jsContent, 'id="btnRecenterColonyDpad"'), "Bouton Recentrer Mon Fief manquant");

// Vérification que le haut est libéré (pas de widget résiduel)
assert(!str_contains($jsContent, 'class="galaxy-minimap-widget"'), "L'ancien widget haut .galaxy-minimap-widget ne doit plus exister");
echo "✓ Structure HTML unifiée validée avec flèches extérieures !\n";

echo "\n=== Test 2 : Vérification des styles CSS dans public/css/style.css ===\n";
$cssPath = __DIR__ . '/../public/css/style.css';
$cssContent = file_get_contents($cssPath);

assert(str_contains($cssContent, '.map-unified-nav-pad'), "Style .map-unified-nav-pad manquant");
assert(str_contains($cssContent, '.nav-pad-arrow'), "Style .nav-pad-arrow manquant");
assert(str_contains($cssContent, '.arrow-up'), "Style .arrow-up manquant");
assert(str_contains($cssContent, '.arrow-down'), "Style .arrow-down manquant");
assert(str_contains($cssContent, '.arrow-left'), "Style .arrow-left manquant");
assert(str_contains($cssContent, '.arrow-right'), "Style .arrow-right manquant");
assert(str_contains($cssContent, '.nav-pad-minimap-core'), "Style .nav-pad-minimap-core manquant");
assert(str_contains($cssContent, '.minimap-q-cell'), "Style .minimap-q-cell manquant");
assert(str_contains($cssContent, '.minimap-center-torii'), "Style .minimap-center-torii manquant");
echo "✓ Styles CSS de la grille boussole validés !\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS DU BOÎTIER DE NAVIGATION SONT VALIDÉS AVEC SUCCÈS !\n";
echo "============================================\n";
