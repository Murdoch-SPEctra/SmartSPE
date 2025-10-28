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
    $apiserver = get_config('mod_smartspe', 'apiserver');
    $url = rtrim($apiserver, '/') . '/getsentiment';
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



function smartspe_get_random_comment(): string {
    $comments = [
         "Very reliable teammate.",
        "Needs improvement on deadlines.",
        "Great communication and effort.",
        "Strong technical skills, helpful to the group.",
        "Could participate more actively.",
        "Z excelled as the team lead, effectively coordinating tasks and ensuring smooth collaboration among all members. She created well-structured and interactive visuals and documentation, which greatly improved the quality of our deliverables.
            She also worked closely with M to review and fix workflows when issues arose, ensuring that the system functioned properly, spending a great amount of time troubleshooting. Z took the lead in presenting our project during client meetings and weekly discussions, and she was always willing to help troubleshoot any problems the team encountered, providing support that kept the project moving forward.",
        
        "Showed very good project management skills as she was the project activity coordinator.
        Regularly took updates about the various tasks of the project, including the development side and the documentation part.
        Exhibited excellent communication skills throughout and ensured to keep the project team members motivated and on track.
        Involved in the testing phase.
        Submitted all documents on time with proper proofreading and formatting.",

        "N was the Secretary and Librarian. As the Secretary, she was responsible for creating meeting agendas before meetings and meeting minutes after the meetings with the supervisor and client. And she also managed a repository (Google Groups) for the meeting agendas and meeting minutes. As the Librarian, she was responsible for maintaining a standardised file naming standard, a repository for all project documentation. For the technical side of the project, she was responsible for creating the dashboard for the SOC/admin and developing the database for storing the output given from the scraper tool.",
    
        "T contributed meaningfully to both the technical development and documentation areas of the project. He wrote detailed sections of the PMP and supported the technical team when needed, demonstrating versatility and a solid understanding of both areas. Although he had commitments in other units that occasionally limited his availability, he consistently demonstrated a strong work ethic and remained reliable in completing his tasks.",
    ];

    return $comments[array_rand($comments)];
}