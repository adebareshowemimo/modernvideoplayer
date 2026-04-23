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

namespace mod_modernvideoplayer;

use core\exception\moodle_exception;
use core_external\external_api;
use externallib_advanced_testcase;
use mod_modernvideoplayer\external\add_bookmark;
use mod_modernvideoplayer\external\delete_bookmark;
use mod_modernvideoplayer\external\list_bookmarks;
use mod_modernvideoplayer\local\bookmark_manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for learner bookmarks (manager + external WS).
 *
 * @package    mod_modernvideoplayer
 * @category   test
 * @copyright  2026 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_modernvideoplayer\local\bookmark_manager
 * @covers     \mod_modernvideoplayer\external\add_bookmark
 * @covers     \mod_modernvideoplayer\external\list_bookmarks
 * @covers     \mod_modernvideoplayer\external\delete_bookmark
 */
final class bookmarks_test extends externallib_advanced_testcase {
    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $cm;

    /** @var stdClass */
    private $instance;

    /** @var stdClass */
    private $student;

    /** @var stdClass */
    private $otherstudent;

    /** @var int */
    private $cmid;

    /**
     * Build a course with one video activity and two enrolled learners.
     */
    protected function setUp(): void {
        global $CFG, $DB;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        require_once($CFG->dirroot . '/webservice/tests/helpers.php');

        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->otherstudent = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        $this->cm = $this->getDataGenerator()->create_module('modernvideoplayer', [
            'course' => $this->course->id,
            'name'   => 'Bookmark test video',
        ]);
        $this->instance = $DB->get_record('modernvideoplayer', ['id' => $this->cm->id], '*', MUST_EXIST);
        $this->instance->id = (int) $this->instance->id;
        $this->cmid = (int) $this->cm->cmid;
    }

    /**
     * bookmark_manager::add persists and round-trips bookmarks.
     */
    public function test_manager_add_persists_bookmark(): void {
        $manager = new bookmark_manager();
        $row = $manager->add($this->instance->id, (int) $this->student->id, 42.5, '  Key moment  ');

        $this->assertGreaterThan(0, $row->id);
        $this->assertSame('Key moment', $row->label);
        $this->assertEqualsWithDelta(42.5, (float) $row->position, 0.01);
    }

    /**
     * Empty or whitespace-only labels must be rejected.
     */
    public function test_manager_rejects_empty_label(): void {
        $manager = new bookmark_manager();
        $this->expectException(moodle_exception::class);
        $manager->add($this->instance->id, (int) $this->student->id, 10.0, '   ');
    }

    /**
     * Negative positions must clamp to zero, not reject the call.
     */
    public function test_manager_clamps_negative_position(): void {
        $manager = new bookmark_manager();
        $row = $manager->add($this->instance->id, (int) $this->student->id, -5.0, 'Intro');

        $this->assertSame(0.0, (float) $row->position);
    }

    /**
     * Bookmarks are ordered by position on listing.
     */
    public function test_manager_lists_ordered_by_position(): void {
        $manager = new bookmark_manager();
        $manager->add($this->instance->id, (int) $this->student->id, 30.0, 'Middle');
        $manager->add($this->instance->id, (int) $this->student->id, 5.0, 'Start');
        $manager->add($this->instance->id, (int) $this->student->id, 90.0, 'End');

        $rows = $manager->list_for_user($this->instance->id, (int) $this->student->id);

        $this->assertCount(3, $rows);
        $this->assertSame('Start', $rows[0]->label);
        $this->assertSame('Middle', $rows[1]->label);
        $this->assertSame('End', $rows[2]->label);
    }

    /**
     * delete_own only removes the caller own bookmark.
     */
    public function test_manager_delete_own_only(): void {
        $manager = new bookmark_manager();
        $mine = $manager->add($this->instance->id, (int) $this->student->id, 10.0, 'Mine');
        $theirs = $manager->add($this->instance->id, (int) $this->otherstudent->id, 10.0, 'Theirs');

        // Attempt to delete the other student's bookmark as the first student.
        $this->assertFalse($manager->delete_own((int) $theirs->id, (int) $this->student->id));

        // Deleting the caller own bookmark succeeds.
        $this->assertTrue($manager->delete_own((int) $mine->id, (int) $this->student->id));

        $remaining = $manager->list_for_user($this->instance->id, (int) $this->otherstudent->id);
        $this->assertCount(1, $remaining);
    }

    /**
     * delete_for_activity purges every bookmark belonging to the activity.
     */
    public function test_manager_delete_for_activity_clears_all_users(): void {
        global $DB;

        $manager = new bookmark_manager();
        $manager->add($this->instance->id, (int) $this->student->id, 1.0, 'A');
        $manager->add($this->instance->id, (int) $this->otherstudent->id, 2.0, 'B');

        $manager->delete_for_activity($this->instance->id);

        $this->assertSame(
            0,
            $DB->count_records('modernvideoplayer_bookmarks', ['modernvideoplayerid' => $this->instance->id])
        );
    }

    /**
     * add_bookmark web service writes and returns the saved row.
     */
    public function test_ws_add_bookmark(): void {
        $this->setUser($this->student);

        $result = add_bookmark::execute($this->cmid, 12.75, 'Favourite bit');
        $result = external_api::clean_returnvalue(add_bookmark::execute_returns(), $result);

        $this->assertGreaterThan(0, $result['id']);
        $this->assertSame('Favourite bit', $result['label']);
        $this->assertEqualsWithDelta(12.75, (float) $result['position'], 0.01);
    }

    /**
     * list_bookmarks web service returns only the current user bookmarks.
     */
    public function test_ws_list_bookmarks_scoped_to_user(): void {
        $manager = new bookmark_manager();
        $manager->add($this->instance->id, (int) $this->student->id, 5.0, 'Student bookmark');
        $manager->add($this->instance->id, (int) $this->otherstudent->id, 10.0, 'Other bookmark');

        $this->setUser($this->student);
        $result = list_bookmarks::execute($this->cmid);
        $result = external_api::clean_returnvalue(list_bookmarks::execute_returns(), $result);

        $this->assertArrayHasKey('bookmarks', $result);
        $this->assertCount(1, $result['bookmarks']);
        $this->assertSame('Student bookmark', $result['bookmarks'][0]['label']);
    }

    /**
     * delete_bookmark web service refuses to delete other users bookmarks.
     */
    public function test_ws_delete_bookmark_refuses_other_owners(): void {
        $manager = new bookmark_manager();
        $other = $manager->add($this->instance->id, (int) $this->otherstudent->id, 5.0, 'Locked');

        $this->setUser($this->student);
        $result = delete_bookmark::execute($this->cmid, (int) $other->id);
        $result = external_api::clean_returnvalue(delete_bookmark::execute_returns(), $result);

        $this->assertFalse($result['deleted']);

        // The other user bookmark must still exist.
        $still = $manager->list_for_user($this->instance->id, (int) $this->otherstudent->id);
        $this->assertCount(1, $still);
    }

    /**
     * Bookmarks are wiped when the activity is deleted.
     */
    public function test_bookmarks_are_purged_with_instance(): void {
        global $DB;

        $manager = new bookmark_manager();
        $manager->add($this->instance->id, (int) $this->student->id, 1.0, 'A');

        course_delete_module($this->cmid);

        $this->assertSame(
            0,
            $DB->count_records('modernvideoplayer_bookmarks', ['modernvideoplayerid' => $this->instance->id])
        );
    }
}
