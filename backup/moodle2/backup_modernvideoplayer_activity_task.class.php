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
 * Backup task for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/modernvideoplayer/backup/moodle2/backup_modernvideoplayer_stepslib.php');

/**
 * Backup task for mod_modernvideoplayer.
 * @package mod_modernvideoplayer
 */
class backup_modernvideoplayer_activity_task extends backup_activity_task {
    /**
     * Define custom settings.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Define backup steps.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new backup_modernvideoplayer_activity_structure_step(
            'modernvideoplayer_structure',
            'modernvideoplayer.xml'
        ));
    }

    /**
     * Encode module links.
     *
     * @param string $content content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace(
            "/(" . $base . "\/mod\/modernvideoplayer\/index.php\?id\=)([0-9]+)/",
            '$@MODERNVIDEOPLAYERINDEX*$2@$',
            $content
        );
        $content = preg_replace(
            "/(" . $base . "\/mod\/modernvideoplayer\/view.php\?id\=)([0-9]+)/",
            '$@MODERNVIDEOPLAYERVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}
