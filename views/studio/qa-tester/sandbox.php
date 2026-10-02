                    
                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm">
                        <div class="fs-1 text-primary"><i class="fa-solid fa-flask"></i></div>
                        <div>
                            <h4 class="alert-title mb-1">Console Sandbox & Débogage QA</h4>
                            <div class="small">
                                Ces commandes directes sont destinées aux tests de non-régression, vérifications d'équilibrage et tests de résistance.<br>
                                <em>Chaque action exécutée ici est enregistrée dans le journal de forge et octroie de l'XP de contribution.</em>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards">
                        <!-- Action 1 : Injection de Ressources -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="fs-2 text-warning"><i class="fa-solid fa-wheat-awn"></i></span>
                                        <div>
                                            <h3 class="card-title mb-0">Approvisionnement Rapide (QA Test)</h3>
                                            <span class="text-muted small">Injection directe sur votre fief actuel</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        Crédite immédiatement <strong>100 000 Métal</strong>, <strong>100 000 Cristal</strong> et <strong>100 000 Deutérium</strong> pour tester les chantiers, recherches et levées d'armées sans attendre la récolte passive.
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" onclick="triggerSandboxAction('give_resources', this)">
                                            <i class="fa-solid fa-bolt text-warning me-1"></i>Injecter +100 000 Ressources
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action 2 : Achèvement Rapide des Bâtiments -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="fs-2 text-danger"><i class="fa-solid fa-chess-rook"></i></span>
                                        <div>
                                            <h3 class="card-title mb-0">Achèvement Instantané des Chantiers</h3>
                                            <span class="text-muted small">Passage forcé à zéro du temps d'attente</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        Met instantanément à jour le timer de tous vos bâtiments actuellement en construction ou montée de niveau sur votre fief pour valider immédiatement le rendu UI et les nouveaux bonus.
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-warning w-100 d-flex align-items-center justify-content-center gap-2 text-dark" onclick="triggerSandboxAction('instant_finish_constructions', this)">
                                            <i class="fa-solid fa-forward-fast me-1"></i>Achever Immédiatement les Chantiers
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
