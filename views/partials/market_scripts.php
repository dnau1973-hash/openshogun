<?php
/**
 * Vue Partielle Mutualisée : Scripts JavaScript du Marché Féodal (P2P, Caravanes, Bourse)
 * Utilisé dans : views/building.php, views/city.php
 */
?>
<script>
var currentPlanetCoords = {
    x: <?= (int)($planet['coord_x'] ?? 0) ?>,
    y: <?= (int)($planet['coord_y'] ?? 0) ?>,
    planetId: <?= (int)($planet['id'] ?? 0) ?>,
    planetName: <?= json_encode($planet['name'] ?? 'Fief Principal', JSON_UNESCAPED_UNICODE) ?>
};

var availableCargoStocks = {
    wood: <?= (int)($planet['metal'] ?? 0) ?>,
    stone: <?= (int)($planet['crystal'] ?? 0) ?>,
    rice: <?= (int)($planet['deuterium'] ?? 0) ?>
};

var availableTransporters = {
    light: <?= (int)($availLight ?? 0) ?>,
    heavy: <?= (int)($availHeavy ?? 0) ?>,
    totalCap: <?= (int)($totalCargoCapacity ?? 0) ?>
};

var p2pPlanetStockMap = <?= json_encode([
    'metal' => (float)($planet['metal'] ?? 0),
    'crystal' => (float)($planet['crystal'] ?? 0),
    'deuterium' => (float)($planet['deuterium'] ?? 0),
    'clay' => (float)($planet['clay'] ?? 0),
    'wooden_beams' => (float)($planet['wooden_beams'] ?? 0),
    'charcoal' => (float)($planet['charcoal'] ?? 0),
    'rice_flour' => (float)($planet['rice_flour'] ?? 0),
    'sake' => (float)($planet['sake'] ?? 0),
    'sulfur' => (float)($planet['sulfur'] ?? 0),
    'nitrate' => (float)($planet['nitrate'] ?? 0),
    'iron_sand' => (float)($planet['iron_sand'] ?? 0),
], JSON_UNESCAPED_UNICODE) ?>;

// ==========================================
// EXPÉDITION DE CONVOIS DE MARCHANDISES
// ==========================================
function toggleMarketDestMode() {
    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;
    const selectBox = document.getElementById('mkt_select_village_box');
    const manualBox = document.getElementById('mkt_manual_coords_box');
    if (isManual) {
        selectBox?.classList.add('d-none');
        manualBox?.classList.remove('d-none');
    } else {
        selectBox?.classList.remove('d-none');
        manualBox?.classList.add('d-none');
    }
    updateMarketCalculations();
}

function setMarketResAmount(res, amount) {
    const input = document.getElementById(`mkt_${res}`);
    if (!input) return;
    const maxVal = availableCargoStocks[res] || 0;
    if (amount === 'max') {
        input.value = maxVal;
    } else if (typeof amount === 'number') {
        if (amount === 0) {
            input.value = 0;
        } else {
            const current = parseInt(input.value || 0, 10);
            input.value = Math.min(maxVal, current + amount);
        }
    }
    updateMarketCalculations();
}

function updateMarketCalculations() {
    const wood = Math.max(0, parseInt(document.getElementById('mkt_wood')?.value || 0, 10));
    const stone = Math.max(0, parseInt(document.getElementById('mkt_stone')?.value || 0, 10));
    const rice = Math.max(0, parseInt(document.getElementById('mkt_rice')?.value || 0, 10));
    const totalCargo = wood + stone + rice;

    const totalCargoDisplay = document.getElementById('mkt_total_cargo_display');
    const vehiclesNeededDisplay = document.getElementById('mkt_vehicles_needed');
    const capMobilizedDisplay = document.getElementById('mkt_cap_mobilized_display');
    const fuelNeededDisplay = document.getElementById('mkt_fuel_needed_display');
    const durationDisplay = document.getElementById('mkt_duration_display');
    const alertStatus = document.getElementById('mkt_alert_status');
    const btnDispatch = document.getElementById('btnDispatchMarketCargo');

    if (!totalCargoDisplay) return;

    totalCargoDisplay.innerText = totalCargo.toLocaleString('fr-FR');

    // Récupérer les coordonnées cibles
    let targetX = null;
    let targetY = null;
    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;

    if (isManual) {
        const inpX = document.getElementById('mkt_target_x')?.value;
        const inpY = document.getElementById('mkt_target_y')?.value;
        if (inpX !== '' && inpY !== '' && !isNaN(inpX) && !isNaN(inpY)) {
            targetX = parseInt(inpX, 10);
            targetY = parseInt(inpY, 10);
        }
    } else {
        const sel = document.getElementById('mkt_target_planet_select');
        if (sel && sel.selectedIndex >= 0) {
            const opt = sel.options[sel.selectedIndex];
            targetX = parseInt(opt.getAttribute('data-x'), 10);
            targetY = parseInt(opt.getAttribute('data-y'), 10);
        }
    }

    // Calcul de la distance
    let distance = 5.0;
    if (targetX !== null && targetY !== null) {
        distance = Math.max(1.0, Math.round(Math.sqrt(Math.pow(targetX - currentPlanetCoords.x, 2) + Math.pow(targetY - currentPlanetCoords.y, 2)) * 100) / 100);
    }

    // Calcul des transporteurs requis
    let needHeavy = 0;
    let needLight = 0;
    let hasEnoughVehicles = false;

    if (totalCargo > 0) {
        needHeavy = Math.min(availableTransporters.heavy, Math.floor(totalCargo / 25000));
        let rem = totalCargo - (needHeavy * 25000);
        if (rem > 0) {
            if (availableTransporters.light * 5000 >= rem) {
                needLight = Math.ceil(rem / 5000);
            } else if (availableTransporters.heavy > needHeavy) {
                needHeavy++;
                needLight = 0;
            } else {
                needLight = Math.ceil(rem / 5000);
            }
        }
        const mobilizedCap = (needHeavy * 25000) + (needLight * 5000);
        hasEnoughVehicles = (mobilizedCap >= totalCargo && needHeavy <= availableTransporters.heavy && needLight <= availableTransporters.light);
    }

    const mobilizedCap = (needHeavy * 25000) + (needLight * 5000);
    if (capMobilizedDisplay) capMobilizedDisplay.innerText = mobilizedCap.toLocaleString('fr-FR');

    let vehicleText = [];
    if (needHeavy > 0) vehicleText.push(`${needHeavy} Grand Convoi (25k)`);
    if (needLight > 0) vehicleText.push(`${needLight} Chariot Léger (5k)`);
    if (vehiclesNeededDisplay) vehiclesNeededDisplay.innerText = vehicleText.length > 0 ? vehicleText.join(' + ') : 'Aucun';

    // Rations de marche requises
    const totalShips = needHeavy + needLight;
    const effectiveMarches = Math.max(1, totalShips);
    const fuelReq = Math.max(5, Math.round(distance * effectiveMarches * 1.2));
    if (fuelNeededDisplay) fuelNeededDisplay.innerHTML = `~${fuelReq.toLocaleString('fr-FR')} Koku <i class="fa-solid fa-wheat-awn text-warning"></i>`;

    // Durée estimée (vitesse de base convoi = 4000)
    const speed = 4000;
    const durSec = Math.max(3, Math.round((3500 * distance) / (speed * 5)));
    const mins = Math.floor(durSec / 60);
    const secs = durSec % 60;
    if (durationDisplay) durationDisplay.innerHTML = `<i class="fa-solid fa-stopwatch text-info me-1"></i>~${mins}m ${secs < 10 ? '0' : ''}${secs}s (Dist. ${distance})`;

    // Validation du bouton
    if (alertStatus) {
        if (totalCargo <= 0) {
            alertStatus.className = 'alert alert-info py-2 px-3 small mb-3';
            alertStatus.innerText = 'Indiquez au moins une quantité de ressources à acheminer.';
            if (btnDispatch) btnDispatch.disabled = true;
        } else if (targetX === null || targetY === null || (targetX === currentPlanetCoords.x && targetY === currentPlanetCoords.y)) {
            alertStatus.className = 'alert alert-warning py-2 px-3 small mb-3';
            alertStatus.innerText = 'Veuillez sélectionner un fief destinataire distinct de ce fief.';
            if (btnDispatch) btnDispatch.disabled = true;
        } else if (!hasEnoughVehicles) {
            alertStatus.className = 'alert alert-danger py-2 px-3 small mb-3';
            alertStatus.innerText = `Capacité de transport insuffisante sur ce fief (${availableTransporters.totalCap.toLocaleString('fr-FR')} disponible / ${totalCargo.toLocaleString('fr-FR')} requis).`;
            if (btnDispatch) btnDispatch.disabled = true;
        } else if (wood > availableCargoStocks.wood || stone > availableCargoStocks.stone || (rice + fuelReq) > availableCargoStocks.rice) {
            alertStatus.className = 'alert alert-danger py-2 px-3 small mb-3';
            alertStatus.innerText = `Ressources ou rations de riz insuffisantes dans vos greniers (Ravitaillement de marche : ${fuelReq} Riz requis).`;
            if (btnDispatch) btnDispatch.disabled = true;
        } else {
            alertStatus.className = 'alert alert-success py-2 px-3 small mb-3';
            alertStatus.innerText = `Convoi prêt : ${totalCargo.toLocaleString('fr-FR')} fret acheminé vers [${targetX}|${targetY}] via ${vehicleText.join(' + ')}.`;
            if (btnDispatch) btnDispatch.disabled = false;
        }
    }
}

async function submitMarketCargoDispatch() {
    const wood = Math.max(0, parseInt(document.getElementById('mkt_wood')?.value || 0, 10));
    const stone = Math.max(0, parseInt(document.getElementById('mkt_stone')?.value || 0, 10));
    const rice = Math.max(0, parseInt(document.getElementById('mkt_rice')?.value || 0, 10));
    const totalCargo = wood + stone + rice;

    if (totalCargo <= 0) {
        if (typeof showModalAlert === 'function') showModalAlert('Veuillez spécifier au moins une quantité de ressources.', 'warning');
        return;
    }

    const isManual = document.getElementById('mkt_manual_coords_toggle')?.checked;
    let targetPlanetId = null;
    let targetX = null;
    let targetY = null;

    if (isManual) {
        targetX = document.getElementById('mkt_target_x')?.value;
        targetY = document.getElementById('mkt_target_y')?.value;
        if (!targetX || !targetY) {
            if (typeof showModalAlert === 'function') showModalAlert('Veuillez entrer les coordonnées féodales de destination [X|Y].', 'warning');
            return;
        }
    } else {
        const sel = document.getElementById('mkt_target_planet_select');
        if (!sel || !sel.value) {
            if (typeof showModalAlert === 'function') showModalAlert('Veuillez choisir un fief destinataire.', 'warning');
            return;
        }
        targetPlanetId = sel.value;
    }

    let confirmed = true;
    if (typeof showModalConfirm === 'function') {
        confirmed = await showModalConfirm(
            `Confirmez-vous l'affrètement du convoi logistique de ${totalCargo.toLocaleString('fr-FR')} ressources ?`,
            'Expédition de Marchandises'
        );
    }
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('mission_type', 'transport');
    if (targetPlanetId) fd.append('target_planet_id', targetPlanetId);
    if (targetX !== null) fd.append('target_x', targetX);
    if (targetY !== null) fd.append('target_y', targetY);
    fd.append('cargo_metal', wood);
    fd.append('cargo_crystal', stone);
    fd.append('cargo_deuterium', rice);

    const btn = document.getElementById('btnDispatchMarketCargo');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch('/api/fleet.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') showModalAlert(data.message || 'Le convoi de marchandises a quitté les portes du fief avec succès !', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            if (typeof showModalAlert === 'function') showModalAlert(data.error || 'Erreur lors de l\'envoi du convoi.', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') showModalAlert('Erreur de transmission avec les intendants du marché.', 'error');
        if (btn) btn.disabled = false;
    }
}

// ==========================================
// BOURSE DES SENS D'ÉCHANGE & TROC P2P
// ==========================================
function onP2POfferResChange() {
    const sel = document.getElementById('p2p_offer_res');
    if (!sel) return;
    const res = sel.value;
    const stock = p2pPlanetStockMap[res] || 0;
    const badge = document.getElementById('p2p_offer_stock_badge');
    if (badge) {
        badge.innerHTML = `Stock dispo : <strong class="text-success">${Math.floor(stock).toLocaleString('fr-FR')}</strong>`;
    }
    updateP2PRatioPreview();
}

function onP2PDemandResChange() {
    updateP2PRatioPreview();
}

function setP2POfferQuick(pct) {
    const sel = document.getElementById('p2p_offer_res');
    if (!sel) return;
    const stock = p2pPlanetStockMap[sel.value] || 0;
    const val = Math.max(1, Math.floor(stock * pct));
    const inp = document.getElementById('p2p_offer_amount');
    if (inp) {
        inp.value = val;
        updateP2PRatioPreview();
    }
}

function setP2PDemandRatio(targetRatio) {
    const offInp = document.getElementById('p2p_offer_amount');
    const demInp = document.getElementById('p2p_demand_amount');
    if (!offInp || !demInp) return;
    const offerVal = Math.max(1, parseInt(offInp.value || 1, 10));
    demInp.value = Math.max(1, Math.round(offerVal * targetRatio));
    updateP2PRatioPreview();
}

function updateP2PRatioPreview() {
    const offInp = document.getElementById('p2p_offer_amount');
    const demInp = document.getElementById('p2p_demand_amount');
    const ratioDisp = document.getElementById('p2p_ratio_display');
    const ratioHint = document.getElementById('p2p_ratio_hint');
    if (!offInp || !demInp || !ratioDisp) return;

    const offVal = Math.max(1, parseInt(offInp.value || 1, 10));
    const demVal = Math.max(1, parseInt(demInp.value || 1, 10));
    const r = (demVal / offVal).toFixed(2);

    ratioDisp.textContent = `1 : ${r}`;
    if (ratioHint) {
        if (r < 0.8) {
            ratioHint.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-gift me-1"></i>Très avantageuse</span>`;
        } else if (r > 1.5) {
            ratioHint.innerHTML = `<span class="text-warning-emphasis fw-bold"><i class="fa-solid fa-coins me-1"></i>Offre exigeante</span>`;
        } else {
            ratioHint.innerHTML = `<span class="text-muted"><i class="fa-solid fa-scale-balanced me-1"></i>Taux équilibré</span>`;
        }
    }
}

function swapP2PExchangeDirection() {
    const offSel = document.getElementById('p2p_offer_res');
    const demSel = document.getElementById('p2p_demand_res');
    const offInp = document.getElementById('p2p_offer_amount');
    const demInp = document.getElementById('p2p_demand_amount');
    if (!offSel || !demSel) return;

    const prevOff = offSel.value;
    const prevDem = demSel.value;
    offSel.value = prevDem;
    demSel.value = prevOff;

    if (offInp && demInp) {
        const prevOffVal = offInp.value;
        const prevDemVal = demInp.value;
        offInp.value = prevDemVal;
        demInp.value = prevOffVal;
    }

    onP2POfferResChange();
    onP2PDemandResChange();
    if (typeof showTablerToast === 'function') {
        showTablerToast("Sens d'échange inversé : l'offre et la demande ont été permutées.", 'info', "Bourse des Sens d'Échange");
    }
}

function swapP2PFilters() {
    const fOff = document.getElementById('p2p_filter_offer');
    const fDem = document.getElementById('p2p_filter_demand');
    if (!fOff || !fDem) return;

    const tmp = fOff.value;
    fOff.value = fDem.value;
    fDem.value = tmp;

    const dirSel = document.getElementById('p2p_filter_direction');
    if (dirSel) dirSel.value = '';

    refreshP2POffers();
}

function selectTradeDirection(offerRes, demandRes, action) {
    if (action === 'filter') {
        const fOff = document.getElementById('p2p_filter_offer');
        const fDem = document.getElementById('p2p_filter_demand');
        if (fOff) fOff.value = offerRes;
        if (fDem) fDem.value = demandRes;
        const dirSel = document.getElementById('p2p_filter_direction');
        if (dirSel) dirSel.value = `${offerRes}_to_${demandRes}`;
        refreshP2POffers();
        document.getElementById('p2p_active_offers_card')?.scrollIntoView({ behavior: 'smooth' });
    } else if (action === 'create') {
        const offSel = document.getElementById('p2p_offer_res');
        const demSel = document.getElementById('p2p_demand_res');
        if (offSel) offSel.value = offerRes;
        if (demSel) demSel.value = demandRes;
        onP2POfferResChange();
        onP2PDemandResChange();
        document.getElementById('p2p_create_offer_card')?.scrollIntoView({ behavior: 'smooth' });
    }
}

function onP2PDirectionPresetChange(selectEl) {
    if (!selectEl) return;
    const val = selectEl.value;
    const fOff = document.getElementById('p2p_filter_offer');
    const fDem = document.getElementById('p2p_filter_demand');

    if (val === '' || val === 'affordable') {
        if (fOff) fOff.value = '';
        if (fDem) fDem.value = '';
    } else if (val.includes('_to_')) {
        const parts = val.split('_to_');
        if (fOff) fOff.value = parts[0];
        if (fDem) fDem.value = parts[1];
    }
    refreshP2POffers();
}

async function submitP2POffer() {
    const offRes = document.getElementById('p2p_offer_res')?.value;
    const offAmt = parseInt(document.getElementById('p2p_offer_amount')?.value || 0, 10);
    const demRes = document.getElementById('p2p_demand_res')?.value;
    const demAmt = parseInt(document.getElementById('p2p_demand_amount')?.value || 0, 10);

    if (!offRes || offAmt <= 0) {
        if (typeof showModalAlert === 'function') showModalAlert("Veuillez saisir une quantité valide de ressources à bloquer en offre.", "warning");
        return;
    }
    if (!demRes || demAmt <= 0) {
        if (typeof showModalAlert === 'function') showModalAlert("Veuillez saisir une quantité valide de ressources demandées.", "warning");
        return;
    }
    if (offRes === demRes) {
        if (typeof showModalAlert === 'function') showModalAlert("Vous ne pouvez pas échanger une ressource contre la même ressource.", "warning");
        return;
    }

    let confirmed = true;
    if (typeof showModalConfirm === 'function') {
        confirmed = await showModalConfirm(
            `Confirmez-vous le dépôt de l'offre ? ${offAmt.toLocaleString('fr-FR')} unités seront immédiatement BLOQUÉES en séquestre jusqu'à l'échange ou l'annulation.`,
            "Séquestre & Dépôt d'Offre de Troc"
        );
    }
    if (!confirmed) return;

    const btn = document.getElementById('btnSubmitP2POffer');
    if (btn) btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('offer_res', offRes);
        fd.append('offer_amount', offAmt);
        fd.append('demand_res', demRes);
        fd.append('demand_amount', demAmt);

        const res = await fetch('/api/market.php?action=create_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') showModalAlert(data.message || "Offre consignée avec succès dans les coffres du marché !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            if (typeof showModalAlert === 'function') showModalAlert(data.error || "Erreur lors du dépôt de l'offre.", "error");
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') showModalAlert("Erreur de communication avec la bourse féodale.", "error");
        if (btn) btn.disabled = false;
    }
}

async function cancelP2POffer(offerId) {
    if (typeof requestCancelEastMarketOffer === 'function') {
        requestCancelEastMarketOffer(offerId);
        return;
    }
    let confirmed = false;
    if (typeof showModalConfirm === 'function') {
        confirmed = await showModalConfirm(
            `Voulez-vous annuler l'offre n°#${offerId} ? Vos ressources consignées seront immédiatement restituées à vos entrepôts.`,
            "Annulation de l'Offre & Restitution"
        );
    }
    if (!confirmed) return;

    try {
        const fd = new FormData();
        fd.append('offer_id', offerId);
        const res = await fetch('/api/market.php?action=cancel_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') showModalAlert(data.message || "Offre annulée et ressources restituées avec succès !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            if (typeof showModalAlert === 'function') showModalAlert(data.error || "Erreur lors de l'annulation de l'offre.", "error");
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') showModalAlert("Erreur de communication avec le marché.", "error");
    }
}

async function acceptP2POffer(offerId, offName, offAmt, demName, demAmt) {
    let confirmed = true;
    if (typeof showModalConfirm === 'function') {
        confirmed = await showModalConfirm(
            `Confirmez-vous l'échange féodal ? Vous allez donner ${demAmt.toLocaleString('fr-FR')} ${demName} pour recevoir ${offAmt.toLocaleString('fr-FR')} ${offName}.`,
            "Conclure le Troc Féodal"
        );
    }
    if (!confirmed) return;

    try {
        const fd = new FormData();
        fd.append('offer_id', offerId);
        const res = await fetch('/api/market.php?action=accept_offer', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            if (typeof showModalAlert === 'function') showModalAlert(data.message || "Accord commercial accompli avec succès !", "success");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            if (typeof showModalAlert === 'function') showModalAlert(data.error || "Erreur lors de la conclusion de l'échange.", "error");
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') showModalAlert("Erreur de communication avec le marché.", "error");
    }
}

async function refreshP2POffers() {
    const filterOff = document.getElementById('p2p_filter_offer')?.value || '';
    const filterDem = document.getElementById('p2p_filter_demand')?.value || '';
    const dirVal = document.getElementById('p2p_filter_direction')?.value || '';
    const onlyAffordable = (dirVal === 'affordable') || (document.getElementById('p2p_filter_affordable')?.checked);

    try {
        const res = await fetch(`/api/market.php?action=list_offers&offer_res=${encodeURIComponent(filterOff)}&demand_res=${encodeURIComponent(filterDem)}`);
        const data = await res.json();
        if (data.success) {
            const tbody = document.getElementById('p2p_active_offers_tbody');
            if (!tbody) return;

            let displayOffers = data.offers;
            if (onlyAffordable) {
                displayOffers = displayOffers.filter(o => {
                    const curStock = p2pPlanetStockMap[o.demand_res] || 0;
                    return curStock >= parseInt(o.demand_amount, 10);
                });
            }

            if (displayOffers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-scale-balanced me-1"></i>Aucune offre ne correspond à vos filtres sur la Bourse Féodale.</td></tr>`;
                return;
            }
            tbody.innerHTML = displayOffers.map(o => {
                const offDef = data.tradables[o.offer_res] || { name: o.offer_res, icon: 'fa-solid fa-box' };
                const demDef = data.tradables[o.demand_res] || { name: o.demand_res, icon: 'fa-solid fa-box' };
                const curStock = p2pPlanetStockMap[o.demand_res] || 0;
                const canAccept = (curStock >= parseInt(o.demand_amount, 10));
                return `
                    <tr>
                        <td class="font-monospace text-muted">#${o.id}</td>
                        <td>
                            <strong class="text-dark">${o.seller_name}</strong>
                            <div class="text-muted small">${o.seller_planet_name} [${o.seller_x}|${o.seller_y}]</div>
                            <div class="mt-1">
                                <span class="badge bg-blue-lt font-monospace small" title="Sens de transaction de cette offre">
                                    <i class="${offDef.icon} me-1"></i> ➔ <i class="${demDef.icon} ms-1"></i>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success text-white fs-6 py-1 px-2">
                                +${parseInt(o.offer_amount).toLocaleString('fr-FR')} <i class="${offDef.icon} ms-1"></i>
                            </span>
                            <div class="text-muted small mt-1">${offDef.name}</div>
                        </td>
                        <td>
                            <span class="badge bg-danger-lt text-danger fs-6 py-1 px-2">
                                -${parseInt(o.demand_amount).toLocaleString('fr-FR')} <i class="${demDef.icon} ms-1"></i>
                            </span>
                            <div class="text-muted small mt-1">${demDef.name}</div>
                        </td>
                        <td class="font-monospace"><strong>1 : ${parseFloat(o.ratio)}</strong></td>
                        <td>
                            <div class="small ${canAccept ? 'text-success fw-bold' : 'text-danger'}">
                                ${Math.floor(curStock).toLocaleString('fr-FR')} en réserve
                            </div>
                        </td>
                        <td class="text-end">
                            ${canAccept ? `
                                <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="acceptP2POffer(${o.id}, '${offDef.name.replace(/'/g, "\\'")}', ${o.offer_amount}, '${demDef.name.replace(/'/g, "\\'")}', ${o.demand_amount})">
                                    <i class="fa-solid fa-handshake me-1"></i>Échanger
                                </button>
                            ` : `
                                <button type="button" class="btn btn-sm btn-secondary disabled" title="Stock insuffisant">
                                    <i class="fa-solid fa-ban me-1"></i>Insuffisant
                                </button>
                            `}
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {
        console.error("Erreur actualisation offres:", e);
    }
}

// Initialisation globale au chargement
document.addEventListener('DOMContentLoaded', () => {
    if (typeof onP2POfferResChange === 'function') onP2POfferResChange();
    if (typeof updateMarketCalculations === 'function') updateMarketCalculations();
});
</script>

