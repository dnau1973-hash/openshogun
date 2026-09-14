<?php
/**
 * Vue du Classement Galactique, Alliances & Tableau d'Honneur (Style Travian)
 */
require_once __DIR__ . '/../core/HonorEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$db = Database::getConnection();
$honorEngine = new HonorEngine();

$tab = $_GET['tab'] ?? 'general';

// 1. Classement Général
$stmt = $db->query("
    SELECT u.id, u.username, u.faction, u.points, u.created_at,
           COUNT(p.id) as planet_count, a.name as alliance_name, a.tag as alliance_tag
    FROM users u 
    LEFT JOIN planets p ON p.user_id = u.id 
    LEFT JOIN alliances a ON u.alliance_id = a.id 
    GROUP BY u.id 
    ORDER BY u.points DESC, u.id ASC 
    LIMIT 50
");
$players = $stmt->fetchAll();

// 2. Tableau d'Honneur de la Semaine
$honorRoll = $honorEngine->getFullHonorRoll(10);
$currentWeek = date('W');
$currentYear = date('Y');
?>

<div class="ranking-container">
    <!-- Sélecteur d'Onglets de Prestige -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <button class="btn <?= ($tab === 'general') ? 'btn-primary' : 'btn-secondary' ?>" 
                id="tabBtnGeneral" onclick="switchRankingTab('general')" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <span>🏆</span> Classement Général
        </button>
        <button class="btn <?= ($tab === 'honor') ? 'btn-primary' : 'btn-secondary' ?>" 
                id="tabBtnHonor" onclick="switchRankingTab('honor')" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <span>🎖️</span> Tableau d'Honneur de la Semaine
            <span class="badge" style="background: rgba(234, 179, 8, 0.2); color: #facc15; font-size: 0.75rem; border: 1px solid #eab308;">
                S<?= $currentWeek ?>
            </span>
        </button>
    </div>

    <!-- ONGLET 1 : Classement Général des Points de Puissance -->
    <div id="sectionGeneral" style="display: <?= ($tab === 'general') ? 'block' : 'none' ?>;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🏆</span> Panthéon des Daimyōs - Points de Puissance Féodale
                </h2>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Mise à jour en temps réel</span>
            </div>
            <div class="card-body" style="padding:0; overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                    <thead>
                        <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.08); color:var(--text-muted);">
                            <th style="padding:0.75rem 1rem; width: 60px;">Rang</th>
                            <th style="padding:0.75rem 1rem;">Daimyō</th>
                            <th style="padding:0.75rem 1rem;">Clan</th>
                            <th style="padding:0.75rem 1rem;">Pacte de Clan</th>
                            <th style="padding:0.75rem 1rem; text-align: center;">Fiefs</th>
                            <th style="padding:0.75rem 1rem; text-align:right;">Puissance Féodale</th>
                            <th style="padding:0.75rem 1rem; text-align:center; width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($players as $p): ?>
                            <?php 
                                $isCurrent = ($p['id'] == $user['id']); 
                                $fInfo = FACTIONS[$p['faction']] ?? FACTIONS['terran'];
                            ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05); background:<?= $isCurrent ? 'rgba(220,38,38,0.1)' : 'transparent' ?>;">
                                <td style="padding:0.75rem 1rem; font-weight:700; color:<?= ($rank === 1) ? '#facc15' : (($rank === 2) ? '#cbd5e1' : (($rank === 3) ? '#d97706' : 'inherit')) ?>;">
                                    <?= ($rank === 1) ? '🥇 #1' : (($rank === 2) ? '🥈 #2' : (($rank === 3) ? '🥉 #3' : '#' . $rank)) ?>
                                    <?php $rank++; ?>
                                </td>
                                <td style="padding:0.75rem 1rem; font-weight:700;">
                                    <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= $p['id'] ?>)" 
                                       style="color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;" 
                                       class="profile-link-hover" title="Consulter la fiche du Daimyō">
                                        <span>👤</span> <?= htmlspecialchars($p['username']) ?>
                                    </a>
                                    <?= $isCurrent ? '<span style="color:#dc2626; font-size:0.75rem; margin-left:0.5rem;">(Vous)</span>' : '' ?>
                                </td>
                                <td style="padding:0.75rem 1rem;">
                                    <span class="faction-badge <?= $p['faction'] ?>">
                                        <?= $fInfo['icon'] ?> <?= htmlspecialchars($fInfo['name']) ?>
                                    </span>
                                </td>
                                <td style="padding:0.75rem 1rem; color:var(--text-muted);">
                                    <?= $p['alliance_tag'] ? '[' . htmlspecialchars($p['alliance_tag']) . ']' : '-' ?>
                                </td>
                                <td style="padding:0.75rem 1rem; text-align:center; font-weight:700;">
                                    <?= number_format($p['planet_count']) ?>
                                </td>
                                <td style="padding:0.75rem 1rem; text-align:right; font-weight:700; color:#dc2626;">
                                    <?= number_format($p['points']) ?>
                                </td>
                                <td style="padding:0.75rem 1rem; text-align:center;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                        <button onclick="openPlayerProfileModal(<?= $p['id'] ?>)" class="btn btn-secondary" style="font-size:0.75rem; padding:0.25rem 0.5rem;" title="Fiche du Daimyō">
                                            👤 Fiche
                                        </button>
                                        <?php if (!$isCurrent): ?>
                                            <a href="?page=messages&tab=compose&to=<?= urlencode($p['username']) ?>" 
                                               class="btn btn-secondary" style="font-size:0.75rem; padding:0.25rem 0.5rem;" 
                                               title="Envoyer une missive">
                                                ✉️
                                            </a>
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

    <!-- ONGLET 2 : Tableau d'Honneur Hebdomadaire (Style Travian) -->
    <div id="sectionHonor" style="display: <?= ($tab === 'honor') ? 'block' : 'none' ?>;">
        <!-- Bannière d'Honneur -->
        <div style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.15) 0%, rgba(17, 18, 24, 0.95) 100%); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="color: #facc15; font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🎖️</span> Tableau d'Honneur Féodal - Semaine <?= $currentWeek ?> / <?= $currentYear ?>
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.35rem; line-height: 1.5;">
                    Les 10 plus illustres daimyōs récompensés chaque semaine par décret impérial du Shogunat.<br>
                    Médailles décernées : <strong>🥇 Or (1er)</strong>, <strong>🥈 Argent (2ème)</strong>, <strong>🥉 Bronze (3ème)</strong> et <strong>🎖️ Rubans Top 10</strong>.
                </p>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: rgba(234, 179, 8, 0.25); color: #fef08a; border: 1px solid #eab308; padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 800;">
                    🏆 DÉCRET DU SHOGUNAT
                </span>
            </div>
        </div>

        <!-- 4 Colonnes du Top 10 Travian-Style -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 1.5rem;">
            <!-- 1. Meilleure Progression -->
            <div class="card" style="border-color: rgba(220, 38, 38, 0.3);">
                <div class="card-header" style="background: rgba(220, 38, 38, 0.1); border-bottom: 1px solid rgba(220, 38, 38, 0.2); padding: 0.85rem 1rem;">
                    <h3 style="color: #dc2626; font-size: 1.05rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📈</span> Top Progression
                    </h3>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Puissance de domaine acquise cette semaine</small>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php renderHonorColumn($honorRoll['progression'], 'points'); ?>
                </div>
            </div>

            <!-- 2. Meilleurs Attaquants -->
            <div class="card" style="border-color: rgba(239, 68, 68, 0.3);">
                <div class="card-header" style="background: rgba(239, 68, 68, 0.1); border-bottom: 1px solid rgba(239, 68, 68, 0.2); padding: 0.85rem 1rem;">
                    <h3 style="color: #f87171; font-size: 1.05rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>⚔️</span> Top Conquérants
                    </h3>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Sièges victorieux et garnisons vaincues</small>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php renderHonorColumn($honorRoll['attack'], 'points'); ?>
                </div>
            </div>

            <!-- 3. Meilleurs Défenseurs -->
            <div class="card" style="border-color: rgba(52, 211, 153, 0.3);">
                <div class="card-header" style="background: rgba(52, 211, 153, 0.1); border-bottom: 1px solid rgba(52, 211, 153, 0.2); padding: 0.85rem 1rem;">
                    <h3 style="color: #34d399; font-size: 1.05rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🛡️</span> Top Défenseurs
                    </h3>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Assauts ennemis repoussés sur vos châteaux</small>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php renderHonorColumn($honorRoll['defense'], 'points'); ?>
                </div>
            </div>

            <!-- 4. Meilleurs Pillards -->
            <div class="card" style="border-color: rgba(168, 85, 247, 0.3);">
                <div class="card-header" style="background: rgba(168, 85, 247, 0.1); border-bottom: 1px solid rgba(168, 85, 247, 0.2); padding: 0.85rem 1rem;">
                    <h3 style="color: #c084fc; font-size: 1.05rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🌾</span> Top Pillards de Riz
                    </h3>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Récoltes et vivres saisis en raid</small>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php renderHonorColumn($honorRoll['raid'], 'ressources'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Rendu d'une colonne du Tableau d'Honneur
 */
function renderHonorColumn(array $list, string $unitLabel): void {
    if (empty($list)) {
        echo "<div style='padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;'>Aucune donnée pour cette semaine.</div>";
        return;
    }
    echo "<table style='width:100%; border-collapse:collapse; font-size:0.85rem;'>";
    $pos = 1;
    foreach ($list as $row) {
        $medalIcon = match ($pos) {
            1 => "🥇",
            2 => "🥈",
            3 => "🥉",
            default => "<span style='color:var(--text-muted); font-size:0.75rem;'>#{$pos}</span>"
        };
        $fInfo = FACTIONS[$row['faction']] ?? FACTIONS['terran'];
        echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;'>";
        echo "<td style='padding: 0.6rem 0.75rem; width: 35px; text-align: center; font-size: 1rem;'>{$medalIcon}</td>";
        echo "<td style='padding: 0.6rem 0.5rem; font-weight: 700;'>
                <a href='javascript:void(0)' onclick='openPlayerProfileModal({$row['user_id']})' 
                   style='color:#fff; text-decoration:none;' class='profile-link-hover' title='Voir la fiche de {$row['username']}'>
                   {$fInfo['icon']} " . htmlspecialchars($row['username']) . "
                </a>
              </td>";
        echo "<td style='padding: 0.6rem 0.75rem; text-align: right; font-weight: 800; color: #facc15; font-family: monospace;'>
                +" . number_format($row['score']) . "
              </td>";
        echo "</tr>";
        $pos++;
    }
    echo "</table>";
}
?>

<style>
.profile-link-hover:hover {
    color: #dc2626 !important;
    text-shadow: 0 0 10px rgba(220, 38, 38, 0.5);
}
</style>

<script>
function switchRankingTab(tabName) {
    document.getElementById('sectionGeneral').style.display = (tabName === 'general') ? 'block' : 'none';
    document.getElementById('sectionHonor').style.display = (tabName === 'honor') ? 'block' : 'none';
    
    document.getElementById('tabBtnGeneral').className = (tabName === 'general') ? 'btn btn-primary' : 'btn btn-secondary';
    document.getElementById('tabBtnHonor').className = (tabName === 'honor') ? 'btn btn-primary' : 'btn btn-secondary';
}
</script>
