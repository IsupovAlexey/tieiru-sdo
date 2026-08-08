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
 * Language strings.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actionfilter'] = 'Action';
$string['teacher'] = 'Teacher';
$string['teacher_help'] = 'Required unless a course is selected. When a course is selected first, a teacher from that course will be chosen automatically.';
$string['actionfilter_help'] = 'Select an action from the values in the loaded report. Run "Show report" first, then narrow the results.';
$string['actionfilter_placeholder'] = 'e.g. viewed';
$string['allcourses'] = 'All courses';
$string['cachedef_reportdata'] = 'Teacher activity report cache';
$string['chooseteacher'] = 'Select a teacher';
$string['col_action'] = 'Action';
$string['col_course'] = 'Course';
$string['col_datetime'] = 'Date and time';
$string['col_fullname'] = 'Teacher full name';
$string['col_module'] = 'Course module';
$string['coursefilter'] = 'Course';
$string['coursefilter_help'] = 'Optional. When a teacher is selected, only their courses are listed. Selecting a course without a teacher will pick a teacher from that course automatically.';
$string['coursenotforteacher'] = 'The selected course is not assigned to this teacher.';
$string['daterangeinvalid'] = 'End date must be after start date.';
$string['daterangetoolong'] = 'Date range cannot exceed {$a} days.';
$string['eventreportviewed'] = 'Teacher activity report viewed';
$string['filterhint'] = 'Select a teacher and date range, then click "Show report". Teacher and course lists are linked: choosing a teacher limits courses, and choosing a course can auto-select a teacher.';
$string['filteroptionsafterreport'] = 'Run the report first — dropdown lists for narrowing will appear here.';
$string['filterheading'] = 'Report parameters';
$string['helpnote'] = 'This report shows actions performed by the selected teacher during the specified period. It is separate from the standard event log and is intended for monitoring teacher activity.';
$string['invalidteacher'] = 'The selected user is not a teacher.';
$string['modulefilter'] = 'Course module';
$string['modulefilter_help'] = 'Select a course module from the values in the loaded report. Run "Show report" first, then narrow the results.';
$string['modulefilter_placeholder'] = 'Part of module name';
$string['nocourseteacher'] = 'No teacher was found for the selected course.';
$string['nofiltermatches'] = 'No records match the selected filters. Clear or change the filters and click "Show report" again.';
$string['nologreader'] = 'Log store is not enabled. Enable the standard log store.';
$string['noevents'] = 'No events found for the selected period.';
$string['pluginname'] = 'Teacher activity log';
$string['privacy:metadata'] = 'The teacher activity report plugin does not store personal data.';
$string['refreshdata'] = 'Refresh data';
$string['reportcount'] = 'Records found: {$a}';
$string['showreport'] = 'Show report';
$string['textfilterheading'] = 'Narrow results (optional)';
$string['timefrom'] = 'Start date';
$string['timespentnotavailable'] = 'Time spent is not available: the event log records actions but not duration.';
$string['timeto'] = 'End date';
$string['toomanyrows'] = 'Too many records ({$a->count}). Narrow the date range (maximum {$a->max} records).';
