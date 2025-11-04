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
 * Save draft submission for the SmartSPE activity.
 *
 * @package     mod_smartspe
 * @copyright   2025 SPEctra
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

require_login();

$PAGE->set_context(context_system::instance());

require_sesskey();

$userid = $USER->id;
$speid = required_param('speid', PARAM_INT);
$datajson = required_param('data', PARAM_RAW);

try {
    $record = $DB->get_record('smartspe_draft', ['spe_id' => $speid, 'student_id' => $userid]);
    $now = time();

    if ($record) {
        $record->data = $datajson;
        $record->saved_at = $now;
        $DB->update_record('smartspe_draft', $record);
    } else {
        $record = (object)[
            'spe_id' => $speid,
            'student_id' => $userid,
            'data' => $datajson,
            'saved_at' => $now
        ];
        $DB->insert_record('smartspe_draft', $record);
    }

    echo json_encode(['status' => 'ok']);
} catch (\Throwable $th) {
    echo json_encode(['status' => 'error', 'message' => $th->getMessage()]);
}
die;

