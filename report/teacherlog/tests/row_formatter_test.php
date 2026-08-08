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
 * Tests for row formatter.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Row formatter tests.
 */
class row_formatter_test extends \advanced_testcase {

    /**
     * Test site-level event formatting.
     */
    public function test_format_site_event(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $event = \core\event\course_viewed::create([
            'context' => \context_course::instance($course->id),
            'objectid' => $course->id,
        ]);
        $event->trigger();

        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_reader');
        if (empty($readers['logstore_standard'])) {
            $this->markTestSkipped('logstore_standard not enabled');
        }

        $reader = $readers['logstore_standard'];
        $events = $reader->get_events_select('userid = :userid', ['userid' => $user->id], 'timecreated DESC', 0, 1);
        $this->assertNotEmpty($events);

        $formatter = new row_formatter(fullname($user));
        $row = $formatter->format_event(reset($events));

        $this->assertNotEmpty($row->datetime);
        $this->assertEquals(fullname($user), $row->fullname);
        $this->assertEquals(format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
            $row->coursename);
        $this->assertEquals('—', $row->modulename);
        $this->assertNotEmpty($row->action);
    }
}
