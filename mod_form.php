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

class mod_smartspe_mod_form extends moodleform_mod {

    /**
     * Define the form fields for creating/updating an SPE activity.
     */
    public function definition() {
        $mform = $this->_form;

        // Activity core settings.
        $mform->addElement('header', 'generalhdr', get_string('general')); // Core general header.

        $mform->addElement('text', 'name', get_string('activityname', 'mod_smartspe'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'activityname', 'mod_smartspe'); // Optional if help string exists later.

        // Standard intro (description) - stored in intro/introformat automatically by core.
        $this->standard_intro_elements();

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
            $qgrp[] =& $mform->createElement('text', 'question' . $i, '', ['size' => 60, 'maxlength' => 255, 'placeholder' => get_string('questionplaceholder', 'mod_smartspe', $i)]);
            $qgrp[] =& $mform->createElement('text', 'question' . $i . '_nick', '', ['size' => 20, 'maxlength' => 50, 'placeholder' => get_string('questionnickplaceholder', 'mod_smartspe')]);
            $mform->addGroup($qgrp, 'questiongroup' . $i, get_string('questiongrouplabel', 'mod_smartspe', $i), '  ', false);
            $mform->setType('question' . $i, PARAM_TEXT);
            $mform->setType('question' . $i . '_nick', PARAM_ALPHANUMEXT);
            if ($i <= 2) {
                $mform->addRule('questiongroup' . $i, get_string('error_requiredquestion', 'mod_smartspe'), 'required', null, 'client');
            }
        }

        // IMPORTANT: moodleform_mod expects core hidden fields (course, module, section, etc.).
        // Re-introduce standard elements, then optionally we could remove or freeze ones we don't want to expose.
        $this->standard_coursemodule_elements();
        // Example (uncomment to hide grading/completion controls if not used yet):
        // $mform->removeElement('availabilityconditionsjson');
        // $mform->removeElement('completion');
        // $mform->removeElement('completionexpected');
        // $mform->removeElement('grade');

        $this->add_action_buttons();
    }

    /**
     * Custom validation: ensure dates make sense and question ordering has no gaps.
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Start must be before end.
        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['start_date'] >= $data['end_date']) {
            $errors['end_date'] = get_string('error_endbeforestart', 'mod_smartspe');
        }

        // Validate question continuity & minimum (2 required) and nicknames uniqueness if provided.
        $providedcount = 0; $previousblank = false; $nicks = [];
        for ($i = 1; $i <= 5; $i++) {
            $q = trim($data['question' . $i] ?? '');
            $nick = trim($data['question' . $i . '_nick'] ?? '');
            if ($q === '') {
                $previousblank = true;
                if ($nick !== '') {
                    $errors['questiongroup' . $i] = get_string('error_nickwithoutquestion', 'mod_smartspe');
                }
            } else {
                $providedcount++;
                if ($previousblank) {
                    $errors['questiongroup' . $i] = get_string('error_question_gap', 'mod_smartspe');
                }
                if ($nick !== '') {
                    if (in_array(strtolower($nick), $nicks, true)) {
                        $errors['questiongroup' . $i] = get_string('error_duplicate_nick', 'mod_smartspe', $nick);
                    } else {
                        $nicks[] = strtolower($nick);
                    }
                }
            }
        }
        if ($providedcount < 2) {
            $errors['questiongroup1'] = get_string('error_minquestions', 'mod_smartspe');
        }

        return $errors;
    }
}
