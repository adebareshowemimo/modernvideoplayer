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
 * Restore task for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/modernvideoplayer/backup/moodle2/restore_modernvideoplayer_stepslib.php');

/**
 * Restore task for mod_modernvideoplayer.
 * @package mod_modernvideoplayer
 */
class restore_modernvideoplayer_activity_task extends restore_activity_task {
    /**
     * Define custom settings.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Define restore steps.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_modernvideoplayer_activity_structure_step(
            'modernvideoplayer_structure',
            'modernvideoplayer.xml'
        ));
    }

    /**
     * Define decode content.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('modernvideoplayer', ['intro'], 'modernvideoplayer'),
        ];
    }

    /**
     * Define decode rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('MODERNVIDEOPLAYERVIEWBYID', '/mod/modernvideoplayer/view.php?id=$1', 'course_module'),
            new restore_decode_rule('MODERNVIDEOPLAYERINDEX', '/mod/modernvideoplayer/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Restore logs.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('modernvideoplayer', 'add', 'view.php?id={course_module}', '{modernvideoplayer}'),
            new restore_log_rule('modernvideoplayer', 'update', 'view.php?id={course_module}', '{modernvideoplayer}'),
            new restore_log_rule('modernvideoplayer', 'view', 'view.php?id={course_module}', '{modernvideoplayer}'),
        ];
    }

    /**
     * Restore course-level logs.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('modernvideoplayer', 'view all', 'index.php?id={course}', null),
        ];
    }
}
