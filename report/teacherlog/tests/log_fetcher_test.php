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
 * Tests for log fetcher.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Log fetcher tests.
 */
class log_fetcher_test extends \advanced_testcase {

    /**
     * Test reader availability.
     */
    public function test_get_reader(): void {
        $this->resetAfterTest();

        try {
            $reader = log_fetcher::get_reader();
            $this->assertInstanceOf(\core\log\sql_reader::class, $reader);
        } catch (\moodle_exception $e) {
            $this->markTestSkipped('No SQL log reader available');
        }
    }

    /**
     * Test event count for a user.
     */
    public function test_count_events(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $event = \core\event\course_viewed::create([
            'context' => \context_course::instance($course->id),
            'objectid' => $course->id,
        ]);
        $event->trigger();

        try {
            $count = log_fetcher::count_events($user->id, time() - HOURSECS, time() + HOURSECS);
            $this->assertGreaterThanOrEqual(1, $count);
        } catch (\moodle_exception $e) {
            $this->markTestSkipped('No SQL log reader available');
        }
    }
}
