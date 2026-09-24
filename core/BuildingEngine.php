<?php
/**
 * Moteur de construction des bâtiments et parcelles
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class BuildingEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    /**
     * Récupère la file active de construction d'une planète
     */
    public function getQueue(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM construction_queue 
            WHERE planet_id = ? 
            ORDER BY finishes_at ASC
        ");
        $stmt->execute([$planetId]);
        return $stmt->fetchAll();
    }

    /**
     * Calcule le coût et le temps pour améliorer une parcelle ou un bâtiment
     */
    public function getUpgradeDetails(string $category, string $targetId, int $currentLevel, int $hqLevel, int $population = 0): array {
        if ($category === 'field') {
            $fieldConf = FIELD_TYPES[$targetId] ?? null;
            if (!$fieldConf) throw new Exception("Type de parcelle inconnu.");
            $targetLevel = $currentLevel + 1;
            $mult = pow($fieldConf['cost_multiplier'], $currentLevel);
            $metal = (int)($fieldConf['base_cost']['metal'] * $mult);
            $crystal = (int)($fieldConf['base_cost']['crystal'] * $mult);
            $deut = (int)($fieldConf['base_cost']['deuterium'] * $mult);
            $baseTime = $fieldConf['base_time'];
        } else {
            $bConf = BUILDINGS[$targetId] ?? null;
            if (!$bConf) throw new Exception("Type de bâtiment inconnu.");
            $targetLevel = $currentLevel + 1;
            $mult = pow($bConf['cost_multiplier'], $currentLevel);
            $metal = (int)($bConf['base_cost']['metal'] * $mult);
            $crystal = (int)($bConf['base_cost']['crystal'] * $mult);
            $deut = (int)($bConf['base_cost']['deuterium'] * $mult);
            $baseTime = $bConf['base_time'];
        }

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        // Échelle de temps authentique Travian : réduction par le Tenshu (0.964^(hq-1)) et progression exponentielle
        $hqFactor = pow(0.964, max(0, $hqLevel - 1));
        $lvlFactor = pow(1.28, $currentLevel) * pow($targetLevel, 0.85);

        // Bonus démographique de main-d'œuvre : +1% de vitesse de construction tous les 100 habitants (plafonné à 25%)
        $popBonus = min(0.25, max(0, $population / 100) * 0.01);
        $popFactor = 1.0 / (1.0 + $popBonus);

        $duration = max(15, (int)(($baseTime * $lvlFactor * $hqFactor * $popFactor) / $speed));

        return [
            'target_level' => $targetLevel,
            'cost' => [
                'metal' => $metal,
                'crystal' => $crystal,
                'deuterium' => $deut
            ],
            'duration' => $duration
        ];
    }

    /**
     * Lance la construction d'une amélioration
     */
    public function startUpgrade(int $planetId, string $category, string $targetId, ?int $slot = null, ?string $fieldType = null): array {
        $planet = $this->planetEngine->updatePlanet($planetId);
        $faction = $planet['faction'] ?? 'terran';
        $buildings = $this->planetEngine->getBuildings($planetId);
        $hqLevel = $buildings['hq'] ?? 1;

        // 1. Vérification du niveau actuel
        if ($category === 'field') {
            $fieldSlot = (int)$targetId;
            if ($fieldSlot < 1 || $fieldSlot > 20) {
                throw new Exception("Emplacement de parcelle invalide (#$fieldSlot).");
            }
            $stmtField = $this->db->prepare("SELECT * FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
            $stmtField->execute([$planetId, $fieldSlot]);
            $field = $stmtField->fetch();

            if (!$field) {
                $type = ($fieldType && isset(FIELD_TYPES[$fieldType])) ? $fieldType : 'metal_mine';
                $currentLevel = 0;
                $this->db->prepare("INSERT INTO planet_fields (planet_id, field_slot, type, level) VALUES (?, ?, ?, 0)")
                    ->execute([$planetId, $fieldSlot, $type]);
            } else {
                $currentLevel = (int)$field['level'];
                if ($currentLevel === 0 && $fieldType && isset(FIELD_TYPES[$fieldType])) {
                    $type = $fieldType;
                    $this->db->prepare("UPDATE planet_fields SET type = ? WHERE planet_id = ? AND field_slot = ?")
                        ->execute([$type, $planetId, $fieldSlot]);
                } else {
                    $type = $field['type'];
                }
            }
        } else {
            $type = $targetId;
            $currentLevel = (int)($buildings[$type] ?? 0);

            // Vérification du bâtiment existant et de son slot
            $stmtExisting = $this->db->prepare("SELECT slot, level FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
            $stmtExisting->execute([$planetId, $type]);
            $existingBuilding = $stmtExisting->fetch();

            if ($existingBuilding && (int)$existingBuilding['level'] > 0) {
                if ($slot !== null && !empty($existingBuilding['slot']) && (int)$existingBuilding['slot'] !== (int)$slot) {
                    throw new Exception("Ce bâtiment est déjà érigé sur l'emplacement #" . $existingBuilding['slot'] . ".");
                }
            } else {
                // Nouveau bâtiment (niveau 0) à fonder
                if ($slot !== null) {
                    $slot = (int)$slot;
                    if ($slot < 19 || $slot > 34) {
                        throw new Exception("Emplacement urbain invalide (#$slot).");
                    }
                    // Vérifier si le slot est déjà pris
                    $stmtSlotTaken = $this->db->prepare("
                        SELECT building_type FROM planet_buildings 
                        WHERE planet_id = ? AND slot = ? AND building_type != ?
                    ");
                    $stmtSlotTaken->execute([$planetId, $slot, $type]);
                    $taken = $stmtSlotTaken->fetch();
                    if ($taken) {
                        $occupiedName = BUILDINGS[$taken['building_type']]['name'] ?? $taken['building_type'];
                        throw new Exception("L'emplacement #$slot est déjà occupé par $occupiedName.");
                    }

                    // Enregistrer le bâtiment au niveau 0 sur ce slot
                    $this->db->prepare("
                        INSERT INTO planet_buildings (planet_id, slot, building_type, level) 
                        VALUES (?, ?, ?, 0) 
                        ON DUPLICATE KEY UPDATE slot = VALUES(slot)
                    ")->execute([$planetId, $slot, $type]);
                }
            }
        }

        // 2. Vérification des files existantes, du Sceau Impérial (Architecte de Cour) et du bonus racial Terran
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);

        $queue = $this->getQueue($planetId);
        $fieldsInQueue = 0;
        $buildingsInQueue = 0;
        $maxFinishesAtCategory = time();
        $maxFinishesAtGlobal = time();

        foreach ($queue as $q) {
            $qFin = (int)$q['finishes_at'];
            if ($qFin > $maxFinishesAtGlobal) {
                $maxFinishesAtGlobal = $qFin;
            }

            if ($q['build_category'] === 'field') {
                $fieldsInQueue++;
                if ($category === 'field' && $qFin > $maxFinishesAtCategory) {
                    $maxFinishesAtCategory = $qFin;
                }
            } else {
                $buildingsInQueue++;
                if ($category === 'building' && $qFin > $maxFinishesAtCategory) {
                    $maxFinishesAtCategory = $qFin;
                }
            }

            // Empêcher d'améliorer deux fois la même cible en même temps dans la file
            if ($q['build_category'] === $category && $q['target_id'] == $targetId) {
                throw new Exception("Cette structure est déjà en cours d'amélioration dans votre file castrale.");
            }
        }

        if ($faction === 'terran') {
            $maxAllowedPerCategory = $isSealActive ? 2 : 1;
            if ($category === 'field' && $fieldsInQueue >= $maxAllowedPerCategory) {
                $msg = $isSealActive 
                    ? "Votre file de parcelles agricoles et minières est déjà saturée (maximum 2 chantiers enchaînés)." 
                    : "Une parcelle rurale est déjà en cours d'amélioration. Décrétez le Sceau Impérial pour mettre en file jusqu'à 2 travaux en attente !";
                throw new Exception($msg);
            }
            if ($category === 'building' && $buildingsInQueue >= $maxAllowedPerCategory) {
                $msg = $isSealActive 
                    ? "Votre file d'infrastructures urbaines est déjà saturée (maximum 2 édifices enchaînés)." 
                    : "Une infrastructure de la cité est déjà en cours de construction. Décrétez le Sceau Impérial pour enchaîner vos chantiers !";
                throw new Exception($msg);
            }
        } else {
            $maxAllowedTotal = $isSealActive ? 3 : 1;
            if (count($queue) >= $maxAllowedTotal) {
                $msg = $isSealActive 
                    ? "Votre Architecte de Cour a déjà planifié 3 chantiers simultanés sur ce domaine." 
                    : "Une construction est déjà en cours sur cette province. Décrétez le Sceau Impérial pour planifier jusqu'à 3 chantiers en file !";
                throw new Exception($msg);
            }
        }

        // 3. Calcul du coût et de la durée (avec prise en compte de la population)
        $population = (int)($planet['population'] ?? 100);
        $details = $this->getUpgradeDetails($category, $type, $currentLevel, $hqLevel, $population);
        $cost = $details['cost'];
        $duration = $details['duration'];

        // 4. Vérification des ressources disponibles
        if ($planet['metal'] < $cost['metal'] || $planet['crystal'] < $cost['crystal'] || $planet['deuterium'] < $cost['deuterium']) {
            throw new Exception("Ressources insuffisantes pour lancer cette construction.");
        }

        // 5. Déduction des ressources
        $stmtDeduct = $this->db->prepare("
            UPDATE planets 
            SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
            WHERE id = ?
        ");
        $stmtDeduct->execute([$cost['metal'], $cost['crystal'], $cost['deuterium'], $planetId]);

        // 6. Ajout à la file de construction (enchaînement séquentiel si un chantier précédent est en cours)
        $now = time();
        $startTime = ($faction === 'terran') ? max($now, $maxFinishesAtCategory) : max($now, $maxFinishesAtGlobal);
        $finishesAt = $startTime + $duration;

        $stmtInsert = $this->db->prepare("
            INSERT INTO construction_queue 
            (planet_id, build_category, target_id, target_level, started_at, finishes_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$planetId, $category, $targetId, $details['target_level'], $startTime, $finishesAt]);
        $queueId = (int)$this->db->lastInsertId();

        return [
            'success' => true,
            'message' => 'Construction initiée avec succès !',
            'queue_id' => $queueId,
            'finishes_at' => $finishesAt,
            'duration' => $duration
        ];
    }

    /**
     * Annule une construction en cours et rembourse 80% des ressources
     */
    public function cancelUpgrade(int $planetId, int $queueId): bool {
        $stmt = $this->db->prepare("SELECT * FROM construction_queue WHERE id = ? AND planet_id = ?");
        $stmt->execute([$queueId, $planetId]);
        $item = $stmt->fetch();
        if (!$item) return false;

        // Si on annule une démolition (target_level = 0), retirer de la file sans impacter les ressources : la structure reste intacte
        if ((int)$item['target_level'] === 0) {
            $this->db->prepare("DELETE FROM construction_queue WHERE id = ?")->execute([$queueId]);
            return true;
        }

        $buildings = $this->planetEngine->getBuildings($planetId);
        $hqLevel = $buildings['hq'] ?? 1;

        if ($item['build_category'] === 'field') {
            $slot = (int)$item['target_id'];
            $st = $this->db->prepare("SELECT type, level FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
            $st->execute([$planetId, $slot]);
            $field = $st->fetch();
            $type = $field['type'];
            $curLvl = (int)$field['level'];
        } else {
            $type = $item['target_id'];
            $curLvl = (int)($buildings[$type] ?? 0);
        }

        $details = $this->getUpgradeDetails($item['build_category'], $type, $curLvl, $hqLevel);
        $refundMetal = (int)($details['cost']['metal'] * 0.8);
        $refundCrystal = (int)($details['cost']['crystal'] * 0.8);
        $refundDeut = (int)($details['cost']['deuterium'] * 0.8);

        $this->db->beginTransaction();
        $this->db->prepare("
            UPDATE planets 
            SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
            WHERE id = ?
        ")->execute([$refundMetal, $refundCrystal, $refundDeut, $planetId]);

        $this->db->prepare("DELETE FROM construction_queue WHERE id = ?")->execute([$queueId]);

        // Si on annulait le niveau 1 d'un bâtiment qui était au niveau 0 en base, libérer l'emplacement
        if ($item['build_category'] === 'building' && (int)$item['target_level'] === 1 && $curLvl === 0) {
            $this->db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND building_type = ? AND level = 0")
                ->execute([$planetId, $type]);
        }

        $this->db->commit();

        return true;
    }

    /**
     * Lance un ordre de démantèlement / démolition avec compte à rebours.
     * La structure reste en place pendant les travaux et n'est supprimée qu'à l'achèvement,
     * libérant ainsi le slot et créditant 30% de remboursement des matériaux.
     */
    public function demolish(int $planetId, string $category, string $targetId, ?int $slot = null): array {
        $buildings = $this->planetEngine->getBuildings($planetId);
        $hqLevel = (int)($buildings['hq'] ?? 1);

        // Récupérer la faction du joueur
        $stmtOwner = $this->db->prepare("SELECT u.faction FROM planets p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmtOwner->execute([$planetId]);
        $faction = $stmtOwner->fetchColumn() ?: 'terran';

        if ($category === 'building') {
            $buildingType = $targetId;

            // Si le type n'est pas fourni, le déduire du slot
            if (empty($buildingType) && $slot !== null) {
                $stmtSlot = $this->db->prepare("SELECT building_type FROM planet_buildings WHERE planet_id = ? AND slot = ?");
                $stmtSlot->execute([$planetId, $slot]);
                $row = $stmtSlot->fetch();
                if ($row) {
                    $buildingType = $row['building_type'];
                }
            }

            if (empty($buildingType)) {
                throw new Exception("Bâtiment non spécifié.");
            }

            // Sécurité : le Tenshu Donjon ne peut pas être rasé
            if ($buildingType === 'hq') {
                throw new Exception("Le Donjon Tenshu est le cœur de votre domaine castral et ne peut pas être démantelé.");
            }

            // Récupérer le bâtiment en base
            $stmtB = $this->db->prepare("SELECT id, slot, level FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
            $stmtB->execute([$planetId, $buildingType]);
            $bData = $stmtB->fetch();

            if (!$bData && !isset($buildings[$buildingType])) {
                throw new Exception("Ce bâtiment n'existe pas sur cette forteresse.");
            }

            $currentLevel = (int)($bData['level'] ?? $buildings[$buildingType] ?? 0);
            $actualSlot = $bData['slot'] ?? $slot;

            if ($currentLevel <= 0) {
                throw new Exception("Ce bâtiment est déjà au niveau 0.");
            }

            // Vérifications des activités dépendantes en cours
            if ($buildingType === 'research_lab') {
                $stmtQ = $this->db->prepare("SELECT COUNT(*) FROM research_queue WHERE planet_id = ?");
                $stmtQ->execute([$planetId]);
                if ($stmtQ->fetchColumn() > 0) {
                    throw new Exception("Impossible de démanteler l'Académie : des recherches sont en cours.");
                }
            } elseif ($buildingType === 'barracks') {
                $stmtQ = $this->db->prepare("SELECT COUNT(*) FROM barracks_queue WHERE planet_id = ?");
                $stmtQ->execute([$planetId]);
                if ($stmtQ->fetchColumn() > 0) {
                    throw new Exception("Impossible de démanteler le Dojo : des recrues sont en formation.");
                }
            } elseif ($buildingType === 'shipyard') {
                $stmtQ = $this->db->prepare("SELECT COUNT(*) FROM shipyard_queue WHERE planet_id = ?");
                $stmtQ->execute([$planetId]);
                if ($stmtQ->fetchColumn() > 0) {
                    throw new Exception("Impossible de démanteler les Écuries & l'Atelier : des engins ou montures sont en cours de formation.");
                }
            }

            $type = $buildingType;
            $name = BUILDINGS[$buildingType]['name'] ?? $buildingType;
            $slotText = $actualSlot ? " (Emplacement #$actualSlot)" : "";
        } else {
            // Parcelle rurale
            $fieldSlot = (int)$targetId;
            if ($fieldSlot < 1 || $fieldSlot > 20) {
                $fieldSlot = (int)($slot ?? 0);
            }
            if ($fieldSlot < 1 || $fieldSlot > 20) {
                throw new Exception("Emplacement de parcelle invalide (#$fieldSlot).");
            }

            $stmtF = $this->db->prepare("SELECT type, level FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
            $stmtF->execute([$planetId, $fieldSlot]);
            $field = $stmtF->fetch();

            $currentLevel = (int)($field['level'] ?? 0);
            $fieldType = $field['type'] ?? 'metal_mine';

            if ($currentLevel <= 0) {
                throw new Exception("Cette parcelle est déjà vierge.");
            }

            $type = $fieldType;
            $name = (FIELD_TYPES[$fieldType]['name'] ?? 'Parcelle') . " #$fieldSlot";
            $slotText = "";
        }

        // Vérification des files de construction
        $queue = $this->getQueue($planetId);
        $fieldsInQueue = 0;
        $buildingsInQueue = 0;

        foreach ($queue as $q) {
            if ($q['build_category'] === 'field') $fieldsInQueue++;
            else $buildingsInQueue++;

            // Empêcher d'intervenir deux fois sur la même cible
            if ($q['build_category'] === $category && $q['target_id'] == $targetId) {
                throw new Exception("Cette structure a déjà un ordre de travaux ou de démantèlement en cours.");
            }
        }

        if ($faction === 'terran') {
            if ($category === 'field' && $fieldsInQueue >= 1) {
                throw new Exception("Une parcelle est déjà mobilisée dans la file des chantiers.");
            }
            if ($category === 'building' && $buildingsInQueue >= 1) {
                throw new Exception("Une infrastructure urbaine est déjà mobilisée dans la file des chantiers.");
            }
        } else {
            if (count($queue) >= 1) {
                throw new Exception("Vos bâtisseurs sont déjà mobilisés sur un autre chantier.");
            }
        }

        // Durée du démantèlement : 50% de la durée de construction, min 10 secondes
        $details = $this->getUpgradeDetails($category, $type, max(0, $currentLevel - 1), $hqLevel);
        $duration = max(10, (int)($details['duration'] * 0.5));
        $now = time();
        $finishesAt = $now + $duration;

        $this->db->prepare("
            INSERT INTO construction_queue 
            (planet_id, build_category, target_id, target_level, started_at, finishes_at)
            VALUES (?, ?, ?, 0, ?, ?)
        ")->execute([$planetId, $category, $targetId, $now, $finishesAt]);

        return [
            'success' => true,
            'message' => "Ordre de démantèlement ordonné pour $name$slotText. Achèvement des travaux dans " . gmdate('i:s', $duration) . ".",
            'duration' => $duration,
            'finishes_at' => $finishesAt
        ];
    }
}

