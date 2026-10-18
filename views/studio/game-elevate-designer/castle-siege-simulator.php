<?php
/**
 * Module Studio Dev : Simulateur d'Attaque & Siège des 12 Châteaux Authentiques du Japon (現存十二天守)
 * Métier : Game Elevate Designer
 * Emplacement : views/studio/game-elevate-designer/castle-siege-simulator.php
 */

require_once __DIR__ . '/../../../core/CastleSiegeSimulatorEngine.php';

$siegeEngine = new CastleSiegeSimulatorEngine();
$allCastles = $siegeEngine->getCastlesList();
$historicalPresets = $siegeEngine->getHistoricalPresets();
$weatherList = CastleSiegeSimulatorEngine::WEATHER_MODIFIERS;
$troopStats = CastleSiegeSimulatorEngine::TROOP_STATS;

// Château sélectionné par défaut
$defaultCastleCode = 'himeji';
$defaultCastle = $allCastles[$defaultCastleCode] ?? reset($allCastles);
$defaultGarrison = $siegeEngine->getAncestralGarrison($defaultCastleCode);
?>

<div class="castle-siege-simulator-wrapper">

    <!-- ── EN-TÊTE DU MODULE : SIMULATEUR DE SIÈGE DES 12 CHÂTEAUX ── -->
    <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #f59e0b !important;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-warning-lt text-dark font-game px-2 py-1">
                            <i class="fa-solid fa-chess-rook me-1"></i>Game Elevate Designer
                        </span>
                        <span class="badge bg-purple-lt text-purple">
                            <i class="fa-solid fa-torii-gate me-1"></i>現存十二天守 &bull; 12 Donjons Authentiques
                        </span>
                        <span class="badge bg-danger-lt text-danger fw-bold">
                            ⚔️ Siège Séquencé en 3 Échelons
                        </span>
                    </div>
                    <h2 class="h3 mb-0 text-dark fw-bold d-flex align-items-center gap-2">
                        <span>Simulateur d'Attaque &amp; Siège des 12 Châteaux Authentiques</span>
                    </h2>
                    <div class="text-secondary small mt-1">
                        Atelier d'équilibrage martial et d'arpentage historique : modélisez la pénétration des douves, l'escalade des murailles cyclopéennes et l'assaut final du donjon Tenshu selon la topographie réelle de chaque forteresse du Japon.
                    </div>
                </div>

                <!-- Préréglages Historiques 1-Clic -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-secondary small fw-bold d-none d-md-inline">Scénarios Historiques :</span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-warning dropdown-toggle fw-bold text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-scroll text-warning me-1"></i>Charger un Scénario
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <?php foreach ($historicalPresets as $pId => $preset): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="javascript:void(0)" onclick="loadHistoricalPreset('<?= $pId ?>')">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($preset['title']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($preset['subtitle']) ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-sm btn-light text-muted" onclick="resetCastleSimulator()" title="Réinitialiser le simulateur">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         CARROUSEL INTERACTIF DES 12 CHÂTEAUX AUTHENTIQUES (SÉLECTEUR)
         ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="card-title text-warning m-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Sélectionnez le Sanctuaire Castral à Assiéger (12 Trésors Nationaux)</span>
            </h4>
            <span class="badge bg-warning text-dark font-monospace" id="current-castle-badge">Himeji (姫路城)</span>
        </div>
        <div class="card-body p-2 bg-light">
            <div class="d-flex gap-2 overflow-auto py-1" id="castles-selector-scroll" style="scrollbar-width: thin;">
                <?php foreach ($allCastles as $code => $c): 
                    $topo = $c['topography'];
                    $isSelected = ($code === $defaultCastleCode);
                ?>
                    <div class="castle-thumb-card flex-shrink-0 p-2 rounded text-center border cursor-pointer <?= $isSelected ? 'border-warning shadow-sm' : 'border-secondary-subtle' ?>" 
                         data-castle-code="<?= htmlspecialchars($code) ?>"
                         onclick="selectCastle('<?= htmlspecialchars($code) ?>')"
                         style="width: 140px; cursor: pointer; transition: all 0.2s ease; background: <?= $isSelected ? '#fffbeb' : '#ffffff' ?>;">
                        <div class="position-relative mb-1 rounded overflow-hidden" style="height: 70px; background: #1c1917;">
                            <img src="<?= htmlspecialchars($c['image_src']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <span class="position-absolute top-0 end-0 badge bg-dark bg-opacity-75 text-warning font-monospace m-1" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($c['kanji']) ?>
                            </span>
                        </div>
                        <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($c['name']) ?>" style="font-size: 0.78rem;">
                            <?= htmlspecialchars(str_replace('Château de ', '', str_replace('Château d\'', '', $c['name']))) ?>
                        </div>
                        <div class="text-secondary text-truncate" style="font-size: 0.68rem;">
                            <?= htmlspecialchars($topo['type_name']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── BANNIÈRE D'INFORMATION ARCHITECTURALE & TOPOGRAPHIQUE ── -->
    <div class="card mb-3 border-0 shadow-sm" id="castle-detail-banner" style="background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); color: #f8fafc;">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded overflow-hidden border border-warning" style="width: 85px; height: 85px; background: #000;">
                        <img id="banner-castle-img" src="<?= htmlspecialchars($defaultCastle['image_src']) ?>" alt="Château" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h3 class="h4 mb-0 fw-bold text-warning font-game" id="banner-castle-title"><?= htmlspecialchars($defaultCastle['name']) ?></h3>
                        <span class="badge bg-warning text-dark font-monospace" id="banner-castle-kanji"><?= htmlspecialchars($defaultCastle['kanji']) ?></span>
                        <span class="badge bg-secondary-lt text-light" id="banner-castle-province"><?= htmlspecialchars($defaultCastle['province']) ?></span>
                        <span class="badge bg-info-lt text-info" id="banner-castle-type"><?= htmlspecialchars($defaultCastle['topography']['type_name']) ?></span>
                    </div>
                    <div class="small text-light opacity-75 mb-2" id="banner-castle-desc">
                        <?= htmlspecialchars($defaultCastle['short_desc']) ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap small">
                        <div>
                            <i class="fa-solid fa-mountain text-warning me-1"></i>
                            <span>Altitude : <strong id="banner-altitude"><?= $defaultCastle['topography']['altitude_m'] ?> m</strong></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-door-closed text-danger me-1"></i>
                            <span>Portes concentriques : <strong id="banner-gates"><?= $defaultCastle['topography']['gate_layers'] ?> battants</strong></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-shield-cat text-primary me-1"></i>
                            <span>Remparts Ishigaki : <strong id="banner-walls"><?= $defaultCastle['topography']['wall_height_m'] ?> m</strong></span>
                        </div>
                        <div>
                            <i class="fa-solid fa-gem text-teal me-1"></i>
                            <span>Relique : <strong id="banner-relic"><?= htmlspecialchars($defaultCastle['relic_bonus']) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerte spécifique à la mécanique martiale du château -->
            <div class="mt-3 p-2 px-3 rounded bg-white bg-opacity-10 border border-warning border-opacity-25 d-flex align-items-center gap-2 small">
                <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                <div>
                    <strong>Règle Martiale &amp; Contrainte Topographique : </strong>
                    <span id="banner-special-mechanic"><?= htmlspecialchars($defaultCastle['topography']['special_mechanic']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         INTERFACE DE CONFIGURATION SPLIT-SCREEN (ATTAQUANT VS DÉFENSEUR)
         ═══════════════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-3">

        <!-- ── COLONNE GAUCHE : CORPS EXPÉDITIONNAIRE ASSAILLANT ── -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0" style="border-top: 3px solid #dc2626 !important;">
                <div class="card-header bg-danger-lt py-2 px-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-danger m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-fire text-danger"></i>
                        <span>Corps Expéditionnaire Assaillant</span>
                    </h3>
                    <span class="badge bg-danger text-white font-monospace" id="badge-att-power">Puissance d'Assaut : 0</span>
                </div>
                <div class="card-body p-3">

                    <!-- Options de l'Assaillant : Clan, Tactique de Siège & Météo -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold mb-1">Clan Assaillant</label>
                            <select id="att-clan" class="form-select form-select-sm" onchange="recalculateSiegeTotals()">
                                <option value="terran" selected>Clan Oda (Arquebuses &amp; Poudre)</option>
                                <option value="vorash">Clan Takeda (Furie de Choc)</option>
                                <option value="aethelis">Clan Tokugawa (Discipline)</option>
                                <option value="neutral">Rebelles &amp; Rōnin</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold mb-1">Doctrine de Siège</label>
                            <select id="siege-tactics" class="form-select form-select-sm" onchange="recalculateSiegeTotals()">
                                <option value="frontal_assault" selected>Assaut Frontal Massif (+15% Éch. 1 &amp; 3)</option>
                                <option value="artillery_bombardment">Pilonnage d'Artillerie (+30% vs Portes/Murs)</option>
                                <option value="shinobi_infiltration">Infiltration Furtive Shinobi (+40% Éch. 2)</option>
                                <option value="attrition_siege">Blocus d'Usure &amp; Famine (-30% Pertes Att.)</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold mb-1">Météo &amp; Heure de l'Assaut</label>
                            <select id="siege-weather" class="form-select form-select-sm" onchange="recalculateSiegeTotals()">
                                <?php foreach ($weatherList as $wKey => $wData): ?>
                                    <option value="<?= htmlspecialchars($wKey) ?>" <?= $wKey === 'clear' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wData['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Composition des Troupes de l'Attaquant -->
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-users me-1"></i>Bataillons Assaillants &amp; Engins
                            </span>
                            <span class="small text-muted font-monospace" id="att-troops-count">0 guerriers</span>
                        </div>

                        <div class="row g-2">
                            <!-- Ashigaru -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-person-military-pointing text-success"></i> Ashigarus Yari
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Att: 40 &bull; Polyvalents</div>
                                    </div>
                                    <input type="number" id="att-ashigaru" class="form-control form-control-sm text-end font-monospace" min="0" value="400" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Arquebusiers -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-crosshairs text-danger"></i> Arquebusiers
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Att: 65 &bull; Salve Tanegashima</div>
                                    </div>
                                    <input type="number" id="att-arquebusier" class="form-control form-control-sm text-end font-monospace" min="0" value="120" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Archers Yumi -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-bullseye text-primary"></i> Archers Yumi
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Att: 55 &bull; Tirs de couverture</div>
                                    </div>
                                    <input type="number" id="att-archer" class="form-control form-control-sm text-end font-monospace" min="0" value="100" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Samouraïs Katana -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-khanda text-purple"></i> Samouraïs Katana
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Att: 85 &bull; Choc au Tenshu</div>
                                    </div>
                                    <input type="number" id="att-samurai" class="form-control form-control-sm text-end font-monospace" min="0" value="80" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Cavalerie -->
                            <div class="col-sm-6" id="att-cavalry-container">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-horse text-warning"></i> Cavalerie Akazonae
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;" id="att-cavalry-desc">Att: 95 &bull; Plaines/Collines</div>
                                    </div>
                                    <input type="number" id="att-cavalry" class="form-control form-control-sm text-end font-monospace" min="0" value="40" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Ninjas Shinobi -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #f8fafc !important;">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1 text-dark">
                                            <i class="fa-solid fa-mask"></i> Ninjas Shinobi
                                        </div>
                                        <div class="text-dark" style="font-size: 0.7rem;">Att: 110 &bull; Escalade Ishigaki</div>
                                    </div>
                                    <input type="number" id="att-shinobi" class="form-control form-control-sm text-end font-monospace fw-bold text-dark" min="0" value="25" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Garde Hatamoto -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-shield text-info"></i> Garde Hatamoto
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Att: 210 &bull; Garde d'élite</div>
                                    </div>
                                    <input type="number" id="att-hatamoto" class="form-control form-control-sm text-end font-monospace" min="0" value="15" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Bélier de Siège -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #fff1f2 !important;">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1 text-danger">
                                            <i class="fa-solid fa-hammer"></i> Bélier Blindé
                                        </div>
                                        <div class="text-danger" style="font-size: 0.7rem;">Att: 400 &bull; Dégâts Portes x2.5</div>
                                    </div>
                                    <input type="number" id="att-ram" class="form-control form-control-sm text-end font-monospace fw-bold text-danger" min="0" value="6" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Catapulte Ôzutsu -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #fff1f2 !important;">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1 text-danger">
                                            <i class="fa-solid fa-bomb"></i> Catapulte Ôzutsu / Tour de Siège
                                        </div>
                                        <div class="text-danger" style="font-size: 0.7rem;">Att: 800 &bull; Pilonnage lourd des Murailles x2.2</div>
                                    </div>
                                    <input type="number" id="att-catapult" class="form-control form-control-sm text-end font-monospace fw-bold text-danger" min="0" value="4" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé Statistique Attaquant -->
                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                        <span class="small text-muted">Puissance Brute Estimée :</span>
                        <span class="fw-bold text-danger font-monospace fs-5" id="att-raw-power">0</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── COLONNE DROITE : LE DONJON & SA GARNISON DÉFENSIVE ── -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0" style="border-top: 3px solid #059669 !important;">
                <div class="card-header bg-success-lt py-2 px-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-success m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chess-rook text-success"></i>
                        <span>Le Donjon Castral &amp; Sa Garnison</span>
                    </h3>
                    <span class="badge bg-success text-white font-monospace" id="badge-def-power">Défense du Sanctuaire : 0</span>
                </div>
                <div class="card-body p-3">

                    <!-- Options du Donjon : Clan Occupant & Mode Garnison -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold mb-1">Garnison / Clan Défenseur</label>
                            <select id="def-clan" class="form-select form-select-sm" onchange="recalculateSiegeTotals()">
                                <option value="ancestral" selected>Garnison Impériale Ancestrale (PNJ Légendaire)</option>
                                <option value="aethelis">Clan Tokugawa (Résistance de Fer)</option>
                                <option value="terran">Clan Oda (Feu de Meurtrières)</option>
                                <option value="vorash">Clan Takeda (Contre-Charge)</option>
                                <option value="neutral">Rebelles du Shogunat</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold mb-1">Garnison Recommandée</label>
                            <button type="button" class="btn btn-sm btn-outline-success w-100 fw-bold" onclick="resetToAncestralGarrison()">
                                <i class="fa-solid fa-arrows-rotate me-1"></i>Charger Garnison Ancestrale
                            </button>
                        </div>
                    </div>

                    <!-- Curseur de Fortification du Donjon (Muraille & Tenshu) -->
                    <div class="card mb-3 border-success-subtle shadow-xs" style="background: #f0fdf4; border: 1px solid #86efac;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-success text-white rounded"><i class="fa-solid fa-shield-halved"></i></span>
                                    <span class="fw-bold text-dark">Niveau de Fortification des Remparts</span>
                                </div>
                                <span class="badge bg-success font-monospace px-2 py-1 fs-6" id="wall-level-badge">Niveau 12</span>
                            </div>

                            <input type="range" class="form-range" min="0" max="20" step="1" value="12" id="def-wall-level" oninput="updateCastleWallMetrics()">
                            <div class="d-flex justify-content-between small text-muted font-monospace" style="font-size: 0.72rem;">
                                <span>Niv 0 (Palissade)</span>
                                <span>Niv 5</span>
                                <span>Niv 10</span>
                                <span>Niv 15</span>
                                <span>Niv 20 (Imprenable)</span>
                            </div>

                            <!-- Métriques calculées de la forteresse -->
                            <div class="row g-2 mt-2 pt-2 border-top border-success-subtle small">
                                <div class="col-6">
                                    <div class="text-secondary">PV Remparts Ishigaki :</div>
                                    <div class="fw-bold text-success font-monospace" id="calc-wall-hp">13 600 PV</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary">PV Donjon Tenshu :</div>
                                    <div class="fw-bold text-success font-monospace" id="calc-tenshu-hp">29 400 PV</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composition des Troupes de la Garnison -->
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-shield me-1"></i>Garnison Intérieure du Donjon
                            </span>
                            <span class="small text-muted font-monospace" id="def-troops-count">0 défenseurs</span>
                        </div>

                        <div class="row g-2">
                            <!-- Ashigaru Def -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-person-military-pointing text-success"></i> Ashigarus Yari
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Def: 55</div>
                                    </div>
                                    <input type="number" id="def-ashigaru" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['ashigaru'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Archers Def -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-bullseye text-primary"></i> Archers Créneaux
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Def: 50 &bull; Tirs de muraille</div>
                                    </div>
                                    <input type="number" id="def-archer" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['archer'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Arquebusiers Def -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-crosshairs text-danger"></i> Arquebuses Yagura
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Def: 60 &bull; Meurtrières</div>
                                    </div>
                                    <input type="number" id="def-arquebusier" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['arquebusier'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Samouraïs Def -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-khanda text-purple"></i> Samouraïs de Garde
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Def: 75 &bull; Remparts</div>
                                    </div>
                                    <input type="number" id="def-samurai" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['samurai'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Cavalerie Def -->
                            <div class="col-sm-6" id="def-cavalry-container">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-horse text-warning"></i> Cavalerie de Réserve
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;" id="def-cavalry-desc">Def: 60</div>
                                    </div>
                                    <input type="number" id="def-cavalry" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['cavalry'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Ninjas Défensifs -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1 text-dark">
                                            <i class="fa-solid fa-mask"></i> Contre-Ninjas Shinobi
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Def: 45 &bull; Pièges</div>
                                    </div>
                                    <input type="number" id="def-shinobi" class="form-control form-control-sm text-end font-monospace" min="0" value="<?= $defaultGarrison['shinobi'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>

                            <!-- Hatamoto Def -->
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #ecfdf5 !important;">
                                    <div>
                                        <div class="fw-bold small d-flex align-items-center gap-1 text-success">
                                            <i class="fa-solid fa-crown"></i> Garde d'Élite du Shōgun (Hatamoto du Tenshu)
                                        </div>
                                        <div class="text-success" style="font-size: 0.7rem;">Def: 270 &bull; Dernier rempart du Donjon central</div>
                                    </div>
                                    <input type="number" id="def-hatamoto" class="form-control form-control-sm text-end font-monospace fw-bold text-success" min="0" value="<?= $defaultGarrison['hatamoto'] ?>" style="width: 75px;" oninput="recalculateSiegeTotals()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé Statistique Défenseur -->
                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                        <span class="small text-muted">Puissance Défensive Brute :</span>
                        <span class="fw-bold text-success font-monospace fs-5" id="def-raw-power">0</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         PANNEAU DE CONTRÔLE DES SIMULATIONS (UNITAIRE & MONTE-CARLO)
         ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-3 shadow-sm border-0 bg-white">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-danger btn-lg shadow-sm px-4 fw-bold d-flex align-items-center gap-2" id="btn-run-siege" onclick="runSiegeSimulation(false)">
                        <i class="fa-solid fa-play"></i>
                        <span>Lancer le Siège Tactique</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg shadow-sm px-3 fw-bold d-flex align-items-center gap-2" id="btn-run-montecarlo" onclick="runSiegeSimulation(true)">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Simulation Monte-Carlo (100 passes)</span>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="btn-export-markdown" onclick="exportSiegeMarkdown()" disabled>
                        <i class="fa-solid fa-file-code"></i>
                        <span>Exporter le Rapport Markdown</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         RÉSULTATS DE LA SIMULATION (PROGRESSION, PERTES & STATISTIQUES)
         ═══════════════════════════════════════════════════════════════════ -->
    <div id="siege-results-container" class="d-none">

        <!-- 1. Bannière de Résultat Global -->
        <div class="card mb-3 shadow-sm border-0" id="result-status-card">
            <div class="card-body p-4 text-center">
                <div class="d-inline-flex p-3 rounded-circle mb-2" id="result-status-icon-wrapper" style="font-size: 2.5rem;">
                    <i class="fa-solid fa-trophy" id="result-status-icon"></i>
                </div>
                <h2 class="h2 fw-bold mb-1" id="result-status-title">VICTOIRE TOTALE DU SIÈGE</h2>
                <div class="fs-4 text-muted" id="result-status-desc">
                    Le donjon Tenshu est tombé, le drapeau du clan flotte sur le château et la Relique Antique est capturée !
                </div>
            </div>
        </div>

        <!-- 2. Widget Statistique Monte-Carlo (si mode Monte-Carlo) -->
        <div class="card mb-3 shadow-sm border-0 d-none" id="montecarlo-stats-card" style="border-left: 4px solid #3b82f6 !important;">
            <div class="card-header bg-primary-lt py-2 px-3">
                <h4 class="card-title text-primary m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Analyse Statistique Monte-Carlo (100 itérations indépendantes)</span>
                </h4>
            </div>
            <div class="card-body p-3">
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <div class="text-secondary small fw-bold text-uppercase">Taux de Victoire Assaillant</div>
                            <div class="fs-1 fw-bold text-success font-monospace" id="mc-win-rate">0%</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <div class="text-secondary small fw-bold text-uppercase">Échec à l'Échelon 1 (Douves)</div>
                            <div class="fs-1 fw-bold text-danger font-monospace" id="mc-fail-p1">0%</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <div class="text-secondary small fw-bold text-uppercase">Échec à l'Échelon 2 (Murs)</div>
                            <div class="fs-1 fw-bold text-warning font-monospace" id="mc-fail-p2">0%</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded bg-light border">
                            <div class="text-secondary small fw-bold text-uppercase">Échec à l'Échelon 3 (Tenshu)</div>
                            <div class="fs-1 fw-bold text-secondary font-monospace" id="mc-fail-p3">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Jauges des 3 Échelons de Siège -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header bg-dark text-white py-2 px-3">
                <h4 class="card-title text-warning m-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                    <i class="fa-solid fa-stairs"></i>
                    <span>Progression du Siège par Échelons Séquentiels</span>
                </h4>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <!-- Échelon 1 -->
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" id="phase1-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">Échelon 1 : Douves &amp; Portes</span>
                                <span class="badge" id="phase1-badge">SUCCÈS</span>
                            </div>
                            <div class="small text-muted mb-2" id="phase1-desc">Pénétration des douves d'eau et brèche des barbacanes.</div>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-danger" id="phase1-progress" style="width: 100%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-secondary" style="font-size: 0.72rem;">
                                <span>Pertes Assaillants : <strong id="phase1-att-loss">0%</strong></span>
                                <span>Pertes Donjon : <strong id="phase1-def-loss">0%</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Échelon 2 -->
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" id="phase2-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">Échelon 2 : Murailles Ishigaki</span>
                                <span class="badge" id="phase2-badge">EN COURS</span>
                            </div>
                            <div class="small text-muted mb-2" id="phase2-desc">Escalade des remparts et tirs de meurtrières.</div>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-warning" id="phase2-progress" style="width: 0%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-secondary" style="font-size: 0.72rem;">
                                <span>Pertes Assaillants : <strong id="phase2-att-loss">0%</strong></span>
                                <span>Pertes Donjon : <strong id="phase2-def-loss">0%</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Échelon 3 -->
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" id="phase3-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">Échelon 3 : Donjon Tenshu</span>
                                <span class="badge" id="phase3-badge">EN ATTENTE</span>
                            </div>
                            <div class="small text-muted mb-2" id="phase3-desc">Combat rapproché au sabre dans les étages du donjon.</div>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar bg-success" id="phase3-progress" style="width: 0%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-secondary" style="font-size: 0.72rem;">
                                <span>Pertes Assaillants : <strong id="phase3-att-loss">0%</strong></span>
                                <span>Pertes Donjon : <strong id="phase3-def-loss">0%</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Tableau Comparatif des Pertes et Dégradations Structurelles -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-light py-2 px-3">
                        <h4 class="card-title text-dark m-0" style="font-size: 0.9rem;">
                            <i class="fa-solid fa-users-slash text-danger me-1"></i>Bilan des Pertes Humaines (Attrition)
                        </h4>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped text-center m-0">
                                <thead>
                                    <tr>
                                        <th class="text-start">Armée</th>
                                        <th>Initial</th>
                                        <th>Survivants</th>
                                        <th>Pertes</th>
                                        <th>Taux de Perte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-bold text-danger">Corps Assaillant</td>
                                        <td id="res-att-initial" class="font-monospace">0</td>
                                        <td id="res-att-survivors" class="font-monospace text-success fw-bold">0</td>
                                        <td id="res-att-lost" class="font-monospace text-danger">0</td>
                                        <td><span class="badge bg-danger-lt text-danger font-monospace" id="res-att-loss-pct">0%</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-success">Garnison du Château</td>
                                        <td id="res-def-initial" class="font-monospace">0</td>
                                        <td id="res-def-survivors" class="font-monospace text-success fw-bold">0</td>
                                        <td id="res-def-lost" class="font-monospace text-danger">0</td>
                                        <td><span class="badge bg-success-lt text-success font-monospace" id="res-def-loss-pct">0%</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-light py-2 px-3">
                        <h4 class="card-title text-dark m-0" style="font-size: 0.9rem;">
                            <i class="fa-solid fa-building-circle-exclamation text-warning me-1"></i>Dégradation des Fortifications
                        </h4>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-bold">Intégrité des Remparts Ishigaki :</span>
                                <span class="font-monospace fw-bold" id="res-wall-hp-text">0 / 0 PV</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" id="res-wall-bar" style="width: 100%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-bold">Intégrité du Donjon Central Tenshu :</span>
                                <span class="font-monospace fw-bold" id="res-tenshu-hp-text">0 / 0 PV</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" id="res-tenshu-bar" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Chronique Détaillée des 3 Échelons (Log Narration) -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light py-2 px-3">
                <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-scroll text-warning"></i>
                    <span>Chronique Détaillée des Échelons d'Affrontement</span>
                </h4>
            </div>
            <div class="card-body p-3" id="phases-log-container">
                <!-- Rempli dynamiquement en JS -->
            </div>
        </div>

    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL D'EXPORTATION DU RAPPORT EN MARKDOWN
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modal-export-markdown" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-code text-warning"></i>
                    <span>Rapport de Siège en Markdown (Exportation GDD)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <textarea id="markdown-export-textarea" class="form-control font-monospace" rows="16" readonly style="font-size: 0.82rem; background: #f8fafc;"></textarea>
            </div>
            <div class="modal-footer py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="copyMarkdownToClipboard()">
                        <i class="fa-solid fa-copy me-1"></i>Copier dans le Presse-Papier
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="downloadMarkdownFile()">
                        <i class="fa-solid fa-download me-1"></i>Télécharger (.md)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     LOGIQUE JAVASCRIPT DU SIMULATEUR DE SIÈGE
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
const castlesData = <?= json_encode($allCastles) ?>;
const presetsData = <?= json_encode($historicalPresets) ?>;
const troopStatsData = <?= json_encode($troopStats) ?>;
let activeCastleCode = '<?= $defaultCastleCode ?>';
let latestSiegeResult = null;

document.addEventListener('DOMContentLoaded', function() {
    updateCastleWallMetrics();
    recalculateSiegeTotals();
});

// Sélectionner un château
function selectCastle(code) {
    if (!castlesData[code]) return;
    activeCastleCode = code;
    const c = castlesData[code];
    const topo = c.topography;

    // Mise à jour visuelle des cartes du carrousel
    document.querySelectorAll('.castle-thumb-card').forEach(el => {
        if (el.getAttribute('data-castle-code') === code) {
            el.classList.add('border-warning', 'shadow-sm');
            el.classList.remove('border-secondary-subtle');
            el.style.background = '#fffbeb';
        } else {
            el.classList.remove('border-warning', 'shadow-sm');
            el.classList.add('border-secondary-subtle');
            el.style.background = '#ffffff';
        }
    });

    // Mise à jour de la bannière
    document.getElementById('current-castle-badge').textContent = `${c.name} (${c.kanji})`;
    document.getElementById('banner-castle-img').src = c.image_src;
    document.getElementById('banner-castle-title').textContent = c.name;
    document.getElementById('banner-castle-kanji').textContent = c.kanji;
    document.getElementById('banner-castle-province').textContent = c.province;
    document.getElementById('banner-castle-type').textContent = topo.type_name;
    document.getElementById('banner-castle-desc').textContent = c.short_desc;
    document.getElementById('banner-altitude').textContent = `${topo.altitude_m} m`;
    document.getElementById('banner-gates').textContent = `${topo.gate_layers} battants`;
    document.getElementById('banner-walls').textContent = `${topo.wall_height_m} m`;
    document.getElementById('banner-relic').textContent = c.relic_bonus;
    document.getElementById('banner-special-mechanic').textContent = topo.special_mechanic;

    // Contrainte cavalerie sur Yamajiro / falaises
    const attCavInput = document.getElementById('att-cavalry');
    const defCavInput = document.getElementById('def-cavalry');
    const attCavDesc = document.getElementById('att-cavalry-desc');
    const defCavDesc = document.getElementById('def-cavalry-desc');

    if (!topo.cavalry_allowed) {
        if (attCavInput) { attCavInput.value = 0; attCavInput.disabled = true; }
        if (defCavInput) { defCavInput.value = 0; defCavInput.disabled = true; }
        if (attCavDesc) attCavDesc.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-ban"></i> Banni (Relief ${topo.type_name})</span>`;
        if (defCavDesc) defCavDesc.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-ban"></i> Banni (Relief ${topo.type_name})</span>`;
    } else {
        if (attCavInput) attCavInput.disabled = false;
        if (defCavInput) defCavInput.disabled = false;
        if (attCavDesc) attCavDesc.textContent = "Att: 95 • Plaines/Collines";
        if (defCavDesc) defCavDesc.textContent = "Def: 60";
    }

    updateCastleWallMetrics();
    recalculateSiegeTotals();
}

// Mise à jour du slider et métriques de muraille
function updateCastleWallMetrics() {
    const wallLvl = parseInt(document.getElementById('def-wall-level')?.value || 10, 10);
    const badge = document.getElementById('wall-level-badge');
    if (badge) badge.textContent = `Niveau ${wallLvl}`;

    let baseWallHP = 4000 + (wallLvl * 800);
    if (activeCastleCode === 'marugame') {
        baseWallHP = Math.round(baseWallHP * 1.50); // Murailles colossales de 60m
    }
    const tenshuHP = 15000 + (wallLvl * 1200);

    const elWall = document.getElementById('calc-wall-hp');
    if (elWall) elWall.textContent = `${baseWallHP.toLocaleString('fr-FR')} PV`;

    const elTenshu = document.getElementById('calc-tenshu-hp');
    if (elTenshu) elTenshu.textContent = `${tenshuHP.toLocaleString('fr-FR')} PV`;

    recalculateSiegeTotals();
}

// Recalcul des totaux en temps réel
function recalculateSiegeTotals() {
    const attTypes = ['ashigaru', 'archer', 'arquebusier', 'samurai', 'cavalry', 'shinobi', 'hatamoto', 'ram', 'catapult'];
    const defTypes = ['ashigaru', 'archer', 'arquebusier', 'samurai', 'cavalry', 'shinobi', 'hatamoto'];

    let attTotalCount = 0;
    let attRawPower = 0;
    attTypes.forEach(t => {
        const qty = parseInt(document.getElementById(`att-${t}`)?.value || 0, 10);
        attTotalCount += qty;
        const stat = troopStatsData[t];
        if (stat) attRawPower += (stat.attack * qty);
    });

    let defTotalCount = 0;
    let defRawPower = 0;
    defTypes.forEach(t => {
        const qty = parseInt(document.getElementById(`def-${t}`)?.value || 0, 10);
        defTotalCount += qty;
        const stat = troopStatsData[t];
        if (stat) defRawPower += (stat.defense * qty);
    });

    const elAttCount = document.getElementById('att-troops-count');
    if (elAttCount) elAttCount.textContent = `${attTotalCount.toLocaleString('fr-FR')} guerriers`;

    const elAttPower = document.getElementById('att-raw-power');
    if (elAttPower) elAttPower.textContent = attRawPower.toLocaleString('fr-FR');

    const elBadgeAtt = document.getElementById('badge-att-power');
    if (elBadgeAtt) elBadgeAtt.textContent = `Puissance d'Assaut : ${attRawPower.toLocaleString('fr-FR')}`;

    const elDefCount = document.getElementById('def-troops-count');
    if (elDefCount) elDefCount.textContent = `${defTotalCount.toLocaleString('fr-FR')} défenseurs`;

    const elDefPower = document.getElementById('def-raw-power');
    if (elDefPower) elDefPower.textContent = defRawPower.toLocaleString('fr-FR');

    const elBadgeDef = document.getElementById('badge-def-power');
    if (elBadgeDef) elBadgeDef.textContent = `Défense du Sanctuaire : ${defRawPower.toLocaleString('fr-FR')}`;
}

// Réinitialiser la garnison ancestrale
function resetToAncestralGarrison() {
    const topo = castlesData[activeCastleCode]?.topography;
    const baseMult = (topo && topo.type === 'yamajiro') ? 0.85 : ((topo && topo.type === 'hirajiro') ? 1.20 : 1.00);

    const values = {
        'ashigaru': Math.round(250 * baseMult),
        'archer': Math.round(120 * baseMult),
        'arquebusier': Math.round(80 * baseMult),
        'samurai': Math.round(60 * baseMult),
        'cavalry': (topo && topo.cavalry_allowed) ? Math.round(20 * baseMult) : 0,
        'shinobi': Math.round(15 * baseMult),
        'hatamoto': Math.round(10 * baseMult)
    };

    for (const [t, val] of Object.entries(values)) {
        const inp = document.getElementById(`def-${t}`);
        if (inp && !inp.disabled) inp.value = val;
    }
    recalculateSiegeTotals();
}

// Charger un préréglage historique
function loadHistoricalPreset(pId) {
    const p = presetsData[pId];
    if (!p) return;

    selectCastle(p.castle_code);

    if (document.getElementById('att-clan')) document.getElementById('att-clan').value = p.att_clan;
    if (document.getElementById('def-clan')) document.getElementById('def-clan').value = p.def_clan;
    if (document.getElementById('siege-tactics')) document.getElementById('siege-tactics').value = p.tactics;
    if (document.getElementById('siege-weather')) document.getElementById('siege-weather').value = p.weather;
    if (document.getElementById('def-wall-level')) {
        document.getElementById('def-wall-level').value = p.wall_level;
        updateCastleWallMetrics();
    }

    if (p.att_troops) {
        for (const [t, count] of Object.entries(p.att_troops)) {
            const inp = document.getElementById(`att-${t}`);
            if (inp && !inp.disabled) inp.value = count;
        }
    }

    if (p.def_troops) {
        for (const [t, count] of Object.entries(p.def_troops)) {
            const inp = document.getElementById(`def-${t}`);
            if (inp && !inp.disabled) inp.value = count;
        }
    }

    recalculateSiegeTotals();
}

// Réinitialiser le simulateur
function resetCastleSimulator() {
    loadHistoricalPreset('himeji_grand_siege');
    document.getElementById('siege-results-container').classList.add('d-none');
    document.getElementById('btn-export-markdown').disabled = true;
    latestSiegeResult = null;
}

// Récupérer le payload de configuration pour l'API
function buildSiegePayload(isMonteCarlo = false) {
    const attTypes = ['ashigaru', 'archer', 'arquebusier', 'samurai', 'cavalry', 'shinobi', 'hatamoto', 'ram', 'catapult'];
    const defTypes = ['ashigaru', 'archer', 'arquebusier', 'samurai', 'cavalry', 'shinobi', 'hatamoto'];

    const attTroops = {};
    attTypes.forEach(t => {
        attTroops[t] = parseInt(document.getElementById(`att-${t}`)?.value || 0, 10);
    });

    const defTroops = {};
    defTypes.forEach(t => {
        defTroops[t] = parseInt(document.getElementById(`def-${t}`)?.value || 0, 10);
    });

    return {
        action: 'simulate_castle_siege',
        castle_code: activeCastleCode,
        weather: document.getElementById('siege-weather')?.value || 'clear',
        wall_level: parseInt(document.getElementById('def-wall-level')?.value || 10, 10),
        tactics: document.getElementById('siege-tactics')?.value || 'frontal_assault',
        att_clan: document.getElementById('att-clan')?.value || 'terran',
        def_clan: document.getElementById('def-clan')?.value || 'aethelis',
        att_troops: attTroops,
        def_troops: defTroops,
        is_monte_carlo: isMonteCarlo ? 1 : 0,
        iterations: 100
    };
}

// Exécuter la simulation de siège via l'API
async function runSiegeSimulation(isMonteCarlo = false) {
    const btnSiege = document.getElementById('btn-run-siege');
    const btnMc = document.getElementById('btn-run-montecarlo');

    if (btnSiege) btnSiege.disabled = true;
    if (btnMc) btnMc.disabled = true;

    const payload = buildSiegePayload(isMonteCarlo);

    try {
        const res = await fetch('/api/dev_team.php?action=simulate_castle_siege', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!data || !data.success) {
            throw new Error((data && data.error) ? data.error : "Erreur de simulation.");
        }

        latestSiegeResult = isMonteCarlo ? data.detailed_sample : data;
        displaySiegeResults(data, isMonteCarlo);
        document.getElementById('btn-export-markdown').disabled = false;

    } catch (e) {
        alert("Erreur de simulation : " + e.message);
    } finally {
        if (btnSiege) btnSiege.disabled = false;
        if (btnMc) btnMc.disabled = false;
    }
}

// Affichage des résultats
function displaySiegeResults(data, isMonteCarlo) {
    const container = document.getElementById('siege-results-container');
    container.classList.remove('d-none');
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const sample = isMonteCarlo ? data.detailed_sample : data;
    const isConquered = sample.conquered;

    // 1. Bannière globale
    const statusCard = document.getElementById('result-status-card');
    const statusTitle = document.getElementById('result-status-title');
    const statusDesc = document.getElementById('result-status-desc');
    const statusIcon = document.getElementById('result-status-icon');
    const iconWrapper = document.getElementById('result-status-icon-wrapper');

    if (isConquered) {
        statusCard.style.borderLeft = '6px solid #22c55e';
        statusTitle.textContent = "VICTOIRE TOTALE DU SIÈGE";
        statusTitle.className = "h2 fw-bold mb-1 text-success font-game";
        statusDesc.textContent = sample.summary_message;
        statusIcon.className = "fa-solid fa-trophy text-white";
        iconWrapper.className = "d-inline-flex p-3 rounded-circle mb-2 bg-success";
    } else {
        statusCard.style.borderLeft = '6px solid #ef4444';
        statusTitle.textContent = "ÉCHEC DU SIÈGE — DÉFAITE ASSAILLANTE";
        statusTitle.className = "h2 fw-bold mb-1 text-danger font-game";
        statusDesc.textContent = sample.summary_message;
        statusIcon.className = "fa-solid fa-shield-xmark text-white";
        iconWrapper.className = "d-inline-flex p-3 rounded-circle mb-2 bg-danger";
    }

    // 2. Widget Monte Carlo
    const mcCard = document.getElementById('montecarlo-stats-card');
    if (isMonteCarlo) {
        mcCard.classList.remove('d-none');
        document.getElementById('mc-win-rate').textContent = `${data.win_rate}%`;
        document.getElementById('mc-fail-p1').textContent = `${data.failure_breakdown.phase1_moat_pct}%`;
        document.getElementById('mc-fail-p2').textContent = `${data.failure_breakdown.phase2_walls_pct}%`;
        document.getElementById('mc-fail-p3').textContent = `${data.failure_breakdown.phase3_tenshu_pct}%`;
    } else {
        mcCard.classList.add('d-none');
    }

    // 3. Jauges des 3 échelons
    renderPhaseProgress(1, sample);
    renderPhaseProgress(2, sample);
    renderPhaseProgress(3, sample);

    // 4. Bilan humain
    const cas = sample.casualties;
    document.getElementById('res-att-initial').textContent = cas.attacker.initial.toLocaleString('fr-FR');
    document.getElementById('res-att-survivors').textContent = cas.attacker.survivors.toLocaleString('fr-FR');
    document.getElementById('res-att-lost').textContent = cas.attacker.lost.toLocaleString('fr-FR');
    document.getElementById('res-att-loss-pct').textContent = `${cas.attacker.loss_pct}%`;

    document.getElementById('res-def-initial').textContent = cas.defender.initial.toLocaleString('fr-FR');
    document.getElementById('res-def-survivors').textContent = cas.defender.survivors.toLocaleString('fr-FR');
    document.getElementById('res-def-lost').textContent = cas.defender.lost.toLocaleString('fr-FR');
    document.getElementById('res-def-loss-pct').textContent = `${cas.defender.loss_pct}%`;

    // 5. Fortifications
    const wall = sample.wall_metrics;
    const tenshu = sample.tenshu_metrics;

    document.getElementById('res-wall-hp-text').textContent = `${wall.remaining_hp.toLocaleString('fr-FR')} / ${wall.initial_hp.toLocaleString('fr-FR')} PV (${wall.integrity_pct}%)`;
    document.getElementById('res-wall-bar').style.width = `${wall.integrity_pct}%`;

    document.getElementById('res-tenshu-hp-text').textContent = `${tenshu.remaining_hp.toLocaleString('fr-FR')} / ${tenshu.initial_hp.toLocaleString('fr-FR')} PV (${tenshu.integrity_pct}%)`;
    document.getElementById('res-tenshu-bar').style.width = `${tenshu.integrity_pct}%`;

    // 6. Log narratif
    const logBox = document.getElementById('phases-log-container');
    logBox.innerHTML = '';
    if (sample.phases && sample.phases.length > 0) {
        sample.phases.forEach((p, idx) => {
            const isSuccess = p.breached || p.captured;
            const logItem = document.createElement('div');
            logItem.className = 'p-3 mb-2 rounded border bg-light';
            logItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark font-game">${p.title}</span>
                    <span class="badge ${isSuccess ? 'bg-success' : 'bg-danger'}">${isSuccess ? 'SUCCÈS' : 'ÉCHEC'}</span>
                </div>
                <div class="text-secondary small mb-2 fst-italic">« ${p.message} »</div>
                <div class="d-flex gap-3 small text-muted font-monospace">
                    <span>Puissance Attaquante : <strong>${p.att_power.toLocaleString('fr-FR')}</strong></span>
                    <span>Puissance Donjon : <strong>${p.def_power.toLocaleString('fr-FR')}</strong></span>
                    <span>Pertes Att. : <strong>${p.att_losses_pct}%</strong></span>
                    <span>Pertes Def. : <strong>${p.def_losses_pct}%</strong></span>
                </div>
            `;
            logBox.appendChild(logItem);
        });
    }
}

function renderPhaseProgress(phaseNum, sample) {
    const badge = document.getElementById(`phase${phaseNum}-badge`);
    const bar = document.getElementById(`phase${phaseNum}-progress`);
    const card = document.getElementById(`phase${phaseNum}-card`);
    const attLoss = document.getElementById(`phase${phaseNum}-att-loss`);
    const defLoss = document.getElementById(`phase${phaseNum}-def-loss`);

    const pData = sample.phases ? sample.phases.find(p => p.phase === phaseNum) : null;

    if (!pData) {
        badge.className = 'badge bg-secondary';
        badge.textContent = 'NON ATTEINT';
        bar.style.width = '0%';
        bar.className = 'progress-bar bg-secondary';
        card.style.background = '#f8fafc';
        card.style.opacity = '0.6';
        if (attLoss) attLoss.textContent = '-';
        if (defLoss) defLoss.textContent = '-';
        return;
    }

    card.style.opacity = '1.0';
    const isSuccess = pData.breached || pData.captured;
    if (isSuccess) {
        badge.className = 'badge bg-success';
        badge.textContent = 'BRÈCHE CONFIRMÉE';
        bar.className = 'progress-bar bg-success';
        bar.style.width = '100%';
        card.style.background = '#f0fdf4';
    } else {
        badge.className = 'badge bg-danger';
        badge.textContent = 'REPPOUSSÉ';
        bar.className = 'progress-bar bg-danger';
        bar.style.width = '45%';
        card.style.background = '#fef2f2';
    }

    if (attLoss) attLoss.textContent = `${pData.att_losses_pct}%`;
    if (defLoss) defLoss.textContent = `${pData.def_losses_pct}%`;
}

// Exporter en Markdown
async function exportSiegeMarkdown() {
    if (!latestSiegeResult) return;
    try {
        const res = await fetch('/api/dev_team.php?action=export_castle_siege_markdown', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(latestSiegeResult)
        });
        const data = await res.json();
        if (data && data.success && data.markdown) {
            document.getElementById('markdown-export-textarea').value = data.markdown;
            const modalEl = document.getElementById('modal-export-markdown');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }
    } catch (e) {
        alert("Erreur lors de l'export Markdown : " + e.message);
    }
}

// Copier Markdown
function copyMarkdownToClipboard() {
    const textarea = document.getElementById('markdown-export-textarea');
    if (!textarea) return;
    textarea.select();
    document.execCommand('copy');
    alert("Le rapport Markdown a été copié dans votre presse-papier !");
}

// Télécharger fichier .md
function downloadMarkdownFile() {
    const text = document.getElementById('markdown-export-textarea')?.value || '';
    if (!text) return;
    const blob = new Blob([text], { type: 'text/markdown;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `rapport_siege_${activeCastleCode}_${Date.now()}.md`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

