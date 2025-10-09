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
 * Main view for the SmartSPE activity (UI demo only – static data).
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$n  = optional_param('n', 0, PARAM_INT);  // Instance id.

global $DB, $OUTPUT, $PAGE;

if ($id) {
    $cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $smartspe = $DB->get_record('smartspe', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $smartspe->course_id ?? $smartspe->course ?? 0], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('smartspe', $smartspe->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingparameter');
}

require_login($course, true, $cm);
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

// Description.
if (!empty($smartspe->description)) {
    echo html_writer::div(format_text($smartspe->description, FORMAT_PLAIN, ['context' => $context]), 'mod-smartspe-desc');
}

// Availability info.
$now = time();
$start = (int)($smartspe->start_date ?? 0);
$end   = (int)($smartspe->end_date ?? 0);

if ($start && $now < $start) {
    echo $OUTPUT->notification('Not open yet. Opens: ' . userdate($start), 'info');
} else if ($end && $now > $end) {
    echo $OUTPUT->notification('Closed. Closed on: ' . userdate($end), 'warning');
} else if ($start || $end) {
    $msg = [];
    if ($start) { $msg[] = 'Opens: ' . userdate($start); }
    if ($end)   { $msg[] = 'Closes: ' . userdate($end); }
    echo $OUTPUT->notification(implode(' | ', $msg), 'info');
}

if ($isteacher) {
    // Teacher view.
    echo $OUTPUT->heading('Teacher view', 3);

    // Quick links (editing UI usually appears when editing is on).
    $editurl = new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]);
    echo html_writer::div(html_writer::link($editurl, 'Edit settings'), 'mod-smartspe-actions');

    // Show configured questions.
    echo $OUTPUT->heading('Questions', 4);
    if ($questions) {
        $list = html_writer::start_tag('ol');
        foreach ($questions as $q) {
            $list .= html_writer::tag('li', format_string($q->text));
        }
        $list .= html_writer::end_tag('ol');
        echo $list;
    } else {
        echo html_writer::div('No questions configured.', 'muted');
    }

    // Placeholder for future reports/actions.
    echo html_writer::div('Reports and responses UI go here.', 'muted');

} else {
    // Student view.
    echo $OUTPUT->heading('Student view', 3);

    // Show questions preview (optional).
    if ($questions) {
        echo $OUTPUT->heading('You will be asked to rate on:', 4);
        $list = html_writer::start_tag('ul');
        foreach ($questions as $q) {
            $list .= html_writer::tag('li', format_string($q->text));
        }
        $list .= html_writer::end_tag('ul');
        echo $list;
    }

    // Call-to-action button (placeholder target for now).
    $open = (!$start || $now >= $start) && (!$end || $now <= $end);
    if ($open) {
        // Replace with actual attempt URL when implemented.
        $attempturl = new moodle_url('/mod/smartspe/attempt.php', ['id' => $cm->id]);
        echo html_writer::div(html_writer::link($attempturl, 'Start evaluation', ['class' => 'btn btn-primary']), 'mod-smartspe-cta');
    } else {
        echo html_writer::div(html_writer::tag('button', 'Start evaluation', [
            'class' => 'btn btn-secondary',
            'disabled' => 'disabled'
        ]), 'mod-smartspe-cta');
    }
}

echo $OUTPUT->footer();



