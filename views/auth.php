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
    <title>OpenShogun - Chroniques Féodales du Shogunat</title>
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../public/css/style.css') ? filemtime(__DIR__ . '/../public/css/style.css') : time() ?>">
    <style>
        body {
            background: radial-gradient(circle at 50% 50%, rgba(5, 5, 8, 0.15) 0%, rgba(4, 4, 7, 0.65) 100%),
                        url('/public/assets/shogun_login_bg.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/shogun_login_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_login_bg.jpg') : time() ?>') center center / cover no-repeat fixed !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background: rgba(12, 12, 18, 0.90);
            border: 1px solid rgba(220, 38, 38, 0.35);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.95), 0 0 35px rgba(220, 38, 38, 0.25);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }
        .auth-header {
            text-align: center;
            padding: 2.5rem 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }
        .auth-tab-btn {
            flex: 1;
            padding: 1rem;
            background: rgba(0,0,0,0.35);
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
            color: #fff;
            background: rgba(220, 38, 38, 0.15);
            border-bottom: 3px solid #dc2626;
        }
        .faction-choice-card {
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(0,0,0,0.3);
        }
        .faction-choice-card:hover {
            border-color: #dc2626;
            transform: translateY(-2px);
        }
        .faction-choice-card.selected {
            border-color: #dc2626;
            background: rgba(220, 38, 38, 0.18);
            box-shadow: 0 0 15px rgba(220, 38, 38, 0.4);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-muted);
        }
        .form-control {
            width: 100%;
            background: rgba(10, 10, 16, 0.85);
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 10px rgba(220, 38, 38, 0.35);
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1 style="font-size: 2.2rem; font-weight: 800; letter-spacing: 3px; color: #fff; text-transform: uppercase;">
                🏯 OpenShogun
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
</script>

</body>
</html>

