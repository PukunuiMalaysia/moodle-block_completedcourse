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
 * Completed course service tests.
 *
 * @package    block_completedcourse
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_completedcourse;

use advanced_testcase;
use block_completedcourse\local\completion_service;

/**
 * Tests for completion_service.
 *
 * @covers \block_completedcourse\local\completion_service
 */
final class completion_service_test extends advanced_testcase {
    /**
     * Invalid settings are normalized to safe defaults.
     */
    public function test_normalise_options_rejects_invalid_values(): void {
        $options = completion_service::normalise_options([
            'rowlimit' => -1,
            'sortdirection' => 'SIDEWAYS',
            'displayname' => 'invalid',
            'categoryid' => -3,
        ]);

        $this->assertSame(10, $options['rowlimit']);
        $this->assertSame('DESC', $options['sortdirection']);
        $this->assertSame('shortname', $options['displayname']);
        $this->assertSame(0, $options['categoryid']);
    }

    /**
     * Row limit is capped.
     */
    public function test_normalise_options_caps_rowlimit(): void {
        $options = completion_service::normalise_options(['rowlimit' => 500]);

        $this->assertSame(100, $options['rowlimit']);
    }

    /**
     * Course display names follow the selected setting.
     */
    public function test_get_course_display_name(): void {
        $course = (object)[
            'shortname' => 'SHORT',
            'fullname' => 'Full course name',
        ];

        $this->assertSame('SHORT', completion_service::get_course_display_name($course, 'shortname'));
        $this->assertSame('Full course name', completion_service::get_course_display_name($course, 'fullname'));
        $this->assertSame('Full course name (SHORT)', completion_service::get_course_display_name($course, 'both'));
    }
}
