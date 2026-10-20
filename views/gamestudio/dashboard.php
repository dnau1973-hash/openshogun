<?php
/**
 * Vue Partielle MVC : Dashboard Exécutif & Télémétrique (Game Studio)
 * Fichier : views/gamestudio/dashboard.php
 * Utilisable dans : views/gamestudio.php et views/admin.php
 */

$currentPageSlug = $page ?? 'gamestudio';
$analyticsPeriod = $analyticsPeriod ?? '30d';
$trendDays = $trendDays ?? 30;

// Initialisation du Moteur de Supervision Raspberry Pi & Serveur Hôte
require_once __DIR__ . '/../../core/ServerMonitorEngine.php';
$serverMonitor = new ServerMonitorEngine();
$serverDiag = $serverMonitor->getSystemDiagnostics();
?>

<style>
    .rpi-sparkline { height: 56px; min-height: 56px; margin-left: -4px; margin-right: -4px; }
    .rpi-sparkline svg { overflow: visible; }
</style>

<div class="gamestudio-dashboard">
    <!-- ── SUPERVISION EN TEMPS RÉEL DU SERVEUR RASPBERRY PI & INFRASTRUCTURE ── -->
    <div class="card shadow-sm mb-4" id="rpi-monitor-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 bg-light">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-md rounded bg-danger-lt text-danger shadow-xs" style="font-size:1.6rem;">
                    <i class="fa-brands fa-raspberry-pi"></i>
                </span>
                <div>
                    <h3 class="card-title text-dark m-0 d-flex align-items-center gap-2" style="font-size:1.2rem;">
                        <span>Supervision Matérielle Serveur &bull; Raspberry Pi</span>
                        <span id="rpi-live-dot" class="status-dot status-dot-animated bg-success" title="Sonde en direct"></span>
                    </h3>
                    <div class="text-muted small mt-0.5">
                        <span id="rpi-model-name" class="fw-bold text-secondary"><?= htmlspecialchars($serverDiag['hardware']['model']) ?></span> &bull; 
                        <span id="rpi-os-name"><?= htmlspecialchars($serverDiag['hardware']['os']) ?> (<?= $serverDiag['hardware']['arch'] ?>)</span> &bull; 
                        Uptime : <strong id="rpi-uptime-str" class="text-dark font-monospace"><?= $serverDiag['uptime']['formatted'] ?></strong>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Score de santé global -->
                <span id="rpi-health-badge" class="badge bg-<?= $serverDiag['health_score']['class'] ?>-lt px-3 py-2 fs-6">
                    <i class="fa-solid fa-heart-pulse me-1"></i>Santé Hôte : <strong id="rpi-health-score"><?= $serverDiag['health_score']['score'] ?>%</strong> (<span id="rpi-health-label"><?= $serverDiag['health_score']['label'] ?></span>)
                </span>

                <!-- Interrupteur Live Polling (3s) -->
                <button type="button" id="btn-toggle-rpi-live" class="btn btn-sm btn-outline-success active" onclick="toggleRpiLiveMonitoring()" title="Activer / Mettre en pause le rafraîchissement temps réel (3s)">
                    <i class="fa-solid fa-satellite-dish me-1"></i><span id="rpi-live-text">Live 3s (Actif)</span>
                </button>

                <!-- Actions de maintenance -->
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="triggerServerPingDb(this)" title="Mesurer le temps de réponse SQL en millisecondes">
                        <i class="fa-solid fa-bolt text-warning me-1"></i>Ping SQL
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="triggerServerResetOpcache(this)" title="Purger le cache d'opcodes PHP sans redémarrer PHP-FPM">
                        <i class="fa-solid fa-broom text-info me-1"></i>Purge OPcache
                    </button>
                    <a href="/api/server_monitor.php?action=export_csv" class="btn btn-outline-secondary" title="Exporter l'historique de télémétrie (CSV)">
                        <i class="fa-solid fa-file-csv text-success"></i>
                    </a>
                    <button type="button" class="btn btn-outline-primary" onclick="refreshRpiMetrics(this)" title="Interroger immédiatement les sondes système">
                        <i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- ── 5 Jauges Hardware Clés (Température, CPU, RAM, Disque, Réseau) ── -->
            <div class="row row-cards mb-3">
                <!-- 1. Température CPU (Critique Raspberry Pi) -->
                <div class="col-sm-6 col-lg-4 col-xl">
                    <div class="card card-sm border shadow-none h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-temperature-three-quarters text-danger me-1"></i>Température CPU
                                </span>
                                <span id="rpi-cpu-temp-badge" class="badge bg-<?= $serverDiag['cpu']['thermal_class'] ?>-lt font-monospace small">
                                    <span id="rpi-cpu-temp-status"><?= $serverDiag['cpu']['thermal_label'] ?></span>
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-2 mb-2">
                                <div id="rpi-cpu-temp" class="h1 m-0 fw-bold font-monospace text-<?= $serverDiag['cpu']['thermal_class'] ?>">
                                    <?= $serverDiag['cpu']['temperature'] !== null ? $serverDiag['cpu']['temperature'] . ' °C' : 'N/A' ?>
                                </div>
                                <span class="text-muted small" style="font-size:0.72rem;">Alerte &ge; 75°C</span>
                            </div>
                            <div class="progress progress-sm mb-1.5">
                                <div id="rpi-cpu-temp-bar" class="progress-bar bg-<?= $serverDiag['cpu']['thermal_class'] ?>" 
                                     style="width: <?= min(100, (($serverDiag['cpu']['temperature'] ?? 40) / 90) * 100) ?>%"></div>
                            </div>
                            <div class="text-muted small d-flex justify-content-between font-monospace" style="font-size:0.7rem;">
                                <span>Refroidissement</span>
                                <span>Throttling : 80°C</span>
                            </div>
                            <!-- Mini-graphique historique : Température -->
                            <div id="rpi-spark-temp" class="rpi-sparkline mt-2"></div>
                            <div class="d-flex justify-content-between text-muted font-monospace" style="font-size:0.66rem;">
                                <span>~20 min</span>
                                <span id="rpi-spark-temp-range">min -- &middot; max --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Charge Processeur (Load Average & Fréquence) -->
                <div class="col-sm-6 col-lg-4 col-xl">
                    <div class="card card-sm border shadow-none h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-microchip text-primary me-1"></i>Charge Système
                                </span>
                                <span class="badge bg-primary-lt font-monospace small">
                                    <span id="rpi-cpu-cores"><?= $serverDiag['cpu']['cores'] ?></span> Cœur(s) &bull; <span id="rpi-cpu-freq"><?= $serverDiag['cpu']['frequency_mhz'] ?? 'auto' ?></span> MHz
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-2 mb-2">
                                <div id="rpi-cpu-load" class="h1 m-0 fw-bold font-monospace text-dark">
                                    <?= $serverDiag['cpu']['load_1m'] ?>
                                </div>
                                <span class="text-muted small font-monospace">
                                    (<span id="rpi-cpu-load-pct"><?= $serverDiag['cpu']['load_percent'] ?></span>%)
                                </span>
                            </div>
                            <div class="progress progress-sm mb-1.5">
                                <div id="rpi-cpu-load-bar" class="progress-bar bg-primary" 
                                     style="width: <?= min(100, $serverDiag['cpu']['load_percent']) ?>%"></div>
                            </div>
                            <div class="text-muted small d-flex justify-content-between font-monospace" style="font-size:0.7rem;">
                                <span>5m : <strong id="rpi-cpu-load-5m"><?= $serverDiag['cpu']['load_5m'] ?></strong></span>
                                <span>15m : <strong id="rpi-cpu-load-15m"><?= $serverDiag['cpu']['load_15m'] ?></strong></span>
                            </div>
                            <!-- Mini-graphique historique : Charge -->
                            <div id="rpi-spark-load" class="rpi-sparkline mt-2"></div>
                            <div class="d-flex justify-content-between text-muted font-monospace" style="font-size:0.66rem;">
                                <span>~20 min</span>
                                <span id="rpi-spark-load-range">min -- &middot; max --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Mémoire Vive & Swap -->
                <div class="col-sm-6 col-lg-4 col-xl">
                    <div class="card card-sm border shadow-none h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-memory text-teal me-1"></i>Mémoire RAM
                                </span>
                                <span id="rpi-ram-badge" class="badge bg-<?= $serverDiag['memory']['ram_status_class'] ?>-lt font-monospace small">
                                    <span id="rpi-ram-pct"><?= $serverDiag['memory']['ram_percent'] ?></span>% utilisé
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-1 mb-2">
                                <div id="rpi-ram-used" class="h1 m-0 fw-bold font-monospace text-dark">
                                    <?= number_format($serverDiag['memory']['ram_used_mb'] / 1024, 1) ?>
                                </div>
                                <span class="text-muted font-monospace fs-5">/ <span id="rpi-ram-total"><?= number_format($serverDiag['memory']['ram_total_mb'] / 1024, 1) ?> Go</span></span>
                            </div>
                            <div class="progress progress-sm mb-1.5">
                                <div id="rpi-ram-bar" class="progress-bar bg-<?= $serverDiag['memory']['ram_status_class'] ?>" 
                                     style="width: <?= $serverDiag['memory']['ram_percent'] ?>%"></div>
                            </div>
                            <div class="text-muted small d-flex justify-content-between font-monospace" style="font-size:0.7rem;">
                                <span>Swap : <strong id="rpi-swap-used"><?= number_format($serverDiag['memory']['swap_used_mb'], 0) ?> Mo</strong> (<span id="rpi-swap-pct"><?= $serverDiag['memory']['swap_percent'] ?></span>%)</span>
                                <span class="<?= $serverDiag['memory']['swap_warning'] ? 'text-danger fw-bold' : '' ?>" id="rpi-swap-status">
                                    <?= $serverDiag['memory']['swap_warning'] ? '⚠️ Swap Actif' : 'Nominal' ?>
                                </span>
                            </div>
                            <!-- Mini-graphique historique : RAM -->
                            <div id="rpi-spark-ram" class="rpi-sparkline mt-2"></div>
                            <div class="d-flex justify-content-between text-muted font-monospace" style="font-size:0.66rem;">
                                <span>~20 min</span>
                                <span id="rpi-spark-ram-range">min -- &middot; max --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Espace Disque SD / SSD -->
                <div class="col-sm-6 col-lg-4 col-xl">
                    <div class="card card-sm border shadow-none h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-hard-drive text-indigo me-1"></i>Stockage Hôte (/)
                                </span>
                                <span id="rpi-disk-badge" class="badge bg-<?= $serverDiag['disk']['status_class'] ?>-lt font-monospace small">
                                    <span id="rpi-disk-pct"><?= $serverDiag['disk']['used_percent'] ?></span>% plein
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-1 mb-2">
                                <div id="rpi-disk-free" class="h1 m-0 fw-bold font-monospace text-dark">
                                    <?= $serverDiag['disk']['free_gb'] ?> Go
                                </div>
                                <span class="text-muted small">libres sur <span id="rpi-disk-total"><?= $serverDiag['disk']['total_gb'] ?> Go</span></span>
                            </div>
                            <div class="progress progress-sm mb-1.5">
                                <div id="rpi-disk-bar" class="progress-bar bg-<?= $serverDiag['disk']['status_class'] ?>" 
                                     style="width: <?= $serverDiag['disk']['used_percent'] ?>%"></div>
                            </div>
                            <div class="text-muted small d-flex justify-content-between font-monospace" style="font-size:0.7rem;">
                                <span>Utilisé : <strong id="rpi-disk-used"><?= $serverDiag['disk']['used_gb'] ?> Go</strong></span>
                                <span>Carte SD / SSD</span>
                            </div>
                            <!-- Mini-graphique historique : Stockage -->
                            <div id="rpi-spark-disk" class="rpi-sparkline mt-2"></div>
                            <div class="d-flex justify-content-between text-muted font-monospace" style="font-size:0.66rem;">
                                <span>~20 min</span>
                                <span id="rpi-spark-disk-range">min -- &middot; max --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. NOUVEL INDICATEUR : Trafic Réseau Hôte (Rx / Tx) -->
                <div class="col-sm-6 col-lg-4 col-xl">
                    <div class="card card-sm border shadow-none h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-network-wired text-azure me-1"></i>Trafic Réseau
                                </span>
                                <span id="rpi-net-badge" class="badge bg-azure-lt font-monospace small" title="<?= htmlspecialchars($serverDiag['network']['primary_type'] ?? '') ?>">
                                    <span id="rpi-net-iface"><?= htmlspecialchars($serverDiag['network']['primary_iface'] ?? 'eth0') ?></span>
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-2 mb-2">
                                <div id="rpi-net-speed-total" class="h1 m-0 fw-bold font-monospace text-azure">
                                    <span id="rpi-net-rx-speed"><?= $serverDiag['network']['rx_speed_kbps'] ?? 0 ?></span> <span class="fs-4 text-muted fw-normal">Ko/s</span>
                                </div>
                                <span class="text-muted small font-monospace" title="Débit montant (Tx)">
                                    &uarr; <span id="rpi-net-tx-speed"><?= $serverDiag['network']['tx_speed_kbps'] ?? 0 ?></span> Ko/s
                                </span>
                            </div>
                            <div class="progress progress-sm mb-1.5">
                                <div id="rpi-net-bar" class="progress-bar bg-azure" style="width: <?= min(100, max(5, (($serverDiag['network']['rx_speed_kbps'] ?? 0) / 500) * 100)) ?>%"></div>
                            </div>
                            <div class="text-muted small d-flex justify-content-between font-monospace" style="font-size:0.7rem;">
                                <span>&darr; <strong id="rpi-net-rx"><?= $serverDiag['network']['total_rx_mb'] ?? 0 ?> Mo</strong></span>
                                <span>&uarr; <strong id="rpi-net-tx"><?= $serverDiag['network']['total_tx_mb'] ?? 0 ?> Mo</strong></span>
                            </div>
                            <!-- Mini-graphique historique : Réseau -->
                            <div id="rpi-spark-net" class="rpi-sparkline mt-2"></div>
                            <div class="d-flex justify-content-between text-muted font-monospace" style="font-size:0.66rem;">
                                <span>~20 min</span>
                                <span id="rpi-spark-net-range">min -- &middot; max --</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Synthèse Technique des Services & Pile Logicielle (4 Blocs sur une seule ligne) ── -->
            <div class="row g-2 g-md-3 font-monospace small mb-3 flex-nowrap overflow-x-auto">
                <!-- 1. Base MySQL -->
                <div class="col-3" style="min-width: 200px;">
                    <div class="rounded border bg-light d-flex justify-content-between align-items-center h-100 shadow-none" style="padding: 0.85rem 1rem;">
                        <div class="text-truncate me-2">
                            <span class="text-muted d-block small text-truncate" style="font-size:0.75rem;"><i class="fa-solid fa-database text-primary me-1"></i>Base de Données SQL</span>
                            <div class="d-flex align-items-center gap-1.5 mt-1">
                                <strong id="rpi-db-latency" class="text-<?= $serverDiag['database']['latency_class'] ?> fs-6">
                                    <?= $serverDiag['database']['latency_ms'] ?> ms
                                </strong>
                                <span class="text-muted small">&bull; <span id="rpi-db-threads"><?= $serverDiag['database']['threads_active'] ?></span> conn.</span>
                            </div>
                        </div>
                        <span class="badge bg-primary-lt px-2 py-1 flex-shrink-0" id="rpi-db-size"><?= $serverDiag['database']['db_size_mb'] ?> Mo</span>
                    </div>
                </div>

                <!-- 2. PHP & OPcache -->
                <div class="col-3" style="min-width: 200px;">
                    <div class="rounded border bg-light d-flex justify-content-between align-items-center h-100 shadow-none" style="padding: 0.85rem 1rem;">
                        <div class="text-truncate me-2">
                            <span class="text-muted d-block small text-truncate" style="font-size:0.75rem;"><i class="fa-brands fa-php text-purple me-1"></i>Moteur PHP &amp; OPcache</span>
                            <div class="mt-1 text-truncate">
                                <strong class="text-dark">v<?= $serverDiag['php']['version'] ?></strong>
                                <span class="text-muted small">(<?= $serverDiag['php']['memory_limit'] ?>)</span>
                            </div>
                        </div>
                        <span class="badge bg-success-lt px-2 py-1 flex-shrink-0" id="rpi-opcache-badge" title="Taux de succès du cache d'opcodes">
                            <span id="rpi-opcache-hit"><?= $serverDiag['php']['opcache_hit_rate'] ?></span>% Hit
                        </span>
                    </div>
                </div>

                <!-- 3. Simulation & Game Loop -->
                <div class="col-3" style="min-width: 200px;">
                    <div class="rounded border bg-light d-flex justify-content-between align-items-center h-100 shadow-none" style="padding: 0.85rem 1rem;">
                        <div class="text-truncate me-2">
                            <span class="text-muted d-block small text-truncate" style="font-size:0.75rem;"><i class="fa-solid fa-gear text-secondary me-1"></i>Simulation IA &amp; Ticks</span>
                            <div class="mt-1 text-truncate">
                                <strong class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Active</strong>
                                <span class="text-muted small">&bull; <?= (int)($totalBots ?? 0) ?> bots</span>
                            </div>
                        </div>
                        <span class="badge bg-secondary-lt font-monospace px-2 py-1 flex-shrink-0">Auto</span>
                    </div>
                </div>

                <!-- 4. Déploiement Git -->
                <div class="col-3" style="min-width: 200px;">
                    <div class="rounded border bg-light d-flex justify-content-between align-items-center h-100 shadow-none" style="padding: 0.85rem 1rem;">
                        <div class="text-truncate me-2">
                            <span class="text-muted d-block small text-truncate" style="font-size:0.75rem;"><i class="fa-solid fa-code-branch text-teal me-1"></i>Version Déployée (Git)</span>
                            <div class="mt-1 text-truncate">
                                <strong class="text-teal font-monospace"><?= htmlspecialchars($localGitInfo['branch'] ?? 'main') ?></strong>
                                <span class="text-muted small">(<?= htmlspecialchars($localGitInfo['short_sha'] ?? 'HEAD') ?>)</span>
                            </div>
                        </div>
                        <span class="badge bg-teal-lt px-2 py-1 flex-shrink-0">Synchronisé</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

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

    <!-- ── ZONE ACTIVITÉ RÉCENTE (PLEINE LARGEUR) ── -->
    <div class="row row-cards mb-4">
        <!-- Journal des Dernières Activités -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bolt text-warning me-1"></i>Inscriptions &amp; Activités Récentes des Daimyōs
                    </h4>
                    <span class="badge bg-secondary-lt font-monospace"><?= count($recentUsers ?? []) ?> derniers événements</span>
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
    </div>
</div>

<!-- Script CDN ApexCharts pour les visualisations interactives du Dashboard -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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
    renderCanvasTrend();
    renderCanvasTopPages();
    renderCanvasHourly();
}

function renderCanvasTrend() {
    const canvas = document.getElementById('adminTrendChart');
    const container = document.getElementById('adminApexTrendChart');
    const fallback = document.getElementById('adminTrendChartFallback');
    if (!canvas) return;
    if (fallback) fallback.style.display = 'block';
    if (container) container.style.display = 'none';

    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const parentW = canvas.parentElement ? canvas.parentElement.clientWidth : 600;
    const w = Math.max(300, parentW || 600);
    const h = 260;

    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.scale(dpr, dpr);

    const padLeft = 40;
    const padRight = 20;
    const padTop = 20;
    const padBottom = 30;

    const data = telemetryData.timeline;
    if (!data || data.length === 0) return;

    const maxVal = Math.max(10, ...data.map(d => Number(d.views) || 0), ...data.map(d => Number(d.unique_users) || 0), ...data.map(d => Number(d.registrations) || 0)) * 1.15;

    ctx.clearRect(0, 0, w, h);

    // Grille horizontale
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.2)';
    ctx.lineWidth = 1;
    ctx.fillStyle = '#94a3b8';
    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    const steps = 4;
    for (let i = 0; i <= steps; i++) {
        const y = padTop + (h - padTop - padBottom) * (1 - i / steps);
        ctx.beginPath();
        ctx.moveTo(padLeft, y);
        ctx.lineTo(w - padRight, y);
        ctx.stroke();
        ctx.fillText(Math.round((maxVal * i) / steps), 10, y + 3);
    }

    const chartW = w - padLeft - padRight;
    const chartH = h - padTop - padBottom;
    const stepX = chartW / Math.max(1, data.length - 1);

    function drawLineSeries(key, strokeCol, fillCol) {
        const points = [];
        data.forEach((d, idx) => {
            const x = padLeft + idx * stepX;
            const y = padTop + chartH * (1 - ((Number(d[key]) || 0) / maxVal));
            points.push({x, y});
        });

        ctx.fillStyle = fillCol;
        ctx.beginPath();
        ctx.moveTo(points[0].x, padTop + chartH);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(points[points.length - 1].x, padTop + chartH);
        ctx.closePath();
        ctx.fill();

        ctx.strokeStyle = strokeCol;
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        points.forEach((p, idx) => {
            if (idx === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.stroke();

        ctx.fillStyle = strokeCol;
        points.forEach((p, idx) => {
            if (idx % 3 === 0 || idx === points.length - 1) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                ctx.fill();
            }
        });
    }

    drawLineSeries('views', '#206bc4', 'rgba(32, 107, 196, 0.12)');
    drawLineSeries('unique_users', '#2fb344', 'rgba(47, 179, 68, 0.08)');
    drawLineSeries('registrations', '#f59f00', 'rgba(245, 159, 0, 0.08)');

    ctx.fillStyle = '#64748b';
    data.forEach((d, idx) => {
        if (idx % Math.max(1, Math.ceil(data.length / 8)) === 0 || idx === data.length - 1) {
            const x = padLeft + idx * stepX;
            ctx.fillText(String(d.label || ''), x - 12, h - 8);
        }
    });
}

function renderCanvasTopPages() {
    const canvas = document.getElementById('adminTopPagesChart');
    const container = document.getElementById('adminApexTopPagesChart');
    const fallback = document.getElementById('adminTopPagesFallback');
    if (!canvas) return;
    if (fallback) fallback.style.display = 'block';
    if (container) container.style.display = 'none';

    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const parentW = canvas.parentElement ? canvas.parentElement.clientWidth : 400;
    const w = Math.max(300, parentW || 400);
    const h = 260;

    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.scale(dpr, dpr);

    ctx.clearRect(0, 0, w, h);

    const items = (telemetryData.topPages || []).slice(0, 8);
    if (!items || items.length === 0) return;

    const maxVal = Math.max(5, ...items.map(i => Number(i.hit_count) || 0)) * 1.15;
    const labelW = 140;
    const padRight = 50;
    const padTop = 15;
    const barAreaW = Math.max(50, w - labelW - padRight);
    const barH = 20;
    const gap = 10;

    ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    items.forEach((item, idx) => {
        const y = padTop + idx * (barH + gap);
        const val = Number(item.hit_count) || 0;
        const barW = Math.max(4, (val / maxVal) * barAreaW);

        // Label
        ctx.fillStyle = '#475569';
        ctx.textAlign = 'right';
        ctx.fillText(String(item.label || '').substring(0, 20), labelW - 10, y + 14);

        // Background barre
        ctx.fillStyle = 'rgba(66, 153, 225, 0.12)';
        ctx.beginPath();
        ctx.roundRect ? ctx.roundRect(labelW, y, barAreaW, barH, 4) : ctx.rect(labelW, y, barAreaW, barH);
        ctx.fill();

        // Barre active
        ctx.fillStyle = '#4299e1';
        ctx.beginPath();
        ctx.roundRect ? ctx.roundRect(labelW, y, barW, barH, 4) : ctx.rect(labelW, y, barW, barH);
        ctx.fill();

        // Valeur
        ctx.fillStyle = '#1e293b';
        ctx.textAlign = 'left';
        ctx.fillText(val + ' v.', labelW + barW + 8, y + 14);
    });
}

function renderCanvasHourly() {
    const canvas = document.getElementById('adminHourlyChart');
    const container = document.getElementById('adminApexHourlyChart');
    const fallback = document.getElementById('adminHourlyFallback');
    if (!canvas) return;
    if (fallback) fallback.style.display = 'block';
    if (container) container.style.display = 'none';

    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const parentW = canvas.parentElement ? canvas.parentElement.clientWidth : 400;
    const w = Math.max(300, parentW || 400);
    const h = 260;

    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.scale(dpr, dpr);

    ctx.clearRect(0, 0, w, h);

    const data = telemetryData.hourly;
    if (!data || data.length === 0) return;

    const maxVal = Math.max(5, ...data.map(v => Number(v) || 0)) * 1.2;
    const padLeft = 30;
    const padRight = 15;
    const padTop = 20;
    const padBottom = 25;
    const chartW = w - padLeft - padRight;
    const chartH = h - padTop - padBottom;
    const colW = (chartW / 24) * 0.7;
    const stepX = chartW / 24;

    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

    // Grille 3 étapes
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.15)';
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 3; i++) {
        const y = padTop + chartH * (1 - i / 3);
        ctx.beginPath();
        ctx.moveTo(padLeft, y);
        ctx.lineTo(w - padRight, y);
        ctx.stroke();
        ctx.fillText(Math.round((maxVal * i) / 3), padLeft - 6, y + 3);
    }

    // Colonnes
    data.forEach((val, hour) => {
        const v = Number(val) || 0;
        const barH = (v / maxVal) * chartH;
        const x = padLeft + hour * stepX + (stepX - colW) / 2;
        const y = padTop + chartH - barH;

        ctx.fillStyle = '#f59f00';
        ctx.beginPath();
        ctx.roundRect ? ctx.roundRect(x, y, colW, barH, [3, 3, 0, 0]) : ctx.rect(x, y, colW, barH);
        ctx.fill();

        if (hour % 3 === 0) {
            ctx.fillStyle = '#64748b';
            ctx.textAlign = 'center';
            ctx.fillText(hour + 'h', x + colW / 2, h - 8);
        }
    });
}

// Initialisation au chargement du DOM et vérification périodique d'ApexCharts
function initDashboardCharts() {
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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}

window.addEventListener('load', requestRenderAnalyticsCharts);
window.addEventListener('resize', requestRenderAnalyticsCharts);

// Retenter si ApexCharts finit de se charger en asynchrone
if (typeof ApexCharts === 'undefined') {
    let checkApexAttempts = 0;
    const checkApexInterval = setInterval(() => {
        checkApexAttempts++;
        if (typeof ApexCharts !== 'undefined') {
            clearInterval(checkApexInterval);
            requestRenderAnalyticsCharts();
        } else if (checkApexAttempts >= 10) {
            clearInterval(checkApexInterval);
            renderCanvasFallbackCharts();
        }
    }, 200);
}

// =========================================================================
// MOTEUR DE SUPERVISION TEMPS RÉEL RASPBERRY PI (LIVE POLLING & ACTIONS)
// =========================================================================
let rpiLiveInterval = null;
let rpiLiveActive = true;

async function fetchRpiMetrics(isManual = false) {
    try {
        const res = await fetch('/api/server_monitor.php?action=metrics');
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success || !json.data) return;

        const d = json.data;

        // 1. Matériel & Uptime
        if (d.hardware) {
            const mEl = document.getElementById('rpi-model-name');
            if (mEl && d.hardware.model) mEl.textContent = d.hardware.model;
            const osEl = document.getElementById('rpi-os-name');
            if (osEl && d.hardware.os) osEl.textContent = `${d.hardware.os} (${d.hardware.arch})`;
            const coresEl = document.getElementById('rpi-cpu-cores');
            if (coresEl) coresEl.textContent = d.hardware.cores || 1;
        }
        if (d.uptime && d.uptime.formatted) {
            const upEl = document.getElementById('rpi-uptime-str');
            if (upEl) upEl.textContent = d.uptime.formatted;
        }

        // 2. Score de santé global
        if (d.health_score) {
            const hBadge = document.getElementById('rpi-health-badge');
            const hScore = document.getElementById('rpi-health-score');
            const hLabel = document.getElementById('rpi-health-label');
            if (hBadge && hScore && hLabel) {
                hScore.textContent = `${d.health_score.score}%`;
                hLabel.textContent = d.health_score.label;
                hBadge.className = `badge bg-${d.health_score.class}-lt px-3 py-2 fs-6`;
            }
        }

        // 3. Température CPU
        if (d.cpu) {
            const tempEl = document.getElementById('rpi-cpu-temp');
            const tempBar = document.getElementById('rpi-cpu-temp-bar');
            const tempStat = document.getElementById('rpi-cpu-temp-status');
            const tempBadge = document.getElementById('rpi-cpu-temp-badge');

            if (tempEl) {
                tempEl.textContent = d.cpu.temperature !== null ? `${d.cpu.temperature} °C` : 'N/A';
                tempEl.className = `h1 m-0 fw-bold font-monospace text-${d.cpu.thermal_class}`;
            }
            if (tempBar) {
                const pct = Math.min(100, ((d.cpu.temperature || 40) / 90) * 100);
                tempBar.style.width = `${pct}%`;
                tempBar.className = `progress-bar bg-${d.cpu.thermal_class}`;
            }
            if (tempStat) tempStat.textContent = d.cpu.thermal_label;
            if (tempBadge) tempBadge.className = `badge bg-${d.cpu.thermal_class}-lt font-monospace small`;

            // Charge CPU
            const loadEl = document.getElementById('rpi-cpu-load');
            const loadPctEl = document.getElementById('rpi-cpu-load-pct');
            const loadBar = document.getElementById('rpi-cpu-load-bar');
            const load5m = document.getElementById('rpi-cpu-load-5m');
            const load15m = document.getElementById('rpi-cpu-load-15m');

            if (loadEl) loadEl.textContent = d.cpu.load_1m;
            if (loadPctEl) loadPctEl.textContent = d.cpu.load_percent;
            if (loadBar) loadBar.style.width = `${Math.min(100, d.cpu.load_percent)}%`;
            if (load5m) load5m.textContent = d.cpu.load_5m;
            if (load15m) load15m.textContent = d.cpu.load_15m;
        }

        // 4. Mémoire RAM & Swap
        if (d.memory) {
            const rUsed = document.getElementById('rpi-ram-used');
            const rTotal = document.getElementById('rpi-ram-total');
            const rPct = document.getElementById('rpi-ram-pct');
            const rBar = document.getElementById('rpi-ram-bar');
            const rBadge = document.getElementById('rpi-ram-badge');

            if (rUsed) rUsed.textContent = (d.memory.ram_used_mb / 1024).toFixed(1);
            if (rTotal) rTotal.textContent = `${(d.memory.ram_total_mb / 1024).toFixed(1)} Go`;
            if (rPct) rPct.textContent = d.memory.ram_percent;
            if (rBar) {
                rBar.style.width = `${d.memory.ram_percent}%`;
                rBar.className = `progress-bar bg-${d.memory.ram_status_class}`;
            }
            if (rBadge) rBadge.className = `badge bg-${d.memory.ram_status_class}-lt font-monospace small`;

            const sUsed = document.getElementById('rpi-swap-used');
            const sPct = document.getElementById('rpi-swap-pct');
            const sStat = document.getElementById('rpi-swap-status');
            if (sUsed) sUsed.textContent = `${Math.round(d.memory.swap_used_mb)} Mo`;
            if (sPct) sPct.textContent = d.memory.swap_percent;
            if (sStat) {
                sStat.textContent = d.memory.swap_warning ? '⚠️ Swap Actif' : 'Nominal';
                sStat.className = d.memory.swap_warning ? 'text-danger fw-bold' : '';
            }
        }

        // 5. Stockage SD / SSD
        if (d.disk) {
            const dFree = document.getElementById('rpi-disk-free');
            const dTotal = document.getElementById('rpi-disk-total');
            const dUsed = document.getElementById('rpi-disk-used');
            const dPct = document.getElementById('rpi-disk-pct');
            const dBar = document.getElementById('rpi-disk-bar');
            const dBadge = document.getElementById('rpi-disk-badge');

            if (dFree) dFree.textContent = `${d.disk.free_gb} Go`;
            if (dTotal) dTotal.textContent = `${d.disk.total_gb} Go`;
            if (dUsed) dUsed.textContent = `${d.disk.used_gb} Go`;
            if (dPct) dPct.textContent = d.disk.used_percent;
            if (dBar) {
                dBar.style.width = `${d.disk.used_percent}%`;
                dBar.className = `progress-bar bg-${d.disk.status_class}`;
            }
            if (dBadge) dBadge.className = `badge bg-${d.disk.status_class}-lt font-monospace small`;
        }

        // 6. Base SQL & OPcache
        if (d.database) {
            const dbLat = document.getElementById('rpi-db-latency');
            const dbThr = document.getElementById('rpi-db-threads');
            const dbSz = document.getElementById('rpi-db-size');
            if (dbLat) {
                dbLat.textContent = `${d.database.latency_ms} ms`;
                dbLat.className = `text-${d.database.latency_class} fs-6`;
            }
            if (dbThr) dbThr.textContent = d.database.threads_active;
            if (dbSz) dbSz.textContent = `${d.database.db_size_mb} Mo`;
        }

        if (d.php) {
            const opHit = document.getElementById('rpi-opcache-hit');
            if (opHit) opHit.textContent = d.php.opcache_hit_rate;
        }

        // 7. NOUVEL INDICATEUR : Trafic Réseau & Débit Rx/Tx
        if (d.network) {
            const netRxSpeed = document.getElementById('rpi-net-rx-speed');
            const netTxSpeed = document.getElementById('rpi-net-tx-speed');
            const netRx = document.getElementById('rpi-net-rx');
            const netTx = document.getElementById('rpi-net-tx');
            const netIface = document.getElementById('rpi-net-iface');
            const netBar = document.getElementById('rpi-net-bar');
            const netBadge = document.getElementById('rpi-net-badge');

            if (netRxSpeed) netRxSpeed.textContent = d.network.rx_speed_kbps ?? 0;
            if (netTxSpeed) netTxSpeed.textContent = d.network.tx_speed_kbps ?? 0;
            if (netRx) netRx.textContent = `${d.network.total_rx_mb ?? 0} Mo`;
            if (netTx) netTx.textContent = `${d.network.total_tx_mb ?? 0} Mo`;
            if (netIface) netIface.textContent = d.network.primary_iface ?? 'eth0';
            if (netBadge && d.network.primary_type) netBadge.title = d.network.primary_type;
            if (netBar) {
                const totalSpd = (d.network.rx_speed_kbps ?? 0) + (d.network.tx_speed_kbps ?? 0);
                netBar.style.width = `${Math.min(100, Math.max(5, (totalSpd / 500) * 100))}%`;
            }
        }

        // 8. Mini-graphiques : ajout du point courant (glissement en temps réel)
        pushRpiSparkPoint(rpiDiagToPoint(d));

        // Animation clignotement subtil du témoin Live
        const dot = document.getElementById('rpi-live-dot');
        if (dot) {
            dot.style.opacity = '0.3';
            setTimeout(() => { dot.style.opacity = '1'; }, 200);
        }
    } catch (e) {
        console.warn('Erreur monitoring Raspberry Pi:', e);
    }
}

// ==========================================================
// MINI-GRAPHIQUES (SPARKLINES) PAR INDICATEUR MATÉRIEL
// ==========================================================
const RPI_SPARK_MAX_POINTS = 40;
const rpiSparkBuffer = []; // { t, temp, load, ram, disk, rx, tx }
const rpiSparkCharts = {};
const RPI_SPARK_DEFS = {
    temp: { color: ['#ef4444'], unit: '°C', fields: ['temp'], names: ['Température'], max: null, threshold: 75 },
    load: { color: ['#3b82f6'], unit: '%',  fields: ['load'], names: ['Charge CPU'],  max: 100 },
    ram:  { color: ['#0ca678'], unit: '%',  fields: ['ram'],  names: ['RAM'],         max: 100 },
    disk: { color: ['#6574cd'], unit: '%',  fields: ['disk'], names: ['Stockage'],    max: 100 },
    net:  { color: ['#4299e1', '#f59f00'], unit: 'Ko/s', fields: ['rx', 'tx'], names: ['↓ Réception', '↑ Émission'], max: null }
};

function rpiNum(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : null;
}

function rpiLogToPoint(l) {
    return {
        t: l.time_label || (l.datetime ? String(l.datetime).split(' ')[1] : ''),
        temp: rpiNum(l.cpu_temp),
        load: rpiNum(l.cpu_load_percent),
        ram: rpiNum(l.ram_percent),
        disk: rpiNum(l.disk_percent),
        rx: rpiNum(l.net_rx_speed_kbps) ?? 0,
        tx: rpiNum(l.net_tx_speed_kbps) ?? 0
    };
}

function rpiDiagToPoint(d) {
    return {
        t: new Date().toLocaleTimeString('fr-FR'),
        temp: rpiNum(d.cpu?.temperature),
        load: rpiNum(d.cpu?.load_percent),
        ram: rpiNum(d.memory?.ram_percent),
        disk: rpiNum(d.disk?.used_percent),
        rx: rpiNum(d.network?.rx_speed_kbps) ?? 0,
        tx: rpiNum(d.network?.tx_speed_kbps) ?? 0
    };
}

let rpiLastSparkPushMs = 0;
const RPI_SPARK_STEP_MS = 30000; // même pas que l'historisation serveur (30 s)

function pushRpiSparkPoint(point) {
    const now = Date.now();
    // Entre deux pas de 30 s, on met à jour le dernier point (queue « live »), sinon on ajoute un point
    if (rpiSparkBuffer.length && (now - rpiLastSparkPushMs) < RPI_SPARK_STEP_MS) {
        rpiSparkBuffer[rpiSparkBuffer.length - 1] = point;
    } else {
        rpiSparkBuffer.push(point);
        rpiLastSparkPushMs = now;
    }
    while (rpiSparkBuffer.length > RPI_SPARK_MAX_POINTS) rpiSparkBuffer.shift();
    renderRpiSparklines();
}

async function loadHardwareLogs() {
    try {
        const res = await fetch(`/api/server_monitor.php?action=logs&limit=${RPI_SPARK_MAX_POINTS}`);
        const json = await res.json();
        if (json.success && Array.isArray(json.logs)) {
            rpiSparkBuffer.length = 0;
            // L'API renvoie du plus récent au plus ancien : on remet dans l'ordre chronologique
            [...json.logs].reverse().forEach(l => rpiSparkBuffer.push(rpiLogToPoint(l)));
            renderRpiSparklines();
        }
    } catch (e) {
        console.warn('Erreur chargement historique matériel:', e);
    }
}

function updateRpiSparkRange(key, def) {
    const el = document.getElementById(`rpi-spark-${key}-range`);
    if (!el) return;
    const vals = [];
    def.fields.forEach(f => rpiSparkBuffer.forEach(p => { if (p[f] !== null) vals.push(p[f]); }));
    if (!vals.length) { el.innerHTML = 'min -- &middot; max --'; return; }
    const fmt = v => (Math.round(v * 10) / 10);
    el.innerHTML = `min ${fmt(Math.min(...vals))}${def.unit === '°C' ? '°' : ''} &middot; max ${fmt(Math.max(...vals))}${def.unit === '°C' ? '°' : (def.unit === '%' ? '%' : '')}`;
}

function renderRpiSvgFallback(container, def) {
    const w = container.clientWidth || 200, h = 56;
    let svg = `<svg width="100%" height="${h}" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none">`;
    def.fields.forEach((f, idx) => {
        const vals = rpiSparkBuffer.map(p => p[f] ?? 0);
        if (vals.length < 2) return;
        const maxV = def.max ?? Math.max(1, ...vals) * 1.15;
        const pts = vals.map((v, i) => `${(i / (vals.length - 1)) * w},${h - (v / maxV) * (h - 4) - 2}`).join(' ');
        svg += `<polyline fill="none" stroke="${def.color[idx]}" stroke-width="2" points="${pts}"/>`;
    });
    container.innerHTML = svg + '</svg>';
}

function renderRpiSparklines() {
    Object.entries(RPI_SPARK_DEFS).forEach(([key, def]) => {
        const container = document.getElementById(`rpi-spark-${key}`);
        if (!container) return;

        updateRpiSparkRange(key, def);

        const categories = rpiSparkBuffer.map(p => p.t);
        const series = def.fields.map((f, idx) => ({ name: def.names[idx], data: rpiSparkBuffer.map(p => p[f]) }));

        if (typeof ApexCharts === 'undefined') {
            renderRpiSvgFallback(container, def);
            return;
        }

        if (rpiSparkCharts[key]) {
            rpiSparkCharts[key].updateOptions({ xaxis: { categories } }, false, false);
            rpiSparkCharts[key].updateSeries(series, false);
            return;
        }

        const options = {
            chart: { type: 'area', height: 56, sparkline: { enabled: true }, animations: { enabled: false } },
            series,
            colors: def.color,
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
            xaxis: { categories },
            yaxis: { min: 0, max: def.max ?? undefined },
            tooltip: {
                fixed: { enabled: false },
                x: { show: true },
                y: { formatter: v => (v === null || v === undefined) ? 'N/A' : `${v} ${def.unit}` },
                marker: { show: true }
            }
        };
        if (def.threshold) {
            options.annotations = { yaxis: [{ y: def.threshold, borderColor: '#ef4444', strokeDashArray: 3, opacity: 0.6 }] };
        }

        container.innerHTML = '';
        rpiSparkCharts[key] = new ApexCharts(container, options);
        rpiSparkCharts[key].render();
    });
}

function toggleRpiLiveMonitoring() {
    const btn = document.getElementById('btn-toggle-rpi-live');
    const txt = document.getElementById('rpi-live-text');
    const dot = document.getElementById('rpi-live-dot');

    if (rpiLiveActive) {
        clearInterval(rpiLiveInterval);
        rpiLiveInterval = null;
        rpiLiveActive = false;
        if (btn) {
            btn.classList.remove('btn-outline-success', 'active');
            btn.classList.add('btn-outline-secondary');
        }
        if (txt) txt.textContent = 'En Pause';
        if (dot) dot.className = 'status-dot bg-secondary';
    } else {
        rpiLiveActive = true;
        if (btn) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-success', 'active');
        }
        if (txt) txt.textContent = 'Live 3s (Actif)';
        if (dot) dot.className = 'status-dot status-dot-animated bg-success';
        fetchRpiMetrics();
        rpiLiveInterval = setInterval(() => fetchRpiMetrics(), 3000);
    }
}

async function refreshRpiMetrics(btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>';
    }
    await fetchRpiMetrics(true);
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser';
    }
}

async function triggerServerPingDb(btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>';
    }
    try {
        const res = await fetch('/api/server_monitor.php?action=ping_db');
        const json = await res.json();
        if (typeof showModalAlert === 'function') {
            showModalAlert(json.message, json.success ? 'success' : 'error');
        } else {
            alert(json.message);
        }
        await fetchRpiMetrics();
    } catch (e) {
        alert("Erreur lors du test de réactivité SQL.");
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt text-warning me-1"></i>Ping SQL';
        }
    }
}

async function triggerServerResetOpcache(btn) {
    if (!confirm("Voulez-vous purger l'ensemble des scripts compilés en mémoire vive (OPcache) ?\nLes fichiers PHP seront rechargés à chaud immédiatement.")) {
        return;
    }
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Purge...';
    }
    try {
        const res = await fetch('/api/server_monitor.php?action=reset_opcache', { method: 'POST' });
        const json = await res.json();
        if (typeof showModalAlert === 'function') {
            showModalAlert(json.message, json.success ? 'success' : 'warning');
        } else {
            alert(json.message);
        }
        await fetchRpiMetrics();
    } catch (e) {
        alert("Erreur lors de la purge OPcache.");
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-broom text-info me-1"></i>Purge OPcache';
        }
    }
}

// Lancement automatique du Live Polling Raspberry Pi (3 secondes) & Historique Télémétrique
document.addEventListener('DOMContentLoaded', () => {
    if (rpiLiveActive) {
        rpiLiveInterval = setInterval(() => fetchRpiMetrics(), 3000);
    }
    // Chargement initial du graphique et des logs de télémétrie
    setTimeout(() => {
        loadHardwareLogs(true);
    }, 400);
});
</script>

