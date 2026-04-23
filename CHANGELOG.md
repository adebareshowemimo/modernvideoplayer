# Changelog

All notable changes to `mod_modernvideoplayer` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Learner bookmarks
- Picture-in-Picture + transcript download
- Activity completion rules
- Gradebook integration
- Behat acceptance tests

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
