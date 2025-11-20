<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_gamification_install() {
    global $DB;

    // Predefine badges
    $badges = [
        ['Leaderboard_Week',  'Weekly Leaderboard Champion', 'Top of weekly leaderboard.', 'pix/badges/Leaderboard_Week.png'],
        ['Leaderboard_Month', 'Monthly Leaderboard Champion', 'Top of monthly leaderboard.', 'pix/badges/Leaderboard_Month.png'],
        ['Leaderboard_Annual','Annual Leaderboard Champion', 'Top of annual leaderboard.', 'pix/badges/Leaderboard_Annual.png'],
        ['Leaderboard_Top10', 'Top 10 Leaderboard', 'Enter the top 10 leaderboard.', 'pix/badges/Leaderboard_Top10.png'],
        ['Daily_Streak',      'Daily Streak', 'Log in daily for a streak.', 'pix/badges/Daily_Streak.png'],
        ['Weekly_Streak',     'Weekly Streak', 'Log in each week for a month.', 'pix/badges/Weekly_Streak.png'],
        ['Quiz_Crusher',      'Quiz Crusher', 'Score perfectly on a quiz.', 'pix/badges/Quiz_Crusher.png'],
        ['First_Forum_Post',  'First Forum Post', 'Make your first forum post.', 'pix/badges/First_Forum_Post.png'],
        ['Course_Completer',  'Course Completer', 'Finish a course.', 'pix/badges/Course_Completer.png'],
        ['Course_Master',     'Course Master', 'Complete 5 courses.', 'pix/badges/Course_Master.png'],
        ['Badge_Collector',   'Badge Collector', 'Collect 5 gamification badges.', 'pix/badges/Badge_Collector.png'],
    ];

    // Insert badges if not exist
    foreach ($badges as $b) {
        list($code, $name, $desc, $image) = $b;
        if (!$DB->record_exists('block_gamif_badges_def', ['badgecode' => $code])) {
            $DB->insert_record('block_gamif_badges_def', (object)[
                'badgecode' => $code,
                'name' => $name,
                'description' => $desc,
                'image' => $image
            ]);
        }
    }
}
