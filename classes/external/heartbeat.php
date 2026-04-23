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
 * Web service: process playback heartbeat.
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
 * Process a playback heartbeat.
 * @package mod_modernvideoplayer
 */
class heartbeat extends external_api {
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
            'playing' => new external_value(PARAM_BOOL, 'Whether the video is playing'),
            'playbackrate' => new external_value(PARAM_FLOAT, 'Current playback rate'),
            'visibility' => new external_value(PARAM_ALPHA, 'Tab visibility state'),
            'sessiontoken' => new external_value(PARAM_RAW, 'Playback session token'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid course module id
     * @param float $currenttime time
     * @param float $duration duration
     * @param bool $playing playing
     * @param float $playbackrate rate
     * @param string $visibility visibility
     * @param string $sessiontoken token
     * @return array
     */
    public static function execute(
        int $cmid,
        float $currenttime,
        float $duration,
        bool $playing,
        float $playbackrate,
        string $visibility,
        string $sessiontoken
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'currenttime' => $currenttime,
            'duration' => $duration,
            'playing' => $playing,
            'playbackrate' => $playbackrate,
            'visibility' => $visibility,
            'sessiontoken' => $sessiontoken,
        ]);

        [$course, $cm, $instance] = modernvideoplayer_get_course_module_and_instance($params['cmid']);
        require_course_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/modernvideoplayer:submitprogress', $context);

        $payload = (object) $params;
        $manager = new playback_manager();
        $result = $manager->heartbeat($course, $cm, $instance, $USER->id, $payload);

        return (array) $result;
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status'),
            'allowedposition' => new external_value(PARAM_FLOAT, 'Maximum immediate seek position'),
            'maxverifiedposition' => new external_value(PARAM_FLOAT, 'Updated verified frontier'),
            'percentcomplete' => new external_value(PARAM_FLOAT, 'Updated completion percent'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the user is complete'),
            'suspiciousflags' => new external_value(PARAM_INT, 'Suspicious flag count'),
            'integrityfailures' => new external_value(PARAM_INT, 'Integrity failure count'),
            'sessiontoken' => new external_value(PARAM_RAW, 'Active session token'),
        ]);
    }
}
