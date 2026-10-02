<?php
/**
 * Modale Universelle de Transparence pour Images Générées par IA (Gemini Imagen 3)
 * Permet d'afficher la version HD, le prompt original en anglais, la traduction française,
 * ainsi que les métadonnées de traçabilité (modèle, date de génération, résolution).
 */
?>
<div class="ai-modal-overlay" id="aiPromptModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="ai-modal-container">
        
        <!-- En-tête de la modale -->
        <div class="ai-modal-header">
            <div class="ai-modal-header-title">
                <span class="ai-modal-sparkle"><i class="fa-solid fa-wand-magic-sparkles text-warning"></i></span>
                <div>
                    <h3 id="aiModalTitle" class="ai-modal-title">Détails de Génération IA</h3>
                    <div class="ai-modal-subtitle" id="aiModalCategory">Traçabilité &bull; Grimoire des Prompts Féodaux</div>
                </div>
            </div>
            <button type="button" class="ai-modal-close-btn" id="aiModalCloseBtn" aria-label="Fermer la fenêtre">
                &times;
            </button>
        </div>

        <!-- Corps défilable -->
        <div class="ai-modal-body">
            <!-- 1. Aperçu de l'image en haute résolution avec bouton plein écran -->
            <div class="ai-modal-image-wrapper">
                <img id="aiModalImage" src="" alt="Aperçu HD de l'image générée" class="ai-modal-img">
                <a id="aiModalFullImgBtn" href="#" target="_blank" class="ai-modal-zoom-btn" title="Ouvrir l'image originale haute résolution dans un nouvel onglet">
                    <i class="fa-solid fa-up-right-and-down-left-from-center me-1"></i>Plein écran HD
                </a>
            </div>

            <!-- 2. Barre des métadonnées IA (Modèle, Date, Résolution) -->
            <div class="ai-modal-meta-grid">
                <div class="ai-meta-item">
                    <span class="ai-meta-icon text-info"><i class="fa-solid fa-robot"></i></span>
                    <div>
                        <div class="ai-meta-label">Modèle IA</div>
                        <div class="ai-meta-val" id="aiModalModel">Google Gemini Imagen 3</div>
                    </div>
                </div>
                <div class="ai-meta-item">
                    <span class="ai-meta-icon text-warning"><i class="fa-regular fa-calendar-check"></i></span>
                    <div>
                        <div class="ai-meta-label">Date de génération</div>
                        <div class="ai-meta-val" id="aiModalDate">Octobre 2026</div>
                    </div>
                </div>
                <div class="ai-meta-item">
                    <span class="ai-meta-icon text-success"><i class="fa-solid fa-expand"></i></span>
                    <div>
                        <div class="ai-meta-label">Format / Résolution</div>
                        <div class="ai-meta-val" id="aiModalResolution">1920×1080 (HD 16:9)</div>
                    </div>
                </div>
            </div>

            <!-- 3. Bloc Prompt Original en Anglais -->
            <div class="ai-prompt-block">
                <div class="ai-prompt-block-header">
                    <span class="ai-prompt-lang-badge"><i class="fa-solid fa-language me-1"></i>Prompt Source (English)</span>
                    <button type="button" class="ai-copy-btn" id="aiCopyPromptBtn" title="Copier le prompt">
                        <i class="fa-solid fa-clipboard me-1"></i>Copier
                    </button>
                </div>
                <div class="ai-prompt-code" id="aiModalPrompt"></div>
            </div>

            <!-- 4. Bloc Traduction Française -->
            <div class="ai-prompt-block ai-prompt-block-fr">
                <div class="ai-prompt-block-header">
                    <span class="ai-prompt-lang-badge ai-badge-fr"><i class="fa-solid fa-language me-1"></i>Traduction en Français &amp; Notes d'Ambiance</span>
                </div>
                <div class="ai-prompt-translation" id="aiModalTranslation"></div>
            </div>
        </div>

        <!-- Pied de modale : Mention légale obligatoire & traçabilité -->
        <div class="ai-modal-footer">
            <div class="ai-modal-disclaimer">
                <i class="fa-solid fa-wand-magic-sparkles text-info me-1"></i>
                <span class="ai-disclaimer-text">
                    Visuel généré par Intelligence Artificielle (Google Gemini Imagen 3). Intégré en transparence au Grimoire des Prompts d'OpenShogun.
                </span>
            </div>
        </div>

    </div>
</div>
