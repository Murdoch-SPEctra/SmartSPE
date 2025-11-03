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
 * Generates the excel spreadsheet for a SmartSPE activity.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/report/exporter.php');
use ZipStream\ZipStream;
use mod_smartspe\report\exporter;

global $DB, $OUTPUT, $PAGE, $USER;

$id = required_param('id', PARAM_INT);
$teamid = optional_param('teamid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);


$speid = $cm->instance;
$context = context_module::instance($cm->id);

require_capability('mod/smartspe:viewresults', $context);

$smartspe = $DB->get_record('smartspe', ['id' => $speid], '*', MUST_EXIST);

if (time() < $smartspe->end_date) 
        throw new moodle_exception('spe_notended', 'mod_smartspe');

if(!$teamid) {
     // Fetch all teams for this SPE
    $groups = $DB->get_records('smartspe_group', ['spe_id' => $speid]);
    if (empty($groups)) {
        throw new moodle_exception('nogroupsfound', 'mod_smartspe');
    }

    // Create a temporary file for the ZIP
    $zipfilename = 'smartspe_reports_' . $speid . '.zip';
    $zippath = make_temp_directory('mod_smartspe') . '/' . $zipfilename;

    $zip = new ZipArchive();
    if ($zip->open($zippath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new moodle_exception('cannotcreatezip', 'mod_smartspe');
    }

    foreach ($groups as $group) {
        // Generate CSV data
        $csvdata =  exporter::generate_csv($speid, $group->id);

        // Build CSV content
        $csvcontent = '';
        $fh = fopen('php://temp', 'r+');
        foreach ($csvdata as $row) {
            fputcsv($fh, $row, ",", "\"", "\\");
        }
        rewind($fh);
        $csvcontent = stream_get_contents($fh);
        fclose($fh);

        // Add to zip
        // Replace non-alphanumeric characters in group name for filename
        $filename = 'team_' . preg_replace('/\W+/', '_', $group->name) . '.csv';
        $zip->addFromString($filename, $csvcontent);
    }

    $zip->close();

    // Send ZIP to browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipfilename . '"');
    header('Content-Length: ' . filesize($zippath));
    readfile($zippath);

    unlink($zippath);
}
else{
    
    $group = $DB->get_record('smartspe_group', ['id' => $teamid, 'spe_id' => $speid], '*', MUST_EXIST);


    $csvdata = exporter::generate_csv($speid, $teamid);

    $filename = 'smartspe_team_' . $group->name . '_report.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache'); 
    header('Expires: 0');
    // We want to prevent caching of the CSV file for NOW but dont mind 
    // caching cuz the data doesnt really change after end_date .

    // ==== Output CSV data ====
    $output = fopen('php://output', 'w');
    foreach ($csvdata as $row) {
        fputcsv($output, $row, ",", "\"", "\\");
    }
    fclose($output);
}


exit;