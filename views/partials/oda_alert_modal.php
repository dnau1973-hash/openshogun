<?php
/**
 * Modale d'Alerte Tactique : Général Oda Nobunaga (Conquête & Guerre)
 * S'affiche en cas d'incursion militaire hostile imminente vers le domaine.
 */

// Récupération des données d'incursion si disponibles dans la vue parent
$hasHostileAlert = !empty($incomingHostile) && count($incomingHostile) > 0;
$closestAttack = $hasHostileAlert ? $incomingHostile[0] : null;
$closestAttackTime = $closestAttack ? (($closestAttack['status'] === 'en_route') ? (int)$closestAttack['arrival_time'] : (int)$closestAttack['return_time']) : (time() + 1800);
$closestAttackId = $closestAttack ? (int)($closestAttack['id'] ?? 1) : 0;
$totalAttacksCount = $hasHostileAlert ? count($incomingHostile) : 1;
?>

<!-- Modale d'Alerte Militaire : Général Oda Nobunaga -->
<div class="modal fade" id="modalOdaAttackAlert" tabindex="-1" aria-labelledby="modalOdaAttackAlertLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 overflow-hidden" 
             style="border-radius: 14px; background: #161210; color: #f8fafc; border: 2px solid #dc2626 !important; box-shadow: 0 20px 45px rgba(220, 38, 38, 0.35) !important;">
            
            <!-- En-tête de Guerre Laque Rouge & Sceau Oda -->
            <div class="modal-header py-3 px-4" 
                 style="background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 60%, #1c1917 100%); border-bottom: 2px solid #ef4444;">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative flex-shrink-0" style="width: 54px; height: 54px;">
                        <img src="/public/assets/oda_nobunaga_alert.jpg" 
                             alt="Général Oda Nobunaga" 
                             class="rounded-circle border border-2 border-danger object-fit-cover shadow"
                             style="width: 54px; height: 54px; object-position: center top;"
                             onerror="this.src='/public/assets/hero_samurai.jpg'">
                        <span class="position-absolute bottom-0 end-0 badge bg-danger text-white p-1 rounded-circle shadow-sm" 
                              title="Commandant en Chef des Armées" style="font-size: 0.65rem;">
                            <i class="fa-solid fa-skull-crossbones"></i>
                        </span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="modal-title fw-bold m-0 text-white font-game" id="modalOdaAttackAlertLabel" style="letter-spacing: 0.04em;">
                                Conseil de Guerre : Général Oda Nobunaga
                            </h4>
                            <span class="badge bg-danger text-white font-monospace animate-pulse" 
                                  style="font-size: 0.68rem; letter-spacing: 0.05em; border: 1px solid #f87171;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>ALERTE D'INCURSION
                            </span>
                        </div>
                        <div class="text-white-50 small mt-0.5">
                            Conquête &amp; Guerre — Quartier Général Tactique d'Azuchi
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps de la Modale -->
            <div class="modal-body p-3 p-md-4">
                
                <!-- Illustration 16:9 avec Parallaxe Cinématique Autonome -->
                <div class="position-relative rounded-3 overflow-hidden shadow mb-3 border border-danger depth-parallax-scene" 
                     style="height: 220px; aspect-ratio: 16/9; max-height: 240px; background: #0b0908;">
                    <img src="/public/assets/oda_nobunaga_alert.jpg" 
                         alt="Général Oda Nobunaga sur le champ de bataille" 
                         class="w-100 h-100 object-fit-cover d-block depth-parallax-img has-cinematic-breathing"
                         style="object-position: center 25%;"
                         onerror="this.onerror=null; this.src='/public/assets/hero_samurai_mounted.jpg';">
                    
                    <!-- Overlay de bas d'image -->
                    <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2 depth-parallax-foreground" 
                         style="background: linear-gradient(180deg, transparent 0%, rgba(15, 10, 8, 0.95) 100%); z-index: 5;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger text-white fw-bold font-monospace px-2 py-1">
                                <i class="fa-solid fa-shield-halved me-1"></i>DÉFENSE DU FIEF
                            </span>
                            <span class="text-white-50 font-monospace small">Étendard du Clan Oda</span>
                        </div>
                        <span class="badge bg-dark border border-danger text-danger font-monospace">
                            Tenka Fubu (天下布武)
                        </span>
                    </div>
                </div>

                <!-- Discours Martial d'Oda Nobunaga -->
                <div class="p-3 rounded-3 mb-3 d-flex align-items-start gap-3 shadow-xs" 
                     style="background: rgba(69, 10, 10, 0.28); border-left: 4px solid #ef4444; border-top: 1px solid rgba(239, 68, 68, 0.3); border-right: 1px solid rgba(239, 68, 68, 0.2); border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
                    <i class="fa-solid fa-quote-left text-danger fs-2 flex-shrink-0 mt-1" style="opacity: 0.85;"></i>
                    <div>
                        <p class="mb-1 text-light font-serif fst-italic" style="font-size: 0.96rem; line-height: 1.55;">
                            « Les tambours de guerre ont grondé à travers les défilés. Une armée ennemie fonce à marche forcée pour piller nos domaines et réduire nos rizières en cendres ! 
                            Daimyō, il n'y a nulle place pour le doute : fermez la grande porte Ōtemon, armez les arquebuses et hissez nos bannières aux remparts ! »
                        </p>
                        <div class="text-end text-danger-lt small font-monospace fw-bold">
                            — Général Oda Nobunaga
                        </div>
                    </div>
                </div>

                <!-- Tableau de bord tactique d'incursion -->
                <div class="card border border-danger-subtle rounded-3 mb-3 bg-dark shadow-xs" style="background: rgba(20, 16, 14, 0.8) !important;">
                    <div class="card-header py-2 px-3 bg-danger-lt d-flex justify-content-between align-items-center text-danger border-bottom border-danger-subtle">
                        <span class="fw-bold text-uppercase font-game" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            <i class="fa-solid fa-binoculars me-1"></i> Rapport Tactique des Sentinelles
                        </span>
                        <span class="badge bg-danger text-white font-monospace" style="font-size: 0.65rem;">
                            <?= $totalAttacksCount ?> Incursion(s) Détectée(s)
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-6">
                                <div class="text-muted small text-uppercase" style="font-size: 0.68rem;">Impact Estimé aux Portes :</div>
                                <div class="fs-2 fw-bold text-danger font-monospace mt-1 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-stopwatch animate-pulse"></i>
                                    <span id="odaAlertCountdown" data-countdown="<?= $closestAttackTime ?>">Calcul en cours...</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small text-uppercase" style="font-size: 0.68rem;">Recommandation Stratégique :</div>
                                <div class="small text-light mt-1" style="font-size: 0.82rem; line-height: 1.45;">
                                    <strong class="text-warning">1.</strong> Rassembler les troupes disponibles au <a href="?page=barracks" class="text-danger fw-bold text-decoration-underline">Dojo &amp; Caserne</a>.<br>
                                    <strong class="text-warning">2.</strong> Élever les palissades et vérifier les stocks de granit.<br>
                                    <strong class="text-warning">3.</strong> Prévenir vos vassaux pour des renforts d'alliance.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer d'Actions Rapides -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top border-secondary-subtle">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="fa-solid fa-check me-1"></i>J'ai compris, aux armes !
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-dark btn-sm border border-danger text-danger px-3" 
                                onclick="bootstrap.Modal.getInstance(document.getElementById('modalOdaAttackAlert'))?.hide(); if(window.openWatchtowerModal){window.openWatchtowerModal();}">
                            <i class="fa-solid fa-tower-observation me-1"></i>Tour de Guet
                        </button>
                        <a href="?page=barracks" class="btn btn-danger btn-sm px-3 shadow-sm fw-bold">
                            <i class="fa-solid fa-shield-halved me-1"></i>Mobiliser la Garnison &rarr;
                        </a>
                    </div>
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

    // Fonction globale pour ouvrir manuellement la modale (depuis la bannière ou un raccourci)
    window.openOdaAlertModal = function() {
        const modalEl = document.getElementById('modalOdaAttackAlert');
        if (!modalEl) return;
        const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInst.show();
    };

    // Déclenchement automatique ordonné si une attaque hostile est en cours
    if (hasIncomingHostile && hostileMissionId > 0) {
        const storageKey = 'shogun_alert_seen_hostile_' + hostileMissionId;
        const alreadySeen = sessionStorage.getItem(storageKey);

        // N'afficher automatiquement qu'une seule fois par session pour cette attaque
        if (!alreadySeen) {
            sessionStorage.setItem(storageKey, 'true');

            // Enregistrement dans l'orchestrateur séquentiel avec Priorité 5 (Urgence absolue de Guerre)
            if (window.shogunModalQueue && typeof window.shogunModalQueue.enqueue === 'function') {
                window.shogunModalQueue.enqueue({
                    id: 'modalOdaAttackAlert_' + hostileMissionId,
                    priority: 5, // Passe immédiatement avant les didacticiels et chroniques
                    open: () => {
                        window.openOdaAlertModal();
                    },
                    modalElement: '#modalOdaAttackAlert'
                });
            } else {
                // Fallback direct si l'orchestrateur n'est pas encore prêt
                setTimeout(() => {
                    window.openOdaAlertModal();
                }, 400);
            }
        }
    }
});
</script>
