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
 * @param int $courseid Optional course to limit teachers to.
 * @return array<int, string> userid => fullname with email
 */
function report_teacherlog_get_teacher_options(int $courseid = 0): array {
    global $DB;

    $params = [];
    $coursejoin = '';
    if ($courseid > 1) {
        $coursejoin = "JOIN {context} coursectx ON coursectx.id = ra.contextid
                            AND coursectx.contextlevel = :courselevel
                            AND coursectx.instanceid = :courseid";
        $params['courselevel'] = CONTEXT_COURSE;
        $params['courseid'] = $courseid;
    }

    $userfieldsapi = \core_user\fields::for_name();
    $userfields = $userfieldsapi->get_sql('u', false, '', '', false);

    $sql = "SELECT DISTINCT u.id, u.email, {$userfields->selects}
              FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {role} r ON r.id = ra.roleid
              $coursejoin
             WHERE r.archetype IN ('teacher', 'editingteacher')
               AND u.deleted = 0
               AND u.suspended = 0
          ORDER BY u.lastname, u.firstname";

    $users = $DB->get_records_sql($sql, array_merge($params, $userfields->params));
    return report_teacherlog_format_user_options($users);
}

/**
 * Returns course options for the selector.
 *
 * @param int $teacherid Optional teacher to limit courses to.
 * @return array<int, string> courseid => label
 */
function report_teacherlog_get_course_options(int $teacherid = 0): array {
    global $DB;

    $params = [
        'courselevel' => CONTEXT_COURSE,
        'siteid' => SITEID,
    ];
    $teacherjoin = '';
    if ($teacherid > 0) {
        $teacherjoin = 'AND ra.userid = :teacherid';
        $params['teacherid'] = $teacherid;
    }

    $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname
              FROM {course} c
              JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :courselevel
              JOIN {role_assignments} ra ON ra.contextid = ctx.id
              JOIN {role} r ON r.id = ra.roleid
              JOIN {user} u ON u.id = ra.userid
             WHERE c.id > :siteid
               AND r.archetype IN ('teacher', 'editingteacher')
               AND u.deleted = 0
               AND u.suspended = 0
               $teacherjoin
          ORDER BY c.fullname, c.shortname";

    $courses = $DB->get_records_sql($sql, $params);
    $options = [0 => ''];
    foreach ($courses as $course) {
        $options[$course->id] = report_teacherlog_format_course_label($course);
    }

    return $options;
}

/**
 * Resolves a default teacher for a course.
 *
 * @param int $courseid
 * @return int
 */
function report_teacherlog_resolve_teacher_for_course(int $courseid): int {
    global $DB;

    if ($courseid <= 1) {
        return 0;
    }

    $sql = "SELECT u.id
              FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} ctx ON ctx.id = ra.contextid
             WHERE ctx.contextlevel = :courselevel
               AND ctx.instanceid = :courseid
               AND r.archetype IN ('teacher', 'editingteacher')
               AND u.deleted = 0
               AND u.suspended = 0
          ORDER BY CASE WHEN r.archetype = 'editingteacher' THEN 0 ELSE 1 END,
                   u.lastname, u.firstname, u.id";

    $teacherid = $DB->get_field_sql($sql, [
        'courselevel' => CONTEXT_COURSE,
        'courseid' => $courseid,
    ], IGNORE_MULTIPLE);

    return $teacherid ? (int)$teacherid : 0;
}

/**
 * Checks whether a teacher has a teaching role in a course.
 *
 * @param int $teacherid
 * @param int $courseid
 * @return bool
 */
function report_teacherlog_teacher_in_course(int $teacherid, int $courseid): bool {
    global $DB;

    if ($teacherid <= 0 || $courseid <= 1) {
        return false;
    }

    $sql = "SELECT 1
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} ctx ON ctx.id = ra.contextid
             WHERE ra.userid = :teacherid
               AND ctx.contextlevel = :courselevel
               AND ctx.instanceid = :courseid
               AND r.archetype IN ('teacher', 'editingteacher')";

    return $DB->record_exists_sql($sql, [
        'teacherid' => $teacherid,
        'courselevel' => CONTEXT_COURSE,
        'courseid' => $courseid,
    ]);
}

/**
 * Formats user records for selector labels.
 *
 * @param array $users
 * @return array<int, string>
 */
function report_teacherlog_format_user_options(array $users): array {
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
 * Formats a course label for selectors.
 *
 * @param \stdClass $course
 * @return string
 */
function report_teacherlog_format_course_label(\stdClass $course): string {
    $label = (string)$course->fullname;
    if (!empty($course->shortname) && $course->shortname !== $course->fullname) {
        $label .= ' (' . $course->shortname . ')';
    }
    return $label;
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
 * Reads an integer parameter from the query string only.
 *
 * @param string $name
 * @return int
 */
function report_teacherlog_get_int_param(string $name): int {
    if (!isset($_GET[$name])) {
        return 0;
    }
    return clean_param($_GET[$name], PARAM_INT);
}

/**
 * Reads a trimmed string parameter from the query string only.
 *
 * @param string $name
 * @return string
 */
function report_teacherlog_get_string_param(string $name): string {
    if (!isset($_GET[$name])) {
        return '';
    }
    return clean_param($_GET[$name], PARAM_RAW_TRIMMED);
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
function report_teacherlog_filter_rows(array $rows, int $courseid, string $modulefilter,
        string $actionfilter): array {
    $modulefilter = report_teacherlog_normalize_filter($modulefilter);
    $actionfilter = report_teacherlog_normalize_filter($actionfilter);

    if ($courseid <= 0 && $modulefilter === '' && $actionfilter === '') {
        return $rows;
    }

    return array_values(array_filter($rows, static function(stdClass $row) use ($courseid, $modulefilter, $actionfilter): bool {
        if ($courseid > 0 && (int)($row->courseid ?? 0) !== $courseid) {
            return false;
        }
        if ($modulefilter !== '' && strpos(report_teacherlog_row_filter_text($row, 'module'), $modulefilter) === false) {
            return false;
        }
        if ($actionfilter !== '' && strpos(report_teacherlog_row_filter_text($row, 'action'), $actionfilter) === false) {
            return false;
        }
        return true;
    }));
}

/**
 * Normalizes user-entered filter text for case-insensitive substring matching.
 *
 * @param string $filter
 * @return string
 */
function report_teacherlog_normalize_filter(string $filter): string {
    $filter = trim(clean_param($filter, PARAM_RAW_TRIMMED));
    if ($filter === '') {
        return '';
    }
    return core_text::strtolower($filter);
}

/**
 * Returns normalized searchable text for a report row column.
 *
 * @param stdClass $row
 * @param string $column course|module|action
 * @return string
 */
function report_teacherlog_row_filter_text(stdClass $row, string $column): string {
    switch ($column) {
        case 'course':
            $text = ($row->coursefiltertext ?? '') !== '' ? $row->coursefiltertext : ($row->coursename ?? '');
            break;
        case 'module':
            $text = $row->modulename ?? '';
            break;
        case 'action':
            $text = $row->action ?? '';
            break;
        default:
            $text = '';
    }

    $text = html_to_text((string)$text, 0, false);
    return core_text::strtolower(trim($text));
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

    reset($sortcolumns);
    $column = key($sortcolumns);
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

    foreach (['filtermodule', 'filteraction'] as $field) {
        if (!empty($params->{$field})) {
            $urlparams[$field] = $params->{$field};
        }
    }

    if (!empty($params->courseid)) {
        $urlparams['courseid'] = $params->courseid;
    }

    return $urlparams;
}
