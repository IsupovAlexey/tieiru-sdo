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
 * Tests for row filtering and sorting helpers.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/teacherlog/lib.php');

/**
 * Helper function tests.
 */
class lib_test extends \advanced_testcase {

    /**
     * Test row filtering.
     */
    public function test_filter_rows(): void {
        $row1 = (object)[
            'coursename' => 'Math 101',
            'modulename' => 'Assignment 1',
            'action' => 'Viewed',
            'timecreated' => 100,
        ];
        $row2 = (object)[
            'coursename' => 'History',
            'modulename' => 'Forum',
            'action' => 'Submitted',
            'timecreated' => 200,
        ];

        $rows = [$row1, $row2];
        $filtered = report_teacherlog_filter_rows($rows, 'math', '', '');
        $this->assertCount(1, $filtered);
        $this->assertEquals('Math 101', $filtered[0]->coursename);
    }

    /**
     * Test row sorting.
     */
    public function test_sort_rows(): void {
        $row1 = (object)['timecreated' => 100, 'coursename' => 'B'];
        $row2 = (object)['timecreated' => 200, 'coursename' => 'A'];

        $sorted = report_teacherlog_sort_rows([$row1, $row2], ['timecreated' => SORT_DESC]);
        $this->assertEquals(200, $sorted[0]->timecreated);

        $sorted = report_teacherlog_sort_rows([$row1, $row2], ['coursename' => SORT_ASC]);
        $this->assertEquals('A', $sorted[0]->coursename);
    }

    /**
     * Test cache key format.
     */
    public function test_cache_key(): void {
        $this->assertEquals('5:100:200', cache_helper::build_key(5, 100, 200));
    }
}
