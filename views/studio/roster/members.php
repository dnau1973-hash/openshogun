                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h3 class="card-title mb-1">Les Métiers de l'Équipe de Développement (<?= count(DevTeamEngine::ROLES) ?> Métiers)</h3>
                            <p class="text-muted small mb-0">Chaque métier confère des habilitations concrètes en jeu et dans les coulisses de la production.</p>
                        </div>
                        <?php if ($canManageTeam): ?>
                        <div>
                            <button type="button" class="btn btn-purple d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modal-assign-role">
                                <i class="fa-solid fa-plus me-1"></i>Assigner un Métier
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grille des 8 Rôles Métiers -->
                    <div class="row row-cards mb-4">
                        <?php foreach (DevTeamEngine::ROLES as $rKey => $role): ?>
                            <?php $assignedUsers = $roleDistribution[$rKey] ?? []; ?>
                            <div class="col-md-6 col-xl-3">
                                <div class="card h-100 border shadow-none" style="background: var(--bg-surface, #fff); border-radius: 8px;">
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fs-1"><?= $role['icon'] ?></span>
                                            <span class="badge <?= $role['badge_color'] ?>"><?= htmlspecialchars($role['category']) ?></span>
                                        </div>
                                        <h4 class="card-title mb-1 text-truncate" title="<?= htmlspecialchars($role['title']) ?>">
                                            <?= htmlspecialchars($role['title']) ?>
                                        </h4>
                                        <div class="text-muted small fst-italic mb-2" style="font-size: 0.75rem;">
                                            « <?= htmlspecialchars($role['honor_title']) ?> »
                                        </div>
                                        <p class="text-muted small flex-grow-1 mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                                            <?= htmlspecialchars($role['description']) ?>
                                        </p>

                                        <!-- Membres actifs détenant le rôle -->
                                        <div class="border-top pt-2 mt-auto">
                                            <div class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Membres assignés :</div>
                                            <div class="d-flex flex-wrap gap-1" id="role-dist-list-<?= $rKey ?>">
                                                <?php if (empty($assignedUsers)): ?>
                                                    <span class="text-muted fst-italic small no-member-tag" style="font-size: 0.75rem;">Aucun pour le moment</span>
                                                <?php else: ?>
                                                    <?php foreach ($assignedUsers as $uName): ?>
                                                        <span class="badge bg-light text-dark border dist-user-tag" data-username="<?= htmlspecialchars($uName) ?>" style="font-size: 0.75rem;"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($uName) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tableau de la Dev Team -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">Membres Actifs du Studio (<?= count($allMembers) ?>)</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped" id="dev-roster-table">
                                <thead>
                                    <tr>
                                        <th>Développeur</th>
                                        <th>Faction</th>
                                        <th>Métiers Assignés</th>
                                        <th>Rang &amp; Forge XP</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allMembers as $member): ?>
                                        <tr id="member-row-<?= $member['user_id'] ?>" data-user-id="<?= $member['user_id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-sm rounded-circle bg-purple-lt fw-bold">
                                                        <?= strtoupper(substr($member['username'], 0, 1)) ?>
                                                    </span>
                                                    <div>
                                                        <strong class="text-reset member-name"><?= htmlspecialchars($member['username']) ?></strong>
                                                        <?php if ($member['is_admin']): ?>
                                                            <span class="badge bg-danger-lt ms-1" style="font-size: 0.65rem;">Admin</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-lt text-capitalize"><?= htmlspecialchars($member['faction']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center gap-1 roles-container" id="user-roles-<?= $member['user_id'] ?>">
                                                    <?php if (empty($member['roles'])): ?>
                                                        <span class="text-muted small fst-italic no-roles-placeholder">Aucun métier</span>
                                                    <?php else: ?>
                                                        <?php foreach ($member['roles'] as $r): ?>
                                                            <span class="badge <?= DevTeamEngine::ROLES[$r['id']]['badge_color'] ?? 'bg-secondary' ?> d-inline-flex align-items-center gap-1 dev-role-badge shadow-none"
                                                                  id="badge-role-<?= $member['user_id'] ?>-<?= $r['id'] ?>"
                                                                  data-user-id="<?= $member['user_id'] ?>"
                                                                  data-role-id="<?= $r['id'] ?>"
                                                                  title="<?= htmlspecialchars($r['honor_title'] ?? '') ?>">
                                                                <span><i class="fa-solid fa-hammer me-1"></i><?= htmlspecialchars($r['title']) ?></span>
                                                                <?php if ($canManageTeam): ?>
                                                                    <button type="button"
                                                                            class="btn-close btn-close-white ms-1 dev-role-close-btn"
                                                                            style="font-size: 0.55rem; width: 0.7em; height: 0.7em; opacity: 0.85; cursor: pointer;"
                                                                            onclick="confirmRemoveDevRole(<?= $member['user_id'] ?>, '<?= $r['id'] ?>', '<?= htmlspecialchars(addslashes($member['username'])) ?>', '<?= htmlspecialchars(addslashes($r['title'])) ?>')"
                                                                            title="Retirer ce métier">
                                                                    </button>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge <?= $member['forge_level']['badge'] ?>">
                                                        Niv. <?= $member['forge_level']['level'] ?> &bull; <?= htmlspecialchars($member['forge_level']['title']) ?>
                                                    </span>
                                                    <span class="text-muted small font-monospace"><?= number_format($member['forge_level']['xp']) ?> XP</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-list justify-content-end">
                                                    <?php if ($canManageTeam): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-purple d-inline-flex align-items-center gap-1"
                                                                onclick="openAssignRoleModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars(addslashes($member['username'])) ?>')"
                                                                title="Attribuer des métiers à ce membre">
                                                            <i class="fa-solid fa-plus me-1"></i><span class="d-none d-md-inline">Métier</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($canManageSprints): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="openAwardXpModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars(addslashes($member['username'])) ?>')">
                                                            <i class="fa-solid fa-star text-warning me-1"></i>Récompenser XP
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
