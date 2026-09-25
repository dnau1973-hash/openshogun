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
    <!-- SECTION 2 : SOUS LE CAPOT DU JEU (MODULE 2 : ARCHITECTURE & ALGORITHMES)-->
    <!-- ===================================================================== -->
    <style>
        /* Styles spécifiques Module 2 : Sous le capot */
        .module-pedagogy-section .algo-ide-window {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        }
        .module-pedagogy-section .algo-ide-header {
            background-color: #1e293b;
            border-bottom: 1px solid #334155;
        }
        .module-pedagogy-section .algo-code-body {
            background-color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            white-space: pre;
            tab-size: 4;
        }
        .module-pedagogy-section .algo-phase-card {
            display: flex !important;
            align-items: flex-start !important;
            flex-wrap: nowrap !important;
            gap: 0.85rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 0.65rem 0.85rem !important;
            text-align: left !important;
            position: static !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }
        .module-pedagogy-section .algo-phase-card:hover {
            transform: translateX(3px);
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        }
        .module-pedagogy-section .algo-phase-card::before,
        .module-pedagogy-section .algo-phase-card::after {
            display: none !important;
            content: none !important;
        }
        .module-pedagogy-section .step-badge-num {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            min-height: 26px !important;
            max-width: 26px !important;
            max-height: 26px !important;
            border-radius: 50% !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            flex-grow: 0 !important;
            margin-top: 2px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }
        .module-pedagogy-section .step-content {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            text-align: left !important;
        }
        @media (max-width: 991.98px) {
            .module-pedagogy-section .algo-ide-window {
                margin-top: 1rem;
            }
        }
    </style>

    <section id="section-algorithmes" class="module-pedagogy-section mb-5 pt-2">
        
        <!-- En-tête de Module Pédagogique -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-azure-lt text-azure rounded-circle fw-bold fs-2 shadow-sm flex-shrink-0">
                    ⚙️
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-azure text-white text-uppercase tracking-wider fw-bold px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Module Pédagogique 02</span>
                        <span class="badge bg-azure-lt text-azure fw-semibold">Sous le capot</span>
                    </div>
                    <h2 class="h1 fw-bold text-dark mb-0">Les Piliers Algorithmiques du Moteur</h2>
                </div>
            </div>
            <div class="text-secondary small d-none d-md-block text-end" style="max-width: 320px;">
                Comment un jeu par navigateur simule le temps, protège ses règles et équilibre le hasard sans jamais faillir.
            </div>
        </div>

        <!-- Pipeline Visuel : L'enchaînement logique du moteur -->
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <span class="badge bg-light text-secondary border fw-bold text-uppercase" style="font-size: 0.72rem;">Flux d'exécution du moteur</span>
                    <span class="text-secondary small">De l'interaction joueur à l'état final en base de données</span>
                </div>
                <div class="row g-2 align-items-center text-center">
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 bg-azure-lt border border-azure-subtle d-flex align-items-center gap-3 text-start flex-nowrap">
                            <span class="fs-1 flex-shrink-0">⏳</span>
                            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                                <div class="fw-bold text-azure small text-uppercase">Étape 1 &bull; Temps</div>
                                <div class="fw-bold text-dark">Boucle &amp; &Delta;t (Temps)</div>
                                <div class="text-secondary small text-truncate">Rattrapage du temps écoulé</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 bg-indigo-lt border border-indigo-subtle d-flex align-items-center gap-3 text-start flex-nowrap">
                            <span class="fs-1 flex-shrink-0">⚖️</span>
                            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                                <div class="fw-bold text-indigo small text-uppercase">Étape 2 &bull; Arbitrage</div>
                                <div class="fw-bold text-dark">Moteur de Règles</div>
                                <div class="text-secondary small text-truncate">Validation anti-triche stricte</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 bg-yellow-lt border border-yellow-subtle d-flex align-items-center gap-3 text-start flex-nowrap">
                            <span class="fs-1 flex-shrink-0">🎲</span>
                            <div class="flex-grow-1 min-w-0" style="min-width: 0;">
                                <div class="fw-bold text-yellow small text-uppercase">Étape 3 &bull; Résolution</div>
                                <div class="fw-bold text-dark">Aléatoire Contrôlé</div>
                                <div class="text-secondary small text-truncate">Tirages équilibrés &amp; équitables</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- PILIER 1 : LA BOUCLE LOGIQUE & LES ÉTATS DU JEU                   -->
        <!-- ================================================================= -->
        <div class="card bg-white border shadow-sm mb-4 overflow-hidden">
            <div class="card-status-top bg-azure"></div>
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    
                    <!-- Colonne Gauche : Pédagogie & Concept -->
                    <div class="col-lg-6 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-azure text-white fw-bold">Pilier 01</span>
                                <span class="badge bg-azure-lt text-azure fw-semibold">Architecture Temporelle</span>
                            </div>
                            <h3 class="h2 fw-bold text-dark mb-3">La Boucle Logique &amp; les États du Jeu</h3>

                            <!-- Callout Analogie Pédagogique -->
                            <div class="alert alert-light border border-azure-subtle bg-azure-lt p-3 rounded-3 mb-4">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="fs-2 text-azure">⏳</span>
                                    <div>
                                        <strong class="text-dark d-block mb-1">L'analogie du sablier d'échecs et du robinet ouvert :</strong>
                                        <p class="text-secondary small mb-0" style="line-height: 1.5;">
                                            Dans un jeu sur navigateur, il n'y a pas de moteur tournant en continu sur le PC du joueur. Quand vous partez vous coucher, le serveur « ferme les yeux ». 
                                            À votre réveil, il regarde simplement votre montre, calcule le débit d'eau de vos rizières pendant vos 8 heures de sommeil, et remplit instantanément votre réservoir.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Les 4 Phases du Cycle d'États -->
                            <h4 class="text-dark fw-bold mb-3 fs-3">Le cycle d'états à 4 temps :</h4>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-azure text-white">1</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Attente (IDLE) :</strong>
                                        <span class="text-secondary small">Le serveur dort paisiblement. Zéro calcul, zéro consommation de processeur.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-azure text-white">2</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Action Joueur (INPUT) :</strong>
                                        <span class="text-secondary small">Le joueur clique pour lancer une construction ou entraîner des lanciers.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-azure text-white">3</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Calcul Temporel (TICK / &Delta;t) :</strong>
                                        <span class="text-secondary small">Mesure exacte du temps écoulé : <code>&Delta;t = maintenant - dernier_passage</code>.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-azure text-white">4</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Résolution (RESOLVED) :</strong>
                                        <span class="text-secondary small">Mise à jour atomique des stocks, enregistrement en base SQL et retour au sommeil.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 text-secondary small">
                            💡 <strong>Secret de conception :</strong> Cette approche événementielle permet à un simple serveur web de gérer des milliers de châteaux simultanés sans jamais surchauffer.
                        </div>
                    </div>

                    <!-- Colonne Droite : Studio de Code Stylisé -->
                    <div class="col-lg-6 d-flex flex-column">
                        <div class="algo-ide-window rounded-3 overflow-hidden border border-dark shadow-sm d-flex flex-column h-100 bg-dark">
                            <!-- Barre de titre IDE -->
                            <div class="algo-ide-header bg-dark-subtle px-3 py-2 border-bottom border-secondary d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ff5f56;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffbd2e;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #27c93f;"></span>
                                    <span class="text-light-subtle font-monospace ms-2 small">moteur_game_loop.algo</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-light small font-monospace">PHP / Logique Serveur</span>
                            </div>

                            <!-- Bloc Code Syntaxique -->
                            <div class="p-3 font-monospace text-light overflow-x-auto flex-grow-1" style="font-size: 0.84rem; line-height: 1.7; background-color: #1a1d20;">
<span class="text-secondary">// 1. Mesure du temps écoulé depuis la dernière interaction</span>
<span class="text-warning">delta_t</span> = heure_actuelle() - derniere_visite;

<span class="text-secondary">// 2. Production passive des ressources (Riz, Bois, Fer)</span>
<span class="text-info fw-bold">POUR CHAQUE</span> ressource <span class="text-info fw-bold">DANS</span> [riz, bois, fer] :
    <span class="text-secondary">// Calcul du gain exact proportionnel au temps passé</span>
    gain = delta_t * (prod_horaire / 3600);
    
    <span class="text-secondary">// Protection anti-débordement selon le niveau du grenier</span>
    stock_actuel = <span class="text-warning fw-bold">MIN</span>(stock_actuel + gain, capacite_grenier);

<span class="text-secondary">// 3. Validation de l'action joueur et sauvegarde atomique</span>
<span class="text-info fw-bold">SI</span> action_demandee == <span class="text-success">"construire_batiment"</span> :
    executer_construction(action_demandee);

derniere_visite = heure_actuelle();
<span class="text-info fw-bold">RETOURNER</span> <span class="text-success">SUCCES</span>;
                            </div>

                            <!-- Pied de fenêtre IDE : Explication variables -->
                            <div class="bg-black bg-opacity-50 p-3 border-top border-secondary small text-light-subtle">
                                <div class="fw-bold text-white mb-1">🔍 Décomposition des variables clés :</div>
                                <div class="font-monospace text-azure small">&bull; delta_t : Secondes passées hors-ligne (ex: 28 800 s pour 8h)</div>
                                <div class="font-monospace text-warning small">&bull; MIN(...) : Empêche de stocker plus de riz que le grenier n'en contient</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- PILIER 2 : MOTEUR DE RÈGLES & CONDITIONS                          -->
        <!-- ================================================================= -->
        <div class="card bg-white border shadow-sm mb-4 overflow-hidden">
            <div class="card-status-top bg-indigo"></div>
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    
                    <!-- Colonne Gauche : Pédagogie & Concept -->
                    <div class="col-lg-6 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-indigo text-white fw-bold">Pilier 02</span>
                                <span class="badge bg-indigo-lt text-indigo fw-semibold">Arbitrage &amp; Sécurité</span>
                            </div>
                            <h3 class="h2 fw-bold text-dark mb-3">Moteur de Règles &amp; Conditions</h3>

                            <!-- Callout Analogie Pédagogique -->
                            <div class="alert alert-light border border-indigo-subtle bg-indigo-lt p-3 rounded-3 mb-4">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="fs-2 text-indigo">⚖️</span>
                                    <div>
                                        <strong class="text-dark d-block mb-1">L'analogie du passage en douane et de l'arbitre intraitable :</strong>
                                        <p class="text-secondary small mb-0" style="line-height: 1.5;">
                                            En programmation réseau, il existe une règle d'or universelle : <em>« Never trust the client »</em> (Ne jamais faire confiance au navigateur). 
                                            Le joueur peut bidouiller le bouton d'un site web, mais le serveur agit comme un douanier qui exige passeport, visa et pesée exacte des bagages avant de laisser passer le moindre ordre.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Triple Barrière de Contrôle -->
                            <h4 class="text-dark fw-bold mb-3 fs-3">La triple barrière de contrôle serveur :</h4>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-indigo text-white">1</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Barrière Technologique (Prérequis) :</strong>
                                        <span class="text-secondary small">Vérifie l'existence et le niveau du bâtiment (ex: Forge Niv. 3 requise pour forger un Katana).</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-indigo text-white">2</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Barrière Économique (Solvabilité) :</strong>
                                        <span class="text-secondary small">Débite les ressources de manière atomique. Aucun compte ne peut passer en solde négatif.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-indigo text-white">3</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Barrière d'État (Victoire / Défaite) :</strong>
                                        <span class="text-secondary small">Évalue l'honneur du Daimyō et la chute éventuelle du Donjon (Tenshu) pour clore une manche.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 text-secondary small">
                            🛡️ <strong>Sécurité absolue :</strong> Même si un joueur modifie le code HTML dans l'inspecteur de son navigateur, le moteur PHP rejette l'ordre en une fraction de milliseconde.
                        </div>
                    </div>

                    <!-- Colonne Droite : Studio de Code Stylisé -->
                    <div class="col-lg-6 d-flex flex-column">
                        <div class="algo-ide-window rounded-3 overflow-hidden border border-dark shadow-sm d-flex flex-column h-100 bg-dark">
                            <!-- Barre de titre IDE -->
                            <div class="algo-ide-header bg-dark-subtle px-3 py-2 border-bottom border-secondary d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ff5f56;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffbd2e;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #27c93f;"></span>
                                    <span class="text-light-subtle font-monospace ms-2 small">controle_regles.algo</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-light small font-monospace">Contrôle Métier</span>
                            </div>

                            <!-- Bloc Code Syntaxique -->
                            <div class="p-3 font-monospace text-light overflow-x-auto flex-grow-1" style="font-size: 0.84rem; line-height: 1.7; background-color: #1a1d20;">
<span class="text-secondary">// 1. Contrôle des prérequis de niveau</span>
<span class="text-info fw-bold">SI</span> (dojo.niveau &lt; troupe.niveau_requis) :
    <span class="text-danger fw-bold">REJETER</span>(<span class="text-danger">"Erreur 403 : Dojo insuffisant"</span>);

<span class="text-secondary">// 2. Contrôle de solvabilité stricte</span>
cout_total_bois = troupe.cout_bois * quantite;
cout_total_riz  = troupe.cout_riz  * quantite;

<span class="text-info fw-bold">SI</span> (joueur.bois &lt; cout_total_bois OU joueur.riz &lt; cout_total_riz) :
    <span class="text-danger fw-bold">REJETER</span>(<span class="text-danger">"Erreur 402 : Ressources insuffisantes"</span>);

<span class="text-secondary">// 3. Transaction atomique : Débit puis inscription en file</span>
debut_transaction_sql();
joueur.bois -= cout_total_bois;
joueur.riz  -= cout_total_riz;
ajouter_file_entrainement(joueur.id, troupe.id, quantite);
valider_transaction_sql();

<span class="text-info fw-bold">NOTIFIER_JOUEUR</span>(<span class="text-success">"Recrutement de "</span> + quantite + <span class="text-success">" guerriers validé !"</span>);
                            </div>

                            <!-- Pied de fenêtre IDE : Explication variables -->
                            <div class="bg-black bg-opacity-50 p-3 border-top border-secondary small text-light-subtle">
                                <div class="fw-bold text-white mb-1">🔍 Ce que Gabriel a retenu :</div>
                                <div class="font-monospace text-indigo-lt small">&bull; Transaction SQL : « Tout passe ou rien ne passe » (évite les pertes de ressources en cas de panne)</div>
                                <div class="font-monospace text-danger small">&bull; REJETER : Stoppe immédiatement l'exécution sans toucher aux données</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- PILIER 3 : ALÉATOIRE CONTRÔLÉ (RNG & MITIGATION)                 -->
        <!-- ================================================================= -->
        <div class="card bg-white border shadow-sm mb-4 overflow-hidden">
            <div class="card-status-top bg-yellow"></div>
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    
                    <!-- Colonne Gauche : Pédagogie & Concept -->
                    <div class="col-lg-6 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-yellow text-dark fw-bold">Pilier 03</span>
                                <span class="badge bg-yellow-lt text-dark fw-semibold">Mathématiques &amp; Probabilités</span>
                            </div>
                            <h3 class="h2 fw-bold text-dark mb-3">Mécanique d'Aléatoire Contrôlé (RNG)</h3>

                            <!-- Callout Analogie Pédagogique -->
                            <div class="alert alert-light border border-yellow-subtle bg-yellow-lt p-3 rounded-3 mb-4">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="fs-2 text-yellow">🎲</span>
                                    <div>
                                        <strong class="text-dark d-block mb-1">L'analogie du dé à 100 faces équilibré et bienveillant :</strong>
                                        <p class="text-secondary small mb-0" style="line-height: 1.5;">
                                            Dans un jeu vidéo, le pur hasard peut être cruel : rater 10 fois d'affilée une capture d'animal sauvage à 50% de chance donne envie d'abandonner. 
                                            Les concepteurs utilisent donc un <em>hasard pondéré</em> : chaque échec augmente discrètement vos chances au tirage suivant pour récompenser la persévérance.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3 Principes de l'Aléatoire de Jeu -->
                            <h4 class="text-dark fw-bold mb-3 fs-3">Les 3 principes du hasard équilibré :</h4>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-yellow text-dark">1</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Tirage Uniforme (RNG Cryptographique) :</strong>
                                        <span class="text-secondary small">Génération d'un entier équi-réparti de 1 à 100 via <code>random_int()</code> en PHP.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-yellow text-dark">2</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Seuils Paramétrables depuis l'Admin :</strong>
                                        <span class="text-secondary small">Une cage de capture à 15% signifie que tout résultat entre 1 et 15 déclenche le succès.</span>
                                    </div>
                                </div>
                                <div class="algo-phase-card">
                                    <span class="step-badge-num bg-yellow text-dark">3</span>
                                    <div class="step-content">
                                        <strong class="text-dark small d-block">Anti-frustration (Bad Luck Mitigation) :</strong>
                                        <span class="text-secondary small">Un bonus cumulatif de +5% par échec consécutif garantit une victoire avant l'écœurement.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 text-secondary small">
                            🎯 <strong>Plaisir de jeu garanti :</strong> L'aléatoire contrôlé crée du suspense et des moments d'euphorie sans jamais donner le sentiment d'une injustice algorithmique.
                        </div>
                    </div>

                    <!-- Colonne Droite : Studio de Code Stylisé -->
                    <div class="col-lg-6 d-flex flex-column">
                        <div class="algo-ide-window rounded-3 overflow-hidden border border-dark shadow-sm d-flex flex-column h-100 bg-dark">
                            <!-- Barre de titre IDE -->
                            <div class="algo-ide-header bg-dark-subtle px-3 py-2 border-bottom border-secondary d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ff5f56;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ffbd2e;"></span>
                                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #27c93f;"></span>
                                    <span class="text-light-subtle font-monospace ms-2 small">generateur_rng.algo</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-light small font-monospace">Probabilités</span>
                            </div>

                            <!-- Bloc Code Syntaxique -->
                            <div class="p-3 font-monospace text-light overflow-x-auto flex-grow-1" style="font-size: 0.84rem; line-height: 1.7; background-color: #1a1d20;">
<span class="text-secondary">// 1. Tirage au sort équiprobable de 1 à 100</span>
tirage = generer_nombre_aleatoire(1, 100);

<span class="text-secondary">// 2. Seuil configuré en base de données + compensation d'échecs</span>
seuil_de_base = config(<span class="text-success">'proba_cage_capture'</span>, 15);
bonus_pitié   = joueur.echecs_consecutifs * 5; 
seuil_final   = seuil_de_base + bonus_pitié;

<span class="text-secondary">// 3. Résolution du résultat</span>
<span class="text-info fw-bold">SI</span> (tirage &lt;= seuil_final) :
    joueur.inventaire.ajouter(<span class="text-warning">"cage_fer_authentique"</span>, 1);
    joueur.echecs_consecutifs = 0; <span class="text-secondary">// Réinitialisation</span>
    <span class="text-info fw-bold">NOTIFIER</span>(<span class="text-success">"🎉 Capture réussie ! Tirage : "</span> + tirage + <span class="text-success">" / "</span> + seuil_final);
<span class="text-info fw-bold">SINON</span> :
    joueur.echecs_consecutifs += 1; <span class="text-secondary">// Augmente la chance pour la prochaine fois</span>
    attribuer_xp(heros, 50);
    <span class="text-info fw-bold">NOTIFIER</span>(<span class="text-warning">"Échec... mais vos chances augmentent pour le prochain essai !"</span>);
                            </div>

                            <!-- Pied de fenêtre IDE : Explication variables -->
                            <div class="bg-black bg-opacity-50 p-3 border-top border-secondary small text-light-subtle">
                                <div class="fw-bold text-white mb-1">🔍 La leçon de Game Design :</div>
                                <div class="font-monospace text-warning small">&bull; bonus_pitié : Mécanisme invisible qui transforme la frustration en fidélité</div>
                                <div class="font-monospace text-success small">&bull; random_int() : Sécurisé côté serveur, impossible à manipuler par le joueur</div>
                            </div>
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
