/**
 * OpenShogun - Moteur Cartographique et Interactif du Domaine Rural Féodal (16:9)
 * Gère le Viewport Grab-and-Pan, le verrouillage strict Pan/Zoom Clamping (minZoom & Bounding Box),
 * les 40 parcelles réparties sur 8 biomes, les filtres par catégorie et la modale d'amélioration.
 */

(function(window, document) {
    'use strict';

    // Coordonnées de focalisation des 8 zones biomes (pourcentages scène 16:9)
    const zoneCenters = {
        'stone':   { left: 24.5, top: 17.5 },
        'wood':    { left: 66.0, top: 22.0 },
        'clay':    { left: 89.5, top: 44.0 },
        'rice':    { left: 59.0, top: 72.0 },
        'tea':     { left: 90.0, top: 81.0 },
        'soybean': { left: 24.0, top: 82.0 },
        'shrine':  { left: 14.0, top: 53.0 },
        'housing': { left: 47.5, top: 44.0 },
        'all':     { left: 50.0, top: 50.0 }
    };

    // État interne du moteur de carte
    const terroirMapState = {
        stageWidth: 1376,
        stageHeight: 768,
        scale: 1.0,
        minScale: 0.6,
        maxScale: 2.2,
        x: 0,
        y: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        hasMoved: false,
        activeCategory: 'all'
    };

    let viewportEl = null;
    let stageEl = null;
    let zoomIndicator = null;

    /**
     * Calcule dynamiquement le ratio d'échelle minimal pour recouvrir 100% du viewport.
     * Empêche formellement l'apparition de bordures vides ou de fond noir.
     * minScale = Math.max(containerWidth / imageWidth, containerHeight / imageHeight)
     */
    function calculateMinScale(containerWidth, containerHeight) {
        if (!containerWidth || !containerHeight) return 0.6;
        return Math.max(
            containerWidth / terroirMapState.stageWidth,
            containerHeight / terroirMapState.stageHeight
        );
    }

    /**
     * Verrouillage du Zoom & Déplacement (Pan/Zoom Clamping)
     * Contrainte 1 : scale >= minScale (couverture intégrale à 100% du conteneur).
     * Contrainte 2 : x borné dans [minX, 0] et y borné dans [minY, 0].
     */
    function clampMapCoordinates() {
        if (!viewportEl) return;
        const containerWidth = viewportEl.clientWidth;
        const containerHeight = viewportEl.clientHeight;
        if (containerWidth <= 0 || containerHeight <= 0) return;

        // 1. Limite minimale de dézoom dynamique (minZoom / Fit-to-screen)
        const minScale = calculateMinScale(containerWidth, containerHeight);
        terroirMapState.minScale = minScale;
        if (terroirMapState.scale < minScale) {
            terroirMapState.scale = minScale;
        }

        // 2. Verrouillage strict des bords au glissement (Clamp Pan / Bounding Box)
        const currentZoom = terroirMapState.scale;
        const minX = containerWidth - (terroirMapState.stageWidth * currentZoom);
        const maxX = 0;
        const minY = containerHeight - (terroirMapState.stageHeight * currentZoom);
        const maxY = 0;

        terroirMapState.x = Math.min(Math.max(terroirMapState.x, minX), maxX);
        terroirMapState.y = Math.min(Math.max(terroirMapState.y, minY), maxY);
    }

    /**
     * Applique la transformation CSS translate + scale sur la scène panoramique
     */
    function renderMapTransform() {
        if (!stageEl) return;
        stageEl.style.transform = `translate(${terroirMapState.x}px, ${terroirMapState.y}px) scale(${terroirMapState.scale})`;
        if (zoomIndicator) {
            zoomIndicator.textContent = Math.round(terroirMapState.scale * 100) + '%';
        }
    }

    /**
     * Zoom fluide centré sur le point du curseur ou le centre de la vue
     */
    function zoomAtCursor(delta, clientX, clientY) {
        if (!viewportEl || !stageEl) return;
        const containerWidth = viewportEl.clientWidth;
        const containerHeight = viewportEl.clientHeight;
        const minScale = calculateMinScale(containerWidth, containerHeight);
        terroirMapState.minScale = minScale;

        const oldScale = terroirMapState.scale;
        let newScale = oldScale + delta;
        newScale = Math.min(terroirMapState.maxScale, Math.max(minScale, newScale));

        if (Math.abs(newScale - oldScale) < 0.001) {
            clampMapCoordinates();
            renderMapTransform();
            return;
        }

        const rect = viewportEl.getBoundingClientRect();
        const cursorX = clientX - rect.left;
        const cursorY = clientY - rect.top;

        // Conservation du point d'ancrage sous la souris
        const stageX = (cursorX - terroirMapState.x) / oldScale;
        const stageY = (cursorY - terroirMapState.y) / oldScale;

        terroirMapState.scale = newScale;
        terroirMapState.x = cursorX - (stageX * newScale);
        terroirMapState.y = cursorY - (stageY * newScale);

        clampMapCoordinates();
        stageEl.classList.remove('is-animating');
        renderMapTransform();
    }

    /**
     * Contrôles de zoom (+ / -) via les boutons de la barre d'outils
     */
    function zoomTerroirMap(delta) {
        if (!viewportEl) return;
        const rect = viewportEl.getBoundingClientRect();
        zoomAtCursor(delta, rect.left + (viewportEl.clientWidth / 2), rect.top + (viewportEl.clientHeight / 2));
    }

    /**
     * Réinitialisation ergonomique du zoom (ajustement fit-to-screen ou 100%)
     */
    function resetTerroirMapZoom() {
        if (!viewportEl) return;
        const containerWidth = viewportEl.clientWidth;
        const containerHeight = viewportEl.clientHeight;
        const minScale = calculateMinScale(containerWidth, containerHeight);
        terroirMapState.minScale = minScale;
        terroirMapState.scale = Math.max(minScale, 1.0);
        centerOnZone(terroirMapState.activeCategory, true);
    }

    /**
     * Recentrer le domaine rural entier
     */
    function centerTerroirMap() {
        centerOnZone('all', true);
    }

    /**
     * Déplacement cinématique fluide vers une zone cible
     */
    function centerOnZone(zoneKey, animate = true) {
        if (!viewportEl || !stageEl) return;
        const center = zoneCenters[zoneKey] || zoneCenters['all'];
        const containerWidth = viewportEl.clientWidth;
        const containerHeight = viewportEl.clientHeight;

        const targetX = terroirMapState.stageWidth * (center.left / 100);
        const targetY = terroirMapState.stageHeight * (center.top / 100);

        terroirMapState.x = (containerWidth / 2) - (targetX * terroirMapState.scale);
        terroirMapState.y = (containerHeight / 2) - (targetY * terroirMapState.scale);

        clampMapCoordinates();

        if (animate) {
            stageEl.classList.add('is-animating');
            renderMapTransform();
            setTimeout(() => {
                if (stageEl) stageEl.classList.remove('is-animating');
            }, 450);
        } else {
            renderMapTransform();
        }
    }

    /**
     * Bascule plein écran
     */
    function toggleTerroirFullscreen() {
        if (!viewportEl) return;
        if (!document.fullscreenElement) {
            viewportEl.requestFullscreen().catch(() => {
                alert("Erreur d'activation plein écran");
            });
        } else {
            document.exitFullscreen();
        }
    }

    /**
     * Bascule d'affichage : Carte Illustrée Panoramique vs Grille Tactique des 40 Parcelles
     */
    function switchTerroirView(mode) {
        const mapContainer = document.getElementById('terroirIllustratedMapView');
        const gridContainer = document.getElementById('terroirTacticalGridView');
        const btnMap = document.getElementById('btnModeMap');
        const btnGrid = document.getElementById('btnModeGrid');

        if (mode === 'map') {
            if (mapContainer) mapContainer.style.display = 'block';
            if (gridContainer) gridContainer.style.display = 'none';
            if (btnMap) {
                btnMap.classList.add('btn-dark', 'active');
                btnMap.classList.remove('btn-outline-secondary');
            }
            if (btnGrid) {
                btnGrid.classList.remove('btn-dark', 'active');
                btnGrid.classList.add('btn-outline-secondary');
            }
            clampMapCoordinates();
            renderMapTransform();
            centerOnZone(terroirMapState.activeCategory, false);
        } else {
            if (mapContainer) mapContainer.style.display = 'none';
            if (gridContainer) gridContainer.style.display = 'block';
            if (btnGrid) {
                btnGrid.classList.add('btn-dark', 'active');
                btnGrid.classList.remove('btn-outline-secondary');
            }
            if (btnMap) {
                btnMap.classList.remove('btn-dark', 'active');
                btnMap.classList.add('btn-outline-secondary');
            }
        }
    }

    /**
     * Filtrage et ciblage des 8 Catégories Thématiques
     */
    function filterCategory(catKey, btn) {
        terroirMapState.activeCategory = catKey;

        // Mise à jour de l'apparence des boutons de filtre
        document.querySelectorAll('.btn-filter-cat').forEach(b => {
            b.classList.remove('active', 'btn-dark');
            if (!b.classList.contains('btn-outline-success') && 
                !b.classList.contains('btn-outline-secondary') && 
                !b.classList.contains('btn-outline-warning') && 
                !b.classList.contains('btn-outline-teal') && 
                !b.classList.contains('btn-outline-orange') && 
                !b.classList.contains('btn-outline-pink') && 
                !b.classList.contains('btn-outline-primary')) {
                b.classList.add('btn-outline-dark');
            }
        });

        if (btn) {
            btn.classList.add('active');
        }

        // 1. Filtrage sur la Carte Illustrée : Mise en surbrillance des pins
        const allPins = document.querySelectorAll('.map-parcel-pin');
        allPins.forEach(pin => {
            pin.classList.remove('is-dimmed', 'is-highlighted');
            if (catKey === 'all') {
                // Tout visible
            } else if (pin.dataset.cat === catKey) {
                pin.classList.add('is-highlighted');
            } else {
                pin.classList.add('is-dimmed');
            }
        });

        // Recentrage caméra sur la zone
        centerOnZone(catKey, true);

        // 2. Filtrage sur la Grille Tactique
        const sections = document.querySelectorAll('.terroir-category-section');
        sections.forEach(sec => {
            if (catKey === 'all' || sec.dataset.cat === catKey) {
                sec.style.display = 'block';
            } else {
                sec.style.display = 'none';
            }
        });
    }

    /**
     * Gestion du clic sur un Pin de la carte (ignore si un drag a eu lieu)
     */
    function handlePinClick(pinEl, e) {
        if (terroirMapState.hasMoved) {
            return;
        }
        openParcelUpgradeModal(pinEl);
    }

    /**
     * Ouverture et remplissage dynamique de la Modale d'Amélioration
     */
    function openParcelUpgradeModal(pinEl) {
        const d = pinEl.dataset;
        const modalEl = document.getElementById('parcelUpgradeModal');
        if (!modalEl) return;

        // Stocks du joueur
        const stocks = (window.TERROIR_CONFIG && window.TERROIR_CONFIG.stocks) ? window.TERROIR_CONFIG.stocks : {
            metal: 0, crystal: 0, deuterium: 0
        };

        // Titre et icône
        const titleEl = document.getElementById('modalTitle');
        const jpSubtitleEl = document.getElementById('modalJpSubtitle');
        const lvlBadge = document.getElementById('modalLevelBadge');
        const catBadge = document.getElementById('modalCategoryBadge');
        const descEl = document.getElementById('modalDesc');

        if (titleEl) titleEl.textContent = d.name;
        if (jpSubtitleEl) jpSubtitleEl.textContent = d.jpName;
        if (lvlBadge) lvlBadge.textContent = 'Niveau ' + d.level;
        if (catBadge) {
            catBadge.textContent = d.categoryName;
            catBadge.className = 'badge bg-' + d.colorClass + '-lt fw-bold';
        }
        if (descEl) descEl.textContent = d.desc;

        // Avatar
        const avatar = document.getElementById('modalIconAvatar');
        if (avatar) {
            avatar.className = 'avatar avatar-sm rounded text-white bg-' + d.colorClass;
            avatar.innerHTML = `<i class="${d.icon}"></i>`;
        }

        // Vignette
        const thumb = document.getElementById('modalVisualThumb');
        if (thumb) {
            thumb.style.backgroundImage = `url('${d.tileImg}')`;
        }

        // Ouvriers
        const roleEl = document.getElementById('modalWorkerRole');
        const countEl = document.getElementById('modalWorkersCount');
        if (roleEl) roleEl.textContent = d.workerRole;
        if (countEl) countEl.textContent = d.workers;

        // Rendements
        const curProdEl = document.getElementById('modalCurrentProd');
        if (curProdEl) curProdEl.textContent = d.prodLabel;

        const nextLvl = parseInt(d.level, 10) + 1;
        let nextProdText = '+?? / h';
        if (d.resType === 'housing') {
            nextProdText = '+' + (nextLvl * 5) + ' hab. (Niv. ' + nextLvl + ' × 5)';
        } else if (d.resType === 'shrine') {
            nextProdText = '+' + (nextLvl * 25) + ' Sérénité';
        } else {
            const curProd = parseInt((d.prodLabel || '').replace(/[^0-9]/g, ''), 10) || 30;
            nextProdText = '+' + Math.round(curProd * 1.35) + ' / h';
        }
        const nextProdEl = document.getElementById('modalNextProd');
        if (nextProdEl) nextProdEl.textContent = nextProdText;

        // Coûts
        const costContainer = document.getElementById('modalCostTags');
        if (costContainer) {
            costContainer.innerHTML = '';
            const costWood = parseInt(d.costWood, 10) || 0;
            const costStone = parseInt(d.costStone, 10) || 0;
            const costRice = parseInt(d.costRice, 10) || 0;

            const addCostTag = (icon, name, costVal, playerStock) => {
                if (costVal <= 0) return;
                const isOk = playerStock >= costVal;
                const tag = document.createElement('span');
                tag.className = 'cost-chip ' + (isOk ? 'affordable' : 'missing');
                tag.innerHTML = `<i class="${icon}"></i> ${name} : <strong>${costVal.toLocaleString()}</strong>`;
                costContainer.appendChild(tag);
            };

            addCostTag('fa-solid fa-tree text-success', 'Bois', costWood, stocks.metal);
            addCostTag('fa-solid fa-mountain text-secondary', 'Pierre', costStone, stocks.crystal);
            addCostTag('fa-solid fa-wheat-awn text-warning', 'Riz', costRice, stocks.deuterium);
        }

        // Durée
        const durSec = parseInt(d.duration, 10) || 60;
        const mins = Math.floor(durSec / 60);
        const secs = durSec % 60;
        const durEl = document.getElementById('modalDuration');
        if (durEl) durEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

        // Bouton d'action
        const actionContainer = document.getElementById('modalActionContainer');
        const isUpgrading = d.isUpgrading === '1';
        const canAfford = d.canAfford === '1';

        if (actionContainer) {
            if (isUpgrading) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-warning w-100 disabled" style="font-size:0.85rem;">
                        <i class="fa-solid fa-hourglass-half fa-spin me-1"></i> Chantier en cours d'exécution...
                    </button>`;
            } else if (canAfford) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-${d.colorClass} w-100 fw-bold" onclick="upgradeTerroirSlot('${d.resType}', ${d.slotIdx}, this)">
                        <i class="fa-solid fa-arrow-up me-1"></i> Élever au Niveau ${nextLvl}
                    </button>`;
            } else {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-outline-secondary w-100 disabled" style="font-size:0.85rem;">
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
     * Action asynchrone AJAX pour élever une parcelle du domaine
     */
    async function upgradeTerroirSlot(resourceType, slotIdx, btn) {
        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Chantiers...';
            }

            const formData = new FormData();
            formData.append('action', 'upgrade');
            formData.append('resource_type', resourceType);
            formData.append('slot_index', slotIdx);

            const res = await fetch('/api/terroir.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Impossible de lancer l\'amélioration de la parcelle.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
                }
            }
        } catch (err) {
            alert('Erreur réseau lors de l\'amélioration de la parcelle.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
            }
        }
    }

    /**
     * Annulation d'un chantier en cours
     */
    async function cancelBuild(queueId) {
        if (!confirm('Voulez-vous vraiment annuler ce chantier ? (80% des ressources seront remboursées)')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('queue_id', queueId);

            const res = await fetch('/api/build.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Impossible d\'annuler ce chantier.');
            }
        } catch (err) {
            alert('Erreur réseau lors de l\'annulation.');
        }
    }

    /**
     * Initialisation globale du Viewport Terroir (Grab-and-Pan & Events)
     */
    function initTerroirViewport() {
        viewportEl = document.getElementById('terroirViewport');
        stageEl = document.getElementById('terroirMapStage');
        zoomIndicator = document.getElementById('zoomLevelIndicator');

        if (!viewportEl || !stageEl) return;

        // Calcul de l'échelle initiale en garantissant la couverture minimale 100%
        const containerWidth = viewportEl.clientWidth || 1100;
        const containerHeight = viewportEl.clientHeight || 720;
        const minScale = calculateMinScale(containerWidth, containerHeight);
        terroirMapState.minScale = minScale;
        terroirMapState.scale = Math.max(minScale, Math.min(1.2, containerWidth / 1376));

        // Centrage initial sur le fief avec clamping strict
        centerOnZone('all', false);

        // 1. Écouteurs de souris pour le Glissement (Grab-to-Pan)
        viewportEl.addEventListener('mousedown', (e) => {
            if (e.target.closest('.terroir-map-toolbar') || e.target.closest('.map-castle-pin')) return;
            
            terroirMapState.isDragging = true;
            terroirMapState.hasMoved = false;
            terroirMapState.dragStartX = e.clientX - terroirMapState.x;
            terroirMapState.dragStartY = e.clientY - terroirMapState.y;
            viewportEl.classList.add('is-dragging');
            stageEl.classList.remove('is-animating');
        });

        window.addEventListener('mousemove', (e) => {
            if (!terroirMapState.isDragging) return;
            const newX = e.clientX - terroirMapState.dragStartX;
            const newY = e.clientY - terroirMapState.dragStartY;

            if (Math.abs(newX - terroirMapState.x) > 4 || Math.abs(newY - terroirMapState.y) > 4) {
                terroirMapState.hasMoved = true;
            }

            terroirMapState.x = newX;
            terroirMapState.y = newY;
            clampMapCoordinates();
            renderMapTransform();
        });

        window.addEventListener('mouseup', () => {
            if (terroirMapState.isDragging) {
                terroirMapState.isDragging = false;
                if (viewportEl) viewportEl.classList.remove('is-dragging');
            }
        });

        // 2. Écouteurs tactiles (Mobile & Tablettes)
        let touchStartDist = 0;
        let initialTouchScale = 1;

        viewportEl.addEventListener('touchstart', (e) => {
            if (e.target.closest('.terroir-map-toolbar') || e.target.closest('.map-castle-pin')) return;

            if (e.touches.length === 1) {
                terroirMapState.isDragging = true;
                terroirMapState.hasMoved = false;
                terroirMapState.dragStartX = e.touches[0].clientX - terroirMapState.x;
                terroirMapState.dragStartY = e.touches[0].clientY - terroirMapState.y;
                stageEl.classList.remove('is-animating');
            } else if (e.touches.length === 2) {
                terroirMapState.isDragging = false;
                touchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                initialTouchScale = terroirMapState.scale;
            }
        }, { passive: true });

        viewportEl.addEventListener('touchmove', (e) => {
            if (terroirMapState.isDragging && e.touches.length === 1) {
                const newX = e.touches[0].clientX - terroirMapState.dragStartX;
                const newY = e.touches[0].clientY - terroirMapState.dragStartY;
                if (Math.abs(newX - terroirMapState.x) > 4 || Math.abs(newY - terroirMapState.y) > 4) {
                    terroirMapState.hasMoved = true;
                }
                terroirMapState.x = newX;
                terroirMapState.y = newY;
                clampMapCoordinates();
                renderMapTransform();
            } else if (e.touches.length === 2 && touchStartDist > 0) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const factor = dist / touchStartDist;
                const minScale = calculateMinScale(viewportEl.clientWidth, viewportEl.clientHeight);
                terroirMapState.minScale = minScale;
                terroirMapState.scale = Math.min(terroirMapState.maxScale, Math.max(minScale, initialTouchScale * factor));
                clampMapCoordinates();
                renderMapTransform();
            }
        }, { passive: true });

        viewportEl.addEventListener('touchend', () => {
            terroirMapState.isDragging = false;
        });

        // 3. Zoom à la molette
        viewportEl.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.12 : -0.12;
            zoomAtCursor(delta, e.clientX, e.clientY);
        }, { passive: false });

        // 4. Écouteur de redimensionnement de fenêtre et plein écran
        window.addEventListener('resize', () => {
            clampMapCoordinates();
            renderMapTransform();
        });

        document.addEventListener('fullscreenchange', () => {
            setTimeout(() => {
                clampMapCoordinates();
                renderMapTransform();
            }, 100);
        });
    }

    // Export des méthodes vers window pour les appels HTML
    window.zoomTerroirMap = zoomTerroirMap;
    window.resetTerroirMapZoom = resetTerroirMapZoom;
    window.centerTerroirMap = centerTerroirMap;
    window.centerOnZone = centerOnZone;
    window.toggleTerroirFullscreen = toggleTerroirFullscreen;
    window.switchTerroirView = switchTerroirView;
    window.filterCategory = filterCategory;
    window.handlePinClick = handlePinClick;
    window.openParcelUpgradeModal = openParcelUpgradeModal;
    window.upgradeTerroirSlot = upgradeTerroirSlot;
    window.cancelBuild = cancelBuild;

    // Démarrage automatique au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTerroirViewport);
    } else {
        initTerroirViewport();
    }

})(window, document);
