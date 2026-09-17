<?php
/**
 * Vue Pédagogique & Atelier de Conception de Jeu Vidéo (Projet Père-Fils)
 * Support d'apprentissage interactif intégré au panneau d'administration.
 */
?>

<!-- EN-TÊTE DU PROJET PÈRE-FILS -->
<div class="card" style="margin-bottom: 2rem; border: 2px solid #06b6d4; background: linear-gradient(135deg, rgba(8, 145, 178, 0.18) 0%, rgba(15, 23, 42, 0.95) 100%); box-shadow: 0 10px 30px rgba(6, 182, 212, 0.25); border-radius: 14px; padding: 1.75rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(6, 182, 212, 0.2); border: 1px solid #06b6d4; color: #67e8f9; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 0.6rem;">
                <span>🎓</span> Projet Éducatif Père & Fils &bull; Studio de Développement
            </div>
            <h2 style="color: #fff; margin: 0; font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; gap: 0.6rem;">
                <span>🎮</span> Les Secrets de Fabrication d'OpenShogun
            </h2>
            <p style="color: #cbd5e1; font-size: 0.92rem; margin: 0.6rem 0 0 0; max-width: 820px; line-height: 1.6;">
                Ce manuel interactif est votre carnet de bord commun pour explorer pas à pas <strong>comment fonctionne un jeu vidéo de stratégie en ligne</strong> : de l'architecture réseau aux algorithmes de combat, en passant par la génération d'images avec l'Intelligence Artificielle.
            </p>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.6rem; align-items: flex-end;">
            <a href="/docs/MANUEL_CONCEPTION_JEU_PERE_FILS.md" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.55rem 1rem; border-color: #06b6d4; color: #67e8f9; display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700;">
                <span>📖</span> Ouvrir le Manuel Markdown Brut
            </a>
            <span style="font-size: 0.75rem; color: #94a3b8;">Document évolutif &bull; Version 1.0</span>
        </div>
    </div>
</div>

<!-- BARRE DE SÉLECTION RAPIDE DES MODULES PÉDAGOGIQUES -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.75rem; overflow-x: auto; padding-bottom: 0.5rem; flex-wrap: wrap;">
    <button type="button" class="btn btn-secondary peda-nav-btn active" onclick="switchPedaModule('archi')" id="btn-peda-archi" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        🏛️ 1. Architecture Client/Serveur
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('gameloop')" id="btn-peda-gameloop" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        ⚙️ 2. La "Game Loop" & Ressources
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('queue')" id="btn-peda-queue" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        🏗️ 3. Algorithme des Chantiers & Démolition
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('combat')" id="btn-peda-combat" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        ⚔️ 4. Algorithme des Combats & Pillage
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('slots')" id="btn-peda-slots" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        🗺️ 5. Carte & Calibration des Slots (%)
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('prompts')" id="btn-peda-prompts" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        🎨 6. Le Grimoire des Prompts d'Images IA
    </button>
    <button type="button" class="btn btn-secondary peda-nav-btn" onclick="switchPedaModule('ateliers')" id="btn-peda-ateliers" style="font-size: 0.82rem; padding: 0.45rem 0.85rem; font-weight: 700; border-radius: 8px;">
        🚀 7. Défis Pratiques Père-Fils
    </button>
</div>

<!-- ========================================================================= -->
<!-- MODULE 1 : ARCHITECTURE CLIENT / SERVEUR                                  -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-archi" style="display: block;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(6, 182, 212, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">🏛️</span>
            <div>
                <h3 style="margin: 0; color: #38bdf8; font-size: 1.15rem;">Module 1 : L'Architecture Client / Serveur</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Comment un clic sur l'écran se transforme en ordre dans la forteresse</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                Dans un jeu multijoueur sur navigateur comme OpenShogun, il y a toujours deux mondes qui se parlent à la vitesse de la lumière :
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin: 1.5rem 0;">
                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #38bdf8;">
                    <h4 style="color: #38bdf8; margin: 0 0 0.5rem 0; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>💻</span> Le Client (Navigateur Web)
                    </h4>
                    <ul style="padding-left: 1.25rem; margin: 0; font-size: 0.85rem; color: #94a3b8;">
                        <li><strong>Rôle :</strong> Afficher le jeu et capter les actions du joueur.</li>
                        <li><strong>Langages :</strong> HTML (structure), CSS (beauté/style), JavaScript (interactivité et animations).</li>
                        <li><strong>Exemple :</strong> Faire tourner le sablier ⏳ ou afficher la modale de confirmation.</li>
                        <li><strong>Attention :</strong> On ne calcule JAMAIS les points ni les ressources ici car le joueur pourrait tricher !</li>
                    </ul>
                </div>

                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #ef4444;">
                    <h4 style="color: #f87171; margin: 0 0 0.5rem 0; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>🧠</span> Le Serveur (Cerveau PHP & MySQL)
                    </h4>
                    <ul style="padding-left: 1.25rem; margin: 0; font-size: 0.85rem; color: #94a3b8;">
                        <li><strong>Rôle :</strong> Il est le juge suprême et détient la vérité absolue.</li>
                        <li><strong>Langages :</strong> PHP 8 (moteur logique) et MariaDB (mémoire permanente).</li>
                        <li><strong>Exemple :</strong> Vérifier si le daimyō a assez de bois avant d'autoriser l'élévation du Dojo.</li>
                        <li><strong>Sécurité :</strong> Même si un pirate manipule son écran, le serveur rejette tout ordre illégitime.</li>
                    </ul>
                </div>
            </div>

            <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                <h4 style="color: #fbbf24; margin: 0 0 0.5rem 0; font-size: 0.9rem;">💡 La Métaphore du Restaurant :</h4>
                <p style="margin: 0; font-size: 0.85rem; color: #cbd5e1;">
                    Le <strong>Client</strong> est comme le client assis à la table qui lit le menu et passe sa commande au serveur.<br>
                    Le <strong>Serveur PHP</strong> est comme le chef cuisinier dans sa cuisine fermée : c'est lui qui vérifie les ingrédients dans le frigo (la <strong>Base de Données</strong>), prépare le plat selon la recette exacte, et renvoie l'assiette au client.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 2 : GAME LOOP & RESSOURCES                                         -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-gameloop" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(234, 179, 8, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">⚙️</span>
            <div>
                <h3 style="margin: 0; color: #fbbf24; font-size: 1.15rem;">Module 2 : La "Game Loop" & Production de Ressources</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Pourquoi un jeu web n'a pas besoin de tourner en continu à 60 images par seconde</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                Dans un jeu comme <em>Mario</em> ou <em>Fortnite</em>, l'ordinateur fait tourner une boucle infinie 60 fois par seconde (<code>while(true)</code>). Mais sur le web, si 10 000 joueurs jouaient en même temps, le serveur surchaufferait immédiatement !
            </p>

            <h4 style="color: #facc15; margin-top: 1.25rem;">L'Astuce Géniale : Le "Tick on Request" (Calcul par Delta de Temps)</h4>
            <p>
                Le serveur s'endort et ne calcule rien tant que personne ne lui demande rien ! Quand un joueur se connecte ou charge une page, le moteur calcule la différence de temps écoulée depuis la dernière fois :
            </p>

            <div style="background: #0f172a; border: 1px solid #1e293b; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.88rem; color: #38bdf8; margin: 1rem 0;">
                $maintenant = time(); // Temps actuel en secondes (ex: 1726580120)<br>
                $deltaTemps = $maintenant - $planete['last_update']; // Ex: 120 secondes se sont écoulées<br><br>
                // Formule de récolte continue :<br>
                $gainBois = $productionHoraireBois * ($deltaTemps / 3600) * $facteurEnergie;<br>
                $nouveauStock = min($stockActuel + $gainBois, $capaciteEntrepot);
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1.25rem;">
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 1rem; border-radius: 8px;">
                    <strong style="color: #4ade80;">🪵 Bois & 🪨 Pierre :</strong>
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem;">Servent aux fondations des remparts, des bâtisses et des armes. Stockés dans l'<strong>Entrepôt</strong>.</div>
                </div>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 1rem; border-radius: 8px;">
                    <strong style="color: #fbbf24;">🌾 Riz (Koku) :</strong>
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem;">La richesse vivrière du Japon féodal. Nourrit l'armée et les conscrits. Stocké dans le <strong>Grenier à Riz (Kura)</strong>.</div>
                </div>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 1rem; border-radius: 8px;">
                    <strong style="color: #f87171;">⛩️ Énergie Shintō :</strong>
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem;">Si vos Sanctuaires ne produisent pas assez d'énergie, vos récoltes tournent au ralenti !</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 3 : FILE DE CONSTRUCTION & DÉMOLITION                              -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-queue" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(239, 68, 68, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">🏗️</span>
            <div>
                <h3 style="margin: 0; color: #f87171; font-size: 1.15rem;">Module 3 : L'Algorithme des Chantiers & Démolition (Machine à États)</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Comment gérer des files de travaux parallèles et la démolition avec compte à rebours</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                Quand un daimyō ordonne un chantier, il mobilise ses maîtres d'œuvre. Deux concepts fondamentaux régissent cette mécanique :
            </p>

            <h4 style="color: #f87171; margin-top: 1rem;">1. La Règle de Concurrence des Chantiers</h4>
            <ul style="padding-left: 1.25rem; font-size: 0.88rem; color: #94a3b8;">
                <li><strong>Clan Oda (Faction Terran) :</strong> Maîtres bâtisseurs capables de mener <strong>1 chantier urbain</strong> dans la cité ET <strong>1 chantier rural</strong> dans les rizières en simultané !</li>
                <li><strong>Autres Clans :</strong> Disposent d'une seule équipe globale (1 chantier à la fois sur l'ensemble du domaine).</li>
            </ul>

            <h4 style="color: #f87171; margin-top: 1.25rem;">2. L'Astuce Algorithmique du `target_level = 0` pour Démolir</h4>
            <p>
                Pourquoi ne pas supprimer immédiatement un bâtiment quand on clique sur "Raser" ?
            </p>
            <div style="background: rgba(15, 23, 42, 0.9); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; margin: 1rem 0;">
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 220px;">
                        <strong style="color: #fbbf24; font-size: 0.95rem;">Le Problème :</strong><br>
                        <span style="font-size: 0.85rem; color: #94a3b8;">Si on supprimait tout de suite, l'emplacement deviendrait instantanément libre, ce qui permettrait d'abuser du système en esquivant les attaques ou en changeant de bâtiment en 1 seconde !</span>
                    </div>
                    <div style="flex: 1; min-width: 220px;">
                        <strong style="color: #4ade80; font-size: 0.95rem;">La Solution Élégante :</strong><br>
                        <span style="font-size: 0.85rem; color: #94a3b8;">On place un ordre spécial dans <code>construction_queue</code> avec <code>target_level = 0</code>. La durée est égale à 50% du temps de construction.</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.85rem; color: #cbd5e1;">
                    <strong>Les 3 avantages majeurs de cette conception :</strong>
                    <ol style="padding-left: 1.25rem; margin-top: 0.4rem; color: #94a3b8;">
                        <li><strong>Préservation visuelle :</strong> Le sprite du bâtiment reste visible sur la carte avec un badge rouge <code>🗑️</code>.</li>
                        <li><strong>Droit au remords :</strong> Le joueur peut annuler à tout moment (<code>cancelUpgrade</code>) sans pénalité : la bâtisse reste intacte !</li>
                        <li><strong>Libération différée :</strong> C'est seulement lorsque le compte à rebours atteint 0 que le serveur supprime la ligne en base, verse les <strong>30% de remboursement</strong>, et fait réapparaître la bulle verte <code>+</code>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 4 : COMBATS & PILLAGE                                              -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-combat" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(168, 85, 247, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">⚔️</span>
            <div>
                <h3 style="margin: 0; color: #c084fc; font-size: 1.15rem;">Module 4 : L'Algorithme des Combats Féodaux & du Pillage</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Comment les samouraïs, les arquebusiers et les remparts résolvent une bataille</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                Quand une armée marche sur un fief ennemi, le combat est résolu dans le moteur <code>core/CombatEngine.php</code>.
            </p>

            <h4 style="color: #c084fc; margin-top: 1rem;">1. Déroulement en 3 Rounds Tactiques</h4>
            <div style="background: #0f172a; border: 1px solid #1e293b; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; color: #e2e8f0; margin: 1rem 0;">
                Pour chaque Round (1 à 3) :<br>
                1. Dégâts Infligés = max(1, Attaque_Attaquant - (Bouclier_Defenseur * 0.5));<br>
                2. Ratio de Pertes = min(1.0, Dégâts Infligés / Points_Structure_Totaux) * 0.5;<br>
                3. Application des pertes aux régiments : Chaque unité perd (Nombre * Ratio_Pertes);<br>
                4. Si une des deux armées est totalement décimée, le combat s'arrête immédiatement !
            </div>

            <h4 style="color: #c084fc; margin-top: 1.25rem;">2. Le Rôle Protecteur de la Muraille et de la Cachette</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 8px;">
                    <strong style="color: #38bdf8;">🏯 Muraille de Cité (`wall`) :</strong>
                    <p style="font-size: 0.82rem; color: #94a3b8; margin: 0.3rem 0 0 0;">
                        Chaque niveau de muraille augmente l'armure de toute la garnison de <strong>+4% à +5%</strong> et riposte automatiquement avec des tirs d'archers de meurtrières.
                    </p>
                </div>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 8px;">
                    <strong style="color: #fbbf24;">🕳️ Cachette Secrète (`quantum_vault`) :</strong>
                    <p style="font-size: 0.82rem; color: #94a3b8; margin: 0.3rem 0 0 0;">
                        Dissimule une quantité fixe de bois, de pierre et de riz sous terre. Les pillards ennemis victorieux ne peuvent pas voler les ressources protégées dans la cachette !
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 5 : SLOTS & COORDONNÉES RELATIVES                                  -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-slots" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(52, 211, 153, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">🗺️</span>
            <div>
                <h3 style="margin: 0; color: #34d399; font-size: 1.15rem;">Module 5 : La Carte Provinciale & Placement Relatif des Slots (%)</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Comment afficher les bâtiments au millimètre près sur n'importe quel écran</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                Dans un jeu de stratégie RTS, le village féodal est dessiné sur un fond illustré. Comment faire pour que les 16 emplacements de la cité et les 18 parcelles de ressources restent toujours bien alignés, que l'écran fasse 4K ou qu'on joue sur mobile ?
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin: 1.25rem 0;">
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem;">
                    <h5 style="color: #f87171; margin: 0 0 0.4rem 0;">❌ Mauvaise méthode : Pixels fixes (px)</h5>
                    <p style="margin: 0; font-size: 0.83rem; color: #cbd5e1;">
                        Si on écrit <code>left: 420px; top: 180px;</code>, le bâtiment sera à la bonne place sur l'écran du développeur... mais flottera dans le ciel ou hors de l'écran sur un téléphone portable !
                    </p>
                </div>

                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; padding: 1rem;">
                    <h5 style="color: #4ade80; margin: 0 0 0.4rem 0;">✅ Bonne méthode : Pourcentages Relatifs (%)</h5>
                    <p style="margin: 0; font-size: 0.83rem; color: #cbd5e1;">
                        On écrit <code>left: 48.5%; top: 28.2%;</code>.<br>
                        Le navigateur recalcule la position exacte quelle que soit la taille de la fenêtre.
                    </p>
                </div>
            </div>

            <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 8px; font-size: 0.85rem;">
                <strong style="color: #34d399;">🧰 Notre Outil de Calibration Intégré :</strong><br>
                Dans les pages Cité et Ressources, nous avons programmé un <strong>mode de Drag & Drop réservé à l'administrateur</strong> avec sécurité de délimitation. Il permet de déplacer les bâtiments à la souris et enregistre les coordonnées directement dans <code>config/slot_positions.json</code> !
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 6 : GRIMOIRE DES PROMPTS D'IMAGES IA                               -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-prompts" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(244, 114, 182, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span style="font-size: 1.3rem;">🎨</span>
                <div>
                    <h3 style="margin: 0; color: #f472b6; font-size: 1.15rem;">Module 6 : L'Art du Prompt Engineering & Le Grimoire des Images</h3>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">Comment parler à une Intelligence Artificielle pour créer des graphismes de jeu vidéo</span>
                </div>
            </div>
            <span class="badge" style="background: rgba(244, 114, 182, 0.2); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.4); font-size: 0.75rem;">28 Assets Documentés</span>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                L'IA générative d'images (comme Imagen, Midjourney ou Stable Diffusion) ne comprend pas nos pensées : elle lit attentivement les mots-clés qu'on lui donne. C'est ce qu'on appelle le <strong>Prompt Engineering</strong>.
            </p>

            <div style="background: linear-gradient(135deg, rgba(30, 27, 75, 0.5) 0%, rgba(15, 23, 42, 0.8) 100%); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 10px; padding: 1.25rem; margin: 1.25rem 0;">
                <h4 style="color: #c084fc; margin: 0 0 0.5rem 0; font-size: 0.95rem;">🧪 La Formule Magique en 5 Étapes pour les Graphismes de Jeu :</h4>
                <div style="font-size: 0.85rem; font-family: monospace; color: #facc15; background: rgba(0,0,0,0.4); padding: 0.75rem; border-radius: 6px; margin: 0.5rem 0;">
                    [SUJET PRINCIPAL] + [POSE/ACTION] + [STYLE ARTISTIQUE] + [ÉCLAIRAGE/ANGLE] + [CONTRAINTES PNG]
                </div>
                <div style="font-size: 0.82rem; color: #94a3b8; line-height: 1.6;">
                    &bull; <strong>Sujet :</strong> Japanese samurai warrior, Sengoku period, black lacquered armor<br>
                    &bull; <strong>Pose :</strong> Holding sharp steel katana, focused battle stance<br>
                    &bull; <strong>Style :</strong> Ukiyo-e woodblock inspired digital painting, semi-realistic character art<br>
                    &bull; <strong>Angle & Lumière :</strong> Dramatic moody lighting, flying embers and cherry blossom petals<br>
                    &bull; <strong>Contraintes :</strong> Isolated character, clean transparent background, no text, 4k
                </div>
            </div>

            <!-- TABLEAU DES PROMPTS AUTHENTIQUES DU JEU -->
            <h4 style="color: #f472b6; margin: 1.75rem 0 1rem 0; font-size: 1.05rem;">📜 Le Grimoire des Prompts Utilisés pour OpenShogun</h4>

            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.84rem; text-align: left;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.05); color: #f472b6; border-bottom: 2px solid rgba(244, 114, 182, 0.3);">
                            <th style="padding: 0.75rem;">Aperçu</th>
                            <th style="padding: 0.75rem;">Asset & Type</th>
                            <th style="padding: 0.75rem;">Fichier</th>
                            <th style="padding: 0.75rem;">Prompt Exact Transmis à l'IA</th>
                            <th style="padding: 0.75rem; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody style="divide-y divide-slate-800;">
                        <!-- FOND CITÉ -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/shogun_castle_city_bg.jpg" style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #334155;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Cité Castrale (Dorf 2)</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Arrière-plan RTS</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                shogun_castle_city_bg.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>High angle aerial RTS village view of a feudal Japanese castle town, Sengoku period, centered on a massive stone Tenshu citadel, courtyards, dojo, market streets, misty morning light, cherry blossoms, detailed isometric strategy game background</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="High angle aerial RTS village view of a feudal Japanese castle town, Sengoku period, centered on a massive stone Tenshu citadel, courtyards, dojo, market streets, misty morning light, cherry blossoms, detailed isometric strategy game background" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- FOND TERROIRS -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/shogun_rural_terroir_bg.jpg" style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #334155;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Terroirs Ruraux (Dorf 1)</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Arrière-plan Ressources</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                shogun_rural_terroir_bg.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Top-down RTS strategic resource landscape of rural feudal Japan, terraced flooded rice paddies reflecting blue sky, lush bamboo lumber forests, stone quarry cliffs, sacred mountain shrine river, miniature tactical game surface</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Top-down RTS strategic resource landscape of rural feudal Japan, terraced flooded rice paddies reflecting blue sky, lush bamboo lumber forests, stone quarry cliffs, sacred mountain shrine river, miniature tactical game surface" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- TENSHU SPRITE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/tile_tenshu.png" style="width: 45px; height: 45px; object-fit: contain; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Donjon Tenshu</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Sprite Bâtiment Isométrique</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                tile_tenshu.png
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Isolated isometric sprite of a monumental multi-tiered Japanese castle keep (Tenshu), black lacquered timber, white plaster walls, curved green tiled roofs, golden ornaments, high stone foundation base, transparent background, RTS building asset</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Isolated isometric sprite of a monumental multi-tiered Japanese castle keep (Tenshu), black lacquered timber, white plaster walls, curved green tiled roofs, golden ornaments, high stone foundation base, transparent background, RTS building asset" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- RIZIÈRE SPRITE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/tile_riziere.png" style="width: 45px; height: 45px; object-fit: contain;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Rizière Inondée</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Sprite Parcelle Rurale</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                tile_riziere.png
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Isolated game tile sprite of lush green terraced flooded rice field, clean wooden irrigation sluice channels, reflective clear water, vibrant green shoots, RTS terrain asset, transparent background</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Isolated game tile sprite of lush green terraced flooded rice field, clean wooden irrigation sluice channels, reflective clear water, vibrant green shoots, RTS terrain asset, transparent background" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- BÛCHERONS -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/tile_bucheron.png" style="width: 45px; height: 45px; object-fit: contain;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Camp de Bûcherons</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Sprite Parcelle Bois</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                tile_bucheron.png
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Isolated game tile sprite of a Japanese lumberjack timber camp, cut cedar logs stacked neatly, rustic wooden shed with saws and axes, sawdust ground, transparent background</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Isolated game tile sprite of a Japanese lumberjack timber camp, cut cedar logs stacked neatly, rustic wooden shed with saws and axes, sawdust ground, transparent background" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- SAMOURAÏ KATANA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $samFile = __DIR__ . '/../../public/assets/units/samourai_katana.jpg'; ?>
                                <img src="/public/assets/units/samourai_katana.jpg?v=<?= file_exists($samFile) ? filemtime($samFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Samouraï au Katana</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Unité Rang III (Dojo)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                samourai_katana.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Master swordsman samurai in mid-stance drawing sharp steel katana blade, crimson and black silk cords on lamellar armor, fierce focus, flying embers, ukiyo-e digital illustration</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Master swordsman samurai in mid-stance drawing sharp steel katana blade, crimson and black silk cords on lamellar armor, fierce focus, flying embers, ukiyo-e digital illustration" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- CAVALIER ÉCLAIREUR TAKEDA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cavFile = __DIR__ . '/../../public/assets/units/cavalier_eclaireur_takeda.jpg'; ?>
                                <img src="/public/assets/units/cavalier_eclaireur_takeda.jpg?v=<?= file_exists($cavFile) ? filemtime($cavFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Cavalier Éclaireur Takeda</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Reconnaissance & Raids de Kai</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                cavalier_eclaireur_takeda.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dynamic cinematic illustration of an agile Takeda clan samurai scout cavalryman, mounted on a swift wild Japanese mountain horse (Kiso horse), lightweight crimson red lacquered armor with vermilion cords, horned jingasa, scouting yari spear, back banner (sashimono) with Takeda four-diamond crest, rocky mountain ridge overlooking misty valleys at sunrise, ukiyo-e digital art</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dynamic cinematic illustration of an agile Takeda clan samurai scout cavalryman, mounted on a swift wild Japanese mountain horse (Kiso horse), lightweight crimson red lacquered armor with vermilion cords, horned jingasa, scouting yari spear, back banner (sashimono) with Takeda four-diamond crest, rocky mountain ridge overlooking misty valleys at sunrise, ukiyo-e digital art" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ARQUEBUSIER TANEGASHIMA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $arqFile = __DIR__ . '/../../public/assets/units/arquebusier_oda_tanegashima.jpg'; ?>
                                <img src="/public/assets/units/arquebusier_oda_tanegashima.jpg?v=<?= file_exists($arqFile) ? filemtime($arqFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Arquebusier Tanegashima</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Unité Rang II (Clan Oda)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                arquebusier_oda_tanegashima.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Oda clan matchlock musketeer aiming a wooden Tanegashima gun, smoke curling from the muzzle, bamboo tate barricade in foreground, glowing matchcord, dynamic battle action pose</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Oda clan matchlock musketeer aiming a wooden Tanegashima gun, smoke curling from the muzzle, bamboo tate barricade in foreground, glowing matchcord, dynamic battle action pose" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- HÉROS SAMOURAÏ -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <img src="/public/assets/hero_samurai.jpg" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Héros Suprême & Daimyō</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Personnage Joueur</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                hero_samurai.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Epic heroic portrait of a venerable Sengoku Daimyo general in full ornate black and gold samurai armor, horned kabuto helmet, holding an ancient katana, wind blowing cherry blossom petals, dramatic moody lighting</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Epic heroic portrait of a venerable Sengoku Daimyo general in full ornate black and gold samurai armor, horned kabuto helmet, holding an ancient katana, wind blowing cherry blossom petals, dramatic moody lighting" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- BÉLIER TITANESQUE DU DRAGON DE KAI -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $belierFile = __DIR__ . '/../../public/assets/units/belier_dragon_kai.jpg'; ?>
                                <img src="/public/assets/units/belier_dragon_kai.jpg?v=<?= file_exists($belierFile) ? filemtime($belierFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Bélier Titanesque du Dragon de Kai</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Atelier de Siège (Clan Takeda)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                belier_dragon_kai.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Monumental ancient Japanese siege ram machine, colossal battering ram shaped like a ferocious roaring dragon head sculpted from blackened iron and bronze, spitting glowing embers and smoke from its nostrils, heavy fortified wooden carriage built of giant cedar timbers with layered damp leather and reinforced iron plating, iron-rimmed massive spiked wooden wheels rolling in muddy battlefield tracks, vermilion red war banners bearing the four-diamond Takeda clan crest (Takeda-bishi) fluttering on the roof, advancing aggressively toward the colossal stone gate of a besieged Japanese castle fortress at dusk, flying sparks, fiery arrows raining from the sky, misty battlefield atmosphere, dynamic cinematic wide low-angle shot, dramatic volumetric lighting, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Monumental ancient Japanese siege ram machine, colossal battering ram shaped like a ferocious roaring dragon head sculpted from blackened iron and bronze, spitting glowing embers and smoke from its nostrils, heavy fortified wooden carriage built of giant cedar timbers with layered damp leather and reinforced iron plating, iron-rimmed massive spiked wooden wheels rolling in muddy battlefield tracks, vermilion red war banners bearing the four-diamond Takeda clan crest (Takeda-bishi) fluttering on the roof, advancing aggressively toward the colossal stone gate of a besieged Japanese castle fortress at dusk, flying sparks, fiery arrows raining from the sky, misty battlefield atmosphere, dynamic cinematic wide low-angle shot, dramatic volumetric lighting, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- EMBUSCADE SHINOBI MONTÉE (TOKUGAWA) -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $shinobiFile = __DIR__ . '/../../public/assets/units/shinobi_monte_tokugawa.jpg'; ?>
                                <img src="/public/assets/units/shinobi_monte_tokugawa.jpg?v=<?= file_exists($shinobiFile) ? filemtime($shinobiFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Embuscade Shinobi Montée</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Cavalerie Furtive (Clan Tokugawa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                shinobi_monte_tokugawa.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dynamic cinematic illustration of a stealth Tokugawa clan shinobi assassin cavalryman mounted on a swift black warhorse with muffled hooves, wearing dark midnight indigo and obsidian shinobi robes with concealed light chainmail armor, menacing black mempo demon half-mask, drawing a razor-sharp steel ninjato blade from his back in mid-stride, smoke bomb canister releasing purple-tinted mist around the horse's legs, subtle Tokugawa triple-hollyhock crest (Mitsuba Aoi) embroidered on his dark sash, bursting out from a dense misty bamboo forest in a surprise night ambush, full moon shining through bamboo stalks casting dramatic moonlight shafts and deep shadows, flying bamboo leaves, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution</em>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dynamic cinematic illustration of a stealth Tokugawa clan shinobi assassin cavalryman mounted on a swift black warhorse with muffled hooves, wearing dark midnight indigo and obsidian shinobi robes with concealed light chainmail armor, menacing black mempo demon half-mask, drawing a razor-sharp steel ninjato blade from his back in mid-stride, smoke bomb canister releasing purple-tinted mist around the horse's legs, subtle Tokugawa triple-hollyhock crest (Mitsuba Aoi) embroidered on his dark sash, bursting out from a dense misty bamboo forest in a surprise night ambush, full moon shining through bamboo stalks casting dramatic moonlight shafts and deep shadows, flying bamboo leaves, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODULE 7 : ATELIERS & DÉFIS PRATIQUES PÈRE-FILS                           -->
<!-- ========================================================================= -->
<div class="peda-module-pane" id="peda-pane-ateliers" style="display: none;">
    <div class="card" style="margin-bottom: 2rem; border-color: rgba(251, 146, 60, 0.3);">
        <div class="card-header" style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.3rem;">🚀</span>
            <div>
                <h3 style="margin: 0; color: #fb923c; font-size: 1.15rem;">Module 7 : Les 4 Défis Pratiques à Programmer Ensemble</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);">Expérimentations guidées pour modifier le jeu et observer le résultat en direct</span>
            </div>
        </div>
        <div class="card-body" style="line-height: 1.6; font-size: 0.9rem; color: #cbd5e1;">
            <p>
                La meilleure façon d'apprendre la programmation, c'est de tester, de modifier de petites valeurs, et de voir ce qui se passe à l'écran ! Voici 4 ateliers passionnants à réaliser à quatre mains :
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-top: 1.25rem;">
                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #facc15;">
                    <h4 style="color: #facc15; margin: 0 0 0.5rem 0; font-size: 0.95rem;">🎯 Défi 1 : La Vitesse Lumière</h4>
                    <p style="font-size: 0.83rem; color: #94a3b8; margin: 0 0 0.75rem 0;">
                        Allez dans l'onglet <strong>⚡ Vitesses & Jeu</strong> juste à côté. Passez la vitesse à <code>x10</code> ou <code>x20</code> !
                    </p>
                    <div style="font-size: 0.8rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 6px;">
                        <strong>Ce qu'on apprend :</strong> Comment une seule variable dans un fichier de configuration (<code>GameConfig.php</code>) modifie tout le rythme du jeu !
                    </div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #38bdf8;">
                    <h4 style="color: #38bdf8; margin: 0 0 0.5rem 0; font-size: 0.95rem;">🎯 Défi 2 : Changer les Stats d'une Troupe</h4>
                    <p style="font-size: 0.83rem; color: #94a3b8; margin: 0 0 0.75rem 0;">
                        Ouvrez la table SQL <code>units</code> ou le fichier <code>config/game_constants.php</code>. Donnez 999 d'attaque au <em>Piquier Ashigaru</em> !
                    </p>
                    <div style="font-size: 0.8rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 6px;">
                        <strong>Ce qu'on apprend :</strong> L'équilibrage de jeu (Game Balance) : pourquoi les unités trop fortes cassent le plaisir de jouer.
                    </div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #f472b6;">
                    <h4 style="color: #f472b6; margin: 0 0 0.5rem 0; font-size: 0.95rem;">🎯 Défi 3 : Générer un Nouveau Monstre</h4>
                    <p style="font-size: 0.83rem; color: #94a3b8; margin: 0 0 0.75rem 0;">
                        Inventez un prompt pour un <em>Tigre d'Or Sacré</em> en suivant la formule magique du Module 6, et sauvegardez l'image dans <code>public/assets/units/</code>.
                    </p>
                    <div style="font-size: 0.8rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 6px;">
                        <strong>Ce qu'on apprend :</strong> La direction artistique et la chaîne d'intégration d'un asset visuel dans le code.
                    </div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; border-top: 4px solid #4ade80;">
                    <h4 style="color: #4ade80; margin: 0 0 0.5rem 0; font-size: 0.95rem;">🎯 Défi 4 : Lancer les Robots Testeurs</h4>
                    <p style="font-size: 0.83rem; color: #94a3b8; margin: 0 0 0.75rem 0;">
                        Dans la console, tapez <code>php tests/test_demolish_building.php</code> et observez les feux verts <code>✔</code> s'allumer un à un.
                    </p>
                    <div style="font-size: 0.8rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 6px;">
                        <strong>Ce qu'on apprend :</strong> Les tests unitaires et la rigueur des ingénieurs pour garantir un jeu sans bugs !
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Navigation interactive entre les modules pédagogiques
function switchPedaModule(modKey) {
    document.querySelectorAll('.peda-module-pane').forEach(p => {
        p.style.display = 'none';
    });
    const target = document.getElementById('peda-pane-' + modKey);
    if (target) {
        target.style.display = 'block';
    }

    document.querySelectorAll('.peda-nav-btn').forEach(b => {
        b.classList.remove('active');
        b.style.borderColor = 'rgba(255,255,255,0.1)';
        b.style.background = 'rgba(255,255,255,0.04)';
        b.style.color = '#cbd5e1';
    });

    const activeBtn = document.getElementById('btn-peda-' + modKey);
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.borderColor = '#06b6d4';
        activeBtn.style.background = 'linear-gradient(135deg, #0891b2, #06b6d4)';
        activeBtn.style.color = '#fff';
    }
}

// Fonction de copie rapide des prompts
function copyPromptText(btn) {
    const text = btn.getAttribute('data-prompt');
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerText;
        btn.innerText = 'Copié ! ✨';
        btn.style.background = '#059669';
        btn.style.color = '#fff';
        setTimeout(() => {
            btn.innerText = orig;
            btn.style.background = '';
            btn.style.color = '';
        }, 1800);
    }).catch(err => {
        alert('Prompt copié : ' + text);
    });
}
</script>

