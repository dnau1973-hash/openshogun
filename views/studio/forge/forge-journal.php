
                    <!-- Paliers de Progression -->
                    <h3 class="card-title mb-3"><i class="fa-solid fa-trophy text-warning me-1"></i>Paliers des Créateurs de la Forge</h3>
                    <div class="row row-cards mb-4">
                        <?php 
                        $tiersDisplay = [
                            ['lvl' => 1, 'min' => 0,    'title' => 'Apprenti Forgeron',          'badge' => 'bg-secondary-lt', 'desc' => 'Prise en main du code et découverte des mécaniques.'],
                            ['lvl' => 2, 'min' => 150,  'title' => 'Artisan du Code & Lore',     'badge' => 'bg-cyan-lt',      'desc' => 'Premières quêtes validées et corrections de bugs.'],
                            ['lvl' => 3, 'min' => 450,  'title' => 'Maître Bâtisseur Féodal',    'badge' => 'bg-info-lt',      'desc' => 'Architecture de nouveaux modules et équilibrages majeurs.'],
                            ['lvl' => 4, 'min' => 1000, 'title' => 'Grand Concepteur du Royaume', 'badge' => 'bg-purple-lt',    'desc' => 'Direction technique et leadership sur les sorties.'],
                            ['lvl' => 5, 'min' => 2000, 'title' => 'Légende Vivante du Studio',   'badge' => 'bg-warning-lt',   'desc' => 'Auteur fondateur de l\'univers OpenShogun.'],
                        ];
                        ?>
                        <?php foreach ($tiersDisplay as $t): ?>
                            <?php $isCurrent = ($levelInfo['level'] === $t['lvl']); ?>
                            <div class="col">
                                <div class="card text-center h-100 border <?= $isCurrent ? 'border-warning shadow' : '' ?>">
                                    <div class="card-body p-3">
                                        <div class="badge <?= $t['badge'] ?> mb-2">Palier <?= $t['lvl'] ?></div>
                                        <h4 class="card-title mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($t['title']) ?></h4>
                                        <div class="text-muted small font-monospace mb-2"><?= number_format($t['min']) ?> XP</div>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem;"><?= htmlspecialchars($t['desc']) ?></p>
                                    </div>
                                    <?php if ($isCurrent): ?>
                                        <div class="card-footer p-1 bg-warning-lt text-warning fw-bold small">
                                            Votre Rang Actuel
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Historique de Forge -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">Dernières Contributions & XP Gagnés</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Contributeur</th>
                                        <th>Action</th>
                                        <th>Détails</th>
                                        <th class="text-end">XP Obtenu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($forgeHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Aucune activité enregistrée pour le moment.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($forgeHistory as $fh): ?>
                                            <tr>
                                                <td class="text-muted small"><?= date('d/m/Y H:i', (int)$fh['created_at']) ?></td>
                                                <td><strong><?= htmlspecialchars($fh['username']) ?></strong></td>
                                                <td><span class="badge bg-purple-lt"><?= htmlspecialchars($fh['action_type']) ?></span></td>
                                                <td class="small text-muted"><?= htmlspecialchars($fh['description']) ?></td>
                                                <td class="text-end"><span class="badge bg-success-lt font-monospace">+<?= (int)$fh['xp_amount'] ?> XP</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
