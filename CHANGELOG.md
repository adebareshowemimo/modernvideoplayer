# Changelog

All notable changes to `mod_modernvideoplayer` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Behat acceptance tests

## [0.10.0] - 2026-04-23

### Added
- **Focus Mode enforcement** (`enforcefocus` instance setting + `defaultenforcefocus` admin default).
  When enabled it disables Picture-in-Picture, suppresses seek keyboard shortcuts
  (arrows, `J`/`L`, `<`/`>`, digit keys), and auto-pauses playback when the browser
  tab is hidden.
- **Picture-in-Picture toggle button** (`allowpip` instance setting, on by default,
  auto-disabled whenever Focus Mode is on). Controls button is rendered only when
  the browser supports PiP and the instance allows it.
- **Transcript download button** (`allowtranscriptdownload` instance setting,
  on by default). Rendered next to the transcript panel close button when captions
  exist; downloads a timestamped `.txt` transcript.
- 3 new DB columns via `db/upgrade.php` savepoint `2026042017`.
- PHPUnit `focus_mode_test.php` (3 tests) covering instance defaults, overrides,
  and the `modernvideoplayer_get_defaults()` admin-default fallbacks.
- New AMD logic: `enforcer.js::enforceFocus()`, `shortcuts.js` seek-key gate,
  `player.js` PiP + transcript-download wiring.

### Changed
- Total PHPUnit suite is now 42 tests / 115 assertions.

## [0.9.0] - 2026-04-23

### Added
- **Learner bookmarks**:
  - New `modernvideoplayer_bookmarks` table (id, modernvideoplayerid, userid,
    position, label, timecreated, timemodified) provisioned via
    `db/upgrade.php` savepoint 2026042016, with FKs to `modernvideoplayer` and
    `user` plus an index on `(modernvideoplayerid, userid)`.
  - Domain service `mod_modernvideoplayer\local\bookmark_manager` enforcing
    trimmed non-empty labels, a 50 bookmarks-per-user cap, position clamping
    to `>= 0`, and scoped `delete_own()` / `delete_for_activity()` cleanup.
  - Three AJAX-enabled web services:
    `mod_modernvideoplayer_add_bookmark` (write, requires
    `mod/modernvideoplayer:submitprogress`),
    `mod_modernvideoplayer_list_bookmarks` (read, requires
    `mod/modernvideoplayer:view`) and
    `mod_modernvideoplayer_delete_bookmark` (write, same submitprogress
    capability, ownership-scoped).
  - Activity deletion (`modernvideoplayer_delete_instance`) now cascades
    through the manager so orphaned bookmarks are removed.
  - PHPUnit coverage in `tests/bookmarks_test.php` (10 tests / 22 assertions)
    across manager edge cases, WS happy paths and ownership enforcement.

### Changed
- Total plugin test suite now reports **39 tests / 100 assertions**, phpcs
  clean.

## [0.8.0] - 2026-04-23

### Added
- **Gradebook integration** (`FEATURE_GRADE_HAS_GRADE`):
  - New `grade` column on the `modernvideoplayer` table (int, default 100)
    provisioned via `db/upgrade.php` savepoint 2026042015.
  - Grade callbacks in `lib.php`:
    `modernvideoplayer_grade_item_update`, `modernvideoplayer_grade_item_delete`,
    `modernvideoplayer_get_user_grades`, `modernvideoplayer_update_grades`.
  - Grade is derived linearly from `percentcomplete`
    (learner_grade = grademax × percentcomplete / 100).
  - Gradebook is refreshed from every heartbeat and from `reset_progress`,
    so teacher-facing grade columns stay in sync with watch progress.
  - `mod_form.php` now exposes the standard `Maximum grade` element.
- PHPUnit coverage in `tests/gradebook_test.php` exercises grade item
  creation, proportional scaling, custom grademax, gradebook writes, and
  grade-item deletion (6 tests / 13 assertions).
- Total plugin suite now **29 tests / 78 assertions**.

## [0.7.0] - 2026-04-23

### Added
- **PHPUnit coverage for custom completion rules**:
  `tests/completion/custom_completion_test.php` exercises every rule returned
  by `\mod_modernvideoplayer\completion\custom_completion::get_defined_custom_rules()`:
  - `completionvideopercent` complete/incomplete against `requiredpercent`.
  - `completionvideoend` complete/incomplete with `graceseconds` tolerance.
  - Incomplete result when no `modernvideoplayer_progress` row exists.
  - Incomplete result when `duration` is unknown (0).
  - Rule descriptions follow `cm_info->customdata.customcompletionrules`.
  - Sort order places `completionview` first.
- Total plugin suite now **23 tests / 65 assertions** (privacy + external + completion).

## [0.6.0] - 2026-04-23

### Added
- **External web service PHPUnit coverage**: `tests/external/external_test.php`
  covers all four WS endpoints (`get_progress`, `heartbeat`, `mark_complete`,
  `reset_progress`) end-to-end, including return-structure validation via
  `external_api::clean_returnvalue()` and course-access enforcement.
- Plugin now ships with **14 PHPUnit tests / 50 assertions** spanning
  privacy provider and external web services. The existing
  `.github/workflows/moodle-ci.yml` runs these on every push/PR via
  `moodle-plugin-ci phpunit --fail-on-warning`.

## [0.5.1] - 2026-04-23

### Fixed
- `view.php` now emits the player config as a `<script type="application/json">`
  blob read by the AMD module instead of passing it through
  `js_call_amd()`, which triggered a `Too much data passed as arguments`
  E_USER_NOTICE in Moodle 5.0 when the strings bundle exceeded 1024 chars.

## [0.5.0] - 2026-04-23

### Added
- **Captions (WebVTT)**: upload multiple `.vtt` files per activity. Language
  is auto-detected from filename suffix (e.g. `lecture.en.vtt`, `lecture.fr.vtt`,
  `lecture.es-MX.vtt`); unrecognised files fall back to the configured
  **Default caption language** (per-activity, with site-wide default).
- **CC button** in the player controls cycles through available caption tracks
  (off → each track → off).
- **Transcript panel**: the default-language caption track is rendered as a
  clickable cue list below the player. Clicking a cue seeks the video to that
  point; the active cue is highlighted and scrolled into view as playback
  progresses.
- New admin setting `modernvideoplayer/defaultcaptionlang` (BCP-47, default `en`).
- Captions files are included in activity backup / restore.
- **Chapter markers (WebVTT)**: upload a single `.vtt` chapter file per
  activity. Chapter starts are rendered as clickable pins on the progress bar
  and as an entry list in a chapter panel. The active chapter is highlighted
  as playback progresses; clicking a pin or chapter entry seeks the video.
- **Chapters button** in the player controls opens the chapter list panel.
- Chapter files are included in activity backup / restore.
- **Playback speed menu**: learner-facing 0.5x-2x dropdown that honors the
  per-instance speed cap and `allowplaybackspeed` flag. Filtered entries
  above the cap are hidden; the enforcer still clamps programmatic changes.
- **Keyboard shortcuts**: Space/K (play-pause), J/L and arrow keys (seek 10s,
  volume), M (mute), F (fullscreen), C (captions), 0-9 (seek to percent),
  `<` / `>` (speed down/up), `?` (show shortcuts help modal). Seek shortcuts
  still respect the server-side `allowedposition`.
- New shortcuts help modal listing all bindings.
- **Privacy provider**: GDPR-compliant `\mod_modernvideoplayer\privacy\provider`
  advertises all learner data (progress + watched segments), exports, and
  deletes per-user/per-context/per-userlist. Backed by PHPUnit coverage.
- **PHPUnit data generator**: `mod_modernvideoplayer_generator::create_instance()`
  with sensible defaults so other tests can spin up activity instances.

## [0.1.0] - 2026-04-22

Initial alpha release.

### Added
- HTML5 player: play / pause / mute / volume / fullscreen / Picture-in-Picture
- Poster image, focus mode, configurable nav toggle
- **Autoplay modes**: off / muted / unmuted (muted fallback on browser block)
- Server-side seek enforcement via signed session tokens
- Server-side playback-speed enforcement
- Heartbeat + segment-based progress tracking
- Suspicious-event counters
- Custom completion rule ("watched ≥ X %")
- Availability condition ("watched ≥ X %")
- Per-learner report with CSV export
- Moodle events: `progress_updated`, `completion_achieved`,
  `suspicious_seek_detected`
- External web services: `get_progress`, `heartbeat`, `mark_complete`,
  `reset_progress`
- Backup / restore
- Privacy provider (GDPR)
- Moodle App support
- Site-wide default settings (autoplay, fullscreen, speed, seek tolerance,
  completion threshold)

[Unreleased]: https://github.com/adebareshowemimo/modernvideoplayer/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/adebareshowemimo/modernvideoplayer/releases/tag/v0.1.0
