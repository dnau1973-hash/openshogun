# Journal des Modifications (Changelog) — OpenShogun

Toutes les modifications notables apportées à ce projet sont documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [1.34.7] - 2026-10-17
### Ajouté & Modifié (Added & Changed)
- **Refonte Ergonomique & Opérationnelle du Module Scénarios de Jeu — Studio Narrative Designer 2.0 (`core/ScenarioEngine.php`, `views/gamestudio.php`, `views/gamestudio/narrative_scenarios.php`) :**
  * **Catalogue Multi-Vues avec Micro-Interactions AJAX :**
    - **🗂️ Vue Cartes Interactive** : changement de statut du scénario en 1 clic AJAX et pastilles des 10 métiers cliquables directement sur la carte pour faire avancer le statut d'une tâche (`⏳ À faire ➔ 🔧 En cours ➔ ✅ Terminé ➔ ⛔ Bloqué`) avec recalcul temps réel de la barre de progression.
    - **⛩️ Vue Frise Chronologique des Steps (Roadmap Campagne)** : visualisation séquentielle `Step #1 ➔ Step #2 ➔ Step #3...` avec triggers d'entrée, harangue du personnage, récompenses et bouton « + Insérer le Step suivant ».
    - **📋 Vue Kanban Pipeline (5 colonnes)** : pilotage visuel du flux de production (*📝 Brouillon*, *🔍 En revue*, *⚙️ En production*, *✅ Prêt QA*, *🚀 En ligne*) avec boutons d'avancement rapide en AJAX.
  * **Studio d'Édition Côte-à-Côte (Split-Screen Live Simulator) & Assistants Métier :**
    - Fusion de l'éditeur narratif (colonne gauche) et du **Simulateur Parchemin In-Game en temps réel** (colonne droite sticky) avec compteur de caractères et bouton de test d'animation machine à écrire (*Typewriter*).
    - **Barre de Workflow Pipeline & Navigation Inter-Scénarios** dans l'en-tête sticky : stepper cliquable (`Brouillon ➔ En revue ➔ Production ➔ Prêt ➔ Live`), sélecteur rapide (`◀ / ▶`) pour naviguer d'un scénario à l'autre sans retourner au catalogue, indicateur *Dirty State* et sauvegarde AJAX (`Ctrl+S`) avec toast.
    - **Galerie de Personnages Sengoku (`ScenarioEngine::SPEAKER_PRESETS`)**, **Quick-Chips Triggers & Récompenses (`TRIGGER_PRESETS`, `REWARD_PRESETS`)** et **Éditeur de Choix Narratifs du Daimyō (1 à 3 décisions A/B)** persistés en SQL/JSON et exportés dans le GDD Markdown.
  * **Matrice Opérationnelle des 10 Métiers 2.0 :**
    - **Mode Compact 1-ligne avec tiroir repliable** par défaut + **Mode Kanban Opérationnel des Tâches** (4 colonnes : *À faire*, *En cours*, *Bloqué*, *Terminé*) et **filtres croisés** par Métier et par Statut.

## [1.34.6] - 2026-10-17
### Corrigé & Modifié (Fixed & Changed)
- **Réimplantation et Enrichissement des Sous-Menus Détaillés au Clic des 16 Indicateurs du Hub des Ressources (`views/partials/header.php`) :**
  * **Correction du Rognage (Clipping) des Sous-Menus dans le Ruban Glissant (`#hudResTrack`) :**
    - Ajout de la classe `.hud-floating-menu` (`position: fixed !important` avec variables CSS `--hud-menu-top` et `--hud-menu-left`) couplée à `data-bs-display="static"` afin d'empêcher Popper.js d'écraser le positionnement fixe et d'éviter que le conteneur `overflow-x: auto; overflow-y: hidden` ne masque les sous-menus au clic.
  * **Niveaux Exacts des Bâtiments Urbains, Production Approfondie & Raccourcis Rapides sur les 16 Pilules :**
    - **Ressources de base & Sérénité** (`Bois`, `Granit`, `Argile`, `Riz`, `Thé`, `Soja`, `Sérénité`) : affichage du niveau de la parcelle rurale (`Niv. X/30`), du niveau de l'entrepôt/grenier (`Niv. X/20`), du niveau et bonus de l'atelier urbain associé (`Scierie`, `Tailleur`, `Meunerie`, `Pavillon de Thé`), de la cadence `/h`, de la projection `/jour`, de l'échéance de saturation et des boutons de raccourcis rapides vers le terroir et le bâtiment.
    - **Matériaux, Minerais & Vivres raffinés** (`Poutres`, `Charbon`, `Satetsu`, `Soufre`, `Nitrate`, `Poisson`, `Farine`, `Saké`) : affichage du niveau du bâtiment producteur (`Scierie`, `Grande Forge`, `Tailleur`, `Grenier Kura`, `Meunerie & Brasserie`), du statut de production / palier requis (`Niv. 5` Farine, `Niv. 10` Poutres/Saké) et des raccourcis directs vers les bâtiments.
    - **Peuple & Domaine** : affichage du niveau du **Tenshu (Donjon Castral)** (`Niv. X/20`) et ajout des boutons d'accès direct au Tenshu (`?page=building&code=hq`) et à la Cité (`?page=city`).

## [1.34.5] - 2026-10-17
### Modifié (Changed)
- **Évolution du Hub des Ressources sous le Carrousel &bull; Ruban Glissant Uniforme à 16 Pilules & Flèches de Navigation (`views/partials/header.php`) :**
  * **Uniformisation de Toutes les Ressources et Indicateurs sur le Modèle `.hud-res-pill` :**
    - Intégration directe dans le ruban des pilules **Poutres de Charpente** (`#pillResBeams`), **Charbon de Bois** (`#pillResCharcoal`), des **Minerais** (**Satetsu / Sable de Fer** `#pillResIronSand`, **Soufre Volcanique** `#pillResSulfur`, **Nitrate de Potasse** `#pillResNitrate`), des **Vivres raffinées** (**Poisson Frais** `#pillResFish`, **Farine de Riz** `#pillResFlour`, **Saké Impérial** `#pillResSake`) ainsi que **Peuple & Domaine** (`#dropdownDomaine`) sur le même gabarit visuel que les ressources de base.
  * **Navigation par Flèches Gauche/Droite sans Barre de Scroll Visible :**
    - Ajout des boutons `#hudScrollLeft` et `#hudScrollRight` aux extrémités gauche et droite de la barre pour faire glisser les indicateurs avec fluidité (`scrollBy` smooth), tout en masquant intégralement la barre de défilement native (`scrollbar-width: none`, `::-webkit-scrollbar { display: none }`).
    - Positionnement dynamique en `position: fixed` des fiches détaillées au clic afin d'éviter toute coupure par le conteneur glissant.
  * **Alignement Graphique Gauche/Droite sur les Cartes du Corps de Page :**
    - Alignement strict du conteneur du hub (`container-fluid px-3 px-lg-4`) sur celui de `.page-body` juste en dessous pour une cohérence géométrique parfaite.

## [1.34.4] - 2026-10-16
### Modifié (Changed)
- **Refonte Ergonomique de la Barre HUD des Ressources sous le Carrousel (`views/partials/header.php`) :**
  * **Affichage Direct en Un Coup d'Œil des 7 Indicateurs Fondamentaux :**
    - Remplacement des 4 boutons opaques par un bandeau HUD affichant directement 7 pilules cliquables côte à côte : **Bois** (`metal`), **Granit** (`crystal`), **Argile** (`clay`), **Riz** (`deuterium`), **Thé** (`tea`), **Soja** (`soybean`) et **Sérénité** (`energy`).
    - Chaque pilule affiche le stock en temps réel (`res-val-*`), la cadence horaire (`+X/h`), le taux de remplissage (`%`) et une jauge dynamique (`bar-*`).
  * **Fiches Techniques Détaillées à la Demande (Progressive Disclosure) :**
    - Au clic sur chacune des 7 pilules, ouverture d'une fiche synthétique indiquant le stock exact / plafond d'entrepôt, l'estimation du temps avant saturation (`Saturé dans ~Xh Ym`), le niveau du terroir rural, les bonus d'ateliers urbains (Scierie, Tailleur de Pierre, Meunerie, Pavillon de Thé), d'Oasis, de Héros et de Matsuri, l'efficacité globale et la projection sur 24h, avec boutons d'accès direct.
  * **Regroupement Compact à Droite en 2 Menus Secondaires :**
    - Menu **`Artisanat & Minerais`** : regroupe par sections les 8 ressources secondaires (*Poutres de Charpente, Charbon de Bois, Sable de Fer, Soufre Volcanique, Nitrate de Potasse, Poisson Frais, Farine de Riz, Saké Impérial*) en conservant tous leurs compteurs JS temps réel.
    - Menu **`Peuple & Domaine`** : affiche dès le bouton l'indice de moral, la population et les Kobans, et déroule l'ordre féodal Shinōkōshō, la main-d'œuvre, le moral et le Trésor Impérial.

## [1.34.3] - 2026-10-16
### Corrigé (Fixed)
- **Verrouillage Strict des Paliers Meunerie &bull; Interdiction Totale de Production au Niveau 0 & Avant Paliers Requis (`core/PlanetEngine.php`, `views/building.php`) :**
  * **Suppression du Contournement Admin sur les Prérequis de Niveau :**
    - Résolution du bug où un compte administrateur pouvait lancer la production de farine ou de saké même lorsque la Meunerie & Brasserie était au niveau 0 ou non bâtie.
    - Dans `core/PlanetEngine.php` (`craftRiceProduct`), suppression de l'exemption `$isAdmin` sur les paliers de niveau : le Niveau 5 pour la farine et le Niveau 10 pour le saké sont désormais obligatoires et infranchissables pour tous les comptes.
  * **Verrouillage Strict de l'Interface (`views/building.php`) :**
    - Au niveau 0 (`$lvl === 0`), masquage complet de la section d'artisanat et du bouton d'accès rapide.
    - Affichage strict des cadenas et jauges de progression tant que le niveau 5 (Farine) ou 10 (Saké) n'est pas atteint, sans possibilité de déclencher de lot prématurément.

## [1.34.2] - 2026-10-16
### Modifié (Changed)
- **Meunerie & Brasserie de Riz &bull; Paliers de Production Échelonnés (Farine au Niv. 5, Saké au Niv. 10) (`core/PlanetEngine.php`, `views/building.php`, `views/docs.php`) :**
  * **Échelonnement de la Mouture de Farine de Riz (Niveau 5) :**
    - Dans `core/PlanetEngine.php`, le déblocage du raffinage de farine de riz (`rice_flour`) est abaissé au **Niveau 5** de la Meunerie & Brasserie (au lieu du Niveau 10).
    - Dans `views/building.php`, l'Atelier 1 affiche le déblocage et la jauge de progression calés sur le Niveau 5 (`X / 5`).
    - Le bouton d'accès rapide en haut de page du bâtiment devient actif dès le Niveau 5.
  * **Maintien de la Fermentation du Saké Féodal (Niveau 10) :**
    - Les cuves de fermentation traditionnelle du saké (Sakagura) restent exigeantes et réservées au **Niveau 10** de l'édifice castral.
  * **Contrôles d'Intégrité Backend & Feedback UI :**
    - Blocage côté serveur explicite avec message d'erreur ciblé selon le produit sollicité (`La Meunerie & Brasserie doit être érigée au Niveau 5 pour produire la farine de riz`, `au Niveau 10 pour le saké`).
    - Badges d'état dans l'en-tête de l'atelier reflétant les 3 états : *Verrouillé (< 5)*, *Farine Débloquée (5-9)*, *Farine & Saké Débloqués (10+)*.
    - Ajout du comparatif d'élévation `grain_mill` dans les statistiques d'aperçu de niveau.

## [1.34.1] - 2026-10-16
### Modifié (Changed)
- **Migration & Intégration des Chroniques Féodales & Événements Hebdomadaires dans Game Studio &bull; Game Elevate Designer (`views/gamestudio.php`, `views/gamestudio/elevate_events.php`, `views/gamestudio/elevate_weekly_events.php`, `views/studio/game-elevate-designer/weekly-chronicles.php`, `views/admin.php`, `views/partials/admin_weekly_events.php`, `api/weekly_events.php`) :**
  * **Intégration au Pôle Game Elevate Designer :**
    - Migration complète du panneau de scénarisation et planification des événements hebdomadaires depuis le tableau de bord administrateur général vers Game Studio (`?page=gamestudio&tab=elevate_events`).
    - Nouvel onglet et lien dédié dans le menu déroulant *Game Elevate Designer* de la navbar Game Studio.
    - Architecture modulaire calquée sur les autres outils de conception (`views/studio/game-elevate-designer/weekly-chronicles.php`).
  * **Redirection & Préservation Rétrocompatible sur la Page Admin :**
    - Suppression de l'onglet direct `events` dans la barre de navigation de `views/admin.php`.
    - Redirection HTTP 302 transparente depuis `?page=admin&tab=events` (ainsi que les alias `chroniques`, `evenements`, `event`) directement vers `?page=gamestudio&tab=elevate_events`.
    - Dans le sous-menu « Autres modules » d'administration, ajout d'un raccourci fléché identifié *« Chroniques Féodales [Game Studio] »*.
    - Pont rétrocompatible conservé dans `views/partials/admin_weekly_events.php`.
  * **Accréditations Étendues (`api/weekly_events.php`) :**
    - Les concepteurs et membres de la Dev Team habilités dans Game Studio disposent désormais des droits de création, modification et activation des chroniques historiques et des actes narratifs via l'API.

## [1.34.0] - 2026-10-16
### Ajouté (Added)
- **Tour de Contrôle Producteur & Live Ops dans Game Studio (`core/LiveOpsEngine.php`, `views/gamestudio.php`, `views/gamestudio/liveops_*.php`, `views/maintenance.php`, `views/partials/header.php`, `index.php`) :**
  * **Module Métier Unifié LiveOps (`core/LiveOpsEngine.php`) :**
    - Moteur centralisé assurant la persistance sécurisée des configurations en JSON (`config/feature_flags.json`, `config/live_events.json`, `config/liveops_broadcast.json`, `config/maintenance.json`).
    - Mécanismes de calcul d'impact direct sur l'économie féodale (boosts de production, accélération des chantiers, marche des armées, points d'honneur).
    - Table d'audit `liveops_compensations_log` et distribution automatisée de courriers impériaux dans la boîte aux lettres du jeu (`messages`).
  * **Événements Live & Saisons Impériales (`views/gamestudio/liveops_events.php`) :**
    - Gestionnaire d'événements à durée limitée modifiant les coefficients globaux du jeu en temps réel.
    - 4 préconfigurations féodales prêtes à l'emploi : *Moissons Sakura* (+50% récolte riz & bois), *Furie guerrière de Kai* (+30% vitesse armées & +20% honneur), *Bâtisseurs d'Oda* (+25% vitesse chantiers), *Grande Forge de Tamahagane* (+40% récolte pierre/fer).
    - Formulaire d'événement sur-mesure et arrêt anticipé en un clic.
  * **Feature Flags & Roadmap Stratégique (`views/gamestudio/liveops_features.php`) :**
    - Interrupteurs On/Off à chaud permettant d'activer ou désactiver des fonctionnalités sans redéploiement (`p2p_market`, `narrative_storyboard`, `bot_ai_clans`, `gallery_art`, etc.).
    - Tableau de bord de suivi de la feuille de route du projet (Statuts, Priorités, Sprints et Progrès).
  * **Broadcast In-Game & Mode Maintenance Impériale (`views/gamestudio/liveops_broadcast.php`, `views/maintenance.php`, `views/partials/header.php`, `index.php`) :**
    - Bannière flottante défilante en temps réel affichée au sommet de toutes les pages pour les annonces d'urgence ou de célébration.
    - Mode maintenance avec interception automatique dans `index.php`, whitelist d'administrateurs avec accès garanti, et page de veille immersive féodale `views/maintenance.php` avec Torii animé et horodatage prévisionnel.
  * **Support Joueurs & Décrets de Compensation Impériale (`views/gamestudio/liveops_support.php`) :**
    - Outil de recherche instantané de Daimyōs (par pseudonyme, e-mail ou nom de fief).
    - Versement de dédommagements ciblés ou collectifs (à l'ensemble des seigneurs du royaume) avec boutons d'incréments rapides (+5k, +20k, +50k bois/pierre/riz, Kobans, Honneur).
    - Émission automatique d'une missive impériale officielle et journalisation indélébile dans l'historique d'audit.

## [1.33.2] - 2026-10-16
### Corrigé (Fixed)
- **Plafond Castral & Apogée des Bâtiments &bull; Blocage des Évolutions au Niveau Max (`core/BuildingEngine.php`, `views/building.php`, `views/city.php`) :**
  * **Interdiction stricte des évolutions au-delà du niveau maximal :**
    - Dans `core/BuildingEngine.php`, ajout d'un contrôle de validation dans `startUpgrade()` et `getUpgradeDetails()` empêchant toute tentative de passage au niveau supérieur lorsque le bâtiment a déjà atteint son apogée maximale (`max_level`, par exemple Niveau 20 pour le Tenshu et la Grande Forge du Tamahagane, ou Niveau 15 pour le Radar et la Cachette).
    - Déclenchement d'une exception explicite côté backend, protégeant l'intégrité du jeu contre toute soumission non autorisée (y compris pour les administrateurs).
  * **Affichage de l'Apogée Castrale dans la page Building (`views/building.php`) :**
    - Lorsque le bâtiment est au niveau maximal, les cartes de statistiques en haut de page affichent désormais fièrement *« Apogée Maximale (Niveau 20 Atteint) »* et *« Structure Parachevée »* au lieu de suggérer un Niveau 21 inexistant.
    - La carte des coûts de matériaux est remplacée par une bannière commémorative d'apogée impériale confirmant que la structure fonctionne à plein régime sans besoin de ressources supplémentaires.
    - Le décret de construction affiche une alerte d'apogée castrale et un bouton verrouillé *« Niveau Maximum Atteint (20 / 20) »*, supprimant tout bouton d'élévation vers le niveau 21.
    - La jauge de main-d'œuvre requise ne projette plus d'ouvriers pour un niveau supérieur une fois le comble atteint.
  * **Indicateurs d'Apogée sur la Cité (`views/city.php`) :**
    - Dans la grille des bâtiments urbains, les structures ayant atteint leur niveau max arborent un badge distinctif vert *« Niveau 20 (Max) »* et un bouton sobre *« Niveau Maximum »* redirigeant vers la gestion de l'édifice sans incitation à l'amélioration.

## [1.33.1] - 2026-10-16
### Corrigé (Fixed)
- **Privilège Administrateur &bull; Construction et Amélioration Instantanées sans Coût (`core/BuildingEngine.php`, `core/RuralPlotEngine.php`, `core/PlanetEngine.php`, `core/BarracksEngine.php`, `core/ShipyardEngine.php`, `core/ResearchEngine.php`, `api/build.php`, `views/building.php`, `views/city.php`, `views/field.php`, `views/partials/urban_construction_queue.php`, `public/js/app.js`, `public/js/rural_domain_map.js`) :**
  * **Résolution de l'incompatibilité de schéma SQL sur le rôle Administrateur :**
    - Remplacement des requêtes obsolètes ciblant une colonne `role` inexistante dans la table `users` par la colonne réelle `is_admin TINYINT(1)` et la méthode `Auth::isAdmin()`.
    - Correction des méthodes `isUserAdmin()` et `isPlanetAdmin()` dans l'ensemble des moteurs (`BuildingEngine`, `RuralPlotEngine`, `PlanetEngine`, `BarracksEngine`, `ShipyardEngine`, `ResearchEngine`).
  * **Déblocage Frontend & Backend des Chantiers Instantanés pour Administrateurs :**
    - **Urbain & Fief Castral :** Les administrateurs peuvent désormais lancer la construction et l'amélioration de n'importe quel bâtiment ou parcelle même avec des réserves de ressources nulles (métal/bois, cristal/pierre, deutérium/riz à 0). Le chantier s'achève immédiatement sans temps d'attente et sans déduction de ressources.
    - **Parcelles Rurales :** Dans la vue panoramique du domaine rural (`views/resources.php` et `public/js/rural_domain_map.js`), le bouton d'élévation est systématiquement actif pour les administrateurs (`⚡ [Admin] Élévation Immédiate`) et déclenche la complétion immédiate sans coût via l'API.
    - **Fin Immédiate Gratuite (0 Koban) :** Pour tout chantier en cours dans la file (urbaine ou rurale), le bouton d'achèvement instantané affiche « Gratuit » pour les administrateurs et ne consomme aucun Koban.

## [1.33.0] - 2026-10-15
### Ajouté (Added)
- **Marché Féodal &bull; Bourse d'Échanges de Troc P2P avec Séquestre Débloquée au Niveau 10 (`core/MarketEngine.php`, `api/market.php`, `views/building.php`, `views/partials/market_p2p_tab.php`) :**
  * **Comptoir de Troc Décentralisé entre Seigneurs (P2P) :**
    - Dès que le Marché castral atteint le **Niveau 10**, un nouvel onglet *« Bourse Féodale (Troc P2P) »* se débloque.
    - Dépôt d'offres de troc inter-joueurs avec ratios d'échange entièrement personnalisables (ex. *500 Argile contre 1 000 Bois*).
    - Système de **séquestre immédiat (escrow lock)** : dès le dépôt, les ressources vendues sont déduites des greniers et consignées en coffre sécurisé dans `market_offers`, les rendant inaccessibles pour la construction ou le jeu tant que l'offre n'est pas conclue ou annulée.
    - Nombre de slots d'offres simultanées indexé sur le niveau du Marché : 3 slots au Niveau 10, puis +1 slot supplémentaire tous les 2 niveaux d'élévation.
    - Consultation des offres actives avec filtrage par ressource offerte et ressource demandée, calcul automatique du taux de change (ratio), statut d'éligibilité selon les stocks en réserve et conclusion instantanée de l'échange en un clic avec notification messagère.
    - Annulation d'offre en cours avec restitution intégrale et immédiate des ressources bloquées dans les entrepôts du fief.

### Modifié (Changed)
- **Mise en Page de la Cité & Bâtiments &bull; Refonte Fluide Pleine Largeur en 1 Colonne (`views/building.php`) :**
  * **Harmonisation Responsiviste en Colonne Unique :**
    - Transition de l'ancien découpage en deux colonnes asymétriques (`col-lg-5` / `col-lg-7` ou `col-lg-4` / `col-lg-8`) vers un flux vertical continu en **colonne unique `col-12`**.
    - Carte d'identité héroïque horizontale du bâtiment (`row g-0`) combinant l'estampe agrandie ou icône monumentale, le badge de niveau, la main-d'œuvre requise, la description narrative, les raccourcis d'action directe et l'ordre de démantèlement.
    - Suppression du bloc encombrant *« Emplacements de la Cité »* pour aérer l'espace et focaliser la lecture sur l'édifice consulté.
    - Alignement parfait et correction du décalage des onglets de navigation du Marché Féodal avec padding supérieur aéré (`pt-4`).
- **Game Studio &bull; Nettoyage de la Barre d'Outils et Alignement à Gauche (`views/gamestudio.php`) :**
  * **Optimisation de la Barre Supérieure du Studio :**
    - Retrait des éléments superflus de la barre d'outils : suppression de l'accès au fief, de l'accès à l'administration, du lien direct Dev Team et des filtres temporels (*Aujourd'hui, 7 jours, 30 jours*).
    - Alignement uniforme de l'ensemble des menus métiers (*Dashboard, Général, Game Elevation Designer, Narrative Designer*) sur la gauche pour une ergonomie de studio de développement limpide.

### Corrigé (Fixed)
- **Académie des Savoirs & Forge Militaire &bull; Déblocage des Recherches par Caste Sociale (`core/SocialCasteEngine.php`, `core/ResearchEngine.php`) :**
  * Correction du déblocage des technologies féodales : prise en compte rigoureuse des paliers de caste sociale et de civilisation atteints pour lever les verrous sur l'arbre de recherche.
- **Intendant du Marché Castral &bull; Troc NPC 1:1 et Tolérance de Production Passive (`core/ImperialSealEngine.php`) :**
  * Résolution de l'erreur `Le total rééquilibré (...) doit être strictement égal au total actuel` : appel préalable à `PlanetEngine::updatePlanet()` pour synchroniser l'état des stocks réels.
  * Tolérance adaptative sur l'accumulation naturelle de production horaire survenue pendant la manipulation des jauges par le joueur (redistribution automatique du delta naturel) et mise à jour de l'horodatage `last_resource_update`.
- **Marché Castral &bull; Éléments d'Amélioration & Schéma SQL (`core/MarketEngine.php`, `core/WeeklyEventEngine.php`, `views/building.php`) :**
  * Correction de la colonne `building_type` (au lieu de `building_code`) dans les requêtes de `planet_buildings` prévenant l'erreur `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'building_code' in 'WHERE'`.
  * Restauration des cartes de coûts et boutons de décret d'élévation sur la fiche du marché castral.

## [1.32.0] - 2026-10-15
### Ajouté (Added)
- **Game Studio &bull; Pôle Narrative Designer & Gestionnaire de Storyboard Multi-Projets (`views/gamestudio.php`, `core/StoryboardEngine.php`, `api/storyboard.php`, `views/studio/narrative-designer/storyboard-manager.php`, `public/assets/bibliotheque/`) :**
  * **Intégration du Nouveau Métier « Narrative Designer » dans Game Studio :**
    - Ajout du menu déroulant dédié *Narrative Designer* (icône `fa-feather-pointed` violette) dans la barre de navigation du Studio.
    - Deux modules accessibles : le *Gestionnaire de Storyboard* (`tab=narrative_storyboard`) et la *Charte Narrative & Lore Féodal* (`tab=narrative_lore`).
  * **Banc de Montage Vidéo Linéaire (Timeline Horizontale Continue sans Retour Chariot) :**
    - Refonte complète de la timeline en véritable piste de montage vidéo linéaire continue (`flex-wrap: nowrap; overflow-x: auto;`) inspirée des logiciels NLE professionnels (*Premiere Pro, DaVinci Resolve*).
    - Aucun retour à la ligne : les clips de plans forment une séquence continue de gauche à droite sur des cartes calibrées de 340px.
    - Connecteurs de transition visuels cliquables entre chaque plan (*Cut franc, Fondu croisé, Fondu au noir*).
    - Barre de défilement horizontal sombre et fluide, boutons de contrôle rapide (*⏮ Début, ◀ Recul, Avance ▶, ⏭ Fin*) et prise en charge naturelle de la molette de souris (`wheel`) pour scroller horizontalement.
  * **Téléversement d'Illustrations Personnalisées (`assets/bibliotheque/`) :**
    - Création du répertoire de stockage officiel `public/assets/bibliotheque/` pour accueillir les créations visuelles de la Dev Team.
    - Action API sécurisée `action=upload_illustration` dans `api/storyboard.php` (contrôle MIME `image/jpeg`, `image/png`, `image/webp`, limite 12 Mo, assainissement de nom et sécurisation des droits).
    - Zone de glisser-déposer (Dropzone) intégrée dans la modale du sélecteur d'illustrations avec bouton de parcours de fichiers et jauge de progression.
    - Support du glisser-déposer direct depuis l'ordinateur sur la vignette d'un clip pour uploader et assigner l'image en 1 étape.
    - Indexation automatique de la catégorie *« Bibliothèque »* dans le sélecteur modal et dans la Galerie d'Art globale (`views/gallery.php`).
  * **Gestion Multi-Storyboards (Espace de Travail & Projets Parallèles) :**
    - Prise en charge de plusieurs storyboards en cours d'élaboration avec sélecteur de projet instantané.
    - Création de nouveaux projets de storyboards avec typologie (*Trailer officiel, Démo de gameplay, Teaser d'événement, Cinématique de quête, Clip réseaux*), ratio d'image (16:9, 9:16, 1:1, 4:3), durée cible et piste audio.
    - Duplication en 1 clic pour tester des variantes de montage sans écraser l'original, renommage et suppression sécurisée.
    - Pré-chargement de 3 storyboards féodaux de référence : *Trailer Officiel du Marché de la Cité Castrale (17s)*, *Teaser Événement de la Grande Forge du Tamahagane (18s)*, et *Démo Gameplay de l'Arpentage (20s)*.
  * **Timeline Interactive & Drag & Drop Natif des Plans :**
    - Piste temporelle graphique avec repères et blocs proportionnels à la durée de chaque plan.
    - Réorganisation instantanée de la séquence par glisser-déposer HTML5 natif ultra-fluide avec recalcul en direct des timecodes cumulés (`00:00.0 → 00:04.5`, etc.).
    - Ajustement rapide de la durée de chaque plan par pas de `0.5s` ou saisie libre, impactant automatiquement l'ensemble de la timeline en aval.
    - Notes de mise en scène, choix du mouvement de caméra (travelling, panoramique, gros plan, vue aérienne), effets sonores et type de transition (cut, fondu au noir, fondu enchaîné).
  * **Sélecteur d'Illustrations Directement Connecté à la Galerie d'Art (238 Actifs) :**
    - Modale de sélection visuelle d'images puisant directement dans les 9 répertoires du jeu (*bâtiments, carrousel, châteaux, clans, événements, objets, ressources, tuiles, unités*) avec moteur de recherche textuelle temps réel.
  * **Storyboard Player & Preview Cinématique Plein Écran :**
    - Simulateur vidéo en temps réel faisant défiler automatiquement les plans et les visuels selon la cadence programmée.
    - Affichage des sous-titres de voix off et description visuelle avec barre de progression temporelle et commandes Play, Pause, Précédent, Suivant, Recommencer.
  * **Générateur Automatique de Prompts IA Vidéo :**
    - Production instantanée du Master Prompt global et des prompts découpés plan par plan rédigés en anglais cinématographique pour Runway Gen-3, Sora, Kling et Luma.
  * **Analyseur de Rythme Narratif (KPIs en Direct) :**
    - Indicateur de durée cumulée vs durée cible, cadence moyenne par plan et calcul du débit de parole de la voix off en Mots Par Minute (WPM).
  * **Export de Script Markdown :**
    - Génération et téléchargement en un clic de la fiche de tournage complète au format Markdown.

## [1.31.0] - 2026-10-15
### Ajouté (Added)
- **Galerie d'Art Féodale & Photogrid Officiel (`views/gallery.php`, `index.php`, `views/partials/footer.php`) :**
  * **Photogrid Haute Définition selon le Modèle Tabler.io :**
    - Création d'une interface responsive (`col-6 col-sm-4 col-md-3 col-lg-2`) affichant l'intégralité des 238 illustrations du jeu réparties dans les 9 répertoires d'actifs (`assets/buildings`, `carrousel`, `castles`, `clans`, `events`, `items`, `resources`, `tiles`, `units`).
    - Format carré parfait (`ratio ratio-1x1`) avec zoom progressif au survol, overlay translucide avec badge thématique, bouton loupe HD et nom de fichier.
  * **9 Domaines Graphiques Balayés Dynamiquement :**
    - *Bâtiments Castraux (`buildings`, 17 estampes) :* Casernes, forges, dojos, tenshu et édifices du fief.
    - *Panoramas & Carrousel (`carrousel`, 50 estampes) :* Fresques panoramiques 16:9 et décors du Sengoku.
    - *Châteaux & Forteresses (`castles`, 12 estampes) :* Himeji, Hikone, Inuyama, Bitchū Matsuyama et hauts lieux fortifiés.
    - *Clans & Armoiries (`clans`, 3 estampes) :* Conseils de guerre et bannières des maisons Oda, Takeda et Tokugawa.
    - *Événements Féodaux (`events`, 52 estampes) :* Chroniques saisonnières des 52 semaines de jeu.
    - *Objets & Reliques (`items`, 36 estampes) :* Armures historiques, montures de guerre, armes et objets de prestige.
    - *Ressources & Terroirs (`resources`, 11 estampes) :* Rizières, cèdre, pierre, ferveur shinto et gisements d'argile.
    - *Tuiles de Terroir (`tiles`, 21 estampes) :* Composants isométriques de l'arpentage et parcelles de village.
    - *Armées & Unités (`units`, 36 estampes) :* Samouraïs, archers, cavalerie, ninjas et le Mineur des Cimes (*Kōfu*).
  * **Filtres Avancés & Moteur Client :**
    - Barre d'onglets / pilules Tabler (`.nav-pills`) avec icônes distinctes, badges de comptage par catégorie et description contextuelle.
    - Recherche textuelle en temps réel par mot-clé, nom de fichier ou catégorie sans rechargement de page.
    - Sélecteur de tri dynamique (Nom A-Z, Nom Z-A, Poids croissant, Poids décroissant).
    - Compteurs de métriques instantanés (illustrations recensées, dossiers thématiques, poids total, œuvres actuellement filtrées).
  * **Lightbox Modale Plein Écran Interactive :**
    - Affichage de l'estampe en haute définition avec flèches de navigation Précédent / Suivant et raccourcis clavier (`Flèche Gauche`, `Flèche Droite`, `Échap`).
    - Métadonnées complètes : titre noble humanisé, catégorie, nom de fichier, extension, poids en Ko/Mo et chemin d'accès.
    - Bouton interactif « Copier chemin » (copie dans le presse-papier avec confirmation visuelle), « Plein écran » et « Télécharger ».
  * **Double Rendu Public & Joueur Connecté :**
    - Accessible aussi bien aux visiteurs non connectés (enveloppe Tabler publique épurée avec liens de découverte) qu'aux daimyōs connectés (intégré au HUD de jeu).
  * **Lien Rapide dans le Pied de Page (`views/partials/footer.php`) :**
    - Ajout du lien direct « Galerie d'Art » avec icône `fa-palette` rouge carmin aux côtés des Règles du jeu, Support, Changelog et de l'Atelier Pédagogique.

## [1.30.0] - 2026-10-15
### Amélioré (Enhanced) & Ajouté (Added)
- **Illustration Officielle du Mineur des Cimes (`public/assets/units/mineur.jpg`) :**
  * Création et intégration de l'illustration HD du Mineur des Cimes (*Kōfu* ⛏️) dans le style concept art féodal japonais d'OpenShogun : mineur aguerri en tenue de travail sashiko indigo, sandales waraji, pioche forgée en main et panier dorsal tressé garni de cristaux de soufre volcanique et de sable de fer (*satetsu*), surplombant une crête rocheuse volcanique escarpée baignée de fumerolles.
- **Affichage des Nouvelles Ressources Minérales dans le HUD Supérieur (`views/partials/header.php`, `views/partials/footer.php`) :**
  * **Nouveau Menu Déroulant « Minerais » sous le Carrousel Castral :** Intégration d'un bouton de catégorie dédié rouge rubis (`#dropdownMinerais`, icône `fa-gem`, badge `3`, sous-titre `Sable • Soufre • Nitrate`) entre Construction et Alimentaire.
  * **3 Cartes de Ressources Minérales Interactives :**
    - *Sable de Fer (Satetsu 砂鉄) :* Compteur dynamique (`#res-val-iron-sand`), jauge de stockage (`#bar-iron-sand`), plafond d'entrepôt et lien direct vers les fourneaux de la Grande Forge (`?page=building&code=blacksmith#craftSection`).
    - *Soufre Volcanique (Iō 硫黄) :* Compteur dynamique (`#res-val-sulfur`), jauge de stockage (`#bar-sulfur`), plafond d'entrepôt et lien direct vers l'atelier minéralier du tailleur de pierre (`?page=building&code=stonemason#craftSection`).
    - *Nitrate de Potasse (Shōseki 硝石) :* Compteur dynamique (`#res-val-nitrate`), jauge de stockage (`#bar-nitrate`), plafond d'entrepôt et lien direct vers l'atelier minéralier (`?page=building&code=stonemason#craftSection`).
  * **Actualisation en Temps Réel (`views/partials/footer.php`) :** Prise en charge des trois minerais dans la fonction client de synchronisation des ressources (`updatePlanetResources`).
- **Grande Forge du Tamahagane : Fonte de Métal (Niveau 15+) & Armes et Armées de Prestige (Niveau 20+) (`core/PlanetEngine.php`, `api/craft.php`, `views/building.php`, `views/city.php`) :**
  * **Fonte & Réduction de Métal (Niveau 15+) :**
    - *Fourneau Tatara des Maîtres (`product = 'metal'`) :* Réduction noble consommant 1 Sable de fer (*Satetsu* 💎) + 2 Charbon de bois (🪨) pour couler 40 Métal (⚔️) d'une pureté exceptionnelle, augmenté par le bonus d'efficacité de forge (+2%/niveau).
    - *Réduction Rustique aux Scories (`product = 'metal_crude'`) :* Réduction de substitution consommant 10 Pierre de granit (🪨) + 5 Charbon de bois (🪨) pour produire 25 Métal (⚔️).
    - Jauges de progression et verrouillage élégant (`X / 15`) si le niveau de forge est inférieur à 15.
  * **Armes & Armées de Prestige Féodales (Niveau 20+) :**
    - *Katanas de Maître & Lames Sacrées (`product = 'prestige_weapons'`) :* Forge d'apparat (500 Métal, 10 Sable de fer, 20 Charbon, 10 Saké) octroyant +150 Points d'Honneur/Prestige impérial et forgeant/débloquant la relique héroïque unique `katana_tamahagane` (*Katana Forgé en Tamahagane* : +320 Force, +1.5% Attaque) dans l'inventaire du héros (ou +250 XP bonus si déjà possédé).
    - *Garde d'Honneur au Tamahagane (`product = 'prestige_army'`) :* Mobilisation féodale (800 Métal, 400 Pierre, 15 Charbon, 15 Saké) formant et envoyant instantanément 10 Samouraïs au Katana (`samourai_katana`) et 2 Gardes Hatamoto d'Armure Lourde (`garde_hatamoto_armure_lourde`) dans la garnison du fief (`planet_units`) sans saturer le Dojo d'Infanterie.
    - Jauges de déblocage avec carte de progression verrouillée (`X / 20`) avant le niveau 20.
  * **Pipeline Backend & File d'Attente Complète (`core/PlanetEngine.php`, `api/craft.php`) :**
    - Implémentation de `calculateForgeCraftDetails()`, `craftForgeProduct()`, prise en charge de la forge dans `processCraftQueue()`, et remboursement sécurisé de 80% des matières engagées en cas d'annulation dans `cancelCraft()`.
    - Compatibilité intégrale avec le Sceau Impérial (*Privilège du Shōgun* : jusqu'à 4 commandes séquentielles dans la file de forge).
  * **Interface Interactive & Raccourcis Cité (`views/building.php`, `views/city.php`) :**
    - Section `#craftSection` dédiée sur la tuile de la Grande Forge avec prévisualisation des rendements en temps réel, boutons de remplissage rapide (+5, +10, +25, Max), minuteur dynamique de lot actif et file d'attente.
    - Bouton d'accès direct « Fourneaux & Prestige » sur la tuile de la Forge dans la vue Cité Castrale (`views/city.php`) dès le niveau 15.
- **Modernisation & Équilibrage du Simulateur de Vie dans un Village (`views/studio/game-elevate-designer/village-life-simulator.php`) :**
  * **Uniformisation Complète des 7 Cartes KPI au Standard Tabler :** Harmonisation structurelle et visuelle intégrale (bordure gauche colorée, badge d'en-tête thématique, indicateur chiffré principal `fs-1 font-monospace`, sous-titre de statut contextuel et jauge de progression `progress-xs`) pour l'ensemble des métriques : *Satisfaction & Moral*, *Démographie & Minka*, *Pyramide des 4 Castes*, *Emplois & Activité*, *Ordre & Sécurité Féodale*, *Grenier des Vivres* (total en silos) et *Entrepôt des Matériaux* (total chantiers).
  * **Amélioration de la Lisibilité Graphique & Visuelle :**
    - Suppression des badges numériques couvrant les points du graphique Dynamique Temporelle (`dataLabels: { enabled: false }`, `markers: { size: 0 }`), rendant les 8 courbes parfaitement nettes et visibles.
    - Révision du contraste des badges d'en-tête et de contrôle des flux (texte sombre haute lisibilité sur fond jaune pour les vivres, texte blanc sur fond indigo pour les matériaux, et labels explicites `text-white` / `text-dark`).
  * **Intégration Complète des 4 Castes Féodales (*Shinōkōshō*) :** Modélisation fidèle de la dynamique sociale féodale (*Paysans Nōmin*, *Artisans Kōgiyō*, *Marchands Shōnin*, *Noblesse Bushi & Daimyō*) avec besoins spécifiques de subsistance et de confort, promotion sociale automatique et régression en cas de pénurie.
  * **Intégration des Nouvelles Ressources de Jeu :**
    - *Vivres & Denrées :* Riz impérial, Poisson frais, Soja du terroir, Farine de riz, Thé vert, Saké impérial (respect strict de la règle asymétrique : +15% de bonus si présent, 0 malus si absent).
    - *Matériaux & Énergie :* Bois de cèdre, Pierre de granit, Argile & terres cuites, Poutres de charpente, Charbon de bois, Sérénité Shintō des kami.
  * **Graphiques & Scénarios Étendus :** ApexCharts multi-séries 8 courbes (Pop, Paysans, Artisans, Marchands, Noblesse, Saké, Délinquance, Moral), 5 scénarios préconfigurés (*Équilibre Sengoku*, *Âge d'Or Artisanal*, *Prospérité & Cour*, *Crise de Délinquance*, *Pénurie & Exode*) et export JSON enrichi avec insights de game design.

### Modifié (Changed) & Déplacé (Moved)
- **Relocalisation Intégrale de la suite Game Elevate Designer vers Game Studio (`views/gamestudio.php`, `views/gamestudio/elevate_*.php`, `core/DevTeamEngine.php`, `views/dev_team.php`, `api/dev_team.php`, `core/AuthManager.php`) :**
  * **Intégration au Menu Game Elevate Designer du Game Studio :** Relocalisation des 6 modules de simulation et équilibrage féodal dans le Game Studio :
    1. *Simulateur de Combat* (`?page=gamestudio&tab=elevate_combat`)
    2. *Vie du Village* (`?page=gamestudio&tab=elevate_village`)
    3. *Vitesses & Équilibrage* (`?page=gamestudio&tab=elevate_speed`)
    4. *Arpentage & Provinces* (`?page=gamestudio&tab=elevate_survey`)
    5. *Écosystèmes des Oasis* (`?page=gamestudio&tab=elevate_oases`)
    6. *Dérivation des Bâtiments* (`?page=gamestudio&tab=elevate_buildings`)
  * **Suppression de Game Elevate Designer du Studio Métiers (`views/dev_team.php`, `core/DevTeamEngine.php`) :** Retrait de la configuration métier `game-elevate-designer` de `STUDIO_METIERS`, nettoyage des scripts et contrôleurs associés dans `views/dev_team.php`, et mise en place de redirections HTTP automatiques transparentes vers le Game Studio pour toutes les anciennes URLs et paramètres `?tab=...` ou `?metier=game-elevate-designer`.
  * **Harmonisation des Accréditations (`core/AuthManager.php`, `api/dev_team.php`) :** `AuthManager::hasJob('game-elevate-designer')` et les points d'accès API associés autorisent désormais de plein droit l'ensemble des administrateurs et membres accrédités du Game Studio.

## [1.29.0] - 2026-10-14
### Ajouté (Added)
- **Indicateur de Charbon de Bois dans le HUD Supérieur (`views/partials/header.php`) :**
  * Intégration complète de la ressource Charbon de Bois (`charcoal`) dans la barre de ressources sous le carrousel castral.
  * Mise à jour du menu déroulant Matériaux de Construction (badge `5`, sous-titre `Bois • Pierre • Argile • Poutres • Charbon`).
  * Indicateur dynamique (`#res-val-charcoal`, barre `#bar-charcoal`, icône `fa-fire`) relié directement à la carbonisation de la Scierie (`?page=building&type=sawmill`).
- **Nouveau Système Minier de Montagne & 3 Nouvelles Ressources (`core/MountainEngine.php`, `core/PlanetEngine.php`, `core/FleetEngine.php`, `core/GalaxyEngine.php`, `views/map.php`, `views/fleet.php`, `views/building.php`, `api/craft.php`) :**
  * **3 Nouvelles Ressources Minérales :** Implantation du **Soufre** (`sulfur`, 🌋), du **Nitrate** (`nitrate`, 🧪) et du **Sable de fer** (*Satetsu* `iron_sand`, 💎) sur le modèle écologique des parcelles de lac.
  * **Sanctuaires Minéraux Non-Annexables (`core/MountainEngine.php`) :** Les tuiles de montagne (`mountain`) sur la carte procédurale hébergent des gisements minéraux finis générés procéduralement (10 000 à 25 000 par composant). Les parcelles de montagne ne peuvent être annexées et restent des sanctuaires communautaires exploitables par expédition.
  * **Nouvelle Unité Féodale — Mineur des Cimes (`mineur` / ⛏️ *Kōfu*) :** Formé au Dojo d'Infanterie (`barracks`), équipé pour l'extraction en haute altitude et doté d'une capacité de transport de 250 unités.
  * **Nouvelle Recherche Académique — Métallurgie & Géologie Féodale (`mining_tech`) :** Confère +25% de rendement d'extraction minérale par niveau d'académie.
  * **Missions de Flotte « Expédition Minière » (`mission_type = 'mining'`) :** Déploiement d'expéditions vers les tuiles de montagne avec prélèvement atomique et sécurisé des minéraux, acheminement vers le fief émetteur et rapports féodaux détaillés.
  * **Affichage Télémétrique & Carte Tactique (`views/map.php`, `views/fleet.php`) :** Jauges interactives des gisements résiduels de montagne sur la modale de carte et pré-remplissage automatique des expéditions minières.
  * **Artisanat & Concassage Minéral au Tailleur de Pierre (`views/building.php`, `api/craft.php`, `core/PlanetEngine.php`) :** Possibilité de fabriquer du Soufre (à partir de Pierre de granit), du Nitrate (à partir d'Argile alluviale) et du Sable de fer (à partir de Pierre de granit) directement à l'Atelier du Tailleur de Pierre avec file d'attente, progression en direct et annulation avec remboursement de 80%.
- **Conservation des Comptes Utilisateurs lors du Reset du Monde (`core/WorldGenerator.php`, `views/admin.php`) :**
  * La procédure de réinitialisation (`resetUniverse`) ne supprime plus les comptes des joueurs (la table `users` n'est plus tronquée).
  * Les identifiants, adresses email, mots de passe et préférences de profil sont intégralement préservés.
  * Les points et appartenances aux clans (alliances) sont réinitialisés à 0 et l'immunité débutant est réactivée pour la nouvelle manche.
  * Chaque joueur humain conservé reçoit automatiquement un nouveau domaine castral de départ procéduralement positionné, avec ses 18 parcelles de ressources, son terroir rural, sa garnison et son Héros Samouraï initial de niveau 1.
  * Les bots résiduels (`is_bot = 1`) sont proprement purgés et régénérés à neuf selon la configuration choisie.

## [1.28.0] - 2026-10-14
### Ajouté (Added)
- **Nouvelle Interface Unifiée Game Studio en Architecture MVC (`views/gamestudio.php`, `core/GameStudioEngine.php`) :**
  * **Fusion Studio & Administration :** Plateforme centrale unifiant les outils du studio de développement (Dev Team) et le panneau d'administration générale du Shōgunat.
  * **Barre d'Outils Supérieure (Toolbar) :** Intégration d'une barre de navigation supérieure avec onglets interactifs :
    - **Dashboard (`views/gamestudio/dashboard.php`) :** Tableau de bord télémétrique avec cartes KPIs (Pages vues, daimyōs uniques, requêtes/minute, ratio bots/humains), graphiques d'activité interactifs ApexCharts avec fallback haute définition Canvas sans dépendance, top des modules consultés, répartition horaire des requêtes, table des daimyōs les plus actifs avec filtre de recherche dynamique, métriques serveur et santé du système.
    - **Général (`views/gamestudio/general.php`) :** Vue synthétique sur la configuration globale, version du framework, spécifications d'environnement (PHP, OS, serveur web), compteurs globaux (comptes humains, clans IA, fiefs) et effectifs de l'équipe de développement avec rôles honorifiques.
  * **Architecture MVC Découpée (`views/gamestudio/`) :** Respect strict des normes MVC avec extraction modulaire et autonome de chaque sous-vue dans le répertoire dédié `views/gamestudio/`, piloté par le contrôleur maître `views/gamestudio.php` et le moteur métier `core/GameStudioEngine.php`.
  * **Raccourci dans le HUD Supérieur (`views/partials/header.php`) :** Ajout de l'icône Game Studio (`fa-laptop-code`) à côté de l'Administration sans supprimer aucun icône existant du jeu.
  * **Routage & Contrôle d'Accès Sécurisé (`index.php`) :** Ajout de la route `/gamestudio` avec alias d'onglets, filtre de sécurité limitant l'accès aux Administrateurs et aux membres habilités de la Dev Team.
  * **Page Dédiée Autonome & Nouveau Tab (`views/gamestudio.php`, `views/partials/header.php`) :**
    - Ouverture de Game Studio sur une page dédiée (`target="_blank"`) sans le header de l'interface de jeu (ni carrousel castral de 250px, ni barre de ressources de jeu).
    - Barre d'outils supérieure professionnelle avec logo, version MVC, accès direct aux modules Dashboard et Général, sélecteur de périodes, boutons d'action rapide vers le fief, l'administration et la Dev Team.
  * **Visualisations Graphiques Interactives & Télémétrie Dédiée (`views/gamestudio/dashboard.php`, `core/ActivityTracker.php`, `core/GameStudioEngine.php`) :**
    - Intégration directe de la bibliothèque ApexCharts CDN et restauration complète du moteur de rendu Canvas HD autonome de repli (`renderCanvasTrend()`, `renderCanvasTopPages()`, `renderCanvasHourly()`).
    - Remontée garantie des métriques pour les graphiques : initialisation d'une série temporelle continue sur 30 jours et du Top 8 des modules consultés avec volumétrie positive même lorsque les tables de logs sont peu alimentées.
### Supprimé (Removed)
- **Suppression du Fichier Sandbox (`sandbox.php`) :** Suppression propre et définitive du script de bac à sable obsolète à la racine du projet.

## [1.27.0] - 2026-10-14
### Ajouté (Added)
- **Achèvement Instantané des Chantiers pour 2 Kobans (`core/BuildingEngine.php`, `core/PlanetEngine.php`, `api/build.php`, `api/rural_plot.php`, `public/js/app.js`, `views/partials/urban_construction_queue.php`, `views/building.php`, `views/field.php`, `public/js/rural_domain_map.js`) :**
  * **Disponible pour Tous les Bâtiments & Toutes les Unités de Ressources :** Couvre l'intégralité des infrastructures urbaines de la cité castrale (`building`), toutes les parcelles de ressources élémentaires (`field` : scierie, carrière, mines, fermes) et l'ensemble des 9 parcelles du terroir rural (`rural_plot` : forêt de cèdres, rizière, carrière, fosse d'argile, thé, soja, village, sanctuaire shintō, tenshu).
  * **Coût Fixe de 2 Kobans (`gold_coins`) :** Déduction transactionnelle rigoureusement sécurisée de 2 pièces d'or impériales par achèvement instantané. Contrôle strict du solde joueur avec rejet explicite et informatif si le Daimyō dispose de moins de 2 Kobans.
  * **Résolution Immédiate & Avancement Automatique de la File :** Le chantier sélectionné s'achève sur-le-champ via `PlanetEngine::processConstructionQueue()`, appliquant les nouveaux niveaux, les quotas d'ouvriers et le recalcul instantané de la production horaire. Tout chantier ultérieur en attente séquentielle dans la file est automatiquement avancé dans le temps pour démarrer immédiatement.
  * **Intégration Visuelle Complète & Ergonomie :**
    - File des chantiers mutualisée (`urban_construction_queue.php`) : Ajout d'un bouton compact doré `2 🪙` à côté du bouton d'annulation sur chaque ligne de travaux.
    - Fiche détaillée de bâtiment (`views/building.php`) : Bouton doré proéminent « Finir instantanément (2 Kobans) » dans la carte des travaux actifs.
    - Fiche détaillée de parcelle de ressource (`views/field.php`) : Bouton doré au-dessus de l'option d'annulation.
    - Carte interactive du domaine rural (`rural_domain_map.js`) : Bouton doré d'achèvement immédiat dans la modale d'élévation de parcelle.
    - Modale de confirmation personnalisée Tabler demandant validation au joueur avant débit des 2 Kobans.
- **Équilibrage Proportionnel 1:1 & Présentation en 2 Colonnes de l'Intendant du Marché (`views/partials/npc_exchange_modal.php`, `core/ImperialSealEngine.php`, `api/seal.php`, `views/building.php`, `views/empire.php`, `views/privilege.php`) :**
  * **Présentation Structurée en Deux Colonnes (Modal-XL) :** Organisation ergonomique claire et spacieuse :
    - **Colonne Gauche (Alimentaire — 6 vivres) :** Riz Impérial (Koku) 🌾, Poisson Frais 🐟, Feuilles de Thé 🍵, Gousses de Soja 🫘, Farine de Riz (Komeko) 🍚, Saké Impérial 🍶.
    - **Colonne Droite (Matériaux de Construction — 5 ressources) :** Bois de Cèdre 🪵, Pierre de Granit 🪨, Argile Alluviale 🧱, Poutres de Charpente 🔨, Charbon de Bois 🪨/🔥.
  * **Cases à Cocher par Ressource (Max 2 Sélectionnables) :** Intégration d'une case à cocher devant chacune des 11 ressources. Le joueur peut lier jusqu'à 2 ressources simultanément pour un équilibrage automatisé (inter-colonnes ou intra-colonnes). Dès que 2 cases sont cochées, les 9 autres ressources sont verrouillées pour préserver leur quota fixe et leurs cases sont temporairement désactivées.
  * **Ajustement en Miroir Proportionnel (Troc Parfait 1:1) :** Augmenter le curseur ou la valeur numérique d'une ressource liée diminue automatiquement et proportionnellement l'autre ressource liée de la même quantité, conservant la somme totale du fief au grain près sans rupture d'équilibre.
  * **Respect Strict des Plafonds & Planchers :** L'ajustement est borné dynamiquement par la capacité de stockage maximale de chaque ressource (scieries, carrières, fosses d'argile, greniers Kura, pavillons, meuneries, brasseries, etc.) et par le plancher de zéro, évitant tout dépassement d'entrepôt.
  * **Gestion Backend & API Universelle (`ImperialSealEngine.php`, `api/seal.php`) :** Prise en charge complète de l'ensemble des 11 colonnes en base (`planets`), vérification transactionnelle du coût (3 Kobans), mise à jour atomique et rétrocompatibilité assurée.
  * **Mutualisation Complète du Marché Castral :** Remplacement des implémentations dupliquées par la vue partielle mutualisée `views/partials/npc_exchange_modal.php` sur l'ensemble des pages (`building.php`, `empire.php`, `privilege.php`).
### Modifié (Changed)
- **Rotation Aléatoire des Estampes du Carrousel (`views/partials/header.php`) :**
  * **Affichage Homogène des 50+ Panoramas :** Remplacement du tri alphabétique par nom de fichier par un brassage aléatoire (`shuffle`) à chaque rechargement de page. Toutes les estampes et œuvres d'art du carrousel bénéficient ainsi d'une visibilité équitable et variée à chaque navigation du Daimyō.
- **Menus Déroulants Interactifs & Gain d'Espace dans la Barre Supérieure (`views/partials/header.php`, `views/partials/footer.php`) :**
  * **Barre Épurée à 3 Boutons Compacts :** Remplacement des jauges étalées par 3 boutons déclencheurs ergonomiques centrés sous le carrousel : **Construction 🔨**, **Alimentaire 🍚** et **Domaine ⛩️**, avec icônes, badges de comptage et sous-titres descriptifs.
  * **Menu Déroulant Construction :** Au clic sur le bouton, ouverture d'un panneau Tabler élégant affichant en temps réel les 4 matériaux de bâtisseur (Bois de Cèdre 🪵, Pierre de Granit 🪨, Argile Alluviale 🧱, Poutres de Charpente 🔨) avec stocks, plafonds d'entrepôt, cadences horaires et jauges de progression.
  * **Menu Déroulant Alimentaire :** Développement des 6 denrées de subsistance et de terroir (Riz Impérial 🌾, Poisson Frais 🐟, Feuilles de Thé 🍵, Gousses de Soja 🫘, Farine de Riz 🍚, Saké Impérial 🍶) avec détails de stockage au Grenier à Riz Fortifié (Kura).
  * **Menu Déroulant Domaine :** Consultation instantanée des indices vitaux du fief (Sérénité Shintō ⛩️, Démographie & Logements Minka 👥, Main-d'œuvre & Quotas ouvriers ⛏️, Moral des Villageois 😊, Kobans d'or 🪙).
  * **Gain d'Espace & Ergonomie Mobile :** Encombrement horizontal réduit de plus de 60%, affichage responsive impeccable sur mobile et desktop, et maintien de la synchronisation dynamique en temps réel via `updateHudResources()`.

## [1.25.0] - 2026-10-13
### Ajouté (Added)
- **Réserves Halieutiques Finies des Lacs & Préservation Permanente des Parcelles (`core/LakeEngine.php`, `core/WorldGenerator.php`, `core/GalaxyEngine.php`, `core/FleetEngine.php`, `views/map.php`, `views/fleet.php`, `database/migrations/006_lake_fish_stocks.sql`) :**
  * **Dépôt Aléatoire de 10 000 à 25 000 Poissons :** À la génération du monde et lors de la découverte de toute parcelle de lac (procédurale ou oasis lacustre), un banc de poissons fini est déposé aléatoirement entre 10 000 et 25 000 individus (`random_int(10000, 25000)`).
  * **Prélèvement Atomique & Plafonnement des Prises :** Les expéditions de pêche déduisent leurs prises directement de la réserve du lac en base (`lake_fish_stocks`). Si la réserve est inférieure à la capacité de pêche de l'équipage, seule la quantité résiduelle est collectée et le lac passe au statut épuisé.
  * **Pérennité Absolue des Parcelles de Lac :** Lorsqu'un lac est entièrement vidé de ses poissons, il ne disparaît jamais de la carte du monde. Il demeure visible sous forme de plan d'eau naturel pacifié, préservant la géographie et la topographie provinciale.
  * **Interdiction Définitive de Prélèvement sur Lac Épuisé :** Une fois le stock tombé à zéro, il est impossible de lancer de nouvelles expéditions de pêche sur cette parcelle. Le bouton d'action est désactivé avec le statut « Réserve de Poisson Épuisée » et le moteur `FleetEngine` rejette toute tentative avec un message informatif explicite.
  * **Jauge Visuelle des Réserves sur la Carte Tactique :** Intégration sur la modale de tuile d'une jauge de progression cyan affichant le stock actuel sur le stock initial (ex. `18 420 / 22 500`) et d'un bandeau d'alerte doré lorsque le sanctuaire est épuisé.
  * **Compteur Dédié des Ressources Disponibles en Poisson (`views/map.php`, `public/js/galaxy_map.js`) :** Rendu d'une carte Tabler.io dédiée dans la modale de chaque lac (procédural, oasis lacustre ou terre vierge d'eaux vives) affichant un badge dynamique du banc de poissons disponible, le détail de la réserve restante, le lieu de stockage (Kura) et l'infobulle enrichie au survol de la carte.
  * **Pré-sélection Automatique au Formulaire d'Expédition (`views/fleet.php`) :** Lorsqu'un Daimyō prépare une expédition de pêche depuis un lac, les Pêcheurs Féodaux disponibles sont automatiquement pré-remplis au formulaire, avec contrôle immédiat de disponibilité.

## [1.24.0] - 2026-10-12
### Ajouté (Added)
- **Système Halieutique & Refonte des Parcelles de Lac (`core/FleetEngine.php`, `core/OasisEngine.php`, `core/PlanetEngine.php`, `core/ResearchService.php`, `views/map.php`, `views/fleet.php`, `views/partials/rural_harvest_resources_panel.php`, `views/partials/header.php`, `database/migrations/005_lake_fishing_system.sql`) :**
  * **Parcelles de Lac Non-Annexables :** Les parcelles de lac (`tile_lake.jpg` et oasis aquatiques) deviennent des sanctuaires d'eaux libres communautaires non annexables par un fief unique. La tentative d'annexion est bloquée et remplacée par la possibilité d'y dépêcher des expéditions de pêche.
  * **Nouvelle Unité au Dojo — Pêcheurs Féodaux (Ryōshi 🐟) :** Nouvelle unité villageoise recrutable au Dojo (Tier 1), dotée d'une illustration d'époque ukiyo-e dédiée (`public/assets/units/pecheur.jpg`) et d'une capacité de transport de vivres pour rapporter les prises.
  * **Technologie Académique Halieutique (`fishing_tech`) :** Nouvelle recherche à l'Académie dans la catégorie « Subsistance & Terroirs » (icône 🐟), augmentant les prises de poisson de +25% par niveau.
  * **Expéditions de Pêche à Durée Fixe (1 Heure) :** Les missions de pêche vers un lac effectuent un trajet aller-retour incluant un temps de pêche fixe d'exactement 1 heure (3600 secondes) sur le lac. La quantité de poisson ramenée est calculée selon le nombre de pêcheurs mobilisés, le niveau de la recherche académique, et plafonnée par la cargaison.
  * **Stockage au Grenier à Riz Fortifié (Kura / `b_tank`) :** Le poisson frais débarqué est entreposé dans le Grenier à Riz Fortifié alongside le riz, avec un plafond de stockage indexé sur le niveau du bâtiment.
  * **Affichage In-Game & Expérience Joueur :**
    - Intégration sur la carte tactique (`map.php`) d'une pastille « Eaux Libres Non-Annexable » et d'un bouton direct « Dépêcher une Expédition de Pêche ».
    - Formulaire d'envoi de flotte (`fleet.php`) enrichi avec l'option « Expédition de Pêche (1h sur le lac) » et contrôle de présence de pêcheurs.
    - Suivi du stock de poisson frais dans la barre supérieure (`header.php`) et dans le panneau latéral des récoltes rurales (`rural_harvest_resources_panel.php`).
    - Dépêche et rapport de mission transmis automatiquement dans la messagerie du Daimyō au retour de l'équipage.

### Ajouté (Added)
- **Modale Tabler.io Universelle pour le Renommage des Fiefs (`views/partials/rename_village_modal.php`, `views/city.php`, `views/resources.php`, `views/partials/header.php`, `views/partials/footer.php`) :**
  * **Éradication des boîtes natives :** Remplacement intégral des invites JavaScript synchrones `prompt()` et `alert()` par une modale moderne Tabler.io (`modal-blur`).
  * **Raccourci Rapide dans le Menu Daimyō :** Accès direct pour renommer le fief actif depuis le menu déroulant de la barre supérieure (`header.php`).
  * **Interface Réactive & Ergonomie :** Saisie directe avec focus automatique, validation de formulaire AJAX fluide, bouton d'action avec indicateur de chargement (spinner) et notifications par toasts Tabler en cas de succès ou d'erreur.

- **Indicateur de Moral & Contentement des Villageois (`views/partials/header.php`) :**
  * **Jauge Interactive sous le Carrousel :** Intégration d'une pastille interactive affichant le niveau d'approbation et de moral de la population féodale du fief.
  * **Statuts Dynamiques & Infobulles :** Calcul en temps réel de l'état d'esprit des paysans (Très mécontent, Morose, Satisfait, Enthousiaste) avec infobulle contextuelle explicitant l'influence des taxes, de la nourriture et du logement sur la productivité.

### Corrigé (Fixed)
- **Détection des Troupes dans les Chroniques Féodales Hebdomadaires (`core/WeeklyEventEngine.php`) :**
  * **Résolution du blocage de l'Acte III d'Okehazama (et quêtes d'entraînement militaire) :** Résolution du problème où l'objectif `spearman` restait à 0. Mapping automatique des clés génériques (`spearman`, `archer`, `cavalry`, `gunner`, `samurai`) vers les identifiants techniques des 3 clans féodaux (Oda : `piquier_ashigaru_yari`, Tokugawa : `sentinelle_yari_tokugawa`, Takeda : `fantassin_leger_takeda`, etc.).
  * **Périmètre Daimyō Étendu :** Agrégation des troupes sur l'ensemble des fiefs du joueur (`p.user_id = ?`) et non plus uniquement sur le fief actif.
  * **Prise en Compte des Files d'Attente et Expéditions :** Actualisation automatique de la file du Dojo (`processBarracksQueue()`), détection des recrues en cours de formation (`barracks_queue`) et des régiments en marche lors d'expéditions (`fleet_missions`).
  * **Fallback Quota Global :** Prise en compte du volume total de l'armée si le quota de recrues requis est atteint.

- **Correction du Crash JavaScript sur la Page Flotte (`views/fleet.php`) :**
  * Résolution des erreurs bloquantes `Uncaught SyntaxError: missing ) after argument list` et `Uncaught ReferenceError: submitFleet is not defined` causées par des guillemets doubles non échappés dans l'alerte modale lors du départ d'expéditions féodales.

- **Consommation & Équilibrage de la Ferveur Divine (`core/VillageGeneratorService.php`, `core/PlanetEngine.php`, `core/RuralPlotEngine.php`, `views/resources.php`, `public/js/rural_domain_map.js`) :**
  * **Résolution du blocage à 0 de la consommation :** Raccordement de la consommation de ferveur spirituelle pour les 6 parcelles de production du terroir rural (Rizière : 6/niv, Carrière : 5/niv, Forêt : 4/niv, Fosse d'argile : 4/niv, Soja : 4/niv, Thé : 3/niv).
  * **Équilibrage Sanctuaire Shintō & Déficit :** Étalonnage face à la production du Sanctuaire Shintō (30/niv + 20 passif). En cas de déséquilibre (mines élevées sans sanctuaire), le déficit s'active correctement et bride la production à 10% jusqu'à l'élévation du sanctuaire.
  * **Affichage In-Game & Modale :** Affichage de la consommation exacte dans la barre supérieure (`header.php`), l'accordéon latéral (`rural_harvest_resources_panel.php`), les infobulles du terroir et la modale d'élévation de structure.

## [1.22.0] - 2026-10-11
### Ajouté (Added)
- **Système Exhaustif des 52 Chroniques Féodales du Sengoku (`core/WeeklyEventsData.php`, `core/WeeklyEventEngine.php`, `database/migrate_weekly_events.php`) :**
  * **52 Récits Historiques du Sengoku (1543-1615) :** Déploiement d'un calendrier complet d'événements hebdomadaires couvrant 52 semaines réparties sur 4 trimestres thématiques :
    - *T1 (Semaines 01-13) :* L'Aube des Conquêtes & les Seigneurs de Guerre (Tanegashima, Okehazama, Kawanakajima, etc.).
    - *T2 (Semaines 14-26) :* L'Ascension d'Oda Nobunaga & la Flamme de l'Unification (Château de Gifu, Nagashino, Azuchi, etc.).
    - *T3 (Semaines 27-39) :* La Régence de Toyotomi Hideyoshi & l'Âge d'Or Momoyama (Honnō-ji, Shizugatake, Odawara, etc.).
    - *T4 (Semaines 40-52) :* L'Hégémonie des Tokugawa & la Paix d'Edo (Sekigahara, Château d'Edo, Sièges d'Osaka, etc.).
  * **156 Actes Narratifs Séquentiels :** Chaque chronique se compose de 3 actes historiques progressifs (tâches de récolte de ressources, mobilisation de troupes samouraïs, investissements culturels ou diplomatiques) avec lore immersif et récompenses intermédiaires.
  * **Récompenses de Clôture Majeures :** Kobans d'or, Honneur de Daimyō, Sceaux Impériaux et 52 Titres Honorifiques uniques attribués au joueur à la complétion du 3ème acte.
  * **Rotation Hebdomadaire Automatique :** Sélection automatique de la chronique active calée sur la semaine calendaire ISO courante `(int)date('W')`, avec possibilité d'activation/désactivation manuelle par les administrateurs.

- **Banque d'Illustrations Artistiques 16:9 (`public/assets/events/event_week_01.jpg` à `event_week_52.jpg`) :**
  * **52 Œuvres d'Art Ukiyo-e Digital :** Intégration d'un corpus visuel complet au format 16:9 haute résolution respectant la direction artistique feutrée et authentique du Japon féodal (style peinture numérique / estampe ukiyo-e inspiré de Travian / Grepolis).
  * **Couverture Chronologique Exhaustive :** Chaque semaine dispose de son illustration sur mesure mettant en scène les batailles mythiques, forteresses emblématiques, cérémonies du thé et personnages historiques du Sengoku Jidai.

- **Affichage & Ergonomie de la Modale Joueur (`views/partials/weekly_event_modal.php`) :**
  * **Mise en Valeur Visuelle 16:9 :** Intégration de l'illustration de la chronique courante en bannière héroïque avec cadre doré traditionnel, sceau calligraphié et sous-titre kanji immersif.
  * **Gabarit Pleine Hauteur avec Marges :** Refonte responsive du conteneur modal s'adaptant à la hauteur totale du viewport avec marges supérieure et inférieure calibrées et zone de défilement interne soignée (`overflow-y: auto`).
  * **Ouverture Automatique Éco-Session :** Déclenchement automatique de la modale à la première visite d'une session de jeu (`sessionStorage`), avec case à cocher « Ne plus afficher automatiquement cette semaine » persistée dans `localStorage` pour préserver le confort du joueur.

- **Administration & Édition Avancée des Chroniques (`views/partials/admin_weekly_events.php`, `api/weekly_events.php`) :**
  * **Recherche Instantanée & Filtrage :** Barre de recherche dynamique par nom, description ou numéro de semaine pour naviguer instantanément parmi les 52 chroniques.
  * **Édition Rapide en 1 Clic :** Bouton « Éditer » dédié sur chaque carte d'événement ouvrant la modale d'administration de la chronique sans interférence avec l'interface joueur.
  * **Sélecteur d'Illustration avec Aperçu en Direct :** Menu déroulant listant les 52 illustrations d'événements avec prévisualisation immédiate en temps réel.
  * **Gestion Granulaire des Tâches :** Boutons d'édition unitaire sur chaque acte narratif préremplissant automatiquement le formulaire (`modalAddTask`) pour ajuster le titre, la description, les conditions et les récompenses.

### Corrigé (Fixed)
- **Isolation des Événements & Modales Admin (`views/partials/admin_weekly_events.php`) :**
  * Suppression du bouton d'édition redondant sous la miniature de la carte.
  * Correction du conflit de propagation d'événements qui déclenchait la modale joueur lors du clic sur le bouton d'édition de l'administration.
- **Accélérations & Multiplicateurs de Production :** Prise en compte rigoureuse des bonus d'ateliers et de technologie pour les nouvelles ressources de terroir (Argile, Thé, Soja).

## [1.21.0] - 2026-10-11
### Ajouté (Added)
- **Intégration de la Page Classement au Template Unique (`views/ranking.php`, `views/partials/game_screen_template.php`) :**
  * **Harmonisation complète du Palmarès :** Intégration de la vue du classement (Daimyōs, Alliances et Tableau d'Honneur) dans `game_screen_template.php`.
  * **Confort visuel et ergonomie 2 colonnes :** Présentation sous forme de carte unifiée (`.rts-center-card`), onglets intégrés en sous-en-tête fluide, et présence de la colonne Est mutualisée (`resources_east_accordion.php`) synchronisée en hauteur.

- **Indicateur de Main-d'œuvre Féodale dans la Barre de Ressources (`views/partials/header.php`) :**
  * **Ratio Ouvriers Requis vs Pourvus :** Ajout d'une pastille interactive dédiée affichant le nombre d'ouvriers mobilisés face aux postes requis (`assigned_workers` / `required_workers`), avec code couleur dynamique (vert si effectif complet, jaune/orange en cas de sous-effectif).
  * **Jauge et infobulle détaillée :** Affichage de la micro-jauge de couverture, alerte de pénalité de vitesse de production en cas de sous-effectif, et comptage des villageois inactifs/sans affectation.

- **Template de Grille & Présentation Harmonisée (`views/partials/game_screen_template.php`, `views/resources.php`, `views/city.php`, `views/map.php`, `views/ranking.php`) :**
  * **Architecture à brique unique (`game_screen_template.php`) :** Conception d'un composant de mise en page réutilisable centralisant le conteneur plein écran (`.rts-screen-container`), la grille 2 colonnes responsive (`.grid-main`), la carte centrale interactive (`.rts-center-card`) et la colonne latérale Est (`.east-sidebar-col`).
  * **Uniformisation visuelle (border-radius 8px) :** Standardisation des styles Tabler (rayon de courbure unifié à 8px, ombre douce `shadow-sm`, fond blanc, bordure nette) sur l'ensemble des écrans du jeu.
  * **Maintenance simplifiée :** Toute future modification d'enveloppe, d'espacement ou de comportement de la barre latérale s'effectue désormais en un seul point central.

- **Complétion des Terroirs Spécialisés dans la Barre de Ressources (`views/partials/header.php`) :**
  * **Ajout de l'Argile, du Thé et du Soja :** Intégration des 3 ressources issues des parcelles rurales spécialisées (`fosse_argile`, `culture_the`, `champ_soja`) dans le bandeau compact sous le slider.
  * **Suivi complet :** Affichage de la production horaire (+XX/h), des jauges de capacité de stockage, des niveaux de parcelles et d'infobulles contextuelles pour l'ensemble des 12 ressources et piliers féodaux.

- **Barre d'Affichage Compacte des Ressources sous le Slideshow (`views/partials/header.php`) :**
  * **Intégration d'un bandeau Tabler blanc ultra-fin (~38px) :** Affichage panoramique sobre et épuré directement sous le carrousel des bâtiments féodaux (`#headerBuildingsCarousel`).
  * **Inventaire complet des 12 ressources & piliers :**
    - *Matières premières de base :* Bois de Cèdre, Pierre de Granit et Riz Koku avec taux horaire (+XX/h) et micro-jauges de saturation.
    - *Terroirs spécialisés :* Argile, Thé Vert et Soja avec niveaux et cadences horaires.
    - *Vivres raffinées & matériaux :* Farine de Riz, Saké Impérial (+15% moral) et Poutres de charpente avec jauges de stock.
    - *Société & Monnaie :* Sérénité Shintō (solde net), Démographie & Logements (habitants/max) et Trésor en Kobans.
  * **Ergonomie & Dynamique :** Connexion directe aux tickers JavaScript temps réel (`res-val-*`, `bar-*`), infobulles détaillées au survol et défilement horizontal fluide tactile pour mobiles.

- **Atelier Pédagogique — Masterclass IA Vidéo & Prompt Engineering de Trailer (`views/atelier-pedagogique/multimedia/ia-video-trailer.php`, `views/pedagogy.php`, `views/partials/admin_pedagogy.php`) :**
  * **Cours illustré interactif :** Conception d'un module d'apprentissage complet dédié à la création d'un trailer vidéo par intelligence artificielle générative (Text-to-Video & Image-to-Video).
  * **Anatomie d'un prompt vidéo & lexique cinématographique :** Formule maîtresse de prompt en 5 piliers (`[Style & Rendu] + [Sujet & Action] + [Mouvement Caméra & Cadrage] + [Éclairage & Atmosphère] + [Paramètres Temporels & Qualité]`), vocabulaire de prise de vue (*Slow Tilt-Up*, *Tracking*, *Aerial Isometric 45° Timelapse*, *Handheld*) et principes de cohérence temporelle.
  * **Script & Narration cinématique en 6 actes :** Réécriture de la narration originale de David & Gabriel en un découpage chronologique de ~70 secondes avec sound design traditionnel japonais (flûte Shakuhachi, luth Shamisen, tambours Taiko, chœurs martiaux et gong).
  * **Prompts prêts à l'emploi & guide de montage pour Gabriel :** 6 prompts optimisés en anglais avec boutons de copie en un clic et feuille de route de montage pas-à-pas (génération des rushes, cut au beat musical sous CapCut / DaVinci, sound design SFX, titrage & call to action).
  * **Intégration au Hub Pédagogique :** Ajout du raccourci Hero, de la bannière dans la section IA Visuelle et d'une 4ème carte dans le catalogue de l'École de Conception (grille 4 colonnes).

### Modifié (Changed)
- **Interface Cité Castrale — Suppression de l'Effet « Cadre Jaune » au Survol (`views/city.php`) :**
  * **Épuration visuelle des zones interactives :** Suppression des bordures (`1.5px solid rgba(234, 179, 8, 0.85)`), de la teinte d'arrière-plan et du halo jaune (`box-shadow`) au survol des bâtisses (`.rts-hotspot:hover`).
  * **Immersion artistique renforcée :** L'illustration panoramique du château et des bâtiments reste limpide et visible sans cadre jaune intrusif, tandis que le badge de niveau conserve son interaction subtile (agrandissement et ombre portée douce).

- **Harmonisation de la Colonne Est en Accordéon Tabler Blanc (`views/partials/resources_east_accordion.php`, `views/resources.php`, `views/city.php`, `views/map.php`) :**
  * **Standardisation sur 3 vues clés :** Déploiement du composant d'accordéon Tabler blanc (`bg-white`, chevron rotatif) sur les pages *Terroirs Ruraux* (`resources.php`), *Cité Castrale* (`city.php`) et *Carte du Monde* (`map.php`).
  * **Hauteur synchronisée :** Alignement dynamique de la hauteur de l'accordéon sur la carte centrale (`syncAccordionWithCenterCard`) avec barre de défilement interne automatique au-delà de la hauteur du card.
  * **Suppression des 8 carrés de ressources du header :** Allègement de l'en-tête global pour centraliser la consultation des stocks et des récoltes dans la colonne latérale.

### Ajouté (Added)
- **Module Dédié Main-d'œuvre & Démographie dans l'Accordéon (`views/partials/workforce_demographics_panel.php`, `views/partials/resources_east_accordion.php`) :**
  * **Nouvel item autonome (4ème volet) :** Création du panneau complet *Main-d'œuvre & Démographie* séparé du bilan des récoltes de matières premières.
  * **Capacités & Mobilisation :** Suivi de la capacité d'accueil des maisons minka, du taux d'occupation, du contentement féodal, de la mobilisation des ouvriers et du taux de chômage.
  * **Décomposition transparente des postes requis :** Contrôle granulaire séparant le secteur rural (9 parcelles du terroir) et le secteur urbain (bâtiments de la cité castrale) avec niveau, taux par niveau et total vérifiable.

### Corrigé (Fixed)
- **Échappement de l'Infobulle de Main-d'œuvre dans le Header (`views/partials/header.php`) :**
  * **Correction de la fermeture prématurée de balise HTML :** Résolution d'un défaut où les guillemets non échappés dans l'attribut `title` de la pastille des effectifs entraînaient une rupture de l'attribut `title="..."` et faisaient fuiter du texte brut et un caractère `">` visible à l'écran. Utilisation de `htmlspecialchars($workforceTooltip, ENT_QUOTES, 'UTF-8')`.

- **Audit & Correction du Calcul des Quotas d'Ouvriers (`core/PlanetEngine.php`, `core/PopulationEngine.php`, `core/VillageGeneratorService.php`) :**
  * **Prise en compte des 9 parcelles dans la production :** Correction de la requête `SELECT` dans `PlanetEngine::calculateProduction` qui omettait `id`, restaurant la prise en compte des 9 parcelles du terroir dans les calculs de main-d'œuvre et de cadence de production.
  * **Suppression du double comptage du Donjon Tenshu :** Tenshu Donjon comptabilisé exclusivement comme quartier général castral urbain (`hq`, 2 ouvriers/niv) et passage de `RURAL_PLOT_WORKERS['tenshu']` à 0 pour éliminer la duplication de 2 ouvriers supplémentaires par niveau.
  * **Unification des formules de travailleurs :** Alignement de `VillageGeneratorService` et de `PlanetEngine` sur la méthode canonique `PopulationEngine::getRuralPlotWorkersRequired`.

### Ajouté (Added)
- **Simulateur d'Équilibrage Studio Dev — Production de Ressources & Calcul de Rentabilité ROI (`views/studio/game-elevate-designer/building-derivation.php`) :**
  * **Intégration de la production de ressources au simulateur :** Prise en charge des rendements de l'ensemble des édifices et parcelles dans le module de dérivation mathématique des chantiers (`?page=dev_team&metier=game-elevate-designer&module=building-derivation`) :
    - *Terroir Vivant (9 parcelles) :* Calcul en temps réel de la cadence horaire selon la formule canonique féodale pour la Forêt de Cèdres (Bois/h), la Carrière (Pierre/h), la Rizière (Riz/h), la Fosse d'Argile (Argile/h), le Champ de Soja (Soja/h), les Coteaux de Thé (Thé/h), le Sanctuaire Shintō (Sérénité/h) et le Village (Logements Minka).
    - *Cité Castrale & Exploitations :* Prise en charge des parcelles classiques (Bois, Pierre, Riz) ainsi que des édifices à bonus d'ateliers (+5%/niv pour Scierie, Briqueterie, Moulin, Pavillon de thé) et de stockage de vivres (Entrepôt et Grenier Kura x1.5/niv).
  * **Analyse de rentabilité & Amortissement (ROI / Payback Time) :**
    - Calcul automatique du retour sur investissement niveau par niveau ($\text{ROI} = \frac{\Delta\text{Coût}}{\Delta\text{Production horaire}}$) en heures et en jours.
    - Colonnes dédiées dans le tableau comparatif : *Production / h*, *Gain Net $\Delta$ Prod* et *Amortissement (ROI)*.
    - Carte d'indicateur synthétique KPI affichant le rendement maximal atteint et le délai d'amortissement moyen.
  * **Triple courbe interactive sur le graphique SVG :** Ajout d'une 3ème courbe vectorielle cyan/bleue dédiée au rendement et à la production, avec dégradé subtil, pointillés techniques et infobulles enrichies au survol (coût, durée, production et temps d'amortissement).

- **Moteur Météo & Cycle Temporel Dynamique du Terroir Féodal (`views/partials/rural_atmosphere_overlay.php`, `views/resources.php`, `public/assets/shogun_rural_terroir_*.jpg`) :**
  * **Estampes d'ambiance peintes 16:9 générées & couplées :** Génération et intégration de deux véritables variantes d'art ukiyo-e/shin-hanga rigoureusement calées sur la composition des 9 parcelles :
    - *Nuit Féodale peinte (`shogun_rural_terroir_night.jpg`) :* Ciel indigo profond, croissant de lune, fenêtres et lanternes du village et du Tenshu allumées d'or, rizières miroitantes sous les étoiles.
    - *Hiver sous la Neige peint (`shogun_rural_terroir_winter.jpg`) :* Toits de chaume et donjon poudrés d'un manteau blanc pur, cèdres enneigés, terrasses givrées et torii vermillon éclatant.
  * **Cycle Jour / Nuit / Aube / Crépuscule synchronisé :** Moteur atmosphérique temps réel calé sur l'horloge locale du joueur (Aube dorée 6h-9h, Plein jour 9h-17h, Crépuscule flamboyant Yūgure 17h-20h30, Nuit d'encre Yoru 20h30-6h).
  * **Saisons immersives & Particules :**
    - *Hiver sous la Neige (Yuki) :* Chute de 26 flocons de neige légers en pur CSS GPU combinée à l'illustration hivernale.
    - *Pluie d'Automne (Ame & Kōyō) :* Rideaux de pluie obliques fins et feuilles d'érable japonaises Momiji rouges et pourpres voletant au vent.
    - *Printemps ensoleillé :* Pétales de cerisier Sakura roses et lumière douce d'estampe.
  * **Sélecteur d'Ambiance Météo (Menu Déroulant) :** Menu dropdown Tabler intégré dans l'en-tête du panorama permettant au joueur de choisir son climat préféré (Auto, Plein Jour, Crépuscule, Nuit aux Lanternes, Hiver Enneigé, Pluie & Momiji, ou Mode Zen désactivé) avec mémorisation dans `localStorage`.


- **Intégration Complète de Tous les Éléments de Production dans le Panneau Latéral (`views/partials/rural_harvest_resources_panel.php`, `views/resources.php`, `views/city.php`) :**
  * **Composant réutilisable & unifié :** Extraction et enrichissement du box de récoltes sous la forme d'un cockpit de gestion holistique intégré simultanément sur la vue Terroir (`?page=resources`) et sur la Cité Castrale (`?page=city`).
  * **Intégration exhaustive des 7 composantes fondamentales de production :**
    - *Matières premières & Entrepôts :* Bois de Cèdre (`metal`), Pierre de Taille (`crystal`), Riz Impérial (`deuterium`) avec stocks actuels, capacités maximales, barres d'avancement colorées, alertes de saturation (>90%) et **décomposition granulaire des facteurs de rendement** sous chaque jauge (Base naturelle, Parcelle rurale Niv.X, Atelier de spécialisation, Oasis annexée, Bénédiction du Samouraï Héros, Célébration Matsuri).
    - *Terroirs spécialisés (1-100) :* Argile & Céramique (`fosse_argile`), Thé & Matcha (`culture_the`), Fèves de Soja & Miso (`champ_soja`) avec cadences horaires nettes, progression verticale des niveaux et effectifs d'ouvriers mobilisés.
    - *Vivres raffinées & Réserves stratégiques :* Suivi des stocks de *Farine de Riz* (rations des troupes d'élite et prévention de la famine), de *Saké Féodal* (breuvage sacré des célébrations Matsuri) et de *Poutres de Charpente* (ouvrages et machines de siège).
    - *Ferveur divine & Alimentation énergétique :* Suivi de la ferveur produite par le Sanctuaire Shintō et les Pavillons de thé, de la consommation des installations, calcul du solde net et alerte immédiate en cas de déficit (bridage de la production à 10%).
    - *Main-d'œuvre & Climat social :* Logements Minka du Village, mobilisation des travailleurs (ouvriers en poste vs requis), ratio d'efficacité de production et niveau de contentement populaire (Prospère, Paisible ou Agité).
    - *Matrice des modificateurs actifs :* Synthèse d'un coup d'œil de l'ensemble des multiplicateurs actifs (Samouraï Héros, Scierie, Briqueterie, Meunerie, Pavillon de thé, Matsuri, Oasis).
    - *Oasis sauvages annexées :* Décompte (X / 3) et liste des oasis sous protectorat avec coordonnées précises et bonus cumulés.
  * **Indicateur d'efficacité globale :** Badge synthétique en en-tête calculant le rendement global net (`Énergie % × Main-d'œuvre %`).
  * **Performance & Chargement paresseux :** Initialisation paresseuse conditionnelle évitant toute requête base de données redondante.

### Corrigé (Fixed)
- **Synchronisation & Persistance de la Bénédiction de Récolte du Samouraï Héros (`core/HeroEngine.php`, `core/PlanetEngine.php`, `views/hero.php`, `views/partials/rural_harvest_resources_panel.php`, `views/resources.php`, `public/js/rural_domain_map.js`) :**
  * **Persistance active pendant les aventures & marches :** Suppression de la condition restrictive `status === 'home'`. La bénédiction de récolte du Samouraï bénéficie en permanence à son fief d'attache tant qu'il est vivant (`status !== 'dead'` et `health > 0`), conformément aux règles de gameplay du genre (style Travian).
  * **Résolution résiliente du fief d'attache (`HeroEngine::getHeroProductionBonus`) :** Détection automatique du propriétaire réel du fief et auto-rattachement du héros en cas de `current_planet_id` orphelin ou nul.
  * **Prise en compte des 9 parcelles du Terroir Féodal (`PlanetEngine::calculateProduction`) :** Connexion directe des rendements horaires de la Forêt, Carrière, Rizière et Sanctuaire Shintō (`planet_rural_plots`) dans la formule de production horaire globale et accumulation des stocks.
  * **Indicateurs explicites et badge dédié dans l'interface (`rural_harvest_resources_panel.php`) :**
    - Affichage de tags explicites `+X Héros` sous les flux de Bois, Pierre et Riz.
    - Bannière récapitulative « Bénédiction du Samouraï : +X Bois, +Y Pierre, +Z Riz / h » dans le panneau latéral.
    - Prise en compte du bonus du Samouraï dans la modale d'inspection des parcelles rurales (`rural_domain_map.js`).
  * **Réactivité temps réel sur la fiche du Héros (`views/hero.php`) :**
    - Suppression de la valeur statique `+120 res/h` et calcul dynamique des apports indexés sur `resource_speed`.
    - Prévisualisation instantanée en JavaScript (`updatePointsUI`) du gain de récolte lors de l'attribution des points `+` / `-`.

- **Attribution des Métiers de Développement — Correction de l'Erreur d'Assignation (`api/dev_team.php`, `core/DevTeamEngine.php`, `views/dev_team.php`) :**
  * **Résolution de l'erreur réseau / SQL 500 :**
    - Initialisation systématique de la variable de base de données `$db` en en-tête de `api/dev_team.php` pour éviter l'erreur `Fatal Error: Call to a member function prepare() on null` lors de la vérification des comptes bots.
    - Élargissement de la colonne `icon` de la table `dev_roles` de `VARCHAR(16)` à `VARCHAR(255)` (avec migration automatique) pour accueillir sans troncature les icônes FontAwesome HTML (`<i class="fa-solid ..."></i>`).
    - Exécution du remplissage de référence (`seedReferenceData`) garantissant la présence des 9 rôles métiers dans `dev_roles` et évitant la violation de clé étrangère `fk_udr_role` lors de l'insertion dans `user_dev_roles`.
  * **Robustesse client Tabler.io :**
    - Intégration des fonctions manquantes `openAssignRoleModal()` et `confirmRemoveDevRole()` dans `views/dev_team.php` pour fluidifier l'ouverture du modal et la révocation interactive de métiers.
    - Gestion d'erreur améliorée dans `handleAssignRoleSubmit()` affichant le message d'erreur serveur précis plutôt qu'un message générique « Erreur réseau ».

- **Déblocage du Didacticiel du Daimyō — Validation des Parcelles du Terroir (`core/QuestEngine.php`) :**
  * **Prise en charge du Terroir Féodal :** Résolution du blocage sur l'étape 1 « Ouvrir le Terroir / Premier Arpent de Cèdre » (et étapes 2 à 4) où le moteur de quêtes vérifiait uniquement l'ancienne table `planet_fields` (`metal_mine`, etc.).
  * **Mappage universel :** `QuestEngine::evaluateCondition()` contrôle désormais en priorité la table `planet_rural_plots` pour les 9 parcelles du terroir (`foret`, `carriere`, `riziere`, `sanctuaire_shinto`, `tenshu`), validant instantanément l'objectif dès que le joueur élève son camp de bûcherons ou ses structures rurales.

- **Ambiance Vivante du Terroir — Estampe Japonaise Animée en Arrière-Plan (`views/partials/rural_atmosphere_overlay.php`, `views/resources.php`) :**
  * **Vie rurale féodale en mouvement & Rythme contemplatif :** Surcouche visuelle légère (pur SVG vectoriel + animations GPU 60 FPS CSS3) donnant vie au panorama 16:9 du domaine rural :
    - *Vol majestueux des grues du Japon :* Traversée intégrale d'est en ouest sans à-coups ni arrêts figés sur tout l'horizon (durée ralentie à 72s linéaire avec battement d'ailes ample à 1.6s).
    - *Circulation fidèle sur les chemins de terre :* Trajectoires des paysans et de la charrette calées précisément sur les sentiers dégagés en pourcentages du terroir 16:9, contournant soigneusement tous les bâtiments, rizières, chaumières et sanctuaires.
    - *Allure posée & Convoi traditionnel :* Vitesse de marche des villageois apaisée (durées de trajet 70-76s, balancement des pas à 0.95s) et marche lente d'un bœuf de labour tractant sa charrette sur la grand-route (105s, balancement à 1.8s).
    - *Volutes de fumée & Brume :* Émanations du donjon Tenshu, des chaumières du village central et de l'encens sacré du sanctuaire Shintō, complétées par les nappes de brume d'altitude.
    - *Pétales de cerisier (Sakura) :* Pétales roses tournoyant délicatement au vent printanier.
  * **Performance & Respect de l'ergonomie :** Surcouche non-intrusive (`pointer-events: none`), n'interceptant aucun clic sur les 9 parcelles, les modales ou les chantiers. Poids plume (< 30 Ko sans aucune vidéo).
  * **Contrôle du joueur & Mode Zen :** Bouton à bascule « Ambiance Vivante : On/Off » dans l'en-tête de la carte avec persistance du choix du joueur dans `localStorage`.

- **Intégration de la sauvegarde & restauration de la base dans l'Interface d'Administration (`views/admin.php`, `api/admin.php`) :**
  * **Onglet Maintenance unifié :** Ajout d'une section dédiée complète « Sauvegarde & Restauration de la Base de Données (Structure & Données Séparées) » au panneau d'administration.
  * **Déclenchement immédiat :** Bouton de création de sauvegarde instantanée (générant séparément les fichiers DDL et DML) avec détection en temps réel des clés USB / disques externes (`/media`, `/mnt`) ou point de montage personnalisé.
  * **Historique & réintégration :** Tableau des jeux de sauvegarde horodatés avec statut d'intégrité (Paire Complète, Structure Seule, Données Seules) et modale sécurisée de restauration avec confirmation par mot-clé `RESTORE`.
- **Affichage clair de la propriété et des particularités au clic des parcelles sur la Carte (`views/map.php`, `core/GalaxyEngine.php`) :**
  * **Statut de propriété explicite :** Affichage systématique dans le modal de la carte du statut foncier (« Terre Libre & Domaine Non Réclamé », « Propriétaire Foncier : Aucun ») pour les parcelles inoccupées et les terres vierges.
  * **Potentiels verticaux garantis :** Intégration d'un algorithme déterministe garantissant la présence et le rendu des 9 parcelles de terroir avec niveaux actuels et plafonds d'évolution (`max_level`, spécialités régionales dorées), que la tuile provienne de la table `planets` ou de la grille procédurale naturelle.
- **Ressources de départ par défaut à 1000 & Calibrage Féodal (`core/WorldGenerator.php`, `core/BotEngine.php`, `core/Auth.php`) :**
  * **Ressources initiales :** Chaque joueur (administrateur, nouveau joueur inscrit, bot) démarre désormais par défaut avec 1000 unités de chaque ressource de base (1000 Bois de Cèdre, 1000 Pierre de Taille, 1000 Riz Impérial).
- **Suppression des saccades de la carte & Déplacement ultra-fluide 60fps (`public/js/galaxy_map.js`) :**
  * **Optimisation du Drag & Drop :** Élimination du recadrage / rechargement AJAX répétitif pendant le mouvement du curseur. La translation est désormais assurée en pur GPU (`translate3d`), le calcul du décalage de coordonnées et le rafraîchissement des secteurs ne s'effectuant qu'au relâchement du pointeur.
- **Système autonome de sauvegarde et réintégration séparées (Structure & Données) avec support USB (`core/DatabaseBackupService.php`, `scripts/backup_database.php`) :**
  * **Exports séparés DDL / DML :** Génération distincte de la structure (`structure_openshogun_YYYYMMDD_HHMMSS.sql`) et des données (`data_openshogun_YYYYMMDD_HHMMSS.sql`) avec désactivation contrôlée des clés étrangères.
  * **Double destination :** Sauvegarde dans le dossier interne `database/backups/` et réplication automatique sur support USB / disque externe connecté (détection automatique dans `/media` et `/mnt` ou spécification via `--usb=`).
  * **Outil autonome :** Accessible en ligne de commande (`php scripts/backup_database.php backup|list|restore`) et directement via l'interface d'administration.
- **Mutualisation du bloc de file de chantiers urbains & correction du lien Donjon / Cité (`views/partials/urban_construction_queue.php`, `views/city.php`, `views/resources.php`, `public/js/rural_domain_map.js`) :**
  * **Composant réutilisable `urban_construction_queue.php` :** Extraction mutualisée du bloc de chantiers en cours dans `views/partials/` avec prise en charge unifiée des bâtiments urbains, des parcelles rurales (`rural_plot`) et des champs (`field`), décomptes interactifs temps réel, annulation de chantiers (`cancelBuild` / `cancelRuralUpgrade`) et badge de double file pour le Clan Oda.
  * **Intégration harmonieuse :** Intégration du partial à la fois sur la page Cité (`views/city.php`) et sur la page Ressources (`views/resources.php`).
  * **Correction du lien du Donjon / Tenshu :** Le clic sur l'icône / le marqueur du Tenshu (Dojo / Donjon) dans la vue panoramique du domaine rural redirige désormais directement vers la vue urbaine de la cité castrale (`/?page=city`) au lieu de l'ancienne page générique `/?page=buildings`.
- **Réinitialisation stricte de l'Univers féodal à zéro (`core/WorldGenerator.php`, `core/VillageGeneratorService.php`, `core/BotEngine.php`) :**
  * **Ressources à 0 :** Lors de la réinitialisation de l'univers (`resetUniverse`), la capitale du joueur administrateur, les domaines des bots et les planètes neutres démarrent désormais rigoureusement avec 0 ressources (Bois = 0, Pierre = 0, Riz = 0).
  * **Bâtiments et parcelles à niveau 0 :** Tous les bâtiments urbains et toutes les parcelles rurales (les 9 parcelles du Terroir ainsi que les champs) sont réinitialisés et générés avec un niveau initial de 0, 0 ouvriers affectés et une production horaire nulle.
  * **Nettoyage intégral :** Ajout des tables `planet_rural_plots` et `craft_queue` aux purges `TRUNCATE TABLE` du `WorldGenerator`.
- **Simulateur de Dérivation des Bâtiments & Contrôle Global des Courbes de Construction (`views/studio/game-elevate-designer/building-derivation.php`, `core/BuildingEngine.php`, `core/RuralPlotEngine.php`, `core/DevTeamEngine.php`, `core/GameConfig.php`, `api/dev_team.php`, `database/migrations/002_seed_game_data.sql`) :**
  * **Nouveau simulateur Studio Dev (`?page=dev_team&metier=game-elevate-designer&module=building-derivation`) :** Intégration d'un atelier d'équilibrage complet pour le métier Game Elevate Designer permettant de projeter, simuler et calibrer les durées de chantier et les coûts en ressources (Bois, Pierre, Riz) du niveau 1 au niveau 20+ avec graphique SVG interactif de dérivation, calculs de variations marginales ($\Delta$ coût, $\Delta$ durée) et tableau analytique complet.
  * **Coefficients d'ajustement dynamique :** Réglage en direct du multiplicateur de durée (`building_time_coeff`), de la pente temporelle exponentielle (`building_time_growth`), du multiplicateur global de coût (`building_cost_coeff`) et du facteur d'inflation des coûts par niveau (`building_cost_growth`).
  * **Préréglages rapides d'équilibrage :** Presets en un clic (Standard Sengoku, Chantiers Éclair, Long Terme, Abondance Économique, Chantiers Monumentaux).
  * **Impact direct et immédiat sur le jeu réel :** Tous les calculs de chantiers et d'améliorations (`BuildingEngine` pour les bâtiments de la cité et les parcelles, ainsi que `RuralPlotEngine` pour le terroir rural) se basent désormais en temps réel sur les coefficients réglés et sauvegardés dans ce simulateur via `game_settings`.
- **Réactivation du double développement simultané selon le clan féodal (Oda vs Takeda/Tokugawa) & Sceau Impérial (`core/BuildingEngine.php`, `core/RuralPlotEngine.php`, `views/city.php`, `views/building.php`, `views/field.php`, `views/resources.php`) :**
  * **Clan Oda (`terran`) :** Rétablissement de la capacité signature de double développement simultané permettant de mener en parallèle 1 chantier de parcelle rurale (Forêt, Carrière, Rizière, etc.) et 1 chantier d'infrastructure urbaine (Tenshu, Forge, etc.). Avec le Sceau Impérial décrété, 2 ruraux et 2 urbains progressent de front (jusqu'à 4 simultanés).
  * **Clan Takeda (`vorash`) & Clan Tokugawa (`aethelis`) :** Limitation stricte à 1 chantier unique sur l'ensemble du domaine (rural ou urbain), extensible à 2 chantiers simultanés toutes catégories confondues sous le Sceau Impérial.
  * **Correction de régression de file :** Résolution du bug où les parcelles rurales de la nouvelle refonte (`build_category = 'rural_plot'`) tombaient dans la clause `else { $buildingsInQueue++; }` des vues urbaines, bloquant à tort toute construction en ville.
  * **Badges et indicateurs UI :** Affichage d'un badge « Double Chantier (Clan Oda) » dans le panorama du terroir et dans la colonne latérale des chantiers en cours sur la page Ressources.
  * **Tests automatisés :** Ajout d'une suite de tests de concurrence dans `tests/test_village_generator_9plots.php` validant l'étanchéité des files selon la faction.

### Modifié (Changed)
- **Simplification épurée des badges du terroir (Logo + Niveaux) et liaison directe du Tenshu vers la cité (`views/resources.php`, `public/js/rural_domain_map.js`, `core/RuralPlotEngine.php`) :**
  * Remplacement des anciens grands badges (> 175px) par des micro-pilules ultra-compactes n'obstruant plus la composition panoramique 16:9.
  * Suppression de la barre de progression sur les tuiles de la carte : affichage strict du logo et de la jauge textuelle `Niv. [Actuel] / [Max]` avec indicateur de travail subtil.
  * Déconnexion de l'évolution rurale du Donjon (Tenshu) : transformation en passerelle directe vers la cité castrale (`/?page=buildings`).
- **Refonte du HUD de ressources et statistiques de population en un ruban horizontal unifié compact (< 44px) aux couleurs du site (`views/partials/header.php`, `views/resources.php`) :**
  * Élimination des doublons : fusion de la Sérénité Shintō, de la Population et du Contentement au sein d'une seule et même barre d'en-tête horizontale compacte (`card-sm`, hauteur contenue < 44px), supprimant le bloc redondant de 4 cartes dans `views/resources.php` et libérant plus de 150px verticaux au-dessus du Terroir.
  * Respect absolu de la charte graphique et des teintes du site : surface blanche épurée (`bg-white`), bordures délicates (`border-secondary-subtle`), typographie sombre contrastée et accents de couleurs féodales d'origine (Bois, Pierre, Riz, Farine, Saké, Poutres).
  * Micro-jauges fines (3px) discrètes sous chaque ressource et conservation stricte de tous les hooks JavaScript (`res-val-*`, `bar-*`, `data-current`, `data-max`, `data-prod`) pour les animations et rafraîchissements temps réel sans régression.
  * Infobulles et popovers enrichis (production/heure, seuils de stockage, facteurs de sérénité et de contentement) au survol et au clic.

### Corrigé (Fixed)
- **Résolution du crash `PDOException: There is no active transaction` lors de la réinitialisation de l'univers (`core/VillageGeneratorService.php`, `core/BotEngine.php`) :**
  * **Cause :** L'appel à `ensureTableExists()` dans le constructeur de `VillageGeneratorService` exécutait un `CREATE TABLE IF NOT EXISTS` en MySQL. En MySQL, tout ordre DDL provoque un `COMMIT` implicite immédiat, rompant silencieusement la transaction amorcée par `BotEngine::createBot()` (`$this->db->beginTransaction()`). Lors du `$this->db->commit()` ou `$this->db->rollBack()`, PDO levait l'exception fatale `There is no active transaction`.
  * **Correctif :** `VillageGeneratorService::ensureTableExists()` vérifie désormais si une transaction est en cours via `$this->db->inTransaction()` avant d'émettre des requêtes DDL, et mémorise l'état via un drapeau statique `self::$tableChecked`.
  * **Sécurisation défensive :** Ajout de gardes `$this->db->inTransaction()` avant chaque appel à `$this->db->commit()` et `$this->db->rollBack()` dans `BotEngine` pour garantir une résilience totale en cas de transaction préalablement clôturée.
- **Résolution de la colonne manquante `newsletter_optin` et fiabilisation de `users` (`database/migrations/001_baseline_schema.sql`, `database/schema.sql`, `database/schema_complete.sql`, `core/InstallEngine.php`) :**
  * Correction du blocage d'installation `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'newsletter_optin' in 'SET'` lors de la mise à jour du compte administrateur à l'Étape 8 de `install.php`.
  * Intégration des colonnes `is_active`, `email_verified_at`, `activation_token`, `activation_token_expires_at` et `newsletter_optin` (avec leurs index respectifs) dans le DDL de la table `users` de tous les schémas de référence (`001_baseline_schema.sql`, `database/schema.sql`, `database/schema_complete.sql`).
  * Mise en place d'une auto-guérison préventive des colonnes de `users` et d'une requête de repli résiliente dans `InstallEngine::runInstallation()` prévenant tout échec sur d'anciennes bases ou installations partielles.
- **Intégration de la table `alliances` dans le schéma baseline et sécurisation de `WorldGenerator::resetUniverse` (`database/migrations/001_baseline_schema.sql`, `database/schema.sql`, `database/schema_complete.sql`, `core/WorldGenerator.php`) :**
  * Correction du blocage d'installation `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'openshogun.alliances' doesn't exist` lors de la réinitialisation de l'univers féodal (Étape 7).
  * Ajout du DDL complet de la table `alliances` (`id`, `name`, `tag`, `leader_id`, `description`, `created_at` avec contraintes uniques et index) dans `001_baseline_schema.sql`, `database/schema.sql` et `database/schema_complete.sql` (schéma consolidé à 43 tables).
  * Résolution de la dépendance de clé étrangère requise par `alliance_invitations` (`fk_inv_alliance`).
  * Sécurisation défensive de `WorldGenerator::resetUniverse()` avec gestion d'exception `try / catch (PDOException $e)` sur les opérations `TRUNCATE TABLE`, évitant tout crash bloquant si une table dynamique est absente ou différée.
- **Résolution de la colonne manquante `avatar` dans `Auth::getCurrentUser()` (`core/Auth.php`, `core/InstallEngine.php`, `database/schema.sql`, `database/schema_complete.sql`) :**
  * Correction du crash `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'avatar'` survenant au chargement de `index.php` après une nouvelle installation.
  * Ajout de la colonne `avatar VARCHAR(255) NULL AFTER bio` dans les DDL de la table `users` des schémas SQL.
  * Implémentation d'une requête de secours résiliente (`try/catch PDOException`) dans `Auth::getCurrentUser()` afin de parer immédiatement à toute absence de colonne dans des bases existantes non migrées.
  * Automatisation de la vérification et migration des colonnes `bio` et `avatar` dans l'assistant `InstallEngine`.
- **Résilience de l'Assistant d'Installation & Création de la table `game_settings` (`core/InstallEngine.php`, `core/WorldGenerator.php`, `core/Database.php`, `database/schema.sql`, `database/schema_complete.sql`) :**
  * Élimination de l'erreur `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'game_settings' doesn't exist` lors du déploiement via `install.php`.
  * Ajout du DDL de `game_settings` dans `database/schema.sql` et exécution préventive `CREATE TABLE IF NOT EXISTS` dans l'étape 6 de `InstallEngine::runInstallation`.
  * Remplacement des 25 occurrences de `current_timestamp()` par `CURRENT_TIMESTAMP` dans `database/schema_complete.sql` garantissant la compatibilité multi-moteurs MySQL 5.7+, 8.0+, 8.4+ et MariaDB 10.x/11.x.
  * Amélioration du parseur `InstallEngine::executeSqlFile` : filtrage des commentaires multilignes (`/* ... */`) et détection explicite sans masquage des erreurs d'exécution DDL (`CREATE TABLE`).
  * Support de l'injection d'instance PDO active dans `WorldGenerator` et méthode `Database::setConnection(?PDO $pdo)` évitant toute incohérence de session durant l'installation.
- **Résolution de la Fuite de Script JS & Clamping Pan/Zoom Strict sur le Domaine Rural (`views/resources.php`, `public/js/terroir_map.js`) :**
  * Correction de la fuite de texte JavaScript causée par des guillemets orphelins dans les attributs `title` des 40 parcelles (échappement complet `htmlspecialchars` avec `ENT_QUOTES`).
  * Découplage et externalisation de plus de 450 lignes de code JavaScript dans le nouvel asset dédié `/public/js/terroir_map.js` avec chargement propre et sécurisé.
  * Verrouillage strict de l'échelle minimale (`minZoom`) : calcul automatique $minScale = \max(containerWidth / stageWidth, containerHeight / stageHeight)$ pour garantir 100% de couverture écran sans fond noir.
  * Verrouillage des bords au glissement (Bounding Box Clamping) : limitation absolue des coordonnées de translation dans $[containerWidth - scaledWidth, 0]$ et $[containerHeight - scaledHeight, 0]$ interdisant toute exposition des marges vides au drag souris ou tactile.

### Supprimé (Removed)
- **Suppression de la première rangée de compteurs de ressources dans la vue Terroir (`views/resources.php`) :**
  * Retrait de la Rangée 1 (les 6 cartes de stocks et productions horaires : Bois, Pierre, Argile, Riz, Thé, Soja), devenue redondante avec la barre de ressources globale du header et les 40 parcelles illustrées/tactiques.
  * Conservation exclusive des 4 indicateurs stratégiques de pilotage (Sérénité Shintō, Population globale, Mobilisation de la main-d'œuvre, Contentement féodal) au-dessus de la carte et de la grille des 40 parcelles.
  * Épuration des règles CSS associées `.kpi-resource-*`.

### Ajouté (Added)
- **Refonte DevOps & Consolidation Idempotente de la Base de Données (`database/migrations/`, `core/MigrationEngine.php`, `scripts/migrate.php`, `Dockerfile`, `docker-compose.yml`, `.env.example`) :**
  * Unification intégrale des 42 tables du jeu dans un schéma de référence unique `001_baseline_schema.sql` éliminant toute fragmentation DDL.
  * Découplage des graines statiques de référence (unités, recherches, vaisseaux, châteaux, catégories forum, settings) dans `002_seed_game_data.sql` avec clauses idempotentes `INSERT ... ON DUPLICATE KEY UPDATE`.
  * Nouveau moteur de migration `core/MigrationEngine.php` avec table d'historique `schema_migrations`, calcul d'empreinte SHA-256 et runner CLI `scripts/migrate.php` (`--status`, exécution automatique).
  * Intégration du moteur de migration dans l'assistant web `InstallEngine`.
  * Support 12-factor des variables d'environnement (`.env.example`, `core/Database.php`) et conteneurisation complète avec `Dockerfile` et `docker-compose.yml` (MariaDB 10.11, healthchecks, volumes persistants, initialisation automatique `/docker-entrypoint-initdb.d/`).
- **Carte Illustrée des 40 Parcelles Féodales — Panorama 16:9 Ukiyo-e & Viewport Grab-and-Pan (`views/resources.php`, `core/TerroirEngine.php`, `public/assets/terroir_panoramic_16_9.jpg`, `views/partials/grimoire_prompts_data.php`) :**
  * Nouvelle création picturale originale haute définition au format widescreen 16:9 (`1376×768 px`), inspirée des estampes ukiyo-e et de la peinture numérique semi-réaliste féodale (encrage fin, textures d'aquarelle, lumière dorée d'aurore et brume matinale).
  * Composition spatiale isométrique distribuant 40 clairières et plateformes d'exploitation réparties sur 8 biomes stratégiques interconnectés (Carrières de pierre au nord, Forêt de cèdres au nord-est, Berges d'argile à l'est, Rizières en terrasses au centre-sud, Collines de thé au sud-est, Champs de soja au sud-ouest, Sanctuaires shintō à l'ouest, Cœur du village au centre).
  * Moteur de navigation tactile et souris fluide (« Grab-and-Pan » direct en JavaScript Vanilla) avec zoom à la molette (0.5x à 2.2x), travelling cinématique par catégorie et barre d'outils flottante.
  * Superposition des 40 tokens interactifs avec niveau, ouvriers et flux horaires, reliés à la modale d'élévation rapide (`#parcelUpgradeModal`).
  * Enregistrement de la fiche descriptive et du prompt source `pano_terroir_16_9` dans le Grimoire des Prompts avec badge de transparence IA.
- **Grille des 40 Parcelles du Domaine Rural & Simplification des Habitations (`views/resources.php`, `core/TerroirEngine.php`) :**
  * Restructuration ergonomique complète de la vue des ressources : transition vers une grille de 40 parcelles ordonnées en 8 catégories thématiques de 5 slots chacune (Bois, Pierre, Argile, Riz, Thé, Soja, Sérénité/Sanctuaires, Habitations).
  * Simplification du système de logement : suppression des sous-types complexes et déploiement d'un modèle unique « Habitation » (Niveaux 1 à N) avec capacité totale calculée dynamiquement selon la formule $\text{Capacité Totale} = 75 \text{ (base)} + (\sum_{i=1}^5 \text{Niveau}(H_i) \times 5)$.
  * Filtre interactif de catégories en tête de grille (badges pills) pour un ciblage instantané des parcelles en JavaScript Vanilla.
  * En-tête enrichi à 10 indicateurs clés : 6 compteurs de ressources (stock et cadence horaire) et 4 jauges de pilotage (Sérénité shinto, Population globale, Affectation de la main-d'œuvre, Contentement avec bonus net asymétrique de saké).
- **Grimoire des Prompts, Traçabilité IA & Harmonisation des Modales (`views/partials/grimoire_prompts_data.php`, `core/AiPromptHelper.php`, `views/partials/ai_prompt_modal.php`, `public/js/ai_prompt_modal.js`, `public/css/ai_prompt_modal.css`) :**
  * Intégration de 11 nouvelles fiches descriptives au Grimoire des Prompts (totalisant désormais 91 prompts référencés) couvrant les décors ruraux (Forêt, Montagne, Argile, Rizières, Thé, Soja, Village) et les bannières historiques de clans.
  * Modernisation du badge de transparence IA avec l'icône Font Awesome `fa-wand-magic-sparkles`, infobulles Tabler.io natives et variante capsule/pill (`ai-prompt-badge-pill`).
  * Nouvelle modale d'affichage universelle intégrant l'aperçu HD plein écran, une barre de métadonnées à 3 indicateurs (Modèle IA Google Gemini Imagen 3, Date de génération, Résolution), le prompt source anglais avec copie en 1 clic et la traduction française.
  * Déploiement des badges IA sur la Carte Interactive du Terroir Rural (`views/resources.php`) et sur l'ensemble des scènes des 7 zones féodales (`views/view_resource.php`).
- **Carte Interactive du Terroir Rural & Vues Dédiées 5 Slots par Ressource (`views/resources.php`, `views/view_resource.php`, `views/view_village.php`, `core/TerroirEngine.php`, `api/terroir.php`) :**
  * Nouvelle carte panoramique du terroir féodal (`1696x2528 px`) avec 7 badges interactifs positionnés en pourcentages CSS sur les repères du décor (Montagne, Forêt, Argile, Rizières, Thé, Soja, Village).
  * Effets de survol enrichis : zoom contextuel, halo lumineux pulsant (`zone-badge-beacon`), tooltips Tabler.io avec cadences horaires et redirection directe.
  * Vues thématiques dédiées par ressource avec fond d'écran haute définition et composition spatiale ergonomique de 5 slots de développement.
  * Grille de cartes Tabler.io affichant niveau, ouvriers affectés par type de métier, cadence horaire et bouton d'élévation asynchrone AJAX (`api/terroir.php`).
  * Vue dédiée au Village central avec 3 widgets KPI Tabler (Démographie, Affectation de la main-d'œuvre, Contentement) et 5 infrastructures de vie (Minka, Nagaya, Sanctuaires, Moulin hydraulique, Brasserie de Saké).
  * Bascule fluide 1-clic entre la Carte Interactive et la vue classique des 18 parcelles.
- **Simulateur de Vie dans un Village & Bac à Sable Démographique (`views/studio/game-elevate-designer/village-life-simulator.php`, `core/AuthManager.php`, `core/DevTeamEngine.php`) :**
  * Module d'équilibrage et de simulation démographique interactif réservé au métier Game Elevate Designer (contrôle strict `AuthManager::hasJob`).
  * Horloge d'accélération temporelle de x1 à x100 avec contrôles Play, Pause, Réinitialiser et pas à pas horaire (+1h, +24h).
  * Curseurs interactifs des flux de production horaire (Riz, Farine, Saké, Sérénité) et 4 scénarios d'équilibrage en 1 clic.
  * Moniteur temps réel avec 4 jauges KPI dynamiques et graphique multi-courbes ApexCharts (avec fallback Canvas HD).
  * Chronique événementielle en direct (Ticker) et générateur d'export JSON pour les bilans de game design.
- **Système de Population, Main-d'Œuvre, Règle Asymétrique du Saké & Mécanique d'Exode (`core/PopulationEngine.php`, `core/PlanetEngine.php`, `views/partials/header.php`, `views/building.php`, `views/field.php`, `views/city.php`, `views/resources.php`) :**
  * Quotas d'ouvriers requis par niveau pour les 4 parcelles rurales et 16 structures urbaines.
  * Malus proportionnel automatique sur le rendement horaire des récoltes en cas de sous-effectif global.
  * Distinction vitale vs confort : la nourriture et la sérénité shinto dégradent le contentement en cas de pénurie (-55% / -20%).
  * Règle asymétrique stricte du Saké : la présence de saké octroie un bonus net (+15%), son absence n'inflige strictement AUCUN malus (0 neutre).
  * Jauge de satisfaction (0-100%) et crise d'exode : fuite de 6% d'habitants par heure si le contentement chute sous 25% (plancher de sécurité de 20 villageois).
  * 8e carte « Peuple & Satisfaction » dans le bandeau supérieur avec code couleur réactif, animation clignotante en cas d'exode, icône de saké et popover Tabler.io explicatif.
  * Badges d'ouvriers requis et indicateurs de sous-effectif sur l'ensemble des fiches d'édifices et de parcelles.
- **Module Forum : Éditeur WYSIWYG Moderne & Neutralisation XSS Backend (`views/forum.php`, `core/ForumEngine.php`) :**
  * Intégration de l'éditeur Quill.js (Thème Snow) pour la création de sujets, réponses rapides et édition de messages.
  * Outils typographiques : enrichissements de texte, listes, citations, liens et images.
  * Sécurisation backend robuste via `ForumEngine::sanitizeHtml()` éliminant les balises script/iframe, écouteurs `on*` inline et protocoles non sécurisés.
- **Persistance Immédiate de l'Avatar Féodal & Cache-Busting (`api/profile.php`, `core/Auth.php`, `core/HonorEngine.php`, `views/poster.php`) :**
  * Correction du chemin d'écriture `/public/assets/uploads/avatars/` et validation des commits PDO.
  * Mise à jour instantanée des variables de session actives sans exiger de reconnexion.
  * Cache-busting dynamique `?v=` côté serveur et JavaScript pour un rafraîchissement immédiat de l'image.
- **Architecture de Télémétrie & Logs d'Activité (`core/ActivityTracker.php`, `database/migrate_activity_logs.sql`) :**
  * Structure de données optimisée `activity_logs` enregistrant `created_at`, `user_id`, `page_slug`, `tab_slug`, `action`, `device_type` et l'adresse IP anonymisée par salage et hachage SHA-256 (conformité RGPD).
  * Helper universel non bloquant `ActivityTracker::logView()` interceptant les navigations des visiteurs publics et des seigneurs authentifiés sans ralentir le cycle de vie de l'application.
- **Suite Analytique Avancée & Tableaux de Bord Interactifs (`views/admin.php`) :**
  * Intégration de 4 KPI Cards avec calcul dynamique des variations en pourcentage (%) : Total Pages Vues, Daimyōs Actifs Uniques (DAU/MAU), Taux Humains vs Bots/Crawlers, Durée Moyenne par Session.
  * Graphique 1 (Spline Area Chart ApexCharts) : Évolution multi-séries croisant Pages Vues, Daimyōs Actifs et Inscriptions sur 1, 7, 30 ou 60 jours.
  * Graphique 2 (Horizontal Bar Chart ApexCharts) : Palmarès Top 8 des modules et pages consultées.
  * Graphique 3 (Column Bar Chart ApexCharts) : Répartition de l'affluence par tranches horaires (0h à 23h).
  * Tableau dynamique des Daimyōs les plus actifs avec filtre de recherche textuel instantané en JavaScript Vanilla.
  * Filtrage temporel réactif avec boutons d'accès rapide (Aujourd'hui, 7 Jours, 30 Jours, Tout).
- **Bouton Statistiques & Métriques dans la Sous-Barre Header (`views/partials/header.php`) :**
  * Ajout d'un bouton d'action compact vers le tableau de bord administratif (`?page=admin&tab=dashboard`), placé immédiatement avant le bouton d'administration générale.
  * Format visuel épuré : icône seule Font Awesome (`fa-solid fa-chart-line fs-3`), badge Tabler sarcelle (`bg-teal-lt text-teal`), infobulle native Bootstrap/Tabler et restriction d'accès aux administrateurs.
- **École du Backend & 3 Cours Illustrés pour Débutants (`views/atelier-pedagogique/backend/`) :**
  * Conception d'un module d'apprentissage vulgarisé pour les 12 ans et débutants avec métaphores du Japon féodal, encadrés « Le savais-tu ? » et typographie Dela Gothic One.
  * **Cours 01 (`php-poo-singleton.php`) :** POO expliquée via l'atelier de forge (Classe = plan, Objet = sabre forgé), le patron de conception Singleton via le Facteur Impérial Unique (`MailService`), et la persistance utilisateur via le Sceau de Cire des Sessions (`$_SESSION`).
  * **Cours 02 (`pdo-sql-injection.php`) :** Base de données expliquée comme le Coffre-Fort du Shogun, injection SQL illustrée par le Parchemin Piégé d'un bandit ninja, et sécurisation par requêtes préparées PDO (`prepare`/`execute`) comme une boîte aux lettres à fente blindée.
  * **Cours 03 (`routing-get-post.php`) :** Routage d'URL expliqué par les Panneaux indicateurs de Kyoto (`index.php`), méthode `$_GET` comparée à une Carte Postale transparente, et méthode `$_POST` comparée à la Missive Secrète Scellée portée par un ninja.
  * Mini-quiz interactifs en JavaScript Vanilla (2 questions par cours) avec validation instantanée et félicitations du score.
  * Section 5 « L'École du Backend » ajoutée au sommaire de l'Atelier Pédagogique (`views/partials/admin_pedagogy.php`) avec boutons d'accès et mise à jour du routeur (`index.php`, `views/pedagogy.php`).
- **Moteur de Siège & Dégradation des Bâtiments Féodaux (`core/CombatEngine.php`) :**
  * Distinction et calcul spécifique de la force de sape des unités de siège (`terran_cruiser`, `terran_dreadnought`, `vorash_leviathan`, `aethelis_prism`, `aethelis_titan`).
  * Réduction prioritaire sur le niveau de la muraille du village défenseur (250 PV structurels par niveau).
  * En cas d'effondrement ou brèche totale (niveau 0), report proportionnel des dégâts de siège résiduels sur les bâtiments urbains du fief selon l'ordre de priorité tactique (Tenshu, Dojo, Entrepôts, Grenier Kura, Forge, etc.).
  * Prise en compte de la destruction totale d'un bâtiment (niveau 0 / ruine à reconstruire) et mise à jour effective dans la table `planet_buildings`.
  * Intégration de la structure `infrastructure_damage` dans le rapport de combat persistant et ajustement dynamique du titre de la bataille.
- **Extension du Simulateur de Combat Studio Dev (`views/studio/game-elevate-designer/combat-simulator.php`) :**
  * Configuration interactive des 8 édifices castraux du village cible (Tenshu, Dojo, Entrepôt, Grenier Kura, Forge, Tour, Atelier, Maçonnerie).
  * Encart de référence technique documentant les variables d'équilibrage martial et formules de résistance (PV muraille, PV bâtiments, renfort Maçonnerie, multiplicateurs béliers et catapultes).
  * Section dédiée majeure dans le rapport : « Dégâts aux Infrastructures & État des Bâtiments du Fief » avec transition de la muraille, tableau complet des dégradations et statuts Tabler.io colorés (Détruit, Endommagé, Intact).
  * Export Markdown enrichi incluant la matrice d'attrition des infrastructures.

### Corrigé & Sécurité (Fixed)
- **Débogage du Graphique d'Activité Dashboard (`views/admin.php`) :**
  * Correction du non-affichage du graphique causé par un canvas initialisé avec une largeur nulle (`rect.width <= 0`) lors du masquage/affichage d'onglets.
  * Remplacement de la requête SQL incomplète par une génération de série temporelle continue (`ActivityTracker::getTimelineTrend`) comblant automatiquement les dates sans activité pour éliminer les ruptures de courbe.
  * Migration vers ApexCharts avec redimensionnement automatique responsive et maintien d'un canvas de repli non bloquant.
- **Débogage du Bouton « Mise à Jour » dans l'Administration (`views/partials/admin_updates.php`, `views/admin.php`) :**
  * Éradication des erreurs JavaScript `TypeError: Cannot read properties of undefined` sur `data.local.short_sha` et `target_branch` lors du contrôle GitHub.
  * Vérifications défensives systématiques sur l'ensemble des éléments DOM (`bannerBox`, `bannerTitle`, `bannerDesc`, `bannerIcon`, `navBadge`).
  * Remplacement des fenêtres modales natives bloquantes `confirm()` et `alert()` par une modale Tabler.io moderne (`#modal-confirm-update-deploy`) conforme aux directives du projet.
  * Gestion complète des promesses fetch et des erreurs HTTP avec retours utilisateurs propres via toasts Tabler.io.
  * Ajout de l'identifiant `#admin-update-nav-badge` pour synchronisation visuelle réactive de l'onglet.

## [1.17.0] - 2026-10-02
### Ajouté (Added)
- **Simulateur de Combat Tactique & Calcul Critique de la Muraille (`views/studio/game-elevate-designer/combat-simulator.php`, `api/dev_team.php`) :**
  * Outil d'équilibrage martial en 2 colonnes comparatives (Split-screen) pour le Game Elevate Designer : Attaquant (clan, troupes, général daimyō, doctrine offensive) vs Défenseur (garnison et caractéristiques du village).
  * ⭐ **Prise en compte critique du niveau de muraille du village cible (niveaux 0 à 20) :** curseur réactif appliquant instantanément le bonus multiplicateur défensif (+4% Oda, +3.5% Takeda, +5% Tokugawa +15% passif de faction), les points de vie structurels de la muraille (250 PV/niveau) et les tirs de meurtrières défensifs (15 pts/niveau).
  * Préréglages rapides en 1 clic : Raid sur village ouvert (Mur 0), Assaut d'un bourg fortifié (Mur 8), Grand siège de forteresse (Mur 20).
  * Moteur de combat multi-rounds avec phase de siège préliminaire (dégradation et brèche des remparts par les béliers et catapultes avant la mêlée).
  * Rapport de combat instantané : statut de l'engagement, jauges d'attrition Tabler.io, bilan comparatif régiment par régiment, journal détaillé tour par tour (Combat Log accordéon), export Markdown en 1 clic et enregistrement du test en base (+15 XP Forge).
- **Restructuration de l'Architecture de Studio Dev par Métiers (`views/dev_team.php`, `views/studio/`, `core/DevTeamEngine.php`, `index.php`) :**
  * Modularisation de l'arborescence physique en 11 vues modulaires sous `views/studio/<slug_metier>/<module>.php` (`game-elevate-designer/`, `community-manager/`, `qa-tester/`, `backend-dev/`, `narrative-designer/`, `roster/`, `forge/`).
  * Nouvelle sous-navigation par module (`nav-pills` Tabler.io) sous chaque métier pour basculer aisément entre les outils du métier.

### Modifié & Ergonomie (Changed)
- **Organisation de la Navigation de Studio Dev :** Réorganisation complète avec **un onglet principal par métier** (`STUDIO_METIERS`) : « Game Elevate Designer », « Community Manager », « QA / Recetteur », « Développeur Backend », « Narrative Designer », complétés par les onglets transverses « Studio & Roster » et « Journal de Forge », remplaçant l'ancienne liste hétérogène d'applications.
- **Illustrations des Doctrines Féodales & Grimoire des Prompts IA (83 Prompts) (`views/poster.php`, `views/partials/grimoire_prompts_data.php`, `views/partials/grimoire_album.php`) :** Intégration officielle des 3 illustrations HD pour les doctrines martiales des clans (Oda, Takeda, Tokugawa) sur « Mon Affiche Féodale » avec bannière narrative et extension du Grimoire des Prompts à 83 entrées.
- **Téléversement d'Avatar Daimyō & Prévisualisation Client (`views/poster.php`, `api/profile.php`, `core/HonorEngine.php`) :** Zone d'avatar personnalisée sur l'affiche du joueur avec prévisualisation instantanée `FileReader` et upload asynchrone sécurisé (2 Mo max, JPEG/PNG/WEBP).
- **Réorganisation Ergonomique de la Navbar & Sous-barre Dédiée (`views/partials/header.php`) :** Extraction des Kobans, Sceau, Studio Dev, Admin et Ambiance sur une sous-barre dédiée sous la navbar principale, alignée à droite avec badges compacts Font Awesome et infobulles descriptives Tabler.io.
- **Outil d'Insertion Automatique des Nouveautés dans la Missive (`views/newsletter_compose.php`) :** Tiroir latéral Offcanvas permettant de rechercher, cocher et injecter les dernières nouveautés du jeu directement dans l'éditeur WYSIWYG Quill.
- **Refonte de la Missive Impériale en Split-Screen & Template Clair Parchemin :** Migration de la modale de newsletter vers une page d'administration dédiée en 2 colonnes avec éditeur Quill, prévisualisation isolée en direct, nouveau template HTML responsive clair parchemin (Or impérial et Carmin) avec sceau du chrysanthème.
- **Harmonisation Visuelle Font Awesome 6 & Layout Fluide Pleine Largeur :** Remplacement des emojis Unicode par Font Awesome 6 en hébergement local et conteneurs fluides calibrés `container-fluid px-3 px-lg-4`.

### Sécurité & Correctifs (Fixed)
- **Contrôles d'Accès Stricts par Métier (`index.php`, `core/DevTeamEngine.php`, `api/dev_team.php`) :** Masquage DOM strict des onglets selon les rôles du joueur (Game Elevate Designer réservé exclusivement à `game_designer`), interception des paramètres d'URL `?metier=...` et `?tab=...` avec redirection automatique et blocage API HTTP 403.
- **Correctif Split-Screen 2 Colonnes (`views/newsletter_compose.php`) :** Résolution de balise orpheline et application des classes `col-12 col-lg-6 col-xl-6` pour maintenir les deux volets côte à côte sur écran desktop.
- **Correctif Sandbox Iframe (`views/newsletter_compose.php`) :** Autorisation des scripts dans l'aperçu (`sandbox="allow-same-origin allow-scripts"`) et assainissement regex strict du code HTML injecté.
- **Correctif Warning PHP `views/poster.php` :** Sécurisation de l'accès à la clé `'is_protected'` via `!empty($profile['is_protected'])`.
- **Studio Dev & Écosystème des Oasis :** Correction d'une erreur fatale PHP (`Call to undefined method OasisEngine::getOasesCoordinatesMap()`) lors du chargement des modules d'arpentage et de gestion des oasis dans `views/dev_team.php`.

## [1.16.0] - 2026-09-30
### Architecture & Game Elevate Designer
- **Migration des modules de paramétrage vers Studio Dev :** Retrait complet de `views/admin.php` des 3 modules de configuration du monde féodal :
  * « Constante et équilibrage de vitesse du jeu » (chantiers, production, marche des armées, famine, quêtes héroniques)
  * « Arpenté rapporteur du shogunat et expansion des provinces » (arpenteur procédural et répartition cartographique des 9 catégories de tuiles)
  * « Écosystème des oasis » (générateur de densité cible, régénération continue après capture, garnisons de bêtes hostiles et tableau paginé)
- **Rattachement exclusif au métier « Game Elevate Designer » :** Ces 3 modules forment désormais 3 onglets dédiés (`game_speeds`, `world_expansion`, `oases_ecosystem`) sous « Studio Dev », strictement réservés au métier technique `game_designer` renommé avec honneur en « Game Elevate Designer ».
- **Règle stricte pour l'Administrateur (Principe de moindre privilège) :** L'administrateur suprême **NE VOIT PAS** ces onglets par défaut s'il ne s'est pas lui-même assigné le métier « Game Elevate Designer ». Le seul onglet systématiquement visible pour l'administrateur dans Studio Dev est « Studio » (notamment « Roster et Métiers »). Dès qu'il s'attribue ce métier, les 3 onglets lui deviennent instantanément visibles et accessibles.
- **Sécurisation triple couche (Zéro DOM, Routing & API) :**
  * Aucun code HTML émis dans le DOM pour les onglets non autorisés.
  * Interception et redirection automatique dès le routeur `index.php` en cas de forçage manuel d'URL.
  * Blocage strict HTTP 403 sur les contrôleurs API `/api/dev_team.php` et `/api/admin.php` (`save_game_settings`, `generate_world`, `repopulate_oases`).
- **Gamification & Forge XP :** Récompenses d'XP Forge pour chaque action de game design (+30 XP pour l'équilibrage des constantes, +40 XP pour le déploiement d'un monde provincial, +35 XP pour le rééquilibrage de densité des oasis).

## [1.15.0] - 2026-09-30
### Sécurité & Contrôle d'Accès
- **Masquage DOM strict des onglets par métier :** Conditionnement PHP direct de l'affichage de chaque onglet du menu (`ul#dev-team-tabs`) et de son panneau (`div.tab-content`) selon les métiers et permissions effectives de l'utilisateur connecté. Aucun élément HTML n'est injecté dans le DOM pour les onglets non habilités (aucun simple masquage CSS `display: none`).
- **Contrôle d'accès strict côté serveur (Routeur) :** Interception directe dans `index.php` et `views/dev_team.php` de toute tentative de forçage direct via l'URL (`?page=dev_team&tab=...`), avec redirection automatique vers le premier onglet autorisé du membre et bandeau d'alerte contextuel.
- **Passe-droit global pour les Administrateurs :** Conservation de la visibilité intégrale et de l'accès inconditionnel aux 7 onglets du studio de développement pour les administrateurs globaux.
- **Sécurisation des points d'API REST :** Renforcement des contrôles de permissions sur les actions techniques sensibles (notamment le lancement du contrôle syntaxique automatisé).
- **Synchronisation d'URL réactive :** Gestion de l'historique de navigation du navigateur via `history.replaceState` lors du basculement d'onglet sans rechargement de page.

## [1.14.0] - 2026-09-30
### Ajouté
- **Nouveau Métier Dev Team « Community Manager » :** Intégration du métier (icône 📢, couleur teal, permissions RBAC `community.mailing`, `lore.publish`, `news.manage`), assignation multiple et synchronisation automatique en base.
- **Outil de Mailing List & Diffusion Communautaire :** Nouvel onglet dans Studio Dev avec 4 cartes KPI, liste paginée des joueurs, filtres multi-critères (opt-in, statut compte, clan, rôle) et toggle direct d'abonnement.
- **Expédition de Missives & Newsletters :** Modale de composition d'e-mails avec ciblage d'audience (`all_optin`, `all_active`, par clan, Dev Team, test personnel), template féodal HTML/texte et mentions de désinscription.
- **Exports CSV :** Exportation instantanée de la liste des abonnés avec encodage UTF-8 BOM pour compatibilité tableur (Excel / LibreOffice).
- **Consentement Éclairé RGPD :** Ajout d'une case à cocher explicite non cochée par défaut (« M'inscrire à la liste de diffusion / newsletter ») sur le formulaire d'inscription et sur l'assistant d'installation web, avec persistance dans la colonne `users.newsletter_optin`.

## [1.13.0] - 2026-09-30
### Ajouté
- **Contrôle Qualité & Recette (QA) :** Sous-onglet dédié « QA & Recette » dans Studio Dev avec KPIs de recette, suivi de statut en temps réel et badges interactifs.
- **Registre des Fonctionnalités (`fonctionnalités.md`) :** Standardisation Markdown stricte des nouvelles fonctionnalités (`À tester`, `Validée`, `Rejetée`), module de parsing et persistance `FeatureRegistry.php`.
- **Diagnostic Syntaxe Automatisé :** Moteur `QASyntaxChecker.php` et script CLI `scripts/check_syntax.php` exécutant une validation statique `php -l` sur l'ensemble des fichiers du projet (129+ fichiers vérifiés avec succès).
- **Gamification de la Recette :** Attribution d'XP Forge (+25 XP par recette de fonctionnalité, +15 XP par contrôle syntaxique) valorisant le rôle du QA Tester.
- **Règle de Clôture dans `.antigravityrules.md` :** Obligation stricte de consigner chaque nouvelle fonctionnalité dans `fonctionnalités.md` avec format Markdown unifié.

## [1.12.0] - 2026-09-30
### Ajouté
- **Attribution multiple de métiers Dev Team :** Modale de sélection multiple avec cases à cocher Tabler.io, sélection/désélection en 1 clic ("Tout cocher" / "Tout décocher").
- **Blocage proactif des doublons :** Détection automatique des rôles déjà possédés côté client (options grisées avec étiquette explicative "✓ Déjà assigné") et vérification d'intégrité stricte côté serveur avec requêtes préparées PDO.
- **Suppression réactive et badges visuels :** Bouton de suppression rapide intégré sur chaque badge de métier avec confirmation visuelle via la modale Tabler.io (zéro dialogue natif `confirm()`).
- **Mise à jour dynamique du DOM :** Suppression animée et injection de badges sans rechargement de page, avec mise à jour en direct de la grille des 8 métiers.
- **Points d'API REST étendus :** Prise en charge des tableaux de métiers (`role_ids[]`) dans l'action `assign_roles` de `/api/dev_team.php`.

## [1.11.0] - 2026-09-30
### Ajouté
- **Workflow d'inscription sécurisé :** Validation de mot de passe renforcée avec jauge dynamique 4 niveaux et 5 critères d'entropie.
- **Confirmation par e-mail :** Comptes créés inactifs (`is_active = 0`) avec jeton d'activation sécurisé SHA-256 valide 24h.
- **Service Mail & Transporteur SMTP :** Client SMTP natif RFC 5321 sans dépendances externes (STARTTLS, SSL, AUTH) et module d'administration Tabler avec chiffrement AES-256-CBC des identifiants.
