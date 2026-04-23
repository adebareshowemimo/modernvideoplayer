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
 * Web service: reset learner progress.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_modernvideoplayer\local\playback_manager;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/modernvideoplayer/locallib.php');

/**
 * Reset learner progress back to zero for restart from beginning.
 * @package mod_modernvideoplayer
 */
class reset_progress extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid course module id
     * @return array
     */
    public static function execute(int $cmid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        [$course, $cm, $instance] = modernvideoplayer_get_course_module_and_instance($params['cmid']);
        require_course_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/modernvideoplayer:submitprogress', $context);

        $manager = new playback_manager();
        $result = $manager->reset_progress($course, $cm, $instance, $USER->id);

        return (array) $result;
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sessiontoken' => new external_value(PARAM_RAW, 'New session token'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Reset position (always 0)'),
            'maxverifiedposition' => new external_value(PARAM_FLOAT, 'Reset verified position (always 0)'),
            'allowedposition' => new external_value(PARAM_FLOAT, 'Allowed seek position after reset'),
            'percentcomplete' => new external_value(PARAM_FLOAT, 'Percent complete (always 0)'),
            'completed' => new external_value(PARAM_BOOL, 'Completed flag (always false)'),
        ]);
    }
}
