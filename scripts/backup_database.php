#!/usr/bin/env php
<?php
/**
 * Script CLI de Sauvegarde et Restauration Séparées (Structure & Données)
 * OpenShogun - Usage Console / Cron
 *
 * Exemples d'utilisation :
 *   php scripts/backup_database.php backup
 *   php scripts/backup_database.php backup --usb=/media/usb_drive
 *   php scripts/backup_database.php list
 *   php scripts/backup_database.php restore --structure=database/backups/structure_xxx.sql --data=database/backups/data_xxx.sql
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/DatabaseBackupService.php';

$options = getopt('', ['help', 'usb::', 'structure::', 'data::', 'file::']);
$action = $argv[1] ?? 'backup';

if (isset($options['help']) || $action === 'help' || $action === '--help') {
    echo <<<HELP
==============================================================
  ⛩️  OpenShogun — Sauvegarde & Réintégration Base de Données
==============================================================
Commandes disponibles :
  php scripts/backup_database.php backup [--usb=/chemin/cle]
      Exporte la structure et les données dans 2 fichiers distincts.
      Sauvegarde dans database/backups/ et réplique sur clé USB si branchée.

  php scripts/backup_database.php list
      Affiche la liste des sauvegardes disponibles et supports USB détectés.

  php scripts/backup_database.php restore --structure=<fichier> --data=<fichier>
      Réintègre la structure puis les données (avec contrôle des clés).

  php scripts/backup_database.php restore --file=<fichier_unique.sql>
      Réintègre un fichier SQL spécifique.

HELP;
    exit(0);
}

$service = new DatabaseBackupService();

switch ($action) {
    case 'list':
        echo "=== Sauvegardes Disponibles (database/backups/) ===\n";
        $list = $service->listLocalBackups();
        echo "\n[Structures SQL] (" . count($list['structure']) . ") :\n";
        foreach ($list['structure'] as $s) {
            echo "  - {$s['filename']} (" . round($s['size'] / 1024, 1) . " Ko) - {$s['date']}\n";
        }
        echo "\n[Données SQL] (" . count($list['data']) . ") :\n";
        foreach ($list['data'] as $d) {
            echo "  - {$d['filename']} (" . round($d['size'] / 1024, 1) . " Ko) - {$d['date']}\n";
        }

        echo "\n=== Détection Supports USB / Externes ===\n";
        $usb = $service->detectUsbDrives();
        if (empty($usb)) {
            echo "  Aucun support USB externe détecté dans /media ou /mnt.\n";
        } else {
            foreach ($usb as $u) {
                echo "  [OK] Support monté : {$u}\n";
            }
        }
        break;

    case 'restore':
        $file = $options['file'] ?? null;
        $struct = $options['structure'] ?? null;
        $data = $options['data'] ?? null;

        if ($struct && $data) {
            echo "Réintégration complète : Structure [{$struct}] puis Données [{$data}]...\n";
            $res = $service->restoreComplete($struct, $data);
            if ($res['success']) {
                echo "✅ {$res['message']}\n";
            } else {
                echo "❌ Échec : {$res['error']}\n";
                exit(1);
            }
        } elseif ($file) {
            echo "Réintégration du fichier SQL [{$file}]...\n";
            $res = $service->restoreSqlFile($file);
            if ($res['success']) {
                echo "✅ {$res['message']}\n";
            } else {
                echo "❌ Échec : {$res['error']}\n";
                exit(1);
            }
        } else {
            echo "❌ Erreur : veuillez spécifier --structure=<fichier> et --data=<fichier>, ou --file=<fichier>.\n";
            exit(1);
        }
        break;

    case 'backup':
    default:
        $customUsb = $options['usb'] ?? null;
        echo "Création de la sauvegarde séparée (Structure & Données)...\n";
        $res = $service->createBackup($customUsb);
        if ($res['success']) {
            echo "✅ Sauvegarde réussie !\n";
            echo "  - Structure : " . basename($res['local']['structure']) . " (" . round($res['local']['structure_size'] / 1024, 1) . " Ko)\n";
            echo "  - Données   : " . basename($res['local']['data']) . " (" . round($res['local']['data_size'] / 1024, 1) . " Ko)\n";
            echo "  - Dossier   : " . $res['local']['dir'] . "\n";

            if (!empty($res['usb_copies'])) {
                echo "  - Copie(s) USB effectuée(s) sur :\n";
                foreach ($res['usb_copies'] as $uc) {
                    echo "      -> {$uc['dir']}\n";
                }
            } else {
                echo "  ℹ️ Aucun support USB détecté, sauvegarde conservée en local.\n";
            }
        } else {
            echo "❌ Erreur lors de la sauvegarde : " . $res['error'] . "\n";
            exit(1);
        }
        break;
}

