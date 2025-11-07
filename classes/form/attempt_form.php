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
            // $mform->setDefault($fieldname, '');

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
                // $mform->setDefault($fieldname, '');
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
        $mform->addElement('static', 'lastsaved', '', 
            '<span id="draft-last-saved">No draft saved yet</span>');
        $buttons[] =& $mform->createElement('submit', 'submitbutton', 'Submit Evaluation');
        $buttons[] =& $mform->createElement('cancel');
        $mform->addGroup($buttons, 'actionbuttons', '', ' ', false);
        
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $customdata = $this->_customdata;
        // Custom validation can be added here if needed

        $rating = $data['rating'] ?? [];
        if (empty($rating) || !is_array($rating)) {
            $errors['rating'] = get_string('error_missingrating', 'mod_smartspe');
            return $errors;
        }
        // Check if there is a rating for each member and each question
        $questions = $customdata['questions'];
        $group = $customdata['group'];
        $members = $group['members'];

        $members[] = (object)[
            'id' => $customdata['userid'],
            'fullname' => 'Self'
        ];

        foreach ($members as $member) {
            $memberid = $member->id;
            if (!isset($rating[$memberid]) || !is_array($rating[$memberid])) {
                $errors['rating'] = get_string('error_missingrating', 'mod_smartspe',
                                            s($member->fullname));
                return $errors;
            }
            foreach ($questions as $q) {
                $qid = $q->id;
                $value = $rating[$memberid][$qid] ?? null;
                if (!isset($value) || $value === '') {
                    $errors['rating'] = get_string('error_missingrating', 'mod_smartspe',
                                            s($member->fullname));
                    return $errors;
                }
                if ($value < 1 || $value > 5) {
                    $errors['rating'] = get_string('error_invalidrating', 'mod_smartspe',
                                     s($value));
                    return $errors;
                }
                $comment = $data['comment'][$memberid] ?? '';
                if (trim($comment) === '') {
                    $errors['comment'] = get_string('error_missingcomment', 'mod_smartspe',
                                         s($member->fullname));
                    return $errors;
                }
                // Make sure words count > 100
                $wordcount = str_word_count(strip_tags($comment));
                if ($wordcount < 100) {
                    $errors['comment'] = get_string('error_commentwordcount', 'mod_smartspe',
                                         s($member->fullname));
                    return $errors;
                }
            } 
        }     

        // Validate if the questionsids exist in the fixture

        $questions = $this->_customdata['questions'] ?? [];
        $questionids = array_map(fn($q) => $q->id, $questions);
        foreach ($rating as $memberid => $ratingsformember) {
            foreach ($ratingsformember as $qid => $value) {
                if (!in_array($qid, $questionids)) {
                    $errors['rating'] = 
                        get_string('error_invalidrating', 'mod_smartspe', s($qid));
                    return $errors;
                }
            }
        }

        // Validate if the memberids exist in the group or is the user themselves
        $group = $this->_customdata['group'] ?? [];
        $memberids = array_map(fn($m) => $m->id, $group['members'] ?? []);
        $memberids[] = $this->_customdata['userid']; // include self

        foreach ($rating as $memberid => $ratingsformember) {
            if (!in_array($memberid, $memberids)) {
                $errors['rating'] = get_string('error_invalidmemberid', 'mod_smartspe',
                                    s($memberid));
                return $errors;
            }
        } 

        $selfreflect = $data['selfreflect'] ?? '';
        if (trim($selfreflect) === '') {
            $errors['selfreflect'] = get_string('error_selfreflection', 'mod_smartspe');
            return $errors;
        }       

        return $errors;
    }
}





