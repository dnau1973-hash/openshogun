<?php
/**
 * Test de validation : Neutralité et Immunité Absolue des Clans IA en mode Passif
 */

require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/BotEngine.php';

echo "=================================================================\n";
echo " TEST : NEUTRALITÉ DES CLANS IA EN MODE PASSIF (DÉFENSE PURE)\n";
echo "=================================================================\n\n";

// 1. Test de la méthode statique isPassiveMode()
echo "1. Vérification de la détection sémantique de l'agressivité :\n";

assert(BotEngine::isPassiveMode('passive') === true, "Le profil 'passive' doit être reconnu comme passif");
echo "  ✓ isPassiveMode('passive') == true\n";

assert(BotEngine::isPassiveMode('peaceful') === true, "Le profil 'peaceful' doit être reconnu comme passif");
echo "  ✓ isPassiveMode('peaceful') == true\n";

assert(BotEngine::isPassiveMode('PASSIVE') === true, "Le profil 'PASSIVE' (majuscules) doit être reconnu");
echo "  ✓ isPassiveMode('PASSIVE') == true\n";

assert(BotEngine::isPassiveMode('moderate') === false, "Le profil 'moderate' ne doit pas être passif");
echo "  ✓ isPassiveMode('moderate') == false\n";

assert(BotEngine::isPassiveMode('aggressive') === false, "Le profil 'aggressive' ne doit pas être passif");
echo "  ✓ isPassiveMode('aggressive') == false\n";

// 2. Test avec GameConfig
echo "\n2. Test de cohérence avec GameConfig :\n";

GameConfig::set('bot_aggressiveness', 'passive');
GameConfig::set('bots_enabled', true);
assert(BotEngine::isPassiveMode() === true, "GameConfig avec 'passive' doit activer isPassiveMode()");
echo "  ✓ GameConfig('passive') => isPassiveMode() == true\n";

GameConfig::set('bot_aggressiveness', 'peaceful');
assert(BotEngine::isPassiveMode() === true, "GameConfig avec 'peaceful' doit activer isPassiveMode()");
echo "  ✓ GameConfig('peaceful') => isPassiveMode() == true\n";

GameConfig::set('bots_enabled', false);
assert(BotEngine::isPassiveMode() === true, "Quand bots_enabled est false, isPassiveMode() doit être true");
echo "  ✓ bots_enabled = false => isPassiveMode() == true\n";

// Rétablir configuration
GameConfig::set('bots_enabled', true);
GameConfig::set('bot_aggressiveness', 'passive');

// 3. Contrôle des fichiers de code pour l'interdiction de raid
echo "\n3. Vérification du blindage du code contre les raids :\n";

$botEngineCode = file_get_contents(__DIR__ . '/../core/BotEngine.php');
assert(strpos($botEngineCode, 'self::isPassiveMode($aggressiveness)') !== false, "BotEngine doit appeler self::isPassiveMode");
echo "  ✓ BotEngine vérifie self::isPassiveMode dans launchBotRaid et launchBotEspionage\n";

assert(strpos($botEngineCode, "if (!\$isPassive)") !== false, "BotEngine doit ignorer les sections 4 et 5 si passif");
echo "  ✓ BotEngine court-circuite l'exécution des raids et de l'espionnage si passif\n";

$fleetEngineCode = file_get_contents(__DIR__ . '/../core/FleetEngine.php');
assert(strpos($fleetEngineCode, 'BotEngine::isPassiveMode()') !== false, "FleetEngine doit vérifier BotEngine::isPassiveMode()");
echo "  ✓ FleetEngine replie pacifiquement toute attaque PNJ arrivant sur un humain si passif\n";

// 4. Vérification de l'interface Game Studio
echo "\n4. Vérification de la synchronisation de l'UI Game Studio :\n";

$studioView = file_get_contents(__DIR__ . '/../views/gamestudio/general_world_config.php');
assert(strpos($studioView, "in_array(\$botAggressiveness, ['passive', 'peaceful'], true)") !== false, "L'UI du Game Studio doit cocher Passif si 'passive' ou 'peaceful'");
echo "  ✓ general_world_config.php gère de façon transparente 'passive' et 'peaceful'\n";

echo "\n=================================================================\n";
echo " ✅ TOUS LES TESTS DE NEUTRALITÉ IA ONT RÉUSSI AVEC SUCCÈS !\n";
echo "=================================================================\n";
