# 🎓 Manuel de Conception & Guide Pédagogique du Jeu Vidéo (Projet Père-Fils)

> **Bienvenue dans l'Atelier des Créateurs !**  
> Ce document est conçu comme le carnet de bord et le support d'apprentissage pour comprendre pas à pas **comment se fabrique un jeu vidéo web multijoueur** à travers le projet **OpenShogun**. Il sera enrichi et complété au fil de l'évolution du jeu.

---

## 🧭 Sommaire
1. [L'Aventure de la Création d'un Jeu Vidéo (Les 4 Métiers du Studio)](#1-laventure-de-la-création-dun-jeu-vidéo)
2. [L'Architecture Technique : Comment Voyage l'Information ?](#2-larchitecture-technique--comment-voyage-linformation-)
3. [Le Moteur de Jeu Asynchrone ("Game Loop" sur le Web)](#3-le-moteur-de-jeu-asynchrone-game-loop-sur-le-web)
4. [L'Algorithme de la File de Construction & Démolition](#4-lalgorithme-de-la-file-de-construction--démolition)
5. [L'Algorithme des Combats Féodaux & du Pillage](#5-lalgorithme-des-combats-féodaux--du-pillage)
6. [La Production Continue de Ressources & les Greniers](#6-la-production-continue-de-ressources--les-greniers)
7. [La Carte Provinciale, Terroirs et Positionnement Relatif des Slots](#7-la-carte-provinciale-terroirs-et-positionnement-relatif-des-slots)
8. [L'Art du Prompt Engineering : Créer des Graphismes avec l'IA](#8-lart-du-prompt-engineering--créer-des-graphismes-avec-lia)
9. [Le Grimoire Complet des Prompts du Jeu](#9-le-grimoire-complet-des-prompts-du-jeu)
10. [Ateliers Pratiques à Réaliser Ensemble (Père & Fils)](#10-ateliers-pratiques-à-réaliser-ensemble-père--fils)

---

## 1. L'Aventure de la Création d'un Jeu Vidéo

Créer un jeu vidéo, c'est réunir plusieurs métiers passionnants au sein d'un même projet :

```
             ┌──────────────────────────────────────────────┐
             │       LE GAME DESIGNER (L'Architecte)        │
             │   Invente les règles, l'histoire et le plaisir│
             └──────────────────────┬───────────────────────┘
                                    │
         ┌──────────────────────────┴──────────────────────────┐
         ▼                                                     ▼
┌─────────────────────────────┐               ┌─────────────────────────────┐
│  LE DÉVELOPPEUR MOTEUR      │               │     LE DIRECTEUR ARTISTIQUE │
│  (Backend & Algorithmes)    │               │     (Graphismes & Prompts)  │
│  Écrit le code PHP, les     │               │  Imagine le style visuel,   │
│  calculs et la base MySQL   │               │  génère les décors et héros │
└──────────────┬──────────────┘               └──────────────┬──────────────┘
               │                                             │
               └──────────────────────┬──────────────────────┘
                                      ▼
             ┌──────────────────────────────────────────────┐
             │         LE DÉVELOPPEUR FRONTEND (UI/UX)      │
             │  Rassemble tout sur l'écran : HTML, CSS, JS  │
             └──────────────────────────────────────────────┘
```

### Le Cycle de Vie d'une Idée :
1. **L'Idée (Brainstorming)** : *"Et si on permettait aux joueurs de démolir un bâtiment pour changer de stratégie ?"*
2. **La Spécification (Règles du jeu)** : Doit-on supprimer tout de suite ? Non, il faut un compte à rebours, un ouvrier mobilisé, et 30% des matériaux récupérés !
3. **Le Schéma de Données** : Quelle table SQL doit stocker l'ordre ? La table `construction_queue` avec `target_level = 0`.
4. **L'Algorithme Backend (PHP)** : Le code qui calcule la durée, vérifie que le Donjon n'est pas ciblé, et rembourse les greniers.
5. **L'Interface Frontend (HTML/CSS/JS)** : Le bouton rouge 💥, le compte à rebours en direct ⏳, et le badge sur la carte.
6. **Le Test (Assurance Qualité)** : Écrire un script automatisé pour s'assurer qu'aucun bug ne s'est glissé.

---

## 2. L'Architecture Technique : Comment Voyage l'Information ?

Dans un jeu multijoueur sur navigateur web, il y a deux acteurs inséparables :
- **Le Client (Le Navigateur)** : C'est la vitrine visible sur l'écran (Google Chrome, Firefox). Il affiche les images, joue les animations et capte les clics du joueur.
- **Le Serveur (Le Cerveau Invisible)** : Un ordinateur distant qui tourne 24h/24. Il détient la vérité absolue. Le client ne fait que lui envoyer des demandes (*"Je veux améliorer ma rizière"*).

```
   JOUEUR (Navigateur / Client)                     SERVEUR (PHP 8 + Apache)
  ┌─────────────────────────────┐                 ┌─────────────────────────────┐
  │   Clic sur "Améliorer"      │   Requête POST  │ 1. Vérifie l'identité       │
  │   Affichage du sablier ⏳   ├────────────────►│ 2. Contrôle les ressources │
  │                             │   (JSON/Fetch)  │ 3. Écrit l'ordre en base    │
  │                             │                 │ 4. Déduit le bois et le riz │
  │   Mise à jour visuelle      │   Réponse JSON  │                             │
  │   de l'écran sans recharger │◄────────────────┤ Renvoie : { success: true } │
  └─────────────────────────────┘                 └──────────────┬──────────────┘
                                                                 │
                                                                 ▼
                                                  BASE DE DONNÉES (MariaDB/MySQL)
                                                  ┌─────────────────────────────┐
                                                  │ Table 'planets' (ressources)│
                                                  │ Table 'construction_queue'  │
                                                  │ Table 'users' (daimyōs)     │
                                                  └─────────────────────────────┘
```

> **Règle d'or de la cybersécurité :**  
> *« Ne jamais faire confiance au client »*. Si un joueur tricheur modifie le code JavaScript sur son ordinateur pour dire *"J'ai 1 000 000 de pièces d'or"*, le serveur vérifie dans sa base de données et rejette la demande !

---

## 3. Le Moteur de Jeu Asynchrone ("Game Loop" sur le Web)

Dans un jeu vidéo traditionnel (sur console ou PC comme Mario ou Fortnite), le programme exécute une boucle infinie qui tourne 60 fois par seconde (`60 FPS`) :
```c
while (jeu_en_cours) {
    lire_manette();
    mettre_a_jour_physique();
    dessiner_ecran();
}
```

### L'Astuce Magique du Web : Le "Tick on Request"
Sur le web, avoir une boucle infinie pour 10 000 joueurs ferait exploser le serveur.  
On utilise donc une **formule mathématique basée sur le temps écoulé ($\Delta t$)** !

Chaque planète possède une colonne `last_update` (horodatage Unix en secondes, ex: `1726580000`).  
Quand le joueur charge sa page ou réalise une action :
$$\Delta t = \text{Temps Actuel} - \text{Dernière Mise à Jour}$$

Si $\Delta t = 120$ secondes, et que la mine produit $360$ unités par heure :
$$\text{Production} = 360 \times \frac{120}{3600} = 12 \text{ unités}$$

Le serveur ajoute immédiatement 12 unités aux greniers et met à jour `last_update = Temps Actuel`.  
C'est instantané, parfaitement équitable, et cela ne consomme aucune mémoire quand le joueur est déconnecté !

---

## 4. L'Algorithme de la File de Construction & Démolition

### 1. La Formule Exponentielle des Coûts
Pour que le jeu soit captivant sur plusieurs semaines, chaque niveau supérieur d'un bâtiment doit coûter plus cher et prendre plus de temps que le précédent :
$$\text{Coût}(n) = \text{Coût Base} \times 1.25^{(n - 1)}$$
$$\text{Durée}(n) = \frac{\text{Bois} + \text{Pierre}}{2000 \times (1 + \text{Niveau Tenshu})} \times 3600 \text{ secondes}$$

Plus votre **Donjon Tenshu** est évolué, plus vos artisans construisent vite !

### 2. La Machine à États de la File de Travaux
Chaque ordre inséré dans la table `construction_queue` possède :
- `target_id` : L'identifiant du bâtiment (`market`, `dojo`, `hq`) ou le numéro de parcelle rurale (`1` à `18`).
- `target_level` : Le niveau visé.
- `started_at` : Date de début (ex: $t = 1000$).
- `finishes_at` : Date de fin programmée (ex: $t = 1060$).

```
                    ┌────────────────────────┐
                    │    ÉTAT INITIAL        │
                    │   Bâtiment Niveau 2    │
                    └───────────┬────────────┘
                                │
                 Clic sur "Raser le Bâtiment"
                                │
                                ▼
                    ┌────────────────────────┐
                    │   DÉMANTÈLEMENT EN     │
                    │   COURS (target=0)     │◄────────────┐
                    │   Compte à rebours ⏳  │             │
                    └─────┬────────────┬─────┘             │
                          │            │                   │
         Clic sur         │            │  Chrono à 0       │
    "Interrompre travaux" │            │  (Fin normale)    │
                          ▼            ▼                   │
             ┌────────────────┐   ┌────────────────────┐   │
             │   ANNULATION   │   │     DESTRUCTION    │   │
             │ Bâtiment reste │   │ Bâtiment supprimé  │   │
             │ intact Niveau 2│   │ 30% remboursés     │   │
             │ Pas de pénalité│   │ Emplacement libre+ │   │
             └────────────────┘   └────────────────────┘   │
                                                           │
             (Si nouvelle construction au Niv. 1, 2, 3...) ─┘
```

### 3. Pourquoi l'astuce `target_level = 0` est brillante ?
Au lieu de créer deux tables différentes pour construire et démolir, nous utilisons la même file !
- Si `target_level > 0` : C'est une élévation. À la fin, on augmente le niveau.
- Si `target_level === 0` : C'est une démolition !
  - **Pendant le décompte** : La bâtisse reste dessinée sur la carte avec un badge rouge `🗑️`. Le slot n'est pas encore libre !
  - **Si on annule** : On supprime simplement la ligne de la file. Le bâtiment n'ayant jamais été effacé, il reste intact sans calcul complexe.
  - **Quand le chrono atteint zéro** : Le moteur `PlanetEngine::processConstructionQueue()` retire définitivement le bâtiment, verse les 30% de remboursement de matériaux et réaffiche la bulle verte `+`.

---

## 5. L'Algorithme des Combats Féodaux & du Pillage

Le moteur de combat (`CombatEngine.php`) gère les batailles entre armées ennemies lorsque deux daimyōs s'affrontent.

### 1. La Règle des 3 Rounds
Une bataille se déroule en **3 rounds tactiques maximum** :
À chaque tour :
1. **Calcul des Puissances Totales** :
   $$\text{Attaque Totale} = \sum (\text{Attaque Unité} \times \text{Nombre})$$
   $$\text{Défense Totale} = \sum (\text{Armure Unité} \times \text{Nombre}) + \text{Bonus Muraille}$$
2. **Atténuation par les Remparts** :
   Les remparts de pierre de la forteresse (`wall`) réduisent les dégâts subis par le défenseur jusqu'à 50% !
3. **Application des Pertes Proportionnelles** :
   $$\text{Ratio de Pertes} = \min\left(1.0, \frac{\text{Dégâts Reçus}}{\text{Points de Structure}}\right) \times 0.5$$
   Chaque régiment perd un pourcentage d'hommes proportionnel à la puissance de l'armée adverse.

### 2. Le Pillage et la Cachette Secrète
Si l'attaquant remporte la victoire, ses survivants chargent leurs sacs et charrettes de ravitaillement :
$$\text{Capacité de Fret Totale} = \sum (\text{Capacité Cargo} \times \text{Unités Survivantes})$$

Mais le défenseur peut protéger ses récoltes grâce à la **Cachette Secrète Sous Terre** (`quantum_vault`) :
$$\text{Ressources Pillables} = \max(0, \text{Ressources en Stock} - \text{Capacité de la Cachette})$$
Les soldats victorieux ne peuvent emporter que les surplus non protégés, répartis équitablement entre le Bois, la Pierre et le Riz.

---

## 6. La Production Continue de Ressources & les Greniers

Le domaine féodal repose sur 4 piliers :
- 🪵 **Bois (Bûcherons)** : Pour les charpentes, lances et palissades.
- 🪨 **Pierre (Carrières)** : Pour les remparts et les soubassements des châteaux.
- 🌾 **Riz (Rizières)** : Pour nourrir la population et lever des armées.
- ⛩️ **Énergie Divine (Sanctuaires Shintō)** : Pour alimenter en moral et en bénédictions les moulins et forges.

### Formule avec Facteur Énergétique :
Si vos Sanctuaires Shintō ne produisent pas assez d'énergie pour couvrir la demande de vos 18 parcelles :
$$\text{Facteur Énergie} = \min\left(1.0, \frac{\text{Énergie Produite}}{\text{Énergie Consommée}}\right)$$
La production de toutes vos mines ralentit automatiquement à la hauteur du ratio d'énergie ! Construire des Sanctuaires est donc indispensable.

---

## 7. La Carte Provinciale, Terroirs et Positionnement Relatif des Slots

### 1. La Carte Hexagonale / Grille 2D
La carte du monde est un damier où chaque province possède des coordonnées $(X, Y)$ :
- La distance entre deux châteaux est calculée par la formule euclidienne :
  $$\text{Distance} = \sqrt{(X_2 - X_1)^2 + (Y_2 - Y_1)^2}$$
- La durée de marche d'une armée :
  $$\text{Durée (secondes)} = \frac{\text{Distance}}{\text{Vitesse de l'unité la plus lente}} \times 3600$$

### 2. Le Système de Calibration des Slots (RTS)
Plutôt que d'utiliser des pixels fixes (`left: 350px`) qui se décalent sur les écrans de smartphone ou de tablettes, nous utilisons des **pourcentages relatifs** :
```css
.hotspot-city-slot-19 {
    position: absolute;
    left: 48.5%; /* Position horizontale relative */
    top: 28.2%;  /* Position verticale relative */
    transform: translate(-50%, -50%);
}
```
Grâce à notre outil interactif de **Calibration Drag & Drop sécurisé**, l'administrateur peut déplacer les bulles de niveau à la souris, et le serveur enregistre les coordonnées exactes dans `config/slot_positions.json` !

---

## 8. L'Art du Prompt Engineering : Créer des Graphismes avec l'IA

Pour donner vie à notre univers féodal sans avoir une armée de 50 dessinateurs, nous avons utilisé des modèles d'intelligence artificielle générative d'images (comme Imagen / Midjourney / Stable Diffusion).

### La Recette Secrète d'un Bon Prompt de Jeu Vidéo :
Pour que toutes les images s'accordent harmonieusement dans le jeu, chaque consigne suit une formule rigoureuse en 5 ingrédients :

```
[SUJET PRINCIPAL] + [ACTION / POSE] + [STYLE ARTISTIQUE] + [ÉCLAIRAGE & AMBIANCE] + [CONTRAINTES TECHNIQUES]
```

1. **Le Sujet** : Décrire précisément le personnage ou le bâtiment (ex: *« Un arquebusier japonais ashigaru du clan Oda tenant un fusil Tanegashima »*).
2. **Le Style Artistique** : Préciser le style visuel exact pour garder la cohérence (ex: *« Estampe japonaise Ukiyo-e moderne, peinture numérique semi-réaliste, détails d'armure laquée »*).
3. **Le Cadrage & Angle** :
   - Pour les bâtiments : *« Vue isométrique RTS de haut en bas, angle 45 degrés »*.
   - Pour les soldats : *« Portrait en pied de trois-quarts héroïque, cadrage serré »*.
4. **L'Ambiance & Éclairage** : *« Lumière dorée du soleil couchant, brume mystique, étincelles et cerisiers en fleurs »*.
5. **Les Contraintes Techniques** : *« Fond transparent PNG isolé, pas de texte, pas de bordure, haute définition »*.

---

## 9. Le Grimoire Complet des Prompts du Jeu

Voici les prompts authentiques utilisés pour générer l'univers visuel d'**OpenShogun** :

### 🌄 A. Décors & Arrière-plans
| Fichier | Nom | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| `shogun_castle_city_bg.jpg` | **Cité Castrale (Dorf 2)** | *« High angle aerial RTS village view of a feudal Japanese castle town, Sengoku period, centered on a massive stone Tenshu citadel, surrounding courtyards, dojo, stables, market streets, misty morning light, cherry blossoms, detailed isometric strategy game background, 4k digital matte painting »*<br><br>**🇫🇷 Traduction :** *Vue aérienne en plongée RTS d'une ville fortifiée japonaise féodale, période Sengoku, centrée sur un donjon Tenshu colossal en pierre, cours intérieures, dojo, écuries, ruelles marchandes, brume matinale, cerisiers en fleurs, arrière-plan de jeu de stratégie isométrique détaillé.* |
| `shogun_rural_terroir_bg.jpg` | **Terroirs Ruraux (Dorf 1)** | *« Top-down RTS strategic resource landscape of rural feudal Japan, terraced flooded rice paddies reflecting blue sky, lush bamboo lumber forests, stone quarry cliffs, sacred mountain shrine river, miniature Travian-style tactical game surface, warm sunlight »*<br><br>**🇫🇷 Traduction :** *Paysage stratégique de ressources en vue du dessus (RTS) du Japon féodal rural, rizières en terrasses inondées reflétant le ciel bleu, forêts denses de bambous et de cèdres, falaises de carrières de pierre, sanctuaire de montagne sacré avec rivière, plateau de jeu tactique miniature.* |
| `shogun_login_bg.jpg` | **Écran Titre & Connexion** | *« Majestic cinematic view of a Japanese samurai fortress at dawn, dramatic red sun rising behind mount Fuji, flying cranes, golden mist, traditional Japanese architecture, epic atmospheric game title screen »*<br><br>**🇫🇷 Traduction :** *Vue cinématographique majestueuse d'une forteresse de samouraïs à l'aube, soleil rouge spectaculaire se levant derrière le mont Fuji, grues en vol, brume dorée, architecture japonaise traditionnelle, écran d'accueil épique et atmosphérique.* |

---

### 🏯 B. Bâtiments & Tuiles Isométriques (Sprites PNG)
| Fichier | Bâtiment | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| `tile_tenshu.png` | **Donjon Tenshu** | *« Isolated isometric sprite of a monumental multi-tiered Japanese castle keep (Tenshu), black lacquered timber, white plaster walls, curved green tiled roofs, golden ornaments, on high stone foundation base, transparent background, RTS building asset »*<br><br>**🇫🇷 Traduction :** *Sprite isométrique isolé d'un monumental donjon de château japonais à plusieurs étages (Tenshu), bois laqué noir, murs de plâtre blanc, toits courbés en tuiles vertes, ornements dorés, haute base en fondation de pierre, fond transparent.* |
| `tile_barracks.png` | **Dojo Militaire** | *« Isolated isometric sprite of a traditional feudal Japanese samurai dojo, training yard with weapon racks and wooden dummies, tiled pagoda roof, red clan banners, transparent background »*<br><br>**🇫🇷 Traduction :** *Sprite isométrique isolé d'un dojo de samouraï traditionnel du Japon féodal, cour d'entraînement avec râteliers d'armes et mannequins de bois, toit en pagode de tuiles, bannières rouges de clan, fond transparent.* |
| `tile_market.png` | **Marché Féodal** | *« Isolated isometric feudal Japanese marketplace building, wooden stalls with textile canopies, rice baskets, lanterns, wooden carts, lively plaza sprite, transparent background »*<br><br>**🇫🇷 Traduction :** *Bâtiment de marché japonais féodal en vue isométrique isolée, étals en bois avec auvents en tissu, paniers de riz, lanternes, charrettes en bois, place de marché vivante, fond transparent.* |
| `tile_riziere.png` | **Rizière Inondée** | *« Isolated game tile sprite of lush green terraced flooded rice field, clean wooden irrigation sluice channels, reflective clear water, vibrant green shoots, RTS terrain asset, transparent background »*<br><br>**🇫🇷 Traduction :** *Tuile de jeu isolée d'une rizière en terrasses inondée d'un vert luxuriant, canaux d'irrigation et vannes en bois propre, eau claire réfléchissante, jeunes pousses vert vif, fond transparent.* |
| `tile_bucheron.png` | **Camp de Bûcherons** | *« Isolated game tile sprite of a Japanese lumberjack timber camp, cut cedar logs stacked neatly, rustic wooden shed with saws and axes, sawdust ground, transparent background »*<br><br>**🇫🇷 Traduction :** *Tuile de jeu isolée d'un camp de bûcherons japonais, rondins de cèdre coupés et empilés soigneusement, abri rustique en bois avec scies et haches, sol couvert de sciure, fond transparent.* |
| `tile_carriere.png` | **Carrière de Pierre** | *« Isolated game tile sprite of a stone quarry cutting into grey rock hillside, scaffolding, chisels, stone blocks ready for castle masonry, transparent background »*<br><br>**🇫🇷 Traduction :** *Tuile de jeu isolée d'une carrière de pierre taillée dans un flanc de colline rocheuse grise, échafaudages, burins, blocs de pierre taillés pour la maçonnerie du château, fond transparent.* |
| `tile_sanctuaire.png` | **Sanctuaire Shintō** | *« Isolated game tile sprite of a vermilion red Shinto shrine pavilion, wooden waterwheel mill beside a bubbling stream, sacred shimenawa rope, peaceful spiritual garden, transparent background »*<br><br>**🇫🇷 Traduction :** *Tuile de jeu isolée d'un pavillon de sanctuaire shintoïste rouge vermillon, roue à aubes en bois au bord d'un ruisseau vif, corde sacrée shimenawa, jardin spirituel paisible, fond transparent.* |
| `tile_wall.png` | **Muraille & Remparts** | *« Isolated isometric section of heavy feudal Japanese stone castle rampart, white plastered wall with arrow slits and triangular gun embrasures, grey tiled coping, transparent background »*<br><br>**🇫🇷 Traduction :** *Section isométrique isolée d'un lourd rempart de pierre de château féodal japonais, mur crépi de blanc avec meurtrières et ouvertures de tir triangulaires, chaperon en tuiles grises, fond transparent.* |

---

### ⚔️ C. Régiments du Dojo, Héros & Engins de Siège
| Fichier | Unité | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| `hero_samurai.jpg` | **Héros Samouraï & Daimyō** | *« Epic heroic portrait of a venerable Sengoku Daimyo general in full ornate black and gold samurai armor, horned kabuto helmet, holding an ancient katana, wind blowing cherry blossom petals, dramatic moody lighting, character art »*<br><br>**🇫🇷 Traduction :** *Portrait héroïque et épique d'un général daimyō de l'époque Sengoku en armure d'apparat noire et ornée, casque kabuto cornu, tenant un katana ancestral, vent emportant des pétales de cerisiers en fleurs, éclairage dramatique.* |
| `piquier_ashigaru_yari.jpg` | **Piquier Ashigaru** | *« Feudal Japanese peasant ashigaru soldier holding an extremely long nagae-yari spear, black jingasa conical helmet, lacquered breastplate, determined expression, battlefield background, historical concept art »*<br><br>**🇫🇷 Traduction :** *Soldat fantassin ashigaru tenant une pique nagae-yari extrêmement longue, casque conique jingasa noir, plastron laqué, regard déterminé, champ de bataille brumeux.* |
| `arquebusier_oda_tanegashima.jpg` | **Arquebusier Tanegashima** | *« Oda clan matchlock musketeer aiming a wooden Tanegashima gun, smoke curling from the muzzle, bamboo tate barricade in foreground, glowing matchcord, dynamic battle action pose »*<br><br>**🇫🇷 Traduction :** *Mousquetaire du clan Oda visant avec une arquebuse Tanegashima en bois, volutes de fumée s'élevant du canon, barricade de boucliers en bambou tate au premier plan, mèche incandescente, pose d'action de combat.* |
| `samourai_katana.jpg` | **Samouraï au Katana** | *« Master swordsman samurai in mid-stance drawing sharp steel katana blade, crimson and black silk cords on lamellar armor, fierce focus, flying embers, ukiyo-e digital illustration »*<br><br>**🇫🇷 Traduction :** *Maître épéiste samouraï en posture de frappe dégainant sa lame d'acier katana tranchante, cordons de soie pourpre et noire sur armure lamellaire, regard d'une intensité féroce, braises incandescentes au vent, style ukiyo-e.* |
| `cavalier_eclaireur_takeda.jpg` | **Cavalier Éclaireur Takeda** | *« Dynamic cinematic illustration of an agile Takeda clan samurai scout cavalryman, mounted on a swift wild Japanese mountain horse (Kiso horse), wearing lightweight crimson red lacquered armor with vermilion cords, horned jingasa battle hat, carrying a scouting yari spear and a katana, small back banner (sashimono) bearing the four-diamond Takeda crest (Takeda bishi) fluttering in the wind, standing on a rocky mountain ridge overlooking misty valleys and enemy army camps at sunrise, Sengoku Jidai feudal Japan, dramatic low-angle shot, golden morning light rays breaking through clouds, flying red maple leaves, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Illustration cinématographique d'un agile cavalier éclaireur samouraï Takeda, monté sur un cheval de montagne rapide (cheval Kiso), armure légère laquée rouge écarlate, jingasa cornu, lance yari et sabre, bannière dorsale aux 4 losanges Takeda, crête rocheuse surplombant les vallées et camps ennemis au lever du soleil, style ukiyo-e 8k.* |
| `cavalier_rouge_akazonae.jpg` | **Cavalerie Rouge Takeda** | *« Fierce mounted samurai cavalryman of Takeda clan, wearing terrifying bright crimson red armor (Akazonae), charging on a powerful warhorse, holding a yari spear, flying battle flags, cinematic motion »*<br><br>**🇫🇷 Traduction :** *Féroce cavalier samouraï du clan Takeda en armure rouge cramoisi intégrale (Akazonae), chargeant sur un destrier puissant, brandissant une lance yari, bannières claquant au vent.* |
| `belier_dragon_kai.jpg` | **Bélier Titanesque du Dragon de Kai** | *« Monumental ancient Japanese siege ram machine, colossal battering ram shaped like a ferocious roaring dragon head sculpted from blackened iron and bronze, spitting glowing embers and smoke from its nostrils, heavy fortified wooden carriage built of giant cedar timbers with layered damp leather and reinforced iron plating, iron-rimmed massive spiked wooden wheels rolling in muddy battlefield tracks, vermilion red war banners bearing the four-diamond Takeda clan crest (Takeda-bishi) fluttering on the roof, advancing aggressively toward the colossal stone gate of a besieged Japanese castle fortress at dusk, flying sparks, fiery arrows raining from the sky, misty battlefield atmosphere, dynamic cinematic wide low-angle shot, dramatic volumetric lighting, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Machine de siège colossale, bélier monumental taillé en forme de tête de dragon rugissant sculptée dans le fer noirci et le bronze crachant braises et fumée, lourd charriot blindé en cèdre géant et cuir humide, roues cloutées dans la boue, bannières rouges Takeda, avançant vers la porte de la forteresse au crépuscule sous une pluie de flèches enflammées, style ukiyo-e 8k.* |
| `shinobi_monte_tokugawa.jpg` | **Embuscade Shinobi Montée** | *« Dynamic cinematic illustration of a stealth Tokugawa clan shinobi assassin cavalryman mounted on a swift black warhorse with muffled hooves, wearing dark midnight indigo and obsidian shinobi robes with concealed light chainmail armor, menacing black mempo demon half-mask, drawing a razor-sharp steel ninjato blade from his back in mid-stride, smoke bomb canister releasing purple-tinted mist around the horse's legs, subtle Tokugawa triple-hollyhock crest (Mitsuba Aoi) embroidered on his dark sash, bursting out from a dense misty bamboo forest in a surprise night ambush, full moon shining through bamboo stalks casting dramatic moonlight shafts and deep shadows, flying bamboo leaves, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Cavalier assassin shinobi furtif du clan Tokugawa sur cheval noir aux sabots assourdis, robe indigo nuit et demi-masque de démon mempo, dégainant un ninjato en plein élan, grenade fumigène violette, blason Tokugawa sur la ceinture, jaillissant d'une forêt de bambous dans une embuscade nocturne au clair de lune, style ukiyo-e 8k.* |
| `catapulte_horokubiya_tokugawa.jpg` | **Catapulte Flamboyante Horokubiya** | *« Monumental ancient Japanese siege catapult traction trebuchet hurling glowing fiery ceramic explosive jars (Horokubiya), heavy fortified timber frame built of thick cypress beams with blackened iron fittings and counterweights, wooden launching arm in mid-motion releasing a blazing ceramic firepot trailing golden sparks and dark smoke across the twilight sky, Tokugawa clan ashigaru siege engineers in indigo armor operating tension ropes and torches, protective bamboo tate pavise mantlets in foreground bearing the Tokugawa three-hollyhock crest (Mitsuba Aoi), distant besieged Japanese castle keep on fire, embers and smoke drifting in the wind, dramatic cinematic low-angle action shot, warm fire glow and volumetric dusk lighting, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Trébuchet de siège monumental propulsant des bombes explosives incendiaires en céramique (Horokubiya) dans le ciel crépusculaire, ingénieurs ashigaru Tokugawa en armure indigo manœuvrant les cordages et torches, pavois en bambou au blason Tokugawa, château assiégé en feu au loin, style ukiyo-e 8k.* |
| `forteresse_roulante_tokugawa.jpg` | **Forteresse Roulante Blindée** | *« Monumental ancient Japanese rolling armored siege fortress, colossal multi-tiered wooden mobile bastion on massive iron-studded timber wheels, heavily armored walls built of thick oak logs reinforced with bolted iron and bronze plates, multiple narrow arrow slits and triangular gun ports with Tanegashima matchlocks and yari spears protruding, curved Japanese pagoda-style tiled roof with defensive parapet, fluttering deep purple and gold war banners bearing the Tokugawa clan triple-hollyhock crest (Mitsuba Aoi), samurai commanders in ornate black and gold armor directing the advance from the upper watchtower, rolling relentlessly across a muddy battlefield toward besieged enemy fortifications at sunset, flaming enemy arrows harmlessly deflecting off the heavy iron plating, dramatic low-angle perspective emphasizing its colossal size and invulnerability, smoke plumes and golden dust in the air, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Forteresse de siège roulante blindée colossale à étages sur roues géantes cloutées de fer, parois de chêne blindées de fer et bronze boulonnées, sabords de tir d'arquebuses et meurtrières de lances, bannières pourpres Tokugawa, commandants samouraïs au sommet, flèches enflammées ricochant, perspective en contre-plongée, style ukiyo-e 8k.* |
| `ombre_shinobi_infiltree.jpg` | **Ombre Shinobi Infiltrée** | *« Mysterious ninja shinobi assassin perched on a temple roof under full moon, dark indigo hooded robes, ninjato blade on back, throwing kunai, mist and bamboo shadows, stealth concept art »*<br><br>**🇫🇷 Traduction :** *Mystérieux ninja assassin shinobi accroupi sur le toit d'un temple sous la pleine lune, vêtement à capuche indigo sombre, lame ninjato dans le dos, lançant un kunai, brume et ombres de bambous, concept art furtif.* |

---

### 🐾 D. Bêtes Sauvages & Gardiens d'Oasis
| Fichier | Animal & Rang | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| `sanglier_sauvage.jpg` | **Sanglier Enragé des Monts (Tier 1)** | *« Dynamic cinematic illustration of a colossal feral wild boar (Sanglier Enragé des Monts) charging furiously through a misty ancient Japanese mountain forest, razor-sharp elongated tusks dripping with foam, bristling dark bristly fur covered in dirt and pine needles, furious glowing amber-red eyes, muscular hunched posture, kicking up volcanic gravel and snapping bamboo under heavy hooves, full moon peeking through cedar trees casting cold moonlight and deep atmospheric shadows, subtle Japanese feudal aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, highly detailed, dramatic lighting, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Illustration cinématographique d'un sanglier sauvage colossal chargeant furieusement dans une forêt de montagne japonaise embrumée, défenses acérées dégoulinantes d'écume, pelage sombre hérissé, yeux ambrés flamboyants, brisant des bambous sous ses sabots, pleine lune à travers les cèdres, style ukiyo-e 8k.* |
| `loup_honshu.jpg` | **Loup Vicieux de Honshu (Tier 2)** | *« Menacing pack of vicious Honshu wolves emerging from dense misty bamboo forest under cold full moon, alpha male in foreground with bared fangs and intense predatory yellow eyes, lean muscular posture, breath visible in freezing air, ancient Shinto stone lanterns covered in moss in background, traditional Japanese feudal mountain wilderness, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric dark rim lighting, highly detailed, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Meute menaçante de loups vicieux de Honshu émergeant d'une dense forêt de bambous brumeuse sous une pleine lune glaciale, mâle alpha au premier plan crocs découverts et yeux jaunes de prédateur, vapeur de souffle dans l'air gelé, lanternes de pierre shinto moussues, style ukiyo-e 8k.* |
| `ours_hokkaido.jpg` | **Grand Ours Brun de Hokkaido (Tier 3)** | *« Dynamic cinematic illustration of a colossal feral Hokkaido brown bear (Grand Ours Brun de Hokkaido, legendary giant Higuma), towering apex predator rising on its hind legs atop a jagged snow-covered mountain cliff, massive muscular frame with battle scars across its chest and snout, thick frosted dark-brown fur matted with ice and snow, roaring furiously with razor-sharp elongated claws slashing through the freezing air, steaming breath billowing from open jaws lined with lethal teeth, fierce glowing amber eyes, background featuring rugged northern Japanese Hokkaido peaks (Ezo), wind-swept frozen ancient pine trees and swirling snow blizzard under a dramatic overcast winter twilight sky, Japanese feudal folklore aesthetic, ukiyo-e woodblock inspired semi-realistic digital painting, heavy textural detail, epic sense of scale and raw primal power, dramatic rim lighting, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Illustration cinématographique d'un gigantesque ours brun de Hokkaido (Higuma), dressé sur une falaise enneigée, cicatrices de guerre sur le poitrail, fourrure givrée, rugissement furieux dans le blizzard des sommets d'Ezo, griffes acérées et vapeur d'expiration, style ukiyo-e 8k.* |

---

### 🏯 E. Les 12 Châteaux Authentiques du Japon (現存十二天守 - Jūni Tenshu)
| Fichier | Château & Surnom | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| `bitchu_matsuyama.jpg` | **Château de Bitchū Matsuyama**<br>*(Le Château dans le Ciel &bull; 430m)* | *« Cinematic epic illustration of Bitchu Matsuyama Castle, the legendary Japanese castle in the sky, perched atop the rugged crags of Mount Gagyu at 430 meters altitude, authentic two-story white plaster and cypress wood tenshu keep standing majestically above a dramatic sea of rolling morning clouds (unkai), ancient dry-stone retaining walls seamlessly built into natural granite cliffs, autumn foliage with scarlet momiji maple leaves clinging to rocky outcrops, warm golden sunrise breaking through misty horizon casting ethereal glows on white battlements, Japanese feudal Sengoku aesthetic, ukiyo-e woodblock inspired semi-realistic digital art, breathtaking aerial view, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Donjon de Bitchū Matsuyama perché à 430 m sur les falaises du mont Gagyū au-dessus d'une mer de nuages d'automne au lever de soleil, érables rouges et remparts granitiques, style ukiyo-e 8k.* |
| `hikone.jpg` | **Château de Hikone**<br>*(Diables Rouges Ii &bull; Lac Biwa)* | *« Cinematic majestic illustration of Hikone Castle, National Treasure of Japan, iconic three-story tenshu keep showcasing intricate gabled roofs combining curved karahafu and triangular irimoya-hafu architecture, white plaster walls adorned with golden crests, standing on a fortified hill overlooking the shimmering waters of Lake Biwa, red lacquered war banners (mon) of the Ii Clan 'Red Devils' fluttering in the breeze, historic stone bastions, spring cherry blossoms softly framing the fortress under a serene morning sky, subtle ukiyo-e woodblock inspired semi-realistic digital painting, highly detailed Japanese feudal craftsmanship, 8k »*<br><br>**🇫🇷 Traduction :** *Donjon de Hikone aux toitures ouvragées karahafu et irimoya-hafu, bannières rouges des Diables Rouges du clan Ii, surplombant le lac Biwa avec cerisiers en fleurs.* |
| `himeji.jpg` | **Château de Himeji**<br>*(Le Héron Blanc &bull; Shirasagi-jō)* | *« Grand panoramic cinematic illustration of Himeji Castle, the magnificent White Heron Castle (Shirasagi-jo), colossal five-tiered seven-story main tenshu keep linked by fortified covered galleries to three sub-towers (renritsu-shiki style), brilliant white fireproof plaster walls gleaming brilliantly like the wings of a giant white heron taking flight, complex labyrinth of stone ramparts, curved samurai gates and triangular arrow slits, misty twilight sky with soft pastel sunset tones, Japanese feudal fortress masterpiece, ukiyo-e woodblock influenced high-end semi-realistic digital art, stunning architectural detail, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Panoramique du château de Himeji, le Héron Blanc immaculé à 5 niveaux relié à 3 donjons secondaires, labyrinthe de remparts de pierre blanche et ciel crépusculaire pastel.* |
| `hirosaki.jpg` | **Château de Hirosaki**<br>*(Donjon Boréal &bull; Mont Iwaki)* | *« Atmospheric winter cinematic illustration of Hirosaki Castle, the northernmost original tenshu keep of Mutsu province, compact three-story wooden fortress dusted with crisp white snow on dark curved roof tiles, surrounded by frozen triple moats with cracked turquoise ice and snow-laden weeping pine trees, dramatic view of snow-capped volcanic peak Mount Iwaki rising in the background under cold winter daylight, subtle traditional red arched wooden bridge spanning the snowy moat, Japanese feudal northern aesthetic, ukiyo-e woodblock inspired semi-realistic art, crisp atmospheric chill, 8k »*<br><br>**🇫🇷 Traduction :** *Hirosaki sous la neige boréale, donjon poudré de givre, douves gelées à la glace turquoise, pont vermillon et pic volcanique enneigé du mont Iwaki.* |
| `inuyama.jpg` | **Château d'Inuyama**<br>*(Plus Vieux Donjon Bois 1537)* | *« Dramatic cinematic illustration of Inuyama Castle, the oldest standing original wooden tenshu in Japan dating back to 1537, perched atop a sheer 40-meter rocky promontory directly towering over the swirling emerald rapids of the Kiso River, dark aged timber construction and traditional white walls, top-floor open-air wooden observation balcony (mawari-en) with panoramic views, mist rising from rushing river waters, flight of cormorants skimming the river surface, Oda clan feudal war banners, ukiyo-e woodblock inspired semi-realistic digital painting, dramatic low-angle composition, 8k »*<br><br>**🇫🇷 Traduction :** *Inuyama dressé sur son éperon rocheux de 40 m au-dessus des rapides de la rivière Kiso, boiseries d'origine du XVIe siècle, balcon panoramique ouvert et pêcheurs aux cormorans.* |
| `kochi.jpg` | **Château de Kōchi**<br>*(Palais Honmaru Intact &bull; Tosa)* | *« Dramatic cinematic illustration of Kochi Castle in Tosa province, iconic historic complex uniquely featuring both the towering original tenshu keep and the preserved Honmaru Goten samurai residential palace, steep stone base fitted with curved iron anti-ninja climbing spikes (shinobi-gaeshi) and monumental stone water drainage gargoyles (mizu-kiri), dramatic stormy Pacific typhoon clouds brewing above, moody atmospheric lighting with wind whipping through subtropical sago cycad palms and ancient pine trees, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Kōchi avec son donjon et son palais seigneurial Honmaru Goten préservés, piques anti-shinobi sur les murailles et gargouilles de pierre sous un ciel d'orage de typhon du Pacifique.* |
| `marugame.jpg` | **Château de Marugame**<br>*(Murailles en Éventail de 60m)* | *« Epic wide-angle cinematic illustration of Marugame Castle, renowned for the tallest stone walls in Japan soaring over 60 meters in four sweeping stepped terraces, distinctive fan-sloped stone curvature (ogi-no-kobai) rising dramatically from the base to sheer vertical summits, compact white tenshu keep perched at the pinnacle, overlooking the tranquil sparkling Seto Inland Sea dotted with green island archipelagoes, golden hour sunlight hitting the weathered cut-stone masonry, ukiyo-e woodblock blended with epic cinematic realism, magnificent scale, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Marugame et ses 4 terrasses colossales de murailles de pierre de 60 mètres en courbure d'éventail (ōgi-no-kōbai), surmonté de son donjon blanc face aux îles de la mer intérieure de Seto.* |
| `maruoka.jpg` | **Château de Maruoka**<br>*(Château de la Brume &bull; Echizen)* | *« Mystical cinematic illustration of Maruoka Castle, the legendary 'Mist Castle' (Kasumi-ga-jo) in Echizen, archaic 16th-century feudal tenshu with steep rustic stone foundations, unique heavy roof crafted entirely from 6,000 blue-gray volcanic stone tiles (shakudani-ishi), mystical swirling dense white fog billowing around the fortress evoking the legendary giant serpent's protective mist, ancient lanterns glowing through the fog, eerie Sengoku-period atmosphere, ukiyo-e woodblock inspired semi-realistic digital painting, atmospheric chiaroscuro, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Maruoka enveloppé de brume mystique (la légende du serpent géant protecteur), toiture archaïque de 6 000 tuiles en pierre volcanique d'Asuwa et fondations rocheuses.* |
| `matsue.jpg` | **Château de Matsue**<br>*(Château du Pluvier &bull; Izumo)* | *« Cinematic noble illustration of Matsue Castle, the 'Plover Castle' (Chidori-jo), striking five-tiered black fortress cladded in dark soot-treated wooden rainboards (tsumi-ita), National Treasure of Izumo province standing on a green hill overlooking the vast waters of Lake Shinji, tranquil castle moats navigable by wooden samurai boats, flock of plover birds soaring across a moody silver twilight sky, ancient stone bridges and weeping willows, ukiyo-e woodblock inspired semi-realistic digital art, rich deep black and wood textures, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Matsue, forteresse noire austère bardée de bois traité au charbon, Trésor National sur la colline d'Izumo au bord du lac Shinji avec volée d'oiseaux pluviers au crépuscule.* |
| `matsumoto.jpg` | **Château de Matsumoto**<br>*(Château du Corbeau &bull; Alpes)* | *« Masterpiece cinematic illustration of Matsumoto Castle, the world-famous 'Crow Castle' (Karasu-jo), five-tier six-story black lacquered tenshu keep accompanied by the vermilion-lacquered Moon-Viewing Turret (Tsukimi-yagura), perfect mirror reflection cast across wide crystalline moats with swimming red koi fish, towering snow-covered Northern Japanese Alps mountain range dominating the background, clear crisp morning atmosphere, flock of black crows gliding over the castle roofs, ukiyo-e woodblock aesthetic fused with stunning cinematic clarity, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Matsumoto « Château du Corbeau », donjon d'ébène noir laqué et pavillon vermillon d'observation de la lune se reflétant dans les douves aux carpes koï, face aux Alpes japonaises.* |
| `matsuyama.jpg` | **Château de Matsuyama**<br>*(Mont Katsuyama &bull; 132m)* | *« Grand panoramic cinematic illustration of Matsuyama Castle in Iyo province, sprawling hilltop fortress atop Mount Katsuyama at 132 meters, intricate interconnected complex (renritsu-shiki) linking the three-story tenshu to multiple defensive watchtowers and fortified corridor gates, sweeping multi-tiered white plaster walls and grey tile roofs overlooking the historic plain of Dogo and the distant sea, lush green pine forest below, warm sunny afternoon sky with drifting clouds, ukiyo-e woodblock inspired semi-realistic digital painting, expansive vista, 8k »*<br><br>**🇫🇷 Traduction :** *Matsuyama perché sur le mont Katsuyama (132 m), complexe relié de 21 tours de guet et portes fortifiées, murailles blanches dominant la plaine de Dōgo et la mer de Seto.* |
| `uwajima.jpg` | **Château d'Uwajima**<br>*(Plan Pentagonal de Takatora)* | *« Cinematic coastal illustration of Uwajima Castle, masterwork of fortress genius Todo Takatora, elegant three-story tenshu with decorative curved shoin-style gables and dark wood accents, set within its ingenious secret pentagonal rampart layout designed to confuse besiegers, perched on a coastal hill directly overlooking the tranquil sapphire waters and fishing harbors of Uwajima Bay, seabirds gliding over the defensive walls, soft golden dusk light reflecting on the sea, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution »*<br><br>**🇫🇷 Traduction :** *Uwajima conçu par Tōdō Takatora avec son enceinte secrète en pentagone irrégulier, donjon à 3 étages dominant la baie maritime et les ports d'Uwajima au crépuscule.* |

---

## 10. Ateliers Pratiques à Réaliser Ensemble (Père & Fils)

Voici 4 petits défis passionnants pour expérimenter et programmer ensemble :

### 🎯 Défi 1 : Modifier la Vitesse du Jeu
- **Où chercher ?** Fichier [`core/GameConfig.php`](file:///var/www/opengalaxy/core/GameConfig.php) ou directement dans l'onglet **⚡ Vitesses & Jeu** de l'administration.
- **Expérience** : Passez la vitesse à `x10` ! Observez comment les rizières se remplissent 10 fois plus vite et comment les bâtiments sont terminés en quelques secondes.

### 🎯 Défi 2 : Inventer un Nouveau Bâtiment ou une Troupe
- **Où chercher ?** Fichier [`config/game_constants.php`](file:///var/www/opengalaxy/config/game_constants.php).
- **Expérience** : Regardez la liste `BUILDINGS` ou `UNITS`. Essayez de modifier la vitesse ou l'attaque du *Samouraï au Katana* pour voir son impact dans le rapport de combat !

### 🎯 Défi 3 : Générer une Nouvelle Image avec un Prompt
- **Où chercher ?** Utilisez un générateur d'images IA avec la formule apprise au [Module 8](#8-lart-du-prompt-engineering--créer-des-graphismes-avec-lia).
- **Expérience** : Inventez un prompt pour une créature légendaire japonaise (comme un *Renard Kitsune* ou un *Dragon d'Eau*) et placez l'image dans `public/assets/` !

### 🎯 Défi 4 : Lancer les Tests Automatisés
- **Où chercher ?** Dans le terminal Linux :
  ```bash
  php tests/test_demolish_building.php
  php tests/test_field_view.php
  ```
- **Expérience** : Regardez les petits symboles verts `✔` s'afficher un à un. C'est ainsi que les ingénieurs logiciels s'assurent que leur jeu fonctionne parfaitement sans le moindre bug !

---

## 11. Créer un Teaser Vidéo Épique avec l'IA (Animation & Cinématique)

Pour donner vie aux illustrations d'**OpenShogun**, les nouveaux générateurs de vidéo IA (comme *Runway Gen-3*, *Kling AI*, *Luma Dream Machine* ou *Hailuo*) permettent d'animer des scènes de bataille complètes. 

### 🎬 La Formule du Prompt Vidéo
En vidéo, l'IA a besoin de 4 informations essentielles :
1. **Mouvement de Caméra** : *Slow dramatic tracking shot* (suivi fluide), *low-angle push-in* (zoom avant en contre-plongée).
2. **Mouvements & Actions** : Ce qui bouge précisément (coursier au galop, gueule de dragon crachant des flammes, roues tournant dans la boue).
3. **Effets & Particules** : Étincelles (*flying sparks*), fumée (*smoke plumes*), feuilles de cerisier ou de bambou (*flying leaves*).
4. **Style Artistique Continu** : Maintenir l'esprit estampe *ukiyo-e semi-realistic digital art* pour que la vidéo ressemble au jeu.

---

### 🎥 Le Prompt Teaser de Bataille Ultime (Scène de 5 à 10 secondes)

> `Epic cinematic battle teaser trailer of feudal Japan Sengoku period. Slow dynamic low-angle tracking shot moving forward through a muddy battlefield. In the center, a colossal wooden dragon siege ram machine rolls forward on spiked iron wheels, its ferocious blackened-iron dragon head roaring and belching glowing sparks and smoke. Beside it, charging Takeda samurai cavalry in brilliant crimson red armor on galloping warhorses surge forward with raised spears. Above, a barrage of flaming arrows arcs across the smoky dusk sky towards a distant towering Japanese castle fortress. Flying fire embers, swirling autumn red leaves, dramatic volumetric sunset light breaking through war smoke, ukiyo-e woodblock inspired semi-realistic digital anime aesthetic, fluid motion, 8k masterpiece`

**🇫🇷 Traduction :**
> *Bande-annonce épique de teaser de bataille du Japon féodal, période Sengoku. Travelling avant fluide en contre-plongée dynamique à travers un champ de bataille boueux. Au centre, un gigantesque bélier de siège à tête de dragon en bois avance sur des roues cloutées de fer, sa féroce gueule de dragon rugissant et crachant étincelles et fumée. À ses côtés, la cavalerie samouraï Takeda charge en armures rouge écarlate étincelantes sur des destriers au galop, lances brandies. Au-dessus, une volée d'arcs de flèches enflammées traverse le ciel crépusculaire enfumé vers une forteresse de château japonais colossale au loin. Braises de feu virevoltantes, feuilles d'automne rouges tourbillonnantes, lumière volumétrique spectaculaire de coucher de soleil perçant la fumée de guerre, esthétique d'art numérique inspirée des estampes ukiyo-e, mouvement fluide, chef-d'œuvre 8k.*

---

### 🎞️ Le Mini-Storyboard en 4 Plans Utilisé pour le Teaser du Jeu

| Plan | Sujet & Action | Prompt Anglais & Traduction Française |
| :--- | :--- | :--- |
| **Plan 1 : L'Éveil** (3s) | Cavalier Éclaireur Takeda sur une falaise au lever du soleil | *« Cinematic low-angle shot of a Takeda samurai scout cavalryman on a cliff overlooking misty mountain valleys at sunrise. The horse snorts with visible breath, the back banner flutters in the morning wind, camera slowly pushes in, golden sunrays, ukiyo-e style. »*<br><br>**🇫🇷 Traduction :** *Plan cinématographique en contre-plongée d'un cavalier éclaireur samouraï Takeda sur une falaise dominant des vallées brumeuses au lever du soleil. Le cheval s'ébroue dans l'air frais, la bannière dorsale flotte dans le vent matinal, zoom avant lent, rayons dorés, style ukiyo-e.* |
| **Plan 2 : La Marche des Titans** (4s) | Le Bélier Dragon & la Forteresse Roulante sous les flèches | *« Slow-motion dynamic tracking shot of colossal rolling siege fortress and dragon ram advancing heavily in battlefield mud towards castle ramparts. Fiery arrows deflect off iron plates, the dragon iron head belches bursts of fire and sparks, massive spiked wheels turning, intense battlefield smoke, dramatic dusk lighting. »*<br><br>**🇫🇷 Traduction :** *Travelling dynamique au ralenti d'une forteresse roulante colossale et d'un bélier à tête de dragon avançant pesamment dans la boue vers les remparts. Flèches enflammées ricochetant sur le fer, gueule de dragon crachant feu et étincelles, roues géantes tournant, fumée dense au crépuscule.* |
| **Plan 3 : L'Embuscade Shinobi** (4s) | Embuscade du Shinobi monté jaillissant des bambous | *« Midnight darkness in misty bamboo forest. A masked shinobi cavalryman bursts forward on a black horse surrounded by purple smoke, drawing gleaming steel sword towards the camera, full moon shafts through bamboo, fast dynamic camera zoom. »*<br><br>**🇫🇷 Traduction :** *Obscurité nocturne dans une forêt de bambous embrumée. Un cavalier shinobi masqué jaillit sur un cheval noir dans un nuage de fumée violette, dégainant son sabre d'acier vers la caméra, pleine lune filtrant à travers les tiges de bambou, zoom avant rapide.* |
| **Plan 4 : Climax & Flèches de Feu** (5s) | La charge générale et le bombardement incendiaire | *« Epic cinematic battle teaser trailer of feudal Japan Sengoku period. Slow dynamic low-angle tracking shot moving forward through a muddy battlefield... charging Takeda samurai cavalry... barrage of flaming arrows arcs towards castle fortress, ukiyo-e woodblock aesthetic, 8k masterpiece. »*<br><br>**🇫🇷 Traduction :** *Charge générale épique de la cavalerie rouge Takeda aux côtés des engins de siège, sous un dôme de flèches enflammées zébrant le crépuscule en direction du château en flammes, tourbillon de braises et de poussière dorée.* |

---

## 12. Le Système de Mises à Jour Automatiques GitHub (CI/CD du Shogunat)

Pour maintenir le jeu à jour sans devoir exécuter manuellement des commandes dans le terminal Linux, un moteur de déploiement continu ([`core/UpdateEngine.php`](file:///var/www/opengalaxy/core/UpdateEngine.php)) a été conçu :

1. **Interrogation de l'API GitHub Compare** :
   Le serveur compare le commit local actif (`git rev-parse HEAD`) avec la branche `main` du dépôt GitHub officiel `dnau1973-hash/openshogun`.
2. **Détection des Nouveautés** :
   Si un nouveau commit existe, le nombre de commits de retard et la liste des messages de commits s'affichent automatiquement dans l'administration (**Onglet 🔄 Mises à Jour GitHub**).
3. **Déploiement Sécurisé en 1 Clic (`git pull`)** :
   Le clic sur **« 🚀 Télécharger & Déployer la Mise à Jour »** effectue :
   - Une sauvegarde préventive des modifications locales (`git stash`).
   - Le téléchargement et la fusion du nouveau code (`git pull`).
   - Le rafraîchissement immédiat du cache PHP (`opcache_reset`).
4. **Sécurité des Clés & Jetons (Secret Protection)** :
   Le jeton d'authentification personnel (`ghp_...`) est stocké dans un fichier local ignoré par Git ([`config/github.local.php`](file:///var/www/opengalaxy/config/github.local.php)), et masqué systématiquement dans tous les affichages et logs pour garantir une sécurité absolue.

---

*Document rédigé avec passion pour accompagner les jeunes créateurs dans le monde merveilleux du développement informatique et du jeu vidéo.*

