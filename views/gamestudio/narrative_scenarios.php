<?php
/**
 * Game Studio - Narrative Designer : Scénarios de Jeu & Matrice des Tâches Multiples par Métier
 * Support du Mode Catalogue & de l'Éditeur Pleine Page (Full Page Studio)
 * Fichier : views/gamestudio/narrative_scenarios.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/ScenarioEngine.php';
$scenarioEngine = new ScenarioEngine();

$rolesMap = ScenarioEngine::ROLES;
$viewAction = (string)($_GET['action'] ?? 'catalog');

// Helper PHP pour rendre une carte de tâche
if (!function_exists('renderTaskCardHtml')) {
    function renderTaskCardHtml(array $t, array $rDef): void {
        $taskId = htmlspecialchars((string)($t['task_id'] ?? rand(100, 9999)));
        $roleCode = htmlspecialchars((string)($t['role_code'] ?? 'level_designer'));
        $roleName = htmlspecialchars((string)($t['role_name'] ?? $rDef['name']));
        $title = htmlspecialchars((string)($t['title'] ?? ''));
        $description = htmlspecialchars((string)($t['description'] ?? ''));
        $assignee = htmlspecialchars((string)($t['assignee'] ?? $rDef['name']));
        $complexity = htmlspecialchars((string)($t['complexity'] ?? 'M'));
        $status = htmlspecialchars((string)($t['status'] ?? 'todo'));
        $deliverables = htmlspecialchars((string)($t['deliverables'] ?? ''));
        ?>
        <div class="card border p-3 mb-3 bg-light shadow-none task-card-item" id="taskCard_<?= $taskId ?>" data-role="<?= $roleCode ?>">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?= $rDef['color'] ?> text-white fw-bold">
                        #<?= $taskId ?>
                    </span>
                    <span class="fw-bold text-dark"><?= $roleName ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-bold">Complexité :</label>
                    <select class="form-select form-select-sm" name="tasks[<?= $taskId ?>][complexity]" style="width: auto;">
                        <option value="S" <?= ($complexity === 'S') ? 'selected' : '' ?>>S (Court)</option>
                        <option value="M" <?= ($complexity === 'M') ? 'selected' : '' ?>>M (Moyen)</option>
                        <option value="L" <?= ($complexity === 'L') ? 'selected' : '' ?>>L (Important)</option>
                        <option value="XL" <?= ($complexity === 'XL') ? 'selected' : '' ?>>XL (Majeur)</option>
                    </select>

                    <label class="small text-muted fw-bold ms-2">Statut :</label>
                    <select class="form-select form-select-sm fw-bold task-status-select" name="tasks[<?= $taskId ?>][status]" style="width: auto;" onchange="updateRoleTelemetry('<?= $roleCode ?>')">
                        <option value="todo" <?= ($status === 'todo') ? 'selected' : '' ?>>⏳ À faire</option>
                        <option value="in_progress" <?= ($status === 'in_progress') ? 'selected' : '' ?>>🔧 En cours</option>
                        <option value="done" <?= ($status === 'done') ? 'selected' : '' ?>>✅ Terminé</option>
                        <option value="blocked" <?= ($status === 'blocked') ? 'selected' : '' ?>>⛔ Bloqué</option>
                    </select>

                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 ms-2" title="Supprimer cette tâche" onclick="removeTaskCard('<?= $taskId ?>', '<?= $roleCode ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <input type="hidden" name="tasks[<?= $taskId ?>][task_id]" value="<?= $taskId ?>">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_code]" value="<?= $roleCode ?>">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_name]" value="<?= $roleName ?>">

            <div class="row g-2">
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-dark m-0">Titre Opérationnel de la Tâche :</label>
                    <input type="text" class="form-control form-control-sm" name="tasks[<?= $taskId ?>][title]" value="<?= $title ?>" required placeholder="Ex: Moteur de trigger, Portrait 2D, Intégration UI...">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-dark m-0">Artisan / Développeur Assigné :</label>
                    <input type="text" class="form-control form-control-sm" name="tasks[<?= $taskId ?>][assignee]" value="<?= $assignee ?>">
                </div>
                <div class="col-12 mt-2">
                    <label class="form-label small fw-bold text-dark m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                    <textarea class="form-control form-control-sm" name="tasks[<?= $taskId ?>][description]" rows="2" placeholder="Détaillez le travail attendu pour ce métier..."><?= $description ?></textarea>
                </div>
                <div class="col-12 mt-1">
                    <label class="form-label small fw-bold text-dark m-0">Livrables / Fichiers Attendus :</label>
                    <input type="text" class="form-control form-control-sm font-monospace" name="tasks[<?= $taskId ?>][deliverables]" value="<?= $deliverables ?>" placeholder="Ex: core/ScenarioEngine.php, public/assets/...">
                </div>
            </div>
        </div>
        <?php
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// MODE 1 : ÉDITEUR PLEINE PAGE STUDIO (action = 'edit' ou 'create')
// ═══════════════════════════════════════════════════════════════════════════
if ($viewAction === 'edit' || $viewAction === 'create'):
    $isCreate = ($viewAction === 'create');
    $scenarioId = (int)($_GET['id'] ?? 0);
    $activeScenario = null;

    if (!$isCreate && $scenarioId > 0) {
        $activeScenario = $scenarioEngine->getScenarioById($scenarioId);
    }

    // Valeurs par défaut si création ou non trouvé
    if (!$activeScenario) {
        $isCreate = true;
        $allSc = $scenarioEngine->getScenarios();
        $stepCount = count(array_filter($allSc, fn($s) => $s['type'] === 'step'));

        $activeScenario = [
            'id'                => 0,
            'slug'              => '',
            'type'              => 'step',
            'step_number'       => $stepCount + 1,
            'title'             => '',
            'subtitle'          => '',
            'era'               => '1560 - Sengoku Jidai',
            'synopsis'          => '',
            'speaker_name'      => 'Maître de la Sagesse (Chie no Sensei)',
            'speaker_role'      => 'Mentor Vénérable & Gardien des Traditions',
            'dialogue'          => '',
            'trigger_condition' => '',
            'rewards_summary'   => '',
            'status'            => 'draft',
            'tasks'             => $scenarioEngine->generateDefaultTasks('step', 'Nouveau Scénario', $stepCount + 1)
        ];
    }

    $activeTasks = $activeScenario['tasks'] ?? [];
    $metrics = $scenarioEngine->calculateTaskMetrics($activeTasks);
    $groupedTasks = $scenarioEngine->getTasksGroupedByRole($activeTasks);
    $statusLabels = [
        'draft'         => '📝 Brouillon',
        'in_review'     => '🔍 En revue narrative',
        'in_production' => '⚙️ En production',
        'ready'         => '✅ Prêt / Validé',
        'published'     => '🚀 En ligne (Live)',
        'archived'      => '📦 Archivé'
    ];
?>

<div class="gamestudio-narrative-editor-fullpage">

    <!-- En-tête Sticky Pleine Page -->
    <div class="card shadow-sm border-0 mb-4 sticky-top bg-white" style="top: 60px; z-index: 1020;">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <!-- Fil d'Ariane & Titre -->
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0 small">
                        <li class="breadcrumb-item">
                            <a href="?page=gamestudio&tab=dashboard" class="text-secondary text-decoration-none">
                                <i class="fa-solid fa-laptop-code me-1"></i>Game Studio
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="?page=gamestudio&tab=narrative_scenarios" class="text-secondary text-decoration-none">
                                Narrative Designer
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-purple fw-bold" aria-current="page">
                            <?= $isCreate ? 'Nouveau Scénario' : 'Édition Pleine Page #' . $activeScenario['id'] ?>
                        </li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <h2 class="h3 m-0 text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-feather-pointed text-purple"></i>
                        <span><?= $isCreate ? 'Création d\'un Nouveau Scénario' : htmlspecialchars($activeScenario['title']) ?></span>
                    </h2>
                    <?php if (!$isCreate): ?>
                        <span class="badge bg-purple-lt text-purple font-monospace">
                            ID #<?= $activeScenario['id'] ?> &bull; <?= htmlspecialchars($activeScenario['slug']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions Rapides Pleine Page -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Catalogue</span>
                </a>

                <?php if (!$isCreate): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" onclick="exportScenarioMarkdown(<?= $activeScenario['id'] ?>)">
                        <i class="fa-solid fa-file-arrow-down text-teal"></i>
                        <span>GDD Markdown</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" onclick="duplicateCurrentScenario(<?= $activeScenario['id'] ?>)">
                        <i class="fa-solid fa-copy text-primary"></i>
                        <span>Dupliquer</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 shadow-sm" onclick="deleteCurrentScenario(<?= $activeScenario['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                        <span>Supprimer</span>
                    </button>
                <?php endif; ?>

                <button type="button" class="btn btn-purple btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1 px-3" onclick="submitFullPageForm('edit')">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Enregistrer</span>
                    <span class="badge bg-purple-dark text-white font-monospace ms-1" style="font-size:0.65rem;">Ctrl+S</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (isset($_SESSION['scenario_flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['scenario_flash']['type']) ?> alert-dismissible mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid <?= $_SESSION['scenario_flash']['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning' ?> fs-3"></i>
                <div><?= htmlspecialchars($_SESSION['scenario_flash']['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['scenario_flash']); ?>
    <?php endif; ?>

    <!-- Formulaire Pleine Page -->
    <form method="POST" action="" id="fullPageScenarioForm">
        <input type="hidden" name="action" value="<?= $isCreate ? 'create_scenario' : 'update_scenario' ?>">
        <input type="hidden" name="scenario_id" value="<?= (int)$activeScenario['id'] ?>">
        <input type="hidden" name="redirect_to" id="formRedirectTo" value="edit">

        <!-- Barre d'onglets de navigation du Studio Pleine Page -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light-subtle p-0 border-bottom">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="fullPageTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold text-dark py-3 px-4 d-inline-flex align-items-center gap-2" id="tab-link-narrative" data-bs-toggle="tab" href="#tab-pane-narrative" role="tab">
                            <i class="fa-solid fa-scroll text-purple fs-4"></i>
                            <span>1. Trame Narrative &amp; Scénario</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark py-3 px-4 d-inline-flex align-items-center gap-2" id="tab-link-tasks" data-bs-toggle="tab" href="#tab-pane-tasks" role="tab">
                            <i class="fa-solid fa-users-gear text-teal fs-4"></i>
                            <span>2. Matrice des Tâches par Métier (Multi-Tâches)</span>
                            <span class="badge bg-teal-lt text-teal rounded-pill font-monospace" id="tasksGlobalBadge">
                                <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $metrics['percent'] ?>%)
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark py-3 px-4 d-inline-flex align-items-center gap-2" id="tab-link-preview" data-bs-toggle="tab" href="#tab-pane-preview" role="tab" onclick="renderInGamePreviewLive()">
                            <i class="fa-solid fa-eye text-warning fs-4"></i>
                            <span>3. Simulateur In-Game &amp; Rendu Joueur</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content">

                    <!-- ───────────────────────────────────────────────────────────── -->
                    <!-- ONGLET 1 : TRAME NARRATIVE & LORE                             -->
                    <!-- ───────────────────────────────────────────────────────────── -->
                    <div class="tab-pane fade show active" id="tab-pane-narrative" role="tabpanel">
                        <div class="row g-4">
                            <!-- Colonne Gauche : Paramètres & Identification -->
                            <div class="col-lg-4">
                                <div class="card bg-light-subtle border p-3 shadow-none h-100">
                                    <h4 class="card-title text-purple fw-bold mb-3 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-sliders"></i>
                                        <span>Paramètres du Scénario</span>
                                    </h4>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">Type de Scénario :</label>
                                        <select class="form-select fw-bold" name="type" id="fullScenType" onchange="toggleStepNumberFieldFullPage()">
                                            <option value="step" <?= ($activeScenario['type'] === 'step') ? 'selected' : '' ?>>⛩️ Étape du Mode de Jeu (Step)</option>
                                            <option value="special_event" <?= ($activeScenario['type'] === 'special_event') ? 'selected' : '' ?>>🏮 Événement Spécial</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="stepNumberGroupFullPage" style="<?= ($activeScenario['type'] === 'step') ? '' : 'display:none;' ?>">
                                        <label class="form-label fw-bold text-dark">Numéro d'Étape (Step #) :</label>
                                        <input type="number" class="form-control fw-bold" name="step_number" id="fullScenStepNumber" value="<?= htmlspecialchars((string)($activeScenario['step_number'] ?? '1')) ?>" min="1" max="100">
                                        <div class="form-text small text-muted">Position dans l'arbre de progression du joueur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">Statut de Production :</label>
                                        <select class="form-select fw-bold" name="status" id="fullScenStatus">
                                            <?php foreach ($statusLabels as $sKey => $sLbl): ?>
                                                <option value="<?= $sKey ?>" <?= ($activeScenario['status'] === $sKey) ? 'selected' : '' ?>><?= $sLbl ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">Identifiant Slug (Unique) :</label>
                                        <input type="text" class="form-control font-monospace" name="slug" id="fullScenSlug" value="<?= htmlspecialchars($activeScenario['slug'] ?? '') ?>" placeholder="step-01-eveil-du-fief">
                                        <div class="form-text small text-muted">Clé technique référencée dans le code.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">Période Historique &amp; Ère :</label>
                                        <input type="text" class="form-control" name="era" id="fullScenEra" value="<?= htmlspecialchars($activeScenario['era'] ?? '1560 - Sengoku Jidai') ?>">
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-bold text-dark">Conditions de Déclenchement (Triggers) :</label>
                                        <textarea class="form-control font-monospace small" name="trigger_condition" id="fullScenTrigger" rows="2" placeholder="Ex: Tenshu niveau 1 construit + Palissades niveau 1"><?= htmlspecialchars($activeScenario['trigger_condition'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne Droite : Textes Narratifs, Dialogues & Récompenses -->
                            <div class="col-lg-8">
                                <div class="d-flex flex-column gap-3">
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label fw-bold text-dark fs-5">Titre Épique du Scénario :</label>
                                            <input type="text" class="form-control form-control-lg fw-bold" name="title" id="fullScenTitle" value="<?= htmlspecialchars($activeScenario['title'] ?? '') ?>" required placeholder="Ex: L'Éveil du Fief & l'Appel du Clan">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold text-dark fs-5">Sous-Titre / Acte :</label>
                                            <input type="text" class="form-control form-control-lg" name="subtitle" id="fullScenSubtitle" value="<?= htmlspecialchars($activeScenario['subtitle'] ?? '') ?>" placeholder="Ex: Prologue : Première moisson">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fa-solid fa-align-left text-primary me-1"></i>Synopsis Narratif &amp; Contexte Scénaristique :
                                        </label>
                                        <textarea class="form-control" name="synopsis" id="fullScenSynopsis" rows="4" style="line-height:1.6;" placeholder="Décrivez les enjeux dramatiques, les rivalités féodales et les motivations du seigneur..."><?= htmlspecialchars($activeScenario['synopsis'] ?? '') ?></textarea>
                                    </div>

                                    <div class="card border p-3 bg-light shadow-none">
                                        <h4 class="card-title text-purple fw-bold mb-3 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-comment-dots"></i>
                                            <span>Prise de Parole du Personnage (In-Game)</span>
                                        </h4>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-dark">Nom du Narrateur / PNJ :</label>
                                                <input type="text" class="form-control" name="speaker_name" id="fullScenSpeakerName" value="<?= htmlspecialchars($activeScenario['speaker_name'] ?? 'Maître de la Sagesse') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-dark">Titre / Rôle du PNJ :</label>
                                                <input type="text" class="form-control" name="speaker_role" id="fullScenSpeakerRole" value="<?= htmlspecialchars($activeScenario['speaker_role'] ?? 'Mentor Féodal') ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-bold text-dark">Discours / Dialogue Joueur :</label>
                                            <textarea class="form-control font-serif fs-4" name="dialogue" id="fullScenDialogue" rows="3" style="line-height:1.6;" placeholder="Discours solennel ou poétique adressé au joueur..."><?= htmlspecialchars($activeScenario['dialogue'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fa-solid fa-gift text-warning me-1"></i>Récompenses Narratives &amp; Conséquences Gameplay :
                                        </label>
                                        <input type="text" class="form-control" name="rewards_summary" id="fullScenRewards" value="<?= htmlspecialchars($activeScenario['rewards_summary'] ?? '') ?>" placeholder="Ex: 250 Kobans, 1 000 Pierres, Déblocage du simulateur de combat">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ───────────────────────────────────────────────────────────── -->
                    <!-- ONGLET 2 : MATRICE DES TÂCHES PAR MÉTIER (MULTI-TÂCHES)       -->
                    <!-- ───────────────────────────────────────────────────────────── -->
                    <div class="tab-pane fade" id="tab-pane-tasks" role="tabpanel">
                        
                        <!-- Barre d'outils Matrice -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 p-3 bg-light rounded border">
                            <div>
                                <h3 class="h4 text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-users-gear text-teal"></i>
                                    <span>Matrice Transversale des 10 Métiers du Studio</span>
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Les tâches par métier peuvent être multiples. Ajoutez autant de tâches que nécessaire pour chaque corps de métier (Level Design, Back, Front, Réseau, etc.).
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-teal fw-bold dropdown-toggle d-inline-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Ajouter une Tâche</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li class="dropdown-header text-uppercase small fw-bold">Sélectionnez le Métier</li>
                                        <?php foreach ($rolesMap as $rCode => $rDef): ?>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="addNewTaskForRole('<?= $rCode ?>'); return false;">
                                                    <span class="avatar avatar-xs rounded bg-<?= $rDef['color'] ?>-lt text-<?= $rDef['color'] ?>">
                                                        <i class="fa-solid <?= $rDef['icon'] ?>"></i>
                                                    </span>
                                                    <span><?= htmlspecialchars($rDef['name']) ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Filtre rapide par métier -->
                        <div class="d-flex flex-wrap gap-1 mb-4" id="roleFilterPills">
                            <button type="button" class="btn btn-sm btn-purple fw-bold" onclick="filterTaskRoleSection('all', this)">
                                Tous les métiers (<span id="totalTasksCountPill"><?= count($activeTasks) ?></span>)
                            </button>
                            <?php foreach ($rolesMap as $rCode => $rDef): ?>
                                <?php
                                $rCount = count($groupedTasks[$rCode]['tasks'] ?? []);
                                $rDone = $metrics['by_role'][$rCode]['done'] ?? 0;
                                ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterTaskRoleSection('<?= $rCode ?>', this)">
                                    <i class="fa-solid <?= $rDef['icon'] ?> text-<?= $rDef['color'] ?> me-1"></i>
                                    <span><?= htmlspecialchars($rDef['name']) ?></span>
                                    <span class="badge bg-secondary-lt ms-1" id="badgeCount_<?= $rCode ?>"><?= $rCount ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Sections Groupées par Métier -->
                        <div id="rolesSectionsContainer" class="d-flex flex-column gap-4">
                            <?php foreach ($rolesMap as $rCode => $rDef): ?>
                                <?php
                                $rTasks = $groupedTasks[$rCode]['tasks'] ?? [];
                                $rMetrics = $metrics['by_role'][$rCode] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
                                ?>
                                <div class="card border shadow-sm role-task-section" id="roleSection_<?= $rCode ?>" data-role="<?= $rCode ?>">
                                    <!-- En-tête du Métier -->
                                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-md rounded bg-<?= $rDef['color'] ?>-lt text-<?= $rDef['color'] ?> fw-bold">
                                                <i class="fa-solid <?= $rDef['icon'] ?> fs-3"></i>
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark h4 m-0 d-flex align-items-center gap-2">
                                                    <span><?= htmlspecialchars($rDef['name']) ?></span>
                                                    <span class="badge bg-<?= $rDef['color'] ?>-lt text-<?= $rDef['color'] ?> font-monospace" style="font-size:0.7rem;">
                                                        <?= htmlspecialchars($rDef['domain']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-secondary small"><?= htmlspecialchars($rDef['desc']) ?></div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            <!-- Jauge Métier -->
                                            <div class="d-none d-md-flex flex-column align-items-end">
                                                <span class="small font-monospace fw-bold text-dark" id="roleStatusText_<?= $rCode ?>">
                                                    <?= count($rTasks) ?> tâche(s) &bull; <?= $rMetrics['done'] ?> terminée(s) (<?= $rMetrics['percent'] ?>%)
                                                </span>
                                                <div class="progress mt-1" style="width: 120px; height: 5px;">
                                                    <div class="progress-bar bg-<?= $rDef['color'] ?>" id="roleProgressBar_<?= $rCode ?>" style="width: <?= $rMetrics['percent'] ?>%;"></div>
                                                </div>
                                            </div>

                                            <button type="button" class="btn btn-sm btn-outline-<?= $rDef['color'] ?> fw-bold d-inline-flex align-items-center gap-1" onclick="addNewTaskForRole('<?= $rCode ?>')">
                                                <i class="fa-solid fa-plus"></i>
                                                <span>Ajouter une tâche</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Conteneur des Tâches pour ce Métier -->
                                    <div class="card-body p-3 role-tasks-list" id="roleTasksList_<?= $rCode ?>">
                                        <?php if (empty($rTasks)): ?>
                                            <div class="text-center py-3 text-muted small empty-role-placeholder" id="placeholder_<?= $rCode ?>">
                                                <em>Aucune tâche spécifique pour ce métier actuellement. Cliquez sur « Ajouter une tâche » pour en définir une.</em>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($rTasks as $task): ?>
                                                <?php renderTaskCardHtml($task, $rDef); ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- ───────────────────────────────────────────────────────────── -->
                    <!-- ONGLET 3 : SIMULATEUR IN-GAME & RENDU JOUEUR                  -->
                    <!-- ───────────────────────────────────────────────────────────── -->
                    <div class="tab-pane fade" id="tab-pane-preview" role="tabpanel">
                        <div class="text-center py-2 text-muted small mb-4">
                            Prévisualisation en direct de l'apparition de l'étape ou de l'événement dans le fief du joueur Daimyō :
                        </div>

                        <div class="max-w-2xl mx-auto p-4 rounded-4 text-white shadow-lg border" style="background: linear-gradient(135deg, #0b1120 0%, #1e1b4b 100%); border-color: #eab308 !important; max-width: 720px;">
                            <div class="text-center mb-3">
                                <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-1 mb-2 font-monospace" id="livePreviewBadge">
                                    <?= ($activeScenario['type'] === 'step') ? "Step #{$activeScenario['step_number']} • Mode Campagne" : "🏮 Événement Spécial" ?>
                                </span>
                                <h2 class="text-warning fw-bold m-0 fs-1" id="livePreviewTitle"><?= htmlspecialchars($activeScenario['title'] ?: 'Titre du Scénario') ?></h2>
                                <div class="text-light-subtle small mt-1 font-monospace" id="livePreviewEra"><?= htmlspecialchars($activeScenario['era'] ?: '1560 - Sengoku Jidai') ?></div>
                            </div>

                            <div class="p-3 rounded bg-dark-subtle border border-secondary mb-3 d-flex gap-3 align-items-start">
                                <div class="avatar avatar-xl rounded-circle bg-purple-lt text-purple flex-shrink-0" style="font-size: 2.2rem;">
                                    🏯
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-warning fs-3" id="livePreviewSpeaker"><?= htmlspecialchars($activeScenario['speaker_name'] ?: 'Narrateur') ?></div>
                                    <div class="text-muted small mb-2 font-monospace" id="livePreviewSpeakerRole"><?= htmlspecialchars($activeScenario['speaker_role'] ?: 'Mentor') ?></div>
                                    <div class="text-white-50 italic fs-4" style="line-height:1.6;" id="livePreviewDialogue">
                                        « <?= htmlspecialchars($activeScenario['dialogue'] ?: 'Dialogue...') ?> »
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 text-start small mb-3">
                                <div class="col-sm-6">
                                    <div class="p-3 rounded bg-dark border border-secondary h-100">
                                        <div class="text-muted fw-bold mb-1"><i class="fa-solid fa-bullseye text-warning me-1"></i>Objectif Requis :</div>
                                        <div class="text-light fw-medium" id="livePreviewTrigger"><?= htmlspecialchars($activeScenario['trigger_condition'] ?: 'Conditions requises') ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded bg-dark border border-secondary h-100">
                                        <div class="text-muted fw-bold mb-1"><i class="fa-solid fa-gift text-purple me-1"></i>Récompenses du Shōgunat :</div>
                                        <div class="text-warning fw-bold" id="livePreviewRewards"><?= htmlspecialchars($activeScenario['rewards_summary'] ?: 'Récompenses') ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center pt-2">
                                <button type="button" class="btn btn-warning fw-bold px-4 py-2 text-dark shadow fs-3">
                                    ⚔️ Décréter la Marche du Fief
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Pied de formulaire sticky -->
            <div class="card-footer bg-light-subtle py-3 px-4 d-flex justify-content-between align-items-center border-top">
                <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-1"></i>Annuler &amp; Retour au Catalogue
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-purple fw-bold shadow-sm" onclick="submitFullPageForm('catalog')">
                        <i class="fa-solid fa-check me-1"></i>Enregistrer et quitter
                    </button>
                    <button type="button" class="btn btn-purple fw-bold shadow-sm d-inline-flex align-items-center gap-2 px-4" onclick="submitFullPageForm('edit')">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer les Modifications</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODE 2 : CATALOGUE PRINCIPAL DES SCÉNARIOS                                  -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<?php else:
    // Filtres catalogue
    $filterType = !empty($_GET['type']) && in_array($_GET['type'], ScenarioEngine::VALID_TYPES, true) ? $_GET['type'] : null;
    $filterStatus = !empty($_GET['status']) && in_array($_GET['status'], ScenarioEngine::VALID_STATUSES, true) ? $_GET['status'] : null;
    $searchQuery = trim($_GET['search'] ?? '');

    $scenarios = $scenarioEngine->getScenarios($filterType, $filterStatus, $searchQuery);

    $totalCount = count($scenarios);
    $stepsCount = 0;
    $eventsCount = 0;
    $totalTasksDone = 0;
    $totalTasksCount = 0;

    foreach ($scenarios as $s) {
        if ($s['type'] === 'step') $stepsCount++;
        else $eventsCount++;

        $m = $s['metrics'] ?? [];
        $totalTasksDone += ($m['done'] ?? 0);
        $totalTasksCount += ($m['total'] ?? 0);
    }

    $globalPercent = ($totalTasksCount > 0) ? (int)round(($totalTasksDone / $totalTasksCount) * 100) : 0;
?>

<div class="gamestudio-narrative-scenarios-catalog">

    <!-- En-tête Catalogue -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="page-pretitle text-secondary text-uppercase fw-bold">Narrative Designer &bull; Scénarisation &amp; Pipeline Studio</div>
            <h2 class="card-title text-purple d-flex align-items-center gap-2 m-0" style="font-size:1.4rem;">
                <i class="fa-solid fa-book-journal-whills text-purple"></i>
                <span>Scénarios de Jeu &amp; Matrice des Tâches Métiers</span>
                <span class="badge bg-purple-lt text-purple font-monospace">Full Page Studio v2.0</span>
            </h2>
            <div class="text-secondary small mt-1">
                Scénarisation des étapes (steps) de la progression gameplay, conception des événements spéciaux et matrice opérationnelle multi-tâches des 10 métiers.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="?page=gamestudio&tab=narrative_scenarios&action=create" class="btn btn-purple fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Nouveau Scénario (Studio Pleine Page)</span>
            </a>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (isset($_SESSION['scenario_flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['scenario_flash']['type']) ?> alert-dismissible mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid <?= $_SESSION['scenario_flash']['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning' ?> fs-3"></i>
                <div><?= htmlspecialchars($_SESSION['scenario_flash']['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['scenario_flash']); ?>
    <?php endif; ?>

    <!-- Cartes KPIs -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple-lt text-purple avatar">
                                <i class="fa-solid fa-scroll fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Total Scénarios</div>
                            <div class="text-dark fw-bold fs-2"><?= $totalCount ?></div>
                            <div class="small text-muted"><?= $stepsCount ?> steps &bull; <?= $eventsCount ?> événements</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning-lt text-warning avatar">
                                <i class="fa-solid fa-stairs fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Steps de Campagne</div>
                            <div class="text-dark fw-bold fs-2"><?= $stepsCount ?></div>
                            <div class="small text-muted">Progression &amp; Quêtes féodales</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger-lt text-danger avatar">
                                <i class="fa-solid fa-calendar-star fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Événements Spéciaux</div>
                            <div class="text-dark fw-bold fs-2"><?= $eventsCount ?></div>
                            <div class="small text-muted">Saisons, Incursions &amp; Crises</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal-lt text-teal avatar">
                                <i class="fa-solid fa-list-check fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Complétion Tâches Métiers</div>
                            <div class="text-dark fw-bold fs-2"><?= $globalPercent ?>%</div>
                            <div class="small text-muted"><?= $totalTasksDone ?> / <?= $totalTasksCount ?> tâches livrées</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de Filtrage & Recherche -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="narrative_scenarios">

                <div class="col-md-5 d-flex gap-1">
                    <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-sm <?= empty($filterType) ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        Tous (<?= $totalCount ?>)
                    </a>
                    <a href="?page=gamestudio&tab=narrative_scenarios&type=step" class="btn btn-sm <?= ($filterType === 'step') ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-stairs me-1"></i>Steps Mode de Jeu
                    </a>
                    <a href="?page=gamestudio&tab=narrative_scenarios&type=special_event" class="btn btn-sm <?= ($filterType === 'special_event') ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-calendar-star me-1"></i>Événements Spéciaux
                    </a>
                </div>

                <div class="col-sm-6 col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Tous les statuts --</option>
                        <option value="draft" <?= ($filterStatus === 'draft') ? 'selected' : '' ?>>📝 Brouillon</option>
                        <option value="in_review" <?= ($filterStatus === 'in_review') ? 'selected' : '' ?>>🔍 En revue</option>
                        <option value="in_production" <?= ($filterStatus === 'in_production') ? 'selected' : '' ?>>⚙️ En production</option>
                        <option value="ready" <?= ($filterStatus === 'ready') ? 'selected' : '' ?>>✅ Prêt / Validé</option>
                        <option value="published" <?= ($filterStatus === 'published') ? 'selected' : '' ?>>🚀 En ligne (Live)</option>
                        <option value="archived" <?= ($filterStatus === 'archived') ? 'selected' : '' ?>>📦 Archivé</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-4">
                    <div class="input-icon">
                        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control form-control-sm" placeholder="Rechercher par titre, synopsis, mot-clé...">
                        <span class="input-icon-addon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Grille des Scénarios -->
    <?php if (empty($scenarios)): ?>
        <div class="card shadow-sm border-0 py-5 text-center">
            <div class="card-body">
                <div class="avatar avatar-xl bg-purple-lt text-purple mx-auto mb-3" style="font-size:2rem;">
                    <i class="fa-solid fa-feather-pointed"></i>
                </div>
                <h3 class="fw-bold text-dark">Aucun scénario ne correspond aux critères</h3>
                <p class="text-secondary max-w-sm mx-auto">
                    Ajustez vos filtres ou ouvrez le Studio Pleine Page pour créer un nouveau scénario.
                </p>
                <a href="?page=gamestudio&tab=narrative_scenarios&action=create" class="btn btn-purple mt-2">
                    <i class="fa-solid fa-plus me-1"></i>Créer en Pleine Page
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cards">
            <?php foreach ($scenarios as $sc): ?>
                <?php
                $isStep = ($sc['type'] === 'step');
                $statusCfg = match($sc['status']) {
                    'published'     => ['badge' => 'bg-success text-white', 'label' => '🚀 En ligne (Live)'],
                    'ready'         => ['badge' => 'bg-teal text-white', 'label' => '✅ Prêt'],
                    'in_production' => ['badge' => 'bg-primary text-white', 'label' => '⚙️ En production'],
                    'in_review'     => ['badge' => 'bg-warning text-dark', 'label' => '🔍 En revue'],
                    'archived'      => ['badge' => 'bg-secondary text-white', 'label' => '📦 Archivé'],
                    default         => ['badge' => 'bg-purple-lt text-purple', 'label' => '📝 Brouillon']
                };
                $metrics = $sc['metrics'] ?? ['percent' => 0, 'done' => 0, 'total' => 0];
                $pct = $metrics['percent'];
                $barCol = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-teal' : (($pct > 0) ? 'bg-primary' : 'bg-secondary'));
                ?>
                <div class="col-md-12 col-xl-6 mb-3">
                    <div class="card shadow-sm border h-100 d-flex flex-column transition-hover">
                        <!-- En-tête -->
                        <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isStep): ?>
                                    <span class="badge bg-purple text-white fw-bold">
                                        <i class="fa-solid fa-stairs me-1"></i>Step #<?= htmlspecialchars((string)$sc['step_number']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white fw-bold">
                                        <i class="fa-solid fa-calendar-star me-1"></i>Événement Spécial
                                    </span>
                                <?php endif; ?>
                                <span class="badge <?= $statusCfg['badge'] ?> fw-medium" style="font-size:0.75rem;">
                                    <?= $statusCfg['label'] ?>
                                </span>
                            </div>
                            <span class="font-monospace small text-muted">
                                <?= htmlspecialchars($sc['era'] ?? 'Sengoku Jidai') ?>
                            </span>
                        </div>

                        <!-- Corps -->
                        <div class="card-body p-3 flex-grow-1">
                            <h3 class="card-title text-dark fw-bold mb-1">
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $sc['id'] ?>" class="text-dark text-decoration-none hover-purple">
                                    <?= htmlspecialchars($sc['title']) ?>
                                </a>
                            </h3>
                            <?php if (!empty($sc['subtitle'])): ?>
                                <div class="text-secondary small fw-medium mb-2 italic">
                                    <?= htmlspecialchars($sc['subtitle']) ?>
                                </div>
                            <?php endif; ?>

                            <p class="text-secondary small mb-3 line-clamp-3" style="line-height:1.5;">
                                <?= htmlspecialchars($sc['synopsis']) ?>
                            </p>

                            <!-- Dialogue condensé -->
                            <div class="p-2 bg-light rounded border mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="avatar avatar-xs rounded bg-purple-lt text-purple fw-bold">
                                        <i class="fa-solid fa-comment"></i>
                                    </span>
                                    <strong class="small text-dark"><?= htmlspecialchars($sc['speaker_name'] ?? 'Narrateur') ?></strong>
                                    <span class="small text-muted">(<?= htmlspecialchars($sc['speaker_role'] ?? 'Voix') ?>)</span>
                                </div>
                                <div class="small text-muted italic ps-4 border-start border-2 border-purple ms-2">
                                    « <?= htmlspecialchars(mb_substr($sc['dialogue'] ?? '', 0, 130)) ?><?= mb_strlen($sc['dialogue'] ?? '') > 130 ? '...' : '' ?> »
                                </div>
                            </div>

                            <!-- Matrice des tâches -->
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-users-gear text-teal"></i>
                                        <span>Matrice Tâches Métiers</span>
                                    </span>
                                    <span class="small font-monospace fw-bold text-secondary">
                                        <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $pct ?>%)
                                    </span>
                                </div>

                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar <?= $barCol ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                </div>

                                <!-- Pastilles des Tâches -->
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($sc['tasks'] as $task): ?>
                                        <?php
                                        $rKey = $task['role_code'] ?? 'level_designer';
                                        $rDef = $rolesMap[$rKey] ?? ['name' => 'Métier', 'icon' => 'fa-gear', 'color' => 'secondary'];
                                        $tStatus = $task['status'] ?? 'todo';
                                        $tBadgeClass = match($tStatus) {
                                            'done'        => 'bg-success text-white border-success',
                                            'in_progress' => 'bg-primary text-white border-primary',
                                            'blocked'     => 'bg-danger text-white border-danger',
                                            default       => 'bg-light text-muted border'
                                        };
                                        $tStatusLabel = match($tStatus) {
                                            'done'        => 'Terminé',
                                            'in_progress' => 'En cours',
                                            'blocked'     => 'Bloqué',
                                            default       => 'À faire'
                                        };
                                        ?>
                                        <span class="badge <?= $tBadgeClass ?> d-inline-flex align-items-center justify-content-center p-1 rounded" 
                                              style="width: 26px; height: 26px; font-size: 0.75rem;"
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top" 
                                              title="<?= htmlspecialchars("{$rDef['name']} : {$task['title']} [{$tStatusLabel}]") ?>">
                                            <i class="fa-solid <?= $rDef['icon'] ?>"></i>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pied de carte -->
                        <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-top">
                            <span class="small font-monospace text-muted">
                                ID #<?= $sc['id'] ?> &bull; <?= htmlspecialchars($sc['slug']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" title="GDD Markdown" onclick="exportScenarioMarkdown(<?= $sc['id'] ?>)">
                                    <i class="fa-solid fa-file-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" title="Dupliquer" onclick="duplicateCurrentScenario(<?= $sc['id'] ?>)">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $sc['id'] ?>" class="btn btn-purple btn-sm fw-bold d-inline-flex align-items-center gap-1">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Éditeur Pleine Page</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODALE COMMUNE D'EXPORT GDD / MARKDOWN                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalScenarioExport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0 text-white">
                    <i class="fa-solid fa-file-code text-teal"></i>
                    <span>Export Game Design Document (GDD / Markdown)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Copiez ce cahier GDD structuré pour vos wikis, GitHub ou Notion :</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyExportMarkdownText()">
                        <i class="fa-solid fa-copy me-1"></i>Copier le GDD
                    </button>
                </div>
                <textarea id="scenarioExportTextarea" class="form-control font-monospace small bg-light" rows="18" readonly></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire POST caché pour actions rapides (duplication, suppression directe) -->
<form id="catalogQuickActionForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="action" id="catQuickActionName" value="">
    <input type="hidden" name="scenario_id" id="catQuickScenarioId" value="0">
    <input type="hidden" name="return_action" value="<?= $viewAction ?>">
</form>

<script>
const ROLES_DEF_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;

// Bascule champ step number
function toggleStepNumberFieldFullPage() {
    const typeSelect = document.getElementById('fullScenType');
    const group = document.getElementById('stepNumberGroupFullPage');
    if (typeSelect && group) {
        group.style.display = (typeSelect.value === 'step') ? 'block' : 'none';
    }
}

// Soumission du formulaire pleine page
function submitFullPageForm(redirectTo) {
    const form = document.getElementById('fullPageScenarioForm');
    if (!form) return;
    document.getElementById('formRedirectTo').value = redirectTo || 'edit';
    form.submit();
}

// Raccourci Clavier Ctrl + S pour enregistrement rapide
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        const form = document.getElementById('fullPageScenarioForm');
        if (form) {
            e.preventDefault();
            submitFullPageForm('edit');
        }
    }
});

// Ajout dynamique d'une tâche pour un métier spécifique (multi-tâches illimitées)
function addNewTaskForRole(roleCode) {
    const rDef = ROLES_DEF_MAP[roleCode] || { name: 'Métier', icon: 'fa-gear', color: 'secondary' };
    const listContainer = document.getElementById(`roleTasksList_${roleCode}`);
    if (!listContainer) return;

    // Supprimer le placeholder si présent
    const placeholder = document.getElementById(`placeholder_${roleCode}`);
    if (placeholder) {
        placeholder.remove();
    }

    const uniqueTaskId = 'task_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

    const card = document.createElement('div');
    card.className = 'card border p-3 mb-3 bg-light shadow-none task-card-item border-teal';
    card.id = `taskCard_${uniqueTaskId}`;
    card.setAttribute('data-role', roleCode);

    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-${rDef.color} text-white fw-bold">
                    Nouveau
                </span>
                <span class="fw-bold text-dark">${rDef.name}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-bold">Complexité :</label>
                <select class="form-select form-select-sm" name="tasks[${uniqueTaskId}][complexity]" style="width: auto;">
                    <option value="S">S (Court)</option>
                    <option value="M" selected>M (Moyen)</option>
                    <option value="L">L (Important)</option>
                    <option value="XL">XL (Majeur)</option>
                </select>

                <label class="small text-muted fw-bold ms-2">Statut :</label>
                <select class="form-select form-select-sm fw-bold task-status-select" name="tasks[${uniqueTaskId}][status]" style="width: auto;" onchange="updateRoleTelemetry('${roleCode}')">
                    <option value="todo" selected>⏳ À faire</option>
                    <option value="in_progress">🔧 En cours</option>
                    <option value="done">✅ Terminé</option>
                    <option value="blocked">⛔ Bloqué</option>
                </select>

                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 ms-2" title="Supprimer cette tâche" onclick="removeTaskCard('${uniqueTaskId}', '${roleCode}')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="tasks[${uniqueTaskId}][task_id]" value="${uniqueTaskId}">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_code]" value="${roleCode}">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_name]" value="${rDef.name}">

        <div class="row g-2">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-dark m-0">Titre Opérationnel de la Tâche :</label>
                <input type="text" class="form-control form-control-sm" name="tasks[${uniqueTaskId}][title]" value="Nouvelle tâche pour ${rDef.name}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-dark m-0">Artisan / Développeur Assigné :</label>
                <input type="text" class="form-control form-control-sm" name="tasks[${uniqueTaskId}][assignee]" value="${rDef.name}">
            </div>
            <div class="col-12 mt-2">
                <label class="form-label small fw-bold text-dark m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                <textarea class="form-control form-control-sm" name="tasks[${uniqueTaskId}][description]" rows="2" placeholder="Spécifications...">${rDef.desc || ''}</textarea>
            </div>
            <div class="col-12 mt-1">
                <label class="form-label small fw-bold text-dark m-0">Livrables / Fichiers Attendus :</label>
                <input type="text" class="form-control form-control-sm font-monospace" name="tasks[${uniqueTaskId}][deliverables]" value="Livrable attendu">
            </div>
        </div>
    `;

    listContainer.appendChild(card);
    updateRoleTelemetry(roleCode);

    // Faire défiler vers la carte ajoutée
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Suppression d'une carte de tâche
function removeTaskCard(taskId, roleCode) {
    const card = document.getElementById(`taskCard_${taskId}`);
    if (card) {
        card.remove();
        updateRoleTelemetry(roleCode);
    }
}

// Mise à jour de la télémétrie locale d'un rôle
function updateRoleTelemetry(roleCode) {
    const listContainer = document.getElementById(`roleTasksList_${roleCode}`);
    if (!listContainer) return;

    const cards = listContainer.querySelectorAll('.task-card-item');
    const total = cards.length;
    let done = 0;

    cards.forEach(c => {
        const sel = c.querySelector('.task-status-select');
        if (sel && sel.value === 'done') done++;
    });

    const percent = (total > 0) ? Math.round((done / total) * 100) : 0;

    // Mise à jour textes et jauges du rôle
    const textEl = document.getElementById(`roleStatusText_${roleCode}`);
    if (textEl) {
        textEl.textContent = `${total} tâche(s) • ${done} terminée(s) (${percent}%)`;
    }

    const barEl = document.getElementById(`roleProgressBar_${roleCode}`);
    if (barEl) {
        barEl.style.width = `${percent}%`;
    }

    const badgePill = document.getElementById(`badgeCount_${roleCode}`);
    if (badgePill) {
        badgePill.textContent = total;
    }

    // Mise à jour du total global
    updateGlobalTasksTelemetry();
}

// Recalcul du total global de toutes les tâches
function updateGlobalTasksTelemetry() {
    const allCards = document.querySelectorAll('#rolesSectionsContainer .task-card-item');
    const total = allCards.length;
    let done = 0;

    allCards.forEach(c => {
        const sel = c.querySelector('.task-status-select');
        if (sel && sel.value === 'done') done++;
    });

    const percent = (total > 0) ? Math.round((done / total) * 100) : 0;
    const badge = document.getElementById('tasksGlobalBadge');
    if (badge) {
        badge.textContent = `${done}/${total} (${percent}%)`;
    }
    const countPill = document.getElementById('totalTasksCountPill');
    if (countPill) {
        countPill.textContent = total;
    }
}

// Filtre d'affichage des sections de rôles
function filterTaskRoleSection(roleCode, btnEl) {
    const buttons = document.querySelectorAll('#roleFilterPills button');
    buttons.forEach(b => {
        b.className = 'btn btn-sm btn-outline-secondary';
    });
    if (btnEl) {
        btnEl.className = 'btn btn-sm btn-purple fw-bold';
    }

    const sections = document.querySelectorAll('.role-task-section');
    sections.forEach(sec => {
        if (roleCode === 'all' || sec.getAttribute('data-role') === roleCode) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}

// Mise à jour du simulateur in-game
function renderInGamePreviewLive() {
    const type = document.getElementById('fullScenType')?.value || 'step';
    const stepNum = document.getElementById('fullScenStepNumber')?.value || '1';
    const title = document.getElementById('fullScenTitle')?.value || 'Titre du Scénario';
    const era = document.getElementById('fullScenEra')?.value || '1560 - Sengoku Jidai';
    const speaker = document.getElementById('fullScenSpeakerName')?.value || 'Maître de la Sagesse';
    const role = document.getElementById('fullScenSpeakerRole')?.value || 'Mentor Féodal';
    const dialogue = document.getElementById('fullScenDialogue')?.value || '...';
    const trigger = document.getElementById('fullScenTrigger')?.value || 'Objectifs requis';
    const rewards = document.getElementById('fullScenRewards')?.value || 'Récompenses du Shōgunat';

    const b = document.getElementById('livePreviewBadge');
    if (b) b.textContent = (type === 'step') ? `Step #${stepNum} • Mode Campagne` : `🏮 Événement Spécial`;

    const t = document.getElementById('livePreviewTitle');
    if (t) t.textContent = title;

    const e = document.getElementById('livePreviewEra');
    if (e) e.textContent = era;

    const sp = document.getElementById('livePreviewSpeaker');
    if (sp) sp.textContent = speaker;

    const spr = document.getElementById('livePreviewSpeakerRole');
    if (spr) spr.textContent = role;

    const dia = document.getElementById('livePreviewDialogue');
    if (dia) dia.textContent = `« ${dialogue} »`;

    const tr = document.getElementById('livePreviewTrigger');
    if (tr) tr.textContent = trigger;

    const rw = document.getElementById('livePreviewRewards');
    if (rw) rw.textContent = rewards;
}

// Duplication d'un scénario
function duplicateCurrentScenario(scId) {
    if (!confirm('Dupliquer ce scénario avec l\'ensemble de ses tâches métiers ?')) return;
    document.getElementById('catQuickActionName').value = 'duplicate_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

// Suppression d'un scénario
function deleteCurrentScenario(scId) {
    if (!confirm(`Supprimer définitivement le scénario #${scId} et toutes ses tâches ? Cette action est irréversible.`)) return;
    document.getElementById('catQuickActionName').value = 'delete_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

// Export GDD Markdown
function exportScenarioMarkdown(scId) {
    fetch(`?page=gamestudio&tab=narrative_scenarios&action=export_markdown&scenario_id=${scId}`)
        .then(res => res.text())
        .then(md => {
            document.getElementById('scenarioExportTextarea').value = md;
            const modalEl = document.getElementById('modalScenarioExport');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        })
        .catch(err => {
            alert('Erreur lors du chargement de l\'export : ' + err);
        });
}

function copyExportMarkdownText() {
    const ta = document.getElementById('scenarioExportTextarea');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        alert('Cahier GDD Markdown copié dans le presse-papier avec succès !');
    });
}
</script>
