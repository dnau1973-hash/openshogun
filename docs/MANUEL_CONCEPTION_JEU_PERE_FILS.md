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
| Fichier | Nom | Prompt Utilisé pour l'IA |
| :--- | :--- | :--- |
| `shogun_castle_city_bg.jpg` | **Cité Castrale (Dorf 2)** | *« High angle aerial RTS village view of a feudal Japanese castle town, Sengoku period, centered on a massive stone Tenshu citadel, surrounding courtyards, dojo, stables, market streets, misty morning light, cherry blossoms, detailed isometric strategy game background, 4k digital matte painting »* |
| `shogun_rural_terroir_bg.jpg` | **Terroirs Ruraux (Dorf 1)** | *« Top-down RTS strategic resource landscape of rural feudal Japan, terraced flooded rice paddies reflecting blue sky, lush bamboo lumber forests, stone quarry cliffs, sacred mountain shrine river, miniature Travian-style tactical game surface, warm sunlight »* |
| `shogun_login_bg.jpg` | **Écran Titre & Connexion** | *« Majestic cinematic view of a Japanese samurai fortress at dawn, dramatic red sun rising behind mount Fuji, flying cranes, golden mist, traditional Japanese architecture, epic atmospheric game title screen »* |

---

### 🏯 B. Bâtiments & Tuiles Isométriques (Sprites PNG)
| Fichier | Bâtiment | Prompt Utilisé pour l'IA |
| :--- | :--- | :--- |
| `tile_tenshu.png` | **Donjon Tenshu** | *« Isolated isometric sprite of a monumental multi-tiered Japanese castle keep (Tenshu), black lacquered timber, white plaster walls, curved green tiled roofs, golden ornaments, on high stone foundation base, transparent background, RTS building asset »* |
| `tile_barracks.png` | **Dojo Militaire** | *« Isolated isometric sprite of a traditional feudal Japanese samurai dojo, training yard with weapon racks and wooden dummies, tiled pagoda roof, red clan banners, transparent background »* |
| `tile_market.png` | **Marché Féodal** | *« Isolated isometric feudal Japanese marketplace building, wooden stalls with textile canopies, rice baskets, lanterns, wooden carts, lively торговая plaza sprite, transparent background »* |
| `tile_riziere.png` | **Rizière Inondée** | *« Isolated game tile sprite of lush green terraced flooded rice field, clean wooden irrigation sluice channels, reflective clear water, vibrant green shoots, RTS terrain asset, transparent background »* |
| `tile_bucheron.png` | **Camp de Bûcherons** | *« Isolated game tile sprite of a Japanese lumberjack timber camp, cut cedar logs stacked neatly, rustic wooden shed with saws and axes, sawdust ground, transparent background »* |
| `tile_carriere.png` | **Carrière de Pierre** | *« Isolated game tile sprite of a stone quarry cutting into grey rock hillside, scaffolding, chisels, stone blocks ready for castle masonry, transparent background »* |
| `tile_sanctuaire.png` | **Sanctuaire Shintō** | *« Isolated game tile sprite of a vermilion red Shinto shrine pavilion, wooden waterwheel mill beside a bubbling stream, sacred shimenawa rope, peaceful spiritual garden, transparent background »* |
| `tile_wall.png` | **Muraille & Remparts** | *« Isolated isometric section of heavy feudal Japanese stone castle rampart, white plastered wall with arrow slits and triangular gun embrasures, grey tiled coping, transparent background »* |

---

### ⚔️ C. Régiments du Dojo & Héros
| Fichier | Unité | Prompt Utilisé pour l'IA |
| :--- | :--- | :--- |
| `hero_samurai.jpg` | **Héros Samouraï** | *« Epic heroic portrait of a venerable Sengoku Daimyo general in full ornate black and gold samurai armor, horned kabuto helmet, holding an ancient katana, wind blowing cherry blossom petals, dramatic moody lighting, character art »* |
| `piquier_ashigaru_yari.jpg` | **Piquier Ashigaru** | *« Feudal Japanese peasant ashigaru soldier holding an extremely long nagae-yari spear, black jingasa conical helmet, lacquered breastplate, determined expression, battlefield background, historical concept art »* |
| `arquebusier_oda_tanegashima.jpg` | **Arquebusier Tanegashima** | *« Oda clan matchlock musketeer aiming a wooden Tanegashima gun, smoke curling from the muzzle, bamboo tate barricade in foreground, glowing matchcord, dynamic battle action pose »* |
| `samourai_katana.jpg` | **Samouraï au Katana** | *« Master swordsman samurai in mid-stance drawing sharp steel katana blade, crimson and black silk cords on lamellar armor, fierce focus, flying embers, ukiyo-e digital illustration »* |
| `cavalier_eclaireur_takeda.jpg` | **Cavalier Éclaireur Takeda** | *« Dynamic cinematic illustration of an agile Takeda clan samurai scout cavalryman, mounted on a swift wild Japanese mountain horse (Kiso horse), wearing lightweight crimson red lacquered armor with vermilion cords, horned jingasa battle hat, carrying a scouting yari spear and a katana, small back banner (sashimono) bearing the four-diamond Takeda crest (Takeda bishi) fluttering in the wind, standing on a rocky mountain ridge overlooking misty valleys and enemy army camps at sunrise, Sengoku Jidai feudal Japan, dramatic low-angle shot, golden morning light rays breaking through clouds, flying red maple leaves, ukiyo-e woodblock inspired semi-realistic digital art, 8k resolution »* |
| `cavalier_rouge_akazonae.jpg` | **Cavalerie Rouge Takeda** | *« Fierce mounted samurai cavalryman of Takeda clan, wearing terrifying bright crimson red armor (Akazonae), charging on a powerful warhorse, holding a yari spear, flying battle flags, cinematic motion »* |
| `ombre_shinobi_infiltree.jpg` | **Ombre Shinobi** | *« Mysterious ninja shinobi assassin perched on a temple roof under full moon, dark indigo hooded robes, ninjato blade on back, throwing kunai, mist and bamboo shadows, stealth concept art »* |

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
*Document rédigé avec passion pour accompagner les jeunes créateurs dans le monde merveilleux du développement informatique et du jeu vidéo.*

