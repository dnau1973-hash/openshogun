<?php
/**
 * Modale d'Alerte : Général Oda Nobunaga (Conquête & Guerre)
 * Style classique standard sans fioritures (illustration & contenu).
 */

$hasHostileAlert = !empty($incomingHostile) && count($incomingHostile) > 0;
$closestAttack = $hasHostileAlert ? $incomingHostile[0] : null;
$closestAttackTime = $closestAttack ? (($closestAttack['status'] === 'en_route') ? (int)$closestAttack['arrival_time'] : (int)$closestAttack['return_time']) : (time() + 1800);
$closestAttackId = $closestAttack ? (int)($closestAttack['id'] ?? 1) : 0;
$totalAttacksCount = $hasHostileAlert ? count($incomingHostile) : 1;
?>

<!-- Modale Classique : Général Oda Nobunaga -->
<div class="modal fade" id="modalOdaAttackAlert" tabindex="-1" aria-labelledby="modalOdaAttackAlertLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-sm">
            
            <!-- En-tête classique -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold m-0" id="modalOdaAttackAlertLabel">
                        Général Oda Nobunaga
                    </h5>
                    <div class="text-muted small">
                        Alerte d'attaque imminente sur votre village
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps : Illustration 16:9 & Contenu -->
            <div class="modal-body p-3 p-md-4">
                
                <!-- Illustration classique 16:9 avec mouvement autonome -->
                <div class="depth-parallax-scene rounded mb-3" style="width: 100%; aspect-ratio: 16/9; max-height: 380px; overflow: hidden; background: #000000;">
                    <img src="/public/assets/personnages/oda_nobunaga.jpeg" 
                         alt="Général Oda Nobunaga" 
                         class="depth-parallax-img w-100 h-100 object-fit-cover"
                         style="object-position: center;"
                         onerror="this.src='/public/assets/events/event_week_01.jpg';">
                </div>

                <!-- Message du Général Oda Nobunaga -->
                <div class="mb-3">
                    <p class="fs-4 fst-italic mb-2" style="line-height: 1.6;">
                        « Daimyō, nos éclaireurs ont repéré une troupe ennemie en marche rapide vers notre domaine. 
                        Le temps presse : rassemblez la garnison aux remparts, armez les défenseurs et préparez le fief à soutenir l'assaut. »
                    </p>
                    <div class="text-muted small text-end">
                        — Général Oda Nobunaga
                    </div>
                </div>

                <!-- Détails de l'incursion -->
                <div class="border rounded p-3 bg-light">
                    <div class="row g-2 align-items-center">
                        <div class="col-sm-6">
                            <div class="text-muted small">Incursion(s) hostile(s) détectée(s) :</div>
                            <div class="fw-bold fs-3 text-danger">
                                <?= $totalAttacksCount ?> armée(s) en approche
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Impact estimé aux portes :</div>
                            <div class="fw-bold fs-3 text-dark" id="odaAlertCountdown" data-countdown="<?= $closestAttackTime ?>">
                                Calcul...
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pied classique -->
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Fermer
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" 
                            onclick="bootstrap.Modal.getInstance(document.getElementById('modalOdaAttackAlert'))?.hide(); if(window.openWatchtowerModal){window.openWatchtowerModal();}">
                        Tour de Guet
                    </button>
                    <a href="?page=barracks" class="btn btn-danger">
                        Mobiliser la Garnison
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
/**
 * Initialisation & Orchestration de la Modale d'Alerte Oda Nobunaga
 */
document.addEventListener('DOMContentLoaded', function() {
    const hasIncomingHostile = <?= $hasHostileAlert ? 'true' : 'false' ?>;
    const hostileMissionId = <?= (int)$closestAttackId ?>;

    window.openOdaAlertModal = function() {
        const modalEl = document.getElementById('modalOdaAttackAlert');
        if (!modalEl) return;
        const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInst.show();
    };

    if (hasIncomingHostile && hostileMissionId > 0) {
        const storageKey = 'shogun_alert_seen_hostile_' + hostileMissionId;
        const alreadySeen = sessionStorage.getItem(storageKey);

        if (!alreadySeen) {
            sessionStorage.setItem(storageKey, 'true');

            if (window.shogunModalQueue && typeof window.shogunModalQueue.enqueue === 'function') {
                window.shogunModalQueue.enqueue({
                    id: 'modalOdaAttackAlert_' + hostileMissionId,
                    priority: 5,
                    open: () => {
                        window.openOdaAlertModal();
                    },
                    modalElement: '#modalOdaAttackAlert'
                });
            } else {
                setTimeout(() => {
                    window.openOdaAlertModal();
                }, 400);
            }
        }
    }
});
</script>
