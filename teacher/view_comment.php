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
 * Teacher view for viewing comments for a student for the SmartSPE activity .
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Teacher Submission view for a SmartSPE activity .
    
require(__DIR__ . '/../../../config.php');
require(__DIR__ . '/../locallib.php');


global $DB, $OUTPUT, $PAGE, $USER;

$id = required_param('id', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);


$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);


$speid = $cm->instance;
$context = context_module::instance($cm->id);

require_capability('mod/smartspe:viewresults', $context);

$smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);

if (time() < $smartspe->end_date) 
        throw new moodle_exception('spe_notended', 'mod_smartspe');

$user = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);

// Page setup.
$PAGE->set_url('/mod/smartspe/teacher/view_submission.php',
         ['id' => $id, 'studentid' => $studentid]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();


// --- Build template data from DB ---

// Get all comments targetting student id from an SPE submission

$comments = $DB->get_records_sql(
    "SELECT c.*, s.student_id
       FROM {smartspe_comment} c
       JOIN {smartspe_submission} s ON s.id = c.submission_id
      WHERE s.spe_id = :speid
        AND c.target_id = :targetid",
    ['speid' => $speid, 'targetid' => $studentid]
);

$submission = $DB->get_record('smartspe_submission', 
        ['student_id' => $studentid, 'spe_id' => $speid], '*', IGNORE_MISSING);
$reflection = $submission->reflection ?? '';

$template_data = [
    'viewingcommentsfor' => get_string('viewingcommentsfor', 'mod_smartspe', 
                (object)['fullname' => fullname($user),
                 'studentid' => smartspe_get_studentid_from_email($user->email)]
                ),
    'members' => []
];      
usort($comments, function($a, $b) use ($studentid) {
    if ($a->student_id == $studentid) return -1; // $a first
    if ($b->student_id == $studentid) return 1;  // $b first
    return 0;                                     // leave order
});

foreach ($comments as $c) {
    $user = $DB->get_record('user', ['id' => $c->student_id] , '*', MUST_EXIST);

    // Comment for this member
    $member_comment = $c->comment;    
    $sentiment = $c->sentiment;
    $senticlass = match ($sentiment) {
                'Positive' => 'bg-success-subtle text-success border border-success',
                'Negative' => 'bg-danger-subtle text-danger border border-danger',
                'Neutral'  => 'bg-warning-subtle text-warning border border-warning',
                default    => 'bg-secondary-subtle text-body-secondary border border-secondary',
    };
    

    // Self-reflection (only for the submitter)
    $is_self = ($c->student_id == $studentid);
    $reflection = $is_self ? $reflection : null;

    $profilepic = core_user::get_profile_picture($user, $context);
    $imageURL = $profilepic->get_url($PAGE)->out();

    $template_data['members'][] = [
        'userid' => $user->id,
        'fullname' => fullname($user),
        'studentid' => smartspe_get_studentid_from_email($user->email),
        'profilepic' => $imageURL,
        'comment' => $member_comment,  
        'senticlass' => $senticlass,    
        'sentiment' => $sentiment,
        'isself' => $is_self,
        'selfreflect' => $reflection, // only for self but included as null otherwise
    ];
}

$PAGE->requires->js_call_amd('mod_smartspe/tooltips', 'init');
echo $OUTPUT->render_from_template('mod_smartspe/comment', $template_data);

echo $OUTPUT->footer();