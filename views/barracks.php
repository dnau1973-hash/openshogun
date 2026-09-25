<?php
/**
 * Vue du Dojo Militaire & Entraînement des Troupes (Style Travian Feudal)
 */
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$barracksEngine = new BarracksEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$barracksLvl = $buildings['barracks'] ?? 0;

$availableUnits = $barracksEngine->getAvailableUnits((int)$planet['id'], $user['faction']);
$queue = $barracksEngine->getQueue((int)$planet['id']);
$activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
$famineUpkeep = $planetEngine->getEliteUnitsUpkeep((int)$planet['id']);

$factionNames = [
    'terran' => 'Clan Oda',
    'vorash' => 'Clan Takeda',
    'aethelis' => 'Clan Tokugawa'
];
$userClanName = $factionNames[$user['faction']] ?? 'Armée Provinciale';
?>

<div class="card" style="border-top: 4px solid var(--red-primary); overflow:hidden; margin-bottom:1.5rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; background:linear-gradient(to right, rgba(194,37,43,0.06), transparent); padding:1rem 1.25rem;">
        <div style="display:flex; align-items:center; gap:1rem;">
            <div style="width:52px; height:52px; border-radius:8px; background:var(--bg-ink, #ede5d5); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
                <img src="/public/assets/tiles/tile_barracks.png" alt="Dojo Militaire" style="width:44px; height:44px; object-fit:contain;">
            </div>
            <div>
                <h2 class="card-title" style="margin:0; font-size:1.3rem; display:flex; align-items:center; gap:0.6rem; color:var(--text-main);">
                    <span>🥋 Dojo Militaire & Caserne de Fief</span>
                    <span style="font-size:0.8rem; font-weight:700; padding:2px 8px; border-radius:12px; background:var(--red-soft, rgba(194,37,43,0.1)); border:1px solid var(--border-highlight, #c2252b); color:var(--red-primary, #c2252b);">
                        Niveau <?= $barracksLvl ?>
                    </span>
                </h2>
                <div style="font-size:0.85rem; color:var(--text-muted); margin-top:3px;">
                    Régiments du <strong><?= htmlspecialchars($userClanName) ?></strong> • Entraînez vos piquiers, tireurs mousquetaires, fiers samouraïs et champions de la garde.
                </div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <?php if ($famineUpkeep['famine_enabled']): ?>
                <span style="font-size:0.8rem; font-weight:700; color:<?= !empty($planet['famine_active']) ? '#b91c1c' : '#854d0e' ?>; background:<?= !empty($planet['famine_active']) ? '#fee2e2' : '#fef3c7' ?>; border:1px solid <?= !empty($planet['famine_active']) ? '#ef4444' : '#f59e0b' ?>; padding:0.35rem 0.75rem; border-radius:6px; display:inline-flex; align-items:center; gap:0.4rem;">
                    <?= !empty($planet['famine_active']) ? '💀 Famine Active (-' . $famineUpkeep['famine_rate'] . '%/h)' : '🍚 Vivres Élite : ' . $famineUpkeep['flour_consumption_per_hour'] . ' farine/h' ?>
                </span>
            <?php endif; ?>

            <?php if ($activeFeast && $activeFeast['feast_type'] === 'warriors'): ?>
                <span style="font-size:0.8rem; font-weight:700; color:#854d0e; background:#fef9c3; border:1px solid #facc15; padding:0.35rem 0.75rem; border-radius:6px; display:inline-flex; align-items:center; gap:0.4rem; box-shadow:0 2px 6px rgba(234,179,8,0.2);">
                    🍶 Banquet des Guerriers Actif &bull; Entraînement -<?= 10 + (int)$activeFeast['tenshu_level'] ?>%
                </span>
            <?php endif; ?>

            <?php if ($user['faction'] === 'vorash'): ?>
                <span style="font-size:0.8rem; font-weight:700; color:#991b1b; background:rgba(153,27,27,0.1); border:1px solid rgba(153,27,27,0.3); padding:0.35rem 0.75rem; border-radius:6px; display:inline-flex; align-items:center; gap:0.4rem;">
                    ⚡ Bonus Takeda : Vitesse d'entraînement +20%
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body" style="padding:1.25rem;">
        <?php if ($famineUpkeep['famine_enabled'] && !empty($planet['famine_active'])): ?>
            <div class="alert alert-danger mb-3 p-3 border-danger shadow-sm" style="border-left: 5px solid #dc2626; background: #fef2f2; border-radius:8px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-1">💀</span>
                        <div>
                            <h4 class="m-0 fw-bold text-danger">⚠️ Alerte Stratégique : Famine Féodale au Dojo !</h4>
                            <div class="text-dark small mt-1">
                                Vos stocks de Farine de Riz 🍚 sont réduits à néant. Sans vivres d'élite, vos <strong><?= $famineUpkeep['elite_units_count'] ?> soldats d'élite</strong> meurent de faim ou désertent (<strong>-<?= $famineUpkeep['famine_rate'] ?>% par heure</strong>).
                            </div>
                        </div>
                    </div>
                    <a href="/?page=building&code=grain_mill#craftSection" class="btn btn-sm btn-danger fw-bold" style="padding:0.4rem 0.9rem;">
                        🍚 Moudre de la Farine à la Meunerie &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($barracksLvl < 1): ?>
            <div style="text-align:center; padding:2.5rem; background:rgba(239,68,68,0.06); border:1px dashed var(--red-primary, #c2252b); border-radius:10px;">
                <div style="font-size:2.5rem; margin-bottom:0.5rem;">🏯</div>
                <h3 style="color:var(--red-primary, #c2252b); margin-bottom:0.5rem;">Dojo Militaire non construit</h3>
                <p style="color:var(--text-muted); margin-bottom:1.25rem; max-width:500px; margin-left:auto; margin-right:auto;">
                    Vous devez bâtir un Dojo Militaire dans votre cité pour forger des armes et entraîner les guerriers de votre domaine.
                </p>
                <a href="?page=city" class="btn btn-primary" style="padding:0.6rem 1.5rem;">
                    Bâtir le Dojo dans la Cité &rarr;
                </a>
            </div>
        <?php else: ?>
            <!-- File active d'entraînement avec Double Barre de Progression (Unité en cours & Lot Global) -->
            <?php if (!empty($queue)): ?>
                <div class="card mb-4 shadow-sm" style="border-top: 4px solid var(--red-primary, #c2252b); background:var(--bg-surface, #ffffff); border-radius:10px;">
                    <div class="card-header py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:linear-gradient(to right, rgba(194,37,43,0.06), transparent);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-2">⏳</span>
                            <div>
                                <h3 class="card-title m-0 fw-bold" style="font-size:1.05rem; color:var(--text-main);">
                                    Régiments en cours de formation au Dojo
                                </h3>
                                <div class="text-secondary small">
                                    Mobilisation progressive <strong>au fil de l'eau</strong> &bull; <?= count($queue) ?> ordre(s) d'enrôlement actif(s)
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-danger-lt fw-bold px-3 py-1">
                            Disponibilité Immédiate dans la Garnison
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($queue as $q): ?>
                                <div class="barracks-queue-card p-3 rounded border shadow-sm"
                                     style="background:var(--bg-surface, #ffffff); border-left: 4px solid var(--red-primary, #c2252b) !important;"
                                     data-started="<?= $q['started_at'] ?>"
                                     data-finishes="<?= $q['finishes_at'] ?>"
                                     data-unit-time="<?= $q['unit_train_time'] ?>"
                                     data-remaining="<?= $q['count'] ?>"
                                     data-total="<?= $q['total_count'] ?>"
                                     data-completed="<?= $q['completed_count'] ?>">

                                    <!-- Entête de la commande -->
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-2 lh-1"><?= $q['unit_icon'] ?></span>
                                            <div>
                                                <h4 class="m-0 fw-bold text-dark fs-3 d-flex align-items-center gap-2">
                                                    <span><?= htmlspecialchars($q['unit_name']) ?></span>
                                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1 fs-5">
                                                        Lot : <span class="queue-completed-count"><?= $q['completed_count'] ?></span> / <?= $q['total_count'] ?> prêts
                                                    </span>
                                                </h4>
                                                <div class="text-secondary small mt-1">
                                                    Cadence : <strong><?= $q['unit_train_time'] ?>s</strong> par guerrier &bull;
                                                    <span class="queue-remaining-badge text-danger fw-semibold"><?= $q['count'] ?> restant(s) à former</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-secondary fw-semibold">Fin totale estimée</div>
                                            <div class="queue-lot-timer text-danger fw-bold font-monospace fs-3">
                                                Calcul...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 1ère Barre : Unité en cours de création -->
                                    <div class="p-2 rounded mb-2" style="background: rgba(194, 37, 43, 0.04); border: 1px solid rgba(194, 37, 43, 0.15);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-dark fw-bold d-flex align-items-center gap-1">
                                                <span>⚡</span>
                                                <span>Guerrier en cours de formation (<span class="queue-current-unit-num"><?= $q['current_unit_number'] ?></span>/<?= $q['total_count'] ?>) :</span>
                                                <strong class="queue-unit-countdown font-monospace text-danger ms-1">--:--</strong>
                                            </span>
                                            <span class="badge bg-danger-lt fw-bold font-monospace queue-unit-pct"><?= $q['unit_pct'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 8px; background: rgba(0,0,0,0.08); border-radius: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger queue-unit-bar"
                                                 role="progressbar"
                                                 style="width: <?= $q['unit_pct'] ?>%;"
                                                 aria-valuenow="<?= $q['unit_pct'] ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <!-- 2ème Barre : Progression Globale du Lot -->
                                    <div class="p-2 rounded" style="background: rgba(32, 107, 196, 0.04); border: 1px solid rgba(32, 107, 196, 0.15);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-dark fw-bold d-flex align-items-center gap-1">
                                                <span>📦</span>
                                                <span>Progression globale du lot :</span>
                                                <span class="text-secondary fw-normal queue-lot-status ms-1">
                                                    <strong><span class="queue-lot-ready"><?= $q['completed_count'] ?></span></strong> sur <strong><?= $q['total_count'] ?></strong> guerriers mobilisés
                                                </span>
                                            </span>
                                            <span class="badge bg-primary-lt fw-bold font-monospace queue-lot-pct"><?= $q['lot_pct'] ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 10px; background: rgba(0,0,0,0.08); border-radius: 5px;">
                                            <div class="progress-bar bg-primary queue-lot-bar"
                                                 role="progressbar"
                                                 style="width: <?= $q['lot_pct'] ?>%;"
                                                 aria-valuenow="<?= $q['lot_pct'] ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <!-- Note au fil de l'eau -->
                                    <div class="d-flex align-items-center justify-content-between mt-2 pt-1 text-secondary" style="font-size: 0.78rem;">
                                        <span>💧 <em>Mobilisation au fil de l'eau : chaque guerrier achevé rejoint directement votre garnison sans attendre la fin du lot de <?= $q['total_count'] ?>.</em></span>
                                        <span class="badge bg-success-lt fw-semibold">✔ Déploiement instantané</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grille des Soldats & Nouveaux Visuels Féodaux -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">
                <?php foreach ($availableUnits as $u): ?>
                    <?php
                        // Image nommée d'après le nom du soldat
                        $imgFile = !empty($u['image']) ? $u['image'] : ($u['code'] . '.jpg');
                        $diskFile = __DIR__ . '/../public/assets/units/' . $imgFile;
                        if (file_exists($diskFile)) {
                            $imgSrc = '/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile);
                            $fullImg = $imgSrc;
                        } else {
                            $legacyDisk = __DIR__ . "/../public/assets/units/{$u['code']}.jpg";
                            if (file_exists($legacyDisk)) {
                                $imgSrc = "/public/assets/units/{$u['code']}.jpg?v=" . filemtime($legacyDisk);
                                $fullImg = $imgSrc;
                            } else {
                                $imgSrc = "/public/assets/units/{$u['code']}.svg";
                                $fullImg = $imgSrc;
                            }
                        }

                        $roleLabels = [
                            1 => 'Infanterie de ligne',
                            2 => 'Tir & Harcèlement',
                            3 => 'Assaut d\'élite',
                            4 => 'Garde du Daimyō'
                        ];
                        $roleText = $roleLabels[$u['tier']] ?? 'Guerrier Féodal';
                    ?>
                    <div class="card unit-card <?= !$u['can_train'] ? 'unit-locked' : '' ?>" style="margin:0; background:var(--bg-surface, #fdfbf7); overflow:hidden; border:1px solid var(--border-color); border-radius:10px; display:flex; flex-direction:column; box-shadow:0 6px 18px rgba(0,0,0,0.05); transition:transform 0.2s ease, box-shadow 0.2s ease;">

                        <!-- Illustration Grand Format du Guerrier (Style Feodal Washi) -->
                        <div style="position:relative; width:100%; height:240px; overflow:hidden; background:var(--bg-ink, #ede5d5); border-bottom:1px solid var(--border-color); cursor:pointer;"
                             onclick="openUnitLightbox('<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= $fullImg ?>', '<?= htmlspecialchars(addslashes($u['description'])) ?>', '<?= $roleText ?>', 'Rang <?= $u['tier'] ?>')"
                             title="Cliquer pour admirer l'illustration en grand format">

                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($u['name']) ?>" class="unit-img" style="width:100%; height:100%; object-fit:cover; object-position:top center; transition:transform 0.4s ease;">

                            <!-- Badge de Rang -->
                            <div style="position:absolute; top:10px; left:10px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid rgba(194,37,43,0.5); border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:var(--red-primary, #c2252b); box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                <?= $u['icon'] ?> Rang <?= $u['tier'] ?>
                            </div>

                            <!-- Badge Effectif Garnison -->
                            <div style="position:absolute; top:10px; right:46px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid rgba(22,101,52,0.5); border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:#166534; box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                🛡️ Garnison : <?= number_format($u['stationed_count']) ?>
                            </div>

                            <!-- Badge Transparence IA Prompts -->
                            <?= AiPromptHelper::renderBadge($imgFile, $u['name'], $fullImg) ?>

                            <!-- Bouton Agrandir Loupe -->
                            <div style="position:absolute; bottom:8px; right:8px; background:rgba(28,25,23,0.75); backdrop-filter:blur(4px); color:#ffffff; border-radius:4px; padding:3px 8px; font-size:0.7rem; display:flex; align-items:center; gap:4px; border:1px solid rgba(255,255,255,0.2);">
                                🔍 Vue détaillée
                            </div>
                        </div>

                        <!-- Titre & Rôle -->
                        <div class="card-header" style="padding:0.85rem 1.15rem; background:transparent; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h3 class="card-title" style="font-size:1.05rem; display:flex; align-items:center; gap:0.4rem; color:var(--text-main); margin:0; font-weight:700;">
                                    <span><?= htmlspecialchars($u['name']) ?></span>
                                </h3>
                                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">
                                    <?= $roleText ?>
                                </span>
                            </div>
                            <span style="font-size:1.25rem;"><?= $u['icon'] ?></span>
                        </div>

                        <div class="card-body" style="padding:1.15rem; display:flex; flex-direction:column; flex:1;">
                            <p style="font-size:0.82rem; line-height:1.4; color:var(--text-muted); margin-bottom:0.85rem; min-height:42px;">
                                <?= htmlspecialchars($u['description']) ?>
                            </p>

                            <!-- Caractéristiques Militaire Travian-Style -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.75rem; background:var(--bg-ink, #ede5d5); padding:0.6rem; border-radius:6px; margin-bottom:0.85rem; border:1px solid var(--border-color); color:var(--text-main);">
                                <div title="Puissance d'attaque en bataille">⚔️ Attaque : <strong style="color:var(--red-primary);"><?= $u['attack'] ?></strong></div>
                                <div title="Défense contre l'infanterie">🛡️ Df Infan : <strong><?= $u['def_infantry'] ?></strong></div>
                                <div title="Défense contre la cavalerie">🐎 Df Caval : <strong><?= $u['def_mech'] ?></strong></div>
                                <div title="Vitesse de marche sur la carte">🏃 Vitesse : <strong><?= $u['speed'] ?></strong></div>
                                <div title="Capacité d'emport de ressources pillées">🎒 Fret : <strong><?= $u['cargo_capacity'] ?></strong></div>
                                <div title="Temps d'entraînement unitaire">⏱️ Vitesse : <strong><?= $u['effective_train_time'] ?>s</strong></div>
                            </div>

                            <!-- Coût de Recrutement -->
                            <div class="cost-row" style="margin:0.25rem 0 0.85rem 0; display:flex; gap:0.75rem; font-size:0.85rem; font-weight:600; flex-wrap:wrap;">
                                <div class="cost-item" title="Bois de Cèdre"><span style="color:var(--res-metal);">🪵</span> <?= number_format($u['metal_cost']) ?></div>
                                <div class="cost-item" title="Pierre de Taille"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($u['crystal_cost']) ?></div>
                                <div class="cost-item" title="Riz Impérial"><span style="color:var(--res-deut);">🌾</span> <?= number_format($u['deuterium_cost']) ?></div>
                                <?php if (!empty($u['rice_flour_cost'])):
                                    $hasEnoughFlour = (($planet['rice_flour'] ?? 0) >= $u['rice_flour_cost']);
                                ?>
                                    <div class="cost-item <?= !$hasEnoughFlour ? 'text-danger' : '' ?>" title="Farine de Riz (Rations de campagne)">
                                        <span style="color:#0284c7;">🍚</span> <?= number_format($u['rice_flour_cost']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Formulaire de Recrutement -->
                            <div style="margin-top:auto; padding-top:0.75rem; border-top:1px dashed var(--border-color);">
                                <?php if ($u['can_train']): ?>
                                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                        <!-- Sélecteur rapide -->
                                        <div style="display:flex; gap:0.35rem; font-size:0.75rem;">
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 5)">+5</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 10)">+10</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 25)">+25</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 50)">+50</button>
                                        </div>
                                        <div style="display:flex; gap:0.5rem;">
                                            <input type="number" id="unit-count-<?= $u['code'] ?>" min="1" max="1000" value="5"
                                                   style="width:75px; background:var(--bg-card); border:1px solid var(--border-color); color:var(--text-main); padding:0.45rem; border-radius:6px; text-align:center; font-weight:bold; font-size:0.9rem;">
                                            <button class="btn btn-primary" style="flex:1; font-size:0.85rem; font-weight:700; padding:0.45rem 0.75rem; display:flex; align-items:center; justify-content:center; gap:0.4rem;"
                                                    onclick="trainTroops('<?= $u['code'] ?>')">
                                                <span>🥋</span> Entraîner
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align:center; padding:0.6rem; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:6px; font-size:0.8rem; color:var(--red-primary, #c2252b); font-weight:700;">
                                        🔒 Requiert Dojo Niveau <?= $u['required_barracks_level'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agrandissement d'Illustration (Lightbox Washi) -->
<div id="unit-lightbox-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(12,10,9,0.85); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;" onclick="closeUnitLightbox(event)">
    <div style="position:relative; max-width:560px; width:100%; background:var(--bg-surface, #fdfbf7); border-radius:12px; border:2px solid var(--border-highlight, #c2252b); overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
        <button onclick="closeUnitLightbox()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:50%; width:34px; height:34px; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10;">
            &times;
        </button>
        <div style="width:100%; height:380px; background:#1c1917; overflow:hidden;">
            <img id="lightbox-img" src="" alt="" style="width:100%; height:100%; object-fit:cover; object-position:center top;">
        </div>
        <div style="padding:1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <h3 id="lightbox-title" style="margin:0; font-size:1.2rem; color:var(--text-main);">Titre</h3>
                <span id="lightbox-badge" style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:10px; background:var(--red-soft); color:var(--red-primary); border:1px solid var(--border-highlight);">Rang</span>
            </div>
            <div id="lightbox-role" style="font-size:0.8rem; color:var(--text-muted); font-style:italic; margin-bottom:0.75rem;">Rôle</div>
            <p id="lightbox-desc" style="font-size:0.85rem; line-height:1.5; color:var(--text-main); margin:0;">Description</p>
        </div>
    </div>
</div>

<script>
function setRecruits(unitCode, amount) {
    const input = document.getElementById(`unit-count-${unitCode}`);
    if (input) {
        input.value = amount;
    }
}

function openUnitLightbox(name, imgSrc, desc, role, badge) {
    document.getElementById('lightbox-title').textContent = name;
    document.getElementById('lightbox-img').src = imgSrc;
    document.getElementById('lightbox-desc').textContent = desc;
    document.getElementById('lightbox-role').textContent = role;
    document.getElementById('lightbox-badge').textContent = badge;
    const modal = document.getElementById('unit-lightbox-modal');
    modal.style.display = 'flex';
}

function closeUnitLightbox(e) {
    const modal = document.getElementById('unit-lightbox-modal');
    modal.style.display = 'none';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeUnitLightbox();
    }
});

async function trainTroops(unitCode) {
    const input = document.getElementById(`unit-count-${unitCode}`);
    const count = parseInt(input.value, 10);
    if (isNaN(count) || count <= 0) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Veuillez spécifier un effectif valide.', 'warning');
        } else {
            alert('Veuillez spécifier un effectif valide.');
        }
        return;
    }

    const formData = new FormData();
    formData.append('unit_code', unitCode);
    formData.append('count', count);

    try {
        const res = await fetch('/api/barracks.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer l\'entraînement.', 'error');
            } else {
                alert(data.error || 'Impossible de lancer l\'entraînement.');
            }
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Erreur de communication avec le dojo militaire.', 'error');
        } else {
            alert('Erreur de communication avec le dojo militaire.');
        }
    }
}

// ⏱️ Mise à jour en temps réel de la Double Barre de Progression (Unité en cours & Lot Global)
function formatTime(seconds) {
    if (seconds <= 0) return "00:00";
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
        return h + "h " + (m < 10 ? "0" : "") + m + "m " + (s < 10 ? "0" : "") + s + "s";
    }
    return (m < 10 ? "0" : "") + m + "m " + (s < 10 ? "0" : "") + s + "s";
}

function updateBarracksDoubleProgress() {
    const cards = document.querySelectorAll('.barracks-queue-card');
    if (!cards.length) return;

    const now = Math.floor(Date.now() / 1000);
    let shouldReload = false;

    cards.forEach(card => {
        const startedAt = parseInt(card.dataset.started, 10);
        const finishesAt = parseInt(card.dataset.finishes, 10);
        const unitTime = Math.max(1, parseInt(card.dataset.unitTime, 10));
        const totalCount = Math.max(1, parseInt(card.dataset.total, 10));
        const initialCompleted = parseInt(card.dataset.completed, 10);

        if (now < startedAt) {
            // Ordre en attente dans la file
            const waitTime = startedAt - now;
            const lotTimer = card.querySelector('.queue-lot-timer');
            if (lotTimer) lotTimer.textContent = "En attente (" + formatTime(waitTime) + ")";
            const unitCountdown = card.querySelector('.queue-unit-countdown');
            if (unitCountdown) unitCountdown.textContent = "En attente...";
            const unitPct = card.querySelector('.queue-unit-pct');
            if (unitPct) unitPct.textContent = "0%";
            const unitBar = card.querySelector('.queue-unit-bar');
            if (unitBar) unitBar.style.width = "0%";
            return;
        }

        if (now >= finishesAt) {
            // Lot entièrement terminé
            const lotTimer = card.querySelector('.queue-lot-timer');
            if (lotTimer) lotTimer.textContent = "Terminé !";
            const unitCountdown = card.querySelector('.queue-unit-countdown');
            if (unitCountdown) unitCountdown.textContent = "Terminé !";
            const unitPct = card.querySelector('.queue-unit-pct');
            if (unitPct) unitPct.textContent = "100%";
            const unitBar = card.querySelector('.queue-unit-bar');
            if (unitBar) unitBar.style.width = "100%";
            const lotPct = card.querySelector('.queue-lot-pct');
            if (lotPct) lotPct.textContent = "100%";
            const lotBar = card.querySelector('.queue-lot-bar');
            if (lotBar) lotBar.style.width = "100%";
            shouldReload = true;
            return;
        }

        // Commande en cours d'exécution
        const totalElapsed = now - startedAt;
        const currentCompletedInBatch = Math.min(totalCount, Math.floor(totalElapsed / unitTime));
        const currentRemainingInBatch = Math.max(0, totalCount - currentCompletedInBatch);

        // Détection d'une nouvelle unité terminée "au fil de l'eau"
        if (currentCompletedInBatch > initialCompleted) {
            shouldReload = true;
        }

        // Unité en cours
        const unitElapsed = totalElapsed % unitTime;
        const unitRemaining = Math.max(0, unitTime - unitElapsed);
        const unitPct = Math.min(100, Math.max(0, (unitElapsed / unitTime) * 100));
        const currentUnitNum = Math.min(totalCount, currentCompletedInBatch + 1);

        // Lot global (unités achevées + fraction de l'unité courante)
        const lotPct = Math.min(100, Math.max(0, ((currentCompletedInBatch + (unitElapsed / unitTime)) / totalCount) * 100));
        const lotRemaining = Math.max(0, finishesAt - now);

        // Rafraîchissement DOM
        const lotTimerEl = card.querySelector('.queue-lot-timer');
        if (lotTimerEl) lotTimerEl.textContent = formatTime(lotRemaining);

        const unitCountdownEl = card.querySelector('.queue-unit-countdown');
        if (unitCountdownEl) unitCountdownEl.textContent = formatTime(unitRemaining) + " (" + unitElapsed + "s / " + unitTime + "s)";

        const unitPctEl = card.querySelector('.queue-unit-pct');
        if (unitPctEl) unitPctEl.textContent = unitPct.toFixed(0) + "%";

        const unitBarEl = card.querySelector('.queue-unit-bar');
        if (unitBarEl) unitBarEl.style.width = unitPct.toFixed(1) + "%";

        const currentUnitNumEl = card.querySelector('.queue-current-unit-num');
        if (currentUnitNumEl) currentUnitNumEl.textContent = currentUnitNum;

        const lotPctEl = card.querySelector('.queue-lot-pct');
        if (lotPctEl) lotPctEl.textContent = lotPct.toFixed(0) + "%";

        const lotBarEl = card.querySelector('.queue-lot-bar');
        if (lotBarEl) lotBarEl.style.width = lotPct.toFixed(1) + "%";

        const completedCountEl = card.querySelector('.queue-completed-count');
        if (completedCountEl) completedCountEl.textContent = currentCompletedInBatch;

        const lotReadyEl = card.querySelector('.queue-lot-ready');
        if (lotReadyEl) lotReadyEl.textContent = currentCompletedInBatch;

        const remainingBadgeEl = card.querySelector('.queue-remaining-badge');
        if (remainingBadgeEl) remainingBadgeEl.textContent = currentRemainingInBatch + " restant(s) à former";
    });

    if (shouldReload) {
        setTimeout(() => { window.location.reload(); }, 1200);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateBarracksDoubleProgress();
    setInterval(updateBarracksDoubleProgress, 1000);
});
</script>
