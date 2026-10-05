# 🏯 La Voie du Shogun - Chroniques Féodales du Japon Sengoku

Jeu de stratégie multijoueur en temps réel sur navigateur (style **Travian**), se déroulant dans le Japon féodal de l'époque Sengoku Jidai. Fondez votre domaine castral, développez votre terroir rural, entraînez des régiments de samouraïs et étendez votre influence à travers les provinces impériales.

---

## 🌸 Fonctionnalités Principales

- **🌾 Terroir Rural & Domaines (18 Parcelles)** :
  - Système inspiré de Travian avec 18 parcelles de ressources disposées sur une carte isométrique 2.5D.
  - Sprites transparents pour chaque type d'exploitation :
    - 🪵 **Camp de Bûcherons** (Bois de Cèdre)
    - 🪨 **Carrière de Pierre** (Granite de Taille)
    - 🌾 **Rizière Inondée** (Riz Impérial)
    - ⛩️ **Sanctuaire Shintō** (Ferveur & Sérénité)
    - 🏯 **Tenshu Central** (Palais et cœur castral du Daimyō)
  - **Génération Procédurale des Terroirs** : 8 archétypes de domaines avec répartition aléatoire des 18 parcelles (Domaine Équilibré, Sylvestre, Fief des Carrières, Grenier Impérial, Terre Sacrée Shintō, etc.).

- **🗾 Carte des Provinces 2D & Navigation** :
  - Placement aléatoire des fiefs et des cités sur la carte géographique.
  - Navigation par glisser-déposer (drag & drop) et coordonnées interactives.

- **⚔️ Cité Castrale, Dojos & Casernes** :
  - Développement des infrastructures militaires et civiles (Quartier Général, Caserne, Académie, Greniers, Entrepôts).
  - Recrutement de régiments de samouraïs, archers, cavaliers et unités spécialisées.

- **🤖 Bots Autonomes (IA Féodale)** :
  - Intelligence artificielle simulant des seigneurs de guerre rivaux (Nobunaga, Shingen, Hideyoshi...).
  - Développement automatique des parcelles, recrutement d'armées et colonisation de nouveaux fiefs.

- **🎨 Thème Visuel Sengoku** :
  - Palette authentique : *Rouge impérial (#dc2626)*, *Noir laqué (#0c0c12)* et *Papier de riz (#f7f4ea)*.
  - Illustrations vectorielles travaillées pour l'accueil et le terroir.

---

## 🛠️ Déploiement & Installation Standardisée

### Méthode 1 — Déploiement Docker (Recommandé, Clé en main)

OpenShogun intègre un environnement conteneurisé complet (PHP 8.2 Apache + MariaDB 10.11 avec initialisation automatique).

1. **Cloner le dépôt et entrer dans le dossier :**
   ```bash
   git clone https://github.com/dnau1973-hash/openshogun.git
   cd openshogun
   ```
2. **Configurer l'environnement :**
   ```bash
   cp .env.example .env
   ```
3. **Lancer les conteneurs :**
   ```bash
   docker compose up -d
   ```
4. **Appliquer les migrations idempotentes :**
   ```bash
   docker compose exec web php scripts/migrate.php
   ```
5. **Ouvrir le jeu :** Accédez à `http://localhost:8080/` (ou complétez l'assistant sur `http://localhost:8080/install.php`).

---

### Méthode 2 — Déploiement Traditionnel (Bare-Metal / Serveur dédié)

1. **Prérequis :** PHP >= 8.1 (`pdo_mysql`, `mbstring`, `gd`, `json`), MariaDB >= 10.5 ou MySQL >= 8.0, Apache/Nginx.
2. **Configuration :**
   ```bash
   cp .env.example .env
   # Renseignez vos accès MySQL dans .env ou dans config/database.php
   ```
3. **Exécuter les migrations idempotentes :**
   ```bash
   php scripts/migrate.php
   ```
4. **Vérifier l'intégrité de l'environnement :**
   ```bash
   php scripts/check_syntax.php
   ```

---

## 📜 Licence
Projet sous licence MIT - Voir le fichier [LICENSE](LICENSE) pour plus de détails.

