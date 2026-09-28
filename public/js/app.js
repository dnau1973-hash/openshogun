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

// ==========================================================
// COMPOSANT ORIENTÉ OBJET : ProgressBar
// Encapsulation stricte de l'état, identifiants uniques et indépendance totale des instances
// ==========================================================

class ProgressBar {
    constructor(element, options = {}) {
        if (!element) return;
        this.element = element;

        // Identifiant unique garanti propre à chaque instance (évite tout conflit ou collision)
        this.id = options.id || element.dataset.progressId || (element.id && element.id !== 'buildingProgressBar' ? element.id : null) || ('pbar_' + Math.random().toString(36).substring(2, 9));
        this.element.dataset.progressId = this.id;

        // Timestamps isolés par instance
        this.startedAt = options.startedAt ?? parseInt(element.getAttribute('data-started') || '0', 10);
        this.finishesAt = options.finishesAt ?? parseInt(element.getAttribute('data-finishes') || '0', 10);

        // Conteneur DOM dédié à ce chantier spécifique
        this.container = options.container || element.closest('.alert, .card, .queue-item, .queue-progress-box, .building-progress-wrapper, tr, td') || element.parentElement;

        // Étiquettes de pourcentages et comptes à rebours scoped à ce conteneur
        this.pctLabels = options.pctLabels || (this.container ? Array.from(this.container.querySelectorAll('.building-progress-pct')) : []);
        this.timerEl = options.timerEl || (this.container ? this.container.querySelector('[data-countdown], .building-time-remaining, .queue-timer') : null);

        // Variables d'état interne strictement encapsulées
        this.currentProgress = 0;
        this.status = 'initialized'; // 'pending' | 'in_progress' | 'completed'
        this.isCompleted = false;

        // Souscriptions d'événements propres à cette instance
        this.onProgress = options.onProgress || null;
        this.onComplete = options.onComplete || null;
        this.onStatusChange = options.onStatusChange || null;

        // Enregistrement dans le registre global des instances
        ProgressBar.instances.set(this.id, this);

        // Premier rafraîchissement synchrone
        this.update();
    }

    /**
     * Calcule l'avancement temporel de façon complètement étanche
     */
    calculateState(now = Math.floor(Date.now() / 1000)) {
        if (this.finishesAt <= this.startedAt) {
            return { pct: 100, remaining: 0, waitTime: 0, status: 'completed' };
        }

        // Cas 1 : Chantier séquentiel en attente dans la file (début planifié dans le futur)
        if (now < this.startedAt) {
            return {
                pct: 0,
                remaining: Math.max(0, this.finishesAt - now),
                waitTime: Math.max(0, this.startedAt - now),
                status: 'pending'
            };
        }

        // Cas 2 : Chantier achevé
        if (now >= this.finishesAt) {
            return { pct: 100, remaining: 0, waitTime: 0, status: 'completed' };
        }

        // Cas 3 : Chantier actif en cours d'avancement
        const totalDuration = this.finishesAt - this.startedAt;
        const elapsed = Math.max(0, now - this.startedAt);
        const pct = Math.min(100, Math.max(0, Math.floor((elapsed / totalDuration) * 100)));
        const remaining = Math.max(0, this.finishesAt - now);

        return { pct, remaining, waitTime: 0, status: 'in_progress' };
    }

    /**
     * Mise à jour autonome de l'instance
     */
    update(now = Math.floor(Date.now() / 1000)) {
        const state = this.calculateState(now);
        const prevStatus = this.status;
        this.status = state.status;
        this.currentProgress = state.pct;

        // Notification de transition de statut
        if (prevStatus !== this.status && typeof this.onStatusChange === 'function') {
            this.onStatusChange(this.status, prevStatus);
        }

        // 1. Mise à jour de la largeur de la jauge
        this.element.style.width = state.pct + '%';
        this.element.setAttribute('aria-valuenow', state.pct);

        // 2. Mise à jour des libellés de pourcentage isolés
        if (this.pctLabels && this.pctLabels.length > 0) {
            this.pctLabels.forEach(lbl => {
                if (state.status === 'pending') {
                    lbl.textContent = 'En attente';
                } else {
                    lbl.textContent = state.pct + '%';
                }
            });
        }

        // 3. Mise à jour du timer associé à cette instance
        if (this.timerEl) {
            if (state.status === 'completed') {
                this.timerEl.innerText = 'Terminé !';
            } else if (state.status === 'pending') {
                this.timerEl.innerText = `⏳ En attente (~${formatTime(state.waitTime)})`;
            } else {
                const timeStr = formatTime(state.remaining);
                if (this.timerEl.classList.contains('building-time-remaining') || this.timerEl.classList.contains('queue-timer')) {
                    this.timerEl.innerText = timeStr;
                } else {
                    this.timerEl.innerText = `⏳ Temps restant : ${timeStr}`;
                }
            }
        }

        // 4. Déclenchement du callback onProgress
        if (typeof this.onProgress === 'function') {
            this.onProgress({
                id: this.id,
                pct: state.pct,
                remaining: state.remaining,
                waitTime: state.waitTime,
                status: state.status
            });
        }

        // 5. Achèvement du chantier
        if (state.status === 'completed' && !this.isCompleted) {
            this.isCompleted = true;
            if (typeof this.onComplete === 'function') {
                this.onComplete(this);
            } else {
                ProgressBar.triggerReload();
            }
        }

        return state.status !== 'completed';
    }

    /**
     * Suppression propre de l'instance
     */
    destroy() {
        ProgressBar.instances.delete(this.id);
    }
}

// Registre d'instances statique (clé = ID unique)
ProgressBar.instances = new Map();

// Déclenchement d'un rechargement contrôlé anti-rebond
ProgressBar.isReloading = false;
ProgressBar.triggerReload = function (delay = 1200) {
    if (ProgressBar.isReloading) return;
    ProgressBar.isReloading = true;
    setTimeout(() => {
        window.location.reload();
    }, delay);
};

// Initialisation globale de toutes les barres de progression
ProgressBar.initAll = function (selector = '.building-progress-bar') {
    document.querySelectorAll(selector).forEach((el, index) => {
        const id = el.dataset.progressId || (el.id && el.id !== 'buildingProgressBar' ? el.id : null) || `pb_auto_${index}_${Date.now()}`;
        if (!ProgressBar.instances.has(id)) {
            new ProgressBar(el, { id });
        }
    });
};

// Alias pour rétro-compatibilité
window.ProgressBar = ProgressBar;
window.ConstructionProgressBar = ProgressBar;

// 2. Gestion des comptes à rebours et barres de progression des chantiers
function initCountdownTimers() {
    // Instanciation automatique de chaque barre de progression comme objet autonome
    ProgressBar.initAll();

    const timers = document.querySelectorAll('[data-countdown]');
    if (timers.length === 0 && ProgressBar.instances.size === 0) return;

    // Ticker centralisé qui orchestre la mise à jour des instances isolées
    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        let hasActiveBars = false;

        // Chaque instance se met à jour avec son propre état isolé
        ProgressBar.instances.forEach(instance => {
            const isActive = instance.update(now);
            if (isActive) hasActiveBars = true;
        });

        // Décomptes temporels autonomes qui ne sont pas rattachés à une ProgressBar
        let hasActiveTimers = false;
        timers.forEach(el => {
            // Ignorer si ce timer est déjà sous la responsabilité d'une instance ProgressBar
            if (el.closest('.building-progress-wrapper, .queue-item, .queue-progress-box, .alert')) {
                return;
            }

            const target = parseInt(el.dataset.countdown, 10);
            const remaining = target - now;

            if (remaining <= 0) {
                el.innerText = 'Terminé !';
                ProgressBar.triggerReload();
            } else {
                hasActiveTimers = true;
                el.innerText = formatTime(remaining);
            }
        });

        if (!hasActiveBars && !hasActiveTimers) {
            clearInterval(interval);
        }
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

