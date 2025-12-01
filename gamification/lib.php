<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Queue quiz invitation emails for background processing
 */
function block_gamification_queue_quiz_emails($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml) {
    global $DB;
    
    $coursecontext = context_course::instance($course->id);
    $enrolledusers = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
    
    $batch_size = 25;
    $batch_count = 0;
    $current_batch = [];
    
    foreach ($enrolledusers as $user) {
        // Skip users who don't want emails, are suspended, or are the sender
        if ($user->emailstop || $user->suspended || $user->id == $fromuser->id) {
            continue;
        }
        
        $current_batch[] = [
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname
        ];
        
        if (count($current_batch) >= $batch_size) {
            // Save this batch to the queue
            block_gamification_save_quiz_email_batch($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
            $current_batch = [];
            $batch_count++;
        }
    }
    
    // Save any remaining users in the final batch
    if (!empty($current_batch)) {
        block_gamification_save_quiz_email_batch($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
    }
    
    return $batch_count + 1; // Return total number of batches
}

/**
 * Save a quiz email batch to the queue table
 */
function block_gamification_save_quiz_email_batch($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $batch_users, $batch_number) {
    global $DB;
    
    $queue_record = new stdClass();
    $queue_record->quizid = $quiz->id;
    $queue_record->courseid = $course->id;
    $queue_record->fromuserid = $fromuser->id;
    $queue_record->subject = $subject;
    $queue_record->messagetext = $messagetext;
    $queue_record->messagehtml = $messagehtml;
    $queue_record->batch_data = json_encode($batch_users);
    $queue_record->batch_number = $batch_number;
    $queue_record->status = 'pending';
    $queue_record->timecreated = time();
    $queue_record->timemodified = time();
    
    $DB->insert_record('block_gamif_quiz_queue', $queue_record);
}

/**
 * Process quiz email batches via cron
 */
function block_gamification_process_quiz_email_queue() {
    global $DB, $CFG, $SITE;
    
    // LOG: Task started
    $starttime = time();
    \block_gamification\event\quiz_task_started::create([
        'context' => context_system::instance(),
        'other' => [
            'start_time' => $starttime,
            'action' => 'process_quiz_email_queue'
        ]
    ])->trigger();
    
    mtrace("Starting quiz email queue processing at " . date('Y-m-d H:i:s', $starttime));
    
    // Get pending batches (limit to 5 batches per cron run to prevent overload)
    $pending_batches = $DB->get_records('block_gamif_quiz_queue', ['status' => 'pending'], 'timecreated ASC', '*', 0, 5);
    
    if (empty($pending_batches)) {
        mtrace("No pending quiz email batches found.");
        
        // LOG: Task completed with no work
        \block_gamification\event\quiz_task_completed::create([
            'context' => context_system::instance(),
            'other' => [
                'start_time' => $starttime,
                'end_time' => time(),
                'batches_processed' => 0,
                'emails_sent' => 0,
                'action' => 'process_quiz_email_queue'
            ]
        ])->trigger();
        
        return 0;
    }
    
    mtrace("Found " . count($pending_batches) . " quiz email batch(es) to process");
    
    $mailer = get_mailer();
    $supportuser = core_user::get_support_user();
    $processed_batches = 0;
    $total_emails_sent = 0;
    $total_emails_failed = 0;
    
    foreach ($pending_batches as $batch) {
        try {
            mtrace("Processing batch #" . $batch->batch_number . " (ID: " . $batch->id . ")");
            
            $users = json_decode($batch->batch_data, true);
            $success_count = 0;
            $fail_count = 0;
            
            mtrace("Batch contains " . count($users) . " recipients");
            
            foreach ($users as $user_data) {
                $mailer->clearAllRecipients();
                $mailer->setFrom($supportuser->email, format_string($SITE->fullname));
                $mailer->addAddress($user_data['email'], $user_data['firstname'] . ' ' . $user_data['lastname']);
                $mailer->addReplyTo($supportuser->email);
                $mailer->Subject = $batch->subject;
                $mailer->isHTML(true);
                $mailer->Body = $batch->messagehtml;
                $mailer->AltBody = $batch->messagetext;
                $mailer->CharSet = 'UTF-8';
                
                if ($mailer->send()) {
                    $success_count++;
                    $total_emails_sent++;
                    
                    // LOG: Individual email sent
                    \block_gamification\event\quiz_email_sent::create([
                        'context' => context_course::instance($batch->courseid),
                        'objectid' => $batch->quizid,
                        'relateduserid' => $user_data['userid'],
                        'other' => [
                            'quizid' => $batch->quizid,
                            'subject' => $batch->subject,
                            'batch_id' => $batch->id,
                            'recipient_email' => $user_data['email'],
                            'batch_number' => $batch->batch_number
                        ]
                    ])->trigger();
                    
                } else {
                    $fail_count++;
                    $total_emails_failed++;
                    
                    mtrace("Failed to send email to: " . $user_data['email'] . " - Error: " . $mailer->ErrorInfo);
                    
                    // LOG: Email failed
                    \block_gamification\event\quiz_email_failed::create([
                        'context' => context_course::instance($batch->courseid),
                        'objectid' => $batch->quizid,
                        'relateduserid' => $user_data['userid'],
                        'other' => [
                            'quizid' => $batch->quizid,
                            'subject' => $batch->subject,
                            'batch_id' => $batch->id,
                            'recipient_email' => $user_data['email'],
                            'error_message' => $mailer->ErrorInfo,
                            'batch_number' => $batch->batch_number
                        ]
                    ])->trigger();
                }
            }
            
            mtrace("Batch #" . $batch->batch_number . " completed: " . $success_count . " sent, " . $fail_count . " failed");
            
            // Update batch status
            $batch->status = 'sent';
            $batch->processed_count = $success_count;
            $batch->failed_count = $fail_count;
            $batch->timemodified = time();
            $DB->update_record('block_gamif_quiz_queue', $batch);
            
            $processed_batches++;
            
            // Small delay between batches to prevent server overload
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            // Mark batch as failed
            $batch->status = 'failed';
            $batch->error_message = $e->getMessage();
            $batch->timemodified = time();
            $DB->update_record('block_gamif_quiz_queue', $batch);
            
            mtrace("Batch #" . $batch->batch_number . " failed with exception: " . $e->getMessage());
            
            // LOG: Batch failed
            \block_gamification\event\quiz_batch_failed::create([
                'context' => context_system::instance(),
                'objectid' => $batch->id,
                'other' => [
                    'quizid' => $batch->quizid,
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'error_message' => $e->getMessage()
                ]
            ])->trigger();
        }
    }
    
    // LOG: Task completed
    $endtime = time();
    $duration = $endtime - $starttime;
    
    \block_gamification\event\quiz_task_completed::create([
        'context' => context_system::instance(),
        'other' => [
            'start_time' => $starttime,
            'end_time' => $endtime,
            'duration_seconds' => $duration,
            'batches_processed' => $processed_batches,
            'emails_sent' => $total_emails_sent,
            'emails_failed' => $total_emails_failed,
            'action' => 'process_quiz_email_queue'
        ]
    ])->trigger();
    
    mtrace("Quiz email queue processing completed at " . date('Y-m-d H:i:s', $endtime));
    mtrace("Processed " . $processed_batches . " batches, " . $total_emails_sent . " emails sent, " . $total_emails_failed . " emails failed");
    mtrace("Total duration: " . $duration . " seconds");
    
    return $processed_batches;
}

/**
 * Clean up old sent quiz email queue records (older than 30 days)
 */
function block_gamification_cleanup_quiz_email_queue() {
    global $DB;
    
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    
    // Get count before deletion for logging
    $count = $DB->count_records_select('block_gamif_quiz_queue', 'status = ? AND timemodified < ?', ['sent', $thirty_days_ago]);
    
    // Delete the records
    $DB->delete_records_select('block_gamif_quiz_queue', 'status = ? AND timemodified < ?', ['sent', $thirty_days_ago]);
    
    return $count;
}

/**
 * Queue quiz invitation emails with custom recipient selection
 */
function block_gamification_queue_quiz_emails_custom($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $formdata) {
    global $DB;
    
    $coursecontext = context_course::instance($course->id);
    $recipients = [];
    
    switch ($formdata->recipient_type) {
        case 'all':
            // All enrolled users who can attempt the quiz
            $recipients = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            break;
            
        case 'groups':
            if ($formdata->selected_groups == 0) {
                // All groups
                $recipients = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            } else {
                // Specific group
                $recipients = get_enrolled_users($coursecontext, 'mod/quiz:attempt', $formdata->selected_groups, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            }
            break;
            
        case 'users':
            // Specific users by email
            $emails = array_filter(array_map('trim', explode("\n", $formdata->selected_users)));
            if (!empty($emails)) {
                list($sql, $params) = $DB->get_in_or_equal($emails);
                $recipients = $DB->get_records_select('user', "email $sql AND deleted = 0", $params, '', 'id, email, firstname, lastname, emailstop, suspended');
            }
            break;
    }
    
    $batch_size = 25;
    $batch_count = 0;
    $current_batch = [];
    
    foreach ($recipients as $user) {
        // Skip users who don't want emails, are suspended, or are the sender
        if ($user->emailstop || $user->suspended || $user->id == $fromuser->id) {
            continue;
        }
        
        $current_batch[] = [
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname
        ];
        
        if (count($current_batch) >= $batch_size) {
            // Save this batch to the queue
            block_gamification_save_quiz_email_batch($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
            $current_batch = [];
            $batch_count++;
        }
    }
    
    // Save any remaining users in the final batch
    if (!empty($current_batch)) {
        block_gamification_save_quiz_email_batch($quiz, $course, $fromuser, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
    }
    
    return $batch_count + 1; // Return total number of batches
}