<?php
declare(strict_types=1);

/**
 * QASyntaxChecker — Automatisation des contrôles de syntaxe PHP & Linting interne
 * Exécute php -l sur l'ensemble des scripts de l'application et retourne l'état technique
 */
class QASyntaxChecker {

    private const STATUS_FILE = __DIR__ . '/../config/qa_syntax_status.json';

    /**
     * Lance le contrôle syntaxique complet et enregistre le résultat
     * @return array<string, mixed>
     */
    public static function runSyntaxCheck(): array {
        $startTime = microtime(true);
        $root = dirname(__DIR__);

        $dirs = ['core', 'views', 'api', 'config', 'database', 'scripts'];
        $files = glob($root . '/*.php') ?: [];

        foreach ($dirs as $d) {
            $dirPath = $root . '/' . $d;
            if (!is_dir($dirPath)) continue;

            $found1 = glob($dirPath . '/*.php') ?: [];
            $found2 = glob($dirPath . '/*/*.php') ?: [];
            $files = array_merge($files, $found1, $found2);
        }

        $files = array_unique($files);
        sort($files);

        $passed = 0;
        $errors = [];

        foreach ($files as $file) {
            if (!is_file($file)) continue;

            $output = [];
            $exitCode = 0;
            exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

            $relativePath = str_replace($root . '/', '', $file);

            if ($exitCode === 0) {
                $passed++;
            } else {
                $errors[] = [
                    'file'    => $relativePath,
                    'message' => implode("\n", $output)
                ];
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $totalFiles = count($files);
        $isClean = (count($errors) === 0);

        $result = [
            'success'       => true,
            'is_clean'      => $isClean,
            'total_files'   => $totalFiles,
            'passed_count'  => $passed,
            'error_count'   => count($errors),
            'errors'        => $errors,
            'duration_ms'   => $durationMs,
            'checked_at'    => date('d/m/Y H:i:s'),
            'timestamp'     => time()
        ];

        // Mettre en cache le dernier résultat
        @file_put_contents(self::STATUS_FILE, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result;
    }

    /**
     * Récupère le dernier contrôle syntaxique enregistré en cache (ou en lance un nouveau si absent)
     * @return array<string, mixed>
     */
    public static function getLastCheckResult(): array {
        if (file_exists(self::STATUS_FILE)) {
            $content = (string)file_get_contents(self::STATUS_FILE);
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['is_clean'])) {
                return $data;
            }
        }

        // Si aucun cache, lancer un premier scan
        return self::runSyntaxCheck();
    }
}
