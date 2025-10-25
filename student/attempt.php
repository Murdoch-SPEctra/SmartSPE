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
 * Attempt view for the SmartSPE activity.
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../classes/form/attempt_form.php');
require_once(__DIR__ . '/../locallib.php');

use mod_smartspe\form\attempt_form;

$id = required_param('id', PARAM_INT); // Course module id

global $DB, $OUTPUT, $PAGE, $USER;

// Normally you’d fetch from DB, but for testing we load JSON
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);

$context = context_module::instance($cm->id);


// Pull the group for the current user
$sql = "SELECT g.*
          FROM {smartspe_group} g
          JOIN {smartspe_group_member} gm ON g.id = gm.group_id
         WHERE g.spe_id = :speid AND gm.user_id = :userid";
$params = ['speid' => $smartspe->id, 'userid' => $USER->id];
$group = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
if (!$group) {
    throw new moodle_exception('groupnotfound', 'mod_smartspe');
}

// Check if they already submitted then error cuz no 2nd attempts
// And check if its not a draft submission
$submission = $DB->get_record('smartspe_submission', [
    'spe_id' => $smartspe->id,
    'student_id' => $USER->id
]);
if ($submission && $submission->submitted_at) {
    throw new moodle_exception('alreadyattempted', 'mod_smartspe');
}else if (time() > $smartspe->end_date) {
    throw new moodle_exception('spe_ended', 'mod_smartspe');
}

// Pull group members excluding current user
$sql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname, u.email
        FROM {user} u
        JOIN {smartspe_group_member} gm ON u.id = gm.user_id
        WHERE gm.group_id = :gid AND u.id != :uid
        ORDER BY u.lastname ASC";
$members = $DB->get_records_sql($sql, ['gid' => $group->id, 'uid' => $USER->id]);


// Pull questions for this SPE
$questions = $DB->get_records('smartspe_question',
            ['spe_id' => $smartspe->id], 'sort_order ASC'
);
// Not displaying studentids in forms
// foreach ($members as $member) {
        // Add displaystudentid property
        //$member->displaystudentid = smartspe_get_studentid_from_email($member->email);
// }
$customdata = [
    'userid'    => $USER->id,
    'displaystudentid' => smartspe_get_studentid_from_email($USER->email),
    'cmid'      => $cm->id,
    'speid'     => $smartspe->id,
    'spe_name'  => $smartspe->name,
    'group'     => [
        'id'      => $group->id,
        'name'    => $group->name,
        'members' => array_values($members)
    ],
    'questions' => array_values($questions)
];
$actionurl = new moodle_url('/mod/smartspe/student/attempt.php', ['id' => $cm->id]);
$mform = new attempt_form($actionurl, $customdata);


// Handle Form submission
if ($mform->is_cancelled()) {
    // Redirect to course page 
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
} 
else if ($data = $mform->get_data()) {

    try {
        // Store data in DB
        $timenow = time();
        $submission = (object)[
            'spe_id' => $smartspe->id,
            'student_id' => $USER->id,
            'last_saved_at' => $timenow,
            'submitted_at' => $timenow            
        ];

        $transaction = $DB->start_delegated_transaction();

        $submission->id = $DB->insert_record('smartspe_submission', $submission);

        foreach ($data->rating as $targetid => $questions) {
            foreach ($questions as $questionid => $score) {
                $answer = (object)[
                    'submission_id' => $submission->id,
                    'question_id' => $questionid,
                    'target_id' => $targetid,
                    'score' => $score
                ];
                $DB->insert_record('smartspe_answer', $answer);
            }
        }
        $payload = [];
        foreach ($data->comment as $targetid => $commenttext) {
            $payload[$targetid] = $commenttext;
        }
        $sentiments = smartspe_get_sentiment_batch($payload);
        
        foreach ($data->comment as $targetid => $commenttext) {
            $comment = (object)[
                'submission_id' => $submission->id,
                'target_id' => $targetid,
                'comment' => $commenttext,
                'sentiment' => $sentiments[$targetid] ?? null 
                // May be null if sentiment API failed
            ];
            
            $DB->insert_record('smartspe_comment', $comment);
        }
        $selfreflect = (object)[
            'submission_id' => $submission->id,
            'reflection' => $data->selfreflect
        ];
        $DB->insert_record('smartspe_selfreflect', $selfreflect);

        $transaction->allow_commit(); 

        // Redirect to course page
        redirect(new moodle_url('/course/view.php', ['id' => $course->id
        ]), get_string('submissionsaved', 'mod_smartspe') );

    } catch (\Throwable $th) {
        $transaction->rollback($th);
        throw $th;
    }    

} 
else {
    // Display form

    $PAGE->set_url('/mod/smartspe/student/attempt.php', ['id' => $cm->id]);
    $PAGE->set_title(format_string($smartspe->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('incourse');

    echo $OUTPUT->header();
    echo $OUTPUT->heading($smartspe->name . ' - ' . $group->name);
    $mform->display();
    echo $OUTPUT->footer();
}




