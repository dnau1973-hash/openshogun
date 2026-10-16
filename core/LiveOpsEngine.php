<?php
/**
 * Moteur Métier : Producteur & Live Operations (LiveOpsEngine)
 * OpenShogun - Tour de Contrôle, Gouvernance, Événements Temporels, Broadcast & Support
 * Fichier : core/LiveOpsEngine.php
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';

class LiveOpsEngine {
    private ?PDO $db;
    private string $flagsPath;
    private string $eventsPath;
    private string $broadcastPath;
    private string $maintenancePath;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $configDir = __DIR__ . '/../config';
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0777, true);
        }
        $this->flagsPath       = $configDir . '/feature_flags.json';
        $this->eventsPath      = $configDir . '/live_events.json';
        $this->broadcastPath   = $configDir . '/liveops_broadcast.json';
        $this->maintenancePath = $configDir . '/maintenance.json';
        $this->ensureTables();
    }

    /**
     * S'assure que la table d'audit des compensations existe
     */
    public function ensureTables(): void {
        if (!$this->db) return;
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS liveops_compensations_log (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        admin_id INTEGER NOT NULL,
                        admin_name TEXT NOT NULL DEFAULT '',
                        recipient_type TEXT NOT NULL DEFAULT 'user',
                        recipient_id INTEGER NULL,
                        recipient_name TEXT NOT NULL DEFAULT '',
                        resources_json TEXT NOT NULL,
                        reason TEXT NOT NULL,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            } else {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS liveops_compensations_log (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        admin_id INT NOT NULL,
                        admin_name VARCHAR(100) NOT NULL DEFAULT '',
                        recipient_type VARCHAR(20) NOT NULL DEFAULT 'user',
                        recipient_id INT NULL,
                        recipient_name VARCHAR(100) NOT NULL DEFAULT '',
                        resources_json TEXT NOT NULL,
                        reason TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_recipient (recipient_id),
                        INDEX idx_created (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        } catch (Throwable $e) {
            error_log("[LiveOpsEngine] ensureTables error: " . $e->getMessage());
        }
    }

    /* ══════════════════════════════════════════════════════════════════════════
       1. FEATURE FLAGS & ROADMAP (Interrupteurs à chaud)
       ══════════════════════════════════════════════════════════════════════════ */

    public function getDefaultFeatureFlags(): array {
        return [
            'p2p_market' => [
                'key'         => 'p2p_market',
                'label'       => 'Bourse Féodale de Troc P2P (Niv. 10)',
                'category'    => 'Économie & Marché',
                'description' => 'Comptoir de troc direct décentralisé entre Daimyōs avec séquestre impérial.',
                'icon'        => 'fa-scale-balanced',
                'enabled'     => true
            ],
            'narrative_storyboard' => [
                'key'         => 'narrative_storyboard',
                'label'       => 'Gestionnaire de Storyboard',
                'category'    => 'Narration & Studio',
                'description' => 'Outil de scénarisation vidéo cinématique interactive dans Game Studio.',
                'icon'        => 'fa-film',
                'enabled'     => true
            ],
            'bot_ai_clans' => [
                'key'         => 'bot_ai_clans',
                'label'       => 'Daimyōs IA & Clans Autonomes',
                'category'    => 'Monde & IA',
                'description' => 'Génération automatique de rivaux IA, colonisation et comportement autonome.',
                'icon'        => 'fa-robot',
                'enabled'     => true
            ],
            'gallery_art' => [
                'key'         => 'gallery_art',
                'label'       => 'Galerie d\'Art Féodale (Photogrid)',
                'category'    => 'Multimédia & Immersion',
                'description' => 'Exposition publique de l\'ensemble des estampes, forteresses et unités.',
                'icon'        => 'fa-images',
                'enabled'     => true
            ],
            'weekly_narrative_events' => [
                'key'         => 'weekly_narrative_events',
                'label'       => 'Chroniques Hebdomadaires Narratives',
                'category'    => 'Événements & Quêtes',
                'description' => 'Système d\'événements scénarisés et quêtes séquentielles historiques.',
                'icon'        => 'fa-calendar-week',
                'enabled'     => true
            ],
            'wisdom_learning' => [
                'key'         => 'wisdom_learning',
                'label'       => 'Arbre des Savoirs & Sagesses',
                'category'    => 'Progression Daimyō',
                'description' => 'Compétences passives seigneuriales et préceptes des ancêtres.',
                'icon'        => 'fa-book-bookmark',
                'enabled'     => true
            ],
            'prestige_weapons' => [
                'key'         => 'prestige_weapons',
                'label'       => 'Armes & Armées de Prestige (Forge 20)',
                'category'    => 'Militaire & Forge',
                'description' => 'Forge d\'armements mythiques en tamahagane pur pour les troupes d\'élite.',
                'icon'        => 'fa-khanda',
                'enabled'     => true
            ],
            'famine_system' => [
                'key'         => 'famine_system',
                'label'       => 'Simulation de Famine & Pénurie de Riz',
                'category'    => 'Gameplay & Survie',
                'description' => 'Gestion des réserves de riz, désertion des troupes et pénalités de rendement.',
                'icon'        => 'fa-wheat-awn-circle-exclamation',
                'enabled'     => (bool)GameConfig::get('famine_enabled', false)
            ]
        ];
    }

    public function getFeatureFlags(): array {
        $defaults = $this->getDefaultFeatureFlags();
        if (!file_exists($this->flagsPath)) {
            $this->saveFeatureFlags($defaults);
            return $defaults;
        }

        $content = @file_get_contents($this->flagsPath);
        $saved = json_decode((string)$content, true);
        if (!is_array($saved)) {
            return $defaults;
        }

        // Fusion avec les valeurs par défaut pour garantir la présence des clés
        foreach ($defaults as $key => $meta) {
            if (isset($saved[$key])) {
                $defaults[$key]['enabled'] = (bool)$saved[$key]['enabled'];
                if (isset($saved[$key]['updated_at'])) {
                    $defaults[$key]['updated_at'] = $saved[$key]['updated_at'];
                }
            }
        }
        return $defaults;
    }

    public function setFeatureFlag(string $key, bool $enabled): bool {
        $flags = $this->getFeatureFlags();
        if (!isset($flags[$key])) {
            $flags[$key] = [
                'key'         => $key,
                'label'       => ucfirst(str_replace('_', ' ', $key)),
                'category'    => 'Personnalisé',
                'description' => 'Fonctionnalité paramétrée dynamiquement.',
                'icon'        => 'fa-toggle-on',
                'enabled'     => $enabled
            ];
        }
        $flags[$key]['enabled'] = $enabled;
        $flags[$key]['updated_at'] = date('Y-m-d H:i:s');

        // Synchronisation spéciale avec GameConfig si applicable
        if ($key === 'famine_system') {
            GameConfig::set('famine_enabled', $enabled);
        }

        return $this->saveFeatureFlags($flags);
    }

    public function isFeatureEnabled(string $key, bool $default = true): bool {
        $flags = $this->getFeatureFlags();
        return isset($flags[$key]) ? (bool)$flags[$key]['enabled'] : $default;
    }

    private function saveFeatureFlags(array $flags): bool {
        return (bool)file_put_contents($this->flagsPath, json_encode($flags, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /* ══════════════════════════════════════════════════════════════════════════
       2. ÉVÉNEMENTS LIVE & BOOSTS D'UNIVERS
       ══════════════════════════════════════════════════════════════════════════ */

    public function getEventPresets(): array {
        return [
            'sakura_harvest' => [
                'title'       => 'Célébration des Moissons Sakura',
                'jp_title'    => '桜の収穫祭 (Sakura no Shūkaku-sai)',
                'icon'        => 'fa-wheat-awn',
                'color'       => '#22c55e',
                'badge_class' => 'bg-success text-white',
                'description' => 'Un climat bienfaisant baigne les provinces. Les rendements de riz et de bois augmentent abondamment.',
                'multipliers' => [
                    'prod_boost_pct' => 25,
                    'build_speed_pct' => 10,
                    'march_speed_pct' => 0
                ]
            ],
            'takeda_cavalry' => [
                'title'       => 'Furie Équestre de Kai (Clan Takeda)',
                'jp_title'    => '甲斐の騎馬隊 (Kai no Kibatai)',
                'icon'        => 'fa-horse',
                'color'       => '#ef4444',
                'badge_class' => 'bg-danger text-white',
                'description' => 'Les haras et dojos tournent à plein régime. Vitesse d\'entraînement et marche des bataillons décuplées.',
                'multipliers' => [
                    'prod_boost_pct' => 0,
                    'train_speed_pct' => 30,
                    'march_speed_pct' => 40
                ]
            ],
            'oda_builders' => [
                'title'       => 'Décret des Maîtres-Bâtisseurs Oda',
                'jp_title'    => '織田の建築令 (Oda no Kenchiku-rei)',
                'icon'        => 'fa-hammer',
                'color'       => '#3b82f6',
                'badge_class' => 'bg-primary text-white',
                'description' => 'Tous les ouvriers et charpentiers sont mobilisés pour ériger forteresses et remparts à cadence record.',
                'multipliers' => [
                    'build_speed_pct' => 35,
                    'prod_boost_pct' => 10,
                    'march_speed_pct' => 0
                ]
            ],
            'sacred_forge' => [
                'title'       => 'Nuit Sacrée de la Forge Tamahagane',
                'jp_title'    => '玉鋼の神鍛 (Tamahagane no Shintan)',
                'icon'        => 'fa-fire-burner',
                'color'       => '#eab308',
                'badge_class' => 'bg-warning text-dark',
                'description' => 'Les fourneaux Tatara crépitent d\'un feu pur. Gain d\'honneur augmenté et coûts de forge allégés.',
                'multipliers' => [
                    'honor_gain_pct' => 50,
                    'prod_boost_pct' => 15,
                    'build_speed_pct' => 0
                ]
            ]
        ];
    }

    public function getAllLiveEvents(): array {
        if (!file_exists($this->eventsPath)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($this->eventsPath), true);
        return is_array($data) ? $data : [];
    }

    public function getActiveLiveEvents(): array {
        $all = $this->getAllLiveEvents();
        $now = time();
        $active = [];
        foreach ($all as $ev) {
            $start = strtotime($ev['starts_at'] ?? '2000-01-01');
            $end   = strtotime($ev['ends_at'] ?? '2000-01-01');
            if (!empty($ev['is_active']) && $start <= $now && $end > $now) {
                $ev['remaining_seconds'] = max(0, $end - $now);
                $active[] = $ev;
            }
        }
        return $active;
    }

    public function getActiveGlobalBoosts(): array {
        $activeEvents = $this->getActiveLiveEvents();
        $boosts = [
            'prod_boost_pct'  => 0,
            'build_speed_pct' => 0,
            'train_speed_pct' => 0,
            'march_speed_pct' => 0,
            'honor_gain_pct'  => 0,
            'active_event_count' => count($activeEvents),
            'events_summary'  => []
        ];

        foreach ($activeEvents as $ev) {
            $mults = $ev['multipliers'] ?? [];
            foreach ($mults as $k => $v) {
                if (isset($boosts[$k])) {
                    $boosts[$k] += (int)$v;
                }
            }
            $boosts['events_summary'][] = $ev['title'];
        }

        return $boosts;
    }

    public function triggerLiveEvent(array $payload): array {
        $all = $this->getAllLiveEvents();
        $id = uniqid('ev_');
        $now = time();
        $durationHours = max(1, (int)($payload['duration_hours'] ?? 24));
        $startsAt = !empty($payload['starts_at']) ? date('Y-m-d H:i:s', strtotime($payload['starts_at'])) : date('Y-m-d H:i:s', $now);
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt) + ($durationHours * 3600));

        $newEvent = [
            'id'          => $id,
            'slug'        => $payload['slug'] ?? 'custom',
            'title'       => trim($payload['title'] ?? 'Événement Impérial Spécial'),
            'jp_title'    => trim($payload['jp_title'] ?? ''),
            'icon'        => $payload['icon'] ?? 'fa-bolt',
            'color'       => $payload['color'] ?? '#3b82f6',
            'description' => trim($payload['description'] ?? ''),
            'multipliers' => [
                'prod_boost_pct'  => max(0, min(200, (int)($payload['prod_boost_pct'] ?? 0))),
                'build_speed_pct' => max(0, min(200, (int)($payload['build_speed_pct'] ?? 0))),
                'train_speed_pct' => max(0, min(200, (int)($payload['train_speed_pct'] ?? 0))),
                'march_speed_pct' => max(0, min(200, (int)($payload['march_speed_pct'] ?? 0))),
                'honor_gain_pct'  => max(0, min(200, (int)($payload['honor_gain_pct'] ?? 0))),
            ],
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'is_active'   => true,
            'created_at'  => date('Y-m-d H:i:s')
        ];

        array_unshift($all, $newEvent);
        file_put_contents($this->eventsPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Déclencher automatiquement une annonce Broadcast si demandé
        if (!empty($payload['broadcast_announcement'])) {
            $msg = "⚡ L'événement impérial « " . $newEvent['title'] . " » est proclamé dans tout le Japon féodal ! Profitez des bénédictions impériales jusqu'au " . date('d/m/Y H:i', strtotime($endsAt)) . ".";
            $this->setBroadcast("Événement : " . $newEvent['title'], $msg, 'warning', $durationHours * 60, true);
        }

        return ['success' => true, 'event' => $newEvent];
    }

    public function stopLiveEvent(string $eventId): bool {
        $all = $this->getAllLiveEvents();
        $found = false;
        foreach ($all as &$ev) {
            if ($ev['id'] === $eventId) {
                $ev['is_active'] = false;
                $ev['ends_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        if ($found) {
            file_put_contents($this->eventsPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return $found;
    }

    /* ══════════════════════════════════════════════════════════════════════════
       3. BROADCAST IN-GAME FLASH & MODE MAINTENANCE
       ══════════════════════════════════════════════════════════════════════════ */

    public function getActiveBroadcast(): ?array {
        if (!file_exists($this->broadcastPath)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($this->broadcastPath), true);
        if (!is_array($data) || empty($data['is_active'])) {
            return null;
        }
        $now = time();
        $expires = isset($data['expires_at']) ? strtotime($data['expires_at']) : 0;
        if ($expires > 0 && $expires <= $now) {
            return null;
        }
        return $data;
    }

    public function setBroadcast(string $title, string $message, string $type = 'info', int $durationMinutes = 60, bool $isActive = true): bool {
        $now = time();
        $payload = [
            'id'          => uniqid('bc_'),
            'title'       => trim($title ?: 'Dépêche Impériale'),
            'message'     => trim($message),
            'type'        => in_array($type, ['info', 'success', 'warning', 'danger', 'primary'], true) ? $type : 'info',
            'icon'        => match($type) {
                'danger'  => 'fa-triangle-exclamation text-danger',
                'warning' => 'fa-bolt text-warning',
                'success' => 'fa-circle-check text-success',
                default   => 'fa-bullhorn text-info'
            },
            'is_active'   => $isActive,
            'created_at'  => date('Y-m-d H:i:s', $now),
            'expires_at'  => date('Y-m-d H:i:s', $now + max(60, $durationMinutes * 60))
        ];
        return (bool)file_put_contents($this->broadcastPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function clearBroadcast(): bool {
        if (!file_exists($this->broadcastPath)) return true;
        return (bool)file_put_contents($this->broadcastPath, json_encode(['is_active' => false], JSON_PRETTY_PRINT));
    }

    public function getMaintenanceStatus(): array {
        if (!file_exists($this->maintenancePath)) {
            return [
                'is_active'         => false,
                'message'           => 'Le Conseil de Guerre impérial procède à des ajustements techniques sur les forteresses. Réouverture imminente.',
                'scheduled_end'     => null,
                'whitelisted_users' => []
            ];
        }
        $data = json_decode((string)file_get_contents($this->maintenancePath), true);
        return is_array($data) ? $data : ['is_active' => false];
    }

    public function isMaintenanceActive(): bool {
        $status = $this->getMaintenanceStatus();
        return !empty($status['is_active']);
    }

    public function isUserWhitelistedInMaintenance(int $userId): bool {
        $status = $this->getMaintenanceStatus();
        $whitelist = $status['whitelisted_users'] ?? [];
        return in_array($userId, $whitelist, true);
    }

    public function setMaintenance(bool $isActive, string $message, ?string $scheduledEnd = null, array $whitelistedUsers = []): bool {
        $payload = [
            'is_active'         => $isActive,
            'message'           => trim($message ?: 'Le Shōgunat effectue des manœuvres de maintenance et consolidation des provinces.'),
            'scheduled_end'     => $scheduledEnd,
            'whitelisted_users' => array_map('intval', $whitelistedUsers),
            'updated_at'        => date('Y-m-d H:i:s')
        ];
        return (bool)file_put_contents($this->maintenancePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /* ══════════════════════════════════════════════════════════════════════════
       4. SUPPORT DAIMYŌ & COMPENSATIONS IMPÉRIALES
       ══════════════════════════════════════════════════════════════════════════ */

    public function searchDaimyos(string $query, int $limit = 20): array {
        if (!$this->db) return [];
        $query = trim($query);
        if ($query === '') {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.email, u.faction, u.points, u.gold_coins as gold, u.is_admin,
                       p.id as planet_id, p.name as planet_name, p.metal, p.crystal, p.deuterium
                FROM users u
                LEFT JOIN planets p ON p.user_id = u.id AND p.is_capital = 1
                WHERE u.is_bot = 0
                ORDER BY u.id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $term = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.email, u.faction, u.points, u.gold_coins as gold, u.is_admin,
                   p.id as planet_id, p.name as planet_name, p.metal, p.crystal, p.deuterium
            FROM users u
            LEFT JOIN planets p ON p.user_id = u.id AND p.is_capital = 1
            WHERE (u.username LIKE ? OR u.email LIKE ? OR p.name LIKE ?) AND u.is_bot = 0
            ORDER BY u.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $term, PDO::PARAM_STR);
        $stmt->bindValue(2, $term, PDO::PARAM_STR);
        $stmt->bindValue(3, $term, PDO::PARAM_STR);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendCompensation(
        ?int $userId,
        array $resources,
        string $reason,
        int $adminId,
        bool $sendToAll = false
    ): array {
        if (!$this->db) {
            throw new Exception("Base de données indisponible.");
        }

        $metal = max(0, (int)($resources['metal'] ?? 0));
        $crystal = max(0, (int)($resources['crystal'] ?? 0));
        $deuterium = max(0, (int)($resources['deuterium'] ?? 0));
        $gold = max(0, (int)($resources['gold'] ?? 0));
        $honor = max(0, (int)($resources['honor'] ?? 0));

        if ($metal === 0 && $crystal === 0 && $deuterium === 0 && $gold === 0 && $honor === 0) {
            throw new Exception("Aucune ressource ou trésor sélectionné pour la compensation.");
        }

        // Récupérer le nom de l'admin émetteur
        $adminStmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $adminStmt->execute([$adminId]);
        $adminName = $adminStmt->fetchColumn() ?: "Producteur Shōgun";

        $recipientsCount = 0;
        $recipientName = 'Tous les Daimyōs';

        if ($sendToAll) {
            // Attribution collective à tous les joueurs humains
            $stmtUsers = $this->db->query("SELECT id, username FROM users WHERE is_bot = 0");
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $u) {
                $uId = (int)$u['id'];
                $this->creditPlayerResources($uId, $metal, $crystal, $deuterium, $gold, $honor);
                $this->sendImperialCompensationMessage($adminId, $uId, $metal, $crystal, $deuterium, $gold, $honor, $reason);
                $recipientsCount++;
            }
            $recipientType = 'all';
            $recipientId = null;
        } else {
            if (!$userId || $userId <= 0) {
                throw new Exception("Destinataire invalide.");
            }
            $stmtUser = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $uName = $stmtUser->fetchColumn();
            if (!$uName) {
                throw new Exception("Daimyō introuvable.");
            }
            $recipientName = $uName;
            $this->creditPlayerResources($userId, $metal, $crystal, $deuterium, $gold, $honor);
            $this->sendImperialCompensationMessage($adminId, $userId, $metal, $crystal, $deuterium, $gold, $honor, $reason);
            $recipientsCount = 1;
            $recipientType = 'user';
            $recipientId = $userId;
        }

        // Enregistrement dans l'audit log
        $resJson = json_encode([
            'metal'     => $metal,
            'crystal'   => $crystal,
            'deuterium' => $deuterium,
            'gold'      => $gold,
            'honor'     => $honor
        ]);

        $logStmt = $this->db->prepare("
            INSERT INTO liveops_compensations_log 
            (admin_id, admin_name, recipient_type, recipient_id, recipient_name, resources_json, reason, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $adminId,
            $adminName,
            $recipientType,
            $recipientId,
            $recipientName,
            $resJson,
            $reason
        ]);

        return [
            'success'          => true,
            'recipients_count' => $recipientsCount,
            'recipient_name'   => $recipientName,
            'resources'        => [
                'metal'     => $metal,
                'crystal'   => $crystal,
                'deuterium' => $deuterium,
                'gold'      => $gold,
                'honor'     => $honor
            ],
            'message'          => "Décret de compensation délivré avec succès à {$recipientsCount} Daimyō(s) !"
        ];
    }

    private function creditPlayerResources(int $userId, int $metal, int $crystal, int $deuterium, int $gold, int $honor): void {
        // 1. Créditer les ressources sur la planète capitale du joueur
        if ($metal > 0 || $crystal > 0 || $deuterium > 0) {
            $stmtP = $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ?
                WHERE user_id = ? AND is_capital = 1
            ");
            $stmtP->execute([$metal, $crystal, $deuterium, $userId]);
        }

        // 2. Créditer les Kobans (gold_coins) et Points d'Honneur (points) sur le compte utilisateur
        if ($gold > 0 || $honor > 0) {
            $stmtU = $this->db->prepare("
                UPDATE users 
                SET gold_coins = gold_coins + ?, points = points + ?
                WHERE id = ?
            ");
            $stmtU->execute([$gold, $honor, $userId]);
        }
    }

    private function sendImperialCompensationMessage(
        int $senderId,
        int $receiverId,
        int $metal,
        int $crystal,
        int $deuterium,
        int $gold,
        int $honor,
        string $reason
    ): void {
        $subject = "📜 Décret de Dédommagement Impérial du Shōgunat";
        
        $body  = "Salutations honorable Daimyō,\n\n";
        $body .= "Par décret officiel du Shōgunat et de la Direction Live Ops, vos domaines bénéficient d'une dotation de compensation pour le motif suivant :\n\n";
        $body .= "« " . htmlspecialchars($reason) . " »\n\n";
        $body .= "Détail des richesses créditées directement dans vos greniers et trésorerie :\n";
        if ($metal > 0)     $body .= "• 🌲 Bois de Cèdre : +" . number_format($metal) . "\n";
        if ($crystal > 0)   $body .= "• ⛰️ Pierre de Taille : +" . number_format($crystal) . "\n";
        if ($deuterium > 0) $body .= "• 🌾 Riz Impérial (Koku) : +" . number_format($deuterium) . "\n";
        if ($gold > 0)      $body .= "• 🪙 Kobans d'Or : +" . number_format($gold) . "\n";
        if ($honor > 0)     $body .= "• 🎖️ Points d'Honneur : +" . number_format($honor) . "\n";
        $body .= "\nPuisse cette faveur affermir vos garnisons et la gloire de votre clan.\n";
        $body .= "— Le Grand Conseil de Production & Live Ops OpenShogun";

        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, body, created_at, is_read)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$senderId, $receiverId, $subject, $body, time()]);
        } catch (Throwable $e) {
            error_log("[LiveOpsEngine] Error sending compensation message: " . $e->getMessage());
        }
    }

    public function getCompensationHistory(int $limit = 30): array {
        if (!$this->db) return [];
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM liveops_compensations_log 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['resources'] = json_decode((string)$r['resources_json'], true) ?: [];
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }
}
