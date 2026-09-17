<?php
/**
 * Test de la fonctionnalité "Modifier ma Devise" et de l'intégration Tenshu / Profil / Quête
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/HonorEngine.php';
require_once __DIR__ . '/../core/QuestEngine.php';

echo "========================================================\n";
echo "  BANC DE TESTS : FONCTIONNALITÉ 'MODIFIER MA DEVISE'\n";
echo "========================================================\n";

$testsTotal = 0;
$testsPassed = 0;

function assertTest($description, $condition, &$passed, &$total) {
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] $description\n";
    } else {
        echo "  [FAIL] $description\n";
    }
}

$db = Database::getConnection();
$honorEngine = new HonorEngine();
$questEngine = new QuestEngine();

// 1. Vérification de la méthode updateBio
$user = $db->query("SELECT id, username, bio FROM users WHERE is_bot = 0 ORDER BY id ASC LIMIT 1")->fetch();
assertTest("Un utilisateur réel existe pour le test", !empty($user), $testsPassed, $testsTotal);

$originalBio = $user['bio'];
$testBio = "Par le sabre et l'honneur, mon clan unifiera le Japon féodal ! (" . date('H:i:s') . ")";

$success = $honorEngine->updateBio((int)$user['id'], $testBio);
assertTest("HonorEngine::updateBio retourne true", $success === true, $testsPassed, $testsTotal);

$updatedBio = $db->prepare("SELECT bio FROM users WHERE id = ?");
$updatedBio->execute([$user['id']]);
assertTest("La devise est bien enregistrée en base de données", $updatedBio->fetchColumn() === $testBio, $testsPassed, $testsTotal);

// 2. Vérification de la quête 'daimyo_motto'
$questStatus = $questEngine->getPlayerQuestsStatus((int)$user['id'], 1);
$mottoQuest = null;
foreach ($questStatus['quests'] as $q) {
    if ($q['key'] === 'daimyo_motto') {
        $mottoQuest = $q;
        break;
    }
}
assertTest("La quête 'daimyo_motto' existe dans le catalogue", $mottoQuest !== null, $testsPassed, $testsTotal);
assertTest("La quête 'daimyo_motto' a pour action_url 'javascript:openEditMottoModal()'", ($mottoQuest['action_url'] ?? '') === 'javascript:openEditMottoModal()', $testsPassed, $testsTotal);
assertTest("La quête 'daimyo_motto' est validée suite à la saisie de la devise", ($mottoQuest['status'] === 'completed' || $mottoQuest['status'] === 'claimed'), $testsPassed, $testsTotal);

// 3. Vérification des fichiers de vue
$footerContent = file_get_contents(__DIR__ . '/../views/partials/footer.php');
assertTest("footer.php contient la fonction openEditMottoModal()", strpos($footerContent, 'function openEditMottoModal()') !== false, $testsPassed, $testsTotal);
assertTest("footer.php gère le paramètre autoEdit dans openPlayerProfileModal", strpos($footerContent, 'async function openPlayerProfileModal(userId = null, autoEdit = false)') !== false, $testsPassed, $testsTotal);
assertTest("footer.php synchronise avec le Tenshu tenshuDaimyoBioText", strpos($footerContent, 'tenshuDaimyoBioText') !== false, $testsPassed, $testsTotal);

$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assertTest("header.php contient le bouton Devise", strpos($headerContent, 'openEditMottoModal()') !== false, $testsPassed, $testsTotal);

$buildingContent = file_get_contents(__DIR__ . '/../views/building.php');
assertTest("building.php contient la section Devise exclusive au Tenshu (hq)", strpos($buildingContent, "code === 'hq'") !== false && strpos($buildingContent, 'tenshuDaimyoBioText') !== false, $testsPassed, $testsTotal);

// Rétablir la bio originale
$honorEngine->updateBio((int)$user['id'], $originalBio);

echo "\n========================================================\n";
echo "  RÉSULTATS DU BANC DE TESTS : $testsPassed / $testsTotal RÉUSSIS\n";
echo "========================================================\n";

if ($testsPassed === $testsTotal) {
    echo "TOUS LES TESTS DE LA FONCTIONNALITÉ 'DEVISE' SONT VALIDÉS AVEC SUCCÈS !\n";
} else {
    exit(1);
}

