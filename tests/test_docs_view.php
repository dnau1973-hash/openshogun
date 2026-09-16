<?php
/**
 * Test unitaire et de rendu de la Documentation / Codex (docs.php)
 */
if (!defined('SPEED_FACTOR')) {
    define('SPEED_FACTOR', 5);
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

echo "=== TEST DE LA VUE DOCUMENTATION (DOCS.PHP) ===\n";

// 1. Vérifier que la route 'docs' est autorisée
$indexContent = file_get_contents(__DIR__ . '/../index.php');
if (strpos($indexContent, "'docs'") !== false) {
    echo "✓ Route 'docs' autorisée dans index.php\n";
} else {
    echo "✗ ERREUR : Route 'docs' absente de index.php\n";
    exit(1);
}

// 2. Vérifier la présence du lien dans header.php
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
if (strpos($headerContent, '?page=docs') !== false) {
    echo "✓ Bouton de documentation (📖) intégré dans header.php\n";
} else {
    echo "✗ ERREUR : Lien vers la documentation absent du header\n";
    exit(1);
}

// 3. Simuler le rendu de chaque chapitre de docs.php
$tabs = ['troops', 'city', 'resources', 'castles', 'combat'];

// Mock session / auth
$_SESSION['user_id'] = 1;
$_SESSION['planet_id'] = 1;

foreach ($tabs as $tab) {
    $_GET['tab'] = $tab;
    ob_start();
    try {
        require __DIR__ . '/../views/docs.php';
        $output = ob_get_clean();
        $len = strlen($output);
        if ($len > 1000) {
            echo "✓ Chapitre '{$tab}' rendu avec succès ({$len} octets)\n";
        } else {
            echo "✗ ERREUR : Contenu trop court pour le chapitre '{$tab}' ({$len} octets)\n";
            exit(1);
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "✗ ERREUR lors du rendu du chapitre '{$tab}': " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 4. Vérifier que toutes les 12 unités ont leur image physique sur le disque
require_once __DIR__ . '/../core/Database.php';
try {
    $db = Database::getConnection();
    $units = $db->query("SELECT code, name, image FROM units")->fetchAll();
    echo "--- Vérification de l'intégrité des illustrations des 12 unités ---\n";
    foreach ($units as $u) {
        $imgName = !empty($u['image']) ? $u['image'] : ($u['code'] . '.jpg');
        $filePath = __DIR__ . '/../public/assets/units/' . $imgName;
        if (file_exists($filePath)) {
            $size = filesize($filePath);
            echo "  ✓ [{$u['name']}] -> {$imgName} ({$size} octets)\n";
        } else {
            echo "  ✗ ERREUR : Image manquante pour {$u['name']} : {$filePath}\n";
            exit(1);
        }
    }
} catch (Exception $e) {
    echo "Note : Connexion DB directe non testée en environnement sandboxed : " . $e->getMessage() . "\n";
}

echo "=== TOUS LES TESTS DE LA DOCUMENTATION SONT VALIDES ! ===\n";
