<?php
declare(strict_types=1);

/**
 * Service Métier du Concepteur Game Elevate Designer (Académie des Savoirs & Forge)
 * Gère la configuration des technologies féodales, les coefficients de recherche,
 * et la simulation des courbes de progression.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/ResearchEngine.php';
require_once __DIR__ . '/SocialCasteEngine.php';

class ResearchElevateService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Prépare toutes les données pour la vue du concepteur
     */
    public function getViewData(): array {
        // Paramètres dynamiques de calibrage des recherches
        $timeCoeff   = (float)GameConfig::get('research_time_coeff', 1.0);
        $timeGrowth  = (float)GameConfig::get('research_time_growth', 1.35);
        $costCoeff   = (float)GameConfig::get('research_cost_coeff', 1.0);
        $costGrowth  = (float)GameConfig::get('research_cost_growth', 1.65);
        $gameSpeed   = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);

        // Liste de toutes les technologies existantes et injectées
        $researches = ResearchEngine::getAllDefinitions($this->db);

        // Castes de référence
        $castes = SocialCasteEngine::CASTES;

        // Message flash éventuel
        $flashMessage = $_SESSION['elevate_flash'] ?? null;
        unset($_SESSION['elevate_flash']);

        return [
            'timeCoeff'    => $timeCoeff,
            'timeGrowth'   => $timeGrowth,
            'costCoeff'    => $costCoeff,
            'costGrowth'   => $costGrowth,
            'gameSpeed'    => $gameSpeed,
            'researches'   => $researches,
            'castes'       => $castes,
            'flashMessage' => $flashMessage
        ];
    }

    /**
     * Met à jour les coefficients globaux du Game Elevate Designer
     */
    public function updateGlobalCoefficients(float $timeCoeff, float $timeGrowth, float $costCoeff, float $costGrowth): bool {
        GameConfig::set('research_time_coeff', max(0.05, min(10.0, $timeCoeff)));
        GameConfig::set('research_time_growth', max(1.05, min(2.5, $timeGrowth)));
        GameConfig::set('research_cost_coeff', max(0.05, min(10.0, $costCoeff)));
        GameConfig::set('research_cost_growth', max(0.5, min(2.5, $costGrowth)));
        return true;
    }

    /**
     * Enregistre ou édite une recherche féodale (BD + métadonnées de caste et d'icône)
     */
    public function saveResearch(array $data): bool {
        $code = trim((string)($data['code'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $desc = trim((string)($data['description'] ?? ''));
        $metal = max(0, (int)($data['metal_cost'] ?? 1000));
        $crystal = max(0, (int)($data['crystal_cost'] ?? 500));
        $deut = max(0, (int)($data['deuterium_cost'] ?? 200));
        $time = max(10, (int)($data['base_time'] ?? 180));
        $civTier = max(1, min(5, (int)($data['civ_tier'] ?? 1)));
        $costMultiplier = max(1.1, min(3.0, (float)($data['cost_multiplier'] ?? 1.65)));
        $category = trim((string)($data['category'] ?? 'military'));
        $categoryLabel = trim((string)($data['category_label'] ?? 'Traité Supérieur'));
        $icon = trim((string)($data['icon'] ?? '📜'));

        if (empty($code) || empty($name)) {
            throw new Exception("Le code technique et le nom du savoir sont obligatoires.");
        }

        // 1. Sauvegarder dans la table `researches`
        try {
            $stmt = $this->db->prepare("
                INSERT INTO researches (code, name, metal_cost, crystal_cost, deuterium_cost, base_time, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    metal_cost = VALUES(metal_cost),
                    crystal_cost = VALUES(crystal_cost),
                    deuterium_cost = VALUES(deuterium_cost),
                    base_time = VALUES(base_time),
                    description = VALUES(description)
            ");
            $stmt->execute([$code, $name, $metal, $crystal, $deut, $time, $desc]);
        } catch (Throwable $e) {
            // Ignorer l'erreur SQL si serveur indisponible
        }

        // 2. Sauvegarder les métadonnées (Palier de Caste, Icône, Catégorie, Multiplicateur)
        $customMeta = json_decode((string)GameConfig::get('custom_research_metadata', '{}'), true) ?: [];
        $customMeta[$code] = [
            'civ_tier' => $civTier,
            'cost_multiplier' => $costMultiplier,
            'category' => $category,
            'category_label' => $categoryLabel ?: 'Traité Supérieur',
            'icon' => $icon ?: '📜'
        ];
        GameConfig::set('custom_research_metadata', json_encode($customMeta, JSON_UNESCAPED_UNICODE));

        return true;
    }
}

