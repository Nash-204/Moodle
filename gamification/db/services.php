<?php
$functions = [
    'block_gamification_get_user_badges' => [
        'classname'   => 'block_gamification_external',
        'methodname'  => 'get_user_badges_by_email',
        'classpath'   => 'blocks/gamification/externallib.php',
        'description' => 'Get badges earned by user identified by email',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => false,
    ],
];

$services = [
    'Gamification Badges Service' => [
        'functions' => ['block_gamification_get_user_badges'],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'gamification_badges_service',
    ],
];