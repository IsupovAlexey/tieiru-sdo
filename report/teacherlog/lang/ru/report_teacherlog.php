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

$string['actionfilter'] = 'Действие';
$string['actionfilter_help'] = 'Выберите действие из списка значений в построенном отчёте. Сначала нажмите «Показать отчёт», затем уточните результат.';
$string['actionfilter_placeholder'] = 'Например: просмотр';
$string['allcourses'] = 'Все курсы';
$string['cachedef_reportdata'] = 'Кэш отчёта по активности преподавателей';
$string['chooseteacher'] = 'Выберите преподавателя';
$string['col_action'] = 'Действие';
$string['col_course'] = 'Курс';
$string['col_datetime'] = 'Дата-время';
$string['col_fullname'] = 'ФИО преподавателя';
$string['col_module'] = 'Элемент курса';
$string['coursefilter'] = 'Курс';
$string['coursefilter_help'] = 'Необязательно. После выбора преподавателя в списке остаются только его курсы. Если выбрать курс без преподавателя, преподаватель будет подставлен автоматически.';
$string['coursenotforteacher'] = 'Выбранный курс не назначен этому преподавателю.';
$string['daterangeinvalid'] = 'Дата окончания должна быть позже даты начала.';
$string['daterangetoolong'] = 'Период не может превышать {$a} дней.';
$string['eventreportviewed'] = 'Просмотрен отчёт по активности преподавателя';
$string['filterhint'] = 'Выберите преподавателя и период, затем нажмите «Показать отчёт». Списки преподавателя и курса связаны: выбор преподавателя сужает курсы, выбор курса может подставить преподавателя.';
$string['filterheading'] = 'Параметры отчёта';
$string['filteroptionsafterreport'] = 'Сначала постройте отчёт — затем здесь появятся выпадающие списки для уточнения.';
$string['helpnote'] = 'Этот отчёт показывает действия выбранного преподавателя за указанный период. Он отделён от стандартного журнала событий и предназначен для контроля активности преподавателей.';
$string['invalidteacher'] = 'Выбранный пользователь не является преподавателем.';
$string['modulefilter'] = 'Элемент курса';
$string['modulefilter_help'] = 'Выберите элемент курса из списка значений в построенном отчёте. Сначала нажмите «Показать отчёт», затем уточните результат.';
$string['modulefilter_placeholder'] = 'Часть названия элемента';
$string['nocourseteacher'] = 'Для выбранного курса не найден преподаватель.';
$string['nofiltermatches'] = 'Нет записей, соответствующих выбранным фильтрам. Очистите или измените фильтры и снова нажмите «Показать отчёт».';
$string['nologreader'] = 'Хранилище журналов не включено. Включите стандартное хранилище журналов.';
$string['noevents'] = 'За выбранный период событий не найдено.';
$string['pluginname'] = 'Журнал активности преподавателя';
$string['privacy:metadata'] = 'Плагин отчёта по активности преподавателей не хранит персональные данные.';
$string['refreshdata'] = 'Обновить данные';
$string['reportcount'] = 'Найдено записей: {$a}';
$string['showreport'] = 'Показать отчёт';
$string['textfilterheading'] = 'Уточнение результатов (необязательно)';
$string['teacher'] = 'Преподаватель';
$string['teacher_help'] = 'Обязательно, если не выбран курс. При выборе только курса преподаватель подставляется автоматически.';
$string['timefrom'] = 'Дата начала';
$string['timespentnotavailable'] = 'Затраченное время недоступно: журнал событий фиксирует действия, но не длительность.';
$string['timeto'] = 'Дата окончания';
$string['toomanyrows'] = 'Слишком много записей ({$a->count}). Уменьшите период (максимум {$a->max} записей).';
