<?php
/**
 * Vue Partielle : Expédition de Caravanes & Convois de Marchandises
 */
?>
<div class="tab-pane fade" id="tab-market-dispatch" role="tabpanel">
    <?php if ($totalCargoCapacity <= 0): ?>
    <div class="alert alert-warning mb-3 p-3 border-warning shadow-sm">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-1"><i class="fa-solid fa-horse text-danger"></i></span>
                <div>
                    <h4 class="m-0 fw-bold text-dark">Aucun chariot de ravitaillement disponible</h4>
                    <div class="text-secondary small mt-1">
                        Pour acheminer des denrées vers un autre domaine, vous devez disposer de <strong>Chariots Légers (5k fret)</strong> ou de <strong>Grands Convois Logistiques (25k fret)</strong> dans vos écuries.
                    </div>
                </div>
            </div>
            <a href="/?page=shipyard" class="btn btn-warning text-dark fw-bold">
                <i class="fa-solid fa-horse me-1"></i>Forger des Convois aux Écuries &rarr;
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Colonne Gauche : Destination & Ressources -->
        <div class="col-lg-7">
            <div class="border rounded p-3 bg-light mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold text-dark m-0"><i class="fa-solid fa-chess-rook text-danger me-1"></i>Fief Destinataire :</label>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="mkt_manual_coords_toggle" onchange="toggleMarketDestMode()">
                        <label class="form-check-label small text-muted" for="mkt_manual_coords_toggle">Saisir coordonnées [X|Y]</label>
                    </div>
                </div>

                <!-- Mode 1 : Sélection parmi mes fiefs -->
                <div id="mkt_select_village_box">
                    <?php if (!empty($otherVillages)): ?>
                        <select id="mkt_target_planet_select" class="form-select fw-bold" onchange="updateMarketCalculations()">
                            <?php foreach ($otherVillages as $v): ?>
                                <option value="<?= $v['id'] ?>" data-x="<?= $v['coord_x'] ?>" data-y="<?= $v['coord_y'] ?>">
                                    <?= htmlspecialchars($v['name']) ?> &bull; Coordonnées [<?= $v['coord_x'] ?>|<?= $v['coord_y'] ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="text-muted small italic p-2 border rounded bg-white">
                            Vous ne possédez pour l'instant qu'un seul fief. Utilisez le mode coordonnées pour ravitailler un domaine allié ou voisin.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mode 2 : Saisie manuelle de coordonnées -->
                <div id="mkt_manual_coords_box" class="d-none">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text small">X</span>
                                <input type="number" id="mkt_target_x" class="form-control text-center fw-bold" placeholder="0" oninput="updateMarketCalculations()">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text small">Y</span>
                                <input type="number" id="mkt_target_y" class="form-control text-center fw-bold" placeholder="0" oninput="updateMarketCalculations()">
                            </div>
                        </div>
                    </div>
                    <div class="text-muted small mt-1" style="font-size:0.75rem;">
                        Entrez les coordonnées féodales de la cité ou du domaine à ravitailler.
                    </div>
                </div>
            </div>

            <!-- Saisie des cargaisons -->
            <div class="border rounded p-3 bg-white">
                <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-boxes-stacked text-warning me-1"></i>Chargement des Marchandises</h4>

                <!-- Bois -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre</label>
                        <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_wood_label"><?= number_format((int)$planet['metal']) ?></strong></span>
                    </div>
                    <div class="input-group mb-1">
                        <input type="number" id="mkt_wood" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['metal'] ?>" step="500" oninput="updateMarketCalculations()">
                        <span class="input-group-text small">Bois</span>
                    </div>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 0)">0</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 1000)">+1k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 5000)">+5k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('wood', 'max')">Max</button>
                    </div>
                </div>

                <!-- Pierre -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Taille</label>
                        <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_stone_label"><?= number_format((int)$planet['crystal']) ?></strong></span>
                    </div>
                    <div class="input-group mb-1">
                        <input type="number" id="mkt_stone" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['crystal'] ?>" step="500" oninput="updateMarketCalculations()">
                        <span class="input-group-text small">Pierre</span>
                    </div>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 0)">0</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 1000)">+1k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 5000)">+5k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('stone', 'max')">Max</button>
                    </div>
                </div>

                <!-- Riz -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark small m-0"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial (Grenier)</label>
                        <span class="text-muted small">Disponible : <strong class="text-success" id="mkt_max_rice_label"><?= number_format((int)$planet['deuterium']) ?></strong></span>
                    </div>
                    <div class="input-group mb-1">
                        <input type="number" id="mkt_rice" class="form-control fw-bold text-center" value="0" min="0" max="<?= (int)$planet['deuterium'] ?>" step="500" oninput="updateMarketCalculations()">
                        <span class="input-group-text small">Riz</span>
                    </div>
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 0)">0</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 1000)">+1k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 5000)">+5k</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setMarketResAmount('rice', 'max')">Max</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Bilan logistique & Déclenchement -->
        <div class="col-lg-5">
            <div class="border rounded p-3 bg-light mb-3">
                <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clipboard-list text-primary me-1"></i>Bilan de l'Expédition</h4>

                <div class="p-3 bg-white rounded border mb-3">
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                        <span class="text-muted small">Cargaison totale :</span>
                        <strong class="fs-4 text-warning-emphasis font-monospace" id="mkt_total_cargo_display">0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Convois requis :</span>
                        <span class="fw-bold text-dark small" id="mkt_vehicles_needed">Aucun</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Capacité mobilisée :</span>
                        <strong class="font-monospace text-dark" id="mkt_cap_mobilized_display">0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Rations de marche requises :</span>
                        <span class="fw-bold font-monospace text-warning-emphasis" id="mkt_fuel_needed_display">0 Koku</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-muted small">Durée du trajet :</span>
                        <span class="fw-bold font-monospace text-dark" id="mkt_duration_display"><i class="fa-solid fa-stopwatch text-info me-1"></i>~calcul...</span>
                    </div>
                </div>

                <div id="mkt_alert_status" class="alert alert-info py-2 px-3 small mb-3">
                    Sélectionnez un fief de destination et indiquez les quantités de ressources à convoyer.
                </div>
            </div>

            <button type="button" class="btn btn-warning text-dark fw-bold w-100 py-3 shadow-sm" id="btnDispatchMarketCargo" onclick="submitMarketCargoDispatch()">
                <i class="fa-solid fa-paper-plane me-1"></i>Affréter et Expédier le Convoi
            </button>
        </div>
    </div>
</div>

