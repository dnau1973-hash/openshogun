<?php
/**
 * Vue Partielle MVC : Dashboard Exécutif & Télémétrique (Game Studio)
 * Fichier : views/gamestudio/dashboard.php
 * Utilisable dans : views/gamestudio.php et views/admin.php
 */

$currentPageSlug = $page ?? 'gamestudio';
$analyticsPeriod = $analyticsPeriod ?? '30d';
$trendDays = $trendDays ?? 30;
?>

<div class="gamestudio-dashboard">
    <!-- En-tête du Dashboard & Filtres Temporels -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-primary d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-chart-line text-primary me-1"></i>Tableau de Bord Exécutif &amp; Télémétrie
            </h3>
            <div class="text-secondary small mt-1">
                Supervision globale de l'activité des Daimyōs, progression moyenne des quêtes, santé du serveur et métriques de production.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-success-lt d-inline-flex align-items-center gap-1 py-2 px-3">
                <span class="status-dot status-dot-animated bg-success"></span>
                <span>Système Opérationnel</span>
            </span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="?page=<?= $currentPageSlug ?>&tab=dashboard&period=today" class="btn btn-outline-secondary <?= $analyticsPeriod === 'today' ? 'active fw-bold' : '' ?>">Aujourd'hui</a>
                <a href="?page=<?= $currentPageSlug ?>&tab=dashboard&period=7d" class="btn btn-outline-secondary <?= $analyticsPeriod === '7d' ? 'active fw-bold' : '' ?>">7 jours</a>
                <a href="?page=<?= $currentPageSlug ?>&tab=dashboard&period=30d" class="btn btn-outline-secondary <?= $analyticsPeriod === '30d' ? 'active fw-bold' : '' ?>">30 jours</a>
                <a href="?page=<?= $currentPageSlug ?>&tab=dashboard&period=all" class="btn btn-outline-secondary <?= $analyticsPeriod === 'all' ? 'active fw-bold' : '' ?>">Tout</a>
            </div>
            <button type="button" onclick="location.reload()" class="btn btn-sm btn-outline-primary" title="Actualiser les métriques">
                <i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser
            </button>
        </div>
    </div>

    <!-- ── 4 CARTES KPIS TÉLÉMÉTRIE & PERFORMANCE ── -->
    <div class="row row-cards mb-4">
        <!-- 1. Total Pages Vues -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-primary shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Total Pages Vues</div>
                        <span class="avatar avatar-sm rounded bg-primary-lt text-primary fs-3"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h1 m-0 font-weight-bold text-dark"><?= number_format($kpiMetrics['total_views'] ?? 0) ?></div>
                        <?php if (($kpiMetrics['views_delta'] ?? 0) >= 0): ?>
                            <span class="badge bg-success-lt text-success small fw-bold"><i class="fa-solid fa-arrow-trend-up me-1"></i>+<?= $kpiMetrics['views_delta'] ?? 0 ?>%</span>
                        <?php else: ?>
                            <span class="badge bg-danger-lt text-danger small fw-bold"><i class="fa-solid fa-arrow-trend-down me-1"></i><?= $kpiMetrics['views_delta'] ?? 0 ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-users-viewfinder text-primary me-1"></i><strong><?= number_format($kpiMetrics['unique_visitors'] ?? 0) ?></strong> visiteurs uniques
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Joueurs Actifs Uniques (DAU / MAU) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-teal shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Daimyōs Actifs (<?= strtoupper($analyticsPeriod) ?>)</div>
                        <span class="avatar avatar-sm rounded bg-teal-lt text-teal fs-3"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h1 m-0 font-weight-bold text-teal"><?= number_format($kpiMetrics['unique_users'] ?? 0) ?></div>
                        <?php if (($kpiMetrics['users_delta'] ?? 0) >= 0): ?>
                            <span class="badge bg-success-lt text-success small fw-bold"><i class="fa-solid fa-arrow-trend-up me-1"></i>+<?= $kpiMetrics['users_delta'] ?? 0 ?>%</span>
                        <?php else: ?>
                            <span class="badge bg-danger-lt text-danger small fw-bold"><i class="fa-solid fa-arrow-trend-down me-1"></i><?= $kpiMetrics['users_delta'] ?? 0 ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-clock text-teal me-1"></i>Temps réel : <strong><?= (int)($activeUsersRealtime ?? 0) ?></strong> (24h : <strong><?= (int)($activeUsers24h ?? 0) ?></strong>)
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Taux Humains vs Bots / Crawlers -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-success shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Taux Humains vs Bots</div>
                        <span class="avatar avatar-sm rounded bg-success-lt text-success fs-3"><i class="fa-solid fa-shield-halved"></i></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h1 m-0 font-weight-bold text-success"><?= $kpiMetrics['human_ratio'] ?? 100 ?>%</div>
                        <span class="badge bg-success-lt text-success small fw-bold">Humains</span>
                    </div>
                    <div class="progress progress-xs mt-2 mb-1">
                        <div class="progress-bar bg-success" style="width: <?= $kpiMetrics['human_ratio'] ?? 100 ?>%"></div>
                        <div class="progress-bar bg-secondary opacity-50" style="width: <?= $kpiMetrics['bot_ratio'] ?? 0 ?>%"></div>
                    </div>
                    <div class="text-secondary small mt-2 border-top pt-1">
                        Bots / Crawlers filtrés : <strong><?= $kpiMetrics['bot_ratio'] ?? 0 ?>%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Durée Moyenne / Session -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Temps Moyen / Session</div>
                        <span class="avatar avatar-sm rounded bg-warning-lt text-warning fs-3"><i class="fa-solid fa-stopwatch"></i></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h1 m-0 font-weight-bold text-warning-emphasis"><?= $kpiMetrics['avg_session'] ?? '24m' ?></div>
                        <span class="badge bg-warning-lt text-warning-emphasis small fw-bold">Engagement</span>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-repeat text-warning me-1"></i>Fréquence : <strong><?= $avgSessionsPerDay ?? '3.2' ?> sessions/j</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ACCÈS RAPIDES AUX MODULES (CARTES KPI STATS CLIQUABLES) ── -->
    <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="m-0 text-secondary text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                <i class="fa-solid fa-bolt text-warning me-1"></i>Modules &amp; Métriques Générales
            </h4>
            <span class="text-muted small">Synthèse rapide de l'écosystème OpenShogun</span>
        </div>
        <div class="row row-cards mb-4">
            <!-- Donjons Sacrés -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm h-100 shadow-sm" title="Surveiller les 12 Donjons Authentiques du Japon">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded bg-warning-lt text-warning" style="font-size:1.3rem;"><i class="fa-solid fa-chess-rook"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Donjons Sacrés</div>
                                <div class="text-warning font-weight-bold" style="font-size:1.25rem;">
                                    <?= (int)($spawnedCastlesCount ?? 0) ?> / 12 Déployés
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">
                            現存十二天守 &bull; Forteresses Impériales
                        </div>
                    </div>
                </div>
            </div>

            <!-- Samouraïs Héros -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm h-100 shadow-sm" title="Gérer les Samouraïs Héros et Reliques">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded bg-purple-lt text-purple" style="font-size:1.3rem;"><i class="fa-solid fa-user-ninja"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Samouraïs Héros</div>
                                <div class="text-purple font-weight-bold" style="font-size:1.25rem;">
                                    <?= (int)($totalHeroes ?? 0) ?> Héros
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">
                            <span class="<?= (($heroesDead ?? 0) + ($heroesReviving ?? 0) > 0) ? 'text-danger font-weight-bold' : '' ?>">
                                <?= ($heroesDead ?? 0) + ($heroesReviving ?? 0) ?> en péril
                            </span>
                            | <i class="fa-solid fa-shield-halved text-success me-1"></i><?= (int)($totalRelicsFound ?? 0) ?> reliques
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clans IA (Bots) -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm h-100 shadow-sm" title="Gérer les Daimyōs IA et bots">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded bg-indigo-lt text-indigo" style="font-size:1.3rem;"><i class="fa-solid fa-robot"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Clans IA (Bots)</div>
                                <div class="text-indigo font-weight-bold" style="font-size:1.25rem;">
                                    <?= (int)($totalBots ?? 0) ?> PNJ
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">
                            Simulation IA &bull; Cycle autonome
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daimyōs Joueurs -->
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm h-100 shadow-sm" title="Gérer les joueurs et privilèges">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded bg-warning-lt text-warning" style="font-size:1.3rem;"><i class="fa-solid fa-users"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Daimyōs Joueurs</div>
                                <div class="text-warning font-weight-bold" style="font-size:1.25rem;">
                                    <?= (int)($totalUsers ?? 0) ?> Joueurs
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2">
                            Comptes humains inscrits
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ZONE VISUALISATION DES DONNÉES (2 COLONNES) ── -->
    <div class="row row-cards mb-4">
        <!-- Graphique 1 : Évolution Vues de Pages, Daimyōs Uniques & Inscriptions -->
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-line text-primary me-1"></i>Évolution Télémétrique &amp; Activités (<?= $trendDays ?> Jours)
                    </h4>
                    <span class="badge bg-primary-lt">Période : <?= strtoupper($analyticsPeriod) ?></span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <!-- Conteneur Graphique 1 (ApexCharts + Canvas de repli direct) -->
                    <div id="chart-activity" style="min-height: 270px; width: 100%;">
                        <div id="adminApexTrendChart" style="min-height: 270px; width: 100%;"></div>
                        <div id="adminTrendChartFallback" style="display: none; position: relative; height: 260px; width: 100%;">
                            <canvas id="adminTrendChart" style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-around text-center py-2 bg-light small flex-wrap gap-2">
                    <div>
                        <span class="badge badge-dot bg-primary me-1"></span> Pages Vues : <strong><?= number_format(array_sum(array_column($cleanTimeline ?? [], 'views'))) ?></strong>
                    </div>
                    <div>
                        <span class="badge badge-dot bg-success me-1"></span> Daimyōs Actifs : <strong><?= number_format(max(array_column($cleanTimeline ?? [], 'unique_users') ?: [0])) ?> pic/j</strong>
                    </div>
                    <div>
                        <span class="badge badge-dot bg-warning me-1"></span> Inscriptions : <strong><?= number_format(array_sum(array_column($cleanTimeline ?? [], 'registrations'))) ?> daimyōs</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition des Scores & Niveaux -->
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-info me-1"></i>Répartition des Daimyōs Joueurs
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Strates de Puissance -->
                    <div class="mb-3">
                        <div class="small fw-bold text-muted mb-1">Niveaux de Puissance Militaire :</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="fa-solid fa-seedling text-success me-1"></i>Débutants (&lt; 500 pts)</span>
                            <strong><?= $ptsStrat['debutant'] ?? 0 ?> (<?= round((($ptsStrat['debutant'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar bg-info" style="width: <?= round((($ptsStrat['debutant'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="fa-solid fa-shield-halved text-primary me-1"></i>Établis (500 - 2 000 pts)</span>
                            <strong><?= $ptsStrat['intermediaire'] ?? 0 ?> (<?= round((($ptsStrat['intermediaire'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar bg-primary" style="width: <?= round((($ptsStrat['intermediaire'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%"></div>
                        </div>

                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="fa-solid fa-crown text-warning me-1"></i>Vétérans (&gt; 2 000 pts)</span>
                            <strong><?= $ptsStrat['veteran'] ?? 0 ?> (<?= round((($ptsStrat['veteran'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%)</strong>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-warning" style="width: <?= round((($ptsStrat['veteran'] ?? 0) / max(1, $totalUsers ?? 1)) * 100) ?>%"></div>
                        </div>
                    </div>

                    <!-- Répartition par Clan Féodal -->
                    <div class="pt-3 border-top">
                        <div class="small fw-bold text-muted mb-2">Répartition par Clan Féodal :</div>
                        <div class="row g-2 text-center">
                            <?php foreach (['terran' => ['name' => 'Clan Oda', 'icon' => '<i class="fa-solid fa-feather text-danger"></i>', 'color' => 'danger'], 'vorash' => ['name' => 'Clan Takeda', 'icon' => '<i class="fa-solid fa-paw text-warning"></i>', 'color' => 'warning'], 'aethelis' => ['name' => 'Clan Tokugawa', 'icon' => '<i class="fa-solid fa-dragon text-success"></i>', 'color' => 'success']] as $fKey => $fMeta): 
                                $fCount = (int)($factionCounts[$fKey] ?? 0);
                                $fPct = round(($fCount / max(1, $totalUsers ?? 1)) * 100);
                            ?>
                                <div class="col-4">
                                    <div class="p-2 border rounded bg-light">
                                        <div class="fs-3"><?= $fMeta['icon'] ?></div>
                                        <div class="small fw-bold text-truncate"><?= $fMeta['name'] ?></div>
                                        <div class="fw-bold text-<?= $fMeta['color'] ?>"><?= $fCount ?></div>
                                        <div class="text-muted" style="font-size: 0.68rem;"><?= $fPct ?>%</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODULE ANALYTICS 2 : TOP PAGES & DISTRIBUTION HORAIRE ── -->
    <div class="row row-cards mb-4">
        <!-- Graphique 2 : Top Modules & Pages Consultées -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-layer-group text-info me-1"></i>Top 8 Modules &amp; Pages Consultées
                    </h4>
                    <span class="badge bg-info-lt">Pages Vues</span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div id="chart-top-pages" style="min-height: 280px; width: 100%;">
                        <div id="adminApexTopPagesChart" style="min-height: 280px; width: 100%;"></div>
                        <div id="adminTopPagesFallback" style="display: none; position: relative; height: 260px; width: 100%;">
                            <canvas id="adminTopPagesChart" style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique 3 : Heures de Pointe (0h - 23h) -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i>Heures de Pointe (0h &mdash; 23h)
                    </h4>
                    <span class="badge bg-warning-lt">Affluence Globale</span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div id="chart-hourly" style="min-height: 280px; width: 100%;">
                        <div id="adminApexHourlyChart" style="min-height: 280px; width: 100%;"></div>
                        <div id="adminHourlyFallback" style="display: none; position: relative; height: 260px; width: 100%;">
                            <canvas id="adminHourlyChart" style="width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TABLEAU DYNAMIQUE : CLASSEMENT DES DAIMYŌS LES PLUS ACTIFS ── -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="card-title m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-trophy text-warning me-1"></i>Classement Télémétrique des Daimyōs les Plus Actifs
                </h4>
                <div class="text-secondary small">Volume d'interactions et dernières actions enregistrées</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="activeUsersSearchInput" class="form-control form-control-sm" placeholder="Filtrer par daimyō ou clan...">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover" id="activeUsersTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Daimyō</th>
                        <th>Clan &amp; Puissance</th>
                        <th>Interactions Télémétriques</th>
                        <th>Dernier Module Consulté</th>
                        <th class="text-end">Dernier Signal</th>
                    </tr>
                </thead>
                <tbody id="activeUsersTableBody">
                    <?php if (empty($topActiveUsers)): ?>
                        <tr><td colspan="6" class="text-center text-muted p-3">Aucune donnée télémétrique disponible sur cette période.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topActiveUsers as $idx => $uRow): 
                            $fName = $uRow['faction'] ?? 'terran';
                            $fInfo = [
                                'terran' => ['name' => 'Clan Oda', 'icon' => '<i class="fa-solid fa-feather text-danger"></i>'],
                                'vorash' => ['name' => 'Clan Takeda', 'icon' => '<i class="fa-solid fa-paw text-warning"></i>'],
                                'aethelis' => ['name' => 'Clan Tokugawa', 'icon' => '<i class="fa-solid fa-dragon text-success"></i>']
                            ][$fName] ?? ['name' => 'Clan Féodal', 'icon' => '<i class="fa-solid fa-shield text-secondary"></i>'];

                            $rankBadge = match($idx) {
                                0 => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i>1</span>',
                                1 => '<span class="badge bg-secondary text-white">2</span>',
                                2 => '<span class="badge bg-orange text-white">3</span>',
                                default => '<span class="badge bg-light text-muted">#' . ($idx + 1) . '</span>'
                            };
                            $timeAgo = !empty($uRow['last_action_at']) ? date('d/m H:i', strtotime($uRow['last_action_at'])) : 'Récemment';
                        ?>
                            <tr class="active-user-row" data-search="<?= strtolower(htmlspecialchars(($uRow['username'] ?? '') . ' ' . $fInfo['name'])) ?>">
                                <td><?= $rankBadge ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-xs rounded-circle bg-primary-lt font-weight-bold text-uppercase">
                                            <?= strtoupper(substr($uRow['username'] ?? 'DA', 0, 2)) ?>
                                        </span>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($uRow['username'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt me-1"><?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?></span>
                                    <span class="text-muted small"><?= number_format((int)($uRow['points'] ?? 0)) ?> pts</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-xs w-50">
                                            <div class="progress-bar bg-primary" style="width: <?= min(100, max(10, (int)($uRow['action_count'] ?? 0))) ?>%"></div>
                                        </div>
                                        <strong class="text-primary small"><?= number_format((int)($uRow['action_count'] ?? 0)) ?> logs</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-azure-lt">
                                        <i class="fa-solid fa-compass me-1"></i><?= htmlspecialchars($uRow['last_page_label'] ?? $uRow['last_page'] ?? 'Fief') ?>
                                    </span>
                                </td>
                                <td class="text-end text-muted small">
                                    <i class="fa-regular fa-clock me-1"></i><?= $timeAgo ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── ZONE ACTIVITÉ RÉCENTE & SANTÉ SYSTÈME (2 COLONNES) ── -->
    <div class="row row-cards">
        <!-- Journal des Dernières Activités -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bolt text-warning me-1"></i>Inscriptions &amp; Activités Récentes
                    </h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Daimyō</th>
                                <th>Clan</th>
                                <th>Action / Événement</th>
                                <th class="text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr><td colspan="4" class="text-center text-muted p-3">Aucune activité récente enregistrée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $ru): 
                                    $fName = $ru['faction'] ?? 'terran';
                                    $fInfo = [
                                        'terran' => ['name' => 'Clan Oda', 'icon' => '<i class="fa-solid fa-feather text-danger"></i>'],
                                        'vorash' => ['name' => 'Clan Takeda', 'icon' => '<i class="fa-solid fa-paw text-warning"></i>'],
                                        'aethelis' => ['name' => 'Clan Tokugawa', 'icon' => '<i class="fa-solid fa-dragon text-success"></i>']
                                    ][$fName] ?? ['name' => 'Clan Féodal', 'icon' => '<i class="fa-solid fa-shield text-secondary"></i>'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($ru['username'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt"><?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-lt"><i class="fa-solid fa-seedling me-1"></i>Inscription &amp; Fief Capital</span>
                                        </td>
                                        <td class="text-end text-muted small">
                                            <?= !empty($ru['created_at']) ? date('d/m H:i', strtotime($ru['created_at'])) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Santé Système & Alertes Techniques -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-info me-1"></i>Santé Système &amp; Métriques Serveur
                    </h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <!-- Base de données -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-database text-primary me-1"></i>Base de Données SQL</div>
                                <div class="small text-muted">Stockage : <?= $dbSizeMb ?? 0 ?> Mo &bull; Tables indexées</div>
                            </div>
                            <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i>Opérationnelle</span>
                        </div>

                        <!-- GameLoop & Bots -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-gear text-secondary me-1"></i>Simulation IA &amp; Game Loop</div>
                                <div class="small text-muted"><?= (int)($totalBots ?? 0) ?> Daimyōs IA configurés</div>
                            </div>
                            <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i>Actif</span>
                        </div>

                        <!-- Support & Tickets -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-headset text-danger me-1"></i>File des Tickets Joueurs</div>
                                <div class="small text-muted"><?= (int)($supportStats['total'] ?? 0) ?> tickets enregistrés</div>
                            </div>
                            <?php if (($supportStats['count_pending'] ?? 0) > 0): ?>
                                <span class="badge bg-danger text-white">
                                    <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i><?= $supportStats['count_pending'] ?> en attente
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i>À jour</span>
                            <?php endif; ?>
                        </div>

                        <!-- Version Git -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-arrows-rotate text-teal me-1"></i>Version Déployée (Git)</div>
                                <div class="small text-muted">Branche <?= htmlspecialchars($localGitInfo['branch'] ?? 'main') ?> (<?= htmlspecialchars($localGitInfo['short_sha'] ?? 'HEAD') ?>)</div>
                            </div>
                            <span class="badge bg-teal-lt fw-bold">Synchronisé</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// =========================================================================
// MOTEUR GRAPHIQUE APEXCHARTS & CANVAS (DASHBOARD GAME STUDIO)
// =========================================================================
let trendApexChart = null;
let topPagesApexChart = null;
let hourlyApexChart = null;
let chartRenderTimer = null;

const telemetryData = {
    timeline: <?= json_encode($cleanTimeline ?? [], JSON_UNESCAPED_UNICODE) ?>,
    topPages: <?= json_encode($cleanTopPages ?? [], JSON_UNESCAPED_UNICODE) ?>,
    hourly: <?= json_encode($cleanHourly ?? [], JSON_UNESCAPED_UNICODE) ?>
};

function requestRenderAnalyticsCharts() {
    if (chartRenderTimer) clearTimeout(chartRenderTimer);
    chartRenderTimer = setTimeout(() => {
        renderAnalyticsCharts();
    }, 40);
}

function renderAnalyticsCharts() {
    const trendTarget = document.getElementById('adminApexTrendChart') || document.getElementById('chart-activity');
    if (!trendTarget) return;

    if (trendTarget.clientWidth <= 0) {
        requestAnimationFrame(() => {
            requestRenderAnalyticsCharts();
        });
        return;
    }

    if (typeof ApexCharts !== 'undefined') {
        try {
            renderApexChartsSuite();
            return;
        } catch (err) {
            console.warn("Erreur d'initialisation ApexCharts, bascule sur Canvas :", err);
        }
    }

    renderCanvasFallbackCharts();
}

function renderApexChartsSuite() {
    if (trendApexChart) {
        try { trendApexChart.destroy(); } catch (e) {}
        trendApexChart = null;
    }
    if (topPagesApexChart) {
        try { topPagesApexChart.destroy(); } catch (e) {}
        topPagesApexChart = null;
    }
    if (hourlyApexChart) {
        try { hourlyApexChart.destroy(); } catch (e) {}
        hourlyApexChart = null;
    }

    // Réinitialiser la visibilité des conteneurs
    const cTrend = document.getElementById('adminApexTrendChart');
    const fTrend = document.getElementById('adminTrendChartFallback');
    if (cTrend) cTrend.style.display = 'block';
    if (fTrend) fTrend.style.display = 'none';

    const cTop = document.getElementById('adminApexTopPagesChart');
    const fTop = document.getElementById('adminTopPagesFallback');
    if (cTop) cTop.style.display = 'block';
    if (fTop) fTop.style.display = 'none';

    const cHour = document.getElementById('adminApexHourlyChart');
    const fHour = document.getElementById('adminHourlyFallback');
    if (cHour) cHour.style.display = 'block';
    if (fHour) fHour.style.display = 'none';

    // ── GRAPHIQUE 1 : Évolution Vues, Actifs, Inscriptions (Spline Area) ──
    const trendContainer = document.getElementById('adminApexTrendChart');
    if (trendContainer && telemetryData.timeline.length > 0) {
        const categories = telemetryData.timeline.map(d => String(d.label || ''));
        const viewsData = telemetryData.timeline.map(d => Number(d.views) || 0);
        const usersData = telemetryData.timeline.map(d => Number(d.unique_users) || 0);
        const regData = telemetryData.timeline.map(d => Number(d.registrations) || 0);

        const trendOptions = {
            series: [
                { name: 'Pages Vues', data: viewsData },
                { name: 'Daimyōs Actifs', data: usersData },
                { name: 'Inscriptions', data: regData }
            ],
            chart: {
                type: 'area',
                height: 270,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: true, easing: 'easeinout', speed: 400 }
            },
            colors: ['#206bc4', '#2fb344', '#f59f00'],
            stroke: { curve: viewsData.length <= 2 ? 'straight' : 'smooth', width: [2.5, 2.5, 2] },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.15)',
                strokeDashArray: 4,
                padding: { left: 10, right: 10, top: 0, bottom: 0 }
            },
            xaxis: {
                categories: categories,
                labels: { style: { colors: '#64748b', fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#64748b', fontSize: '11px' },
                    formatter: val => Math.round(val).toLocaleString('fr-FR')
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                theme: 'light',
                y: { formatter: val => Number(val).toLocaleString('fr-FR') + ' interactions' }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                markers: { radius: 12 }
            }
        };
        trendApexChart = new ApexCharts(trendContainer, trendOptions);
        trendApexChart.render();
    }

    // ── GRAPHIQUE 2 : Top Pages Consultées (Barres Horizontales) ──
    const topPagesContainer = document.getElementById('adminApexTopPagesChart');
    if (topPagesContainer && telemetryData.topPages.length > 0) {
        const pageLabels = telemetryData.topPages.map(d => String(d.label || ''));
        const pageHits = telemetryData.topPages.map(d => Number(d.hit_count) || 0);

        const topOptions = {
            series: [{ name: 'Pages Vues', data: pageHits }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '60%',
                    distributed: false
                }
            },
            colors: ['#4299e1'],
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                style: { colors: ['#2d3748'], fontSize: '11px' },
                formatter: (val, opt) => {
                    const pct = telemetryData.topPages[opt.dataPointIndex]?.percent || 0;
                    return Number(val).toLocaleString('fr-FR') + ' (' + pct + '%)';
                },
                offsetX: 10
            },
            xaxis: {
                categories: pageLabels,
                labels: { style: { colors: '#64748b', fontSize: '11px' } }
            },
            yaxis: {
                labels: { style: { colors: '#2d3748', fontSize: '11px' }, maxWidth: 140 }
            },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.15)',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'light',
                y: { formatter: val => Number(val).toLocaleString('fr-FR') + ' vues' }
            }
        };
        topPagesApexChart = new ApexCharts(topPagesContainer, topOptions);
        topPagesApexChart.render();
    }

    // ── GRAPHIQUE 3 : Heures de Pointe (Barres Verticales 24h) ──
    const hourlyContainer = document.getElementById('adminApexHourlyChart');
    if (hourlyContainer) {
        const hourCategories = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0') + 'h');
        const hourlyOptions = {
            series: [{ name: 'Activité', data: telemetryData.hourly }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    borderRadius: 3,
                    columnWidth: '55%'
                }
            },
            colors: ['#f59f00'],
            dataLabels: { enabled: false },
            xaxis: {
                categories: hourCategories,
                labels: {
                    rotate: -45,
                    style: { colors: '#64748b', fontSize: '10px' }
                }
            },
            yaxis: {
                labels: { style: { colors: '#64748b', fontSize: '11px' } }
            },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.15)',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'light',
                y: { formatter: val => Number(val).toLocaleString('fr-FR') + ' signaux' }
            }
        };
        hourlyApexChart = new ApexCharts(hourlyContainer, hourlyOptions);
        hourlyApexChart.render();
    }
}

function renderCanvasFallbackCharts() {
    // Si ApexCharts échoue ou est absent, affiche le fallback Canvas HD
    const cTrend = document.getElementById('adminApexTrendChart');
    const fTrend = document.getElementById('adminTrendChartFallback');
    if (cTrend) cTrend.style.display = 'none';
    if (fTrend) fTrend.style.display = 'block';
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    requestRenderAnalyticsCharts();

    // Filtre dynamique du tableau des daimyōs actifs
    const searchInput = document.getElementById('activeUsersSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#activeUsersTableBody .active-user-row').forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                row.style.display = searchData.includes(query) ? '' : 'none';
            });
        });
    }
});

// Réactualisation automatique lors du redimensionnement de la fenêtre
window.addEventListener('resize', () => {
    requestRenderAnalyticsCharts();
});
</script>
