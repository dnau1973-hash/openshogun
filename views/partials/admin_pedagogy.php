<?php
/**
 * Vue Pédagogique & Atelier de Conception de Jeu Vidéo (Projet Père-Fils)
 * Conception : David NAU (Père & Ingénieur) & Gabriel NAU (Fils & Apprenti Game Designer)
 * Charte graphique : Strict Tabler.io (Thème clair, composants officiels, cartes épurées)
 * Stack technique : PHP 8, JavaScript Vanilla, MySQL / MariaDB, Bootstrap & Tabler.io
 */
?>

<div class="pedagogy-workshop-wrapper mb-5">

    <!-- ===================================================================== -->
    <!-- EN-TÊTE HERO : PROJET PÈRE & FILS (TRANSMISSION & COULISSES)         -->
    <!-- ===================================================================== -->
    <div class="card mb-4 bg-white border shadow-sm">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary-lt text-primary fw-bold small mb-3 border border-primary-subtle">
                        <span>👨‍👦</span> Projet Éducatif Père &amp; Fils &bull; Studio d'Apprentissage
                    </div>
                    <h1 class="display-6 fw-bold text-dark mb-2">
                        🎮 Les Secrets de Fabrication d'OpenShogun
                    </h1>
                    <p class="text-secondary fs-3 mb-4" style="line-height: 1.6; max-width: 760px;">
                        Bienvenue dans notre atelier partagé ! Découvrez comment fonctionne un jeu vidéo de stratégie en ligne depuis ses fondations : 
                        de la boucle logique aux algorithmes du serveur, jusqu'à la création d'un univers visuel complet avec l'Intelligence Artificielle.
                    </p>
                    
                    <!-- Raccourcis d'ancres fluides -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#section-qui-sommes-nous" class="btn btn-outline-primary rounded-pill">
                            <span>👨‍👦</span> 1. Qui sommes-nous ?
                        </a>
                        <a href="#section-algorithmes" class="btn btn-outline-azure rounded-pill">
                            <span>⚙️</span> 2. Sous le capot (Algorithmes)
                        </a>
                        <a href="#section-ia-gemini" class="btn btn-outline-warning rounded-pill">
                            <span>✨</span> 3. L'Univers Visuel &amp; Gemini
                        </a>
                        <a href="#section-grimoire" class="btn btn-outline-secondary rounded-pill">
                            <span>📖</span> 4. Le Grimoire des 80 Prompts
                        </a>
                    </div>
                </div>

                <!-- Duo Créateur : Avatars Tabler & Complémentarité -->
                <div class="col-lg-4">
                    <div class="card bg-light border p-3 shadow-none">
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <span class="avatar avatar-lg rounded-circle bg-primary text-white shadow-sm fw-bold fs-2">DN</span>
                            <div>
                                <div class="fw-bold text-dark fs-4">David NAU</div>
                                <div class="text-primary small fw-semibold">Papa &bull; Informaticien de métier</div>
                                <div class="text-secondary small">Infrastructures, logique PHP &amp; transmission</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-lg rounded-circle bg-teal text-white shadow-sm fw-bold fs-2">GN</span>
                            <div>
                                <div class="fw-bold text-dark fs-4">Gabriel NAU</div>
                                <div class="text-teal small fw-semibold">Fils &bull; Apprenti Développeur</div>
                                <div class="text-secondary small">Gameplay, tests &amp; prompt engineering</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- SECTION 1 : QUI SOMMES-NOUS ? (GENÈSE & STACK TECHNIQUE)              -->
    <!-- ===================================================================== -->
    <section id="section-qui-sommes-nous" class="mb-5 pt-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fs-1">👨‍👦</span>
            <div>
                <h2 class="h1 fw-bold text-dark mb-0">1. Qui sommes-nous ?</h2>
                <div class="text-secondary">L'origine du projet et le choix d'une architecture accessible, solide et sans artifice.</div>
            </div>
        </div>

        <div class="row row-cards">
            <!-- Récit enrichi & Transmission -->
            <div class="col-lg-7">
                <div class="card h-100 bg-white border shadow-sm">
                    <div class="card-status-top bg-primary"></div>
                    <div class="card-body p-4 p-md-5">
                        <div class="badge bg-primary-lt mb-3">Genèse &amp; Esprit de transmission</div>
                        <h3 class="card-title fs-2 text-dark mb-3">Démystifier la technologie à travers le jeu vidéo</h3>
                        
                        <p class="text-secondary" style="line-height: 1.7;">
                            Je m'appelle <strong>David NAU</strong>. Informaticien de métier fort d'une longue expérience dans les infrastructures systèmes, les réseaux et le développement d'applications, je suis un passionné de longue date de culture numérique et d'univers vidéoludiques. Au fil des ans, j'ai vu l'informatique se complexifier, s'enfermer dans des couches d'abstractions opaques où l'on perd souvent de vue la beauté des mécanismes fondamentaux.
                        </p>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Ce projet est né d'une volonté simple et sincère : <strong>partager cette passion avec mon fils, Gabriel</strong>. Les jeunes générations manipulent aujourd'hui des jeux vidéo spectaculaires sur leurs consoles ou smartphones, mais rares sont ceux qui ont l'opportunité d'en ouvrir le capot pour voir les engrenages tourner.
                        </p>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Avec <strong>OpenShogun</strong>, nous avons fait du jeu vidéo un <em>véritable terrain d'apprentissage concret</em>. Ensemble, nous avons bâti un univers féodal jouable de A à Z : Gabriel a découvert comment un clic sur un bouton déclenche une requête, comment des mathématiques simples gèrent les réserves de riz et de bois, et comment chaque soldat obéit à des règles programmées avec rigueur.
                        </p>

                        <div class="alert alert-info bg-azure-lt border border-azure-subtle mt-4 mb-0">
                            <div class="d-flex gap-3 align-items-start">
                                <span class="fs-1 text-primary">💡</span>
                                <div>
                                    <strong class="text-dark d-block mb-1">Le crédo pédagogique du projet :</strong>
                                    Un jeu n'est pas une boîte noire magique. C'est un assemblage patient d'idées logiques, de structures de données et de règles équitables qu'un enfant peut comprendre, manipuler et réinventer.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Environnement Technique Retenu -->
            <div class="col-lg-5">
                <div class="card h-100 bg-white border shadow-sm">
                    <div class="card-status-top bg-teal"></div>
                    <div class="card-body p-4 p-md-5">
                        <div class="badge bg-teal-lt mb-3">Architecture &amp; Outils</div>
                        <h3 class="card-title fs-2 text-dark mb-3">Un environnement solide et sans surcouche</h3>
                        <p class="text-secondary small mb-4">
                            Nous avons délibérément écarté les frameworks lourds et les générateurs "clic-bouton" au profit des technologies socles du web moderne.
                        </p>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item bg-transparent px-0 py-3 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-blue-lt text-blue rounded fw-bold fs-3">🐘</span>
                                    <div>
                                        <div class="fw-bold text-dark">PHP 8 (Le Moteur Serveur &amp; l'Arbitre)</div>
                                        <div class="text-secondary small mt-1">
                                            Exécuté côté serveur, il héberge la vérité absolue du monde, applique les formules d'équilibrage et empêche toute triche.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-3 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-yellow-lt text-yellow rounded fw-bold fs-3">⚡</span>
                                    <div>
                                        <div class="fw-bold text-dark">JavaScript Vanilla (Les Réflexes Client)</div>
                                        <div class="text-secondary small mt-1">
                                            Du JavaScript pur, sans framework superflu, pour animer le compte à rebours des chantiers, les modales et la réactivité du HUD.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-3 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-orange-lt text-orange rounded fw-bold fs-3">🐬</span>
                                    <div>
                                        <div class="fw-bold text-dark">MySQL / MariaDB (La Mémoire Permanente)</div>
                                        <div class="text-secondary small mt-1">
                                            La base de données relationnelle qui garantit la persistance des châteaux, des troupes, des rapports et des alliances.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-3 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-cyan-lt text-cyan rounded fw-bold fs-3">🎨</span>
                                    <div>
                                        <div class="fw-bold text-dark">Bootstrap &amp; Tabler.io (L'Habillage UI/UX)</div>
                                        <div class="text-secondary small mt-1">
                                            Un design system rigoureux, épuré et responsive, assurant une parfaite lisibilité sur ordinateur, tablette et mobile.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECTION 2 : SOUS LE CAPOT DU JEU (ALGORITHMES APPROFONDIS)            -->
    <!-- ===================================================================== -->
    <section id="section-algorithmes" class="mb-5 pt-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fs-1">⚙️</span>
            <div>
                <h2 class="h1 fw-bold text-dark mb-0">2. Sous le capot du jeu</h2>
                <div class="text-secondary">Les piliers algorithmiques expliqués en détail, avec des analogies claires et du pseudo-code pédagogique.</div>
            </div>
        </div>

        <div class="row row-cards g-4">
            
            <!-- ALGO 1 : BOUCLE LOGIQUE & ÉTATS DU JEU -->
            <div class="col-lg-4">
                <div class="card h-100 bg-white border shadow-sm">
                    <div class="card-status-top bg-azure"></div>
                    <div class="card-body p-4 d-flex flex-direction-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-azure-lt fw-bold">Pilier Algorithmique 01</span>
                            <span class="fs-1 text-azure">⏳</span>
                        </div>
                        <h3 class="card-title fs-2 text-dark mb-2">La Boucle Logique &amp; les États du Jeu</h3>
                        <div class="text-azure small fw-semibold mb-3">« L'analogie du sablier d'échecs et du réservoir »</div>

                        <p class="text-secondary" style="line-height: 1.6;">
                            Dans un jeu sur navigateur, le monde continue de tourner même lorsque le joueur ferme son onglet. Comment gérer ce temps qui passe sans saturer les serveurs ?
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            Le moteur fonctionne selon un <strong>cycle d'états à 4 phases</strong> :
                        </p>
                        <ol class="text-secondary small ps-3 mb-3">
                            <li><strong>Attente (IDLE) :</strong> Le serveur est passif, en écoute.</li>
                            <li><strong>Action Joueur (INPUT) :</strong> Clic pour entraîner une troupe ou améliorer un bâtiment.</li>
                            <li><strong>Calcul Temporel (TICK / &Delta;t) :</strong> Calcul immédiat du temps écoulé depuis la dernière visite.</li>
                            <li><strong>Résolution (RESOLVED) :</strong> Mise à jour atomique des stocks et transition vers le nouvel état.</li>
                        </ol>

                        <!-- Bloc Pseudo-code -->
                        <div class="bg-light p-3 rounded font-monospace small border border-secondary-subtle text-dark mt-auto">
                            <span class="text-muted">// Boucle passive événementielle :</span><br>
                            delta_t = heure_actuelle() - derniere_mise_a_jour;<br>
                            <span class="text-primary">POUR CHAQUE</span> ressource (bois, pierre, fer) :<br>
                            &nbsp;&nbsp;gain = delta_t * (prod_horaire / 3600);<br>
                            &nbsp;&nbsp;stock = <span class="text-primary">MIN</span>(stock + gain, capacite_max);<br>
                            derniere_mise_a_jour = heure_actuelle();
                        </div>
                    </div>
                </div>
            </div>

            <!-- ALGO 2 : MOTEUR DE RÈGLES & CONDITIONS -->
            <div class="col-lg-4">
                <div class="card h-100 bg-white border shadow-sm">
                    <div class="card-status-top bg-indigo"></div>
                    <div class="card-body p-4 d-flex flex-direction-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-indigo-lt fw-bold">Pilier Algorithmique 02</span>
                            <span class="fs-1 text-indigo">⚖️</span>
                        </div>
                        <h3 class="card-title fs-2 text-dark mb-2">Moteur de Règles &amp; Conditions</h3>
                        <div class="text-indigo small fw-semibold mb-3">« L'analogie de l'arbitre et du passage en douane »</div>

                        <p class="text-secondary" style="line-height: 1.6;">
                            Un jeu équitable repose sur une règle absolue : <em>le client ne décide de rien, le serveur vérifie tout</em>. L'interface propose, le moteur dispose.
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            À chaque interaction, le moteur applique une <strong>triple barrière de contrôle</strong> :
                        </p>
                        <ul class="text-secondary small ps-3 mb-3">
                            <li><strong>Prérequis techniques :</strong> Le joueur possède-t-il les bâtiments et le rang technologique nécessaires ?</li>
                            <li><strong>Solvabilité des ressources :</strong> Les réserves couvrent-elles exactement le coût requis, sans solde négatif ?</li>
                            <li><strong>Conditions de Victoire / Défaite :</strong> Les points d'honneur du Daimyō ou l'intégrité du Donjon (Tenshu) déclenchent-ils une fin de partie ?</li>
                        </ul>

                        <!-- Bloc Pseudo-code -->
                        <div class="bg-light p-3 rounded font-monospace small border border-secondary-subtle text-dark mt-auto">
                            <span class="text-muted">// Validation stricte anti-triche :</span><br>
                            <span class="text-primary">SI</span> (dojo.niveau &lt; troupe.niveau_requis) :<br>
                            &nbsp;&nbsp;<span class="text-danger">REJETER</span>("Bâtiment insuffisant");<br>
                            <span class="text-primary">SI</span> (joueur.riz &lt; cout.riz OU joueur.bois &lt; cout.bois) :<br>
                            &nbsp;&nbsp;<span class="text-danger">REJETER</span>("Ressources manquantes");<br>
                            <span class="text-muted">// Si tout est validé :</span><br>
                            debiter_ressources(cout);<br>
                            ajouter_file_entrainement(troupe, quantite);
                        </div>
                    </div>
                </div>
            </div>

            <!-- ALGO 3 : ALÉATOIRE CONTRÔLÉ (RNG) -->
            <div class="col-lg-4">
                <div class="card h-100 bg-white border shadow-sm">
                    <div class="card-status-top bg-yellow"></div>
                    <div class="card-body p-4 d-flex flex-direction-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-yellow-lt fw-bold">Pilier Algorithmique 03</span>
                            <span class="fs-1 text-yellow">🎲</span>
                        </div>
                        <h3 class="card-title fs-2 text-dark mb-2">Mécanique d'Aléatoire Contrôlé (RNG)</h3>
                        <div class="text-yellow small fw-semibold mb-3">« L'analogie du dé à 100 faces équilibré »</div>

                        <p class="text-secondary" style="line-height: 1.6;">
                            Le hasard pimente l'aventure : découverte de trésors lors des quêtes du Héros, embuscades ou capture d'animaux sauvages dans des cages féodales.
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            Mais un bon jeu vidéo n'utilise jamais un hasard aveugle. Il recourt à un <strong>aléatoire contrôlé</strong> :
                        </p>
                        <ul class="text-secondary small ps-3 mb-3">
                            <li><strong>Distribution uniforme :</strong> Un tirage cryptographique de 1 à 100 garantit une chance réelle et non biaisée.</li>
                            <li><strong>Probabilités paramétrables :</strong> Ex. 15% de chance d'obtenir une cage de capture, ajustable depuis l'administration.</li>
                            <li><strong>Anti-frustration (Bad Luck Mitigation) :</strong> Le moteur compense les longues séries d'échecs en augmentant légèrement la chance au tirage suivant.</li>
                        </ul>

                        <!-- Bloc Pseudo-code -->
                        <div class="bg-light p-3 rounded font-monospace small border border-secondary-subtle text-dark mt-auto">
                            <span class="text-muted">// Tirage de probabilité contrôlée :</span><br>
                            tirage = nombre_aleatoire(1, 100);<br>
                            seuil_cage = config('proba_cage', 15);<br>
                            <span class="text-primary">SI</span> (tirage &lt;= seuil_cage) :<br>
                            &nbsp;&nbsp;inventaire.ajouter('cage_capture_fer', 1);<br>
                            &nbsp;&nbsp;notifier("✨ Trouvé : Cage de capture !");<br>
                            <span class="text-primary">SINON</span> :<br>
                            &nbsp;&nbsp;attribuer_experience_seule(heros, 50);
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECTION 3 : L'UNIVERS VISUEL ET GEMINI (PROMPT ENGINEERING & DÉMO)   -->
    <!-- ===================================================================== -->
    <section id="section-ia-gemini" class="mb-5 pt-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fs-1">✨</span>
            <div>
                <h2 class="h1 fw-bold text-dark mb-0">3. L'Univers Visuel et Gemini</h2>
                <div class="text-secondary">Concevoir plus de 80 illustrations épiques grâce au prompt engineering avec Google Gemini.</div>
            </div>
        </div>

        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-status-top bg-purple"></div>
            <div class="card-body p-4 p-md-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <div class="badge bg-purple-lt mb-3">Direction Artistique &amp; IA</div>
                        <h3 class="fs-2 text-dark mb-3">Le Prompt Engineering au service de l'immersion</h3>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Donner vie à un jeu de stratégie sur le Japon féodal nécessite une identité visuelle forte : samouraïs en armures laquées, bannières flottant au vent, pavillons de thé et forteresses juchées sur des pitons rocheux. <strong>N'étant ni graphiste ni illustrateur de métier</strong>, je n'aurais jamais pu dessiner ces dizaines d'œuvres à la main.
                        </p>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Nous nous sommes donc tournés vers <strong>l'intelligence artificielle Gemini de Google</strong> comme véritable partenaire créatif. Le défi a consisté à concevoir avec méthode plus de **80 descriptions textuelles détaillées (prompts)** en anglais : choix des matières (fer martelé, soie, papier washi), contrastes de lumières d'aube ou de crépuscule, et style pictural semi-réaliste évoquant les classiques du genre.
                        </p>
                        
                        <div class="alert alert-light border border-secondary-subtle p-3 mt-3 mb-0">
                            <div class="fw-bold text-dark mb-1">🔍 Plus de 80 images sur mesure intégrées au moteur :</div>
                            <div class="text-secondary small">
                                Les 12 guerriers du Dojo, les 13 engins de siège et cavaleries, les 17 bâtiments de cité, les parcelles de ressources et les bêtes sauvages.
                            </div>
                        </div>
                    </div>

                    <!-- Démonstrateur Interactif du Badge « ? » -->
                    <div class="col-lg-5">
                        <div class="card bg-light border shadow-sm p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary text-white fw-bold px-2 py-1">Exemple interactif</span>
                                <span class="text-primary small fw-semibold">Transparence IA</span>
                            </div>
                            
                            <!-- Carte Image de démonstration avec Badge « ? » universel -->
                            <div class="position-relative rounded overflow-hidden border border-secondary-subtle mb-3 bg-white" style="max-height: 220px;">
                                <img src="/public/assets/units/piquier_ashigaru_yari.jpg" alt="Piquier Ashigaru" class="w-100 h-100" style="object-fit: cover; object-position: top center; display: block;">
                                
                                <!-- Badge Circulaire « ? » Universel branché sur ai_prompt_modal.js -->
                                <button type="button" 
                                        class="ai-prompt-badge" 
                                        data-ai-title="Piquier Ashigaru (Yari)"
                                        data-ai-img="/public/assets/units/piquier_ashigaru_yari.jpg"
                                        data-ai-prompt="Full-body RTS video game unit illustration of a Sengoku Jidai Ashigaru Spearman, holding a long bamboo yari spear with a gleaming steel point, dynamic ready combat pose, light lacquered chest armor and jingasa conical helmet, standing in a misty bamboo forest near an authentic wooden fortress gate, colorful hand-drawn painterly vector art style of Travian and Grepolis, epic feudal Japanese tactical atmosphere, colorful vibrant colors, heroic composition. --no rice paper border, parchment, Japanese letters, kanji, text, frame"
                                        data-ai-translation="Illustration de jeu de stratégie en pied d'un lancier Ashigaru de l'époque Sengoku, tenant une longue lance yari en bambou à pointe d'acier étincelante, posture dynamique de préparation au combat, armure pectorale laquée légère et chapeau conique jingasa, debout dans une forêt de bambous brumeuse près d'une porte de forteresse en bois d'époque, style artistique vectoriel peint à la main coloré rappelant Travian et Grepolis..."
                                        title="Cliquez pour admirer la modale de transparence IA">
                                    <span class="ai-badge-icon">?</span>
                                </button>

                                <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-white bg-opacity-90 text-dark small border-top d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Piquier Ashigaru</span>
                                    <span class="text-primary small fw-semibold">↗ Cliquez sur le « ? »</span>
                                </div>
                            </div>

                            <div class="text-secondary small text-center">
                                👆 <strong>Faites le test !</strong> Cliquez sur l'icône <code>?</code> en haut à droite de l'image pour ouvrir la modale, découvrir le prompt original en anglais et sa traduction française intégrale.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied de section : Transparence et Mention Google Gemini -->
            <div class="card-footer bg-light border-top p-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="fs-1 text-primary">✦</span>
                    <div>
                        <h4 class="text-dark mb-1">Notre engagement d'éthique et de transparence totale</h4>
                        <p class="text-secondary small mb-2">
                            Dans un projet éducatif, il est indispensable d'être clair avec son public : chaque image générée par une machine porte le badge circulaire « ? ». 
                            Tous les prompts sont documentés et consultables librement pour permettre à chacun de comprendre la relation de cause à effet entre les mots choisis et le résultat produit.
                        </p>
                        <div class="badge bg-white text-dark border p-2 font-monospace">
                            Mention légale exacte : « Cette image a été générée par l'intelligence artificielle Gemini de Google. »
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECTION 4 : LE GRIMOIRE COMPLET DES 80 PROMPTS IA (COLLAPSIBLE)       -->
    <!-- ===================================================================== -->
    <section id="section-grimoire" class="mb-5 pt-2">
        <div class="card bg-white border shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-1">📖</span>
                    <div>
                        <h3 class="card-title fs-2 text-dark mb-0">Le Grimoire Complet des 80 Prompts</h3>
                        <div class="text-secondary small">Explorez, filtrez et copiez l'intégralité des descriptions textuelles d'OpenShogun.</div>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleGrimoireCatalog()">
                        <span id="grimoireToggleIcon">📂</span> <span id="grimoireToggleLabel">Afficher l'Album Complet</span>
                    </button>
                </div>
            </div>
            
            <div id="grimoireContainer" class="card-body p-4" style="display: none; border-top: 1px solid #e2e8f0;">
                <?php require __DIR__ . '/grimoire_album.php'; ?>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECTION 5 : DÉFIS PRATIQUES PÈRE-FILS                                -->
    <!-- ===================================================================== -->
    <section class="mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-status-top bg-yellow"></div>
            <div class="card-body p-4">
                <div class="badge bg-yellow-lt mb-2">Exercices d'application</div>
                <h3 class="card-title fs-2 text-dark mb-3">🚀 4 Défis Pratiques à réaliser à quatre mains</h3>
                <p class="text-secondary small mb-4">
                    La meilleure façon d'apprendre la programmation, c'est d'expérimenter, de modifier de petites variables, et d'observer le résultat immédiat !
                </p>

                <div class="row row-cards g-3">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light border p-3 h-100 shadow-none">
                            <h4 class="text-dark mb-2">🎯 1. La Vitesse Lumière</h4>
                            <p class="text-secondary small mb-2">Modifiez la vitesse du serveur à <code>x10</code> ou <code>x20</code> dans la configuration.</p>
                            <div class="text-primary small mt-auto font-monospace fw-semibold">💡 Découvrir les constantes globales</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light border p-3 h-100 shadow-none">
                            <h4 class="text-dark mb-2">🎯 2. Équilibrer une Troupe</h4>
                            <p class="text-secondary small mb-2">Passez l'attaque du Piquier Ashigaru à 999 en base SQL pour tester les extrêmes.</p>
                            <div class="text-azure small mt-auto font-monospace fw-semibold">💡 Comprendre le Game Balancing</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light border p-3 h-100 shadow-none">
                            <h4 class="text-dark mb-2">🎯 3. Créer un Monstre</h4>
                            <p class="text-secondary small mb-2">Rédigez un nouveau prompt pour un <em>Tigre Sacré</em> et intégrez son visuel.</p>
                            <div class="text-teal small mt-auto font-monospace fw-semibold">💡 Chaîne d'intégration d'assets</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light border p-3 h-100 shadow-none">
                            <h4 class="text-dark mb-2">🎯 4. Robots Testeurs</h4>
                            <p class="text-secondary small mb-2">Lancez <code>php tests/test_barracks.php</code> et observez les tests unitaires <code>✔</code>.</p>
                            <div class="text-green small mt-auto font-monospace fw-semibold">💡 Assurance qualité &amp; tests</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
function toggleGrimoireCatalog() {
    const cont = document.getElementById('grimoireContainer');
    const label = document.getElementById('grimoireToggleLabel');
    const icon = document.getElementById('grimoireToggleIcon');
    if (!cont) return;

    if (cont.style.display === 'none' || cont.style.display === '') {
        cont.style.display = 'block';
        if (label) label.textContent = 'Replier le Grimoire';
        if (icon) icon.textContent = '📁';
    } else {
        cont.style.display = 'none';
        if (label) label.textContent = 'Afficher l\'Album Complet';
        if (icon) icon.textContent = '📂';
    }
}
</script>
