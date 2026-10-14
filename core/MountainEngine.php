<?php
/**
 * Moteur des Réserves Minérales des Montagnes (OpenShogun)
 *
 * Chaque parcelle de montagne (terrain procédural `mountain` ou oasis montagneuse)
 * dispose de filons de minerais finis : Soufre volcanique (sulfur), Nitrate de potasse (nitrate)
 * et Sable de fer noir (iron_sand / Satetsu 砂鉄).
 * Les Mineurs des Cimes (Kōfu) formés au Dojo peuvent être dépêchés en expédition minière.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GalaxyEngine.php';

class MountainEngine {
    // Stocks initiaux par gisement de montagne
    public const SULFUR_MIN = 5000;
    public const SULFUR_MAX = 15000;

    public const NITRATE_MIN = 5000;
    public const NITRATE_MAX = 15000;

    public const IRON_SAND_MIN = 8000;
    public const IRON_SAND_MAX = 25000;

    private PDO $db;
    private static bool $schemaChecked = false;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Création transparente de la table des réserves minérales de montagne
     */
    public function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("CREATE TABLE IF NOT EXISTS mountain_mineral_stocks (
                    coord_x INTEGER NOT NULL,
                    coord_y INTEGER NOT NULL,
                    sulfur_stock INTEGER NOT NULL DEFAULT 0,
                    sulfur_initial INTEGER NOT NULL DEFAULT 0,
                    nitrate_stock INTEGER NOT NULL DEFAULT 0,
                    nitrate_initial INTEGER NOT NULL DEFAULT 0,
                    iron_sand_stock INTEGER NOT NULL DEFAULT 0,
                    iron_sand_initial INTEGER NOT NULL DEFAULT 0,
                    created_at INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (coord_x, coord_y)
                )");
            } else {
                $this->db->exec("CREATE TABLE IF NOT EXISTS `mountain_mineral_stocks` (
                    `coord_x` INT NOT NULL,
                    `coord_y` INT NOT NULL,
                    `sulfur_stock` INT UNSIGNED NOT NULL DEFAULT 0,
                    `sulfur_initial` INT UNSIGNED NOT NULL DEFAULT 0,
                    `nitrate_stock` INT UNSIGNED NOT NULL DEFAULT 0,
                    `nitrate_initial` INT UNSIGNED NOT NULL DEFAULT 0,
                    `iron_sand_stock` INT UNSIGNED NOT NULL DEFAULT 0,
                    `iron_sand_initial` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`coord_x`, `coord_y`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (Exception $e) {
            // Silencieux
        }
    }

    /**
     * Indique si une coordonnée correspond à une parcelle de montagne
     */
    public function isMountainAt(int $x, int $y): bool {
        try {
            $stmt = $this->db->prepare("SELECT oasis_type FROM oases WHERE coord_x = ? AND coord_y = ? LIMIT 1");
            $stmt->execute([$x, $y]);
            $type = $stmt->fetchColumn();
            if ($type !== false && $type !== null) {
                return str_contains((string)$type, 'mountain') || str_contains((string)$type, 'rock');
            }
        } catch (Exception $e) {}

        return GalaxyEngine::getTerrainType($x, $y)['type'] === 'mountain';
    }

    /**
     * Retourne les réserves d'une montagne. Si jamais recensée, initialisée à la volée.
     *
     * @return array|null ['sulfur' => [...], 'nitrate' => [...], 'iron_sand' => [...], 'depleted' => bool]
     */
    public function getStock(int $x, int $y, bool $autoInit = true): ?array {
        $stmt = $this->db->prepare("SELECT * FROM mountain_mineral_stocks WHERE coord_x = ? AND coord_y = ?");
        $stmt->execute([$x, $y]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            if (!$autoInit || !$this->isMountainAt($x, $y)) {
                return null;
            }
            $sInit = random_int(self::SULFUR_MIN, self::SULFUR_MAX);
            $nInit = random_int(self::NITRATE_MIN, self::NITRATE_MAX);
            $iInit = random_int(self::IRON_SAND_MIN, self::IRON_SAND_MAX);
            $this->insertStock($x, $y, $sInit, $nInit, $iInit);
            $row = [
                'sulfur_stock'      => $sInit,
                'sulfur_initial'    => $sInit,
                'nitrate_stock'     => $nInit,
                'nitrate_initial'   => $nInit,
                'iron_sand_stock'   => $iInit,
                'iron_sand_initial' => $iInit,
            ];
        }

        $sStock = max(0, (int)$row['sulfur_stock']);
        $nStock = max(0, (int)$row['nitrate_stock']);
        $iStock = max(0, (int)$row['iron_sand_stock']);

        $totalStock = $sStock + $nStock + $iStock;

        return [
            'sulfur_stock'      => $sStock,
            'sulfur_initial'    => (int)$row['sulfur_initial'],
            'nitrate_stock'     => $nStock,
            'nitrate_initial'   => (int)$row['nitrate_initial'],
            'iron_sand_stock'   => $iStock,
            'iron_sand_initial' => (int)$row['iron_sand_initial'],
            'total_stock'       => $totalStock,
            'depleted'          => $totalStock <= 0,
        ];
    }

    private function insertStock(int $x, int $y, int $s, int $n, int $i): void {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? "INSERT OR IGNORE INTO mountain_mineral_stocks (coord_x, coord_y, sulfur_stock, sulfur_initial, nitrate_stock, nitrate_initial, iron_sand_stock, iron_sand_initial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            : "INSERT IGNORE INTO mountain_mineral_stocks (coord_x, coord_y, sulfur_stock, sulfur_initial, nitrate_stock, nitrate_initial, iron_sand_stock, iron_sand_initial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([$x, $y, $s, $s, $n, $n, $i, $i, time()]);
    }

    /**
     * Prélèvement minier atomique dans la montagne
     *
     * @param int $x
     * @param int $y
     * @param array $requested ['sulfur' => int, 'nitrate' => int, 'iron_sand' => int]
     * @return array Quantités réellement prélevées ['sulfur' => int, 'nitrate' => int, 'iron_sand' => int]
     */
    public function harvest(int $x, int $y, array $requested): array {
        $stock = $this->getStock($x, $y);
        if ($stock === null || $stock['depleted']) {
            return ['sulfur' => 0, 'nitrate' => 0, 'iron_sand' => 0];
        }

        $sTaken = min(max(0, (int)($requested['sulfur'] ?? 0)), $stock['sulfur_stock']);
        $nTaken = min(max(0, (int)($requested['nitrate'] ?? 0)), $stock['nitrate_stock']);
        $iTaken = min(max(0, (int)($requested['iron_sand'] ?? 0)), $stock['iron_sand_stock']);

        if ($sTaken <= 0 && $nTaken <= 0 && $iTaken <= 0) {
            return ['sulfur' => 0, 'nitrate' => 0, 'iron_sand' => 0];
        }

        $upd = $this->db->prepare("
            UPDATE mountain_mineral_stocks
            SET sulfur_stock = sulfur_stock - ?,
                nitrate_stock = nitrate_stock - ?,
                iron_sand_stock = iron_sand_stock - ?
            WHERE coord_x = ? AND coord_y = ? 
              AND sulfur_stock >= ? 
              AND nitrate_stock >= ? 
              AND iron_sand_stock >= ?
        ");
        $upd->execute([$sTaken, $nTaken, $iTaken, $x, $y, $sTaken, $nTaken, $iTaken]);

        if ($upd->rowCount() === 0) {
            // Relire en cas de concurrence
            $stock = $this->getStock($x, $y, false);
            if ($stock === null || $stock['depleted']) {
                return ['sulfur' => 0, 'nitrate' => 0, 'iron_sand' => 0];
            }
            $sTaken = min($sTaken, $stock['sulfur_stock']);
            $nTaken = min($nTaken, $stock['nitrate_stock']);
            $iTaken = min($iTaken, $stock['iron_sand_stock']);

            $upd->execute([$sTaken, $nTaken, $iTaken, $x, $y, $sTaken, $nTaken, $iTaken]);
            if ($upd->rowCount() === 0) {
                return ['sulfur' => 0, 'nitrate' => 0, 'iron_sand' => 0];
            }
        }

        $harvested = ['sulfur' => $sTaken, 'nitrate' => $nTaken, 'iron_sand' => $iTaken];
        $remStock = $this->getStock($x, $y, false);
        return [
            'harvested' => $harvested,
            'depleted' => $remStock ? !empty($remStock['depleted']) : false
        ];
    }
}
