<?php
/**
 * Handle giving or taking XP from a user.
 *
 * @package    block_gamification
 * 
 **/
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('block/gamification:givexp', $context);

$action = required_param('action', PARAM_TEXT);   // "Give XP" or "Take XP"
$userid = required_param('userid', PARAM_INT);   // User to modify
$points = required_param('points', PARAM_INT);   // XP amount

$manager = new \block_gamification\leaderboard_manager();

// ✅ Validate input
if ($userid <= 0) {
    print_error('nouserselected', 'block_gamification');
}
if ($points <= 0) {
    print_error('invalidpoints', 'block_gamification');
}

// ✅ Handle Give XP
if ($action === get_string('givexp', 'block_gamification')) {
    $manager->add_xp($userid, $points);

    redirect(
        new moodle_url('/my'),
        get_string('xpadded', 'block_gamification', $points),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

// ✅ Handle Take XP
} else if ($action === get_string('takexp', 'block_gamification')) {
    $current = $manager->get_user_xp($userid);

    if ($current <= 0) {
        print_error('cannotremovexp', 'block_gamification');
    }

    $manager->add_xp($userid, -$points);

    redirect(
        new moodle_url('/my'),
        get_string('xptaken', 'block_gamification', $points),
        null,
        \core\output\notification::NOTIFY_WARNING
    );

// ❌ Invalid action
} else {
    print_error('invalidaction', 'block_gamification');
}
