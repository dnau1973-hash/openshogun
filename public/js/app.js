/**
 * OpenGalaxy Client Logic
 * Gestion des ressources en temps réel, comptes à rebours et requêtes asynchrones
 */

document.addEventListener('DOMContentLoaded', () => {
    initResourceTickers();
    initCountdownTimers();
});

// 1. Tickers de ressources en direct (incrémentation fluide à la seconde)
function initResourceTickers() {
    const metalEl = document.getElementById('res-val-metal');
    const crystalEl = document.getElementById('res-val-crystal');
    const deutEl = document.getElementById('res-val-deut');

    if (!metalEl || !crystalEl || !deutEl) return;

    let metal = parseFloat(metalEl.dataset.current || 0);
    let crystal = parseFloat(crystalEl.dataset.current || 0);
    let deut = parseFloat(deutEl.dataset.current || 0);

    const metalMax = parseFloat(metalEl.dataset.max || 15000);
    const crystalMax = parseFloat(crystalEl.dataset.max || 15000);
    const deutMax = parseFloat(deutEl.dataset.max || 15000);

    const prodMetalSec = parseFloat(metalEl.dataset.prod || 0) / 3600;
    const prodCrystalSec = parseFloat(crystalEl.dataset.prod || 0) / 3600;
    const prodDeutSec = parseFloat(deutEl.dataset.prod || 0) / 3600;

    setInterval(() => {
        metal = Math.min(metalMax, metal + prodMetalSec);
        crystal = Math.min(crystalMax, crystal + prodCrystalSec);
        deut = Math.min(deutMax, deut + prodDeutSec);

        metalEl.innerText = Math.floor(metal).toLocaleString();
        crystalEl.innerText = Math.floor(crystal).toLocaleString();
        deutEl.innerText = Math.floor(deut).toLocaleString();

        // Mettre à jour les jauges visuelles
        updateBar('metal', metal, metalMax);
        updateBar('crystal', crystal, crystalMax);
        updateBar('deut', deut, deutMax);
    }, 1000);
}

function updateBar(type, val, max) {
    const bar = document.getElementById(`bar-${type}`);
    if (bar && max > 0) {
        const pct = Math.min(100, Math.max(0, (val / max) * 100));
        bar.style.width = `${pct}%`;
    }
}

// 2. Gestion des comptes à rebours (Files de construction, chantiers, flottes)
function initCountdownTimers() {
    const timers = document.querySelectorAll('[data-countdown]');
    if (timers.length === 0) return;

    const interval = setInterval(() => {
        let hasActive = false;
        const now = Math.floor(Date.now() / 1000);

        timers.forEach(el => {
            const target = parseInt(el.dataset.countdown, 10);
            const remaining = target - now;

            if (remaining <= 0) {
                el.innerText = 'Terminé !';
                if (!el.dataset.reloaded) {
                    el.dataset.reloaded = 'true';
                    setTimeout(() => window.location.reload(), 1200);
                }
            } else {
                hasActive = true;
                el.innerText = formatTime(remaining);
            }
        });

        if (!hasActive) clearInterval(interval);
    }, 1000);
}

function formatTime(seconds) {
    if (seconds <= 0) return '00:00:00';
    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (d > 0) {
        return `${d}j ${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

// 3. Gestion des modales d'amélioration
function openUpgradeModal(category, targetId, name, level, costMetal, costCrystal, costDeut, duration) {
    const modal = document.getElementById('upgradeModal');
    if (!modal) return;

    document.getElementById('modalTitle').innerText = `Améliorer : ${name}`;
    document.getElementById('modalTargetLevel').innerText = `Niveau supérieur : ${parseInt(level, 10) + 1}`;
    document.getElementById('modalCostMetal').innerText = costMetal.toLocaleString();
    document.getElementById('modalCostCrystal').innerText = costCrystal.toLocaleString();
    document.getElementById('modalCostDeut').innerText = costDeut.toLocaleString();
    document.getElementById('modalDuration').innerText = formatTime(duration);

    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.onclick = () => submitUpgrade(category, targetId);

    modal.style.display = 'flex';
}

function closeUpgradeModal() {
    const modal = document.getElementById('upgradeModal');
    if (modal) modal.style.display = 'none';
}

// ==========================================================
// ==========================================================
// SYSTÈME DE TOAST TABLER.IO (CONFIRMATIONS D'ACTIONS & ALERTES)
// ==========================================================

function showToast(param1, param2 = 'info', param3 = null, duration = 4500) {
    let message = param1 || '';
    let type = param2 || 'info';
    let title = param3 || null;

    const validTypes = ['info', 'error', 'danger', 'warning', 'success'];
    if (typeof param2 === 'string' && typeof param3 === 'string' && validTypes.includes(param3.toLowerCase())) {
        title = param1;
        message = param2;
        type = param3.toLowerCase();
    } else if (typeof param1 === 'string' && typeof param2 === 'string' && validTypes.includes(param2.toLowerCase())) {
        message = param1;
        type = param2.toLowerCase();
        title = param3;
    }

    if (type === 'error') type = 'danger';

    let container = document.getElementById('tablerToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'tablerToastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '99999';
        container.style.maxWidth = '420px';
        document.body.appendChild(container);
    }

    const typeConfig = {
        success: { color: 'success', icon: '✅', defaultTitle: 'Ordre Exécuté' },
        danger:  { color: 'danger',  icon: '⚠️', defaultTitle: 'Alerte Système' },
        warning: { color: 'warning', icon: '⚡', defaultTitle: 'Avertissement' },
        info:    { color: 'info',    icon: 'ℹ️', defaultTitle: 'Transmission Féodale' }
    };

    const cfg = typeConfig[type] || typeConfig.info;
    const finalTitle = title || cfg.defaultTitle;

    const toastEl = document.createElement('div');
    toastEl.className = `toast show border border-${cfg.color} shadow-lg mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.style.backgroundColor = '#ffffff';
    toastEl.style.borderRadius = '8px';
    toastEl.style.overflow = 'hidden';
    toastEl.style.transition = 'all 0.3s ease';

    toastEl.innerHTML = `
        <div class="toast-header bg-surface border-bottom py-2">
            <span class="status-dot status-dot-animated bg-${cfg.color} me-2"></span>
            <strong class="me-auto text-dark" style="font-size:0.88rem;"></strong>
            <small class="text-muted ms-2" style="font-size:0.75rem;">À l'instant</small>
            <button type="button" class="btn-close ms-2" aria-label="Fermer"></button>
        </div>
        <div class="toast-body d-flex align-items-center gap-2 py-2 px-3 text-dark" style="font-size:0.875rem; line-height:1.4;">
            <span style="font-size:1.25rem;" class="flex-shrink-0">${cfg.icon}</span>
            <div class="toast-message-content flex-grow-1"></div>
        </div>
    `;

    toastEl.querySelector('strong').textContent = finalTitle;
    const msgContent = toastEl.querySelector('.toast-message-content');
    if (typeof message === 'string' && /<[a-z][\s\S]*>/i.test(message)) {
        msgContent.innerHTML = message;
    } else {
        msgContent.textContent = message;
    }

    container.appendChild(toastEl);

    const closeBtn = toastEl.querySelector('.btn-close');
    const removeToast = () => {
        toastEl.style.opacity = '0';
        toastEl.style.transform = 'translateY(10px)';
        setTimeout(() => toastEl.remove(), 250);
    };

    if (closeBtn) closeBtn.onclick = removeToast;
    if (duration > 0) {
        setTimeout(removeToast, duration);
    }

    return Promise.resolve(true);
}

// Remplacer showModalAlert par showToast pour toutes les confirmations d'action
function showModalAlert(param1, param2 = 'info', param3 = null) {
    return showToast(param1, param2, param3);
}

let currentConfirmResolve = null;

function showModalConfirm(message, title = 'Ordre de Commandement', callback = null) {
    // Supporter la signature alternative (title, message, callback)
    if (typeof callback === 'function' || (typeof arguments[1] === 'string' && typeof arguments[2] === 'function')) {
        const actualTitle = message;
        const actualMessage = title;
        const cb = arguments[2];
        return showModalConfirm(actualMessage, actualTitle).then(confirmed => {
            if (confirmed && cb) cb();
            return confirmed;
        });
    }

    return new Promise((resolve) => {
        const modal = document.getElementById('customAlertModal');
        const card = document.getElementById('customAlertCard');
        const titleEl = document.getElementById('customAlertTitle');
        const iconEl = document.getElementById('customAlertIcon');
        const textEl = document.getElementById('customAlertText');
        const actionsEl = document.getElementById('customAlertActions');

        if (!modal) return resolve(false);

        if (currentConfirmResolve) {
            currentConfirmResolve(false);
            currentConfirmResolve = null;
        }
        currentConfirmResolve = resolve;

        const isDemolish = /raser|démant|démol/i.test(title + ' ' + message);
        const isCancelAction = /interruption|annul|suspend/i.test(title + ' ' + message);
        
        let icon = '❓';
        let confirmLabel = 'Confirmer';
        if (isDemolish) {
            icon = '💥';
            confirmLabel = /bâtiment/i.test(title + ' ' + message) ? '💥 Démanteler le bâtiment' : '💥 Raser l\'exploitation';
        } else if (isCancelAction) {
            icon = '🛑';
            confirmLabel = 'Confirmer l\'interruption';
        }

        titleEl.innerText = title;
        iconEl.innerText = icon;
        textEl.innerHTML = (typeof message === 'string') ? message.replace(/\n/g, '<br>') : message;

        actionsEl.innerHTML = `
            <button type="button" class="btn btn-secondary" id="customConfirmCancelBtn" style="padding:0.5rem 1rem;">Annuler</button>
            <button type="button" class="btn btn-primary" id="customConfirmOkBtn" style="padding:0.5rem 1.25rem; background: var(--red-primary, #c2252b); border-color: var(--red-deep, #991b1b); color: #ffffff; font-weight: 700;">${confirmLabel}</button>
        `;

        const doClose = (result) => {
            const res = currentConfirmResolve;
            currentConfirmResolve = null;
            const modal = document.getElementById('customAlertModal');
            if (modal) modal.style.display = 'none';
            if (res) {
                res(result);
            }
        };

        document.getElementById('customConfirmCancelBtn').onclick = () => doClose(false);
        document.getElementById('customConfirmOkBtn').onclick = () => doClose(true);

        const closeBtn = card.querySelector('.modal-close-btn');
        if (closeBtn) {
            closeBtn.onclick = () => doClose(false);
        }

        modal.onclick = (e) => {
            if (e.target === modal) {
                doClose(false);
            }
        };

        modal.style.display = 'flex';
    });
}

function closeCustomAlert() {
    const modal = document.getElementById('customAlertModal');
    if (modal) modal.style.display = 'none';
    if (currentConfirmResolve) {
        const res = currentConfirmResolve;
        currentConfirmResolve = null;
        res(false);
    }
}

// Remplacer globalement window.alert par la modale stylée
window.alert = function(message) {
    // Détecter si c'est une erreur ou un succès
    const isError = message.toLowerCase().includes('erreur') || message.toLowerCase().includes('insuffisant') || message.toLowerCase().includes('invalide');
    const isSuccess = message.toLowerCase().includes('succès') || message.toLowerCase().includes('lancé') || message.toLowerCase().includes('déployée');
    const type = isError ? 'error' : (isSuccess ? 'success' : 'info');
    showModalAlert(message, type);
};

// Actions asynchrones avec la nouvelle modale
async function submitUpgrade(category, targetId) {
    const btn = document.getElementById('modalConfirmBtn');
    btn.disabled = true;
    btn.innerText = 'Initialisation...';

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', category);
    formData.append('target_id', targetId);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de lancer la construction.', 'error');
            btn.disabled = false;
            btn.innerText = 'Lancer la construction';
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur.', 'error');
        btn.disabled = false;
        btn.innerText = 'Lancer la construction';
    }
}

async function cancelBuild(queueId) {
    const confirmed = await showModalConfirm('Voulez-vous vraiment suspendre ces travaux ? 80% des matériaux investis vous seront restitués.', 'Interruption de Chantier');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('queue_id', queueId);

    try {
        const res = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur lors de l\'annulation.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de connexion au serveur.', 'error');
    }
}

