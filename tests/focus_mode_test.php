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

/**
 * Tests for focus-mode enforcement instance settings.
 *
 * @package    mod_modernvideoplayer
 * @category   test
 * @copyright  2026 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::modernvideoplayer_get_defaults
 */
final class focus_mode_test extends \advanced_testcase {
    /**
     * Generator writes the new fields to the DB with the expected defaults.
     */
    public function test_instance_defaults_persisted(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('modernvideoplayer', ['course' => $course->id]);
        $instance = $DB->get_record('modernvideoplayer', ['id' => $module->id], '*', MUST_EXIST);

        $this->assertSame(0, (int) $instance->enforcefocus);
        $this->assertSame(1, (int) $instance->allowpip);
        $this->assertSame(1, (int) $instance->allowtranscriptdownload);
    }

    /**
     * Enabling focus mode persists and can coexist with other flags.
     */
    public function test_instance_with_focus_enabled(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('modernvideoplayer', [
            'course'                  => $course->id,
            'enforcefocus'            => 1,
            'allowpip'                => 0,
            'allowtranscriptdownload' => 0,
        ]);
        $instance = $DB->get_record('modernvideoplayer', ['id' => $module->id], '*', MUST_EXIST);

        $this->assertSame(1, (int) $instance->enforcefocus);
        $this->assertSame(0, (int) $instance->allowpip);
        $this->assertSame(0, (int) $instance->allowtranscriptdownload);
    }

    /**
     * The admin defaults array exposes the three new switches.
     */
    public function test_defaults_array_exposes_new_switches(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/modernvideoplayer/locallib.php');

        $this->resetAfterTest();

        $defaults = modernvideoplayer_get_defaults();
        $this->assertArrayHasKey('enforcefocus', $defaults);
        $this->assertArrayHasKey('allowpip', $defaults);
        $this->assertArrayHasKey('allowtranscriptdownload', $defaults);
        $this->assertSame(0, $defaults['enforcefocus']);
        $this->assertSame(1, $defaults['allowpip']);
        $this->assertSame(1, $defaults['allowtranscriptdownload']);

        set_config('defaultenforcefocus', 1, 'modernvideoplayer');
        set_config('defaultallowpip', 0, 'modernvideoplayer');
        set_config('defaultallowtranscriptdownload', 0, 'modernvideoplayer');
        $overridden = modernvideoplayer_get_defaults();
        $this->assertSame(1, $overridden['enforcefocus']);
        $this->assertSame(0, $overridden['allowpip']);
        $this->assertSame(0, $overridden['allowtranscriptdownload']);
    }
}
