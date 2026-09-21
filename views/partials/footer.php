</div> <!-- Fin .container -->

<!-- Modale Universelle d'Amélioration (Bâtiments et Parcelles) -->
<div class="modal-overlay" id="upgradeModal">
    <div class="modal-card">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Amélioration</h3>
            <button onclick="closeUpgradeModal()" class="modal-close-btn">&times;</button>
        </div>
        <div class="card-body">
            <p id="modalTargetLevel" style="font-weight:700; color:#dc2626; margin-bottom:1rem;"></p>
            
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">Ressources requises :</div>
            <div class="cost-row">
                <div class="cost-item"><span style="color:var(--res-metal);">🪵</span> <span id="modalCostMetal">0</span></div>
                <div class="cost-item"><span style="color:var(--res-crystal);">🪨</span> <span id="modalCostCrystal">0</span></div>
                <div class="cost-item"><span style="color:var(--res-deut);">🌾</span> <span id="modalCostDeut">0</span></div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin:1rem 0; font-size:0.9rem;">
                <span style="color:var(--text-muted);">Temps de travaux :</span>
                <span id="modalDuration" style="font-weight:700; color:#fff; font-family:monospace;">00:00:00</span>
            </div>

            <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem;">
                <button class="btn btn-secondary" onclick="closeUpgradeModal()">Fermer</button>
                <button class="btn btn-primary" id="modalConfirmBtn">Lancer les travaux</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale Personnalisée pour Messages, Alertes & Confirmations (Remplace alert/confirm) -->
<div class="modal-overlay" id="customAlertModal">
    <div class="modal-card alert-modal-card" id="customAlertCard">
        <div class="card-header">
            <h3 class="card-title" id="customAlertTitle">Décret du Shogunat</h3>
            <button onclick="closeCustomAlert()" class="modal-close-btn">&times;</button>
        </div>
        <div class="card-body">
            <div class="alert-modal-content">
                <span class="alert-modal-icon" id="customAlertIcon">ℹ️</span>
                <p class="alert-modal-text" id="customAlertText"></p>
            </div>
            <div class="alert-modal-actions" id="customAlertActions">
                <button class="btn btn-primary" id="customAlertBtnOk">Compris</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale d'Annonces & Notifications des Fonctionnalités -->
<?php require_once __DIR__ . '/announcement_modal.php'; ?>

<!-- Modale Universelle de la Fiche Daimyō / Profil (Style Washi Féodal) -->
<div class="modal-overlay" id="playerProfileModal">
    <div class="modal-card modal-card-lg">
        <!-- En-tête Profil -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <span id="profAvatar" style="font-size:2.5rem; filter:drop-shadow(0 2px 6px rgba(185,28,28,0.3));">🏯</span>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 id="profUsername" style="font-size:1.35rem; font-weight:800; color:#1c1917; margin:0;">Daimyō</h2>
                        <span id="profOnlineBadge" style="font-size:0.7rem; padding:0.15rem 0.45rem; border-radius:4px; font-weight:700;"></span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-1" style="font-size:0.82rem;">
                        <span id="profFactionBadge" class="faction-badge"></span>
                        <span id="profAlliance" style="color:var(--text-muted); font-weight:600;"></span>
                        <span style="color:#b45309; font-weight:800;" id="profRank">Rang #1</span>
                    </div>
                </div>
            </div>
            <button onclick="closePlayerProfileModal()" class="modal-close-btn" title="Fermer">&times;</button>
        </div>

        <!-- Corps Scrollable du Profil -->
        <div class="modal-body">
            <!-- Vitrine des Médailles de Prestige (Style Travian) -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 style="color:#b45309; font-size:1rem; font-weight:800; margin:0; display:flex; align-items:center; gap:0.4rem;">
                        <span>🎖️</span> Vitrine des Médailles d'Honneur
                    </h3>
                    <span id="profMedalsCount" style="font-size:0.78rem; color:var(--text-muted); font-weight:600;">0 distinction(s)</span>
                </div>
                <div id="profMedalsList" style="display:flex; gap:0.75rem; flex-wrap:wrap; background:#ffffff; border:1px solid var(--border-color); padding:1rem; border-radius:8px; min-height:60px; align-items:center; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <!-- Les médailles s'injecteront ici -->
                </div>
            </div>

            <!-- Devise / Chronique du Daimyō -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 style="color:#b91c1c; font-size:1rem; font-weight:800; margin:0; display:flex; align-items:center; gap:0.4rem;">
                        <span>📜</span> Devise & Chronique du Daimyō
                    </h3>
                    <button id="profEditBioBtn" onclick="toggleBioEdit()" class="btn btn-secondary" style="display:none; font-size:0.75rem; padding:0.25rem 0.65rem; border-color:#b91c1c; color:#b91c1c;">
                        ✏️ Modifier ma devise
                    </button>
                </div>
                <div id="profBioView" style="background:#ffffff; border:1px solid var(--border-color); border-left:4px solid #b91c1c; padding:0.85rem 1rem; border-radius:0 8px 8px 0; color:#1c1917; font-size:0.9rem; line-height:1.6; font-style:italic; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <!-- Bio texte -->
                </div>
                <div id="profBioEditContainer" style="display:none; margin-top:0.5rem;">
                    <textarea id="profBioInput" class="form-control" rows="3" maxlength="1000" style="font-size:0.85rem;"></textarea>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button onclick="toggleBioEdit()" class="btn btn-secondary" style="font-size:0.75rem; padding:0.35rem 0.75rem;">Annuler</button>
                        <button onclick="saveBio()" class="btn btn-primary" style="font-size:0.75rem; padding:0.35rem 0.75rem;">Enregistrer</button>
                    </div>
                </div>
            </div>

            <!-- Statistiques de Guerre de la Semaine -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;">
                <div style="background:#ffffff; border:1px solid var(--border-color); border-top:3px solid #b91c1c; border-radius:8px; padding:0.75rem; text-align:center; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Progression Semaine</div>
                    <div id="profStatProg" style="font-size:1.15rem; font-weight:800; color:#b91c1c; margin-top:0.2rem;">+0 pts</div>
                </div>
                <div style="background:#ffffff; border:1px solid var(--border-color); border-top:3px solid #dc2626; border-radius:8px; padding:0.75rem; text-align:center; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Points Conquête</div>
                    <div id="profStatAtt" style="font-size:1.15rem; font-weight:800; color:#dc2626; margin-top:0.2rem;">0 pts</div>
                </div>
                <div style="background:#ffffff; border:1px solid var(--border-color); border-top:3px solid #15803d; border-radius:8px; padding:0.75rem; text-align:center; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Points Défense</div>
                    <div id="profStatDef" style="font-size:1.15rem; font-weight:800; color:#15803d; margin-top:0.2rem;">0 pts</div>
                </div>
                <div style="background:#ffffff; border:1px solid var(--border-color); border-top:3px solid #7e22ce; border-radius:8px; padding:0.75rem; text-align:center; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Riz Pillé Hebdo</div>
                    <div id="profStatRaid" style="font-size:1.15rem; font-weight:800; color:#7e22ce; margin-top:0.2rem;">0</div>
                </div>
            </div>

            <!-- Territoire & Fiefs Recensés -->
            <div>
                <h3 style="color:#1c1917; font-size:1rem; font-weight:800; margin:0 0 0.75rem 0; display:flex; align-items:center; gap:0.4rem;">
                    <span>🏯</span> Fiefs & Domaines Provinciaux
                </h3>
                <div style="max-height:200px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px; background:#ffffff;">
                    <table class="table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Fief</th>
                                <th>Province</th>
                                <th>Coordonnées</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="profPlanetsList">
                            <!-- Lignes des fiefs -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pied de page Profil Actions -->
        <div class="modal-footer">
            <div id="profFooterLeft">
                <!-- Actions contextuelles comme Contacter -->
            </div>
            <button class="btn btn-secondary" onclick="closePlayerProfileModal()">Fermer</button>
        </div>
    </div>
</div>

<!-- Modale Complète Didacticiel Féodal & Voie du Daimyō (Style Maître de Quête Travian) -->
<div class="modal-overlay" id="questModal">
    <div class="modal-card modal-card-xl">
        <!-- En-tête -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <span style="font-size:2rem; filter:drop-shadow(0 2px 6px rgba(185,28,28,0.3));">🎯</span>
                <div>
                    <h2 class="modal-title">
                        Codex des Quêtes & Didacticiel du Daimyō
                    </h2>
                    <div class="d-flex align-items-center gap-3 mt-1" style="font-size:0.85rem; color:var(--text-muted);">
                        <span id="questModalProgressText" style="font-weight:700; color:#1c1917;">0/12 Quêtes Accomplies</span> &bull; 
                        <span style="color:#b45309; font-weight:800;" id="questModalPercentText">0% Complété</span>
                    </div>
                </div>
            </div>
            <button onclick="closeQuestModal()" class="modal-close-btn" title="Fermer le Codex">&times;</button>
        </div>

        <!-- Corps de la Modale en 2 Colonnes Responsive -->
        <div class="modal-body p-0 d-flex flex-column flex-md-row" style="min-height:500px;">
            <!-- Colonne Gauche : Liste des 12 Quêtes -->
            <div style="width:340px; border-right:1px solid var(--border-color); background:var(--bg-ink); overflow-y:auto; padding:0.85rem;" id="questModalList">
                <!-- Les tuiles de quêtes seront injectées dynamiquement ici -->
            </div>

            <!-- Colonne Droite : Fiche Détaillée de la Quête Sélectionnée -->
            <div style="flex:1; overflow-y:auto; padding:1.5rem; display:flex; flex-direction:column; justify-content:space-between; background:#ffffff;" id="questModalDetail">
                <!-- Détail de la quête injecté dynamiquement -->
            </div>
        </div>

        <!-- Pied de page -->
        <div class="modal-footer">
            <div style="font-size:0.85rem; color:var(--text-muted);">
                🥋 <em>Guide de l'art de la guerre enseigné par Katsumoto, Maître d'Armes</em>
            </div>
            <button class="btn btn-secondary" onclick="closeQuestModal()">Fermer le Codex</button>
        </div>
    </div>
</div>

<!-- 🏯 Modale de la Tour de Guet (Registre Détaillé des Raids et Mouvements Tactiques) -->
<div class="modal-overlay" id="watchtowerModal" style="display:none;" onclick="if(event.target===this)closeWatchtowerModal()">
    <div class="modal-card modal-card-lg watchtower-modal-card">
        <!-- En-tête -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <span style="font-size:2rem; filter:drop-shadow(0 2px 5px rgba(185,28,28,0.4));">🏯</span>
                <div>
                    <h2 style="font-size:1.25rem; font-weight:800; color:#1c1917; margin:0; display:flex; align-items:center; gap:0.5rem;">
                        <span>Tour de Guet &bull; Registre Stratégique</span>
                        <?php if (!empty($incomingHostile)): ?>
                            <span class="badge-threat-pulse">🚨 Incursions en Approche</span>
                        <?php endif; ?>
                    </h2>
                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">
                        Surveillance des mouvements militaires aux abords de <strong><?= htmlspecialchars($planet['name'] ?? 'votre domaine') ?></strong> [<?= $planet['coord_x'] ?? 0 ?>|<?= $planet['coord_y'] ?? 0 ?>]
                    </div>
                </div>
            </div>
            <button onclick="closeWatchtowerModal()" class="modal-close-btn" title="Fermer le Registre">&times;</button>
        </div>

        <!-- Corps de la Modale -->
        <div class="modal-body" style="max-height:600px; overflow-y:auto; padding:1.25rem;">
            <!-- Barre KPI Synthétique -->
            <div class="watchtower-kpi-bar">
                <div class="watchtower-kpi-item <?= !empty($incomingHostile) ? 'threat' : '' ?>">
                    <span class="kpi-icon">🚨</span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($incomingHostile ?? []) ?></span>
                        <span class="kpi-label">Incursions Armées</span>
                    </div>
                </div>
                <div class="watchtower-kpi-item <?= !empty($incomingSpy) ? 'spy' : '' ?>">
                    <span class="kpi-icon">🥷</span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($incomingSpy ?? []) ?></span>
                        <span class="kpi-label">Missions Shinobi</span>
                    </div>
                </div>
                <div class="watchtower-kpi-item info">
                    <span class="kpi-icon">🐎</span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($outgoingMissions ?? []) ?></span>
                        <span class="kpi-label">Expéditions du Clan</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 1 : INCURSIONS & RAIDS ENNEMIS EN APPROCHE -->
            <?php if (!empty($incomingHostile)): ?>
                <div class="watchtower-section">
                    <div class="watchtower-section-title threat">
                        <span>⚔️ Incursions et Raids Ennemis en Approche (<?= count($incomingHostile) ?>)</span>
                    </div>
                    <div class="watchtower-cards-list">
                        <?php foreach ($incomingHostile as $m): ?>
                            <?php 
                                $fleetData = json_decode($m['fleet_data'] ?? '{}', true) ?: [];
                                $totalWarriors = array_sum($fleetData);
                                $missionLabel = ($m['mission_type'] === 'raid') ? 'Raid Éclair de Pillage' : (($m['mission_type'] === 'attack') ? 'Siège et Destruction Castrale' : strtoupper($m['mission_type']));
                            ?>
                            <div class="watchtower-mission-card threat">
                                <div class="wt-card-header">
                                    <div class="wt-card-title">
                                        <span class="wt-type-badge threat">🚨 <?= $missionLabel ?></span>
                                        <span class="wt-impact-target">Cible : <strong><?= htmlspecialchars($m['target_planet_name'] ?? $planet['name']) ?></strong> [<?= $m['target_coord_x'] ?>|<?= $m['target_coord_y'] ?>]</span>
                                    </div>
                                    <div class="wt-card-timer">
                                        <span class="timer-label">Impact dans</span>
                                        <span class="timer-val" data-countdown="<?= $m['arrival_time'] ?>">Calcul...</span>
                                    </div>
                                </div>
                                <div class="wt-card-body">
                                    <div class="wt-detail-grid">
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">👤 Aggresseur Détecté :</div>
                                            <div class="wt-col-val">
                                                <strong><?= htmlspecialchars($m['sender_username'] ?? 'Daimyō Inconnu') ?></strong>
                                                <?php if (!empty($m['sender_faction'])): ?>
                                                    <span class="faction-chip <?= htmlspecialchars($m['sender_faction']) ?>">Clan <?= ucfirst(htmlspecialchars($m['sender_faction'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="wt-col-sub">
                                                Provenance : <?= htmlspecialchars($m['source_planet_name'] ?? 'Fief Ennemi') ?> [<?= $m['source_coord_x'] ?>|<?= $m['source_coord_y'] ?>]
                                            </div>
                                        </div>

                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">⚔️ Forces Repérées :</div>
                                            <div class="wt-col-val">
                                                <strong>~<?= number_format($totalWarriors) ?></strong> combattants & engins
                                                <?php if (!empty($m['has_hero'])): ?>
                                                    <span class="wt-hero-tag">🥋 Samouraï Héros</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($fleetData)): ?>
                                                <div class="wt-units-breakdown">
                                                    <?php foreach ($fleetData as $uCode => $uCount): ?>
                                                        <?php 
                                                            $uName = $unitsMap[$uCode]['name'] ?? ($shipsMap[$uCode]['name'] ?? $uCode);
                                                            $uIco = $unitsMap[$uCode]['icon'] ?? '🛡️';
                                                        ?>
                                                        <span class="wt-unit-badge" title="<?= htmlspecialchars($uName) ?>">
                                                            <?= $uIco ?> <?= $uCount ?> <?= htmlspecialchars($uName) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECTION 2 : MISSIONS SHINOBI & INFILTRATION -->
            <?php if (!empty($incomingSpy)): ?>
                <div class="watchtower-section">
                    <div class="watchtower-section-title spy">
                        <span>🥷 Infiltrations Shinobi & Espionnage Détectés (<?= count($incomingSpy) ?>)</span>
                    </div>
                    <div class="watchtower-cards-list">
                        <?php foreach ($incomingSpy as $m): ?>
                            <div class="watchtower-mission-card spy">
                                <div class="wt-card-header">
                                    <div class="wt-card-title">
                                        <span class="wt-type-badge spy">🥷 RECONNAISSANCE FURTIVE</span>
                                        <span class="wt-impact-target">Cible : <strong><?= htmlspecialchars($m['target_planet_name'] ?? $planet['name']) ?></strong></span>
                                    </div>
                                    <div class="wt-card-timer">
                                        <span class="timer-label">Arrivée dans</span>
                                        <span class="timer-val" data-countdown="<?= $m['arrival_time'] ?>">Calcul...</span>
                                    </div>
                                </div>
                                <div class="wt-card-body">
                                    <div class="wt-detail-grid">
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">👤 Commanditaire :</div>
                                            <div class="wt-col-val">
                                                <strong><?= htmlspecialchars($m['sender_username'] ?? 'Ombre Inconnue') ?></strong>
                                            </div>
                                            <div class="wt-col-sub">
                                                Départ : <?= htmlspecialchars($m['source_planet_name'] ?? 'Domaine Inconnu') ?> [<?= $m['source_coord_x'] ?>|<?= $m['source_coord_y'] ?>]
                                            </div>
                                        </div>
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">🔍 Rapport Vigies :</div>
                                            <div class="wt-col-val" style="color:var(--text-muted); font-size:0.85rem;">
                                                Des éclaireurs et espions shinobi tentent de sonder vos entrepôts et garnisons.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECTION 3 : VOS EXPÉDITIONS & TROUPES EN DÉPLACEMENT -->
            <?php if (!empty($outgoingMissions)): ?>
                <div class="watchtower-section">
                    <div class="watchtower-section-title info">
                        <span>🐎 Expéditions et Marches de Vos Troupes (<?= count($outgoingMissions) ?>)</span>
                    </div>
                    <div class="watchtower-cards-list">
                        <?php foreach ($outgoingMissions as $m): ?>
                            <?php 
                                $isOutbound = ($m['status'] === 'en_route');
                                $targetTime = $isOutbound ? $m['arrival_time'] : $m['return_time'];
                                $fleetData = json_decode($m['fleet_data'] ?? '{}', true) ?: [];
                                $totalWarriors = array_sum($fleetData);
                                $missionTypeLabel = match($m['mission_type']) {
                                    'raid' => 'Raid Féodal',
                                    'attack' => 'Siège de Forteresse',
                                    'spy' => 'Infiltration Shinobi',
                                    'transport' => 'Convoi de Ravitaillement',
                                    'colonize' => 'Fief Colonial',
                                    'adventure' => 'Aventure Provinciale',
                                    default => ucfirst($m['mission_type'])
                                };
                            ?>
                            <div class="watchtower-mission-card info">
                                <div class="wt-card-header">
                                    <div class="wt-card-title">
                                        <span class="wt-type-badge info"><?= $isOutbound ? '↗️ ' : '↙️ ' ?><?= $missionTypeLabel ?></span>
                                        <span class="wt-impact-target">
                                            <?= $isOutbound ? 'Vers : ' : 'Retour vers : ' ?>
                                            <strong><?= htmlspecialchars($m['target_planet_name'] ?? 'Fief') ?></strong> [<?= $m['target_coord_x'] ?>|<?= $m['target_coord_y'] ?>]
                                        </span>
                                    </div>
                                    <div class="wt-card-timer">
                                        <span class="timer-label"><?= $isOutbound ? 'Arrivée dans' : 'Retour dans' ?></span>
                                        <span class="timer-val" data-countdown="<?= $targetTime ?>">Calcul...</span>
                                    </div>
                                </div>
                                <div class="wt-card-body">
                                    <div class="wt-detail-grid">
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">📍 Itinéraire :</div>
                                            <div class="wt-col-sub">
                                                <?= htmlspecialchars($m['source_planet_name'] ?? 'Votre Fief') ?> &rarr; <?= htmlspecialchars($m['target_planet_name'] ?? 'Destination') ?>
                                            </div>
                                        </div>
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label">⚔️ Effectifs mobilisés :</div>
                                            <div class="wt-col-val">
                                                <strong><?= number_format($totalWarriors) ?></strong> combattants
                                                <?php if (!empty($m['has_hero'])): ?>
                                                    <span class="wt-hero-tag">🥋 Samouraï Héros</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($incomingHostile) && empty($incomingSpy) && empty($outgoingMissions)): ?>
                <div style="text-align:center; padding:3rem 1.5rem; background:#faf8f5; border:1px dashed var(--border-color); border-radius:8px;">
                    <div style="font-size:3rem; margin-bottom:1rem;">⛩️</div>
                    <h3 style="color:#1c1917; font-weight:800; font-size:1.1rem; margin-bottom:0.4rem;">Paix sur vos Terres</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; max-width:450px; margin:0 auto;">
                        Les vigies et éclaireurs ne signalent aucun mouvement militaire en approche ni armée en marche. Votre domaine est pour l'heure en sécurité.
                    </p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Pied de page de la modale -->
        <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:0.8rem; color:var(--text-muted);">
                💡 <em>Astuce : Renforcez votre Muraille d'Enceinte pour accroître la valeur défensive de vos troupes.</em>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <a href="?page=fleet" class="btn btn-primary" style="font-size:0.85rem; padding:0.4rem 1rem;">🏇 Gérer les Troupes</a>
                <button class="btn btn-secondary" onclick="closeWatchtowerModal()" style="font-size:0.85rem; padding:0.4rem 1rem;">Fermer</button>
            </div>
        </div>
    </div>
</div>

<footer style="text-align:center; padding:2rem 1rem; color:var(--text-muted); font-size:0.85rem; border-top:1px solid rgba(255,255,255,0.05); margin-top:3rem;">
    <p><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> &copy; <?= date('Y') ?> - Jeu de stratégie féodale japonaise par navigateur inspiré de Travian.</p>
    <p style="margin-top:0.35rem; color:#64748b;">Moteur féodal Sengoku PHP 8 + MariaDB + JavaScript Vanilla</p>
</footer>

<script src="/public/js/app.js?v=<?= file_exists(__DIR__ . '/../../public/js/app.js') ? filemtime(__DIR__ . '/../../public/js/app.js') : time() ?>"></script>
<script>
function openWatchtowerModal() {
    const modal = document.getElementById('watchtowerModal');
    if (modal) modal.style.display = 'flex';
}

function closeWatchtowerModal() {
    const modal = document.getElementById('watchtowerModal');
    if (modal) modal.style.display = 'none';
}

let currentViewingProfileId = null;

async function openPlayerProfileModal(userId = null, autoEdit = false) {
    const currentLoggedUserId = <?= (int)($user['id'] ?? 0) ?>;
    const uid = parseInt(userId || currentLoggedUserId, 10);
    if (!uid || isNaN(uid) || uid <= 0) {
        showModalAlert("Information", "Aucun Daimyō répertorié sur ce territoire.", "info");
        return;
    }

    currentViewingProfileId = uid;
    const modal = document.getElementById('playerProfileModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.getElementById('profUsername').innerText = "Chargement...";
    document.getElementById('profBioView').innerText = "Lecture des parchemins...";
    document.getElementById('profMedalsList').innerHTML = "<span style='color:var(--text-muted); font-size:0.85rem;'>Récupération des distinctions...</span>";
    document.getElementById('profPlanetsList').innerHTML = "<tr><td colspan='4' style='padding:1rem; text-align:center; color:var(--text-muted);'>Recherche des fiefs...</td></tr>";

    try {
        const res = await fetch(`/api/profile.php?action=get_profile&user_id=${uid}`);
        const data = await res.json();

        if (!data.success) {
            showModalAlert("Erreur", data.error || "Impossible de charger le profil.", "danger");
            closePlayerProfileModal();
            return;
        }

        const p = data.profile;
        if (!p) {
            showModalAlert("Erreur", "Daimyō introuvable dans les registres impériaux.", "danger");
            closePlayerProfileModal();
            return;
        }

        const factionIcons = { 'terran': '🏯', 'vorash': '🐎', 'aethelis': '⛩️' };
        const fKey = (p.faction || 'terran').toLowerCase();

        document.getElementById('profAvatar').innerText = factionIcons[fKey] || '🏯';
        document.getElementById('profUsername').innerText = p.username || 'Daimyō Inconnu';
        document.getElementById('profRank').innerText = `Rang #${p.rank_pos || 1} (${(p.points || 0).toLocaleString()} pts)`;

        const onlineBadge = document.getElementById('profOnlineBadge');
        if (p.is_online) {
            onlineBadge.innerText = "🟢 En ligne";
            onlineBadge.style.background = "#dcfce7";
            onlineBadge.style.color = "#15803d";
            onlineBadge.style.border = "1px solid #86efac";
        } else {
            onlineBadge.innerText = "⚪ Hors-ligne";
            onlineBadge.style.background = "#f5f5f4";
            onlineBadge.style.color = "#57534e";
            onlineBadge.style.border = "1px solid #e7e5e4";
        }

        const fBadge = document.getElementById('profFactionBadge');
        fBadge.className = `faction-badge ${fKey}`;
        fBadge.innerText = (p.faction_name || fKey).toUpperCase();

        document.getElementById('profAlliance').innerText = p.alliance_tag ? `[${p.alliance_tag}] ${p.alliance_name || ''}` : "Clan Indépendant";

        // Médailles (Style Travian)
        const medalsList = document.getElementById('profMedalsList');
        const medals = p.medals || [];
        document.getElementById('profMedalsCount').innerText = `${medals.length} distinction(s)`;

        if (medals.length === 0) {
            medalsList.innerHTML = "<span style='color:var(--text-muted); font-size:0.85rem;'>Aucune médaille d'honneur décernée pour le moment.</span>";
        } else {
            medalsList.innerHTML = medals.map(m => `
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-top: 3px solid ${m.color || '#b45309'}; border-radius: 8px; padding: 0.5rem 0.75rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(60,45,30,0.04);"
                     title="${escapeHtmlModal(m.description || '')} (${m.week_code || ''}) - Décerné le ${m.awarded_at || ''}"
                     onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="font-size: 1.5rem;">${m.icon || '🎖️'}</span>
                    <div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: ${m.color || '#b45309'};">${m.category_label || 'Honneur'} #${m.rank || 1}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">${m.week_code || ''}</div>
                    </div>
                </div>
            `).join('');
        }

        // Bio
        const bioText = p.bio || "Fier Daimyō au service de l'honneur de son clan et de l'Empereur.";
        document.getElementById('profBioView').innerText = bioText;
        document.getElementById('profBioInput').value = bioText;
        document.getElementById('profBioEditContainer').style.display = 'none';
        document.getElementById('profBioView').style.display = 'block';

        const editBtn = document.getElementById('profEditBioBtn');
        if (p.is_self) {
            editBtn.style.display = 'inline-block';
            if (autoEdit) {
                toggleBioEdit(true);
            }
        } else {
            editBtn.style.display = 'none';
        }

        // Stats hebdo
        const ws = p.weekly_stats || {};
        document.getElementById('profStatProg').innerText = `+${(ws.progression || 0).toLocaleString()} pts`;
        document.getElementById('profStatAtt').innerText = `${(ws.attack_points || 0).toLocaleString()} pts`;
        document.getElementById('profStatDef').innerText = `${(ws.defense_points || 0).toLocaleString()} pts`;
        document.getElementById('profStatRaid').innerText = `${(ws.raid_resources || 0).toLocaleString()} Riz`;

        // Colonies
        const planetsTbody = document.getElementById('profPlanetsList');
        const colonies = p.planets || p.colonies || [];
        if (colonies.length === 0) {
            planetsTbody.innerHTML = "<tr><td colspan='4' style='padding:0.75rem; text-align:center; color:var(--text-muted);'>Aucun fief recensé.</td></tr>";
        } else {
            planetsTbody.innerHTML = colonies.map(pl => `
                <tr>
                    <td style="padding: 0.6rem 0.75rem; font-weight: 700; color: #1c1917;">
                        ${pl.is_capital ? '⭐ ' : '🏯 '} ${escapeHtmlModal(pl.name || 'Fief')}
                    </td>
                    <td style="padding: 0.6rem 0.75rem; color: var(--text-muted); font-weight: 500;">${pl.planet_type || 'Fief Castral'}</td>
                    <td style="padding: 0.6rem 0.75rem; font-family: monospace; font-weight: 700; color: #b91c1c;">[${pl.coord_x} : ${pl.coord_y}]</td>
                    <td style="padding: 0.6rem 0.75rem; text-align: right;">
                        <a href="?page=map&x=${pl.coord_x}&y=${pl.coord_y}" class="btn btn-secondary" style="font-size: 0.72rem; padding: 0.25rem 0.6rem;" title="Voir sur la carte">
                            🗾 Provinces
                        </a>
                        ${!p.is_self ? `
                            <a href="?page=fleet&target_id=${pl.id}&mission=raid" class="btn btn-primary" style="background:#b91c1c; font-size: 0.7rem; padding: 0.2rem 0.5rem; margin-left: 0.25rem;">
                                ⚔️ Raid
                            </a>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        }

        // Actions bas de page
        const footerLeft = document.getElementById('profFooterLeft');
        if (!p.is_self) {
            footerLeft.innerHTML = `
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(p.username || '')}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85rem;">
                    <span>✉️</span> Dépêcher une Missive
                </a>
            `;
        } else {
            footerLeft.innerHTML = `
                <span style="color:#dc2626; font-size:0.85rem; font-weight:600;">👑 Il s'agit de votre Fiche de Daimyō officielle</span>
            `;
        }

    } catch (e) {
        console.error("Erreur fiche joueur:", e);
        showModalAlert("Erreur", "Impossible d'établir la liaison avec la base de données : " + (e.message || e), "danger");
        closePlayerProfileModal();
    }
}

function escapeHtmlModal(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closePlayerProfileModal() {
    const modal = document.getElementById('playerProfileModal');
    if (modal) modal.style.display = 'none';
}

function openEditMottoModal() {
    closeQuestModal();
    openPlayerProfileModal(<?= (int)($user['id'] ?? 0) ?>, true);
}

function toggleBioEdit(forceOpen = null) {
    const view = document.getElementById('profBioView');
    const container = document.getElementById('profBioEditContainer');
    const shouldOpen = (forceOpen !== null) ? forceOpen : (container.style.display === 'none');
    if (shouldOpen) {
        container.style.display = 'block';
        view.style.display = 'none';
        const input = document.getElementById('profBioInput');
        if (input) {
            input.focus();
            input.select();
        }
    } else {
        container.style.display = 'none';
        view.style.display = 'block';
    }
}

async function saveBio() {
    const bioInput = document.getElementById('profBioInput');
    const bioText = bioInput ? bioInput.value : '';
    try {
        const formData = new FormData();
        formData.append('action', 'update_bio');
        formData.append('bio', bioText);

        const res = await fetch('/api/profile.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            const bioView = document.getElementById('profBioView');
            if (bioView) bioView.innerText = data.bio || bioText;
            toggleBioEdit(false);
            showModalAlert("Votre devise de Daimyō a été proclamée avec succès !", "success", "Manifeste Sauvegardé");

            // Synchroniser avec l'affichage éventuel du Tenshu sur la page en cours
            const tenshuBio = document.getElementById('tenshuDaimyoBioText');
            if (tenshuBio) {
                tenshuBio.innerText = data.bio;
            }
            // Synchroniser le chip dans le header
            const headerMotto = document.getElementById('headerDaimyoMotto');
            if (headerMotto) {
                headerMotto.innerText = data.bio ? `« ${data.bio} »` : 'Proclamer ma devise...';
            }
            // Recharger si nécessaire pour valider la quête du didactiel
            const qBanner = document.getElementById('questBannerContainer');
            if (qBanner) {
                setTimeout(() => window.location.reload(), 1200);
            }
        } else {
            showModalAlert(data.error || "Impossible d'enregistrer votre devise.", "error", "Erreur");
        }
    } catch (e) {
        showModalAlert("Une erreur est survenue lors de l'enregistrement : " + (e.message || e), "error", "Erreur Réseau");
    }
}

/* ==========================================================================
   GESTION DU DIDACTICIEL & CODEX DES QUÊTES FÉODALES (STYLE TRAVIAN)
   ========================================================================== */
let cachedQuestsData = null;
let selectedQuestKey = null;

async function openQuestModal(forceKey = null) {
    const modal = document.getElementById('questModal');
    if (!modal) return;
    modal.style.display = 'flex';

    try {
        const res = await fetch('/api/quests.php?action=get_status');
        const json = await res.json();
        if (json.success && json.data) {
            cachedQuestsData = json.data;
            if (forceKey) {
                selectedQuestKey = forceKey;
            } else if (!selectedQuestKey || !cachedQuestsData.quests.find(q => q.key === selectedQuestKey)) {
                selectedQuestKey = cachedQuestsData.active_quest ? cachedQuestsData.active_quest.key : cachedQuestsData.quests[0].key;
            }
            renderQuestModal();
        }
    } catch (e) {
        console.error("Erreur chargement quêtes:", e);
    }
}

function closeQuestModal() {
    const modal = document.getElementById('questModal');
    if (modal) modal.style.display = 'none';
}

function selectQuestInModal(questKey) {
    selectedQuestKey = questKey;
    renderQuestModal();
}

function renderQuestModal() {
    if (!cachedQuestsData) return;
    const { quests, active_quest, claimed_count, total_quests, overall_percent } = cachedQuestsData;

    const progText = document.getElementById('questModalProgressText');
    const pctText = document.getElementById('questModalPercentText');
    if (progText) progText.innerText = `${claimed_count}/${total_quests} Quêtes Accomplies`;
    if (pctText) pctText.innerText = `${overall_percent}% Complété`;

    // Liste des quêtes à gauche
    const listContainer = document.getElementById('questModalList');
    if (listContainer) {
        let listHtml = '';
        quests.forEach(q => {
            const isSelected = (q.key === selectedQuestKey);
            const isClaimed = (q.status === 'claimed');
            const isClaimable = (q.is_claimable);
            
            let statusBadge = '';
            let borderStyle = isSelected ? 'border: 2px solid #b91c1c; background: #fee2e2; box-shadow: 0 2px 8px rgba(185,28,28,0.12);' : 'border: 1px solid var(--border-color); background: #ffffff;';

            if (isClaimed) {
                statusBadge = '<span class="badge badge-success">✓ Perçue</span>';
            } else if (isClaimable) {
                statusBadge = '<span class="badge badge-warning">✨ Prête !</span>';
                if (!isSelected) {
                    borderStyle = 'border: 1px solid #15803d; background: #dcfce7;';
                }
            } else {
                statusBadge = '<span class="badge" style="background:#f5f5f4; color:#57534e; border-color:#e7e5e4;">En cours</span>';
            }

            listHtml += `
                <div onclick="selectQuestInModal('${q.key}')" style="${borderStyle} border-radius:8px; padding:0.65rem 0.85rem; margin-bottom:0.5rem; cursor:pointer; display:flex; align-items:center; gap:0.75rem; transition:all 0.2s ease;">
                    <div style="font-size:1.6rem; min-width:32px; text-align:center;">${q.icon}</div>
                    <div style="flex:1; overflow:hidden;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:0.7rem; color:#b91c1c; font-weight:800;">ÉTAPE ${q.order}</span>
                            ${statusBadge}
                        </div>
                        <div style="font-size:0.875rem; font-weight:700; color:#1c1917; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; margin-top:2px;">
                            ${escapeHtmlModal(q.title)}
                        </div>
                    </div>
                </div>
            `;
        });
        listContainer.innerHTML = listHtml;
    }

    // Détail à droite
    const detailContainer = document.getElementById('questModalDetail');
    const currentQ = quests.find(q => q.key === selectedQuestKey) || active_quest || quests[0];
    if (detailContainer && currentQ) {
        const isClaimed = (currentQ.status === 'claimed');
        const isClaimable = (currentQ.is_claimable);

        let actionButtonHtml = '';
        if (isClaimed) {
            actionButtonHtml = `
                <div style="background:#dcfce7; border:1px solid #86efac; color:#15803d; padding:0.75rem 1.25rem; border-radius:8px; font-weight:700; display:flex; align-items:center; gap:0.6rem; justify-content:center;">
                    <span>✓</span> Récompense perçue avec honneur le ${currentQ.claimed_at ? currentQ.claimed_at.substring(0, 16) : 'récemment'}.
                </div>
            `;
        } else if (isClaimable) {
            actionButtonHtml = `
                <button type="button" onclick="claimQuestReward('${currentQ.key}')" class="btn btn-primary pulse-btn" style="width:100%; background:linear-gradient(135deg, #15803d 0%, #166534 100%); border-color:#14532d; color:#fff; font-weight:800; font-size:1.05rem; padding:0.85rem; border-radius:8px; cursor:pointer; box-shadow:0 4px 20px rgba(21,128,61,0.35);">
                    ✨ Réclamer ma Récompense Immédiatement
                </button>
            `;
        } else {
            if (currentQ.action_url.startsWith('javascript:')) {
                actionButtonHtml = `
                    <button type="button" onclick="closeQuestModal(); ${currentQ.action_url.substring(11)};" class="btn btn-primary" style="width:100%; padding:0.75rem; font-size:1rem; font-weight:700;">
                        ${escapeHtmlModal(currentQ.action_label)} &rarr;
                    </button>
                `;
            } else {
                actionButtonHtml = `
                    <a href="${currentQ.action_url}" class="btn btn-primary" style="display:block; text-align:center; text-decoration:none; padding:0.75rem; font-size:1rem; font-weight:700;">
                        ${escapeHtmlModal(currentQ.action_label)} &rarr;
                    </a>
                `;
            }
        }

        let rewardsHtml = '';
        if (currentQ.rewards.metal) {
            rewardsHtml += `<div style="background:#ffffff; border:1px solid var(--border-color); border-radius:8px; padding:0.6rem 0.9rem; text-align:center; min-width:95px; box-shadow:0 1px 3px rgba(60,45,30,0.04);"><span style="color:var(--res-metal,#78350f); font-size:1.2rem;">🪵</span><div style="font-size:0.72rem; color:var(--text-muted); font-weight:600;">Bois de Cèdre</div><div style="font-size:0.95rem; font-weight:800; color:#1c1917;">+${Number(currentQ.rewards.metal).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.crystal) {
            rewardsHtml += `<div style="background:#ffffff; border:1px solid var(--border-color); border-radius:8px; padding:0.6rem 0.9rem; text-align:center; min-width:95px; box-shadow:0 1px 3px rgba(60,45,30,0.04);"><span style="color:var(--res-crystal,#334155); font-size:1.2rem;">🪨</span><div style="font-size:0.72rem; color:var(--text-muted); font-weight:600;">Pierre de Taille</div><div style="font-size:0.95rem; font-weight:800; color:#1c1917;">+${Number(currentQ.rewards.crystal).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.deuterium) {
            rewardsHtml += `<div style="background:#ffffff; border:1px solid var(--border-color); border-radius:8px; padding:0.6rem 0.9rem; text-align:center; min-width:95px; box-shadow:0 1px 3px rgba(60,45,30,0.04);"><span style="color:var(--res-deut,#b45309); font-size:1.2rem;">🌾</span><div style="font-size:0.72rem; color:var(--text-muted); font-weight:600;">Riz Impérial</div><div style="font-size:0.95rem; font-weight:800; color:#1c1917;">+${Number(currentQ.rewards.deuterium).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.points) {
            rewardsHtml += `<div style="background:#ffffff; border:1px solid var(--border-color); border-radius:8px; padding:0.6rem 0.9rem; text-align:center; min-width:95px; box-shadow:0 1px 3px rgba(60,45,30,0.04);"><span style="color:#b45309; font-size:1.2rem;">⛩️</span><div style="font-size:0.72rem; color:var(--text-muted); font-weight:600;">Honneur</div><div style="font-size:0.95rem; font-weight:800; color:#b45309;">+${Number(currentQ.rewards.points).toLocaleString()} pts</div></div>`;
        }
        if (currentQ.rewards.bonus_units) {
            rewardsHtml += `<div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:0.6rem 0.9rem; text-align:center; min-width:115px;"><span style="color:#b91c1c; font-size:1.2rem;">⚔️</span><div style="font-size:0.72rem; color:#b91c1c; font-weight:700;">Garnison</div><div style="font-size:0.95rem; font-weight:800; color:#b91c1c;">+${currentQ.rewards.bonus_units} Guerriers</div></div>`;
        }

        detailContainer.innerHTML = `
            <div>
                <!-- En-tête Quête -->
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem;">
                    <div>
                        <span style="font-size:0.75rem; font-weight:800; color:#b91c1c; text-transform:uppercase;">QUÊTE ${currentQ.order} SUR ${total_quests} &bull; ${escapeHtmlModal(currentQ.category.toUpperCase())}</span>
                        <h2 style="font-size:1.4rem; font-weight:900; color:#1c1917; margin:0.25rem 0 0 0;">
                            ${currentQ.icon} ${escapeHtmlModal(currentQ.title)}
                        </h2>
                    </div>
                    <span class="badge ${isClaimed ? 'badge-success' : (isClaimable ? 'badge-warning' : '')}" style="${!isClaimed && !isClaimable ? 'background:#f5f5f4; color:#57534e; border-color:#e7e5e4;' : ''}; font-size:0.85rem; padding:0.35rem 0.75rem;">
                        ${isClaimed ? '✓ Accompli' : (isClaimable ? '✨ Prêt à réclamer' : 'En cours')}
                    </span>
                </div>

                <!-- Dialogue du Conseiller Katsumoto -->
                <div style="background:#fdfbf7; border:1px solid var(--border-color); border-left:4px solid #b91c1c; border-radius:0 8px 8px 0; padding:1.1rem; margin-bottom:1.25rem; box-shadow:0 1px 3px rgba(60,45,30,0.04);">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                        <span style="font-size:1.2rem;">🥋</span>
                        <strong style="color:#b45309; font-size:0.9rem;">${escapeHtmlModal(currentQ.mentor_name)} :</strong>
                    </div>
                    <p style="margin:0; font-size:0.92rem; color:#292524; line-height:1.6; font-style:italic;">
                        &laquo; ${escapeHtmlModal(currentQ.lore)} &raquo;
                    </p>
                </div>

                <!-- Objectif précis -->
                <div style="background:#f6f2e8; border:1px solid var(--border-color); border-radius:8px; padding:0.9rem 1.1rem; margin-bottom:1.25rem;">
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:0.25rem;">Objectif à atteindre :</div>
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                        <span style="font-size:1.05rem; font-weight:800; color:${isClaimable || isClaimed ? '#15803d' : '#1c1917'};">
                            ${isClaimable || isClaimed ? '✓ ' : '🎯 '} ${escapeHtmlModal(currentQ.objective)}
                        </span>
                        <span style="font-size:0.75rem; color:#44403c; background:#ffffff; border:1px solid var(--border-color); padding:0.25rem 0.6rem; border-radius:4px; font-weight:600;">
                            📍 ${escapeHtmlModal(currentQ.target_slot_hint || 'Fief')}
                        </span>
                    </div>
                </div>

                <!-- Récompenses promises -->
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:0.6rem;">Récompenses accordées par le Shogunat :</div>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                        ${rewardsHtml}
                    </div>
                </div>
            </div>

            <!-- Bouton d'action bas de panneau -->
            <div style="margin-top:1rem;">
                ${actionButtonHtml}
            </div>
        `;
    }
}

async function claimQuestReward(questKey) {
    try {
        const formData = new FormData();
        formData.append('action', 'claim');
        formData.append('quest_key', questKey);

        const res = await fetch('/api/quests.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            if (data.planet) {
                updateHudResources(data.planet);
            }

            if (data.quests_status) {
                cachedQuestsData = data.quests_status;
                selectedQuestKey = cachedQuestsData.active_quest ? cachedQuestsData.active_quest.key : questKey;
                renderQuestModal();
            }

            let rewardDetailMsg = '';
            if (data.rewards.metal) rewardDetailMsg += `+${Number(data.rewards.metal).toLocaleString()} 🪵 Bois, `;
            if (data.rewards.crystal) rewardDetailMsg += `+${Number(data.rewards.crystal).toLocaleString()} 🪨 Pierre, `;
            if (data.rewards.deuterium) rewardDetailMsg += `+${Number(data.rewards.deuterium).toLocaleString()} 🌾 Riz, `;
            if (data.rewards.points) rewardDetailMsg += `+${data.rewards.points} ⛩️ Honneur, `;
            if (data.bonus_units) rewardDetailMsg += `+${data.bonus_units} ⚔️ ${data.rewarded_unit_name}, `;
            rewardDetailMsg = rewardDetailMsg.replace(/, $/, '');

            showModalAlert("Récompense de Daimyō Perçue !", `${data.message}\n\nVos coffres reçoivent : ${rewardDetailMsg}`, "success");

            setTimeout(() => {
                window.location.reload();
            }, 1200);

        } else {
            showModalAlert("Décret Impérial", data.error || "Impossible de réclamer la récompense.", "warning");
        }
    } catch (e) {
        console.error("Erreur réclamation:", e);
        showModalAlert("Erreur", "Une erreur est survenue lors de la réclamation de la récompense.", "danger");
    }
}

function updateHudResources(planet) {
    if (!planet) return;
    const updateEl = (valId, barId, current, max) => {
        const valEl = document.getElementById(valId);
        const barEl = document.getElementById(barId);
        if (valEl) {
            valEl.setAttribute('data-current', current);
            valEl.innerText = Math.floor(current).toLocaleString();
        }
        if (barEl && max > 0) {
            barEl.style.width = Math.min(100, (current / max) * 100) + '%';
        }
    };
    updateEl('res-val-metal', 'bar-metal', planet.metal, planet.metal_max);
    updateEl('res-val-crystal', 'bar-crystal', planet.crystal, planet.crystal_max);
    updateEl('res-val-deut', 'bar-deut', planet.deuterium, planet.deuterium_max);
}
</script>
<script src="/public/js/audio_manager.js"></script>
</body>
</html>

