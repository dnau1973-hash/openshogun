/**
 * Gestionnaire Universel de la Modale de Transparence pour Images IA (OpenShogun)
 * Permet d'afficher la version HD, les métadonnées de traçabilité et les prompts sources.
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

        // 1. Délégation globale au clic sur un badge IA
        document.body.addEventListener('click', (e) => {
            const badge = e.target.closest('.ai-prompt-badge');
            if (badge) {
                e.preventDefault();
                e.stopPropagation();
                
                // Masquer le tooltip Tabler/Bootstrap s'il est actif
                if (window.bootstrap && window.bootstrap.Tooltip) {
                    const tooltipInstance = window.bootstrap.Tooltip.getInstance(badge);
                    if (tooltipInstance) {
                        tooltipInstance.hide();
                    }
                }

                openFromBadge(badge);
                return;
            }

            // 2. Délégation au clic sur une image associée à un badge IA
            const aiImg = e.target.closest('.ai-image-container img, .ai-image-clickable');
            if (aiImg) {
                const container = aiImg.closest('.ai-image-container') || aiImg.parentElement;
                const relatedBadge = container ? container.querySelector('.ai-prompt-badge') : null;
                if (relatedBadge) {
                    e.preventDefault();
                    e.stopPropagation();
                    openFromBadge(relatedBadge);
                }
            }
        });

        // 3. Clic sur le bouton de fermeture
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        // 4. Clic sur l'overlay extérieur pour fermer
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // 5. Fermeture avec la touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });

        // 6. Copie du prompt source anglais
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                if (!currentPromptText) return;
                navigator.clipboard.writeText(currentPromptText).then(() => {
                    const original = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copié !';
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
        const model = badge.dataset.aiModel || 'Google Gemini Imagen 3';
        const date = badge.dataset.aiDate || 'Octobre 2026';
        const resolution = badge.dataset.aiResolution || '1920×1080 (HD 16:9)';
        const category = badge.dataset.aiCategory || 'Traçabilité &bull; Grimoire des Prompts Féodaux';

        openAiModal({
            title: title,
            img: img,
            prompt: prompt,
            translation: translation,
            model: model,
            date: date,
            resolution: resolution,
            category: category
        });
    }

    function openAiModal(data) {
        const modal = document.getElementById('aiPromptModal');
        const titleEl = document.getElementById('aiModalTitle');
        const catEl = document.getElementById('aiModalCategory');
        const imgEl = document.getElementById('aiModalImage');
        const fullImgBtn = document.getElementById('aiModalFullImgBtn');
        const modelEl = document.getElementById('aiModalModel');
        const dateEl = document.getElementById('aiModalDate');
        const resEl = document.getElementById('aiModalResolution');
        const promptEl = document.getElementById('aiModalPrompt');
        const transEl = document.getElementById('aiModalTranslation');

        if (!modal) return;

        currentPromptText = data.prompt || '';

        if (titleEl) titleEl.textContent = data.title || 'Détails de Génération IA';
        if (catEl) catEl.innerHTML = data.category || 'Traçabilité &bull; Grimoire des Prompts Féodaux';

        if (imgEl) {
            imgEl.src = data.img || '';
            imgEl.alt = data.title || 'Image IA';
        }

        if (fullImgBtn) {
            if (data.img) {
                fullImgBtn.href = data.img;
                fullImgBtn.style.display = 'inline-flex';
            } else {
                fullImgBtn.style.display = 'none';
            }
        }

        if (modelEl) modelEl.textContent = data.model || 'Google Gemini Imagen 3';
        if (dateEl) dateEl.textContent = data.date || 'Octobre 2026';
        if (resEl) resEl.textContent = data.resolution || '1920×1080 (HD 16:9)';

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
