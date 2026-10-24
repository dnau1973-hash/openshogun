<?php
/**
 * Test Automatisé : Validation de la Dissémination Homogène des Oasis
 * Vérifie l'absence de recentrage, la présence d'oasis jusqu'aux frontières du monde,
 * le respect de la distance minimale répulsive et l'équilibre entre quadrants.
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

echo "=== TEST DE DISSÉMINATION HOMOGÈNE DES OASIS ===\n\n";

$db = Database::getConnection();
$oasisEngine = new OasisEngine($db);

$radius = 35; // Rayon officiel du monde féodal
$density = 2.0;

echo "1. Exécution de la génération homogène (Rayon: $radius, Densité: $density%)...\n";
$res = $oasisEngine->spawnOasesByDensity($density, $radius, true);

assert($res['success'] === true, "La génération homogène doit réussir.");
$totalSpawned = $res['total_now'];
echo "   ✓ {$totalSpawned} oasis générées sur la carte.\n";
assert($totalSpawned >= 40, "Au moins 40 oasis doivent être générées pour une densité de 2%.");

// 2. Vérification des coordonnées et de l'exclusion du centre impérial
echo "2. Vérification des bornes et du centre impérial [0:0]...\n";
$stmt = $db->query("SELECT id, name, coord_x, coord_y FROM oases");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quadrants = ['NO' => 0, 'NE' => 0, 'SO' => 0, 'SE' => 0];
$frontierCount = 0; // Au-delà de 60% du rayon (|coord| >= 21)
$coordsList = [];

foreach ($all as $o) {
    $x = (int)$o['coord_x'];
    $y = (int)$o['coord_y'];
    
    assert(!($x === 0 && $y === 0), "Aucune oasis ne doit occuper le centre impérial [0:0].");
    assert(abs($x) <= $radius, "La coordonnée X ($x) doit être comprise dans [-$radius, $radius].");
    assert(abs($y) <= $radius, "La coordonnée Y ($y) doit être comprise dans [-$radius, $radius].");

    if ($x <= 0 && $y >= 0) $quadrants['NO']++;
    elseif ($x >= 0 && $y >= 0) $quadrants['NE']++;
    elseif ($x <= 0 && $y <= 0) $quadrants['SO']++;
    else $quadrants['SE']++;

    if (abs($x) >= 21 || abs($y) >= 21) {
        $frontierCount++;
    }

    $coordsList[] = [$x, $y, $o['name']];
}

echo "   ✓ Aucune collision avec le centre [0:0]. Toutes les oasis respectent les bornes [-$radius : $radius].\n";

// 3. Vérification de la présence aux frontières périphériques (évite le recentrage au cœur)
echo "3. Vérification de la couverture des frontières extérieures (|coord| >= 21)...\n";
$frontierRatio = round(($frontierCount / count($all)) * 100, 1);
echo "   - Oasis en zone frontière / provinces extérieures : $frontierCount / " . count($all) . " ({$frontierRatio}%).\n";
assert($frontierCount >= 15, "Au moins 15 oasis doivent être situées dans les provinces extérieures/frontières.");
echo "   ✓ Couverture périphérique validée (fin du désert aux frontières).\n";

// 4. Vérification de l'équilibre des 4 quadrants
echo "4. Vérification de l'équilibre entre les 4 quadrants géographiques...\n";
foreach ($quadrants as $q => $cnt) {
    $ratio = round(($cnt / count($all)) * 100, 1);
    echo "   - Quadrant $q : $cnt oasis ({$ratio}%)\n";
    assert($ratio >= 15.0 && $ratio <= 35.0, "Chaque quadrant doit contenir entre 15% et 35% des oasis.");
}
echo "   ✓ Équilibre parfait entre les 4 points cardinaux.\n";

// 5. Vérification de la distance minimale (pas d'oasis collées à distance < 2)
echo "5. Vérification de la distance minimale entre oasis...\n";
$violations = 0;
for ($i = 0; $i < count($coordsList); $i++) {
    for ($j = $i + 1; $j < count($coordsList); $j++) {
        $dx = $coordsList[$i][0] - $coordsList[$j][0];
        $dy = $coordsList[$i][1] - $coordsList[$j][1];
        $dist = sqrt($dx * $dx + $dy * $dy);
        if ($dist < 2.0) {
            $violations++;
            echo "   [AVERTISSEMENT] Oasis trop proches : {$coordsList[$i][2]} [{$coordsList[$i][0]}:{$coordsList[$i][1]}] et {$coordsList[$j][2]} [{$coordsList[$j][0]}:{$coordsList[$j][1]}] (dist: " . round($dist, 2) . ")\n";
        }
    }
}
assert($violations === 0, "Aucune paire d'oasis ne doit être adjacente à moins de 2 tuiles.");
echo "   ✓ Distance répulsive minimale respectée (zéro cluster/agglutinement).\n";

// 6. Test du respawn en province périphérique
echo "6. Test du respawn équilibré en province extérieure (capture à [28, 28])...\n";
$respawn = $oasisEngine->spawnReplacementWildOasis(28, 28);
assert($respawn !== null, "Le respawn d'oasis doit réussir.");
$rx = (int)$respawn['coord_x'];
$ry = (int)$respawn['coord_y'];
echo "   - Nouvelle oasis apparue en [$rx : $ry] suite à la capture en [28 : 28].\n";
assert(abs($rx) > 5 || abs($ry) > 5, "La réapparition ne doit pas s'écrouler vers le centre.");
echo "   ✓ Respawn régional sans dérive centripète validé.\n";

// Nettoyage de l'oasis de respawn de test
$db->exec("DELETE FROM oases WHERE id = {$respawn['id']}");

echo "\n=== TOUS LES TESTS DE DISSÉMINATION HOMOGÈNE SONT VALIDÉS AVEC SUCCÈS ! ===\n";
