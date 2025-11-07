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
 * Teacher view for viewing submissions for the SmartSPE activity .
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
$teamid = required_param('teamid', PARAM_INT);
$submissionid = required_param('submissionid', PARAM_INT);


$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);


$speid = $cm->instance;
$context = context_module::instance($cm->id);

require_capability('mod/smartspe:viewresults', $context);

$smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);

if (time() < $smartspe->end_date) 
        throw new moodle_exception('spe_notended', 'mod_smartspe');


$group = $DB->get_record('smartspe_group',
        ['id' => $teamid, 'spe_id' => $speid], '*', MUST_EXIST);
$submission = $DB->get_record('smartspe_submission', 
        ['id' => $submissionid,], '*', MUST_EXIST);
if(!$submission->submitted_at ){
    throw new moodle_exception('spe_notsubmitted', 'mod_smartspe');
}
$user = $DB->get_record('user', 
        ['id' => $submission->student_id,], '*', MUST_EXIST);




// Page setup.
$PAGE->set_url('/mod/smartspe/teacher/view_submission.php',
         ['id' => $id, 'teamid' => $teamid, 'submissionid' => $submissionid]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();


// --- Build template data from DB ---

$group_members = $DB->get_records('smartspe_group_member', 
        ['group_id' => $teamid] , '' , 'user_id');
$criteria = $DB->get_records('smartspe_question', ['spe_id' => $smartspe->id], 'sort_order ASC');
$answers = $DB->get_records('smartspe_answer', ['submission_id' => $submissionid]);
$comments = $DB->get_records('smartspe_comment', ['submission_id' => $submissionid]);

$template_data = [
    'viewingsubmissionfor' => get_string('viewingsubmissionfor', 'mod_smartspe', 
                (object)['fullname' => fullname($user),
                 'studentid' => smartspe_get_studentid_from_email($user->email)]
                ),
    'members' => []
];      
usort($group_members, function($a, $b) use ($submission) {
    if ($a->user_id == $submission->student_id) return -1; // $a before $b
    if ($b->user_id == $submission->student_id) return 1;  // $b before $a
    return 0;                                              // leave order
});

foreach ($group_members as $member) {
    $user = $DB->get_record('user', ['id' => $member->user_id] , '*', MUST_EXIST);  

    // Build criteria rows
    $criteriaRow1 = [];
    $criteriaRow2 = [];
    $total = 0;
    $count = 0;
    foreach ($criteria as $criterion) {
        $score = null;
        foreach ($answers as $answer) {
            if ($answer->target_id == $member->user_id && $answer->question_id == $criterion->id) {
                $score = $answer->score;
                break;
            }
        }
        if($score == null) {
            throw new moodle_exception('Data inconsistency: Missing score for user ' .
                             $member->user_id . ' criterion ' . $criterion->id);
        }
        $row = [
            'text' => 'Criteria ' . $criterion->sort_order,
            'score' => $score,
            'tooltip' => $criterion->text,
        ];
        if($count < 3) {
            $criteriaRow1[] = $row;
        } else {
            $criteriaRow2[] = $row;
        }
        $total += $score;
        $count++;       

    }
    $averagerow = [
        'text' => 'Average',
        'score' => round($total / $count, 1) ,
        'tooltip' => 'Average score',
    ];

    if($count < 3) {
        $criteriaRow1[] = $averagerow;
    } else {
        $criteriaRow2[] = $averagerow;
    }

    // Comment for this member
    $member_comment = '';
    $senticlass = '';
    $sentiment = '';
    foreach ($comments as $comment) {
        if ($comment->target_id == $member->user_id) {
            $member_comment = $comment->comment;
            $sentiment = $comment->sentiment;
            $senticlass = match ($sentiment) {
                'Positive' => 'bg-success-subtle text-success border border-success',
                'Negative' => 'bg-danger-subtle text-danger border border-danger',
                'Neutral'  => 'bg-warning-subtle text-warning border border-warning',
                default    => 'bg-secondary-subtle text-body-secondary border border-secondary',
            };
            break;
        }
    }

    // Self-reflection (only for the submitter)
    $is_self = ($member->user_id == $submission->student_id);
    $reflection = $is_self ? $submission->reflection : null;

    $profilepic = core_user::get_profile_picture($user, $context);
    $imageURL = $profilepic->get_url($PAGE)->out();

    $template_data['members'][] = [
        'userid' => $user->id,
        'fullname' => fullname($user),
        'studentid' => smartspe_get_studentid_from_email($user->email),
        'profilepic' => $imageURL,
        'criteriaRow1' => $criteriaRow1,
        'criteriaRow2' => $criteriaRow2,
        'comment' => $member_comment,  
        'senticlass' => $senticlass,    
        'sentiment' => $sentiment,
        'isself' => $is_self,
        'selfreflect' => $reflection, // only for self but included as null otherwise
    ];
}

$PAGE->requires->js_call_amd('mod_smartspe/tooltips', 'init');

echo $OUTPUT->render_from_template('mod_smartspe/submission', $template_data);

echo $OUTPUT->footer();