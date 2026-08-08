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
 * Report table.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog\table;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Flexible table for teacher activity rows.
 */
class teacherlog_table extends \flexible_table {

    /**
     * @param \moodle_url $baseurl
     */
    public function __construct(\moodle_url $baseurl) {
        parent::__construct('report-teacherlog');

        $this->define_columns(['datetime', 'fullname', 'coursename', 'modulename', 'action']);
        $this->define_headers([
            get_string('col_datetime', 'report_teacherlog'),
            get_string('col_fullname', 'report_teacherlog'),
            get_string('col_course', 'report_teacherlog'),
            get_string('col_module', 'report_teacherlog'),
            get_string('col_action', 'report_teacherlog'),
        ]);

        $this->define_baseurl($baseurl);
        $this->set_attribute('class', 'generaltable generalbox table-sm reporttable');
        $this->sortable(true, 'datetime', SORT_DESC);
        $this->no_sorting('fullname');
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
    }

    /**
     * Renders rows with sorting and pagination.
     *
     * @param array $rows
     */
    public function display_rows(array $rows): void {
        $this->setup();

        $sortcolumns = $this->get_sort_columns();
        if (empty($sortcolumns)) {
            $sortcolumns = ['timecreated' => SORT_DESC];
        } else {
            $mapped = [];
            foreach ($sortcolumns as $column => $order) {
                if ($column === 'datetime') {
                    $mapped['timecreated'] = $order;
                } else {
                    $mapped[$column] = $order;
                }
            }
            $sortcolumns = $mapped;
        }

        $rows = report_teacherlog_sort_rows($rows, $sortcolumns);

        if ($this->is_downloading()) {
            $pagerows = $rows;
            $this->pagesize(count($pagerows), count($pagerows));
        } else {
            $perpage = report_teacherlog_config::PER_PAGE;
            $this->pagesize($perpage, count($rows));
            $pagerows = array_slice($rows, $this->get_page_start(), $perpage);
        }

        foreach ($pagerows as $row) {
            $this->add_data_keyed([
                'datetime' => $row->datetime,
                'fullname' => $row->fullname,
                'coursename' => $row->coursename,
                'modulename' => $row->modulename,
                'action' => $row->action,
            ]);
        }

        $this->finish_output(!$this->is_downloading());
    }
}
