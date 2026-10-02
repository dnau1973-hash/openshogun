
                    <!-- En-tête QA & Action Rapide -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="card-title d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-clipboard-check text-primary me-1"></i>Registre de Recette QA &amp; Contrôle Qualité
                            </h3>
                            <p class="text-muted small mb-0">
                                Suivi des fonctionnalités consignées dans <code>fonctionnalités.md</code>, validation manuelle par l'équipe QA et contrôle automatisé de la syntaxe PHP (<code>php -l</code>).
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-teal d-flex align-items-center gap-2 shadow-sm" id="btn-run-syntax" onclick="handleRunSyntaxCheck(this)">
                                <i class="fa-solid fa-bolt text-warning me-1"></i><span id="btn-run-syntax-text">Lancer le Contrôle de Syntaxe</span>
                            </button>
                        </div>
                    </div>

                    <!-- ── INDICATEURS CLÉS QA & ÉTAT TECHNIQUE ── -->
                    <div class="row row-cards mb-4">
                        <!-- 1. Feu Vert Technique (Syntaxe Automatisée) -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none" id="card-syntax-status">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Contrôle Syntaxe</span>
                                        <span class="badge <?= $syntaxStatus['is_clean'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?>" id="badge-syntax-status">
                                            <?= $syntaxStatus['is_clean'] ? '100% VALIDE' : 'ERREURS DÉTECTÉES' ?>
                                        </span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace d-flex align-items-center gap-2" id="text-syntax-summary">
                                        <span id="icon-syntax-clean"><?= $syntaxStatus['is_clean'] ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-circle-xmark text-danger"></i>' ?></span>
                                        <span id="text-syntax-passed"><?= $syntaxStatus['passed_count'] ?></span>
                                        <span class="text-muted fs-5 fw-normal">/ <span id="text-syntax-total"><?= $syntaxStatus['total_files'] ?></span> fichiers</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Dernier scan : <span id="val-syntax-date"><?= htmlspecialchars($syntaxStatus['checked_at'] ?? 'Jamais') ?></span> (<span id="val-syntax-dur"><?= $syntaxStatus['duration_ms'] ?? 0 ?></span> ms)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Fonctionnalités À Tester -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">À Tester</span>
                                        <span class="badge bg-warning-lt text-warning">En attente QA</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-warning d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-hourglass-half text-warning me-1"></i><span id="qa-stat-pending"><?= $qaStats['pending'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Nouveautés en attente de recette manuelle
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Fonctionnalités Validées -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Validées</span>
                                        <span class="badge bg-success-lt text-success">Recette OK</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-success d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i><span id="qa-stat-validated"><?= $qaStats['validated'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Fonctionnalités prêtes pour la production
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Fonctionnalités Rejetées -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Rejetées</span>
                                        <span class="badge bg-danger-lt text-danger">Failles / Bugs</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-danger d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-circle-xmark text-danger me-1"></i><span id="qa-stat-rejected"><?= $qaStats['rejected'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Nécessite des correctifs du développeur
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapport d'erreur syntaxe si erreurs détectées -->
                    <div id="qa-syntax-errors-box" class="alert alert-danger <?= empty($syntaxStatus['errors']) ? 'd-none' : '' ?> mb-4 shadow-sm" role="alert">
                        <h4 class="alert-title d-flex align-items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Erreurs de syntaxe PHP détectées lors du scan :
                        </h4>
                        <ul class="mb-0 small font-monospace" id="qa-syntax-errors-list">
                            <?php foreach (($syntaxStatus['errors'] ?? []) as $err): ?>
                                <li><strong><?= htmlspecialchars($err['file']) ?> :</strong> <?= htmlspecialchars($err['message']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- ── TABLEAU DU REGISTRE DE RECETTE (FONCTIONNALITÉS.MD) ── -->
                    <div class="card border">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-0">Registre des Fonctionnalités &amp; Historique de Recette</h4>
                                <div class="text-muted small mt-1">Source synchronisée : <code>fonctionnalités.md</code></div>
                            </div>
                            <span class="badge bg-purple-lt" id="qa-features-total-badge"><?= count($qaFeatures) ?> entrée(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover" id="table-qa-features">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Date &amp; Module</th>
                                        <th>Fonctionnalité &amp; Spécifications</th>
                                        <th>Vérification QA Attendue</th>
                                        <th style="width: 140px;">Statut Actuel</th>
                                        <th class="text-end" style="width: 180px;">Action de Recette</th>
                                    </tr>
                                </thead>
                                <tbody id="qa-features-tbody">
                                    <?php if (empty($qaFeatures)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4 fst-italic">
                                                Aucune fonctionnalité enregistrée dans <code>fonctionnalités.md</code>.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($qaFeatures as $f): ?>
                                            <?php
                                            $st = mb_strtolower($f['status']);
                                            if (str_contains($st, 'valid')) {
                                                $statusBadge = 'bg-success-lt text-success border border-success';
                                                $statusIcon = '<i class="fa-solid fa-check text-success me-1"></i>';
                                            } elseif (str_contains($st, 'rejet') || str_contains($st, 'refus')) {
                                                $statusBadge = 'bg-danger-lt text-danger border border-danger';
                                                $statusIcon = '<i class="fa-solid fa-xmark text-danger me-1"></i>';
                                            } else {
                                                $statusBadge = 'bg-warning-lt text-warning border border-warning';
                                                $statusIcon = '<i class="fa-solid fa-hourglass-half text-warning me-1"></i>';
                                            }
                                            ?>
                                            <tr id="feature-row-<?= $f['id'] ?>" data-feature-id="<?= $f['id'] ?>">
                                                <td>
                                                    <div class="font-monospace small text-dark fw-bold"><?= htmlspecialchars($f['date']) ?></div>
                                                    <span class="badge bg-blue-lt text-uppercase font-monospace mt-1" style="font-size: 0.65rem;">
                                                        <?= htmlspecialchars($f['module']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark mb-1" id="feature-title-<?= $f['id'] ?>"><?= htmlspecialchars($f['title']) ?></div>
                                                    <div class="text-secondary small" style="line-height: 1.4;">
                                                        <?= htmlspecialchars($f['description']) ?>
                                                    </div>
                                                    <?php if (!empty($f['files'])): ?>
                                                        <div class="text-muted small mt-1 font-monospace" style="font-size: 0.75rem;">
                                                            <i class="fa-solid fa-folder-open text-primary me-1"></i><?= htmlspecialchars($f['files']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($f['validated_by'])): ?>
                                                        <div class="text-muted small mt-1 fst-italic" style="font-size: 0.75rem;" id="feature-validator-<?= $f['id'] ?>">
                                                            <i class="fa-solid fa-user-check me-1"></i>Validé par : <?= htmlspecialchars($f['validated_by']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($f['qa_check'])): ?>
                                                        <div class="small text-secondary bg-light p-2 rounded border" style="font-size: 0.8rem; line-height: 1.35;">
                                                            <i class="fa-solid fa-bullseye text-danger me-1"></i><?= htmlspecialchars($f['qa_check']) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">Non spécifié</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $statusBadge ?> px-2 py-1" id="feature-badge-<?= $f['id'] ?>">
                                                        <?= $statusIcon ?> <?= htmlspecialchars($f['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'Validée', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Marquer comme validée">
                                                            <i class="fa-solid fa-check me-1"></i>Valider
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'Rejetée', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Rejeter (anomalie détectée)">
                                                            <i class="fa-solid fa-xmark me-1"></i>Rejeter
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="setFeatureStatus('<?= $f['id'] ?>', 'À tester', '<?= htmlspecialchars(addslashes($f['title'])) ?>')"
                                                                title="Remettre à tester">
                                                            <i class="fa-solid fa-rotate-left me-1"></i>Reset
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
