<?php
/**
 * Page de Connexion et Inscription OpenGalaxy
 */
require_once __DIR__ . '/../config/game_constants.php';
$error = $authError ?? null;
$tab = $_GET['tab'] ?? (($action ?? '') === 'register' ? 'register' : 'login');
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
            margin: 0;
            padding: 0;
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

        /* Page d'Accueil Landing */
        .landing-page-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1rem;
            box-sizing: border-box;
        }
        .landing-header {
            text-align: center;
            margin-top: clamp(2.5rem, 7vh, 5.5rem);
            animation: fadeInDown 0.8s ease-out;
        }
        .landing-logo {
            max-width: 480px;
            width: 85vw;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.75)) drop-shadow(0 0 35px rgba(185, 28, 28, 0.35));
            transition: transform 0.3s ease;
        }
        .landing-logo:hover {
            transform: scale(1.02);
        }
        .landing-subtitle {
            color: #fef08a;
            font-size: clamp(0.9rem, 2vw, 1.15rem);
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.95), 0 0 15px rgba(0, 0, 0, 0.85);
            font-family: Georgia, "Times New Roman", serif;
        }

        /* Boutons d'Action en Bas */
        .landing-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 580px;
            margin-bottom: clamp(2.5rem, 6vh, 4.5rem);
            animation: fadeInUp 0.8s ease-out;
        }
        @media (max-width: 576px) {
            .landing-actions {
                flex-direction: column;
                gap: 1rem;
                width: 90%;
            }
        }
        .landing-btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.1rem 2rem;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            outline: none;
            user-select: none;
            white-space: nowrap;
        }
        .landing-btn-icon {
            font-size: 1.25rem;
            line-height: 1;
        }
        .landing-btn-login {
            background: linear-gradient(145deg, rgba(28, 25, 23, 0.94) 0%, rgba(12, 10, 9, 0.98) 100%);
            color: #fef08a;
            border: 2px solid #ca8a04;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6), 0 0 20px rgba(202, 138, 4, 0.25), inset 0 1px 0 rgba(254, 240, 138, 0.2);
        }
        .landing-btn-login:hover {
            background: linear-gradient(145deg, rgba(41, 37, 36, 0.96) 0%, rgba(20, 16, 14, 1) 100%);
            border-color: #eab308;
            color: #ffffff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.75), 0 0 30px rgba(234, 179, 8, 0.45);
            transform: translateY(-3px) scale(1.02);
        }
        .landing-btn-login:active {
            transform: translateY(0) scale(0.99);
        }
        .landing-btn-register {
            background: linear-gradient(145deg, #b91c1c 0%, #991b1b 100%);
            color: #ffffff;
            border: 2px solid #ef4444;
            box-shadow: 0 8px 25px rgba(185, 28, 28, 0.5), 0 0 25px rgba(220, 38, 38, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }
        .landing-btn-register:hover {
            background: linear-gradient(145deg, #dc2626 0%, #b91c1c 100%);
            border-color: #f87171;
            box-shadow: 0 12px 32px rgba(220, 38, 38, 0.65), 0 0 35px rgba(248, 113, 113, 0.45);
            transform: translateY(-3px) scale(1.02);
        }
        .landing-btn-register:active {
            transform: translateY(0) scale(0.99);
        }

        /* Modal Overlay (Dalle de Connexion / Inscription) */
        .auth-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(10, 8, 7, 0.82);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            overflow-y: auto;
            box-sizing: border-box;
        }
        .auth-modal-dialog {
            position: relative;
            max-width: 820px;
            width: 100%;
            margin: auto;
            animation: modalScaleIn 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .auth-modal-close {
            position: absolute;
            top: -16px;
            right: -16px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #b91c1c;
            border: 2px solid #ffffff;
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            z-index: 20;
            transition: all 0.2s ease;
        }
        .auth-modal-close:hover {
            background: #dc2626;
            transform: scale(1.1) rotate(90deg);
        }
        @media (max-width: 576px) {
            .auth-modal-close {
                top: 8px;
                right: 8px;
                width: 32px;
                height: 32px;
                font-size: 1.1rem;
            }
        }
        @keyframes modalScaleIn {
            from {
                opacity: 0;
                transform: scale(0.94) translateY(12px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-box {
            background: rgba(253, 251, 247, 0.98);
            border: 2px solid #b91c1c;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(60, 45, 30, 0.35), 0 0 35px rgba(194, 37, 43, 0.15);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            overflow: hidden;
        }
        .auth-header {
            text-align: center;
            padding: 2rem 1rem 1.25rem;
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

        /* Stepper d'inscription en 3 étapes */
        .auth-stepper {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            padding: 0.5rem 0;
        }
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            opacity: 0.45;
            transition: all 0.3s ease;
            user-select: none;
        }
        .step-indicator.active {
            opacity: 1;
        }
        .step-indicator.completed {
            opacity: 0.85;
        }
        .step-bubble {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #a8a29e;
            color: #57534e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .step-indicator.active .step-bubble {
            border-color: #b91c1c;
            background: #b91c1c;
            color: #ffffff;
            box-shadow: 0 0 14px rgba(185, 28, 28, 0.45);
        }
        .step-indicator.completed .step-bubble {
            border-color: #15803d;
            background: #15803d;
            color: #ffffff;
        }
        .step-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1c1917;
        }
        .step-line {
            flex: 1;
            max-width: 60px;
            min-width: 25px;
            height: 2px;
            background: #e7e5e4;
            margin: 0 0.5rem;
            transition: background 0.3s ease;
        }
        .step-line.active {
            background: #b91c1c;
        }

        /* Viewport & Track du Slider */
        .auth-slider-viewport {
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        .auth-slider-track {
            display: flex;
            width: 300%;
            transition: transform 0.45s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .auth-slide-step {
            width: 33.333333%;
            flex-shrink: 0;
            padding: 0.25rem 0.5rem;
            box-sizing: border-box;
        }
        .slide-nav-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.75rem;
            gap: 1rem;
        }
        .auth-summary-box {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 4px solid #b91c1c;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-top: 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.85rem;
        }
        .auth-summary-item {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .auth-summary-item strong {
            color: #1c1917;
            font-size: 0.95rem;
        }
        .auth-summary-item span {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

<!-- PAGE D'ACCUEIL : LOGO EN HAUT ET 2 BOUTONS EN BAS -->
<div class="landing-page-wrapper">
    <!-- Logo en haut au milieu avec espace depuis le haut -->
    <header class="landing-header">
        <img src="/public/assets/logo_transparent.png?v=<?= file_exists(__DIR__ . '/../public/assets/logo_transparent.png') ? filemtime(__DIR__ . '/../public/assets/logo_transparent.png') : 1 ?>" 
             alt="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>" 
             class="landing-logo">
        <p class="landing-subtitle">
            Chronique Historique de l'Ère Sengoku Jidai &bull; Conquête du Shogunat
        </p>
    </header>

    <!-- En bas : Boutons Connexion et Inscription + Lien Découverte Atelier Pédagogique -->
    <div class="landing-actions flex-column align-items-center">
        <div class="d-flex gap-3 justify-content-center w-100" style="flex-wrap: wrap;">
            <button type="button" class="landing-btn landing-btn-login" onclick="openAuthModal('login')">
                <span class="landing-btn-icon">⚔️</span>
                <span>CONNEXION</span>
            </button>
            <button type="button" class="landing-btn landing-btn-register" onclick="openAuthModal('register')">
                <span class="landing-btn-icon">📜</span>
                <span>INSCRIPTION</span>
            </button>
        </div>
        <a href="/?page=pedagogy" class="text-decoration-none mt-2 px-3 py-1 rounded-pill small text-center" style="color: #67e8f9; background: rgba(8, 145, 178, 0.25); border: 1px solid rgba(6, 182, 212, 0.5); font-size: 0.82rem; letter-spacing: 0.5px; backdrop-filter: blur(4px);">
            <span>🎓</span> Découvrir l'Atelier Pédagogique &bull; Coulisses de Conception &rarr;
        </a>
    </div>
</div>

<!-- DALLE DE CONNEXION / INSCRIPTION (MODAL OVERLAY) -->
<div id="authModal" class="auth-modal-backdrop" style="display: <?= $error ? 'flex' : 'none' ?>;" onclick="handleModalBackdropClick(event)">
    <div class="auth-modal-dialog">
        <button type="button" class="auth-modal-close" onclick="closeAuthModal()" title="Fermer la fenêtre (Échap)">&times;</button>
        
        <div class="auth-box">
            <div class="auth-header" style="text-align: center; padding: 1.5rem 1rem 1.25rem;">
                <div style="margin-bottom: 0.35rem;">
                    <img src="/public/assets/logo_transparent.png?v=<?= file_exists(__DIR__ . '/../public/assets/logo_transparent.png') ? filemtime(__DIR__ . '/../public/assets/logo_transparent.png') : 1 ?>" 
                         alt="<?= defined('GAME_NAME') ? GAME_NAME : 'La Voie du Shogun' ?>" 
                         style="max-width: 320px; width: 75%; height: auto; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(60, 45, 30, 0.15));">
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0; font-weight: 600;">
                    Chronique Historique de l'Ère Sengoku Jidai &bull; Conquête du Shogunat
                </p>
            </div>

            <div class="auth-tabs">
                <button type="button" id="tabBtnLogin" class="auth-tab-btn <?= ($tab === 'login') ? 'active' : '' ?>" onclick="setTab('login')">
                    ⚔️ Connexion
                </button>
                <button type="button" id="tabBtnRegister" class="auth-tab-btn <?= ($tab === 'register') ? 'active' : '' ?>" onclick="setTab('register')">
                    📜 Prêter Allégeance (Inscription)
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

            <!-- Formulaire d'Inscription (Slider Féodal en 3 Étapes) -->
            <form id="registerForm" method="POST" action="?action=register" style="display: <?= ($tab === 'register') ? 'block' : 'none' ?>;">
                
                <!-- Stepper d'Inscription en 3 Étapes -->
                <div class="auth-stepper">
                    <div class="step-indicator active" id="step-ind-1" onclick="goToRegisterStep(1)">
                        <span class="step-bubble" id="step-bubble-1">1</span>
                        <span class="step-label">Identité</span>
                    </div>
                    <div class="step-line" id="step-line-1"></div>
                    <div class="step-indicator" id="step-ind-2" onclick="goToRegisterStep(2)">
                        <span class="step-bubble" id="step-bubble-2">2</span>
                        <span class="step-label">Clan Féodal</span>
                    </div>
                    <div class="step-line" id="step-line-2"></div>
                    <div class="step-indicator" id="step-ind-3" onclick="goToRegisterStep(3)">
                        <span class="step-bubble" id="step-bubble-3">3</span>
                        <span class="step-label">Province</span>
                    </div>
                </div>

                <!-- Viewport du Slider -->
                <div class="auth-slider-viewport">
                    <div class="auth-slider-track" id="registerSliderTrack">
                        
                        <!-- SLIDE 1 : IDENTITÉ DU DAIMYŌ -->
                        <div class="auth-slide-step" id="slide-step-1">
                            <div style="margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 0.35rem 0; font-size: 1.15rem; color: #1c1917; display: flex; align-items: center; gap: 0.5rem;">
                                    <span>📜</span>
                                    <span>Étape 1 sur 3 : Identité de votre Daimyō</span>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                    Choisissez le nom sous lequel votre seigneur sera reconnu dans les chroniques impériales du Shogunat.
                                </p>
                            </div>

                            <div class="form-group">
                                <label>Nom de Daimyō <span style="color:#b91c1c;">*</span></label>
                                <input type="text" name="username" id="reg_username" class="form-control" required minlength="3" placeholder="ex: Shingen_Takeda" autocomplete="username" onkeydown="if(event.key==='Enter'){event.preventDefault();nextRegisterStep(1);}">
                                <div id="username-error" style="display:none; color:#dc2626; font-size:0.78rem; margin-top:4px; font-weight:600;">⚠️ Veuillez saisir un nom de Daimyō d'au moins 3 caractères.</div>
                            </div>
                            <div class="form-group">
                                <label>Email provincial <span style="color:#b91c1c;">*</span></label>
                                <input type="email" name="email" id="reg_email" class="form-control" required placeholder="daimyo@domaine.local" autocomplete="email" onkeydown="if(event.key==='Enter'){event.preventDefault();nextRegisterStep(1);}">
                                <div id="email-error" style="display:none; color:#dc2626; font-size:0.78rem; margin-top:4px; font-weight:600;">⚠️ Veuillez saisir une adresse email valide.</div>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe de protection <span style="color:#b91c1c;">*</span></label>
                                <input type="password" name="password" id="reg_password" class="form-control" required minlength="6" placeholder="Minimum 6 caractères" autocomplete="new-password" onkeydown="if(event.key==='Enter'){event.preventDefault();nextRegisterStep(1);}">
                                <div id="password-error" style="display:none; color:#dc2626; font-size:0.78rem; margin-top:4px; font-weight:600;">⚠️ Le mot de passe doit comporter au moins 6 caractères.</div>
                            </div>

                            <div class="slide-nav-buttons" style="justify-content: flex-end;">
                                <button type="button" class="btn btn-primary" onclick="nextRegisterStep(1)" style="padding: 0.8rem 1.6rem; font-size: 0.95rem;">
                                    Continuer vers le Clan Féodal &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- SLIDE 2 : CHOIX DU CLAN FÉODAL -->
                        <div class="auth-slide-step" id="slide-step-2">
                            <div style="margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 0.35rem 0; font-size: 1.15rem; color: #1c1917; display: flex; align-items: center; gap: 0.5rem;">
                                    <span>⚔️</span>
                                    <span>Étape 2 sur 3 : Choix de votre Clan Féodal</span>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                    Chaque grande maison féodale confère des atouts militaires et économiques uniques pour asseoir votre règne.
                                </p>
                            </div>

                            <input type="hidden" name="faction" id="selectedFactionInput" value="terran">
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                                <?php foreach (FACTIONS as $fKey => $fData): ?>
                                    <div class="faction-choice-card <?= ($fKey === 'terran') ? 'selected' : '' ?>" 
                                         id="faction-card-<?= $fKey ?>" onclick="selectFaction('<?= $fKey ?>', '<?= htmlspecialchars(addslashes($fData['name'])) ?>')">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                                            <span style="font-size: 1.5rem;"><?= $fData['icon'] ?></span>
                                            <strong style="color: <?= $fData['color'] ?>; font-size: 1rem;"><?= htmlspecialchars($fData['name']) ?></strong>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600;">
                                            <?= htmlspecialchars($fData['subname']) ?>
                                        </div>
                                        <p style="font-size: 0.8rem; color: #44403c; margin-bottom: 0.5rem; line-height: 1.35;">
                                            <?= htmlspecialchars($fData['description']) ?>
                                        </p>
                                        <div style="font-size: 0.75rem; color: #b45309; font-weight: 700; background: #fef3c7; border: 1px solid #fde68a; padding: 0.3rem 0.5rem; border-radius: 4px;">
                                            ★ <?= htmlspecialchars($fData['special_ability']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="slide-nav-buttons">
                                <button type="button" class="btn btn-secondary" onclick="prevRegisterStep(2)" style="padding: 0.8rem 1.4rem;">
                                    &larr; Retour (Identité)
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextRegisterStep(2)" style="padding: 0.8rem 1.6rem;">
                                    Choisir ma Province de Départ &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- SLIDE 3 : PROVINCE DE DÉPART -->
                        <div class="auth-slide-step" id="slide-step-3">
                            <div style="margin-bottom: 1.5rem;">
                                <h3 style="margin: 0 0 0.35rem 0; font-size: 1.15rem; color: #1c1917; display: flex; align-items: center; gap: 0.5rem;">
                                    <span>🗾</span>
                                    <span>Étape 3 sur 3 : Province de Fondation du Domaine</span>
                                </h3>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                                    Indiquez la zone géographique de l'archipel où votre château originel sera érigé.
                                </p>
                            </div>

                            <input type="hidden" name="zone" id="selectedZoneInput" value="random">

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.65rem;">
                                <div class="zone-choice-card selected" id="zone-card-random" onclick="selectZone('random', 'Aléatoire (Équilibré)')">
                                    <div style="font-size: 1.4rem;">🎲</div>
                                    <strong style="font-size: 0.85rem; color: #b91c1c;">Aléatoire</strong>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Recommandé</div>
                                </div>
                                <div class="zone-choice-card" id="zone-card-nord_ouest" onclick="selectZone('nord_ouest', 'Nord-Ouest (Monts)')">
                                    <div style="font-size: 1.4rem;">↖️</div>
                                    <strong style="font-size: 0.85rem;">Nord-Ouest</strong>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[- / +] Monts</div>
                                </div>
                                <div class="zone-choice-card" id="zone-card-nord_est" onclick="selectZone('nord_est', 'Nord-Est (Plaines)')">
                                    <div style="font-size: 1.4rem;">↗️</div>
                                    <strong style="font-size: 0.85rem;">Nord-Est</strong>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[+ / +] Plaines</div>
                                </div>
                                <div class="zone-choice-card" id="zone-card-sud_ouest" onclick="selectZone('sud_ouest', 'Sud-Ouest (Shikoku)')">
                                    <div style="font-size: 1.4rem;">↙️</div>
                                    <strong style="font-size: 0.85rem;">Sud-Ouest</strong>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[- / -] Shikoku</div>
                                </div>
                                <div class="zone-choice-card" id="zone-card-sud_est" onclick="selectZone('sud_est', 'Sud-Est (Côtes)')">
                                    <div style="font-size: 1.4rem;">↘️</div>
                                    <strong style="font-size: 0.85rem;">Sud-Est</strong>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">[+ / -] Côtes</div>
                                </div>
                            </div>

                            <!-- Récapitulatif du Pacte Féodal -->
                            <div class="auth-summary-box">
                                <div class="auth-summary-item">
                                    <span>Seigneur Daimyō</span>
                                    <strong id="summaryUsername">-</strong>
                                </div>
                                <div class="auth-summary-item">
                                    <span>Clan Allié</span>
                                    <strong id="summaryClan" style="color: #3b82f6;">Clan Oda</strong>
                                </div>
                                <div class="auth-summary-item">
                                    <span>Province Initiale</span>
                                    <strong id="summaryZone" style="color: #b91c1c;">Aléatoire (Équilibré)</strong>
                                </div>
                            </div>

                            <div class="slide-nav-buttons">
                                <button type="button" class="btn btn-secondary" onclick="prevRegisterStep(3)" style="padding: 0.8rem 1.4rem;">
                                    &larr; Retour (Clan)
                                </button>
                                <button type="submit" class="btn btn-primary" style="padding: 0.9rem 1.8rem; font-size: 1rem; font-weight: 800; background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);">
                                    🏯 Fonder mon Fief Castral &rarr;
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
</div>

<script>
let currentRegisterStep = 1;
let selectedClanLabel = 'Clan Oda';
let selectedZoneLabel = 'Aléatoire (Équilibré)';

function openAuthModal(tab) {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    setTab(tab);
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function handleModalBackdropClick(event) {
    if (event.target === document.getElementById('authModal')) {
        closeAuthModal();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAuthModal();
    }
});

function setTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabBtnLogin = document.getElementById('tabBtnLogin');
    const tabBtnRegister = document.getElementById('tabBtnRegister');

    if (loginForm) loginForm.style.display = (tab === 'login') ? 'block' : 'none';
    if (registerForm) registerForm.style.display = (tab === 'register') ? 'block' : 'none';
    
    if (tabBtnLogin && tabBtnRegister) {
        if (tab === 'login') {
            tabBtnLogin.classList.add('active');
            tabBtnRegister.classList.remove('active');
        } else {
            tabBtnLogin.classList.remove('active');
            tabBtnRegister.classList.add('active');
        }
    }
    if (tab === 'register') {
        goToRegisterStep(currentRegisterStep, false);
    }
}

<?php if ($error || isset($_GET['tab'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    openAuthModal('<?= ($tab === 'register') ? 'register' : 'login' ?>');
});
<?php endif; ?>

function validateStep1() {
    const userInp = document.getElementById('reg_username');
    const emailInp = document.getElementById('reg_email');
    const passInp = document.getElementById('reg_password');

    let valid = true;

    if (!userInp.value || userInp.value.trim().length < 3) {
        document.getElementById('username-error').style.display = 'block';
        userInp.focus();
        valid = false;
    } else {
        document.getElementById('username-error').style.display = 'none';
    }

    if (!emailInp.checkValidity() || !emailInp.value) {
        document.getElementById('email-error').style.display = 'block';
        if (valid) emailInp.focus();
        valid = false;
    } else {
        document.getElementById('email-error').style.display = 'none';
    }

    if (!passInp.value || passInp.value.length < 6) {
        document.getElementById('password-error').style.display = 'block';
        if (valid) passInp.focus();
        valid = false;
    } else {
        document.getElementById('password-error').style.display = 'none';
    }

    return valid;
}

function updateSummary() {
    const u = document.getElementById('reg_username') ? document.getElementById('reg_username').value.trim() : '';
    const sumUser = document.getElementById('summaryUsername');
    const sumClan = document.getElementById('summaryClan');
    const sumZone = document.getElementById('summaryZone');
    if (sumUser) sumUser.textContent = u || '(Non spécifié)';
    if (sumClan) sumClan.textContent = selectedClanLabel;
    if (sumZone) sumZone.textContent = selectedZoneLabel;
}

function goToRegisterStep(targetStep, doValidate = true) {
    if (targetStep > currentRegisterStep && doValidate) {
        if (currentRegisterStep === 1 && !validateStep1()) {
            return;
        }
    }
    
    currentRegisterStep = targetStep;
    const track = document.getElementById('registerSliderTrack');
    if (track) {
        track.style.transform = 'translateX(-' + ((targetStep - 1) * 33.333333) + '%)';
    }

    for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById('step-ind-' + i);
        const bubble = document.getElementById('step-bubble-' + i);
        if (!ind || !bubble) continue;

        if (i < targetStep) {
            ind.className = 'step-indicator completed';
            bubble.textContent = '✓';
        } else if (i === targetStep) {
            ind.className = 'step-indicator active';
            bubble.textContent = i;
        } else {
            ind.className = 'step-indicator';
            bubble.textContent = i;
        }

        if (i < 3) {
            const line = document.getElementById('step-line-' + i);
            if (line) {
                if (i < targetStep) line.classList.add('active');
                else line.classList.remove('active');
            }
        }
    }

    if (targetStep === 3) {
        updateSummary();
    }
}

function nextRegisterStep(fromStep) {
    goToRegisterStep(fromStep + 1, true);
}

function prevRegisterStep(fromStep) {
    goToRegisterStep(fromStep - 1, false);
}

function selectFaction(fKey, fName) {
    document.querySelectorAll('.faction-choice-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('faction-card-' + fKey);
    if (card) card.classList.add('selected');
    document.getElementById('selectedFactionInput').value = fKey;
    selectedClanLabel = fName || fKey;
    updateSummary();
}

function selectZone(zKey, zName) {
    document.querySelectorAll('.zone-choice-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('zone-card-' + zKey);
    if (card) card.classList.add('selected');
    document.getElementById('selectedZoneInput').value = zKey;
    selectedZoneLabel = zName || zKey;
    updateSummary();
}
</script>

</body>
</html>
