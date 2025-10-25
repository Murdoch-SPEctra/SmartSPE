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
 * Library of functions and callbacks for SmartSPE module.
 * Implements add/update/delete instance logic mapping the form fields
 * to the custom schema defined in install.xml.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_smartspe\local\CSV;

function smartspe_supports($feature) {
    // ../../lib/moodlelib.php line 406 for all features.
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true; // Test this 
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_QUICKCREATE:
            return false;        
        case FEATURE_GRADE_HAS_GRADE:
            return false; 
        default:
            return null;
    }
}
function spe_handle_csv($data , $speid){
    global $DB;
    $fs = get_file_storage();
    $context = context_module::instance($data->coursemodule);
    $groups = [];
    file_save_draft_area_files(
        $data->groupscsv,                  // draftitemid from form
        $context->id,                     // context
        'mod_smartspe',                 // component
        'groupcsv',                     // name of file area
        $speid,                              // each file is tied to the SPE
        [
            'subdirs' => 0,
            'maxfiles' => 1,
            'maxbytes' => 1048576, // 1MB
        ]
    );
    $files = $fs->get_area_files($context->id, 'mod_smartspe', 'groupcsv', $speid, 'sortorder', false);

    if (!$files) {
        throw new moodle_exception('file_not_saved', 'mod_smartspe');
    }
    $file = reset($files);    
    
    $filename = $file->get_filename();
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'csv') {
        // Needs testing 
        throw new moodle_exception('invalid_file_type', 'mod_smartspe');
    }

    $content = $file->get_content();

    $content = str_replace("\r", "\n", trim($content));
        
    $rows = array_map(function($line) {
        return str_getcsv($line, ',', '"', '\\');
    }, explode("\n", trim($content)));

    if (count($rows) <= 1) {
        throw new moodle_exception('empty_or_invalid_csv', 'mod_smartspe');
    }

    // Extract and validate header
    $header = array_map('trim', array_shift($rows));
    if (strtolower($header[0]) !== 'groupname') {
        throw new moodle_exception('invalid_csv_header', 'mod_smartspe');
    }

    $groupnamemap = []; // to avoid duplicates
    foreach ($rows as $i => $cols) {
        // Skip empty lines
        if (count(array_filter($cols)) === 0) {
            continue;
        }

        $line_no = $i + 2; // +2 for header and 0-based index

        $groupname = trim($cols[0]);
        if ($groupname === '') {
            continue;
        }

        // Check for duplicate group names (case-insensitive)
        $lowername = strtolower($groupname);
        if (isset($groupnamemap[$lowername])) {
            $msgdata = (object)[
                'name' => $groupname,
                'line' => $line_no,
            ];
            throw new moodle_exception('duplicategroupname',
                         'mod_smartspe', '', $msgdata);
        }

        $studentids = array_slice($cols, 1);
        $studentids = array_filter(array_map('trim', $studentids), fn($u) => $u !== '');

        if (count($studentids) === 0) {
            throw new moodle_exception('empty_group', 'mod_smartspe');
        }

        foreach ($studentids as $sid) {
        if (isset($studentmap[$sid])) {
            // Found duplicate student ID in another group.
            $prevline = $studentmap[$sid];
            $msgdata = (object)[
                'sid' => $sid,
                'line' => $line,
            ];
            throw new moodle_exception('duplicatestudentingroup', 'mod_smartspe', '', $msgdata);
        }
        $studentmap[$sid] = $line;
        }

        $groups[$groupname] = [
            'name' => $groupname,
            'studentids' => $studentids
        ];

        $groupnamemap[$lowername] = true;
    }

    // If no valid rows found
    if (empty($groupnamemap)) {
        throw new moodle_exception('no_valid_groups', 'mod_smartspe');
    }   

    return $groups;

}

function smartspe_add_instance($data, $mform = null){

    global $DB;
    try {
        $transaction = $DB->start_delegated_transaction();

        // Create an acitivty object
        $activity = new stdClass();
        $activity->name = $data->name;
        $activity->description = $data->desc;
        $activity->created_by = 2; // Admin user id for testing , need to remove this
        $activity->start_date = $data->start_date;
        $activity->end_date = $data->end_date;
        $activity->course = $data->course;
        $speid = $DB->insert_record('smartspe', $activity);

        $groups = spe_handle_csv($data, $speid);

        // Now handle the questions
        
        foreach ($data->questions as $i => $questiontext) {
            $questiontext = trim((string)$questiontext);
            if ($questiontext !== '') {
                $question = new stdClass();
                $question->spe_id = $speid;
                $question->sort_order = (int)$i;
                $question->text = $questiontext;
                $DB->insert_record('smartspe_question', $question);
            }
        }        
        foreach ($groups as $groupdata) {
            $group = new stdClass();
            $group->spe_id = $speid;
            $group->name = $groupdata['name'];
            $groupid = $DB->insert_record('smartspe_group', $group);
            
            foreach ($groupdata['studentids'] as $studentid) {
                $email = $studentid . '@student.murdoch.edu.au';

                // Fetch the Moodle user by email.
                $user = $DB->get_record('user',
                             ['email' => $email, 'deleted' => 0],
                              'id', IGNORE_MISSING);
                if (!$user) {
                    throw new moodle_exception('invalidstudentid',
                         'mod_smartspe', '', $studentid);
                }

                $member = (object)[
                    'group_id' => $groupid,
                    'user_id' => $user->id,
                ];
                $DB->insert_record('smartspe_group_member', $member);
            }
        }        
        $transaction->allow_commit(); 
        return $speid; 
    } catch (\Throwable $th) {
        throw $th;
    }

};

function smartspe_delete_instance($speid) {
    global $DB;

    try{
        $transaction = $DB->start_delegated_transaction();
        // Get all related groups
        $groups = $DB->get_records('smartspe_group', ['spe_id' => $speid]);
        foreach ($groups as $group) {
            // Delete members first
            $DB->delete_records('smartspe_group_member', ['group_id' => $group->id]);
            // Delete the group
            $DB->delete_records('smartspe_group', ['id' => $group->id]);
        }

        // Delete questions
        $DB->delete_records('smartspe_question', ['spe_id' => $speid]);

        // Get all submissions

        $submissions = $DB->get_records('smartspe_submission', ['spe_id' => $speid]);

        foreach ($submissions as $submission) {
            $sid = $submission->id;
            // Delete answers first
            $DB->delete_records('smartspe_answer', ['submission_id' => $sid]);
            // Delete self reflection
            $DB->delete_records('smartspe_self_reflection', ['submission_id' => $sid]);
            // Delete comments
            $DB->delete_records('smartspe_comment', ['submission_id' => $sid]);
            // Delete the submission
            $DB->delete_records('smartspe_submission', ['id' => $sid]);
        }
        // Delete the main activity
        $DB->delete_records('smartspe', ['id' => $speid]);
       
        $transaction->allow_commit();
        mtrace("SmartSPE instance {$speid} and all related data deleted.");
        return true;

    } catch (\Throwable $th) {
        mtrace("Error deleting SmartSPE instance {$speid}: " . $th->getMessage());
        throw $th;
    }
}
