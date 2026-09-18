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
            <span class="badge" style="background: rgba(244, 114, 182, 0.2); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.4); font-size: 0.75rem;">58 Assets Documentés</span>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Vue aérienne en plongée RTS d'une ville fortifiée japonaise féodale, période Sengoku, centrée sur un donjon Tenshu colossal en pierre, cours intérieures, dojo, ruelles marchandes, brume matinale, cerisiers en fleurs, arrière-plan de jeu de stratégie isométrique détaillé.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Paysage stratégique de ressources en vue du dessus (RTS) du Japon féodal rural, rizières en terrasses inondées reflétant le ciel bleu, forêts denses de bambous et de cèdres, falaises de carrières de pierre, sanctuaire de montagne sacré avec rivière, plateau de jeu tactique miniature.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Sprite isométrique isolé d'un monumental donjon de château japonais à plusieurs étages (Tenshu), bois laqué noir, murs de plâtre blanc, toits courbés en tuiles vertes, ornements dorés, haute base en fondation de pierre, fond transparent.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Tuile de jeu isolée d'une rizière en terrasses inondée d'un vert luxuriant, canaux d'irrigation et vannes en bois propre, eau claire réfléchissante, jeunes pousses vert vif, fond transparent.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Tuile de jeu isolée d'un camp de bûcherons japonais, rondins de cèdre coupés et empilés soigneusement, abri rustique en bois avec scies et haches, sol couvert de sciure, fond transparent.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Maître épéiste samouraï en posture de frappe dégainant sa lame d'acier katana tranchante, cordons de soie pourpre et noire sur armure lamellaire, regard d'une intensité féroce, braises incandescentes flottantes, illustration numérique style ukiyo-e.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Illustration cinématographique d'un agile cavalier éclaireur samouraï Takeda, monté sur un cheval de montagne japonais rapide (cheval Kiso), armure légère laquée rouge écarlate, jingasa cornu, lance yari, bannière dorsale aux 4 losanges Takeda, crête rocheuse dominant les vallées à l'aube, art ukiyo-e.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Mousquetaire du clan Oda visant avec une arquebuse Tanegashima en bois, volutes de fumée au canon, barricade de boucliers en bambou tate au premier plan, mèche incandescente, pose dynamique d'action de bataille.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Portrait héroïque d'un général daimyō Sengoku en armure d'apparat noire et ornée, casque kabuto cornu, tenant un katana ancestral, pétales de cerisier soufflés par le vent, lumière dramatique et majestueuse.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Bélier colossal en tête de dragon rugissant de fer et bronze crachant feu et fumée, lourd charriot blindé en poutres de cèdre et cuir humide, roues géantes hérissées de pointes dans la boue, bannières rouges Takeda, avançant vers la porte de la forteresse au crépuscule sous une pluie de flèches enflammées, style ukiyo-e 8k.</em>
                                </div>
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
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Cavalier assassin shinobi furtif Tokugawa sur destrier noir aux sabots assourdis, tenue indigo et masque démon mempo, dégainant un ninjato acéré dans une fumée violette, surgissant d'une forêt de bambous embrumée en embuscade nocturne au clair de lune, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dynamic cinematic illustration of a stealth Tokugawa clan shinobi assassin cavalryman mounted on a swift black warhorse with muffled hooves, wearing dark midnight indigo and obsidian shinobi robes with concealed light chainmail armor, menacing black mempo demon half-mask, drawing a razor-sharp steel ninjato blade from his back in mid-stride, smoke bomb canister releasing purple-tinted mist around the horse's legs, subtle Tokugawa triple-hollyhock crest (Mitsuba Aoi) embroidered on his dark sash, bursting out from a dense misty bamboo forest in a surprise night ambush, full moon shining through bamboo stalks casting dramatic moonlight shafts and deep shadows, flying bamboo leaves, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- CATAPULTE FLAMBOYANTE HOROKUBIYA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $catapultFile = __DIR__ . '/../../public/assets/units/catapulte_horokubiya_tokugawa.jpg'; ?>
                                <img src="/public/assets/units/catapulte_horokubiya_tokugawa.jpg?v=<?= file_exists($catapultFile) ? filemtime($catapultFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Catapulte Flamboyante Horokubiya</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Artillerie Incendiaire (Clan Tokugawa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                catapulte_horokubiya_tokugawa.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Monumental ancient Japanese siege catapult traction trebuchet hurling glowing fiery ceramic explosive jars (Horokubiya), heavy fortified timber frame built of thick cypress beams with blackened iron fittings and counterweights, wooden launching arm in mid-motion releasing a blazing ceramic firepot trailing golden sparks and dark smoke across the twilight sky, Tokugawa clan ashigaru siege engineers in indigo armor operating tension ropes and torches, protective bamboo tate pavise mantlets in foreground bearing the Tokugawa three-hollyhock crest (Mitsuba Aoi), distant besieged Japanese castle keep on fire, embers and smoke drifting in the wind, dramatic cinematic low-angle action shot, warm fire glow and volumetric dusk lighting, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Trébuchet de siège monumental propulsant des bombes incendiaires explosives en céramique (Horokubiya) dans le ciel crépusculaire, ingénieurs ashigaru Tokugawa manœuvrant les cordes et torches, pavois en bambou au blason Tokugawa, forteresse en flammes au loin, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Monumental ancient Japanese siege catapult traction trebuchet hurling glowing fiery ceramic explosive jars (Horokubiya), heavy fortified timber frame built of thick cypress beams with blackened iron fittings and counterweights, wooden launching arm in mid-motion releasing a blazing ceramic firepot trailing golden sparks and dark smoke across the twilight sky, Tokugawa clan ashigaru siege engineers in indigo armor operating tension ropes and torches, protective bamboo tate pavise mantlets in foreground bearing the Tokugawa three-hollyhock crest (Mitsuba Aoi), distant besieged Japanese castle keep on fire, embers and smoke drifting in the wind, dramatic cinematic low-angle action shot, warm fire glow and volumetric dusk lighting, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- FORTERESSE ROULANTE BLINDÉE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $fortressFile = __DIR__ . '/../../public/assets/units/forteresse_roulante_tokugawa.jpg'; ?>
                                <img src="/public/assets/units/forteresse_roulante_tokugawa.jpg?v=<?= file_exists($fortressFile) ? filemtime($fortressFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Forteresse Roulante Blindée</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Bastion Mobile (Clan Tokugawa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                forteresse_roulante_tokugawa.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Monumental ancient Japanese rolling armored siege fortress, colossal multi-tiered wooden mobile bastion on massive iron-studded timber wheels, heavily armored walls built of thick oak logs reinforced with bolted iron and bronze plates, multiple narrow arrow slits and triangular gun ports with Tanegashima matchlocks and yari spears protruding, curved Japanese pagoda-style tiled roof with defensive parapet, fluttering deep purple and gold war banners bearing the Tokugawa clan triple-hollyhock crest (Mitsuba Aoi), samurai commanders in ornate black and gold armor directing the advance from the upper watchtower, rolling relentlessly across a muddy battlefield toward besieged enemy fortifications at sunset, flaming enemy arrows harmlessly deflecting off the heavy iron plating, dramatic low-angle perspective emphasizing its colossal size and invulnerability, smoke plumes and golden dust in the air, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Bastion mobile géant à plusieurs étages sur roues cloutées de fer, parois de chêne blindées de plaques de fer et bronze, sabords de tir d'arquebuses et meurtrières de lances, bannières pourpres Tokugawa, commandants samouraïs au sommet, flèches enflammées ricochant, ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Monumental ancient Japanese rolling armored siege fortress, colossal multi-tiered wooden mobile bastion on massive iron-studded timber wheels, heavily armored walls built of thick oak logs reinforced with bolted iron and bronze plates, multiple narrow arrow slits and triangular gun ports with Tanegashima matchlocks and yari spears protruding, curved Japanese pagoda-style tiled roof with defensive parapet, fluttering deep purple and gold war banners bearing the Tokugawa clan triple-hollyhock crest (Mitsuba Aoi), samurai commanders in ornate black and gold armor directing the advance from the upper watchtower, rolling relentlessly across a muddy battlefield toward besieged enemy fortifications at sunset, flaming enemy arrows harmlessly deflecting off the heavy iron plating, dramatic low-angle perspective emphasizing its colossal size and invulnerability, smoke plumes and golden dust in the air, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ============================================================== -->
                        <!-- SÉPARATEUR : BÊTES SAUVAGES DES OASIS                          -->
                        <!-- ============================================================== -->
                        <tr style="background: rgba(180, 83, 9, 0.15); border-top: 2px solid #b45309; border-bottom: 2px solid #b45309;">
                            <td colspan="5" style="padding: 0.6rem 0.75rem; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem;">
                                🐾 Faune Hostile & Bêtes Sauvages des Oasis (Système d'Annexions)
                            </td>
                        </tr>

                        <!-- SANGLIER ENRAGÉ DES MONTS -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $boarFile = __DIR__ . '/../../public/assets/units/sanglier_sauvage.jpg'; ?>
                                <img src="/public/assets/units/sanglier_sauvage.jpg?v=<?= file_exists($boarFile) ? filemtime($boarFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Sanglier Enragé des Monts</strong><br>
                                <span style="font-size: 0.75rem; color: #f59e0b;">🐗 Bête Sauvage Tier 1 (山猪)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                sanglier_sauvage.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dynamic cinematic illustration of a colossal feral wild boar (Sanglier Enragé des Monts) charging furiously through a misty ancient Japanese mountain forest, razor-sharp elongated tusks dripping with foam, bristling dark bristly fur covered in dirt and pine needles, furious glowing amber-red eyes, muscular hunched posture, kicking up volcanic gravel and snapping bamboo under heavy hooves, full moon peeking through cedar trees casting cold moonlight and deep atmospheric shadows, subtle Japanese feudal aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, dramatic lighting, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Illustration cinématographique d'un sanglier sauvage colossal chargeant furieusement dans une forêt de montagne japonaise embrumée, défenses acérées dégoulinantes d'écume, pelage sombre hérissé, yeux ambrés flamboyants, brisant des bambous sous ses sabots, pleine lune à travers les cèdres, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dynamic cinematic illustration of a colossal feral wild boar (Sanglier Enragé des Monts) charging furiously through a misty ancient Japanese mountain forest, razor-sharp elongated tusks dripping with foam, bristling dark bristly fur covered in dirt and pine needles, furious glowing amber-red eyes, muscular hunched posture, kicking up volcanic gravel and snapping bamboo under heavy hooves, full moon peeking through cedar trees casting cold moonlight and deep atmospheric shadows, subtle Japanese feudal aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, dramatic lighting, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- LOUP VICIEUX DE HONSHU -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $wolfFile = __DIR__ . '/../../public/assets/units/loup_honshu.jpg'; ?>
                                <img src="/public/assets/units/loup_honshu.jpg?v=<?= file_exists($wolfFile) ? filemtime($wolfFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Loup Vicieux de Honshu</strong><br>
                                <span style="font-size: 0.75rem; color: #38bdf8;">🐺 Meute Sauvage Tier 2 (本州狼)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                loup_honshu.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Menacing pack of vicious Honshu wolves emerging from dense misty bamboo forest under cold full moon, alpha male in foreground with bared fangs and intense predatory yellow eyes, lean muscular posture, breath visible in freezing air, ancient Shinto stone lanterns covered in moss in background, traditional Japanese feudal mountain wilderness, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric dark rim lighting, highly detailed, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Meute menaçante de loups vicieux de Honshu émergeant d'une dense forêt de bambous brumeuse sous une pleine lune glaciale, mâle alpha au premier plan crocs découverts et yeux jaunes de prédateur, vapeur de souffle dans l'air gelé, lanternes de pierre shinto moussues, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Menacing pack of vicious Honshu wolves emerging from dense misty bamboo forest under cold full moon, alpha male in foreground with bared fangs and intense predatory yellow eyes, lean muscular posture, breath visible in freezing air, ancient Shinto stone lanterns covered in moss in background, traditional Japanese feudal mountain wilderness, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric dark rim lighting, highly detailed, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- GRAND OURS BRUN DE HOKKAIDO -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bearFile = __DIR__ . '/../../public/assets/units/ours_hokkaido.jpg'; ?>
                                <img src="/public/assets/units/ours_hokkaido.jpg?v=<?= file_exists($bearFile) ? filemtime($bearFile) : 1 ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Grand Ours Brun de Hokkaido</strong><br>
                                <span style="font-size: 0.75rem; color: #ef4444;">🐻 Colosse d'Oasis Tier 3 (北海道羆)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                ours_hokkaido.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dynamic cinematic illustration of a colossal feral Hokkaido brown bear (Grand Ours Brun de Hokkaido, legendary giant Higuma), towering apex predator rising on its hind legs atop a jagged snow-covered mountain cliff, massive muscular frame with battle scars across its chest and snout, thick frosted dark-brown fur matted with ice and snow, roaring furiously with razor-sharp elongated claws slashing through the freezing air, steaming breath billowing from open jaws lined with lethal teeth, fierce glowing amber eyes, background featuring rugged northern Japanese Hokkaido peaks (Ezo), wind-swept frozen ancient pine trees and swirling snow blizzard under a dramatic overcast winter twilight sky, Japanese feudal folklore aesthetic, ukiyo-e woodblock inspired semi-realistic digital painting, heavy textural detail, epic sense of scale and raw primal power, dramatic rim lighting, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Illustration cinématographique d'un gigantesque ours brun de Hokkaido (Higuma), dressé sur une falaise enneigée, cicatrices de guerre sur le poitrail, fourrure givrée, rugissement furieux dans le blizzard des sommets d'Ezo, griffes acérées et vapeur d'expiration, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dynamic cinematic illustration of a colossal feral Hokkaido brown bear (Grand Ours Brun de Hokkaido, legendary giant Higuma), towering apex predator rising on its hind legs atop a jagged snow-covered mountain cliff, massive muscular frame with battle scars across its chest and snout, thick frosted dark-brown fur matted with ice and snow, roaring furiously with razor-sharp elongated claws slashing through the freezing air, steaming breath billowing from open jaws lined with lethal teeth, fierce glowing amber eyes, background featuring rugged northern Japanese Hokkaido peaks (Ezo), wind-swept frozen ancient pine trees and swirling snow blizzard under a dramatic overcast winter twilight sky, Japanese feudal folklore aesthetic, ukiyo-e woodblock inspired semi-realistic digital painting, heavy textural detail, epic sense of scale and raw primal power, dramatic rim lighting, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ============================================================== -->
                        <!-- SÉPARATEUR : LES 12 CHÂTEAUX AUTHENTIQUES DU JAPON             -->
                        <!-- ============================================================== -->
                        <tr style="background: rgba(220, 38, 38, 0.15); border-top: 2px solid #b91c1c; border-bottom: 2px solid #b91c1c;">
                            <td colspan="5" style="padding: 0.6rem 0.75rem; font-weight: 800; color: #fca5a5; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.8rem;">
                                🏯 Les 12 Donjons Authentiques Préservés du Japon (現存十二天守 - Jūni Tenshu)
                            </td>
                        </tr>

                        <!-- BITCHU MATSUYAMA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/bitchu_matsuyama.jpg'; ?>
                                <img src="/public/assets/castles/bitchu_matsuyama.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Bitchū Matsuyama</strong><br>
                                <span style="font-size: 0.75rem; color: #fbbf24;">備中松山城 (Château dans le Ciel &bull; 430m)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                bitchu_matsuyama.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic epic illustration of Bitchu Matsuyama Castle, the legendary Japanese castle in the sky, perched atop the rugged crags of Mount Gagyu at 430 meters altitude, authentic two-story white plaster and cypress wood tenshu keep standing majestically above a dramatic sea of rolling morning clouds (unkai), ancient dry-stone retaining walls seamlessly built into natural granite cliffs, autumn foliage with scarlet momiji maple leaves clinging to rocky outcrops, warm golden sunrise breaking through misty horizon casting ethereal glows on white battlements, Japanese feudal Sengoku aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, breathtaking aerial view, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Donjon de Bitchū Matsuyama perché à 430 m sur les falaises du mont Gagyū au-dessus d'une mer de nuages d'automne au lever de soleil, érables rouges et remparts granitiques, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic epic illustration of Bitchu Matsuyama Castle, the legendary Japanese castle in the sky, perched atop the rugged crags of Mount Gagyu at 430 meters altitude, authentic two-story white plaster and cypress wood tenshu keep standing majestically above a dramatic sea of rolling morning clouds (unkai), ancient dry-stone retaining walls seamlessly built into natural granite cliffs, autumn foliage with scarlet momiji maple leaves clinging to rocky outcrops, warm golden sunrise breaking through misty horizon casting ethereal glows on white battlements, Japanese feudal Sengoku aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, breathtaking aerial view, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- HIKONE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/hikone.jpg'; ?>
                                <img src="/public/assets/castles/hikone.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Hikone</strong><br>
                                <span style="font-size: 0.75rem; color: #f87171;">彦根城 (Fief des Diables Rouges Ii &bull; Lac Biwa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                hikone.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic majestic illustration of Hikone Castle, National Treasure of Japan, iconic three-story tenshu keep showcasing intricate gabled roofs combining curved karahafu and triangular irimoya-hafu architecture, white plaster walls adorned with golden crests, standing on a fortified hill overlooking the shimmering waters of Lake Biwa, red lacquered war banners (mon) of the Ii Clan 'Red Devils' fluttering in the breeze, historic stone bastions, spring cherry blossoms softly framing the fortress under a serene morning sky, subtle ukiyo-e woodblock inspired semi-realistic digital painting, highly detailed Japanese feudal craftsmanship, 8k</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Donjon de Hikone aux toitures ouvragées karahafu et irimoya-hafu, bannières rouges des Diables Rouges du clan Ii, surplombant le lac Biwa avec cerisiers en fleurs.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic majestic illustration of Hikone Castle, National Treasure of Japan, iconic three-story tenshu keep showcasing intricate gabled roofs combining curved karahafu and triangular irimoya-hafu architecture, white plaster walls adorned with golden crests, standing on a fortified hill overlooking the shimmering waters of Lake Biwa, red lacquered war banners (mon) of the Ii Clan 'Red Devils' fluttering in the breeze, historic stone bastions, spring cherry blossoms softly framing the fortress under a serene morning sky, subtle ukiyo-e woodblock inspired semi-realistic digital painting, highly detailed Japanese feudal craftsmanship, 8k" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- HIMEJI -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/himeji.jpg'; ?>
                                <img src="/public/assets/castles/himeji.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Himeji</strong><br>
                                <span style="font-size: 0.75rem; color: #e2e8f0;">姫路城 (Le Héron Blanc &bull; Shirasagi-jō)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                himeji.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Grand panoramic cinematic illustration of Himeji Castle, the magnificent White Heron Castle (Shirasagi-jo), colossal five-tiered seven-story main tenshu keep linked by fortified covered galleries to three sub-towers (renritsu-shiki style), brilliant white fireproof plaster walls gleaming brilliantly like the wings of a giant white heron taking flight, complex labyrinth of stone ramparts, curved samurai gates and triangular arrow slits, misty twilight sky with soft pastel sunset tones, Japanese feudal fortress masterpiece, ukiyo-e woodblock influenced high-end semi-realistic digital art, stunning architectural detail, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Panoramique du château de Himeji, le Héron Blanc immaculé à 5 niveaux relié à 3 donjons secondaires, labyrinthe de remparts de pierre blanche et ciel crépusculaire pastel.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Grand panoramic cinematic illustration of Himeji Castle, the magnificent White Heron Castle (Shirasagi-jo), colossal five-tiered seven-story main tenshu keep linked by fortified covered galleries to three sub-towers (renritsu-shiki style), brilliant white fireproof plaster walls gleaming brilliantly like the wings of a giant white heron taking flight, complex labyrinth of stone ramparts, curved samurai gates and triangular arrow slits, misty twilight sky with soft pastel sunset tones, Japanese feudal fortress masterpiece, ukiyo-e woodblock influenced high-end semi-realistic digital art, stunning architectural detail, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- HIROSAKI -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/hirosaki.jpg'; ?>
                                <img src="/public/assets/castles/hirosaki.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Hirosaki</strong><br>
                                <span style="font-size: 0.75rem; color: #38bdf8;">弘前城 (Donjon Boréal de Mutsu &bull; Mont Iwaki)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                hirosaki.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Atmospheric winter cinematic illustration of Hirosaki Castle, the northernmost original tenshu keep of Mutsu province, compact three-story wooden fortress dusted with crisp white snow on dark curved roof tiles, surrounded by frozen triple moats with cracked turquoise ice and snow-laden weeping pine trees, dramatic view of snow-capped volcanic peak Mount Iwaki rising in the background under cold winter daylight, subtle traditional red arched wooden bridge spanning the snowy moat, Japanese feudal northern aesthetic, ukiyo-e woodblock inspired semi-realistic art, crisp atmospheric chill, 8k</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Hirosaki sous la neige boréale, donjon poudré de givre, douves gelées à la glace turquoise, pont vermillon et pic volcanique enneigé du mont Iwaki.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Atmospheric winter cinematic illustration of Hirosaki Castle, the northernmost original tenshu keep of Mutsu province, compact three-story wooden fortress dusted with crisp white snow on dark curved roof tiles, surrounded by frozen triple moats with cracked turquoise ice and snow-laden weeping pine trees, dramatic view of snow-capped volcanic peak Mount Iwaki rising in the background under cold winter daylight, subtle traditional red arched wooden bridge spanning the snowy moat, Japanese feudal northern aesthetic, ukiyo-e woodblock inspired semi-realistic art, crisp atmospheric chill, 8k" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- INUYAMA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/inuyama.jpg'; ?>
                                <img src="/public/assets/castles/inuyama.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château d'Inuyama</strong><br>
                                <span style="font-size: 0.75rem; color: #fbbf24;">犬山城 (Plus Ancien Donjon Bois 1537 &bull; Kiso-gawa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                inuyama.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dramatic cinematic illustration of Inuyama Castle, the oldest standing original wooden tenshu in Japan dating back to 1537, perched atop a sheer 40-meter rocky promontory directly towering over the swirling emerald rapids of the Kiso River, dark aged timber construction and traditional white walls, top-floor open-air wooden observation balcony (mawari-en) with panoramic views, mist rising from rushing river waters, flight of cormorants skimming the river surface, Oda clan feudal war banners, ukiyo-e woodblock inspired semi-realistic digital painting, dramatic low-angle composition, 8k</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Inuyama dressé sur son éperon rocheux de 40 m au-dessus des rapides de la rivière Kiso, boiseries d'origine du XVIe siècle, balcon panoramique ouvert et pêcheurs aux cormorans.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dramatic cinematic illustration of Inuyama Castle, the oldest standing original wooden tenshu in Japan dating back to 1537, perched atop a sheer 40-meter rocky promontory directly towering over the swirling emerald rapids of the Kiso River, dark aged timber construction and traditional white walls, top-floor open-air wooden observation balcony (mawari-en) with panoramic views, mist rising from rushing river waters, flight of cormorants skimming the river surface, Oda clan feudal war banners, ukiyo-e woodblock inspired semi-realistic digital painting, dramatic low-angle composition, 8k" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- KOCHI -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/kochi.jpg'; ?>
                                <img src="/public/assets/castles/kochi.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Kōchi</strong><br>
                                <span style="font-size: 0.75rem; color: #38bdf8;">高知城 (Palais Honmaru Goten Intact &bull; Tosa)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                kochi.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dramatic cinematic illustration of Kochi Castle in Tosa province, iconic historic complex uniquely featuring both the towering original tenshu keep and the preserved Honmaru Goten samurai residential palace, steep stone base fitted with curved iron anti-ninja climbing spikes (shinobi-gaeshi) and monumental stone water drainage gargoyles (mizu-kiri), dramatic stormy Pacific typhoon clouds brewing above, moody atmospheric lighting with wind whipping through subtropical sago cycad palms and ancient pine trees, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Kōchi avec son donjon et son palais seigneurial Honmaru Goten préservés, piques anti-shinobi sur les murailles et gargouilles de pierre sous un ciel d'orage de typhon du Pacifique.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dramatic cinematic illustration of Kochi Castle in Tosa province, iconic historic complex uniquely featuring both the towering original tenshu keep and the preserved Honmaru Goten samurai residential palace, steep stone base fitted with curved iron anti-ninja climbing spikes (shinobi-gaeshi) and monumental stone water drainage gargoyles (mizu-kiri), dramatic stormy Pacific typhoon clouds brewing above, moody atmospheric lighting with wind whipping through subtropical sago cycad palms and ancient pine trees, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MARUGAME -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/marugame.jpg'; ?>
                                <img src="/public/assets/castles/marugame.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Marugame</strong><br>
                                <span style="font-size: 0.75rem; color: #fbbf24;">丸亀城 (Murailles en Éventail de 60m &bull; Seto)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                marugame.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Epic wide-angle cinematic illustration of Marugame Castle, renowned for the tallest stone walls in Japan soaring over 60 meters in four sweeping stepped terraces, distinctive fan-sloped stone curvature (ogi-no-kobai) rising dramatically from the base to sheer vertical summits, compact white tenshu keep perched at the pinnacle, overlooking the tranquil sparkling Seto Inland Sea dotted with green island archipelagoes, golden hour sunlight hitting the weathered cut-stone masonry, ukiyo-e woodblock blended with epic cinematic realism, magnificent scale, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Marugame et ses 4 terrasses colossales de murailles de pierre de 60 mètres en courbure d'éventail (ōgi-no-kōbai), surmonté de son donjon blanc face aux îles de la mer intérieure de Seto.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Epic wide-angle cinematic illustration of Marugame Castle, renowned for the tallest stone walls in Japan soaring over 60 meters in four sweeping stepped terraces, distinctive fan-sloped stone curvature (ogi-no-kobai) rising dramatically from the base to sheer vertical summits, compact white tenshu keep perched at the pinnacle, overlooking the tranquil sparkling Seto Inland Sea dotted with green island archipelagoes, golden hour sunlight hitting the weathered cut-stone masonry, ukiyo-e woodblock blended with epic cinematic realism, magnificent scale, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MARUOKA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/maruoka.jpg'; ?>
                                <img src="/public/assets/castles/maruoka.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Maruoka</strong><br>
                                <span style="font-size: 0.75rem; color: #c084fc;">丸岡城 (Château de la Brume &bull; Tuiles de Pierre)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                maruoka.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Mystical cinematic illustration of Maruoka Castle, the legendary 'Mist Castle' (Kasumi-ga-jo) in Echizen, archaic 16th-century feudal tenshu with steep rustic stone foundations, unique heavy roof crafted entirely from 6,000 blue-gray volcanic stone tiles (shakudani-ishi), mystical swirling dense white fog billowing around the fortress evoking the legendary giant serpent's protective mist, ancient lanterns glowing through the fog, eerie Sengoku-period atmosphere, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric chiaroscuro, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Maruoka enveloppé de brume mystique (la légende du serpent géant protecteur), toiture archaïque de 6 000 tuiles en pierre volcanique d'Asuwa et fondations rocheuses.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Mystical cinematic illustration of Maruoka Castle, the legendary 'Mist Castle' (Kasumi-ga-jo) in Echizen, archaic 16th-century feudal tenshu with steep rustic stone foundations, unique heavy roof crafted entirely from 6,000 blue-gray volcanic stone tiles (shakudani-ishi), mystical swirling dense white fog billowing around the fortress evoking the legendary giant serpent's protective mist, ancient lanterns glowing through the fog, eerie Sengoku-period atmosphere, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric chiaroscuro, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MATSUE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/matsue.jpg'; ?>
                                <img src="/public/assets/castles/matsue.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Matsue</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">松江城 (Le Château du Pluvier &bull; Bois Noirci)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                matsue.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic noble illustration of Matsue Castle, the 'Plover Castle' (Chidori-jo), striking five-tiered black fortress cladded in dark soot-treated wooden rainboards (tsumi-ita), National Treasure of Izumo province standing on a green hill overlooking the vast waters of Lake Shinji, tranquil castle moats navigable by wooden samurai boats, flock of plover birds soaring across a moody silver twilight sky, ancient stone bridges and weeping willows, ukiyo-e woodblock inspired semi-realistic digital art, rich deep black and wood textures, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Matsue, forteresse noire austère bardée de bois traité au charbon, Trésor National sur la colline d'Izumo au bord du lac Shinji avec volée d'oiseaux pluviers au crépuscule.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic noble illustration of Matsue Castle, the 'Plover Castle' (Chidori-jo), striking five-tiered black fortress cladded in dark soot-treated wooden rainboards (tsumi-ita), National Treasure of Izumo province standing on a green hill overlooking the vast waters of Lake Shinji, tranquil castle moats navigable by wooden samurai boats, flock of plover birds soaring across a moody silver twilight sky, ancient stone bridges and weeping willows, ukiyo-e woodblock inspired semi-realistic digital art, rich deep black and wood textures, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MATSUMOTO -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/matsumoto.jpg'; ?>
                                <img src="/public/assets/castles/matsumoto.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Matsumoto</strong><br>
                                <span style="font-size: 0.75rem; color: #f87171;">松本城 (Château du Corbeau &bull; Alpes Japonaises)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                matsumoto.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Masterpiece cinematic illustration of Matsumoto Castle, the world-famous 'Crow Castle' (Karasu-jo), five-tier six-story black lacquered tenshu keep accompanied by the vermilion-lacquered Moon-Viewing Turret (Tsukimi-yagura), perfect mirror reflection cast across wide crystalline moats with swimming red koi fish, towering snow-covered Northern Japanese Alps mountain range dominating the background, clear crisp morning atmosphere, flock of black crows gliding over the castle roofs, ukiyo-e woodblock aesthetic fused with stunning cinematic clarity, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Matsumoto « Château du Corbeau », donjon d'ébène noir laqué et pavillon vermillon d'observation de la lune se reflétant dans les douves aux carpes koï, face aux Alpes japonaises.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Masterpiece cinematic illustration of Matsumoto Castle, the world-famous 'Crow Castle' (Karasu-jo), five-tier six-story black lacquered tenshu keep accompanied by the vermilion-lacquered Moon-Viewing Turret (Tsukimi-yagura), perfect mirror reflection cast across wide crystalline moats with swimming red koi fish, towering snow-covered Northern Japanese Alps mountain range dominating the background, clear crisp morning atmosphere, flock of black crows gliding over the castle roofs, ukiyo-e woodblock aesthetic fused with stunning cinematic clarity, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MATSUYAMA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/matsuyama.jpg'; ?>
                                <img src="/public/assets/castles/matsuyama.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château de Matsuyama</strong><br>
                                <span style="font-size: 0.75rem; color: #4ade80;">松山城 (Complexe Renritsu-shiki &bull; Mont Katsuyama)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                matsuyama.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Grand panoramic cinematic illustration of Matsuyama Castle in Iyo province, sprawling hilltop fortress atop Mount Katsuyama at 132 meters, intricate interconnected complex (renritsu-shiki) linking the three-story tenshu to multiple defensive watchtowers and fortified corridor gates, sweeping multi-tiered white plaster walls and grey tile roofs overlooking the historic plain of Dogo and the distant sea, lush green pine forest below, warm sunny afternoon sky with drifting clouds, ukiyo-e woodblock inspired semi-realistic digital painting, expansive vista, 8k</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Matsuyama perché sur le mont Katsuyama (132 m), complexe relié de 21 tours de guet et portes fortifiées, murailles blanches dominant la plaine de Dōgo et la mer de Seto.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Grand panoramic cinematic illustration of Matsuyama Castle in Iyo province, sprawling hilltop fortress atop Mount Katsuyama at 132 meters, intricate interconnected complex (renritsu-shiki) linking the three-story tenshu to multiple defensive watchtowers and fortified corridor gates, sweeping multi-tiered white plaster walls and grey tile roofs overlooking the historic plain of Dogo and the distant sea, lush green pine forest below, warm sunny afternoon sky with drifting clouds, ukiyo-e woodblock inspired semi-realistic digital painting, expansive vista, 8k" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- UWAJIMA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $cFile = __DIR__ . '/../../public/assets/castles/uwajima.jpg'; ?>
                                <img src="/public/assets/castles/uwajima.jpg?v=<?= file_exists($cFile) ? filemtime($cFile) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Château d'Uwajima</strong><br>
                                <span style="font-size: 0.75rem; color: #f59e0b;">宇和島城 (Plan Pentagonal de Takatora &bull; Baie Côtière)</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                uwajima.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic coastal illustration of Uwajima Castle, masterwork of fortress genius Todo Takatora, elegant three-story tenshu with decorative curved shoin-style gables and dark wood accents, set within its ingenious secret pentagonal rampart layout designed to confuse besiegers, perched on a coastal hill directly overlooking the tranquil sapphire waters and fishing harbors of Uwajima Bay, seabirds gliding over the defensive walls, soft golden dusk light reflecting on the sea, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Uwajima conçu par Tōdō Takatora avec son enceinte secrète en pentagone irrégulier, donjon à 3 étages dominant la baie maritime et les ports d'Uwajima au crépuscule.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic coastal illustration of Uwajima Castle, masterwork of fortress genius Todo Takatora, elegant three-story tenshu with decorative curved shoin-style gables and dark wood accents, set within its ingenious secret pentagonal rampart layout designed to confuse besiegers, perched on a coastal hill directly overlooking the tranquil sapphire waters and fishing harbors of Uwajima Bay, seabirds gliding over the defensive walls, soft golden dusk light reflecting on the sea, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ============================================================== -->
                        <!-- SÉPARATEUR : LES 4 RESSOURCES FÉODALES & TERROIRS (DORF 1)     -->
                        <!-- ============================================================== -->
                        <tr style="background: rgba(22, 163, 74, 0.15); border-top: 2px solid rgba(22, 163, 74, 0.4); border-bottom: 2px solid rgba(22, 163, 74, 0.4);">
                            <td colspan="5" style="padding: 0.75rem 1rem; color: #4ade80; font-weight: 800; font-size: 0.9rem;">
                                🌾 Terroirs Ruraux & Ressources Majeures du Shogunat (4 Matières Premières &bull; Dorf 1)
                            </td>
                        </tr>

                        <!-- BOIS DE CÈDRE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $resWood = __DIR__ . '/../../public/assets/resources/ressource_bois_cedre.jpg'; ?>
                                <img src="/public/assets/resources/ressource_bois_cedre.jpg?v=<?= file_exists($resWood) ? filemtime($resWood) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Bois de Cèdre (Sugi)</strong><br>
                                <span style="font-size: 0.75rem; color: #22c55e;">Camp de Bûcherons &bull; Charpentes</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                ressource_bois_cedre.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic feudal Japanese still life illustration of freshly harvested noble cedar wood logs (sugi) in a mountain lumber camp, stacked giant fragrant timber logs with visible raw growth rings and golden wood fibers, traditional woodsman iron axes and two-man saws resting on wooden trestles, fine cedar sawdust sparkling in warm sunbeams filtering through ancient towering cypress trees, mountain mist in background, feudal Japan Sengoku period, warm earthy tones, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed wood grain texture, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Nature morte de rondins de cèdre noble dans un camp forestier de montagne, troncs géants empilés aux fibres dorées, haches et scies de bûcherons, sciure fine sous les rayons de soleil et brume, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic feudal Japanese still life illustration of freshly harvested noble cedar wood logs (sugi) in a mountain lumber camp, stacked giant fragrant timber logs with visible raw growth rings and golden wood fibers, traditional woodsman iron axes and two-man saws resting on wooden trestles, fine cedar sawdust sparkling in warm sunbeams filtering through ancient towering cypress trees, mountain mist in background, feudal Japan Sengoku period, warm earthy tones, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed wood grain texture, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- PIERRE DE TAILLE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $resStone = __DIR__ . '/../../public/assets/resources/ressource_pierre_taille.jpg'; ?>
                                <img src="/public/assets/resources/ressource_pierre_taille.jpg?v=<?= file_exists($resStone) ? filemtime($resStone) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Pierre de Taille (Granit)</strong><br>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Carrière de Granit &bull; Nozura-zumi</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                ressource_pierre_taille.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic atmospheric illustration of massive chiselled granite fortress stones and volcanic rock blocks in a feudal Japanese mountain quarry, master stonemasons chiselling colossal grey boulders with iron mallets and chisels, stacked heavy ashlar masonry blocks bound with thick hemp ropes on wooden skids ready for castle rampart construction, quarry dust catching dramatic side lighting, rocky cliffside at dusk, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, rugged mineral textures, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Blocs monumentaux de granit et roches volcaniques dans une carrière féodale, tailleurs de pierre burinant les rocs, lourdes pierres d'appareil cerclées de chanvre prêtes pour les murailles, lumière rasante au crépuscule, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic atmospheric illustration of massive chiselled granite fortress stones and volcanic rock blocks in a feudal Japanese mountain quarry, master stonemasons chiselling colossal grey boulders with iron mallets and chisels, stacked heavy ashlar masonry blocks bound with thick hemp ropes on wooden skids ready for castle rampart construction, quarry dust catching dramatic side lighting, rocky cliffside at dusk, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, rugged mineral textures, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- RIZ IMPÉRIAL -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $resRice = __DIR__ . '/../../public/assets/resources/ressource_riz_imperial.jpg'; ?>
                                <img src="/public/assets/resources/ressource_riz_imperial.jpg?v=<?= file_exists($resRice) ? filemtime($resRice) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Riz Impérial (Koku)</strong><br>
                                <span style="font-size: 0.75rem; color: #fbbf24;">Rizières Inondées &bull; Or Blanc</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                ressource_riz_imperial.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Rich cinematic feudal still life of harvested imperial rice, overflowing woven straw bales of polished golden rice grains (koku bales / komedawara), ornate black and gold lacquered masu measuring box filled with pristine white rice grains, bundles of dry golden wheat and rice stalks bound with red cords, warm golden hour sunlight reflecting off the harvest, rustic storehouse timber background, peaceful prosperity, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, vibrant golden tones, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Nature morte de riz impérial récolté, sacs de paille tressée débordant de grains dorés (balles komedawara en koku), boîte de mesure masu laquée d'or et noir, gerbes de riz sous la lumière dorée dans un grenier en bois, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Rich cinematic feudal still life of harvested imperial rice, overflowing woven straw bales of polished golden rice grains (koku bales / komedawara), ornate black and gold lacquered masu measuring box filled with pristine white rice grains, bundles of dry golden wheat and rice stalks bound with red cords, warm golden hour sunlight reflecting off the harvest, rustic storehouse timber background, peaceful prosperity, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, vibrant golden tones, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- FERVEUR SHINTO -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $resShinto = __DIR__ . '/../../public/assets/resources/ressource_ferveur_shinto.jpg'; ?>
                                <img src="/public/assets/resources/ressource_ferveur_shinto.jpg?v=<?= file_exists($resShinto) ? filemtime($resShinto) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Ferveur Divine (Shintō)</strong><br>
                                <span style="font-size: 0.75rem; color: #f43f5e;">Sanctuaire &bull; Sérénité Spirituelle</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                ressource_ferveur_shinto.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Mystical spiritual illustration of a sacred Shinto shrine grove at twilight, iconic vermilion red Torii gate hung with sacred braided rice straw rope (shimenawa) and white zigzag paper streamers (shide), gentle moss-covered stone lantern (ishidoro) glowing with a soft candlelight, bubbling crystal-clear sacred purification stream with swirling cherry blossom petals, ethereal golden fireflies floating in the dusk air, ancient cedar forest background, Sengoku period Japan, peaceful mystical lighting, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Bosquet de sanctuaire Shintō au crépuscule, porte Torii rouge vermillon ornée de corde sacrée shimenawa et bandelettes shide, lanterne de pierre moussue éclairée, ruisseau de purification et pétales de cerisier, lucioles dorées, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Mystical spiritual illustration of a sacred Shinto shrine grove at twilight, iconic vermilion red Torii gate hung with sacred braided rice straw rope (shimenawa) and white zigzag paper streamers (shide), gentle moss-covered stone lantern (ishidoro) glowing with a soft candlelight, bubbling crystal-clear sacred purification stream with swirling cherry blossom petals, ethereal golden fireflies floating in the dusk air, ancient cedar forest background, Sengoku period Japan, peaceful mystical lighting, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ============================================================== -->
                        <!-- SÉPARATEUR : LES 11 BÂTIMENTS DE LA CITÉ CASTRALE (DORF 2)     -->
                        <!-- ============================================================== -->
                        <tr style="background: rgba(37, 99, 235, 0.15); border-top: 2px solid rgba(37, 99, 235, 0.4); border-bottom: 2px solid rgba(37, 99, 235, 0.4);">
                            <td colspan="5" style="padding: 0.75rem 1rem; color: #60a5fa; font-weight: 800; font-size: 0.9rem;">
                                🏯 Bâtiments Majeurs de la Cité Castrale (11 Édifices Urbains &bull; Dorf 2)
                            </td>
                        </tr>

                        <!-- TENSHU -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bHq = __DIR__ . '/../../public/assets/buildings/building_tenshu.jpg'; ?>
                                <img src="/public/assets/buildings/building_tenshu.jpg?v=<?= file_exists($bHq) ? filemtime($bHq) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Tenshu (Donjon Castral)</strong><br>
                                <span style="font-size: 0.75rem; color: #f87171;">QG & Palais du Daimyō &bull; Slot 19</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_tenshu.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Majestic panoramic cinematic illustration of a monumental multi-tiered Japanese feudal castle keep (Tenshu), dramatic low-angle view showing the massive sloping dry-stone foundation wall (ishigaki), brilliant white plaster walls adorned with dark timber beams and curved grey tile roofs with golden dolphin roof ornaments (shachihoko), clan war banners fluttering proudly in the breeze, courtyard with blooming pink cherry blossom trees, golden sunrise breaking over mountain mist, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, epic composition, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Donjon castral monumental Tenshu à étages multiples sur ses murailles ishigaki, plâtre blanc immaculé, shachihoko dorés, bannières de clan et cerisiers en fleurs sous le soleil levant, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Majestic panoramic cinematic illustration of a monumental multi-tiered Japanese feudal castle keep (Tenshu), dramatic low-angle view showing the massive sloping dry-stone foundation wall (ishigaki), brilliant white plaster walls adorned with dark timber beams and curved grey tile roofs with golden dolphin roof ornaments (shachihoko), clan war banners fluttering proudly in the breeze, courtyard with blooming pink cherry blossom trees, golden sunrise breaking over mountain mist, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, epic composition, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- DOJO MILITAIRE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bBar = __DIR__ . '/../../public/assets/buildings/building_barracks.jpg'; ?>
                                <img src="/public/assets/buildings/building_barracks.jpg?v=<?= file_exists($bBar) ? filemtime($bBar) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Dojo Militaire & Caserne</strong><br>
                                <span style="font-size: 0.75rem; color: #3b82f6;">Entraînement des Troupes &bull; Slot 22</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_barracks.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Atmospheric cinematic illustration of an active feudal Japanese martial arts dojo and military training barracks, traditional wooden pavilion with sliding shoji doors opening onto a packed dirt training courtyard, wooden weapon racks holding katana swords, naginata and yari spears, straw targets pierced by arrows, ashigaru spearmen and samurai practicing forms, red and white battle standards, banners with clan mon crests, morning mist and sunbeams, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, dynamic warrior atmosphere, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Dojo militaire et caserne d'entraînement féodale, pavillon en bois ouvert sur la cour battue, râteliers de katanas, naginatas et piques yari, cibles en paille transpercées, guerriers à l'entraînement, brume matinale, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Atmospheric cinematic illustration of an active feudal Japanese martial arts dojo and military training barracks, traditional wooden pavilion with sliding shoji doors opening onto a packed dirt training courtyard, wooden weapon racks holding katana swords, naginata and yari spears, straw targets pierced by arrows, ashigaru spearmen and samurai practicing forms, red and white battle standards, banners with clan mon crests, morning mist and sunbeams, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, dynamic warrior atmosphere, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ATELIER DE SIÈGE & ÉCURIES -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bShip = __DIR__ . '/../../public/assets/buildings/building_shipyard.jpg'; ?>
                                <img src="/public/assets/buildings/building_shipyard.jpg?v=<?= file_exists($bShip) ? filemtime($bShip) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Atelier de Siège & Écuries</strong><br>
                                <span style="font-size: 0.75rem; color: #ef4444;">Cavalerie & Engins &bull; Slot 23</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_shipyard.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic illustration of a bustling feudal Japanese military siege workshop and cavalry stables, open timber stables sheltering muscular Japanese warhorses (Kiso horses) with braided manes and decorated saddles, blacksmiths and carpenters assembling heavy wooden siege rams and catapult frames bound with iron brackets and damp leather, wood shavings on the earthen ground, glowing forge furnace in background, war banners bearing horse crests, late afternoon warm sunlight, Sengoku period, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Atelier de siège et écuries militaires féodales, chevaux de guerre Kiso aux crinières tressées, forgerons montant béliers et catapultes blindés de fer et cuir, forge rougeoyante et bannières équestres, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic illustration of a bustling feudal Japanese military siege workshop and cavalry stables, open timber stables sheltering muscular Japanese warhorses (Kiso horses) with braided manes and decorated saddles, blacksmiths and carpenters assembling heavy wooden siege rams and catapult frames bound with iron brackets and damp leather, wood shavings on the earthen ground, glowing forge furnace in background, war banners bearing horse crests, late afternoon warm sunlight, Sengoku period, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ACADÉMIE & FORGE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bLab = __DIR__ . '/../../public/assets/buildings/building_research_lab.jpg'; ?>
                                <img src="/public/assets/buildings/building_research_lab.jpg?v=<?= file_exists($bLab) ? filemtime($bLab) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Académie des Savoirs & Forge</strong><br>
                                <span style="font-size: 0.75rem; color: #a855f7;">Recherche & Métallurgie &bull; Slot 25</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_research_lab.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Atmospheric cinematic interior of a feudal Japanese master scholar academy and sacred sword forge, on one side venerable scholars examining ancient strategic scrolls and calligraphic tactical maps by soft lantern light, on the other side a sacred Tatara furnace and master bladesmith in white robes folding incandescent glowing steel (tamahagane) over an anvil, showers of bright golden sparks flying in the air, sacred shimenawa ropes hanging above, Sengoku Jidai, dramatic chiaroscuro contrast between firelight and shadow, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Académie de lettrés et forge sacrée : érudits étudiant cartes tactiques et rouleaux d'un côté, maître forgeron battant l'acier incandescent tamahagane de l'autre dans une pluie d'étincelles dorées, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Atmospheric cinematic interior of a feudal Japanese master scholar academy and sacred sword forge, on one side venerable scholars examining ancient strategic scrolls and calligraphic tactical maps by soft lantern light, on the other side a sacred Tatara furnace and master bladesmith in white robes folding incandescent glowing steel (tamahagane) over an anvil, showers of bright golden sparks flying in the air, sacred shimenawa ropes hanging above, Sengoku Jidai, dramatic chiaroscuro contrast between firelight and shadow, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- TOUR DE GUET YAGURA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bRad = __DIR__ . '/../../public/assets/buildings/building_radar.jpg'; ?>
                                <img src="/public/assets/buildings/building_radar.jpg?v=<?= file_exists($bRad) ? filemtime($bRad) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Tour de Guet Yagura & Feux</strong><br>
                                <span style="font-size: 0.75rem; color: #06b6d4;">Vigie & Détection &bull; Slot 26</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_radar.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Dramatic cinematic illustration of a tall multi-tiered Japanese castle watchtower (Yagura) perched on a high corner stone rampart, samurai sentry in lacquered armor vigilantly scanning distant misty mountain valleys from the wooden observation balcony, a large iron brazier burning bright warning fire with thick black smoke curling toward the dusk sky, signal war drum (taiko) on the platform, dramatic sunset sky with crimson and indigo clouds, Sengoku period Japan, panoramic vista, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Tour de guet d'angle Yagura à étages, sentinelle samouraï surveillant les vallées brumeuses depuis la galerie de bois, brasero d'alarme crachant fumée et flammes, tambour taiko sous un ciel crépusculaire, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Dramatic cinematic illustration of a tall multi-tiered Japanese castle watchtower (Yagura) perched on a high corner stone rampart, samurai sentry in lacquered armor vigilantly scanning distant misty mountain valleys from the wooden observation balcony, a large iron brazier burning bright warning fire with thick black smoke curling toward the dusk sky, signal war drum (taiko) on the platform, dramatic sunset sky with crimson and indigo clouds, Sengoku period Japan, panoramic vista, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- ENTREPÔT DE MATÉRIAUX -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bSto = __DIR__ . '/../../public/assets/buildings/building_storage.jpg'; ?>
                                <img src="/public/assets/buildings/building_storage.jpg?v=<?= file_exists($bSto) ? filemtime($bSto) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Entrepôt Bois & Pierre</strong><br>
                                <span style="font-size: 0.75rem; color: #84cc16;">Stockage Matériaux &bull; Slot 20</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_storage.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Cinematic illustration of a massive fortified timber and stone warehouse courtyard in a Japanese castle district, giant stacked piles of fragrant cedar logs and dressed granite masonry stones neatly organized under wide overhanging tile roofs, heavy wooden sliding doors with iron reinforcements, draft carts and hemp ropes, clerk scribe checking inventory tallies with calligraphy brush, lantern illuminating the bustling logistics area, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, rich textural detail, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Cour d'entrepôt fortifié de matériaux, piles ordonnées de grumes de cèdre et blocs de granit sous de vastes auvents de tuiles, portes coulissantes blindées, chariots et scribe dressant l'inventaire au pinceau, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic illustration of a massive fortified timber and stone warehouse courtyard in a Japanese castle district, giant stacked piles of fragrant cedar logs and dressed granite masonry stones neatly organized under wide overhanging tile roofs, heavy wooden sliding doors with iron reinforcements, draft carts and hemp ropes, clerk scribe checking inventory tallies with calligraphy brush, lantern illuminating the bustling logistics area, Sengoku Jidai, ukiyo-e woodblock inspired semi-realistic digital art, rich textural detail, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- GRENIER À RIZ KURA -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bTan = __DIR__ . '/../../public/assets/buildings/building_tank.jpg'; ?>
                                <img src="/public/assets/buildings/building_tank.jpg?v=<?= file_exists($bTan) ? filemtime($bTan) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Grenier à Riz Fortifié (Kura)</strong><br>
                                <span style="font-size: 0.75rem; color: #eab308;">Silos à Grains Koku &bull; Slot 21</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_tank.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Atmospheric cinematic illustration of traditional feudal Japanese fireproof rice storehouses (kura / dozo), thick white fireproof plaster walls with distinctive black lacquered square base tiling, heavy barred wooden and iron doors, raised stone foundation to protect from dampness, open doors revealing stacks of woven straw rice bales (koku) reaching high ceiling beams, small red lacquered shrine dedicated to Inari the rice god beside the entrance, warm harvest afternoon light, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Greniers à riz ignifugés kura / dōzō aux épais murs de plâtre blanc et soubassement carrelé d'ardoise noire, portes blindées protégeant les balles de riz koku, autel vermillon d'Inari, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Atmospheric cinematic illustration of traditional feudal Japanese fireproof rice storehouses (kura / dozo), thick white fireproof plaster walls with distinctive black lacquered square base tiling, heavy barred wooden and iron doors, raised stone foundation to protect from dampness, open doors revealing stacks of woven straw rice bales (koku) reaching high ceiling beams, small red lacquered shrine dedicated to Inari the rice god beside the entrance, warm harvest afternoon light, Sengoku period Japan, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MARCHÉ FÉODAL -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bMkt = __DIR__ . '/../../public/assets/buildings/building_market.jpg'; ?>
                                <img src="/public/assets/buildings/building_market.jpg?v=<?= file_exists($bMkt) ? filemtime($bMkt) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Marché Féodal & Caravanes</strong><br>
                                <span style="font-size: 0.75rem; color: #f97316;">Commerce & Échanges &bull; Slot 24</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_market.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Lively vibrant cinematic illustration of a bustling feudal Japanese town marketplace (Rakuichi-rakuza), bustling streets lined with wooden merchant stalls adorned with colorful indigo and crimson fabric banners (noren), vendors displaying pottery, bolts of silk cloth, dried seafood, rice sacks and iron tools, pack horses laden with woven wicker panniers, travelling merchants and townspeople in traditional kimono, hanging paper lanterns, lively festival atmosphere, Sengoku period, warm sunlight casting long shadows, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Marché féodal animé Rakuichi-rakuza, ruelles marchandes bordées d'échoppes aux tentures noren indigo et pourpres, soieries, poteries, outils de fer et chevaux de bât, passants et marchands sous le soleil de fin de journée, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Lively vibrant cinematic illustration of a bustling feudal Japanese town marketplace (Rakuichi-rakuza), bustling streets lined with wooden merchant stalls adorned with colorful indigo and crimson fabric banners (noren), vendors displaying pottery, bolts of silk cloth, dried seafood, rice sacks and iron tools, pack horses laden with woven wicker panniers, travelling merchants and townspeople in traditional kimono, hanging paper lanterns, lively festival atmosphere, Sengoku period, warm sunlight casting long shadows, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- PAVILLON DIPLOMATIQUE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bEmb = __DIR__ . '/../../public/assets/buildings/building_embassy.jpg'; ?>
                                <img src="/public/assets/buildings/building_embassy.jpg?v=<?= file_exists($bEmb) ? filemtime($bEmb) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Pavillon Diplomatique (Ambassade)</strong><br>
                                <span style="font-size: 0.75rem; color: #10b981;">Alliances & Cérémonie &bull; Slot 28</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_embassy.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Serene yet prestigious cinematic illustration of a feudal Japanese diplomatic council pavilion and ceremonial tea house, elegant wooden architecture with wide wooden verandas (engawa) overlooking a tranquil Zen rock garden with raked white gravel and miniature pine trees, delicate silk sliding screens (fusuma) painted with golden clouds and flying cranes, solemn daimyo emissaries seated on tatami mats exchanging sealed alliance treaties bearing red wax seals, soft golden morning light, peaceful noble atmosphere, Sengoku period, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Pavillon diplomatique et maison de thé d'apparat avec coursive engawa sur jardin zen ratissé, cloisons fusuma peintes de grues dorées, émissaires daimyōs sur tatamis scellant des traités d'alliance à la cire rouge, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Serene yet prestigious cinematic illustration of a feudal Japanese diplomatic council pavilion and ceremonial tea house, elegant wooden architecture with wide wooden verandas (engawa) overlooking a tranquil Zen rock garden with raked white gravel and miniature pine trees, delicate silk sliding screens (fusuma) painted with golden clouds and flying cranes, solemn daimyo emissaries seated on tatami mats exchanging sealed alliance treaties bearing red wax seals, soft golden morning light, peaceful noble atmosphere, Sengoku period, ukiyo-e woodblock inspired semi-realistic digital painting, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- CACHETTE SECRÈTE -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bQv = __DIR__ . '/../../public/assets/buildings/building_quantum_vault.jpg'; ?>
                                <img src="/public/assets/buildings/building_quantum_vault.jpg?v=<?= file_exists($bQv) ? filemtime($bQv) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Cachette Secrète Sous Terre</strong><br>
                                <span style="font-size: 0.75rem; color: #64748b;">Caveau Inviolable &bull; Slot 27</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_quantum_vault.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Mysterious cinematic illustration of a hidden underground emergency vault beneath a Japanese castle, concealed trapdoor hidden under tatami mats leading down into a secret torchlit stone cellar, wooden chests reinforced with iron bands, hidden jars filled with gold ryo coins and precious grain bags safely concealed behind false walls and tripwire counterweight traps, warm flickering torchlight casting deep dramatic shadows across mossy stone walls, secretive atmosphere, Sengoku period Tokugawa style, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Caveau secret d'urgence sous tatamis dérobés, cave en pierre éclairée à la torche dissimulant coffres cerclés de fer, jarres de pièces d'or ryō et sacs de grains précieux protégés de faux murs et pièges, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Mysterious cinematic illustration of a hidden underground emergency vault beneath a Japanese castle, concealed trapdoor hidden under tatami mats leading down into a secret torchlit stone cellar, wooden chests reinforced with iron bands, hidden jars filled with gold ryo coins and precious grain bags safely concealed behind false walls and tripwire counterweight traps, warm flickering torchlight casting deep dramatic shadows across mossy stone walls, secretive atmosphere, Sengoku period Tokugawa style, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>

                        <!-- MURAILLE & REMPARTS -->
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <?php $bWal = __DIR__ . '/../../public/assets/buildings/building_wall.jpg'; ?>
                                <img src="/public/assets/buildings/building_wall.jpg?v=<?= file_exists($bWal) ? filemtime($bWal) : 1 ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle;">
                                <strong style="color: #fff;">Muraille & Remparts de Cité</strong><br>
                                <span style="font-size: 0.75rem; color: #22c55e;">Enceinte & Douves &bull; Slot 34</span>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; font-family: monospace; font-size: 0.75rem; color: #67e8f9;">
                                building_wall.jpg
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">
                                <em>Colossal cinematic illustration of massive feudal Japanese castle defensive ramparts and fortified stone walls (nozura-zumi ishigaki), towering grey megalithic rock walls rising from wide deep moats filled with dark reflective water, white plastered defensive parapets along the top pierced by triangular gun embrasures and rectangular arrow slits (sama), heavy timber corner bastion, spiked wooden palisades (mokusaku) along the outer bank, dramatic stormy sky with lightning flash breaking through clouds, Sengoku period Japan, epic defensive scale, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution</em>
                                <div style="font-size: 0.75rem; color: #fde047; margin-top: 5px; padding-top: 4px; border-top: 1px dashed rgba(255,255,255,0.12); line-height: 1.4;">
                                    <strong>🇫🇷 Traduction :</strong> <em>Remparts colossaux et murailles cyclopéennes ishigaki nozura-zumi émergeant de douves profondes, parapets blancs crénelés de meurtrières sama, bastion de bois et palissades sous un ciel orageux épique, style ukiyo-e 8k.</em>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Colossal cinematic illustration of massive feudal Japanese castle defensive ramparts and fortified stone walls (nozura-zumi ishigaki), towering grey megalithic rock walls rising from wide deep moats filled with dark reflective water, white plastered defensive parapets along the top pierced by triangular gun embrasures and rectangular arrow slits (sama), heavy timber corner bastion, spiked wooden palisades (mokusaku) along the outer bank, dramatic stormy sky with lightning flash breaking through clouds, Sengoku period Japan, epic defensive scale, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Copier</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION BONUS : TEASER VIDÉO IA -->
        <div class="card" style="margin-top: 1.5rem; background: rgba(30, 27, 75, 0.4); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 10px; padding: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem;">
                <span style="font-size: 1.4rem;">🎬</span>
                <div>
                    <h4 style="margin: 0; color: #c084fc; font-size: 1.1rem;">Atelier Cinématique : Générer un Teaser Vidéo avec l'IA</h4>
                    <span style="font-size: 0.78rem; color: #94a3b8;">Compatible avec Runway Gen-3, Kling AI, Luma Dream Machine et Sora</span>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: #cbd5e1; line-height: 1.5; margin-bottom: 1rem;">
                En vidéo, l'IA a besoin d'indications sur le <strong>mouvement de caméra</strong> (zoom avant, travelling), les <strong>effets dynamiques</strong> (flammes crachées, roues dans la boue, flèches enflammées) et le style artistique <em>ukiyo-e</em> pour garder la cohérence avec le jeu.
            </p>

            <?php 
                $videoDir = __DIR__ . '/../../public/assets/videos/';
                $videoDisk = $videoDir . 'Slow_motion_dynamic_tracking_s.mp4';
                if (!file_exists($videoDisk)) {
                    $mp4Files = glob($videoDir . '*.mp4');
                    if (!empty($mp4Files)) {
                        $videoDisk = $mp4Files[0];
                    }
                }
                $videoExists = file_exists($videoDisk);
                $videoFilename = $videoExists ? basename($videoDisk) : '';
                $videoSrc = $videoExists ? ('/public/assets/videos/' . $videoFilename . '?v=' . filemtime($videoDisk)) : '';
                $videoSizeMb = $videoExists ? round(filesize($videoDisk) / (1024 * 1024), 1) : 0;
            ?>

            <?php if ($videoExists): ?>
                <!-- LECTEUR VIDÉO HTML5 -->
                <div style="margin: 1.25rem 0; background: #0b0f19; border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.7);">
                    <div style="background: linear-gradient(90deg, rgba(147, 51, 234, 0.25) 0%, rgba(220, 38, 38, 0.25) 100%); padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-weight: 800; color: #f3e8ff; font-size: 0.95rem; display: flex; align-items: center; gap: 0.6rem;">
                            <span style="font-size: 1.2rem;">▶️</span> Teaser Officiel OpenShogun &bull; Séquence Complète des 4 Scènes
                        </span>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="background: rgba(34, 197, 94, 0.2); color: #4ade80; font-size: 0.72rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(34, 197, 94, 0.3);">
                                🎬 MP4 &bull; <?= $videoSizeMb ?> Mo
                            </span>
                            <a href="<?= $videoSrc ?>" download="OpenShogun_Teaser_Complet.mp4" class="btn btn-secondary" style="font-size: 0.75rem; padding: 3px 10px; font-weight: 700;">
                                ⬇️ Télécharger la Vidéo
                            </a>
                        </div>
                    </div>
                    <div style="position: relative; width: 100%; max-width: 950px; margin: 0 auto; background: #000;">
                        <video controls preload="metadata" style="width: 100%; max-height: 520px; display: block; object-fit: contain; margin: 0 auto; outline: none;" poster="/public/assets/shogun_login_bg.jpg">
                            <source src="<?= $videoSrc ?>" type="video/mp4">
                            Votre navigateur ne prend pas en charge la lecture de vidéos HTML5.
                        </video>
                    </div>
                    <div style="padding: 0.6rem 1.25rem; background: rgba(0,0,0,0.5); font-size: 0.78rem; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap; gap: 0.5rem;">
                        <span>💡 <em>Astuce :</em> Vous pouvez basculer en plein écran avec l'icône ⛶ en bas à droite du lecteur.</span>
                        <span style="color: #c084fc;">Fichier actif : <code><?= htmlspecialchars($videoFilename) ?></code></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LES 4 PROMPTS COMBINÉS DANS LE TEASER -->
            <div style="margin-top: 1.5rem;">
                <h5 style="color: #facc15; font-size: 0.95rem; margin-bottom: 0.75rem;">🎞️ Les 4 Prompts Successifs Utilisés pour Composer ce Teaser :</h5>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">
                    <!-- SCÈNE 1 -->
                    <div style="background: rgba(0,0,0,0.3); padding: 0.9rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong style="color: #38bdf8; font-size: 0.85rem;">🌅 Scène 1 : L'Éveil de l'Éclaireur</strong>
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Cinematic low-angle shot of a Takeda samurai scout cavalryman on a cliff overlooking misty mountain valleys at sunrise. The horse snorts with visible breath, the back banner flutters in the morning wind, camera slowly pushes in, golden sunrays, ukiyo-e style." style="font-size: 0.7rem; padding: 2px 6px;">Copier</button>
                            </div>
                            <p style="font-size: 0.76rem; color: #67e8f9; margin: 0 0 0.5rem 0; line-height: 1.4; font-family: monospace;">
                                <em>« Cinematic low-angle shot of a Takeda samurai scout cavalryman on a cliff overlooking misty mountain valleys at sunrise. The horse snorts with visible breath, the back banner flutters in the morning wind, camera slowly pushes in, golden sunrays, ukiyo-e style. »</em>
                            </p>
                        </div>
                        <div style="font-size: 0.76rem; color: #fde047; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.15); line-height: 1.45;">
                            <strong>🇫🇷 Traduction :</strong> <em>Plan cinématographique en contre-plongée d'un cavalier éclaireur samouraï Takeda sur une falaise dominant des vallées brumeuses au lever du soleil. Le cheval s'ébroue dans l'air frais, la bannière dorsale flotte dans le vent matinal, zoom avant lent, rayons dorés, style ukiyo-e.</em>
                        </div>
                    </div>

                    <!-- SCÈNE 2 -->
                    <div style="background: rgba(0,0,0,0.3); padding: 0.9rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong style="color: #ef4444; font-size: 0.85rem;">🐎 Scène 2 : La Marche des Titans</strong>
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Slow-motion dynamic tracking shot of colossal rolling siege fortress and dragon ram advancing heavily in battlefield mud towards castle ramparts. Fiery arrows deflect off iron plates, the dragon iron head belches bursts of fire and sparks, massive spiked wheels turning, intense battlefield smoke, dramatic dusk lighting." style="font-size: 0.7rem; padding: 2px 6px;">Copier</button>
                            </div>
                            <p style="font-size: 0.76rem; color: #67e8f9; margin: 0 0 0.5rem 0; line-height: 1.4; font-family: monospace;">
                                <em>« Slow-motion dynamic tracking shot of colossal rolling siege fortress and dragon ram advancing heavily in battlefield mud towards castle ramparts. Fiery arrows deflect off iron plates, the dragon iron head belches bursts of fire and sparks, massive spiked wheels turning, intense battlefield smoke, dramatic dusk lighting. »</em>
                            </p>
                        </div>
                        <div style="font-size: 0.76rem; color: #fde047; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.15); line-height: 1.45;">
                            <strong>🇫🇷 Traduction :</strong> <em>Travelling dynamique au ralenti d'une forteresse roulante colossale et d'un bélier à tête de dragon avançant pesamment dans la boue vers les remparts. Flèches enflammées ricochetant sur le fer, gueule de dragon crachant feu et étincelles, roues géantes tournant, fumée dense au crépuscule.</em>
                        </div>
                    </div>

                    <!-- SCÈNE 3 -->
                    <div style="background: rgba(0,0,0,0.3); padding: 0.9rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong style="color: #a855f7; font-size: 0.85rem;">🥷 Scène 3 : L'Embuscade Shinobi</strong>
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Midnight darkness in misty bamboo forest. A masked shinobi cavalryman bursts forward on a black horse surrounded by purple smoke, drawing gleaming steel sword towards the camera, full moon shafts through bamboo, fast dynamic camera zoom." style="font-size: 0.7rem; padding: 2px 6px;">Copier</button>
                            </div>
                            <p style="font-size: 0.76rem; color: #67e8f9; margin: 0 0 0.5rem 0; line-height: 1.4; font-family: monospace;">
                                <em>« Midnight darkness in misty bamboo forest. A masked shinobi cavalryman bursts forward on a black horse surrounded by purple smoke, drawing gleaming steel sword towards the camera, full moon shafts through bamboo, fast dynamic camera zoom. »</em>
                            </p>
                        </div>
                        <div style="font-size: 0.76rem; color: #fde047; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.15); line-height: 1.45;">
                            <strong>🇫🇷 Traduction :</strong> <em>Obscurité nocturne dans une forêt de bambous embrumée. Un cavalier shinobi masqué jaillit sur un cheval noir dans un nuage de fumée violette, dégainant son sabre d'acier vers la caméra, pleine lune filtrant à travers les tiges de bambou, zoom avant rapide.</em>
                        </div>
                    </div>

                    <!-- SCÈNE 4 -->
                    <div style="background: rgba(0,0,0,0.3); padding: 0.9rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong style="color: #f59e0b; font-size: 0.85rem;">🔥 Scène 4 : Climax & Flèches de Feu</strong>
                                <button type="button" class="btn btn-secondary" onclick="copyPromptText(this)" data-prompt="Epic cinematic battle teaser trailer of feudal Japan Sengoku period. Slow dynamic low-angle tracking shot moving forward through a muddy battlefield. In the center, a colossal wooden dragon siege ram machine rolls forward on spiked iron wheels, its ferocious blackened-iron dragon head roaring and belching glowing sparks and smoke. Beside it, charging Takeda samurai cavalry in brilliant crimson red armor on galloping warhorses surge forward with raised spears. Above, a barrage of flaming arrows arcs across the smoky dusk sky towards a distant towering Japanese castle fortress. Flying fire embers, swirling autumn red leaves, dramatic volumetric sunset light breaking through war smoke, ukiyo-e woodblock inspired semi-realistic digital anime aesthetic, fluid motion, 8k masterpiece" style="font-size: 0.7rem; padding: 2px 6px;">Copier</button>
                            </div>
                            <p style="font-size: 0.76rem; color: #67e8f9; margin: 0 0 0.5rem 0; line-height: 1.4; font-family: monospace;">
                                <em>« Epic cinematic battle teaser trailer of feudal Japan Sengoku period. Slow dynamic low-angle tracking shot moving forward through a muddy battlefield... fluid motion, 8k masterpiece »</em>
                            </p>
                        </div>
                        <div style="font-size: 0.76rem; color: #fde047; padding-top: 0.5rem; border-top: 1px dashed rgba(255,255,255,0.15); line-height: 1.45;">
                            <strong>🇫🇷 Traduction :</strong> <em>Bande-annonce épique de bataille Sengoku. Travelling en contre-plongée dans la boue. Le bélier dragon roule et crache des flammes, la cavalerie rouge Takeda charge lances dressées, barrage d'arcs de flèches enflammées vers un château en flammes au loin, feuilles rouges, lumière dorée et braises.</em>
                        </div>
                    </div>
                </div>
                </div>
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

