<?php
/**
 * ScenarioEngine - Moteur de Scénarisation & Matrice des Tâches Métiers
 * Module Narrative Designer (OpenShogun Game Studio)
 * Fichier : core/ScenarioEngine.php
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class ScenarioEngine {
    private ?PDO $db;
    private string $jsonStorageFile;

    public const VALID_TYPES = ['step', 'special_event'];
    public const VALID_STATUSES = ['draft', 'in_review', 'in_production', 'ready', 'published', 'archived'];
    public const VALID_TASK_STATUSES = ['todo', 'in_progress', 'done', 'blocked'];
    public const VALID_COMPLEXITIES = ['S', 'M', 'L', 'XL'];

    /**
     * Matrice des 10 Métiers Clés de Développement du Jeu
     */
    public const ROLES = [
        'level_designer' => [
            'code'        => 'level_designer',
            'name'        => 'Game & Level Designer',
            'icon'        => 'fa-compass-drafting',
            'color'       => 'warning',
            'badge_class' => 'bg-warning-lt text-warning',
            'domain'      => 'Gameplay & World Building',
            'desc'        => 'Progression, flow du joueur, placement des POI, équilibrage des courbes et objectifs'
        ],
        'backend' => [
            'code'        => 'backend',
            'name'        => 'Développeur Back-End',
            'icon'        => 'fa-server',
            'color'       => 'teal',
            'badge_class' => 'bg-teal-lt text-teal',
            'domain'      => 'Moteur & Base de Données',
            'desc'        => 'Moteurs PHP, triggers d\'événements, cron, tables SQL, intégrité transactionnelle'
        ],
        'frontend' => [
            'code'        => 'frontend',
            'name'        => 'Développeur Front-End',
            'icon'        => 'fa-code',
            'color'       => 'info',
            'badge_class' => 'bg-info-lt text-info',
            'domain'      => 'Interface & UX',
            'desc'        => 'Intégration UI Tabler, modales interactives, animations CSS, responsive mobile'
        ],
        'network' => [
            'code'        => 'network',
            'name'        => 'Développeur Réseau & Infra',
            'icon'        => 'fa-network-wired',
            'color'       => 'primary',
            'badge_class' => 'bg-primary-lt text-primary',
            'domain'      => 'Réseau & Synchronisation',
            'desc'        => 'Sockets temps réel, webhooks/API, latence, gestion de charge des events collectifs'
        ],
        'artist_2d' => [
            'code'        => 'artist_2d',
            'name'        => 'Game Artist 2D & Ukiyo-e',
            'icon'        => 'fa-palette',
            'color'       => 'danger',
            'badge_class' => 'bg-danger-lt text-danger',
            'domain'      => 'Direction Artistique 2D',
            'desc'        => 'Portraits de personnages, icônes d\'items, estampes de paysages et illustrations'
        ],
        'artist_env' => [
            'code'        => 'artist_env',
            'name'        => 'Environment & Isometric Artist',
            'icon'        => 'fa-mountain-sun',
            'color'       => 'green',
            'badge_class' => 'bg-green-lt text-green',
            'domain'      => 'Décors & Isométrie',
            'desc'        => 'Bâtiments du fief, variantes météo (nuit/neige), tuiles de provinces et fiefs'
        ],
        'sound' => [
            'code'        => 'sound',
            'name'        => 'Sound Designer & Compositeur',
            'icon'        => 'fa-music',
            'color'       => 'cyan',
            'badge_class' => 'bg-cyan-lt text-cyan',
            'domain'      => 'Audio & Ambiance',
            'desc'        => 'Compositions koto/taiko, nappes ambiantes de nature, effets sonores (SFX)'
        ],
        'narrative' => [
            'code'        => 'narrative',
            'name'        => 'Narrative Designer & Scénariste',
            'icon'        => 'fa-feather-pointed',
            'color'       => 'purple',
            'badge_class' => 'bg-purple-lt text-purple',
            'domain'      => 'Écriture & Lore',
            'desc'        => 'Scénarisation, dialogues des PNJ, fidélité historique Sengoku, quêtes narratives'
        ],
        'qa' => [
            'code'        => 'qa',
            'name'        => 'QA Tester & Recetteur',
            'icon'        => 'fa-vial-circle-check',
            'color'       => 'orange',
            'badge_class' => 'bg-orange-lt text-orange',
            'domain'      => 'Qualité & Recette',
            'desc'        => 'Cahier de recette, tests de non-régression, vérification des conditions de trigger'
        ],
        'producer' => [
            'code'        => 'producer',
            'name'        => 'Producteur Live Ops & Économiste',
            'icon'        => 'fa-crown',
            'color'       => 'yellow',
            'badge_class' => 'bg-yellow-lt text-yellow',
            'domain'      => 'Planification & KPIs',
            'desc'        => 'Planification de release, métriques de complétion, économie et rétention joueur'
        ]
    ];

    public function __construct() {
        $this->jsonStorageFile = __DIR__ . '/../data/narrative_scenarios_storage.json';
        try {
            $this->db = Database::getConnection();
            $this->ensureTables();
        } catch (Throwable $e) {
            $this->db = null;
        }

        // Si aucun scénario n'est présent, initialiser avec les modèles féodaux complets
        $this->ensureDefaultScenarios();
    }

    /**
     * Initialise la table SQL si disponible
     */
    private function ensureTables(): void {
        if (!$this->db) return;
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS `narrative_scenarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `type` VARCHAR(30) NOT NULL DEFAULT 'step',
                `step_number` INT NULL,
                `title` VARCHAR(255) NOT NULL,
                `subtitle` VARCHAR(255) NULL,
                `era` VARCHAR(100) NULL,
                `synopsis` TEXT NOT NULL,
                `speaker_name` VARCHAR(150) NULL,
                `speaker_role` VARCHAR(150) NULL,
                `dialogue` TEXT NOT NULL,
                `trigger_condition` TEXT NULL,
                `rewards_summary` TEXT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
                `tasks_json` LONGTEXT NULL,
                `created_by` INT NULL,
                `created_at` INT NOT NULL,
                `updated_at` INT NOT NULL,
                INDEX `idx_scenario_type` (`type`),
                INDEX `idx_scenario_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $this->db->exec($sql);
        } catch (Throwable $e) {
            // Ignorer si déjà existant ou SQLite
        }
    }

    /**
     * Charge les scénarios depuis le stockage JSON
     */
    private function loadFromJson(): array {
        if (!file_exists($this->jsonStorageFile)) {
            return [];
        }
        $raw = file_get_contents($this->jsonStorageFile);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Sauvegarde la liste complète des scénarios dans le fichier JSON
     */
    private function saveToJson(array $scenarios): bool {
        $dir = dirname($this->jsonStorageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return file_put_contents($this->jsonStorageFile, json_encode($scenarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * Ensemence les scénarios par défaut de haute qualité si la base est vide
     */
    private function ensureDefaultScenarios(): void {
        $existing = $this->getScenarios();
        if (!empty($existing)) return;

        $defaults = $this->buildDefaultScenarios();
        foreach ($defaults as $scenario) {
            $this->saveScenarioFull($scenario);
        }
    }

    /**
     * Génère la matrice standard des 10 tâches métiers pour un scénario donné
     */
    public function generateDefaultTasks(string $type, string $title, ?int $stepNumber = null): array {
        $tasks = [];
        $i = 1;

        $contextLabel = ($type === 'step') ? "Step {$stepNumber} : {$title}" : "Événement Spécial : {$title}";

        foreach (self::ROLES as $roleKey => $roleDef) {
            $taskTitle = match($roleKey) {
                'level_designer' => "Conception du parcours gameplay & équilibrage des objectifs ({$contextLabel})",
                'backend'        => "Moteur de validation des conditions de trigger & attribution des récompenses",
                'frontend'       => "Intégration de l'interface joueur, boîte de dialogue et HUD de progression",
                'network'        => "Synchronisation serveur en direct et broadcast de progression inter-joueurs",
                'artist_2d'      => "Illustration du personnage narrateur & estampe d'ambiance 16:9",
                'artist_env'     => "Modélisation des assets visuels du fief et modifications d'état du décor",
                'sound'          => "Création de la nappe musicale Sengoku et effets sonores de triomphe",
                'narrative'      => "Rédaction finale des dialogues, relecture historique et résonance du lore",
                'qa'             => "Cahier de recette, tests de non-régression et cas limites d'abandon/succès",
                'producer'       => "Cadrage du jalon de release, monitoring de rétention et KPIs d'engagement"
            };

            $desc = match($roleKey) {
                'level_designer' => "Définir les seuils de déclenchement, les coûts de progression et s'assurer que le joueur ne rencontre ni goulot d'étranglement ni facilité excessive.",
                'backend'        => "Implémenter la logique dans ScenarioEngine / QuestEngine. Vérifier l'atomicité des transactions SQL lors de la distribution des Kobans et ressources.",
                'frontend'       => "Développer la modale narrative avec avatar animé, typewriter de dialogue, boutons de choix et affichage des récompenses en particules.",
                'network'        => "Assurer que la notification de fin de step ou de proclamation de l'événement est transmise sans latence excessive sur le cluster.",
                'artist_2d'      => "Produire un portrait expressif du narrateur au format carré (512x512) et une bannière thématique ukiyo-e respectant la charte graphique féodale.",
                'artist_env'     => "Mettre à jour les tuiles isométriques correspondantes dans la cité castrale ou sur le terroir des 9 parcelles.",
                'sound'          => "Composer un leitmotiv distinctif (koto / shakuhachi / taiko) et concevoir les jingles de validation de quête.",
                'narrative'      => "Vérifier la concordance avec la Bible Narrative d'OpenShogun, l'exactitude des kanjis et l'élégance littéraire des répliques.",
                'qa'             => "Tester la reprise de sauvegarde, l'absence de doublons de récompenses, et l'affichage sur mobile/tablette/PC.",
                'producer'       => "Vérifier la vélocité des 10 métiers sur le sprint, arbitrer les priorités et programmer la date de déploiement en Live."
            };

            $complexity = match($roleKey) {
                'backend', 'frontend', 'level_designer' => 'L',
                'artist_2d', 'artist_env', 'qa'          => 'M',
                default                                  => 'S'
            };

            $tasks[] = [
                'task_id'      => $i++,
                'role_code'    => $roleKey,
                'role_name'    => $roleDef['name'],
                'title'        => $taskTitle,
                'description'  => $desc,
                'assignee'     => $roleDef['name'],
                'complexity'   => $complexity,
                'status'       => 'todo',
                'deliverables' => match($roleKey) {
                    'backend'   => 'core/ScenarioEngine.php / migrations SQL',
                    'frontend'  => 'views/partials/modal_scenario.php / CSS',
                    'artist_2d' => 'public/assets/scenarios/portrait.jpg',
                    'qa'        => 'tests/test_scenario_' . ($stepNumber ?? 'event') . '.php',
                    default     => 'Spécifications GDD / Assets livrés'
                }
            ];
        }

        return $tasks;
    }

    /**
     * Sauvegarde un scénario complet (DB + fallback JSON)
     */
    public function saveScenarioFull(array $sc): int {
        $now = time();
        $slug = !empty($sc['slug']) ? $this->slugify($sc['slug']) : $this->slugify($sc['title'] ?? 'scenario-' . $now);
        $type = in_array($sc['type'] ?? '', self::VALID_TYPES, true) ? $sc['type'] : 'step';
        $stepNumber = isset($sc['step_number']) && $sc['step_number'] !== '' ? (int)$sc['step_number'] : null;
        $title = trim((string)($sc['title'] ?? 'Nouveau Scénario'));
        $subtitle = trim((string)($sc['subtitle'] ?? ''));
        $era = trim((string)($sc['era'] ?? 'Sengoku Jidai (~1560)'));
        $synopsis = trim((string)($sc['synopsis'] ?? ''));
        $speakerName = trim((string)($sc['speaker_name'] ?? 'Maître de la Sagesse'));
        $speakerRole = trim((string)($sc['speaker_role'] ?? 'Chie no Sensei (Mentor Féodal)'));
        $dialogue = trim((string)($sc['dialogue'] ?? ''));
        $triggerCond = trim((string)($sc['trigger_condition'] ?? ''));
        $rewards = trim((string)($sc['rewards_summary'] ?? ''));
        $status = in_array($sc['status'] ?? '', self::VALID_STATUSES, true) ? $sc['status'] : 'draft';
        $createdBy = (int)($sc['created_by'] ?? 1);

        $tasks = !empty($sc['tasks']) && is_array($sc['tasks']) 
            ? $sc['tasks'] 
            : $this->generateDefaultTasks($type, $title, $stepNumber);

        $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);

        $insertedId = 0;

        // 1. Sauvegarde en Base SQL si accessible
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO narrative_scenarios 
                    (slug, type, step_number, title, subtitle, era, synopsis, speaker_name, speaker_role, dialogue, trigger_condition, rewards_summary, status, tasks_json, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $slug, $type, $stepNumber, $title, $subtitle, $era, $synopsis,
                    $speakerName, $speakerRole, $dialogue, $triggerCond, $rewards,
                    $status, $tasksJson, $createdBy, $now, $now
                ]);
                $insertedId = (int)$this->db->lastInsertId();
            } catch (Throwable $e) {
                // Fallback direct sur JSON
            }
        }

        // 2. Sauvegarde miroir dans le JSON
        $all = $this->loadFromJson();
        if ($insertedId === 0) {
            $maxId = 0;
            foreach ($all as $item) {
                if ((int)$item['id'] > $maxId) $maxId = (int)$item['id'];
            }
            $insertedId = $maxId + 1;
        }

        $record = [
            'id'                => $insertedId,
            'slug'              => $slug,
            'type'              => $type,
            'step_number'       => $stepNumber,
            'title'             => $title,
            'subtitle'          => $subtitle,
            'era'               => $era,
            'synopsis'          => $synopsis,
            'speaker_name'      => $speakerName,
            'speaker_role'      => $speakerRole,
            'dialogue'          => $dialogue,
            'trigger_condition' => $triggerCond,
            'rewards_summary'   => $rewards,
            'status'            => $status,
            'tasks'             => $tasks,
            'created_by'        => $createdBy,
            'created_at'        => $now,
            'updated_at'        => $now
        ];

        // Remplacement ou ajout
        $found = false;
        foreach ($all as $idx => $existingItem) {
            if ($existingItem['id'] === $insertedId || $existingItem['slug'] === $slug) {
                $all[$idx] = $record;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $all[] = $record;
        }

        $this->saveToJson($all);
        return $insertedId;
    }

    /**
     * Récupère la liste de tous les scénarios avec filtres et progression des 10 tâches
     */
    public function getScenarios(?string $type = null, ?string $status = null, ?string $search = null): array {
        $results = [];

        if ($this->db) {
            try {
                $sql = "SELECT * FROM narrative_scenarios WHERE 1=1";
                $params = [];
                if ($type && in_array($type, self::VALID_TYPES, true)) {
                    $sql .= " AND type = ?";
                    $params[] = $type;
                }
                if ($status && in_array($status, self::VALID_STATUSES, true)) {
                    $sql .= " AND status = ?";
                    $params[] = $status;
                }
                if ($search && trim($search) !== '') {
                    $sql .= " AND (title LIKE ? OR synopsis LIKE ? OR slug LIKE ?)";
                    $term = '%' . trim($search) . '%';
                    $params[] = $term;
                    $params[] = $term;
                    $params[] = $term;
                }
                $sql .= " ORDER BY CASE type WHEN 'step' THEN 1 ELSE 2 END, step_number ASC, created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $tasks = !empty($row['tasks_json']) ? json_decode($row['tasks_json'], true) : [];
                    $row['tasks'] = is_array($tasks) ? $tasks : [];
                    $row['metrics'] = $this->calculateTaskMetrics($row['tasks']);
                    $results[] = $row;
                }
            } catch (Throwable $e) {
                // Erreur DB -> Fallback JSON
            }
        }

        if (empty($results)) {
            $all = $this->loadFromJson();
            foreach ($all as $row) {
                if ($type && $row['type'] !== $type) continue;
                if ($status && $row['status'] !== $status) continue;
                if ($search && trim($search) !== '') {
                    $term = mb_strtolower(trim($search));
                    $txt = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['synopsis'] ?? '') . ' ' . ($row['slug'] ?? ''));
                    if (!str_contains($txt, $term)) continue;
                }
                $tasks = $row['tasks'] ?? [];
                $row['metrics'] = $this->calculateTaskMetrics($tasks);
                $results[] = $row;
            }

            usort($results, function($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'step' ? -1 : 1;
                }
                if ($a['type'] === 'step') {
                    return ($a['step_number'] ?? 0) <=> ($b['step_number'] ?? 0);
                }
                return ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0);
            });
        }

        return $results;
    }

    /**
     * Récupère un scénario par son ID
     */
    public function getScenarioById(int $id): ?array {
        if ($id <= 0) return null;

        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM narrative_scenarios WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $tasks = !empty($row['tasks_json']) ? json_decode($row['tasks_json'], true) : [];
                    $row['tasks'] = is_array($tasks) ? $tasks : [];
                    $row['metrics'] = $this->calculateTaskMetrics($row['tasks']);
                    return $row;
                }
            } catch (Throwable $e) {
                // Fallback
            }
        }

        $all = $this->loadFromJson();
        foreach ($all as $item) {
            if ((int)$item['id'] === $id) {
                $item['metrics'] = $this->calculateTaskMetrics($item['tasks'] ?? []);
                return $item;
            }
        }
        return null;
    }

    /**
     * Calcule la télémétrie des 10 tâches métiers (Taux de complétion, tâches restantes, blocages)
     */
    public function calculateTaskMetrics(array $tasks): array {
        $total = count($tasks);
        if ($total === 0) {
            return [
                'total' => 0, 'done' => 0, 'in_progress' => 0,
                'todo' => 0, 'blocked' => 0, 'percent' => 0, 'is_complete' => false
            ];
        }

        $done = 0;
        $inProgress = 0;
        $todo = 0;
        $blocked = 0;

        foreach ($tasks as $t) {
            $st = $t['status'] ?? 'todo';
            match($st) {
                'done'        => $done++,
                'in_progress' => $inProgress++,
                'blocked'     => $blocked++,
                default       => $todo++
            };
        }

        $percent = (int)round(($done / $total) * 100);

        return [
            'total'       => $total,
            'done'        => $done,
            'in_progress' => $inProgress,
            'todo'        => $todo,
            'blocked'     => $blocked,
            'percent'     => $percent,
            'is_complete' => ($done === $total)
        ];
    }

    /**
     * Met à jour un scénario existant
     */
    public function updateScenario(int $id, array $data): bool {
        $existing = $this->getScenarioById($id);
        if (!$existing) return false;

        $now = time();
        $title = trim((string)($data['title'] ?? $existing['title']));
        $slug = !empty($data['slug']) ? $this->slugify($data['slug']) : $existing['slug'];
        $type = in_array($data['type'] ?? '', self::VALID_TYPES, true) ? $data['type'] : $existing['type'];
        $stepNumber = isset($data['step_number']) && $data['step_number'] !== '' ? (int)$data['step_number'] : $existing['step_number'];
        $subtitle = trim((string)($data['subtitle'] ?? $existing['subtitle']));
        $era = trim((string)($data['era'] ?? $existing['era']));
        $synopsis = trim((string)($data['synopsis'] ?? $existing['synopsis']));
        $speakerName = trim((string)($data['speaker_name'] ?? $existing['speaker_name']));
        $speakerRole = trim((string)($data['speaker_role'] ?? $existing['speaker_role']));
        $dialogue = trim((string)($data['dialogue'] ?? $existing['dialogue']));
        $triggerCond = trim((string)($data['trigger_condition'] ?? $existing['trigger_condition']));
        $rewards = trim((string)($data['rewards_summary'] ?? $existing['rewards_summary']));
        $status = in_array($data['status'] ?? '', self::VALID_STATUSES, true) ? $data['status'] : $existing['status'];

        $tasks = $existing['tasks'];
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $tasks = $data['tasks'];
        }

        $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);

        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    UPDATE narrative_scenarios
                    SET slug = ?, type = ?, step_number = ?, title = ?, subtitle = ?, era = ?, synopsis = ?,
                        speaker_name = ?, speaker_role = ?, dialogue = ?, trigger_condition = ?,
                        rewards_summary = ?, status = ?, tasks_json = ?, updated_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $slug, $type, $stepNumber, $title, $subtitle, $era, $synopsis,
                    $speakerName, $speakerRole, $dialogue, $triggerCond,
                    $rewards, $status, $tasksJson, $now, $id
                ]);
            } catch (Throwable $e) {
                // Fallback
            }
        }

        // Miroir JSON
        $all = $this->loadFromJson();
        foreach ($all as $idx => $item) {
            if ((int)$item['id'] === $id) {
                $all[$idx] = [
                    'id'                => $id,
                    'slug'              => $slug,
                    'type'              => $type,
                    'step_number'       => $stepNumber,
                    'title'             => $title,
                    'subtitle'          => $subtitle,
                    'era'               => $era,
                    'synopsis'          => $synopsis,
                    'speaker_name'      => $speakerName,
                    'speaker_role'      => $speakerRole,
                    'dialogue'          => $dialogue,
                    'trigger_condition' => $triggerCond,
                    'rewards_summary'   => $rewards,
                    'status'            => $status,
                    'tasks'             => $tasks,
                    'created_by'        => $item['created_by'] ?? 1,
                    'created_at'        => $item['created_at'] ?? $now,
                    'updated_at'        => $now
                ];
                break;
            }
        }
        $this->saveToJson($all);
        return true;
    }

    /**
     * Met à jour le statut d'une tâche métier spécifique
     */
    public function updateTaskStatus(int $scenarioId, int $taskId, string $newStatus): bool {
        if (!in_array($newStatus, self::VALID_TASK_STATUSES, true)) return false;

        $scenario = $this->getScenarioById($scenarioId);
        if (!$scenario) return false;

        $tasks = $scenario['tasks'];
        $modified = false;

        foreach ($tasks as $idx => $t) {
            if ((int)$t['task_id'] === $taskId) {
                $tasks[$idx]['status'] = $newStatus;
                $modified = true;
                break;
            }
        }

        if (!$modified) return false;

        return $this->updateScenario($scenarioId, ['tasks' => $tasks]);
    }

    /**
     * Supprime un scénario
     */
    public function deleteScenario(int $id): bool {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("DELETE FROM narrative_scenarios WHERE id = ?");
                $stmt->execute([$id]);
            } catch (Throwable $e) {}
        }

        $all = $this->loadFromJson();
        $filtered = array_filter($all, fn($item) => (int)$item['id'] !== $id);
        return $this->saveToJson(array_values($filtered));
    }

    /**
     * Duplique un scénario avec l'ensemble de ses 10 tâches métiers
     */
    public function duplicateScenario(int $id, int $userId = 1): ?array {
        $source = $this->getScenarioById($id);
        if (!$source) return null;

        $clone = $source;
        unset($clone['id'], $clone['metrics']);
        $clone['title'] = $source['title'] . ' (Copie)';
        $clone['slug'] = $source['slug'] . '-copie-' . time();
        $clone['status'] = 'draft';
        $clone['created_by'] = $userId;

        // Réinitialiser les statuts des tâches à 'todo'
        if (!empty($clone['tasks'])) {
            foreach ($clone['tasks'] as &$t) {
                $t['status'] = 'todo';
            }
        }

        $newId = $this->saveScenarioFull($clone);
        return $this->getScenarioById($newId);
    }

    /**
     * Export du Scénario sous format Game Design Document (GDD Markdown)
     */
    public function exportMarkdown(int $id): string {
        $sc = $this->getScenarioById($id);
        if (!$sc) return "# Erreur : Scénario introuvable.\n";

        $typeLabel = ($sc['type'] === 'step') ? "Étape de Jeu (Step #{$sc['step_number']})" : "Événement Spécial";

        $md = "# 📜 CAHIER DE SCÉNARISATION & GDD : {$sc['title']}\n\n";
        $md .= "**Type** : {$typeLabel}  \n";
        $md .= "**Identifiant** : `{$sc['slug']}`  \n";
        $md .= "**Période Historique** : {$sc['era']}  \n";
        $md .= "**Statut de Production** : " . strtoupper($sc['status']) . "  \n";
        $md .= "**Avancement Global des 10 Tâches** : {$sc['metrics']['percent']}% ({$sc['metrics']['done']}/{$sc['metrics']['total']} terminées)  \n\n";

        $md .= "## 1. Trame Narrative & Synopsis\n";
        $md .= "{$sc['synopsis']}\n\n";

        $md .= "## 2. Voix Narrative & Discours In-Game\n";
        $md .= "> **{$sc['speaker_name']}** (*{$sc['speaker_role']}*)\n";
        $md .= "> \n";
        $md .= "> « " . nl2br($sc['dialogue']) . " »\n\n";

        $md .= "## 3. Déclencheurs & Conséquences de Jeu\n";
        $md .= "- **Conditions d'Activation** : {$sc['trigger_condition']}\n";
        $md .= "- **Récompenses & Effets** : {$sc['rewards_summary']}\n\n";

        $md .= "## 4. Matrice des 10 Tâches Métiers\n\n";
        $md .= "| # | Métier Clé | Tâche Opérationnelle | Complexité | Assigné | Statut | Livrables |\n";
        $md .= "|---|---|---|---|---|---|---|\n";

        foreach ($sc['tasks'] as $t) {
            $stBadge = match($t['status']) {
                'done'        => '✅ Terminé',
                'in_progress' => '🔧 En cours',
                'blocked'     => '⛔ Bloqué',
                default       => '⏳ À faire'
            };
            $md .= "| {$t['task_id']} | **{$t['role_name']}** | {$t['title']} | `{$t['complexity']}` | {$t['assignee']} | {$stBadge} | `{$t['deliverables']}` |\n";
        }

        $md .= "\n---\n*Généré automatiquement par OpenShogun Game Studio (Narrative Designer)*\n";
        return $md;
    }

    /**
     * Construit les scénarios par défaut pour les steps du mode de jeu et les événements spéciaux
     */
    private function buildDefaultScenarios(): array {
        return [
            // STEP 1
            [
                'slug'              => 'step-01-eveil-du-fief',
                'type'              => 'step',
                'step_number'       => 1,
                'title'             => 'L\'Éveil du Fief & l\'Appel du Clan',
                'subtitle'          => 'Prologue : Fondation de la seigneurie et première moisson',
                'era'               => '1560 - Aube du Sengoku Jidai',
                'synopsis'          => 'Après des années de pillages féodaux, le conseil des anciens de la province accorde l\'autonomie à une jeune lignée de daimyōs. Le joueur doit poser les fondations de son premier domaine : défricher les terres, ériger le premier entrepôt de riz et prêter le serment d\'honneur du Bushido.',
                'speaker_name'      => 'Maître de la Sagesse (Chie no Sensei)',
                'speaker_role'      => 'Vénérable Mentor & Gardien des Traditions',
                'dialogue'          => 'Salutations, noble jeune Seigneur. Votre terre a longtemps sommeillé sous la cendre des guerres passées. Écoutez le bruissement des bambous et la voix de vos sujets : érigez votre premier grenier et honorez les esprits de cette vallée. Votre destinée commence sous le signe du dragon.',
                'trigger_condition' => 'Ouverture du Fief (Niveau de joueur 1, achèvement du pacte initial)',
                'rewards_summary'   => '100 Kobans impériaux, 500 Riz, 500 Bois de cèdre, Titre honorifique : « Seigneur Novice du Domaine »',
                'status'            => 'published',
                'created_by'        => 1
            ],
            // STEP 2
            [
                'slug'              => 'step-02-erection-forteresse',
                'type'              => 'step',
                'step_number'       => 2,
                'title'             => 'L\'Érection du Tenshu & la Muraille Cyclopéenne',
                'subtitle'          => 'Chapitre 2 : Fortification de la cité castrale face aux rōnins',
                'era'               => '1562 - Tumulte des Marches Frontalières',
                'synopsis'          => 'Les bruits de la renaissance du domaine ont attiré des bandes de pillards et mercenaires errants. Pour protéger les familles paysannes et centraliser le commandement, le seigneur doit ériger le donjon fortifié (Tenshu niveau 1) et dresser des palissades renforcées autour du bourg.',
                'speaker_name'      => 'Intendant Torii Mototada',
                'speaker_role'      => 'Maître Bâtisseur & Intendant en Chef',
                'dialogue'          => 'Mon Daimyō, une armée sans donjon est comme un sabre sans poignée. Les guetteurs ont aperçu des fumées suspectes sur les crêtes de l\'Est. Ordonnez l\'extraction du granit et montons ce donjon avant le premier gel !',
                'trigger_condition' => 'Tenshu niveau 1 construit + Palissades de bois niveau 1',
                'rewards_summary'   => '250 Kobans, 1 000 Pierres de carrière, Déblocage du simulateur de combat de garnison',
                'status'            => 'in_production',
                'created_by'        => 1
            ],
            // STEP 3
            [
                'slug'              => 'step-03-pacte-des-terroirs',
                'type'              => 'step',
                'step_number'       => 3,
                'title'             => 'Le Pacte des Terroirs & Expansion Rurale',
                'subtitle'          => 'Chapitre 3 : Maîtrise des 9 parcelles et équilibre agraire',
                'era'               => '1565 - Essor Agricole des Provinces',
                'synopsis'          => 'Une forteresse ne peut tenir sans un approvisionnement continu. Ce scénario plonge le joueur dans la mise en valeur des 9 parcelles extérieures : rizières inondées, sylviculture des cèdres, carrière de granit et plantations de thé vert montagnard.',
                'speaker_name'      => 'Dame Asuka du Clan Mori',
                'speaker_role'      => 'Déesse des Récoltes & Emissaire du Terroir',
                'dialogue'          => 'Regardez ces collines en terrasses, Mon Seigneur. L\'eau douce descend des névés pour nourrir nos rizières. Donnez des outils aux paysans, creusez les canaux d\'irrigation et notre grenier ne connaîtra jamais l\'ombre de la disette.',
                'trigger_condition' => 'Posséder au moins 4 parcelles rurales de niveau 3 ou supérieur',
                'rewards_summary'   => '+25% Rendement agricole pendant 48h, Déverrouillage des outils agraires en fer',
                'status'            => 'in_review',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 1
            [
                'slug'              => 'event-mousson-celeste',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'La Grande Mousson Céleste & Famine Blanche',
                'subtitle'          => 'Événement d\'Hiver : Tempête de neige féodale et solidarité des clans',
                'era'               => '1570 - Le Grand Hiver Sengoku',
                'synopsis'          => 'Une terrible tempête de neige s\'abat sur tout l\'archipel. Les rizières gèlent, les caravanes de marchands sont bloquées dans les cols et les oasis deviennent glacées. Les seigneurs doivent choisir entre distribuer leurs réserves de saké et de riz pour sauver leurs paysans ou thésauriser pour armer leurs soldats.',
                'speaker_name'      => 'Chie no Sensei (Maître de la Sagesse)',
                'speaker_role'      => 'Grand Ermite du Temple Shinto',
                'dialogue'          => 'Le ciel blanc ne distingue ni samouraï ni laboureur. Ouvrez les portes de vos greniers royaux, offrez le bouillon chaud et le saké à vos sujets. Le Daimyō qui protège son peuple en temps d\'épreuve trouvera dix mille sabres fidèles lorsque reviendra le printemps.',
                'trigger_condition' => 'Événement Live Déclenché (Saison Hivernale ou Feature Flag Live Ops)',
                'rewards_summary'   => 'Relique « Sceau du Flocon Éternel », Médaille de Bienveillance (+15 Morale permanente)',
                'status'            => 'ready',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 2
            [
                'slug'              => 'event-tournoi-100-lames',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'Le Tournoi Impérial des 100 Lames de Kyōto',
                'subtitle'          => 'Événement Combattant : Joutes d\'honneur entre champions de clans',
                'era'               => '1578 - Faste Impérial de Kyōto',
                'synopsis'          => 'L\'Empereur et le Shōgun organisent au cœur de la capitale un tournoi sacré pour honorer la déesse Amaterasu. Les plus grands champions de chaque fief (Oda, Takeda, Mori) sont conviés dans l\'arène du palais pour croiser le fer dans le respect absolu du code Bushido.',
                'speaker_name'      => 'Général Oda Nobunaga',
                'speaker_role'      => 'Seigneur de Guerre & Maître de l\'Artillerie',
                'dialogue'          => 'Que les lâches tremblent ! Envoyez votre plus valeureux samouraï à Kyōto. Montrez à ces seigneurs engourdis ce que vaut la lame d\'un combattant forgé par nos rudes provinces !',
                'trigger_condition' => 'Avoir un Héros Samouraï niveau 5 ou supérieur + Dojo niveau 4',
                'rewards_summary'   => 'Katana légendaire de l\'Empereur (+35 Attaque Héros), 500 Kobans, Bannière impériale de prestige',
                'status'            => 'in_production',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 3
            [
                'slug'              => 'event-incursion-wakou',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'L\'Incursion des Pirates Wakō sur la Mer Intérieure',
                'subtitle'          => 'Événement Maritime & Défense Côtière',
                'era'               => '1580 - Tempête sur la Mer de Seto',
                'synopsis'          => 'Des escadres de jonques de pirates Wakō pillent les navires marchands et menacent les ports de pêche. Tous les daimyōs côtiers doivent mobiliser leurs Atakebune et équiper leurs pêcheurs pour briser le blocus pirate.',
                'speaker_name'      => 'Amiral Murakami Takeyoshi',
                'speaker_role'      => 'Grand Corsaire du Clan Mori',
                'dialogue'          => 'Ces chiens de mer croient pouvoir braver nos côtes impunément ! Hissez nos étendards, chargez les canons à pierre et envoyons leurs jonques par le fond !',
                'trigger_condition' => 'Chantier naval niveau 2 ou 3 barques de pêche opérationnelles',
                'rewards_summary'   => 'Plans du Navire Blindé Tekkōsen, 1 500 Bois précieux, Coffres de butin pirate',
                'status'            => 'draft',
                'created_by'        => 1
            ]
        ];
    }

    /**
     * Helper de slugification
     */
    private function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'scenario-' . time() : $text;
    }
}

