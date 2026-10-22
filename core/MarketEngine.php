<?php
/**
 * MarketEngine - Gestion du Marché Féodal & de la Bourse d'Échange P2P
 * La Voie du Shogun - Époque Sengoku Jidai
 */

require_once __DIR__ . '/Database.php';

class MarketEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * S'assure que la table market_offers existe
     */
    private function ensureSchema(): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        if ($this->db->inTransaction()) {
            return;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `market_offers` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `planet_id` INT UNSIGNED NOT NULL,
                    `offer_res` VARCHAR(32) NOT NULL,
                    `offer_amount` INT UNSIGNED NOT NULL,
                    `demand_res` VARCHAR(32) NOT NULL,
                    `demand_amount` INT UNSIGNED NOT NULL,
                    `ratio` DECIMAL(8,4) NOT NULL,
                    `status` ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
                    `buyer_user_id` INT UNSIGNED DEFAULT NULL,
                    `buyer_planet_id` INT UNSIGNED DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `completed_at` DATETIME DEFAULT NULL,
                    `cancelled_at` DATETIME DEFAULT NULL,
                    INDEX `idx_mkt_status_res` (`status`, `offer_res`, `demand_res`),
                    INDEX `idx_mkt_user_status` (`user_id`, `status`),
                    INDEX `idx_mkt_planet` (`planet_id`),
                    CONSTRAINT `fk_mkt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_mkt_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {
            error_log("[MarketEngine::ensureSchema] " . $e->getMessage());
        }
    }

    private function forUpdate(): string {
        try {
            return ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') ? '' : ' FOR UPDATE';
        } catch (Throwable $e) {
            return ' FOR UPDATE';
        }
    }

    /**
     * Dictionnaire des ressources autorisées dans la bourse
     */
    public static function getTradableResources(): array {
        return [
            // Matériaux de base
            'metal' => [
                'name' => 'Bois de Cèdre',
                'category' => 'construction',
                'col' => 'metal',
                'max_col' => 'metal_max',
                'icon' => 'fa-solid fa-tree text-success'
            ],
            'crystal' => [
                'name' => 'Pierre de Granit',
                'category' => 'construction',
                'col' => 'crystal',
                'max_col' => 'crystal_max',
                'icon' => 'fa-solid fa-mountain text-secondary'
            ],
            'clay' => [
                'name' => 'Argile Alluviale',
                'category' => 'construction',
                'col' => 'clay',
                'max_col' => 'clay_max',
                'icon' => 'fa-solid fa-cubes-stacked text-danger'
            ],
            'wooden_beams' => [
                'name' => 'Poutres de Charpente',
                'category' => 'construction',
                'col' => 'wooden_beams',
                'max_col' => 'wooden_beams_max',
                'icon' => 'fa-solid fa-hammer text-warning'
            ],
            'charcoal' => [
                'name' => 'Charbon de Bois',
                'category' => 'construction',
                'col' => 'charcoal',
                'max_col' => 'charcoal_max',
                'icon' => 'fa-solid fa-fire text-dark'
            ],

            // Vivres
            'deuterium' => [
                'name' => 'Riz Impérial (Koku)',
                'category' => 'food',
                'col' => 'deuterium',
                'max_col' => 'deuterium_max',
                'icon' => 'fa-solid fa-wheat-awn text-warning'
            ],
            'fish' => [
                'name' => 'Poisson Frais',
                'category' => 'food',
                'col' => 'fish',
                'max_col' => 'fish_max',
                'icon' => 'fa-solid fa-fish text-info'
            ],
            'tea' => [
                'name' => 'Feuilles de Thé',
                'category' => 'food',
                'col' => 'tea',
                'max_col' => 'tea_max',
                'icon' => 'fa-solid fa-leaf text-success'
            ],
            'soybean' => [
                'name' => 'Gousses de Soja',
                'category' => 'food',
                'col' => 'soybean',
                'max_col' => 'soybean_max',
                'icon' => 'fa-solid fa-seedling text-warning'
            ],
            'rice_flour' => [
                'name' => 'Farine de Riz (Komeko)',
                'category' => 'food',
                'col' => 'rice_flour',
                'max_col' => 'rice_flour_max',
                'icon' => 'fa-solid fa-bowl-rice text-secondary'
            ],
            'sake' => [
                'name' => 'Saké Impérial',
                'category' => 'food',
                'col' => 'sake',
                'max_col' => 'sake_max',
                'icon' => 'fa-solid fa-wine-bottle text-danger'
            ],

            // Raffinages minéraux
            'sulfur' => [
                'name' => 'Soufre Volcanique',
                'category' => 'minerals',
                'col' => 'sulfur',
                'max_col' => 'sulfur_max',
                'icon' => 'fa-solid fa-volcano text-warning'
            ],
            'nitrate' => [
                'name' => 'Nitrate (Salpêtre)',
                'category' => 'minerals',
                'col' => 'nitrate',
                'max_col' => 'nitrate_max',
                'icon' => 'fa-solid fa-flask-vial text-info'
            ],
            'iron_sand' => [
                'name' => 'Sable de Fer (Satetsu)',
                'category' => 'minerals',
                'col' => 'iron_sand',
                'max_col' => 'iron_sand_max',
                'icon' => 'fa-solid fa-gem text-secondary'
            ]
        ];
    }

    /**
     * Récupère le niveau du marché sur un fief
     */
    public function getMarketLevel(int $planetId): int {
        $st = $this->db->prepare("
            SELECT level FROM planet_buildings 
            WHERE planet_id = ? AND building_type = 'market'
        ");
        $st->execute([$planetId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Nombre max de slots d'offres simultanées selon le niveau du marché
     * Accessible dès le Niveau 1 :
     * - Niveau 1-2 : 1 slot
     * - Niveau 3-4 : 2 slots
     * - Niveau 5-9 : 3 slots
     * - Niveau 10+ : 4 + 1 slot tous les 2 niveaux
     */
    public function getMaxOfferSlots(int $marketLevel): int {
        if ($marketLevel < 1) return 0;
        if ($marketLevel < 3) return 1;
        if ($marketLevel < 5) return 2;
        if ($marketLevel < 10) return 3;
        return 4 + (int)floor(($marketLevel - 10) / 2);
    }

    /**
     * Dépose une offre d'échange avec mise sous séquestre immédiate des ressources
     */
    public function createOffer(int $userId, int $planetId, string $offerRes, int $offerAmount, string $demandRes, int $demandAmount): array {
        $marketLevel = $this->getMarketLevel($planetId);
        if ($marketLevel < 1) {
            throw new Exception("Le Marché doit être construit pour déposer des offres sur la Bourse Féodale.");
        }

        $resDefs = self::getTradableResources();
        if (!isset($resDefs[$offerRes])) {
            throw new Exception("Ressource offerte invalide.");
        }
        if (!isset($resDefs[$demandRes])) {
            throw new Exception("Ressource demandée invalide.");
        }
        if ($offerRes === $demandRes) {
            throw new Exception("Vous ne pouvez pas échanger une ressource contre elle-même.");
        }
        if ($offerAmount <= 0) {
            throw new Exception("La quantité offerte doit être supérieure à zéro.");
        }
        if ($demandAmount <= 0) {
            throw new Exception("La quantité demandée doit être supérieure à zéro.");
        }

        $maxSlots = $this->getMaxOfferSlots($marketLevel);
        $stCount = $this->db->prepare("
            SELECT COUNT(*) FROM market_offers 
            WHERE planet_id = ? AND status = 'active'
        ");
        $stCount->execute([$planetId]);
        $activeCount = (int)$stCount->fetchColumn();

        if ($activeCount >= $maxSlots) {
            throw new Exception("Tous vos slots d'offres de troc sont occupés ({$activeCount}/{$maxSlots}). Améliorez votre Marché ou annulez une offre en cours.");
        }

        $ratio = round($demandAmount / $offerAmount, 4);
        $offerCol = $resDefs[$offerRes]['col'];

        $this->db->beginTransaction();
        try {
            // Verrouiller la ligne du fief
            $stLock = $this->db->prepare("SELECT id, user_id, `{$offerCol}` FROM planets WHERE id = ?" . $this->forUpdate());
            $stLock->execute([$planetId]);
            $planetRow = $stLock->fetch(PDO::FETCH_ASSOC);

            if (!$planetRow || (int)$planetRow['user_id'] !== $userId) {
                throw new Exception("Fief introuvable ou non autorisé.");
            }

            $currentStock = (float)($planetRow[$offerCol] ?? 0);
            if ($currentStock < $offerAmount) {
                throw new Exception("Stock insuffisant : vous possédez " . number_format((int)$currentStock) . " {$resDefs[$offerRes]['name']}, il vous en faut {$offerAmount}.");
            }

            // Déduction immédiate pour séquestre (escrow)
            $stDed = $this->db->prepare("
                UPDATE planets 
                SET `{$offerCol}` = `{$offerCol}` - ? 
                WHERE id = ? AND `{$offerCol}` >= ?
            ");
            $stDed->execute([$offerAmount, $planetId, $offerAmount]);
            if ($stDed->rowCount() === 0) {
                throw new Exception("Impossible de bloquer les ressources dans le slot de troc.");
            }

            // Enregistrement de l'offre
            $stIns = $this->db->prepare("
                INSERT INTO market_offers (user_id, planet_id, offer_res, offer_amount, demand_res, demand_amount, ratio, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stIns->execute([
                $userId,
                $planetId,
                $offerRes,
                $offerAmount,
                $demandRes,
                $demandAmount,
                $ratio
            ]);
            $offerId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return [
                'success' => true,
                'offer_id' => $offerId,
                'message' => "Offre n°#{$offerId} déposée avec succès ! {$offerAmount} {$resDefs[$offerRes]['name']} ont été consignés dans le coffre du marché.",
                'remaining_stock' => (int)($currentStock - $offerAmount)
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Annule une offre active et restitue intégralement les ressources bloquées
     */
    public function cancelOffer(int $userId, int $offerId): array {
        $resDefs = self::getTradableResources();

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT * FROM market_offers WHERE id = ?" . $this->forUpdate());
            $st->execute([$offerId]);
            $offer = $st->fetch(PDO::FETCH_ASSOC);

            if (!$offer) {
                throw new Exception("Offre introuvable.");
            }
            if ((int)$offer['user_id'] !== $userId) {
                throw new Exception("Vous n'êtes pas le propriétaire de cette offre.");
            }
            if ($offer['status'] !== 'active') {
                throw new Exception("Cette offre n'est plus active (déjà honorée ou annulée).");
            }

            $offerRes = $offer['offer_res'];
            $offerAmount = (int)$offer['offer_amount'];
            $planetId = (int)$offer['planet_id'];
            $offerCol = $resDefs[$offerRes]['col'] ?? $offerRes;

            // Restitution intégrale des ressources bloquées
            $stRef = $this->db->prepare("
                UPDATE planets 
                SET `{$offerCol}` = `{$offerCol}` + ? 
                WHERE id = ?
            ");
            $stRef->execute([$offerAmount, $planetId]);

            // Marquer l'offre comme annulée
            $stCancel = $this->db->prepare("
                UPDATE market_offers 
                SET status = 'cancelled', cancelled_at = NOW() 
                WHERE id = ?
            ");
            $stCancel->execute([$offerId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Offre n°#{$offerId} annulée. Les {$offerAmount} {$resDefs[$offerRes]['name']} ont été réintégrés dans vos réserves !"
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Accepte et conclut une offre d'échange
     */
    public function acceptOffer(int $buyerUserId, int $buyerPlanetId, int $offerId): array {
        $buyerMarketLvl = $this->getMarketLevel($buyerPlanetId);
        if ($buyerMarketLvl < 1) {
            throw new Exception("Votre fief doit disposer d'un Marché pour commercer sur la Bourse Féodale.");
        }

        $resDefs = self::getTradableResources();

        $this->db->beginTransaction();
        try {
            // Verrouiller l'offre
            $stOffer = $this->db->prepare("SELECT * FROM market_offers WHERE id = ?" . $this->forUpdate());
            $stOffer->execute([$offerId]);
            $offer = $stOffer->fetch(PDO::FETCH_ASSOC);

            if (!$offer || $offer['status'] !== 'active') {
                throw new Exception("Cette offre n'est plus disponible.");
            }

            if ((int)$offer['user_id'] === $buyerUserId && (int)$offer['planet_id'] === $buyerPlanetId) {
                throw new Exception("Vous ne pouvez pas accepter votre propre offre sur le même domaine castral.");
            }

            $offerRes = $offer['offer_res'];
            $offerAmount = (int)$offer['offer_amount'];
            $demandRes = $offer['demand_res'];
            $demandAmount = (int)$offer['demand_amount'];

            $offerDef = $resDefs[$offerRes] ?? null;
            $demandDef = $resDefs[$demandRes] ?? null;
            if (!$offerDef || !$demandDef) {
                throw new Exception("Type de ressource introuvable dans le répertoire.");
            }

            $offerCol = $offerDef['col'];
            $demandCol = $demandDef['col'];
            $sellerPlanetId = (int)$offer['planet_id'];
            $sellerUserId = (int)$offer['user_id'];

            // Verrouiller le fief de l'acheteur
            $stBuyerP = $this->db->prepare("
                SELECT id, user_id, `{$demandCol}`, `{$offerCol}`, `{$offerDef['max_col']}` 
                FROM planets 
                WHERE id = ?" . $this->forUpdate() . "
            ");
            $stBuyerP->execute([$buyerPlanetId]);
            $buyerPlanet = $stBuyerP->fetch(PDO::FETCH_ASSOC);

            if (!$buyerPlanet || (int)$buyerPlanet['user_id'] !== $buyerUserId) {
                throw new Exception("Fief acheteur introuvable.");
            }

            // Vérifier le stock de la ressource demandée chez l'acheteur
            $buyerDemandStock = (float)($buyerPlanet[$demandCol] ?? 0);
            if ($buyerDemandStock < $demandAmount) {
                throw new Exception("Stock insuffisant : vous devez fournir {$demandAmount} {$demandDef['name']} (vous possédez " . number_format((int)$buyerDemandStock) . ").");
            }

            // Déduire la ressource demandée chez l'acheteur et créditer la ressource offerte
            $stUpBuyer = $this->db->prepare("
                UPDATE planets 
                SET `{$demandCol}` = `{$demandCol}` - ?,
                    `{$offerCol}` = `{$offerCol}` + ?
                WHERE id = ? AND `{$demandCol}` >= ?
            ");
            $stUpBuyer->execute([$demandAmount, $offerAmount, $buyerPlanetId, $demandAmount]);
            if ($stUpBuyer->rowCount() === 0) {
                throw new Exception("Échec de la transaction financière côté acheteur.");
            }

            // Créditer la ressource demandée sur le fief du vendeur
            $stUpSeller = $this->db->prepare("
                UPDATE planets 
                SET `{$demandCol}` = `{$demandCol}` + ? 
                WHERE id = ?
            ");
            $stUpSeller->execute([$demandAmount, $sellerPlanetId]);

            // Marquer l'offre comme complétée
            $stComplete = $this->db->prepare("
                UPDATE market_offers 
                SET status = 'completed', 
                    buyer_user_id = ?, 
                    buyer_planet_id = ?, 
                    completed_at = NOW() 
                WHERE id = ?
            ");
            $stComplete->execute([$buyerUserId, $buyerPlanetId, $offerId]);

            // Notification pour le vendeur
            try {
                $stBuyerUser = $this->db->prepare("SELECT username FROM users WHERE id = ?");
                $stBuyerUser->execute([$buyerUserId]);
                $buyerName = $stBuyerUser->fetchColumn() ?: "Un seigneur";

                $notifMsg = "Votre offre n°#{$offerId} a été conclue par {$buyerName} ! Vous avez reçu {$demandAmount} {$demandDef['name']} sur votre domaine en échange des {$offerAmount} {$offerDef['name']} consignés.";
                $this->db->prepare("
                    INSERT INTO messages (user_id, sender_id, subject, body, created_at)
                    VALUES (?, 0, 'Accord Commercial Honoré (Bourse P2P)', ?, NOW())
                ")->execute([$sellerUserId, $notifMsg]);
            } catch (Exception $e) {
                // Silencieux si table de messages diffère légèrement
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Transaction féodale accomplie ! Vous avez reçu {$offerAmount} {$offerDef['name']} en échange de {$demandAmount} {$demandDef['name']}."
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Récupère les offres actives sur le marché
     */
    public function getActiveOffers(?string $filterOfferRes = null, ?string $filterDemandRes = null, int $limit = 50): array {
        $sql = "
            SELECT mo.*, 
                   u.username as seller_name, 
                   p.name as seller_planet_name, 
                   p.coord_x as seller_x, 
                   p.coord_y as seller_y
            FROM market_offers mo
            JOIN users u ON mo.user_id = u.id
            JOIN planets p ON mo.planet_id = p.id
            WHERE mo.status = 'active'
        ";
        $params = [];

        if ($filterOfferRes) {
            $sql .= " AND mo.offer_res = ?";
            $params[] = $filterOfferRes;
        }
        if ($filterDemandRes) {
            $sql .= " AND mo.demand_res = ?";
            $params[] = $filterDemandRes;
        }

        $sql .= " ORDER BY mo.created_at DESC LIMIT " . (int)$limit;
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les offres d'un utilisateur
     */
    public function getUserOffers(int $userId): array {
        $st = $this->db->prepare("
            SELECT mo.*, p.name as planet_name
            FROM market_offers mo
            JOIN planets p ON mo.planet_id = p.id
            WHERE mo.user_id = ?
            ORDER BY (CASE mo.status WHEN 'active' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END), mo.created_at DESC
            LIMIT 30
        ");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récapitulatif de la Bourse des Sens d'Échange (agrégation par couple offre/demande)
     */
    public function getExchangeDirectionsSummary(): array {
        $st = $this->db->query("
            SELECT mo.offer_res, mo.demand_res,
                   COUNT(*) as offers_count,
                   SUM(mo.offer_amount) as total_offered,
                   SUM(mo.demand_amount) as total_demanded,
                   MIN(mo.ratio) as best_ratio,
                   AVG(mo.ratio) as avg_ratio
            FROM market_offers mo
            WHERE mo.status = 'active'
            GROUP BY mo.offer_res, mo.demand_res
            ORDER BY offers_count DESC, total_offered DESC
        ");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les offres de troc actives déposées par un fief
     */
    public function getActiveOffersForPlanet(int $planetId): array {
        $st = $this->db->prepare("
            SELECT mo.*, p.name as planet_name
            FROM market_offers mo
            JOIN planets p ON mo.planet_id = p.id
            WHERE mo.planet_id = ? AND mo.status = 'active'
            ORDER BY mo.created_at DESC
        ");
        $st->execute([$planetId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $tradables = self::getTradableResources();
        foreach ($rows as &$r) {
            $r['offer_meta'] = $tradables[$r['offer_res']] ?? ['name' => $r['offer_res'], 'icon' => 'fa-solid fa-box text-secondary'];
            $r['demand_meta'] = $tradables[$r['demand_res']] ?? ['name' => $r['demand_res'], 'icon' => 'fa-solid fa-box text-secondary'];
            $r['offer_amount_formatted'] = number_format((int)$r['offer_amount']);
            $r['demand_amount_formatted'] = number_format((int)$r['demand_amount']);
        }
        unset($r);
        return $rows;
    }

    /**
     * Récupère les missions de convoi / transport en transit (sortantes ou entrantes) pour un fief
     */
    public function getTransportConvoysForPlanet(int $planetId, int $userId): array {
        $st = $this->db->prepare("
            SELECT fm.*,
                   p1.name as source_planet_name, p1.coord_x as sx, p1.coord_y as sy, u1.username as source_username,
                   p2.name as target_planet_name, p2.coord_x as tx, p2.coord_y as ty, u2.username as target_username
            FROM fleet_missions fm
            JOIN planets p1 ON fm.source_planet_id = p1.id
            JOIN users u1 ON fm.user_id = u1.id
            LEFT JOIN planets p2 ON fm.target_planet_id = p2.id
            LEFT JOIN users u2 ON p2.user_id = u2.id
            WHERE fm.mission_type = 'transport'
              AND fm.status IN ('en_route', 'returning')
              AND (fm.source_planet_id = ? OR fm.target_planet_id = ?)
            ORDER BY fm.arrival_time ASC
        ");
        $st->execute([$planetId, $planetId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $now = time();
        $tradables = self::getTradableResources();

        $convoys = [];
        foreach ($rows as $r) {
            $isSource = ((int)$r['source_planet_id'] === $planetId);
            $status = $r['status']; // 'en_route' or 'returning'

            $direction = 'outgoing'; // par défaut : convoi sortant du fief
            $targetTime = (int)$r['arrival_time'];

            if ($isSource && $status === 'returning') {
                $direction = 'returning';
                $targetTime = (int)$r['return_time'];
            } elseif (!$isSource && $status === 'en_route') {
                $direction = 'incoming';
                $targetTime = (int)$r['arrival_time'];
            } elseif (!$isSource && $status === 'returning') {
                // Convoi d'un autre joueur qui repart de notre fief après livraison : non pertinent
                continue;
            }

            $cargoData = json_decode($r['cargo_data'] ?? '[]', true) ?: [];
            $fleetData = json_decode($r['fleet_data'] ?? '[]', true) ?: [];

            $formattedCargo = [];
            $totalCargo = 0;
            foreach ($cargoData as $resKey => $amt) {
                $intAmt = (int)$amt;
                if ($intAmt > 0) {
                    $totalCargo += $intAmt;
                    $formattedCargo[$resKey] = [
                        'amount' => $intAmt,
                        'formatted' => number_format($intAmt),
                        'meta' => $tradables[$resKey] ?? ['name' => ucfirst($resKey), 'icon' => 'fa-solid fa-box text-secondary']
                    ];
                }
            }

            $secRemaining = max(0, $targetTime - $now);

            $convoys[] = [
                'id' => (int)$r['id'],
                'direction' => $direction, // 'outgoing', 'returning', 'incoming'
                'status' => $status,
                'source_planet_id' => (int)$r['source_planet_id'],
                'source_planet_name' => $r['source_planet_name'],
                'source_coords' => "[{$r['sx']}:{$r['sy']}]",
                'source_username' => $r['source_username'],
                'target_planet_id' => (int)$r['target_planet_id'],
                'target_planet_name' => $r['target_planet_name'] ?? 'Fief voisin',
                'target_coords' => "[{$r['tx']}:{$r['ty']}]",
                'target_username' => $r['target_username'] ?? 'Seigneur',
                'cargo' => $formattedCargo,
                'total_cargo' => $totalCargo,
                'total_cargo_formatted' => number_format($totalCargo),
                'fleet' => $fleetData,
                'target_time' => $targetTime,
                'seconds_remaining' => $secRemaining,
                'is_owner' => ((int)$r['user_id'] === $userId)
            ];
        }

        return $convoys;
    }

    /**
     * Récupère les routes commerciales programmées et actives associées à un fief
     */
    public function getActiveTradeRoutesForPlanet(int $planetId): array {
        try {
            $st = $this->db->prepare("
                SELECT tr.*,
                       p1.name as source_planet_name, p1.coord_x as sx, p1.coord_y as sy,
                       p2.name as target_planet_name, p2.coord_x as tx, p2.coord_y as ty
                FROM trade_routes tr
                JOIN planets p1 ON tr.source_planet_id = p1.id
                JOIN planets p2 ON tr.target_planet_id = p2.id
                WHERE (tr.source_planet_id = ? OR tr.target_planet_id = ?)
                  AND tr.is_active = 1
                ORDER BY tr.next_run_at ASC
            ");
            $st->execute([$planetId, $planetId]);
            $routes = $st->fetchAll(PDO::FETCH_ASSOC);

            $now = time();
            foreach ($routes as &$rt) {
                $nextRunTs = !empty($rt['next_run_at']) ? strtotime($rt['next_run_at']) : $now;
                $rt['seconds_until_next_run'] = max(0, $nextRunTs - $now);
                $rt['total_cargo'] = (int)($rt['wood'] ?? 0) + (int)($rt['stone'] ?? 0) + (int)($rt['rice'] ?? 0);
            }
            unset($rt);
            return $routes;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Récupère les véhicules marchands stationnés dans le fief
     */
    public function getStationedTransporters(int $planetId): array {
        try {
            $st = $this->db->prepare("
                SELECT ship_code, quantity 
                FROM planet_ships 
                WHERE planet_id = ? AND ship_code IN ('transporter_light', 'transporter_heavy')
            ");
            $st->execute([$planetId]);
            $rows = $st->fetchAll(PDO::FETCH_KEY_PAIR);

            $light = (int)($rows['transporter_light'] ?? 0);
            $heavy = (int)($rows['transporter_heavy'] ?? 0);
            $totalCargoCap = ($light * 5000) + ($heavy * 25000);

            return [
                'transporter_light' => $light,
                'transporter_heavy' => $heavy,
                'total_count' => $light + $heavy,
                'total_capacity' => $totalCargoCap,
                'total_capacity_formatted' => number_format($totalCargoCap)
            ];
        } catch (Throwable $e) {
            return [
                'transporter_light' => 0,
                'transporter_heavy' => 0,
                'total_count' => 0,
                'total_capacity' => 0,
                'total_capacity_formatted' => '0'
            ];
        }
    }

    /**
     * Bilan global en temps réel du Marché pour un fief donné
     */
    public function getOngoingMarketSummary(int $planetId, int $userId): array {
        $marketLevel = $this->getMarketLevel($planetId);
        $maxOfferSlots = $this->getMaxOfferSlots($marketLevel);

        $activeOffers = $this->getActiveOffersForPlanet($planetId);
        $activeOffersCount = count($activeOffers);

        $convoys = $this->getTransportConvoysForPlanet($planetId, $userId);
        $convoysCount = count($convoys);

        $routes = $this->getActiveTradeRoutesForPlanet($planetId);
        $routesCount = count($routes);

        $transporters = $this->getStationedTransporters($planetId);

        $totalActive = $activeOffersCount + $convoysCount + $routesCount;

        return [
            'market_level' => $marketLevel,
            'is_market_built' => ($marketLevel > 0),
            'max_offer_slots' => $maxOfferSlots,
            'active_offers' => $activeOffers,
            'active_offers_count' => $activeOffersCount,
            'transport_convoys' => $convoys,
            'transport_convoys_count' => $convoysCount,
            'trade_routes' => $routes,
            'trade_routes_count' => $routesCount,
            'transporters' => $transporters,
            'total_active' => $totalActive
        ];
    }
}

