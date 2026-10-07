/**
 * OpenShogun - Gestion Interactive du Domaine Rural Féodal (9 Parcelles Uniques)
 * Affichage panoramique 16:9 fixe (sans Pan/Zoom ni curseurs de déplacement),
 * File de construction asynchrone, compte à rebours dynamique JJ:HH:MM:SS et modale Tabler.
 */
(function(window, document) {
    'use strict';

    let currentOpenPin = null;

    /**
     * Formate un temps en secondes en JJ:HH:MM:SS ou HH:MM:SS
     */
    function formatDuration(seconds) {
        if (seconds <= 0) return '00:00:00';
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        const pad = n => n.toString().padStart(2, '0');
        if (days > 0) {
            return `${days}j ${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
        }
        return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
    }

    /**
     * Clic sur un badge de parcelle
     */
    window.handleRuralPinClick = function(el, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        currentOpenPin = el;
        openPlotUpgradeModal(el);
    };

    /**
     * Remplissage et affichage de la modale d'amélioration
     */
    function openPlotUpgradeModal(pinEl) {
        if (!pinEl) return;
        const modalEl = document.getElementById('plotUpgradeModal');
        if (!modalEl) return;

        const d = pinEl.dataset;
        const stocks = (window.RURAL_CONFIG && window.RURAL_CONFIG.stocks) ? window.RURAL_CONFIG.stocks : { metal: 0, crystal: 0, deuterium: 0 };

        // Titres & Textes
        const titleEl = document.getElementById('modalPlotTitle');
        if (titleEl) titleEl.textContent = d.name || 'Parcelle';

        const jpSubEl = document.getElementById('modalPlotJpSubtitle');
        if (jpSubEl) jpSubEl.textContent = d.jpName || '';

        const descEl = document.getElementById('modalPlotDesc');
        if (descEl) descEl.textContent = d.desc || '';

        const lvlBadge = document.getElementById('modalPlotLevelBadge');
        if (lvlBadge) lvlBadge.textContent = `Niveau ${d.level} / ${d.maxLevel}`;

        const workerRoleEl = document.getElementById('modalPlotWorkerRole');
        if (workerRoleEl) workerRoleEl.textContent = d.workerRole || 'Ouvriers';

        const workersCountEl = document.getElementById('modalPlotWorkersCount');
        if (workersCountEl) workersCountEl.textContent = d.workers || '2';

        // Miniature visuelle
        const thumbEl = document.getElementById('modalPlotVisualThumb');
        if (thumbEl) {
            const bg = d.bgImg || d.tileImg || '';
            thumbEl.style.backgroundImage = bg ? `url('${bg}')` : 'none';
        }

        // Avatar icône
        const avatarEl = document.getElementById('modalPlotIconAvatar');
        if (avatarEl) {
            avatarEl.style.backgroundColor = d.color || '#3b82f6';
            avatarEl.innerHTML = `<i class="${d.icon}"></i>`;
        }

        // Rendements
        const curProdEl = document.getElementById('modalPlotCurrentProd');
        if (curProdEl) curProdEl.textContent = d.prodLabel || '-';

        const nextProdEl = document.getElementById('modalPlotNextProd');
        if (nextProdEl) nextProdEl.textContent = d.nextProdLabel || '-';

        // Barre de potentiel (20 à 100)
        const progBar = document.getElementById('modalPlotPotentialBar');
        const progText = document.getElementById('modalPlotPotentialText');
        const pct = parseInt(d.progressPct, 10) || 0;
        if (progBar) progBar.style.width = pct + '%';
        if (progText) progText.textContent = `${pct}% du potentiel féodal atteint`;

        // Bloc travaux en cours vs bloc élévation
        const workBox = document.getElementById('modalPlotActiveWorkBox');
        const normalBox = document.getElementById('modalPlotNormalUpgradeBox');
        const actionContainer = document.getElementById('modalPlotActionContainer');

        const isUpgrading = (d.isUpgrading === '1');
        const isMax = (d.isMax === '1');
        const queueId = parseInt(d.queueId, 10) || 0;
        const targetLevel = d.targetLevel || d.nextLevel;

        if (isUpgrading) {
            // Afficher le bloc de chantier actif
            if (workBox) workBox.classList.remove('d-none');
            if (normalBox) normalBox.classList.add('d-none');

            const now = Math.floor(Date.now() / 1000);
            const finishesAt = parseInt(d.finishesAt, 10) || now;
            const startedAt = parseInt(d.startedAt, 10) || now;
            const remaining = Math.max(0, finishesAt - now);
            const total = Math.max(1, finishesAt - startedAt);
            const elapsed = Math.max(0, now - startedAt);
            const progress = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));

            const cdEl = document.getElementById('modalPlotWorkCountdown');
            if (cdEl) cdEl.textContent = formatDuration(remaining);

            const pBar = document.getElementById('modalPlotWorkProgressBar');
            if (pBar) pBar.style.width = progress + '%';

            const finishLabel = document.getElementById('modalPlotWorkFinishLabel');
            if (finishLabel) {
                const dateObj = new Date(finishesAt * 1000);
                finishLabel.textContent = `Élévation au Niveau ${targetLevel} en cours. Fin estimée : ${dateObj.toLocaleTimeString()}`;
            }

            if (actionContainer) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-secondary w-100 disabled py-2 mb-2">
                        <i class="fa-solid fa-clock me-1"></i> Chantier en cours (Élévation active)
                    </button>
                    <button type="button" class="btn btn-outline-danger w-100 py-1" onclick="cancelRuralUpgrade(${queueId}, this)">
                        <i class="fa-solid fa-ban me-1"></i> Annuler le chantier (Remboursement 80%)
                    </button>`;
            }
        } else {
            // Afficher les coûts d'élévation
            if (workBox) workBox.classList.add('d-none');
            if (normalBox) normalBox.classList.remove('d-none');

            const costContainer = document.getElementById('modalPlotCostTags');
            if (costContainer) {
                costContainer.innerHTML = '';
                if (isMax) {
                    costContainer.innerHTML = '<span class="badge bg-success-lt fs-4 p-2"><i class="fa-solid fa-crown me-1 text-warning"></i>Potentiel Maximum Atteint (Niveau ' + d.maxLevel + ')</span>';
                } else {
                    const costMetal = parseInt(d.costMetal, 10) || 0;
                    const costCrystal = parseInt(d.costCrystal, 10) || 0;
                    const costDeut = parseInt(d.costDeuterium, 10) || 0;
                    const costClay = parseInt(d.costClay, 10) || 0;

                    const addTag = (icon, name, costVal, playerStock) => {
                        if (costVal <= 0) return;
                        const ok = playerStock >= costVal;
                        const tag = document.createElement('span');
                        tag.className = 'cost-chip ' + (ok ? 'affordable' : 'missing');
                        tag.innerHTML = `<i class="${icon}"></i> ${name} : <strong>${costVal.toLocaleString()}</strong>`;
                        costContainer.appendChild(tag);
                    };

                    addTag('fa-solid fa-tree text-success', 'Bois', costMetal, stocks.metal);
                    addTag('fa-solid fa-mountain text-secondary', 'Pierre', costCrystal, stocks.crystal);
                    addTag('fa-solid fa-wheat-awn text-warning', 'Riz', costDeut, stocks.deuterium);
                    if (costClay > 0) {
                        addTag('fa-solid fa-cubes-stacked text-orange', 'Argile', costClay, stocks.clay || 99999);
                    }
                }
            }

            // Durée estimée du chantier
            const durSec = parseInt(d.duration, 10) || 60;
            const durEl = document.getElementById('modalPlotDuration');
            if (durEl) {
                durEl.textContent = isMax ? 'Apogée' : formatDuration(durSec);
            }

            // Bouton d'élévation
            const canAfford = (d.canAfford === '1');
            if (actionContainer) {
                if (isMax) {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-secondary w-100 disabled" style="font-size:0.9rem;">
                            <i class="fa-solid fa-check me-1"></i> Apogée de la structure atteinte
                        </button>`;
                } else if (canAfford) {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-${d.colorClass || 'primary'} w-100 fw-bold py-2 fs-3" onclick="upgradeRuralPlot('${d.type}', this)">
                            <i class="fa-solid fa-arrow-up me-1"></i> Lancer le chantier (Niveau ${d.nextLevel} / ${d.maxLevel})
                        </button>`;
                } else {
                    actionContainer.innerHTML = `
                        <button type="button" class="btn btn-outline-secondary w-100 disabled py-2" style="font-size:0.85rem;">
                            <i class="fa-solid fa-lock me-1"></i> Ressources insuffisantes pour lancer ce chantier
                        </button>`;
                }
            }
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    /**
     * Action AJAX d'élévation (non-instantanée)
     */
    window.upgradeRuralPlot = async function(structureType, btn) {
        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Ordre de chantier en cours d\'envoi...';
            }

            const formData = new FormData();
            formData.append('action', 'upgrade');
            formData.append('structure_type', structureType);

            const res = await fetch('/api/rural_plot.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Impossible de lancer ce chantier.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
                }
            }
        } catch (err) {
            alert('Erreur réseau lors du lancement du chantier.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
            }
        }
    };

    /**
     * Action AJAX d'annulation d'un chantier
     */
    window.cancelRuralUpgrade = async function(queueId, btn) {
        if (!confirm('Voulez-vous vraiment annuler ce chantier ?\n80% des ressources investies vous seront restituées.')) {
            return;
        }

        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Annulation...';
            }

            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('queue_id', queueId);

            const res = await fetch('/api/rural_plot.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Impossible d\'annuler ce chantier.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-ban me-1"></i> Annuler le chantier';
                }
            }
        } catch (err) {
            alert('Erreur réseau lors de l\'annulation du chantier.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-ban me-1"></i> Annuler le chantier';
            }
        }
    };

    /**
     * Horloge dynamique de compte à rebours par seconde
     */
    function initDynamicCountdowns() {
        setInterval(() => {
            const now = Math.floor(Date.now() / 1000);
            let shouldReload = false;

            // 1. Mise à jour des badges sur la carte
            document.querySelectorAll('[data-rural-countdown]').forEach(el => {
                const finishesAt = parseInt(el.dataset.ruralCountdown, 10);
                const startedAt = parseInt(el.dataset.ruralStarted, 10);
                if (!finishesAt) return;

                const remaining = Math.max(0, finishesAt - now);
                el.textContent = formatDuration(remaining);

                if (startedAt && finishesAt > startedAt) {
                    const total = finishesAt - startedAt;
                    const elapsed = Math.max(0, now - startedAt);
                    const pct = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));
                    const bar = el.closest('.rural-plot-badge')?.querySelector('.rural-badge-progress-bar');
                    if (bar) bar.style.width = pct + '%';
                }

                if (remaining <= 0 && !el.dataset.expired) {
                    el.dataset.expired = '1';
                    shouldReload = true;
                }
            });

            // 2. Mise à jour des chantiers dans la colonne latérale
            document.querySelectorAll('.building-time-remaining[data-countdown]').forEach(el => {
                const finishesAt = parseInt(el.dataset.countdown, 10);
                if (!finishesAt) return;

                const remaining = Math.max(0, finishesAt - now);
                el.textContent = formatDuration(remaining);

                if (remaining <= 0 && !el.dataset.expired) {
                    el.dataset.expired = '1';
                    shouldReload = true;
                }
            });

            // 3. Mise à jour de la modale si ouverte sur un chantier en cours
            if (currentOpenPin && currentOpenPin.dataset.isUpgrading === '1') {
                const modalWorkBox = document.getElementById('modalPlotActiveWorkBox');
                if (modalWorkBox && !modalWorkBox.classList.contains('d-none')) {
                    const finishesAt = parseInt(currentOpenPin.dataset.finishesAt, 10) || now;
                    const startedAt = parseInt(currentOpenPin.dataset.startedAt, 10) || now;
                    const remaining = Math.max(0, finishesAt - now);
                    const total = Math.max(1, finishesAt - startedAt);
                    const elapsed = Math.max(0, now - startedAt);
                    const pct = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));

                    const cdEl = document.getElementById('modalPlotWorkCountdown');
                    if (cdEl) cdEl.textContent = formatDuration(remaining);

                    const pBar = document.getElementById('modalPlotWorkProgressBar');
                    if (pBar) pBar.style.width = pct + '%';

                    if (remaining <= 0 && !currentOpenPin.dataset.expired) {
                        currentOpenPin.dataset.expired = '1';
                        shouldReload = true;
                    }
                }
            }

            if (shouldReload) {
                setTimeout(() => window.location.reload(), 1200);
            }
        }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDynamicCountdowns);
    } else {
        initDynamicCountdowns();
    }

})(window, document);
