<?php
/**
 * Test : Vérification des illustrations haute définition dans la modale de la carte
 * Fichier : tests/test_map_modal_illustrations.php
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
echo " TEST : INTÉGRATION DES ILLUSTRATIONS HD DANS LA MODALE DE CARTE" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

$mapAssetsDir = dirname(__DIR__) . '/public/assets/map';
$expectedIllustrations = [
    'collines.jpg'      => 'Collines Ondoyantes & Terres d\'Argile',
    'dojo_imperial.jpg' => 'Dōjō Impérial Éveillé [0:0]',
    'donjon.jpg'        => 'Donjon Authentique Historique (12 Trésors)',
    'fief.jpg'          => 'Fief Castral de Daimyō',
    'foret.jpg'         => 'Forêt Féodale Sauvage (Sugi)',
    'lac.jpg'           => 'Lac d\'Eaux Vives & Sanctuaire Halieutique',
    'oasis.jpg'         => 'Oasis Naturelle & Terroir Sauvage',
    'pics.jpg'          => 'Pics Rocheux & Montagnes Minérales',
    'plaines.jpg'       => 'Plaines Fertiles & Terres Vierges',
    'terre_sacree.jpg'  => 'Terre Sacrée Scellée [0:0] de Kyōto'
];

echo "[TEST 1] Présence et intégrité des 10 fichiers d'illustration dans public/assets/map/..." . PHP_EOL;
foreach ($expectedIllustrations as $file => $label) {
    $path = $mapAssetsDir . '/' . $file;
    assertTest(file_exists($path), "Illustration présente : $file ($label)");
    $size = filesize($path);
    assertTest($size > 500000, "Taille HD de $file : " . round($size / 1024) . " Ko (haute résolution)");
}

echo PHP_EOL . "[TEST 2] Vérification de la fonction getMapModalIllustration() dans views/map.php..." . PHP_EOL;
$mapView = file_get_contents(dirname(__DIR__) . '/views/map.php');

assertTest(str_contains($mapView, 'function getMapModalIllustration(data)'), "Fonction getMapModalIllustration définie");
assertTest(str_contains($mapView, 'terre_sacree.jpg'), "Modale : Terre Sacrée Scellée utilise terre_sacree.jpg");
assertTest(str_contains($mapView, 'dojo_imperial.jpg'), "Modale : Dōjō Impérial Éveillé utilise dojo_imperial.jpg");
assertTest(str_contains($mapView, 'donjon.jpg'), "Modale : Donjons Authentiques utilise donjon.jpg");
assertTest(str_contains($mapView, 'fief.jpg'), "Modale : Fief de Daimyō utilise fief.jpg");
assertTest(str_contains($mapView, 'oasis.jpg'), "Modale : Oasis Naturelle utilise oasis.jpg");
assertTest(str_contains($mapView, 'lac.jpg'), "Modale : Lac d'Eaux Vives utilise lac.jpg");
assertTest(str_contains($mapView, 'pics.jpg'), "Modale : Pics Rocheux utilise pics.jpg");
assertTest(str_contains($mapView, 'foret.jpg'), "Modale : Forêt de Cèdres utilise foret.jpg");
assertTest(str_contains($mapView, 'collines.jpg'), "Modale : Collines & Coteaux utilise collines.jpg");
assertTest(str_contains($mapView, 'plaines.jpg'), "Modale : Plaines Fertiles utilise plaines.jpg");

echo PHP_EOL . "[TEST 3] Vérification du bandeau illustré en haut de modale (modèle identique Pêcheur & Terroir)..." . PHP_EOL;
assertTest(str_contains($mapView, 'id="mapModalBannerImg"'), "Image du bandeau #mapModalBannerImg présente");
assertTest(str_contains($mapView, 'id="mapModalCategoryBadge"'), "Badge de catégorie #mapModalCategoryBadge présent");
assertTest(str_contains($mapView, 'id="mapModalCoords"'), "Badge de coordonnées #mapModalCoords présent");
assertTest(str_contains($mapView, 'function setMapModalHeader('), "Fonction de mise à jour de l'en-tête setMapModalHeader définie");
assertTest(!str_contains($mapView, 'width:68px; height:68px;'), "Anciennes vignettes latérales 68x68px supprimées");
assertTest(str_contains($mapView, 'object-fit: cover;'), "Mise à l'échelle propre des images via object-fit: cover");

echo PHP_EOL . "[TEST 4] Vérification de l'alignement des modales Expédition Pêche & Mines..." . PHP_EOL;
assertTest(str_contains($mapView, 'id="mapFishingModal"'), "Modale d'expédition de pêche #mapFishingModal présente");
assertTest(str_contains($mapView, '/public/assets/units/pecheur.jpg'), "Illustration d'en-tête pecheur.jpg dans #mapFishingModal");
assertTest(str_contains($mapView, 'id="mapMiningModal"'), "Modale d'expédition minière #mapMiningModal présente");
assertTest(str_contains($mapView, '/public/assets/units/mineur.jpg'), "Illustration d'en-tête mineur.jpg dans #mapMiningModal");

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS D'ILLUSTRATION DE LA MODALE SONT 100% OK !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;
