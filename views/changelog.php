<?php
/**
 * Vue Joueur : Chroniques des Versions & Changelog Officiel (OpenShogun)
 * Intégration complète dans le thème Tabler et le Layout HUD du jeu
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';

$isAuth = Auth::check();

// Localisation du fichier source du changelog
$changelogFile = __DIR__ . '/../public/changelog.html';
if (!file_exists($changelogFile)) {
    $changelogFile = __DIR__ . '/../changelog.html';
}

$rawHtml = file_exists($changelogFile) ? file_get_contents($changelogFile) : '';

// 1. Extraction et nettoyage des styles CSS spécifiques à la timeline
preg_match('/<style>(.*?)<\/style>/s', $rawHtml, $styleMatches);
$customCss = $styleMatches[1] ?? '';
// Suppression des règles globales body, .page et .changelog-header pour préserver le HUD du jeu
$customCss = preg_replace('/body\s*\{[^}]*\}/s', '', $customCss);
$customCss = preg_replace('/\.page\s*\{[^}]*\}/s', '', $customCss);
$customCss = preg_replace('/\.changelog-header\s*\{[^}]*\}/s', '', $customCss);

// 2. Extraction du corps de la timeline et de la bannière
preg_match('/<main[^>]*>\s*<div class="container-xl">(.*)<\/div>\s*<\/main>/s', $rawHtml, $bodyMatches);
$changelogBody = $bodyMatches[1] ?? '';

// Fallback de sécurité si le format du fichier était altéré
if (empty($changelogBody)) {
    $changelogBody = '<div class="alert alert-info">Le registre des versions est en cours de synchronisation.</div>';
}

// Si visiteur non connecté, encapsuler dans un template HTML public propre (sans bouton retour)
if (!$isAuth):
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog &bull; Chroniques de Déploiement d'OpenShogun</title>
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css">
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../public/css/style.css') ? filemtime(__DIR__ . '/../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📜</text></svg>">
    <style>
        <?= $customCss ?>
    </style>
</head>
<body class="antialiased bg-light">
    <div class="page">
        <header class="navbar navbar-expand-md bg-white border-bottom py-2 sticky-top shadow-sm">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <a href="/" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                    <span style="font-size: 1.6rem;">📜</span>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size: 1.15rem; letter-spacing: 0.3px;">OpenShogun &bull; Changelog</div>
                        <div class="text-secondary small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.6px; text-transform: uppercase;">Chroniques des Mises à Jour &amp; Évolutions</div>
                    </div>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <a href="/?page=docs" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1">
                        <span>📖</span> Règles du jeu
                    </a>
                    <a href="/?page=support" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1">
                        <span>📮</span> Support &amp; Aide
                    </a>
                    <a href="/?page=pedagogy" class="btn btn-sm btn-outline-cyan d-none d-md-inline-flex align-items-center gap-1">
                        <span>🎓</span> Atelier Pédagogique
                    </a>
                </div>
            </div>
        </header>

        <main class="page-body my-4">
            <div class="container-xl">
                <?= $changelogBody ?>
            </div>
        </main>

        <footer class="footer footer-transparent d-print-none py-3 border-top bg-white mt-auto">
            <div class="container-xl text-center text-muted small">
                OpenShogun &copy; <?= date('Y') ?> &bull; Jeu de stratégie féodale par navigateur &bull; Moteur Sengoku PHP 8
            </div>
        </footer>
    </div>
    <script src="/public/js/tabler/tabler.min.js"></script>
</body>
</html>
<?php
    exit;
endif;

// Utilisateur connecté : rendu direct dans le layout HUD (header.php fournit le container-xl)
?>
<style>
    <?= $customCss ?>
</style>

<div class="changelog-integrated-view mb-4">
    <?= $changelogBody ?>
</div>
