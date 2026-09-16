<?php
/**
 * Test unitaire et de rendu de la nouvelle page Map (Carte des Provinces en pleine largeur)
 */
if (!defined('SPEED_FACTOR')) {
    define('SPEED_FACTOR', 5);
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

echo "=== TEST DE LA PAGE MAP (CARTE DES PROVINCES EN PLEINE LARGEUR) ===\n";

// 1. Vérifier index.php
$indexContent = file_get_contents(__DIR__ . '/../index.php');
if (strpos($indexContent, "'map'") !== false) {
    echo "✓ Route 'map' autorisée dans index.php\n";
} else {
    echo "✗ ERREUR : Route 'map' absente de index.php\n";
    exit(1);
}

if (strpos($indexContent, "\$page === 'galaxy'") !== false && strpos($indexContent, "\$page = 'map'") !== false) {
    echo "✓ Alias / redirection rétrocompatible 'galaxy' -> 'map' en place dans index.php\n";
} else {
    echo "✗ ERREUR : Rétrocompatibilité 'galaxy' manquante dans index.php\n";
    exit(1);
}

// 2. Vérifier header.php
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
if (strpos($headerContent, 'href="?page=map"') !== false) {
    echo "✓ Médaillon n°3 pointe bien vers '?page=map' dans header.php\n";
} else {
    echo "✗ ERREUR : Médaillon n°3 ne pointe pas vers ?page=map\n";
    exit(1);
}

if (strpos($headerContent, "container-fullwidth") !== false) {
    echo "✓ Classe plein écran 'container-fullwidth' conditionnelle présente dans header.php\n";
} else {
    echo "✗ ERREUR : Classe 'container-fullwidth' absente de header.php\n";
    exit(1);
}

// 3. Vérifier style.css
$cssContent = file_get_contents(__DIR__ . '/../public/css/style.css');
if (strpos($cssContent, ".container.container-fullwidth") !== false) {
    echo "✓ Règles CSS de pleine largeur (.container.container-fullwidth) définies dans style.css\n";
} else {
    echo "✗ ERREUR : Styles .container.container-fullwidth absents de style.css\n";
    exit(1);
}

// 4. Tester le rendu HTML de views/map.php
$planet = [
    'id' => 1,
    'name' => 'Fief d\'Azuchi',
    'coord_x' => 0,
    'coord_y' => 0
];
$user = [
    'id' => 1,
    'username' => 'Nobunaga',
    'faction' => 'terran'
];

ob_start();
try {
    require __DIR__ . '/../views/map.php';
    $output = ob_get_clean();
    $len = strlen($output);
    if ($len > 1000 && strpos($output, 'galaxyMapContainer') !== false) {
        echo "✓ Vue 'views/map.php' rendue avec succès ({$len} octets)\n";
    } else {
        echo "✗ ERREUR : Rendu 'views/map.php' invalide\n";
        exit(1);
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "✗ ERREUR lors du rendu de views/map.php: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Tester l'alias views/galaxy.php
ob_start();
try {
    require __DIR__ . '/../views/galaxy.php';
    $outputGalaxy = ob_get_clean();
    if (strlen($outputGalaxy) === $len) {
        echo "✓ Alias 'views/galaxy.php' redirige fidèlement vers 'views/map.php'\n";
    } else {
        echo "✗ ERREUR : Divergence dans l'alias views/galaxy.php\n";
        exit(1);
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "✗ ERREUR lors du rendu de l'alias views/galaxy.php: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== TOUS LES TESTS DE LA PAGE MAP PLEINE LARGEUR SONT VALIDES ! ===\n";

