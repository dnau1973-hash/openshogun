<?php
/**
 * Moteur d'Ambiance Visuelle & Météo Saisonnière Dynamique (OpenShogun)
 * Gère l'affichage contextuel des arrière-plans (Terroir & Cité Castrale)
 * selon la saison de l'année et l'heure du jour/nuit.
 *
 * Règles Métier :
 * - Printemps & Été (Jour)  => Plein Soleil (sun)
 * - Printemps & Été (Nuit)  => Nuit féodale au clair de lune (night)
 * - Automne (Jour)           => Orage féodal diurne (storm_day / storm)
 * - Automne (Nuit)           => Orage féodal nocturne (storm_night)
 * - Hiver (Jour)             => Neige immaculée (snow_day)
 * - Hiver (Nuit)             => Nuit sous la neige (snow_night)
 */
require_once __DIR__ . '/GameConfig.php';

// Alignement sur le fuseau horaire du serveur ou Europe/Paris par défaut
if (date_default_timezone_get() === 'UTC' || empty(ini_get('date.timezone'))) {
    @date_default_timezone_set('Europe/Paris');
}

class EnvironmentThemeEngine {

    public const THEME_SUN         = 'sun';
    public const THEME_NIGHT       = 'night';
    public const THEME_STORM_DAY   = 'storm_day';
    public const THEME_STORM_NIGHT = 'storm_night';
    public const THEME_STORM       = 'storm_day'; // Alias de rétrocompatibilité
    public const THEME_SNOW_DAY    = 'snow_day';
    public const THEME_SNOW_NIGHT  = 'snow_night';

    public const SEASONS = [
        'spring' => [
            'name'        => 'Printemps',
            'jp_name'     => '春 (Haru)',
            'icon'        => '🌸',
            'day_theme'   => self::THEME_SUN,
            'night_theme' => self::THEME_NIGHT,
            'desc'        => 'Renouveau de la terre, douceur des vents et cerisiers en fleurs.'
        ],
        'summer' => [
            'name'        => 'Été',
            'jp_name'     => '夏 (Natsu)',
            'icon'        => '☀️',
            'day_theme'   => self::THEME_SUN,
            'night_theme' => self::THEME_NIGHT,
            'desc'        => 'Chaleur estivale, zénith éclatant et abondance dans les rizières.'
        ],
        'autumn' => [
            'name'        => 'Automne',
            'jp_name'     => '秋 (Aki)',
            'icon'        => '🍂',
            'day_theme'   => self::THEME_STORM_DAY,
            'night_theme' => self::THEME_STORM_NIGHT,
            'desc'        => 'Pluies battantes, ciels tourmentés et orages impériaux de Raiden.'
        ],
        'winter' => [
            'name'        => 'Hiver',
            'jp_name'     => '冬 (Fuyu)',
            'icon'        => '❄️',
            'day_theme'   => self::THEME_SNOW_DAY,
            'night_theme' => self::THEME_SNOW_NIGHT,
            'desc'        => 'Manteau blanc immaculé, eaux gelées et sérénité sous les flocons.'
        ],
    ];

    /**
     * Détermine la saison courante ('spring', 'summer', 'autumn', 'winter')
     * Découpage astronomique réaliste par défaut (équinoxes et solstices)
     * Printemps : ~21 Mars au 20 Juin
     * Été       : ~21 Juin au 21 Septembre
     * Automne   : ~22 Septembre au 20 Décembre
     * Hiver     : ~21 Décembre au 20 Mars
     */
    public static function getCurrentSeason(?int $timestamp = null): string {
        $override = GameConfig::get('environment_season_override', 'auto');
        if ($override && $override !== 'auto' && isset(self::SEASONS[$override])) {
            return $override;
        }

        $ts = $timestamp ?? time();
        $mode = GameConfig::get('environment_season_mode', 'astronomical');

        if ($mode === 'meteorological') {
            $month = (int)date('n', $ts);
            return match ($month) {
                3, 4, 5   => 'spring',
                6, 7, 8   => 'summer',
                9, 10, 11 => 'autumn',
                default   => 'winter',
            };
        }

        // Calcul astronomique fin basé sur le jour et mois (MMDD)
        $month = (int)date('n', $ts);
        $day = (int)date('j', $ts);
        $md = ($month * 100) + $day;

        if ($md >= 321 && $md < 621) {
            return 'spring';
        }
        if ($md >= 621 && $md < 922) {
            return 'summer';
        }
        if ($md >= 922 && $md < 1221) {
            return 'autumn';
        }
        return 'winter';
    }

    /**
     * Détermine si le moment est en période diurne ou nocturne
     * Période jour par défaut : 07h00 à 19h59
     */
    public static function isDayTime(?int $timestamp = null): bool {
        $override = GameConfig::get('environment_time_override', 'auto');
        if ($override === 'day') return true;
        if ($override === 'night') return false;

        $ts = $timestamp ?? time();
        $hour = (int)date('G', $ts); // 0 à 23

        $dayStart = (int)GameConfig::get('environment_day_start_hour', 7);
        $dayEnd   = (int)GameConfig::get('environment_day_end_hour', 20);

        return ($hour >= $dayStart && $hour < $dayEnd);
    }

    /**
     * Retourne la clé de thème actif : 'sun', 'night', 'storm_day', 'storm_night', 'snow_day', 'snow_night'
     */
    public static function getThemeKey(?int $timestamp = null): string {
        // Surcharge directe du thème complet (priorité absolue si configurée)
        $themeOverride = GameConfig::get('environment_theme_override', 'auto');
        if ($themeOverride && $themeOverride !== 'auto') {
            if ($themeOverride === 'storm') {
                $themeOverride = self::THEME_STORM_DAY;
            }
            $valid = [
                self::THEME_SUN,
                self::THEME_NIGHT,
                self::THEME_STORM_DAY,
                self::THEME_STORM_NIGHT,
                self::THEME_SNOW_DAY,
                self::THEME_SNOW_NIGHT,
                'storm'
            ];
            if (in_array($themeOverride, $valid, true)) {
                return ($themeOverride === 'storm') ? self::THEME_STORM_DAY : $themeOverride;
            }
        }

        $season = self::getCurrentSeason($timestamp);
        $isDay = self::isDayTime($timestamp);

        if ($isDay) {
            return match ($season) {
                'spring', 'summer' => self::THEME_SUN,
                'autumn'           => self::THEME_STORM_DAY,
                'winter'           => self::THEME_SNOW_DAY,
                default            => self::THEME_SUN,
            };
        }

        // Période nocturne
        return match ($season) {
            'autumn'           => self::THEME_STORM_NIGHT,
            'winter'           => self::THEME_SNOW_NIGHT,
            default            => self::THEME_NIGHT,
        };
    }

    /**
     * Retourne l'URL de fond d'écran pour la page Terroir / Ressources (Dorf 1)
     */
    public static function getTerroirBackground(?int $timestamp = null): string {
        $theme = self::getThemeKey($timestamp);
        $baseDir = __DIR__ . '/../public/assets/terroirs/';
        $fallback = '/public/assets/shogun_rural_terroir_9plots.jpg';

        $themeVariants = match ($theme) {
            self::THEME_STORM_DAY, 'storm' => ['storm_day', 'storm'],
            self::THEME_STORM_NIGHT        => ['storm_night'],
            self::THEME_SNOW_DAY           => ['snow_day', 'snow'],
            self::THEME_SNOW_NIGHT         => ['snow_night'],
            self::THEME_SUN                => ['sun', 'day'],
            self::THEME_NIGHT              => ['night'],
            default                        => [$theme],
        };

        $extensions = ['jpg', 'jpeg', 'webp', 'png'];
        foreach ($themeVariants as $variant) {
            foreach ($extensions as $ext) {
                $filename = "terroir_{$variant}.{$ext}";
                if (file_exists($baseDir . $filename)) {
                    $mtime = filemtime($baseDir . $filename);
                    return "/public/assets/terroirs/{$filename}?v={$mtime}";
                }
            }
        }

        return $fallback;
    }

    /**
     * Retourne l'URL de fond d'écran pour la page Cité Castrale (Dorf 2)
     */
    public static function getCityBackground(?int $timestamp = null): string {
        $theme = self::getThemeKey($timestamp);
        $baseDir = __DIR__ . '/../public/assets/cities/';
        $fallback = '/public/assets/shogun_castle_city_bg.jpg';

        $themeVariants = match ($theme) {
            self::THEME_STORM_DAY, 'storm' => ['storm_day', 'storm'],
            self::THEME_STORM_NIGHT        => ['storm_night'],
            self::THEME_SNOW_DAY           => ['snow_day', 'snow'],
            self::THEME_SNOW_NIGHT         => ['snow_night'],
            self::THEME_SUN                => ['sun', 'day'],
            self::THEME_NIGHT              => ['night'],
            default                        => [$theme],
        };

        $extensions = ['jpg', 'jpeg', 'webp', 'png'];
        foreach ($themeVariants as $variant) {
            foreach ($extensions as $ext) {
                $filename = "city_{$variant}.{$ext}";
                if (file_exists($baseDir . $filename)) {
                    $mtime = filemtime($baseDir . $filename);
                    return "/public/assets/cities/{$filename}?v={$mtime}";
                }
            }
        }

        return $fallback;
    }

    /**
     * Retourne l'ensemble des métadonnées d'ambiance pour l'UI, badges ou télémétrie
     */
    public static function getThemeData(?int $timestamp = null): array {
        $ts = $timestamp ?? time();
        $seasonKey = self::getCurrentSeason($ts);
        $seasonInfo = self::SEASONS[$seasonKey] ?? self::SEASONS['spring'];
        $isDay = self::isDayTime($ts);
        $themeKey = self::getThemeKey($ts);

        $themeLabels = [
            self::THEME_SUN         => ['label' => 'Zénith Doré & Plein Soleil',     'icon' => '☀️',   'badge' => 'bg-warning text-dark'],
            self::THEME_NIGHT       => ['label' => 'Clair de Lune & Lanternes',        'icon' => '🌙',   'badge' => 'bg-dark text-white'],
            self::THEME_STORM_DAY   => ['label' => 'Orage Diurne & Tempête Féodale',  'icon' => '⛈️',   'badge' => 'bg-secondary text-white'],
            self::THEME_STORM_NIGHT => ['label' => 'Orage Nocturne & Éclairs Féodaux', 'icon' => '⛈️🌙', 'badge' => 'bg-dark text-warning'],
            self::THEME_SNOW_DAY    => ['label' => 'Hiver Poudré de Neige',           'icon' => '❄️',   'badge' => 'bg-info text-dark'],
            self::THEME_SNOW_NIGHT  => ['label' => 'Nuit Féodale sous la Neige',      'icon' => '❄️🌙', 'badge' => 'bg-primary text-white'],
        ];

        // Rétrocompatibilité pour la clé 'storm'
        $themeLabels['storm'] = $themeLabels[self::THEME_STORM_DAY];

        $currentThemeMeta = $themeLabels[$themeKey] ?? $themeLabels[self::THEME_SUN];

        $seasonOverride = GameConfig::get('environment_season_override', 'auto');
        $timeOverride   = GameConfig::get('environment_time_override', 'auto');
        $themeOverride  = GameConfig::get('environment_theme_override', 'auto');
        $isOverridden   = ($seasonOverride !== 'auto' || $timeOverride !== 'auto' || $themeOverride !== 'auto');

        return [
            'season'            => $seasonKey,
            'season_name'       => $seasonInfo['name'],
            'season_jp'         => $seasonInfo['jp_name'],
            'season_icon'       => $seasonInfo['icon'],
            'season_desc'       => $seasonInfo['desc'],
            'is_day'            => $isDay,
            'time_label'        => $isDay ? 'Jour' : 'Nuit',
            'time_icon'         => $isDay ? '☀️' : '🌙',
            'theme_key'         => $themeKey,
            'theme_label'       => $currentThemeMeta['label'],
            'theme_icon'        => $currentThemeMeta['icon'],
            'theme_badge_class' => $currentThemeMeta['badge'],
            'terroir_url'       => self::getTerroirBackground($ts),
            'city_url'          => self::getCityBackground($ts),
            'is_overridden'     => $isOverridden,
            'overrides'         => [
                'season' => $seasonOverride,
                'time'   => $timeOverride,
                'theme'  => $themeOverride,
            ]
        ];
    }
}
