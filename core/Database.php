<?php
/**
 * Singleton Database Connection
 */
require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
            $port = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : '3306');
            $name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'openshogun');
            $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
            $pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
            $charset = getenv('DB_CHARSET') ?: (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die('Erreur de connexion MySQL : ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }

    public static function setConnection(?PDO $pdo): void {
        self::$instance = $pdo;
    }
}

