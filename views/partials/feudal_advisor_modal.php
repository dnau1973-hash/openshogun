<?php
/**
 * Modale Classique des Conseillers & Alertes du Sengoku
 * Style sobre et standard (sans fioritures ni couleurs particulières) :
 * - Illustration 16:9 avec parallaxe autonome
 * - Contenu textuel et actions
 * Supporte : Oda Nobunaga, Takayama Kagetora, Torii Mototada, Dame Asuka,
 * Murakami Takeyoshi, Masamune, Hattori Hanzō, Maître de la Sagesse.
 */

$feudalAdvisorsList = [
    'nobunaga' => [
        'name'      => 'Général Oda Nobunaga',
        'domain'    => 'Conquête & Guerre',
        'image'     => '/public/assets/personnages/oda_nobunaga.jpeg',
        'defaultText' => 'Daimyō, préparez vos troupes. L\'ennemi est en marche et notre honneur se jouera sur le champ de bataille.'
    ],
    'kagetora' => [
        'name'      => 'Takayama Kagetora',
        'domain'    => 'Feu Tonnerre & Sièges Coordonnés',
        'image'     => '/public/assets/personnages/takayama_kagetora.jpeg',
        'defaultText' => 'La poudre noire et le bronze parlent plus fort que mille discours. Nos remparts et nos catapultes doivent être parés.'
    ],
    'mototada' => [
        'name'      => 'Intendant Torii Mototada',
        'domain'    => 'Forteresse & Chantiers',
        'image'     => '/public/assets/personnages/intendant_torii_mototada.jpeg',
        'defaultText' => 'Une forteresse ne vaut que par la solidité de ses fondations et la vigilance de son intendant.'
    ],
    'asuka' => [
        'name'      => 'Dame Asuka du Clan Mōri',
        'domain'    => 'Agriculture & Peuple',
        'image'     => '/public/assets/personnages/dame_asuka_clan_mori.jpeg',
        'defaultText' => 'La prospérité d\'un empire commence dans la paix des rizières et la bienveillance envers ceux qui labourent la terre.'
    ],
    'murakami' => [
        'name'      => 'Amiral Murakami Takeyoshi',
        'domain'    => 'Flotte & Mer de Seto',
        'image'     => '/public/assets/personnages/amiral_murakami_takeyoshi.jpeg',
        'defaultText' => 'Les marées de la mer intérieure ne pardonnent pas l\'imprudence. Nos navires surveillent le rivage.'
    ],
    'masamune' => [
        'name'      => 'Maître Forgeron Masamune',
        'domain'    => 'Forge & Métallurgie',
        'image'     => '/public/assets/personnages/maitre_forgeron_masamune.jpeg',
        'defaultText' => 'L\'acier Tamahagane forgé dans le feu et la patience ne plie jamais devant l\'adversité.'
    ],
    'hanzo' => [
        'name'      => 'Hattori Hanzō',
        'domain'    => 'Ombres & Espionnage',
        'image'     => '/public/assets/personnages/hattori_hanzo.jpeg',
        'defaultText' => 'Les ombres révèlent ce que le jour dissimule. Restez sur vos gardes, des regards indiscrets rôdent.'
    ],
    'sensei' => [
        'name'      => 'Le Maître de la Sagesse',
        'domain'    => 'Sagesse & Bushidō',
        'image'     => '/public/assets/personnages/maitre_de_la_sagesse.jpeg',
        'defaultText' => 'Celui qui sait gouverner son propre esprit saura gouverner dix mille provinces.'
    ],
];
?>

<!-- Modale Classique des Conseillers Féodaux -->
<div class="modal fade" id="modalFeudalAdvisor" tabindex="-1" aria-labelledby="modalFeudalAdvisorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-sm">
            
            <!-- En-tête classique -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold m-0" id="modalFeudalAdvisorLabel">
                        Conseiller du Fief
                    </h5>
                    <div class="text-muted small" id="feudalAdvisorDomain">
                        Chronique &amp; Renseignement Féodal
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps : Illustration 16:9 & Contenu -->
            <div class="modal-body p-3 p-md-4">
                
                <!-- Illustration classique 16:9 avec parallaxe autonome -->
                <div class="depth-parallax-scene rounded mb-3" style="width: 100%; aspect-ratio: 16/9; max-height: 380px; overflow: hidden; background: #000000;">
                    <img src="/public/assets/personnages/oda_nobunaga.jpeg" 
                         alt="Conseiller Sengoku" 
                         id="feudalAdvisorImage"
                         class="depth-parallax-img w-100 h-100 object-fit-cover"
                         style="object-position: center;">
                </div>

                <!-- Message du conseiller -->
                <div class="mb-3">
                    <p class="fs-4 fst-italic mb-2" id="feudalAdvisorMessage" style="line-height: 1.6;">
                        Chargement des instructions du conseiller...
                    </p>
                    <div class="text-muted small text-end" id="feudalAdvisorSignature">
                        — Conseiller
                    </div>
                </div>

                <!-- Bloc d'information ou de détails contextuels -->
                <div class="border rounded p-3 bg-light d-none" id="feudalAdvisorDetailsBox">
                    <div id="feudalAdvisorDetailsContent" class="small text-dark"></div>
                </div>

            </div>

            <!-- Pied classique -->
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Fermer
                </button>
                <div class="d-flex gap-2" id="feudalAdvisorActionButtons">
                    <!-- Bouton d'action dynamique si fourni -->
                </div>
            </div>

        </div>
    </div>
</div>

<script>
/**
 * Gestionnaire JS des Modales de Conseillers Sengoku
 */
window.FEUDAL_ADVISORS_DATA = <?= json_encode($feudalAdvisorsList, JSON_UNESCAPED_UNICODE) ?>;

window.openFeudalAdvisorModal = function(options) {
    options = options || {};
    const key = options.character || 'nobunaga';
    const advisor = window.FEUDAL_ADVISORS_DATA[key] || window.FEUDAL_ADVISORS_DATA['nobunaga'];

    const modalEl = document.getElementById('modalFeudalAdvisor');
    if (!modalEl) return;

    // Titre & domaine
    const titleEl = document.getElementById('modalFeudalAdvisorLabel');
    const domainEl = document.getElementById('feudalAdvisorDomain');
    if (titleEl) titleEl.textContent = options.title || advisor.name;
    if (domainEl) domainEl.textContent = options.domain || advisor.domain;

    // Image 16:9
    const imgEl = document.getElementById('feudalAdvisorImage');
    if (imgEl) {
        imgEl.src = options.image || advisor.image;
        imgEl.alt = advisor.name;
    }

    // Discours
    const msgEl = document.getElementById('feudalAdvisorMessage');
    const sigEl = document.getElementById('feudalAdvisorSignature');
    if (msgEl) msgEl.textContent = `« ${options.text || advisor.defaultText} »`;
    if (sigEl) sigEl.textContent = `— ${advisor.name}`;

    // Détails optionnels
    const detailsBox = document.getElementById('feudalAdvisorDetailsBox');
    const detailsContent = document.getElementById('feudalAdvisorDetailsContent');
    if (detailsBox && detailsContent) {
        if (options.detailsHtml) {
            detailsContent.innerHTML = options.detailsHtml;
            detailsBox.classList.remove('d-none');
        } else {
            detailsBox.classList.add('d-none');
        }
    }

    // Boutons d'action
    const actionsContainer = document.getElementById('feudalAdvisorActionButtons');
    if (actionsContainer) {
        if (options.ctaText && options.ctaUrl) {
            actionsContainer.innerHTML = `<a href="${options.ctaUrl}" class="btn ${options.ctaClass || 'btn-primary'}">${options.ctaText}</a>`;
        } else {
            actionsContainer.innerHTML = '';
        }
    }

    // Affichage Bootstrap
    const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInst.show();
};
</script>

