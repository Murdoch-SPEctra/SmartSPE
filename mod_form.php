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
 * Redesigned per updated spec: CSV group upload, 2–5 Likert questions,
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
        global $DB;
        $mform = $this->_form;        

        // Determine add vs edit, and whether critical fields should be locked.
        $isupdate = !empty($this->current->instance);
        $lockedquestions = false;
        $locked = false;
        $lockgroups = false;
        if ($isupdate) {
            // Fetch current instance to decide locking.
            $spe = $DB->get_record('smartspe', ['id' => (int)$this->current->instance]);
            $hasresponses = !empty($spe) && $DB->record_exists('smartspe_submission', ['spe_id' => $spe->id]);
            
            // Typically groups are also locked once started or responses exist.
            $lockgroups = $hasresponses;
            $lockedquestions = $hasresponses;
            $locked = $hasresponses;
        }

        // Activity core settings.
        $mform->addElement('header', 'generalhdr', get_string('general')); 

        $mform->addElement('text', 'name', get_string('activityname', 'mod_smartspe'),
                            ['size' => 64, 'maxlength' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'activityname', 'mod_smartspe'); 

       // Description

       // ----- Activity Description -----
        

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
        
        // Only required when creating a new instance. On edit, it's optional (keeps existing groups unless replaced).
        if (!$isupdate) {
            $mform->addRule('groupscsv', null, 'required', null, 'client');
            $mform->addElement('hidden', 'isupdate', 0);
        }
        else{
            $mform->addElement('hidden', 'isupdate', 1);
        }
        $mform->setType('isupdate', PARAM_INT);
        if ($isupdate && $locked) {
            // Prevent replacing groups after the activity has started or responses exist.
            $mform->freeze('groupscsv');
            $mform->addElement('static', 'groupscsv_note', '', get_string('groupscsv_locked', 'mod_smartspe'));
        }

        // Evaluation questions.
        $mform->addElement('header', 'questionshdr', get_string('questionsheading', 'mod_smartspe'));
        $mform->addElement('static', 'questioninstructions', '', get_string('questioninstructions', 'mod_smartspe'));
        
        if($isupdate && $lockedquestions) {
            $mform->addElement('static', 'questions_note', '',
                         get_string('questions_locked', 'mod_smartspe'));
        }
        
        // Get preset questions and format for autocomplete.
        $presetquestions = $this->get_preset_questions();
        
        for ($i = 1; $i <= 5; $i++) {
            $name = "questions[$i]"; // Use array syntax in form name.

            // Use autocomplete for preset + custom option.
            $mform->addElement('autocomplete', $name, 
                get_string('questiongrouplabel', 'mod_smartspe', $i),
                $presetquestions,
                [
                    'tags' => true, // Allows custom text entry
                    'placeholder' => get_string('questionplaceholder', 'mod_smartspe', $i)
                ]
            );

            $mform->setType($name, PARAM_TEXT);

            
            // First two questions are required on create; on edit they're required only if not yet started.
            if ($i <= 2 && (!$isupdate || ($isupdate && !$locked))) {
                $mform->addRule($name, get_string('error_requiredquestion', 'mod_smartspe'), 'required', null, 'client');
            }
            if ($isupdate && $locked) {
                $mform->freeze($name);
            }
        }

        $this->standard_coursemodule_elements();
       
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $CFG;
        global $DB;

        $errors = parent::validation($data, $files);
        
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
           

        // first two required when creating; on edit only if not yet started.
        if (!empty($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $i => $val) {
                $val = trim((string)$val);
                if ($i <= 2 && $val === '') {
                    $errors["questions[$i]"] = get_string('required');
                }
            }

            // Make sure the questions are filled sequentially, but only if editable.            
            for ($i = 2; $i <= 4; $i++) {
                $current = trim((string)($data['questions'][$i] ?? ''));
                $next = trim((string)($data['questions'][$i + 1] ?? ''));
                if ($current === '' && $next !== '') {
                        $index = $i + 1;
                        $errors["questions[$index]"] = get_string('error_sequentialquestions',
                                                    'mod_smartspe');
                }
            }            
        }

        // // Validate CSV upload presence (server-side): required only when creating.
        // if (!$isupdate) {
        //     $draftid = (int)($data['groupscsv'] ?? 0);
        //     if (empty($draftid)) {
        //         $errors['groupscsv'] = get_string('required');
        //     } else {
        //         $info = file_get_draft_area_info($draftid);
        //         if (empty($info['filecount'])) {
        //             $errors['groupscsv'] = get_string('required');
        //         }
        //     }
        // }
        return $errors;
    }

    function data_preprocessing(&$default_values) {
       global $DB;

        // If editing an existing instance, populate form with saved values.
        if (!empty($this->current->instance)) {
            $speid = (int)$this->current->instance;
            $smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);

            $default_values['name'] = $smartspe->name;
            $default_values['desc'] = $smartspe->description;
            $default_values['start_date'] = $smartspe->start_date;
            $default_values['end_date'] = $smartspe->end_date;

            // Load questions into the array expected by the form (indices match what form uses).
            $questions = $DB->get_records('smartspe_question', ['spe_id' => $speid], 'sort_order');
            if (!empty($questions)) {
                foreach ($questions as $q) {
                    // Ensure indexes are consistent with form (1..5)
                    $default_values['questions'][(int)$q->sort_order] = $q->text;
                }
            }            

        } 
        else {
            // Default dates when creating a new instance.
            if (empty($default_values['start_date'])) {
                $default_values['start_date'] = time();
            }
            if (empty($default_values['end_date'])) {
                $default_values['end_date'] = time() + WEEKSECS*4;
            }

            // Pull questions from the last modified instance from the same course as defaults.
            $courseid = (int)($this->current->course ?? 0);
            if ($courseid > 0) {
                $spes = $records = $DB->get_records(
                            'smartspe',
                            ['course' => $courseid],
                            'timemodified DESC',
                            'id, timemodified',
                            0,
                            1
                        );
                $lastspe = reset($spes);
                if ($lastspe) {
                    $questions = $DB->get_records('smartspe_question',
                                        ['spe_id' => $lastspe->id], 'sort_order');
                    if (!empty($questions)) {
                        foreach ($questions as $q) {
                            // Ensure indexes are consistent with form (1..5)
                            $default_values['questions'][(int)$q->sort_order] = $q->text;
                        }
                    }
                }
            }
        }

        return;
        
    }
    
    /**
     * Returns preset question options formatted for autocomplete element.
     * 
     * @return array Associative array where both key and value are the question text
     */
    private function get_preset_questions() {
        $questions = [
            'The amount of work and effort put into the Requirements and Analysis Document, the Project Management Plan, and the Design Document',
            'Willingness to work as part of the group and taking responsibility in the group',
            'Communication within the group and participation in group meetings',
            'Contribution to the management of the project, e.g. work delivered on time',
            'Problem solving and creativity on behalf of the group\'s work'
        ];
        
        // Convert to X => X format for autocomplete
        $formatted = ['' => get_string('presetquestion', 'mod_smartspe')];
        foreach ($questions as $question) {
            $formatted[$question] = $question;
        }
        
        return $formatted;
    }
    
}
