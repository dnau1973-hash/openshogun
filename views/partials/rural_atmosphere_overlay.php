<?php
/**
 * Calque Atmosphérique & Vie Rurale Vivante (OpenShogun)
 * Animations SVG / CSS superposées 60 FPS :
 * 1. Brume matinale dérivant le long des crêtes et montagnes
 * 2. Vol gracieux de hérons / grues du Japon traversant le ciel
 * 3. Volutes de fumée de bois et d'encens s'élevant des toits et du sanctuaire
 * 4. Paysans et charrette à bœuf sillonnant les sentiers
 * 5. Pétales de cerisier (Sakura) flottant au vent
 */
?>

<!-- CONTENEUR DU CALQUE ATMOSPHÉRIQUE & VIE DU TERROIR -->
<div class="rural-atmosphere-overlay" id="ruralAtmosphereOverlay" aria-hidden="true">

    <!-- 1. NAPPES DE BRUME MATINALE SUR L'HORIZON (DRIFT ATMOSPHÉRIQUE) -->
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

    <!-- 2. VOL PÉRIODIQUE DE HÉRONS / GRUES DU JAPON (CRANES FLOCK) -->
    <div class="atmosphere-cranes-flock">
        <!-- Héron Meneur -->
        <div class="crane-bird crane-lead">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <!-- Corps et cou -->
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.9" />
                <!-- Bec -->
                <path d="M31,6 L34,7 L30,8 Z" fill="#ea580c" />
                <!-- Ailes battantes -->
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
        <!-- Héron Ailier 1 -->
        <div class="crane-bird crane-follower-1">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.85" />
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
        <!-- Héron Ailier 2 -->
        <div class="crane-bird crane-follower-2">
            <svg viewBox="0 0 32 20" class="crane-svg">
                <path d="M12,10 Q16,8 24,10 Q28,9 31,6 Q27,11 20,12 Q14,13 8,11 Z" fill="#ffffff" opacity="0.8" />
                <path class="crane-wing-left" d="M14,10 Q16,1 21,0 Q18,6 16,10 Z" fill="#f8fafc" />
                <path class="crane-wing-right" d="M12,10 Q14,19 19,20 Q16,14 14,10 Z" fill="#cbd5e1" opacity="0.75" />
            </svg>
        </div>
    </div>

    <!-- 3. VOLUTES DE FUMÉE & ENCENS SACRÉ (SMOKE PUFFS) -->
    <!-- Fumée 1 : Donjon Tenshu -->
    <div class="smoke-emitter smoke-tenshu" style="left: 49%; top: 12%;">
        <div class="smoke-particle p1"></div>
        <div class="smoke-particle p2"></div>
        <div class="smoke-particle p3"></div>
    </div>

    <!-- Fumée 2 : Chaume du Hameau Central -->
    <div class="smoke-emitter smoke-village" style="left: 48.5%; top: 51%;">
        <div class="smoke-particle p1"></div>
        <div class="smoke-particle p2"></div>
        <div class="smoke-particle p3"></div>
    </div>

    <!-- Fumée 3 : Encens Sacré du Sanctuaire Shintō -->
    <div class="smoke-emitter smoke-shrine" style="left: 85%; top: 73.5%;">
        <div class="smoke-particle p1 incense-particle"></div>
        <div class="smoke-particle p2 incense-particle"></div>
        <div class="smoke-particle p3 incense-particle"></div>
    </div>

    <!-- 4. PAYSANS ET CHARRETTE SUR LES SENTIERS DE TERRE -->
    <div class="atmosphere-paths-life">
        <!-- Paysan aux paniers de récolte (Sentier des Rizières -> Hameau) -->
        <div class="peasant-walker peasant-rice">
            <div class="peasant-bob">
                <svg viewBox="0 0 24 28" class="peasant-svg">
                    <!-- Chapeau de paille Kasa conique -->
                    <polygon points="12,2 4,8 20,8" fill="#ca8a04" />
                    <!-- Tête -->
                    <circle cx="12" cy="9.5" r="2.5" fill="#e2b17a" />
                    <!-- Corps en tunique indigo -->
                    <path d="M10,12 L14,12 L15,19 L9,19 Z" fill="#1e3a8a" />
                    <!-- Balancier de bois Tenbinbo -->
                    <line x1="2" y1="13" x2="22" y2="13" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <!-- Panier gauche -->
                    <rect x="1" y="14" width="4" height="4" rx="1" fill="#ca8a04" />
                    <line x1="3" y1="13" x2="3" y2="14" stroke="#78350f" stroke-width="0.8" />
                    <!-- Panier droit -->
                    <rect x="19" y="14" width="4" height="4" rx="1" fill="#ca8a04" />
                    <line x1="21" y1="13" x2="21" y2="14" stroke="#78350f" stroke-width="0.8" />
                    <!-- Jambes qui marchent -->
                    <line class="leg-left" x1="10" y1="19" x2="9" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <line class="leg-right" x1="14" y1="19" x2="15" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Deuxième paysan portant un fardeau de bois descendant de la forêt -->
        <div class="peasant-walker peasant-wood">
            <div class="peasant-bob">
                <svg viewBox="0 0 24 28" class="peasant-svg">
                    <!-- Chapeau Kasa -->
                    <polygon points="12,2 4,8 20,8" fill="#ca8a04" />
                    <!-- Tête -->
                    <circle cx="12" cy="9.5" r="2.5" fill="#e2b17a" />
                    <!-- Tunique terre cuite -->
                    <path d="M10,12 L14,12 L15,19 L9,19 Z" fill="#9a3412" />
                    <!-- Fagot de bois sur le dos -->
                    <rect x="5" y="11" width="5" height="7" rx="1" fill="#713f12" />
                    <!-- Jambes -->
                    <line class="leg-left" x1="10" y1="19" x2="9" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                    <line class="leg-right" x1="14" y1="19" x2="15" y2="25" stroke="#78350f" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Chariot tiré par un bœuf sur la voie centrale -->
        <div class="ox-cart-unit">
            <div class="ox-cart-bob">
                <svg viewBox="0 0 54 26" class="ox-cart-svg">
                    <!-- Bœuf (tête et corps) -->
                    <ellipse cx="44" cy="15" rx="7" ry="5" fill="#334155" />
                    <circle cx="49" cy="12" r="3.5" fill="#1e293b" />
                    <!-- Cornes -->
                    <path d="M48,10 Q50,7 53,7" stroke="#e2e8f0" stroke-width="1.2" fill="none" />
                    <!-- Pattes du bœuf -->
                    <line class="ox-leg-front" x1="48" y1="19" x2="49" y2="25" stroke="#1e293b" stroke-width="1.8" stroke-linecap="round" />
                    <line class="ox-leg-back" x1="40" y1="19" x2="39" y2="25" stroke="#1e293b" stroke-width="1.8" stroke-linecap="round" />
                    <!-- Attelage / Brancards -->
                    <line x1="28" y1="16" x2="40" y2="15" stroke="#78350f" stroke-width="1.5" />
                    <!-- Chariot en bois de cèdre -->
                    <rect x="8" y="9" width="20" height="9" rx="1" fill="#854d0e" stroke="#581c87" stroke-width="0.5" />
                    <!-- Chargement de sacs de riz et ballots -->
                    <ellipse cx="14" cy="8" rx="4" ry="2.5" fill="#fef08a" opacity="0.9" />
                    <ellipse cx="21" cy="7.5" rx="4.5" ry="2.8" fill="#fef08a" opacity="0.9" />
                    <!-- Roue du chariot avec rayons -->
                    <circle cx="18" cy="18" r="5" fill="#451a03" stroke="#ca8a04" stroke-width="1.2" />
                    <circle cx="18" cy="18" r="1.5" fill="#ca8a04" />
                </svg>
            </div>
        </div>
    </div>

    <!-- 5. CHUTE DOUCE DE PÉTALES DE CERISIER (SAKURA PETALS) -->
    <div class="atmosphere-sakura-container">
        <div class="sakura-petal p-sakura-1"></div>
        <div class="sakura-petal p-sakura-2"></div>
        <div class="sakura-petal p-sakura-3"></div>
        <div class="sakura-petal p-sakura-4"></div>
        <div class="sakura-petal p-sakura-5"></div>
        <div class="sakura-petal p-sakura-6"></div>
    </div>

</div>

<!-- STYLES CSS DÉDIÉS DE L'AMBIANCE VIVANTE (60 FPS MATÉRIEL) -->
<style>
/* Conteneur principal superposé sur l'illustration 16:9 */
.rural-atmosphere-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 10;
    overflow: hidden;
    user-select: none;
    transition: opacity 0.4s ease;
}

/* État désactivé via le bouton zen */
.rural-atmosphere-overlay.is-disabled {
    opacity: 0 !important;
    display: none !important;
}

/* ────────────────────────────────────────────────────────────
   1. BRUME MATINALE (HORIZON MIST DRIFT)
   ──────────────────────────────────────────────────────────── */
.atmosphere-mist-wrapper {
    position: absolute;
    top: 5%;
    left: 0;
    width: 100%;
    height: 35%;
    overflow: hidden;
    pointer-events: none;
    opacity: 0.75;
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

/* ────────────────────────────────────────────────────────────
   2. VOL DES HÉRONS / GRUES (FLYING CRANES)
   ──────────────────────────────────────────────────────────── */
.atmosphere-cranes-flock {
    position: absolute;
    top: 8%;
    left: -12%;
    width: 120px;
    height: 60px;
    will-change: transform;
    animation: flyAcrossSky 38s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}

.crane-bird {
    position: absolute;
    width: 24px;
    height: 16px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));
}

.crane-lead {
    left: 45px;
    top: 10px;
    width: 28px;
    height: 18px;
}

.crane-follower-1 {
    left: 10px;
    top: 0px;
    width: 22px;
    height: 14px;
    opacity: 0.9;
}

.crane-follower-2 {
    left: 0px;
    top: 26px;
    width: 20px;
    height: 13px;
    opacity: 0.8;
}

.crane-wing-left {
    transform-origin: 15px 10px;
    animation: craneFlap 0.75s ease-in-out infinite alternate;
}

.crane-wing-right {
    transform-origin: 14px 10px;
    animation: craneFlap 0.75s ease-in-out infinite alternate-reverse;
}

@keyframes craneFlap {
    0%   { transform: scaleY(1) rotate(0deg); }
    50%  { transform: scaleY(0.2) rotate(-15deg); }
    100% { transform: scaleY(-0.9) rotate(25deg); }
}

@keyframes flyAcrossSky {
    0% {
        transform: translate3d(-10vw, 4vh, 0) scale(0.65);
        opacity: 0;
    }
    5% {
        opacity: 1;
    }
    45% {
        transform: translate3d(60vw, -1vh, 0) scale(0.9);
        opacity: 1;
    }
    60% {
        transform: translate3d(115vw, -6vh, 0) scale(1.1);
        opacity: 0;
    }
    100% {
        transform: translate3d(115vw, -6vh, 0) scale(1.1);
        opacity: 0;
    }
}

/* ────────────────────────────────────────────────────────────
   3. VOLUTES DE FUMÉE & ENCENS (SMOKE & INCENSE)
   ──────────────────────────────────────────────────────────── */
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

.smoke-particle.p2 {
    animation-delay: 1.1s;
    width: 10px;
    height: 10px;
}

.smoke-particle.p3 {
    animation-delay: 2.2s;
    width: 7px;
    height: 7px;
}

.incense-particle {
    background: radial-gradient(circle, rgba(254,240,138,0.7) 0%, rgba(253,186,116,0.35) 50%, rgba(255,255,255,0) 100%) !important;
    animation-duration: 4.2s !important;
}

@keyframes riseSmoke {
    0% {
        transform: translate3d(-50%, 0, 0) scale(0.5);
        opacity: 0;
    }
    20% {
        opacity: 0.65;
    }
    70% {
        opacity: 0.35;
    }
    100% {
        transform: translate3d(-30%, -35px, 0) scale(2.2);
        opacity: 0;
    }
}

/* ────────────────────────────────────────────────────────────
   4. PAYSANS ET CHARRETTES (RURAL LIFE ON PATHS)
   ──────────────────────────────────────────────────────────── */
.atmosphere-paths-life {
    position: absolute;
    inset: 0;
}

/* Paysan des rizières (Monte depuis la rizière sud-ouest vers le village) */
.peasant-rice {
    position: absolute;
    left: 20%;
    top: 79%;
    width: 16px;
    height: 20px;
    animation: walkPathRice 32s ease-in-out infinite;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.6));
}

@keyframes walkPathRice {
    0% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0;
    }
    5% { opacity: 1; }
    45% {
        transform: translate3d(14vw, -8vh, 0) scale(0.92);
        opacity: 1;
    }
    50% {
        transform: translate3d(16vw, -9vh, 0) scale(0.9);
        opacity: 0;
    }
    55% {
        transform: translate3d(16vw, -9vh, 0) scale(-0.9, 0.9);
        opacity: 0;
    }
    60% { opacity: 1; }
    95% {
        transform: translate3d(0, 0, 0) scale(-1, 1);
        opacity: 1;
    }
    100% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0;
    }
}

/* Paysan du bois (Descend depuis la forêt nord-ouest vers le village) */
.peasant-wood {
    position: absolute;
    left: 24%;
    top: 36%;
    width: 15px;
    height: 19px;
    animation: walkPathWood 28s ease-in-out infinite 6s;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.6));
}

@keyframes walkPathWood {
    0% {
        transform: translate3d(0, 0, 0) scale(0.85);
        opacity: 0;
    }
    5% { opacity: 1; }
    45% {
        transform: translate3d(11vw, 6vh, 0) scale(0.95);
        opacity: 1;
    }
    50% {
        transform: translate3d(12vw, 7vh, 0) scale(1);
        opacity: 0;
    }
    55% {
        transform: translate3d(12vw, 7vh, 0) scale(-1, 1);
        opacity: 0;
    }
    60% { opacity: 1; }
    95% {
        transform: translate3d(0, 0, 0) scale(-0.85, 0.85);
        opacity: 1;
    }
    100% {
        transform: translate3d(0, 0, 0) scale(0.85);
        opacity: 0;
    }
}

/* Balancement de marche du paysan */
.peasant-bob {
    animation: peasantBob 0.65s ease-in-out infinite alternate;
}

@keyframes peasantBob {
    0%   { transform: translateY(0) rotate(-2deg); }
    100% { transform: translateY(-2px) rotate(2deg); }
}

.leg-left {
    transform-origin: 10px 19px;
    animation: legSwing 0.65s ease-in-out infinite alternate;
}

.leg-right {
    transform-origin: 14px 19px;
    animation: legSwing 0.65s ease-in-out infinite alternate-reverse;
}

@keyframes legSwing {
    0%   { transform: rotate(-18deg); }
    100% { transform: rotate(18deg); }
}

/* Chariot à Bœuf (Traverse doucement la route de plaine de droite à gauche) */
.ox-cart-unit {
    position: absolute;
    left: 62%;
    top: 67%;
    width: 38px;
    height: 18px;
    animation: moveOxCart 48s linear infinite;
    filter: drop-shadow(0 3px 4px rgba(0,0,0,0.65));
}

.ox-cart-bob {
    animation: oxBob 1.2s ease-in-out infinite alternate;
}

@keyframes oxBob {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-1.5px); }
}

.ox-leg-front {
    transform-origin: 48px 19px;
    animation: oxLeg 1.2s ease-in-out infinite alternate;
}

.ox-leg-back {
    transform-origin: 40px 19px;
    animation: oxLeg 1.2s ease-in-out infinite alternate-reverse;
}

@keyframes oxLeg {
    0%   { transform: rotate(-12deg); }
    100% { transform: rotate(12deg); }
}

@keyframes moveOxCart {
    0% {
        transform: translate3d(12vw, 4vh, 0) scale(-0.85, 0.85);
        opacity: 0;
    }
    5% { opacity: 1; }
    50% {
        transform: translate3d(-6vw, -3vh, 0) scale(-0.85, 0.85);
        opacity: 1;
    }
    55% { opacity: 0; }
    60% {
        transform: translate3d(-6vw, -3vh, 0) scale(0.85, 0.85);
        opacity: 0;
    }
    65% { opacity: 1; }
    95% {
        transform: translate3d(12vw, 4vh, 0) scale(0.85, 0.85);
        opacity: 1;
    }
    100% {
        transform: translate3d(12vw, 4vh, 0) scale(0.85, 0.85);
        opacity: 0;
    }
}

/* ────────────────────────────────────────────────────────────
   5. PÉTALES DE CERISIER SAKURA FLOTTANTS
   ──────────────────────────────────────────────────────────── */
.atmosphere-sakura-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.sakura-petal {
    position: absolute;
    width: 8px;
    height: 12px;
    background: radial-gradient(circle at 35% 35%, #fbcfe8 0%, #f472b6 70%, #db2777 100%);
    border-radius: 80% 0 80% 40% / 80% 0 80% 40%;
    opacity: 0.75;
    filter: drop-shadow(0 1px 2px rgba(219,39,119,0.3));
    will-change: transform;
}

.p-sakura-1 { top: -10%; left: 15%; animation: fallSakura 16s linear infinite; }
.p-sakura-2 { top: -10%; left: 35%; animation: fallSakura 21s linear infinite 4s; width: 6px; height: 9px; }
.p-sakura-3 { top: -10%; left: 55%; animation: fallSakura 18s linear infinite 8s; width: 9px; height: 13px; }
.p-sakura-4 { top: -10%; left: 75%; animation: fallSakura 24s linear infinite 2s; width: 7px; height: 10px; }
.p-sakura-5 { top: -10%; left: 90%; animation: fallSakura 19s linear infinite 11s; width: 8px; height: 11px; }
.p-sakura-6 { top: -10%; left: 5%;  animation: fallSakura 22s linear infinite 14s; width: 6px; height: 8px; }

@keyframes fallSakura {
    0% {
        transform: translate3d(0, 0, 0) rotate(0deg);
        opacity: 0;
    }
    10% { opacity: 0.8; }
    90% { opacity: 0.7; }
    100% {
        transform: translate3d(25vw, 115vh, 0) rotate(540deg);
        opacity: 0;
    }
}
</style>

<!-- SCRIPT DE GESTION DU MODE AMBIANCE ZEN -->
<script>
(function() {
    const STORAGE_KEY = 'shogun_atmosphere_enabled';
    const overlay = document.getElementById('ruralAtmosphereOverlay');
    const toggleBtn = document.getElementById('btnToggleAtmosphere');

    function updateAtmosphereState(isEnabled) {
        if (!overlay) return;
        if (isEnabled) {
            overlay.classList.remove('is-disabled');
            if (toggleBtn) {
                toggleBtn.classList.remove('btn-outline-secondary');
                toggleBtn.classList.add('btn-dark', 'text-warning');
                toggleBtn.innerHTML = '<i class="fa-solid fa-wind me-1 text-warning"></i>Ambiance Vivante : On';
            }
        } else {
            overlay.classList.add('is-disabled');
            if (toggleBtn) {
                toggleBtn.classList.remove('btn-dark', 'text-warning');
                toggleBtn.classList.add('btn-outline-secondary');
                toggleBtn.innerHTML = '<i class="fa-solid fa-wind me-1 text-muted"></i>Ambiance : Off';
            }
        }
    }

    // Récupérer l'état initial (activé par défaut)
    const savedState = localStorage.getItem(STORAGE_KEY);
    const initialEnabled = (savedState === null) ? true : (savedState === '1');
    updateAtmosphereState(initialEnabled);

    // Fonction globale accessible depuis le bouton
    window.toggleRuralAtmosphere = function() {
        const currentState = !overlay.classList.contains('is-disabled');
        const newState = !currentState;
        localStorage.setItem(STORAGE_KEY, newState ? '1' : '0');
        updateAtmosphereState(newState);
    };
})();
</script>
