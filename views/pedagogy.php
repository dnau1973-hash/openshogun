<?php
/**
 * Vue Publique de l'Atelier Pédagogique (Coulisses de Conception OpenShogun - Projet Père-Fils)
 * Accessible à tous les utilisateurs (visiteurs et joueurs connectés)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';

// Incrémentation discrète des consultations pédagogiques pour le Dashboard Admin
try {
    $curViews = (int)GameConfig::get('pedagogy_views_count', 42);
    GameConfig::set('pedagogy_views_count', $curViews + 1);
} catch (Exception $e) {}

$isAuth = Auth::check();
$auth = new Auth();
$user = $isAuth ? $auth->getCurrentUser() : null;

// Si l'utilisateur n'est pas connecté, afficher le template complet public Tabler.io
if (!$isAuth):
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atelier Pédagogique &bull; Les Coulisses de Conception d'OpenShogun</title>
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css">
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../public/css/style.css') ? filemtime(__DIR__ . '/../public/css/style.css') : time() ?>">
    <link rel="stylesheet" href="/public/css/ai_prompt_modal.css?v=<?= file_exists(__DIR__ . '/../public/css/ai_prompt_modal.css') ? filemtime(__DIR__ . '/../public/css/ai_prompt_modal.css') : 1 ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">
    <style>
        body {
            background-color: #0b1120;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .public-pedagogy-header {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(11, 17, 32, 0.95) 100%);
            border-bottom: 1px solid rgba(6, 182, 212, 0.25);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="theme-dark antialiased">
    <div class="page">
        <!-- Barre de navigation publique -->
        <header class="navbar navbar-expand-md public-pedagogy-header py-2 sticky-top">
            <div class="container-xl">
                <a href="/" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                    <span style="font-size: 1.6rem;">🎓</span>
                    <div>
                        <div class="fw-bold text-white lh-1" style="font-size: 1.1rem; letter-spacing: 0.5px;">Atelier Pédagogique</div>
                        <div class="text-cyan small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.8px; text-transform: uppercase;">OpenShogun &bull; Conception Jeu Vidéo</div>
                    </div>
                </a>

                <div class="navbar-nav flex-row order-md-last align-items-center gap-2 ms-auto">
                    <a href="/?page=docs" class="btn btn-sm btn-outline-cyan d-none d-sm-inline-flex align-items-center gap-1">
                        <span>📖</span> Codex &amp; Lore
                    </a>
                    <a href="/?action=login" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                        <span>🏯</span> Connexion
                    </a>
                    <a href="/?action=register" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                        <span>⚔️</span> Rejoindre l'Archipel
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenu de l'Atelier Pédagogique -->
        <main class="page-wrapper py-4">
            <div class="container-xl">
                <!-- Bandeau d'introduction publique -->
                <div class="alert alert-important bg-dark border-cyan-subtle text-cyan d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-2">🌟</span>
                        <div>
                            <strong>Bienvenue dans les coulisses techniques d'un jeu de stratégie en ligne !</strong>
                            <div class="text-white-50 small">Découvrez comment fonctionnent l'architecture client/serveur, la Game Loop, les formules d'équilibrage et la génération d'images par IA.</div>
                        </div>
                    </div>
                    <div>
                        <a href="/?action=register" class="btn btn-sm btn-cyan fw-bold">
                            Créer un fief et tester le moteur &rarr;
                        </a>
                    </div>
                </div>

                <?php require __DIR__ . '/partials/admin_pedagogy.php'; ?>
            </div>
        </main>

        <!-- Pied de page public -->
        <footer class="footer footer-transparent d-print-none py-3 border-top border-secondary-subtle">
            <div class="container-xl text-center text-muted small">
                Projet Éducatif Père &amp; Fils &bull; OpenShogun Engine &bull; <?= date('Y') ?> &bull; Tous droits réservés.
            </div>
        </footer>
    </div>

    <!-- Modale de Transparence IA (Prompts & Traduction) -->
    <?php require __DIR__ . '/partials/ai_prompt_modal.php'; ?>

    <!-- Tabler Core JS -->
    <script src="/public/js/tabler/tabler.min.js"></script>
    <script src="/public/js/ai_prompt_modal.js"></script>
</body>
</html>
<?php 
    exit;
endif; 

// Si l'utilisateur est déjà connecté, index.php fournit déjà le header et footer HUD du jeu
?>

<div class="container-xl my-3">
    <!-- Fil d'Ariane & Titre -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-cyan fw-bold">🎓 Savoir &amp; Coulisses Techniques</div>
                <h2 class="page-title d-flex align-items-center gap-2">
                    <span>🎮</span>
                    <span>Atelier Pédagogique &bull; Studio de Conception</span>
                    <span class="badge bg-cyan-lt ms-2">Projet Père-Fils</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Explorez les rouages du moteur de jeu : architecture réseau, algorithmes de combat et génération créative.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if ($auth->isAdmin()): ?>
                        <a href="?page=admin&tab=pedagogy" class="btn btn-sm btn-outline-cyan">
                            <span>🛡️</span> Vue Administration
                        </a>
                    <?php endif; ?>
                    <a href="?page=resources" class="btn btn-sm btn-secondary">
                        &larr; Retour au Fief
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/admin_pedagogy.php'; ?>
</div>
