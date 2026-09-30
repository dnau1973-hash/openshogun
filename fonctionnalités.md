# 📋 Registre des Fonctionnalités & Recette QA — OpenShogun

> Ce document consigne l'ensemble des fonctionnalités et composants implémentés dans le projet.
> Chaque nouvelle entrée démarre avec le statut initial `À tester` et doit être éprouvée et validée par le profil QA / Testeur.

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
