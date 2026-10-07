<?php
/**
 * Module Studio Dev : Simulateur de Vie dans un Village & Équilibrage Démographique
 * Métier : Game Elevate Designer
 * Emplacement : views/studio/game-elevate-designer/village-life-simulator.php
 */

require_once __DIR__ . '/../../../core/AuthManager.php';
require_once __DIR__ . '/../../../core/PopulationEngine.php';

// Contrôle strict côté serveur du métier Game Elevate Designer
if (!AuthManager::hasJob('game-elevate-designer')) {
    http_response_code(403);
    ?>
    <div class="card border-danger shadow-sm my-4">
        <div class="card-body text-center py-5">
            <div class="empty-icon text-danger fs-1 mb-3"><i class="fa-solid fa-lock"></i></div>
            <h2 class="text-danger fw-bold">Accès Restreint (Code HTTP 403)</h2>
            <p class="text-muted fs-4">
                Ce simulateur d'équilibrage démographique et de bac à sable est strictement réservé au métier <strong>Game Elevate Designer</strong>.
            </p>
            <div class="mt-4">
                <a href="/?page=dev_team" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour au Studio Dev
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}
?>

<!-- ── EN-TÊTE DU MODULE : SIMULATEUR DE VIE DANS UN VILLAGE ── -->
<div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid var(--tblr-success) !important;">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-green-lt text-green font-game px-2 py-1">
                        <i class="fa-solid fa-compass-drafting me-1"></i>Game Elevate Designer
                    </span>
                    <span class="badge bg-primary-lt text-primary">
                        <i class="fa-solid fa-people-roof me-1"></i>Bac à Sable Démographique
                    </span>
                    <span class="badge bg-purple-lt text-purple">
                        <i class="fa-solid fa-wine-bottle me-1"></i>Règle Asymétrique du Saké
                    </span>
                    <span class="badge bg-danger-lt text-danger">
                        <i class="fa-solid fa-shield-halved me-1"></i>Chômage &amp; Délinquance Féodale
                    </span>
                    <span class="badge bg-warning-lt text-dark fw-bold">
                        ⚡ Horloge Accélérée x1 à x100
                    </span>
                </div>
                <h2 class="h3 mb-0 text-dark fw-bold d-flex align-items-center gap-2">
                    <span>Simulateur de Vie dans un Village &amp; Équilibrage des Flux</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Ajustez en temps réel les flux entrants de vivres, de saké et de sérénité. Observez dynamiquement l'évolution du contentement, l'oisiveté et la délinquance, l'occupation des ateliers, la croissance démographique et le seuil critique d'exode féodal.
                </div>
            </div>

            <!-- Profils Prédéfinis (Presets Rapides) -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-secondary small fw-bold d-none d-md-inline">Scénarios :</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyVillagePreset('famine')" title="Pénurie sévère de vivres et effondrement moral">
                    <i class="fa-solid fa-skull me-1"></i>Pénurie Critique
                </button>
                <button type="button" class="btn btn-sm btn-outline-purple text-purple" onclick="applyVillagePreset('sake_prosperity')" title="Abondance de saké et fête continue">
                    <i class="fa-solid fa-wine-bottle me-1"></i>Prospérité sous Saké
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyVillagePreset('overcrowded')" title="Surpopulation avec déficit de postes de travail">
                    <i class="fa-solid fa-users-slash me-1"></i>Surpopulation Sans Emplois
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyVillagePreset('delinquency_crisis')" title="Explosion du chômage et criminalité sans garnison">
                    <i class="fa-solid fa-mask me-1"></i>Crise de Délinquance
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="applyVillagePreset('perfect_balance')" title="Harmonie idéale production, consommation et expansion">
                    <i class="fa-solid fa-scale-balanced me-1"></i>Équilibre Parfait
                </button>
                <button type="button" class="btn btn-sm btn-light text-muted" onclick="resetVillageSim()" title="Réinitialiser le simulateur">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles immersifs du simulateur de vie de village */
.village-kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.village-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.time-btn.active {
    background-color: var(--tblr-primary) !important;
    color: #fff !important;
    font-weight: bold;
}
.live-log-item {
    padding: 0.35rem 0.6rem;
    font-size: 0.78rem;
    border-left: 3px solid transparent;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}
.live-log-item.event-famine {
    border-left-color: #ef4444;
    background: #fef2f2;
}
.live-log-item.event-sake {
    border-left-color: #a855f7;
    background: #faf5ff;
}
.live-log-item.event-growth {
    border-left-color: #22c55e;
    background: #f0fdf4;
}
.live-log-item.event-exodus {
    border-left-color: #dc2626;
    background: #fee2e2;
}
.live-log-item.event-jobs {
    border-left-color: #f59e0b;
    background: #fffbeb;
}
.live-log-item.event-delinquency {
    border-left-color: #e11d48;
    background: #fff1f2;
}
.live-log-item.event-info {
    border-left-color: #0ea5e9;
    background: #f0f9ff;
}
@keyframes simPulseExodus {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.02); }
    100% { opacity: 1; transform: scale(1); }
}
.sim-blinking-exodus {
    animation: simPulseExodus 1.2s infinite ease-in-out;
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     LAYOUT 2 COLONNES TABLER.IO : PARAMÉTRAGE VS MONITEUR TEMPS RÉEL
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- ── COLONNE GAUCHE (5/12) : CONTRÔLE DU TEMPS & PARAMÈTRES ── -->
    <div class="col-12 col-xl-5">

        <!-- 1. Horloge Temporelle & Commandes de Simulation -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #0284c7 !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock text-info"></i>
                    <span>Horloge &amp; Vitesse Temporelle</span>
                </div>
                <div class="badge bg-blue-lt font-monospace px-2 py-1 fs-5" id="sim-clock-display">
                    Jour 1 &bull; 00:00
                </div>
            </div>
            <div class="card-body p-3">
                <!-- Boutons d'Action Principaux -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <button type="button" class="btn btn-success fw-bold px-3" id="btn-sim-play" onclick="startVillageSim()">
                        <i class="fa-solid fa-play me-1"></i>Lancer
                    </button>
                    <button type="button" class="btn btn-warning fw-bold px-3 d-none" id="btn-sim-pause" onclick="pauseVillageSim()">
                        <i class="fa-solid fa-pause me-1"></i>Pause
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="stepVillageSim(1)" title="Avancer de 1 heure de cycle féodal">
                        <i class="fa-solid fa-forward-step me-1"></i>+1h
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="stepVillageSim(24)" title="Avancer d'une journée complète (24h)">
                        <i class="fa-solid fa-forward me-1"></i>+24h
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-auto" onclick="resetVillageSim()" title="Réinitialiser à l'état initial">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

                <!-- Sélecteur d'Échelle Temporelle (x1 à x100) -->
                <div>
                    <label class="form-label small fw-bold text-secondary mb-1 d-flex justify-content-between">
                        <span>Échelle Temporelle (Facteur d'accélération) :</span>
                        <span class="text-primary fw-bold font-monospace" id="speed-indicator-label">Vitesse x10 (1h / sec)</span>
                    </label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn" data-speed="1" onclick="setVillageSpeed(1)">x1</button>
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn" data-speed="5" onclick="setVillageSpeed(5)">x5</button>
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn active" data-speed="10" onclick="setVillageSpeed(10)">x10</button>
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn" data-speed="25" onclick="setVillageSpeed(25)">x25</button>
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn" data-speed="50" onclick="setVillageSpeed(50)">x50</button>
                        <button type="button" class="btn btn-sm btn-outline-primary time-btn" data-speed="100" onclick="setVillageSpeed(100)">x100</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Paramètres Démographiques de Départ -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #10b981 !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-users text-success"></i>
                    <span>Démographie &amp; Infrastructure Initiale</span>
                </div>
                <span class="badge bg-secondary-lt font-monospace" id="demography-badge-total">Pop: 100</span>
            </div>
            <div class="card-body p-3">
                <!-- 1. Population Initiale -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-initial-pop">
                            <i class="fa-solid fa-person text-primary me-1"></i>Population Initiale :
                        </label>
                        <span class="badge bg-primary font-monospace" id="val-initial-pop">100 habitants</span>
                    </div>
                    <input type="range" class="form-range" id="range-initial-pop" min="10" max="600" step="5" value="100" oninput="syncInput('initial-pop', this.value)">
                </div>

                <!-- 2. Capacité des Logements -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-housing-cap">
                            <i class="fa-solid fa-house-chimney text-success me-1"></i>Capacité des Logements (Parcelles d'Habitation) :
                        </label>
                        <span class="badge bg-success font-monospace" id="val-housing-cap">200 places</span>
                    </div>
                    <input type="range" class="form-range" id="range-housing-cap" min="50" max="800" step="10" value="200" oninput="syncInput('housing-cap', this.value)">
                </div>

                <!-- 3. Postes de Travail Ouverts -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-open-jobs">
                            <i class="fa-solid fa-briefcase text-warning me-1"></i>Postes de Travail Ouverts (Ateliers &amp; Parcelles) :
                        </label>
                        <span class="badge bg-warning text-dark font-monospace" id="val-open-jobs">85 postes</span>
                    </div>
                    <input type="range" class="form-range" id="range-open-jobs" min="10" max="600" step="5" value="85" oninput="syncInput('open-jobs', this.value)">
                </div>

                <!-- 4. Maintien de l'Ordre Féodal (Tenshu, Muraille, Dojo, Vigie) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-security">
                            <i class="fa-solid fa-shield-halved text-danger me-1"></i>Maintien de l'Ordre Féodal (Garnison &amp; Sécurité) :
                        </label>
                        <span class="badge bg-danger font-monospace" id="val-security">+10% d'ordre</span>
                    </div>
                    <input type="range" class="form-range" id="range-security" min="0" max="50" step="1" value="10" oninput="syncInput('security', this.value)">
                    <div class="text-secondary small">Présence armée (Tenshu +3%, Muraille +2%, Dojo +2%, Vigie +1%/lvl). Réprime la criminalité liée au chômage.</div>
                </div>
            </div>
        </div>

        <!-- 3. Flux Entrants de Ressources (Production Horaire /h) -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #f59e0b !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-arrow-trend-up text-warning"></i>
                    <span>Flux de Production Horaire (Entrées nettes /h)</span>
                </div>
                <span class="badge bg-warning-lt text-warning fw-bold">Alimentation &amp; Confort</span>
            </div>
            <div class="card-body p-3">
                <!-- 1. Production de Riz (Nourriture de base) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-prod-rice">
                            <i class="fa-solid fa-wheat-awn text-warning me-1"></i>Production de Riz Impérial :
                        </label>
                        <span class="badge bg-warning text-dark font-monospace" id="val-prod-rice">+60 koku / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-rice" min="0" max="300" step="5" value="60" oninput="syncInput('prod-rice', this.value)">
                    <div class="text-secondary small">Nourriture de base féodale. Consommée si la farine vient à manquer.</div>
                </div>

                <!-- 2. Production de Farine (Nourriture transformée en meunerie) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-prod-flour">
                            <i class="fa-solid fa-bowl-rice text-secondary me-1"></i>Production de Farine de Riz :
                        </label>
                        <span class="badge bg-secondary font-monospace" id="val-prod-flour">+25 sacs / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-flour" min="0" max="150" step="2" value="25" oninput="syncInput('prod-flour', this.value)">
                    <div class="text-secondary small">Aliment vital de subsistance (0.04 sac/habitant/heure). Prévient la famine.</div>
                </div>

                <!-- 3. Production de Saké (Bien de confort réjouissant - Règle Asymétrique) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-prod-sake">
                            <i class="fa-solid fa-wine-bottle text-purple me-1"></i>Production de Saké Impérial :
                        </label>
                        <span class="badge bg-purple font-monospace" id="val-prod-sake">+12 tonnelets / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-sake" min="0" max="80" step="1" value="12" oninput="syncInput('prod-sake', this.value)">
                    <div class="text-secondary small">
                        <strong>Règle Asymétrique :</strong> Si présent &gt; 0, confère <strong>+15% de bonus net</strong>. Si épuisé, n'inflige <strong>aucun malus</strong>.
                    </div>
                </div>

                <!-- 4. Sérénité Shinto Passive -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="inp-serenity">
                            <i class="fa-solid fa-torii-gate text-teal me-1"></i>Sérénité Shinto &amp; Ferveur :
                        </label>
                        <span class="badge bg-teal font-monospace" id="val-serenity">75 / 100</span>
                    </div>
                    <input type="range" class="form-range" id="range-serenity" min="0" max="100" step="5" value="75" oninput="syncInput('serenity', this.value)">
                    <div class="text-secondary small">Énergie et paix des kami. Si &lt; 50, induit une anxiété spirituelle (-20%).</div>
                </div>
            </div>
        </div>

        <!-- 4. Export & Sauvegarde du Scénario -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-secondary">
                    <i class="fa-solid fa-file-code me-1 text-primary"></i>Exporter les paramètres et la série chronologique sous format standard JSON.
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm fw-bold" onclick="exportVillageScenario()">
                    <i class="fa-solid fa-download me-1"></i>Exporter Scénario JSON
                </button>
            </div>
        </div>

    </div>

    <!-- ── COLONNE DROITE (7/12) : MONITEUR TEMPS RÉEL & VISUALISATION ── -->
    <div class="col-12 col-xl-7">

        <!-- 1. 5 Jauges KPI Temps Réel -->
        <div class="row g-2 mb-3">
            <!-- KPI 1 : Contentement Global -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" id="card-kpi-contentment" style="border-left-color: #22c55e !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Satisfaction</span>
                            <span id="kpi-sake-bonus-icon" class="badge bg-purple-lt text-purple py-0 px-1" title="Bonus Saké +15% Actif">🍶 +15%</span>
                        </div>
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="fs-1 fw-bold text-success" id="kpi-contentment-val">85%</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-contentment-status">Euphorique &amp; Prospère</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-success" id="kpi-contentment-bar" style="width: 85%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 2 : Population Vivante -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3 border-primary">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Population</span>
                            <i class="fa-solid fa-users text-primary"></i>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-primary font-monospace" id="kpi-pop-val">100</span>
                            <span class="text-muted small">/ <span id="kpi-housing-max">200</span></span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-housing-rate">50% Logements</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-primary" id="kpi-pop-bar" style="width: 50%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 3 : Main-d'Œuvre & Emplois -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3 border-warning">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Main-d'Œuvre</span>
                            <i class="fa-solid fa-briefcase text-warning"></i>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-warning font-monospace" id="kpi-workforce-val">85</span>
                            <span class="text-muted small">/ <span id="kpi-workforce-req">85</span></span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-workforce-status">100% Pourvu</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-warning" id="kpi-workforce-bar" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4 : Délinquance & Sécurité Féodale -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" id="card-kpi-delinquency" style="border-left-color: #22c55e !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Délinquance</span>
                            <span id="kpi-delinquency-badge" class="badge bg-success-lt text-success py-0 px-1" title="Maintien de l'Ordre Féodal">
                                <i class="fa-solid fa-shield-halved me-1"></i>Ordre
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-success font-monospace" id="kpi-delinquency-val">0%</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-delinquency-status">Ordre Parfait</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-success" id="kpi-delinquency-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 5 : Stocks en Grenier (Vivres & Saké) -->
            <div class="col-6 col-sm-4 col-xl">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3 border-teal">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Grenier Féodal</span>
                            <i class="fa-solid fa-wheat-awn text-teal"></i>
                        </div>
                        <div class="small font-monospace" style="line-height: 1.35;">
                            <div>🌾 Riz : <strong id="kpi-stock-rice">300</strong></div>
                            <div>🍚 Farine : <strong id="kpi-stock-flour">120</strong></div>
                            <div>🍶 Saké : <strong id="kpi-stock-sake">50</strong></div>
                        </div>
                        <div class="text-muted small mt-1" id="kpi-food-status" style="font-size:0.68rem;">Rations assurées</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Graphique Dynamique Multi-Séries (ApexCharts avec Fallback Canvas) -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    <span>Évolution Temporelle Dynamique (Population, Vivres, Saké, Contentement)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-green-lt text-green font-monospace" id="sim-points-counter">0 cycles enregistrés</span>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-1" onclick="clearVillageChart()" title="Réinitialiser les courbes">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-2">
                <!-- Conteneur ApexCharts avec Canvas HD de secours -->
                <div id="village-apex-chart" style="min-height: 320px; width: 100%;"></div>
                <canvas id="village-canvas-chart" class="d-none" width="800" height="320" style="width: 100%; height: 320px;"></canvas>
            </div>
        </div>

        <!-- 3. Journal d'Événements du Village (Ticker / Log en Direct) -->
        <div class="card shadow-sm border-0">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-scroll text-secondary"></i>
                    <span>Chronique Événementielle du Village (Journal en Direct)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary-lt font-monospace" id="log-count-badge">0 événement</span>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-1" onclick="clearVillageLog()" title="Vider le journal">
                        <i class="fa-solid fa-broom"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 220px; overflow-y: auto;" id="village-live-log-container">
                <div class="text-center text-muted p-4 small" id="village-empty-log-msg">
                    <i class="fa-solid fa-hourglass-start me-1"></i>Lancez la simulation pour observer les chroniques du village...
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Script CDN ApexCharts pour le tracé interactif temps réel -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- ═══════════════════════════════════════════════════════════════════════
     MOTEUR JAVASCRIPT DU SIMULATEUR DE VIE DANS UN VILLAGE (CLIENT-SIDE)
     ═══════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    // État interne de la simulation
    const state = {
        isRunning: false,
        timerId: null,
        speed: 10,              // Facteur d'accélération (x1 à x100)
        tickIntervalMs: 500,    // Cadence de l'intervalle JS (500ms)
        hoursElapsed: 0,        // Total d'heures féodales simulées
        cyclesCount: 0,         // Nombre de ticks exécutés

        // Paramètres actuels
        pop: 100,
        housingCap: 200,
        openJobs: 85,
        securityBonus: 10,
        prodRice: 60,
        prodFlour: 25,
        prodSake: 12,
        serenity: 75,

        // Stocks en réserve
        stockRice: 300,
        stockFlour: 120,
        stockSake: 50,

        // Métriques calculées
        contentment: 85,
        statusLabel: 'Euphorique & Prospère',
        badgeColor: 'success',
        assignedJobs: 85,
        idleWorkers: 15,
        workforceRatio: 1.0,
        unemploymentRate: 0.15,
        unemploymentPct: 15,
        baseDelinquency: 0,
        netDelinquency: 0,
        delinquencyPenalty: 0,
        delinquencyStatusLabel: 'Ordre Parfait',
        delinquencyBadgeColor: 'success',
        sakeBonusActive: true,
        isExodus: false,

        // Historique des séries temporelles (max 60 points)
        history: {
            labels: [],
            pop: [],
            rice: [],
            flour: [],
            sake: [],
            delinquency: [],
            contentment: []
        },
        maxHistoryPoints: 40
    };

    let apexChartInstance = null;

    // Presets préconfigurés
    const PRESETS = {
        famine: {
            pop: 150,
            housingCap: 200,
            openJobs: 110,
            security: 5,
            prodRice: 0,
            prodFlour: 0,
            prodSake: 0,
            serenity: 35,
            stockRice: 10,
            stockFlour: 5,
            stockSake: 0
        },
        sake_prosperity: {
            pop: 110,
            housingCap: 250,
            openJobs: 95,
            security: 25,
            prodRice: 80,
            prodFlour: 35,
            prodSake: 25,
            serenity: 90,
            stockRice: 500,
            stockFlour: 200,
            stockSake: 150
        },
        overcrowded: {
            pop: 280,
            housingCap: 250,
            openJobs: 60,
            security: 5,
            prodRice: 60,
            prodFlour: 20,
            prodSake: 5,
            serenity: 45,
            stockRice: 200,
            stockFlour: 40,
            stockSake: 10
        },
        delinquency_crisis: {
            pop: 250,
            housingCap: 300,
            openJobs: 40,
            security: 0,
            prodRice: 60,
            prodFlour: 25,
            prodSake: 0,
            serenity: 40,
            stockRice: 150,
            stockFlour: 40,
            stockSake: 0
        },
        perfect_balance: {
            pop: 120,
            housingCap: 200,
            openJobs: 110,
            security: 15,
            prodRice: 70,
            prodFlour: 30,
            prodSake: 12,
            serenity: 80,
            stockRice: 400,
            stockFlour: 160,
            stockSake: 60
        }
    };

    // Synchronisation Curseur Slider -> Input & État
    window.syncInput = function(key, val) {
        val = parseFloat(val);
        switch(key) {
            case 'initial-pop':
                state.pop = val;
                document.getElementById('val-initial-pop').innerText = val + ' habitants';
                document.getElementById('range-initial-pop').value = val;
                break;
            case 'housing-cap':
                state.housingCap = val;
                document.getElementById('val-housing-cap').innerText = val + ' places';
                document.getElementById('range-housing-cap').value = val;
                break;
            case 'open-jobs':
                state.openJobs = val;
                document.getElementById('val-open-jobs').innerText = val + ' postes';
                document.getElementById('range-open-jobs').value = val;
                break;
            case 'security':
                state.securityBonus = val;
                document.getElementById('val-security').innerText = '+' + val + "% d'ordre";
                document.getElementById('range-security').value = val;
                break;
            case 'prod-rice':
                state.prodRice = val;
                document.getElementById('val-prod-rice').innerText = '+' + val + ' koku / h';
                document.getElementById('range-prod-rice').value = val;
                break;
            case 'prod-flour':
                state.prodFlour = val;
                document.getElementById('val-prod-flour').innerText = '+' + val + ' sacs / h';
                document.getElementById('range-prod-flour').value = val;
                break;
            case 'prod-sake':
                state.prodSake = val;
                document.getElementById('val-prod-sake').innerText = '+' + val + ' tonnelets / h';
                document.getElementById('range-prod-sake').value = val;
                break;
            case 'serenity':
                state.serenity = val;
                document.getElementById('val-serenity').innerText = val + ' / 100';
                document.getElementById('range-serenity').value = val;
                break;
        }
        recalculateMetrics();
        updateUI();
    };

    // Application d'un scénario prédéfini
    window.applyVillagePreset = function(presetKey) {
        const p = PRESETS[presetKey];
        if (!p) return;

        window.syncInput('initial-pop', p.pop);
        window.syncInput('housing-cap', p.housingCap);
        window.syncInput('open-jobs', p.openJobs);
        window.syncInput('security', (p.security !== undefined) ? p.security : 10);
        window.syncInput('prod-rice', p.prodRice);
        window.syncInput('prod-flour', p.prodFlour);
        window.syncInput('prod-sake', p.prodSake);
        window.syncInput('serenity', p.serenity);

        state.stockRice = p.stockRice;
        state.stockFlour = p.stockFlour;
        state.stockSake = p.stockSake;

        addLogEntry('Scénario appliqué : ' + presetKey.replace('_', ' ').toUpperCase(), 'event-info', 'fa-wand-magic-sparkles');
        recalculateMetrics();
        updateUI();
    };

    // Ajustement de la vitesse d'accélération
    window.setVillageSpeed = function(newSpeed) {
        state.speed = parseInt(newSpeed, 10);
        document.querySelectorAll('.time-btn').forEach(btn => {
            if (parseInt(btn.getAttribute('data-speed'), 10) === state.speed) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        const labels = {
            1: 'Vitesse x1 (1h / 10 sec)',
            5: 'Vitesse x5 (1h / 2 sec)',
            10: 'Vitesse x10 (1h / 1 sec)',
            25: 'Vitesse x25 (2.5h / 1 sec)',
            50: 'Vitesse x50 (5h / 1 sec)',
            100: 'Vitesse x100 (10h / 1 sec - Ultra-rapide)'
        };
        document.getElementById('speed-indicator-label').innerText = labels[state.speed] || ('Vitesse x' + state.speed);
    };

    // Démarrage de la simulation
    window.startVillageSim = function() {
        if (state.isRunning) return;
        state.isRunning = true;
        document.getElementById('btn-sim-play').classList.add('d-none');
        document.getElementById('btn-sim-pause').classList.remove('d-none');

        addLogEntry('Simulation lancée à vitesse x' + state.speed, 'event-info', 'fa-play');

        state.timerId = setInterval(() => {
            tickStep();
        }, state.tickIntervalMs);
    };

    // Pause de la simulation
    window.pauseVillageSim = function() {
        if (!state.isRunning) return;
        state.isRunning = false;
        clearInterval(state.timerId);
        state.timerId = null;

        document.getElementById('btn-sim-play').classList.remove('d-none');
        document.getElementById('btn-sim-pause').classList.add('d-none');

        addLogEntry('Simulation mise en pause', 'event-info', 'fa-pause');
    };

    // Pas temporel manuel (+1h ou +24h)
    window.stepVillageSim = function(hours) {
        const prevSpeed = state.speed;
        processDeltaHours(hours);
        recalculateMetrics();
        updateUI();
        recordDataPoint();
    };

    // Réinitialisation de la simulation
    window.resetVillageSim = function() {
        window.pauseVillageSim();
        state.hoursElapsed = 0;
        state.cyclesCount = 0;
        window.applyVillagePreset('perfect_balance');
        window.clearVillageChart();
        window.clearVillageLog();
        addLogEntry('Simulateur réinitialisé au Jour 1', 'event-info', 'fa-rotate-left');
    };

    // Cœur de la boucle temporelle (Tick Step)
    function tickStep() {
        // Calcul du temps féodal écoulé lors de ce pas
        // À x10 : 1 tick (500ms) = 0.5 heure féodale
        const deltaHours = (state.tickIntervalMs / 1000) * (state.speed / 10);
        processDeltaHours(deltaHours);
        recalculateMetrics();
        updateUI();

        // Enregistrer une mesure graphique tous les 2 ticks pour ne pas surcharger
        if (state.cyclesCount % 2 === 0) {
            recordDataPoint();
        }
    }

    // Traitement mathématique d'un delta d'heures
    function processDeltaHours(hours) {
        state.hoursElapsed += hours;
        state.cyclesCount++;

        const pop = state.pop;

        // 1. Production horaire
        state.stockRice += state.prodRice * hours;
        state.stockFlour += state.prodFlour * hours;
        state.stockSake += state.prodSake * hours;

        // 2. Consommation vitale de nourriture (0.04 sac farine par habitant par heure)
        const flourNeeded = pop * 0.04 * hours;
        let famineTriggered = false;

        if (state.stockFlour >= flourNeeded) {
            state.stockFlour -= flourNeeded;
        } else {
            const missingFlour = flourNeeded - state.stockFlour;
            state.stockFlour = 0;
            // Puiser dans le riz brut
            if (state.stockRice >= missingFlour) {
                state.stockRice -= missingFlour;
            } else {
                state.stockRice = 0;
                famineTriggered = true;
            }
        }

        // 3. Consommation de Saké (0.01 tonnelet par habitant par heure si disponible)
        // RÈGLE STRICTE ASYMÉTRIQUE : si vide, aucun malus, juste absence du bonus !
        if (state.stockSake > 0) {
            const sakeNeeded = pop * 0.01 * hours;
            const consumed = Math.min(state.stockSake, sakeNeeded);
            state.stockSake = Math.max(0, state.stockSake - consumed);
            state.sakeBonusActive = (state.stockSake > 0);
        } else {
            state.sakeBonusActive = false;
        }

        // 4. Plafond de stockage féodal (max 2000 unités par ressource)
        state.stockRice = Math.min(2500, Math.max(0, state.stockRice));
        state.stockFlour = Math.min(1500, Math.max(0, state.stockFlour));
        state.stockSake = Math.min(1000, Math.max(0, state.stockSake));

        // 5. Calcul de Contentement selon PopulationEngine
        let baseScore = 75;
        const foodDelta = famineTriggered ? -55 : +15;
        const serenityDelta = (state.serenity >= 50) ? 0 : -20;
        const sakePoints = state.sakeBonusActive ? +15 : 0; // Strictement >= 0 (asymétrique)

        // Impact du ratio d'emploi (sous-effectif sur parcelles)
        const requiredWorkers = state.openJobs;
        const assigned = Math.min(pop, requiredWorkers);
        const jobRatio = (requiredWorkers > 0) ? Math.min(1.0, pop / requiredWorkers) : 1.0;
        let jobMalus = 0;
        if (jobRatio < 1.0) {
            jobMalus = -Math.round((1.0 - jobRatio) * 15);
        }

        // Malus de surpopulation (si population > logements)
        let overcrowdingMalus = 0;
        if (pop > state.housingCap) {
            overcrowdingMalus = -Math.min(30, Math.round(((pop - state.housingCap) / state.housingCap) * 50));
        }

        // Délinquance féodale (manque de travail / chômage)
        const idleWorkers = Math.max(0, pop - requiredWorkers);
        const unempRate = (pop > 20) ? Math.min(1.0, Math.max(0, idleWorkers / Math.max(1, pop))) : 0;
        const baseDlq = (unempRate > 0.15) ? Math.min(100, Math.round(((unempRate - 0.15) / 0.85) * 100)) : 0;
        const netDlq = Math.max(0, Math.round(baseDlq - state.securityBonus));
        const delinquencyPenalty = Math.round((netDlq / 100) * 25);
        const delinquencyMalus = -delinquencyPenalty;

        state.netDelinquency = netDlq;
        state.delinquencyPenalty = delinquencyPenalty;

        let totalScore = Math.max(0, Math.min(100, baseScore + foodDelta + serenityDelta + sakePoints + jobMalus + overcrowdingMalus + delinquencyMalus));
        state.contentment = totalScore;
        state.isExodus = (totalScore < 25);

        // 6. Démographie : Naissances ou Exode
        if (state.isExodus) {
            // Fuite de 6% par heure de crise (plancher à 10 habitants)
            const fleeing = Math.ceil(pop * 0.06 * hours);
            const actualFleeing = Math.min(pop - 10, fleeing);
            if (actualFleeing > 0) {
                state.pop -= actualFleeing;
                if (state.cyclesCount % 3 === 0) {
                    addLogEntry(`Crise morale (${totalScore}%) : ${actualFleeing} villageois fuient le village !`, 'event-exodus', 'fa-skull');
                }
            }
        } else if (totalScore >= 75 && state.pop < state.housingCap && !famineTriggered) {
            // Croissance naturelle
            const growthRate = 0.04 * (totalScore / 100);
            const births = Math.floor(pop * growthRate * hours);
            const actualBirths = Math.min(state.housingCap - state.pop, births);
            if (actualBirths > 0) {
                state.pop += actualBirths;
                if (state.cyclesCount % 5 === 0) {
                    addLogEntry(`Félicité féodale (${totalScore}%) : Naissance et arrivée de ${actualBirths} nouveaux villageois.`, 'event-growth', 'fa-baby');
                }
            }
        }

        // Journalisation intelligente des événements de seuil
        if (famineTriggered && state.cyclesCount % 4 === 0) {
            addLogEntry('Rupture de vivres : La famine frappe le fief (-55% de moral) !', 'event-famine', 'fa-triangle-exclamation');
        }
        if (state.sakeBonusActive && state.cyclesCount % 6 === 0) {
            addLogEntry('Festivités de saké : Régal des villageois (+15% de satisfaction active).', 'event-sake', 'fa-wine-bottle');
        }
        if (netDlq >= 40 && state.cyclesCount % 5 === 0) {
            addLogEntry(`Criminalité & Chômage (${netDlq}%) : L'oisiveté et le manque de postes sèment le chaos (-${delinquencyPenalty}% moral) !`, 'event-delinquency', 'fa-skull-crossbones');
        } else if (netDlq > 15 && netDlq < 40 && state.cyclesCount % 6 === 0) {
            addLogEntry(`Insécurité & Vols (${netDlq}%) : ${idleWorkers} villageois oisifs dégradent l'harmonie (-${delinquencyPenalty}% moral).`, 'event-delinquency', 'fa-mask');
        }
    }

    // Recalcul des métriques UI dérivées
    function recalculateMetrics() {
        const pop = state.pop;
        const jobs = state.openJobs;
        state.assignedJobs = Math.min(pop, jobs);
        state.idleWorkers = Math.max(0, pop - jobs);
        state.workforceRatio = (jobs > 0) ? Math.min(1.0, pop / jobs) : 1.0;

        // Délinquance et sécurité
        if (pop <= 20) {
            state.unemploymentRate = 0;
            state.unemploymentPct = 0;
            state.baseDelinquency = 0;
            state.netDelinquency = 0;
            state.delinquencyPenalty = 0;
            state.delinquencyStatusLabel = 'Ordre Parfait';
            state.delinquencyBadgeColor = 'success';
        } else {
            state.unemploymentRate = Math.min(1.0, Math.max(0, state.idleWorkers / Math.max(1, pop)));
            state.unemploymentPct = Math.round(state.unemploymentRate * 100);
            state.baseDelinquency = (state.unemploymentRate > 0.15) 
                ? Math.min(100, Math.round(((state.unemploymentRate - 0.15) / 0.85) * 100)) 
                : 0;
            state.netDelinquency = Math.max(0, Math.round(state.baseDelinquency - state.securityBonus));
            state.delinquencyPenalty = Math.round((state.netDelinquency / 100) * 25);

            if (state.netDelinquency <= 0) {
                state.delinquencyStatusLabel = 'Ordre Parfait';
                state.delinquencyBadgeColor = 'success';
            } else if (state.netDelinquency <= 15) {
                state.delinquencyStatusLabel = 'Tension Faible';
                state.delinquencyBadgeColor = 'info';
            } else if (state.netDelinquency <= 35) {
                state.delinquencyStatusLabel = 'Vols & Mécontentement';
                state.delinquencyBadgeColor = 'warning';
            } else if (state.netDelinquency <= 60) {
                state.delinquencyStatusLabel = 'Troubles & Brigandage';
                state.delinquencyBadgeColor = 'danger';
            } else {
                state.delinquencyStatusLabel = 'Criminalité Sévère';
                state.delinquencyBadgeColor = 'danger';
            }
        }

        const score = state.contentment;
        if (score >= 80) {
            state.statusLabel = 'Euphorique & Prospère';
            state.badgeColor = 'success';
        } else if (score >= 50) {
            state.statusLabel = 'Paisible & Satisfait';
            state.badgeColor = 'teal';
        } else if (score >= 25) {
            state.statusLabel = 'Inquiet & Tendu';
            state.badgeColor = 'warning';
        } else {
            state.statusLabel = 'Exode Imminent !';
            state.badgeColor = 'danger';
        }
    }

    // Mise à jour complète du DOM
    function updateUI() {
        // Horloge
        const day = Math.floor(state.hoursElapsed / 24) + 1;
        const hour = Math.floor(state.hoursElapsed % 24);
        const hourFormatted = (hour < 10 ? '0' : '') + hour + ':00';
        document.getElementById('sim-clock-display').innerText = `Jour ${day} • ${hourFormatted}`;

        // KPI 1 : Contentement
        const valContentment = document.getElementById('kpi-contentment-val');
        valContentment.innerText = state.contentment + '%';
        valContentment.className = `fs-1 fw-bold text-${state.badgeColor}`;
        document.getElementById('kpi-contentment-status').innerText = state.statusLabel;
        const barContentment = document.getElementById('kpi-contentment-bar');
        barContentment.style.width = state.contentment + '%';
        barContentment.className = `progress-bar bg-${state.badgeColor}`;

        const cardContentment = document.getElementById('card-kpi-contentment');
        if (state.isExodus) {
            cardContentment.classList.add('sim-blinking-exodus');
        } else {
            cardContentment.classList.remove('sim-blinking-exodus');
        }

        const sakeIconBadge = document.getElementById('kpi-sake-bonus-icon');
        if (state.sakeBonusActive) {
            sakeIconBadge.classList.remove('d-none');
        } else {
            sakeIconBadge.classList.add('d-none');
        }

        // KPI 2 : Population
        document.getElementById('kpi-pop-val').innerText = Math.round(state.pop);
        document.getElementById('kpi-housing-max').innerText = state.housingCap;
        const occRate = Math.round((state.pop / state.housingCap) * 100);
        document.getElementById('kpi-housing-rate').innerText = occRate + '% Logements';
        document.getElementById('kpi-pop-bar').style.width = Math.min(100, occRate) + '%';
        document.getElementById('demography-badge-total').innerText = 'Pop: ' + Math.round(state.pop);

        // KPI 3 : Main-d'œuvre
        document.getElementById('kpi-workforce-val').innerText = state.assignedJobs;
        document.getElementById('kpi-workforce-req').innerText = state.openJobs;
        const wfRate = Math.round(state.workforceRatio * 100);
        document.getElementById('kpi-workforce-bar').style.width = wfRate + '%';
        if (state.workforceRatio < 1.0) {
            const malus = Math.round((1.0 - state.workforceRatio) * 100);
            document.getElementById('kpi-workforce-status').innerText = `Sous-effectif (-${malus}%)`;
        } else if (state.idleWorkers > 0) {
            document.getElementById('kpi-workforce-status').innerText = `${state.idleWorkers} inactifs (${state.unemploymentPct}% sans poste)`;
        } else {
            document.getElementById('kpi-workforce-status').innerText = `Plein emploi garanti`;
        }

        // KPI 4 : Délinquance & Sécurité
        const valDelinquency = document.getElementById('kpi-delinquency-val');
        if (valDelinquency) {
            valDelinquency.innerText = state.netDelinquency + '%';
            valDelinquency.className = `fs-1 fw-bold font-monospace text-${state.delinquencyBadgeColor}`;
            document.getElementById('kpi-delinquency-status').innerText = `${state.delinquencyStatusLabel} (-${state.delinquencyPenalty}%)`;
            const barDelinquency = document.getElementById('kpi-delinquency-bar');
            barDelinquency.style.width = Math.min(100, state.netDelinquency) + '%';
            barDelinquency.className = `progress-bar bg-${state.delinquencyBadgeColor}`;
            const cardDelinquency = document.getElementById('card-kpi-delinquency');
            if (cardDelinquency) {
                cardDelinquency.style.setProperty('border-left-color', `var(--tblr-${state.delinquencyBadgeColor})`, 'important');
            }
            const badgeDelinquency = document.getElementById('kpi-delinquency-badge');
            if (badgeDelinquency) {
                badgeDelinquency.className = `badge bg-${state.delinquencyBadgeColor}-lt text-${state.delinquencyBadgeColor} py-0 px-1`;
                badgeDelinquency.innerHTML = `<i class="fa-solid ${state.netDelinquency > 15 ? 'fa-mask' : 'fa-shield-halved'} me-1"></i>${state.delinquencyStatusLabel}`;
            }
        }

        // KPI 5 : Stocks
        document.getElementById('kpi-stock-rice').innerText = Math.round(state.stockRice);
        document.getElementById('kpi-stock-flour').innerText = Math.round(state.stockFlour);
        document.getElementById('kpi-stock-sake').innerText = Math.round(state.stockSake);

        const foodStatus = document.getElementById('kpi-food-status');
        if (state.stockFlour <= 0 && state.stockRice <= 0) {
            foodStatus.innerText = '⚠️ Famine Déclarée';
            foodStatus.className = 'text-danger fw-bold mt-1';
        } else if (state.stockFlour <= 15) {
            foodStatus.innerText = 'Ravitaillement critique';
            foodStatus.className = 'text-warning fw-bold mt-1';
        } else {
            foodStatus.innerText = 'Rations assurées';
            foodStatus.className = 'text-muted small mt-1';
        }
    }

    // Ajout d'une ligne d'événement au journal
    function addLogEntry(text, typeClass, icon) {
        const container = document.getElementById('village-live-log-container');
        const emptyMsg = document.getElementById('village-empty-log-msg');
        if (emptyMsg) emptyMsg.remove();

        const day = Math.floor(state.hoursElapsed / 24) + 1;
        const hour = Math.floor(state.hoursElapsed % 24);
        const timeStr = `J${day} ${hour < 10 ? '0' : ''}${hour}:00`;

        const row = document.createElement('div');
        row.className = `live-log-item ${typeClass} d-flex align-items-center justify-content-between`;
        row.innerHTML = `
            <div class="d-flex align-items-center gap-2 text-truncate">
                <i class="fa-solid ${icon}"></i>
                <span>${text}</span>
            </div>
            <span class="text-secondary small font-monospace ms-2" style="font-size:0.7rem;">${timeStr}</span>
        `;

        container.insertBefore(row, container.firstChild);

        // Limiter à 30 entrées dans le journal
        while (container.children.length > 30) {
            container.removeChild(container.lastChild);
        }

        document.getElementById('log-count-badge').innerText = container.children.length + ' événements';
    }

    window.clearVillageLog = function() {
        const container = document.getElementById('village-live-log-container');
        container.innerHTML = '<div class="text-center text-muted p-4 small" id="village-empty-log-msg"><i class="fa-solid fa-hourglass-start me-1"></i>Journal réinitialisé.</div>';
        document.getElementById('log-count-badge').innerText = '0 événement';
    };

    // Gestion du Graphique ApexCharts (ou Canvas HD de secours)
    function initApexChart() {
        const chartEl = document.getElementById('village-apex-chart');
        if (!chartEl) return;

        if (typeof ApexCharts !== 'undefined') {
            const options = {
                chart: {
                    type: 'area',
                    height: 320,
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 400
                        }
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                series: [
                    { name: '👥 Population', data: [] },
                    { name: '🌾 Riz (koku)', data: [] },
                    { name: '🍚 Farine (sacs)', data: [] },
                    { name: '🍶 Saké (tonnelets)', data: [] },
                    { name: '🗡️ Délinquance (%)', data: [] },
                    { name: '❤️ Satisfaction (%)', data: [] }
                ],
                colors: ['#0284c7', '#eab308', '#64748b', '#a855f7', '#e11d48', '#22c55e'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2, 2, 2, 2, 3]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: 0.35,
                        opacityTo: 0.05
                    }
                },
                xaxis: {
                    categories: [],
                    labels: {
                        show: true,
                        style: { fontSize: '10px' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '10px' }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '11px'
                },
                tooltip: {
                    shared: true,
                    intersect: false
                }
            };

            apexChartInstance = new ApexCharts(chartEl, options);
            apexChartInstance.render();
        } else {
            // Activer le fallback Canvas HD
            chartEl.classList.add('d-none');
            document.getElementById('village-canvas-chart').classList.remove('d-none');
        }
    }

    // Enregistrement d'un point de données dans les courbes
    function recordDataPoint() {
        const day = Math.floor(state.hoursElapsed / 24) + 1;
        const hour = Math.floor(state.hoursElapsed % 24);
        const label = `J${day} ${hour}h`;

        const h = state.history;
        h.labels.push(label);
        h.pop.push(Math.round(state.pop));
        h.rice.push(Math.round(state.stockRice));
        h.flour.push(Math.round(state.stockFlour));
        h.sake.push(Math.round(state.stockSake));
        h.delinquency.push(state.netDelinquency);
        h.contentment.push(state.contentment);

        // Tronquer au nombre maximum de points
        if (h.labels.length > state.maxHistoryPoints) {
            h.labels.shift();
            h.pop.shift();
            h.rice.shift();
            h.flour.shift();
            h.sake.shift();
            h.delinquency.shift();
            h.contentment.shift();
        }

        document.getElementById('sim-points-counter').innerText = h.labels.length + ' points';

        if (apexChartInstance) {
            apexChartInstance.updateOptions({
                xaxis: { categories: h.labels }
            }, false, false);

            apexChartInstance.updateSeries([
                { name: '👥 Population', data: h.pop },
                { name: '🌾 Riz (koku)', data: h.rice },
                { name: '🍚 Farine (sacs)', data: h.flour },
                { name: '🍶 Saké (tonnelets)', data: h.sake },
                { name: '🗡️ Délinquance (%)', data: h.delinquency },
                { name: '❤️ Satisfaction (%)', data: h.contentment }
            ], true);
        } else {
            renderCanvasFallback();
        }
    }

    window.clearVillageChart = function() {
        state.history = {
            labels: [],
            pop: [],
            rice: [],
            flour: [],
            sake: [],
            delinquency: [],
            contentment: []
        };
        if (apexChartInstance) {
            apexChartInstance.updateSeries([
                { name: '👥 Population', data: [] },
                { name: '🌾 Riz (koku)', data: [] },
                { name: '🍚 Farine (sacs)', data: [] },
                { name: '🍶 Saké (tonnelets)', data: [] },
                { name: '🗡️ Délinquance (%)', data: [] },
                { name: '❤️ Satisfaction (%)', data: [] }
            ]);
        }
        document.getElementById('sim-points-counter').innerText = '0 cycle';
    };

    // Fallback Canvas HD en l'absence d'ApexCharts
    function renderCanvasFallback() {
        const canvas = document.getElementById('village-canvas-chart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;

        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, w, h);

        const series = state.history.pop;
        if (series.length < 2) return;

        // Tracé de la courbe population
        ctx.beginPath();
        ctx.strokeStyle = '#0284c7';
        ctx.lineWidth = 3;

        const maxVal = Math.max(200, ...series);
        const stepX = w / (series.length - 1);

        series.forEach((val, idx) => {
            const x = idx * stepX;
            const y = h - (val / maxVal) * (h - 40) - 20;
            if (idx === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();
    }

    // Exportation du scénario d'équilibrage en JSON téléchargeable
    window.exportVillageScenario = function() {
        const report = {
            export_title: "OpenShogun - Bilan de Simulation de Vie de Village",
            exported_at: new Date().toISOString(),
            job: "Game Elevate Designer",
            simulated_time: {
                hours_elapsed: Math.round(state.hoursElapsed * 10) / 10,
                days_elapsed: Math.floor(state.hoursElapsed / 24) + 1,
                total_cycles: state.cyclesCount
            },
            parameters: {
                initial_population: state.pop,
                housing_capacity: state.housingCap,
                open_jobs: state.openJobs,
                security_bonus: state.securityBonus,
                hourly_production: {
                    rice: state.prodRice,
                    flour: state.prodFlour,
                    sake: state.prodSake,
                    serenity: state.serenity
                }
            },
            final_metrics: {
                population: Math.round(state.pop),
                contentment: state.contentment,
                status_label: state.statusLabel,
                workforce_ratio: Math.round(state.workforceRatio * 100) / 100,
                idle_workers: state.idleWorkers,
                unemployment_pct: state.unemploymentPct,
                net_delinquency: state.netDelinquency,
                delinquency_penalty: state.delinquencyPenalty,
                is_exodus: state.isExodus,
                sake_bonus_active: state.sakeBonusActive,
                residual_stocks: {
                    rice: Math.round(state.stockRice),
                    flour: Math.round(state.stockFlour),
                    sake: Math.round(state.stockSake)
                }
            },
            history_data: state.history,
            game_balancing_insights: [
                "La règle asymétrique du saké permet un levier moral dynamique sans pénaliser les villages en paix.",
                "Le manque de travail (> 15% d'inactifs) engendre de la délinquance féodale et pénalise le moral jusqu'à -25%.",
                "Le maintien de l'ordre (Tenshu, Muraille, Dojo, Vigie) permet de réprimer et d'endiguer la criminalité urbaine.",
                "Le point de bascule de l'exode féodal à 25% nécessite un approvisionnement continu en farine ou riz.",
                "Le ratio d'emploi doit être maintenu au-dessus de 0.85 pour éviter le malus de productivité rurale."
            ]
        };

        const jsonStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(report, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute("href", jsonStr);
        downloadAnchor.setAttribute("download", `village_simulation_${Date.now()}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();

        addLogEntry('Bilan JSON du scénario téléchargé avec succès.', 'event-info', 'fa-file-arrow-down');
    };

    // Initialisation au chargement de la vue
    document.addEventListener("DOMContentLoaded", function() {
        initApexChart();
        recalculateMetrics();
        updateUI();
        recordDataPoint();
    });

    // Initialisation immédiate si le DOM est déjà prêt
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(() => {
            initApexChart();
            recalculateMetrics();
            updateUI();
            recordDataPoint();
        }, 100);
    }
})();
</script>
