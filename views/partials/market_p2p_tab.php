<?php
/**
 * Vue partielle : Onglet Bourse Féodale (Troc P2P)
 * Débloqué au Marché Niveau 10
 */
?>
<!-- ONGLET 4 : BOURSE FÉODALE (TROC P2P & ESCROW) -->
<div class="tab-pane" id="tab-market-p2p" role="tabpanel">
    <?php if ($lvl < 10): ?>
    <div class="border rounded p-4 text-center bg-light-lt border-dashed">
        <div class="mb-3">
            <span class="avatar avatar-xl rounded-circle bg-secondary-lt text-secondary">
                <i class="fa-solid fa-lock fs-1"></i>
            </span>
        </div>
        <h3 class="fw-bold text-dark mb-1">Bourse Féodale de Troc Privé &bull; Niveau 10 Requis</h3>
        <p class="text-secondary small mx-auto mb-4" style="max-width: 620px;">
            À partir du <strong>Niveau 10 du Marché</strong>, vos marchands établissent des comptoirs permanents pour déposer des offres de troc avec les autres seigneurs du Japon féodal. Vous pourrez fixer vos propres ratios d'échange (ex. <em>500 Argile contre 1 000 Bois</em>). Les ressources mises en vente sont alors immédiatement consignées en coffre sécurisé (séquestre) jusqu'à ce que l'échange soit conclu ou annulé.
        </p>

        <div class="mx-auto" style="max-width: 360px;">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Niveau du Marché actuel</span>
                <strong><?= $lvl ?> / 10</strong>
            </div>
            <div class="progress mb-3" style="height: 10px;">
                <div class="progress-bar bg-warning" style="width: <?= min(100, ($lvl / 10) * 100) ?>%;"></div>
            </div>
            <a href="#buildActionBtn" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm" onclick="document.getElementById('buildActionBtn')?.scrollIntoView({behavior:'smooth'})">
                <i class="fa-solid fa-arrow-up me-1"></i>Améliorer le Marché au Niveau <?= $lvl + 1 ?>
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- BOURSE ACTIVE : DÉBLOCAGE NIVEAU 10+ -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
        <div>
            <h4 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <span><i class="fa-solid fa-handshake text-success"></i></span> Bourse Féodale des Seigneurs (Troc P2P)
            </h4>
            <div class="text-secondary small mt-1">
                Déposez des offres de troc à ratio libre ou concluez les offres proposées par les daimyōs du shogunat.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple-lt fw-bold">
                <i class="fa-solid fa-coins me-1"></i>Slots d'offres : <?= $p2pMyActiveOffersCount ?> / <?= $p2pMaxSlots ?>
            </span>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshP2POffers()">
                <i class="fa-solid fa-arrows-rotate me-1"></i>Actualiser
            </button>
        </div>
    </div>

    <!-- 1. FORMULAIRE DE DÉPÔT D'OFFRE -->
    <div class="card mb-4 border-success-lt shadow-sm" style="border-left: 4px solid #16a34a;">
        <div class="card-header bg-light py-2">
            <h4 class="card-title text-success d-flex align-items-center gap-2 m-0" style="font-size:0.95rem;">
                <i class="fa-solid fa-plus-circle"></i> Déposer une Offre d'Échange avec Séquestre
            </h4>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <!-- COLONNE GAUCHE : CE QUE JE CÈDE (OFFRE) -->
                <div class="col-md-5">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-danger m-0">
                                <i class="fa-solid fa-arrow-up-from-bracket me-1"></i>Ressource à Céder (Bloquée) :
                            </label>
                            <span class="small text-muted" id="p2p_offer_stock_badge">Stock : --</span>
                        </div>
                        <div class="mb-3">
                            <select id="p2p_offer_res" class="form-select fw-bold" onchange="onP2POfferResChange()">
                                <?php foreach ($p2pTradables as $k => $t): ?>
                                    <option value="<?= $k ?>" <?= ($k === 'clay') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-bold text-secondary mb-1">Quantité à bloquer dans l'offre :</label>
                            <div class="input-group mb-2">
                                <input type="number" id="p2p_offer_amount" class="form-control fw-bold text-center" min="1" step="50" value="500" oninput="updateP2PRatioPreview()">
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2POfferQuick(0.25)">25%</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2POfferQuick(0.5)">50%</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2POfferQuick(1)">Max</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE CENTRALE : RATIO D'ÉCHANGE -->
                <div class="col-md-2 d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="fs-2 text-warning mb-1">
                        <i class="fa-solid fa-right-left"></i>
                    </div>
                    <div class="small fw-bold text-muted mb-1">Ratio d'Échange</div>
                    <div class="badge bg-secondary-lt fs-5 px-3 py-1 font-monospace" id="p2p_ratio_display">1 : 2.0</div>
                    <div class="small text-muted mt-1" id="p2p_ratio_hint" style="font-size:0.75rem;">1 Cédé &rarr; 2 Reçu</div>
                </div>

                <!-- COLONNE DROITE : CE QUE JE DEMANDE -->
                <div class="col-md-5">
                    <div class="p-3 border rounded bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold text-success m-0">
                                <i class="fa-solid fa-arrow-down-to-bracket me-1"></i>Ressource Demandée en Retour :
                            </label>
                            <span class="small text-muted">À livrer par l'acquéreur</span>
                        </div>
                        <div class="mb-3">
                            <select id="p2p_demand_res" class="form-select fw-bold" onchange="onP2PDemandResChange()">
                                <?php foreach ($p2pTradables as $k => $t): ?>
                                    <option value="<?= $k ?>" <?= ($k === 'metal') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-bold text-secondary mb-1">Quantité souhaitée :</label>
                            <div class="input-group mb-2">
                                <input type="number" id="p2p_demand_amount" class="form-control fw-bold text-center" min="1" step="50" value="1000" oninput="updateP2PRatioPreview()">
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2PDemandRatio(1)">1:1</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2PDemandRatio(1.5)">1:1.5</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setP2PDemandRatio(2)">1:2</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ALERTE SÉQUESTRE & BOUTON DÉPOSER -->
            <div class="alert alert-info py-2 px-3 small mt-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved fs-4 text-info"></i>
                    <div>
                        <strong>Protection sous Séquestre Immédiat :</strong> Les ressources cédées seront déduites dès maintenant de votre fief et bloquées dans un coffre protégé. Vous pouvez annuler cette offre à tout instant pour récupérer immédiatement vos biens.
                    </div>
                </div>
                <div class="text-end">
                    <span class="text-muted" style="font-size:0.75rem;">Capacité restante : <?= $p2pMaxSlots - $p2pMyActiveOffersCount ?> slot(s)</span>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-success fw-bold px-4 py-2 shadow-sm" id="btnSubmitP2POffer" onclick="submitP2POffer()" <?= ($p2pMyActiveOffersCount >= $p2pMaxSlots) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-lock me-1"></i>Bloquer les Ressources & Déposer l'Offre
                </button>
            </div>
        </div>
    </div>

    <!-- 2. MES OFFRES EN COURS (SOUS SÉQUESTRE) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h4 class="card-title text-dark m-0" style="font-size:0.95rem;">
                <i class="fa-solid fa-box-archive text-warning me-1"></i>Mes Offres Déposées &amp; Ressources Bloquées
            </h4>
            <span class="badge bg-secondary-lt"><?= count($p2pMyOffers) ?> offre(s) enregistrée(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-sm card-table mb-0">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Fief Origine</th>
                        <th>Ressources Bloquées (Offre)</th>
                        <th>Demande Attendue</th>
                        <th>Ratio</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="p2p_my_offers_tbody">
                    <?php if (empty($p2pMyOffers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Vous n'avez aucune offre de troc active ou récente.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($p2pMyOffers as $mo): 
                            $offDef = $p2pTradables[$mo['offer_res']] ?? ['name' => $mo['offer_res'], 'icon' => 'fa-solid fa-box'];
                            $demDef = $p2pTradables[$mo['demand_res']] ?? ['name' => $mo['demand_res'], 'icon' => 'fa-solid fa-box'];
                        ?>
                        <tr>
                            <td class="font-monospace text-muted">#<?= $mo['id'] ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($mo['planet_name']) ?></span></td>
                            <td>
                                <strong class="text-danger">
                                    -<?= number_format($mo['offer_amount']) ?> 
                                    <i class="<?= $offDef['icon'] ?> ms-1"></i> <?= htmlspecialchars($offDef['name']) ?>
                                </strong>
                            </td>
                            <td>
                                <strong class="text-success">
                                    +<?= number_format($mo['demand_amount']) ?> 
                                    <i class="<?= $demDef['icon'] ?> ms-1"></i> <?= htmlspecialchars($demDef['name']) ?>
                                </strong>
                            </td>
                            <td class="font-monospace fw-bold">
                                1 : <?= (float)$mo['ratio'] ?>
                            </td>
                            <td>
                                <?php if ($mo['status'] === 'active'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i>Bloqué en vente</span>
                                <?php elseif ($mo['status'] === 'completed'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Conclu</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>Annulé</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('d/m H:i', strtotime($mo['created_at'])) ?></td>
                            <td class="text-end">
                                <?php if ($mo['status'] === 'active'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelP2POffer(<?= (int)$mo['id'] ?>)">
                                        <i class="fa-solid fa-unlock me-1"></i>Annuler & Récupérer
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. BOURSE DES DAIMYŌS (OFFRES DISPONIBLES DANS LE SHOGUNAT) -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <h4 class="card-title text-dark m-0" style="font-size:0.95rem;">
                <i class="fa-solid fa-landmark text-primary me-1"></i>Bourse des Daimyōs &bull; Offres Disponibles dans le Shogunat
            </h4>
            <div class="d-flex align-items-center gap-2">
                <select id="p2p_filter_offer" class="form-select form-select-sm" style="width: auto;" onchange="refreshP2POffers()">
                    <option value="">-- Toute ressource offerte --</option>
                    <?php foreach ($p2pTradables as $k => $t): ?>
                        <option value="<?= $k ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="p2p_filter_demand" class="form-select form-select-sm" style="width: auto;" onchange="refreshP2POffers()">
                    <option value="">-- Toute ressource demandée --</option>
                    <?php foreach ($p2pTradables as $k => $t): ?>
                        <option value="<?= $k ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table mb-0">
                <thead>
                    <tr>
                        <th>Offre N°</th>
                        <th>Seigneur Émetteur</th>
                        <th>Vous Recevez (Offert)</th>
                        <th>Vous Donnez (Demandé)</th>
                        <th>Taux d'Échange</th>
                        <th>Votre Stock</th>
                        <th class="text-end">Commerce</th>
                    </tr>
                </thead>
                <tbody id="p2p_active_offers_tbody">
                    <?php if (empty($p2pActiveOffers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Aucune offre n'est actuellement déposée sur la Bourse Féodale. Soyez le premier à déposer vos denrées !
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($p2pActiveOffers as $o):
                            $offDef = $p2pTradables[$o['offer_res']] ?? ['name' => $o['offer_res'], 'icon' => 'fa-solid fa-box'];
                            $demDef = $p2pTradables[$o['demand_res']] ?? ['name' => $o['demand_res'], 'icon' => 'fa-solid fa-box'];
                            $myStockForDemand = (float)($planet[$demDef['col'] ?? $o['demand_res']] ?? 0);
                            $canAccept = ($myStockForDemand >= (int)$o['demand_amount']) && ((int)$o['user_id'] !== (int)$user['id'] || (int)$o['planet_id'] !== (int)$planet['id']);
                            $isMine = ((int)$o['user_id'] === (int)$user['id']);
                        ?>
                        <tr class="<?= $isMine ? 'table-warning-lt' : '' ?>">
                            <td class="font-monospace text-muted">#<?= $o['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <strong class="text-dark"><?= htmlspecialchars($o['seller_name']) ?></strong>
                                    <?php if ($isMine): ?>
                                        <span class="badge bg-warning-lt text-warning">Vous</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small"><?= htmlspecialchars($o['seller_planet_name']) ?> [<?= $o['seller_x'] ?>|<?= $o['seller_y'] ?>]</div>
                            </td>
                            <td>
                                <span class="badge bg-success text-white fs-6 py-1 px-2">
                                    +<?= number_format($o['offer_amount']) ?> 
                                    <i class="<?= $offDef['icon'] ?> ms-1"></i>
                                </span>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($offDef['name']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-danger-lt text-danger fs-6 py-1 px-2">
                                    -<?= number_format($o['demand_amount']) ?> 
                                    <i class="<?= $demDef['icon'] ?> ms-1"></i>
                                </span>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($demDef['name']) ?></div>
                            </td>
                            <td class="font-monospace">
                                <strong>1 : <?= (float)$o['ratio'] ?></strong>
                            </td>
                            <td>
                                <div class="small <?= ($myStockForDemand >= (int)$o['demand_amount']) ? 'text-success fw-bold' : 'text-danger' ?>">
                                    <?= number_format((int)$myStockForDemand) ?> en réserve
                                </div>
                            </td>
                            <td class="text-end">
                                <?php if ($isMine && (int)$o['planet_id'] === (int)$planet['id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelP2POffer(<?= (int)$o['id'] ?>)">
                                        <i class="fa-solid fa-unlock me-1"></i>Annuler
                                    </button>
                                <?php elseif ($canAccept): ?>
                                    <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="acceptP2POffer(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($offDef['name'])) ?>', <?= (int)$o['offer_amount'] ?>, '<?= htmlspecialchars(addslashes($demDef['name'])) ?>', <?= (int)$o['demand_amount'] ?>)">
                                        <i class="fa-solid fa-handshake me-1"></i>Échanger
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary disabled" title="Stock insuffisant pour payer la demande">
                                        <i class="fa-solid fa-ban me-1"></i>Insuffisant
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

