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
 * PHPUnit data generator for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generator for mod_modernvideoplayer.
 */
class mod_modernvideoplayer_generator extends testing_module_generator {
    /**
     * Create an activity instance with sensible defaults.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'name' => 'Modern video player test',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'requiredpercent' => 100.0,
            'completionmode' => 0,
            'allowresume' => 1,
            'allowrewind' => 1,
            'allowfullscreen' => 1,
            'autoplay' => 'unmuted',
            'defaultcaptionlang' => 'en',
            'showprimarynav' => 0,
            'showsecondarynav' => 0,
            'showcourseindex' => 0,
            'showrightblocks' => 0,
            'titleposition' => 'left',
            'showcontroltext' => 1,
            'allowplaybackspeed' => 1,
            'maxplaybackspeed' => 1.0,
            'graceseconds' => 3,
            'heartbeatinterval' => 15,
            'forceservervalidation' => 1,
            'strictendvalidation' => 0,
            'showsuspiciousflags' => 1,
            'video' => 0,
            'posterimage' => 0,
            'captions' => 0,
            'chapters' => 0,
        ];
        foreach ($defaults as $key => $value) {
            if (!isset($record->$key)) {
                $record->$key = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }
}
