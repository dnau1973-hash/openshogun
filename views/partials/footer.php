            </div><!-- /container-fluid -->
        </div><!-- /page-body -->
    </div><!-- /page-wrapper -->
</div><!-- /page -->

<!-- Modale Personnalisée pour Messages, Alertes & Confirmations (Remplace alert/confirm) -->
<div class="modal-overlay" id="customAlertModal">
    <div class="modal-card alert-modal-card" id="customAlertCard">
        <div class="card-header">
            <h3 class="card-title" id="customAlertTitle">Décret du Shogunat</h3>
            <button onclick="closeCustomAlert()" class="modal-close-btn">&times;</button>
        </div>
        <div class="card-body">
            <div class="alert-modal-content">
                <span class="alert-modal-icon" id="customAlertIcon"><i class="fa-solid fa-circle-info text-primary"></i></span>
                <p class="alert-modal-text" id="customAlertText"></p>
            </div>
            <div class="alert-modal-actions" id="customAlertActions">
                <button class="btn btn-primary" id="customAlertBtnOk">Compris</button>
            </div>
        </div>
    </div>
</div>

<!-- Conteneur Universel de Toasts Tabler.io pour Confirmations d'Actions & Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tablerToastContainer" style="z-index: 99999; max-width: 420px;"></div>

<!-- Modale d'Annonces & Notifications des Fonctionnalités -->
<?php require_once __DIR__ . '/announcement_modal.php'; ?>
<!-- Modale du Sceau Impérial / Privilège du Shōgun -->
<?php require_once __DIR__ . '/imperial_seal_modal.php'; ?>
<!-- Modale des Chroniques Féodales du Sengoku (Événement Hebdomadaire) -->
<?php require_once __DIR__ . '/weekly_event_modal.php'; ?>
<!-- Modale de Transparence IA (Prompts & Traduction) -->
<?php require_once __DIR__ . '/ai_prompt_modal.php'; ?>
<!-- Modale Universelle Tabler.io : Renommage de Fief Féodal -->
<?php require_once __DIR__ . '/rename_village_modal.php'; ?>
<!-- Modale Interactive : Mode Apprentissage & Maître de la Sagesse (Shinōkōshō) -->
<?php require_once __DIR__ . '/wisdom_learning_modal.php'; ?>
<!-- Modale d'Alerte Tactique : Général Oda Nobunaga (Conquête & Guerre) -->
<?php require_once __DIR__ . '/oda_alert_modal.php'; ?>
<!-- Modale Universelle des Conseillers & Alertes du Sengoku (Style Classique) -->
<?php require_once __DIR__ . '/feudal_advisor_modal.php'; ?>

<!-- Modale Universelle de la Fiche Daimyō / Profil (Style Washi Féodal) -->
<div class="modal-overlay" id="playerProfileModal">
    <div class="modal-card modal-card-lg">
        <!-- En-tête Profil -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <span id="profAvatar" style="font-size:2.5rem; filter:drop-shadow(0 2px 6px rgba(185,28,28,0.3));"><i class="fa-solid fa-chess-rook text-danger"></i></span>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 id="profUsername" style="font-size:1.35rem; font-weight:800; color:#1c1917; margin:0;">Daimyō</h2>
                        <span id="profRoleBadge" style="display:none; font-size:0.7rem; padding:0.15rem 0.45rem; border-radius:4px; font-weight:700;"></span>
                        <span id="profOnlineBadge" style="font-size:0.7rem; padding:0.15rem 0.45rem; border-radius:4px; font-weight:700;"></span>
                        <span id="profProtectionBadge" style="display:none; font-size:0.7rem; padding:0.15rem 0.45rem; border-radius:4px; font-weight:700; background:#dcfce7; color:#15803d; border:1px solid #86efac;" title="Protection Débutant active"></span>
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
                        <i class="fa-solid fa-medal text-warning me-1"></i> Vitrine des Médailles d'Honneur
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
                        <i class="fa-solid fa-scroll text-danger me-1"></i> Devise &amp; Chronique du Daimyō
                    </h3>
                    <button id="profEditBioBtn" onclick="toggleBioEdit()" class="btn btn-secondary" style="display:none; font-size:0.75rem; padding:0.25rem 0.65rem; border-color:#b91c1c; color:#b91c1c;">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Modifier ma devise
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
                    <i class="fa-solid fa-chess-rook text-primary me-1"></i> Fiefs &amp; Domaines Provinciaux
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

<!-- ========================================================
     MODALE ILLUSTRÉE DU CODEX DES QUÊTES DU DAIMYŌ (STYLE TRAVIAN / CRAFT)
     ======================================================== -->
<div class="modal fade" id="modalCodexQuest" tabindex="-1" aria-labelledby="codexQuestModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: 12px;">
            <!-- Illustration d'en-tête de la quête (215px) avec overlay dégradé -->
            <div style="position: relative; width: 100%; height: 215px; overflow: hidden; background: #0f172a;">
                <img id="codexQuestModalImage" src="/public/assets/quests/quest_01_wood_field.jpg" alt="Quête du Codex" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>
                <div style="position: absolute; bottom: 12px; left: 16px; right: 16px;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                    <div style="max-width: 72%;">
                        <span id="codexQuestModalCategoryBadge" class="badge bg-danger text-white fw-bold px-2 py-1 mb-1 shadow-sm" style="font-size:0.75rem;">
                            ⛩️ Étape 1/12 &bull; TERROIR
                        </span>
                        <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="codexQuestModalTitle" style="font-size: 1.25rem;">
                            Premier Arpent de Cèdre
                        </h3>
                    </div>
                    <div>
                        <span id="codexQuestModalStatusBadge" class="badge bg-success text-white font-monospace fs-6 px-3 py-1 shadow-sm">
                            <i class="fa-solid fa-sparkles me-1"></i>Objectif Atteint !
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>
            </div>

            <!-- Corps de la modale -->
            <div class="modal-body p-4 bg-white">
                <!-- Dialogue du Conseiller Katsumoto -->
                <div class="p-3 rounded border mb-3" style="background: #fdfbf7; border-left: 4px solid #b91c1c !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-size: 1.15rem;"><i class="fa-solid fa-khanda text-danger"></i></span>
                        <strong class="text-danger small" id="codexQuestModalMentorName">Katsumoto, Maître d'Armes :</strong>
                    </div>
                    <p class="small text-secondary m-0 fst-italic" id="codexQuestModalLore" style="line-height: 1.5;">
                        « ... »
                    </p>
                </div>

                <!-- Encart Objectif & Fief -->
                <div class="p-3 rounded border mb-3" style="background: #f8fafc;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-secondary small"><i class="fa-solid fa-bullseye me-1 text-danger"></i>Objectif :</span>
                        <strong class="text-dark small" id="codexQuestModalObjective">Élever un Camp de Bûcherons au niveau 1</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-secondary small"><i class="fa-solid fa-location-dot me-1 text-primary"></i>Emplacement :</span>
                        <span class="badge bg-white border text-dark font-monospace" id="codexQuestModalSlotHint">Parcelles 1 à 5</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-secondary small"><i class="fa-solid fa-gift me-1 text-warning"></i>Dotation Féodale :</span>
                        <div class="d-flex gap-2 flex-wrap" id="codexQuestModalRewardsList">
                            <!-- Injections dynamiques de badges de récompense -->
                        </div>
                    </div>
                </div>

                <!-- Barre de progression du Codex global -->
                <div class="d-flex justify-content-between align-items-center px-1 mb-1 small text-muted">
                    <span>Progression du Codex du Daimyō :</span>
                    <strong class="font-monospace text-dark" id="codexQuestModalProgressPct">0/12 (0%)</strong>
                </div>
                <div class="progress mb-0" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                    <div class="progress-bar bg-danger" id="codexQuestModalProgressBar" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Pied de modale avec confirmation -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Fermer
                </button>
                <div id="codexQuestModalActionContainer">
                    <!-- Bouton injecté dynamiquement : Réclamer ou Se rendre sur place -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modale de la Tour de Guet (Registre Détaillé des Raids et Mouvements Tactiques) -->
<div class="modal-overlay" id="watchtowerModal" style="display:none;" onclick="if(event.target===this)closeWatchtowerModal()">
    <div class="modal-card modal-card-lg watchtower-modal-card">
        <!-- En-tête -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <span style="font-size:2rem; filter:drop-shadow(0 2px 5px rgba(185,28,28,0.4));"><i class="fa-solid fa-chess-rook text-danger"></i></span>
                <div>
                    <h2 style="font-size:1.25rem; font-weight:800; color:#1c1917; margin:0; display:flex; align-items:center; gap:0.5rem;" class="font-game">
                        <span>Tour de Guet &bull; Registre Stratégique</span>
                        <?php if (!empty($incomingHostile)): ?>
                            <span class="badge-threat-pulse"><i class="fa-solid fa-triangle-exclamation me-1"></i> Incursions en Approche</span>
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
            <?php if (($watchtowerLevel ?? 0) < 1): ?>
                <div class="alert alert-warning mb-3 d-flex align-items-center gap-2">
                    <span class="fs-2"><i class="fa-solid fa-torii-gate text-warning"></i></span>
                    <div class="small">
                        <strong>Tour de Guet non érigée :</strong> Sans Tour de Guet Yagura (Niveau 1 minimum) sur ce fief, vos vigies ne peuvent pas repérer les armées ennemies ni détecter les infiltrations de Shinobis à l'approche de vos murailles. Seules vos propres expéditions sont répertoriées.
                    </div>
                </div>
            <?php endif; ?>
            <!-- Barre KPI Synthétique -->
            <div class="watchtower-kpi-bar">
                <div class="watchtower-kpi-item <?= !empty($incomingHostile) ? 'threat' : '' ?>">
                    <span class="kpi-icon"><i class="fa-solid fa-triangle-exclamation text-danger"></i></span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($incomingHostile ?? []) ?></span>
                        <span class="kpi-label">Incursions Armées</span>
                    </div>
                </div>
                <div class="watchtower-kpi-item <?= !empty($incomingSpy) ? 'spy' : '' ?>">
                    <span class="kpi-icon"><i class="fa-solid fa-user-ninja text-info"></i></span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($incomingSpy ?? []) ?></span>
                        <span class="kpi-label">Missions Shinobi</span>
                    </div>
                </div>
                <div class="watchtower-kpi-item info">
                    <span class="kpi-icon"><i class="fa-solid fa-horse text-primary"></i></span>
                    <div class="kpi-data">
                        <span class="kpi-num"><?= count($outgoingMissions ?? []) ?></span>
                        <span class="kpi-label">Expéditions du Clan</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 1 : INCURSIONS & RAIDS ENNEMIS EN APPROCHE -->
            <            <?php if (!empty($incomingHostile)): ?>
                <div class="watchtower-section">
                    <div class="watchtower-section-title threat">
                        <span><i class="fa-solid fa-khanda text-danger me-1"></i> Incursions et Raids Ennemis en Approche (<?= count($incomingHostile) ?>)</span>
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
                                        <span class="wt-type-badge threat"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $missionLabel ?></span>
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
                                            <div class="wt-col-label"><i class="fa-solid fa-user me-1"></i> Aggresseur Détecté :</div>
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
                                            <div class="wt-col-label"><i class="fa-solid fa-khanda me-1"></i> Forces Repérées :</div>
                                            <div class="wt-col-val">
                                                <strong>~<?= number_format($totalWarriors) ?></strong> combattants & engins
                                                <?php if (!empty($m['has_hero'])): ?>
                                                    <span class="wt-hero-tag"><i class="fa-solid fa-award me-1"></i> Samouraï Héros</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($fleetData)): ?>
                                                <div class="wt-units-breakdown">
                                                    <?php foreach ($fleetData as $uCode => $uCount): ?>
                                                        <?php 
                                                            $uName = $unitsMap[$uCode]['name'] ?? ($shipsMap[$uCode]['name'] ?? $uCode);
                                                            $uIco = $unitsMap[$uCode]['icon'] ?? '<i class="fa-solid fa-shield-halved"></i>';
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
                        <span><i class="fa-solid fa-user-ninja text-info me-1"></i> Infiltrations Shinobi &amp; Espionnage Détectés (<?= count($incomingSpy) ?>)</span>
                    </div>
                    <div class="watchtower-cards-list">
                        <?php foreach ($incomingSpy as $m): ?>
                            <div class="watchtower-mission-card spy">
                                <div class="wt-card-header">
                                    <div class="wt-card-title">
                                        <span class="wt-type-badge spy"><i class="fa-solid fa-user-ninja me-1"></i> RECONNAISSANCE FURTIVE</span>
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
                                            <div class="wt-col-label"><i class="fa-solid fa-user me-1"></i> Commanditaire :</div>
                                            <div class="wt-col-val">
                                                <strong><?= htmlspecialchars($m['sender_username'] ?? 'Ombre Inconnue') ?></strong>
                                            </div>
                                            <div class="wt-col-sub">
                                                Départ : <?= htmlspecialchars($m['source_planet_name'] ?? 'Domaine Inconnu') ?> [<?= $m['source_coord_x'] ?>|<?= $m['source_coord_y'] ?>]
                                            </div>
                                        </div>
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label"><i class="fa-solid fa-magnifying-glass me-1"></i> Rapport Vigies :</div>
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
                        <span><i class="fa-solid fa-horse text-primary me-1"></i> Expéditions et Marches de Vos Troupes (<?= count($outgoingMissions) ?>)</span>
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
                                        <span class="wt-type-badge info"><i class="fa-solid <?= $isOutbound ? 'fa-arrow-up-right-from-square' : 'fa-arrow-down-left-and-up-right-to-center' ?> me-1"></i><?= $missionTypeLabel ?></span>
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
                                            <div class="wt-col-label"><i class="fa-solid fa-location-dot me-1"></i> Itinéraire :</div>
                                            <div class="wt-col-sub">
                                                <?= htmlspecialchars($m['source_planet_name'] ?? 'Votre Fief') ?> &rarr; <?= htmlspecialchars($m['target_planet_name'] ?? 'Destination') ?>
                                            </div>
                                        </div>
                                        <div class="wt-detail-col">
                                            <div class="wt-col-label"><i class="fa-solid fa-khanda me-1"></i> Effectifs mobilisés :</div>
                                            <div class="wt-col-val">
                                                <strong><?= number_format($totalWarriors) ?></strong> combattants
                                                <?php if (!empty($m['has_hero'])): ?>
                                                    <span class="wt-hero-tag"><i class="fa-solid fa-award me-1"></i> Samouraï Héros</span>
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
                    <div style="font-size:3rem; margin-bottom:1rem;"><i class="fa-solid fa-torii-gate text-muted"></i></div>
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
                <i class="fa-solid fa-lightbulb text-warning me-1"></i> <em>Astuce : Renforcez votre Muraille d'Enceinte pour accroître la valeur défensive de vos troupes.</em>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <a href="?page=fleet" class="btn btn-primary" style="font-size:0.85rem; padding:0.4rem 1rem;"><i class="fa-solid fa-horse me-1"></i> Gérer les Troupes</a>
                <button class="btn btn-secondary" onclick="closeWatchtowerModal()" style="font-size:0.85rem; padding:0.4rem 1rem;">Fermer</button>
            </div>
        </div>
    </div>
</div>

<footer class="footer footer-transparent d-print-none py-4 border-top" style="text-align:center; padding:2rem 1rem; color:var(--text-muted); font-size:0.85rem; border-top:1px solid rgba(255,255,255,0.08); margin-top:3rem;">
    <div class="container-xl">
        <!-- Liens rapides de navigation et d'assistance -->
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-2" style="font-weight: 600;">
            <a href="?page=docs" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary hover-primary">
                <i class="fa-solid fa-book-open me-1"></i> Règles du jeu
            </a>
            <span class="text-muted opacity-50">&bull;</span>
            <a href="?page=support" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary hover-primary">
                <i class="fa-solid fa-headset me-1"></i> Support &amp; Aide
            </a>
            <span class="text-muted opacity-50">&bull;</span>
            <a href="?page=changelog" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary hover-primary">
                <i class="fa-solid fa-scroll me-1"></i> Changelog
            </a>
            <span class="text-muted opacity-50">&bull;</span>
            <a href="?page=pedagogy" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary hover-primary">
                <i class="fa-solid fa-graduation-cap me-1"></i> Atelier Pédagogique
            </a>
            <span class="text-muted opacity-50">&bull;</span>
            <a href="?page=gallery" class="text-decoration-none d-inline-flex align-items-center gap-1 text-secondary hover-primary">
                <i class="fa-solid fa-palette me-1 text-danger"></i> Galerie d'Art
            </a>
        </div>
        <p class="m-0"><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> &copy; <?= date('Y') ?> &mdash; Jeu de stratégie féodale japonaise par navigateur inspiré de Travian.</p>
        <p style="margin-top:0.35rem; color:#64748b;" class="small">Moteur féodal Sengoku PHP 8 &bull; MariaDB &bull; JavaScript Vanilla &bull; Tabler.io</p>
    </div>
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
        if (typeof showModalAlert === 'function') {
            showModalAlert("Information", "Aucun Daimyō répertorié sur ce territoire.", "info");
        }
        return;
    }

    const editParam = autoEdit ? '&edit=1' : '';
    if (uid === currentLoggedUserId) {
        window.location.href = '/?page=poster' + (autoEdit ? '&edit=1' : '');
    } else {
        window.location.href = '/?page=poster&id=' + uid + editParam;
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
   GESTION DU CODEX DES QUÊTES DU DAIMYŌ (MODALE ILLUSTRÉE STYLE TRAVIAN / CRAFT)
   ========================================================================== */
let cachedQuestsData = null;
let currentCodexQuest = null;

async function openCodexQuestModal(forceKey = null) {
    const modalEl = document.getElementById('modalCodexQuest');
    if (!modalEl) return;

    try {
        const res = await fetch('/api/quests.php?action=get_status');
        const json = await res.json();
        if (json.success && json.data) {
            cachedQuestsData = json.data;
            const quests = cachedQuestsData.quests || [];
            if (forceKey) {
                currentCodexQuest = quests.find(q => q.key === forceKey);
            } else if (cachedQuestsData.active_quest) {
                currentCodexQuest = cachedQuestsData.active_quest;
            } else {
                currentCodexQuest = quests[0] || null;
            }
            renderCodexQuestModal();
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const questModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                questModal.show();
            } else {
                $(modalEl).modal('show');
            }
        }
    } catch (e) {
        console.error("Erreur chargement quêtes Codex:", e);
    }
}

function closeCodexQuestModal() {
    const modalEl = document.getElementById('modalCodexQuest');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const questModal = bootstrap.Modal.getInstance(modalEl);
        if (questModal) questModal.hide();
    }
}

// Alias de rétrocompatibilité
const openQuestModal = openCodexQuestModal;
const closeQuestModal = closeCodexQuestModal;
const claimQuestReward = executeClaimCodexQuest;

function renderCodexQuestModal() {
    if (!cachedQuestsData || !currentCodexQuest) return;

    const q = currentCodexQuest;
    const totalQuests = cachedQuestsData.total_quests || 12;
    const claimedCount = cachedQuestsData.claimed_count || 0;
    const overallPercent = cachedQuestsData.overall_percent || 0;
    const isClaimed = (q.status === 'claimed');
    const isClaimable = Boolean(q.is_claimable);

    // Image d'en-tête (215px)
    const imgEl = document.getElementById('codexQuestModalImage');
    if (imgEl) {
        imgEl.src = q.image_url || '/public/assets/quests/quest_01_wood_field.jpg';
    }

    // Badge catégorie
    const catBadge = document.getElementById('codexQuestModalCategoryBadge');
    if (catBadge) {
        catBadge.innerText = `⛩️ Étape ${q.order}/${totalQuests} • ${(q.category || 'TERROIR').toUpperCase()}`;
    }

    // Titre
    const titleEl = document.getElementById('codexQuestModalTitle');
    if (titleEl) {
        titleEl.innerHTML = (q.icon ? `${q.icon} ` : '') + escapeHtmlModal(q.title);
    }

    // Badge Statut
    const statusBadge = document.getElementById('codexQuestModalStatusBadge');
    if (statusBadge) {
        if (isClaimed) {
            statusBadge.className = 'badge bg-secondary text-white font-monospace fs-6 px-3 py-1 shadow-sm';
            statusBadge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Déjà Réclamé';
        } else if (isClaimable) {
            statusBadge.className = 'badge bg-success text-white font-monospace fs-6 px-3 py-1 shadow-sm';
            statusBadge.innerHTML = '<i class="fa-solid fa-sparkles me-1"></i>Objectif Atteint !';
        } else {
            statusBadge.className = 'badge bg-warning text-dark font-monospace fs-6 px-3 py-1 shadow-sm';
            statusBadge.innerHTML = '<i class="fa-solid fa-hourglass-half me-1"></i>En cours';
        }
    }

    // Mentor & Dialogue
    const mentorName = document.getElementById('codexQuestModalMentorName');
    if (mentorName) {
        mentorName.innerText = `${q.mentor_name || 'Katsumoto, Maître d\'Armes'} :`;
    }
    const loreEl = document.getElementById('codexQuestModalLore');
    if (loreEl) {
        loreEl.innerText = `« ${q.lore} »`;
    }

    // Objectif & Emplacement
    const objEl = document.getElementById('codexQuestModalObjective');
    if (objEl) {
        objEl.innerText = q.objective || '';
    }
    const slotEl = document.getElementById('codexQuestModalSlotHint');
    if (slotEl) {
        slotEl.innerText = q.target_slot_hint || 'Fief';
    }

    // Récompenses
    const rewardsContainer = document.getElementById('codexQuestModalRewardsList');
    if (rewardsContainer) {
        let rHtml = '';
        if (q.rewards) {
            if (q.rewards.metal) {
                rHtml += `<span class="badge bg-white border text-dark"><i class="fa-solid fa-tree text-success me-1"></i>+${Number(q.rewards.metal).toLocaleString()} Bois</span>`;
            }
            if (q.rewards.crystal) {
                rHtml += `<span class="badge bg-white border text-dark"><i class="fa-solid fa-mountain text-secondary me-1"></i>+${Number(q.rewards.crystal).toLocaleString()} Pierre</span>`;
            }
            if (q.rewards.deuterium) {
                rHtml += `<span class="badge bg-white border text-dark"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>+${Number(q.rewards.deuterium).toLocaleString()} Riz</span>`;
            }
            if (q.rewards.points) {
                rHtml += `<span class="badge bg-white border text-dark"><i class="fa-solid fa-torii-gate text-danger me-1"></i>+${Number(q.rewards.points).toLocaleString()} Honneur</span>`;
            }
            if (q.rewards.bonus_units) {
                rHtml += `<span class="badge bg-danger text-white"><i class="fa-solid fa-khanda me-1"></i>+${q.rewards.bonus_units} Guerriers</span>`;
            }
        }
        rewardsContainer.innerHTML = rHtml || '<span class="text-muted small">Bénédiction du Daimyō</span>';
    }

    // Progression globale
    const progPct = document.getElementById('codexQuestModalProgressPct');
    if (progPct) {
        progPct.innerText = `${claimedCount}/${totalQuests} (${overallPercent}%)`;
    }
    const progBar = document.getElementById('codexQuestModalProgressBar');
    if (progBar) {
        progBar.style.width = `${overallPercent}%`;
    }

    // Bouton d'action
    const actionContainer = document.getElementById('codexQuestModalActionContainer');
    if (actionContainer) {
        if (isClaimable) {
            actionContainer.innerHTML = `
                <button type="button" class="btn btn-success fw-bold px-4 shadow-sm pulse-btn" onclick="executeClaimCodexQuest('${q.key}')">
                    <i class="fa-solid fa-gift me-1"></i>Réclamer ma Récompense
                </button>
            `;
        } else if (isClaimed) {
            actionContainer.innerHTML = `
                <button type="button" class="btn btn-secondary px-3" disabled>
                    <i class="fa-solid fa-check me-1"></i>Récompense perçue
                </button>
            `;
        } else {
            if (q.action_url && q.action_url.startsWith('javascript:')) {
                actionContainer.innerHTML = `
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="closeCodexQuestModal(); ${q.action_url.substring(11)};">
                        ${escapeHtmlModal(q.action_label || 'Se rendre sur place')} &rarr;
                    </button>
                `;
            } else {
                actionContainer.innerHTML = `
                    <a href="${escapeHtmlModal(q.action_url || '#')}" class="btn btn-primary fw-bold px-4 shadow-sm">
                        ${escapeHtmlModal(q.action_label || 'Se rendre sur place')} &rarr;
                    </a>
                `;
            }
        }
    }
}

async function executeClaimCodexQuest(questKey) {
    try {
        const formData = new FormData();
        formData.append('action', 'claim');
        formData.append('quest_key', questKey);

        const res = await fetch('/api/quests.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            closeCodexQuestModal();

            if (data.planet) {
                updateHudResources(data.planet);
            }

            let rewardDetailMsg = '';
            if (data.rewards.metal) rewardDetailMsg += `+${Number(data.rewards.metal).toLocaleString()} Bois, `;
            if (data.rewards.crystal) rewardDetailMsg += `+${Number(data.rewards.crystal).toLocaleString()} Pierre, `;
            if (data.rewards.deuterium) rewardDetailMsg += `+${Number(data.rewards.deuterium).toLocaleString()} Riz, `;
            if (data.rewards.points) rewardDetailMsg += `+${data.rewards.points} Honneur, `;
            if (data.bonus_units) rewardDetailMsg += `+${data.bonus_units} ${data.rewarded_unit_name}, `;
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

// ---------------------------------------------------------------------------
// VÉRIFICATION AUTOMATIQUE NON-INTRUSIVE DU CODEX AU CHARGEMENT DE PAGE
// Ne s'ouvre JAMAIS à l'apparition d'une nouvelle quête non terminée.
// S'ouvre UNIQUEMENT lorsque les conditions sont remplies (is_claimable === true).
// Ne s'ouvre qu'UNE SEULE FOIS par session (sessionStorage) pour ne pas être intempestif.
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    const currentLoggedUserId = <?= (int)($user['id'] ?? 0) ?>;
    if (!currentLoggedUserId) return;

    fetch('/api/quests.php?action=get_status')
        .then(res => res.json())
        .then(json => {
            if (json.success && json.data) {
                cachedQuestsData = json.data;
                const activeQ = cachedQuestsData.active_quest;
                if (activeQ && activeQ.is_claimable) {
                    const sessionKey = `codex_quest_notified_${currentLoggedUserId}_${activeQ.key}_claimable`;
                    if (!sessionStorage.getItem(sessionKey)) {
                        sessionStorage.setItem(sessionKey, '1');
                        if (window.ModalQueueManager) {
                            window.ModalQueueManager.enqueue({
                                id: 'codex_quest_' + activeQ.key,
                                priority: 30,
                                modalElement: '#modalCodexQuest',
                                open: () => openCodexQuestModal(activeQ.key)
                            });
                        } else {
                            setTimeout(() => {
                                openCodexQuestModal(activeQ.key);
                            }, 700);
                        }
                    }
                }
            }
        })
        .catch(err => {
            console.warn("Vérification statut quête codex:", err);
        });
});

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
    if (planet.fish !== undefined) updateEl('res-val-fish', 'bar-fish', planet.fish, planet.fish_max || planet.deuterium_max || 15000);
    if (planet.rice_flour !== undefined) updateEl('res-val-rice-flour', 'bar-rice-flour', planet.rice_flour, planet.rice_flour_max || 10000);
    if (planet.sake !== undefined) updateEl('res-val-sake', 'bar-sake', planet.sake, planet.sake_max || 10000);
    if (planet.wooden_beams !== undefined) updateEl('res-val-wooden-beams', 'bar-wooden-beams', planet.wooden_beams, planet.wooden_beams_max || 10000);
    if (planet.clay !== undefined) updateEl('res-val-clay', 'bar-clay', planet.clay, planet.clay_max || 10000);
    if (planet.tea !== undefined) updateEl('res-val-tea', 'bar-tea', planet.tea, planet.tea_max || 10000);
    if (planet.soybean !== undefined) updateEl('res-val-soybean', 'bar-soybean', planet.soybean, planet.soybean_max || 10000);
    if (planet.iron_sand !== undefined) updateEl('res-val-iron-sand', 'bar-iron-sand', planet.iron_sand, planet.iron_sand_max || 15000);
    if (planet.sulfur !== undefined) updateEl('res-val-sulfur', 'bar-sulfur', planet.sulfur, planet.sulfur_max || 10000);
    if (planet.nitrate !== undefined) updateEl('res-val-nitrate', 'bar-nitrate', planet.nitrate, planet.nitrate_max || 10000);
    if (planet.roof_tiles !== undefined) updateEl('res-val-roof-tiles', 'bar-roof-tiles', planet.roof_tiles, planet.roof_tiles_max || 15000);
}
</script>
<!-- Tabler JS -->
<script src="/public/js/tabler/tabler.min.js"></script>
<script>
// Initialisation universelle des infobulles & popovers Tabler / Bootstrap
document.addEventListener("DOMContentLoaded", function () {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
        });
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.forEach(function (popoverTriggerEl) {
            new bootstrap.Popover(popoverTriggerEl, { boundary: document.body });
        });
    }
});

// Thème clair 100% natif Tabler.io - Nettoyage et verrouillage du thème clair
(function() {
    try {
        localStorage.removeItem('shogun-theme');
    } catch(e) {}
    document.documentElement.setAttribute('data-bs-theme', 'light');
})();
function toggleTheme() {}
</script>
<?php require_once __DIR__ . '/chat_widget.php'; ?>
<!-- Script Modale Transparence Prompts IA -->
<script src="/public/js/ai_prompt_modal.js"></script>
</body>
</html>

