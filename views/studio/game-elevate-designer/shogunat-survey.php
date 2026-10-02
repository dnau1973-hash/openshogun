                    
                    <!-- Répartition Détaillée des Tuiles -->
                    <?php if (!empty($mapTileStats)): ?>
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold"><i class="fa-solid fa-gamepad me-1"></i>Game Elevate Designer</span>
                                    <span class="badge bg-indigo-lt fw-bold"><i class="fa-solid fa-map me-1"></i>Carte Féodale</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-dark">
                                    <i class="fa-solid fa-map text-primary me-1"></i>Répartition des Tuiles du Monde Féodal
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Grille de <?= ($mapTileStats['radius'] * 2 + 1) ?>&times;<?= ($mapTileStats['radius'] * 2 + 1) ?> cases &bull; Rayon &plusmn;<?= $mapTileStats['radius'] ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white fw-bold">
                                    <?= number_format($mapTileStats['total_tiles']) ?> Tuiles au total
                                </span>
                                <a href="/?page=map" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-map-location-dot me-1"></i>Ouvrir la Carte &rarr;
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Barre de progression proportionnelle multi-segments -->
                            <div class="progress progress-separated mb-3" style="height: 10px;" title="Répartition graphique de la carte féodale">
                                <?php foreach ($mapTileCategories as $cat): 
                                    $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 2);
                                    if ($pct <= 0) continue;
                                ?>
                                    <div class="progress-bar <?= $cat['bar_color'] ?>" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" title="<?= htmlspecialchars($cat['name']) ?> : <?= number_format($cat['count']) ?> tuiles (<?= $pct ?>%)"></div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Grille des 9 catégories de tuiles -->
                            <div class="row row-cards g-2">
                                <?php foreach ($mapTileCategories as $key => $cat): 
                                    $pct = round(($cat['count'] / $mapTileStats['total_tiles']) * 100, 1);
                                ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-xl">
                                        <div class="card card-sm h-100 shadow-none border">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= $cat['img'] ?>" alt="<?= htmlspecialchars($cat['name']) ?>" 
                                                         style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover; border: 1px solid rgba(0,0,0,0.15);" class="flex-shrink-0">
                                                    <div class="overflow-hidden flex-grow-1">
                                                        <div class="text-truncate fw-bold text-dark small" title="<?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['sub']) ?>)">
                                                            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                                                        </div>
                                                        <div class="d-flex align-items-baseline justify-content-between gap-1 mt-1">
                                                            <span class="badge <?= $cat['badge_bg'] ?> px-1 py-0 fw-bold" style="font-size: 0.72rem;">
                                                                <?= number_format($cat['count']) ?>
                                                            </span>
                                                            <span class="text-secondary small font-monospace" style="font-size: 0.7rem;">
                                                                <?= $pct ?>%
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Arpenteur du Shogunat & Déploiement des Fiefs -->
                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #10b981 !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-success">
                                    <i class="fa-solid fa-map-location-dot text-primary me-1"></i>Arpenteur du Shogunat &amp; Expansion des Provinces
                                </h3>
                                <div class="text-secondary small mt-1">Création procédurale de fiefs, vallées et sanctuaires</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="devWorldGenForm" onsubmit="generateDevWorld(event)">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Nombre de Terres &amp; Fiefs à Déployer</span>
                                            <span class="badge bg-success-lt" id="dev_badge_planet_count">12 fiefs</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_planet_count_range" min="1" max="50" value="12" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_planet_count_input').value = this.value; document.getElementById('dev_badge_planet_count').textContent = this.value + ' fiefs';">
                                            <input type="number" id="dev_planet_count_input" name="planet_count" min="1" max="50" value="12" 
                                                   class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_planet_count_range').value = this.value; document.getElementById('dev_badge_planet_count').textContent = this.value + ' fiefs';">
                                        </div>
                                        <div class="form-hint">Terres libres prêtes à être explorées, pillées ou inféodées.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Rayon de Dispersion Géographique</span>
                                            <span class="badge bg-info-lt" id="dev_badge_radius">&plusmn; 12</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_radius_range" min="5" max="35" value="12" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_radius_input').value = this.value; document.getElementById('dev_badge_radius').textContent = '± ' + this.value;">
                                            <input type="number" id="dev_radius_input" name="radius" min="5" max="35" value="12" 
                                                   class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_radius_range').value = this.value; document.getElementById('dev_badge_radius').textContent = '± ' + this.value;">
                                        </div>
                                        <div class="form-hint">Étendue des provinces [-R, +R] autour de la capitale impériale.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            Gestion des Terres Inoccupées
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="clear_uninhabited" value="1" id="dev_clear_uninhabited">
                                                <span class="form-check-label fw-medium text-dark">
                                                    Purger les terres libres inoccupées existantes avant génération
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Ne supprime jamais les fiefs possédés par un Daimyō ou un bot.</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold">
                                        <i class="fa-solid fa-map-location-dot me-1"></i>Déployer les Fiefs dans les Provinces
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
