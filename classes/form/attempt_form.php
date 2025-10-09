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
 * Form for attempting the SmartSPE activity.
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_smartspe\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');


class attempt_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Get customdata passed from attempt.php
        $customdata = $this->_customdata;
        $group = $customdata['group'] ;
        $questions = $customdata['questions'] ;

        // Sort questions by sort_order
        usort($questions, function($a, $b) {
            return ($a->sort_order ?? 0) <=> ($b->sort_order ?? 0);
        });

        $scale_text = <<<EOT
        Assessment scale:
        1 = Very poor, or even obstructive, contribution to the project process
        2 = Poor contribution to the project process
        3 = Acceptable contribution to the project process
        4 = Good contribution to the project process
        5 = Excellent contribution to the project process
        EOT;

        $mform->addElement('static', 'scaleinfo', '', '<pre>' . s($scale_text) . '</pre>');
        
        $options = [
                '' => 'Select a rating...',
                1 => '1 - Very poor',
                2 => '2 - Poor',
                3 => '3 - Acceptable',
                4 => '4 - Good',
                5 => '5 - Excellent'
            ];


        // Self-evaluation section
        $mform->addElement('header', 'self_eval', 'Self-Evaluation (You)');
        $memberid = $customdata['userid']; // the evaluator themselves

        foreach ($questions as $index => $q) {
            $qid = $q->id;
            $number = $index + 1; // Question number
            $fieldname = "rating[{$memberid}][{$qid}]"; 
            $label = $number . '. ' . format_string($q->text); // prepend number           

            $mform->addElement('select', $fieldname, $label, $options);
            $mform->addRule($fieldname, null, 'required', null, 'client');
            $mform->setDefault($fieldname, '');

        }

        // Self comment box
        $commentname = "comment[{$memberid}]";
        $mform->addElement('textarea', $commentname,
            "Your self-feedback", [
            'rows' => 10,
            'cols' => 50,
            'placeholder' => 'Enter your self comments here'
        ]);
        $mform->addRule($commentname, null, 'required', null, 'client');

        // Another self comment box

        $mform->addElement('textarea', 'selfreflect', "Self Reflection", [
            'rows' => 10,
            'cols' => 50,
            'placeholder' => 'Write your personal reflection on what skills'.
                            'and knowledge do you now know you need to develop '. 
                            'for your future work in the IT industry and/or what '.
                            'issues of your own working style do you need to address?'            
        ]);
        $mform->addRule('selfreflect', null, 'required', null, 'client');

        // Peer evaluation section
        foreach ($group['members'] as $member) {
            
            $memberid = $member->id;
            $fullname = $member->fullname;

            // Section header for this member
            $mform->addElement('header', 'member_'.$memberid, $fullname);

            foreach ($questions as $index => $q) {
                $qid = $q->id;
                $number = $index + 1; // Question number
                $fieldname = "rating[{$memberid}][{$qid}]";
                $label = $number . '. ' . format_string($q->text);

                $mform->addElement('select', $fieldname, $label, $options);
                $mform->addRule($fieldname, null, 'required', null, 'client');
                $mform->setDefault($fieldname, '');
            }

            // Add comment box
            $commentname = "comment[{$memberid}]";
            $mform->addElement('textarea', $commentname, 'Comments',
            [
                'rows' => 10,
                'cols' => 50,
                'placeholder' => 'Below add a brief description of how you believe ' . $fullname . ' contributed to the project process over the whole semester.'
            ]);
            
            $mform->addRule($commentname, null, 'required', null, 'client');
        }

        $buttons = [];
        $buttons[] =& $mform->createElement('submit', 'savedraft', 'Save draft');
        $buttons[] =& $mform->createElement('submit', 'submitbutton', 'Submit Evaluation');
        $buttons[] =& $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', ' ', false);
        $mform->disabledIf('savedraft', 'submitbutton');
        
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Custom validation can be added here if needed
        return $errors;
    }
}





