<?php
/**
 * Game Studio - Game Elevate Designer : Écosystèmes des Oasis
 * Fichier : views/gamestudio/elevate_oases.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/GameConfig.php';
require_once __DIR__ . '/../../core/OasisEngine.php';

$db = Database::getConnection();
$gameSettings = GameConfig::load();
$oasisEngine = new OasisEngine();
$oasisStats = $oasisEngine->getOasisStatistics();

$allOases = $db->query("
    SELECT o.*, 
           p.name as owner_planet_name, 
           u.username as owner_username 
    FROM oases o 
    LEFT JOIN planets p ON o.owner_planet_id = p.id 
    LEFT JOIN users u ON p.user_id = u.id 
    ORDER BY o.owner_planet_id DESC, o.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($allOases as &$oRow) {
    $oRow['garrison'] = $oasisEngine->getOasisGarrison((int)$oRow['id']);
}
unset($oRow);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-teal"><i class="fa-solid fa-seedling"></i></span>
            <span>Écosystème des Oasis Naturelles &amp; Faune Sauvage</span>
            <span class="badge bg-teal-lt text-teal font-monospace">Havres &amp; Densité Procedurale</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
require __DIR__ . '/../studio/game-elevate-designer/oasis-ecosystem.php';
?>

<script>
async function executeDevRepopulateOases() {
    const density = parseFloat(document.getElementById('dev_repop_density')?.value) || 2.0;
    const radius = parseInt(document.getElementById('dev_repop_radius')?.value, 10) || 28;
    const clearUnoccupied = document.getElementById('dev_repop_clear_unoccupied')?.checked ? '1' : '0';

    showConfirmModal(
        "Génération des Oasis Naturelles",
        `Confirmez-vous la génération et l'équilibrage des oasis avec une densité cible de <strong>${density}%</strong> sur un rayon de <strong>${radius}</strong> tuiles ?`,
        async () => {
            const formData = new FormData();
            formData.append('action', 'repopulate_oases');
            formData.append('density_percent', density);
            formData.append('radius', radius);
            formData.append('clear_unoccupied', clearUnoccupied);

            try {
                const res = await fetch('/api/dev_team.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showDevAlert('success', `<i class="fa-solid fa-leaf text-success me-1"></i><strong>Succès :</strong> ${data.message}`);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible de générer les oasis."}`);
                }
            } catch (e) {
                showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${e.message}`);
            }
        },
        'btn-success',
        '<i class="fa-solid fa-leaf"></i>'
    );
}

// Pagination des oasis
let devOasesPerPage = 15;
let currentDevOasesPage = 1;

function initDevOasesPagination() {
    const rows = document.querySelectorAll('.dev-oasis-table-row');
    if (rows.length === 0) return;
    const select = document.getElementById('devOasesPerPageSelect');
    if (select) devOasesPerPage = parseInt(select.value, 10) || 15;
    renderDevOasesPage(currentDevOasesPage);
}

function changeDevOasesPerPage(newVal) {
    devOasesPerPage = parseInt(newVal, 10) || 15;
    currentDevOasesPage = 1;
    renderDevOasesPage(currentDevOasesPage);
}

function renderDevOasesPage(page) {
    const rows = Array.from(document.querySelectorAll('.dev-oasis-table-row'));
    const totalOases = rows.length;
    if (totalOases === 0) return;
    const totalPages = Math.ceil(totalOases / devOasesPerPage) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentDevOasesPage = page;

    const startIdx = (page - 1) * devOasesPerPage;
    const endIdx = Math.min(startIdx + devOasesPerPage, totalOases);

    rows.forEach((row, i) => {
        row.style.display = (i >= startIdx && i < endIdx) ? '' : 'none';
    });

    const startEl = document.getElementById('devOasesPaginationStart');
    const endEl = document.getElementById('devOasesPaginationEnd');
    const totalEl = document.getElementById('devOasesPaginationTotal');
    if (startEl) startEl.textContent = (startIdx + 1);
    if (endEl) endEl.textContent = endIdx;
    if (totalEl) totalEl.textContent = totalOases;

    const listEl = document.getElementById('devOasesPaginationList');
    if (!listEl) return;

    let html = '';
    html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="renderDevOasesPage(${page - 1}); return false;">&laquo;</a>
    </li>`;

    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, page + 2);
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="renderDevOasesPage(1); return false;">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
    }

    for (let p = startPage; p <= endPage; p++) {
        html += `<li class="page-item ${p === page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="renderDevOasesPage(${p}); return false;">${p}</a>
        </li>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" onclick="renderDevOasesPage(${totalPages}); return false;">${totalPages}</a></li>`;
    }

    html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="renderDevOasesPage(${page + 1}); return false;">&raquo;</a>
    </li>`;

    listEl.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    initDevOasesPagination();
});
</script>
