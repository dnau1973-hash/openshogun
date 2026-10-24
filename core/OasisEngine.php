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

        if (str_contains((string)$oasis['oasis_type'], 'lake')) {
            return [
                'success' => false,
                'message' => 'Les parcelles de lac sont des sanctuaires d\'eaux libres communautaires. Elles ne peuvent pas être annexées par un fief, mais vous pouvez y envoyer des Pêcheurs formés au Dojo en expédition halieutique.'
            ];
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

        // Apparition d'une nouvelle oasis sauvage après capture si configuré
        require_once __DIR__ . '/GameConfig.php';
        $respawnOnCapture = (bool)GameConfig::get('oasis_respawn_on_capture', true);
        $newSpawn = null;
        if ($respawnOnCapture) {
            $newSpawn = $this->spawnReplacementWildOasis((int)$oasis['coord_x'], (int)$oasis['coord_y']);
        }

        $extraMsg = $newSpawn ? " Une nouvelle oasis sauvage a émergé en terre libre [{$newSpawn['coord_x']} : {$newSpawn['coord_y']}]." : "";

        return [
            'success' => true, 
            'message' => "L'oasis [{$oasis['name']}] a été annexée avec succès ! Les bonus de production s'appliquent à votre fief." . $extraMsg,
            'respawned_oasis' => $newSpawn
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

    /**
     * Retourne les archétypes prédéfinis d'oasis équilibrées avec faune sauvage
     */
    public static function getOasisTemplates(): array {
        return [
            [
                'type' => 'lake_rice_50',
                'name' => 'Grand Lac aux Eaux Vivifiantes',
                'wood' => 0, 'stone' => 0, 'rice' => 50,
                'animals' => ['sanglier_sauvage' => 30, 'loup_honshu' => 20, 'ours_hokkaido' => 8]
            ],
            [
                'type' => 'forest_wood_50',
                'name' => 'Forêt Millénaire de Cèdres Géants',
                'wood' => 50, 'stone' => 0, 'rice' => 0,
                'animals' => ['sanglier_sauvage' => 35, 'loup_honshu' => 25, 'ours_hokkaido' => 10]
            ],
            [
                'type' => 'mountain_stone_50',
                'name' => 'Pics Escarpés aux Gisements de Fer',
                'wood' => 0, 'stone' => 50, 'rice' => 0,
                'animals' => ['sanglier_sauvage' => 25, 'loup_honshu' => 30, 'ours_hokkaido' => 12]
            ],
            [
                'type' => 'lake_wood_rice',
                'name' => 'Source Chaude d\'Onsen en Lisière',
                'wood' => 25, 'stone' => 0, 'rice' => 25,
                'animals' => ['sanglier_sauvage' => 25, 'loup_honshu' => 15, 'ours_hokkaido' => 5]
            ],
            [
                'type' => 'hills_stone_wood',
                'name' => 'Plateau Argileux & Vergers Sauvages',
                'wood' => 25, 'stone' => 25, 'rice' => 0,
                'animals' => ['sanglier_sauvage' => 20, 'loup_honshu' => 18, 'ours_hokkaido' => 6]
            ],
            [
                'type' => 'mountain_stone_rice',
                'name' => 'Gorge Minérale & Cascades Sacrées',
                'wood' => 0, 'stone' => 25, 'rice' => 25,
                'animals' => ['sanglier_sauvage' => 22, 'loup_honshu' => 20, 'ours_hokkaido' => 7]
            ]
        ];
    }

    /**
     * Récupère la liste de toutes les coordonnées déjà occupées (planètes, donjons, oasis)
     */
    public function getOccupiedCoordinates(): array {
        $used = [];
        $stmtP = $this->db->query("SELECT coord_x, coord_y FROM planets");
        while ($r = $stmtP->fetch(PDO::FETCH_ASSOC)) {
            $used[$r['coord_x'] . ':' . $r['coord_y']] = true;
        }
        $stmtC = $this->db->query("SELECT coord_x, coord_y FROM authentic_castles WHERE is_spawned = 1");
        while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
            $used[$r['coord_x'] . ':' . $r['coord_y']] = true;
        }
        $stmtO = $this->db->query("SELECT coord_x, coord_y FROM oases");
        while ($r = $stmtO->fetch(PDO::FETCH_ASSOC)) {
            $used[$r['coord_x'] . ':' . $r['coord_y']] = true;
        }
        return $used;
    }

    /**
     * Déploie une nouvelle oasis sauvage avec sa faune à des coordonnées données
     */
    public function spawnWildOasisAt(int $x, int $y, ?array $template = null): ?int {
        if ($template === null) {
            $templates = self::getOasisTemplates();
            $template = $templates[array_rand($templates)];
        }

        $stmt = $this->db->prepare("
            INSERT INTO `oases` 
            (`coord_x`, `coord_y`, `oasis_type`, `name`, `bonus_wood`, `bonus_stone`, `bonus_rice`, `res_wood`, `res_stone`, `res_rice`, `res_max`, `last_loot_time`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1500, 1500, 1500, 5000, UNIX_TIMESTAMP())
        ");
        $stmt->execute([
            $x, $y, $template['type'], $template['name'],
            $template['wood'], $template['stone'], $template['rice']
        ]);
        $oasisId = (int)$this->db->lastInsertId();
        if ($oasisId <= 0) return null;

        // Peupler avec les animaux sauvages
        $stmtUnit = $this->db->prepare("
            INSERT INTO `oasis_units` (`oasis_id`, `unit_code`, `count`, `is_wild`)
            VALUES (?, ?, ?, 1)
        ");
        foreach ($template['animals'] as $uCode => $cnt) {
            $stmtUnit->execute([$oasisId, $uCode, $cnt]);
        }

        return $oasisId;
    }

    /**
     * Fait réapparaître une nouvelle oasis sauvage lors de la capture d'une oasis par un joueur.
     * L'apparition respecte la zone régionale d'origine pour éviter tout recentrage vers le cœur.
     */
    public function spawnReplacementWildOasis(int $nearX, int $nearY): ?array {
        require_once __DIR__ . '/GameConfig.php';
        $radius = (int)GameConfig::getWorldRadius();
        $usedCoords = $this->getOccupiedCoordinates();
        $templates = self::getOasisTemplates();
        $template = $templates[array_rand($templates)];

        // Récupérer les coordonnées de toutes les oasis pour maintenir la distance minimale
        $existingOasesStmt = $this->db->query("SELECT coord_x, coord_y FROM oases");
        $oasisCoordsList = [];
        while ($row = $existingOasesStmt->fetch()) {
            $oasisCoordsList[] = [(int)$row['coord_x'], (int)$row['coord_y']];
        }

        // 1. Définir une fenêtre régionale autour de la position capturée (rayon local ~12 cases)
        $localRadius = 12;
        $minX = max(-$radius, $nearX - $localRadius);
        $maxX = min($radius, $nearX + $localRadius);
        $minY = max(-$radius, $nearY - $localRadius);
        $maxY = min($radius, $nearY + $localRadius);

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $x = rand($minX, $maxX);
            $y = rand($minY, $maxY);
            if ($x === 0 && $y === 0) continue;
            $k = $x . ':' . $y;

            if (!isset($usedCoords[$k])) {
                $tooClose = false;
                foreach ($oasisCoordsList as $oc) {
                    $dx = $x - $oc[0];
                    $dy = $y - $oc[1];
                    if (($dx * $dx + $dy * $dy) < 9) { // distance euclidienne >= 3
                        $tooClose = true;
                        break;
                    }
                }
                if (!$tooClose) {
                    $oasisId = $this->spawnWildOasisAt($x, $y, $template);
                    if ($oasisId) {
                        return $this->getOasisById($oasisId);
                    }
                }
            }
        }

        // 2. Définir le quadrant élargi si la zone locale est encombrée
        $qMinX = ($nearX >= 0) ? 2 : -$radius;
        $qMaxX = ($nearX >= 0) ? $radius : -2;
        $qMinY = ($nearY >= 0) ? 2 : -$radius;
        $qMaxY = ($nearY >= 0) ? $radius : -2;

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $x = rand($qMinX, $qMaxX);
            $y = rand($qMinY, $qMaxY);
            if ($x === 0 && $y === 0) continue;
            $k = $x . ':' . $y;

            if (!isset($usedCoords[$k])) {
                $oasisId = $this->spawnWildOasisAt($x, $y, $template);
                if ($oasisId) {
                    return $this->getOasisById($oasisId);
                }
            }
        }

        // 3. Fallback large sur toute la carte de -$radius à +$radius
        for ($attempt = 0; $attempt < 80; $attempt++) {
            $x = rand(-$radius, $radius);
            $y = rand(-$radius, $radius);
            if ($x === 0 && $y === 0) continue;
            $k = $x . ':' . $y;

            if (!isset($usedCoords[$k])) {
                $oasisId = $this->spawnWildOasisAt($x, $y, $template);
                if ($oasisId) {
                    return $this->getOasisById($oasisId);
                }
            }
        }

        return null;
    }

    /**
     * Génère ou rééquilibre les oasis de manière homogène sur toute l'étendue de la carte.
     * Utilise une stratification spatiale par grille (jittered grid) et une contrainte
     * de distance minimale répulsive pour éviter tout regroupement ou recentrage.
     *
     * @param float $percent Densité cible (ex: 2.0%)
     * @param int|null $radius Rayon de la carte (défaut: GameConfig::getWorldRadius())
     * @param bool $clearExistingUnoccupied Si true, supprime les oasis sauvages non annexées avant de régénérer
     * @return array Rapport de génération
     */
    public function spawnOasesByDensity(float $percent, ?int $radius = null, bool $clearExistingUnoccupied = false): array {
        require_once __DIR__ . '/GameConfig.php';
        $radius = $radius !== null ? max(10, min(50, $radius)) : (int)GameConfig::getWorldRadius();
        $percent = max(0.5, min(20.0, $percent));

        if ($clearExistingUnoccupied) {
            // Nettoie uniquement les oasis non occupées/annexées par des joueurs
            $this->db->exec("DELETE FROM oases WHERE owner_planet_id IS NULL");
        }

        // Calcul du nombre cible d'oasis selon la surface totale (2R + 1)^2
        $totalTiles = (int)pow(($radius * 2) + 1, 2);
        $targetCount = max(4, min(250, (int)round($totalTiles * ($percent / 100.0))));

        $currentTotal = (int)$this->db->query("SELECT COUNT(*) FROM oases")->fetchColumn();
        $needed = max(0, $targetCount - $currentTotal);

        if ($needed === 0) {
            return [
                'success' => true,
                'target_count' => $targetCount,
                'spawned' => 0,
                'total_now' => $currentTotal,
                'radius' => $radius,
                'percent' => $percent,
                'message' => "La carte contient déjà $currentTotal oasis (densité cible de $percent% atteinte)."
            ];
        }

        $usedCoords = $this->getOccupiedCoordinates();
        // Coordonnées absolues du centre impérial
        $usedCoords['0:0'] = true;

        // Récupérer les coordonnées de toutes les oasis existantes pour tester la distance minimale
        $existingOasesStmt = $this->db->query("SELECT coord_x, coord_y FROM oases");
        $oasisCoordsList = [];
        while ($row = $existingOasesStmt->fetch()) {
            $oasisCoordsList[] = [(int)$row['coord_x'], (int)$row['coord_y']];
        }

        $templates = self::getOasisTemplates();
        $tplCount = count($templates);

        // Découpage en grille spatiale régulière M x M cellules pour couvrir toute la carte de -R à +R
        $gridSize = (int)max(2, ceil(sqrt($targetCount)));
        $cellWidth = (($radius * 2) + 1) / (float)$gridSize;

        // Générer la liste de toutes les cellules (col, row)
        $cells = [];
        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                $cells[] = [
                    'c' => $c,
                    'r' => $r,
                    'min_x' => (int)floor(-$radius + ($c * $cellWidth)),
                    'max_x' => (int)floor(-$radius + (($c + 1) * $cellWidth) - 1),
                    'min_y' => (int)floor(-$radius + ($r * $cellWidth)),
                    'max_y' => (int)floor(-$radius + (($r + 1) * $cellWidth) - 1),
                ];
            }
        }

        // Mélanger l'ordre des cellules pour une attribution variée des templates féodaux
        shuffle($cells);

        $minDistance = 3.0; // Distance euclidienne minimale entre deux oasis
        $minDistSq = $minDistance * $minDistance;

        $spawned = 0;
        $tplIndex = 0;

        // Phase 1 : Placer 1 oasis par cellule spatiale (stratification homogène)
        foreach ($cells as $cell) {
            if ($spawned >= $needed) {
                break;
            }

            $tpl = $templates[$tplIndex % $tplCount];
            $placed = false;

            // Essais avec Jitter aléatoire dans la cellule
            for ($attempt = 0; $attempt < 35; $attempt++) {
                $ox = rand($cell['min_x'], $cell['max_x']);
                $oy = rand($cell['min_y'], $cell['max_y']);

                // S'assurer qu'on reste dans les bornes du rayon
                if ($ox < -$radius || $ox > $radius || $oy < -$radius || $oy > $radius) {
                    continue;
                }
                if ($ox === 0 && $oy === 0) {
                    continue;
                }

                $k = $ox . ':' . $oy;
                if (isset($usedCoords[$k])) {
                    continue;
                }

                // Vérifier la distance minimale avec toutes les oasis déjà déployées
                $tooClose = false;
                foreach ($oasisCoordsList as $oc) {
                    $dx = $ox - $oc[0];
                    $dy = $oy - $oc[1];
                    if (($dx * $dx + $dy * $dy) < $minDistSq) {
                        $tooClose = true;
                        break;
                    }
                }

                if (!$tooClose) {
                    $oid = $this->spawnWildOasisAt($ox, $oy, $tpl);
                    if ($oid) {
                        $usedCoords[$k] = true;
                        $oasisCoordsList[] = [$ox, $oy];
                        $spawned++;
                        $tplIndex++;
                        $placed = true;
                        break;
                    }
                }
            }

            // Si aucune coordonnée respectant $minDistance n'a été trouvée dans la cellule,
            // on effectue un second passage en assouplissant légèrement la distance ($minDistSq = 4 => dist = 2)
            if (!$placed) {
                for ($attempt = 0; $attempt < 20; $attempt++) {
                    $ox = rand($cell['min_x'], $cell['max_x']);
                    $oy = rand($cell['min_y'], $cell['max_y']);

                    if ($ox < -$radius || $ox > $radius || $oy < -$radius || $oy > $radius || ($ox === 0 && $oy === 0)) {
                        continue;
                    }
                    $k = $ox . ':' . $oy;
                    if (isset($usedCoords[$k])) {
                        continue;
                    }

                    $tooClose = false;
                    foreach ($oasisCoordsList as $oc) {
                        $dx = $ox - $oc[0];
                        $dy = $oy - $oc[1];
                        if (($dx * $dx + $dy * $dy) < 4) { // distance >= 2
                            $tooClose = true;
                            break;
                        }
                    }

                    if (!$tooClose) {
                        $oid = $this->spawnWildOasisAt($ox, $oy, $tpl);
                        if ($oid) {
                            $usedCoords[$k] = true;
                            $oasisCoordsList[] = [$ox, $oy];
                            $spawned++;
                            $tplIndex++;
                            $placed = true;
                            break;
                        }
                    }
                }
            }
        }

        // Phase 2 : Si la cible n'est pas encore atteinte, balayage homogène avec distance minimale
        if ($spawned < $needed) {
            $remaining = $needed - $spawned;
            for ($i = 0; $i < $remaining; $i++) {
                $tpl = $templates[$tplIndex % $tplCount];

                for ($attempt = 0; $attempt < 80; $attempt++) {
                    $ox = rand(-$radius, $radius);
                    $oy = rand(-$radius, $radius);
                    if ($ox === 0 && $oy === 0) continue;
                    $k = $ox . ':' . $oy;
                    if (isset($usedCoords[$k])) continue;

                    $tooClose = false;
                    foreach ($oasisCoordsList as $oc) {
                        $dx = $ox - $oc[0];
                        $dy = $oy - $oc[1];
                        if (($dx * $dx + $dy * $dy) < 4) {
                            $tooClose = true;
                            break;
                        }
                    }

                    if (!$tooClose) {
                        $oid = $this->spawnWildOasisAt($ox, $oy, $tpl);
                        if ($oid) {
                            $usedCoords[$k] = true;
                            $oasisCoordsList[] = [$ox, $oy];
                            $spawned++;
                            $tplIndex++;
                            break;
                        }
                    }
                }
            }
        }

        $newTotal = (int)$this->db->query("SELECT COUNT(*) FROM oases")->fetchColumn();
        return [
            'success' => true,
            'target_count' => $targetCount,
            'spawned' => $spawned,
            'total_now' => $newTotal,
            'radius' => $radius,
            'percent' => $percent,
            'message' => "Génération homogène terminée : $spawned nouvelles oasis créées réparties équitablement de -$radius à +$radius (Total sur la carte : $newTotal)."
        ];
    }

    /**
     * Récupère les métriques globales sur les oasis pour l'administration
     */
    public function getOasisStatistics(): array {
        require_once __DIR__ . '/GameConfig.php';

        $totalOases = (int)$this->db->query("SELECT COUNT(*) FROM oases")->fetchColumn();
        $capturedOases = (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE owner_planet_id IS NOT NULL")->fetchColumn();
        $wildOases = (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE owner_planet_id IS NULL")->fetchColumn();
        
        // Animaux vivants
        $stmtAnimals = $this->db->query("
            SELECT ou.unit_code, COALESCE(SUM(ou.count), 0) as total_count 
            FROM oasis_units ou 
            WHERE ou.is_wild = 1 
            GROUP BY ou.unit_code
        ");
        $animalsSummary = $stmtAnimals->fetchAll(PDO::FETCH_KEY_PAIR);

        $totalWildAnimals = array_sum($animalsSummary);

        // Analyse de la dissémination géographique
        $radius = (int)GameConfig::getWorldRadius();
        $quadrants = [
            'NO' => (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE coord_x < 0 AND coord_y > 0")->fetchColumn(),
            'NE' => (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE coord_x > 0 AND coord_y > 0")->fetchColumn(),
            'SO' => (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE coord_x < 0 AND coord_y < 0")->fetchColumn(),
            'SE' => (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE coord_x > 0 AND coord_y < 0")->fetchColumn(),
        ];
        $frontierThreshold = (int)round($radius * 0.6);
        $frontierOases = (int)$this->db->query("SELECT COUNT(*) FROM oases WHERE ABS(coord_x) >= $frontierThreshold OR ABS(coord_y) >= $frontierThreshold")->fetchColumn();

        return [
            'total_oases' => $totalOases,
            'captured_oases' => $capturedOases,
            'wild_oases' => $wildOases,
            'animals_summary' => $animalsSummary,
            'total_wild_animals' => $totalWildAnimals,
            'density_percent' => (float)GameConfig::get('oasis_density_percent', 2.0),
            'respawn_on_capture' => (bool)GameConfig::get('oasis_respawn_on_capture', true),
            'world_radius' => $radius,
            'quadrants' => $quadrants,
            'frontier_oases' => $frontierOases
        ];
    }
}


