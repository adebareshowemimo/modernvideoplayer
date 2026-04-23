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
 * External web service tests for mod_modernvideoplayer.
 *
 * @package    mod_modernvideoplayer
 * @category   test
 * @copyright  2026 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_modernvideoplayer\external;

use context_module;
use core_external\external_api;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the plugin's external web service endpoints.
 *
 * @covers \mod_modernvideoplayer\external\get_progress
 * @covers \mod_modernvideoplayer\external\heartbeat
 * @covers \mod_modernvideoplayer\external\mark_complete
 * @covers \mod_modernvideoplayer\external\reset_progress
 */
final class external_test extends externallib_advanced_testcase {
    /** @var \stdClass */
    private $course;

    /** @var \stdClass */
    private $cm;

    /** @var \stdClass */
    private $instance;

    /** @var context_module */
    private $context;

    /** @var \stdClass */
    private $student;

    /**
     * Shared fixture: course + activity + enrolled student.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course();
        $this->instance = $this->getDataGenerator()->create_module('modernvideoplayer', [
            'course' => $this->course->id,
            'name' => 'External WS test video',
            'requiredpercent' => 100.0,
            'graceseconds' => 3,
            'heartbeatinterval' => 15,
            'forceservervalidation' => 1,
        ]);
        $this->cm = get_coursemodule_from_instance('modernvideoplayer', $this->instance->id);
        $this->context = context_module::instance($this->cm->id);

        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, 'student');
    }

    /**
     * get_progress returns a fresh session token and zeroed state for a new learner.
     */
    public function test_get_progress_initial_state(): void {
        $this->setUser($this->student);

        $result = get_progress::execute($this->cm->id);
        $result = external_api::clean_returnvalue(get_progress::execute_returns(), $result);

        $this->assertEquals($this->cm->id, $result['cmid']);
        $this->assertNotEmpty($result['sessiontoken']);
        $this->assertSame(0.0, (float) $result['lastposition']);
        $this->assertSame(0.0, (float) $result['maxverifiedposition']);
        $this->assertFalse((bool) $result['completed']);
        $this->assertIsFloat((float) $result['requiredpercent']);
        $this->assertSame(15, (int) $result['heartbeatinterval']);
    }

    /**
     * get_progress blocks a user who is not enrolled in the course.
     */
    public function test_get_progress_requires_course_access(): void {
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);

        // Unenrolled users hit require_course_login, which either throws a
        // moodle_exception (redirect blocked in CLI) or a require_login_exception.
        $this->expectException(\core\exception\moodle_exception::class);
        get_progress::execute($this->cm->id);
    }

    /**
     * Heartbeat returns a valid status and session token for an authenticated learner.
     */
    public function test_heartbeat_returns_status(): void {
        $this->setUser($this->student);

        $initial = get_progress::execute($this->cm->id);
        $initial = external_api::clean_returnvalue(get_progress::execute_returns(), $initial);
        $token = $initial['sessiontoken'];

        $beat = heartbeat::execute(
            $this->cm->id,
            10.0,
            120.0,
            true,
            1.0,
            'visible',
            $token
        );
        $beat = external_api::clean_returnvalue(heartbeat::execute_returns(), $beat);

        $this->assertArrayHasKey('status', $beat);
        $this->assertArrayHasKey('maxverifiedposition', $beat);
        $this->assertArrayHasKey('sessiontoken', $beat);
        $this->assertNotEmpty($beat['sessiontoken']);
        $this->assertGreaterThanOrEqual(0.0, (float) $beat['maxverifiedposition']);
    }

    /**
     * Heartbeat returns a well-formed response even when given a bogus session token.
     */
    public function test_heartbeat_accepts_any_token_shape(): void {
        $this->setUser($this->student);

        // Prime a session so the user has a valid progress row.
        $initial = get_progress::execute($this->cm->id);
        external_api::clean_returnvalue(get_progress::execute_returns(), $initial);

        $beat = heartbeat::execute(
            $this->cm->id,
            5.0,
            120.0,
            true,
            1.0,
            'visible',
            'definitely-not-a-real-token'
        );
        $beat = external_api::clean_returnvalue(heartbeat::execute_returns(), $beat);

        // Manager always returns a well-formed struct; we only assert the shape
        // since token-mismatch handling may re-issue a token rather than error.
        $this->assertArrayHasKey('status', $beat);
        $this->assertArrayHasKey('sessiontoken', $beat);
        $this->assertNotEmpty($beat['sessiontoken']);
    }

    /**
     * mark_complete delegates to heartbeat and returns a completion snapshot.
     */
    public function test_mark_complete_returns_snapshot(): void {
        $this->setUser($this->student);

        $initial = get_progress::execute($this->cm->id);
        $initial = external_api::clean_returnvalue(get_progress::execute_returns(), $initial);
        $token = $initial['sessiontoken'];

        $result = mark_complete::execute($this->cm->id, 120.0, 120.0, $token);
        $result = external_api::clean_returnvalue(mark_complete::execute_returns(), $result);

        $this->assertArrayHasKey('completed', $result);
        $this->assertArrayHasKey('percentcomplete', $result);
        $this->assertArrayHasKey('sessiontoken', $result);
        $this->assertNotEmpty($result['sessiontoken']);
    }

    /**
     * reset_progress zeroes the learner state and issues a fresh session token.
     */
    public function test_reset_progress_zeroes_state(): void {
        global $DB;
        $this->setUser($this->student);

        // Prime a session + advance progress manually so reset has something to clear.
        $initial = get_progress::execute($this->cm->id);
        $initial = external_api::clean_returnvalue(get_progress::execute_returns(), $initial);
        $oldtoken = $initial['sessiontoken'];

        $DB->set_field(
            'modernvideoplayer_progress',
            'maxverifiedposition',
            45.0,
            ['modernvideoplayerid' => $this->instance->id, 'userid' => $this->student->id]
        );

        $result = reset_progress::execute($this->cm->id);
        $result = external_api::clean_returnvalue(reset_progress::execute_returns(), $result);

        $this->assertSame(0.0, (float) $result['lastposition']);
        $this->assertSame(0.0, (float) $result['maxverifiedposition']);
        $this->assertSame(0.0, (float) $result['percentcomplete']);
        $this->assertFalse((bool) $result['completed']);
        $this->assertNotEmpty($result['sessiontoken']);
        $this->assertNotSame($oldtoken, $result['sessiontoken']);
    }

    /**
     * reset_progress blocks users who are not enrolled.
     */
    public function test_reset_progress_requires_course_access(): void {
        $guest = $this->getDataGenerator()->create_user();
        $this->setUser($guest);

        $this->expectException(\core\exception\moodle_exception::class);
        reset_progress::execute($this->cm->id);
    }
}
