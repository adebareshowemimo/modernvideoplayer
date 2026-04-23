# Modern Video Player for Moodle (`mod_modernvideoplayer`)

[![Moodle Plugin CI](https://github.com/adebareshowemimo/modernvideoplayer/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/adebareshowemimo/modernvideoplayer/actions/workflows/moodle-ci.yml)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![Moodle 4.5+](https://img.shields.io/badge/Moodle-4.5%2B-orange.svg)](https://moodle.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://www.php.net)

A **secure HTML5 video activity** for Moodle with **server-side seek and
playback-speed enforcement**, accurate segment-based progress tracking, and
first-class accessibility.

> Built for courses where watch-time is evidence — compliance training,
> micro-credentials, mandatory onboarding, flipped classrooms.

---

## Why this plugin

Moodle's built-in video embedding (via `mod_resource` or the URL filter) shows a
video. It doesn't tell you if the learner actually watched it. Skipping,
fast-forwarding, and tab-hiding all look like "watched".

`mod_modernvideoplayer` closes that gap:

- **Server-side enforcement.** Every seek and speed change is validated server
  side against a signed session token. Clients can't lie about watch state.
- **Segment-level progress.** Heartbeats record which intervals of the video
  were actually played, giving a real viewed-percentage — not the native
  `timeupdate` approximation.
- **Real completion.** Native Moodle completion rule "watched ≥ X%" and a
  matching availability condition for downstream activities.

## Features (Free / Community Edition)

Everything below is in the free GPL build — no license key, no upsell wall.

### Playback & player UI
- HTML5 `<video>` player with play / pause / mute / volume / fullscreen
- Picture-in-Picture toggle (opt-in per activity, auto-disabled in Focus Mode)
- Poster image, configurable primary/secondary navigation toggles,
  title position, right-blocks toggle, course-index toggle
- **Autoplay modes**: off / muted / unmuted (with muted fallback when the
  browser blocks sound)
- External URL source **or** uploaded MP4 (file API + repository integration)
- Learner-facing **playback-speed menu** with admin-configurable maximum
- **Keyboard shortcuts**: space, arrows, `J`/`K`/`L`, `M`, `F`, digit-seek
  (all gated by Focus Mode)
- Resume from last known position
- Theme-aware, RTL-ready, responsive, accessible controls (ARIA, focus rings)

### Captions, transcript & chapters
- VTT captions with default-language selector
- **Transcript panel** with click-to-seek cues
- **Transcript download** button (opt-in per activity) — exports a timestamped
  `.txt` file named from the activity
- Chapter / timeline markers

### Focus Mode enforcement (v0.10.0)
- Per-activity `enforcefocus` toggle + admin default
- Disables Picture-in-Picture
- Suppresses seek keyboard shortcuts (arrows, `J`/`L`, `<`/`>`, digit keys)
- Auto-pauses playback when the browser tab is hidden
- Picture-in-Picture and transcript download each have independent opt-in
  toggles (`allowpip`, `allowtranscriptdownload`)

### Integrity (the differentiator)
- Signed per-session tokens per user + activity
- Server-side **seek validation** with configurable tolerance
- Server-side **playback-speed enforcement** (allow / deny)
- Heartbeat grace seconds + strict end-of-video validation
- Suspicious-event counters surfaced in the instructor report

### Progress & completion
- Heartbeat + **segment tracking** → accurate viewed-percentage (not native
  `timeupdate` approximation)
- Custom completion rule: **watched at least N %** (v0.7.0)
- Availability condition to gate downstream activities on watch progress
- Partial / full / forced completion modes

### Gradebook integration (v0.8.0)
- Activity grade item driven by watched-percentage
- `grade_update()` hook, `grade_item_update`, scale/point support
- Honours Moodle's grade category, pass mark, and grade locking

### Learner bookmarks (v0.9.0)
- Per-learner timestamped bookmarks with optional labels
- AJAX external services: `mod_modernvideoplayer_add_bookmark`,
  `list_bookmarks`, `delete_bookmark`
- Bookmarks are purged automatically when the instance is deleted

### Reporting
- Per-activity learner report (progress, flags, last-seen, total watched)
- CSV export
- Moodle events: `progress_updated`, `completion_achieved`,
  `suspicious_seek_detected`, `bookmark_created`, `bookmark_deleted` — plug
  straight into your LRS / data warehouse

### Privacy & compliance (v0.5.0)
- Full Moodle Privacy API provider
- GDPR subject-access **export** and **delete** for all user-authored data
  (sessions, progress, suspicious flags, bookmarks)
- No external network calls, no tracking pixels

### Platform hygiene
- Full **backup & restore** (user data, instance config, bookmarks)
- **Mobile / Moodle App** support
- Mustache templates, AMD modules (Rollup-built, ESLint-clean)
- Site-wide admin defaults for every instance-level setting
- Internationalisation scaffold (English bundled; all strings externalised)

### Quality bar
- **42 PHPUnit tests / 115 assertions** covering defaults, completion rules,
  gradebook writeback, bookmarks CRUD + ownership, privacy export/delete,
  Focus Mode fields
- **Behat acceptance scenarios** for activity creation + Focus Mode settings
- GitHub Actions CI (`moodle-plugin-ci`) green on Moodle 4.5 and 5.0
- Moodle coding style clean (`phpcs --standard=moodle`)

## Requirements

- Moodle **4.5+** or **5.0+**
- PHP **8.1+**
- Modern browser with HTML5 video support

## Install

### Via Moodle admin UI (recommended)

1. Download the latest release ZIP from the [Releases page](https://github.com/adebareshowemimo/modernvideoplayer/releases).
2. In Moodle, go to **Site administration → Plugins → Install plugins**.
3. Drop the ZIP into the uploader.
4. Confirm the plugin details and click **Install**. Run the upgrade.

### Via git

```bash
cd /path/to/moodle/mod
git clone https://github.com/adebareshowemimo/modernvideoplayer.git
# Then from Moodle root:
php admin/cli/upgrade.php
```

## Configuration

Site administration → Plugins → Activity modules → **Modern video player**.
You can set site-wide defaults for:

- Default autoplay mode (off / muted / unmuted)
- Fullscreen enabled by default
- Playback speed allowed
- Seek tolerance (seconds)
- Completion threshold (%)

## Instructor usage

1. Turn editing on in a course.
2. **Add an activity or resource → Modern video player**.
3. Upload an MP4 (or paste an external URL).
4. Set completion rule (e.g. watched ≥ 80 %) and save.

Learners get a clean player; instructors get a report at
*Course → Activity → Report*.

## Development

```bash
# AMD build
cd /path/to/moodle
npx grunt amd --root=mod/modernvideoplayer

# Unit tests
vendor/bin/phpunit --testsuite mod_modernvideoplayer_testsuite

# Behat (requires behat init)
vendor/bin/behat --tags=@mod_modernvideoplayer
```

## Roadmap

The free-edition roadmap through **v0.11.0** is complete (see
[CHANGELOG.md](CHANGELOG.md) for the per-release breakdown):

- ✅ v0.5.0 — Privacy provider
- ✅ v0.5.1 — `js_call_amd` hotfix
- ✅ v0.6.0 — PHPUnit + CI
- ✅ v0.7.0 — Activity completion rules
- ✅ v0.8.0 — Gradebook integration
- ✅ v0.9.0 — Learner bookmarks
- ✅ v0.10.0 — Focus Mode + PiP + transcript download
- ✅ v0.11.0 — Behat acceptance coverage

Next on the path to **1.0.0**: per-user watch-progress export report and
packaging for the Moodle Plugins Directory.

## Contributing

Issues and PRs welcome! Please read
[CONTRIBUTING.md](CONTRIBUTING.md) first. All contributors sign a
[lightweight CLA](https://cla-assistant.io) so we can keep the project
relicensable if needed.

## Security

If you find a security issue, **please do not file a public issue.** Email
`security@agunfoninteractivity.com` instead. See [SECURITY.md](SECURITY.md).

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).

## Commercial support & premium add-ons

The community edition is 100% free and GPL. Paid tiers (Pro / Enterprise) add
auto-captions, watermarking, signed URLs, interactive questions, heatmap
analytics, xAPI/LTI, DRM and SLA support. Contact
`sales@agunfoninteractivity.com` or see [the pricing page](https://agunfoninteractivity.com/modernvideoplayer).

## Credits

Built by [Adebare Showemimo](https://agunfoninteractivity.com) and
contributors.
