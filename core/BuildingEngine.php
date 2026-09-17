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
    public function getUpgradeDetails(string $category, string $targetId, int $currentLevel, int $hqLevel): array {
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

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        // Facteur de réduction par le QG et vitesse de jeu
        $duration = max(2, (int)(($baseTime * $targetLevel * 2) / ((1 + ($hqLevel * 0.25)) * $speed)));

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
            if ($fieldSlot < 1 || $fieldSlot > 18) {
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

        // 2. Vérification des files existantes et du bonus racial Terran
        $queue = $this->getQueue($planetId);
        $fieldsInQueue = 0;
        $buildingsInQueue = 0;

        foreach ($queue as $q) {
            if ($q['build_category'] === 'field') $fieldsInQueue++;
            else $buildingsInQueue++;

            // Empêcher d'améliorer deux fois la même cible en même temps
            if ($q['build_category'] === $category && $q['target_id'] == $targetId) {
                throw new Exception("Cette structure est déjà en cours d'amélioration.");
            }
        }

        if ($faction === 'terran') {
            // Terrans : 1 champ et 1 bâtiment en simultané
            if ($category === 'field' && $fieldsInQueue >= 1) {
                throw new Exception("Une parcelle minière est déjà en cours d'amélioration.");
            }
            if ($category === 'building' && $buildingsInQueue >= 1) {
                throw new Exception("Une infrastructure de la cité est déjà en cours de construction.");
            }
        } else {
            // Vorash & Aethelis : 1 seule construction à la fois
            if (count($queue) >= 1) {
                throw new Exception("Une construction est déjà en cours sur cette planète.");
            }
        }

        // 3. Calcul du coût et de la durée
        $details = $this->getUpgradeDetails($category, $type, $currentLevel, $hqLevel);
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

        // 6. Ajout à la file de construction
        $now = time();
        $finishesAt = $now + $duration;

        $stmtInsert = $this->db->prepare("
            INSERT INTO construction_queue 
            (planet_id, build_category, target_id, target_level, started_at, finishes_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$planetId, $category, $targetId, $details['target_level'], $now, $finishesAt]);
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
     * Démolit / supprime définitivement un bâtiment ou une parcelle
     * Libère l'emplacement (devient free_plot / niveau 0) et rembourse 30% des matériaux
     */
    public function demolish(int $planetId, string $category, string $targetId, ?int $slot = null): array {
        $buildings = $this->planetEngine->getBuildings($planetId);
        $hqLevel = (int)($buildings['hq'] ?? 1);

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

            // Calcul du remboursement (30% du coût du niveau actuel)
            $refLevel = max(1, $currentLevel);
            $details = $this->getUpgradeDetails('building', $buildingType, $refLevel - 1, $hqLevel);
            $refundMetal = ($currentLevel > 0) ? (int)($details['cost']['metal'] * 0.3) : 0;
            $refundCrystal = ($currentLevel > 0) ? (int)($details['cost']['crystal'] * 0.3) : 0;
            $refundDeut = ($currentLevel > 0) ? (int)($details['cost']['deuterium'] * 0.3) : 0;

            $this->db->beginTransaction();

            // Supprimer les constructions en cours pour ce bâtiment
            $this->db->prepare("DELETE FROM construction_queue WHERE planet_id = ? AND build_category = 'building' AND target_id = ?")
                ->execute([$planetId, $buildingType]);

            // Supprimer le bâtiment de planet_buildings pour libérer le slot
            $this->db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND building_type = ?")
                ->execute([$planetId, $buildingType]);

            // Créditer les matériaux récupérés
            if ($refundMetal > 0 || $refundCrystal > 0 || $refundDeut > 0) {
                $this->db->prepare("
                    UPDATE planets 
                    SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                    WHERE id = ?
                ")->execute([$refundMetal, $refundCrystal, $refundDeut, $planetId]);
            }

            $this->db->commit();

            $bName = BUILDINGS[$buildingType]['name'] ?? $buildingType;
            $slotText = $actualSlot ? " (Emplacement #$actualSlot)" : "";

            return [
                'success' => true,
                'message' => "Le bâtiment $bName$slotText a été démantelé avec succès. L'emplacement est désormais libre !",
                'refund' => [
                    'metal' => $refundMetal,
                    'crystal' => $refundCrystal,
                    'deuterium' => $refundDeut
                ]
            ];
        } else {
            // Parcelle rurale
            $fieldSlot = (int)$targetId;
            if ($fieldSlot < 1 || $fieldSlot > 18) {
                $fieldSlot = (int)($slot ?? 0);
            }
            if ($fieldSlot < 1 || $fieldSlot > 18) {
                throw new Exception("Emplacement de parcelle invalide (#$fieldSlot).");
            }

            $stmtF = $this->db->prepare("SELECT type, level FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
            $stmtF->execute([$planetId, $fieldSlot]);
            $field = $stmtF->fetch();

            if (!$field || (int)$field['level'] <= 0) {
                $stmtQ = $this->db->prepare("SELECT id FROM construction_queue WHERE planet_id = ? AND build_category = 'field' AND target_id = ?");
                $stmtQ->execute([$planetId, $fieldSlot]);
                $inQ = $stmtQ->fetch();
                if (!$inQ && (!$field || (int)$field['level'] === 0)) {
                    throw new Exception("Cette parcelle est déjà vierge.");
                }
            }

            $currentLevel = (int)($field['level'] ?? 0);
            $fieldType = $field['type'] ?? 'metal_mine';

            $refLevel = max(1, $currentLevel);
            $details = $this->getUpgradeDetails('field', $fieldType, $refLevel - 1, $hqLevel);
            $refundMetal = ($currentLevel > 0) ? (int)($details['cost']['metal'] * 0.3) : 0;
            $refundCrystal = ($currentLevel > 0) ? (int)($details['cost']['crystal'] * 0.3) : 0;
            $refundDeut = ($currentLevel > 0) ? (int)($details['cost']['deuterium'] * 0.3) : 0;

            $this->db->beginTransaction();

            // Supprimer de la file
            $this->db->prepare("DELETE FROM construction_queue WHERE planet_id = ? AND build_category = 'field' AND target_id = ?")
                ->execute([$planetId, $fieldSlot]);

            // Réinitialiser la parcelle à 0
            $this->db->prepare("DELETE FROM planet_fields WHERE planet_id = ? AND field_slot = ?")
                ->execute([$planetId, $fieldSlot]);

            // Créditer les matériaux récupérés
            if ($refundMetal > 0 || $refundCrystal > 0 || $refundDeut > 0) {
                $this->db->prepare("
                    UPDATE planets 
                    SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                    WHERE id = ?
                ")->execute([$refundMetal, $refundCrystal, $refundDeut, $planetId]);
            }

            $this->db->commit();

            $fName = FIELD_TYPES[$fieldType]['name'] ?? $fieldType;

            return [
                'success' => true,
                'message' => "L'exploitation $fName sur la parcelle #$fieldSlot a été rasée. L'emplacement est désormais vierge !",
                'refund' => [
                    'metal' => $refundMetal,
                    'crystal' => $refundCrystal,
                    'deuterium' => $refundDeut
                ]
            ];
        }
    }
}

