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

// Teacher Team view for a SmartSPE activity .
    
require(__DIR__ . '/../../../config.php');

global $DB, $OUTPUT, $PAGE, $USER;

$id = required_param('id', PARAM_INT);
$teamid = required_param('teamid', PARAM_INT);

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);


$speid = $cm->instance;
$context = context_module::instance($cm->id);

require_capability('mod/smartspe:viewresults', $context);

$smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);
$group = $DB->get_record('smartspe_group', ['id' => $teamid, 'spe_id' => $speid], '*', MUST_EXIST);


$disabled = time() < $smartspe->end_date;

// Page setup.
$PAGE->set_url('/mod/smartspe/teacher/team_view.php',
         ['id' => $id, 'teamid' => $teamid]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

$template_data['groupname'] = $group->name;

$sql = "
    SELECT u.*,      
           s.id AS submissionid
    FROM {smartspe_group_member} gm
    JOIN {user} u ON u.id = gm.user_id
    LEFT JOIN {smartspe_submission} s 
        ON s.student_id = u.id AND s.spe_id = :speid
    WHERE gm.group_id = :teamid
";

$params = ['speid' => $speid, 'teamid' => $teamid];

$members = $DB->get_records_sql($sql, $params);

 
foreach ($members as $member) {   
    $profilepic = core_user::get_profile_picture($member, $context);
    $imageURL = $profilepic->get_url($PAGE)->out();

    $disabledbuttontooltip = 'Cannot view until SPE end date.';
    $viewurl = '#';
    if(!$member->submissionid){
        $disabledbuttontooltip = 'No submission found for this user.';
    }
    else{
        $viewurl = new moodle_url('/mod/smartspe/teacher/view_submission.php', 
            ['id' => $id, 'teamid' => $teamid, 'submissionid' => $member->submissionid]);
    }    

    $template_data['members'][] = [
        'name' => fullname($member),
        'studentid' => $member->id,
        'profilepic' => $imageURL,
        'viewurl' => $viewurl,
        'disabled' => $disabled || !$member->submissionid,
        'disabledbuttontooltip' => $disabledbuttontooltip ,
    ];
}


echo $OUTPUT->render_from_template('mod_smartspe/grouplist', $template_data);

echo $OUTPUT->footer();