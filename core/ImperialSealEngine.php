<?php
/**
 * ImperialSealEngine.php
 * Moteur du Sceau Impérial (Privilège du Shōgun / Travian Plus)
 * 
 * Gère :
 * - Le statut VIP "Sceau Impérial" (durée, privilèges)
 * - La monnaie féodale (Pièces d'Or / Koban / Ryō)
 * - L'Intendant du Marché (Échangeur de ressources NPC 1:1:1)
 * - L'Ordre de Repli Tactique (Évasion de Garnison)
 * - Le Carnet de Raids Automatisé (Farm List)
 * - La consolidation multi-villages pour le Grand Tableau de Bord de l'Empire
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';

class ImperialSealEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Migration automatique et vérification du schéma
     */
    public function ensureSchema(): void {
        try {
            // 1. Colonnes dans users : imperial_seal_until et gold_coins
            $userCols = [];
            $st = $this->db->query("SHOW COLUMNS FROM users");
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $userCols[] = $r['Field'];
            }

            if (!in_array('gold_coins', $userCols)) {
                $this->db->exec("ALTER TABLE users ADD COLUMN gold_coins INT UNSIGNED NOT NULL DEFAULT 100 AFTER email");
            }
            if (!in_array('imperial_seal_until', $userCols)) {
                $this->db->exec("ALTER TABLE users ADD COLUMN imperial_seal_until DATETIME NULL DEFAULT NULL AFTER gold_coins");
            }
            if (!in_array('last_daily_gold', $userCols)) {
                $this->db->exec("ALTER TABLE users ADD COLUMN last_daily_gold DATE NULL DEFAULT NULL AFTER imperial_seal_until");
            }

            // 2. Colonne tactical_evasion dans planets
            $planetCols = [];
            $stP = $this->db->query("SHOW COLUMNS FROM planets");
            while ($r = $stP->fetch(PDO::FETCH_ASSOC)) {
                $planetCols[] = $r['Field'];
            }
            if (!in_array('tactical_evasion', $planetCols)) {
                $this->db->exec("ALTER TABLE planets ADD COLUMN tactical_evasion TINYINT(1) NOT NULL DEFAULT 0 AFTER is_capital");
            }

            // 3. Table des listes de raids (Farm Lists)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `farm_lists` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `source_planet_id` INT UNSIGNED NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_fl_user` (`user_id`),
                    INDEX `idx_fl_source` (`source_planet_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 4. Table des entrées de cibles dans les listes de raids
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `farm_list_entries` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `farm_list_id` INT UNSIGNED NOT NULL,
                    `target_type` ENUM('planet', 'oasis') NOT NULL DEFAULT 'planet',
                    `target_id` INT UNSIGNED NOT NULL,
                    `target_name` VARCHAR(100) NOT NULL,
                    `coord_x` INT NOT NULL,
                    `coord_y` INT NOT NULL,
                    `fleet_data` TEXT NOT NULL,
                    `last_raid_at` DATETIME NULL DEFAULT NULL,
                    `last_loot` TEXT NULL DEFAULT NULL,
                    `last_status` VARCHAR(50) DEFAULT NULL,
                    `distance` DOUBLE DEFAULT 0,
                    INDEX `idx_fle_list` (`farm_list_id`),
                    CONSTRAINT `fk_fle_list` FOREIGN KEY (`farm_list_id`) REFERENCES `farm_lists` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

        } catch (Exception $e) {
            error_log("[ImperialSealEngine::ensureSchema] " . $e->getMessage());
        }
    }

    /**
     * Vérifie si le joueur possède le Sceau Impérial actif
     */
    public function isSealActive(int $userId): bool {
        $st = $this->db->prepare("SELECT imperial_seal_until FROM users WHERE id = ?");
        $st->execute([$userId]);
        $val = $st->fetchColumn();
        if (!$val) return false;
        return (strtotime($val) > time());
    }

    /**
     * Récupère le statut détaillé du Sceau Impérial
     */
    public function getSealStatus(int $userId): array {
        $st = $this->db->prepare("SELECT gold_coins, imperial_seal_until, last_daily_gold FROM users WHERE id = ?");
        $st->execute([$userId]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'active' => false,
                'gold' => 0,
                'until' => null,
                'remaining_seconds' => 0,
                'remaining_formatted' => 'Inactif',
                'can_claim_daily' => false
            ];
        }

        $gold = (int)($user['gold_coins'] ?? 0);
        $until = $user['imperial_seal_until'];
        $now = time();
        $active = false;
        $remainingSeconds = 0;
        $remainingFormatted = 'Inactif';

        if ($until) {
            $ts = strtotime($until);
            if ($ts > $now) {
                $active = true;
                $remainingSeconds = $ts - $now;
                $days = floor($remainingSeconds / 86400);
                $hours = floor(($remainingSeconds % 86400) / 3600);
                $mins = floor(($remainingSeconds % 3600) / 60);
                if ($days > 0) {
                    $remainingFormatted = "{$days}j {$hours}h restantes";
                } elseif ($hours > 0) {
                    $remainingFormatted = "{$hours}h {$mins}m restantes";
                } else {
                    $remainingFormatted = "{$mins} min restantes";
                }
            }
        }

        $today = date('Y-m-d');
        $canClaimDaily = ($user['last_daily_gold'] !== $today);

        return [
            'active' => $active,
            'gold' => $gold,
            'until' => $until,
            'remaining_seconds' => $remainingSeconds,
            'remaining_formatted' => $remainingFormatted,
            'can_claim_daily' => $canClaimDaily
        ];
    }

    /**
     * Récupère le solde de pièces d'or
     */
    public function getGoldCoins(int $userId): int {
        $st = $this->db->prepare("SELECT gold_coins FROM users WHERE id = ?");
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Décrète / Prolonge le Sceau Impérial
     */
    public function activateSeal(int $userId, int $days): array {
        $pricing = [
            7 => 50,    // 7 jours pour 50 Koban
            14 => 90,   // 14 jours pour 90 Koban (remise 10%)
            30 => 150   // 30 jours pour 150 Koban (remise 25%)
        ];

        if (!isset($pricing[$days])) {
            throw new Exception("Durée d'investiture impériale invalide (options : 7, 14 ou 30 jours).");
        }

        $cost = $pricing[$days];
        $currentGold = $this->getGoldCoins($userId);
        if ($currentGold < $cost) {
            throw new Exception("Solde insuffisant : il vous faut {$cost} Koban (vous en possédez {$currentGold}).");
        }

        $st = $this->db->prepare("SELECT imperial_seal_until FROM users WHERE id = ?");
        $st->execute([$userId]);
        $existingUntil = $st->fetchColumn();

        $now = time();
        $startTime = ($existingUntil && strtotime($existingUntil) > $now) 
            ? strtotime($existingUntil) 
            : $now;

        $newUntil = date('Y-m-d H:i:s', $startTime + ($days * 86400));

        $this->db->beginTransaction();
        try {
            $up = $this->db->prepare("
                UPDATE users 
                SET gold_coins = gold_coins - ?, imperial_seal_until = ? 
                WHERE id = ? AND gold_coins >= ?
            ");
            $up->execute([$cost, $newUntil, $userId, $cost]);

            if ($up->rowCount() === 0) {
                throw new Exception("Solde de Koban insuffisant lors du paiement.");
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => "Le Sceau Impérial du Shōgun est activé pour {$days} jours !",
            'new_until' => $newUntil,
            'remaining_gold' => $currentGold - $cost
        ];
    }

    /**
     * Récupère le tribut quotidien gratuit (+5 Koban)
     */
    public function claimDailyBonus(int $userId): array {
        $today = date('Y-m-d');
        $st = $this->db->prepare("SELECT last_daily_gold FROM users WHERE id = ?");
        $st->execute([$userId]);
        $lastDaily = $st->fetchColumn();

        if ($lastDaily === $today) {
            throw new Exception("Vous avez déjà perçu le tribut d'or impérial du jour. Revenez demain !");
        }

        $bonusGold = 5;
        $this->db->prepare("
            UPDATE users 
            SET gold_coins = gold_coins + ?, last_daily_gold = ? 
            WHERE id = ?
        ")->execute([$bonusGold, $today, $userId]);

        return [
            'success' => true,
            'claimed_amount' => $bonusGold,
            'message' => "Tribut quotidien perçu : +{$bonusGold} Koban ajoutés à votre trésor !"
        ];
    }

    /**
     * Intendant du Marché : Échangeur de ressources NPC 1:1:1
     * Rééquilibre instantanément le bois, la pierre et le riz
     */
    public function npcExchange(int $userId, int $planetId, int $wood, int $stone, int $rice): array {
        if ($wood < 0 || $stone < 0 || $rice < 0) {
            throw new Exception("Les quantités de ressources doivent être positives.");
        }

        $costGold = 3; // Coût symbolique de 3 Koban

        // Vérifier que la planète appartient à l'utilisateur
        $st = $this->db->prepare("SELECT id, user_id, metal, crystal, deuterium, metal_max, crystal_max, deuterium_max FROM planets WHERE id = ?");
        $st->execute([$planetId]);
        $planet = $st->fetch(PDO::FETCH_ASSOC);

        if (!$planet || (int)$planet['user_id'] !== $userId) {
            throw new Exception("Ce fief ne répond pas à votre commandement.");
        }

        $currentTotal = (int)floor($planet['metal'] + $planet['crystal'] + $planet['deuterium']);
        $targetTotal = (int)floor($wood + $stone + $rice);

        if ($currentTotal <= 0) {
            throw new Exception("Vos greniers sont déserts, impossible d'effectuer un troc.");
        }

        if ($targetTotal !== $currentTotal) {
            throw new Exception("Le total rééquilibré ($targetTotal) doit être strictement égal au total actuel ($currentTotal).");
        }

        // Vérification des capacités de stockage
        if ($wood > $planet['metal_max']) {
            throw new Exception("La quantité de Bois dépasse la capacité de vos greniers (" . number_format($planet['metal_max']) . ").");
        }
        if ($stone > $planet['crystal_max']) {
            throw new Exception("La quantité de Pierre dépasse la capacité de vos greniers (" . number_format($planet['crystal_max']) . ").");
        }
        if ($rice > $planet['deuterium_max']) {
            throw new Exception("La quantité de Riz dépasse la capacité de votre silo (" . number_format($planet['deuterium_max']) . ").");
        }

        $currentGold = $this->getGoldCoins($userId);
        if ($currentGold < $costGold) {
            throw new Exception("Il vous faut au moins {$costGold} Koban pour mandater l'Intendant du Marché.");
        }

        $this->db->beginTransaction();
        try {
            // Déduire l'or
            $upGold = $this->db->prepare("UPDATE users SET gold_coins = gold_coins - ? WHERE id = ? AND gold_coins >= ?");
            $upGold->execute([$costGold, $userId, $costGold]);
            if ($upGold->rowCount() === 0) {
                throw new Exception("Or insuffisant.");
            }

            // Rééquilibrer les ressources
            $upPlanet = $this->db->prepare("
                UPDATE planets 
                SET metal = ?, crystal = ?, deuterium = ? 
                WHERE id = ?
            ");
            $upPlanet->execute([$wood, $stone, $rice, $planetId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => "L'Intendant a rééquilibré vos réserves : {$wood} Bois, {$stone} Pierre, {$rice} Riz.",
            'wood' => $wood,
            'stone' => $stone,
            'rice' => $rice,
            'remaining_gold' => $currentGold - $costGold
        ];
    }

    /**
     * Active ou désactive l'Ordre de Repli Tactique pour un fief
     */
    public function toggleTacticalEvasion(int $userId, int $planetId): bool {
        $st = $this->db->prepare("SELECT tactical_evasion FROM planets WHERE id = ? AND user_id = ?");
        $st->execute([$planetId, $userId]);
        $val = $st->fetchColumn();
        if ($val === false) {
            throw new Exception("Fief introuvable ou non autorisé.");
        }

        $newVal = ($val == 1) ? 0 : 1;
        $up = $this->db->prepare("UPDATE planets SET tactical_evasion = ? WHERE id = ?");
        $up->execute([$newVal, $planetId]);
        return (bool)$newVal;
    }

    // =========================================================================
    // CARNET DE RAIDS (FARM LIST)
    // =========================================================================

    /**
     * Récupère les listes de raids du joueur
     */
    public function getFarmLists(int $userId, ?int $sourcePlanetId = null): array {
        $sql = "
            SELECT fl.*, p.name as source_planet_name, p.coord_x as source_coord_x, p.coord_y as source_coord_y,
                   (SELECT COUNT(*) FROM farm_list_entries WHERE farm_list_id = fl.id) as entries_count
            FROM farm_lists fl
            JOIN planets p ON fl.source_planet_id = p.id
            WHERE fl.user_id = ?
        ";
        $params = [$userId];

        if ($sourcePlanetId) {
            $sql .= " AND fl.source_planet_id = ?";
            $params[] = $sourcePlanetId;
        }

        $sql .= " ORDER BY fl.id ASC";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $lists = $st->fetchAll(PDO::FETCH_ASSOC);

        // Charger les entrées de chaque liste
        foreach ($lists as &$list) {
            $stEntries = $this->db->prepare("
                SELECT * FROM farm_list_entries 
                WHERE farm_list_id = ? 
                ORDER BY distance ASC, id ASC
            ");
            $stEntries->execute([$list['id']]);
            $entries = $stEntries->fetchAll(PDO::FETCH_ASSOC);

            foreach ($entries as &$entry) {
                $entry['fleet_data_arr'] = json_decode($entry['fleet_data'], true) ?: [];
                $entry['last_loot_arr'] = json_decode($entry['last_loot'] ?? '', true) ?: null;
            }
            $list['entries'] = $entries;
        }

        return $lists;
    }

    /**
     * Crée une nouvelle liste de raids
     */
    public function createFarmList(int $userId, int $sourcePlanetId, string $name): int {
        $name = trim($name);
        if (empty($name)) {
            $name = "Tournée de Garnison";
        }

        // Vérifier que la planète appartient au joueur
        $st = $this->db->prepare("SELECT id FROM planets WHERE id = ? AND user_id = ?");
        $st->execute([$sourcePlanetId, $userId]);
        if (!$st->fetch()) {
            throw new Exception("Fief d'origine non valide.");
        }

        $ins = $this->db->prepare("INSERT INTO farm_lists (user_id, source_planet_id, name) VALUES (?, ?, ?)");
        $ins->execute([$userId, $sourcePlanetId, $name]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Supprime une liste de raids
     */
    public function deleteFarmList(int $userId, int $listId): bool {
        $del = $this->db->prepare("DELETE FROM farm_lists WHERE id = ? AND user_id = ?");
        $del->execute([$listId, $userId]);
        return ($del->rowCount() > 0);
    }

    /**
     * Ajoute une cible dans une liste de raids
     */
    public function addFarmListEntry(
        int $userId,
        int $listId,
        string $targetType,
        int $targetId,
        string $targetName,
        int $x,
        int $y,
        array $fleetData
    ): array {
        // Vérifier la propriété de la liste
        $st = $this->db->prepare("SELECT fl.*, p.coord_x as sx, p.coord_y as sy FROM farm_lists fl JOIN planets p ON fl.source_planet_id = p.id WHERE fl.id = ? AND fl.user_id = ?");
        $st->execute([$listId, $userId]);
        $list = $st->fetch(PDO::FETCH_ASSOC);
        if (!$list) {
            throw new Exception("Liste de raids introuvable.");
        }

        // Nettoyer les troupes (au moins 1 unité)
        $cleanFleet = [];
        $totalUnits = 0;
        foreach ($fleetData as $code => $qty) {
            $q = (int)$qty;
            if ($q > 0) {
                $cleanFleet[$code] = $q;
                $totalUnits += $q;
            }
        }

        if ($totalUnits <= 0) {
            throw new Exception("Vous devez assigner au moins 1 guerrier à cette expédition de raid.");
        }

        // Calculer la distance
        require_once __DIR__ . '/FleetEngine.php';
        $distance = FleetEngine::calculateDistance((int)$list['sx'], (int)$list['sy'], $x, $y);

        $ins = $this->db->prepare("
            INSERT INTO farm_list_entries 
            (farm_list_id, target_type, target_id, target_name, coord_x, coord_y, fleet_data, distance) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $listId,
            $targetType,
            $targetId,
            $targetName,
            $x,
            $y,
            json_encode($cleanFleet),
            round($distance, 1)
        ]);

        return [
            'success' => true,
            'entry_id' => (int)$this->db->lastInsertId(),
            'distance' => round($distance, 1)
        ];
    }

    /**
     * Supprime une cible de la liste de raids
     */
    public function deleteFarmListEntry(int $userId, int $entryId): bool {
        $st = $this->db->prepare("
            DELETE fle FROM farm_list_entries fle
            JOIN farm_lists fl ON fle.farm_list_id = fl.id
            WHERE fle.id = ? AND fl.user_id = ?
        ");
        $st->execute([$entryId, $userId]);
        return ($st->rowCount() > 0);
    }

    /**
     * Lance un raid unique depuis le carnet
     */
    public function executeRaidEntry(int $userId, int $entryId): array {
        $st = $this->db->prepare("
            SELECT fle.*, fl.source_planet_id, fl.user_id 
            FROM farm_list_entries fle
            JOIN farm_lists fl ON fle.farm_list_id = fl.id
            WHERE fle.id = ? AND fl.user_id = ?
        ");
        $st->execute([$entryId, $userId]);
        $entry = $st->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            throw new Exception("Cible de raid introuvable.");
        }

        $sourcePlanetId = (int)$entry['source_planet_id'];
        $fleet = json_decode($entry['fleet_data'], true) ?: [];
        $targetPlanetId = ($entry['target_type'] === 'planet') ? (int)$entry['target_id'] : null;
        $targetOasisId = ($entry['target_type'] === 'oasis') ? (int)$entry['target_id'] : null;

        require_once __DIR__ . '/FleetEngine.php';
        $fleetEngine = new FleetEngine();

        try {
            $dispatchRes = $fleetEngine->dispatchMission(
                $userId,
                $sourcePlanetId,
                $targetPlanetId,
                'raid',
                $fleet,
                ['metal' => 0, 'crystal' => 0, 'deuterium' => 0],
                $targetOasisId,
                false
            );

            // Mettre à jour l'entrée
            $this->db->prepare("
                UPDATE farm_list_entries 
                SET last_raid_at = NOW(), last_status = 'en_route' 
                WHERE id = ?
            ")->execute([$entryId]);

            return [
                'success' => true,
                'message' => "Raid lancé vers {$entry['target_name']} !",
                'mission_id' => $dispatchRes['mission_id'] ?? null
            ];
        } catch (Exception $e) {
            $this->db->prepare("
                UPDATE farm_list_entries 
                SET last_status = 'error_troops' 
                WHERE id = ?
            ")->execute([$entryId]);
            throw $e;
        }
    }

    /**
     * Lance l'intégralité d'une liste de raids en 1 clic
     */
    public function executeFullFarmList(int $userId, int $listId): array {
        $st = $this->db->prepare("
            SELECT fl.*, p.name as source_planet_name 
            FROM farm_lists fl
            JOIN planets p ON fl.source_planet_id = p.id
            WHERE fl.id = ? AND fl.user_id = ?
        ");
        $st->execute([$listId, $userId]);
        $list = $st->fetch(PDO::FETCH_ASSOC);

        if (!$list) {
            throw new Exception("Liste de raids introuvable.");
        }

        $stEntries = $this->db->prepare("
            SELECT * FROM farm_list_entries 
            WHERE farm_list_id = ? 
            ORDER BY distance ASC
        ");
        $stEntries->execute([$listId]);
        $entries = $stEntries->fetchAll(PDO::FETCH_ASSOC);

        if (empty($entries)) {
            throw new Exception("Cette liste ne contient aucune cible de raid.");
        }

        require_once __DIR__ . '/FleetEngine.php';
        $fleetEngine = new FleetEngine();

        $launchedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($entries as $entry) {
            $entryId = (int)$entry['id'];
            $fleet = json_decode($entry['fleet_data'], true) ?: [];
            $targetPlanetId = ($entry['target_type'] === 'planet') ? (int)$entry['target_id'] : null;
            $targetOasisId = ($entry['target_type'] === 'oasis') ? (int)$entry['target_id'] : null;

            try {
                $fleetEngine->dispatchMission(
                    $userId,
                    (int)$list['source_planet_id'],
                    $targetPlanetId,
                    'raid',
                    $fleet,
                    ['metal' => 0, 'crystal' => 0, 'deuterium' => 0],
                    $targetOasisId,
                    false
                );

                $this->db->prepare("
                    UPDATE farm_list_entries 
                    SET last_raid_at = NOW(), last_status = 'en_route' 
                    WHERE id = ?
                ")->execute([$entryId]);

                $launchedCount++;
            } catch (Exception $e) {
                $failedCount++;
                $errors[] = "{$entry['target_name']} : " . $e->getMessage();
                $this->db->prepare("
                    UPDATE farm_list_entries 
                    SET last_status = 'error_troops' 
                    WHERE id = ?
                ")->execute([$entryId]);
            }
        }

        return [
            'success' => true,
            'launched' => $launchedCount,
            'failed' => $failedCount,
            'errors' => $errors,
            'message' => "Tournée exécutée : {$launchedCount} raid(s) déployé(s)" . ($failedCount > 0 ? " ({$failedCount} cible(s) non pourvues par manque de troupes)." : ".")
        ];
    }

    // =========================================================================
    // GRAND TABLEAU DE BORD DE L'EMPIRE (CONSOLIDATION MULTI-FIEFS)
    // =========================================================================

    /**
     * Récupère la totalité des données consolidées de tous les villages du joueur
     */
    public function getEmpireOverview(int $userId): array {
        require_once __DIR__ . '/PlanetEngine.php';
        $planetEngine = new PlanetEngine();

        $st = $this->db->prepare("SELECT id FROM planets WHERE user_id = ? ORDER BY is_capital DESC, id ASC");
        $st->execute([$userId]);
        $planetIds = $st->fetchAll(PDO::FETCH_COLUMN);

        $villages = [];
        $totals = [
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 0,
            'rice_flour' => 0,
            'sake' => 0,
            'prod_metal' => 0,
            'prod_crystal' => 0,
            'prod_deuterium' => 0,
            'population' => 0,
            'elite_upkeep_flour' => 0,
            'active_constructions_count' => 0
        ];

        // Charger unités pour noms/icônes
        $unitsDb = [];
        foreach ($this->db->query("SELECT code, name, icon FROM units")->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $unitsDb[$u['code']] = $u;
        }

        foreach ($planetIds as $pid) {
            $pData = $planetEngine->updatePlanet((int)$pid);
            $famine = $planetEngine->getEliteUnitsUpkeep((int)$pid);
            $feast = $planetEngine->getActiveFeast((int)$pid);

            // Chantiers en cours
            $stQ = $this->db->prepare("
                SELECT * FROM construction_queue 
                WHERE planet_id = ? 
                ORDER BY finishes_at ASC
            ");
            $stQ->execute([(int)$pid]);
            $queue = $stQ->fetchAll(PDO::FETCH_ASSOC);

            // Garnison stationnée
            $stUnits = $this->db->prepare("
                SELECT unit_code, count FROM planet_units 
                WHERE planet_id = ? AND count > 0 
                ORDER BY count DESC
            ");
            $stUnits->execute([(int)$pid]);
            $garrison = $stUnits->fetchAll(PDO::FETCH_ASSOC);

            // Cumul des totaux
            $totals['metal'] += (float)$pData['metal'];
            $totals['crystal'] += (float)$pData['crystal'];
            $totals['deuterium'] += (float)$pData['deuterium'];
            $totals['rice_flour'] += (float)($pData['rice_flour'] ?? 0);
            $totals['sake'] += (float)($pData['sake'] ?? 0);
            $totals['prod_metal'] += (float)$pData['prod_rates']['metal'];
            $totals['prod_crystal'] += (float)$pData['prod_rates']['crystal'];
            $totals['prod_deuterium'] += (float)$pData['prod_rates']['deuterium'];
            $totals['population'] += (int)($pData['population'] ?? 100);
            $totals['elite_upkeep_flour'] += (int)$famine['flour_consumption_per_hour'];
            $totals['active_constructions_count'] += count($queue);

            $villages[] = [
                'planet' => $pData,
                'famine' => $famine,
                'feast' => $feast,
                'queue' => $queue,
                'garrison' => $garrison,
                'tactical_evasion' => !empty($pData['tactical_evasion'])
            ];
        }

        return [
            'villages' => $villages,
            'totals' => $totals,
            'units_db' => $unitsDb,
            'is_seal_active' => $this->isSealActive($userId)
        ];
    }
}

