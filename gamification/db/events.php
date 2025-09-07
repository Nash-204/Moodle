<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => '\block_gamification\observer::quiz_attempt_submitted',
        'includefile' => '/blocks/xpleaderboard/classes/observer.php',
        'priority'    => 1000,
        'internal'    => false,
    ],
];
