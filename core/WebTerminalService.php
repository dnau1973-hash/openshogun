<?php
/**
 * Service Backend : Terminal Web & Console Système Game Studio
 * Fichier : core/WebTerminalService.php
 *
 * Fournit une interface sécurisée d'exécution de commandes système et de maintenance
 * pour les administrateurs du jeu (spécifiquement optimisé pour Raspberry Pi OS / Debian Linux).
 */

class WebTerminalService {

    /**
     * Racine du projet OpenShogun
     */
    protected string $basePath;

    /**
     * Timeout maximal d'exécution en secondes
     */
    protected int $maxExecutionTime = 15;

    /**
     * Motifs de commandes bloquées pour la sécurité du serveur
     */
    protected array $blacklistedPatterns = [
        '/\brm\s+.*(-[a-zA-Z]*r|--recursive)/i',
        '/\bmkfs\b/i',
        '/\bfdisk\b/i',
        '/\bdd\s+if=\/dev\/(zero|urandom|random)/i',
        '/:[(][{]\s*:[|]:[&]\s*[}];:/', // Fork bomb
        '/\b(shutdown|reboot|poweroff|init\s+[06])\b/i',
    ];

    public function __construct() {
        $this->basePath = str_replace('\\', '/', realpath(dirname(__DIR__)));
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['terminal_cwd']) || !@is_dir($_SESSION['terminal_cwd'])) {
            $_SESSION['terminal_cwd'] = $this->basePath;
        }
    }

    /**
     * Récupère le répertoire courant de travail
     */
    public function getCwd(): string {
        return $_SESSION['terminal_cwd'] ?? $this->basePath;
    }

    /**
     * Définit le répertoire de travail
     */
    public function setCwd(string $dir): bool {
        $real = realpath($dir);
        if ($real && is_dir($real)) {
            $_SESSION['terminal_cwd'] = str_replace('\\', '/', $real);
            return true;
        }
        return false;
    }

    /**
     * Détecte si le système tourne sous Linux / Raspberry Pi OS
     */
    public function isLinux(): bool {
        return (PHP_OS_FAMILY === 'Linux') || (DIRECTORY_SEPARATOR === '/');
    }

    /**
     * Récupère l'utilisateur web courant
     */
    public function getCurrentUser(): string {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = posix_getpwuid(posix_geteuid());
            return $info['name'] ?? 'www-data';
        }
        return get_current_user() ?: 'www-data';
    }

    /**
     * Récupère le nom d'hôte de la machine
     */
    public function getHostname(): string {
        return gethostname() ?: 'raspberrypi';
    }

    /**
     * Récupère le modèle matériel ou l'OS détaillé (Raspberry Pi OS)
     */
    public function getSystemHardware(): string {
        if ($this->isLinux()) {
            if (@file_exists('/proc/device-tree/model')) {
                return trim((string)@file_get_contents('/proc/device-tree/model'));
            }
            if (@file_exists('/etc/rpi-issue')) {
                return 'Raspberry Pi OS';
            }
            if (@file_exists('/etc/os-release')) {
                $osData = @parse_ini_file('/etc/os-release');
                if (!empty($osData['PRETTY_NAME'])) {
                    return $osData['PRETTY_NAME'];
                }
            }
            return php_uname('s') . ' ' . php_uname('r');
        }
        return php_uname('s') . ' ' . php_uname('r');
    }

    /**
     * Récupère les métriques système (RAM, Espace Disque, Uptime)
     */
    public function getSystemMetrics(): array {
        $cwd = $this->getCwd();
        $freeDisk = @disk_free_space($cwd) ?: 0;
        $totalDisk = @disk_total_space($cwd) ?: 0;
        $usedDisk = max(0, $totalDisk - $freeDisk);
        $diskPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 1) : 0;

        $memTotal = 0;
        $memFree = 0;
        $memAvailable = 0;

        if ($this->isLinux() && @file_exists('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo') ?: '';
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $m)) $memTotal = (int)$m[1] * 1024;
            if (preg_match('/MemFree:\s+(\d+)\s+kB/', $meminfo, $m)) $memFree = (int)$m[1] * 1024;
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $m)) $memAvailable = (int)$m[1] * 1024;
        }

        $usedMem = max(0, $memTotal - ($memAvailable ?: $memFree));
        $memPercent = $memTotal > 0 ? round(($usedMem / $memTotal) * 100, 1) : 0;

        // Uptime
        $uptimeFmt = 'Inconnu';
        if ($this->isLinux() && @file_exists('/proc/uptime')) {
            $upStr = @file_get_contents('/proc/uptime');
            $upSec = (int)floatval(explode(' ', $upStr)[0]);
            $days = floor($upSec / 86400);
            $hours = floor(($upSec % 86400) / 3600);
            $minutes = floor(($upSec % 3600) / 60);
            $uptimeFmt = ($days > 0 ? "{$days}j " : "") . sprintf("%02dh %02dm", $hours, $minutes);
        }

        return [
            'os'            => $this->getSystemHardware(),
            'hostname'      => $this->getHostname(),
            'user'          => $this->getCurrentUser(),
            'arch'          => php_uname('m'),
            'php_version'   => PHP_VERSION,
            'php_sapi'      => PHP_SAPI,
            'uptime'        => $uptimeFmt,
            'disk_total'    => $this->formatBytes($totalDisk),
            'disk_used'     => $this->formatBytes($usedDisk),
            'disk_free'     => $this->formatBytes($freeDisk),
            'disk_percent'  => $diskPercent,
            'mem_total'     => $memTotal > 0 ? $this->formatBytes($memTotal) : 'N/A',
            'mem_used'      => $memTotal > 0 ? $this->formatBytes($usedMem) : 'N/A',
            'mem_free'      => $memTotal > 0 ? $this->formatBytes($memAvailable ?: $memFree) : 'N/A',
            'mem_percent'   => $memPercent,
            'cwd'           => $cwd,
            'base_path'     => $this->basePath
        ];
    }

    /**
     * Exécute une commande shell de manière sécurisée
     */
    public function executeCommand(string $command): array {
        $rawCmd = trim($command);
        if ($rawCmd === '') {
            return [
                'success'           => true,
                'command'           => '',
                'output'            => '',
                'cwd'               => $this->getCwd(),
                'return_code'       => 0,
                'execution_time_ms' => 0
            ];
        }

        // 1. Commande de nettoyage de console (clear)
        if ($rawCmd === 'clear' || $rawCmd === 'cls') {
            return [
                'success'           => true,
                'command'           => $rawCmd,
                'output'            => '__CLEAR__',
                'cwd'               => $this->getCwd(),
                'return_code'       => 0,
                'execution_time_ms' => 0
            ];
        }

        // 2. Gestion spéciale de "cd"
        if (preg_match('/^cd(\s+.*)?$/i', $rawCmd, $m)) {
            $targetDir = isset($m[1]) ? trim($m[1]) : '';
            return $this->handleCdCommand($targetDir, $rawCmd);
        }

        // 3. Filtrage de sécurité (liste noire de commandes destructives)
        foreach ($this->blacklistedPatterns as $pattern) {
            if (preg_match($pattern, $rawCmd)) {
                return [
                    'success'           => false,
                    'command'           => $rawCmd,
                    'output'            => "❌ Commande bloquée par mesure de sécurité système : cette opération est interdite dans le terminal web.",
                    'cwd'               => $this->getCwd(),
                    'return_code'       => 126,
                    'execution_time_ms' => 0
                ];
            }
        }

        // 4. Adaptation des commandes interactives
        if (preg_match('/^top(\s+.*)?$/i', $rawCmd)) {
            $rawCmd = 'top -b -n 1';
        } elseif (preg_match('/^(nano|vim|vi|less|more)(\s+.*)?$/i', $rawCmd, $m)) {
            return [
                'success'           => false,
                'command'           => $rawCmd,
                'output'            => "ℹ️ Les éditeurs et visualiseurs interactifs plein écran ({$m[1]}) ne sont pas supportés dans cette console web.\n👉 Utilisez 'cat', 'head' ou 'tail' pour consulter les fichiers.",
                'cwd'               => $this->getCwd(),
                'return_code'       => 1,
                'execution_time_ms' => 0
            ];
        }

        $startTime = microtime(true);
        $currentCwd = $this->getCwd();

        // 5. Exécution via proc_open avec timeout strict
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $env = array_merge($_ENV, [
            'TERM' => 'xterm-256color',
            'LANG' => 'fr_FR.UTF-8',
            'LC_ALL' => 'fr_FR.UTF-8'
        ]);

        $fullCmd = $rawCmd . ' 2>&1';
        $process = @proc_open($fullCmd, $descriptors, $pipes, $currentCwd, $env);

        if (!is_resource($process)) {
            // Fallback sur exec si proc_open est indisponible
            $outputLines = [];
            $returnCode = 0;
            @exec("cd " . escapeshellarg($currentCwd) . " && " . $fullCmd, $outputLines, $returnCode);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success'           => ($returnCode === 0),
                'command'           => $rawCmd,
                'output'            => implode("\n", $outputLines),
                'cwd'               => $currentCwd,
                'return_code'       => $returnCode,
                'execution_time_ms' => $executionTime
            ];
        }

        // Fermer stdin
        @fclose($pipes[0]);

        // Mode non-bloquant pour la lecture avec surveillance du timeout
        @stream_set_blocking($pipes[1], false);
        @stream_set_blocking($pipes[2], false);

        $output = '';
        $timeoutReached = false;
        $maxSec = $this->maxExecutionTime;

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            $numChanged = @stream_select($read, $write, $except, 0, 200000); // 200ms
            if ($numChanged > 0) {
                foreach ($read as $r) {
                    $chunk = @fread($r, 8192);
                    if ($chunk !== false && $chunk !== '') {
                        $output .= $chunk;
                    }
                }
            }

            $status = @proc_get_status($process);
            if (!$status['running']) {
                // Vider les tampons résiduels
                while ($chunk = @fread($pipes[1], 8192)) $output .= $chunk;
                while ($chunk = @fread($pipes[2], 8192)) $output .= $chunk;
                break;
            }

            if ((microtime(true) - $startTime) > $maxSec) {
                $timeoutReached = true;
                @proc_terminate($process, 9); // SIGKILL
                break;
            }
        }

        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $returnCode = @proc_close($process);

        if ($timeoutReached) {
            $output .= "\n⏱️ [Dépassement de Délai] La commande a été interrompue après {$maxSec} secondes d'exécution.";
            $returnCode = 124;
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // Nettoyage des séquences ANSI d'échappement pour un affichage web impeccable
        $cleanOutput = $this->stripAnsiSequences($output);

        return [
            'success'           => ($returnCode === 0),
            'command'           => $rawCmd,
            'output'            => trim($cleanOutput),
            'cwd'               => $this->getCwd(),
            'return_code'       => $returnCode,
            'execution_time_ms' => $executionTime
        ];
    }

    /**
     * Traite la commande de changement de répertoire (cd)
     */
    protected function handleCdCommand(string $target, string $rawCmd): array {
        $target = trim($target);
        if ($target === '' || $target === '~') {
            $newDir = $this->basePath;
        } elseif (str_starts_with($target, '/')) {
            $newDir = $target;
        } else {
            $newDir = $this->getCwd() . '/' . $target;
        }

        $real = @realpath($newDir);
        if ($real && @is_dir($real)) {
            $realFormatted = str_replace('\\', '/', $real);
            // Vérification de permission minimale
            if (@scandir($realFormatted) !== false) {
                $this->setCwd($realFormatted);
                return [
                    'success'           => true,
                    'command'           => $rawCmd,
                    'output'            => "📂 Répertoire courant : {$realFormatted}",
                    'cwd'               => $realFormatted,
                    'return_code'       => 0,
                    'execution_time_ms' => 0.5
                ];
            } else {
                return [
                    'success'           => false,
                    'command'           => $rawCmd,
                    'output'            => "cd: {$target}: Permission refusée",
                    'cwd'               => $this->getCwd(),
                    'return_code'       => 1,
                    'execution_time_ms' => 0.5
                ];
            }
        }

        return [
            'success'           => false,
            'command'           => $rawCmd,
            'output'            => "cd: {$target}: Aucun dossier de ce type",
            'cwd'               => $this->getCwd(),
            'return_code'       => 1,
            'execution_time_ms' => 0.5
        ];
    }

    /**
     * Supprime ou normalise les codes ANSI d'échappement
     */
    protected function stripAnsiSequences(string $str): string {
        // Enlève les séquences de couleurs et de contrôle de curseur
        $clean = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $str);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean);
        return $clean;
    }

    /**
     * Formate des octets en unités lisibles
     */
    protected function formatBytes(float $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', ' ') . ' Go';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', ' ') . ' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
        }
        return (int)$bytes . ' octets';
    }
}
