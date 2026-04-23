/**
 * Captions & transcript support for the modern video player.
 *
 * Renders a transcript panel from the default caption track, provides
 * click-to-seek cues, and exposes a CC button that cycles through the
 * available text tracks (off → track 1 → track 2 → … → off).
 *
 * @module     mod_modernvideoplayer/captions
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {formatTime} from 'mod_modernvideoplayer/ui';

/**
 * Initialise captions & transcript features.
 *
 * @param {HTMLElement} root player root node
 * @param {HTMLVideoElement} video the underlying <video> element
 * @param {object} config player JS config (strings + defaultcaptionlang)
 * @returns {void}
 */
export const init = (root, video, config) => {
    const tracks = Array.from(video.textTracks || []);
    const ccButton = root.querySelector('[data-action="toggle-captions"]');
    const ccLabel = root.querySelector('[data-region="captions-label"]');
    const transcriptButton = root.querySelector('[data-action="toggle-transcript"]');
    const transcriptPanel = root.querySelector('[data-region="transcript-panel"]');
    const transcriptList = root.querySelector('[data-region="transcript-list"]');
    const transcriptEmpty = root.querySelector('[data-region="transcript-empty"]');
    const transcriptClose = root.querySelector('[data-action="close-transcript"]');

    if (!tracks.length) {
        return;
    }

    // Hide all tracks by default; the user opts in via the CC button.
    tracks.forEach((track) => {
        track.mode = 'hidden';
    });

    // Show controls now that we know there are tracks.
    ccButton?.classList.remove('d-none');
    transcriptButton?.classList.remove('d-none');

    const setCcState = (activeTrack) => {
        if (!ccButton) {
            return;
        }
        const isOn = Boolean(activeTrack);
        ccButton.setAttribute('aria-pressed', isOn ? 'true' : 'false');
        ccButton.classList.toggle('modernvideoplayer-controls__button--active', isOn);
        if (ccLabel) {
            if (isOn) {
                const trackLabel = activeTrack.label || activeTrack.language || '';
                const template = config.strings.captionson || 'Captions: {$a}';
                ccLabel.textContent = template.replace('__LABEL__', trackLabel);
            } else {
                ccLabel.textContent = config.strings.captionsoff || 'Captions off';
            }
        }
    };

    const setActiveTrack = (target) => {
        tracks.forEach((track) => {
            track.mode = track === target ? 'showing' : 'hidden';
        });
        setCcState(target);
    };

    // Cycle: off → first → second → … → off.
    ccButton?.addEventListener('click', () => {
        const currentIndex = tracks.findIndex((t) => t.mode === 'showing');
        const nextIndex = currentIndex + 1;
        if (nextIndex >= tracks.length) {
            setActiveTrack(null);
        } else {
            setActiveTrack(tracks[nextIndex]);
        }
    });

    setCcState(null);

    // -----------------------------------------------------------------------
    // Transcript panel.
    // -----------------------------------------------------------------------

    const defaultLang = (config.defaultcaptionlang || 'en').toLowerCase();
    const transcriptTrack = tracks.find((t) => (t.language || '').toLowerCase() === defaultLang)
        || tracks[0];

    /**
     * Render the cue list in the transcript panel.
     */
    const renderCues = () => {
        if (!transcriptList || !transcriptTrack || !transcriptTrack.cues) {
            return;
        }

        // Clear + rebuild.
        while (transcriptList.firstChild) {
            transcriptList.removeChild(transcriptList.firstChild);
        }

        const cues = Array.from(transcriptTrack.cues);
        if (!cues.length) {
            transcriptEmpty.textContent = config.strings.transcriptunavailable
                || 'No transcript is available for this video.';
            transcriptEmpty.classList.remove('d-none');
            transcriptList.classList.add('d-none');
            return;
        }

        cues.forEach((cue, index) => {
            const li = document.createElement('li');
            li.className = 'modernvideoplayer-transcript__cue';
            li.dataset.index = String(index);
            li.dataset.start = String(cue.startTime);
            li.dataset.end = String(cue.endTime);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-link text-start p-2 w-100 modernvideoplayer-transcript__button';
            const timeLabel = formatTime(cue.startTime);
            button.setAttribute(
                'aria-label',
                (config.strings.transcriptjumpto || 'Jump to {$a} in the video').replace('__TIME__', timeLabel)
            );

            const timeEl = document.createElement('span');
            timeEl.className = 'modernvideoplayer-transcript__time text-muted me-2';
            timeEl.textContent = timeLabel;

            const textEl = document.createElement('span');
            textEl.className = 'modernvideoplayer-transcript__text';
            textEl.textContent = cue.text || '';

            button.appendChild(timeEl);
            button.appendChild(textEl);
            button.addEventListener('click', () => {
                video.currentTime = cue.startTime;
                if (video.paused) {
                    video.play().catch(() => {
                        // Ignore play failures — the browser may block.
                    });
                }
            });

            li.appendChild(button);
            transcriptList.appendChild(li);
        });

        transcriptEmpty.classList.add('d-none');
        transcriptList.classList.remove('d-none');
    };

    if (transcriptTrack) {
        if (transcriptTrack.cues && transcriptTrack.cues.length) {
            renderCues();
        } else {
            // Cues are populated asynchronously after the track file loads.
            transcriptTrack.addEventListener('load', renderCues, {once: true});
            transcriptTrack.addEventListener('cuechange', renderCues, {once: true});
        }
    }

    // Highlight the active cue as playback progresses.
    let lastActiveIndex = -1;
    video.addEventListener('timeupdate', () => {
        if (!transcriptList || !transcriptTrack || !transcriptTrack.cues) {
            return;
        }
        const now = video.currentTime;
        const cues = transcriptTrack.cues;
        let activeIndex = -1;
        for (let i = 0; i < cues.length; i++) {
            const cue = cues[i];
            if (cue.startTime <= now && cue.endTime > now) {
                activeIndex = i;
                break;
            }
        }
        if (activeIndex === lastActiveIndex) {
            return;
        }
        if (lastActiveIndex >= 0) {
            transcriptList.querySelector(`[data-index="${lastActiveIndex}"]`)
                ?.classList.remove('modernvideoplayer-transcript__cue--active');
        }
        if (activeIndex >= 0) {
            const el = transcriptList.querySelector(`[data-index="${activeIndex}"]`);
            el?.classList.add('modernvideoplayer-transcript__cue--active');
            // Keep the active cue visible when the panel is open.
            if (!transcriptPanel.classList.contains('d-none')) {
                el?.scrollIntoView({block: 'nearest', behavior: 'smooth'});
            }
        }
        lastActiveIndex = activeIndex;
    });

    const setTranscriptOpen = (open) => {
        if (!transcriptPanel || !transcriptButton) {
            return;
        }
        transcriptPanel.classList.toggle('d-none', !open);
        transcriptButton.setAttribute('aria-pressed', open ? 'true' : 'false');
        transcriptButton.classList.toggle('modernvideoplayer-controls__button--active', open);
    };

    transcriptButton?.addEventListener('click', () => {
        setTranscriptOpen(transcriptPanel.classList.contains('d-none'));
    });
    transcriptClose?.addEventListener('click', () => setTranscriptOpen(false));
};
