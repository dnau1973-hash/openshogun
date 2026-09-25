<?php
/**
 * Vue Publique de l'Atelier Pédagogique (Coulisses de Conception OpenShogun - Projet Père-Fils)
 * Accessible à tous les utilisateurs (visiteurs et joueurs connectés)
 * Thème clair épuré Tabler.io
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

// Si l'utilisateur n'est pas connecté, afficher le template complet public Tabler.io en thème clair
if (!$isAuth):
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
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
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
        .public-pedagogy-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="antialiased bg-light">
    <div class="page">
        <!-- Barre de navigation publique en thème clair -->
        <header class="navbar navbar-expand-md public-pedagogy-header py-2 sticky-top">
            <div class="container-xl">
                <a href="/" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                    <span style="font-size: 1.6rem;">🎓</span>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size: 1.1rem; letter-spacing: 0.5px;">Atelier Pédagogique</div>
                        <div class="text-primary small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.8px; text-transform: uppercase;">OpenShogun &bull; Conception Jeu Vidéo</div>
                    </div>
                </a>

                <div class="navbar-nav flex-row order-md-last align-items-center gap-2 ms-auto">
                    <a href="/?page=docs" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1">
                        <span>📖</span> Règles du jeu
                    </a>
                    <a href="/?action=login" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
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
                <!-- Bandeau d'introduction publique épuré -->
                <div class="alert alert-info bg-white border border-primary-subtle text-dark d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-1 text-primary">🌟</span>
                        <div>
                            <strong class="text-dark">Bienvenue dans les coulisses techniques d'un jeu de stratégie en ligne !</strong>
                            <div class="text-secondary small">Découvrez comment fonctionnent l'architecture client/serveur, la boucle d'états, le moteur de règles, l'aléatoire contrôlé (RNG) et la création graphique avec l'IA.</div>
                        </div>
                    </div>
                    <div>
                        <a href="/?action=register" class="btn btn-sm btn-primary fw-bold">
                            Créer un fief et tester le moteur &rarr;
                        </a>
                    </div>
                </div>

                <?php require __DIR__ . '/partials/admin_pedagogy.php'; ?>
            </div>
        </main>

        <!-- Pied de page public épuré -->
        <footer class="footer footer-transparent d-print-none py-3 border-top bg-white">
            <div class="container-xl text-center text-muted small">
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-2 small fw-semibold">
                    <a href="/?page=docs" class="text-decoration-none text-secondary">📖 Règles du jeu</a>
                    <span class="text-muted opacity-50">&bull;</span>
                    <a href="/?page=support" class="text-decoration-none text-secondary">📮 Support &amp; Aide</a>
                    <span class="text-muted opacity-50">&bull;</span>
                    <a href="/changelog.html" class="text-decoration-none text-secondary">📜 Changelog</a>
                </div>
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
    <!-- Fil d'Ariane & Titre épuré -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-primary fw-bold">🎓 Savoir &amp; Coulisses Techniques</div>
                <h2 class="page-title d-flex align-items-center gap-2 text-dark">
                    <span>🎮</span>
                    <span>Atelier Pédagogique &bull; Studio de Conception</span>
                    <span class="badge bg-primary-lt ms-2">Projet Père-Fils</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Explorez les rouages du moteur de jeu : architecture réseau, états logiques, algorithmes de combat et génération créative.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if ($auth->isAdmin()): ?>
                        <a href="?page=admin&tab=pedagogy" class="btn btn-sm btn-outline-primary">
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
