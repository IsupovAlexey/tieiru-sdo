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
 * Report filter form.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Teacher activity report filter form.
 */
class report_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;

        $teacherid = (int)($custom['teacherid'] ?? 0);
        $courseid = (int)($custom['courseid'] ?? 0);

        $mform->addElement('header', 'filterheading', get_string('filterheading', 'report_teacherlog'));
        $mform->addElement('static', 'filterhint', '', get_string('filterhint', 'report_teacherlog'));

        $teachers = report_teacherlog_get_teacher_options($courseid);
        $mform->addElement('searchableselector', 'teacherid', get_string('teacher', 'report_teacherlog'), $teachers);
        $mform->addHelpButton('teacherid', 'teacher', 'report_teacherlog');

        $courses = report_teacherlog_get_course_options($teacherid);
        $mform->addElement('searchableselector', 'courseid', get_string('coursefilter', 'report_teacherlog'), $courses);
        $mform->addHelpButton('courseid', 'coursefilter', 'report_teacherlog');

        $mform->addElement('date_selector', 'timefrom', get_string('timefrom', 'report_teacherlog'),
            ['optional' => false]);
        $mform->addElement('date_selector', 'timeto', get_string('timeto', 'report_teacherlog'),
            ['optional' => false]);

        $mform->addElement('header', 'textfilterheading', get_string('textfilterheading', 'report_teacherlog'));

        $mform->addElement('text', 'filtermodule', get_string('modulefilter', 'report_teacherlog'), [
            'size' => 40,
            'placeholder' => get_string('modulefilter_placeholder', 'report_teacherlog'),
        ]);
        $mform->setType('filtermodule', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('filtermodule', 'modulefilter', 'report_teacherlog');

        $mform->addElement('text', 'filteraction', get_string('actionfilter', 'report_teacherlog'), [
            'size' => 40,
            'placeholder' => get_string('actionfilter_placeholder', 'report_teacherlog'),
        ]);
        $mform->setType('filteraction', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('filteraction', 'actionfilter', 'report_teacherlog');

        $mform->addElement('hidden', 'choosereport', 1);
        $mform->setType('choosereport', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('showreport', 'report_teacherlog'));
        if (!empty($custom['showrefresh'])) {
            $buttonarray[] = $mform->createElement('submit', 'refreshbutton', get_string('refreshdata', 'report_teacherlog'));
        }
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        $defaults = [
            'teacherid' => $teacherid,
            'courseid' => $courseid,
            'timefrom' => $custom['timefrom'] ?? (time() - (report_teacherlog_config::DEFAULT_DATERANGE * DAYSECS)),
            'timeto' => $custom['timeto'] ?? time(),
            'filtermodule' => $custom['filtermodule'] ?? '',
            'filteraction' => $custom['filteraction'] ?? '',
            'choosereport' => $custom['choosereport'] ?? 1,
        ];
        $mform->setDefaults($defaults);
    }

    /**
     * Returns submitted data with teacher resolved from course when needed.
     *
     * @return \stdClass|false|null
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return null;
        }

        if (empty($data->teacherid) && !empty($data->courseid)) {
            $data->teacherid = report_teacherlog_resolve_teacher_for_course((int)$data->courseid);
        }

        return $data;
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $teacherid = (int)($data['teacherid'] ?? 0);
        $courseid = (int)($data['courseid'] ?? 0);

        if (!$teacherid && $courseid) {
            $teacherid = report_teacherlog_resolve_teacher_for_course($courseid);
        }

        if (!$teacherid || !report_teacherlog_is_teacher($teacherid)) {
            if ($courseid) {
                $errors['courseid'] = get_string('nocourseteacher', 'report_teacherlog');
            } else {
                $errors['teacherid'] = get_string('chooseteacher', 'report_teacherlog');
            }
        } else if ($courseid && !report_teacherlog_teacher_in_course($teacherid, $courseid)) {
            $errors['courseid'] = get_string('coursenotforteacher', 'report_teacherlog');
        }

        $timefrom = report_teacherlog_date_to_timestamp($data['timefrom']);
        $timeto = report_teacherlog_date_to_timestamp($data['timeto']) + DAYSECS;

        if ($timeto <= $timefrom) {
            $errors['timeto'] = get_string('daterangeinvalid', 'report_teacherlog');
        } else if (($timeto - $timefrom) > (report_teacherlog_config::MAX_DATERANGE * DAYSECS)) {
            $errors['timeto'] = get_string('daterangetoolong', 'report_teacherlog',
                report_teacherlog_config::MAX_DATERANGE);
        }

        return $errors;
    }

    /**
     * Returns normalized report parameters from submitted form data.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function normalize_params(\stdClass $data): \stdClass {
        $params = new \stdClass();
        $params->teacherid = (int)$data->teacherid;
        if (empty($params->teacherid) && !empty($data->courseid)) {
            $params->teacherid = report_teacherlog_resolve_teacher_for_course((int)$data->courseid);
        }
        $params->courseid = (int)($data->courseid ?? 0);
        $params->timefrom = report_teacherlog_date_to_timestamp($data->timefrom);
        $params->timeto = report_teacherlog_date_to_timestamp($data->timeto) + DAYSECS;
        $params->filtermodule = trim($data->filtermodule ?? '');
        $params->filteraction = trim($data->filteraction ?? '');
        $params->choosereport = 1;
        $params->refresh = !empty($data->refreshbutton) ? 1 : 0;
        return $params;
    }
}
