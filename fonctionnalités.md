# 📋 Registre des Fonctionnalités & Recette QA — OpenShogun

> Ce document consigne l'ensemble des fonctionnalités et composants implémentés dans le projet.
> Chaque nouvelle entrée démarre avec le statut initial `À tester` et doit être éprouvée et validée par le profil QA / Testeur.

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
  5. Tester la modification des constantes de vitesse (avec presets et attribution de 30 XP Forge), le déploiement procédural de nouveaux fiefs (+40 XP), et l'équilibrage/génération des oasis par densité (+35 XP).



