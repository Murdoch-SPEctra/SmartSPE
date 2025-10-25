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

function smartspe_get_sentiment_batch($comments) {
    global $CFG;


    // Prepare payload: numeric keys => comment text
    $payloadData = [];
    foreach ($comments as $key => $comment) {
        $payloadData[$key] = $comment;
    }

    $url = 'http://localhost:5000/getsentiment';
    $payload = json_encode($payloadData);

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 2,
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

        // $result should be: 0 => sentiment, 1 => sentiment, etc.
        return $result;

    } catch (\Throwable $e) {
        debugging("Sentiment API error: " . $e->getMessage(), DEBUG_DEVELOPER);
        mtrace( "Sentiment API error: " . $e->getMessage() );
        return [];
    }
}

function smartspe_get_studentid_from_email(string $email): string {
    $pos = strpos($email, '@');
    if ($pos === false) {
        // Email doesn’t have the expected pattern.
        return 'Unknown ID';
    }
    return substr($email, 0, $pos);
}