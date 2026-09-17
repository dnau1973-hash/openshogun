<?php
/**
 * Moteur de Mises à Jour Automatiques & Synchronisation GitHub
 * OpenShogun / OpenGalaxy
 */

require_once __DIR__ . '/../config/github.php';
require_once __DIR__ . '/GameConfig.php';

class UpdateEngine {
    private string $token;
    private string $owner;
    private string $repo;
    private string $branch;
    private string $basePath;

    public function __construct(
        ?string $token = null,
        ?string $owner = null,
        ?string $repo = null,
        ?string $branch = null
    ) {
        $this->basePath = realpath(__DIR__ . '/..') ?: '/var/www/opengalaxy';
        $this->token = $token ?: (defined('GITHUB_TOKEN') ? GITHUB_TOKEN : '');
        $this->owner = $owner ?: (defined('GITHUB_REPO_OWNER') ? GITHUB_REPO_OWNER : 'dnau1973-hash');
        $this->repo = $repo ?: (defined('GITHUB_REPO_NAME') ? GITHUB_REPO_NAME : 'openshogun');
        $this->branch = $branch ?: (defined('GITHUB_DEFAULT_BRANCH') ? GITHUB_DEFAULT_BRANCH : 'main');
    }

    /**
     * Masque un token GitHub dans une chaîne pour la sécurité
     */
    public function maskToken(string $text): string {
        if (!empty($this->token)) {
            $text = str_replace($this->token, 'ghp_••••••••' . substr($this->token, -4), $text);
        }
        return preg_replace('/ghp_[a-zA-Z0-9]{15,}/', 'ghp_••••••••••••', $text);
    }

    /**
     * Récupère les métadonnées Git locales du projet
     */
    public function getLocalInfo(): array {
        $exec = function(string $cmd): string {
            $output = [];
            $ret = 0;
            exec("cd {$this->basePath} && {$cmd} 2>&1", $output, $ret);
            return trim(implode("\n", $output));
        };

        $branch = $exec('git rev-parse --abbrev-ref HEAD') ?: 'main';
        $commitSha = $exec('git rev-parse HEAD') ?: 'Inconnu';
        $shortSha = $exec('git rev-parse --short HEAD') ?: substr($commitSha, 0, 7);
        $commitMessage = $exec('git log -1 --pretty=%B') ?: 'Aucun message';
        $authorName = $exec('git log -1 --pretty=%an') ?: 'Auteur inconnu';
        $authorDate = $exec('git log -1 --pretty=%ad --date=iso') ?: date('Y-m-d H:i:s');
        $humanDate = $exec('git log -1 --pretty=%cr') ?: 'Récemment';

        $statusOutput = $exec('git status --porcelain');
        $dirtyFiles = [];
        if (!empty($statusOutput)) {
            $lines = explode("\n", $statusOutput);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $dirtyFiles[] = $trimmed;
                }
            }
        }

        return [
            'branch' => $branch,
            'commit_sha' => $commitSha,
            'short_sha' => $shortSha,
            'commit_message' => $commitMessage,
            'author_name' => $authorName,
            'author_date' => $authorDate,
            'human_date' => $humanDate,
            'is_clean' => empty($dirtyFiles),
            'dirty_files' => $dirtyFiles,
            'repo_owner' => $this->owner,
            'repo_name' => $this->repo,
            'target_branch' => $this->branch,
            'masked_token' => !empty($this->token) ? 'ghp_••••••••' . substr($this->token, -4) : 'Non configuré'
        ];
    }

    /**
     * Interroge l'API GitHub ou git ls-remote pour vérifier s'il existe de nouveaux commits
     */
    public function checkRemoteUpdates(): array {
        $local = $this->getLocalInfo();
        $localSha = $local['commit_sha'];

        $result = [
            'success' => true,
            'has_update' => false,
            'status' => 'identical',
            'behind_by' => 0,
            'commits' => [],
            'local' => $local,
            'remote' => null,
            'error' => null,
            'checked_at' => date('Y-m-d H:i:s')
        ];

        // 1. Tenter via l'API GitHub Compare (méthode la plus riche en données)
        $compareUrl = GITHUB_API_URL . "/repos/{$this->owner}/{$this->repo}/compare/{$localSha}...{$this->branch}";
        $apiResponse = $this->callGitHubApi($compareUrl);

        if ($apiResponse['status_code'] === 200 && is_array($apiResponse['data'])) {
            $data = $apiResponse['data'];
            $status = $data['status'] ?? 'identical';
            $behindBy = (int)($data['behind_by'] ?? 0);
            $commitsList = [];

            if (!empty($data['commits']) && is_array($data['commits'])) {
                foreach ($data['commits'] as $c) {
                    $commitsList[] = [
                        'sha' => $c['sha'] ?? '',
                        'short_sha' => substr($c['sha'] ?? '', 0, 7),
                        'message' => $c['commit']['message'] ?? '',
                        'author_name' => $c['commit']['author']['name'] ?? 'Inconnu',
                        'date' => $c['commit']['author']['date'] ?? '',
                        'html_url' => $c['html_url'] ?? ''
                    ];
                }
            }

            $result['status'] = $status;
            $result['behind_by'] = $behindBy;
            $result['has_update'] = ($behindBy > 0 || $status === 'behind' || count($commitsList) > 0);
            $result['commits'] = $commitsList;

            if (!empty($commitsList)) {
                $latest = end($commitsList);
                $result['remote'] = $latest;
            } elseif (!empty($data['base_commit'])) {
                $result['remote'] = [
                    'sha' => $data['base_commit']['sha'] ?? '',
                    'short_sha' => substr($data['base_commit']['sha'] ?? '', 0, 7),
                    'message' => $data['base_commit']['commit']['message'] ?? '',
                    'author_name' => $data['base_commit']['commit']['author']['name'] ?? '',
                    'date' => $data['base_commit']['commit']['author']['date'] ?? '',
                    'html_url' => $data['base_commit']['html_url'] ?? ''
                ];
            }

            return $result;
        }

        // 2. Si compare échoue (par exemple si localSha est inconnu sur le remote), interroger le dernier commit de la branche
        $branchUrl = GITHUB_API_URL . "/repos/{$this->owner}/{$this->repo}/commits/{$this->branch}";
        $branchResponse = $this->callGitHubApi($branchUrl);

        if ($branchResponse['status_code'] === 200 && is_array($branchResponse['data'])) {
            $remoteSha = $branchResponse['data']['sha'] ?? '';
            $remoteMessage = $branchResponse['data']['commit']['message'] ?? '';
            $remoteAuthor = $branchResponse['data']['commit']['author']['name'] ?? '';
            $remoteDate = $branchResponse['data']['commit']['author']['date'] ?? '';

            $hasUpdate = ($remoteSha !== '' && $remoteSha !== $localSha);

            $result['has_update'] = $hasUpdate;
            $result['status'] = $hasUpdate ? 'behind' : 'identical';
            $result['behind_by'] = $hasUpdate ? 1 : 0;
            $result['remote'] = [
                'sha' => $remoteSha,
                'short_sha' => substr($remoteSha, 0, 7),
                'message' => $remoteMessage,
                'author_name' => $remoteAuthor,
                'date' => $remoteDate,
                'html_url' => $branchResponse['data']['html_url'] ?? ''
            ];

            if ($hasUpdate) {
                $result['commits'][] = $result['remote'];
            }

            return $result;
        }

        // 3. Fallback en ligne de commande locale : git ls-remote avec token
        $repoUrl = "https://{$this->token}@github.com/{$this->owner}/{$this->repo}.git";
        $cmd = "git ls-remote " . escapeshellarg($repoUrl) . " " . escapeshellarg("refs/heads/{$this->branch}");
        $output = [];
        $ret = 0;
        exec("cd {$this->basePath} && {$cmd} 2>&1", $output, $ret);

        if ($ret === 0 && !empty($output)) {
            $line = trim($output[0] ?? '');
            $parts = preg_split('/\s+/', $line);
            $remoteSha = $parts[0] ?? '';

            if (!empty($remoteSha)) {
                $hasUpdate = ($remoteSha !== $localSha);
                $result['has_update'] = $hasUpdate;
                $result['status'] = $hasUpdate ? 'behind' : 'identical';
                $result['behind_by'] = $hasUpdate ? 1 : 0;
                $result['remote'] = [
                    'sha' => $remoteSha,
                    'short_sha' => substr($remoteSha, 0, 7),
                    'message' => 'Dernier commit distant sur ' . $this->branch,
                    'author_name' => 'GitHub',
                    'date' => date('Y-m-d H:i:s'),
                    'html_url' => "https://github.com/{$this->owner}/{$this->repo}/commit/{$remoteSha}"
                ];
                return $result;
            }
        }

        $result['success'] = false;
        $result['error'] = "Impossible de joindre GitHub API ou git ls-remote. Code HTTP: " . ($apiResponse['status_code'] ?? 'N/A') . ". Erreur: " . ($apiResponse['error'] ?? 'Vérifiez la connexion réseau et le token.');
        return $result;
    }

    /**
     * Exécute le déploiement et l'installation de la mise à jour (git pull)
     */
    public function installUpdate(bool $stashIfDirty = true): array {
        $localBefore = $this->getLocalInfo();
        $logs = [];

        $logs[] = "🚀 [1/4] Démarrage du processus de mise à jour...";
        $logs[] = "Branche cible : {$this->branch} | Dépôt : {$this->owner}/{$this->repo}";
        $logs[] = "Commit actuel : {$localBefore['short_sha']} ({$localBefore['commit_message']})";

        // Gestion de l'arbre de travail
        if (!$localBefore['is_clean']) {
            if ($stashIfDirty) {
                $logs[] = "⚠️ Fichiers locaux modifiés détectés (" . count($localBefore['dirty_files']) . "). Sauvegarde automatique (git stash)...";
                $stashOutput = [];
                $stashRet = 0;
                $stashName = "Auto-stash-OpenShogun-" . date('Ymd-His');
                exec("cd {$this->basePath} && git stash push -m " . escapeshellarg($stashName) . " 2>&1", $stashOutput, $stashRet);
                $logs[] = "Stash résultat : " . trim(implode("\n", $stashOutput));
            } else {
                return [
                    'success' => false,
                    'message' => "Mise à jour annulée : des fichiers locaux ont été modifiés sans être committés.",
                    'dirty_files' => $localBefore['dirty_files'],
                    'logs' => $logs
                ];
            }
        }

        // Commande Git Pull avec Token sécurisé
        $logs[] = "📥 [2/4] Récupération et fusion des modifications (git pull)...";
        $pullUrl = "https://{$this->token}@github.com/{$this->owner}/{$this->repo}.git";
        $pullCmd = "git pull " . escapeshellarg($pullUrl) . " " . escapeshellarg($this->branch);

        $pullOutput = [];
        $pullRet = 0;
        exec("cd {$this->basePath} && {$pullCmd} 2>&1", $pullOutput, $pullRet);

        $maskedPullLog = $this->maskToken(implode("\n", $pullOutput));
        $logs[] = $maskedPullLog;

        if ($pullRet !== 0) {
            $logs[] = "❌ Échec de la commande git pull (Code retour: {$pullRet}).";
            return [
                'success' => false,
                'message' => "La commande git pull a rencontré une erreur.",
                'error_code' => $pullRet,
                'logs' => $logs
            ];
        }

        // Vérification du nouveau commit
        $localAfter = $this->getLocalInfo();
        $logs[] = "✅ [3/4] Mise à jour des fichiers terminée avec succès !";
        $logs[] = "Nouveau commit actif : {$localAfter['short_sha']} ({$localAfter['commit_message']})";

        // Nettoyage de l'Opcache si actif
        $logs[] = "🔄 [4/4] Rafraîchissement du cache applicatif...";
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $logs[] = "Cache OPcache réinitialisé.";
        }

        return [
            'success' => true,
            'message' => "Le jeu a été mis à jour avec succès vers la version {$localAfter['short_sha']} !",
            'previous_commit' => $localBefore['short_sha'],
            'current_commit' => $localAfter['short_sha'],
            'current_message' => $localAfter['commit_message'],
            'logs' => $logs
        ];
    }

    /**
     * Sauvegarde de nouveaux paramètres de mise à jour (token, branche, dépôt)
     */
    public function saveSettings(string $token, string $branch, string $owner, string $repo): bool {
        $token = trim($token);
        $branch = trim($branch) ?: 'main';
        $owner = trim($owner) ?: 'dnau1973-hash';
        $repo = trim($repo) ?: 'openshogun';

        if (!empty($token)) {
            $this->token = $token;
            @file_put_contents(__DIR__ . '/../config/github.local.php', "<?php\ndefine('GITHUB_TOKEN', " . var_export($token, true) . ");\n");
            GameConfig::set('github_token', $token);
        }
        $this->branch = $branch;
        $this->owner = $owner;
        $this->repo = $repo;

        GameConfig::set('github_branch', $branch);
        GameConfig::set('github_repo_owner', $owner);
        GameConfig::set('github_repo_name', $repo);

        return true;
    }

    /**
     * Effectue une requête HTTP cURL vers l'API GitHub avec authentification par Token
     */
    private function callGitHubApi(string $url): array {
        if (!function_exists('curl_init')) {
            return ['status_code' => 0, 'data' => null, 'error' => 'Extension PHP cURL manquante'];
        }

        $ch = curl_init($url);
        $headers = [
            'User-Agent: OpenShogun-Updater',
            'Accept: application/vnd.github.v3+json'
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['status_code' => $httpCode, 'data' => null, 'error' => $curlError];
        }

        $decoded = json_decode($body, true);
        return [
            'status_code' => $httpCode,
            'data' => $decoded,
            'error' => ($httpCode >= 400) ? ($decoded['message'] ?? 'Erreur HTTP ' . $httpCode) : null
        ];
    }
}
