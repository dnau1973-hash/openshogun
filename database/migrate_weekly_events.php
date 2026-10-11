<?php
/**
 * Migration & Initialisation du Système d'Événements Hebdomadaires Narratifs (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== Migration : Événements Hebdomadaires Narratifs (Chroniques du Sengoku) ===\n";

try {
    if (in_array('--sqlite', $argv ?? [])) {
        $sqliteFile = __DIR__ . '/../openshogun.sqlite';
        $db = new PDO('sqlite:' . $sqliteFile);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Database::setConnection($db);
    } else {
        $db = Database::getConnection();
    }
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $db->exec("
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
        $sqlFile = __DIR__ . '/migrations/004_create_weekly_events.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $db->exec($sql);
        }
    }
    echo "[OK] Tables des événements hebdomadaires créées avec succès.\n";

    require_once __DIR__ . '/../core/WeeklyEventEngine.php';
    require_once __DIR__ . '/../core/WeeklyEventsData.php';

    // Nettoyer les 4 anciens slugs de test s'ils existent afin de laisser place aux 52 semaines normalisées
    $db->exec("DELETE FROM weekly_events WHERE slug IN ('okehazama_1560', 'sakai_tea_1569', 'rakuichi_rakuza_1577', 'kawanakajima_1561')");

    $stmtCount = $db->query("SELECT COUNT(*) FROM weekly_events");
    $eventCount = (int)$stmtCount->fetchColumn();

    $engine = new WeeklyEventEngine($db);

    if ($eventCount < 52) {
        echo "[INFO] Amorçage des 52 Chroniques Historiques Féodales du Sengoku (1 par semaine de l'année)...\n";
        $engine->seedInitialEvents();
        $newCount = (int)$db->query("SELECT COUNT(*) FROM weekly_events")->fetchColumn();
        $taskCount = (int)$db->query("SELECT COUNT(*) FROM weekly_event_tasks")->fetchColumn();
        echo "[SUCCÈS] $newCount Chroniques Féodales et $taskCount Actes narratifs enregistrés avec honneur !\n";
    } else {
        echo "[INFO] $eventCount Chroniques déjà présentes en base de données.\n";
    }

} catch (Exception $e) {
    echo "[ERREUR] Exception migration : " . $e->getMessage() . "\n";
}
