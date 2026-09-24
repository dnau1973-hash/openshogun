<?php
/**
 * Page Dédiée : Privilèges du Shōgun & Sceau Impérial (Sengoku Plus)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user) {
    header('Location: /');
    exit;
}

$sealEngine = new ImperialSealEngine();
$sealStatus = $sealEngine->getSealStatus((int)$user['id']);
$isSealActive = $sealStatus['active'];
$goldCoins = (int)$sealStatus['gold'];

$planetEngine = new PlanetEngine();
$myVillages = $planetEngine->getUserPlanets((int)$user['id']);
$tradeRoutes = $sealEngine->getTradeRoutes((int)$user['id']);
?>

<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="text-muted small">Cour Suprême du Shogunat &bull; Intendance Impériale</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span>👑</span> Les Privilèges du Shōgun (Sceau Impérial)
                <?php if ($isSealActive): ?>
                    <span class="badge bg-warning text-dark fs-5 shadow-sm">👑 Sceau Actif</span>
                <?php else: ?>
                    <span class="badge bg-secondary-lt fs-5">Régime Ordinaire</span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark text-warning p-2 fs-5 border border-warning shadow-sm">
                    🪙 <?= number_format($goldCoins) ?> Koban
                </span>
                <a href="?page=empire" class="btn btn-outline-warning">
                    👑 Grand Tableau de Bord
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ================= BANNIÈRE STATUT & TRÉSOR DU DAIMYŌ ================= -->
<div class="row row-cards mb-4">
    <!-- Colonne 1 : Statut du Sceau -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0" 
             style="background: <?= $isSealActive ? 'linear-gradient(135deg, #1c1917 0%, #292524 60%, #451a03 100%)' : 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)' ?>; color:#ffffff; border-radius:12px; overflow:hidden;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="letter-spacing:1px;">
                            <?= $isSealActive ? 'Investiture Active' : 'Sceau Inactif' ?>
                        </span>
                        <span class="fs-1">👑</span>
                    </div>
                    <h3 class="card-title text-white fs-2 mb-2">
                        <?= $isSealActive ? 'Sceau Impérial en Vigueur' : 'Aucun Sceau Proclamé' ?>
                    </h3>
                    <p class="text-secondary small mb-3" style="color: #cbd5e1 !important; line-height:1.6;">
                        <?= $isSealActive 
                            ? 'Vos maîtres d\'œuvre, intendants et généraux appliquent sans réserve les décrets impériaux sur l\'ensemble de vos domaines.' 
                            : 'Activez le Sceau Impérial pour débloquer l\'Architecte de Cour (file jusqu\'à 4 chantiers), le Carnet de Raids en 1 clic et l\'Évasion de Garnison.' ?>
                    </p>
                </div>
                <div class="p-3 rounded" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="small text-uppercase text-warning fw-bold">Durée restante</div>
                            <div class="fs-3 fw-bold font-monospace mt-1">
                                <?= htmlspecialchars($sealStatus['remaining_formatted']) ?>
                            </div>
                        </div>
                        <?php if ($isSealActive): ?>
                            <div class="text-end text-muted small">
                                Échéance : <strong><?= htmlspecialchars($sealStatus['until']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne 2 : Trésor en Koban & Tributs -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border" style="border-radius:12px;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning-lt text-warning fw-bold text-uppercase">Monnaie du Shogunat</span>
                        <span class="fs-1">🪙</span>
                    </div>
                    <h3 class="card-title text-dark fs-2 mb-1">
                        <?= number_format($goldCoins) ?> Koban
                    </h3>
                    <div class="text-secondary small mb-3" style="line-height:1.6;">
                        Le Koban est la monnaie officielle de la Cour Impériale. Elle ne dépend d'aucun paiement obligatoire : chaque daimyō gagne des Koban via les quêtes, les médailles et le tribut quotidien.
                    </div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <div class="p-3 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong class="d-block text-dark">🎁 Tribut Quotidien de Fidélité</strong>
                            <span class="small text-muted">+5 Koban offerts chaque jour de connexion</span>
                        </div>
                        <?php if (!empty($sealStatus['can_claim_daily'])): ?>
                            <button type="button" class="btn btn-warning fw-bold shadow-sm" id="btnClaimDailyGold" onclick="claimDailyGold()">
                                🪙 Réclamer mon Tribut (+5 Koban)
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success-lt p-2">✓ Tribut déjà perçu aujourd'hui</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted italic text-center">
                        🎖️ <strong>Exploits de Guerre :</strong> Chaque médaille d'honneur remportée vous rapporte <strong>+100 Koban</strong> !
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= 3 FORMULES D'INVESTITURE IMPÉRIALE ================= -->
<div class="card mb-4 shadow-sm border" style="overflow: visible;">
    <div class="card-header bg-warning-subtle py-3">
        <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
            <span>📜</span> Décréter ou Prolonger le Sceau Impérial
        </h3>
        <div class="card-options text-muted small">Aucun paiement en argent réel requis</div>
    </div>
    <div class="card-body p-4" style="overflow: visible;">
        <div class="row g-4">
            <!-- Offre 1 : 7 Jours -->
            <div class="col-md-4">
                <div class="card h-100 border text-center hover-shadow d-flex flex-column justify-content-between" 
                     style="border-radius:12px; min-height:490px; padding: 1.8rem 1.5rem; transition: transform 0.15s ease; background:#ffffff;">
                    <div>
                        <div class="mb-3" style="min-height: 26px;">
                            <span class="badge bg-secondary-lt text-secondary fw-bold px-3 py-1" style="letter-spacing:0.5px; font-size:0.75rem;">
                                ⛩️ DÉCOUVERTE &bull; SPRINT
                            </span>
                        </div>
                        <div class="fs-2 mb-1">⛩️</div>
                        <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing:0.5px;">Investiture Découverte</div>
                        <div class="fs-1 fw-bold text-dark my-2" style="font-size:2.2rem !important;">7 Jours</div>
                        <div class="fs-2 fw-bold text-warning mb-1">200 Koban</div>
                        <div class="text-muted small mb-3">soit ~28,6 Koban / jour</div>

                        <p class="text-secondary small mb-3" style="line-height:1.6;">
                            Parfait pour un démarrage rapide, accélérer l'annexion d'un second fief ou soutenir une guerre de frontière.
                        </p>

                        <div class="p-3 rounded text-start small mb-3" style="background: rgba(0,0,0,0.02); border: 1px dashed rgba(0,0,0,0.1);">
                            <div class="text-dark fw-bold mb-2 small text-uppercase" style="font-size:0.7rem; color:#64748b;">Avantages garantis :</div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span>File de construction <strong>4 chantiers</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Carnet de Raids</strong> illimité en 1 clic</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Troc de Marché 1:1:1</strong> débloqué</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-success fw-bold">✓</span>
                                <span>Ordre de <strong>Repli Samouraï</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" class="btn btn-outline-warning w-100 fw-bold py-2 shadow-sm" onclick="activateSeal(7)">
                            👑 Proclamer pour 7 Jours
                        </button>
                    </div>
                </div>
            </div>

            <!-- Offre 2 : 14 Jours (Populaire) -->
            <div class="col-md-4">
                <div class="card h-100 border border-warning text-center shadow-sm d-flex flex-column justify-content-between" 
                     style="background: #fffdfa; border-radius:12px; border-width:2.5px !important; min-height:490px; padding: 1.8rem 1.5rem; transition: transform 0.15s ease;">
                    <div>
                        <div class="mb-3" style="min-height: 26px;">
                            <span class="badge bg-warning text-dark fw-bold px-3 py-1 shadow-sm" style="letter-spacing:0.5px; font-size:0.75rem;">
                                ⭐ ÉCONOMIE -10% &bull; RECOMMANDÉ
                            </span>
                        </div>
                        <div class="fs-2 mb-1">🏯</div>
                        <div class="text-warning small fw-bold text-uppercase" style="letter-spacing:0.5px;">Investiture Royale</div>
                        <div class="fs-1 fw-bold text-dark my-2" style="font-size:2.2rem !important;">14 Jours</div>
                        <div class="fs-2 fw-bold text-warning mb-1">360 Koban</div>
                        <div class="text-muted small mb-3">soit ~25,7 Koban / jour</div>

                        <p class="text-secondary small mb-3" style="line-height:1.6;">
                            La formule recommandée par les conseillers du Shōgun pour fortifier vos cités et planifier vos chantiers sur deux semaines.
                        </p>

                        <div class="p-3 rounded text-start small mb-3" style="background: rgba(245,158,11,0.06); border: 1px dashed rgba(245,158,11,0.3);">
                            <div class="text-dark fw-bold mb-2 small text-uppercase" style="font-size:0.7rem; color:#b45309;">Avantages garantis :</div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span>File de construction <strong>4 chantiers</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Carnet de Raids</strong> illimité en 1 clic</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Troc de Marché 1:1:1</strong> débloqué</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-success fw-bold">✓</span>
                                <span>Ordre de <strong>Repli Samouraï</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" class="btn btn-warning w-100 fw-bold py-2 shadow" onclick="activateSeal(14)">
                            👑 Proclamer pour 14 Jours
                        </button>
                    </div>
                </div>
            </div>

            <!-- Offre 3 : 30 Jours -->
            <div class="col-md-4">
                <div class="card h-100 border text-center hover-shadow d-flex flex-column justify-content-between" 
                     style="border-radius:12px; min-height:490px; padding: 1.8rem 1.5rem; transition: transform 0.15s ease; background:#ffffff;">
                    <div>
                        <div class="mb-3" style="min-height: 26px;">
                            <span class="badge bg-purple text-white fw-bold px-3 py-1 shadow-sm" style="letter-spacing:0.5px; font-size:0.75rem;">
                                👑 PRESTIGE &bull; -25% D'OR
                            </span>
                        </div>
                        <div class="fs-2 mb-1">👑</div>
                        <div class="text-purple small fw-bold text-uppercase" style="letter-spacing:0.5px;">Investiture Mensuelle</div>
                        <div class="fs-1 fw-bold text-dark my-2" style="font-size:2.2rem !important;">30 Jours</div>
                        <div class="fs-2 fw-bold text-warning mb-1">600 Koban</div>
                        <div class="text-muted small mb-3">soit 20,0 Koban / jour seulement</div>

                        <p class="text-secondary small mb-3" style="line-height:1.6;">
                            La tranquillité absolue pour régner un mois complet avec la plénitude de tous les privilèges et le carnet de raids illimité.
                        </p>

                        <div class="p-3 rounded text-start small mb-3" style="background: rgba(107,33,168,0.04); border: 1px dashed rgba(107,33,168,0.25);">
                            <div class="text-dark fw-bold mb-2 small text-uppercase" style="font-size:0.7rem; color:#6b21a8;">Avantages garantis :</div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span>File de construction <strong>4 chantiers</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Carnet de Raids</strong> illimité en 1 clic</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-success fw-bold">✓</span>
                                <span><strong>Troc de Marché 1:1:1</strong> débloqué</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-success fw-bold">✓</span>
                                <span>Ordre de <strong>Repli Samouraï</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" class="btn btn-outline-warning w-100 fw-bold py-2 shadow-sm" onclick="activateSeal(30)">
                            👑 Proclamer pour 30 Jours
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= PRÉSENTATION DES 8 GRANDS PRIVILÈGES IMPÉRIAUX ================= -->
<div class="mb-4">
    <h3 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
        <span>⭐</span> Guide des 8 Grands Privilèges du Shōgun
    </h3>

    <div class="row g-3">
        <!-- 1. Architecte de Cour -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #3b82f6 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🔨</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">1. Architecte de Cour</h4>
                        <span class="badge bg-blue-lt">File Étendue (x4)</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Enchaînez jusqu'à <strong>4 constructions</strong> (jusqu'à 2 parcelles agricoles et 2 édifices urbains pour Oda, et 3 chantiers consécutifs pour les autres clans).
                </p>
                <div class="mt-auto">
                    <a href="?page=city" class="btn btn-sm btn-outline-primary w-100">
                        🏯 Gérer les Bâtisseurs &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. File Meunerie & Saké (NOUVEAU) -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #166534 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🍶</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">2. Raffinage Sakagura</h4>
                        <span class="badge bg-success-lt">File x4 Commandes</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Programmez jusqu'à <strong>4 commandes en chaîne</strong> dans votre Meunerie (Farine de Riz 🍚 et Saké 🍶). Les cuves et meules s'enchaînent jour et nuit sans interruption !
                </p>
                <div class="mt-auto">
                    <a href="?page=building&slot=31" class="btn btn-sm btn-outline-success w-100">
                        🍶 Ouvrir la Meunerie &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Routes Commerciales Automatisées (NOUVEAU) -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #0891b2 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🛣️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">3. Routes Commerciales</h4>
                        <span class="badge bg-cyan-lt">Convois Autonomes</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Automatisez les livraisons régulières de Bois 🪵, Pierre 🪨 et Riz 🌾 entre vos fiefs. Vos convois partent à heure fixe sans action manuelle requise !
                </p>
                <div class="mt-auto">
                    <a href="#sectionTradeRoutes" class="btn btn-sm btn-outline-cyan w-100">
                        🛣️ Gérer les Routes &darr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Grand Tableau de Bord -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #f59e0b !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">👑</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">4. Tableau Impérial</h4>
                        <span class="badge bg-warning-lt">Multi-Fiefs</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Panorama consolidé de tout votre Empire : stocks globaux, cadences horaires, chantiers actifs et prévention active du risque de famine féodale.
                </p>
                <div class="mt-auto">
                    <a href="?page=empire" class="btn btn-sm btn-outline-warning w-100">
                        👑 Consulter l'Empire &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 5. Carnet de Raids -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #10b981 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">📜</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">5. Carnet de Raids</h4>
                        <span class="badge bg-success-lt">Attaque en 1 Clic</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Mémorisez vos cibles régulières (oasis sauvages, provinces inactives) et lancez l'ensemble de votre tournée de pillage en un seul clic !
                </p>
                <div class="mt-auto">
                    <a href="?page=fleet#tab-farm-lists" class="btn btn-sm btn-outline-success w-100">
                        ⚔️ Ouvrir le Carnet &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 6. Intendant du Marché -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">⚖️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">6. Intendant du Marché</h4>
                        <span class="badge bg-purple-lt">Troc 1:1:1 Immédiat</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Rééquilibrez immédiatement vos réserves excédentaires de Bois, Pierre et Riz sans perte au ratio 1:1:1 pour 3 Koban, et lancez vos chantiers cruciaux.
                </p>
                <div class="mt-auto">
                    <button type="button" class="btn btn-sm btn-outline-purple w-100" 
                            onclick="openNpcExchangeModal(<?= $planet['id'] ?>, '<?= htmlspecialchars(addslashes($planet['name'])) ?>', <?= (int)$planet['metal'] ?>, <?= (int)$planet['crystal'] ?>, <?= (int)$planet['deuterium'] ?>, <?= (int)$planet['metal_max'] ?>, <?= (int)$planet['crystal_max'] ?>, <?= (int)$planet['deuterium_max'] ?>)">
                        ⚖️ Troc Rapide &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- 7. Ordre de Repli Tactique -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #ef4444 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">⛩️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">7. Repli Tactique</h4>
                        <span class="badge bg-danger-lt">Évasion Nocturne</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Vos garnisons et votre Héros se replient dans les collines et forêts lors des assauts pour éviter l'anéantissement face aux armées écrasantes !
                </p>
                <div class="mt-auto">
                    <a href="?page=empire#tab-evasion" class="btn btn-sm btn-outline-danger w-100">
                        🛡️ Configurer le Repli &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- 8. Récompenses d'Honneur -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border p-3" style="border-left: 4px solid #d97706 !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fs-1">🎖️</span>
                    <div>
                        <h4 class="m-0 text-dark fw-bold">8. Médailles d'Honneur</h4>
                        <span class="badge bg-warning-lt">+100 Koban / Médaille</span>
                    </div>
                </div>
                <p class="text-secondary small mb-3" style="line-height:1.55;">
                    Chaque exploit récompensé par la Cour Impériale (Top Assaut, Défense, Raid ou Progression) vous octroie automatiquement <strong>+100 Koban</strong>.
                </p>
                <div class="mt-auto">
                    <a href="?page=ranking" class="btn btn-sm btn-outline-warning w-100">
                        🏆 Voir le Classement &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= SECTION : ROUTES COMMERCIALES FÉODALES AUTOMATISÉES ================= -->
<div class="card mb-4 shadow-sm border" id="sectionTradeRoutes" style="border-top: 3px solid #0891b2 !important;">
    <div class="card-header bg-cyan-subtle py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                <span>🛣️</span> Routes Commerciales &amp; Convois Féodaux Automatisés
            </h3>
            <div class="text-secondary small mt-1">
                Programmez des rotations logistiques régulières entre vos fiefs sans aucune intervention manuelle.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($isSealActive): ?>
                <button type="button" class="btn btn-cyan text-white fw-bold shadow-sm" onclick="openCreateTradeRouteModal()">
                    ➕ Établir une Route Commerciale
                </button>
            <?php else: ?>
                <a href="#decretSection" class="btn btn-outline-secondary fw-bold" onclick="window.scrollTo({top:0, behavior:'smooth'})">
                    👑 Sceau Impérial Requis
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-3">
        <?php if (!$isSealActive): ?>
            <div class="alert alert-warning mb-0 p-3 border-warning">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-1">👑</span>
                    <div>
                        <h4 class="m-0 fw-bold text-dark">Privilège Exclusif du Sceau Impérial</h4>
                        <div class="text-secondary small mt-1">
                            L'automatisation des routes commerciales permet à vos intendants d'expédier régulièrement des chariots de ravitaillement (Bois, Pierre, Riz) entre vos provinces pour nourrir vos chantiers castraux et approvisionner vos troupes. Décrétez le Sceau Impérial pour activer ce service !
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (empty($tradeRoutes)): ?>
            <div class="text-center py-5 text-muted">
                <div class="fs-1 mb-2">🛣️</div>
                <h4 class="text-dark fw-bold mb-1">Aucune Route Commerciale Active</h4>
                <p class="small text-secondary mb-3" style="max-width: 500px; margin: 0 auto;">
                    Vous n'avez pas encore défini de route de ravitaillement automatique entre vos fiefs. Cliquez sur le bouton ci-dessous pour planifier votre premier convoi récurrent.
                </p>
                <button type="button" class="btn btn-outline-cyan fw-bold" onclick="openCreateTradeRouteModal()">
                    ➕ Définir ma première route commerciale
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th>Départ &rarr; Destination</th>
                            <th>Cargaison par Convoi</th>
                            <th>Fréquence</th>
                            <th>Prochain Passage</th>
                            <th>Livraisons</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tradeRoutes as $tr): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <a href="?page=overview&planet=<?= $tr['source_planet_id'] ?>" class="fw-bold text-dark">
                                            🏯 <?= htmlspecialchars($tr['source_name']) ?>
                                        </a>
                                        <div class="text-muted" style="font-size:0.75rem;">(<?= $tr['source_x'] ?>|<?= $tr['source_y'] ?>)</div>
                                    </div>
                                    <span class="text-muted fs-4">&rarr;</span>
                                    <div>
                                        <a href="?page=overview&planet=<?= $tr['target_planet_id'] ?>" class="fw-bold text-primary">
                                            🏯 <?= htmlspecialchars($tr['target_name']) ?>
                                        </a>
                                        <div class="text-muted" style="font-size:0.75rem;">(<?= $tr['target_x'] ?>|<?= $tr['target_y'] ?>)</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:0.85rem;">
                                    <?php if ($tr['wood'] > 0): ?>
                                        <span class="badge bg-light text-dark border">🪵 <?= number_format($tr['wood']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($tr['stone'] > 0): ?>
                                        <span class="badge bg-light text-dark border">🪨 <?= number_format($tr['stone']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($tr['rice'] > 0): ?>
                                        <span class="badge bg-light text-dark border">🌾 <?= number_format($tr['rice']) ?></span>
                                    <?php endif; ?>
                                    <span class="text-muted" style="font-size:0.75rem;">(Total : <?= number_format($tr['total_cargo']) ?>)</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-cyan-lt fw-bold">
                                    ⏱️ Toutes les <?= $tr['interval_hours'] ?>h
                                </span>
                            </td>
                            <td>
                                <?php if (!$tr['is_active']): ?>
                                    <span class="badge bg-secondary-lt">En pause</span>
                                <?php elseif ($tr['is_due']): ?>
                                    <span class="badge bg-warning text-dark font-monospace animate-pulse">
                                        ⚡ Échu (convoi imminent)
                                    </span>
                                <?php else: ?>
                                    <span class="font-monospace text-dark fw-bold small" data-countdown="<?= strtotime($tr['next_run_at']) ?>">
                                        <?= htmlspecialchars($tr['next_run_at']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    📦 <?= (int)$tr['deliveries_count'] ?> convois
                                </span>
                            </td>
                            <td>
                                <?php if (!$tr['is_active']): ?>
                                    <span class="badge bg-secondary text-white">Mise en pause</span>
                                <?php else: ?>
                                    <span class="badge bg-success-lt fw-bold">Active</span>
                                <?php endif; ?>
                                <div class="text-muted" style="font-size:0.7rem; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($tr['last_status'] ?? '') ?>">
                                    <?= htmlspecialchars($tr['last_status'] ?? 'En attente') ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="executeTradeRouteNow(<?= $tr['id'] ?>)" title="Expédier immédiatement un convoi">
                                        🚀 Expédier
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleTradeRoute(<?= $tr['id'] ?>)" title="<?= $tr['is_active'] ? 'Mettre en pause' : 'Réactiver' ?>">
                                        <?= $tr['is_active'] ? '⏸️' : '▶️' ?>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteTradeRoute(<?= $tr['id'] ?>)" title="Supprimer la route">
                                        🗑️
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

<!-- ================= MODALE DE CRÉATION DE ROUTE COMMERCIALE ================= -->
<div class="modal modal-blur fade" id="modalCreateTradeRoute" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-cyan-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>🛣️</span> Établir une Route Commerciale Féodale
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Vos intendants mobiliseront automatiquement des chariots de transport pour acheminer les denrées choisies selon la fréquence définie.
                </p>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold text-dark small">Fief d'Expédition (Départ) :</label>
                        <select id="tr_source_planet" class="form-select">
                            <?php foreach ($myVillages as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= ((int)$v['id'] === (int)$planet['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['name']) ?> (<?= $v['coord_x'] ?>|<?= $v['coord_y'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold text-dark small">Fief de Destination :</label>
                        <select id="tr_target_planet" class="form-select">
                            <?php foreach ($myVillages as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= ((int)$v['id'] !== (int)$planet['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['name']) ?> (<?= $v['coord_x'] ?>|<?= $v['coord_y'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small mb-2">Chargement par expédition :</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small text-muted mb-1">🪵 Bois</label>
                            <input type="number" id="tr_wood" class="form-control text-center fw-bold" value="1000" min="0" step="500">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted mb-1">🪨 Pierre</label>
                            <input type="number" id="tr_stone" class="form-control text-center fw-bold" value="1000" min="0" step="500">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted mb-1">🌾 Riz</label>
                            <input type="number" id="tr_rice" class="form-control text-center fw-bold" value="1000" min="0" step="500">
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold text-dark small">Périodicité d'envoi :</label>
                        <select id="tr_interval_hours" class="form-select">
                            <option value="1">Toutes les 1 heure</option>
                            <option value="2">Toutes les 2 heures</option>
                            <option value="4" selected>Toutes les 4 heures (Idéal)</option>
                            <option value="8">Toutes les 8 heures</option>
                            <option value="12">Toutes les 12 heures</option>
                            <option value="24">Toutes les 24 heures (1x / jour)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold text-dark small">Choix des Transporteurs :</label>
                        <select id="tr_transporter_pref" class="form-select">
                            <option value="auto" selected>Automatique (Optimal)</option>
                            <option value="transporter_light">Chariots Légers (5k)</option>
                            <option value="transporter_heavy">Grands Convois (25k)</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0">
                    ℹ️ <strong>Règle Féodale :</strong> Le fief d'expédition doit posséder un <strong>Marché Castral</strong> (Niveau 1+) et les transporteurs requis en garnison lors du passage horaire.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-cyan text-white fw-bold" id="btnSubmitCreateTradeRoute" onclick="submitCreateTradeRoute()">
                    🛣️ Déployer la Route Commerciale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALE INTERACTIVE DU MARCHAND NPC ================= -->
<div class="modal modal-blur fade" id="modalNpcExchange" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>⚖️</span> Intendant du Marché Castral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-3">
                    L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au taux parfait de <strong>1:1:1</strong> pour un tribut de <strong>3 Koban 🪙</strong>.
                </div>

                <div class="p-2 bg-light border rounded mb-3 text-center">
                    <span class="text-muted small">Fief sélectionné : </span>
                    <strong id="npc_planet_name" class="text-dark">Fief</strong>
                    <div class="fs-4 fw-bold text-primary mt-1">
                        Total à répartir : <span id="npc_total_amount">0</span>
                    </div>
                    <div class="small text-muted">La somme totale doit être conservée au grain près.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪵 Bois de Cèdre :</span>
                        <span id="npc_wood_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_wood" oninput="onNpcRangeChange('wood')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_wood" oninput="onNpcInputChange('wood')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪨 Pierre de Taille :</span>
                        <span id="npc_stone_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_stone" oninput="onNpcRangeChange('stone')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_stone" oninput="onNpcInputChange('stone')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🌾 Riz Impérial :</span>
                        <span id="npc_rice_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_rice" oninput="onNpcRangeChange('rice')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_rice" oninput="onNpcInputChange('rice')">
                </div>

                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="distributeEvenly()">
                        ⚖️ Répartir Équitablement (1/3 chacun)
                    </button>
                </div>

                <div id="npc_diff_alert" class="alert alert-danger p-2 small d-none">
                    La somme répartie ne correspond pas au total disponible.
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <span class="small text-muted">Coût : <strong class="text-warning">3 Koban</strong> (Solde : <?= $goldCoins ?>)</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        🪙 Sceller le Troc (3 Koban)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function activateSeal(days) {
    const confirmed = await showModalConfirm(`Voulez-vous proclamer le Sceau Impérial du Shōgun pour une durée de ${days} jours ?`, 'Décret Impérial');
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'activate');
    fd.append('days', days);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Sceau Impérial Proclamé');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'activer le Sceau Impérial.', 'warning');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec le Shogunat.', 'error');
    }
}

async function claimDailyGold() {
    const btn = document.getElementById('btnClaimDailyGold');
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'claim_daily');

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Tribut d\'Or Perçu');
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Tribut indisponible pour l\'instant.', 'warning');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur réseau.', 'error');
        if (btn) btn.disabled = false;
    }
}

// Logique Intendant NPC
let currentNpcPlanetId = 0;
let currentNpcTotal = 0;
let currentNpcMax = { wood: 0, stone: 0, rice: 0 };

function openNpcExchangeModal(planetId, planetName, wood, stone, rice, maxW, maxS, maxR) {
    currentNpcPlanetId = planetId;
    currentNpcTotal = Math.floor(wood + stone + rice);
    currentNpcMax = { wood: maxW, stone: maxS, rice: maxR };

    document.getElementById('npc_planet_name').innerText = planetName;
    document.getElementById('npc_total_amount').innerText = currentNpcTotal.toLocaleString('fr-FR');

    ['wood', 'stone', 'rice'].forEach(res => {
        const r = document.getElementById(`npc_range_${res}`);
        const inp = document.getElementById(`npc_input_${res}`);
        const val = (res === 'wood') ? wood : ((res === 'stone') ? stone : rice);

        r.max = Math.min(currentNpcTotal, currentNpcMax[res]);
        r.value = val;
        inp.max = currentNpcMax[res];
        inp.value = val;
        document.getElementById(`npc_val_${res}`) && (document.getElementById(`npc_val_${res}`).innerText = val);
    });

    updateNpcDisplay();
    const modal = new bootstrap.Modal(document.getElementById('modalNpcExchange'));
    modal.show();
}

function distributeEvenly() {
    const part = Math.floor(currentNpcTotal / 3);
    const rest = currentNpcTotal - (part * 2);

    document.getElementById('npc_input_wood').value = part;
    document.getElementById('npc_input_stone').value = part;
    document.getElementById('npc_input_rice').value = rest;

    document.getElementById('npc_range_wood').value = part;
    document.getElementById('npc_range_stone').value = part;
    document.getElementById('npc_range_rice').value = rest;

    updateNpcDisplay();
}

function onNpcRangeChange(type) {
    const val = document.getElementById(`npc_range_${type}`).value;
    document.getElementById(`npc_input_${type}`).value = val;
    updateNpcDisplay();
}

function onNpcInputChange(type) {
    const val = document.getElementById(`npc_input_${type}`).value;
    document.getElementById(`npc_range_${type}`).value = val;
    updateNpcDisplay();
}

function updateNpcDisplay() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    document.getElementById('npc_wood_val').innerText = w.toLocaleString('fr-FR');
    document.getElementById('npc_stone_val').innerText = s.toLocaleString('fr-FR');
    document.getElementById('npc_rice_val').innerText = r.toLocaleString('fr-FR');

    const sum = w + s + r;
    const diff = currentNpcTotal - sum;
    const alertEl = document.getElementById('npc_diff_alert');
    const submitBtn = document.getElementById('btnSubmitNpcExchange');

    if (diff !== 0) {
        alertEl.classList.remove('d-none');
        alertEl.innerText = diff > 0 ? `Il reste ${diff.toLocaleString('fr-FR')} ressources à assigner.` : `Excédent de ${Math.abs(diff).toLocaleString('fr-FR')} ressources réparties en trop.`;
        submitBtn.disabled = true;
    } else {
        alertEl.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

async function submitNpcExchange() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    const fd = new FormData();
    fd.append('action', 'npc_exchange');
    fd.append('planet_id', currentNpcPlanetId);
    fd.append('wood', w);
    fd.append('stone', s);
    fd.append('rice', r);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du troc.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

// ==========================================
// GESTION DES ROUTES COMMERCIALES FÉODALES
// ==========================================
function openCreateTradeRouteModal() {
    const modalEl = document.getElementById('modalCreateTradeRoute');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

async function submitCreateTradeRoute() {
    const src = document.getElementById('tr_source_planet').value;
    const tgt = document.getElementById('tr_target_planet').value;
    const wood = parseInt(document.getElementById('tr_wood').value || 0, 10);
    const stone = parseInt(document.getElementById('tr_stone').value || 0, 10);
    const rice = parseInt(document.getElementById('tr_rice').value || 0, 10);
    const interval = parseInt(document.getElementById('tr_interval_hours').value || 4, 10);
    const pref = document.getElementById('tr_transporter_pref').value;

    if (src === tgt) {
        showModalAlert("Le fief de départ et le fief d'arrivée doivent être distincts.", "warning");
        return;
    }

    if ((wood + stone + rice) <= 0) {
        showModalAlert("Veuillez allouer au moins une ressource pour le convoi.", "warning");
        return;
    }

    const fd = new FormData();
    fd.append('action', 'create_route');
    fd.append('source_planet_id', src);
    fd.append('target_planet_id', tgt);
    fd.append('wood', wood);
    fd.append('stone', stone);
    fd.append('rice', rice);
    fd.append('interval_hours', interval);
    fd.append('transporter_pref', pref);

    const btn = document.getElementById('btnSubmitCreateTradeRoute');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch('/api/trade_route.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la création de la route.', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (e) {
        showModalAlert('Erreur de transmission avec les intendants.', 'error');
        if (btn) btn.disabled = false;
    }
}

async function toggleTradeRoute(routeId) {
    const fd = new FormData();
    fd.append('action', 'toggle_route');
    fd.append('route_id', routeId);

    try {
        const res = await fetch('/api/trade_route.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || 'Erreur lors du changement de statut.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function deleteTradeRoute(routeId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous supprimer définitivement cette route commerciale ?',
        'Dissolution de Route Commerciale'
    );
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'delete_route');
    fd.append('route_id', routeId);

    try {
        const res = await fetch('/api/trade_route.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModalAlert(data.error || 'Erreur lors de la suppression.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}

async function executeTradeRouteNow(routeId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous affréter et expédier immédiatement ce convoi de transport sans attendre la prochaine heure planifiée ?',
        'Expédition Immédiate de Convoi'
    );
    if (!confirmed) return;

    const fd = new FormData();
    fd.append('action', 'execute_route');
    fd.append('route_id', routeId);

    try {
        const res = await fetch('/api/trade_route.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Échec du lancement du convoi.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec les écuries.', 'error');
    }
}
</script>

