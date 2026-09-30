<?php
declare(strict_types=1);

/**
 * FeatureRegistry — Gestionnaire et parseur du registre des fonctionnalités & recette QA
 * Parse et met à jour le fichier fonctionnalités.md à la racine du projet
 */
class FeatureRegistry {

    private static ?string $filePath = null;

    /**
     * Détermine le chemin vers le fichier fonctionnalités.md ou fonctionnalites.md
     */
    public static function getFilePath(): string {
        if (self::$filePath !== null) {
            return self::$filePath;
        }

        $root = dirname(__DIR__);
        $withAccent = $root . '/fonctionnalités.md';
        $withoutAccent = $root . '/fonctionnalites.md';

        if (file_exists($withAccent)) {
            self::$filePath = $withAccent;
        } elseif (file_exists($withoutAccent)) {
            self::$filePath = $withoutAccent;
        } else {
            self::$filePath = $withAccent;
        }

        return self::$filePath;
    }

    /**
     * Récupère la liste structurée de toutes les fonctionnalités consignées
     * @return array<array<string, mixed>>
     */
    public static function getAllFeatures(): array {
        $path = self::getFilePath();
        if (!file_exists($path)) {
            return [];
        }

        $content = (string)file_get_contents($path);
        return self::parseMarkdown($content);
    }

    /**
     * Parse le contenu brut Markdown en tableau d'entités fonctionnalités
     * @param string $content
     * @return array<array<string, mixed>>
     */
    public static function parseMarkdown(string $content): array {
        $features = [];
        $blocks = preg_split('/(?:\r?\n)(?=###\s+\[)/', $content);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (!str_starts_with($block, '###')) {
                continue;
            }

            // Ex: ### [2026-09-30] - dev_team : Refonte du Roster & Attribution Multiple
            if (preg_match('/^###\s+\[([0-9]{4}-[0-9]{2}-[0-9]{2})\]\s*-\s*([a-zA-Z0-9_\-]+)\s*:\s*(.+)$/m', $block, $headerMatches)) {
                $date    = trim($headerMatches[1]);
                $module  = trim($headerMatches[2]);
                $title   = trim($headerMatches[3]);

                // Extraction des champs
                $status      = 'À tester';
                $description = '';
                $files       = '';
                $qaCheck     = '';
                $validatedBy = '';

                if (preg_match('/-\s*\*\*Statut\s*:\*\*\s*`?([^`\r\n]+)`?/ui', $block, $m)) {
                    $status = trim($m[1]);
                }
                if (preg_match('/-\s*\*\*Description\s*:\*\*\s*(.+)$/m', $block, $m)) {
                    $description = trim($m[1]);
                }
                if (preg_match('/-\s*\*\*Fichiers modifiés\s*:\*\*\s*(.+)$/m', $block, $m)) {
                    $files = trim($m[1]);
                }
                if (preg_match('/-\s*\*\*Vérification QA\s*:\*\*\s*(.+)$/m', $block, $m)) {
                    $qaCheck = trim($m[1]);
                }
                if (preg_match('/-\s*\*\*Validé par\s*:\*\*\s*(.+)$/m', $block, $m)) {
                    $validatedBy = trim($m[1]);
                }

                $id = md5($date . '-' . $module . '-' . $title);

                $features[] = [
                    'id'           => $id,
                    'date'         => $date,
                    'module'       => $module,
                    'title'        => $title,
                    'status'       => $status,
                    'description'  => $description,
                    'files'        => $files,
                    'qa_check'     => $qaCheck,
                    'validated_by' => $validatedBy,
                    'raw'          => $block
                ];
            }
        }

        return $features;
    }

    /**
     * Statistiques globales du registre QA
     */
    public static function getStatistics(): array {
        $features = self::getAllFeatures();
        $total = count($features);
        $pending = 0;
        $validated = 0;
        $rejected = 0;

        foreach ($features as $f) {
            $s = mb_strtolower($f['status']);
            if (str_contains($s, 'valid')) {
                $validated++;
            } elseif (str_contains($s, 'rejet') || str_contains($s, 'refus')) {
                $rejected++;
            } else {
                $pending++;
            }
        }

        return [
            'total'     => $total,
            'pending'   => $pending,
            'validated' => $validated,
            'rejected'  => $rejected
        ];
    }

    /**
     * Met à jour le statut d'une fonctionnalité dans le fichier Markdown
     */
    public static function updateStatus(string $featureId, string $newStatus, string $testerName = ''): bool {
        $validStatuses = ['À tester', 'Validée', 'Rejetée'];
        if (!in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("Statut invalide : {$newStatus}");
        }

        $path = self::getFilePath();
        if (!file_exists($path)) {
            return false;
        }

        $content = (string)file_get_contents($path);
        $features = self::parseMarkdown($content);

        $targetFeature = null;
        foreach ($features as $f) {
            if ($f['id'] === $featureId) {
                $targetFeature = $f;
                break;
            }
        }

        if (!$targetFeature) {
            return false;
        }

        $oldBlock = $targetFeature['raw'];

        // Remplacer ou insérer le nouveau statut
        $newBlock = preg_replace(
            '/-\s*\*\*Statut\s*:\*\*\s*`?[^`\r\n]+`?/ui',
            "- **Statut :** `{$newStatus}`",
            $oldBlock
        );

        // Mettre à jour ou ajouter la mention du validateur
        if (!empty($testerName)) {
            $validationTag = "- **Validé par :** {$testerName} (le " . date('d/m/Y H:i') . ")";
            if (preg_match('/-\s*\*\*Validé par\s*:\*\*.+$/m', $newBlock)) {
                $newBlock = preg_replace('/-\s*\*\*Validé par\s*:\*\*.+$/m', $validationTag, $newBlock);
            } else {
                $newBlock .= "\n" . $validationTag;
            }
        }

        $updatedContent = str_replace($oldBlock, $newBlock, $content);
        $written = file_put_contents($path, $updatedContent, LOCK_EX);

        // Si symlink ou version non accentuée existe, synchroniser
        $root = dirname(__DIR__);
        $altPath = str_ends_with($path, 'fonctionnalités.md') ? ($root . '/fonctionnalites.md') : ($root . '/fonctionnalités.md');
        if (file_exists($altPath) && !is_link($altPath)) {
            @file_put_contents($altPath, $updatedContent, LOCK_EX);
        }

        return ($written !== false);
    }
}
