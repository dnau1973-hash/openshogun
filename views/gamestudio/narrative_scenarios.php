<?php
/**
 * Game Studio - Narrative Designer 2.0 :
 * - Catalogue Multi-Vues (Cartes Interactives + Frise Chronologique des Steps + Kanban Pipeline)
 * - Studio Pleine Page Côte-à-Côte (Split-Screen Live Preview, Galerie PNJ Sengoku, Quick-Chips & Choix Narratifs)
 * - Matrice Opérationnelle des 10 Métiers 2.0 (Mode Compact / Détaillé / Kanban Tâches + Filtres Croisés + AJAX)
 * Fichier : views/gamestudio/narrative_scenarios.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/ScenarioEngine.php';
$scenarioEngine = new ScenarioEngine();

$rolesMap       = ScenarioEngine::ROLES;
$speakerPresets = ScenarioEngine::SPEAKER_PRESETS;
$triggerPresets = ScenarioEngine::TRIGGER_PRESETS;
$rewardPresets  = ScenarioEngine::REWARD_PRESETS;
$viewAction     = (string)($_GET['action'] ?? 'catalog');

$statusLabels = [
    'draft'         => ['label' => '📝 Brouillon',         'short' => 'Brouillon',   'badge' => 'bg-purple-lt text-purple', 'dot' => 'purple'],
    'in_review'     => ['label' => '🔍 En revue narrative','short' => 'En revue',    'badge' => 'bg-warning text-dark',     'dot' => 'warning'],
    'in_production' => ['label' => '⚙️ En production',     'short' => 'Production',  'badge' => 'bg-primary text-white',    'dot' => 'primary'],
    'ready'         => ['label' => '✅ Prêt / Validé',     'short' => 'Prêt',        'badge' => 'bg-teal text-white',       'dot' => 'teal'],
    'published'     => ['label' => '🚀 En ligne (Live)',   'short' => 'Live',        'badge' => 'bg-success text-white',    'dot' => 'success'],
    'archived'      => ['label' => '📦 Archivé',           'short' => 'Archivé',     'badge' => 'bg-secondary text-white',  'dot' => 'secondary'],
];

// Helper PHP pour rendre une carte de tâche (Mode Compact + Tiroir Détaillé)
if (!function_exists('renderTaskCardHtml')) {
    function renderTaskCardHtml(array $t, array $rDef): void {
        $taskId       = htmlspecialchars((string)($t['task_id'] ?? rand(100, 9999)));
        $roleCode     = htmlspecialchars((string)($t['role_code'] ?? 'level_designer'));
        $roleName     = htmlspecialchars((string)($t['role_name'] ?? $rDef['name']));
        $title        = htmlspecialchars((string)($t['title'] ?? ''));
        $description  = htmlspecialchars((string)($t['description'] ?? ''));
        $assignee     = htmlspecialchars((string)($t['assignee'] ?? $rDef['name']));
        $complexity   = htmlspecialchars((string)($t['complexity'] ?? 'M'));
        $status       = htmlspecialchars((string)($t['status'] ?? 'todo'));
        $deliverables = htmlspecialchars((string)($t['deliverables'] ?? ''));

        $statusColor = match($status) {
            'done'        => 'border-success bg-success-lt',
            'in_progress' => 'border-primary bg-primary-lt',
            'blocked'     => 'border-danger bg-danger-lt',
            default       => 'border-secondary-subtle bg-light'
        };
        ?>
        <div class="card border mb-2 shadow-none task-card-item transition-all"
             id="taskCard_<?= $taskId ?>"
             data-task-id="<?= $taskId ?>"
             data-role="<?= $roleCode ?>"
             data-status="<?= $status ?>">

            <!-- Barre Compacte (1 ligne toujours visible) -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 <?= $statusColor ?> rounded-top task-compact-header"
                 id="taskHeader_<?= $taskId ?>">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 240px;">
                    <span class="badge bg-<?= $rDef['color'] ?> text-white fw-bold font-monospace" style="min-width: 38px;">
                        #<?= $taskId ?>
                    </span>
                    <input type="text"
                           class="form-control form-control-sm fw-bold text-dark border-0 bg-white shadow-xs task-title-input"
                           name="tasks[<?= $taskId ?>][title]"
                           value="<?= $title ?>"
                           required
                           placeholder="Titre opérationnel de la tâche..."
                           oninput="markFormDirty(); syncKanbanBoardTasks();">
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Artisan / Assigné -->
                    <div class="input-group input-group-sm" style="width: 175px;" title="Artisan / Développeur assigné">
                        <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-user-ninja"></i></span>
                        <input type="text"
                               class="form-control form-control-sm bg-white task-assignee-input"
                               name="tasks[<?= $taskId ?>][assignee]"
                               value="<?= $assignee ?>"
                               oninput="markFormDirty()">
                    </div>

                    <!-- Complexité -->
                    <select class="form-select form-select-sm font-monospace fw-bold bg-white"
                            name="tasks[<?= $taskId ?>][complexity]"
                            style="width: 72px;"
                            title="Complexité estimée"
                            onchange="markFormDirty()">
                        <option value="S" <?= ($complexity === 'S') ? 'selected' : '' ?>>S</option>
                        <option value="M" <?= ($complexity === 'M') ? 'selected' : '' ?>>M</option>
                        <option value="L" <?= ($complexity === 'L') ? 'selected' : '' ?>>L</option>
                        <option value="XL" <?= ($complexity === 'XL') ? 'selected' : '' ?>>XL</option>
                    </select>

                    <!-- Sélecteur de Statut -->
                    <select class="form-select form-select-sm fw-bold task-status-select bg-white"
                            name="tasks[<?= $taskId ?>][status]"
                            style="width: 138px;"
                            onchange="onTaskStatusSelectChange('<?= $taskId ?>', '<?= $roleCode ?>', this.value)">
                        <option value="todo" <?= ($status === 'todo') ? 'selected' : '' ?>>⏳ À faire</option>
                        <option value="in_progress" <?= ($status === 'in_progress') ? 'selected' : '' ?>>🔧 En cours</option>
                        <option value="done" <?= ($status === 'done') ? 'selected' : '' ?>>✅ Terminé</option>
                        <option value="blocked" <?= ($status === 'blocked') ? 'selected' : '' ?>>⛔ Bloqué</option>
                    </select>

                    <!-- Bouton Tiroir Spécifications & Livrables -->
                    <button type="button"
                            class="btn btn-white btn-sm p-1 px-2 border task-drawer-btn"
                            title="Afficher / Masquer le cahier des charges & livrables"
                            onclick="toggleTaskDrawer('<?= $taskId ?>')">
                        <i class="fa-solid fa-sliders text-secondary"></i>
                    </button>

                    <!-- Supprimer -->
                    <button type="button"
                            class="btn btn-outline-danger btn-sm p-1 px-2"
                            title="Supprimer cette tâche"
                            onclick="removeTaskCard('<?= $taskId ?>', '<?= $roleCode ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <input type="hidden" name="tasks[<?= $taskId ?>][task_id]" value="<?= $taskId ?>">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_code]" value="<?= $roleCode ?>">
            <input type="hidden" name="tasks[<?= $taskId ?>][role_name]" value="<?= $roleName ?>">

            <!-- Tiroir Repliable (Cahier des charges & Livrables) -->
            <div class="p-3 bg-white border-top task-drawer-body" id="taskDrawer_<?= $taskId ?>" style="display: none;">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-secondary m-0">
                            <i class="fa-solid fa-clipboard-list me-1"></i>Cahier des Charges &amp; Spécifications Métier :
                        </label>
                        <textarea class="form-control form-control-sm mt-1"
                                  name="tasks[<?= $taskId ?>][description]"
                                  rows="2"
                                  placeholder="Détaillez le travail attendu pour ce métier..."
                                  oninput="markFormDirty()"><?= $description ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary m-0">
                            <i class="fa-solid fa-box-archive me-1"></i>Livrables / Fichiers Attendus :
                        </label>
                        <input type="text"
                               class="form-control form-control-sm font-monospace mt-1"
                               name="tasks[<?= $taskId ?>][deliverables]"
                               value="<?= $deliverables ?>"
                               placeholder="Ex: core/ScenarioEngine.php, public/assets/..."
                               oninput="markFormDirty()">
                        <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                            Domaine : <strong><?= htmlspecialchars($rDef['domain'] ?? 'Production') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// MODE 1 : STUDIO PLEINE PAGE CÔTE-À-CÔTE (action = 'edit' ou 'create')
// ═══════════════════════════════════════════════════════════════════════════
if ($viewAction === 'edit' || $viewAction === 'create'):
    $isCreate = ($viewAction === 'create');
    $scenarioId = (int)($_GET['id'] ?? 0);
    $activeScenario = null;

    $allScenariosList = $scenarioEngine->getScenarios();

    if (!$isCreate && $scenarioId > 0) {
        $activeScenario = $scenarioEngine->getScenarioById($scenarioId);
    }

    // Valeurs par défaut si création ou non trouvé
    if (!$activeScenario) {
        $isCreate = true;
        $stepCount = count(array_filter($allScenariosList, fn($s) => $s['type'] === 'step'));
        $presetType = in_array($_GET['preset_type'] ?? '', ['step', 'special_event'], true) ? $_GET['preset_type'] : 'step';
        $presetStep = isset($_GET['step_number']) ? max(1, (int)$_GET['step_number']) : ($stepCount + 1);

        $activeScenario = [
            'id'                => 0,
            'slug'              => '',
            'type'              => $presetType,
            'step_number'       => ($presetType === 'step') ? $presetStep : null,
            'title'             => '',
            'subtitle'          => '',
            'era'               => '1560 - Sengoku Jidai',
            'synopsis'          => '',
            'speaker_name'      => 'Maître de la Sagesse (Chie no Sensei)',
            'speaker_role'      => 'Vénérable Mentor & Gardien des Traditions',
            'speaker_avatar'    => '🏯',
            'dialogue'          => '',
            'trigger_condition' => '',
            'rewards_summary'   => '',
            'choices'           => [
                ['label' => '🌾 Soutenir les paysans du domaine', 'outcome' => '+500 Riz, +10 Morale', 'style' => 'emerald'],
                ['label' => '⚔️ Mobiliser la garde des samouraïs', 'outcome' => '+150 Kobans, +15 Prestige', 'style' => 'warning']
            ],
            'status'            => 'draft',
            'tasks'             => $scenarioEngine->generateDefaultTasks($presetType, 'Nouveau Scénario', $presetStep)
        ];
    }

    // Calcul Précédent / Suivant pour navigation rapide sans retour au catalogue
    $prevScenario = null;
    $nextScenario = null;
    if (!$isCreate && !empty($allScenariosList)) {
        foreach ($allScenariosList as $idx => $scItem) {
            if ((int)$scItem['id'] === (int)$activeScenario['id']) {
                if ($idx > 0) $prevScenario = $allScenariosList[$idx - 1];
                if ($idx < count($allScenariosList) - 1) $nextScenario = $allScenariosList[$idx + 1];
                break;
            }
        }
    }

    $activeTasks   = $activeScenario['tasks'] ?? [];
    $activeChoices = $activeScenario['choices'] ?? [
        ['label' => '⚔️ Décréter la Marche du Fief', 'outcome' => $activeScenario['rewards_summary'] ?? '', 'style' => 'warning']
    ];
    $metrics       = $scenarioEngine->calculateTaskMetrics($activeTasks);
    $groupedTasks  = $scenarioEngine->getTasksGroupedByRole($activeTasks);
    $currentAvatar = $activeScenario['speaker_avatar'] ?? '🏯';
?>

<style>
.studio-pipeline-stepper {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    padding: 4px 6px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.studio-pipeline-step {
    border: none;
    background: transparent;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.studio-pipeline-step:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.studio-pipeline-step.active {
    background: #6b21a8;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(107, 33, 168, 0.25);
}
.npc-preset-chip {
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid #e2e8f0;
    background: #ffffff;
}
.npc-preset-chip:hover, .npc-preset-chip.active {
    border-color: #9333ea;
    background: #faf5ff;
    transform: translateY(-1px);
}
.quick-insert-chip {
    cursor: pointer;
    font-size: 0.72rem;
    transition: all 0.12s ease;
}
.quick-insert-chip:hover {
    background-color: #6b21a8 !important;
    color: #fff !important;
    border-color: #6b21a8 !important;
}
.sengoku-choice-btn-warning {
    background: linear-gradient(135deg, #d97706, #b45309);
    color: #fff;
    border: 1px solid #fbbf24;
}
.sengoku-choice-btn-emerald {
    background: linear-gradient(135deg, #059669, #047857);
    color: #fff;
    border: 1px solid #34d399;
}
.sengoku-choice-btn-crimson {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    border: 1px solid #f87171;
}
.dirty-indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    background: #10b981;
}
.dirty-indicator-dot.unsaved {
    background: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
}
</style>

<div class="gamestudio-narrative-editor-fullpage">

    <!-- En-tête Sticky Pleine Page avec Stepper Pipeline & Sélecteur Rapide -->
    <div class="card shadow-sm border-0 mb-3 sticky-top bg-white" style="top: 58px; z-index: 1020;">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Gauche : Navigation Catalogue + Sélecteur Rapide Inter-Scénarios -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Catalogue</span>
                    </a>

                    <?php if (!$isCreate && !empty($allScenariosList)): ?>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Navigation inter-scénarios">
                            <?php if ($prevScenario): ?>
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $prevScenario['id'] ?>"
                                   class="btn btn-outline-secondary"
                                   title="Précédent : <?= htmlspecialchars($prevScenario['title']) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary" disabled><i class="fa-solid fa-chevron-left"></i></button>
                            <?php endif; ?>

                            <select class="form-select form-select-sm fw-bold border-secondary-subtle"
                                    style="max-width: 240px;"
                                    onchange="if(this.value) window.location.href='?page=gamestudio&tab=narrative_scenarios&action=edit&id='+this.value">
                                <?php foreach ($allScenariosList as $scNav): ?>
                                    <option value="<?= $scNav['id'] ?>" <?= ((int)$scNav['id'] === (int)$activeScenario['id']) ? 'selected' : '' ?>>
                                        <?= $scNav['type'] === 'step' ? "Step #{$scNav['step_number']} — " : "🏮 Event — " ?>
                                        <?= htmlspecialchars($scNav['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($nextScenario): ?>
                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $nextScenario['id'] ?>"
                                   class="btn btn-outline-secondary"
                                   title="Suivant : <?= htmlspecialchars($nextScenario['title']) ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary" disabled><i class="fa-solid fa-chevron-right"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex align-items-center gap-2 ms-1">
                        <span class="dirty-indicator-dot" id="dirtyStateDot" title="État de sauvegarde"></span>
                        <span class="small fw-bold text-muted" id="dirtyStateLabel">Synchronisé</span>
                    </div>
                </div>

                <!-- Centre : Workflow Pipeline Stepper Cliquable -->
                <div class="studio-pipeline-stepper">
                    <?php
                    $pipelineSteps = [
                        'draft'         => '📝 Brouillon',
                        'in_review'     => '🔍 En revue',
                        'in_production' => '⚙️ Production',
                        'ready'         => '✅ Prêt',
                        'published'     => '🚀 Live'
                    ];
                    foreach ($pipelineSteps as $stKey => $stText):
                        $isActiveSt = ($activeScenario['status'] === $stKey);
                    ?>
                        <button type="button"
                                class="studio-pipeline-step <?= $isActiveSt ? 'active' : '' ?>"
                                data-status-step="<?= $stKey ?>"
                                onclick="setScenarioPipelineStatus('<?= $stKey ?>')">
                            <?= $stText ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Droite : Actions Rapides & Sauvegarde AJAX Ctrl+S -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-purple btn-sm d-inline-flex align-items-center gap-1" onclick="triggerSynopsisMdFileInput()" title="Importer un fichier .md dans le Synopsis & Contexte Historique">
                        <i class="fa-solid fa-file-import"></i>
                        <span>Importer .md</span>
                    </button>
                    <?php if (!$isCreate): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" onclick="exportScenarioMarkdown(<?= $activeScenario['id'] ?>)">
                            <i class="fa-solid fa-file-arrow-down text-teal"></i>
                            <span>GDD</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" onclick="duplicateCurrentScenario(<?= $activeScenario['id'] ?>)">
                            <i class="fa-solid fa-copy text-primary"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" onclick="deleteCurrentScenario(<?= $activeScenario['id'] ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    <?php endif; ?>

                    <button type="button"
                            id="btnAjaxSaveStudio"
                            class="btn btn-purple btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1 px-3"
                            onclick="saveScenarioAjax()">
                        <i class="fa-solid fa-floppy-disk" id="btnAjaxSaveIcon"></i>
                        <span id="btnAjaxSaveText"><?= $isCreate ? 'Créer le Scénario' : 'Enregistrer' ?></span>
                        <span class="badge bg-purple-dark text-white font-monospace ms-1" style="font-size:0.65rem;">Ctrl+S</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire Principal Pleine Page -->
    <form method="POST" action="?page=gamestudio&tab=narrative_scenarios" id="fullPageScenarioForm">
        <input type="hidden" name="action" value="<?= $isCreate ? 'create_scenario' : 'update_scenario' ?>">
        <input type="hidden" name="scenario_id" id="fullScenId" value="<?= $activeScenario['id'] ?>">
        <input type="hidden" name="redirect_to" id="formRedirectTo" value="edit">

        <!-- Barre d'Onglets du Studio : 1. Conception Narrative + Live Simulator (Split-Screen) | 2. Matrice des 10 Métiers 2.0 -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white p-0 border-0">
                <ul class="nav nav-tabs card-header-tabs bg-dark border-0 px-3 pt-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold d-flex align-items-center gap-2 py-2 px-3"
                                data-bs-toggle="tab" data-bs-target="#studioTabStoryLive" type="button" role="tab">
                            <i class="fa-solid fa-wand-magic-sparkles text-purple"></i>
                            <span>1. Studio Narratif &amp; Simulateur In-Game Live (Split-Screen)</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold d-flex align-items-center gap-2 py-2 px-3"
                                data-bs-toggle="tab" data-bs-target="#studioTabTasksMatrix" type="button" role="tab">
                            <i class="fa-solid fa-users-gear text-teal"></i>
                            <span>2. Cockpit &amp; Matrice des 10 Métiers 2.0</span>
                            <span class="badge bg-teal text-white ms-1" id="tasksGlobalBadge">
                                <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $metrics['percent'] ?>%)
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content">

                    <!-- ========================================================================= -->
                    <!-- ONGLET 1 : CONCEPTION NARRATIVE + SIMULATEUR IN-GAME CÔTE-À-CÔTE          -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade show active" id="studioTabStoryLive" role="tabpanel">
                        <div class="row g-4 align-items-start">

                            <!-- COLONNE GAUCHE (7 col) : ÉDITEUR NARRATIF & ASSISTANTS MÉTIER -->
                            <div class="col-xl-7 col-lg-7">

                                <!-- Bloc A : Identité & Classification -->
                                <div class="card border shadow-none mb-3">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title m-0 fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                                            <i class="fa-solid fa-compass-drafting text-purple"></i>
                                            <span>Identité &amp; Classification du Scénario</span>
                                        </h4>
                                        <span class="badge bg-purple-lt text-purple font-monospace">
                                            <?= $isCreate ? 'Nouveau' : 'ID #' . $activeScenario['id'] ?>
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label fw-bold text-dark small mb-1">Type de Scénario</label>
                                                <select class="form-select form-select-sm fw-bold" name="type" id="fullScenType" onchange="toggleStepNumberFieldFullPage(); renderInGamePreviewLive(); markFormDirty();">
                                                    <option value="step" <?= ($activeScenario['type'] === 'step') ? 'selected' : '' ?>>⛩️ Étape de Jeu (Step Campagne)</option>
                                                    <option value="special_event" <?= ($activeScenario['type'] === 'special_event') ? 'selected' : '' ?>>🏮 Événement Spécial (Live Ops / Saison)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3" id="stepNumberGroupFullPage" style="<?= ($activeScenario['type'] === 'special_event') ? 'display:none;' : '' ?>">
                                                <label class="form-label fw-bold text-dark small mb-1">N° du Step</label>
                                                <input type="number" class="form-control form-control-sm fw-bold" name="step_number" id="fullScenStepNumber" min="1" max="99" value="<?= htmlspecialchars((string)($activeScenario['step_number'] ?? 1)) ?>" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-dark small mb-1">Statut Pipeline</label>
                                                <select class="form-select form-select-sm fw-bold" name="status" id="fullScenStatus" onchange="syncStepperFromSelect(this.value); markFormDirty();">
                                                    <?php foreach ($statusLabels as $stKey => $stCfg): ?>
                                                        <option value="<?= $stKey ?>" <?= ($activeScenario['status'] === $stKey) ? 'selected' : '' ?>><?= $stCfg['label'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-8">
                                                <label class="form-label fw-bold text-dark small mb-1">Titre Majeur du Scénario <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fw-bold" name="title" id="fullScenTitle" required value="<?= htmlspecialchars($activeScenario['title']) ?>" placeholder="Ex: L'Éveil du Fief & l'Appel du Clan" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-dark small mb-1">Période / Ère Historique</label>
                                                <input type="text" class="form-control" name="era" id="fullScenEra" value="<?= htmlspecialchars($activeScenario['era']) ?>" placeholder="Ex: 1560 - Aube du Sengoku" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>

                                            <div class="col-md-8">
                                                <label class="form-label fw-bold text-dark small mb-1">Sous-titre / Accroche Dramatique</label>
                                                <input type="text" class="form-control form-control-sm" name="subtitle" id="fullScenSubtitle" value="<?= htmlspecialchars($activeScenario['subtitle']) ?>" placeholder="Ex: Chapitre 1 : Fondation de la seigneurie" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-dark small mb-1">Identifiant Slug</label>
                                                <input type="text" class="form-control form-control-sm font-monospace" name="slug" id="fullScenSlug" value="<?= htmlspecialchars($activeScenario['slug']) ?>" placeholder="auto-genere-si-vide" oninput="markFormDirty();">
                                            </div>

                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                                                    <label class="form-label fw-bold text-dark small m-0 d-flex align-items-center gap-1">
                                                        <i class="fa-brands fa-markdown text-purple"></i>
                                                        <span>Synopsis &amp; Contexte Historique (GDD / Markdown `.md`)</span>
                                                    </label>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <input type="file" id="synopsisMdFileInput" accept=".md,.markdown,.txt" style="display:none;" onchange="onSynopsisMdFileSelected(this)">
                                                        <select id="selMdImportMode" class="form-select form-select-sm py-0 ps-2 pe-4" style="width:auto; height:26px; font-size:0.73rem;" title="Mode d'importation du fichier .md">
                                                            <option value="replace">Remplacer le synopsis</option>
                                                            <option value="append">Ajouter à la suite</option>
                                                        </select>
                                                        <label class="form-check form-check-inline m-0 small d-flex align-items-center gap-1" style="font-size:0.73rem; cursor:pointer;" title="Extraire automatiquement le Titre, Sous-titre, Période, Personnage, Dialogue, Triggers et Récompenses détectés dans le fichier .md">
                                                            <input class="form-check-input m-0" type="checkbox" id="chkMdSmartExtract" checked>
                                                            <span class="text-secondary fw-medium">Auto-remplir les champs détectés</span>
                                                        </label>
                                                        <button type="button" class="btn btn-purple btn-sm py-0 px-2 fw-bold d-inline-flex align-items-center gap-1" style="height:26px; font-size:0.74rem;" onclick="triggerSynopsisMdFileInput()">
                                                            <i class="fa-solid fa-file-import"></i>
                                                            <span>Importer un fichier .md</span>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 d-inline-flex align-items-center gap-1" style="height:26px; font-size:0.74rem;" onclick="toggleSynopsisMarkdownPreview()">
                                                            <i class="fa-solid fa-eye"></i>
                                                            <span>Aperçu .md</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div id="synopsisDropZoneWrapper"
                                                     class="position-relative"
                                                     ondragover="onSynopsisMdDragOver(event)"
                                                     ondragleave="onSynopsisMdDragLeave(event)"
                                                     ondrop="onSynopsisMdDrop(event)">
                                                    <textarea class="form-control form-control-sm font-monospace"
                                                              name="synopsis"
                                                              id="fullScenSynopsis"
                                                              rows="6"
                                                              placeholder="Décrivez la trame narrative, le contexte historique féodal et les enjeux... ou glissez-déposez directement un fichier .md ici !"
                                                              oninput="renderInGamePreviewLive(); updateSynopsisMarkdownPreview(); markFormDirty();"><?= htmlspecialchars($activeScenario['synopsis']) ?></textarea>
                                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                                        <span class="small text-muted" style="font-size:0.7rem;">
                                                            <i class="fa-solid fa-cloud-arrow-up text-purple me-1"></i>Astuce : vous pouvez glisser-déposer un fichier <code>.md</code> directement dans ce cadre.
                                                        </span>
                                                        <span class="badge bg-purple-lt text-purple font-monospace" id="mdImportedBadge" style="display:none; font-size:0.68rem;"></span>
                                                    </div>
                                                </div>
                                                <!-- Panneau d'aperçu Markdown formaté (repliable) -->
                                                <div id="synopsisMarkdownPreviewBox" class="mt-2 p-3 rounded border bg-light-subtle" style="display:none; max-height: 360px; overflow-y: auto; font-size: 0.85rem;">
                                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                                        <span class="fw-bold text-purple small"><i class="fa-brands fa-markdown me-1"></i>Aperçu du Synopsis &amp; Contexte Historique (.md)</span>
                                                        <button type="button" class="btn-close" style="font-size:0.65rem;" onclick="toggleSynopsisMarkdownPreview()"></button>
                                                    </div>
                                                    <div id="synopsisMarkdownPreviewContent" class="text-dark"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloc B : Galerie de Personnages Sengoku & Discours In-Game -->
                                <div class="card border shadow-none mb-3">
                                    <div class="card-header bg-purple-lt py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <h4 class="card-title m-0 fw-bold text-purple d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                                            <i class="fa-solid fa-masks-theater"></i>
                                            <span>Galerie de Personnages Sengoku &amp; Discours In-Game</span>
                                        </h4>
                                        <span class="small text-purple fw-medium">Cliquez sur un conseiller pour pré-remplir</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <!-- Galerie Rapide 1-Clic des Figures Sengoku -->
                                        <div class="row g-2 mb-3">
                                            <?php foreach ($speakerPresets as $spKey => $spDef): ?>
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="p-2 rounded npc-preset-chip d-flex align-items-center gap-2"
                                                         onclick="applySpeakerPreset(<?= htmlspecialchars(json_encode($spDef, JSON_UNESCAPED_UNICODE)) ?>)">
                                                        <span class="fs-3"><?= $spDef['avatar'] ?></span>
                                                        <div class="text-truncate">
                                                            <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($spDef['name']) ?></div>
                                                            <div class="text-muted text-truncate" style="font-size: 0.68rem;"><?= htmlspecialchars($spDef['tone']) ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="row g-2 mb-2">
                                            <div class="col-md-2">
                                                <label class="form-label fw-bold text-dark small mb-1">Icône / Avatar</label>
                                                <input type="text" class="form-control form-control-sm text-center fs-4" name="speaker_avatar" id="fullScenSpeakerAvatar" value="<?= htmlspecialchars($currentAvatar) ?>" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label fw-bold text-dark small mb-1">Personnage / Narrateur</label>
                                                <input type="text" class="form-control form-control-sm fw-bold" name="speaker_name" id="fullScenSpeakerName" value="<?= htmlspecialchars($activeScenario['speaker_name']) ?>" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label fw-bold text-dark small mb-1">Titre / Fonction Féodale</label>
                                                <input type="text" class="form-control form-control-sm" name="speaker_role" id="fullScenSpeakerRole" value="<?= htmlspecialchars($activeScenario['speaker_role']) ?>" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            </div>
                                        </div>

                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold text-dark small m-0">Réplique / Harangue au Joueur (Modale In-Game)</label>
                                                <span class="badge bg-light text-secondary border font-monospace" id="dialogueCharCounter">0 car.</span>
                                            </div>
                                            <textarea class="form-control border-purple" name="dialogue" id="fullScenDialogue" rows="3" placeholder="« Salutations, noble Daimyō... »" oninput="renderInGamePreviewLive(); markFormDirty();"><?= htmlspecialchars($activeScenario['dialogue']) ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloc C : Triggers, Récompenses & Choix Narratifs du Daimyō (Dilemmes A/B) -->
                                <div class="card border shadow-none">
                                    <div class="card-header bg-amber-lt py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title m-0 fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                                            <i class="fa-solid fa-code-branch text-warning"></i>
                                            <span>Déclencheurs, Récompenses &amp; Choix du Daimyō (Dilemmes In-Game)</span>
                                        </h4>
                                    </div>
                                    <div class="card-body p-3">
                                        <!-- Conditions de déclenchement + Quick Chips -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark small mb-1">
                                                <i class="fa-solid fa-bolt text-warning me-1"></i>Conditions d'Activation (Triggers)
                                            </label>
                                            <input type="text" class="form-control form-control-sm mb-1" name="trigger_condition" id="fullScenTrigger" value="<?= htmlspecialchars($activeScenario['trigger_condition']) ?>" placeholder="Ex: Tenshu niveau 1 construit + 4 parcelles rurales" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="small text-muted me-1" style="font-size:0.7rem;">Insérer :</span>
                                                <?php foreach ($triggerPresets as $trPreset): ?>
                                                    <span class="badge bg-light text-dark border quick-insert-chip"
                                                          onclick="appendPresetToField('fullScenTrigger', <?= htmlspecialchars(json_encode($trPreset['value'], JSON_UNESCAPED_UNICODE)) ?>)">
                                                        <?= htmlspecialchars($trPreset['label']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Récompenses + Quick Chips -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark small mb-1">
                                                <i class="fa-solid fa-coins text-success me-1"></i>Récompenses &amp; Effets Globaux
                                            </label>
                                            <input type="text" class="form-control form-control-sm mb-1" name="rewards_summary" id="fullScenRewards" value="<?= htmlspecialchars($activeScenario['rewards_summary']) ?>" placeholder="Ex: 250 Kobans, +15% rendement rizicole, Titre honorifique" oninput="renderInGamePreviewLive(); markFormDirty();">
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="small text-muted me-1" style="font-size:0.7rem;">Insérer :</span>
                                                <?php foreach ($rewardPresets as $rwPreset): ?>
                                                    <span class="badge bg-light text-dark border quick-insert-chip"
                                                          onclick="appendPresetToField('fullScenRewards', <?= htmlspecialchars(json_encode($rwPreset['value'], JSON_UNESCAPED_UNICODE)) ?>)">
                                                        <?= htmlspecialchars($rwPreset['label']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Éditeur des Choix Narratifs du Joueur (1 à 3 boutons dans le parchemin) -->
                                        <div class="border-top pt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark small m-0">
                                                    <i class="fa-solid fa-scale-balanced text-purple me-1"></i>Choix Narratifs proposés au Joueur (1 à 3 Décisions)
                                                </label>
                                                <button type="button" class="btn btn-outline-purple btn-sm py-0 px-2" onclick="addNarrativeChoiceRow()">
                                                    <i class="fa-solid fa-plus me-1"></i>Ajouter un Choix
                                                </button>
                                            </div>
                                            <div id="narrativeChoicesContainer">
                                                <?php foreach ($activeChoices as $cIdx => $chItem): ?>
                                                    <div class="row g-2 align-items-center mb-2 narrative-choice-row" data-choice-index="<?= $cIdx ?>">
                                                        <div class="col-md-5">
                                                            <input type="text"
                                                                   class="form-control form-control-sm fw-bold choice-label-input"
                                                                   name="choices[<?= $cIdx ?>][label]"
                                                                   value="<?= htmlspecialchars($chItem['label'] ?? '') ?>"
                                                                   placeholder="Texte du bouton joueur..."
                                                                   oninput="renderInGamePreviewLive(); markFormDirty();">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="text"
                                                                   class="form-control form-control-sm choice-outcome-input"
                                                                   name="choices[<?= $cIdx ?>][outcome]"
                                                                   value="<?= htmlspecialchars($chItem['outcome'] ?? '') ?>"
                                                                   placeholder="Conséquence / Bonus..."
                                                                   oninput="renderInGamePreviewLive(); markFormDirty();">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <select class="form-select form-select-sm choice-style-input"
                                                                    name="choices[<?= $cIdx ?>][style]"
                                                                    onchange="renderInGamePreviewLive(); markFormDirty();">
                                                                <option value="warning" <?= (($chItem['style'] ?? '') === 'warning') ? 'selected' : '' ?>>🟡 Or Impérial</option>
                                                                <option value="emerald" <?= (($chItem['style'] ?? '') === 'emerald') ? 'selected' : '' ?>>🟢 Jade Prospérité</option>
                                                                <option value="crimson" <?= (($chItem['style'] ?? '') === 'crimson') ? 'selected' : '' ?>>🔴 Vermillon Guerre</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-1 text-end">
                                                            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeNarrativeChoiceRow(this)" title="Retirer ce choix">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- COLONNE DROITE STICKY (5 col) : SIMULATEUR VISUEL IN-GAME EN DIRECT -->
                            <div class="col-xl-5 col-lg-5">
                                <div class="sticky-top" style="top: 120px; z-index: 1010;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-dark text-warning px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Aperçu Parchemin In-Game Temps Réel</span>
                                        </span>
                                        <button type="button" class="btn btn-outline-dark btn-sm py-0 px-2" onclick="playTypewriterSimulation()">
                                            <i class="fa-solid fa-play me-1 text-purple"></i>Tester l'Animation Texte
                                        </button>
                                    </div>

                                    <!-- Cadre Parchemin Féodal Sengoku -->
                                    <div class="p-3 rounded-3 shadow-lg border border-warning"
                                         style="background: radial-gradient(circle at center, #2e1d0f 0%, #140d07 100%); color: #f5ebd6;">
                                        <!-- En-tête du Parchemin -->
                                        <div class="d-flex justify-content-between align-items-center border-bottom border-warning-subtle pb-2 mb-3">
                                            <span class="badge bg-warning text-dark fw-bold text-uppercase px-2 py-1" id="livePreviewBadge">
                                                <?= ($activeScenario['type'] === 'step') ? 'Step #' . ($activeScenario['step_number'] ?? 1) . ' • Mode Campagne' : '🏮 Événement Spécial' ?>
                                            </span>
                                            <span class="font-monospace small text-warning" id="livePreviewEra">
                                                <?= htmlspecialchars($activeScenario['era'] ?: '1560 - Sengoku Jidai') ?>
                                            </span>
                                        </div>

                                        <!-- Titre & Sous-titre -->
                                        <h3 class="fw-bold text-warning mb-1" id="livePreviewTitle" style="font-family: Georgia, serif; letter-spacing: 0.4px; font-size: 1.25rem;">
                                            <?= htmlspecialchars($activeScenario['title'] ?: 'Titre du Scénario') ?>
                                        </h3>
                                        <div class="small text-light opacity-75 mb-3 fst-italic" id="livePreviewSubtitle">
                                            <?= htmlspecialchars($activeScenario['subtitle'] ?: '') ?>
                                        </div>

                                        <!-- Boîte de Dialogue PNJ -->
                                        <div class="d-flex align-items-start gap-3 p-3 rounded mb-3"
                                             style="background: rgba(255,255,255,0.06); border-left: 4px solid #f59f00;">
                                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow"
                                                 id="livePreviewAvatar"
                                                 style="width: 52px; height: 52px; font-size: 1.65rem;">
                                                <?= htmlspecialchars($currentAvatar) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-warning" id="livePreviewSpeaker" style="font-size: 0.95rem;">
                                                    <?= htmlspecialchars($activeScenario['speaker_name'] ?: 'Maître de la Sagesse') ?>
                                                </div>
                                                <div class="small text-light opacity-75 mb-2" id="livePreviewSpeakerRole" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars($activeScenario['speaker_role'] ?: 'Mentor Féodal') ?>
                                                </div>
                                                <p class="m-0 fst-italic" id="livePreviewDialogue" style="font-size: 0.92rem; line-height: 1.55; color: #fff8eb; min-height: 54px;">
                                                    « <?= htmlspecialchars($activeScenario['dialogue'] ?: 'Saisissez le discours du personnage pour prévisualiser la modale...') ?> »
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Triggers & Récompenses -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <div class="p-2 rounded h-100" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.12);">
                                                    <div class="text-uppercase small fw-bold text-warning mb-1" style="font-size: 0.65rem;">
                                                        <i class="fa-solid fa-bolt me-1"></i>Condition d'Activation
                                                    </div>
                                                    <div class="small" id="livePreviewTrigger" style="font-size: 0.78rem;">
                                                        <?= htmlspecialchars($activeScenario['trigger_condition'] ?: 'Objectifs requis') ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-2 rounded h-100" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.12);">
                                                    <div class="text-uppercase small fw-bold text-success mb-1" style="font-size: 0.65rem;">
                                                        <i class="fa-solid fa-gift me-1"></i>Dotation Impériale
                                                    </div>
                                                    <div class="small" id="livePreviewRewards" style="font-size: 0.78rem;">
                                                        <?= htmlspecialchars($activeScenario['rewards_summary'] ?: 'Récompenses du Shōgunat') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Boutons de Décision du Daimyō (Choix Narratifs Interactifs) -->
                                        <div class="border-top border-warning-subtle pt-2">
                                            <div class="small text-warning text-uppercase fw-bold mb-2 text-center" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                                                Décision du Daimyō (Cliquez pour tester l'issue)
                                            </div>
                                            <div class="d-grid gap-2" id="livePreviewChoicesList">
                                                <!-- Rendu dynamique par JS -->
                                            </div>
                                            <div id="livePreviewChoiceFeedback" class="mt-2 p-2 rounded text-center small fw-bold" style="display:none; background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #a7f3d0;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- ONGLET 2 : COCKPIT & MATRICE DES 10 MÉTIERS 2.0 (COMPACT / KANBAN)        -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade" id="studioTabTasksMatrix" role="tabpanel">
                        <!-- Barre d'Outils Supérieure de la Matrice 2.0 -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-3 border-bottom">
                            <div>
                                <h4 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-sitemap text-teal"></i>
                                    <span>Matrice Opérationnelle des 10 Métiers Clés</span>
                                </h4>
                                <p class="text-muted small m-0">
                                    Basculez entre la vue compacte par métier et le tableau Kanban des tâches, ou filtrez les blocages.
                                </p>
                            </div>

                            <!-- Sélecteur de Mode d'Affichage : Liste Compacte vs Kanban Tâches + Actions Tiroirs -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-teal fw-bold" id="btnMatrixModeList" onclick="switchMatrixDisplayMode('list')">
                                        <i class="fa-solid fa-list-check me-1"></i>Vue par Métier
                                    </button>
                                    <button type="button" class="btn btn-outline-teal fw-bold" id="btnMatrixModeKanban" onclick="switchMatrixDisplayMode('kanban')">
                                        <i class="fa-solid fa-table-columns me-1"></i>Kanban des Tâches
                                    </button>
                                </div>

                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllTaskDrawers(true)" title="Déplier tous les cahiers des charges">
                                    <i class="fa-solid fa-angles-down me-1"></i>Déplier détails
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllTaskDrawers(false)" title="Replier en mode compact 1-ligne">
                                    <i class="fa-solid fa-angles-up me-1"></i>Mode Compact
                                </button>
                            </div>
                        </div>

                        <!-- Double Barre de Filtres : Par Métier ET Par Statut de Tâche -->
                        <div class="card bg-light border-0 p-2 mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <!-- Filtre par Métier -->
                                <div class="d-flex flex-wrap gap-1 align-items-center" id="roleFilterPills">
                                    <button type="button" class="btn btn-sm btn-purple fw-bold" data-role-filter="all" onclick="filterTaskRoleSection('all', this)">
                                        Tous (<span id="totalTasksCountPill"><?= count($activeTasks) ?></span>)
                                    </button>
                                    <?php foreach ($rolesMap as $rCode => $rDef): ?>
                                        <?php $rCount = count($groupedTasks[$rCode]['tasks'] ?? []); ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-role-filter="<?= $rCode ?>" onclick="filterTaskRoleSection('<?= $rCode ?>', this)">
                                            <i class="fa-solid <?= $rDef['icon'] ?> me-1"></i><?= htmlspecialchars($rDef['name']) ?>
                                            <span class="badge bg-secondary-lt text-dark ms-1" id="badgeCount_<?= $rCode ?>"><?= $rCount ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Filtre par Statut de Tâche -->
                                <div class="btn-group btn-group-sm" id="statusFilterPills" role="group">
                                    <button type="button" class="btn btn-dark fw-bold" data-status-filter="all" onclick="filterTasksByStatus('all', this)">Tous statuts</button>
                                    <button type="button" class="btn btn-outline-secondary" data-status-filter="todo" onclick="filterTasksByStatus('todo', this)">⏳ À faire</button>
                                    <button type="button" class="btn btn-outline-primary" data-status-filter="in_progress" onclick="filterTasksByStatus('in_progress', this)">🔧 En cours</button>
                                    <button type="button" class="btn btn-outline-danger" data-status-filter="blocked" onclick="filterTasksByStatus('blocked', this)">⛔ Bloqué</button>
                                    <button type="button" class="btn btn-outline-success" data-status-filter="done" onclick="filterTasksByStatus('done', this)">✅ Terminé</button>
                                </div>
                            </div>
                        </div>

                        <!-- VUE A : SECTIONS PAR MÉTIER (AVEC LIGNES COMPACTES + TIROIRS) -->
                        <div id="rolesSectionsContainer">
                            <?php foreach ($rolesMap as $rCode => $rDef): ?>
                                <?php
                                $roleTasks = $groupedTasks[$rCode]['tasks'] ?? [];
                                $roleMetrics = $metrics['by_role'][$rCode] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
                                ?>
                                <div class="card border shadow-sm mb-3 role-task-section" id="roleSection_<?= $rCode ?>" data-role="<?= $rCode ?>">
                                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm rounded bg-<?= $rDef['color'] ?>-lt text-<?= $rDef['color'] ?>">
                                                <i class="fa-solid <?= $rDef['icon'] ?>"></i>
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                    <span><?= htmlspecialchars($rDef['name']) ?></span>
                                                    <span class="badge bg-<?= $rDef['color'] ?>-lt text-<?= $rDef['color'] ?> small">
                                                        <?= htmlspecialchars($rDef['domain']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-muted small" id="roleStatusText_<?= $rCode ?>">
                                                    <?= $roleMetrics['total'] ?> tâche(s) &bull; <?= $roleMetrics['done'] ?> terminée(s) (<?= $roleMetrics['percent'] ?>%)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress d-none d-sm-flex" style="width: 90px; height: 6px;">
                                                <div class="progress-bar bg-<?= $rDef['color'] ?>" id="roleProgressBar_<?= $rCode ?>" style="width: <?= $roleMetrics['percent'] ?>%;"></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-<?= $rDef['color'] ?> btn-sm fw-bold d-inline-flex align-items-center gap-1" onclick="addNewTaskForRole('<?= $rCode ?>')">
                                                <i class="fa-solid fa-plus"></i>
                                                <span>Ajouter une tâche</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body p-2" id="roleTasksList_<?= $rCode ?>">
                                        <?php if (empty($roleTasks)): ?>
                                            <div class="text-center text-muted py-2 small role-empty-placeholder" id="placeholder_<?= $rCode ?>">
                                                Aucune tâche assignée à ce métier pour ce scénario.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($roleTasks as $t): ?>
                                                <?php renderTaskCardHtml($t, $rDef); ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- VUE B : KANBAN OPÉRATIONNEL DES TÂCHES DU SCÉNARIO (4 COLONNES DRAG & DROP) -->
                        <div id="tasksKanbanBoardContainer" style="display: none;">
                            <div class="alert alert-teal bg-teal-lt border-0 py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
                                <i class="fa-solid fa-hand-pointer"></i>
                                <span><strong>Glisser-Déposer (Drag &amp; Drop) actif :</strong> faites glisser une carte de tâche d'une colonne à l'autre pour modifier instantanément son statut sans recharger la page.</span>
                            </div>
                            <div class="row g-3">
                                <?php
                                $taskKanbanCols = [
                                    'todo'        => ['title' => '⏳ À faire',   'headerBg' => 'bg-secondary-lt text-dark', 'border' => 'border-secondary-subtle'],
                                    'in_progress' => ['title' => '🔧 En cours',  'headerBg' => 'bg-primary-lt text-primary','border' => 'border-primary'],
                                    'blocked'     => ['title' => '⛔ Bloqué',    'headerBg' => 'bg-danger-lt text-danger',  'border' => 'border-danger'],
                                    'done'        => ['title' => '✅ Terminé',   'headerBg' => 'bg-success-lt text-success','border' => 'border-success']
                                ];
                                foreach ($taskKanbanCols as $kColKey => $kColCfg):
                                ?>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card h-100 border <?= $kColCfg['border'] ?> bg-light-subtle">
                                            <div class="card-header py-2 px-3 <?= $kColCfg['headerBg'] ?> d-flex justify-content-between align-items-center">
                                                <span class="fw-bold small"><?= $kColCfg['title'] ?></span>
                                                <span class="badge bg-white text-dark fw-bold" id="kanbanTaskColCount_<?= $kColKey ?>">0</span>
                                            </div>
                                            <div class="card-body p-2 d-flex flex-column gap-2 kanban-dropzone"
                                                 id="kanbanTaskColBody_<?= $kColKey ?>"
                                                 data-task-status="<?= $kColKey ?>"
                                                 ondragover="onTaskKanbanDragOver(event)"
                                                 ondragleave="onTaskKanbanDragLeave(event)"
                                                 ondrop="onTaskKanbanDrop(event, '<?= $kColKey ?>')"
                                                 style="min-height: 320px;">
                                                <!-- Peuplé dynamiquement depuis les cartes de tâches du DOM -->
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Pied de Formulaire Pleine Page -->
            <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3 px-4">
                <a href="?page=gamestudio&tab=narrative_scenarios" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour au Catalogue
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-purple fw-bold" onclick="submitFullPageForm('catalog')">
                        <i class="fa-solid fa-check me-1"></i>Enregistrer &amp; Quitter
                    </button>
                    <button type="button" class="btn btn-purple fw-bold px-4 shadow-sm" onclick="saveScenarioAjax()">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer en direct (Ctrl+S)
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
<?php
// ═══════════════════════════════════════════════════════════════════════════
// MODE 2 : CATALOGUE MULTI-VUES (CARTES + FRISE DES STEPS + KANBAN PIPELINE)
// ═══════════════════════════════════════════════════════════════════════════
else:
    $filterType   = isset($_GET['scenario_type']) ? trim((string)$_GET['scenario_type']) : '';
    $filterStatus = isset($_GET['scenario_status']) ? trim((string)$_GET['scenario_status']) : '';
    $filterSearch = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $catalogView  = isset($_GET['view_mode']) && in_array($_GET['view_mode'], ['cards', 'timeline', 'kanban'], true)
        ? (string)$_GET['view_mode']
        : 'cards';

    $scenarios = $scenarioEngine->getScenarios([
        'type'   => $filterType,
        'status' => $filterStatus,
        'search' => $filterSearch
    ]);

    $allScenariosUnfiltered = $scenarioEngine->getScenarios();
    $totalScenarios = count($allScenariosUnfiltered);
    $totalSteps  = 0;
    $totalEvents = 0;
    $totalTasksGlobal = 0;
    $doneTasksGlobal  = 0;
    $blockedTasksGlobal = 0;

    foreach ($allScenariosUnfiltered as $sc) {
        if ($sc['type'] === 'step') $totalSteps++;
        else $totalEvents++;

        $totalTasksGlobal   += $sc['metrics']['total'] ?? 0;
        $doneTasksGlobal    += $sc['metrics']['done'] ?? 0;
        $blockedTasksGlobal += $sc['metrics']['blocked'] ?? 0;
    }

    $globalCompletionPct = ($totalTasksGlobal > 0) ? (int)round(($doneTasksGlobal / $totalTasksGlobal) * 100) : 0;

    // Séparer les steps pour la vue Frise Chronologique
    $stepScenarios = array_values(array_filter($scenarios, fn($s) => ($s['type'] ?? '') === 'step'));
    usort($stepScenarios, fn($a, $b) => ((int)($a['step_number'] ?? 0)) <=> ((int)($b['step_number'] ?? 0)));
    $eventScenarios = array_values(array_filter($scenarios, fn($s) => ($s['type'] ?? '') !== 'step'));
?>

<style>
.task-pill-interactive {
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.task-pill-interactive:hover {
    transform: translateY(-2px) scale(1.08);
    box-shadow: 0 3px 6px rgba(0,0,0,0.18);
}
.roadmap-step-node {
    position: relative;
    border-left: 3px solid #9333ea;
    padding-left: 1.5rem;
    margin-left: 1rem;
}
.roadmap-step-badge {
    position: absolute;
    left: -18px;
    top: 12px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #6b21a8;
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 4px #f3e8ff;
}
.kanban-draggable-card {
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    user-select: none;
}
.kanban-draggable-card:hover {
    border-color: #9333ea !important;
    box-shadow: 0 4px 12px rgba(107, 33, 168, 0.12) !important;
    transform: translateY(-1px);
}
.kanban-draggable-card:active {
    cursor: grabbing;
}
.kanban-draggable-card.kanban-dragging {
    opacity: 0.45;
    transform: scale(0.97);
    border: 2px dashed #9333ea !important;
}
.kanban-dropzone {
    transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    border-radius: 0 0 8px 8px;
}
.kanban-dropzone.kanban-dropzone-active {
    background-color: rgba(147, 51, 234, 0.08) !important;
    outline: 2px dashed #9333ea;
    outline-offset: -4px;
}
</style>

<div class="gamestudio-narrative-scenarios">

    <!-- En-tête : Titre & Boutons de Création Rapide + Sélecteur de Vue -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-md rounded bg-purple-lt text-purple">
                    <i class="fa-solid fa-feather-pointed fs-3"></i>
                </span>
                <div>
                    <h2 class="h2 m-0 text-dark fw-bold">
                        Studio Narrative Designer &amp; Scénarios de Jeu
                    </h2>
                    <div class="text-secondary small">
                        Concevez la campagne <strong>Steps</strong> et les <strong>Événements Spéciaux</strong> avec prévisualisation In-Game et pilotage des <strong>10 Métiers</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Sélecteur de Vue (Cartes / Frise Chronologique / Kanban Pipeline) -->
            <div class="btn-group shadow-xs" role="group" aria-label="Sélecteur de vue catalogue">
                <button type="button"
                        class="btn btn-sm fw-bold <?= $catalogView === 'cards' ? 'btn-purple' : 'btn-outline-secondary bg-white' ?>"
                        id="btnCatView_cards"
                        onclick="switchCatalogViewMode('cards')">
                    <i class="fa-solid fa-grip me-1"></i>Cartes
                </button>
                <button type="button"
                        class="btn btn-sm fw-bold <?= $catalogView === 'timeline' ? 'btn-purple' : 'btn-outline-secondary bg-white' ?>"
                        id="btnCatView_timeline"
                        onclick="switchCatalogViewMode('timeline')">
                    <i class="fa-solid fa-timeline me-1"></i>Frise des Steps
                </button>
                <button type="button"
                        class="btn btn-sm fw-bold <?= $catalogView === 'kanban' ? 'btn-purple' : 'btn-outline-secondary bg-white' ?>"
                        id="btnCatView_kanban"
                        onclick="switchCatalogViewMode('kanban')">
                    <i class="fa-solid fa-table-columns me-1"></i>Kanban Pipeline
                </button>
            </div>

            <a href="?page=gamestudio&tab=narrative_scenarios&action=create&preset_type=step&step_number=<?= $totalSteps + 1 ?>"
               class="btn btn-purple fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span>Nouveau Step (#<?= $totalSteps + 1 ?>)</span>
            </a>
            <a href="?page=gamestudio&tab=narrative_scenarios&action=create&preset_type=special_event"
               class="btn btn-outline-danger bg-white fw-bold d-inline-flex align-items-center gap-1 shadow-xs">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Nouvel Événement</span>
            </a>
            <button type="button"
                    class="btn btn-outline-purple bg-white fw-bold d-inline-flex align-items-center gap-1 shadow-xs"
                    data-bs-toggle="modal"
                    data-bs-target="#modalImportMarkdownScenario">
                <i class="fa-solid fa-file-import"></i>
                <span>Importer un .md</span>
            </button>
        </div>
    </div>

    <!-- Message Flash -->
    <?php if (!empty($_SESSION['scenario_flash'])): ?>
        <?php $fl = $_SESSION['scenario_flash']; unset($_SESSION['scenario_flash']); ?>
        <div class="alert alert-<?= htmlspecialchars($fl['type'] ?? 'info') ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <div><?= htmlspecialchars($fl['msg'] ?? '') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Bandeau KPI : 4 Cartes Synthétiques -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple text-white avatar"><i class="fa-solid fa-scroll"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary small text-uppercase fw-bold">Catalogue Scénarios</div>
                            <div class="text-dark fw-bold fs-2"><?= $totalScenarios ?> scénarios</div>
                            <div class="text-muted small">Actifs dans le moteur narratif</div>
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
                            <span class="bg-amber text-white avatar"><i class="fa-solid fa-stairs"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary small text-uppercase fw-bold">Étapes du Mode de Jeu</div>
                            <div class="text-dark fw-bold fs-2"><?= $totalSteps ?> Steps</div>
                            <div class="text-muted small">Progression séquentielle du fief</div>
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
                            <span class="bg-red text-white avatar"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary small text-uppercase fw-bold">Événements Spéciaux</div>
                            <div class="text-dark fw-bold fs-2"><?= $totalEvents ?> Événements</div>
                            <div class="text-muted small">Saisons, tournois, crises &amp; guerres</div>
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
                            <span class="bg-teal text-white avatar"><i class="fa-solid fa-users-gear"></i></span>
                        </div>
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="font-weight-medium text-secondary small text-uppercase fw-bold">Pipeline 10 Métiers</div>
                                <span class="badge bg-teal-lt text-teal fw-bold" id="globalCatalogPctBadge"><?= $globalCompletionPct ?>%</span>
                            </div>
                            <div class="text-dark fw-bold fs-3" id="globalCatalogDoneCounter"><?= $doneTasksGlobal ?> / <?= $totalTasksGlobal ?> tâches</div>
                            <div class="progress progress-sm mt-1">
                                <div class="progress-bar bg-teal" id="globalCatalogProgressBar" style="width: <?= $globalCompletionPct ?>%" role="progressbar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de Filtres et Recherche -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="narrative_scenarios">
                <input type="hidden" name="view_mode" id="filterFormViewMode" value="<?= htmlspecialchars($catalogView) ?>">

                <div class="col-md-3">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>" class="form-control form-control-sm" placeholder="Rechercher un scénario, dialogue, PNJ...">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="scenario_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les types (Steps &amp; Événements)</option>
                        <option value="step" <?= ($filterType === 'step') ? 'selected' : '' ?>>⛩️ Étapes du Mode de Jeu (Steps)</option>
                        <option value="special_event" <?= ($filterType === 'special_event') ? 'selected' : '' ?>>🏮 Événements Spéciaux</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="scenario_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les statuts de production</option>
                        <option value="draft" <?= ($filterStatus === 'draft') ? 'selected' : '' ?>>📝 Brouillon (Draft)</option>
                        <option value="in_review" <?= ($filterStatus === 'in_review') ? 'selected' : '' ?>>🔍 En revue narrative</option>
                        <option value="in_production" <?= ($filterStatus === 'in_production') ? 'selected' : '' ?>>⚙️ En production (10 Métiers)</option>
                        <option value="ready" <?= ($filterStatus === 'ready') ? 'selected' : '' ?>>✅ Prêt / Validé QA</option>
                        <option value="published" <?= ($filterStatus === 'published') ? 'selected' : '' ?>>🚀 Publié In-Game</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fa-solid fa-filter me-1"></i>Filtrer
                    </button>
                    <?php if ($filterType !== '' || $filterStatus !== '' || $filterSearch !== ''): ?>
                        <a href="?page=gamestudio&tab=narrative_scenarios&view_mode=<?= urlencode($catalogView) ?>" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($scenarios)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fa-solid fa-scroll fa-3x text-muted mb-3"></i>
                <h3 class="text-dark">Aucun scénario ne correspond à vos critères</h3>
                <p class="text-secondary">Ajustez vos filtres ou créez un nouveau scénario dans l'éditeur pleine page.</p>
                <a href="?page=gamestudio&tab=narrative_scenarios&action=create" class="btn btn-purple mt-2">
                    <i class="fa-solid fa-plus me-1"></i>Créer en Pleine Page
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- ========================================================================= -->
        <!-- VUE CATALOGUE 1 : CARTES ENRICHIES AVEC MICRO-INTERACTIONS AJAX           -->
        <!-- ========================================================================= -->
        <div id="catalogView_cards" style="<?= $catalogView === 'cards' ? '' : 'display:none;' ?>">
            <div class="row row-cards">
                <?php foreach ($scenarios as $sc): ?>
                    <?php
                    $isStep = ($sc['type'] === 'step');
                    $statusCfg = $statusLabels[$sc['status']] ?? $statusLabels['draft'];
                    $metrics = $sc['metrics'] ?? ['percent' => 0, 'done' => 0, 'total' => 0, 'blocked' => 0];
                    $pct = $metrics['percent'];
                    $barCol = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-teal' : (($pct > 0) ? 'bg-primary' : 'bg-secondary'));
                    $scChoices = $sc['choices'] ?? [];
                    ?>
                    <div class="col-md-12 col-xl-6 mb-3" id="catalogCardCol_<?= $sc['id'] ?>">
                        <div class="card shadow-sm border h-100 d-flex flex-column transition-hover">
                            <!-- En-tête avec sélecteur rapide de statut AJAX -->
                            <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-bottom flex-wrap gap-2">
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

                                    <!-- Changement rapide du statut du scénario en 1 clic AJAX -->
                                    <select class="form-select form-select-sm fw-bold py-0 ps-2 pe-4 border-secondary-subtle"
                                            id="cardStatusSelect_<?= $sc['id'] ?>"
                                            style="font-size: 0.74rem; width: auto; height: 26px;"
                                            onchange="movePipelineKanbanCard(<?= $sc['id'] ?>, this.value)">
                                        <?php foreach ($statusLabels as $stK => $stInfo): ?>
                                            <option value="<?= $stK ?>" <?= ($sc['status'] === $stK) ? 'selected' : '' ?>>
                                                <?= $stInfo['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                    <div class="text-secondary small fw-medium mb-2 fst-italic">
                                        <?= htmlspecialchars($sc['subtitle']) ?>
                                    </div>
                                <?php endif; ?>

                                <p class="text-secondary small mb-3" style="line-height:1.5;">
                                    <?= htmlspecialchars($sc['synopsis']) ?>
                                </p>

                                <!-- Dialogue condensé avec Avatar -->
                                <div class="p-2 bg-light rounded border mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="avatar avatar-xs rounded bg-warning-lt text-dark fw-bold">
                                            <?= htmlspecialchars($sc['speaker_avatar'] ?? '🏯') ?>
                                        </span>
                                        <strong class="small text-dark"><?= htmlspecialchars($sc['speaker_name'] ?? 'Narrateur') ?></strong>
                                        <span class="small text-muted">(<?= htmlspecialchars($sc['speaker_role'] ?? 'Voix') ?>)</span>
                                    </div>
                                    <div class="small text-muted fst-italic ps-4 border-start border-2 border-purple ms-2">
                                        « <?= htmlspecialchars(mb_substr($sc['dialogue'] ?? '', 0, 140)) ?><?= mb_strlen($sc['dialogue'] ?? '') > 140 ? '...' : '' ?> »
                                    </div>
                                    <?php if (!empty($scChoices)): ?>
                                        <div class="d-flex flex-wrap gap-1 mt-2 ps-2">
                                            <?php foreach ($scChoices as $ch): ?>
                                                <span class="badge bg-amber-lt text-dark border" style="font-size: 0.68rem;">
                                                    <?= htmlspecialchars($ch['label'] ?? '') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Matrice des tâches interactive (Clic sur pastille = bascule statut AJAX) -->
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-users-gear text-teal"></i>
                                            <span>Matrice 10 Métiers <span class="text-muted fw-normal">(cliquez sur une icône pour changer son statut)</span></span>
                                        </span>
                                        <span class="small font-monospace fw-bold text-secondary" id="cardMetricsLabel_<?= $sc['id'] ?>">
                                            <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $pct ?>%)
                                        </span>
                                    </div>

                                    <div class="progress mb-2" style="height: 6px;">
                                        <div class="progress-bar <?= $barCol ?>" id="cardMetricsBar_<?= $sc['id'] ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                    </div>

                                    <!-- Pastilles des Tâches Cliquables -->
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($sc['tasks'] as $task): ?>
                                            <?php
                                            $tId  = (string)($task['task_id'] ?? '');
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
                                                'done'        => '✅ Terminé',
                                                'in_progress' => '🔧 En cours',
                                                'blocked'     => '⛔ Bloqué',
                                                default       => '⏳ À faire'
                                            };
                                            ?>
                                            <button type="button"
                                                    class="badge <?= $tBadgeClass ?> d-inline-flex align-items-center justify-content-center p-1 rounded task-pill-interactive"
                                                    style="width: 28px; height: 28px; font-size: 0.78rem;"
                                                    data-scenario-id="<?= $sc['id'] ?>"
                                                    data-task-id="<?= htmlspecialchars($tId) ?>"
                                                    data-status="<?= htmlspecialchars($tStatus) ?>"
                                                    data-role-name="<?= htmlspecialchars($rDef['name']) ?>"
                                                    data-task-title="<?= htmlspecialchars($task['title'] ?? '') ?>"
                                                    onclick="cycleTaskStatusAjax(this)"
                                                    title="<?= htmlspecialchars("{$rDef['name']} : {$task['title']} [{$tStatusLabel}] — Cliquez pour avancer le statut") ?>">
                                                <i class="fa-solid <?= $rDef['icon'] ?>"></i>
                                            </button>
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
                                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" title="Supprimer ce scénario" onclick="deleteScenarioFromCatalogAjax(<?= $sc['id'] ?>)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $sc['id'] ?>" class="btn btn-purple btn-sm fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Studio Split-Screen</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- VUE CATALOGUE 2 : FRISE CHRONOLOGIQUE DES STEPS (ROADMAP CAMPAGNE)        -->
        <!-- ========================================================================= -->
        <div id="catalogView_timeline" style="<?= $catalogView === 'timeline' ? '' : 'display:none;' ?>">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-purple-lt py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title m-0 fw-bold text-purple d-flex align-items-center gap-2">
                            <i class="fa-solid fa-timeline"></i>
                            <span>Frise Chronologique de la Campagne (Séquence des Steps)</span>
                        </h3>
                        <p class="text-secondary small m-0">
                            Visualisez l'enchaînement narratif du joueur (Conditions d'entrée ➔ Harangue PNJ ➔ Choix du Daimyō ➔ Récompenses)
                        </p>
                    </div>
                    <a href="?page=gamestudio&tab=narrative_scenarios&action=create&preset_type=step&step_number=<?= count($stepScenarios) + 1 ?>"
                       class="btn btn-purple btn-sm fw-bold">
                        <i class="fa-solid fa-plus me-1"></i>Insérer le Step #<?= count($stepScenarios) + 1 ?>
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($stepScenarios)): ?>
                        <div class="text-center text-muted py-4">Aucun Step de campagne trouvé avec les filtres actuels.</div>
                    <?php else: ?>
                        <?php foreach ($stepScenarios as $sIdx => $stepSc): ?>
                            <?php
                            $stInfo = $statusLabels[$stepSc['status']] ?? $statusLabels['draft'];
                            $pct = $stepSc['metrics']['percent'] ?? 0;
                            ?>
                            <div class="roadmap-step-node pb-4">
                                <div class="roadmap-step-badge"><?= (int)($stepSc['step_number'] ?? ($sIdx + 1)) ?></div>
                                <div class="card border shadow-xs">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-purple text-white fw-bold">Step #<?= (int)$stepSc['step_number'] ?></span>
                                                    <span class="badge <?= $stInfo['badge'] ?>"><?= $stInfo['label'] ?></span>
                                                    <span class="font-monospace small text-muted"><?= htmlspecialchars($stepSc['era'] ?? '') ?></span>
                                                </div>
                                                <h4 class="fw-bold text-dark m-0 mt-1">
                                                    <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $stepSc['id'] ?>" class="text-dark text-decoration-none">
                                                        <?= htmlspecialchars($stepSc['title']) ?>
                                                    </a>
                                                </h4>
                                                <div class="small text-secondary fst-italic"><?= htmlspecialchars($stepSc['subtitle'] ?? '') ?></div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-teal-lt text-teal font-monospace">
                                                    Métiers : <?= $stepSc['metrics']['done'] ?>/<?= $stepSc['metrics']['total'] ?> (<?= $pct ?>%)
                                                </span>
                                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $stepSc['id'] ?>" class="btn btn-outline-purple btn-sm fw-bold">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Éditer
                                                </a>
                                            </div>
                                        </div>

                                        <div class="row g-2 mt-1">
                                            <div class="col-md-4">
                                                <div class="p-2 rounded bg-warning-lt border border-warning-subtle h-100">
                                                    <div class="small fw-bold text-dark"><i class="fa-solid fa-bolt text-warning me-1"></i>Trigger d'Entrée</div>
                                                    <div class="small text-secondary"><?= htmlspecialchars($stepSc['trigger_condition'] ?: 'Aucun prérequis') ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2 rounded bg-light border h-100">
                                                    <div class="small fw-bold text-dark">
                                                        <?= htmlspecialchars($stepSc['speaker_avatar'] ?? '🏯') ?>
                                                        <?= htmlspecialchars($stepSc['speaker_name'] ?? 'Narrateur') ?>
                                                    </div>
                                                    <div class="small text-muted fst-italic">
                                                        « <?= htmlspecialchars(mb_substr($stepSc['dialogue'] ?? '', 0, 95)) ?>... »
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-2 rounded bg-success-lt border border-success-subtle h-100">
                                                    <div class="small fw-bold text-dark"><i class="fa-solid fa-gift text-success me-1"></i>Récompenses &amp; Issue</div>
                                                    <div class="small text-secondary"><?= htmlspecialchars($stepSc['rewards_summary'] ?: 'Dotation du fief') ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($eventScenarios)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger-lt py-2 px-4">
                        <h4 class="card-title m-0 fw-bold text-danger d-flex align-items-center gap-2">
                            <i class="fa-solid fa-calendar-star"></i>
                            <span>Événements Spéciaux &amp; Saisons Live Ops (Hors-Séquence)</span>
                        </h4>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <?php foreach ($eventScenarios as $evSc): ?>
                                <div class="col-md-4">
                                    <div class="card border h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-danger text-white">🏮 Événement</span>
                                                <span class="small font-monospace text-muted"><?= htmlspecialchars($evSc['era'] ?? '') ?></span>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($evSc['title']) ?></h5>
                                            <p class="small text-secondary mb-2"><?= htmlspecialchars(mb_substr($evSc['synopsis'] ?? '', 0, 100)) ?>...</p>
                                            <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $evSc['id'] ?>" class="btn btn-sm btn-outline-danger w-100 fw-bold">
                                                Ouvrir dans le Studio
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========================================================================= -->
        <!-- VUE CATALOGUE 3 : KANBAN PIPELINE DE PRODUCTION (5 COLONNES DRAG & DROP)  -->
        <!-- ========================================================================= -->
        <div id="catalogView_kanban" style="<?= $catalogView === 'kanban' ? '' : 'display:none;' ?>">
            <div class="alert alert-purple bg-purple-lt border-0 py-2 px-3 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-hand-pointer"></i>
                    <span><strong>Glisser-Déposer (Drag &amp; Drop) actif :</strong> saisissez une carte de scénario et déposez-la dans une autre colonne pour faire évoluer son statut en direct sans quitter le Kanban.</span>
                </div>
                <span class="badge bg-purple text-white">Sauvegarde AJAX instantanée</span>
            </div>
            <div class="row g-3">
                <?php
                $kanbanColumns = [
                    'draft'         => ['title' => '📝 Brouillon',      'bg' => 'bg-purple-lt text-purple'],
                    'in_review'     => ['title' => '🔍 En revue',       'bg' => 'bg-warning-lt text-dark'],
                    'in_production' => ['title' => '⚙️ En production',  'bg' => 'bg-primary-lt text-primary'],
                    'ready'         => ['title' => '✅ Prêt QA',        'bg' => 'bg-teal-lt text-teal'],
                    'published'     => ['title' => '🚀 En ligne (Live)','bg' => 'bg-success-lt text-success']
                ];
                foreach ($kanbanColumns as $colStatus => $colDef):
                    $colItems = array_filter($scenarios, fn($s) => ($s['status'] ?? 'draft') === $colStatus);
                ?>
                    <div class="col-xl col-md-6">
                        <div class="card shadow-sm border h-100 bg-light-subtle">
                            <div class="card-header py-2 px-3 <?= $colDef['bg'] ?> d-flex justify-content-between align-items-center">
                                <span class="fw-bold small"><?= $colDef['title'] ?></span>
                                <span class="badge bg-white text-dark fw-bold" id="kanbanPipelineColCount_<?= $colStatus ?>"><?= count($colItems) ?></span>
                            </div>
                            <div class="card-body p-2 d-flex flex-column gap-2 kanban-dropzone"
                                 id="kanbanPipelineColBody_<?= $colStatus ?>"
                                 data-pipeline-status="<?= $colStatus ?>"
                                 ondragover="onPipelineKanbanDragOver(event)"
                                 ondragleave="onPipelineKanbanDragLeave(event)"
                                 ondrop="onPipelineKanbanDrop(event, '<?= $colStatus ?>')"
                                 style="min-height: 440px;">
                                <?php foreach ($colItems as $kSc): ?>
                                    <?php
                                    $pct = $kSc['metrics']['percent'] ?? 0;
                                    ?>
                                    <div class="card border shadow-xs bg-white p-2 kanban-draggable-card"
                                         id="kanbanPipelineCard_<?= $kSc['id'] ?>"
                                         draggable="true"
                                         data-scenario-id="<?= $kSc['id'] ?>"
                                         data-status="<?= $colStatus ?>"
                                         ondragstart="onPipelineKanbanDragStart(event, <?= $kSc['id'] ?>)"
                                         ondragend="onPipelineKanbanDragEnd(event)">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-grip-vertical text-muted" style="font-size:0.75rem;" title="Glisser-déposer cette carte"></i>
                                                <?php if ($kSc['type'] === 'step'): ?>
                                                    <span class="badge bg-purple text-white" style="font-size:0.68rem;">Step #<?= (int)$kSc['step_number'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger text-white" style="font-size:0.68rem;">🏮 Event</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="small font-monospace fw-bold text-secondary" style="font-size:0.7rem;"><?= $pct ?>%</span>
                                        </div>
                                        <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $kSc['id'] ?>"
                                           draggable="false"
                                           class="fw-bold text-dark text-decoration-none small d-block mb-1">
                                            <?= htmlspecialchars($kSc['title']) ?>
                                        </a>
                                        <div class="small text-muted mb-2" style="font-size:0.72rem;">
                                            <?= htmlspecialchars($kSc['speaker_avatar'] ?? '🏯') ?> <?= htmlspecialchars($kSc['speaker_name'] ?? '') ?>
                                        </div>
                                        <div class="progress mb-2" style="height: 4px;">
                                            <div class="progress-bar bg-teal" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pt-1 border-top">
                                            <span class="small text-muted font-monospace" style="font-size:0.66rem;">
                                                <i class="fa-solid fa-up-down-left-right me-1"></i>Glisser
                                            </span>
                                            <div class="d-flex align-items-center gap-1">
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm py-0 px-1"
                                                        style="font-size:0.7rem;"
                                                        title="Supprimer ce scénario"
                                                        onclick="deleteScenarioFromCatalogAjax(<?= $kSc['id'] ?>)">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                                <a href="?page=gamestudio&tab=narrative_scenarios&action=edit&id=<?= $kSc['id'] ?>"
                                                   draggable="false"
                                                   class="btn btn-ghost-purple btn-sm py-0 px-2 fw-bold"
                                                   style="font-size:0.72rem;">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Studio
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- TOAST FLOTTANT DE NOTIFICATION AJAX                                         -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="narrativeStudioToast" class="toast align-items-center text-bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check text-teal" id="narrativeStudioToastIcon"></i>
                <span id="narrativeStudioToastMsg">Opération effectuée.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

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

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODALE D'IMPORT D'UN FICHIER MARKDOWN (.MD) POUR LE SYNOPSIS & HISTOIRE     -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalImportMarkdownScenario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="?page=gamestudio&tab=narrative_scenarios" enctype="multipart/form-data" id="formModalImportMarkdown">
                <input type="hidden" name="action" value="import_markdown_scenario">
                <input type="hidden" name="md_filename" id="modalMdFilename" value="">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h5 class="modal-title d-flex align-items-center gap-2 m-0 text-white">
                        <i class="fa-brands fa-markdown text-purple"></i>
                        <span>Importer un fichier Markdown (`.md`) — Synopsis &amp; Contexte Historique</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-purple bg-purple-lt border-0 py-2 px-3 small mb-3">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                        Sélectionnez ou glissez-déposez un fichier <code>.md</code> (GDD, trame narrative, contexte historique). Son contenu alimentera automatiquement le <strong>Synopsis &amp; Contexte Historique</strong> et pré-remplira le titre, l'époque et le dialogue détectés.
                    </div>

                    <!-- Zone de sélection / Drag & Drop du fichier .md -->
                    <div id="modalMdDropzone"
                         class="border border-2 border-dashed rounded-3 p-4 text-center mb-3 bg-light-subtle"
                         style="cursor: pointer; border-color: #7950f2 !important;"
                         onclick="document.getElementById('modalMdFileInput').click()"
                         ondragover="event.preventDefault(); this.classList.add('bg-purple-lt');"
                         ondragleave="this.classList.remove('bg-purple-lt');"
                         ondrop="onModalMdDrop(event)">
                        <input type="file" name="md_file" id="modalMdFileInput" accept=".md,.markdown,.txt" style="display:none;" onchange="onModalMdFileSelected(this)">
                        <div class="mb-1">
                            <i class="fa-solid fa-file-arrow-up fa-2x text-purple"></i>
                        </div>
                        <div class="fw-bold text-dark" id="modalMdDropTitle">Cliquez pour choisir un fichier <code>.md</code> ou glissez-le ici</div>
                        <div class="small text-muted" id="modalMdDropSub">Formats supportés : .md, .markdown, .txt</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small mb-1">Titre du Scénario (extrait du `.md`)</label>
                            <input type="text" name="title" id="modalMdExtractedTitle" class="form-control form-control-sm fw-bold" placeholder="Détecté automatiquement depuis # Titre...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small mb-1">Type de Scénario</label>
                            <select name="type" id="modalMdType" class="form-select form-select-sm fw-bold">
                                <option value="step">⛩️ Step de Campagne</option>
                                <option value="special_event">🏮 Événement Spécial</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small mb-1">N° Step</label>
                            <input type="number" name="step_number" value="<?= $totalSteps + 1 ?>" min="1" max="99" class="form-control form-control-sm fw-bold">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small mb-1">Ère</label>
                            <input type="text" name="era" id="modalMdExtractedEra" class="form-control form-control-sm" placeholder="1580 - Sengoku">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-bold small mb-1">
                            Contenu Markdown importé (Synopsis &amp; Contexte Historique) :
                        </label>
                        <textarea name="md_content" id="modalMdContentTextarea" class="form-control form-control-sm font-monospace" rows="9" placeholder="Le contenu de votre fichier .md s'affichera ici (vous pouvez aussi coller directement votre Markdown)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-purple btn-sm fw-bold px-3">
                        <i class="fa-solid fa-rocket me-1"></i>Créer le Scénario &amp; Ouvrir dans le Studio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulaire POST caché pour actions rapides (duplication, suppression directe) -->
<form id="catalogQuickActionForm" method="POST" action="?page=gamestudio&tab=narrative_scenarios" style="display:none;">
    <input type="hidden" name="action" id="catQuickActionName" value="">
    <input type="hidden" name="scenario_id" id="catQuickScenarioId" value="0">
    <input type="hidden" name="return_action" value="<?= htmlspecialchars($viewAction) ?>">
</form>

<script>
const ROLES_DEF_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;

// ---------------------------------------------------------------------------
// IMPORTATION DE FICHIER MARKDOWN (.MD) POUR LE SYNOPSIS & CONTEXTE HISTORIQUE
// ---------------------------------------------------------------------------
function triggerSynopsisMdFileInput() {
    const inp = document.getElementById('synopsisMdFileInput');
    if (inp) {
        inp.value = '';
        inp.click();
    }
}

function parseMarkdownClientSide(mdText, fileName = '') {
    const raw = (mdText || '').replace(/\r\n|\r/g, '\n').trim();
    const res = {
        title: '',
        subtitle: '',
        era: '',
        synopsis: raw,
        speaker_name: '',
        speaker_role: '',
        dialogue: '',
        trigger_condition: '',
        rewards_summary: ''
    };
    if (!raw) return res;

    // 1. Titre principal (# Titre)
    const mTitle = raw.match(/^#\s+(.+)$/m);
    if (mTitle) {
        res.title = mTitle[1].replace(/^[🏯⚔️📜🔥⛩️🏮🧨]+\s*/u, '').trim();
    } else if (fileName) {
        res.title = fileName.replace(/\.(md|markdown|txt)$/i, '').replace(/[_-]+/g, ' ');
    }

    // 2. Sous-titre (## Sous-titre)
    const mSub = raw.match(/^##\s+(.+)$/m);
    if (mSub) {
        res.subtitle = mSub[1].replace(/^[🏯⚔️📜🔥⛩️🏮🧨IVX\.\s]+/u, '').trim();
    }

    // 3. Ère / Période
    const mEra = raw.match(/\*\*Période\s*(?:\/\s*Ère)?\s*:\*\*\s*(.+)$/mi) || raw.match(/((?:15|16)\d{2}\s*[-–]\s*[^\n\*]+)/);
    if (mEra) {
        res.era = mEra[1].trim();
    }

    // 4. Personnage
    const mSp = raw.match(/\*\*(?:Personnage|Interlocuteur)\s*:\*\*\s*([^\n]+)/i);
    if (mSp) {
        const cleanSp = mSp[1].replace(/\*/g, '').trim();
        const parts = cleanSp.split(/[,—–-]/);
        res.speaker_name = (parts[0] || '').trim();
        if (parts[1]) {
            res.speaker_role = parts[1].replace(/Avatar\s*:.*/i, '').trim();
        }
    }

    // 5. Dialogue en citation (> « ... »)
    const mDia = raw.match(/^>\s*\*?[«"]?([^»"\n]{25,})[»"]?\*?/m);
    if (mDia) {
        res.dialogue = mDia[1].replace(/[\*«»"]/g, '').trim();
    }

    // 6. Trigger & Récompenses
    const mTr = raw.match(/\*\*(?:Condition(?:s)? d'activation|Trigger|Déclencheur|Objectif Stratégique(?: Majeur)?)\s*:\*\*\s*([^\n]+)/i);
    if (mTr) {
        res.trigger_condition = mTr[1].replace(/\*/g, '').trim();
    }
    const mRw = raw.match(/\*\*(?:Récompenses?(?:\s*&\s*Effets)?|Dotation)\s*:\*\*\s*([^\n]+)/i);
    if (mRw) {
        res.rewards_summary = mRw[1].replace(/\*/g, '').trim();
    }

    return res;
}

function applyMarkdownToStudioSynopsis(mdText, fileName = '') {
    const synEl = document.getElementById('fullScenSynopsis');
    if (!synEl) return;

    const mode = document.getElementById('selMdImportMode')?.value || 'replace';
    const smartExtract = document.getElementById('chkMdSmartExtract')?.checked ?? true;
    const parsed = parseMarkdownClientSide(mdText, fileName);

    if (mode === 'append' && synEl.value.trim() !== '') {
        synEl.value = synEl.value.trim() + "\n\n---\n\n" + parsed.synopsis;
    } else {
        synEl.value = parsed.synopsis;
    }

    // Si l'extraction intelligente est activée, pré-remplir les champs vides (ou mettre à jour)
    if (smartExtract) {
        const titleEl = document.getElementById('fullScenTitle');
        if (titleEl && parsed.title && (!titleEl.value.trim() || mode === 'replace')) {
            titleEl.value = parsed.title;
        }
        const subEl = document.getElementById('fullScenSubtitle');
        if (subEl && parsed.subtitle && (!subEl.value.trim() || mode === 'replace')) {
            subEl.value = parsed.subtitle;
        }
        const eraEl = document.getElementById('fullScenEra');
        if (eraEl && parsed.era && (!eraEl.value.trim() || mode === 'replace')) {
            eraEl.value = parsed.era;
        }
        const spNameEl = document.getElementById('fullScenSpeakerName');
        if (spNameEl && parsed.speaker_name) {
            spNameEl.value = parsed.speaker_name;
        }
        const spRoleEl = document.getElementById('fullScenSpeakerRole');
        if (spRoleEl && parsed.speaker_role) {
            spRoleEl.value = parsed.speaker_role;
        }
        const diaEl = document.getElementById('fullScenDialogue');
        if (diaEl && parsed.dialogue) {
            diaEl.value = parsed.dialogue;
        }
        const trEl = document.getElementById('fullScenTrigger');
        if (trEl && parsed.trigger_condition) {
            trEl.value = parsed.trigger_condition;
        }
        const rwEl = document.getElementById('fullScenRewards');
        if (rwEl && parsed.rewards_summary) {
            rwEl.value = parsed.rewards_summary;
        }
    }

    const badge = document.getElementById('mdImportedBadge');
    if (badge) {
        badge.style.display = 'inline-block';
        badge.textContent = `📄 Importé : ${fileName || 'Markdown'} (${parsed.synopsis.length} car.)`;
    }

    updateSynopsisMarkdownPreview();
    renderInGamePreviewLive();
    markFormDirty(true);
    showStudioToast(`Fichier Markdown « ${fileName || '.md'} » importé dans le Synopsis & Contexte Historique !`);
}

function onSynopsisMdFileSelected(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(evt) {
        applyMarkdownToStudioSynopsis(evt.target.result || '', file.name);
    };
    reader.readAsText(file, 'UTF-8');
}

function onSynopsisMdDragOver(e) {
    e.preventDefault();
    const ta = document.getElementById('fullScenSynopsis');
    if (ta) {
        ta.style.borderColor = '#7950f2';
        ta.style.backgroundColor = '#faf8ff';
    }
}

function onSynopsisMdDragLeave(e) {
    const ta = document.getElementById('fullScenSynopsis');
    if (ta) {
        ta.style.borderColor = '';
        ta.style.backgroundColor = '';
    }
}

function onSynopsisMdDrop(e) {
    e.preventDefault();
    onSynopsisMdDragLeave(e);
    const files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;
    const file = files[0];
    const reader = new FileReader();
    reader.onload = function(evt) {
        applyMarkdownToStudioSynopsis(evt.target.result || '', file.name);
    };
    reader.readAsText(file, 'UTF-8');
}

function renderSimpleMarkdown(md) {
    if (!md) return '<em class="text-muted">Aucun contenu Markdown à prévisualiser.</em>';
    let html = md
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    html = html
        .replace(/^###\s+(.+)$/gm, '<h6 class="fw-bold text-dark mt-2 mb-1">$1</h6>')
        .replace(/^##\s+(.+)$/gm, '<h5 class="fw-bold text-purple mt-3 mb-1 border-bottom pb-1">$1</h5>')
        .replace(/^#\s+(.+)$/gm, '<h4 class="fw-bold text-dark mt-2 mb-2">$1</h4>')
        .replace(/^\s*[-*]\s+(.+)$/gm, '<li class="ms-3">$1</li>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="text-purple">$1</code>')
        .replace(/\n\n/g, '<br><br>');
    return html;
}

function updateSynopsisMarkdownPreview() {
    const box = document.getElementById('synopsisMarkdownPreviewBox');
    const content = document.getElementById('synopsisMarkdownPreviewContent');
    const synEl = document.getElementById('fullScenSynopsis');
    if (!box || !content || !synEl) return;
    if (box.style.display !== 'none') {
        content.innerHTML = renderSimpleMarkdown(synEl.value);
    }
}

function toggleSynopsisMarkdownPreview() {
    const box = document.getElementById('synopsisMarkdownPreviewBox');
    if (!box) return;
    if (box.style.display === 'none') {
        box.style.display = 'block';
        updateSynopsisMarkdownPreview();
    } else {
        box.style.display = 'none';
    }
}

// Gestion de la modale d'import .md depuis le Catalogue
function onModalMdFileSelected(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    readModalMdFile(file);
}

function onModalMdDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('modalMdDropzone');
    if (dz) dz.classList.remove('bg-purple-lt');
    const files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;
    readModalMdFile(files[0]);
}

function readModalMdFile(file) {
    const reader = new FileReader();
    reader.onload = function(evt) {
        const content = evt.target.result || '';
        const parsed = parseMarkdownClientSide(content, file.name);
        const ta = document.getElementById('modalMdContentTextarea');
        if (ta) ta.value = content;
        const fn = document.getElementById('modalMdFilename');
        if (fn) fn.value = file.name;
        const t = document.getElementById('modalMdExtractedTitle');
        if (t && parsed.title) t.value = parsed.title;
        const era = document.getElementById('modalMdExtractedEra');
        if (era && parsed.era) era.value = parsed.era;
        const dt = document.getElementById('modalMdDropTitle');
        if (dt) dt.innerHTML = `<span class="text-purple"><i class="fa-solid fa-circle-check me-1"></i>Fichier prêt : ${file.name}</span>`;
        const ds = document.getElementById('modalMdDropSub');
        if (ds) ds.textContent = `${content.length} caractères analysés — prêt à créer le scénario`;
    };
    reader.readAsText(file, 'UTF-8');
}
let currentRoleFilter = 'all';
let currentStatusFilter = 'all';
let isStudioFormDirty = false;
let typewriterTimer = null;

function showStudioToast(msg, isError = false) {
    const toastEl = document.getElementById('narrativeStudioToast');
    const msgEl   = document.getElementById('narrativeStudioToastMsg');
    const iconEl  = document.getElementById('narrativeStudioToastIcon');
    if (!toastEl || !msgEl) return;
    msgEl.textContent = msg;
    if (iconEl) {
        iconEl.className = isError ? 'fa-solid fa-triangle-exclamation text-warning' : 'fa-solid fa-circle-check text-teal';
    }
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2600 }).show();
}

function markFormDirty(dirty = true) {
    isStudioFormDirty = dirty;
    const dot = document.getElementById('dirtyStateDot');
    const lbl = document.getElementById('dirtyStateLabel');
    if (!dot || !lbl) return;
    if (dirty) {
        dot.classList.add('unsaved');
        lbl.textContent = 'Modifications non enregistrées';
        lbl.className = 'small fw-bold text-warning';
    } else {
        dot.classList.remove('unsaved');
        lbl.textContent = 'Synchronisé';
        lbl.className = 'small fw-bold text-muted';
    }
}

// Bascule des 3 vues du Catalogue (Cartes / Frise / Kanban) avec persistance URL & localStorage
function switchCatalogViewMode(mode) {
    if (!['cards', 'timeline', 'kanban'].includes(mode)) mode = 'cards';
    ['cards', 'timeline', 'kanban'].forEach(m => {
        const panel = document.getElementById('catalogView_' + m);
        const btn   = document.getElementById('btnCatView_' + m);
        if (panel) panel.style.display = (m === mode) ? 'block' : 'none';
        if (btn) {
            btn.className = (m === mode)
                ? 'btn btn-sm fw-bold btn-purple'
                : 'btn btn-sm fw-bold btn-outline-secondary bg-white';
        }
    });
    const hiddenInput = document.getElementById('filterFormViewMode');
    if (hiddenInput) hiddenInput.value = mode;

    try {
        localStorage.setItem('openshogun_narrative_catalog_view', mode);
        const url = new URL(window.location.href);
        url.searchParams.set('view_mode', mode);
        window.history.replaceState({}, '', url.toString());
    } catch (e) {}
}

// ---------------------------------------------------------------------------
// DRAG & DROP — KANBAN PIPELINE DU CATALOGUE (SANS RECHARGEMENT DE PAGE)
// ---------------------------------------------------------------------------
let draggedPipelineScenarioId = null;

function onPipelineKanbanDragStart(e, scenarioId) {
    draggedPipelineScenarioId = scenarioId;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(scenarioId));
    }
    const card = document.getElementById('kanbanPipelineCard_' + scenarioId);
    if (card) {
        setTimeout(() => card.classList.add('kanban-dragging'), 0);
    }
}

function onPipelineKanbanDragEnd(e) {
    document.querySelectorAll('.kanban-draggable-card.kanban-dragging').forEach(el => {
        el.classList.remove('kanban-dragging');
    });
    document.querySelectorAll('.kanban-dropzone.kanban-dropzone-active').forEach(el => {
        el.classList.remove('kanban-dropzone-active');
    });
    draggedPipelineScenarioId = null;
}

function onPipelineKanbanDragOver(e) {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    const zone = e.currentTarget;
    if (zone && !zone.classList.contains('kanban-dropzone-active')) {
        zone.classList.add('kanban-dropzone-active');
    }
}

function onPipelineKanbanDragLeave(e) {
    const zone = e.currentTarget;
    if (zone && !zone.contains(e.relatedTarget)) {
        zone.classList.remove('kanban-dropzone-active');
    }
}

function onPipelineKanbanDrop(e, targetStatus) {
    e.preventDefault();
    const zone = e.currentTarget;
    if (zone) zone.classList.remove('kanban-dropzone-active');

    const scId = draggedPipelineScenarioId || parseInt(e.dataTransfer?.getData('text/plain') || '0', 10);
    if (!scId) return;
    movePipelineKanbanCard(scId, targetStatus);
}

// Déplace une carte de scénario dans le Kanban Pipeline en direct + synchronise en AJAX sans recharger
function movePipelineKanbanCard(scenarioId, newStatus) {
    const card = document.getElementById('kanbanPipelineCard_' + scenarioId);
    const targetCol = document.getElementById('kanbanPipelineColBody_' + newStatus);

    if (card && targetCol) {
        const oldStatus = card.getAttribute('data-status');
        if (oldStatus !== newStatus) {
            card.setAttribute('data-status', newStatus);
            targetCol.appendChild(card);
            recalculatePipelineKanbanCounts();
        }
    }

    // Synchroniser aussi le sélecteur de la vue Cartes s'il est présent
    const cardSelect = document.getElementById('cardStatusSelect_' + scenarioId);
    if (cardSelect && cardSelect.value !== newStatus) {
        cardSelect.value = newStatus;
    }

    quickUpdateScenarioStatusAjax(scenarioId, newStatus);
}

function recalculatePipelineKanbanCounts() {
    ['draft', 'in_review', 'in_production', 'ready', 'published'].forEach(st => {
        const col = document.getElementById('kanbanPipelineColBody_' + st);
        const badge = document.getElementById('kanbanPipelineColCount_' + st);
        if (col && badge) {
            badge.textContent = col.querySelectorAll('.kanban-draggable-card').length;
        }
    });
}

// Mise à jour rapide du statut d'un scénario en AJAX (zéro rechargement de page)
function quickUpdateScenarioStatusAjax(scenarioId, newStatus) {
    const fd = new FormData();
    fd.append('action', 'ajax_update_scenario_status');
    fd.append('scenario_id', scenarioId);
    fd.append('status', newStatus);

    fetch('?page=gamestudio&tab=narrative_scenarios', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                showStudioToast(res.msg || 'Statut mis à jour !');
            } else {
                showStudioToast(res.msg || 'Erreur de mise à jour', true);
            }
        })
        .catch(() => showStudioToast('Erreur réseau', true));
}

// Cycle rapide du statut d'une tâche en 1 clic sur sa pastille dans le catalogue
function cycleTaskStatusAjax(btnEl) {
    const scId      = btnEl.getAttribute('data-scenario-id');
    const taskId    = btnEl.getAttribute('data-task-id');
    const curStatus = btnEl.getAttribute('data-status') || 'todo';
    const roleName  = btnEl.getAttribute('data-role-name') || 'Métier';
    const taskTitle = btnEl.getAttribute('data-task-title') || '';

    const cycleOrder = ['todo', 'in_progress', 'done', 'blocked'];
    const nextIdx    = (cycleOrder.indexOf(curStatus) + 1) % cycleOrder.length;
    const nextStatus = cycleOrder[nextIdx];

    const fd = new FormData();
    fd.append('action', 'ajax_update_task_status');
    fd.append('scenario_id', scId);
    fd.append('task_id', taskId);
    fd.append('task_status', nextStatus);

    fetch('?page=gamestudio&tab=narrative_scenarios', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                showStudioToast(res.msg || 'Erreur', true);
                return;
            }
            btnEl.setAttribute('data-status', nextStatus);
            const badgeMap = {
                'todo':        'bg-light text-muted border',
                'in_progress': 'bg-primary text-white border-primary',
                'done':        'bg-success text-white border-success',
                'blocked':     'bg-danger text-white border-danger'
            };
            const labelMap = {
                'todo':        '⏳ À faire',
                'in_progress': '🔧 En cours',
                'done':        '✅ Terminé',
                'blocked':     '⛔ Bloqué'
            };
            btnEl.className = `badge ${badgeMap[nextStatus]} d-inline-flex align-items-center justify-content-center p-1 rounded task-pill-interactive`;
            btnEl.title = `${roleName} : ${taskTitle} [${labelMap[nextStatus]}] — Cliquez pour avancer le statut`;

            if (res.metrics) {
                const lbl = document.getElementById('cardMetricsLabel_' + scId);
                const bar = document.getElementById('cardMetricsBar_' + scId);
                if (lbl) lbl.textContent = `${res.metrics.done}/${res.metrics.total} (${res.metrics.percent}%)`;
                if (bar) bar.style.width = `${res.metrics.percent}%`;
            }
            showStudioToast(`${roleName} ➔ ${labelMap[nextStatus]}`);
        })
        .catch(() => showStudioToast('Erreur réseau lors de la mise à jour', true));
}

// Bascule champ step number
function toggleStepNumberFieldFullPage() {
    const typeSelect = document.getElementById('fullScenType');
    const group      = document.getElementById('stepNumberGroupFullPage');
    if (typeSelect && group) {
        group.style.display = (typeSelect.value === 'step') ? 'block' : 'none';
    }
}

// Synchronisation du Stepper Pipeline dans l'en-tête du Studio
function setScenarioPipelineStatus(statusKey) {
    const sel = document.getElementById('fullScenStatus');
    if (sel) sel.value = statusKey;
    syncStepperFromSelect(statusKey);
    markFormDirty(true);

    const scId = parseInt(document.getElementById('fullScenId')?.value || '0', 10);
    if (scId > 0) {
        quickUpdateScenarioStatusAjax(scId, statusKey, false);
    }
}

function syncStepperFromSelect(statusKey) {
    document.querySelectorAll('.studio-pipeline-step').forEach(btn => {
        if (btn.getAttribute('data-status-step') === statusKey) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// Galerie PNJ Sengoku : application en 1 clic
function applySpeakerPreset(preset) {
    if (!preset) return;
    const av = document.getElementById('fullScenSpeakerAvatar');
    const nm = document.getElementById('fullScenSpeakerName');
    const rl = document.getElementById('fullScenSpeakerRole');
    if (av) av.value = preset.avatar || '🏯';
    if (nm) nm.value = preset.name || '';
    if (rl) rl.value = preset.role || '';
    renderInGamePreviewLive();
    markFormDirty(true);
    showStudioToast(`Narrateur sélectionné : ${preset.name}`);
}

// Quick-Chips : ajout rapide dans Trigger ou Récompenses
function appendPresetToField(fieldId, textValue) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const cur = input.value.trim();
    input.value = cur ? (cur + ' + ' + textValue) : textValue;
    renderInGamePreviewLive();
    markFormDirty(true);
}

// Gestion des Choix Narratifs du Daimyō (1 à 3 choix)
function addNarrativeChoiceRow() {
    const container = document.getElementById('narrativeChoicesContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.narrative-choice-row');
    if (rows.length >= 3) {
        showStudioToast('Maximum 3 choix narratifs par modale In-Game.', true);
        return;
    }
    const idx = Date.now();
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 narrative-choice-row';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control form-control-sm fw-bold choice-label-input"
                   name="choices[${idx}][label]" value="⚔️ Nouvelle décision du Daimyō"
                   placeholder="Texte du bouton joueur..." oninput="renderInGamePreviewLive(); markFormDirty();">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm choice-outcome-input"
                   name="choices[${idx}][outcome]" value="+100 Kobans, +5 Morale"
                   placeholder="Conséquence / Bonus..." oninput="renderInGamePreviewLive(); markFormDirty();">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm choice-style-input"
                    name="choices[${idx}][style]" onchange="renderInGamePreviewLive(); markFormDirty();">
                <option value="warning" selected>🟡 Or Impérial</option>
                <option value="emerald">🟢 Jade Prospérité</option>
                <option value="crimson">🔴 Vermillon Guerre</option>
            </select>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeNarrativeChoiceRow(this)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    renderInGamePreviewLive();
    markFormDirty(true);
}

function removeNarrativeChoiceRow(btnEl) {
    const container = document.getElementById('narrativeChoicesContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.narrative-choice-row');
    if (rows.length <= 1) {
        showStudioToast('Un scénario doit comporter au moins 1 bouton d\'action.', true);
        return;
    }
    const row = btnEl.closest('.narrative-choice-row');
    if (row) row.remove();
    renderInGamePreviewLive();
    markFormDirty(true);
}

// Sauvegarde AJAX sans rechargement de page (Ctrl+S)
function saveScenarioAjax() {
    const form = document.getElementById('fullPageScenarioForm');
    if (!form) return;

    const btnIcon = document.getElementById('btnAjaxSaveIcon');
    const btnText = document.getElementById('btnAjaxSaveText');
    if (btnIcon) btnIcon.className = 'fa-solid fa-spinner fa-spin';
    if (btnText) btnText.textContent = 'Sauvegarde...';

    const fd = new FormData(form);
    fd.set('action', 'ajax_save_scenario');

    fetch('?page=gamestudio&tab=narrative_scenarios', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (btnIcon) btnIcon.className = 'fa-solid fa-floppy-disk';
            if (btnText) btnText.textContent = 'Enregistrer';

            if (res.ok) {
                markFormDirty(false);
                showStudioToast(res.msg || 'Scénario sauvegardé !');
                if (res.is_new && res.redirect) {
                    window.location.href = res.redirect;
                }
            } else {
                showStudioToast(res.msg || 'Erreur lors de la sauvegarde', true);
            }
        })
        .catch(() => {
            if (btnIcon) btnIcon.className = 'fa-solid fa-floppy-disk';
            if (btnText) btnText.textContent = 'Enregistrer';
            showStudioToast('Erreur réseau lors de la sauvegarde', true);
        });
}

// Soumission classique du formulaire pleine page
function submitFullPageForm(redirectTo) {
    const form = document.getElementById('fullPageScenarioForm');
    if (!form) return;
    document.getElementById('formRedirectTo').value = redirectTo || 'edit';
    form.submit();
}

// Raccourci Clavier Ctrl + S pour enregistrement AJAX direct
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        const form = document.getElementById('fullPageScenarioForm');
        if (form) {
            e.preventDefault();
            saveScenarioAjax();
        }
    }
});

// Tiroir repliable d'une tâche
function toggleTaskDrawer(taskId) {
    const drawer = document.getElementById('taskDrawer_' + taskId);
    if (!drawer) return;
    drawer.style.display = (drawer.style.display === 'none') ? 'block' : 'none';
}

function toggleAllTaskDrawers(expand) {
    document.querySelectorAll('.task-drawer-body').forEach(el => {
        el.style.display = expand ? 'block' : 'none';
    });
}

// Changement de statut d'une tâche dans la Matrice 2.0
function onTaskStatusSelectChange(taskId, roleCode, newStatus) {
    const card   = document.getElementById('taskCard_' + taskId);
    const header = document.getElementById('taskHeader_' + taskId);
    if (card) card.setAttribute('data-status', newStatus);
    if (header) {
        header.className = 'd-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 rounded-top task-compact-header ' + (
            newStatus === 'done' ? 'border-success bg-success-lt' :
            newStatus === 'in_progress' ? 'border-primary bg-primary-lt' :
            newStatus === 'blocked' ? 'border-danger bg-danger-lt' : 'border-secondary-subtle bg-light'
        );
    }
    markFormDirty(true);
    updateRoleTelemetry(roleCode);
    applyCombinedTaskFilters();
    syncKanbanBoardTasks();
}

// Bascule Vue Liste par Métier / Vue Kanban des Tâches dans l'onglet 2
function switchMatrixDisplayMode(mode) {
    const listContainer   = document.getElementById('rolesSectionsContainer');
    const kanbanContainer = document.getElementById('tasksKanbanBoardContainer');
    const btnList         = document.getElementById('btnMatrixModeList');
    const btnKanban       = document.getElementById('btnMatrixModeKanban');

    if (mode === 'kanban') {
        if (listContainer) listContainer.style.display = 'none';
        if (kanbanContainer) kanbanContainer.style.display = 'block';
        if (btnList) btnList.className = 'btn btn-outline-teal fw-bold';
        if (btnKanban) btnKanban.className = 'btn btn-teal fw-bold';
        syncKanbanBoardTasks();
    } else {
        if (listContainer) listContainer.style.display = 'block';
        if (kanbanContainer) kanbanContainer.style.display = 'none';
        if (btnList) btnList.className = 'btn btn-teal fw-bold';
        if (btnKanban) btnKanban.className = 'btn btn-outline-teal fw-bold';
    }
}

// ---------------------------------------------------------------------------
// DRAG & DROP — KANBAN OPÉRATIONNEL DES TÂCHES (ONGLET 2 DU STUDIO)
// ---------------------------------------------------------------------------
let draggedKanbanTaskInfo = null;

function onTaskKanbanDragStart(e, taskId, roleCode) {
    draggedKanbanTaskInfo = { taskId: String(taskId), roleCode: String(roleCode) };
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', JSON.stringify(draggedKanbanTaskInfo));
    }
    const el = e.currentTarget;
    if (el) setTimeout(() => el.classList.add('kanban-dragging'), 0);
}

function onTaskKanbanDragEnd(e) {
    document.querySelectorAll('.kanban-draggable-card.kanban-dragging').forEach(el => {
        el.classList.remove('kanban-dragging');
    });
    document.querySelectorAll('.kanban-dropzone.kanban-dropzone-active').forEach(el => {
        el.classList.remove('kanban-dropzone-active');
    });
    draggedKanbanTaskInfo = null;
}

function onTaskKanbanDragOver(e) {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    const zone = e.currentTarget;
    if (zone && !zone.classList.contains('kanban-dropzone-active')) {
        zone.classList.add('kanban-dropzone-active');
    }
}

function onTaskKanbanDragLeave(e) {
    const zone = e.currentTarget;
    if (zone && !zone.contains(e.relatedTarget)) {
        zone.classList.remove('kanban-dropzone-active');
    }
}

function onTaskKanbanDrop(e, targetStatus) {
    e.preventDefault();
    const zone = e.currentTarget;
    if (zone) zone.classList.remove('kanban-dropzone-active');

    let info = draggedKanbanTaskInfo;
    if (!info && e.dataTransfer) {
        try {
            info = JSON.parse(e.dataTransfer.getData('text/plain'));
        } catch (err) {}
    }
    if (!info || !info.taskId) return;
    changeTaskStatusFromKanban(info.taskId, info.roleCode, targetStatus);
}

// Construction dynamique du Kanban des tâches à partir des cartes du formulaire
function syncKanbanBoardTasks() {
    const cols = { todo: [], in_progress: [], blocked: [], done: [] };
    const cards = document.querySelectorAll('#rolesSectionsContainer .task-card-item');

    cards.forEach(c => {
        const taskId   = c.getAttribute('data-task-id');
        const roleCode = c.getAttribute('data-role') || 'level_designer';
        const status   = c.querySelector('.task-status-select')?.value || 'todo';
        const title    = c.querySelector('.task-title-input')?.value || '';
        const assignee = c.querySelector('.task-assignee-input')?.value || '';

        if (currentRoleFilter !== 'all' && roleCode !== currentRoleFilter) return;
        if (currentStatusFilter !== 'all' && status !== currentStatusFilter) return;

        const rDef = ROLES_DEF_MAP[roleCode] || { name: roleCode, icon: 'fa-gear', color: 'secondary' };
        cols[status] = cols[status] || [];
        cols[status].push({ taskId, roleCode, status, title, assignee, rDef });
    });

    ['todo', 'in_progress', 'blocked', 'done'].forEach(stKey => {
        const bodyEl  = document.getElementById('kanbanTaskColBody_' + stKey);
        const countEl = document.getElementById('kanbanTaskColCount_' + stKey);
        const list    = cols[stKey] || [];
        if (countEl) countEl.textContent = list.length;
        if (!bodyEl) return;

        bodyEl.innerHTML = '';
        list.forEach(item => {
            const cardEl = document.createElement('div');
            cardEl.className = 'card border bg-white p-2 shadow-xs kanban-draggable-card';
            cardEl.setAttribute('draggable', 'true');
            cardEl.addEventListener('dragstart', (ev) => onTaskKanbanDragStart(ev, item.taskId, item.roleCode));
            cardEl.addEventListener('dragend', (ev) => onTaskKanbanDragEnd(ev));
            cardEl.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-${item.rDef.color}-lt text-${item.rDef.color} fw-bold" style="font-size:0.68rem;">
                        <i class="fa-solid fa-grip-vertical me-1 text-muted"></i><i class="fa-solid ${item.rDef.icon} me-1"></i>${item.rDef.name}
                    </span>
                    <span class="font-monospace text-muted" style="font-size:0.68rem;">#${item.taskId}</span>
                </div>
                <div class="fw-bold text-dark small mb-1">${item.title}</div>
                <div class="small text-muted mb-2" style="font-size:0.72rem;">
                    <i class="fa-solid fa-user-ninja me-1"></i>${item.assignee}
                </div>
                <select class="form-select form-select-sm fw-bold py-0" style="font-size:0.72rem;"
                        onchange="changeTaskStatusFromKanban('${item.taskId}', '${item.roleCode}', this.value)">
                    <option value="todo" ${item.status === 'todo' ? 'selected' : ''}>⏳ À faire</option>
                    <option value="in_progress" ${item.status === 'in_progress' ? 'selected' : ''}>🔧 En cours</option>
                    <option value="blocked" ${item.status === 'blocked' ? 'selected' : ''}>⛔ Bloqué</option>
                    <option value="done" ${item.status === 'done' ? 'selected' : ''}>✅ Terminé</option>
                </select>
            `;
            bodyEl.appendChild(cardEl);
        });
    });
}

function changeTaskStatusFromKanban(taskId, roleCode, newStatus) {
    const card = document.getElementById('taskCard_' + taskId);
    if (!card) return;
    const sel = card.querySelector('.task-status-select');
    if (sel && sel.value !== newStatus) {
        sel.value = newStatus;
        onTaskStatusSelectChange(taskId, roleCode, newStatus);

        const scId = parseInt(document.getElementById('fullScenId')?.value || '0', 10);
        if (scId > 0) {
            const fd = new FormData();
            fd.append('action', 'ajax_update_task_status');
            fd.append('scenario_id', scId);
            fd.append('task_id', taskId);
            fd.append('task_status', newStatus);
            fetch('?page=gamestudio&tab=narrative_scenarios', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) showStudioToast(res.msg || 'Statut de la tâche mis à jour !');
                })
                .catch(() => {});
        }
    }
}

// Ajout dynamique d'une tâche pour un métier spécifique
function addNewTaskForRole(roleCode) {
    const rDef = ROLES_DEF_MAP[roleCode] || { name: 'Métier', icon: 'fa-gear', color: 'secondary', domain: 'Production' };
    const listContainer = document.getElementById(`roleTasksList_${roleCode}`);
    if (!listContainer) return;

    const placeholder = document.getElementById(`placeholder_${roleCode}`);
    if (placeholder) placeholder.remove();

    const uniqueTaskId = 'task_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

    const card = document.createElement('div');
    card.className = 'card border mb-2 shadow-none task-card-item border-teal';
    card.id = `taskCard_${uniqueTaskId}`;
    card.setAttribute('data-task-id', uniqueTaskId);
    card.setAttribute('data-role', roleCode);
    card.setAttribute('data-status', 'todo');

    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 border-secondary-subtle bg-light rounded-top task-compact-header" id="taskHeader_${uniqueTaskId}">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 240px;">
                <span class="badge bg-${rDef.color} text-white fw-bold font-monospace">Nouveau</span>
                <input type="text" class="form-control form-control-sm fw-bold text-dark border-0 bg-white shadow-xs task-title-input"
                       name="tasks[${uniqueTaskId}][title]" value="Nouvelle tâche — ${rDef.name}" required
                       oninput="markFormDirty(); syncKanbanBoardTasks();">
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width: 175px;">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-user-ninja"></i></span>
                    <input type="text" class="form-control form-control-sm bg-white task-assignee-input"
                           name="tasks[${uniqueTaskId}][assignee]" value="${rDef.name}" oninput="markFormDirty()">
                </div>
                <select class="form-select form-select-sm font-monospace fw-bold bg-white" name="tasks[${uniqueTaskId}][complexity]" style="width: 72px;" onchange="markFormDirty()">
                    <option value="S">S</option>
                    <option value="M" selected>M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select>
                <select class="form-select form-select-sm fw-bold task-status-select bg-white" name="tasks[${uniqueTaskId}][status]" style="width: 138px;"
                        onchange="onTaskStatusSelectChange('${uniqueTaskId}', '${roleCode}', this.value)">
                    <option value="todo" selected>⏳ À faire</option>
                    <option value="in_progress">🔧 En cours</option>
                    <option value="done">✅ Terminé</option>
                    <option value="blocked">⛔ Bloqué</option>
                </select>
                <button type="button" class="btn btn-white btn-sm p-1 px-2 border" onclick="toggleTaskDrawer('${uniqueTaskId}')">
                    <i class="fa-solid fa-sliders text-secondary"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeTaskCard('${uniqueTaskId}', '${roleCode}')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
        <input type="hidden" name="tasks[${uniqueTaskId}][task_id]" value="${uniqueTaskId}">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_code]" value="${roleCode}">
        <input type="hidden" name="tasks[${uniqueTaskId}][role_name]" value="${rDef.name}">
        <div class="p-3 bg-white border-top task-drawer-body" id="taskDrawer_${uniqueTaskId}" style="display: block;">
            <div class="row g-2">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-secondary m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                    <textarea class="form-control form-control-sm mt-1" name="tasks[${uniqueTaskId}][description]" rows="2" oninput="markFormDirty()">${rDef.desc || ''}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary m-0">Livrables / Fichiers Attendus :</label>
                    <input type="text" class="form-control form-control-sm font-monospace mt-1" name="tasks[${uniqueTaskId}][deliverables]" value="Livrable attendu" oninput="markFormDirty()">
                </div>
            </div>
        </div>
    `;

    listContainer.appendChild(card);
    markFormDirty(true);
    updateRoleTelemetry(roleCode);
    syncKanbanBoardTasks();
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeTaskCard(taskId, roleCode) {
    const card = document.getElementById(`taskCard_${taskId}`);
    if (card) {
        card.remove();
        markFormDirty(true);
        updateRoleTelemetry(roleCode);
        syncKanbanBoardTasks();
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
    if (textEl) textEl.textContent = `${total} tâche(s) • ${done} terminée(s) (${percent}%)`;

    const barEl = document.getElementById(`roleProgressBar_${roleCode}`);
    if (barEl) barEl.style.width = `${percent}%`;

    const badgePill = document.getElementById(`badgeCount_${roleCode}`);
    if (badgePill) badgePill.textContent = total;

    updateGlobalTasksTelemetry();
}

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
    if (badge) badge.textContent = `${done}/${total} (${percent}%)`;

    const countPill = document.getElementById('totalTasksCountPill');
    if (countPill) countPill.textContent = total;
}

// Filtres combinés Rôle + Statut dans la Matrice 2.0
function filterTaskRoleSection(roleCode, btnEl) {
    currentRoleFilter = roleCode;
    document.querySelectorAll('#roleFilterPills button').forEach(b => {
        b.className = 'btn btn-sm btn-outline-secondary';
    });
    if (btnEl) btnEl.className = 'btn btn-sm btn-purple fw-bold';
    applyCombinedTaskFilters();
    syncKanbanBoardTasks();
}

function filterTasksByStatus(statusCode, btnEl) {
    currentStatusFilter = statusCode;
    document.querySelectorAll('#statusFilterPills button').forEach(b => {
        b.classList.remove('btn-dark', 'fw-bold');
    });
    if (btnEl) btnEl.classList.add('btn-dark', 'fw-bold');
    applyCombinedTaskFilters();
    syncKanbanBoardTasks();
}

function applyCombinedTaskFilters() {
    document.querySelectorAll('.role-task-section').forEach(sec => {
        const rCode = sec.getAttribute('data-role');
        const roleMatch = (currentRoleFilter === 'all' || rCode === currentRoleFilter);
        if (!roleMatch) {
            sec.style.display = 'none';
            return;
        }

        let visibleCardsInRole = 0;
        const cards = sec.querySelectorAll('.task-card-item');
        cards.forEach(c => {
            const st = c.querySelector('.task-status-select')?.value || 'todo';
            const statusMatch = (currentStatusFilter === 'all' || st === currentStatusFilter);
            c.style.display = statusMatch ? 'block' : 'none';
            if (statusMatch) visibleCardsInRole++;
        });

        sec.style.display = (currentStatusFilter === 'all' || visibleCardsInRole > 0) ? 'block' : 'none';
    });
}

// Mise à jour en direct du Simulateur In-Game (Split-Screen)
function renderInGamePreviewLive() {
    const type     = document.getElementById('fullScenType')?.value || 'step';
    const stepNum  = document.getElementById('fullScenStepNumber')?.value || '1';
    const title    = document.getElementById('fullScenTitle')?.value || 'Titre du Scénario';
    const subtitle = document.getElementById('fullScenSubtitle')?.value || '';
    const era      = document.getElementById('fullScenEra')?.value || '1560 - Sengoku Jidai';
    const avatar   = document.getElementById('fullScenSpeakerAvatar')?.value || '🏯';
    const speaker  = document.getElementById('fullScenSpeakerName')?.value || 'Maître de la Sagesse';
    const role     = document.getElementById('fullScenSpeakerRole')?.value || 'Mentor Féodal';
    const dialogue = document.getElementById('fullScenDialogue')?.value || '...';
    const trigger  = document.getElementById('fullScenTrigger')?.value || 'Objectifs requis';
    const rewards  = document.getElementById('fullScenRewards')?.value || 'Récompenses du Shōgunat';

    const charCounter = document.getElementById('dialogueCharCounter');
    if (charCounter) {
        const len = dialogue.length;
        charCounter.textContent = `${len} car.`;
        charCounter.className = len > 320
            ? 'badge bg-warning-lt text-warning border font-monospace'
            : 'badge bg-light text-secondary border font-monospace';
    }

    const b = document.getElementById('livePreviewBadge');
    if (b) b.textContent = (type === 'step') ? `Step #${stepNum} • Mode Campagne` : `🏮 Événement Spécial`;

    const t = document.getElementById('livePreviewTitle');
    if (t) t.textContent = title;

    const sub = document.getElementById('livePreviewSubtitle');
    if (sub) sub.textContent = subtitle;

    const e = document.getElementById('livePreviewEra');
    if (e) e.textContent = era;

    const av = document.getElementById('livePreviewAvatar');
    if (av) av.textContent = avatar;

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

    // Rendu des boutons de Choix Narratifs du Daimyō
    const choicesBox = document.getElementById('livePreviewChoicesList');
    if (choicesBox) {
        choicesBox.innerHTML = '';
        const rows = document.querySelectorAll('#narrativeChoicesContainer .narrative-choice-row');
        rows.forEach(r => {
            const lbl = r.querySelector('.choice-label-input')?.value || 'Décision';
            const out = r.querySelector('.choice-outcome-input')?.value || '';
            const sty = r.querySelector('.choice-style-input')?.value || 'warning';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm fw-bold py-2 px-3 rounded shadow-sm d-flex justify-content-between align-items-center sengoku-choice-btn-${sty}`;
            btn.innerHTML = `
                <span>${lbl}</span>
                ${out ? `<span class="badge bg-dark bg-opacity-50 text-white fw-normal ms-2" style="font-size:0.68rem;">${out}</span>` : ''}
            `;
            btn.onclick = function() {
                const fb = document.getElementById('livePreviewChoiceFeedback');
                if (fb) {
                    fb.style.display = 'block';
                    fb.innerHTML = `<i class="fa-solid fa-scroll me-1"></i>Décision choisie : <strong>${lbl}</strong> ${out ? '➔ <em>' + out + '</em>' : ''}`;
                }
            };
            choicesBox.appendChild(btn);
        });
    }
}

// Effet machine à écrire (Typewriter) dans le Simulateur In-Game
function playTypewriterSimulation() {
    const diaEl = document.getElementById('livePreviewDialogue');
    const rawText = document.getElementById('fullScenDialogue')?.value || 'Salutations, noble Daimyō...';
    if (!diaEl) return;

    if (typewriterTimer) clearInterval(typewriterTimer);
    const fullStr = `« ${rawText} »`;
    diaEl.textContent = '';
    let i = 0;
    typewriterTimer = setInterval(() => {
        diaEl.textContent += fullStr.charAt(i);
        i++;
        if (i >= fullStr.length) {
            clearInterval(typewriterTimer);
            typewriterTimer = null;
        }
    }, 18);
}

// Duplication d'un scénario
function duplicateCurrentScenario(scId) {
    if (!confirm('Dupliquer ce scénario avec l\'ensemble de ses tâches métiers ?')) return;
    document.getElementById('catQuickActionName').value = 'duplicate_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

// Suppression d'un scénario (depuis l'éditeur pleine page -> redirection catalogue)
function deleteCurrentScenario(scId) {
    if (!confirm(`Supprimer définitivement le scénario #${scId} et toutes ses tâches ? Cette action est irréversible.`)) return;
    document.getElementById('catQuickActionName').value = 'delete_scenario';
    document.getElementById('catQuickScenarioId').value = scId;
    document.getElementById('catalogQuickActionForm').submit();
}

// Suppression rapide AJAX d'un scénario depuis la vue Cartes ou le Kanban Pipeline (sans quitter la vue active)
function deleteScenarioFromCatalogAjax(scId) {
    if (!confirm(`Supprimer définitivement le scénario #${scId} et toutes ses tâches ? Cette action est irréversible.`)) return;

    const fd = new FormData();
    fd.append('action', 'ajax_delete_scenario');
    fd.append('ajax', '1');
    fd.append('scenario_id', scId);

    fetch('?page=gamestudio&tab=narrative_scenarios', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            const cardCol = document.getElementById('catalogCardCol_' + scId);
            if (cardCol) cardCol.remove();
            const kanbanCard = document.getElementById('kanbanPipelineCard_' + scId);
            if (kanbanCard) kanbanCard.remove();
            if (typeof refreshPipelineKanbanCounts === 'function') {
                refreshPipelineKanbanCounts();
            }
            showStudioToast(data.msg || `Scénario #${scId} supprimé définitivement.`);
        } else {
            showStudioToast(data.msg || 'Échec de la suppression du scénario.', true);
        }
    })
    .catch(err => {
        showStudioToast('Erreur réseau : ' + err, true);
    });
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
            showStudioToast('Erreur lors du chargement de l\'export : ' + err, true);
        });
}

function copyExportMarkdownText() {
    const ta = document.getElementById('scenarioExportTextarea');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        showStudioToast('Cahier GDD Markdown copié dans le presse-papier !');
    });
}

// Initialisation au chargement du Studio ou du Catalogue
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('fullPageScenarioForm')) {
        renderInGamePreviewLive();
    } else if (document.getElementById('catalogView_cards')) {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const urlMode = urlParams.get('view_mode');
            const savedMode = localStorage.getItem('openshogun_narrative_catalog_view');
            if (urlMode && ['cards', 'timeline', 'kanban'].includes(urlMode)) {
                switchCatalogViewMode(urlMode);
            } else if (savedMode && ['cards', 'timeline', 'kanban'].includes(savedMode)) {
                switchCatalogViewMode(savedMode);
            }
        } catch (e) {}
    }
});
</script>
