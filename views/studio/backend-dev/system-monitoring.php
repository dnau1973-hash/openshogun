
                    <h3 class="card-title mb-3">Indicateurs de Santé & Opérations Réseau</h3>

                    <div class="row row-cards mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar"><i class="fa-brands fa-php fs-2"></i></span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">Version Moteur PHP</div>
                                            <div class="text-muted small"><?= phpversion() ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-success text-white avatar"><i class="fa-solid fa-users fs-2"></i></span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium"><?= number_format($totalPlayersCount) ?> Joueurs</div>
                                            <div class="text-muted small"><?= number_format($totalPlanetsCount) ?> Fiefs & Provinces</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-warning text-white avatar"><i class="fa-solid fa-hammer fs-2"></i></span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium"><?= number_format($activeQueuesCount) ?> Chantiers Actifs</div>
                                            <div class="text-muted small">File d'attente féodale</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card card-sm border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-danger text-white avatar"><i class="fa-solid fa-brain fs-2"></i></span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">Mémoire Utilisée</div>
                                            <div class="text-muted small"><?= round(memory_get_usage() / 1024 / 1024, 2) ?> Mo (Pic: <?= round(memory_get_peak_usage() / 1024 / 1024, 2) ?> Mo)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Déclenchement de Cron / IA -->
                    <?php if ($canTriggerCron): ?>
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h4 class="card-title mb-1"><i class="fa-solid fa-robot text-indigo me-1"></i>Déclenchement Forcé de la Boucle d'IA &amp; Crons</h4>
                                    <p class="text-muted small mb-0">Exécute immédiatement le cycle autonome de réflexion des bots PNJ, calcul des flottes et mise à jour de la carte.</p>
                                </div>
                                <button type="button" class="btn btn-indigo d-flex align-items-center gap-2" onclick="triggerBotCycle(this)">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i>Forcer le Cycle IA
                                </button>
                            </div>
                            <div id="bot-cycle-log" class="mt-3 font-monospace p-3 bg-dark text-light rounded small d-none" style="max-height: 250px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
