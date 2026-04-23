# Changes

User-facing release notes for `mod_modernvideoplayer`.

The canonical, full changelog lives in [CHANGELOG.md](CHANGELOG.md) — this file
exists because the Moodle Plugins Directory uploader prefills release notes
from a file named exactly `CHANGES.md` / `CHANGES` / `CHANGES.txt`.

## 1.2.0 — 2026-04-23

### Fixed
- Privacy API now covers the `modernvideoplayer_bookmarks` table. Learner
  bookmarks are included in subject-access exports and are deleted by all four
  privacy delete paths (per-user, per-userlist, per-context,
  delete-all-in-context).
- Backup and restore now round-trip learner bookmarks. Previously bookmarks
  were silently dropped on course duplicate / course restore.

### Added
- `$plugin->supported = [405, 500]` declared in `version.php` so the Plugins
  Directory auto-publishes the supported Moodle version range.
- Privacy metadata language strings for the bookmarks table.

## 1.1.0 — 2026-04-23

- First stable release (`MATURITY_STABLE`).
- See [CHANGELOG.md](CHANGELOG.md) for the per-release breakdown of the
  0.5.x — 0.11.x feature line (playback UI, captions/transcript/chapters,
  Focus Mode enforcement, integrity, progress & completion, gradebook,
  bookmarks, reporting, privacy, backup/restore, mobile hooks, PHPUnit and
  Behat coverage).
