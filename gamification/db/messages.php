<?php
defined('MOODLE_INTERNAL') || die();

// Define message providers for notifications
$messageproviders = [
    'xpnotification' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED, 
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    'badgenotification' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED, 
            'email' => MESSAGE_PERMITTED,
        ],
    ],
];
