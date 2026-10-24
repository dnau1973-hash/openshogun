<?php
/**
 * Helper de métadonnées et badge de transparence pour images IA (OpenShogun)
 */
class AiPromptHelper {
    private static ?array $catalog = null;
    private static ?array $byFile = null;

    /**
     * Charge et indexe le catalogue des prompts une seule fois en mémoire
     */
     public static function load(): array {
        if (self::$catalog === null) {
            $dataFile = __DIR__ . '/../views/partials/grimoire_prompts_data.php';
            if (file_exists($dataFile)) {
                self::$catalog = require $dataFile;
                self::$byFile = [];
                foreach (self::$catalog as $item) {
                    $f = $item['file'] ?? '';
                    self::$byFile[$f] = $item;
                    $basename = basename($f);
                    self::$byFile[$basename] = $item;
                    // Également indexer sans extension ou chemins relatifs
                    $noExt = pathinfo($f, PATHINFO_FILENAME);
                    self::$byFile[$noExt] = $item;
                    if (!empty($item['id'])) {
                        self::$byFile[$item['id']] = $item;
                    }
                }
            } else {
                self::$catalog = [];
                self::$byFile = [];
            }
        }
        return self::$catalog;
    }

    /**
     * Récupère les données d'un prompt par chemin, nom de fichier ou identifiant
     */
    public static function getByFile(string $file): ?array {
        self::load();
        $clean = ltrim($file, '/');
        $clean = preg_replace('#^public/assets/#', '', $clean);
        return self::$byFile[$clean] ?? self::$byFile[basename($clean)] ?? self::$byFile[pathinfo($clean, PATHINFO_FILENAME)] ?? null;
    }

    /**
     * Génère le HTML du badge de transparence IA superposé sur l'image
     * (Fonctionnalité désactivée sur l'ensemble du site)
     */
    public static function renderBadge(string $file, ?string $customTitle = null, ?string $customImgUrl = null, string $extraClass = '', bool $asPill = false): string {
        return '';
    }
}
