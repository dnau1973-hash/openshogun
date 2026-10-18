<?php
/**
 * Game Studio - Narrative Designer : Scénarios de Jeu & Matrice des Tâches Multiples par Métier
 * Support du Mode Catalogue, Graphiques Télémétriques, Visionneuse Markdown GDD Rendue & Éditeur Pleine Page
 * Fichier : views/gamestudio/narrative_scenarios.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/ScenarioEngine.php';
$scenarioEngine = new ScenarioEngine();

$rolesMap = ScenarioEngine::ROLES;
$viewAction = (string)($_GET['action'] ?? 'catalog');

// Pré-chargement de tous les scénarios pour les graphiques et la visionneuse Markdown instantanée
$allScenariosUnfiltered = $scenarioEngine->getScenarios();
$preloadedMarkdownMap = [];
$preloadedScenariosMap = [];
foreach ($allScenariosUnfiltered as $scItem) {
    $scItemId = (int)$scItem['id'];
    $preloadedMarkdownMap[$scItemId] = $scenarioEngine->exportMarkdown($scItemId);
    $preloadedScenariosMap[$scItemId] = $scItem;
}

// Helper PHP pour rendre une carte de tâche dans l'éditeur pleine page
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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="small text-muted fw-bold m-0">Complexité :</label>
                    <select class="form-select form-select-sm task-complexity-select" name="tasks[<?= $taskId ?>][complexity]" style="width: auto;" onchange="updateRoleTelemetry('<?= $roleCode ?>')">
                        <option value="S" <?= ($complexity === 'S') ? 'selected' : '' ?>>S (Court)</option>
                        <option value="M" <?= ($complexity === 'M') ? 'selected' : '' ?>>M (Moyen)</option>
                        <option value="L" <?= ($complexity === 'L') ? 'selected' : '' ?>>L (Important)</option>
                        <option value="XL" <?= ($complexity === 'XL') ? 'selected' : '' ?>>XL (Majeur)</option>
                    </select>

                    <label class="small text-muted fw-bold ms-2 m-0">Statut :</label>
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

            <input type="hidden" name="tasks[<?= $taskId ?>][task_id]" value="<?= $taskId ?>" class="task-id-input">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_code]" value="<?= $roleCode ?>" class="task-role-code-input">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_name]" value="<?= $roleName ?>" class="task-role-name-input">

            <div class="row g-2">
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-dark m-0">Titre Opérationnel de la Tâche :</label>
                    <input type="text" class="form-control form-control-sm task-title-input" name="tasks[<?= $taskId ?>][title]" value="<?= $title ?>" required placeholder="Ex: Moteur de trigger, Portrait 2D, Intégration UI...">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-dark m-0">Artisan / Développeur Assigné :</label>
                    <input type="text" class="form-control form-control-sm task-assignee-input" name="tasks[<?= $taskId ?>][assignee]" value="<?= $assignee ?>">
                </div>
                <div class="col-12 mt-2">
                    <label class="form-label small fw-bold text-dark m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                    <textarea class="form-control form-control-sm task-desc-input" name="tasks[<?= $taskId ?>][description]" rows="2" placeholder="Détaillez le travail attendu pour ce métier..."><?= $description ?></textarea>
                </div>
                <div class="col-12 mt-1">
                    <label class="form-label small fw-bold text-dark m-0">Livrables / Fichiers Attendus :</label>
                    <input type="text" class="form-control form-control-sm font-monospace task-deliverables-input" name="tasks[<?= $taskId ?>][deliverables]" value="<?= $deliverables ?>" placeholder="Ex: core/ScenarioEngine.php, public/assets/...">
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- STYLES DÉDIÉS : COMPOSANTS GRAPHIQUES, BOUTONS & VISIONNEUSE MARKDOWN GDD  -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* Correction des classes utilitaires Tabler / Bootstrap pour affichage garanti */
.btn-purple {
    background-color: #7c3aed !important;
    border-color: #6d28d9 !important;
    color: #ffffff !important;
}
.btn-purple:hover, .btn-purple:focus {
    background-color: #6d28d9 !important;
    border-color: #5b21b6 !important;
    color: #ffffff !important;
}
.btn-outline-purple {
    color: #7c3aed !important;
    border-color: #7c3aed !important;
    background-color: transparent !important;
}
.btn-outline-purple:hover {
    background-color: #7c3aed !important;
    color: #ffffff !important;
}
.btn-teal {
    background-color: #0ca678 !important;
    border-color: #099268 !important;
    color: #ffffff !important;
}
.btn-teal:hover {
    background-color: #099268 !important;
    color: #ffffff !important;
}
.btn-outline-teal {
    color: #0ca678 !important;
    border-color: #0ca678 !important;
}
.btn-outline-teal:hover {
    background-color: #0ca678 !important;
    color: #ffffff !important;
}
.bg-purple-dark {
    background-color: #4c1d95 !important;
    color: #ffffff !important;
}
.bg-light-subtle {
    background-color: #f8fafc !important;
}
.border-purple {
    border-color: #8b5cf6 !important;
}
.border-teal {
    border-color: #14b8a6 !important;
}
.hover-purple:hover {
    color: #7c3aed !important;
}
.transition-hover {
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.transition-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.1) !important;
    border-color: #cbd5e1 !important;
}

/* Pipeline Graphique des Steps & Événements */
.scenario-pipeline-track {
    display: flex;
    align-items: stretch;
    gap: 0.75rem;
    overflow-x: auto;
    padding: 0.5rem 0.25rem 0.75rem 0.25rem;
}
.scenario-pipeline-node {
    min-width: 230px;
    max-width: 260px;
    flex: 0 0 auto;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.85rem;
    position: relative;
    transition: all 0.2s ease;
}
.scenario-pipeline-node:hover {
    border-color: #8b5cf6;
    box-shadow: 0 6px 16px rgba(124, 58, 237, 0.12);
    transform: translateY(-2px);
}
.scenario-pipeline-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1.1rem;
    flex: 0 0 20px;
}

/* Visionneuse Markdown GDD Rendue */
.gdd-viewer-rendered {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.75rem 2rem;
    color: #1e293b;
    font-size: 0.94rem;
    line-height: 1.65;
    max-height: 72vh;
    overflow-y: auto;
}
.gdd-viewer-rendered h1 {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    border-bottom: 2px solid #8b5cf6;
    padding-bottom: 0.65rem;
    margin-bottom: 1rem;
}
.gdd-viewer-rendered h2 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #4c1d95;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    padding-left: 0.65rem;
    border-left: 4px solid #8b5cf6;
    background: #f5f3ff;
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
    border-radius: 0 6px 6px 0;
}
.gdd-viewer-rendered h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}
.gdd-viewer-rendered blockquote {
    background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
    color: #f8fafc;
    border-left: 4px solid #eab308;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin: 1rem 0;
    font-style: italic;
}
.gdd-viewer-rendered blockquote strong {
    color: #facc15;
    font-style: normal;
}
.gdd-viewer-rendered code {
    background: #f1f5f9;
    color: #7c3aed;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.85em;
}
.gdd-viewer-rendered table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    font-size: 0.86rem;
}
.gdd-viewer-rendered table th {
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    padding: 0.65rem 0.75rem;
    border: 1px solid #cbd5e1;
}
.gdd-viewer-rendered table td {
    padding: 0.6rem 0.75rem;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
}
.gdd-viewer-rendered table tr:nth-child(even) td {
    background-color: #f8fafc;
}
.gdd-viewer-rendered hr {
    border: 0;
    border-top: 1px solid #cbd5e1;
    margin: 1.5rem 0;
}

/* Simulateur In-Game Sengoku */
.sengoku-ingame-simulator {
    background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #0f172a 100%);
    border: 2px solid #eab308;
    border-radius: 16px;
    color: #f8fafc;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
    position: relative;
    overflow: hidden;
}
.sengoku-dialogue-box {
    background: rgba(15, 23, 42, 0.88);
    border: 1px solid rgba(234, 179, 8, 0.45);
    border-radius: 12px;
    color: #f8fafc;
}
.sengoku-info-box {
    background: rgba(9, 13, 22, 0.85);
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    color: #f1f5f9;
}
</style>

<?php
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
        $stepCount = count(array_filter($allScenariosUnfiltered, fn($s) => ($s['type'] ?? 'step') === 'step'));

        $activeScenario = [
            'id'                => 0,
            'slug'              => 'step-0' . ($stepCount + 1) . '-nouveau-scenario',
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
                            <?= $isCreate ? 'Nouveau Scénario' : 'Édition Pleine Page #' . (int)$activeScenario['id'] ?>
                        </li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                    <h2 class="h3 m-0 text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-feather-pointed text-purple"></i>
                        <span id="headerLiveTitle"><?= $isCreate ? 'Création d\'un Nouveau Scénario' : htmlspecialchars($activeScenario['title']) ?></span>
                    </h2>
                    <?php if (!$isCreate): ?>
                        <span class="badge bg-purple-lt text-purple font-monospace">
                            ID #<?= (int)$activeScenario['id'] ?> &bull; <?= htmlspecialchars($activeScenario['slug']) ?>
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

                <button type="button" class="btn btn-outline-teal btn-sm d-inline-flex align-items-center gap-1 shadow-sm fw-bold" onclick="openLiveEditorMarkdownModal()">
                    <i class="fa-brands fa-markdown"></i>
                    <span>Visionneuse GDD Markdown</span>
                </button>

                <?php if (!$isCreate): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" onclick="duplicateCurrentScenario(<?= (int)$activeScenario['id'] ?>)">
                        <i class="fa-solid fa-copy text-primary"></i>
                        <span>Dupliquer</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 shadow-sm" onclick="deleteCurrentScenario(<?= (int)$activeScenario['id'] ?>)">
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
                        <a class="nav-link active fw-bold text-dark py-3 px-3 d-inline-flex align-items-center gap-2" id="tab-link-narrative" data-bs-toggle="tab" href="#tab-pane-narrative" role="tab">
                            <i class="fa-solid fa-scroll text-purple fs-4"></i>
                            <span>1. Trame Narrative &amp; Scénario</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark py-3 px-3 d-inline-flex align-items-center gap-2" id="tab-link-tasks" data-bs-toggle="tab" href="#tab-pane-tasks" role="tab" onclick="setTimeout(refreshEditorGraphicalCharts, 60)">
                            <i class="fa-solid fa-users-gear text-teal fs-4"></i>
                            <span>2. Matrice des Tâches &amp; Graphiques</span>
                            <span class="badge bg-teal-lt text-teal rounded-pill font-monospace" id="tasksGlobalBadge">
                                <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $metrics['percent'] ?>%)
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark py-3 px-3 d-inline-flex align-items-center gap-2" id="tab-link-preview" data-bs-toggle="tab" href="#tab-pane-preview" role="tab" onclick="renderInGamePreviewLive()">
                            <i class="fa-solid fa-eye text-warning fs-4"></i>
                            <span>3. Simulateur In-Game</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark py-3 px-3 d-inline-flex align-items-center gap-2" id="tab-link-gdd" data-bs-toggle="tab" href="#tab-pane-gdd" role="tab" onclick="refreshInlineEditorGddViewer()">
                            <i class="fa-brands fa-markdown text-indigo fs-4"></i>
                            <span>4. Visionneuse GDD Markdown &amp; Schémas</span>
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
                    <!-- ONGLET 2 : MATRICE DES TÂCHES & GRAPHIQUES TEMPS RÉEL         -->
                    <!-- ───────────────────────────────────────────────────────────── -->
                    <div class="tab-pane fade" id="tab-pane-tasks" role="tabpanel">

                        <!-- SYNTHÈSE GRAPHIQUE TEMPS RÉEL DU SCÉNARIO -->
                        <div class="row row-cards mb-4">
                            <!-- Jauge Circulaire SVG & Répartition par Statut -->
                            <div class="col-lg-5">
                                <div class="card border shadow-none h-100 bg-light-subtle">
                                    <div class="card-header py-2 px-3 bg-white border-bottom">
                                        <h4 class="card-title m-0 fs-4 fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-chart-pie text-purple"></i>
                                            <span>Avancement Graphique du Scénario</span>
                                        </h4>
                                    </div>
                                    <div class="card-body p-3 d-flex align-items-center justify-content-around flex-wrap gap-3">
                                        <!-- Anneau SVG Dynamique -->
                                        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 130px; height: 130px;">
                                            <svg viewBox="0 0 36 36" class="w-100 h-100" style="transform: rotate(-90deg);">
                                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                      fill="none" stroke="#e2e8f0" stroke-width="3.8" />
                                                <path id="editorSvgDonutCircle"
                                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                      fill="none" stroke="#0ca678" stroke-width="3.8"
                                                      stroke-dasharray="<?= $metrics['percent'] ?>, 100"
                                                      stroke-linecap="round" />
                                            </svg>
                                            <div class="position-absolute text-center">
                                                <div class="h2 m-0 fw-bold text-dark font-monospace" id="editorDonutPercentText"><?= $metrics['percent'] ?>%</div>
                                                <div class="small text-muted" style="font-size:0.68rem;">Complété</div>
                                            </div>
                                        </div>

                                        <!-- Compteurs par Statut -->
                                        <div class="flex-grow-1" style="min-width: 180px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                <span class="small fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i>Terminées</span>
                                                <span class="badge bg-success text-white font-monospace" id="editorCountDone"><?= $metrics['done'] ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                <span class="small fw-bold text-primary"><i class="fa-solid fa-gears me-1"></i>En cours</span>
                                                <span class="badge bg-primary text-white font-monospace" id="editorCountProgress"><?= $metrics['in_progress'] ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                                <span class="small fw-bold text-secondary"><i class="fa-regular fa-clock me-1"></i>À faire</span>
                                                <span class="badge bg-secondary text-white font-monospace" id="editorCountTodo"><?= $metrics['todo'] ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="small fw-bold text-danger"><i class="fa-solid fa-ban me-1"></i>Bloquées</span>
                                                <span class="badge bg-danger text-white font-monospace" id="editorCountBlocked"><?= $metrics['blocked'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Graphique de Répartition par Métier (10 Barres Visuelles Temps Réel) -->
                            <div class="col-lg-7">
                                <div class="card border shadow-none h-100 bg-light-subtle">
                                    <div class="card-header py-2 px-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h4 class="card-title m-0 fs-4 fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-chart-bar text-teal"></i>
                                            <span>Charge &amp; Progression des 10 Métiers</span>
                                        </h4>
                                        <span class="badge bg-teal-lt text-teal font-monospace" id="editorTotalTasksChartBadge"><?= $metrics['total'] ?> tâches</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2" id="editorRolesMiniChartGrid">
                                            <?php foreach ($rolesMap as $rCode => $rDef):
                                                $rM = $metrics['by_role'][$rCode] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
                                            ?>
                                                <div class="col-sm-6">
                                                    <div class="p-2 bg-white rounded border">
                                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                                            <span class="fw-bold text-truncate" style="max-width: 155px;" title="<?= htmlspecialchars($rDef['name']) ?>">
                                                                <i class="fa-solid <?= $rDef['icon'] ?> text-<?= $rDef['color'] ?> me-1"></i><?= htmlspecialchars($rDef['name']) ?>
                                                            </span>
                                                            <span class="font-monospace small fw-bold text-muted" id="miniChartLabel_<?= $rCode ?>">
                                                                <?= $rM['done'] ?>/<?= $rM['total'] ?> (<?= $rM['percent'] ?>%)
                                                            </span>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-<?= $rDef['color'] ?>" id="miniChartBar_<?= $rCode ?>" style="width: <?= $rM['percent'] ?>%;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
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
                                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-bottom flex-wrap gap-2">
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
                        <div class="text-center py-2 text-secondary small mb-3">
                            <i class="fa-solid fa-gamepad text-purple me-1"></i>Prévisualisation fidèle de l'apparition de l'étape ou de l'événement dans le fief du joueur Daimyō :
                        </div>

                        <div class="sengoku-ingame-simulator mx-auto p-4" style="max-width: 760px;">
                            <div class="text-center mb-4">
                                <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-1 mb-2 font-monospace shadow-sm" id="livePreviewBadge">
                                    <?= ($activeScenario['type'] === 'step') ? "⛩️ Step #{$activeScenario['step_number']} • Mode Campagne" : "🏮 Événement Spécial" ?>
                                </span>
                                <h2 class="fw-bold m-0 fs-1" style="color: #facc15;" id="livePreviewTitle"><?= htmlspecialchars($activeScenario['title'] ?: 'Titre du Scénario') ?></h2>
                                <div class="small mt-1 fst-italic" style="color: #cbd5e1;" id="livePreviewSubtitle"><?= htmlspecialchars($activeScenario['subtitle'] ?: '') ?></div>
                                <div class="small mt-1 font-monospace" style="color: #94a3b8;" id="livePreviewEra"><?= htmlspecialchars($activeScenario['era'] ?: '1560 - Sengoku Jidai') ?></div>
                            </div>

                            <!-- Synopsis In-Game -->
                            <div class="sengoku-info-box p-3 mb-3 small" style="line-height: 1.6; color: #e2e8f0;" id="livePreviewSynopsis">
                                <?= nl2br(htmlspecialchars($activeScenario['synopsis'] ?: 'Synopsis narratif du scénario...')) ?>
                            </div>

                            <!-- Boîte de Dialogue du PNJ -->
                            <div class="sengoku-dialogue-box p-3 mb-3 d-flex gap-3 align-items-start">
                                <div class="avatar avatar-xl rounded-circle flex-shrink-0 border border-warning shadow" style="background: #311042; color: #facc15; font-size: 2rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-torii-gate"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="fw-bold fs-3" style="color: #facc15;" id="livePreviewSpeaker"><?= htmlspecialchars($activeScenario['speaker_name'] ?: 'Maître de la Sagesse') ?></span>
                                        <span class="badge bg-purple text-white font-monospace" id="livePreviewSpeakerRole"><?= htmlspecialchars($activeScenario['speaker_role'] ?: 'Mentor Féodal') ?></span>
                                    </div>
                                    <div class="fst-italic fs-4 mt-2" style="line-height:1.65; color: #f8fafc;" id="livePreviewDialogue">
                                        « <?= htmlspecialchars($activeScenario['dialogue'] ?: 'Dialogue solennel adressé au Seigneur...') ?> »
                                    </div>
                                </div>
                            </div>

                            <!-- Objectifs & Récompenses -->
                            <div class="row g-3 text-start small mb-4">
                                <div class="col-sm-6">
                                    <div class="sengoku-info-box p-3 h-100">
                                        <div class="fw-bold mb-1" style="color: #94a3b8;"><i class="fa-solid fa-bullseye text-warning me-1"></i>Conditions &amp; Objectif Requis :</div>
                                        <div class="fw-medium" style="color: #f8fafc;" id="livePreviewTrigger"><?= htmlspecialchars($activeScenario['trigger_condition'] ?: 'Conditions requises') ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="sengoku-info-box p-3 h-100">
                                        <div class="fw-bold mb-1" style="color: #94a3b8;"><i class="fa-solid fa-gift text-warning me-1"></i>Récompenses du Shōgunat :</div>
                                        <div class="fw-bold" style="color: #facc15;" id="livePreviewRewards"><?= htmlspecialchars($activeScenario['rewards_summary'] ?: 'Récompenses') ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center pt-1">
                                <button type="button" class="btn btn-warning fw-bold px-4 py-2 text-dark shadow fs-4">
                                    <i class="fa-solid fa-khanda me-2"></i>Décréter la Marche du Fief
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ───────────────────────────────────────────────────────────── -->
                    <!-- ONGLET 4 : VISIONNEUSE GDD MARKDOWN & SCHÉMAS                 -->
                    <!-- ───────────────────────────────────────────────────────────── -->
                    <div class="tab-pane fade" id="tab-pane-gdd" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h3 class="h4 m-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-brands fa-markdown text-purple fs-3"></i>
                                    <span>Visionneuse Interactive du Game Design Document (GDD)</span>
                                </h3>
                                <div class="text-secondary small">
                                    Généré en direct à partir de votre scénario et de la matrice des tâches métiers ci-dessus.
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-purple fw-bold" id="btnInlineModeRendered" onclick="setInlineGddMode('rendered')">
                                        <i class="fa-solid fa-eye me-1"></i>Aperçu Visuel &amp; Graphiques
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnInlineModeSplit" onclick="setInlineGddMode('split')">
                                        <i class="fa-solid fa-table-columns me-1"></i>Split Côte-à-Côte
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnInlineModeRaw" onclick="setInlineGddMode('raw')">
                                        <i class="fa-solid fa-code me-1"></i>Code Markdown (.md)
                                    </button>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="copyMarkdownFromSource('inlineGddRawTextarea', this)">
                                    <i class="fa-solid fa-copy me-1"></i>Copier Markdown
                                </button>
                                <button type="button" class="btn btn-outline-teal btn-sm fw-bold" onclick="downloadMarkdownFile('inlineGddRawTextarea')">
                                    <i class="fa-solid fa-download me-1"></i>Télécharger .md
                                </button>
                            </div>
                        </div>

                        <div class="row g-3" id="inlineGddContainerRow">
                            <div class="col-12" id="inlineGddRenderedCol">
                                <div id="inlineGddRenderedOutput" class="gdd-viewer-rendered shadow-sm"></div>
                            </div>
                            <div class="col-12 d-none" id="inlineGddRawCol">
                                <textarea id="inlineGddRawTextarea" class="form-control font-monospace small bg-dark text-light p-3" rows="24" readonly style="border-radius: 10px; line-height: 1.5;"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Pied de formulaire sticky -->
            <div class="card-footer bg-light-subtle py-3 px-4 d-flex justify-content-between align-items-center border-top flex-wrap gap-2">
                <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-1"></i>Annuler &amp; Retour au Catalogue
                </a>
                <div class="d-flex align-items-center gap-2 flex-wrap">
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
<!-- MODE 2 : CATALOGUE PRINCIPAL DES SCÉNARIOS & TABLEAU DE BORD GRAPHIQUE      -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<?php else:
    // Filtres catalogue
    $filterType = !empty($_GET['type']) && in_array($_GET['type'], ScenarioEngine::VALID_TYPES, true) ? $_GET['type'] : null;
    $filterStatus = !empty($_GET['status']) && in_array($_GET['status'], ScenarioEngine::VALID_STATUSES, true) ? $_GET['status'] : null;
    $searchQuery = trim($_GET['search'] ?? '');

    $scenarios = $scenarioEngine->getScenarios($filterType, $filterStatus, $searchQuery);
    $studioGlobalMetrics = $scenarioEngine->getGlobalStudioMetrics($scenarios);

    $totalCount = $studioGlobalMetrics['total_scenarios'];
    $stepsCount = $studioGlobalMetrics['steps_count'];
    $eventsCount = $studioGlobalMetrics['events_count'];
    $totalTasksDone = $studioGlobalMetrics['tasks_totals']['done'];
    $totalTasksInProgress = $studioGlobalMetrics['tasks_totals']['in_progress'];
    $totalTasksTodo = $studioGlobalMetrics['tasks_totals']['todo'];
    $totalTasksBlocked = $studioGlobalMetrics['tasks_totals']['blocked'];
    $totalTasksCount = $studioGlobalMetrics['tasks_totals']['total'];
    $globalPercent = $studioGlobalMetrics['global_percent'];
?>

<div class="gamestudio-narrative-scenarios-catalog">

    <!-- En-tête Catalogue -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="page-pretitle text-secondary text-uppercase fw-bold">Narrative Designer &bull; Scénarisation &amp; Pipeline Studio</div>
            <h2 class="card-title text-purple d-flex align-items-center gap-2 m-0" style="font-size:1.4rem;">
                <i class="fa-solid fa-book-journal-whills text-purple"></i>
                <span>Scénarios de Jeu &amp; Matrice des Tâches Métiers</span>
                <span class="badge bg-purple-lt text-purple font-monospace">Full Page Studio v2.1</span>
            </h2>
            <div class="text-secondary small mt-1">
                Scénarisation des étapes (steps) de la progression gameplay, conception des événements spéciaux, graphiques de production et visionneuse GDD Markdown.
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
            <div class="card card-sm shadow-sm border-start border-2 border-purple h-100">
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
            <div class="card card-sm shadow-sm border-start border-2 border-warning h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning-lt text-warning avatar">
                                <i class="fa-solid fa-torii-gate fs-3"></i>
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
            <div class="card card-sm shadow-sm border-start border-2 border-danger h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger-lt text-danger avatar">
                                <i class="fa-solid fa-calendar-check fs-3"></i>
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
            <div class="card card-sm shadow-sm border-start border-2 border-teal h-100">
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

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION GRAPHIQUE : VISUALISATIONS TÉLÉMÉTRIQUES & PIPELINE DES SCÉNARIOS   -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="row row-cards mb-4">
        <!-- Graphique 1 : Charge & Avancement des 10 Métiers du Studio -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                    <h4 class="card-title m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-bar text-purple"></i>
                        <span>Répartition &amp; Avancement par Métier (10 Corps de Métier)</span>
                    </h4>
                    <span class="badge bg-purple-lt text-purple font-monospace"><?= $totalTasksCount ?> tâches totales</span>
                </div>
                <div class="card-body p-3">
                    <!-- Conteneur ApexCharts + Barres Graphiques HTML/SVG Toujours Visibles -->
                    <div id="catalogRolesApexChart" style="min-height: 250px; width: 100%;" class="mb-2"></div>

                    <!-- Grille visuelle de secours / détail immédiat des 10 métiers -->
                    <div class="row g-2 pt-2 border-top" id="catalogRolesVisualBars">
                        <?php foreach ($studioGlobalMetrics['by_role'] as $rCode => $rStat): ?>
                            <div class="col-sm-6">
                                <div class="p-2 rounded border bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="fw-bold text-dark text-truncate" style="max-width: 170px;">
                                            <i class="fa-solid <?= $rStat['icon'] ?> text-<?= $rStat['color'] ?> me-1"></i><?= htmlspecialchars($rStat['name']) ?>
                                        </span>
                                        <span class="font-monospace small fw-bold text-secondary">
                                            <?= $rStat['done'] ?>/<?= $rStat['total'] ?> (<?= $rStat['percent'] ?>%)
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $rStat['color'] ?>" style="width: <?= $rStat['percent'] ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique 2 : Jauge Globale des Statuts & Complexités -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                    <h4 class="card-title m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-teal"></i>
                        <span>État du Pipeline &amp; Complexité des Tâches</span>
                    </h4>
                    <span class="badge bg-teal-lt text-teal font-monospace"><?= $globalPercent ?>% complété</span>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <!-- Donut Chart ApexCharts + Anneau SVG Natif -->
                    <div class="d-flex align-items-center justify-content-around flex-wrap gap-3 my-auto py-2">
                        <div id="catalogStatusDonutApex" style="min-height: 180px; width: 190px;" class="d-flex align-items-center justify-content-center">
                            <!-- Anneau SVG natif affiché immédiatement -->
                            <div class="position-relative d-flex align-items-center justify-content-center" style="width: 145px; height: 145px;">
                                <svg viewBox="0 0 36 36" class="w-100 h-100" style="transform: rotate(-90deg);">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#e2e8f0" stroke-width="3.8" />
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#0ca678" stroke-width="3.8"
                                          stroke-dasharray="<?= $globalPercent ?>, 100"
                                          stroke-linecap="round" />
                                </svg>
                                <div class="position-absolute text-center">
                                    <div class="h2 m-0 fw-bold text-dark font-monospace"><?= $globalPercent ?>%</div>
                                    <div class="small text-muted" style="font-size:0.7rem;">Livré</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex-grow-1" style="min-width: 170px;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <span class="small fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i>Terminé</span>
                                <span class="badge bg-success text-white font-monospace"><?= $totalTasksDone ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <span class="small fw-bold text-primary"><i class="fa-solid fa-gears me-1"></i>En cours</span>
                                <span class="badge bg-primary text-white font-monospace"><?= $totalTasksInProgress ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <span class="small fw-bold text-secondary"><i class="fa-regular fa-clock me-1"></i>À faire</span>
                                <span class="badge bg-secondary text-white font-monospace"><?= $totalTasksTodo ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-danger"><i class="fa-solid fa-ban me-1"></i>Bloqué</span>
                                <span class="badge bg-danger text-white font-monospace"><?= $totalTasksBlocked ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition par Complexité S / M / L / XL -->
                    <div class="pt-3 border-top mt-2">
                        <div class="small fw-bold text-secondary mb-2 text-uppercase" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-layer-group text-purple me-1"></i>Répartition par Complexité d'Ingénierie :
                        </div>
                        <div class="row g-2 text-center">
                            <?php
                            $compColors = ['S' => 'teal', 'M' => 'info', 'L' => 'warning', 'XL' => 'danger'];
                            $compLabels = ['S' => 'Court (S)', 'M' => 'Moyen (M)', 'L' => 'Important (L)', 'XL' => 'Majeur (XL)'];
                            foreach ($compColors as $cKey => $cCol):
                                $cVal = $studioGlobalMetrics['complexity'][$cKey] ?? 0;
                                $cPct = ($totalTasksCount > 0) ? (int)round(($cVal / $totalTasksCount) * 100) : 0;
                            ?>
                                <div class="col-3">
                                    <div class="p-2 rounded border bg-light-subtle">
                                        <div class="badge bg-<?= $cCol ?>-lt text-<?= $cCol ?> fw-bold mb-1"><?= $compLabels[$cKey] ?></div>
                                        <div class="fw-bold text-dark fs-4 font-monospace"><?= $cVal ?></div>
                                        <div class="text-muted" style="font-size: 0.68rem;"><?= $cPct ?>%</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Frise Graphique Interactive du Flow Narratif (Steps & Événements) -->
    <?php if (!empty($scenarios)): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h4 class="card-title m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-diagram-project text-purple"></i>
                <span>Schéma Graphique du Pipeline Narratif (Steps &amp; Événements Spéciaux)</span>
            </h4>
            <span class="text-muted small">Cliquez sur un nœud pour éditer ou prévisualiser son GDD Markdown</span>
        </div>
        <div class="card-body p-3 bg-light-subtle">
            <div class="scenario-pipeline-track">
                <?php foreach ($scenarios as $idx => $scNode):
                    $isStepNode = ($scNode['type'] === 'step');
                    $nodePct = (int)($scNode['metrics']['percent'] ?? 0);
                    $nodeColor = $isStepNode ? '#7c3aed' : '#e11d48';
                ?>
                    <?php if ($idx > 0): ?>
                        <div class="scenario-pipeline-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    <?php endif; ?>
                    <div class="scenario-pipeline-node shadow-sm d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <?php if ($isStepNode): ?>
                                    <span class="badge bg-purple text-white fw-bold font-monospace">
                                        ⛩️ Step #<?= (int)$scNode['step_number'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white fw-bold font-monospace">
                                        🏮 Événement
                                    </span>
                                <?php endif; ?>
                                <span class="badge bg-teal-lt text-teal font-monospace"><?= $nodePct ?>%</span>
                            </div>
                            <div class="fw-bold text-dark small mb-1 text-truncate" title="<?= htmlspecialchars($scNode['title']) ?>">
                                <?= htmlspecialchars($scNode['title']) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-comment-dots text-purple me-1"></i><?= htmlspecialchars($scNode['speaker_name'] ?? 'Narrateur') ?>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="progress mb-2" style="height: 4px;">
                                <div class="progress-bar" style="width: <?= $nodePct ?>%; background-color: <?= $nodeColor ?>;"></div>
                            </div>
                            <div class="d-flex justify-content-between gap-1">
                                <button type="button" class="btn btn-xs btn-outline-teal flex-grow-1 py-1" onclick="exportScenarioMarkdown(<?= (int)$scNode['id'] ?>)" title="Visionneuse GDD Markdown">
                                    <i class="fa-brands fa-markdown me-1"></i>GDD
                                </button>
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= (int)$scNode['id'] ?>" class="btn btn-xs btn-purple flex-grow-1 py-1">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Éditer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Barre de Filtrage & Recherche -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="narrative_scenarios">

                <div class="col-md-5 d-flex gap-1 flex-wrap">
                    <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-sm <?= empty($filterType) ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        Tous (<?= $totalCount ?>)
                    </a>
                    <a href="?page=gamestudio&tab=narrative_scenarios&type=step" class="btn btn-sm <?= ($filterType === 'step') ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-torii-gate me-1"></i>Steps Mode de Jeu
                    </a>
                    <a href="?page=gamestudio&tab=narrative_scenarios&type=special_event" class="btn btn-sm <?= ($filterType === 'special_event') ? 'btn-purple fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-calendar-check me-1"></i>Événements Spéciaux
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
                                        <i class="fa-solid fa-torii-gate me-1"></i>Step #<?= htmlspecialchars((string)$sc['step_number']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white fw-bold">
                                        <i class="fa-solid fa-calendar-check me-1"></i>Événement Spécial
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
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= (int)$sc['id'] ?>" class="text-dark text-decoration-none hover-purple">
                                    <?= htmlspecialchars($sc['title']) ?>
                                </a>
                            </h3>
                            <?php if (!empty($sc['subtitle'])): ?>
                                <div class="text-secondary small fw-medium mb-2 fst-italic">
                                    <?= htmlspecialchars($sc['subtitle']) ?>
                                </div>
                            <?php endif; ?>

                            <p class="text-secondary small mb-3" style="line-height:1.5;">
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
                                <div class="small text-muted fst-italic ps-3 border-start border-2 border-purple ms-2">
                                    « <?= htmlspecialchars(mb_substr($sc['dialogue'] ?? '', 0, 140)) ?><?= mb_strlen($sc['dialogue'] ?? '') > 140 ? '...' : '' ?> »
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
                                ID #<?= (int)$sc['id'] ?> &bull; <?= htmlspecialchars($sc['slug']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-teal btn-sm p-1 px-2 fw-bold" title="Visionneuse GDD Markdown & Graphiques" onclick="exportScenarioMarkdown(<?= (int)$sc['id'] ?>)">
                                    <i class="fa-brands fa-markdown me-1"></i>GDD
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" title="Dupliquer" onclick="duplicateCurrentScenario(<?= (int)$sc['id'] ?>)">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= (int)$sc['id'] ?>" class="btn btn-purple btn-sm fw-bold d-inline-flex align-items-center gap-1">
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
<!-- MODALE INTERACTIVE : VISIONNEUSE GDD MARKDOWN (APERÇU RENDU + CODE BRUT)    -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalScenarioExport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0 text-white">
                    <i class="fa-brands fa-markdown text-teal fs-3"></i>
                    <span id="modalGddTitleLabel">Visionneuse Game Design Document (GDD / Markdown)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light-subtle">
                <!-- Barre d'outils de la Visionneuse Markdown -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 bg-white p-2 rounded border shadow-sm">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-purple fw-bold" id="btnModalModeRendered" onclick="setModalGddMode('rendered')">
                            <i class="fa-solid fa-eye me-1"></i>Aperçu Visuel &amp; Graphiques
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnModalModeSplit" onclick="setModalGddMode('split')">
                            <i class="fa-solid fa-table-columns me-1"></i>Split Côte-à-Côte
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnModalModeRaw" onclick="setModalGddMode('raw')">
                            <i class="fa-solid fa-code me-1"></i>Code Markdown Brut (.md)
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="copyMarkdownFromSource('scenarioExportTextarea', this)">
                            <i class="fa-solid fa-copy me-1"></i>Copier le GDD
                        </button>
                        <button type="button" class="btn btn-outline-teal btn-sm fw-bold" onclick="downloadMarkdownFile('scenarioExportTextarea')">
                            <i class="fa-solid fa-download me-1"></i>Télécharger .md
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printModalGddDocument()">
                            <i class="fa-solid fa-print me-1"></i>Imprimer
                        </button>
                    </div>
                </div>

                <!-- Zone de Contenu (Aperçu HTML Rendu + Code Source Markdown) -->
                <div class="row g-3" id="modalGddContentRow">
                    <div class="col-12" id="modalGddRenderedCol">
                        <div id="scenarioExportRendered" class="gdd-viewer-rendered shadow-sm"></div>
                    </div>
                    <div class="col-12 d-none" id="modalGddRawCol">
                        <textarea id="scenarioExportTextarea" class="form-control font-monospace small bg-dark text-light p-3" rows="22" readonly style="border-radius: 10px; line-height: 1.5;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-white">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer la visionneuse</button>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire POST caché pour actions rapides (duplication, suppression directe) -->
<form id="catalogQuickActionForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="action" id="catQuickActionName" value="">
    <input type="hidden" name="scenario_id" id="catQuickScenarioId" value="0">
    <input type="hidden" name="return_action" value="<?= htmlspecialchars($viewAction) ?>">
</form>

<script>
// ═══════════════════════════════════════════════════════════════════════════
// DONNÉES PRÉCHARGÉES & RÉFÉRENTIEL DES 10 MÉTIERS DU STUDIO
// ═══════════════════════════════════════════════════════════════════════════
const ROLES_DEF_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;
const PRELOADED_MARKDOWN_MAP = <?= json_encode($preloadedMarkdownMap, JSON_UNESCAPED_UNICODE) ?>;
const PRELOADED_SCENARIOS_MAP = <?= json_encode($preloadedScenariosMap, JSON_UNESCAPED_UNICODE) ?>;
<?php if (isset($studioGlobalMetrics)): ?>
const STUDIO_GLOBAL_METRICS = <?= json_encode($studioGlobalMetrics, JSON_UNESCAPED_UNICODE) ?>;
<?php else: ?>
const STUDIO_GLOBAL_METRICS = null;
<?php endif; ?>

// ═══════════════════════════════════════════════════════════════════════════
// PARSEUR & RENDU VISUEL MARKDOWN GDD + BLOCS GRAPHIQUES INTÉGRÉS
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

    // Code inline `...` avec détection des barres de progression ASCII █░
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
            const colMap = { S: 'teal', M: 'info', L: 'warning', XL: 'danger' };
            return `<span class="badge bg-${colMap[codeContent] || 'secondary'}-lt text-${colMap[codeContent] || 'secondary'} fw-bold font-monospace">${codeContent}</span>`;
        }
        return `<code>${codeContent}</code>`;
    });

    // Gras **...**
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    // Italique *...*
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');

    // Badges de statut automatiques
    html = html.replace(/✅ Terminé/g, '<span class="badge bg-success text-white">✅ Terminé</span>');
    html = html.replace(/🔧 En cours/g, '<span class="badge bg-primary text-white">🔧 En cours</span>');
    html = html.replace(/⛔ Bloqué/g, '<span class="badge bg-danger text-white">⛔ Bloqué</span>');
    html = html.replace(/⏳ À faire/g, '<span class="badge bg-secondary-lt text-secondary">⏳ À faire</span>');

    return html;
}

/**
 * Convertit un document Markdown GDD en HTML riche avec graphiques SVG intégrés
 */
function renderMarkdownToGddHtml(mdText, scenarioObj = null) {
    if (!mdText || !mdText.trim()) {
        return '<div class="text-muted text-center py-4">Aucun contenu Markdown à afficher.</div>';
    }

    const lines = mdText.replace(/\r\n/g, '\n').split('\n');
    let htmlOut = '';
    let inList = false;
    let inBlockquote = false;
    let blockquoteLines = [];
    let inTable = false;
    let tableRows = [];

    function flushList() {
        if (inList) {
            htmlOut += '</ul>';
            inList = false;
        }
    }

    function flushBlockquote() {
        if (inBlockquote) {
            htmlOut += '<blockquote>' + blockquoteLines.join('<br>') + '</blockquote>';
            inBlockquote = false;
            blockquoteLines = [];
        }
    }

    function flushTable() {
        if (!inTable || tableRows.length === 0) return;
        let tHtml = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        tableRows.forEach((rowCells, rIdx) => {
            if (rIdx === 0) {
                tHtml += '<thead><tr>';
                rowCells.forEach(c => { tHtml += `<th>${formatInlineMarkdown(c)}</th>`; });
                tHtml += '</tr></thead><tbody>';
            } else if (rIdx === 1 && rowCells.every(c => /^[-:\s]+$/.test(c))) {
                // Ligne de séparation Markdown |---|---|
            } else {
                tHtml += '<tr>';
                rowCells.forEach(c => { tHtml += `<td>${formatInlineMarkdown(c)}</td>`; });
                tHtml += '</tr>';
            }
        });
        tHtml += '</tbody></table></div>';
        htmlOut += tHtml;
        inTable = false;
        tableRows = [];
    }

    // Insertion d'un bandeau graphique visuel en haut du GDD si les métriques sont disponibles
    let visualHeaderInjected = false;

    for (let i = 0; i < lines.length; i++) {
        const rawLine = lines[i];
        const trimmed = rawLine.trim();

        // Détection Tableau Markdown
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

        // Détection Blockquote
        if (trimmed.startsWith('>')) {
            flushList();
            inBlockquote = true;
            const cleanBq = trimmed.replace(/^>\s?/, '');
            blockquoteLines.push(formatInlineMarkdown(cleanBq));
            continue;
        } else if (inBlockquote) {
            flushBlockquote();
        }

        // Détection Liste
        if (trimmed.startsWith('- ')) {
            if (!inList) {
                htmlOut += '<ul class="mb-3">';
                inList = true;
            }
            htmlOut += `<li class="mb-1">${formatInlineMarkdown(trimmed.slice(2))}</li>`;
            continue;
        } else if (inList) {
            flushList();
        }

        if (trimmed === '') {
            continue;
        }

        if (trimmed === '---') {
            htmlOut += '<hr>';
            continue;
        }

        if (trimmed.startsWith('# ')) {
            htmlOut += `<h1>${formatInlineMarkdown(trimmed.slice(2))}</h1>`;
            if (!visualHeaderInjected && scenarioObj) {
                htmlOut += buildGddGraphicalDiagramHtml(scenarioObj);
                visualHeaderInjected = true;
            }
            continue;
        }

        if (trimmed.startsWith('## ')) {
            htmlOut += `<h2>${formatInlineMarkdown(trimmed.slice(3))}</h2>`;
            continue;
        }

        if (trimmed.startsWith('### ')) {
            htmlOut += `<h3>${formatInlineMarkdown(trimmed.slice(4))}</h3>`;
            continue;
        }

        htmlOut += `<p class="mb-2">${formatInlineMarkdown(trimmed)}</p>`;
    }

    flushList();
    flushBlockquote();
    flushTable();

    return htmlOut;
}

/**
 * Construit le bloc graphique SVG (Anneau de progression + Schéma de flux narratif) intégré dans la visionneuse GDD
 */
function buildGddGraphicalDiagramHtml(sc) {
    const tasks = sc.tasks || [];
    const total = tasks.length;
    let done = 0, inProgress = 0, blocked = 0, todo = 0;
    tasks.forEach(t => {
        if (t.status === 'done') done++;
        else if (t.status === 'in_progress') inProgress++;
        else if (t.status === 'blocked') blocked++;
        else todo++;
    });
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    const isStep = (sc.type === 'step');
    const stepBadge = isStep ? `⛩️ Étape de Campagne (Step #${sc.step_number || 1})` : `🏮 Événement Spécial`;

    return `
    <div class="p-3 mb-4 rounded-3 border bg-light-subtle">
        <div class="row g-3 align-items-center">
            <div class="col-md-4 d-flex align-items-center gap-3 border-end">
                <div class="position-relative d-flex align-items-center justify-content-center flex-shrink-0" style="width: 78px; height: 78px;">
                    <svg viewBox="0 0 36 36" class="w-100 h-100" style="transform: rotate(-90deg);">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e2e8f0" stroke-width="4" />
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#7c3aed" stroke-width="4" stroke-dasharray="${pct}, 100" stroke-linecap="round" />
                    </svg>
                    <span class="position-absolute fw-bold font-monospace small text-dark">${pct}%</span>
                </div>
                <div>
                    <div class="badge bg-purple text-white mb-1">${escapeHtmlStr(stepBadge)}</div>
                    <div class="small fw-bold text-dark">${done}/${total} tâches terminées</div>
                    <div class="text-muted" style="font-size:0.75rem;">${inProgress} en cours &bull; ${todo} à faire &bull; ${blocked} bloquées</div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="small fw-bold text-uppercase text-secondary mb-2" style="font-size:0.7rem;">
                    <i class="fa-solid fa-diagram-project text-purple me-1"></i>Architecture &amp; Flux d'Exécution du Scénario :
                </div>
                <div class="d-flex align-items-center justify-content-between gap-2 text-center flex-wrap">
                    <div class="p-2 bg-white rounded border flex-fill">
                        <div class="text-warning fw-bold small"><i class="fa-solid fa-bolt me-1"></i>1. Trigger</div>
                        <div class="text-muted text-truncate" style="font-size:0.72rem; max-width:150px;" title="${escapeHtmlStr(sc.trigger_condition || '')}">${escapeHtmlStr(sc.trigger_condition || 'Condition')}</div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-muted"></i>
                    <div class="p-2 bg-white rounded border flex-fill">
                        <div class="text-purple fw-bold small"><i class="fa-solid fa-comment-dots me-1"></i>2. Narration PNJ</div>
                        <div class="text-muted text-truncate" style="font-size:0.72rem; max-width:150px;">${escapeHtmlStr(sc.speaker_name || 'Mentor')}</div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-muted"></i>
                    <div class="p-2 bg-white rounded border flex-fill">
                        <div class="text-teal fw-bold small"><i class="fa-solid fa-gift me-1"></i>3. Récompense</div>
                        <div class="text-muted text-truncate" style="font-size:0.72rem; max-width:150px;" title="${escapeHtmlStr(sc.rewards_summary || '')}">${escapeHtmlStr(sc.rewards_summary || 'Récompenses')}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

// ═══════════════════════════════════════════════════════════════════════════
// GÉNÉRATION MARKDOWN LIVE DEPUIS L'ÉDITEUR PLEINE PAGE
// ═══════════════════════════════════════════════════════════════════════════
function collectLiveScenarioFromEditorForm() {
    const form = document.getElementById('fullPageScenarioForm');
    if (!form) return null;

    const type = document.getElementById('fullScenType')?.value || 'step';
    const stepNumber = document.getElementById('fullScenStepNumber')?.value || '1';
    const status = document.getElementById('fullScenStatus')?.value || 'draft';
    const slug = document.getElementById('fullScenSlug')?.value || 'nouveau-scenario';
    const era = document.getElementById('fullScenEra')?.value || '1560 - Sengoku Jidai';
    const trigger = document.getElementById('fullScenTrigger')?.value || '';
    const title = document.getElementById('fullScenTitle')?.value || 'Nouveau Scénario';
    const subtitle = document.getElementById('fullScenSubtitle')?.value || '';
    const synopsis = document.getElementById('fullScenSynopsis')?.value || '';
    const speakerName = document.getElementById('fullScenSpeakerName')?.value || 'Maître de la Sagesse';
    const speakerRole = document.getElementById('fullScenSpeakerRole')?.value || 'Mentor Féodal';
    const dialogue = document.getElementById('fullScenDialogue')?.value || '';
    const rewards = document.getElementById('fullScenRewards')?.value || '';

    const tasks = [];
    const cards = document.querySelectorAll('#rolesSectionsContainer .task-card-item');
    cards.forEach((card, idx) => {
        tasks.push({
            task_id: card.querySelector('.task-id-input')?.value || (idx + 1),
            role_code: card.getAttribute('data-role') || 'level_designer',
            role_name: card.querySelector('.task-role-name-input')?.value || 'Métier',
            title: card.querySelector('.task-title-input')?.value || '',
            assignee: card.querySelector('.task-assignee-input')?.value || '',
            complexity: card.querySelector('.task-complexity-select')?.value || 'M',
            status: card.querySelector('.task-status-select')?.value || 'todo',
            description: card.querySelector('.task-desc-input')?.value || '',
            deliverables: card.querySelector('.task-deliverables-input')?.value || ''
        });
    });

    return {
        type, step_number: stepNumber, status, slug, era,
        trigger_condition: trigger, title, subtitle, synopsis,
        speaker_name: speakerName, speaker_role: speakerRole,
        dialogue, rewards_summary: rewards, tasks
    };
}

function generateMarkdownFromScenarioObject(sc) {
    const typeLabel = (sc.type === 'step') ? `Étape de Jeu (Step #${sc.step_number || 1})` : 'Événement Spécial';
    const tasks = sc.tasks || [];
    const total = tasks.length;
    let done = 0, inProgress = 0, blocked = 0;
    const byRole = {};
    Object.keys(ROLES_DEF_MAP).forEach(rKey => {
        byRole[rKey] = { total: 0, done: 0, percent: 0 };
    });

    tasks.forEach(t => {
        const rCode = t.role_code || 'level_designer';
        if (!byRole[rCode]) byRole[rCode] = { total: 0, done: 0, percent: 0 };
        byRole[rCode].total++;
        if (t.status === 'done') {
            done++;
            byRole[rCode].done++;
        } else if (t.status === 'in_progress') {
            inProgress++;
        } else if (t.status === 'blocked') {
            blocked++;
        }
    });

    const globalPct = total > 0 ? Math.round((done / total) * 100) : 0;
    const cleanDialogue = (sc.dialogue || '').replace(/\r\n/g, '\n').trim().replace(/\n/g, '\n> ');

    let md = `# 📜 CAHIER DE SCÉNARISATION & GDD : ${sc.title || 'Sans Titre'}\n\n`;
    if (sc.subtitle) {
        md += `*${sc.subtitle}*\n\n`;
    }
    md += `- **Type** : ${typeLabel}\n`;
    md += `- **Identifiant Technique** : \`${sc.slug || 'scenario'}\`\n`;
    md += `- **Période Historique** : ${sc.era || 'Sengoku Jidai'}\n`;
    md += `- **Statut de Production** : \`${String(sc.status || 'draft').toUpperCase()}\`\n`;
    md += `- **Avancement Global des Tâches Métiers** : **${globalPct}%** (${done}/${total} terminées, ${inProgress} en cours, ${blocked} bloquées)\n\n`;

    md += `## 1. Trame Narrative & Synopsis\n\n${sc.synopsis || 'À rédiger...'}\n\n`;
    md += `## 2. Voix Narrative & Discours In-Game\n\n`;
    md += `> **${sc.speaker_name || 'Narrateur'}** (*${sc.speaker_role || 'Mentor'}*)\n>\n> « ${cleanDialogue} »\n\n`;

    md += `## 3. Déclencheurs & Conséquences de Jeu\n\n`;
    md += `- **Conditions d'Activation (Triggers)** : ${sc.trigger_condition || 'Non défini'}\n`;
    md += `- **Récompenses & Effets Gameplay** : ${sc.rewards_summary || 'Non défini'}\n\n`;

    md += `## 4. Synthèse Graphique par Métier\n\n`;
    md += `| Métier du Studio | Domaine | Progression | Tâches (Terminées / Total) |\n`;
    md += `|---|---|---|---|\n`;
    Object.keys(ROLES_DEF_MAP).forEach(rCode => {
        const rDef = ROLES_DEF_MAP[rCode];
        const rStat = byRole[rCode] || { total: 0, done: 0 };
        const rPct = rStat.total > 0 ? Math.round((rStat.done / rStat.total) * 100) : 0;
        const filled = Math.round((rPct / 100) * 10);
        const bar = '█'.repeat(filled) + '░'.repeat(Math.max(0, 10 - filled));
        md += `| **${rDef.name}** | ${rDef.domain} | \`${bar}\` **${rPct}%** | ${rStat.done} / ${rStat.total} |\n`;
    });
    md += `\n`;

    md += `## 5. Matrice Opérationnelle des Tâches Métiers\n\n`;
    md += `| # | Métier Clé | Tâche Opérationnelle | Complexité | Assigné | Statut | Livrables |\n`;
    md += `|---|---|---|---|---|---|---|\n`;
    tasks.forEach(t => {
        const stBadge = t.status === 'done' ? '✅ Terminé' : (t.status === 'in_progress' ? '🔧 En cours' : (t.status === 'blocked' ? '⛔ Bloqué' : '⏳ À faire'));
        const safeTitle = String(t.title || '').replace(/\|/g, '/');
        const safeDeliv = String(t.deliverables || '').replace(/\|/g, '/');
        md += `| ${t.task_id} | **${t.role_name}** | ${safeTitle} | \`${t.complexity || 'M'}\` | ${t.assignee || ''} | ${stBadge} | \`${safeDeliv}\` |\n`;
    });

    md += `\n---\n*Généré automatiquement par OpenShogun Game Studio (Narrative Designer)*\n`;
    return md;
}

// ═══════════════════════════════════════════════════════════════════════════
// GESTION DES MODES D'AFFICHAGE DE LA VISIONNEUSE MARKDOWN (MODALE & ONGLET 4)
// ═══════════════════════════════════════════════════════════════════════════
function setModalGddMode(mode) {
    const renderedCol = document.getElementById('modalGddRenderedCol');
    const rawCol = document.getElementById('modalGddRawCol');
    const bRend = document.getElementById('btnModalModeRendered');
    const bSplit = document.getElementById('btnModalModeSplit');
    const bRaw = document.getElementById('btnModalModeRaw');

    [bRend, bSplit, bRaw].forEach(b => { if (b) b.className = 'btn btn-outline-secondary'; });

    if (mode === 'raw') {
        if (bRaw) bRaw.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-12 d-none';
        rawCol.className = 'col-12';
    } else if (mode === 'split') {
        if (bSplit) bSplit.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-lg-6';
        rawCol.className = 'col-lg-6';
    } else {
        if (bRend) bRend.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-12';
        rawCol.className = 'col-12 d-none';
    }
}

function setInlineGddMode(mode) {
    const renderedCol = document.getElementById('inlineGddRenderedCol');
    const rawCol = document.getElementById('inlineGddRawCol');
    const bRend = document.getElementById('btnInlineModeRendered');
    const bSplit = document.getElementById('btnInlineModeSplit');
    const bRaw = document.getElementById('btnInlineModeRaw');

    [bRend, bSplit, bRaw].forEach(b => { if (b) b.className = 'btn btn-outline-secondary'; });

    if (mode === 'raw') {
        if (bRaw) bRaw.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-12 d-none';
        rawCol.className = 'col-12';
    } else if (mode === 'split') {
        if (bSplit) bSplit.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-lg-6';
        rawCol.className = 'col-lg-6';
    } else {
        if (bRend) bRend.className = 'btn btn-purple fw-bold';
        renderedCol.className = 'col-12';
        rawCol.className = 'col-12 d-none';
    }
}

function refreshInlineEditorGddViewer() {
    const liveSc = collectLiveScenarioFromEditorForm();
    if (!liveSc) return;
    const md = generateMarkdownFromScenarioObject(liveSc);
    const rawTa = document.getElementById('inlineGddRawTextarea');
    const rendDiv = document.getElementById('inlineGddRenderedOutput');
    if (rawTa) rawTa.value = md;
    if (rendDiv) rendDiv.innerHTML = renderMarkdownToGddHtml(md, liveSc);
}

function openLiveEditorMarkdownModal() {
    const liveSc = collectLiveScenarioFromEditorForm();
    if (!liveSc) return;
    const md = generateMarkdownFromScenarioObject(liveSc);
    displayMarkdownInModal(md, liveSc);
}

function displayMarkdownInModal(md, scenarioObj = null) {
    const ta = document.getElementById('scenarioExportTextarea');
    const rendered = document.getElementById('scenarioExportRendered');
    const titleLbl = document.getElementById('modalGddTitleLabel');

    if (ta) ta.value = md;
    if (rendered) rendered.innerHTML = renderMarkdownToGddHtml(md, scenarioObj);
    if (titleLbl && scenarioObj && scenarioObj.title) {
        titleLbl.textContent = `Visionneuse GDD Markdown : ${scenarioObj.title}`;
    }

    setModalGddMode('rendered');
    const modalEl = document.getElementById('modalScenarioExport');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

// Export GDD Markdown depuis le catalogue ou l'éditeur (avec préchargement instantané + fallback fetch)
function exportScenarioMarkdown(scId) {
    const scObj = PRELOADED_SCENARIOS_MAP[scId] || null;
    if (PRELOADED_MARKDOWN_MAP[scId]) {
        displayMarkdownInModal(PRELOADED_MARKDOWN_MAP[scId], scObj);
        return;
    }

    fetch(`/index.php?page=gamestudio&tab=narrative_scenarios&action=export_markdown&scenario_id=${scId}`)
        .then(res => res.text())
        .then(md => {
            displayMarkdownInModal(md, scObj);
        })
        .catch(err => {
            alert('Erreur lors du chargement du GDD Markdown : ' + err);
        });
}

function copyMarkdownFromSource(textareaId, btnEl = null) {
    const ta = document.getElementById(textareaId);
    if (!ta) return;
    navigator.clipboard.writeText(ta.value).then(() => {
        if (btnEl) {
            const origHtml = btnEl.innerHTML;
            btnEl.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Copié !';
            setTimeout(() => { btnEl.innerHTML = origHtml; }, 2000);
        }
    });
}

function downloadMarkdownFile(textareaId) {
    const ta = document.getElementById(textareaId);
    if (!ta) return;
    const slugInput = document.getElementById('fullScenSlug');
    const filename = (slugInput && slugInput.value ? slugInput.value : 'scenario-gdd') + '.md';
    const blob = new Blob([ta.value], { type: 'text/markdown;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function printModalGddDocument() {
    const rendered = document.getElementById('scenarioExportRendered');
    if (!rendered) return;
    const win = window.open('', '_blank', 'width=960,height=800');
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>GDD - OpenShogun</title>
        <link rel="stylesheet" href="/public/css/tabler/tabler.min.css">
        <style>body{padding:2rem;font-family:sans-serif;color:#0f172a;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #cbd5e1;padding:8px;}</style>
        </head><body>${rendered.innerHTML}<script>window.onload=function(){window.print();}<\/script></body></html>`);
    win.document.close();
}

// ═══════════════════════════════════════════════════════════════════════════
// FONCTIONS DE L'ÉDITEUR PLEINE PAGE & MULTI-TÂCHES DYNAMIQUES
// ═══════════════════════════════════════════════════════════════════════════
function toggleStepNumberFieldFullPage() {
    const typeSelect = document.getElementById('fullScenType');
    const group = document.getElementById('stepNumberGroupFullPage');
    if (typeSelect && group) {
        group.style.display = (typeSelect.value === 'step') ? 'block' : 'none';
    }
}

function submitFullPageForm(redirectTo) {
    const form = document.getElementById('fullPageScenarioForm');
    if (!form) return;
    document.getElementById('formRedirectTo').value = redirectTo || 'edit';
    form.submit();
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        const form = document.getElementById('fullPageScenarioForm');
        if (form) {
            e.preventDefault();
            submitFullPageForm('edit');
        }
    }
});

function addNewTaskForRole(roleCode) {
    const rDef = ROLES_DEF_MAP[roleCode] || { name: 'Métier', icon: 'fa-gear', color: 'secondary' };
    const listContainer = document.getElementById(`roleTasksList_${roleCode}`);
    if (!listContainer) return;

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
                <span class="fw-bold text-dark">${escapeHtmlStr(rDef.name)}</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="small text-muted fw-bold m-0">Complexité :</label>
                <select class="form-select form-select-sm task-complexity-select" name="tasks[${uniqueTaskId}][complexity]" style="width: auto;" onchange="updateRoleTelemetry('${roleCode}')">
                    <option value="S">S (Court)</option>
                    <option value="M" selected>M (Moyen)</option>
                    <option value="L">L (Important)</option>
                    <option value="XL">XL (Majeur)</option>
                </select>

                <label class="small text-muted fw-bold ms-2 m-0">Statut :</label>
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

        <input type="hidden" name="tasks[${uniqueTaskId}][task_id]" value="${uniqueTaskId}" class="task-id-input">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_code]" value="${roleCode}" class="task-role-code-input">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_name]" value="${escapeHtmlStr(rDef.name)}" class="task-role-name-input">

        <div class="row g-2">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-dark m-0">Titre Opérationnel de la Tâche :</label>
                <input type="text" class="form-control form-control-sm task-title-input" name="tasks[${uniqueTaskId}][title]" value="Nouvelle tâche pour ${escapeHtmlStr(rDef.name)}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold text-dark m-0">Artisan / Développeur Assigné :</label>
                <input type="text" class="form-control form-control-sm task-assignee-input" name="tasks[${uniqueTaskId}][assignee]" value="${escapeHtmlStr(rDef.name)}">
            </div>
            <div class="col-12 mt-2">
                <label class="form-label small fw-bold text-dark m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                <textarea class="form-control form-control-sm task-desc-input" name="tasks[${uniqueTaskId}][description]" rows="2" placeholder="Spécifications...">${escapeHtmlStr(rDef.desc || '')}</textarea>
            </div>
            <div class="col-12 mt-1">
                <label class="form-label small fw-bold text-dark m-0">Livrables / Fichiers Attendus :</label>
                <input type="text" class="form-control form-control-sm font-monospace task-deliverables-input" name="tasks[${uniqueTaskId}][deliverables]" value="Livrable attendu">
            </div>
        </div>
    `;

    listContainer.appendChild(card);
    updateRoleTelemetry(roleCode);
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeTaskCard(taskId, roleCode) {
    const card = document.getElementById(`taskCard_${taskId}`);
    if (card) {
        card.remove();
        updateRoleTelemetry(roleCode);
    }
}

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

    // Mise à jour de la mini-barre graphique de l'onglet 2
    const miniLabel = document.getElementById(`miniChartLabel_${roleCode}`);
    if (miniLabel) {
        miniLabel.textContent = `${done}/${total} (${percent}%)`;
    }
    const miniBar = document.getElementById(`miniChartBar_${roleCode}`);
    if (miniBar) {
        miniBar.style.width = `${percent}%`;
    }

    updateGlobalTasksTelemetry();
}

function updateGlobalTasksTelemetry() {
    const allCards = document.querySelectorAll('#rolesSectionsContainer .task-card-item');
    const total = allCards.length;
    let done = 0, inProgress = 0, todo = 0, blocked = 0;

    allCards.forEach(c => {
        const sel = c.querySelector('.task-status-select');
        const st = sel ? sel.value : 'todo';
        if (st === 'done') done++;
        else if (st === 'in_progress') inProgress++;
        else if (st === 'blocked') blocked++;
        else todo++;
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
    const totalChartBadge = document.getElementById('editorTotalTasksChartBadge');
    if (totalChartBadge) {
        totalChartBadge.textContent = `${total} tâches`;
    }

    // Mise à jour de l'anneau SVG et des compteurs temps réel
    const svgCircle = document.getElementById('editorSvgDonutCircle');
    if (svgCircle) {
        svgCircle.setAttribute('stroke-dasharray', `${percent}, 100`);
    }
    const pctText = document.getElementById('editorDonutPercentText');
    if (pctText) {
        pctText.textContent = `${percent}%`;
    }
    const cDone = document.getElementById('editorCountDone');
    if (cDone) cDone.textContent = done;
    const cProg = document.getElementById('editorCountProgress');
    if (cProg) cProg.textContent = inProgress;
    const cTodo = document.getElementById('editorCountTodo');
    if (cTodo) cTodo.textContent = todo;
    const cBlk = document.getElementById('editorCountBlocked');
    if (cBlk) cBlk.textContent = blocked;
}

function refreshEditorGraphicalCharts() {
    Object.keys(ROLES_DEF_MAP).forEach(rCode => updateRoleTelemetry(rCode));
}

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

function renderInGamePreviewLive() {
    const type = document.getElementById('fullScenType')?.value || 'step';
    const stepNum = document.getElementById('fullScenStepNumber')?.value || '1';
    const title = document.getElementById('fullScenTitle')?.value || 'Titre du Scénario';
    const subtitle = document.getElementById('fullScenSubtitle')?.value || '';
    const synopsis = document.getElementById('fullScenSynopsis')?.value || '';
    const era = document.getElementById('fullScenEra')?.value || '1560 - Sengoku Jidai';
    const speaker = document.getElementById('fullScenSpeakerName')?.value || 'Maître de la Sagesse';
    const role = document.getElementById('fullScenSpeakerRole')?.value || 'Mentor Féodal';
    const dialogue = document.getElementById('fullScenDialogue')?.value || '...';
    const trigger = document.getElementById('fullScenTrigger')?.value || 'Objectifs requis';
    const rewards = document.getElementById('fullScenRewards')?.value || 'Récompenses du Shōgunat';

    const b = document.getElementById('livePreviewBadge');
    if (b) b.textContent = (type === 'step') ? `⛩️ Step #${stepNum} • Mode Campagne` : `🏮 Événement Spécial`;

    const t = document.getElementById('livePreviewTitle');
    if (t) t.textContent = title;

    const st = document.getElementById('livePreviewSubtitle');
    if (st) st.textContent = subtitle;

    const syn = document.getElementById('livePreviewSynopsis');
    if (syn) syn.textContent = synopsis;

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

function duplicateCurrentScenario(scId) {
    if (!confirm('Dupliquer ce scénario avec l\'ensemble de ses tâches métiers ?')) return;
    document.getElementById('catQuickActionName').value = 'duplicate_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

function deleteCurrentScenario(scId) {
    if (!confirm(`Supprimer définitivement le scénario #${scId} et toutes ses tâches ? Cette action est irréversible.`)) return;
    document.getElementById('catQuickActionName').value = 'delete_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALISATION DES GRAPHIQUES APEXCHARTS DU CATALOGUE
// ═══════════════════════════════════════════════════════════════════════════
function initCatalogApexCharts() {
    if (!STUDIO_GLOBAL_METRICS || typeof ApexCharts === 'undefined') return;

    // 1. Graphique Barres Horizontales Empilées par Métier
    const rolesChartEl = document.getElementById('catalogRolesApexChart');
    if (rolesChartEl && STUDIO_GLOBAL_METRICS.by_role) {
        const rolesArr = Object.values(STUDIO_GLOBAL_METRICS.by_role);
        const categories = rolesArr.map(r => r.name);
        const doneSeries = rolesArr.map(r => Number(r.done) || 0);
        const progressSeries = rolesArr.map(r => Number(r.in_progress) || 0);
        const todoSeries = rolesArr.map(r => Number(r.todo) || 0);
        const blockedSeries = rolesArr.map(r => Number(r.blocked) || 0);

        try {
            const rolesChart = new ApexCharts(rolesChartEl, {
                series: [
                    { name: 'Terminé', data: doneSeries },
                    { name: 'En cours', data: progressSeries },
                    { name: 'À faire', data: todoSeries },
                    { name: 'Bloqué', data: blockedSeries }
                ],
                chart: {
                    type: 'bar',
                    height: 255,
                    stacked: true,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '65%',
                        borderRadius: 3
                    }
                },
                colors: ['#2fb344', '#206bc4', '#94a3b8', '#d63939'],
                xaxis: {
                    categories: categories,
                    labels: { style: { fontSize: '11px', colors: '#64748b' } }
                },
                yaxis: {
                    labels: { style: { fontSize: '11px', colors: '#1e293b', fontWeight: 600 }, maxWidth: 165 }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px'
                },
                grid: {
                    borderColor: 'rgba(148, 163, 184, 0.15)',
                    strokeDashArray: 4
                },
                dataLabels: { enabled: false }
            });
            rolesChart.render();
        } catch (e) {
            console.warn('ApexCharts Roles bar fallback:', e);
        }
    }

    // 2. Graphique Donut des Statuts
    const donutEl = document.getElementById('catalogStatusDonutApex');
    if (donutEl && STUDIO_GLOBAL_METRICS.tasks_totals && STUDIO_GLOBAL_METRICS.tasks_totals.total > 0) {
        const totals = STUDIO_GLOBAL_METRICS.tasks_totals;
        try {
            donutEl.innerHTML = '';
            const donutChart = new ApexCharts(donutEl, {
                series: [
                    Number(totals.done) || 0,
                    Number(totals.in_progress) || 0,
                    Number(totals.todo) || 0,
                    Number(totals.blocked) || 0
                ],
                labels: ['Terminé', 'En cours', 'À faire', 'Bloqué'],
                chart: {
                    type: 'donut',
                    height: 195,
                    fontFamily: 'inherit'
                },
                colors: ['#2fb344', '#206bc4', '#94a3b8', '#d63939'],
                legend: { show: false },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Complété',
                                    formatter: () => `${STUDIO_GLOBAL_METRICS.global_percent}%`
                                }
                            }
                        }
                    }
                }
            });
            donutChart.render();
        } catch (e) {
            console.warn('ApexCharts Donut fallback:', e);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initCatalogApexCharts();
    if (document.getElementById('fullPageScenarioForm')) {
        refreshInlineEditorGddViewer();
    }
});
</script>
