/**
 * Gestionnaire Universel de la Modale de Transparence pour Images IA (OpenShogun)
 */
(function() {
    'use strict';

    let currentPromptText = '';

    document.addEventListener('DOMContentLoaded', () => {
        initAiPromptModal();
    });

    function initAiPromptModal() {
        const modal = document.getElementById('aiPromptModal');
        if (!modal) return;

        const closeBtn = document.getElementById('aiModalCloseBtn');
        const copyBtn = document.getElementById('aiCopyPromptBtn');

        // 1. Délégation globale au clic sur un badge « ? »
        document.body.addEventListener('click', (e) => {
            const badge = e.target.closest('.ai-prompt-badge');
            if (badge) {
                e.preventDefault();
                e.stopPropagation();
                openFromBadge(badge);
            }
        });

        // 2. Clic sur le bouton de fermeture
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        // 3. Clic sur l'overlay extérieur pour fermer
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // 4. Fermeture avec la touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });

        // 5. Copie du prompt anglais
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                if (!currentPromptText) return;
                navigator.clipboard.writeText(currentPromptText).then(() => {
                    const original = copyBtn.innerHTML;
                    copyBtn.innerHTML = '✔ Copié !';
                    copyBtn.style.background = '#059669';
                    copyBtn.style.color = '#ffffff';

                    setTimeout(() => {
                        copyBtn.innerHTML = original;
                        copyBtn.style.background = '';
                        copyBtn.style.color = '';
                    }, 1800);
                }).catch(() => {
                    alert('Prompt copié : ' + currentPromptText);
                });
            });
        }
    }

    function openFromBadge(badge) {
        const title = badge.dataset.aiTitle || 'Détails de Génération IA';
        const img = badge.dataset.aiImg || '';
        const prompt = badge.dataset.aiPrompt || 'Prompt non renseigné.';
        const translation = badge.dataset.aiTranslation || 'Traduction indisponible.';

        openAiModal({
            title: title,
            img: img,
            prompt: prompt,
            translation: translation
        });
    }

    function openAiModal(data) {
        const modal = document.getElementById('aiPromptModal');
        const titleEl = document.getElementById('aiModalTitle');
        const imgEl = document.getElementById('aiModalImage');
        const promptEl = document.getElementById('aiModalPrompt');
        const transEl = document.getElementById('aiModalTranslation');

        if (!modal) return;

        currentPromptText = data.prompt || '';

        if (titleEl) titleEl.textContent = data.title || 'Détails de Génération IA';
        if (imgEl) {
            imgEl.src = data.img || '';
            imgEl.alt = data.title || 'Image IA';
        }
        if (promptEl) promptEl.textContent = data.prompt || '';
        if (transEl) transEl.textContent = data.translation || '';

        modal.style.display = 'flex';
        void modal.offsetWidth; // Reflow pour forcer la transition
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('aiPromptModal');
        if (!modal) return;

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        setTimeout(() => {
            if (!modal.classList.contains('active')) {
                modal.style.display = 'none';
            }
        }, 250);
    }

    // API globale
    window.openAiPromptDetails = openAiModal;
    window.closeAiPromptDetails = closeModal;
})();
