<?php
/**
 * Vue Partielle MVC : Vue Générale du Game Studio
 * Fichier : views/gamestudio/general.php
 */

$currentPageSlug = $page ?? 'gamestudio';
?>

<div class="gamestudio-general">
    <!-- En-tête de la Vue Générale -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-indigo d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-sliders text-indigo me-1"></i>Configuration &amp; Synthèse Générale
            </h3>
            <div class="text-secondary small mt-1">
                Aperçu global de l'environnement de jeu, profil serveur, état de la version et équipe de développement.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-purple-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-code-branch me-1"></i>Branche <?= htmlspecialchars($gitInfo['branch'] ?? 'main') ?> (<?= htmlspecialchars($gitInfo['short_sha'] ?? 'HEAD') ?>)
            </span>
        </div>
    </div>

    <!-- ── CARTES KPIS GÉNÉRALES ── -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-indigo shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Version du Jeu</div>
                        <span class="avatar avatar-sm rounded bg-indigo-lt text-indigo fs-3"><i class="fa-solid fa-code"></i></span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-indigo">v<?= htmlspecialchars($gameVersion ?? '1.27.0') ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-server text-muted me-1"></i>PHP <?= htmlspecialchars($phpVersion ?? PHP_VERSION) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-primary shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Comptes Humains</div>
                        <span class="avatar avatar-sm rounded bg-primary-lt text-primary fs-3"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-primary"><?= number_format((int)($totalUsers ?? 0)) ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-globe text-primary me-1"></i><?= number_format((int)($totalPlanets ?? 0)) ?> fiefs colonisés
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-warning shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Clans IA (Bots)</div>
                        <span class="avatar avatar-sm rounded bg-warning-lt text-warning fs-3"><i class="fa-solid fa-robot"></i></span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-warning"><?= number_format((int)($totalBots ?? 0)) ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-bolt text-warning me-1"></i>Simulation périodique
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border-start border-2 border-purple shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-muted small fw-bold text-uppercase">Équipe Dev Team</div>
                        <span class="avatar avatar-sm rounded bg-purple-lt text-purple fs-3"><i class="fa-solid fa-hammer"></i></span>
                    </div>
                    <div class="h1 m-0 font-weight-bold text-purple"><?= count($devMembers ?? []) ?></div>
                    <div class="text-secondary small mt-2 pt-2 border-top">
                        <i class="fa-solid fa-id-badge text-purple me-1"></i>Membres accrédités
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TABLEAU DES ACCRÉDITATIONS STUDIO & DEVS ── -->
    <div class="row row-cards mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-gear text-purple me-1"></i>Accréditations de la Dev Team
                    </h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Membre</th>
                                <th>Rôles &amp; Métiers</th>
                                <th>Niveau Forge</th>
                                <th class="text-end">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($devMembers)): ?>
                                <tr><td colspan="4" class="text-center text-muted p-3">Aucun membre accrédité pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($devMembers as $dm): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($dm['username'] ?? '') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($dm['email'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <?php foreach ($dm['roles'] ?? [] as $role): ?>
                                                <span class="badge bg-purple-lt me-1"><?= htmlspecialchars($role['name'] ?? '') ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-lt fw-bold">Niveau <?= (int)($dm['forge_level']['level'] ?? 1) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success-lt"><i class="fa-solid fa-check me-1"></i>Actif</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-server text-info me-1"></i>Environnement &amp; Serveur
                    </h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Serveur HTTP</span>
                            <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/Nginx') ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Version PHP</span>
                            <strong><?= htmlspecialchars(PHP_VERSION) ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Moteur de Base de Données</span>
                            <strong>MariaDB / MySQL</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Architecture Logique</span>
                            <span class="badge bg-indigo-lt fw-bold">MVC Modulaire (OpenShogun)</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Dossier des Vues Studio</span>
                            <code class="text-indigo">views/gamestudio/</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

