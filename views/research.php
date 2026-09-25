<?php
/**
 * Vue de la Recherche et Technologies Féodales (Académie des Savoirs & Forge Militaire)
 * Architecture POO avec ResearchService & ResearchDTO - Thème 100% Tabler.io
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/ResearchService.php';

$researchService = new ResearchService();
$vm = $researchService->getViewModel(
    (int)$user['id'],
    (int)$planet['id'],
    [
        'metal' => (int)($planet['metal'] ?? 0),
        'crystal' => (int)($planet['crystal'] ?? 0),
        'deuterium' => (int)($planet['deuterium'] ?? 0),
    ],
    $_GET
);

$labLvl = $vm['lab_level'];
$activeResearch = $vm['active_queue'];
$items = $vm['items'];
$filters = $vm['filters'];
$pagination = $vm['pagination'];

/**
 * Helper de pagination Tabler pour la vue recherche
 */
function renderResearchPagination(int $currentPage, int $totalPages, array $filters): void {
    if ($totalPages <= 1) return;

    // Construction de l'URL de base sans le paramètre 'p'
    $queryParams = $filters;
    unset($queryParams['page']); // 'page' dans les filtres correspond à la pagination interne
    $queryParams['page'] = 'research';

    echo '<ul class="pagination pagination-sm m-0 ms-auto">';

    // Bouton Précédent
    $prevDisabled = ($currentPage <= 1);
    $queryParams['p'] = max(1, $currentPage - 1);
    $prevUrl = '?' . http_build_query($queryParams);
    echo '<li class="page-item ' . ($prevDisabled ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="' . ($prevDisabled ? 'javascript:void(0)' : htmlspecialchars($prevUrl)) . '" tabindex="' . ($prevDisabled ? '-1' : '0') . '" aria-disabled="' . ($prevDisabled ? 'true' : 'false') . '">';
    echo '&lsaquo; Précédent';
    echo '</a>';
    echo '</li>';

    // Plage intelligente de pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $queryParams['p'] = 1;
        echo '<li class="page-item"><a class="page-link" href="?' . htmlspecialchars(http_build_query($queryParams)) . '">1</a></li>';
        if ($start > 2) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $isActive = ($i === $currentPage);
        $queryParams['p'] = $i;
        echo '<li class="page-item ' . ($isActive ? 'active' : '') . '">';
        echo '<a class="page-link" href="?' . htmlspecialchars(http_build_query($queryParams)) . '">' . $i . '</a>';
        echo '</li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $queryParams['p'] = $totalPages;
        echo '<li class="page-item"><a class="page-link" href="?' . htmlspecialchars(http_build_query($queryParams)) . '">' . $totalPages . '</a></li>';
    }

    // Bouton Suivant
    $nextDisabled = ($currentPage >= $totalPages);
    $queryParams['p'] = min($totalPages, $currentPage + 1);
    $nextUrl = '?' . http_build_query($queryParams);
    echo '<li class="page-item ' . ($nextDisabled ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="' . ($nextDisabled ? 'javascript:void(0)' : htmlspecialchars($nextUrl)) . '" tabindex="' . ($nextDisabled ? '-1' : '0') . '" aria-disabled="' . ($nextDisabled ? 'true' : 'false') . '">';
    echo 'Suivant &rsaquo;';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
}
?>

<!-- 🏆 EN-TÊTE DE PAGE TABLER -->
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle text-secondary">Traité de Guerre &amp; Savoirs Féodaux</div>
                <h2 class="page-title d-flex align-items-center gap-2">
                    <span class="text-primary">📜</span> Académie des Savoirs &amp; Forge Militaire
                    <span class="badge bg-purple-lt text-purple ms-1" title="Niveau actuel de l'Académie dans ce fief">Niveau <?= $labLvl ?></span>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?page=city" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
                        <span>🏯</span> Cité Castrale
                    </a>
                    <button type="button" onclick="window.location.reload()" class="btn btn-white d-inline-flex align-items-center gap-1 shadow-sm" title="Actualiser la liste">
                        <span>🔄</span> Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xl">
    <?php if ($labLvl < 1): ?>
        <!-- ========================================== -->
        <!-- ÉTAT VIDE : ACADÉMIE NON CONSTRUITE        -->
        <!-- ========================================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="empty py-5">
                    <div class="empty-icon fs-1 text-secondary">🏯</div>
                    <p class="empty-title fs-2">Académie des Savoirs non érigée</p>
                    <p class="empty-subtitle text-secondary">
                        Vous devez bâtir une <strong>Académie des Savoirs</strong> dans votre cité castrale pour perfectionner la métallurgie du Tamahagane, l'art du tir et les tactiques militaires.
                    </p>
                    <div class="empty-action">
                        <a href="?page=city" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                            <span>🏯</span> Bâtir l'Académie dans la Cité Castrale
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- ========================================== -->
        <!-- RECHERCHE EN COURS (FILE ACTIVE)           -->
        <!-- ========================================== -->
        <?php if ($activeResearch): ?>
            <div class="card shadow-sm mb-4 border-purple-subtle bg-purple-lt">
                <div class="card-status-top bg-purple"></div>
                <div class="card-body p-3">
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <span class="avatar avatar-md bg-purple text-white rounded shadow-sm fs-2">📜</span>
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h3 class="card-title text-purple fw-bold mb-0 fs-3">
                                    Perfectionnement en cours : <?= htmlspecialchars($activeResearch['name'] ?? $activeResearch['research_name'] ?? 'Technologie féodale') ?>
                                </h3>
                                <span class="badge bg-purple text-white fw-bold">Palier <?= (int)($activeResearch['target_level'] ?? 1) ?></span>
                            </div>
                            <div class="progress progress-sm mb-2" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-purple"
                                     id="researchProgressBar"
                                     style="width: <?= (int)$activeResearch['progress_pct'] ?>%;"
                                     role="progressbar"
                                     aria-valuenow="<?= (int)$activeResearch['progress_pct'] ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     data-started="<?= (int)$activeResearch['started_at'] ?>"
                                     data-finishes="<?= (int)$activeResearch['finishes_at'] ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center text-secondary small flex-wrap gap-2">
                                <span>Progression : <strong id="researchProgressText"><?= (int)$activeResearch['progress_pct'] ?>%</strong></span>
                                <span class="d-inline-flex align-items-center gap-1 fw-bold text-dark font-monospace fs-4">
                                    ⏱️ <span class="queue-timer" data-countdown="<?= (int)$activeResearch['finishes_at'] ?>"><?= $activeResearch['remaining_formatted'] ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- FILTRES ET FORMULAIRE DE RECHERCHE TABLER  -->
        <!-- ========================================== -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body p-3">
                <form method="GET" action="/" class="row g-2 align-items-center" id="researchFilterForm">
                    <input type="hidden" name="page" value="research">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($filters['view']) ?>" id="filterViewInput">

                    <!-- Recherche textuelle -->
                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="input-icon">
                            <span class="input-icon-addon">🔍</span>
                            <input type="text" name="search" class="form-control" placeholder="Nom ou description..." value="<?= htmlspecialchars($filters['search']) ?>" aria-label="Recherche">
                        </div>
                    </div>

                    <!-- Filtre Catégorie -->
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($vm['categories'] as $catKey => $catData): ?>
                                <option value="<?= htmlspecialchars($catKey) ?>" <?= ($filters['category'] === $catKey) ? 'selected' : '' ?>>
                                    <?= $catData['icon'] ?> <?= htmlspecialchars($catData['label']) ?> (<?= $catData['count'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tri -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="default" <?= ($filters['sort'] === 'default') ? 'selected' : '' ?>>Ordre conseillé</option>
                            <option value="name_asc" <?= ($filters['sort'] === 'name_asc') ? 'selected' : '' ?>>Nom (A-Z)</option>
                            <option value="level_desc" <?= ($filters['sort'] === 'level_desc') ? 'selected' : '' ?>>Niveau décroissant</option>
                            <option value="cost_asc" <?= ($filters['sort'] === 'cost_asc') ? 'selected' : '' ?>>Coût croissant</option>
                            <option value="duration_asc" <?= ($filters['sort'] === 'duration_asc') ? 'selected' : '' ?>>Durée croissante</option>
                        </select>
                    </div>

                    <!-- Switch Finançables uniquement -->
                    <div class="col-6 col-md-2 col-lg-2">
                        <label class="form-check form-switch m-0 d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" name="affordable_only" value="1" <?= $filters['affordable_only'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="form-check-label small ms-2">Finançables</span>
                        </label>
                    </div>

                    <!-- Boutons d'Action & Bascule Vue Grille / Vue Tableau -->
                    <div class="col-6 col-md-auto ms-auto d-flex align-items-center gap-1 justify-content-end">
                        <button type="submit" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-1" title="Appliquer les filtres">
                            <span>🔍</span> <span class="d-none d-sm-inline">Filtrer</span>
                        </button>
                        <?php if ($filters['search'] !== '' || $filters['category'] !== 'all' || $filters['affordable_only'] || $filters['sort'] !== 'default'): ?>
                            <a href="?page=research&view=<?= urlencode($filters['view']) ?>" class="btn btn-white shadow-sm" title="Réinitialiser les filtres">
                                <span>✕</span>
                            </a>
                        <?php endif; ?>
                        <div class="btn-group ms-1" role="group">
                            <button type="button" class="btn btn-sm <?= ($filters['view'] === 'grid') ? 'btn-primary' : 'btn-white' ?>" onclick="setViewMode('grid')" title="Vue en grille">
                                🔲
                            </button>
                            <button type="button" class="btn btn-sm <?= ($filters['view'] === 'table') ? 'btn-primary' : 'btn-white' ?>" onclick="setViewMode('table')" title="Vue en tableau">
                                📄
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- RÉSULTATS DES TECHNOLOGIES                 -->
        <!-- ========================================== -->
        <?php if (empty($items)): ?>
            <!-- État vide après filtrage -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="empty py-5">
                        <div class="empty-icon fs-1 text-secondary">🔍</div>
                        <p class="empty-title fs-2">Aucun savoir trouvé</p>
                        <p class="empty-subtitle text-secondary">
                            Aucune technologie ne correspond à vos filtres de recherche actuels.
                        </p>
                        <div class="empty-action">
                            <a href="?page=research&view=<?= urlencode($filters['view']) ?>" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                                <span>🔄</span> Réinitialiser les filtres
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <?php if ($filters['view'] === 'grid'): ?>
                <!-- ========================================== -->
                <!-- AFFICHAGE EN GRILLE DE CARTES TABLER       -->
                <!-- ========================================== -->
                <div class="row row-cards mb-4">
                    <?php foreach ($items as $r): /** @var ResearchDTO $r */ ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card shadow-sm h-100 <?= !$r->canAfford ? 'opacity-90' : '' ?>">
                                <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <span class="fs-2"><?= $r->icon ?></span>
                                        <h3 class="card-title fs-4 fw-bold text-truncate mb-0" title="<?= htmlspecialchars($r->name) ?>">
                                            <?= htmlspecialchars($r->name) ?>
                                        </h3>
                                    </div>
                                    <span class="badge bg-primary-lt fw-bold flex-shrink-0">
                                        Niveau <?= $r->currentLevel ?>
                                    </span>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-secondary-lt text-secondary" style="font-size: 0.7rem;">
                                            <?= htmlspecialchars($r->categoryLabel) ?>
                                        </span>
                                    </div>
                                    <p class="text-secondary small mb-3 flex-grow-1" style="min-height: 48px;">
                                        <?= htmlspecialchars($r->description) ?>
                                    </p>

                                    <!-- Coûts requis pour le niveau suivant -->
                                    <div class="p-2 rounded bg-light-subtle border mb-3">
                                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                                            <span>Ressources requises (Niv <?= $r->nextLevel ?>)</span>
                                            <?php if ($r->canAfford): ?>
                                                <span class="badge bg-success-lt fw-bold">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-lt fw-bold">Insuffisant</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between gap-1 flex-wrap">
                                            <span class="badge <?= ($r->missingMetal > 0) ? 'bg-danger-lt text-danger' : 'bg-white text-dark border' ?> px-2 py-1" title="Bois requis : <?= number_format($r->costMetal) ?>">
                                                🪵 <?= number_format($r->costMetal) ?>
                                            </span>
                                            <span class="badge <?= ($r->missingCrystal > 0) ? 'bg-danger-lt text-danger' : 'bg-white text-dark border' ?> px-2 py-1" title="Pierre requise : <?= number_format($r->costCrystal) ?>">
                                                🪨 <?= number_format($r->costCrystal) ?>
                                            </span>
                                            <span class="badge <?= ($r->missingDeuterium > 0) ? 'bg-danger-lt text-danger' : 'bg-white text-dark border' ?> px-2 py-1" title="Riz requis : <?= number_format($r->costDeuterium) ?>">
                                                🌾 <?= number_format($r->costDeuterium) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Pied de carte : Durée (corrigée) & Bouton d'action -->
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                        <span class="text-secondary small d-inline-flex align-items-center gap-1 font-monospace" title="Durée de recherche : <?= $r->getHumanDuration() ?>">
                                            <span>⏱️</span> <?= $r->getFormattedDuration() ?>
                                        </span>
                                        <?php if ($activeResearch): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" disabled title="Une recherche est déjà en cours dans l'Académie">
                                                File occupée
                                            </button>
                                        <?php elseif (!$r->canAfford): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Ressources insuffisantes pour lancer ce palier">
                                                Manque vivres
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="startTech('<?= htmlspecialchars($r->code) ?>')">
                                                Développer (Niv <?= $r->nextLevel ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- ========================================== -->
                <!-- AFFICHAGE EN TABLEAU TABLER                -->
                <!-- ========================================== -->
                <div class="card shadow-sm mb-4">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr class="text-uppercase text-secondary fs-6">
                                    <th>Technologie Féodale</th>
                                    <th>Discipline</th>
                                    <th class="text-center">Niveau Actuel</th>
                                    <th>Coûts Requis (Niv Suivant)</th>
                                    <th class="text-center">Durée</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $r): /** @var ResearchDTO $r */ ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fs-2 flex-shrink-0"><?= $r->icon ?></span>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r->name) ?></div>
                                                    <div class="text-secondary small"><?= htmlspecialchars($r->description) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt text-secondary">
                                                <?= htmlspecialchars($r->categoryLabel) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-lt fw-bold fs-4">
                                                <?= $r->currentLevel ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <span class="badge <?= ($r->missingMetal > 0) ? 'bg-danger-lt text-danger' : 'bg-light text-dark' ?>">
                                                    🪵 <?= number_format($r->costMetal) ?>
                                                </span>
                                                <span class="badge <?= ($r->missingCrystal > 0) ? 'bg-danger-lt text-danger' : 'bg-light text-dark' ?>">
                                                    🪨 <?= number_format($r->costCrystal) ?>
                                                </span>
                                                <span class="badge <?= ($r->missingDeuterium > 0) ? 'bg-danger-lt text-danger' : 'bg-light text-dark' ?>">
                                                    🌾 <?= number_format($r->costDeuterium) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center font-monospace small">
                                            ⏱️ <?= $r->getFormattedDuration() ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($activeResearch): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                    File occupée
                                                </button>
                                            <?php elseif (!$r->canAfford): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" disabled>
                                                    Manque vivres
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="startTech('<?= htmlspecialchars($r->code) ?>')">
                                                    Développer (Niv <?= $r->nextLevel ?>)
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- PAGINATION TABLER                          -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-4">
                <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <p class="m-0 text-secondary small">
                        Affichage de <strong><?= $pagination['start_item'] ?></strong> à <strong><?= $pagination['end_item'] ?></strong> sur <strong><?= $pagination['total_items'] ?></strong> traités féodaux
                    </p>
                    <?php renderResearchPagination($pagination['current_page'], $pagination['total_pages'], $filters); ?>
                </div>
            </div>

        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
/**
 * Lancement d'une recherche technologique via l'API
 */
async function startTech(code) {
    const formData = new FormData();
    formData.append('research_code', code);

    try {
        const res = await fetch('/api/research.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible d\'initier la recherche.', 'warning');
            } else {
                alert(data.error || 'Impossible d\'initier la recherche.');
            }
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Erreur de communication avec l\'académie des savoirs.', 'danger');
        } else {
            alert('Erreur de communication avec l\'académie des savoirs.');
        }
    }
}

/**
 * Basculement du mode d'affichage Grille / Tableau
 */
function setViewMode(mode) {
    const input = document.getElementById('filterViewInput');
    if (input) {
        input.value = mode;
        document.getElementById('researchFilterForm').submit();
    }
}

/**
 * Mise à jour en temps réel des décomptes et de la barre de progression
 */
document.addEventListener('DOMContentLoaded', function () {
    const timerEls = document.querySelectorAll('.queue-timer');
    const progressBar = document.getElementById('researchProgressBar');
    const progressText = document.getElementById('researchProgressText');

    if (timerEls.length === 0 && !progressBar) return;

    // Calcul du décalage éventuel entre l'horloge du serveur et l'horloge du navigateur client
    const serverTimestamp = <?= time() ?>;
    const clientTimestamp = Math.floor(Date.now() / 1000);
    const clockDelta = serverTimestamp - clientTimestamp;

    function refreshActiveQueue() {
        const now = Math.floor(Date.now() / 1000) + clockDelta;

        // Décompte textuel
        timerEls.forEach(function (el) {
            const finishTs = parseInt(el.getAttribute('data-countdown') || '0', 10);
            const remaining = Math.max(0, finishTs - now);

            if (remaining <= 0) {
                el.textContent = 'Terminé !';
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', '100');
                }
                if (progressText) {
                    progressText.textContent = '100%';
                }
                setTimeout(function () { window.location.reload(); }, 1200);
                return;
            }

            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            const pad = (n) => n < 10 ? '0' + n : n;
            el.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
        });

        // Barre de progression dynamique
        if (progressBar) {
            const finishTs = parseInt(progressBar.getAttribute('data-finishes') || '0', 10);
            const startTs = parseInt(progressBar.getAttribute('data-started') || '0', 10);
            const total = Math.max(1, finishTs - startTs);
            const elapsed = Math.max(0, now - startTs);
            const pct = Math.min(100, Math.max(0, Math.floor((elapsed / total) * 100)));

            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', pct);
            if (progressText) {
                progressText.textContent = pct + '%';
            }
        }
    }

    refreshActiveQueue();
    setInterval(refreshActiveQueue, 1000);
});
</script>
