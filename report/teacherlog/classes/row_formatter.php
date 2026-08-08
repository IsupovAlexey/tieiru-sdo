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
 * Formats log events into report rows.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Converts log events to flat row objects for display and export.
 */
final class row_formatter {

    /** @var string */
    private $teachername;

    /** @var array<int, array{0: string, 1: string}> */
    private $coursenames = [];

    /** @var array<int, string> */
    private $contextnames = [];

    /**
     * @param string $teachername
     */
    public function __construct(string $teachername) {
        $this->teachername = $teachername;
    }

    /**
     * Formats a single event.
     *
     * @param \core\event\base $event
     * @return \stdClass|null
     */
    public function format_event(\core\event\base $event): ?\stdClass {
        $row = new \stdClass();
        $row->timecreated = $event->timecreated;
        $row->datetime = userdate($event->timecreated, get_string('strftimedatetime', 'langconfig'));
        $row->fullname = $this->teachername;
        $row->courseid = (int)$event->courseid;
        [$row->coursename, $row->coursefiltertext] = $this->get_course_names($row->courseid);
        $row->modulename = $this->get_module_name($event);
        $row->action = $event->get_name();

        return $row;
    }

    /**
     * @param int $courseid
     * @return array{0: string, 1: string} display name and raw searchable text
     */
    private function get_course_names(int $courseid): array {
        if ($courseid <= 0) {
            $sitename = get_string('site');
            return [$sitename, $sitename];
        }

        if (!isset($this->coursenames[$courseid])) {
            try {
                $course = get_course($courseid);
                $displayname = format_string($course->fullname, true,
                    ['context' => \context_course::instance($courseid)]);
                $filtertext = trim($course->fullname . ' ' . $course->shortname);
                $this->coursenames[$courseid] = [$displayname, $filtertext];
            } catch (\Throwable $e) {
                $unknown = get_string('unknowncourse');
                $this->coursenames[$courseid] = [$unknown, $unknown];
            }
        }

        return $this->coursenames[$courseid];
    }

    /**
     * @param \core\event\base $event
     * @return string
     */
    private function get_module_name(\core\event\base $event): string {
        if ((int)$event->contextlevel !== CONTEXT_MODULE || empty($event->contextid)) {
            return '—';
        }

        if (!isset($this->contextnames[$event->contextid])) {
            $context = \context::instance_by_id($event->contextid, IGNORE_MISSING);
            if ($context) {
                $this->contextnames[$event->contextid] = $context->get_context_name(false);
            } else {
                $this->contextnames[$event->contextid] = '—';
            }
        }

        return $this->contextnames[$event->contextid];
    }
}
