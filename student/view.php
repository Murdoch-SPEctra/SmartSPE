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
        $attempturl = new moodle_url('/mod/smartspe/student/attempt.php', ['id' => $cm->id]);
        echo html_writer::div(html_writer::link($attempturl, 'Start evaluation', ['class' => 'btn btn-primary']), 'mod-smartspe-cta');
    } else {
        echo html_writer::div(html_writer::tag('button', 'Start evaluation', [
            'class' => 'btn btn-secondary',
            'disabled' => 'disabled'
        ]), 'mod-smartspe-cta');
    }