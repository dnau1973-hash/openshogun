<?php
/**
 * Outil Administratif de Calibration Visuelle des Emplacements par Drag & Drop (OpenShogun)
 * Permet de déplacer en direct n'importe quel slot ou bâtiment à la souris et d'enregistrer ses coordonnées.
 */
if (!isset($auth) || !$auth->isAdmin()) {
    return;
}

require_once __DIR__ . '/../../core/SlotPositionEngine.php';
$calibView = ($page === 'city') ? 'city' : 'resources';
$defaultPositions = SlotPositionEngine::getDefaults($calibView);
$maxDelta = SlotPositionEngine::MAX_DELTA_PERCENT;
?>

<!-- Bouton d'activation flottant du calibrateur -->
<div id="calibratorFloatingLauncher" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button type="button" onclick="toggleSlotCalibrationMode()" class="btn btn-warning" style="box-shadow: 0 4px 20px rgba(0,0,0,0.6), 0 0 15px rgba(245, 158, 11, 0.4); font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.1rem; display: flex; align-items: center; gap: 0.5rem; border-radius: 30px; border: 2px solid #facc15;">
        <span>🎯</span>
        <span id="calibratorBtnText">Mode Calibration (Drag & Drop)</span>
    </button>
</div>

<!-- Barre d'outils HUD du Calibrateur (visible quand actif) -->
<div id="calibratorToolbar" style="display: none; position: fixed; top: 15px; left: 50%; transform: translateX(-50%); z-index: 10000; background: rgba(15, 18, 26, 0.96); border: 2px solid #facc15; border-radius: 12px; padding: 0.75rem 1.25rem; box-shadow: 0 10px 40px rgba(0,0,0,0.8), 0 0 25px rgba(245, 158, 11, 0.35); backdrop-filter: blur(10px); color: #fff; align-items: center; gap: 1.25rem; max-width: 95vw; flex-wrap: wrap;">
    
    <!-- Statut & Nom de la Vue -->
    <div style="display: flex; align-items: center; gap: 0.6rem;">
        <span style="font-size: 1.3rem;">🎯</span>
        <div>
            <div style="font-size: 0.8rem; font-weight: 800; color: #facc15; text-transform: uppercase; letter-spacing: 0.5px;">
                Calibration <?= ($calibView === 'city') ? 'Cité Castrale' : 'Terroirs Ruraux' ?>
            </div>
            <div style="font-size: 0.72rem; color: #94a3b8;">Glissez les bâtiments (sécurité limitée à ±<?= $maxDelta ?>%)</div>
        </div>
    </div>

    <!-- Info du slot sélectionné & micro-ajustement -->
    <div style="background: rgba(0,0,0,0.4); padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.8rem;">
        <div id="calibSelectedInfo" style="font-family: monospace; font-size: 0.82rem; color: #38bdf8;">
            Cliquez sur un slot pour l'ajuster
        </div>
        
        <!-- Touches fléchées pour ajustement au 0.1% -->
        <div style="display: flex; gap: 0.25rem;">
            <button type="button" onclick="nudgeSelectedSlot(-0.2, 0)" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Gauche (-0.2%)">◀</button>
            <button type="button" onclick="nudgeSelectedSlot(0, -0.2)" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Haut (-0.2%)">▲</button>
            <button type="button" onclick="nudgeSelectedSlot(0, 0.2)" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Bas (+0.2%)">▼</button>
            <button type="button" onclick="nudgeSelectedSlot(0.2, 0)" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Droite (+0.2%)">▶</button>
        </div>
    </div>

    <!-- Actions d'enregistrement et sortie -->
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <button type="button" id="btnSaveSlotPositions" onclick="saveCalibratedPositions()" class="btn btn-primary" style="background: linear-gradient(135deg, #15803d, #22c55e); border-color: #4ade80; font-weight: 800; font-size: 0.82rem; padding: 0.45rem 1rem;">
            💾 Enregistrer
        </button>
        <button type="button" onclick="resetCalibratedPositions()" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; color: #facc15;" title="Rétablir les positions par défaut">
            ↺ Rétablir
        </button>
        <button type="button" onclick="copyCalibratedCss()" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.45rem 0.75rem;" title="Copier le code CSS brut">
            📋 Copier CSS
        </button>
        <button type="button" onclick="toggleSlotCalibrationMode()" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.45rem 0.65rem; color: #f87171;" title="Quitter le mode calibration">
            ✕
        </button>
    </div>
</div>

<style>
/* Styles appliqués quand le mode calibration est activé */
body.calibrator-active .fields-viewport {
    outline: 3px dashed #facc15 !important;
    outline-offset: 4px !important;
    cursor: default !important;
}

body.calibrator-active .rts-hotspot {
    cursor: grab !important;
    outline: 2px dashed rgba(250, 204, 21, 0.7) !important;
    outline-offset: 2px !important;
    background: rgba(250, 204, 21, 0.12) !important;
    user-select: none !important;
    z-index: 50 !important;
}

body.calibrator-active .rts-hotspot:hover {
    outline: 2px solid #ef4444 !important;
    background: rgba(239, 68, 68, 0.22) !important;
}

body.calibrator-active .rts-hotspot.calib-dragging {
    cursor: grabbing !important;
    outline: 2.5px solid #22c55e !important;
    background: rgba(34, 197, 94, 0.3) !important;
    z-index: 100 !important;
}

body.calibrator-active .rts-hotspot.calib-selected {
    outline: 2.5px solid #38bdf8 !important;
    box-shadow: 0 0 15px rgba(56, 189, 248, 0.7) !important;
}

/* Alerte visuelle quand le déplacement atteint la limite maximale de sécurité (±10%) */
body.calibrator-active .rts-hotspot.calib-at-limit {
    outline: 2.5px solid #ef4444 !important;
    background: rgba(239, 68, 68, 0.35) !important;
    box-shadow: 0 0 18px rgba(239, 68, 68, 0.85) !important;
}

/* Zone de sécurité autorisée pour le slot sélectionné */
#calibratorSafetyZone {
    position: absolute;
    border: 2px dashed rgba(56, 189, 248, 0.85);
    background: rgba(56, 189, 248, 0.08);
    border-radius: 8px;
    pointer-events: none;
    z-index: 45;
    transition: all 0.12s ease-out;
    display: none;
    box-shadow: inset 0 0 12px rgba(56, 189, 248, 0.2);
}

#calibratorSafetyZone .safety-zone-label {
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.92);
    color: #38bdf8;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid #38bdf8;
    white-space: nowrap;
    box-shadow: 0 2px 5px rgba(0,0,0,0.6);
}

/* Badge indicatif flottant sur chaque hotspot en mode édition */
.calib-coord-pill {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: #facc15;
    font-size: 0.68rem;
    font-weight: 800;
    font-family: monospace;
    padding: 1px 6px;
    border-radius: 10px;
    border: 1px solid #facc15;
    white-space: nowrap;
    pointer-events: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.7);
    display: none;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}

body.calibrator-active .calib-coord-pill {
    display: block !important;
}
</style>

<script>
(function() {
    const VIEW_NAME = <?= json_encode($calibView) ?>;
    const DEFAULT_POSITIONS = <?= json_encode($defaultPositions) ?>;
    const MAX_DELTA_PCT = <?= (float)$maxDelta ?>;
    let isCalibratorActive = false;
    let selectedHotspot = null;
    let currentDragging = null;
    let dragStartX = 0;
    let dragStartY = 0;
    let initialLeftPct = 0;
    let initialTopPct = 0;

    // Récupérer le viewport
    function getViewport() {
        return document.querySelector('.fields-viewport.rts-surface');
    }

    // Récupérer la clé unique du hotspot (ex: "1", "18", "bunker-hq", "19", "gateway")
    function getHotspotKey(el) {
        if (!el) return null;
        if (el.dataset.slot) {
            return String(el.dataset.slot);
        }
        if (el.classList.contains('hotspot-bunker-hq')) {
            return 'bunker-hq';
        }
        if (el.classList.contains('hotspot-city-slot-gateway') || el.dataset.sector === 'gateway') {
            return 'gateway';
        }
        // Extraction depuis les classes
        const classes = el.className.split(' ');
        for (let c of classes) {
            if (c.startsWith('hotspot-slot-')) {
                return c.replace('hotspot-slot-', '');
            }
            if (c.startsWith('hotspot-city-slot-')) {
                return c.replace('hotspot-city-slot-', '');
            }
        }
        return null;
    }

    // Activer / Désactiver le mode calibration
    window.toggleSlotCalibrationMode = function() {
        isCalibratorActive = !isCalibratorActive;
        const body = document.body;
        const toolbar = document.getElementById('calibratorToolbar');
        const launcherText = document.getElementById('calibratorBtnText');

        if (isCalibratorActive) {
            body.classList.add('calibrator-active');
            toolbar.style.display = 'flex';
            launcherText.innerText = "Quitter Calibration";
            setupHotspotsForCalibration();
        } else {
            body.classList.remove('calibrator-active');
            toolbar.style.display = 'none';
            launcherText.innerText = "Mode Calibration (Drag & Drop)";
            removeSafetyZoneGuide();
            if (selectedHotspot) {
                selectedHotspot.classList.remove('calib-selected');
                selectedHotspot.classList.remove('calib-at-limit');
                selectedHotspot = null;
            }
        }
    };

    function setupHotspotsForCalibration() {
        const viewport = getViewport();
        if (!viewport) return;

        const hotspots = viewport.querySelectorAll('.rts-hotspot');
        hotspots.forEach(hs => {
            const key = getHotspotKey(hs);
            if (!key) return;

            // Ajouter le badge de coordonnées s'il n'existe pas déjà
            let pill = hs.querySelector('.calib-coord-pill');
            if (!pill) {
                pill = document.createElement('div');
                pill.className = 'calib-coord-pill';
                hs.appendChild(pill);
            }

            updateHotspotPill(hs);

            // Attacher les écouteurs de souris pour le drag & drop
            hs.onmousedown = onHotspotMouseDown;
            
            // Intercepter les clics normaux pour éviter de déclencher les modales ou la navigation
            hs.addEventListener('click', function(e) {
                if (isCalibratorActive) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectHotspot(hs);
                }
            }, true);
        });
    }

    // Calcul des bornes autorisées pour un slot donné (sécurité anti-dérive)
    function getSlotBounds(key) {
        const def = DEFAULT_POSITIONS[key];
        if (!def) {
            return { minLeft: 0, maxLeft: 95, minTop: 0, maxTop: 95, defLeft: 50, defTop: 50, width: 11.0, height: 18.6 };
        }
        return {
            defLeft: def.left,
            defTop: def.top,
            width: def.width,
            height: def.height,
            minLeft: Math.max(0, Math.round((def.left - MAX_DELTA_PCT) * 10) / 10),
            maxLeft: Math.min(95, Math.round((def.left + MAX_DELTA_PCT) * 10) / 10),
            minTop: Math.max(0, Math.round((def.top - MAX_DELTA_PCT) * 10) / 10),
            maxTop: Math.min(95, Math.round((def.top + MAX_DELTA_PCT) * 10) / 10)
        };
    }

    function renderSafetyZoneGuide(key, bounds) {
        const viewport = getViewport();
        if (!viewport) return;

        let zoneEl = document.getElementById('calibratorSafetyZone');
        if (!zoneEl) {
            zoneEl = document.createElement('div');
            zoneEl.id = 'calibratorSafetyZone';
            zoneEl.innerHTML = '<span class="safety-zone-label">Zone autorisée (±' + MAX_DELTA_PCT + '%)</span>';
            viewport.appendChild(zoneEl);
        }

        const zoneWidth = Math.round((bounds.maxLeft - bounds.minLeft + bounds.width) * 10) / 10;
        const zoneHeight = Math.round((bounds.maxTop - bounds.minTop + bounds.height) * 10) / 10;

        zoneEl.style.left = bounds.minLeft + '%';
        zoneEl.style.top = bounds.minTop + '%';
        zoneEl.style.width = zoneWidth + '%';
        zoneEl.style.height = zoneHeight + '%';
        zoneEl.style.display = 'block';
    }

    function removeSafetyZoneGuide() {
        const zoneEl = document.getElementById('calibratorSafetyZone');
        if (zoneEl) {
            zoneEl.style.display = 'none';
        }
    }

    function selectHotspot(hs) {
        if (selectedHotspot && selectedHotspot !== hs) {
            selectedHotspot.classList.remove('calib-selected');
            selectedHotspot.classList.remove('calib-at-limit');
        }
        selectedHotspot = hs;
        if (selectedHotspot) {
            selectedHotspot.classList.add('calib-selected');
            const key = getHotspotKey(hs);
            const pos = getElementPctPos(hs);
            const bounds = getSlotBounds(key);
            const deltaX = Math.round((pos.left - bounds.defLeft) * 10) / 10;
            const deltaY = Math.round((pos.top - bounds.defTop) * 10) / 10;
            const isAtLimit = (pos.left <= bounds.minLeft || pos.left >= bounds.maxLeft || pos.top <= bounds.minTop || pos.top >= bounds.maxTop);

            const infoEl = document.getElementById('calibSelectedInfo');
            if (infoEl) {
                const signX = deltaX >= 0 ? '+' : '';
                const signY = deltaY >= 0 ? '+' : '';
                const limitStatus = isAtLimit 
                    ? `<span style="color: #ef4444; font-weight: bold; margin-left: 6px;">⚠️ Limite max ±${MAX_DELTA_PCT}% atteinte</span>` 
                    : `<span style="color: #4ade80; margin-left: 6px;">(Sécurité : max ±${MAX_DELTA_PCT}%)</span>`;
                
                infoEl.innerHTML = `Slot <strong>#${key}</strong> [ X: <strong>${pos.left.toFixed(1)}%</strong> (${signX}${deltaX.toFixed(1)}%) | Y: <strong>${pos.top.toFixed(1)}%</strong> (${signY}${deltaY.toFixed(1)}%) ] ${limitStatus}`;
            }

            renderSafetyZoneGuide(key, bounds);
        } else {
            removeSafetyZoneGuide();
        }
    }

    function getElementPctPos(el) {
        if (el.style.left && el.style.left.includes('%') && el.style.top && el.style.top.includes('%')) {
            const l = parseFloat(el.style.left);
            const t = parseFloat(el.style.top);
            if (!isNaN(l) && !isNaN(t)) {
                return {
                    left: Math.round(l * 10) / 10,
                    top: Math.round(t * 10) / 10
                };
            }
        }
        const viewport = getViewport();
        const vRect = viewport.getBoundingClientRect();
        const eRect = el.getBoundingClientRect();
        const borderLeft = parseFloat(window.getComputedStyle(viewport).borderLeftWidth) || 0;
        const borderTop = parseFloat(window.getComputedStyle(viewport).borderTopWidth) || 0;
        const clientW = viewport.clientWidth || vRect.width;
        const clientH = viewport.clientHeight || vRect.height;

        const leftPct = ((eRect.left - (vRect.left + borderLeft)) / clientW) * 100;
        const topPct = ((eRect.top - (vRect.top + borderTop)) / clientH) * 100;

        return {
            left: Math.round(leftPct * 10) / 10,
            top: Math.round(topPct * 10) / 10
        };
    }

    function updateHotspotPill(hs) {
        const pill = hs.querySelector('.calib-coord-pill');
        if (!pill) return;
        const key = getHotspotKey(hs);
        const pos = getElementPctPos(hs);
        const bounds = getSlotBounds(key);
        const isAtLimit = (pos.left <= bounds.minLeft || pos.left >= bounds.maxLeft || pos.top <= bounds.minTop || pos.top >= bounds.maxTop);

        if (isAtLimit) {
            pill.style.borderColor = '#ef4444';
            pill.style.color = '#fca5a5';
            pill.style.background = '#450a0a';
            pill.innerText = `⛔ #${key} (${pos.left.toFixed(1)}%, ${pos.top.toFixed(1)}%) MAX`;
        } else {
            pill.style.borderColor = '#facc15';
            pill.style.color = '#facc15';
            pill.style.background = '#0f172a';
            pill.innerText = `#${key} (${pos.left.toFixed(1)}%, ${pos.top.toFixed(1)}%)`;
        }
    }

    function onHotspotMouseDown(e) {
        if (!isCalibratorActive) return;
        if (e.button !== 0) return; // Clic gauche seulement

        e.preventDefault();
        e.stopPropagation();

        currentDragging = this;
        selectHotspot(currentDragging);
        currentDragging.classList.add('calib-dragging');

        const pos = getElementPctPos(currentDragging);
        dragStartX = e.clientX;
        dragStartY = e.clientY;

        initialLeftPct = pos.left;
        initialTopPct = pos.top;

        document.addEventListener('mousemove', onDocumentMouseMove);
        document.addEventListener('mouseup', onDocumentMouseUp);
    }

    function onDocumentMouseMove(e) {
        if (!currentDragging) return;

        const viewport = getViewport();
        const clientW = viewport.clientWidth || viewport.getBoundingClientRect().width;
        const clientH = viewport.clientHeight || viewport.getBoundingClientRect().height;

        const key = getHotspotKey(currentDragging);
        const bounds = getSlotBounds(key);

        const deltaXPct = ((e.clientX - dragStartX) / clientW) * 100;
        const deltaYPct = ((e.clientY - dragStartY) / clientH) * 100;

        let targetLeft = Math.round((initialLeftPct + deltaXPct) * 10) / 10;
        let targetTop = Math.round((initialTopPct + deltaYPct) * 10) / 10;

        // Sécurité anti-dérive : bridage strict dans la zone autorisée (±MAX_DELTA_PCT)
        let newLeft = Math.max(bounds.minLeft, Math.min(bounds.maxLeft, targetLeft));
        let newTop = Math.max(bounds.minTop, Math.min(bounds.maxTop, targetTop));

        const hitLimit = (targetLeft < bounds.minLeft || targetLeft > bounds.maxLeft || targetTop < bounds.minTop || targetTop > bounds.maxTop);
        if (hitLimit) {
            currentDragging.classList.add('calib-at-limit');
        } else {
            currentDragging.classList.remove('calib-at-limit');
        }

        currentDragging.style.setProperty('left', newLeft + '%', 'important');
        currentDragging.style.setProperty('top', newTop + '%', 'important');

        updateHotspotPill(currentDragging);
        selectHotspot(currentDragging);
    }

    function onDocumentMouseUp(e) {
        if (currentDragging) {
            currentDragging.classList.remove('calib-dragging');
            currentDragging.classList.remove('calib-at-limit');
            currentDragging = null;
        }
        document.removeEventListener('mousemove', onDocumentMouseMove);
        document.removeEventListener('mouseup', onDocumentMouseUp);
    }

    // Déplacement fin via boutons ou clavier
    window.nudgeSelectedSlot = function(deltaX, deltaY) {
        if (!selectedHotspot) {
            alert("Veuillez d'abord cliquer sur un slot à déplacer.");
            return;
        }

        const key = getHotspotKey(selectedHotspot);
        const bounds = getSlotBounds(key);
        const pos = getElementPctPos(selectedHotspot);

        let targetLeft = Math.round((pos.left + deltaX) * 10) / 10;
        let targetTop = Math.round((pos.top + deltaY) * 10) / 10;

        // Sécurité anti-dérive : bridage strict
        let newLeft = Math.max(bounds.minLeft, Math.min(bounds.maxLeft, targetLeft));
        let newTop = Math.max(bounds.minTop, Math.min(bounds.maxTop, targetTop));

        const hitLimit = (targetLeft < bounds.minLeft || targetLeft > bounds.maxLeft || targetTop < bounds.minTop || targetTop > bounds.maxTop);
        if (hitLimit) {
            selectedHotspot.classList.add('calib-at-limit');
            setTimeout(() => {
                if (selectedHotspot) selectedHotspot.classList.remove('calib-at-limit');
            }, 600);
        }

        selectedHotspot.style.setProperty('left', newLeft + '%', 'important');
        selectedHotspot.style.setProperty('top', newTop + '%', 'important');

        updateHotspotPill(selectedHotspot);
        selectHotspot(selectedHotspot);
    };

    // Contrôle au clavier (touches fléchées)
    window.addEventListener('keydown', function(e) {
        if (!isCalibratorActive || !selectedHotspot) return;
        const step = e.shiftKey ? 1.0 : 0.2;

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            nudgeSelectedSlot(-step, 0);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            nudgeSelectedSlot(step, 0);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            nudgeSelectedSlot(0, -step);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            nudgeSelectedSlot(0, step);
        } else if (e.key === 'Escape') {
            toggleSlotCalibrationMode();
        }
    });

    // Enregistrement sur le serveur
    window.saveCalibratedPositions = async function() {
        const viewport = getViewport();
        if (!viewport) return;

        const saveBtn = document.getElementById('btnSaveSlotPositions');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span>⏳</span> Enregistrement...';

        const positions = {};
        const hotspots = viewport.querySelectorAll('.rts-hotspot');

        hotspots.forEach(hs => {
            const key = getHotspotKey(hs);
            if (!key) return;

            const pos = getElementPctPos(hs);
            // Récupérer largeur et hauteur actuelles
            const vRect = viewport.getBoundingClientRect();
            const hRect = hs.getBoundingClientRect();
            const clientW = viewport.clientWidth || vRect.width;
            const clientH = viewport.clientHeight || vRect.height;
            const wPct = Math.round(((hRect.width / clientW) * 100) * 10) / 10;
            const hPct = Math.round(((hRect.height / clientH) * 100) * 10) / 10;
            const zIndex = parseInt(window.getComputedStyle(hs).zIndex, 10) || 10;

            positions[key] = {
                left: pos.left,
                top: pos.top,
                width: wPct || 11.0,
                height: hPct || 18.6,
                z: zIndex
            };
        });

        try {
            const formData = new FormData();
            formData.append('action', 'save_slot_positions');
            formData.append('view', VIEW_NAME);
            formData.append('positions', JSON.stringify(positions));

            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error("Réponse serveur non JSON:", text);
                alert("Erreur serveur lors de l'enregistrement: " + text.substring(0, 300));
                return;
            }

            if (data.success) {
                alert("✓ " + data.message);
                window.location.reload();
            } else {
                alert("Erreur: " + (data.error || "Impossible de sauvegarder"));
            }
        } catch (e) {
            console.error("Erreur calibration:", e);
            alert("Erreur réseau lors de la transmission.");
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    };

    // Rétablissement par défaut
    window.resetCalibratedPositions = async function() {
        if (!confirm("Voulez-vous rétablir les positions d'origine pour cette carte ?")) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'reset_slot_positions');
            formData.append('view', VIEW_NAME);

            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error("Réponse serveur:", text);
                alert("Erreur serveur : " + text.substring(0, 300));
                return;
            }

            if (data.success) {
                alert("✓ " + data.message);
                window.location.reload();
            } else {
                alert("Erreur: " + (data.error || "Impossible de réinitialiser"));
            }
        } catch (e) {
            console.error("Erreur réinitialisation:", e);
            alert("Erreur réseau.");
        }
    };

    // Copier le CSS généré
    window.copyCalibratedCss = function() {
        const viewport = getViewport();
        if (!viewport) return;

        let css = "/* Positions calibrées via Drag & Drop */\n";
        const hotspots = viewport.querySelectorAll('.rts-hotspot');

        hotspots.forEach(hs => {
            const key = getHotspotKey(hs);
            if (!key) return;

            const pos = getElementPctPos(hs);
            const vRect = viewport.getBoundingClientRect();
            const hRect = hs.getBoundingClientRect();
            const wPct = (Math.round(((hRect.width / vRect.width) * 100) * 10) / 10) || 11.0;
            const hPct = (Math.round(((hRect.height / vRect.height) * 100) * 10) / 10) || 18.6;
            const z = parseInt(window.getComputedStyle(hs).zIndex, 10) || 10;

            if (VIEW_NAME === 'resources') {
                if (key === 'bunker-hq') {
                    css += `.hotspot-bunker-hq { left: ${pos.left}% !important; top: ${pos.top}% !important; width: ${wPct}% !important; height: ${hPct}% !important; z-index: ${z} !important; }\n`;
                } else {
                    css += `.hotspot-slot-${key} { left: ${pos.left}% !important; top: ${pos.top}% !important; width: ${wPct}% !important; height: ${hPct}% !important; z-index: ${z} !important; }\n`;
                }
            } else {
                if (key === 'gateway') {
                    css += `.hotspot-city-slot-gateway { left: ${pos.left}% !important; top: ${pos.top}% !important; width: ${wPct}% !important; height: ${hPct}% !important; z-index: ${z} !important; }\n`;
                } else {
                    css += `.hotspot-city-slot-${key} { left: ${pos.left}% !important; top: ${pos.top}% !important; width: ${wPct}% !important; height: ${hPct}% !important; z-index: ${z} !important; }\n`;
                }
            }
        });

        navigator.clipboard.writeText(css).then(() => {
            alert("✓ Code CSS copié dans le presse-papier !");
        }).catch(() => {
            prompt("Copiez le code CSS ci-dessous :", css);
        });
    };
})();
</script>

