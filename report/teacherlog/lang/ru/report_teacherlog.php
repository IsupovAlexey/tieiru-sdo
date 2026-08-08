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

$string['cachedef_reportdata'] = 'Кэш отчёта по активности преподавателей';
$string['chooseteacher'] = 'Выберите преподавателя';
$string['col_action'] = 'Действие';
$string['col_course'] = 'Курс';
$string['col_datetime'] = 'Дата-время';
$string['col_fullname'] = 'ФИО преподавателя';
$string['col_module'] = 'Элемент курса';
$string['coursefilter'] = 'Курс';
$string['coursefilter_help'] = 'Фильтр по названию курса (поиск по подстроке, без учёта регистра).';
$string['daterangeinvalid'] = 'Дата окончания должна быть позже даты начала.';
$string['daterangetoolong'] = 'Период не может превышать {$a} дней.';
$string['eventreportviewed'] = 'Просмотрен отчёт по активности преподавателя';
$string['filterheading'] = 'Параметры отчёта';
$string['helpnote'] = 'Этот отчёт показывает действия выбранного преподавателя за указанный период. Он отделён от стандартного журнала событий и предназначен для контроля активности преподавателей.';
$string['invalidteacher'] = 'Выбранный пользователь не является преподавателем.';
$string['modulefilter'] = 'Элемент курса';
$string['modulefilter_help'] = 'Фильтр по названию элемента курса (поиск по подстроке).';
$string['nologreader'] = 'Хранилище журналов не включено. Включите стандартное хранилище журналов.';
$string['noevents'] = 'За выбранный период событий не найдено.';
$string['pluginname'] = 'Журнал активности преподавателя';
$string['privacy:metadata'] = 'Плагин отчёта по активности преподавателей не хранит персональные данные.';
$string['refreshdata'] = 'Обновить данные';
$string['reportcount'] = 'Найдено записей: {$a}';
$string['showreport'] = 'Показать отчёт';
$string['teacher'] = 'Преподаватель';
$string['timefrom'] = 'Дата начала';
$string['timespentnotavailable'] = 'Затраченное время недоступно: журнал событий фиксирует действия, но не длительность.';
$string['timeto'] = 'Дата окончания';
$string['toomanyrows'] = 'Слишком много записей ({$a->count}). Уменьшите период (максимум {$a->max} записей).';
$string['actionfilter'] = 'Действие';
$string['actionfilter_help'] = 'Фильтр по названию действия (поиск по подстроке).';
