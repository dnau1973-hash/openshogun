<?php
/**
 * Test du Moteur des 12 Donjons Authentiques du Japon (現存十二天守)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CastleEngine.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';

echo "=== TEST DES 12 DONJONS AUTHENTIQUES DU JAPON (現存十二天守) ===\n";

$castleEngine = new CastleEngine();
$galaxyEngine = new GalaxyEngine();

// 1. Vérification des 12 châteaux en base
$castles = $castleEngine->getAllCastles();
assert(count($castles) === 12, "Il doit y avoir exactement 12 châteaux authentiques.");
echo "✓ 12 châteaux authentiques recensés dans la base de données\n";

$expectedCastles = [
    'bitchu_matsuyama' => 'Château de Bitchū Matsuyama',
    'hikone' => 'Château de Hikone',
    'himeji' => 'Château de Himeji',
    'hirosaki' => 'Château de Hirosaki',
    'inuyama' => 'Château d\'Inuyama',
    'kochi' => 'Château de Kōchi',
    'marugame' => 'Château de Marugame',
    'maruoka' => 'Château de Maruoka',
    'matsue' => 'Château de Matsue',
    'matsumoto' => 'Château de Matsumoto',
    'matsuyama' => 'Château de Matsuyama',
    'uwajima' => 'Château d\'Uwajima',
];

foreach ($expectedCastles as $code => $expectedName) {
    $c = $castleEngine->getCastle($code);
    assert($c !== null, "Le château $code doit exister.");
    assert($c['name'] === $expectedName, "Le nom du château $code doit être $expectedName.");
    assert(!empty($c['kanji']), "Le kanji du château $code ne doit pas être vide.");
    assert(!empty($c['final_battle_lore']), "L'enjeu de la bataille finale doit être renseigné.");
    assert(!empty($c['relic_bonus']), "Le bonus de relique doit être renseigné.");
    echo "  - [{$c['kanji']}] {$c['name']} ({$c['province']}) validé.\n";
}

// 2. Déploiement des 12 donjons sur la carte
echo "\n--- Déploiement des 12 donjons sur la carte ---\n";
$spawnRes = $castleEngine->spawnAllCastles();
assert($spawnRes['success'] === true, "Le déploiement global doit réussir.");
assert($spawnRes['count'] === 12, "Les 12 donjons doivent être déployés.");

$spawned = $castleEngine->getSpawnedCastles();
assert(count($spawned) === 12, "12 donjons doivent être marqués comme déployés.");
echo "✓ 12/12 donjons authentiques déployés sur la carte aux coordonnées stratégiques.\n";

// 3. Vérification de la détection sur la carte par GalaxyEngine
echo "\n--- Vérification de la carte galactique / provinces (GalaxyEngine) ---\n";
// Himeji est à [-2:1]
$sector = $galaxyEngine->getSectorMap(0, 0, 5);
$planets = $sector['planets'];

$foundCastlesOnMap = 0;
foreach ($planets as $coordKey => $tile) {
    if (!empty($tile['is_authentic_castle'])) {
        $foundCastlesOnMap++;
        echo "  - Trouvé sur la carte en [$coordKey] : {$tile['castle_name']} ({$tile['castle_kanji']})\n";
    }
}
assert($foundCastlesOnMap > 0, "Au moins plusieurs châteaux doivent être visibles autour de (0,0).");
echo "✓ Les donjons authentiques sont correctement injectés dans les tuiles de la carte.\n";

// 4. Test du rendu de la vue dédiée views/castle.php pour chaque château
echo "\n--- Rendu HTML de la page dédiée (views/castle.php) ---\n";

$db = Database::getConnection();
$user = $db->query("SELECT * FROM users LIMIT 1")->fetch();
$_SESSION['user_id'] = $user['id'];
$planet = $db->query("SELECT * FROM planets WHERE user_id = {$user['id']} LIMIT 1")->fetch();
$_SESSION['planet_id'] = $planet ? $planet['id'] : 1;

foreach ($expectedCastles as $code => $name) {
    $_GET['page'] = 'castle';
    $_GET['code'] = $code;

    ob_start();
    include __DIR__ . '/../views/castle.php';
    $output = ob_get_clean();

    assert(strpos($output, htmlspecialchars($name)) !== false, "Le nom $name doit être présent dans le rendu.");
    assert(strpos($output, 'Bataille Finale du Shogunat') !== false, "La section de la bataille finale doit être présente.");
    assert(strpos($output, 'Sanctuaire de l\'Hégémonie Suprême') !== false, "Le sanctuaire doit être présent.");
    assert(strpos($output, 'Trésor') !== false, "L'indicateur de trésor doit être présent.");
    echo "  ✓ Vue du $name rendue sans erreur (" . strlen($output) . " octets).\n";
}

echo "\n=== TOUS LES TESTS DES 12 DONJONS AUTHENTIQUES ONT RÉUSSI ! ===\n";

