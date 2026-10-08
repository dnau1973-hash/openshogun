# 📋 Registre des Fonctionnalités & Recette QA — OpenShogun

### [2026-10-08] - map-smooth-parcel-modal-backup-service : Déplacement Fluide Carte 60fps, Modale des Parcelles Libres avec Niveaux Max, 1000 Ressources Initiales et Sauvegarde Autonome USB
- **Module :** `map-smooth-parcel-modal-backup-service`
- **Statut :** `À tester`
- **Description :**
  1. **Ressources Initiales par Défaut à 1000 (`core/WorldGenerator.php`, `core/BotEngine.php`, `core/Auth.php`) :**
     - Tout joueur (administrateur au reset, nouvel inscrit, bots) débute désormais avec 1000 unités de Bois de Cèdre, 1000 Pierre de Taille et 1000 Riz Impérial.
  2. **Suppression des Saccades sur la Carte des Provinces (`public/js/galaxy_map.js`) :**
     - Le Drag & Drop de la carte est désormais traité exclusivement en translation GPU matérielle (`translate3d`), sans requête ni ré-instanciation DOM intempestive en cours de geste. Le recadrage et la requête AJAX ne s'opèrent qu'au relâchement du pointeur.
  3. **Affichage des Particularités du Terroir & Niveaux Max au Clic d'une Parcelle Libre (`core/GalaxyEngine.php`, `views/map.php`) :**
     - Au clic sur un domaine inoccupé ou une parcelle libre, la modale affiche la liste des 9 parcelles uniques du fief avec leur niveau maximal (`max_level`) et met en valeur les filons d'or / jackpots régionaux (Niveau max 75 à 100).
  4. **Système Autonome de Sauvegarde / Réintégration Base de Données Séparées & Support USB (`core/DatabaseBackupService.php`, `scripts/backup_database.php`) :**
     - Export séparé de la Structure (DDL) et des Données (DML avec `SET FOREIGN_KEY_CHECKS=0`).
     - Sauvegarde dans `database/backups/` et duplication automatique sur support USB / disque externe connecté (`/media` ou `/mnt`, ou via paramètre `--usb`).
     - Script CLI dédié indépendant de l'administration (`php scripts/backup_database.php backup|list|restore`).
- **Fichiers modifiés :** `core/WorldGenerator.php`, `core/BotEngine.php`, `core/Auth.php`, `core/GalaxyEngine.php`, `public/js/galaxy_map.js`, `views/map.php`, `core/DatabaseBackupService.php`, `scripts/backup_database.php`, `CHANGELOG.md`, `fonctionnalités.md`
- **Vérification QA :**
  1. Glisser la carte des provinces (`?page=map`) à la souris ou au doigt : vérifier le déplacement instantané et fluide sans aucune saccade.
  2. Cliquer sur une parcelle libre ou neutre : constater la présence du tableau des potentiels verticaux des 9 parcelles uniques (niveaux max).
  3. Exécuter `php scripts/backup_database.php backup` : constater la création des 2 fichiers distincts dans `database/backups/` (structure et données).
  4. Exécuter `php scripts/backup_database.php list` : constater l'affichage des sauvegardes et le statut des clés USB.

---

### [2026-10-08] - urban-queue-partial-tenshu-city-link-zero-reset : Mutualisation du Chantier Urbain en Partial, Redirection Cité du Tenshu et Réinitialisation du Monde à Zéro
- **Module :** `urban-queue-partial-tenshu-city-link-zero-reset`
- **Statut :** `À tester`
- **Description :**
  1. **Composant Partiel Mutualisé `views/partials/urban_construction_queue.php` :**
     - Extraction du bloc de suivi des chantiers en cours hors de `views/city.php` vers un partial réutilisable.
     - Prise en charge universelle des chantiers d'infrastructures urbaines, des parcelles rurales (`rural_plot`) et des champs traditionnels (`field`).
     - Rendu unifié sur la vue Cité Castrale (`views/city.php`) et sur la vue Terroir Rural (`views/resources.php`).
     - Décomptes en direct interactifs, boutons d'annulation avec confirmation et badge de double file pour le Clan Oda.
  2. **Correction du Lien Tenshu / Donjon vers la Cité Castrale :**
     - Remplacement de la redirection vers `/?page=buildings` par `/?page=city` lors du clic sur le Tenshu / Donjon dans la carte panoramique du domaine rural (`views/resources.php` et `public/js/rural_domain_map.js`).
  3. **Réinitialisation Strictement à Zéro de l'Univers Féodal :**
     - Mise à jour de `WorldGenerator::resetUniverse()` pour purger l'ensemble des tables dynamiques, y compris `planet_rural_plots` et `craft_queue`.
     - Les ressources de départ de la capitale admin, des bots et des planètes neutres sont strictement initialisées à 0 (Bois = 0, Pierre = 0, Riz = 0).
     - Tous les bâtiments urbains et toutes les parcelles rurales (les 9 parcelles du Terroir ainsi que les champs) sont réinitialisés et générés avec un niveau initial de 0, 0 ouvriers affectés et une production horaire nulle.
- **Fichiers modifiés :** `views/partials/urban_construction_queue.php`, `views/city.php`, `views/resources.php`, `public/js/rural_domain_map.js`, `core/WorldGenerator.php`, `core/VillageGeneratorService.php`, `core/BotEngine.php`, `CHANGELOG.md`, `fonctionnalités.md`
- **Vérification QA :**
  1. Accéder à `/?page=city` et `/?page=resources` et lancer des chantiers : constater le rendu identique et temps réel du bloc de file de construction sur les deux pages.
  2. Sur la page `/?page=resources`, cliquer sur l'épingle / badge du Tenshu (Donjon) : vérifier la redirection immédiate vers `/?page=city`.
  3. Lancer une réinitialisation du monde via l'administration ou le script : vérifier que les ressources du joueur et des fiefs sont à 0 et que tous les bâtiments / parcelles débutent au niveau 0.

---

### [2026-10-08] - game-elevate-designer-building-derivation : Simulateur de Dérivation des Bâtiments & Contrôle Global des Courbes de Construction
- **Module :** `game-elevate-designer-building-derivation`
- **Statut :** `À tester`
- **Description :**
  1. **Nouveau Simulateur Studio Dev (`?page=dev_team&metier=game-elevate-designer&module=building-derivation`) :**
     - Atelier dédié au Game Elevate Designer permettant de simuler et calibrer les chantiers et les coûts de construction du niveau 1 au niveau 20+.
     - Calcul en direct des durées (en heures, minutes, secondes) et des ressources nécessaires (Bois, Pierre, Riz) pour chaque palier.
     - Graphique interactif SVG affichant simultanément la courbe exponentielle de durée et la courbe de coût avec tooltips dynamiques au survol.
     - Tableau analytique complet avec calculs de dérivation marginale ($\Delta$ coût en % et $\Delta$ durée en ratio de progression).
     - Préréglages rapides d'équilibrage : Standard Sengoku, Chantiers Éclair, Long Terme, Abondance Économique, Chantiers Monumentaux.
  2. **Coefficients d'Ajustement Dynamiques :**
     - **Multiplicateur de durée (`building_time_coeff`) :** Rallonge ou accélère la durée globale de tous les chantiers (0.1x à 5.0x).
     - **Pente temporelle ($g_t$, `building_time_growth`) :** Module l'exposant géométrique par niveau ($g_t^L$).
     - **Multiplicateur de coût (`building_cost_coeff`) :** Accentue ou allège la quantité de ressources requise pour bâtir (0.1x à 5.0x).
     - **Inflation des coûts ($g_c$, `building_cost_growth`) :** Module la croissance des coûts entre les niveaux inférieurs et supérieurs.
  3. **Application Immédiate au Moteur de Jeu Réel :**
     - `BuildingEngine::getUpgradeDetails` et `RuralPlotEngine::calculateUpgradeCost` / `calculateUpgradeDuration` intègrent directement ces paramètres depuis `game_settings`.
     - Toute modification enregistrée dans le simulateur impacte instantanément les coûts affichés sur la page Bâtiments (`?page=buildings`), la Cité Castrale (`?page=city`), le Terroir Rural (`?page=resources`) et les files de chantiers actives.
- **Fichiers modifiés :** `views/studio/game-elevate-designer/building-derivation.php`, `core/BuildingEngine.php`, `core/RuralPlotEngine.php`, `core/DevTeamEngine.php`, `core/GameConfig.php`, `api/dev_team.php`, `database/migrations/002_seed_game_data.sql`, `views/dev_team.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se connecter avec un compte Game Elevate Designer (ou Administrateur) et accéder à `/?page=dev_team&metier=game-elevate-designer`.
  2. Constater la présence du sous-module **« Dérivation des Bâtiments »** dans la barre de navigation des pills.
  3. Cliquer dessus : vérifier le chargement fluide du simulateur, du graphique SVG et du tableau analytique niveau par niveau (1 à 20).
  4. Modifier le curseur de durée ou de coût, ou cliquer sur un preset (ex. « Éclair » ou « Long Terme ») : constater la mise à jour immédiate à 60 fps des courbes, des KPIs et du tableau.
  5. Cliquer sur « Appliquer au Jeu Réel » : vérifier la notification de confirmation et le gain d'XP Forge (+35 XP).
  6. Naviguer vers `/?page=buildings` ou `/?page=city` et constater que les durées et coûts affichés pour les prochains niveaux reflètent fidèlement les nouveaux coefficients enregistrés.

---

### [2026-10-07] - ui-simplify-rural-badges-tenshu-link : Simplification minimaliste des badges du terroir (Logo + Niveaux) et liaison directe du Tenshu vers la cité
- **Module :** `ui-simplify-rural-badges-tenshu-link`
- **Statut :** `À tester`
- **Description :**
  1. **Simplification épurée des badges des parcelles rurales (`views/resources.php`) :**
     - Remplacement des anciens grands badges encombrants (> 175px) par des micro-pilules ultra-compactes et élégantes n'obstruant plus l'illustration panoramique 16:9.
     - Suppression de la barre de progression de chantier sur les tuiles, des cadences de production horaires et des timers encombrants.
     - Affichage strict du format épuré demandé : **Logo / Avatar rond + Niveau actuel / Niveau maximal** (`Niv. X / Y`).
     - Indicateur subtil de travaux en cours : animation de marteau ou sablier discret sans déformer la pilule.
  2. **Liaison directe du Tenshu vers la cité castrale (`views/resources.php`, `public/js/rural_domain_map.js`, `core/RuralPlotEngine.php`) :**
     - Le donjon seigneurial (Tenshu) sur la page ressources n'est plus une parcelle rurale évolutive : il fait désormais office de porte d'accès directe vers la cité castrale.
     - Le clic sur le badge du Tenshu redirige immédiatement vers `/?page=buildings` (sans ouvrir de modale d'amélioration rurale).
     - Badge spécifique Tenshu avec icône donjon, intitulé et flèche d'entrée (`Tenshu ->`).
     - Sécurité côté serveur dans `RuralPlotEngine::upgradePlot` interdisant toute amélioration rurale du Tenshu.
- **Fichiers modifiés :** `views/resources.php`, `public/js/rural_domain_map.js`, `core/RuralPlotEngine.php`, `fonctionnalités.md`, `pedagogie.md`, `CHANGELOG.md`, `public/changelog.html`
- **Vérification QA :**
  1. Se rendre sur la page Ressources (`?page=resources`).
  2. Constater la nouvelle apparence des 9 badges : micro-pilules arrondies et compactes avec uniquement le logo de la ressource et `Niv. X / Y`.
  3. Vérifier qu'aucune barre de progression ni texte de production ne pollue les badges de la carte.
  4. Cliquer sur le badge du Tenshu (Donjon seigneurial en haut) : vérifier qu'il redirige instantanément vers la cité castrale (`?page=buildings`).
  5. Cliquer sur les autres parcelles (ex. Forêt, Rizière) : la modale d'amélioration complète s'ouvre normalement.

---

### [2026-10-07] - queue-concurrency-clan-perks : Réactivation de la construction simultanée selon le clan féodal (Oda vs Takeda/Tokugawa) & Sceau Impérial
- **Module :** `queue-concurrency-clan-perks`
- **Statut :** `À tester`
- **Description :**
  1. **Réactivation du Double Développement Simultané (Clan Oda / `terran`) :**
     - Rétablissement de la capacité signature du Clan Oda : possibilité d'élever simultanément une parcelle rurale (Forêt, Carrière, Rizière, etc.) ET de bâtir/améliorer une infrastructure urbaine de la cité castrale (Tenshu, Caserne, Forge, Silos, etc.).
     - Un chantier urbain en cours ne bloque plus le lancement d'une amélioration rurale, et réciproquement un chantier rural n'occupe plus le créneau de construction urbain.
     - Avec le Sceau Impérial décrété : 2 chantiers ruraux ET 2 chantiers urbains peuvent progresser de front (jusqu'à 4 chantiers simultanés au total).
  2. **Contrôle de Concurrence Rigoureux pour les autres Clans (Clan Takeda / `vorash`, Clan Tokugawa / `aethelis`) :**
     - Respect de la doctrine féodale standard : 1 seul chantier actif au total sur l'ensemble du fief (qu'il soit rural ou urbain).
     - Si un chantier urbain est en cours, toute tentative de lancer un chantier rural est rejetée avec un message explicite rappelant le privilège unique du Clan Oda.
     - Si un chantier rural est en cours, toute tentative de bâtir un bâtiment urbain est bloquée de la même manière.
     - Avec le Sceau Impérial décrété : déblocage d'un second créneau de chantier (jusqu'à 2 chantiers simultanés toutes catégories confondues sur le domaine).
  3. **Harmonisation globale des contrôleurs et des vues :**
     - Classification stricte dans `BuildingEngine::startUpgrade` et `RuralPlotEngine::upgradePlot` des catégories de travaux (`rural_plot` groupé avec `field`, `building` isolé).
     - Correction dans `views/city.php`, `views/building.php` et `views/field.php` où les parcelles rurales de la nouvelle refonte étaient auparavant assimilées à tort à des bâtiments urbains (`else { $buildingsInQueue++; }`), ce qui verrouillait la cité.
     - Affichage d'un badge distinctif « Double Chantier (Clan Oda) » dans le panneau de carte et dans la colonne latérale des chantiers en cours sur la page Ressources lorsque le joueur appartient au Clan Oda.
  4. **Couverture de tests unitaires automatisés :**
     - Ajout de tests de concurrence dans `tests/test_village_generator_9plots.php` validant que le Clan Oda lance bien 1 urbain + 1 rural en simultané (Queue count = 2) et bloque le 2e rural sans Sceau, et que le Clan Takeda/Tokugawa se voit interdire le double développement sans Sceau.
  5. **Résolution du crash de transaction PDO (`There is no active transaction`) :**
     - Instanciation des moteurs annexes (`ImperialSealEngine`, `PlanetEngine`) en amont de `beginTransaction()` dans `RuralPlotEngine::upgradePlot`.
     - Garde-fou strict sur `ImperialSealEngine::ensureSchema()` et `PlanetEngine::ensureSchemaMigration()` évitant l'exécution de requêtes DDL (`CREATE TABLE`, `ALTER TABLE`) qui provoquent un commit implicite MySQL rompant la transaction.
     - Sécurisation de l'ensemble des appels `$this->db->commit()` et `$this->db->rollBack()` sous condition `$this->db->inTransaction()`.
- **Fichiers modifiés :** `core/BuildingEngine.php`, `core/RuralPlotEngine.php`, `core/ImperialSealEngine.php`, `core/PlanetEngine.php`, `views/city.php`, `views/building.php`, `views/field.php`, `views/resources.php`, `tests/test_village_generator_9plots.php`, `pedagogie.md`, `fonctionnalités.md`, `public/changelog.html`
- **Vérification QA :**
  1. Avec un compte du Clan Oda (faction `terran`) :
     - Se rendre sur la page Cité (`?page=buildings`) et lancer l'amélioration d'un bâtiment urbain (ex: Tenshu ou Forge).
     - Se rendre sur la page Ressources (`?page=resources`) : constater que les badges ruraux restent disponibles à l'amélioration (pas de blocage).
     - Lancer l'élévation d'une parcelle rurale (ex: Forêt) : le chantier démarre immédiatement avec succès.
     - Constater dans la colonne latérale « Chantiers en Cours » que les deux chantiers (1 urbain + 1 rural) s'égrènent simultanément.
     - Tenter de lancer une 2e parcelle rurale : vérifier que le jeu affiche le message de blocage invitant à activer le Sceau Impérial.
  2. Avec un compte non-Oda (Clan Takeda ou Tokugawa) :
     - Lancer un bâtiment urbain.
     - Tenter de lancer une parcelle rurale : vérifier que le jeu refuse l'élévation en indiquant qu'un chantier est déjà actif sur le fief et que seul le Clan Oda dispose du double développement de base.

---

### [2026-10-07] - refactor-rural-9plots-async-queue-fixed-panorama : Refonte globale du domaine rural (9 parcelles procédurales 20-100, panorama fixe 16:9 sans pan/zoom, file de construction asynchrone non-instantanée)
- **Module :** `refactor-rural-9plots-async-queue-fixed-panorama`
- **Statut :** `À tester`
- **Description :**
  1. **Suppression définitive des contrôles de Zoom et Déplacement (Pan/Zoom/Drag) :**
     - Retrait total des gestionnaires d'événements de drag-to-scroll, grab-and-pan, molette de zoom et boutons de contrôle (+, -, reset 100%, recentrage, plein écran).
     - Nettoyage des styles de curseurs (`grab`, `grabbing`).
     - Remplacement du viewport mobile par un conteneur responsive fixe verrouillé au ratio 16:9 (`aspect-ratio: 16 / 9; background-size: cover`), centré et stable sur tous les écrans.
     - Positionnement direct en coordonnées relatives fixes (`% left / top`) des 9 badges interactifs sans matrice de transformation variable (`transform: translate/scale`).
  2. **Modèle de données & 9 parcelles procédurales uniques :**
     - Structure de données des 9 types par fief : `tenshu`, `foret`, `carriere`, `fosse_argile`, `riziere`, `champ_soja`, `culture_the`, `sanctuaire_shinto`, `village`.
     - Potentiels verticaux procéduraux tirés entre le niveau 20 et 100 via `VillageGeneratorService`.
  3. **File de construction & Durées réelles non-instantanées (Niveaux 1 à 100) :**
     - Règle stricte d'élévation non-instantanée : enregistrement persistant dans `construction_queue` (`started_at`, `finishes_at`, `target_level`).
     - Déduction immédiate des ressources au lancement du chantier.
     - Verrouillage du niveau : le niveau en base de données reste inchangé pendant les travaux et n'est incrémenté côté serveur que lors de l'expiration de `finishes_at` via `PlanetEngine::processConstructionQueue`.
     - Échelonnage progressif des durées : de quelques minutes sur les paliers bas, plusieurs heures sur les paliers intermédiaires, jusqu'à plusieurs jours sur les très hauts niveaux (jusqu'à 100).
     - Support de l'annulation avec remboursement de 80% des ressources via `BuildingEngine::cancelUpgrade`.
  4. **Interface utilisateur dynamique (Tabler.io + Vanilla JS) :**
     - État visuel « En travaux » sur le badge de la parcelle ciblée : icône de marteau animée, halo doré pulsant, compte à rebours dynamique en temps réel (`JJ:HH:MM:SS` ou `HH:MM:SS`) et barre de progression fluide.
     - Verrouillage de la modale d'amélioration pendant la durée du chantier (affichage du compte à rebours et possibilité d'annuler le chantier).
     - Intégration des parcelles rurales dans le panneau « Chantiers en Cours » de la colonne latérale avec bouton d'annulation rapide.
- **Fichiers modifiés :** `views/resources.php`, `public/js/rural_domain_map.js`, `core/RuralPlotEngine.php`, `core/PlanetEngine.php`, `core/BuildingEngine.php`, `core/ImperialSealEngine.php`, `api/rural_plot.php`, `database/migrations/003_support_rural_plots_in_queue.sql`, `database/migrate_rural_plots.php`, `tests/test_village_generator_9plots.php`, `fonctionnalités.md`, `public/changelog.html`
- **Vérification QA :**
  1. Se rendre sur la page Ressources (`?page=resources`).
  2. Vérifier que la carte s'affiche dans un cadre 16:9 net et fixe, sans curseur grab, sans boutons de zoom et sans possibilité de glisser ou déplacer l'image à la souris.
  3. Vérifier les 9 badges des structures féodales avec leurs niveaux et potentiels (ex: Niv. 1 / 45).
  4. Cliquer sur une parcelle (ex. Forêt) : la modale s'ouvre affichant les coûts et la durée estimée.
  5. Cliquer sur « Lancer le chantier » : les ressources sont déduites, la page se recharge avec l'état « TRAVAUX » sur le badge de la forêt, le compte à rebours dynamique s'égrène seconde par seconde et la barre de progression avance.
  6. Vérifier dans la colonne de droite « Chantiers en Cours » que le chantier apparaît avec son compte à rebours et sa croix d'annulation.
  7. Cliquer à nouveau sur le badge en travaux : la modale affiche l'état en cours et le bouton « Annuler le chantier (Remboursement 80%) ».
  8. Cliquer sur « Annuler » : vérifier que 80% des ressources investies sont bien restituées et que la parcelle redevient disponible.

---
- **Module :** `refactor-compact-hud-unified-header`
- **Statut :** `À tester`
- **Description :**
  1. **Unification et compacité de l'en-tête de jeu (`views/partials/header.php`) :**
     - Remplacement des 8 grands blocs de cartes par un ruban horizontal unifié extra-compact (hauteur globale contenue < 44px, `card card-sm shadow-sm py-1`).
     - Intégration harmonieuse des 6 ressources matérielles féodales (Bois, Pierre, Riz, Farine de Riz, Saké Impérial, Poutres Maîtresses) et des statistiques démographiques / spirituelles (Sérénité Shintō, Population, Contentement populaire).
     - Micro-jauges minimalistes (3px de hauteur) discrètement placées sous chaque valeur pour matérialiser les niveaux de stockage sans alourdir l'interface.
  2. **Respect rigoureux de la direction artistique et des teintes du site :**
     - Conformité avec le thème Tabler clair d'OpenShogun : carte blanche (`bg-white`), bordures douces (`border-secondary-subtle`), typographie sombre et contrastée (`text-dark font-monospace`).
     - Préservation des codes couleurs féodaux identitaires du jeu : Bois (`text-success`), Pierre (`text-primary`), Riz (`text-warning`), Farine (`text-secondary`), Saké (`text-purple`), Poutres (`text-orange`), Sérénité (`text-teal`), Population (`text-dark`).
     - Badge de Contentement adaptatif (`badge bg-*-lt border-0`).
  3. **Élimination complète des doublons (`views/resources.php`) :**
     - Suppression du bloc intermédiaire de 4 cartes (Sérénité, Population, Main-d'œuvre, Contentement) dans la vue Terroir, évitant tout affichage en double avec le header.
     - Gain d'espace vertical immédiat (> 150px) permettant à la carte interactive des 40 parcelles d'être visible sans défilement excessif.
  4. **Compatibilité JS temps réel et infobulles :**
     - Conservation de l'ensemble des IDs DOM (`res-val-metal`, `bar-metal`, `res-val-crystal`, etc.) et attributs `data-current`, `data-max`, `data-prod` exploités par `public/js/app.js` et le rafraîchissement périodique.
     - Infobulles natives Bootstrap (`data-bs-toggle="tooltip"`) et popover de décomposition détaillée (`data-bs-toggle="popover"`).
- **Fichiers modifiés :** `views/partials/header.php`, `views/resources.php`, `CHANGELOG.md`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter et naviguer sur n'importe quelle page du jeu (ex: `?page=overview`, `?page=resources`, `?page=buildings`).
  2. Vérifier que la barre de ressources du header s'affiche sous forme d'un ruban horizontal compact d'une hauteur inférieure à 44px avec fond blanc et bordure discrète.
  3. Vérifier que les ressources (Bois, Pierre, Riz, Farine, Saké, Poutres) s'incrémentent correctement en direct sans erreur dans la console JavaScript.
  4. Vérifier que la Sérénité et la Population apparaissent dans le HUD du header avec leurs micro-jauges et pourcentages.
  5. Se rendre sur la page Terroir (`?page=resources`) et vérifier que les 4 cartes en doublon n'apparaissent plus : la carte 40 parcelles et la grille tactique débutent directement sous la barre d'outils.
  6. Survoler et cliquer sur le badge de Contentement dans le header pour vérifier l'affichage du popover explicatif.

---

### [2026-10-06] - refactor-terroir-remove-redundant-resource-row : Allègement de l'en-tête du Terroir Féodal et suppression de la première rangée de ressources redondante
- **Module :** `refactor-terroir-remove-redundant-resource-row`
- **Statut :** `À tester`
- **Description :**
  1. **Suppression de la première rangée de compteurs de ressources (`views/resources.php`) :**
     - Retrait de la Rangée 1 contenant les 6 cartes de stocks et productions horaires (Bois, Pierre, Argile, Riz, Thé, Soja), devenue inutile et redondante avec la barre de ressources globale du header et l'affichage individuel des 40 parcelles.
     - Conservation exclusive de la barre des 4 indicateurs clés essentiels (Sérénité Shintō, Population globale, Mobilisation de la main-d'œuvre, Contentement féodal) pour un en-tête aéré et centré sur la gestion stratégique.
     - Nettoyage des styles CSS `.kpi-resource-*` devenus obsolètes.
- **Fichiers modifiés :** `views/resources.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se rendre sur la page du Terroir Féodal (`?page=resources`).
  2. Vérifier que la rangée des 6 cartes de ressources (Bois, Pierre, Argile, Riz, Thé, Soja) n'apparaît plus au-dessus des indicateurs clés.
  3. Vérifier que les 4 cartes d'indicateurs de pilotage (Sérénité, Population, Main-d'œuvre, Contentement) s'affichent proprement et conservent toute leur interactivité (popover sur le contentement).
  4. Vérifier que la carte illustrée des 40 parcelles et la grille tactique fonctionnent parfaitement sans perturbation de layout.

---

### [2026-10-06] - fix-installer-missing-newsletter-optin-column : Correction de l'erreur SQLSTATE[42S22] colonne 'newsletter_optin' inconnue lors de l'installation
- **Module :** `fix-installer-missing-newsletter-optin-column`
- **Statut :** `À tester`
- **Description :**
  1. **Intégration des colonnes complètes de `users` dans les schémas de référence :**
     - Ajout des colonnes `is_active`, `email_verified_at`, `activation_token`, `activation_token_expires_at` et `newsletter_optin` (avec leurs index respectifs) dans le DDL de la table `users` des fichiers `database/migrations/001_baseline_schema.sql`, `database/schema.sql` et `database/schema_complete.sql`.
  2. **Auto-guérison et repli résilient dans l'assistant d'installation (`core/InstallEngine.php`) :**
     - Vérification dynamique et création automatique (`ALTER TABLE`) des colonnes `newsletter_optin`, `avatar` et `bio` avant la mise à jour de l'administrateur.
     - Requête `UPDATE` de secours sans `newsletter_optin` en cas d'exception sur les structures de données historiques.
- **Fichiers modifiés :** `database/migrations/001_baseline_schema.sql`, `database/schema.sql`, `database/schema_complete.sql`, `core/InstallEngine.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Lancer l'installation via `install.php` jusqu'à son terme : vérifier que l'Étape 8 (création / mise à jour du Shogun Administrateur) passe avec succès.
  2. Vérifier que la table `users` possède bien la colonne `newsletter_optin` et que le choix fait sur l'installateur y est correctement persisté.
  3. Vérifier que la connexion à l'espace de jeu avec le compte administrateur fonctionne immédiatement sans erreur SQL.

---

### [2026-10-06] - fix-installer-missing-alliances-table : Correction de l'erreur SQLSTATE[42S02] table 'alliances' inexistante lors de l'installation
- **Module :** `fix-installer-missing-alliances-table`
- **Statut :** `À tester`
- **Description :**
  1. **Intégration de la table `alliances` dans les schémas de référence consolidés :**
     - Ajout de la définition DDL de la table `alliances` (`id`, `name`, `tag`, `leader_id`, `description`, `created_at` avec clés uniques et index) dans la migration de base `database/migrations/001_baseline_schema.sql`, ainsi que dans `database/schema.sql` et `database/schema_complete.sql`.
     - Résolution de la dépendance de clé étrangère requise par `alliance_invitations` (`fk_inv_alliance`).
     - Porte le schéma consolidé à 43 tables complètes du jeu.
  2. **Sécurisation défensive de `WorldGenerator::resetUniverse` (`core/WorldGenerator.php`) :**
     - Encadrement des requêtes `TRUNCATE TABLE` dans un bloc `try / catch (PDOException $e)` avec journalisation d'avertissement, garantissant que l'absence ou la suppression future d'une table dynamique ne bloque pas la réinitialisation de l'univers ni le processus d'installation.
- **Fichiers modifiés :** `database/migrations/001_baseline_schema.sql`, `database/schema.sql`, `database/schema_complete.sql`, `core/WorldGenerator.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Lancer l'installation complète via `install.php` (ou CLI `scripts/migrate.php`) sur une base vierge : vérifier qu'aucune exception SQLSTATE[42S02] relative à `alliances` n'est levée.
  2. Vérifier que la table `alliances` est bien créée dans la base avec ses index et clés uniques (`SHOW CREATE TABLE alliances;`).
  3. Vérifier que l'Étape 7 (`WorldGenerator::resetUniverse`) s'exécute avec succès et initialise le compte administrateur et les fiefs initiaux.

---

### [2026-10-05] - refactor-devops-database-consolidation : Refonte DevOps & Consolidation Idempotente de la Base de Données
- **Module :** `refactor-devops-database-consolidation`
- **Statut :** `À tester`
- **Description :**
  1. **Consolidation de la Baseline DDL & Seeds (`database/migrations/`) :**
     - Fusion et unification intégrale des 42 tables du jeu dans un schéma de référence unique `database/migrations/001_baseline_schema.sql` (encadrement strict de désactivation des foreign keys pendant l'import, standardisation `CURRENT_TIMESTAMP`, UTF-8 mb4, suppression des numérotations statiques `AUTO_INCREMENT`).
     - Création du fichier de graines de référence immuables `database/migrations/002_seed_game_data.sql` (unités, technologies, engins de siège, châteaux authentiques du Japon, catégories de forum et paramètres par défaut avec clauses idempotentes `INSERT ... ON DUPLICATE KEY UPDATE`).
     - Synchronisation des schémas historiques `database/schema.sql` et `database/schema_complete.sql` sur la baseline consolidée.
  2. **Moteur Idempotent de Migrations (`core/MigrationEngine.php` & `scripts/migrate.php`) :**
     - Mise en place de la table de suivi `schema_migrations` (`version`, `applied_at`, `execution_time_ms`, `checksum` SHA-256).
     - Script CLI `php scripts/migrate.php` supportant les modes exécution automatique et statut (`--status`).
     - Intégration transparente dans l'assistant web `core/InstallEngine.php` pour un déploiement 100% automatisé sans patchs manuels.
  3. **Architecture Docker & 12-Factor App :**
     - Création du modèle d'environnement `.env.example` et support natif des variables d'environnement dans `core/Database.php` (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`).
     - Création de `Dockerfile` (PHP 8.2 Apache + extensions requises) et `docker-compose.yml` (MariaDB 10.11 avec healthcheck, initialisation automatique `/docker-entrypoint-initdb.d/` et volumes persistants).
     - Documentation standardisée complète dans `README.md`.
- **Fichiers modifiés :** `database/migrations/001_baseline_schema.sql`, `database/migrations/002_seed_game_data.sql`, `core/MigrationEngine.php`, `scripts/migrate.php`, `core/Database.php`, `core/InstallEngine.php`, `Dockerfile`, `docker-compose.yml`, `.env.example`, `database/schema.sql`, `database/schema_complete.sql`, `README.md`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Lancer `php scripts/migrate.php --status` : vérifier que le statut de chaque migration est tracé avec succès.
  2. Lancer `php scripts/migrate.php` plusieurs fois de suite : vérifier la parfaite idempotence (0 erreur, message d'état à jour).
  3. Tester `php scripts/check_syntax.php` : valider le feu vert technique (100% de fichiers valides).
  4. Tester un déploiement Docker `docker compose up -d` depuis une arborescence vierge.

---

### [2026-10-05] - fix-auth-missing-avatar-column : Résolution de l'erreur SQLSTATE[42S22] colonne 'avatar' inconnue dans Auth::getCurrentUser
- **Module :** `fix-auth-missing-avatar-column`
- **Statut :** `À tester`
- **Description :**
  1. **Résolution du crash au chargement de l'en-tête (`Auth::getCurrentUser()`) :**
     - Ajout de la colonne `avatar VARCHAR(255) NULL AFTER bio` dans les schémas DDL de création de la table `users` (`database/schema_complete.sql` et `database/schema.sql`).
     - Sécurisation résiliente de `Auth::getCurrentUser()` dans `core/Auth.php` : mise en place d'un bloc `try / catch (PDOException)` avec requête de secours sans les colonnes `avatar` / `bio` (assignées à `null` par défaut), garantissant le fonctionnement continu de l'interface même sur une base n'ayant pas encore exécuté la migration.
     - Automatisation de la migration des colonnes `bio` et `avatar` directement dans l'étape d'installation de `core/InstallEngine.php`.
- **Fichiers modifiés :** `core/Auth.php`, `core/InstallEngine.php`, `database/schema.sql`, `database/schema_complete.sql`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se connecter avec un compte utilisateur et charger `index.php` : vérifier que la page d'accueil s'affiche sans erreur PDO.
  2. Vérifier que la table `users` dispose des colonnes `bio` et `avatar`.
  3. Vérifier que le profil utilisateur et l'avatar personnalisé fonctionnent sans régression.

---

### [2026-10-05] - fix-installer-game-settings-resilience : Résolution de l'erreur SQLSTATE[42S02] table 'game_settings' inexistante lors de l'installation
- **Module :** `fix-installer-game-settings-resilience`
- **Statut :** `À tester`
- **Description :**
  1. **Résolution du blocage d'installation sur `game_settings` :**
     - Ajout de la définition complète de la table `game_settings` dans le schéma de référence `database/schema.sql` (avec clause `DROP TABLE IF EXISTS` et DDL `CREATE TABLE IF NOT EXISTS`).
     - Normalisation de `current_timestamp()` vers `CURRENT_TIMESTAMP` dans l'ensemble des 25 occurrences de `database/schema_complete.sql` pour assurer une compatibilité SQL stricte universelle (MySQL 5.7, 8.0, 8.4, MariaDB 10.x/11.x).
     - Filet de sécurité résilient dans l'Étape 6 de `InstallEngine::runInstallation` : exécution explicite et inconditionnelle d'un `CREATE TABLE IF NOT EXISTS game_settings` immédiatement avant l'insertion des clés de configuration, rendant tout échec d'insertion par table absente impossible.
  2. **Fiabilisation de l'analyseur SQL (`InstallEngine::executeSqlFile`) :**
     - Prise en charge et filtrage rigoureux des blocs de commentaires multilignes (`/* ... */`) et des commentaires `#` / `--`.
     - Levée d'exception descriptive explicite si une requête `CREATE TABLE` échoue, interdisant le masquage silencieux d'erreurs DDL critiques.
  3. **Injection de dépendance PDO & Synchronisation Singleton :**
     - Ajout de `Database::setConnection(?PDO $pdo)` pour réassigner dynamiquement la connexion active du singleton lors de l'installation.
     - Refactorisation du constructeur `WorldGenerator::__construct(?PDO $db = null)` acceptant l'injection de l'instance PDO courante afin d'éviter tout conflit de connexion non synchronisée avec le fichier `config/database.php`.
- **Fichiers modifiés :** `core/InstallEngine.php`, `core/WorldGenerator.php`, `core/Database.php`, `database/schema.sql`, `database/schema_complete.sql`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Lancer l'assistant d'installation web `install.php` sur une base vierge ou réinitialisée.
  2. Vérifier que les 7 étapes d'installation se déroulent avec succès sans déclencher d'exception PDO ni d'erreur de table manquante.
  3. Vérifier que la table `game_settings` est créée et contient l'intégralité des 12 paramètres requis (`game_title`, `game_speed`, `bots_enabled`, `oasis_density_percent`, etc.).
  4. Vérifier la génération du château de départ et du compte administrateur.

---

### [2026-10-02] - rural-terroir-viewport-clamping-fix : Verrouillage Pan/Zoom Clamping (minZoom & Bounding Box) & Résolution Fuite JS
- **Module :** `rural-terroir-viewport-clamping-fix`
- **Statut :** `À tester`
- **Description :**
  1. **Résolution de la Fuite de Code JavaScript au-dessus de l'Image :**
     - Correction des guillemets orphelins dans les attributs des 40 pins interactifs : échappement strict `htmlspecialchars($tooltipTitle, ENT_QUOTES, 'UTF-8')` sur `title` et `data-bs-title`. Les classes CSS internes (`text-warning`, `text-muted`) ne provoquent plus de fermeture prématurée de l'attribut HTML.
     - Découplage et externalisation de l'intégralité de la logique front-end dans un asset dédié `/public/js/terroir_map.js` (validé sans erreur via `node --check`).
     - Injection propre et sécurisée des stocks du joueur via `window.TERROIR_CONFIG` sans script inline orphelin.
  2. **Verrouillage du Zoom & Déplacement (Pan/Zoom Clamping Strict) :**
     - **Contrainte 1 — Limite minimale de dézoom (`minZoom` / Fit-to-screen) :** Calcul dynamique systématique `minScale = Math.max(containerWidth / stageWidth, containerHeight / stageHeight)`. L'illustration 16:9 recouvre toujours 100% de la zone visible du viewport, interdisant toute bordure vide ou fond noir.
     - **Contrainte 2 — Verrouillage des bords au glissement (Clamp Pan / Bounding Box) :** Bornage strict des coordonnées de translation :
       * `minX = containerWidth - (stageWidth * currentZoom)`, `maxX = 0`
       * `minY = containerHeight - (stageHeight * currentZoom)`, `maxY = 0`
       * `x = Math.min(Math.max(x, minX), maxX)` et `y = Math.min(Math.max(y, minY), maxY)`
     - Interdiction formelle de tout déplacement hors de la boîte englobante lors du glissement à la souris et au tactile.
     - Préservation du point d'ancrage sous le curseur lors du zoom molette / boutons (`zoomAtCursor`), recalcul dynamique sur `resize` et transitions plein écran (`fullscreenchange`).
- **Fichiers modifiés :** `public/js/terroir_map.js`, `views/resources.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Charger la page `/?page=resources` : vérifier l'absence totale de code JavaScript en texte brut au-dessus de l'image de fond ou dans les en-têtes.
  2. Survoler les pins des 40 parcelles : vérifier que les infobulles s'affichent correctement et qu'aucun attribut HTML (`onclick`, `data-`) ne déborde en texte sur l'écran.
  3. Dézoomer au maximum (molette arrière ou bouton `-`) : vérifier que le zoom se bloque dès que l'image atteint les bords du conteneur (aucun fond noir visible).
  4. Faire glisser la carte dans les 4 directions (haut, bas, gauche, droite) : vérifier le blocage net aux 4 bordures sans aucun interstice ni fond noir exposé.
  5. Tester en redimensionnant la fenêtre et en mode Plein Écran (⛶) : vérifier que le clamping s'ajuste immédiatement.

---

### [2026-10-02] - rural-terroir-40-illustrated-map : Carte Illustrée des 40 Parcelles Féodales (Panorama 16:9 Ukiyo-e & Viewport Grab-and-Pan)
- **Module :** `rural-terroir-40-illustrated-map`
- **Statut :** `À tester`
- **Description :**
  1. **Nouvelle Illustration Panoramique Originale Haute Définition 16:9 (`public/assets/terroir_panoramic_16_9.jpg`) :**
     - Création originale complète (format widescreen 16:9, 1376×768 px) sans réutilisation directe ni altération de l'image précédente.
     - Signature graphique : Peinture numérique semi-réaliste et inspiration estampe japonaise ukiyo-e (traits fins, encrage précis, textures d'aquarelle et aplats texturés, lumière dorée d'aurore filtrant à travers une brume matinale légère).
     - Vue plongeante isométrique (bird's-eye view) stratégique articulant 8 biomes bien distincts reliés par des sentiers de terre et un cours d'eau :
       * ⛰️ **Carrière de Pierre (Nord / Massif rocheux)** : 5 plateformes de granit étagées avec blocs taillés et échafaudages en bois.
       * 🌲 **Forêt d'Exploitation (Nord-Est)** : 5 clairières de coupe au milieu de grands cèdres et cyprès du Japon.
       * 🏺 **Berges d'Argile (Est / Cours d'eau)** : 5 fosses d'extraction alluviale et berges argileuses le long de la rivière.
       * 🌾 **Rizières en Terrasses (Centre-Sud)** : 5 bassins inondés en gradins avec reflets d'eau et diguettes.
       * 🍵 **Collines de Thé (Sud-Est)** : 5 paliers ondulés formés de buissons de théiers bien taillés.
       * 🫘 **Champs de Soja (Sud-Ouest)** : 5 parcelles agricoles plates avec rangs de culture et claies de séchage.
       * ⛩️ **Sanctuaires Shinto (Ouest / Colline sacrée)** : 5 clairières surélevées ornées de torii vermillon, lanternes de pierre et cordes shimenawa.
       * 🛖 **Cœur du Village (Centre)** : 5 emplacements spacieux réservés aux habitations traditionnelles (minka) et roue à aubes.
     - Coordonnées spatiales relatives précises (% left, % top) calculées et déclarées dans `TerroirEngine::SLOT_MAP_COORDS`.
  2. **Moteur de Navigation Viewport 16:9 Fluide (Grab-and-Pan & Zoom) :**
     - Système de glissement ergonomique direct au curseur et tactile (drag-to-scroll / grab-and-pan en JavaScript Vanilla) avec gestion des états `cursor: grab` et `cursor: grabbing`.
     - Zoom progressif à la molette centré sur le curseur avec bornes de clampage adaptées au format 16:9 (0.5x à 2.2x).
     - Barre de commandes flottante : Zoom avant (+), Zoom arrière (-), Ajustement 100%, Recentrage rapide, et Mode Plein Écran HTML5.
     - Déplacement cinématique caméra avec lissage CSS (`cubic-bezier`) lors du clic sur l'un des filtres de catégories.
     - Bascule fluide 1-clic entre la « 🗺️ Carte Illustrée (40) » et la « 📊 Grille Tactique (40) ».
  3. **Superposition des 40 Slots Interactifs & Modale d'Élévation :**
     - Tokens circulaires style jeu de stratégie avec liseré thématique lumineux, pastille de niveau (`N.1`), compteur de travailleurs requis et affichage des cadences.
     - Détection visuelle des chantiers actifs avec halo ambré pulsant et marteau animé.
     - Infobulles enrichies au survol (nom, rôle de l'ouvrier, production horaire, invite d'action).
     - Modale d'élévation interactive (`#parcelUpgradeModal`) présentant vignette, comparatif Niveau N ➔ N+1, tags de coûts multi-ressources colorés (vert si disponible, rouge si manquant) et bouton d'action AJAX direct.
  4. **Traçabilité & Grimoire des Prompts :**
     - Ajout de la fiche descriptive `pano_terroir_16_9` dans `views/partials/grimoire_prompts_data.php`.
- **Fichiers modifiés :** `public/assets/terroir_panoramic_16_9.jpg`, `core/TerroirEngine.php`, `views/partials/grimoire_prompts_data.php`, `views/resources.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se rendre sur la page des ressources (`/?page=resources`) : vérifier l'affichage par défaut de la carte panoramique des 40 parcelles.
  2. Tester le Grab-and-Pan : cliquer et faire glisser la souris dans le viewport, vérifier l'absence d'à-coups et la fluidité du déplacement.
  3. Tester le zoom : molette de la souris, boutons `+`, `-`, `100%` et `Recentrer`.
  4. Tester le filtrage des catégories (ex: `🌲 Bois (5)`, `🌾 Riz (5)`, `🛖 Habitations (5)`) : vérifier que la caméra effectue un travelling fluide vers la zone ciblée et que les pins non sélectionnés sont estompés.
  5. Cliquer sur un pin de parcelle : vérifier l'ouverture de la modale `#parcelUpgradeModal` avec les métriques et les coûts.
  6. Cliquer sur « 📊 Grille Tactique (40) » : vérifier la bascule instantanée vers la vue des cartes récapitulatives.
  7. Cliquer sur le badge « Généré par IA » : vérifier l'ouverture de la modale de transparence avec le prompt du modèle Google Gemini Imagen 3.

---

### [2026-10-02] - rural-terroir-40-grid : Grille des 40 Parcelles & Simplification des Habitations
- **Module :** `rural-terroir-40-grid`
- **Statut :** `À tester`
- **Description :**
  1. **Restructuration de la Grille : Passage à 40 Parcelles (8 Catégories × 5 Parcelles) :**
     - Remplacement de la navigation par carte interactive panoramique par une vue complète en grille de tuiles et parcelles (CSS Grid / Flexbox Tabler.io).
     - Intégration stricte de 5 parcelles/slots dédiés pour chacune des 8 composantes féodales :
       * 🌲 **Bois** (5 parcelles de sylviculture)
       * ⛰️ **Pierre** (5 carrières d'extraction de granit)
       * 🏺 **Argile** (5 gisements alluviaux et poteries)
       * 🌾 **Riz** (5 terrasses inondées de riz Koku)
       * 🍵 **Thé** (5 plantations de thé vert & matcha)
       * 🫘 **Soja** (5 champs de soja pour tofu & farine)
       * ⛩️ **Sérénité / Spiritualité** (5 sanctuaires shinto)
       * 🛖 **Village / Habitations** (5 parcelles d'habitations civiles)
     - Barre de filtrage interactif par catégorie en haut de grille (`Tout afficher (40)`, `Bois`, `Pierre`, `Argile`, `Riz`, `Thé`, `Soja`, `Sérénité`, `Habitations`) pour isoler instantanément un groupe de parcelles sans rechargement.
     - Chaque tuile affiche : illustration thématique en filigrane, pastille de niveau, type de bâtiment, ouvriers requis, métrique de production/capacité (+XX / h, +XX Sérénité, ou +XX hab.), tags de coût multi-ressources, durée et bouton d'action/amélioration rapide 1-clic branché sur `api/terroir.php`.
  2. **Simplification Drastique du Système d'Habitation :**
     - Abandon des sous-types complexes (Minka, Nagaya, etc.) au profit d'un modèle unique et prévisible : « Habitation » (Niveaux 1 à N).
     - Formule de capacité synchronisée : $\text{Capacité Totale} = 75 \text{ (base villageoise)} + (\sum_{i=1}^5 \text{Niveau}(H_i) \times 5 \text{ villageois})$.
     - Évolution dynamique : chaque niveau d'habitation supplémentaire augmente directement la capacité d'accueil de +5 villageois dans la base de données.
     - 5 habitations de niveau 1 de départ procurent exactement $75 + (5 \times 5) = 100$ places, s'alignant sur la population initiale de 100 habitants.
  3. **Tableau de Bord Supérieur Enrichi (Header de la Vue `views/resources.php`) :**
     - 6 cartes de ressources complètes avec stocks résiduels et cadences horaires dynamiques : Bois, Pierre, Argile, Riz, Thé, Soja.
     - 4 cartes d'indicateurs majeurs :
       * ⛩️ **Sérénité Shinto** : jauge et balance énergétique spirituelle issue des 5 sanctuaires.
       * 👥 **Population Globale** : habitants actuels / capacité totale d'accueil issue des 5 habitations.
       * ⛏️ **Main-d'œuvre** : ouvriers en poste vs requis vs inactifs disponibles.
       * 😊 **Contentement du Peuple** : jauge de satisfaction (0-100%) intégrant vivres, spiritualité et le bonus net de +15% apporté par le Saké.
- **Fichiers modifiés :** `core/TerroirEngine.php`, `views/resources.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se rendre sur la page des ressources du domaine rural (`/?page=resources`).
  2. Contrôler les 10 cartes d'indicateurs dans l'en-tête (6 ressources + Sérénité, Population, Main-d'œuvre, Contentement).
  3. Vérifier que la grille présente 8 sections bien ordonnées pour un total de 40 tuiles de parcelles (5 slots par catégorie).
  4. Tester les filtres de catégories (`🌲 Bois`, `🛖 Habitations`, etc.) : vérifier que les tuiles se masquent et s'affichent instantanément sans erreur JS.
  5. Vérifier la capacité des habitations : au niveau 1 pour les 5 habitations, la capacité totale affichée est de 100 (75 + 25).
  6. Déclencher l'amélioration d'une parcelle (champ de ressource, sanctuaire ou habitation) : vérifier la déduction des ressources et le rafraîchissement des valeurs.

---

### [2026-10-02] - grimoire-prompts-traceability : Grimoire des Prompts, Traçabilité IA & Harmonisation des Modales
- **Module :** `grimoire-prompts-traceability`
- **Statut :** `À tester`
- **Description :**
  1. **Enrichissement du Grimoire des Prompts (`views/partials/grimoire_prompts_data.php`) :**
     - Intégration complète de 11 nouvelles fiches descriptives détaillées (portant le grimoire à 91 prompts référencés) :
       * Décors thématiques de terroirs ruraux : `shogun_rural_terroir_bg.jpg` (Panorama interactif d'ensemble), `terroir_wood.jpg` (Forêt & Bûcheronnage), `terroir_stone.jpg` (Montagne & Granit), `terroir_clay.jpg` (Argile alluviale & Fours Noborigama), `terroir_rice.jpg` (Terrasses rizicoles inondées), `terroir_tea.jpg` (Coteaux de thé & Matcha), `terroir_soybean.jpg` (Champs de soja & Tofu), `terroir_village.jpg` (Village central, Minka & Sakagura).
       * Bannières de clans féodaux : `clan_oda_war_council.jpg`, `clan_takeda_cavalry_charge.jpg`, `clan_tokugawa_covert_scout.jpg`.
     - Respect strict de la structure documentaire : titre, sous-titre, catégorie, fichier d'asset, format, résolution (1920×1080), modèle IA (`Google Gemini Imagen 3`), date (`Octobre 2026`), prompt source anglais et traduction française avec notes d'ambiance.
  2. **Refonte & Harmonisation du Badge de Traçabilité IA (`core/AiPromptHelper.php`, `public/css/ai_prompt_modal.css`) :**
     - Remplacement de l'icône « ? » par l'icône Font Awesome `fa-wand-magic-sparkles` avec infobulle Tabler.io native (`title="Généré par IA • Google Gemini Imagen 3 • Cliquer pour voir le prompt source"`).
     - Support du mode capsule/pill (`ai-prompt-badge-pill`) avec libellé textuel « Généré par IA », lueur ambrée et effet d'échelle au survol.
     - Injection universelle des métadonnées `data-ai-model`, `data-ai-date`, `data-ai-resolution`, `data-ai-category`, `data-ai-title`, `data-ai-img`, `data-ai-prompt` et `data-ai-translation`.
     - Intégration directe du badge sur la carte interactive du terroir rural (`views/resources.php`) et sur les scènes des 7 zones de ressources (`views/view_resource.php`).
  3. **Modale Universelle Haute Définition & Métadonnées (`views/partials/ai_prompt_modal.php`, `public/js/ai_prompt_modal.js`) :**
     - Affichage de l'image haute définition en grand format avec bouton d'ouverture plein écran (`#aiModalFullImgBtn`).
     - Grille 3 colonnes de métadonnées visuelles : Modèle IA (`Google Gemini Imagen 3`), Date de génération (`Octobre 2026`) et Résolution/Format (`1920×1080 HD 16:9`).
     - Blocs de code pour le prompt source en anglais avec bouton de copie en 1 clic dans le presse-papiers et traduction française commentée.
     - Prise en charge du clic sur l'image elle-même (`.ai-image-clickable` ou dans `.ai-image-container`) pour déclencher la modale.
     - Fermeture fluide via bouton, overlay assombri et touche Échap avec gestion des tooltips Tabler.
- **Fichiers modifiés :** `views/partials/grimoire_prompts_data.php`, `core/AiPromptHelper.php`, `views/partials/ai_prompt_modal.php`, `public/js/ai_prompt_modal.js`, `public/css/ai_prompt_modal.css`, `views/resources.php`, `views/view_resource.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se rendre sur la carte interactive du Terroir Rural (`/?page=resources`) : observer le badge capsule « Généré par IA » en haut à droite du décor panoramique.
  2. Survoler le badge : vérifier l'infobulle Tooltip indiquant « Généré par IA • Google Gemini Imagen 3 • Cliquer pour voir le prompt source ».
  3. Cliquer sur le badge : vérifier l'ouverture instantanée de la modale avec l'image HD, la barre de 3 métadonnées (Modèle, Date, Résolution), le prompt anglais et la traduction française.
  4. Tester le bouton « Copier » du prompt anglais : vérifier le feedback visuel « Copié ! » et le collage effectif dans le presse-papiers.
  5. Naviguer vers une vue de ressource dédiée (ex. `/?page=view_resource&type=wood` ou `type=village`) : vérifier la présence du badge IA sur la scène thématique et son bon fonctionnement au clic.
  6. Vérifier la page de l'affiche féodale (`/?page=poster`) et les fiches de bâtiments/champs (`/?page=building`, `/?page=field`) : s'assurer que les badges de transparence existants affichent désormais l'icône `fa-wand-magic-sparkles` et ouvrent la modale enrichie.

---

### [2026-10-02] - rural-terroir-interactive : Carte Interactive du Terroir Rural & Vues Dédiées 5 Slots
- **Module :** `rural-terroir-interactive`
- **Statut :** `À tester`
- **Description :**
  1. **Déploiement de l'Illustration Panoramique Maîtresse :**
     - Récupération de l'image de référence haute résolution (`1696x2528 px`) depuis `tmp/` et déploiement dans les assets publics sous `public/assets/shogun_rural_terroir_bg.jpg` (avec sauvegarde du fond historique sous `shogun_rural_terroir_bg_legacy.jpg`).
     - Génération d'assets thématiques dédiés haute définition pour les 7 zones du fief (`terroir_wood.jpg`, `terroir_stone.jpg`, `terroir_clay.jpg`, `terroir_rice.jpg`, `terroir_tea.jpg`, `terroir_soybean.jpg`, `terroir_village.jpg`) dans `public/assets/resources/`.
  2. **Carte Interactive du Terroir Rural (`views/resources.php`) :**
     - Intégration de la carte panoramique avec ratio d'aspect strict `1696 / 2528` et positionnement en pourcentages CSS de badges interactifs enrichis (pins/hotspots) avec icônes Font Awesome, balises lumineuses pulsantes (`zone-badge-beacon`), tooltips Tabler.io natifs, affichage dynamique de la cadence horaire ou du statut démographique, et redirection au clic :
       * ⛰️ **Montagne** (`left: 58%, top: 25%`) : Extraction de pierre de taille (`?page=view_resource&type=stone`).
       * 🌲 **Forêt** (`left: 21%, top: 38%`) : Bois de cèdre et sylviculture (`?page=view_resource&type=wood`).
       * 🏺 **Argile** (`left: 81%, top: 51%`) : Gisement alluvial et cuisson de tuiles Kawara (`?page=view_resource&type=clay`).
       * 🌾 **Rizières** (`left: 52%, top: 62%`) : Terrasses inondées de riz impérial Koku (`?page=view_resource&type=rice`).
       * 🍵 **Thé** (`left: 20%, top: 54%`) : Champs étagés de thé vert et matcha (`?page=view_resource&type=tea`).
       * 🫘 **Soja** (`left: 19%, top: 72%`) : Légumineuses, farine et pâte miso (`?page=view_resource&type=soybean`).
       * ⛩️ **Village central** (`left: 53%, top: 90%`) : Logements, sanctuaires shinto et meuneries (`?page=view_resource&type=village`).
       * 🏯 **Tenshu** (`left: 50%, top: 34%`) : Donjon central et cité castrale (`?page=city`).
     - Bouton bascule ergonomique permettant de permuter à tout moment entre la « Carte Interactive du Terroir » et la « Vue 18 Parcelles Travian ».
  3. **Architecture des Vues de Gestion par Ressource (5 Slots) (`views/view_resource.php`, `core/TerroirEngine.php`) :**
     - Routage modulaire dédié `?page=view_resource&type=wood|stone|clay|rice|tea|soybean|village` déclaré dans `index.php`.
     - Scène thématique haute définition (format 16/9) avec 5 emplacements interactifs positionnés précisément sur les repères visuels du décor.
     - Grille de 5 cartes Tabler.io détaillant pour chaque slot : niveau, main-d'œuvre/villageois affectés (avec type de métier spécialisé), cadence horaire, coût d'élévation multi-ressources et bouton d'action asynchrone branché sur `api/terroir.php`.
  4. **Vue Dédiée au Village Central (`views/view_resource.php?type=village`, `views/view_village.php`) :**
     - En-tête enrichi avec 3 widgets Tabler.io de suivi : Population totale & Capacité libre d'accueil, Affectation de la main-d'œuvre (ouvriers requis vs affectés vs inactifs), et Jauge de Contentement féodale avec bonus asymétrique de Saké (+15%).
     - 5 emplacements réservés aux infrastructures rurales : Logements traditionnels (Minka), Maisons communes d'artisans (Nagaya), Sanctuaire Shintō & Torii, Moulin à eau fluvial (Suisha), Brasserie de Riz (Sakagura).
- **Fichiers modifiés / créés :** `views/resources.php`, `views/view_resource.php`, `views/view_village.php`, `core/TerroirEngine.php`, `api/terroir.php`, `database/migrate_terroir_slots.php`, `index.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter et accéder à la page des ressources (`/?page=resources`).
  2. Vérifier l'affichage de la carte panoramique féodale avec ses 7 badges interactifs positionnés précisément sur les zones.
  3. Survoler les badges : observer l'animation de halo, le zoom progressif et l'infobulle Tooltip Tabler affichant la cadence.
  4. Cliquer sur chaque badge (Montagne, Forêt, Argile, Rizières, Thé, Soja, Village) : vérifier la redirection fluide vers la vue dédiée 5 slots correspondante.
  5. Sur chaque vue de ressource, vérifier les 5 slots positionnés sur le décor et les 5 cartes d'action en contrebas (avec niveau, ouvriers, cadence horaire et bouton d'élévation).
  6. Tester l'élévation d'un slot de ressource et vérifier la déduction des ressources et la mise à jour immédiate.
  7. Sur la vue Village (`?page=view_resource&type=village`), vérifier la présence des 3 widgets Tabler (Démographie, Main-d'œuvre, Contentement) et des 5 édifices villageois.

---

### [2026-10-02] - admin/js-syntax-error : Correction de la Balise Script & Modale Koban
- **Module :** `admin/js-syntax-error`
- **Statut :** `À tester`
- **Description :**
  - **Cause racine de l'erreur JavaScript console :** Lors d'un commit précédent ajoutant la modale d'octroi de Koban (`#modalAdminGiveKoban`), le bloc HTML de la modale avait été inséré en fin de fichier à l'intérieur de la balise `<script>` sans balise fermante `</script>`. Le parseur JavaScript du navigateur levait immédiatement `Uncaught SyntaxError: Unexpected token '<'`, empêchant l'évaluation de l'ensemble du JavaScript de la page d'administration (`switchAdminTab`, `renderAnalyticsCharts`, paginations, soumissions de formulaires, etc.).
  - **Correctif :** 
    1. Déplacement de la modale HTML `#modalAdminGiveKoban` dans la section des modales (au-dessus du bloc `<script>`).
    2. Fermeture rigoureuse de la balise `<script>` avec `</script>` à la fin de la fonction `toggleModerator()`.
    3. Validation de la syntaxe JavaScript via analyseur AST (zéro erreur).
- **Fichiers modifiés :** `views/admin.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Ouvrir la console développeur (F12) et charger la page d'administration (`/?page=admin`).
  2. Vérifier l'absence totale de l'erreur `Uncaught SyntaxError: Unexpected token '<'`.
  3. Vérifier que la navigation par onglets (`switchAdminTab`), les graphiques statistiques et le bouton d'octroi de Koban (`openAdminGiveKobanModal`) fonctionnent immédiatement sans incident.

---

### [2026-10-02] - admin/analytics-engine : Nettoyage Header & Moteur de Graphiques Résilient
- **Module :** `admin/analytics-engine`
- **Statut :** `À tester`
- **Description :**
  1. **Nettoyage de la Barre de Navigation (`views/partials/header.php`) :**
     - Retrait définitif du bouton/badge « Statistiques » (`?page=admin&tab=dashboard`) dans la deuxième sous-barre du header afin de préserver la sobriété visuelle et de centraliser l'accès aux métriques exclusivement dans l'espace d'Administration (`views/admin.php`).
     - Restauration de l'état actif propre du bouton d'Administration générale (`fa-gear`).
  2. **Diagnostic & Résolution du Non-Affichage des Graphiques (`views/admin.php`) :**
     - **Cause racine 1 (Collision asynchrone) :** Détection d'un double appel concurrent (`switchAdminTab` à 60ms et `DOMContentLoaded` à 80ms) entraînant l'appel de `updateOptions()` sur une instance ApexCharts en cours d'initialisation asynchrone (`render()` Promise non résolue), provoquant une exception bloquante dans la console. Résolu via un déboucleur unique `requestRenderAnalyticsCharts()` annulant tout timer antérieur.
     - **Cause racine 2 (Dimensions nulles 0px x 0px) :** Résolution des erreurs de rendu SVG lors de l'initialisation sur conteneur en cours de reflow ou onglet masqué via détection de visibilité (`offsetParent`), vérification de `clientWidth > 0` et repli sur `requestAnimationFrame`.
     - **Cause racine 3 (Division par zéro sur séries vides) :** Normalisation stricte de `$cleanTimeline`, `$cleanTopPages`, `$cleanHourly` garantissant des valeurs `(int)` et des bornes minimales d'axes (`forceNiceScale: true`, `max: Math.max(10, ...)`), empêchant les plantages d'échelle lorsque toutes les valeurs sont à 0.
     - **Moteur de Secours Canvas HD Autonome :** Si la bibliothèque externe est indisponible (offline, bloqueur de script ou échec CDN), bascule automatique transparente sur 3 moteurs de dessin Canvas 2D natifs (`renderCanvasTrend`, `renderCanvasTopPages`, `renderCanvasHourly`) garantissant qu'aucun graphique ne reste vide ou invisible.
- **Fichiers modifiés :** `views/partials/header.php`, `views/admin.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Vérifier la deuxième barre de navigation du header : le bouton vert sarcelle `fa-chart-line` a bien disparu ; seul le bouton d'engrenage `fa-gear` est présent pour l'administration.
  2. Ouvrir la console du navigateur sur la page d'administration (`/?page=admin&tab=dashboard`) : vérifier l'absence totale d'exception JS bloquante (`TypeError`, `Uncaught in promise`).
  3. Vérifier que les 3 graphiques (Évolution temporelle, Top 8 des modules, Heures de pointe) s'affichent instantanément et distinctement, même si la base ne contient encore que peu ou pas de logs.
  4. Basculer vers un autre onglet (ex. « Joueurs », « Paramètres ») puis revenir sur « Dashboard » : vérifier que les graphiques se redessinent immédiatement et proprement sans déformation.
  5. Couper la connexion réseau ou bloquer le CDN `jsdelivr.net` : vérifier que le moteur Canvas HD prend immédiatement le relais et dessine les courbes et barres sans écran blanc.

---

### [2026-10-02] - telemetry/analytics-suite : Suite Télémétrique & Débogage du Graphique d'Activité
- **Module :** `telemetry/analytics-suite`
- **Statut :** `À tester`
- **Description :**
  1. **Débogage du Graphique d'Activité Dashboard (`views/admin.php`) :**
     - Résolution de l'incident de canvas vierge (`rect.width <= 0`) causé par le rendu asynchrone lors du masquage/affichage d'onglets.
     - Correction de la requête SQL d'agrégation chronologique sur 30 jours via une série continue sans saut (`ActivityTracker::getTimelineTrend`), alimentant les jours à 0 inscription ou activité pour préserver la courbe.
     - Implémentation d'ApexCharts via CDN avec `ResizeObserver` natif et mécanisme de repli transparent sur un canvas 2D stylisé si la bibliothèque externe est indisponible.
  2. **Moteur Télémétrique & Journal d'Activité (`core/ActivityTracker.php`, `database/migrate_activity_logs.sql`) :**
     - Table `activity_logs` indexée (`created_at`, `user_id`, `page_slug`, `ip_hash`) avec horodatage, typage d'appareil (ordinateur, mobile, tablette, bot) et anonymisation RGPD par hachage SHA-256 avec salage mensuel.
     - Helper non-bloquant `ActivityTracker::logView()` branché dans `index.php` pour tracer les visiteurs publics (Atelier pédagogique, Changelog, Authentification) et les joueurs authentifiés avant le rendu de chaque vue.
  3. **Module de Statistiques Avancées du Shōgunat (`views/admin.php`) :**
     - 4 KPI Cards réactives avec calcul de variation % par rapport à la période précédente : Total Pages Vues, Daimyōs Actifs Uniques (DAU/MAU), Taux Humains vs Bots, Durée moyenne de session.
     - Graphique 1 (Spline Area Chart ApexCharts) : Évolution croisée Vues de Pages vs Daimyōs Actifs vs Inscriptions sur 1, 7, 30 ou 60 jours.
     - Graphique 2 (Horizontal Bar Chart ApexCharts) : Top 8 des modules et pages les plus consultés avec volume de vues.
     - Graphique 3 (Bar Chart ApexCharts) : Distribution et heures d'affluence de 0h à 23h.
     - Tableau interactif dynamique : Classement des utilisateurs les plus actifs avec filtre de recherche instantané en JavaScript Vanilla (`#activeUsersSearchInput`).
     - Boutons de filtrage temporel rapide : Aujourd'hui (`today`), 7 Jours (`7d`), 30 Jours (`30d`), Tout (`all`).
- **Fichiers modifiés :** `core/ActivityTracker.php`, `database/migrate_activity_logs.sql`, `database/migrate_activity_logs.php`, `index.php`, `views/admin.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Accéder au Dashboard Administrateur (`/?page=admin&tab=dashboard`) : vérifier que le graphique d'activité principal s'affiche immédiatement sans page blanche ni erreur console JS.
  2. Vérifier la présence et les valeurs des 4 KPI Cards (Pages Vues, Daimyōs Actifs, Taux Humains/Bots, Durée de session).
  3. Tester les filtres temporels (Aujourd'hui, 7 jours, 30 jours, Tout) : vérifier la mise à jour des métriques selon la période choisie.
  4. Vérifier l'affichage du Graphique 2 (Top 8 Modules) et du Graphique 3 (Heures de pointe).
  5. Saisir un nom de joueur ou un clan dans le champ de recherche du tableau des Daimyōs les plus actifs : vérifier le filtrage instantané sans rechargement de page.
  6. Naviguer vers un autre onglet puis revenir sur « Dashboard » : vérifier que les graphiques se redimensionnent correctement sans distorsion.

---

### [2026-10-02] - navigation/sub-navbar : Bouton Statistiques & Métriques dans la Sous-Barre Header
- **Module :** `navigation/sub-navbar`
- **Statut :** `À tester`
- **Description :** 
  - Ajout d'un bouton d'action compact « Statistiques d'utilisation & Métriques du Shōgunat » (`?page=admin&tab=dashboard`) dans la deuxième barre de navigation (sous-barre d'outils rapides alignée à droite dans `views/partials/header.php`).
  - Positionnement : inséré immédiatement avant le bouton d'Administration Générale (`views/partials/header.php`).
  - Format visuel strict : icône seule Font Awesome `<i class="fa-solid fa-chart-line fs-3"></i>` sans texte visible, format compact badge Tabler `bg-teal-lt text-teal`.
  - Infobulle native Tabler / Bootstrap : attributs `data-bs-toggle="tooltip"`, `data-bs-placement="bottom"` et `title="Statistiques d'utilisation &amp; Métriques du Shōgunat"`, pris en charge par l'initialisation automatique dans `views/partials/footer.php`.
  - Condition d'affichage : restreint aux profils administrateurs (`$auth->isAdmin()`).
- **Fichiers modifiés :** `views/partials/header.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec un compte non-administrateur : vérifier que le bouton Statistiques (`fa-chart-line`) n'est pas affiché dans la sous-barre.
  2. Se connecter avec un compte Administrateur : vérifier l'apparition du badge vert/bleu sarcelle `bg-teal-lt` immédiatement à gauche du bouton d'engrenage (Administration).
  3. Survoler le bouton avec la souris : vérifier l'affichage de l'infobulle Tabler « Statistiques d'utilisation & Métriques du Shōgunat ».
  4. Cliquer sur le bouton : vérifier la redirection fluide vers `?page=admin&tab=dashboard` et la mise en surbrillance de l'état actif (bordure sarcelle).

---

### [2026-10-02] - pedagogy/backend-school : École du Backend & 3 Cours Illustrés (POO/Singleton, PDO/SQLi, Routage GET/POST)
- **Module :** `pedagogy/backend-school`
- **Statut :** `À tester`
- **Description :** 
  1. **Création de l'arborescence et des 3 vues de cours dédiées (`views/atelier-pedagogique/backend/`) :**
     - `php-poo-singleton.php` (Niveau 1) : La Classe et l'Objet expliqués via l'Atelier de Forge de sabres / le moule à gâteaux, le patron Singleton via le Facteur Impérial Unique (`MailService`), et les sessions PHP (`$_SESSION`) via le Sceau de Cire tamponné sur la main du joueur.
     - `pdo-sql-injection.php` (Niveau 2) : La base de données expliquée comme le Coffre-Fort des trésors du Shogun, l'injection SQL comme le Parchemin Piégé d'un bandit ninja, et les requêtes préparées (`prepare` / `execute`) comme la Boîte aux Lettres Magique avec fente blindée qui dissocie l'ordre de la donnée.
     - `routing-get-post.php` (Niveau 3) : L'adresse URL et le Routeur (`index.php`) comparés aux Panneaux directionnels dans les ruelles de Kyoto, le messager `$_GET` à la Carte Postale transparente lisible par tous dans la rue, et `$_POST` à la Lettre Secrète Scellée portée discrètement par un ninja.
  2. **Composants d'Apprentissage & Interactivité :**
     - Mise en forme épurée Tabler.io avec la typographie féodale Dela Gothic One pour les grands titres de leçons.
     - Encarts culturels et techniques « Le savais-tu ? » (Little Bobby Tables, flèche `->`, limite de taille d'URL).
     - Mini-quiz interactifs en JavaScript Vanilla (2 questions par cours) avec feedback visuel instantané sans rechargement de page, messages explicatifs et célébration du score.
     - Fil d'Ariane et boutons de navigation fluide (« Cours Précédent », « Sommaire de l'Atelier », « Cours Suivant »).
  3. **Routage & Intégration Sommaire :**
     - Prise en charge des routes publiques et privées dans `index.php` (gestion des URLs `/atelier-pedagogique/backend/<lesson>` et des paramètres `?page=pedagogy&lesson=<lesson>`).
     - Ajout de la Section 5 « L'École du Backend : Les Cours Illustrés » dans le sommaire principal (`views/partials/admin_pedagogy.php`) avec 3 cartes de synthèse colorées et bouton d'ancrage rapide dans le Hero banner.
- **Fichiers modifiés :** `views/atelier-pedagogique/backend/php-poo-singleton.php`, `views/atelier-pedagogique/backend/pdo-sql-injection.php`, `views/atelier-pedagogique/backend/routing-get-post.php`, `views/pedagogy.php`, `views/partials/admin_pedagogy.php`, `index.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. **Accès et Sommaire :**
     - Aller sur `/?page=pedagogy` (en visiteur déconnecté puis connecté) : vérifier la présence de la Section 5 « L'École du Backend » et du bouton raccourci n°5 dans l'en-tête Hero.
     - Vérifier la bonne disposition des 3 cartes avec badges de niveau, temps de lecture et résumés des métaphores.
  2. **Leçon 1 (POO & Singleton) :**
     - Cliquer sur le bouton « Suivre le Cours 01 » ou ouvrir `/?page=pedagogy&lesson=php-poo-singleton`.
     - Vérifier l'affichage complet du cours, du bloc de code Katana et de l'explication du Facteur Unique `MailService::getInstance()`.
     - Tester le quiz interactif : cliquer sur « Valider » sans sélectionner d'option (message d'avertissement), sélectionner de mauvaises réponses (retours rouges d'explication), sélectionner les bonnes réponses (retours verts et trophée 2/2).
  3. **Leçon 2 (PDO & Injections SQL) :**
     - Cliquer sur « Cours Suivant » : vérifier l'ouverture de `/?page=pedagogy&lesson=pdo-sql-injection`.
     - Vérifier les encarts Coffre-Fort, Parchemin Piégé, Boîte Blindée et l'histoire de Little Bobby Tables.
     - Tester et valider le mini-quiz sécurité (2/2).
  4. **Leçon 3 (Routage, $_GET & $_POST) :**
     - Cliquer sur « Cours Suivant » : vérifier l'ouverture de `/?page=pedagogy&lesson=routing-get-post`.
     - Vérifier le tableau comparatif « Carte Postale ($_GET) vs Missive Ninja ($_POST) ».
     - Tester et valider le mini-quiz réseau (2/2).
     - Cliquer sur « Retour à l'Atelier Pédagogique » et vérifier le retour au sommaire.
  5. **Contrôle d'accès et réécriture :**
     - Tester l'URL propre `/atelier-pedagogique/backend/php-poo-singleton` ou `/?page=atelier&lesson=pdo-sql-injection` : vérifier qu'elle affiche bien le cours sans redirection d'erreur.

---

### [2026-10-02] - admin/updates & combat-engine : Débogage Mises à Jour & Dégâts de Siège aux Bâtiments
- **Module :** `admin/updates & combat-engine`
- **Statut :** `À tester`
- **Description :** 
  1. **Débogage du bouton « Mise à jour » dans l'Administration (`views/partials/admin_updates.php`, `views/admin.php`) :**
     - Sécurisation intégrale de `checkGitHubUpdates()` et `installGitHubUpdate()` contre les exceptions JavaScript (`TypeError: Cannot read properties of undefined` sur `data.local.short_sha` ou `data.local.target_branch`).
     - Vérification défensive systématique de la présence des éléments du DOM (`bannerBox`, `bannerTitle`, `bannerDesc`, `bannerIcon`, `navBadge`, etc.).
     - Remplacement des fonctions bloquantes natives `confirm()` et `alert()` au profit d'une modale interactive Tabler.io (`#modal-confirm-update-deploy`) conforme à `.antigravityrules.md`.
     - Gestion robuste des promesses réseau et codes HTTP avec retours utilisateurs propres via toasts Tabler.io.
     - Ajout de l'identifiant `#admin-update-nav-badge` sur le bouton de navigation pour mise à jour réactive.
  2. **Dégâts de Siège & Dégradation des Bâtiments (`core/CombatEngine.php`) :**
     - Prise en compte active des unités de siège (`terran_cruiser`, `terran_dreadnought`, `vorash_leviathan`, `aethelis_prism`, `aethelis_titan`) dans les assauts victorieux.
     - Réduction prioritaire sur le niveau de la muraille du village défenseur (250 PV structurels par niveau).
     - En cas d'effondrement ou de brèche totale (niveau 0), report proportionnel des dégâts de siège résiduels sur les bâtiments du fief selon l'ordre de priorité tactique (Tenshu, Dojo/Caserne, Entrepôt, Grenier Kura, Forge, etc.).
     - Prise en compte de la destruction totale d'édifice (niveau 0 / ruine à reconstruire) et mise à jour effective en BDD dans `planet_buildings`.
     - Intégration des détails de dégradation structurelle dans `$reportData['infrastructure_damage']` et mise à jour du titre du rapport de combat.
  3. **Simulateur de Combat Studio Dev (`views/studio/game-elevate-designer/combat-simulator.php`) :**
     - Ajout de la configuration des 8 édifices castraux du village cible (Tenshu, Dojo, Entrepôt, Grenier, Forge, Tour, Atelier, Maçonnerie).
     - Encart de référence technique exposant clairement les constantes d'équilibrage de siège (PV muraille, PV bâtiments, renfort Maçonnerie, dégâts et multiplicateurs des béliers et catapultes).
     - Section dédiée dans le rapport instantané : « Dégâts aux Infrastructures & État des Bâtiments du Fief » avec transition de la muraille, tableau complet des bâtiments endommagés/détruits, jauges et statuts visuels.
     - Intégration des données d'infrastructures dans l'export Markdown et la sauvegarde d'historique de simulation (+15 XP Forge).
- **Fichiers modifiés :** `views/partials/admin_updates.php`, `views/admin.php`, `core/CombatEngine.php`, `views/studio/game-elevate-designer/combat-simulator.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. **Administration / Mises à jour :**
     - Aller sur `/admin` ou `/?page=admin&tab=updates`.
     - Cliquer sur le bouton « Contrôler les Mises à Jour » ou « Vérifier Mises à Jour » du Dashboard : vérifier qu'aucune exception `TypeError` n'apparaît dans la console et qu'un toast de statut s'affiche.
     - Tester le bouton « Télécharger & Déployer la Mise à Jour » : vérifier l'ouverture de la modale Tabler.io au lieu du `confirm()` natif du navigateur.
  2. **Simulateur de Combat :**
     - Aller sur `/?page=dev_team&metier=game-elevate-designer&module=combat-simulator`.
     - Vérifier la présence du volet « Infrastructures du Fief (Siège) » avec les 8 édifices et la carte de référence des constantes d'équilibrage.
     - Tester les 3 préréglages (Mur 0, Mur 8, Mur 20) : vérifier le bon remplissage des niveaux de bâtiments.
     - Lancer une simulation avec béliers et catapultes contre un village ouvert ou en forçant une brèche : vérifier l'apparition de la section « Dégâts aux Infrastructures » avec le détail des niveaux perdus et le badge rouge « Détruit (Niv 0) » le cas échéant.
     - Tester la copie Markdown et vérifier la présence du tableau des bâtiments dans le texte copié.

- **Module :** `studio/game-elevate-designer`
- **Statut :** `À tester`
- **Description :** 
  1. **Restauration Intégrale de la Vue Simulateur (`views/studio/game-elevate-designer/combat-simulator.php`) :** Remplacement du fichier vide par l'atelier martial complet en 2 colonnes comparatives (Attaquant vs Défenseur avec curseur de muraille de 0 à 20, calcul en temps réel des bonus défensifs et PV de structure, préréglages 1 clic, moteur d'affrontement multi-rounds, combat log dépliable et export Markdown).
  2. **Déverrouillage d'Accès Administrateur (`core/DevTeamEngine.php`, `api/dev_team.php`) :** Autorisation des profils administrateurs dans `canAccessMetier()` et `canAccessTab()` pour leur permettre d'accéder au module du Game Elevate Designer et d'enregistrer des simulations (`save_combat_test`) sans blocage de sécurité ou redirection intempestive.
  3. **Initialisation JavaScript Fiable :** Exécution réactive de `updateWallMetrics()` contrôlant l'état du DOM (`document.readyState === 'complete'`) pour assurer un calcul immédiat des jauges même lors des navigations asynchrones.
- **Fichiers modifiés :** `views/studio/game-elevate-designer/combat-simulator.php`, `core/DevTeamEngine.php`, `api/dev_team.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec un compte Administrateur ou détenteur du rôle Game Elevate Designer et ouvrir `/?page=dev_team&metier=game-elevate-designer&module=combat-simulator` (ou `/?page=dev_team&tab=combat_simulator`).
  2. Vérifier que la page se charge immédiatement avec les deux colonnes (Attaquant à gauche, Village défendu à droite avec le slider de muraille) et le grand bouton rouge d'action.
  3. Tester le slider de muraille (0 à 20) : vérifier la mise à jour instantanée des badges de bonus (+% et PV).
  4. Cliquer sur les 3 préréglages (« Raid Village Ouvert », « Bourg Fortifié », « Forteresse Impériale ») et vérifier la réactivité des jauges.
  5. Cliquer sur « Lancer la Simulation Tactique Instantanée » : vérifier l'apparition du rapport complet, des jauges d'attrition, du bilan régimentaire et du journal déroulant.
  6. Cliquer sur « Sauvegarder ce Test (+15 XP Forge) » et « Copier le Rapport Markdown » : vérifier l'absence d'erreur 403 et le message de succès.

---

### [2026-10-02] - studio/architecture & studio/game-elevate-designer : Restructuration Métiers de Studio Dev & Simulateur de Combat avec Muraille
- **Module :** `studio/architecture & studio/game-elevate-designer`
- **Statut :** `À tester`
- **Description :** 
  1. **Refactorisation de l'Architecture de Studio Dev (`views/dev_team.php`, `views/studio/`, `core/DevTeamEngine.php`, `index.php`) :**
     - Réorganisation complète de la navigation avec **UN ONGLET PRINCIPAL PAR MÉTIER** (`STUDIO_METIERS`) : « Game Elevate Designer » (réservé exclusivement au rôle `game_designer`), « Community Manager », « QA / Recetteur », « Développeur Backend », « Narrative Designer », complétés par « Studio & Roster » (gestion des membres) et « Journal de Forge ».
     - Règle de visibilité et sécurité stricte : aucun onglet métier n'est affiché dans le DOM si l'utilisateur ne possède pas le métier requis (l'administrateur conserve l'accès à son onglet de gestion Roster & Métiers, et doit s'assigner le rôle pour voir les modules métier).
     - Sous-navigation par module : sous chaque onglet métier, des pilules de sous-navigation (`nav-pills` Tabler.io) permettent de basculer de manière fluide entre les sous-modules.
     - Modularisation physique des fichiers dans l'arborescence dédiée `views/studio/<slug_metier>/<module>.php` :
       * `views/studio/game-elevate-designer/combat-simulator.php`
       * `views/studio/game-elevate-designer/speed-balancing.php`
       * `views/studio/game-elevate-designer/shogunat-survey.php`
       * `views/studio/game-elevate-designer/oasis-ecosystem.php`
       * `views/studio/community-manager/mailing-list.php`
       * `views/studio/qa-tester/qa-validation.php`
       * `views/studio/qa-tester/sandbox.php`
       * `views/studio/backend-dev/system-monitoring.php`
       * `views/studio/narrative-designer/lore.php`
       * `views/studio/roster/members.php`
       * `views/studio/forge/forge-journal.php`
     - Contrôle d'accès et routage au niveau serveur (`index.php`) : redirection automatique avec toast d'alerte si tentative de forçage d'URL (`?page=dev_team&metier=...` ou `?page=dev_team&tab=...`).
  2. **Simulateur de Combat Tactique pour le Game Elevate Designer (`views/studio/game-elevate-designer/combat-simulator.php`, `api/dev_team.php`) :**
     - Ergonomie en 2 colonnes comparatives (Split-screen) :
       * **Colonne Attaquant :** sélection de clan, saisie numérique fluide des troupes (infanterie, cavalerie, archers, armes de siège bélier/catapulte), sélection du Daimyō/Général et doctrine martiale avec bonus offensifs dynamiques.
       * **Colonne Défenseur (Village attaqué) :** sélection du clan défenseur, composition de la garnison, ⭐ **Paramètre critique « Muraille du village »** avec sélecteur de niveau de 0 à 20 (curseur/slider réactif avec badges dynamiques), calcul en temps réel du bonus défensif (+4% Oda, +3.5% Takeda, +5% Tokugawa +15% bouclier passif), PV structurels de la muraille (250 PV/niveau) et tirs de meurtrières (15 pts/niveau).
     - Préréglages rapides en 1 clic : « Raid sur village ouvert (Mur 0) », « Assaut d'un bourg fortifié (Mur 8) », « Grand siège de forteresse (Mur 20) ».
     - Moteur de simulation martiale multi-rounds :
       * Dégradation et brèche de la muraille par les engins de siège avant absorption résiduelle.
       * Calcul des pertes d'effectifs, puissance brute vs effective, et tirs défensifs des meurtrières.
       * Issue de la bataille : Victoire totale attaquant, Victoire à la Pyrrhus, Repli / Échec du siège, ou Triomphe défensif.
     - Rapport d'analyse instantané :
       * Bandeau de statut et jauges d'attrition Tabler.io colorées pour chaque armée.
       * Bilan comparatif régiment par régiment (engagés, pertes, survivants).
       * Journal de combat détaillé tour par tour (Combat Log dans un accordéon Tabler dépliable).
       * Bouton d'exportation instantanée du rapport en Markdown.
       * Bouton d'enregistrement du test de combat dans l'historique d'équilibrage avec attribution de +15 XP Forge (`api/dev_team.php`).
- **Fichiers modifiés :** `views/dev_team.php`, `views/studio/*` (11 fichiers modulaires), `core/DevTeamEngine.php`, `index.php`, `api/dev_team.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec un compte Administrateur : se rendre sur Studio Dev (`/?page=dev_team`). Vérifier que seul l'onglet « Studio & Roster » est visible par défaut si aucun rôle métier n'est assigné.
  2. S'attribuer le métier « Game Elevate Designer » : vérifier l'apparition immédiate de l'onglet « Game Elevate Designer ».
  3. Cliquer sur l'onglet « Game Elevate Designer » : vérifier les 4 sous-modules (Simulateur de Combat, Équilibrage Vitesses, Arpentage des Terres, Écosystème Oasis).
  4. Sélectionner « Simulateur de Combat » :
     - Tester les 3 boutons de préréglages rapides (« Raid sur village ouvert », « Assaut d'un bourg fortifié », « Grand siège de forteresse ») : vérifier le remplissage instantané des effectifs et du niveau de muraille.
     - Manipuler le curseur du niveau de muraille (de 0 à 20) : vérifier la mise à jour réactive des badges d'information (bonus %, PV muraille, tirs meurtrières).
     - Cliquer sur « Lancer la Simulation Tactique » : observer l'apparition immédiate du rapport de combat sans rechargement de page.
     - Vérifier les jauges d'attrition, le tableau détaillé des troupes engagées/perdues, et déplier le « Journal de Combat Détaillé (Combat Log) ».
     - Cliquer sur « Copier le Rapport Markdown » : coller dans un éditeur et vérifier le formatage.
     - Cliquer sur « Sauvegarder ce Test d'Équilibrage » : vérifier la requête AJAX vers l'API, l'apparition du toast de succès et le gain d'XP Forge.
  5. Tenter d'accéder directement par l'URL avec un profil sans le rôle `game_designer` (`/?page=dev_team&metier=game-elevate-designer`) : vérifier le blocage de sécurité et la redirection propre.

---

### [2026-10-01] - profile/poster : Upload d'Avatar Joueur & Sécurisation des Clés de Protection
- **Module :** `profile/poster`
- **Statut :** `À tester`
- **Description :** 
  1. **Résolution du Warning PHP (`views/poster.php`) :** Sécurisation de l'évaluation de la clé `'is_protected'` et des statuts du joueur via `!empty($profile['is_protected'])` pour éliminer l'avertissement PHP 8.x `Undefined array key 'is_protected'`.
  2. **Module de Téléversement d'Avatar Personnalisé (`views/poster.php`, `api/profile.php`, `core/HonorEngine.php`) :**
     - Intégration sur l'affiche féodale d'un médaillon circulaire noble affichant l'avatar personnalisé du Daimyō avec superposition de l'emblème Mon du clan.
     - Bouton interactif « Changer l'Avatar » (uniquement visible sur sa propre affiche) permettant de sélectionner une image.
     - Prévisualisation instantanée via l'API JavaScript `FileReader` sans rechargement de page.
     - Contrôle strict côté backend dans `api/profile.php` : types MIME acceptés (`image/jpeg`, `image/png`, `image/webp`), limite de taille à 2 Mo, génération d'un nom de fichier haché unique (`avatar_{userId}_{timestamp}_{hash}.{ext}`), enregistrement sécurisé dans `/public/assets/uploads/avatars/` et persistance du chemin en base de données dans la table `users`.
- **Fichiers modifiés :** `views/poster.php`, `api/profile.php`, `core/HonorEngine.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se rendre sur son affiche féodale (`/?page=poster` ou `/?page=profile`) : vérifier l'absence totale de Warning PHP en haut de page ou dans les logs.
  2. Vérifier la présence du médaillon avatar avec l'insigne du clan et le bouton « Changer l'Avatar ».
  3. Cliquer sur « Changer l'Avatar » et sélectionner une image locale (JPEG, PNG ou WEBP < 2 Mo) : vérifier la prévisualisation instantanée dans le cercle, l'indicateur de chargement et le toast de confirmation « Avatar établi ! ».
  4. Rafraîchir la page (F5) et vérifier que le nouvel avatar reste affiché et persiste en base de données.
  5. Consulter le profil d'un autre seigneur (`/?page=poster&id=...`) : vérifier que le bouton d'upload est masqué et que son avatar/emblème est rendu correctement.

---

### [2026-10-01] - ui/newsletter & ui/header : Correctif de Grille Split-Screen 2 Colonnes, Iframe Sandbox & Alignement Droite Sous-barre
- **Module :** `ui/newsletter & ui/header`
- **Statut :** `À tester`
- **Description :** 
  1. **Résolution du layout 2 colonnes (`views/newsletter_compose.php`) :** Correction d'une balise `</div>` manquante dans le header de la carte gauche qui cassait l'arbre DOM et forçait la colonne de prévisualisation à passer sous l'atelier de rédaction. Remplacement des classes de colonnes par `col-12 col-lg-6 col-xl-6` pour garantir que l'éditeur et l'aperçu dynamique restent côte à côte sur tous les écrans desktop dès le breakpoint `lg` (992px+).
  2. **Résolution de l'erreur JavaScript Sandbox Iframe (`views/newsletter_compose.php`) :** Ajout de la permission `allow-scripts` sur l'attribut sandbox de l'iframe de prévisualisation (`sandbox="allow-same-origin allow-scripts"`) pour éliminer l'erreur console « Blocked script execution in about:srcdoc », combiné avec un assainissement regex strict de `bodyHtml` (suppression de toute balise `<script>` ou gestionnaire JS `on*` inline injecté).
  3. **Ajustement de la sous-barre du Header (`views/partials/header.php`, `public/js/audio_manager.js`) :** Alignement de l'ensemble des badges et boutons (Kobans, Sceaux, Studio Dev, Admin, Ambiance) complètement à droite via `justify-content-end`. Remplacement définitif de toute émoticône sur le bouton Ambiance par l'icône Font Awesome `<i class="fa-solid fa-volume-high"></i>` (avec bascule dynamique `audio-playing` et préservation de la taille `fs-3`), avec infobulle interactive Bootstrap/Tabler mise à jour en temps réel.
- **Fichiers modifiés :** `views/newsletter_compose.php`, `views/partials/header.php`, `public/js/audio_manager.js`, `fonctionnalités.md`
- **Vérification QA :**
  1. Ouvrir la page de composition de missive (`/?page=newsletter_compose`) sur écran desktop (largeur >= 992px) : vérifier que l'atelier de rédaction et le cadre de prévisualisation dynamique sont affichés côte à côte de manière fluide.
  2. Ouvrir les outils de développement (Console F12) et saisir du texte dans l'éditeur : vérifier qu'aucune erreur `Blocked script execution in about:srcdoc` n'apparaît dans la console.
  3. Observer la sous-barre sous le header : vérifier que tous les badges compacts sont bien calés à l'extrémité droite de l'écran.
  4. Vérifier le bouton d'Ambiance sonore : vérifier la présence exclusive de l'icône Font Awesome (`fa-volume-high`), tester le survol pour afficher l'infobulle et cliquer pour lancer/couper la musique féodale.

---

### [2026-10-01] - ui/navbar & community/newsletter : Sous-barre Ergonomique à Badges & Outil d'Insertion des Nouveautés dans la Missive
- **Module :** `ui/navbar & community/newsletter`
- **Statut :** `À tester`
- **Description :** 
  1. **Réorganisation Ergonomique de la Barre de Navigation (`views/partials/header.php`) :** Extraction hors de la barre principale des éléments Kobans, Sceau actif, Studio Dev, Admin et Ambiance sonore. Positionnement sur une sous-barre dédiée immédiatement sous la barre de navigation principale, alignée à gauche avec le même conteneur fluide (`container-fluid px-3 px-lg-4`). Formatage de chaque élément sous forme de badge ultra-compact contenant **UNIQUEMENT son icône vectorielle Font Awesome**, avec infobulle / tooltip Tabler/Bootstrap (`data-bs-toggle="tooltip"` / `title`) affichant l'intitulé textuel complet et explicatif au survol de la souris. Initialisation globale et réactive des tooltips dans `views/partials/footer.php` et `public/js/app.js`.
  2. **Insertion Dynamique des Nouveautés du Jeu dans la Missive (`views/newsletter_compose.php`) :** Intégration d'un outil de récupération automatique des dernières nouveautés du jeu directement depuis le registre officiel (`fonctionnalités.md` via `FeatureRegistry`). Ajout d'un bouton d'action « Insérer des Nouveautés » dans le bandeau de l'atelier de rédaction et au-dessus de l'éditeur WYSIWYG. Déploiement d'un tiroir latéral dédié (Offcanvas Tabler) avec champ de recherche en direct, sélection rapide (« Tout cocher » / « Tout décocher »), compteur dynamique, choix du format d'insertion (liste à puces synthétique ou blocs détaillés immersifs avec dates et citations), injection fluide dans l'éditeur Quill à la position du curseur et rafraîchissement immédiat de la prévisualisation en direct.
- **Fichiers modifiés :** `views/partials/header.php`, `views/partials/footer.php`, `public/js/app.js`, `views/newsletter_compose.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Charger n'importe quelle page du jeu et observer la barre de navigation supérieure : vérifier que la navbar principale est allégée et qu'une sous-barre dédiée apparaît immédiatement en-dessous, alignée à gauche.
  2. Vérifier que la sous-barre affiche uniquement les icônes Font Awesome pour Kobans (pièce d'or), Sceau actif (tampon/tampon impérial), Studio Dev (boussole/crayon), Admin (bouclier/clé) et Ambiance (haut-parleur).
  3. Survoler chacun des badges avec la souris : vérifier l'apparition instantanée de l'infobulle (tooltip Bootstrap/Tabler) avec le texte descriptif complet (ex: « Kobans Impériaux : 1,250 », « Sceau Impérial : Aucun sceau actif », etc.).
  4. Se rendre sur la page de composition de la missive impériale (`/?page=newsletter_compose`).
  5. Cliquer sur le bouton « Insérer des Nouveautés » : vérifier l'ouverture fluide du tiroir latéral droit (Offcanvas).
  6. Tester la recherche dans l'Offcanvas (taper par exemple « layout », « icons », « newsletter ») : vérifier le filtrage instantané de la liste.
  7. Tester les boutons « Tout cocher » et « Tout décocher » et observer la mise à jour des compteurs.
  8. Sélectionner une ou plusieurs nouveautés, choisir le format (liste à puces ou blocs détaillés) et cliquer sur « Insérer dans la Missive » : vérifier l'insertion automatique du texte formaté dans l'éditeur Quill, la fermeture du tiroir, le toast de notification et l'actualisation instantanée de l'aperçu e-mail dans la colonne de droite.

---

### [2026-10-01] - community/newsletter : Refonte de la Missive Impériale, Page Dédiée Split-Screen & Éditeur WYSIWYG
- **Module :** `community/newsletter`
- **Statut :** `À tester`
- **Description :** Transformation complète de l'outil de composition de missives et newsletters d'une fenêtre modale vers une page d'administration dédiée en split-screen (`?page=newsletter_compose`). Intégration d'un éditeur riche WYSIWYG (Quill) avec barre d'outils complète (titres, gras, italique, citations, listes, liens, couleurs féodales) et modèles d'annonces pré-rédigés. Prévisualisation dynamique en direct dans un cadre isolé (`iframe`) reproduisant au pixel près le rendu réel en boîte de réception sur fond clair/parchemin washi, rehaussé de touches Rouge carmin/vermillon et Or impérial. En-tête avec logo officiel centré et pied de page avec sceau impérial du chrysanthème, mentions de conformité RGPD et liens de désinscription automatique. Actions dédiées : enregistrement de brouillons (`status = 'draft'`), planification temporelle, expédition de tests personnels vers l'adresse du Daimyō connecté et envoi de campagne avec attribution d'XP Forge.
- **Fichiers modifiés :** `views/newsletter_compose.php`, `views/dev_team.php`, `index.php`, `core/MailingListEngine.php`, `core/MailService.php`, `api/dev_team.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec le profil Administrateur ou Community Manager et se rendre sur `/?page=dev_team&tab=mailing`.
  2. Cliquer sur « Composer une Missive » et vérifier la redirection immédiate vers la page dédiée `/?page=newsletter_compose` (et non plus l'ouverture d'une modale).
  3. Vérifier la disposition split-screen : colonne gauche (sujet, sélecteur de cible avec badges, date d'envoi, éditeur Quill) et colonne droite (prévisualisation instantanée).
  4. Tester la saisie dans l'éditeur WYSIWYG : vérifier la mise à jour réactive sans latence du rendu dans l'aperçu dynamique.
  5. Tester les boutons de bascule d'affichage Bureau (640px) et Mobile (380px) de l'aperçu.
  6. Tester le bouton « Modèles Rapides » et appliquer le modèle « Mobilisation & Siège Féodal » : vérifier l'insertion automatique des blocs formatés.
  7. Tester le bouton « Enregistrer le Brouillon » : vérifier le retour d'API avec ID de brouillon et l'apparition de l'état sauvegardé.
  8. Tester « Envoyer un Test » : vérifier la confirmation d'envoi du test sur son propre e-mail.
  9. Vérifier le gabarit d'e-mail dans l'aperçu : fond clair/parchemin (zéro fond sombre), logo officiel centré en en-tête, badge de clan thématique, corps de texte lisible, bouton CTA rouge carmin/or et bandeau décoratif avec le sceau du chrysanthème en pied de page.

---

### [2026-10-01] - ui/icons : Harmonisation Visuelle Intégrale & Remplacement des Émojis par Font Awesome 6
- **Module :** `ui/icons`
- **Statut :** `À tester`
- **Description :** Remplacement systématique de la totalité des émoticônes/emojis Unicode textuels (🌾, ⚔️, 💰, 👑, ⚙️, 📜, 🛡️, 🏯, 🏮, 🥷, 🐎, 🐺, 🐗, 🐻, etc.) par des icônes vectorielles professionnelles de la bibliothèque Font Awesome 6.5.2 (`<i class="fa-solid fa-..."></i>`). Intégration en mode local/offline-first dans `public/fontawesome/` pour une totale indépendance vis-à-vis des CDN tiers. Harmonisation et contextualisation visuelle avec les utilitaires Tabler.io / Bootstrap (`me-1`, `me-2`, `align-middle`, `text-warning`, `text-danger`, `text-success`, `text-primary`, `text-info`, `text-teal`). Sécurisation des éléments `<select>` (suppression pure des emojis sans injection HTML) et adaptation du DOM dynamique JavaScript (`.innerHTML` au lieu de `.textContent` / `.innerText` lors de la mise à jour réactive des boutons et badges).
- **Fichiers modifiés :** `config/game_constants.php`, `views/partials/header.php`, `views/partials/footer.php`, `views/ranking.php`, `views/reports.php`, `views/messages.php`, `views/poster.php`, `views/resources.php`, `views/field.php`, `views/city.php`, `views/building.php`, `views/barracks.php`, `views/shipyard.php`, `views/castle.php`, `views/research.php`, `views/map.php`, `views/fleet.php`, `views/hero.php`, `views/empire.php`, `core/QuestEngine.php`, `views/partials/quest_banner.php`, `views/partials/troops_panel.php`, `views/partials/imperial_seal_modal.php`, `views/partials/announcement_modal.php`, `views/partials/ai_prompt_modal.php`, `views/pedagogy.php`, `views/alliance.php`, `views/support.php`, `views/edit_ticket.php`, `views/admin_support_traiter.php`, `views/privilege.php`, `views/forum.php`, `views/auth.php`, `views/partials/admin_mail.php`, `views/partials/admin_updates.php`, `views/partials/admin_pedagogy.php`, `views/admin.php`, `views/dev_team.php`, `views/docs.php`, `public/js/app.js`, `public/js/galaxy_map.js`, `fonctionnalités.md`
- **Vérification QA :** Parcourir les différentes pages du jeu (Navbar, Ressources, Bâtiments, Champs, Caserne, Chantier naval, Carte, Rapports, Messagerie, Alliance, Forum, Héros, Administration et Studio Dev) : vérifier qu'aucun emoji textuel brut n'apparaît dans l'interface et que chaque symbole est rendu par une icône vectorielle nette, parfaitement alignée avec son intitulé textuel et dotée d'une couleur thématique appropriée.

---

### [2026-09-30] - ui/layout : Harmonisation Pleine Largeur Fluide & Alignement Vertical du Corps de Page
- **Module :** `ui/layout`
- **Statut :** `À tester`
- **Description :** Remplacement des conteneurs bridés ou à largeur fixe (`container-xl`, `container`, etc.) par le format fluide calibré `container-fluid px-3 px-lg-4` sur le conteneur principal `.page-body > .container-fluid`, aligné verticalement au pixel près sur la barre de ressources et la navbar. Surcharge CSS globale dans `public/css/style.css` pour neutraliser les conteneurs imbriqués sans régression sur la vue tactique `map`. Adaptation des vues centrales (`views/dev_team.php`, `views/poster.php`, `views/ranking.php`, `views/reports.php`, `views/research.php`, `views/chat.php`, `views/castle.php`, `views/docs.php`, `views/support.php`, `views/edit_ticket.php`, `views/pedagogy.php`, `views/changelog.php`).
- **Fichiers modifiés :** `views/partials/header.php`, `views/partials/footer.php`, `public/css/style.css`, `views/dev_team.php`, `views/poster.php`, `views/ranking.php`, `views/reports.php`, `views/research.php`, `views/chat.php`, `views/castle.php`, `views/docs.php`, `views/support.php`, `views/edit_ticket.php`, `views/pedagogy.php`, `views/changelog.php`
- **Vérification QA :** Parcourir les différentes vues du jeu sur écran large (>= 1200px et 1080p/1440p) et vérifier que le corps des pages s'étend harmonieusement sur toute la largeur disponible, avec un alignement vertical parfait des bordures gauche et droite entre la barre de ressources/navbar et les cartes centrales du jeu. S'assurer de l'absence de scroll horizontal non désiré (`overflow-x: hidden`).

---

### [2026-09-30] - dev_team : Roster & Métiers — Exclusion Stricte des Profils IA / Bots
- **Module :** `dev_team`
- **Statut :** `À tester`
- **Description :** Filtrage SQL strict (`WHERE is_bot = 0`) dans le sélecteur de membres éligibles à l'attribution des métiers de développement et dans le listing de l'équipe, avec blocage applicatif dans `assignRoles()` et le contrôleur d'API pour empêcher toute assignation de rôle à un bot.
- **Fichiers modifiés :** `views/dev_team.php`, `core/DevTeamEngine.php`, `api/dev_team.php`
- **Vérification QA :** Ouvrir la modale d'attribution de métiers dans Studio Dev et vérifier que seuls les comptes de joueurs réels figurent dans la liste déroulante (aucun bot PNJ ou IA présent). Tenter d'attribuer un rôle à un bot via l'API et vérifier le rejet avec message explicite.

---

### [2026-09-30] - reports : Pagination du Registre & Suppressions Unitaire et en Masse
- **Module :** `reports`
- **Statut :** `À tester`
- **Description :** Mise en place d'une pagination Tabler.io par page de 15 rapports avec contrôles de navigation réactifs et indicateur de page, ajout d'un bouton d'action unitaire pour supprimer la chronique consultée (ou directement depuis la liste), ajout d'un bouton de purge globale « Supprimer tous les rapports », sécurisation avec confirmation obligatoire via modale Tabler.io (`showModalConfirm`), et création de l'endpoint `api/reports.php`.
- **Fichiers modifiés :** `views/reports.php`, `api/reports.php`
- **Vérification QA :** Naviguer sur la page des rapports de combat, vérifier la pagination et les boutons précédent/suivant, supprimer un rapport spécifique et vérifier l'apparition de la modale Tabler ainsi que sa suppression effective, puis tester la purge globale via le bouton supérieur.

---

### [2026-09-30] - messages : Purge Globale des Missives & Confirmation Modale
- **Module :** `messages`
- **Statut :** `À tester`
- **Description :** Ajout du bouton d'action « Supprimer tous les messages » dans l'en-tête de la messagerie, confirmation préalable obligatoire via modale Tabler.io, implémentation de `deleteAllMessages()` dans `MessageEngine` (avec soft-delete selon le scope réception/envoi et hard-delete si purge mutuelle ou système) et point d'API dédié dans `api/messages.php`.
- **Fichiers modifiés :** `views/messages.php`, `core/MessageEngine.php`, `api/messages.php`
- **Vérification QA :** Accéder aux missives diplomatiques, cliquer sur « Supprimer tous les messages », vérifier que la boîte de dialogue Tabler prévient de l'irréversibilité de l'action, confirmer la suppression et constater le vidage immédiat de la boîte sans erreur.

---

### [2026-09-30] - layout : Header Pleine Largeur Fluide & Navbar Positionnée Tout en Haut
- **Module :** `layout`
- **Statut :** `À tester`
- **Description :** Repositionnement de la barre de navigation principale tout en haut de la page (`sticky-top`), conversion de tous les conteneurs d'en-tête vers la pleine largeur fluide (`container-fluid` au lieu de `container` ou `container-xl`), intégration harmonieuse des badges (Koban, Sceau, Immunité) et des utilitaires (Studio Dev, Admin, Audio, Fiefs, Héros, Déconnexion) dans la navbar responsive Tabler.io.
- **Fichiers modifiés :** `views/partials/header.php`
- **Vérification QA :** Vérifier que la barre de navigation est le premier élément visible en haut de l'écran, qu'elle occupe 100% de la largeur du viewport sans marge latérale superflue, que le logo central et les barres de ressources s'adaptent de manière fluide, et que le menu mobile toggler fonctionne sur petit écran.

---

### [2026-09-30] - dev_team : Refonte du Roster & Attribution Multiple
- **Module :** `dev_team`
- **Statut :** `Validée`
- **Description :** Permet la sélection multiple et l'assignation de plusieurs métiers à un membre avec blocage proactif des doublons, badges visuels réactifs et révocation immédiate sans rechargement.
- **Fichiers modifiés :** `views/dev_team.php`, `core/DevTeamEngine.php`, `api/dev_team.php`
- **Vérification QA :** Tester l'attribution de 2 métiers simultanés, vérifier que les métiers possédés sont grisés dans la modale, supprimer un métier via la croix et constater la disparition fluide du badge sans recharger la page.

---

### [2026-09-30] - admin : Routage et Affichage de l'Onglet Messagerie
- **Module :** `admin`
- **Statut :** `Validée`
- **Description :** Autorisation de l'onglet `mail` dans `$allowedTabs` PHP et dans le validateur JS `validTabs` pour permettre l'affichage du module messagerie & SMTP.
- **Fichiers modifiés :** `views/admin.php`
- **Vérification QA :** Ouvrir `/admin/mail` ou cliquer sur "Messagerie & SMTP" et vérifier que le panneau s'affiche correctement sans redirection vers le tableau de bord.

---

### [2026-09-30] - auth : Refonte Inscription & Activation par E-mail
- **Module :** `auth`
- **Statut :** `Validée`
- **Description :** Jauge dynamique de mot de passe à 4 niveaux, jetons cryptographiques SHA-256 à expiration 24h, et client SMTP natif RFC 5321 avec chiffrement AES-256-CBC des mots de passe.
- **Fichiers modifiés :** `core/Auth.php`, `core/MailService.php`, `views/auth.php`, `views/partials/admin_mail.php`
- **Vérification QA :** Créer un compte, tester la jauge de complexité, vérifier le blocage si mot de passe non concordant, et contrôler la validation par jeton d'activation.

---

### [2026-09-30] - qa : Module de Recette QA & Contrôle de Syntaxe Automatique
- **Module :** `qa`
- **Statut :** `À tester`
- **Description :** Interface dédiée QA & Recette dans Studio Dev, lecteur/gestionnaire de statut des fonctionnalités et exécution automatisée du contrôle de syntaxe `php -l`.
- **Fichiers modifiés :** `core/FeatureRegistry.php`, `core/QASyntaxChecker.php`, `views/dev_team.php`, `api/dev_team.php`, `fonctionnalités.md`
- **Vérification QA :** Accéder à l'onglet "Contrôle Qualité & Recette" dans Studio Dev, lancer le contrôle syntaxique, et basculer le statut d'une fonctionnalité entre "À tester", "Validée" et "Rejetée".

---

### [2026-09-30] - community : Métier Community Manager, Mailing List & Opt-in RGPD
- **Module :** `community`
- **Statut :** `À tester`
- **Description :** Intégration du métier Community Manager (permissions RBAC, badges, anti-doublons), case à cocher explicite d'opt-in newsletter non cochée par défaut à l'inscription et installation, moteur MailingListEngine avec filtres, export CSV, expédition de missives et interface Tabler.io dans Studio Dev.
- **Fichiers modifiés :** `core/DevTeamEngine.php`, `core/MailingListEngine.php`, `core/Auth.php`, `core/InstallEngine.php`, `install.php`, `index.php`, `views/auth.php`, `views/dev_team.php`, `views/partials/admin_mail.php`, `api/dev_team.php`, `database/schema_complete.sql`, `database/migrate_community_and_newsletter.php`
- **Vérification QA :** Assigner le métier Community Manager à un joueur, accéder à l'onglet "Mailing List" dans Studio Dev, filtrer les abonnés, exporter en CSV, envoyer une missive de test sur son propre e-mail et vérifier que la case newsletter est bien non cochée par défaut à l'inscription.

---

### [2026-09-30] - dev_team : Contrôle d'Accès Strict et Masquage DOM des Onglets par Métier
- **Module :** `dev_team`
- **Statut :** `À tester`
- **Description :** Conditionnement strict du rendu PHP de chaque onglet du menu et de son panneau associé (aucun nœud généré dans le DOM si métier non possédé), conservation de la vue totale pour les administrateurs globaux, interception et redirection propre côté serveur dans `index.php` avec alerte contextuelle en cas de tentative de forçage d'URL (`?page=dev_team&tab=...`), et sécurisation des points d'API REST.
- **Fichiers modifiés :** `core/DevTeamEngine.php`, `index.php`, `views/dev_team.php`, `api/dev_team.php`, `fonctionnalités.md`
- **Vérification QA :** Se connecter avec un compte ayant uniquement le métier Narrative Designer : vérifier que seuls les onglets Roster, Lore et Forge s'affichent dans la navigation et que le DOM ne contient aucun élément de QA, Mailing, Sandbox ou Système. Tenter de forcer l'URL `/?page=dev_team&tab=system` et vérifier la redirection automatique vers le premier onglet autorisé avec le bandeau d'alerte. Se connecter en administrateur et vérifier que les 7 onglets sont tous visibles et accessibles.

---

### [2026-09-30] - dev_team : Migration des Modules de Paramétrage vers Studio Dev & Règle Stricte Game Elevate Designer
- **Module :** `dev_team`
- **Statut :** `À tester`
- **Description :** Retrait complet de l'administration générale (`views/admin.php`) des 3 modules de configuration : « Constante et équilibrage de vitesse du jeu », « Arpenté rapporteur du shogunat et expansion des provinces », et « Écosystème des oasis ». Migration sous « Studio Dev » sous forme de 3 onglets dédiés (`game_speeds`, `world_expansion`, `oases_ecosystem`). Application d'une règle de sécurité stricte : ces onglets sont EXCLUSIVEMENT réservés aux titulaires du métier « Game Elevate Designer ». L'administrateur suprême ne les voit pas par défaut (seul l'onglet « Studio / Roster » lui est garanti) ; ils ne lui deviennent visibles que s'il s'est lui-même assigné le métier « Game Elevate Designer ». Sécurisation triple couche (zéro code DOM injecté, redirection serveur HTTP avec alerte, et blocage HTTP 403 des contrôleurs API).
- **Fichiers modifiés :** `core/DevTeamEngine.php`, `views/dev_team.php`, `views/admin.php`, `api/dev_team.php`, `api/admin.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec le compte Administrateur non titulaire du métier Game Elevate Designer : vérifier que dans l'administration générale, les sections vitesses, arpentage et oasis ont disparu et que seuls les 12 Donjons subsistent.
  2. Ouvrir « Studio Dev » avec ce même compte : constater que les 3 onglets (`Vitesses & Équilibrage`, `Arpentage & Provinces`, `Écosystème des Oasis`) ne sont PAS présents dans la barre d'onglets ni dans le DOM.
  3. Forcer l'URL `/?page=dev_team&tab=game_speeds` : constater la redirection immédiate vers le premier onglet autorisé avec le bandeau d'accès restreint.
  4. Via l'onglet « Roster et Métiers », assigner le métier « Game Elevate Designer » au compte Administrateur : constater l'apparition immédiate des 3 nouveaux onglets.


---

### [2026-09-30] - poster : Page Dédiée « Mon Affiche Féodale » & Lore Immersif du Clan
- **Module :** `poster`
- **Statut :** `À tester`
- **Description :** Transformation de la fenêtre modale « Mon affiche » / « M'affiche » en une page dédiée complète (`?page=poster` et alias `?page=profile`). Hero header immersif reprenant l'identité visuelle du clan féodal du joueur (Oda, Takeda, Tokugawa ou provincial), Mon/armoiries, bannière stylisée, titre féodal et devise ancestrale. Section narrative détaillée exposant les origines du clan sous les époques Sengoku/Muromachi/Edo, la doctrine militaire et économique (arquebuses de Tanegashima, libre marché Rakuichi Rakuza, Fūrinkazan, cavalerie d'élite Akazonae, ninjas d'Iga, fortifications inexpugnables), parchemins de la devise du Daimyō avec édition asynchrone AJAX, tableau des fiefs provinciaux avec coordonnées cliquables, KPIs de performances hebdomadaires et vitrine des médailles de guerre d'honneur. Intégration de la redirection automatique depuis toutes les vues (ranking, messages, alliance, forum, header).
- **Fichiers modifiés :** `index.php`, `views/poster.php`, `views/partials/header.php`, `views/partials/footer.php`, `views/ranking.php`, `views/alliance.php`, `views/forum.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Cliquer sur « Mon Affiche Féodale » dans le menu déroulant « Mon Empire » : vérifier l'ouverture directe de la page dédiée `/?page=poster`.
  2. Vérifier l'en-tête immersif avec les armoiries (Mon) et le titre féodal correspondant au clan du joueur (Clan Oda, Clan Takeda ou Clan Tokugawa).
  3. Vérifier la section narrative avec le contexte historique (Sengoku/Muromachi/Edo), la doctrine martiale et les 4 piliers illustrés.
  4. Tester la modification de la devise personnelle via le bouton « Modifier ma Devise » (ou via l'URL avec `?page=poster&edit=1`) et vérifier sa mise à jour instantanée sans rechargement.
  5. Se rendre sur le classement (`/?page=ranking`) et cliquer sur un Daimyō adverse : vérifier l'affichage de son affiche féodale avec les boutons d'action diplomatique (« Écrire une Missive », « Chuchoter au Salon », « Lancer une Expédition »).

---

### [2026-09-30] - typography : Intégration Typographique « Dela Gothic One » (Google Fonts)
- **Module :** `ui/typography`
- **Statut :** `À tester`
- **Description :** Intégration de la police Google Fonts « Dela Gothic One » pour renforcer l'identité et l'immersion féodale (Sengoku Jidai) du jeu. Import via balises `<link rel="preconnect">` et `<link href="...">` dans les en-têtes globaux (`views/partials/header.php`, `views/auth.php`, `views/changelog.php`, `views/pedagogy.php`, `install.php`) et via `@import` dans `public/css/style.css`. Déclaration des variables CSS `--font-game-title`, `--font-feodal`, `--tblr-font-game` et de la classe utilitaire réutilisable `.font-game` (avec alias `.title-feodal`). Application ciblée sur les éléments à fort impact : logo et marque (« La Voie du Shogun »), en-tête immersif et titres de l'écran « Mon affiche », titres des cartes de jeu, boutons d'action majeurs et titres des bâtiments/parcelles, tout en préservant la police sans-serif standard (Inter) pour les corps de texte, formulaires et tableaux de données.
- **Fichiers modifiés :** `public/css/style.css`, `views/partials/header.php`, `views/auth.php`, `views/changelog.php`, `views/pedagogy.php`, `install.php`, `views/poster.php`, `views/ranking.php`, `views/castle.php`, `views/field.php`, `views/building.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Inspecter le réseau dans les outils de développement du navigateur : vérifier que la ressource `fonts.googleapis.com/css2?family=Dela+Gothic+One` est chargée avec succès (HTTP 200).
  2. Vérifier que le titre de marque dans la barre de navigation supérieure (« La Voie du Shogun ») est stylisé avec Dela Gothic One.
  3. Ouvrir la page « Mon Affiche Féodale » (`/?page=poster`) : vérifier l'application de la police sur le nom du joueur, le badge du clan, les en-têtes de cartes et les grands compteurs chiffrés des KPIs militaires.
  4. Vérifier que la lisibilité reste irréprochable sur les paragraphes de texte narratif, les tableaux et les formulaires (police standard Inter).

---

### [2026-10-01] - clans : Intégration des Illustrations de Doctrines Martiales & Enrichissement du Grimoire des Prompts (83 Prompts)
- **Module :** `poster / grimoire`
- **Statut :** `À tester`
- **Description :** 
  1. Intégration des 3 œuvres d'art haute résolution (2752×1536) dans l'arborescence officielle (`public/assets/clans/`) illustrant les chroniques et doctrines martiales de chaque clan féodal :
     - Clan Oda (`clan_oda_war_council.jpg`) : Conseil de guerre stratégique au sommet du donjon d'Azuchi, Oda Nobunaga et ses généraux avec cartes tactiques en papier washi et bannières du clan.
     - Clan Takeda (`clan_takeda_cavalry_charge.jpg`) : Charge héroïque de la cavalerie rouge Akazonae à l'aube sur les plaines de Kawanakajima avec étendards du Fūrinkazan.
     - Clan Tokugawa (`clan_tokugawa_covert_scout.jpg`) : Ruse et reconnaissance nocturne d'un shinobi d'Iga observant une puissante forteresse sous la brume et la pluie.
  2. Intégration visuelle dans la page « Mon Affiche Féodale » (`views/poster.php`) : Bannière d'illustration haute fidélité insérée dans la carte « Chroniques & Doctrine Militaire du Clan », avec titre féodal, devise ancestrale et badge circulaire interactif de transparence IA (`AiPromptHelper::renderBadge`).
  3. Enrichissement du Grimoire des Prompts (`views/partials/grimoire_prompts_data.php` et `views/partials/grimoire_album.php`) : Ajout de la nouvelle catégorie `clans` (« 📜 Chroniques des Clans »), passage dynamique du catalogue à 83 prompts IA, bouton de filtre nav-pill dédié et mise à jour de l'export Markdown.
- **Fichiers modifiés :** `public/assets/clans/clan_oda_war_council.jpg`, `public/assets/clans/clan_takeda_cavalry_charge.jpg`, `public/assets/clans/clan_tokugawa_covert_scout.jpg`, `views/poster.php`, `views/partials/grimoire_prompts_data.php`, `views/partials/grimoire_album.php`, `fonctionnalités.md`, `CHANGELOG.md`
- **Vérification QA :**
  1. Se rendre sur la page « Mon Affiche Féodale » (`/?page=poster`) avec un compte de chaque clan (Oda, Takeda, Tokugawa) : vérifier la présence de l'illustration correspondante au-dessus de la section narrative avec l'overlay féodal.
  2. Cliquer sur le badge « ? » en haut à droite de l'illustration : vérifier l'ouverture de la modale de transparence IA affichant le prompt anglais complet, la traduction française et les détails de l'image.
  3. Se rendre sur la page Pédagogie / Grimoire des Prompts (`/?page=pedagogy`) : constater la présence du compteur à 83 prompts et du filtre « 📜 Chroniques des Clans ». Cliquer sur ce filtre et vérifier l'affichage des 3 nouvelles cartes avec copie en un clic du prompt et vue grand format.

---

### [2026-10-02] - forum-wysiwyg : Éditeur WYSIWYG Moderne (Quill.js) & Neutralisation Stricte XSS Backend
- **Module :** `forum`
- **Statut :** `À tester`
- **Description :** Modernisation de l'espace d'expression du Forum féodal en remplaçant les `<textarea>` bruts par un éditeur WYSIWYG moderne et fluide basé sur Quill.js (thème Snow) pour la création de sujets, la rédaction de réponses et l'édition de messages existants. Barre d'outils complète intégrée : enrichissements typographiques (gras, italique, souligné, barré), listes à puces et ordonnées, blocs de citation stylisés, liens hypertextes et insertion d'images. Côté backend, sécurisation absolue via une méthode d'assainissement HTML stricte (`ForumEngine::sanitizeHtml()`) : filtrage par liste blanche de balises et attributs autorisés, neutralisation préventive des balises exécutables (`<script>`, `<iframe>`, `<object>`, `<embed>`), stripping des attributs d'écouteurs d'événements JavaScript inline (`onclick`, `onerror`, `onload`, etc.) et validation rigoureuse des protocoles d'URL (`http`, `https`, `mailto`, liens relatifs).
- **Fichiers modifiés :** `views/forum.php`, `core/ForumEngine.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Accéder au forum (`/?page=forum`) et ouvrir la création d'un nouveau sujet : vérifier la présence de l'éditeur Quill avec barre d'outils complète.
  2. Rédiger un message formaté avec styles (gras, italique), listes et citation, puis publier : constater le respect des styles typographiques dans l'affichage du sujet.
  3. Tester la réponse rapide et l'édition de message : vérifier que le contenu existant est correctement pré-chargé dans l'éditeur Quill et sauvegardé fidèlement.
  4. Tester la sécurité XSS en injectant du code malveillant (ex. `<script>alert(1)</script>`, `<img src=x onerror=alert(1)>`, lien `javascript:void(0)`) : vérifier que le script est systématiquement neutralisé et n'est jamais exécuté par le navigateur.

---

### [2026-10-02] - avatar-persistence : Persistance Immédiate de l'Avatar Féodal & Cache-Busting
- **Module :** `poster / profile`
- **Statut :** `À tester`
- **Description :** Résolution du dysfonctionnement de persistance de l'avatar téléversé sur la page d'affiche féodale (`/?page=poster`). Les causes racines ont été traitées : correction du chemin d'écriture des fichiers (`/public/assets/uploads/avatars/`), sécurisation des commits de transactions PDO dans `core/HonorEngine.php`, synchronisation immédiate des variables de session actives (`$_SESSION['user']['avatar']` et `$_SESSION['avatar']`) dès l'upload sans exiger de déconnexion/reconnexion, sélection explicite du champ avatar dans `core/Auth.php`, et ajout de suffixes de cache-busting dynamiques (`?v=...`) sur toutes les balises `<img>` et requêtes d'actualisation DOM JavaScript.
- **Fichiers modifiés :** `api/profile.php`, `core/Auth.php`, `core/HonorEngine.php`, `views/poster.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se rendre sur la page « Mon Affiche Féodale » (`/?page=poster`).
  2. Téléverser un nouvel avatar personnalisé (JPEG ou PNG) : constater la mise à jour immédiate de la photo dans l'interface sans rechargement.
  3. Recharger complètement la page (F5 ou Ctrl+F5) : vérifier que le nouvel avatar reste affiché et ne revient pas à l'icône par défaut.
  4. Naviguer vers d'autres pages (classement, forum, header) : vérifier que l'avatar est conservé dans l'ensemble de l'application.

---

### [2026-10-02] - population-contentment : Système de Population, Main-d'Œuvre, Règle Asymétrique du Saké & Mécanique d'Exode
- **Module :** `economy / population`
- **Statut :** `À tester`
- **Description :** Implémentation du système de démographie, répartition du travail et satisfaction des villageois (`core/PopulationEngine.php`) intégré au moteur de calcul planétaire (`core/PlanetEngine.php`) :
  - **Quotas de main-d'œuvre par bâtiment :** Définition stricte des besoins en ouvriers par niveau pour les 4 parcelles rurales (Bûcherons : 2, Carrières : 2, Rizières : 2, Shinto : 1) et les 16 structures urbaines (Scierie : 3, Briqueterie : 3, Meunerie : 3, Forge : 4, Caserne : 2, Écuries : 3, Académie : 2, Tenshu : 2, Marché : 1, Tour de Guet : 1, Remparts : 1, Grenier : 1, Silo : 1, Pavillon de Thé : 1, Place d'Exercices : 1, Ambassade : 1). En cas de sous-effectif global (population < ouvriers requis), un malus proportionnel est automatiquement appliqué au rendement horaire des récoltes ruraux.
  - **Besoins vitaux vs Biens de confort (Règle asymétrique du Saké) :** 
    * *Besoins vitaux :* La nourriture (farine de riz / riz impérial) et la sérénité spirituelle shinto sont indispensables. Une pénurie de nourriture dégrade violemment le moral féodal (-55%), tandis qu'un déficit de sérénité cause un malus de -20%.
    * *Règle asymétrique du Saké :* La présence de saké dans les cuves octroie un bonus net de contentement (+15%). En cas d'épuisement ou absence de saké, AUCUN MALUS n'est appliqué (impact 0 neutre). Le saké étant un luxe réjouissant, son absence ne rend pas le peuple malheureux si les besoins vitaux sont garantis.
  - **Jauge de contentement (0-100%) & Exode :** Si le contentement chute en dessous de 25% (famine prolongée, déficit cumulé), une mécanique de crise d'exode se déclenche avec la fuite progressive de 6% des villageois par heure (avec plancher de sécurité à 20 habitants). À l'inverse, si le contentement est supérieur ou égal à 50% avec vivres suffisants, une croissance démographique naturelle s'opère jusqu'à la capacité d'accueil des logements.
  - **Interface Tabler.io :**
    * 8e carte dédiée « Peuple & Satisfaction » dans la bannière supérieure des ressources : affichage des habitants / logements, ouvriers requis vs libres, jauge de satisfaction dynamique avec code couleur (vert euphorique, sarcelle paisible, jaune/orange inquiet, rouge clignotant pulsant avec barre striée animée en cas d'exode imminent `< 25%`).
    * Icône saké violette animée sur la carte lors de l'activation du bonus +15%.
    * Popover Bootstrap/Tabler interactif au survol détaillant le statut vital, le bonus saké asymétrique, le malus de sous-effectif et les prévisions démographiques.
    * Badges d'ouvriers requis vs disponibles et alertes de sous-effectif sur les cartes d'exploitation rurale (`views/field.php`), d'édifices urbains (`views/building.php`), de la cité castrale (`views/city.php`) et du terroir (`views/resources.php`).
- **Fichiers modifiés :** `core/PopulationEngine.php`, `core/PlanetEngine.php`, `views/partials/header.php`, `views/partials/footer.php`, `views/building.php`, `views/field.php`, `views/city.php`, `views/resources.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Consulter le bandeau des ressources en haut de page : vérifier la présence de la 8e carte « Peuple » avec la jauge de satisfaction et le nombre d'habitants.
  2. Survoler la carte : vérifier l'ouverture du popover complet détaillant les besoins vitaux, le bonus de saké et la main-d'œuvre.
  3. Vérifier la règle asymétrique du saké : avec du saké en stock, constater le bonus de +15% avec icône saké violette. Vider le saké tout en maintenant la farine : constater que le score revient à 90% sans AUCUN malus pénalisant.
  4. Vider les réserves de farine et de riz : constater la chute du contentement en dessous de 25%, l'apparition du statut « Exode Imminent ! », le clignotement pulsant rouge du cadre et le début de perte d'habitants par cycle.
  5. Consulter une fiche de bâtiment (`/?page=building&slot=...`) ou de parcelle (`/?page=field&slot=...`) : vérifier l'affichage du badge d'ouvriers requis et de l'indicateur d'effectif complet ou sous-effectif.

---

### [2026-10-07] - refonte-terroir-9-parcelles : Refonte Majeure du Domaine Rural & Dorf 1 vers 9 Parcelles Stratégiques Procédurales
- **Module :** `views / resources (Dorf 1 - Domaine Rural Féodal)`
- **Statut :** `Implémenté / Prêt pour QA`
- **Description :** Refonte complète du domaine rural féodal abandonnant la grille héritée de 40 micro-emplacements pour un système resserré, immersif et profond de **9 parcelles uniques** par village, avec génération procédurale/aléatoire par village et progression verticale profonde (Niveaux 20 à 100) :
  - **Modèle de données & Migration BDD :** Création de la table `planet_rural_plots` (`database/migrations/002_create_planet_rural_plots.sql` et `database/migrate_rural_plots.php`) stockant pour chaque parcelle : son type (`tenshu`, `foret`, `carriere`, `fosse_argile`, `riziere`, `champ_soja`, `culture_the`, `sanctuaire_shinto`, `village`), son niveau actuel (min 1), son niveau maximal (`max_level` tiré entre 20 et 100), ses coordonnées relatives `pos_x` et `pos_y` sur la scène 16:9, ses ouvriers affectés et sa cadence de production courante.
  - **Moteur de génération aléatoire & Spawner (`core/VillageGeneratorService.php`) :**
    * Attribution des 9 structures indispensables garantissant l'équilibre économique du joueur.
    * Tirage procédural des positions sur les points d'ancrage topologiques de la carte 16:9 avec micro-variations naturelles (jitter $\pm 0.8\%$).
    * Tirage pondéré du potentiel (`max_level`) entre 20 et 100 différenciant chaque fief (potentiel jackpot de 75 à 100 pour la spécialité majeure du fief, 50 à 75 pour la spécialité secondaire, et 25 à 50 pour les parcelles équilibrées).
    * Auto-migration douce (`convertExistingPlanet`) convertissant les niveaux historiques de bâtiments et de champs sans perte de progression.
  - **Moteur Métier & Formules de progression (`core/RuralPlotEngine.php`) :**
    * Formule de coût exponentielle lissée : $\text{Base} \times (1 + 0.22 \times (L-1)^{1.45}) \times 1.12^{\min(L-1, 40)}$, assurant une montée viable et non buggée du palier 20 jusqu'au palier 100 sans débordement d'entier.
    * Formule de production horaire : $35 \times L^{1.38} \times (1 + L \times 0.015)$.
    * Formule de capacité d'habitation du village : $75 + (L \times 25)$ places, indexant la démographie sur la parcelle `village`.
    * Synchronisation avec `PlanetEngine::calculateMaxPopulation()` et mise à jour dynamique de `population_max`.
  - **Interface Utilisateur & Expérience 16:9 (`views/resources.php` & `public/js/rural_domain_map.js`) :**
    * Affichage plein écran de la nouvelle illustration panoramique (`public/assets/shogun_rural_terroir_9plots.jpg`) en 1920×1080.
    * Moteur de navigation Grab-and-Pan avec clamping strict des bordures (aucun fond noir) et zoom molette/boutons centré.
    * 9 tuiles/badges interactifs modernes Tabler affichant l'icône, le nom, le badge `Niv. [Actuel] / [Max]`, la jauge de progression visuelle du potentiel et le rendement horaire.
    * Modale Tabler d'élévation interactive avec vignette d'illustration, prévisualisation des gains de rendement, tags de coûts avec statut de solvabilité en temps réel et bouton d'action asynchrone AJAX vers `/api/rural_plot.php`.
  - **Tests & Intégrité :** Suite de tests unitaires automatisés validée à 100% (`tests/test_village_generator_9plots.php`).
- **Fichiers modifiés / créés :** `database/migrations/002_create_planet_rural_plots.sql`, `database/migrate_rural_plots.php`, `core/VillageGeneratorService.php`, `core/RuralPlotEngine.php`, `api/rural_plot.php`, `views/resources.php`, `public/js/rural_domain_map.js`, `core/PlanetEngine.php`, `views/partials/grimoire_prompts_data.php`, `tests/test_village_generator_9plots.php`, `fonctionnalités.md`.
- **Vérification QA :**
  1. Accéder à `/?page=resources` : vérifier le chargement de la nouvelle illustration 16:9 haute définition sans bordure noire.
  2. Vérifier la présence des 9 badges interactifs positionnés sur leurs zones respectives (Tenshu, Forêt, Carrière, Fosse d'argile, Rizière, Soja, Thé, Sanctuaire, Village).
  3. Vérifier le format d'affichage du niveau : `Niv. X / Y` avec la barre de progression relative au plafond maximal.
  4. Tester le Grab-and-Pan (glisser à la souris ou au doigt) et le zoom : vérifier la fluidité et le verrouillage strict des bords.
  5. Cliquer sur un badge : vérifier l'ouverture instantanée de la modale d'amélioration avec le calcul exact des coûts, rendements et durée.
  6. Cliquer sur « Élever la structure » avec ressources suffisantes : constater l'élévation au niveau supérieur, la déduction des ressources et le rafraîchissement sans erreur.
  7. Élever le village : vérifier que la capacité maximale de logements (`population_max`) augmente immédiatement de +25 villageois.

---

### [2026-10-02] - village-life-simulator : Simulateur de Vie dans un Village & Bac à Sable Démographique (Studio Dev)
- **Module :** `studio / game-elevate-designer`
- **Statut :** `À tester`
- **Description :** Création du module d'équilibrage et de simulation démographique « Simulateur de Vie dans un Village » (`views/studio/game-elevate-designer/village-life-simulator.php`) pour le métier Game Elevate Designer dans Studio Dev :
  - **Sécurité serveur :** Verrouillage strict de l'accès via le helper `AuthManager::hasJob('game-elevate-designer')` avec renvoi d'une erreur HTTP 403 et écran de verrouillage si l'utilisateur ne possède pas ce métier (avec passe-droit administrateur).
  - **Contrôle temporel accéléré :** Horloge féodale réglable avec facteur d'accélération de x1 à x100 (x1, x5, x10, x25, x50, x100), boutons Play, Pause, Réinitialiser et pas à pas (+1h, +24h).
  - **Paramètres de flux & Presets en 1 clic :** Curseurs interactifs pour la population initiale, la capacité des habitations, les postes de travail ouverts, et les flux horaires entrants de Riz, Farine, Saké et Sérénité passive Shinto. 4 scénarios prédéfinis prêts à tester : « Pénurie critique de riz », « Prospérité sous saké », « Surpopulation sans emplois », « Équilibre parfait ».
  - **Moniteur temps réel & Visualisation :** 4 cartes KPI dynamiques (Satisfaction féodale avec alerte clignotante d'exode `< 25%`, Population/Logements, Main-d'œuvre/Emplois, Stocks du grenier). Graphique interactif multi-courbes en temps réel (ApexCharts avec canvas HD de secours) traçant simultanément la population, le riz, la farine, le saké et le contentement.
  - **Chronique d'événements en direct :** Journal horodaté (Ticker) capturant les naissances, exodes de crise, ruptures de farine et célébrations de saké.
  - **Exportation :** Générateur de rapport d'équilibrage JSON complet téléchargeable en un clic avec séries chronologiques et recommandations pour les constantes de jeu.
- **Fichiers modifiés :** `core/AuthManager.php`, `core/DevTeamEngine.php`, `views/dev_team.php`, `views/studio/game-elevate-designer/village-life-simulator.php`, `fonctionnalités.md`
- **Vérification QA :**
  1. Se connecter avec un compte Game Elevate Designer (ou Administrateur) et accéder à `/?page=dev_team&metier=game-elevate-designer` : constater la présence du nouveau sous-module « Vie du Village (Sandbox) » dans les pills de navigation.
  2. Cliquer sur l'onglet : vérifier l'affichage fluide du layout 2 colonnes avec l'horloge et les 4 cartes KPI initialisées.
  3. Lancer la simulation (Play) à vitesse x10 ou x25 : observer le défilement de l'horloge, l'animation du graphique et les entrées du journal d'événements.
  4. Tester le scénario « Pénurie critique » : constater l'effondrement rapide du stock de farine, la chute du moral sous 25%, le passage au statut « Exode Imminent ! », le clignotement rouge du cadre et les départs de villageois dans le journal.
  5. Tester le scénario « Prospérité sous saké » : constater l'activation du bonus +15% de saké et la croissance démographique continue jusqu'au plafond de logements.
  6. Cliquer sur « Exporter Scénario JSON » : vérifier le téléchargement effectif du fichier `.json` structuré.
  7. Tester la sécurité avec un compte membre ne détenant pas le métier Game Elevate Designer : tenter d'accéder au module et constater le refus d'accès HTTP 403.


