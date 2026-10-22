<?php
/**
 * Banc de Tests Unitaires & Intégration : Système d'Ambiance Visuelle & Saisons / Jour-Nuit
 * OpenShogun - Page Ressources (Terroirs) & Cité Castrale
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/EnvironmentThemeEngine.php';
require_once __DIR__ . '/../core/GameConfig.php';

// Configuration SQLite en mémoire autonome
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setConnection($sqlite);

$sqlite->exec("CREATE TABLE IF NOT EXISTS game_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

function assertTest(bool $condition, string $message): void {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo " TEST : MOTEUR D'AMBIANCE SAISONNIÈRE & CYCLE JOUR/NUIT DYNAMIQUE\n";
echo "========================================================================\n\n";

// Réinitialisation des surcharges pour les tests propres
GameConfig::set('environment_season_override', 'auto');
GameConfig::set('environment_time_override', 'auto');
GameConfig::set('environment_theme_override', 'auto');

echo "[TEST 1] Vérification de la présence physique des images d'arrière-plan...\n";
$expectedTerroir = [
    'sun'         => 'public/assets/terroirs/terroir_sun.jpg',
    'night'       => 'public/assets/terroirs/terroir_night.jpg',
    'storm'       => 'public/assets/terroirs/terroir_storm.jpg',
    'storm_day'   => 'public/assets/terroirs/terroir_storm_day.jpg',
    'storm_night' => 'public/assets/terroirs/terroir_storm_night.jpg',
    'snow_day'    => 'public/assets/terroirs/terroir_snow_day.jpg',
    'snow_night'  => 'public/assets/terroirs/terroir_snow_night.jpg',
];
foreach ($expectedTerroir as $th => $relPath) {
    $fullPath = __DIR__ . '/../' . $relPath;
    assertTest(file_exists($fullPath), "Image Terroir présente : {$relPath} (" . round(filesize($fullPath) / 1024 / 1024, 2) . " Mo)");
}

$expectedCity = [
    'sun'         => 'public/assets/cities/city_sun.jpg',
    'night'       => 'public/assets/cities/city_night.jpg',
    'storm'       => 'public/assets/cities/city_storm.jpg',
    'storm_day'   => 'public/assets/cities/city_storm_day.jpg',
    'storm_night' => 'public/assets/cities/city_storm_night.jpg',
    'snow_day'    => 'public/assets/cities/city_snow_day.jpg',
    'snow_night'  => 'public/assets/cities/city_snow_night.jpg',
];
foreach ($expectedCity as $th => $relPath) {
    $fullPath = __DIR__ . '/../' . $relPath;
    assertTest(file_exists($fullPath), "Image Cité présente : {$relPath} (" . round(filesize($fullPath) / 1024 / 1024, 2) . " Mo)");
}

echo "\n[TEST 2] Détection automatique des 4 saisons selon le mois de l'année...\n";
// Mars (3) = Printemps
$tsSpring = strtotime('2026-04-15 12:00:00');
assertTest(EnvironmentThemeEngine::getCurrentSeason($tsSpring) === 'spring', "Avril (Mois 4) -> Printemps (spring)");

// Juillet (7) = Été
$tsSummer = strtotime('2026-07-20 12:00:00');
assertTest(EnvironmentThemeEngine::getCurrentSeason($tsSummer) === 'summer', "Juillet (Mois 7) -> Été (summer)");

// Octobre (10) = Automne
$tsAutumn = strtotime('2026-10-21 12:00:00');
assertTest(EnvironmentThemeEngine::getCurrentSeason($tsAutumn) === 'autumn', "Octobre (Mois 10) -> Automne (autumn)");

// Janvier (1) = Hiver
$tsWinter = strtotime('2027-01-15 12:00:00');
assertTest(EnvironmentThemeEngine::getCurrentSeason($tsWinter) === 'winter', "Janvier (Mois 1) -> Hiver (winter)");

echo "\n[TEST 3] Détection Jour / Nuit selon l'heure de la journée...\n";
// 14h00 = Jour
$tsNoon = strtotime('2026-05-10 14:00:00');
assertTest(EnvironmentThemeEngine::isDayTime($tsNoon) === true, "14h00 est considéré comme Jour");

// 22h30 = Nuit
$tsNight = strtotime('2026-05-10 22:30:00');
assertTest(EnvironmentThemeEngine::isDayTime($tsNight) === false, "22h30 est considéré comme Nuit");

// 04h00 = Nuit
$tsEarlyNight = strtotime('2026-05-10 04:00:00');
assertTest(EnvironmentThemeEngine::isDayTime($tsEarlyNight) === false, "04h00 est considéré comme Nuit");

// 08h00 = Jour
$tsMorning = strtotime('2026-05-10 08:00:00');
assertTest(EnvironmentThemeEngine::isDayTime($tsMorning) === true, "08h00 est considéré comme Jour");

echo "\n[TEST 4] Règles d'affichage demandées par l'utilisateur (Printemps & Été = Soleil, Automne = Orage, Hiver = Neige)...\n";
// 1. Printemps en journée -> Soleil
$themeSpringDay = EnvironmentThemeEngine::getThemeKey(strtotime('2026-04-10 14:00:00'));
assertTest($themeSpringDay === 'sun', "Printemps en journée -> 'sun' (Plein Soleil)");

// 2. Été en journée -> Soleil
$themeSummerDay = EnvironmentThemeEngine::getThemeKey(strtotime('2026-07-15 14:00:00'));
assertTest($themeSummerDay === 'sun', "Été en journée -> 'sun' (Plein Soleil)");

// 3. Automne en journée -> Orage Diurne (storm_day)
$themeAutumnDay = EnvironmentThemeEngine::getThemeKey(strtotime('2026-10-21 14:00:00'));
assertTest($themeAutumnDay === 'storm_day', "Automne en journée -> 'storm_day' (Orage Diurne)");

// 4. Hiver en journée -> Neige
$themeWinterDay = EnvironmentThemeEngine::getThemeKey(strtotime('2027-01-10 14:00:00'));
assertTest($themeWinterDay === 'snow_day', "Hiver en journée -> 'snow_day' (Neige de Jour)");

// 5. Nuit en Hiver -> Nuit sous la neige
$themeWinterNight = EnvironmentThemeEngine::getThemeKey(strtotime('2027-01-10 23:00:00'));
assertTest($themeWinterNight === 'snow_night', "Hiver de nuit -> 'snow_night' (Nuit sous la neige)");

// 6. Nuit en Automne -> Orage Nocturne (storm_night)
$themeAutumnNight = EnvironmentThemeEngine::getThemeKey(strtotime('2026-10-21 23:00:00'));
assertTest($themeAutumnNight === 'storm_night', "Automne de nuit -> 'storm_night' (Orage Nocturne féodal)");

// 7. Nuit en Printemps & Été -> Nuit au clair de lune
$themeSpringNight = EnvironmentThemeEngine::getThemeKey(strtotime('2026-04-10 23:00:00'));
assertTest($themeSpringNight === 'night', "Printemps de nuit -> 'night' (Nuit féodale)");

echo "\n[TEST 5] Résolution dynamique des URLs de background Terroir & Cité...\n";
// Automne en journée : terroir_storm_day.jpg & city_storm_day.jpg
$terroirStormUrl = EnvironmentThemeEngine::getTerroirBackground(strtotime('2026-10-21 14:00:00'));
$cityStormUrl = EnvironmentThemeEngine::getCityBackground(strtotime('2026-10-21 14:00:00'));
assertTest(str_contains($terroirStormUrl, '/public/assets/terroirs/terroir_storm'), "Terroir Automne Jour pointe sur terroir_storm_day.jpg ou terroir_storm.jpg");
assertTest(str_contains($cityStormUrl, '/public/assets/cities/city_storm'), "Cité Automne Jour pointe sur city_storm_day.jpg ou city_storm.jpg");

// Automne de nuit : terroir_storm_night.jpg & city_storm_night.jpg
$terroirStormNightUrl = EnvironmentThemeEngine::getTerroirBackground(strtotime('2026-10-21 23:00:00'));
$cityStormNightUrl = EnvironmentThemeEngine::getCityBackground(strtotime('2026-10-21 23:00:00'));
assertTest(str_contains($terroirStormNightUrl, '/public/assets/terroirs/terroir_storm_night.jpg'), "Terroir Automne Nuit pointe sur terroir_storm_night.jpg");
assertTest(str_contains($cityStormNightUrl, '/public/assets/cities/city_storm_night.jpg'), "Cité Automne Nuit pointe sur city_storm_night.jpg");

// Hiver de nuit : terroir_snow_night.jpg & city_snow_night.jpg
$terroirSnowNightUrl = EnvironmentThemeEngine::getTerroirBackground(strtotime('2027-01-15 22:00:00'));
$citySnowNightUrl = EnvironmentThemeEngine::getCityBackground(strtotime('2027-01-15 22:00:00'));
assertTest(str_contains($terroirSnowNightUrl, '/public/assets/terroirs/terroir_snow_night.jpg'), "Terroir Hiver Nuit pointe sur terroir_snow_night.jpg");
assertTest(str_contains($citySnowNightUrl, '/public/assets/cities/city_snow_night.jpg'), "Cité Hiver Nuit pointe sur city_snow_night.jpg");

echo "\n[TEST 6] Surcharges administratives via GameConfig...\n";
// Forcer la saison à Hiver en plein mois d'Août
GameConfig::set('environment_season_override', 'winter');
GameConfig::set('environment_time_override', 'day');
$overriddenTheme = EnvironmentThemeEngine::getThemeKey(strtotime('2026-08-15 14:00:00'));
assertTest($overriddenTheme === 'snow_day', "Surcharge saison 'winter' + heure 'day' active -> 'snow_day'");

// Forcer directement le thème complet à 'storm_day' (ou 'storm')
GameConfig::set('environment_theme_override', 'storm_day');
$forcedStorm = EnvironmentThemeEngine::getThemeKey();
assertTest($forcedStorm === 'storm_day', "Surcharge directe thème 'storm_day' prioritaire");

GameConfig::set('environment_theme_override', 'storm_night');
$forcedStormNight = EnvironmentThemeEngine::getThemeKey();
assertTest($forcedStormNight === 'storm_night', "Surcharge directe thème 'storm_night' prioritaire");

// Réinitialisation en mode automatique
GameConfig::set('environment_season_override', 'auto');
GameConfig::set('environment_time_override', 'auto');
GameConfig::set('environment_theme_override', 'auto');

echo "\n[TEST 7] Intégration dans les vues PHP (views/resources.php & views/city.php)...\n";
$resourcesContent = file_get_contents(__DIR__ . '/../views/resources.php');
assertTest(str_contains($resourcesContent, 'EnvironmentThemeEngine::getThemeData'), "views/resources.php appelle EnvironmentThemeEngine");
assertTest(str_contains($resourcesContent, '$terroirBgUrl'), 'views/resources.php applique $terroirBgUrl dynamique');

$cityContent = file_get_contents(__DIR__ . '/../views/city.php');
assertTest(str_contains($cityContent, 'EnvironmentThemeEngine::getThemeData'), 'views/city.php appelle EnvironmentThemeEngine');
assertTest(str_contains($cityContent, '$cityBgUrl'), 'views/city.php applique $cityBgUrl dynamique');

echo "\n========================================================================\n";
echo " TOUS LES TESTS D'AMBIANCE SAISONNIÈRE ET JOUR/NUIT SONT 100% OK !\n";
echo "========================================================================\n";
