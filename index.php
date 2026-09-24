<?php
/**
 * Point d'entrée principal et Routeur de l'application OpenShogun
 */
if (!file_exists(__DIR__ . '/config/installed.lock') || !file_exists(__DIR__ . '/config/database.php')) {
    header('Location: /install.php');
    exit;
}

require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();
$authError = null;

// Traitement des actions d'authentification
$action = $_GET['action'] ?? null;

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = $auth->login($username, $password);
    if ($res['success']) {
        header('Location: /');
        exit;
    } else {
        $authError = $res['error'];
    }
} elseif ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $faction = $_POST['faction'] ?? 'terran';
    $zone = $_POST['zone'] ?? 'random';
    $res = $auth->register($username, $email, $password, $faction, $zone);
    if ($res['success']) {
        header('Location: /');
        exit;
    } else {
        $authError = $res['error'];
    }
} elseif ($action === 'logout') {
    $auth->logout();
    header('Location: /');
    exit;
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
}

$allowedPages = ['resources', 'field', 'building', 'city', 'map', 'fleet', 'shipyard', 'barracks', 'research', 'reports', 'ranking', 'messages', 'admin', 'castle', 'docs', 'hero', 'support', 'alliance', 'forum', 'chat', 'empire'];

if (!in_array($page, $allowedPages)) {
    $page = 'resources';
}

// Vérifier les droits si la page demandée est admin
if ($page === 'admin' && !$auth->isAdmin()) {
    header('Location: /?page=resources');
    exit;
}

// Rendu de la vue avec le Layout HUD
require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/' . $page . '.php';
require __DIR__ . '/views/partials/footer.php';

