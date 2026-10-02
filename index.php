<?php
/**
 * Point d'entrée principal et Routeur de l'application OpenShogun
 */
if (!file_exists(__DIR__ . '/config/installed.lock') || !file_exists(__DIR__ . '/config/database.php')) {
    header('Location: /install.php');
    exit;
}

$reqPage = $_GET['page'] ?? null;
$requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($requestUriPath === '/changelog') {
    $_GET['page'] = 'changelog';
    $reqPage = 'changelog';
}

require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();
$authError = null;

// Traitement des actions d'authentification
$action = $_GET['action'] ?? null;

// Routage d'activation /verify-email
if ($requestUriPath === '/verify-email') {
    $_GET['action'] = 'verify_email';
    $action = 'verify_email';
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = $auth->login($username, $password);
    if ($res['success']) {
        header('Location: /');
        exit;
    } else {
        $authError = $res['error'];
        if (!empty($res['unverified'])) {
            $_GET['unverified_email'] = $res['email'] ?? '';
        }
    }
} elseif ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? null;
    $faction = $_POST['faction'] ?? 'terran';
    $zone = $_POST['zone'] ?? 'random';
    $newsletterOptin = !empty($_POST['newsletter_optin']);
    $res = $auth->register($username, $email, $password, $faction, $zone, $passwordConfirm, $newsletterOptin);
    if ($res['success']) {
        if (!empty($res['require_verification'])) {
            header('Location: /?registered_pending=1&email=' . urlencode($res['email']));
            exit;
        }
        header('Location: /');
        exit;
    } else {
        $authError = $res['error'];
    }
} elseif ($action === 'verify_email') {
    $token = $_GET['token'] ?? '';
    $vRes = $auth->verifyEmailToken($token);
    if ($vRes['success']) {
        header('Location: /?verified=1');
        exit;
    } else {
        header('Location: /?verify_error=' . urlencode($vRes['error']));
        exit;
    }
} elseif ($action === 'resend_verification' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ident = $_POST['identifier'] ?? '';
    $rRes = $auth->resendVerification($ident);
    if ($rRes['success']) {
        header('Location: /?resend_success=' . urlencode($rRes['message']));
        exit;
    } else {
        $authError = $rRes['error'];
    }
} elseif ($action === 'logout') {
    $auth->logout();
    header('Location: /');
    exit;
}

// Détection des routes d'administration (/admin ou /admin/{section})
$requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('#^/admin(?:/([a-zA-Z0-9_-]+))?/?$#', $requestUriPath, $adminMatches)) {
    $_GET['page'] = 'admin';
    if (!empty($adminMatches[1])) {
        $_GET['tab'] = $adminMatches[1];
    }
} elseif (preg_match('#^/atelier(?:-pedagogique)?(?:/backend/([a-zA-Z0-9_-]+))?/?$#', $requestUriPath, $atelierMatches)) {
    $_GET['page'] = 'pedagogy';
    if (!empty($atelierMatches[1])) {
        $_GET['lesson'] = $atelierMatches[1];
    }
}

// Détecter si la page demandée est publique (ex: Atelier Pédagogique ou Changelog accessible à tous)
$reqPage = $_GET['page'] ?? 'resources';
if (in_array($reqPage, ['php-poo-singleton', 'pdo-sql-injection', 'routing-get-post'], true)) {
    $_GET['lesson'] = $reqPage;
    $reqPage = 'pedagogy';
    $_GET['page'] = 'pedagogy';
}

if ($reqPage === 'pedagogy' || $reqPage === 'atelier' || $reqPage === 'atelier-pedagogique') {
    $page = 'pedagogy';
    if (!Auth::check()) {
        require __DIR__ . '/views/pedagogy.php';
        exit;
    }
} elseif ($reqPage === 'changelog') {
    $page = 'changelog';
    if (!Auth::check()) {
        require __DIR__ . '/views/changelog.php';
        exit;
    }
}

// Si non connecté, afficher le portail d'authentification
if (!Auth::check()) {
    require __DIR__ . '/views/auth.php';
    exit;
}

// Récupérer la page demandée
$page = $_GET['page'] ?? 'resources';
if ($page === 'galaxy') {
    $page = 'map';
} elseif ($page === 'plus') {
    $page = 'privilege';
} elseif ($page === 'atelier' || $page === 'atelier-pedagogique') {
    $page = 'pedagogy';
} elseif (in_array($page, ['php-poo-singleton', 'pdo-sql-injection', 'routing-get-post'], true)) {
    $_GET['lesson'] = $page;
    $page = 'pedagogy';
} elseif ($page === 'profile') {
    $page = 'poster';
} elseif ($page === 'newsletter') {
    $page = 'newsletter_compose';
}

$allowedPages = ['resources', 'field', 'building', 'city', 'map', 'fleet', 'shipyard', 'barracks', 'research', 'reports', 'ranking', 'messages', 'admin', 'castle', 'docs', 'hero', 'support', 'edit_ticket', 'alliance', 'forum', 'chat', 'empire', 'privilege', 'pedagogy', 'changelog', 'dev_team', 'poster', 'newsletter_compose'];

if (!in_array($page, $allowedPages)) {
    $page = 'resources';
}

// Vérifier les droits si la page demandée est admin
if ($page === 'admin' && !$auth->isAdmin()) {
    header('Location: /?page=resources');
    exit;
}

// Vérifier les droits si la page demandée est la composition de missive impériale
if ($page === 'newsletter_compose') {
    require_once __DIR__ . '/core/DevTeamEngine.php';
    $devEngine = new DevTeamEngine();
    $currentUserId = (int)Auth::id();
    if (!$auth->isAdmin() && !$devEngine->hasPermission($currentUserId, 'community.mailing')) {
        header('Location: /?page=dev_team&tab=mailing&forbidden=1');
        exit;
    }
}

// Vérifier les droits si la page demandée est le Studio Dev Team
if ($page === 'dev_team') {
    require_once __DIR__ . '/core/DevTeamEngine.php';
    $devEngine = new DevTeamEngine();
    $currentDevUserId = (int)Auth::id();
    $isDevAdmin = $auth->isAdmin();

    if (!$devEngine->isDevTeamMember($currentDevUserId)) {
        header('Location: /?page=resources');
        exit;
    }

    // Contrôle d'accès strict côté serveur sur le métier ou l'onglet demandé
    $allowedDevMetiers = $devEngine->getAllowedMetiers($currentDevUserId, $isDevAdmin);
    if (empty($allowedDevMetiers)) {
        http_response_code(403);
        die("Accès interdit : aucun métier autorisé pour votre profil de développement.");
    }

    if (isset($_GET['metier']) && trim((string)$_GET['metier']) !== '') {
        $requestedMetier = trim((string)$_GET['metier']);
        if (!in_array($requestedMetier, $allowedDevMetiers, true)) {
            $fallbackMetier = $allowedDevMetiers[0];
            header('Location: /?page=dev_team&metier=' . urlencode($fallbackMetier) . '&forbidden=1');
            exit;
        }
    }

    $allowedDevTabs = $devEngine->getAllowedTabs($currentDevUserId, $isDevAdmin);
    if (isset($_GET['tab']) && trim((string)$_GET['tab']) !== '') {
        $requestedTab = trim((string)$_GET['tab']);
        if (!in_array($requestedTab, $allowedDevTabs, true)) {
            $fallbackMetier = $allowedDevMetiers[0];
            header('Location: /?page=dev_team&metier=' . urlencode($fallbackMetier) . '&forbidden=1');
            exit;
        }
    }
}

// Rendu de la vue avec le Layout HUD
require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/' . $page . '.php';
require __DIR__ . '/views/partials/footer.php';

