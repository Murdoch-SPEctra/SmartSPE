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
 * Student view for the SmartSPE activity .
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // Student view.
//     echo $OUTPUT->heading('Student view', 3);

//     echo $OUTPUT->header();
// echo $OUTPUT->heading(format_string($smartspe->name));

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

    // Call-to-action button (placeholder target for now).
    $open = (!$start || $now >= $start) && (!$end || $now <= $end);
    if ($open) {
        // Replace with actual attempt URL when implemented.
        $attempturl = new moodle_url('/mod/smartspe/student/attempt.php', ['id' => $cm->id]);
        echo html_writer::div(html_writer::link($attempturl, 'Start evaluation', ['class' => 'btn btn-primary']), 'mod-smartspe-cta');
    } else {
        echo html_writer::div(html_writer::tag('button', 'Start evaluation', [
            'class' => 'btn btn-secondary',
            'disabled' => 'disabled'
        ]), 'mod-smartspe-cta');
    }