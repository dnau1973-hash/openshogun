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
