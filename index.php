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
 * List modern video player instances in a course.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2025 Adebare Showemmo | adebareshowemimo@gmail.com | support@agunfoninteractivity.com | www.agunfoninteractivity.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/modernvideoplayer/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('modernvideoplayer', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'modernvideoplayer'));

if (!$instances) {
    echo $OUTPUT->notification(get_string('noentries', 'modernvideoplayer'));
    echo $OUTPUT->footer();
    die();
}

$table = new html_table();
$table->head = [get_string('name'), get_string('lastmodified')];
foreach ($instances as $instance) {
    $url = new moodle_url('/mod/modernvideoplayer/view.php', ['id' => $instance->coursemodule]);
    $table->data[] = [
        html_writer::link($url, format_string($instance->name)),
        userdate($instance->timemodified),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
