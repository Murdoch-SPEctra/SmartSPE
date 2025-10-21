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
 * Main view for the SmartSPE activity .
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT); // Course module id.

global $DB, $OUTPUT, $PAGE;

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);

$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);



$context = context_module::instance($cm->id);
require_capability('mod/smartspe:view', $context);

// Page setup.
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Completion: mark viewed.
$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

// Detect role (teacher/manager vs student) using core capability.
$isteacher = has_capability('moodle/course:manageactivities', $context);

// Fetch questions (if any).
$questions = $DB->get_records('smartspe_question', ['spe_id' => $smartspe->id], 'sort_order ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($smartspe->name));

// // Description.
// if (!empty($smartspe->description)) {
//     echo html_writer::div(format_text($smartspe->description, FORMAT_PLAIN, ['context' => $context]), 'mod-smartspe-desc');
// }

// // Availability info.
// $now = time();
// $start = (int)($smartspe->start_date ?? 0);
// $end   = (int)($smartspe->end_date ?? 0);

// if ($start && $now < $start) {
//     echo $OUTPUT->notification('Not open yet. Opens: ' . userdate($start), 'info');
// } else if ($end && $now > $end) {
//     echo $OUTPUT->notification('Closed. Closed on: ' . userdate($end), 'warning');
// } else if ($start || $end) {
//     $msg = [];
//     if ($start) { $msg[] = 'Opens: ' . userdate($start); }
//     if ($end)   { $msg[] = 'Closes: ' . userdate($end); }
//     echo $OUTPUT->notification(implode(' | ', $msg), 'info');
// }

if ($isteacher) {
    require_once(__DIR__ . '/teacher/view.php');
} else {
    require_once(__DIR__ . '/student/view.php');    
}

echo $OUTPUT->footer();



