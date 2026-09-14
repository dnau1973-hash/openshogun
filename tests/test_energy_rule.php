<?php
/**
 * Test unitaire : Règle des 10% en cas d'énergie négative
 */
require_once __DIR__ . '/../core/PlanetEngine.php';

$pe = new PlanetEngine();

// Cas 1 : Énergie positive (1 centrale niv 10, 1 mine niv 1)
$fieldsPos = [
    ['field_slot' => 15, 'type' => 'solar_plant', 'level' => 10],
    ['field_slot' => 1, 'type' => 'metal_mine', 'level' => 1]
];
$prodPos = $pe->calculateProduction($fieldsPos);
echo "Cas Énergie Positive :\n";
echo "- Ratio : {$prodPos['energy_ratio']} (Attendu: 1.0)\n";
echo "- Production Métal : {$prodPos['metal']}/h\n";
echo "- Énergie : {$prodPos['energy_used']} / {$prodPos['energy_max']} (Disponible: " . ($prodPos['energy_max'] - $prodPos['energy_used']) . ")\n\n";

// Cas 2 : Énergie négative (Pas de centrale, grosses mines qui consomment)
$fieldsNeg = [
    ['field_slot' => 1, 'type' => 'metal_mine', 'level' => 8],
    ['field_slot' => 2, 'type' => 'metal_mine', 'level' => 8],
    ['field_slot' => 6, 'type' => 'crystal_mine', 'level' => 8],
    ['field_slot' => 11, 'type' => 'deuterium_synth', 'level' => 8]
];
$prodNeg = $pe->calculateProduction($fieldsNeg);
echo "Cas Énergie Négative (Déficit) :\n";
echo "- Ratio : {$prodNeg['energy_ratio']} (Attendu: 0.1)\n";
echo "- Production Métal : {$prodNeg['metal']}/h\n";
echo "- Énergie : {$prodNeg['energy_used']} / {$prodNeg['energy_max']} (Déficit: " . ($prodNeg['energy_max'] - $prodNeg['energy_used']) . ")\n";

assert($prodPos['energy_ratio'] == 1.0, "Ratio positif incorrect");
assert($prodNeg['energy_ratio'] == 0.10, "Ratio négatif incorrect (doit être 0.10)");
echo "\n✔ Test réussi : Quand l'énergie est négative, la production est strictement limitée à 10% !\n";

