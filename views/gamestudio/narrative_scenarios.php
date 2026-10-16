<?php
/**
 * Game Studio - Narrative Designer : Scénarios de Jeu & Matrice des 10 Tâches Métiers
 * Fichier : views/gamestudio/narrative_scenarios.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/ScenarioEngine.php';
$scenarioEngine = new ScenarioEngine();

// Filtres
$filterType = !empty($_GET['type']) && in_array($_GET['type'], ScenarioEngine::VALID_TYPES, true) ? $_GET['type'] : null;
$filterStatus = !empty($_GET['status']) && in_array($_GET['status'], ScenarioEngine::VALID_STATUSES, true) ? $_GET['status'] : null;
$searchQuery = trim($_GET['search'] ?? '');

$scenarios = $scenarioEngine->getScenarios($filterType, $filterStatus, $searchQuery);

// KPIs globaux
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
$rolesMap = ScenarioEngine::ROLES;
?>

<div class="gamestudio-narrative-scenarios">

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 1. EN-TÊTE PRINCIPAL & ACTIONS                                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="page-pretitle text-secondary text-uppercase fw-bold">Narrative Designer &bull; Scénarisation &amp; Pipeline Studio</div>
            <h2 class="card-title text-purple d-flex align-items-center gap-2 m-0" style="font-size:1.4rem;">
                <i class="fa-solid fa-book-journal-whills text-purple"></i>
                <span>Scénarios de Jeu &amp; Matrice des 10 Tâches Métiers</span>
                <span class="badge bg-purple-lt text-purple font-monospace">GDD &amp; Pipeline v1.0</span>
            </h2>
            <div class="text-secondary small mt-1">
                Scénarisation des étapes (steps) de la progression gameplay, conception des événements spéciaux et matrice opérationnelle des 10 métiers.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-purple fw-bold shadow-sm d-inline-flex align-items-center gap-2" onclick="openCreateScenarioModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Nouveau Scénario</span>
            </button>
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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 2. CARTES KPIS DE NARRATION & TÂCHES                                -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 3. BARRE DE FILTRAGE & RECHERCHE                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="narrative_scenarios">

                <!-- Filtre Type (Tabs pill) -->
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

                <!-- Filtre Statut -->
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

                <!-- Champ Recherche -->
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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 4. LISTE DES SCÉNARIOS & MATRICE DES 10 TÂCHES                      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php if (empty($scenarios)): ?>
        <div class="card shadow-sm border-0 py-5 text-center">
            <div class="card-body">
                <div class="avatar avatar-xl bg-purple-lt text-purple mx-auto mb-3" style="font-size:2rem;">
                    <i class="fa-solid fa-feather-pointed"></i>
                </div>
                <h3 class="fw-bold text-dark">Aucun scénario ne correspond aux critères</h3>
                <p class="text-secondary max-w-sm mx-auto">
                    Ajustez vos filtres de recherche ou créez un tout nouveau scénario pour enrichir le parcours des daimyōs et les événements du jeu.
                </p>
                <button type="button" class="btn btn-purple mt-2" onclick="openCreateScenarioModal()">
                    <i class="fa-solid fa-plus me-1"></i>Créer un Scénario
                </button>
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
                $metrics = $sc['metrics'] ?? ['percent' => 0, 'done' => 0, 'total' => 10];
                $pct = $metrics['percent'];
                $barCol = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-teal' : (($pct > 0) ? 'bg-primary' : 'bg-secondary'));
                ?>
                <div class="col-md-12 col-xl-6 mb-3">
                    <div class="card shadow-sm border h-100 d-flex flex-column transition-hover">
                        <!-- En-tête de la carte -->
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

                        <!-- Corps du Scénario -->
                        <div class="card-body p-3 flex-grow-1">
                            <h3 class="card-title text-dark fw-bold mb-1 d-flex align-items-center justify-content-between">
                                <span><?= htmlspecialchars($sc['title']) ?></span>
                            </h3>
                            <?php if (!empty($sc['subtitle'])): ?>
                                <div class="text-secondary small fw-medium mb-2 italic">
                                    <?= htmlspecialchars($sc['subtitle']) ?>
                                </div>
                            <?php endif; ?>

                            <p class="text-secondary small mb-3 line-clamp-3" style="line-height:1.5;">
                                <?= htmlspecialchars($sc['synopsis']) ?>
                            </p>

                            <!-- Bloc Narrateur & Dialogue condensé -->
                            <div class="p-2 bg-light rounded border mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="avatar avatar-xs rounded bg-purple-lt text-purple fw-bold">
                                        <i class="fa-solid fa-comment"></i>
                                    </span>
                                    <strong class="small text-dark"><?= htmlspecialchars($sc['speaker_name'] ?? 'Narrateur') ?></strong>
                                    <span class="small text-muted">(<?= htmlspecialchars($sc['speaker_role'] ?? 'Voix du Destin') ?>)</span>
                                </div>
                                <div class="small text-muted italic ps-4 border-start border-2 border-purple ms-2">
                                    « <?= htmlspecialchars(mb_substr($sc['dialogue'] ?? '', 0, 140)) ?><?= mb_strlen($sc['dialogue'] ?? '') > 140 ? '...' : '' ?> »
                                </div>
                            </div>

                            <!-- Conditions & Récompenses condensées -->
                            <div class="row g-2 mb-3 small">
                                <div class="col-sm-6">
                                    <span class="text-muted fw-bold d-block"><i class="fa-solid fa-bolt text-warning me-1"></i>Déclencheur :</span>
                                    <span class="text-dark"><?= htmlspecialchars($sc['trigger_condition'] ?? 'Manuel') ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted fw-bold d-block"><i class="fa-solid fa-gift text-purple me-1"></i>Récompenses :</span>
                                    <span class="text-dark text-truncate d-block" title="<?= htmlspecialchars($sc['rewards_summary'] ?? 'Aucune') ?>"><?= htmlspecialchars($sc['rewards_summary'] ?? 'Aucune') ?></span>
                                </div>
                            </div>

                            <hr class="my-2">

                            <!-- ═══════════════════════════════════════════════════ -->
                            <!-- MATRICE DES 10 TÂCHES MÉTIERS                       -->
                            <!-- ═══════════════════════════════════════════════════ -->
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-users-gear text-teal"></i>
                                        <span>Matrice des 10 Tâches Métiers</span>
                                    </span>
                                    <span class="small font-monospace fw-bold text-secondary">
                                        <?= $metrics['done'] ?>/<?= $metrics['total'] ?> (<?= $pct ?>%)
                                    </span>
                                </div>

                                <!-- Barre d'avancement -->
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar <?= $barCol ?>" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <!-- Pastilles des 10 Métiers -->
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
                                        $tooltipText = htmlspecialchars("{$rDef['name']} : {$task['title']} [{$tStatusLabel}]");
                                        ?>
                                        <span class="badge <?= $tBadgeClass ?> d-inline-flex align-items-center justify-content-center p-1 rounded" 
                                              style="width: 26px; height: 26px; font-size: 0.75rem; cursor: pointer;"
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top" 
                                              title="<?= $tooltipText ?>"
                                              onclick="quickToggleTask(<?= $sc['id'] ?>, <?= $task['task_id'] ?>, '<?= $tStatus ?>')">
                                            <i class="fa-solid <?= $rDef['icon'] ?>"></i>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pied de la carte / Actions -->
                        <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center py-2 px-3 border-top">
                            <span class="small font-monospace text-muted">
                                ID #<?= $sc['id'] ?> &bull; <?= htmlspecialchars($sc['slug']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" title="Exporter le Cahier GDD (Markdown)" onclick="exportScenarioMarkdown(<?= $sc['id'] ?>)">
                                    <i class="fa-solid fa-file-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" title="Dupliquer ce scénario et ses 10 tâches" onclick="duplicateScenario(<?= $sc['id'] ?>)">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <button type="button" class="btn btn-purple btn-sm fw-bold d-inline-flex align-items-center gap-1" onclick="openEditScenarioModal(<?= htmlspecialchars(json_encode($sc), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Éditer &amp; Tâches</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 1 : ÉDITION & CONSULTATION COMPLÈTE DU SCÉNARIO & DES TÂCHES -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalScenarioDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="" id="scenarioForm">
                <input type="hidden" name="action" id="scenarioFormAction" value="update_scenario">
                <input type="hidden" name="scenario_id" id="scenarioFormId" value="0">

                <div class="modal-header bg-dark text-white py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-feather-pointed text-purple fs-3"></i>
                        <div>
                            <h4 class="modal-title text-white m-0" id="modalScenarioTitle">Édition du Scénario</h4>
                            <div class="small text-muted font-monospace" id="modalScenarioSlug">ID #0 &bull; slug</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Navigation interne par onglets dans la modale -->
                <div class="border-bottom bg-light px-3 pt-2">
                    <ul class="nav nav-tabs card-header-tabs" id="scenarioModalTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active fw-bold text-dark" id="tab-scen-narrative" data-bs-toggle="tab" href="#scen-tab-content-narrative" role="tab">
                                <i class="fa-solid fa-scroll text-purple me-1"></i>Trame Narrative &amp; Scénario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold text-dark" id="tab-scen-tasks" data-bs-toggle="tab" href="#scen-tab-content-tasks" role="tab">
                                <i class="fa-solid fa-users-gear text-teal me-1"></i>Matrice des 10 Tâches Métiers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold text-dark" id="tab-scen-preview" data-bs-toggle="tab" href="#scen-tab-content-preview" role="tab" onclick="renderInGamePreview()">
                                <i class="fa-solid fa-eye text-warning me-1"></i>Aperçu In-Game (Joueur)
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-3 p-lg-4">
                    <div class="tab-content">

                        <!-- ONGLET 1 : TRAME NARRATIVE -->
                        <div class="tab-pane fade show active" id="scen-tab-content-narrative" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-dark">Titre Épique du Scénario :</label>
                                    <input type="text" class="form-control" name="title" id="scenTitle" required placeholder="Ex: L'Éveil du Fief & l'Appel du Clan">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Identifiant Slug (Unique) :</label>
                                    <input type="text" class="form-control font-monospace" name="slug" id="scenSlug" placeholder="step-01-eveil-du-fief">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Type de Scénario :</label>
                                    <select class="form-select" name="type" id="scenType" onchange="toggleStepNumberField()">
                                        <option value="step">⛩️ Étape du Mode de Jeu (Step)</option>
                                        <option value="special_event">🏮 Événement Spécial</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="scenStepNumberGroup">
                                    <label class="form-label fw-bold text-dark">Numéro d'Étape (Step #) :</label>
                                    <input type="number" class="form-control" name="step_number" id="scenStepNumber" min="1" max="100" placeholder="1 à 10">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Statut de Production :</label>
                                    <select class="form-select" name="status" id="scenStatus">
                                        <option value="draft">📝 Brouillon</option>
                                        <option value="in_review">🔍 En revue narrative</option>
                                        <option value="in_production">⚙️ En production (Tâches ouvertes)</option>
                                        <option value="ready">✅ Prêt / Validé</option>
                                        <option value="published">🚀 En ligne dans le jeu (Live)</option>
                                        <option value="archived">📦 Archivé</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Sous-titre / Acte Dramatique :</label>
                                    <input type="text" class="form-control" name="subtitle" id="scenSubtitle" placeholder="Ex: Prologue : Fondation et première moisson">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Période Historique &amp; Ambiance :</label>
                                    <input type="text" class="form-control" name="era" id="scenEra" placeholder="Ex: 1560 - Aube du Sengoku Jidai">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Synopsis Narratif &amp; Contexte Scénaristique :</label>
                                    <textarea class="form-control" name="synopsis" id="scenSynopsis" rows="3" placeholder="Décrivez les enjeux géopolitiques, les conflits féodaux et les défis que le joueur devra surmonter..."></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Personnage Narrateur (PNJ) :</label>
                                    <input type="text" class="form-control" name="speaker_name" id="scenSpeakerName" placeholder="Ex: Maître de la Sagesse (Chie no Sensei)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Rôle / Titre du PNJ :</label>
                                    <input type="text" class="form-control" name="speaker_role" id="scenSpeakerRole" placeholder="Ex: Mentor Vénérable &amp; Gardien des Traditions">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark">Dialogue / Discours Prononcé au Joueur :</label>
                                    <textarea class="form-control font-serif" name="dialogue" id="scenDialogue" rows="3" placeholder="Rédigez la prise de parole solennelle ou poétique adressée au joueur..."></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Conditions de Déclenchement (Triggers In-Game) :</label>
                                    <input type="text" class="form-control" name="trigger_condition" id="scenTriggerCondition" placeholder="Ex: Tenshu niveau 1 construit, Date du solstice d'hiver...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Récompenses &amp; Effets Narratifs :</label>
                                    <input type="text" class="form-control" name="rewards_summary" id="scenRewardsSummary" placeholder="Ex: 100 Kobans, Déverrouillage des rizières, +25 Réputation...">
                                </div>
                            </div>
                        </div>

                        <!-- ONGLET 2 : MATRICE DES 10 TÂCHES MÉTIERS -->
                        <div class="tab-pane fade" id="scen-tab-content-tasks" role="tabpanel">
                            <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                                <i class="fa-solid fa-circle-info fs-3"></i>
                                <div>
                                    <strong>Matrice Intégrale des 10 Métiers :</strong> Chaque étape ou événement requiert la coordination transversale des 10 expertises (Level Design, Dev Back/Front/Réseau, Artistes 2D/3D, Audio, QA, Live Ops). Ajustez les statuts, assignés et livrables pour chaque métier ci-dessous.
                                </div>
                            </div>

                            <div id="tasksContainer" class="d-flex flex-column gap-3">
                                <!-- Généré dynamiquement en JS selon le scénario sélectionné -->
                            </div>
                        </div>

                        <!-- ONGLET 3 : APERÇU JOUEUR IN-GAME -->
                        <div class="tab-pane fade" id="scen-tab-content-preview" role="tabpanel">
                            <div class="text-center py-2 text-muted small mb-3">
                                Simulation de la modale telle qu'elle est vécue par le joueur Daimyō lorsqu'il atteint ce step ou déclenche cet événement :
                            </div>

                            <div class="max-w-xl mx-auto p-4 rounded-3 text-white shadow-lg" style="background: linear-gradient(135deg, #0b1120 0%, #1e1b4b 100%); border: 2px solid #eab308; max-width:680px;">
                                <div class="text-center mb-3">
                                    <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-1 mb-2 font-monospace" id="previewBadgeType">Step #1 &bull; Chronique Féodale</span>
                                    <h2 class="text-warning fw-bold m-0" id="previewTitle">Titre du Scénario</h2>
                                    <div class="text-light-subtle small mt-1 font-monospace" id="previewEra">1560 - Sengoku Jidai</div>
                                </div>

                                <div class="p-3 rounded bg-dark-subtle border border-secondary mb-3 d-flex gap-3 align-items-start">
                                    <div class="avatar avatar-lg rounded-circle bg-purple-lt text-purple flex-shrink-0" style="font-size:1.8rem;">
                                        🏯
                                    </div>
                                    <div>
                                        <div class="fw-bold text-warning" id="previewSpeaker">Maître de la Sagesse</div>
                                        <div class="text-muted small mb-2" id="previewSpeakerRole">Mentor du Fief</div>
                                        <div class="text-white-50 italic" style="line-height:1.5;" id="previewDialogue">
                                            « Dialogue... »
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 text-start small mb-3">
                                    <div class="col-sm-6">
                                        <div class="p-2 rounded bg-dark border border-secondary">
                                            <div class="text-muted fw-bold">🎯 Objectif Requis :</div>
                                            <div class="text-light" id="previewTrigger">Objectif...</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-2 rounded bg-dark border border-secondary">
                                            <div class="text-muted fw-bold">🎁 Récompenses du Shōgunat :</div>
                                            <div class="text-warning fw-bold" id="previewRewards">Récompenses...</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="button" class="btn btn-warning fw-bold px-4 py-2 text-dark shadow">
                                        ⚔️ Décréter la Marche du Fief
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteScenario" onclick="confirmDeleteCurrentScenario()">
                            <i class="fa-solid fa-trash me-1"></i>Supprimer ce Scénario
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-purple fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Enregistrer le Scénario &amp; ses 10 Tâches</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 2 : EXPORT GDD / MARKDOWN                                    -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
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
                    <span class="text-muted small">Copiez ce markdown complet pour vos documentations d'équipe, Notion, ou GitHub Wiki :</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyExportMarkdown()">
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

<!-- Formulaire POST caché pour actions rapides (duplication, suppression, task toggle) -->
<form id="quickActionForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="action" id="quickActionName" value="">
    <input type="hidden" name="scenario_id" id="quickScenarioId" value="0">
    <input type="hidden" name="task_id" id="quickTaskId" value="0">
    <input type="hidden" name="task_status" id="quickTaskStatus" value="">
</form>

<script>
const ROLES_MAP = <?= json_encode($rolesMap, JSON_UNESCAPED_UNICODE) ?>;

// Ouvre la modale pour un nouveau scénario
function openCreateScenarioModal() {
    document.getElementById('scenarioFormAction').value = 'create_scenario';
    document.getElementById('scenarioFormId').value = '0';
    document.getElementById('modalScenarioTitle').textContent = 'Création d\'un Nouveau Scénario';
    document.getElementById('modalScenarioSlug').textContent = 'Génération automatique des 10 tâches métiers clés';
    document.getElementById('btnDeleteScenario').style.display = 'none';

    document.getElementById('scenTitle').value = '';
    document.getElementById('scenSlug').value = '';
    document.getElementById('scenType').value = 'step';
    document.getElementById('scenStepNumber').value = '<?= $stepsCount + 1 ?>';
    document.getElementById('scenStatus').value = 'draft';
    document.getElementById('scenSubtitle').value = '';
    document.getElementById('scenEra').value = '1560 - Sengoku Jidai';
    document.getElementById('scenSynopsis').value = '';
    document.getElementById('scenSpeakerName').value = 'Maître de la Sagesse (Chie no Sensei)';
    document.getElementById('scenSpeakerRole').value = 'Mentor Vénérable & Gardien des Traditions';
    document.getElementById('scenDialogue').value = '';
    document.getElementById('scenTriggerCondition').value = '';
    document.getElementById('scenRewardsSummary').value = '';

    toggleStepNumberField();

    // Générer 10 tâches par défaut pour la modale
    renderTasksEditor(generateDefaultTasksArray());

    // Activer le premier onglet
    const firstTab = new bootstrap.Tab(document.getElementById('tab-scen-narrative'));
    firstTab.show();

    const modalEl = document.getElementById('modalScenarioDetail');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

// Ouvre la modale pour éditer un scénario existant
function openEditScenarioModal(sc) {
    if (!sc) return;

    document.getElementById('scenarioFormAction').value = 'update_scenario';
    document.getElementById('scenarioFormId').value = sc.id;
    document.getElementById('modalScenarioTitle').textContent = `Édition : ${sc.title}`;
    document.getElementById('modalScenarioSlug').textContent = `ID #${sc.id} &bull; ${sc.slug}`;
    document.getElementById('btnDeleteScenario').style.display = 'inline-block';

    document.getElementById('scenTitle').value = sc.title || '';
    document.getElementById('scenSlug').value = sc.slug || '';
    document.getElementById('scenType').value = sc.type || 'step';
    document.getElementById('scenStepNumber').value = sc.step_number || '';
    document.getElementById('scenStatus').value = sc.status || 'draft';
    document.getElementById('scenSubtitle').value = sc.subtitle || '';
    document.getElementById('scenEra').value = sc.era || '';
    document.getElementById('scenSynopsis').value = sc.synopsis || '';
    document.getElementById('scenSpeakerName').value = sc.speaker_name || '';
    document.getElementById('scenSpeakerRole').value = sc.speaker_role || '';
    document.getElementById('scenDialogue').value = sc.dialogue || '';
    document.getElementById('scenTriggerCondition').value = sc.trigger_condition || '';
    document.getElementById('scenRewardsSummary').value = sc.rewards_summary || '';

    toggleStepNumberField();

    // Rendre l'éditeur des 10 tâches
    renderTasksEditor(sc.tasks || []);

    // Activer le premier onglet
    const firstTab = new bootstrap.Tab(document.getElementById('tab-scen-narrative'));
    firstTab.show();

    const modalEl = document.getElementById('modalScenarioDetail');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function toggleStepNumberField() {
    const type = document.getElementById('scenType').value;
    const group = document.getElementById('scenStepNumberGroup');
    if (group) {
        group.style.display = (type === 'step') ? 'block' : 'none';
    }
}

// Génère la liste des 10 tâches par défaut côté client
function generateDefaultTasksArray() {
    const arr = [];
    let i = 1;
    for (const [key, def] of Object.entries(ROLES_MAP)) {
        arr.push({
            task_id: i++,
            role_code: key,
            role_name: def.name,
            title: `Cahier des charges & spécifications (${def.name})`,
            description: def.desc,
            assignee: def.name,
            complexity: (key === 'backend' || key === 'frontend' || key === 'level_designer') ? 'L' : 'M',
            status: 'todo',
            deliverables: 'Livrable attendu'
        });
    }
    return arr;
}

// Rendu des 10 tâches dans l'onglet de la modale
function renderTasksEditor(tasks) {
    const container = document.getElementById('tasksContainer');
    if (!container) return;
    container.innerHTML = '';

    if (!tasks || tasks.length === 0) {
        tasks = generateDefaultTasksArray();
    }

    tasks.forEach((t, index) => {
        const rDef = ROLES_MAP[t.role_code] || { name: t.role_name || 'Métier', icon: 'fa-gear', color: 'secondary' };
        const card = document.createElement('div');
        card.className = 'card border p-3 shadow-none bg-light-subtle';

        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm rounded bg-${rDef.color}-lt text-${rDef.color} fw-bold">
                        <i class="fa-solid ${rDef.icon}"></i>
                    </span>
                    <div>
                        <strong class="text-dark">Tâche #${t.task_id} &bull; ${rDef.name}</strong>
                        <div class="text-secondary small">${rDef.domain || ''}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-bold">Complexité :</label>
                    <select class="form-select form-select-sm" name="tasks[${index}][complexity]" style="width: auto;">
                        <option value="S" ${t.complexity === 'S' ? 'selected' : ''}>S (Court)</option>
                        <option value="M" ${t.complexity === 'M' ? 'selected' : ''}>M (Moyen)</option>
                        <option value="L" ${t.complexity === 'L' ? 'selected' : ''}>L (Important)</option>
                        <option value="XL" ${t.complexity === 'XL' ? 'selected' : ''}>XL (Majeur)</option>
                    </select>

                    <label class="small text-muted fw-bold ms-2">Statut :</label>
                    <select class="form-select form-select-sm fw-bold" name="tasks[${index}][status]" style="width: auto;">
                        <option value="todo" ${t.status === 'todo' ? 'selected' : ''}>⏳ À faire</option>
                        <option value="in_progress" ${t.status === 'in_progress' ? 'selected' : ''}>🔧 En cours</option>
                        <option value="done" ${t.status === 'done' ? 'selected' : ''}>✅ Terminé</option>
                        <option value="blocked" ${t.status === 'blocked' ? 'selected' : ''}>⛔ Bloqué</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="tasks[${index}][task_id]" value="${t.task_id}">
            <input type="hidden" name="tasks[${index}][role_code]" value="${t.role_code}">
            <input type="hidden" name="tasks[${index}][role_name]" value="${t.role_name || rDef.name}">

            <div class="row g-2">
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-dark m-0">Titre Opérationnel de la Tâche :</label>
                    <input type="text" class="form-control form-control-sm" name="tasks[${index}][title]" value="${escapeHtml(t.title || '')}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-dark m-0">Artisan / Développeur Assigné :</label>
                    <input type="text" class="form-control form-control-sm" name="tasks[${index}][assignee]" value="${escapeHtml(t.assignee || rDef.name)}">
                </div>
                <div class="col-12 mt-2">
                    <label class="form-label small fw-bold text-dark m-0">Cahier des Charges &amp; Spécifications Métier :</label>
                    <textarea class="form-control form-control-sm" name="tasks[${index}][description]" rows="2">${escapeHtml(t.description || '')}</textarea>
                </div>
                <div class="col-12 mt-1">
                    <label class="form-label small fw-bold text-dark m-0">Livrables / Fichiers Attendus :</label>
                    <input type="text" class="form-control form-control-sm font-monospace" name="tasks[${index}][deliverables]" value="${escapeHtml(t.deliverables || '')}">
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Mise à jour de l'aperçu In-Game
function renderInGamePreview() {
    const isStep = (document.getElementById('scenType').value === 'step');
    const stepNum = document.getElementById('scenStepNumber').value || '1';
    const title = document.getElementById('scenTitle').value || 'Sans Titre';
    const era = document.getElementById('scenEra').value || '1560 - Sengoku Jidai';
    const speaker = document.getElementById('scenSpeakerName').value || 'Maître de la Sagesse';
    const role = document.getElementById('scenSpeakerRole').value || 'Mentor Féodal';
    const dialogue = document.getElementById('scenDialogue').value || '...';
    const trigger = document.getElementById('scenTriggerCondition').value || 'Début de partie';
    const rewards = document.getElementById('scenRewardsSummary').value || 'Honneur et prospérité';

    document.getElementById('previewBadgeType').textContent = isStep ? `Step #${stepNum} • Mode Campagne` : `🏮 Événement Spécial`;
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewEra').textContent = era;
    document.getElementById('previewSpeaker').textContent = speaker;
    document.getElementById('previewSpeakerRole').textContent = role;
    document.getElementById('previewDialogue').textContent = `« ${dialogue} »`;
    document.getElementById('previewTrigger').textContent = trigger;
    document.getElementById('previewRewards').textContent = rewards;
}

// Bascule rapide d'un statut de tâche (To Do -> In Progress -> Done)
function quickToggleTask(scId, taskId, currentStatus) {
    const nextStatus = (currentStatus === 'todo') ? 'in_progress' : ((currentStatus === 'in_progress') ? 'done' : 'todo');
    document.getElementById('quickActionName').value = 'update_task_status';
    document.getElementById('quickScenarioId').value = scId;
    document.getElementById('quickTaskId').value = taskId;
    document.getElementById('quickTaskStatus').value = nextStatus;
    document.getElementById('quickActionForm').submit();
}

// Duplication d'un scénario
function duplicateScenario(scId) {
    if (!confirm('Dupliquer ce scénario avec l\'ensemble de ses 10 tâches métiers ?')) return;
    document.getElementById('quickActionName').value = 'duplicate_scenario';
    document.getElementById('quickScenarioId').value = scId;
    document.getElementById('quickActionForm').submit();
}

// Suppression du scénario courant
function confirmDeleteCurrentScenario() {
    const id = document.getElementById('scenarioFormId').value;
    if (!id || id === '0') return;
    if (!confirm(`Supprimer définitivement le scénario #${id} et toutes ses tâches ? Cette action est irréversible.`)) return;

    document.getElementById('quickActionName').value = 'delete_scenario';
    document.getElementById('quickScenarioId').value = id;
    document.getElementById('quickActionForm').submit();
}

// Export Markdown / GDD
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

function copyExportMarkdown() {
    const ta = document.getElementById('scenarioExportTextarea');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        alert('Cahier GDD Markdown copié dans le presse-papier avec succès !');
    });
}
</script>

