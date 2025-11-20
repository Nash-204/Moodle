<?php
/**
 * Adhoc task for sending training invitations in background
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task for sending training invitations in background
 */
class send_training_invites_task extends \core\task\adhoc_task {

    /**
     * Execute the task
     */
    public function execute() {
        global $CFG, $DB, $SITE;

        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/mod/gmtracker/lib.php');

        $data = $this->get_custom_data();
        
        $gmtrackerid = $data->gmtrackerid;
        $sentby = $data->sentby;
        $batchsize = $data->batchsize ?? 25;

        // Get gmtracker details
        $gmtracker = $DB->get_record('gmtracker', ['id' => $gmtrackerid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $gmtracker->course], '*', MUST_EXIST);
        
        // Get all enrolled users in the course
        $coursecontext = \context_course::instance($course->id);
        $enrolledusers = get_enrolled_users($coursecontext, '', 0, 'u.*', null, 0, 0, true);

        if (empty($enrolledusers)) {
            return;
        }

        // Build email content once
        $subject = "Training Invitation: " . format_string($gmtracker->name) . " - " . format_string($course->fullname);
        $viewurl = new \moodle_url('/mod/gmtracker/view.php', ['g' => $gmtracker->id]);
        $meetingdate = !empty($gmtracker->meetingdate) ? userdate($gmtracker->meetingdate, get_string('strftimedatetime', 'langconfig')) : 'To be determined';
        $duration = !empty($gmtracker->duration) ? gmtracker_format_minutes_email($gmtracker->duration) : 'Not specified';

        list($messagetext, $messagehtml) = gmtracker_build_email($gmtracker, $course, $viewurl, $meetingdate, $duration, $SITE, $CFG);

        $totalusers = count($enrolledusers);
        $emailsentcount = 0;
        $failedemails = [];

        // Log bulk start
        gmtracker_log_invite_bulk_start($gmtracker, $course, $sentby, $totalusers);

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

            $batchresult = $this->send_batch_email($batchusers, $subject, $messagetext, $messagehtml, $SITE, $gmtracker, $course, $sentby);
            
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
        gmtracker_log_invite_bulk_complete($gmtracker, $course, $sentby, $emailsentcount, $totalusers, $failedemails);
    }

    /**
     * Send batch emails
     */
    private function send_batch_email($recipients, $subject, $messagetext, $messagehtml, $SITE, $gmtracker, $course, $sentby) {
        global $CFG;

        $mailer = get_mailer();
        $supportuser = \core_user::get_support_user();
        $batchresult = [
            'sent' => 0,
            'failed' => []
        ];

        foreach ($recipients as $user) {
            // Skip conditions
            if ($user->emailstop || $user->suspended || empty($user->email)) {
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
                // Log successful email sent using the function from lib.php
                gmtracker_log_invite_sent($gmtracker, $course, $sentby, $user->id);
            } else {
                $batchresult['failed'][] = [
                    'userid' => $user->id,
                    'username' => fullname($user),
                    'email' => $user->email,
                    'error' => $mailer->ErrorInfo
                ];
                // Log failed email using the function from lib.php
                gmtracker_log_invite_failed($gmtracker, $course, $sentby, $user->id, $mailer->ErrorInfo);
            }
        }

        return $batchresult;
    }

    /**
     * Update task progress
     */
    private function update_progress($current, $total) {
        mtrace("Processed batch $current of $total");
    }
}