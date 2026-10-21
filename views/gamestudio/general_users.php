<?php
/**
 * Vue Partielle MVC : Console de Gestion des Joueurs & Fiefs (Game Studio)
 * Fichier : views/gamestudio/general_users.php
 */

$kpis = $usersKpis ?? [
    'total_users'        => 0,
    'active_15m'         => 0,
    'active_24h'         => 0,
    'protected_users'    => 0,
    'imperial_seal_users'=> 0,
    'banned_users'       => 0,
    'faction_counts'     => ['terran' => 0, 'vorash' => 0, 'aethelis' => 0],
];

$users = $usersList ?? [];
$filters = $activeFilters ?? ['search' => '', 'faction' => 'all', 'status' => 'all', 'sort' => 'points_desc'];
$currentPage = (int)($currentPage ?? 1);
$totalPages = (int)($totalPages ?? 1);
$filteredTotal = (int)($filteredTotal ?? count($users));
$perPage = (int)($perPage ?? 25);
?>

<div class="gamestudio-users-module">
    <!-- ── 1. BANDEAU DE MÉTRIQUES & KPIS DAIMYŌS ── -->
    <div class="row row-cards mb-4">
        <!-- KPI 1 : Population Humaine & Factions -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-primary shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Daimyōs Enregistrés</div>
                        <span class="avatar avatar-sm rounded bg-primary-lt text-primary fs-3">
                            <i class="fa-solid fa-users"></i>
                        </span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-primary"><?= number_format((int)$kpis['total_users'], 0, ',', ' ') ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex gap-2 flex-wrap">
                        <span title="Clan Oda (Terran)" class="badge bg-blue-lt">
                            <i class="fa-solid fa-chess-rook me-1"></i>Oda: <?= (int)($kpis['faction_counts']['terran'] ?? 0) ?>
                        </span>
                        <span title="Clan Takeda (Vorash)" class="badge bg-red-lt">
                            <i class="fa-solid fa-horse me-1"></i>Takeda: <?= (int)($kpis['faction_counts']['vorash'] ?? 0) ?>
                        </span>
                        <span title="Clan Tokugawa (Aethelis)" class="badge bg-purple-lt">
                            <i class="fa-solid fa-torii-gate me-1"></i>Tokugawa: <?= (int)($kpis['faction_counts']['aethelis'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2 : Activité en direct & 24h -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-success shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Activité Daimyō</div>
                        <span class="avatar avatar-sm rounded bg-success-lt text-success fs-3">
                            <i class="fa-solid fa-bolt"></i>
                        </span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-success d-flex align-items-baseline gap-2">
                        <span><?= number_format((int)$kpis['active_15m']) ?></span>
                        <span class="badge bg-success-lt fs-6 fw-normal animate-pulse">En direct (&lt;15m)</span>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-clock-rotate-left text-success me-1"></i><strong><?= number_format((int)$kpis['active_24h']) ?></strong> actifs lors des dernières 24h
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3 : Grâces & Immunités -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Grâces &amp; Protections</div>
                        <span class="avatar avatar-sm rounded bg-warning-lt text-warning fs-3">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-warning"><?= number_format((int)$kpis['protected_users']) ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                        <span><i class="fa-solid fa-shield-heart text-warning me-1"></i>Sous bouclier débutant</span>
                        <span class="badge bg-yellow-lt" title="Sceau Impérial actif"><i class="fa-solid fa-certificate me-1"></i><?= (int)$kpis['imperial_seal_users'] ?> Sceaux</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4 : Modération & Sanctions -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-danger shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Discipline Shogunale</div>
                        <span class="avatar avatar-sm rounded bg-danger-lt text-danger fs-3">
                            <i class="fa-solid fa-gavel"></i>
                        </span>
                    </div>
                    <div class="h1 m-0 font-weight-bold <?= $kpis['banned_users'] > 0 ? 'text-danger' : 'text-secondary' ?>">
                        <?= number_format((int)$kpis['banned_users']) ?>
                    </div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <?php if ($kpis['banned_users'] > 0): ?>
                            <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Comptes suspendus / bannis</span>
                        <?php else: ?>
                            <span class="text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>Aucun compte banni</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. BARRE D'OUTILS DE RECHERCHE & FILTRES AVANCÉS ── -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-end" id="usersFilterForm">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="general">
                <input type="hidden" name="subtab" value="users">

                <!-- Recherche texte -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Recherche Daimyō / Fief / Coordonnées
                    </label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="fa-solid fa-user-ninja"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Pseudo, courriel, nom de fief ou [X:Y]..." 
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <!-- Filtre Faction -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">Faction</label>
                    <select name="faction" class="form-select form-select-sm">
                        <option value="all" <?= ($filters['faction'] ?? '') === 'all' ? 'selected' : '' ?>>Toutes les Factions</option>
                        <option value="terran" <?= ($filters['faction'] ?? '') === 'terran' ? 'selected' : '' ?>>Clan Oda (Bleu)</option>
                        <option value="vorash" <?= ($filters['faction'] ?? '') === 'vorash' ? 'selected' : '' ?>>Clan Takeda (Rouge)</option>
                        <option value="aethelis" <?= ($filters['faction'] ?? '') === 'aethelis' ? 'selected' : '' ?>>Clan Tokugawa (Pourpre)</option>
                    </select>
                </div>

                <!-- Filtre Statut -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">Statut &amp; Rôle</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>Tous les Statuts</option>
                        <option value="online" <?= ($filters['status'] ?? '') === 'online' ? 'selected' : '' ?>>🟢 En ligne (&lt;15m)</option>
                        <option value="active24h" <?= ($filters['status'] ?? '') === 'active24h' ? 'selected' : '' ?>>⚡ Actifs 24h</option>
                        <option value="protected" <?= ($filters['status'] ?? '') === 'protected' ? 'selected' : '' ?>>🛡️ Sous Immunité</option>
                        <option value="seal" <?= ($filters['status'] ?? '') === 'seal' ? 'selected' : '' ?>>👑 Sceau Impérial</option>
                        <option value="banned" <?= ($filters['status'] ?? '') === 'banned' ? 'selected' : '' ?>>⛔ Suspendus / Bannis</option>
                        <option value="admin" <?= ($filters['status'] ?? '') === 'admin' ? 'selected' : '' ?>>⭐ Administrateurs</option>
                        <option value="moderator" <?= ($filters['status'] ?? '') === 'moderator' ? 'selected' : '' ?>>🛡️ Modérateurs</option>
                    </select>
                </div>

                <!-- Tri -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">Ordre de Tri</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="points_desc" <?= ($filters['sort'] ?? '') === 'points_desc' ? 'selected' : '' ?>>Points (Décroissant)</option>
                        <option value="points_asc" <?= ($filters['sort'] ?? '') === 'points_asc' ? 'selected' : '' ?>>Points (Croissant)</option>
                        <option value="active_desc" <?= ($filters['sort'] ?? '') === 'active_desc' ? 'selected' : '' ?>>Dernière Activité</option>
                        <option value="colonies_desc" <?= ($filters['sort'] ?? '') === 'colonies_desc' ? 'selected' : '' ?>>Nombre de Fiefs</option>
                        <option value="koban_desc" <?= ($filters['sort'] ?? '') === 'koban_desc' ? 'selected' : '' ?>>Trésor de Koban</option>
                        <option value="id_desc" <?= ($filters['sort'] ?? '') === 'id_desc' ? 'selected' : '' ?>>Plus récents (ID)</option>
                        <option value="id_asc" <?= ($filters['sort'] ?? '') === 'id_asc' ? 'selected' : '' ?>>Plus anciens (ID)</option>
                    </select>
                </div>

                <!-- Boutons d'action filtres -->
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Filtrer
                    </button>
                    <a href="?page=gamestudio&tab=general&subtab=users" class="btn btn-sm btn-outline-secondary" title="Réinitialiser les filtres">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ── 3. REGISTRE CENTRAL DES DAIMYŌS ── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <h3 class="card-title text-indigo m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-users-gear text-indigo"></i>
                <span>Registre Impérial des Daimyōs Joueurs</span>
            </h3>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-indigo-lt fw-bold font-monospace">
                    <?= number_format($filteredTotal, 0, ',', ' ') ?> résultat<?= $filteredTotal > 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table text-nowrap">
                <thead class="bg-light">
                    <tr>
                        <th class="w-1">ID</th>
                        <th>Daimyō</th>
                        <th>Faction / Clan</th>
                        <th>Capitale &amp; Fiefs</th>
                        <th class="text-end">Prestige &amp; Héros</th>
                        <th class="text-center">Protection &amp; Sceau</th>
                        <th class="text-end"><i class="fa-solid fa-coins text-warning me-1"></i>Koban</th>
                        <th class="text-center w-1">Commandes GM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="fa-solid fa-user-slash fs-1 text-secondary"></i></div>
                                <div class="fw-bold">Aucun Daimyō trouvé</div>
                                <div class="small">Aucun compte joueur ne correspond à vos critères de recherche actuels.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php 
                                $isBanned = ((int)($u['is_active'] ?? 1) === 0);
                                $isOnline = !empty($u['is_online']);
                                $fKey = $u['faction'] ?? 'terran';
                                $fBadgeClass = match($fKey) {
                                    'vorash'  => 'badge bg-red-lt border border-danger-subtle',
                                    'aethelis'=> 'badge bg-purple-lt border border-purple-subtle',
                                    default   => 'badge bg-blue-lt border border-primary-subtle'
                                };
                                $fIcon = match($fKey) {
                                    'vorash'  => '<i class="fa-solid fa-horse text-danger me-1"></i>Clan Takeda',
                                    'aethelis'=> '<i class="fa-solid fa-torii-gate text-purple me-1"></i>Clan Tokugawa',
                                    default   => '<i class="fa-solid fa-chess-rook text-primary me-1"></i>Clan Oda'
                                };
                                $prot = $u['protection'] ?? null;
                                $isProtected = $prot && !empty($prot['is_protected']);
                                $isSeal = !empty($u['seal_active']);
                            ?>
                            <tr class="<?= $isBanned ? 'bg-danger-subtle' : '' ?>" id="user-row-<?= (int)$u['id'] ?>">
                                <!-- ID -->
                                <td class="text-secondary small font-monospace">#<?= (int)$u['id'] ?></td>

                                <!-- Daimyō (Avatar, Nom, Email, Status) -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <span class="avatar rounded-circle bg-indigo-lt fw-bold">
                                                <?= strtoupper(substr($u['username'], 0, 2)) ?>
                                            </span>
                                            <?php if ($isOnline): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="En ligne actuellement"></span>
                                            <?php else: ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-secondary border border-white rounded-circle" title="Hors-ligne"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold d-flex align-items-center gap-1">
                                                <?= htmlspecialchars($u['username']) ?>
                                                <?php if ((int)$u['id'] === (int)Auth::id()): ?>
                                                    <span class="badge bg-indigo text-white ms-1" style="font-size:0.65rem;">VOUS</span>
                                                <?php endif; ?>
                                                <?php if (!empty($u['is_admin'])): ?>
                                                    <span class="badge bg-danger-lt" style="font-size:0.65rem;" title="Administrateur Shogunat">
                                                        <i class="fa-solid fa-crown me-1"></i>Admin
                                                    </span>
                                                <?php elseif (!empty($u['is_moderator'])): ?>
                                                    <span class="badge bg-teal-lt" style="font-size:0.65rem;" title="Modérateur Féodal">
                                                        <i class="fa-solid fa-shield me-1"></i>Modo
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($isBanned): ?>
                                                    <span class="badge bg-danger text-white ms-1" style="font-size:0.65rem;">
                                                        <i class="fa-solid fa-ban me-1"></i>BANNI
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted font-monospace" style="font-size:0.75rem;">
                                                <?= htmlspecialchars($u['email']) ?>
                                                <?php if (!empty($u['alliance_tag'])): ?>
                                                    &bull; <span class="badge bg-secondary-lt">[<?= htmlspecialchars($u['alliance_tag']) ?>]</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Faction & Clan -->
                                <td>
                                    <span class="<?= $fBadgeClass ?>"><?= $fIcon ?></span>
                                </td>

                                <!-- Capitale & Fiefs -->
                                <td>
                                    <div>
                                        <?php if ($u['capital_x'] !== null && $u['capital_y'] !== null): ?>
                                            <a href="?page=map&x=<?= (int)$u['capital_x'] ?>&y=<?= (int)$u['capital_y'] ?>" 
                                               class="badge bg-azure-lt font-monospace text-decoration-none" 
                                               title="Voir la capitale sur la carte féodale" target="_blank">
                                                <i class="fa-solid fa-location-dot me-1"></i>[<?= (int)$u['capital_x'] ?>:<?= (int)$u['capital_y'] ?>]
                                            </a>
                                            <span class="small text-secondary ms-1"><?= htmlspecialchars($u['capital_name'] ?? 'Capitale') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Aucune capitale</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="fa-solid fa-castle me-1"></i><strong><?= (int)$u['colony_count'] ?></strong> fief<?= (int)$u['colony_count'] > 1 ? 's' : '' ?>
                                    </div>
                                </td>

                                <!-- Prestige & Héros -->
                                <td class="text-end">
                                    <div class="fw-bold font-monospace text-primary">
                                        <?= number_format((int)$u['points'], 0, ',', ' ') ?> pts
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <?php if (!empty($u['hero_name'])): ?>
                                            <i class="fa-solid fa-user-shield me-1 text-secondary"></i>
                                            <?= htmlspecialchars($u['hero_name']) ?> <span class="badge bg-light text-dark font-monospace">Niv.<?= (int)$u['hero_level'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sans champion</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Protection & Sceau -->
                                <td class="text-center">
                                    <?php if ($isProtected): ?>
                                        <span class="badge bg-warning-lt d-inline-flex align-items-center gap-1" title="Protégé jusqu'au <?= htmlspecialchars($prot['until_formatted'] ?? '') ?>">
                                            <i class="fa-solid fa-shield-halved text-warning"></i>
                                            <span><?= htmlspecialchars($prot['formatted'] ?? 'Protégé') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt text-muted">Expirée</span>
                                    <?php endif; ?>

                                    <?php if ($isSeal): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-yellow text-dark fw-bold font-monospace" title="Sceau Impérial décrété">
                                                <i class="fa-solid fa-crown me-1"></i>Sceau Actif
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Koban -->
                                <td class="text-end font-monospace fw-bold text-warning" id="user-koban-<?= (int)$u['id'] ?>">
                                    <?= number_format((int)$u['gold_coins'], 0, ',', ' ') ?> 🪙
                                </td>

                                <!-- Commandes GM -->
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm shadow-none" role="group">
                                        <!-- Bouton Inspecter Fiche 360° -->
                                        <button type="button" class="btn btn-outline-indigo" 
                                                title="Inspecter la fiche complète du Daimyō (Fiefs, Ressources, Héros)"
                                                onclick="openDaimyoDrawer(<?= (int)$u['id'] ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <!-- Bouton Don de Koban -->
                                        <button type="button" class="btn btn-outline-warning" 
                                                title="Créditer des Koban (Pièces d'Or)"
                                                onclick="openGiftKobanModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                            <i class="fa-solid fa-coins"></i>
                                        </button>

                                        <!-- Menu déroulant actions supplémentaires -->
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); openDaimyoDrawer(<?= (int)$u['id'] ?>)">
                                                        <i class="fa-solid fa-address-card text-indigo"></i>
                                                        <span>Fiche Complète 360°</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>

                                                <!-- Immunité -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); extendUserProtection(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', 7)">
                                                        <i class="fa-solid fa-shield-halved text-warning"></i>
                                                        <span>Prolonger Immunité (+7 jours)</span>
                                                    </a>
                                                </li>
                                                <?php if ($isProtected): ?>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" onclick="event.preventDefault(); revokeUserProtection(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                                            <i class="fa-solid fa-shield-slash"></i>
                                                            <span>Lever l'Immunité Débutant</span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>

                                                <li><hr class="dropdown-divider my-1"></li>

                                                <!-- Sécurité / Mot de passe -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); openResetPasswordModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                                        <i class="fa-solid fa-key text-teal"></i>
                                                        <span>Réinitialiser Mot de Passe</span>
                                                    </a>
                                                </li>

                                                <!-- Rôles -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); toggleUserAdmin(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', <?= (int)($u['is_admin'] ?? 0) ?>)">
                                                        <i class="fa-solid fa-crown text-purple"></i>
                                                        <span><?= !empty($u['is_admin']) ? 'Rétrograder Admin' : 'Promouvoir Admin' ?></span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); toggleUserModerator(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', <?= (int)($u['is_moderator'] ?? 0) ?>)">
                                                        <i class="fa-solid fa-shield text-azure"></i>
                                                        <span><?= !empty($u['is_moderator']) ? 'Rétrograder Modo' : 'Promouvoir Modo' ?></span>
                                                    </a>
                                                </li>

                                                <li><hr class="dropdown-divider my-1"></li>

                                                <!-- Sanction Ban / Déban -->
                                                <?php if ((int)$u['id'] !== (int)Auth::id()): ?>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" onclick="event.preventDefault(); toggleUserBan(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', <?= $isBanned ? 0 : 1 ?>)">
                                                            <i class="fa-solid <?= $isBanned ? 'fa-user-check' : 'fa-gavel' ?>"></i>
                                                            <span><?= $isBanned ? 'Réhabiliter le compte (Débannir)' : 'Suspendre le compte (Bannir)' ?></span>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── 4. PAGINATION ── -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 bg-white">
                <div class="text-secondary small">
                    Page <strong><?= $currentPage ?></strong> sur <strong><?= $totalPages ?></strong> &bull; Total : <strong><?= number_format($filteredTotal) ?></strong> Daimyōs
                </div>
                <ul class="pagination pagination-sm m-0">
                    <!-- Page précédente -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $filters) ?>" tabindex="-1" aria-disabled="<?= $currentPage <= 1 ? 'true' : 'false' ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>

                    <?php 
                        $startP = max(1, $currentPage - 2);
                        $endP = min($totalPages, $currentPage + 2);
                        if ($startP > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $filters) . '">1</a></li>';
                            if ($startP > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                        }
                        for ($p = $startP; $p <= $endP; $p++) {
                            $activeClass = ($p === $currentPage) ? 'active' : '';
                            echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . buildPaginationUrl($p, $filters) . '">' . $p . '</a></li>';
                        }
                        if ($endP < $totalPages) {
                            if ($endP < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $filters) . '">' . $totalPages . '</a></li>';
                        }
                    ?>

                    <!-- Page suivante -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $filters) ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════════════ -->
<!-- MODALES DU MODULE GESTION DES JOUEURS                                     -->
<!-- ═════════════════════════════════════════════════════════════════════════ -->

<!-- 1. MODALE D'INSPECTION 360° DU DAIMYŌ -->
<div class="modal fade" id="modal-daimyo-drawer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-md rounded bg-indigo-lt text-white fs-3" id="drawer-avatar">
                        <i class="fa-solid fa-user-ninja"></i>
                    </span>
                    <div>
                        <h4 class="modal-title d-flex align-items-center gap-2 m-0 text-white" id="drawer-username">
                            Chargement du Daimyō...
                        </h4>
                        <div class="small text-white-50 mt-1" id="drawer-subtitle">
                            Console d'inspection et d'assistance Shogunale
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Onglets de la Fiche Daimyō -->
            <div class="card-tabs border-bottom bg-light">
                <ul class="nav nav-tabs px-3 pt-2" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold py-2 px-3 text-indigo" data-bs-toggle="tab" href="#drawer-tab-fiefs">
                            <i class="fa-solid fa-castle me-1"></i>Fiefs &amp; Économie
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-2 px-3 text-teal" data-bs-toggle="tab" href="#drawer-tab-hero">
                            <i class="fa-solid fa-user-shield me-1"></i>Héros Shogunal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-2 px-3 text-primary" data-bs-toggle="tab" href="#drawer-tab-security">
                            <i class="fa-solid fa-shield-halved me-1"></i>Sécurité &amp; Assistance GM
                        </a>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <!-- Loader initial -->
                <div id="drawer-loading-spinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-2 small">Récupération des données impériales du Daimyō...</div>
                </div>

                <div id="drawer-content-body" class="d-none">
                    <div class="tab-content">
                        <!-- SOUS-ONGLET 1 : FIEFS & ÉCONOMIE -->
                        <div class="tab-pane active show" id="drawer-tab-fiefs">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0 text-secondary fw-bold">Domaines Provinciaux &amp; Stocks</h5>
                                <span class="badge bg-primary-lt" id="drawer-fiefs-count">0 fiefs</span>
                            </div>
                            <div id="drawer-fiefs-list" class="row g-3">
                                <!-- Injecté par JS -->
                            </div>
                        </div>

                        <!-- SOUS-ONGLET 2 : HÉROS SHOGUNAL -->
                        <div class="tab-pane" id="drawer-tab-hero">
                            <div id="drawer-hero-container">
                                <!-- Injecté par JS -->
                            </div>
                        </div>

                        <!-- SOUS-ONGLET 3 : SÉCURITÉ & ASSISTANCE GM -->
                        <div class="tab-pane" id="drawer-tab-security">
                            <div class="row g-4">
                                <!-- Actions Rapides Sécurité -->
                                <div class="col-md-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="card-title m-0 text-teal"><i class="fa-solid fa-key me-1"></i>Gestion des Identifiants</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <p class="small text-muted mb-3">
                                                Générez un mot de passe provisoire hautement sécurisé pour ce Daimyō en cas de perte ou de blocage d'accès.
                                            </p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Nouveau Mot de Passe (laisser vide pour auto-générer) :</label>
                                                <input type="text" id="drawer-custom-pwd" class="form-control form-control-sm font-monospace" placeholder="Optionnel...">
                                            </div>
                                            <button type="button" class="btn btn-sm btn-teal w-100" onclick="triggerDrawerPasswordReset()">
                                                <i class="fa-solid fa-arrows-rotate me-1"></i>Appliquer le Réarmement du Mot de Passe
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions de Modération & Sanction -->
                                <div class="col-md-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="card-title m-0 text-danger"><i class="fa-solid fa-gavel me-1"></i>Discipline &amp; Suspension</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="drawer-ban-status-box" class="mb-3">
                                                <!-- Injecté par JS -->
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Motif de la sanction :</label>
                                                <input type="text" id="drawer-ban-reason" class="form-control form-control-sm" placeholder="Infraction aux règles féodales...">
                                            </div>
                                            <button type="button" id="drawer-btn-toggle-ban" class="btn btn-sm btn-danger w-100" onclick="triggerDrawerToggleBan()">
                                                <i class="fa-solid fa-gavel me-1"></i>Modifier l'État du Compte
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer la Fiche</button>
            </div>
        </div>
    </div>
</div>

<!-- 2. MODALE DON DE KOBAN EXPRESS -->
<div class="modal fade" id="modal-gift-koban" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0 fw-bold">
                    <i class="fa-solid fa-coins"></i> Gratification Shogunale en Koban
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Octroyer une dotation impériale de Kobans (pièces d'or) à <strong id="gift-koban-username"></strong>. 
                    Une missive officielle du Shogunat lui sera automatiquement délivrée.
                </p>
                <input type="hidden" id="gift-koban-user-id" value="0">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Montant de Koban à octroyer :</label>
                    <div class="input-group input-group-flat">
                        <input type="number" id="gift-koban-amount" class="form-control" value="500" min="1" step="50">
                        <span class="input-group-text">🪙 Koban</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning flex-fill" onclick="document.getElementById('gift-koban-amount').value = 100">+100</button>
                    <button type="button" class="btn btn-sm btn-outline-warning flex-fill" onclick="document.getElementById('gift-koban-amount').value = 500">+500</button>
                    <button type="button" class="btn btn-sm btn-outline-warning flex-fill" onclick="document.getElementById('gift-koban-amount').value = 1000">+1 000</button>
                    <button type="button" class="btn btn-sm btn-outline-warning flex-fill" onclick="document.getElementById('gift-koban-amount').value = 5000">+5 000</button>
                </div>
            </div>
            <div class="modal-footer border-top-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-warning fw-bold text-dark" id="btn-confirm-gift-koban" onclick="submitGiftKoban()">
                    <i class="fa-solid fa-gift me-1"></i>Accorder la Gratification
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 3. MODALE DON DE RESSOURCES EXPRESS (SUR FIEF) -->
<div class="modal fade" id="modal-gift-resources" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0 fw-bold">
                    <i class="fa-solid fa-boxes-stacked"></i> Approvisionnement Shogunal de Fief
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Créditer des subsides d'urgence sur le fief <strong id="gift-res-planet-name"></strong>.
                </p>
                <input type="hidden" id="gift-res-planet-id" value="0">
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-primary">Riz (Métal) :</label>
                        <input type="number" id="gift-res-metal" class="form-control form-control-sm" value="50000" min="0" step="5000">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold text-azure">Bois (Cristal) :</label>
                        <input type="number" id="gift-res-crystal" class="form-control form-control-sm" value="30000" min="0" step="5000">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold text-indigo">Fer (Deutérium) :</label>
                        <input type="number" id="gift-res-deuterium" class="form-control form-control-sm" value="15000" min="0" step="2500">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setGiftResPreset(10000, 5000, 2500)">Léger</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setGiftResPreset(50000, 30000, 15000)">Standard</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setGiftResPreset(200000, 100000, 50000)">Massif</button>
                </div>
            </div>
            <div class="modal-footer border-top-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-success fw-bold" id="btn-confirm-gift-res" onclick="submitGiftResources()">
                    <i class="fa-solid fa-truck-ramp-box me-1"></i>Livrer les Provisions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 4. MODALE TÉLÉPORTATION EXPRESS (SUR FIEF) -->
<div class="modal fade" id="modal-teleport-colony" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-azure text-white py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 m-0 fw-bold">
                    <i class="fa-solid fa-arrows-up-down-left-right"></i> Téléportation Impériale de Fief
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Déplacer le fief <strong id="teleport-planet-name"></strong> vers de nouvelles coordonnées vacantes.
                </p>
                <input type="hidden" id="teleport-planet-id" value="0">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Coordonnée X :</label>
                        <input type="number" id="teleport-coord-x" class="form-control form-control-sm font-monospace" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Coordonnée Y :</label>
                        <input type="number" id="teleport-coord-y" class="form-control form-control-sm font-monospace" value="0">
                    </div>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>L'emplacement doit être libre de toute colonie ou forteresse impériale et contenu dans le rayon du monde.
                </div>
            </div>
            <div class="modal-footer border-top-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-azure fw-bold" id="btn-confirm-teleport" onclick="submitTeleportColony()">
                    <i class="fa-solid fa-location-arrow me-1"></i>Téléporter le Fief
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════════════ -->
<!-- SCRIPTS JAVASCRIPT DU MODULE GESTION DES JOUEURS                          -->
<!-- ═════════════════════════════════════════════════════════════════════════ -->
<script>
let currentInspectedUserId = 0;
let currentInspectedData = null;

function getAdminApiUrl() {
    return '/api/admin.php';
}

// ── 1. MODALE D'INSPECTION 360° DU DAIMYŌ ──
async function openDaimyoDrawer(userId) {
    currentInspectedUserId = userId;
    const modalEl = document.getElementById('modal-daimyo-drawer');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    
    document.getElementById('drawer-loading-spinner').classList.remove('d-none');
    document.getElementById('drawer-content-body').classList.add('d-none');
    document.getElementById('drawer-username').textContent = 'Chargement en cours...';
    document.getElementById('drawer-subtitle').textContent = 'Console d\'inspection Shogunale';
    
    bsModal.show();

    try {
        const res = await fetch(`${getAdminApiUrl()}?action=get_user_profile&user_id=${userId}`);
        const data = await res.json();
        
        if (!data.success || !data.profile) {
            showDevAlert('danger', data.error || 'Impossible de récupérer la fiche du Daimyō.');
            bsModal.hide();
            return;
        }

        currentInspectedData = data.profile;
        renderDaimyoDrawer(data.profile);
    } catch (e) {
        showDevAlert('danger', 'Erreur réseau lors de la récupération de la fiche Daimyō.');
        bsModal.hide();
    }
}

function renderDaimyoDrawer(profile) {
    const user = profile.user;
    const planets = profile.planets || [];
    const hero = profile.hero;

    // En-tête
    document.getElementById('drawer-avatar').textContent = user.username.substring(0, 2).toUpperCase();
    document.getElementById('drawer-username').innerHTML = `
        <span>${escapeHtml(user.username)}</span>
        ${user.is_admin == 1 ? '<span class="badge bg-danger ms-2">Admin</span>' : ''}
        ${user.is_moderator == 1 ? '<span class="badge bg-teal ms-1">Modo</span>' : ''}
        ${user.is_active == 0 ? '<span class="badge bg-danger text-white ms-1">BANNI</span>' : ''}
        ${user.is_online ? '<span class="badge bg-success ms-1">🟢 En direct</span>' : '<span class="badge bg-secondary ms-1">Hors-ligne</span>'}
    `;
    document.getElementById('drawer-subtitle').innerHTML = `
        <i class="fa-solid fa-envelope me-1"></i>${escapeHtml(user.email)} &bull; 
        <i class="fa-solid fa-coins text-warning me-1"></i><strong>${Number(user.gold_coins).toLocaleString()} Koban</strong> &bull; 
        <i class="fa-solid fa-award text-primary me-1"></i><strong>${Number(user.points).toLocaleString()} Pts</strong>
    `;

    // Onglet Fiefs
    document.getElementById('drawer-fiefs-count').textContent = `${planets.length} fief${planets.length > 1 ? 's' : ''}`;
    const fiefsContainer = document.getElementById('drawer-fiefs-list');
    if (planets.length === 0) {
        fiefsContainer.innerHTML = '<div class="col-12 text-center py-4 text-muted">Ce joueur ne possède aucun fief.</div>';
    } else {
        fiefsContainer.innerHTML = planets.map(p => `
            <div class="col-12 col-lg-6">
                <div class="card border shadow-none h-100">
                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            <i class="fa-solid fa-castle text-primary"></i>
                            <span>${escapeHtml(p.name)}</span>
                            ${p.is_capital == 1 ? '<span class="badge bg-indigo-lt">Capitale</span>' : ''}
                        </div>
                        <a href="?page=map&x=${p.coord_x}&y=${p.coord_y}" target="_blank" class="badge bg-azure-lt font-monospace text-decoration-none">
                            [${p.coord_x}:${p.coord_y}]
                        </a>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 text-center small mb-3">
                            <div class="col-4 bg-light rounded py-1">
                                <div class="text-muted" style="font-size:0.7rem;">Riz (Métal)</div>
                                <div class="fw-bold text-primary">${Number(Math.floor(p.metal)).toLocaleString()}</div>
                            </div>
                            <div class="col-4 bg-light rounded py-1">
                                <div class="text-muted" style="font-size:0.7rem;">Bois (Cristal)</div>
                                <div class="fw-bold text-azure">${Number(Math.floor(p.crystal)).toLocaleString()}</div>
                            </div>
                            <div class="col-4 bg-light rounded py-1">
                                <div class="text-muted" style="font-size:0.7rem;">Fer (Deutérium)</div>
                                <div class="fw-bold text-indigo">${Number(Math.floor(p.deuterium)).toLocaleString()}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success flex-fill" onclick="openGiftResourcesModal(${p.id}, '${escapeJs(p.name)}')">
                                <i class="fa-solid fa-plus me-1"></i>Subsides
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-azure flex-fill" onclick="openTeleportModal(${p.id}, '${escapeJs(p.name)}', ${p.coord_x}, ${p.coord_y})">
                                <i class="fa-solid fa-location-arrow me-1"></i>Téléporter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Onglet Héros
    const heroBox = document.getElementById('drawer-hero-container');
    if (!hero) {
        heroBox.innerHTML = '<div class="text-center py-4 text-muted"><i class="fa-solid fa-user-slash fs-2 mb-2"></i><div>Ce Daimyō n\'a pas encore éveillé son Héros Champion.</div></div>';
    } else {
        heroBox.innerHTML = `
            <div class="card border shadow-none">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-md rounded bg-teal-lt fs-3"><i class="fa-solid fa-user-ninja"></i></span>
                            <div>
                                <h5 class="m-0">${escapeHtml(hero.name || 'Champion')}</h5>
                                <div class="small text-muted">Niveau <strong class="text-dark">${hero.level}</strong> &bull; Expérience : <strong>${Number(hero.experience).toLocaleString()}</strong> XP</div>
                            </div>
                        </div>
                        <div>
                            <span class="badge ${hero.health > 50 ? 'bg-success-lt' : (hero.health > 20 ? 'bg-warning-lt' : 'bg-danger-lt')} fs-6 font-monospace">
                                <i class="fa-solid fa-heart-pulse me-1"></i>${Math.round(hero.health)}% PV
                            </span>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 small">
                        <div class="col-sm-3"><span class="text-muted">Statut :</span> <strong>${escapeHtml(hero.status || 'home')}</strong></div>
                        <div class="col-sm-3"><span class="text-muted">Force :</span> <strong>${hero.stat_strength || 0}</strong></div>
                        <div class="col-sm-3"><span class="text-muted">Bonus Attaque :</span> <strong>+${hero.stat_offense_bonus || 0}%</strong></div>
                        <div class="col-sm-3"><span class="text-muted">Bonus Défense :</span> <strong>+${hero.stat_defense_bonus || 0}%</strong></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap border-top pt-3">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="adminHealHero(${hero.user_id})">
                            <i class="fa-solid fa-hand-holding-medical me-1"></i>Soigner 100% PV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="adminReviveHero(${hero.user_id})">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Ressusciter Immédiatement
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="adminResetHeroQuota(${hero.user_id})">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>Reset Quota Quotidien
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Onglet Sécurité / Sanctions
    const isBanned = (user.is_active == 0);
    const banBox = document.getElementById('drawer-ban-status-box');
    const banBtn = document.getElementById('drawer-btn-toggle-ban');
    if (isBanned) {
        banBox.innerHTML = `
            <div class="alert alert-danger py-2 small mb-0">
                <i class="fa-solid fa-ban me-1"></i><strong>Ce compte est actuellement SUSPENDU / BANNI.</strong> Le Daimyō ne peut plus se connecter.
            </div>
        `;
        banBtn.className = 'btn btn-sm btn-success w-100';
        banBtn.innerHTML = '<i class="fa-solid fa-user-check me-1"></i>Réhabiliter et Débannir le Compte';
    } else {
        banBox.innerHTML = `
            <div class="alert alert-success py-2 small mb-0">
                <i class="fa-solid fa-circle-check me-1"></i>Ce compte est actuellement <strong>ACTIF</strong> et en règle.
            </div>
        `;
        banBtn.className = 'btn btn-sm btn-danger w-100';
        banBtn.innerHTML = '<i class="fa-solid fa-gavel me-1"></i>Suspendre / Bannir le Compte';
    }

    document.getElementById('drawer-loading-spinner').classList.add('d-none');
    document.getElementById('drawer-content-body').classList.remove('d-none');
}

// ── 2. DON DE KOBAN EXPRESS ──
function openGiftKobanModal(userId, username) {
    document.getElementById('gift-koban-user-id').value = userId;
    document.getElementById('gift-koban-username').textContent = username;
    document.getElementById('gift-koban-amount').value = 500;
    const modalEl = document.getElementById('modal-gift-koban');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
}

async function submitGiftKoban() {
    const userId = parseInt(document.getElementById('gift-koban-user-id').value, 10);
    const amount = parseInt(document.getElementById('gift-koban-amount').value, 10);
    const btn = document.getElementById('btn-confirm-gift-koban');

    if (!userId || isNaN(amount) || amount <= 0) {
        alert('Veuillez spécifier un montant valide supérieur à 0.');
        return;
    }

    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi...';

    try {
        const formData = new FormData();
        formData.append('action', 'give_koban');
        formData.append('user_id', userId);
        formData.append('amount', amount);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-gift-koban')).hide();
            showDevAlert('success', json.message || `+${amount} Koban octroyés avec succès !`);
            const kobanCell = document.getElementById(`user-koban-${userId}`);
            if (kobanCell && json.new_balance !== undefined) {
                kobanCell.innerHTML = `${Number(json.new_balance).toLocaleString()} 🪙`;
            }
        } else {
            alert(json.error || 'Erreur lors de l\'octroi des Koban.');
        }
    } catch (e) {
        alert('Erreur réseau lors de la communication avec le serveur.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ── 3. DON DE RESSOURCES EXPRESS SUR FIEF ──
function openGiftResourcesModal(planetId, planetName) {
    document.getElementById('gift-res-planet-id').value = planetId;
    document.getElementById('gift-res-planet-name').textContent = planetName;
    setGiftResPreset(50000, 30000, 15000);
    const modalEl = document.getElementById('modal-gift-resources');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
}

function setGiftResPreset(m, c, d) {
    document.getElementById('gift-res-metal').value = m;
    document.getElementById('gift-res-crystal').value = c;
    document.getElementById('gift-res-deuterium').value = d;
}

async function submitGiftResources() {
    const planetId = parseInt(document.getElementById('gift-res-planet-id').value, 10);
    const metal = parseFloat(document.getElementById('gift-res-metal').value) || 0;
    const crystal = parseFloat(document.getElementById('gift-res-crystal').value) || 0;
    const deuterium = parseFloat(document.getElementById('gift-res-deuterium').value) || 0;
    const btn = document.getElementById('btn-confirm-gift-res');

    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Livraison...';

    try {
        const formData = new FormData();
        formData.append('action', 'give_resources');
        formData.append('planet_id', planetId);
        formData.append('metal', metal);
        formData.append('crystal', crystal);
        formData.append('deuterium', deuterium);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-gift-resources')).hide();
            showDevAlert('success', json.message);
            if (currentInspectedUserId > 0) {
                openDaimyoDrawer(currentInspectedUserId);
            }
        } else {
            alert(json.error || 'Erreur lors de la livraison des ressources.');
        }
    } catch (e) {
        alert('Erreur réseau lors de la communication avec le serveur.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ── 4. TÉLÉPORTATION EXPRESS SUR FIEF ──
function openTeleportModal(planetId, planetName, currentX, currentY) {
    document.getElementById('teleport-planet-id').value = planetId;
    document.getElementById('teleport-planet-name').textContent = planetName;
    document.getElementById('teleport-coord-x').value = currentX;
    document.getElementById('teleport-coord-y').value = currentY;
    const modalEl = document.getElementById('modal-teleport-colony');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
}

async function submitTeleportColony() {
    const planetId = parseInt(document.getElementById('teleport-planet-id').value, 10);
    const newX = parseInt(document.getElementById('teleport-coord-x').value, 10);
    const newY = parseInt(document.getElementById('teleport-coord-y').value, 10);
    const btn = document.getElementById('btn-confirm-teleport');

    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Téléportation...';

    try {
        const formData = new FormData();
        formData.append('action', 'teleport_colony');
        formData.append('planet_id', planetId);
        formData.append('coord_x', newX);
        formData.append('coord_y', newY);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-teleport-colony')).hide();
            showDevAlert('success', json.message);
            if (currentInspectedUserId > 0) {
                openDaimyoDrawer(currentInspectedUserId);
            } else {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            alert(json.error || 'Échec de la téléportation.');
        }
    } catch (e) {
        alert('Erreur réseau lors de la téléportation.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}

// ── 5. RÉINITIALISATION DE MOT DE PASSE ──
function openResetPasswordModal(userId, username) {
    showConfirmModal(
        "Réarmement de Mot de Passe",
        `Voulez-vous générer un nouveau mot de passe provisoire pour <strong>${escapeHtml(username)}</strong> ?`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'reset_user_password');
                formData.append('user_id', userId);

                const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    showDevAlert('success', `
                        <strong>Mot de passe réinitialisé avec succès pour ${escapeHtml(username)} !</strong><br>
                        Nouveau mot de passe provisoire : <code class="fs-4 select-all text-dark font-monospace">${escapeHtml(json.temporary_password)}</code><br>
                        <small class="text-muted">Veuillez transmettre ce code au joueur.</small>
                    `);
                } else {
                    showDevAlert('danger', json.error || 'Échec de la réinitialisation.');
                }
            } catch (e) {
                showDevAlert('danger', 'Erreur réseau lors de la réinitialisation.');
            }
        },
        'btn-teal',
        '<i class="fa-solid fa-key"></i>'
    );
}

async function triggerDrawerPasswordReset() {
    if (!currentInspectedUserId) return;
    const customPwd = document.getElementById('drawer-custom-pwd').value.trim();

    try {
        const formData = new FormData();
        formData.append('action', 'reset_user_password');
        formData.append('user_id', currentInspectedUserId);
        if (customPwd) formData.append('new_password', customPwd);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert(`Succès ! Nouveau mot de passe provisoire : ${json.temporary_password}`);
            document.getElementById('drawer-custom-pwd').value = '';
        } else {
            alert(json.error || 'Échec de la réinitialisation.');
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

// ── 6. IMMUNITÉ DÉBUTANT ──
async function extendUserProtection(userId, username, days) {
    try {
        const formData = new FormData();
        formData.append('action', 'extend_protection');
        formData.append('user_id', userId);
        formData.append('days', days);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            showDevAlert('success', `Immunité de <strong>${escapeHtml(username)}</strong> prolongée de ${days} jours avec succès !`);
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showDevAlert('danger', json.error || 'Échec de la prolongation.');
        }
    } catch (e) {
        showDevAlert('danger', 'Erreur réseau.');
    }
}

async function revokeUserProtection(userId, username) {
    showConfirmModal(
        "Lever l'Immunité Débutant",
        `Voulez-vous vraiment lever le bouclier d'immunité de <strong>${escapeHtml(username)}</strong> dès à présent ?`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'revoke_protection');
                formData.append('user_id', userId);

                const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    showDevAlert('success', `Immunité de <strong>${escapeHtml(username)}</strong> levée avec succès.`);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showDevAlert('danger', json.error || 'Échec de la levée d\'immunité.');
                }
            } catch (e) {
                showDevAlert('danger', 'Erreur réseau.');
            }
        },
        'btn-warning',
        '<i class="fa-solid fa-shield-slash"></i>'
    );
}

// ── 7. BANNISSEMENT & SUSPENSION ──
async function toggleUserBan(userId, username, targetStatus) {
    const actionTxt = targetStatus === 0 ? 'réhabiliter et débannir' : 'suspendre et bannir';
    const btnClass = targetStatus === 0 ? 'btn-success' : 'btn-danger';

    showConfirmModal(
        "Discipline Shogunale",
        `Êtes-vous certain de vouloir <strong>${actionTxt}</strong> le compte de <strong>${escapeHtml(username)}</strong> ?`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_ban');
                formData.append('user_id', userId);

                const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    showDevAlert(targetStatus === 0 ? 'success' : 'warning', json.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showDevAlert('danger', json.error || 'Échec de la modification du statut.');
                }
            } catch (e) {
                showDevAlert('danger', 'Erreur réseau.');
            }
        },
        btnClass,
        '<i class="fa-solid fa-gavel"></i>'
    );
}

async function triggerDrawerToggleBan() {
    if (!currentInspectedUserId) return;
    const reason = document.getElementById('drawer-ban-reason').value.trim();

    try {
        const formData = new FormData();
        formData.append('action', 'toggle_ban');
        formData.append('user_id', currentInspectedUserId);
        if (reason) formData.append('reason', reason);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert(json.message);
            openDaimyoDrawer(currentInspectedUserId);
        } else {
            alert(json.error || 'Échec de la modification.');
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

// ── 8. PRIVILÈGES ADMIN & MODÉRATEUR ──
async function toggleUserAdmin(userId, username, currentStatus) {
    const actionTxt = currentStatus === 1 ? "rétrograder au rang de Daimyō Joueur" : "promouvoir au rang de Shogun Administrateur";
    showConfirmModal(
        "Privilèges Shogunat",
        `Voulez-vous vraiment ${actionTxt} le Daimyō <strong>${escapeHtml(username)}</strong> ?`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_admin');
                formData.append('user_id', userId);

                const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    showDevAlert('success', json.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showDevAlert('danger', json.error || 'Échec de l\'opération.');
                }
            } catch (e) {
                showDevAlert('danger', 'Erreur réseau.');
            }
        },
        currentStatus === 1 ? 'btn-danger' : 'btn-purple',
        '<i class="fa-solid fa-crown"></i>'
    );
}

async function toggleUserModerator(userId, username, currentStatus) {
    const actionTxt = currentStatus === 1 ? "retirer du corps des modérateurs" : "promouvoir au rang de Modérateur Féodal";
    showConfirmModal(
        "Modération Shogunale",
        `Voulez-vous vraiment ${actionTxt} le Daimyō <strong>${escapeHtml(username)}</strong> ?`,
        async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_moderator');
                formData.append('user_id', userId);

                const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    showDevAlert('success', json.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showDevAlert('danger', json.error || 'Échec de l\'opération.');
                }
            } catch (e) {
                showDevAlert('danger', 'Erreur réseau.');
            }
        },
        currentStatus === 1 ? 'btn-secondary' : 'btn-azure',
        '<i class="fa-solid fa-shield"></i>'
    );
}

// ── 9. ACTIONS RAPIDES HÉROS ──
async function adminHealHero(userId) {
    try {
        const formData = new FormData();
        formData.append('action', 'admin_heal_hero');
        formData.append('user_id', userId);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert(json.message || 'Héros soigné à 100% PV !');
            openDaimyoDrawer(userId);
        } else {
            alert(json.error || 'Erreur lors du soin.');
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function adminReviveHero(userId) {
    try {
        const formData = new FormData();
        formData.append('action', 'admin_revive_hero');
        formData.append('user_id', userId);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert(json.message || 'Héros ressuscité immédiatement !');
            openDaimyoDrawer(userId);
        } else {
            alert(json.error || 'Erreur lors de la résurrection.');
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function adminResetHeroQuota(userId) {
    try {
        const formData = new FormData();
        formData.append('action', 'admin_reset_hero_quota');
        formData.append('user_id', userId);

        const res = await fetch(getAdminApiUrl(), { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert(json.message || 'Quota quotidien d\'aventures réinitialisé !');
            openDaimyoDrawer(userId);
        } else {
            alert(json.error || 'Erreur lors de la réinitialisation.');
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

// Utilitaires de sécurité d'affichage
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/['"\\]/g, '\\$&');
}
</script>

<?php
/**
 * Helper de génération d'URL pour la pagination conservant les filtres actifs
 */
function buildPaginationUrl(int $page, array $filters): string {
    $params = [
        'page'   => 'gamestudio',
        'tab'    => 'general',
        'subtab' => 'users',
        'user_page' => $page
    ];
    if (!empty($filters['search'])) $params['search'] = $filters['search'];
    if (!empty($filters['faction']) && $filters['faction'] !== 'all') $params['faction'] = $filters['faction'];
    if (!empty($filters['status']) && $filters['status'] !== 'all') $params['status'] = $filters['status'];
    if (!empty($filters['sort']) && $filters['sort'] !== 'points_desc') $params['sort'] = $filters['sort'];
    return '?' . http_build_query($params);
}
?>

