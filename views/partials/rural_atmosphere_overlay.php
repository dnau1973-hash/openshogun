<?php
/**
 * Moteur Météo & Cycle Jour/Nuit/Saisons — Terroir Féodal (OpenShogun)
 * Animations vectorielles ultra-légères 60 FPS GPU :
 * 1. Cycle Jour / Nuit / Aube / Crépuscule dynamique synchronisé sur l'horloge réelle ou réglable manuellement
 * 2. Éclairage nocturne magique : fenêtres du Tenshu et chaumières du village illuminées d'or, lanternes tōrō du sanctuaire rougeoyantes, lucioles d'eau
 * 3. Hiver sous la Neige (Yuki) : palette glaciale, flocons de neige tourbillonnants
 * 4. Pluie d'Automne (Ame & Kōyō) : rideaux d'eau fins, feuilles d'érable rouges Momiji
 * 5. Vol fluide des grues, marche des paysans sur les sentiers, brume d'altitude et fumée de chaume
 */
?>

<!-- CONTENEUR DU MOTEUR MÉTÉO & VIE DU TERROIR -->
<div class="rural-atmosphere-overlay theme-day" id="ruralAtmosphereOverlay" aria-hidden="true">

    <!-- 0. CALQUE DE FILTRE ATMOSPHÉRIQUE & TEINTE DU CIEL -->
    <div class="atmosphere-weather-tint"></div>

    <!-- 1. NAPPES DE BRUME MATINALE (DRIFT PARALLAXE CONTINU) -->
    <div class="atmosphere-mist-wrapper">
        <div class="mist-cloud mist-cloud-slow">
            <svg viewBox="0 0 1600 240" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="mistGrad1" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                        <stop offset="45%" stop-color="#ffffff" stop-opacity="0.32" />
                        <stop offset="75%" stop-color="#f8fafc" stop-opacity="0.18" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="M0,120 Q200,60 400,110 T800,90 T1200,120 T1600,80 L1600,240 L0,240 Z" fill="url(#mistGrad1)" />
            </svg>
        </div>
        <div class="mist-cloud mist-cloud-fast">
            <svg viewBox="0 0 1600 240" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="mistGrad2" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                        <stop offset="50%" stop-color="#ffffff" stop-opacity="0.25" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="M0,100 Q300,150 600,90 T1200,130 T1600,100 L1600,240 L0,240 Z" fill="url(#mistGrad2)" />
            </svg>
        </div>
    </div>

    <!-- 2. CIEL NOCTURNE : LUNE & ÉTOILES SCINTILLANTES -->
    <div class="atmosphere-night-sky">
        <!-- Croissant de Lune Féodale -->
        <div class="night-moon">
            <svg viewBox="0 0 36 36" width="32" height="32">
                <path d="M28,6 A14,14 0 1,0 30,28 A12,12 0 1,1 28,6 Z" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 6px rgba(254,240,138,0.7))" />
            </svg>
        </div>
        <!-- Étoiles Scintillantes -->
        <div class="star star-1" style="left: 12%; top: 6%;"></div>
        <div class="star star-2" style="left: 22%; top: 12%;"></div>
        <div class="star star-3" style="left: 35%; top: 5%;"></div>
        <div class="star star-4" style="left: 58%; top: 9%;"></div>
        <div class="star star-5" style="left: 71%; top: 4%;"></div>
        <div class="star star-6" style="left: 88%; top: 11%;"></div>
        <div class="star star-7" style="left: 44%; top: 7%;"></div>
    </div>

    <!-- 3. ÉCLAIRAGE NOCTURNE : FENÊTRES DU CHÂTEAU, LANTERNES DU VILLAGE & SANCTUAIRE -->
    <div class="atmosphere-night-lights">
        <!-- Fenêtres dorées du Donjon Tenshu -->
        <div class="light-glow light-tenshu-1" style="left: 48.2%; top: 15.5%;"></div>
        <div class="light-glow light-tenshu-2" style="left: 49.8%; top: 15.5%;"></div>
        <div class="light-glow light-tenshu-3" style="left: 49.0%; top: 17.6%;"></div>

        <!-- Chaumières et ruelles du Village Central -->
        <div class="light-glow light-village-1" style="left: 47.4%; top: 53.8%;"></div>
        <div class="light-glow light-village-2" style="left: 49.3%; top: 55.2%;"></div>
        <div class="light-glow light-village-3" style="left: 51.1%; top: 53.6%;"></div>
        <div class="light-glow light-village-4" style="left: 48.6%; top: 57.0%;"></div>

        <!-- Lanternes Tōrō rouges du Sanctuaire Shintō & Pont -->
        <div class="light-glow light-lantern-shrine-1" style="left: 84.1%; top: 76.8%;"></div>
        <div class="light-glow light-lantern-shrine-2" style="left: 86.4%; top: 76.8%;"></div>

        <!-- Lucioles (Hotaru) au-dessus des Rizières et Ruisseaux -->
        <div class="firefly firefly-1" style="left: 22%; top: 76%;"></div>
        <div class="firefly firefly-2" style="left: 31%; top: 82%;"></div>
        <div class="firefly firefly-3" style="left: 44%; top: 78%;"></div>
        <div class="firefly firefly-4" style="left: 65%; top: 73%;"></div>
        <div class="firefly firefly-5" style="left: 74%; top: 68%;"></div>
    </div>

    <!-- 4. VOL MAJESTUEUX DES HÉRONS / GRUES DU JAPON (TRAVERSÉE FLUIDE 72s) -->
    <div class="atmosphere-cranes-flock">
        <div class="crane-bird crane-lead">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.9" />
                <path d="M31,6 L34,7 L30,8 Z" fill="#ea580c" />
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
        <div class="crane-bird crane-follower-1">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.85" />
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
        <div class="crane-bird crane-follower-2">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.8" />
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
    </div>

    <!-- 5. VOLUTES DE FUMÉE & ENCENS (TENSHU, VILLAGE, SANCTUAIRE) -->
    <div class="smoke-emitter smoke-tenshu" style="left: 49%; top: 12%;">
        <div class="smoke-particle p1"></div>
        <div class="smoke-particle p2"></div>
        <div class="smoke-particle p3"></div>
    </div>
    <div class="smoke-emitter smoke-village" style="left: 48.5%; top: 51%;">
        <div class="smoke-particle p1"></div>
        <div class="smoke-particle p2"></div>
        <div class="smoke-particle p3"></div>
    </div>
    <div class="smoke-emitter smoke-shrine" style="left: 85%; top: 73.5%;">
        <div class="smoke-particle p1 incense-particle"></div>
        <div class="smoke-particle p2 incense-particle"></div>
        <div class="smoke-particle p3 incense-particle"></div>
    </div>

    <!-- 6. CIRCULATION DES PAYSANS & DU CHARIOT SUR LES CHEMINS DÉGAGÉS -->
    <div class="atmosphere-paths-life">
        <!-- Paysan des rizières (sentier sud) -->
        <div class="peasant-walker peasant-rice">
            <div class="peasant-bob">
                <svg viewBox="0 0 24 28" class="peasant-svg">
                    <polygon points="12,2 4,8 20,8" fill="#ca8a04" />
                    <circle cx="12" cy="9.5" r="2.5" fill="#e2b17a" />
                    <path d="M10,12 L14,12 L15,19 L9,19 Z" fill="#1e3a8a" />
                    <line x1="2" y1="13" x2="22" y2="13" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <rect x="1" y="14" width="4" height="4" rx="1" fill="#ca8a04" />
                    <line x1="3" y1="13" x2="3" y2="14" stroke="#78350f" stroke-width="0.8" />
                    <rect x="19" y="14" width="4" height="4" rx="1" fill="#ca8a04" />
                    <line x1="21" y1="13" x2="21" y2="14" stroke="#78350f" stroke-width="0.8" />
                    <line class="leg-left" x1="10" y1="19" x2="9" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <line class="leg-right" x1="14" y1="19" x2="15" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Bûcheron descendant la colline ouest -->
        <div class="peasant-walker peasant-wood">
            <div class="peasant-bob">
                <svg viewBox="0 0 24 28" class="peasant-svg">
                    <polygon points="12,2 4,8 20,8" fill="#ca8a04" />
                    <circle cx="12" cy="9.5" r="2.5" fill="#e2b17a" />
                    <path d="M10,12 L14,12 L15,19 L9,19 Z" fill="#9a3412" />
                    <rect x="5" y="11" width="5" height="7" rx="1" fill="#713f12" />
                    <line class="leg-left" x1="10" y1="19" x2="9" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <line class="leg-right" x1="14" y1="19" x2="15" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Attelage traditionnel du bœuf de labour -->
        <div class="ox-cart-unit">
            <div class="ox-cart-bob">
                <svg viewBox="0 0 54 26" class="ox-cart-svg">
                    <ellipse cx="44" cy="15" rx="7" ry="5" fill="#334155" />
                    <circle cx="49" cy="12" r="3.5" fill="#1e293b" />
                    <path d="M48,10 Q50,7 53,7" stroke="#e2e8f0" stroke-width="1.2" fill="none" />
                    <line class="ox-leg-front" x1="48" y1="19" x2="49" y2="25" stroke="#1e293b" stroke-width="1.8" stroke-linecap="round" />
                    <line class="ox-leg-back" x1="40" y1="19" x2="39" y2="25" stroke="#1e293b" stroke-width="1.8" stroke-linecap="round" />
                    <line x1="28" y1="16" x2="40" y2="15" stroke="#78350f" stroke-width="1.5" />
                    <rect x="8" y="9" width="20" height="9" rx="1" fill="#854d0e" stroke="#581c87" stroke-width="0.5" />
                    <ellipse cx="14" cy="8" rx="4" ry="2.5" fill="#fef08a" opacity="0.9" />
                    <ellipse cx="21" cy="7.5" rx="4.5" ry="2.8" fill="#fef08a" opacity="0.9" />
                    <circle cx="18" cy="18" r="5" fill="#451a03" stroke="#ca8a04" stroke-width="1.2" />
                    <circle cx="18" cy="18" r="1.5" fill="#ca8a04" />
                </svg>
            </div>
        </div>
    </div>

    <!-- 7. PARTICULES SAISONNIÈRES : PÉTALES DE SAKURA (PRINTEMPS) -->
    <div class="atmosphere-sakura-container">
        <div class="sakura-petal p-sakura-1"></div>
        <div class="sakura-petal p-sakura-2"></div>
        <div class="sakura-petal p-sakura-3"></div>
        <div class="sakura-petal p-sakura-4"></div>
        <div class="sakura-petal p-sakura-5"></div>
        <div class="sakura-petal p-sakura-6"></div>
    </div>

    <!-- 8. PARTICULES SAISONNIÈRES : FLOCONS DE NEIGE (HIVER YUKI) -->
    <div class="atmosphere-snow-container">
        <?php for ($i = 1; $i <= 26; $i++): ?>
            <div class="snow-flake snow-<?= $i ?>"></div>
        <?php endfor; ?>
    </div>

    <!-- 9. PARTICULES SAISONNIÈRES : PLUIE FINE & FEUILLES D'ÉRABLE KŌYŌ (AUTOMNE AME) -->
    <div class="atmosphere-rain-container">
        <div class="rain-curtain"></div>
        <!-- Feuilles d'érable japonaises Momiji -->
        <div class="momiji-leaf momiji-1"></div>
        <div class="momiji-leaf momiji-2"></div>
        <div class="momiji-leaf momiji-3"></div>
        <div class="momiji-leaf momiji-4"></div>
        <div class="momiji-leaf momiji-5"></div>
    </div>

</div>

<!-- FEUILLE DE STYLE MATÉRIELLE DU MOTEUR MÉTÉO (60 FPS GPU) -->
<style>
/* Conteneur principal */
.rural-atmosphere-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 10;
    overflow: hidden;
    user-select: none;
    transition: filter 1.2s ease, opacity 0.5s ease;
}

.rural-atmosphere-overlay.is-disabled {
    opacity: 0 !important;
    display: none !important;
}

/* ============================================================
   0. FILTRES ATMOSPHÉRIQUES & COULEURS DU CIEL
   ============================================================ */
.atmosphere-weather-tint {
    position: absolute;
    inset: 0;
    pointer-events: none;
    transition: background 1.2s ease, opacity 1.2s ease;
}

/* Thème Plein Jour (Estampe ensoleillée) */
.theme-day .atmosphere-weather-tint {
    background: transparent;
    opacity: 0;
}

/* Thème Aube (Matin brumeux doré) */
.theme-dawn .atmosphere-weather-tint {
    background: linear-gradient(180deg, rgba(251, 191, 36, 0.18) 0%, rgba(244, 114, 182, 0.12) 40%, rgba(255, 255, 255, 0.05) 100%);
    opacity: 0.9;
    mix-blend-mode: soft-light;
}

/* Thème Crépuscule (Yūgure - Or flamboyant & Pourpre) */
.theme-dusk .atmosphere-weather-tint {
    background: linear-gradient(180deg, rgba(147, 51, 234, 0.28) 0%, rgba(249, 115, 22, 0.32) 45%, rgba(217, 119, 6, 0.15) 100%);
    opacity: 0.95;
    mix-blend-mode: hard-light;
}

/* Thème Nuit Féodale (Yoru - Nuit étoilée & Lanternes) */
.theme-night .atmosphere-weather-tint {
    background: linear-gradient(180deg, rgba(8, 14, 38, 0.78) 0%, rgba(15, 23, 42, 0.68) 55%, rgba(10, 16, 32, 0.72) 100%);
    opacity: 0.98;
    mix-blend-mode: multiply;
}

/* Thème Hiver Sous la Neige (Yuki - Froid, givre, blanc) */
.theme-snow .atmosphere-weather-tint {
    background: linear-gradient(180deg, rgba(224, 242, 254, 0.22) 0%, rgba(241, 245, 249, 0.25) 50%, rgba(255, 255, 255, 0.2) 100%);
    backdrop-filter: saturate(0.55) brightness(1.05);
    opacity: 0.95;
}

/* Thème Pluie d'Automne (Ame & Momiji) */
.theme-rain .atmosphere-weather-tint {
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.38) 0%, rgba(51, 65, 85, 0.25) 60%, rgba(15, 23, 42, 0.28) 100%);
    backdrop-filter: saturate(0.85);
    opacity: 0.95;
}

/* ============================================================
   1. BRUME MATINALE (HORIZON MIST)
   ============================================================ */
.atmosphere-mist-wrapper {
    position: absolute;
    top: 5%;
    left: 0;
    width: 100%;
    height: 35%;
    overflow: hidden;
    pointer-events: none;
    opacity: 0.75;
    transition: opacity 1s ease;
}

.theme-night .atmosphere-mist-wrapper {
    opacity: 0.35;
}

.theme-snow .atmosphere-mist-wrapper {
    opacity: 0.9;
}

.mist-cloud {
    position: absolute;
    top: 0;
    left: 0;
    width: 200%;
    height: 100%;
    will-change: transform;
}

.mist-cloud svg {
    width: 100%;
    height: 100%;
    display: block;
}

.mist-cloud-slow {
    animation: driftMist 65s linear infinite;
    opacity: 0.85;
}

.mist-cloud-fast {
    top: 6%;
    animation: driftMist 45s linear infinite reverse;
    opacity: 0.65;
}

@keyframes driftMist {
    0%   { transform: translate3d(0, 0, 0); }
    100% { transform: translate3d(-50%, 0, 0); }
}

/* ============================================================
   2. CIEL NOCTURNE : LUNE & ÉTOILES
   ============================================================ */
.atmosphere-night-sky {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 1.2s ease;
    pointer-events: none;
}

.theme-night .atmosphere-night-sky {
    opacity: 1;
}

.night-moon {
    position: absolute;
    right: 14%;
    top: 6%;
    filter: drop-shadow(0 0 12px rgba(254, 240, 138, 0.85));
    animation: moonFloat 8s ease-in-out infinite alternate;
}

@keyframes moonFloat {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-3px); }
}

.star {
    position: absolute;
    width: 2px;
    height: 2px;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 4px #ffffff, 0 0 8px #93c5fd;
    animation: starTwinkle 3s ease-in-out infinite alternate;
}

.star-2 { animation-delay: 0.7s; width: 3px; height: 3px; }
.star-3 { animation-delay: 1.4s; }
.star-4 { animation-delay: 2.1s; width: 2.5px; height: 2.5px; }
.star-5 { animation-delay: 0.9s; }
.star-6 { animation-delay: 1.8s; width: 3px; height: 3px; }
.star-7 { animation-delay: 2.5s; }

@keyframes starTwinkle {
    0%   { opacity: 0.3; transform: scale(0.8); }
    100% { opacity: 1;   transform: scale(1.3); }
}

/* ============================================================
   3. ÉCLAIRAGE NOCTURNE (FENÊTRES D'OR & LANTERNES)
   ============================================================ */
.atmosphere-night-lights {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 1.2s ease;
    pointer-events: none;
}

.theme-night .atmosphere-night-lights {
    opacity: 1;
}

.light-glow {
    position: absolute;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    will-change: opacity, transform;
}

/* Fenêtres dorées du Tenshu */
.light-tenshu-1, .light-tenshu-2, .light-tenshu-3 {
    width: 5px;
    height: 6px;
    background: #fef08a;
    box-shadow: 0 0 8px 3px rgba(250, 204, 21, 0.85), 0 0 16px 6px rgba(245, 158, 11, 0.45);
    animation: windowPulse 4s ease-in-out infinite alternate;
}

.light-tenshu-2 { animation-delay: 1.5s; }
.light-tenshu-3 { animation-delay: 2.8s; width: 6px; height: 5px; }

/* Chaumières du village */
.light-village-1, .light-village-2, .light-village-3, .light-village-4 {
    width: 6px;
    height: 5px;
    background: #fed7aa;
    box-shadow: 0 0 10px 4px rgba(249, 115, 22, 0.8), 0 0 18px 6px rgba(234, 88, 12, 0.35);
    animation: windowPulse 3.5s ease-in-out infinite alternate;
}

.light-village-2 { animation-delay: 1.2s; }
.light-village-3 { animation-delay: 2.1s; }
.light-village-4 { animation-delay: 0.8s; }

/* Lanternes Shintō rouges */
.light-lantern-shrine-1, .light-lantern-shrine-2 {
    width: 5px;
    height: 7px;
    background: #fecaca;
    box-shadow: 0 0 10px 4px rgba(239, 68, 68, 0.85), 0 0 18px 6px rgba(185, 28, 28, 0.45);
    animation: lanternFlicker 2.5s ease-in-out infinite alternate;
}
.light-lantern-shrine-2 { animation-delay: 1.3s; }

@keyframes windowPulse {
    0%   { opacity: 0.8; transform: translate(-50%, -50%) scale(0.95); }
    100% { opacity: 1;   transform: translate(-50%, -50%) scale(1.15); }
}

@keyframes lanternFlicker {
    0%   { opacity: 0.75; transform: translate(-50%, -50%) scale(0.9); }
    50%  { opacity: 1;    transform: translate(-50%, -50%) scale(1.1); }
    100% { opacity: 0.85; transform: translate(-50%, -50%) scale(1.0); }
}

/* Lucioles féodales (Hotaru) */
.firefly {
    position: absolute;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: #a3e635;
    box-shadow: 0 0 6px 2px rgba(163, 230, 53, 0.9), 0 0 12px 4px rgba(132, 204, 22, 0.5);
    animation: fireflyDrift 6s ease-in-out infinite alternate;
}

.firefly-2 { animation-delay: 1.5s; animation-duration: 7s; }
.firefly-3 { animation-delay: 3s;   animation-duration: 5.5s; }
.firefly-4 { animation-delay: 2s;   animation-duration: 8s; }
.firefly-5 { animation-delay: 4.5s; animation-duration: 6.5s; }

@keyframes fireflyDrift {
    0%   { transform: translate(0, 0) scale(0.8); opacity: 0.4; }
    50%  { transform: translate(15px, -12px) scale(1.2); opacity: 1; }
    100% { transform: translate(-10px, -20px) scale(0.7); opacity: 0.2; }
}

/* ============================================================
   4. VOL DES GRUES (72s LINÉAIRE MAJESTUEUX)
   ============================================================ */
.atmosphere-cranes-flock {
    position: absolute;
    top: 10%;
    left: -18%;
    width: 120px;
    height: 60px;
    will-change: left, top, transform;
    animation: flyAcrossSky 72s linear infinite;
    transition: opacity 1s ease;
}

.theme-snow .atmosphere-cranes-flock {
    opacity: 0.4; /* Grues plus discrètes sous la neige */
}

.crane-bird {
    position: absolute;
    width: 24px;
    height: 16px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));
}

.crane-lead { left: 45px; top: 10px; width: 28px; height: 18px; }
.crane-follower-1 { left: 10px; top: 0px; width: 22px; height: 14px; opacity: 0.9; }
.crane-follower-2 { left: 0px; top: 26px; width: 20px; height: 13px; opacity: 0.8; }

.crane-wing-left {
    transform-origin: 15px 10px;
    animation: craneFlap 1.6s ease-in-out infinite alternate;
}
.crane-wing-right {
    transform-origin: 14px 10px;
    animation: craneFlap 1.6s ease-in-out infinite alternate-reverse;
}

@keyframes craneFlap {
    0%   { transform: scaleY(1) rotate(0deg); }
    35%  { transform: scaleY(0.2) rotate(-8deg); }
    70%  { transform: scaleY(-0.8) rotate(18deg); }
    100% { transform: scaleY(-0.9) rotate(22deg); }
}

@keyframes flyAcrossSky {
    0%   { left: -18%; top: 12%; opacity: 0; transform: scale(0.7); }
    3%   { opacity: 0.95; }
    45%  { top: 7%; opacity: 1; transform: scale(0.85); }
    85%  { left: 104%; top: 4%; opacity: 0.95; transform: scale(1.0); }
    90%  { left: 112%; top: 3.5%; opacity: 0; transform: scale(1.05); }
    100% { left: 112%; top: 3.5%; opacity: 0; transform: scale(1.05); }
}

/* ============================================================
   5. VOLUTES DE FUMÉE (VILLAGE, CHÂTEAU, SANCTUAIRE)
   ============================================================ */
.smoke-emitter {
    position: absolute;
    width: 14px;
    height: 14px;
    transform: translate(-50%, -100%);
    pointer-events: none;
}

.smoke-particle {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(226,232,240,0.3) 60%, rgba(255,255,255,0) 100%);
    transform: translateX(-50%);
    animation: riseSmoke 3.4s ease-out infinite;
}

.smoke-particle.p2 { animation-delay: 1.1s; width: 10px; height: 10px; }
.smoke-particle.p3 { animation-delay: 2.2s; width: 7px; height: 7px; }

.incense-particle {
    background: radial-gradient(circle, rgba(254,240,138,0.7) 0%, rgba(253,186,116,0.35) 50%, rgba(255,255,255,0) 100%) !important;
    animation-duration: 4.2s !important;
}

@keyframes riseSmoke {
    0%   { transform: translate3d(-50%, 0, 0) scale(0.5); opacity: 0; }
    20%  { opacity: 0.65; }
    70%  { opacity: 0.35; }
    100% { transform: translate3d(-30%, -35px, 0) scale(2.2); opacity: 0; }
}

/* ============================================================
   6. PAYSANS & CHARRETTE SUR LES SENTIERS
   ============================================================ */
.atmosphere-paths-life {
    position: absolute;
    inset: 0;
}

.peasant-rice {
    position: absolute;
    left: 28%;
    top: 73.5%;
    width: 16px;
    height: 20px;
    will-change: left, top, transform;
    animation: walkPathRice 76s linear infinite;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.6));
}

@keyframes walkPathRice {
    0%   { left: 28%; top: 73.5%; transform: scale(0.9); opacity: 0; }
    3%   { opacity: 1; }
    40%  { left: 48%; top: 72%; transform: scale(0.92); opacity: 1; }
    47%  { left: 48%; top: 72%; transform: scale(0.92); opacity: 1; }
    50%  { left: 48%; top: 72%; transform: scale(-0.92, 0.92); opacity: 1; }
    87%  { left: 28%; top: 73.5%; transform: scale(-0.9, 0.9); opacity: 1; }
    94%  { left: 28%; top: 73.5%; transform: scale(-0.9, 0.9); opacity: 1; }
    97%  { opacity: 0; }
    100% { left: 28%; top: 73.5%; transform: scale(0.9); opacity: 0; }
}

.peasant-wood {
    position: absolute;
    left: 28%;
    top: 41%;
    width: 15px;
    height: 19px;
    will-change: left, top, transform;
    animation: walkPathWood 70s linear infinite 5s;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.6));
}

@keyframes walkPathWood {
    0%   { left: 28%; top: 41%; transform: scale(0.78); opacity: 0; }
    4%   { opacity: 1; }
    40%  { left: 34%; top: 59%; transform: scale(0.9); opacity: 1; }
    47%  { left: 34%; top: 59%; transform: scale(0.9); opacity: 1; }
    50%  { left: 34%; top: 59%; transform: scale(-0.9, 0.9); opacity: 1; }
    87%  { left: 28%; top: 41%; transform: scale(-0.78, 0.78); opacity: 1; }
    94%  { left: 28%; top: 41%; transform: scale(-0.78, 0.78); opacity: 1; }
    97%  { opacity: 0; }
    100% { left: 28%; top: 41%; transform: scale(0.78); opacity: 0; }
}

.peasant-bob { animation: peasantBob 0.95s ease-in-out infinite alternate; }
@keyframes peasantBob {
    0%   { transform: translateY(0) rotate(-1.5deg); }
    100% { transform: translateY(-1.5px) rotate(1.5deg); }
}

.leg-left { transform-origin: 10px 19px; animation: legSwing 0.95s ease-in-out infinite alternate; }
.leg-right { transform-origin: 14px 19px; animation: legSwing 0.95s ease-in-out infinite alternate-reverse; }
@keyframes legSwing {
    0%   { transform: rotate(-14deg); }
    100% { transform: rotate(14deg); }
}

.ox-cart-unit {
    position: absolute;
    left: 77%;
    top: 66%;
    width: 38px;
    height: 18px;
    will-change: left, top, transform;
    animation: moveOxCart 105s linear infinite;
    filter: drop-shadow(0 3px 4px rgba(0,0,0,0.65));
}

.ox-cart-bob { animation: oxBob 1.8s ease-in-out infinite alternate; }
@keyframes oxBob {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-1.2px); }
}

.ox-leg-front { transform-origin: 48px 19px; animation: oxLeg 1.8s ease-in-out infinite alternate; }
.ox-leg-back { transform-origin: 40px 19px; animation: oxLeg 1.8s ease-in-out infinite alternate-reverse; }
@keyframes oxLeg {
    0%   { transform: rotate(-10deg); }
    100% { transform: rotate(10deg); }
}

@keyframes moveOxCart {
    0%   { left: 77%; top: 66%; transform: scale(-0.85, 0.85); opacity: 0; }
    3%   { opacity: 1; }
    40%  { left: 53%; top: 69%; transform: scale(-0.88, 0.88); opacity: 1; }
    47%  { left: 53%; top: 69%; transform: scale(-0.88, 0.88); opacity: 1; }
    50%  { left: 53%; top: 69%; transform: scale(0.88, 0.88); opacity: 1; }
    87%  { left: 77%; top: 66%; transform: scale(0.85, 0.85); opacity: 1; }
    94%  { left: 77%; top: 66%; transform: scale(0.85, 0.85); opacity: 1; }
    97%  { opacity: 0; }
    100% { left: 77%; top: 66%; transform: scale(-0.85, 0.85); opacity: 0; }
}

/* ============================================================
   7. PÉTALES DE CERISIER SAKURA (PRINTEMPS & JOUR)
   ============================================================ */
.atmosphere-sakura-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    transition: opacity 1s ease;
}

.theme-snow .atmosphere-sakura-container,
.theme-rain .atmosphere-sakura-container {
    opacity: 0; /* Pas de fleurs pendant la neige ou la pluie d'automne */
}

.sakura-petal {
    position: absolute;
    width: 9px;
    height: 13px;
    background: radial-gradient(circle at 40% 30%, #fbcfe8 0%, #f472b6 65%, #db2777 100%);
    border-radius: 50% 50% 50% 10% / 60% 60% 40% 40%;
    opacity: 0.8;
    filter: drop-shadow(0 1px 2px rgba(219, 39, 119, 0.35));
    will-change: transform;
}

.p-sakura-1 { top: -5%; left: 15%; animation: fallSakura 16s linear infinite; }
.p-sakura-2 { top: -5%; left: 35%; animation: fallSakura 19s linear infinite 3s; width: 7px; height: 11px; }
.p-sakura-3 { top: -5%; left: 55%; animation: fallSakura 22s linear infinite 7s; }
.p-sakura-4 { top: -5%; left: 75%; animation: fallSakura 17s linear infinite 4s; width: 8px; height: 12px; }
.p-sakura-5 { top: -5%; left: 88%; animation: fallSakura 21s linear infinite 9s; }
.p-sakura-6 { top: -5%; left: 28%; animation: fallSakura 25s linear infinite 12s; width: 6px; height: 9px; }

@keyframes fallSakura {
    0%   { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0; }
    10%  { opacity: 0.85; }
    90%  { opacity: 0.85; }
    100% { transform: translate3d(25vw, 115vh, 0) rotate(540deg); opacity: 0; }
}

/* ============================================================
   8. CHUTE DE NEIGE (HIVER YUKI)
   ============================================================ */
.atmosphere-snow-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    opacity: 0;
    transition: opacity 1.2s ease;
}

.theme-snow .atmosphere-snow-container {
    opacity: 1;
}

.snow-flake {
    position: absolute;
    top: -4%;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 4px #ffffff, 0 0 6px rgba(255, 255, 255, 0.8);
    opacity: 0.9;
    will-change: transform;
    animation: snowFall 12s linear infinite;
}

<?php for ($i = 1; $i <= 26; $i++): 
    $left = ($i * 3.8) % 98;
    $dur = 9 + (($i * 3) % 11);
    $delay = ($i * 0.45) % 8;
    $size = 2.5 + (($i % 4) * 1.2);
    $drift = 10 + (($i * 5) % 25);
?>
.snow-<?= $i ?> {
    left: <?= $left ?>%;
    width: <?= $size ?>px;
    height: <?= $size ?>px;
    animation-duration: <?= $dur ?>s;
    animation-delay: <?= $delay ?>s;
    --snow-drift: <?= $drift ?>vw;
}
<?php endfor; ?>

@keyframes snowFall {
    0% {
        transform: translate3d(0, 0, 0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.95;
    }
    90% {
        opacity: 0.95;
    }
    100% {
        transform: translate3d(var(--snow-drift, 18vw), 110vh, 0) rotate(360deg);
        opacity: 0;
    }
}

/* ============================================================
   9. PLUIE FINE & FEUILLES MOMIJI (AUTOMNE AME)
   ============================================================ */
.atmosphere-rain-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    opacity: 0;
    transition: opacity 1.2s ease;
}

.theme-rain .atmosphere-rain-container {
    opacity: 1;
}

/* Rideau de pluie en diagonale */
.rain-curtain {
    position: absolute;
    inset: -30%;
    background: repeating-linear-gradient(
        110deg,
        rgba(255, 255, 255, 0) 0px,
        rgba(255, 255, 255, 0) 14px,
        rgba(203, 213, 225, 0.35) 15px,
        rgba(203, 213, 225, 0) 16px
    );
    animation: rainRush 0.5s linear infinite;
    opacity: 0.75;
}

@keyframes rainRush {
    0%   { transform: translate(0, 0); }
    100% { transform: translate(-20px, 45px); }
}

/* Feuilles d'érable japonaises Momiji */
.momiji-leaf {
    position: absolute;
    top: -5%;
    width: 12px;
    height: 12px;
    background: radial-gradient(circle at 35% 35%, #ef4444 0%, #b91c1c 70%, #7f1d1d 100%);
    clip-path: polygon(50% 0%, 65% 35%, 100% 35%, 75% 60%, 85% 100%, 50% 75%, 15% 100%, 25% 60%, 0% 35%, 35% 35%);
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.4));
    will-change: transform;
}

.momiji-1 { left: 18%; animation: momijiDrift 14s linear infinite; }
.momiji-2 { left: 38%; animation: momijiDrift 17s linear infinite 2s; width: 10px; height: 10px; background: #ea580c; }
.momiji-3 { left: 58%; animation: momijiDrift 13s linear infinite 5s; width: 14px; height: 14px; }
.momiji-4 { left: 78%; animation: momijiDrift 16s linear infinite 3s; background: #dc2626; }
.momiji-5 { left: 88%; animation: momijiDrift 18s linear infinite 7s; width: 11px; height: 11px; }

@keyframes momijiDrift {
    0%   { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0; }
    10%  { opacity: 0.9; }
    90%  { opacity: 0.9; }
    100% { transform: translate3d(30vw, 115vh, 0) rotate(720deg); opacity: 0; }
}
</style>

<!-- SCRIPT DU MOTEUR MÉTÉO & CYCLE TEMPOREL -->
<script>
(function() {
    const STORAGE_THEME_KEY = 'shogun_weather_theme';
    const overlay = document.getElementById('ruralAtmosphereOverlay');
    const labelEl = document.getElementById('txtAtmosphereLabel');
    const iconEl = document.getElementById('iconAtmosphere');

    const THEME_NAMES = {
        'auto':  { label: 'Auto (Horloge)', icon: 'fa-solid fa-clock text-primary' },
        'day':   { label: 'Plein Jour',    icon: 'fa-solid fa-sun text-warning' },
        'dusk':  { label: 'Crépuscule',    icon: 'fa-solid fa-cloud-sun text-orange' },
        'night': { label: 'Nuit & Lanternes', icon: 'fa-solid fa-moon text-info' },
        'snow':  { label: 'Hiver Enneigé', icon: 'fa-solid fa-snowflake text-cyan' },
        'rain':  { label: 'Pluie & Kōyō',  icon: 'fa-solid fa-cloud-rain text-teal' },
        'off':   { label: 'Mode Zen (Off)',icon: 'fa-solid fa-pause text-muted' }
    };

    function calculateAutoPhase() {
        const hour = new Date().getHours();
        if (hour >= 6 && hour < 9) {
            return 'dawn';
        } else if (hour >= 9 && hour < 17) {
            return 'day';
        } else if (hour >= 17 && hour < 20) {
            return 'dusk';
        } else {
            return 'night';
        }
    }

    function applyWeatherTheme(themeKey) {
        if (!overlay) return;

        // Retirer toutes les classes de thème
        overlay.classList.remove('theme-day', 'theme-dawn', 'theme-dusk', 'theme-night', 'theme-snow', 'theme-rain', 'is-disabled');

        if (themeKey === 'off') {
            overlay.classList.add('is-disabled');
        } else if (themeKey === 'auto') {
            const phase = calculateAutoPhase();
            overlay.classList.add('theme-' + phase);
        } else {
            overlay.classList.add('theme-' + themeKey);
        }

        // Mettre à jour l'intitulé du bouton et l'icône
        const meta = THEME_NAMES[themeKey] || THEME_NAMES['auto'];
        if (labelEl) {
            labelEl.textContent = 'Météo : ' + meta.label;
        }
        if (iconEl) {
            iconEl.className = meta.icon + ' me-1';
        }

        // Mettre à jour la classe active dans le dropdown
        document.querySelectorAll('.weather-dropdown-item').forEach(el => {
            if (el.dataset.theme === themeKey) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
    }

    // Récupérer le réglage mémorisé ou 'auto' par défaut
    const savedTheme = localStorage.getItem(STORAGE_THEME_KEY) || 'auto';
    applyWeatherTheme(savedTheme);

    // Fonction globale pour changer d'ambiance
    window.setShogunWeather = function(newTheme) {
        localStorage.setItem(STORAGE_THEME_KEY, newTheme);
        applyWeatherTheme(newTheme);
    };

    // Rafraîchir la phase automatique toutes les 5 minutes si mode auto actif
    setInterval(function() {
        const currentTheme = localStorage.getItem(STORAGE_THEME_KEY) || 'auto';
        if (currentTheme === 'auto') {
            applyWeatherTheme('auto');
        }
    }, 300000);
})();
</script>
