<?php
/**
 * Service de Diagnostic et Visualisation des Logs d'Erreur Apache
 * Fichier : core/ApacheLogService.php
 *
 * Détecte, lit, filtre et formate les fichiers de logs du serveur Apache (et PHP)
 * pour le Game Studio d'OpenShogun.
 */

class ApacheLogService {

    /**
     * Liste des chemins standards de logs d'erreur Apache sur divers environnements
     */
    protected array $candidatePaths = [
        'C:/wamp64/logs/apache_error.log',
        'C:/wamp/logs/apache_error.log',
        'C:/xampp/apache/logs/error.log',
        'C:/laragon/bin/apache/logs/error.log',
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        '/var/log/httpd/error.log',
        '/usr/local/apache2/logs/error.log',
        '/etc/httpd/logs/error_log',
        '/usr/local/var/log/apache2/error_log',
        '/opt/homebrew/var/log/apache2/error_log'
    ];

    /**
     * Détecte le chemin du fichier de log Apache
     */
    public function detectApacheErrorLogPath(?string $requestedPath = null): ?string {
        // 1. Chemin demandé explicitement
        if (!empty($requestedPath)) {
            $normalized = str_replace('\\', '/', trim($requestedPath));
            // Sécurité anti-traversée
            if (!str_contains($normalized, '..') && file_exists($normalized) && is_file($normalized)) {
                return $normalized;
            }
        }

        // 2. Variable d'environnement
        $envLog = getenv('APACHE_LOG_DIR');
        if ($envLog) {
            $envPath = str_replace('\\', '/', $envLog) . '/error.log';
            if (file_exists($envPath) && is_file($envPath)) {
                return $envPath;
            }
        }

        // 3. Chemins candidats standards
        foreach ($this->candidatePaths as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        // 4. Glob pour WAMP ou Laragon avec versions dynamiques
        $globs = [
            'C:/wamp64/bin/apache/apache*/logs/error.log',
            'C:/wamp/bin/apache/apache*/logs/error.log',
            'C:/laragon/bin/apache/httpd-*/logs/error.log'
        ];
        foreach ($globs as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches) && file_exists($matches[0])) {
                return str_replace('\\', '/', $matches[0]);
            }
        }

        // 5. error_log PHP si disponible et contenant apache
        $iniErrorLog = ini_get('error_log');
        if ($iniErrorLog && file_exists($iniErrorLog) && is_file($iniErrorLog)) {
            return str_replace('\\', '/', $iniErrorLog);
        }

        return null;
    }

    /**
     * Liste tous les fichiers logs disponibles dans le même dossier pour permettre le basculement
     */
    public function getAvailableLogFiles(?string $currentLogPath = null): array {
        $logs = [];
        $baseDir = null;

        if ($currentLogPath && file_exists($currentLogPath)) {
            $baseDir = dirname($currentLogPath);
        } elseif (is_dir('C:/wamp64/logs')) {
            $baseDir = 'C:/wamp64/logs';
        } elseif (is_dir('/var/log/apache2')) {
            $baseDir = '/var/log/apache2';
        }

        if ($baseDir && is_dir($baseDir)) {
            $files = @scandir($baseDir) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                if (str_ends_with(strtolower($f), '.log')) {
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

        // Trier avec apache_error.log en premier
        usort($logs, function ($a, $b) {
            if (str_contains($a['filename'], 'apache_error')) return -1;
            if (str_contains($b['filename'], 'apache_error')) return 1;
            return strcmp($a['filename'], $b['filename']);
        });

        return $logs;
    }

    /**
     * Lit efficacement les N dernières lignes d'un fichier log sans charger tout le fichier en mémoire
     */
    public function readTail(string $filePath, int $maxLines = 150): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
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
            $buffer = array_shift($split); // Conserver le début incomplet

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

        // $lines contient les lignes du plus récent au plus ancien (reverse)
        return $lines;
    }

    /**
     * Parse une ligne de log Apache 2.4 / 2.2 ou PHP
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
        // Format 2 : PHP Error Log
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

        // Attribution des badges et couleurs
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
        if (!file_exists($filePath) || !is_writable($filePath)) {
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

        $logPath = $this->detectApacheErrorLogPath($requestedPath);
        $availableLogs = $this->getAvailableLogFiles($logPath);

        $exists = ($logPath !== null && file_exists($logPath));
        $fileSize = $exists ? filesize($logPath) : 0;
        $mtime = $exists ? filemtime($logPath) : 0;
        $isWritable = $exists ? is_writable($logPath) : false;

        $rawLines = $exists ? $this->readTail($logPath, $maxLines) : [];
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

        return [
            'logPath'          => $logPath,
            'logExists'        => $exists,
            'logSize'          => $fileSize,
            'logSizeFmt'       => $this->formatFileSize($fileSize),
            'logMtime'         => $mtime,
            'logMtimeFmt'      => $mtime ? date('d/m/Y H:i:s', $mtime) : 'Inconnue',
            'isWritable'       => $isWritable,
            'availableLogs'    => $availableLogs,
            'parsedEntries'    => $parsedEntries,
            'stats'            => $stats,
            'maxLines'         => $maxLines,
            'filterLevel'      => $filterLevel,
            'searchKeyword'    => $searchKeyword,
            'order'            => $order
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
