<?php
/**
 * Configuration GitHub & Mises à Jour Automatiques (OpenShogun)
 */

if (!defined('GITHUB_REPO_OWNER')) {
    define('GITHUB_REPO_OWNER', 'dnau1973-hash');
}
if (!defined('GITHUB_REPO_NAME')) {
    define('GITHUB_REPO_NAME', 'openshogun');
}
if (!defined('GITHUB_DEFAULT_BRANCH')) {
    define('GITHUB_DEFAULT_BRANCH', 'main');
}
if (!defined('GITHUB_API_URL')) {
    define('GITHUB_API_URL', 'https://api.github.com');
}

// Chargement du token local sécurisé (fichier ignoré par git)
if (file_exists(__DIR__ . '/github.local.php')) {
    require_once __DIR__ . '/github.local.php';
}

if (!defined('GITHUB_TOKEN')) {
    define('GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
}

