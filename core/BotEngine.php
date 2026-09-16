<?php
/**
 * Moteur d'Intelligence Artificielle et de Colonisation des Bots (PNJ)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class BotEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    /**
     * Récupère la liste de tous les bots enregistrés
     */
    public function getBots(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.email, u.faction, u.points, u.created_at, u.last_active,
                   COUNT(p.id) as planet_count,
                   MIN(p.coord_x) as capital_x,
                   MIN(p.coord_y) as capital_y
            FROM users u
            LEFT JOIN planets p ON p.user_id = u.id
            WHERE u.is_bot = 1
            GROUP BY u.id
            ORDER BY u.points DESC, u.id ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Crée un bot avec sa planète capitale et ses infrastructures initiales
     */
    public function createBot(string $username, string $faction): array {
        $username = trim($username);
        if (!array_key_exists($faction, FACTIONS)) {
            $faction = 'terran';
        }

        // Vérifier l'unicité
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => "Le bot '{$username}' existe déjà."];
        }

        $email = strtolower($username) . "@bot.opengalaxy.local";
        $dummyHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);

        $this->db->beginTransaction();
        try {
            // 1. Créer le compte bot
            $stmtUser = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, created_at, last_active)
                VALUES (?, ?, ?, ?, 0, 1, 100, NOW(), NOW())
            ");
            $stmtUser->execute([$username, $email, $dummyHash, $faction]);
            $botId = (int)$this->db->lastInsertId();

            // 2. Trouver des coordonnées libres
            $coords = $this->findFreeCoordinates();

            // 3. Fonder la capitale du domaine castral du bot
            $planetName = "Château " . ucfirst($username);
            $stmtPlanet = $this->db->prepare("
                INSERT INTO planets 
                (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, last_resource_update, is_capital) 
                VALUES (?, ?, ?, ?, 'terrestrial', 3000, 2500, 1200, 0, 80, 25000, 25000, 25000, UNIX_TIMESTAMP(), 1)
            ");
            $stmtPlanet->execute([$botId, $planetName, $coords['x'], $coords['y']]);
            $planetId = (int)$this->db->lastInsertId();

            // 4. Initialiser les 18 parcelles de ressources de façon procédurale (Style Travian)
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, null, 1, true);

            // 5. Initialiser les bâtiments
            $stmtBuild = $this->db->prepare("
                INSERT INTO planet_buildings (planet_id, building_type, level) 
                VALUES (?, ?, ?)
            ");
            $stmtBuild->execute([$planetId, 'hq', 2]);
            $stmtBuild->execute([$planetId, 'storage', 2]);
            $stmtBuild->execute([$planetId, 'tank', 2]);
            $stmtBuild->execute([$planetId, 'barracks', 2]);
            $stmtBuild->execute([$planetId, 'shipyard', 1]);

            // 6. Donner une garnison initiale
            $starterUnits = [
                'terran' => ['code' => 'piquier_ashigaru_yari', 'count' => 30],
                'vorash' => ['code' => 'fantassin_leger_takeda', 'count' => 45],
                'aethelis' => ['code' => 'sentinelle_yari_tokugawa', 'count' => 25]
            ];
            $u = $starterUnits[$faction] ?? $starterUnits['terran'];
            $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count) VALUES (?, ?, ?)
            ")->execute([$planetId, $u['code'], $u['count']]);

            // 7. Flotte de départ
            $this->db->prepare("
                INSERT INTO planet_ships (planet_id, ship_code, count) VALUES (?, 'transporter_light', 3)
            ")->execute([$planetId]);
            $this->db->prepare("
                INSERT INTO planet_ships (planet_id, ship_code, count) VALUES (?, 'spy_probe', 4)
            ")->execute([$planetId]);

            $this->db->commit();
            return ['success' => true, 'bot_id' => $botId, 'username' => $username, 'coords' => $coords];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Génère un groupe de bots prédéfinis avec identités scénarisées
     */
    public function generatePresetBots(int $count = 3): array {
        $botPresets = [
            // Clan Oda (terran)
            ['name' => 'Daimyo_Nobunaga', 'faction' => 'terran'],
            ['name' => 'General_Katsuie', 'faction' => 'terran'],
            ['name' => 'Strategie_Hideyoshi', 'faction' => 'terran'],
            // Clan Takeda (vorash)
            ['name' => 'Daimyo_Shingen', 'faction' => 'vorash'],
            ['name' => 'Cavalier_Yukimura', 'faction' => 'vorash'],
            ['name' => 'Guerrier_Masakage', 'faction' => 'vorash'],
            // Clan Tokugawa (aethelis)
            ['name' => 'Daimyo_Ieyasu', 'faction' => 'aethelis'],
            ['name' => 'Maitre_Hanzo', 'faction' => 'aethelis'],
            ['name' => 'Gardien_Tadakatsu', 'faction' => 'aethelis'],
        ];

        $created = [];
        $existingBots = $this->db->query("SELECT username FROM users WHERE is_bot = 1")->fetchAll(PDO::FETCH_COLUMN);

        $added = 0;
        foreach ($botPresets as $preset) {
            if ($added >= $count) break;
            if (in_array($preset['name'], $existingBots)) continue;

            $res = $this->createBot($preset['name'], $preset['faction']);
            if ($res['success']) {
                $created[] = $preset['name'] . " (" . ucfirst($preset['faction']) . ")";
                $added++;
            }
        }

        return [
            'success' => true,
            'created_count' => count($created),
            'created_bots' => $created
        ];
    }

    /**
     * Exécute un cycle de simulation autonome pour tous les bots
     */
    public function executeBotCycle(): array {
        if (!GameConfig::get('bots_enabled', true)) {
            return ['success' => false, 'message' => "Le système de bots est actuellement désactivé."];
        }

        $bots = $this->db->query("SELECT * FROM users WHERE is_bot = 1")->fetchAll();
        $autoColonize = GameConfig::get('bot_colonize_enabled', true);
        $maxPlanets = (int)GameConfig::get('bot_max_planets', 3);

        $report = [
            'bots_processed' => count($bots),
            'mines_upgraded' => 0,
            'buildings_upgraded' => 0,
            'troops_trained' => 0,
            'colonies_founded' => []
        ];

        foreach ($bots as $bot) {
            $botId = (int)$bot['id'];
            $faction = $bot['faction'];

            // Récupérer toutes les planètes du bot
            $planets = $this->db->prepare("SELECT * FROM planets WHERE user_id = ?");
            $planets->execute([$botId]);
            $botPlanets = $planets->fetchAll();

            foreach ($botPlanets as $planet) {
                $planetId = (int)$planet['id'];
                
                // Mettre à jour les ressources
                $this->planetEngine->updatePlanet($planetId);

                // 1. Amélioration de parcelle de ressource (Mines / Centrales)
                $fields = $this->planetEngine->getFields($planetId);
                // Trouver la mine de plus bas niveau
                usort($fields, function($a, $b) {
                    return $a['level'] <=> $b['level'];
                });

                if (!empty($fields) && $fields[0]['level'] < 15) {
                    $targetField = $fields[0];
                    $slot = (int)$targetField['field_slot'];
                    $newLevel = (int)$targetField['level'] + 1;

                    $this->db->prepare("
                        UPDATE planet_fields SET level = ? WHERE planet_id = ? AND field_slot = ?
                    ")->execute([$newLevel, $planetId, $slot]);
                    $report['mines_upgraded']++;
                }

                // 2. Amélioration d'un bâtiment
                $buildings = $this->planetEngine->getBuildings($planetId);
                $hqLvl = $buildings['hq'] ?? 1;
                $barracksLvl = $buildings['barracks'] ?? 1;

                if ($hqLvl < 10 && rand(1, 10) <= 6) {
                    $this->db->prepare("
                        INSERT INTO planet_buildings (planet_id, building_type, level)
                        VALUES (?, 'hq', ?)
                        ON DUPLICATE KEY UPDATE level = VALUES(level)
                    ")->execute([$planetId, $hqLvl + 1]);
                    $report['buildings_upgraded']++;
                } elseif ($barracksLvl < 8) {
                    $this->db->prepare("
                        INSERT INTO planet_buildings (planet_id, building_type, level)
                        VALUES (?, 'barracks', ?)
                        ON DUPLICATE KEY UPDATE level = VALUES(level)
                    ")->execute([$planetId, $barracksLvl + 1]);
                    $report['buildings_upgraded']++;
                }

                // 3. Recrutement de soldats en garnison
                $unitCodes = [
                    'terran' => ['piquier_ashigaru_yari', 'arquebusier_oda_tanegashima'],
                    'vorash' => ['fantassin_leger_takeda', 'archer_yumi_monte'],
                    'aethelis' => ['sentinelle_yari_tokugawa', 'archer_protecteur_muraille']
                ];
                $possibleUnits = $unitCodes[$faction] ?? $unitCodes['terran'];
                $chosenUnit = $possibleUnits[array_rand($possibleUnits)];
                $recruitCount = rand(5, 15);

                $this->db->prepare("
                    INSERT INTO planet_units (planet_id, unit_code, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + VALUES(count)
                ")->execute([$planetId, $chosenUnit, $recruitCount]);
                $report['troops_trained'] += $recruitCount;
            }

            // 4. COLONISATION AUTOMATIQUE D'UNE NOUVELLE PLANÈTE
            if ($autoColonize && count($botPlanets) < $maxPlanets) {
                // Trouver des coordonnées libres
                $newCoords = $this->findFreeCoordinates();
                $colonyNum = count($botPlanets) + 1;
                $colonyName = "Fief " . ucfirst($bot['username']) . " " . $colonyNum;

                // Créer le nouveau fief du bot
                $stmtNewPlanet = $this->db->prepare("
                    INSERT INTO planets 
                    (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, last_resource_update, is_capital) 
                    VALUES (?, ?, ?, ?, 'terrestrial', 2000, 1500, 800, 0, 60, 15000, 15000, 15000, UNIX_TIMESTAMP(), 0)
                ");
                $stmtNewPlanet->execute([$botId, $colonyName, $newCoords['x'], $newCoords['y']]);
                $newPlanetId = (int)$this->db->lastInsertId();

                // Initialiser les 18 parcelles de la nouvelle colonie de façon procédurale (Style Travian)
                VillageFieldGenerator::populatePlanetFields($this->db, $newPlanetId, null, 1, false);

                // Bâtiments de base
                $this->db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'hq', 1)")->execute([$newPlanetId]);
                $this->db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'barracks', 1)")->execute([$newPlanetId]);

                $report['colonies_founded'][] = [
                    'bot' => $bot['username'],
                    'colony_name' => $colonyName,
                    'coords' => "[{$newCoords['x']} : {$newCoords['y']}]"
                ];
            }

            // 5. Mettre à jour les points de classement du bot
            $this->updateBotPoints($botId);
        }

        return [
            'success' => true,
            'report' => $report
        ];
    }

    /**
     * Supprime un bot et l'ensemble de ses possessions
     */
    public function deleteBot(int $botId): bool {
        $stmt = $this->db->prepare("SELECT is_bot FROM users WHERE id = ?");
        $stmt->execute([$botId]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['is_bot'] !== 1) {
            return false;
        }

        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$botId]);
        return true;
    }

    /**
     * Met à jour les points d'empire d'un bot
     */
    private function updateBotPoints(int $botId): void {
        // Somme des niveaux de parcelles + bâtiments + unités
        $stmtField = $this->db->prepare("
            SELECT COALESCE(SUM(pf.level), 0) 
            FROM planet_fields pf 
            JOIN planets p ON pf.planet_id = p.id 
            WHERE p.user_id = ?
        ");
        $stmtField->execute([$botId]);
        $fieldPts = (int)$stmtField->fetchColumn();

        $stmtB = $this->db->prepare("
            SELECT COALESCE(SUM(pb.level), 0) 
            FROM planet_buildings pb 
            JOIN planets p ON pb.planet_id = p.id 
            WHERE p.user_id = ?
        ");
        $stmtB->execute([$botId]);
        $buildPts = (int)$stmtB->fetchColumn();

        $stmtU = $this->db->prepare("
            SELECT COALESCE(SUM(pu.count), 0) 
            FROM planet_units pu 
            JOIN planets p ON pu.planet_id = p.id 
            WHERE p.user_id = ?
        ");
        $stmtU->execute([$botId]);
        $unitPts = (int)$stmtU->fetchColumn();

        $totalPoints = ($fieldPts * 5) + ($buildPts * 15) + (int)($unitPts * 2) + 100;

        $this->db->prepare("UPDATE users SET points = ?, last_active = NOW() WHERE id = ?")
            ->execute([$totalPoints, $botId]);
    }

    /**
     * Trouve des coordonnées libres aléatoires (Style Travian)
     */
    private function findFreeCoordinates(): array {
        return VillageFieldGenerator::findRandomFreeCoordinates($this->db, 35);
    }
}

