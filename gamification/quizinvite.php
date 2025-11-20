<?php
// This file is part of the Gamification block for Moodle

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/moodlelib.php');

global $DB, $USER, $PAGE, $OUTPUT, $SITE;

// Require login and admin capabilities
require_login();
$context = context_system::instance();
require_capability('block/gamification:givexp', $context);

// Get parameters
$quizid = required_param('quizid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_RAW);

// Validate session key
if (!confirm_sesskey($sesskey)) {
    throw new moodle_exception('invalidsesskey', 'error');
}

// Get quiz details
$quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);

// Get enrolled users count for verification
$coursecontext = context_course::instance($course->id);
$enrolledusers = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0, 'u.id', null, 0, 0, true);
$totalusers = count($enrolledusers);

if (empty($enrolledusers)) {
    redirect(
        new moodle_url('/my'),
        get_string('noenrolledusers', 'block_gamification'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Create and queue adhoc task
$task = new \block_gamification\task\send_quiz_invites_task();
$task->set_custom_data([
    'quizid' => $quizid,
    'sentby' => $USER->id,
    'batchsize' => 25
]);

// Add some delay to prevent immediate execution (optional)
$task->set_next_run_time(time() + 10);

\core\task\manager::queue_adhoc_task($task);

// Log that we've queued the task
\block_gamification\logger::log_quiz_invite_queued($quiz, $course, $USER->id, $totalusers);

// Immediate redirect with success message
redirect(
    new moodle_url('/my'),
    get_string('invitequeued', 'block_gamification', $totalusers),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);