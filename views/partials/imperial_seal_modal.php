<?php
/**
 * Modale Universelle du Sceau Impérial (Privilège du Shōgun / Travian Plus)
 */
require_once __DIR__ . '/../../core/ImperialSealEngine.php';
$sealEngineModal = new ImperialSealEngine();
$sealStatusModal = ($user && !empty($user['id'])) ? $sealEngineModal->getSealStatus((int)$user['id']) : null;
$isSealActiveModal = $sealStatusModal && !empty($sealStatusModal['active']);
$goldCoinsModal = $sealStatusModal ? (int)$sealStatusModal['gold'] : 0;
?>

<!-- MODALE DU SCEAU IMPÉRIAL -->
<div class="modal modal-blur fade" id="modalImperialSeal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0" style="background: #ffffff; border-radius: 12px; overflow:hidden;">
            
            <!-- HEADER AVEC FOND PRESTIGIEUX -->
            <div class="modal-header border-0 p-4 text-white" 
                 style="background: linear-gradient(135deg, #1c1917 0%, #292524 50%, #451a03 100%); border-bottom: 2px solid #f59e0b !important;">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-1 p-2 rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow" style="width: 54px; height: 54px;">
                        👑
                    </span>
                    <div>
                        <div class="text-warning text-uppercase fw-bold small" style="letter-spacing:1px;">Privilège du Shōgun &bull; Sengoku Plus</div>
                        <h3 class="modal-title fs-2 fw-bold text-white m-0">Le Sceau Impérial de l'Empire</h3>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- BANDEAU STATUT & PIÈCES D'OR -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100 d-flex flex-column justify-content-between" style="background:#fefce8; border-color:#fef08a !important;">
                            <div>
                                <div class="text-secondary small fw-bold text-uppercase">Statut Impérial Actuel</div>
                                <div class="fs-3 fw-bold mt-1 d-flex align-items-center gap-2">
                                    <?php if ($isSealActiveModal): ?>
                                        <span class="text-success">👑 Sceau Actif</span>
                                    <?php else: ?>
                                        <span class="text-muted">Inactif</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?php if ($isSealActiveModal): ?>
                                        Expire le : <strong><?= htmlspecialchars($sealStatusModal['until']) ?></strong> (<?= htmlspecialchars($sealStatusModal['remaining_formatted']) ?>)
                                    <?php else: ?>
                                        Activez le Sceau pour bénéficier des 6 privilèges impériaux de cour.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($isSealActiveModal): ?>
                                <span class="badge bg-success-lt align-self-start mt-2">Privilèges Pleinement Débloqués</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100 d-flex flex-column justify-content-between" style="background:#fffbeb; border-color:#fde68a !important;">
                            <div>
                                <div class="text-secondary small fw-bold text-uppercase">Trésor en Koban (Pièces d'Or)</div>
                                <div class="fs-2 fw-bold text-warning mt-1 d-flex align-items-center gap-2">
                                    <span>🪙</span>
                                    <span id="seal_gold_display"><?= number_format($goldCoinsModal) ?></span>
                                    <span class="fs-5 text-dark fw-normal">Koban</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    La monnaie impériale s'obtient via le tribut quotidien, les quêtes féodales et les aventures du Samouraï Héros.
                                </div>
                            </div>
                            <div class="mt-2">
                                <?php if (!empty($sealStatusModal['can_claim_daily'])): ?>
                                    <button type="button" class="btn btn-sm btn-warning w-100 fw-bold shadow-sm" id="btnClaimDailyGold" onclick="claimDailyGold()">
                                        🎁 Réclamer le Tribut du Jour (+5 Koban Gratuits)
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                        ✓ Tribut du Jour déjà perçu (Revenez demain)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3 CARTES D'ACTIVATION / PROLONGATION -->
                <div class="mb-4">
                    <h4 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span>📜</span> Décréter ou Prolonger le Sceau Impérial
                    </h4>
                    <div class="row g-3">
                        <!-- 7 Jours -->
                        <div class="col-md-4">
                            <div class="card h-100 border text-center p-3 hover-shadow" style="transition: transform 0.15s ease;">
                                <div class="text-secondary small fw-bold text-uppercase">Investiture Rapide</div>
                                <div class="fs-2 fw-bold text-dark my-1">7 Jours</div>
                                <div class="fs-4 fw-bold text-warning mb-2">50 Koban</div>
                                <p class="text-muted small mb-3">Idéal pour un sprint stratégique ou fonder vos premières colonies.</p>
                                <button type="button" class="btn btn-outline-warning w-100 fw-bold" onclick="activateSeal(7)">
                                    👑 Décréter (7j)
                                </button>
                            </div>
                        </div>

                        <!-- 14 Jours (Populaire) -->
                        <div class="col-md-4">
                            <div class="card h-100 border border-warning text-center p-3 position-relative shadow-sm" style="background:#fffdfa;">
                                <span class="badge bg-warning text-dark position-absolute top-0 start-50 translate-middle fw-bold">
                                    ⭐ ÉCONOMIE -10%
                                </span>
                                <div class="text-secondary small fw-bold text-uppercase mt-1">Investiture Royale</div>
                                <div class="fs-2 fw-bold text-dark my-1">14 Jours</div>
                                <div class="fs-4 fw-bold text-warning mb-2">90 Koban</div>
                                <p class="text-muted small mb-3">Le meilleur équilibre pour orchestrer la consolidation de vos domaines.</p>
                                <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm" onclick="activateSeal(14)">
                                    👑 Décréter (14j)
                                </button>
                            </div>
                        </div>

                        <!-- 30 Jours -->
                        <div class="col-md-4">
                            <div class="card h-100 border text-center p-3 hover-shadow" style="transition: transform 0.15s ease;">
                                <span class="badge bg-purple-lt text-purple position-absolute top-0 start-50 translate-middle fw-bold">
                                    👑 SOUVERAIN -25%
                                </span>
                                <div class="text-secondary small fw-bold text-uppercase mt-1">Investiture Mensuelle</div>
                                <div class="fs-2 fw-bold text-dark my-1">30 Jours</div>
                                <div class="fs-4 fw-bold text-warning mb-2">150 Koban</div>
                                <p class="text-muted small mb-3">La paix royale pour tout un mois de conquête et de développement continu.</p>
                                <button type="button" class="btn btn-outline-warning w-100 fw-bold" onclick="activateSeal(30)">
                                    👑 Décréter (30j)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LISTE DES 6 GRANDS PRIVILÈGES IMPÉRIAUX -->
                <div>
                    <h4 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span>⭐</span> Les 6 Grands Privilèges du Shōgun
                    </h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">🔨</span>
                                <div>
                                    <strong class="text-dark">Architecte de Cour (File Étendue)</strong>
                                    <div class="text-secondary small mt-1">
                                        Enchaînez jusqu'à <strong>4 constructions et parcelles</strong> simultanées en file d'attente pour planifier vos chantiers jour et nuit.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">👑</span>
                                <div>
                                    <strong class="text-dark">Grand Tableau de Bord de l'Empire</strong>
                                    <div class="text-secondary small mt-1">
                                        Vue panoramique de tous vos fiefs : stocks totaux, productions nettes, risques de famine et garnisons centralisées.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">📜</span>
                                <div>
                                    <strong class="text-dark">Carnet de Raids (Farm List)</strong>
                                    <div class="text-secondary small mt-1">
                                        Mémorisez vos cibles régulières et lancez l'ensemble de vos tournées de pillage <strong>en 1 clic</strong> depuis la caserne.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">⚖️</span>
                                <div>
                                    <strong class="text-dark">Intendant du Marché (Troc 1:1:1)</strong>
                                    <div class="text-secondary small mt-1">
                                        Rééquilibrez instantanément vos réserves excédentaires de Bois, Pierre et Riz pour lancer immédiatement vos chantiers.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">⛩️</span>
                                <div>
                                    <strong class="text-dark">Ordre de Repli Tactique (Évasion)</strong>
                                    <div class="text-secondary small mt-1">
                                        Vos guerriers et votre Samouraï Héros se replient dans les forêts lors des assauts nocturnes pour éviter l'anéantissement.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3 p-2 rounded border bg-light h-100">
                                <span class="fs-2">⚡</span>
                                <div>
                                    <strong class="text-dark">Raccourcis &amp; Suivi Prioritaire</strong>
                                    <div class="text-secondary small mt-1">
                                        Liens d'accès direct dans la barre impériale pour gouverner rapidement sans allers-retours superflus.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 p-3 bg-light d-flex justify-content-between">
                <span class="small text-muted">Jeu équitable &bull; Aucun achat obligatoire, pièces d'or gratuites offertes chaque jour.</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function openImperialSealModal() {
    window.location.href = '/?page=privilege';
}

async function activateSeal(days) {
    const confirmed = await showModalConfirm(`Voulez-vous activer le Sceau Impérial du Shōgun pour une durée de ${days} jours ?`, 'Décret Impérial');
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'activate');
    fd.append('days', days);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Sceau Impérial Proclamé');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'activer le Sceau Impérial.', 'warning');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le Shogunat.', 'error');
    }
}

async function claimDailyGold() {
    const btn = document.getElementById('btnClaimDailyGold');
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'claim_daily');

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Tribut d\'Or Perçu');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Tribut indisponible pour l\'instant.', 'warning');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
        if (btn) btn.disabled = false;
    }
}
</script>

