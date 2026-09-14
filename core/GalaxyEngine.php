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

        return [
            'center_x' => $centerX,
            'center_y' => $centerY,
            'radius' => $radius,
            'bounds' => ['min_x' => $minX, 'max_x' => $maxX, 'min_y' => $minY, 'max_y' => $maxY],
            'planets' => $gridMap
        ];
    }
}

