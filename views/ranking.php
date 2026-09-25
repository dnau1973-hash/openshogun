<?php
/**
 * Vue du Classement Féodal, Alliances & Tableau d'Honneur (Style Travian - Full Tabler.io)
 * Navigation d'onglets robuste, pagination complète et colonnes à largeur fixe
 */
require_once __DIR__ . '/../core/HonorEngine.php';
require_once __DIR__ . '/../core/AllianceEngine.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/game_constants.php';

$db = Database::getConnection();
$honorEngine = new HonorEngine();
$allianceEngine = new AllianceEngine();

// Onglet actif
$tab = $_GET['tab'] ?? 'general';
if (!in_array($tab, ['general', 'alliances', 'honor'])) {
    $tab = 'general';
}

// -------------------------------------------------------------
// 1. CLASSEMENT GÉNÉRAL DES DAIMYŌS (AVEC PAGINATION)
// -------------------------------------------------------------
$perPage = 25;
$totalPlayers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_bot = 0")->fetchColumn();
$totalPages = max(1, (int)ceil($totalPlayers / $perPage));
$pageNum = min(max(1, (int)($_GET['p'] ?? 1)), $totalPages);
$offset = ($pageNum - 1) * $perPage;

$stmtPlayers = $db->prepare("
    SELECT u.id, u.username, u.faction, u.points, u.created_at, u.protection_until, u.is_bot,
           COUNT(p.id) as planet_count, a.name as alliance_name, a.tag as alliance_tag
    FROM users u 
    LEFT JOIN planets p ON p.user_id = u.id 
    LEFT JOIN alliances a ON u.alliance_id = a.id 
    WHERE u.is_bot = 0
    GROUP BY u.id 
    ORDER BY u.points DESC, u.id ASC 
    LIMIT :limit OFFSET :offset
");
$stmtPlayers->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtPlayers->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtPlayers->execute();
$players = $stmtPlayers->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// 2. CLASSEMENT DES ALLIANCES FÉODALES (AVEC PAGINATION)
// -------------------------------------------------------------
$allyPerPage = 20;
$totalAlliances = $allianceEngine->getTotalAlliancesCount();
$totalAllyPages = max(1, (int)ceil($totalAlliances / $allyPerPage));
$allyPageNum = min(max(1, (int)($_GET['p_ally'] ?? 1)), $totalAllyPages);
$allyOffset = ($allyPageNum - 1) * $allyPerPage;

$alliancesRanking = $allianceEngine->getAlliancesRanking($allyPerPage, $allyOffset);

// -------------------------------------------------------------
// 3. TABLEAU D'HONNEUR HEBDOMADAIRE (TOP 10 TRAVIAN-STYLE)
// -------------------------------------------------------------
$honorRoll = $honorEngine->getFullHonorRoll(10);
$currentWeek = date('W');
$currentYear = date('Y');

/**
 * Helper de pagination Tabler.io
 */
function renderTablerPagination(int $currentPage, int $totalPages, string $activeTab, string $paramName = 'p'): void {
    if ($totalPages <= 1) return;

    echo '<ul class="pagination pagination-sm m-0 ms-auto">';
    
    // Bouton Précédent
    $prevDisabled = ($currentPage <= 1);
    $prevUrl = '?page=ranking&tab=' . urlencode($activeTab) . '&' . urlencode($paramName) . '=' . max(1, $currentPage - 1);
    echo '<li class="page-item ' . ($prevDisabled ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="' . ($prevDisabled ? 'javascript:void(0)' : htmlspecialchars($prevUrl)) . '" tabindex="' . ($prevDisabled ? '-1' : '0') . '" aria-disabled="' . ($prevDisabled ? 'true' : 'false') . '">';
    echo '&lsaquo; Précédent';
    echo '</a>';
    echo '</li>';

    // Plage intelligente de numéros de pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $urlFirst = '?page=ranking&tab=' . urlencode($activeTab) . '&' . urlencode($paramName) . '=1';
        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($urlFirst) . '">1</a></li>';
        if ($start > 2) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $isActive = ($i === $currentPage);
        $urlPage = '?page=ranking&tab=' . urlencode($activeTab) . '&' . urlencode($paramName) . '=' . $i;
        echo '<li class="page-item ' . ($isActive ? 'active' : '') . '">';
        echo '<a class="page-link" href="' . htmlspecialchars($urlPage) . '">' . $i . '</a>';
        echo '</li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $urlLast = '?page=ranking&tab=' . urlencode($activeTab) . '&' . urlencode($paramName) . '=' . $totalPages;
        echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($urlLast) . '">' . $totalPages . '</a></li>';
    }

    // Bouton Suivant
    $nextDisabled = ($currentPage >= $totalPages);
    $nextUrl = '?page=ranking&tab=' . urlencode($activeTab) . '&' . urlencode($paramName) . '=' . min($totalPages, $currentPage + 1);
    echo '<li class="page-item ' . ($nextDisabled ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="' . ($nextDisabled ? 'javascript:void(0)' : htmlspecialchars($nextUrl)) . '" tabindex="' . ($nextDisabled ? '-1' : '0') . '" aria-disabled="' . ($nextDisabled ? 'true' : 'false') . '">';
    echo 'Suivant &rsaquo;';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
}

/**
 * Helper de rendu d'une colonne du Tableau d'Honneur
 */
function renderTablerHonorColumn(array $list, string $unitLabel): void {
    if (empty($list)) {
        echo '<div class="card-body text-center text-secondary py-4 small">Aucune donnée pour cette semaine.</div>';
        return;
    }
    echo '<div class="table-responsive">';
    echo '<table class="table table-vcenter card-table table-hover table-sm" style="table-layout: fixed; width: 100%;">';
    echo '<colgroup>';
    echo '  <col style="width: 65px;">';
    echo '  <col>';
    echo '  <col style="width: 105px;">';
    echo '</colgroup>';
    $pos = 1;
    foreach ($list as $row) {
        $badgeClass = match ($pos) {
            1 => 'bg-warning text-dark fw-bold',
            2 => 'bg-secondary text-white fw-bold',
            3 => 'bg-amber text-white fw-bold',
            default => 'bg-secondary-lt text-secondary'
        };
        $medalLabel = match ($pos) {
            1 => '🥇 1',
            2 => '🥈 2',
            3 => '🥉 3',
            default => '#' . $pos
        };
        $fInfo = FACTIONS[$row['faction']] ?? FACTIONS['terran'];
        echo '<tr style="cursor: pointer;" onclick="openPlayerProfileModal(' . (int)$row['user_id'] . ')" title="Consulter la fiche du Daimyō : ' . htmlspecialchars($row['username']) . '">';
        echo '<td class="text-center py-2 pe-1"><span class="badge ' . $badgeClass . ' py-1 px-2">' . $medalLabel . '</span></td>';
        echo '<td class="py-2 ps-3 text-truncate">';
        echo '  <div class="d-inline-flex align-items-center gap-2 text-truncate">';
        echo '    <span class="fs-4 flex-shrink-0">' . $fInfo['icon'] . '</span>';
        echo '    <span class="fw-semibold text-truncate text-reset">' . htmlspecialchars($row['username']) . '</span>';
        echo '  </div>';
        echo '</td>';
        echo '<td class="text-end py-2 text-nowrap">';
        echo '  <span class="badge bg-warning-lt text-warning fw-bold font-monospace fs-4">+' . number_format($row['score']) . '</span>';
        echo '</td>';
        echo '</tr>';
        $pos++;
    }
    echo '</table>';
    echo '</div>';
}
?>

<style>
/* Style des onglets Tabler.io épuré et sans chevauchement */
.ranking-navbar-card {
    border-radius: 8px;
    background: #ffffff;
}
.ranking-tab-btn {
    color: #475569;
    font-weight: 600;
    padding: 0.55rem 1.15rem;
    border-radius: 6px;
    transition: all 0.15s ease-in-out;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    line-height: 1.25;
}
.ranking-tab-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.ranking-tab-btn.active {
    background-color: #0054a6 !important;
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 84, 166, 0.25);
}
.ranking-tab-btn.active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}
.ranking-tab-pane {
    display: none;
}
.ranking-tab-pane.active {
    display: block !important;
}
</style>

<!-- 🏆 EN-TÊTE DE PAGE TABLER -->
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle text-secondary">Honneur &amp; Renommée de l'Archipel</div>
                <h2 class="page-title d-flex align-items-center gap-2">
                    <span class="text-warning">🏆</span> Palmarès &amp; Gloire du Japon
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?page=alliance" class="btn btn-outline-danger d-none d-sm-inline-flex align-items-center gap-1">
                        <span>🎌</span> Pavillon des Alliances
                    </a>
                    <button type="button" onclick="window.location.reload()" class="btn btn-white d-inline-flex align-items-center gap-1 shadow-sm" title="Actualiser le classement">
                        <span>🔄</span> Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xl">
    <!-- 🗂️ BARRE DE NAVIGATION TABLER PAR ONGLETS SÉPARÉS -->
    <div class="card mb-3 bg-white border shadow-sm ranking-navbar-card">
        <div class="card-header border-bottom p-2 bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills flex-wrap gap-1 align-items-center m-0 p-0 border-0" id="rankingTabsNav" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="?page=ranking&tab=general" 
                       id="tab-btn-general"
                       class="nav-link ranking-tab-btn <?= ($tab === 'general') ? 'active' : '' ?>" 
                       data-tab="general"
                       onclick="switchRankingTab('general'); return false;"
                       role="tab" 
                       aria-selected="<?= ($tab === 'general') ? 'true' : 'false' ?>">
                        <span class="me-1">🏆</span> Classement Général
                        <span class="badge bg-primary-lt ms-2"><?= number_format($totalPlayers) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="?page=ranking&tab=alliances" 
                       id="tab-btn-alliances"
                       class="nav-link ranking-tab-btn <?= ($tab === 'alliances') ? 'active' : '' ?>" 
                       data-tab="alliances"
                       onclick="switchRankingTab('alliances'); return false;"
                       role="tab" 
                       aria-selected="<?= ($tab === 'alliances') ? 'true' : 'false' ?>">
                        <span class="me-1">🎌</span> Alliances Féodales
                        <span class="badge bg-danger-lt ms-2"><?= number_format($totalAlliances) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="?page=ranking&tab=honor" 
                       id="tab-btn-honor"
                       class="nav-link ranking-tab-btn <?= ($tab === 'honor') ? 'active' : '' ?>" 
                       data-tab="honor"
                       onclick="switchRankingTab('honor'); return false;"
                       role="tab" 
                       aria-selected="<?= ($tab === 'honor') ? 'true' : 'false' ?>">
                        <span class="me-1">🎖️</span> Tableau d'Honneur
                        <span class="badge bg-warning-lt ms-2">Semaine <?= $currentWeek ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 1 : CLASSEMENT GÉNÉRAL DES JOUEURS  -->
    <!-- ========================================== -->
    <div class="ranking-tab-pane <?= ($tab === 'general') ? 'active show' : '' ?>" 
         id="tab-general" 
         role="tabpanel" 
         style="display: <?= ($tab === 'general') ? 'block' : 'none' ?>;">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light-subtle flex-wrap gap-2">
                <div class="text-secondary small">
                    <span>Daimyōs les plus puissants du Japon • Page <?= $pageNum ?> sur <?= $totalPages ?> (25 par page)</span>
                </div>
                <div class="text-secondary small d-none d-md-flex align-items-center gap-2">
                    <span class="badge bg-success-lt d-inline-flex align-items-center gap-1">
                        <span class="badge-dot bg-success"></span> Temps réel
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover" style="table-layout: fixed; width: 100%;">
                    <colgroup>
                        <col style="width: 80px;">   <!-- Rang -->
                        <col style="width: 250px;">  <!-- Daimyō -->
                        <col style="width: 140px;">  <!-- Clan -->
                        <col style="width: 170px;">  <!-- Alliance -->
                        <col style="width: 90px;">   <!-- Fiefs -->
                        <col style="width: 160px;">  <!-- Puissance -->
                        <col style="width: 130px;">  <!-- Actions -->
                    </colgroup>
                    <thead>
                        <tr class="text-uppercase text-secondary fs-6">
                            <th class="text-center" style="width: 80px;">Rang</th>
                            <th style="width: 250px;">Daimyō</th>
                            <th style="width: 140px;">Clan</th>
                            <th style="width: 170px;">Alliance</th>
                            <th class="text-center" style="width: 90px;">Fiefs</th>
                            <th class="text-end" style="width: 160px;">Puissance Féodale</th>
                            <th class="text-center" style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($players)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    Aucun daimyō trouvé pour cette page.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = $offset + 1; foreach ($players as $p): ?>
                                <?php 
                                    $curRank = $rank++;
                                    $isCurrent = ($p['id'] == $user['id']); 
                                    $fInfo = FACTIONS[$p['faction']] ?? FACTIONS['terran'];
                                ?>
                                <tr class="<?= $isCurrent ? 'table-warning' : '' ?>" style="cursor: pointer;" onclick="if (!event.target.closest('button, a')) openPlayerProfileModal(<?= (int)$p['id'] ?>)" title="Consulter la fiche du Daimyō : <?= htmlspecialchars($p['username']) ?>">
                                    <td class="text-center">
                                        <?php if ($curRank === 1): ?>
                                            <span class="badge bg-warning text-dark fw-bold fs-4 px-2 py-1 shadow-sm">🥇 #1</span>
                                        <?php elseif ($curRank === 2): ?>
                                            <span class="badge bg-secondary text-white fw-bold fs-4 px-2 py-1 shadow-sm">🥈 #2</span>
                                        <?php elseif ($curRank === 3): ?>
                                            <span class="badge bg-amber text-white fw-bold fs-4 px-2 py-1 shadow-sm">🥉 #3</span>
                                        <?php else: ?>
                                            <span class="text-secondary fw-bold fs-4">#<?= $curRank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-truncate">
                                        <div class="d-flex align-items-center gap-2 text-truncate">
                                            <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= (int)$p['id'] ?>)" 
                                               class="text-reset fw-bold text-decoration-none d-inline-flex align-items-center gap-1 text-truncate" 
                                               title="Consulter la fiche du Daimyō : <?= htmlspecialchars($p['username']) ?>">
                                                <span class="flex-shrink-0">👤</span>
                                                <span class="text-truncate"><?= htmlspecialchars($p['username']) ?></span>
                                            </a>
                                            <?php if (Auth::isUserProtected($p)): ?>
                                                <span class="badge bg-success-lt flex-shrink-0" title="Immunité Féodale des Nouveaux Joueurs Active">🔰 Trêve</span>
                                            <?php endif; ?>
                                            <?php if (!empty($p['is_bot'])): ?>
                                                <span class="badge bg-secondary-lt flex-shrink-0" title="Daimyō IA Autonome">🤖 IA</span>
                                            <?php endif; ?>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-red text-white fw-bold flex-shrink-0">Vous</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-truncate">
                                        <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1 text-truncate" title="<?= htmlspecialchars($fInfo['name']) ?>">
                                            <span class="flex-shrink-0"><?= $fInfo['icon'] ?></span>
                                            <span class="text-truncate"><?= htmlspecialchars($fInfo['name']) ?></span>
                                        </span>
                                    </td>
                                    <td class="text-truncate">
                                        <?php if (!empty($p['alliance_tag'])): ?>
                                            <a href="?page=alliance" class="badge bg-danger-lt text-danger text-decoration-none fw-bold text-truncate d-inline-block mw-100" title="Ligue : <?= htmlspecialchars($p['alliance_name'] ?? '') ?>">
                                                [<?= htmlspecialchars($p['alliance_tag']) ?>] <?= htmlspecialchars($p['alliance_name'] ?? '') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-secondary small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-azure-lt fw-bold fs-4"><?= number_format($p['planet_count']) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <span class="text-danger fw-bold fs-3"><?= number_format($p['points']) ?></span>
                                        <span class="text-secondary small ms-1">pts</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-list flex-nowrap justify-content-center">
                                            <button type="button" onclick="openPlayerProfileModal(<?= (int)$p['id'] ?>)" 
                                                    class="btn btn-sm btn-white d-inline-flex align-items-center gap-1 shadow-sm px-2" 
                                                    title="Fiche du Daimyō">
                                                <span>👤</span> <span class="d-none d-md-inline">Fiche</span>
                                            </button>
                                            <?php if (!$isCurrent): ?>
                                                <a href="?page=messages&tab=compose&to=<?= urlencode($p['username']) ?>" 
                                                   class="btn btn-sm btn-white d-inline-flex align-items-center gap-1 shadow-sm px-2" 
                                                   title="Envoyer une missive">
                                                    <span>✉️</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination Tabler Daimyōs -->
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="m-0 text-secondary small">
                    Affichage de <strong><?= ($totalPlayers > 0) ? ($offset + 1) : 0 ?></strong> à <strong><?= min($offset + count($players), $totalPlayers) ?></strong> sur <strong><?= number_format($totalPlayers) ?></strong> daimyōs
                </p>
                <?php renderTablerPagination($pageNum, $totalPages, 'general', 'p'); ?>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 2 : CLASSEMENT DES ALLIANCES        -->
    <!-- ========================================== -->
    <div class="ranking-tab-pane <?= ($tab === 'alliances') ? 'active show' : '' ?>" 
         id="tab-alliances" 
         role="tabpanel" 
         style="display: <?= ($tab === 'alliances') ? 'block' : 'none' ?>;">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light-subtle flex-wrap gap-2">
                <div class="text-secondary small">
                    <span>Grand Livre des Alliances &amp; Ligues Féodales • Page <?= $allyPageNum ?> sur <?= $totalAllyPages ?> (20 par page)</span>
                </div>
                <div>
                    <a href="/?page=alliance" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 shadow-sm">
                        <span>🏛️</span> Pavillon Diplomatique
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover" style="table-layout: fixed; width: 100%;">
                    <colgroup>
                        <col style="width: 80px;">   <!-- Rang -->
                        <col style="width: 260px;">  <!-- Alliance -->
                        <col style="width: 180px;">  <!-- Chef Suprême -->
                        <col style="width: 150px;">  <!-- Membres / Capacité -->
                        <col style="width: 95px;">   <!-- Fiefs -->
                        <col style="width: 140px;">  <!-- Moyenne / Daimyō -->
                        <col style="width: 160px;">  <!-- Puissance Globale -->
                    </colgroup>
                    <thead>
                        <tr class="text-uppercase text-secondary fs-6">
                            <th class="text-center" style="width: 80px;">Rang</th>
                            <th style="width: 260px;">Alliance</th>
                            <th style="width: 180px;">Chef Suprême</th>
                            <th class="text-center" style="width: 150px;">Membres / Capacité</th>
                            <th class="text-center" style="width: 95px;">Fiefs</th>
                            <th class="text-end" style="width: 140px;">Moyenne / Daimyō</th>
                            <th class="text-end" style="width: 160px;">Puissance Globale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alliancesRanking)): ?>
                            <tr>
                                <td colspan="7" class="p-0">
                                    <div class="empty py-5">
                                        <div class="empty-icon fs-1 text-secondary">🎌</div>
                                        <p class="empty-title">Aucune alliance n'a encore été proclamée</p>
                                        <p class="empty-subtitle text-secondary">Rendez-vous au Pavillon Diplomatique pour fonder la première ligue souveraine du Japon !</p>
                                        <div class="empty-action">
                                            <a href="/?page=alliance" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                                <span>🏛️</span> Fonder une Alliance
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $aRank = $allyOffset + 1; foreach ($alliancesRanking as $a): ?>
                                <?php
                                $curAllyRank = $aRank++;
                                $isMyAlly = (!empty($user['alliance_id']) && (int)$user['alliance_id'] === (int)$a['id']);
                                ?>
                                <tr class="<?= $isMyAlly ? 'table-warning' : '' ?>">
                                    <td class="text-center">
                                        <?php if ($curAllyRank === 1): ?>
                                            <span class="badge bg-warning text-dark fw-bold fs-4 px-2 py-1 shadow-sm">🥇 #1</span>
                                        <?php elseif ($curAllyRank === 2): ?>
                                            <span class="badge bg-secondary text-white fw-bold fs-4 px-2 py-1 shadow-sm">🥈 #2</span>
                                        <?php elseif ($curAllyRank === 3): ?>
                                            <span class="badge bg-amber text-white fw-bold fs-4 px-2 py-1 shadow-sm">🥉 #3</span>
                                        <?php else: ?>
                                            <span class="text-secondary fw-bold fs-4">#<?= $curAllyRank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-truncate">
                                        <div class="d-flex align-items-center gap-1 text-truncate">
                                            <span class="badge bg-danger text-white fw-bold me-1 flex-shrink-0">[<?= htmlspecialchars($a['tag']) ?>]</span>
                                            <a href="?page=alliance" class="text-reset fw-bold text-decoration-none text-truncate" title="<?= htmlspecialchars($a['name']) ?>">
                                                <?= htmlspecialchars($a['name']) ?>
                                            </a>
                                            <?php if ($isMyAlly): ?>
                                                <span class="badge bg-red text-white fw-bold ms-2 flex-shrink-0">Votre Clan</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-truncate">
                                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= (int)$a['leader_id'] ?>)" 
                                           class="text-reset text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-truncate"
                                           title="Chef : <?= htmlspecialchars($a['leader_name']) ?>">
                                            <span class="flex-shrink-0">👑</span>
                                            <span class="text-truncate"><?= htmlspecialchars($a['leader_name']) ?></span>
                                        </a>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge <?= ($a['member_count'] >= $a['capacity']) ? 'bg-orange-lt text-orange' : 'bg-primary-lt' ?> fw-bold">
                                            <?= $a['member_count'] ?> / <?= $a['capacity'] ?>
                                        </span>
                                        <?php if ($a['member_count'] >= $a['capacity']): ?>
                                            <span class="badge bg-secondary-lt ms-1">Plein</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-azure-lt fw-bold fs-4"><?= number_format($a['total_planets']) ?></span>
                                    </td>
                                    <td class="text-end text-secondary fw-semibold text-nowrap">
                                        <?= number_format($a['avg_points']) ?> pts
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <span class="text-danger fw-bold fs-3"><?= number_format($a['total_points']) ?></span>
                                        <span class="text-secondary small ms-1">pts</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Tabler Alliances -->
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="m-0 text-secondary small">
                    Affichage de <strong><?= ($totalAlliances > 0) ? ($allyOffset + 1) : 0 ?></strong> à <strong><?= min($allyOffset + count($alliancesRanking), $totalAlliances) ?></strong> sur <strong><?= number_format($totalAlliances) ?></strong> alliances
                </p>
                <?php renderTablerPagination($allyPageNum, $totalAllyPages, 'alliances', 'p_ally'); ?>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ONGLET 3 : TABLEAU D'HONNEUR HEBDOMADAIRE  -->
    <!-- ========================================== -->
    <div class="ranking-tab-pane <?= ($tab === 'honor') ? 'active show' : '' ?>" 
         id="tab-honor" 
         role="tabpanel" 
         style="display: <?= ($tab === 'honor') ? 'block' : 'none' ?>;">
        
        <!-- Bannière d'Honneur Shogunal -->
        <div class="card bg-warning-lt border-warning-subtle shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <h3 class="card-title text-warning fw-bold d-flex align-items-center gap-2 mb-1 fs-2">
                            <span>🎖️</span> Tableau d'Honneur Féodal — Semaine <?= $currentWeek ?> / <?= $currentYear ?>
                        </h3>
                        <p class="text-secondary mb-0">
                            Les 10 plus illustres daimyōs récompensés chaque semaine par décret impérial du Shogunat.<br>
                            Médailles &amp; Dotations : <strong>🥇 Or (1er) +100 Koban</strong>, <strong>🥈 Argent (2ème) +50 Koban</strong>, <strong>🥉 Bronze (3ème) +25 Koban</strong> et <strong>🎖️ Rubans Top 10</strong>.
                            <span class="d-block mt-1 text-muted small"><span class="badge bg-secondary-lt">ℹ️ Règle impériale</span> Seuls les commandants humains sont classés et peuvent recevoir des décorations impériales (les daimyōs IA en sont exclus).</span>
                        </p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 fs-4 shadow-sm">
                            🏆 DÉCRET DU SHOGUNAT
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Colonnes du Top 10 Travian-Style en cartes Tabler -->
        <div class="row row-cards mb-4">
            <!-- 1. Meilleure Progression -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100">
                    <div class="card-status-top bg-danger"></div>
                    <div class="card-header border-bottom py-2">
                        <div>
                            <h4 class="card-title text-danger fw-bold mb-0 d-flex align-items-center gap-1">
                                <span>📈</span> Top Progression
                            </h4>
                            <div class="text-secondary small">Puissance acquise cette semaine</div>
                        </div>
                    </div>
                    <?php renderTablerHonorColumn($honorRoll['progression'], 'points'); ?>
                </div>
            </div>

            <!-- 2. Meilleurs Attaquants -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100">
                    <div class="card-status-top bg-red"></div>
                    <div class="card-header border-bottom py-2">
                        <div>
                            <h4 class="card-title text-red fw-bold mb-0 d-flex align-items-center gap-1">
                                <span>⚔️</span> Top Conquérants
                            </h4>
                            <div class="text-secondary small">Sièges victorieux &amp; garnisons vaincues</div>
                        </div>
                    </div>
                    <?php renderTablerHonorColumn($honorRoll['attack'], 'points'); ?>
                </div>
            </div>

            <!-- 3. Meilleurs Défenseurs -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100">
                    <div class="card-status-top bg-success"></div>
                    <div class="card-header border-bottom py-2">
                        <div>
                            <h4 class="card-title text-success fw-bold mb-0 d-flex align-items-center gap-1">
                                <span>🛡️</span> Top Défenseurs
                            </h4>
                            <div class="text-secondary small">Assauts ennemis repoussés</div>
                        </div>
                    </div>
                    <?php renderTablerHonorColumn($honorRoll['defense'], 'points'); ?>
                </div>
            </div>

            <!-- 4. Meilleurs Pillards -->
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100">
                    <div class="card-status-top bg-purple"></div>
                    <div class="card-header border-bottom py-2">
                        <div>
                            <h4 class="card-title text-purple fw-bold mb-0 d-flex align-items-center gap-1">
                                <span>🌾</span> Top Pillards de Riz
                            </h4>
                            <div class="text-secondary small">Récoltes et vivres saisis en raid</div>
                        </div>
                    </div>
                    <?php renderTablerHonorColumn($honorRoll['raid'], 'ressources'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Commutation autonome et robuste des onglets du classement
 */
function switchRankingTab(tabKey) {
    const validTabs = ['general', 'alliances', 'honor'];
    if (!validTabs.includes(tabKey)) tabKey = 'general';

    // 1. Basculer l'affichage de chaque panneau
    validTabs.forEach(function (key) {
        const pane = document.getElementById('tab-' + key);
        const btn = document.getElementById('tab-btn-' + key);
        const isSelected = (key === tabKey);

        if (pane) {
            if (isSelected) {
                pane.classList.add('active', 'show');
                pane.style.setProperty('display', 'block', 'important');
            } else {
                pane.classList.remove('active', 'show');
                pane.style.setProperty('display', 'none', 'important');
            }
        }

        if (btn) {
            btn.classList.toggle('active', isSelected);
            btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        }
    });

    // 2. Synchroniser le paramètre d'URL sans rechargement de page
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState(null, '', url.toString());
    } catch (e) {}
}

// Initialisation dès le chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const hash = window.location.hash.replace('#tab-', '').replace('#', '');
    const activeTab = urlParams.get('tab') || hash || '<?= $tab ?>';
    if (['general', 'alliances', 'honor'].includes(activeTab)) {
        switchRankingTab(activeTab);
    }
});
</script>
