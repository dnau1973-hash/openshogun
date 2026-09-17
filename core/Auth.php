<?php
/**
 * Gestionnaire d'Authentification et Initialisation Joueur
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/../config/game_constants.php';

class Auth {
    private PDO $db;

    public static function initSession(): void {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function __construct() {
        self::initSession();
        $this->db = Database::getConnection();
    }

    public static function check(): bool {
        self::initSession();
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int {
        self::initSession();
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUser(): ?array {
        if (!self::check()) return null;
        $stmt = $this->db->prepare("SELECT id, username, email, faction, alliance_id, points, is_admin, is_bot, created_at FROM users WHERE id = ?");
        $stmt->execute([self::id()]);
        return $stmt->fetch() ?: null;
    }

    public function isAdmin(): bool {
        $user = $this->getCurrentUser();
        return !empty($user) && (int)$user['is_admin'] === 1;
    }

    public function getCurrentPlanet(): ?array {
        $userId = self::id();
        if (!$userId) return null;

        $planetId = $_SESSION['current_planet_id'] ?? null;
        if ($planetId) {
            $stmt = $this->db->prepare("SELECT * FROM planets WHERE id = ? AND user_id = ?");
            $stmt->execute([$planetId, $userId]);
            $planet = $stmt->fetch();
            if ($planet) return $planet;
        }

        // Sinon récupérer la planète capitale
        $stmt = $this->db->prepare("SELECT * FROM planets WHERE user_id = ? ORDER BY is_capital DESC, id ASC LIMIT 1");
        $stmt->execute([$userId]);
        $planet = $stmt->fetch();
        if ($planet) {
            $_SESSION['current_planet_id'] = $planet['id'];
        }
        return $planet ?: null;
    }

    public function setCurrentPlanet(int $planetId): void {
        $userId = self::id();
        $stmt = $this->db->prepare("SELECT id FROM planets WHERE id = ? AND user_id = ?");
        $stmt->execute([$planetId, $userId]);
        if ($stmt->fetch()) {
            $_SESSION['current_planet_id'] = $planetId;
        }
    }

    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['faction'] = $user['faction'];

        // Mise à jour de la dernière activité
        $this->db->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);

        return ['success' => true];
    }

    public function register(string $username, string $email, string $password, string $faction, string $zone = 'random'): array {
        $username = trim($username);
        $email = trim($email);

        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'error' => 'Le nom d\'utilisateur doit contenir entre 3 et 30 caractères.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Le mot de passe doit comporter au moins 6 caractères.'];
        }
        if (!array_key_exists($faction, FACTIONS)) {
            return ['success' => false, 'error' => 'Civilisation galactique invalide.'];
        }

        // Vérifier l'unicité
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Ce nom d\'utilisateur ou cet email est déjà utilisé.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->db->beginTransaction();
        try {
            // 1. Créer l'utilisateur
            $stmtUser = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, faction, created_at, last_active) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtUser->execute([$username, $email, $hash, $faction]);
            $userId = (int)$this->db->lastInsertId();

            // 2. Trouver un emplacement de coordonnées (X, Y) libre dans le quadrant choisi (Style Travian)
            $coords = $this->findFreeCoordinates($zone);

            // 3. Créer le domaine castral principal
            $planetName = "Château " . ucfirst($username);
            $stmtPlanet = $this->db->prepare("
                INSERT INTO planets 
                (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, last_resource_update, is_capital) 
                VALUES (?, ?, ?, ?, 'terrestrial', 2000, 1500, 800, 0, 50, 15000, 15000, 15000, UNIX_TIMESTAMP(), 1)
            ");
            $stmtPlanet->execute([$userId, $planetName, $coords['x'], $coords['y']]);
            $planetId = (int)$this->db->lastInsertId();

            // 4. Initialiser les 18 parcelles de ressources de façon procédurale (Style Travian)
            $capArchetype = VillageFieldGenerator::getRandomArchetypeKey(true);
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, $capArchetype, 0, true);

            // 5. Initialiser les bâtiments de base de la colonie (QG lvl 1, Stockages lvl 1, Caserne lvl 1)
            $stmtBuild = $this->db->prepare("
                INSERT INTO planet_buildings (planet_id, building_type, level) 
                VALUES (?, ?, 1)
            ");
            $stmtBuild->execute([$planetId, 'hq']);
            $stmtBuild->execute([$planetId, 'storage']);
            $stmtBuild->execute([$planetId, 'tank']);
            $stmtBuild->execute([$planetId, 'barracks']);

            // 6. Donner 2 sondes d'espionnage et 1 transporteur léger pour démarrer
            $stmtShip = $this->db->prepare("
                INSERT INTO planet_ships (planet_id, ship_code, count) 
                VALUES (?, ?, ?)
            ");
            $stmtShip->execute([$planetId, 'spy_probe', 2]);
            $stmtShip->execute([$planetId, 'transporter_light', 1]);

            // 7. Donner une première garnison de soldats (Style Travian)
            $starterUnits = [
                'terran' => ['code' => 'piquier_ashigaru_yari', 'count' => 15],
                'vorash' => ['code' => 'fantassin_leger_takeda', 'count' => 20],
                'aethelis' => ['code' => 'sentinelle_yari_tokugawa', 'count' => 15]
            ];
            $starter = $starterUnits[$faction] ?? $starterUnits['terran'];
            $stmtUnit = $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count) 
                VALUES (?, ?, ?)
            ");
            $stmtUnit->execute([$planetId, $starter['code'], $starter['count']]);

            // 8. Créer le Samouraï Héros initial (Style Travian)
            // Initialiser le Héros Samouraï et ses quêtes d'exploration
            require_once __DIR__ . '/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $heroName = "Samouraï " . ucfirst($username);
            $heroEngine->createHeroForUser($userId, $heroName, $planetId, $coords['x'], $coords['y']);

            $this->db->commit();

            // Connecter le joueur
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['faction'] = $faction;
            $_SESSION['current_planet_id'] = $planetId;

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la création du compte : ' . $e->getMessage()];
        }
    }

    public function logout(): void {
        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
    }

    /**
     * Recherche un emplacement libre (X, Y) dans le quadrant souhaité (Style Travian)
     */
    private function findFreeCoordinates(string $zone = 'random'): array {
        return VillageFieldGenerator::findRandomFreeCoordinates($this->db, 35, $zone);
    }

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
