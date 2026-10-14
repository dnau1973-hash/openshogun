<?php
/**
 * Game Studio - Game Elevate Designer : Académie des Savoirs & Forge
 * Fichier : views/gamestudio/elevate_research.php
 * Interface de configuration et d'équilibrage des technologies et castes féodales
 */
declare(strict_types=1);

// Traitement du formulaire POST de mise à jour des coefficients si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_global_coefficients') {
        $elevateService->updateGlobalCoefficients(
            (float)($_POST['research_time_coeff'] ?? 1.0),
            (float)($_POST['research_time_growth'] ?? 1.35),
            (float)($_POST['research_cost_coeff'] ?? 1.0),
            (float)($_POST['research_cost_growth'] ?? 1.65)
        );
        $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Coefficients exponentiels de recherche enregistrés avec succès !'];
        header('Location: ?page=gamestudio&tab=elevate_research');
        exit;
    } elseif ($_POST['action'] === 'save_custom_research') {
        $elevateService->saveResearch($_POST);
        $_SESSION['elevate_flash'] = ['type' => 'success', 'msg' => 'Nouvelle technologie enregistrée dans l\'Académie des Savoirs !'];
        header('Location: ?page=gamestudio&tab=elevate_research');
        exit;
    }
}
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-warning"><i class="fa-solid fa-compass-drafting"></i></span>
            <span>Académie des Savoirs &amp; Forge Militaire</span>
            <span class="badge bg-purple-lt text-purple font-monospace">Modèle Exponentiel v2.0</span>
        </h2>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNewResearch">
            <i class="fa-solid fa-plus me-1"></i> Ajouter une Recherche
        </button>
    </div>
</div>

<?php if (!empty($flashMessage)): ?>
    <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?> alert-dismissible fade show shadow-sm mb-3" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> <?= htmlspecialchars($flashMessage['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 1. CARTES D'ÉCHELONS SOCIAUX (CASTES SHINŌKŌSHŌ)                     -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-dark text-white py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-torii-gate text-warning fs-3"></i>
                <div>
                    <h3 class="card-title text-white mb-0">Échelle des 5 Castes Féodales (Shinōkōshō)</h3>
                    <div class="small text-white-50">Les villageois évoluent automatiquement de caste lorsque leurs besoins en vivres sont comblés</div>
                </div>
            </div>
            <span class="badge bg-warning text-dark font-monospace fw-bold">Seuils de Déblocage des Savoirs</span>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row row-cards g-3">
            <?php foreach ($castes as $tier => $c): ?>
                <div class="col-12 col-md-6 col-lg">
                    <div class="card h-100 shadow-none border border-<?= $c['badge'] ?>-subtle bg-light-subtle">
                        <div class="card-status-top bg-<?= $c['badge'] ?>"></div>
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-<?= $c['badge'] ?> text-white fw-bold">Palier <?= $tier ?></span>
                                <span class="fs-3"><?= htmlspecialchars($c['kanji']) ?></span>
                            </div>
                            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-1">
                                <i class="fa-solid <?= $c['icon'] ?> text-<?= $c['badge'] ?>"></i>
                                <?= htmlspecialchars($c['short_name']) ?>
                            </h4>
                            <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.78rem;">
                                <?= htmlspecialchars($c['desc']) ?>
                            </p>
                            <div class="p-2 rounded bg-white border">
                                <div class="text-uppercase text-secondary fw-bold" style="font-size: 0.65rem;">Vivres &amp; Biens Requis :</div>
                                <div class="small mt-1 font-monospace">
                                    <?php foreach ($c['needs'] as $resKey => $rate): ?>
                                        <div class="d-flex justify-content-between text-dark">
                                            <span><?= ucfirst($resKey) ?> :</span>
                                            <span class="fw-bold text-primary"><?= $rate ?> / hab / h</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 2. PARAMÈTRES GLOBAUX DU FORMULE EXPONENTIELLE                      -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="row row-cards mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 bg-light-subtle">
                <h3 class="card-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo"></i>
                    <span>Formule Exponentielle (Calquée sur Bâtiments)</span>
                </h3>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="?page=gamestudio&tab=elevate_research">
                    <input type="hidden" name="action" value="save_global_coefficients">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Multiplicateur de Temps Général (research_time_coeff)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-stopwatch text-info"></i></span>
                            <input type="number" step="0.05" min="0.05" max="5.0" class="form-control font-monospace" name="research_time_coeff" value="<?= $timeCoeff ?>" required>
                        </div>
                        <div class="form-text small">Échelle globale de durée des recherches (1.0 = normal, 0.5 = 2x plus rapide).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Facteur d'Accélération Temporelle par Niveau (research_time_growth)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-arrow-trend-up text-warning"></i></span>
                            <input type="number" step="0.01" min="1.05" max="2.5" class="form-control font-monospace" name="research_time_growth" value="<?= $timeGrowth ?>" required>
                        </div>
                        <div class="form-text small">Croissance exponentielle de durée par niveau (calqué sur building_time_growth 1.28 - 1.35).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Multiplicateur de Coût Général (research_cost_coeff)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-coins text-warning"></i></span>
                            <input type="number" step="0.05" min="0.05" max="5.0" class="form-control font-monospace" name="research_cost_coeff" value="<?= $costCoeff ?>" required>
                        </div>
                        <div class="form-text small">Modificateur de coût brut pour tous les paliers de technologie.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Facteur de Croissance du Coût par Niveau (research_cost_growth)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-chart-line text-success"></i></span>
                            <input type="number" step="0.01" min="0.5" max="2.5" class="form-control font-monospace" name="research_cost_growth" value="<?= $costGrowth ?>" required>
                        </div>
                        <div class="form-text small">Facteur exponentiel de coût par niveau supérieur (calqué sur le modèle Travian 1.60 - 1.75).</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer les Coefficients
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Simulateur interactif de courbe -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                <h3 class="card-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-column text-primary"></i>
                    <span>Simulateur de Courbe de Progression (Niveaux 1 à 15)</span>
                </h3>
                <span class="badge bg-indigo-lt font-monospace">Vitesse Jeu : x<?= $gameSpeed ?></span>
            </div>
            <div class="card-body p-3">
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-sm-6">
                        <label class="form-label small mb-1">Technologie de référence pour la simulation :</label>
                        <select class="form-select form-select-sm" id="simTechSelect" onchange="updateSimulation()">
                            <?php foreach ($researches as $code => $r): ?>
                                <option value="<?= htmlspecialchars($code) ?>"
                                        data-metal="<?= (int)$r['base_cost']['metal'] ?>"
                                        data-crystal="<?= (int)$r['base_cost']['crystal'] ?>"
                                        data-deut="<?= (int)$r['base_cost']['deuterium'] ?>"
                                        data-time="<?= (int)$r['base_time'] ?>"
                                        data-mult="<?= (float)($r['cost_multiplier'] ?? 1.6) ?>">
                                    <?= $r['icon'] ?> <?= htmlspecialchars($r['name']) ?> (Palier <?= (int)$r['civ_tier'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small mb-1">Niveau Académie :</label>
                        <input type="number" id="simLabLevel" class="form-control form-control-sm" value="5" min="1" max="20" onchange="updateSimulation()">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small mb-1">Érudits (Caste 4) :</label>
                        <input type="number" id="simEruditeRatio" class="form-control form-control-sm" value="8" min="0" max="25" title="% d'Érudits dans la population" onchange="updateSimulation()">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 320px;">
                    <table class="table table-sm table-vcenter table-hover border">
                        <thead class="table-light sticky-top">
                            <tr class="small text-uppercase text-secondary">
                                <th class="text-center">Niv</th>
                                <th>Coût Bois</th>
                                <th>Coût Pierre</th>
                                <th>Coût Riz</th>
                                <th>Total Ressources</th>
                                <th class="text-end">Durée Estimée</th>
                            </tr>
                        </thead>
                        <tbody id="simTableBody" class="font-monospace small">
                            <!-- Rempli par JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 3. TABLEAU DES TECHNOLOGIES ET PALIERS DE CIVILISATION               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title d-flex align-items-center gap-2">
            <i class="fa-solid fa-scroll text-danger"></i>
            <span>Catalogue des Traités de l'Académie des Savoirs &amp; Forge</span>
        </h3>
        <span class="badge bg-secondary-lt"><?= count($researches) ?> Technologies Enregistrées</span>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr class="text-uppercase text-secondary small">
                    <th>Technologie</th>
                    <th>Discipline</th>
                    <th class="text-center">Palier Civilisation</th>
                    <th>Coûts de Base</th>
                    <th class="text-center">Temps de Base</th>
                    <th class="text-center">Coeff Exponentiel</th>
                    <th class="text-end">Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($researches as $code => $r): 
                    $civTier = (int)($r['civ_tier'] ?? 1);
                    $casteInfo = $castes[$civTier] ?? $castes[1];
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-2"><?= $r['icon'] ?></span>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['name']) ?></div>
                                    <div class="text-secondary small font-monospace"><?= htmlspecialchars($code) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-lt text-secondary">
                                <?= htmlspecialchars($r['category_label'] ?? 'Général') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $casteInfo['badge'] ?> text-white fw-bold px-2 py-1" title="<?= htmlspecialchars($casteInfo['name']) ?>">
                                <i class="fa-solid <?= $casteInfo['icon'] ?> me-1"></i>
                                Palier <?= $civTier ?> : <?= htmlspecialchars($casteInfo['short_name']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap small font-monospace">
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-tree text-success me-1"></i><?= number_format($r['base_cost']['metal']) ?>
                                </span>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($r['base_cost']['crystal']) ?>
                                </span>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($r['base_cost']['deuterium']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-center font-monospace small">
                            <i class="fa-solid fa-stopwatch text-info me-1"></i><?= $r['base_time'] ?>s (<?= intdiv($r['base_time'], 60) ?>m <?= $r['base_time'] % 60 ?>s)
                        </td>
                        <td class="text-center font-monospace fw-bold text-indigo">
                            &times;<?= number_format((float)($r['cost_multiplier'] ?? 1.6), 2) ?>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-success-lt text-success fw-bold">Actif en Jeu</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL D'AJOUT D'UNE NOUVELLE RECHERCHE -->
<div class="modal fade" id="modalNewResearch" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="?page=gamestudio&tab=elevate_research">
                <input type="hidden" name="action" value="save_custom_research">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="fa-solid fa-scroll text-primary"></i>
                        <span>Ajouter une Technologie dans l'Académie des Savoirs</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Code Technique (ex: kayaku_explosive_tech) *</label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="code_en_snake_case" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nom Féodal de la Recherche *</label>
                            <input type="text" name="name" class="form-control" placeholder="ex: Alchimie du Salpêtre Volcanique" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Description &amp; Traité Militaire</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Expliquez les vertus tactiques et stratégiques de ce savoir..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Coût Bois de Base</label>
                            <input type="number" name="metal_cost" class="form-control font-monospace" value="2500" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Coût Pierre de Base</label>
                            <input type="number" name="crystal_cost" class="form-control font-monospace" value="1500" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Coût Riz de Base</label>
                            <input type="number" name="deuterium_cost" class="form-control font-monospace" value="1000" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Temps de Base (Secondes)</label>
                            <input type="number" name="base_time" class="form-control font-monospace" value="300" min="10" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Palier de Civilisation Requis (Shinōkōshō)</label>
                            <select name="civ_tier" class="form-select">
                                <option value="1">Palier 1 : Nōmin (Paysans &amp; Ouvriers)</option>
                                <option value="2">Palier 2 : Kōgiyō (Artisans &amp; Bâtisseurs)</option>
                                <option value="3">Palier 3 : Shōnin (Marchands &amp; Caravaniers)</option>
                                <option value="4" selected>Palier 4 : Gakusha &amp; Bushi (Érudits &amp; Samouraïs)</option>
                                <option value="5">Palier 5 : Daimyō &amp; Kuge (Haute Noblesse)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-plus me-1"></i> Créer la Technologie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Simulateur dynamique de progression de recherche
 */
function updateSimulation() {
    const select = document.getElementById('simTechSelect');
    const opt = select.options[select.selectedIndex];
    const baseMetal = parseFloat(opt.getAttribute('data-metal') || 1000);
    const baseCrystal = parseFloat(opt.getAttribute('data-crystal') || 800);
    const baseDeut = parseFloat(opt.getAttribute('data-deut') || 400);
    const baseTime = parseFloat(opt.getAttribute('data-time') || 300);
    const costMult = parseFloat(opt.getAttribute('data-mult') || 1.6);

    const labLvl = Math.max(1, parseInt(document.getElementById('simLabLevel').value) || 1);
    const eruditeRatio = (parseFloat(document.getElementById('simEruditeRatio').value) || 0) / 100.0;

    const timeCoeff = <?= $timeCoeff ?>;
    const timeGrowth = <?= $timeGrowth ?>;
    const costCoeff = <?= $costCoeff ?>;
    const costGrowth = <?= $costGrowth ?>;
    const speed = <?= $gameSpeed ?>;

    const tbody = document.getElementById('simTableBody');
    tbody.innerHTML = '';

    for (let curLvl = 0; curLvl < 15; curLvl++) {
        const targetLvl = curLvl + 1;
        const mult = Math.pow(costMult * (costGrowth / 1.65), curLvl);
        const metal = Math.round(baseMetal * mult * costCoeff);
        const crystal = Math.round(baseCrystal * mult * costCoeff);
        const deut = Math.round(baseDeut * mult * costCoeff);
        const total = metal + crystal + deut;

        const labFactor = Math.pow(0.95, Math.max(0, labLvl - 1));
        const lvlFactor = Math.pow(timeGrowth, curLvl) * Math.pow(targetLvl, 0.85);
        const eruditeBonus = Math.min(0.25, Math.max(0.0, eruditeRatio * 2.5));
        const eruditeFactor = 1.0 / (1.0 + eruditeBonus);

        const durationSec = Math.max(15, Math.round((baseTime * lvlFactor * labFactor * eruditeFactor * timeCoeff) / speed));
        
        const hours = Math.floor(durationSec / 3600);
        const minutes = Math.floor((durationSec % 3600) / 60);
        const seconds = durationSec % 60;
        let timeStr = '';
        if (hours > 0) timeStr += hours + 'h ';
        if (minutes > 0 || hours > 0) timeStr += minutes + 'm ';
        timeStr += seconds + 's';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center fw-bold text-dark">${targetLvl}</td>
            <td class="text-success">${metal.toLocaleString()}</td>
            <td class="text-secondary">${crystal.toLocaleString()}</td>
            <td class="text-warning">${deut.toLocaleString()}</td>
            <td class="fw-bold text-dark">${total.toLocaleString()}</td>
            <td class="text-end text-info font-monospace">${timeStr}</td>
        `;
        tbody.appendChild(tr);
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', updateSimulation);
</script>
