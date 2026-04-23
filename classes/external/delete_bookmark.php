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
use core_external\external_single_structure;
use core_external\external_value;
use mod_modernvideoplayer\local\bookmark_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/modernvideoplayer/locallib.php');

/**
 * Web service: delete one of the current user's own bookmarks.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_bookmark extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'       => new external_value(PARAM_INT, 'Course module id'),
            'bookmarkid' => new external_value(PARAM_INT, 'Bookmark id to delete'),
        ]);
    }

    /**
     * Delete a bookmark owned by the current user.
     *
     * @param int $cmid course module id
     * @param int $bookmarkid bookmark id
     * @return array
     */
    public static function execute(int $cmid, int $bookmarkid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'       => $cmid,
            'bookmarkid' => $bookmarkid,
        ]);

        [$course, $cm, $instance] = modernvideoplayer_get_course_module_and_instance($params['cmid']);
        require_course_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/modernvideoplayer:submitprogress', $context);

        $manager = new bookmark_manager();
        $deleted = $manager->delete_own($params['bookmarkid'], (int) $USER->id);

        return ['deleted' => $deleted];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Whether the bookmark was removed'),
        ]);
    }
}
