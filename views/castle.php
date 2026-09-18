<?php
/**
 * Vue Dédiée : Donjon Authentique du Japon (現存十二天守)
 * Récit Historique & Enjeu Stratégique de la Bataille Finale du Shogunat
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CastleEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

$castleEngine = new CastleEngine();

// Récupérer le château demandé par son code ou son ID
$identifier = $_GET['code'] ?? $_GET['id'] ?? 'himeji';
$castle = $castleEngine->getCastle($identifier);

if (!$castle) {
    $castle = $castleEngine->getCastle('himeji');
}

// Récupérer les 12 châteaux pour la navigation
$allCastles = $castleEngine->getAllCastles();

// Trouver index du château actuel pour suivant/précédent
$currentIndex = 0;
foreach ($allCastles as $idx => $c) {
    if ($c['id'] === $castle['id']) {
        $currentIndex = $idx;
        break;
    }
}
$prevCastle = $allCastles[($currentIndex > 0) ? $currentIndex - 1 : count($allCastles) - 1];
$nextCastle = $allCastles[($currentIndex < count($allCastles) - 1) ? $currentIndex + 1 : 0];

// Résolution de l'illustration authentique du château
$castleImgFile = !empty($castle['image']) ? $castle['image'] : ($castle['code'] . '.jpg');
$castleImgDisk = __DIR__ . '/../public/assets/castles/' . $castleImgFile;
$hasCastleImg = file_exists($castleImgDisk);
$castleImgSrc = $hasCastleImg ? ('/public/assets/castles/' . $castleImgFile . '?v=' . filemtime($castleImgDisk)) : '/public/assets/shogun_castle_city_bg.jpg';
?>

<div class="container castle-view-container" style="max-width: 1400px; margin: 0 auto; padding: 1.5rem 1rem;">

    <!-- Barre de Navigation Supérieure -->
    <div class="field-nav-bar" style="margin-bottom: 1.5rem;">
        <a href="/?page=map<?= $castle['is_spawned'] ? ('&x=' . $castle['coord_x'] . '&y=' . $castle['coord_y']) : '' ?>" class="field-back-btn">
            <span>&larr;</span>
            <span>Retour à la Carte des Provinces</span>
        </a>

        <div class="field-slot-switcher">
            <a href="/?page=castle&code=<?= $prevCastle['code'] ?>" class="field-arrow-btn" title="Donjon précédent">
                &larr; <?= htmlspecialchars($prevCastle['name']) ?>
            </a>
            <div class="field-current-indicator">
                <span class="field-slot-badge" style="background:#f59e0b; color:#18181b; font-weight:800;">
                    Trésor <?= ($currentIndex + 1) ?> / 12 &bull; 現存十二天守
                </span>
            </div>
            <a href="/?page=castle&code=<?= $nextCastle['code'] ?>" class="field-arrow-btn" title="Donjon suivant">
                <?= htmlspecialchars($nextCastle['name']) ?> &rarr;
            </a>
        </div>
    </div>

    <!-- CARTE PRINCIPALE DU CHÂTEAU AUTHENTIQUE -->
    <div class="field-hero-card" style="border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 10px 35px rgba(245, 158, 11, 0.15);">
        <!-- Fond de carte estompé -->
        <div class="field-hero-bg" style="background-image: url('<?= $castleImgSrc ?>'); filter: blur(2px) brightness(0.65) saturate(1.2);"></div>
        <div class="field-hero-overlay" style="background: linear-gradient(135deg, rgba(253, 251, 247, 0.94) 0%, rgba(254, 243, 199, 0.9) 60%, rgba(254, 215, 170, 0.94) 100%);"></div>

        <div class="field-hero-content">
            <!-- Piédestal de l'Emblème du Château -->
            <div class="field-tile-stage">
                <div class="field-tile-pedestal" style="background: radial-gradient(circle, rgba(245, 158, 11, 0.25) 0%, rgba(254, 243, 199, 0.95) 75%); border: 3px solid #d97706; box-shadow: 0 0 25px rgba(245, 158, 11, 0.35); overflow: hidden; padding: 0;">
                    <?php if ($hasCastleImg): ?>
                        <img src="<?= $castleImgSrc ?>" alt="<?= htmlspecialchars($castle['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                    <?php else: ?>
                        <span style="font-size: 5rem; filter: drop-shadow(0 6px 12px rgba(180, 83, 9, 0.5));">🏯</span>
                    <?php endif; ?>
                </div>
                <div class="field-level-emblem" style="background: linear-gradient(135deg, #d97706, #b45309); border-color: #fde68a;">
                    <span class="emblem-lvl-text" style="color: #fef3c7;">DONJON</span>
                    <span class="emblem-lvl-number" style="font-size: 0.95rem; letter-spacing: 0.5px;"><?= htmlspecialchars($castle['kanji']) ?></span>
                </div>
            </div>

            <!-- Identité & Titres Historiques -->
            <div class="field-meta-pane">
                <div class="field-header-row">
                    <div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                            <span class="badge" style="background: #f59e0b; color: #1c1917; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.75rem;">
                                👑 <?= htmlspecialchars($castle['classification']) ?>
                            </span>
                            <span class="badge" style="background: rgba(185, 28, 28, 0.12); color: #b91c1c; border: 1px solid rgba(185, 28, 28, 0.3); font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.75rem;">
                                📍 <?= htmlspecialchars($castle['province']) ?>
                            </span>
                        </div>
                        <h1 class="field-title" style="color: #1c1917;">
                            <?= htmlspecialchars($castle['name']) ?>
                            <span style="font-size: 1.3rem; color: #b45309; font-weight: 600; font-family: serif;">
                                (<?= htmlspecialchars($castle['japanese_name']) ?> - <?= htmlspecialchars($castle['kanji']) ?>)
                            </span>
                        </h1>
                        <span class="field-subtitle">
                            Bâtisseur : <strong><?= htmlspecialchars($castle['historical_builder']) ?></strong> &bull; Époque : <strong><?= htmlspecialchars($castle['construction_year']) ?></strong>
                        </span>
                    </div>

                    <?php if ($castle['is_spawned']): ?>
                        <div class="field-status-badge ready" style="background: rgba(245, 158, 11, 0.2); color: #92400e; border-color: #f59e0b;">
                            <span class="pulse-dot" style="background: #f59e0b;"></span>
                            <span>Déployé sur la Carte : [<?= $castle['coord_x'] ?> : <?= $castle['coord_y'] ?>]</span>
                        </div>
                    <?php else: ?>
                        <div class="field-status-badge" style="background: rgba(100, 116, 139, 0.15); color: #475569; border: 1px solid rgba(100, 116, 139, 0.3);">
                            <span>En réserve (Non déployé sur la carte)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="field-description" style="font-size: 1.05rem; line-height: 1.6; color: #44403c; margin-top: 0.75rem;">
                    <?= htmlspecialchars($castle['short_desc']) ?>
                </p>

                <!-- Actions Rapides -->
                <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <?php if ($castle['is_spawned']): ?>
                        <a href="/?page=map&x=<?= $castle['coord_x'] ?>&y=<?= $castle['coord_y'] ?>" class="btn btn-primary" style="background: #b91c1c; font-weight: 700;">
                            🗾 Localiser sur la Carte des Provinces [<?= $castle['coord_x'] ?> : <?= $castle['coord_y'] ?>] &rarr;
                        </a>
                    <?php endif; ?>
                    <a href="#section-final-battle" class="btn btn-warning" style="background: #f59e0b; color: #18181b; font-weight: 800;">
                        ⚔️ Découvrir l'Enjeu de la Bataille Finale &darr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($hasCastleImg): ?>
        <!-- ILLUSTRATION PANORAMIQUE UKIYO-E DE LA FORTERESSE -->
        <div class="card" style="margin-bottom: 2rem; border-color: rgba(245, 158, 11, 0.4); background: var(--bg-surface, #fdfbf7); overflow: hidden; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.08);">
            <div style="position: relative; width: 100%; max-height: 520px; overflow: hidden; background: #1c1917;">
                <img src="<?= $castleImgSrc ?>" alt="<?= htmlspecialchars($castle['name']) ?>" style="width: 100%; max-height: 520px; object-fit: cover; object-position: center; display: block; transition: transform 0.4s ease;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(28, 25, 23, 0.9) 0%, rgba(28, 25, 23, 0) 100%); padding: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="text-align: left;">
                        <span style="background: #f59e0b; color: #1c1917; font-weight: 800; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                            Estampe Authentique &bull; <?= htmlspecialchars($castle['kanji']) ?>
                        </span>
                        <h2 style="color: #fff; margin: 0.4rem 0 0 0; font-size: 1.6rem; text-shadow: 0 2px 4px rgba(0,0,0,0.9);">
                            <?= htmlspecialchars($castle['name']) ?>
                        </h2>
                    </div>
                    <span style="color: #fef3c7; font-size: 0.85rem; font-style: italic; background: rgba(0,0,0,0.6); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(254, 243, 199, 0.3);">
                        📍 <?= htmlspecialchars($castle['province']) ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- GRILLE DÉTAILLÉE : HISTOIRE & BATAILLE FINALE -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

        <!-- Colonne Gauche : L'Enjeu de la Bataille Finale & Bénédiction du Fief -->
        <div class="card" id="section-final-battle" style="border-color: rgba(220, 38, 38, 0.4); background: #ffffff;">
            <div class="card-header" style="background: linear-gradient(135deg, rgba(185, 28, 28, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%); border-bottom: 1px solid rgba(185, 28, 28, 0.2);">
                <h3 style="color: #b91c1c; display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.15rem;">
                    <span>⚔️</span> L'Enjeu de la Bataille Finale du Shogunat
                </h3>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <div style="background: rgba(185, 28, 28, 0.06); border-left: 4px solid #b91c1c; padding: 1rem; border-radius: 0 6px 6px 0; margin-bottom: 1.25rem;">
                    <strong style="color: #b91c1c; display: block; font-size: 0.95rem; margin-bottom: 0.35rem;">
                        Sanctuaire de l'Hégémonie Suprême
                    </strong>
                    <p style="font-size: 0.9rem; color: #44403c; line-height: 1.5; margin: 0;">
                        <?= nl2br(htmlspecialchars($castle['final_battle_lore'])) ?>
                    </p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(254, 243, 199, 0.4) 100%); border: 1.5px solid rgba(245, 158, 11, 0.5); padding: 1.1rem; border-radius: 8px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <span style="font-size: 1.3rem;">✨</span>
                        <strong style="color: #92400e; font-size: 0.95rem;">Bénédiction Sacrée & Relique de Province :</strong>
                    </div>
                    <p style="color: #78350f; font-weight: 600; font-size: 0.9rem; margin: 0;">
                        <?= htmlspecialchars($castle['relic_bonus']) ?>
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.85rem;">
                    <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); padding: 0.75rem; border-radius: 6px;">
                        <span style="color: var(--text-muted); display: block;">Garnison Sacrée :</span>
                        <strong style="color: #166534; font-size: 1rem;"><?= number_format($castle['defense_power']) ?> pts</strong>
                    </div>
                    <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); padding: 0.75rem; border-radius: 6px;">
                        <span style="color: var(--text-muted); display: block;">Statut de Contrôle :</span>
                        <strong style="color: #b91c1c; font-size: 1rem;"><?= !empty($castle['controlling_faction']) ? strtoupper($castle['controlling_faction']) : 'Sanctuaire Neutre' ?></strong>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.8rem; color: var(--text-muted);">
                    💡 <em>Dans la phase finale de conquête du serveur, l'alliance de clans qui tiendra le plus grand nombre de ces 12 donjons authentiques proclamera son Daimyō comme nouveau <strong>Shogun du Japon</strong>.</em>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Histoire Féodale & Merveilles Architecturales -->
        <div class="card" style="border-color: var(--border-color); background: #ffffff;">
            <div class="card-header">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.15rem; color: #1c1917;">
                    <span>📜</span> Histoire Féodale & Architecture d'Origine
                </h3>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <h4 style="font-size: 0.95rem; color: #b45309; margin-bottom: 0.5rem;">Chronique du Château</h4>
                <p style="font-size: 0.9rem; color: #44403c; line-height: 1.6; margin-bottom: 1.5rem;">
                    <?= nl2br(htmlspecialchars($castle['full_description'])) ?>
                </p>

                <h4 style="font-size: 0.95rem; color: #b45309; margin-bottom: 0.5rem;">Particularités & Secrets de Fortification</h4>
                <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); padding: 1rem; border-radius: 6px; font-size: 0.88rem; color: #44403c; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($castle['architectural_features'])) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- CARROUSEL / LISTE DES 12 DONJONS AUTHENTIQUES DU JAPON (現存十二天守) -->
    <div class="card" style="border-color: rgba(245, 158, 11, 0.3);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="color: #92400e; display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.1rem;">
                <span>🏯</span> Les 12 Donjons Authentiques Préservés du Japon (現存十二天守)
            </h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Cliquez sur une forteresse pour consulter sa fiche historique</span>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.85rem;">
                <?php foreach ($allCastles as $idx => $c): ?>
                    <?php 
                        $isSelected = ($c['id'] === $castle['id']);
                        $cImgFile = !empty($c['image']) ? $c['image'] : ($c['code'] . '.jpg');
                        $cImgDisk = __DIR__ . '/../public/assets/castles/' . $cImgFile;
                        $cHasImg = file_exists($cImgDisk);
                        $cImgSrc = $cHasImg ? ('/public/assets/castles/' . $cImgFile . '?v=' . filemtime($cImgDisk)) : null;
                    ?>
                    <a href="/?page=castle&code=<?= $c['code'] ?>" 
                       style="text-decoration: none; color: inherit; display: block;"
                       title="Consulter <?= htmlspecialchars($c['name']) ?>">
                        <div style="padding: 0.75rem; border-radius: 8px; border: 1.5px solid <?= $isSelected ? '#b91c1c' : 'var(--border-color)' ?>; background: <?= $isSelected ? 'rgba(185, 28, 28, 0.08)' : 'var(--bg-surface, #fdfbf7)' ?>; transition: all 0.2s ease; display: flex; gap: 0.75rem; align-items: center;">
                            <?php if ($cHasImg): ?>
                                <img src="<?= $cImgSrc ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); flex-shrink: 0;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; border-radius: 6px; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; border: 1px solid var(--border-color);">🏯</div>
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.85rem; font-weight: 700; color: <?= $isSelected ? '#b91c1c' : '#1c1917' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= ($idx + 1) ?>. <?= htmlspecialchars($c['name']) ?>
                                    </span>
                                    <span style="font-size: 0.8rem; color: #b45309; font-weight: 700; font-family: serif; margin-left: 0.35rem;">
                                        <?= htmlspecialchars($c['kanji']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars(explode('(', $c['province'])[0]) ?></span>
                                    <span style="color: <?= $c['is_spawned'] ? '#166534' : '#64748b' ?>; font-weight: 600; flex-shrink: 0; margin-left: 0.35rem;">
                                        <?= $c['is_spawned'] ? ('[' . $c['coord_x'] . ' : ' . $c['coord_y'] . ']') : 'En réserve' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

