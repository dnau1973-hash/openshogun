<?php
/**
 * Vue de Gestion et Déploiement des Expéditions Militaires (OpenShogun)
 */
require_once __DIR__ . '/../core/FleetEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/HeroEngine.php';

$fleetEngine = new FleetEngine();
$planetEngine = new PlanetEngine();
$heroEngine = new HeroEngine();
$db = Database::getConnection();

// Samouraï Héros Champion
$heroData = $heroEngine->getHeroByUserId($user['id']);
$canDeployHero = false;
$heroEffectiveStats = null;
if ($heroData && $heroData['status'] === 'home' && (int)$heroData['health'] > 0 && (int)$heroData['current_planet_id'] === (int)$planet['id']) {
    $canDeployHero = true;
    $heroEffectiveStats = $heroEngine->calculateEffectiveStats($heroData);
}

// Cavalerie, convois et engins de siège stationnés
$stmtShips = $db->prepare("
    SELECT ps.ship_code, ps.count, s.name, s.speed, s.cargo_capacity, s.attack, s.defense, s.shield 
    FROM planet_ships ps 
    JOIN ships s ON ps.ship_code = s.code 
    WHERE ps.planet_id = ? AND ps.count > 0
");
$stmtShips->execute([$planet['id']]);
$stationedShips = $stmtShips->fetchAll();

// Guerriers et soldats du Dojo stationnés
$stmtUnits = $db->prepare("
    SELECT pu.unit_code, pu.count, u.name, u.icon, u.speed, u.cargo_capacity, u.attack, u.def_infantry, u.def_mech 
    FROM planet_units pu 
    JOIN units u ON pu.unit_code = u.code 
    WHERE pu.planet_id = ? AND pu.count > 0
");
$stmtUnits->execute([$planet['id']]);
$stationedUnits = $stmtUnits->fetchAll();

// Toutes les marches et expéditions en cours du Daimyō
$stmtMissions = $db->prepare("
    SELECT fm.*, 
           p1.name as source_name, p1.coord_x as sx, p1.coord_y as sy,
           COALESCE(p2.name, CONCAT('Oasis ', o.name)) as target_name, 
           COALESCE(p2.coord_x, o.coord_x) as tx, 
           COALESCE(p2.coord_y, o.coord_y) as ty
    FROM fleet_missions fm 
    JOIN planets p1 ON fm.source_planet_id = p1.id 
    LEFT JOIN planets p2 ON fm.target_planet_id = p2.id 
    LEFT JOIN oases o ON fm.target_oasis_id = o.id
    WHERE fm.user_id = ? AND fm.status IN ('en_route', 'returning') 
    ORDER BY fm.arrival_time ASC
");
$stmtMissions->execute([$user['id']]);
$activeMissions = $stmtMissions->fetchAll();

// Liste des fiefs et domaines connus pour cible rapide
$stmtTargets = $db->prepare("SELECT id, name, coord_x, coord_y FROM planets WHERE id != ? ORDER BY id ASC LIMIT 20");
$stmtTargets->execute([$planet['id']]);
$knownPlanets = $stmtTargets->fetchAll();

// Liste des oasis sauvages et naturelles
$stmtOases = $db->prepare("SELECT id, name, coord_x, coord_y, oasis_type, bonus_wood, bonus_stone, bonus_rice, owner_planet_id FROM oases ORDER BY id ASC");
$stmtOases->execute();
$knownOases = $stmtOases->fetchAll();

$preselectedTargetType = $_GET['target_type'] ?? 'planet';
$preselectedTarget = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
$preselectedMission = $_GET['mission'] ?? 'raid';
?>

<div class="grid-main">
    <!-- Déploiement d'armée féodale et engins -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🚩 Expédition Militaire & Convois Provinciaux</h2>
            <span style="font-size:0.85rem; color:var(--text-muted);">Fief d'attache : <?= htmlspecialchars($planet['name']) ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($stationedShips) && empty($stationedUnits) && !$canDeployHero): ?>
                <div style="text-align:center; padding:2rem; background:rgba(255,255,255,0.02); border-radius:8px;">
                    <p style="color:var(--text-muted); margin-bottom:1rem;">Aucun régiment de guerriers, engin de siège ni héros samouraï n'est disponible dans votre garnison.</p>
                    <div style="display:flex; justify-content:center; gap:1rem;">
                        <a href="?page=shipyard" class="btn btn-primary">Atelier de Siège & Écuries</a>
                        <a href="?page=barracks" class="btn btn-secondary">Dojo Militaire</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="fleetForm" onsubmit="event.preventDefault(); submitFleet();">
                    <!-- Étape 1 : Cavalerie et Engins de Siège -->
                    <?php if (!empty($stationedShips)): ?>
                        <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">🐎 1. Cavalerie, Convois & Engins de Siège</h3>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem;">
                            <?php foreach ($stationedShips as $s): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.5rem 0.75rem; border-radius:6px;">
                                    <div>
                                        <strong><?= htmlspecialchars($s['name']) ?></strong>
                                        <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">
                                            (Dispo : <span id="max-<?= $s['ship_code'] ?>"><?= $s['count'] ?></span>)
                                        </span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.2rem 0.5rem;" 
                                                onclick="document.getElementById('ship-<?= $s['ship_code'] ?>').value = <?= $s['count'] ?>;">
                                            Max
                                        </button>
                                        <input type="number" id="ship-<?= $s['ship_code'] ?>" name="fleet[<?= $s['ship_code'] ?>]" 
                                                min="0" max="<?= $s['count'] ?>" value="0"
                                                style="width:70px; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.3rem; border-radius:4px; text-align:center;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Étape 2 : Guerriers & Régiments du Dojo (Style Travian) -->
                    <?php if (!empty($stationedUnits)): ?>
                        <h3 style="font-size:0.95rem; color:#4ade80; margin-bottom:0.75rem;">⚔️ 2. Régiments de Guerriers & Samouraïs</h3>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1.5rem;">
                            <?php foreach ($stationedUnits as $u): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.5rem 0.75rem; border-radius:6px; border-left:3px solid #dc2626;">
                                    <div>
                                        <span style="font-size:1.1rem; margin-right:0.3rem;"><?= $u['icon'] ?></span>
                                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                                        <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">
                                            (Garnison : <span id="max-<?= $u['unit_code'] ?>"><?= $u['count'] ?></span>)
                                        </span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.7rem; padding:0.2rem 0.5rem;" 
                                                onclick="document.getElementById('ship-<?= $u['unit_code'] ?>').value = <?= $u['count'] ?>;">
                                            Max
                                        </button>
                                        <input type="number" id="ship-<?= $u['unit_code'] ?>" name="fleet[<?= $u['unit_code'] ?>]" 
                                                min="0" max="<?= $u['count'] ?>" value="0"
                                                style="width:70px; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.3rem; border-radius:4px; text-align:center;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Étape 3 : Héros Samouraï Champion -->
                    <?php if ($canDeployHero): ?>
                        <h3 style="font-size:0.95rem; color:#f59e0b; margin-bottom:0.75rem;">🥋 3. Champion Samouraï (Héros de Guerre)</h3>
                        <div style="background:linear-gradient(135deg, rgba(234,179,8,0.12), rgba(15,23,42,0.6)); border:1px solid rgba(234,179,8,0.35); border-radius:8px; padding:0.85rem; margin-bottom:1.5rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <div style="width:44px; height:44px; border-radius:50%; border:2px solid #eab308; overflow:hidden; background:#000; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                                        🥋
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#f8fafc; font-size:0.95rem;">
                                            <?= htmlspecialchars($heroData['name']) ?> 
                                            <span style="background:rgba(234,179,8,0.25); color:#fde047; padding:0.1rem 0.4rem; border-radius:4px; font-size:0.75rem; border:1px solid rgba(234,179,8,0.4);">Niv. <?= $heroData['level'] ?></span>
                                        </div>
                                        <div style="font-size:0.8rem; color:#94a3b8; display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:0.25rem;">
                                            <span style="color:#ef4444;">⚔️ Force : <strong><?= $heroEffectiveStats['combat_strength'] ?></strong></span>
                                            <span style="color:#f97316;">🔥 Attaque armée : <strong>+<?= $heroEffectiveStats['offense_bonus_pct'] ?? 0 ?>%</strong></span>
                                            <span style="color:#10b981;">🛡️ Défense garnison : <strong>+<?= $heroEffectiveStats['defense_bonus_pct'] ?? 0 ?>%</strong></span>
                                            <span style="color:#22c55e;">❤️ Vie : <strong><?= $heroData['health'] ?>%</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer; background:rgba(234,179,8,0.2); padding:0.5rem 0.9rem; border-radius:6px; border:1px solid #eab308; font-weight:600; font-size:0.85rem; color:#fef08a; transition:all 0.2s;">
                                    <input type="checkbox" id="deploy_hero" name="has_hero" value="1" style="width:18px; height:18px; cursor:pointer; accent-color:#eab308;">
                                    Accompagner l'expédition
                                </label>
                            </div>
                        </div>
                    <?php elseif ($heroData && $heroData['status'] !== 'home'): ?>
                        <div style="background:rgba(0,0,0,0.2); border:1px dashed rgba(255,255,255,0.1); border-radius:6px; padding:0.6rem 0.85rem; margin-bottom:1.5rem; font-size:0.8rem; color:var(--text-muted);">
                            🥋 Samouraï Héros <strong><?= htmlspecialchars($heroData['name']) ?></strong> : Indisponible (En mission ou en aventure).
                        </div>
                    <?php elseif ($heroData && (int)$heroData['health'] <= 0): ?>
                        <div style="background:rgba(220,38,38,0.1); border:1px dashed #ef4444; border-radius:6px; padding:0.6rem 0.85rem; margin-bottom:1.5rem; font-size:0.8rem; color:#fca5a5;">
                            🥋 Samouraï Héros <strong><?= htmlspecialchars($heroData['name']) ?></strong> est tombé au combat. <a href="?page=hero" style="color:#eab308; text-decoration:underline;">Accomplir le rituel de résurrection</a>.
                        </div>
                    <?php endif; ?>

                    <!-- Étape 4 : Destination -->
                    <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">🗾 4. Destination (Fief Provincial ou Oasis Naturelle)</h3>
                    <div style="margin-bottom:1.5rem;">
                        <select id="targetSelect" style="width:100%; background:rgba(15,23,42,0.9); border:1px solid var(--border-color); color:#fff; padding:0.6rem; border-radius:6px; margin-bottom:0.75rem;">
                            <option value="">-- Sélectionner une destination féodale ou oasis --</option>
                            <optgroup label="🏯 Fiefs & Domaines Provinciaux">
                                <?php foreach ($knownPlanets as $kp): ?>
                                    <option value="planet:<?= $kp['id'] ?>" <?= ($preselectedTargetType === 'planet' && $preselectedTarget === (int)$kp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kp['name']) ?> [<?= $kp['coord_x'] ?> : <?= $kp['coord_y'] ?>]
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🌿 Oasis Naturelles & Fiefs Sauvages (Bonus de Récoltes)">
                                <?php foreach ($knownOases as $ko): 
                                    $bText = '';
                                    if ($ko['bonus_rice'] > 0) $bText .= "+{$ko['bonus_rice']}% Riz ";
                                    if ($ko['bonus_wood'] > 0) $bText .= "+{$ko['bonus_wood']}% Bois ";
                                    if ($ko['bonus_stone'] > 0) $bText .= "+{$ko['bonus_stone']}% Pierre ";
                                    $statusOasis = !empty($ko['owner_planet_id']) ? ' [Occupée]' : ' [Sauvage]';
                                ?>
                                    <option value="oasis:<?= $ko['id'] ?>" <?= ($preselectedTargetType === 'oasis' && $preselectedTarget === (int)$ko['id']) ? 'selected' : '' ?>>
                                        🌿 <?= htmlspecialchars($ko['name']) ?> [<?= $ko['coord_x'] ?> : <?= $ko['coord_y'] ?>] (<?= trim($bText) ?>)<?= $statusOasis ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Étape 5 : Ordre de Mission -->
                    <h3 style="font-size:0.95rem; color:#fff; margin-bottom:0.75rem;">⚔️ 5. Ordre Tactique de Marche</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:0.5rem; margin-bottom:1.5rem;">
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="raid" <?= ($preselectedMission === 'raid') ? 'checked' : '' ?>>
                            <span>⚔️ Raid de Pillage</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="attack" <?= ($preselectedMission === 'attack') ? 'checked' : '' ?>>
                            <span>💥 Assaut de Siège</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="occupy" <?= ($preselectedMission === 'occupy') ? 'checked' : '' ?>>
                            <span>🚩 Occuper / Garnison</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="spy" <?= ($preselectedMission === 'spy') ? 'checked' : '' ?>>
                            <span>🥷 Infiltration Shinobi</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="transport" <?= ($preselectedMission === 'transport') ? 'checked' : '' ?>>
                            <span>🐂 Convoi de Vivres</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; cursor:pointer;">
                            <input type="radio" name="mission_type" value="colonize" <?= ($preselectedMission === 'colonize') ? 'checked' : '' ?>>
                            <span>🏯 Fonder un Fief</span>
                        </label>
                    </div>

                    <!-- Étape 6 : Chargement de Fret (Optionnel pour transport) -->
                    <div style="background:rgba(0,0,0,0.25); padding:0.75rem; border-radius:6px; margin-bottom:1.5rem;">
                        <h4 style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">Ressources à convoyer (pour convoi de vivres) :</h4>
                        <div style="display:flex; gap:0.75rem;">
                            <input type="number" id="cargo_metal" placeholder="🪵 Bois" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                            <input type="number" id="cargo_crystal" placeholder="🪨 Pierre" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                            <input type="number" id="cargo_deuterium" placeholder="🌾 Riz" min="0" value="0" style="width:33%; background:rgba(0,0,0,0.6); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem; font-weight:700; background: linear-gradient(135deg, #b91c1c, #dc2626); border-color:#ef4444;">
                        🚩 Lancer l'Expédition Féodale
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mouvements Actifs -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏇 Troupes & Convois en Marche</h3>
            </div>
            <div class="card-body">
                <?php if (empty($activeMissions)): ?>
                    <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1.5rem 0;">Aucune troupe ni convoi en marche.</p>
                <?php else: ?>
                    <?php foreach ($activeMissions as $m): ?>
                        <?php 
                            $isOutbound = ($m['status'] === 'en_route');
                            $targetTime = $isOutbound ? $m['arrival_time'] : $m['return_time'];
                            $fleetData = json_decode($m['fleet_data'], true) ?: [];
                            $missionLabel = match($m['mission_type']) {
                                'raid' => '⚔️ RAID',
                                'attack' => '💥 SIÈGE',
                                'occupy' => '🚩 OCCUPATION',
                                'spy' => '🥷 SHINOBI',
                                'transport' => '🐂 CONVOI',
                                'colonize' => '🏯 EXPANSION',
                                default => strtoupper($m['mission_type'])
                            };
                        ?>
                        <div class="queue-item" style="flex-direction:column; align-items:flex-start; gap:0.4rem;">
                            <div style="display:flex; justify-content:space-between; width:100%; font-size:0.85rem;">
                                <strong style="color:<?= $isOutbound ? '#dc2626' : '#4ade80' ?>;">
                                    <?= $isOutbound ? '↗️ ' : '↙️ ' ?><?= $missionLabel ?>
                                </strong>
                                <span class="queue-timer" data-countdown="<?= $targetTime ?>">Calcul...</span>
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">
                                Destination : <?= htmlspecialchars($m['target_name']) ?> [<?= $m['tx'] ?> : <?= $m['ty'] ?>]
                            </div>
                            <div style="font-size:0.75rem; color:#94a3b8; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                <span>Effectif : <?= array_sum($fleetData) ?> guerriers & engins</span>
                                <?php if (!empty($m['has_hero'])): ?>
                                    <span style="background:rgba(234,179,8,0.2); color:#fde047; padding:0.1rem 0.4rem; border-radius:4px; font-weight:600; font-size:0.7rem; border:1px solid rgba(234,179,8,0.4);">🥋 Samouraï Héros</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function submitFleet() {
    const rawTarget = document.getElementById('targetSelect').value;
    if (!rawTarget) {
        showModalAlert('Veuillez sélectionner un fief ou une oasis de destination.', 'warning');
        return;
    }

    const missionTypeEl = document.querySelector('input[name="mission_type"]:checked');
    const missionType = missionTypeEl ? missionTypeEl.value : 'raid';

    const formData = new FormData();
    const parts = rawTarget.split(':');
    if (parts.length === 2) {
        if (parts[0] === 'oasis') {
            formData.append('target_oasis_id', parts[1]);
        } else {
            formData.append('target_planet_id', parts[1]);
        }
    } else {
        formData.append('target_planet_id', rawTarget);
    }
    formData.append('mission_type', missionType);

    // Vaisseaux et Troupes
    let hasForces = false;
    document.querySelectorAll('input[name^="fleet["]').forEach(inp => {
        const cnt = parseInt(inp.value, 10);
        if (cnt > 0) {
            formData.append(inp.name, cnt);
            hasForces = true;
        }
    });

    // Samouraï Champion Héros
    const heroCheckbox = document.getElementById('deploy_hero');
    if (heroCheckbox && heroCheckbox.checked) {
        formData.append('has_hero', '1');
        hasForces = true;
    }

    if (!hasForces) {
        showModalAlert('Veuillez sélectionner au moins un régiment, engin de siège ou votre héros samouraï à déployer.', 'warning');
        return;
    }

    formData.append('cargo_metal', document.getElementById('cargo_metal').value);
    formData.append('cargo_crystal', document.getElementById('cargo_crystal').value);
    formData.append('cargo_deuterium', document.getElementById('cargo_deuterium').value);

    try {
        const res = await fetch('/api/fleet.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message || 'Expédition militaire lancée avec succès !', 'success', 'Ordre de Marche Transmis');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de lancer cette expédition.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec vos généraux.', 'error');
    }
}
</script>

