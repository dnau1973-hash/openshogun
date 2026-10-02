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
     *
     * @param string $file Nom ou chemin du fichier d'image
     * @param string|null $customTitle Titre optionnel personnalisé
     * @param string|null $customImgUrl URL personnalisée pour l'aperçu HD
     * @param string $extraClass Classes CSS supplémentaires
     * @param bool $asPill Affiche une pilule avec texte "Généré par IA" au lieu d'une simple icône
     */
    public static function renderBadge(string $file, ?string $customTitle = null, ?string $customImgUrl = null, string $extraClass = '', bool $asPill = false): string {
        $data = self::getByFile($file);
        if (!$data) {
            return '';
        }

        $title = $customTitle ?: ($data['title'] ?? 'Détails de Génération IA');
        $img = $customImgUrl ?: ('/public/assets/' . $data['file']);
        $prompt = htmlspecialchars($data['prompt'] ?? '', ENT_QUOTES, 'UTF-8');
        $translation = htmlspecialchars($data['translation'] ?? '', ENT_QUOTES, 'UTF-8');
        $titleAttr = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $imgAttr = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
        $model = htmlspecialchars($data['model'] ?? 'Google Gemini Imagen 3', ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars($data['date'] ?? 'Octobre 2026', ENT_QUOTES, 'UTF-8');
        $resolution = htmlspecialchars($data['resolution'] ?? ($data['format'] ?? '1920×1080'), ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($data['category_label'] ?? ($data['category'] ?? 'Génération Féodale'), ENT_QUOTES, 'UTF-8');

        $isPill = $asPill || str_contains($extraClass, 'ai-prompt-badge-pill');
        $classes = 'ai-prompt-badge' . ($isPill ? ' ai-prompt-badge-pill' : '') . ($extraClass !== '' ? ' ' . trim($extraClass) : '');
        $tooltip = 'Généré par IA &bull; ' . ($data['model'] ?? 'Google Gemini Imagen 3') . ' &bull; Cliquer pour voir le prompt source';

        $innerHtml = $isPill
            ? '<i class="fa-solid fa-wand-magic-sparkles me-1 text-warning"></i><span class="ai-badge-label">Généré par IA</span>'
            : '<i class="fa-solid fa-wand-magic-sparkles ai-badge-icon"></i>';

        return '<button type="button" class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" '
             . 'aria-label="Voir le prompt IA et les détails de génération" '
             . 'data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom" title="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '" '
             . 'data-ai-title="' . $titleAttr . '" '
             . 'data-ai-img="' . $imgAttr . '" '
             . 'data-ai-prompt="' . $prompt . '" '
             . 'data-ai-translation="' . $translation . '" '
             . 'data-ai-model="' . $model . '" '
             . 'data-ai-date="' . $date . '" '
             . 'data-ai-resolution="' . $resolution . '" '
             . 'data-ai-category="' . $category . '">'
             . $innerHtml
             . '</button>';
    }
}
