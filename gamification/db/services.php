<?php
$functions = [
    'block_gamification_get_user_badges' => [
        'classname'   => 'block_gamification_external_badges',
        'methodname'  => 'get_user_badges_by_email',
        'classpath'   => 'blocks/gamification/classes/external/badges_external.php',
        'description' => 'Get badges earned by user identified by email',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => false,
    ],
    'block_gamification_get_user_course_info' => [
        'classname'   => 'block_gamification_external_courses',
        'methodname'  => 'get_user_course_info_by_email',
        'classpath'   => 'blocks/gamification/classes/external/courses_external.php',
        'description' => 'Get courses, activity scores, and attendance for a user by email',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => false,
    ],
];

$services = [
    'Gamification Badges Service' => [
        'functions' => [
            'block_gamification_get_user_badges'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'gamification_badges_service',
    ],
    'Gamification Courses Service' => [
        'functions' => [
            'block_gamification_get_user_course_info'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'gamification_courses_service',
    ],
];