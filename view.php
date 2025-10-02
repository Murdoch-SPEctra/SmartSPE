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
 * Main view for the SmartSPE activity (UI demo only – static data).
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.

// Standard Moodle activity bootstrap (minimal for UI demo).
if ($id) {
    $cm         = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
    $course     = get_course($cm->course);
    $moduleinstance = (object)['name' => get_string('pluginname', 'mod_smartspe')]; // Placeholder.
} else {
    print_error('missingparameter');
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/smartspe/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Static demo data – replace later with real DB backed structures.
$scenarios = [
    [
        'id' => 1,
        'title' => 'Patient onboarding interview',
        'tags' => ['communication', 'ethics'],
        'difficulty' => 'Beginner',
        'status' => 'Draft',
        'summary' => 'Introduce yourself to a virtual patient and gather background information.',
        'avatarurl' => $OUTPUT->image_url('u/f1')->out(),
        'updated' => userdate(time() - 3600)
    ],
    [
        'id' => 2,
        'title' => 'Conflict resolution (team)',
        'tags' => ['teamwork', 'leadership'],
        'difficulty' => 'Intermediate',
        'status' => 'Published',
        'summary' => 'Mediate a disagreement between two colleagues in a simulated breakout.',
        'avatarurl' => $OUTPUT->image_url('u/f2')->out(),
        'updated' => userdate(time() - 7200)
    ],
    [
        'id' => 3,
        'title' => 'Crisis communication briefing',
        'tags' => ['communication', 'pressure'],
        'difficulty' => 'Advanced',
        'status' => 'In review',
        'summary' => 'Deliver concise instructions during a high-pressure simulated event.',
        'avatarurl' => $OUTPUT->image_url('u/f3')->out(),
        'updated' => userdate(time() - 14200)
    ],
];

$templatecontext = [
    'activityname' => format_string($moduleinstance->name),
    'introhtml' => html_writer::div(get_string('uiplaceholderintro', 'mod_smartspe'), 'smartspe-intro alert alert-info'),
    'showsidepanel' => true,
    'filters' => [
        ['name' => 'All', 'active' => true],
        ['name' => 'Draft'],
        ['name' => 'Published'],
        ['name' => 'In review'],
    ],
    'scenarios' => array_map(function($s) { return $s; }, $scenarios),
    'has_scenarios' => !empty($scenarios),
    'emptymessage' => get_string('emptyscenarios', 'mod_smartspe'),
    'showcreatebutton' => true,
    'createbuttonlabel' => get_string('createScenario', 'mod_smartspe'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_smartspe/main', $templatecontext);
echo $OUTPUT->footer();
