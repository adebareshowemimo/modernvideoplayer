/**
 * Client-side seek and speed enforcement.
 *
 * @module     mod_modernvideoplayer/enforcer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const clampSeek = (video, state, strings) => {
    video.addEventListener('seeking', () => {
        if (typeof state.allowedposition !== 'number') {
            return;
        }

        if (video.currentTime > state.allowedposition) {
            video.currentTime = state.maxverifiedposition || 0;
            if (strings.seekblocked) {
                window.console.warn(strings.seekblocked);
            }
        }
    });
};

const clampSpeed = (video, state, strings) => {
    video.addEventListener('ratechange', () => {
        if (!state.allowplaybackspeed) {
            video.playbackRate = 1;
            return;
        }
        if (state.maxplaybackspeed && video.playbackRate > state.maxplaybackspeed) {
            video.playbackRate = state.maxplaybackspeed;
            if (strings.speedrestricted) {
                window.console.warn(strings.speedrestricted);
            }
        }
    });
};

/**
 * When focus-mode enforcement is on, block every escape hatch that could
 * detach the learner from the course page: Picture-in-Picture, tab hiding
 * and any programmatic PiP request.
 *
 * Keyboard shortcut suppression is handled inside shortcuts.js because it
 * has to branch per-key.
 *
 * @param {HTMLVideoElement} video
 * @param {object} config player config
 * @returns {void}
 */
const enforceFocus = (video, config) => {
    if (!config || !config.enforcefocus) {
        return;
    }

    const strings = config.strings || {};

    // Browser PiP — both the standard API and the Safari-specific one.
    video.setAttribute('disablePictureInPicture', 'disablePictureInPicture');
    video.disablePictureInPicture = true;
    video.setAttribute('disableRemotePlayback', 'disableRemotePlayback');

    // If the browser already has the video in PiP (e.g. via browser chrome
    // before the script ran), force-exit it.
    if (document.pictureInPictureElement === video && document.exitPictureInPicture) {
        document.exitPictureInPicture().catch(() => null);
    }
    video.addEventListener('enterpictureinpicture', () => {
        if (document.exitPictureInPicture) {
            document.exitPictureInPicture().catch(() => null);
        }
    });

    // Pause whenever the tab is hidden so the progress timer cannot run in
    // the background.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && !video.paused) {
            video.pause();
            if (strings.focuspausedhidden) {
                window.console.info(strings.focuspausedhidden);
            }
        }
    });
};

export const init = (video, state, strings, config) => {
    clampSeek(video, state, strings);
    clampSpeed(video, state, strings);
    enforceFocus(video, config || {strings});
};
