<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Award XP and notify the user.
     */
    private static function award_xp_and_notify(int $userid, int $points, string $reason) {
        global $USER;

        if ($points <= 0) {
            return;
        }

        $manager = new leaderboard_manager();
        $manager->add_xp($userid, $points);

        // Moodle notification (for current user).
        if ($USER->id === $userid) {
            \core\notification::success("🎉 You earned {$points} XP for {$reason}!");
        }

        // Send Moodle message (stored in notification drawer).
        $eventdata = new \core\message\message();
        $eventdata->component         = 'block_gamification';
        $eventdata->name              = 'xpnotification';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $userid;
        $eventdata->subject           = "XP Earned!";
        $eventdata->fullmessage       = "You earned {$points} XP for {$reason}. Keep it up!";
        $eventdata->fullmessageformat = FORMAT_MARKDOWN;
        $eventdata->fullmessagehtml   = "<p>🎉 You earned <strong>{$points} XP</strong> for {$reason}.</p>";
        $eventdata->smallmessage      = "Earned {$points} XP for {$reason}";
        $eventdata->notification      = 1;
        message_send($eventdata);

        // 🎉 Store a one-time toast notification (for frontend).
        set_user_preference('block_gamification_toast', "🎉 You earned {$points} XP for {$reason}!", $userid);
    }


    // ===============================
    // Event handlers
    // ===============================

    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        $points = get_config('block_gamification', 'xp_quizpass') ?? 50;
        self::award_xp_and_notify($event->userid, $points, 'submitting a quiz');
    }

    public static function course_completed(\core\event\course_completed $event) {
        $points = get_config('block_gamification', 'xp_coursecompleted') ?? 100;
        self::award_xp_and_notify($event->relateduserid, $points, 'completing a course');
    }

    public static function user_loggedin(\core\event\user_loggedin $event) {
        $userid = $event->userid;
        $today = date('Y-m-d');

        $lastlog = get_user_preferences('block_gamification_lastlogin', '', $userid);
        if ($lastlog !== $today) {
            $points = get_config('block_gamification', 'xp_dailylogin') ?? 5;
            self::award_xp_and_notify($userid, $points, 'your daily login');
            set_user_preference('block_gamification_lastlogin', $today, $userid);
        }
    }

    public static function forum_discussion_created(\mod_forum\event\discussion_created $event) {
        $points = get_config('block_gamification', 'xp_forumdiscussion') ?? 10;
        self::award_xp_and_notify($event->userid, $points, 'starting a forum discussion');
    }

    public static function forum_post_created(\mod_forum\event\post_created $event) {
        $points = get_config('block_gamification', 'xp_forumpost') ?? 5;
        self::award_xp_and_notify($event->userid, $points, 'posting in a forum');
    }

    public static function assignment_submitted(\mod_assign\event\assessable_submitted $event) {
        $points = get_config('block_gamification', 'xp_assignment') ?? 20;
        self::award_xp_and_notify($event->userid, $points, 'submitting an assignment');
    }

    public static function profile_updated(\core\event\user_updated $event) {
        $user = \core_user::get_user($event->userid);
        if (!empty($user->picture)) {
            $points = get_config('block_gamification', 'xp_profilepic') ?? 15;
            self::award_xp_and_notify($event->userid, $points, 'updating your profile picture');
        }
    }

    public static function lesson_completed(\mod_lesson\event\lesson_completed $event) {
        $points = get_config('block_gamification', 'xp_lesson') ?? 25;
        self::award_xp_and_notify($event->userid, $points, 'completing a lesson');
    }

    public static function workshop_assessed(\mod_workshop\event\assessment_evaluated $event) {
        $points = get_config('block_gamification', 'xp_peerassessment') ?? 20;
        self::award_xp_and_notify($event->userid, $points, 'peer assessment in a workshop');
    }

    public static function glossary_entry_created(\mod_glossary\event\entry_created $event) {
        $points = get_config('block_gamification', 'xp_glossary') ?? 10;
        self::award_xp_and_notify($event->userid, $points, 'adding a glossary entry');
    }

    public static function data_record_created(\mod_data\event\record_created $event) {
        $points = get_config('block_gamification', 'xp_database') ?? 10;
        self::award_xp_and_notify($event->userid, $points, 'adding a database record');
    }

    public static function wiki_page_created(\mod_wiki\event\page_created $event) {
        $points = get_config('block_gamification', 'xp_wiki') ?? 15;
        self::award_xp_and_notify($event->userid, $points, 'creating a wiki page');
    }

    public static function wiki_page_updated(\mod_wiki\event\page_updated $event) {
        $points = get_config('block_gamification', 'xp_wikiupdate') ?? 8;
        self::award_xp_and_notify($event->userid, $points, 'updating a wiki page');
    }
}
