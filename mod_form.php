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
 * Form definition for adding/updating Self & Peer Evaluation (SmartSPE) instances.
 * Redesigned per updated spec: CSV group upload, 2–5 Likert questions with optional nicknames,
 * start/end scheduling and minimal core module options.
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

class mod_smartspe_mod_form extends moodleform_mod {

    /**
     * Defines forms elements.
     */
    public function definition() {
        $mform = $this->_form;

        

        // Activity core settings.
        $mform->addElement('header', 'generalhdr', get_string('general')); 

        $mform->addElement('text', 'name', get_string('activityname', 'mod_smartspe'),
                            ['size' => 64, 'maxlength' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'activityname', 'mod_smartspe'); 

       // Description

       // ----- Activity Description -----
        $mform->addElement('header', 'descriptionhdr',
             get_string('description', 'mod_smartspe'));

        // Add a normal textarea for description
        $mform->addElement('textarea', 'desc',
                get_string('activitydescription', 'mod_smartspe'), [
            'rows' => 10,
            'cols' => 50
        ]);

        // Set type to clean text
        $mform->setType('desc', PARAM_TEXT);
        

        

        // Availability scheduling.
        $mform->addElement('header', 'availabilityhdr', get_string('availability', 'mod_smartspe'));
        $mform->addElement('date_time_selector', 'start_date', get_string('startdate', 'mod_smartspe'));
        $mform->setType('start_date', PARAM_INT);
        $mform->addRule('start_date', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'end_date', get_string('enddate', 'mod_smartspe'));
        $mform->setType('end_date', PARAM_INT);
        $mform->addRule('end_date', null, 'required', null, 'client');

        // Group CSV upload.
        $mform->addElement('header', 'groupshdr', get_string('groupsheading', 'mod_smartspe'));
        $mform->addElement('static', 'groupsinfo', '', get_string('groupsinstructions', 'mod_smartspe'));
        $fileoptions = [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.csv'],
        ];
        $mform->addElement('filepicker', 'groupscsv', get_string('groupscsv', 'mod_smartspe'), null, $fileoptions);
        $mform->addRule('groupscsv', null, 'required', null, 'client');

        // Evaluation questions.
        $mform->addElement('header', 'questionshdr', get_string('questionsheading', 'mod_smartspe'));
        $mform->addElement('static', 'questioninstructions', '', get_string('questioninstructions', 'mod_smartspe'));

        for ($i = 1; $i <= 5; $i++) {
            $qgrp = [];
            $qgrp[] =& $mform->createElement('text', 'question' . $i, '',
            [  
                'size' => 60,
                'maxlength' => 255,
                'placeholder' => get_string('questionplaceholder', 'mod_smartspe', $i)
            ]);
            $mform->addGroup($qgrp, 'questiongroup' . $i,
                get_string('questiongrouplabel', 'mod_smartspe', $i),
                 '  ', false);
            $mform->setType('question' . $i, PARAM_TEXT);
            if ($i <= 2) {
                $mform->addRule('questiongroup' . $i, get_string('error_requiredquestion', 'mod_smartspe'), 'required', null, 'client');
            }
        }

        $this->standard_coursemodule_elements();
       
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);
        // Server side validation.
        // Activity name.
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = get_string('required');
        } else if (\core_text::strlen($name) > 64) {
            $errors['name'] = get_string('maximumchars', '', 64);
        }

        // Validate scheduling.
        $start = (int)($data['start_date'] ?? 0); 
        $end   = (int)($data['end_date'] ?? 0);

        if (empty($start)) {
            $errors['start_date'] = get_string('required');
        }
        if (empty($end)) {
            $errors['end_date'] = get_string('required');
        } else if (!empty($start) && $end <= $start) {
            $errors['end_date'] = get_string('error_endbeforestart', 'mod_smartspe');
        }

        // first two required, each <= 255 chars.
        for ($i = 1; $i <= 5; $i++) {
            $key = 'question' . $i;
            $val = trim((string)($data[$key] ?? ''));

            if ($i <= 2 && $val === '') {
                $errors['questiongroup' . $i] = get_string('required');
            }

            if ($val !== '' && \core_text::strlen($val) > 255) {
                $errors[$key] = get_string('maximumchars', '', 255);
            }
        }

        // Validate CSV upload presence (server-side).
        $draftid = (int)($data['groupscsv'] ?? 0);
        if (empty($draftid)) {
            $errors['groupscsv'] = get_string('required');
        } else {            
            $info = file_get_draft_area_info($draftid);
            if (empty($info['filecount'])) {
                $errors['groupscsv'] = get_string('required');
            }
        }

        return $errors;
    }

    function data_preprocessing(&$default_values) {
       // Default dates when creating a new instance.
        if (empty($this->current->instance)) {
            if (empty($default_values['start_date'])) {
                $default_values['start_date'] = time();
            }
            if (empty($default_values['end_date'])) {
                $default_values['end_date'] = time() + WEEKSECS*4;
            }
        }       
    }
    
}
