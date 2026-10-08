<?php
/**
 * Service Autonome de Sauvegarde et Restauration Séparées (Structure & Données)
 * OpenShogun - Base de Données
 *
 * Sauvegarde :
 * 1. Structure seule (DDL : tables, clés, triggers, index)
 * 2. Données seules (DML : INSERT avec désactivation des clés étrangères)
 *
 * Destinations :
 * - Dossier interne de l'application : database/backups/
 * - Clé USB / Disque externe monté (détection auto /media, /mnt, ou chemin manuel)
 */
require_once __DIR__ . '/Database.php';

class DatabaseBackupService {
    private string $host;
    private string $port;
    private string $dbName;
    private string $user;
    private string $password;
    private string $appBackupDir;

    public function __construct(string $appBackupDir = '') {
        $this->host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $this->port = defined('DB_PORT') ? (string)DB_PORT : '3306';
        $this->dbName = defined('DB_NAME') ? DB_NAME : 'openshogun';
        $this->user = defined('DB_USER') ? DB_USER : 'root';
        $this->password = defined('DB_PASS') ? DB_PASS : '';
        $this->appBackupDir = !empty($appBackupDir) ? $appBackupDir : realpath(__DIR__ . '/../database') . '/backups';

        if (!is_dir($this->appBackupDir)) {
            @mkdir($this->appBackupDir, 0775, true);
        }
    }

    /**
     * Détecte les points de montage USB / disques externes disponibles
     * @return array Liste des chemins de supports externes accessibles en écriture
     */
    public function detectUsbDrives(): array {
        $candidates = [];

        // 1. Parcours de /media
        if (is_dir('/media')) {
            $userDirs = @scandir('/media') ?: [];
            foreach ($userDirs as $u) {
                if ($u === '.' || $u === '..') continue;
                $userPath = '/media/' . $u;
                if (is_dir($userPath)) {
                    $mounts = @scandir($userPath) ?: [];
                    foreach ($mounts as $m) {
                        if ($m === '.' || $m === '..') continue;
                        $full = $userPath . '/' . $m;
                        if (is_dir($full) && is_writable($full)) {
                            $candidates[] = $full;
                        }
                    }
                }
            }
        }

        // 2. Parcours de /mnt
        if (is_dir('/mnt')) {
            $mntEntries = @scandir('/mnt') ?: [];
            foreach ($mntEntries as $m) {
                if ($m === '.' || $m === '..') continue;
                $full = '/mnt/' . $m;
                if (is_dir($full) && is_writable($full)) {
                    $candidates[] = $full;
                }
            }
        }

        // 3. Inspection des points de montage système dans /proc/mounts
        if (file_exists('/proc/mounts')) {
            $lines = @file('/proc/mounts', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 2) {
                    $dev = $parts[0];
                    $mountPoint = $parts[1];
                    if (str_starts_with($dev, '/dev/sd') || str_starts_with($dev, '/dev/nvme') || str_starts_with($dev, '/dev/mmcblk')) {
                        if (str_starts_with($mountPoint, '/media') || str_starts_with($mountPoint, '/mnt')) {
                            if (is_dir($mountPoint) && is_writable($mountPoint) && !in_array($mountPoint, $candidates)) {
                                $candidates[] = $mountPoint;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Génère une sauvegarde complète (structure et données séparées)
     *
     * @param string|null $customUsbPath Chemin USB optionnel (si null, détecté automatiquement)
     * @return array Résultat avec les chemins des fichiers créés
     */
    public function createBackup(?string $customUsbPath = null): array {
        $timestamp = date('Ymd_His');
        $structureFileName = "structure_{$this->dbName}_{$timestamp}.sql";
        $dataFileName = "data_{$this->dbName}_{$timestamp}.sql";

        $localStructurePath = $this->appBackupDir . '/' . $structureFileName;
        $localDataPath = $this->appBackupDir . '/' . $dataFileName;

        // Commande pour la STRUCTURE SEULE (--no-data, --routines, --events)
        $dumpCmdStructure = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s --no-data --routines --triggers %s > %s 2>&1',
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->user),
            !empty($this->password) ? '--password=' . escapeshellarg($this->password) : '',
            escapeshellarg($this->dbName),
            escapeshellarg($localStructurePath)
        );

        // Commande pour les DONNÉES SEULES (--no-create-info, --complete-insert, désactivation FK)
        $dumpCmdData = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s --no-create-info --skip-triggers --single-transaction --quick %s > %s 2>&1',
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->user),
            !empty($this->password) ? '--password=' . escapeshellarg($this->password) : '',
            escapeshellarg($this->dbName),
            escapeshellarg($localDataPath)
        );

        $outStruct = [];
        $resStruct = 0;
        exec($dumpCmdStructure, $outStruct, $resStruct);
        if ($resStruct !== 0 || !file_exists($localStructurePath) || filesize($localStructurePath) === 0) {
            return [
                'success' => false,
                'error' => "Échec de l'export de la structure : " . implode("\n", $outStruct)
            ];
        }

        $outData = [];
        $resData = 0;
        exec($dumpCmdData, $outData, $resData);
        if ($resData !== 0 || !file_exists($localDataPath)) {
            return [
                'success' => false,
                'error' => "Échec de l'export des données : " . implode("\n", $outData)
            ];
        }

        // Préfixer le fichier de données avec SET FOREIGN_KEY_CHECKS=0
        $dataContent = file_get_contents($localDataPath);
        $header = "-- OpenShogun Data Export - " . date('Y-m-d H:i:s') . "\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET AUTOCOMMIT=0;\nSTART TRANSACTION;\n\n";
        $footer = "\n\nCOMMIT;\nSET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n";
        file_put_contents($localDataPath, $header . $dataContent . $footer);

        // Réplication sur support USB
        $usbDrives = !empty($customUsbPath) ? [$customUsbPath] : $this->detectUsbDrives();
        $usbCopies = [];

        foreach ($usbDrives as $usb) {
            $usbDestDir = rtrim($usb, '/') . '/openshogun_backups';
            if (!is_dir($usbDestDir)) {
                @mkdir($usbDestDir, 0775, true);
            }
            if (is_dir($usbDestDir) && is_writable($usbDestDir)) {
                $usbStruct = $usbDestDir . '/' . $structureFileName;
                $usbData = $usbDestDir . '/' . $dataFileName;
                @copy($localStructurePath, $usbStruct);
                @copy($localDataPath, $usbData);
                $usbCopies[] = [
                    'mount'     => $usb,
                    'dir'       => $usbDestDir,
                    'structure' => $usbStruct,
                    'data'      => $usbData
                ];
            }
        }

        return [
            'success' => true,
            'timestamp' => $timestamp,
            'local' => [
                'dir'       => $this->appBackupDir,
                'structure' => $localStructurePath,
                'data'      => $localDataPath,
                'structure_size' => filesize($localStructurePath),
                'data_size' => filesize($localDataPath)
            ],
            'usb_copies' => $usbCopies,
            'usb_found' => !empty($usbCopies)
        ];
    }

    /**
     * Réintègre (restaure) une sauvegarde
     *
     * @param string $sqlFilePath Chemin du fichier .sql à importer
     * @return array Résultat de la réintégration
     */
    public function restoreSqlFile(string $sqlFilePath): array {
        if (!file_exists($sqlFilePath) || !is_readable($sqlFilePath)) {
            return ['success' => false, 'error' => "Fichier SQL introuvable ou illisible : {$sqlFilePath}"];
        }

        $cmd = sprintf(
            'mysql --host=%s --port=%s --user=%s %s %s < %s 2>&1',
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->user),
            !empty($this->password) ? '--password=' . escapeshellarg($this->password) : '',
            escapeshellarg($this->dbName),
            escapeshellarg($sqlFilePath)
        );

        $output = [];
        $resCode = 0;
        exec($cmd, $output, $resCode);

        if ($resCode !== 0) {
            return [
                'success' => false,
                'error' => "Erreur lors de la réintégration de {$sqlFilePath} : " . implode("\n", $output)
            ];
        }

        return [
            'success' => true,
            'file' => $sqlFilePath,
            'message' => "Réintégration réussie du fichier " . basename($sqlFilePath)
        ];
    }

    /**
     * Réintègre une paire complète Structure + Données
     *
     * @param string $structureFile Fichier SQL de structure
     * @param string $dataFile Fichier SQL de données
     * @return array
     */
    public function restoreComplete(string $structureFile, string $dataFile): array {
        // 1. Réintégration de la structure
        $resStruct = $this->restoreSqlFile($structureFile);
        if (!$resStruct['success']) {
            return $resStruct;
        }

        // 2. Réintégration des données
        $resData = $this->restoreSqlFile($dataFile);
        if (!$resData['success']) {
            return $resData;
        }

        return [
            'success' => true,
            'message' => "Restauration complète effectuée avec succès (structure puis données).",
            'structure_file' => basename($structureFile),
            'data_file' => basename($dataFile)
        ];
    }

    /**
     * Liste toutes les sauvegardes locales disponibles
     * @return array
     */
    public function listLocalBackups(): array {
        if (!is_dir($this->appBackupDir)) return [];
        $files = scandir($this->appBackupDir);
        $backups = [
            'structure' => [],
            'data'      => []
        ];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || !str_ends_with($f, '.sql')) continue;
            $path = $this->appBackupDir . '/' . $f;
            $info = [
                'filename' => $f,
                'path'     => $path,
                'size'     => filesize($path),
                'mtime'    => filemtime($path),
                'date'     => date('Y-m-d H:i:s', filemtime($path))
            ];
            if (str_starts_with($f, 'structure_')) {
                $backups['structure'][] = $info;
            } elseif (str_starts_with($f, 'data_')) {
                $backups['data'][] = $info;
            }
        }

        usort($backups['structure'], fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        usort($backups['data'], fn($a, $b) => $b['mtime'] <=> $a['mtime']);

        return $backups;
    }
}
