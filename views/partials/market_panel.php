<?php
/**
 * Panel du Marché Féodal & Commerce en Cours (OpenShogun)
 * Widget mutualisé affiché dans la colonne Est (Ressources, Cité, Carte)
 * Présente les offres actives sur la Bourse P2P, les convois de marchands en transit et les routes commerciales.
 */
require_once __DIR__ . '/../../core/MarketEngine.php';

$marketEnginePanel = new MarketEngine();
$panelPlanetId = (int)($planet['id'] ?? 0);
$panelUserId   = (int)($user['id'] ?? 0);

$marketSummary = $marketEnginePanel->getOngoingMarketSummary($panelPlanetId, $panelUserId);

$marketLevel       = $marketSummary['market_level'];
$isMarketBuilt     = $marketSummary['is_market_built'];
$maxOfferSlots     = $marketSummary['max_offer_slots'];
$activeOffers      = $marketSummary['active_offers'];
$activeOffersCount = $marketSummary['active_offers_count'];
$convoys           = $marketSummary['transport_convoys'];
$convoysCount      = $marketSummary['transport_convoys_count'];
$tradeRoutes       = $marketSummary['trade_routes'];
$tradeRoutesCount  = $marketSummary['trade_routes_count'];
$transporters      = $marketSummary['transporters'];
?>

<div class="market-ongoing-panel font-sans">

    <!-- ── 1. BANDEAU DE STATUT DU MARCHÉ ── -->
    <div class="p-2.5 rounded border mb-3 bg-light shadow-2xs">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm rounded <?= $isMarketBuilt ? 'bg-warning-lt text-warning' : 'bg-secondary-lt text-secondary' ?>" style="width:32px; height:32px; font-size:1rem;">
                    <i class="fa-solid fa-scale-balanced"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark small leading-tight">
                        <?= $isMarketBuilt ? 'Marché Féodal (Niveau ' . $marketLevel . ')' : 'Marché Non Bâti' ?>
                    </div>
                    <div class="text-muted" style="font-size:0.72rem;">
                        <?php if ($isMarketBuilt): ?>
                            <span>Étals de troc : <strong class="<?= ($activeOffersCount >= $maxOfferSlots) ? 'text-danger' : 'text-success' ?>"><?= $activeOffersCount ?>/<?= $maxOfferSlots ?></strong></span>
                            &bull; <span>Fret : <strong><?= $transporters['total_capacity_formatted'] ?></strong></span>
                        <?php else: ?>
                            <span>Requis pour commercer et troquer</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($isMarketBuilt): ?>
                <button type="button" 
                        class="btn btn-sm btn-outline-warning text-dark font-monospace py-1 px-2" 
                        style="font-size:0.72rem;"
                        onclick="openEastMarketQuickAccess()" 
                        title="Ouvrir la Bourse P2P & le Marché">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ouvrir
                </button>
            <?php else: ?>
                <a href="/?page=city" 
                   class="btn btn-sm btn-primary font-monospace py-1 px-2" 
                   style="font-size:0.72rem;">
                    <i class="fa-solid fa-hammer me-1"></i>Construire
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$isMarketBuilt): ?>
            <div class="alert alert-warning py-1.5 px-2 mt-2 mb-0 small" style="font-size:0.75rem;">
                <i class="fa-solid fa-circle-info me-1"></i>Érigez un <strong>Marché</strong> dans votre Cité Castrale pour débloquer la bourse de troc et envoyer des convois marchands.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 2. OFFRES DE TROC ACTIVES (BOURSE P2P) ── -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-handshake text-warning me-1"></i>Mes Offres en Attente (<?= $activeOffersCount ?>/<?= $maxOfferSlots ?>)
            </span>
            <?php if ($isMarketBuilt && $activeOffersCount < $maxOfferSlots): ?>
                <button type="button" 
                        class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" 
                        style="font-size:0.7rem;"
                        onclick="openEastMarketQuickAccess('tab-p2p-btn')">
                    <i class="fa-solid fa-plus me-0.5"></i>Déposer
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($activeOffers)): ?>
            <div class="border rounded p-2.5 bg-white text-center text-muted small" style="font-size:0.78rem;">
                <i class="fa-solid fa-scale-balanced text-muted opacity-50 mb-1 fs-3 d-block"></i>
                <span>Aucune offre de troc active déposée sur ce fief.</span>
                <?php if ($isMarketBuilt): ?>
                    <div class="mt-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size:0.72rem;" onclick="openEastMarketQuickAccess('tab-p2p-btn')">
                            Proposer un échange &rarr;
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($activeOffers as $off): ?>
                    <div class="border rounded p-2 bg-white shadow-2xs position-relative" id="east-offer-row-<?= (int)$off['id'] ?>">
                        <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                            <span class="badge bg-secondary-lt font-monospace text-secondary" style="font-size:0.65rem;">
                                Offre #<?= (int)$off['id'] ?> &bull; Séquestre
                            </span>
                            <button type="button" 
                                    class="btn btn-sm btn-ghost-danger py-0 px-1 text-danger" 
                                    style="font-size:0.68rem;" 
                                    onclick="cancelEastMarketOffer(<?= (int)$off['id'] ?>)" 
                                    title="Annuler cette offre et récupérer les ressources bloquées">
                                <i class="fa-solid fa-times me-0.5"></i>Annuler
                            </button>
                        </div>

                        <!-- Troc Visuel : Offre -> Demande -->
                        <div class="d-flex align-items-center justify-content-between gap-1 p-1.5 rounded bg-surface-secondary" style="font-size:0.76rem;">
                            <!-- Vous donnez -->
                            <div class="d-flex align-items-center gap-1 text-truncate" style="max-width:44%;">
                                <i class="<?= htmlspecialchars($off['offer_meta']['icon'] ?? 'fa-solid fa-box') ?>"></i>
                                <span class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($off['offer_meta']['name']) ?>">
                                    <?= $off['offer_amount_formatted'] ?>
                                </span>
                            </div>

                            <i class="fa-solid fa-arrow-right text-muted opacity-75" style="font-size:0.7rem;"></i>

                            <!-- Vous recevez -->
                            <div class="d-flex align-items-center gap-1 justify-content-end text-truncate" style="max-width:44%;">
                                <span class="fw-bold text-success text-truncate" title="<?= htmlspecialchars($off['demand_meta']['name']) ?>">
                                    <?= $off['demand_amount_formatted'] ?>
                                </span>
                                <i class="<?= htmlspecialchars($off['demand_meta']['icon'] ?? 'fa-solid fa-box') ?>"></i>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-1 text-muted font-monospace" style="font-size:0.68rem;">
                            <span>Ratio : <strong>1 : <?= (float)$off['ratio'] ?></strong></span>
                            <span><?= date('d/m H:i', strtotime($off['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 3. CONVOIS DE MARCHANDS EN TRANSIT ── -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-truck-moving text-info me-1"></i>Convois en Transit (<?= $convoysCount ?>)
            </span>
            <?php if ($isMarketBuilt): ?>
                <button type="button" 
                        class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" 
                        style="font-size:0.7rem;"
                        onclick="openEastMarketQuickAccess('tab-dispatch-btn')">
                    <i class="fa-solid fa-paper-plane me-0.5"></i>Affréter
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($convoys)): ?>
            <div class="border rounded p-2.5 bg-white text-center text-muted small" style="font-size:0.78rem;">
                <i class="fa-solid fa-caravan text-muted opacity-50 mb-1 fs-3 d-block"></i>
                <span>Aucune caravane marchande en marche actuellement.</span>
                <?php if ($isMarketBuilt): ?>
                    <div class="mt-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" style="font-size:0.72rem;" onclick="openEastMarketQuickAccess('tab-dispatch-btn')">
                            Expédier des vivres &rarr;
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($convoys as $cvy): 
                    $dir = $cvy['direction'];
                    $dirBadge = match ($dir) {
                        'outgoing' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-solid fa-arrow-right-long', 'label' => 'Sortant (Aller)'],
                        'returning' => ['class' => 'bg-info text-white', 'icon' => 'fa-solid fa-arrow-rotate-left', 'label' => 'Retour à vide'],
                        'incoming' => ['class' => 'bg-success text-white', 'icon' => 'fa-solid fa-truck-ramp-box', 'label' => 'Entrant (Arrivée)'],
                        default => ['class' => 'bg-secondary text-white', 'icon' => 'fa-solid fa-truck', 'label' => 'En marche']
                    };
                ?>
                    <div class="border rounded p-2 bg-white shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                            <span class="badge <?= $dirBadge['class'] ?> font-monospace py-0.5 px-1.5" style="font-size:0.65rem;">
                                <i class="<?= $dirBadge['icon'] ?> me-1"></i><?= $dirBadge['label'] ?>
                            </span>
                            <span class="badge bg-dark border text-light font-monospace py-0.5 px-1.5 market-convoy-countdown" 
                                  data-seconds="<?= (int)$cvy['seconds_remaining'] ?>"
                                  style="font-size:0.68rem;">
                                <i class="fa-regular fa-clock me-1 text-warning"></i><span>--:--:--</span>
                            </span>
                        </div>

                        <!-- Cible et Trajet -->
                        <div class="small fw-bold text-dark text-truncate mb-1" style="font-size:0.75rem;">
                            <?php if ($dir === 'outgoing'): ?>
                                <span>Vers : <strong><?= htmlspecialchars($cvy['target_planet_name']) ?></strong></span>
                                <span class="text-muted font-monospace small"><?= $cvy['target_coords'] ?></span>
                            <?php elseif ($dir === 'returning'): ?>
                                <span>Retour vers : <strong><?= htmlspecialchars($cvy['source_planet_name']) ?></strong></span>
                            <?php else: ?>
                                <span>Depuis : <strong><?= htmlspecialchars($cvy['source_planet_name']) ?></strong> (<?= htmlspecialchars($cvy['source_username']) ?>)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Cargaison transportée -->
                        <?php if (!empty($cvy['cargo'])): ?>
                            <div class="d-flex gap-1 flex-wrap p-1 rounded bg-surface-secondary mb-1">
                                <?php foreach ($cvy['cargo'] as $resKey => $cg): ?>
                                    <span class="badge bg-white text-dark border font-monospace py-0.5 px-1" style="font-size:0.68rem;">
                                        <i class="<?= htmlspecialchars($cg['meta']['icon']) ?> me-1"></i><?= $cg['formatted'] ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($dir === 'returning'): ?>
                            <div class="text-muted fst-italic p-1" style="font-size:0.68rem;">
                                <i class="fa-solid fa-boxes-packing me-1"></i>Cargaison livrée &bull; Chariots sur le chemin du retour.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 4. ROUTES COMMERCIALES AUTOMATISÉES (SCEAU IMPÉRIAL) ── -->
    <?php if (!empty($tradeRoutes)): ?>
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-arrows-spin text-purple me-1"></i>Routes Commerciales (<?= $tradeRoutesCount ?>)
            </span>
            <button type="button" 
                    class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" 
                    style="font-size:0.7rem;"
                    onclick="openEastMarketQuickAccess('tab-routes-btn')">
                <i class="fa-solid fa-gear me-0.5"></i>Gérer
            </button>
        </div>

        <div class="d-flex flex-column gap-1.5">
            <?php foreach ($tradeRoutes as $rt): 
                $isSource = ((int)$rt['source_planet_id'] === $panelPlanetId);
                $partnerName = $isSource ? $rt['target_planet_name'] : $rt['source_planet_name'];
                $partnerCoords = $isSource ? "[{$rt['tx']}:{$rt['ty']}]" : "[{$rt['sx']}:{$rt['sy']}]";
            ?>
                <div class="border rounded p-2 bg-white shadow-2xs font-monospace small" style="font-size:0.72rem;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark text-truncate">
                            <i class="fa-solid <?= $isSource ? 'fa-arrow-right text-warning' : 'fa-arrow-left text-info' ?> me-1"></i><?= htmlspecialchars($partnerName) ?> <?= $partnerCoords ?>
                        </span>
                        <span class="badge bg-purple-lt text-purple py-0.5 px-1" style="font-size:0.65rem;">
                            Toutes les <?= (int)$rt['interval_hours'] ?>h
                        </span>
                    </div>
                    <div class="d-flex justify-content-between text-muted" style="font-size:0.68rem;">
                        <span>Fret : <strong><?= number_format((int)$rt['total_cargo']) ?></strong></span>
                        <span>Prochaine : <strong class="text-dark"><?= date('H:i', strtotime($rt['next_run_at'])) ?></strong></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── 5. PIED D'ACTION & LIENS DIRECTS ── -->
    <div class="d-grid gap-1.5 pt-1 border-top">
        <button type="button" 
                class="btn btn-sm btn-warning text-dark fw-bold shadow-2xs d-flex align-items-center justify-content-center gap-1.5"
                onclick="openEastMarketQuickAccess()">
            <i class="fa-solid fa-scale-balanced"></i>
            <span>Accéder au Marché Castral</span>
        </button>
        <button type="button" 
                class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center gap-1.5"
                onclick="openEastMarketQuickAccess('tab-dispatch-btn')">
            <i class="fa-solid fa-paper-plane text-info"></i>
            <span>Affréter un Convoi de Ravitaillement</span>
        </button>
    </div>

</div>

<!-- Script de décompte en direct et interactions rapides pour le panel Marché -->
<script>
(function() {
    // 1. Décompte en temps réel des convois de marchands
    function tickEastMarketCountdowns() {
        document.querySelectorAll('.market-convoy-countdown').forEach(el => {
            let sec = parseInt(el.dataset.seconds, 10);
            if (isNaN(sec)) return;
            if (sec > 0) {
                sec--;
                el.dataset.seconds = sec;
            }
            const hours = String(Math.floor(sec / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
            const seconds = String(sec % 60).padStart(2, '0');
            const span = el.querySelector('span');
            if (span) {
                span.textContent = (sec <= 0) ? 'Arrivé !' : `${hours}:${minutes}:${seconds}`;
            }
        });
    }
    setInterval(tickEastMarketCountdowns, 1000);
    tickEastMarketCountdowns();

    // 2. Navigation rapide vers la modale ou la page Marché
    window.openEastMarketQuickAccess = function(tabTargetId = null) {
        // Si nous sommes déjà sur la page city.php avec les slots de la cité
        if (typeof openBuildingSlotModalByCode === 'function') {
            openBuildingSlotModalByCode('market');
            if (tabTargetId) {
                setTimeout(() => {
                    const tabBtn = document.getElementById(tabTargetId);
                    if (tabBtn) {
                        const tabTrigger = new bootstrap.Tab(tabBtn);
                        tabTrigger.show();
                    }
                }, 350);
            }
            return;
        }

        // Si nous sommes sur resources.php ou map.php, rediriger vers la cité
        window.location.href = '/?page=city';
    };

    // 3. Annulation rapide d'une offre en direct depuis la colonne latérale
    window.cancelEastMarketOffer = function(offerId) {
        if (!confirm(`Voulez-vous vraiment annuler l'offre de troc #${offerId} et récupérer vos ressources consignées ?`)) {
            return;
        }
        const fd = new FormData();
        fd.append('action', 'cancel_offer');
        fd.append('offer_id', offerId);

        fetch('/api/market.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof showModalAlert === 'function') {
                        showModalAlert(data.message || 'Offre annulée avec succès.', 'success');
                    } else {
                        alert(data.message || 'Offre annulée avec succès.');
                    }
                    const row = document.getElementById(`east-offer-row-${offerId}`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.95)';
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        window.location.reload();
                    }
                } else {
                    if (typeof showModalAlert === 'function') {
                        showModalAlert(data.error || 'Erreur lors de l\'annulation.', 'error');
                    } else {
                        alert(data.error || 'Erreur.');
                    }
                }
            })
            .catch(() => alert('Erreur de transmission avec les intendants du marché.'));
    };
})();
</script>
