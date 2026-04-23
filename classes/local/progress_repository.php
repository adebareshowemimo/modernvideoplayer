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

/**
 * Progress repository for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\local;

use stdClass;
/**
 * Persistence layer for learner progress.
 * @package mod_modernvideoplayer
 */
class progress_repository {
    /**
     * Get or create progress row.
     *
     * @param int $instanceid activity id
     * @param int $userid user id
     * @return stdClass
     */
    public function get_or_create(int $instanceid, int $userid): stdClass {
        global $DB;

        $progress = $DB->get_record('modernvideoplayer_progress', [
            'modernvideoplayerid' => $instanceid,
            'userid' => $userid,
        ]);

        if ($progress) {
            return $progress;
        }

        $now = time();
        $progress = (object) [
            'modernvideoplayerid' => $instanceid,
            'userid' => $userid,
            'sessiontoken' => null,
            'duration' => 0,
            'lastposition' => 0,
            'maxverifiedposition' => 0,
            'totalsecondswatched' => 0,
            'percentcomplete' => 0,
            'completed' => 0,
            'completiontime' => null,
            'timecreated' => $now,
            'lastheartbeat' => null,
            'lastplaybackrate' => 1.0,
            'lastvisibility' => 'visible',
            'suspiciousflags' => 0,
            'integrityfailures' => 0,
            'timemodified' => $now,
        ];
        $progress->id = $DB->insert_record('modernvideoplayer_progress', $progress);

        return $progress;
    }

    /**
     * Persist a progress record.
     *
     * @param stdClass $progress progress
     * @return stdClass
     */
    public function save(stdClass $progress): stdClass {
        global $DB;

        $progress->timemodified = time();
        $DB->update_record('modernvideoplayer_progress', $progress);
        return $progress;
    }

    /**
     * Insert a validated segment.
     *
     * @param int $progressid progress id
     * @param float $start start time
     * @param float $end end time
     * @return void
     */
    public function add_segment(int $progressid, float $start, float $end): void {
        global $DB;

        if ($end <= $start) {
            return;
        }

        $tolerance = 0.5;
        $mergedstart = round($start, 2);
        $mergedend = round($end, 2);

        $sql = "progressid = :progressid AND segmentend >= :start AND segmentstart <= :end";
        $params = [
            'progressid' => $progressid,
            'start' => $mergedstart - $tolerance,
            'end' => $mergedend + $tolerance,
        ];
        $existing = $DB->get_records_select('modernvideoplayer_segments', $sql, $params, 'segmentstart ASC');

        foreach ($existing as $segment) {
            $mergedstart = min($mergedstart, (float) $segment->segmentstart);
            $mergedend = max($mergedend, (float) $segment->segmentend);
            $DB->delete_records('modernvideoplayer_segments', ['id' => $segment->id]);
        }

        $DB->insert_record('modernvideoplayer_segments', (object) [
            'progressid' => $progressid,
            'segmentstart' => $mergedstart,
            'segmentend' => $mergedend,
            'watchedseconds' => round($mergedend - $mergedstart, 2),
            'timecreated' => time(),
        ]);
    }

    /**
     * Return the validated watched coverage.
     *
     * @param int $progressid progress id
     * @return float
     */
    public function get_total_watched_seconds(int $progressid): float {
        global $DB;

        $sql = "SELECT COALESCE(SUM(watchedseconds), 0)
                  FROM {modernvideoplayer_segments}
                 WHERE progressid = :progressid";
        return round((float) $DB->get_field_sql($sql, ['progressid' => $progressid]), 2);
    }

    /**
     * Return all watched segments.
     *
     * @param int $progressid progress id
     * @return array
     */
    public function get_segments(int $progressid): array {
        global $DB;

        return array_values($DB->get_records('modernvideoplayer_segments', ['progressid' => $progressid], 'segmentstart ASC'));
    }

    /**
     * Reset all progress for a user on an activity back to zero.
     * Deletes all segments and zeroes all numeric progress fields.
     * Generates a fresh session token so any in-flight heartbeats are invalidated.
     *
     * @param int $instanceid activity id
     * @param int $userid user id
     * @return stdClass the zeroed progress row
     */
    public function reset(int $instanceid, int $userid): stdClass {
        global $DB;

        $progress = $this->get_or_create($instanceid, $userid);

        // Delete all watched segments.
        $DB->delete_records('modernvideoplayer_segments', ['progressid' => $progress->id]);

        // Zero all progress fields and issue a new session token.
        $progress->sessiontoken = bin2hex(random_bytes(16));
        $progress->lastposition = 0;
        $progress->maxverifiedposition = 0;
        $progress->totalsecondswatched = 0;
        $progress->percentcomplete = 0;
        $progress->completed = 0;
        $progress->completiontime = null;
        $progress->suspiciousflags = 0;
        $progress->integrityfailures = 0;
        $progress->lastheartbeat = null;
        $progress->timemodified = time();

        $DB->update_record('modernvideoplayer_progress', $progress);

        return $progress;
    }
}
