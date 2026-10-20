<?php
/**
 * Moteur de Supervision Système & Matériel Hôte (Spécifique Raspberry Pi & Linux)
 * Fichier : core/ServerMonitorEngine.php
 * Module : Game Studio - Supervision Serveur
 */

require_once __DIR__ . '/Database.php';

class ServerMonitorEngine {
    private ?PDO $db;

    public function __construct(?PDO $pdo = null) {
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
                    } else {
                        $this->db = Database::getInstance();
                    }
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
        return [
            'timestamp'    => time(),
            'datetime'     => date('Y-m-d H:i:s'),
            'hardware'     => $this->getHardwareInfo(),
            'cpu'          => $this->getCpuAndThermalInfo(),
            'memory'       => $this->getMemoryAndSwapInfo(),
            'disk'         => $this->getDiskInfo(),
            'database'     => $this->getDatabaseMetrics(),
            'php'          => $this->getPhpAndOpcacheInfo(),
            'network'      => $this->getNetworkInfo(),
            'uptime'       => $this->getUptimeInfo(),
            'health_score' => $this->calculateHealthScore()
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

        return [
            'temperature'   => $tempCelsius,
            'thermal_status'=> $thermalStatus,
            'thermal_label' => $thermalLabel,
            'thermal_class' => $thermalClass,
            'load_1m'       => $loadAvg[0],
            'load_5m'       => $loadAvg[1],
            'load_15m'      => $loadAvg[2],
            'load_percent'  => $loadPercent,
            'cores'         => $cores
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
     * Informations réseau basiques
     */
    public function getNetworkInfo(): array {
        $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        return [
            'server_ip' => $serverIp,
            'port'      => (int)($_SERVER['SERVER_PORT'] ?? 80),
            'scheme'    => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'
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
}
