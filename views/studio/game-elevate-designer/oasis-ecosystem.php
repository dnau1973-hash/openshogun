                    
                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #22c55e !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold"><i class="fa-solid fa-gamepad me-1"></i>Game Elevate Designer</span>
                                    <span class="badge bg-green-lt fw-bold"><i class="fa-solid fa-leaf me-1"></i>Faune &amp; Oasis</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-green">
                                    <i class="fa-solid fa-leaf text-success me-1"></i>Écosystème des Oasis Naturelles &amp; Faune Sauvage (Style Travian)
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Gestion du réseau d'oasis sauvages, de la faune hostile (Sangliers, Loups, Ours) et de la réapparition continue après capture.
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <span class="badge bg-green-lt fw-bold">
                                    <?= $oasisStats['total_oases'] ?> Oasis Totales
                                </span>
                                <span class="badge bg-blue-lt fw-bold">
                                    <?= $oasisStats['captured_oases'] ?> Fiefs Annexés
                                </span>
                                <span class="badge bg-danger-lt fw-bold">
                                    <?= $oasisStats['wild_oases'] ?> Sauvages Libres
                                </span>
                                <span class="badge bg-warning-lt fw-bold">
                                    <i class="fa-solid fa-paw text-warning me-1"></i><?= number_format($oasisStats['total_wild_animals']) ?> Bêtes Sauvages
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Panneau de contrôle et rééquilibrage de densité -->
                            <div class="card card-body bg-light border mb-3">
                                <h4 class="card-title d-flex align-items-center gap-2 text-success mb-2" style="font-size: 0.95rem;">
                                    <i class="fa-solid fa-gear text-secondary me-1"></i>Générateur &amp; Rééquilibrage par Pourcentage de Couverture
                                </h4>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Pourcentage de Densité Cible (%) :
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="input-group" style="width: 110px;">
                                                <input type="number" id="dev_repop_density" min="0.5" max="20.0" step="0.5" 
                                                       value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                            <span class="text-secondary small">&approx; 65 oasis à 2.0%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Rayon de Couverture Carte :
                                        </label>
                                        <div class="input-group" style="width: 110px;">
                                            <input type="number" id="dev_repop_radius" min="10" max="50" value="28" class="form-control text-center font-weight-bold">
                                            <span class="input-group-text px-1 text-muted">tuiles</span>
                                        </div>
                                    </div>

                                    <div class="col-md-5 col-sm-12">
                                        <label class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="dev_repop_clear_unoccupied" value="1">
                                            <span class="form-check-label fw-medium text-dark">Remplacer uniquement les oasis sauvages existantes</span>
                                        </label>
                                        <button type="button" onclick="executeDevRepopulateOases()" class="btn btn-success w-100 fw-bold">
                                            <i class="fa-solid fa-leaf me-1"></i>Appliquer &amp; Générer les Oasis
                                        </button>
                                    </div>
                                </div>
                                <div class="text-secondary small mt-2">
                                    <i class="fa-solid fa-circle-info text-info me-1"></i>Les oasis sont automatiquement réparties de façon équitable entre les 4 quadrants géographiques (NO, NE, SO, SE) sans empiéter sur les fiefs ni les 12 donjons authentiques.
                                </div>
                            </div>

                            <!-- Tableau des Oasis existantes -->
                            <div class="table-responsive" style="max-height: 440px;">
                                <table class="table card-table table-vcenter table-striped text-nowrap">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th class="w-1">#</th>
                                            <th>Nom de l'Oasis</th>
                                            <th class="text-center">Coords</th>
                                            <th>Bonus de Récolte</th>
                                            <th>Faune / Garnison</th>
                                            <th class="text-center">Statut Féodal</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dev-oases-tbody">
                                        <?php if (empty($allOases)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    Aucune oasis recensée. Utilisez le générateur ci-dessus pour peupler le royaume.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($allOases as $o): ?>
                                                <?php 
                                                    $isCaptured = !empty($o['owner_planet_id']);
                                                    $bText = '';
                                                    if ($o['bonus_rice'] > 0) $bText .= "+{$o['bonus_rice']}% (Riz) ";
                                                    if ($o['bonus_wood'] > 0) $bText .= "+{$o['bonus_wood']}% (Bois) ";
                                                    if ($o['bonus_stone'] > 0) $bText .= "+{$o['bonus_stone']}% (Pierre) ";
                                                ?>
                                                <tr class="dev-oasis-table-row <?= $isCaptured ? 'table-primary-lt' : '' ?>">
                                                    <td class="text-muted fw-bold"><?= $o['id'] ?></td>
                                                    <td>
                                                        <strong class="text-dark"><?= htmlspecialchars($o['name']) ?></strong>
                                                        <div class="text-secondary small">
                                                            <i class="fa-solid fa-tree text-success me-1"></i><?= number_format($o['res_wood']) ?> &bull; <i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($o['res_stone']) ?> &bull; <i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($o['res_rice']) ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center fw-bold text-azure">
                                                        [<?= $o['coord_x'] ?> : <?= $o['coord_y'] ?>]
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning-lt fw-bold"><?= trim($bText) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($o['garrison'])): ?>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($o['garrison'] as $g): ?>
                                                                    <span class="badge bg-dark-lt text-dark border">
                                                                         <?= $g['icon'] ?> <?= htmlspecialchars($g['unit_name']) ?> <strong class="text-warning">x<?= $g['count'] ?></strong>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-lt"><i class="fa-solid fa-circle-check text-success me-1"></i>Pacifiée (Aucune bête)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($isCaptured): ?>
                                                            <span class="badge bg-blue-lt">
                                                                <i class="fa-solid fa-shield-halved text-primary me-1"></i>Fief de <?= htmlspecialchars($o['owner_username'] ?? 'Daimyō') ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-lt">
                                                                <i class="fa-solid fa-paw text-warning me-1"></i>Sauvage Libre
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="/?page=map&x=<?= $o['coord_x'] ?>&y=<?= $o['coord_y'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fa-solid fa-map me-1"></i>Carte
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination du Réseau des Oasis -->
                            <?php if (!empty($allOases)): ?>
                                <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2" id="devOasesPaginationContainer">
                                    <p class="m-0 text-secondary small" id="devOasesPaginationInfo">
                                        Affichage de <strong id="devOasesPaginationStart"><?= min(1, count($allOases)) ?></strong> à <strong id="devOasesPaginationEnd"><?= min(15, count($allOases)) ?></strong> sur <strong id="devOasesPaginationTotal"><?= count($allOases) ?></strong> oasis
                                    </p>
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="devOasesPerPageSelect" class="small text-muted mb-0 d-none d-sm-inline">Par page :</label>
                                        <select id="devOasesPerPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeDevOasesPerPage(this.value)">
                                            <option value="15" selected>15</option>
                                            <option value="30">30</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <ul class="pagination pagination-sm m-0" id="devOasesPaginationList">
                                            <!-- Rempli en JavaScript -->
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
