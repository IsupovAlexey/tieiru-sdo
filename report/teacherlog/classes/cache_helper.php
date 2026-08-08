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
 * MUC cache helper.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages cached report snapshots.
 */
final class cache_helper {

    /**
     * Builds a cache key for a teacher and date range.
     *
     * @param int $teacherid
     * @param int $timefrom
     * @param int $timeto
     * @return string
     */
    public static function build_key(int $teacherid, int $timefrom, int $timeto): string {
        return $teacherid . ':' . $timefrom . ':' . $timeto;
    }

    /**
     * Returns cached rows or fetches and stores them.
     *
     * @param int $teacherid
     * @param int $timefrom
     * @param int $timeto
     * @param bool $refresh
     * @return \stdClass[]
     */
    public static function get_or_fetch(int $teacherid, int $timefrom, int $timeto, bool $refresh = false): array {
        global $SESSION;

        $cache = \cache::make('report_teacherlog', 'reportdata');
        $key = self::build_key($teacherid, $timefrom, $timeto);

        if (!$refresh) {
            $cached = $cache->get($key);
            if (is_array($cached)) {
                $SESSION->report_teacherlog = [
                    'key' => $key,
                    'teacherid' => $teacherid,
                    'timefrom' => $timefrom,
                    'timeto' => $timeto,
                ];
                return $cached;
            }
        }

        $rows = log_fetcher::fetch_rows($teacherid, $timefrom, $timeto);
        $cache->set($key, $rows);

        $SESSION->report_teacherlog = [
            'key' => $key,
            'teacherid' => $teacherid,
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        return $rows;
    }

    /**
     * Validates that the current session matches the requested report parameters.
     *
     * @param int $teacherid
     * @param int $timefrom
     * @param int $timeto
     * @return bool
     */
    public static function session_matches(int $teacherid, int $timefrom, int $timeto): bool {
        global $SESSION;

        if (empty($SESSION->report_teacherlog)) {
            return false;
        }

        $state = $SESSION->report_teacherlog;
        return (int)$state['teacherid'] === $teacherid
            && (int)$state['timefrom'] === $timefrom
            && (int)$state['timeto'] === $timeto;
    }
}
