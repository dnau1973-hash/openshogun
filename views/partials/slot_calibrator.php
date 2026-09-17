<?php
/**
 * Outil Administratif de Calibration Visuelle des Emplacements par Drag & Drop (OpenShogun)
 * Permet de déplacer en direct n'importe quel slot ou bâtiment à la souris et d'enregistrer ses coordonnées.
 */
if (!isset($auth) || !$auth->isAdmin()) {
    return;
}

$calibView = ($page === 'city') ? 'city' : 'resources';
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
            <div style="font-size: 0.72rem; color: #94a3b8;">Glissez les bâtiments / slots à la souris</div>
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
}

body.calibrator-active .calib-coord-pill {
    display: block !important;
}
</style>

<script>
(function() {
    const VIEW_NAME = <?= json_encode($calibView) ?>;
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
            if (selectedHotspot) {
                selectedHotspot.classList.remove('calib-selected');
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

    function selectHotspot(hs) {
        if (selectedHotspot) {
            selectedHotspot.classList.remove('calib-selected');
        }
        selectedHotspot = hs;
        if (selectedHotspot) {
            selectedHotspot.classList.add('calib-selected');
            const key = getHotspotKey(hs);
            const pos = getElementPctPos(hs);
            const infoEl = document.getElementById('calibSelectedInfo');
            if (infoEl) {
                infoEl.innerHTML = `Slot <strong>#${key}</strong> [ X: <strong>${pos.left.toFixed(1)}%</strong> | Y: <strong>${pos.top.toFixed(1)}%</strong> ]`;
            }
        }
    }

    function getElementPctPos(el) {
        const viewport = getViewport();
        const vRect = viewport.getBoundingClientRect();
        const eRect = el.getBoundingClientRect();

        const leftPct = ((eRect.left - vRect.left) / vRect.width) * 100;
        const topPct = ((eRect.top - vRect.top) / vRect.height) * 100;

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
        pill.innerText = `#${key} (${pos.left.toFixed(1)}%, ${pos.top.toFixed(1)}%)`;
    }

    function onHotspotMouseDown(e) {
        if (!isCalibratorActive) return;
        if (e.button !== 0) return; // Clic gauche seulement

        e.preventDefault();
        e.stopPropagation();

        currentDragging = this;
        selectHotspot(currentDragging);
        currentDragging.classList.add('calib-dragging');

        const viewport = getViewport();
        const vRect = viewport.getBoundingClientRect();
        const hRect = currentDragging.getBoundingClientRect();

        dragStartX = e.clientX;
        dragStartY = e.clientY;

        initialLeftPct = ((hRect.left - vRect.left) / vRect.width) * 100;
        initialTopPct = ((hRect.top - vRect.top) / vRect.height) * 100;

        document.addEventListener('mousemove', onDocumentMouseMove);
        document.addEventListener('mouseup', onDocumentMouseUp);
    }

    function onDocumentMouseMove(e) {
        if (!currentDragging) return;

        const viewport = getViewport();
        const vRect = viewport.getBoundingClientRect();

        const deltaXPct = ((e.clientX - dragStartX) / vRect.width) * 100;
        const deltaYPct = ((e.clientY - dragStartY) / vRect.height) * 100;

        let newLeft = Math.max(0, Math.min(95, initialLeftPct + deltaXPct));
        let newTop = Math.max(0, Math.min(95, initialTopPct + deltaYPct));

        // Arrondi au dixième de pourcent
        newLeft = Math.round(newLeft * 10) / 10;
        newTop = Math.round(newTop * 10) / 10;

        currentDragging.style.setProperty('left', newLeft + '%', 'important');
        currentDragging.style.setProperty('top', newTop + '%', 'important');

        updateHotspotPill(currentDragging);
        selectHotspot(currentDragging);
    }

    function onDocumentMouseUp(e) {
        if (currentDragging) {
            currentDragging.classList.remove('calib-dragging');
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

        const pos = getElementPctPos(selectedHotspot);
        let newLeft = Math.max(0, Math.min(95, pos.left + deltaX));
        let newTop = Math.max(0, Math.min(95, pos.top + deltaY));

        newLeft = Math.round(newLeft * 10) / 10;
        newTop = Math.round(newTop * 10) / 10;

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
            const wPct = Math.round(((hRect.width / vRect.width) * 100) * 10) / 10;
            const hPct = Math.round(((hRect.height / vRect.height) * 100) * 10) / 10;
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
            const data = await res.json();

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
            const data = await res.json();

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

