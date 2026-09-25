<?php
declare(strict_types=1);

/**
 * Service Métier & ViewModel pour la vue Recherche & Académie des Savoirs
 * Gère la séparation logique/vue, le typage strict, les filtres et la pagination
 */
require_once __DIR__ . '/ResearchEngine.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/ResearchDTO.php';

class ResearchService {
    private ResearchEngine $researchEngine;
    private PlanetEngine $planetEngine;

    public function __construct(
        ?ResearchEngine $researchEngine = null,
        ?PlanetEngine $planetEngine = null
    ) {
        $this->researchEngine = $researchEngine ?? new ResearchEngine();
        $this->planetEngine = $planetEngine ?? new PlanetEngine();
    }

    /**
     * Métadonnées et enrichissement des technologies féodales
     */
    public static function getTechMetadata(string $code): array {
        return match ($code) {
            'armor_tech' => [
                'category' => 'military',
                'category_label' => 'Armement & Forge',
                'icon' => '🛡️',
            ],
            'laser_tech' => [
                'category' => 'military',
                'category_label' => 'Tir & Balistique',
                'icon' => '🏹',
            ],
            'shield_tech' => [
                'category' => 'defense',
                'category_label' => 'Défense Castrale',
                'icon' => '⛩️',
            ],
            'propulsion_tech' => [
                'category' => 'logistics',
                'category_label' => 'Logistique des Armées',
                'icon' => '🐎',
            ],
            'warp_tech' => [
                'category' => 'logistics',
                'category_label' => 'Grandes Voies (Gokaido)',
                'icon' => '🗾',
            ],
            'espionage_tech' => [
                'category' => 'strategy',
                'category_label' => 'Renseignement Shinobi',
                'icon' => '🥷',
            ],
            'energy_tech' => [
                'category' => 'doctrine',
                'category_label' => 'Doctrine Spirituelle',
                'icon' => '🕯️',
            ],
            default => [
                'category' => 'general',
                'category_label' => 'Savoir Général',
                'icon' => '📜',
            ],
        };
    }

    /**
     * Prépare le modèle de vue complet avec filtres, recherche, tri et pagination
     *
     * @param int $userId Identifiant du joueur
     * @param int $planetId Identifiant du fief actif
     * @param array $currentResources Ressources actuelles du fief (metal, crystal, deuterium)
     * @param array $queryParams Paramètres GET ($_GET)
     * @return array
     */
    public function getViewModel(
        int $userId,
        int $planetId,
        array $currentResources,
        array $queryParams
    ): array {
        // 1. Niveau du bâtiment Académie des Savoirs
        $buildings = $this->planetEngine->getBuildings($planetId);
        $labLvl = (int)($buildings['research_lab'] ?? 0);

        // 2. Recherche actuellement en cours dans la file
        $activeQueue = $this->researchEngine->getActiveQueue($userId);
        $activeQueueViewModel = null;
        if ($activeQueue) {
            $now = time();
            $finishesAt = (int)$activeQueue['finishes_at'];
            $startedAt = (int)($activeQueue['started_at'] ?? ($finishesAt - 60));
            $totalDuration = max(1, $finishesAt - $startedAt);
            $remaining = max(0, $finishesAt - $now);
            $progressPct = min(100, max(0, (int)round((($totalDuration - $remaining) / $totalDuration) * 100)));

            $activeQueueViewModel = [
                'research_code' => (string)($activeQueue['research_code'] ?? ''),
                'research_name' => (string)$activeQueue['research_name'],
                'target_level' => (int)$activeQueue['target_level'],
                'finishes_at' => $finishesAt,
                'remaining_seconds' => $remaining,
                'remaining_formatted' => sprintf(
                    '%02d:%02d:%02d',
                    intdiv($remaining, 3600),
                    intdiv($remaining % 3600, 60),
                    $remaining % 60
                ),
                'progress_pct' => $progressPct,
            ];
        }

        // 3. Récupération des technologies brutes et mapping en DTO
        $rawResearches = $this->researchEngine->getResearches($userId, $planetId);

        $curMetal = (int)($currentResources['metal'] ?? 0);
        $curCrystal = (int)($currentResources['crystal'] ?? 0);
        $curDeut = (int)($currentResources['deuterium'] ?? 0);

        $dtos = [];
        $categories = [
            'all' => ['label' => 'Toutes', 'count' => 0, 'icon' => '📜'],
        ];

        foreach ($rawResearches as $r) {
            $meta = self::getTechMetadata((string)$r['code']);
            $costMetal = (int)$r['cost_metal'];
            $costCrystal = (int)$r['cost_crystal'];
            $costDeut = (int)$r['cost_deuterium'];

            $canAfford = ($curMetal >= $costMetal && $curCrystal >= $costCrystal && $curDeut >= $costDeut);
            $missingMetal = max(0, $costMetal - $curMetal);
            $missingCrystal = max(0, $costCrystal - $curCrystal);
            $missingDeut = max(0, $costDeut - $curDeut);

            $dto = new ResearchDTO(
                code: (string)$r['code'],
                name: (string)$r['name'],
                description: (string)$r['description'],
                currentLevel: (int)$r['current_level'],
                nextLevel: (int)$r['next_level'],
                costMetal: $costMetal,
                costCrystal: $costCrystal,
                costDeuterium: $costDeut,
                duration: (int)$r['duration'],
                canResearch: (bool)($r['can_research'] ?? ($labLvl >= 1)),
                category: $meta['category'],
                categoryLabel: $meta['category_label'],
                icon: $meta['icon'],
                canAfford: $canAfford,
                missingMetal: $missingMetal,
                missingCrystal: $missingCrystal,
                missingDeuterium: $missingDeut
            );

            $dtos[] = $dto;

            // Incrémentation des catégories
            $categories['all']['count']++;
            $catKey = $meta['category'];
            if (!isset($categories[$catKey])) {
                $categories[$catKey] = [
                    'label' => $meta['category_label'],
                    'count' => 0,
                    'icon' => $meta['icon'],
                ];
            }
            $categories[$catKey]['count']++;
        }

        // 4. Traitement des filtres (Recherche textuelle, Catégorie, Finançables)
        $search = trim((string)($queryParams['search'] ?? ''));
        $selectedCat = (string)($queryParams['category'] ?? 'all');
        $affordableOnly = !empty($queryParams['affordable_only']);
        $sortBy = (string)($queryParams['sort'] ?? 'default');
        $viewMode = (string)($queryParams['view'] ?? 'grid');
        if (!in_array($viewMode, ['grid', 'table'], true)) {
            $viewMode = 'grid';
        }

        $filtered = array_filter($dtos, function (ResearchDTO $item) use ($search, $selectedCat, $affordableOnly): bool {
            if ($selectedCat !== 'all' && $item->category !== $selectedCat) {
                return false;
            }
            if ($affordableOnly && !$item->canAfford) {
                return false;
            }
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $haystackName = mb_strtolower($item->name);
                $haystackDesc = mb_strtolower($item->description);
                if (!str_contains($haystackName, $needle) && !str_contains($haystackDesc, $needle)) {
                    return false;
                }
            }
            return true;
        });

        // 5. Tri des technologies
        usort($filtered, function (ResearchDTO $a, ResearchDTO $b) use ($sortBy): int {
            return match ($sortBy) {
                'name_asc' => strcasecmp($a->name, $b->name),
                'name_desc' => strcasecmp($b->name, $a->name),
                'level_desc' => $b->currentLevel <=> $a->currentLevel,
                'level_asc' => $a->currentLevel <=> $b->currentLevel,
                'cost_asc' => $a->getTotalCost() <=> $b->getTotalCost(),
                'cost_desc' => $b->getTotalCost() <=> $a->getTotalCost(),
                'duration_asc' => $a->duration <=> $b->duration,
                'duration_desc' => $b->duration <=> $a->duration,
                default => 0,
            };
        });

        // 6. Pagination stricte
        $totalFiltered = count($filtered);
        $perPage = (int)($queryParams['per_page'] ?? 6);
        if (!in_array($perPage, [6, 12, 24, 100], true)) {
            $perPage = 6;
        }

        $totalPages = max(1, (int)ceil($totalFiltered / $perPage));
        $page = min(max(1, (int)($queryParams['p'] ?? 1)), $totalPages);
        $offset = ($page - 1) * $perPage;

        $pagedItems = array_slice($filtered, $offset, $perPage);

        return [
            'lab_level' => $labLvl,
            'active_queue' => $activeQueueViewModel,
            'items' => $pagedItems,
            'total_items' => $totalFiltered,
            'all_items_count' => count($dtos),
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $selectedCat,
                'affordable_only' => $affordableOnly,
                'sort' => $sortBy,
                'view' => $viewMode,
                'per_page' => $perPage,
                'page' => $page,
            ],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $totalFiltered,
                'offset' => $offset,
                'start_item' => ($totalFiltered > 0) ? ($offset + 1) : 0,
                'end_item' => min($offset + count($pagedItems), $totalFiltered),
                'has_prev' => ($page > 1),
                'has_next' => ($page < $totalPages),
            ],
        ];
    }
}
