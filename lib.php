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
    $fs = get_file_storage();
    $context = context_module::instance($data->coursemodule);

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
    
    
    if($file->getmime_type() !== 'text/csv'){
        $file->delete();        
        throw new moodle_exception('invalid_file_type', 'mod_smartspe');
    }
    $content = $file->get_content();

    global $DB;

    $rows = array_map('str_getcsv', explode("\n", trim($content)));
    if (count($rows) <= 1) {
        throw new moodle_exception('empty_or_invalid_csv', 'mod_smartspe');
    }

    // Extract and validate header
    $header = array_map('trim', array_shift($rows));
    if (strtolower($header[0]) !== 'group name') {
        throw new moodle_exception('invalid_csv_header', 'mod_smartspe');
    }

    $groupnamemap = []; // to avoid duplicates
    foreach ($rows as $i => $cols) {
        // Skip empty lines
        if (count(array_filter($cols)) === 0) {
            continue;
        }

        $groupname = trim($cols[0]);
        if ($groupname === '') {
            continue;
        }

        // Check for duplicate group names (case-insensitive)
        $lowername = strtolower($groupname);
        if (isset($groupnamemap[$lowername])) {
            continue;
        }

        $userids = array_slice($cols, 1);
        $userids = array_filter(array_map('trim', $userids), fn($u) => $u !== '');

        if (count($userids) === 0) {
            continue;
        }

        // Insert into smartspe_group
        $groupid = $DB->insert_record('smartspe_group', (object)[
            'spe_id' => $speid,
            'name' => $groupname
        ]);

        // Insert into smartspe_group_member
        foreach ($userids as $uid) {
            $DB->insert_record('smartspe_group_member', (object)[
                'group_id' => $groupid,
                'user_id' => (int)$uid
            ]);
        }

        $groupnamemap[$lowername] = true;
    }

    // If no valid rows found
    if (empty($groupnamemap)) {
        throw new moodle_exception('no_valid_groups', 'mod_smartspe');
    }   

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
        $activity->course_id = $data->course;
        $speid = $DB->insert_record('smartspe', $activity);

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

        
        spe_handle_csv($data,$speid);
        
        exit;
        
        // Insert group members for now (hardcoded)        

        $groupid = $DB->insert_record('smartspe_group', (object)[
            'spe_id' => $speid,
            'name' => 'SPE Group 1'
        ]);
        $members = [3,4,5,6,7];
        foreach ($members as $memberid) {
            $DB->insert_record('smartspe_group_member', (object)[
                'group_id' => $groupid,
                'user_id' => $memberid
            ]);
        }

       
        $transaction->allow_commit(); 
        return $speid; 
    } catch (\Throwable $th) {
        $transaction->rollback($th);
        throw $th;
    }
    
 


};

