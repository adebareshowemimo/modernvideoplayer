/**
 * Keyboard shortcuts for the modern video player.
 *
 * Binds document-level keydown listeners that only act when the player
 * is the primary focus target (no other input/textarea/contenteditable
 * has focus and the player root is visible in the viewport).
 *
 * Shortcuts:
 *   Space / K      Toggle play / pause
 *   ← / J          Rewind 10 seconds (respects allowedposition)
 *   → / L          Forward 10 seconds (respects allowedposition)
 *   ↑              Volume +10 %
 *   ↓              Volume -10 %
 *   M              Toggle mute
 *   F              Toggle fullscreen
 *   0..9           Seek to N*10 % of the video (respects allowedposition)
 *   < or ,         Speed down one step (if allowplaybackspeed)
 *   > or .         Speed up one step (if allowplaybackspeed, clamped by maxplaybackspeed)
 *   C              Toggle captions
 *   ?              Show shortcuts help dialog
 *
 * @module     mod_modernvideoplayer/shortcuts
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SPEED_STEPS = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];

const isTypingTarget = (target) => {
    if (!target) {
        return false;
    }
    const tag = (target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
        return true;
    }
    if (target.isContentEditable) {
        return true;
    }
    return false;
};

const clampSeek = (video, state, targetTime) => {
    const duration = Number.isFinite(video.duration) ? video.duration : 0;
    let t = Math.max(0, targetTime);
    if (duration > 0) {
        t = Math.min(duration, t);
    }
    if (typeof state.allowedposition === 'number' && t > state.allowedposition) {
        t = state.allowedposition;
    }
    return t;
};

const requestFullscreen = (root) => {
    const target = root;
    if (document.fullscreenElement) {
        document.exitFullscreen?.();
        return;
    }
    target.requestFullscreen?.();
};

/**
 * Initialise keyboard shortcuts.
 *
 * @param {HTMLElement} root player root
 * @param {HTMLVideoElement} video <video> element
 * @param {object} state progress state
 * @param {object} config player JS config (strings)
 * @returns {void}
 */
export const init = (root, video, state, config) => {
    const strings = config.strings || {};
    const helpDialog = root.querySelector('[data-region="shortcuts-help"]');
    const helpClose = root.querySelector('[data-action="close-shortcuts-help"]');
    const helpButton = root.querySelector('[data-action="toggle-shortcuts-help"]');

    const stepSpeed = (dir) => {
        if (!state.allowplaybackspeed) {
            return;
        }
        const cap = state.maxplaybackspeed && state.maxplaybackspeed > 0 ? state.maxplaybackspeed : 2.0;
        const current = video.playbackRate || 1;
        const allowed = SPEED_STEPS.filter((r) => r <= cap + 0.001);
        // Find nearest step to current.
        let idx = 0;
        let bestDelta = Infinity;
        allowed.forEach((r, i) => {
            const d = Math.abs(r - current);
            if (d < bestDelta) {
                bestDelta = d;
                idx = i;
            }
        });
        const nextIdx = Math.max(0, Math.min(allowed.length - 1, idx + dir));
        video.playbackRate = allowed[nextIdx];
    };

    const seekBy = (delta) => {
        const target = clampSeek(video, state, (video.currentTime || 0) + delta);
        video.currentTime = target;
    };

    const seekToPercent = (percent) => {
        const duration = Number.isFinite(video.duration) ? video.duration : 0;
        if (!duration) {
            return;
        }
        const target = clampSeek(video, state, duration * (percent / 100));
        video.currentTime = target;
    };

    const setHelpOpen = (open) => {
        if (!helpDialog) {
            return;
        }
        helpDialog.classList.toggle('d-none', !open);
        helpButton?.setAttribute('aria-pressed', open ? 'true' : 'false');
        if (open) {
            helpClose?.focus();
        }
    };

    const cycleCaptions = () => {
        const ccButton = root.querySelector('[data-action="toggle-captions"]');
        ccButton?.click();
    };

    const togglePlay = () => {
        if (video.paused) {
            video.play().catch(() => {
                // Ignore play failures — the browser may block.
                return null;
            });
        } else {
            video.pause();
        }
    };

    const changeVolume = (delta) => {
        const newVol = Math.max(0, Math.min(1, (video.volume || 0) + delta));
        video.volume = newVol;
        if (newVol > 0 && video.muted) {
            video.muted = false;
        }
    };

    const handleKey = (ev) => {
        if (ev.ctrlKey || ev.metaKey || ev.altKey) {
            return;
        }
        if (isTypingTarget(ev.target)) {
            return;
        }
        // Only handle shortcuts if the player is in the page (always true here,
        // one player per page) AND nothing else claims arrow/space input.
        const key = ev.key;

        const enforceFocus = !!config.enforcefocus;
        // Keys that move the playhead or change speed. When focus enforcement
        // is on, these are ignored so the learner can't skip content.
        const seekKeys = new Set([
            'ArrowLeft', 'ArrowRight', 'j', 'J', 'l', 'L',
            '<', ',', '>', '.',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ]);
        if (enforceFocus && seekKeys.has(key)) {
            return;
        }

        // Close help modal first on Escape.
        if (key === 'Escape' && helpDialog && !helpDialog.classList.contains('d-none')) {
            ev.preventDefault();
            setHelpOpen(false);
            return;
        }

        switch (key) {
            case ' ':
            case 'k':
            case 'K':
                ev.preventDefault();
                togglePlay();
                break;
            case 'ArrowLeft':
            case 'j':
            case 'J':
                ev.preventDefault();
                seekBy(-10);
                break;
            case 'ArrowRight':
            case 'l':
            case 'L':
                ev.preventDefault();
                seekBy(10);
                break;
            case 'ArrowUp':
                ev.preventDefault();
                changeVolume(0.1);
                break;
            case 'ArrowDown':
                ev.preventDefault();
                changeVolume(-0.1);
                break;
            case 'm':
            case 'M':
                ev.preventDefault();
                video.muted = !video.muted;
                break;
            case 'f':
            case 'F':
                ev.preventDefault();
                requestFullscreen(root);
                break;
            case 'c':
            case 'C':
                ev.preventDefault();
                cycleCaptions();
                break;
            case '<':
            case ',':
                ev.preventDefault();
                stepSpeed(-1);
                break;
            case '>':
            case '.':
                ev.preventDefault();
                stepSpeed(1);
                break;
            case '?':
                ev.preventDefault();
                setHelpOpen(helpDialog?.classList.contains('d-none') ?? true);
                break;
            default:
                if (key >= '0' && key <= '9') {
                    ev.preventDefault();
                    seekToPercent(parseInt(key, 10) * 10);
                }
                break;
        }
    };

    document.addEventListener('keydown', handleKey);

    helpButton?.addEventListener('click', () => {
        setHelpOpen(helpDialog?.classList.contains('d-none') ?? true);
    });
    helpClose?.addEventListener('click', () => setHelpOpen(false));
    helpDialog?.addEventListener('click', (ev) => {
        if (ev.target === helpDialog) {
            setHelpOpen(false);
        }
    });

    // Expose strings for aria labels where available.
    if (strings.shortcutshelp && helpButton) {
        helpButton.setAttribute('aria-label', strings.shortcutshelp);
        helpButton.setAttribute('title', strings.shortcutshelp);
    }
};
