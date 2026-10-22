<?php
/**
 * Moteur de Supervision Système & Matériel Hôte (Spécifique Raspberry Pi & Linux)
 * Fichier : core/ServerMonitorEngine.php
 * Module : Game Studio - Supervision Serveur
 */

require_once __DIR__ . '/Database.php';

// Alignement sur le fuseau horaire du serveur ou Europe/Paris par défaut
if (date_default_timezone_get() === 'UTC' || empty(ini_get('date.timezone'))) {
    @date_default_timezone_set('Europe/Paris');
}

class ServerMonitorEngine {
    private ?PDO $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = null;
        if ($pdo) {
            $this->db = $pdo;
        } else {
            try {
                if (class_exists('Database')) {
                    $ref = new ReflectionProperty('Database', 'instance');
                    $ref->setAccessible(true);
                    $inst = $ref->getValue();
                    if ($inst instanceof PDO) {
                        $this->db = $inst;
                    }
                }
                if ($this->db === null && class_exists('Database') && file_exists(__DIR__ . '/../config/database.php')) {
                    require_once __DIR__ . '/../config/database.php';
                    $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
                    $port = defined('DB_PORT') ? DB_PORT : '3306';
                    $dbname = defined('DB_NAME') ? DB_NAME : 'openshogun';
                    $user = defined('DB_USER') ? DB_USER : 'root';
                    $pass = defined('DB_PASS') ? DB_PASS : '';
                    $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    $this->db = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                        PDO::ATTR_TIMEOUT => 2
                    ]);
                }
            } catch (Throwable $e) {
                $this->db = null;
            }
        }
    }

    /**
     * Diagnostic complet et instantané du serveur hôte
     */
    public function getSystemDiagnostics(): array {
        // Enregistrement automatique transparent d'un relevé toutes les 30s
        $this->recordHardwareLog(false, 30);

        return [
            'timestamp'             => time(),
            'datetime'              => date('Y-m-d H:i:s'),
            'system_time'           => date('d/m/Y H:i:s'),
            'system_time_formatted' => date('d/m/Y H:i:s'),
            'timezone'              => date_default_timezone_get(),
            'hardware'              => $this->getHardwareInfo(),
            'cpu'              => $this->getCpuAndThermalInfo(),
            'memory'           => $this->getMemoryAndSwapInfo(),
            'disk'             => $this->getDiskInfo(),
            'database'         => $this->getDatabaseMetrics(),
            'php'              => $this->getPhpAndOpcacheInfo(),
            'network'          => $this->getNetworkInfo(),
            'uptime'           => $this->getUptimeInfo(),
            'health_score'     => $this->calculateHealthScore(),
            'hardware_summary' => $this->getHardwareLogSummary()
        ];
    }

    /**
     * Informations sur la carte Raspberry Pi & OS Linux
     */
    public function getHardwareInfo(): array {
        $model = 'Linux Server (ARM/x86)';
        $deviceTree = '/proc/device-tree/model';
        if (is_readable($deviceTree)) {
            $rawModel = @file_get_contents($deviceTree);
            if (!empty($rawModel)) {
                $model = trim(str_replace("\0", '', $rawModel));
            }
        } elseif (is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo') ?: '';
            if (preg_match('/Model\s*:\s*(.+)/i', $cpuinfo, $m)) {
                $model = trim($m[1]);
            } elseif (preg_match('/model\s+name\s*:\s*(.+)/i', $cpuinfo, $m)) {
                $model = trim($m[1]);
            } elseif (preg_match('/Hardware\s*:\s*(.+)/i', $cpuinfo, $m)) {
                $model = trim($m[1]);
            }
        }

        $cores = 1;
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo') ?: '';
            $coresCount = substr_count($cpuinfo, 'processor');
            if ($coresCount > 0) $cores = $coresCount;
        }

        return [
            'model'        => $model,
            'is_rpi'       => (stripos($model, 'Raspberry') !== false),
            'os'           => php_uname('s') . ' ' . php_uname('r'),
            'arch'         => php_uname('m'),
            'hostname'     => gethostname(),
            'cores'        => $cores
        ];
    }

    /**
     * Métriques CPU, charge et température thermique du Raspberry Pi
     */
    public function getCpuAndThermalInfo(): array {
        // Température CPU Raspberry Pi
        $tempCelsius = null;
        $thermalPath = '/sys/class/thermal/thermal_zone0/temp';
        if (is_readable($thermalPath)) {
            $rawTemp = @file_get_contents($thermalPath);
            if ($rawTemp !== false && is_numeric(trim($rawTemp))) {
                $tempVal = (float)trim($rawTemp);
                // Si la valeur est en millidegrés (ex: 45000 pour 45°C)
                $tempCelsius = round($tempVal > 1000 ? ($tempVal / 1000) : $tempVal, 1);
            }
        }

        // Évaluation du niveau de chauffe
        $thermalStatus = 'normal';
        $thermalLabel = 'Température Nominale';
        $thermalClass = 'success';
        if ($tempCelsius !== null) {
            if ($tempCelsius >= 80) {
                $thermalStatus = 'throttling_critical';
                $thermalLabel = 'Surchauffe Critique (Throttling)';
                $thermalClass = 'danger';
            } elseif ($tempCelsius >= 70) {
                $thermalStatus = 'warning_high';
                $thermalLabel = 'Charge Thermique Élevée';
                $thermalClass = 'warning';
            } elseif ($tempCelsius >= 55) {
                $thermalStatus = 'warm';
                $thermalLabel = 'Température Modérée';
                $thermalClass = 'info';
            }
        }

        // Charge CPU (Load Average)
        $loadAvg = [0.0, 0.0, 0.0];
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                $loadAvg = [round($load[0], 2), round($load[1], 2), round($load[2], 2)];
            }
        } elseif (is_readable('/proc/loadavg')) {
            $rawLoad = @file_get_contents('/proc/loadavg') ?: '';
            $parts = explode(' ', trim($rawLoad));
            if (count($parts) >= 3) {
                $loadAvg = [round((float)$parts[0], 2), round((float)$parts[1], 2), round((float)$parts[2], 2)];
            }
        }

        $cores = $this->getHardwareInfo()['cores'] ?: 1;
        $loadPercent = min(100, round(($loadAvg[0] / $cores) * 100, 1));

        // Fréquence CPU en MHz (Spécifique Linux / Raspberry Pi cpufreq)
        $freqMhz = null;
        $freqMaxMhz = null;
        $freqCurPath = '/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq';
        $freqMaxPath = '/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq';
        if (is_readable($freqCurPath)) {
            $curVal = (int)@file_get_contents($freqCurPath);
            if ($curVal > 0) $freqMhz = round($curVal / 1000);
        }
        if (is_readable($freqMaxPath)) {
            $maxVal = (int)@file_get_contents($freqMaxPath);
            if ($maxVal > 0) $freqMaxMhz = round($maxVal / 1000);
        }

        // Détection de Throttling (Bridage par surchauffe)
        $throttlingDetected = false;
        if ($freqMhz && $freqMaxMhz && ($freqMhz < $freqMaxMhz * 0.75) && ($tempCelsius !== null && $tempCelsius >= 68)) {
            $throttlingDetected = true;
            $thermalStatus = 'throttling_critical';
            $thermalLabel = 'Bridage Fréquence (Throttling)';
            $thermalClass = 'danger';
        }

        return [
            'temperature'          => $tempCelsius,
            'thermal_status'       => $thermalStatus,
            'thermal_label'        => $thermalLabel,
            'thermal_class'        => $thermalClass,
            'load_1m'              => $loadAvg[0],
            'load_5m'              => $loadAvg[1],
            'load_15m'             => $loadAvg[2],
            'load_percent'         => $loadPercent,
            'cores'                => $cores,
            'frequency_mhz'        => $freqMhz,
            'frequency_max_mhz'    => $freqMaxMhz,
            'throttling_detected'  => $throttlingDetected
        ];
    }

    /**
     * Utilisation de la mémoire vive physique (RAM) et du Swap
     */
    public function getMemoryAndSwapInfo(): array {
        $memTotal = 0;
        $memFree = 0;
        $memAvailable = 0;
        $buffers = 0;
        $cached = 0;
        $swapTotal = 0;
        $swapFree = 0;

        if (is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo') ?: '';
            foreach (explode("\n", $meminfo) as $line) {
                if (preg_match('/^(\w+):\s+(\d+)\s+kB/i', $line, $m)) {
                    $val = (int)$m[2];
                    switch ($m[1]) {
                        case 'MemTotal':     $memTotal = $val; break;
                        case 'MemFree':      $memFree = $val; break;
                        case 'MemAvailable': $memAvailable = $val; break;
                        case 'Buffers':      $buffers = $val; break;
                        case 'Cached':       $cached = $val; break;
                        case 'SwapTotal':    $swapTotal = $val; break;
                        case 'SwapFree':     $swapFree = $val; break;
                    }
                }
            }
        }

        // Si MemAvailable n'est pas fourni (anciens noyaux), on l'estime
        if ($memAvailable === 0 && $memTotal > 0) {
            $memAvailable = $memFree + $buffers + $cached;
        }

        $memUsed = max(0, $memTotal - $memAvailable);
        $memPercent = ($memTotal > 0) ? round(($memUsed / $memTotal) * 100, 1) : 0;

        $swapUsed = max(0, $swapTotal - $swapFree);
        $swapPercent = ($swapTotal > 0) ? round(($swapUsed / $swapTotal) * 100, 1) : 0;

        return [
            'ram_total_mb'     => round($memTotal / 1024, 1),
            'ram_used_mb'      => round($memUsed / 1024, 1),
            'ram_available_mb' => round($memAvailable / 1024, 1),
            'ram_percent'      => $memPercent,
            'ram_status_class' => ($memPercent > 90 ? 'danger' : ($memPercent > 75 ? 'warning' : 'success')),
            'swap_total_mb'    => round($swapTotal / 1024, 1),
            'swap_used_mb'     => round($swapUsed / 1024, 1),
            'swap_percent'     => $swapPercent,
            'swap_warning'     => ($swapPercent > 35),
            'swap_status_class'=> ($swapPercent > 50 ? 'danger' : ($swapPercent > 20 ? 'warning' : 'secondary'))
        ];
    }

    /**
     * Utilisation de l'espace disque (Carte SD / SSD)
     */
    public function getDiskInfo(): array {
        $rootPath = '/';
        $totalBytes = @disk_total_space($rootPath) ?: 0;
        $freeBytes = @disk_free_space($rootPath) ?: 0;
        $usedBytes = max(0, $totalBytes - $freeBytes);

        $totalGb = round($totalBytes / (1024 * 1024 * 1024), 1);
        $freeGb = round($freeBytes / (1024 * 1024 * 1024), 1);
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 1);
        $usedPercent = ($totalBytes > 0) ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        return [
            'total_gb'     => $totalGb,
            'used_gb'      => $usedGb,
            'free_gb'      => $freeGb,
            'used_percent' => $usedPercent,
            'status_class' => ($usedPercent > 90 ? 'danger' : ($usedPercent > 80 ? 'warning' : 'success')),
            'is_critical'  => ($usedPercent > 92 || $freeGb < 1.0)
        ];
    }

    /**
     * Métriques de santé et de latence de la base MySQL / MariaDB
     */
    public function getDatabaseMetrics(): array {
        if (!$this->db) {
            return [
                'status'         => 'error',
                'connected'      => false,
                'latency_ms'     => null,
                'threads_active' => 0,
                'db_size_mb'     => 0,
                'version'        => 'Inaccessible'
            ];
        }

        // Mesure de la latence de requête en millisecondes
        $start = microtime(true);
        $latencyMs = 0.0;
        try {
            $stmt = $this->db->query("SELECT 1");
            $stmt->fetchColumn();
            $latencyMs = round((microtime(true) - $start) * 1000, 2);
        } catch (Throwable $e) {
            $latencyMs = -1;
        }

        // Connexions SQL et métriques serveur
        $threadsActive = 1;
        $version = 'MySQL';
        try {
            $stThreads = $this->db->query("SHOW STATUS LIKE 'Threads_connected'");
            $row = $stThreads ? $stThreads->fetch(PDO::FETCH_ASSOC) : null;
            if ($row) $threadsActive = (int)$row['Value'];

            $stVer = $this->db->query("SELECT VERSION()");
            $v = $stVer ? $stVer->fetchColumn() : null;
            if ($v) $version = $v;
        } catch (Throwable $e) {}

        // Taille de la base de données
        $dbSizeMb = 0.0;
        try {
            $stSize = $this->db->query("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
            ");
            $val = $stSize ? $stSize->fetchColumn() : 0;
            if ($val) $dbSizeMb = (float)$val;
        } catch (Throwable $e) {}

        return [
            'status'         => ($latencyMs >= 0 ? 'online' : 'degraded'),
            'connected'      => true,
            'latency_ms'     => $latencyMs,
            'latency_class'  => ($latencyMs < 5 ? 'success' : ($latencyMs < 20 ? 'info' : ($latencyMs < 50 ? 'warning' : 'danger'))),
            'threads_active' => $threadsActive,
            'db_size_mb'     => $dbSizeMb,
            'version'        => $version
        ];
    }

    /**
     * Informations sur l'interpréteur PHP & accélérateur d'opcodes (OPcache)
     */
    public function getPhpAndOpcacheInfo(): array {
        $opcacheEnabled = false;
        $opcacheHitRate = 0.0;
        $opcacheUsedMb = 0.0;
        $opcacheFreeMb = 0.0;

        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if (is_array($status) && !empty($status['opcache_enabled'])) {
                $opcacheEnabled = true;
                if (!empty($status['opcache_statistics'])) {
                    $opcacheHitRate = round((float)($status['opcache_statistics']['opcache_hit_rate'] ?? 0), 1);
                }
                if (!empty($status['memory_usage'])) {
                    $opcacheUsedMb = round(($status['memory_usage']['used_memory'] ?? 0) / (1024 * 1024), 1);
                    $opcacheFreeMb = round(($status['memory_usage']['free_memory'] ?? 0) / (1024 * 1024), 1);
                }
            }
        }

        return [
            'version'         => phpversion(),
            'sapi'            => php_sapi_name(),
            'memory_limit'    => ini_get('memory_limit'),
            'max_exec_time'   => ini_get('max_execution_time'),
            'opcache_enabled' => $opcacheEnabled,
            'opcache_hit_rate'=> $opcacheHitRate,
            'opcache_used_mb' => $opcacheUsedMb,
            'opcache_free_mb' => $opcacheFreeMb
        ];
    }

    /**
     * Uptime et disponibilité du système
     */
    public function getUptimeInfo(): array {
        $seconds = 0;
        if (is_readable('/proc/uptime')) {
            $raw = @file_get_contents('/proc/uptime') ?: '';
            $parts = explode(' ', trim($raw));
            if (!empty($parts[0])) $seconds = (int)$parts[0];
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return [
            'seconds'   => $seconds,
            'days'      => $days,
            'hours'     => $hours,
            'minutes'   => $minutes,
            'formatted' => ($days > 0 ? "{$days}j " : '') . "{$hours}h {$minutes}m"
        ];
    }

    /**
     * Télémétrie réseau complète & estimation du débit instantané (Rx/Tx)
     */
    public function getNetworkInfo(): array {
        $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        $interfaces = [];
        $totalRxBytes = 0.0;
        $totalTxBytes = 0.0;
        $totalRxPackets = 0;
        $totalTxPackets = 0;

        if (is_readable('/proc/net/dev')) {
            $lines = @file('/proc/net/dev') ?: [];
            foreach ($lines as $line) {
                if (strpos($line, ':') === false) continue;
                [$ifaceName, $data] = explode(':', $line, 2);
                $ifaceName = trim($ifaceName);
                $parts = preg_split('/\s+/', trim($data));
                if (count($parts) >= 10) {
                    $rxB = (float)$parts[0];
                    $rxP = (int)$parts[1];
                    $txB = (float)$parts[8];
                    $txP = (int)$parts[9];

                    $interfaces[$ifaceName] = [
                        'interface'  => $ifaceName,
                        'rx_bytes'   => $rxB,
                        'rx_mb'      => round($rxB / (1024 * 1024), 2),
                        'rx_packets' => $rxP,
                        'tx_bytes'   => $txB,
                        'tx_mb'      => round($txB / (1024 * 1024), 2),
                        'tx_packets' => $txP,
                        'is_loopback'=> ($ifaceName === 'lo')
                    ];

                    if ($ifaceName !== 'lo') {
                        $totalRxBytes += $rxB;
                        $totalTxBytes += $txB;
                        $totalRxPackets += $rxP;
                        $totalTxPackets += $txP;
                    }
                }
            }
        }

        // Détermination de l'interface principale
        $primaryIface = 'eth0';
        $primaryType = 'Ethernet (Filaire)';
        $foundNonLo = false;

        foreach ($interfaces as $name => $meta) {
            if ($name !== 'lo') {
                $primaryIface = $name;
                $foundNonLo = true;
                if (str_starts_with($name, 'wlan') || str_starts_with($name, 'wifi')) {
                    $primaryType = 'Wi-Fi (Sans fil)';
                } elseif (str_starts_with($name, 'eth') || str_starts_with($name, 'en')) {
                    $primaryType = 'Ethernet (Filaire)';
                } else {
                    $primaryType = 'Interface Réseau (' . $name . ')';
                }
                break;
            }
        }

        if (!$foundNonLo && isset($interfaces['lo'])) {
            $primaryIface = 'lo';
            $primaryType = 'Boucle Locale (Loopback)';
            $totalRxBytes = $interfaces['lo']['rx_bytes'];
            $totalTxBytes = $interfaces['lo']['tx_bytes'];
            $totalRxPackets = $interfaces['lo']['rx_packets'];
            $totalTxPackets = $interfaces['lo']['tx_packets'];
        }

        // Calcul du débit instantané (Ko/s)
        $now = microtime(true);
        $rxSpeedKbps = 0.0;
        $txSpeedKbps = 0.0;
        $cacheFile = __DIR__ . '/../config/.net_traffic_cache.json';

        $prevData = null;
        if (file_exists($cacheFile)) {
            $prevData = @json_decode((string)@file_get_contents($cacheFile), true);
        }

        if ($prevData && isset($prevData['time'], $prevData['rx_bytes'], $prevData['tx_bytes'])) {
            $elapsed = $now - (float)$prevData['time'];
            if ($elapsed > 0.4 && $elapsed < 300) {
                $deltaRx = max(0, $totalRxBytes - (float)$prevData['rx_bytes']);
                $deltaTx = max(0, $totalTxBytes - (float)$prevData['tx_bytes']);
                $rxSpeedKbps = round(($deltaRx / 1024) / $elapsed, 1);
                $txSpeedKbps = round(($deltaTx / 1024) / $elapsed, 1);
            }
        }

        @file_put_contents($cacheFile, json_encode([
            'time'     => $now,
            'rx_bytes' => $totalRxBytes,
            'tx_bytes' => $totalTxBytes
        ]), LOCK_EX);

        return [
            'server_ip'      => $serverIp,
            'port'           => (int)($_SERVER['SERVER_PORT'] ?? 80),
            'scheme'         => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
            'primary_iface'  => $primaryIface,
            'primary_type'   => $primaryType,
            'total_rx_mb'    => round($totalRxBytes / (1024 * 1024), 2),
            'total_tx_mb'    => round($totalTxBytes / (1024 * 1024), 2),
            'total_rx_gb'    => round($totalRxBytes / (1024 * 1024 * 1024), 3),
            'total_tx_gb'    => round($totalTxBytes / (1024 * 1024 * 1024), 3),
            'rx_speed_kbps'  => $rxSpeedKbps,
            'tx_speed_kbps'  => $txSpeedKbps,
            'rx_packets'     => $totalRxPackets,
            'tx_packets'     => $totalTxPackets,
            'interfaces'     => $interfaces
        ];
    }

    /**
     * Calcul d'un score de santé global du Raspberry Pi (0 à 100%)
     */
    public function calculateHealthScore(): array {
        $score = 100;
        $reasons = [];

        // 1. Température
        $cpu = $this->getCpuAndThermalInfo();
        if ($cpu['temperature'] !== null) {
            if ($cpu['temperature'] >= 80) {
                $score -= 35;
                $reasons[] = "Surchauffe processeur critique ({$cpu['temperature']}°C)";
            } elseif ($cpu['temperature'] >= 72) {
                $score -= 15;
                $reasons[] = "Température CPU élevée ({$cpu['temperature']}°C)";
            }
        }

        // 2. Charge
        if ($cpu['load_percent'] > 90) {
            $score -= 20;
            $reasons[] = "Surcharge CPU ({$cpu['load_percent']}%)";
        }

        // 3. RAM & Swap
        $mem = $this->getMemoryAndSwapInfo();
        if ($mem['ram_percent'] > 90) {
            $score -= 20;
            $reasons[] = "Mémoire vive presque saturée ({$mem['ram_percent']}%)";
        }
        if ($mem['swap_percent'] > 40) {
            $score -= 15;
            $reasons[] = "Swap mémoire actif élevé ({$mem['swap_percent']}%)";
        }

        // 4. Disque
        $disk = $this->getDiskInfo();
        if ($disk['used_percent'] > 90) {
            $score -= 25;
            $reasons[] = "Espace disque critique ({$disk['used_percent']}%)";
        }

        // 5. BDD
        $db = $this->getDatabaseMetrics();
        if (!$db['connected'] || $db['latency_ms'] === -1) {
            $score -= 40;
            $reasons[] = "Base de données non accessible";
        } elseif ($db['latency_ms'] > 40) {
            $score -= 10;
            $reasons[] = "Latence BDD élevée ({$db['latency_ms']} ms)";
        }

        $score = max(0, min(100, $score));

        $badgeClass = 'success';
        $label = 'Excellente';
        if ($score < 50) {
            $badgeClass = 'danger';
            $label = 'Critique';
        } elseif ($score < 75) {
            $badgeClass = 'warning';
            $label = 'Vigilance';
        } elseif ($score < 90) {
            $badgeClass = 'info';
            $label = 'Bonne';
        }

        return [
            'score'       => $score,
            'label'       => $label,
            'class'       => $badgeClass,
            'issues'      => $reasons
        ];
    }

    /**
     * Action administrative : purge d'urgence du cache OPcache PHP
     */
    public function resetOpcache(): array {
        if (function_exists('opcache_reset')) {
            $res = @opcache_reset();
            return [
                'success' => $res,
                'message' => $res ? "Cache d'opcodes PHP (OPcache) purgé avec succès !" : "Échec de la purge OPcache."
            ];
        }
        return [
            'success' => false,
            'message' => "L'extension OPcache n'est pas active sur ce serveur."
        ];
    }

    // =========================================================================
    // MODULE DE JOURNALISATION DE TÉLÉMÉTRIE MATÉRIELLE (LOGS RASPBERRY PI)
    // =========================================================================

    /**
     * Chemin vers le fichier de stockage des logs matériels
     */
    public static function getHardwareLogsPath(): string {
        $dir = __DIR__ . '/../config';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . '/server_hardware_logs.json';
    }

    /**
     * Enregistre un instantané de télémétrie matérielle (Température, Load, RAM, Swap, Réseau)
     */
    public function recordHardwareLog(bool $force = false, int $minIntervalSec = 30): array {
        $path = self::getHardwareLogsPath();
        $logs = [];
        if (file_exists($path)) {
            $logs = @json_decode((string)@file_get_contents($path), true) ?: [];
        }

        $now = time();
        if (!$force && !empty($logs)) {
            $last = end($logs);
            if (isset($last['timestamp']) && ($now - (int)$last['timestamp']) < $minIntervalSec) {
                return $last;
            }
        }

        $cpu = $this->getCpuAndThermalInfo();
        $mem = $this->getMemoryAndSwapInfo();
        $disk = $this->getDiskInfo();
        $net = $this->getNetworkInfo();
        $health = $this->calculateHealthScore();

        $entry = [
            'id'                => count($logs) + 1,
            'timestamp'         => $now,
            'datetime'          => date('Y-m-d H:i:s', $now),
            'time_label'        => date('H:i:s', $now),
            'cpu_temp'          => $cpu['temperature'],
            'thermal_status'    => $cpu['thermal_status'],
            'cpu_load_1m'       => $cpu['load_1m'],
            'cpu_load_5m'       => $cpu['load_5m'],
            'cpu_load_percent'  => $cpu['load_percent'],
            'cpu_freq_mhz'      => $cpu['frequency_mhz'],
            'ram_used_mb'       => $mem['ram_used_mb'],
            'ram_total_mb'      => $mem['ram_total_mb'],
            'ram_percent'       => $mem['ram_percent'],
            'swap_used_mb'      => $mem['swap_used_mb'],
            'swap_percent'      => $mem['swap_percent'],
            'disk_percent'      => $disk['used_percent'],
            'net_rx_mb'         => $net['total_rx_mb'],
            'net_tx_mb'         => $net['total_tx_mb'],
            'net_rx_speed_kbps' => $net['rx_speed_kbps'],
            'net_tx_speed_kbps' => $net['tx_speed_kbps'],
            'health_score'      => $health['score']
        ];

        $logs[] = $entry;

        // Rotation : conserver les 300 derniers relevés
        if (count($logs) > 300) {
            $logs = array_slice($logs, -300);
        }

        @file_put_contents($path, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $entry;
    }

    /**
     * Récupère la liste des logs matériels ordonnée par date décroissante
     */
    public function getHardwareLogs(int $limit = 60): array {
        $path = self::getHardwareLogsPath();
        if (!file_exists($path)) {
            $first = $this->recordHardwareLog(true, 0);
            return [$first];
        }

        $logs = @json_decode((string)@file_get_contents($path), true) ?: [];
        if (empty($logs)) {
            $first = $this->recordHardwareLog(true, 0);
            return [$first];
        }

        if ($limit > 0 && count($logs) > $limit) {
            $logs = array_slice($logs, -$limit);
        }

        return array_reverse($logs);
    }

    /**
     * Synthèse statistique des relevés (Pics de chauffe, Charge Max, RAM Max, Réseau cumulé)
     */
    public function getHardwareLogSummary(): array {
        $path = self::getHardwareLogsPath();
        $logs = file_exists($path) ? (@json_decode((string)@file_get_contents($path), true) ?: []) : [];

        if (empty($logs)) {
            $entry = $this->recordHardwareLog(true, 0);
            $logs = [$entry];
        }

        $temps = [];
        $loads = [];
        $rams = [];
        $peakTemp = null;
        $peakTempTime = '--:--';
        $peakLoad = 0.0;
        $peakLoadTime = '--:--';
        $peakRam = 0.0;
        $peakRamTime = '--:--';
        $anomalies = 0;

        foreach ($logs as $l) {
            // Température
            if (isset($l['cpu_temp']) && $l['cpu_temp'] !== null) {
                $t = (float)$l['cpu_temp'];
                $temps[] = $t;
                if ($peakTemp === null || $t > $peakTemp) {
                    $peakTemp = $t;
                    $peakTempTime = $l['time_label'] ?? date('H:i', $l['timestamp']);
                }
                if ($t >= 75.0) $anomalies++;
            }

            // Charge
            if (isset($l['cpu_load_1m'])) {
                $ld = (float)$l['cpu_load_1m'];
                $loads[] = $ld;
                if ($ld > $peakLoad) {
                    $peakLoad = $ld;
                    $peakLoadTime = $l['time_label'] ?? date('H:i', $l['timestamp']);
                }
                if (isset($l['cpu_load_percent']) && $l['cpu_load_percent'] >= 90) $anomalies++;
            }

            // RAM
            if (isset($l['ram_percent'])) {
                $rp = (float)$l['ram_percent'];
                $rams[] = $rp;
                if ($rp > $peakRam) {
                    $peakRam = $rp;
                    $peakRamTime = $l['time_label'] ?? date('H:i', $l['timestamp']);
                }
                if ($rp >= 90.0) $anomalies++;
            }
        }

        $last = end($logs);

        return [
            'count'             => count($logs),
            'peak_temp'         => $peakTemp,
            'peak_temp_time'    => $peakTempTime,
            'avg_temp'          => !empty($temps) ? round(array_sum($temps) / count($temps), 1) : null,
            'min_temp'          => !empty($temps) ? min($temps) : null,
            'peak_load'         => round($peakLoad, 2),
            'peak_load_time'    => $peakLoadTime,
            'avg_load'          => !empty($loads) ? round(array_sum($loads) / count($loads), 2) : 0.0,
            'peak_ram'          => round($peakRam, 1),
            'peak_ram_time'     => $peakRamTime,
            'avg_ram'           => !empty($rams) ? round(array_sum($rams) / count($rams), 1) : 0.0,
            'anomalies_count'   => $anomalies,
            'total_rx_mb'       => (float)($last['net_rx_mb'] ?? 0),
            'total_tx_mb'       => (float)($last['net_tx_mb'] ?? 0),
            'last_recorded_at'  => $last['datetime'] ?? date('Y-m-d H:i:s')
        ];
    }

    /**
     * Génère un fichier CSV de l'historique complet pour téléchargement
     */
    public function exportLogsCsv(): string {
        $path = self::getHardwareLogsPath();
        $logs = file_exists($path) ? (@json_decode((string)@file_get_contents($path), true) ?: []) : [];

        $fp = fopen('php://temp', 'r+');
        fputs($fp, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($fp, [
            'ID',
            'Horodatage (Unix)',
            'Date & Heure',
            'Température CPU (°C)',
            'Statut Thermique',
            'Charge 1m',
            'Charge 5m',
            'Charge CPU (%)',
            'Fréquence CPU (MHz)',
            'RAM Utilisée (Mo)',
            'RAM Totale (Mo)',
            'RAM Utilisée (%)',
            'Swap Utilisé (Mo)',
            'Swap Utilisé (%)',
            'Espace Disque Utilisé (%)',
            'Trafic Réseau Reçu (Mo)',
            'Trafic Réseau Transmis (Mo)',
            'Débit Réception (Ko/s)',
            'Débit Émission (Ko/s)',
            'Score Santé Hôte (%)'
        ], ';');

        foreach ($logs as $l) {
            fputcsv($fp, [
                $l['id'] ?? '',
                $l['timestamp'] ?? '',
                $l['datetime'] ?? '',
                $l['cpu_temp'] ?? 'N/A',
                $l['thermal_status'] ?? '',
                $l['cpu_load_1m'] ?? '',
                $l['cpu_load_5m'] ?? '',
                $l['cpu_load_percent'] ?? '',
                $l['cpu_freq_mhz'] ?? '',
                $l['ram_used_mb'] ?? '',
                $l['ram_total_mb'] ?? '',
                $l['ram_percent'] ?? '',
                $l['swap_used_mb'] ?? '',
                $l['swap_percent'] ?? '',
                $l['disk_percent'] ?? '',
                $l['net_rx_mb'] ?? '',
                $l['net_tx_mb'] ?? '',
                $l['net_rx_speed_kbps'] ?? '',
                $l['net_tx_speed_kbps'] ?? '',
                $l['health_score'] ?? ''
            ], ';');
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return (string)$csv;
    }

    /**
     * Purge des logs de télémétrie matérielle
     */
    public function purgeLogs(): bool {
        $path = self::getHardwareLogsPath();
        $current = $this->recordHardwareLog(true, 0);
        return (bool)file_put_contents($path, json_encode([$current], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
