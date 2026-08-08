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

$download = optional_param('download', '', PARAM_ALPHA);
$choosereport = optional_param('choosereport', 0, PARAM_INT);
$teacherid = optional_param('teacherid', 0, PARAM_INT);
$timefrom = optional_param('timefrom', 0, PARAM_INT);
$timeto = optional_param('timeto', 0, PARAM_INT);
$filtercourse = optional_param('filtercourse', '', PARAM_TEXT);
$filtermodule = optional_param('filtermodule', '', PARAM_TEXT);
$filteraction = optional_param('filteraction', '', PARAM_TEXT);

$formcustom = [
    'teacherid' => $teacherid,
    'timefrom' => $timefrom ?: (time() - (report_teacherlog_config::DEFAULT_DATERANGE * DAYSECS)),
    'timeto' => $timeto ? ($timeto - DAYSECS) : time(),
    'filtercourse' => $filtercourse,
    'filtermodule' => $filtermodule,
    'filteraction' => $filteraction,
    'choosereport' => $choosereport,
    'showrefresh' => false,
];

$mform = new report_form(null, $formcustom);

if ($formdata = $mform->get_data()) {
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

$params = null;
if ($choosereport && $teacherid && $timefrom && $timeto) {
    $params = (object)[
        'teacherid' => $teacherid,
        'timefrom' => $timefrom,
        'timeto' => $timeto,
        'filtercourse' => $filtercourse,
        'filtermodule' => $filtermodule,
        'filteraction' => $filteraction,
        'choosereport' => 1,
    ];
}

$forcerefresh = false;
if ($params && !empty($SESSION->report_teacherlog_forcerefresh)) {
    $cachekey = cache_helper::build_key($params->teacherid, $params->timefrom, $params->timeto);
    if ($SESSION->report_teacherlog_forcerefresh === $cachekey) {
        $forcerefresh = true;
        unset($SESSION->report_teacherlog_forcerefresh);
    }
}

$urlparams = $params ? report_teacherlog_url_params($params) : [];
$url = new moodle_url('/report/teacherlog/index.php', $urlparams);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_teacherlog'));
$PAGE->set_heading(get_string('pluginname', 'report_teacherlog'));
$PAGE->requires->css('/report/teacherlog/styles.css');

$rows = [];
$error = null;

if ($params) {
    if (!report_teacherlog_is_teacher($params->teacherid)) {
        $error = get_string('invalidteacher', 'report_teacherlog');
    } else if ($params->timeto <= $params->timefrom) {
        $error = get_string('daterangeinvalid', 'report_teacherlog');
    } else if (($params->timeto - $params->timefrom) > (report_teacherlog_config::MAX_DATERANGE * DAYSECS)) {
        $error = get_string('daterangetoolong', 'report_teacherlog', report_teacherlog_config::MAX_DATERANGE);
    } else {
        try {
            $rows = cache_helper::get_or_fetch(
                $params->teacherid,
                $params->timefrom,
                $params->timeto,
                $forcerefresh
            );
            $rows = report_teacherlog_filter_rows(
                $rows,
                $params->filtercourse,
                $params->filtermodule,
                $params->filteraction
            );
            $formcustom['showrefresh'] = true;
        } catch (moodle_exception $e) {
            $error = $e->getMessage();
        }
    }
}

if ($download && $params && $rows && !$error) {
    $filename = 'teacherlog_' . $params->teacherid . '_' . userdate(time(), '%Y%m%d', 99, false);
    $table = new teacherlog_table($PAGE->url);
    $table->is_downloading($download, $filename, get_string('pluginname', 'report_teacherlog'));
    $table->display_rows($rows);
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->notification(get_string('helpnote', 'report_teacherlog'), 'info');
echo $OUTPUT->notification(get_string('timespentnotavailable', 'report_teacherlog'), 'notifymessage');

$formcustom = array_merge($formcustom, [
    'teacherid' => $params ? $params->teacherid : $formcustom['teacherid'],
    'timefrom' => $params ? $params->timefrom : $formcustom['timefrom'],
    'timeto' => $params ? ($params->timeto - DAYSECS) : $formcustom['timeto'],
    'filtercourse' => $params ? $params->filtercourse : $formcustom['filtercourse'],
    'filtermodule' => $params ? $params->filtermodule : $formcustom['filtermodule'],
    'filteraction' => $params ? $params->filteraction : $formcustom['filteraction'],
    'choosereport' => $params ? 1 : 0,
    'showrefresh' => !empty($rows) || !empty($params),
]);
$mform = new report_form($PAGE->url->out(false), $formcustom);
$mform->display();

if ($error) {
    echo $OUTPUT->notification($error, 'notifyproblem');
} else if ($params && empty($rows)) {
    echo $OUTPUT->notification(get_string('noevents', 'report_teacherlog'), 'notifymessage');
} else if ($rows) {
    echo html_writer::tag('p', get_string('reportcount', 'report_teacherlog', count($rows)),
        ['class' => 'report-teacherlog-count']);
    $table = new teacherlog_table($PAGE->url);
    $table->display_rows($rows);
}

echo $OUTPUT->footer();
