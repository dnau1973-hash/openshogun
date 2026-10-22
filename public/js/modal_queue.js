/**
 * ModalQueueManager (OpenShogun)
 * Orchestrateur séquentiel des modales d'ouverture de session et d'événements.
 * 
 * Évite l'empilement simultané de 3 ou 4 modales au chargement de page (proclamation,
 * annonces, objectifs de quête/didacticiel, chroniques féodales, maître de la sagesse).
 * Affiche les modales une par une, selon leur priorité, avec une transition fluide de 350ms.
 */

(function(window) {
    'use strict';

    class ModalQueue {
        constructor() {
            this.queue = [];
            this.currentModal = null;
            this.isProcessing = false;
            this.displayedModalsInPage = 0;
            this.maxAutoModalsPerPage = 3; // Limite raisonnable pour ne pas surcharger le Daimyō
            this.registeredIds = new Set();
            this.transitionDelay = 350; // Millisecondes entre la fermeture d'une modale et l'ouverture de la suivante
        }

        /**
         * Enregistre une modale dans la file d'attente d'ouverture
         * @param {Object} item
         * @param {string} item.id - Identifiant unique de la modale
         * @param {number} item.priority - Priorité (10 = urgente, 50 = informative)
         * @param {Function} item.open - Fonction appelée pour ouvrir la modale
         * @param {string|HTMLElement} [item.modalElement] - ID, sélecteur ou élément modal
         * @param {Function} [item.onClose] - Callback exécuté à la fermeture
         */
        enqueue(item) {
            if (!item || !item.id || typeof item.open !== 'function') {
                console.warn('[ModalQueueManager] Élément invalide pour enqueue:', item);
                return;
            }

            // Déduplication : ne pas ajouter deux fois la même modale dans la même page
            if (this.registeredIds.has(item.id)) {
                return;
            }

            this.registeredIds.add(item.id);
            item.priority = (typeof item.priority === 'number') ? item.priority : 50;

            this.queue.push(item);
            // Tri croissant par priorité (10 s'affiche avant 20, etc.)
            this.queue.sort((a, b) => a.priority - b.priority);

            // Tenter le traitement au tick suivant pour laisser le temps aux autres composants de s'enregistrer
            setTimeout(() => this.processQueue(), 150);
        }

        /**
         * Déroule la file d'attente
         */
        processQueue() {
            if (this.isProcessing || this.currentModal) {
                return;
            }

            if (this.queue.length === 0) {
                return;
            }

            // Plafond de confort pour la session
            if (this.displayedModalsInPage >= this.maxAutoModalsPerPage) {
                console.info('[ModalQueueManager] Plafond de modales automatiques atteint pour cette page.');
                this.queue = [];
                return;
            }

            const next = this.queue.shift();
            this.currentModal = next;
            this.isProcessing = true;
            this.displayedModalsInPage++;

            try {
                // Attacher la détection de fermeture sur l'élément modal
                this._bindCloseEvents(next);

                // Ouvrir la modale
                next.open();
            } catch (err) {
                console.error(`[ModalQueueManager] Erreur lors de l'ouverture de la modale [${next.id}]:`, err);
                this.notifyClosed(next.id);
            }
        }

        /**
         * Attache les écouteurs de fermeture (Bootstrap 5, overlays custom, etc.)
         * @private
         */
        _bindCloseEvents(item) {
            let el = null;
            if (typeof item.modalElement === 'string') {
                el = document.querySelector(item.modalElement.startsWith('#') || item.modalElement.startsWith('.') ? item.modalElement : '#' + item.modalElement);
            } else if (item.modalElement instanceof HTMLElement) {
                el = item.modalElement;
            }

            if (!el) {
                // Pas d'élément DOM précisé, la modale devra appeler notifyClosed explicitement
                return;
            }

            // Écouteur standard Bootstrap 5
            const bsCloseHandler = () => {
                el.removeEventListener('hidden.bs.modal', bsCloseHandler);
                this.notifyClosed(item.id);
            };
            el.addEventListener('hidden.bs.modal', bsCloseHandler);

            // Écouteur sur boutons de fermeture custom (data-bs-dismiss="modal", .btn-close, .modal-close)
            const closeButtons = el.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .modal-close, .btn-modal-close');
            closeButtons.forEach(btn => {
                const btnHandler = () => {
                    // Délai court pour laisser Bootstrap initier la fermeture
                    setTimeout(() => this.notifyClosed(item.id), 50);
                };
                btn.addEventListener('click', btnHandler, { once: true });
            });
        }

        /**
         * Notifie que la modale active est désormais fermée
         * Déclenche la transition et l'ouverture de la modale suivante dans la file.
         * @param {string} [id] - Identifiant de la modale fermée
         */
        notifyClosed(id) {
            if (!this.currentModal) {
                this._cleanupBackdrops();
                return;
            }

            if (id && this.currentModal.id !== id) {
                return;
            }

            const closedModal = this.currentModal;
            if (typeof closedModal.onClose === 'function') {
                try {
                    closedModal.onClose();
                } catch (e) {
                    console.warn('[ModalQueueManager] Erreur dans onClose callback:', e);
                }
            }

            this.currentModal = null;
            this.isProcessing = false;

            // Nettoyage des backdrops orphelins
            this._cleanupBackdrops();

            // Attendre un délai fluide (350ms) avant d'afficher la prochaine modale
            setTimeout(() => {
                this.processQueue();
            }, this.transitionDelay);
        }

        /**
         * Nettoie les toiles de fond résiduelles et débloque le body si aucune modale n'est visible
         * @private
         */
        _cleanupBackdrops() {
            setTimeout(() => {
                const visibleModals = document.querySelectorAll('.modal.show, [id$="Modal"][style*="display: flex"], [id$="Modal"][style*="display: block"]');
                if (visibleModals.length === 0) {
                    document.querySelectorAll('.modal-backdrop, #weekly-modal-backdrop').forEach(bd => bd.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }, 100);
        }

        /**
         * Réinitialise complètement la file d'attente
         */
        clear() {
            this.queue = [];
            this.currentModal = null;
            this.isProcessing = false;
        }
    }

    // Instance globale
    window.ModalQueueManager = new ModalQueue();

})(window);
