# Journal des Modifications (Changelog) — OpenShogun

Toutes les modifications notables apportées à ce projet sont documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]
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


- **Panneau Mutualisé des Récoltes, Stocks & Oasis Féodales (`views/partials/rural_harvest_resources_panel.php`, `views/resources.php`, `views/city.php`) :**
  * **Composant réutilisable & unifié :** Extraction et enrichissement du box de récoltes sous la forme d'un élément modulaire sur le même modèle que le suivi des chantiers en cours, intégré simultanément sur la vue Terroir (`?page=resources`) et sur la Cité Castrale (`?page=city`).
  * **Ensemble des 8 indicateurs du fief :** Suivi complet des productions horaires, niveaux de développement et jauges de remplissage :
    - *Matières premières & Entrepôts :* Bois de Cèdre (`metal`), Pierre de Taille (`crystal`), Riz Impérial (`deuterium`) avec stocks actuels, capacités maximales d'entrepôts/greniers, barres d'avancement colorées et badges d'alerte en cas de saturation (>90%).
    - *Terroirs spécialisés :* Argile & Céramique (`fosse_argile`), Thé & Matcha (`culture_the`), Fèves de Soja & Miso (`champ_soja`) avec productions horaires et progression verticale des parcelles.
    - *Harmonie & Démographie :* Sérénité globale du fief et apport du Sanctuaire Shintō, ainsi que l'occupation des logements traditionnels (population active / capacité d'accueil des minka).
  * **Protection des oasis sauvages :** Section dédiée aux protectorats d'oasis annexées (statut X/3, coordonnées précises et bonus de production en koku).

### Corrigé (Fixed)
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
