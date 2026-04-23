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
 * Backup steps for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup step definitions for mod_modernvideoplayer.
 * @package mod_modernvideoplayer
 */
class backup_modernvideoplayer_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $modernvideoplayer = new backup_nested_element('modernvideoplayer', ['id'], [
            'name', 'intro', 'introformat', 'videoitemid', 'posteritemid', 'requiredpercent',
            'completionmode', 'allowresume', 'allowrewind', 'allowfullscreen', 'autoplay',
            'defaultcaptionlang', 'allowplaybackspeed',
            'maxplaybackspeed', 'graceseconds', 'heartbeatinterval', 'forceservervalidation',
            'strictendvalidation', 'showsuspiciousflags', 'timecreated', 'timemodified',
        ]);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'sessiontoken', 'duration', 'lastposition', 'maxverifiedposition',
            'totalsecondswatched', 'percentcomplete', 'completed', 'completiontime', 'timecreated',
            'lastheartbeat', 'lastplaybackrate', 'lastvisibility', 'suspiciousflags',
            'integrityfailures', 'timemodified',
        ]);

        $segments = new backup_nested_element('segments');
        $segment = new backup_nested_element('segment', ['id'], [
            'segmentstart', 'segmentend', 'watchedseconds', 'timecreated',
        ]);

        $bookmarks = new backup_nested_element('bookmarks');
        $bookmark = new backup_nested_element('bookmark', ['id'], [
            'userid', 'position', 'label', 'timecreated', 'timemodified',
        ]);

        $modernvideoplayer->add_child($progresses);
        $progresses->add_child($progress);
        $progress->add_child($segments);
        $segments->add_child($segment);
        $modernvideoplayer->add_child($bookmarks);
        $bookmarks->add_child($bookmark);

        $modernvideoplayer->set_source_table('modernvideoplayer', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $progress->set_source_table('modernvideoplayer_progress', ['modernvideoplayerid' => backup::VAR_PARENTID], 'id ASC');
            $segment->set_source_table('modernvideoplayer_segments', ['progressid' => backup::VAR_PARENTID], 'id ASC');
            $bookmark->set_source_table('modernvideoplayer_bookmarks', ['modernvideoplayerid' => backup::VAR_PARENTID], 'id ASC');
        }

        $progress->annotate_ids('user', 'userid');
        $bookmark->annotate_ids('user', 'userid');
        $modernvideoplayer->annotate_files('mod_modernvideoplayer', 'intro', null);
        $modernvideoplayer->annotate_files('mod_modernvideoplayer', 'video', null);
        $modernvideoplayer->annotate_files('mod_modernvideoplayer', 'poster', null);
        $modernvideoplayer->annotate_files('mod_modernvideoplayer', 'captions', null);
        $modernvideoplayer->annotate_files('mod_modernvideoplayer', 'chapters', null);

        return $this->prepare_activity_structure($modernvideoplayer);
    }
}
