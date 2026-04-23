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
 * Web service: mark activity complete.
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
 * Recalculate completion after playback ends.
 * @package mod_modernvideoplayer
 */
class mark_complete extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'currenttime' => new external_value(PARAM_FLOAT, 'Current playback time'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
            'sessiontoken' => new external_value(PARAM_RAW, 'Playback session token'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid course module id
     * @param float $currenttime playback position
     * @param float $duration duration
     * @param string $sessiontoken token
     * @return array
     */
    public static function execute(int $cmid, float $currenttime, float $duration, string $sessiontoken): array {
        $result = heartbeat::execute($cmid, $currenttime, $duration, false, 1.0, 'visible', $sessiontoken);
        return [
            'completed' => (bool) $result['completed'],
            'percentcomplete' => (float) $result['percentcomplete'],
            'maxverifiedposition' => (float) $result['maxverifiedposition'],
            'allowedposition' => (float) $result['allowedposition'],
            'sessiontoken' => (string) $result['sessiontoken'],
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'completed' => new external_value(PARAM_BOOL, 'Whether the user is complete'),
            'percentcomplete' => new external_value(PARAM_FLOAT, 'Percent complete'),
            'maxverifiedposition' => new external_value(PARAM_FLOAT, 'Verified frontier'),
            'allowedposition' => new external_value(PARAM_FLOAT, 'Maximum seek position'),
            'sessiontoken' => new external_value(PARAM_RAW, 'Active session token'),
        ]);
    }
}
