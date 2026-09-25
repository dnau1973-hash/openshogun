<?php
/**
 * Modale Universelle de Transparence pour Images Générées par IA (Gemini)
 * Permet d'afficher la version HD, le prompt original en anglais et la traduction française.
 */
?>
<div class="ai-modal-overlay" id="aiPromptModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="ai-modal-container">
        
        <!-- En-tête de la modale -->
        <div class="ai-modal-header">
            <div class="ai-modal-header-title">
                <span class="ai-modal-sparkle">✨</span>
                <h3 id="aiModalTitle" class="ai-modal-title">Détails de Génération IA</h3>
            </div>
            <button type="button" class="ai-modal-close-btn" id="aiModalCloseBtn" aria-label="Fermer la fenêtre">
                &times;
            </button>
        </div>

        <!-- Corps défilable -->
        <div class="ai-modal-body">
            <!-- 1. Aperçu de l'image en haute résolution -->
            <div class="ai-modal-image-wrapper">
                <img id="aiModalImage" src="" alt="Aperçu HD de l'image générée" class="ai-modal-img">
            </div>

            <!-- 2. Bloc Prompt Original en Anglais -->
            <div class="ai-prompt-block">
                <div class="ai-prompt-block-header">
                    <span class="ai-prompt-lang-badge">🇬🇧 Prompt Original (English)</span>
                    <button type="button" class="ai-copy-btn" id="aiCopyPromptBtn" title="Copier le prompt">
                        📋 Copier
                    </button>
                </div>
                <div class="ai-prompt-code" id="aiModalPrompt"></div>
            </div>

            <!-- 3. Bloc Traduction Française -->
            <div class="ai-prompt-block ai-prompt-block-fr">
                <div class="ai-prompt-block-header">
                    <span class="ai-prompt-lang-badge ai-badge-fr">🇫🇷 Traduction en Français</span>
                </div>
                <div class="ai-prompt-translation" id="aiModalTranslation"></div>
            </div>
        </div>

        <!-- Pied de modale : Mention légale obligatoire -->
        <div class="ai-modal-footer">
            <div class="ai-modal-disclaimer">
                <span class="ai-gemini-icon">✦</span>
                <span class="ai-disclaimer-text">
                    Cette image a été générée par l'intelligence artificielle Gemini de Google.
                </span>
            </div>
        </div>

    </div>
</div>
