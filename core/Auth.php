<?php
/**
 * Gestionnaire d'Authentification et Initialisation Joueur
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/../config/game_constants.php';

class Auth {
    private PDO $db;
    private static bool $protectionSchemaChecked = false;

    public static function initSession(): void {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function __construct() {
        self::initSession();
        $this->db = Database::getConnection();
        self::ensureProtectionSchema($this->db);
    }

    /**
     * Garantit automatiquement la présence de la colonne protection_until dans users
     */
    public static function ensureProtectionSchema(?PDO $db = null): void {
        if (self::$protectionSchemaChecked) return;
        self::$protectionSchemaChecked = true;

        try {
            $db = $db ?: Database::getConnection();
            $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('protection_until', $cols)) {
                $db->exec("ALTER TABLE users ADD COLUMN protection_until DATETIME NULL DEFAULT NULL AFTER last_active");
                try {
                    $db->exec("ALTER TABLE users ADD INDEX idx_protection (protection_until)");
                } catch (Exception $e) {}
                
                // Rétro-protection des utilisateurs créés il y a moins de 7 jours
                $db->exec("
                    UPDATE users 
                    SET protection_until = DATE_ADD(created_at, INTERVAL 7 DAY) 
                    WHERE is_bot = 0 
                      AND protection_until IS NULL 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ");
            }

            if (!in_array('is_moderator', $cols)) {
                try {
                    $db->exec("ALTER TABLE users ADD COLUMN is_moderator TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin");
                    $db->exec("ALTER TABLE users ADD INDEX idx_user_moderator (is_moderator)");
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {
            // Ignorer silencieusement si la table n'est pas encore créée
        }
    }

    /**
     * Vérifie si un joueur bénéficie de la protection / immunité des nouveaux joueurs
     */
    public static function isUserProtected(int|array $userOrUserId): bool {
        $db = Database::getConnection();
        self::ensureProtectionSchema($db);

        $userData = null;
        if (is_array($userOrUserId)) {
            $userData = $userOrUserId;
        } else {
            $stmt = $db->prepare("SELECT id, is_bot, created_at, protection_until FROM users WHERE id = ?");
            $stmt->execute([(int)$userOrUserId]);
            $userData = $stmt->fetch();
        }

        if (!$userData) return false;
        if (!empty($userData['is_bot'])) return false; // Les bots ne bénéficient pas d'immunité

        if (isset($userData['protection_until'])) {
            if ($userData['protection_until'] === null) {
                return false;
            }
            return strtotime($userData['protection_until']) > time();
        }

        // Fallback si la colonne protection_until n'était pas sélectionnée
        if (!empty($userData['id'])) {
            $stmt = $db->prepare("SELECT protection_until, created_at, is_bot FROM users WHERE id = ?");
            $stmt->execute([(int)$userData['id']]);
            $u = $stmt->fetch();
            if (!$u || !empty($u['is_bot'])) return false;
            if ($u['protection_until'] !== null) {
                return strtotime($u['protection_until']) > time();
            }
        }

        return false;
    }

    /**
     * Calcule le temps de protection restant et retourne les métadonnées formatées
     */
    public static function getProtectionRemaining(int|array $userOrUserId): ?array {
        $db = Database::getConnection();
        self::ensureProtectionSchema($db);

        $userData = null;
        if (is_array($userOrUserId) && array_key_exists('protection_until', $userOrUserId)) {
            $userData = $userOrUserId;
        } else {
            $uid = is_array($userOrUserId) ? (int)($userOrUserId['id'] ?? 0) : (int)$userOrUserId;
            $stmt = $db->prepare("SELECT id, username, is_bot, created_at, protection_until FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $userData = $stmt->fetch();
        }

        if (!$userData || !empty($userData['is_bot'])) {
            return null;
        }

        $untilStr = $userData['protection_until'] ?? null;
        if (!$untilStr) {
            return null;
        }

        $untilTs = strtotime($untilStr);
        $now = time();
        $diff = $untilTs - $now;

        if ($diff <= 0) {
            return [
                'is_protected' => false,
                'remaining_seconds' => 0,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'formatted' => 'Expirée',
                'until_datetime' => $untilStr,
                'until_timestamp' => $untilTs,
                'until_formatted' => date('d/m/Y H:i', $untilTs)
            ];
        }

        $days = (int)floor($diff / 86400);
        $hours = (int)floor(($diff % 86400) / 3600);
        $minutes = (int)floor(($diff % 3600) / 60);

        $formatted = ($days > 0) ? "{$days}j {$hours}h" : (($hours > 0) ? "{$hours}h {$minutes}m" : "{$minutes}m");

        return [
            'is_protected' => true,
            'remaining_seconds' => $diff,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'formatted' => $formatted,
            'until_datetime' => $untilStr,
            'until_timestamp' => $untilTs,
            'until_formatted' => date('d/m/Y à H:i', $untilTs)
        ];
    }

    /**
     * Révoque l'immunité d'un joueur (ex: lorsqu'il lance une attaque sur un autre seigneur)
     */
    public static function revokeProtection(int $userId): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET protection_until = NOW() WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Prolonge ou réactive l'immunité d'un joueur pour un nombre de jours donné
     */
    public static function extendProtection(int $userId, int $days = 7): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE users 
                SET protection_until = DATE_ADD(GREATEST(NOW(), COALESCE(protection_until, NOW())), INTERVAL ? DAY)
                WHERE id = ?
            ");
            return $stmt->execute([$days, $userId]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function check(): bool {
        self::initSession();
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int {
        self::initSession();
        return $_SESSION['user_id'] ?? null;
    }

    public static function getCurrentUser(): ?array {
        if (!self::check()) return null;
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, faction, alliance_id, points, is_admin, is_moderator, is_bot, created_at, protection_until FROM users WHERE id = ?");
        $stmt->execute([self::id()]);
        return $stmt->fetch() ?: null;
    }

    public function isAdmin(): bool {
        $user = $this->getCurrentUser();
        return !empty($user) && (int)$user['is_admin'] === 1;
    }

    public function isModerator(): bool {
        $user = $this->getCurrentUser();
        return !empty($user) && ((int)($user['is_moderator'] ?? 0) === 1 || (int)($user['is_admin'] ?? 0) === 1);
    }

    public function isStaff(): bool {
        $user = $this->getCurrentUser();
        return !empty($user) && ((int)($user['is_admin'] ?? 0) === 1 || (int)($user['is_moderator'] ?? 0) === 1);
    }

    public static function isUserModerator(int|array $userOrUserId): bool {
        if (is_array($userOrUserId)) {
            return !empty($userOrUserId['is_moderator']) || !empty($userOrUserId['is_admin']);
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT is_admin, is_moderator FROM users WHERE id = ?");
        $stmt->execute([(int)$userOrUserId]);
        $row = $stmt->fetch();
        return !empty($row) && (!empty($row['is_moderator']) || !empty($row['is_admin']));
    }

    public function setModerator(int $userId, bool $status): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_moderator = ? WHERE id = ?");
        return $stmt->execute([$status ? 1 : 0, $userId]);
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

        // Pré-initialiser le HeroEngine hors transaction pour exécuter les DDL (ensureSchema)
        require_once __DIR__ . '/HeroEngine.php';
        $heroEngine = new HeroEngine($this->db);

        $this->db->beginTransaction();
        try {
            // Durée de protection des nouveaux joueurs en jours (7 jours par défaut)
            $protectionDays = (int)GameConfig::get('beginner_protection_days', 7);
            $protectionUntil = ($protectionDays > 0) ? date('Y-m-d H:i:s', time() + ($protectionDays * 86400)) : null;

            // 1. Créer l'utilisateur avec son immunité féodale de départ
            $stmtUser = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, faction, created_at, last_active, protection_until) 
                VALUES (?, ?, ?, ?, NOW(), NOW(), ?)
            ");
            $stmtUser->execute([$username, $email, $hash, $faction, $protectionUntil]);
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

            // 4. Initialiser les 19 parcelles de ressources de façon procédurale (Style Travian)
            $capArchetype = VillageFieldGenerator::getRandomArchetypeKey(true);
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, $capArchetype, 0, false);

            // 5. Aucun bâtiment initial pré-placé : tous les slots 19 à 34 sont 100% libres pour le joueur

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
            $heroName = "Samouraï " . ucfirst($username);
            $heroEngine->createHeroForUser($userId, $heroName, $planetId, $coords['x'], $coords['y']);

            $this->db->commit();

            // Connecter le joueur
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['faction'] = $faction;
            $_SESSION['current_planet_id'] = $planetId;

            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                try {
                    $this->db->rollBack();
                } catch (Throwable $re) {
                    // Ignorer si la transaction a déjà été terminée
                }
            }
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
