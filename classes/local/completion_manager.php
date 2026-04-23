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
 * Completion management for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\local;

use completion_info;
use context_module;
use stdClass;
/**
 * Module completion coordinator.
 * @package mod_modernvideoplayer
 */
class completion_manager {
    /**
     * Recalculate and apply completion state.
     *
     * @param stdClass $course course
     * @param stdClass $cm course module
     * @param stdClass $instance activity config
     * @param stdClass $progress learner progress
     * @return bool
     */
    public function update(stdClass $course, stdClass $cm, stdClass $instance, stdClass $progress): bool {
        $iscomplete = $this->is_complete($instance, $progress);

        if ((int) ($cm->completion ?? COMPLETION_TRACKING_NONE) !== COMPLETION_TRACKING_AUTOMATIC) {
            return $iscomplete;
        }

        if ($iscomplete && !$progress->completed) {
            $progress->completed = 1;
            $progress->completiontime = time();

            $event = \mod_modernvideoplayer\event\completion_achieved::create([
                'context' => context_module::instance($cm->id),
                'objectid' => $instance->id,
                'relateduserid' => $progress->userid,
            ]);
            $event->trigger();

            // Only grant completion â€” never revoke it via a learner action.
            // Completion is sticky: a teacher must use Moodle's standard
            // "Reset user completion data" tool to remove it.
            $completion = new completion_info($course);
            $completion->update_state($cm, COMPLETION_COMPLETE, $progress->userid);
        } else if (!$iscomplete) {
            // Keep internal tracking accurate so the progress bar and
            // reports reflect the current watch state, but do NOT push
            // COMPLETION_INCOMPLETE into Moodle's completion system.
            $progress->completed = 0;
            $progress->completiontime = null;
        }

        return $iscomplete;
    }

    /**
     * Determine if the progress meets completion rules.
     *
     * @param stdClass $instance activity config
     * @param stdClass $progress learner progress
     * @return bool
     */
    public function is_complete(stdClass $instance, stdClass $progress): bool {
        $meetspercent = (float) $progress->percentcomplete >= (float) $instance->requiredpercent;
        $duration = (float) $progress->duration;
        $meetsend = $duration > 0
            ? (float) $progress->maxverifiedposition >= max(0, $duration - max(1, (int) $instance->graceseconds))
            : false;

        if (!empty($instance->strictendvalidation)) {
            $meetspercent = $meetspercent && $meetsend;
        }
        if ((int) $instance->completionmode === 1) {
            return $meetspercent && $meetsend;
        }

        return $meetspercent;
    }
}
