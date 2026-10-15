<?php
/**
 * Game Studio - Contrôleur et Vue Maîtresse (Architecture MVC)
 * Plateforme Unifiée du Studio de Développement et de l'Administration Générale
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
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accès Réservé - Game Studio</title>
        <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
        <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    </head>
    <body class="d-flex flex-column justify-content-center min-vh-100 bg-light">
        <div class="container-xl py-5 text-center">
            <div class="empty">
                <div class="empty-icon text-danger" style="font-size: 3.5rem;"><i class="fa-solid fa-lock"></i></div>
                <p class="empty-title fs-2 fw-bold">Accès Réservé au Game Studio</p>
                <p class="empty-subtitle text-muted fs-4">
                    Cet espace est strictement réservé aux <strong>Administrateurs</strong> et membres de la <strong>Dev Team</strong> d'OpenShogun.
                </p>
                <div class="empty-action mt-4">
                    <a href="/?page=resources" class="btn btn-primary btn-lg">&larr; Retour au Fief Provincial</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Alias de redirection directe vers les sous-onglets de Général
if (($_GET['tab'] ?? '') === 'updates') {
    header('Location: ?page=gamestudio&tab=general&subtab=updates');
    exit;
}
if (($_GET['tab'] ?? '') === 'backups') {
    header('Location: ?page=gamestudio&tab=general&subtab=backups');
    exit;
}

// 1. Détection de l'onglet actif dans la barre d'outils
$allowedTabs = ['dashboard', 'general', 'elevate_research', 'elevate_bots'];
$currentTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'dashboard';
$currentSubTab = in_array($_GET['subtab'] ?? '', ['overview', 'backups', 'updates'], true) ? $_GET['subtab'] : 'overview';
$analyticsPeriod = in_array($_GET['period'] ?? '', ['today', '7d', '30d', 'all'], true) ? (string)$_GET['period'] : '30d';

// 2. Préparation des données selon l'onglet
if ($currentTab === 'dashboard') {
    $dashboardData = $gameStudioEngine->getDashboardData($analyticsPeriod);
    extract($dashboardData);
} elseif ($currentTab === 'general') {
    $generalData = $gameStudioEngine->getGeneralData();
    extract($generalData);
} elseif ($currentTab === 'elevate_research') {
    require_once __DIR__ . '/../core/ResearchElevateService.php';
    $elevateService = new ResearchElevateService();

    // Traitement du formulaire POST avant tout envoi de contenu HTML
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'save_global_coefficients') {
            $elevateService->updateGlobalCoefficients(
                (float)($_POST['research_time_coeff'] ?? 1.0),
                (float)($_POST['research_time_growth'] ?? 1.35),
                (float)($_POST['research_cost_coeff'] ?? 1.0),
                (float)($_POST['research_cost_growth'] ?? 1.65)
            );
            $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Coefficients exponentiels de recherche enregistrés avec succès !'];
            header('Location: ?page=gamestudio&tab=elevate_research');
            exit;
        } elseif ($_POST['action'] === 'save_custom_research') {
            $elevateService->saveResearch($_POST);
            $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Nouvelle technologie enregistrée dans l\'Académie des Savoirs !'];
            header('Location: ?page=gamestudio&tab=elevate_research');
            exit;
        }
    }

    $elevateData = $elevateService->getViewData();
    extract($elevateData);
} elseif ($currentTab === 'elevate_bots') {
    require_once __DIR__ . '/../core/BotEngine.php';
    require_once __DIR__ . '/../core/GameConfig.php';
    $botEngine = new BotEngine();
    $settings = GameConfig::load();
    $botsList = $botEngine->getBots();
    $botSpawnStatus = $botEngine->getBotSpawnStatus();
}

// 3. Journalisation télémétrique non-bloquante
ActivityTracker::logView($userId, 'gamestudio', $currentTab);
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShogun Game Studio &bull; Plateforme Unifiée (<?= ucfirst($currentTab) ?>)</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Tabler UI Framework -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <link rel="stylesheet" href="/public/css/style.css">

    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        :root {
            --studio-primary: #4f46e5;
            --studio-dark: #0f172a;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .studio-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .studio-nav-link {
            font-weight: 600;
            font-size: 0.92rem;
            color: #475569;
            padding: 0.55rem 1rem;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .studio-nav-link:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }
        .studio-nav-link.active {
            color: #ffffff !important;
            background-color: var(--studio-primary) !important;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
        }
        .studio-page-container {
            flex: 1 0 auto;
        }
    </style>
</head>
<body class="antialiased">

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- BARRE D'OUTILS PRINCIPALE GAME STUDIO (STANDALONE NAVBAR)           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<header class="studio-navbar sticky-top py-2">
    <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <!-- 1. Logo & Identité Game Studio -->
        <div class="d-flex align-items-center gap-3">
            <a href="?page=gamestudio&tab=dashboard" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <span class="avatar avatar-md rounded bg-indigo-lt text-indigo shadow-sm" style="font-size: 1.4rem;">
                    <i class="fa-solid fa-laptop-code"></i>
                </span>
                <div class="lh-1">
                    <div class="fw-bold font-game text-dark d-flex align-items-center gap-2" style="font-size: 1.15rem; letter-spacing: 0.04em;">
                        <span>Game Studio</span>
                        <span class="badge bg-indigo text-white font-monospace" style="font-size: 0.65rem;">MVC v<?= htmlspecialchars(defined('GAME_VERSION') ? GAME_VERSION : '1.27.0') ?></span>
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 0.72rem;">
                        Plateforme Unifiée &bull; Shogunat &amp; Dev Team
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Barre d'Outils (Toolbar Centrale : Dashboard, Général) -->
        <nav class="d-flex align-items-center gap-1">
            <a href="?page=gamestudio&tab=dashboard" 
               class="studio-nav-link <?= $currentTab === 'dashboard' ? 'active' : '' ?>"
               title="Tableau de bord exécutif et télémétrie en temps réel">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <!-- Dropdown Onglet Général avec Sous-Menu -->
            <div class="dropdown">
                <a href="#" 
                   class="studio-nav-link dropdown-toggle <?= $currentTab === 'general' ? 'active' : '' ?>"
                   data-bs-toggle="dropdown" 
                   aria-expanded="false"
                   title="Synthèse générale et outils système du Game Studio">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Général</span>
                </a>
                <div class="dropdown-menu shadow-sm">
                    <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                        Administration &amp; Système
                    </div>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'overview') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=overview">
                        <i class="fa-solid fa-gauge text-indigo"></i>
                        <div>
                            <div>Synthèse &amp; Dev Team</div>
                            <div class="small text-secondary">Environnement, KPIs &amp; Accréditations</div>
                        </div>
                    </a>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'backups') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=backups">
                        <i class="fa-solid fa-database text-primary"></i>
                        <div>
                            <div>Gestion des Sauvegardes</div>
                            <div class="small text-secondary">Sauvegardes DDL/DML, USB &amp; Restauration</div>
                        </div>
                    </a>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?= ($currentTab === 'general' && $currentSubTab === 'updates') ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=general&subtab=updates">
                        <i class="fa-solid fa-arrows-rotate text-teal"></i>
                        <div>
                            <div>GitHub Sync &amp; Mises à Jour</div>
                            <div class="small text-secondary">Déploiement continu, commits &amp; synchronisation</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Dropdown Game Elevate Designer -->
            <div class="dropdown">
                <a href="#" 
                   class="studio-nav-link dropdown-toggle <?= str_starts_with($currentTab, 'elevate_') ? 'active' : '' ?>"
                   data-bs-toggle="dropdown" 
                   aria-expanded="false"
                   title="Concepteur de progression et équilibrage de jeu (Game Elevate Designer)">
                    <i class="fa-solid fa-compass-drafting text-warning"></i>
                    <span>Game Elevate Designer</span>
                </a>
                <div class="dropdown-menu shadow-sm">
                    <div class="dropdown-header text-uppercase text-secondary small fw-bold">
                        Équilibrage &amp; Systèmes de Jeu
                    </div>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_research' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_research">
                        <i class="fa-solid fa-scroll text-info"></i>
                        <div>
                            <div>Académie des Savoirs &amp; Forge</div>
                            <div class="small text-secondary">Technologies, Castes féodales, Coûts &amp; Délais</div>
                        </div>
                    </a>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?= $currentTab === 'elevate_bots' ? 'active fw-bold' : '' ?>" href="?page=gamestudio&tab=elevate_bots">
                        <i class="fa-solid fa-robot text-indigo"></i>
                        <div>
                            <div>Clans IA &amp; Daimyōs Autonomes</div>
                            <div class="small text-secondary">Supervision, Cycles de décision &amp; Éclosion PNJ</div>
                        </div>
                    </a>
                </div>
            </div>
        </nav>

        <!-- 3. Outils Rapides & Raccourcis Externes (Jeu, Admin, Profil) -->
        <div class="d-flex align-items-center gap-2">
            <!-- Période d'analyse si sur le dashboard -->
            <?php if ($currentTab === 'dashboard'): ?>
                <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                    <a href="?page=gamestudio&tab=dashboard&period=today" class="btn btn-outline-secondary <?= $analyticsPeriod === 'today' ? 'active fw-bold' : '' ?>">Aujourd'hui</a>
                    <a href="?page=gamestudio&tab=dashboard&period=7d" class="btn btn-outline-secondary <?= $analyticsPeriod === '7d' ? 'active fw-bold' : '' ?>">7 jours</a>
                    <a href="?page=gamestudio&tab=dashboard&period=30d" class="btn btn-outline-secondary <?= $analyticsPeriod === '30d' ? 'active fw-bold' : '' ?>">30 jours</a>
                    <a href="?page=gamestudio&tab=dashboard&period=all" class="btn btn-outline-secondary <?= $analyticsPeriod === 'all' ? 'active fw-bold' : '' ?>">Tout</a>
                </div>
            <?php endif; ?>

            <!-- Raccourci vers le jeu en plein écran -->
            <a href="/?page=resources" target="_blank" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm" title="Ouvrir le jeu OpenShogun dans un nouvel onglet">
                <i class="fa-solid fa-torii-gate me-1"></i>
                <span class="d-none d-sm-inline">Accéder au</span> Fief
            </a>

            <?php if ($auth->isAdmin()): ?>
                <a href="/?page=admin" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" title="Ouvrir l'Administration Générale">
                    <i class="fa-solid fa-gear me-1"></i>
                    <span class="d-none d-md-inline">Administration</span>
                </a>
            <?php endif; ?>

            <a href="/?page=dev_team" target="_blank" class="btn btn-sm btn-outline-purple d-flex align-items-center gap-1 shadow-sm" title="Ouvrir les métiers du jeu vidéo">
                <i class="fa-solid fa-hammer me-1"></i>
                <span class="d-none d-md-inline">Dev Team</span>
            </a>

            <!-- Badge Utilisateur -->
            <div class="d-flex align-items-center gap-2 ps-2 border-start">
                <span class="avatar avatar-sm rounded-circle bg-indigo-lt text-indigo fw-bold">
                    <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 2)) ?>
                </span>
                <div class="d-none d-xl-block lh-1 text-start">
                    <div class="fw-bold small"><?= htmlspecialchars($currentUser['username'] ?? 'Daimyō') ?></div>
                    <div class="text-secondary font-monospace" style="font-size:0.65rem;">
                        <?= $auth->isAdmin() ? 'Administrateur' : 'Dev Team' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- CORPS PRINCIPAL DE LA VUE GAME STUDIO                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<main class="studio-page-container py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4">
        <?php
        $subviewFile = __DIR__ . '/gamestudio/' . $currentTab . '.php';
        if (file_exists($subviewFile)) {
            require $subviewFile;
        } else {
            require __DIR__ . '/gamestudio/dashboard.php';
        }
        ?>
    </div>
</main>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- PIED DE PAGE TECHNIQUE DU GAME STUDIO                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<footer class="footer footer-transparent d-print-none py-3 border-top mt-auto bg-white">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0 small">
                    <li class="list-inline-item">
                        <a href="/?page=docs" target="_blank" class="link-secondary">Documentation API</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/?page=changelog" target="_blank" class="link-secondary">Notes de Version</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/?page=dev_team" target="_blank" class="link-secondary">Studio Métiers</a>
                    </li>
                </ul>
            </div>
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0 small text-secondary">
                    <li class="list-inline-item">
                        <strong>OpenShogun Game Studio</strong> &bull; Architecture Modulaire MVC
                    </li>
                    <li class="list-inline-item">
                        Moteur Télémétrique &amp; Monitoring Intégré
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Modale Universelle pour Messages, Alertes & Confirmations Tabler -->
<div class="modal-overlay" id="customAlertModal">
    <div class="modal-card alert-modal-card" id="customAlertCard">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title text-white m-0" id="customAlertTitle">Décret du Shogunat</h3>
            <button onclick="closeCustomAlert()" class="modal-close-btn text-white btn-close" aria-label="Fermer"></button>
        </div>
        <div class="card-body p-4">
            <div class="alert-modal-content d-flex align-items-center gap-3">
                <span class="alert-modal-icon fs-1" id="customAlertIcon"><i class="fa-solid fa-circle-info text-primary"></i></span>
                <p class="alert-modal-text m-0 fs-3" id="customAlertText"></p>
            </div>
            <div class="alert-modal-actions d-flex justify-content-end gap-2 mt-4" id="customAlertActions">
                <button class="btn btn-primary" id="customAlertBtnOk">Compris</button>
            </div>
        </div>
    </div>
</div>

<!-- Conteneur Universel de Toasts Tabler.io pour Confirmations d'Actions & Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tablerToastContainer" style="z-index: 99999; max-width: 420px;"></div>

<!-- Tabler Core JS & Bootstrap Bundle -->
<script src="/public/css/tabler/tabler.min.js?v=1.0.0-beta21"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="/public/js/app.js"></script>

</body>
</html>
