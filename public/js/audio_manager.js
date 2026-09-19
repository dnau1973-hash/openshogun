/**
 * ShogunAudioManager — Gestionnaire d'ambiances sonores immersives féodales
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

        // Association des pistes d'ambiance selon la page
        this.pageTracks = {
            'city': '/public/assets/audio/ambient_city.mp3',
            'building': '/public/assets/audio/ambient_city.mp3',
            // Extensions futures :
            // 'resources': '/public/assets/audio/ambient_terroir.mp3',
            // 'map': '/public/assets/audio/ambient_map.mp3',
        };

        this.init();
    }

    init() {
        const trackUrl = this.pageTracks[this.currentPage];
        if (!trackUrl) {
            this.updateUi();
            return;
        }

        this.currentTrack = trackUrl;
        this.audio = new Audio(trackUrl);
        this.audio.loop = true;
        this.audio.volume = this.isEnabled ? this.volume : 0;
        this.audio.preload = 'auto';

        // Si l'utilisateur avait activé l'audio lors d'une visite précédente,
        // les navigateurs bloquent l'autoplay tant qu'il n'y a pas eu un premier clic.
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
            // Si pas de piste sur cette page, initialiser la piste par défaut
            const trackUrl = this.pageTracks[this.currentPage] || '/public/assets/audio/ambient_city.mp3';
            this.currentTrack = trackUrl;
            this.audio = new Audio(trackUrl);
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

        // Fondu sonore à l'entrée
        this.audio.volume = 0;
        const playPromise = this.audio.play();

        if (playPromise !== undefined) {
            playPromise.then(() => {
                this.fadeIn();
                this.updateUi();
            }).catch(err => {
                console.log("Lecture audio en attente d'interaction utilisateur :", err);
                if (!isAuto) {
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

        const volText = document.getElementById('shogun-audio-vol-text');
        if (volText) {
            volText.textContent = Math.round(this.volume * 100) + '%';
        }
        const volSlider = document.getElementById('shogun-audio-vol');
        if (volSlider) {
            volSlider.value = Math.round(this.volume * 100);
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
        const volIcon = document.getElementById('audio-vol-icon');
        const isPlaying = this.audio && !this.audio.paused && this.isEnabled;

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
                ? `Ambiance Féodale Active (${Math.round(this.volume * 100)}%) — Cliquer pour couper` 
                : 'Ambiance Féodale Coupée — Cliquer pour activer';
            btnEl.classList.toggle('active', isPlaying);
        }

        if (volIcon) {
            volIcon.textContent = this.volume > 0.5 ? '🔊' : (this.volume > 0 ? '🔉' : '🔇');
        }
    }
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    window.shogunAudio = new ShogunAudioManager();
});
