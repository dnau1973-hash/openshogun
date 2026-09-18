<?php
require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/CombatEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';

echo "=== TEST 1: BUILDINGS Configuration & Upgrade Details ===\n";
$bEngine = new BuildingEngine();
$newBuildings = ['sawmill', 'stonemason', 'grain_mill', 'blacksmith', 'teahouse', 'tournament_square'];

foreach ($newBuildings as $code) {
    if (!isset(BUILDINGS[$code])) {
        throw new Exception("Missing building constant: $code");
    }
    $info = BUILDINGS[$code];
    $det0 = $bEngine->getUpgradeDetails('building', $code, 0, 1);
    $det1 = $bEngine->getUpgradeDetails('building', $code, 1, 1);
    
    echo sprintf("  [OK] %-18s | %-45s | Lvl 1: Wood=%-4d Stone=%-4d Rice=%-4d Time=%ds\n", 
        $code, $info['name'], $det0['cost']['metal'], $det0['cost']['crystal'], $det0['cost']['deuterium'], $det0['duration']);
}

echo "\n=== TEST 2: Asset Verification ===\n";
foreach (BUILDINGS as $code => $info) {
    $file = __DIR__ . '/../public/assets/' . $info['tile_img'];
    if (!file_exists($file) || filesize($file) < 1000) {
        throw new Exception("Missing or invalid asset for $code: {$info['tile_img']}");
    }
    $size = getimagesize($file);
    echo sprintf("  [OK] Asset: %-28s (%dx%d, %d KB)\n", $info['tile_img'], $size[0], $size[1], round(filesize($file)/1024));
}

echo "\n=== TEST 3: Resource Production Bonus Calculations ===\n";
$pEngine = new PlanetEngine();
$dummyFields = [
    ['field_slot' => 1, 'type' => 'metal_mine', 'level' => 10],
    ['field_slot' => 2, 'type' => 'crystal_mine', 'level' => 10],
    ['field_slot' => 3, 'type' => 'deuterium_synth', 'level' => 10],
    ['field_slot' => 4, 'type' => 'solar_plant', 'level' => 10],
];
$baseProd = $pEngine->calculateProduction($dummyFields, null);
echo sprintf("  Base yields without bonuses: Wood=%d/h, Stone=%d/h, Rice=%d/h, Energy=%d\n", 
    $baseProd['metal'], $baseProd['crystal'], $baseProd['deuterium'], $baseProd['energy_max']);

echo "\n=== TEST 4: CITY_SLOT_LAYOUT Completeness ===\n";
for ($s = 19; $s <= 34; $s++) {
    if (!isset(CITY_SLOT_LAYOUT[$s])) {
        throw new Exception("Missing slot $s in CITY_SLOT_LAYOUT");
    }
    $bCode = CITY_SLOT_LAYOUT[$s];
    echo sprintf("  Slot %2d: %-18s (%s)\n", $s, $bCode, BUILDINGS[$bCode]['name'] ?? 'Inconnu');
}

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
