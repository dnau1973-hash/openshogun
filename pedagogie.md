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
  - Débogage d'une liste blanche (`allowedTabs` / `validTabs`) : comprendre pourquoi un onglet valide côté vue était refoulé par le contrôleur de routage.
- **Activité pratique suggérée :** Créer un petit script de test en ligne de commande pour envoyer un message SMTP simulé avec `stream_socket_client`.

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
