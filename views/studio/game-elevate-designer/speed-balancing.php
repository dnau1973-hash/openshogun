                    <div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #ef4444 !important;">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-purple-lt text-purple fw-bold"><i class="fa-solid fa-gamepad me-1"></i>Game Elevate Designer</span>
                                    <span class="badge bg-danger-lt fw-bold"><i class="fa-solid fa-bolt me-1"></i>Constantes Monde</span>
                                </div>
                                <h3 class="card-title d-flex align-items-center gap-2 m-0 text-danger">
                                    <i class="fa-solid fa-bolt text-warning me-1"></i>Constantes &amp; Équilibrage des Vitesses de Jeu
                                </h3>
                                <div class="text-secondary small mt-1">
                                    Facteurs d'accélération des chantiers, de production des ressources et de marche des armées féodales.
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(1, 1, 1)">1x Classique</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(5, 5, 5)">5x Standard</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(20, 20, 10)">20x Éclair</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDevSpeedPreset(50, 50, 20)">50x Hyper</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="devGameSettingsForm" onsubmit="saveDevGameSettings(event)">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-bolt text-warning me-1"></i>Vitesse du Jeu (Chantiers &amp; Recherches)</span>
                                            <span class="badge bg-danger-lt" id="dev_badge_game_speed">x<?= (int)($gameSettings['game_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_game_speed_range" min="1" max="100" value="<?= (int)($gameSettings['game_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_game_speed_input').value = this.value; document.getElementById('dev_badge_game_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_game_speed_input" name="game_speed" min="1" max="100" 
                                                   value="<?= (int)($gameSettings['game_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_game_speed_range').value = this.value; document.getElementById('dev_badge_game_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Divise le temps nécessaire aux chantiers, Tenshu, académies et entraînements.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-hammer text-primary me-1"></i>Production des Ressources</span>
                                            <span class="badge bg-warning-lt" id="dev_badge_resource_speed">x<?= (int)($gameSettings['resource_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_resource_speed_range" min="1" max="100" value="<?= (int)($gameSettings['resource_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_resource_speed_input').value = this.value; document.getElementById('dev_badge_resource_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_resource_speed_input" name="resource_speed" min="1" max="100" 
                                                   value="<?= (int)($gameSettings['resource_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_resource_speed_range').value = this.value; document.getElementById('dev_badge_resource_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Multiplie la production horaire de Bois de Cèdre <i class="fa-solid fa-tree text-success"></i>, Pierre <i class="fa-solid fa-mountain text-secondary"></i> et Koku de Riz <i class="fa-solid fa-wheat-awn text-warning"></i>.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-horse text-danger me-1"></i>Marche des Troupes &amp; Expéditions</span>
                                            <span class="badge bg-primary-lt" id="dev_badge_fleet_speed">x<?= (int)($gameSettings['fleet_speed'] ?? 5) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_fleet_speed_range" min="1" max="50" value="<?= (int)($gameSettings['fleet_speed'] ?? 5) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_fleet_speed_input').value = this.value; document.getElementById('dev_badge_fleet_speed').textContent = 'x' + this.value;">
                                            <input type="number" id="dev_fleet_speed_input" name="fleet_speed" min="1" max="50" 
                                                   value="<?= (int)($gameSettings['fleet_speed'] ?? 5) ?>" class="form-control text-center font-weight-bold" style="width: 75px; min-height: 36px;"
                                                   oninput="document.getElementById('dev_fleet_speed_range').value = this.value; document.getElementById('dev_badge_fleet_speed').textContent = 'x' + this.value;">
                                        </div>
                                        <div class="form-hint">Accélère les trajets des régiments pour les assauts, convois de tributs et fondations.</div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Durée d'Immunité Débutant</span>
                                            <span class="badge bg-info-lt" id="dev_badge_protection_days"><?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?> j</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_beginner_protection_days_range" min="0" max="30" value="<?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_beginner_protection_days_input').value = this.value; document.getElementById('dev_badge_protection_days').textContent = this.value + ' j';">
                                            <div class="input-group" style="width: 85px;">
                                                <input type="number" id="dev_beginner_protection_days_input" name="beginner_protection_days" min="0" max="60" 
                                                       value="<?= (int)($gameSettings['beginner_protection_days'] ?? 7) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_beginner_protection_days_range').value = this.value; document.getElementById('dev_badge_protection_days').textContent = this.value + ' j';">
                                                <span class="input-group-text px-1 text-muted">j</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Durée accordée automatiquement lors de l'inscription (0 pour désactiver).</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3 border-top pt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-leaf text-success me-1"></i>Densité des Oasis sur la Carte (%)</span>
                                            <span class="badge bg-success-lt" id="dev_badge_oasis_density"><?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_oasis_density_percent_range" min="0.5" max="15.0" step="0.5" value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_oasis_density_percent_input').value = this.value; document.getElementById('dev_badge_oasis_density').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_oasis_density_percent_input" name="oasis_density_percent" min="0.5" max="20" step="0.5" 
                                                       value="<?= (float)($gameSettings['oasis_density_percent'] ?? 2.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_oasis_density_percent_range').value = this.value; document.getElementById('dev_badge_oasis_density').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Proportion de tuiles réservées aux oasis naturelles par rapport à la superficie totale.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            <i class="fa-solid fa-arrows-rotate me-1"></i>Réapparition Continue d'Oasis après Capture
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="dev_oasis_respawn_on_capture" name="oasis_respawn_on_capture" value="1" 
                                                       <?= !empty($gameSettings['oasis_respawn_on_capture']) ? 'checked' : '' ?>>
                                                <span class="form-check-label fw-medium text-dark">
                                                    Faire éclore une nouvelle oasis sauvage lors de l'annexion d'une oasis par un joueur
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Maintient le réservoir d'oasis sauvages et de faune active pour l'ensemble des seigneurs.</div>
                                    </div>
                                </div>

                                <!-- Paramètres des Quêtes Féodales & Samouraï Héros -->
                                <div class="row g-3 mb-3 border-top pt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-box text-warning me-1"></i>Cages de Capture (Kago) en Quête (%)</span>
                                            <span class="badge bg-green-lt fw-bold" id="dev_badge_hero_cage_drop_rate"><?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_hero_cage_drop_rate_range" min="0" max="100" step="1" 
                                                   value="<?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_hero_cage_drop_rate_input').value = this.value; document.getElementById('dev_badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_hero_cage_drop_rate_input" name="hero_cage_drop_rate" min="0" max="100" step="1" 
                                                       value="<?= (int)($gameSettings['hero_cage_drop_rate'] ?? 25) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_hero_cage_drop_rate_range').value = this.value; document.getElementById('dev_badge_hero_cage_drop_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Probabilité pour le Samouraï de rapporter un lot de Cages (Kago) pour capturer les bêtes sauvages des oasis sans combat.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-user-ninja text-purple me-1"></i>Gain d'Expérience (XP) en Aventure (%)</span>
                                            <span class="badge bg-primary-lt fw-bold" id="dev_badge_hero_xp_rate"><?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_hero_xp_rate_percent_range" min="10" max="500" step="5" 
                                                   value="<?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_hero_xp_rate_percent_input').value = this.value; document.getElementById('dev_badge_hero_xp_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_hero_xp_rate_percent_input" name="hero_xp_rate_percent" min="10" max="500" step="5" 
                                                       value="<?= (int)($gameSettings['hero_xp_rate_percent'] ?? 100) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_hero_xp_rate_percent_range').value = this.value; document.getElementById('dev_badge_hero_xp_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Multiplicateur du gain d'XP du Héros lors des aventures. Diminuez ce pourcentage pour ralentir la montée de niveau du Samouraï.</div>
                                    </div>
                                </div>

                                <!-- Section Famine & Vivres Féodaux -->
                                <div class="row g-3 mb-3 border-top pt-3" style="background: #fef2f2; border-radius: 8px; padding: 1rem; border: 1px solid #fecaca;">
                                    <div class="col-12">
                                        <h4 class="m-0 fw-bold text-danger d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-wheat-awn text-warning me-1"></i>Mécanisme de Famine &amp; Vivres Féodaux (Optionnel)
                                        </h4>
                                        <div class="text-secondary small mt-1">
                                            Si activé, les régiments d'élite (Tier 2, 3 et 4) exigent un entretien régulier en farine de riz. En cas de pénurie totale (stock de farine à 0), une famine s'abat sur le fief et décime progressivement les troupes d'élite.
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark mb-1">
                                            <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Activer la Famine (Disette de Farine)
                                        </label>
                                        <div class="d-flex align-items-center" style="min-height: 38px;">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="dev_famine_enabled" name="famine_enabled" value="1" 
                                                       <?= !empty($gameSettings['famine_enabled']) ? 'checked' : '' ?>>
                                                <span class="form-check-label fw-bold text-danger">
                                                    Activer le péril de la famine
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-hint">Désactivé par défaut. Les troupes d'élite ne meurent pas si décoché.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-skull text-danger me-1"></i>Taux de Pertes Horaire en Famine (%)</span>
                                            <span class="badge bg-danger text-white" id="dev_badge_famine_rate"><?= (float)($gameSettings['famine_rate'] ?? 3.0) ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_famine_rate_range" min="0.5" max="25.0" step="0.5" value="<?= (float)($gameSettings['famine_rate'] ?? 3.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_famine_rate_input').value = this.value; document.getElementById('dev_badge_famine_rate').textContent = this.value + ' %';">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_famine_rate_input" name="famine_rate" min="0.5" max="50" step="0.5" 
                                                       value="<?= (float)($gameSettings['famine_rate'] ?? 3.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_famine_rate_range').value = this.value; document.getElementById('dev_badge_famine_rate').textContent = this.value + ' %';">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Pourcentage de soldats d'élite mourant de faim ou désertant par heure de rupture de farine.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span><i class="fa-solid fa-bowl-rice text-warning me-1"></i>Rations Requises (Farine / 100 soldats / h)</span>
                                            <span class="badge bg-warning text-dark" id="dev_badge_famine_flour"><?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?></span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2" style="min-height: 38px;">
                                            <input type="range" id="dev_famine_flour_range" min="0.1" max="10.0" step="0.1" value="<?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?>" class="form-range flex-grow-1" 
                                                   oninput="document.getElementById('dev_famine_flour_consumption_input').value = this.value; document.getElementById('dev_badge_famine_flour').textContent = this.value;">
                                            <div class="input-group" style="width: 95px;">
                                                <input type="number" id="dev_famine_flour_consumption_input" name="famine_flour_consumption" min="0.1" max="20" step="0.1" 
                                                       value="<?= (float)($gameSettings['famine_flour_consumption'] ?? 1.0) ?>" class="form-control text-center font-weight-bold px-1" style="min-height: 36px;"
                                                       oninput="document.getElementById('dev_famine_flour_range').value = this.value; document.getElementById('dev_badge_famine_flour').textContent = this.value;">
                                                <span class="input-group-text px-1 text-muted"><i class="fa-solid fa-bowl-rice"></i></span>
                                            </div>
                                        </div>
                                        <div class="form-hint">Unités de farine consommées par heure pour maintenir 100 troupes d'élite rassasiées.</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer les Constantes de Jeu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
