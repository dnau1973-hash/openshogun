<?php
/**
 * Test Automatisé : Moteur de Mises à Jour GitHub & Sécurité (OpenShogun)
 */
require_once __DIR__ . '/../core/UpdateEngine.php';

echo "========================================================\n";
echo " TEST : MOTEUR DE MISES À JOUR GITHUB & TOKEN SECURITY   \n";
echo "========================================================\n\n";

// 1. Initialiser le moteur
$engine = new UpdateEngine();
assert($engine instanceof UpdateEngine, "Erreur d'instanciation de UpdateEngine");
echo "[PASS] UpdateEngine instancié avec succès.\n";

// 2. Tester le masquage de token (Sécurité)
$dummySecret = "ghp_EXAMPLE_TEST_TOKEN_1234567890abcdef";
$masked = $engine->maskToken("git pull https://{$dummySecret}@github.com/test.git main");
assert(strpos($masked, $dummySecret) === false, "Échec de sécurité : Le token en clair ne doit JAMAIS apparaître !");
assert(strpos($masked, 'ghp_••••••••') !== false, "Le token doit être masqué.");
echo "[PASS] Sécurité : Le token GitHub est systématiquement masqué ({$masked}).\n";

// 3. Tester la récupération des métadonnées locales
$local = $engine->getLocalInfo();
assert(!empty($local['branch']), "Branche locale introuvable");
assert(!empty($local['commit_sha']), "SHA commit local introuvable");
assert(!empty($local['short_sha']), "Short SHA introuvable");
assert(strlen($local['short_sha']) >= 7, "Taille du short SHA invalide");
assert(strpos($local['masked_token'], 'ghp_••••••••') !== false, "Token masqué dans les métadonnées");
echo "[PASS] Métadonnées locales : Branche '{$local['branch']}', Commit '{$local['short_sha']}'.\n";

echo "\n========================================================\n";
echo " TOUS LES TESTS DU MOTEUR DE MISE À JOUR ONT RÉUSSI !    \n";
echo "========================================================\n";

