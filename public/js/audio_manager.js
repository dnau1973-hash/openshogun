/**
 * ShogunAudioManager — Gestionnaire d'ambiances sonores immersives féodales pour OpenShogun
 */
class ShogunAudioManager {
    constructor() {
        this.audio = null;
        this.currentTrack = null;
        this.isEnabled = localStorage.getItem('shogun_audio_enabled') === 'true';
        this.volume = parseFloat(localStorage.getItem('shogun_audio_volume') || '0.35');
        this.fadeInterval = null;

        // Détection de la page actuelle depuis l'URL
        const params = new URLSearchParams(window.location.search);
        this.currentPage = params.get('page') || 'resources';

        // Cartographie complète des 5 ambiances sonores selon la page active
        this.pageTracks = {
            // 🏯 Cité Castrale & Bâtiments
            'city': { url: '/public/assets/audio/ambient_city.mp3', title: 'Cité Castrale' },
            'building': { url: '/public/assets/audio/ambient_city.mp3', title: 'Chantier Urbain' },

            // 🌾 Terroir, Parcelles & Vue Générale
            'resources': { url: '/public/assets/audio/ambient_terroir.mp3', title: 'Terroir & Rizières' },
            'field': { url: '/public/assets/audio/ambient_terroir.mp3', title: 'Parcelles du Domaine' },

            // 🗾 Carte Stratégique de l'Archipel
            'map': { url: '/public/assets/audio/ambient_map.mp3', title: 'Carte des Provinces' },
            'galaxy': { url: '/public/assets/audio/ambient_map.mp3', title: 'Carte des Provinces' },

            // ⚔️ Dojos Militaires, Engins & Flottes
            'barracks': { url: '/public/assets/audio/ambient_martial.mp3', title: 'Dojo & Caserne' },
            'shipyard': { url: '/public/assets/audio/ambient_martial.mp3', title: 'Atelier de Siège' },
            'fleet': { url: '/public/assets/audio/ambient_martial.mp3', title: 'Marche Militaire' },

            // ⛩️ Sanctuaire, Recherches, Donjon & Pavillon du Héros
            'research': { url: '/public/assets/audio/ambient_sanctuary.mp3', title: 'Sanctuaire Shintō' },
            'castle': { url: '/public/assets/audio/ambient_sanctuary.mp3', title: 'Donjon Sacré' },
            'hero': { url: '/public/assets/audio/ambient_sanctuary.mp3', title: 'Pavillon du Héros' },
        };

        this.init();
    }

    getCurrentTrackInfo() {
        return this.pageTracks[this.currentPage] || { url: '/public/assets/audio/ambient_terroir.mp3', title: 'Chronique Féodale' };
    }

    init() {
        const trackInfo = this.getCurrentTrackInfo();
        this.currentTrack = trackInfo.url;

        this.audio = new Audio(trackInfo.url);
        this.audio.loop = true;
        this.audio.volume = this.isEnabled ? this.volume : 0;
        this.audio.preload = 'auto';

        // Si le son était activé lors de sessions précédentes,
        // respecter la politique du navigateur en déclenchant au premier geste utilisateur
        if (this.isEnabled) {
            const startOnInteraction = () => {
                this.play(true);
                document.removeEventListener('click', startOnInteraction);
                document.removeEventListener('keydown', startOnInteraction);
            };
            document.addEventListener('click', startOnInteraction, { once: true });
            document.addEventListener('keydown', startOnInteraction, { once: true });
        }

        this.updateUi();
    }

    toggle() {
        if (!this.audio) {
            const trackInfo = this.getCurrentTrackInfo();
            this.currentTrack = trackInfo.url;
            this.audio = new Audio(trackInfo.url);
            this.audio.loop = true;
            this.audio.volume = this.volume;
        }

        if (this.isEnabled && !this.audio.paused) {
            this.pause();
        } else {
            this.play();
        }
    }

    play(isAuto = false) {
        if (!this.audio) return;

        this.isEnabled = true;
        localStorage.setItem('shogun_audio_enabled', 'true');

        // Démarrage progressif (fade in)
        this.audio.volume = 0;
        const playPromise = this.audio.play();

        if (playPromise !== undefined) {
            playPromise.then(() => {
                this.fadeIn();
                this.updateUi();
            }).catch(err => {
                if (!isAuto) {
                    console.log("Audio en attente d'interaction utilisateur :", err);
                    this.updateUi();
                }
            });
        }
    }

    pause() {
        if (!this.audio) return;
        this.isEnabled = false;
        localStorage.setItem('shogun_audio_enabled', 'false');

        this.fadeOut(() => {
            this.audio.pause();
            this.updateUi();
        });
    }

    setVolume(newVol) {
        this.volume = Math.max(0, Math.min(1, parseFloat(newVol)));
        localStorage.setItem('shogun_audio_volume', this.volume.toString());

        if (this.audio && this.isEnabled && !this.audio.paused) {
            this.audio.volume = this.volume;
        }

        this.updateUi();
    }

    fadeIn(targetVol = null, duration = 1200) {
        if (!this.audio) return;
        clearInterval(this.fadeInterval);
        const finalVol = targetVol !== null ? targetVol : this.volume;
        const step = finalVol / (duration / 50);

        this.fadeInterval = setInterval(() => {
            if (!this.audio) {
                clearInterval(this.fadeInterval);
                return;
            }
            if (this.audio.volume + step >= finalVol) {
                this.audio.volume = finalVol;
                clearInterval(this.fadeInterval);
            } else {
                this.audio.volume += step;
            }
        }, 50);
    }

    fadeOut(callback = null, duration = 600) {
        if (!this.audio) {
            if (callback) callback();
            return;
        }
        clearInterval(this.fadeInterval);
        const step = this.audio.volume / (duration / 50);

        this.fadeInterval = setInterval(() => {
            if (!this.audio || this.audio.volume - step <= 0) {
                if (this.audio) this.audio.volume = 0;
                clearInterval(this.fadeInterval);
                if (callback) callback();
            } else {
                this.audio.volume -= step;
            }
        }, 50);
    }

    updateUi() {
        const iconEl = document.getElementById('shogun-audio-icon');
        const btnEl = document.getElementById('shogun-audio-btn');
        const isPlaying = this.audio && !this.audio.paused && this.isEnabled;
        const trackInfo = this.getCurrentTrackInfo();

        if (iconEl) {
            if (isPlaying) {
                iconEl.textContent = '🔊';
                iconEl.classList.add('audio-playing');
            } else {
                iconEl.textContent = '🔇';
                iconEl.classList.remove('audio-playing');
            }
        }

        if (btnEl) {
            btnEl.title = isPlaying 
                ? `Ambiance « ${trackInfo.title} » active (${Math.round(this.volume * 100)}%) — Cliquer pour couper` 
                : `Ambiance « ${trackInfo.title} » coupée — Cliquer pour activer`;
            btnEl.classList.toggle('active', isPlaying);
        }
    }
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    window.shogunAudio = new ShogunAudioManager();
});
