<?php
$capabilities = [
    'block/gamification:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager' => CAP_ALLOW]
    ],
    'block/gamification:myaddinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['user' => CAP_ALLOW]
    ]
];
