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
 * Plugin configuration constants.
 *
 * @package   report_teacherlog
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_teacherlog;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin configuration constants.
 */
final class config {
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
