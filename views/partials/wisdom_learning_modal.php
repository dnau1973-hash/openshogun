<!-- Modale Interactive Washi : Le Maître de la Sagesse (Mode Apprentissage Féodal) -->
<div class="modal fade" id="modalWisdomLearning" tabindex="-1" aria-labelledby="modalWisdomLearningLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: 12px; background: #fffcf7; border: 1px solid #e2d9cc !important;">
            
            <!-- En-tête avec dégradé Washi & sceau féodal -->
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #1c1917 0%, #292524 60%, #44403c 100%); color: #f5f5f4; border-bottom: 2px solid #b45309;">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative flex-shrink-0" style="width: 52px; height: 52px;">
                        <img src="/public/assets/maitre_sagesse.jpg" 
                             alt="Maître de la Sagesse" 
                             class="rounded-circle border border-2 border-warning object-fit-cover shadow-sm"
                             style="width: 52px; height: 52px; object-position: center top;"
                             onerror="this.src='/public/assets/hero_samurai.jpg'">
                        <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark p-1 rounded-circle" title="Érudit & Bonze de la Sagesse" style="font-size: 0.65rem;">
                            <i class="fa-solid fa-scroll"></i>
                        </span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="modal-title fw-bold m-0 text-white font-game" id="modalWisdomLearningLabel" style="letter-spacing: 0.03em;">
                                Le Maître de la Sagesse
                            </h4>
                            <span class="badge bg-warning-lt text-warning font-monospace" id="wisdomBadge" style="font-size: 0.65rem; border: 1px solid rgba(245, 158, 11, 0.4);">
                                Chie no Sensei (知恵の先生)
                            </span>
                        </div>
                        <div class="text-white-50 small mt-1" id="wisdomSubtitle">
                            Enseignement & Évolution de votre Société Féodale
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps de la Modale -->
            <div class="modal-body p-4">
                
                <!-- Parchemin de citation philosophique -->
                <div class="p-3 rounded-3 mb-3 d-flex align-items-center gap-3 shadow-xs" 
                     style="background: #fbf7ee; border-left: 4px solid #b45309; border-top: 1px solid #ebd9c5; border-right: 1px solid #ebd9c5; border-bottom: 1px solid #ebd9c5;">
                    <i class="fa-solid fa-quote-left text-warning fs-1 flex-shrink-0" style="opacity: 0.85;"></i>
                    <p class="mb-0 fst-italic text-dark font-serif" id="wisdomQuote" style="font-size: 0.95rem; font-family: 'Georgia', serif; line-height: 1.5;">
                        « Un empire repose sur le bien-être de son peuple. »
                    </p>
                </div>

                <!-- En-tête du jalon -->
                <div class="mb-3">
                    <h5 class="fw-bold text-dark font-game d-flex align-items-center gap-2 mb-1" id="wisdomTitle" style="font-size: 1.15rem;">
                        <i class="fa-solid fa-scroll text-danger"></i> <span>Éveil de votre Société</span>
                    </h5>
                    <div class="text-muted" id="wisdomText" style="font-size: 0.9rem; line-height: 1.6; color: #44403c !important;">
                        Chargement des enseignements du sage...
                    </div>
                </div>

                <!-- Panneau des prérequis / ressources nécessaires -->
                <div class="card border rounded-3 mb-3 bg-white shadow-xs">
                    <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase font-game" style="font-size: 0.72rem; letter-spacing: 0.05em; color: #78716c;">
                            <i class="fa-solid fa-bowl-rice text-success me-1"></i> Besoins &amp; Conditions d'Évolution
                        </span>
                        <span class="badge bg-blue-lt text-blue font-monospace" id="wisdomCasteRank" style="font-size: 0.65rem;">
                            Shinōkōshō
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2" id="wisdomRequirementsList">
                            <!-- Injecté dynamiquement en JS -->
                        </div>
                    </div>
                </div>

                <!-- Pied informatif & désactivation discrète -->
                <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top text-muted small">
                    <div class="d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-circle-info text-info"></i>
                        <span>Ce conseil s'affiche uniquement aux étapes clés. Vous pouvez le revoir via le médaillon du HUD.</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-link btn-xs text-secondary text-decoration-none p-0" onclick="quickDisableLearningMode()" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-eye-slash me-1"></i> Ne plus afficher ces conseils
                        </button>
                    </div>
                </div>

            </div>

            <!-- Pied de Modale avec Actions -->
            <div class="modal-footer px-4 py-3 bg-light d-flex justify-content-between align-items-center border-top">
                <button type="button" class="btn btn-outline-secondary font-game" data-bs-dismiss="modal" onclick="dismissWisdomCurrentAdvice()">
                    <i class="fa-solid fa-check me-1"></i> Merci Maître, c'est compris
                </button>
                <a href="#" id="wisdomActionBtn" class="btn btn-primary font-game d-inline-flex align-items-center gap-2 shadow-sm">
                    <span id="wisdomActionLabel">Agir maintenant</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </div>
</div>

<script>
let currentWisdomMilestoneKey = null;

/**
 * Charge et affiche automatiquement le conseil du sage si disponible
 */
async function checkWisdomAdvice(isManual = false) {
    try {
        const resp = await fetch('/api/learning.php?action=get_advice' + (isManual ? '&manual=1' : ''));
        const data = await resp.json();

        if (data.success && data.has_advice && data.advice) {
            renderWisdomModal(data.advice);
            const modalEl = document.getElementById('modalWisdomLearning');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        } else if (isManual) {
            // Si ouverture manuelle mais tout est déjà accompli
            if (typeof showTablerToast === 'function') {
                showTablerToast("Le Maître de la Sagesse médite en paix. Votre société prospère harmonieusement !", "info");
            } else {
                alert("Le Maître de la Sagesse médite en paix. Votre société prospère harmonieusement !");
            }
        }
    } catch (e) {
        console.error("Erreur vérification Maître de la Sagesse :", e);
    }
}

/**
 * Peuple les éléments de la modale avec les données du conseil
 */
function renderWisdomModal(advice) {
    currentWisdomMilestoneKey = advice.key;

    const titleEl = document.getElementById('wisdomTitle');
    const quoteEl = document.getElementById('wisdomQuote');
    const textEl = document.getElementById('wisdomText');
    const badgeEl = document.getElementById('wisdomBadge');
    const subEl = document.getElementById('wisdomSubtitle');
    const reqListEl = document.getElementById('wisdomRequirementsList');
    const actionBtn = document.getElementById('wisdomActionBtn');
    const actionLabel = document.getElementById('wisdomActionLabel');

    if (titleEl) titleEl.innerHTML = `<i class="fa-solid fa-scroll text-danger me-2"></i> ${advice.title}`;
    if (quoteEl) quoteEl.innerText = advice.quote || "« La patience et le labeur bâtissent les empires durables. »";
    if (textEl) textEl.innerHTML = advice.text;
    if (badgeEl) {
        badgeEl.innerText = advice.badge || "Enseignement Féodal";
        badgeEl.className = `badge bg-${advice.badge_color || 'warning'}-lt text-${advice.badge_color || 'warning'} font-monospace`;
    }
    if (subEl) subEl.innerText = advice.subtitle || "Enseignement & Évolution de votre Société Féodale";

    if (reqListEl) {
        reqListEl.innerHTML = '';
        if (advice.requirements && advice.requirements.length > 0) {
            advice.requirements.forEach(req => {
                const isFulfilled = req.status === 'fulfilled';
                const statusBadge = isFulfilled 
                    ? '<span class="badge bg-success-lt text-success"><i class="fa-solid fa-check me-1"></i>Comblé</span>'
                    : '<span class="badge bg-danger-lt text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Requis</span>';

                const col = document.createElement('div');
                col.className = 'col-12 col-md-6';
                col.innerHTML = `
                    <div class="p-2 border rounded-2 d-flex align-items-center justify-content-between ${isFulfilled ? 'bg-success-subtle border-success-subtle' : 'bg-light border-warning-subtle'}">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <i class="fa-solid ${req.icon || 'fa-box'} fs-2 flex-shrink-0" style="color:${req.color || '#64748b'}; width:28px; text-align:center;"></i>
                            <div class="lh-1 overflow-hidden">
                                <div class="fw-bold text-dark text-truncate" style="font-size:0.82rem;">${req.name}</div>
                                <div class="text-muted text-truncate" style="font-size:0.68rem;">${req.desc || ''}</div>
                            </div>
                        </div>
                        <div class="ms-2 flex-shrink-0">
                            ${statusBadge}
                        </div>
                    </div>
                `;
                reqListEl.appendChild(col);
            });
        } else {
            reqListEl.innerHTML = '<div class="col-12 text-muted small p-2">Aucun prérequis supplémentaire. Votre société rayonne de plénitude.</div>';
        }
    }

    if (actionBtn && actionLabel) {
        actionBtn.href = advice.next_action_url || '?page=city';
        actionLabel.innerText = advice.next_action_label || 'Agir maintenant';
    }
}

/**
 * Acquitte le conseil actuel pour qu'il ne se réaffiche plus en pop-up intempestif
 */
async function dismissWisdomCurrentAdvice() {
    if (!currentWisdomMilestoneKey) return;
    try {
        const formData = new FormData();
        formData.append('action', 'dismiss_advice');
        formData.append('milestone_key', currentWisdomMilestoneKey);
        await fetch('/api/learning.php', { method: 'POST', body: formData });
    } catch (e) {
        console.error("Erreur acquittement jalon :", e);
    }
}

/**
 * Désactivation rapide du Mode Apprentissage directement depuis la modale
 */
async function quickDisableLearningMode() {
    if (!confirm("Souhaitez-vous désactiver les conseils du Maître de la Sagesse ?\nVous pourrez les réactiver à tout moment depuis votre page Poster / Profil.")) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'disable_learning_mode');
        const resp = await fetch('/api/learning.php', { method: 'POST', body: formData });
        const res = await resp.json();

        if (res.success) {
            const modalEl = document.getElementById('modalWisdomLearning');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
            if (typeof showTablerToast === 'function') {
                showTablerToast(res.message, "info");
            } else {
                alert(res.message);
            }
        }
    } catch (e) {
        alert("Erreur lors de la désactivation : " + e.message);
    }
}

// Vérification automatique non-intrusive au chargement de la page (après 1.2s pour ne pas bloquer le rendu)
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        checkWisdomAdvice(false);
    }, 1200);
});
</script>

