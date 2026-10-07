/**
 * OpenShogun - Moteur Cartographique Interactif du Domaine Rural Féodal (9 Parcelles Uniques)
 * Viewport Grab-and-Pan, Pan/Zoom Clamping (16:9), Modale d'amélioration et intégration AJAX Tabler.
 */
(function(window, document) {
    'use strict';

    const mapState = {
        stageWidth: 1920,
        stageHeight: 1080,
        scale: 1.0,
        minScale: 0.5,
        maxScale: 2.2,
        x: 0,
        y: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        hasMoved: false
    };

    let viewportEl = null;
    let stageEl = null;
    let zoomIndicator = null;
    let modalEl = null;

    /**
     * Calcul du scale minimum pour couvrir 100% du conteneur sans bordure noire
     */
    function calculateMinScale(containerWidth, containerHeight) {
        if (!containerWidth || !containerHeight) return 0.5;
        return Math.max(
            containerWidth / mapState.stageWidth,
            containerHeight / mapState.stageHeight
        );
    }

    /**
     * Clamping strict du déplacement et du zoom
     */
    function clampMapCoordinates() {
        if (!viewportEl) return;
        const containerWidth = viewportEl.clientWidth;
        const containerHeight = viewportEl.clientHeight;
        if (containerWidth <= 0 || containerHeight <= 0) return;

        const minScale = calculateMinScale(containerWidth, containerHeight);
        mapState.minScale = minScale;
        if (mapState.scale < minScale) {
            mapState.scale = minScale;
        }

        const currentZoom = mapState.scale;
        const minX = containerWidth - (mapState.stageWidth * currentZoom);
        const maxX = 0;
        const minY = containerHeight - (mapState.stageHeight * currentZoom);
        const maxY = 0;

        mapState.x = Math.min(Math.max(mapState.x, minX), maxX);
        mapState.y = Math.min(Math.max(mapState.y, minY), maxY);
    }

    /**
     * Application de la transformation CSS
     */
    function renderTransform() {
        if (!stageEl) return;
        stageEl.style.transform = `translate(${mapState.x}px, ${mapState.y}px) scale(${mapState.scale})`;
        if (zoomIndicator) {
            zoomIndicator.textContent = Math.round(mapState.scale * 100) + '%';
        }
    }

    /**
     * Zoom centré sur la vue ou le curseur
     */
    function zoomMap(delta, clientX, clientY) {
        if (!viewportEl || !stageEl) return;
        const rect = viewportEl.getBoundingClientRect();
        const cursorX = (clientX !== undefined) ? (clientX - rect.left) : (viewportEl.clientWidth / 2);
        const cursorY = (clientY !== undefined) ? (clientY - rect.top) : (viewportEl.clientHeight / 2);

        const oldScale = mapState.scale;
        let newScale = oldScale + delta;
        newScale = Math.max(mapState.minScale, Math.min(mapState.maxScale, newScale));

        if (newScale === oldScale) return;

        // Conservation du point focal
        const contentX = (cursorX - mapState.x) / oldScale;
        const contentY = (cursorY - mapState.y) / oldScale;

        mapState.scale = newScale;
        mapState.x = cursorX - (contentX * newScale);
        mapState.y = cursorY - (contentY * newScale);

        clampMapCoordinates();
        renderTransform();
    }

    function resetZoom() {
        if (!viewportEl) return;
        mapState.scale = calculateMinScale(viewportEl.clientWidth, viewportEl.clientHeight);
        centerMap();
    }

    function centerMap() {
        if (!viewportEl) return;
        const containerW = viewportEl.clientWidth;
        const containerH = viewportEl.clientHeight;
        const scaledW = mapState.stageWidth * mapState.scale;
        const scaledH = mapState.stageHeight * mapState.scale;

        mapState.x = (containerW - scaledW) / 2;
        mapState.y = (containerH - scaledH) / 2;

        clampMapCoordinates();
        if (stageEl) {
            stageEl.classList.add('is-animating');
            renderTransform();
            setTimeout(() => stageEl && stageEl.classList.remove('is-animating'), 400);
        } else {
            renderTransform();
        }
    }

    function toggleFullscreen() {
        if (!viewportEl) return;
        if (!document.fullscreenElement) {
            viewportEl.requestFullscreen().catch(err => console.error(err));
        } else {
            document.exitFullscreen().catch(err => console.error(err));
        }
    }

    /**
     * Clic sur un badge de parcelle
     */
    window.handleRuralPinClick = function(el, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (mapState.hasMoved) return;

        openPlotUpgradeModal(el);
    };

    /**
     * Remplissage et ouverture de la modale d'amélioration
     */
    function openPlotUpgradeModal(pinEl) {
        if (!pinEl) return;
        modalEl = document.getElementById('plotUpgradeModal');
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

        // Miniature
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

        // Tags de coût
        const costContainer = document.getElementById('modalPlotCostTags');
        const isMax = d.isMax === '1';

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

        // Durée estimée
        const durSec = parseInt(d.duration, 10) || 30;
        const mins = Math.floor(durSec / 60);
        const secs = durSec % 60;
        const durEl = document.getElementById('modalPlotDuration');
        if (durEl) {
            durEl.textContent = isMax ? 'Terminé' : `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Bouton d'élévation
        const actionContainer = document.getElementById('modalPlotActionContainer');
        const canAfford = d.canAfford === '1';

        if (actionContainer) {
            if (isMax) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-secondary w-100 disabled" style="font-size:0.9rem;">
                        <i class="fa-solid fa-check me-1"></i> Apogée de la structure atteinte
                    </button>`;
            } else if (canAfford) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-${d.colorClass || 'primary'} w-100 fw-bold py-2 fs-3" onclick="upgradeRuralPlot('${d.type}', this)">
                        <i class="fa-solid fa-arrow-up me-1"></i> Élever au Niveau ${d.nextLevel} / ${d.maxLevel}
                    </button>`;
            } else {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-outline-secondary w-100 disabled py-2" style="font-size:0.85rem;">
                        <i class="fa-solid fa-lock me-1"></i> Ressources insuffisantes pour cette élévation
                    </button>`;
            }
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    /**
     * Action AJAX d'élévation
     */
    window.upgradeRuralPlot = async function(structureType, btn) {
        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Bénédiction & Chantier en cours...';
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
                alert(data.error || 'Impossible d\'élever cette structure.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
                }
            }
        } catch (err) {
            alert('Erreur réseau lors de l\'élévation de la structure.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
            }
        }
    };

    /**
     * Initialisation globale des événements de la carte
     */
    function initMap() {
        viewportEl = document.getElementById('ruralViewport');
        stageEl = document.getElementById('ruralStage');
        zoomIndicator = document.getElementById('ruralZoomIndicator');

        if (!viewportEl || !stageEl) return;

        // Grab and Pan (Souris & Touch)
        viewportEl.addEventListener('mousedown', e => {
            if (e.button !== 0) return;
            mapState.isDragging = true;
            mapState.hasMoved = false;
            mapState.dragStartX = e.clientX - mapState.x;
            mapState.dragStartY = e.clientY - mapState.y;
            viewportEl.classList.add('is-dragging');
        });

        window.addEventListener('mousemove', e => {
            if (!mapState.isDragging) return;
            const newX = e.clientX - mapState.dragStartX;
            const newY = e.clientY - mapState.dragStartY;
            if (Math.abs(newX - mapState.x) > 3 || Math.abs(newY - mapState.y) > 3) {
                mapState.hasMoved = true;
            }
            mapState.x = newX;
            mapState.y = newY;
            clampMapCoordinates();
            renderTransform();
        });

        window.addEventListener('mouseup', () => {
            if (mapState.isDragging) {
                mapState.isDragging = false;
                viewportEl && viewportEl.classList.remove('is-dragging');
            }
        });

        // Touch support
        let touchStartDist = 0;
        viewportEl.addEventListener('touchstart', e => {
            if (e.touches.length === 1) {
                mapState.isDragging = true;
                mapState.hasMoved = false;
                mapState.dragStartX = e.touches[0].clientX - mapState.x;
                mapState.dragStartY = e.touches[0].clientY - mapState.y;
            } else if (e.touches.length === 2) {
                touchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
            }
        }, { passive: true });

        viewportEl.addEventListener('touchmove', e => {
            if (e.touches.length === 1 && mapState.isDragging) {
                mapState.x = e.touches[0].clientX - mapState.dragStartX;
                mapState.y = e.touches[0].clientY - mapState.dragStartY;
                mapState.hasMoved = true;
                clampMapCoordinates();
                renderTransform();
            } else if (e.touches.length === 2 && touchStartDist > 0) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const delta = (dist - touchStartDist) * 0.005;
                zoomMap(delta);
                touchStartDist = dist;
            }
        }, { passive: true });

        viewportEl.addEventListener('touchend', () => {
            mapState.isDragging = false;
            touchStartDist = 0;
        });

        // Molette souris
        viewportEl.addEventListener('wheel', e => {
            e.preventDefault();
            const delta = (e.deltaY < 0) ? 0.15 : -0.15;
            zoomMap(delta, e.clientX, e.clientY);
        }, { passive: false });

        // Ajuster au démarrage
        resetZoom();

        // Réajustement lors du redimensionnement de la fenêtre
        window.addEventListener('resize', () => {
            clampMapCoordinates();
            renderTransform();
        });
    }

    // Export fonctions globales
    window.zoomRuralMap = delta => zoomMap(delta);
    window.resetRuralMapZoom = resetZoom;
    window.centerRuralMap = centerMap;
    window.toggleRuralFullscreen = toggleFullscreen;

    // Démarrage
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }

})(window, document);
