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
 * Library functions.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin configuration constants.
 */
final class report_teacherlog_config {
    /** @var int Default report window in days. */
    public const DEFAULT_DATERANGE = 30;

    /** @var int Maximum allowed report window in days. */
    public const MAX_DATERANGE = 365;

    /** @var int Maximum number of log rows to fetch. */
    public const MAX_ROWS = 10000;

    /** @var int MUC cache TTL in seconds. */
    public const CACHE_TTL = 3600;

    /** @var int Rows per page in the table. */
    public const PER_PAGE = 100;
}

/**
 * Returns users with teacher or editingteacher archetype for the selector.
 *
 * @return array<int, string> userid => fullname with email
 */
function report_teacherlog_get_teacher_options(): array {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.middlename, u.email, u.alternatename
              FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {role} r ON r.id = ra.roleid
             WHERE r.archetype IN ('teacher', 'editingteacher')
               AND u.deleted = 0
               AND u.suspended = 0
          ORDER BY u.lastname, u.firstname";

    $users = $DB->get_records_sql($sql);
    $options = [];
    foreach ($users as $user) {
        $label = fullname($user);
        if (!empty($user->email)) {
            $label .= ' (' . $user->email . ')';
        }
        $options[$user->id] = $label;
    }

    return $options;
}

/**
 * Checks whether a user is a teacher.
 *
 * @param int $userid
 * @return bool
 */
function report_teacherlog_is_teacher(int $userid): bool {
    global $DB;

    if (!$userid) {
        return false;
    }

    $sql = "SELECT 1
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND r.archetype IN ('teacher', 'editingteacher')";

    return $DB->record_exists_sql($sql, ['userid' => $userid]);
}

/**
 * Converts date selector array to midnight timestamp.
 *
 * @param array|int $date
 * @return int
 */
function report_teacherlog_date_to_timestamp($date): int {
    if (is_numeric($date)) {
        return (int)$date;
    }
    if (!is_array($date) || count($date) !== 3) {
        return 0;
    }
    return make_timestamp($date[2], $date[1], $date[0]);
}

/**
 * Applies text filters to report rows.
 *
 * @param array $rows
 * @param string $coursefilter
 * @param string $modulefilter
 * @param string $actionfilter
 * @return array
 */
function report_teacherlog_filter_rows(array $rows, string $coursefilter, string $modulefilter,
        string $actionfilter): array {
    $coursefilter = core_text::strtolower(trim($coursefilter));
    $modulefilter = core_text::strtolower(trim($modulefilter));
    $actionfilter = core_text::strtolower(trim($actionfilter));

    if ($coursefilter === '' && $modulefilter === '' && $actionfilter === '') {
        return $rows;
    }

    return array_values(array_filter($rows, static function(stdClass $row) use ($coursefilter, $modulefilter, $actionfilter): bool {
        if ($coursefilter !== '' && strpos(core_text::strtolower($row->coursename), $coursefilter) === false) {
            return false;
        }
        if ($modulefilter !== '' && strpos(core_text::strtolower($row->modulename), $modulefilter) === false) {
            return false;
        }
        if ($actionfilter !== '' && strpos(core_text::strtolower($row->action), $actionfilter) === false) {
            return false;
        }
        return true;
    }));
}

/**
 * Sorts rows according to flexible_table sort preferences.
 *
 * @param array $rows
 * @param array $sortcolumns column => SORT_ASC|SORT_DESC
 * @return array
 */
function report_teacherlog_sort_rows(array $rows, array $sortcolumns): array {
    if (empty($sortcolumns)) {
        usort($rows, static function(stdClass $a, stdClass $b): int {
            return $b->timecreated <=> $a->timecreated;
        });
        return $rows;
    }

    $column = array_key_first($sortcolumns);
    $direction = $sortcolumns[$column];

    usort($rows, static function(stdClass $a, stdClass $b) use ($column, $direction): int {
        $av = $a->{$column} ?? '';
        $bv = $b->{$column} ?? '';
        if ($column === 'timecreated') {
            $cmp = $a->timecreated <=> $b->timecreated;
        } else {
            $cmp = strnatcasecmp((string)$av, (string)$bv);
        }
        return $direction === SORT_ASC ? $cmp : -$cmp;
    });

    return $rows;
}

/**
 * Builds URL parameters for the report page.
 *
 * @param stdClass $params
 * @return array
 */
function report_teacherlog_url_params(stdClass $params): array {
    $urlparams = [
        'choosereport' => 1,
        'teacherid' => $params->teacherid,
        'timefrom' => $params->timefrom,
        'timeto' => $params->timeto,
    ];

    foreach (['filtercourse', 'filtermodule', 'filteraction'] as $field) {
        if (!empty($params->{$field})) {
            $urlparams[$field] = $params->{$field};
        }
    }

    return $urlparams;
}
