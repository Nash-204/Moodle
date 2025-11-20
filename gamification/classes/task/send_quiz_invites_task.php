<?php
/**
 * Adhoc task for sending quiz invitations in background
 *
 * @package    block_gamification
 */

namespace block_gamification\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task for sending quiz invitations in background
 */
class send_quiz_invites_task extends \core\task\adhoc_task {

    /**
     * Execute the task
     */
    public function execute() {
        global $CFG, $DB, $SITE;

        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $data = $this->get_custom_data();
        
        $quizid = $data->quizid;
        $sentby = $data->sentby;
        $batchsize = $data->batchsize ?? 25;

        // Get quiz details
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);

        // Get all enrolled users in the course
        $coursecontext = \context_course::instance($course->id);
        $enrolledusers = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0, 'u.*', null, 0, 0, true);

        if (empty($enrolledusers)) {
            $this->send_completion_notification($sentby, $quiz, 0, 0, []);
            return;
        }

        // Build email content once
        $fromuser = $DB->get_record('user', ['id' => $sentby]);
        $subject = get_string('quizinvitesubject', 'block_gamification', format_string($quiz->name));
        list($messagetext, $messagehtml) = $this->build_email_content($quiz, $course, $cm, $fromuser, $SITE, $CFG);

        $totalusers = count($enrolledusers);
        $emailsentcount = 0;
        $failedemails = [];

        // Log bulk start
        $this->log_quiz_invite_bulk_start($quiz, $course, $sentby, $totalusers);

        // Process users in batches
        $userids = array_keys($enrolledusers);
        $totalbatches = ceil($totalusers / $batchsize);

        for ($batch = 0; $batch < $totalbatches; $batch++) {
            $start = $batch * $batchsize;
            $batchuserids = array_slice($userids, $start, $batchsize);
            $batchusers = [];
            
            foreach ($batchuserids as $userid) {
                $batchusers[$userid] = $enrolledusers[$userid];
            }

            $batchresult = $this->send_batch_email($batchusers, $subject, $messagetext, $messagehtml, $SITE, $quiz, $course, $sentby);
            
            $emailsentcount += $batchresult['sent'];
            $failedemails = array_merge($failedemails, $batchresult['failed']);

            // Update progress
            if ($batch % 5 === 0) { // Every 5 batches
                $this->update_progress($batch + 1, $totalbatches);
            }

            // Small delay between batches
            if (($batch + 1) < $totalbatches) {
                usleep(300000); // 0.3 second delay
            }
        }

        // Log bulk completion
        $this->log_quiz_invite_bulk_complete($quiz, $course, $sentby, $emailsentcount, $totalusers, $failedemails);

        // Send completion notification to the user who initiated
        $this->send_completion_notification($sentby, $quiz, $emailsentcount, $totalusers, $failedemails);
    }

    /**
     * Send batch emails
     */
    private function send_batch_email($recipients, $subject, $messagetext, $messagehtml, $SITE, $quiz, $course, $sentby) {
        global $CFG;

        $mailer = get_mailer();
        $supportuser = \core_user::get_support_user();
        $batchresult = [
            'sent' => 0,
            'failed' => []
        ];

        foreach ($recipients as $user) {
            // Skip conditions
            if ($user->emailstop || $user->suspended || $user->id == $sentby || empty($user->email)) {
                continue;
            }

            $mailer->clearAllRecipients();
            $mailer->setFrom($supportuser->email, format_string($SITE->fullname));
            $mailer->addAddress($user->email, fullname($user));
            $mailer->addReplyTo($supportuser->email);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $messagehtml;
            $mailer->AltBody = $messagetext;
            $mailer->CharSet = 'UTF-8';

            if ($mailer->send()) {
                $batchresult['sent']++;
                $this->log_quiz_invite_sent($quiz, $course, $sentby, $user->id);
            } else {
                $batchresult['failed'][] = [
                    'userid' => $user->id,
                    'username' => fullname($user),
                    'email' => $user->email,
                    'error' => $mailer->ErrorInfo
                ];
                $this->log_quiz_invite_failed($quiz, $course, $sentby, $user->id, $mailer->ErrorInfo);
            }
        }

        return $batchresult;
    }

    /**
     * Build email content
     */
    private function build_email_content($quiz, $course, $cm, $fromuser, $SITE, $CFG) {
        $viewurl = new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
        
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

        // Plain text version
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

    /**
     * Send completion notification to the user who initiated the task
     */
    private function send_completion_notification($userid, $quiz, $sentcount, $totalusers, $failedemails) {
        $user = \core_user::get_user($userid);
        
        $subject = get_string('quizinvitecompletesubject', 'block_gamification', format_string($quiz->name));
        
        $message = get_string('quizinvitecompletebody', 'block_gamification', [
            'quizname' => format_string($quiz->name),
            'sentcount' => $sentcount,
            'totalusers' => $totalusers
        ]);
        
        if (!empty($failedemails)) {
            $failedcount = count($failedemails);
            $message .= "\n\n" . get_string('quizinvitefailedbody', 'block_gamification', $failedcount);
        }

        $eventdata = new \core\message\message();
        $eventdata->component         = 'block_gamification';
        $eventdata->name              = 'quizinvitecomplete';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $user;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = format_text($message, FORMAT_HTML);
        $eventdata->smallmessage      = $subject;
        $eventdata->notification      = 1;

        message_send($eventdata);
    }

    /**
     * Update task progress
     */
    private function update_progress($current, $total) {
        mtrace("Processed batch $current of $total");
    }

    /**
     * Logging methods - self-contained for the task
     */
    private function log_quiz_invite_bulk_start($quiz, $course, $sentby, $totalusers) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'userid' => $sentby,
            'other' => [
                'quizname' => $quiz->name,
                'totalusers' => $totalusers
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_bulk_start::create($params);
        $event->trigger();
    }
    
    private function log_quiz_invite_bulk_complete($quiz, $course, $sentby, $sentcount, $totalusers, $failedemails) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'userid' => $sentby,
            'other' => [
                'quizname' => $quiz->name,
                'sentcount' => $sentcount,
                'totalusers' => $totalusers,
                'failedcount' => count($failedemails)
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_bulk_complete::create($params);
        $event->trigger();
    }
    
    private function log_quiz_invite_sent($quiz, $course, $sentby, $recipientid) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'userid' => $sentby,
            'relateduserid' => $recipientid,
            'other' => [
                'quizname' => $quiz->name
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_sent::create($params);
        $event->trigger();
    }
    
    private function log_quiz_invite_failed($quiz, $course, $sentby, $recipientid, $error) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'userid' => $sentby,
            'relateduserid' => $recipientid,
            'other' => [
                'quizname' => $quiz->name,
                'error' => $error
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_failed::create($params);
        $event->trigger();
    }
}