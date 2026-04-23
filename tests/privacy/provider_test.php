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
 * Privacy provider tests.
 *
 * @package    mod_modernvideoplayer
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\privacy;

use context_module;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider test suite for mod_modernvideoplayer.
 *
 * @covers \mod_modernvideoplayer\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var \stdClass */
    private $course;

    /** @var \stdClass */
    private $cm;

    /** @var context_module */
    private $context;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('modernvideoplayer', [
            'course' => $this->course->id,
            'name' => 'Privacy test video',
        ]);
        $this->cm = get_coursemodule_from_instance('modernvideoplayer', $instance->id);
        $this->context = context_module::instance($this->cm->id);
    }

    /**
     * get_metadata() advertises both learner tables.
     */
    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('mod_modernvideoplayer');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        $tablenames = [];
        foreach ($items as $item) {
            $tablenames[] = $item->get_name();
        }
        $this->assertContains('modernvideoplayer_progress', $tablenames);
        $this->assertContains('modernvideoplayer_segments', $tablenames);
        $this->assertContains('modernvideoplayer_bookmarks', $tablenames);
    }

    /**
     * get_contexts_for_userid() returns the activity context for a learner with bookmarks only.
     */
    public function test_get_contexts_for_userid_bookmarks_only(): void {
        $user = $this->create_bookmark_for_new_user();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($this->context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * get_users_in_context() returns a user with bookmarks even when they have no progress row.
     */
    public function test_get_users_in_context_bookmarks_only(): void {
        $user = $this->create_bookmark_for_new_user();

        $userlist = new userlist($this->context, 'mod_modernvideoplayer');
        provider::get_users_in_context($userlist);

        $this->assertEquals([$user->id], $userlist->get_userids());
    }

    /**
     * export_user_data() writes bookmark data for the user.
     */
    public function test_export_user_data_includes_bookmarks(): void {
        [$user, $context] = $this->create_progress_for_new_user();
        $this->create_bookmark_for_user($user);

        $approvedlist = new approved_contextlist($user, 'mod_modernvideoplayer', [$context->id]);
        provider::export_user_data($approvedlist);

        $writer = writer::with_context($context);
        $bookmarks = $writer->get_data(['bookmarks']);
        $this->assertNotEmpty($bookmarks);
        $this->assertObjectHasProperty('bookmarks', $bookmarks);
        $this->assertCount(1, $bookmarks->bookmarks);
        $this->assertEquals('Intro', $bookmarks->bookmarks[0]->label);
    }

    /**
     * delete_data_for_all_users_in_context() also clears bookmarks.
     */
    public function test_delete_data_for_all_users_in_context_bookmarks(): void {
        global $DB;

        $this->create_bookmark_for_new_user();
        $this->create_bookmark_for_new_user();
        $this->assertEquals(2, $DB->count_records('modernvideoplayer_bookmarks'));

        provider::delete_data_for_all_users_in_context($this->context);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_bookmarks'));
    }

    /**
     * delete_data_for_user() removes only the target learner's bookmarks.
     */
    public function test_delete_data_for_user_bookmarks(): void {
        global $DB;

        $user1 = $this->create_bookmark_for_new_user();
        $user2 = $this->create_bookmark_for_new_user();

        $approvedlist = new approved_contextlist($user1, 'mod_modernvideoplayer', [$this->context->id]);
        provider::delete_data_for_user($approvedlist);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_bookmarks', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('modernvideoplayer_bookmarks', ['userid' => $user2->id]));
    }

    /**
     * delete_data_for_users() removes only the supplied learners' bookmarks.
     */
    public function test_delete_data_for_users_bookmarks(): void {
        global $DB;

        $user1 = $this->create_bookmark_for_new_user();
        $user2 = $this->create_bookmark_for_new_user();

        $approvedlist = new approved_userlist($this->context, 'mod_modernvideoplayer', [$user1->id]);
        provider::delete_data_for_users($approvedlist);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_bookmarks', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('modernvideoplayer_bookmarks', ['userid' => $user2->id]));
    }

    /**
     * get_contexts_for_userid() returns the activity context for a learner with progress.
     */
    public function test_get_contexts_for_userid(): void {
        [$user, $context] = $this->create_progress_for_new_user();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * get_users_in_context() returns the learner who has progress in the module.
     */
    public function test_get_users_in_context(): void {
        [$user, $context] = $this->create_progress_for_new_user();

        $userlist = new userlist($context, 'mod_modernvideoplayer');
        provider::get_users_in_context($userlist);

        $this->assertEquals([$user->id], $userlist->get_userids());
    }

    /**
     * export_user_data() writes progress and segment data to the export.
     */
    public function test_export_user_data(): void {
        [$user, $context] = $this->create_progress_for_new_user();

        $approvedlist = new approved_contextlist($user, 'mod_modernvideoplayer', [$context->id]);
        provider::export_user_data($approvedlist);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([]);
        $this->assertNotEmpty($data);
        $this->assertObjectHasProperty('progress', $data);
        $this->assertEquals(60.5, (float) $data->progress->lastposition);
        $this->assertEquals(45.0, (float) $data->progress->maxverifiedposition);

        $segments = $writer->get_data(['segments']);
        $this->assertNotEmpty($segments);
        $this->assertObjectHasProperty('segments', $segments);
        $this->assertCount(1, $segments->segments);
    }

    /**
     * delete_data_for_all_users_in_context() removes all learner data in the module.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        [, $context] = $this->create_progress_for_new_user();
        $this->create_progress_for_new_user();

        $this->assertEquals(2, $DB->count_records('modernvideoplayer_progress'));
        $this->assertEquals(2, $DB->count_records('modernvideoplayer_segments'));

        provider::delete_data_for_all_users_in_context($context);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_progress'));
        $this->assertEquals(0, $DB->count_records('modernvideoplayer_segments'));
    }

    /**
     * delete_data_for_user() removes only the requested learner's data.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        [$user1, $context] = $this->create_progress_for_new_user();
        [$user2] = $this->create_progress_for_new_user();

        $approvedlist = new approved_contextlist($user1, 'mod_modernvideoplayer', [$context->id]);
        provider::delete_data_for_user($approvedlist);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_progress', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('modernvideoplayer_progress', ['userid' => $user2->id]));
    }

    /**
     * delete_data_for_users() removes only the supplied learners' data.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        [$user1, $context] = $this->create_progress_for_new_user();
        [$user2] = $this->create_progress_for_new_user();

        $approvedlist = new approved_userlist($context, 'mod_modernvideoplayer', [$user1->id]);
        provider::delete_data_for_users($approvedlist);

        $this->assertEquals(0, $DB->count_records('modernvideoplayer_progress', ['userid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records('modernvideoplayer_progress', ['userid' => $user2->id]));
    }

    /**
     * Helper: create a new enrolled user with progress + segment data.
     *
     * @return array{0: \stdClass, 1: context_module}
     */
    protected function create_progress_for_new_user(): array {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, 'student');

        $now = time();
        $progressid = $DB->insert_record('modernvideoplayer_progress', (object) [
            'modernvideoplayerid' => $this->cm->instance,
            'userid' => $user->id,
            'sessiontoken' => 'sess_' . $user->id,
            'duration' => 120.0,
            'lastposition' => 60.5,
            'maxverifiedposition' => 45.0,
            'totalsecondswatched' => 45.0,
            'percentcomplete' => 37.5,
            'completed' => 0,
            'completiontime' => 0,
            'timecreated' => $now,
            'lastheartbeat' => $now,
            'lastplaybackrate' => 1.0,
            'lastvisibility' => 'visible',
            'suspiciousflags' => 0,
            'integrityfailures' => 0,
        ]);
        $DB->insert_record('modernvideoplayer_segments', (object) [
            'progressid' => $progressid,
            'segmentstart' => 0.0,
            'segmentend' => 45.0,
            'watchedseconds' => 45.0,
            'timecreated' => $now,
        ]);

        return [$user, $this->context];
    }

    /**
     * Helper: create a new enrolled user with a bookmark only (no progress row).
     *
     * @return \stdClass the created user
     */
    protected function create_bookmark_for_new_user(): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, 'student');
        $this->create_bookmark_for_user($user);
        return $user;
    }

    /**
     * Helper: insert a bookmark for an existing user in the current activity.
     *
     * @param \stdClass $user
     * @return int inserted bookmark id
     */
    protected function create_bookmark_for_user(\stdClass $user): int {
        global $DB;

        $now = time();
        return $DB->insert_record('modernvideoplayer_bookmarks', (object) [
            'modernvideoplayerid' => $this->cm->instance,
            'userid' => $user->id,
            'position' => 12.5,
            'label' => 'Intro',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
