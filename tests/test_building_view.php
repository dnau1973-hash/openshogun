<?php
/**
 * Test de la vue dédiée d'amélioration des bâtiments de la cité (views/building.php)
 * Style Travian build.php pour les emplacements urbains 19 à 34.
 */

require_once __DIR__ . '/../config/game_constants.php';

echo "=== TEST VUE DÉDIÉE BÂTIMENTS URBAINS (BUILDING.PHP) ===\n";

// Simuler l'environnement utilisateur et planète
class TestAuthStub {
    public function getCurrentUser() {
        return ['id' => 1, 'username' => 'Daimyo_Oda', 'faction' => 'terran'];
    }
    public function getCurrentPlanet() {
        return [
            'id' => 1,
            'name' => 'Fief d\'Azuchi',
            'metal' => 150000,
            'crystal' => 120000,
            'deuterium' => 90000,
            'prod_rates' => ['metal' => 2500, 'crystal' => 1800, 'deuterium' => 1000]
        ];
    }
}

class TestPlanetEngineStub {
    public function getBuildings(int $planetId): array {
        return [
            'hq' => 10,
            'shipyard' => 5,
            'barracks' => 6,
            'radar' => 3,
            'wall' => 4,
            'research_lab' => 7,
            'storage' => 8,
            'tank' => 8,
            'quantum_vault' => 5,
            'market' => 2,
            'embassy' => 1
        ];
    }
    public function getCitySlotMap(int $planetId): array {
        $slots = [];
        for ($s = 19; $s <= 34; $s++) {
            $slots[$s] = [
                'slot' => $s,
                'code' => ($s === 19) ? 'hq' : (($s === 20) ? 'barracks' : 'free_plot'),
                'level' => ($s === 19) ? 10 : (($s === 20) ? 6 : 0)
            ];
        }
        return $slots;
    }
}

class TestBuildingEngineStub {
    public function getQueue(int $planetId): array {
        return [];
    }
    public function getUpgradeDetails(string $category, string $code, int $lvl, int $hqLevel): array {
        $bConf = BUILDINGS[$code] ?? null;
        $targetLevel = $lvl + 1;
        $mult = pow($bConf['cost_multiplier'] ?? 1.5, $lvl);
        return [
            'target_level' => $targetLevel,
            'cost' => [
                'metal' => (int)(($bConf['base_cost']['metal'] ?? 100) * $mult),
                'crystal' => (int)(($bConf['base_cost']['crystal'] ?? 100) * $mult),
                'deuterium' => (int)(($bConf['base_cost']['deuterium'] ?? 50) * $mult),
            ],
            'duration' => max(5, (int)(($bConf['base_time'] ?? 30) * $targetLevel / (1 + $hqLevel * 0.25)))
        ];
    }
}

// Remplacer dynamiquement l'inclusion pour tester views/building.php
// Nous créons un fichier de test wrapper qui injecte les stubs
$buildingViewCode = file_get_contents(__DIR__ . '/../views/building.php');
// Remplacer l'instanciation des moteurs réels par nos stubs
$mockedViewCode = str_replace(
    ['$auth = new Auth();', '$planetEngine = new PlanetEngine();', '$buildingEngine = new BuildingEngine();'],
    ['$auth = new TestAuthStub();', '$planetEngine = new TestPlanetEngineStub();', '$buildingEngine = new TestBuildingEngineStub();'],
    $buildingViewCode
);

// Tester les slots 19 à 34
for ($slot = 19; $slot <= 34; $slot++) {
    $_GET['page'] = 'building';
    $_GET['slot'] = $slot;
    unset($_GET['code']);

    ob_start();
    eval('?>' . $mockedViewCode);
    $output = ob_get_clean();

    $code = CITY_SLOT_LAYOUT[$slot];

    assert(strpos($output, 'field-hero-card') !== false, "Slot #$slot ($code) : Carte héroïque manquante");
    assert(strpos($output, 'shogun_castle_city_bg.jpg') !== false, "Slot #$slot ($code) : Fond de cité manquant");
    assert(strpos($output, 'Retour à la Cité Castrale') !== false, "Slot #$slot ($code) : Bouton de retour manquant");
    assert(strpos($output, 'Slot Castral #' . $slot) !== false, "Slot #$slot ($code) : Badge indicateur de slot manquant");

    if ($code === 'free_plot') {
        assert(strpos($output, 'TERRAIN') !== false, "Slot #$slot : Devrait être un terrain libre");
        assert(strpos($output, 'Emplacement Disponible') !== false, "Slot #$slot : Badge disponible manquant");
        echo "✓ Slot #$slot : Emplacement Libre viabilisé validé\n";
    } else {
        $bInfo = BUILDINGS[$code];
        assert(strpos($output, htmlspecialchars($bInfo['name'])) !== false, "Slot #$slot : Nom du bâtiment manquant ({$bInfo['name']})");
        assert(strpos($output, 'field-tile-img') !== false, "Slot #$slot : Illustration tuile manquante");
        assert(strpos($output, 'Coûts d\'Amélioration') !== false, "Slot #$slot : Section des coûts manquante");
        assert(strpos($output, 'Décret de Construction Castral') !== false, "Slot #$slot : Section décret manquante");
        echo "✓ Slot #$slot [{$bInfo['name']}] : Page complète validée\n";
    }
}

// Test spécifique de la muraille féodale (Slot 34 / code 'wall')
$_GET['slot'] = 34;
ob_start();
eval('?>' . $mockedViewCode);
$wallOutput = ob_get_clean();

assert(strpos($wallOutput, 'Muraille & Remparts') !== false, "Muraille : Titre manquant");
assert(strpos($wallOutput, 'tile_wall.png') !== false, "Muraille : Tuile tile_wall.png manquante");
assert(strpos($wallOutput, 'Bonus Défense Garnison') !== false, "Muraille : Statistique de défense garnison manquante");
assert(strpos($wallOutput, '+16% (+100 pts mur)') !== false, "Muraille : Calcul niveau 4 erroné");
echo "✓ Muraille féodale (Slot 34) : Bonus défensif et points de structure validés\n";

echo "\n=== TOUS LES TESTS DE LA VUE BÂTIMENT (BUILDING.PHP) SONT VALIDES ! ===\n";

