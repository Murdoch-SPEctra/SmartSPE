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

function smartspe_add_instance($data, $mform = null){


    global $DB;

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

    // Group Management

    // For now we skip this as groups are not enabled

    return $speid;  


};

