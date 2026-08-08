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
 * Language pack consistency tests.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Ensures English and Russian language packs stay in sync.
 */
class lang_test extends \advanced_testcase {

    /**
     * Loads string keys from a plugin language file.
     *
     * @param string $lang
     * @return string[]
     */
    private function get_lang_keys(string $lang): array {
        global $CFG;

        $path = $CFG->dirroot . '/report/teacherlog/lang/' . $lang . '/report_teacherlog.php';
        $this->assertFileExists($path, 'Missing required language file: ' . $path);

        $string = [];
        include($path);

        $keys = array_keys($string);
        sort($keys);
        return $keys;
    }

    /**
     * English is the required base pack; other langs must mirror its keys.
     */
    public function test_language_packs_are_in_sync(): void {
        $enkeys = $this->get_lang_keys('en');
        $rukeys = $this->get_lang_keys('ru');

        $this->assertNotEmpty($enkeys, 'English language pack must not be empty.');
        $this->assertSame($enkeys, $rukeys, 'Russian language pack keys must match English.');
    }
}
