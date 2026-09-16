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
                    'oasis_density_percent' => 2.0,
                    'oasis_respawn_on_capture' => true
                ];
            }
        }
        return self::$cache;
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
            $stmt = $db->prepare("
                INSERT INTO game_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            $strVal = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
            $res = $stmt->execute([$key, $strVal]);
            
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

