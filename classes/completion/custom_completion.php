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

namespace mod_modernvideoplayer\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for mod_modernvideoplayer.
 *
 * Moodle's core completion system calls this class via
 * activity_custom_completion::get_cm_completion_class() whenever it needs to
 * evaluate the module's custom rules (e.g. on page-view, heartbeat update, or
 * bulk recalculation). Without this class the custom rules are silently skipped
 * and the activity completes on view alone.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Names of every custom rule this module can define.
     *
     * @return string[]
     */
    public static function get_defined_custom_rules(): array {
        return ['completionvideopercent', 'completionvideoend'];
    }

    /**
     * Evaluate a single custom completion rule for the current user.
     *
     * @param string $rule Rule name — must be one of get_defined_custom_rules().
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $instance = $DB->get_record(
            'modernvideoplayer',
            ['id' => $this->cm->instance],
            'id, requiredpercent, strictendvalidation, graceseconds',
            MUST_EXIST
        );

        $progress = $DB->get_record('modernvideoplayer_progress', [
            'modernvideoplayerid' => $instance->id,
            'userid'              => $this->userid,
        ]);

        // No playback data yet — the user has not started watching.
        if (!$progress) {
            return COMPLETION_INCOMPLETE;
        }

        if ($rule === 'completionvideopercent') {
            $required = (float) $instance->requiredpercent;
            // Rule is considered satisfied when requiredpercent is 0 (disabled).
            if ($required <= 0) {
                return COMPLETION_COMPLETE;
            }
            return (float) $progress->percentcomplete >= $required
                ? COMPLETION_COMPLETE
                : COMPLETION_INCOMPLETE;
        }

        if ($rule === 'completionvideoend') {
            $duration = (float) $progress->duration;
            if ($duration <= 0) {
                // Duration not yet known — cannot confirm the learner reached the end.
                return COMPLETION_INCOMPLETE;
            }
            $grace     = max(1, (int) $instance->graceseconds);
            $threshold = max(0.0, $duration - $grace);
            return (float) $progress->maxverifiedposition >= $threshold
                ? COMPLETION_COMPLETE
                : COMPLETION_INCOMPLETE;
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Human-readable descriptions of the active custom rules for display on
     * the course page and completion report.
     *
     * @return array  Rule name => description string.
     */
    public function get_custom_rule_descriptions(): array {
        $customrules = (array) ($this->cm->customdata['customcompletionrules'] ?? []);
        $descriptions = [];

        if (!empty($customrules['completionvideopercent'])) {
            $descriptions['completionvideopercent'] = get_string(
                'completionvideopercentdesc',
                'modernvideoplayer',
                $customrules['completionvideopercent']
            );
        }

        if (!empty($customrules['completionvideoend'])) {
            $descriptions['completionvideoend'] = get_string(
                'completionvideoenddesc',
                'modernvideoplayer'
            );
        }

        return $descriptions;
    }

    /**
     * Display order for completion rules on the course page.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionvideopercent',
            'completionvideoend',
        ];
    }
}
