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
 * Language strings for SmartSPE module.
 *
 * @package   mod_smartspe
 * @copyright 2025, SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SmartSPE'; // Generic plugin label sometimes used in admin trees.


// Capability strings.
$string['smartspe:view']   = 'View SmartSPE activity';
$string['smartspe:viewresults']   = 'View SmartSPE results';
$string['smartspe:addinstance'] = 'Add a new SmartSPE activity';

// Required standard activity module strings.
$string['modulename'] = 'Self and Peer Evaluation';
$string['modulenameplural'] = 'Self and Peer Evaluations';
$string['pluginadministration'] = 'Self and Peer Evaluation administration';

// Form labels & help.
$string['activityname'] = 'Activity name';
$string['description'] = 'Description';
$string['activitydescription'] = 'Activity description';
$string['activityname_help'] = 'Enter a name for this Self & Peer Evaluation activity. (Up to 64 characters.) This name will be shown on the course page and in activity lists.';
$string['availability'] = 'Availability';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['questionsheading'] = 'Evaluation questions';
$string['questioninstructions'] = 'Enter 2 to 5 custom Likert-scale questions (ratings 1–5). Leave later fields blank if fewer than 5.';
$string['questionlabel'] = 'Question {$a}';
$string['questiongrouplabel'] = 'Question {$a}';
$string['questionplaceholder'] = 'Question {$a} text';
$string['questionnickplaceholder'] = 'Nickname';
$string['groupsheading'] = 'Groups CSV';
$string['groupsinstructions'] = 'Upload a CSV with columns: GroupName, StudentID. One student per row. Header row optional.';
$string['groupscsv'] = 'Groups CSV file';
$string['commentsinfo_title'] = 'Comments requirement';
$string['commentsinfo_body'] = 'Each student must enter self and peer comments. These are always collected and analysed for sentiment.';
$string['file_not_saved'] = 'File could not be saved. Please try again.';
$string['invalid_file_type'] = 'Invalid file type. Please upload a CSV file.';

// Validation messages.
$string['error_endbeforestart'] = 'End date must be after start date.';
$string['error_minquestions'] = 'At least two questions are required.';
$string['error_question_gap'] = 'Remove gaps: fill earlier question(s) or clear this one.';
$string['error_requiredquestion'] = 'This question text is required.';
$string['error_nickwithoutquestion'] = 'Nickname supplied without a question.';
$string['error_duplicate_nick'] = 'Duplicate nickname: {$a}';



// Other strings.
$string['viewingsubmissionfor'] = 'Viewing submission for: {$a->fullname} (Student ID: {$a->studentid})';


// Attempt
$string['submissionsaved'] = 'Your response has been saved.';
$string['alreadyattempted'] = 'You have already submitted your response for this activity.';

// Errors
$string['spe_notended'] = 'This SmartSPE evaluation has not ended yet.';
$string['spe_notsubmitted'] = 'This submission has not been submitted.';
$string['groupnotfound'] = 'You are not assigned to any group for this activity. Please contact your instructor.';
