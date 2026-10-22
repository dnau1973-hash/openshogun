/**
 * ShogunAudioManager — Pavillon Musical & Gestionnaire de Playlist Féodale pour OpenShogun
 * Gère la lecture des mélodies traditionnelles (Shakuhachi, Shinobue, Koto, Taiko),
 * les modes de lecture (Playlist continue, Ambiance par domaine, Boucle, Aléatoire),
 * ainsi que l'interface du lecteur interactif dans le bandeau supérieur.
 */
class ShogunAudioManager {
    constructor() {
        this.audio = null;
        this.fadeInterval = null;

        // Préférences utilisateur persistantes
        this.isEnabled = localStorage.getItem('shogun_audio_enabled') === 'true';
        this.volume = parseFloat(localStorage.getItem('shogun_audio_volume') || '0.35');
        // Modes : 'playlist' (enchaînement continu), 'page' (selon la province/page), 'loop' (morceau unique répété), 'random' (aléatoire)
        this.mode = localStorage.getItem('shogun_audio_mode') || 'playlist';

        // Détection de la page active depuis l'URL
        const params = new URLSearchParams(window.location.search);
        this.currentPage = params.get('page') || 'resources';

        // 🎋 Catalogue des ambiances féodales d'OpenShogun
        this.tracks = [
            {
                id: 'terroir',
                title: 'Terroir & Rizières',
                subtitle: 'Flûte Shinobue & Koto champêtre',
                tag: 'Shinobue & Koto',
                icon: 'fa-solid fa-wheat-awn',
                iconColor: '#10b981',
                badgeClass: 'bg-green-lt text-green',
                url: '/public/assets/audio/ambient_terroir.mp3',
                oggUrl: '/public/assets/audio/ambient_terroir.ogg',
                pages: ['resources', 'field']
            },
            {
                id: 'city',
                title: 'Cité Castrale',
                subtitle: 'Koto impérial, Shakuhachi & carillons de temple',
                tag: 'Koto & Shakuhachi',
                icon: 'fa-solid fa-torii-gate',
                iconColor: '#eab308',
                badgeClass: 'bg-yellow-lt text-warning',
                url: '/public/assets/audio/ambient_city.mp3',
                oggUrl: '/public/assets/audio/ambient_city.ogg',
                pages: ['city', 'building']
            },
            {
                id: 'map',
                title: 'Carte des Provinces',
                subtitle: 'Vent des steppes & Flûte féodale d\'exploration',
                tag: 'Flûte & Vents',
                icon: 'fa-solid fa-map-location-dot',
                iconColor: '#06b6d4',
                badgeClass: 'bg-cyan-lt text-cyan',
                url: '/public/assets/audio/ambient_map.mp3',
                oggUrl: '/public/assets/audio/ambient_map.ogg',
                pages: ['map', 'galaxy']
            },
            {
                id: 'martial',
                title: 'Dojo & Marche Guerrière',
                subtitle: 'Tambours Taiko rythmés & souffle martial des armes',
                tag: 'Taiko & Dojos',
                icon: 'fa-solid fa-shield-halved',
                iconColor: '#ef4444',
                badgeClass: 'bg-red-lt text-red',
                url: '/public/assets/audio/ambient_martial.mp3',
                oggUrl: '/public/assets/audio/ambient_martial.ogg',
                pages: ['barracks', 'shipyard', 'fleet']
            },
            {
                id: 'sanctuary',
                title: 'Sanctuaire Shintō & Donjon',
                subtitle: 'Shakuhachi profond des moines Komusō & recueillement',
                tag: 'Shakuhachi Zen',
                icon: 'fa-solid fa-om',
                iconColor: '#a855f7',
                badgeClass: 'bg-purple-lt text-purple',
                url: '/public/assets/audio/ambient_sanctuary.mp3',
                oggUrl: '/public/assets/audio/ambient_sanctuary.ogg',
                pages: ['research', 'castle', 'hero']
            }
        ];

        // Déterminer la piste initiale
        this.currentIndex = this.determineInitialTrackIndex();

        this.init();
    }

    determineInitialTrackIndex() {
        const savedTrackId = localStorage.getItem('shogun_audio_track_id');

        if (this.mode === 'page') {
            const pageIndex = this.tracks.findIndex(t => t.pages.includes(this.currentPage));
            if (pageIndex !== -1) return pageIndex;
        }

        if (savedTrackId) {
            const idx = this.tracks.findIndex(t => t.id === savedTrackId);
            if (idx !== -1) return idx;
        }

        const pageIdx = this.tracks.findIndex(t => t.pages.includes(this.currentPage));
        return pageIdx !== -1 ? pageIdx : 0;
    }

    getCurrentTrack() {
        return this.tracks[this.currentIndex] || this.tracks[0];
    }

    init() {
        this.loadAudioTrack(this.currentIndex, false);
        this.renderPlaylistUi();
        this.attachEventListeners();

        // Autoplay policy des navigateurs : démarrer dès la première interaction
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

    loadAudioTrack(index, autoplay = false) {
        this.currentIndex = (index + this.tracks.length) % this.tracks.length;
        const track = this.getCurrentTrack();
        localStorage.setItem('shogun_audio_track_id', track.id);

        if (this.audio) {
            this.audio.pause();
            this.audio.removeAttribute('src');
            this.audio.load();
        }

        this.audio = new Audio(track.url);
        this.audio.loop = (this.mode === 'loop');
        this.audio.volume = this.isEnabled ? this.volume : 0;
        this.audio.preload = 'auto';

        // Événement fin de morceau (enchaînement de la playlist)
        this.audio.addEventListener('ended', () => {
            this.handleTrackEnded();
        });

        // Progression temporelle
        this.audio.addEventListener('timeupdate', () => {
            this.updateScrubber();
        });

        this.audio.addEventListener('loadedmetadata', () => {
            this.updateScrubber();
        });

        if (autoplay && this.isEnabled) {
            this.play();
        } else {
            this.updateUi();
        }
    }

    handleTrackEnded() {
        if (this.mode === 'loop') {
            this.play();
            return;
        }

        if (this.mode === 'random') {
            let nextIdx = Math.floor(Math.random() * this.tracks.length);
            if (nextIdx === this.currentIndex && this.tracks.length > 1) {
                nextIdx = (nextIdx + 1) % this.tracks.length;
            }
            this.loadAudioTrack(nextIdx, true);
            return;
        }

        if (this.mode === 'page') {
            // En mode ambiance de province, rejouer la piste de la page en boucle
            this.play();
            return;
        }

        // Mode 'playlist' : passer à la piste suivante en boucle continue
        this.nextTrack(true);
    }

    toggle() {
        if (this.isEnabled && this.audio && !this.audio.paused) {
            this.pause();
        } else {
            this.play();
        }
    }

    play(isAuto = false) {
        if (!this.audio) {
            this.loadAudioTrack(this.currentIndex, false);
        }

        this.isEnabled = true;
        localStorage.setItem('shogun_audio_enabled', 'true');

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

    playTrack(index) {
        this.loadAudioTrack(index, true);
    }

    nextTrack(autoplay = true) {
        const nextIdx = (this.currentIndex + 1) % this.tracks.length;
        this.loadAudioTrack(nextIdx, autoplay);
    }

    prevTrack(autoplay = true) {
        const prevIdx = (this.currentIndex - 1 + this.tracks.length) % this.tracks.length;
        this.loadAudioTrack(prevIdx, autoplay);
    }

    setMode(newMode) {
        const validModes = ['playlist', 'page', 'loop', 'random'];
        if (!validModes.includes(newMode)) return;

        this.mode = newMode;
        localStorage.setItem('shogun_audio_mode', newMode);

        if (this.audio) {
            this.audio.loop = (this.mode === 'loop');
        }

        if (newMode === 'page') {
            const pageIndex = this.tracks.findIndex(t => t.pages.includes(this.currentPage));
            if (pageIndex !== -1 && pageIndex !== this.currentIndex) {
                this.loadAudioTrack(pageIndex, this.isEnabled && !this.audio.paused);
            }
        }

        this.updateUi();
    }

    cycleMode() {
        const order = ['playlist', 'page', 'loop', 'random'];
        const currentPos = order.indexOf(this.mode);
        const nextMode = order[(currentPos + 1) % order.length];
        this.setMode(nextMode);
    }

    setVolume(newVol) {
        this.volume = Math.max(0, Math.min(1, parseFloat(newVol)));
        localStorage.setItem('shogun_audio_volume', this.volume.toString());

        if (this.audio && this.isEnabled && !this.audio.paused) {
            this.audio.volume = this.volume;
        }

        this.updateUi();
    }

    seek(percentage) {
        if (!this.audio || !this.audio.duration) return;
        const targetTime = (Math.max(0, Math.min(100, percentage)) / 100) * this.audio.duration;
        this.audio.currentTime = targetTime;
        this.updateScrubber();
    }

    fadeIn(targetVol = null, duration = 1000) {
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

    fadeOut(callback = null, duration = 500) {
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

    formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    updateScrubber() {
        const curTimeEl = document.getElementById('shogun-player-time-cur');
        const durTimeEl = document.getElementById('shogun-player-time-dur');
        const progressEl = document.getElementById('shogun-player-progress');

        if (!this.audio) return;

        const cur = this.audio.currentTime || 0;
        const dur = this.audio.duration || 57;

        if (curTimeEl) curTimeEl.textContent = this.formatTime(cur);
        if (durTimeEl) durTimeEl.textContent = this.formatTime(dur);

        if (progressEl) {
            const pct = dur > 0 ? (cur / dur) * 100 : 0;
            progressEl.style.width = `${pct}%`;
        }
    }

    renderPlaylistUi() {
        const container = document.getElementById('shogun-playlist-tracks');
        if (!container) return;

        container.innerHTML = this.tracks.map((track, idx) => `
            <div class="shogun-track-item ${idx === this.currentIndex ? 'active' : ''}" 
                 onclick="window.shogunAudio && window.shogunAudio.playTrack(${idx})"
                 data-track-index="${idx}">
                <div class="d-flex align-items-center justify-content-center text-secondary" style="width: 28px; height: 28px;">
                    <div class="shogun-eq ${idx === this.currentIndex && this.isEnabled && this.audio && !this.audio.paused ? 'playing' : ''}">
                        <div class="shogun-eq-bar"></div>
                        <div class="shogun-eq-bar"></div>
                        <div class="shogun-eq-bar"></div>
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center justify-content-between gap-1">
                        <div class="fw-bold text-truncate text-light fs-4" style="letter-spacing: 0.3px;">
                            ${track.title}
                        </div>
                        <span class="badge ${track.badgeClass} border-0" style="font-size: 0.68rem; padding: 2px 6px;">
                            ${track.tag}
                        </span>
                    </div>
                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                        <i class="${track.icon} me-1" style="color: ${track.iconColor};"></i>
                        ${track.subtitle}
                    </div>
                </div>
            </div>
        `).join('');
    }

    attachEventListeners() {
        // Scrubber cliquable
        const scrubber = document.getElementById('shogun-player-scrubber');
        if (scrubber) {
            scrubber.addEventListener('click', (e) => {
                const rect = scrubber.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const pct = (clickX / rect.width) * 100;
                this.seek(pct);
            });
        }

        // Slider de volume
        const volSlider = document.getElementById('shogun-audio-vol-slider');
        if (volSlider) {
            volSlider.addEventListener('input', (e) => {
                this.setVolume(parseFloat(e.target.value) / 100);
            });
        }
    }

    updateUi() {
        const isPlaying = this.audio && !this.audio.paused && this.isEnabled;
        const currentTrack = this.getCurrentTrack();

        // 1. Bouton trigger et icône dans le header
        const btnEl = document.getElementById('shogun-audio-btn');
        const iconEl = document.getElementById('shogun-audio-icon');
        const dotEl = document.getElementById('shogun-audio-badge-dot');

        if (iconEl) {
            if (isPlaying) {
                iconEl.className = 'fa-solid fa-volume-high text-success fs-3 audio-playing';
            } else {
                iconEl.className = 'fa-solid fa-volume-xmark text-secondary fs-3';
            }
        }

        if (dotEl) {
            dotEl.classList.toggle('d-none', !isPlaying);
        }

        if (btnEl) {
            const tooltipTitle = isPlaying
                ? `Pavillon Musical : « ${currentTrack.title} » en lecture (${Math.round(this.volume * 100)}%)`
                : `Pavillon Musical & Playlist Féodale (Son coupé)`;
            btnEl.setAttribute('title', tooltipTitle);
            btnEl.setAttribute('data-bs-original-title', tooltipTitle);
            btnEl.classList.toggle('active', isPlaying);
        }

        // 2. Panneau Dropdown du lecteur
        const playBtnIcon = document.getElementById('shogun-ctrl-play-icon');
        if (playBtnIcon) {
            playBtnIcon.className = isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play';
        }

        const nowTitleEl = document.getElementById('shogun-player-now-title');
        if (nowTitleEl) nowTitleEl.textContent = currentTrack.title;

        const nowSubEl = document.getElementById('shogun-player-now-sub');
        if (nowSubEl) nowSubEl.textContent = currentTrack.subtitle;

        const nowTagEl = document.getElementById('shogun-player-now-tag');
        if (nowTagEl) {
            nowTagEl.textContent = currentTrack.tag;
            nowTagEl.className = `badge ${currentTrack.badgeClass} border-0`;
        }

        // Mode bouton & libellé
        const modeBtn = document.getElementById('shogun-ctrl-mode');
        const modeBadge = document.getElementById('shogun-player-mode-badge');
        const modeConfigs = {
            'playlist': { icon: 'fa-solid fa-repeat', label: 'Enchaînement continu', title: 'Mode : Enchaîne toutes les pistes' },
            'page': { icon: 'fa-solid fa-compass', label: 'Ambiance du fief', title: 'Mode : S\'adapte au domaine actuel' },
            'loop': { icon: 'fa-solid fa-rotate-right', label: 'Piste en boucle', title: 'Mode : Répète ce morceau indéfiniment' },
            'random': { icon: 'fa-solid fa-shuffle', label: 'Aléatoire', title: 'Mode : Morceaux au hasard' },
        };
        const curModeConfig = modeConfigs[this.mode] || modeConfigs['playlist'];

        if (modeBtn) {
            modeBtn.innerHTML = `<i class="${curModeConfig.icon}"></i>`;
            modeBtn.title = curModeConfig.title;
        }

        if (modeBadge) {
            modeBadge.textContent = curModeConfig.label;
        }

        // Volume slider & texte
        const volSlider = document.getElementById('shogun-audio-vol-slider');
        if (volSlider) volSlider.value = Math.round(this.volume * 100);

        const volText = document.getElementById('shogun-audio-vol-percent');
        if (volText) volText.textContent = `${Math.round(this.volume * 100)}%`;

        const volIcon = document.getElementById('shogun-audio-vol-icon');
        if (volIcon) {
            if (this.volume === 0 || !this.isEnabled) {
                volIcon.className = 'fa-solid fa-volume-xmark text-secondary';
            } else if (this.volume < 0.4) {
                volIcon.className = 'fa-solid fa-volume-low text-warning';
            } else {
                volIcon.className = 'fa-solid fa-volume-high text-success';
            }
        }

        // 3. Rafraîchir les statuts dans la liste des morceaux
        document.querySelectorAll('#shogun-playlist-tracks .shogun-track-item').forEach((item, idx) => {
            const isItemActive = (idx === this.currentIndex);
            item.classList.toggle('active', isItemActive);

            const eq = item.querySelector('.shogun-eq');
            if (eq) {
                eq.classList.toggle('playing', isItemActive && isPlaying);
            }
        });
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    window.shogunAudio = new ShogunAudioManager();
});
