<?php
/**
 * Service de Diagnostic et Visualisation des Logs d'Erreur Apache
 * Fichier : core/ApacheLogService.php
 *
 * Détecte, lit, filtre et formate les fichiers de logs du serveur Apache (et PHP)
 * pour le Game Studio d'OpenShogun. Supporte nativement Raspberry Pi OS / Debian Linux,
 * Windows (Wamp, XAMPP, Laragon) et macOS.
 */

class ApacheLogService {

    /**
     * Liste des chemins standards de logs d'erreur Apache sur divers environnements
     */
    protected array $candidatePaths = [
        // Raspberry Pi OS / Debian / Ubuntu (Priorité Linux)
        '/var/log/apache2/error.log',
        '/var/log/apache2/error_log',
        '/var/log/apache2/other_vhosts_access.log',
        '/var/log/httpd/error_log',
        '/var/log/httpd/error.log',
        '/var/log/nginx/error.log',
        '/var/log/lighttpd/error.log',
        '/var/log/syslog',

        // Windows (Wamp, XAMPP, Laragon)
        'C:/wamp64/logs/apache_error.log',
        'C:/wamp/logs/apache_error.log',
        'C:/xampp/apache/logs/error.log',
        'C:/laragon/bin/apache/logs/error.log',

        // macOS & BSD
        '/usr/local/apache2/logs/error.log',
        '/etc/httpd/logs/error_log',
        '/usr/local/var/log/apache2/error_log',
        '/opt/homebrew/var/log/apache2/error_log'
    ];

    /**
     * Détecte le chemin du fichier de log Apache
     */
    public function detectApacheErrorLogPath(?string $requestedPath = null): ?string {
        $isLinux = (PHP_OS_FAMILY === 'Linux') || (DIRECTORY_SEPARATOR === '/');

        // 1. Chemin demandé explicitement (GET ou POST)
        if (!empty($requestedPath)) {
            $normalized = str_replace('\\', '/', trim($requestedPath));
            if (!str_contains($normalized, '..')) {
                // Sur Linux/Raspberry Pi, accepter le chemin même si file_exists() échoue temporairement à cause des permissions
                if (@file_exists($normalized) || @is_file($normalized) || str_starts_with($normalized, '/var/log/')) {
                    return $normalized;
                }
            }
        }

        // 2. Chemin sauvegardé en session
        if (!empty($_SESSION['custom_apache_log_path'])) {
            $sessPath = str_replace('\\', '/', trim($_SESSION['custom_apache_log_path']));
            if (!str_contains($sessPath, '..') && (@file_exists($sessPath) || str_starts_with($sessPath, '/var/log/'))) {
                return $sessPath;
            }
        }

        // 3. Variable d'environnement APACHE_LOG_DIR
        $envLog = getenv('APACHE_LOG_DIR');
        if ($envLog) {
            $envPath = str_replace('\\', '/', $envLog) . '/error.log';
            if (@file_exists($envPath) || str_starts_with($envPath, '/var/log/')) {
                return $envPath;
            }
        }

        // 4. Si Linux / Raspberry Pi : tester en priorité les chemins Linux
        if ($isLinux) {
            $linuxDefaults = [
                '/var/log/apache2/error.log',
                '/var/log/apache2/error_log',
                '/var/log/apache2/other_vhosts_access.log',
                '/var/log/httpd/error_log',
                '/var/log/httpd/error.log',
                '/var/log/nginx/error.log',
                '/var/log/lighttpd/error.log',
                dirname(__DIR__) . '/logs/apache_error.log',
                dirname(__DIR__) . '/logs/error.log',
                dirname(__DIR__) . '/error.log',
                '/var/www/html/logs/error.log',
                '/var/www/html/error.log',
                '/tmp/apache_error.log',
                '/var/log/syslog'
            ];

            // Chercher d'abord un fichier directement lisible
            foreach ($linuxDefaults as $path) {
                if (@file_exists($path) && @is_file($path) && @is_readable($path)) {
                    return $path;
                }
            }

            // Chercher un fichier existant même sans droits complets
            foreach ($linuxDefaults as $path) {
                if (@file_exists($path) && @is_file($path)) {
                    return $path;
                }
            }

            // Si le dossier /var/log/apache2 ou /var/log existe :
            // C'est un Raspberry Pi / Debian standard ! Le chemin est /var/log/apache2/error.log
            if (@is_dir('/var/log/apache2') || @is_dir('/var/log')) {
                return '/var/log/apache2/error.log';
            }
        }

        // 5. Chemins Windows (WAMP, XAMPP, Laragon)
        $windowsCandidates = [
            'C:/wamp64/logs/apache_error.log',
            'C:/wamp/logs/apache_error.log',
            'C:/xampp/apache/logs/error.log',
            'C:/laragon/bin/apache/logs/error.log'
        ];
        foreach ($windowsCandidates as $path) {
            if (@file_exists($path) && @is_file($path)) {
                return $path;
            }
        }

        // 6. Glob pour WAMP ou Laragon avec versions dynamiques
        $globs = [
            'C:/wamp64/bin/apache/apache*/logs/error.log',
            'C:/wamp/bin/apache/apache*/logs/error.log',
            'C:/laragon/bin/apache/httpd-*/logs/error.log',
            '/var/log/apache2/*.log',
            '/var/log/httpd/*.log'
        ];
        foreach ($globs as $pattern) {
            $matches = @glob($pattern);
            if (!empty($matches) && @file_exists($matches[0])) {
                return str_replace('\\', '/', $matches[0]);
            }
        }

        // 7. error_log PHP si disponible
        $iniErrorLog = ini_get('error_log');
        if ($iniErrorLog && @file_exists($iniErrorLog) && @is_file($iniErrorLog)) {
            return str_replace('\\', '/', $iniErrorLog);
        }

        // Par défaut sur Raspberry Pi / Linux : renvoyer le chemin standard Debian
        if ($isLinux) {
            return '/var/log/apache2/error.log';
        }

        return null;
    }

    /**
     * Liste tous les fichiers logs disponibles dans le même dossier pour permettre le basculement
     */
    public function getAvailableLogFiles(?string $currentLogPath = null): array {
        $logs = [];
        $searchDirs = [];

        if ($currentLogPath) {
            $dir = dirname($currentLogPath);
            if (@is_dir($dir)) $searchDirs[] = $dir;
        }

        $commonDirs = [
            '/var/log/apache2',
            '/var/log/httpd',
            '/var/log/nginx',
            dirname(__DIR__) . '/logs',
            'C:/wamp64/logs',
            'C:/wamp/logs',
            'C:/xampp/apache/logs'
        ];
        foreach ($commonDirs as $cd) {
            if (!in_array($cd, $searchDirs, true) && @is_dir($cd)) {
                $searchDirs[] = $cd;
            }
        }

        foreach ($searchDirs as $baseDir) {
            $files = @scandir($baseDir) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                if (str_ends_with(strtolower($f), '.log') || $f === 'syslog' || $f === 'error_log') {
                    $fullPath = str_replace('\\', '/', $baseDir . DIRECTORY_SEPARATOR . $f);
                    $size = @filesize($fullPath) ?: 0;
                    $mtime = @filemtime($fullPath) ?: 0;
                    $logs[] = [
                        'filename'   => $f,
                        'path'       => $fullPath,
                        'size'       => $size,
                        'size_fmt'   => $this->formatFileSize($size),
                        'mtime'      => $mtime,
                        'is_current' => ($currentLogPath && realpath($currentLogPath) === realpath($fullPath))
                    ];
                }
            }
        }

        // Trier avec apache_error.log et error.log en premier
        usort($logs, function ($a, $b) {
            if (str_contains($a['filename'], 'apache_error') || $a['filename'] === 'error.log') return -1;
            if (str_contains($b['filename'], 'apache_error') || $b['filename'] === 'error.log') return 1;
            return strcmp($a['filename'], $b['filename']);
        });

        return $logs;
    }

    /**
     * Lit efficacement les N dernières lignes d'un fichier log sans charger tout le fichier en mémoire
     * Tente successivement fopen natif, puis tail shell et sudo tail si nécessaire (Raspberry Pi).
     */
    public function readTail(string $filePath, int $maxLines = 150): array {
        // 1. Essai avec fopen PHP natif
        $lines = $this->readTailWithPhp($filePath, $maxLines);
        if (!empty($lines)) {
            return $lines;
        }

        // 2. Essai avec la commande système tail (Linux / Raspberry Pi)
        if (function_exists('exec') || function_exists('shell_exec')) {
            $shellLines = $this->readTailWithShell($filePath, $maxLines);
            if (!empty($shellLines)) {
                return $shellLines;
            }
        }

        return [];
    }

    /**
     * Lecture inversée rapide en pur PHP via fseek
     */
    protected function readTailWithPhp(string $filePath, int $maxLines = 150): array {
        if (!@file_exists($filePath) || !@is_readable($filePath)) {
            return [];
        }

        $f = @fopen($filePath, 'rb');
        if (!$f) {
            return [];
        }

        $lines = [];
        $buffer = '';
        $chunkSize = 8192;
        @fseek($f, 0, SEEK_END);
        $pos = @ftell($f);

        while ($pos > 0 && count($lines) <= $maxLines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            @fseek($f, $pos, SEEK_SET);
            $chunk = @fread($f, $readSize);
            $buffer = $chunk . $buffer;

            $split = explode("\n", $buffer);
            $buffer = array_shift($split);

            while (!empty($split)) {
                $rawLine = array_pop($split);
                $cleanLine = rtrim($rawLine, "\r");
                if ($cleanLine !== '') {
                    $lines[] = $cleanLine;
                    if (count($lines) >= $maxLines) {
                        break 2;
                    }
                }
            }
        }

        if ($pos === 0 && $buffer !== '' && count($lines) < $maxLines) {
            $cleanLine = rtrim($buffer, "\r");
            if ($cleanLine !== '') {
                $lines[] = $cleanLine;
            }
        }

        @fclose($f);
        return $lines;
    }

    /**
     * Lecture via tail shell ou sudo -n tail (sur Raspberry Pi / Linux)
     */
    protected function readTailWithShell(string $filePath, int $maxLines = 150): array {
        if (!function_exists('exec')) {
            return [];
        }

        $escaped = escapeshellarg($filePath);
        $output = [];
        $returnVar = 0;

        // 1. tail standard
        @exec("tail -n " . (int)$maxLines . " " . $escaped . " 2>&1", $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $valid = [];
            foreach ($output as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, 'tail: ')) {
                    $valid[] = $line;
                }
            }
            if (!empty($valid)) {
                return array_reverse($valid);
            }
        }

        // 2. sudo -n tail (si NOPASSWD est configuré pour www-data)
        $output = [];
        @exec("sudo -n tail -n " . (int)$maxLines . " " . $escaped . " 2>&1", $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $valid = [];
            foreach ($output as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, 'sudo: ')) {
                    $valid[] = $line;
                }
            }
            if (!empty($valid)) {
                return array_reverse($valid);
            }
        }

        return [];
    }

    /**
     * Tente de lire les logs Apache via systemd journalctl (Debian / Raspberry Pi OS)
     */
    public function readJournalctl(int $maxLines = 150): array {
        if (!function_exists('exec') || PHP_OS_FAMILY !== 'Linux') {
            return [];
        }

        $output = [];
        $returnVar = 0;
        @exec("journalctl -u apache2 -n " . (int)$maxLines . " --no-pager 2>&1", $output, $returnVar);

        if ($returnVar !== 0 || empty($output) || str_contains(implode(' ', $output), 'No journal files') || str_contains(implode(' ', $output), 'Permission denied')) {
            $output = [];
            @exec("sudo -n journalctl -u apache2 -n " . (int)$maxLines . " --no-pager 2>&1", $output, $returnVar);
        }

        if ($returnVar === 0 && !empty($output)) {
            $valid = [];
            foreach ($output as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, '-- ')) {
                    $valid[] = $line;
                }
            }
            if (!empty($valid)) {
                return array_reverse($valid);
            }
        }

        return [];
    }

    /**
     * Parse une ligne de log Apache 2.4 / 2.2, journalctl ou PHP
     */
    public function parseLogLine(string $rawLine): array {
        $entry = [
            'raw'       => $rawLine,
            'date'      => '',
            'module'    => '',
            'level'     => 'info',
            'pid'       => '',
            'tid'       => '',
            'client'    => '',
            'code'      => '',
            'message'   => $rawLine,
            'badge_class' => 'bg-secondary-lt text-secondary'
        ];

        // Format 1 : Apache 2.4 standard
        // [Wed Jan 28 12:58:59.097072 2026] [mpm_winnt:notice] [pid 21424:tid 468] AH00354: Message
        // ou avec client : [core:error] [pid 1234:tid 567] [client 127.0.0.1:54321] AH00124: Message
        $pattern24 = '/^\[(?P<date>[^\]]+)\] \[(?:(?P<module>[^:]+):)?(?P<level>[a-zA-Z]+)\] (?:\[pid (?P<pid>\d+)(?::tid (?P<tid>\d+))?\] )?(?:\[client (?P<client>[^\]]+)\] )?(?:(?P<code>AH\d+): )?(?P<message>.*)$/';

        if (preg_match($pattern24, $rawLine, $m)) {
            $entry['date']    = trim($m['date'] ?? '');
            $entry['module']  = trim($m['module'] ?? '');
            $entry['level']   = strtolower(trim($m['level'] ?? 'info'));
            $entry['pid']     = trim($m['pid'] ?? '');
            $entry['tid']     = trim($m['tid'] ?? '');
            $entry['client']  = trim($m['client'] ?? '');
            $entry['code']    = trim($m['code'] ?? '');
            $entry['message'] = trim($m['message'] ?? '');
        }
        // Format 2 : Journalctl / Syslog
        // Oct 22 21:04:12 raspberrypi apache2[1234]: [core:notice] AH00094: Command line...
        elseif (preg_match('/^(?P<date>[A-Z][a-z]{2}\s+\d+\s+\d{2}:\d{2}:\d{2})\s+[^\s]+\s+(?P<module>[^:\[]+)(?:\[(?P<pid>\d+)\])?:\s*(?P<message>.*)$/', $rawLine, $m)) {
            $entry['date']   = trim($m['date'] ?? '');
            $entry['module'] = trim($m['module'] ?? '');
            $entry['pid']    = trim($m['pid'] ?? '');
            $msg = trim($m['message'] ?? '');

            if (preg_match('/\[(?:(?P<submod>[^:]+):)?(?P<sublevel>[a-zA-Z]+)\]\s*(?:(?P<code>AH\d+):\s*)?(?P<submsg>.*)/', $msg, $sm)) {
                $entry['level']   = strtolower(trim($sm['sublevel'] ?? 'info'));
                $entry['code']    = trim($sm['code'] ?? '');
                $entry['message'] = trim($sm['submsg'] ?? '');
                if (!empty($sm['submod'])) $entry['module'] .= ':' . trim($sm['submod']);
            } else {
                $entry['message'] = $msg;
                if (stripos($msg, 'error') !== false || stripos($msg, 'fatal') !== false) $entry['level'] = 'error';
                elseif (stripos($msg, 'warn') !== false) $entry['level'] = 'warn';
                else $entry['level'] = 'info';
            }
        }
        // Format 3 : PHP Error Log
        // [22-Oct-2026 21:04:12 UTC] PHP Fatal error:  Uncaught Error...
        elseif (preg_match('/^\[(?P<date>[^\]]+)\] PHP (?P<level>[a-zA-Z ]+?): (?P<message>.*)$/', $rawLine, $m)) {
            $entry['date']    = trim($m['date'] ?? '');
            $entry['module']  = 'php';
            $phpLevel = strtolower(trim($m['level'] ?? 'error'));
            if (str_contains($phpLevel, 'fatal') || str_contains($phpLevel, 'parse')) {
                $entry['level'] = 'crit';
            } elseif (str_contains($phpLevel, 'warning')) {
                $entry['level'] = 'warn';
            } elseif (str_contains($phpLevel, 'notice')) {
                $entry['level'] = 'notice';
            } else {
                $entry['level'] = 'error';
            }
            $entry['message'] = trim($m['message'] ?? '');
        }

        // Attribution des badges et styles Tabler
        $level = $entry['level'];
        if (in_array($level, ['emerg', 'alert', 'crit'], true)) {
            $entry['badge_class'] = 'bg-danger text-white fw-bold shadow-sm';
            $entry['level_label'] = 'CRITIQUE';
            $entry['row_class']   = 'border-danger bg-danger-subtle bg-opacity-10';
        } elseif (in_array($level, ['error', 'err'], true)) {
            $entry['badge_class'] = 'bg-danger-lt text-danger fw-bold';
            $entry['level_label'] = 'ERREUR';
            $entry['row_class']   = 'border-danger-subtle';
        } elseif (in_array($level, ['warn', 'warning'], true)) {
            $entry['badge_class'] = 'bg-warning-lt text-dark fw-bold';
            $entry['level_label'] = 'ATTENTION';
            $entry['row_class']   = 'border-warning-subtle';
        } elseif (in_array($level, ['notice'], true)) {
            $entry['badge_class'] = 'bg-cyan-lt text-cyan fw-bold';
            $entry['level_label'] = 'NOTICE';
            $entry['row_class']   = '';
        } elseif (in_array($level, ['info'], true)) {
            $entry['badge_class'] = 'bg-info-lt text-info fw-bold';
            $entry['level_label'] = 'INFO';
            $entry['row_class']   = '';
        } else {
            $entry['badge_class'] = 'bg-secondary-lt text-secondary';
            $entry['level_label'] = strtoupper($level);
            $entry['row_class']   = '';
        }

        return $entry;
    }

    /**
     * Vider le fichier de log Apache
     */
    public function clearLogFile(string $filePath): bool {
        if (!@file_exists($filePath) || !@is_writable($filePath)) {
            if (function_exists('exec')) {
                $escaped = escapeshellarg($filePath);
                @exec("truncate -s 0 " . $escaped . " 2>&1", $out, $ret);
                if ($ret === 0) return true;
                @exec("sudo -n truncate -s 0 " . $escaped . " 2>&1", $out, $ret);
                if ($ret === 0) return true;
            }
            return false;
        }
        $f = @fopen($filePath, 'wb');
        if ($f) {
            fclose($f);
            return true;
        }
        return false;
    }

    /**
     * Assemble l'ensemble des données pour la vue de log
     */
    public function getLogViewData(array $params = []): array {
        $requestedPath = !empty($params['log_file']) ? (string)$params['log_file'] : null;
        $maxLines = isset($params['lines']) ? max(10, min(2000, (int)$params['lines'])) : 100;
        $filterLevel = !empty($params['filter_level']) ? strtolower(trim($params['filter_level'])) : 'all';
        $searchKeyword = !empty($params['search']) ? trim($params['search']) : '';
        $order = !empty($params['order']) && $params['order'] === 'asc' ? 'asc' : 'desc';

        $isLinux = (PHP_OS_FAMILY === 'Linux') || (DIRECTORY_SEPARATOR === '/');
        $currentUser = function_exists('posix_getpwuid') && function_exists('posix_geteuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? 'www-data')
            : (get_current_user() ?: 'www-data');

        // Détection spécifique Raspberry Pi OS
        $isRaspberryPi = false;
        $piModel = '';
        if ($isLinux) {
            if (@file_exists('/proc/device-tree/model')) {
                $piModel = trim((string)@file_get_contents('/proc/device-tree/model'));
                $isRaspberryPi = true;
            } elseif (@file_exists('/etc/rpi-issue')) {
                $piModel = 'Raspberry Pi OS';
                $isRaspberryPi = true;
            } elseif (@file_exists('/etc/debian_version')) {
                $piModel = 'Debian / Raspberry Pi OS';
                $isRaspberryPi = true;
            }
        }

        $logPath = $this->detectApacheErrorLogPath($requestedPath);
        $availableLogs = $this->getAvailableLogFiles($logPath);

        $exists = ($logPath !== null && @file_exists($logPath));
        $isReadable = ($logPath !== null && @is_readable($logPath));
        $fileSize = $exists ? (@filesize($logPath) ?: 0) : 0;
        $mtime = $exists ? (@filemtime($logPath) ?: 0) : 0;
        $isWritable = $exists ? @is_writable($logPath) : false;

        // Lecture des lignes
        $rawLines = [];
        $sourceType = 'file';

        if ($logPath) {
            $rawLines = $this->readTail($logPath, $maxLines);
        }

        // Si la lecture par fichier a échoué sur Linux/Raspberry Pi :
        // Tenter systemd journalctl pour ne pas laisser l'utilisateur bloqué !
        if (empty($rawLines) && $isLinux) {
            $journalLines = $this->readJournalctl($maxLines);
            if (!empty($journalLines)) {
                $rawLines = $journalLines;
                $sourceType = 'journalctl';
                $exists = true;
                $isReadable = true;
            }
        }

        // Diagnostic précis des permissions Linux / Raspberry Pi
        // Sur Debian/Raspberry Pi, /var/log/apache2/ est en 0750 root:adm.
        // www-data ne peut ni lister ni faire stat() sur le fichier tant qu'il n'est pas dans le groupe adm.
        $permissionDenied = false;
        if ($isLinux) {
            if (empty($rawLines) || $sourceType === 'journalctl') {
                if (str_starts_with($logPath ?? '', '/var/log/apache2') || @is_dir('/etc/apache2') || stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache') !== false) {
                    $permissionDenied = true;
                    // Le fichier existe bel et bien sur le système Debian/Raspberry Pi, même si file_exists() retourne false
                    $exists = true;
                }
            } elseif (!$isReadable && str_starts_with($logPath ?? '', '/var/log/')) {
                $permissionDenied = true;
            }
        }

        $parsedEntries = [];
        $stats = [
            'total_parsed' => 0,
            'critical'     => 0,
            'errors'       => 0,
            'warnings'     => 0,
            'notices'      => 0,
            'info'         => 0
        ];

        foreach ($rawLines as $line) {
            $parsed = $this->parseLogLine($line);
            $lvl = $parsed['level'];

            if (in_array($lvl, ['emerg', 'alert', 'crit'], true)) $stats['critical']++;
            elseif (in_array($lvl, ['error', 'err'], true)) $stats['errors']++;
            elseif (in_array($lvl, ['warn', 'warning'], true)) $stats['warnings']++;
            elseif (in_array($lvl, ['notice'], true)) $stats['notices']++;
            else $stats['info']++;

            // Filtrage par niveau
            if ($filterLevel !== 'all') {
                if ($filterLevel === 'error' && !in_array($lvl, ['emerg', 'alert', 'crit', 'error', 'err'], true)) {
                    continue;
                }
                if ($filterLevel === 'warning' && !in_array($lvl, ['warn', 'warning'], true)) {
                    continue;
                }
                if ($filterLevel === 'notice' && !in_array($lvl, ['notice', 'info'], true)) {
                    continue;
                }
            }

            // Filtrage par mot-clé
            if ($searchKeyword !== '') {
                if (stripos($parsed['raw'], $searchKeyword) === false) {
                    continue;
                }
            }

            $parsedEntries[] = $parsed;
        }

        $stats['total_parsed'] = count($parsedEntries);

        // Ordre chronologique si demandé (asc)
        if ($order === 'asc') {
            $parsedEntries = array_reverse($parsedEntries);
        }

        // Commandes recommandées pour Raspberry Pi
        $permissionCommand = "sudo usermod -aG adm " . escapeshellcmd($currentUser) . " && sudo systemctl restart apache2";
        $permissionAlternative = "sudo chmod 755 /var/log/apache2 && sudo chmod 644 /var/log/apache2/*.log";

        return [
            'logPath'               => $logPath,
            'logExists'             => $exists,
            'isReadable'            => $isReadable,
            'permissionDenied'      => $permissionDenied,
            'permissionUser'        => $currentUser,
            'permissionCommand'     => $permissionCommand,
            'permissionAlternative' => $permissionAlternative,
            'isLinux'               => $isLinux,
            'isRaspberryPi'         => $isRaspberryPi,
            'piModel'               => $piModel,
            'sourceType'            => $sourceType,
            'logSize'               => $fileSize,
            'logSizeFmt'            => $this->formatFileSize($fileSize),
            'logMtime'              => $mtime,
            'logMtimeFmt'           => $mtime ? date('d/m/Y H:i:s', $mtime) : 'Inconnue',
            'isWritable'            => $isWritable,
            'availableLogs'         => $availableLogs,
            'parsedEntries'         => $parsedEntries,
            'stats'                 => $stats,
            'maxLines'              => $maxLines,
            'filterLevel'           => $filterLevel,
            'searchKeyword'         => $searchKeyword,
            'order'                 => $order
        ];
    }

    /**
     * Formate une taille d'octets en unité lisible
     */
    protected function formatFileSize(int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', ' ') . ' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
        }
        return $bytes . ' octets';
    }
}
