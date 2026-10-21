<?php
/**
 * Moteur Métier des Événements Hebdomadaires Narratifs (Chroniques du Sengoku)
 * OpenShogun - Système de fidélisation et de quêtes historiques séquentielles.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/WeeklyEventsData.php';

class WeeklyEventEngine {
    private ?PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureTables();
    }

    /**
     * S'assure que les tables nécessaires existent
     */
    public function ensureTables(): void {
        if (!$this->db) return;
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS weekly_events (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        slug TEXT UNIQUE NOT NULL,
                        title TEXT NOT NULL,
                        jp_title TEXT NOT NULL DEFAULT '',
                        era_year TEXT NOT NULL DEFAULT 'Époque Sengoku',
                        description TEXT NOT NULL,
                        image_url TEXT NOT NULL DEFAULT '/public/assets/buildings/hq.jpg',
                        starts_at TEXT NOT NULL,
                        ends_at TEXT NOT NULL,
                        is_active INTEGER NOT NULL DEFAULT 0,
                        reward_final_gold INTEGER NOT NULL DEFAULT 50,
                        reward_final_honor INTEGER NOT NULL DEFAULT 100,
                        reward_final_seal_days INTEGER NOT NULL DEFAULT 1,
                        reward_final_title TEXT DEFAULT NULL,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE TABLE IF NOT EXISTS weekly_event_tasks (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        event_id INTEGER NOT NULL,
                        step_order INTEGER NOT NULL DEFAULT 1,
                        title TEXT NOT NULL,
                        description TEXT NOT NULL,
                        task_type TEXT NOT NULL,
                        target_key TEXT NOT NULL,
                        target_amount INTEGER NOT NULL DEFAULT 1,
                        reward_metal INTEGER NOT NULL DEFAULT 0,
                        reward_crystal INTEGER NOT NULL DEFAULT 0,
                        reward_deuterium INTEGER NOT NULL DEFAULT 0,
                        reward_gold INTEGER NOT NULL DEFAULT 0,
                        reward_honor INTEGER NOT NULL DEFAULT 0,
                        reward_item TEXT DEFAULT NULL,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (event_id) REFERENCES weekly_events (id) ON DELETE CASCADE
                    );
                    CREATE TABLE IF NOT EXISTS player_weekly_events (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        event_id INTEGER NOT NULL,
                        is_completed INTEGER NOT NULL DEFAULT 0,
                        final_claimed INTEGER NOT NULL DEFAULT 0,
                        completed_at TEXT DEFAULT NULL,
                        claimed_at TEXT DEFAULT NULL,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE (user_id, event_id)
                    );
                    CREATE TABLE IF NOT EXISTS player_weekly_event_tasks (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        event_id INTEGER NOT NULL,
                        task_id INTEGER NOT NULL,
                        current_progress INTEGER NOT NULL DEFAULT 0,
                        is_completed INTEGER NOT NULL DEFAULT 0,
                        is_claimed INTEGER NOT NULL DEFAULT 0,
                        claimed_at TEXT DEFAULT NULL,
                        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE (user_id, task_id)
                    );
                ");
            } else {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS weekly_events (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        slug VARCHAR(100) NOT NULL UNIQUE,
                        title VARCHAR(255) NOT NULL,
                        jp_title VARCHAR(255) NOT NULL DEFAULT '',
                        era_year VARCHAR(100) NOT NULL DEFAULT 'Époque Sengoku',
                        description TEXT NOT NULL,
                        image_url VARCHAR(255) NOT NULL DEFAULT '/public/assets/buildings/hq.jpg',
                        starts_at DATETIME NOT NULL,
                        ends_at DATETIME NOT NULL,
                        is_active TINYINT(1) NOT NULL DEFAULT 0,
                        reward_final_gold INT NOT NULL DEFAULT 50,
                        reward_final_honor INT NOT NULL DEFAULT 100,
                        reward_final_seal_days INT NOT NULL DEFAULT 1,
                        reward_final_title VARCHAR(150) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS weekly_event_tasks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_id INT NOT NULL,
                        step_order INT NOT NULL DEFAULT 1,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        task_type VARCHAR(50) NOT NULL,
                        target_key VARCHAR(100) NOT NULL,
                        target_amount INT NOT NULL DEFAULT 1,
                        reward_metal INT NOT NULL DEFAULT 0,
                        reward_crystal INT NOT NULL DEFAULT 0,
                        reward_deuterium INT NOT NULL DEFAULT 0,
                        reward_gold INT NOT NULL DEFAULT 0,
                        reward_honor INT NOT NULL DEFAULT 0,
                        reward_item VARCHAR(100) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (event_id) REFERENCES weekly_events(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS player_weekly_events (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        event_id INT NOT NULL,
                        is_completed TINYINT(1) NOT NULL DEFAULT 0,
                        final_claimed TINYINT(1) NOT NULL DEFAULT 0,
                        completed_at DATETIME DEFAULT NULL,
                        claimed_at DATETIME DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uk_user_event (user_id, event_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS player_weekly_event_tasks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        event_id INT NOT NULL,
                        task_id INT NOT NULL,
                        current_progress INT NOT NULL DEFAULT 0,
                        is_completed TINYINT(1) NOT NULL DEFAULT 0,
                        is_claimed TINYINT(1) NOT NULL DEFAULT 0,
                        claimed_at DATETIME DEFAULT NULL,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uk_user_task (user_id, task_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            }

            // Amorçage automatique des 52 chroniques si la table contient moins de 52 événements
            $stCnt = $this->db->query("SELECT COUNT(*) FROM weekly_events");
            $curCount = $stCnt ? (int)$stCnt->fetchColumn() : 0;
            if ($curCount < 52) {
                $this->seedInitialEvents();
            }
        } catch (Exception $e) {}
    }

    /**
     * Initialise automatiquement les 52 Chroniques Historiques Féodales du Sengoku (1 par semaine de l'année)
     */
    public function seedInitialEvents(bool $forceAll = false): void {
        if (!$this->db) return;
        try {
            $allEvents = WeeklyEventsData::getAll();
            $isoYear = (int)date('o');
            $curWeek = (int)date('W');

            $stCheck = $this->db->prepare("SELECT id FROM weekly_events WHERE slug = ?");
            $stHasActive = $this->db->query("SELECT id FROM weekly_events WHERE is_active = 1 LIMIT 1");
            $alreadyHasActive = ($stHasActive && $stHasActive->fetchColumn()) ? true : false;

            foreach ($allEvents as $ev) {
                $stCheck->execute([$ev['slug']]);
                $existingId = $stCheck->fetchColumn();

                if ($existingId && !$forceAll) {
                    continue;
                }

                $w = (int)($ev['week'] ?? 1);
                $startsAt = date('Y-m-d 00:00:00', strtotime("{$isoYear}-W" . sprintf('%02d', $w) . "-1"));
                $endsAt   = date('Y-m-d 23:59:59', strtotime("{$isoYear}-W" . sprintf('%02d', $w) . "-7"));

                $isActive = 0;
                if (!$alreadyHasActive && $w === $curWeek) {
                    $isActive = 1;
                    $alreadyHasActive = true;
                }

                $evTasks = $ev['tasks'] ?? [];
                $evRecord = [
                    'slug'                   => $ev['slug'],
                    'title'                  => $ev['title'],
                    'jp_title'               => $ev['jp_title'] ?? '',
                    'era_year'               => $ev['era_year'] ?? 'Époque Sengoku',
                    'description'            => $ev['description'],
                    'image_url'              => $ev['image_url'] ?? '/public/assets/buildings/hq.jpg',
                    'starts_at'              => $startsAt,
                    'ends_at'                => $endsAt,
                    'is_active'              => $isActive,
                    'reward_final_gold'      => $ev['reward_final_gold'] ?? 60,
                    'reward_final_honor'     => $ev['reward_final_honor'] ?? 120,
                    'reward_final_seal_days' => $ev['reward_final_seal_days'] ?? 1,
                    'reward_final_title'     => $ev['reward_final_title'] ?? null,
                ];

                if ($existingId) {
                    $evRecord['id'] = (int)$existingId;
                }

                $evId = $this->saveEvent($evRecord);

                if (!$existingId) {
                    foreach ($evTasks as $t) {
                        $t['event_id'] = $evId;
                        $this->saveTask($t);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("WeeklyEventEngine::seedInitialEvents error: " . $e->getMessage());
        }
    }

    /**
     * Calcule précisément les bornes d'une semaine ISO (Lundi 00:00:00 au Dimanche 23:59:59)
     */
    public static function getWeekBoundaries(?int $timestamp = null, ?int $week = null, ?int $year = null): array {
        $ts = $timestamp ?? time();
        $isoYear = $year ?? (int)date('o', $ts);
        $w = $week ?? (int)date('W', $ts);
        $weekStr = sprintf('%02d', $w);

        $mondayTs = strtotime("{$isoYear}-W{$weekStr}-1 00:00:00");
        $sundayTs = strtotime("{$isoYear}-W{$weekStr}-7 23:59:59");

        $frenchMonths = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $startDay = date('j', $mondayTs);
        $startMonth = $frenchMonths[(int)date('n', $mondayTs)] ?? date('M', $mondayTs);
        $endDay = date('j', $sundayTs);
        $endMonth = $frenchMonths[(int)date('n', $sundayTs)] ?? date('M', $sundayTs);
        $endYear = date('Y', $sundayTs);

        if ($startMonth === $endMonth) {
            $periodStr = "Du Lundi {$startDay} au Dimanche {$endDay} {$endMonth} {$endYear}";
        } else {
            $periodStr = "Du Lundi {$startDay} {$startMonth} au Dimanche {$endDay} {$endMonth} {$endYear}";
        }

        return [
            'week_number'         => $w,
            'week_str'            => $weekStr,
            'iso_year'            => $isoYear,
            'starts_at'           => date('Y-m-d 00:00:00', $mondayTs),
            'ends_at'             => date('Y-m-d 23:59:59', $sundayTs),
            'starts_at_timestamp' => $mondayTs,
            'ends_at_timestamp'   => $sundayTs,
            'week_label'          => "Semaine {$w}",
            'period_label'        => "Semaine {$w} • {$periodStr}",
            'period_text'         => $periodStr,
            'starts_at_formatted' => "Lundi {$startDay} {$startMonth} {$endYear}",
            'ends_at_formatted'   => "Dimanche {$endDay} {$endMonth} {$endYear}"
        ];
    }

    /**
     * Synchronise et active automatiquement la Chronique Féodale de la semaine en cours (Lundi au Dimanche)
     */
    public function syncCurrentWeekEvent(): ?array {
        if (!$this->db) return null;

        $bounds = self::getWeekBoundaries();
        $curWeekStr = $bounds['week_str'];
        $monday = $bounds['starts_at'];
        $sunday = $bounds['ends_at'];

        // Chercher l'événement correspondant à la semaine actuelle
        $stmt = $this->db->prepare("SELECT * FROM weekly_events WHERE slug LIKE ? LIMIT 1");
        $stmt->execute(["semaine_{$curWeekStr}_%"]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            // Désactiver tous les autres événements expirés
            $this->db->prepare("UPDATE weekly_events SET is_active = 0 WHERE id != ? AND is_active = 1")->execute([$event['id']]);
            // Activer la chronique de la semaine actuelle avec ses bornes exactes Lundi 00:00 -> Dimanche 23:59
            $this->db->prepare("UPDATE weekly_events SET is_active = 1, starts_at = ?, ends_at = ? WHERE id = ?")
                     ->execute([$monday, $sunday, $event['id']]);
            $event['is_active'] = 1;
            $event['starts_at'] = $monday;
            $event['ends_at']   = $sunday;
        }

        return $event;
    }

    /**
     * Recalcule et synchronise les dates du Lundi 00:00:00 au Dimanche 23:59:59 pour toutes les 52 chroniques
     */
    public function resyncAllWeekDates(?int $year = null): int {
        if (!$this->db) return 0;
        $isoYear = $year ?? (int)date('o');
        $updated = 0;

        $stmt = $this->db->query("SELECT id, slug FROM weekly_events");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtUp = $this->db->prepare("UPDATE weekly_events SET starts_at = ?, ends_at = ? WHERE id = ?");

        foreach ($events as $ev) {
            if (preg_match('/semaine_(\d+)_/i', $ev['slug'], $m)) {
                $w = (int)$m[1];
                $bounds = self::getWeekBoundaries(null, $w, $isoYear);
                $stmtUp->execute([$bounds['starts_at'], $bounds['ends_at'], $ev['id']]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Récupère l'événement hebdomadaire actuellement actif
     */
    public function getActiveEvent(?int $userId = null, ?int $planetId = null): ?array {
        if (!$this->db) return null;

        $now = date('Y-m-d H:i:s');
        $nowTs = time();
        $currentBounds = self::getWeekBoundaries($nowTs);
        $curWeek = $currentBounds['week_number'];
        $curWeekStr = $currentBounds['week_str'];

        // 1. Chercher un événement explicitement marqué actif
        $stmt = $this->db->prepare("
            SELECT * FROM weekly_events 
            WHERE is_active = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Contrôle de validité : si l'événement marqué actif est expiré ou ne correspond pas à la semaine actuelle
        $needsResync = false;
        if ($event) {
            $endTs = strtotime($event['ends_at']);
            $startTs = strtotime($event['starts_at']);
            // Si l'événement est périmé dans le temps ou hors de sa semaine
            if ($endTs < $nowTs || $startTs > $nowTs) {
                $needsResync = true;
            }
        } else {
            $needsResync = true;
        }

        // 3. Auto-synchronisation sur la Chronique de la Semaine Courante (Lundi au Dimanche)
        if ($needsResync) {
            $synced = $this->syncCurrentWeekEvent();
            if ($synced) {
                $event = $synced;
            }
        }

        // 4. Si toujours non trouvé, chercher par fenêtre de dates du moment
        if (!$event) {
            $stmtDate = $this->db->prepare("
                SELECT * FROM weekly_events 
                WHERE starts_at <= ? AND ends_at >= ?
                ORDER BY starts_at DESC 
                LIMIT 1
            ");
            $stmtDate->execute([$now, $now]);
            $event = $stmtDate->fetch(PDO::FETCH_ASSOC);
        }

        // 5. Chercher par numéro de semaine de l'année (semaine_XX_...)
        if (!$event) {
            $stmtWeek = $this->db->prepare("
                SELECT * FROM weekly_events 
                WHERE slug LIKE ? 
                LIMIT 1
            ");
            $stmtWeek->execute(["semaine_{$curWeekStr}_%"]);
            $event = $stmtWeek->fetch(PDO::FETCH_ASSOC);
            if ($event) {
                $this->db->prepare("UPDATE weekly_events SET is_active = 1, starts_at = ?, ends_at = ? WHERE id = ?")
                         ->execute([$currentBounds['starts_at'], $currentBounds['ends_at'], $event['id']]);
                $event['is_active'] = 1;
                $event['starts_at'] = $currentBounds['starts_at'];
                $event['ends_at']   = $currentBounds['ends_at'];
            }
        }

        // 6. Fallback de sécurité
        if (!$event) {
            $stmtAny = $this->db->query("SELECT * FROM weekly_events ORDER BY id ASC LIMIT 1");
            $event = $stmtAny ? $stmtAny->fetch(PDO::FETCH_ASSOC) : null;
        }

        if (!$event) {
            return null;
        }

        $eventId = (int)$event['id'];

        // Enrichissement des métadonnées temporelles (Numéro de semaine & Période Lundi -> Dimanche)
        $evWeek = $curWeek;
        if (preg_match('/semaine_(\d+)_/i', $event['slug'] ?? '', $m)) {
            $evWeek = (int)$m[1];
        }
        $evBounds = self::getWeekBoundaries(strtotime($event['starts_at']), $evWeek);
        $event['week_number']         = $evBounds['week_number'];
        $event['week_label']          = $evBounds['week_label'];
        $event['period_label']        = $evBounds['period_label'];
        $event['period_text']         = $evBounds['period_text'];
        $event['starts_at_formatted'] = $evBounds['starts_at_formatted'];
        $event['ends_at_formatted']   = $evBounds['ends_at_formatted'];

        // Récupérer les tâches de l'événement
        $stmtTasks = $this->db->prepare("
            SELECT * FROM weekly_event_tasks 
            WHERE event_id = ? 
            ORDER BY step_order ASC, id ASC
        ");
        $stmtTasks->execute([$eventId]);
        $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        $event['tasks'] = $tasks;
        $event['total_tasks'] = count($tasks);

        // Calcul du temps restant (en secondes jusqu'au dimanche 23h59)
        $endTs = strtotime($event['ends_at']);
        $event['seconds_remaining'] = max(0, $endTs - time());

        // Si contexte joueur fourni, évaluer et fusionner sa progression
        if ($userId !== null && $planetId !== null) {
            $progressData = $this->evaluateProgress($userId, $planetId, $eventId);
            $event['player_status'] = $progressData;
        }

        return $event;
    }

    /**
     * Évalue et synchronise la progression d'un joueur sur un événement
     */
    public function evaluateProgress(int $userId, int $planetId, ?int $eventId = null): array {
        if (!$this->db) {
            return ['completed_tasks' => 0, 'total_tasks' => 0, 'all_completed' => false, 'final_claimed' => false, 'tasks' => []];
        }

        if ($eventId === null) {
            $active = $this->getActiveEvent();
            if (!$active) {
                return ['completed_tasks' => 0, 'total_tasks' => 0, 'all_completed' => false, 'final_claimed' => false, 'tasks' => []];
            }
            $eventId = (int)$active['id'];
        }

        // 1. Récupérer l'état de l'événement pour ce joueur
        $stmtPwe = $this->db->prepare("SELECT * FROM player_weekly_events WHERE user_id = ? AND event_id = ?");
        $stmtPwe->execute([$userId, $eventId]);
        $pwe = $stmtPwe->fetch(PDO::FETCH_ASSOC);

        if (!$pwe) {
            $this->db->prepare("INSERT INTO player_weekly_events (user_id, event_id, is_completed, final_claimed) VALUES (?, ?, 0, 0)")
                ->execute([$userId, $eventId]);
            $pwe = ['user_id' => $userId, 'event_id' => $eventId, 'is_completed' => 0, 'final_claimed' => 0];
        }

        // 2. Charger les tâches de l'événement
        $stmtTasks = $this->db->prepare("SELECT * FROM weekly_event_tasks WHERE event_id = ? ORDER BY step_order ASC, id ASC");
        $stmtTasks->execute([$eventId]);
        $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        // 3. Charger l'état enregistré des tâches pour ce joueur
        $stmtPwet = $this->db->prepare("SELECT * FROM player_weekly_event_tasks WHERE user_id = ? AND event_id = ?");
        $stmtPwet->execute([$userId, $eventId]);
        $pwetRows = $stmtPwet->fetchAll(PDO::FETCH_ASSOC);
        $playerTaskMap = [];
        foreach ($pwetRows as $pt) {
            $playerTaskMap[(int)$pt['task_id']] = $pt;
        }

        // 4. Charger les données en temps réel de la planète et du compte
        $planet = [];
        $buildings = [];
        try {
            require_once __DIR__ . '/PlanetEngine.php';
            $pe = new PlanetEngine($this->db);
            $planet = $pe->getPlanet($planetId);
            $buildings = $pe->getBuildings($planetId);
        } catch (Throwable $e) {
            // Lecture directe si PlanetEngine dépend de tables annexes (ex: chantiers non initialisés)
            try {
                $stmtP = $this->db->prepare("SELECT * FROM planets WHERE id = ?");
                $stmtP->execute([$planetId]);
                $planet = $stmtP->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {}

            try {
                $stmtB = $this->db->prepare("SELECT building_type, level FROM planet_buildings WHERE planet_id = ?");
                $stmtB->execute([$planetId]);
                while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
                    $buildings[$b['building_type']] = (int)$b['level'];
                }
            } catch (Throwable $e2) {}
        }

        // Parcelles rurales
        $ruralPlots = [];
        try {
            $stmtRural = $this->db->prepare("SELECT structure_type, level FROM planet_rural_plots WHERE planet_id = ?");
            $stmtRural->execute([$planetId]);
            while ($r = $stmtRural->fetch(PDO::FETCH_ASSOC)) {
                $ruralPlots[$r['structure_type']] = (int)$r['level'];
            }
        } catch (Throwable $e) {}

        // Troupes & Forces Armées Féodales du Joueur
        $troops = [];
        $totalTroops = 0;
        try {
            // Traiter la file d'entraînement de la caserne/dojo sur le fief actif et tous les fiefs du joueur
            if (isset($pe) && is_object($pe)) {
                try {
                    $pe->processBarracksQueue($planetId);
                    $stmtUserPlanets = $this->db->prepare("SELECT id FROM planets WHERE user_id = ?");
                    $stmtUserPlanets->execute([$userId]);
                    while ($pRow = $stmtUserPlanets->fetch(PDO::FETCH_ASSOC)) {
                        $pid = (int)$pRow['id'];
                        if ($pid !== $planetId) {
                            $pe->processBarracksQueue($pid);
                        }
                    }
                } catch (Throwable $e) {}
            }

            // 1. Troupes stationnées en garnison (sur l'ensemble des fiefs du joueur)
            $stmtUnits = $this->db->prepare("
                SELECT pu.unit_code, SUM(pu.count) as total_count 
                FROM planet_units pu 
                JOIN planets p ON pu.planet_id = p.id 
                WHERE p.user_id = ? 
                GROUP BY pu.unit_code
            ");
            $stmtUnits->execute([$userId]);
            while ($u = $stmtUnits->fetch(PDO::FETCH_ASSOC)) {
                $cnt = (int)$u['total_count'];
                $troops[$u['unit_code']] = ($troops[$u['unit_code']] ?? 0) + $cnt;
                $totalTroops += $cnt;
            }

            // 2. Troupes en file d'attente active ou commandées au Dojo (barracks_queue)
            $stmtQueue = $this->db->prepare("
                SELECT bq.unit_code, SUM(COALESCE(bq.total_count, bq.count, 0)) as queue_count 
                FROM barracks_queue bq 
                JOIN planets p ON bq.planet_id = p.id 
                WHERE p.user_id = ? 
                GROUP BY bq.unit_code
            ");
            $stmtQueue->execute([$userId]);
            while ($qRow = $stmtQueue->fetch(PDO::FETCH_ASSOC)) {
                $qCnt = (int)$qRow['queue_count'];
                if ($qCnt > 0) {
                    $uCode = $qRow['unit_code'];
                    $troops[$uCode] = ($troops[$uCode] ?? 0) + $qCnt;
                    $totalTroops += $qCnt;
                }
            }

            // 3. Troupes déployées en expédition / marche (fleet_missions)
            $stmtMissions = $this->db->prepare("
                SELECT fleet_data FROM fleet_missions 
                WHERE user_id = ? AND status IN ('en_route', 'returning')
            ");
            $stmtMissions->execute([$userId]);
            while ($m = $stmtMissions->fetch(PDO::FETCH_ASSOC)) {
                $fData = json_decode($m['fleet_data'] ?? '{}', true);
                if (is_array($fData)) {
                    foreach ($fData as $uCode => $uQty) {
                        $q = (int)$uQty;
                        if ($q > 0) {
                            $troops[$uCode] = ($troops[$uCode] ?? 0) + $q;
                            $totalTroops += $q;
                        }
                    }
                }
            }
        } catch (Throwable $e) {}

        if (empty($troops)) {
            try {
                $stmtTroops = $this->db->prepare("
                    SELECT pt.unit_code, SUM(pt.quantity) as total_quantity 
                    FROM planet_troops pt 
                    JOIN planets p ON pt.planet_id = p.id 
                    WHERE p.user_id = ? 
                    GROUP BY pt.unit_code
                ");
                $stmtTroops->execute([$userId]);
                while ($t = $stmtTroops->fetch(PDO::FETCH_ASSOC)) {
                    $cnt = (int)($t['total_quantity'] ?? 0);
                    $troops[$t['unit_code']] = ($troops[$t['unit_code']] ?? 0) + $cnt;
                    $totalTroops += $cnt;
                }
            } catch (Throwable $e) {}
        }

        // Samouraï Héros
        $heroStrength = 150;
        try {
            require_once __DIR__ . '/HeroEngine.php';
            $heroEngine = new HeroEngine($this->db);
            $hero = $heroEngine->getHeroByUserId($userId);
            if ($hero && isset($hero['effective']['combat_strength'])) {
                $heroStrength = (int)$hero['effective']['combat_strength'];
            }
        } catch (Exception $e) {}

        $tasksEvaluated = [];
        $completedCount = 0;
        $claimedCount = 0;

        foreach ($tasks as $task) {
            $tId = (int)$task['id'];
            $type = $task['task_type'];
            $key = $task['target_key'];
            $target = (int)$task['target_amount'];

            $currentVal = 0;
            switch ($type) {
                case 'stock_resource':
                case 'resource_stock':
                    $currentVal = (int)($planet[$key] ?? 0);
                    break;
                case 'upgrade_building':
                case 'build_level':
                    $currentVal = (int)($buildings[$key] ?? 0);
                    break;
                case 'upgrade_rural':
                case 'rural_level':
                    $currentVal = (int)($ruralPlots[$key] ?? 0);
                    break;
                case 'train_troops':
                case 'troops_count':
                    $normKey = strtolower(trim((string)$key));
                    if ($normKey === 'all' || empty($normKey) || $normKey === 'any') {
                        $currentVal = $totalTroops;
                    } elseif ($normKey === 'spearman' || $normKey === 'piquier' || $normKey === 'yari' || $normKey === 'infantry' || $normKey === 'ashigaru') {
                        // Troupes de type piquiers / lanciers / fantassins selon les 3 clans féodaux :
                        // - Clan Oda (terran) : piquier_ashigaru_yari
                        // - Clan Tokugawa (aethelis) : sentinelle_yari_tokugawa
                        // - Clan Takeda (vorash) : fantassin_leger_takeda
                        $spearmanCount = (int)($troops['spearman'] ?? 0)
                            + (int)($troops['piquier_ashigaru_yari'] ?? 0)
                            + (int)($troops['sentinelle_yari_tokugawa'] ?? 0)
                            + (int)($troops['fantassin_leger_takeda'] ?? 0);
                        
                        // Si le joueur a formé des lanciers de son clan ou au moins le quota de soldats
                        $currentVal = ($spearmanCount > 0) ? max($spearmanCount, $totalTroops) : $totalTroops;
                    } elseif ($normKey === 'archer' || $normKey === 'yumi') {
                        $archerCount = (int)($troops['archer'] ?? 0)
                            + (int)($troops['archer_protecteur_muraille'] ?? 0)
                            + (int)($troops['archer_yumi_monte'] ?? 0);
                        $currentVal = ($archerCount > 0) ? max($archerCount, $totalTroops) : $totalTroops;
                    } elseif ($normKey === 'cavalry' || $normKey === 'cavalier' || $normKey === 'horse') {
                        $cavalryCount = (int)($troops['cavalry'] ?? 0)
                            + (int)($troops['cavalier_rouge_akazonae'] ?? 0)
                            + (int)($troops['archer_yumi_monte'] ?? 0);
                        $currentVal = ($cavalryCount > 0) ? max($cavalryCount, $totalTroops) : $totalTroops;
                    } elseif ($normKey === 'gunner' || $normKey === 'arquebusier' || $normKey === 'tanegashima') {
                        $gunnerCount = (int)($troops['gunner'] ?? 0)
                            + (int)($troops['arquebusier_oda_tanegashima'] ?? 0);
                        $currentVal = ($gunnerCount > 0) ? max($gunnerCount, $totalTroops) : $totalTroops;
                    } elseif ($normKey === 'samurai' || $normKey === 'katana' || $normKey === 'nodachi' || $normKey === 'hatamoto') {
                        $samuraiCount = (int)($troops['samurai'] ?? 0)
                            + (int)($troops['samourai_katana'] ?? 0)
                            + (int)($troops['garde_hatamoto_armure_lourde'] ?? 0)
                            + (int)($troops['maitre_nodachi_kai'] ?? 0)
                            + (int)($troops['hatamoto_venerable_tokugawa'] ?? 0);
                        $currentVal = ($samuraiCount > 0) ? max($samuraiCount, $totalTroops) : $totalTroops;
                    } else {
                        $currentVal = (int)($troops[$key] ?? 0);
                        if ($currentVal === 0 && $totalTroops >= $target) {
                            $currentVal = $totalTroops;
                        }
                    }
                    break;
                case 'hero_stat':
                    $currentVal = $heroStrength;
                    break;
                default:
                    $currentVal = (int)($planet[$key] ?? 0);
                    break;
            }

            $ptRecord = $playerTaskMap[$tId] ?? null;
            $isClaimed = $ptRecord ? (int)$ptRecord['is_claimed'] : 0;
            $isCompleted = ($currentVal >= $target) || ($ptRecord && (int)$ptRecord['is_completed'] === 1);
            $recordedProgress = max($currentVal, $ptRecord ? (int)$ptRecord['current_progress'] : 0);

            // Mettre à jour en BDD si changement
            if (!$ptRecord) {
                $this->db->prepare("
                    INSERT INTO player_weekly_event_tasks (user_id, event_id, task_id, current_progress, is_completed, is_claimed)
                    VALUES (?, ?, ?, ?, ?, 0)
                ")->execute([$userId, $eventId, $tId, $recordedProgress, $isCompleted ? 1 : 0]);
            } else {
                if ($recordedProgress != (int)$ptRecord['current_progress'] || ($isCompleted ? 1 : 0) != (int)$ptRecord['is_completed']) {
                    $this->db->prepare("
                        UPDATE player_weekly_event_tasks 
                        SET current_progress = ?, is_completed = ? 
                        WHERE user_id = ? AND task_id = ?
                    ")->execute([$recordedProgress, $isCompleted ? 1 : 0, $userId, $tId]);
                }
            }

            if ($isCompleted) $completedCount++;
            if ($isClaimed) $claimedCount++;

            $pct = ($target > 0) ? min(100, round(($recordedProgress / $target) * 100)) : 100;

            $tasksEvaluated[] = array_merge($task, [
                'current_progress' => $recordedProgress,
                'target_amount'    => $target,
                'progress_pct'     => $pct,
                'is_completed'     => (bool)$isCompleted,
                'is_claimed'       => (bool)$isClaimed,
                'claimed_at'       => $ptRecord['claimed_at'] ?? null,
            ]);
        }

        $allCompleted = ($completedCount >= count($tasks)) && count($tasks) > 0;
        $finalClaimed = (int)($pwe['final_claimed'] ?? 0) === 1;

        if ($allCompleted && (int)$pwe['is_completed'] === 0) {
            $nowStr = date('Y-m-d H:i:s');
            $this->db->prepare("UPDATE player_weekly_events SET is_completed = 1, completed_at = ? WHERE user_id = ? AND event_id = ?")
                ->execute([$nowStr, $userId, $eventId]);
        }

        return [
            'event_id'         => $eventId,
            'completed_tasks'  => $completedCount,
            'claimed_tasks'    => $claimedCount,
            'total_tasks'      => count($tasks),
            'progress_percent' => count($tasks) > 0 ? (int)round(($completedCount / count($tasks)) * 100) : 0,
            'all_completed'    => $allCompleted,
            'final_claimed'    => $finalClaimed,
            'tasks'            => $tasksEvaluated
        ];
    }

    /**
     * Réclamer la récompense d'une tâche d'événement
     */
    public function claimTaskReward(int $userId, int $planetId, int $taskId): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Erreur de connexion base de données.'];
        }

        // Récupérer la tâche
        $stmtT = $this->db->prepare("SELECT * FROM weekly_event_tasks WHERE id = ?");
        $stmtT->execute([$taskId]);
        $task = $stmtT->fetch(PDO::FETCH_ASSOC);
        if (!$task) {
            return ['success' => false, 'error' => 'Tâche introuvable.'];
        }

        $eventId = (int)$task['event_id'];

        // Vérifier l'état de la tâche
        $stmtPt = $this->db->prepare("SELECT * FROM player_weekly_event_tasks WHERE user_id = ? AND task_id = ?");
        $stmtPt->execute([$userId, $taskId]);
        $pt = $stmtPt->fetch(PDO::FETCH_ASSOC);

        if (!$pt || (int)$pt['is_completed'] !== 1) {
            // Tentative de réévaluation à la volée
            $this->evaluateProgress($userId, $planetId, $eventId);
            $stmtPt->execute([$userId, $taskId]);
            $pt = $stmtPt->fetch(PDO::FETCH_ASSOC);
            if (!$pt || (int)$pt['is_completed'] !== 1) {
                return ['success' => false, 'error' => 'Les conditions de cet acte narratif ne sont pas encore remplies.'];
            }
        }

        if ((int)$pt['is_claimed'] === 1) {
            return ['success' => false, 'error' => 'La récompense de cet acte a déjà été perçue.'];
        }

        // Attribution des récompenses
        $rMetal = (int)$task['reward_metal'];
        $rCrystal = (int)$task['reward_crystal'];
        $rDeut = (int)$task['reward_deuterium'];
        $rGold = (int)$task['reward_gold'];
        $rHonor = (int)$task['reward_honor'];
        $rItem = trim((string)($task['reward_item'] ?? ''));

        // 1. Ressources sur la planète
        if ($rMetal > 0 || $rCrystal > 0 || $rDeut > 0) {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                WHERE id = ?
            ")->execute([$rMetal, $rCrystal, $rDeut, $planetId]);
        }

        // 2. Or et Honneur sur l'utilisateur
        if ($rGold > 0 || $rHonor > 0) {
            try {
                $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                $chkHonor = ($driver === 'sqlite') ? true : $this->db->query("SHOW COLUMNS FROM users LIKE 'honor_points'")->rowCount() > 0;
                if ($chkHonor) {
                    $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ?, honor_points = honor_points + ? WHERE id = ?")
                        ->execute([$rGold, $rHonor, $userId]);
                } else {
                    $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ? WHERE id = ?")
                        ->execute([$rGold, $userId]);
                }
            } catch (Exception $e) {
                $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ? WHERE id = ?")
                    ->execute([$rGold, $userId]);
            }
        }

        // 3. Item / Denrées raffinées spéciales (ex: rice_flour:40, sake:30, wooden_beams:20)
        if (!empty($rItem) && str_contains($rItem, ':')) {
            [$itemKey, $itemQty] = explode(':', $rItem, 2);
            $itemQty = (float)$itemQty;
            $allowedItems = ['rice_flour', 'sake', 'wooden_beams', 'clay', 'tea', 'soybean'];
            if (in_array($itemKey, $allowedItems, true) && $itemQty > 0) {
                try {
                    $this->db->prepare("UPDATE planets SET `{$itemKey}` = `{$itemKey}` + ? WHERE id = ?")
                        ->execute([$itemQty, $planetId]);
                } catch (Exception $e) {}
            }
        }

        // Marquer comme réclamé
        $nowStr = date('Y-m-d H:i:s');
        $this->db->prepare("UPDATE player_weekly_event_tasks SET is_claimed = 1, claimed_at = ? WHERE user_id = ? AND task_id = ?")
            ->execute([$nowStr, $userId, $taskId]);

        return [
            'success' => true,
            'message' => "Récompense de « {$task['title']} » perçue avec honneur !",
            'reward'  => [
                'metal'     => $rMetal,
                'crystal'   => $rCrystal,
                'deuterium' => $rDeut,
                'gold'      => $rGold,
                'honor'     => $rHonor,
                'item'      => $rItem
            ]
        ];
    }

    /**
     * Réclamer la grande récompense finale de l'événement hebdomadaire
     */
    public function claimFinalReward(int $userId, int $planetId, int $eventId): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Erreur de connexion base de données.'];
        }

        $stmtEv = $this->db->prepare("SELECT * FROM weekly_events WHERE id = ?");
        $stmtEv->execute([$eventId]);
        $event = $stmtEv->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            return ['success' => false, 'error' => 'Événement introuvable.'];
        }

        $progress = $this->evaluateProgress($userId, $planetId, $eventId);
        if (!$progress['all_completed']) {
            return ['success' => false, 'error' => 'Vous devez accomplir tous les actes narratifs de la chronique avant de clore l\'événement.'];
        }

        if ($progress['final_claimed']) {
            return ['success' => false, 'error' => 'La grande récompense de cet événement a déjà été perçue.'];
        }

        $fGold = (int)$event['reward_final_gold'];
        $fHonor = (int)$event['reward_final_honor'];
        $fSealDays = (int)$event['reward_final_seal_days'];
        $fTitle = trim((string)($event['reward_final_title'] ?? ''));

        // 1. Or & Honneur
        if ($fGold > 0 || $fHonor > 0) {
            try {
                $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                $chkHonor = ($driver === 'sqlite') ? true : $this->db->query("SHOW COLUMNS FROM users LIKE 'honor_points'")->rowCount() > 0;
                if ($chkHonor) {
                    $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ?, honor_points = honor_points + ? WHERE id = ?")
                        ->execute([$fGold, $fHonor, $userId]);
                } else {
                    $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ? WHERE id = ?")
                        ->execute([$fGold, $userId]);
                }
            } catch (Exception $e) {
                $this->db->prepare("UPDATE users SET gold_coins = gold_coins + ? WHERE id = ?")
                    ->execute([$fGold, $userId]);
            }
        }

        // 2. Extension du Sceau Impérial
        if ($fSealDays > 0) {
            try {
                $st = $this->db->prepare("SELECT imperial_seal_until FROM users WHERE id = ?");
                $st->execute([$userId]);
                $existingUntil = $st->fetchColumn();

                $now = time();
                $startTime = ($existingUntil && strtotime($existingUntil) > $now) 
                    ? strtotime($existingUntil) 
                    : $now;

                $newUntil = date('Y-m-d H:i:s', $startTime + ($fSealDays * 86400));
                $this->db->prepare("UPDATE users SET imperial_seal_until = ? WHERE id = ?")
                    ->execute([$newUntil, $userId]);
            } catch (Throwable $e) {}
        }

        // 3. Titre honorifique (Stocké dans honorary_title ou motto)
        if (!empty($fTitle)) {
            try {
                $this->db->prepare("UPDATE users SET honorary_title = ? WHERE id = ?")
                    ->execute([$fTitle, $userId]);
            } catch (Throwable $e) {
                try {
                    $this->db->prepare("UPDATE users SET motto = ? WHERE id = ? AND (motto IS NULL OR motto = '')")
                        ->execute([$fTitle, $userId]);
                } catch (Throwable $e2) {}
            }
        }

        // Marquer la récompense finale comme réclamée
        $nowStr = date('Y-m-d H:i:s');
        $this->db->prepare("UPDATE player_weekly_events SET final_claimed = 1, claimed_at = ? WHERE user_id = ? AND event_id = ?")
            ->execute([$nowStr, $userId, $eventId]);

        return [
            'success' => true,
            'message' => "Félicitations noble Daimyō ! Vous avez parachevé la chronique « {$event['title']} » avec gloire !",
            'rewards' => [
                'gold'      => $fGold,
                'honor'     => $fHonor,
                'seal_days' => $fSealDays,
                'title'     => $fTitle
            ]
        ];
    }

    // =========================================================================
    // MÉTHODES DE GESTION ADMINISTRATIVE (BACK-OFFICE)
    // =========================================================================

    /**
     * Liste tous les événements enregistrés
     */
    public function getAllEvents(): array {
        if (!$this->db) return [];
        $stmt = $this->db->query("
            SELECT e.*, 
                   COUNT(t.id) as task_count,
                   (SELECT COUNT(*) FROM player_weekly_events p WHERE p.event_id = e.id AND p.is_completed = 1) as completed_players_count
            FROM weekly_events e
            LEFT JOIN weekly_event_tasks t ON e.id = t.event_id
            GROUP BY e.id
            ORDER BY e.is_active DESC, e.starts_at DESC
        ");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Récupère un événement avec ses tâches pour l'édition admin
     */
    public function getEventDetails(int $eventId): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM weekly_events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) return null;

        $stmtTasks = $this->db->prepare("SELECT * FROM weekly_event_tasks WHERE event_id = ? ORDER BY step_order ASC, id ASC");
        $stmtTasks->execute([$eventId]);
        $event['tasks'] = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        return $event;
    }

    /**
     * Création ou mise à jour d'un événement
     */
    public function saveEvent(array $data): int {
        if (!$this->db) throw new Exception("Connexion base de données indisponible.");

        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        $title = trim((string)($data['title'] ?? 'Nouvelle Chronique'));
        $slug = trim((string)($data['slug'] ?? ''));
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $title)) . '_' . time();
        }
        $jpTitle = trim((string)($data['jp_title'] ?? ''));
        $eraYear = trim((string)($data['era_year'] ?? 'Époque Sengoku'));
        $desc = trim((string)($data['description'] ?? ''));
        $img = trim((string)($data['image_url'] ?? '/public/assets/buildings/hq.jpg'));
        $startsAt = !empty($data['starts_at']) ? date('Y-m-d H:i:s', strtotime($data['starts_at'])) : date('Y-m-d 00:00:00');
        $endsAt = !empty($data['ends_at']) ? date('Y-m-d H:i:s', strtotime($data['ends_at'])) : date('Y-m-d 23:59:59', strtotime('+7 days'));
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $rGold = max(0, (int)($data['reward_final_gold'] ?? 50));
        $rHonor = max(0, (int)($data['reward_final_honor'] ?? 100));
        $rSeal = max(0, (int)($data['reward_final_seal_days'] ?? 1));
        $rTitle = trim((string)($data['reward_final_title'] ?? ''));

        if ($isActive === 1) {
            // Désactiver les autres événements actifs pour n'en conserver qu'un
            $this->db->exec("UPDATE weekly_events SET is_active = 0");
        }

        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE weekly_events
                SET slug = ?, title = ?, jp_title = ?, era_year = ?, description = ?, image_url = ?,
                    starts_at = ?, ends_at = ?, is_active = ?, reward_final_gold = ?, reward_final_honor = ?,
                    reward_final_seal_days = ?, reward_final_title = ?
                WHERE id = ?
            ");
            $stmt->execute([$slug, $title, $jpTitle, $eraYear, $desc, $img, $startsAt, $endsAt, $isActive, $rGold, $rHonor, $rSeal, $rTitle, $id]);
            return $id;
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO weekly_events (slug, title, jp_title, era_year, description, image_url, starts_at, ends_at, is_active, reward_final_gold, reward_final_honor, reward_final_seal_days, reward_final_title)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$slug, $title, $jpTitle, $eraYear, $desc, $img, $startsAt, $endsAt, $isActive, $rGold, $rHonor, $rSeal, $rTitle]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Supprime un événement et ses tâches associées
     */
    public function deleteEvent(int $eventId): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM weekly_events WHERE id = ?");
        return $stmt->execute([$eventId]);
    }

    /**
     * Définit l'événement actif en 1 clic
     */
    public function setActiveEvent(int $eventId): bool {
        if (!$this->db) return false;
        $this->db->exec("UPDATE weekly_events SET is_active = 0");
        $stmt = $this->db->prepare("UPDATE weekly_events SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$eventId]);
    }

    /**
     * Sauvegarde d'une tâche d'événement
     */
    public function saveTask(array $data): int {
        if (!$this->db) throw new Exception("Connexion base de données indisponible.");

        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        $eventId = (int)($data['event_id'] ?? 0);
        if ($eventId <= 0) throw new Exception("Identifiant d'événement manquant.");

        $stepOrder = max(1, (int)($data['step_order'] ?? 1));
        $title = trim((string)($data['title'] ?? 'Acte'));
        $desc = trim((string)($data['description'] ?? ''));
        $taskType = trim((string)($data['task_type'] ?? 'stock_resource'));
        $targetKey = trim((string)($data['target_key'] ?? 'deuterium'));
        $targetAmount = max(1, (int)($data['target_amount'] ?? 100));

        $rMetal = max(0, (int)($data['reward_metal'] ?? 0));
        $rCrystal = max(0, (int)($data['reward_crystal'] ?? 0));
        $rDeut = max(0, (int)($data['reward_deuterium'] ?? 0));
        $rGold = max(0, (int)($data['reward_gold'] ?? 0));
        $rHonor = max(0, (int)($data['reward_honor'] ?? 0));
        $rItem = trim((string)($data['reward_item'] ?? ''));

        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE weekly_event_tasks
                SET step_order = ?, title = ?, description = ?, task_type = ?, target_key = ?, target_amount = ?,
                    reward_metal = ?, reward_crystal = ?, reward_deuterium = ?, reward_gold = ?, reward_honor = ?, reward_item = ?
                WHERE id = ? AND event_id = ?
            ");
            $stmt->execute([$stepOrder, $title, $desc, $taskType, $targetKey, $targetAmount, $rMetal, $rCrystal, $rDeut, $rGold, $rHonor, $rItem, $id, $eventId]);
            return $id;
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO weekly_event_tasks (event_id, step_order, title, description, task_type, target_key, target_amount, reward_metal, reward_crystal, reward_deuterium, reward_gold, reward_honor, reward_item)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$eventId, $stepOrder, $title, $desc, $taskType, $targetKey, $targetAmount, $rMetal, $rCrystal, $rDeut, $rGold, $rHonor, $rItem]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Supprime une tâche
     */
    public function deleteTask(int $taskId): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM weekly_event_tasks WHERE id = ?");
        return $stmt->execute([$taskId]);
    }
}
