<?php
/**
 * Game Studio - Contrôleur et Vue Maîtresse (Architecture MVC)
 * Fusionne le Studio de Développement et l'Administration Générale
 * Fichier : views/gamestudio.php
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/ActivityTracker.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = (int)$currentUser['id'];

$gameStudioEngine = new GameStudioEngine();
if (!$gameStudioEngine->isAuthorized($userId)) {
    ?>
    <div class="container-xl py-5 text-center">
        <div class="empty">
            <div class="empty-icon text-danger" style="font-size: 3rem;"><i class="fa-solid fa-lock"></i></div>
            <p class="empty-title">Accès Réservé au Game Studio</p>
            <p class="empty-subtitle text-muted">
                Cet espace est strictement réservé aux <strong>Administrateurs</strong> et membres de la <strong>Dev Team</strong> d'OpenShogun.
            </p>
            <div class="empty-action">
                <a href="/?page=resources" class="btn btn-primary">&larr; Retour au Fief Provincial</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

// 1. Détection de l'onglet actif dans la barre d'outils
$allowedTabs = ['dashboard', 'general'];
$currentTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'dashboard';
$analyticsPeriod = in_array($_GET['period'] ?? '', ['today', '7d', '30d', 'all'], true) ? (string)$_GET['period'] : '30d';

// 2. Préparation des données selon l'onglet
if ($currentTab === 'dashboard') {
    $dashboardData = $gameStudioEngine->getDashboardData($analyticsPeriod);
    extract($dashboardData);
} elseif ($currentTab === 'general') {
    $generalData = $gameStudioEngine->getGeneralData();
    extract($generalData);
}

// 3. Journalisation télémétrique non-bloquante
ActivityTracker::logView($userId, 'gamestudio', $currentTab);
?>

<div class="container-xl py-3">
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- EN-TÊTE PRINCIPAL GAME STUDIO & STATUTS HABILITÉS -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="text-muted small d-flex align-items-center gap-2">
                    <span><i class="fa-solid fa-gamepad text-indigo me-1"></i>OpenShogun Core Framework</span>
                    <span>&bull;</span>
                    <span class="badge bg-indigo-lt text-indigo font-monospace" style="font-size:0.7rem;">v<?= htmlspecialchars(defined('GAME_VERSION') ? GAME_VERSION : '1.27.0') ?></span>
                </div>
                <h2 class="page-title d-flex align-items-center gap-2 mt-1">
                    <span class="text-indigo"><i class="fa-solid fa-laptop-code me-1"></i></span>
                    <span class="fw-bold">Game Studio</span>
                    <span class="badge bg-indigo text-white ms-1 shadow-sm" style="font-size: 0.75rem;">Plateforme Unifiée</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Espace centralisé unifiant le <strong>Studio de Développement</strong> et l'<strong>Administration du Shōgunat</strong> selon les normes d'architecture modulaire MVC.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <?php if ($auth->isAdmin()): ?>
                        <a href="?page=admin" class="btn btn-sm btn-outline-primary" title="Accéder à l'ancien panneau d'administration">
                            <i class="fa-solid fa-gear me-1"></i>Administration
                        </a>
                    <?php endif; ?>
                    <a href="?page=dev_team" class="btn btn-sm btn-outline-purple" title="Accéder aux métiers du jeu vidéo">
                        <i class="fa-solid fa-hammer me-1"></i>Dev Team
                    </a>
                    <a href="?page=resources" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-house me-1"></i>Retour Fief
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- BARRE D'OUTILS SUPÉRIEURE (TOOLBAR GAME STUDIO) -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 shadow-sm border">
        <div class="card-header p-2 bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <!-- Onglets de la barre d'outils -->
            <ul class="nav nav-pills" role="tablist">
                <!-- 1. Dashboard -->
                <li class="nav-item" role="presentation">
                    <a href="?page=gamestudio&tab=dashboard" 
                       class="nav-link px-3 py-2 fw-bold d-flex align-items-center gap-2 <?= $currentTab === 'dashboard' ? 'active bg-primary' : 'text-dark' ?>">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- 2. Général -->
                <li class="nav-item ms-1" role="presentation">
                    <a href="?page=gamestudio&tab=general" 
                       class="nav-link px-3 py-2 fw-bold d-flex align-items-center gap-2 <?= $currentTab === 'general' ? 'active bg-indigo text-white' : 'text-dark' ?>">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Général</span>
                    </a>
                </li>
            </ul>

            <!-- Indicateur d'état du module -->
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-muted border font-monospace" style="font-size:0.75rem;">
                    Vue : views/gamestudio/<?= htmlspecialchars($currentTab) ?>.php
                </span>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- CONTENU DYNAMIQUE DU SOUS-MODULE CHARGÉ EN MVC -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="card-body p-3 p-md-4">
            <?php
            $subviewFile = __DIR__ . '/gamestudio/' . $currentTab . '.php';
            if (file_exists($subviewFile)) {
                require $subviewFile;
            } else {
                require __DIR__ . '/gamestudio/dashboard.php';
            }
            ?>
        </div>
    </div>
</div>
