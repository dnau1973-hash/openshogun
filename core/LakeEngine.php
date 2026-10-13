<?php
/**
 * Moteur des Réserves Halieutiques des Lacs (OpenShogun)
 *
 * Chaque parcelle de lac (terrain procédural `lake` ou oasis lacustre) dispose d'un stock
 * de poisson fini, tiré aléatoirement entre FISH_STOCK_MIN et FISH_STOCK_MAX à la génération
 * du monde. Une fois le stock épuisé, le lac reste présent sur la carte mais ne peut plus
 * être pêché.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GalaxyEngine.php';

class LakeEngine {
    public const FISH_STOCK_MIN = 10000;
    public const FISH_STOCK_MAX = 25000;

    private PDO $db;
    private static bool $schemaChecked = false;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Création transparente de la table des réserves halieutiques
     */
    public function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("CREATE TABLE IF NOT EXISTS lake_fish_stocks (
                    coord_x INTEGER NOT NULL,
                    coord_y INTEGER NOT NULL,
                    fish_stock INTEGER NOT NULL DEFAULT 0,
                    fish_initial INTEGER NOT NULL DEFAULT 0,
                    created_at INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (coord_x, coord_y)
                )");
            } else {
                $this->db->exec("CREATE TABLE IF NOT EXISTS `lake_fish_stocks` (
                    `coord_x` INT NOT NULL,
                    `coord_y` INT NOT NULL,
                    `fish_stock` INT UNSIGNED NOT NULL DEFAULT 0,
                    `fish_initial` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`coord_x`, `coord_y`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (Exception $e) {
            // Silencieux (permissions limitées)
        }
    }

    /**
     * Indique si une coordonnée correspond à une parcelle de lac (oasis lacustre ou terrain procédural)
     */
    public function isLakeAt(int $x, int $y): bool {
        try {
            $stmt = $this->db->prepare("SELECT oasis_type FROM oases WHERE coord_x = ? AND coord_y = ? LIMIT 1");
            $stmt->execute([$x, $y]);
            $type = $stmt->fetchColumn();
            if ($type !== false && $type !== null) {
                return str_contains((string)$type, 'lake');
            }
        } catch (Exception $e) {}

        return GalaxyEngine::getTerrainType($x, $y)['type'] === 'lake';
    }

    private static function randomStock(): int {
        return random_int(self::FISH_STOCK_MIN, self::FISH_STOCK_MAX);
    }

    /**
     * Retourne la réserve d'un lac. Si le lac n'a jamais été recensé (hors zone de génération
     * ou monde antérieur à la mise à jour), sa réserve est initialisée à la volée.
     *
     * @return array|null ['fish_stock' => int, 'fish_initial' => int, 'depleted' => bool] ou null si pas un lac
     */
    public function getStock(int $x, int $y, bool $autoInit = true): ?array {
        $stmt = $this->db->prepare("SELECT fish_stock, fish_initial FROM lake_fish_stocks WHERE coord_x = ? AND coord_y = ?");
        $stmt->execute([$x, $y]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            if (!$autoInit || !$this->isLakeAt($x, $y)) {
                return null;
            }
            $initial = self::randomStock();
            $this->insertStock($x, $y, $initial);
            $row = ['fish_stock' => $initial, 'fish_initial' => $initial];
        }

        $stock = max(0, (int)$row['fish_stock']);
        return [
            'fish_stock'   => $stock,
            'fish_initial' => (int)$row['fish_initial'],
            'depleted'     => $stock <= 0,
        ];
    }

    private function insertStock(int $x, int $y, int $amount): void {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? "INSERT OR IGNORE INTO lake_fish_stocks (coord_x, coord_y, fish_stock, fish_initial, created_at) VALUES (?, ?, ?, ?, ?)"
            : "INSERT IGNORE INTO lake_fish_stocks (coord_x, coord_y, fish_stock, fish_initial, created_at) VALUES (?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([$x, $y, $amount, $amount, time()]);
    }

    /**
     * Prélève du poisson dans le lac de manière atomique.
     * @return int Quantité réellement prélevée (0 si le lac est épuisé)
     */
    public function harvest(int $x, int $y, int $requested): int {
        if ($requested <= 0) return 0;
        $stock = $this->getStock($x, $y);
        if ($stock === null || $stock['depleted']) return 0;

        $taken = min($requested, $stock['fish_stock']);
        $upd = $this->db->prepare("
            UPDATE lake_fish_stocks
            SET fish_stock = fish_stock - ?
            WHERE coord_x = ? AND coord_y = ? AND fish_stock >= ?
        ");
        $upd->execute([$taken, $x, $y, $taken]);

        if ($upd->rowCount() === 0) {
            // Concurrence : une autre expédition a pêché entre-temps, on relit et on prend le reliquat
            $stock = $this->getStock($x, $y, false);
            if ($stock === null || $stock['depleted']) return 0;
            $taken = min($requested, $stock['fish_stock']);
            $upd->execute([$taken, $x, $y, $taken]);
            if ($upd->rowCount() === 0) return 0;
        }
        return $taken;
    }

    /**
     * Ensemencement de toutes les parcelles de lac du monde (appelé à la génération / reset de l'univers).
     * Les stocks existants sont remplacés.
     *
     * @return int Nombre de lacs ensemencés
     */
    public function seedWorldLakes(int $radius = 40): int {
        $this->db->exec("DELETE FROM lake_fish_stocks");

        $coords = [];
        // 1. Lacs procéduraux dans le rayon du monde
        for ($y = -$radius; $y <= $radius; $y++) {
            for ($x = -$radius; $x <= $radius; $x++) {
                if (GalaxyEngine::getTerrainType($x, $y)['type'] === 'lake') {
                    $coords[$x . ':' . $y] = [$x, $y];
                }
            }
        }

        // 2. Oasis lacustres ; les oasis non lacustres recouvrent leur terrain et ne sont pas des lacs
        try {
            $stmt = $this->db->query("SELECT coord_x, coord_y, oasis_type FROM oases");
            while ($o = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $k = $o['coord_x'] . ':' . $o['coord_y'];
                if (str_contains((string)$o['oasis_type'], 'lake')) {
                    $coords[$k] = [(int)$o['coord_x'], (int)$o['coord_y']];
                } else {
                    unset($coords[$k]);
                }
            }
        } catch (Exception $e) {}

        foreach ($coords as [$x, $y]) {
            $this->insertStock($x, $y, self::randomStock());
        }
        return count($coords);
    }
}

