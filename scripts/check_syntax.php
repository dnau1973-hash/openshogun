#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Script CLI de Contrôle Automatique de Syntaxe PHP (php -l)
 * Utilisation : php scripts/check_syntax.php [--quiet]
 */

require_once __DIR__ . '/../core/QASyntaxChecker.php';

$isQuiet = in_array('--quiet', $argv, true) || in_array('-q', $argv, true);

if (!$isQuiet) {
    echo "========================================================\n";
    echo "  ⛩️  OpenShogun — Contrôle Automatique de Syntaxe PHP\n";
    echo "========================================================\n";
    echo "Analyse des répertoires de l'application...\n";
}

$result = QASyntaxChecker::runSyntaxCheck();

if (!$isQuiet) {
    echo "Fichiers analysés  : {$result['total_files']}\n";
    echo "Fichiers valides   : {$result['passed_count']}\n";
    echo "Erreurs détectées  : {$result['error_count']}\n";
    echo "Temps d'exécution  : {$result['duration_ms']} ms\n";
    echo "--------------------------------------------------------\n";
}

if ($result['is_clean']) {
    if (!$isQuiet) {
        echo "✅ FEU VERT TECHNIQUE : 100% des fichiers PHP sont syntaxiquement valides !\n";
    }
    exit(0);
} else {
    echo "❌ ÉCHEC DU CONTRÔLE : {$result['error_count']} erreur(s) syntaxique(s) détectée(s) :\n";
    foreach ($result['errors'] as $err) {
        echo "  - {$err['file']} : {$err['message']}\n";
    }
    exit(1);
}
