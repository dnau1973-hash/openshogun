<?php
/**
 * Moteur Métier des Événements Hebdomadaires Narratifs (Chroniques du Sengoku)
 * OpenShogun - Système de fidélisation et de quêtes historiques séquentielles.
 */
require_once __DIR__ . '/Database.php';

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
        } catch (Exception $e) {}
    }

    /**
     * Récupère l'événement hebdomadaire actuellement actif
     */
    public function getActiveEvent(?int $userId = null, ?int $planetId = null): ?array {
        if (!$this->db) return null;

        $now = date('Y-m-d H:i:s');
        // Priorité 1 : Événement explicitement marqué actif
        $stmt = $this->db->prepare("
            SELECT * FROM weekly_events 
            WHERE is_active = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // Priorité 2 : Si aucun actif forcé, chercher par fenêtre de dates
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

        // Priorité 3 : Fallback sur le tout premier événement existant
        if (!$event) {
            $stmtAny = $this->db->query("SELECT * FROM weekly_events ORDER BY id ASC LIMIT 1");
            $event = $stmtAny ? $stmtAny->fetch(PDO::FETCH_ASSOC) : null;
        }

        if (!$event) {
            return null;
        }

        $eventId = (int)$event['id'];

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

        // Calcul du temps restant (en secondes)
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
                $stmtB = $this->db->prepare("SELECT building_code, level FROM planet_buildings WHERE planet_id = ?");
                $stmtB->execute([$planetId]);
                while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
                    $buildings[$b['building_code']] = (int)$b['level'];
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

        // Troupes
        $troops = [];
        $totalTroops = 0;
        try {
            $stmtUnits = $this->db->prepare("SELECT unit_code, count FROM planet_units WHERE planet_id = ?");
            $stmtUnits->execute([$planetId]);
            while ($u = $stmtUnits->fetch(PDO::FETCH_ASSOC)) {
                $cnt = (int)$u['count'];
                $troops[$u['unit_code']] = $cnt;
                $totalTroops += $cnt;
            }
        } catch (Throwable $e) {}

        if (empty($troops)) {
            try {
                $stmtTroops = $this->db->prepare("SELECT unit_code, quantity FROM planet_troops WHERE planet_id = ?");
                $stmtTroops->execute([$planetId]);
                while ($t = $stmtTroops->fetch(PDO::FETCH_ASSOC)) {
                    $cnt = (int)($t['quantity'] ?? 0);
                    $troops[$t['unit_code']] = $cnt;
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
                    $currentVal = ($key === 'all') ? $totalTroops : (int)($troops[$key] ?? 0);
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
