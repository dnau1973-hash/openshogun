/**
 * Moteur de Carte des Provinces et Fiefs du Japon Féodal (OpenShogun)
 * Système de navigation fluide avec caméra continue au pixel, inertie cinétique (momentum),
 * anneaux de tuiles tampons (overscan sans bord vide), zéro rebond snap-back et contrôles tactiles / clavier.
 */

class GalaxyMapController {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        // Position de la caméra en coordonnées continues (flottantes)
        this.cameraX = Number(options.initialX) || 0;
        this.cameraY = Number(options.initialY) || 0;
        this.gridCenterX = Math.round(this.cameraX);
        this.gridCenterY = Math.round(this.cameraY);

        this.playerX = options.playerX || 0;
        this.playerY = options.playerY || 0;
        this.currentUserId = options.userId || 0;

        this.tileSize = 80; // pixels par case
        this.gap = 0;       // Grille continue sans séparation
        this.stepSize = this.tileSize;

        // Anneau de tuiles de débordement hors écran (overscan) pour éliminer les bords vides
        this.overscan = 2;

        // Calcul dynamique du rayon pour occuper toute la surface disponible
        if (options.radius && options.radius > 0) {
            this.baseRadius = options.radius;
        } else {
            const availW = Math.max(window.innerWidth, this.container.clientWidth || 1200);
            const neededCols = Math.ceil(availW / this.tileSize) + 2;
            this.baseRadius = Math.min(11, Math.max(5, Math.ceil((neededCols - 1) / 2)));
        }
        this.radius = this.baseRadius + this.overscan;

        // État de glissement et physique de déplacement
        this.isDragging = false;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.lastPointerX = 0;
        this.lastPointerY = 0;
        this.totalDistanceMoved = 0;
        this.pointerSamples = [];
        this.momentumId = null;
        this.snapAnimationId = null;
        this.fetchDebounceTimer = null;
        this.wheelSnapTimer = null;

        // Caches locaux
        this.planetsCache = {};
        this.terrainsCache = {};
        this.currentQuadrant = null;
        this.isLoading = false;

        this.initDOM();
        this.bindEvents();
        this.renderGrid();
        this.applyTransform();
        this.loadSector(this.gridCenterX, this.gridCenterY);
    }

    // Propriétés de compatibilité
    get centerX() { return Math.round(this.cameraX); }
    set centerX(val) { this.cameraX = val; }
    get centerY() { return Math.round(this.cameraY); }
    set centerY(val) { this.cameraY = val; }

    initDOM() {
        this.container.innerHTML = `
            <div class="galaxy-hud-overlay">
                <!-- Badge Coordonnées (Haut Gauche) -->
                <div class="hud-coords-badge">
                    <span id="hudQuadrantDisplay"><i class="fa-solid fa-compass me-1"></i>Provinces</span>
                    <strong id="hudCoordsDisplay">[${this.centerX} : ${this.centerY}]</strong>
                </div>
            </div>

            <div class="galaxy-viewport" id="galaxyViewport">
                <div class="galaxy-canvas" id="galaxyCanvas">
                    <!-- Grille générée dynamiquement -->
                </div>
                <div class="galaxy-drag-hint" id="galaxyDragHint">
                    <span><i class="fa-solid fa-hand me-1"></i>Glissez librement pour explorer l'Archipel (Flèches, ZQSD ou souris)</span>
                </div>

                <!-- Boîtier de Navigation Féodale Unifié en Bas à Droite (Mini-Map Centrale + Flèches Extérieures) -->
                <div class="map-unified-nav-pad" id="mapUnifiedNavPad" title="Boîtier de navigation féodale">
                    <!-- Ligne 1 : Haut -->
                    <div class="nav-pad-corner corner-compass" title="Boussole féodale"><i class="fa-solid fa-compass text-danger"></i></div>
                    <button type="button" class="nav-pad-arrow arrow-up" id="btnArrowUp" title="Nord (Haut / Z / Flèche Haut)"><i class="fa-solid fa-caret-up"></i></button>
                    <button type="button" class="nav-pad-corner corner-zoom-in" id="btnZoomIn" title="Zoom avant (agrandir)"><i class="fa-solid fa-plus"></i></button>

                    <!-- Ligne 2 : Milieu avec Flèches Extérieures & Cœur Mini-Map -->
                    <button type="button" class="nav-pad-arrow arrow-left" id="btnArrowLeft" title="Ouest (Gauche / Q / Flèche Gauche)"><i class="fa-solid fa-caret-left"></i></button>
                    
                    <div class="nav-pad-minimap-core" id="minimapCoreGrid" title="Mini-Map des 4 Quadrants Féodaux">
                        <button type="button" class="minimap-q-cell q-nw" id="btnQuadrantNO" title="Terres du Nord-Ouest [- / +]">N-O</button>
                        <button type="button" class="minimap-q-cell q-ne" id="btnQuadrantNE" title="Terres du Nord-Est [+ / +]">N-E</button>
                        <button type="button" class="minimap-q-cell q-sw" id="btnQuadrantSO" title="Terres du Sud-Ouest [- / -]">S-O</button>
                        <button type="button" class="minimap-q-cell q-se" id="btnQuadrantSE" title="Terres du Sud-Est [+ / -]">S-E</button>
                        <button type="button" class="minimap-center-torii" id="btnQuadrantKyoto" title="Capitale Impériale [0 : 0]">
                            <i class="fa-solid fa-torii-gate"></i>
                        </button>
                    </div>

                    <button type="button" class="nav-pad-arrow arrow-right" id="btnArrowRight" title="Est (Droite / D / Flèche Droite)"><i class="fa-solid fa-caret-right"></i></button>

                    <!-- Ligne 3 : Bas -->
                    <button type="button" class="nav-pad-corner corner-home" id="btnRecenterColonyDpad" title="Recentrer sur mon Fief seigneurial"><i class="fa-solid fa-chess-rook"></i></button>
                    <button type="button" class="nav-pad-arrow arrow-down" id="btnArrowDown" title="Sud (Bas / S / Flèche Bas)"><i class="fa-solid fa-caret-down"></i></button>
                    <button type="button" class="nav-pad-corner corner-zoom-out" id="btnZoomOut" title="Zoom arrière (réduire)"><i class="fa-solid fa-minus"></i></button>
                </div>
            </div>
        `;

        this.viewport = document.getElementById('galaxyViewport');
        this.canvas = document.getElementById('galaxyCanvas');
        this.coordsDisplay = document.getElementById('hudCoordsDisplay');
        this.dragHint = document.getElementById('galaxyDragHint');
    }

    bindEvents() {
        // Événements pointeur continus (souris, stylet et tactile smartphone/tablette)
        this.viewport.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        window.addEventListener('pointermove', (e) => this.onPointerMove(e));
        window.addEventListener('pointerup', (e) => this.onPointerUp(e));
        window.addEventListener('pointercancel', (e) => this.onPointerUp(e));

        // Empêcher le glisser d'image natif du navigateur
        this.viewport.addEventListener('dragstart', (e) => e.preventDefault());

        // Navigation molette souris douce
        this.viewport.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });

        // Navigation Quadrants
        document.getElementById('btnQuadrantNO').onclick = () => this.moveTo(-16, 16);
        document.getElementById('btnQuadrantNE').onclick = () => this.moveTo(16, 16);
        document.getElementById('btnQuadrantSO').onclick = () => this.moveTo(-16, -16);
        document.getElementById('btnQuadrantSE').onclick = () => this.moveTo(16, -16);
        document.getElementById('btnQuadrantKyoto').onclick = () => this.moveTo(0, 0);

        // Croix Directionnelle Féodale en Bas à Droite (D-Pad)
        const btnUp = document.getElementById('btnArrowUp');
        const btnDown = document.getElementById('btnArrowDown');
        const btnLeft = document.getElementById('btnArrowLeft');
        const btnRight = document.getElementById('btnArrowRight');
        const btnCenterDpad = document.getElementById('btnRecenterColonyDpad');

        const bindDpad = (btn, dx, dy) => {
            if (!btn) return;
            let holdTimeout = null;
            let holdInterval = null;

            const clearHold = () => {
                if (holdTimeout) { clearTimeout(holdTimeout); holdTimeout = null; }
                if (holdInterval) { clearInterval(holdInterval); holdInterval = null; }
            };

            btn.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.smoothMoveBy(dx, dy);
                clearHold();
                holdTimeout = setTimeout(() => {
                    holdInterval = setInterval(() => {
                        this.smoothMoveBy(dx, dy);
                    }, 110);
                }, 280);
            });

            btn.addEventListener('pointerup', clearHold);
            btn.addEventListener('pointerleave', clearHold);
            btn.addEventListener('pointercancel', clearHold);
        };

        bindDpad(btnUp, 0, 1);
        bindDpad(btnDown, 0, -1);
        bindDpad(btnLeft, -1, 0);
        bindDpad(btnRight, 1, 0);

        if (btnCenterDpad) {
            btnCenterDpad.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.moveTo(this.playerX, this.playerY);
            });
        }

        // Recentrage sur la capitale du joueur
        const btnRecenter = document.getElementById('btnRecenterColony');
        if (btnRecenter) {
            btnRecenter.onclick = () => {
                this.moveTo(this.playerX, this.playerY);
            };
        }

        // Boutons de Zoom
        document.getElementById('btnZoomIn').onclick = () => {
            if (this.tileSize < 112) {
                this.tileSize += 12;
                this.stepSize = this.tileSize;
                this.renderGrid();
                this.applyTransform();
            }
        };

        document.getElementById('btnZoomOut').onclick = () => {
            if (this.tileSize > 52) {
                this.tileSize -= 12;
                this.stepSize = this.tileSize;
                this.renderGrid();
                this.applyTransform();
            }
        };

        // Navigation Clavier fluide (Flèches, ZQSD et WASD)
        window.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;

            let deltaX = 0;
            let deltaY = 0;

            if (e.key === 'ArrowUp' || e.code === 'KeyW' || e.code === 'KeyZ') deltaY = 1;
            else if (e.key === 'ArrowDown' || e.code === 'KeyS') deltaY = -1;
            else if (e.key === 'ArrowLeft' || e.code === 'KeyA' || e.code === 'KeyQ') deltaX = -1;
            else if (e.key === 'ArrowRight' || e.code === 'KeyD') deltaX = 1;

            if (deltaX !== 0 || deltaY !== 0) {
                e.preventDefault();
                this.smoothMoveBy(deltaX, deltaY);
            }
        });

        // Redimensionnement automatique de la grille selon la taille de fenêtre
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const availW = Math.max(window.innerWidth, this.container.clientWidth || 1200);
                const neededCols = Math.ceil(availW / this.tileSize) + 2;
                const newBaseRadius = Math.min(11, Math.max(5, Math.ceil((neededCols - 1) / 2)));
                const newRadius = newBaseRadius + this.overscan;
                if (newRadius !== this.radius) {
                    this.radius = newRadius;
                    this.renderGrid();
                    this.applyTransform();
                    this.loadSector(this.gridCenterX, this.gridCenterY);
                }
            }, 250);
        });
    }

    onPointerDown(e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        if (e.target.closest('.map-dpad-cross') || e.target.closest('.galaxy-hud-overlay')) return;

        // Interrompre tout mouvement d'inertie ou calage en cours
        if (this.momentumId) {
            cancelAnimationFrame(this.momentumId);
            this.momentumId = null;
        }
        if (this.snapAnimationId) {
            cancelAnimationFrame(this.snapAnimationId);
            this.snapAnimationId = null;
        }

        this.isDragging = true;
        this.dragStartX = e.clientX;
        this.dragStartY = e.clientY;
        this.lastPointerX = e.clientX;
        this.lastPointerY = e.clientY;
        this.totalDistanceMoved = 0;

        const now = performance.now();
        this.pointerSamples = [{ x: e.clientX, y: e.clientY, time: now }];

        this.viewport.classList.add('grabbing');
        this.canvas.style.transition = 'none';
    }

    onPointerMove(e) {
        if (!this.isDragging) return;

        const dx = e.clientX - this.lastPointerX;
        const dy = e.clientY - this.lastPointerY;
        this.lastPointerX = e.clientX;
        this.lastPointerY = e.clientY;
        this.totalDistanceMoved += Math.hypot(dx, dy);

        // Masquer l'aide de glissement une fois que le joueur a compris le geste
        if (this.totalDistanceMoved > 14 && this.dragHint) {
            this.dragHint.style.opacity = '0';
        }

        // Échantillonnage pour calculer la vitesse de lancer (momentum)
        const now = performance.now();
        this.pointerSamples.push({ x: e.clientX, y: e.clientY, time: now });
        while (this.pointerSamples.length > 5 || (this.pointerSamples.length > 2 && now - this.pointerSamples[0].time > 100)) {
            this.pointerSamples.shift();
        }

        this.panByDelta(dx, dy);
    }

    onPointerUp(e) {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.viewport.classList.remove('grabbing');

        // Calcul de la vélocité sur les derniers points d'échantillonnage
        let vx = 0;
        let vy = 0;
        if (this.pointerSamples.length >= 2) {
            const oldest = this.pointerSamples[0];
            const newest = this.pointerSamples[this.pointerSamples.length - 1];
            const dt = newest.time - oldest.time;
            if (dt > 10 && dt < 140) {
                vx = (newest.x - oldest.x) / dt;
                vy = (newest.y - oldest.y) / dt;
            }
        }

        const speed = Math.hypot(vx, vy);
        if (speed > 0.18) {
            // Lancer la glisse inertielle (momentum)
            this.startMomentum(vx * 16, vy * 16);
        } else {
            // Calage doux et magnétique sur la tuile la plus proche
            this.smoothSnapToCenter();
        }
    }

    /**
     * Déplacement relatif en pixels (utilisé lors du drag, du momentum et de la molette)
     */
    panByDelta(dx, dy) {
        // Translation continue de la caméra
        this.cameraX -= dx / this.tileSize;
        this.cameraY += dy / this.tileSize;

        // Détection du décalage de tuiles entières pour le recyclage transparent de la grille
        const shiftX = Math.round(this.cameraX - this.gridCenterX);
        const shiftY = Math.round(this.cameraY - this.gridCenterY);

        if (shiftX !== 0 || shiftY !== 0) {
            this.gridCenterX += shiftX;
            this.gridCenterY += shiftY;
            // Rendu instantané depuis le cache local (zéro latence)
            this.renderGrid();
            this.scheduleSectorFetch(this.gridCenterX, this.gridCenterY);
        }

        this.applyTransform();
        this.updateCoordsDisplay();
    }

    /**
     * Applique la transformation GPU exacte au sous-pixel près
     */
    applyTransform() {
        const pixelShiftX = -(this.cameraX - this.gridCenterX) * this.tileSize;
        const pixelShiftY = (this.cameraY - this.gridCenterY) * this.tileSize;
        this.canvas.style.transform = `translate3d(${pixelShiftX.toFixed(2)}px, ${pixelShiftY.toFixed(2)}px, 0)`;
    }

    /**
     * Glisse cinétique avec décélération exponentielle (Momentum Scrolling)
     */
    startMomentum(vx, vy) {
        if (this.momentumId) cancelAnimationFrame(this.momentumId);
        let curVx = vx;
        let curVy = vy;
        const friction = 0.925; // Facteur de glisse organique

        const step = () => {
            if (this.isDragging) return;
            curVx *= friction;
            curVy *= friction;

            if (Math.hypot(curVx, curVy) < 0.25) {
                this.smoothSnapToCenter();
                return;
            }

            this.panByDelta(curVx, curVy);
            this.momentumId = requestAnimationFrame(step);
        };
        this.momentumId = requestAnimationFrame(step);
    }

    /**
     * Alignement magnétique doux sur la tuile entière la plus proche sans aucun rebond
     */
    smoothSnapToCenter() {
        if (this.snapAnimationId) cancelAnimationFrame(this.snapAnimationId);

        const targetX = Math.round(this.cameraX);
        const targetY = Math.round(this.cameraY);
        const dist = Math.hypot(targetX - this.cameraX, targetY - this.cameraY);

        if (dist < 0.005) {
            this.cameraX = targetX;
            this.cameraY = targetY;
            this.applyTransform();
            this.updateCoordsDisplay();
            this.scheduleSectorFetch(targetX, targetY);
            return;
        }

        const startX = this.cameraX;
        const startY = this.cameraY;
        const startTime = performance.now();
        const duration = Math.min(220, Math.max(120, dist * 180));

        const snapStep = (now) => {
            if (this.isDragging) return;
            const elapsed = now - startTime;
            const progress = Math.min(1, elapsed / duration);
            // Courbe d'atténuation cubique (ease out)
            const ease = 1 - Math.pow(1 - progress, 3);

            this.cameraX = startX + (targetX - startX) * ease;
            this.cameraY = startY + (targetY - startY) * ease;

            const shiftX = Math.round(this.cameraX - this.gridCenterX);
            const shiftY = Math.round(this.cameraY - this.gridCenterY);
            if (shiftX !== 0 || shiftY !== 0) {
                this.gridCenterX += shiftX;
                this.gridCenterY += shiftY;
                this.renderGrid();
            }

            this.applyTransform();
            this.updateCoordsDisplay();

            if (progress < 1) {
                this.snapAnimationId = requestAnimationFrame(snapStep);
            } else {
                this.cameraX = targetX;
                this.cameraY = targetY;
                this.applyTransform();
                this.updateCoordsDisplay();
                this.scheduleSectorFetch(targetX, targetY);
            }
        };
        this.snapAnimationId = requestAnimationFrame(snapStep);
    }

    /**
     * Déplacement relatif d'un pas (ex: touches clavier ou boutons de direction)
     */
    smoothMoveBy(deltaX, deltaY) {
        this.moveTo(Math.round(this.cameraX) + deltaX, Math.round(this.cameraY) + deltaY, 180);
    }

    /**
     * Déplacement panoramique fluide vers des coordonnées cibles (moveTo)
     */
    moveTo(targetX, targetY, customDuration = null) {
        if (this.momentumId) cancelAnimationFrame(this.momentumId);
        if (this.snapAnimationId) cancelAnimationFrame(this.snapAnimationId);

        targetX = parseInt(targetX, 10);
        targetY = parseInt(targetY, 10);
        if (isNaN(targetX) || isNaN(targetY)) return;

        const startX = this.cameraX;
        const startY = this.cameraY;
        const dist = Math.hypot(targetX - startX, targetY - startY);

        // Si la distance est immense (> 45 tuiles), téléportation directe plus véloce
        if (dist > 45) {
            this.cameraX = targetX;
            this.cameraY = targetY;
            this.gridCenterX = targetX;
            this.gridCenterY = targetY;
            this.renderGrid();
            this.applyTransform();
            this.updateCoordsDisplay();
            this.loadSector(targetX, targetY);
            return;
        }

        const startTime = performance.now();
        const duration = customDuration !== null ? customDuration : Math.min(420, Math.max(180, dist * 28));

        const moveStep = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(1, elapsed / duration);
            const ease = 1 - Math.pow(1 - progress, 3); // Cubic ease out

            this.cameraX = startX + (targetX - startX) * ease;
            this.cameraY = startY + (targetY - startY) * ease;

            const shiftX = Math.round(this.cameraX - this.gridCenterX);
            const shiftY = Math.round(this.cameraY - this.gridCenterY);
            if (shiftX !== 0 || shiftY !== 0) {
                this.gridCenterX += shiftX;
                this.gridCenterY += shiftY;
                this.renderGrid();
            }

            this.applyTransform();
            this.updateCoordsDisplay();

            if (progress < 1) {
                this.snapAnimationId = requestAnimationFrame(moveStep);
            } else {
                this.cameraX = targetX;
                this.cameraY = targetY;
                this.applyTransform();
                this.updateCoordsDisplay();
                this.loadSector(targetX, targetY);
            }
        };
        this.snapAnimationId = requestAnimationFrame(moveStep);
    }

    /**
     * Molette de la souris : glissement fluide ou zoom si Ctrl est pressé
     */
    onWheel(e) {
        e.preventDefault();
        if (this.momentumId) cancelAnimationFrame(this.momentumId);
        if (this.snapAnimationId) cancelAnimationFrame(this.snapAnimationId);

        if (e.ctrlKey) {
            // Zoom in / out
            if (e.deltaY < 0 && this.tileSize < 112) {
                this.tileSize += 8;
                this.stepSize = this.tileSize;
                this.renderGrid();
                this.applyTransform();
            } else if (e.deltaY > 0 && this.tileSize > 52) {
                this.tileSize -= 8;
                this.stepSize = this.tileSize;
                this.renderGrid();
                this.applyTransform();
            }
        } else {
            // Déplacement fluide à la molette
            const deltaX = -e.deltaX * 0.7;
            const deltaY = -e.deltaY * 0.7;
            this.panByDelta(deltaX, deltaY);

            clearTimeout(this.wheelSnapTimer);
            this.wheelSnapTimer = setTimeout(() => {
                this.smoothSnapToCenter();
            }, 120);
        }
    }

    updateCoordsDisplay() {
        const curX = Math.round(this.cameraX);
        const curY = Math.round(this.cameraY);

        if (this.coordsDisplay) {
            this.coordsDisplay.innerText = `[${curX} : ${curY}]`;
        }
        const inputX = document.getElementById('inputCoordX');
        const inputY = document.getElementById('inputCoordY');
        if (inputX && inputY && document.activeElement !== inputX && document.activeElement !== inputY) {
            inputX.value = curX;
            inputY.value = curY;
        }

        // Mise à jour visuelle du quadrant actif sur la Mini-Map
        this.updateMinimapActiveQuadrant(curX, curY);
    }

    updateMinimapActiveQuadrant(x, y) {
        const btnNO = document.getElementById('btnQuadrantNO');
        const btnNE = document.getElementById('btnQuadrantNE');
        const btnSO = document.getElementById('btnQuadrantSO');
        const btnSE = document.getElementById('btnQuadrantSE');
        const btnKyoto = document.getElementById('btnQuadrantKyoto');

        [btnNO, btnNE, btnSO, btnSE, btnKyoto].forEach(b => { if (b) b.classList.remove('active'); });

        if (Math.abs(x) <= 3 && Math.abs(y) <= 3) {
            if (btnKyoto) btnKyoto.classList.add('active');
        } else if (x < 0 && y >= 0) {
            if (btnNO) btnNO.classList.add('active');
        } else if (x >= 0 && y >= 0) {
            if (btnNE) btnNE.classList.add('active');
        } else if (x < 0 && y < 0) {
            if (btnSO) btnSO.classList.add('active');
        } else {
            if (btnSE) btnSE.classList.add('active');
        }
    }

    updateQuadrantDisplay() {
        const quadEl = document.getElementById('hudQuadrantDisplay');
        if (quadEl && this.currentQuadrant) {
            quadEl.innerHTML = `${this.currentQuadrant.symbol} <strong>${this.currentQuadrant.name}</strong>`;
            quadEl.title = `${this.currentQuadrant.desc} (${this.currentQuadrant.coords})`;
        }
    }

    /**
     * Planification asynchrone non-bloquante du chargement réseau d'un secteur
     */
    scheduleSectorFetch(cx, cy) {
        clearTimeout(this.fetchDebounceTimer);
        this.fetchDebounceTimer = setTimeout(() => {
            this.loadSector(cx, cy);
        }, 110);
    }

    async loadSector(cx, cy) {
        if (this.isLoading) return;
        this.isLoading = true;

        try {
            const res = await fetch(`/api/galaxy.php?x=${cx}&y=${cy}&radius=${this.radius}`);
            const json = await res.json();
            if (json.success && json.data) {
                Object.assign(this.planetsCache, json.data.planets || {});
                if (json.data.terrains) {
                    Object.assign(this.terrainsCache, json.data.terrains);
                }
                if (json.data.quadrant) {
                    this.currentQuadrant = json.data.quadrant;
                    this.updateQuadrantDisplay();
                }
                // Mise à jour discrète des tuiles sans saut visuel
                this.renderGrid();
                this.applyTransform();
            }
        } catch (e) {
            console.error("Erreur de chargement du secteur féodal:", e);
        } finally {
            this.isLoading = false;
        }
    }

    renderGrid() {
        const sideCount = (this.radius * 2) + 1;
        this.canvas.style.gridTemplateColumns = `repeat(${sideCount}, ${this.tileSize}px)`;
        this.canvas.style.gridTemplateRows = `repeat(${sideCount}, ${this.tileSize}px)`;
        this.canvas.style.gap = `${this.gap}px`;

        let html = '';
        const maxY = this.gridCenterY + this.radius;
        const minY = this.gridCenterY - this.radius;
        const minX = this.gridCenterX - this.radius;
        const maxX = this.gridCenterX + this.radius;

        for (let y = maxY; y >= minY; y--) {
            for (let x = minX; x <= maxX; x++) {
                const key = `${x}:${y}`;
                const planet = this.planetsCache[key] || null;
                const terrain = this.terrainsCache[key] || {
                    type: 'plains',
                    name: 'Plaines Fertiles',
                    img: '/public/assets/map/tile_plains.jpg?v=3'
                };
                const isCurrent = (x === this.playerX && y === this.playerY);
                const isCenter = (x === this.gridCenterX && y === this.gridCenterY);

                const isDojoActive = (planet && planet.is_imperial_dojo && planet.dojo_unlocked);
                const isDojoSealed = ((planet && planet.is_sealed_dojo) || (x === 0 && y === 0 && !isDojoActive));

                let tileClass = 'galaxy-tile';
                if (isCurrent) tileClass += ' current-planet';
                if (isCenter) tileClass += ' tile-center';
                if (isDojoActive) tileClass += ' imperial-dojo-tile';
                else if (isDojoSealed) tileClass += ' sacred-ground-tile';
                else if (planet && planet.is_authentic_castle) tileClass += ' authentic-castle-tile';
                else if (planet && planet.is_oasis) tileClass += (planet.is_occupied ? ' oasis-tile oasis-occupied' : ' oasis-tile oasis-wild');
                else if (planet && planet.user_id) tileClass += ' village-tile';
                else tileClass += ` terrain-tile-${terrain.type}`;

                let bgImg = terrain.img;
                if (planet && planet.terrain_img) {
                    bgImg = planet.terrain_img;
                }

                let contentHtml = '';
                if (planet) {
                    if (isDojoActive) {
                        const dojoLvl = parseInt(planet.dojo_level, 10) || 0;
                        const isFrozen = (planet.dojo_status === 'frozen');
                        const isComplete = (planet.dojo_status === 'completed' || dojoLvl >= 10);
                        let badgeBg = 'background:rgba(217,119,6,0.95);';
                        let badgeTxt = `NIV. ${dojoLvl}`;
                        if (isFrozen) {
                            badgeBg = 'background:rgba(220,38,38,0.95);';
                            badgeTxt = 'GELÉ';
                        } else if (isComplete) {
                            badgeBg = 'background:rgba(147,51,234,0.95);';
                            badgeTxt = 'SHŌGUN';
                        } else if (dojoLvl === 0) {
                            badgeBg = 'background:rgba(180,83,9,0.95);';
                            badgeTxt = 'TERRE 0:0';
                        }

                        contentHtml = `
                            <span class="tile-authentic-badge" style="${badgeBg} border-color:#fde047; color:#fff; font-weight:800; font-size:0.65rem; letter-spacing:0.5px;">⛩️ ${badgeTxt}</span>
                            <span class="tile-owner castle-label" style="color:#fef08a; font-weight:bold; text-shadow:0 1px 3px rgba(0,0,0,0.9);">${escapeHtml(planet.dojo_alliance_tag ? '[' + planet.dojo_alliance_tag + ']' : 'DŌJŌ IMPÉRIAL')}</span>
                        `;
                    } else if (isDojoSealed) {
                        contentHtml = `
                            <span class="tile-authentic-badge" style="background:rgba(51,65,85,0.92); border-color:#94a3b8; color:#f1f5f9; font-weight:700; font-size:0.63rem; letter-spacing:0.5px;">⛩️ SCELLÉ</span>
                            <span class="tile-owner castle-label" style="color:#e2e8f0; font-weight:600; text-shadow:0 1px 3px rgba(0,0,0,0.9);">TERRE SACRÉE</span>
                        `;
                    } else if (planet.is_authentic_castle) {
                        contentHtml = `
                            <span class="tile-authentic-badge">TRÉSOR</span>
                            <span class="tile-owner castle-label">${escapeHtml(planet.castle_name || planet.planet_name)}</span>
                        `;
                    } else if (planet.is_oasis) {
                        let resBadges = '';
                        if (planet.bonus_rice > 0) {
                            resBadges += `<span class="tile-res-badge res-rice" title="Riz +${planet.bonus_rice}%"><i class="fa-solid fa-wheat-awn me-1"></i>+${planet.bonus_rice}%</span>`;
                        }
                        if (planet.bonus_wood > 0) {
                            resBadges += `<span class="tile-res-badge res-wood" title="Bois +${planet.bonus_wood}%"><i class="fa-solid fa-tree me-1"></i>+${planet.bonus_wood}%</span>`;
                        }
                        if (planet.bonus_stone > 0) {
                            resBadges += `<span class="tile-res-badge res-stone" title="Pierre +${planet.bonus_stone}%"><i class="fa-solid fa-mountain me-1"></i>+${planet.bonus_stone}%</span>`;
                        }

                        const ownerHtml = (planet.is_occupied && planet.owner_planet_name)
                            ? `<span class="tile-owner village-label">${escapeHtml(planet.owner_planet_name)}</span>`
                            : '';

                        contentHtml = `
                            <div class="tile-oasis-res-group">
                                ${resBadges}
                            </div>
                            ${ownerHtml}
                        `;
                    } else if (planet.user_id) {
                        const ownerName = planet.planet_name || planet.username || 'Fief';
                        const protectedBadge = (planet.is_protected == 1)
                            ? `<span class="tile-authentic-badge" style="background:rgba(22,163,74,0.9); color:#fff; border-color:#86efac;" title="Fief sous immunité féodale débutant"><i class="fa-solid fa-shield-halved me-1"></i>PROTÉGÉ</span>` 
                            : '';
                        contentHtml = `
                            ${protectedBadge}
                            <span class="tile-owner village-label">${escapeHtml(ownerName)}</span>
                        `;
                    }
                }

                const planetDataAttr = planet 
                    ? JSON.stringify(planet).replace(/"/g, '&quot;') 
                    : JSON.stringify({ 
                        coord_x: x, 
                        coord_y: y, 
                        empty: true, 
                        terrain_name: terrain.name, 
                        terrain_desc: terrain.desc, 
                        terrain_img: terrain.img, 
                        terrain_type: terrain.type,
                        rural_plots: terrain.rural_plots || null,
                        fish_stock: (terrain.fish_stock !== undefined ? terrain.fish_stock : null),
                        fish_initial: (terrain.fish_initial !== undefined ? terrain.fish_initial : null),
                        lake_depleted: !!terrain.lake_depleted,
                        sulfur_stock: (terrain.sulfur_stock !== undefined ? terrain.sulfur_stock : null),
                        sulfur_initial: (terrain.sulfur_initial !== undefined ? terrain.sulfur_initial : null),
                        nitrate_stock: (terrain.nitrate_stock !== undefined ? terrain.nitrate_stock : null),
                        nitrate_initial: (terrain.nitrate_initial !== undefined ? terrain.nitrate_initial : null),
                        iron_sand_stock: (terrain.iron_sand_stock !== undefined ? terrain.iron_sand_stock : null),
                        iron_sand_initial: (terrain.iron_sand_initial !== undefined ? terrain.iron_sand_initial : null),
                        mountain_depleted: !!terrain.mountain_depleted
                    }).replace(/"/g, '&quot;');

                let tileTooltip = terrain.name;
                if (terrain.type === 'lake') {
                    if (terrain.lake_depleted) {
                        tileTooltip += ' [Réserve Épuisée]';
                    } else if (terrain.fish_stock !== null && terrain.fish_stock !== undefined) {
                        tileTooltip += ` [${Number(terrain.fish_stock).toLocaleString()} poissons]`;
                    }
                } else if (terrain.type === 'mountain') {
                    if (terrain.mountain_depleted) {
                        tileTooltip += ' [Filon Épuisé]';
                    } else if (terrain.sulfur_stock !== null && terrain.sulfur_stock !== undefined) {
                        const totMin = (parseInt(terrain.sulfur_stock, 10) || 0) + (parseInt(terrain.nitrate_stock, 10) || 0) + (parseInt(terrain.iron_sand_stock, 10) || 0);
                        tileTooltip += ` [${Number(totMin).toLocaleString()} minerais]`;
                    }
                }
                if (planet) {
                    if (planet.is_authentic_castle) {
                        tileTooltip = planet.castle_name || planet.planet_name;
                    } else if (planet.is_oasis) {
                        const isLakeO = (planet.oasis_type && planet.oasis_type.indexOf('lake') !== -1);
                        const isMountO = (planet.oasis_type && (planet.oasis_type.indexOf('mountain') !== -1 || planet.oasis_type.indexOf('rock') !== -1));
                        let infoSuffix = '';
                        if (isLakeO) {
                            infoSuffix = planet.lake_depleted ? ' [Épuisé]' : (planet.fish_stock !== undefined ? ' [' + Number(planet.fish_stock).toLocaleString() + ' poissons]' : '');
                        } else if (isMountO) {
                            const totOasisMin = (parseInt(planet.sulfur_stock, 10) || 0) + (parseInt(planet.nitrate_stock, 10) || 0) + (parseInt(planet.iron_sand_stock, 10) || 0);
                            infoSuffix = planet.mountain_depleted ? ' [Épuisé]' : ' [' + Number(totOasisMin).toLocaleString() + ' minerais]';
                        }
                        tileTooltip = `${planet.bonus_label || 'Oasis'} ${planet.is_occupied ? '(Occupée par ' + (planet.owner_planet_name || 'Fief') + ')' : '(Sauvage)'}${infoSuffix}`;
                    } else {
                        tileTooltip = (planet.planet_name || 'Fief') + (planet.is_protected == 1 ? ' [Immunité active]' : '');
                    }
                }

                html += `
                    <div class="${tileClass}" 
                         data-x="${x}" data-y="${y}" 
                         data-planet="${planetDataAttr}"
                         title="${escapeHtml(tileTooltip)} [${x} : ${y}]"
                         style="width:${this.tileSize}px; height:${this.tileSize}px; background-image:url('${bgImg}'); background-size:cover; background-position:center;">
                        <div class="tile-inner-overlay">
                            ${contentHtml}
                            <span class="tile-coords">${x}:${y}</span>
                        </div>
                    </div>
                `;
            }
        }

        this.canvas.innerHTML = html;

        // Attacher les événements de clic sur les tuiles (seulement si ce n'était pas un glissement)
        this.canvas.querySelectorAll('.galaxy-tile').forEach(tile => {
            tile.onclick = (e) => {
                if (this.totalDistanceMoved > 8) return; // C'était un glissement, pas un clic
                const x = parseInt(tile.dataset.x, 10);
                const y = parseInt(tile.dataset.y, 10);
                const rawData = tile.dataset.planet;
                const planetData = rawData ? JSON.parse(rawData) : { coord_x: x, coord_y: y, empty: true };
                
                if (typeof window.selectPlanetTile === 'function') {
                    window.selectPlanetTile(planetData);
                }
            };
        });
    }
}

function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
