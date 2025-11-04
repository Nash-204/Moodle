<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer callbacks for awarding XP and badges.
 *
 * @package   block_gamification
 */
class observer {

    /**
     * Award XP and notify the user.
     */
    private static function award_xp_and_notify(int $userid, int $points, string $reason): void {
        global $USER;

        if (isguestuser($userid) || $userid <= 0 || $points <= 0) {
            return;
        }

        $manager = new leaderboard_manager();
        $manager->add_xp($userid, $points);

        // Toast notification (only for the same user).
        if ($USER->id === $userid) {
            \core\notification::success(get_string('xpearnedsmall', 'block_gamification', [
                'points' => $points,
                'reason' => $reason
            ]));
        }

        // Moodle notification.
        $eventdata = new \core\message\message();
        $eventdata->component         = 'block_gamification';
        $eventdata->name              = 'xpnotification';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $userid;
        $eventdata->subject           = get_string('xpearnedsubject', 'block_gamification');
        $eventdata->fullmessage       = get_string('xpearnedfull', 'block_gamification', [
            'points' => $points,
            'reason' => $reason
        ]);
        $eventdata->fullmessageformat = FORMAT_MARKDOWN;
        $eventdata->fullmessagehtml   = "<p>🎉 You earned <strong>{$points} XP</strong> for {$reason}.</p>";
        $eventdata->smallmessage      = get_string('xpearnedsmall', 'block_gamification', [
            'points' => $points,
            'reason' => $reason
        ]);
        $eventdata->notification      = 1;
        $eventdata->contexturl        = (new \moodle_url('/my'))->out(false);
        $eventdata->contexturlname    = get_string('myhome');
        message_send($eventdata);

        // Save preference for frontend.
        set_user_preference('block_gamification_toast',
            get_string('xpearnedsmall', 'block_gamification', [
                'points' => $points,
                'reason' => $reason
            ]),
            $userid
        );
    }

    /**
     * Handle Google Meet completion 
     */
    public static function meeting_left(\mod_gmtracker\event\meeting_left $event): void {
        global $DB;

        $userid = $event->userid;
        $courseid = $event->courseid;
        $userduration = $event->other['userduration'];
        $meetingduration = $event->other['meetingduration'];

        // Safety checks
        if ($userduration <= 0 || $meetingduration <= 0) {
            return;
        }

        // Get ratio (e.g. 0.5 = 50%, 1.2 = 120%)
        $ratio = $userduration / $meetingduration;

        $basexp = (int)(get_config('block_gamification', 'xp_gmtracker') ?? 100);
        $xp = 0;

        if ($ratio >= 1.1) {
            $xp = $basexp * 1.2; // extra XP
        } elseif ($ratio >= 0.9) {
            $xp = $basexp; // full XP
        } elseif ($ratio >= 0.5) {
            $xp = $basexp * 0.5; // half XP
        } else {
            $xp = 0; // too short
        }

        if ($xp > 0) {
            self::award_xp_and_notify($userid, $xp, get_string('reason_meetingleft', 'block_gamification'));
        }
    }

    
    /**
     * Course completed → award XP and badges.
     */
    public static function course_completed(\core\event\course_completed $event): void {
        global $DB;

        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        
        if (!$userid) {
            return;
        }

        // XP Award with course level multiplier
        if (get_config('block_gamification', 'enable_coursecompleted')) {
            $basepoints = (int)(get_config('block_gamification', 'xp_coursecompleted') ?? 100);
            
            // Apply course level multiplier
            $multiplier = self::get_course_level_multiplier($courseid);
            $finalpoints = (int) round($basepoints * $multiplier);
            
            self::award_xp_and_notify($userid, $finalpoints, get_string('reason_course', 'block_gamification'));
            
            // Optional: Add debug logging to see the multiplier in action
            debugging("Course XP: Base=$basepoints, Multiplier=$multiplier, Final=$finalpoints for course $courseid", DEBUG_DEVELOPER);
        }

        // Badges
        $badges = new badge_manager();
        $badges->award_badge_if_new($userid, 'Course_Completer');

        $sql = "SELECT COUNT(DISTINCT course) 
                FROM {course_completions} 
                WHERE userid = :userid 
                AND timecompleted IS NOT NULL"; // Fixed: should be completed courses
        $completedcount = $DB->get_field_sql($sql, ['userid' => $userid]);

        if ($completedcount >= 5) {
            $badges->award_badge_if_new($userid, 'Course_Master');
        }
    }

    /**
     * Get the course level multiplier
     */
    private static function get_course_level_multiplier(int $courseid): float {
        global $DB;
        
        $coursemultipliers = [
            'Beginner'     => 1.0,
            'Intermediate' => 1.5,
            'Advance'      => 2.0,
        ];
        
        // Get the level from your database table
        $level = $DB->get_field('block_gamif_coursediff', 'level', ['courseid' => $courseid]);
        
        // Default to Beginner if no level is set
        if (!$level || !isset($coursemultipliers[$level])) {
            $level = 'Beginner';
        }
        
        return $coursemultipliers[$level];
    }

    // Course viewed.
    public static function course_viewed(\core\event\course_viewed $event): void {
        global $SITE, $DB;

        $courseid = $event->courseid;
        $userid   = $event->userid;

        // Skip if front page (site home).
        if ($courseid == $SITE->id) {
            return;
        }

        // Skip if guest.
        if (isguestuser($event->relateduserid)) {
            return;
        }

        // Check if enabled in settings.
        if (!get_config('block_gamification', 'enable_courseview')) {
            return;
        }

        try {
            // ✅ Get user's timezone safely via Moodle API
            $user = $DB->get_record('user', ['id' => $userid], 'id, timezone, lang');
            $userTimezone = \core_date::get_user_timezone($user);
            $userTzObject = \core_date::get_user_timezone_object($userTimezone);

            // ✅ Current date/time in user's timezone
            $userNow = new \DateTime('now', $userTzObject);
            $today = $userNow->format('Y-m-d');

            // Unique key: course + date.
            $prefkey = 'block_gamification_courseview_' . $courseid;

            // Last awarded date for this course.
            $lastaward = get_user_preferences($prefkey, '', $userid);

            if ($lastaward !== $today) {
                $points = (int)(get_config('block_gamification', 'xp_courseview') ?? 2);
                self::award_xp_and_notify($userid, $points, get_string('reason_courseview', 'block_gamification'));
                set_user_preference($prefkey, $today, $userid);
            }

        } catch (\Exception $e) {
            // Log error but don't break the course view process
            debugging('Error in gamification course_viewed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * User logged in → award XP, track streaks, award badges.
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $DB, $USER;

        $userid = $event->userid;
        if (!$userid || isguestuser($userid)) {
            return;
        }

        try {
            // Get user's timezone safely via Moodle API
            $user = $DB->get_record('user', ['id' => $userid], 'id, timezone, lang');
            $userTimezone = \core_date::get_user_timezone($user);
            $userTzObject = \core_date::get_user_timezone_object($userTimezone);

            // Current time in user's timezone
            $userNow = new \DateTime('now', $userTzObject);

            // Calculate timestamps in user's timezone
            $userTodayStart = clone $userNow;
            $userTodayStart->setTime(0, 0, 0);
            $todayts = $userTodayStart->getTimestamp();
            
            // Get day of week (1 = Monday, 7 = Sunday)
            $dayOfWeek = $userNow->format('N');

            // Skip weekend days (Saturday = 6, Sunday = 7) for streak tracking
            $isWeekend = ($dayOfWeek >= 6);
            
            // For weekends, we'll still update lastlogin but skip streak calculations
            $userYesterday = clone $userTodayStart;
            $userYesterday->modify('-1 day');
            $yesterdayts = $userYesterday->getTimestamp();

            // XP Award (Daily Login) - Still award on weekends if enabled
            if (get_config('block_gamification', 'enable_dailylogin')) {
                $lastlog = get_user_preferences('block_gamification_lastlogin', 0, $userid);
                if ($lastlog < $todayts) { // Compare timestamps in user's timezone
                    $points = (int)(get_config('block_gamification', 'xp_dailylogin') ?? 5);
                    self::award_xp_and_notify($userid, $points, get_string('reason_daily', 'block_gamification'));
                    set_user_preference('block_gamification_lastlogin', $todayts, $userid);
                }
            }

            // Badge streak tracking - ONLY ON WEEKDAYS
            $badges = new badge_manager();
            $record = $DB->get_record('block_gamif_streaks', ['userid' => $userid]);

            if (!$record) {
                // Only create record on weekdays, skip on weekends
                if (!$isWeekend) {
                    $record = (object)[
                        'userid' => $userid,
                        'lastlogin' => $todayts,
                        'dailystreak' => 1,
                        'weeklystreak' => 1,
                        'lastweeklycheck' => $todayts, // Start the weekly counter
                        'week1' => 1,  // Track week 1 login
                        'week2' => 0,  // Track week 2 login
                        'week3' => 0,  // Track week 3 login  
                        'week4' => 0,  // Track week 4 login
                        'currentweekstart' => self::get_week_start_timestamp($userNow), // Start of current week
                    ];
                    $DB->insert_record('block_gamif_streaks', $record);
                }
                return;
            }

            $lastlogin = (int)$record->lastlogin;

            // DAILY STREAK - Only update on weekdays (Monday-Friday)
            if (!$isWeekend) {
                // Check if last login was on a weekday we should count
                $lastLoginDate = new \DateTime();
                $lastLoginDate->setTimestamp($lastlogin);
                $lastLoginDayOfWeek = $lastLoginDate->format('N');
                $lastLoginWasWeekend = ($lastLoginDayOfWeek >= 6);
                
                // Calculate the expected gap between consecutive weekday logins
                $expectedGap = 1; // Default 1 day gap
                
                // If last login was Friday, next should be Monday (3 day gap)
                if ($lastLoginDayOfWeek == 5 && $dayOfWeek == 1) { // Friday to Monday
                    $expectedGap = 3;
                } 
                // If last login was a weekday and current is next weekday, check the gap
                else if (!$lastLoginWasWeekend) {
                    $daysDifference = $dayOfWeek - $lastLoginDayOfWeek;
                    if ($daysDifference == 1) {
                        $expectedGap = 1; // Consecutive weekdays
                    } else if ($daysDifference > 1) {
                        $expectedGap = $daysDifference + 2; // Account for weekend days in between
                    }
                }

                $actualGap = floor(($todayts - $lastlogin) / (60 * 60 * 24));
                
                if ($actualGap == $expectedGap) {
                    // Perfect! User logged in on the next expected weekday
                    $record->dailystreak += 1;
                } else if ($actualGap > $expectedGap) {
                    // Missed one or more expected logins
                    $record->dailystreak = 1;
                }
                // If logged in today already or unexpected pattern, streak remains unchanged
                
                // Update lastlogin to today (weekday)
                $record->lastlogin = $todayts;
            } else {
                // It's weekend - update lastlogin but don't modify streak
                $record->lastlogin = $todayts;
            }

            // FIXED: WEEKLY STREAK - Track weekly logins over 4 consecutive weeks
            $currentWeekStart = self::get_week_start_timestamp($userNow);
            $lastWeekStart = (int)$record->currentweekstart;
            
            // If we're in a new week
            if ($currentWeekStart > $lastWeekStart) {
                // Shift the weekly tracking
                $record->week4 = $record->week3;  // Week 3 becomes week 4
                $record->week3 = $record->week2;  // Week 2 becomes week 3  
                $record->week2 = $record->week1;  // Week 1 becomes week 2
                $record->week1 = 1;               // Mark current week as logged in
                
                // Update the current week start
                $record->currentweekstart = $currentWeekStart;
                
                // Calculate current weekly streak based on consecutive weeks with logins
                $weeklyStreak = 0;
                if ($record->week1 == 1) $weeklyStreak++;
                if ($record->week2 == 1) $weeklyStreak++;
                if ($record->week3 == 1) $weeklyStreak++; 
                if ($record->week4 == 1) $weeklyStreak++;
                
                $record->weeklystreak = $weeklyStreak;
                
                // Debug logging
                debugging("Weekly tracking - Week1: {$record->week1}, Week2: {$record->week2}, Week3: {$record->week3}, Week4: {$record->week4}, Streak: {$weeklyStreak}", DEBUG_DEVELOPER);
            } else {
                // Still in the same week, ensure current week is marked
                $record->week1 = 1;
            }

            $DB->update_record('block_gamif_streaks', $record);

            // Award badges - based on weekday streaks only
            if (!$isWeekend) {
                if ($record->dailystreak >= 5) { // 5 consecutive weekdays
                    $badges->award_badge_if_new($userid, 'Daily_Streak');
                }
                // Award weekly warrior badge only if user logged in at least once in each of the last 4 weeks
                if ($record->week1 == 1 && $record->week2 == 1 && $record->week3 == 1 && $record->week4 == 1) {
                    $badges->award_badge_if_new($userid, 'Weekly_Streak');
                }
            }

        } catch (\Exception $e) {
            // Log error but don't break the login process
            debugging('Error in gamification user_loggedin: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Helper function to get the start of week timestamp (Monday)
     */
    private static function get_week_start_timestamp(\DateTime $date): int {
        $weekStart = clone $date;
        // Set to Monday of current week (1 = Monday, 7 = Sunday)
        $dayOfWeek = $weekStart->format('N');
        if ($dayOfWeek != 1) {
            $weekStart->modify('last monday');
        }
        $weekStart->setTime(0, 0, 0);
        return $weekStart->getTimestamp();
    }

    /**
     * Quiz attempt submitted → award XP only if passed + badge, prevent multiple XP awards for same quiz.
     */
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        global $DB;

        $userid = $event->userid;
        $attemptid = $event->objectid;

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', IGNORE_MISSING);
        if (!$attempt || $attempt->state !== 'finished') {
            return; // Attempt not finished or doesn't exist
        }

        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', IGNORE_MISSING);
        if (!$quiz) {
            return;
        }

        // If no grades available, can't determine pass/fail
        if ($attempt->sumgrades === null || $quiz->sumgrades == 0) {
            return;
        }

        // Prevent multiple XP awards for the same quiz
        $prefkey = 'block_gamification_quizpassed_' . $quiz->id;
        $lastpassed = get_user_preferences($prefkey, 0, $userid);

        if ($lastpassed > 0) {
            debugging("User {$userid} already received XP for quiz {$quiz->id}", DEBUG_DEVELOPER);
            return; // Already awarded XP for this quiz
        }

        // Calculate user's score percentage
        $percentage = ($attempt->sumgrades / $quiz->sumgrades) * 100;
        
        // Get the actual "Grade to pass" value from quiz settings
        $passingpercentage = self::get_quiz_passing_percentage($quiz);

        debugging("Quiz {$quiz->id}: User score: {$percentage}%, Passing: {$passingpercentage}%", DEBUG_DEVELOPER);

        // Only award XP if user passed (score >= passing grade)
        if ($percentage >= $passingpercentage) {
            // XP Award
            if (get_config('block_gamification', 'enable_quizpass')) {
                $basepoints = (int)(get_config('block_gamification', 'xp_quizpass') ?? 50);
                $multiplier = self::get_quiz_difficulty_multiplier($quiz->id);
                $finalpoints = (int) round($basepoints * $multiplier);
                
                self::award_xp_and_notify($userid, $finalpoints, get_string('reason_quiz', 'block_gamification'));
                
                // Mark this quiz as passed for this user to prevent future XP awards
                set_user_preference($prefkey, time(), $userid);
            }

            // Perfect score badge (can still be awarded even if XP was already given)
            if ((float)$attempt->sumgrades >= (float)$quiz->sumgrades) {
                $badges = new badge_manager();
                $badges->award_badge_if_new($userid, 'Quiz_Crusher');
            }
            
            debugging("User {$userid} passed quiz {$quiz->id} with {$percentage}% and received XP", DEBUG_DEVELOPER);
        } else {
            debugging("User {$userid} failed quiz {$quiz->id} with {$percentage}% (needed {$passingpercentage}%)", DEBUG_DEVELOPER);
        }
    }

    /**
     * Get the actual passing percentage for a quiz based on Moodle's "Grade to pass" setting
     */
    private static function get_quiz_passing_percentage(\stdClass $quiz): float {
        // If gradepass is not set or is 0, use default 60%
        if (empty($quiz->gradepass) || $quiz->gradepass == 0) {
            return 60.0;
        }

        // If quiz grade is 0, we can't calculate percentage
        if (empty($quiz->grade) || $quiz->grade == 0) {
            return 60.0;
        }

        // Calculate passing percentage: (gradepass / max_grade) * 100
        $passingpercentage = ($quiz->gradepass / $quiz->grade) * 100;
        
        // Ensure it's a reasonable value (between 0 and 100)
        if ($passingpercentage < 0 || $passingpercentage > 100) {
            debugging("Invalid passing percentage calculated: {$passingpercentage}% for quiz {$quiz->id}, using default 60%", DEBUG_DEVELOPER);
            return 60.0;
        }

        debugging("Quiz {$quiz->id}: Grade to pass = {$quiz->gradepass} / {$quiz->grade} = {$passingpercentage}%", DEBUG_DEVELOPER);
        return (float)$passingpercentage;
    }

    /**
     * Get the difficulty multiplier for a quiz
     */
    private static function get_quiz_difficulty_multiplier(int $quizid): float {
        global $DB;
        
        $quizmultipliers = [
            'Easy'   => 1.0,
            'Medium' => 1.5,
            'Hard'   => 2.0,
        ];
        
        // Get the difficulty from your database table
        $difficulty = $DB->get_field('block_gamif_quizdiff', 'difficulty', ['quizid' => $quizid]);
        
        // Default to Easy if no difficulty is set
        if (!$difficulty || !isset($quizmultipliers[$difficulty])) {
            $difficulty = 'Easy';
        }
        
        return $quizmultipliers[$difficulty];
    }

    /**
     * Forum post created → award XP and badge.
     */
    public static function forum_post_created(\mod_forum\event\post_created $event): void {
        global $DB;

        $userid = $event->userid;

        // XP Award 
        if (get_config('block_gamification', 'enable_forumpost')) {
            $points = (int)(get_config('block_gamification', 'xp_forumpost') ?? 5);
            self::award_xp_and_notify($userid, $points, get_string('reason_forum_post', 'block_gamification'));
        }
        // Badge Award
        $count = $DB->count_records('forum_posts', ['userid' => $userid]);
        if ($count >= 1) {
            $badges = new badge_manager();
            $badges->award_badge_if_new($userid, 'First_Forum_Post');
        }
    }

    // Forum discussion created.
    public static function forum_discussion_created(\mod_forum\event\discussion_created $event): void {
        global $DB;

        $userid = $event->userid;

        // XP Award
        if (!get_config('block_gamification', 'enable_forumdiscussion')) return;
        $points = (int)(get_config('block_gamification', 'xp_forumdiscussion') ?? 10);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_forum_discussion', 'block_gamification'));
        // Badge Award 
        $count = $DB->count_records('forum_posts', ['userid' => $userid]);
        if ($count >= 1) {
            $badges = new badge_manager();
            $badges->award_badge_if_new($userid, 'First_Forum_Post');
        }
    }

    
    // Profile updated (check if picture changed).
    public static function profile_updated(\core\event\user_updated $event): void {
        global $DB;
        
        if (!get_config('block_gamification', 'enable_profilepic')) return;
        
        $userid = $event->userid;
        $user = \core_user::get_user($userid);
        
        // Check if user has a profile picture
        if (!empty($user->picture)) {
            // Get the last time profile picture XP was awarded for this user
            $last_pic_xp = get_user_preferences('block_gamification_last_profilepic_xp', 0, $userid);
            
            // Check if 60 days have passed since last award
            $current_time = time();
            $sixty_days_in_seconds = 60 * 24 * 60 * 60; // 60 days in seconds
            
            if (($current_time - $last_pic_xp) >= $sixty_days_in_seconds) {
                $points = (int)(get_config('block_gamification', 'xp_profilepic') ?? 15);
                self::award_xp_and_notify($userid, $points, get_string('reason_profilepic', 'block_gamification'));
                
                // Update the last award timestamp
                set_user_preference('block_gamification_last_profilepic_xp', $current_time, $userid);
            }
        }
    }
    
    // Assignment submitted.
    public static function assignment_submitted(\mod_assign\event\assessable_submitted $event): void {
        if (!get_config('block_gamification', 'enable_assignment')) return;
        $points = (int)(get_config('block_gamification', 'xp_assignment') ?? 20);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_assignment', 'block_gamification'));
    }

    // Lesson completed.
    public static function lesson_completed(\mod_lesson\event\lesson_completed $event): void {
        if (!get_config('block_gamification', 'enable_lesson')) return;
        $points = (int)(get_config('block_gamification', 'xp_lesson') ?? 25);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_lesson', 'block_gamification'));
    }
    
    // Resource viewed.
    public static function resource_viewed(\mod_resource\event\course_module_viewed $event): void {
        if (isguestuser($event->relateduserid)) {
            return;
        }
        if (!get_config('block_gamification', 'enable_resourceview')) return;
        $points = (int)(get_config('block_gamification', 'xp_resourceview') ?? 2);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_resourceview', 'block_gamification'));
    }

    // Feedback submitted.
    public static function feedback_submitted(\mod_feedback\event\response_submitted $event): void {
        if (!get_config('block_gamification', 'enable_feedback')) return;
        $points = (int)(get_config('block_gamification', 'xp_feedback') ?? 12);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_feedback', 'block_gamification'));
    }

    // Choice answered.
    public static function choice_answered(\mod_choice\event\answer_submitted $event): void {
        if (!get_config('block_gamification', 'enable_choice')) return;
        $points = (int)(get_config('block_gamification', 'xp_choice') ?? 8);
        self::award_xp_and_notify($event->userid, $points, get_string('reason_choice', 'block_gamification'));
    }

    public static function activity_added(\core\event\course_module_created $event): void {
        $userid = $event->userid;
        if (!get_config('block_gamification', 'enable_activityadded')) return;
        $points = (int)(get_config('block_gamification', 'xp_activityadded') ?? 12);
        self::award_xp_and_notify($userid, $points, get_string('reason_activityadded', 'block_gamification'));
    }

    
}
