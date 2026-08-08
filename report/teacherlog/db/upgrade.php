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
 * Upgrade steps.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_report_teacherlog_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024080800) {
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('user-time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2024080800, 'report', 'teacherlog');
    }

    if ($oldversion < 2024080805) {
        get_string_manager()->reset_caches();
        upgrade_plugin_savepoint(true, 2024080805, 'report', 'teacherlog');
    }

    if ($oldversion < 2024080811) {
        get_string_manager()->reset_caches();
        upgrade_plugin_savepoint(true, 2024080811, 'report', 'teacherlog');
    }

    return true;
}
