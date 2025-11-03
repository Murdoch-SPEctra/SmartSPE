<?php

// avoid direct access
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * Test ID: S01
 * Scenario: Valid submission (no missing ratings or comments)
 * Expected result: No validation errors
 */

return [
    'fixture' => [
        'userid'    => 3,
        'displaystudentid' => '35011901',
        'cmid'      => 69,
        'speid'     => 49,
        'spe_name'  => 'Tessadsadada',
        'group'     => [
            'id'      => 48,
            'name'    => 'SPEctra',
            'members' => [
                (object)[
                    'id' => 5,
                    'fullname' => 'Tercia Fernandes',
                    'email' => '35011983@student.murdoch.edu.au'
                ],
            ]
        ],
        'questions' => [
            (object)[
                'id' => 112,
                'spe_id' => 49,
                'text' => 'sdadadsad',
                'sort_order' => 1,
                'score' => null
            ]
        ]
    ],
    'formdata' => [
        'rating' => [
            3 => [        // Member ID
                112 => 5 // Question ID => Rating
            ],
            5 => [
                112 => 3
            ]
        ],
    'comment' => [
        3 => 'sdadsadsad',   // Member ID => Comment
        5 => 'asdasdasdasdasda',
    ],
    'selfreflect' => 'asdadadada',
    'submitbutton' => 'Submit Evaluation'
    ]

];

