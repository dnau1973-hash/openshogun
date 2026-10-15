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
     * Niveau 10 : 3 slots, +1 slot tous les 2 niveaux au-delà
     */
    public function getMaxOfferSlots(int $marketLevel): int {
        if ($marketLevel < 10) return 0;
        return 3 + (int)floor(($marketLevel - 10) / 2);
    }

    /**
     * Dépose une offre d'échange avec mise sous séquestre immédiate des ressources
     */
    public function createOffer(int $userId, int $planetId, string $offerRes, int $offerAmount, string $demandRes, int $demandAmount): array {
        $marketLevel = $this->getMarketLevel($planetId);
        if ($marketLevel < 10) {
            throw new Exception("Le Marché doit être au moins au Niveau 10 pour déposer des offres de troc féodal.");
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
            $stLock = $this->db->prepare("SELECT id, user_id, `{$offerCol}` FROM planets WHERE id = ? FOR UPDATE");
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
            $st = $this->db->prepare("SELECT * FROM market_offers WHERE id = ? FOR UPDATE");
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
        if ($buyerMarketLvl < 10) {
            throw new Exception("Votre fief doit disposer d'un Marché au moins au Niveau 10 pour commercer sur la Bourse Féodale.");
        }

        $resDefs = self::getTradableResources();

        $this->db->beginTransaction();
        try {
            // Verrouiller l'offre
            $stOffer = $this->db->prepare("SELECT * FROM market_offers WHERE id = ? FOR UPDATE");
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
                WHERE id = ? FOR UPDATE
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
            ORDER BY FIELD(mo.status, 'active', 'completed', 'cancelled'), mo.created_at DESC
            LIMIT 30
        ");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}

