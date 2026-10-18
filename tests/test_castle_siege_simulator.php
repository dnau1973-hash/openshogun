<?php
/**
 * Test unitaire et d'intégration pour le Simulateur de Siège des 12 Châteaux Authentiques
 * Fichier : tests/test_castle_siege_simulator.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/game_constants.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== TEST SIMULATEUR DE SIÈGE DES 12 CHÂTEAUX AUTHENTIQUES ===\n\n";

// 1. Initialisation d'une base SQLite en mémoire pour isoler l'environnement de test
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

require_once __DIR__ . '/../core/CastleEngine.php';
require_once __DIR__ . '/../core/CastleSiegeSimulatorEngine.php';

$engine = new CastleSiegeSimulatorEngine($sqlite);

// 2. Validation de l'intégrité des 12 Châteaux Authentiques
echo "[1] Test catalogue des 12 châteaux authentiques (Genzon Jūni Tenshu)...\n";
$castles = $engine->getCastlesList();
assert(count($castles) === 12, "Il doit y avoir exactement 12 châteaux authentiques.");

$requiredKeys = [
    'code', 'name', 'japanese_name', 'kanji', 'province',
    'historical_builder', 'construction_year', 'classification',
    'relic_bonus', 'topography', 'image_src'
];

foreach ($castles as $code => $c) {
    foreach ($requiredKeys as $k) {
        assert(isset($c[$k]), "Clé manquante '$k' pour le château $code.");
    }
    // Vérification de la topographie
    $topo = $c['topography'];
    assert(isset($topo['type'], $topo['type_name'], $topo['altitude_m'], $topo['gate_layers'], $topo['wall_height_m'], $topo['cavalry_allowed']), "Topographie incomplète pour $code.");
}
echo "OK - 12 châteaux vérifiés avec métadonnées topographiques complètes.\n";

// 3. Contrôle des spécificités historiques et topographiques majeures
echo "[2] Test contraintes historiques spécifiques (Himeji, Bitchū Matsuyama, Marugame, Uwajima)...\n";
assert($castles['himeji']['topography']['gate_layers'] === 3, "Himeji doit posséder 3 couches de portes successives en labyrinthe.");
assert($castles['himeji']['topography']['cavalry_allowed'] === true, "Himeji autorise la cavalerie.");

assert($castles['bitchu_matsuyama']['topography']['type'] === 'yamajiro', "Bitchū Matsuyama est un Yamajiro.");
assert($castles['bitchu_matsuyama']['topography']['altitude_m'] === 430, "Bitchū Matsuyama culmine à 430m.");
assert($castles['bitchu_matsuyama']['topography']['cavalry_allowed'] === false, "Cavalerie interdite à Bitchū Matsuyama.");

assert($castles['marugame']['topography']['wall_height_m'] === 60, "Marugame possède les plus hautes murailles (60m).");
assert($castles['uwajima']['topography']['has_water_moat'] === true, "Uwajima possède des douves marines.");
echo "OK - Spécificités topographiques et interdictions de cavalerie validées.\n";

// 4. Validation des conditions météorologiques Sengoku
echo "[3] Test catalogue des conditions météorologiques Sengoku...\n";
$weathers = CastleSiegeSimulatorEngine::WEATHER_MODIFIERS;
assert(count($weathers) === 5, "5 conditions météo requises.");
assert(isset($weathers['clear'], $weathers['mist'], $weathers['rain'], $weathers['snow'], $weathers['night']), "Météos manquantes.");
assert($weathers['mist']['shinobi_mult'] > 1.0, "La brume doit avantager les shinobi.");
assert($weathers['rain']['tanegashima_mult'] < 1.0, "La pluie doit pénaliser la poudre.");
echo "OK - Modificateurs météorologiques validés.\n";

// 5. Test des Préréglages Historiques
echo "[4] Test des préréglages historiques Sengoku...\n";
$presets = $engine->getHistoricalPresets();
assert(count($presets) === 5, "5 préréglages historiques attendus.");
assert(isset($presets['himeji_grand_siege'], $presets['bitchu_mountain_mist'], $presets['hikone_red_devils']), "Préréglages spécifiques manquants.");
echo "OK - 5 préréglages historiques validés.\n";

// 6. Test d'une simulation unitaire de siège (Himeji)
echo "[5] Test simulation unitaire d'un siège (Himeji - Assaut frontal)...\n";
$assaultConfig = [
    'castle_code' => 'himeji',
    'weather' => 'clear',
    'tactics' => 'frontal_assault',
    'wall_level' => 12,
    'att_clan' => 'terran',
    'def_clan' => 'aethelis',
    'att_troops' => [
        'ashigaru' => 1500,
        'archer' => 600,
        'arquebusier' => 450,
        'samurai' => 400,
        'cavalry' => 200,
        'shinobi' => 80,
        'ram' => 4,
        'catapult' => 2
    ],
    'def_troops' => [
        'ashigaru' => 700,
        'archer' => 350,
        'arquebusier' => 300,
        'samurai' => 150,
        'shinobi' => 30
    ]
];

$resultHimeji = $engine->simulateSiege($assaultConfig);
assert($resultHimeji['success'] === true, "La simulation doit réussir.");
assert(isset($resultHimeji['status'], $resultHimeji['conquered'], $resultHimeji['final_phase']), "Statuts manquants.");
assert(in_array($resultHimeji['final_phase'], [1, 2, 3], true), "Phase finale invalide.");
assert(!empty($resultHimeji['phases']), "Les phases de siège doivent être consignées.");
assert(isset($resultHimeji['wall_metrics'], $resultHimeji['tenshu_metrics'], $resultHimeji['casualties']), "Métriques incomplètes.");
assert($resultHimeji['casualties']['attacker']['initial'] > 0, "Effectif attaquant initial invalide.");
assert($resultHimeji['casualties']['defender']['initial'] > 0, "Effectif défenseur initial invalide.");
echo "OK - Siège résolu (Résultat: " . $resultHimeji['status'] . ", Phase: " . $resultHimeji['final_phase'] . ", Conquis: " . ($resultHimeji['conquered'] ? 'OUI' : 'NON') . ").\n";

// 7. Test de la restriction stricte de cavalerie sur Yamajiro (Bitchū Matsuyama)
echo "[6] Test restriction cavalerie sur forteresse de montagne (Bitchū Matsuyama)...\n";
$bitchuConfig = $assaultConfig;
$bitchuConfig['castle_code'] = 'bitchu_matsuyama';
$bitchuConfig['att_troops']['cavalry'] = 300;
$resultBitchu = $engine->simulateSiege($bitchuConfig);
assert($resultBitchu['success'] === true, "Simulation Bitchū doit réussir.");
assert($resultBitchu['casualties']['attacker']['breakdown_initial']['cavalry'] === 0, "La cavalerie doit être neutralisée à 0 sur yamajiro.");
echo "OK - Règle Yamajiro appliquée : Cavalerie neutralisée à 0 sur sentier d'escarpement.\n";

// 8. Test du Moteur Monte-Carlo (100 itérations stochastiques)
echo "[7] Test simulation stochastique Monte-Carlo (100 itérations sur Hikone)...\n";
$mcConfig = $assaultConfig;
$mcConfig['castle_code'] = 'hikone';
$mcConfig['weather'] = 'clear';
$mcResult = $engine->runMonteCarlo($mcConfig, 100);
assert($mcResult['iterations'] === 100, "Nombre d'itérations incorrect.");
assert(isset($mcResult['win_rate']), "Win rate manquant.");
assert($mcResult['win_rate'] >= 0 && $mcResult['win_rate'] <= 100, "Win rate hors limites (0-100%).");
assert(($mcResult['wins'] + $mcResult['losses']) === 100, "Somme victoires + défaites = 100.");
assert(isset($mcResult['failure_breakdown']['phase1_moat_pct'], $mcResult['failure_breakdown']['phase2_walls_pct'], $mcResult['failure_breakdown']['phase3_tenshu_pct']), "Détails d'échecs manquants.");
echo "OK - Monte-Carlo 100 passes exécuté : Taux de succès attaquant = " . $mcResult['win_rate'] . "% (Victoires: {$mcResult['wins']}, Défaites: {$mcResult['losses']}).\n";

// 9. Test d'exportation du rapport tactique Markdown
echo "[8] Test génération du rapport tactique Markdown...\n";
$mdReport = $engine->exportMarkdownReport($resultHimeji);
assert(!empty($mdReport), "Le rapport Markdown ne doit pas être vide.");
assert(str_contains($mdReport, "# Rapport de Siège"), "En-tête manquant dans le markdown.");
assert(str_contains($mdReport, "Himeji"), "Nom du château manquant dans le rapport.");
assert(str_contains($mdReport, "Chronique des 3 Échelons"), "Section des échelons manquante.");
echo "OK - Rapport Markdown généré avec succès (" . strlen($mdReport) . " octets).\n";

echo "\n========================================================================\n";
echo " TOUS LES TESTS DU SIMULATEUR DE SIÈGE DES 12 CHÂTEAUX SONT PASSÉS ! \n";
echo "========================================================================\n";
