# Journal des Modifications (Changelog) — OpenShogun

Toutes les modifications notables apportées à ce projet sont documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]
- **Harmonisation Visuelle Intégrale & Remplacement des Émojis par Font Awesome 6 :** Remplacement systématique de l'ensemble des émoticônes/emojis Unicode textuels par des icônes vectorielles Font Awesome 6.5.2 (`<i class="fa-solid fa-..."></i>`). Intégration en hébergement local autonome (`public/fontawesome/`) pour un fonctionnement 100% offline sans dépendance aux CDN externes. Harmonisation visuelle et sémantique avec la charte Tabler.io (espacements `me-1`/`me-2`, alignement vertical `align-middle`, colorimétrie contextuelle thématique pour le riz, bois, pierre, farine, saké, koban, rangs et statuts). Adaptation sécurisée des sélecteurs `<select>` et mise à niveau des scripts dynamiques JavaScript (`.innerHTML`) pour l'affichage réactif des boutons et modales.
- **Layout Pleine Largeur Fluide & Calibrage d'Alignement Vertical :** Remplacement des conteneurs bridés ou à largeur fixe (`container-xl`, `container`, `max-width: 1200px/1400px`) par le format fluide `container-fluid px-3 px-lg-4` sur l'ensemble du corps de page (`.page-body > .container-fluid`). Calage rigoureux des gouttières latérales (1rem sur mobile/tablette, 1.5rem sur desktop) pour assurer un alignement géométrique parfait des bordures gauche et droite entre la barre de navigation supérieure, les 6 blocs de ressources féodales et les cartes de contenu central. Déploiement d'une surcharge CSS globale dans `public/css/style.css` neutralisant les conteneurs imbriqués (`max-width: 100% !important; padding: 0 !important;`) tout en préservant l'exception sans marge latérale dédiée à la carte tactique Sengoku (`page === 'map' || page === 'galaxy'`). Harmonisation appliquée sur toutes les vues clés : Studio Dev, Affiche féodale, Classements, Rapports, Recherches, Conseil de guerre (Chat), Tenshu, Codex, Support, Pédagogie et Changelog.
- **Charte Typographique Féodale (« Dela Gothic One ») :** Intégration de la police Google Fonts *Dela Gothic One* via balises d'en-tête HTML (`preconnect`, `stylesheet`) et `@import` CSS. Déclaration des variables `--font-game-title`, `--font-feodal`, `--tblr-font-game` et de la classe utilitaire réutilisable `.font-game` / `.title-feodal`. Application ciblée sur les éléments d'affichage à fort impact visuel (titre de marque « La Voie du Shogun », en-tête immersif et cartes de « Mon affiche », titres des classements, donjons, parcelles et compteurs KPIs) tout en préservant la police sans-serif Inter pour les textes longs et tables de données.
- **Affiche Féodale & Fiche Daimyō (`views/poster.php`) :** Migration de l'ancienne modale vers une page dédiée complète (`?page=poster` et alias `?page=profile`), avec hero header immersif aux armoiries du clan (Mon, bannière féodale, statut de présence et trêve sacrée), section narrative complète (contexte Sengoku/Muromachi/Edo, doctrine martiale, arquebuses Tanegashima, libre marché Rakuichi Rakuza, Fūrinkazan, cavalerie d'élite Akazonae, ninjas d'Iga, bastions imprenables), parchemin de la devise du Daimyō avec édition asynchrone AJAX directe, registre des fiefs provinciaux avec coordonnées géographiques cliquables, KPIs de combat hebdomadaires et panthéon des médailles d'honneur.
- **Navigation & Redirection Globale :** Actualisation du menu « Mon Empire » pour pointer directement vers « Mon Affiche Féodale », et redirection transparente de l'ensemble des boutons de l'application (classements, forum, chat, alliance, cartes) vers la nouvelle page dédiée.
- **Studio Dev & Roster :** Filtrage strict des membres éligibles à l'attribution des métiers (`WHERE is_bot = 0`) afin d'exclure formellement tous les profils d'IA ou bots du studio de développement.
- **Module Rapports (Chroniques Militaires) :** Pagination fluide de 15 entrées par page avec contrôles Tabler.io, bouton d'action pour la suppression unitaire d'un rapport, et bouton global pour purger l'ensemble des chroniques avec confirmation via modale Tabler.io (`api/reports.php`).
- **Module Messages :** Bouton d'action « Supprimer tous les messages » avec modale de confirmation Tabler.io obligatoire et endpoint API dédié supportant le ciblage de boîte (`inbox`, `outbox`, `all`).
- **Layout & Header Global :** Positionnement de la barre de navigation Tabler tout en haut de l'écran en pleine largeur (`container-fluid px-3 px-lg-4`), avec intégration directe des statuts, outils et sélecteur de fiefs.

### Corrigé
- **Studio Dev & Écosystème des Oasis :** Correction d'une erreur fatale PHP (`Call to undefined method OasisEngine::getOasesCoordinatesMap()`) lors du chargement des modules d'arpentage et de gestion des oasis dans `views/dev_team.php`. Remplacement des appels statiques inexistants par l'instanciation de `OasisEngine`, la récupération des statistiques via `getOasisStatistics()` et le requêtage direct des coordonnées sur la table `oases`.

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
