<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace mod_modernvideoplayer\local;

use core\exception\moodle_exception;
use stdClass;

/**
 * Persistence logic for learner bookmarks.
 *
 * Bookmarks are always scoped to (modernvideoplayerid, userid): a user can
 * only read, write, or delete their own bookmarks. Capability checks live in
 * the external classes that call this manager.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookmark_manager {
    /** Maximum bookmarks a single learner may create per activity. */
    public const MAX_PER_USER = 50;

    /** Maximum label length enforced beyond the DB column width. */
    public const LABEL_MAX_LENGTH = 255;

    /**
     * Create a new bookmark for a learner.
     *
     * @param int $modernvideoplayerid activity id
     * @param int $userid owner
     * @param float $position position in seconds (>= 0)
     * @param string $label human-readable label; will be trimmed
     * @return stdClass the inserted bookmark row (with id, timestamps)
     */
    public function add(int $modernvideoplayerid, int $userid, float $position, string $label): stdClass {
        global $DB;

        $position = max(0.0, round($position, 2));
        $label = trim($label);

        if ($label === '') {
            throw new moodle_exception('bookmarklabelrequired', 'mod_modernvideoplayer');
        }

        if (mb_strlen($label) > self::LABEL_MAX_LENGTH) {
            $label = mb_substr($label, 0, self::LABEL_MAX_LENGTH);
        }

        $existing = $DB->count_records('modernvideoplayer_bookmarks', [
            'modernvideoplayerid' => $modernvideoplayerid,
            'userid'              => $userid,
        ]);
        if ($existing >= self::MAX_PER_USER) {
            throw new moodle_exception('bookmarklimitreached', 'mod_modernvideoplayer');
        }

        $now = time();
        $row = (object) [
            'modernvideoplayerid' => $modernvideoplayerid,
            'userid'              => $userid,
            'position'            => $position,
            'label'               => $label,
            'timecreated'         => $now,
            'timemodified'        => $now,
        ];
        $row->id = $DB->insert_record('modernvideoplayer_bookmarks', $row);

        return $row;
    }

    /**
     * List all bookmarks owned by a learner for an activity, ordered by position.
     *
     * @param int $modernvideoplayerid activity id
     * @param int $userid owner
     * @return stdClass[]
     */
    public function list_for_user(int $modernvideoplayerid, int $userid): array {
        global $DB;

        return array_values($DB->get_records(
            'modernvideoplayer_bookmarks',
            ['modernvideoplayerid' => $modernvideoplayerid, 'userid' => $userid],
            'position ASC, id ASC'
        ));
    }

    /**
     * Delete a bookmark that belongs to the supplied user.
     *
     * Silently succeeds (no-ops) when the bookmark does not exist or is owned
     * by someone else — this keeps delete idempotent and prevents ownership
     * enumeration from the client.
     *
     * @param int $bookmarkid bookmark id
     * @param int $userid owner
     * @return bool true when a row was actually removed
     */
    public function delete_own(int $bookmarkid, int $userid): bool {
        global $DB;

        if (!$DB->record_exists('modernvideoplayer_bookmarks', ['id' => $bookmarkid, 'userid' => $userid])) {
            return false;
        }

        $DB->delete_records('modernvideoplayer_bookmarks', [
            'id'     => $bookmarkid,
            'userid' => $userid,
        ]);

        return true;
    }

    /**
     * Remove every bookmark attached to an activity. Used by
     * modernvideoplayer_delete_instance() to keep referential integrity.
     *
     * @param int $modernvideoplayerid activity id
     */
    public function delete_for_activity(int $modernvideoplayerid): void {
        global $DB;

        $DB->delete_records('modernvideoplayer_bookmarks', ['modernvideoplayerid' => $modernvideoplayerid]);
    }
}
