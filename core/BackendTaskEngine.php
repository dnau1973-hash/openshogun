<?php
declare(strict_types=1);

/**
 * OpenShogun Game Studio - Moteur de Gestion des Tâches Développeur Back-End
 * 
 * Permet la gestion autonome du backlog technique (PHP/SQL), la synchronisation
 * bidirectionnelle avec les tâches de scénarios narratifs, et le suivi opérationnel.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ScenarioEngine.php';

class BackendTaskEngine {
    private ?PDO $db = null;

    public const STATUSES = [
        'todo' => [
            'label' => '⏳ À faire',
            'short' => 'À faire',
            'badge' => 'bg-secondary text-white',
            'badge_lt' => 'bg-secondary-lt text-secondary',
            'color' => 'secondary',
            'icon'  => 'fa-regular fa-clock'
        ],
        'in_progress' => [
            'label' => '🔧 En cours',
            'short' => 'En cours',
            'badge' => 'bg-primary text-white',
            'badge_lt' => 'bg-primary-lt text-primary',
            'color' => 'primary',
            'icon'  => 'fa-solid fa-gears'
        ],
        'review' => [
            'label' => '🔍 En revue / Tests',
            'short' => 'En revue',
            'badge' => 'bg-warning text-dark',
            'badge_lt' => 'bg-warning-lt text-warning',
            'color' => 'warning',
            'icon'  => 'fa-solid fa-flask'
        ],
        'done' => [
            'label' => '✅ Terminé',
            'short' => 'Terminé',
            'badge' => 'bg-success text-white',
            'badge_lt' => 'bg-success-lt text-success',
            'color' => 'success',
            'icon'  => 'fa-solid fa-circle-check'
        ],
        'blocked' => [
            'label' => '⛔ Bloqué',
            'short' => 'Bloqué',
            'badge' => 'bg-danger text-white',
            'badge_lt' => 'bg-danger-lt text-danger',
            'color' => 'danger',
            'icon'  => 'fa-solid fa-ban'
        ]
    ];

    public const PRIORITIES = [
        'low' => [
            'label' => 'Basse',
            'badge' => 'bg-secondary-lt text-secondary',
            'color' => 'secondary'
        ],
        'medium' => [
            'label' => 'Moyenne',
            'badge' => 'bg-info-lt text-info',
            'color' => 'info'
        ],
        'high' => [
            'label' => 'Haute',
            'badge' => 'bg-warning text-dark',
            'color' => 'warning'
        ],
        'critical' => [
            'label' => 'Critique',
            'badge' => 'bg-danger text-white',
            'color' => 'danger'
        ]
    ];

    public const COMPLEXITIES = [
        'S'  => ['label' => 'S (Simple - 1-2h)',   'color' => 'teal'],
        'M'  => ['label' => 'M (Moyen - 0.5j)',    'color' => 'info'],
        'L'  => ['label' => 'L (Large - 1-2j)',    'color' => 'warning'],
        'XL' => ['label' => 'XL (Épique - 3j+)',   'color' => 'danger']
    ];

    public function __construct() {
        try {
            $this->db = Database::getConnection();
            $this->ensureTables();
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] Database connection error: " . $e->getMessage());
        }
    }

    /**
     * Initialise la table SQL si nécessaire
     */
    private function ensureTables(): void {
        if (!$this->db) return;
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS `dev_backend_tasks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `description` MEDIUMTEXT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'todo',
                `priority` VARCHAR(30) NOT NULL DEFAULT 'medium',
                `complexity` VARCHAR(10) NOT NULL DEFAULT 'M',
                `assignee` VARCHAR(100) NULL DEFAULT 'Développeur Back-End',
                `deliverables` VARCHAR(255) NULL,
                `scenario_id` INT NULL,
                `scenario_task_id` VARCHAR(50) NULL,
                `source` VARCHAR(30) NOT NULL DEFAULT 'manual',
                `created_at` INT NOT NULL,
                `updated_at` INT NOT NULL,
                INDEX `idx_status` (`status`),
                INDEX `idx_priority` (`priority`),
                INDEX `idx_scenario` (`scenario_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $this->db->exec($sql);
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] Table initialization error: " . $e->getMessage());
        }
    }

    /**
     * Récupère la liste des tâches avec filtres
     */
    public function getTasks(array $filters = []): array {
        if (!$this->db) return [];
        try {
            $where = [];
            $params = [];

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = "`status` = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
                $where[] = "`priority` = ?";
                $params[] = $filters['priority'];
            }

            if (!empty($filters['complexity']) && $filters['complexity'] !== 'all') {
                $where[] = "`complexity` = ?";
                $params[] = $filters['complexity'];
            }

            if (!empty($filters['source']) && $filters['source'] !== 'all') {
                $where[] = "`source` = ?";
                $params[] = $filters['source'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(`title` LIKE ? OR `description` LIKE ? OR `deliverables` LIKE ?)";
                $term = '%' . trim($filters['search']) . '%';
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }

            $sql = "SELECT t.*, s.title AS scenario_title, s.type AS scenario_type, s.step_number AS scenario_step_number 
                    FROM `dev_backend_tasks` t
                    LEFT JOIN `narrative_scenarios` s ON t.scenario_id = s.id";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }

            $sql .= " ORDER BY 
                CASE t.priority 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    ELSE 4 
                END, 
                t.updated_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] getTasks error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une tâche par son identifiant
     */
    public function getTaskById(int $id): ?array {
        if (!$this->db) return null;
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, s.title AS scenario_title, s.type AS scenario_type, s.step_number AS scenario_step_number 
                FROM `dev_backend_tasks` t
                LEFT JOIN `narrative_scenarios` s ON t.scenario_id = s.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            return $task ?: null;
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] getTaskById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une nouvelle tâche
     */
    public function createTask(array $data): int {
        if (!$this->db) return 0;
        try {
            $now = time();
            $title = trim((string)($data['title'] ?? 'Nouvelle Tâche'));
            $desc  = (string)($data['description'] ?? '');
            $status = in_array($data['status'] ?? '', array_keys(self::STATUSES), true) ? $data['status'] : 'todo';
            $priority = in_array($data['priority'] ?? '', array_keys(self::PRIORITIES), true) ? $data['priority'] : 'medium';
            $complexity = in_array($data['complexity'] ?? '', array_keys(self::COMPLEXITIES), true) ? $data['complexity'] : 'M';
            $assignee = trim((string)($data['assignee'] ?? 'Développeur Back-End'));
            $deliverables = trim((string)($data['deliverables'] ?? ''));
            $scenarioId = !empty($data['scenario_id']) ? (int)$data['scenario_id'] : null;
            $scenarioTaskId = !empty($data['scenario_task_id']) ? (string)$data['scenario_task_id'] : null;
            $source = in_array($data['source'] ?? '', ['manual', 'scenario', 'hotfix'], true) ? $data['source'] : 'manual';

            $stmt = $this->db->prepare("
                INSERT INTO `dev_backend_tasks` 
                (`title`, `description`, `status`, `priority`, `complexity`, `assignee`, `deliverables`, `scenario_id`, `scenario_task_id`, `source`, `created_at`, `updated_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $desc, $status, $priority, $complexity, $assignee, $deliverables, $scenarioId, $scenarioTaskId, $source, $now, $now
            ]);

            return (int)$this->db->lastInsertId();
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] createTask error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Met à jour une tâche existante
     */
    public function updateTask(int $id, array $data): bool {
        if (!$this->db) return false;
        try {
            $now = time();
            $task = $this->getTaskById($id);
            if (!$task) return false;

            $title = isset($data['title']) ? trim((string)$data['title']) : $task['title'];
            $desc  = isset($data['description']) ? (string)$data['description'] : $task['description'];
            $status = (!empty($data['status']) && in_array($data['status'], array_keys(self::STATUSES), true)) ? $data['status'] : $task['status'];
            $priority = (!empty($data['priority']) && in_array($data['priority'], array_keys(self::PRIORITIES), true)) ? $data['priority'] : $task['priority'];
            $complexity = (!empty($data['complexity']) && in_array($data['complexity'], array_keys(self::COMPLEXITIES), true)) ? $data['complexity'] : $task['complexity'];
            $assignee = isset($data['assignee']) ? trim((string)$data['assignee']) : $task['assignee'];
            $deliverables = isset($data['deliverables']) ? trim((string)$data['deliverables']) : $task['deliverables'];

            $stmt = $this->db->prepare("
                UPDATE `dev_backend_tasks` 
                SET `title` = ?, `description` = ?, `status` = ?, `priority` = ?, `complexity` = ?, `assignee` = ?, `deliverables` = ?, `updated_at` = ?
                WHERE `id` = ?
            ");
            $res = $stmt->execute([$title, $desc, $status, $priority, $complexity, $assignee, $deliverables, $now, $id]);

            // Si c'est une tâche liée à un scénario, répercuter le statut dans le scénario
            if ($res && !empty($task['scenario_id']) && !empty($task['scenario_task_id'])) {
                $this->syncTaskBackToScenario($id);
            }

            return $res;
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] updateTask error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour uniquement le statut d'une tâche (micro-action AJAX Kanban)
     */
    public function updateTaskStatus(int $id, string $status): bool {
        if (!$this->db) return false;
        if (!in_array($status, array_keys(self::STATUSES), true)) return false;
        try {
            $now = time();
            $stmt = $this->db->prepare("UPDATE `dev_backend_tasks` SET `status` = ?, `updated_at` = ? WHERE `id` = ?");
            $res = $stmt->execute([$status, $now, $id]);

            if ($res) {
                $this->syncTaskBackToScenario($id);
            }
            return $res;
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] updateTaskStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une tâche
     */
    public function deleteTask(int $id): bool {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("DELETE FROM `dev_backend_tasks` WHERE `id` = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] deleteTask error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule les métriques globales du backlog Back-End
     */
    public function getMetrics(): array {
        $tasks = $this->getTasks();
        $total = count($tasks);
        $done = 0;
        $inProgress = 0;
        $review = 0;
        $todo = 0;
        $blocked = 0;

        $byPriority = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        $bySource = ['manual' => 0, 'scenario' => 0, 'hotfix' => 0];

        foreach ($tasks as $t) {
            $st = $t['status'] ?? 'todo';
            if ($st === 'done') $done++;
            elseif ($st === 'in_progress') $inProgress++;
            elseif ($st === 'review') $review++;
            elseif ($st === 'blocked') $blocked++;
            else $todo++;

            $pr = $t['priority'] ?? 'medium';
            if (isset($byPriority[$pr])) $byPriority[$pr]++;

            $src = $t['source'] ?? 'manual';
            if (isset($bySource[$src])) $bySource[$src]++;
        }

        $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;

        return [
            'total'       => $total,
            'done'        => $done,
            'in_progress' => $inProgress,
            'review'      => $review,
            'todo'        => $todo,
            'blocked'     => $blocked,
            'percent'     => $percent,
            'by_priority' => $byPriority,
            'by_source'   => $bySource
        ];
    }

    /**
     * Synchronise et importe toutes les tâches assignées au rôle 'backend'
     * depuis tous les scénarios existants dans narrative_scenarios.
     */
    public function syncTasksFromScenarios(): array {
        if (!$this->db) return ['imported' => 0, 'updated' => 0, 'total' => 0];

        $scenarioEngine = new ScenarioEngine();
        $allScenarios = $scenarioEngine->getScenarios();
        $importedCount = 0;
        $updatedCount = 0;
        $totalScenarioBackendTasks = 0;

        foreach ($allScenarios as $sc) {
            $scId = (int)$sc['id'];
            $tasks = $sc['tasks'] ?? [];

            foreach ($tasks as $t) {
                $roleCode = (string)($t['role_code'] ?? '');
                if ($roleCode !== 'backend') {
                    continue;
                }

                $totalScenarioBackendTasks++;
                $tId = (string)($t['task_id'] ?? '');
                $title = trim((string)($t['title'] ?? "Tâche Back-End Scénario #{$scId}"));
                $desc = (string)($t['description'] ?? '');
                $deliverables = (string)($t['deliverables'] ?? '');
                $complexity = (string)($t['complexity'] ?? 'M');
                $scStatus = (string)($t['status'] ?? 'todo');
                $assignee = (string)($t['assignee'] ?? 'Développeur Back-End');

                // Mapper le statut du scénario vers le backlog backend
                $statusMap = [
                    'done' => 'done',
                    'in_progress' => 'in_progress',
                    'blocked' => 'blocked',
                    'todo' => 'todo'
                ];
                $mappedStatus = $statusMap[$scStatus] ?? 'todo';

                // Vérifier si cette tâche de scénario est déjà enregistrée
                $stmtCheck = $this->db->prepare("
                    SELECT `id`, `status` FROM `dev_backend_tasks` 
                    WHERE `scenario_id` = ? AND `scenario_task_id` = ?
                ");
                $stmtCheck->execute([$scId, $tId]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // Mise à jour douce : on actualise le titre, la description et les livrables
                    // On ne modifie pas le statut si le dev l'a déjà fait avancer (ex: en cours ou en revue)
                    $stmtUp = $this->db->prepare("
                        UPDATE `dev_backend_tasks`
                        SET `title` = ?, `description` = ?, `deliverables` = ?, `complexity` = ?, `updated_at` = ?
                        WHERE `id` = ?
                    ");
                    $stmtUp->execute([$title, $desc, $deliverables, $complexity, time(), $existing['id']]);
                    $updatedCount++;
                } else {
                    // Insertion d'une nouvelle tâche issue du scénario
                    $this->createTask([
                        'title'            => $title,
                        'description'      => $desc,
                        'status'           => $mappedStatus,
                        'priority'         => ($complexity === 'XL' || $complexity === 'L') ? 'high' : 'medium',
                        'complexity'       => in_array($complexity, ['S','M','L','XL'], true) ? $complexity : 'M',
                        'assignee'         => $assignee ?: 'Développeur Back-End',
                        'deliverables'     => $deliverables,
                        'scenario_id'      => $scId,
                        'scenario_task_id' => $tId,
                        'source'           => 'scenario'
                    ]);
                    $importedCount++;
                }
            }
        }

        return [
            'imported' => $importedCount,
            'updated'  => $updatedCount,
            'total'    => $totalScenarioBackendTasks
        ];
    }

    /**
     * Répercute le statut d'une tâche vers son scénario d'origine si applicable
     */
    public function syncTaskBackToScenario(int $backendTaskId): bool {
        if (!$this->db) return false;
        try {
            $task = $this->getTaskById($backendTaskId);
            if (!$task || empty($task['scenario_id']) || empty($task['scenario_task_id'])) {
                return false;
            }

            $scenarioEngine = new ScenarioEngine();
            $sc = $scenarioEngine->getScenarioById((int)$task['scenario_id']);
            if (!$sc) return false;

            $tasks = $sc['tasks'] ?? [];
            $modified = false;

            // Mapper le statut backend vers le statut scénario ('todo', 'in_progress', 'done', 'blocked')
            $scenarioStatusMap = [
                'todo'        => 'todo',
                'in_progress' => 'in_progress',
                'review'      => 'in_progress', // 'review' est considéré comme 'in_progress' côté scénario
                'done'        => 'done',
                'blocked'     => 'blocked'
            ];
            $targetStatus = $scenarioStatusMap[$task['status']] ?? 'todo';

            foreach ($tasks as &$t) {
                if ((string)($t['task_id'] ?? '') === (string)$task['scenario_task_id']) {
                    if (($t['status'] ?? '') !== $targetStatus) {
                        $t['status'] = $targetStatus;
                        $modified = true;
                    }
                    if (!empty($task['description'])) {
                        $t['description'] = $task['description'];
                    }
                    if (!empty($task['deliverables'])) {
                        $t['deliverables'] = $task['deliverables'];
                    }
                    break;
                }
            }
            unset($t);

            if ($modified) {
                $sc['tasks'] = $tasks;
                $scenarioEngine->saveScenario($sc);
            }

            return true;
        } catch (Throwable $e) {
            error_log("[BackendTaskEngine] syncTaskBackToScenario error: " . $e->getMessage());
            return false;
        }
    }
}

