<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

        $userid = $event->userid;
        $attemptid = $event->objectid;

        // Load the attempt record.
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
        $quiz    = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);

        // Calculate percentage score.
        if ($quiz->sumgrades > 0) {
            $percentage = ($attempt->sumgrades / $quiz->sumgrades) * 100;
            $points = round($percentage); // 1 XP per % score
        } else {
            $points = 0;
        }

        // Award XP.
        $xpmanager = new leaderboard_manager();
        $xpmanager->add_xp($userid, $points);
    }
}
