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

        $mform->addElement('header', 'filterheading', get_string('filterheading', 'report_teacherlog'));

        $teachers = report_teacherlog_get_teacher_options();
        $mform->addElement('searchableselector', 'teacherid', get_string('teacher', 'report_teacherlog'), $teachers);
        $mform->addRule('teacherid', get_string('chooseteacher', 'report_teacherlog'), 'required', null, 'client');
        $mform->addRule('teacherid', get_string('chooseteacher', 'report_teacherlog'), 'required', null, 'server');

        $mform->addElement('date_selector', 'timefrom', get_string('timefrom', 'report_teacherlog'),
            ['optional' => false]);
        $mform->addElement('date_selector', 'timeto', get_string('timeto', 'report_teacherlog'),
            ['optional' => false]);

        $mform->addElement('text', 'filtercourse', get_string('coursefilter', 'report_teacherlog'), ['size' => 40]);
        $mform->setType('filtercourse', PARAM_TEXT);
        $mform->addHelpButton('filtercourse', 'coursefilter', 'report_teacherlog');

        $mform->addElement('text', 'filtermodule', get_string('modulefilter', 'report_teacherlog'), ['size' => 40]);
        $mform->setType('filtermodule', PARAM_TEXT);
        $mform->addHelpButton('filtermodule', 'modulefilter', 'report_teacherlog');

        $mform->addElement('text', 'filteraction', get_string('actionfilter', 'report_teacherlog'), ['size' => 40]);
        $mform->setType('filteraction', PARAM_TEXT);
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
            'teacherid' => $custom['teacherid'] ?? 0,
            'timefrom' => $custom['timefrom'] ?? (time() - (report_teacherlog_config::DEFAULT_DATERANGE * DAYSECS)),
            'timeto' => $custom['timeto'] ?? time(),
            'filtercourse' => $custom['filtercourse'] ?? '',
            'filtermodule' => $custom['filtermodule'] ?? '',
            'filteraction' => $custom['filteraction'] ?? '',
            'choosereport' => $custom['choosereport'] ?? 0,
        ];
        $mform->setDefaults($defaults);
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['teacherid']) || !report_teacherlog_is_teacher((int)$data['teacherid'])) {
            $errors['teacherid'] = get_string('invalidteacher', 'report_teacherlog');
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
        $params->timefrom = report_teacherlog_date_to_timestamp($data->timefrom);
        $params->timeto = report_teacherlog_date_to_timestamp($data->timeto) + DAYSECS;
        $params->filtercourse = trim($data->filtercourse ?? '');
        $params->filtermodule = trim($data->filtermodule ?? '');
        $params->filteraction = trim($data->filteraction ?? '');
        $params->choosereport = 1;
        $params->refresh = !empty($data->refreshbutton) ? 1 : 0;
        return $params;
    }
}
