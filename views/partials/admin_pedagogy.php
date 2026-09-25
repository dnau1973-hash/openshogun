<?php
/**
 * Vue Pédagogique & Atelier de Conception de Jeu Vidéo (Projet Père-Fils)
 * Conception : David NAU (Père & Ingénieur) & Gabriel NAU (Fils & Apprenti Game Designer)
 * Stack : PHP 8, Vanilla JavaScript, MySQL, Bootstrap & Tabler.io
 */
?>

<div class="pedagogy-workshop-wrapper mb-5">

    <!-- ===================================================================== -->
    <!-- EN-TÊTE HERO : PROJET PÈRE & FILS (TRANSMISSION & COULISSES)         -->
    <!-- ===================================================================== -->
    <div class="card card-lg mb-4 border-cyan shadow-sm" style="background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(15, 23, 42, 0.98) 100%); border-width: 2px;">
        <div class="card-status-top bg-cyan"></div>
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-cyan-lt text-cyan fw-bold small mb-3 border border-cyan-subtle">
                        <span>👨‍👦</span> Projet Éducatif Père &amp; Fils &bull; Studio d'Apprentissage
                    </div>
                    <h1 class="display-6 fw-bold text-white mb-2">
                        🎮 Les Secrets de Fabrication d'OpenShogun
                    </h1>
                    <p class="text-secondary fs-3 mb-4" style="line-height: 1.6; max-width: 760px;">
                        Bienvenue dans notre atelier partagé ! Découvrez comment fonctionne un jeu vidéo de stratégie en ligne depuis ses fondations : 
                        de l'architecture réseau aux algorithmes du serveur, jusqu'à la création d'un univers visuel complet avec l'Intelligence Artificielle.
                    </p>
                    
                    <!-- Raccourcis d'ancres fluides -->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#section-qui-sommes-nous" class="btn btn-outline-light rounded-pill">
                            <span>👨‍👦</span> 1. Qui sommes-nous ?
                        </a>
                        <a href="#section-algorithmes" class="btn btn-outline-cyan rounded-pill">
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

                <!-- Duo Créateur : Avatars & Complémentarité -->
                <div class="col-lg-4">
                    <div class="card bg-dark-subtle border-secondary-subtle p-3 shadow">
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary-subtle">
                            <span class="avatar avatar-lg rounded-circle bg-blue text-white shadow-sm fw-bold fs-2">DN</span>
                            <div>
                                <div class="fw-bold text-white fs-4">David NAU</div>
                                <div class="text-cyan small fw-semibold">Papa &bull; Informaticien de métier</div>
                                <div class="text-muted small">Architecture, logique PHP &amp; transmission</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-lg rounded-circle bg-teal text-white shadow-sm fw-bold fs-2">GN</span>
                            <div>
                                <div class="fw-bold text-white fs-4">Gabriel NAU</div>
                                <div class="text-teal small fw-semibold">Fils &bull; Apprenti Développeur</div>
                                <div class="text-muted small">Idées gameplay, tests &amp; prompt testing</div>
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
                <h2 class="h1 fw-bold text-white mb-0">1. Qui sommes-nous ?</h2>
                <div class="text-secondary">L'étincelle du projet et les outils fondamentaux choisis pour bâtir le jeu.</div>
            </div>
        </div>

        <div class="row row-cards">
            <!-- Histoire & Transmission -->
            <div class="col-lg-7">
                <div class="card h-100 border-0 shadow-sm" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-body p-4">
                        <div class="badge bg-primary-lt mb-3">Genèse du projet</div>
                        <h3 class="card-title fs-2 text-white mb-3">Transmettre la passion du code de façon concrète</h3>
                        
                        <p class="text-secondary" style="line-height: 1.7;">
                            Je m'appelle <strong>David NAU</strong>. Informaticien de métier depuis de nombreuses années, j'ai toujours été passionné par la conception logicielle et les univers vidéoludiques. Mais en tant que père, une envie m'accompagnait depuis longtemps : <strong>partager cette passion avec mon fils Gabriel</strong>.
                        </p>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Les jeunes d'aujourd'hui utilisent au quotidien des applications et des jeux sophistiqués sans toujours soupçonner la mécanique invisible qui bat sous l'écran. 
                            Avec <strong>OpenShogun</strong>, nous avons voulu ouvrir le capot ensemble : comment un clic sur un bouton ordonne à des fantassins de marcher ? Comment stocker le bois et le riz sans que rien ne se perde ?
                        </p>

                        <div class="alert alert-important bg-dark border-cyan text-cyan mt-4 mb-0">
                            <div class="d-flex gap-2 align-items-start">
                                <span class="fs-2">💡</span>
                                <div>
                                    <strong class="d-block text-white">L'objectif pédagogique :</strong>
                                    Démystifier la programmation en montrant à Gabriel qu'avec des concepts simples, un peu de mathématiques du quotidien et de la méthode, on peut construire un monde virtuel interactif complet.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stack Technique Retenue -->
            <div class="col-lg-5">
                <div class="card h-100 border-0 shadow-sm" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-body p-4">
                        <div class="badge bg-green-lt mb-3">Architecture logicielle</div>
                        <h3 class="card-title fs-2 text-white mb-3">La boîte à outils retenue</h3>
                        <p class="text-secondary small mb-3">
                            Pas de frameworks complexes ou de boîtes noires opaques : des briques fondamentales du web, faciles à lire et à manipuler pour un débutant.
                        </p>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-blue-lt text-blue rounded fw-bold fs-3">🐘</span>
                                    <div>
                                        <strong class="text-white">PHP 8 (Le Moteur &amp; l'Arbitre)</strong>
                                        <div class="text-secondary small">Tourne sur le serveur. Il valide chaque action, applique les règles et garantit l'impartialité.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-yellow-lt text-yellow rounded fw-bold fs-3">⚡</span>
                                    <div>
                                        <strong class="text-white">JavaScript Vanilla (Les Réflexes Client)</strong>
                                        <div class="text-secondary small">Pur JavaScript sans surcouche pour animer les décomptes, les modales et les jauges en temps réel.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-orange-lt text-orange rounded fw-bold fs-3">🐬</span>
                                    <div>
                                        <strong class="text-white">Base MySQL / MariaDB (La Mémoire)</strong>
                                        <div class="text-secondary small">Le registre permanent conservant les comptes de joueurs, les troupes, les ressources et les châteaux.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-cyan-lt text-cyan rounded fw-bold fs-3">🎨</span>
                                    <div>
                                        <strong class="text-white">Tabler.io &amp; Bootstrap (L'Habillage UI/UX)</strong>
                                        <div class="text-secondary small">Un design system professionnel et responsive pour une interface claire sur ordinateur comme sur smartphone.</div>
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
    <!-- SECTION 2 : SOUS LE CAPOT DU JEU (ALGORITHMES VULGARISÉS)             -->
    <!-- ===================================================================== -->
    <section id="section-algorithmes" class="mb-5 pt-2">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="fs-1">⚙️</span>
            <div>
                <h2 class="h1 fw-bold text-white mb-0">2. Sous le capot du jeu</h2>
                <div class="text-secondary">Les grands principes algorithmiques expliqués simplement, avec des métaphores du quotidien.</div>
            </div>
        </div>

        <div class="row row-cards">
            <!-- Algo 1 : Boucle de jeu & Temps Passif -->
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 shadow-sm border-0" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-status-top bg-cyan"></div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-cyan-lt fw-bold">Algorithme 01</span>
                            <span class="fs-2">⏳</span>
                        </div>
                        <h3 class="card-title fs-2 text-white">La Boucle de Jeu &amp; le Temps Passif</h3>
                        <div class="text-cyan small fw-semibold mb-3">« La métaphore de la fontaine et du seau »</div>
                        
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>Le mystère :</strong> Quand tu éteins ton ordinateur pour aller dormir, comment le jeu sait-il combien de bois ou de riz ton fief a récolté pendant la nuit ?
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>L'astuce :</strong> Le serveur ne passe pas sa nuit à compter chaque seconde ! Il retient simplement l'horodatage de ton dernier passage. À ta reconnexion, il regarde sa montre et verse d'un seul coup toute la récolte accumulée.
                        </p>

                        <!-- Pseudo code -->
                        <div class="bg-dark p-3 rounded font-monospace small border border-secondary-subtle text-cyan mt-3">
                            <div class="text-muted mb-1">// Calcul instantané à la reconnexion :</div>
                            secondes_ecoulees = maintenant() - derniere_mise_a_jour;<br>
                            gain_bois = secondes_ecoulees * (production_horaire / 3600);<br>
                            stock_bois = min(stock_actuel + gain_bois, capacite_max_depot);
                        </div>
                    </div>
                </div>
            </div>

            <!-- Algo 2 : Gestion des États & Sécurité -->
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 shadow-sm border-0" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-status-top bg-indigo"></div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-indigo-lt fw-bold">Algorithme 02</span>
                            <span class="fs-2">⚖️</span>
                        </div>
                        <h3 class="card-title fs-2 text-white">Gestion des États &amp; Intégrité</h3>
                        <div class="text-indigo small fw-semibold mb-3">« La métaphore de l'arbitre et du guichet de banque »</div>
                        
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>La règle d'or :</strong> Ton écran de navigateur (le client) ne peut rien décider tout seul. S'il suffisait de modifier un chiffre sur son écran pour avoir 1 million de guerriers, tout le monde tricherait !
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>L'arbitre serveur :</strong> Lorsque tu cliques sur « Construire », tu envoies une demande polie au serveur. Celui-ci vérifie scrupuleusement les stocks réels en base de données avant d'accepter.
                        </p>

                        <!-- Pseudo code -->
                        <div class="bg-dark p-3 rounded font-monospace small border border-secondary-subtle text-indigo mt-3">
                            <div class="text-muted mb-1">// Vérification stricte côté serveur :</div>
                            SI (bois_joueur &gt;= cout_bois ET pierre_joueur &gt;= cout_pierre) ALORS<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;retirer_ressources(cout_bois, cout_pierre);<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;lancer_minuteur_construction(duree_secondes);<br>
                            SINON : refuser_action("Ressources insuffisantes !");
                        </div>
                    </div>
                </div>
            </div>

            <!-- Algo 3 : Tirages Aléatoires & Probabilités -->
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 shadow-sm border-0" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-status-top bg-yellow"></div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-yellow-lt fw-bold">Algorithme 03</span>
                            <span class="fs-2">🎲</span>
                        </div>
                        <h3 class="card-title fs-2 text-white">Tirages Aléatoires &amp; Butin du Héros</h3>
                        <div class="text-yellow small fw-semibold mb-3">« La métaphore du dé géant à 100 faces »</div>
                        
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>Le suspense :</strong> Lors d'une aventure avec le Héros, va-t-on trouver une cage pour capturer un animal, une relique légendaire ou rien du tout ?
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>La méthode :</strong> Le serveur lance un dé virtuel gradué de 1 à 100. Si le résultat se situe dans la fourchette de réussite (par exemple entre 1 et 15 pour 15% de chance), le trésor est débloqué !
                        </p>

                        <!-- Pseudo code -->
                        <div class="bg-dark p-3 rounded font-monospace small border border-secondary-subtle text-yellow mt-3">
                            <div class="text-muted mb-1">// Tirage au sort équiprobable :</div>
                            tirage = nombre_au_hasard(1, 100);<br>
                            SI (tirage &lt;= pourcentage_chance_cage) ALORS<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;ajouter_au_sac("Cage de capture d'animaux sauvages 🪤");<br>
                            SINON : message("Le Héros rentre avec de l'expérience uniquement.");
                        </div>
                    </div>
                </div>
            </div>

            <!-- Algo 4 : Combat & Conditions de Victoire -->
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 shadow-sm border-0" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
                    <div class="card-status-top bg-red"></div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-red-lt fw-bold">Algorithme 04</span>
                            <span class="fs-2">⚔️</span>
                        </div>
                        <h3 class="card-title fs-2 text-white">Résolution des Combats &amp; Équilibre</h3>
                        <div class="text-red small fw-semibold mb-3">« La métaphore de la balance à double plateau »</div>
                        
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>La confrontation :</strong> Deux armées se rencontrent. Comment déterminer qui gagne sans faire un simple pile ou face ?
                        </p>
                        <p class="text-secondary" style="line-height: 1.6;">
                            <strong>L'équilibre :</strong> D'un côté, on empile l'attaque cumulée des assaillants. De l'autre, la défense de la garnison renforcée par le mur d'enceinte. Le ratio entre les deux plateaux dicte les pertes et le pillage.
                        </p>

                        <!-- Pseudo code -->
                        <div class="bg-dark p-3 rounded font-monospace small border border-secondary-subtle text-red mt-3">
                            <div class="text-muted mb-1">// Résolution proportionnelle :</div>
                            puissance_attaque = total_attaquants * bonus_clan;<br>
                            puissance_defense = total_defenseurs * (1 + bonus_muraille);<br>
                            ratio_pertes = puissance_perdante / puissance_gagnante;
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
                <h2 class="h1 fw-bold text-white mb-0">3. L'Univers Visuel et Gemini</h2>
                <div class="text-secondary">Comment concevoir plus de 80 illustrations épiques sans être illustrateur professionnel.</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
            <div class="card-body p-4 p-md-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <div class="badge bg-warning-lt mb-3">Démarche créative &amp; IA</div>
                        <h3 class="fs-2 text-white mb-3">Le Prompt Engineering au service de l'immersion</h3>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Créer un jeu complet demande du code, mais aussi une âme visuelle : des armures étincelantes, des forteresses sous la brume et des reliques ancestrales. <strong>N'étant ni graphiste ni illustrateur de métier</strong>, j'aurais été incapable de dessiner ces dizaines de planches à la main.
                        </p>
                        <p class="text-secondary" style="line-height: 1.7;">
                            Nous nous sommes donc tournés vers <strong>l'intelligence artificielle Gemini de Google</strong>. Notre travail a consisté à apprendre l'art du <em>prompt engineering</em> : décrire avec une minutie chirurgicale les matières (fer laqué, papier washi, cèdre ancien), la lumière rasante, les cadrages dynamiques et le style graphique semi-réaliste évoquant les estampes et les classiques de stratégie.
                        </p>
                        
                        <div class="p-3 rounded bg-dark border border-secondary-subtle mt-3">
                            <div class="fw-bold text-warning mb-1">🔍 Plus de 80 images uniques conçues sur mesure :</div>
                            <div class="text-secondary small">Guerriers des 3 clans féodaux, donjons de cité, reliques d'équipement, animaux sauvages et parcelles de terroir.</div>
                        </div>
                    </div>

                    <!-- Démonstrateur Interactif du Badge « ? » -->
                    <div class="col-lg-5">
                        <div class="card bg-dark border border-cyan-subtle shadow-lg p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-cyan text-dark fw-bold px-2 py-1">Démonstrateur en direct</span>
                                <span class="text-cyan small fw-semibold">Transparence IA</span>
                            </div>
                            
                            <!-- Carte Image de démonstration avec Badge « ? » universel -->
                            <div class="position-relative rounded overflow-hidden border border-secondary-subtle mb-3" style="max-height: 220px; background: #000;">
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

                                <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 text-white small d-flex justify-content-between align-items-center">
                                    <span>Piquier Ashigaru</span>
                                    <span class="text-cyan small">↗ Survolez et cliquez sur le « ? »</span>
                                </div>
                            </div>

                            <div class="text-muted small text-center">
                                👆 <strong>Faites le test !</strong> Cliquez sur l'icône <code>?</code> pour ouvrir la modale, inspecter le prompt original en anglais et sa traduction intégrale en français.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied de section : Transparence et Mention Google Gemini -->
            <div class="card-footer bg-dark-subtle border-top border-secondary-subtle p-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="fs-2 text-warning">✦</span>
                    <div>
                        <h4 class="text-white mb-1">Notre engagement de transparence totale</h4>
                        <p class="text-secondary small mb-2">
                            Dans un projet éducatif, il est capital de ne jamais tromper son public : chaque image générée par une machine est clairement signalée par son badge circulaire « ? ». 
                            Tous les prompts sont consultables librement afin que chacun puisse s'en inspirer pour ses propres créations.
                        </p>
                        <div class="badge bg-dark text-cyan border border-cyan-subtle p-2 font-monospace">
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
        <div class="card border-0 shadow-sm" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
            <div class="card-header d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-1">📖</span>
                    <div>
                        <h3 class="card-title fs-2 text-white mb-0">Le Grimoire Complet des 80 Prompts</h3>
                        <div class="text-secondary small">Explorez, filtrez et copiez l'intégralité des descriptions textuelles d'OpenShogun.</div>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-cyan btn-sm" onclick="toggleGrimoireCatalog()">
                        <span id="grimoireToggleIcon">📂</span> <span id="grimoireToggleLabel">Afficher l'Album Complet</span>
                    </button>
                </div>
            </div>
            
            <div id="grimoireContainer" class="card-body p-4" style="display: none; border-top: 1px solid rgba(255,255,255,0.08);">
                <?php require __DIR__ . '/grimoire_album.php'; ?>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- SECTION 5 : DÉFIS PRATIQUES PÈRE-FILS                                -->
    <!-- ===================================================================== -->
    <section class="mb-4">
        <div class="card border-0 shadow-sm" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.08) !important;">
            <div class="card-body p-4">
                <div class="badge bg-yellow-lt mb-2">Exercices d'application</div>
                <h3 class="card-title fs-2 text-white mb-3">🚀 4 Défis Pratiques à réaliser à quatre mains</h3>
                <p class="text-secondary small mb-4">
                    La meilleure façon d'apprendre la programmation, c'est de tester, de modifier de petites valeurs, et de voir ce qui se passe à l'écran !
                </p>

                <div class="row row-cards">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-dark border-secondary-subtle p-3 h-100">
                            <h4 class="text-warning mb-2">🎯 1. La Vitesse Lumière</h4>
                            <p class="text-secondary small mb-2">Passez la vitesse du serveur à <code>x10</code> ou <code>x20</code> dans la configuration.</p>
                            <div class="text-cyan small mt-auto font-monospace">💡 Découvrir les variables globales</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-dark border-secondary-subtle p-3 h-100">
                            <h4 class="text-cyan mb-2">🎯 2. Équilibrer une Troupe</h4>
                            <p class="text-secondary small mb-2">Donnez 999 d'attaque au Piquier Ashigaru dans la base SQL pour tester les limites.</p>
                            <div class="text-cyan small mt-auto font-monospace">💡 Comprendre le Game Balancing</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-dark border-secondary-subtle p-3 h-100">
                            <h4 class="text-pink mb-2">🎯 3. Créer un Monstre</h4>
                            <p class="text-secondary small mb-2">Rédigez un nouveau prompt pour un <em>Tigre Sacré</em> et intégrez son visuel.</p>
                            <div class="text-cyan small mt-auto font-monospace">💡 Chaîne d'intégration d'assets</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-dark border-secondary-subtle p-3 h-100">
                            <h4 class="text-green mb-2">🎯 4. Robots Testeurs</h4>
                            <p class="text-secondary small mb-2">Lancez <code>php tests/test_barracks.php</code> et observez les feux verts <code>✔</code>.</p>
                            <div class="text-cyan small mt-auto font-monospace">💡 Tests unitaires et rigueur</div>
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
