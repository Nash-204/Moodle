<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/blocks/gamification/lib.php');
require_once($CFG->dirroot . '/blocks/gamification/quiz_invite_form.php');

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

// Set up page
$PAGE->set_url(new moodle_url('/blocks/gamification/quizinvite.php', ['quizid' => $quizid, 'sesskey' => $sesskey]));
$PAGE->set_title(get_string('sendquizinvites', 'block_gamification'));
$PAGE->set_heading(get_string('sendquizinvites', 'block_gamification'));
$PAGE->navbar->add(get_string('sendquizinvites', 'block_gamification'));

// Create form
$form = new quiz_invite_form(null, ['quizid' => $quizid, 'courseid' => $course->id]);

if ($form->is_cancelled()) {
    // Redirect back to dashboard if cancelled
    redirect(new moodle_url('/my'));
} else if ($formdata = $form->get_data()) {
    // Form submitted - process the invitation
    
    // Build email content
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
    $subject = get_string('quizinvitesubject', 'block_gamification', format_string($quiz->name));
    list($messagetext, $messagehtml) = quizinvite_build_email($quiz, $course, $cm, $USER, $SITE, $CFG);

    // Queue emails based on recipient selection
    $batch_count = block_gamification_queue_quiz_emails_custom($quiz, $course, $USER, $subject, $messagetext, $messagehtml, $formdata);

    // Log that emails were queued
    \block_gamification\event\quiz_emails_queued::create([
        'context' => context_course::instance($course->id),
        'objectid' => $quiz->id,
        'other' => [
            'batches_created' => $batch_count,
            'subject' => $subject,
            'quizname' => $quiz->name,
            'recipient_type' => $formdata->recipient_type
        ]
    ])->trigger();

    // Prepare success message and redirect
    if ($batch_count > 0) {
        $message = get_string('invitequeuedsuccess', 'block_gamification', $batch_count);
        $type = \core\output\notification::NOTIFY_SUCCESS;
    } else {
        $message = get_string('noeligibleusers', 'block_gamification');
        $type = \core\output\notification::NOTIFY_ERROR;
    }

    redirect(new moodle_url('/my'), $message, null, $type);
}

// Display the form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sendquizinvites', 'block_gamification'));

// Display quiz info
echo $OUTPUT->box_start('generalbox quizinfo');
echo html_writer::tag('h3', format_string($quiz->name));
echo html_writer::tag('p', get_string('course') . ': ' . format_string($course->fullname));
echo $OUTPUT->box_end();

$form->display();

echo $OUTPUT->footer();

/**
 * Build quiz invitation email content
 */
function quizinvite_build_email($quiz, $course, $cm, $fromuser, $SITE, $CFG) {
    $viewurl = new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
    
    $quizname = format_string($quiz->name);
    $coursename = format_string($course->fullname);
    $fromname = fullname($fromuser);
    
    // Quiz time limits and availability
    $timelimit = '';
    if ($quiz->timelimit > 0) {
        $timelimit = format_time($quiz->timelimit);
    }
    
    $attempts = '';
    if ($quiz->attempts > 0) {
        $attempts = $quiz->attempts;
    } else {
        $attempts = get_string('unlimited');
    }

    $primary = '#34459c';
    $accent = '#f29031';

    // HTML email content
    $messagehtml = '
<div style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08); font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif;">
    <!-- HEADER -->
    <div style="background:' . $primary . '; color:#ffffff; padding:28px 36px; text-align:center;">
        <h1 style="margin:10px 0 0 0; font-size:24px; font-weight:600;">Quiz Invitation 
            <span style="background:#10b981; color:white; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-left:8px;">ONLINE QUIZ</span>
        </h1>
        <p style="margin:6px 0 0 0; font-size:14px; opacity:0.85;">From ' . format_string($SITE->fullname) . '</p>
    </div>

    <!-- QUIZ INFO -->
    <div style="padding:36px 30px 10px 30px;">
        <h2 style="margin:0 0 8px 0; font-size:22px; color:' . $primary . '; font-weight:600;">' . $quizname . '</h2>
        <p style="margin:0 0 24px 0; font-size:15px; color:#4a4a4a;">
            <strong>Course:</strong> ' . $coursename . '
        </p>

        <div style="margin-bottom:24px;">
            <div style="margin-bottom:8px;">
                <span style="color:#6b7280; font-size:14px; display:inline-block; width:130px;"><strong>Quiz Name:</strong></span>
                <span style="font-size:15px; color:#1f2937;">' . $quizname . '</span>
            </div>' . 
            ($timelimit ? '
            <div style="margin-bottom:8px;">
                <span style="color:#6b7280; font-size:14px; display:inline-block; width:130px;"><strong>Time Limit:</strong></span>
                <span style="font-size:15px; color:#1f2937;">' . $timelimit . '</span>
            </div>' : '') . '
            <div style="margin-bottom:8px;">
                <span style="color:#6b7280; font-size:14px; display:inline-block; width:130px;"><strong>Attempts Allowed:</strong></span>
                <span style="font-size:15px; color:#1f2937;">' . $attempts . '</span>
            </div>
            <div style="margin-bottom:8px;">
                <span style="color:#6b7280; font-size:14px; display:inline-block; width:130px;"><strong>Invited by:</strong></span>
                <span style="font-size:15px; color:#1f2937;">' . $fromname . '</span>
            </div>
        </div>

        <hr style="border:none; border-top:1px solid #e5e7eb; margin:24px 0;">

        <!-- BUTTON -->
        <div style="text-align:center; margin-bottom:30px;">
            <a href="' . $viewurl->out(false) . '" 
               style="background:' . $primary . '; color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:8px;
                      font-size:15px; font-weight:600; display:inline-block;">
                Attempt Quiz Now
            </a>
        </div>
    </div>

    <!-- REMINDER SECTION -->
    <div style="background:#f9fafb; padding:24px 30px;">
        <h3 style="margin:0 0 12px 0; font-size:16px; color:' . $accent . '; font-weight:600;">Important Quiz Notes</h3>
        <ul style="margin:0; padding-left:20px; color:#374151; font-size:14px; line-height:1.6;">
            <li>This is an <strong>online quiz</strong> available through ' . format_string($SITE->fullname) . '</li>
            <li>Click <strong>"Attempt Quiz Now"</strong> to start your quiz attempt</li>' . 
            ($timelimit ? '<li>This quiz has a time limit of <strong>' . $timelimit . '</strong></li>' : '') . '
            <li>You are allowed <strong>' . $attempts . ' attempt(s)</strong> for this quiz</li>
            <li>Ensure you have a stable internet connection before starting</li>
            <li>Complete the quiz in one sitting once started</li>
        </ul>
    </div>

    <!-- FOOTER -->
    <div style="background:#f3f4f6; text-align:center; padding:20px 30px;">
        <p style="font-size:12px; color:#6b7280; margin:0;">
            This is an automated message from ' . format_string($SITE->fullname) . '.<br>
            <a href="' . $CFG->wwwroot . '" style="color:' . $primary . '; text-decoration:none;">Visit Moodle</a> • 
            <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" style="color:' . $primary . '; text-decoration:none;">View Course</a>
        </p>
    </div>
</div>';

    // Plain text version of the email
    $messagetext = "Quiz Invitation - ONLINE QUIZ\n" .
        "==========================================\n" .
        "Quiz: $quizname\nCourse: $coursename\n\n" .
        "Quiz Name: $quizname\n" .
        ($timelimit ? "Time Limit: $timelimit\n" : "") .
        "Attempts Allowed: $attempts\n" .
        "Invited by: $fromname\n\n" .
        "Attempt Quiz: " . $viewurl->out(false) . "\n\n" .
        "Important Quiz Notes:\n" .
        "- This is an online quiz available through " . format_string($SITE->fullname) . "\n" .
        "- Click \"Attempt Quiz Now\" to start your quiz attempt\n" .
        ($timelimit ? "- This quiz has a time limit of $timelimit\n" : "") .
        "- You are allowed $attempts attempt(s) for this quiz\n" .
        "- Ensure you have a stable internet connection before starting\n" .
        "- Complete the quiz in one sitting once started\n\n" .
        "This is an automated message from " . format_string($SITE->fullname) . ".";

    return [$messagetext, $messagehtml];
}