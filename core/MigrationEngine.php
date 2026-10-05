<?php
/**
 * MigrationEngine — Moteur Idempotent de Migrations et Gestion de Schéma
 * OpenShogun — Architecture DevOps & Base de Données
 */

class MigrationEngine {
    public const MIGRATIONS_DIR = __DIR__ . '/../database/migrations';
    public const TABLE_NAME = 'schema_migrations';

    /**
     * Garantit la présence de la table d'historique de migrations
     */
    public static function ensureMigrationTable(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `" . self::TABLE_NAME . "` (
                `version` VARCHAR(180) NOT NULL PRIMARY KEY,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `execution_time_ms` INT UNSIGNED NOT NULL,
                `checksum` VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Liste les migrations déjà appliquées avec leur checksum
     */
    public static function getAppliedMigrations(PDO $pdo): array {
        self::ensureMigrationTable($pdo);
        $stmt = $pdo->query("SELECT `version`, `checksum`, `applied_at`, `execution_time_ms` FROM `" . self::TABLE_NAME . "` ORDER BY `version` ASC");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['version']] = $row;
        }
        return $results;
    }

    /**
     * Récupère la liste de toutes les migrations disponibles sur le disque
     */
    public static function getAvailableMigrations(): array {
        if (!is_dir(self::MIGRATIONS_DIR)) {
            return [];
        }
        $files = glob(self::MIGRATIONS_DIR . '/*.{sql,php}', GLOB_BRACE);
        if (!$files) return [];
        sort($files, SORT_NATURAL);

        $migrations = [];
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $version = pathinfo($filePath, PATHINFO_FILENAME);
            $content = file_get_contents($filePath);
            $migrations[$version] = [
                'version' => $version,
                'filename' => $filename,
                'path' => $filePath,
                'extension' => $extension,
                'checksum' => hash('sha256', $content)
            ];
        }
        return $migrations;
    }

    /**
     * Liste les migrations en attente d'application
     */
    public static function getPendingMigrations(PDO $pdo): array {
        $applied = self::getAppliedMigrations($pdo);
        $available = self::getAvailableMigrations();

        $pending = [];
        foreach ($available as $version => $info) {
            if (!isset($applied[$version])) {
                $pending[$version] = $info;
            }
        }
        return $pending;
    }

    /**
     * Exécute un fichier SQL de migration bloc par bloc
     */
    public static function executeSqlMigration(PDO $pdo, string $filePath): void {
        $sql = file_get_contents($filePath);
        $sqlClean = preg_replace('!/\*.*?\*/!s', '', $sql);
        $lines = explode("\n", $sqlClean);
        $query = '';

        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if (empty($lineTrim) || str_starts_with($lineTrim, '--') || str_starts_with($lineTrim, '#')) {
                continue;
            }
            $query .= $line . "\n";
            if (str_ends_with($lineTrim, ';')) {
                $trimmedQuery = trim($query);
                if (!empty($trimmedQuery)) {
                    $pdo->exec($trimmedQuery);
                }
                $query = '';
            }
        }
        $remaining = trim($query);
        if (!empty($remaining)) {
            $pdo->exec($remaining);
        }
    }

    /**
     * Exécute toutes les migrations en attente
     */
    public static function run(PDO $pdo, ?callable $logger = null): array {
        self::ensureMigrationTable($pdo);
        $pending = self::getPendingMigrations($pdo);
        $executed = [];

        if (empty($pending)) {
            if ($logger) $logger("Toutes les migrations sont déjà à jour.", 'info');
            return [
                'success' => true,
                'executed_count' => 0,
                'executed' => []
            ];
        }

        foreach ($pending as $version => $m) {
            if ($logger) $logger("Exécution de {$m['filename']}...", 'running');
            $start = microtime(true);

            try {
                if ($m['extension'] === 'sql') {
                    self::executeSqlMigration($pdo, $m['path']);
                } elseif ($m['extension'] === 'php') {
                    // Les scripts PHP reçoivent $pdo en contexte
                    $migrationClosure = function(PDO $db, string $path) {
                        require $path;
                    };
                    $migrationClosure($pdo, $m['path']);
                }

                $durationMs = (int)((microtime(true) - $start) * 1000);

                // Enregistrer dans la table d'historique
                $stmt = $pdo->prepare("
                    INSERT INTO `" . self::TABLE_NAME . "` (`version`, `applied_at`, `execution_time_ms`, `checksum`)
                    VALUES (?, NOW(), ?, ?)
                    ON DUPLICATE KEY UPDATE `applied_at` = NOW(), `execution_time_ms` = VALUES(`execution_time_ms`), `checksum` = VALUES(`checksum`)
                ");
                $stmt->execute([$version, $durationMs, $m['checksum']]);

                $executed[] = [
                    'version' => $version,
                    'duration_ms' => $durationMs,
                    'checksum' => $m['checksum']
                ];

                if ($logger) $logger("✅ {$m['filename']} appliqué avec succès ({$durationMs} ms)", 'success');
            } catch (Throwable $e) {
                if ($logger) $logger("❌ Échec sur {$m['filename']} : " . $e->getMessage(), 'error');
                throw new Exception("Échec de la migration {$m['filename']} : " . $e->getMessage(), 0, $e);
            }
        }

        return [
            'success' => true,
            'executed_count' => count($executed),
            'executed' => $executed
        ];
    }
}
