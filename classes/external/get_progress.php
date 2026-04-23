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
 * Web service: get playback progress.
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
 * Return learner progress and activity configuration.
 * @package mod_modernvideoplayer
 */
class get_progress extends external_api {
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
        require_capability('mod/modernvideoplayer:view', $context);

        $manager = new playback_manager();
        $progress = $manager->get_state($instance, $USER->id);

        $videofile = modernvideoplayer_get_file($context, 'video');
        $posterfile = modernvideoplayer_get_file($context, 'poster');

        return [
            'cmid' => $cm->id,
            'sessiontoken' => (string) $progress->sessiontoken,
            'lastposition' => (float) $progress->lastposition,
            'maxverifiedposition' => (float) $progress->maxverifiedposition,
            'allowedposition' => (float) $progress->maxverifiedposition + (int) $instance->graceseconds,
            'totalsecondswatched' => (float) $progress->totalsecondswatched,
            'percentcomplete' => (float) $progress->percentcomplete,
            'completed' => (bool) $progress->completed,
            'duration' => (float) $progress->duration,
            'requiredpercent' => (float) $instance->requiredpercent,
            'heartbeatinterval' => (int) $instance->heartbeatinterval,
            'graceseconds' => (int) $instance->graceseconds,
            'allowresume' => (bool) $instance->allowresume,
            'allowrewind' => (bool) $instance->allowrewind,
            'allowfullscreen' => (bool) $instance->allowfullscreen,
            'autoplay' => (string) $instance->autoplay,
            'allowplaybackspeed' => (bool) $instance->allowplaybackspeed,
            'maxplaybackspeed' => (float) $instance->maxplaybackspeed,
            'forceservervalidation' => (bool) $instance->forceservervalidation,
            'strictendvalidation' => (bool) $instance->strictendvalidation,
            'videourl' => $videofile ? modernvideoplayer_file_url($videofile)->out(false) : '',
            'posterurl' => $posterfile ? modernvideoplayer_file_url($posterfile)->out(false) : '',
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'sessiontoken' => new external_value(PARAM_RAW, 'Active playback session token'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Last known position'),
            'maxverifiedposition' => new external_value(PARAM_FLOAT, 'Last verified position'),
            'allowedposition' => new external_value(PARAM_FLOAT, 'Maximum immediate seek position'),
            'totalsecondswatched' => new external_value(PARAM_FLOAT, 'Total validated watched seconds'),
            'percentcomplete' => new external_value(PARAM_FLOAT, 'Completion percentage'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the user is complete'),
            'duration' => new external_value(PARAM_FLOAT, 'Last known duration'),
            'requiredpercent' => new external_value(PARAM_FLOAT, 'Required percentage'),
            'heartbeatinterval' => new external_value(PARAM_INT, 'Heartbeat interval'),
            'graceseconds' => new external_value(PARAM_INT, 'Seek tolerance'),
            'allowresume' => new external_value(PARAM_BOOL, 'Resume enabled'),
            'allowrewind' => new external_value(PARAM_BOOL, 'Rewind enabled'),
            'allowfullscreen' => new external_value(PARAM_BOOL, 'Fullscreen enabled'),
            'autoplay' => new external_value(PARAM_ALPHA, 'Autoplay mode: off, muted or unmuted'),
            'allowplaybackspeed' => new external_value(PARAM_BOOL, 'Speed control enabled'),
            'maxplaybackspeed' => new external_value(PARAM_FLOAT, 'Maximum playback speed'),
            'forceservervalidation' => new external_value(PARAM_BOOL, 'Server validation enforced'),
            'strictendvalidation' => new external_value(PARAM_BOOL, 'Strict end validation enabled'),
            'videourl' => new external_value(PARAM_RAW, 'Protected video URL', VALUE_OPTIONAL),
            'posterurl' => new external_value(PARAM_RAW, 'Poster URL', VALUE_OPTIONAL),
        ]);
    }
}
