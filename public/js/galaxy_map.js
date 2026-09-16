/**
 * Moteur de Carte des Provinces et Fiefs du Japon Féodal (OpenShogun)
 * Déplacement fluide (Drag & Drop), tuiles de terrain illustrées (plaines, forêts, montagnes, lacs, collines),
 * donjons authentiques et navigation par quadrants (Nord-Ouest, Nord-Est, Sud-Ouest, Sud-Est).
 */

class GalaxyMapController {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.centerX = options.initialX || 0;
        this.centerY = options.initialY || 0;
        this.playerX = options.playerX || 0;
        this.playerY = options.playerY || 0;
        this.currentUserId = options.userId || 0;

        this.tileSize = 76; // pixels par case
        this.gap = 6;
        this.stepSize = this.tileSize + this.gap;

        // Calcul dynamique du rayon pour occuper toute la largeur d'écran disponible
        if (options.radius && options.radius > 0) {
            this.radius = options.radius;
        } else {
            const availW = Math.max(window.innerWidth, this.container.clientWidth || 1200);
            const neededCols = Math.ceil(availW / this.stepSize) + 2;
            this.radius = Math.min(13, Math.max(5, Math.ceil((neededCols - 1) / 2)));
        }

        // État du Drag
        this.isDragging = false;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.dragOffsetX = 0;
        this.dragOffsetY = 0;
        this.totalDistanceMoved = 0;

        // Cache des secteurs planétaires et paysages naturels
        this.planetsCache = {};
        this.terrainsCache = {};
        this.currentQuadrant = null;
        this.isLoading = false;

        this.initDOM();
        this.bindEvents();
        this.loadSector(this.centerX, this.centerY);
    }

    initDOM() {
        this.container.innerHTML = `
            <div class="galaxy-hud-overlay">
                <div class="hud-coords-badge">
                    <span id="hudQuadrantDisplay">🧭 Provinces</span>
                    <strong id="hudCoordsDisplay">[${this.centerX} : ${this.centerY}]</strong>
                </div>

                <div class="hud-quadrants-nav">
                    <button class="hud-btn" id="btnQuadrantNO" title="Sauter vers les Terres du Nord-Ouest [- / +]">↖️ N-O</button>
                    <button class="hud-btn" id="btnQuadrantNE" title="Sauter vers les Terres du Nord-Est [+ / +]">↗️ N-E</button>
                    <button class="hud-btn" id="btnQuadrantSO" title="Sauter vers les Terres du Sud-Ouest [- / -]">↙️ S-O</button>
                    <button class="hud-btn" id="btnQuadrantSE" title="Sauter vers les Terres du Sud-Est [+ / -]">↘️ S-E</button>
                    <button class="hud-btn" id="btnQuadrantKyoto" title="Sauter vers la Capitale Impériale [0 : 0]">⛩️ Centre</button>
                </div>

                <div class="hud-controls-group">
                    <button class="hud-btn" id="btnRecenterColony" title="Centrer sur mon fief">🏯 Mon Fief</button>
                    <button class="hud-btn" id="btnZoomIn" title="Zoom avant">+</button>
                    <button class="hud-btn" id="btnZoomOut" title="Zoom arrière">&minus;</button>
                </div>
            </div>

            <div class="galaxy-viewport" id="galaxyViewport">
                <div class="galaxy-canvas" id="galaxyCanvas">
                    <!-- Les tuiles seront générées dynamiquement -->
                </div>
                <div class="galaxy-drag-hint">
                    <span>🖐️ Glissez (Drag & Drop) pour explorer le Japon féodal</span>
                </div>
            </div>
        `;

        this.viewport = document.getElementById('galaxyViewport');
        this.canvas = document.getElementById('galaxyCanvas');
        this.coordsDisplay = document.getElementById('hudCoordsDisplay');
    }

    bindEvents() {
        // Drag-and-Drop avec Pointer Events (support souris + touch mobile)
        this.viewport.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        window.addEventListener('pointermove', (e) => this.onPointerMove(e));
        window.addEventListener('pointerup', (e) => this.onPointerUp(e));
        window.addEventListener('pointercancel', (e) => this.onPointerUp(e));

        // Empêcher le glisser d'image natif
        this.viewport.addEventListener('dragstart', (e) => e.preventDefault());

        // Boutons de navigation des 4 Quadrants & Centre
        document.getElementById('btnQuadrantNO').onclick = () => this.moveTo(-16, 16);
        document.getElementById('btnQuadrantNE').onclick = () => this.moveTo(16, 16);
        document.getElementById('btnQuadrantSO').onclick = () => this.moveTo(-16, -16);
        document.getElementById('btnQuadrantSE').onclick = () => this.moveTo(16, -16);
        document.getElementById('btnQuadrantKyoto').onclick = () => this.moveTo(0, 0);

        // Boutons de contrôle HUD
        document.getElementById('btnRecenterColony').onclick = () => {
            this.moveTo(this.playerX, this.playerY);
        };

        document.getElementById('btnZoomIn').onclick = () => {
            if (this.tileSize < 110) {
                this.tileSize += 12;
                this.stepSize = this.tileSize + this.gap;
                this.renderGrid();
            }
        };

        document.getElementById('btnZoomOut').onclick = () => {
            if (this.tileSize > 54) {
                this.tileSize -= 12;
                this.stepSize = this.tileSize + this.gap;
                this.renderGrid();
            }
        };

        // Navigation clavier (Flèches directionnelles)
        window.addEventListener('keydown', (e) => {
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
                e.preventDefault();
                if (e.key === 'ArrowUp') this.moveTo(this.centerX, this.centerY + 1);
                if (e.key === 'ArrowDown') this.moveTo(this.centerX, this.centerY - 1);
                if (e.key === 'ArrowLeft') this.moveTo(this.centerX - 1, this.centerY);
                if (e.key === 'ArrowRight') this.moveTo(this.centerX + 1, this.centerY);
            }
        });

        // Redimensionnement automatique pour couvrir toute la largeur de l'écran
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const availW = Math.max(window.innerWidth, this.container.clientWidth || 1200);
                const neededCols = Math.ceil(availW / this.stepSize) + 2;
                const newRadius = Math.min(13, Math.max(5, Math.ceil((neededCols - 1) / 2)));
                if (newRadius !== this.radius) {
                    this.radius = newRadius;
                    this.loadSector(this.centerX, this.centerY);
                }
            }, 300);
        });
    }

    onPointerDown(e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;

        this.isDragging = true;
        this.dragStartX = e.clientX;
        this.dragStartY = e.clientY;
        this.dragOffsetX = 0;
        this.dragOffsetY = 0;
        this.totalDistanceMoved = 0;

        this.viewport.classList.add('grabbing');
    }

    onPointerMove(e) {
        if (!this.isDragging) return;

        const dx = e.clientX - this.dragStartX;
        const dy = e.clientY - this.dragStartY;
        this.dragOffsetX = dx;
        this.dragOffsetY = dy;
        this.totalDistanceMoved += Math.hypot(e.movementX, e.movementY);

        this.canvas.style.transform = `translate3d(${dx}px, ${dy}px, 0)`;

        const coordDeltaX = -Math.trunc(dx / this.stepSize);
        const coordDeltaY = Math.trunc(dy / this.stepSize);

        if (coordDeltaX !== 0 || coordDeltaY !== 0) {
            this.centerX += coordDeltaX;
            this.centerY += coordDeltaY;
            this.dragStartX += (-coordDeltaX * this.stepSize);
            this.dragStartY += (coordDeltaY * this.stepSize);
            this.dragOffsetX = e.clientX - this.dragStartX;
            this.dragOffsetY = e.clientY - this.dragStartY;
            this.canvas.style.transform = `translate3d(${this.dragOffsetX}px, ${this.dragOffsetY}px, 0)`;

            this.updateCoordsDisplay();
            this.loadSector(this.centerX, this.centerY);
        }
    }

    onPointerUp(e) {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.viewport.classList.remove('grabbing');

        this.canvas.style.transition = 'transform 0.2s cubic-bezier(0.2, 0.9, 0.4, 1)';
        this.canvas.style.transform = 'translate3d(0, 0, 0)';

        setTimeout(() => {
            this.canvas.style.transition = 'none';
        }, 220);
    }

    moveTo(x, y) {
        this.centerX = x;
        this.centerY = y;
        this.updateCoordsDisplay();
        this.loadSector(this.centerX, this.centerY);
    }

    updateCoordsDisplay() {
        if (this.coordsDisplay) {
            this.coordsDisplay.innerText = `[${this.centerX} : ${this.centerY}]`;
        }
        const inputX = document.getElementById('inputCoordX');
        const inputY = document.getElementById('inputCoordY');
        if (inputX && inputY) {
            inputX.value = this.centerX;
            inputY.value = this.centerY;
        }
    }

    updateQuadrantDisplay() {
        const quadEl = document.getElementById('hudQuadrantDisplay');
        if (quadEl && this.currentQuadrant) {
            quadEl.innerHTML = `${this.currentQuadrant.symbol} <strong>${this.currentQuadrant.name}</strong>`;
            quadEl.title = `${this.currentQuadrant.desc} (${this.currentQuadrant.coords})`;
        }
    }

    async loadSector(cx, cy) {
        if (this.isLoading) return;
        this.isLoading = true;

        try {
            const res = await fetch(`/api/galaxy.php?x=${cx}&y=${cy}&radius=${this.radius}`);
            const json = await res.json();
            if (json.success && json.data) {
                // Fusionner dans les caches
                Object.assign(this.planetsCache, json.data.planets || {});
                if (json.data.terrains) {
                    Object.assign(this.terrainsCache, json.data.terrains);
                }
                if (json.data.quadrant) {
                    this.currentQuadrant = json.data.quadrant;
                    this.updateQuadrantDisplay();
                }
                this.renderGrid();
            }
        } catch (e) {
            console.error("Erreur de chargement du secteur galactique:", e);
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
        const maxY = this.centerY + this.radius;
        const minY = this.centerY - this.radius;
        const minX = this.centerX - this.radius;
        const maxX = this.centerX + this.radius;

        for (let y = maxY; y >= minY; y--) {
            for (let x = minX; x <= maxX; x++) {
                const key = `${x}:${y}`;
                const planet = this.planetsCache[key] || null;
                const terrain = this.terrainsCache[key] || {
                    type: 'plains',
                    name: 'Plaines Fertiles',
                    img: '/public/assets/map/tile_plains.jpg'
                };
                const isCurrent = (x === this.playerX && y === this.playerY);
                const isCenter = (x === this.centerX && y === this.centerY);

                let tileClass = 'galaxy-tile';
                if (isCurrent) tileClass += ' current-planet';
                if (isCenter) tileClass += ' tile-center';
                if (planet && planet.is_authentic_castle) tileClass += ' authentic-castle-tile';
                else if (planet && planet.user_id) tileClass += ' village-tile';
                else tileClass += ` terrain-tile-${terrain.type}`;

                let bgImg = terrain.img;
                if (planet && planet.terrain_img) {
                    bgImg = planet.terrain_img;
                }

                let contentHtml = '';
                if (planet) {
                    if (planet.is_authentic_castle) {
                        contentHtml = `
                            <span class="tile-authentic-badge">TRÉSOR</span>
                            <span class="tile-planet-icon" style="font-size:1.45rem; filter:drop-shadow(0 0 8px rgba(245,158,11,0.85));">🏯</span>
                            <span class="tile-owner" style="color:#fbbf24; font-weight:800; font-size:0.62rem; text-shadow:0 1px 3px rgba(0,0,0,0.8);">${escapeHtml(planet.castle_name || planet.planet_name)}</span>
                        `;
                    } else {
                        const icon = planet.user_id ? '🏯' : '🌾';
                        const ownerName = planet.username || 'Terres Libres';
                        const ownerColor = planet.user_id ? '#38bdf8' : '#e2e8f0';
                        contentHtml = `
                            <span class="tile-planet-icon" style="filter:drop-shadow(0 0 6px rgba(0,0,0,0.85));">${icon}</span>
                            <span class="tile-owner" style="color:${ownerColor}; background:rgba(0,0,0,0.7); padding:1px 4px; border-radius:3px; font-weight:700; font-size:0.6rem;">${escapeHtml(ownerName)}</span>
                        `;
                    }
                } else {
                    // Marqueur discret de terrain
                    contentHtml = `<span class="tile-nature-marker" title="${escapeHtml(terrain.name)}"></span>`;
                }

                const planetDataAttr = planet 
                    ? JSON.stringify(planet).replace(/"/g, '&quot;') 
                    : JSON.stringify({ coord_x: x, coord_y: y, empty: true, terrain_name: terrain.name, terrain_desc: terrain.desc }).replace(/"/g, '&quot;');

                const tileTooltip = planet 
                    ? (planet.castle_name || planet.planet_name) 
                    : terrain.name;

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

        // Attacher les événements de clic sur les tuiles (seulement si ce n'était pas un drag)
        this.canvas.querySelectorAll('.galaxy-tile').forEach(tile => {
            tile.onclick = (e) => {
                if (this.totalDistanceMoved > 8) return; // C'était un drag, pas un clic
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
