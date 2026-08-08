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
 * Teacher activity report.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/report/teacherlog/lib.php');
require_once($CFG->libdir . '/tablelib.php');

use report_teacherlog\cache_helper;
use report_teacherlog\form\report_form;
use report_teacherlog\table\teacherlog_table;

require_login();

$context = context_system::instance();
require_capability('report/teacherlog:view', $context);

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_teacherlog'));
$PAGE->set_heading(get_string('pluginname', 'report_teacherlog'));
$PAGE->requires->css('/report/teacherlog/styles.css');

// Bookmarkable state from the query string (GET only — POST date fields are arrays).
$download = optional_param('download', '', PARAM_ALPHA);

$formcustom = [
    'teacherid' => 0,
    'courseid' => 0,
    'timefrom' => time() - (\report_teacherlog\config::DEFAULT_DATERANGE * DAYSECS),
    'timeto' => time(),
    'filtermodule' => '',
    'filteraction' => '',
    'choosereport' => 0,
];

$url = new moodle_url('/report/teacherlog/index.php');
$PAGE->set_url($url);

$mform = new report_form($url, $formcustom);

$params = null;
$forcerefresh = false;

if ($formdata = $mform->get_data()) {
    $params = report_form::normalize_params($formdata);
    if (!empty($params->refresh)) {
        $forcerefresh = true;
        $SESSION->report_teacherlog_forcerefresh = cache_helper::build_key(
            $params->teacherid,
            $params->timefrom,
            $params->timeto
        );
    }
    $urlparams = report_teacherlog_url_params($params);
    $url = new moodle_url('/report/teacherlog/index.php', $urlparams);
    $PAGE->set_url($url);
    if (!headers_sent()) {
        redirect($url);
    }
} else if (!$mform->is_submitted()) {
    $choosereport = report_teacherlog_get_int_param('choosereport');
    $teacherid = report_teacherlog_get_int_param('teacherid');
    $courseid = report_teacherlog_get_int_param('courseid');
    $timefrom = report_teacherlog_get_int_param('timefrom');
    $timeto = report_teacherlog_get_int_param('timeto');
    $filtermodule = report_teacherlog_get_string_param('filtermodule');
    $filteraction = report_teacherlog_get_string_param('filteraction');

    if ($courseid && !$teacherid) {
        $teacherid = report_teacherlog_resolve_teacher_for_course($courseid);
    }

    $formcustom = [
        'teacherid' => $teacherid,
        'courseid' => $courseid,
        'timefrom' => $timefrom ?: (time() - (\report_teacherlog\config::DEFAULT_DATERANGE * DAYSECS)),
        'timeto' => $timeto ? ($timeto - DAYSECS) : time(),
        'filtermodule' => $filtermodule,
        'filteraction' => $filteraction,
        'choosereport' => $choosereport,
    ];

    if ($choosereport && $teacherid && $timefrom && $timeto) {
        $params = (object)[
            'teacherid' => $teacherid,
            'courseid' => $courseid,
            'timefrom' => $timefrom,
            'timeto' => $timeto,
            'filtermodule' => $filtermodule,
            'filteraction' => $filteraction,
            'choosereport' => 1,
        ];
        $url = new moodle_url('/report/teacherlog/index.php', report_teacherlog_url_params($params));
        $PAGE->set_url($url);
        $mform = new report_form($url, $formcustom);
    }
}

if ($params && !empty($SESSION->report_teacherlog_forcerefresh)) {
    $cachekey = cache_helper::build_key($params->teacherid, $params->timefrom, $params->timeto);
    if ($SESSION->report_teacherlog_forcerefresh === $cachekey) {
        $forcerefresh = true;
        unset($SESSION->report_teacherlog_forcerefresh);
    }
}

$rows = [];
$allrows = [];
$hasactivefilters = false;
$error = null;

if ($params) {
    if (!report_teacherlog_is_teacher($params->teacherid)) {
        $error = get_string('invalidteacher', 'report_teacherlog');
    } else if ($params->timeto <= $params->timefrom) {
        $error = get_string('daterangeinvalid', 'report_teacherlog');
    } else if (($params->timeto - $params->timefrom) > (\report_teacherlog\config::MAX_DATERANGE * DAYSECS)) {
        $error = get_string('daterangetoolong', 'report_teacherlog', \report_teacherlog\config::MAX_DATERANGE);
    } else {
        try {
            \core\session\manager::write_close();
            $allrows = cache_helper::get_or_fetch(
                $params->teacherid,
                $params->timefrom,
                $params->timeto,
                $forcerefresh
            );
            $rows = report_teacherlog_filter_rows(
                $allrows,
                $params->courseid,
                $params->filtermodule,
                $params->filteraction
            );
            $hasactivefilters = $params->courseid > 0
                || report_teacherlog_normalize_filter($params->filtermodule) !== ''
                || report_teacherlog_normalize_filter($params->filteraction) !== '';
        } catch (moodle_exception $e) {
            $error = $e->getMessage();
        }
    }
}

if ($download && $params && $rows && !$error) {
    \core\session\manager::write_close();
    $filename = 'teacherlog_' . $params->teacherid . '_' . userdate(time(), '%Y%m%d', 99, false);
    $table = new teacherlog_table($PAGE->url);
    $table->is_downloading($download, $filename, get_string('pluginname', 'report_teacherlog'));
    $table->display_rows($rows);
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->notification(get_string('helpnote', 'report_teacherlog'), 'info');
echo $OUTPUT->notification(get_string('timespentnotavailable', 'report_teacherlog'), 'notifymessage');

$formshowerrors = $mform->is_submitted() && !$params;
if (!$formshowerrors && $params && !$error) {
    $displaycustom = [
        'teacherid' => $params->teacherid,
        'courseid' => $params->courseid,
        'timefrom' => $params->timefrom,
        'timeto' => $params->timeto - DAYSECS,
        'filtermodule' => $params->filtermodule,
        'filteraction' => $params->filteraction,
        'choosereport' => 1,
        'hasreportdata' => !empty($allrows),
    ];
    if (!empty($allrows)) {
        $filteroptions = report_teacherlog_collect_filter_options($allrows, (int)$params->courseid);
        $displaycustom['moduleoptions'] = $filteroptions['modules'];
        $displaycustom['actionoptions'] = $filteroptions['actions'];
    }
    $mform = new report_form($url, $displaycustom);
}

$mform->display();

if ($error) {
    echo $OUTPUT->notification($error, 'notifyproblem');
} else if ($params && empty($rows)) {
    if (!empty($hasactivefilters) && !empty($allrows)) {
        echo $OUTPUT->notification(get_string('nofiltermatches', 'report_teacherlog'), 'notifymessage');
    } else {
        echo $OUTPUT->notification(get_string('noevents', 'report_teacherlog'), 'notifymessage');
    }
} else if ($rows) {
    echo html_writer::tag('p', get_string('reportcount', 'report_teacherlog', count($rows)),
        ['class' => 'report-teacherlog-count']);
    $table = new teacherlog_table($PAGE->url);
    $table->display_rows($rows);
}

echo $OUTPUT->footer();
