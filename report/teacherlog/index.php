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

// Handle form submission first. Date fields are arrays in POST and must not be read via optional_param().
$submitformcustom = [
    'teacherid' => 0,
    'courseid' => 0,
    'timefrom' => time() - (\report_teacherlog\config::DEFAULT_DATERANGE * DAYSECS),
    'timeto' => time(),
    'filtermodule' => '',
    'filteraction' => '',
    'choosereport' => 1,
    'showrefresh' => false,
];
$submitmform = new report_form(null, $submitformcustom);
if ($formdata = $submitmform->get_data()) {
    $params = report_form::normalize_params($formdata);
    if (!empty($params->refresh)) {
        $SESSION->report_teacherlog_forcerefresh = cache_helper::build_key(
            $params->teacherid,
            $params->timefrom,
            $params->timeto
        );
    }
    redirect(new moodle_url('/report/teacherlog/index.php', report_teacherlog_url_params($params)));
}

// Report state is carried in the query string after redirect.
$download = optional_param('download', '', PARAM_ALPHA);
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

$params = null;
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
}

$urlparams = $params ? report_teacherlog_url_params($params) : [];
$url = new moodle_url('/report/teacherlog/index.php', $urlparams);
$PAGE->set_url($url);

$formcustom = [
    'teacherid' => $teacherid,
    'courseid' => $courseid,
    'timefrom' => $timefrom ?: (time() - (\report_teacherlog\config::DEFAULT_DATERANGE * DAYSECS)),
    'timeto' => $timeto ? ($timeto - DAYSECS) : time(),
    'filtermodule' => $filtermodule,
    'filteraction' => $filteraction,
    'choosereport' => $choosereport,
    'showrefresh' => false,
];

$forcerefresh = false;
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
            // Release session lock before heavy log query (see report/log).
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
            $formcustom['showrefresh'] = true;
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

$formcustom['showrefresh'] = $formcustom['showrefresh'] || !empty($rows) || !empty($params);
$mform = new report_form($PAGE->url->out(false), $formcustom);
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
