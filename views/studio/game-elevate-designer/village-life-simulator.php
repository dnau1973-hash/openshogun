<?php
/**
 * Module Studio Dev : Simulateur de Vie dans un Village & Équilibrage Démographique
 * Métier : Game Elevate Designer
 * Emplacement : views/studio/game-elevate-designer/village-life-simulator.php
 */

require_once __DIR__ . '/../../../core/AuthManager.php';
require_once __DIR__ . '/../../../core/PopulationEngine.php';
require_once __DIR__ . '/../../../core/SocialCasteEngine.php';

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
                <a href="/?page=gamestudio" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour au Game Studio
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}
?>

<!-- ── EN-TÊTE DU MODULE : SIMULATEUR DE VIE DANS UN VILLAGE & ÉQUILIBRAGE DES FLUX ── -->
<div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid var(--tblr-success) !important;">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-green-lt text-green font-game px-2 py-1">
                        <i class="fa-solid fa-compass-drafting me-1"></i>Game Elevate Designer
                    </span>
                    <span class="badge bg-primary-lt text-primary">
                        <i class="fa-solid fa-people-roof me-1"></i>Bac à Sable Démographique v2.1
                    </span>
                    <span class="badge bg-info-lt text-info">
                        <i class="fa-solid fa-layer-group me-1"></i>4 Castes Féodales
                    </span>
                    <span class="badge bg-purple-lt text-purple">
                        <i class="fa-solid fa-wine-bottle me-1"></i>Règle Asymétrique du Saké (+15%)
                    </span>
                    <span class="badge bg-warning-lt text-dark fw-bold">
                        <i class="fa-solid fa-boxes-stacked me-1"></i>Flux Vivres &amp; Matériaux
                    </span>
                    <span class="badge bg-dark-lt text-dark font-monospace">
                        ⚡ Horloge x1 à x100
                    </span>
                </div>
                <h2 class="h3 mb-0 text-dark fw-bold d-flex align-items-center gap-2">
                    <span>Simulateur de Vie dans un Village &amp; Équilibrage des Flux</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Simulez l'écosystème d'un fief féodal : flux entrants de vivres (Riz, Poisson, Thé, Soja, Farine, Saké) et de matériaux (Bois, Pierre, Argile, Poutres, Charbon), répartition pyramidale des 4 strates sociales (Paysans, Artisans, Marchands, Noblesse), oisiveté et délinquance, seuils de famine et seuils d'exode rural.
                </div>
            </div>

            <!-- Profils Prédéfinis (Presets Rapides) -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-secondary small fw-bold d-none d-md-inline">Scénarios :</span>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="applyVillagePreset('perfect_balance')" title="Harmonie idéale entre vivres, matériaux et castes féodales">
                    <i class="fa-solid fa-scale-balanced me-1"></i>Équilibre Sengoku
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="applyVillagePreset('artisanal_boom')" title="Boom de construction, ateliers florissants et forte classe d'artisans">
                    <i class="fa-solid fa-hammer me-1"></i>Âge d'Or Artisanal
                </button>
                <button type="button" class="btn btn-sm btn-outline-purple text-purple" onclick="applyVillagePreset('sake_prosperity')" title="Abondance de saké, cérémonies du thé et apogée de la noblesse">
                    <i class="fa-solid fa-wine-bottle me-1"></i>Prospérité &amp; Cour
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyVillagePreset('delinquency_crisis')" title="Explosion du chômage et criminalité sans ordre féodal">
                    <i class="fa-solid fa-mask me-1"></i>Crise de Délinquance
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyVillagePreset('famine')" title="Pénurie sévère de vivres et effondrement moral">
                    <i class="fa-solid fa-skull me-1"></i>Pénurie &amp; Exode
                </button>
                <button type="button" class="btn btn-sm btn-light text-muted" onclick="resetVillageSim()" title="Réinitialiser le simulateur">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles modernes et réactifs du simulateur de vie de village */
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
.live-log-item.event-caste {
    border-left-color: #0284c7;
    background: #f0f9ff;
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
    50% { opacity: 0.7; transform: scale(1.01); }
    100% { opacity: 1; transform: scale(1); }
}
.sim-blinking-exodus {
    animation: simPulseExodus 1.2s infinite ease-in-out;
}
.caste-pill {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.73rem;
    font-weight: 600;
}
.caste-nomin { background: #dcfce7; color: #15803d; }
.caste-kogiyo { background: #e0f2fe; color: #0369a1; }
.caste-shonin { background: #fef3c7; color: #b45309; }
.caste-noblesse { background: #f3e8ff; color: #6b21a8; }
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     LAYOUT 2 COLONNES TABLER.IO : PARAMÉTRAGE VS MONITEUR TEMPS RÉEL
     ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- ── COLONNE GAUCHE (5/12) : CONTRÔLE DU TEMPS & PARAMÈTRES D'ÉQUILIBRAGE ── -->
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

        <!-- 2. Paramètres Démographiques & Infrastructure -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #10b981 !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-users text-success"></i>
                    <span>Démographie &amp; Infrastructure</span>
                </div>
                <span class="badge bg-secondary-lt font-monospace" id="demography-badge-total">Pop: 120</span>
            </div>
            <div class="card-body p-3">
                <!-- 1. Population Initiale -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-initial-pop">
                            <i class="fa-solid fa-person text-primary me-1"></i>Population Initiale :
                        </label>
                        <span class="badge bg-primary font-monospace" id="val-initial-pop">120 habitants</span>
                    </div>
                    <input type="range" class="form-range" id="range-initial-pop" min="10" max="600" step="5" value="120" oninput="syncInput('initial-pop', this.value)">
                </div>

                <!-- 2. Capacité des Logements (Minka) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-housing-cap">
                            <i class="fa-solid fa-house-chimney text-success me-1"></i>Capacité des Minka (Logements &amp; Parcelles) :
                        </label>
                        <span class="badge bg-success font-monospace" id="val-housing-cap">200 places</span>
                    </div>
                    <input type="range" class="form-range" id="range-housing-cap" min="50" max="800" step="10" value="200" oninput="syncInput('housing-cap', this.value)">
                </div>

                <!-- 3. Postes de Travail Ouverts -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-open-jobs">
                            <i class="fa-solid fa-briefcase text-warning me-1"></i>Postes de Travail Ouverts (Ateliers &amp; Exploitations) :
                        </label>
                        <span class="badge bg-warning text-dark font-monospace" id="val-open-jobs">110 postes</span>
                    </div>
                    <input type="range" class="form-range" id="range-open-jobs" min="10" max="600" step="5" value="110" oninput="syncInput('open-jobs', this.value)">
                </div>

                <!-- 4. Maintien de l'Ordre Féodal (Garnison & Tenshu) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0" for="range-security">
                            <i class="fa-solid fa-shield-halved text-danger me-1"></i>Maintien de l'Ordre Féodal (Garnison &amp; Vigie) :
                        </label>
                        <span class="badge bg-danger font-monospace" id="val-security">+15% d'ordre</span>
                    </div>
                    <input type="range" class="form-range" id="range-security" min="0" max="50" step="1" value="15" oninput="syncInput('security', this.value)">
                    <div class="text-secondary small">Présence militaire (Tenshu, Muraille, Dojo, Vigie). Réprime la criminalité liée au chômage et à l'oisiveté.</div>
                </div>
            </div>
        </div>

        <!-- 3. Flux Agricoles & Vivres (Entrées Nettes /h) -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #f59e0b !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-wheat-awn text-warning"></i>
                    <span>Flux Agricoles &amp; Vivres (Entrées /h)</span>
                </div>
                <span class="badge bg-warning text-dark fw-bold">Alimentation des Castes</span>
            </div>
            <div class="card-body p-3">
                <!-- 1. Riz Impérial -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial (Base vitale Nōmin) :
                        </label>
                        <span class="badge bg-warning text-dark font-monospace" id="val-prod-rice">+70 koku / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-rice" min="0" max="300" step="5" value="70" oninput="syncInput('prod-rice', this.value)">
                    <div class="text-secondary small">0.04 koku/habitant paysan/heure. Aliment de subsistance vital prévenant la famine.</div>
                </div>

                <!-- 2. Poisson Frais -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-fish text-azure me-1"></i>Poisson Frais (Port &amp; Pêcherie) :
                        </label>
                        <span class="badge bg-azure text-white font-monospace" id="val-prod-fish">+30 paniers / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-fish" min="0" max="150" step="2" value="30" oninput="syncInput('prod-fish', this.value)">
                    <div class="text-secondary small">Nécessaire à l'épanouissement de la caste des Artisans (0.02 panier/hab/h).</div>
                </div>

                <!-- 3. Soja du Terroir -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-seedling text-success me-1"></i>Soja du Terroir (Champs &amp; Maraîchage) :
                        </label>
                        <span class="badge bg-success text-white font-monospace" id="val-prod-soy">+25 sacs / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-soy" min="0" max="150" step="2" value="25" oninput="syncInput('prod-soy', this.value)">
                    <div class="text-secondary small">Consommé par les Artisans &amp; Bâtisseurs (0.02 sac/hab/h) avec le poisson.</div>
                </div>

                <!-- 4. Farine de Riz -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-bowl-rice text-secondary me-1"></i>Farine de Riz (Meunerie &amp; Minoterie) :
                        </label>
                        <span class="badge bg-secondary text-white font-monospace" id="val-prod-flour">+30 sacs / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-flour" min="0" max="150" step="2" value="30" oninput="syncInput('prod-flour', this.value)">
                    <div class="text-secondary small">Consommée par les Marchands (0.02 sac/h) et la Noblesse (0.02 sac/h).</div>
                </div>

                <!-- 5. Thé Vert des Coteaux -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-mug-hot text-teal me-1"></i>Thé Vert (Plantations &amp; Pavillons) :
                        </label>
                        <span class="badge bg-teal text-white font-monospace" id="val-prod-tea">+15 boîtes / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-tea" min="0" max="100" step="1" value="15" oninput="syncInput('prod-tea', this.value)">
                    <div class="text-secondary small">Requis pour les Marchands (0.015 boîte/h) et la Noblesse/Bushi (0.015 boîte/h).</div>
                </div>

                <!-- 6. Saké Impérial (Règle Asymétrique) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-wine-bottle text-purple me-1"></i>Saké Impérial (Brasseries féodales) :
                        </label>
                        <span class="badge bg-purple text-white font-monospace" id="val-prod-sake">+15 tonnelets / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-sake" min="0" max="80" step="1" value="15" oninput="syncInput('prod-sake', this.value)">
                    <div class="text-secondary small">
                        <strong>Règle Asymétrique :</strong> Si stock &gt; 0, confère <strong>+15% de satisfaction</strong>. Si épuisé, n'inflige <strong>aucun malus</strong>. Requis pour maintenir la haute Noblesse (0.02/h).
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Flux Matériaux & Énergie Féodale (Entrées Nettes /h) -->
        <div class="card mb-3 shadow-sm border-0" style="border-top: 3px solid #6366f1 !important;">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-cubes-stacked text-indigo"></i>
                    <span>Flux Matériaux &amp; Énergie (Entrées /h)</span>
                </div>
                <span class="badge bg-indigo text-white fw-bold">Chantiers &amp; Ateliers</span>
            </div>
            <div class="card-body p-3">
                <!-- 1. Bois de Cèdre -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-tree text-green me-1"></i>Bois de Cèdre (Scieries) :
                        </label>
                        <span class="badge bg-green text-white font-monospace" id="val-prod-wood">+50 unités / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-wood" min="0" max="200" step="5" value="50" oninput="syncInput('prod-wood', this.value)">
                </div>

                <!-- 2. Pierre de Granit -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-gem text-secondary me-1"></i>Pierre de Granit (Carrières) :
                        </label>
                        <span class="badge bg-secondary text-white font-monospace" id="val-prod-stone">+40 unités / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-stone" min="0" max="200" step="5" value="40" oninput="syncInput('prod-stone', this.value)">
                </div>

                <!-- 3. Argile & Terres Cuites -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-shapes text-orange me-1"></i>Argile &amp; Terres (Fosses &amp; Tuileries) :
                        </label>
                        <span class="badge bg-orange text-white font-monospace" id="val-prod-clay">+30 unités / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-clay" min="0" max="150" step="2" value="30" oninput="syncInput('prod-clay', this.value)">
                </div>

                <!-- 4. Poutres de Charpente (Atelier de charpenterie) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-trowel-bricks text-warning me-1"></i>Poutres de Charpente (Menuiserie) :
                        </label>
                        <span class="badge bg-warning text-dark font-monospace" id="val-prod-beams">+15 poutres / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-beams" min="0" max="100" step="1" value="15" oninput="syncInput('prod-beams', this.value)">
                </div>

                <!-- 5. Charbon de Bois (Fours & Meules) -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-fire-flame-curved text-dark me-1"></i>Charbon de Bois (Combustible des forges) :
                        </label>
                        <span class="badge bg-dark text-white font-monospace" id="val-prod-charcoal">+20 sacs / h</span>
                    </div>
                    <input type="range" class="form-range" id="range-prod-charcoal" min="0" max="100" step="1" value="20" oninput="syncInput('prod-charcoal', this.value)">
                </div>

                <!-- 6. Sérénité Shinto & Ferveur -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold m-0">
                            <i class="fa-solid fa-torii-gate text-teal me-1"></i>Sérénité Shinto &amp; Ferveur Kami :
                        </label>
                        <span class="badge bg-teal text-white font-monospace" id="val-serenity">80 / 100</span>
                    </div>
                    <input type="range" class="form-range" id="range-serenity" min="0" max="100" step="5" value="80" oninput="syncInput('serenity', this.value)">
                    <div class="text-secondary small">Énergie et paix spirituelle des sanctuaires. Si &lt; 50, induit une anxiété des villageois (-20%).</div>
                </div>
            </div>
        </div>

        <!-- 5. Export & Sauvegarde du Scénario -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-secondary">
                    <i class="fa-solid fa-file-code me-1 text-primary"></i>Exporter les paramètres, castes et séries au format standard JSON.
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm fw-bold" onclick="exportVillageScenario()">
                    <i class="fa-solid fa-download me-1"></i>Exporter Scénario JSON
                </button>
            </div>
        </div>

    </div>

    <!-- ── COLONNE DROITE (7/12) : MONITEUR TEMPS RÉEL, KPI & VISUALISATION ── -->
    <div class="col-12 col-xl-7">

        <!-- ── RANGÉE 1 DES KPI : MORAL, DÉMOGRAPHIE, CASTES, EMPLOIS ── -->
        <div class="row g-2 mb-2">
            <!-- KPI 1 : Satisfaction & Moral Global -->
            <div class="col-12 col-sm-6 col-xxl-3">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" id="card-kpi-contentment" style="border-left-color: #22c55e !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Satisfaction &amp; Moral</span>
                            <span id="kpi-sake-bonus-icon" class="badge bg-purple-lt text-purple py-0 px-1" title="Bonus Saké +15% Actif">🍶 +15%</span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-success font-monospace" id="kpi-contentment-val">88%</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-contentment-status">Euphorique &amp; Prospère</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-success" id="kpi-contentment-bar" style="width: 88%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 2 : Démographie & Minka -->
            <div class="col-12 col-sm-6 col-xxl-3">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" style="border-left-color: #0284c7 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Démographie (Minka)</span>
                            <span id="kpi-housing-occ-badge" class="badge bg-primary-lt text-primary py-0 px-1 font-monospace">
                                <i class="fa-solid fa-users me-1"></i>60% plein
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-primary font-monospace" id="kpi-pop-val">120</span>
                            <span class="text-muted small">/ <span id="kpi-housing-max">200</span></span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-housing-rate">60% d'occupation des Minka</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-primary" id="kpi-pop-bar" style="width: 60%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 3 : Pyramide des 4 Castes Féodales -->
            <div class="col-12 col-sm-6 col-xxl-3">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" style="border-left-color: #8b5cf6 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">4 Castes Sociales</span>
                            <span class="badge bg-purple-lt text-purple py-0 px-1 font-monospace" id="kpi-caste-top-badge">Shinōkōshō</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mb-1 font-monospace" style="font-size: 0.72rem;">
                            <span class="caste-pill caste-nomin" title="Paysans &amp; Ouvriers">🌾 <span id="caste-nomin-count">60</span></span>
                            <span class="caste-pill caste-kogiyo" title="Artisans &amp; Bâtisseurs">🔨 <span id="caste-kogiyo-count">30</span></span>
                            <span class="caste-pill caste-shonin" title="Marchands &amp; Caravaniers">⚖️ <span id="caste-shonin-count">18</span></span>
                            <span class="caste-pill caste-noblesse" title="Noblesse &amp; Daimyō">👑 <span id="caste-noblesse-count">12</span></span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-caste-level-desc">Palier : Cour Aristocratique</div>
                        <!-- Barre segmentée quadri-colore -->
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar" id="bar-caste-nomin" style="width: 50%; background-color: #16a34a;" title="Paysans"></div>
                            <div class="progress-bar" id="bar-caste-kogiyo" style="width: 25%; background-color: #0284c7;" title="Artisans"></div>
                            <div class="progress-bar" id="bar-caste-shonin" style="width: 15%; background-color: #d97706;" title="Marchands"></div>
                            <div class="progress-bar" id="bar-caste-noblesse" style="width: 10%; background-color: #7c3aed;" title="Noblesse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4 : Emplois & Activité -->
            <div class="col-12 col-sm-6 col-xxl-3">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" style="border-left-color: #f59e0b !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Emplois &amp; Activité</span>
                            <span id="kpi-workforce-badge" class="badge bg-warning-lt text-warning py-0 px-1 font-monospace">
                                <i class="fa-solid fa-briefcase me-1"></i>100% pourvu
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-warning font-monospace" id="kpi-workforce-val">110</span>
                            <span class="text-muted small">/ <span id="kpi-workforce-req">110</span></span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-workforce-status">Plein emploi garanti</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-warning" id="kpi-workforce-bar" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── RANGÉE 2 DES KPI : SÉCURITÉ, GRENIER VIVRES, ENTREPÔT MATÉRIAUX ── -->
        <div class="row g-2 mb-3">
            <!-- KPI 5 : Ordre & Sécurité Féodale -->
            <div class="col-12 col-md-4">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" id="card-kpi-delinquency" style="border-left-color: #22c55e !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Ordre &amp; Sécurité</span>
                            <span id="kpi-delinquency-badge" class="badge bg-success-lt text-success py-0 px-1">
                                <i class="fa-solid fa-shield-halved me-1"></i>Ordre Féodal
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-success font-monospace" id="kpi-delinquency-val">0%</span>
                            <span class="text-secondary small">(Délinquance nette)</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-delinquency-status">Sérénité &amp; Paix Civile (0%)</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-success" id="kpi-delinquency-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 6 : Grenier Féodal (Vivres) -->
            <div class="col-12 col-md-4">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" style="border-left-color: #0d9488 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Grenier (Vivres)</span>
                            <span id="kpi-grain-badge" class="badge bg-teal-lt text-teal py-0 px-1 font-monospace">
                                <i class="fa-solid fa-wheat-awn me-1"></i>Silos Pleins
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-teal font-monospace" id="kpi-stock-food-total">970</span>
                            <span class="text-secondary small">(Total en silos)</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-food-status">Rations &amp; Fêtes assurées</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-teal" id="kpi-food-bar" style="width: 65%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 7 : Entrepôt des Matériaux & Chantiers -->
            <div class="col-12 col-md-4">
                <div class="card card-sm shadow-sm h-100 village-kpi-card border-start border-3" style="border-left-color: #6366f1 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary small fw-bold">Entrepôt Matériaux</span>
                            <span id="kpi-materials-badge" class="badge bg-indigo-lt text-indigo py-0 px-1 font-monospace">
                                <i class="fa-solid fa-boxes-stacked me-1"></i>Chantiers
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bold text-indigo font-monospace" id="kpi-stock-mat-total">735</span>
                            <span class="text-secondary small">(Unités chantiers)</span>
                        </div>
                        <div class="text-muted small text-truncate" id="kpi-materials-status">Chantiers Kizukuri approvisionnés</div>
                        <div class="progress progress-xs mt-2">
                            <div class="progress-bar bg-indigo" id="kpi-materials-bar" style="width: 55%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── GRAPHIQUE DYNAMIQUE MULTI-SÉRIES (APEXCHARTS + FALLBACK CANVAS) ── -->
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-header py-2 bg-light-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    <span>Dynamique Temporelle (Population, 4 Castes, Saké, Délinquance, Moral)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-1" onclick="clearVillageChart()" title="Réinitialiser les courbes">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-2">
                <!-- Conteneur ApexCharts avec Canvas HD de secours -->
                <div id="village-apex-chart" style="min-height: 330px; width: 100%;"></div>
                <canvas id="village-canvas-chart" class="d-none" width="800" height="330" style="width: 100%; height: 330px;"></canvas>
            </div>
        </div>

        <!-- ── JOURNAL D'ÉVÉNEMENTS DU VILLAGE (TICKER / LOG EN DIRECT) ── -->
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
                    <i class="fa-solid fa-hourglass-start me-1"></i>Lancez la simulation pour observer les chroniques démographiques et sociales du village...
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
    // État interne complet de la simulation
    const state = {
        isRunning: false,
        timerId: null,
        speed: 10,              // Facteur d'accélération (x1 à x100)
        tickIntervalMs: 500,    // Cadence de l'intervalle JS (500ms)
        hoursElapsed: 0,        // Total d'heures féodales simulées
        cyclesCount: 0,         // Nombre de ticks exécutés

        // Paramètres démographiques et d'infrastructure
        pop: 120,
        housingCap: 200,
        openJobs: 110,
        securityBonus: 15,
        serenity: 80,

        // Flux de production horaire Vivres
        prodRice: 70,
        prodFish: 30,
        prodSoy: 25,
        prodFlour: 30,
        prodTea: 15,
        prodSake: 15,

        // Flux de production horaire Matériaux
        prodWood: 50,
        prodStone: 40,
        prodClay: 30,
        prodBeams: 15,
        prodCharcoal: 20,

        // Stocks de vivres
        stockRice: 400,
        stockFish: 150,
        stockSoy: 120,
        stockFlour: 160,
        stockTea: 80,
        stockSake: 60,

        // Stocks de matériaux
        stockWood: 250,
        stockStone: 180,
        stockClay: 140,
        stockBeams: 75,
        stockCharcoal: 90,

        // 4 Castes Féodales (Effectifs et Pourcentages)
        castes: {
            nomin: { count: 60, pct: 50.0 },     // Paysans & Ouvriers
            kogiyo: { count: 30, pct: 25.0 },    // Artisans & Bâtisseurs
            shonin: { count: 18, pct: 15.0 },    // Marchands & Caravaniers
            noblesse: { count: 12, pct: 10.0 }   // Noblesse (Bushi & Daimyō)
        },
        civLevelName: 'Cour Aristocratique',

        // Métriques calculées de moral et activité
        contentment: 88,
        statusLabel: 'Euphorique & Prospère',
        badgeColor: 'success',
        assignedJobs: 110,
        idleWorkers: 10,
        workforceRatio: 1.0,
        unemploymentPct: 8,
        netDelinquency: 0,
        delinquencyPenalty: 0,
        delinquencyStatusLabel: 'Ordre Féodal',
        delinquencyBadgeColor: 'success',
        sakeBonusActive: true,
        famineTriggered: false,
        isExodus: false,

        // Historique des séries temporelles (max 40 points)
        history: {
            labels: [],
            pop: [],
            nomin: [],
            kogiyo: [],
            shonin: [],
            noblesse: [],
            sake: [],
            delinquency: [],
            contentment: []
        },
        maxHistoryPoints: 40
    };

    let apexChartInstance = null;

    // Presets préconfigurés enrichis
    const PRESETS = {
        perfect_balance: {
            pop: 120,
            housingCap: 200,
            openJobs: 110,
            security: 15,
            serenity: 80,
            prodRice: 70,
            prodFish: 30,
            prodSoy: 25,
            prodFlour: 30,
            prodTea: 15,
            prodSake: 15,
            prodWood: 50,
            prodStone: 40,
            prodClay: 30,
            prodBeams: 15,
            prodCharcoal: 20,
            stockRice: 400,
            stockFish: 150,
            stockSoy: 120,
            stockFlour: 160,
            stockTea: 80,
            stockSake: 60,
            stockWood: 250,
            stockStone: 180,
            stockClay: 140,
            stockBeams: 75,
            stockCharcoal: 90
        },
        artisanal_boom: {
            pop: 140,
            housingCap: 220,
            openJobs: 135,
            security: 20,
            serenity: 85,
            prodRice: 80,
            prodFish: 50,
            prodSoy: 45,
            prodFlour: 35,
            prodTea: 20,
            prodSake: 12,
            prodWood: 120,
            prodStone: 90,
            prodClay: 70,
            prodBeams: 40,
            prodCharcoal: 45,
            stockRice: 450,
            stockFish: 250,
            stockSoy: 200,
            stockFlour: 180,
            stockTea: 90,
            stockSake: 50,
            stockWood: 500,
            stockStone: 400,
            stockClay: 300,
            stockBeams: 160,
            stockCharcoal: 180
        },
        sake_prosperity: {
            pop: 130,
            housingCap: 250,
            openJobs: 115,
            security: 25,
            serenity: 95,
            prodRice: 90,
            prodFish: 35,
            prodSoy: 30,
            prodFlour: 45,
            prodTea: 35,
            prodSake: 35,
            prodWood: 60,
            prodStone: 50,
            prodClay: 35,
            prodBeams: 20,
            prodCharcoal: 25,
            stockRice: 600,
            stockFish: 200,
            stockSoy: 160,
            stockFlour: 250,
            stockTea: 150,
            stockSake: 180,
            stockWood: 300,
            stockStone: 220,
            stockClay: 160,
            stockBeams: 90,
            stockCharcoal: 110
        },
        delinquency_crisis: {
            pop: 250,
            housingCap: 300,
            openJobs: 45,
            security: 0,
            serenity: 40,
            prodRice: 50,
            prodFish: 10,
            prodSoy: 10,
            prodFlour: 15,
            prodTea: 5,
            prodSake: 0,
            prodWood: 20,
            prodStone: 15,
            prodClay: 10,
            prodBeams: 5,
            prodCharcoal: 5,
            stockRice: 120,
            stockFish: 20,
            stockSoy: 15,
            stockFlour: 30,
            stockTea: 10,
            stockSake: 0,
            stockWood: 80,
            stockStone: 60,
            stockClay: 40,
            stockBeams: 15,
            stockCharcoal: 20
        },
        famine: {
            pop: 160,
            housingCap: 200,
            openJobs: 110,
            security: 5,
            serenity: 30,
            prodRice: 0,
            prodFish: 0,
            prodSoy: 0,
            prodFlour: 0,
            prodTea: 0,
            prodSake: 0,
            prodWood: 15,
            prodStone: 10,
            prodClay: 5,
            prodBeams: 2,
            prodCharcoal: 5,
            stockRice: 15,
            stockFish: 5,
            stockSoy: 5,
            stockFlour: 5,
            stockTea: 0,
            stockSake: 0,
            stockWood: 60,
            stockStone: 40,
            stockClay: 25,
            stockBeams: 10,
            stockCharcoal: 15
        }
    };

    // Synchronisation Curseur Slider -> État & Affichage
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
            case 'serenity':
                state.serenity = val;
                document.getElementById('val-serenity').innerText = val + ' / 100';
                document.getElementById('range-serenity').value = val;
                break;

            // Vivres
            case 'prod-rice':
                state.prodRice = val;
                document.getElementById('val-prod-rice').innerText = '+' + val + ' koku / h';
                document.getElementById('range-prod-rice').value = val;
                break;
            case 'prod-fish':
                state.prodFish = val;
                document.getElementById('val-prod-fish').innerText = '+' + val + ' paniers / h';
                document.getElementById('range-prod-fish').value = val;
                break;
            case 'prod-soy':
                state.prodSoy = val;
                document.getElementById('val-prod-soy').innerText = '+' + val + ' sacs / h';
                document.getElementById('range-prod-soy').value = val;
                break;
            case 'prod-flour':
                state.prodFlour = val;
                document.getElementById('val-prod-flour').innerText = '+' + val + ' sacs / h';
                document.getElementById('range-prod-flour').value = val;
                break;
            case 'prod-tea':
                state.prodTea = val;
                document.getElementById('val-prod-tea').innerText = '+' + val + ' boîtes / h';
                document.getElementById('range-prod-tea').value = val;
                break;
            case 'prod-sake':
                state.prodSake = val;
                document.getElementById('val-prod-sake').innerText = '+' + val + ' tonnelets / h';
                document.getElementById('range-prod-sake').value = val;
                break;

            // Matériaux
            case 'prod-wood':
                state.prodWood = val;
                document.getElementById('val-prod-wood').innerText = '+' + val + ' unités / h';
                document.getElementById('range-prod-wood').value = val;
                break;
            case 'prod-stone':
                state.prodStone = val;
                document.getElementById('val-prod-stone').innerText = '+' + val + ' unités / h';
                document.getElementById('range-prod-stone').value = val;
                break;
            case 'prod-clay':
                state.prodClay = val;
                document.getElementById('val-prod-clay').innerText = '+' + val + ' unités / h';
                document.getElementById('range-prod-clay').value = val;
                break;
            case 'prod-beams':
                state.prodBeams = val;
                document.getElementById('val-prod-beams').innerText = '+' + val + ' poutres / h';
                document.getElementById('range-prod-beams').value = val;
                break;
            case 'prod-charcoal':
                state.prodCharcoal = val;
                document.getElementById('val-prod-charcoal').innerText = '+' + val + ' sacs / h';
                document.getElementById('range-prod-charcoal').value = val;
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
        window.syncInput('security', (p.security !== undefined) ? p.security : 15);
        window.syncInput('serenity', p.serenity);

        window.syncInput('prod-rice', p.prodRice);
        window.syncInput('prod-fish', p.prodFish);
        window.syncInput('prod-soy', p.prodSoy);
        window.syncInput('prod-flour', p.prodFlour);
        window.syncInput('prod-tea', p.prodTea);
        window.syncInput('prod-sake', p.prodSake);

        window.syncInput('prod-wood', p.prodWood);
        window.syncInput('prod-stone', p.prodStone);
        window.syncInput('prod-clay', p.prodClay);
        window.syncInput('prod-beams', p.prodBeams);
        window.syncInput('prod-charcoal', p.prodCharcoal);

        state.stockRice = p.stockRice;
        state.stockFish = p.stockFish;
        state.stockSoy = p.stockSoy;
        state.stockFlour = p.stockFlour;
        state.stockTea = p.stockTea;
        state.stockSake = p.stockSake;

        state.stockWood = p.stockWood;
        state.stockStone = p.stockStone;
        state.stockClay = p.stockClay;
        state.stockBeams = p.stockBeams;
        state.stockCharcoal = p.stockCharcoal;

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
            100: 'Vitesse x100 (10h / 1 sec - Accélération Max)'
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

    // Boucle temporelle (Tick Step)
    function tickStep() {
        const deltaHours = (state.tickIntervalMs / 1000) * (state.speed / 10);
        processDeltaHours(deltaHours);
        recalculateMetrics();
        updateUI();

        // Enregistrer une mesure graphique tous les 2 ticks pour une animation fluide
        if (state.cyclesCount % 2 === 0) {
            recordDataPoint();
        }
    }

    // Traitement mathématique d'un delta d'heures (Castes, Stocks, Contentement, Délinquance)
    function processDeltaHours(hours) {
        state.hoursElapsed += hours;
        state.cyclesCount++;

        const pop = state.pop;

        // 1. Entrées de production des Vivres
        state.stockRice += state.prodRice * hours;
        state.stockFish += state.prodFish * hours;
        state.stockSoy += state.prodSoy * hours;
        state.stockFlour += state.prodFlour * hours;
        state.stockTea += state.prodTea * hours;
        state.stockSake += state.prodSake * hours;

        // 2. Entrées de production des Matériaux
        state.stockWood += state.prodWood * hours;
        state.stockStone += state.prodStone * hours;
        state.stockClay += state.prodClay * hours;
        state.stockBeams += state.prodBeams * hours;
        state.stockCharcoal += state.prodCharcoal * hours;

        // 3. Calcul dynamique de la répartition des 4 Castes Sociales (Shinōkōshō)
        // Selon SocialCasteEngine :
        // Cible idéale : Noblesse 10%, Marchands 15%, Artisans 25%, Paysans 50%
        const targetNoblesse = Math.max(0, Math.round(pop * 0.10));
        const targetMarchands = Math.max(0, Math.round(pop * 0.15));
        const targetArtisans = Math.max(0, Math.round(pop * 0.25));

        // Strate 4 : Noblesse (Bushi & Daimyō)
        // Besoins : Saké 0.02, Thé 0.015, Farine 0.02 /hab/h
        let actualNoblesse = 0;
        if (hours > 0 && targetNoblesse > 0) {
            const reqSake = targetNoblesse * 0.02 * hours;
            const reqTea = targetNoblesse * 0.015 * hours;
            const reqFlour = targetNoblesse * 0.02 * hours;
            let ratioN = 1.0;
            if (reqSake > 0) ratioN = Math.min(ratioN, state.stockSake / reqSake);
            if (reqTea > 0) ratioN = Math.min(ratioN, state.stockTea / reqTea);
            if (reqFlour > 0) ratioN = Math.min(ratioN, state.stockFlour / reqFlour);
            ratioN = Math.max(0, Math.min(1.0, ratioN));

            actualNoblesse = Math.floor(targetNoblesse * ratioN);
            if (actualNoblesse > 0) {
                state.stockSake -= Math.min(state.stockSake, actualNoblesse * 0.02 * hours);
                state.stockTea -= Math.min(state.stockTea, actualNoblesse * 0.015 * hours);
                state.stockFlour -= Math.min(state.stockFlour, actualNoblesse * 0.02 * hours);
            }
        }

        // Strate 3 : Marchands (Shōnin)
        // Besoins : Farine 0.02, Thé 0.015 /hab/h
        let actualMarchands = 0;
        if (hours > 0 && targetMarchands > 0) {
            const reqFlour = targetMarchands * 0.02 * hours;
            const reqTea = targetMarchands * 0.015 * hours;
            let ratioM = 1.0;
            if (reqFlour > 0) ratioM = Math.min(ratioM, state.stockFlour / reqFlour);
            if (reqTea > 0) ratioM = Math.min(ratioM, state.stockTea / reqTea);
            ratioM = Math.max(0, Math.min(1.0, ratioM));

            actualMarchands = Math.floor(targetMarchands * ratioM);
            if (actualMarchands > 0) {
                state.stockFlour -= Math.min(state.stockFlour, actualMarchands * 0.02 * hours);
                state.stockTea -= Math.min(state.stockTea, actualMarchands * 0.015 * hours);
            }
        }

        // Strate 2 : Artisans (Kōgiyō)
        // Besoins : Poisson 0.02, Soja 0.02 /hab/h
        let actualArtisans = 0;
        if (hours > 0 && targetArtisans > 0) {
            const reqFish = targetArtisans * 0.02 * hours;
            const reqSoy = targetArtisans * 0.02 * hours;
            let ratioA = 1.0;
            if (reqFish > 0) ratioA = Math.min(ratioA, state.stockFish / reqFish);
            if (reqSoy > 0) ratioA = Math.min(ratioA, state.stockSoy / reqSoy);
            ratioA = Math.max(0, Math.min(1.0, ratioA));

            actualArtisans = Math.floor(targetArtisans * ratioA);
            if (actualArtisans > 0) {
                state.stockFish -= Math.min(state.stockFish, actualArtisans * 0.02 * hours);
                state.stockSoy -= Math.min(state.stockSoy, actualArtisans * 0.02 * hours);
                // Consommation nominale d'entretien d'ateliers par les artisans
                state.stockWood -= Math.min(state.stockWood, actualArtisans * 0.015 * hours);
                state.stockStone -= Math.min(state.stockStone, actualArtisans * 0.01 * hours);
                state.stockClay -= Math.min(state.stockClay, actualArtisans * 0.01 * hours);
                state.stockBeams -= Math.min(state.stockBeams, actualArtisans * 0.005 * hours);
                state.stockCharcoal -= Math.min(state.stockCharcoal, actualArtisans * 0.01 * hours);
            }
        }

        // Strate 1 : Paysans & Ouvriers (Nōmin)
        const actualPaysans = Math.max(0, Math.round(pop - (actualNoblesse + actualMarchands + actualArtisans)));

        // Consommation vitale de subsistance paysanne (0.04 sac ou koku/hab/h)
        const vitalFoodNeeded = actualPaysans * 0.04 * hours;
        let famineOccurred = false;

        if (state.stockFlour >= vitalFoodNeeded) {
            state.stockFlour -= vitalFoodNeeded;
        } else {
            const missingFlour = vitalFoodNeeded - state.stockFlour;
            state.stockFlour = 0;
            if (state.stockRice >= missingFlour) {
                state.stockRice -= missingFlour;
            } else {
                state.stockRice = 0;
                famineOccurred = true;
            }
        }
        state.famineTriggered = famineOccurred;

        // Enregistrement des castes dans le state
        const totalP = Math.max(1, pop);
        state.castes = {
            nomin: { count: actualPaysans, pct: Math.round((actualPaysans / totalP) * 1000) / 10 },
            kogiyo: { count: actualArtisans, pct: Math.round((actualArtisans / totalP) * 1000) / 10 },
            shonin: { count: actualMarchands, pct: Math.round((actualMarchands / totalP) * 1000) / 10 },
            noblesse: { count: actualNoblesse, pct: Math.round((actualNoblesse / totalP) * 1000) / 10 }
        };

        if (actualNoblesse >= 5) {
            state.civLevelName = 'Cour Aristocratique & Shōgunat';
        } else if (actualMarchands >= 5) {
            state.civLevelName = 'Comptoir Marchand Prospère';
        } else if (actualArtisans >= 5) {
            state.civLevelName = 'Cité Artisanale Kizukuri';
        } else {
            state.civLevelName = 'Hameau Rural Paysan';
        }

        // 4. Règle Asymétrique du Saké :
        // Si présent > 0, procure +15% de satisfaction. Si épuisé, aucun malus !
        state.sakeBonusActive = (state.stockSake > 1);

        // 5. Plafonds de stockage féodaux
        const capVivres = 3000;
        state.stockRice = Math.min(capVivres, Math.max(0, state.stockRice));
        state.stockFish = Math.min(capVivres, Math.max(0, state.stockFish));
        state.stockSoy = Math.min(capVivres, Math.max(0, state.stockSoy));
        state.stockFlour = Math.min(capVivres, Math.max(0, state.stockFlour));
        state.stockTea = Math.min(capVivres, Math.max(0, state.stockTea));
        state.stockSake = Math.min(capVivres, Math.max(0, state.stockSake));

        const capMat = 2500;
        state.stockWood = Math.min(capMat, Math.max(0, state.stockWood));
        state.stockStone = Math.min(capMat, Math.max(0, state.stockStone));
        state.stockClay = Math.min(capMat, Math.max(0, state.stockClay));
        state.stockBeams = Math.min(capMat, Math.max(0, state.stockBeams));
        state.stockCharcoal = Math.min(capMat, Math.max(0, state.stockCharcoal));

        // 6. Formule de Contentement Global
        let baseScore = 70;
        const foodDelta = famineOccurred ? -55 : +15;
        const serenityDelta = (state.serenity >= 50) ? 0 : -20;
        const sakePoints = state.sakeBonusActive ? +15 : 0; // Strictement >= 0 (asymétrique)

        // Bonus d'épanouissement des castes supérieures
        let casteBonus = 0;
        if (targetNoblesse > 0 && actualNoblesse >= targetNoblesse * 0.7) casteBonus += 5;
        if (targetMarchands > 0 && actualMarchands >= targetMarchands * 0.7) casteBonus += 4;
        if (targetArtisans > 0 && actualArtisans >= targetArtisans * 0.7) casteBonus += 4;

        // Malus de sous-effectif sur les ateliers
        const requiredWorkers = state.openJobs;
        const jobRatio = (requiredWorkers > 0) ? Math.min(1.0, pop / requiredWorkers) : 1.0;
        let jobMalus = 0;
        if (jobRatio < 1.0) {
            jobMalus = -Math.round((1.0 - jobRatio) * 15);
        }

        // Malus de surpopulation (si population > parcelles de logements)
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

        const totalScore = Math.max(0, Math.min(100, baseScore + foodDelta + serenityDelta + sakePoints + casteBonus + jobMalus + overcrowdingMalus + delinquencyMalus));
        state.contentment = totalScore;
        state.isExodus = (totalScore < 25);

        // 7. Démographie : Naissances ou Fuite féodale
        if (state.isExodus) {
            const fleeing = Math.ceil(pop * 0.06 * hours);
            const actualFleeing = Math.min(pop - 10, fleeing);
            if (actualFleeing > 0) {
                state.pop -= actualFleeing;
                if (state.cyclesCount % 3 === 0) {
                    addLogEntry(`Effondrement moral (${totalScore}%) : ${actualFleeing} villageois fuient le fief !`, 'event-exodus', 'fa-skull');
                }
            }
        } else if (totalScore >= 75 && state.pop < state.housingCap && !famineOccurred) {
            const growthRate = 0.04 * (totalScore / 100);
            const births = Math.floor(pop * growthRate * hours);
            const actualBirths = Math.min(state.housingCap - state.pop, births);
            if (actualBirths > 0) {
                state.pop += actualBirths;
                if (state.cyclesCount % 5 === 0) {
                    addLogEntry(`Félicité féodale (${totalScore}%) : Arrivée et naissance de ${actualBirths} villageois.`, 'event-growth', 'fa-baby');
                }
            }
        }

        // Journalisation intelligente des événements notables
        if (famineOccurred && state.cyclesCount % 4 === 0) {
            addLogEntry('Rupture de vivres : La famine frappe le fief (-55% de satisfaction) !', 'event-famine', 'fa-triangle-exclamation');
        }
        if (state.sakeBonusActive && state.cyclesCount % 8 === 0) {
            addLogEntry('Festivités de saké : Régal des samouraïs et villageois (+15% de bonus actif).', 'event-sake', 'fa-wine-bottle');
        }
        if (netDlq >= 35 && state.cyclesCount % 5 === 0) {
            addLogEntry(`Chômage & Brigandage (${netDlq}%) : ${idleWorkers} oisifs perturbent la paix civile (-${delinquencyPenalty}% moral).`, 'event-delinquency', 'fa-mask');
        }
        if (actualNoblesse >= 8 && state.cyclesCount % 10 === 0) {
            addLogEntry(`Prestige de la Cour : ${actualNoblesse} seigneurs et samouraïs honorent le château.`, 'event-caste', 'fa-crown');
        }
    }

    // Recalcul des métriques UI dérivées
    function recalculateMetrics() {
        const pop = state.pop;
        const jobs = state.openJobs;
        state.assignedJobs = Math.min(pop, jobs);
        state.idleWorkers = Math.max(0, pop - jobs);
        state.workforceRatio = (jobs > 0) ? Math.min(1.0, pop / jobs) : 1.0;

        if (pop <= 20) {
            state.unemploymentPct = 0;
            state.netDelinquency = 0;
            state.delinquencyPenalty = 0;
            state.delinquencyStatusLabel = 'Ordre Féodal';
            state.delinquencyBadgeColor = 'success';
        } else {
            const unempRate = Math.min(1.0, Math.max(0, state.idleWorkers / Math.max(1, pop)));
            state.unemploymentPct = Math.round(unempRate * 100);
            const baseDlq = (unempRate > 0.15) ? Math.min(100, Math.round(((unempRate - 0.15) / 0.85) * 100)) : 0;
            state.netDelinquency = Math.max(0, Math.round(baseDlq - state.securityBonus));
            state.delinquencyPenalty = Math.round((state.netDelinquency / 100) * 25);

            if (state.netDelinquency <= 0) {
                state.delinquencyStatusLabel = 'Ordre Féodal Parfait';
                state.delinquencyBadgeColor = 'success';
            } else if (state.netDelinquency <= 15) {
                state.delinquencyStatusLabel = 'Tension Légère';
                state.delinquencyBadgeColor = 'info';
            } else if (state.netDelinquency <= 35) {
                state.delinquencyStatusLabel = 'Vols & Mécontentement';
                state.delinquencyBadgeColor = 'warning';
            } else {
                state.delinquencyStatusLabel = 'Brigandage Sévère';
                state.delinquencyBadgeColor = 'danger';
            }
        }

        const score = state.contentment;
        if (score >= 80) {
            state.statusLabel = 'Euphorique & Prospère';
            state.badgeColor = 'success';
        } else if (score >= 50) {
            state.statusLabel = 'Paisible & Harmonieux';
            state.badgeColor = 'teal';
        } else if (score >= 25) {
            state.statusLabel = 'Inquiet & Agité';
            state.badgeColor = 'warning';
        } else {
            state.statusLabel = 'Exode & Révolte !';
            state.badgeColor = 'danger';
        }
    }

    // Mise à jour complète du DOM (Cartes KPI, Barres, Stocks, Castes)
    function updateUI() {
        // Horloge
        const day = Math.floor(state.hoursElapsed / 24) + 1;
        const hour = Math.floor(state.hoursElapsed % 24);
        const hourFormatted = (hour < 10 ? '0' : '') + hour + ':00';
        document.getElementById('sim-clock-display').innerText = `Jour ${day} • ${hourFormatted}`;

        // KPI 1 : Contentement & Moral
        const valContentment = document.getElementById('kpi-contentment-val');
        valContentment.innerText = state.contentment + '%';
        valContentment.className = `fs-1 fw-bold font-monospace text-${state.badgeColor}`;
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

        // KPI 2 : Population & Minka
        document.getElementById('kpi-pop-val').innerText = Math.round(state.pop);
        document.getElementById('kpi-housing-max').innerText = state.housingCap;
        const occRate = Math.round((state.pop / state.housingCap) * 100);
        document.getElementById('kpi-housing-rate').innerText = occRate + "% d'occupation des Minka";
        document.getElementById('kpi-pop-bar').style.width = Math.min(100, occRate) + '%';
        const demographyBadge = document.getElementById('kpi-housing-occ-badge');
        if (demographyBadge) {
            demographyBadge.innerHTML = `<i class="fa-solid fa-users me-1"></i>${occRate}% plein`;
            demographyBadge.className = occRate > 100 ? 'badge bg-danger-lt text-danger py-0 px-1 font-monospace' : 'badge bg-primary-lt text-primary py-0 px-1 font-monospace';
        }
        document.getElementById('demography-badge-total').innerText = 'Pop: ' + Math.round(state.pop);

        // KPI 3 : 4 Castes Féodales
        document.getElementById('caste-nomin-count').innerText = state.castes.nomin.count;
        document.getElementById('caste-kogiyo-count').innerText = state.castes.kogiyo.count;
        document.getElementById('caste-shonin-count').innerText = state.castes.shonin.count;
        document.getElementById('caste-noblesse-count').innerText = state.castes.noblesse.count;

        document.getElementById('bar-caste-nomin').style.width = state.castes.nomin.pct + '%';
        document.getElementById('bar-caste-kogiyo').style.width = state.castes.kogiyo.pct + '%';
        document.getElementById('bar-caste-shonin').style.width = state.castes.shonin.pct + '%';
        document.getElementById('bar-caste-noblesse').style.width = state.castes.noblesse.pct + '%';

        document.getElementById('kpi-caste-level-desc').innerText = 'Palier : ' + state.civLevelName;

        // KPI 4 : Emplois & Activité
        document.getElementById('kpi-workforce-val').innerText = state.assignedJobs;
        document.getElementById('kpi-workforce-req').innerText = state.openJobs;
        const wfRate = Math.round(state.workforceRatio * 100);
        document.getElementById('kpi-workforce-bar').style.width = wfRate + '%';
        const workforceBadge = document.getElementById('kpi-workforce-badge');
        if (state.workforceRatio < 1.0) {
            const malus = Math.round((1.0 - state.workforceRatio) * 100);
            document.getElementById('kpi-workforce-status').innerText = `Sous-effectif (-${malus}%)`;
            if (workforceBadge) {
                workforceBadge.className = 'badge bg-danger-lt text-danger py-0 px-1 font-monospace';
                workforceBadge.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i>${wfRate}% pourvu`;
            }
        } else if (state.idleWorkers > 0) {
            document.getElementById('kpi-workforce-status').innerText = `${state.idleWorkers} inactifs (${state.unemploymentPct}% sans poste)`;
            if (workforceBadge) {
                workforceBadge.className = 'badge bg-warning-lt text-warning py-0 px-1 font-monospace';
                workforceBadge.innerHTML = `<i class="fa-solid fa-person-shelter me-1"></i>${state.idleWorkers} inactifs`;
            }
        } else {
            document.getElementById('kpi-workforce-status').innerText = `Plein emploi garanti`;
            if (workforceBadge) {
                workforceBadge.className = 'badge bg-success-lt text-success py-0 px-1 font-monospace';
                workforceBadge.innerHTML = `<i class="fa-solid fa-briefcase me-1"></i>100% pourvu`;
            }
        }

        // KPI 5 : Ordre & Sécurité
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

        // KPI 6 : Stocks du Grenier Féodal (Vivres)
        const totalFood = Math.round(state.stockRice + state.stockFish + state.stockSoy + state.stockTea + state.stockFlour + state.stockSake);
        const foodTotalEl = document.getElementById('kpi-stock-food-total');
        if (foodTotalEl) foodTotalEl.innerText = totalFood;
        const foodBar = document.getElementById('kpi-food-bar');
        if (foodBar) {
            const foodFillPct = Math.min(100, Math.round((totalFood / 2000) * 100));
            foodBar.style.width = foodFillPct + '%';
            foodBar.className = state.famineTriggered ? 'progress-bar bg-danger' : (totalFood < 200 ? 'progress-bar bg-warning' : 'progress-bar bg-teal');
        }
        const foodStatus = document.getElementById('kpi-food-status');
        const grainBadge = document.getElementById('kpi-grain-badge');
        if (state.famineTriggered) {
            foodStatus.innerText = '⚠️ Famine Déclarée (Rupture)';
            foodStatus.className = 'text-danger fw-bold text-truncate';
            if (grainBadge) {
                grainBadge.className = 'badge bg-danger-lt text-danger py-0 px-1 font-monospace';
                grainBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Famine';
            }
        } else if (state.stockFlour <= 20 || state.stockRice <= 20) {
            foodStatus.innerText = 'Ravitaillement en flux tendu';
            foodStatus.className = 'text-warning fw-bold text-truncate';
            if (grainBadge) {
                grainBadge.className = 'badge bg-warning-lt text-warning py-0 px-1 font-monospace';
                grainBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Tension';
            }
        } else {
            foodStatus.innerText = 'Rations & Fêtes assurées';
            foodStatus.className = 'text-muted small text-truncate';
            if (grainBadge) {
                grainBadge.className = 'badge bg-teal-lt text-teal py-0 px-1 font-monospace';
                grainBadge.innerHTML = '<i class="fa-solid fa-wheat-awn me-1"></i>Silos Pleins';
            }
        }

        // KPI 7 : Stocks des Matériaux
        const totalMat = Math.round(state.stockWood + state.stockStone + state.stockClay + state.stockBeams + state.stockCharcoal);
        const matTotalEl = document.getElementById('kpi-stock-mat-total');
        if (matTotalEl) matTotalEl.innerText = totalMat;
        const matBar = document.getElementById('kpi-materials-bar');
        if (matBar) {
            const matFillPct = Math.min(100, Math.round((totalMat / 1500) * 100));
            matBar.style.width = matFillPct + '%';
            matBar.className = totalMat < 100 ? 'progress-bar bg-warning' : 'progress-bar bg-indigo';
        }
        const matStatus = document.getElementById('kpi-materials-status');
        const matBadge = document.getElementById('kpi-materials-badge');
        if (state.stockWood <= 10 || state.stockStone <= 10) {
            matStatus.innerText = 'Chantiers ralentis (Manque de matériaux)';
            matStatus.className = 'text-warning fw-bold text-truncate';
            if (matBadge) {
                matBadge.className = 'badge bg-warning-lt text-warning py-0 px-1 font-monospace';
                matBadge.innerHTML = '<i class="fa-solid fa-boxes-stacked me-1"></i>Ralentis';
            }
        } else {
            matStatus.innerText = 'Chantiers Kizukuri approvisionnés';
            matStatus.className = 'text-muted small text-truncate';
            if (matBadge) {
                matBadge.className = 'badge bg-indigo-lt text-indigo py-0 px-1 font-monospace';
                matBadge.innerHTML = '<i class="fa-solid fa-boxes-stacked me-1"></i>Chantiers';
            }
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
                    height: 330,
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
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 0
                },
                series: [
                    { name: '👥 Population', data: [] },
                    { name: '🌾 Paysans', data: [] },
                    { name: '🔨 Artisans', data: [] },
                    { name: '⚖️ Marchands', data: [] },
                    { name: '👑 Noblesse', data: [] },
                    { name: '🍶 Saké (tonnelets)', data: [] },
                    { name: '🗡️ Délinquance (%)', data: [] },
                    { name: '❤️ Satisfaction (%)', data: [] }
                ],
                colors: ['#0284c7', '#16a34a', '#0ea5e9', '#d97706', '#7c3aed', '#ec4899', '#e11d48', '#22c55e'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2, 2, 2, 2, 2, 2, 3]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: 0.30,
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
            // Fallback Canvas HD
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
        h.nomin.push(state.castes.nomin.count);
        h.kogiyo.push(state.castes.kogiyo.count);
        h.shonin.push(state.castes.shonin.count);
        h.noblesse.push(state.castes.noblesse.count);
        h.sake.push(Math.round(state.stockSake));
        h.delinquency.push(state.netDelinquency);
        h.contentment.push(state.contentment);

        // Tronquer au nombre maximum de points
        if (h.labels.length > state.maxHistoryPoints) {
            h.labels.shift();
            h.pop.shift();
            h.nomin.shift();
            h.kogiyo.shift();
            h.shonin.shift();
            h.noblesse.shift();
            h.sake.shift();
            h.delinquency.shift();
            h.contentment.shift();
        }

        const counterEl = document.getElementById('sim-points-counter');
        if (counterEl) counterEl.innerText = h.labels.length + ' points';

        if (apexChartInstance) {
            apexChartInstance.updateOptions({
                xaxis: { categories: h.labels }
            }, false, false);

            apexChartInstance.updateSeries([
                { name: '👥 Population', data: h.pop },
                { name: '🌾 Paysans', data: h.nomin },
                { name: '🔨 Artisans', data: h.kogiyo },
                { name: '⚖️ Marchands', data: h.shonin },
                { name: '👑 Noblesse', data: h.noblesse },
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
            nomin: [],
            kogiyo: [],
            shonin: [],
            noblesse: [],
            sake: [],
            delinquency: [],
            contentment: []
        };
        if (apexChartInstance) {
            apexChartInstance.updateSeries([
                { name: '👥 Population', data: [] },
                { name: '🌾 Paysans', data: [] },
                { name: '🔨 Artisans', data: [] },
                { name: '⚖️ Marchands', data: [] },
                { name: '👑 Noblesse', data: [] },
                { name: '🍶 Saké (tonnelets)', data: [] },
                { name: '🗡️ Délinquance (%)', data: [] },
                { name: '❤️ Satisfaction (%)', data: [] }
            ]);
        }
        const counterEl = document.getElementById('sim-points-counter');
        if (counterEl) counterEl.innerText = '0 cycle';
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

    // Exportation du scénario d'équilibrage complet en JSON
    window.exportVillageScenario = function() {
        const report = {
            export_title: "OpenShogun - Bilan Équilibrage de Vie Féodale & Castes",
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
                hourly_production_food: {
                    rice: state.prodRice,
                    fish: state.prodFish,
                    soybean: state.prodSoy,
                    flour: state.prodFlour,
                    tea: state.prodTea,
                    sake: state.prodSake
                },
                hourly_production_materials: {
                    wood: state.prodWood,
                    stone: state.prodStone,
                    clay: state.prodClay,
                    beams: state.prodBeams,
                    charcoal: state.prodCharcoal,
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
                famine_triggered: state.famineTriggered,
                caste_distribution: state.castes,
                civilization_palier: state.civLevelName,
                residual_food_stocks: {
                    rice: Math.round(state.stockRice),
                    fish: Math.round(state.stockFish),
                    soybean: Math.round(state.stockSoy),
                    flour: Math.round(state.stockFlour),
                    tea: Math.round(state.stockTea),
                    sake: Math.round(state.stockSake)
                },
                residual_material_stocks: {
                    wood: Math.round(state.stockWood),
                    stone: Math.round(state.stockStone),
                    clay: Math.round(state.stockClay),
                    beams: Math.round(state.stockBeams),
                    charcoal: Math.round(state.stockCharcoal),
                    serenity: Math.round(state.serenity)
                }
            },
            history_data: state.history,
            game_balancing_insights: [
                "La règle asymétrique du saké permet un levier moral (+15%) sans pénaliser les villages ordinaires lorsqu'il s'épuise.",
                "Les 4 strates sociales (Paysans, Artisans, Marchands, Noblesse) requièrent une chaîne de production diversifiée.",
                "Les Artisans consomment du poisson et du soja et mobilisent du bois, de la pierre, de l'argile et des poutres pour les chantiers.",
                "Les Marchands requièrent de la farine de riz et du thé vert, apportant une prospérité commerciale.",
                "La Noblesse consomme du saké, du thé et de la farine, conférant un bonus de moral supérieur à la province.",
                "Le manque de travail (> 15% d'inactifs) engendre de la délinquance réprimée par la garnison et le Tenshu.",
                "Le point de rupture de l'exode féodal fixé à 25% nécessite un grenier sécurisé sous peine de désertification."
            ]
        };

        const jsonStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(report, null, 2));
        const downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute("href", jsonStr);
        downloadAnchor.setAttribute("download", `village_life_simulation_${Date.now()}.json`);
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        downloadAnchor.remove();

        addLogEntry('Bilan JSON complet du scénario téléchargé avec succès.', 'event-info', 'fa-file-arrow-down');
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
