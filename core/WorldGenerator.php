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

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Génère des planètes neutres/inoccupées de manière procédurale
     */
    public function generatePlanets(int $count = 12, int $radius = 10, array $allowedTypes = [], bool $clearExistingUninhabited = false, bool $startAtZero = false): array {
        if ($clearExistingUninhabited) {
            $this->db->exec("
                DELETE FROM planets 
                WHERE user_id IS NULL 
                  AND is_capital != 2 
                  AND id NOT IN (SELECT COALESCE(planet_id, 0) FROM authentic_castles WHERE is_spawned = 1)
            ");
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

        // Coordonnées déjà occupées (planètes existantes, donjons authentiques et oasis)
        $occupiedStmt = $this->db->query("
            SELECT coord_x, coord_y FROM planets
            UNION
            SELECT coord_x, coord_y FROM authentic_castles WHERE is_spawned = 1
            UNION
            SELECT coord_x, coord_y FROM oases
        ");
        $occupiedMap = [];
        while ($row = $occupiedStmt->fetch()) {
            $occupiedMap[$row['coord_x'] . ':' . $row['coord_y']] = true;
        }

        $createdPlanets = [];
        $attempts = 0;
        $maxAttempts = $count * 50;

        // Garantir la migration de schéma transparente
        require_once __DIR__ . '/PlanetEngine.php';
        $planetEngine = new PlanetEngine($this->db);
        $planetEngine->ensureSchemaMigration();

        $hasFishCol = false;
        try {
            $chk = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'fish'");
            $hasFishCol = ($chk && $chk->rowCount() > 0);
        } catch (Exception $e) {
            try {
                $this->db->query("SELECT fish FROM planets LIMIT 0");
                $hasFishCol = true;
            } catch (Exception $e2) {
                $hasFishCol = false;
            }
        }

        if ($hasFishCol) {
            $insertStmt = $this->db->prepare("
                INSERT INTO planets 
                (name, coord_x, coord_y, planet_type, metal, crystal, deuterium, fish, energy_used, energy_max, metal_max, crystal_max, deuterium_max, fish_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 50, 20000, 20000, 20000, 20000, 0, UNIX_TIMESTAMP())
            ");
        } else {
            $insertStmt = $this->db->prepare("
                INSERT INTO planets 
                (name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 50, 20000, 20000, 20000, 0, UNIX_TIMESTAMP())
            ");
        }

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

            // Ressources initiales selon le type de planète (ou 0 si startAtZero)
            if ($startAtZero) {
                $baseMetal = 0;
                $baseCrystal = 0;
                $baseDeut = 0;
            } else {
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
            }

            $insertStmt->execute([$name, $x, $y, $type, $baseMetal, $baseCrystal, $baseDeut]);
            $newPlanetId = (int)$this->db->lastInsertId();
            $occupiedMap[$x . ':' . $y] = true;

            // Initialiser les 19 parcelles selon un archétype procédural (Style Travian)
            $fieldInfo = VillageFieldGenerator::populatePlanetFields($this->db, $newPlanetId, null, 0, false);

            // Initialiser le domaine rural (9 parcelles)
            try {
                require_once __DIR__ . '/VillageGeneratorService.php';
                $villageService = new VillageGeneratorService($this->db);
                $villageService->generateVillage($newPlanetId, false, true, $startAtZero);
            } catch (Exception $e) {
                // Continuer si erreur
            }

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
     * 
     * @param string $adminPassword Mot de passe assigné au compte administrateur nezzar
     * @param int $neutralPlanetsCount Nombre de provinces neutres non colonisées à générer
     * @param bool $deployBots Déployer ou non les 3 seigneurs IA de départ (Oda, Takeda, Tokugawa)
     * @param bool|null $preserveHumanUsers Conserver les comptes des joueurs déjà présents dans le monde (null = selon GameConfig)
     */
    public function resetUniverse(
        string $adminPassword = 'Gabriel125#', 
        int $neutralPlanetsCount = 12, 
        bool $deployBots = true, 
        ?bool $preserveHumanUsers = null
    ): array {
        $preserveHumanUsers = ($preserveHumanUsers !== null) 
            ? (bool)$preserveHumanUsers 
            : (bool)GameConfig::get('preserve_human_users_on_reset', true);

        $driverName = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driverName !== 'sqlite') {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        }

        // Garantir que les migrations de schéma sont appliquées avant le vidage et la reconstruction
        try {
            require_once __DIR__ . '/PlanetEngine.php';
            $planetEngine = new PlanetEngine($this->db);
            $planetEngine->ensureSchemaMigration(true);
        } catch (Exception $eMig) {
            error_log("WorldGenerator::resetUniverse - ensureSchemaMigration notice: " . $eMig->getMessage());
        }

        // 1. Vidage de toutes les tables dynamiques de partie (progression, armées, fiefs, quêtes)
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
            'craft_queue',
            'planet_buildings',
            'planet_fields',
            'planet_rural_plots',
            'user_quests',
            'hero_inventory',
            'hero_adventures',
            'heroes',
            'oasis_units',
            'oases',
            'planets',
            'support_tickets',
            'alliances',
            'user_weekly_stats'
        ];

        foreach ($dynamicTables as $tbl) {
            try {
                $this->db->exec("TRUNCATE TABLE `{$tbl}`");
            } catch (PDOException $e) {
                // Repli sur DELETE si TRUNCATE échoue (ex: SQLite ou contraintes résiduelles)
                try {
                    $this->db->exec("DELETE FROM `{$tbl}`");
                } catch (PDOException $e2) {
                    error_log("WorldGenerator::resetUniverse - purge `{$tbl}` notice: " . $e2->getMessage());
                }
            }
        }

        // Purge systématique des bots IA précédents dans `users`
        try {
            $this->db->exec("DELETE FROM users WHERE is_bot = 1");
        } catch (Exception $e) {
            error_log("WorldGenerator::resetUniverse - DELETE bots notice: " . $e->getMessage());
        }

        // Traitement des comptes joueurs selon l'option 'preserve_human_users'
        if (!$preserveHumanUsers) {
            // Option DÉSACTIVÉE : purge complète des comptes joueurs non-administrateurs
            try {
                $this->db->exec("DELETE FROM users WHERE is_admin = 0");
            } catch (Exception $e) {
                error_log("WorldGenerator::resetUniverse - DELETE non-admin users notice: " . $e->getMessage());
            }
        } else {
            // Option ACTIVÉE : Réinitialisation stricte des statistiques, devises (kobars/gold_coins),
            // privilèges impériaux et appartenances pour TOUS les utilisateurs conservés.
            // L'identité, les identifiants et les accès des comptes joueurs sont intégralement préservés.
            $protectionDays = (int)GameConfig::get('beginner_protection_days', 7);
            $protectionSql  = ($protectionDays > 0)
                ? "DATE_ADD(NOW(), INTERVAL {$protectionDays} DAY)"
                : "NULL";

            // Détection pilote DB (compatibilité SQLite pour tests)
            $driverName = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driverName === 'sqlite') {
                $protVal = ($protectionDays > 0) ? (time() + ($protectionDays * 86400)) : 'NULL';
                try {
                    $this->db->exec("UPDATE users SET points = 0, alliance_id = NULL, protection_until = {$protVal}, gold_coins = 0, imperial_seal_until = NULL, last_daily_gold = NULL");
                } catch (Exception $eFull) {
                    try {
                        $this->db->exec("UPDATE users SET points = 0, alliance_id = NULL, protection_until = {$protVal}, gold_coins = 0");
                    } catch (Exception $eGold) {
                        $this->db->exec("UPDATE users SET points = 0, alliance_id = NULL, protection_until = {$protVal}");
                    }
                }
            } else {
                try {
                    $this->db->exec("
                        UPDATE users 
                        SET points = 0, 
                            alliance_id = NULL, 
                            protection_until = {$protectionSql},
                            gold_coins = 0,
                            imperial_seal_until = NULL,
                            last_daily_gold = NULL
                    ");
                } catch (Exception $eFull) {
                    try {
                        $this->db->exec("
                            UPDATE users 
                            SET points = 0, 
                                alliance_id = NULL, 
                                protection_until = {$protectionSql},
                                gold_coins = 0
                        ");
                    } catch (Exception $eGold) {
                        $this->db->exec("
                            UPDATE users 
                            SET points = 0, 
                                alliance_id = NULL, 
                                protection_until = {$protectionSql}
                        ");
                    }
                }
            }
        }

        // 2. Initialiser / Vérifier les constantes de jeu et réinitialiser la chronologie à l'Ère 1
        $nowTs = time();
        $defaultSettings = [
            'game_speed' => '5',
            'resource_speed' => '5',
            'fleet_speed' => '5',
            'bots_enabled' => '1',
            'bot_colonize_enabled' => '1',
            'bot_aggressiveness' => 'moderate',
            'bot_max_planets' => '3',
            'server_current_era' => '1',
            'server_start_timestamp' => (string)$nowTs,
            'era_castles_unlocked' => '0',
            'era_dojo_unlocked' => '0'
        ];
        $sqlSet = ($driverName === 'sqlite')
            ? "INSERT INTO game_settings (setting_key, setting_value, setting_type, updated_at)
               VALUES (?, ?, 'string', datetime('now'))
               ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = datetime('now')"
            : "INSERT INTO game_settings (setting_key, setting_value, setting_type, updated_at)
               VALUES (?, ?, 'string', NOW())
               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";

        $stmtSet = $this->db->prepare($sqlSet);
        foreach ($defaultSettings as $sk => $sv) {
            $stmtSet->execute([$sk, $sv]);
        }
        GameConfig::clearCache();

        // 3. Gestion / Garantie du Compte Administrateur 'nezzar'
        $adminUsername = 'nezzar';
        $adminEmail = 'nezzar@openshogun.local';
        $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $adminFaction = 'terran';

        $stmtFindAdmin = $this->db->prepare("SELECT id, faction FROM users WHERE username = ? OR is_admin = 1 ORDER BY is_admin DESC, id ASC LIMIT 1");
        $stmtFindAdmin->execute([$adminUsername]);
        $existingAdmin = $stmtFindAdmin->fetch(PDO::FETCH_ASSOC);

        if ($existingAdmin) {
            $adminId = (int)$existingAdmin['id'];
            $adminFaction = !empty($existingAdmin['faction']) ? $existingAdmin['faction'] : 'terran';
            // Mettre à jour le mot de passe de l'admin et garantir is_admin = 1 avec remise à zéro des devises et points
            try {
                $stmtUpAdmin = $this->db->prepare("UPDATE users SET password_hash = ?, is_admin = 1, is_bot = 0, points = 0, gold_coins = 0, imperial_seal_until = NULL, last_daily_gold = NULL WHERE id = ?");
                $stmtUpAdmin->execute([$adminHash, $adminId]);
            } catch (Exception $eAdmUp) {
                $stmtUpAdmin = $this->db->prepare("UPDATE users SET password_hash = ?, is_admin = 1, is_bot = 0, points = 0 WHERE id = ?");
                $stmtUpAdmin->execute([$adminHash, $adminId]);
            }
        } else {
            $stmtAdmin = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, faction, is_admin, is_bot, points, created_at, last_active)
                VALUES (?, ?, ?, ?, 1, 0, 0, NOW(), NOW())
            ");
            $stmtAdmin->execute([$adminUsername, $adminEmail, $adminHash, $adminFaction]);
            $adminId = (int)$this->db->lastInsertId();
        }

        // 4. Fonder le Domaine Castral Capitale de l'Admin en [1 : 1] avec ressources de départ (1000 Bois, Pierre, Riz)
        $capitalName = "Château Nezzar";
        $capitalX = 1;
        $capitalY = 1;

        $hasFishCol = false;
        try {
            $chk = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'fish'");
            $hasFishCol = ($chk && $chk->rowCount() > 0);
        } catch (Exception $e) {
            try {
                $this->db->query("SELECT fish FROM planets LIMIT 0");
                $hasFishCol = true;
            } catch (Exception $e2) {
                $hasFishCol = false;
            }
        }
        $starterWood  = (float)GameConfig::get('starter_wood', 1000.0);
        $starterStone = (float)GameConfig::get('starter_stone', 1000.0);
        $starterRice  = (float)GameConfig::get('starter_rice', 1000.0);
        $starterFish  = (float)GameConfig::get('starter_fish', 250.0);

        if ($hasFishCol) {
            $stmtCap = $this->db->prepare("
                INSERT INTO planets 
                (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, fish, energy_used, energy_max, metal_max, crystal_max, deuterium_max, fish_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, ?, 'terrestrial', ?, ?, ?, ?, 0, 120, 30000, 30000, 30000, 30000, 1, UNIX_TIMESTAMP())
            ");
            $stmtCap->execute([$adminId, $capitalName, $capitalX, $capitalY, $starterWood, $starterStone, $starterRice, $starterFish]);
        } else {
            $stmtCap = $this->db->prepare("
                INSERT INTO planets 
                (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, ?, 'terrestrial', ?, ?, ?, 0, 120, 30000, 30000, 30000, 1, UNIX_TIMESTAMP())
            ");
            $stmtCap->execute([$adminId, $capitalName, $capitalX, $capitalY, $starterWood, $starterStone, $starterRice]);
        }
        $capitalPlanetId = (int)$this->db->lastInsertId();

        // 5. Initialiser les 18 Parcelles de ressources vierges (Style Travian, niveau 0 = parcelles disponibles)
        VillageFieldGenerator::populatePlanetFields($this->db, $capitalPlanetId, 'balanced', 0, false);

        // 5b. Initialiser le Domaine Rural (9 parcelles à niveau 0)
        try {
            require_once __DIR__ . '/VillageGeneratorService.php';
            $villageService = new VillageGeneratorService($this->db);
            $villageService->generateVillage($capitalPlanetId, false, true, true);
        } catch (Exception $e) {
            error_log("WorldGenerator::resetUniverse - generateVillage error: " . $e->getMessage());
        }

        // 6. Aucun bâtiment initial dans les slots urbains (tous les slots 19 à 34 sont 100% libres pour le joueur)

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
        $stmtUnit->execute([$capitalPlanetId, 'spy_probe', 5]);

        // 8b. Initialiser le Héros Samouraï de départ pour l'Admin
        try {
            require_once __DIR__ . '/HeroEngine.php';
            $heroEngine = new HeroEngine($this->db);
            $heroEngine->createHeroForUser($adminId, "Samouraï " . ucfirst($adminUsername), $capitalPlanetId, $capitalX, $capitalY);
        } catch (Exception $eHero) {
            error_log("WorldGenerator::resetUniverse - createHeroForUser notice: " . $eHero->getMessage());
        }

        // 8c. Fonder un nouveau domaine de départ et un Héros pour CHAQUE autre joueur humain conservé
        $preservedUsersCount = 1; // Shogun Admin
        if ($preserveHumanUsers) {
            $stmtOtherUsers = $this->db->prepare("SELECT id, username, faction FROM users WHERE is_bot = 0 AND id != ?");
            $stmtOtherUsers->execute([$adminId]);
            $otherUsers = $stmtOtherUsers->fetchAll(PDO::FETCH_ASSOC);
            $preservedUsersCount += count($otherUsers);

            $starterUnits = [
                'terran' => ['code' => 'piquier_ashigaru_yari', 'count' => 15],
                'vorash' => ['code' => 'fantassin_leger_takeda', 'count' => 20],
                'aethelis' => ['code' => 'sentinelle_yari_tokugawa', 'count' => 15]
            ];

            foreach ($otherUsers as $ou) {
                $uId = (int)$ou['id'];
                $uName = $ou['username'];
                $uFaction = $ou['faction'] ?? 'terran';

                // Coordonnées libres pour le nouveau départ du joueur
                $uCoords = VillageFieldGenerator::findRandomFreeCoordinates($this->db, null, 'random');
                $uPlanetName = "Château " . ucfirst($uName);

                if ($hasFishCol) {
                    $stmtUserPlan = $this->db->prepare("
                        INSERT INTO planets 
                        (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, fish, energy_used, energy_max, metal_max, crystal_max, deuterium_max, fish_max, is_capital, last_resource_update)
                        VALUES (?, ?, ?, ?, 'terrestrial', ?, ?, ?, ?, 0, 120, 30000, 30000, 30000, 30000, 1, UNIX_TIMESTAMP())
                    ");
                    $stmtUserPlan->execute([$uId, $uPlanetName, $uCoords['x'], $uCoords['y'], $starterWood, $starterStone, $starterRice, $starterFish]);
                } else {
                    $stmtUserPlan = $this->db->prepare("
                        INSERT INTO planets 
                        (user_id, name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
                        VALUES (?, ?, ?, ?, 'terrestrial', ?, ?, ?, 0, 120, 30000, 30000, 30000, 1, UNIX_TIMESTAMP())
                    ");
                    $stmtUserPlan->execute([$uId, $uPlanetName, $uCoords['x'], $uCoords['y'], $starterWood, $starterStone, $starterRice]);
                }
                $uPlanetId = (int)$this->db->lastInsertId();

                // Parcelles de ressources
                $uArchetype = VillageFieldGenerator::getRandomArchetypeKey(true);
                VillageFieldGenerator::populatePlanetFields($this->db, $uPlanetId, $uArchetype, 0, false);

                // Domaine rural
                if (isset($villageService)) {
                    try {
                        $villageService->generateVillage($uPlanetId, false, true, true);
                    } catch (Exception $eVil) {}
                }

                // Troupes de départ
                $stUnitInfo = $starterUnits[$uFaction] ?? $starterUnits['terran'];
                $stmtUnit->execute([$uPlanetId, $stUnitInfo['code'], $stUnitInfo['count']]);

                // Vaisseaux et éclaireurs
                $stmtShip->execute([$uPlanetId, 'transporter_light', 1]);
                $stmtUnit->execute([$uPlanetId, 'spy_probe', 2]);

                // Héros Samouraï
                if (isset($heroEngine)) {
                    try {
                        $heroEngine->createHeroForUser($uId, "Samouraï " . ucfirst($uName), $uPlanetId, $uCoords['x'], $uCoords['y']);
                    } catch (Exception $eHero) {}
                }
            }
        }

        // 9. Générer les planètes neutres (avec ressources et parcelles à 0)
        $worldGenRes = $this->generatePlanets($neutralPlanetsCount, 10, [], false, true);

        // 10. Déployer les bots initiaux si demandé
        $botsResult = null;
        if ($deployBots) {
            $botEngine = new BotEngine();
            $botsResult = $botEngine->generatePresetBots(3);
        }

        // 10b. Génération automatique des oasis naturelles selon la densité configurée
        try {
            require_once __DIR__ . '/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $density = (float)GameConfig::get('oasis_density_percent', 2.0);
            $oasisEngine->spawnOasesByDensity($density, 28, true);
        } catch (Exception $e) {
            // Ignorer silencieusement
        }

        // 10c. Déploiement automatique et homogène des 12 Châteaux Authentiques du Japon
        try {
            require_once __DIR__ . '/CastleEngine.php';
            $castleEngine = new CastleEngine();
            $castleEngine->deployCastlesHomogeneously(null);
        } catch (Exception $e) {
            // Ignorer silencieusement
        }

        // 10d. Ensemencement aléatoire des réserves de poisson de chaque lac (10 000 à 25 000)
        try {
            require_once __DIR__ . '/LakeEngine.php';
            $lakeEngine = new LakeEngine($this->db);
            $lakeEngine->seedWorldLakes(40);
        } catch (Exception $e) {
            error_log("WorldGenerator::resetUniverse - seedWorldLakes notice: " . $e->getMessage());
        }

        // 10e. Ensemencement aléatoire des gisements minéraux de chaque montagne (Soufre, Nitrate, Sable de fer)
        try {
            require_once __DIR__ . '/MountainEngine.php';
            $mountainEngine = new MountainEngine($this->db);
            $mountainEngine->seedWorldMountains(40);
        } catch (Exception $e) {
            error_log("WorldGenerator::resetUniverse - seedWorldMountains notice: " . $e->getMessage());
        }

        if ($driverName !== 'sqlite') {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        }

        // 11. Reconnecter directement la session active sous le compte nezzar
        $_SESSION['user_id'] = $adminId;
        $_SESSION['username'] = $adminUsername;
        $_SESSION['faction'] = $adminFaction;
        $_SESSION['current_planet_id'] = $capitalPlanetId;

        $usersPreservedMsg = $preserveHumanUsers 
            ? "{$preservedUsersCount} compte(s) joueur(s) humain(s) ont été conservés avec un nouveau fief capital."
            : "Tous les anciens comptes joueurs ont été purgés (remise à blanc totale).";

        // Émettre la Proclamation de l'Ère I pour le nouveau monde
        try {
            require_once __DIR__ . '/GameStudioEngine.php';
            $gse = new GameStudioEngine($this->db);
            $gse->broadcastEraTransition(1);
        } catch (Throwable $eBc) {}

        return [
            'success' => true,
            'message' => "L'univers des Shoguns a été réinitialisé avec succès ! {$usersPreservedMsg} Vous êtes connecté sous le Daimyō '{$adminUsername}'.",
            'admin' => [
                'id' => $adminId,
                'username' => $adminUsername,
                'email' => $adminEmail,
                'capital_planet' => "{$capitalName} [{$capitalX} : {$capitalY}]"
            ],
            'neutral_planets' => $worldGenRes['generated_count'],
            'bots_deployed' => $botsResult ? $botsResult['created_count'] : 0,
            'preserve_human_users' => $preserveHumanUsers,
            'preserved_users_count' => $preservedUsersCount
        ];
    }
}

