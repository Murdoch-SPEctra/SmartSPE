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
 * Contains helper function to get sentiment analysis.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function smartspe_get_sentiment($commenttext) {
    global $CFG;

    $url = 'http://localhost:5000/getsentiment'; // hardcoded for now
    $payload = json_encode(['comment' => $commenttext]);
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 2, // seconds (don’t hang forever)
        ],
    ];

    try {
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('HTTP request failed');
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON from sentiment API');
        }
        debugging ("Sentiment API response: " . print_r($result, true), DEBUG_DEVELOPER);

        return $result['sentiment'] ?? null;

    } catch (Throwable $e) {
        // Only log in developer/debug mode.
        debugging("Sentiment API error: " . $e->getMessage(), DEBUG_DEVELOPER);
        return null;
    }
}
