/**
 * Player bootstrapping for the modern video player activity.
 *
 * @module     mod_modernvideoplayer/player
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import * as UI from 'mod_modernvideoplayer/ui';
import * as Enforcer from 'mod_modernvideoplayer/enforcer';
import * as Tracker from 'mod_modernvideoplayer/tracker';
import * as Captions from 'mod_modernvideoplayer/captions';
import * as Chapters from 'mod_modernvideoplayer/chapters';
import * as SpeedMenu from 'mod_modernvideoplayer/speedmenu';
import * as Shortcuts from 'mod_modernvideoplayer/shortcuts';

const getProgress = (cmid) => Ajax.call([{
    methodname: 'mod_modernvideoplayer_get_progress',
    args: {cmid}
}])[0];

const resetProgress = (cmid) => Ajax.call([{
    methodname: 'mod_modernvideoplayer_reset_progress',
    args: {cmid}
}])[0];

export const init = (cmid) => {
    const root = document.querySelector('[data-region="modern-video-player"]');
    if (!root) {
        return;
    }

    const configNode = document.getElementById('mod_modernvideoplayer-config-' + cmid);
    if (!configNode) {
        window.console.warn('mod_modernvideoplayer: missing config node for cmid ' + cmid);
        return;
    }
    let config;
    try {
        config = JSON.parse(configNode.textContent);
    } catch (e) {
        window.console.error('mod_modernvideoplayer: failed to parse config JSON', e);
        return;
    }

    const video = root.querySelector('[data-region="video"]');
    const stage = root.querySelector('[data-region="player-stage"]');
    const playButton = root.querySelector('[data-action="toggle-play"]');
    const muteButton = root.querySelector('[data-action="toggle-mute"]');
    const fullscreenButton = root.querySelector('[data-action="toggle-fullscreen"]');
    const resumeButton = root.querySelector('[data-action="resume-playback"]');
    let pendingSeekTime = null;
    let controlsTouchTimer = null;
    let mouseControlsEnabled = true;

    const applySeek = (time) => {
        const targetTime = Math.max(0, Number(time) || 0);

        if (video.readyState >= 1 || Number.isFinite(video.duration)) {
            video.currentTime = targetTime;
            pendingSeekTime = null;
            return Promise.resolve(targetTime);
        }

        pendingSeekTime = targetTime;
        return new Promise((resolve) => {
            const onReady = () => {
                video.currentTime = pendingSeekTime;
                pendingSeekTime = null;
                video.removeEventListener('loadedmetadata', onReady);
                resolve(targetTime);
            };

            video.addEventListener('loadedmetadata', onReady, {once: true});
        });
    };

    video.setAttribute('playsinline', 'playsinline');
    video.setAttribute('webkit-playsinline', 'webkit-playsinline');

    const syncPlayerUi = () => {
        UI.updatePlayState(root, video.paused, config.strings);
        UI.updateMuteState(root, video.muted || video.volume === 0, config.strings);
        UI.updateTime(root, video.currentTime || 0, video.duration || 0);
        root.classList.toggle('modernvideoplayer--playing', !video.paused);
        if (video.paused) {
            root.classList.add('modernvideoplayer--controls-visible');
        }
    };

    const setControlsVisible = (visible) => {
        root.classList.toggle('modernvideoplayer--controls-visible', visible);
    };

    const showControlsTemporarily = () => {
        setControlsVisible(true);
        if (controlsTouchTimer) {
            window.clearTimeout(controlsTouchTimer);
        }

        if (!video.paused) {
            controlsTouchTimer = window.setTimeout(() => {
                setControlsVisible(false);
            }, 2200);
        }
    };

    const requestFullscreen = () => {
        const container = root.querySelector('[data-region="controls-shell"]')?.parentElement || video;
        if (document.fullscreenElement) {
            return document.exitFullscreen?.();
        }

        return container.requestFullscreen?.();
    };

    const getVisualPercent = (state) => {
        const validated = Number(state.percentcomplete) || 0;
        if (!video.duration || !Number.isFinite(video.duration) || video.duration <= 0) {
            return validated;
        }

        const frontierpercent = ((Number(state.maxverifiedposition) || 0) / video.duration) * 100;
        const currentpercent = ((Math.max(0, Number(video.currentTime) || 0)) / video.duration) * 100;
        return Math.max(validated, Math.min(100, Math.max(frontierpercent, currentpercent)));
    };

    const syncBufferedUi = () => {
        if (!video.duration || !video.buffered || !video.buffered.length) {
            UI.updateBuffered(root, 0);
            return;
        }

        let bufferedend = 0;
        try {
            bufferedend = video.buffered.end(video.buffered.length - 1);
        } catch (error) {
            bufferedend = 0;
        }

        const buffered = (bufferedend / video.duration) * 100;
        UI.updateBuffered(root, buffered);
    };

    getProgress(config.cmid).then((state) => {
        if (!state.videourl) {
            window.console.warn(config.strings.progressunavailable);
            return;
        }

        if (config.hascaptions) {
            Captions.init(root, video, config);
        }

        if (config.haschapters) {
            Chapters.init(root, video, config);
        }

        SpeedMenu.init(root, video, state, config.strings);
        Shortcuts.init(root, video, state, config);

        Enforcer.init(video, state, config.strings, config);

        const pipButton = root.querySelector('[data-action="toggle-pip"]');
        if (pipButton) {
            const pipSupported = !!(document.pictureInPictureEnabled && !video.disablePictureInPicture);
            if (config.allowpip && pipSupported) {
                pipButton.classList.remove('d-none');
                pipButton.addEventListener('click', () => {
                    if (document.pictureInPictureElement === video) {
                        document.exitPictureInPicture?.().catch(() => null);
                    } else {
                        video.requestPictureInPicture?.().catch(() => null);
                    }
                });
                video.addEventListener('enterpictureinpicture', () => {
                    pipButton.setAttribute('aria-pressed', 'true');
                });
                video.addEventListener('leavepictureinpicture', () => {
                    pipButton.setAttribute('aria-pressed', 'false');
                });
            } else {
                pipButton.classList.add('d-none');
            }
        }

        const downloadButton = root.querySelector('[data-action="download-transcript"]');
        if (downloadButton && config.allowtranscriptdownload && config.hascaptions) {
            downloadButton.classList.remove('d-none');
            downloadButton.addEventListener('click', () => {
                const items = root.querySelectorAll('[data-region="transcript-list"] li');
                if (!items.length) {
                    return;
                }
                const formatSeconds = (raw) => {
                    const total = Math.max(0, Math.floor(Number(raw) || 0));
                    const hh = Math.floor(total / 3600);
                    const mm = Math.floor((total % 3600) / 60);
                    const ss = total % 60;
                    const pad = (n) => String(n).padStart(2, '0');
                    return hh > 0 ? `${hh}:${pad(mm)}:${pad(ss)}` : `${pad(mm)}:${pad(ss)}`;
                };
                const lines = [];
                items.forEach((li) => {
                    const start = li.getAttribute('data-start');
                    const text = (li.textContent || '').replace(/\s+/g, ' ').trim();
                    if (text.length) {
                        lines.push(start !== null ? '[' + formatSeconds(start) + '] ' + text : text);
                    }
                });
                const blob = new Blob([lines.join('\n') + '\n'], {type: 'text/plain;charset=utf-8'});
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = config.transcriptfilename || 'transcript.txt';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.setTimeout(() => URL.revokeObjectURL(url), 500);
            });
        }

        const onUpdate = (response) => {
            state.allowedposition = response.allowedposition;
            state.maxverifiedposition = response.maxverifiedposition;
            state.percentcomplete = response.percentcomplete || 0;
            UI.updatePercent(root, getVisualPercent(state));
        };

        const hideControlsAfterDelay = (ms = 3000) => {
            // Disable mouse-triggered show while the countdown runs.
            mouseControlsEnabled = false;
            if (controlsTouchTimer) {
                window.clearTimeout(controlsTouchTimer);
            }
            setControlsVisible(true);
            controlsTouchTimer = window.setTimeout(() => {
                controlsTouchTimer = null;
                if (!video.paused) {
                    setControlsVisible(false);
                }
                // Re-enable mouse controls only AFTER controls are hidden.
                mouseControlsEnabled = true;
            }, ms);
        };

        const startPlayback = (time = 0) => {
            UI.hideResumeOverlay(root);
            // eslint-disable-next-line promise/no-nesting -- legitimate fallback chain.
            return applySeek(time).then(() => video.play()).catch(() => video.play());
        };

        // Attempt autoplay with the configured mode. Falls back to muted if an
        // unmuted attempt is blocked by the browser's autoplay policy.
        const tryAutoplay = () => {
            const mode = config.autoplay;
            if (mode !== 'muted' && mode !== 'unmuted') {
                return;
            }
            if (mode === 'muted') {
                video.muted = true;
                syncPlayerUi();
            }
            const playPromise = startPlayback(0);
            if (mode === 'unmuted' && playPromise && typeof playPromise.catch === 'function') {
                // eslint-disable-next-line promise/no-nesting -- fallback retry on autoplay-block.
                playPromise.catch(() => {
                    // Browser blocked unmuted autoplay — retry muted.
                    video.muted = true;
                    syncPlayerUi();
                    // eslint-disable-next-line promise/no-nesting
                    void video.play().catch(() => undefined);
                });
            }
        };

        const resumeposition = Math.max(
            Number(state.lastposition) || 0,
            Number(state.maxverifiedposition) || 0
        );
        // Show the resume overlay whenever there is a saved position, regardless of
        // completion status. A completed student returning to re-watch should still be
        // asked whether to resume from their last position or restart from the beginning.
        const shouldPromptResume = state.allowresume && resumeposition >= 1;

        if (shouldPromptResume) {
            UI.showResumeOverlay(
                root,
                config.strings.resumepromptheading,
                config.strings.resumeplaybackfrom.replace('__TIME__', UI.formatTime(resumeposition))
            );
            // Pre-seek to the resume position so the time display and progress bar
            // already reflect where playback will resume — even before the user clicks.
            void applySeek(resumeposition);
            video.pause();
        } else if (state.allowresume && resumeposition > 0) {
            void applySeek(resumeposition);
        }

        const tracker = Tracker.start(config, video, state, onUpdate);
        // Only send an immediate heartbeat when starting fresh from position 0.
        // If resumeposition > 0 a seek is pending; firing now would send currentTime=0
        // which saves lastposition=0 and causes the plausibility check to reject all
        // subsequent heartbeats at the actual resume position.
        if (resumeposition === 0) {
            tracker.sync();
        }

        // Trigger autoplay only on fresh starts (no resume prompt shown).
        if (!shouldPromptResume) {
            tryAutoplay();
        }

        resumeButton?.addEventListener('click', () => startPlayback(resumeposition));
        root.querySelectorAll('[data-action="restart-playback"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                /* eslint-disable promise/no-nesting -- chain restart logic. */
                resetProgress(config.cmid).then((fresh) => {
                    // Wipe all local state counters so enforcer/progress bar reset.
                    state.sessiontoken = fresh.sessiontoken;
                    state.lastposition = 0;
                    state.maxverifiedposition = 0;
                    state.allowedposition = fresh.allowedposition;
                    state.percentcomplete = 0;
                    state.completed = false;
                    // NOTE: no Enforcer.init here — the existing listeners already hold a
                    // reference to the same `state` object and will enforce the new limits.
                    UI.updatePercent(root, 0);
                    return startPlayback(0);
                }).catch(() => {
                    // Fall back to local seek-only restart if the API call fails.
                    return startPlayback(0);
                });
                /* eslint-enable promise/no-nesting */
            });
        });
        playButton?.addEventListener('click', () => {
            if (video.paused) {
                void video.play();
            } else {
                video.pause();
            }
        });
        muteButton?.addEventListener('click', () => {
            video.muted = !video.muted;
            syncPlayerUi();
        });
        fullscreenButton?.addEventListener('click', () => {
            void requestFullscreen();
        });

        video.addEventListener('play', () => {
            UI.hideResumeOverlay(root);
            // Show controls briefly then auto-hide after 3 s.
            hideControlsAfterDelay(3000);
            syncPlayerUi();
        });

        video.addEventListener('pause', () => {
            // Cancel any running auto-hide timer so it can't hide paused controls.
            if (controlsTouchTimer) {
                window.clearTimeout(controlsTouchTimer);
                controlsTouchTimer = null;
            }
            mouseControlsEnabled = true;
            setControlsVisible(true);
            syncPlayerUi();
        });

        video.addEventListener('loadedmetadata', () => {
            if (pendingSeekTime !== null) {
                video.currentTime = pendingSeekTime;
                pendingSeekTime = null;
            }
            // Update after seek so time/progress reflects the actual currentTime.
            syncBufferedUi();
            syncPlayerUi();
            UI.updatePercent(root, getVisualPercent(state));
        });

        video.addEventListener('durationchange', () => {
            if (!video.duration || !Number.isFinite(video.duration)) {
                return;
            }

            if (pendingSeekTime !== null) {
                video.currentTime = pendingSeekTime;
                pendingSeekTime = null;
            }
            syncBufferedUi();
            syncPlayerUi();
            UI.updatePercent(root, getVisualPercent(state));
        });

        video.addEventListener('progress', () => {
            syncBufferedUi();
        });

        video.addEventListener('loadeddata', () => {
            syncBufferedUi();
            syncPlayerUi();
            UI.updatePercent(root, getVisualPercent(state));
        });

        video.addEventListener('timeupdate', () => {
            UI.updatePercent(root, getVisualPercent(state));
            UI.updateTime(root, video.currentTime || 0, video.duration || 0);
        });

        video.addEventListener('canplay', syncBufferedUi);
        video.addEventListener('seeked', () => {
            syncBufferedUi();
            syncPlayerUi();
            UI.updatePercent(root, getVisualPercent(state));
        });

        video.addEventListener('volumechange', () => {
            UI.updateMuteState(root, video.muted || video.volume === 0, config.strings);
        });

        stage?.addEventListener('mouseenter', () => {
            if (!video.paused && mouseControlsEnabled) {
                showControlsTemporarily();
            }
        });

        stage?.addEventListener('mouseleave', () => {
            if (controlsTouchTimer) {
                window.clearTimeout(controlsTouchTimer);
                controlsTouchTimer = null;
            }
            if (!video.paused) {
                setControlsVisible(false);
            }
        });

        stage?.addEventListener('mousemove', () => {
            if (!video.paused && mouseControlsEnabled) {
                showControlsTemporarily();
            }
        });

        stage?.addEventListener('touchstart', () => {
            showControlsTemporarily();
        }, {passive: true});

        // Pause when the user switches browser tabs or minimises the window.
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden' && !video.paused) {
                video.pause();
            }
        });

        // Pause when the browser window itself loses focus (e.g. Alt-Tab to another app).
        window.addEventListener('blur', () => {
            if (!video.paused) {
                video.pause();
            }
        });

        setControlsVisible(video.paused);
        UI.updatePercent(root, getVisualPercent(state));
        syncPlayerUi();
        // eslint-disable-next-line consistent-return -- explicit return for promise/always-return.
        return null;
    }).catch(() => {
        window.console.warn(config.strings.progressunavailable);
    });
};
