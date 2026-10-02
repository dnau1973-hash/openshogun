
                    <!-- En-tête de section -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h3 class="card-title mb-1 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-bullhorn text-pink me-1"></i>Mailing List &amp; Diffusion Communautaire
                            </h3>
                            <p class="text-muted small mb-0">
                                Gestion des abonnés aux chroniques impériales, composition de missives officielles et campagnes ciblées par faction ou statut.
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="handleExportMailingCsv()">
                                <i class="fa-solid fa-file-csv me-1"></i>Exporter la Liste (CSV)
                            </button>
                            <a href="/?page=newsletter_compose" class="btn btn-teal text-white d-flex align-items-center gap-2">
                                <i class="fa-solid fa-pen-nib me-1"></i>Composer une Missive
                            </a>
                        </div>
                    </div>

                    <!-- Cartes KPI de la Mailing List -->
                    <div class="row row-cards mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Total Inscrits</span>
                                        <span class="badge bg-secondary-lt text-secondary">Base Globale</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-dark d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-users text-primary me-1"></i><span id="kpi-mailing-total"><?= $mailingStats['total_users'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Tous les joueurs humains enregistrés
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Abonnés Newsletter</span>
                                        <span class="badge bg-teal-lt text-teal">Opt-in Actif</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-teal d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-envelope-open text-success me-1"></i><span id="kpi-mailing-optin"><?= $mailingStats['newsletter_subscribers'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Joueurs ayant consenti à la diffusion
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Taux d'Adhésion</span>
                                        <span class="badge bg-cyan-lt text-cyan">Conformité RGPD</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-cyan d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-percent text-info me-1"></i><span id="kpi-mailing-rate"><?= $mailingStats['optin_rate_percent'] ?>%</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Proportion d'abonnés volontaires
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 border shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-bold text-uppercase">Missives Expédiées</span>
                                        <span class="badge bg-purple-lt text-purple">Historique</span>
                                    </div>
                                    <div class="h2 mb-1 font-monospace text-purple d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-paper-plane text-purple me-1"></i><span id="kpi-mailing-campaigns"><?= $mailingStats['total_campaigns'] ?></span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        Campagnes envoyées depuis la fondation
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barre de Recherche et Filtres -->
                    <div class="card border mb-4">
                        <div class="card-body p-3">
                            <form id="mailing-filter-form" onsubmit="event.preventDefault(); loadMailingSubscribers(1);" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input type="text" class="form-control" id="filter-mailing-search" placeholder="Daimyō ou adresse e-mail..." oninput="debounceMailingSearch()">
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-optin" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Diffusion : Tous</option>
                                        <option value="1">Abonnés (Opt-in)</option>
                                        <option value="0">Non abonnés</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-status" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Compte : Tous</option>
                                        <option value="active">Actifs (Vérifiés)</option>
                                        <option value="pending">En attente d'activation</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-faction" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Clan : Tous</option>
                                        <option value="terran">Clan Tokugawa</option>
                                        <option value="vorash">Clan Oda</option>
                                        <option value="aethelis">Clan Takeda</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <select class="form-select" id="filter-mailing-role" onchange="loadMailingSubscribers(1)">
                                        <option value="all">Rôle : Tous</option>
                                        <option value="player">Joueurs simples</option>
                                        <option value="dev_team">Membres Dev Team</option>
                                        <option value="moderator">Modérateurs</option>
                                        <option value="admin">Administrateurs</option>
                                    </select>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetMailingFilters()" title="Réinitialiser les filtres">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau des Abonnés -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Registre des Joueurs &amp; Statut de Diffusion (<span id="mailing-subscribers-count"><?= $initialSubscribers['total'] ?></span>)</h4>
                            <span class="text-muted small" id="mailing-pagination-indicator">Page 1</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover" id="table-mailing-subscribers">
                                <thead>
                                    <tr>
                                        <th>Seigneur Daimyō</th>
                                        <th>E-mail</th>
                                        <th>Clan</th>
                                        <th>Métiers &amp; Profil</th>
                                        <th class="text-center">Abonnement Newsletter</th>
                                        <th>Date d'Inscription</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="mailing-subscribers-tbody">
                                    <?php if (empty($initialSubscribers['subscribers'])): ?>
                                        <tr class="no-subscribers-row">
                                            <td colspan="7" class="text-center text-muted py-4 fst-italic">
                                                Aucun joueur ne correspond aux critères sélectionnés.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($initialSubscribers['subscribers'] as $s): ?>
                                            <tr id="subscriber-row-<?= $s['id'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="avatar avatar-sm bg-blue-lt"><i class="fa-solid fa-user"></i></span>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['username']) ?></div>
                                                            <div class="text-muted small font-monospace">ID #<?= $s['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-dark font-monospace small"><?= htmlspecialchars($s['email']) ?></div>
                                                    <?php if ($s['is_active']): ?>
                                                        <span class="badge bg-success-lt" style="font-size: 0.68rem;"><i class="fa-solid fa-circle-check text-success me-1"></i>Confirmé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-lt" style="font-size: 0.68rem;"><i class="fa-solid fa-hourglass-half text-warning me-1"></i>En attente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $fNames = ['terran' => 'Tokugawa', 'vorash' => 'Oda', 'aethelis' => 'Takeda'];
                                                    $fColors = ['terran' => 'bg-blue-lt text-blue', 'vorash' => 'bg-red-lt text-red', 'aethelis' => 'bg-green-lt text-green'];
                                                    $fName = $fNames[$s['faction']] ?? $s['faction'];
                                                    $fColor = $fColors[$s['faction']] ?? 'bg-secondary-lt text-secondary';
                                                    ?>
                                                    <span class="badge <?= $fColor ?>"><?= htmlspecialchars($fName) ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($s['is_admin']): ?>
                                                            <span class="badge bg-danger text-white"><i class="fa-solid fa-crown me-1"></i>Admin</span>
                                                        <?php endif; ?>
                                                        <?php if ($s['is_moderator']): ?>
                                                            <span class="badge bg-warning text-white"><i class="fa-solid fa-shield-halved me-1"></i>Modo</span>
                                                        <?php endif; ?>
                                                        <?php if (empty($s['dev_roles']) && !$s['is_admin'] && !$s['is_moderator']): ?>
                                                            <span class="badge bg-light text-muted">Joueur</span>
                                                        <?php else: ?>
                                                            <?php foreach ($s['dev_roles'] as $dr): ?>
                                                                <span class="badge bg-purple-lt"><?= $dr['icon'] ?> <?= htmlspecialchars($dr['title']) ?></span>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center" id="subscriber-optin-cell-<?= $s['id'] ?>">
                                                    <?php if ($s['newsletter_optin']): ?>
                                                        <span class="badge bg-success-lt text-success" title="Inscrit volontairement à la newsletter">
                                                            <i class="fa-solid fa-check text-success me-1"></i>Abonné
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-lt text-muted" title="Non inscrit">
                                                            <i class="fa-solid fa-xmark text-secondary me-1"></i>Non abonné
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted font-monospace">
                                                    <?= htmlspecialchars(substr($s['created_at'], 0, 10)) ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick="toggleSubscriberOptin(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['username'])) ?>', <?= $s['newsletter_optin'] ? 'true' : 'false' ?>)"
                                                            title="Basculer le statut d'adhésion">
                                                        <?= $s['newsletter_optin'] ? 'Désinscrire' : 'Abonner' ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between py-2" id="mailing-pagination-box">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mailing-prev" onclick="changeMailingPage(-1)" disabled>
                                &larr; Précédent
                            </button>
                            <span class="small text-muted" id="mailing-page-info">Affichage des 20 premiers</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mailing-next" onclick="changeMailingPage(1)" <?= ($initialSubscribers['total'] <= 20) ? 'disabled' : '' ?>>
                                Suivant &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- Journal des Missives & Campagnes Expédiées -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-scroll text-warning me-1"></i>Dernières Missives Expédiées
                            </h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped" id="table-mailing-campaigns">
                                <thead>
                                    <tr>
                                        <th>Date d'Envoi</th>
                                        <th>Sujet de la Missive</th>
                                        <th>Cible</th>
                                        <th class="text-center">Destinataires</th>
                                        <th>Héraut / Expéditeur</th>
                                        <th>Statut</th>
                                        <th class="w-1 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="mailing-campaigns-tbody">
                                    <?php if (empty($recentCampaigns)): ?>
                                        <tr class="no-campaign-row">
                                            <td colspan="7" class="text-center text-muted py-4 fst-italic">
                                                Aucune missive groupée n'a encore été expédiée. Utilisez le bouton « Composer une Missive » pour lancer votre première campagne.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentCampaigns as $camp): ?>
                                            <tr>
                                                <td class="small font-monospace"><?= htmlspecialchars($camp['sent_at']) ?></td>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($camp['subject']) ?></td>
                                                <td><span class="badge bg-secondary-lt"><?= htmlspecialchars($camp['target_group']) ?></span></td>
                                                <td class="text-center font-monospace fw-bold text-teal"><?= (int)$camp['recipient_count'] ?></td>
                                                <td class="small"><?= htmlspecialchars($camp['sender_name']) ?></td>
                                                <td>
                                                    <?php if ($camp['status'] === 'sent'): ?>
                                                        <span class="badge bg-success-lt text-success"><i class="fa-solid fa-circle-check me-1"></i>Expédiée</span>
                                                    <?php elseif ($camp['status'] === 'draft'): ?>
                                                        <span class="badge bg-warning-lt text-warning"><i class="fa-solid fa-pen-ruler me-1"></i>Brouillon</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-lt text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Échec</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/?page=newsletter_compose&id=<?= (int)$camp['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ouvrir dans l'atelier de rédaction">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i>Éditer
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
