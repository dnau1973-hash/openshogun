<?php
/**
 * Game Studio - Game Elevate Designer : Arpentage & Provinces
 * Fichier : views/gamestudio/elevate_survey.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/GameConfig.php';
require_once __DIR__ . '/../../core/WorldGenerator.php';

$db = Database::getConnection();
$mapRadius = 12;
$totalTilesCount = ($mapRadius * 2 + 1) * ($mapRadius * 2 + 1);

$stmtCastles = $db->query("SELECT coord_x, coord_y FROM authentic_castles WHERE is_spawned = 1");
$spawnedCastleCoords = [];
while ($row = $stmtCastles->fetch(PDO::FETCH_ASSOC)) {
    $spawnedCastleCoords[$row['coord_x'] . ':' . $row['coord_y']] = true;
}

$allPlanetsRows = $db->query("SELECT coord_x, coord_y, user_id FROM planets")->fetchAll(PDO::FETCH_ASSOC);
$villageCoords = [];
$freeLandCoords = [];
foreach ($allPlanetsRows as $p) {
    $k = ((int)$p['coord_x']) . ':' . ((int)$p['coord_y']);
    if (isset($spawnedCastleCoords[$k])) {
        continue;
    }
    if (!empty($p['user_id'])) {
        $villageCoords[$k] = true;
    } else {
        $freeLandCoords[$k] = true;
    }
}

$stmtOasesCoords = $db->query("SELECT coord_x, coord_y FROM oases");
$oasisCoords = [];
while ($row = $stmtOasesCoords->fetch(PDO::FETCH_ASSOC)) {
    $oasisCoords[((int)$row['coord_x']) . ':' . ((int)$row['coord_y'])] = true;
}

$mapTileStats = [
    'radius'      => $mapRadius,
    'total_tiles' => $totalTilesCount,
    'villages'    => count($villageCoords),
    'free_lands'  => count($freeLandCoords),
    'castles'     => count($spawnedCastleCoords),
    'oases'       => count($oasisCoords),
    'plains'      => 0,
    'forest'      => 0,
    'mountain'    => 0,
    'hills'       => 0,
    'lake'        => 0,
];

for ($y = -$mapRadius; $y <= $mapRadius; $y++) {
    for ($x = -$mapRadius; $x <= $mapRadius; $x++) {
        $k = $x . ':' . $y;
        if (isset($villageCoords[$k]) || isset($freeLandCoords[$k]) || isset($spawnedCastleCoords[$k]) || isset($oasisCoords[$k])) {
            continue;
        }
        $seed = abs((int)(($x * 73856093) ^ ($y * 19349663))) % 1000;
        if ($seed < 600) {
            $mapTileStats['plains']++;
        } elseif ($seed < 750) {
            $mapTileStats['forest']++;
        } elseif ($seed < 870) {
            $mapTileStats['mountain']++;
        } elseif ($seed < 950) {
            $mapTileStats['hills']++;
        } else {
            $mapTileStats['lake']++;
        }
    }
}

$mapTileCategories = [
    'villages' => [
        'name'      => 'Fiefs Occupés',
        'sub'       => 'Daimyōs & PNJ',
        'count'     => $mapTileStats['villages'],
        'icon'      => '<i class="fa-solid fa-chess-rook me-1"></i>',
        'badge_bg'  => 'bg-purple-lt text-purple',
        'bar_color' => 'bg-purple',
        'img'       => '/public/assets/map/tile_village.jpg?v=2',
        'desc'      => 'Capitales et fiefs colonisés par les joueurs et clans IA.'
    ],
    'free_lands' => [
        'name'      => 'Terres Libres',
        'sub'       => 'Emplacements arpentés',
        'count'     => $mapTileStats['free_lands'],
        'icon'      => '<i class="fa-solid fa-flag me-1"></i>',
        'badge_bg'  => 'bg-secondary-lt text-secondary',
        'bar_color' => 'bg-secondary',
        'img'       => '/public/assets/map/tile_plains.jpg?v=2',
        'desc'      => 'Emplacements arpentés disponibles pour fondation de colonie.'
    ],
    'castles' => [
        'name'      => 'Donjons Sacrés',
        'sub'       => '現存十二天守',
        'count'     => $mapTileStats['castles'],
        'icon'      => '<i class="fa-solid fa-crown me-1"></i>',
        'badge_bg'  => 'bg-warning-lt text-warning',
        'bar_color' => 'bg-warning',
        'img'       => '/public/assets/map/tile_authentic_castle.jpg?v=2',
        'desc'      => 'Les 12 forteresses impériales historiques du Japon féodal.'
    ],
    'oases' => [
        'name'      => 'Oasis Naturelles',
        'sub'       => 'Faune & Bonus',
        'count'     => $mapTileStats['oases'],
        'icon'      => '<i class="fa-solid fa-leaf me-1"></i>',
        'badge_bg'  => 'bg-teal-lt text-teal',
        'bar_color' => 'bg-teal',
        'img'       => '/public/assets/map/tile_lake.jpg?v=2',
        'desc'      => 'Havres de faune sauvage procurant des bonus de production.'
    ],
    'plains' => [
        'name'      => 'Plaines Fertiles',
        'sub'       => 'Prairies & Terres',
        'count'     => $mapTileStats['plains'],
        'icon'      => '<i class="fa-solid fa-wheat-awn me-1"></i>',
        'badge_bg'  => 'bg-lime-lt text-lime',
        'bar_color' => 'bg-lime',
        'img'       => '/public/assets/map/tile_plains.jpg?v=2',
        'desc'      => 'Terres arables verdoyantes et plaines propices à l\'agriculture.'
    ],
    'forest' => [
        'name'      => 'Forêts de Cèdres',
        'sub'       => 'Bois d\'Œuvre',
        'count'     => $mapTileStats['forest'],
        'icon'      => '<i class="fa-solid fa-tree me-1"></i>',
        'badge_bg'  => 'bg-green-lt text-green',
        'bar_color' => 'bg-green',
        'img'       => '/public/assets/map/tile_forest.jpg?v=2',
        'desc'      => 'Massifs forestiers anciens riches en cèdres pour la charpente.'
    ],
    'mountain' => [
        'name'      => 'Monts & Granit',
        'sub'       => 'Gisements de Pierre',
        'count'     => $mapTileStats['mountain'],
        'icon'      => '<i class="fa-solid fa-mountain me-1"></i>',
        'badge_bg'  => 'bg-secondary-lt text-secondary',
        'bar_color' => 'bg-secondary',
        'img'       => '/public/assets/map/tile_mountain.jpg?v=2',
        'desc'      => 'Coteaux rocheux renfermant les filons de granit et de minerai.'
    ],
    'hills' => [
        'name'      => 'Collines Sanctuaire',
        'sub'       => 'Sérénité & Esprits',
        'count'     => $mapTileStats['hills'],
        'icon'      => '<i class="fa-solid fa-torii-gate me-1"></i>',
        'badge_bg'  => 'bg-orange-lt text-orange',
        'bar_color' => 'bg-orange',
        'img'       => '/public/assets/map/tile_hills.jpg?v=2',
        'desc'      => 'Hautes collines baignées de ferveur spirituelle pour les sanctuaires.'
    ],
    'lake' => [
        'name'      => 'Lacs & Rivières',
        'sub'       => 'Eaux Sacrées',
        'count'     => $mapTileStats['lake'],
        'icon'      => '<i class="fa-solid fa-water me-1"></i>',
        'badge_bg'  => 'bg-azure-lt text-azure',
        'bar_color' => 'bg-azure',
        'img'       => '/public/assets/map/tile_lake.jpg?v=2',
        'desc'      => 'Plans d\'eau navigables et canaux essentiels à l\'irrigation.'
    ]
];
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-primary"><i class="fa-solid fa-map-location-dot"></i></span>
            <span>Arpentage &amp; Provinces Féodales</span>
            <span class="badge bg-primary-lt text-primary font-monospace">Cartographie &amp; Génération</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
require __DIR__ . '/../studio/game-elevate-designer/shogunat-survey.php';
?>

<script>
async function generateDevWorld(event) {
    if (event) event.preventDefault();
    const count = document.getElementById('dev_planet_count_input')?.value || 12;
    const radius = document.getElementById('dev_radius_range')?.value || 12;
    const clearUninhabited = document.getElementById('dev_clear_uninhabited')?.checked ? '1' : '0';

    showConfirmModal(
        "Expansion Provinciale Féodale",
        `Voulez-vous générer <strong>${count}</strong> nouveaux fiefs et terres procédurales dans un rayon de <strong>&plusmn;${radius}</strong> provinces ?`,
        async () => {
            const formData = new FormData();
            formData.append('action', 'generate_world');
            formData.append('planet_count', count);
            formData.append('radius', radius);
            formData.append('clear_uninhabited', clearUninhabited);

            try {
                const res = await fetch('/api/dev_team.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showDevAlert('success', `<i class="fa-solid fa-map-location-dot text-primary me-1"></i><strong>Expansion Réussie :</strong> <strong>${data.generated_count}</strong> terres ont été déployées !`);
                    setTimeout(() => location.reload(), 2500);
                } else {
                    showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible d'arpenter les terres."}`);
                }
            } catch (e) {
                showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${e.message}`);
            }
        },
        'btn-success',
        '<i class="fa-solid fa-map-location-dot"></i>'
    );
}
</script>

