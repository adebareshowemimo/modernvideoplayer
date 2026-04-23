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
 * Restore steps for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore step definitions for mod_modernvideoplayer.
 * @package mod_modernvideoplayer
 */
class restore_modernvideoplayer_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure to restore.
     *
     * @return array
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $paths = [];
        $paths[] = new restore_path_element('modernvideoplayer', '/activity/modernvideoplayer');
        if ($userinfo) {
            $paths[] = new restore_path_element(
                'modernvideoplayer_progress',
                '/activity/modernvideoplayer/progresses/progress'
            );
            $paths[] = new restore_path_element(
                'modernvideoplayer_segment',
                '/activity/modernvideoplayer/progresses/progress/segments/segment'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore activity instance.
     *
     * @param array $data activity data
     * @return void
     */
    protected function process_modernvideoplayer($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('modernvideoplayer', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore progress row.
     *
     * @param array $data progress data
     * @return void
     */
    protected function process_modernvideoplayer_progress($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->modernvideoplayerid = $this->get_new_parentid('modernvideoplayer');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('modernvideoplayer_progress', $data);
        $this->set_mapping('modernvideoplayer_progress', $oldid, $newitemid);
    }

    /**
     * Restore a watched segment.
     *
     * @param array $data segment data
     * @return void
     */
    protected function process_modernvideoplayer_segment($data) {
        global $DB;

        $data = (object) $data;
        $data->progressid = $this->get_new_parentid('modernvideoplayer_progress');
        $DB->insert_record('modernvideoplayer_segments', $data);
    }

    /**
     * Restore related files.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_modernvideoplayer', 'intro', null);
        $this->add_related_files('mod_modernvideoplayer', 'video', null);
        $this->add_related_files('mod_modernvideoplayer', 'poster', null);
        $this->add_related_files('mod_modernvideoplayer', 'captions', null);
        $this->add_related_files('mod_modernvideoplayer', 'chapters', null);
    }
}
