<?php
/**
 * Vue Partielle MVC : Visualisation & Diagnostic des Logs d'Erreur Apache
 * Fichier : views/gamestudio/general_apache_logs.php
 */
?>

<div class="apache-logs-container">

    <!-- ── 1. EN-TÊTE PRINCIPAL ── -->
    <div class="card bg-white border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-lg rounded bg-danger-lt text-danger fs-1 shadow-sm">
                        <i class="fa-solid fa-file-waveform"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="text-dark fw-bold m-0" style="font-size: 1.4rem;">
                                Journal des Erreurs Apache &amp; Serveur Web
                            </h2>
                            <?php if ($permissionDenied && empty($parsedEntries)): ?>
                                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Accès Restreint (Permissions OS)
                                </span>
                            <?php elseif ($logExists): ?>
                                <?php if (($sourceType ?? '') === 'journalctl'): ?>
                                    <span class="badge bg-info-lt fw-bold px-2.5 py-1">
                                        <i class="fa-solid fa-terminal me-1"></i>Via journalctl
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-lt fw-bold px-2.5 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i>Fichier En Ligne
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger-lt fw-bold px-2.5 py-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Fichier Non Trouvé
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-secondary small mt-1">
                            Diagnostic en direct des erreurs HTTP, avertissements des modules MPM/Core et anomalies PHP enregistrées par le serveur web.
                        </div>
                    </div>
                </div>

                <!-- Boutons d'Action Rapide -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5" onclick="window.location.reload();">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Rafraîchir</span>
                    </button>

                    <?php if ($logExists): ?>
                        <a href="?page=gamestudio&tab=general&subtab=apache_logs&download_log=1&log_file=<?= urlencode($logPath ?? '') ?>" 
                           class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1.5" title="Télécharger le fichier log brut">
                            <i class="fa-solid fa-download"></i>
                            <span>Télécharger</span>
                        </a>

                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5" onclick="copyLogsToClipboard()">
                            <i class="fa-solid fa-copy"></i>
                            <span id="copyLogsBtnText">Copier</span>
                        </button>

                        <?php if ($isWritable): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1.5" onclick="confirmClearLog()">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Vider le Log</span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. CARTES KPIS & INFORMATIONS TECHNIQUES ── -->
    <div class="row row-cards mb-4">
        <!-- KPI 1 : Fichier Actif & Sélecteur -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-danger shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Log Actif</span>
                        <span class="avatar avatar-xs rounded bg-danger-lt text-danger"><i class="fa-solid fa-file-code"></i></span>
                    </div>
                    <div class="fw-bold text-dark font-monospace text-truncate" title="<?= htmlspecialchars($logPath ?? 'Aucun') ?>">
                        <?= htmlspecialchars(basename($logPath ?? 'Non détecté')) ?>
                    </div>
                    <!-- Sélecteur d'autres logs détectés -->
                    <?php if (!empty($availableLogs)): ?>
                        <div class="mt-2 pt-2 border-top">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-ghost-secondary text-secondary w-100 py-0.5 px-1 d-flex justify-content-between align-items-center small" type="button" data-bs-toggle="dropdown">
                                    <span class="small text-truncate">Changer de log (<?= count($availableLogs) ?> dispo)</span>
                                    <i class="fa-solid fa-chevron-down ms-1" style="font-size:0.7rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small" style="max-height: 250px; overflow-y: auto;">
                                    <li class="dropdown-header text-uppercase small">Fichiers logs détectés :</li>
                                    <?php foreach ($availableLogs as $al): ?>
                                        <li>
                                            <a class="dropdown-item d-flex justify-content-between align-items-center gap-2 <?= $al['is_current'] ? 'active fw-bold' : '' ?>" 
                                               href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=<?= urlencode($al['path']) ?>">
                                                <span class="font-monospace"><?= htmlspecialchars($al['filename']) ?></span>
                                                <span class="badge bg-secondary-lt font-monospace"><?= $al['size_fmt'] ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KPI 2 : Poids & Emplacement -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-indigo shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Taille du Fichier</span>
                        <span class="avatar avatar-xs rounded bg-indigo-lt text-indigo"><i class="fa-solid fa-hard-drive"></i></span>
                    </div>
                    <div class="h2 m-0 font-weight-bold text-indigo font-monospace">
                        <?= $logSizeFmt ?? '0 Ko' ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top text-truncate" title="<?= htmlspecialchars($logMtimeFmt ?? '') ?>">
                        <i class="fa-regular fa-clock me-1"></i>Modifié : <?= htmlspecialchars($logMtimeFmt ?? 'Inconnue') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3 : Synthèse des Niveaux de Log -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Événements Lus</span>
                        <span class="avatar avatar-xs rounded bg-warning-lt text-warning"><i class="fa-solid fa-chart-pie"></i></span>
                    </div>
                    <div class="h2 m-0 font-weight-bold text-dark font-monospace">
                        <?= count($parsedEntries ?? []) ?> <span class="small text-secondary fw-normal">/ <?= $maxLines ?> lignes</span>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex gap-2 flex-wrap">
                        <span class="badge bg-danger-lt font-monospace" title="Critiques &amp; Erreurs"><?= ($stats['critical'] ?? 0) + ($stats['errors'] ?? 0) ?> Erreurs</span>
                        <span class="badge bg-warning-lt font-monospace" title="Avertissements"><?= $stats['warnings'] ?? 0 ?> Warn</span>
                        <span class="badge bg-cyan-lt font-monospace" title="Notices"><?= $stats['notices'] ?? 0 ?> Notice</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4 : Environnement Serveur -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start border-2 border-teal shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="text-secondary small fw-bold text-uppercase">Système &amp; Serveur</span>
                        <span class="avatar avatar-xs rounded bg-teal-lt text-teal"><i class="fa-solid fa-server"></i></span>
                    </div>
                    <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?>">
                        <?php if (!empty($isRaspberryPi)): ?>
                            <span class="text-danger"><i class="fa-brands fa-raspberry-pi me-1"></i><?= htmlspecialchars($piModel ?: 'Raspberry Pi OS') ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars(explode(' ', $_SERVER['SERVER_SOFTWARE'] ?? 'Apache HTTP Server')[0]) ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $isWritable ? 'success' : 'secondary' ?>-lt font-monospace">
                            <i class="fa-solid fa-<?= $isWritable ? 'pen-to-square' : 'lock' ?> me-1"></i><?= $isWritable ? 'Accessible en écriture' : 'Lecture seule' ?>
                        </span>
                        <span class="badge bg-dark-lt font-monospace" title="Utilisateur d'exécution PHP"><?= htmlspecialchars($permissionUser ?? 'www-data') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (($sourceType ?? '') === 'journalctl'): ?>
        <!-- Bandeau d'information quand les logs sont récupérés via journalctl -->
        <div class="alert alert-info border-0 shadow-sm mb-4 py-2.5 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-terminal text-info fs-3"></i>
                    <div>
                        <strong class="text-dark">Logs récupérés en direct via systemd journalctl</strong>
                        <div class="text-secondary small">
                            L'accès direct au fichier <code><?= htmlspecialchars($logPath ?? '/var/log/apache2/error.log') ?></code> nécessite les droits du groupe <code>adm</code>. Pour autoriser la lecture directe du fichier sans passer par journalctl :
                            <code class="user-select-all"><?= htmlspecialchars($permissionCommand ?? '') ?></code>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="copySnippetToClipboard('<?= htmlspecialchars($permissionCommand ?? '') ?>', this)">
                    <i class="fa-solid fa-copy me-1"></i>Copier la commande
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── 3. BARRE DE FILTRAGE & RECHERCHE EN DIRECT ── -->
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center" id="apacheLogsFilterForm">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="general">
                <input type="hidden" name="subtab" value="apache_logs">
                <?php if ($logPath): ?>
                    <input type="hidden" name="log_file" value="<?= htmlspecialchars($logPath) ?>">
                <?php endif; ?>

                <!-- Recherche textuelle instantanée -->
                <div class="col-md-4">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                        </span>
                        <input type="text" id="logFilterSearch" name="search" class="form-control form-control-sm" 
                               placeholder="Filtrer en direct (ex: AH00455, error, core, mpm...)" 
                               value="<?= htmlspecialchars($searchKeyword ?? '') ?>"
                               oninput="filterLogRowsRealtime(this.value)">
                    </div>
                </div>

                <!-- Filtre par Niveau -->
                <div class="col-6 col-md-3">
                    <select name="filter_level" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($filterLevel ?? 'all') === 'all' ? 'selected' : '' ?>>Tous les niveaux</option>
                        <option value="error" <?= ($filterLevel ?? '') === 'error' ? 'selected' : '' ?>>🔴 Erreurs &amp; Critiques seulement</option>
                        <option value="warning" <?= ($filterLevel ?? '') === 'warning' ? 'selected' : '' ?>>🟡 Avertissements (Warn)</option>
                        <option value="notice" <?= ($filterLevel ?? '') === 'notice' ? 'selected' : '' ?>>🔵 Notices &amp; Infos</option>
                    </select>
                </div>

                <!-- Nombre de Lignes -->
                <div class="col-6 col-md-2">
                    <select name="lines" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="50" <?= ($maxLines ?? 100) === 50 ? 'selected' : '' ?>>50 lignes</option>
                        <option value="100" <?= ($maxLines ?? 100) === 100 ? 'selected' : '' ?>>100 lignes</option>
                        <option value="250" <?= ($maxLines ?? 100) === 250 ? 'selected' : '' ?>>250 lignes</option>
                        <option value="500" <?= ($maxLines ?? 100) === 500 ? 'selected' : '' ?>>500 lignes</option>
                        <option value="1000" <?= ($maxLines ?? 100) === 1000 ? 'selected' : '' ?>>1 000 lignes</option>
                    </select>
                </div>

                <!-- Ordre d'affichage -->
                <div class="col-6 col-md-2">
                    <select name="order" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="desc" <?= ($order ?? 'desc') === 'desc' ? 'selected' : '' ?>>Plus récents en haut</option>
                        <option value="asc" <?= ($order ?? 'desc') === 'asc' ? 'selected' : '' ?>>Chronologique (asc)</option>
                    </select>
                </div>

                <!-- Toggle Vue Console / Vue Tableau -->
                <div class="col-6 col-md-1 text-end">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="btnViewStructured" onclick="switchLogView('structured')" title="Vue Tableau Structuré">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnViewTerminal" onclick="switchLogView('terminal')" title="Vue Console Terminal">
                            <i class="fa-solid fa-terminal"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── 4. CONTENU DU JOURNAL DE LOG ── -->
    <?php if ($permissionDenied && empty($parsedEntries)): ?>
        <!-- Diagnostic Permissions Raspberry Pi OS / Debian Linux -->
        <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #ef4444 !important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="avatar avatar-xl rounded bg-danger-lt text-danger fs-1 shadow-sm flex-shrink-0">
                        <i class="fa-brands fa-raspberry-pi"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                            <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                                <span>Fichier Log Détecté sur Raspberry Pi :</span>
                                <code class="text-danger"><?= htmlspecialchars($logPath ?? '/var/log/apache2/error.log') ?></code>
                            </h3>
                            <span class="badge bg-danger text-white fw-bold px-2 py-1">
                                <i class="fa-solid fa-lock me-1"></i>Permissions Système Restreintes (0750)
                            </span>
                        </div>
                        <p class="text-secondary small mb-3">
                            Sur <strong>Raspberry Pi OS (Debian)</strong>, le dossier <code>/var/log/apache2/</code> appartient par défaut à <code>root:adm</code> avec les droits <code>0750</code>. 
                            L'utilisateur web du serveur <strong><code><?= htmlspecialchars($permissionUser ?? 'www-data') ?></code></strong> n'est pas membre du groupe <code>adm</code> à l'installation, ce qui empêche PHP de lire le fichier de log d'erreurs Apache.
                        </p>

                        <!-- Commande SSH Recommandée (1 Seul Clic) -->
                        <div class="card bg-dark text-white border-0 shadow-sm mb-3">
                            <div class="card-header bg-dark border-bottom border-secondary py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="small font-monospace text-warning">
                                    <i class="fa-solid fa-terminal me-1"></i>Résolution en 1 commande SSH (Recommandée) :
                                </span>
                                <button type="button" class="btn btn-xs btn-outline-warning text-warning" onclick="copySnippetToClipboard('<?= htmlspecialchars($permissionCommand ?? '') ?>', this)">
                                    <i class="fa-solid fa-copy me-1"></i>Copier la commande
                                </button>
                            </div>
                            <div class="card-body p-3 font-monospace small" style="background:#0b0f19;">
                                <div class="text-secondary mb-1"># Ajouter l'utilisateur web au groupe système adm puis recharger Apache :</div>
                                <div class="text-warning user-select-all fs-4 fw-bold">
                                    $ <?= htmlspecialchars($permissionCommand ?? '') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Commande Alternative chmod -->
                        <div class="p-3 bg-light rounded border text-secondary small d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <strong>Alternative rapide (chmod) :</strong>
                                <code class="ms-1 user-select-all"><?= htmlspecialchars($permissionAlternative ?? '') ?></code>
                            </div>
                            <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="copySnippetToClipboard('<?= htmlspecialchars($permissionAlternative ?? '') ?>', this)">
                                <i class="fa-solid fa-copy me-1"></i>Copier le chmod
                            </button>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1.5" onclick="window.location.reload();">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>J'ai exécuté la commande &mdash; Recharger les logs</span>
                            </button>
                            <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=/var/log/apache2/other_vhosts_access.log" class="btn btn-outline-secondary btn-sm">
                                Tester other_vhosts_access.log
                            </a>
                            <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=/var/log/syslog" class="btn btn-outline-secondary btn-sm">
                                Tester /var/log/syslog
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif (!$logExists): ?>
        <!-- Fichier non détecté -->
        <div class="card border-warning shadow-sm">
            <div class="card-body p-4 text-center">
                <span class="avatar avatar-xl rounded-circle bg-warning-lt text-warning mb-3">
                    <i class="fa-solid fa-file-circle-question fs-1"></i>
                </span>
                <h3 class="fw-bold text-dark">Fichier Log Apache Introuvable</h3>
                <p class="text-secondary mx-auto mb-3" style="max-width: 580px;">
                    Aucun journal d'erreur Apache n'a été détecté automatiquement dans les chemins standards de Wamp, XAMPP ou Linux.
                </p>

                <!-- Raccourcis chemins courants -->
                <div class="mb-3 d-flex justify-content-center gap-2 flex-wrap">
                    <span class="small text-secondary align-self-center">Tester :</span>
                    <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=/var/log/apache2/error.log" class="btn btn-xs btn-outline-secondary font-monospace">/var/log/apache2/error.log</a>
                    <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=/var/log/apache2/other_vhosts_access.log" class="btn btn-xs btn-outline-secondary font-monospace">/var/log/apache2/other_vhosts_access.log</a>
                    <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=/var/log/syslog" class="btn btn-xs btn-outline-secondary font-monospace">/var/log/syslog</a>
                    <a href="?page=gamestudio&tab=general&subtab=apache_logs&log_file=C:/wamp64/logs/apache_error.log" class="btn btn-xs btn-outline-secondary font-monospace">C:/wamp64/logs/apache_error.log</a>
                </div>

                <form method="GET" action="" class="d-inline-flex gap-2 mx-auto" style="max-width: 500px;">
                    <input type="hidden" name="page" value="gamestudio">
                    <input type="hidden" name="tab" value="general">
                    <input type="hidden" name="subtab" value="apache_logs">
                    <input type="text" name="log_file" class="form-control font-monospace form-control-sm" placeholder="ex: /var/log/apache2/error.log ou C:/wamp64/logs/apache_error.log">
                    <button type="submit" class="btn btn-primary btn-sm">Inspecter</button>
                </form>
            </div>
        </div>
    <?php elseif (empty($parsedEntries)): ?>
        <!-- Fichier vide ou aucun filtre correspondant -->
        <div class="card border shadow-sm">
            <div class="card-body p-5 text-center">
                <span class="avatar avatar-xl rounded-circle bg-success-lt text-success mb-3">
                    <i class="fa-solid fa-circle-check fs-1"></i>
                </span>
                <h3 class="fw-bold text-dark">Aucune erreur Apache répertoriée</h3>
                <p class="text-secondary mx-auto mb-3" style="max-width: 500px;">
                    Le fichier de log est vide ou aucun événement ne correspond aux critères de filtre appliqués.
                </p>
                <a href="?page=gamestudio&tab=general&subtab=apache_logs" class="btn btn-outline-secondary btn-sm">
                    Réinitialiser les filtres
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- VUE 1 : STRUCTURED (Cartes / Lignes Tabler) -->
        <div id="logStructuredView" class="card border shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="small text-secondary fw-semibold">
                    <i class="fa-solid fa-list-check me-1"></i><span id="visibleRowsCount"><?= count($parsedEntries) ?></span> ligne(s) affichée(s) &bull; <?= htmlspecialchars($logPath ?? '') ?>
                </span>
                <span class="badge bg-secondary-lt text-secondary font-monospace" style="font-size:0.75rem;">
                    Format Apache 2.4 / PHP
                </span>
            </div>

            <div class="table-responsive" style="max-height: 680px; overflow-y: auto;">
                <table class="table table-vcenter table-hover table-striped mb-0 font-monospace" id="apacheLogsTable" style="font-size: 0.8rem;">
                    <thead class="sticky-top bg-light text-uppercase text-secondary" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        <tr>
                            <th style="width: 175px;">Date &amp; Heure</th>
                            <th style="width: 105px;">Niveau</th>
                            <th style="width: 130px;">Module / Code</th>
                            <th>Message &amp; Diagnostic</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parsedEntries as $idx => $entry): ?>
                            <tr class="log-row <?= htmlspecialchars($entry['row_class'] ?? '') ?>" data-search-text="<?= htmlspecialchars(strtolower($entry['raw'])) ?>">
                                <!-- Horodatage -->
                                <td class="text-nowrap text-secondary py-1.5" style="font-size: 0.75rem;">
                                    <i class="fa-regular fa-clock me-1 text-muted"></i><?= htmlspecialchars($entry['date'] ?: 'N/A') ?>
                                </td>

                                <!-- Niveau -->
                                <td class="py-1.5">
                                    <span class="badge <?= htmlspecialchars($entry['badge_class']) ?>" style="font-size: 0.68rem; letter-spacing: 0.3px;">
                                        <?= htmlspecialchars($entry['level_label'] ?? strtoupper($entry['level'])) ?>
                                    </span>
                                </td>

                                <!-- Module & Code AH -->
                                <td class="text-nowrap py-1.5" style="font-size: 0.75rem;">
                                    <?php if (!empty($entry['module'])): ?>
                                        <span class="text-indigo fw-bold"><?= htmlspecialchars($entry['module']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['code'])): ?>
                                        <span class="badge bg-dark-lt text-dark ms-1"><?= htmlspecialchars($entry['code']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['client'])): ?>
                                        <div class="text-muted small" style="font-size: 0.65rem;" title="Adresse Client IP">
                                            <i class="fa-solid fa-network-wired me-1"></i><?= htmlspecialchars($entry['client']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Message -->
                                <td class="py-1.5 text-break" style="line-height: 1.45;">
                                    <?= htmlspecialchars($entry['message']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VUE 2 : TERMINAL CONSOLE SOMBRE (Cachée par défaut) -->
        <div id="logTerminalView" class="card border-0 shadow-lg mb-4 overflow-hidden d-none" style="background: #0f172a; border-radius: 8px;">
            <div class="py-2 px-3 d-flex justify-content-between align-items-center border-bottom border-dark" style="background: #1e293b;">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #ef4444;"></span>
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #f59e0b;"></span>
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #10b981;"></span>
                    <span class="text-white-50 font-monospace small ms-2">apache_error.log &bull; Console Terminal</span>
                </div>
                <button type="button" class="btn btn-sm btn-ghost-light text-white py-0.5 px-2 font-monospace" onclick="copyTerminalText()">
                    <i class="fa-solid fa-copy me-1"></i>Copier Tout
                </button>
            </div>
            <div class="p-3 font-monospace text-light" style="max-height: 600px; overflow-y: auto; font-size: 0.78rem; line-height: 1.55;" id="terminalConsoleContent">
                <?php foreach ($parsedEntries as $i => $e): 
                    $color = '#94a3b8'; // standard gray
                    if (in_array($e['level'], ['emerg', 'alert', 'crit', 'fatal'])) $color = '#f87171'; // red
                    elseif (in_array($e['level'], ['error', 'err'])) $color = '#fb7185'; // rose
                    elseif (in_array($e['level'], ['warn', 'warning'])) $color = '#fbbf24'; // amber
                    elseif (in_array($e['level'], ['notice'])) $color = '#38bdf8'; // light blue
                ?>
                    <div class="terminal-line text-truncate" style="color: <?= $color ?>;" title="<?= htmlspecialchars($e['raw']) ?>">
                        <span class="text-white-50 select-none me-2"><?= str_pad((string)($i + 1), 3, ' ', STR_PAD_LEFT) ?> |</span>
                        <?= htmlspecialchars($e['raw']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- ── 5. FORMULAIRE CHEMIN PERSONNALISÉ (ACCORDÉON) ── -->
    <div class="card border shadow-sm">
        <div class="card-header bg-light py-2 px-3" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseCustomPath" aria-expanded="false">
            <span class="small text-secondary fw-semibold d-flex align-items-center gap-2">
                <i class="fa-solid fa-folder-gear text-indigo"></i>
                <span>Configuration avancée : Spécifier un chemin de log personnalisé</span>
                <i class="fa-solid fa-chevron-down ms-auto" style="font-size:0.75rem;"></i>
            </span>
        </div>
        <div class="collapse" id="collapseCustomPath">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="gamestudio">
                    <input type="hidden" name="tab" value="general">
                    <input type="hidden" name="subtab" value="apache_logs">

                    <div class="col-md-9">
                        <label class="form-label small text-secondary mb-1">Chemin absolu vers le fichier de log Apache :</label>
                        <input type="text" name="log_file" class="form-control form-control-sm font-monospace" 
                               placeholder="ex: C:/wamp64/logs/apache_error.log ou /var/log/apache2/error.log" 
                               value="<?= htmlspecialchars($logPath ?? '') ?>">
                    </div>
                    <div class="col-md-3 mt-md-4">
                        <button type="submit" class="btn btn-indigo btn-sm w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Charger ce log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- ── MODALE DE CONFIRMATION VIDAGE DE LOG ── -->
<div class="modal fade" id="modalConfirmClearLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Vider le Journal d'Erreur Apache
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="fs-4 text-dark mb-2">
                    Êtes-vous certain de vouloir vider le contenu du fichier de log suivant ?
                </p>
                <div class="p-2 bg-light rounded border font-monospace small text-danger text-break mb-3">
                    <?= htmlspecialchars($logPath ?? '') ?>
                </div>
                <div class="alert alert-warning small text-start py-2 mb-0">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>Cette opération effacera l'historique actuel pour ce fichier. Les nouvelles erreurs continueront d'être inscrites normalement.
                </div>
            </div>
            <div class="modal-footer border-top-0 d-flex justify-content-end gap-2 p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="?page=gamestudio&tab=general&subtab=apache_logs">
                    <input type="hidden" name="action" value="clear_apache_log">
                    <input type="hidden" name="log_file" value="<?= htmlspecialchars($logPath ?? '') ?>">
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="fa-solid fa-trash-can me-1"></i>Confirmer le Vidage
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bascule entre la Vue Tableau et la Vue Terminal
function switchLogView(mode) {
    const struct = document.getElementById('logStructuredView');
    const term = document.getElementById('logTerminalView');
    const btnStruct = document.getElementById('btnViewStructured');
    const btnTerm = document.getElementById('btnViewTerminal');

    if (mode === 'terminal') {
        if (struct) struct.classList.add('d-none');
        if (term) term.classList.remove('d-none');
        if (btnTerm) btnTerm.classList.add('active');
        if (btnStruct) btnStruct.classList.remove('active');
    } else {
        if (term) term.classList.add('d-none');
        if (struct) struct.classList.remove('d-none');
        if (btnStruct) btnStruct.classList.add('active');
        if (btnTerm) btnTerm.classList.remove('active');
    }
}

// Filtrage instantané en JavaScript sur les lignes visibles
function filterLogRowsRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#apacheLogsTable tbody tr.log-row');
    let visible = 0;

    rows.forEach(r => {
        const text = r.getAttribute('data-search-text') || '';
        if (q === '' || text.includes(q)) {
            r.style.display = '';
            visible++;
        } else {
            r.style.display = 'none';
        }
    });

    const cnt = document.getElementById('visibleRowsCount');
    if (cnt) cnt.textContent = visible;
}

// Copier les logs bruts dans le presse-papier
function copyLogsToClipboard() {
    const lines = [];
    document.querySelectorAll('#apacheLogsTable tbody tr.log-row').forEach(r => {
        if (r.style.display !== 'none') {
            lines.push(r.getAttribute('data-search-text') || '');
        }
    });

    if (lines.length === 0) return;
    navigator.clipboard.writeText(lines.join('\n')).then(() => {
        const btnText = document.getElementById('copyLogsBtnText');
        if (btnText) {
            const orig = btnText.textContent;
            btnText.textContent = 'Copié !';
            setTimeout(() => { btnText.textContent = orig; }, 2000);
        }
    });
}

function copyTerminalText() {
    const el = document.getElementById('terminalConsoleContent');
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        alert('Console terminal copiée dans le presse-papier !');
    });
}

function copySnippetToClipboard(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copié !';
            setTimeout(() => { btn.innerHTML = originalHtml; }, 2500);
        }
    });
}

function confirmClearLog() {
    const modalEl = document.getElementById('modalConfirmClearLog');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>

