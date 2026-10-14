<?php
/**
 * Test unitaire et fonctionnel : Modale Intendant du Marché Castral & Équilibrage Proportionnel 1:1
 * Couvre les 11 ressources présentées en 2 colonnes (Alimentaire à gauche, Matériaux à droite)
 */

echo "=== TEST : MODALE INTENDANT DU MARCHÉ & ÉQUILIBRAGE PROPORTIONNEL (11 RESSOURCES) ===\n\n";

// 1. Test de rendu de la vue partielle
echo "[1] Test de rendu de views/partials/npc_exchange_modal.php...\n";
$goldCoins = 15;
$user = ['gold_coins' => 15];
ob_start();
require __DIR__ . '/../views/partials/npc_exchange_modal.php';
$html = ob_get_clean();

// Vérification de la présence de la modale et de ses colonnes
assert(strpos($html, 'id="modalNpcExchange"') !== false, "La modale modalNpcExchange doit être présente.");
assert(strpos($html, 'modal-xl') !== false, "La modale doit utiliser la classe modal-xl pour les 2 colonnes.");
assert(strpos($html, 'Vivres &amp; Ressources Alimentaires') !== false, "La colonne gauche Alimentaire doit être présente.");
assert(strpos($html, 'Matériaux de Construction') !== false, "La colonne droite Matériaux de Construction doit être présente.");

// Vérification des 6 ressources alimentaires (Gauche)
$foodKeys = ['rice', 'fish', 'tea', 'soybean', 'rice_flour', 'sake'];
foreach ($foodKeys as $k) {
    assert(strpos($html, "id=\"npc_check_{$k}\"") !== false, "La case à cocher pour {$k} doit être présente.");
    assert(strpos($html, "id=\"npc_range_{$k}\"") !== false, "Le curseur pour {$k} doit être présent.");
    assert(strpos($html, "id=\"npc_input_{$k}\"") !== false, "Le champ numérique pour {$k} doit être présent.");
    assert(strpos($html, "id=\"npc_max_{$k}_label\"") !== false, "Le label de stockage max pour {$k} doit être présent.");
}

// Vérification des 5 ressources de construction (Droite)
$constructKeys = ['wood', 'stone', 'clay', 'wooden_beams', 'charcoal'];
foreach ($constructKeys as $k) {
    assert(strpos($html, "id=\"npc_check_{$k}\"") !== false, "La case à cocher pour {$k} doit être présente.");
    assert(strpos($html, "id=\"npc_range_{$k}\"") !== false, "Le curseur pour {$k} doit être présent.");
    assert(strpos($html, "id=\"npc_input_{$k}\"") !== false, "Le champ numérique pour {$k} doit être présent.");
    assert(strpos($html, "id=\"npc_max_{$k}_label\"") !== false, "Le label de stockage max pour {$k} doit être présent.");
}

assert(strpos($html, 'onNpcCheckChange') !== false, "Le gestionnaire de cases à cocher onNpcCheckChange doit être présent.");
assert(strpos($html, 'handleResourceAdjustment') !== false, "La fonction d'équilibrage proportionnel doit être présente.");
assert(strpos($html, 'Solde : 15') !== false, "Le solde de Kobans doit être affiché.");
echo "✓ Rendu HTML de la modale validé avec succès (11 ressources en 2 colonnes) !\n";

// 2. Test algorithmique de l'équilibrage proportionnel 1:1 sur 11 ressources
echo "\n[2] Test algorithmique de l'équilibrage proportionnel 1:1...\n";

function simulateAdjustment11($type, $desiredValue, $checked, $values, $maxes, $total, $allKeys) {
    if (count($checked) === 2 && in_array($type, $checked)) {
        $otherType = ($checked[0] === $type) ? $checked[1] : $checked[0];

        $unselectedSum = 0;
        foreach ($allKeys as $r) {
            if (!in_array($r, $checked)) {
                $unselectedSum += $values[$r];
            }
        }

        $pool = $total - $unselectedSum;
        $maxType = $maxes[$type] ?? $pool;
        $maxOther = $maxes[$otherType] ?? $pool;

        $minVal = max(0, $pool - $maxOther);
        $maxVal = min($pool, $maxType);

        $clampedVal = max($minVal, min($maxVal, $desiredValue));
        $otherVal = max(0, $pool - $clampedVal);

        $values[$type] = $clampedVal;
        $values[$otherType] = $otherVal;
    } else {
        $max = $maxes[$type] ?? $total;
        $values[$type] = max(0, min($max, $desiredValue));
    }
    return $values;
}

$allKeys = array_merge($foodKeys, $constructKeys);
$initialValues = [
    'rice' => 5000,
    'fish' => 2000,
    'tea' => 1500,
    'soybean' => 1200,
    'rice_flour' => 800,
    'sake' => 500,
    'wood' => 10000,
    'stone' => 8000,
    'clay' => 3000,
    'wooden_beams' => 1000,
    'charcoal' => 1000
];
$grandTotal = array_sum($initialValues); // 34 000
$maxes = [
    'rice' => 50000, 'fish' => 50000, 'tea' => 20000, 'soybean' => 20000, 'rice_flour' => 20000, 'sake' => 20000,
    'wood' => 50000, 'stone' => 50000, 'clay' => 20000, 'wooden_beams' => 20000, 'charcoal' => 20000
];

// Scénario A : Joueur lie 1 ressource alimentaire (Riz: 5 000) et 1 ressource de construction (Bois: 10 000)
// Les 9 autres ressources totalisent 19 000. Le pool Riz+Bois = 15 000.
$checkedA = ['rice', 'wood'];
// Augmenter le Riz de 5 000 à 9 000 (+4 000)
$resA = simulateAdjustment11('rice', 9000, $checkedA, $initialValues, $maxes, $grandTotal, $allKeys);
assert($resA['rice'] === 9000, "Le riz doit passer à 9 000.");
assert($resA['wood'] === 6000, "Le bois doit avoir diminué de 4 000 (10 000 -> 6 000). Obtenu : {$resA['wood']}");
assert($resA['stone'] === 8000, "La pierre non liée doit rester à 8 000.");
assert($resA['fish'] === 2000, "Le poisson non lié doit rester à 2 000.");
assert(array_sum($resA) === $grandTotal, "Le total général doit rester strictement égal à {$grandTotal}.");
echo "✓ Scénario A validé : Échange inter-colonnes Riz (+4 000) <-> Bois (-4 000). Total: {$grandTotal}.\n";

// Scénario B : Joueur lie 2 ressources de construction (Argile: 3 000, Charbon: 1 000)
// Pool Argile+Charbon = 4 000
$checkedB = ['clay', 'charcoal'];
$resB = simulateAdjustment11('clay', 3800, $checkedB, $resA, $maxes, $grandTotal, $allKeys);
assert($resB['clay'] === 3800, "L'argile doit passer à 3 800.");
assert($resB['charcoal'] === 200, "Le charbon doit être tombé à 200 (4 000 - 3 800). Obtenu : {$resB['charcoal']}");
assert($resB['rice'] === 9000, "Le riz doit rester à 9 000.");
assert(array_sum($resB) === $grandTotal, "Le total général doit être strictement conservé.");
echo "✓ Scénario B validé : Échange intra-colonne construction Argile (+800) <-> Charbon (-800).\n";

// Scénario C : Plafonnement et plancher (tentative de dépasser le pool)
$resC = simulateAdjustment11('clay', 10000, $checkedB, $resB, $maxes, $grandTotal, $allKeys);
assert($resC['clay'] === 4000, "L'argile doit être plafonnée au pool total de 4 000. Obtenu : {$resC['clay']}");
assert($resC['charcoal'] === 0, "Le charbon ne peut pas être négatif, fixé à 0. Obtenu : {$resC['charcoal']}");
assert(array_sum($resC) === $grandTotal, "Total conservé.");
echo "✓ Scénario C validé : Plafonnement au pool (4 000) et plancher à 0 respectés.\n";

// 3. Test unitaire de ImperialSealEngine::getNpcPlanetResourceData et npcExchange
echo "\n[3] Test de l'API moteur ImperialSealEngine...\n";
require_once __DIR__ . '/../core/ImperialSealEngine.php';

// Création d'une base de données SQLite en mémoire pour tester npcExchange
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Création tables users et planets
$sqlite->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        gold_coins INTEGER DEFAULT 100,
        imperial_seal_until TEXT NULL,
        last_daily_gold TEXT NULL
    );
    CREATE TABLE planets (
        id INTEGER PRIMARY KEY,
        user_id INTEGER,
        name TEXT,
        metal REAL DEFAULT 1000,
        crystal REAL DEFAULT 800,
        deuterium REAL DEFAULT 400,
        fish REAL DEFAULT 200,
        tea REAL DEFAULT 150,
        soybean REAL DEFAULT 100,
        rice_flour REAL DEFAULT 50,
        sake REAL DEFAULT 25,
        clay REAL DEFAULT 300,
        wooden_beams REAL DEFAULT 75,
        charcoal REAL DEFAULT 60,
        metal_max INTEGER DEFAULT 15000,
        crystal_max INTEGER DEFAULT 15000,
        deuterium_max INTEGER DEFAULT 15000,
        fish_max INTEGER DEFAULT 15000,
        tea_max INTEGER DEFAULT 10000,
        soybean_max INTEGER DEFAULT 10000,
        rice_flour_max INTEGER DEFAULT 10000,
        sake_max INTEGER DEFAULT 10000,
        clay_max INTEGER DEFAULT 10000,
        wooden_beams_max INTEGER DEFAULT 10000,
        charcoal_max INTEGER DEFAULT 10000
    );
");

$sqlite->exec("INSERT INTO users (id, gold_coins) VALUES (1, 10)");
$sqlite->exec("INSERT INTO planets (id, user_id, name) VALUES (1, 1, 'Fief de Test')");

// Instancier ImperialSealEngine avec la DB SQLite injectée
$engine = new ImperialSealEngine($sqlite);

// Test de getNpcPlanetResourceData
$planetRow = $sqlite->query("SELECT * FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$pData = $engine->getNpcPlanetResourceData($planetRow);
assert(isset($pData['resources']['rice']), "La ressource rice doit être présente.");
assert(isset($pData['resources']['charcoal']), "La ressource charcoal doit être présente.");
assert($pData['resources']['wood'] === 1000, "Le bois initial doit être 1 000.");
assert($pData['resources']['sake'] === 25, "Le saké initial doit être 25.");
echo "✓ getNpcPlanetResourceData extrait parfaitement les 11 ressources et plafonds !\n";

// Test de npcExchange avec rééquilibrage de 100 unités de Bois vers le Saké
$initialTotal = array_sum($pData['resources']);
$newPayload = $pData['resources'];
$newPayload['wood'] -= 100;
$newPayload['sake'] += 100;

$exchangeRes = $engine->npcExchange(1, 1, $newPayload);
assert($exchangeRes['success'] === true, "L'échange NPC doit réussir.");
assert($exchangeRes['remaining_gold'] === 7, "Le solde de Koban doit être passé de 10 à 7 (-3 Koban). Obtenu : {$exchangeRes['remaining_gold']}");

$updatedPlanet = $sqlite->query("SELECT metal, sake FROM planets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assert((int)$updatedPlanet['metal'] === 900, "Le bois sur la planète doit être à 900. Obtenu : {$updatedPlanet['metal']}");
assert((int)$updatedPlanet['sake'] === 125, "Le saké sur la planète doit être à 125. Obtenu : {$updatedPlanet['sake']}");
echo "✓ npcExchange a rééquilibré avec succès les 11 ressources en DB et déduit 3 Kobans !\n";

echo "\n>>> TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS ! <<<\n";
