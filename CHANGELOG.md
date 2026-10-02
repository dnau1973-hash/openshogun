# Journal des Modifications (Changelog) — OpenShogun

Toutes les modifications notables apportées à ce projet sont documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]
### Ajouté (Added)
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
