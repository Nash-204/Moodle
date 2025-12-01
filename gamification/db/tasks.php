<?php

defined('MOODLE_INTERNAL') || die();

// Define the tasks to be run periodically.
$tasks = [
    [
        'classname' => 'block_gamification\task\weekly_badges_task',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '*',
        'dayofweek' => '5',      // Friday (0=Sunday, 5=Friday, 6=Saturday)
        'month'     => '*',
        'disabled'  => 0         
    ],
    [
        'classname' => 'block_gamification\task\monthly_badges_task',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '1',      // 1st of month
        'dayofweek' => '*',
        'month'     => '*',
        'disabled'  => 0
    ],
    [
        'classname' => 'block_gamification\task\annual_badges_task',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '1',      // 1st of January
        'dayofweek' => '*',
        'month'     => '1',      // January
        'disabled'  => 0
    ],
    [
        'classname' => 'block_gamification\task\process_quiz_email_queue',
        'blocking' => 0,
        'minute' => '*/2',    // Every 2 minutes
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],
];