<?php
/**
 * InstallEngine — Moteur d'installation et de déploiement automatisé d'OpenShogun
 */

class InstallEngine {
    public const LOCK_FILE = __DIR__ . '/../config/installed.lock';
    public const CONFIG_FILE = __DIR__ . '/../config/database.php';
    public const SCHEMA_FILE = __DIR__ . '/../database/schema_complete.sql';
    public const SEED_FILE = __DIR__ . '/../database/seed_complete.sql';

    /**
     * Vérifie si l'application est déjà installée
     */
    public static function isInstalled(): bool {
        return file_exists(self::LOCK_FILE);
    }

    /**
     * Analyse complète des prérequis système
     */
    public static function checkRequirements(): array {
        $checks = [];
        $allPassed = true;

        // 1. Version de PHP (>= 8.1 recommandé)
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.1.0', '>=');
        $checks['php_version'] = [
            'name' => 'Version de PHP',
            'required' => '>= 8.1.0 (Recommandé 8.2+)',
            'current' => $phpVersion,
            'passed' => $phpOk,
            'critical' => true
        ];
        if (!$phpOk) $allPassed = false;

        // 2. Extensions requises
        $requiredExtensions = [
            'pdo' => 'Pilote d\'abstraction base de données PDO',
            'pdo_mysql' => 'Connecteur MySQL pour PDO',
            'mbstring' => 'Gestion multi-octets UTF-8',
            'json' => 'Sérialisation et API JSON',
            'gd' => 'Traitement d\'images (pour avatars & cartes)',
        ];

        foreach ($requiredExtensions as $ext => $label) {
            $loaded = extension_loaded($ext);
            $checks['ext_' . $ext] = [
                'name' => "Extension PHP : $ext ($label)",
                'required' => 'Activée',
                'current' => $loaded ? 'Disponible' : 'Manquante',
                'passed' => $loaded,
                'critical' => true
            ];
            if (!$loaded) $allPassed = false;
        }

        // 3. Extensions optionnelles
        $optExtensions = [
            'curl' => 'Client HTTP cURL (recommandé pour webhooks & APIs)',
            'cairo' => 'Bibliothèque graphique vectorielle pycairo / cairo',
        ];
        foreach ($optExtensions as $ext => $label) {
            $loaded = extension_loaded($ext);
            $checks['opt_' . $ext] = [
                'name' => "Extension : $ext ($label)",
                'required' => 'Optionnelle',
                'current' => $loaded ? 'Disponible' : 'Non installée',
                'passed' => true,
                'critical' => false
            ];
        }

        // 4. Permissions d'écriture sur les répertoires critiques
        $writableDirs = [
            'config' => __DIR__ . '/../config',
            'public/assets' => __DIR__ . '/../public/assets',
            'tmp' => __DIR__ . '/../tmp'
        ];

        foreach ($writableDirs as $rel => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            $isWritable = is_writable($path);
            $checks['dir_' . $rel] = [
                'name' => "Dossier accessible en écriture : $rel/",
                'required' => 'Inscriptible (0775 / 0777)',
                'current' => $isWritable ? 'Accessible en écriture' : 'Verrouillé en lecture seule',
                'passed' => $isWritable,
                'critical' => true
            ];
            if (!$isWritable) $allPassed = false;
        }

        return [
            'checks' => $checks,
            'all_passed' => $allPassed
        ];
    }

    /**
     * Teste la connexion à MySQL avec ou sans sélection de base de données
     */
    public static function testDatabaseConnection(
        string $host,
        string $port,
        string $dbname,
        string $user,
        string $pass,
        bool $createDatabase = false
    ): array {
        try {
            // D'abord tenter une connexion sans dbname pour tester les identifiants
            $dsnWithoutDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsnWithoutDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Vérifier si la base demandée existe
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbname]);
            $exists = (bool)$stmt->fetchColumn();

            if (!$exists) {
                if ($createDatabase) {
                    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $dbStatus = 'Base de données créée avec succès !';
                } else {
                    return [
                        'success' => false,
                        'error' => "La base de données '$dbname' n'existe pas. Cochez l'option pour la créer automatiquement ou créez-la manuellement."
                    ];
                }
            } else {
                $dbStatus = "La base de données '$dbname' existe et est accessible.";
            }

            // Tester la connexion directe sur la base de données
            $dsnWithDb = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
            $pdoDb = new PDO($dsnWithDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            return [
                'success' => true,
                'message' => "Connexion réussie au serveur MySQL ! $dbStatus",
                'db_existed' => $exists
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => "Échec de connexion MySQL : " . $e->getMessage()
            ];
        }
    }

    /**
     * Génère et écrit le fichier config/database.php
     */
    public static function writeDatabaseConfigFile(
        string $host,
        string $port,
        string $dbname,
        string $user,
        string $pass,
        string $siteUrl = 'http://opengalaxy.local',
        int $speedFactor = 1
    ): bool {
        $content = "<?php\n"
            . "/**\n"
            . " * Configuration de la base de données OpenShogun\n"
            . " * Généré automatiquement par l'assistant d'installation le " . date('Y-m-d H:i:s') . "\n"
            . " */\n\n"
            . "define('DB_HOST', " . var_export($host, true) . ");\n"
            . "define('DB_PORT', " . var_export($port, true) . ");\n"
            . "define('DB_NAME', " . var_export($dbname, true) . ");\n"
            . "define('DB_USER', " . var_export($user, true) . ");\n"
            . "define('DB_PASS', " . var_export($pass, true) . ");\n"
            . "define('DB_CHARSET', 'utf8mb4');\n\n"
            . "define('SITE_URL', " . var_export(rtrim($siteUrl, '/'), true) . ");\n"
            . "if (!defined('SPEED_FACTOR')) {\n"
            . "    define('SPEED_FACTOR', " . intval($speedFactor) . ");\n"
            . "}\n";

        return (bool)file_put_contents(self::CONFIG_FILE, $content);
    }

    /**
     * Exécute un fichier de requêtes SQL (séparées par des points-virgules)
     */
    public static function executeSqlFile(PDO $pdo, string $filePath): void {
        if (!file_exists($filePath)) {
            throw new Exception("Fichier SQL introuvable : $filePath");
        }

        $sql = file_get_contents($filePath);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Découpage et exécution bloc par bloc
        $lines = explode("\n", $sql);
        $query = '';
        foreach ($lines as $line) {
            $lineTrim = trim($line);
            if (empty($lineTrim) || str_starts_with($lineTrim, '--') || str_starts_with($lineTrim, '/*')) {
                continue;
            }
            $query .= $line . "\n";
            if (str_ends_with($lineTrim, ';')) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Ignorer les avertissements non critiques
                }
                $query = '';
            }
        }
        if (!empty(trim($query))) {
            $pdo->exec($query);
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * Exécute le processus complet d'installation
     */
    public static function runInstallation(array $params): array {
        $host = trim($params['db_host'] ?? '127.0.0.1');
        $port = trim($params['db_port'] ?? '3306');
        $dbname = trim($params['db_name'] ?? 'openshogun');
        $user = trim($params['db_user'] ?? 'root');
        $pass = $params['db_pass'] ?? '';
        $createDb = !empty($params['create_db']);

        $siteUrl = trim($params['site_url'] ?? 'http://opengalaxy.local');
        $speedFactor = max(1, min(100, intval($params['speed_factor'] ?? 5)));
        $gameTitle = trim($params['game_title'] ?? 'OpenShogun — Chroniques Féodales');

        $adminUser = trim($params['admin_user'] ?? 'nezzar');
        $adminEmail = trim($params['admin_email'] ?? 'admin@openshogun.local');
        $adminPass = $params['admin_pass'] ?? '';
        $adminFaction = in_array($params['admin_faction'] ?? '', ['terran', 'vorash', 'aethelis']) ? $params['admin_faction'] : 'terran';

        if (empty($adminUser) || empty($adminPass)) {
            throw new Exception("Le nom d'utilisateur et le mot de passe du Shogun Administrateur sont requis.");
        }
        if (strlen($adminPass) < 6) {
            throw new Exception("Le mot de passe administrateur doit contenir au moins 6 caractères.");
        }

        // 1. Tester et créer la base de données
        $testRes = self::testDatabaseConnection($host, $port, $dbname, $user, $pass, $createDb);
        if (!$testRes['success']) {
            throw new Exception($testRes['error']);
        }

        // 2. Écrire le fichier config/database.php
        $wroteConfig = self::writeDatabaseConfigFile($host, $port, $dbname, $user, $pass, $siteUrl, $speedFactor);
        if (!$wroteConfig) {
            throw new Exception("Impossible d'écrire le fichier " . self::CONFIG_FILE . ". Vérifiez les permissions d'écriture sur le dossier config/.");
        }

        // 3. Établir la connexion PDO
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 4. Importer le schéma complet consolidé (30 tables)
        self::executeSqlFile($pdo, self::SCHEMA_FILE);

        // 5. Importer les graines de référence (unités, recherches, vaisseaux)
        if (file_exists(self::SEED_FILE)) {
            self::executeSqlFile($pdo, self::SEED_FILE);
        }

        // 6. Configurer le titre du jeu et la vitesse dans game_settings
        $stmtSet = $pdo->prepare("
            INSERT INTO game_settings (setting_key, setting_value, setting_type, updated_at)
            VALUES (?, ?, 'string', NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmtSet->execute(['game_title', $gameTitle]);
        $stmtSet->execute(['game_speed', (string)$speedFactor]);
        $stmtSet->execute(['resource_speed', (string)$speedFactor]);
        $stmtSet->execute(['fleet_speed', (string)$speedFactor]);
        $stmtSet->execute(['bots_enabled', '1']);
        $stmtSet->execute(['bot_colonize_enabled', '1']);
        $stmtSet->execute(['bot_aggressiveness', 'moderate']);
        $stmtSet->execute(['bot_max_planets', '3']);
        $stmtSet->execute(['oasis_density_percent', '2.5']);

        // 7. Initialiser l'Univers Féodal via WorldGenerator
        require_once __DIR__ . '/WorldGenerator.php';
        $worldGen = new WorldGenerator();
        $worldGen->resetUniverse($adminPass, 12, true);

        // Mettre à jour le compte administrateur avec les identifiants saisis
        $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
        $stmtUpAdmin = $pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, password_hash = ?, faction = ? 
            WHERE is_admin = 1 
            ORDER BY id ASC LIMIT 1
        ");
        $stmtUpAdmin->execute([$adminUser, $adminEmail, $adminHash, $adminFaction]);

        // Mettre à jour le nom du château capital
        $stmtUpCap = $pdo->prepare("
            UPDATE planets 
            SET name = ? 
            WHERE is_capital = 1 
            ORDER BY id ASC LIMIT 1
        ");
        $capitalName = "Fief de " . ucfirst($adminUser);
        $stmtUpCap->execute([$capitalName]);

        // 8. Créer le verrou d'installation config/installed.lock
        self::createLockFile([
            'installed_at' => date('c'),
            'app' => 'OpenShogun',
            'version' => '1.0.0',
            'admin_user' => $adminUser,
            'db_name' => $dbname,
            'speed_factor' => $speedFactor,
        ]);

        return [
            'success' => true,
            'admin_user' => $adminUser,
            'admin_email' => $adminEmail,
            'site_url' => $siteUrl,
            'game_title' => $gameTitle
        ];
    }

    /**
     * Crée le fichier de verrouillage config/installed.lock
     */
    public static function createLockFile(array $meta = []): void {
        $meta['locked_at'] = date('Y-m-d H:i:s');
        @file_put_contents(self::LOCK_FILE, json_encode($meta, JSON_PRETTY_PRINT));
    }
}

