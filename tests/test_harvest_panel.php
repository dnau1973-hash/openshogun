<?php
/**
 * Test de rendu unitaire pour rural_harvest_resources_panel.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/RuralPlotEngine.php';
require_once __DIR__ . '/../core/OasisEngine.php';

$sqlite = new PDO('sqlite::memory:');
$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (setting_key VARCHAR(50), setting_value VARCHAR(100), setting_type VARCHAR(20))");
Database::setConnection($sqlite);

$planet = [
    'id' => 1,
    'user_id' => 1,
    'name' => 'Fief Test',
    'metal' => 1250,
    'metal_max' => 15000,
    'crystal' => 840,
    'crystal_max' => 15000,
    'deuterium' => 310,
    'deuterium_max' => 15000,
    'rice_flour' => 450,
    'rice_flour_max' => 10000,
    'sake' => 120,
    'sake_max' => 10000,
    'wooden_beams' => 60,
    'wooden_beams_max' => 10000,
    'population' => 42,
    'population_max' => 100,
    'contentment' => 85,
    'energy_used' => 12,
    'energy_max' => 28,
    'famine_active' => 0
];

$buildings = [
    'hq' => 1,
    'sawmill' => 1,
    'stonemason' => 0,
    'grain_mill' => 1,
    'teahouse' => 0
];
$maxPop = 100;

$plots = [
    'foret' => ['name' => 'Forêt de Cèdres', 'level' => 2, 'max_level' => 30, 'prod_hourly' => 45, 'workers_assigned' => 4],
    'carriere' => ['name' => 'Carrière de Granit', 'level' => 1, 'max_level' => 30, 'prod_hourly' => 30, 'workers_assigned' => 2],
    'riziere' => ['name' => 'Rizière Inondée', 'level' => 1, 'max_level' => 30, 'prod_hourly' => 25, 'workers_assigned' => 2],
    'fosse_argile' => ['name' => 'Fosse d\'Argile', 'level' => 2, 'max_level' => 25, 'prod_hourly' => 20, 'workers_assigned' => 4],
    'culture_the' => ['name' => 'Coteaux de Théiers', 'level' => 1, 'max_level' => 25, 'prod_hourly' => 15, 'workers_assigned' => 2],
    'champ_soja' => ['name' => 'Champ de Soja', 'level' => 1, 'max_level' => 25, 'prod_hourly' => 15, 'workers_assigned' => 2],
    'sanctuaire_shinto' => ['name' => 'Sanctuaire Shintō', 'level' => 1, 'max_level' => 30, 'prod_hourly' => 8, 'workers_assigned' => 1],
    'village' => ['name' => 'Village Minka', 'level' => 1, 'max_level' => 30, 'prod_hourly' => 0, 'workers_assigned' => 2],
    'tenshu' => ['name' => 'Donjon Tenshu', 'level' => 1, 'max_level' => 20, 'prod_hourly' => 0, 'workers_assigned' => 2]
];

$prodRates = [
    'metal' => 265,
    'crystal' => 180,
    'deuterium' => 145,
    'energy_max' => 28,
    'energy_used' => 12,
    'energy_ratio' => 1.0,
    'workforce_ratio' => 1.0,
    'workforce' => [
        'total_population' => 42,
        'max_population' => 100,
        'required_workers' => 21,
        'assigned_workers' => 21,
        'idle_workers' => 21,
        'workforce_ratio' => 1.0,
        'unemployment_pct' => 50.0
    ],
    'oasis_bonuses' => ['wood' => 0, 'stone' => 0, 'rice' => 25],
    'hero_bonuses' => ['metal' => 120, 'crystal' => 120, 'deuterium' => 120],
    'matsuri_bonus' => 0,
    'building_bonuses' => [
        'sawmill' => 5,
        'stonemason' => 0,
        'grain_mill' => 5,
        'teahouse' => 0
    ]
];

$annexedOases = [
    ['id' => 10, 'name' => 'Oasis des Bambous', 'coord_x' => 502, 'coord_y' => 501, 'bonus_rice' => 25]
];

ob_start();
include __DIR__ . '/../views/partials/rural_harvest_resources_panel.php';
$outputHarvest = ob_get_clean();

$checksHarvest = [
    'Bois de Cèdre' => 'Matières premières (Bois)',
    'Pierre de Taille' => 'Matières premières (Pierre)',
    'Riz Impérial (Koku)' => 'Matières premières (Riz)',
    'Argile & Céramique' => 'Spécialités rurales (Argile)',
    'Thé & Matcha' => 'Spécialités rurales (Thé)',
    'Fèves de Soja' => 'Spécialités rurales (Soja)',
    'Farine de Riz' => 'Vivres transformées (Farine)',
    'Saké Féodal' => 'Vivres transformées (Saké)',
    'Poutres de Charpente' => 'Matériaux transformés (Poutres)',
    'Ferveur Divine' => 'Facteur moteur (Énergie / Sérénité)',
    'Modificateurs &amp; Bonus Actifs' => 'Matrice des modificateurs',
    'Héros :' => 'Bénédiction du Héros Samouraï',
    'Scierie +' => 'Bonus d\'atelier (Scierie)',
    'Meunerie +' => 'Bonus d\'atelier (Meunerie)',
    'Oasis des Bambous' => 'Oasis annexées sous tutelle',
    '100% Rendement' => 'Indicateur d\'efficacité globale'
];

echo "=== VÉRIFICATION DU WIDGET RÉCOLTES, STOCKS & OASIS ===\n";
$allOk = true;
foreach ($checksHarvest as $needle => $label) {
    $found = (strpos($outputHarvest, $needle) !== false);
    echo ($found ? "[OK] " : "[ERREUR] ") . $label . " : " . ($found ? "Présent" : "MANQUANT") . "\n";
    if (!$found) $allOk = false;
}

ob_start();
include __DIR__ . '/../views/partials/workforce_demographics_panel.php';
$outputWorkforce = ob_get_clean();

$checksWorkforce = [
    'Capacité d\'Habitations' => 'Logements villageois & Minka',
    'Mobilisation Ouvrière' => 'Mobilisation & Quotas ouvriers',
    'Décomposition des Postes Requis' => 'Section de contrôle des calculs',
    'Terroir Rural (9 Parcelles)' => 'Sous-total secteur rural',
    'Cité Castrale (Édifices Urbains)' => 'Sous-total secteur urbain',
    'Total des Besoins en Main-d\'œuvre' => 'Formule totale consolidée'
];

echo "\n=== VÉRIFICATION DU WIDGET MAIN-D'ŒUVRE & DÉMOGRAPHIE ===\n";
foreach ($checksWorkforce as $needle => $label) {
    $found = (strpos($outputWorkforce, $needle) !== false);
    echo ($found ? "[OK] " : "[ERREUR] ") . $label . " : " . ($found ? "Présent" : "MANQUANT") . "\n";
    if (!$found) $allOk = false;
}

if ($allOk) {
    echo "\n>>> SUCCÈS TOTAL : Tous les éléments de production et de main-d'œuvre sont parfaitement intégrés et vérifiés !\n";
} else {
    echo "\n>>> ÉCHEC : Des éléments sont manquants.\n";
}
