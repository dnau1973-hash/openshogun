<?php
/**
 * Test de validation de la Mini-Map interactive des quadrants et des contrôles de zoom
 * OpenShogun - Coin supérieur droit de la carte
 */

echo "=== Test 1 : Vérification du composant Mini-Map dans public/js/galaxy_map.js ===\n";
$jsPath = __DIR__ . '/../public/js/galaxy_map.js';
assert(file_exists($jsPath), "public/js/galaxy_map.js introuvable");
$jsContent = file_get_contents($jsPath);

assert(str_contains($jsContent, 'class="galaxy-minimap-widget"'), "Widget .galaxy-minimap-widget manquant dans galaxy_map.js");
assert(str_contains($jsContent, 'id="btnQuadrantNO"'), "Bouton Quadrant Nord-Ouest manquant");
assert(str_contains($jsContent, 'id="btnQuadrantNE"'), "Bouton Quadrant Nord-Est manquant");
assert(str_contains($jsContent, 'id="btnQuadrantSO"'), "Bouton Quadrant Sud-Ouest manquant");
assert(str_contains($jsContent, 'id="btnQuadrantSE"'), "Bouton Quadrant Sud-Est manquant");
assert(str_contains($jsContent, 'id="btnQuadrantKyoto"'), "Pastille Centre Impérial manquante");
assert(str_contains($jsContent, 'id="btnZoomIn"'), "Bouton Zoom In manquant");
assert(str_contains($jsContent, 'id="btnZoomOut"'), "Bouton Zoom Out manquant");

// Vérifier que le bouton Mon Fief n'est plus dans le HUD haut
assert(!str_contains($jsContent, 'id="btnRecenterColony"'), "Le bouton 'Mon Fief' (btnRecenterColony) ne doit plus exister dans le HUD haut");

// Vérifier la méthode d'illumination du quadrant actif
assert(str_contains($jsContent, 'updateMinimapActiveQuadrant'), "Méthode updateMinimapActiveQuadrant manquante dans galaxy_map.js");
echo "✓ galaxy_map.js intègre parfaitement la Mini-Map et ses commandes !\n";

echo "\n=== Test 2 : Vérification des styles CSS dans public/css/style.css ===\n";
$cssPath = __DIR__ . '/../public/css/style.css';
$cssContent = file_get_contents($cssPath);

assert(str_contains($cssContent, '.galaxy-minimap-widget'), "Style .galaxy-minimap-widget manquant");
assert(str_contains($cssContent, '.minimap-quadrants-grid'), "Style .minimap-quadrants-grid manquant");
assert(str_contains($cssContent, '.minimap-quadrant-btn'), "Style .minimap-quadrant-btn manquant");
assert(str_contains($cssContent, '.minimap-center-node'), "Style .minimap-center-node manquant");
assert(str_contains($cssContent, '.minimap-zoom-btn'), "Style .minimap-zoom-btn manquant");
assert(str_contains($cssContent, '.minimap-quadrant-btn.active'), "Style .minimap-quadrant-btn.active manquant");
echo "✓ Les styles CSS féodaux de la Mini-Map sont en place !\n";

echo "\n=== Test 3 : Vérification du nettoyage dans views/map.php ===\n";
$mapContent = file_get_contents(__DIR__ . '/../views/map.php');
assert(!str_contains($mapContent, 'onclick="galaxyMap.moveTo(-16, 16)"'), "Les anciens boutons de quadrants redondants doivent être supprimés de views/map.php");
echo "✓ views/map.php a été épuré avec succès !\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS MINI-MAP SONT VALIDÉS AVEC SUCCÈS !\n";
echo "============================================\n";
