<?php
/**
 * Vue Partielle Mutualisée : Modale Interactive de l'Intendant du Marché Castral (Troc NPC 1:1)
 * Présentation en 2 colonnes : Alimentaire (gauche) & Matériaux de Construction (droite)
 * Utilisée dans : views/building.php, views/empire.php, views/privilege.php
 *
 * Variables attendues (optionnelles) :
 * - $goldCoins (int, optionnel) : Solde de Kobans du joueur
 * - $user (array, optionnel) : Données utilisateur
 */

$npcKobanBalance = isset($goldCoins) ? (int)$goldCoins : (isset($user['gold_coins']) ? (int)$user['gold_coins'] : null);
?>

<?php if (!empty($isInlineNpcExchange)): ?>
<!-- PANNEAU DIRECT DU MARCHAND NPC / INTENDANT DU MARCHÉ (TROC 1:1:1) -->
<div class="card shadow-sm border mb-3" id="inlineNpcExchangeContainer" style="border-top: 3px solid #7c3aed !important;">
    <div class="card-header bg-warning-subtle py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title fw-bold text-dark d-flex align-items-center gap-2 m-0">
            <i class="fa-solid fa-scale-balanced text-warning fs-3"></i>
            <span>Intendant du Marché Castral &bull; Troc Équilibré 1:1:1</span>
        </h4>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple text-white font-monospace py-1 px-2">
                <i class="fa-solid fa-coins me-1"></i>Tribut : 3 Koban
            </span>
            <?php if ($npcKobanBalance !== null): ?>
                <span class="badge bg-warning-lt text-dark font-monospace py-1 px-2">
                    Solde : <strong><?= (int)$npcKobanBalance ?> Koban</strong>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
<?php else: ?>
<!-- MODALE DU MARCHAND NPC / INTENDANT DU MARCHÉ (TROC 1:1) -->
<div class="modal modal-blur fade" id="modalNpcExchange" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border">
            <div class="modal-header bg-warning-subtle py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 m-0">
                    <i class="fa-solid fa-scale-balanced text-warning fs-3"></i>
                    <span>Intendant du Marché Castral &bull; Troc Équilibré 1:1</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
<?php endif; ?>
                <div class="p-3 bg-light border rounded mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-7 text-md-start">
                            <div class="small text-muted mb-1">
                                Fief provincial sélectionné : <strong id="npc_planet_name" class="text-dark">Fief</strong>
                            </div>
                            <div class="text-secondary small">
                                L'Intendant rééquilibre instantanément vos réserves au ratio d'or pur de <strong>1:1</strong> sans aucune taxe de déperdition pour un tribut impérial de <strong>3 Koban <i class="fa-solid fa-coins text-warning"></i></strong>.
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-2 mt-md-0 border-start-md">
                            <div class="fs-2 fw-bold text-primary font-monospace">
                                <span id="npc_total_amount">0</span>
                            </div>
                            <div class="small text-muted">Total des réserves à répartir (strictement conservé).</div>
                        </div>
                    </div>
                </div>

                <!-- Bandeau dynamique d'aide et statut d'équilibrage -->
                <div id="npc_link_status_banner" class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info fs-4 flex-shrink-0"></i>
                    <div id="npc_link_status_text">
                        Cochez 2 ressources pour lier leurs échelles et équilibrer le troc automatiquement en miroir (1:1).
                    </div>
                </div>

                <!-- GRILLE DE PRÉSENTATION EN 2 COLONNES -->
                <div class="row g-3">
                    <!-- ========================================== -->
                    <!-- COLONNE GAUCHE : ALIMENTAIRE (6 RESSOURCES) -->
                    <!-- ========================================== -->
                    <div class="col-lg-6">
                        <div class="card h-100 border shadow-none bg-surface-secondary">
                            <div class="card-header bg-warning-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-bowl-rice text-warning"></i>
                                    <span>Vivres &amp; Ressources Alimentaires</span>
                                </span>
                                <span class="badge bg-warning text-dark font-monospace" style="font-size:0.7rem;">6 vivres</span>
                            </div>
                            <div class="card-body p-2 p-md-3">
                                <!-- 1. Riz Impérial -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_rice">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_rice" onchange="onNpcCheckChange('rice')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-wheat-awn text-warning"></i>
                                                <span>Riz Impérial (Koku)</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_rice" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_rice_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_rice" oninput="onNpcRangeChange('rice')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_rice" style="width: 100px;" min="0" oninput="onNpcInputChange('rice')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Grenier : <strong id="npc_max_rice_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 2. Poisson Frais -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_fish">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_fish" onchange="onNpcCheckChange('fish')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-fish text-info"></i>
                                                <span>Poisson Frais</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_fish" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_fish_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_fish" oninput="onNpcRangeChange('fish')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_fish" style="width: 100px;" min="0" oninput="onNpcInputChange('fish')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Vivier : <strong id="npc_max_fish_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 3. Feuilles de Thé -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_tea">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_tea" onchange="onNpcCheckChange('tea')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-leaf text-success"></i>
                                                <span>Feuilles de Thé</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_tea" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_tea_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_tea" oninput="onNpcRangeChange('tea')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_tea" style="width: 100px;" min="0" oninput="onNpcInputChange('tea')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Pavillon : <strong id="npc_max_tea_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 4. Gousses de Soja -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_soybean">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_soybean" onchange="onNpcCheckChange('soybean')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-seedling text-warning"></i>
                                                <span>Gousses de Soja</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_soybean" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_soybean_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_soybean" oninput="onNpcRangeChange('soybean')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_soybean" style="width: 100px;" min="0" oninput="onNpcInputChange('soybean')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Garde-manger : <strong id="npc_max_soybean_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 5. Farine de Riz -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_rice_flour">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_rice_flour" onchange="onNpcCheckChange('rice_flour')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-bowl-rice text-secondary"></i>
                                                <span>Farine de Riz (Komeko)</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_rice_flour" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_rice_flour_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_rice_flour" oninput="onNpcRangeChange('rice_flour')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_rice_flour" style="width: 100px;" min="0" oninput="onNpcInputChange('rice_flour')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Meunerie : <strong id="npc_max_rice_flour_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 6. Saké Impérial -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_sake">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_sake" onchange="onNpcCheckChange('sake')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-wine-bottle text-danger"></i>
                                                <span>Saké Impérial</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_sake" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_sake_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_sake" oninput="onNpcRangeChange('sake')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_sake" style="width: 100px;" min="0" oninput="onNpcInputChange('sake')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Brasserie : <strong id="npc_max_sake_label">0</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- COLONNE DROITE : MATÉRIAUX DE CONSTRUCTION (5 TYPES) -->
                    <!-- ==================================================== -->
                    <div class="col-lg-6">
                        <div class="card h-100 border shadow-none bg-surface-secondary">
                            <div class="card-header bg-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-hammer text-primary"></i>
                                    <span>Matériaux de Construction</span>
                                </span>
                                <span class="badge bg-primary text-white font-monospace" style="font-size:0.7rem;">5 matériaux</span>
                            </div>
                            <div class="card-body p-2 p-md-3">
                                <!-- 1. Bois de Cèdre -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_wood">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_wood" onchange="onNpcCheckChange('wood')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-tree text-success"></i>
                                                <span>Bois de Cèdre</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_wood" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_wood_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_wood" oninput="onNpcRangeChange('wood')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_wood" style="width: 100px;" min="0" oninput="onNpcInputChange('wood')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Scierie : <strong id="npc_max_wood_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 2. Pierre de Granit -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_stone">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_stone" onchange="onNpcCheckChange('stone')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-mountain text-secondary"></i>
                                                <span>Pierre de Granit</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_stone" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_stone_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_stone" oninput="onNpcRangeChange('stone')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_stone" style="width: 100px;" min="0" oninput="onNpcInputChange('stone')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Carrière : <strong id="npc_max_stone_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 3. Argile Alluviale -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_clay">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_clay" onchange="onNpcCheckChange('clay')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-cubes-stacked text-danger"></i>
                                                <span>Argile Alluviale</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_clay" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_clay_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_clay" oninput="onNpcRangeChange('clay')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_clay" style="width: 100px;" min="0" oninput="onNpcInputChange('clay')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Fosse d'argile : <strong id="npc_max_clay_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 4. Poutres de Charpente -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_wooden_beams">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_wooden_beams" onchange="onNpcCheckChange('wooden_beams')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-hammer text-warning"></i>
                                                <span>Poutres de Charpente</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_wooden_beams" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_wooden_beams_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_wooden_beams" oninput="onNpcRangeChange('wooden_beams')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_wooden_beams" style="width: 100px;" min="0" oninput="onNpcInputChange('wooden_beams')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Atelier charpente : <strong id="npc_max_wooden_beams_label">0</strong></span>
                                    </div>
                                </div>

                                <!-- 5. Charbon de Bois -->
                                <div class="mb-2 p-2 rounded border bg-white transition-all npc-res-card" id="npc_row_charcoal">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer user-select-none">
                                            <input type="checkbox" class="form-check-input npc-resource-check" id="npc_check_charcoal" onchange="onNpcCheckChange('charcoal')">
                                            <span class="form-check-label fw-bold small text-dark d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-fire text-dark"></i>
                                                <span>Charbon de Bois</span>
                                            </span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="npc_link_badge_charcoal" class="badge bg-primary-lt d-none" style="font-size:0.68rem;"></span>
                                            <span id="npc_charcoal_val" class="font-monospace fw-bold text-primary small">0</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" id="npc_range_charcoal" oninput="onNpcRangeChange('charcoal')">
                                        <input type="number" class="form-control form-control-sm font-monospace text-end" id="npc_input_charcoal" style="width: 100px;" min="0" oninput="onNpcInputChange('charcoal')">
                                    </div>
                                    <div class="d-flex justify-content-end text-muted mt-1" style="font-size: 0.70rem;">
                                        <span>Meule de charbonnier : <strong id="npc_max_charcoal_label">0</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS D'AIDE ET RÉPARTITION RAPIDE -->
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-outline-warning fw-bold" onclick="distributeThirds()">
                        <i class="fa-solid fa-scale-balanced me-1"></i>Troc 1:1:1 en Tiers (Bois / Pierre / Riz)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="distributeEvenly()">
                        <i class="fa-solid fa-boxes-stacked me-1"></i>Répartir équitablement (toutes les denrées)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetInitialStocks()">
                        <i class="fa-solid fa-rotate-left me-1"></i>Rétablir les stocks d'origine
                    </button>
                </div>

                <div id="npc_diff_alert" class="alert alert-danger p-2 small mt-3 d-none">
                    La somme répartie ne correspond pas au total disponible.
                </div>
            </div>

<?php if (!empty($isInlineNpcExchange)): ?>
            <div class="card-footer d-flex justify-content-between align-items-center bg-light flex-wrap gap-2 py-3">
                <span class="small text-muted">
                    Tribut impérial : <strong class="text-warning">3 Koban <i class="fa-solid fa-coins text-warning"></i></strong>
                    <?php if ($npcKobanBalance !== null): ?>
                        (Solde disponible : <strong><?= (int)$npcKobanBalance ?> Koban</strong>)
                    <?php endif; ?>
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="resetInitialStocks()">
                        <i class="fa-solid fa-rotate-left me-1"></i>Rétablir
                    </button>
                    <button type="button" class="btn btn-warning fw-bold shadow-sm px-4" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        <i class="fa-solid fa-coins me-1"></i>Sceller le Troc 1:1:1 (3 Koban)
                    </button>
                </div>
            </div>
        </div>
<?php else: ?>
            <div class="modal-footer d-flex justify-content-between align-items-center bg-light">
                <span class="small text-muted">
                    Tribut impérial : <strong class="text-warning">3 Koban <i class="fa-solid fa-coins text-warning"></i></strong>
                    <?php if ($npcKobanBalance !== null): ?>
                        (Solde : <?= (int)$npcKobanBalance ?>)
                    <?php endif; ?>
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning fw-bold shadow-sm" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        <i class="fa-solid fa-coins me-1"></i>Sceller le Troc (3 Koban)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// =========================================================================
// MOTEUR INTENDANT DU MARCHÉ CASTRAL (TROC 1:1 & ÉQUILIBRAGE PROPORTIONNEL)
// =========================================================================
const ALL_NPC_RESOURCES = [
    // Alimentaire (Gauche)
    'rice', 'fish', 'tea', 'soybean', 'rice_flour', 'sake',
    // Construction (Droite)
    'wood', 'stone', 'clay', 'wooden_beams', 'charcoal'
];

const NPC_RES_NAMES = {
    rice: 'Riz Impérial',
    fish: 'Poisson Frais',
    tea: 'Feuilles de Thé',
    soybean: 'Gousses de Soja',
    rice_flour: 'Farine de Riz',
    sake: 'Saké Impérial',
    wood: 'Bois de Cèdre',
    stone: 'Pierre de Granit',
    clay: 'Argile Alluviale',
    wooden_beams: 'Poutres de Charpente',
    charcoal: 'Charbon de Bois'
};

var currentNpcPlanetId = 0;
var currentNpcTotal = 0;
var currentNpcMax = {};
var initialNpcStocks = {};
var npcCheckedResources = []; // Maximum 2 cases sélectionnées simultanément

/**
 * Ouverture et initialisation de la modale pour le fief ciblé
 * Compatible avec :
 * - openNpcExchangeModal(planetId, planetName, resourcesObject, maxesObject)
 * - openNpcExchangeModal(planetId, planetName, wood, stone, rice, maxW, maxS, maxR)
 */
function openNpcExchangeModal(planetId, planetName, resOrWood, maxOrStone, rice, maxW, maxS, maxR) {
    currentNpcPlanetId = planetId;
    npcCheckedResources = [];

    let resources = {};
    let maxes = {};

    if (typeof resOrWood === 'object' && resOrWood !== null) {
        resources = Object.assign({}, resOrWood);
        maxes = Object.assign({}, maxOrStone || {});
    } else {
        // Mode rétrocompatibilité (wood, stone, rice)
        const w = parseInt(resOrWood || 0, 10);
        const s = parseInt(maxOrStone || 0, 10);
        const r = parseInt(rice || 0, 10);
        resources = {
            wood: w,
            stone: s,
            rice: r,
            fish: 0,
            tea: 0,
            soybean: 0,
            rice_flour: 0,
            sake: 0,
            clay: 0,
            wooden_beams: 0,
            charcoal: 0
        };
        maxes = {
            wood: maxW || 15000,
            stone: maxS || 15000,
            rice: maxR || 15000,
            fish: maxR || 15000,
            tea: 10000,
            soybean: 10000,
            rice_flour: 10000,
            sake: 10000,
            clay: 10000,
            wooden_beams: 10000,
            charcoal: 10000
        };
    }

    // Calcul du total général
    currentNpcTotal = 0;
    initialNpcStocks = {};
    currentNpcMax = {};

    ALL_NPC_RESOURCES.forEach(k => {
        const val = Math.max(0, parseInt(resources[k] || 0, 10));
        const maxVal = Math.max(1, parseInt(maxes[k] || 15000, 10));
        initialNpcStocks[k] = val;
        currentNpcMax[k] = maxVal;
        currentNpcTotal += val;
    });

    const nameEl = document.getElementById('npc_planet_name');
    if (nameEl) nameEl.innerText = planetName;
    const totEl = document.getElementById('npc_total_amount');
    if (totEl) totEl.innerText = currentNpcTotal.toLocaleString('fr-FR');

    // Initialisation des champs et jauges
    ALL_NPC_RESOURCES.forEach(res => {
        const val = initialNpcStocks[res];
        const maxStore = currentNpcMax[res] || currentNpcTotal;

        const chk = document.getElementById(`npc_check_${res}`);
        const r = document.getElementById(`npc_range_${res}`);
        const inp = document.getElementById(`npc_input_${res}`);
        const maxLbl = document.getElementById(`npc_max_${res}_label`);

        if (chk) {
            chk.checked = false;
            chk.disabled = false;
        }
        if (maxLbl) {
            maxLbl.innerText = maxStore.toLocaleString('fr-FR');
        }
        if (r) {
            r.min = 0;
            r.max = Math.min(currentNpcTotal, maxStore);
            r.value = val;
            r.disabled = false;
        }
        if (inp) {
            inp.min = 0;
            inp.max = maxStore;
            inp.value = val;
            inp.disabled = false;
        }
        const valSpan = document.getElementById(`npc_${res}_val`);
        if (valSpan) valSpan.innerText = val.toLocaleString('fr-FR');
    });

    updateNpcCheckState();
    updateNpcDisplay();

    const modalEl = document.getElementById('modalNpcExchange');
    if (modalEl && modalEl.classList && modalEl.classList.contains('modal')) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }
}

/**
 * Gestion du changement de sélection des cases à cocher (max 2)
 */
function onNpcCheckChange(type) {
    const chk = document.getElementById(`npc_check_${type}`);
    if (!chk) return;

    if (chk.checked) {
        if (npcCheckedResources.length >= 2) {
            chk.checked = false;
            if (typeof showTablerToast === 'function') {
                showTablerToast('Sélection maximale atteinte : 2 ressources maximum peuvent être liées pour l\'équilibrage.', 'warning', 'Équilibrage de Troc');
            }
            return;
        }
        if (!npcCheckedResources.includes(type)) {
            npcCheckedResources.push(type);
        }
    } else {
        npcCheckedResources = npcCheckedResources.filter(r => r !== type);
    }

    updateNpcCheckState();
}

/**
 * Mise à jour de l'état visuel et interactif des cases à cocher et des liaisons
 */
function updateNpcCheckState() {
    const banner = document.getElementById('npc_link_status_banner');
    const bannerText = document.getElementById('npc_link_status_text');

    if (npcCheckedResources.length === 2) {
        const [r1, r2] = npcCheckedResources;

        // Désactiver les 9 autres cases à cocher et verrouiller leurs curseurs
        ALL_NPC_RESOURCES.forEach(r => {
            const isChecked = npcCheckedResources.includes(r);
            const chk = document.getElementById(`npc_check_${r}`);
            const rng = document.getElementById(`npc_range_${r}`);
            const inp = document.getElementById(`npc_input_${r}`);
            const badge = document.getElementById(`npc_link_badge_${r}`);
            const row = document.getElementById(`npc_row_${r}`);

            if (!isChecked) {
                if (chk) {
                    chk.disabled = true;
                    chk.parentElement.setAttribute('title', 'Désélectionnez une ressource pour en choisir une autre (max 2).');
                }
                if (rng) rng.disabled = true;
                if (inp) inp.disabled = true;
                if (badge) badge.classList.add('d-none');
                if (row) {
                    row.classList.remove('border-primary', 'bg-primary-subtle');
                    row.classList.add('opacity-75');
                }
            } else {
                if (chk) {
                    chk.disabled = false;
                    chk.parentElement.removeAttribute('title');
                }
                if (rng) rng.disabled = false;
                if (inp) inp.disabled = false;
                if (badge) {
                    badge.classList.remove('d-none');
                    const other = (r === r1) ? r2 : r1;
                    badge.innerHTML = `<i class="fa-solid fa-link me-1"></i>Lié à ${NPC_RES_NAMES[other]}`;
                }
                if (row) {
                    row.classList.add('border-primary', 'bg-primary-subtle');
                    row.classList.remove('opacity-75');
                }
            }
        });

        if (banner && bannerText) {
            banner.className = 'alert alert-success py-2 px-3 small mb-3 d-flex align-items-center gap-2';
            bannerText.innerHTML = `<strong><i class="fa-solid fa-link me-1"></i>Échange équilibré 1:1 actif :</strong> Augmenter le <strong>${NPC_RES_NAMES[r1]}</strong> diminue proportionnellement la ressource <strong>${NPC_RES_NAMES[r2]}</strong> (et inversement).`;
        }
    } else if (npcCheckedResources.length === 1) {
        const selected = npcCheckedResources[0];

        ALL_NPC_RESOURCES.forEach(r => {
            const chk = document.getElementById(`npc_check_${r}`);
            if (chk) {
                chk.disabled = false;
                chk.parentElement.removeAttribute('title');
            }
            const rng = document.getElementById(`npc_range_${r}`);
            const inp = document.getElementById(`npc_input_${r}`);
            if (rng) rng.disabled = false;
            if (inp) inp.disabled = false;

            const badge = document.getElementById(`npc_link_badge_${r}`);
            const row = document.getElementById(`npc_row_${r}`);

            if (r === selected) {
                if (badge) {
                    badge.classList.remove('d-none');
                    badge.innerHTML = `<i class="fa-solid fa-hourglass-half me-1"></i>En attente`;
                }
                if (row) {
                    row.classList.add('border-warning');
                    row.classList.remove('border-primary', 'bg-primary-subtle', 'opacity-75');
                }
            } else {
                if (badge) badge.classList.add('d-none');
                if (row) {
                    row.classList.remove('border-primary', 'border-warning', 'bg-primary-subtle', 'opacity-75');
                }
            }
        });

        if (banner && bannerText) {
            banner.className = 'alert alert-warning py-2 px-3 small mb-3 d-flex align-items-center gap-2';
            bannerText.innerHTML = `<span><i class="fa-solid fa-circle-exclamation me-1"></i>Cochez une seconde ressource pour lier leurs échelles et équilibrer le troc avec <strong>${NPC_RES_NAMES[selected]}</strong>.</span>`;
        }
    } else {
        // Aucune ressource cochée (mode libre standard)
        ALL_NPC_RESOURCES.forEach(r => {
            const chk = document.getElementById(`npc_check_${r}`);
            if (chk) {
                chk.disabled = false;
                chk.parentElement.removeAttribute('title');
            }
            const rng = document.getElementById(`npc_range_${r}`);
            const inp = document.getElementById(`npc_input_${r}`);
            if (rng) rng.disabled = false;
            if (inp) inp.disabled = false;

            const badge = document.getElementById(`npc_link_badge_${r}`);
            const row = document.getElementById(`npc_row_${r}`);
            if (badge) badge.classList.add('d-none');
            if (row) {
                row.classList.remove('border-primary', 'border-warning', 'bg-primary-subtle', 'opacity-75');
            }
        });

        if (banner && bannerText) {
            banner.className = 'alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2';
            bannerText.innerHTML = `<span><i class="fa-solid fa-circle-info me-1"></i>Cochez 2 ressources pour lier leurs échelles et équilibrer le troc automatiquement en miroir (1:1).</span>`;
        }
    }
}

/**
 * Traitement de l'ajustement d'une ressource avec équilibrage proportionnel 1:1
 */
function handleResourceAdjustment(type, desiredValue) {
    if (npcCheckedResources.length === 2 && npcCheckedResources.includes(type)) {
        // Mode Équilibrage 1:1 entre les 2 ressources cochées
        const otherType = (npcCheckedResources[0] === type) ? npcCheckedResources[1] : npcCheckedResources[0];

        // Calcul de la somme fixe des 9 autres ressources non sélectionnées
        let unselectedSum = 0;
        ALL_NPC_RESOURCES.forEach(r => {
            if (!npcCheckedResources.includes(r)) {
                unselectedSum += parseInt(document.getElementById(`npc_input_${r}`)?.value || 0, 10);
            }
        });

        // Le sous-total réservé pour les deux ressources liées
        const pool = currentNpcTotal - unselectedSum;

        // Capacités de stockage maximales
        const maxType = currentNpcMax[type] !== undefined ? currentNpcMax[type] : pool;
        const maxOther = currentNpcMax[otherType] !== undefined ? currentNpcMax[otherType] : pool;

        // Bornes garantissant :
        // 0 <= type <= maxType
        // 0 <= otherVal = (pool - type) <= maxOther  ==>  pool - maxOther <= type <= pool
        const minVal = Math.max(0, pool - maxOther);
        const maxVal = Math.min(pool, maxType);

        const clampedVal = Math.max(minVal, Math.min(maxVal, desiredValue));
        const otherVal = Math.max(0, pool - clampedVal);

        // Mise à jour de la ressource modifiée
        const inpType = document.getElementById(`npc_input_${type}`);
        const rngType = document.getElementById(`npc_range_${type}`);
        if (inpType) inpType.value = clampedVal;
        if (rngType) rngType.value = clampedVal;

        // Mise à jour en miroir de la ressource liée (diminution proportionnelle)
        const inpOther = document.getElementById(`npc_input_${otherType}`);
        const rngOther = document.getElementById(`npc_range_${otherType}`);
        if (inpOther) inpOther.value = otherVal;
        if (rngOther) rngOther.value = otherVal;

    } else {
        // Mode libre sans équilibrage miroir
        const inp = document.getElementById(`npc_input_${type}`);
        const rng = document.getElementById(`npc_range_${type}`);
        const max = currentNpcMax[type] !== undefined ? currentNpcMax[type] : currentNpcTotal;
        const clamped = Math.max(0, Math.min(max, desiredValue));
        if (inp) inp.value = clamped;
        if (rng) rng.value = clamped;
    }

    updateNpcDisplay();
}

function onNpcRangeChange(type) {
    const r = document.getElementById(`npc_range_${type}`);
    if (r) {
        const val = parseInt(r.value || 0, 10);
        handleResourceAdjustment(type, val);
    }
}

function onNpcInputChange(type) {
    const inp = document.getElementById(`npc_input_${type}`);
    if (inp) {
        let val = parseInt(inp.value || 0, 10);
        if (isNaN(val)) val = 0;
        handleResourceAdjustment(type, val);
    }
}

/**
 * Calcul des totaux et rafraîchissement de l'affichage
 */
function updateNpcDisplay() {
    let sum = 0;
    ALL_NPC_RESOURCES.forEach(res => {
        const inp = document.getElementById(`npc_input_${res}`);
        const val = parseInt(inp?.value || 0, 10);
        sum += val;

        const valSpan = document.getElementById(`npc_${res}_val`);
        if (valSpan) valSpan.innerText = val.toLocaleString('fr-FR');
    });

    const diff = currentNpcTotal - sum;
    const alertEl = document.getElementById('npc_diff_alert');
    const submitBtn = document.getElementById('btnSubmitNpcExchange');

    if (alertEl && submitBtn) {
        if (diff !== 0) {
            alertEl.classList.remove('d-none');
            alertEl.innerText = diff > 0 
                ? `Il reste ${diff.toLocaleString('fr-FR')} ressources à assigner.` 
                : `Excédent de ${Math.abs(diff).toLocaleString('fr-FR')} ressources réparties en trop.`;
            submitBtn.disabled = true;
        } else {
            alertEl.classList.add('d-none');
            submitBtn.disabled = false;
        }
    }
}

/**
 * Troc 1:1:1 : Répartition équitable en 3 tiers égaux sur les denrées majeures (Bois, Pierre, Riz)
 */
function distributeThirds() {
    const triad = ['wood', 'stone', 'rice'];
    let otherSum = 0;
    ALL_NPC_RESOURCES.forEach(r => {
        if (!triad.includes(r)) {
            const val = parseInt(initialNpcStocks[r] || 0, 10);
            otherSum += val;
            const inp = document.getElementById(`npc_input_${r}`);
            const rng = document.getElementById(`npc_range_${r}`);
            if (inp) inp.value = val;
            if (rng) rng.value = val;
        }
    });

    const pool = Math.max(0, currentNpcTotal - otherSum);
    const third = Math.floor(pool / 3);
    let rem = pool;

    triad.forEach((res, idx) => {
        const isLast = (idx === triad.length - 1);
        let val = isLast ? rem : third;
        const max = currentNpcMax[res] || val;
        val = Math.min(val, max);
        rem -= val;

        const inp = document.getElementById(`npc_input_${res}`);
        const rng = document.getElementById(`npc_range_${res}`);
        if (inp) inp.value = val;
        if (rng) rng.value = val;
    });

    npcCheckedResources = [];
    updateNpcCheckState();
    updateNpcDisplay();
}

/**
 * Répartition équitable des ressources
 */
function distributeEvenly() {
    if (npcCheckedResources.length === 2) {
        // Répartir à 50/50 le pool disponible entre les deux ressources cochées
        const [r1, r2] = npcCheckedResources;
        let unselectedSum = 0;
        ALL_NPC_RESOURCES.forEach(r => {
            if (!npcCheckedResources.includes(r)) {
                unselectedSum += parseInt(document.getElementById(`npc_input_${r}`)?.value || 0, 10);
            }
        });
        const pool = currentNpcTotal - unselectedSum;
        const half = Math.floor(pool / 2);
        const otherHalf = pool - half;

        handleResourceAdjustment(r1, half);
        return;
    }

    // Répartition équitable générale sur les ressources dont le stockage le permet
    const count = ALL_NPC_RESOURCES.length;
    const part = Math.floor(currentNpcTotal / count);
    let remaining = currentNpcTotal;

    ALL_NPC_RESOURCES.forEach((res, idx) => {
        const isLast = (idx === count - 1);
        let val = isLast ? remaining : part;
        const max = currentNpcMax[res] || val;
        val = Math.min(val, max);
        remaining -= val;

        const inp = document.getElementById(`npc_input_${res}`);
        const rng = document.getElementById(`npc_range_${res}`);
        if (inp) inp.value = val;
        if (rng) rng.value = val;
    });

    updateNpcDisplay();
}

/**
 * Rétablir les stocks initiaux enregistrés à l'ouverture de la modale
 */
function resetInitialStocks() {
    ALL_NPC_RESOURCES.forEach(res => {
        const val = initialNpcStocks[res] || 0;
        const inp = document.getElementById(`npc_input_${res}`);
        const rng = document.getElementById(`npc_range_${res}`);
        if (inp) inp.value = val;
        if (rng) rng.value = val;
    });
    npcCheckedResources = [];
    updateNpcCheckState();
    updateNpcDisplay();
}

/**
 * Notification modale interne
 */
function showModalAlert(message, type) {
    if (typeof showTablerToast === 'function') {
        showTablerToast(message, type, 'Intendant du Marché');
    } else {
        alert(message);
    }
}

/**
 * Soumission de l'ordre de troc au serveur
 */
async function submitNpcExchange() {
    const btn = document.getElementById('btnSubmitNpcExchange');
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'npc_exchange');
    fd.append('planet_id', currentNpcPlanetId);

    ALL_NPC_RESOURCES.forEach(res => {
        const val = parseInt(document.getElementById(`npc_input_${res}`)?.value || 0, 10);
        fd.append(res, val);
    });

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (pe) {
            showModalAlert("Erreur serveur : " + (text.replace(/<[^>]*>?/gm, '').trim().substring(0, 180) || 'Réponse inattendue.'), 'error');
            if (btn) btn.disabled = false;
            return;
        }

        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du troc.', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec les intendants du marché.', 'error');
        if (btn) btn.disabled = false;
    }
}

<?php if (!empty($isInlineNpcExchange) && !empty($planet)): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php 
    $npcInlinePayload = isset($npcPlanetData) ? $npcPlanetData : (isset($sealEngine) ? $sealEngine->getNpcPlanetResourceData($planet) : null);
    ?>
    if (typeof openNpcExchangeModal === 'function') {
        openNpcExchangeModal(
            <?= (int)$planet['id'] ?>, 
            <?= json_encode($planet['name']) ?>, 
            <?= json_encode($npcInlinePayload['resources'] ?? []) ?>, 
            <?= json_encode($npcInlinePayload['maxes'] ?? []) ?>
        );
    }
});
<?php endif; ?>
</script>
