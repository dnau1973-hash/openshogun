<?php
/**
 * Moteur de Résolution des Combats Spatiaux et du Pillage
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/HonorEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class CombatEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    /**
     * Résout un affrontement spatial entre l'attaquant et le défenseur
     */
    public function resolveBattle(array $mission, array $targetPlanet, array $attackerUser, ?array $defenderUser): array {
        $attackerFleet = json_decode($mission['fleet_data'], true) ?: [];
        $targetPlanetId = (int)$targetPlanet['id'];

        // 1. Récupérer la flotte et l'armée en défense sur la planète cible
        $defenderFleet = [];
        $stmtDef = $this->db->prepare("SELECT ship_code as code, count FROM planet_ships WHERE planet_id = ? AND count > 0");
        $stmtDef->execute([$targetPlanetId]);
        foreach ($stmtDef->fetchAll() as $r) {
            $defenderFleet[$r['code']] = (int)$r['count'];
        }
        $stmtDefUnits = $this->db->prepare("SELECT unit_code as code, count FROM planet_units WHERE planet_id = ? AND count > 0");
        $stmtDefUnits->execute([$targetPlanetId]);
        foreach ($stmtDefUnits->fetchAll() as $r) {
            $defenderFleet[$r['code']] = (int)$r['count'];
        }

        // 2. Charger les stats de tous les vaisseaux et soldats
        $shipDb = [];
        $stmtAllShips = $this->db->query("SELECT * FROM ships");
        while ($row = $stmtAllShips->fetch()) {
            $shipDb[$row['code']] = $row;
        }
        $stmtAllUnits = $this->db->query("SELECT * FROM units");
        while ($row = $stmtAllUnits->fetch()) {
            $shipDb[$row['code']] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'attack' => (int)$row['attack'],
                'shield' => 0,
                'defense' => (int)$row['def_infantry'] + (int)$row['def_mech'],
                'speed' => (int)$row['speed'],
                'cargo_capacity' => (int)$row['cargo_capacity'],
                'is_unit' => true
            ];
        }

        // 3. Calcul de la puissance de combat initiale
        $attPower = 0;
        $attShield = 0;
        $attHull = 0;
        $cargoTotal = 0;

        foreach ($attackerFleet as $code => $count) {
            if ($count <= 0 || !isset($shipDb[$code])) continue;
            $s = $shipDb[$code];
            $attPower += $s['attack'] * $count;
            $attShield += $s['shield'] * $count;
            $attHull += $s['defense'] * $count;
            $cargoTotal += $s['cargo_capacity'] * $count;
        }

        $defPower = 0;
        $defShield = 0;
        $defHull = 0;

        foreach ($defenderFleet as $code => $count) {
            if ($count <= 0 || !isset($shipDb[$code])) continue;
            $s = $shipDb[$code];
            $defPower += $s['attack'] * $count;
            $defShield += $s['shield'] * $count;
            $defHull += $s['defense'] * $count;
        }

        // Récupérer le niveau des remparts / muraille féodale du village cible
        $targetBuildings = $this->planetEngine->getBuildings($targetPlanetId);
        $wallLvl = (int)($targetBuildings['wall'] ?? 0);
        $wallMult = 0.04;
        if ($defenderUser) {
            if ($defenderUser['faction'] === 'aethelis') { // Tokugawa : maîtres bâtisseurs défensifs
                $wallMult = 0.05;
            } elseif ($defenderUser['faction'] === 'vorash') { // Takeda : palissade rapide
                $wallMult = 0.035;
            }
        }
        $wallDefenseMultiplier = 1.0 + ($wallLvl * $wallMult);
        $wallStructuralDefense = $wallLvl * 25; // Points de structure des remparts
        $wallRipostePower = (int)($wallLvl * 5); // Tirs de courtine / meurtrières

        // Bonus faction défenseur Aethelis (+15% bouclier)
        if ($defenderUser && $defenderUser['faction'] === 'aethelis') {
            $defShield = (int)($defShield * 1.15);
        }

        // Application des bonus de la muraille féodale
        if ($wallLvl > 0) {
            $defShield = (int)($defShield * $wallDefenseMultiplier);
            $defHull = (int)($defHull * $wallDefenseMultiplier) + $wallStructuralDefense;
            $defPower += $wallRipostePower;
        }

        // Simulation de 3 rounds de combat
        $roundLogs = [];
        $currentAttFleet = $attackerFleet;
        $currentDefFleet = $defenderFleet;

        $rounds = 3;
        for ($r = 1; $r <= $rounds; $r++) {
            if ($attPower <= 0 || $defPower <= 0) break;

            // Dégâts infligés par l'attaquant (réduits par les boucliers du défenseur)
            $damageToDef = max(1, $attPower - ($defShield * 0.5));
            $damageToAtt = max(1, $defPower - ($attShield * 0.5));

            // Perte de vaisseaux défenseurs
            $defLossRatio = min(1.0, $damageToDef / max(1, $defHull));
            $attLossRatio = min(1.0, $damageToAtt / max(1, $attHull));

            // Appliquer les pertes
            foreach ($currentDefFleet as $code => $cnt) {
                $lost = (int)ceil($cnt * $defLossRatio * 0.5);
                $currentDefFleet[$code] = max(0, $cnt - $lost);
            }
            foreach ($currentAttFleet as $code => $cnt) {
                $lost = (int)ceil($cnt * $attLossRatio * 0.5);
                $currentAttFleet[$code] = max(0, $cnt - $lost);
            }

            // Recalculer les puissances pour le tour suivant
            $attPower = 0; $attShield = 0; $attHull = 0;
            foreach ($currentAttFleet as $code => $cnt) {
                if ($cnt <= 0) continue;
                $s = $shipDb[$code];
                $attPower += $s['attack'] * $cnt;
                $attShield += $s['shield'] * $cnt;
                $attHull += $s['defense'] * $cnt;
            }

            $defPower = 0; $defShield = 0; $defHull = 0;
            foreach ($currentDefFleet as $code => $cnt) {
                if ($cnt <= 0) continue;
                $s = $shipDb[$code];
                $defPower += $s['attack'] * $cnt;
                $defShield += $s['shield'] * $cnt;
                $defHull += $s['defense'] * $cnt;
            }

            $roundLogs[] = [
                'round' => $r,
                'att_remaining' => array_sum($currentAttFleet),
                'def_remaining' => array_sum($currentDefFleet)
            ];
        }

        // Déterminer le vainqueur
        $totalAttRemaining = array_sum($currentAttFleet);
        $totalDefRemaining = array_sum($currentDefFleet);

        if ($totalAttRemaining > 0 && $totalDefRemaining === 0) {
            $winner = 'attacker';
        } elseif ($totalAttRemaining === 0 && $totalDefRemaining > 0) {
            $winner = 'defender';
        } else {
            $winner = ($totalAttRemaining >= $totalDefRemaining) ? 'attacker' : 'defender';
        }

        // Calcul des pertes
        $attLost = [];
        foreach ($attackerFleet as $code => $initialCount) {
            $rem = $currentAttFleet[$code] ?? 0;
            $lost = $initialCount - $rem;
            if ($lost > 0) $attLost[$code] = $lost;
        }

        $defLost = [];
        foreach ($defenderFleet as $code => $initialCount) {
            $rem = $currentDefFleet[$code] ?? 0;
            $lost = $initialCount - $rem;
            if ($lost > 0) $defLost[$code] = $lost;
        }

        // Mettre à jour les troupes et vaisseaux restants du défenseur sur sa planète
        foreach ($defenderFleet as $code => $cnt) {
            $rem = $currentDefFleet[$code] ?? 0;
            if (!empty($shipDb[$code]['is_unit'])) {
                $this->db->prepare("UPDATE planet_units SET count = ? WHERE planet_id = ? AND unit_code = ?")
                    ->execute([$rem, $targetPlanetId, $code]);
            } else {
                $this->db->prepare("UPDATE planet_ships SET count = ? WHERE planet_id = ? AND ship_code = ?")
                    ->execute([$rem, $targetPlanetId, $code]);
            }
        }

        // Calcul du pillage si l'attaquant a vaincu
        $looted = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        if ($winner === 'attacker' && $totalAttRemaining > 0 && $cargoTotal > 0) {
            $targetPlanet = $this->planetEngine->updatePlanet($targetPlanetId);
            $defFaction = $defenderUser['faction'] ?? 'terran';
            $vaultCap = $this->planetEngine->getVaultCapacity($targetPlanetId, $defFaction);

            // Bonus racial Vorash : ignore 25% de la cachette adverse
            if ($attackerUser['faction'] === 'vorash') {
                $vaultCap = (int)($vaultCap * 0.75);
            }

            $plunderableMetal = max(0, $targetPlanet['metal'] - $vaultCap);
            $plunderableCrystal = max(0, $targetPlanet['crystal'] - $vaultCap);
            $plunderableDeut = max(0, $targetPlanet['deuterium'] - $vaultCap);

            $maxRaidRatio = ($mission['mission_type'] === 'raid') ? 0.66 : 0.50;

            $stealMetal = min((int)($plunderableMetal * $maxRaidRatio), (int)($cargoTotal / 3));
            $stealCrystal = min((int)($plunderableCrystal * $maxRaidRatio), (int)($cargoTotal / 3));
            $stealDeut = min((int)($plunderableDeut * $maxRaidRatio), (int)($cargoTotal / 3));

            $looted = [
                'metal' => max(0, $stealMetal),
                'crystal' => max(0, $stealCrystal),
                'deuterium' => max(0, $stealDeut),
            ];

            // Déduire les ressources de la planète cible
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
                WHERE id = ?
            ")->execute([$looted['metal'], $looted['crystal'], $looted['deuterium'], $targetPlanetId]);
        }

        // Création du rapport de combat
        $reportData = [
            'attacker_name' => $attackerUser['username'],
            'defender_name' => $defenderUser['username'] ?? 'Fief Neutre',
            'attacker_faction' => $attackerUser['faction'],
            'defender_faction' => $defenderUser['faction'] ?? 'terran',
            'target_coords' => "({$targetPlanet['coord_x']}, {$targetPlanet['coord_y']})",
            'attacker_fleet_initial' => $attackerFleet,
            'defender_fleet_initial' => $defenderFleet,
            'attacker_survivors' => $currentAttFleet,
            'defender_survivors' => $currentDefFleet,
            'attacker_lost' => $attLost,
            'defender_lost' => $defLost,
            'looted' => $looted,
            'rounds' => $roundLogs,
            'winner' => $winner
        ];

        $title = "Bataille provinciale en ({$targetPlanet['coord_x']}, {$targetPlanet['coord_y']}) : Victoire de " . 
                 ($winner === 'attacker' ? $attackerUser['username'] : ($defenderUser['username'] ?? 'la Garnison'));

        $stmtReport = $this->db->prepare("
            INSERT INTO combat_reports 
            (attacker_id, defender_id, attacker_planet_id, defender_planet_id, mission_type, title, report_data, winner, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
        ");
        $stmtReport->execute([
            $attackerUser['id'],
            $defenderUser['id'] ?? 0,
            $mission['source_planet_id'],
            $targetPlanetId,
            $mission['mission_type'],
            $title,
            json_encode($reportData, JSON_UNESCAPED_UNICODE),
            $winner
        ]);

        // Mettre à jour les statistiques pour le Tableau d'Honneur de la semaine
        try {
            $honorEngine = new HonorEngine();
            $lootSum = (int)($looted['metal'] ?? 0) + (int)($looted['crystal'] ?? 0) + (int)($looted['deuterium'] ?? 0);
            $attPoints = ($winner === 'attacker' ? 100 : 25) + count($defLost) * 15;
            $defPoints = ($winner === 'defender' ? 100 : 25) + count($attLost) * 15;
            $honorEngine->updateCombatStats(
                (int)$attackerUser['id'], 
                !empty($defenderUser['id']) ? (int)$defenderUser['id'] : null, 
                $attPoints, 
                $defPoints, 
                $lootSum
            );
        } catch (Exception $e) {
            // Ignorer silencieusement pour ne pas bloquer le combat
        }

        return [
            'winner' => $winner,
            'survivors' => $currentAttFleet,
            'looted' => $looted,
            'report_id' => $this->db->lastInsertId()
        ];
    }
}

