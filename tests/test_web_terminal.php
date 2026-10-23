<?php
/**
 * Test : Terminal Web & Console Système (Game Studio)
 * Fichier : tests/test_web_terminal.php
 */

require_once __DIR__ . '/../core/WebTerminalService.php';

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "\n========================================================================" . PHP_EOL;
echo " TEST : TERMINAL WEB & CONSOLE SYSTÈME DANS GAME STUDIO GÉNÉRAL" . PHP_EOL;
echo "========================================================================" . PHP_EOL . PHP_EOL;

$terminal = new WebTerminalService();

// 1. Initialisation & Métriques Système
echo "[TEST 1] Métriques et informations système..." . PHP_EOL;
$metrics = $terminal->getSystemMetrics();
assertTest(!empty($metrics['os']), "Système OS détecté : {$metrics['os']}");
assertTest(!empty($metrics['user']), "Utilisateur web détecté : {$metrics['user']}");
assertTest(!empty($metrics['cwd']), "Répertoire de travail initial : {$metrics['cwd']}");
assertTest(!empty($metrics['disk_total']), "Espace disque total : {$metrics['disk_total']}");
assertTest(!empty($metrics['disk_free']), "Espace disque libre : {$metrics['disk_free']}");
assertTest(isset($metrics['uptime']), "Uptime présent : {$metrics['uptime']}");

// 2. Exécution d'une commande simple et sécurisée
echo PHP_EOL . "[TEST 2] Exécution d'une commande système sécurisée..." . PHP_EOL;
$resEcho = $terminal->executeCommand('echo "Hello OpenShogun Terminal"');
assertTest($resEcho['success'] === true, "Exécution de 'echo' réussie");
assertTest(str_contains($resEcho['output'], 'Hello OpenShogun Terminal'), "Sortie attendue reçue : {$resEcho['output']}");
assertTest($resEcho['return_code'] === 0, "Code de retour 0");
assertTest($resEcho['execution_time_ms'] >= 0, "Temps d'exécution calculé : {$resEcho['execution_time_ms']} ms");

// 3. Exécution d'une commande PHP
echo PHP_EOL . "[TEST 3] Exécution de 'php -v'..." . PHP_EOL;
$resPhp = $terminal->executeCommand('php -v');
assertTest($resPhp['return_code'] === 0 || !empty($resPhp['output']), "Commande php exécutée");

// 4. Filtrage de sécurité des commandes destructives
echo PHP_EOL . "[TEST 4] Blocage des commandes dangereuses (rm -rf /)..." . PHP_EOL;
$resDanger = $terminal->executeCommand('rm -rf /');
assertTest($resDanger['success'] === false, "Commande rm -rf / correctement bloquée");
assertTest($resDanger['return_code'] === 126, "Code de retour de sécurité 126");
assertTest(str_contains($resDanger['output'], 'sécurité'), "Message de blocage de sécurité affiché");

// 5. Blocage des éditeurs interactifs
echo PHP_EOL . "[TEST 5] Protection contre les commandes interactives bloquantes (nano)..." . PHP_EOL;
$resNano = $terminal->executeCommand('nano /etc/passwd');
assertTest($resNano['success'] === false, "Commande nano interceptée");
assertTest(str_contains($resNano['output'], 'interactifs'), "Avertissement sur les éditeurs interactifs retourné");

// 6. Gestion de la commande 'clear'
echo PHP_EOL . "[TEST 6] Gestion de 'clear'..." . PHP_EOL;
$resClear = $terminal->executeCommand('clear');
assertTest($resClear['output'] === '__CLEAR__', "Signal __CLEAR__ renvoyé par la commande clear");

// 7. Navigation de répertoire (cd)
echo PHP_EOL . "[TEST 7] Navigation de répertoire avec 'cd'..." . PHP_EOL;
$initialCwd = $terminal->getCwd();
$resCd = $terminal->executeCommand('cd core');
assertTest($resCd['success'] === true, "Commande cd core réussie");
assertTest(str_ends_with(str_replace('\\', '/', $terminal->getCwd()), '/core'), "Répertoire courant mis à jour vers /core : {$terminal->getCwd()}");

// Retour au dossier racine
$resCdBack = $terminal->executeCommand('cd ..');
assertTest($resCdBack['success'] === true, "Commande cd .. réussie");
assertTest($terminal->getCwd() === $initialCwd, "Retour réussi au dossier initial");

// 8. Vérification des déclarations dans views/gamestudio.php
echo PHP_EOL . "[TEST 8] Enregistrement du sous-onglet dans views/gamestudio.php..." . PHP_EOL;
$gsContent = file_get_contents(__DIR__ . '/../views/gamestudio.php');
assertTest(str_contains($gsContent, "'terminal'"), "Sous-onglet 'terminal' autorisé dans gamestudio.php");
assertTest(str_contains($gsContent, "subtab=terminal"), "Lien subtab=terminal présent dans le dropdown Général");
assertTest(str_contains($gsContent, "WebTerminalService"), "Chargement de WebTerminalService présent dans le contrôleur");

// 9. Vérification des déclarations dans views/gamestudio/general.php
echo PHP_EOL . "[TEST 9] Enregistrement de l'onglet dans views/gamestudio/general.php..." . PHP_EOL;
$genContent = file_get_contents(__DIR__ . '/../views/gamestudio/general.php');
assertTest(str_contains($genContent, "'terminal'"), "Sous-onglet 'terminal' autorisé dans general.php");
assertTest(str_contains($genContent, "general_terminal.php"), "Inclusion de general_terminal.php présente");
assertTest(str_contains($genContent, "Terminal Web"), "Libellé 'Terminal Web' présent dans la barre d'onglets");

// 10. Vérification des endpoints dans api/admin.php
echo PHP_EOL . "[TEST 10] Présence des endpoints API dans api/admin.php..." . PHP_EOL;
$apiContent = file_get_contents(__DIR__ . '/../api/admin.php');
assertTest(str_contains($apiContent, "execute_terminal_command"), "Action execute_terminal_command présente dans api/admin.php");
assertTest(str_contains($apiContent, "get_terminal_sysinfo"), "Action get_terminal_sysinfo présente dans api/admin.php");

// 11. Test de rendu de la vue partielle views/gamestudio/general_terminal.php
echo PHP_EOL . "[TEST 11] Rendu de la vue partielle general_terminal.php..." . PHP_EOL;
$terminalSysInfo = $metrics;
ob_start();
include __DIR__ . '/../views/gamestudio/general_terminal.php';
$renderedHtml = ob_get_clean();
assertTest(!empty($renderedHtml), "Vue general_terminal.php rendue avec succès (" . strlen($renderedHtml) . " octets)");
assertTest(str_contains($renderedHtml, "Terminal Web &amp; Console Système"), "Titre présent dans le HTML rendu");
assertTest(str_contains($renderedHtml, "terminalCommandInput"), "Champ de saisie présent dans le HTML rendu");
assertTest(str_contains($renderedHtml, "git status"), "Bouton raccourci 'git status' présent dans le HTML");
assertTest(str_contains($renderedHtml, "php scripts/migrate.php"), "Bouton raccourci 'migrate' présent dans le HTML");

echo PHP_EOL . "========================================================================" . PHP_EOL;
echo " TOUS LES TESTS DU TERMINAL WEB GAME STUDIO SONT 100% VALIDES !" . PHP_EOL;
echo "========================================================================" . PHP_EOL;
