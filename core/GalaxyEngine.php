<?php
/**
 * Moteur de Carte Galactique et Navigation Stellaire
 */
require_once __DIR__ . '/Database.php';

class GalaxyEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère les secteurs de la carte spatiale autour d'un centre (cx, cy)
     */
    public function getSectorMap(int $centerX, int $centerY, int $radius = 4): array {
        $minX = $centerX - $radius;
        $maxX = $centerX + $radius;
        $minY = $centerY - $radius;
        $maxY = $centerY + $radius;

        $stmt = $this->db->prepare("
            SELECT p.id as planet_id, p.name as planet_name, p.coord_x, p.coord_y, p.planet_type, 
                   p.user_id, u.username, u.faction, u.points, a.tag as alliance_tag
            FROM planets p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE p.coord_x BETWEEN ? AND ? AND p.coord_y BETWEEN ? AND ?
        ");
        $stmt->execute([$minX, $maxX, $minY, $maxY]);
        $planets = $stmt->fetchAll();

        // Indexer par "x:y"
        $gridMap = [];
        foreach ($planets as $p) {
            $gridMap[$p['coord_x'] . ':' . $p['coord_y']] = $p;
        }

        // Récupérer les donjons authentiques déployés dans ce secteur
        try {
            $stmtCastles = $this->db->prepare("
                SELECT id, code, name as castle_name, japanese_name, kanji, province, 
                       historical_builder, classification, icon, short_desc, coord_x, coord_y
                FROM authentic_castles
                WHERE is_spawned = 1 AND coord_x BETWEEN ? AND ? AND coord_y BETWEEN ? AND ?
            ");
            $stmtCastles->execute([$minX, $maxX, $minY, $maxY]);
            $castles = $stmtCastles->fetchAll();

            foreach ($castles as $c) {
                $k = $c['coord_x'] . ':' . $c['coord_y'];
                if (isset($gridMap[$k])) {
                    $gridMap[$k]['is_authentic_castle'] = 1;
                    $gridMap[$k]['castle_code'] = $c['code'];
                    $gridMap[$k]['castle_name'] = $c['castle_name'];
                    $gridMap[$k]['castle_kanji'] = $c['kanji'];
                    $gridMap[$k]['castle_classification'] = $c['classification'];
                    $gridMap[$k]['castle_province'] = $c['province'];
                    $gridMap[$k]['castle_builder'] = $c['historical_builder'];
                    $gridMap[$k]['castle_icon'] = $c['icon'];
                } else {
                    $gridMap[$k] = [
                        'planet_id' => null,
                        'planet_name' => $c['castle_name'],
                        'coord_x' => (int)$c['coord_x'],
                        'coord_y' => (int)$c['coord_y'],
                        'planet_type' => 'authentic_castle',
                        'user_id' => null,
                        'username' => 'Garnison Antique',
                        'faction' => null,
                        'points' => 25000,
                        'alliance_tag' => 'TRÉSOR',
                        'is_authentic_castle' => 1,
                        'castle_code' => $c['code'],
                        'castle_name' => $c['castle_name'],
                        'castle_kanji' => $c['kanji'],
                        'castle_classification' => $c['classification'],
                        'castle_province' => $c['province'],
                        'castle_builder' => $c['historical_builder'],
                        'castle_icon' => $c['icon'],
                    ];
                }
            }
        } catch (Exception $e) {
            // Table pas encore créée ou fallback silencieux
        }

        return [
            'center_x' => $centerX,
            'center_y' => $centerY,
            'radius' => $radius,
            'bounds' => ['min_x' => $minX, 'max_x' => $maxX, 'min_y' => $minY, 'max_y' => $maxY],
            'planets' => $gridMap
        ];
    }
}

