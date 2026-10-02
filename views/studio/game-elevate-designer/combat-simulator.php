<?php
/**
 * Module Studio Dev : Simulateur de Combat Féodal & Équilibrage Stratégique
 * Métier : Game Elevate Designer
 * Emplacement : views/studio/game-elevate-designer/combat-simulator.php
 */
?>

<!-- ── EN-TÊTE DU MODULE : SIMULATEUR DE COMBAT TACTIQUE ── -->
<div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid var(--tblr-danger) !important;">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger-lt text-danger font-game px-2 py-1">
                        <i class="fa-solid fa-shield-halved me-1"></i>Game Elevate Designer
                    </span>
                    <span class="badge bg-purple-lt text-purple">
                        <i class="fa-solid fa-calculator me-1"></i>Moteur Martial Sengoku
                    </span>
                    <span class="badge bg-warning-lt text-dark fw-bold">
                        ⭐ Prise en Compte Critique des Murailles
                    </span>
                </div>
                <h2 class="h3 mb-0 text-dark fw-bold d-flex align-items-center gap-2">
                    <span>Simulateur Tactique de Combat &amp; Siège de Fief</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Simulez instantanément les affrontements provinciaux en split-screen : composition des légions, doctrines de clan, dégradation structurelle des remparts et tirs de meurtrières.
                </div>
            </div>

            <!-- Boutons de Préréglages Rapides en 1 Clic -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-secondary small fw-bold d-none d-md-inline">Préréglages :</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset('open_village')">
                    <i class="fa-solid fa-wheat-awn text-warning me-1"></i>Raid Village Ouvert (Mur 0)
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyPreset('fortified_town')">
                    <i class="fa-solid fa-chess-rook text-primary me-1"></i>Bourg Fortifié (Mur 8)
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyPreset('imperial_fortress')">
                    <i class="fa-solid fa-shield-halved text-danger me-1"></i>Forteresse Impériale (Mur 20)
                </button>
                <button type="button" class="btn btn-sm btn-light text-muted" onclick="resetSimulator()" title="Réinitialiser">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     INTERFACE DE SAISIE EN SPLIT-SCREEN (ATTAQUANT VS DÉFENSEUR)
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- ── COLONNE GAUCHE : ARMÉE ATTAQUANTE ── -->
    <div class="col-12 col-xl-6">
        <div class="card h-100 shadow-sm border-0" style="border-top: 3px solid #dc2626 !important;">
            <div class="card-header bg-danger-lt py-2 px-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-danger m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-flag text-danger"></i>
                    <span>Corps Expéditionnaire Attaquant</span>
                </h3>
                <span class="badge bg-danger text-white" id="badge-att-power">Puissance : 0</span>
            </div>
            <div class="card-body p-3">

                <!-- 1. Faction & Doctrine de l'Attaquant -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Clan de l'Attaquant</label>
                        <select id="att-clan" class="form-select form-select-sm" onchange="calculateArmyTotals()">
                            <option value="terran" selected>Clan Oda (Arquebuses &amp; Poudre)</option>
                            <option value="vorash">Clan Takeda (Cavalerie Fūrinkazan)</option>
                            <option value="aethelis">Clan Tokugawa (Discipline de Fer)</option>
                            <option value="neutral">Rebelles Ronins / Indépendants</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Général &amp; Doctrine Martiale</label>
                        <select id="att-doctrine" class="form-select form-select-sm" onchange="calculateArmyTotals()">
                            <option value="standard" selected>Ordre de Marche Standard (+0%)</option>
                            <option value="furinkazan">Charge Furieuse Fūrinkazan (+15% Attaque)</option>
                            <option value="tanegashima">Salve Perforante Tanegashima (+20% Attaque)</option>
                            <option value="siege_master">Pilonnage de Siège Agressif (+25% vs Murailles)</option>
                        </select>
                    </div>
                </div>

                <!-- 2. Composition des Troupes de l'Attaquant -->
                <div class="border rounded p-2 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-users me-1"></i>Régiments &amp; Engins de Siège
                        </span>
                        <span class="small text-muted" id="att-troops-count">0 soldats</span>
                    </div>

                    <div class="row g-2">
                        <!-- Piquiers Ashigaru -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-person-military-pointing text-success"></i> Ashigarus Yari
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 40 &bull; Def: 55</div>
                                </div>
                                <input type="number" id="att-ashigaru" class="form-control form-control-sm text-end font-monospace" min="0" value="150" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Arquebusiers Tanegashima -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-crosshairs text-danger"></i> Arquebusiers
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 65 &bull; Def: 61</div>
                                </div>
                                <input type="number" id="att-arquebusier" class="form-control form-control-sm text-end font-monospace" min="0" value="80" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Samouraïs au Katana -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-khanda text-purple"></i> Samouraïs Katana
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 85 &bull; Def: 75</div>
                                </div>
                                <input type="number" id="att-samurai" class="form-control form-control-sm text-end font-monospace" min="0" value="50" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Cavalerie Akazonae -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-horse text-warning"></i> Cavalerie de Choc
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 95 &bull; Def: 60</div>
                                </div>
                                <input type="number" id="att-cavalry" class="form-control form-control-sm text-end font-monospace" min="0" value="30" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Garde Hatamoto Lourde -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-shield text-info"></i> Garde Hatamoto
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 210 &bull; Def: 270</div>
                                </div>
                                <input type="number" id="att-hatamoto" class="form-control form-control-sm text-end font-monospace" min="0" value="10" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Béliers Blindés de Siège -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #fff1f2 !important;">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1 text-danger">
                                        <i class="fa-solid fa-hammer"></i> Bélier de Siège
                                    </div>
                                    <div class="text-danger" style="font-size: 0.7rem;">Att: 400 &bull; Dégâts Mur x3</div>
                                </div>
                                <input type="number" id="att-ram" class="form-control form-control-sm text-end font-monospace fw-bold text-danger" min="0" value="6" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Catapultes & Balistes -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #fff1f2 !important;">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1 text-danger">
                                        <i class="fa-solid fa-bomb"></i> Catapulte Ôzutsu / Tour de Siège
                                    </div>
                                    <div class="text-danger" style="font-size: 0.7rem;">Att: 800 &bull; Pilonnage lourd des remparts x4</div>
                                </div>
                                <input type="number" id="att-catapult" class="form-control form-control-sm text-end font-monospace fw-bold text-danger" min="0" value="2" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé Statistique Attaquant -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                    <span class="small text-muted">Puissance Offensive Brute :</span>
                    <span class="fw-bold text-danger font-monospace fs-5" id="att-raw-power">0</span>
                </div>

            </div>
        </div>
    </div>

    <!-- ── COLONNE DROITE : DÉFENSEUR & MURAILLE DU VILLAGE ── -->
    <div class="col-12 col-xl-6">
        <div class="card h-100 shadow-sm border-0" style="border-top: 3px solid #059669 !important;">
            <div class="card-header bg-success-lt py-2 px-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-success m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chess-rook text-success"></i>
                    <span>Village Cible &amp; Garnison Détenue</span>
                </h3>
                <span class="badge bg-success text-white" id="badge-def-power">Défense : 0</span>
            </div>
            <div class="card-body p-3">

                <!-- 1. Faction & Fief Défendu -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Clan Défenseur</label>
                        <select id="def-clan" class="form-select form-select-sm" onchange="updateWallMetrics()">
                            <option value="aethelis" selected>Clan Tokugawa (+5%/niv &amp; +15% Bouclier)</option>
                            <option value="terran">Clan Oda (+4%/niv Muraille)</option>
                            <option value="vorash">Clan Takeda (+3.5%/niv Muraille)</option>
                            <option value="neutral">Fief Neutre / Indépendant</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Nom du Bourg / Cité Castrale</label>
                        <input type="text" id="def-village-name" class="form-control form-select-sm" value="Château de Sunpu (Bastion)">
                    </div>
                </div>

                <!-- 2. ⭐ PARAMÈTRE CRITIQUE : NIVEAU DE LA MURAILLE DU VILLAGE -->
                <div class="card mb-3 border-success-subtle shadow-xs" style="background: #f0fdf4; border: 1px solid #86efac;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-xs bg-success text-white rounded"><i class="fa-solid fa-shield-halved"></i></span>
                                <span class="fw-bold text-dark">Muraille &amp; Remparts de Cité</span>
                            </div>
                            <span class="badge bg-success font-monospace px-2 py-1 fs-6" id="wall-level-badge">Niveau 8</span>
                        </div>

                        <!-- Slider interactif 0 à 20 -->
                        <div class="mb-3">
                            <input type="range" class="form-range" min="0" max="20" step="1" value="8" id="def-wall-level" oninput="updateWallMetrics()">
                            <div class="d-flex justify-content-between small text-muted font-monospace" style="font-size: 0.72rem;">
                                <span>Niv 0 (Village ouvert)</span>
                                <span>Niv 5</span>
                                <span>Niv 10</span>
                                <span>Niv 15</span>
                                <span>Niv 20 (Forteresse)</span>
                            </div>
                        </div>

                        <!-- Indicateurs Dynamiques des Propriétés de la Muraille -->
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 bg-white rounded border">
                                    <div class="text-secondary small" style="font-size: 0.7rem;">Bonus Défensif</div>
                                    <div class="fw-bold text-success font-monospace" id="wall-defense-bonus">+40%</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-white rounded border">
                                    <div class="text-secondary small" style="font-size: 0.7rem;">PV Structure</div>
                                    <div class="fw-bold text-primary font-monospace" id="wall-structural-hp">2,000 PV</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-white rounded border">
                                    <div class="text-secondary small" style="font-size: 0.7rem;">Meurtrières</div>
                                    <div class="fw-bold text-danger font-monospace" id="wall-riposte-power">120 pts/rd</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 3. Composition de la Garnison du Village -->
                <div class="border rounded p-2 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-users-viewfinder me-1"></i>Garnison Féodale en Défense
                        </span>
                        <span class="small text-muted" id="def-troops-count">0 défenseurs</span>
                    </div>

                    <div class="row g-2">
                        <!-- Sentinelles Yari -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-shield text-secondary"></i> Sentinelles Yari
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 42 &bull; Def: 63</div>
                                </div>
                                <input type="number" id="def-sentinel" class="form-control form-control-sm text-end font-monospace" min="0" value="120" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Archers Protecteurs de Muraille -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border" style="background: #f0fdf4 !important;">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1 text-success">
                                        <i class="fa-solid fa-bullseye"></i> Archers Protecteurs
                                    </div>
                                    <div class="text-success" style="font-size: 0.7rem;">Att: 70 &bull; Def: 72 (+Mur)</div>
                                </div>
                                <input type="number" id="def-archer" class="form-control form-control-sm text-end font-monospace fw-bold text-success" min="0" value="80" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Samouraïs en Garnison -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-khanda text-purple"></i> Samouraïs de Fief
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 85 &bull; Def: 75</div>
                                </div>
                                <input type="number" id="def-samurai" class="form-control form-control-sm text-end font-monospace" min="0" value="40" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>

                        <!-- Hatamotos Vénérables -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-white rounded border">
                                <div>
                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-chess-rook text-dark"></i> Hatamotos Gardiens
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Att: 200 &bull; Def: 300</div>
                                </div>
                                <input type="number" id="def-hatamoto" class="form-control form-control-sm text-end font-monospace" min="0" value="15" style="width: 75px;" oninput="calculateArmyTotals()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé Statistique Défenseur -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-white border">
                    <span class="small text-muted">Défense Effective Finale (avec Muraille) :</span>
                    <span class="fw-bold text-success font-monospace fs-5" id="def-effective-power">0</span>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     BARRE D'ACTION CENTRALE : DÉCLENCHEMENT DE LA SIMULATION
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="card mb-4 border-0 shadow-sm text-center py-2" style="background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);">
    <div class="card-body py-2">
        <button type="button" class="btn btn-danger btn-lg px-5 shadow-sm fw-bold fs-4 d-inline-flex align-items-center gap-2" onclick="runTacticalSimulation()">
            <i class="fa-solid fa-swords"></i>
            <span>Lancer la Simulation Tactique Instantanée</span>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     RAPPORT DE COMBAT INSTANTANÉ (RÉSULTATS DE SIMULATION)
     ═══════════════════════════════════════════════════════════════════════ -->
<div id="sim-results-card" class="card mb-4 shadow-sm border-0 d-none">
    <div class="card-header bg-dark text-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar avatar-sm bg-danger text-white rounded"><i class="fa-solid fa-scroll"></i></span>
            <div>
                <h3 class="card-title text-white m-0">Rapport de Siège &amp; Simulation d'Équilibrage</h3>
                <div class="text-white-50 small" id="sim-timestamp">Généré à l'instant &bull; 3 Rounds Tactiques</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-light" onclick="copyMarkdownReport()">
                <i class="fa-solid fa-copy me-1"></i>Copier le Rapport Markdown
            </button>
            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="saveCombatTestToApi()">
                <i class="fa-solid fa-floppy-disk me-1"></i>Sauvegarder ce Test (+15 XP Forge)
            </button>
        </div>
    </div>

    <div class="card-body p-4">

        <!-- 1. Bannière de Verdict -->
        <div class="alert mb-4 shadow-xs" id="sim-verdict-alert" role="alert">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-1" id="sim-verdict-icon"><i class="fa-solid fa-trophy"></i></span>
                <div>
                    <h3 class="alert-title mb-1 fs-3 fw-bold" id="sim-verdict-title">Victoire de l'Attaquant</h3>
                    <div id="sim-verdict-desc" class="small">Description du dénouement martial.</div>
                </div>
            </div>
        </div>

        <!-- 2. Jauges d'Attrition et KPIs de Pertes -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                        <span><i class="fa-solid fa-flag text-danger me-1"></i>Attrition Attaquant :</span>
                        <strong id="att-loss-pct" class="text-danger font-monospace">0%</strong>
                    </div>
                    <div class="progress progress-sm mb-2" style="height: 10px;">
                        <div id="att-loss-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted font-monospace">
                        <span id="att-initial-total">0 engagés</span>
                        <span id="att-losses-total" class="text-danger fw-bold">-0 tués</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                        <span><i class="fa-solid fa-chess-rook text-success me-1"></i>Attrition Défenseur :</span>
                        <strong id="def-loss-pct" class="text-success font-monospace">0%</strong>
                    </div>
                    <div class="progress progress-sm mb-2" style="height: 10px;">
                        <div id="def-loss-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted font-monospace">
                        <span id="def-initial-total">0 défenseurs</span>
                        <span id="def-losses-total" class="text-danger fw-bold">-0 tués</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                        <span><i class="fa-solid fa-shield-halved text-warning me-1"></i>État des Remparts :</span>
                        <strong id="wall-status-badge" class="badge bg-success-lt text-success">Intacte</strong>
                    </div>
                    <div class="progress progress-sm mb-2" style="height: 10px;">
                        <div id="wall-hp-bar" class="progress-bar bg-warning" role="progressbar" style="width: 100%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted font-monospace">
                        <span id="wall-hp-text">2000 / 2000 PV</span>
                        <span id="wall-breach-text" class="text-success">Résiste</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tableau Bilan Régiment par Régiment -->
        <div class="card mb-4 border shadow-xs">
            <div class="card-header bg-light py-2 px-3">
                <h4 class="card-title m-0 small fw-bold text-uppercase text-secondary">
                    <i class="fa-solid fa-list-ol me-1"></i>Bilan Détaillé des Pertes et Survivants par Régiment
                </h4>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table">
                    <thead>
                        <tr class="text-muted small">
                            <th>Camp &bull; Régiment</th>
                            <th class="text-center">Engagés</th>
                            <th class="text-center text-danger">Pertes</th>
                            <th class="text-center text-success">Survivants</th>
                            <th class="text-end">Taux d'Attrition</th>
                        </tr>
                    </thead>
                    <tbody id="sim-regiments-table-body">
                        <!-- Rempli dynamiquement par JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Journal de Combat Détaillé (Combat Log Dépliable) -->
        <div class="accordion" id="accordion-combat-log">
            <div class="accordion-item border shadow-xs">
                <h2 class="accordion-header" id="heading-log">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-log" aria-expanded="false" aria-controls="collapse-log">
                        <i class="fa-solid fa-scroll text-warning me-2"></i>
                        <span>Journal de Combat Détaillé Tour par Tour (Combat Log)</span>
                    </button>
                </h2>
                <div id="collapse-log" class="accordion-collapse collapse" aria-labelledby="heading-log" data-bs-parent="#accordion-combat-log">
                    <div class="accordion-body p-3 font-monospace small bg-dark text-light" style="max-height: 380px; overflow-y: auto;" id="sim-combat-log-content">
                        <!-- Rempli dynamiquement par JS -->
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MOTEUR DE CALCUL JAVASCRIPT DU SIMULATEUR DE COMBAT
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
// Données globales des unités
const UNIT_STATS = {
    'att-ashigaru':    { name: 'Ashigarus Yari (Attaque)', side: 'att', attack: 40, defense: 55, isSiege: false },
    'att-arquebusier': { name: 'Arquebusiers Tanegashima', side: 'att', attack: 65, defense: 61, isSiege: false },
    'att-samurai':     { name: 'Samouraïs au Katana (Attaque)', side: 'att', attack: 85, defense: 75, isSiege: false },
    'att-cavalry':     { name: 'Cavalerie de Choc Akazonae', side: 'att', attack: 95, defense: 60, isSiege: false },
    'att-hatamoto':    { name: 'Garde Hatamoto Lourde', side: 'att', attack: 210, defense: 270, isSiege: false },
    'att-ram':         { name: 'Béliers Blindés de Siège', side: 'att', attack: 400, defense: 2650, isSiege: true, wallDmgMult: 3 },
    'att-catapult':    { name: 'Catapultes Ôzutsu & Tours', side: 'att', attack: 800, defense: 4500, isSiege: true, wallDmgMult: 4 },

    'def-sentinel':    { name: 'Sentinelles Yari (Défense)', side: 'def', attack: 42, defense: 63, isSiege: false },
    'def-archer':      { name: 'Archers Protecteurs de Muraille', side: 'def', attack: 70, defense: 72, isSiege: false },
    'def-samurai':     { name: 'Samouraïs de Garnison', side: 'def', attack: 85, defense: 75, isSiege: false },
    'def-hatamoto':    { name: 'Hatamotos Gardiens des Remparts', side: 'def', attack: 200, defense: 300, isSiege: false }
};

let lastSimulationResult = null;

// Mise à jour réactive des caractéristiques de la muraille
function updateWallMetrics() {
    const wallInput = document.getElementById('def-wall-level');
    const wallLvl = parseInt(wallInput ? wallInput.value : 0, 10) || 0;
    const defClan = document.getElementById('def-clan') ? document.getElementById('def-clan').value : 'aethelis';

    // Badge niveau
    const badgeLvl = document.getElementById('wall-level-badge');
    if (badgeLvl) badgeLvl.textContent = `Niveau ${wallLvl}`;

    // Coefficient multiplicateur par clan
    let multPerLvl = 0.04;
    let factionShieldBonus = 0;
    if (defClan === 'aethelis') { // Tokugawa
        multPerLvl = 0.05;
        factionShieldBonus = 15;
    } else if (defClan === 'vorash') { // Takeda
        multPerLvl = 0.035;
    }

    const totalDefensePct = Math.round((wallLvl * multPerLvl * 100) + factionShieldBonus);
    const structuralHp = wallLvl * 250;
    const riposteDmg = wallLvl * 15;

    const bonusEl = document.getElementById('wall-defense-bonus');
    if (bonusEl) bonusEl.textContent = `+${totalDefensePct}%`;

    const hpEl = document.getElementById('wall-structural-hp');
    if (hpEl) hpEl.textContent = `${structuralHp.toLocaleString()} PV`;

    const riposteEl = document.getElementById('wall-riposte-power');
    if (riposteEl) riposteEl.textContent = `${riposteDmg} pts/rd`;

    calculateArmyTotals();
}

// Calcul des totaux bruts et affichage des compteurs
function calculateArmyTotals() {
    let attTotalTroops = 0;
    let attRawPower = 0;

    const doctrine = document.getElementById('att-doctrine') ? document.getElementById('att-doctrine').value : 'standard';
    let doctrineMult = 1.0;
    if (doctrine === 'furinkazan') doctrineMult = 1.15;
    if (doctrine === 'tanegashima') doctrineMult = 1.20;

    for (const [id, stats] of Object.entries(UNIT_STATS)) {
        if (stats.side === 'att') {
            const input = document.getElementById(id);
            const count = parseInt(input ? input.value : 0, 10) || 0;
            attTotalTroops += count;
            attRawPower += count * stats.attack;
        }
    }

    attRawPower = Math.round(attRawPower * doctrineMult);

    const attCountEl = document.getElementById('att-troops-count');
    if (attCountEl) attCountEl.textContent = `${attTotalTroops.toLocaleString()} soldats`;

    const attRawEl = document.getElementById('att-raw-power');
    if (attRawEl) attRawEl.textContent = attRawPower.toLocaleString();

    const badgeAtt = document.getElementById('badge-att-power');
    if (badgeAtt) badgeAtt.textContent = `Puissance : ${attRawPower.toLocaleString()}`;

    // Défenseur
    let defTotalTroops = 0;
    let defRawDefense = 0;

    for (const [id, stats] of Object.entries(UNIT_STATS)) {
        if (stats.side === 'def') {
            const input = document.getElementById(id);
            const count = parseInt(input ? input.value : 0, 10) || 0;
            defTotalTroops += count;
            defRawDefense += count * stats.defense;
        }
    }

    const wallInput = document.getElementById('def-wall-level');
    const wallLvl = parseInt(wallInput ? wallInput.value : 0, 10) || 0;
    const defClan = document.getElementById('def-clan') ? document.getElementById('def-clan').value : 'aethelis';

    let multPerLvl = 0.04;
    let factionShieldBonus = 0;
    if (defClan === 'aethelis') {
        multPerLvl = 0.05;
        factionShieldBonus = 15;
    } else if (defClan === 'vorash') {
        multPerLvl = 0.035;
    }

    const totalDefMultiplier = 1.0 + (wallLvl * multPerLvl) + (factionShieldBonus / 100);
    const defEffectiveDefense = Math.round(defRawDefense * totalDefMultiplier + (wallLvl * 250));

    const defCountEl = document.getElementById('def-troops-count');
    if (defCountEl) defCountEl.textContent = `${defTotalTroops.toLocaleString()} défenseurs`;

    const defEffEl = document.getElementById('def-effective-power');
    if (defEffEl) defEffEl.textContent = defEffectiveDefense.toLocaleString();

    const badgeDef = document.getElementById('badge-def-power');
    if (badgeDef) badgeDef.textContent = `Défense : ${defEffectiveDefense.toLocaleString()}`;
}

// Application des préréglages en 1 clic
function applyPreset(presetName) {
    if (presetName === 'open_village') {
        document.getElementById('def-wall-level').value = 0;
        document.getElementById('att-ashigaru').value = 80;
        document.getElementById('att-arquebusier').value = 30;
        document.getElementById('att-samurai').value = 20;
        document.getElementById('att-cavalry').value = 15;
        document.getElementById('att-hatamoto').value = 0;
        document.getElementById('att-ram').value = 0;
        document.getElementById('att-catapult').value = 0;

        document.getElementById('def-sentinel').value = 40;
        document.getElementById('def-archer').value = 20;
        document.getElementById('def-samurai').value = 10;
        document.getElementById('def-hatamoto').value = 0;
    } else if (presetName === 'fortified_town') {
        document.getElementById('def-wall-level').value = 8;
        document.getElementById('att-ashigaru').value = 250;
        document.getElementById('att-arquebusier').value = 120;
        document.getElementById('att-samurai').value = 80;
        document.getElementById('att-cavalry').value = 40;
        document.getElementById('att-hatamoto').value = 15;
        document.getElementById('att-ram').value = 8;
        document.getElementById('att-catapult').value = 3;

        document.getElementById('def-sentinel').value = 180;
        document.getElementById('def-archer').value = 110;
        document.getElementById('def-samurai').value = 60;
        document.getElementById('def-hatamoto').value = 20;
    } else if (presetName === 'imperial_fortress') {
        document.getElementById('def-wall-level').value = 20;
        document.getElementById('att-ashigaru').value = 800;
        document.getElementById('att-arquebusier').value = 400;
        document.getElementById('att-samurai').value = 250;
        document.getElementById('att-cavalry').value = 150;
        document.getElementById('att-hatamoto').value = 60;
        document.getElementById('att-ram').value = 25;
        document.getElementById('att-catapult').value = 12;

        document.getElementById('def-sentinel').value = 500;
        document.getElementById('def-archer').value = 350;
        document.getElementById('def-samurai').value = 200;
        document.getElementById('def-hatamoto').value = 80;
    }
    updateWallMetrics();
}

function resetSimulator() {
    applyPreset('fortified_town');
    const resCard = document.getElementById('sim-results-card');
    if (resCard) resCard.classList.add('d-none');
}

// ── MOTEUR D'AFFRONTEMENT & SIMULATION MULTI-ROUNDS ──────────────────────────
function runTacticalSimulation() {
    const wallLvlInput = document.getElementById('def-wall-level');
    const initialWallLvl = parseInt(wallLvlInput ? wallLvlInput.value : 0, 10) || 0;
    let currentWallLvl = initialWallLvl;
    const initialWallHp = initialWallLvl * 250;
    let currentWallHp = initialWallHp;

    const defClan = document.getElementById('def-clan') ? document.getElementById('def-clan').value : 'aethelis';
    const attClan = document.getElementById('att-clan') ? document.getElementById('att-clan').value : 'terran';
    const doctrine = document.getElementById('att-doctrine') ? document.getElementById('att-doctrine').value : 'standard';

    let multPerLvl = (defClan === 'aethelis') ? 0.05 : ((defClan === 'vorash') ? 0.035 : 0.04);
    let factionShieldBonus = (defClan === 'aethelis') ? 15 : 0;

    // Récupération des effectifs initiaux
    const unitsData = {};
    let attInitialCount = 0;
    let defInitialCount = 0;

    for (const [id, stats] of Object.entries(UNIT_STATS)) {
        const input = document.getElementById(id);
        const count = Math.max(0, parseInt(input ? input.value : 0, 10) || 0);
        unitsData[id] = {
            id: id,
            name: stats.name,
            side: stats.side,
            attack: stats.attack,
            defense: stats.defense,
            isSiege: stats.isSiege,
            wallDmgMult: stats.wallDmgMult || 1,
            initialCount: count,
            currentCount: count,
            losses: 0
        };
        if (stats.side === 'att') attInitialCount += count;
        if (stats.side === 'def') defInitialCount += count;
    }

    if (attInitialCount === 0 && defInitialCount === 0) {
        alert("Veuillez saisir au moins quelques unités dans chaque camp pour lancer la simulation.");
        return;
    }

    const combatLog = [];
    combatLog.push(`[INIT] Affrontement engagé entre l'Armée (${attClan.toUpperCase()}) et le Fief (${defClan.toUpperCase()}).`);
    combatLog.push(`[INIT] Muraille initiale : Niveau ${initialWallLvl} (${initialWallHp.toLocaleString()} PV Structurels, Bonus Défensif +${Math.round((initialWallLvl * multPerLvl * 100) + factionShieldBonus)}%).`);

    // ── ROUND 1 : PHASE DE SIÈGE & ARCHERIE ──────────────────────────────────
    combatLog.push(`\n═════ ROUND 1 : PHASE DE SIÈGE & VOLÉE DE TRAITS ═════`);
    
    // 1. Pilonnage de la muraille par les engins de siège
    const rams = unitsData['att-ram'].currentCount;
    const catapults = unitsData['att-catapult'].currentCount;
    let siegeWallDmg = (rams * 400 * 3) + (catapults * 800 * 4);
    if (doctrine === 'siege_master') siegeWallDmg = Math.round(siegeWallDmg * 1.25);

    if (currentWallHp > 0 && siegeWallDmg > 0) {
        currentWallHp = Math.max(0, currentWallHp - siegeWallDmg);
        combatLog.push(`[SIÈGE] ${rams} Béliers et ${catapults} Catapultes pilonnent les remparts et infligent ${siegeWallDmg.toLocaleString()} dégâts structurels.`);
        if (currentWallHp === 0) {
            currentWallLvl = 0;
            combatLog.push(`💥 [BRÈCHE CRITIQUE] Les remparts s'effondrent sous les coups de boutoir ! La muraille est percée !`);
        } else {
            currentWallLvl = Math.ceil(currentWallHp / 250);
            combatLog.push(`[MURAILLE] Les remparts tiennent bon mais sont fissurés : ${currentWallHp.toLocaleString()} PV restants (Équivalent Niv ${currentWallLvl}).`);
        }
    } else if (currentWallHp === 0) {
        combatLog.push(`[MURAILLE] Aucune fortification ne protège le village. Les troupes s'élancent à découvert.`);
    }

    // 2. Tirs des archers défenseurs et riposte des meurtrières
    const riposteDmg = currentWallLvl * 15;
    if (riposteDmg > 0 && attInitialCount > 0) {
        const lossFromRiposte = Math.min(unitsData['att-ashigaru'].currentCount, Math.ceil(riposteDmg / 45));
        unitsData['att-ashigaru'].currentCount -= lossFromRiposte;
        unitsData['att-ashigaru'].losses += lossFromRiposte;
        combatLog.push(`🏹 [MEURTRIÈRES] Les archers postés sur les créneaux et les meurtrières de courtine abattent ${lossFromRiposte} piquiers de tête.`);
    }

    // ── ROUND 2 : CHOC DES LIGNES & SALVES DE MOUSQUETS ──────────────────────
    combatLog.push(`\n═════ ROUND 2 : CHOC DES LIGNES & SALVES TANEGASHIMA ═════`);

    let currentWallDefBonus = 1.0 + (currentWallLvl * multPerLvl) + (factionShieldBonus / 100);

    let attP2 = (unitsData['att-ashigaru'].currentCount * 40) +
                (unitsData['att-arquebusier'].currentCount * 65 * 1.3) +
                (unitsData['att-samurai'].currentCount * 85) +
                (unitsData['att-cavalry'].currentCount * 95) +
                (unitsData['att-hatamoto'].currentCount * 210);

    if (doctrine === 'furinkazan') attP2 = Math.round(attP2 * 1.15);
    if (doctrine === 'tanegashima') attP2 = Math.round(attP2 * 1.20);

    let defP2 = (unitsData['def-sentinel'].currentCount * 63) +
                (unitsData['def-archer'].currentCount * 72) +
                (unitsData['def-samurai'].currentCount * 75) +
                (unitsData['def-hatamoto'].currentCount * 300);

    defP2 = Math.round(defP2 * currentWallDefBonus);

    combatLog.push(`[ENGAGEMENT] Puissance d'assaut attaquant : ${attP2.toLocaleString()} | Capacité défensive du village : ${defP2.toLocaleString()}.`);

    // Calcul des pertes réciproques au round 2
    let attLossRatio = Math.min(0.75, defP2 / (attP2 + defP2 + 1));
    let defLossRatio = Math.min(0.75, attP2 / (attP2 + defP2 + 1));

    // Si muraille encore debout, la défense subit 35% de pertes en moins
    if (currentWallLvl > 5) {
        defLossRatio *= 0.65;
        combatLog.push(`🛡️ [PROTECTION DES REMPARTS] La muraille absorbe l'onde de choc et réduit les pertes de la garnison.`);
    }

    applyLossesToSide(unitsData, 'att', attLossRatio, combatLog);
    applyLossesToSide(unitsData, 'def', defLossRatio, combatLog);

    // ── ROUND 3 : MÊLÉE GÉNÉRALE AU KATANA & PRISE DU DONJON ─────────────────
    combatLog.push(`\n═════ ROUND 3 : MÊLÉE DÉCISIVE & ASSAUT DU TENSHU ═════`);

    let attP3 = calculateRemainingPower(unitsData, 'att');
    let defP3 = calculateRemainingPower(unitsData, 'def') * (1.0 + (currentWallLvl * 0.02));

    if (attP3 > defP3 * 1.5) {
        // Balayage attaquant
        applyLossesToSide(unitsData, 'def', 0.85, combatLog);
        applyLossesToSide(unitsData, 'att', 0.10, combatLog);
        combatLog.push(`⚔️ [PERCÉE DÉCISIVE] L'armée attaquante submerge le donjon et anéantit la résistance.`);
    } else if (defP3 > attP3 * 1.5) {
        // Échec du siège
        applyLossesToSide(unitsData, 'att', 0.70, combatLog);
        applyLossesToSide(unitsData, 'def', 0.15, combatLog);
        combatLog.push(`🛡️ [REPLI FORCÉ] L'armée attaquante est décimée au pied des remparts et bat en retraite.`);
    } else {
        // Combat équilibré féroce
        applyLossesToSide(unitsData, 'att', 0.40, combatLog);
        applyLossesToSide(unitsData, 'def', 0.45, combatLog);
        combatLog.push(`⚔️ [LUTTE ACHARNÉE] Les corps-à-corps font rage dans les ruelles du bourg fortifié.`);
    }

    // ── BILAN FINAL & VERDICT ────────────────────────────────────────────────
    let attFinalRemaining = 0;
    let attTotalLosses = 0;
    let defFinalRemaining = 0;
    let defTotalLosses = 0;

    for (const u of Object.values(unitsData)) {
        if (u.side === 'att') {
            attFinalRemaining += u.currentCount;
            attTotalLosses += u.losses;
        } else {
            defFinalRemaining += u.currentCount;
            defTotalLosses += u.losses;
        }
    }

    const attLossPct = attInitialCount > 0 ? Math.round((attTotalLosses / attInitialCount) * 100) : 0;
    const defLossPct = defInitialCount > 0 ? Math.round((defTotalLosses / defInitialCount) * 100) : 0;

    let verdict = {
        title: '',
        desc: '',
        colorClass: '',
        icon: '',
        isAttackerWin: false
    };

    if (defFinalRemaining === 0 || defLossPct >= 90) {
        if (attLossPct <= 35) {
            verdict.title = "Triomphe Écrasant de l'Attaquant";
            verdict.desc = "Les remparts ont été enfoncés et la garnison entièrement décimée avec des pertes minimes pour l'attaquant.";
            verdict.colorClass = "alert-success";
            verdict.icon = '<i class="fa-solid fa-trophy text-success"></i>';
            verdict.isAttackerWin = true;
        } else {
            verdict.title = "Victoire à la Pyrrhus de l'Attaquant";
            verdict.desc = "Le bourg fortifié a été pris, mais au prix d'une hécatombe majeure parmi les troupes d'assaut.";
            verdict.colorClass = "alert-warning";
            verdict.icon = '<i class="fa-solid fa-fire text-warning"></i>';
            verdict.isAttackerWin = true;
        }
    } else if (attFinalRemaining === 0 || attLossPct >= 75) {
        verdict.title = "Échec du Siège & Bérézina de l'Attaquant";
        verdict.desc = "Les fortifications ont tenu ! L'armée d'invasion est anéantie ou forcée de se débander en catastrophe.";
        verdict.colorClass = "alert-danger";
        verdict.icon = '<i class="fa-solid fa-skull text-danger"></i>';
        verdict.isAttackerWin = false;
    } else {
        if (attFinalRemaining > defFinalRemaining) {
            verdict.title = "Avantage Tactique Attaquant (Siège en cours)";
            verdict.desc = "La garnison est retranchée dans le dernier donjon. La muraille a subi de lourdes avaries.";
            verdict.colorClass = "alert-info";
            verdict.icon = '<i class="fa-solid fa-chess-rook text-info"></i>';
            verdict.isAttackerWin = true;
        } else {
            verdict.title = "Triomphe Défensif des Remparts";
            verdict.desc = "La muraille féodale a brisé l'élan de l'offensive. La garnison repousse l'envahisseur avec bravoure.";
            verdict.colorClass = "alert-primary";
            verdict.icon = '<i class="fa-solid fa-shield-halved text-primary"></i>';
            verdict.isAttackerWin = false;
        }
    }

    combatLog.push(`\n[VERDICT] ${verdict.title} : Pertes Attaquant ${attLossPct}% | Pertes Défenseur ${defLossPct}% | Muraille finale : ${currentWallHp.toLocaleString()} PV.`);

    // Sauvegarde en mémoire du dernier résultat pour Markdown et API
    lastSimulationResult = {
        outcomeTitle: verdict.title,
        outcomeDesc: verdict.desc,
        initialWallLvl: initialWallLvl,
        currentWallLvl: currentWallLvl,
        initialWallHp: initialWallHp,
        currentWallHp: currentWallHp,
        attInitial: attInitialCount,
        attLosses: attTotalLosses,
        attLossPct: attLossPct,
        defInitial: defInitialCount,
        defLosses: defTotalLosses,
        defLossPct: defLossPct,
        unitsData: unitsData,
        combatLog: combatLog.join('\n')
    };

    // Rendu dans l'interface
    renderSimulationResults(lastSimulationResult, verdict);
}

// Répartition proportionnelle des pertes
function applyLossesToSide(unitsData, side, lossRatio, combatLog) {
    for (const u of Object.values(unitsData)) {
        if (u.side === side && u.currentCount > 0) {
            let casualties = Math.round(u.currentCount * lossRatio);
            if (u.isSiege) casualties = Math.round(casualties * 0.4); // Les engins de siège restent en retrait
            casualties = Math.min(u.currentCount, casualties);
            u.currentCount -= casualties;
            u.losses += casualties;
        }
    }
}

function calculateRemainingPower(unitsData, side) {
    let p = 0;
    for (const u of Object.values(unitsData)) {
        if (u.side === side) {
            p += (u.currentCount * u.attack);
        }
    }
    return p;
}

// Rendu graphique du rapport
function renderSimulationResults(data, verdict) {
    const card = document.getElementById('sim-results-card');
    if (!card) return;
    card.classList.remove('d-none');

    // Bannière
    const alertBox = document.getElementById('sim-verdict-alert');
    alertBox.className = `alert mb-4 shadow-xs ${verdict.colorClass}`;
    document.getElementById('sim-verdict-title').textContent = verdict.title;
    document.getElementById('sim-verdict-desc').textContent = verdict.desc;
    document.getElementById('sim-verdict-icon').innerHTML = verdict.icon;

    // Horodatage
    const now = new Date();
    document.getElementById('sim-timestamp').textContent = `Simulé à ${now.toLocaleTimeString()} &bull; 3 Rounds Tactiques`;

    // Jauges
    document.getElementById('att-loss-pct').textContent = `${data.attLossPct}%`;
    document.getElementById('att-loss-bar').style.width = `${data.attLossPct}%`;
    document.getElementById('att-initial-total').textContent = `${data.attInitial.toLocaleString()} engagés`;
    document.getElementById('att-losses-total').textContent = `-${data.attLosses.toLocaleString()} tués`;

    document.getElementById('def-loss-pct').textContent = `${data.defLossPct}%`;
    document.getElementById('def-loss-bar').style.width = `${data.defLossPct}%`;
    document.getElementById('def-initial-total').textContent = `${data.defInitial.toLocaleString()} défenseurs`;
    document.getElementById('def-losses-total').textContent = `-${data.defLosses.toLocaleString()} tués`;

    // Muraille
    const wallHpPct = data.initialWallHp > 0 ? Math.round((data.currentWallHp / data.initialWallHp) * 100) : 0;
    document.getElementById('wall-hp-bar').style.width = `${wallHpPct}%`;
    document.getElementById('wall-hp-text').textContent = `${data.currentWallHp.toLocaleString()} / ${data.initialWallHp.toLocaleString()} PV`;

    const statusBadge = document.getElementById('wall-status-badge');
    const breachText = document.getElementById('wall-breach-text');

    if (data.initialWallHp === 0) {
        statusBadge.className = "badge bg-secondary-lt text-secondary";
        statusBadge.textContent = "Aucune (Mur 0)";
        breachText.textContent = "Terrain ouvert";
        breachText.className = "text-secondary";
    } else if (data.currentWallHp === 0) {
        statusBadge.className = "badge bg-danger-lt text-danger";
        statusBadge.textContent = "Brèche Totale";
        breachText.textContent = "Effondrée !";
        breachText.className = "text-danger fw-bold";
    } else if (wallHpPct < 50) {
        statusBadge.className = "badge bg-warning-lt text-warning";
        statusBadge.textContent = "Avariée";
        breachText.textContent = "Fissures majeures";
        breachText.className = "text-warning fw-bold";
    } else {
        statusBadge.className = "badge bg-success-lt text-success";
        statusBadge.textContent = "Intacte";
        breachText.textContent = "Tient bon";
        breachText.className = "text-success";
    }

    // Tableau régiments
    const tbody = document.getElementById('sim-regiments-table-body');
    tbody.innerHTML = '';

    for (const u of Object.values(data.unitsData)) {
        if (u.initialCount === 0) continue;
        const lossRate = Math.round((u.losses / u.initialCount) * 100);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="badge ${u.side === 'att' ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success'} me-1">${u.side === 'att' ? 'Attaque' : 'Défense'}</span>
                <strong>${u.name}</strong>
            </td>
            <td class="text-center font-monospace">${u.initialCount.toLocaleString()}</td>
            <td class="text-center font-monospace text-danger fw-bold">-${u.losses.toLocaleString()}</td>
            <td class="text-center font-monospace text-success fw-bold">${u.currentCount.toLocaleString()}</td>
            <td class="text-end font-monospace">
                <span class="badge ${lossRate > 50 ? 'bg-danger-lt text-danger' : (lossRate > 0 ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success')}">${lossRate}%</span>
            </td>
        `;
        tbody.appendChild(tr);
    }

    // Log détaillé
    document.getElementById('sim-combat-log-content').textContent = data.combatLog;

    // Défilement doux vers le rapport
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Copie du rapport au format Markdown
function copyMarkdownReport() {
    if (!lastSimulationResult) return;
    const r = lastSimulationResult;

    let md = `### 🏯 Rapport de Simulation Martiale — OpenShogun\n`;
    md += `**Verdict :** ${r.outcomeTitle}\n`;
    md += `**Contexte :** ${r.outcomeDesc}\n\n`;
    md += `| Paramètre | Attaquant | Défenseur / Muraille |\n`;
    md += `|---|---|---|\n`;
    md += `| **Effectifs Engagés** | ${r.attInitial.toLocaleString()} | ${r.defInitial.toLocaleString()} |\n`;
    md += `| **Pertes Humaines** | -${r.attLosses.toLocaleString()} (${r.attLossPct}%) | -${r.defLosses.toLocaleString()} (${r.defLossPct}%) |\n`;
    md += `| **Muraille Cible** | — | Niv ${r.initialWallLvl} ➔ Niv ${r.currentWallLvl} (${r.currentWallHp.toLocaleString()} / ${r.initialWallHp.toLocaleString()} PV) |\n\n`;
    md += `#### Détail des Régiments\n`;
    md += `| Camp | Unité | Engagés | Pertes | Survivants |\n`;
    md += `|---|---|---|---|---|\n`;
    for (const u of Object.values(r.unitsData)) {
        if (u.initialCount > 0) {
            md += `| ${u.side === 'att' ? 'Attaque' : 'Défense'} | ${u.name} | ${u.initialCount} | -${u.losses} | ${u.currentCount} |\n`;
        }
    }

    navigator.clipboard.writeText(md).then(() => {
        showDevAlert("Rapport Markdown copié dans votre presse-papier avec succès !", "success");
    }).catch(err => {
        showDevAlert("Erreur lors de la copie presse-papier : " + err, "danger");
    });
}

// Enregistrement via l'API REST Dev Team
function saveCombatTestToApi() {
    if (!lastSimulationResult) return;

    const formData = new FormData();
    formData.append('action', 'save_combat_test');
    formData.append('test_data', JSON.stringify(lastSimulationResult));

    fetch('/api/dev_team.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showDevAlert(`✅ ${data.message} (+${data.xp_awarded} XP Forge)`, "success");
        } else {
            showDevAlert(`⛔ Erreur : ${data.error || 'Impossible d\'enregistrer le test.'}`, "danger");
        }
    })
    .catch(err => {
        showDevAlert(`Erreur réseau lors de la sauvegarde : ${err}`, "danger");
    });
}

function showDevAlert(message, type) {
    const alertBox = document.getElementById('dev-alert');
    const content = document.getElementById('dev-alert-content');
    if (alertBox && content) {
        alertBox.className = `alert alert-${type} mb-3 shadow-sm alert-dismissible`;
        content.textContent = message;
        alertBox.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        alert(message);
    }
}

// Initialisation au chargement immédiat et réactif
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    updateWallMetrics();
} else {
    document.addEventListener('DOMContentLoaded', () => {
        updateWallMetrics();
    });
}
</script>

