<?php
/**
 * Moteur de Génération Procédurale de Mondes et Réinitialisation de l'Univers
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/BotEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class WorldGenerator {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Génère des planètes neutres/inoccupées de manière procédurale
     */
    public function generatePlanets(int $count = 12, int $radius = 10, array $allowedTypes = [], bool $clearExistingUninhabited = false): array {
        if ($clearExistingUninhabited) {
            $this->db->exec("DELETE FROM planets WHERE user_id IS NULL");
        }

        $allTypes = ['terrestrial', 'oceanic', 'desert', 'volcanic', 'arctic', 'gas'];
        $types = !empty($allowedTypes) ? array_intersect($allowedTypes, $allTypes) : $allTypes;
        if (empty($types)) $types = $allTypes;

        $prefixes = [
            'Province d\'Owari', 'Domaine de Mikawa', 'Fief de Kai', 'Province d\'Echigo', 
            'Château de Mino', 'Vallée d\'Omi', 'Domaine de Suruga', 'Fief de Totomi', 
            'Plateau de Shinano', 'Province de Kaga', 'Fief d\'Echizen', 'Plaine de Yamashiro', 
            'Domaine de Settsu', 'Château de Harima', 'Fief de Bizen', 'Domaine de Satsuma', 
            'Province de Higo', 'Fief de Chikuzen', 'Collines de Hizen', 'Fief de Tosa', 
            'Province d\'Iyo', 'Terres de Mutsu', 'Fief de Dewa', 'Forteresse de Sagami', 
            'Domaine de Musashi', 'Province de Kozuke', 'Fief de Hitachi'
        ];

        $suffixes = [
            'Castral', 'des Rizières', 'des Monts', 'du Fleuve', 'du Val', 
            'Supérieur', 'Inférieur', 'de la Plaine', 'du Soleil Levant', 'du Nord', 
            'du Sud', 'des Cèdres', 'Antique', 'des Cerisiers'
        ];

        // Coordonnées déjà occupées
        $occupiedStmt = $this->db->query("SELECT coord_x, coord_y FROM planets");
        $occupiedMap = [];
        while ($row = $occupiedStmt->fetch()) {
            $occupiedMap[$row['coord_x'] . ':' . $row['coord_y']] = true;
        }

        $createdPlanets = [];
        $attempts = 0;
        $maxAttempts = $count * 50;

        $insertStmt = $this->db->prepare("
            INSERT INTO planets 
            (name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 50, 20000, 20000, 20000, 0, UNIX_TIMESTAMP())
        ");

        while (count($createdPlanets) < $count && $attempts < $maxAttempts) {
            $attempts++;
            $x = rand(-$radius, $radius);
            $y = rand(-$radius, $radius);

            // Ne pas écraser le centre absolu (0,0) ni les coordonnées occupées
            if (($x === 0 && $y === 0) || isset($occupiedMap[$x . ':' . $y])) {
                continue;
            }

            $prefix = $prefixes[array_rand($prefixes)];
            $suffix = $suffixes[array_rand($suffixes)];
            $name = $prefix . " " . $suffix;
            $type = $types[array_rand($types)];

            // Ressources initiales selon le type de planète
            $baseMetal = 2000;
            $baseCrystal = 1500;
            $baseDeut = 1000;

            switch ($type) {
                case 'volcanic':
                    $baseMetal = rand(4000, 7000);
                    $baseCrystal = rand(1000, 2500);
                    $baseDeut = rand(500, 1500);
                    break;
                case 'arctic':
                    $baseMetal = rand(1500, 3000);
                    $baseCrystal = rand(3500, 6500);
                    $baseDeut = rand(1000, 2500);
                    break;
                case 'gas':
                    $baseMetal = rand(1000, 2000);
                    $baseCrystal = rand(1500, 3000);
                    $baseDeut = rand(4000, 8000);
                    break;
                case 'desert':
                    $baseMetal = rand(3000, 5000);
                    $baseCrystal = rand(2500, 4500);
                    $baseDeut = rand(800, 2000);
                    break;
                case 'oceanic':
                    $baseMetal = rand(2000, 4000);
                    $baseCrystal = rand(2000, 4000);
                    $baseDeut = rand(2500, 5000);
                    break;
                default: // terrestrial
                    $baseMetal = rand(2500, 4500);
                    $baseCrystal = rand(2000, 4000);
                    $baseDeut = rand(1500, 3000);
                    break;
            }

            $insertStmt->execute([$name, $x, $y, $type, $baseMetal, $baseCrystal, $baseDeut]);
            $newPlanetId = (int)$this->db->lastInsertId();
            $occupiedMap[$x . ':' . $y] = true;

            // Initialiser les 18 parcelles selon un archétype procédural (Style Travian)
            $fieldInfo = VillageFieldGenerator::populatePlanetFields($this->db, $newPlanetId, null, 0, false);

            $createdPlanets[] = [
                'name' => $name,
                'coords' => "[{$x} : {$y}]",
                'type' => $type,
                'archetype' => $fieldInfo['archetype_name'],
                'resources' => "M:{$baseMetal} / C:{$baseCrystal} / D:{$baseDeut}"
            ];
        }

        return [
            'success' => true,
            'generated_count' => count($createdPlanets),
            'planets' => $createdPlanets
        ];
    }

    /**
     * Réinitialisation complète de l'univers avec recréation de l'Administrateur par défaut
     */
    public function resetUniverse(string $adminPassword = 'Gabriel125#', int $neutralPlanetsCount = 12, bool $deployBots = true): array {
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Vidage de toutes les tables dynamiques
        $dynamicTables = [
            'combat_reports',
            'messages',
            'fleet_missions',
            'research_queue',
            'user_researches',
            'shipyard_queue',
            'planet_ships',
            'barracks_queue',
            'planet_units',
            'construction_queue',
            'planet_buildings',
            'planet_fields',
            'planets',
            'alliances',
            'users'
        ];

        foreach ($dynamicTables as $tbl) {
            $this->db->exec("TRUNCATE TABLE `{$tbl}`");
        }

        // 2. Initialiser / Vérifier les constantes de jeu
        $defaultSettings = [
            'game_speed' => '5',
            'resource_speed' => '5',
            'fleet_speed' => '5',
            'bots_enabled' => '1',
            'bot_colonize_enabled' => '1',
            'bot_aggressiveness' => 'moderate',
            'bot_max_planets' => '3'
        ];
        $stmtSet = $this->db->prepare("
            INSERT INTO game_settings (setting_key, setting_value, setting_type, updated_at)
            VALUES (?, ?, 'string', NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        foreach ($defaultSettings as $sk => $sv) {
            $stmtSet->execute([$sk, $sv]);
        }
        GameConfig::clearCache();

        // 3. Créer l'Administrateur par Défaut : nezzar / Gabriel125#
        $adminUsername = 'nezzar';
        $adminEmail = 'nezzar@openshogun.local';
        $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $adminFaction = 'terran';

        $stmtAdmin = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, created_at, last_active)
            VALUES (?, ?, ?, ?, 1, 0, 350, NOW(), NOW())
        ");
        $stmtAdmin->execute([$adminUsername, $adminEmail, $adminHash, $adminFaction]);
        $adminId = (int)$this->db->lastInsertId();

        // 4. Fonder le Domaine Castral Capitale de l'Admin en [1 : 1]
        $capitalName = "Château Nezzar";
        $capitalX = 1;
        $capitalY = 1;

        $stmtCap = $this->db->prepare("
            INSERT INTO planets 
            (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
            VALUES (?, ?, ?, ?, 'terrestrial', 8000, 6000, 3000, 0, 120, 30000, 30000, 30000, 1, UNIX_TIMESTAMP())
        ");
        $stmtCap->execute([$adminId, $capitalName, $capitalX, $capitalY]);
        $capitalPlanetId = (int)$this->db->lastInsertId();

        // 5. Initialiser les 18 Parcelles de ressources de manière procédurale (Style Travian)
        VillageFieldGenerator::populatePlanetFields($this->db, $capitalPlanetId, 'balanced', 1, true);

        // 6. Bâtiments initiaux avancés pour l'Admin
        $stmtBuild = $this->db->prepare("
            INSERT INTO planet_buildings (planet_id, building_type, level) 
            VALUES (?, ?, ?)
        ");
        $stmtBuild->execute([$capitalPlanetId, 'hq', 2]);
        $stmtBuild->execute([$capitalPlanetId, 'barracks', 2]);
        $stmtBuild->execute([$capitalPlanetId, 'storage', 2]);
        $stmtBuild->execute([$capitalPlanetId, 'tank', 2]);
        $stmtBuild->execute([$capitalPlanetId, 'shipyard', 1]);

        // 7. Garnison militaire de l'Admin
        $stmtUnit = $this->db->prepare("
            INSERT INTO planet_units (planet_id, unit_code, count) VALUES (?, ?, ?)
        ");
        $stmtUnit->execute([$capitalPlanetId, 'piquier_ashigaru_yari', 40]);
        $stmtUnit->execute([$capitalPlanetId, 'arquebusier_oda_tanegashima', 15]);

        // 8. Flotte de départ de l'Admin
        $stmtShip = $this->db->prepare("
            INSERT INTO planet_ships (planet_id, ship_code, count) VALUES (?, ?, ?)
        ");
        $stmtShip->execute([$capitalPlanetId, 'transporter_light', 3]);
        $stmtShip->execute([$capitalPlanetId, 'spy_probe', 5]);

        // 9. Générer les planètes neutres
        $worldGenRes = $this->generatePlanets($neutralPlanetsCount, 10, [], false);

        // 10. Déployer les bots initiaux si demandé
        $botsResult = null;
        if ($deployBots) {
            $botEngine = new BotEngine();
            $botsResult = $botEngine->generatePresetBots(3);
        }

        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // 11. Reconnecter directement la session active sous le compte nezzar
        $_SESSION['user_id'] = $adminId;
        $_SESSION['username'] = $adminUsername;
        $_SESSION['faction'] = $adminFaction;
        $_SESSION['current_planet_id'] = $capitalPlanetId;

        return [
            'success' => true,
            'message' => "L'univers des Shoguns a été réinitialisé avec succès ! Vous êtes connecté sous le Daimyō '{$adminUsername}'.",
            'admin' => [
                'id' => $adminId,
                'username' => $adminUsername,
                'email' => $adminEmail,
                'capital_planet' => "{$capitalName} [{$capitalX} : {$capitalY}]"
            ],
            'neutral_planets' => $worldGenRes['generated_count'],
            'bots_deployed' => $botsResult ? $botsResult['created_count'] : 0
        ];
    }
}

