<?php
/**
 * Moteur de Configuration et Variables Dynamiques d'OpenGalaxy
 */
require_once __DIR__ . '/Database.php';

class GameConfig {
    private static ?array $cache = null;

    /**
     * Charge toutes les variables depuis la table game_settings
     */
    public static function load(): array {
        if (self::$cache === null) {
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM game_settings");
                $rows = $stmt->fetchAll();
                
                self::$cache = [];
                foreach ($rows as $row) {
                    $val = $row['setting_value'];
                    switch ($row['setting_type']) {
                        case 'int':
                            $val = (int)$val;
                            break;
                        case 'float':
                            $val = (float)$val;
                            break;
                        case 'boolean':
                            $val = ($val === '1' || $val === 'true' || $val === 1 || $val === true);
                            break;
                        default:
                            $val = (string)$val;
                            break;
                    }
                    self::$cache[$row['setting_key']] = $val;
                }
            } catch (Exception $e) {
                // Fallbacks si la table n'est pas accessible
                self::$cache = [
                    'game_speed' => 5,
                    'resource_speed' => 5,
                    'fleet_speed' => 5,
                    'bots_enabled' => true,
                    'bot_colonize_enabled' => true,
                    'bot_aggressiveness' => 'moderate',
                    'bot_max_planets' => 3,
                    'bot_spawn_enabled' => false,
                    'bot_spawn_interval_min' => 15,
                    'bot_spawn_max_villages' => 50,
                    'oasis_density_percent' => 2.0,
                    'oasis_respawn_on_capture' => true,
                    'famine_enabled' => false,
                    'famine_rate' => 3.0,
                    'famine_flour_consumption' => 1.0,
                    'hero_cage_drop_rate' => 25,
                    'hero_xp_rate_percent' => 100,
                    'building_time_coeff' => 1.0,
                    'building_time_growth' => 1.28,
                    'building_cost_coeff' => 1.0,
                    'building_cost_growth' => 1.0,
                    'research_time_coeff' => 1.0,
                    'research_time_growth' => 1.35,
                    'research_cost_coeff' => 1.0,
                    'research_cost_growth' => 1.65,
                    'world_radius' => 35,
                    'starter_wood' => 1000,
                    'starter_stone' => 1000,
                    'starter_rice' => 1000,
                    'starter_fish' => 250,
                    'starter_gold_coins' => 100,
                    'beginner_protection_days' => 7,
                    'preserve_human_users_on_reset' => true,
                    // Déroulement du Jeu & Chronologie des Ères (Progression & Endgame)
                    'server_start_timestamp' => time() - (86400 * 3),
                    'server_current_era' => 1,
                    'era_auto_progression_enabled' => true,
                    'era_castles_auto_day' => 7,
                    'era_castles_unlocked' => false,
                    'era_dojo_auto_day' => 21,
                    'era_dojo_unlocked' => false,
                    'era_broadcast_announcements' => true,
                    'era_victory_alliance_id' => 0
                ];
            }
        }
        return self::$cache;
    }

    /**
     * Récupère l'horodatage initial d'ouverture du monde
     */
    public static function getServerStartTimestamp(): int {
        $ts = (int)self::get('server_start_timestamp', 0);
        if ($ts <= 0) {
            $ts = time();
        }
        return $ts;
    }

    /**
     * Calcule l'âge actuel du serveur en jours décimaux
     */
    public static function getServerAgeDays(): float {
        $start = self::getServerStartTimestamp();
        $diff = max(0, time() - $start);
        return round($diff / 86400, 1);
    }

    /**
     * Récupère le numéro de l'Ère mondiale active (1 à 5)
     */
    public static function getServerCurrentEra(): int {
        return max(1, min(5, (int)self::get('server_current_era', 1)));
    }

    /**
     * Vérifie si la Guerre des 12 Châteaux a été débloquée
     */
    public static function isCastlesWarUnlocked(): bool {
        return (bool)self::get('era_castles_unlocked', false);
    }

    /**
     * Vérifie si le chantier du Dōjō Impérial [0:0] a été débloqué
     */
    public static function isImperialDojoUnlocked(): bool {
        return (bool)self::get('era_dojo_unlocked', false);
    }

    /**
     * Récupère le rayon du monde actuel
     */
    public static function getWorldRadius(): int {
        return (int)self::get('world_radius', 35);
    }

    /**
     * Calcule le nombre total de tuiles pour un rayon donné (ou le rayon actif)
     * Formule cartésienne : (2 * R + 1)^2
     */
    public static function getTotalTilesCount(?int $radius = null): int {
        $r = $radius ?? self::getWorldRadius();
        $dim = ($r * 2) + 1;
        return $dim * $dim;
    }

    /**
     * Récupère la valeur d'une variable de jeu
     */
    public static function get(string $key, mixed $default = null): mixed {
        $settings = self::load();
        return $settings[$key] ?? $default;
    }

    /**
     * Met à jour une variable de jeu
     */
    public static function set(string $key, mixed $value): bool {
        try {
            $db = Database::getConnection();
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $strVal = is_bool($value) ? ($value ? '1' : '0') : (string)$value;

            if ($driver === 'sqlite') {
                $stmt = $db->prepare("INSERT OR REPLACE INTO game_settings (setting_key, setting_value) VALUES (?, ?)");
                $res = $stmt->execute([$key, $strVal]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO game_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $res = $stmt->execute([$key, $strVal]);
            }
            
            // Invalider le cache
            self::$cache = null;
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les variables avec métadonnées
     */
    public static function getAll(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM game_settings ORDER BY setting_key ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Réinitialise le cache en mémoire
     */
    public static function clearCache(): void {
        self::$cache = null;
    }
}

