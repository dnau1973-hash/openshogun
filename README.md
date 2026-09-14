# 🏯 OpenShogun - Chroniques Féodales du Japon Sengoku

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

## 🛠️ Prérequis & Installation

### Prérequis
- **PHP** : 8.1 ou supérieur (avec extensions `pdo`, `pdo_mysql`, `mbstring`)
- **Base de données** : MariaDB 10.5+ ou MySQL 8.0+
- **Serveur Web** : Apache (avec `mod_rewrite`) ou Nginx

### Installation Rapide
1. Cloner le dépôt :
   ```bash
   git clone git@github.com:dnau1973-hash/openshogun.git
   cd openshogun
   ```
2. Importer la base de données :
   ```bash
   mysql -u root -p opengalaxy < database/schema.sql
   ```
3. Configurer la connexion dans `config/database.php` :
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'opengalaxy');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   ```
4. Lancer les tests de vérification :
   ```bash
   php tests/test_village_generation.php
   ```

---

## 📜 Licence
Projet sous licence MIT - Voir le fichier [LICENSE](LICENSE) pour plus de détails.
