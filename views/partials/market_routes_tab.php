<?php
/**
 * Vue Partielle : Routes Commerciales Impériales (Sceau Impérial)
 */
?>
<div class="tab-pane fade" id="tab-market-routes" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="m-0 fw-bold text-dark"><i class="fa-solid fa-route text-primary me-1"></i>Routes Commerciales liées à ce Marché</h4>
            <div class="text-muted small">Convois de ravitaillement programmés au départ ou à destination de ce fief.</div>
        </div>
        <?php if (!empty($isSealActive)): ?>
            <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-cyan text-white fw-bold btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Gérer les Routes dans la Cour du Shōgun &rarr;
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($isSealActive)): ?>
        <div class="alert alert-warning p-3 border-warning">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-1"><i class="fa-solid fa-crown text-warning"></i></span>
                <div>
                    <h4 class="m-0 fw-bold text-dark">Privilège du Sceau Impérial Requis</h4>
                    <div class="text-secondary small mt-1">
                        L'automatisation des routes commerciales permet de programmer des convois logistiques récurrents toutes les 1h, 2h, 4h, 8h, etc. sans aucune action manuelle requise.
                    </div>
                    <a href="/?page=privilege" class="btn btn-warning text-dark fw-bold btn-sm mt-2">
                        <i class="fa-solid fa-crown me-1"></i>Décréter le Sceau Impérial &rarr;
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (empty($marketRoutes)): ?>
        <div class="text-center py-4 text-muted bg-light rounded border">
            <div class="fs-1 mb-1"><i class="fa-solid fa-route text-primary"></i></div>
            <div class="fw-bold text-dark">Aucune route commerciale active sur ce fief</div>
            <div class="small mb-3">Définissez des livraisons récurrentes pour alimenter automatiquement vos provinces.</div>
            <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-outline-cyan btn-sm fw-bold">
                <i class="fa-solid fa-plus me-1"></i>Établir une Route Commerciale
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Sens &amp; Fiefs</th>
                        <th>Cargaison par Passage</th>
                        <th>Fréquence</th>
                        <th>Prochain Envoi</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($marketRoutes as $tr): ?>
                    <?php $isDepart = ((int)$tr['source_planet_id'] === (int)$planet['id']); ?>
                    <tr>
                        <td>
                            <span class="badge <?= $isDepart ? 'bg-primary-lt' : 'bg-success-lt' ?> me-1">
                                <?= $isDepart ? 'Départ <i class="fa-solid fa-arrow-up-right-from-square text-info"></i>' : 'Arrivée <i class="fa-solid fa-arrow-down-left-and-up-right-to-center text-success"></i>' ?>
                            </span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($tr['source_name'] ?? 'Fief Origine') ?></span>
                            <i class="fa-solid fa-arrow-right text-muted mx-1"></i>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($tr['target_name'] ?? 'Fief Cible') ?></span>
                        </td>
                        <td>
                            <span class="font-monospace small">
                                <?php if ($tr['wood'] > 0): ?><i class="fa-solid fa-tree text-success me-1"></i><?= number_format($tr['wood']) ?> <?php endif; ?>
                                <?php if ($tr['stone'] > 0): ?><i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($tr['stone']) ?> <?php endif; ?>
                                <?php if ($tr['rice'] > 0): ?><i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($tr['rice']) ?> <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-cyan-lt">Toutes les <?= $tr['interval_hours'] ?>h</span>
                        </td>
                        <td>
                            <span class="font-monospace small fw-bold" data-countdown="<?= strtotime($tr['next_run_at']) ?>">
                                <?= htmlspecialchars($tr['next_run_at']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $tr['is_active'] ? 'bg-success-lt' : 'bg-secondary-lt' ?>">
                                <?= $tr['is_active'] ? 'Active' : 'En pause' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="/?page=privilege#sectionTradeRoutes" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-gear me-1"></i>Gérer &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

