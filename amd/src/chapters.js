/**
 * Chapter markers support for the modern video player.
 *
 * Renders clickable pins on the progress bar at each chapter cue start,
 * exposes a chapter list panel with click-to-seek, and highlights the
 * active chapter as playback progresses.
 *
 * @module     mod_modernvideoplayer/chapters
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {formatTime} from 'mod_modernvideoplayer/ui';

/**
 * Initialise chapter markers + list panel.
 *
 * @param {HTMLElement} root player root node
 * @param {HTMLVideoElement} video the underlying <video> element
 * @param {object} config player JS config (strings)
 * @returns {void}
 */
export const init = (root, video, config) => {
    const tracks = Array.from(video.textTracks || []);
    const chapterTrack = tracks.find((t) => t.kind === 'chapters');
    if (!chapterTrack) {
        return;
    }

    // Keep chapters hidden from the native UI — we render our own.
    chapterTrack.mode = 'hidden';

    const button = root.querySelector('[data-action="toggle-chapters"]');
    const panel = root.querySelector('[data-region="chapters-panel"]');
    const list = root.querySelector('[data-region="chapters-list"]');
    const emptyLabel = root.querySelector('[data-region="chapters-empty"]');
    const closeBtn = root.querySelector('[data-action="close-chapters"]');
    const pinsContainer = root.querySelector('[data-region="chapters-pins"]');

    button?.classList.remove('d-none');

    const strings = config.strings || {};

    const seekToCue = (cue) => {
        video.currentTime = cue.startTime;
        if (video.paused) {
            video.play().catch(() => {
                // Ignore play failures — the browser may block.
                return null;
            });
        }
    };

    const renderList = (cues) => {
        if (!list) {
            return;
        }
        while (list.firstChild) {
            list.removeChild(list.firstChild);
        }
        if (!cues.length) {
            if (emptyLabel) {
                emptyLabel.textContent = strings.chaptersunavailable || 'Chapters unavailable.';
                emptyLabel.classList.remove('d-none');
            }
            list.classList.add('d-none');
            return;
        }
        cues.forEach((cue, index) => {
            const li = document.createElement('li');
            li.className = 'modernvideoplayer-chapters__cue';
            li.dataset.index = String(index);
            li.dataset.start = String(cue.startTime);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-link text-start p-2 w-100 modernvideoplayer-chapters__button';
            const timeLabel = formatTime(cue.startTime);
            btn.setAttribute(
                'aria-label',
                (strings.chapterjumpto || 'Jump to chapter at {$a}').replace('__TIME__', timeLabel)
            );

            const timeEl = document.createElement('span');
            timeEl.className = 'modernvideoplayer-chapters__time text-muted me-2';
            timeEl.textContent = timeLabel;

            const textEl = document.createElement('span');
            textEl.className = 'modernvideoplayer-chapters__text';
            textEl.textContent = cue.text || '';

            btn.appendChild(timeEl);
            btn.appendChild(textEl);
            btn.addEventListener('click', () => seekToCue(cue));

            li.appendChild(btn);
            list.appendChild(li);
        });
        emptyLabel?.classList.add('d-none');
        list.classList.remove('d-none');
    };

    const renderPins = (cues) => {
        if (!pinsContainer) {
            return;
        }
        while (pinsContainer.firstChild) {
            pinsContainer.removeChild(pinsContainer.firstChild);
        }
        const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
        if (!duration || !cues.length) {
            return;
        }
        cues.forEach((cue, index) => {
            if (cue.startTime <= 0) {
                return;
            }
            const pct = Math.min(100, Math.max(0, (cue.startTime / duration) * 100));
            const pin = document.createElement('button');
            pin.type = 'button';
            pin.className = 'modernvideoplayer-chapters__pin';
            pin.style.left = pct + '%';
            pin.dataset.index = String(index);
            pin.title = cue.text || '';
            pin.setAttribute('aria-label',
                (strings.chapterjumpto || 'Jump to chapter at {$a}').replace('__TIME__', formatTime(cue.startTime))
            );
            pin.addEventListener('click', (ev) => {
                ev.stopPropagation();
                seekToCue(cue);
            });
            pinsContainer.appendChild(pin);
        });
    };

    const render = () => {
        const cues = Array.from(chapterTrack.cues || []);
        renderList(cues);
        renderPins(cues);
    };

    if (chapterTrack.cues && chapterTrack.cues.length) {
        render();
    } else {
        chapterTrack.addEventListener('load', render, {once: true});
        chapterTrack.addEventListener('cuechange', render, {once: true});
    }

    // Re-render pins once duration is known (needed for percentage positioning).
    if (!Number.isFinite(video.duration) || video.duration <= 0) {
        video.addEventListener('loadedmetadata', () => {
            if (chapterTrack.cues && chapterTrack.cues.length) {
                renderPins(Array.from(chapterTrack.cues));
            }
        }, {once: true});
    }

    // Highlight the active chapter.
    let lastActiveIndex = -1;
    video.addEventListener('timeupdate', () => {
        if (!list || !chapterTrack.cues) {
            return;
        }
        const now = video.currentTime;
        const cues = chapterTrack.cues;
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
            list.querySelector(`[data-index="${lastActiveIndex}"]`)
                ?.classList.remove('modernvideoplayer-chapters__cue--active');
            pinsContainer?.querySelector(`[data-index="${lastActiveIndex}"]`)
                ?.classList.remove('modernvideoplayer-chapters__pin--active');
        }
        if (activeIndex >= 0) {
            const el = list.querySelector(`[data-index="${activeIndex}"]`);
            el?.classList.add('modernvideoplayer-chapters__cue--active');
            pinsContainer?.querySelector(`[data-index="${activeIndex}"]`)
                ?.classList.add('modernvideoplayer-chapters__pin--active');
            if (panel && !panel.classList.contains('d-none')) {
                el?.scrollIntoView({block: 'nearest', behavior: 'smooth'});
            }
        }
        lastActiveIndex = activeIndex;
    });

    const setOpen = (open) => {
        if (!panel || !button) {
            return;
        }
        panel.classList.toggle('d-none', !open);
        button.setAttribute('aria-pressed', open ? 'true' : 'false');
        button.classList.toggle('modernvideoplayer-controls__button--active', open);
    };

    button?.addEventListener('click', () => {
        setOpen(panel?.classList.contains('d-none') ?? false);
    });
    closeBtn?.addEventListener('click', () => setOpen(false));
};
