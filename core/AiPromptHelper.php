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
                }
            } else {
                self::$catalog = [];
                self::$byFile = [];
            }
        }
        return self::$catalog;
    }

    /**
     * Récupère les données d'un prompt par chemin ou nom de fichier
     */
    public static function getByFile(string $file): ?array {
        self::load();
        $clean = ltrim($file, '/');
        $clean = preg_replace('#^public/assets/#', '', $clean);
        return self::$byFile[$clean] ?? self::$byFile[basename($clean)] ?? self::$byFile[pathinfo($clean, PATHINFO_FILENAME)] ?? null;
    }

    /**
     * Génère le HTML du badge circulaire « ? » superposé sur l'image
     */
    public static function renderBadge(string $file, ?string $customTitle = null, ?string $customImgUrl = null, string $extraClass = ''): string {
        $data = self::getByFile($file);
        if (!$data) {
            return '';
        }

        $title = $customTitle ?: $data['title'];
        $img = $customImgUrl ?: ('/public/assets/' . $data['file']);
        $prompt = htmlspecialchars($data['prompt'], ENT_QUOTES, 'UTF-8');
        $translation = htmlspecialchars($data['translation'], ENT_QUOTES, 'UTF-8');
        $titleAttr = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $imgAttr = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
        $classes = 'ai-prompt-badge' . ($extraClass !== '' ? ' ' . trim($extraClass) : '');

        return '<button type="button" class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" aria-label="Voir le prompt IA et les détails de génération" title="Détails du prompt IA" '
             . 'data-ai-title="' . $titleAttr . '" '
             . 'data-ai-img="' . $imgAttr . '" '
             . 'data-ai-prompt="' . $prompt . '" '
             . 'data-ai-translation="' . $translation . '">'
             . '<span class="ai-badge-icon">?</span>'
             . '</button>';
    }
}
