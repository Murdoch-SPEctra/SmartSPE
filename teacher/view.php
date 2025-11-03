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
 * Teacher view for the SmartSPE activity .
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Teacher view.

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


global $DB, $OUTPUT, $PAGE, $USER;
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$speid = $cm->instance;
$smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);
$teams = $DB->get_records('smartspe_group', ['spe_id' => $speid]);


$desc = format_text($smartspe->description, FORMAT_PLAIN, ['context' => $context]);

$now = time();
$start = (int)($smartspe->start_date);
$end   = (int)($smartspe->end_date);

// Disable if it before end
$disabled = $now < $end; // Disable buttons if before end date

$status = '';
$statusclass = '';

if ($start && $now < $start) {
    $status = 'Not open yet. Opens: ' . userdate($start);
    $statusclass = 'warning';
} else if ($end && $now > $end) {
    $status = 'Closed. Closed on: ' . userdate($end);
    $statusclass = 'info';
} else if ($start || $end) {
    $parts = [];
    if ($start) { $parts[] = 'Opens: ' . userdate($start); }
    if ($end)   { $parts[] = 'Closes: ' . userdate($end); }
    $status = implode(' | ', $parts);
    $statusclass = 'warning';
}

$teamdata = [];

foreach ($teams as $team) {
    $viewurl = new moodle_url('/mod/smartspe/teacher/team_view.php',
        ['id' => $id, 'teamid' => $team->id]);
    $csvurl = new moodle_url('/mod/smartspe/teacher/export.php', 
        ['id' => $id, 'teamid' => $team->id]);
    
    $teamdata[] = [
        'name' => $team->name,
        'viewurl' => $viewurl,
        'csvurl' => $csvurl,
        'disabled' => $disabled,
    ];
}

$editurl = new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]);


// Pass data to template
$templatecontext = [
    'teams' => $teamdata,
    'description' => $desc,
    'status' => $status,
    'disabled' => $disabled,
    'exportallurl' => new moodle_url('/mod/smartspe/teacher/export.php', ['id' => $id]),
    'statusclass' => $statusclass,    
];
echo $OUTPUT->render_from_template('mod_smartspe/teacher_view', $templatecontext);
   

