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
 * Clean draft submissions scheduled task for SmartSPE module.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_smartspe\task;

defined('MOODLE_INTERNAL') || die();

class clean_draft_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('clean_draft_task', 'mod_smartspe');
    }

    public function execute(): void {
        global $DB;

        $MAX_AGE = 30 * 24 * 60 * 60; // 30 days in seconds.
        mtrace("=== Starting SmartSPE Clean Draft Task ===");

        $thresholdtime = time() - $MAX_AGE;
       
        $count = $DB->count_records_select('smartspe_draft', 'saved_at < ?', [$thresholdtime]);
        $DB->delete_records_select('smartspe_draft', 'saved_at < ?', [$thresholdtime]);
        mtrace("Deleted $count old draft(s) from smartspe_draft table.");

        mtrace("=== SmartSPE Clean Draft Task Completed ===");



       
    }
}
