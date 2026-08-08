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
 * Log fetcher for teacher activity report.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Fetches log events for a teacher within a time range.
 */
final class log_fetcher {

    /**
     * Returns the standard log reader or throws.
     *
     * @return \core\log\sql_reader
     */
    public static function get_reader(): \core\log\sql_reader {
        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_reader');

        if (isset($readers['logstore_standard'])) {
            return $readers['logstore_standard'];
        }

        foreach ($readers as $reader) {
            if ($reader instanceof \core\log\sql_internal_table_reader) {
                return $reader;
            }
        }

        throw new \moodle_exception('nologreader', 'report_teacherlog');
    }

    /**
     * Counts matching events.
     *
     * @param int $teacherid
     * @param int $timefrom inclusive
     * @param int $timeto exclusive
     * @return int
     */
    public static function count_events(int $teacherid, int $timefrom, int $timeto): int {
        [$where, $params] = self::build_where($teacherid, $timefrom, $timeto);
        return self::get_reader()->get_events_select_count($where, $params);
    }

    /**
     * Fetches and formats all matching events.
     *
     * @param int $teacherid
     * @param int $timefrom inclusive
     * @param int $timeto exclusive
     * @return \stdClass[]
     */
    public static function fetch_rows(int $teacherid, int $timefrom, int $timeto): array {
        global $DB;

        \core_php_time_limit::raise();

        $count = self::count_events($teacherid, $timefrom, $timeto);
        if ($count > config::MAX_ROWS) {
            $a = (object)[
                'count' => $count,
                'max' => config::MAX_ROWS,
            ];
            throw new \moodle_exception('toomanyrows', 'report_teacherlog', '', $a);
        }

        $teacher = $DB->get_record('user', ['id' => $teacherid, 'deleted' => 0], '*', MUST_EXIST);
        $teachername = fullname($teacher);

        [$where, $params] = self::build_where($teacherid, $timefrom, $timeto);
        $iterator = self::get_reader()->get_events_select_iterator(
            $where,
            $params,
            'timecreated DESC, id DESC',
            0,
            0
        );

        $formatter = new row_formatter($teachername);
        $rows = [];
        foreach ($iterator as $event) {
            if ($row = $formatter->format_event($event)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Builds SQL where clause for teacher activity query.
     *
     * @param int $teacherid
     * @param int $timefrom
     * @param int $timeto
     * @return array{0: string, 1: array}
     */
    private static function build_where(int $teacherid, int $timefrom, int $timeto): array {
        $where = 'userid = :userid AND timecreated >= :timefrom AND timecreated < :timeto';
        $params = [
            'userid' => $teacherid,
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $context = \context_system::instance();
        if (!has_capability('moodle/site:viewanonymousevents', $context)) {
            $where .= ' AND anonymous = 0';
        }

        return [$where, $params];
    }
}
