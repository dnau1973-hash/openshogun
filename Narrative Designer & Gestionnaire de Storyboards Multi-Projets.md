# 📜 Plan d'Implémentation : Narrative Designer & Gestionnaire de Storyboards Multi-Projets

## 🎯 1. Vision & Objectifs

L'objectif est d'enrichir le **Game Studio** d'OpenShogun avec une nouvelle branche de métier dédiée : le **Narrative Designer**.

Le premier outil développé au sein de ce pôle est le **Gestionnaire de Storyboards & Timeline Narrative**, conçu pour :
1. **Modéliser et séquencer visuellement la narration** des vidéos de démo, trailers cinématiques de gameplay, annonces de versions, quêtes épiques et chroniques événementielles du Sengoku.
2. **Offrir une Timeline horizontale interactive avec Drag & Drop natif** permettant d'ordonner, déplacer et calibrer les plans (illustrations, textes narratifs, durées, transitions, mouvements de caméra et ambiances sonores).
3. **Supporter le Multi-Storyboards en simultané** : créer, cloner, basculer, éditer et archiver plusieurs projets de storyboards en travaux.
4. **Intégrer les forces de proposition avancées** :
   - Connexion directe avec la **Galerie d'Art Féodale** (sélecteur modal parmi les 238 illustrations du jeu).
   - **Mode Lecteur / Preview Cinématique en direct** (simulation temps réel du rendu vidéo avec défilement minuté des images et sous-titres).
   - **Générateur automatique de Prompts IA Vidéo** (Runway Gen-3, Kling, Sora, Luma) pour chaque plan.
   - **Analyse du rythme narratif** (KPIs durée totale, WPM - mots par minute pour la voix off, cadence de montage).
   - **Export multi-formats** : Script de tournage Markdown/PDF, JSON, Fiches de plans.

---

## 🏗️ 2. Architecture Technique & Intégration

```
Game Studio (views/gamestudio.php)
  │
  ├── 🧭 Navbar : Nouveau Menu Déroulant "Narrative Designer"
  │     ├── 🎬 Gestionnaire de Storyboard (tab=narrative_storyboard)  <-- NOUVEAU
  │     └── 📜 Charte Narrative & Lore Féodal (tab=narrative_lore)
  │
  ├── ⚙️ Backend & API
  │     ├── core/StoryboardEngine.php (CRUD, calculs de timecodes, persistance hybride DB/JSON)
  │     └── api/storyboard.php (Endpoints AJAX pour gestion fluide sans rechargement)
  │
  └── 🎨 Interface & Vues
        ├── views/gamestudio/narrative_storyboard.php (Contrôleur d'inclusion Studio)
        ├── views/studio/narrative-designer/storyboard-manager.php (UI Timeline, Drag & Drop, Player)
        └── views/gamestudio/narrative_lore.php (Vue Charte & Lore)
```

### Modèle de Données & Tables SQL

```sql
-- Table des Storyboards (Projets en travaux)
CREATE TABLE IF NOT EXISTS `storyboards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `video_type` ENUM('trailer', 'gameplay_demo', 'event_teaser', 'quest_cinematic', 'social_clip') DEFAULT 'trailer',
    `target_duration` INT NOT NULL DEFAULT 20, -- Durée cible en secondes
    `audio_track_title` VARCHAR(150) NULL,
    `audio_track_url` VARCHAR(255) NULL,
    `aspect_ratio` VARCHAR(10) DEFAULT '16:9',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_storyboard_type` (`video_type`),
    INDEX `idx_storyboard_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Plans / Séquences de la Timeline
CREATE TABLE IF NOT EXISTS `storyboard_shots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `storyboard_id` INT NOT NULL,
    `order_index` INT NOT NULL DEFAULT 0,
    `title` VARCHAR(150) NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `narrative_text` TEXT NULL,       -- Texte d'ambiance ou description de l'action
    `voiceover_text` TEXT NULL,       -- Texte exact lu en voix off ou dialogue
    `camera_movement` VARCHAR(80) NULL, -- Travelling avant, Plan large, Vue aérienne...
    `sound_fx` VARCHAR(150) NULL,      -- Effets audio, bruits d'ambiance, instruments
    `duration` DECIMAL(4,1) NOT NULL DEFAULT 4.0, -- Durée du plan en secondes (ex: 3.5s)
    `transition_type` VARCHAR(50) DEFAULT 'cut',   -- cut, fade_black, crossfade, wipe
    `ai_prompt` TEXT NULL,            -- Prompt IA vidéo pré-calculé
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`storyboard_id`) REFERENCES `storyboards`(`id`) ON DELETE CASCADE,
    INDEX `idx_shot_order` (`storyboard_id`, `order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> [!NOTE]
> **Résilience & Mode Hors-Ligne :** Le moteur `StoryboardEngine.php` implémentera un stockage hybride : persistance SQL avec bascule transparente vers un fichier JSON de sauvegarde locale (`data/storyboards_backup.json`) et `localStorage` client si la base MySQL est temporairement indisponible.

---

## 🧩 3. Spécifications Détaillées des Composants

### A. Barre de Navigation Game Studio (`views/gamestudio.php`)
- Ajout d'un nouvel élément de navigation :
  - **Icône :** `<i class="fa-solid fa-film text-purple"></i>` ou `<i class="fa-solid fa-book-open-reader text-purple"></i>`
  - **Libellé :** `Narrative Designer`
  - **Menu déroulant :**
    1. **Gestionnaire de Storyboards** : Timeline, drag & drop, vidéos de démo & trailers (`tab=narrative_storyboard`).
    2. **Charte Narrative & Lore Féodal** : Lexique d'époque, factions Oda/Takeda/Mori, guides de quêtes (`tab=narrative_lore`).

---

### B. Gestion Multi-Storyboards (Espace de Travail)
- **Sélecteur de projet actif :** Un menu déroulant permettant de passer instantanément d'un storyboard à l'autre en haut de l'interface.
- **Bouton « + Nouveau Storyboard » :** Modale de création avec :
  - Titre du projet (ex: *Trailer Marché Féodal 17s*)
  - Catégorie : *Trailer Vidéo, Démo de Gameplay, Événement de Saison, Quête Cinématique, Clip Réseaux*
  - Durée cible (en secondes, ex: 15s, 20s, 30s, 60s)
  - Format d'image : 16:9 (paysage cinématographique) ou 9:16 (vertical mobile)
  - Piste musicale d'ambiance suggérée (*Taiko martial, Koto mélancolique, Flûte Shakuhachi*)
- **Actions de projet :**
  - **Dupliquer** : Cloner un storyboard avec tous ses plans pour créer une variante sans modifier l'original.
  - **Renommer / Éditer les métadonnées**.
  - **Supprimer** avec confirmation sécurisée.
  - **Export / Import JSON** pour sauvegarder ou échanger des storyboards.
- **Storyboards par défaut pré-embarqués :**
  1. **Trailer Officiel &bull; Le Marché de la Cité Castrale (17s)** :
     - Plan 1 (0:00 - 0:04) : Échoppes & Vivres (*carrousel_01.jpg*)
     - Plan 2 (0:04 - 0:09) : Déambulation des Geishas (*carrousel_08.jpg*)
     - Plan 3 (0:09 - 0:14) : Patrouille des Samouraïs (*clan_takeda_cavalry_charge.jpg* ou unité samouraï)
     - Plan 4 (0:14 - 0:17) : Donjon Tenshu & Logo OpenShogun (*building_tenshu.jpg*)
  2. **Teaser Gameplay &bull; Assaut Naval du Clan Mori (22s)**
  3. **Chronique Artisanale &bull; La Grande Forge du Tamahagane (18s)**

---

### C. La Timeline Interactive & Drag & Drop

```
+---------------------------------------------------------------------------------------------------------+
| [Règle Temporelle]  00:00s ---------- 00:05s ---------- 00:10s ---------- 00:15s ---------- 00:20s     |
| Curseur Cumulé:     |=== Plan 1 [4s] ===|=== Plan 2 [5s] ===|=== Plan 3 [5s] ===|=== Plan 4 [3s] ===|   |
+---------------------------------------------------------------------------------------------------------+
|                                                                                                         |
|  [ Carte Plan 1 ]         [ Carte Plan 2 ]         [ Carte Plan 3 ]         [ Carte Plan 4 ]            |
|  +--------------------+   +--------------------+   +--------------------+   +--------------------+      |
|  | [::] Glisser       |   | [::] Glisser       |   | [::] Glisser       |   | [::] Glisser       |      |
|  | [ Image Aperçu ]   |   | [ Image Aperçu ]   |   | [ Image Aperçu ]   |   | [ Image Aperçu ]   |      |
|  | Plan 1 : Échoppes  |   | Plan 2 : Geishas   |   | Plan 3 : Samouraïs |   | Plan 4 : Tenshu    |      |
|  | Timecode: 0s - 4s  |   | Timecode: 4s - 9s  |   | Timecode: 9s - 14s |   | Timecode: 14s - 17s|      |
|  | [Durée: 4.0s] [v]  |   | [Durée: 5.0s] [v]  |   | [Durée: 5.0s] [v]  |   | [Durée: 3.0s] [v]  |      |
|  | [Éditer] [Suppr]   |   | [Éditer] [Suppr]   |   | [Éditer] [Suppr]   |   | [Éditer] [Suppr]   |      |
|  +--------------------+   +--------------------+   +--------------------+   +--------------------+      |
|                                                                                                         |
|  [ + Ajouter un plan ]  [ 💾 Sauvegarder ]  [ ▶️ Lire le Storyboard ]  [ 🤖 Exporter Prompts IA ]        |
+---------------------------------------------------------------------------------------------------------+
```

- **Drag and Drop natif HTML5 :**
  - Poignée de préhension visuelle (`<i class="fa-solid fa-grip-vertical"></i>`).
  - Animation de glissement fluide avec indicateur de drop zone.
  - Recalcul automatique et instantané des timecodes dès qu'un plan change de position.
- **Détails de chaque Plan :**
  - **Vignette d'illustration :** Sélecteur direct ouvrant un tiroir modal connecté aux 238 illustrations répertoriées dans notre Galerie d'Art (`public/assets/`).
  - **Champs narratifs :**
    - Titre du plan
    - Narration / Voix off (ou réplique de personnage)
    - Notes de mise en scène / Cadrage (Travelling avant, Plan panoramique, Gros plan...)
    - Ambiance sonore / Sound FX (Taiko, Koto, foule du marché, bruit de sabre...)
    - Durée en secondes (incrémentable par pas de 0.5s ou champ direct)
    - Transition vers le plan suivant (Cut franc, Fondu au noir, Fondu croisé)

---

### D. Forces de Proposition Différenciantes

1. **Lecteur Cinématique Interactif (Storyboard Player) :**
   - Modale plein écran simulant le visionnage du trailer final.
   - Les illustrations défilent automatiquement en respectant scrupuleusement la durée programmée pour chaque plan.
   - Transitions visuelles simulées en CSS (cut, fondu au noir, fondu croisé).
   - Bandeau de sous-titres stylisé pour afficher la voix off en bas d'écran.
   - Barre de lecture temporelle interactive avec boutons Lecture, Pause, Précédent, Suivant, et plein écran.

2. **Générateur Automatique de Prompts IA Vidéo :**
   - Un bouton « Exporter Prompts IA » analyse la séquence et génère en un clic :
     - Le **Prompt Global** (pour une génération en un seul plan continu).
     - Les **Prompts Détaillés Plan par Plan** rédigés en anglais cinématographique avec tags techniques (éclairage, lentille 35mm, 4K, 24fps, style féodal réaliste).
     - Boutons de copie rapide vers le presse-papier pour Runway Gen-3, Sora, Kling et Luma.

3. **Calculateur de Métriques & Rythme Narratif (KPIs Temps Réel) :**
   - **Durée totale cumulée vs Durée cible** (avec jauge d'écart vert/jaune/rouge).
   - **Compteur de mots total de la voix off**.
   - **Débit de lecture estimé (WPM - Words Per Minute)** : avertit si le narrateur doit parler trop vite (> 150 WPM) ou s'il y a des blancs narratifs (< 100 WPM).
   - **Rythme moyen de découpage** (durée moyenne par plan, ex: 4.2s/plan).

4. **Export Fiche de Tournage & Script de Production :**
   - Export d'une fiche récapitulative au format Markdown téléchargeable ou imprimable (tableau avec timecodes, visuels, dialogues, sons et instructions de caméra).

---

## 📅 4. Déroulement du Développement par Étapes

| Étape | Livrables | Description |
|---|---|---|
| **Étape 1** | Moteur Backend & API | Création de `core/StoryboardEngine.php` (gestion des tables, seed des storyboards démo, méthodes CRUD et calculs de timing) et de l'endpoint API `api/storyboard.php`. |
| **Étape 2** | Navigation Game Studio | Mise à jour de `views/gamestudio.php` avec le menu dropdown *Narrative Designer*, ses routes `narrative_storyboard` et `narrative_lore`. |
| **Étape 3** | Interface Storyboard & Timeline | Création de `views/studio/narrative-designer/storyboard-manager.php` avec la règle temporelle, les cartes de plans et le moteur de Drag & Drop natif. |
| **Étape 4** | Sélecteur d'Actifs & Modal | Intégration du navigateur d'illustrations puisant dans les 238 fichiers des 9 répertoires d'actifs du jeu. |
| **Étape 5** | Player Cinématique & IA | Ajout de la modale de lecture vidéo temps réel, du générateur de prompts IA vidéo et du calculateur de débit vocal (WPM). |
| **Étape 6** | Tests, Recette & Docs | Validation fonctionnelle, linting PHP, mise à jour du `CHANGELOG.md` et de `fonctionnalités.md`. |

---

## ❓ 5. Questions / Choix à Valider avec l'Utilisateur

1. **Persistance des projets de storyboards :** Préférez-vous que les storyboards soient partagés entre tous les administrateurs/devs du Game Studio (stockage centralisé en base MySQL + sauvegarde JSON), ou spécifiques à l'utilisateur connecté ?
2. **Durée cible standard par défaut :** 20 secondes convient-il comme valeur par défaut pour les formats courts/trailers (comme le projet du marché à 17s) ?
3. **Piste audio d'ambiance :** Souhaitez-vous que le Storyboard Player puisse jouer un fond sonore féodal d'ambiance (par exemple une nappe de taiko ou flûte du jeu) pendant la prévisualisation ?
