<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Generate random code for onsite meetings
 */
function gmtracker_generate_code($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Handle code-based attendance for onsite meetings - JOIN
 */
function gmtracker_handle_join_code($gmtracker, $userid, $code) {
    global $DB;
    
    // Check if code matches
    if ($gmtracker->joincode !== $code) {
        return 'invalidcode';
    }
    
    // Check if already joined
    $existing = $DB->get_record('gmtracker_attendance', [
        'gmtrackerid' => $gmtracker->id,
        'userid' => $userid
    ]);
    
    if ($existing && $existing->jointime) {
        return 'alreadyjoined';
    }
    
    if (!$existing) {
        $attendance = (object)[
            'gmtrackerid' => $gmtracker->id,
            'userid' => $userid,
            'jointime' => time()
        ];
        $DB->insert_record('gmtracker_attendance', $attendance);
    } else {
        $existing->jointime = time();
        $DB->update_record('gmtracker_attendance', $existing);
    }
    
    return 'success';
}

/**
 * Handle code-based attendance for onsite meetings - LEAVE
 */
function gmtracker_handle_leave_code($gmtracker, $userid, $code) {
    global $DB;
    
    // Check if code matches
    if ($gmtracker->leavecode !== $code) {
        return 'invalidcode';
    }
    
    // Check if joined but not left
    $attendance = $DB->get_record('gmtracker_attendance', [
        'gmtrackerid' => $gmtracker->id,
        'userid' => $userid,
        'leavetime' => null
    ]);
    
    if (!$attendance || !$attendance->jointime) {
        return 'notjoinedyet';
    }
    
    $attendance->leavetime = time();
    $attendance->duration = $attendance->leavetime - $attendance->jointime;
    $DB->update_record('gmtracker_attendance', $attendance);
    
    return 'success';
}

/**
 * Format minutes into readable duration for emails.
 */
function gmtracker_format_minutes_email($minutes) {
    if ($minutes < 60) {
        return $minutes . ' minutes';
    } else {
        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;
        if ($remaining > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $remaining . ' minutes';
        } else {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
    }
}

/**
 * Build Google Calendar URL
 */
function gmtracker_get_google_calendar_url($data, $course, $meetingdate, $duration, $viewurl) {
    // Parse start time and end time (duration in minutes)
    $start = date('Ymd\THis', $data->meetingdate);
    $end = date('Ymd\THis', $data->meetingdate + ($data->duration * 60));

    $params = [
        'action' => 'TEMPLATE',
        'text' => 'Training: ' . format_string($data->name) . ' - ' . format_string($course->fullname),
        'dates' => "{$start}/{$end}",
        'details' => 'Join via Moodle GM Tracker: ' . $viewurl->out(false),
        'location' => $data->meetingtype === 'online' ? 'Online' : s($data->location),
    ];

    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

/** Helper to build plain text and HTML versions of the email */
function gmtracker_build_email($data, $course, $viewurl, $meetingdate, $duration, $SITE, $CFG) {
    $add_to_calendar_url = gmtracker_get_google_calendar_url($data, $course, $meetingdate, $duration, $viewurl);

    $trainingname = format_string($data->name);
    $coursename = format_string($course->fullname);
    $hostemail = s($data->hostemail);
    $location = s($data->location);
    $trainingtype = $data->meetingtype;

    $primary = '#34459c'; // Main blue
    $accent = '#f29031'; // Warm orange

    // Build training type specific content
    $location_info = '';
    $important_notes = '';
    $training_type_badge = '';
    
    if ($trainingtype === 'online') {
        $training_type_badge = '<span style="background:#10b981; color:white; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-left:8px;">ONLINE TRAINING</span>';
        $location_info = '
            <tr>
                <td style="color:#6b7280; font-size:14px; padding:6px 0;"><strong>Location:</strong></td>
                <td style="font-size:15px; color:#1f2937; padding:6px 0;">Online (Google Meet)</td>
            </tr>';
        
        $important_notes = '
            <ul style="margin:0; padding-left:20px; color:#374151; font-size:14px; line-height:1.6;">
                <li>This is an <strong>online training session</strong> conducted via Google Meet.</li>
                <li>Access the training only through the GM Tracker portal to log attendance.</li>
                <li>Use <strong>Join Training</strong> to launch Google Meet when ready.</li>
                <li>Click <strong>Leave Training</strong> once finished to record your participation.</li>
                <li>Ensure you have a stable internet connection and working audio/video.</li>
            </ul>';
    } else { // For onsite
        $training_type_badge = '<span style="background:#f59e0b; color:white; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-left:8px;">ONSITE TRAINING</span>';
        $location_info = '
            <tr>
                <td style="color:#6b7280; font-size:14px; padding:6px 0;"><strong>Location:</strong></td>
                <td style="font-size:15px; color:#1f2937; padding:6px 0;">' . $location . '</td>
            </tr>';
        
        $important_notes = '
            <ul style="margin:0; padding-left:20px; color:#374151; font-size:14px; line-height:1.6;">
                <li>This is an <strong>onsite training session</strong> at the specified physical location.</li>
                <li>Use the provided codes in the GM Tracker portal to mark your attendance.</li>
                <li>Enter the <strong>Join Code</strong> when arriving at the training venue.</li>
                <li>Enter the <strong>Leave Code</strong> when departing from the training venue.</li>
                <li>Access the GM Tracker portal to enter codes and track your participation.</li>
            </ul>';
    }

    $messagehtml = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Training Invitation</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f5f6fa; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif;">
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f5f6fa; margin:0; padding:0;">
        <tr>
          <td align="center" style="padding:20px;">
            <!-- CARD -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
              
              <!-- HEADER -->
              <tr>
                <td style="background:' . $primary . '; color:#ffffff; padding:28px 36px; text-align:center;">
                  <h1 style="margin:0; font-size:24px; font-weight:600;">Training Invitation ' . $training_type_badge . '</h1>
                  <p style="margin:6px 0 0 0; font-size:14px; opacity:0.85;">From ' . format_string($SITE->fullname) . '</p>
                </td>
              </tr>

              <!-- TRAINING INFO SECTION -->
              <tr>
                <td style="padding:36px 30px 10px 30px;">
                  <h2 style="margin:0 0 8px 0; font-size:22px; color:' . $primary . '; font-weight:600;">' . $trainingname . '</h2>
                  <p style="margin:0 0 24px 0; font-size:15px; color:#4a4a4a;">
                    <strong>Course:</strong> ' . $coursename . '
                  </p>

                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td width="130" style="color:#6b7280; font-size:14px; padding:6px 0;"><strong>Date & Time:</strong></td>
                      <td style="font-size:15px; color:#1f2937; padding:6px 0;">' . $meetingdate . '</td>
                    </tr>
                    <tr>
                      <td style="color:#6b7280; font-size:14px; padding:6px 0;"><strong>Duration:</strong></td>
                      <td style="font-size:15px; color:#1f2937; padding:6px 0;">' . $duration . '</td>
                    </tr>' . 
                    $location_info . '
                    <tr>
                      <td style="color:#6b7280; font-size:14px; padding:6px 0;"><strong>Host:</strong></td>
                      <td style="font-size:15px; color:#1f2937; padding:6px 0;">' . $hostemail . '</td>
                    </tr>
                  </table>

                  <!-- DIVIDER -->
                  <hr style="border:none; border-top:1px solid #e5e7eb; margin:24px 0;">

                  <!-- BUTTONS -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:30px;">
                    <tr>
                      <td align="center" style="padding-bottom:12px;">
                        <a href="' . $viewurl->out(false) . '" 
                           style="background:' . $primary . '; color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:8px;
                                  font-size:15px; font-weight:600; display:inline-block;">
                          Access Training Portal
                        </a>
                      </td>
                    </tr>
                    <tr>
                      <td align="center">
                        <a href="' . $add_to_calendar_url . '" target="_blank"
                           style="background:' . $accent . '; color:#ffffff; text-decoration:none; padding:12px 30px; border-radius:8px;
                                  font-size:14px; font-weight:500; display:inline-block;">
                            Add to Google Calendar
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

              <!-- REMINDER SECTION -->
              <tr>
                <td style="background:#f9fafb; padding:24px 30px;">
                  <h3 style="margin:0 0 12px 0; font-size:16px; color:' . $accent . '; font-weight:600;">Important Training Notes</h3>
                  ' . $important_notes . '
                </td>
              </tr>

              <!-- FOOTER -->
              <tr>
                <td style="background:#f3f4f6; text-align:center; padding:20px 30px;">
                  <p style="font-size:12px; color:#6b7280; margin:0;">
                    This is an automated message from ' . format_string($SITE->fullname) . '.<br>
                    <a href="' . $CFG->wwwroot . '" style="color:' . $primary . '; text-decoration:none;">Visit Moodle</a> • 
                    <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" style="color:' . $primary . '; text-decoration:none;">View Course</a>
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>';

    // ---- PLAIN TEXT VERSION ----
    $training_type_text = ($trainingtype === 'online') ? "ONLINE TRAINING" : "ONSITE TRAINING";
    $location_text = ($trainingtype === 'online') ? "Location: Online (Google Meet)" : "Location: $location";
    
    $notes_text = ($trainingtype === 'online') ? 
        "Important Training Notes:\n- This is an online training session conducted via Google Meet\n- Access the training only through the GM Tracker portal to log attendance\n- Use 'Join Training' to launch Google Meet when ready\n- Click 'Leave Training' once finished to record your participation\n- Ensure you have a stable internet connection and working audio/video" :
        "Important Training Notes:\n- This is an onsite training session at the specified physical location\n- Use the provided codes in the GM Tracker portal to mark your attendance\n- Enter the 'Join Code' when arriving at the training venue\n- Enter the 'Leave Code' when departing from the training venue\n- Access the GM Tracker portal to enter codes and track your participation";

    $messagetext = "Training Invitation - $training_type_text\n" .
        "==========================================\n" .
        "Training: $trainingname\nCourse: $coursename\n\n" .
        "Date & Time: $meetingdate\nDuration: $duration\n$location_text\nHost: $hostemail\n\n" .
        "Access Training: " . $viewurl->out(false) . "\n" .
        "Add to Google Calendar: $add_to_calendar_url\n\n" .
        "$notes_text\n\n" .
        "This is an automated message from " . format_string($SITE->fullname) . ".";

    return [$messagetext, $messagehtml];
}

/** 
 * Queue emails for selected recipients only
 */
function gmtracker_queue_emails($gmtracker, $course, $subject, $messagetext, $messagehtml, $recipient_type, $selected_groups = 0, $selected_users = '') {
    global $DB;
    
    $context = context_course::instance($course->id);
    $recipients = [];
    
    switch ($recipient_type) {
        case 'all':
            // All enrolled users
            $recipients = get_enrolled_users($context, '', 0, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            break;
            
        case 'groups':
            if ($selected_groups == 0) {
                // All groups
                $recipients = get_enrolled_users($context, '', 0, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            } else {
                // Specific group
                $recipients = get_enrolled_users($context, '', $selected_groups, 'u.id, u.email, u.firstname, u.lastname, u.emailstop, u.suspended', null, 0, 0, true);
            }
            break;
            
        case 'users':
            // Specific users by email
            $emails = array_filter(array_map('trim', explode("\n", $selected_users)));
            if (!empty($emails)) {
                list($sql, $params) = $DB->get_in_or_equal($emails);
                $recipients = $DB->get_records_select('user', "email $sql AND deleted = 0", $params, '', 'id, email, firstname, lastname, emailstop, suspended');
            }
            break;
    }
    
    // Filter out users who don't want emails or are suspended
    $valid_recipients = [];
    foreach ($recipients as $user) {
        if (!$user->emailstop && !$user->suspended && !empty($user->email)) {
            $valid_recipients[] = [
                'userid' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname
            ];
        }
    }
    
    if (empty($valid_recipients)) {
        return 0;
    }
    
    $batch_size = 25;
    $batch_count = 0;
    $current_batch = [];
    
    foreach ($valid_recipients as $user) {
        $current_batch[] = $user;
        
        if (count($current_batch) >= $batch_size) {
            gmtracker_save_email_batch($gmtracker, $course, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
            $current_batch = [];
            $batch_count++;
        }
    }
    
    // Save any remaining users in the final batch
    if (!empty($current_batch)) {
        gmtracker_save_email_batch($gmtracker, $course, $subject, $messagetext, $messagehtml, $current_batch, $batch_count);
    }
    
    return $batch_count + 1; // Return total number of batches
}

/**
 * Save an email batch to the queue table
 */
function gmtracker_save_email_batch($gmtracker, $course, $subject, $messagetext, $messagehtml, $batch_users, $batch_number) {
    global $DB;
    
    $queue_record = new stdClass();
    $queue_record->gmtrackerid = $gmtracker->id;
    $queue_record->courseid = $course->id;
    $queue_record->subject = $subject;
    $queue_record->messagetext = $messagetext;
    $queue_record->messagehtml = $messagehtml;
    $queue_record->batch_data = json_encode($batch_users);
    $queue_record->batch_number = $batch_number;
    $queue_record->status = 'pending';
    $queue_record->timecreated = time();
    $queue_record->timemodified = time();
    
    $DB->insert_record('gmtracker_email_queue', $queue_record);
}

/**
 * Process email batches via cron
 */
function gmtracker_process_email_queue() {
    global $DB, $CFG, $SITE;

    require_once($CFG->dirroot . '/lib/phpmailer/moodle_phpmailer.php');

    // Get pending batches (limit to 5 batches per cron run to prevent overload)
    $pending_batches = $DB->get_records('gmtracker_email_queue', ['status' => 'pending'], 'timecreated ASC', '*', 0, 5);
    
    if (empty($pending_batches)) {
        return true;
    }
    
    $mailer = get_mailer();
    $supportuser = core_user::get_support_user();
    $processed = 0;
    
    foreach ($pending_batches as $batch) {
        try {
            $users = json_decode($batch->batch_data, true);
            $success_count = 0;
            $fail_count = 0;
            
            // Get GMTracker instance for logging context
            $gmtracker = $DB->get_record('gmtracker', ['id' => $batch->gmtrackerid]);
            $course = $DB->get_record('course', ['id' => $batch->courseid]);
            $cm = get_coursemodule_from_instance('gmtracker', $batch->gmtrackerid, $batch->courseid);
            
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
                    
                    // LOG THE EMAIL SEND - Using Moodle's standard logging
                    $log_context = context_module::instance($cm->id);
                    $log_params = [
                        'context' => $log_context,
                        'objectid' => $batch->gmtrackerid,
                        'relateduserid' => $user_data['userid'],
                        'other' => [
                            'gmtrackerid' => $batch->gmtrackerid,
                            'subject' => $batch->subject,
                            'batch_id' => $batch->id,
                            'recipient_email' => $user_data['email']
                        ]
                    ];
                    
                    // Trigger email sent event
                    $event = \mod_gmtracker\event\email_sent::create($log_params);
                    $event->trigger();
                    
                } else {
                    $fail_count++;
                    debugging('GMTracker email failed for ' . $user_data['email'] . ': ' . $mailer->ErrorInfo, DEBUG_DEVELOPER);
                    
                    // Also log failed attempts
                    $log_context = context_module::instance($cm->id);
                    $log_params = [
                        'context' => $log_context,
                        'objectid' => $batch->gmtrackerid,
                        'relateduserid' => $user_data['userid'],
                        'other' => [
                            'gmtrackerid' => $batch->gmtrackerid,
                            'subject' => $batch->subject,
                            'batch_id' => $batch->id,
                            'recipient_email' => $user_data['email'],
                            'error' => $mailer->ErrorInfo
                        ]
                    ];
                    
                    // Trigger email failed event
                    $event = \mod_gmtracker\event\email_failed::create($log_params);
                    $event->trigger();
                }
            }
            
            // Update batch status
            $batch->status = 'sent';
            $batch->processed_count = $success_count;
            $batch->failed_count = $fail_count;
            $batch->timemodified = time();
            $DB->update_record('gmtracker_email_queue', $batch);
            
            $processed++;
            
            // Small delay between batches to prevent server overload
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            // Mark batch as failed
            $batch->status = 'failed';
            $batch->error_message = $e->getMessage();
            $batch->timemodified = time();
            $DB->update_record('gmtracker_email_queue', $batch);
            
            debugging('GMTracker email batch processing failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    
    return $processed;
}

/**
 * Clean up old sent email queue records (older than 30 days)
 */
function gmtracker_cleanup_email_queue() {
    global $DB;
    
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    
    // Get count before deletion for logging
    $count = $DB->count_records_select('gmtracker_email_queue', 'status = ? AND timemodified < ?', ['sent', $thirty_days_ago]);
    
    // Delete the records
    $DB->delete_records_select('gmtracker_email_queue', 'status = ? AND timemodified < ?', ['sent', $thirty_days_ago]);
    
    return $count;
}

/**
 * Mark all users who haven't left the meeting as incomplete when host leaves
 */
function gmtracker_mark_incomplete_users($gmtracker) {
    global $DB, $CFG;
    
    $context = context_course::instance($gmtracker->course);
    
    // Get all enrolled users in the course
    $enrolled_users = get_enrolled_users($context, '', 0, 'u.id', null, 0, 0, true);
    
    $incomplete_count = 0;
    
    foreach ($enrolled_users as $user) {
        // Check if this user already has an attendance record
        $existing_attendance = $DB->get_record('gmtracker_attendance', [
            'gmtrackerid' => $gmtracker->id,
            'userid' => $user->id
        ]);
        
        if ($existing_attendance) {
            // User has an attendance record - mark as incomplete if they haven't left
            if (empty($existing_attendance->leavetime)) {
                $existing_attendance->incomplete = 1;
                $DB->update_record('gmtracker_attendance', $existing_attendance);
                $incomplete_count++;
            }
        } else {
            // User never joined - create an incomplete attendance record
            $attendance = (object)[
                'gmtrackerid' => $gmtracker->id,
                'userid' => $user->id,
                'jointime' => null,
                'leavetime' => null,
                'duration' => null,
                'incomplete' => 1
            ];
            $DB->insert_record('gmtracker_attendance', $attendance);
            $incomplete_count++;
        }
    }
    
    return $incomplete_count;
}

/**
 * Check if host has left the meeting
 */
function gmtracker_has_host_left($gmtracker) {
    global $DB;
    
    // Get host user record
    $host = $DB->get_record('user', ['email' => $gmtracker->hostemail]);
    if (!$host) {
        return false;
    }
    
    // Check if host has left (has leavetime set)
    $host_attendance = $DB->get_record('gmtracker_attendance', [
        'gmtrackerid' => $gmtracker->id,
        'userid' => $host->id
    ]);
    
    return $host_attendance && $host_attendance->leavetime;
}

/* -------------------- CORE MODULE FUNCTIONS -------------------- */

/**
 * Add or update a calendar event when a meeting is created or updated.
 */
function gmtracker_set_calendar_event($gmtracker) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/calendar/lib.php');

    // Remove existing calendar events for this instance.
    $DB->delete_records('event', [
        'modulename' => 'gmtracker',
        'instance' => $gmtracker->id
    ]);

    // Only create a calendar event if meeting date is set and calendar option enabled.
    if (empty($gmtracker->meetingdate) || (isset($gmtracker->addtocalendar) && !$gmtracker->addtocalendar)) {
        return true;
    }

    // Prepare the event data.
    $event = new stdClass();
    $event->name         = $gmtracker->name;
    $event->description  = isset($gmtracker->intro) ? $gmtracker->intro : '';
    $event->format       = FORMAT_HTML;
    $event->courseid     = $gmtracker->course;
    $event->groupid      = 0;
    $event->userid       = 0;
    $event->modulename   = 'gmtracker';
    $event->instance     = $gmtracker->id;
    $event->eventtype    = 'course';
    $event->timestart    = $gmtracker->meetingdate;
    $event->timeduration = isset($gmtracker->duration) ? $gmtracker->duration * 60 : 0;
    $event->visible      = true;
    $event->timemodified = time();

    // Use ?g= parameter because CM may not yet exist at add time.
    $event->url = new moodle_url('/mod/gmtracker/view.php', ['g' => $gmtracker->id]);

    try {
        calendar_event::create($event, false);
    } catch (moodle_exception $e) {
        debugging('Could not create gmtracker calendar event: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }

    return true;
}

/**
 * Delete all calendar events associated with a gmtracker instance.
 */
function gmtracker_delete_calendar_events($gmtrackerid) {
    global $DB;

    $DB->delete_records('event', [
        'modulename' => 'gmtracker',
        'instance' => $gmtrackerid
    ]);

    return true;
}

/**
 * Called when a new gmtracker instance is created.
 */
function gmtracker_add_instance($data, $mform = null) {
    global $DB, $CFG, $SITE;

    require_once($CFG->dirroot . '/lib/moodlelib.php');
    require_once($CFG->dirroot . '/lib/accesslib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/lib/phpmailer/moodle_phpmailer.php');

    $data->timecreated = $data->timemodified = time();
    
    // Generate codes for onsite meetings
    if ($data->meetingtype === 'onsite') {
        $data->joincode = gmtracker_generate_code();
        $data->leavecode = gmtracker_generate_code();
    } else {
        $data->joincode = null;
        $data->leavecode = null;
        // Only clear location if it's empty 
        if (empty($data->location)) {
            $data->location = null;
        }
    }
    
    $data->id = $DB->insert_record('gmtracker', $data);

    // Create calendar event
    gmtracker_set_calendar_event($data);

    // Email notifications based on user selection
    if (empty($data->sendinvites)) {
        return $data->id;
    }

    // Queue emails for selected recipients only
    $course = $DB->get_record('course', ['id' => $data->course], '*', MUST_EXIST);
    
    $subject = "Training Invitation: " . format_string($data->name) . " - " . format_string($course->fullname);
    $viewurl = new moodle_url('/mod/gmtracker/view.php', ['g' => $data->id]);
    $meetingdate = !empty($data->meetingdate) ? userdate($data->meetingdate, get_string('strftimedatetime', 'langconfig')) : 'To be determined';
    $duration = !empty($data->duration) ? gmtracker_format_minutes_email($data->duration) : 'Not specified';

    [$messagetext, $messagehtml] = gmtracker_build_email($data, $course, $viewurl, $meetingdate, $duration, $SITE, $CFG);
    
    // Get recipient parameters
    $recipient_type = $data->recipient_type ?? 'all';
    $selected_groups = $data->selected_groups ?? 0;
    $selected_users = $data->selected_users ?? '';
    
    // Queue emails for scheduled cron processing
    $batch_count = gmtracker_queue_emails($data, $course, $subject, $messagetext, $messagehtml, $recipient_type, $selected_groups, $selected_users);
    
    if ($batch_count > 0) {
        // Log that emails were queued
        $event = \mod_gmtracker\event\emails_queued::create([
            'context' => context_course::instance($course->id),
            'objectid' => $data->id,
            'other' => [
                'batches_created' => $batch_count,
                'subject' => $subject,
                'recipient_type' => $recipient_type
            ]
        ]);
        $event->trigger();
    }

    return $data->id;
}

/**
 * Called when a gmtracker instance is updated.
 */
function gmtracker_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $result = $DB->update_record('gmtracker', $data);

    if ($result) {
        gmtracker_set_calendar_event($data);
    }

    return $result;
}

/**
 * Called when a gmtracker instance is deleted.
 */
function gmtracker_delete_instance($id) {
    global $DB;

    if (!$gmtracker = $DB->get_record('gmtracker', ['id' => $id])) {
        return false;
    }

    // Delete calendar events.
    gmtracker_delete_calendar_events($id);

    // Delete attendance records if applicable.
    $DB->delete_records('gmtracker_attendance', ['gmtrackerid' => $gmtracker->id]);

    // Delete email queue records for this instance.
    $DB->delete_records('gmtracker_email_queue', ['gmtrackerid' => $gmtracker->id]);

    // Delete the main gmtracker record.
    $DB->delete_records('gmtracker', ['id' => $gmtracker->id]);

    // RETURN TRUE to indicate successful deletion
    return true;
}

/**
 * Indicates which features are supported by this module.
 */
function gmtracker_supports($feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:                return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:       return true;
        case FEATURE_GRADE_HAS_GRADE:               return false;
        case FEATURE_GRADE_OUTCOMES:                return false;
        case FEATURE_MOD_INTRO:                     return true;
        case FEATURE_SHOW_DESCRIPTION:              return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:    return true;
        case FEATURE_COMMENT:                       return false;
        case FEATURE_MOD_ARCHETYPE:                 return MOD_ARCHETYPE_OTHER;
        default:                                    return null;
    }
}

/**
 * Extend global navigation if needed.
 */
function gmtracker_extend_navigation($navigation, $course, $module, $cm) {
    // No custom navigation items needed.
}

/**
 * Extend settings navigation if needed.
 */
function gmtracker_extend_settings_navigation($settingsnav, $navigation) {
    // No custom settings navigation needed.
}