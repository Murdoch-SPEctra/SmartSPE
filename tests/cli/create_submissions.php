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
 * CLI script to create sample submissions for testing.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php'); 
require_once(__DIR__ . '/../../locallib.php'); 


global $DB;

$speid = 3; 


echo "Generating test submissions for SmartSPE ID {$speid}...\n";

$cmid = $DB->get_field('course_modules', 'id', ['instance' => $speid], MUST_EXIST);
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$groups = $DB->get_records('smartspe_group', ['spe_id' => $speid]);
$questions = $DB->get_records('smartspe_question', ['spe_id' => $speid]);

$transaction = $DB->start_delegated_transaction();

foreach ($groups as $group) {
   $group_members = $DB->get_records('smartspe_group_member', ['group_id' => $group->id]);
   foreach ($group_members as $group_member) {
        if ($group_member->user_id == 3) { // Skip gil's submission 
                    continue; // skip random to show no submission case
        } 
        // Start a transaction for each submission.
        $member = $DB->get_record('user', ['id' => $group_member->user_id], '*', MUST_EXIST);
        $submission = (object)[
            'spe_id' => $speid,
            'student_id' => $group_member->user_id,
            'last_saved_at' => time(),
            'submitted_at' => time(),
            'reflection' => "This is a self reflection by user {$member->email}."
        ];
        $submissionid = $DB->insert_record('smartspe_submission', $submission);
       

        // Insert peer reviews

        foreach ($group_members as $target_member) {     
                 
            foreach ($questions as $question) {
                $answer = (object)[
                    'submission_id' => $submissionid,
                    'question_id' => $question->id,
                    'target_id' => $target_member->user_id  ,
                    'score' => rand(1, 5), 
                ];
                $DB->insert_record('smartspe_answer', $answer);
            }
            // Insert comment
            $comment = (object)[
                'submission_id' => $submissionid,
                'target_id' => $target_member->user_id,
                'comment' => smartspe_get_random_comment(),
            ];
            $DB->insert_record('smartspe_comment', $comment);
        }        
        echo "Created submission for user ID {$group_member->user_id} in group ID {$group->id}\n";
    }    
}
$transaction->allow_commit();