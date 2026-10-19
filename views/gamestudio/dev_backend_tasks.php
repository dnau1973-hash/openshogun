<?php
/**
 * Vue Partielle MVC : Backlog Technique & Tâches Développeur Back-End
 * Module Développeur (Back-End, Front-End & Réseau)
 * Fichier : views/gamestudio/dev_backend_tasks.php
 */
if (!isset($backendTaskEngine)) {
    require_once __DIR__ . '/../../core/BackendTaskEngine.php';
    $backendTaskEngine = new BackendTaskEngine();
}

// Paramètres de filtrage GET
$filterStatus = !empty($_GET['status']) && in_array($_GET['status'], BackendTaskEngine::VALID_STATUSES) ? $_GET['status'] : null;
$filterPriority = !empty($_GET['priority']) && in_array($_GET['priority'], BackendTaskEngine::VALID_PRIORITIES) ? $_GET['priority'] : null;
$filterComplexity = !empty($_GET['complexity']) && in_array($_GET['complexity'], BackendTaskEngine::VALID_COMPLEXITIES) ? $_GET['complexity'] : null;
$filterSource = !empty($_GET['source']) && in_array($_GET['source'], ['manual', 'scenario']) ? $_GET['source'] : null;
$searchQuery = trim($_GET['search'] ?? '');
$viewMode = ($_GET['view'] ?? 'kanban') === 'table' ? 'table' : 'kanban';

$filters = [];
if ($filterStatus) $filters['status'] = $filterStatus;
if ($filterPriority) $filters['priority'] = $filterPriority;
if ($filterComplexity) $filters['complexity'] = $filterComplexity;
if ($filterSource) $filters['source'] = $filterSource;
if ($searchQuery !== '') $filters['search'] = $searchQuery;

$tasks = $backendTaskEngine->getTasks($filters);
$metrics = $backendTaskEngine->getMetrics();

// Flash message
$flash = $_SESSION['dev_backend_flash'] ?? null;
unset($_SESSION['dev_backend_flash']);

// Mappings visuels
$statusLabels = [
    'todo'        => ['label' => 'À faire',    'badge' => 'secondary', 'icon' => 'fa-clock'],
    'in_progress' => ['label' => 'En cours',   'badge' => 'primary',   'icon' => 'fa-screwdriver-wrench'],
    'review'      => ['label' => 'En revue',   'badge' => 'purple',    'icon' => 'fa-magnifying-glass-check'],
    'blocked'     => ['label' => 'Bloqué',     'badge' => 'danger',    'icon' => 'fa-ban'],
    'done'        => ['label' => 'Terminé',    'badge' => 'success',   'icon' => 'fa-circle-check']
];

$priorityLabels = [
    'low'      => ['label' => 'Basse',    'badge' => 'secondary-lt text-secondary', 'icon' => 'fa-arrow-down'],
    'medium'   => ['label' => 'Moyenne',  'badge' => 'info-lt text-info',           'icon' => 'fa-minus'],
    'high'     => ['label' => 'Haute',    'badge' => 'warning-lt text-warning',     'icon' => 'fa-arrow-up'],
    'critical' => ['label' => 'Critique', 'badge' => 'danger-lt text-danger fw-bold','icon' => 'fa-triangle-exclamation']
];

$complexityLabels = [
    'S'  => ['label' => 'S (Rapide)',     'badge' => 'teal-lt text-teal'],
    'M'  => ['label' => 'M (Standard)',   'badge' => 'azure-lt text-azure'],
    'L'  => ['label' => 'L (Complexe)',   'badge' => 'warning-lt text-warning'],
    'XL' => ['label' => 'XL (Architecture)', 'badge' => 'danger-lt text-danger']
];
?>

<div class="gamestudio-backend-tasks">
    <!-- Message Flash / Alerte -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger' ?> fs-3 me-3"></i>
                <div class="fs-4"><?= htmlspecialchars($flash['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-indigo d-flex align-items-center gap-2 m-0" style="font-size:1.4rem;">
                <i class="fa-solid fa-server text-indigo"></i>
                <span>Développeur Back-End &bull; Backlog Technique &amp; Tâches Scénarios</span>
            </h3>
            <div class="text-secondary small mt-1">
                Gestion unifiée du backlog technique, synchronisation directe avec le Gestionnaire de Scénarios narratifs et cahier des charges Markdown/Mermaid.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Bouton Synchronisation Scénarios -->
            <button type="button" class="btn btn-outline-purple shadow-sm d-flex align-items-center gap-2" id="btnSyncScenarios" onclick="triggerScenarioSync()">
                <i class="fa-solid fa-rotate text-purple"></i>
                <span>Synchroniser Scénarios</span>
            </button>
            <!-- Bouton Nouvelle Tâche -->
            <button type="button" class="btn btn-primary shadow-sm d-flex align-items-center gap-2" id="btnOpenCreateTaskModal" onclick="openCreateTaskModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Nouvelle Tâche</span>
            </button>
        </div>
    </div>

    <!-- Cockpit d'avancement & Métriques clés -->
    <div class="row row-cards mb-4">
        <!-- Progression Globale -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-bold text-uppercase">Taux d'Achèvement</span>
                        <span class="badge bg-indigo-lt text-indigo fw-bold"><?= $metrics['total'] ?> Tâches</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <div class="h1 mb-0 text-dark"><?= $metrics['pct_done'] ?>%</div>
                        <div class="text-secondary small">livrées</div>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-indigo" style="width: <?= $metrics['pct_done'] ?>%" role="progressbar" aria-valuenow="<?= $metrics['pct_done'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition Statuts -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small fw-bold text-uppercase mb-2">Pipeline Actif</div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-1 rounded bg-secondary-lt">
                                <div class="fw-bold fs-3 text-secondary"><?= $metrics['todo'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">À faire</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-1 rounded bg-primary-lt">
                                <div class="fw-bold fs-3 text-primary"><?= $metrics['in_progress'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">En cours</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-1 rounded bg-purple-lt">
                                <div class="fw-bold fs-3 text-purple"><?= $metrics['review'] ?></div>
                                <div class="text-muted" style="font-size:0.7rem;">Revue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blocages & Alertes -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small fw-bold text-uppercase mb-2">Santé &amp; Blocages</div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="d-flex align-items-center gap-1 text-danger fw-bold">
                            <i class="fa-solid fa-ban"></i> Tâches Bloquées
                        </span>
                        <span class="badge bg-danger text-white rounded-pill fs-4 px-2"><?= $metrics['blocked'] ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-1 text-warning fw-bold">
                            <i class="fa-solid fa-triangle-exclamation"></i> Priorité Critique
                        </span>
                        <span class="badge bg-warning-lt text-warning fw-bold"><?= $metrics['critical'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tâches Synchronisées Scénarios -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-bold text-uppercase">Origine des Tâches</span>
                        <i class="fa-solid fa-scroll text-purple fs-3"></i>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <div class="h2 mb-0 text-purple"><?= $metrics['from_scenarios'] ?></div>
                        <div class="text-secondary small">issues des scénarios</div>
                    </div>
                    <div class="text-secondary small" style="font-size:0.75rem;">
                        <?= $metrics['manual'] ?> tâche(s) manuelle(s) créée(s)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre d'outils, Recherche & Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="dev_backend_tasks">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

                <!-- Champ Recherche -->
                <div class="col-md-3">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher par titre, livrables..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                </div>

                <!-- Filtre Statut -->
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les statuts</option>
                        <?php foreach (BackendTaskEngine::VALID_STATUSES as $st): ?>
                            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>>
                                <?= $statusLabels[$st]['label'] ?> (<?= $metrics[$st] ?? 0 ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtre Priorité -->
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes priorités</option>
                        <?php foreach (BackendTaskEngine::VALID_PRIORITIES as $pr): ?>
                            <option value="<?= $pr ?>" <?= $filterPriority === $pr ? 'selected' : '' ?>>
                                Priorité <?= $priorityLabels[$pr]['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtre Complexité -->
                <div class="col-md-2">
                    <select name="complexity" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes complexités</option>
                        <?php foreach (BackendTaskEngine::VALID_COMPLEXITIES as $cpx): ?>
                            <option value="<?= $cpx ?>" <?= $filterComplexity === $cpx ? 'selected' : '' ?>>
                                Taille <?= $complexityLabels[$cpx]['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtre Source -->
                <div class="col-md-1">
                    <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Source</option>
                        <option value="scenario" <?= $filterSource === 'scenario' ? 'selected' : '' ?>>Scénario</option>
                        <option value="manual" <?= $filterSource === 'manual' ? 'selected' : '' ?>>Manuel</option>
                    </select>
                </div>

                <!-- Actions et Bascule de vue -->
                <div class="col-md-2 d-flex align-items-center justify-content-end gap-2">
                    <?php if ($filterStatus || $filterPriority || $filterComplexity || $filterSource || $searchQuery !== ''): ?>
                        <a href="?page=gamestudio&tab=dev_backend_tasks&view=<?= htmlspecialchars($viewMode) ?>" class="btn btn-sm btn-outline-secondary" title="Réinitialiser filtres">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>

                    <div class="btn-group btn-group-sm" role="group">
                        <a href="?page=gamestudio&tab=dev_backend_tasks&view=kanban<?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPriority ? '&priority='.$filterPriority : '' ?><?= $filterComplexity ? '&complexity='.$filterComplexity : '' ?><?= $filterSource ? '&source='.$filterSource : '' ?><?= $searchQuery !== '' ? '&search='.urlencode($searchQuery) : '' ?>" 
                           class="btn <?= $viewMode === 'kanban' ? 'btn-primary' : 'btn-outline-secondary' ?>" title="Vue Kanban">
                            <i class="fa-solid fa-table-columns"></i>
                        </a>
                        <a href="?page=gamestudio&tab=dev_backend_tasks&view=table<?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPriority ? '&priority='.$filterPriority : '' ?><?= $filterComplexity ? '&complexity='.$filterComplexity : '' ?><?= $filterSource ? '&source='.$filterSource : '' ?><?= $searchQuery !== '' ? '&search='.urlencode($searchQuery) : '' ?>" 
                           class="btn <?= $viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary' ?>" title="Vue Liste">
                            <i class="fa-solid fa-list-ul"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($viewMode === 'kanban'): ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- VUE KANBAN INTERACTIVE (DRAG & DROP SUR 5 COLONNES)                  -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="row g-3 kanban-board">
            <?php foreach (BackendTaskEngine::VALID_STATUSES as $colStatus): 
                $colTasks = array_values(array_filter($tasks, fn($t) => $t['status'] === $colStatus));
                $colMeta = $statusLabels[$colStatus];
            ?>
                <div class="col-12 col-md-6 col-xl">
                    <div class="kanban-column bg-light-subtle rounded-3 p-2 border border-2 border-dashed border-secondary-subtle d-flex flex-column h-100" 
                         data-status="<?= $colStatus ?>" 
                         ondragover="onKanbanDragOver(event)" 
                         ondragleave="onKanbanDragLeave(event)" 
                         ondrop="onKanbanDrop(event, '<?= $colStatus ?>')">
                        
                        <!-- En-tête de colonne -->
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded shadow-sm border-start border-3 border-<?= $colMeta['badge'] ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid <?= $colMeta['icon'] ?> text-<?= $colMeta['badge'] ?>"></i>
                                <span class="fw-bold small text-dark"><?= $colMeta['label'] ?></span>
                            </div>
                            <span class="badge bg-<?= $colMeta['badge'] ?>-lt text-<?= $colMeta['badge'] ?> rounded-pill kanban-col-count" id="count-<?= $colStatus ?>">
                                <?= count($colTasks) ?>
                            </span>
                        </div>

                        <!-- Liste des cartes de tâches -->
                        <div class="kanban-card-list d-flex flex-column gap-2 flex-grow-1" style="min-height: 380px;">
                            <?php if (empty($colTasks)): ?>
                                <div class="text-center text-muted p-4 my-auto kanban-empty-placeholder">
                                    <i class="fa-regular fa-clipboard fs-2 mb-2 opacity-50"></i>
                                    <div class="small">Aucune tâche</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($colTasks as $task): 
                                    $prioMeta = $priorityLabels[$task['priority']] ?? $priorityLabels['medium'];
                                    $cpxMeta = $complexityLabels[$task['complexity']] ?? $complexityLabels['M'];
                                ?>
                                    <div class="card kanban-task-card shadow-sm border border-secondary-subtle cursor-grab" 
                                         id="task-card-<?= $task['id'] ?>"
                                         draggable="true" 
                                         ondragstart="onKanbanDragStart(event, <?= $task['id'] ?>)" 
                                         ondragend="onKanbanDragEnd(event)">
                                        <div class="card-body p-2-5">
                                            <!-- En-tête de carte : ID, Priorité, Complexité -->
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-dark-lt text-dark font-monospace" style="font-size:0.68rem;">#<?= $task['id'] ?></span>
                                                    <span class="badge <?= $prioMeta['badge'] ?>" style="font-size:0.65rem;" title="Priorité <?= $prioMeta['label'] ?>">
                                                        <i class="fa-solid <?= $prioMeta['icon'] ?> me-1"></i><?= $prioMeta['label'] ?>
                                                    </span>
                                                    <span class="badge <?= $cpxMeta['badge'] ?> fw-bold font-monospace" style="font-size:0.65rem;" title="Taille / Complexité">
                                                        <?= $task['complexity'] ?>
                                                    </span>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-ghost-secondary p-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:20px; height:20px;">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 small" href="javascript:void(0)" onclick="openTaskQuickPreview(<?= $task['id'] ?>)">
                                                                <i class="fa-solid fa-eye text-primary"></i> Aperçu .md
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 small" href="javascript:void(0)" onclick="openEditTaskModal(<?= $task['id'] ?>)">
                                                                <i class="fa-solid fa-pen-to-square text-warning"></i> Modifier
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 small text-danger" href="javascript:void(0)" onclick="confirmDeleteTask(<?= $task['id'] ?>, '<?= htmlspecialchars(addslashes($task['title'])) ?>')">
                                                                <i class="fa-solid fa-trash"></i> Supprimer
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Titre de la tâche -->
                                            <div class="fw-bold text-dark small mb-1 kanban-task-title" style="line-height:1.25; font-size:0.85rem;">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </div>

                                            <!-- Scénario lié si applicable -->
                                            <?php if (!empty($task['scenario_id'])): ?>
                                                <div class="mb-1">
                                                    <a href="?page=gamestudio&tab=narrative_scenarios&scenario_id=<?= (int)$task['scenario_id'] ?>" target="_blank" class="badge bg-purple-lt text-purple text-decoration-none text-truncate d-inline-block" style="max-width:100%; font-size:0.65rem;" title="Origine : Scénario #<?= $task['scenario_id'] ?> (<?= htmlspecialchars($task['scenario_title'] ?? '') ?>)">
                                                        <i class="fa-solid fa-scroll me-1"></i><?= htmlspecialchars($task['scenario_title'] ?: 'Scénario #' . $task['scenario_id']) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Livrables attendus (si définis) -->
                                            <?php if (!empty($task['deliverables'])): ?>
                                                <div class="text-secondary small font-monospace mb-2 text-truncate" style="font-size:0.7rem;" title="<?= htmlspecialchars($task['deliverables']) ?>">
                                                    <i class="fa-solid fa-box-archive me-1 text-muted"></i><?= htmlspecialchars($task['deliverables']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Pied de carte : Assigné, Heures et Bouton Aperçu .md rapide -->
                                            <div class="d-flex align-items-center justify-content-between pt-1 border-top border-secondary-subtle">
                                                <div class="d-flex align-items-center gap-1 text-secondary" style="font-size:0.7rem;">
                                                    <i class="fa-solid fa-user-circle"></i>
                                                    <span><?= htmlspecialchars($task['assigned_to'] ?: 'Back-End') ?></span>
                                                    <?php if ($task['estimated_hours'] > 0): ?>
                                                        <span class="ms-1 badge bg-secondary-lt text-secondary font-monospace" style="font-size:0.62rem;"><?= $task['estimated_hours'] ?>h</span>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-xs btn-outline-purple py-0 px-1-5" style="font-size:0.68rem; height:22px;" onclick="openTaskQuickPreview(<?= $task['id'] ?>)" title="Aperçu Markdown & Flowcharts">
                                                    <i class="fa-solid fa-file-lines me-1"></i>.md
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- VUE TABLEAU / LISTE STRUCTURÉE                                      -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter card-table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="w-1">ID</th>
                            <th>Titre &amp; Livrables</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Taille</th>
                            <th>Assigné</th>
                            <th>Origine</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-inbox fs-1 mb-2 opacity-50"></i>
                                    <div>Aucune tâche Back-End trouvée selon les critères actuels.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): 
                                $stMeta = $statusLabels[$task['status']] ?? $statusLabels['todo'];
                                $prioMeta = $priorityLabels[$task['priority']] ?? $priorityLabels['medium'];
                                $cpxMeta = $complexityLabels[$task['complexity']] ?? $complexityLabels['M'];
                            ?>
                                <tr>
                                    <td class="font-monospace text-muted small">#<?= $task['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($task['title']) ?></div>
                                        <?php if (!empty($task['deliverables'])): ?>
                                            <div class="text-secondary small font-monospace mt-1" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-box-archive me-1 text-muted"></i><?= htmlspecialchars($task['deliverables']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $stMeta['badge'] ?> text-white">
                                            <i class="fa-solid <?= $stMeta['icon'] ?> me-1"></i><?= $stMeta['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $prioMeta['badge'] ?>">
                                            <i class="fa-solid <?= $prioMeta['icon'] ?> me-1"></i><?= $prioMeta['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $cpxMeta['badge'] ?> fw-bold font-monospace">
                                            <?= $task['complexity'] ?>
                                        </span>
                                    </td>
                                    <td class="small text-secondary">
                                        <i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($task['assigned_to'] ?: 'Back-End') ?>
                                        <?php if ($task['estimated_hours'] > 0): ?>
                                            <span class="badge bg-secondary-lt text-secondary font-monospace ms-1"><?= $task['estimated_hours'] ?>h</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($task['scenario_id'])): ?>
                                            <a href="?page=gamestudio&tab=narrative_scenarios&scenario_id=<?= (int)$task['scenario_id'] ?>" target="_blank" class="badge bg-purple-lt text-purple text-decoration-none">
                                                <i class="fa-solid fa-scroll me-1"></i>Scénario #<?= $task['scenario_id'] ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Manuel</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-purple" onclick="openTaskQuickPreview(<?= $task['id'] ?>)" title="Aperçu .md & Flowcharts">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" onclick="openEditTaskModal(<?= $task['id'] ?>)" title="Modifier">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteTask(<?= $task['id'] ?>, '<?= htmlspecialchars(addslashes($task['title'])) ?>')" title="Supprimer">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- MODALE UNIVERSELLE : CRÉATION / ÉDITION DE TÂCHE BACK-END               -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalBackendTaskForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="?page=gamestudio&tab=dev_backend_tasks" id="formBackendTask">
                <input type="hidden" name="action" id="taskFormAction" value="create_task">
                <input type="hidden" name="task_id" id="taskFormId" value="0">

                <div class="modal-header bg-dark text-white py-2">
                    <h5 class="modal-title d-flex align-items-center gap-2 m-0" id="modalTaskFormTitle">
                        <i class="fa-solid fa-server text-teal"></i>
                        <span>Nouvelle Tâche Back-End</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Titre de la tâche -->
                        <div class="col-12">
                            <label class="form-label required fw-bold small">Titre de la tâche technique</label>
                            <input type="text" name="title" id="taskFormTitleInput" class="form-control" placeholder="ex: Créer l'endpoint API de validation des échanges de koku..." required>
                        </div>

                        <!-- Statut, Priorité, Complexité -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Statut</label>
                            <select name="status" id="taskFormStatusSelect" class="form-select">
                                <?php foreach (BackendTaskEngine::VALID_STATUSES as $st): ?>
                                    <option value="<?= $st ?>"><?= $statusLabels[$st]['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Priorité</label>
                            <select name="priority" id="taskFormPrioritySelect" class="form-select">
                                <?php foreach (BackendTaskEngine::VALID_PRIORITIES as $pr): ?>
                                    <option value="<?= $pr ?>" <?= $pr === 'medium' ? 'selected' : '' ?>>
                                        <?= $priorityLabels[$pr]['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Complexité (T-Shirt)</label>
                            <select name="complexity" id="taskFormComplexitySelect" class="form-select">
                                <?php foreach (BackendTaskEngine::VALID_COMPLEXITIES as $cpx): ?>
                                    <option value="<?= $cpx ?>" <?= $cpx === 'M' ? 'selected' : '' ?>>
                                        <?= $complexityLabels[$cpx]['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Assigné, Heures estimées -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Développeur Assigné</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="assigned_to" id="taskFormAssignedInput" class="form-control" placeholder="ex: Back-End Lead, Daimyō, etc.">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Estimation Temps (heures)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="fa-solid fa-stopwatch"></i></span>
                                <input type="number" step="0.5" min="0" max="999" name="estimated_hours" id="taskFormHoursInput" class="form-control" placeholder="ex: 4.5">
                            </div>
                        </div>

                        <!-- Livrables attendus -->
                        <div class="col-12">
                            <label class="form-label fw-bold small">Livrables Attendus (Endpoints, Tables, Classes PHP)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="fa-solid fa-box-archive"></i></span>
                                <input type="text" name="deliverables" id="taskFormDeliverablesInput" class="form-control font-monospace small" placeholder="ex: POST /api/trade, core/TradeEngine.php, table `trades`">
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════════ -->
                        <!-- CHAMP DESCRIPTION AVEC DOUBLE FONCTION : MODIF & APERÇU -->
                        <!-- ═══════════════════════════════════════════════════════ -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label fw-bold small mb-0">Cahier des Charges &amp; Description Technique (.md)</label>
                                    <span class="badge bg-purple-lt text-purple small" style="font-size:0.68rem;">
                                        <i class="fa-solid fa-diagram-project me-1"></i>Flowcharts &amp; Mermaid supportés
                                    </span>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Bouton Importer un fichier .md -->
                                    <input type="file" id="taskFormMdFileInput" accept=".md,.markdown,.txt" style="display:none;" onchange="onTaskFormMdFileSelected(this)">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:0.75rem; height:26px;" onclick="triggerTaskFormMdFileInput()" title="Importer un fichier Markdown depuis votre ordinateur">
                                        <i class="fa-solid fa-file-arrow-up me-1 text-purple"></i>Importer .md
                                    </button>

                                    <!-- Commutateur Mode Édition vs Aperçu .md -->
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" id="btnTabTaskEdit" onclick="switchTaskDescMode('edit')">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Édition
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTabTaskPreview" onclick="switchTaskDescMode('preview')">
                                            <i class="fa-solid fa-eye me-1"></i>Aperçu .md
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Badge Fichier Importé -->
                            <div id="taskFormMdImportBadge" class="badge bg-teal-lt text-teal mb-2" style="display:none;"></div>

                            <!-- Zone d'Édition Textarea (avec Drag & Drop de fichier .md) -->
                            <div id="taskFormEditArea">
                                <textarea name="description" id="taskFormDescTextarea" class="form-control font-monospace" rows="9" 
                                          placeholder="Rédigez ou collez vos spécifications techniques au format Markdown...&#10;&#10;Supporte les titres (#), listes, tableaux (| col | col |) et les flowcharts Mermaid (```mermaid ... ```).&#10;Astuce : Glissez-déposez un fichier .md directement dans cette zone !"
                                          ondragover="onTaskFormDescDragOver(event)" 
                                          ondragleave="onTaskFormDescDragLeave(event)" 
                                          ondrop="onTaskFormDescDrop(event)"
                                          oninput="onTaskFormDescInput()"></textarea>
                                <div class="text-secondary small mt-1 d-flex justify-content-between align-items-center" style="font-size:0.7rem;">
                                    <span><i class="fa-solid fa-info-circle me-1"></i>Glissez-déposez un fichier <code>.md</code> dans la zone pour l'importer instantanément.</span>
                                    <span id="taskFormDescCharCount" class="font-monospace text-muted">0 caractères</span>
                                </div>
                            </div>

                            <!-- Zone d'Aperçu Markdown formaté avec Flowcharts & Mermaid -->
                            <div id="taskFormPreviewArea" style="display:none;">
                                <div class="card border rounded bg-white shadow-sm">
                                    <div class="card-header bg-light py-1-5 px-3 d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-purple d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-eye"></i>Rendu Visuel Markdown &amp; Diagrammes de Flux
                                        </span>
                                        <span class="badge bg-secondary-lt text-secondary font-monospace" style="font-size:0.65rem;">Moteur Markdown Studio 2.0</span>
                                    </div>
                                    <div class="card-body p-3 overflow-y-auto" id="taskFormPreviewContent" style="max-height: 280px; min-height: 180px; background: #ffffff;">
                                        <!-- Rendu injecté dynamiquement -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" id="btnSubmitTaskForm">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="btnSubmitTaskFormText">Enregistrer la Tâche</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- MODALE D'APERÇU PLEIN ÉCRAN .MD & FLOWCHARTS                            -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalTaskQuickPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-code text-teal fs-3"></i>
                    <div>
                        <h5 class="modal-title m-0 text-white" id="quickPreviewTaskTitle">Cahier des Charges &amp; Spécifications</h5>
                        <div class="text-secondary small" id="quickPreviewTaskSub" style="font-size:0.75rem;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="quickPreviewTaskBody" style="min-height: 350px;">
                <!-- Rendu injecté -->
            </div>
            <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                <div class="d-flex align-items-center gap-2" id="quickPreviewMetaBadges"></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-warning btn-sm" id="btnQuickPreviewEdit" onclick="editFromQuickPreview()">
                        <i class="fa-solid fa-pen-to-square me-1"></i>Modifier la Tâche
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Moteur de Rendu Graphique Mermaid.js v10 (chargé avec defer pour ne pas bloquer l'interface) -->
<script defer src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>

<script>
// ═══════════════════════════════════════════════════════════════════════════
// REGISTRE DES TÂCHES PRÉCHARGÉES (SÉCURISÉ CONTRE LES INJECTIONS ET ERREURS DE PARSING)
// ═══════════════════════════════════════════════════════════════════════════
const PRELOADED_BACKEND_TASKS = <?= json_encode($tasks ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
let activePreviewTaskId = null;

// ═══════════════════════════════════════════════════════════════════════════
// GESTIONNAIRE UNIVERSEL DE MODALES BOOTSTRAP TABLER
// ═══════════════════════════════════════════════════════════════════════════
function getBootstrapModalInstance(modalEl) {
    if (!modalEl) return null;
    if (window.bootstrap && window.bootstrap.Modal) {
        if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
            return window.bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return new window.bootstrap.Modal(modalEl);
    }
    return null;
}

function showModalSafely(modalId) {
    const el = document.getElementById(modalId);
    if (!el) {
        console.error('[OpenShogun Back-End] Élément modal #' + modalId + ' introuvable.');
        return;
    }
    const bsModal = getBootstrapModalInstance(el);
    if (bsModal) {
        bsModal.show();
    } else {
        // En cas de chargement asynchrone différé de Bootstrap
        setTimeout(() => {
            const retryModal = getBootstrapModalInstance(el);
            if (retryModal) {
                retryModal.show();
            } else {
                el.classList.add('show');
                el.style.display = 'block';
            }
        }, 150);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONS DE GESTION DES MODALES CRÉATION & MODIFICATION DE TÂCHE
// (DÉCLARÉES IMMÉDIATEMENT POUR ÊTRE DISPONIBLES DANS TOUT LE DOM)
// ═══════════════════════════════════════════════════════════════════════════
function openCreateTaskModal() {
    try {
        const actionInput = document.getElementById('taskFormAction');
        const idInput = document.getElementById('taskFormId');
        const titleModal = document.getElementById('modalTaskFormTitle');
        const submitText = document.getElementById('btnSubmitTaskFormText');

        if (actionInput) actionInput.value = 'create_task';
        if (idInput) idInput.value = '0';
        if (titleModal) titleModal.innerHTML = '<i class="fa-solid fa-server text-teal"></i> <span>Nouvelle Tâche Back-End</span>';
        if (submitText) submitText.textContent = 'Créer la Tâche';

        const titleInput = document.getElementById('taskFormTitleInput');
        const statusSelect = document.getElementById('taskFormStatusSelect');
        const prioritySelect = document.getElementById('taskFormPrioritySelect');
        const complexitySelect = document.getElementById('taskFormComplexitySelect');
        const assignedInput = document.getElementById('taskFormAssignedInput');
        const hoursInput = document.getElementById('taskFormHoursInput');
        const deliverablesInput = document.getElementById('taskFormDeliverablesInput');
        const descTextarea = document.getElementById('taskFormDescTextarea');

        if (titleInput) titleInput.value = '';
        if (statusSelect) statusSelect.value = 'todo';
        if (prioritySelect) prioritySelect.value = 'medium';
        if (complexitySelect) complexitySelect.value = 'M';
        if (assignedInput) assignedInput.value = '';
        if (hoursInput) hoursInput.value = '';
        if (deliverablesInput) deliverablesInput.value = '';
        if (descTextarea) descTextarea.value = '';

        const badge = document.getElementById('taskFormMdImportBadge');
        if (badge) badge.style.display = 'none';

        onTaskFormDescInput();
        switchTaskDescMode('edit');

        showModalSafely('modalBackendTaskForm');
    } catch (e) {
        console.error('[OpenShogun Back-End] Erreur openCreateTaskModal:', e);
    }
}

function openEditTaskModal(taskId) {
    try {
        const task = PRELOADED_BACKEND_TASKS.find(t => parseInt(t.id, 10) === parseInt(taskId, 10));
        if (!task) return;

        const actionInput = document.getElementById('taskFormAction');
        const idInput = document.getElementById('taskFormId');
        const titleModal = document.getElementById('modalTaskFormTitle');
        const submitText = document.getElementById('btnSubmitTaskFormText');

        if (actionInput) actionInput.value = 'update_task';
        if (idInput) idInput.value = task.id;
        if (titleModal) titleModal.innerHTML = `<i class="fa-solid fa-pen-to-square text-warning"></i> <span>Modifier la Tâche #${task.id}</span>`;
        if (submitText) submitText.textContent = 'Enregistrer les Modifications';

        const titleInput = document.getElementById('taskFormTitleInput');
        const statusSelect = document.getElementById('taskFormStatusSelect');
        const prioritySelect = document.getElementById('taskFormPrioritySelect');
        const complexitySelect = document.getElementById('taskFormComplexitySelect');
        const assignedInput = document.getElementById('taskFormAssignedInput');
        const hoursInput = document.getElementById('taskFormHoursInput');
        const deliverablesInput = document.getElementById('taskFormDeliverablesInput');
        const descTextarea = document.getElementById('taskFormDescTextarea');

        if (titleInput) titleInput.value = task.title || '';
        if (statusSelect) statusSelect.value = task.status || 'todo';
        if (prioritySelect) prioritySelect.value = task.priority || 'medium';
        if (complexitySelect) complexitySelect.value = task.complexity || 'M';
        if (assignedInput) assignedInput.value = task.assigned_to || '';
        if (hoursInput) hoursInput.value = task.estimated_hours || '';
        if (deliverablesInput) deliverablesInput.value = task.deliverables || '';
        if (descTextarea) descTextarea.value = task.description || '';

        const badge = document.getElementById('taskFormMdImportBadge');
        if (badge) badge.style.display = 'none';

        onTaskFormDescInput();
        switchTaskDescMode('edit');

        showModalSafely('modalBackendTaskForm');
    } catch (e) {
        console.error('[OpenShogun Back-End] Erreur openEditTaskModal:', e);
    }
}

function confirmDeleteTask(taskId, taskTitle) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer définitivement la tâche #${taskId} « ${taskTitle} » ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=gamestudio&tab=dev_backend_tasks';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_task">
            <input type="hidden" name="task_id" value="${taskId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// GESTION DU CHAMP DESCRIPTION : SWITCH MODE ÉDITION VS APERÇU .MD
// ═══════════════════════════════════════════════════════════════════════════
function switchTaskDescMode(mode) {
    const editArea = document.getElementById('taskFormEditArea');
    const previewArea = document.getElementById('taskFormPreviewArea');
    const btnEdit = document.getElementById('btnTabTaskEdit');
    const btnPreview = document.getElementById('btnTabTaskPreview');
    const ta = document.getElementById('taskFormDescTextarea');
    const previewContent = document.getElementById('taskFormPreviewContent');

    if (!editArea || !previewArea || !btnEdit || !btnPreview || !ta || !previewContent) return;

    if (mode === 'preview') {
        editArea.style.display = 'none';
        previewArea.style.display = 'block';
        btnEdit.classList.remove('btn-primary');
        btnEdit.classList.add('btn-outline-secondary');
        btnPreview.classList.remove('btn-outline-secondary');
        btnPreview.classList.add('btn-primary');

        previewContent.innerHTML = renderSimpleMarkdown(ta.value);
        renderAllMermaidInContainer(previewContent);
    } else {
        previewArea.style.display = 'none';
        editArea.style.display = 'block';
        btnPreview.classList.remove('btn-primary');
        btnPreview.classList.add('btn-outline-secondary');
        btnEdit.classList.remove('btn-outline-secondary');
        btnEdit.classList.add('btn-primary');
        ta.focus();
    }
}

function onTaskFormDescInput() {
    const ta = document.getElementById('taskFormDescTextarea');
    const countEl = document.getElementById('taskFormDescCharCount');
    if (ta && countEl) {
        countEl.textContent = `${ta.value.length} caractère(s)`;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// IMPORTATION DE FICHIER .MD DANS LA DESCRIPTION (BOUTON & DRAG & DROP)
// ═══════════════════════════════════════════════════════════════════════════
function triggerTaskFormMdFileInput() {
    const inp = document.getElementById('taskFormMdFileInput');
    if (inp) {
        inp.value = '';
        inp.click();
    }
}

function onTaskFormMdFileSelected(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    loadMdFileContent(file);
}

function onTaskFormDescDragOver(e) {
    e.preventDefault();
    const ta = document.getElementById('taskFormDescTextarea');
    if (ta) {
        ta.style.borderColor = '#7950f2';
        ta.style.backgroundColor = '#faf8ff';
    }
}

function onTaskFormDescDragLeave(e) {
    const ta = document.getElementById('taskFormDescTextarea');
    if (ta) {
        ta.style.borderColor = '';
        ta.style.backgroundColor = '';
    }
}

function onTaskFormDescDrop(e) {
    e.preventDefault();
    onTaskFormDescDragLeave(e);
    const files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;
    loadMdFileContent(files[0]);
}

function loadMdFileContent(file) {
    const reader = new FileReader();
    reader.onload = function(evt) {
        const content = evt.target.result || '';
        const ta = document.getElementById('taskFormDescTextarea');
        if (ta) {
            ta.value = content;
            onTaskFormDescInput();
            const badge = document.getElementById('taskFormMdImportBadge');
            if (badge) {
                badge.style.display = 'inline-block';
                badge.textContent = `📄 ${file.name} (${content.length} car.) importé`;
            }
            showToastNotification(`Fichier « ${file.name} » importé avec succès !`, 'success');
        }
    };
    reader.readAsText(file, 'UTF-8');
}

// ═══════════════════════════════════════════════════════════════════════════
// MODALE D'APERÇU PLEIN ÉCRAN .MD & FLOWCHARTS
// ═══════════════════════════════════════════════════════════════════════════
function openTaskQuickPreview(taskId) {
    const task = PRELOADED_BACKEND_TASKS.find(t => parseInt(t.id, 10) === parseInt(taskId, 10));
    if (!task) return;

    activePreviewTaskId = task.id;

    const titleEl = document.getElementById('quickPreviewTaskTitle');
    const subEl = document.getElementById('quickPreviewTaskSub');
    const bodyEl = document.getElementById('quickPreviewTaskBody');
    const badgesEl = document.getElementById('quickPreviewMetaBadges');

    if (titleEl) titleEl.textContent = `Tâche #${task.id} : ${task.title}`;
    if (subEl) {
        subEl.textContent = `Assigné à ${task.assigned_to || 'Back-End'} • Estimation : ${task.estimated_hours || 0}h • Créée le ${task.created_at || ''}`;
    }

    let metaBadgesHtml = '';
    const prioLabels = { low: 'Basse', medium: 'Moyenne', high: 'Haute', critical: 'Critique' };
    metaBadgesHtml += `<span class="badge bg-dark-lt text-dark">#${task.id}</span>`;
    metaBadgesHtml += `<span class="badge bg-secondary-lt text-secondary">Statut: ${task.status}</span>`;
    metaBadgesHtml += `<span class="badge bg-warning-lt text-warning">Priorité: ${prioLabels[task.priority] || task.priority}</span>`;
    metaBadgesHtml += `<span class="badge bg-teal-lt text-teal">Taille: ${task.complexity}</span>`;
    if (task.scenario_id) {
        metaBadgesHtml += `<span class="badge bg-purple-lt text-purple"><i class="fa-solid fa-scroll me-1"></i>Scénario #${task.scenario_id}</span>`;
    }
    if (badgesEl) badgesEl.innerHTML = metaBadgesHtml;

    let contentHtml = '';
    if (task.deliverables) {
        contentHtml += `
            <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
                <i class="fa-solid fa-box-archive text-info fs-4"></i>
                <div>
                    <strong>Livrables Attendus :</strong> <code>${escapeHtmlStr(task.deliverables)}</code>
                </div>
            </div>
        `;
    }

    contentHtml += renderCustomMarkdown(task.description || '', { compact: false });
    if (bodyEl) {
        bodyEl.innerHTML = contentHtml;
        renderAllMermaidInContainer(bodyEl);
    }

    showModalSafely('modalTaskQuickPreview');
}

function editFromQuickPreview() {
    if (!activePreviewTaskId) return;
    const modalEl = document.getElementById('modalTaskQuickPreview');
    const prevModal = getBootstrapModalInstance(modalEl);
    if (prevModal) prevModal.hide();
    openEditTaskModal(activePreviewTaskId);
}

// ═══════════════════════════════════════════════════════════════════════════
// KANBAN DRAG & DROP & MISE À JOUR AJAX DU STATUT
// ═══════════════════════════════════════════════════════════════════════════
let draggedTaskId = null;

function onKanbanDragStart(e, taskId) {
    draggedTaskId = taskId;
    e.dataTransfer.setData('text/plain', taskId);
    e.dataTransfer.effectAllowed = 'move';
    const card = document.getElementById('task-card-' + taskId);
    if (card) card.classList.add('opacity-50', 'border-primary');
}

function onKanbanDragEnd(e) {
    if (draggedTaskId) {
        const card = document.getElementById('task-card-' + draggedTaskId);
        if (card) card.classList.remove('opacity-50', 'border-primary');
    }
    document.querySelectorAll('.kanban-column').forEach(col => {
        col.classList.remove('bg-primary-subtle', 'border-primary');
    });
}

function onKanbanDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const col = e.currentTarget;
    col.classList.add('bg-primary-subtle', 'border-primary');
}

function onKanbanDragLeave(e) {
    const col = e.currentTarget;
    col.classList.remove('bg-primary-subtle', 'border-primary');
}

function onKanbanDrop(e, targetStatus) {
    e.preventDefault();
    const col = e.currentTarget;
    col.classList.remove('bg-primary-subtle', 'border-primary');

    const taskId = draggedTaskId || parseInt(e.dataTransfer.getData('text/plain'), 10);
    if (!taskId) return;

    const task = PRELOADED_BACKEND_TASKS.find(t => parseInt(t.id, 10) === parseInt(taskId, 10));
    if (!task) return;

    if (task.status === targetStatus) return; // Rien à changer

    const cardEl = document.getElementById('task-card-' + taskId);
    const cardList = col.querySelector('.kanban-card-list');
    const placeholder = col.querySelector('.kanban-empty-placeholder');

    if (cardEl && cardList) {
        if (placeholder) placeholder.style.display = 'none';
        cardList.appendChild(cardEl);
        task.status = targetStatus;
    }

    // Appel AJAX
    const formData = new FormData();
    formData.append('action', 'update_status_ajax');
    formData.append('task_id', taskId);
    formData.append('status', targetStatus);

    fetch('?page=gamestudio&tab=dev_backend_tasks', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateKanbanCounters();
            showToastNotification(`Tâche #${taskId} déplacée vers « ${targetStatus} »`, 'success');
        } else {
            showToastNotification(`Erreur lors du déplacement de la tâche #${taskId}`, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToastNotification(`Erreur réseau lors de la mise à jour du statut`, 'danger');
    });
}

function updateKanbanCounters() {
    ['todo', 'in_progress', 'review', 'blocked', 'done'].forEach(st => {
        const countBadge = document.getElementById('count-' + st);
        const col = document.querySelector(`.kanban-column[data-status="${st}"]`);
        if (countBadge && col) {
            const cards = col.querySelectorAll('.kanban-task-card');
            countBadge.textContent = cards.length;
            const placeholder = col.querySelector('.kanban-empty-placeholder');
            if (placeholder) {
                placeholder.style.display = cards.length === 0 ? 'block' : 'none';
            }
        }
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// SYNCHRONISATION 1-CLIC DES SCÉNARIOS NARRATIFS (AJAX)
// ═══════════════════════════════════════════════════════════════════════════
function triggerScenarioSync() {
    const btn = document.getElementById('btnSyncScenarios');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Synchronisation...`;
    }

    const formData = new FormData();
    formData.append('action', 'sync_scenarios_ajax');

    fetch('?page=gamestudio&tab=dev_backend_tasks', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        if (data.success && data.result) {
            const res = data.result;
            showToastNotification(`Synchronisation réussie : ${res.imported} importée(s), ${res.updated} actualisée(s) sur ${res.total} tâche(s) de scénario trouvée(s).`, 'success');
            setTimeout(() => { window.location.reload(); }, 1200);
        } else {
            showToastNotification(`Erreur lors de la synchronisation des scénarios.`, 'danger');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        console.error(err);
        showToastNotification(`Erreur de communication serveur.`, 'danger');
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// TOAST NOTIFICATIONS TABLER
// ═══════════════════════════════════════════════════════════════════════════
function showToastNotification(message, type = 'info') {
    const container = document.getElementById('tablerToastContainer');
    if (!container) {
        alert(message);
        return;
    }
    const toastId = 'toast_' + Date.now();
    const bgCol = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-primary text-white');
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'danger' ? 'fa-triangle-exclamation' : 'fa-info-circle');

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgCol} border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fa-solid ${icon} fs-4"></i>
                    <div style="font-size:0.85rem;">${escapeHtmlStr(message)}</div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    if (toastEl && window.bootstrap && window.bootstrap.Toast) {
        const bsToast = new window.bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// MOTEUR MERMAID.JS v10 & DIAGRAMMES DE FLUX
// ═══════════════════════════════════════════════════════════════════════════
let MERMAID_INITIALIZED = false;
let mermaidRenderCounter = 0;

function initMermaidEngine() {
    if (MERMAID_INITIALIZED) return true;
    if (typeof mermaid !== 'undefined' && mermaid.initialize) {
        try {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'neutral',
                fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
                flowchart: {
                    useMaxWidth: true,
                    htmlLabels: true,
                    curve: 'basis'
                },
                themeVariables: {
                    primaryColor: '#f3e8ff',
                    primaryTextColor: '#581c87',
                    primaryBorderColor: '#7c3aed',
                    lineColor: '#6d28d9',
                    secondaryColor: '#ede9fe',
                    tertiaryColor: '#ffffff'
                },
                securityLevel: 'loose'
            });
            MERMAID_INITIALIZED = true;
            return true;
        } catch (e) {
            console.warn('[OpenShogun Back-End] Erreur initialisation Mermaid:', e);
        }
    }
    return false;
}

function buildFallbackFlowchartHtml(code, errorNotice = null) {
    if (!code) return '';
    const cleanLines = code.split('\n')
        .map(l => l.trim())
        .filter(l => l && !l.startsWith('%%') && !/^flowchart\b/i.test(l) && !/^graph\b/i.test(l));

    const steps = [];
    const relations = [];

    cleanLines.forEach((line) => {
        if (line.includes('-->') || line.includes('-.->') || line.includes('==>')) {
            const arrowRegex = /-->|-\.->|==>/;
            const parts = line.split(arrowRegex).map(p => p.trim());
            if (parts.length >= 2) {
                const parseNode = (raw) => {
                    const m = raw.match(/^([a-zA-Z0-9_]+)\s*[\[\(\{]["']?([^\]\)\}]+)["']?[\]\)\}]/);
                    if (m) return { id: m[1], label: m[2].replace(/\\n/g, ' ') };
                    return { id: raw, label: raw };
                };
                const rawSources = parts[0].split('&').map(s => s.trim());
                const targetNode = parseNode(parts[1]);
                if (!steps.find(s => s.id === targetNode.id)) steps.push(targetNode);
                rawSources.forEach(rs => {
                    const srcNode = parseNode(rs);
                    if (!steps.find(s => s.id === srcNode.id)) steps.push(srcNode);
                    relations.push({ from: srcNode.id, to: targetNode.id });
                });
            }
        } else {
            const m = line.match(/^([a-zA-Z0-9_]+)\s*[\[\(\{]["']?([^\]\)\}]+)["']?[\]\)\}]/);
            if (m && !steps.find(s => s.id === m[1])) {
                steps.push({ id: m[1], label: m[2].replace(/\\n/g, ' ') });
            }
        }
    });

    if (steps.length === 0) {
        return `<div class="p-3 bg-light rounded text-muted font-monospace small"><pre class="m-0">${escapeHtmlStr(code)}</pre></div>`;
    }

    let outHtml = `<div class="flowchart-fallback-diagram py-2">`;
    outHtml += `<div class="d-flex flex-wrap align-items-center justify-content-center gap-3">`;
    steps.forEach((st, idx) => {
        outHtml += `
            <div class="card border border-purple shadow-sm px-3 py-2 text-start" style="min-width: 170px; max-width: 250px; background: linear-gradient(180deg, #ffffff 0%, #fbf9fe 100%);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="badge bg-purple text-white rounded-pill" style="font-size:0.65rem;">Étape #${idx + 1}</span>
                    <span class="fw-bold text-purple font-monospace" style="font-size:0.75rem;">${escapeHtmlStr(st.id)}</span>
                </div>
                <div class="small text-dark fw-medium" style="font-size:0.8rem; line-height:1.25;">${escapeHtmlStr(st.label)}</div>
            </div>
        `;
        if (idx < steps.length - 1) {
            outHtml += `<div class="text-purple fs-4 px-1 d-flex align-items-center justify-content-center"><i class="fa-solid fa-arrow-right"></i></div>`;
        }
    });
    outHtml += `</div>`;
    if (errorNotice) {
        outHtml += `<div class="text-secondary small mt-2" style="font-size:0.7rem;"><i class="fa-solid fa-info-circle me-1"></i>Diagramme de secours (${escapeHtmlStr(errorNotice)})</div>`;
    }
    outHtml += `</div>`;
    return outHtml;
}

function renderAllMermaidInContainer(container) {
    if (!container) return;
    const diagramEls = container.querySelectorAll('.mermaid-diagram');
    if (!diagramEls || diagramEls.length === 0) return;

    initMermaidEngine();

    diagramEls.forEach((el) => {
        const code = el.getAttribute('data-mermaid-code');
        if (!code || el.querySelector('svg')) return;

        if (typeof mermaid !== 'undefined' && mermaid.render) {
            const uniqueId = 'os_mermaid_be_' + Date.now() + '_' + (++mermaidRenderCounter);
            try {
                mermaid.render(uniqueId, code).then((res) => {
                    el.innerHTML = res.svg;
                    if (res.bindFunctions) res.bindFunctions(el);
                }).catch((err) => {
                    el.innerHTML = buildFallbackFlowchartHtml(code, err && err.message ? err.message : 'Erreur syntaxe');
                });
            } catch (errSync) {
                el.innerHTML = buildFallbackFlowchartHtml(code, errSync.message);
            }
        } else {
            el.innerHTML = buildFallbackFlowchartHtml(code);
        }
    });
}

function toggleMermaidSource(btn) {
    const container = btn.closest('.mermaid-diagram-container');
    if (!container) return;
    const src = container.querySelector('.mermaid-source-code');
    if (!src) return;
    if (src.style.display === 'none' || !src.style.display) {
        src.style.display = 'block';
        btn.classList.add('active', 'btn-secondary');
        btn.classList.remove('btn-outline-secondary');
    } else {
        src.style.display = 'none';
        btn.classList.remove('active', 'btn-secondary');
        btn.classList.add('btn-outline-secondary');
    }
}

function copyMermaidCode(btn) {
    const container = btn.closest('.mermaid-diagram-container');
    if (!container) return;
    const diag = container.querySelector('.mermaid-diagram');
    const code = diag ? diag.getAttribute('data-mermaid-code') : '';
    if (code) {
        navigator.clipboard.writeText(code).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-success"></i> Copié !';
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        }).catch(() => {});
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// PARSEUR MARKDOWN COMPLET AVEC TABLES, BADGES & FLOWCHARTS
// ═══════════════════════════════════════════════════════════════════════════
function escapeHtmlStr(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatInlineMarkdown(text) {
    let html = escapeHtmlStr(text);

    html = html.replace(/`([^`]+)`/g, function(_, codeContent) {
        if (/^[█░]+$/.test(codeContent)) {
            const total = codeContent.length || 10;
            const filled = (codeContent.match(/█/g) || []).length;
            const pct = Math.round((filled / total) * 100);
            const barCol = pct >= 100 ? '#10b981' : (pct >= 50 ? '#0ca678' : (pct > 0 ? '#3b82f6' : '#94a3b8'));
            return `<span class="d-inline-flex align-items-center gap-2 align-middle" style="min-width:110px;">
                <span class="progress flex-grow-1" style="height:8px; width:85px; background:#e2e8f0;">
                    <span class="progress-bar" style="width:${pct}%; background-color:${barCol};"></span>
                </span>
            </span>`;
        }
        if (/^(S|M|L|XL)$/.test(codeContent)) {
            const colMap = { S: 'teal', M: 'azure', L: 'warning', XL: 'danger' };
            return `<span class="badge bg-${colMap[codeContent] || 'secondary'}-lt text-${colMap[codeContent] || 'secondary'} fw-bold font-monospace">${codeContent}</span>`;
        }
        return `<code>${codeContent}</code>`;
    });

    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');

    html = html.replace(/✅ Terminé/g, '<span class="badge bg-success text-white">✅ Terminé</span>');
    html = html.replace(/🔧 En cours/g, '<span class="badge bg-primary text-white">🔧 En cours</span>');
    html = html.replace(/⛔ Bloqué/g, '<span class="badge bg-danger text-white">⛔ Bloqué</span>');
    html = html.replace(/⏳ À faire/g, '<span class="badge bg-secondary-lt text-secondary">⏳ À faire</span>');

    return html;
}

function renderCustomMarkdown(mdText, options = {}) {
    if (!mdText || !mdText.trim()) {
        return '<em class="text-muted">Aucun cahier des charges Markdown rédigé pour le moment.</em>';
    }

    const codeBlocks = [];
    let textWithoutBlocks = mdText.replace(/```([a-zA-Z0-9_-]*)[ \t]*\r?\n([\s\S]*?)```/g, function(match, lang, code) {
        const langLower = (lang || '').trim().toLowerCase();
        let codeTrimmed = code.trim();
        const placeholder = `@@CODE_BLOCK_TOKEN_${codeBlocks.length}@@`;

        const isMermaid = (
            langLower === 'mermaid' ||
            langLower === 'flowchart' ||
            langLower === 'graph' ||
            /^(flowchart|graph|sequenceDiagram|classDiagram|stateDiagram|erDiagram|gantt|pie)\b/m.test(codeTrimmed)
        );

        if ((langLower === 'flowchart' || langLower === 'graph') && !/^(flowchart|graph)\b/m.test(codeTrimmed)) {
            codeTrimmed = langLower + ' TD\n' + codeTrimmed;
        }

        codeBlocks.push({
            isMermaid: isMermaid,
            lang: langLower || (isMermaid ? 'mermaid' : 'text'),
            code: codeTrimmed
        });

        return '\n\n' + placeholder + '\n\n';
    });

    const lines = textWithoutBlocks.replace(/\r\n/g, '\n').split('\n');
    let htmlOut = '';
    let inList = false;
    let listType = 'ul';
    let inBlockquote = false;
    let blockquoteLines = [];
    let inTable = false;
    let tableRows = [];

    function flushList() {
        if (inList) {
            htmlOut += (listType === 'ol' ? '</ol>' : '</ul>');
            inList = false;
        }
    }

    function flushBlockquote() {
        if (inBlockquote) {
            htmlOut += '<blockquote class="border-start border-3 border-purple ps-3 my-2 text-secondary fst-italic bg-light-subtle py-1 rounded-end">' + blockquoteLines.join('<br>') + '</blockquote>';
            inBlockquote = false;
            blockquoteLines = [];
        }
    }

    function flushTable() {
        if (!inTable || tableRows.length === 0) return;
        let tHtml = '<div class="table-responsive my-2"><table class="table table-sm table-bordered table-hover align-middle mb-0 bg-white">';
        tableRows.forEach((rowCells, rIdx) => {
            if (rIdx === 0) {
                tHtml += '<thead class="table-light"><tr>';
                rowCells.forEach(c => { tHtml += `<th class="py-1 px-2 text-dark">${formatInlineMarkdown(c)}</th>`; });
                tHtml += '</tr></thead><tbody>';
            } else if (rIdx === 1 && rowCells.every(c => /^[-:\s]+$/.test(c))) {
                // séparateur
            } else {
                tHtml += '<tr>';
                rowCells.forEach(c => { tHtml += `<td class="py-1 px-2">${formatInlineMarkdown(c)}</td>`; });
                tHtml += '</tr>';
            }
        });
        tHtml += '</tbody></table></div>';
        htmlOut += tHtml;
        inTable = false;
        tableRows = [];
    }

    for (let i = 0; i < lines.length; i++) {
        const rawLine = lines[i];
        const trimmed = rawLine.trim();

        const codeTokenMatch = trimmed.match(/^@@CODE_BLOCK_TOKEN_(\d+)@@$/);
        if (codeTokenMatch) {
            flushList();
            flushBlockquote();
            flushTable();
            const idx = parseInt(codeTokenMatch[1], 10);
            const blk = codeBlocks[idx];
            if (blk) {
                if (blk.isMermaid) {
                    htmlOut += `
                    <div class="mermaid-diagram-container my-3 rounded border bg-white shadow-sm overflow-hidden text-start">
                        <div class="d-flex justify-content-between align-items-center bg-light px-3 py-1-5 border-bottom">
                            <span class="small fw-bold text-purple d-flex align-items-center gap-2">
                                <i class="fa-solid fa-diagram-project"></i>
                                <span>Diagramme de Flux Back-End (Mermaid)</span>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:0.7rem; height:24px;" onclick="toggleMermaidSource(this)" title="Afficher/masquer code">
                                    <i class="fa-solid fa-code me-1"></i>Code
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:0.7rem; height:24px;" onclick="copyMermaidCode(this)" title="Copier code">
                                    <i class="fa-solid fa-copy me-1"></i>Copier
                                </button>
                            </div>
                        </div>
                        <div class="mermaid-diagram-body p-3 text-center" style="overflow-x:auto; background:#ffffff; min-height:80px;">
                            <div class="mermaid-diagram" data-mermaid-code="${escapeHtmlStr(blk.code)}">
                                <div class="d-flex align-items-center justify-content-center gap-2 text-muted py-3">
                                    <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                                    <span class="small">Rendu du diagramme...</span>
                                </div>
                            </div>
                        </div>
                        <div class="mermaid-source-code p-2 bg-dark text-white font-monospace small" style="display:none; max-height:220px; overflow-y:auto; font-size:0.75rem;">
                            <pre class="m-0 text-light p-1"><code>${escapeHtmlStr(blk.code)}</code></pre>
                        </div>
                    </div>`;
                } else {
                    htmlOut += `
                    <div class="code-block my-2 rounded border overflow-hidden">
                        <div class="bg-dark text-secondary px-2 py-1 small font-monospace d-flex justify-content-between align-items-center border-bottom border-secondary" style="font-size:0.7rem;">
                            <span>${escapeHtmlStr(blk.lang || 'code')}</span>
                            <button type="button" class="btn btn-link btn-sm text-secondary p-0 text-decoration-none" style="font-size:0.68rem;" onclick="navigator.clipboard.writeText(this.closest('.code-block').querySelector('code').textContent)">Copier</button>
                        </div>
                        <pre class="bg-dark text-light p-2 mb-0 font-monospace small" style="max-height:280px; overflow-y:auto;"><code>${escapeHtmlStr(blk.code)}</code></pre>
                    </div>`;
                }
            }
            continue;
        }

        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            flushList();
            flushBlockquote();
            inTable = true;
            const cells = trimmed.slice(1, -1).split('|').map(c => c.trim());
            tableRows.push(cells);
            continue;
        } else if (inTable) {
            flushTable();
        }

        if (trimmed.startsWith('>')) {
            flushList();
            inBlockquote = true;
            blockquoteLines.push(formatInlineMarkdown(trimmed.replace(/^>\s?/, '')));
            continue;
        } else if (inBlockquote) {
            flushBlockquote();
        }

        if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
            if (!inList || listType !== 'ul') {
                flushList();
                htmlOut += '<ul class="mb-2 ps-3">';
                inList = true;
                listType = 'ul';
            }
            htmlOut += `<li class="mb-1">${formatInlineMarkdown(trimmed.slice(2))}</li>`;
            continue;
        }

        const numListMatch = trimmed.match(/^(\d+)\.\s+(.+)$/);
        if (numListMatch) {
            if (!inList || listType !== 'ol') {
                flushList();
                htmlOut += '<ol class="mb-2 ps-3">';
                inList = true;
                listType = 'ol';
            }
            htmlOut += `<li class="mb-1">${formatInlineMarkdown(numListMatch[2])}</li>`;
            continue;
        }

        if (inList) flushList();
        if (trimmed === '') continue;
        if (trimmed === '---') { htmlOut += '<hr class="my-3">'; continue; }

        if (trimmed.startsWith('# ')) {
            htmlOut += `<h4 class="fw-bold text-dark mt-3 mb-2 border-bottom pb-1">${formatInlineMarkdown(trimmed.slice(2))}</h4>`;
            continue;
        }
        if (trimmed.startsWith('## ')) {
            htmlOut += `<h5 class="fw-bold text-purple mt-3 mb-1 border-bottom pb-1">${formatInlineMarkdown(trimmed.slice(3))}</h5>`;
            continue;
        }
        if (trimmed.startsWith('### ')) {
            htmlOut += `<h6 class="fw-bold text-dark mt-2 mb-1">${formatInlineMarkdown(trimmed.slice(4))}</h6>`;
            continue;
        }

        htmlOut += `<p class="mb-2">${formatInlineMarkdown(trimmed)}</p>`;
    }

    flushList();
    flushBlockquote();
    flushTable();

    return htmlOut;
}

function renderSimpleMarkdown(md) {
    return renderCustomMarkdown(md, { compact: true });
}

// ═══════════════════════════════════════════════════════════════════════════
// EXPORTATION EXPLICITE SUR WINDOW & INITIALISATION AU CHARGEMENT DU DOM
// ═══════════════════════════════════════════════════════════════════════════
window.openCreateTaskModal = openCreateTaskModal;
window.openEditTaskModal = openEditTaskModal;
window.confirmDeleteTask = confirmDeleteTask;
window.openTaskQuickPreview = openTaskQuickPreview;
window.editFromQuickPreview = editFromQuickPreview;
window.switchTaskDescMode = switchTaskDescMode;
window.triggerTaskFormMdFileInput = triggerTaskFormMdFileInput;
window.onTaskFormMdFileSelected = onTaskFormMdFileSelected;
window.onTaskFormDescDragOver = onTaskFormDescDragOver;
window.onTaskFormDescDragLeave = onTaskFormDescDragLeave;
window.onTaskFormDescDrop = onTaskFormDescDrop;
window.onTaskFormDescInput = onTaskFormDescInput;
window.onKanbanDragStart = onKanbanDragStart;
window.onKanbanDragEnd = onKanbanDragEnd;
window.onKanbanDragOver = onKanbanDragOver;
window.onKanbanDragLeave = onKanbanDragLeave;
window.onKanbanDrop = onKanbanDrop;
window.triggerScenarioSync = triggerScenarioSync;
window.showToastNotification = showToastNotification;
window.initMermaidEngine = initMermaidEngine;
window.renderAllMermaidInContainer = renderAllMermaidInContainer;
window.toggleMermaidSource = toggleMermaidSource;
window.copyMermaidCode = copyMermaidCode;
window.renderCustomMarkdown = renderCustomMarkdown;
window.renderSimpleMarkdown = renderSimpleMarkdown;

document.addEventListener('DOMContentLoaded', function() {
    const btnCreate = document.getElementById('btnOpenCreateTaskModal');
    if (btnCreate) {
        btnCreate.addEventListener('click', function(e) {
            e.preventDefault();
            openCreateTaskModal();
        });
    }
    initMermaidEngine();
});
</script>

