<?php
/**
 * Handle giving or taking XP, or saving quiz/course categories.
 *
 * @package    block_gamification
 */
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('block/gamification:givexp', $context);

$action = required_param('action', PARAM_TEXT);

$quizid      = optional_param('quizid', 0, PARAM_INT);
$courseid    = optional_param('courseid', 0, PARAM_INT);
$quizdiff    = optional_param('quizdiff', 'Easy', PARAM_TEXT);
$courselevel = optional_param('courselevel', 'Beginner', PARAM_TEXT);

$manager = new \block_gamification\leaderboard_manager();

/**
 * === Handle Send Quiz Invite ===
 */
if ($action === get_string('sendquizinvite', 'block_gamification')) {
    if (!$quizid) {
        redirect(
            new moodle_url('/my'),
            get_string('val_quiz_email', 'block_gamification'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    
    redirect(new moodle_url('/blocks/gamification/quizinvite.php', [
        'quizid' => $quizid,
        'sesskey' => sesskey()
    ]));
}

/**
 * === Handle Save Quiz Category only ===
 */
if ($action === get_string('savequizcategory', 'block_gamification')) {
    if ($quizid) {
        $record = $DB->get_record('block_gamif_quizdiff', ['quizid' => $quizid]);
        if ($record) {
            $record->difficulty = $quizdiff ?: 'Easy';
            $DB->update_record('block_gamif_quizdiff', $record);
        } else {
            $DB->insert_record('block_gamif_quizdiff', [
                'quizid'     => $quizid,
                'difficulty' => $quizdiff ?: 'Easy'
            ]);
        }
    }

    redirect(
        new moodle_url('/my'),
        get_string('quizcategorysaved', 'block_gamification'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

/**
 * === Handle Save Course Category ===
 */
if ($action === get_string('savecoursecategory', 'block_gamification')) {
    if ($courseid) {
        $record = $DB->get_record('block_gamif_coursediff', ['courseid' => $courseid]);
        if ($record) {
            $record->level = $courselevel ?: 'Beginner';
            $DB->update_record('block_gamif_coursediff', $record);
        } else {
            $DB->insert_record('block_gamif_coursediff', [
                'courseid' => $courseid,
                'level'    => $courselevel ?: 'Beginner'
            ]);
        }
    }

    redirect(
        new moodle_url('/my'),
        get_string('coursecategorysaved', 'block_gamification'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

/**
 * === XP actions (require userid + points) ===
 */
$userid = required_param('userid', PARAM_INT);
$points = required_param('points', PARAM_INT);

// Prevent giving XP to guest user.
if (isguestuser($userid)) {
    throw new moodle_exception('cannotassignguest', 'block_gamification');
}

// Validate input
if ($userid <= 0) {
    print_error('nouserselected', 'block_gamification');
}
if ($points <= 0) {
    print_error('invalidpoints', 'block_gamification');
}

/**
 * Difficulty multipliers
 */
$quizmultipliers = [
    'Easy'   => 1.0,
    'Medium' => 1.5,
    'Hard'   => 2.0,
];
$coursemultipliers = [
    'Beginner'     => 1.0,
    'Intermediate' => 1.5,
    'Advance'      => 2.0,
];

/**
 * Apply multipliers
 */
$multiplier = 1.0;

// Adjust by quiz difficulty
if ($quizid) {
    $difficulty = $DB->get_field('block_gamif_quizdiff', 'difficulty', ['quizid' => $quizid]) ?? 'Easy';
    if (isset($quizmultipliers[$difficulty])) {
        $multiplier *= $quizmultipliers[$difficulty];
    }
}

// Adjust by course level
if ($courseid) {
    $level = $DB->get_field('block_gamif_coursediff', 'level', ['courseid' => $courseid]) ?? 'Beginner';
    if (isset($coursemultipliers[$level])) {
        $multiplier *= $coursemultipliers[$level];
    }
}

$finalpoints = (int) round($points * $multiplier);

/**
 * === Handle Give XP ===
 */
if ($action === get_string('givexp', 'block_gamification')) {
    $manager->add_xp($userid, $finalpoints);

    notify_xp_change($userid, $finalpoints, 'given', $action);
    \block_gamification\leaderboard_manager::check_realtime_badges($userid);

    redirect(
        new moodle_url('/my'),
        get_string('xpgiven', 'block_gamification', $finalpoints),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

/**
 * === Handle Take XP ===
 */
} else if ($action === get_string('takexp', 'block_gamification')) {
    $current = $manager->get_user_xp($userid);

    if ($current <= 0) {
        redirect(
            new moodle_url('/my'),
            get_string('cannotremovexp', 'block_gamification'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $manager->add_xp($userid, -$finalpoints);

    notify_xp_change($userid, $finalpoints, 'taken', $action);
    \block_gamification\leaderboard_manager::check_realtime_badges($userid);

    redirect(
        new moodle_url('/my'),
        get_string('xptaken', 'block_gamification', $finalpoints),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

/**
 * === Invalid action ===
 */
} else {
    print_error('invalidaction', 'block_gamification');
}

/**
 * Send notifications for XP changes made by admin
 */
function notify_xp_change(int $userid, int $points, string $type, string $action): void {
    global $USER;

    if (isguestuser($userid) || $userid <= 0 || $points <= 0) {
        return;
    }

    $adminname = fullname($USER);
    $pointsabs = abs($points);

    if ($type === 'given') {
        $smallmessage = get_string('xpgivensmall', 'block_gamification', $pointsabs);
        $subject      = get_string('xpgivensubject', 'block_gamification');
        $fullmessage  = get_string('xpgivenfull', 'block_gamification', [
            'points' => $pointsabs,
            'admin'  => $adminname
        ]);
        $emoji = "🎉";
        $verb  = "added";
    } else {
        $smallmessage = get_string('xptakensmall', 'block_gamification', $pointsabs);
        $subject      = get_string('xptakensubject', 'block_gamification');
        $fullmessage  = get_string('xptakenfull', 'block_gamification', [
            'points' => $pointsabs,
            'admin'  => $adminname
        ]);
        $emoji = "⚠️";
        $verb  = "removed";
    }

    $eventdata = new \core\message\message();
    $eventdata->component         = 'block_gamification';
    $eventdata->name              = 'xpnotification';
    $eventdata->userfrom          = \core_user::get_noreply_user();
    $eventdata->userto            = $userid;
    $eventdata->subject           = $subject;
    $eventdata->fullmessage       = $fullmessage;
    $eventdata->fullmessageformat = FORMAT_MARKDOWN;
    $eventdata->fullmessagehtml   = "<p>{$emoji} Admin <strong>{$adminname}</strong> {$verb} <strong>{$pointsabs} XP</strong> from your account.</p>";
    $eventdata->smallmessage      = $smallmessage;
    $eventdata->notification      = 1;
    $eventdata->contexturl        = (new \moodle_url('/my'))->out(false);
    $eventdata->contexturlname    = get_string('myhome');
    message_send($eventdata);

    set_user_preference('block_gamification_toast', $smallmessage, $userid);
}