<?php
/**
 * Moteur de gestion des Oasis Sauvages, Faune et Annexions (Style Travian)
 * OpenShogun
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PlanetEngine.php';

class OasisEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère toutes les oasis situées dans une zone rectangulaire de la carte
     */
    public function getOasesInSector(int $minX, int $maxX, int $minY, int $maxY): array {
        $stmt = $this->db->prepare("
            SELECT o.*, p.name as owner_planet_name, u.username as owner_username, u.faction as owner_faction
            FROM oases o
            LEFT JOIN planets p ON o.owner_planet_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE o.coord_x BETWEEN ? AND ? AND o.coord_y BETWEEN ? AND ?
        ");
        $stmt->execute([$minX, $maxX, $minY, $maxY]);
        $oases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($oases as $o) {
            $k = $o['coord_x'] . ':' . $o['coord_y'];
            $o['units'] = $this->getOasisGarrison((int)$o['id']);
            $result[$k] = $o;
        }
        return $result;
    }

    /**
     * Récupère une oasis par son ID
     */
    public function getOasisById(int $oasisId): ?array {
        $stmt = $this->db->prepare("
            SELECT o.*, p.name as owner_planet_name, u.username as owner_username, u.faction as owner_faction
            FROM oases o
            LEFT JOIN planets p ON o.owner_planet_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$oasisId]);
        $oasis = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$oasis) return null;

        $oasis['units'] = $this->getOasisGarrison($oasisId);
        return $oasis;
    }

    /**
     * Récupère une oasis par ses coordonnées (X, Y)
     */
    public function getOasisAt(int $x, int $y): ?array {
        $stmt = $this->db->prepare("
            SELECT o.*, p.name as owner_planet_name, u.username as owner_username, u.faction as owner_faction
            FROM oases o
            LEFT JOIN planets p ON o.owner_planet_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE o.coord_x = ? AND o.coord_y = ?
        ");
        $stmt->execute([$x, $y]);
        $oasis = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$oasis) return null;

        $oasis['units'] = $this->getOasisGarrison((int)$oasis['id']);
        return $oasis;
    }

    /**
     * Récupère les unités en garnison dans l'oasis (faune sauvage ou soldats d'occupation)
     */
    public function getOasisGarrison(int $oasisId): array {
        $stmt = $this->db->prepare("
            SELECT ou.*, u.name as unit_name, u.icon, u.image, u.attack, u.def_infantry, u.def_mech, u.tier
            FROM oasis_units ou
            JOIN units u ON ou.unit_code = u.code
            WHERE ou.oasis_id = ? AND ou.count > 0
            ORDER BY ou.is_wild DESC, u.tier ASC
        ");
        $stmt->execute([$oasisId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si l'oasis est entièrement pacifiée (aucun animal sauvage survivant)
     */
    public function isOasisPacified(int $oasisId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM oasis_units 
            WHERE oasis_id = ? AND is_wild = 1 AND count > 0
        ");
        $stmt->execute([$oasisId]);
        return ((int)$stmt->fetchColumn() === 0);
    }

    /**
     * Calcule le total des bonus conférés par les oasis annexées d'un village
     */
    public function getTotalOasisBonusesForPlanet(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(bonus_wood), 0) as total_bonus_wood,
                COALESCE(SUM(bonus_stone), 0) as total_bonus_stone,
                COALESCE(SUM(bonus_rice), 0) as total_bonus_rice
            FROM oases
            WHERE owner_planet_id = ?
        ");
        $stmt->execute([$planetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'wood'  => (int)($row['total_bonus_wood'] ?? 0),
            'stone' => (int)($row['total_bonus_stone'] ?? 0),
            'rice'  => (int)($row['total_bonus_rice'] ?? 0),
        ];
    }

    /**
     * Récupère la liste des oasis annexées par un fief
     */
    public function getAnnexedOasesForPlanet(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM oases 
            WHERE owner_planet_id = ? 
            ORDER BY annexed_at DESC
        ");
        $stmt->execute([$planetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Annexe / Occupe une oasis pour un fief spécifique
     * Règle Travian : Maximum 3 oasis par village
     */
    public function annexOasis(int $oasisId, int $planetId): array {
        $oasis = $this->getOasisById($oasisId);
        if (!$oasis) {
            return ['success' => false, 'message' => 'Oasis introuvable.'];
        }

        if (!$this->isOasisPacified($oasisId)) {
            return ['success' => false, 'message' => 'L\'oasis est encore infestée d\'animaux sauvages féroces !'];
        }

        if ((int)$oasis['owner_planet_id'] === $planetId) {
            return ['success' => true, 'message' => 'Cette oasis est déjà sous votre contrôle.'];
        }

        // Vérifier la limite de 3 oasis par fief
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM oases WHERE owner_planet_id = ?");
        $stmtCount->execute([$planetId]);
        $currentCount = (int)$stmtCount->fetchColumn();

        if ($currentCount >= 3) {
            return ['success' => false, 'message' => 'Votre fief contrôle déjà le maximum de 3 oasis simultanées.'];
        }

        // Annexion
        $stmtUpdate = $this->db->prepare("
            UPDATE oases 
            SET owner_planet_id = ?, annexed_at = UNIX_TIMESTAMP() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$planetId, $oasisId]);

        return [
            'success' => true, 
            'message' => "L'oasis [{$oasis['name']}] a été annexée avec succès ! Les bonus de production s'appliquent à votre fief."
        ];
    }

    /**
     * Abandonne le contrôle d'une oasis
     */
    public function abandonOasis(int $oasisId, int $planetId): array {
        $stmt = $this->db->prepare("
            UPDATE oases 
            SET owner_planet_id = NULL, annexed_at = NULL 
            WHERE id = ? AND owner_planet_id = ?
        ");
        $stmt->execute([$oasisId, $planetId]);

        if ($stmt->rowCount() > 0) {
            // Rapatrier ou dissoudre les troupes stationnées
            $this->db->prepare("DELETE FROM oasis_units WHERE oasis_id = ? AND is_wild = 0")->execute([$oasisId]);
            return ['success' => true, 'message' => 'L\'oasis a été libérée de votre tutelle.'];
        }
        return ['success' => false, 'message' => 'Vous ne contrôlez pas cette oasis.'];
    }

    /**
     * Pillage des ressources disponibles dans l'oasis
     */
    public function lootOasis(int $oasisId, int $maxCargo): array {
        $oasis = $this->getOasisById($oasisId);
        if (!$oasis || $maxCargo <= 0) {
            return ['wood' => 0, 'stone' => 0, 'rice' => 0];
        }

        $availWood  = (int)$oasis['res_wood'];
        $availStone = (int)$oasis['res_stone'];
        $availRice  = (int)$oasis['res_rice'];
        $totalAvail = $availWood + $availStone + $availRice;

        if ($totalAvail <= 0) {
            return ['wood' => 0, 'stone' => 0, 'rice' => 0];
        }

        $takeWood = 0;
        $takeStone = 0;
        $takeRice = 0;

        if ($totalAvail <= $maxCargo) {
            $takeWood = $availWood;
            $takeStone = $availStone;
            $takeRice = $availRice;
        } else {
            // Répartition proportionnelle
            $ratio = $maxCargo / (float)$totalAvail;
            $takeWood = min($availWood, (int)round($availWood * $ratio));
            $takeStone = min($availStone, (int)round($availStone * $ratio));
            $remaining = $maxCargo - ($takeWood + $takeStone);
            $takeRice = min($availRice, max(0, $remaining));
        }

        // Déduire les ressources de l'oasis
        $stmt = $this->db->prepare("
            UPDATE oases 
            SET res_wood = res_wood - ?, res_stone = res_stone - ?, res_rice = res_rice - ?, last_loot_time = UNIX_TIMESTAMP() 
            WHERE id = ?
        ");
        $stmt->execute([$takeWood, $takeStone, $takeRice, $oasisId]);

        return [
            'wood'  => $takeWood,
            'stone' => $takeStone,
            'rice'  => $takeRice
        ];
    }

    /**
     * Régénération périodique des ressources et de la faune sauvage
     */
    public function regenerateOases(): void {
        $now = time();
        // Régénérer 200 ressources de chaque type par tranche de 10 minutes (jusqu'au max)
        $this->db->exec("
            UPDATE oases 
            SET 
                res_wood = LEAST(res_max, res_wood + 250),
                res_stone = LEAST(res_max, res_stone + 250),
                res_rice = LEAST(res_max, res_rice + 250)
            WHERE owner_planet_id IS NULL AND last_loot_time <= ($now - 600)
        ");
    }
}
