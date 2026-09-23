<?php
/**
 * Vue Dédiée : Pavillon Diplomatique & Gestion des Alliances Féodales (OpenShogun)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AllianceEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

$allianceEngine = new AllianceEngine();
$myAlliance = $allianceEngine->getUserAlliance((int)$user['id']);
$userEmbassyLevel = $allianceEngine->getUserMaxEmbassyLevel((int)$user['id']);

$isLeader = ($myAlliance && (int)$myAlliance['leader_id'] === (int)$user['id']);
$members = $myAlliance ? $allianceEngine->getAllianceMembers((int)$myAlliance['id']) : [];
$pendingSentInvites = $isLeader ? $allianceEngine->getAlliancePendingInvitations((int)$myAlliance['id']) : [];
$pendingReceivedInvites = !$myAlliance ? $allianceEngine->getUserPendingInvitations((int)$user['id']) : [];
$alliancesRanking = $allianceEngine->getAlliancesRanking(20);

// Capacité unlocked par le joueur actuel s'il fonde une alliance
$potentialCapacity = min(ALLIANCE_MAX_MEMBERS, max(ALLIANCE_CREATION_MIN_EMBASSY_LEVEL, $userEmbassyLevel) * ALLIANCE_SLOTS_PER_EMBASSY_LEVEL);
?>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Affaires Extérieures & Diplomatie Féodale</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span>🎌</span>
                <?php if ($myAlliance): ?>
                    Alliance [<?= htmlspecialchars($myAlliance['tag']) ?>] <?= htmlspecialchars($myAlliance['name']) ?>
                <?php else: ?>
                    Pavillon Diplomatique & Alliances
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="/?page=building&code=embassy" class="btn btn-outline-secondary">
                    ⛩️ Mon Pavillon Diplomatique (Niv.<?= $userEmbassyLevel ?>)
                </a>
                <a href="/?page=ranking&tab=alliances" class="btn btn-secondary">
                    🏆 Classement des Clans
                </a>
            </div>
        </div>
    </div>
</div>

<div id="allianceAlertBox"></div>

<?php if ($myAlliance): ?>
    <!-- ========================================== -->
    <!-- CAS 1 : LE JOUEUR EST MEMBRE D'UNE ALLIANCE -->
    <!-- ========================================== -->
    <?php
    $memberCount = (int)$myAlliance['member_count'];
    $capacity = (int)$myAlliance['capacity'];
    $capacityPct = ($capacity > 0) ? min(100, round(($memberCount / $capacity) * 100)) : 100;
    $barClass = ($capacityPct >= 95) ? 'bg-danger' : (($capacityPct >= 75) ? 'bg-warning' : 'bg-primary');
    ?>

    <!-- Bandeau Récapitulatif du Clan Féodal -->
    <div class="card mb-4" style="border: 1px solid rgba(194, 37, 43, 0.25); background: linear-gradient(135deg, rgba(194,37,43,0.03) 0%, rgba(255,255,255,0.98) 100%);">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size: 2.8rem; line-height: 1;">🎌</div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="mb-0" style="font-weight: 800; color: #1e293b;">
                                    <?= htmlspecialchars($myAlliance['name']) ?>
                                </h2>
                                <span class="badge bg-danger text-white fs-6 px-2 py-1">
                                    [<?= htmlspecialchars($myAlliance['tag']) ?>]
                                </span>
                                <?php if ($isLeader): ?>
                                    <span class="badge bg-warning text-dark">👑 Vous êtes le Chef</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary small">
                                Scellée le <?= date('d/m/Y à H:i', strtotime($myAlliance['created_at'])) ?> · 
                                Chef de clan : <strong><?= htmlspecialchars($myAlliance['leader_name']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 mt-3 mt-md-0">
                    <div class="p-3 rounded" style="background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold" style="font-size: 0.85rem;">Capacité du Clan</span>
                            <span class="badge <?= $myAlliance['is_full'] ? 'bg-danger' : 'bg-secondary' ?>" style="font-size: 0.78rem;">
                                <?= $memberCount ?> / <?= $capacity ?> Daimyōs (Max <?= ALLIANCE_MAX_MEMBERS ?>)
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $capacityPct ?>%;" aria-valuenow="<?= $capacityPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted mt-2" style="font-size: 0.75rem;">
                            <span>Puissance globale : <strong class="text-danger"><?= number_format($myAlliance['total_points']) ?> pts</strong></span>
                            <span><?= ALLIANCE_SLOTS_PER_EMBASSY_LEVEL ?> places / niv. d'ambassade</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation par Onglets du Clan -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-members" class="nav-link active fw-bold" data-bs-toggle="tab">
                        👥 Membres du Clan (<?= count($members) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-charter" class="nav-link fw-bold" data-bs-toggle="tab">
                        📜 Charte & Serment d'Honneur
                    </a>
                </li>
                <?php if ($isLeader): ?>
                    <li class="nav-item">
                        <a href="#tab-recruitment" class="nav-link fw-bold" data-bs-toggle="tab">
                            ✉️ Recrutement & Invitations (<?= count($pendingSentInvites) ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-management" class="nav-link fw-bold text-danger" data-bs-toggle="tab">
                            ⚙️ Conseil de Guerre & Commandement
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item ms-auto">
                    <a href="#tab-alliances-list" class="nav-link text-secondary" data-bs-toggle="tab">
                        🔍 Autres Alliances
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                <!-- ONGLET 1 : MEMBRES DU CLAN -->
                <div class="tab-pane active show" id="tab-members">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.02);">
                                    <th style="width: 50px;">Rang</th>
                                    <th>Daimyō</th>
                                    <th>Rôle</th>
                                    <th>Clan d'Origine</th>
                                    <th class="text-center">Fiefs</th>
                                    <th class="text-end">Puissance Féodale</th>
                                    <th class="text-center">Dernière Activité</th>
                                    <?php if ($isLeader): ?>
                                        <th class="text-center" style="width: 90px;">Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($members as $m): ?>
                                    <?php 
                                    $isMe = ((int)$m['id'] === (int)$user['id']);
                                    $fInfo = FACTIONS[$m['faction']] ?? FACTIONS['terran'];
                                    $timeDiff = time() - strtotime($m['last_active']);
                                    $isOnline = ($timeDiff < 300);
                                    ?>
                                    <tr style="<?= $isMe ? 'background: rgba(194,37,43,0.05);' : '' ?>">
                                        <td class="fw-bold text-muted">#<?= $rank++ ?></td>
                                        <td>
                                            <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= $m['id'] ?>)" class="fw-bold text-decoration-none">
                                                <?= htmlspecialchars($m['username']) ?>
                                            </a>
                                            <?php if ($isMe): ?>
                                                <span class="badge bg-danger-lt ms-1">Vous</span>
                                            <?php endif; ?>
                                            <?php if (Auth::isUserProtected($m)): ?>
                                                <span class="badge bg-success-lt ms-1" title="Immunité débutant active">🔰</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($m['is_leader']): ?>
                                                <span class="badge bg-warning text-dark">👑 Chef Suprême</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt">⚔️ Frère d'Armes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="faction-badge <?= $m['faction'] ?>">
                                                <?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold"><?= number_format($m['planet_count']) ?></td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($m['points']) ?></td>
                                        <td class="text-center small">
                                            <?php if ($isOnline): ?>
                                                <span class="badge bg-success-lt">En ligne</span>
                                            <?php elseif ($timeDiff < 86400): ?>
                                                <span class="text-muted">Aujourd'hui</span>
                                            <?php else: ?>
                                                <span class="text-muted"><?= round($timeDiff / 86400) ?> j.</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($isLeader): ?>
                                            <td class="text-center">
                                                <?php if (!$m['is_leader']): ?>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="kickMember(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['username'])) ?>')"
                                                            title="Bannir du clan">
                                                        Bannir
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$isLeader): ?>
                        <div class="mt-4 pt-3 border-top text-end">
                            <button class="btn btn-outline-danger" onclick="leaveAlliance()">
                                🚪 Quitter l'Alliance Féodale
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ONGLET 2 : CHARTE & SERMENT -->
                <div class="tab-pane" id="tab-charter">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="p-4 rounded border bg-light">
                                <h3 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <span>📜</span> Charte d'Honneur du Clan [<?= htmlspecialchars($myAlliance['tag']) ?>]
                                </h3>
                                <div style="font-size: 0.95rem; line-height: 1.7; color: #334155; white-space: pre-line;">
                                    <?= !empty($myAlliance['description']) ? htmlspecialchars($myAlliance['description']) : "<em>Aucune charte féodale n'a encore été proclamée par le Chef de clan.</em>" ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($isLeader): ?>
                            <div class="col-lg-4 mt-3 mt-lg-0">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Éditer la Charte</h4>
                                    </div>
                                    <div class="card-body">
                                        <form id="formUpdateCharter" onsubmit="updateCharter(event)">
                                            <div class="mb-3">
                                                <label class="form-label">Texte de la Charte / Présentation</label>
                                                <textarea id="charterText" class="form-control" rows="8" placeholder="Exprimez ici les principes directeurs, pactes et ambitions de votre alliance..."><?= htmlspecialchars($myAlliance['description'] ?? '') ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100" id="btnSaveCharter">
                                                💾 Sauvegarder la Charte
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ONGLET 3 : RECRUTEMENT & INVITATIONS (LEADER) -->
                <?php if ($isLeader): ?>
                    <div class="tab-pane" id="tab-recruitment">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h4 class="card-title d-flex align-items-center gap-2">
                                            <span>✉️</span> Transmettre une Invitation
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($myAlliance['is_full']): ?>
                                            <div class="alert alert-warning mb-3">
                                                <strong>Capacité maximale atteinte (<?= $memberCount ?>/<?= $capacity ?>).</strong><br>
                                                Vous devez améliorer le Pavillon Diplomatique de votre cité castrale pour pouvoir inviter d'autres seigneurs (+3 places par niveau).
                                            </div>
                                        <?php else: ?>
                                            <form id="formSendInvite" onsubmit="sendInvitation(event)">
                                                <div class="mb-3">
                                                    <label class="form-label required">Pseudo du Daimyō</label>
                                                    <input type="text" id="inviteUsername" class="form-control" placeholder="Nom exact du seigneur" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Missive d'Accompagnement (Optionnel)</label>
                                                    <input type="text" id="inviteMessage" class="form-control" placeholder="Rejoignez notre bannière féodale !">
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100" id="btnSendInvite">
                                                    🎌 Sceller & Expédier l'Invitation
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h4 class="card-title">Invitations Diplomatiques en Cours</h4>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if (empty($pendingSentInvites)): ?>
                                            <div class="p-4 text-center text-muted">
                                                Aucune invitation féodale n'est actuellement en attente de réponse.
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-vcenter">
                                                    <thead>
                                                        <tr>
                                                            <th>Daimyō Invité</th>
                                                            <th>Points</th>
                                                            <th>Date</th>
                                                            <th class="text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($pendingSentInvites as $inv): ?>
                                                            <tr>
                                                                <td class="fw-bold"><?= htmlspecialchars($inv['target_username']) ?></td>
                                                                <td class="text-danger fw-bold"><?= number_format($inv['target_points']) ?></td>
                                                                <td class="small text-muted"><?= date('d/m H:i', strtotime($inv['created_at'])) ?></td>
                                                                <td class="text-end">
                                                                    <button class="btn btn-sm btn-outline-secondary" onclick="cancelInvitation(<?= $inv['id'] ?>)">
                                                                        Annuler
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET 4 : CONSEIL DE GUERRE & COMMANDEMENT (LEADER) -->
                    <div class="tab-pane" id="tab-management">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning-lt">
                                        <h4 class="card-title text-warning-emphasis">👑 Cession du Titre de Chef Suprême</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="small text-muted">
                                            Vous pouvez transmettre les insignes de commandement et la tête du clan à un frère d'armes de confiance.
                                        </p>
                                        <form id="formTransferLeadership" onsubmit="transferLeadership(event)">
                                            <div class="mb-3">
                                                <label class="form-label">Désigner le nouveau Chef :</label>
                                                <select id="newLeaderSelect" class="form-select" required>
                                                    <option value="">Sélectionnez un membre...</option>
                                                    <?php foreach ($members as $m): ?>
                                                        <?php if (!$m['is_leader']): ?>
                                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['username']) ?> (<?= number_format($m['points']) ?> pts)</option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100" id="btnTransferLeader">
                                                📜 Transmettre le Commandement
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger-lt">
                                        <h4 class="card-title text-danger">☠️ Dissolution du Pacte d'Alliance</h4>
                                    </div>
                                    <div class="card-body">
                                        <p class="small text-danger">
                                            <strong>Attention :</strong> La dissolution est irréversible. Tous les membres retrouveront leur statut indépendant et l'alliance sera effacée des registres impériaux.
                                        </p>
                                        <button class="btn btn-danger w-100 mt-3" onclick="disbandAlliance()">
                                            💥 Dissoudre Définitivement l'Alliance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ONGLET 5 : AUTRES ALLIANCES -->
                <div class="tab-pane" id="tab-alliances-list">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Alliance</th>
                                    <th>Chef</th>
                                    <th class="text-center">Membres / Capacité</th>
                                    <th class="text-end">Puissance Globale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($alliancesRanking as $r): ?>
                                    <tr style="<?= ($r['id'] == $myAlliance['id']) ? 'background: rgba(194,37,43,0.06); font-weight:bold;' : '' ?>">
                                        <td>#<?= $rank++ ?></td>
                                        <td>
                                            <span class="badge bg-danger text-white me-1">[<?= htmlspecialchars($r['tag']) ?>]</span>
                                            <?= htmlspecialchars($r['name']) ?>
                                            <?= ($r['id'] == $myAlliance['id']) ? '<span class="badge bg-secondary-lt ms-1">Votre clan</span>' : '' ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['leader_name']) ?></td>
                                        <td class="text-center"><?= $r['member_count'] ?> / <?= $r['capacity'] ?></td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($r['total_points']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================== -->
    <!-- CAS 2 : LE JOUEUR N'A PAS ENCORE D'ALLIANCE -->
    <!-- ========================================== -->

    <!-- Boîte d'information sur le Pavillon Diplomatique -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title d-flex align-items-center gap-2">
                        <span>⛩️</span> Statut de votre Pavillon Diplomatique
                    </h3>
                    <span class="badge <?= ($userEmbassyLevel >= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL) ? 'bg-success' : (($userEmbassyLevel >= ALLIANCE_JOIN_MIN_EMBASSY_LEVEL) ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                        Niveau <?= $userEmbassyLevel ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($userEmbassyLevel < ALLIANCE_JOIN_MIN_EMBASSY_LEVEL): ?>
                        <div class="alert alert-secondary d-flex align-items-center gap-3">
                            <div style="font-size: 2rem;">🏗️</div>
                            <div>
                                <strong>Pavillon Diplomatique non bâti.</strong><br>
                                Vous devez construire le Pavillon Diplomatique (Niveau <?= ALLIANCE_JOIN_MIN_EMBASSY_LEVEL ?>) dans votre Cité Castrale pour pouvoir sceller des pactes et rejoindre une alliance.
                            </div>
                        </div>
                        <a href="/?page=building&code=embassy" class="btn btn-outline-primary">
                            Edifier le Pavillon Diplomatique
                        </a>

                    <?php elseif ($userEmbassyLevel < ALLIANCE_CREATION_MIN_EMBASSY_LEVEL): ?>
                        <div class="alert alert-info d-flex align-items-center gap-3">
                            <div style="font-size: 2rem;">📜</div>
                            <div>
                                <strong>Pavillon Diplomatique de Niveau <?= $userEmbassyLevel ?>/<?= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL ?>.</strong><br>
                                Vous pouvez <strong>rejoindre</strong> une alliance féodale existante dès maintenant. Pour avoir l'autorité d'en <strong>fonder une nouvelle</strong>, vous devez élever ce bâtiment au <strong>Niveau <?= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL ?></strong>.
                            </div>
                        </div>
                        <a href="/?page=building&code=embassy" class="btn btn-outline-primary">
                            Améliorer au Niveau 3 (Débloquer la Création)
                        </a>

                    <?php else: ?>
                        <div class="alert alert-success d-flex align-items-center gap-3">
                            <div style="font-size: 2rem;">🎌</div>
                            <div>
                                <strong>Pavillon Diplomatique de Rang Supérieur (Niveau <?= $userEmbassyLevel ?>).</strong><br>
                                Vos émissaires sont prêts ! Vous avez le prestige nécessaire pour proclamer une nouvelle alliance féodale pouvant accueillir <strong><?= $potentialCapacity ?> Daimyōs</strong> dès sa création (+3 places par niveau jusqu'à 60 max).
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 pt-3 border-top small text-muted">
                        <strong>Règles de l'Alliance Féodale :</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Rejoindre une alliance : Pavillon Diplomatique Niv. <?= ALLIANCE_JOIN_MIN_EMBASSY_LEVEL ?> requis.</li>
                            <li>Fonder une alliance : Pavillon Diplomatique Niv. <?= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL ?> requis.</li>
                            <li>Capacité : <strong>3 membres</strong> par niveau d'ambassade du Chef (Plafond maximal à <strong>60 joueurs</strong> au niveau 20).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de Fondation de Clan (si Niv >= 3) ou encouragement -->
        <div class="col-md-5 mt-3 mt-md-0">
            <div class="card h-100" style="border: 1px solid <?= ($userEmbassyLevel >= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL) ? 'rgba(194,37,43,0.3)' : 'rgba(0,0,0,0.1)' ?>;">
                <div class="card-header <?= ($userEmbassyLevel >= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL) ? 'bg-danger-lt text-danger fw-bold' : '' ?>">
                    <h3 class="card-title">🎌 Fonder une Alliance Féodale</h3>
                </div>
                <div class="card-body">
                    <?php if ($userEmbassyLevel >= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL): ?>
                        <form id="formCreateAlliance" onsubmit="createAlliance(event)">
                            <div class="mb-3">
                                <label class="form-label required">Nom de l'Alliance</label>
                                <input type="text" id="createName" class="form-control" placeholder="Ex: Ordre du Dragon Rouge" minlength="3" maxlength="40" required>
                                <div class="form-text">Entre 3 et 40 caractères.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Sigle / Tag (2 à 8 car.)</label>
                                <input type="text" id="createTag" class="form-control" placeholder="Ex: DRAGON" minlength="2" maxlength="8" style="text-transform: uppercase;" required>
                                <div class="form-text">Apparaîtra sur la carte et dans les classements [TAG].</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Charte / Devise (Optionnel)</label>
                                <textarea id="createDesc" class="form-control" rows="3" placeholder="Devise d'honneur ou serment de fidélité..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100" id="btnCreateAlliance">
                                🎌 Sceller le Traité & Fonder l'Alliance
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <div style="font-size: 3rem; opacity: 0.5;">🔒</div>
                            <h4 class="mt-2">Création Verrouillée</h4>
                            <p class="small">
                                Votre Pavillon Diplomatique doit atteindre le <strong>Niveau <?= ALLIANCE_CREATION_MIN_EMBASSY_LEVEL ?></strong> pour pouvoir fonder un clan féodal.
                            </p>
                            <a href="/?page=building&code=embassy" class="btn btn-sm btn-outline-secondary">
                                Élever le Pavillon (Actuel : Niv. <?= $userEmbassyLevel ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Boîte des Invitations Reçues -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h3 class="card-title d-flex align-items-center gap-2">
                <span>📬</span> Invitations Reçues des Autres Clans (<?= count($pendingReceivedInvites) ?>)
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pendingReceivedInvites)): ?>
                <div class="p-4 text-center text-muted">
                    Vous n'avez aucune invitation en attente pour le moment.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Alliance</th>
                                <th>Émissaire</th>
                                <th>Message</th>
                                <th class="text-center">Effectif / Capacité</th>
                                <th>Date</th>
                                <th class="text-end">Décision Féodale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingReceivedInvites as $inv): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger text-white me-1">[<?= htmlspecialchars($inv['alliance_tag']) ?>]</span>
                                        <strong><?= htmlspecialchars($inv['alliance_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($inv['sender_username']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($inv['message'] ?? 'Traité d\'alliance proposé') ?></td>
                                    <td class="text-center">
                                        <?= $inv['current_members'] ?> / <?= $inv['capacity'] ?>
                                        <?php if ($inv['is_full']): ?>
                                            <span class="badge bg-danger-lt ms-1">Pleine</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($inv['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            <?php if ($userEmbassyLevel < ALLIANCE_JOIN_MIN_EMBASSY_LEVEL): ?>
                                                <button class="btn btn-sm btn-outline-secondary disabled" title="Pavillon Diplomatique Niv. 1 requis">
                                                    Niv.1 Requis
                                                </button>
                                            <?php elseif ($inv['is_full']): ?>
                                                <button class="btn btn-sm btn-outline-secondary disabled" title="Alliance complète">
                                                    Pleine
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="respondInvitation(<?= $inv['id'] ?>, 'accept')">
                                                    ✅ Prêter Serment
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="respondInvitation(<?= $inv['id'] ?>, 'reject')">
                                                ❌ Décliner
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Répertoire & Recherche des Alliances Existantes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🔍 Registre des Clans Féodaux du Japon</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vcenter table-hover">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Clan</th>
                            <th>Chef Suprême</th>
                            <th class="text-center">Membres / Capacité</th>
                            <th class="text-end">Puissance</th>
                            <th class="text-center" style="width: 140px;">Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alliancesRanking)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aucune alliance n'a encore été fondée sur ce serveur. Soyez le premier Daimyō à sceller un pacte !
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = 1; foreach ($alliancesRanking as $r): ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?= $rank++ ?></td>
                                    <td>
                                        <span class="badge bg-danger text-white me-1">[<?= htmlspecialchars($r['tag']) ?>]</span>
                                        <strong><?= htmlspecialchars($r['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($r['leader_name']) ?></td>
                                    <td class="text-center">
                                        <?= $r['member_count'] ?> / <?= $r['capacity'] ?>
                                        <?php if ($r['member_count'] >= $r['capacity']): ?>
                                            <span class="badge bg-secondary ms-1">Plein</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger"><?= number_format($r['total_points']) ?></td>
                                    <td class="text-center">
                                        <a href="?page=messages&tab=compose&to=<?= urlencode($r['leader_name']) ?>" class="btn btn-sm btn-outline-secondary" title="Envoyer une missive au chef">
                                            ✉️ Contacter
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- Script JavaScript d'Interaction Client -->
<script>
function showAlert(message, type = 'success') {
    const box = document.getElementById('allianceAlertBox');
    if (!box) return;
    box.innerHTML = `
        <div class="alert alert-${type} alert-dismissible mb-3" role="alert">
            <div class="d-flex">
                <div>${message}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    `;
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 1. Fonder une alliance
async function createAlliance(e) {
    e.preventDefault();
    const btn = document.getElementById('btnCreateAlliance');
    const name = document.getElementById('createName').value.trim();
    const tag = document.getElementById('createTag').value.trim();
    const description = document.getElementById('createDesc').value.trim();

    if (!name || !tag) {
        showAlert("Veuillez renseigner un nom et un sigle d'alliance.", 'danger');
        return;
    }

    btn.disabled = true;
    btn.innerText = "Création en cours...";

    try {
        const formData = new FormData();
        formData.append('name', name);
        formData.append('tag', tag);
        formData.append('description', description);

        const res = await fetch('/api/alliance.php?action=create', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || "Erreur lors de la création.", 'danger');
            btn.disabled = false;
            btn.innerText = "🎌 Sceller le Traité & Fonder l'Alliance";
        }
    } catch (err) {
        showAlert("Erreur réseau de communication.", 'danger');
        btn.disabled = false;
        btn.innerText = "🎌 Sceller le Traité & Fonder l'Alliance";
    }
}

// 2. Envoyer une invitation
async function sendInvitation(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSendInvite');
    const username = document.getElementById('inviteUsername').value.trim();
    const message = document.getElementById('inviteMessage').value.trim();

    if (!username) return;

    btn.disabled = true;
    btn.innerText = "Acheminement...";

    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('message', message);

        const res = await fetch('/api/alliance.php?action=invite', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || "Erreur lors de l'invitation.", 'danger');
            btn.disabled = false;
            btn.innerText = "🎌 Sceller & Expédier l'Invitation";
        }
    } catch (err) {
        showAlert("Erreur de connexion.", 'danger');
        btn.disabled = false;
        btn.innerText = "🎌 Sceller & Expédier l'Invitation";
    }
}

// 3. Annuler une invitation envoyée
async function cancelInvitation(invId) {
    if (!confirm("Voulez-vous révoquer cette invitation diplomatique ?")) return;

    try {
        const formData = new FormData();
        formData.append('invitation_id', invId);

        const res = await fetch('/api/alliance.php?action=cancel_invite', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showAlert(data.error || "Erreur lors de l'annulation.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 4. Répondre à une invitation reçue
async function respondInvitation(invId, decision) {
    const actionText = (decision === 'accept') ? "prêter serment et rejoindre cette alliance" : "décliner cette proposition";
    if (!confirm(`Confirmez-vous vouloir ${actionText} ?`)) return;

    try {
        const formData = new FormData();
        formData.append('invitation_id', invId);
        formData.append('decision', decision);

        const res = await fetch('/api/alliance.php?action=respond_invite', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || "Erreur de réponse.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 5. Exclure un membre
async function kickMember(userId, username) {
    if (!confirm(`Êtes-vous sûr de vouloir bannir le Daimyō ${username} de l'alliance ?`)) return;

    try {
        const formData = new FormData();
        formData.append('user_id', userId);

        const res = await fetch('/api/alliance.php?action=kick', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.error || "Erreur lors de l'exclusion.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 6. Quitter l'alliance
async function leaveAlliance() {
    if (!confirm("Voulez-vous réellement rompre vos serments et quitter cette alliance féodale ?")) return;

    try {
        const res = await fetch('/api/alliance.php?action=leave', { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.error || "Erreur.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 7. Céder le commandement
async function transferLeadership(e) {
    e.preventDefault();
    const newLeaderId = document.getElementById('newLeaderSelect').value;
    if (!newLeaderId) return;

    if (!confirm("ATTENTION : En cédant le titre de Chef Suprême, vous abandonnez le contrôle suprême du clan. Confirmer le transfert ?")) return;

    try {
        const formData = new FormData();
        formData.append('new_leader_id', newLeaderId);

        const res = await fetch('/api/alliance.php?action=transfer', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || "Erreur de transmission.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 8. Dissoudre l'alliance
async function disbandAlliance() {
    const confirmation = prompt("DISSOLUTION IRREVOCABLE : Tapez 'DISSOUDRE' en majuscules pour confirmer l'effacement définitif de l'alliance :");
    if (confirmation !== 'DISSOUDRE') {
        alert("Action annulée : mot de confirmation incorrect.");
        return;
    }

    try {
        const res = await fetch('/api/alliance.php?action=disband', { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAlert(data.error || "Erreur de dissolution.", 'danger');
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
    }
}

// 9. Mettre à jour la charte
async function updateCharter(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveCharter');
    const desc = document.getElementById('charterText').value;

    btn.disabled = true;
    btn.innerText = "Sauvegarde...";

    try {
        const formData = new FormData();
        formData.append('description', desc);

        const res = await fetch('/api/alliance.php?action=update_desc', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.error || "Erreur de sauvegarde.", 'danger');
            btn.disabled = false;
            btn.innerText = "💾 Sauvegarder la Charte";
        }
    } catch (err) {
        showAlert("Erreur réseau.", 'danger');
        btn.disabled = false;
        btn.innerText = "💾 Sauvegarder la Charte";
    }
}
</script>

