# ⛩️ Atelier Pédagogique OpenShogun — Carnet de Bord d'Apprentissage

> **Projet Père-Fils & Développement Web Féodal**  
> Ce document centralise les objectifs d'apprentissage, les notions clés abordées lors des sessions de développement et les pistes d'ateliers interactifs à réaliser ensemble.

---

## 🎯 Objectifs Pédagogiques Globaux
1. **Comprendre le fonctionnement du Web :** Requêtes HTTP (GET/POST), sessions serveur, rendu côté client vs traitement côté serveur.
2. **Architecture & Bonnes Pratiques :** Structuration propre du code en PHP natif sans dépendance externe magique, base de données relationnelle (MySQL/PDO) et requêtes sécurisées.
3. **Game Design & Mécaniques de Jeu :** Équilibrage des ressources, gestion de la boucle de gameplay (game loop), clans IA, rôles d'équipe (Dev Team) et gamification.
4. **Sécurité Informatique :** Hachage des mots de passe, protection contre les injections SQL, tokens d'activation uniques avec expiration temporelle.

---

## 🧭 Notions Clés Abordées par Thématique

### 1. Programmation Backend & Base de Données
- **PHP 8.2+ Natif :** Programmation orientée objet, pattern Singleton (`MailService`), validation de formulaires, sessions administrateur.
- **PDO & Sécurité :** Requêtes préparées (`prepare()` / `execute()`) pour immuniser le jeu contre les injections SQL.
- **Routage par URL :** Paramètres `page`, `tab`, `action`, compréhension des variables globales `$_GET` et `$_POST`.

### 2. Réseau, Protocoles & Sécurité
- **Sockets & Protocole SMTP (RFC 5321) :** Comprendre comment deux serveurs se parlent en direct (codes de réponse `220`, `250`, poignée de main `EHLO`, négociation `STARTTLS`).
- **Cryptographie appliquée :** Jeton de confirmation unique (`random_bytes`), empreinte SHA-256 et chiffrement symétrique AES-256-CBC pour les mots de passe de serveurs.
- **Validation du Mot de Passe :** Algorithme de calcul de force (entropie, regex de complexité) et synchronisation en temps réel.

### 3. Frontend & Ergonomie (UI / UX)
- **JavaScript Vanilla :** Événements DOM (`input`, `change`, `click`), manipulation dynamique des classes CSS Tabler.io, zéro dépendance externe.
- **Templates d'E-mails Responsive :** Conception en HTML tabulaire rétrocompatible, intégration graphique dans un univers féodal sombre.

### 4. Game Design & Métiers du Jeu Vidéo (Dev Team)
- **Matrice des Rôles :** Game Designer, Développeur Moteur/Rendu, QA Tester, Scénariste, Producteur.
- **Gamification :** Attribution de compétences thématiques et buffing en jeu selon le profil de contributeur.

---

## 📜 Historique des Évolutions Pédagogiques

### Session du 08/10/2026 (Partie 22) — Vue Holistique de Production & Ergonomie d'un Tableau de Bord Stratégique
- **Concept exploré :** Conception d'un widget de tableau de bord synthétique multi-dimensionnel (« cockpit de gestion »), agrégation de données complexes (flux bruts, vivres de transformation, énergie/ferveur motrice, quotas de main-d'œuvre et modificateurs croisés) et chargement paresseux d'objets métier (Lazy Initialization Pattern).
- **Notions pour l'atelier :**
  - **Le Cockpit Stratégique (Vue Holistique vs Vues Encastrées) :** Dans un jeu de stratégie complexe, les informations de production sont souvent éparpillées : un peu dans la forêt, un peu dans la meunerie, un peu dans le temple shinto, un peu chez le forgeron. Le rôle d'un bon designer d'interface est d'offrir un panneau latéral unifié capable de synthétiser **l'ensemble de la chaîne de valeur** en un coup d'œil, sans forcer le joueur à cliquer sur dix pages différentes pour savoir pourquoi son fief produit moins de riz.
  - **La Décomposition Mathématique Visible (Le Ticket de Caisse Pédagogique) :** Plutôt que d'afficher uniquement le résultat final d'une formule (ex: `+265 Bois/h`), afficher des micro-puces listant les contributions (`Base +100`, `Forêt Niv.2 +45`, `Scierie +5%`, `Héros +120`). L'utilisateur comprend immédiatement quelle décision a augmenté sa production et où porter son prochain investissement.
  - **Le Goulot d'Étranglement Énergétique et Humain :** Comprendre comment l'énergie (ferveur divine des Kamis) et la main-d'œuvre (villageois Minka) agissent comme des « fusibles ». Si l'énergie est déficitaire, le rendement chute brutalement à 10%. Le widget doit immédiatement avertir le joueur avec une couleur contrastée pour guider son action corrective (construire un sanctuaire).
  - **L'Optimisation par Initialisation Paresseuse (Lazy Loading) :** Si la page appelante a déjà préparé les données (`$plots`, `$prodRates`), le composant partiel ne doit pas réinterroger la base de données ni recréer des moteurs lourds. En vérifiant `if (!isset(...))`, on économise des requêtes SQL et on accélère l'affichage à quelques millisecondes.
- **Activité pratique suggérée :** Ouvrir le domaine rural (`/?page=resources`) et inspecter la colonne de droite. Observer la décomposition sous le Bois, la Pierre et le Riz. Consulter l'état des réserves de Farine et de Saké. Observer la jauge de Ferveur et de Main-d'œuvre, puis vérifier comment le taux de rendement global réagit lorsqu'un chantier urbain est lancé.

---

### Session du 08/10/2026 (Partie 21) — Bénédiction de Récolte du Samouraï Héros & Chaîne de Production Féodale
- **Concept exploré :** Comprendre comment plusieurs briques logicielles indépendantes (moteur de héros, moteur de domaine rural, tick de ressources et rendu visuel côté client) doivent s'articuler sans rupture logique. Découverte de la notion de « découplage » vs « synchronisation des flux ».
- **Notions pour l'atelier :**
  - **Pourquoi une valeur semble-t-elle disparaître ? (Le Mystère du Statut Temporaire) :** Dans un jeu vidéo, une entité (ici le Samouraï) change d'état : il est « au domaine » (`home`), « en aventure » (`adventure`), ou « en marche » (`mission`). Si une formule de calcul mathématique dit naïvement « *ajoute le bonus seulement si le héros est dans sa chambre (`status === 'home'`)* », alors dès qu'il part chasser 30 minutes, le village perd tout son bonus ! C'est ce qu'on appelle un effet de bord indésirable. En modifiant la règle pour « *le bonus est actif tant que le héros respire (`health > 0` et `status !== 'dead'`)* », le jeu respecte la logique du joueur sans mauvaise surprise.
  - **La Clé Étrangère Orpheline (Le Gardien sans Fief) :** Si la base de données relie le héros à un domaine via `current_planet_id`, que se passe-t-il si cette valeur est vide ou pointe vers un ancien identifiant ? Le code ne trouvait personne ! Pour rendre un moteur robuste, on recherche toujours le propriétaire du domaine en premier, puis on recalibre automatiquement le lien du héros s'il était égaré.
  - **La Transparence de l'UI (Le Principe du Ticket de Caisse) :** Si un joueur lit `+155 Bois/h`, il ne sait pas ce qui compose ce chiffre (base, forêt, héros, scierie ?). En affichant un badge explicite `+X Héros` et un récapitulatif détaillé, on applique le principe d'explicabilité : le joueur comprend instantanément le résultat de ses investissements.
- **Activité pratique suggérée :** Attribuer des points dans la Bénédiction de Récolte du Samouraï, observer la mise à jour réactive du libellé, puis aller sur la page Ressources pour constater l'apparition du badge violet `+X Héros` sur le panneau latéral et dans les modales des parcelles. Lancer une aventure et vérifier que la production horaire ne s'effondre plus !

---

### Session du 02/10/2026 (Partie 16) — Télémétrie, Respect de la Vie Privée (RGPD) & Visualisation de Données (ApexCharts)
- **Concept exploré :** Architecture d'un système de télémétrie et d'analyse d'audience pour un jeu en ligne, anonymisation des données personnelles (RGPD), génération de séries temporelles continues en SQL/PHP pour éviter les ruptures de courbes, et intégration d'une bibliothèque graphique réactive moderne (ApexCharts).
- **Notions pour l'atelier :**
  - **Qu'est-ce que la Télémétrie ? (Le Registre des Pèlerins de Kyoto) :** Pour savoir si un jeu plaît et quelles fonctionnalités sont réellement utilisées (les casernes, l'atelier de forge, la carte ou les simulateurs), les créateurs de jeux placent des « balises discrètes » qui notent les visites. C'est l'équivalent du garde à la porte du temple qui compte le nombre de pèlerins sans leur demander leur nom intime ni violer leur secret.
  - **L'Anonymisation & Le Respect de la Vie Privée (Le Masque de Cire Haché) :** Une adresse IP est une donnée personnelle protégée par la loi (RGPD). Pour protéger les joueurs, on ne stocke jamais leur véritable adresse IP en clair. On utilise une fonction de hachage cryptographique (`hash('sha256', ...)`) combinée à un « sel » secret qui change chaque mois. Cela transforme l'IP en une chaîne de caractères indéchiffrable unique : on peut savoir si le même visiteur revient plusieurs fois dans la journée, mais personne (même pas l'administrateur de la base de données) ne peut retrouver l'identité réelle ou la localisation du joueur !
  - **Pourquoi une Courbe Graphique se « casse » ? (Le Piège des Jours Vides en SQL) :** Si une requête SQL fait un `GROUP BY DATE(created_at)`, elle ne renvoie que les jours où il y a eu au moins 1 visite. S'il n'y a eu aucune inscription le mardi, le mardi disparaît complètement du résultat ! Pour tracer une belle courbe continue sans trous ni sauts temporels, le serveur génère d'abord une « ligne du temps continue » de 30 jours remplie de 0, puis vient y insérer les vraies données.
  - **Le Bug du Canvas à Largeur Nulle (`rect.width = 0`) :** Lorsqu'un graphique en HTML5 Canvas est initialisé dans un onglet masqué (`display: none`), le navigateur ne peut pas calculer sa largeur car l'élément n'a aucune dimension physique à l'écran. Lors de l'ouverture de l'onglet, le canvas reste bloqué à 0 pixel ! Avec une bibliothèque moderne comme ApexCharts (ou en écoutant l'événement de bascule d'onglet avec un léger délai d'attente), le graphique redessine automatiquement ses proportions à la perfection.
- **Activité pratique suggérée :** Ouvrir le panneau d'administration sur le Dashboard, observer les 4 KPI cards et les 3 graphiques interactifs. Survoler les courbes du graphique principal pour voir la bulle d'information s'afficher avec les 3 métriques (Vues, Actifs, Inscriptions), puis tester les boutons de filtrage temporel (Aujourd'hui, 7 jours, 30 jours, Tout) et le champ de recherche instantané du tableau des Daimyōs les plus actifs.

---

### Session du 02/10/2026 (Partie 15) — Ergonomie des Micro-Interactions & Barre d'Actions Rapides
- **Concept exploré :** Conception d'actions d'accès rapide dans une barre secondaire (sous-barre compacte alignée à droite), épuration visuelle par l'usage exclusif d'icônes vectorielles avec infobulles contextuelles Bootstrap/Tabler (`Tooltip API`), et gestion des permissions d'affichage côté serveur.
- **Notions pour l'atelier :**
  - **Sobriété de l'Interface & Ratio Signal/Bruit :** Pourquoi ne pas écrire du texte sur tous les boutons d'une sous-barre ? Dans un tableau de bord de jeu de stratégie, la surcharge d'informations fatigue l'œil. L'usage d'une icône explicite (`fa-chart-line` pour les métriques, `fa-gear` pour l'administration) combinée à un badge compact coloré offre un repère visuel immédiat sans encombrer la largeur de l'écran.
  - **Accessibilité & Infobulles Asynchrones (`bootstrap.Tooltip`) :** L'icône seule ne doit jamais laisser l'utilisateur dans l'ignorance. L'attribut `data-bs-toggle="tooltip"` couplé à un `title` descriptif permet au moteur Tabler d'afficher une infobulle flottante élégante dès le survol, garantissant un apprentissage sans effort pour les nouveaux joueurs et jeunes concepteurs.
- **Activité pratique suggérée :** Se connecter avec le compte administrateur, observer l'alignement des icônes dans la sous-barre supérieure, survoler le nouveau badge sarcelle pour vérifier l'apparition de l'infobulle « Statistiques d'utilisation & Métriques du Shōgunat », puis cliquer pour vérifier la bascule instantanée sur l'onglet dashboard d'administration.

---

### Session du 02/10/2026 (Partie 14) — Vulgarisation Pédagogique Backend (POO, Singleton, Injections SQL & Routage)
- **Concept exploré :** Conception d'un module d'apprentissage interactif complet (« L'École du Backend ») dans l'Atelier Pédagogique, dédié aux jeunes débutants (dès 12 ans), avec vulgarisation par métaphores concrètes du quotidien et du Japon féodal, encarts culturels « Le savais-tu ? » et mini-quiz interactifs client-side.
- **Notions pour l'atelier :**
  - **La Programmation Orientée Objet (POO) à hauteur d'enfant :** Comment expliquer une Classe et un Objet sans jargon abstrait ? Une **Classe** est le plan d'architecte du maître forgeron (Muramasa) ou un moule à gâteau en silicone : elle ne coupe rien et ne se mange pas, mais définit la forme exacte de ce qu'on va créer. L'**Objet** (l'instance créée avec `new Katana()`) est le vrai sabre en acier forgé ou le gâteau qui sort du four. À partir d'un seul plan, on peut forger des dizaines de sabres différents avec chacun leur nom, leur tranchant et leur propriétaire.
  - **Le Design Pattern Singleton (Le Facteur Impérial Unique) :** Pourquoi ce patron de conception est-il indispensable pour certains services comme `MailService` ? Dans un château féodal, si chaque samouraï ou tavernier embauchait un nouveau chef de la poste et construisait une nouvelle écurie à chaque lettre envoyée, le château s'effondrerait sous le désordre. Le Singleton (`MailService::getInstance()`) garantit qu'il n'existe qu'un seul et unique facteur impérial dans tout le royaume pour gérer les missives sans encombrer la mémoire du serveur.
  - **Les Sessions HTTP (Le Sceau de Cire sur la Main) :** Le protocole web HTTP n'a aucune mémoire : dès qu'une page est chargée, il oublie qui tu es ! Pour éviter de ressaisir son mot de passe à chaque clic sur le jeu, le garde à l'entrée du château tamponne sur la main du joueur un **sceau de cire impérial avec un numéro secret** (`$_SESSION` / `PHPSESSID`). Tant que le sceau est présent, les sentinelles reconnaissent le seigneur du fief et le laissent entrer librement.
  - **PDO & La Protection contre les Injections SQL (Le Coffre-Fort et la Boîte Blindée) :** La base de données MySQL est le coffre-fort des trésors du Shogun. Si l'archiviste lit naïvement un parchemin piégé (`' OR '1'='1`), il ouvre le coffre aux bandits. La solution magique de PDO est la **requête préparée** (`prepare` / `execute`) : une boîte aux lettres à fente blindée qui sépare strictement l'ordre (l'étagère où chercher) de la donnée fournie par le joueur. Même si un joueur tape du code malveillant, il est traité comme du simple texte inoffensif.
  - **Le Routage et les Messagers HTTP (Panneaux de Kyoto, Cartes Postales et Ninjas) :** L'URL du site est comme les panneaux indicateurs dans les ruelles de la capitale impériale de Kyoto. Le fichier `index.php` est le guide (le Routeur) qui regarde le panneau `?page=...` pour charger la bonne vue. Le messager `$_GET` est une **carte postale transparente** que tout le monde peut lire en passant dans la rue (pratique pour partager des coordonnées, mais dangereux pour un mot de passe). Le messager `$_POST` est une **missive secrète scellée** confiée à un ninja (les données sont cachées dans l'enveloppe de la requête, invisibles dans l'URL).
- **Activité pratique suggérée :** Ouvrir l'Atelier Pédagogique (`/?page=pedagogy`), descendre à la Section 5 « L'École du Backend », parcourir successivement les 3 cours (La Forge POO, Le Coffre-Fort PDO, Les Panneaux de Kyoto) et tester les mini-quiz interactifs en tentant d'obtenir le score parfait de 2/2 sur chacun d'eux pour décrocher les trophées de développeur samouraï !

---

### Session du 02/10/2026 (Partie 13) — Dégâts de Siège, Dégradation des Bâtiments Urbains & Robustesse Client
- **Concept exploré :** Mécanique de siège féodale avancée avec sape des infrastructures et dégradation en cascade (de la muraille vers les édifices urbains du village), et robustesse du code JavaScript client face aux erreurs asynchrones et données partielles (défense en profondeur, élimination des crashes `TypeError`, suppression des dialogues bloquants natifs au profit de composants graphiques Tabler.io).
- **Notions pour l'atelier :**
  - **Game Design & Balistique Médiévale (La Mécanique de Sape) :** Dans un jeu de stratégie, la défense d'un fief s'organise en couches concentriques. La muraille est la première barrière : elle protège les bâtiments et offre un multiplicateur défensif à la garnison. Tant qu'elle est debout, les édifices intérieurs sont à l'abri. Dès que les béliers et catapultes perforent les remparts (brèche totale / niveau 0), la force de sape résiduelle frappe directement le tissu urbain selon un ordre de priorité militaire (Tenshu/Donjon en premier pour briser le commandement, dojo/caserne pour neutraliser les renforts, entrepôts pour exposer les réserves). Les bâtiments perdent des niveaux et peuvent être anéantis (niveau 0 / ruine à reconstruire).
  - **Programmation & Robustesse : Qu'est-ce qu'un `TypeError` et comment immuniser son code ? :** Lorsque le code tente d'accéder à `data.local.short_sha`, si l'API a retourné une réponse partielle ou une erreur réseau où `data.local` n'existe pas, le navigateur plante immédiatement avec `TypeError: Cannot read properties of undefined (reading 'short_sha')`. En appliquant une programmation défensive systématique (vérification de la présence de l'objet, opérateur de coalescence ou chaînage optionnel, valeurs de repli sûres), le code reste stable et informe proprement l'utilisateur au lieu de geler silencieusement.
  - **UX & Ergonomie : Pourquoi bannir `confirm()` et `alert()` sur le Web moderne ? :** Les boîtes de dialogue natives du navigateur interrompent le fil d'exécution JavaScript, brisent l'immersion féodale avec une interface grise du système d'exploitation, et sont de plus en plus bridées par les navigateurs modernes. En les remplaçant par des modales interactives Tabler.io (`#modal-confirm-update-deploy`) et des toasts non intrusifs, l'expérience reste fluide, élégante et parfaitement intégrée au thème du jeu.
- **Activité pratique suggérée :** Dans le Simulateur de Combat de Studio Dev (`/?page=dev_team&metier=game-elevate-designer&module=combat-simulator`), charger le préréglage « Forteresse Impériale » et lancer la simulation pour constater la résistance des remparts. Puis charger « Raid Village Ouvert (Mur 0) » avec des engins de siège : observer comment les dégâts résiduels s'abattent directement sur le Tenshu et le Dojo, réduisant leurs niveaux et affichant les badges rouges « Détruit (Niv 0) ».

---

### Session du 02/10/2026 (Partie 12) — Simulateur Tactique de Combat Féodal (Rôle de la Muraille) & Découpage Modulaire par Métiers
- **Concept exploré :** Conception d'un simulateur de combat instantané en split-screen (Attaquant vs Défenseur), modélisation mathématique du rôle défensif des murailles (points de vie de structure, absorption, bonus multiplicateur de faction, riposte de meurtrières, dégradation par engins de siège), et refactorisation architecturale d'un studio de développement en sous-dossiers modulaires isolés par métier.
- **Notions pour l'atelier :**
  - **Mathématiques du Combat & Rôle des Fortifications (Game Design) :** Comment modéliser l'impact d'une muraille dans un jeu de stratégie ? Une fortification n'est pas qu'un simple boost statistique : elle possède des Points de Vie (PV) structurels (ex. 250 PV par niveau) qui absorbent les tirs, un coefficient multiplicateur de défense pour la garnison (+4% Oda, +3.5% Takeda, +5% Tokugawa) et des tirs de riposte automatiques (meurtrières). Les engins de siège (béliers et catapultes) attaquent en priorité la muraille au premier tour pour créer une brèche avant la mêlée générale.
  - **Simulation Déterministe vs Multi-Rounds :** Comment calculer l'issue d'une bataille complexe sans latence ? En divisant l'affrontement en 3 phases tactiques (phase de siège préliminaire, phase d'échanges à distance/archerie, phase de mêlée générale au corps-à-corps) et en calculant les pertes relatives au prorata des puissances effectives restantes.
  - **Architecture Modulaire par Métier en PHP :** Pourquoi éviter un fichier monolithique de 2000 lignes ? En découpant les vues en sous-répertoires dédiés (`views/studio/<slug_metier>/<module>.php`), chaque métier dispose de son propre atelier isolé. Le contrôleur central (`views/dev_team.php`) se contente d'inclure dynamiquement le module requis via une matrice de configuration sécurisée, évitant les collisions de code et facilitant la maintenance en équipe.
- **Activité pratique suggérée :** Ouvrir le « Simulateur de Combat » dans Studio Dev, charger le préréglage « Assaut d'un bourg fortifié », faire varier le slider de muraille du niveau 0 au niveau 15, lancer la simulation et observer comment la muraille absorbe les pertes de la garnison et inflige des dégâts aux troupes attaquantes.

---

### Session du 01/10/2026 (Partie 11) — Ergonomie de Navigation, Sous-barres Contextuelles & Tiroir Offcanvas d'Insertion WYSIWYG
- **Concept exploré :** Décharge cognitive de la barre de navigation principale (séparation en deux niveaux hiérarchiques), conception de micro-badges à icône seule assistés de tooltips interactifs (Bootstrap/Tabler), et création d'un tiroir latéral coulissant (*Offcanvas*) pour la recherche, la sélection multiple et l'injection dynamique de contenu riche dans un éditeur WYSIWYG.
- **Notions pour l'atelier :**
  - **Lois de Miller et Hick en UX Design (Pourquoi diviser une barre de navigation ?) :** La barre de navigation principale accumulait trop d'éléments hétérogènes (liens de jeu, devises, sceaux, boutons d'outils, son). En déportant les utilitaires système et monétaires sur une sous-barre dédiée immédiatement en-dessous, on allège la navigation principale tout en conservant un accès permanent en un clic.
  - **Micro-badges à icône seule & Infobulles (*Tooltips*) :** Afficher du texte à côté de chaque icône encombre l'écran et force l'œil à lire de longs libellés. En affichant uniquement une icône vectorielle bien reconnaissable complétée par un tooltip déclenché au survol de la souris (`data-bs-toggle="tooltip"` / `title`), on optimise l'espace sur l'écran tout en conservant une accessibilité parfaite pour les joueurs débutants.
  - **Composant Offcanvas vs Modale :** Pourquoi utiliser un tiroir coulissant latéral (*Offcanvas*) pour insérer les nouveautés du jeu plutôt qu'une modale ? La modale bloque complètement l'écran et masque le document en cours de rédaction. L'Offcanvas glisse sur le côté droit sans recouvrir la totalité de la vue, permettant au rédacteur de continuer à voir son texte et son aperçu en direct pendant qu'il sélectionne ses nouveautés.
  - **Injection HTML dynamique dans un éditeur WYSIWYG (Quill API) :** Comment insérer du contenu sans écraser le travail de l'utilisateur ? En interrogeant le curseur avec `quill.getSelection()`, puis en injectant le HTML précisément à la position active avec `quill.clipboard.dangerouslyPasteHTML(index, html)`.
- **Activité pratique suggérée :** Survoler les badges de la sous-barre sous le header pour observer le déclenchement des tooltips, puis ouvrir l'atelier de missive (`/?page=newsletter_compose`), cliquer sur « Insérer des Nouveautés », cocher deux fonctionnalités dans le tiroir latéral et observer leur insertion directe dans le corps du texte à l'emplacement exact de votre curseur.

---

### Session du 01/10/2026 (Partie 10) — Architecture d'E-Mailing Moderne : Split-Screen Temps Réel, WYSIWYG & Gabarits HTML Compatibles
- **Concept exploré :** Conception d'un atelier d'e-mailing complet (transition de modale vers page dédiée), intégration d'un éditeur riche WYSIWYG (Quill), isolation CSS via `iframe` (`srcdoc`) pour la prévisualisation instantanée sans latence, et règles strictes de délivrabilité d'e-mails (HTML tabulaire `<table>`, CSS inline, compatibilité Outlook/Gmail/Apple Mail, zéro fond sombre).
- **Notions pour l'atelier :**
  - **Pourquoi le HTML d'un e-mail est-il si différent du HTML d'un site web ? :** Sur le web moderne, on utilise CSS Grid, Flexbox et des variables CSS. Mais dans les clients de messagerie (en particulier Microsoft Outlook basé sur le moteur de rendu Word !), ces technologies modernes ne fonctionnent pas. Pour qu'un e-mail s'affiche de manière identique sur 100% des boîtes de réception, on utilise des tableaux imbriqués (`<table role="presentation">`), des largeurs maximales fixes (`max-width: 640px`) et tout le style doit être écrit en CSS inline sur chaque cellule `<td>`.
  - **L'isolation de style par l'Iframe (`srcdoc`) :** Pourquoi afficher l'aperçu dans une `<iframe>` plutôt que dans une simple `<div>` ? Si l'aperçu était dans une `<div>`, les règles CSS de Tabler.io viendraient écraser et modifier l'apparence de l'e-mail. L'iframe crée un bac à sable (sandbox) totalement hermétique : le rendu affiché dans l'outil d'administration est rigoureusement identique à ce que verra le joueur dans Gmail.
  - **Le pattern du Split-Screen & Debounce JavaScript :** Comment permettre une frappe fluide sans lag ? Chaque frappe dans l'éditeur WYSIWYG déclenche un minuteur d'attente (debounce de 120ms). L'aperçu ne se recalcule que lorsque le rédacteur marque une micro-pause, évitant ainsi des centaines de recompilations inutiles par seconde.
  - **Sécurité & Assainissement du WYSIWYG :** Pourquoi ne jamais faire confiance au HTML saisi par un utilisateur, même un administrateur ? Fonction `sanitizeEmailHtml()` pour filtrer les balises dangereuses (`<script>`, `<iframe>`) et bloquer tout attribut JavaScript d'événement (`onclick=...`).
- **Activité pratique suggérée :** Ouvrir la page de composition de missive (`/?page=newsletter_compose`), choisir un modèle féodal, basculer entre la vue Bureau (640px) et Mobile (380px), taper du texte stylisé dans Quill et observer la synchronisation instantanée dans l'iframe d'aperçu.

---

### Session du 01/10/2026 (Partie 9) — Iconographie Vectorielle (Font Awesome 6), Standardisation Visuelle & Rendu Offline-First
- **Concept exploré :** Remplacement des émoticônes Unicode (hétérogénéité d'affichage selon les OS et navigateurs) par une bibliothèque vectorielle professionnelle (Font Awesome 6), hébergement local (*offline-first / self-hosted*), alignement vertical (`align-middle`, espacements Tabler/Bootstrap) et manipulation sécurisée du DOM en JavaScript (`innerHTML` vs `textContent`).
- **Notions pour l'atelier :**
  - **Pourquoi remplacer les emojis Unicode par des icônes vectorielles ? :** Un emoji 🌾 ou ⚔️ est rendu différemment selon le système d'exploitation du joueur (Apple, Google Android, Windows, Linux). Les styles visuels ne sont pas harmonisés, les couleurs ne respectent pas la charte graphique et certains emojis récents ne s'affichent pas sur les anciens OS (le fameux "carré blanc" ou *tofu*). Les icônes vectorielles Font Awesome (`.fa-solid`, `.fa-brands`) garantissent un rendu identique au pixel près sur tous les écrans du monde.
  - **Offline-First vs CDN externe :** Pourquoi héberger Font Awesome directement dans `public/fontawesome/` ? Dépendre d'un CDN externe (ex. `cdnjs` ou `fontawesome.com`) pose des risques : coupure réseau, blocage pare-feu/adblocker, temps de latence DNS ou disparition de l'asset. En local, le jeu fonctionne même hors ligne ou en environnement souverain/intranet.
  - **Alignement & Hiérarchie Couleur en CSS :** L'importance des classes d'espacement (`me-1`, `me-2`) et d'alignement (`align-middle`) pour que le symbole ne vienne pas écraser ou désaligner le texte. L'utilisation des classes de couleur contextuelle (`text-warning` pour le riz et l'or, `text-danger` pour le saké et les attaques, `text-success` pour le bois et la défense) apporte une lecture immédiate et intuitive.
  - **Le piège JavaScript du DOM (`textContent` vs `innerHTML`) :** Si un script met à jour un bouton en faisant `btn.textContent = '<i class="fa-solid fa-check"></i> Enregistrer'`, le navigateur affiche littéralement la balise HTML en texte brut au lieu de dessiner l'icône ! Il faut utiliser `.innerHTML` dès lors qu'un fragment HTML est injecté dynamiquement.
- **Activité pratique suggérée :** Ouvrir la barre des ressources féodales ou la caserne, inspecter une icône de troupe ou de ressource avec l'inspecteur d'éléments (F12), modifier sa couleur ou sa taille (`fa-lg`, `fa-2x`, `text-info`) pour comprendre la flexibilité d'une police d'icônes vectorielle par rapport à une image bitmap PNG.

---

### Session du 30/09/2026 (Partie 8) — Direction Artistique Web, Web Fonts & Hiérarchie Typographique (Dela Gothic One)
- **Concept exploré :** Intégration de polices web tierces (Google Fonts), optimisation du chargement réseau (`preconnect`, `display=swap`), hiérarchie visuelle (polices d'affichage à fort impact vs polices de lecture pour les données) et variables CSS réutilisables.
- **Notions pour l'atelier :**
  - **Qu'est-ce qu'une Web Font et comment voyage-t-elle ? :** Comprendre qu'une police personnalisée n'est pas installée par défaut sur la machine du joueur. Le navigateur doit la télécharger via un CDN (Google Fonts). D'où l'importance de `<link rel="preconnect">` qui prépare la négociation TLS/DNS avant même le téléchargement du fichier de police.
  - **La règle d'or UX de la typographie (Impact vs Lisibilité) :** Pourquoi ne JAMAIS utiliser une police stylisée / lourde comme *Dela Gothic One* pour du texte de paragraphe ou des tableaux ! Une police d'affichage (*display font*) sert d'accroche visuelle pour les logos, bannières et titres majeurs. Pour le contenu informatif (statistiques, textes de lore, formulaires), une police sans-serif sobre (comme *Inter*) garantit un confort de lecture optimal.
  - **CSS Custom Properties & Classes Utilitaires :** Comprendre l'intérêt de déclarer `--font-game-title: 'Dela Gothic One', sans-serif;` dans `:root` et d'encapsuler cette règle dans une classe `.font-game`. Si demain la direction artistique décide de tester une autre police d'inspiration calligraphique ou bushido, une seule ligne de CSS suffit pour transformer tout le jeu.
- **Activité pratique suggérée :** Ouvrir l'inspecteur d'éléments (F12) sur le logo « La Voie du Shogun » ou sur l'en-tête de « Mon Affiche Féodale », observer la classe `.font-game` et la variable `--font-game-title`, puis tester temporairement d'autres valeurs de `letter-spacing` pour apprécier l'effet de souffle visuel d'une police gothique japonaise.

### Session du 30/09/2026 (Partie 7) — Refonte Narrative, Transition UX Modale vers Page Dédiée & Worldbuilding Féodal
- **Concept exploré :** Évolution ergonomique d'une fenêtre modale éphémère vers une page complète dédiée (`views/poster.php`), narration historique et worldbuilding (ancrage des factions dans l'histoire des époques Sengoku, Muromachi et Edo), édition in-place de devise par requête asynchrone AJAX (`fetch`) et architecture de routage avec alias.
- **Notions pour l'atelier :**
  - **Modale vs Page Dédiée (Quand franchir le pas ?) :** Comprendre qu'une fenêtre modale est idéale pour une action éphémère (confirmation, saisie rapide). Dès lors que le contenu s'étoffe (armoiries, chroniques narratives, tableau des fiefs, distinctions de guerre, KPIs hebdomadaires), la modale devient étriquée. Une page dédiée offre l'espace nécessaire pour une expérience immersive, partageable par URL (`?page=poster&id=X`) et respectant l'historique du navigateur.
  - **Worldbuilding & Narration liée au Gameplay :** Découvrir comment lier l'histoire réelle du Japon féodal aux mécaniques de jeu. Comment les équilibrages de faction (double développement des Oda, furie de la cavalerie rouge des Takeda, forteresse et patience des Tokugawa) s'enracinent dans la réalité historique (Tanegashima, Fūrinkazan, Sekigahara, réseaux d'espionnage d'Iga).
  - **Édition « In-Place » avec AJAX & Feedback Utilisateur :** Comprendre comment modifier une donnée (la devise du Daimyō) directement sur la page sans recharger l'écran : bascule dynamique formulaire/affichage, appel `fetch` vers l'API REST `/api/profile.php`, mise à jour du nœud DOM en cas de succès et affichage d'un toast Tabler.io non bloquant.
  - **Routage avec Alias d'URL :** Comprendre le fonctionnement d'un routeur PHP (`index.php`) qui intercepte un paramètre GET (`page=profile` vers `poster`) pour garantir la rétrocompatibilité des liens et offrir une navigation fluide depuis n'importe quel point du jeu (classement, chat, alliance, cartes).
- **Activité pratique suggérée :** Ouvrir « Mon Affiche Féodale » depuis le menu « Mon Empire », découvrir les armoiries et les 4 piliers historiques de son clan, modifier sa devise personnelle avec enregistrement instantané, puis aller sur la page du classement pour consulter l'affiche féodale d'un seigneur rival et tester les boutons d'actions diplomatiques (missive, carte, expédition).

### Session du 30/09/2026 (Partie 6) — Ergonomie Fluide, Pagination SQL & Cycle de Vie des Données (Purge & Sécurité)
- **Concept exploré :** Conception d'interfaces complètes pleine largeur (`container-fluid`), pagination de données volumineuses en base (SQL `LIMIT` & `OFFSET`), cycle de vie et suppression sécurisée d'historiques (soft-delete vs hard-delete) et séparation stricte joueurs réels vs bots.
- **Notions pour l'atelier :**
  - **Pagination SQL & Économie de bande passante :** Pourquoi ne jamais charger des milliers d'enregistrements d'un coup dans le navigateur. Comprendre comment le calcul des pages (`ceil(total / perPage)`), le saut d'enregistrements (`OFFSET`) et la restriction de taille (`LIMIT`) rendent une interface instantanée même avec des années d'archives.
  - **Confirmation Modale vs Dialogues Bloquants :** Découvrir pourquoi les alertes JavaScript natives (`confirm()`, `alert()`) bloquent tout le navigateur et sont à proscrire au profit de modales stylisées intégrées au DOM (Tabler.io), avec promesses asynchrones (`await showModalConfirm(...)`).
  - **Stratégie de suppression (Soft-delete vs Hard-delete) :** Pourquoi un message entre deux joueurs ne doit pas disparaître pour le destinataire si l'expéditeur vide sa boîte d'envoi. Comprendre comment combiner des drapeaux booléens (`deleted_by_sender`, `deleted_by_receiver`) avec une suppression définitive uniquement lorsque les deux partis ont confirmé la purge.
  - **Layout & Ergonomie « Full-Width » :** Comprendre la différence entre un conteneur rigide centré (`container-xl`) et une interface fluide d'application web (`container-fluid`), et comment placer une barre de navigation tout en haut en mode sticky (`sticky-top`) pour maximiser l'espace utile de jeu.
- **Activité pratique suggérée :** Se rendre sur la page des rapports de combat, tester la pagination d'une page à l'autre, supprimer un rapport spécifique via la modale de confirmation, puis se rendre sur les missives pour tester le vidage de la boîte de réception.

### Session du 30/09/2026 (Partie 5) — Débogage & Robustesse Objet : Méthodes Statiques vs Instances de Moteur
- **Concept exploré :** Débogage d'une erreur fatale PHP (`Call to undefined method`), distinction fondamentale en programmation orientée objet entre méthode statique (`Class::method()`) et méthode d'instance (`$obj->method()`), et cycle de vie des données d'un monde de jeu.
- **Notions pour l'atelier :**
  - **Analyse d'une trace d'erreur (Stack Trace) :** Apprendre à lire une erreur fatale (`Fatal error: Uncaught Error...`) en repérant immédiatement la classe fautive, la méthode manquante et le numéro de ligne dans le fichier source.
  - **Méthode Statique vs Méthode d'Instance :** Pourquoi certaines classes peuvent s'appeler directement sans instanciation (`DevTeamEngine::hasRole()`) alors que les moteurs manipulant un état interne ou une connexion de base (`OasisEngine`, `$oasisEngine = new OasisEngine()`) doivent être créés et instanciés avant d'être interrogés.
  - **Intégrité des requêtes cartographiques :** Comprendre comment indexer les coordonnées de tuiles dans un tableau associatif (`$coords["$x:$y"] = true`) pour effectuer des recherches de présence instantanées en temps constant O(1) plutôt que de parcourir des listes en boucle imbriquée.
- **Activité pratique suggérée :** Dans l'onglet « Écosystème des Oasis » sous Studio Dev, observer le chargement fluide de la liste des oasis et de leurs garnisons animales, puis tester le générateur d'oasis pour vérifier la synchronisation en temps réel de la cartographie.

### Session du 30/09/2026 (Partie 4) — Métier Community Manager, RGPD & Communication par Mailing List
- **Concept exploré :** Métier de Community Manager dans le jeu vidéo, consentement éclairé (RGPD / Privacy by Design) et moteur de communication groupée (Mailing List).
- **Notions pour l'atelier :**
  - **Rôle du Community Manager (CM) :** Comprendre que créer un jeu vidéo ne se résume pas au code et au dessin : animer la communauté des joueurs, recueillir leurs retours, annoncer les nouveautés et maintenir le lien émotionnel avec l'univers sont des missions capitales.
  - **RGPD & Consentement explicite (Opt-in) :** Pourquoi la loi impose que la case d'inscription à une newsletter ne soit **jamais pré-cochée** (principe de l'opt-in libre, actif et révocable). Comprendre la différence entre un e-mail transactionnel obligatoire (activation de compte) et un e-mail promotionnel/informatif soumis à consentement.
  - **Segmentation d'audience & Ciblage :** Comment filtrer une base de données de joueurs pour envoyer le bon message à la bonne personne (abonnés volontaires, clans spécifiques, membres du studio).
  - **Génération et flux de données (Exports CSV) :** Comment transformer des enregistrements SQL en un fichier tableur universel (CSV avec encodage UTF-8 BOM pour compatibilité Excel) et l'envoyer au navigateur via des en-têtes HTTP de téléchargement direct (`Content-Disposition: attachment`).
- **Activité pratique suggérée :** Assigner le métier de Community Manager au profil de test, explorer le nouvel onglet « Mailing List », composer une dépêche impériale pour son clan féodal, et tester l'exportation du fichier CSV des abonnés pour l'ouvrir dans LibreOffice ou Excel.

### Session du 30/09/2026 (Partie 3) — Contrôle Qualité (QA), Registre des Fonctionnalités & Automatisation Syntaxe
- **Concept exploré :** Assurance Qualité (QA), industrialisation des tests, parsing de Markdown structuré et vérification statique de code (`php -l`).
- **Notions pour l'atelier :**
  - **Rôle du QA Tester dans l'industrie :** Pourquoi tester ne consiste pas seulement à « jouer », mais à suivre un protocole de recette méthodique avec des critères d'acceptation précis (statuts : *À tester*, *Validée*, *Rejetée*).
  - **Linter & Contrôle syntaxique automatisé :** Comment la commande CLI `php -l` (lint) permet de vérifier en quelques secondes l'absence d'erreurs de syntaxe sur l'ensemble des fichiers du projet (129+ fichiers scannés sans exécution) avant même de lancer des tests humains.
  - **Parsing et manipulation de fichiers Markdown :** Comment lire un fichier texte structuré (`fonctionnalités.md`), en extraire des données avec des expressions régulières (Regex) en PHP, calculer des statistiques et persister les modifications de statut de manière transparente.
  - **Gamification de la recette :** Valoriser le rôle de testeur en récompensant les validations et contrôles de syntaxe par des points d'XP Forge et de progression d'équipe.
- **Activité pratique suggérée :** Dans l'onglet « QA & Recette » de Studio Dev, déclencher le diagnostic syntaxique automatique (bouton vert « Lancer l'analyse complète »), observer le temps de scan et les compteurs, puis passer en revue les fonctionnalités récentes pour simuler une session de recette officielle.

### Session du 30/09/2026 (Partie 2) — Roster Dev Team : Attribution Multiple & Suppression Réactive
- **Concept exploré :** Relations n-à-n (plusieurs-à-plusieurs) en base de données, intégrité applicative anti-doublons et manipulation réactive du DOM en JavaScript Vanilla (Fetch API sans rechargement de page).
- **Notions pour l'atelier :**
  - **Table de jonction & Relations N:N :** Comment lier plusieurs métiers à un même utilisateur via la table `user_dev_roles`.
  - **Blocage des doublons à 2 niveaux :**
    - *Frontend :* Désactivation des options déjà possédées (`disabled`, `opacity: 0.55`, tag "✓ Déjà assigné") dès la sélection du membre dans la modale.
    - *Backend :* Requête préparée PDO contrôlant l'existence avant insertion pour immuniser le système contre les soumissions concurrentes ou malveillantes.
  - **Expérience Utilisateur (UX) Réactive :**
    - Suppression fluide d'un badge du DOM (`style.opacity = '0'`, `transform: scale(0.8)`) suite à confirmation par modale Tabler.io (zéro popup native du navigateur).
    - Injection dynamique de nouveaux badges dans le tableau à la volée avec écouteurs d'événements attachés sans recharger la page.
- **Activité pratique suggérée :** Assigner et retirer des métiers de l'équipe (Game Designer, Sound Designer, QA Tester) sur les comptes de test et observer en direct l'actualisation des badges et de la grille des métiers.

### Session du 30/09/2026 (Partie 1) — Refonte Inscription, Sécurité SMTP & Débogage de Routage
- **Concept exploré :** Cycle complet d'authentification utilisateur et négociation réseau par sockets.
- **Notions pour l'atelier :**
  - Pourquoi ne jamais stocker un mot de passe en clair (hachage bcrypt / argon2 vs chiffrement réversible AES-256).
  - Comment vérifier la concordance d'un mot de passe en temps réel avant d'envoyer la requête au serveur.
### Session du 30/09/2026 (Partie 4) — Contrôle d'Accès Strict (RBAC) & Masquage DOM des Onglets par Métier
- **Concept exploré :** Cloisonnement strict des interfaces (Security by Design) et distinction fondamentale entre masquage CSS (`display: none`) et non-rendu côté serveur.
- **Notions pour l'atelier :**
  - **Ne jamais faire confiance au client :** Pourquoi masquer un élément en CSS (`display: none`) ne protège rien (n'importe quel joueur peut ouvrir les outils de développement `F12` et inspecter le code HTML ou déclencher un événement JS).
  - **Non-rendu PHP (Zéro DOM) :** Conditionner l'émission du HTML avec `<?php if (in_array(...)): ?>` empêche totalement la fuite d'informations ou de contrôles sensibles vers le navigateur d'un profil non habilité.
  - **Protection en profondeur du routeur :** Interception dès le routeur principal `index.php` en amont de l'affichage avec redirection `302` et code `HTTP 403` si un utilisateur modifie manuellement le paramètre `?tab=...` dans la barre d'adresse.
  - **Passe-droit hiérarchique (Super-Admin) :** Comment un administrateur système hérite naturellement de la visibilité exhaustive sur l'ensemble des modules d'un studio sans altérer les règles propres aux métiers spécialisés.
- **Activité pratique suggérée :** 
  1. Se connecter avec un compte doté uniquement du rôle *Narrative Designer*.
  2. Ouvrir l'inspecteur du navigateur (`F12`), chercher `#tab-system` ou `#tab-qa` et constater qu'aucun nœud HTML n'existe.
  3. Taper manuellement `/?page=dev_team&tab=system` dans la barre d'adresse et observer la redirection immédiate vers l'onglet autorisé avec le bandeau d'alerte.

### Session du 30/09/2026 (Partie 5) — Game Elevate Designer : Migration de Modules & Principe de Moindre Privilège
- **Concept exploré :** Décentralisation de l'administration vers les espaces métiers, rôle du *Game Designer* dans l'équilibrage de gameplay, et application stricte du **principe de moindre privilège** (*Least Privilege*) même envers les comptes administrateurs.
- **Notions pour l'atelier :**
  - **Qu'est-ce qu'un Game Elevate Designer ?** Dans un studio de jeux vidéo, ce n'est pas l'administrateur système qui décide de la vitesse des chantiers ou de la prolifération des oasis sauvages, mais le concepteur de jeu (*Game Designer*). Rapatrier ces modules dans l'espace « Studio Dev » permet à chaque corps de métier d'avoir son établi de travail dédié sans polluer l'administration technique du serveur.
  - **Le principe de moindre privilège (*Least Privilege*) :** Même un compte administrateur ne doit pas posséder des boutons sensibles activés par défaut s'il n'en a pas le besoin immédiat. En n'affichant les modules de modification du monde qu'après auto-attribution explicite du métier « Game Elevate Designer », on évite les clics accidentels catastrophiques (ex: regénérer un monde en cours de saison ou altérer brutalement les vitesses de jeu).
  - **Migration architecturale propre :** Comment déplacer des blocs entiers d'une vue (`views/admin.php`) vers une autre (`views/dev_team.php`) tout en maintenant la rétrocompatibilité des contrôleurs (`api/admin.php` et `api/dev_team.php`) et en garantissant qu'aucune porte dérobée (*backdoor*) ne subsiste dans les anciennes routes.
- **Activité pratique suggérée :**
  1. Ouvrir l'administration générale : vérifier que les commandes de vitesses, d'arpentage et d'oasis ont bien disparu pour ne laisser place qu'aux prestigieux 12 Donjons Féodaux.
  2. Aller dans « Studio Dev » avec le compte admin : constater que les onglets de paramétrage monde n'apparaissent pas encore.
  3. S'attribuer le métier « Game Elevate Designer » dans le Roster : voir s'illuminer les 3 nouveaux onglets ⚡, 🗾 et 🌿 !
  4. Tester un preset de vitesse éclair (20x) et admirer le gain de +30 XP Forge dans le journal des créateurs.

### Session du 30/09/2026 (Partie 6) — Layout Fluide, Gouttières Responsive & Alignement Vertical Géométrique
- **Concept exploré :** Conception de layouts réactifs modernes (CSS Grid / Flexbox / Bootstrap & Tabler.io), passage d'un conteneur bridé (`container-xl` fixe) à un conteneur fluide (`container-fluid`), et gestion rigoureuse des gouttières (*gutters / paddings*) pour garantir la cohérence d'alignement vertical entre en-tête et corps de page.
- **Notions pour l'atelier :**
  - **Pourquoi abandonner les largeurs fixes bridées sur desktop ?** Sur un écran large (1080p, 1440p ou 4K), un conteneur bridé à 1140px ou 1320px laisse de gigantesques bandes vides sur les côtés, réduisant artificiellement l'espace disponible pour les tableaux tactiques, les arbres de recherche ou les cartes de jeu.
  - **Le piège des gouttières asymétriques :** Si le header utilise un conteneur avec un espacement latéral de 1.5rem (`px-lg-4`) et que le corps de page utilise une marge différente ou un conteneur fixe, les bordures extérieures des composants ne s'alignent pas verticalement. L'œil humain repère immédiatement ce décalage inélégant.
  - **L'art de l'imbrication propre (Reset CSS) :** Quand une vue globale fournit déjà les marges latérales via `.page-body > .container-fluid px-3 px-lg-4`, les sous-vues incluses ne doivent pas redéclarer un `container-xl` avec son propre padding interne, sous peine de cumuler les marges (*padding stacking*) ou de provoquer un défilement horizontal parasite (`overflow-x`). L'utilisation conjointe de classes utilitaires (`px-0`) et d'une règle globale de neutralisation dans `style.css` résout définitivement ce problème.
  - **L'exception stratégique de la carte de jeu :** Certaines vues nécessitent une immersion bord à bord totale (comme la carte du monde provincial Sengoku). Savoir gérer cette exception conditionnelle en PHP (`$page === 'map' ? 'px-0' : 'px-3 px-lg-4'`) sans casser le reste du site est une compétence clé du développeur frontend senior.
- **Activité pratique suggérée :**
  1. Redimensionner la fenêtre du navigateur en plein écran sur un écran d'ordinateur de bureau.
  2. Prendre une règle virtuelle (ou afficher les repères avec l'inspecteur `F12`) et tracer une ligne verticale depuis la première colonne de ressources (Riz) jusqu'au bord gauche de la carte principale : observer l'alignement parfait des bordures !
  3. Ouvrir l'onglet « Écosystème des oasis » ou « Roster & Métiers » dans Studio Dev et apprécier le confort visuel d'un affichage qui respire et tire parti de toute la largeur de l'écran.

### Session du 07/10/2026 (Partie 17) — Game Design Asymétrique, Concurrence de Files d'Attente & Capacités de Clans (Oda vs Takeda/Tokugawa)
- **Concept exploré :** Conception de mécaniques de gameplay asymétriques selon la faction choisie, gestion fine de la concurrence dans une file d'attente partagée (`construction_queue`), et détection d'effets de bord lors de refontes majeures (régression de type de données).
- **Notions pour l'atelier :**
  - **Qu'est-ce que l'Asymétrie dans un Jeu de Stratégie ? (L'Esprit des Clans Sengoku) :** Si tous les clans fonctionnaient à l'identique, le jeu perdrait sa richesse tactique. Le Clan Oda, réputé pour sa modernisation et sa vitesse d'organisation, possède un privilège unique : le *Double Développement Simultané*. Un joueur Oda peut mener de front la construction d'un bâtiment urbain (Tenshu, Forge, Dojo) ET l'élévation d'une parcelle rurale (Rizière, Carrière, Forêt). Les autres clans (Takeda, Tokugawa) doivent se concentrer sur un seul chantier à la fois sur tout leur domaine, à moins de décréter le prestigieux Sceau Impérial.
  - **Comment modéliser des Files d'Attente Indépendantes en Base de Données ? :** Dans une table unique `construction_queue`, chaque chantier porte un marqueur de catégorie (`build_category`). Pour le Clan Oda, le serveur ne compte pas le total des chantiers, mais partitionne la file : d'un côté les chantiers ruraux (`rural_plot` / `field`), de l'autre les infrastructures urbaines (`building`). Cela permet d'avoir deux chronomètres qui tournent en parallèle sans jamais se bloquer l'un l'autre !
  - **Le Piège des Clauses `else` et des Migrations de Types (L'Effet Domino) :** Lors de la refonte des 9 parcelles, un nouveau type `build_category = 'rural_plot'` a été introduit. Mais dans les vues (`city.php`, `building.php`), le code testait `if ($q['build_category'] === 'field') ... else { $buildingsInQueue++; }`. Conséquence inattendue : les nouvelles parcelles rurales tombaient toutes dans le `else` et étaient comptées comme des bâtiments urbains ! La ville se retrouvait donc instantanément bloquée, croyant à tort qu'un bâtiment urbain était en cours. En programmation, il faut toujours être explicite (`in_array(..., ['field', 'rural_plot'])`) plutôt que de compter sur des `else` fourre-tout.
  - **Tester les Cas Limites (Tests Unitaires de Concurrence) :** Comment s'assurer qu'un joueur ne triche pas ou qu'un bug ne permet pas de lancer 10 chantiers ? En écrivant des tests automatisés qui essaient délibérément de violer les règles du jeu : tenter de lancer un 2e chantier rural pour Oda sans Sceau, tenter de lancer un rural pour Takeda alors qu'un urbain tourne déjà, et vérifier que le moteur renvoie bien une erreur claire et protectrice.
- **Activité pratique suggérée :**
  1. Se connecter avec le compte Oda : lancer l'amélioration d'un bâtiment urbain dans la Cité castrale.
  2. Passer immédiatement sur la page Ressources : observer le badge « Double Chantier (Clan Oda) » et lancer l'élévation d'une Forêt.
  3. Constater dans la colonne de droite que les deux comptes à rebours s'égrènent ensemble en temps réel !
  4. Réfléchir ensemble : en quoi cet avantage modifie-t-il la vitesse de développement économique au début d'une saison ? Pourquoi les autres clans reçoivent-ils en contrepartie des bonus de combat ou de défense pour équilibrer la balance ?

### Session du 07/10/2026 (Partie 18) — Ergonomie Épurée (Minimalist UX), Réduction de la Surcharge Cognitive & Portails Inter-Mondes
- **Concept exploré :** Épuration visuelle d'une interface de jeu (principe « *Less is More* »), hiérarchisation de l'information (badges discrets vs panneaux détaillés), et utilisation d'un bâtiment emblématique comme passerelle de navigation (*World Portal*).
- **Notions pour l'atelier :**
  - **Pourquoi trop d'informations tuent l'immersion ? (La Règle du Glancement) :** Quand une illustration panoramique de grande qualité est recouverte de gigantesques blocs de 175px avec des jauges, des chronomètres et des taux de production, le joueur ne voit plus le paysage du terroir ! En réduisant chaque tuile à un format micro-pilule épuré (**Logo + Niv. Actuel / Max**), la carte respire à nouveau. Si le joueur veut les détails (ouvriers, coûts, temps restant), il les obtient en survolant la tuile ou en ouvrant la modale. C'est la règle d'or du *Progressive Disclosure* (divulgation progressive de l'information).
  - **Le Donjon comme Passerelle (Le Concept de « Porte entre Deux Mondes ») :** Dans la plupart des jeux de stratégie féodale (Travian, Ikariam), le monde rural (Dorf 1) et la cité fortifiée (Dorf 2) sont connectés. Plutôt que de traiter le Tenshu comme une simple ferme de ressources parmi les autres, le consacrer comme une porte directe vers la Cité Castrale (`/?page=buildings`) donne un repère géographique naturel au joueur : cliquer sur le grand château fort au sommet de la colline le téléporte immédiatement au cœur de sa ville !
- **Activité pratique suggérée :**
  1. Comparer visuellement la carte avant et après : apprécier comme le paysage féodal 16:9 est mis en valeur avec les micro-badges.
  2. Cliquer sur le Tenshu : constater la transition directe vers la Cité castrale.
  3. Cliquer sur une rizière ou une carrière : vérifier que la modale d'élévation continue de fournir toutes les données détaillées à la demande.

---

## 🛠️ Modèle d'Entrée pour les Prochaines Sessions (Template)

```markdown
### Session du [JJ/MM/AAAA] — [Titre de la fonctionnalité ou du sujet]
- **Concept exploré :** [Explication synthétique du concept technique ou ludique]
- **Notions pour l'atelier :**
  - [Point d'apprentissage 1]
  - [Point d'apprentissage 2]
- **Activité pratique suggérée :** [Exercice, test guidé ou questionnement avec mon fils]
```
