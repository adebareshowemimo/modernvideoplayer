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

namespace mod_modernvideoplayer\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_modernvideoplayer\local\bookmark_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/modernvideoplayer/locallib.php');

/**
 * Web service: list the current user's bookmarks for an activity.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_bookmarks extends external_api {
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
     * List the current user's bookmarks for an activity.
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

        $manager = new bookmark_manager();
        $rows = $manager->list_for_user((int) $instance->id, (int) $USER->id);

        $bookmarks = array_map(static function ($row) {
            return [
                'id'           => (int) $row->id,
                'position'     => (float) $row->position,
                'label'        => (string) $row->label,
                'timecreated'  => (int) $row->timecreated,
                'timemodified' => (int) $row->timemodified,
            ];
        }, $rows);

        return ['bookmarks' => $bookmarks];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'bookmarks' => new external_multiple_structure(
                new external_single_structure([
                    'id'           => new external_value(PARAM_INT, 'Bookmark id'),
                    'position'     => new external_value(PARAM_FLOAT, 'Bookmark position in seconds'),
                    'label'        => new external_value(PARAM_TEXT, 'Bookmark label'),
                    'timecreated'  => new external_value(PARAM_INT, 'Creation timestamp'),
                    'timemodified' => new external_value(PARAM_INT, 'Modification timestamp'),
                ])
            ),
        ]);
    }
}
