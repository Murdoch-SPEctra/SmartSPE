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

$string['apiserver'] = 'API Server URL';
$string['apiserver_desc'] = 'The base URL of the sentiment analysis API (e.g., http://localhost:5000)';



// Capability strings.
$string['smartspe:view']   = 'View SmartSPE activity';
$string['smartspe:viewresults']   = 'View SmartSPE results';
$string['smartspe:addinstance'] = 'Add a new SmartSPE activity';

// Required standard activity module strings.
$string['modulename'] = 'Self and Peer Evaluation';
$string['modulename_help'] = '
This activity allows team members to assess their own and the members contribution in a project.
 This was developed in ICT302: IT Professional Practice Project with Mr. Peter Cole as the client,
  supervised by Ms. Noor Alkhateeb and the team includes Rabiya Saleh, Tercia Fernandes,
   Gilchrist Tavares, Meagan Saldanha and Khushank Jain.

The activity is created through a setup form that requires entering the activity name,
 description, start and end dates, and uploading a CSV file containing the group details of
  the students for whom the activity is being created.

Each student logs in to complete their self and peer evaluations by rating both their own and
 their teammates’ contributions to the project. Once submitted, the data is
  automatically recorded and used for performance analysis.

Teachers can access the submitted evaluations, download results as CSV files,
 and review combined scores generated through the system. This allows them to
  compare self and peer ratings, identify participation patterns, and ensure fair
   and transparent grading for each team member.
';
$string['modulename_link'] = 'https://github.com/Murdoch-SPEctra/SmartSPE';
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

// Preset question options.
$string['presetquestion'] = 'Select a preset question or type your own...';

$string['groupsheading'] = 'Groups CSV';
$string['groupsinstructions'] = 'Upload a CSV with columns: GroupName,StudentID1,StudentID2,StudentID3.... Header row required.';
$string['groupscsv'] = 'Groups CSV file';
$string['groupscsv_locked'] = 'Groups CSV is locked after the activity has started or submissions exist.';
$string['questions_locked'] = 'Evaluation questions are locked after submissions exist.';
$string['commentsinfo_title'] = 'Comments requirement';
$string['commentsinfo_body'] = 'Each student must enter self and peer comments. These are always collected and analysed for sentiment.';
$string['file_not_saved'] = 'File could not be saved. Please try again.';
$string['invalid_file_type'] = 'Invalid file type. Please upload a CSV file.';

// Validation messages.
$string['error_startbeforecurrent'] = 'Start date must be in the future.';
$string['error_endbeforestart'] = 'End date must be after start date.';
$string['error_minquestions'] = 'At least two questions are required.';
$string['error_question_gap'] = 'Remove gaps: fill earlier question(s) or clear this one.';
$string['error_requiredquestion'] = 'This question text is required.';


// Student form validation messages.
$string['error_missingrating'] = 'Please provide a rating for {$a}.';
$string['error_missingcomment'] = 'Please provide comments for {$a}.';
$string['error_commentwordcount'] = 'Comments for {$a} must be at least 100 words.';
$string['error_selfreflection'] = 'Please provide your self reflection.';
$string['error_invalidrating'] = 'Invalid rating for {$a}.';
$string['error_invalidmemberid'] = 'Invalid member ID: {$a}.';

// Other strings.
$string['viewingsubmissionfor'] = 'Viewing submission for: {$a->fullname} (Student ID: {$a->studentid})';
$string['viewingcommentsfor'] = 'Viewing comments for: {$a->fullname} (Student ID: {$a->studentid})';

// Attempt
$string['submissionsaved'] = 'Your response has been saved.';
$string['alreadyattempted'] = 'You have already submitted your response for this activity.';

// Errors
$string['missinginstanceid'] = 'Missing instance id in update.';
$string['error_sequentialquestions'] = 'Please fill in questions sequentially without gaps.';
$string['spe_notstarted'] = 'This SPE has not started yet.';
$string['spe_notended'] = 'This SPE evaluation has not ended yet.';
$string['spe_notsubmitted'] = 'This submission has not been submitted.';
$string['groupnotfound'] = 'You are not assigned to any group for this activity. Please contact your instructor.';
$string['spe_ended'] = 'This SPE has already ended. You can no longer submit responses.';
$string['invalidstudentid'] = 'Unknown student ID: {$a}';
$string['file_not_saved'] = 'File could not be saved. Please try again.';
$string['empty_or_invalid_csv'] = 'The uploaded CSV file is empty or invalid. Please check the file and try again.';
$string['invalid_file_type'] = 'Invalid file type. Please upload a CSV file.';
$string['invalid_csv_header'] = 'Invalid CSV header. The first column must be "GroupName".';
$string['duplicategroupname'] = 'Duplicate group name detected: {$a->name} on line {$a->line}';
$string['duplicatestudentingroup'] = 'Student ID {$a->sid} appears again (line {$a->line}) in another group.';
$string['empty_group'] = 'A group with no student IDs was found. Please ensure all groups have at least one student.';  
$string['no_valid_groups'] = 'No valid groups were found in the uploaded CSV file. Please check the file and try again.';
$string['studentnotenrolled'] = 'Student ID {$a->sid} {$a->name} is not enrolled in this course.';
$string['noindexpage'] = 'There is no index page for the SmartSPE activity. Please access it through the course page.';
$string['cannotcreatezip'] = 'Cannot create ZIP file for export. Please try again later.';
$string['nogroupsfound'] = 'No groups found for this SmartSPE activity. Cannot generate reports.';
// Scheduled task
$string['sentiment_task'] = 'SmartSPE Sentiment Analysis Task';
$string['clean_draft_task'] = 'SmartSPE Clean Draft Submissions Task';