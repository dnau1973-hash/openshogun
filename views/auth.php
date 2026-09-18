<?php
/**
 * Page de Connexion et Inscription OpenGalaxy
 */
require_once __DIR__ . '/../config/game_constants.php';
$error = $authError ?? null;
$tab = $_GET['tab'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?> - Chroniques Féodales du Shogunat</title>
    <link rel="stylesheet" href="/public/css/bootstrap-grid.min.css?v=5.3.3">
    <link rel="stylesheet" href="/public/css/bootstrap-utilities.min.css?v=5.3.3">
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../public/css/style.css') ? filemtime(__DIR__ . '/../public/css/style.css') : time() ?>">
    <style>
        body {
            background: url('/public/assets/shogun_login_bg.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/shogun_login_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_login_bg.jpg') : time() ?>') center center / cover no-repeat fixed !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        .auth-video-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }
        .auth-video-element {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%) scale(1.02);
            object-fit: cover;
            opacity: 1 !important;
            filter: none !important;
        }
        .auth-container {
            max-width: 850px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 0 1rem;
            position: relative;
            z-index: 10;
        }
        .auth-box {
            background: rgba(253, 251, 247, 0.96);
            border: 2px solid #b91c1c;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(60, 45, 30, 0.35), 0 0 35px rgba(194, 37, 43, 0.15);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }
        .auth-header {
            text-align: center;
            padding: 2.5rem 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }
        .auth-tab-btn {
            flex: 1;
            padding: 1rem;
            background: rgba(246, 242, 232, 0.7);
            border: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.2s;
        }
        .auth-tab-btn.active {
            color: #c2252b;
            background: #ffffff;
            border-bottom: 3px solid #c2252b;
        }
        .faction-choice-card {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(60, 45, 30, 0.04);
        }
        .faction-choice-card:hover {
            border-color: #c2252b;
            transform: translateY(-2px);
        }
        .faction-choice-card.selected {
            border-color: #c2252b;
            background: rgba(194, 37, 43, 0.06);
            box-shadow: 0 0 15px rgba(194, 37, 43, 0.25);
        }
        .zone-choice-card {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            background: #ffffff;
            text-align: center;
        }
        .zone-choice-card:hover {
            border-color: #c2252b;
            transform: translateY(-2px);
        }
        .zone-choice-card.selected {
            border-color: #c2252b;
            background: rgba(194, 37, 43, 0.08);
            box-shadow: 0 0 10px rgba(194, 37, 43, 0.2);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: #1c1917;
        }
        .form-control {
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--border-color);
            color: #1c1917;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #c2252b;
            box-shadow: 0 0 10px rgba(194, 37, 43, 0.25);
        }
    </style>
</head>
<body>

<?php
$loopVideoMp4 = '/public/assets/shogun_login_bg_loop.mp4';
$loopVideoWebm = '/public/assets/shogun_login_bg_loop.webm';
$hasLoopVideo = file_exists(__DIR__ . '/..' . $loopVideoMp4);
?>

<?php if ($hasLoopVideo): ?>
    <!-- Fond Vidéo Animé en Boucle (sans logo Gemini et raccord invisible) -->
    <div class="auth-video-container">
        <video class="auth-video-element" autoplay muted loop playsinline poster="/public/assets/shogun_login_bg.jpg">
            <?php if (file_exists(__DIR__ . '/..' . $loopVideoWebm)): ?>
                <source src="<?= $loopVideoWebm ?>?v=<?= filemtime(__DIR__ . '/..' . $loopVideoWebm) ?>" type="video/webm">
            <?php endif; ?>
            <source src="<?= $loopVideoMp4 ?>?v=<?= filemtime(__DIR__ . '/..' . $loopVideoMp4) ?>" type="video/mp4">
        </video>
    </div>
<?php endif; ?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1 style="font-size: 2.2rem; font-weight: 800; letter-spacing: 3px; color: #1c1917; text-transform: uppercase;">
                🏯 <?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.5rem;">
                Chronique Historique de l'Ère Sengoku Jidai & Conquête du Shogunat
            </p>
        </div>

        <div class="auth-tabs">
            <button class="auth-tab-btn <?= ($tab === 'login') ? 'active' : '' ?>" onclick="setTab('login')">
                Connexion
            </button>
            <button class="auth-tab-btn <?= ($tab === 'register') ? 'active' : '' ?>" onclick="setTab('register')">
                Prêter Allégeance à un Clan
            </button>
        </div>

        <div style="padding: 2rem;">
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de Connexion -->
            <form id="loginForm" method="POST" action="?action=login" style="display: <?= ($tab === 'login') ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label>Nom de Daimyō ou Email</label>
                    <input type="text" name="username" class="form-control" required placeholder="ex: Nobunaga_Oda">
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; margin-top: 1rem;">
                    Accéder à mon Fief & Donjon &rarr;
                </button>
            </form>

            <!-- Formulaire d'Inscription -->
            <form id="registerForm" method="POST" action="?action=register" style="display: <?= ($tab === 'register') ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label>Nom de Daimyō</label>
                    <input type="text" name="username" class="form-control" required placeholder="ex: Shingen_Takeda">
                </div>
                <div class="form-group">
                    <label>Email provincial</label>
                    <input type="email" name="email" class="form-control" required placeholder="daimyo@openshogun.local">
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimum 6 caractères">
                </div>

                <div class="form-group">
                    <label style="margin-bottom: 0.75rem;">Choisissez votre Clan Féodal :</label>
                    <input type="hidden" name="faction" id="selectedFactionInput" value="terran">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <?php foreach (FACTIONS as $fKey => $fData): ?>
                            <div class="faction-choice-card <?= ($fKey === 'terran') ? 'selected' : '' ?>" 
                                 id="faction-card-<?= $fKey ?>" onclick="selectFaction('<?= $fKey ?>')">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                    <span style="font-size: 1.5rem;"><?= $fData['icon'] ?></span>
                                    <strong style="color: <?= $fData['color'] ?>; font-size: 1rem;"><?= htmlspecialchars($fData['name']) ?></strong>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($fData['subname']) ?>
                                </div>
                                <p style="font-size: 0.8rem; color: var(--text-main); margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($fData['description']) ?>
                                </p>
                                <div style="font-size: 0.75rem; color: #f59e0b; font-weight: 700; background: rgba(0,0,0,0.3); padding: 0.3rem 0.5rem; border-radius: 4px;">
                                    ★ <?= htmlspecialchars($fData['special_ability']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sélection du Quadrant / Zone Géographique (Style Travian) -->
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label style="margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <span>🧭 Choisissez votre Province de Départ (Quadrant) :</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Découpage en 4 zones</span>
                    </label>
                    <input type="hidden" name="zone" id="selectedZoneInput" value="random">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.6rem;">
                        <div class="zone-choice-card selected" id="zone-card-random" onclick="selectZone('random')">
                            <div style="font-size: 1.3rem;">🎲</div>
                            <strong style="font-size: 0.85rem; color: #c2252b;">Aléatoire</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Équilibré</div>
                        </div>
                        <div class="zone-choice-card" id="zone-card-nord_ouest" onclick="selectZone('nord_ouest')">
                            <div style="font-size: 1.3rem;">↖️</div>
                            <strong style="font-size: 0.85rem;">Nord-Ouest</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[- / +] Monts</div>
                        </div>
                        <div class="zone-choice-card" id="zone-card-nord_est" onclick="selectZone('nord_est')">
                            <div style="font-size: 1.3rem;">↗️</div>
                            <strong style="font-size: 0.85rem;">Nord-Est</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[+ / +] Plaines</div>
                        </div>
                        <div class="zone-choice-card" id="zone-card-sud_ouest" onclick="selectZone('sud_ouest')">
                            <div style="font-size: 1.3rem;">↙️</div>
                            <strong style="font-size: 0.85rem;">Sud-Ouest</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[- / -] Shikoku</div>
                        </div>
                        <div class="zone-choice-card" id="zone-card-sud_est" onclick="selectZone('sud_est')">
                            <div style="font-size: 1.3rem;">↘️</div>
                            <strong style="font-size: 0.85rem;">Sud-Est</strong>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[+ / -] Côtes</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; margin-top: 1.5rem;">
                    Fonder mon Fief Castral &rarr;
                </button>
            </form>
        </div>
    </div>
</div>


<script>
function setTab(tab) {
    document.getElementById('loginForm').style.display = (tab === 'login') ? 'block' : 'none';
    document.getElementById('registerForm').style.display = (tab === 'register') ? 'block' : 'none';
    document.querySelectorAll('.auth-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

function selectFaction(fKey) {
    document.querySelectorAll('.faction-choice-card').forEach(c => c.classList.remove('selected'));
    document.getElementById(`faction-card-${fKey}`).classList.add('selected');
    document.getElementById('selectedFactionInput').value = fKey;
}

function selectZone(zKey) {
    document.querySelectorAll('.zone-choice-card').forEach(c => c.classList.remove('selected'));
    document.getElementById(`zone-card-${zKey}`).classList.add('selected');
    document.getElementById('selectedZoneInput').value = zKey;
}
</script>

</body>
</html>

