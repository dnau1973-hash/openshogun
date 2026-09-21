<?php
/**
 * Configuration de la base de données OpenGalaxy / OpenShogun (Modèle d'exemple)
 * Copier ce fichier vers config/database.php ou utiliser l'assistant install.php
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'openshogun');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'http://localhost');
if (!defined('SPEED_FACTOR')) {
    define('SPEED_FACTOR', 5); // Facteur d'accélération du jeu (x5 pour dynamiser le gameplay)
}
