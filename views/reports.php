<?php
/**
 * Vue des Rapports de Combat et d'Espionnage (OpenShogun)
 * 100% Conforme au Design System Tabler.io (Thème clair, cartes modulaires, recherche & filtres réactifs)
 */

$db = Database::getConnection();

// Récupération des rapports de l'utilisateur (combats et espionnages)
$stmt = $db->prepare("
    SELECT cr.*, u1.username as att_user, u2.username as def_user 
    FROM combat_reports cr 
    LEFT JOIN users u1 ON cr.attacker_id = u1.id 
    LEFT JOIN users u2 ON cr.defender_id = u2.id 
    WHERE cr.attacker_id = ? OR cr.defender_id = ? 
    ORDER BY cr.created_at DESC 
    LIMIT 50
");
$stmt->execute([$user['id'], $user['id']]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dictionnaire des unités et engins pour affichage clair (icônes + noms)
$unitsDict = [];
try {
    $stmtU = $db->query("SELECT code, name, icon FROM units");
    while ($row = $stmtU->fetch(PDO::FETCH_ASSOC)) {
        $unitsDict[$row['code']] = $row;
    }
    $stmtS = $db->query("SELECT code, name, icon FROM ships");
    while ($row = $stmtS->fetch(PDO::FETCH_ASSOC)) {
        $unitsDict[$row['code']] = $row;
    }
} catch (Exception $e) {}

// Faune sauvage des oasis
$beastsDict = [
    'loup_honshu' => ['name' => 'Loup de Honshū', 'icon' => '🐺'],
    'ours_hokkaido' => ['name' => 'Ours d\'Hokkaidō', 'icon' => '🐻'],
    'sanglier_sauvage' => ['name' => 'Sanglier Sauvage', 'icon' => '🐗'],
];
$unitsDict = array_merge($unitsDict, $beastsDict);

// Calcul des statistiques pour les filtres et KPIs
$totalReports = count($reports);
$winCount = 0;
$lossCount = 0;
$spyCount = 0;

foreach ($reports as $rep) {
    $isAtt = ($rep['attacker_id'] == $user['id']);
    $isDef = ($rep['defender_id'] == $user['id']);
    $mType = $rep['mission_type'];

    if ($mType === 'spy') {
        $spyCount++;
    } else {
        $isWinner = ($rep['winner'] === 'attacker' && $isAtt) || ($rep['winner'] === 'defender' && $isDef);
        if ($isWinner) {
            $winCount++;
        } else {
            $lossCount++;
        }
    }
}

// Rapport sélectionné
$selectedReportId = isset($_GET['id']) ? (int)$_GET['id'] : ($reports[0]['id'] ?? 0);
$currentReport = null;
foreach ($reports as $r) {
    if ((int)$r['id'] === $selectedReportId) {
        $currentReport = $r;
        break;
    }
}

$repData = $currentReport ? json_decode($currentReport['report_data'], true) : null;

// Détermination de l'issue pour le joueur connecté
$isCurAtt = $currentReport && ($currentReport['attacker_id'] == $user['id']);
$isCurDef = $currentReport && ($currentReport['defender_id'] == $user['id']);
$isCurSpy = $currentReport && ($currentReport['mission_type'] === 'spy');
$isCurWin = $currentReport && (($currentReport['winner'] === 'attacker' && $isCurAtt) || ($currentReport['winner'] === 'defender' && $isCurDef));

// Détermination de la cible adverse pour contact direct
$targetDaimyo = '';
if ($currentReport && $repData) {
    $targetDaimyo = $isCurAtt ? ($repData['defender_name'] ?? $currentReport['def_user'] ?? '') : ($repData['attacker_name'] ?? $currentReport['att_user'] ?? '');
}
?>

<div class="container-xl my-3">

    <!-- 1. EN-TÊTE DE PAGE TABLER (BREADCRUMB & TITRE) -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-arrows mb-1">
                        <li class="breadcrumb-item"><a href="/" class="text-secondary">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><strong class="text-dark">Chroniques Militaires</strong></li>
                    </ol>
                </nav>
                <h2 class="page-title d-flex align-items-center gap-2 text-dark">
                    <span>📜</span>
                    <span>Chroniques de Siège &amp; Rapports de Bataille</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Archives impériales du Shōgunat : analyse détaillée de vos assauts, raids de pillage et infiltrations de ninjas shinobi.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-lt p-2">
                        📊 <?= $totalReports ?> Chronique<?= $totalReports > 1 ? 's' : '' ?>
                    </span>
                    <span class="badge bg-success-lt p-2">
                        🏆 <?= $winCount ?> Victoire<?= $winCount > 1 ? 's' : '' ?>
                    </span>
                    <span class="badge bg-danger-lt p-2">
                        💥 <?= $lossCount ?> Défaite<?= $lossCount > 1 ? 's' : '' ?>
                    </span>
                    <span class="badge bg-info-lt p-2">
                        🥷 <?= $spyCount ?> Infiltration<?= $spyCount > 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. ARCHITECTURE MODULAIRE 2 COLONNES TABLER -->
    <div class="row g-3">

        <!-- COLONNE GAUCHE : REGISTRE & LISTE DES RAPPORTS -->
        <div class="col-lg-5 col-xl-4">
            <div class="card bg-white border shadow-sm h-100 d-flex flex-column">
                
                <!-- En-tête de carte avec titre et recherche -->
                <div class="card-header border-bottom p-3 bg-white">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-title text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                <span>🗂️</span> Registre des Rapports
                            </h3>
                            <span class="badge bg-light text-secondary border font-monospace" id="reportsVisibleCounter">
                                <?= $totalReports ?>
                            </span>
                        </div>

                        <!-- Filtres par type de rapport (Nav Pills) -->
                        <div class="nav nav-pills gap-1 mb-2" id="reportFilterPills">
                            <button type="button" class="nav-link btn btn-sm py-1 px-2 border active" data-filter="all" onclick="filterReportsList('all', this)">
                                Tous
                            </button>
                            <button type="button" class="nav-link btn btn-sm py-1 px-2 border" data-filter="win" onclick="filterReportsList('win', this)">
                                🏆 Victoires
                            </button>
                            <button type="button" class="nav-link btn btn-sm py-1 px-2 border" data-filter="loss" onclick="filterReportsList('loss', this)">
                                💥 Défaites
                            </button>
                            <button type="button" class="nav-link btn btn-sm py-1 px-2 border" data-filter="spy" onclick="filterReportsList('spy', this)">
                                🥷 Shinobi
                            </button>
                        </div>

                        <!-- Champ de recherche en temps réel -->
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                            </span>
                            <input type="text" 
                                   id="reportSearchInput" 
                                   class="form-control form-control-sm" 
                                   placeholder="Rechercher par daimyō ou lieu..."
                                   autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Liste scrollable des rapports -->
                <div class="card-body p-0 flex-fill">
                    <?php if (empty($reports)): ?>
                        <div class="empty p-4">
                            <div class="empty-icon fs-1">📜</div>
                            <p class="empty-title fs-3 text-dark">Aucune chronique militaire</p>
                            <p class="empty-subtitle text-secondary">
                                Vos armées et éclaireurs n'ont encore mené aucun assaut ou mission d'espionnage.
                            </p>
                            <div class="empty-action">
                                <a href="?page=fleet" class="btn btn-sm btn-primary">
                                    🏇 Déployer une expédition
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush overflow-y-auto" style="max-height: 680px;" id="reportsListGroup">
                            <?php foreach ($reports as $rep): 
                                $isAtt = ($rep['attacker_id'] == $user['id']);
                                $isDef = ($rep['defender_id'] == $user['id']);
                                $mType = $rep['mission_type'];
                                $isSpy = ($mType === 'spy');
                                $isWin = ($rep['winner'] === 'attacker' && $isAtt) || ($rep['winner'] === 'defender' && $isDef);
                                $isSelected = ((int)$rep['id'] === $selectedReportId);

                                $itemCategory = $isSpy ? 'spy' : ($isWin ? 'win' : 'loss');
                                $searchStr = strtolower($rep['title'] . ' ' . ($rep['att_user'] ?? '') . ' ' . ($rep['def_user'] ?? '') . ' ' . $rep['mission_type']);
                                
                                $repTime = is_numeric($rep['created_at']) ? (int)$rep['created_at'] : strtotime($rep['created_at']);
                                $formattedDate = date('d/m/Y H:i', $repTime);

                                // Badges d'état
                                $badgeClass = $isSpy ? 'bg-info-lt text-info' : ($isWin ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger');
                                $badgeIcon = $isSpy ? '🥷' : ($isWin ? '🏆' : '💥');
                                $badgeLabel = $isSpy ? 'INFILTRATION' : ($isWin ? 'VICTOIRE' : 'DÉFAITE');
                            ?>
                                <a href="?page=reports&id=<?= $rep['id'] ?>" 
                                   class="list-group-item list-group-item-action p-3 transition-all report-item <?= $isSelected ? 'active bg-light border-start border-3 border-primary' : '' ?>"
                                   data-category="<?= $itemCategory ?>"
                                   data-search="<?= htmlspecialchars($searchStr) ?>"
                                   style="text-decoration: none;">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs rounded <?= $isSpy ? 'bg-info-lt' : ($isWin ? 'bg-success-lt' : 'bg-danger-lt') ?>">
                                                <?= $badgeIcon ?>
                                            </span>
                                            <span class="badge <?= $badgeClass ?> font-monospace" style="font-size: 0.68rem;">
                                                <?= $badgeLabel ?>
                                            </span>
                                        </div>
                                        <span class="text-secondary small font-monospace" style="font-size: 0.72rem;">
                                            <?= $formattedDate ?>
                                        </span>
                                    </div>

                                    <div class="fw-bold text-dark lh-sm mb-1" style="font-size: 0.88rem;">
                                        <?= htmlspecialchars($rep['title']) ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center small text-secondary">
                                        <span>
                                            <?= $isAtt ? '⚔️ Assaut lancé' : '🛡️ Attaque subie' ?>
                                        </span>
                                        <span class="badge bg-secondary-lt text-uppercase font-monospace" style="font-size: 0.65rem;">
                                            <?= htmlspecialchars($rep['mission_type']) ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- État aucun résultat après filtre/recherche -->
                        <div id="reportsSearchEmpty" class="p-4 text-center text-secondary small" style="display: none;">
                            <span>🔍 Aucun rapport ne correspond à votre recherche.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- COLONNE DROITE : DÉTAIL TACTIQUE DU RAPPORT -->
        <div class="col-lg-7 col-xl-8">
            <?php if ($currentReport && $repData): 
                $mType = $currentReport['mission_type'];
                $isSpy = ($mType === 'spy');
                $repTime = is_numeric($currentReport['created_at']) ? (int)$currentReport['created_at'] : strtotime($currentReport['created_at']);
                $formattedDate = date('d/m/Y à H:i:s', $repTime);

                $statusColor = $isSpy ? 'info' : ($isCurWin ? 'success' : 'danger');
                $statusBannerText = $isSpy 
                    ? "Infiltration Shinobi &bull; Rapport de Reconnaissance Furtive" 
                    : ($isCurWin ? "Victoire Éclatante du Clan !" : "Défaite Militaire lors de l'Affrontement");
            ?>
                <div class="card bg-white border shadow-sm">
                    <!-- Bandeau d'état coloré Tabler -->
                    <div class="card-status-top bg-<?= $statusColor ?>"></div>

                    <!-- En-tête de la Chronique -->
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-white border-bottom">
                        <div>
                            <div class="badge bg-<?= $statusColor ?>-lt text-<?= $statusColor ?> fw-bold mb-1">
                                <?= $isSpy ? '🥷 RAPPORT D\'ESPIONNAGE' : ($isCurWin ? '🏆 VICTOIRE' : '💥 DÉFAITE') ?>
                            </div>
                            <h3 class="card-title text-dark fw-bold m-0" style="font-size: 1.15rem;">
                                <?= htmlspecialchars($currentReport['title']) ?>
                            </h3>
                            <div class="text-secondary small mt-1 font-monospace">
                                📅 <?= $formattedDate ?>
                            </div>
                        </div>

                        <!-- Actions diplomatiques rapides -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($targetDaimyo) && $targetDaimyo !== $user['username']): ?>
                                <a href="?page=messages&tab=compose&to=<?= urlencode($targetDaimyo) ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm">
                                    <span>✉️</span> Contacter <?= htmlspecialchars($targetDaimyo) ?>
                                </a>
                            <?php endif; ?>
                            <a href="?page=fleet" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <span>🏇</span> Expédition
                            </a>
                        </div>
                    </div>

                    <!-- Corps de la Chronique -->
                    <div class="card-body p-4">

                        <?php if ($isSpy): ?>
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- CAS 1 : RAPPORT D'ESPIONNAGE SHINOBI                    -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <div class="alert alert-info d-flex align-items-center gap-3 mb-4 shadow-sm">
                                <span class="fs-1">🥷</span>
                                <div>
                                    <div class="fw-bold">Rapport d'Infiltration Furtive Shinobi</div>
                                    <div class="small">
                                        Fief cible observé : <strong><?= htmlspecialchars($repData['planet_name'] ?? 'Fief') ?></strong> 
                                        <code class="text-primary"><?= htmlspecialchars($repData['coords'] ?? '') ?></code>
                                    </div>
                                </div>
                            </div>

                            <!-- Ressources détectées dans les greniers -->
                            <div class="mb-4">
                                <h4 class="text-dark fw-bold d-flex align-items-center gap-2 mb-2">
                                    <span>🌾</span> Ressources Recensées dans les Greniers du Fief
                                </h4>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="card bg-light border p-3 text-center shadow-none h-100">
                                            <div class="text-secondary small fw-bold">🪵 Bois de Cèdre</div>
                                            <div class="h2 m-0 text-dark font-monospace">
                                                <?= number_format($repData['resources']['metal'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-light border p-3 text-center shadow-none h-100">
                                            <div class="text-secondary small fw-bold">🪨 Pierre de Taille</div>
                                            <div class="h2 m-0 text-dark font-monospace">
                                                <?= number_format($repData['resources']['crystal'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-light border p-3 text-center shadow-none h-100">
                                            <div class="text-secondary small fw-bold">🌾 Riz Impérial (Koku)</div>
                                            <div class="h2 m-0 text-dark font-monospace">
                                                <?= number_format($repData['resources']['deuterium'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Garnison Détectée -->
                            <div class="mb-4">
                                <h4 class="text-dark fw-bold d-flex align-items-center gap-2 mb-2">
                                    <span>🏯</span> Garnison Observée dans les Remparts
                                </h4>
                                <?php if (empty($repData['fleet'])): ?>
                                    <div class="p-3 bg-light rounded border text-muted small text-center">
                                        ✔ Aucune garnison en faction détectée lors de l'infiltration.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive border rounded">
                                        <table class="table table-vcenter table-hover card-table m-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Unité / Engin</th>
                                                    <th class="text-end">Effectif Recensé</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($repData['fleet'] as $code => $cnt): 
                                                    $uInfo = $unitsDict[$code] ?? ['name' => ucfirst(str_replace('_', ' ', $code)), 'icon' => '⚔️'];
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="me-2"><?= $uInfo['icon'] ?></span>
                                                            <strong class="text-dark"><?= htmlspecialchars($uInfo['name']) ?></strong>
                                                            <span class="text-muted small font-monospace ms-1">(<?= htmlspecialchars($code) ?>)</span>
                                                        </td>
                                                        <td class="text-end font-monospace fw-bold text-primary">
                                                            <?= number_format($cnt) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Infrastructures Observées -->
                            <?php if (!empty($repData['buildings'])): ?>
                                <div>
                                    <h4 class="text-dark fw-bold d-flex align-items-center gap-2 mb-2">
                                        <span>🏗️</span> Bâtiments Observés
                                    </h4>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($repData['buildings'] as $bCode => $bLvl): ?>
                                            <span class="badge bg-light text-dark border p-2 font-monospace">
                                                🏯 <?= htmlspecialchars($bCode) ?> : <strong>Niv. <?= $bLvl ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- CAS 2 : RAPPORT DE COMBAT & BATAILLE PROVINCIALE        -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            
                            <!-- Duel des Seigneurs (Bannière Comparative) -->
                            <div class="card bg-light border mb-4 shadow-none">
                                <div class="card-body p-3">
                                    <div class="row align-items-center text-center text-md-start">
                                        <!-- Attaquant -->
                                        <div class="col-md-5">
                                            <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                                                <span class="badge bg-danger-lt">ATTAQUANT</span>
                                                <span class="badge bg-secondary-lt font-monospace"><?= htmlspecialchars($repData['attacker_faction'] ?? 'Oda') ?></span>
                                            </div>
                                            <div class="h3 m-0 text-dark fw-bold">
                                                Daimyō <?= htmlspecialchars($repData['attacker_name'] ?? 'Inconnu') ?>
                                            </div>
                                            <div class="small text-secondary">
                                                <?= ($currentReport['attacker_id'] == $user['id']) ? '👑 Votre Armée' : '⚔️ Armée Hostile' ?>
                                            </div>
                                        </div>

                                        <!-- VS & Issue au centre -->
                                        <div class="col-md-2 my-2 my-md-0 text-center">
                                            <div class="badge bg-dark text-white p-2 px-3 fw-bold fs-3 shadow-sm">
                                                VS
                                            </div>
                                            <div class="small fw-bold mt-1 text-<?= ($currentReport['winner'] === 'attacker') ? 'danger' : 'success' ?>">
                                                <?= ($currentReport['winner'] === 'attacker') ? 'Victoire Attaquant' : 'Victoire Défenseur' ?>
                                            </div>
                                        </div>

                                        <!-- Défenseur -->
                                        <div class="col-md-5 text-center text-md-end">
                                            <div class="d-flex align-items-center justify-content-center justify-content-md-end gap-2 mb-1">
                                                <span class="badge bg-secondary-lt font-monospace"><?= htmlspecialchars($repData['defender_faction'] ?? 'Takeda') ?></span>
                                                <span class="badge bg-primary-lt">DÉFENSEUR</span>
                                            </div>
                                            <div class="h3 m-0 text-dark fw-bold">
                                                Daimyō <?= htmlspecialchars($repData['defender_name'] ?? 'Fief Neutre') ?>
                                            </div>
                                            <div class="small text-secondary font-monospace">
                                                Cible <?= htmlspecialchars($repData['target_coords'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Repli Tactique (si applicable) -->
                            <?php if (!empty($repData['evasion_applied'])): ?>
                                <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm">
                                    <span class="fs-1">🛡️</span>
                                    <div>
                                        <div class="fw-bold">Repli Stratégique Déclenché</div>
                                        <div class="small">
                                            Conformément aux décrets féodaux de sauvegarde, les troupes en faction se sont repliées sans engagement frontal.
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Butin Pillé (si raid avec ressources capturées) -->
                            <?php if (!empty($repData['looted']) && array_sum($repData['looted']) > 0): 
                                $totalLoot = array_sum($repData['looted']);
                            ?>
                                <div class="card bg-success-lt border-success mb-4 shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <div class="fw-bold text-success d-flex align-items-center gap-2">
                                                <span>🌾</span> Butin Prélevé en Conquête : <strong>+<?= number_format($totalLoot) ?> ressources</strong>
                                            </div>
                                            <span class="badge bg-success text-white">Saisie de Guerre</span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="p-2 bg-white rounded border text-center">
                                                    <div class="text-secondary small fw-bold">🪵 Bois de Cèdre</div>
                                                    <div class="fw-bold font-monospace text-success">+<?= number_format($repData['looted']['metal'] ?? 0) ?></div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-white rounded border text-center">
                                                    <div class="text-secondary small fw-bold">🪨 Pierre de Taille</div>
                                                    <div class="fw-bold font-monospace text-success">+<?= number_format($repData['looted']['crystal'] ?? 0) ?></div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-white rounded border text-center">
                                                    <div class="text-secondary small fw-bold">🌾 Riz Impérial</div>
                                                    <div class="fw-bold font-monospace text-success">+<?= number_format($repData['looted']['deuterium'] ?? 0) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Grille des Pertes & Bilan d'Armée (2 Colonnes) -->
                            <div class="row g-3 mb-4">
                                <!-- Bilan Armée Attaquante -->
                                <div class="col-md-6">
                                    <div class="card bg-white border h-100 shadow-none">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <strong class="text-dark d-flex align-items-center gap-1 small">
                                                <span>⚔️</span> Pertes Attaquant
                                            </strong>
                                            <?php if (empty($repData['attacker_lost'])): ?>
                                                <span class="badge bg-success-lt small">✔ Indemne</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-lt small">-<?= number_format(array_sum($repData['attacker_lost'])) ?> unités</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body p-3">
                                            <?php if (empty($repData['attacker_lost'])): ?>
                                                <div class="text-success small fw-semibold text-center py-3">
                                                    ✔ Aucune perte subie par l'armée attaquante !
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-2">
                                                    <?php foreach ($repData['attacker_lost'] as $code => $cnt): 
                                                        $uInfo = $unitsDict[$code] ?? ['name' => ucfirst(str_replace('_', ' ', $code)), 'icon' => '⚔️'];
                                                    ?>
                                                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border-bottom">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span><?= $uInfo['icon'] ?></span>
                                                                <span class="small fw-semibold text-dark"><?= htmlspecialchars($uInfo['name']) ?></span>
                                                            </div>
                                                            <span class="badge bg-danger text-white font-monospace">
                                                                -<?= number_format($cnt) ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bilan Garnison Défenseur -->
                                <div class="col-md-6">
                                    <div class="card bg-white border h-100 shadow-none">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <strong class="text-dark d-flex align-items-center gap-1 small">
                                                <span>🛡️</span> Pertes Défenseur
                                            </strong>
                                            <?php if (empty($repData['defender_lost'])): ?>
                                                <span class="badge bg-success-lt small">✔ Indemne</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-lt small">-<?= number_format(array_sum($repData['defender_lost'])) ?> unités</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body p-3">
                                            <?php if (empty($repData['defender_lost'])): ?>
                                                <div class="text-success small fw-semibold text-center py-3">
                                                    ✔ Aucune perte subie par la garnison du fief !
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-2">
                                                    <?php foreach ($repData['defender_lost'] as $code => $cnt): 
                                                        $uInfo = $unitsDict[$code] ?? ['name' => ucfirst(str_replace('_', ' ', $code)), 'icon' => '⚔️'];
                                                    ?>
                                                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border-bottom">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span><?= $uInfo['icon'] ?></span>
                                                                <span class="small fw-semibold text-dark"><?= htmlspecialchars($uInfo['name']) ?></span>
                                                            </div>
                                                            <span class="badge bg-danger text-white font-monospace">
                                                                -<?= number_format($cnt) ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- Pied de carte : Actions et diplomatie -->
                    <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
                        <div class="text-muted small">
                            Chronique archivée sous le décret N° <code>#<?= $currentReport['id'] ?></code>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($targetDaimyo) && $targetDaimyo !== $user['username']): ?>
                                <a href="?page=messages&tab=compose&to=<?= urlencode($targetDaimyo) ?>" class="btn btn-sm btn-primary">
                                    ✉️ Envoyer une missive à <?= htmlspecialchars($targetDaimyo) ?>
                                </a>
                            <?php endif; ?>
                            <a href="?page=fleet" class="btn btn-sm btn-outline-danger">
                                🏇 Déployer une armée
                            </a>
                        </div>
                    </div>

                </div>

            <?php else: ?>
                <!-- Aucun rapport sélectionné -->
                <div class="card bg-white border shadow-sm">
                    <div class="empty p-5">
                        <div class="empty-icon fs-1">📜</div>
                        <p class="empty-title fs-2 text-dark">Sélectionnez une Chronique Militaire</p>
                        <p class="empty-subtitle text-secondary">
                            Cliquez sur un rapport dans le registre de gauche pour examiner le détail tactique des engagements et des butins saisis.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- LOGIQUE CLIENT TABLER : FILTRAGE INSTANTANÉ DES RAPPORTS -->
<script>
let currentReportFilter = 'all';

function filterReportsList(filter, btn) {
    currentReportFilter = filter;
    
    // Mettre à jour les pilules de filtre
    document.querySelectorAll('#reportFilterPills button').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    applyReportsFilter();
}

const reportSearchInput = document.getElementById('reportSearchInput');
if (reportSearchInput) {
    reportSearchInput.addEventListener('input', function() {
        applyReportsFilter();
    });
}

function applyReportsFilter() {
    const query = (reportSearchInput ? reportSearchInput.value : '').toLowerCase().trim();
    const items = document.querySelectorAll('.report-item');
    let visibleCount = 0;

    items.forEach(item => {
        const cat = item.getAttribute('data-category');
        const search = item.getAttribute('data-search') || '';

        const matchesCat = (currentReportFilter === 'all' || cat === currentReportFilter);
        const matchesQuery = (query === '' || search.includes(query));

        if (matchesCat && matchesQuery) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const counterEl = document.getElementById('reportsVisibleCounter');
    if (counterEl) counterEl.textContent = visibleCount;

    const emptyEl = document.getElementById('reportsSearchEmpty');
    if (emptyEl) {
        emptyEl.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}
</script>
