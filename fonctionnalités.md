# 📋 Registre des Fonctionnalités & Recette QA — OpenShogun

> Ce document consigne l'ensemble des fonctionnalités et composants implémentés dans le projet.
> Chaque nouvelle entrée démarre avec le statut initial `À tester` et doit être éprouvée et validée par le profil QA / Testeur.

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
