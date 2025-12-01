<?php
/**
 * Export all leaderboards to CSV.
 *
 * @package    block_gamification
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('block/gamification:export', $context);

$manager = new \block_gamification\leaderboard_manager();

/**
 * Format percentage for display
 */
function gamification_export_format_percentage($value) {
    return round($value, 2) . '%';
}

/**
 * Export comprehensive leaderboard report
 */
function gamification_export_comprehensive_report($manager, $current_context) {
    global $DB, $CFG;
    
    $filename = clean_filename("gamification_leaderboards_" . userdate(time(), '%Y%m%d-%H%M'));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); 
    
    // Report header
    fwrite($output, "GAMIFICATION LEADERBOARDS - COMPREHENSIVE REPORT\n\n");
    fwrite($output, "Generated," . userdate(time(), '%Y-%m-%d %H:%M:%S') . "\n");
    fwrite($output, "Report Type,All Leaderboards with Detailed Analytics\n");
    fwrite($output, "Context,System Wide\n\n");
    
    // Get all data - ALL USERS
    $yearly_leaderboard = $manager->get_yearly_leaderboard(0);
    $monthly_leaderboard = $manager->get_monthly_leaderboard(0);
    $realtime_leaderboard = $manager->get_leaderboard_all_users(0);
    
    $user_quiz_data = get_user_quiz_data_all_users();
    $user_course_completions = get_user_course_completions_all_users();
    
    // ===== YEARLY LEADERBOARD =====
    fwrite($output, "YEARLY LEADERBOARD\n");
    fwrite($output, "Rank,Full Name,Group,XP\n");
    
    $ranknum = 1;
    foreach ($yearly_leaderboard as $user) {
        fputcsv($output, [
            $ranknum,
            fullname($user),
            $user->groups ?? 'No Group',
            $user->xp
        ]);
        $ranknum++;
    }
    
    fwrite($output, "\n" . str_repeat("-", 60) . "\n\n");
    
    // ===== MONTHLY LEADERBOARD =====
    fwrite($output, "MONTHLY LEADERBOARD\n");
    fwrite($output, "Rank,Full Name,Group,XP\n");
    
    $ranknum = 1;
    foreach ($monthly_leaderboard as $user) {
        fputcsv($output, [
            $ranknum,
            fullname($user),
            $user->groups ?? 'No Group',
            $user->xp
        ]);
        $ranknum++;
    }
    
    fwrite($output, "\n" . str_repeat("-", 60) . "\n\n");
    
    // ===== REAL-TIME LEADERBOARD =====
    fwrite($output, "REAL-TIME LEADERBOARD WITH PERFORMANCE ANALYTICS\n");
    fwrite($output, "Rank,Full Name,Group,XP,Completed Courses,Quizzes Completed\n");
    
    $ranknum = 1;
    foreach ($realtime_leaderboard as $user) {
        $user_id = $user->id;
        
        // Completed courses
        $completed_courses = $user_course_completions[$user_id] ?? [];
        $completed_courses_count = count($completed_courses);
        
        // Completed quizzes (only finished ones)
        $user_quizzes = $user_quiz_data[$user_id] ?? [];
        $quizzes_completed = 0;
        foreach ($user_quizzes as $quiz) {
            if (!empty($quiz->timefinish)) {
                $quizzes_completed++;
            }
        }
        
        fputcsv($output, [
            $ranknum,
            fullname($user),
            $user->groups ?? 'No Group',
            $user->xp,
            $completed_courses_count,
            $quizzes_completed
        ]);
        $ranknum++;
    }
    
    fwrite($output, "\n" . str_repeat("-", 60) . "\n\n");
    
    // ===== DETAILED QUIZ PERFORMANCE =====
    fwrite($output, "DETAILED QUIZ PERFORMANCE BY USER\n\n");

    $all_user_ids_with_quizzes = array_keys($user_quiz_data);

    // Sort users alphabetically by name (case-insensitive)
    $sorted_users_quizzes = [];
    foreach ($all_user_ids_with_quizzes as $user_id) {
        $user = $DB->get_record('user', ['id' => $user_id]);
        if ($user && !$user->deleted && $user->id > 1) {
            $sorted_users_quizzes[$user_id] = fullname($user);
        }
    }

    // Case-insensitive sorting
    uasort($sorted_users_quizzes, function($a, $b) {
        return strcasecmp($a, $b);
    });

    foreach (array_keys($sorted_users_quizzes) as $user_id) {
        $user_quizzes = $user_quiz_data[$user_id];
        if (empty($user_quizzes)) {
            continue;
        }
        $user = $DB->get_record('user', ['id' => $user_id]);
        
        // Get user's group name
        $group_name = $user->groups ?? 'No Group';
        
        fwrite($output, "USER: " . fullname($user) . " (" . $group_name . ")\n");
        fwrite($output, "Quiz Name,Course,Score,Percentage,Completion Date\n");
        
        foreach ($user_quizzes as $quiz) {
            $percentage = $quiz->maxgrade > 0 ? round(($quiz->grade / $quiz->maxgrade) * 100, 2) : 0;
            
            // Format scores to remove unnecessary decimals
            $formatted_grade = (float)$quiz->grade == (int)$quiz->grade ? (int)$quiz->grade : round($quiz->grade, 1);
            $formatted_maxgrade = (float)$quiz->maxgrade == (int)$quiz->maxgrade ? (int)$quiz->maxgrade : round($quiz->maxgrade, 1);
            $score_display = $formatted_grade . '/' . $formatted_maxgrade;
            
            fputcsv($output, [
                $quiz->quizname,
                $quiz->coursename,
                $score_display,
                gamification_export_format_percentage($percentage),
                $quiz->timefinish ? userdate($quiz->timefinish, '%Y-%m-%d %H:%M:%S') : 'In progress'
            ]);
        }
        fwrite($output, "\n");
    }

    fwrite($output, str_repeat("-", 60) . "\n\n");

    // ===== COURSE COMPLETION DETAILS =====
    fwrite($output, "COURSE COMPLETION DETAILS BY USER\n\n");

    $all_user_ids_with_completions = array_keys($user_course_completions);

    // Sort users alphabetically by name (case-insensitive)
    $sorted_users_completions = [];
    foreach ($all_user_ids_with_completions as $user_id) {
        $user = $DB->get_record('user', ['id' => $user_id]);
        if ($user && !$user->deleted && $user->id > 1) {
            $sorted_users_completions[$user_id] = fullname($user);
        }
    }

    // Case-insensitive sorting
    uasort($sorted_users_completions, function($a, $b) {
        return strcasecmp($a, $b);
    });

    foreach (array_keys($sorted_users_completions) as $user_id) {
        $completed_courses = $user_course_completions[$user_id];
        if (empty($completed_courses)) {
            continue;
        }
        $user = $DB->get_record('user', ['id' => $user_id]);
        
        // Get user's group name
        $group_name = $user->groups ?? 'No Group';
        
        fwrite($output, "USER: " . fullname($user) . " (" . $group_name . ")\n");
        fwrite($output, "Course Name,Completion Date\n");
        
        foreach ($completed_courses as $course) {
            fputcsv($output, [
                $course['coursename'],
                $course['completion_date']
            ]);
        }
        fwrite($output, "\n");
    }
    
    fclose($output);
    exit;
}

/**
 * Export simplified leaderboard
 */
function gamification_export_simple_leaderboard($manager, $current_context) {
    $filename = clean_filename("gamification_simple_leaderboard_" . userdate(time(), '%Y%m%d-%H%M'));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fwrite($output, "GAMIFICATION - SIMPLE LEADERBOARD EXPORT\n\n");
    fwrite($output, "Generated," . userdate(time(), '%Y-%m-%d %H:%M:%S') . "\n");
    fwrite($output, "Report Type,Leaderboard Rankings Only\n\n");
    
    $leaderboard = $manager->get_leaderboard_all_users(0);
    
    fwrite($output, "LEADERBOARD RANKINGS\n");
    fwrite($output, "Rank,Full Name,Group,XP\n");
    
    $ranknum = 1;
    foreach ($leaderboard as $user) {
        fputcsv($output, [
            $ranknum,
            fullname($user),
            $user->groups ?? 'No Group',
            $user->xp
        ]);
        $ranknum++;
    }
    
    fclose($output);
    exit;
}

/**
 * Get quiz data for ALL users - ONLY LAST ATTEMPT PER QUIZ
 */
function get_user_quiz_data_all_users() {
    global $DB;
    
    $sql = "SELECT 
                qa.id as attemptid,
                qa.userid,
                q.name as quizname,
                q.id as quizid,
                c.fullname as coursename,
                qa.sumgrades as grade,
                q.grade as maxgrade,
                qa.timefinish
            FROM {quiz_attempts} qa
            JOIN {quiz} q ON q.id = qa.quiz
            JOIN {course} c ON c.id = q.course
            JOIN {user} u ON u.id = qa.userid
            WHERE qa.state = 'finished'
              AND qa.preview = 0
              AND u.deleted = 0
              AND u.suspended = 0
              AND u.id > 1
              AND qa.id = (
                  SELECT MAX(qa2.id) 
                  FROM {quiz_attempts} qa2 
                  WHERE qa2.quiz = qa.quiz 
                  AND qa2.userid = qa.userid 
                  AND qa2.state = 'finished'
              )
            ORDER BY qa.userid, qa.timefinish DESC";
    
    $attempts = $DB->get_records_sql($sql);
    $user_quiz_data = [];
    
    foreach ($attempts as $attempt) {
        $userid = $attempt->userid;
        if (!isset($user_quiz_data[$userid])) {
            $user_quiz_data[$userid] = [];
        }
        $user_quiz_data[$userid][] = $attempt;
    }
    return $user_quiz_data;
}

/**
 * Get course completions for ALL users
 */
function get_user_course_completions_all_users() {
    global $DB;
    
    $sql = "SELECT 
                cc.id as completionid,
                cc.userid,
                c.fullname as coursename,
                FROM_UNIXTIME(cc.timecompleted) as completion_date
            FROM {course_completions} cc
            JOIN {course} c ON c.id = cc.course
            JOIN {user} u ON u.id = cc.userid
            WHERE cc.timecompleted IS NOT NULL
              AND u.deleted = 0
              AND u.suspended = 0
              AND u.id > 1
            ORDER BY cc.userid, cc.timecompleted DESC";
    
    $completions = $DB->get_records_sql($sql);
    $user_completions = [];
    
    foreach ($completions as $completion) {
        $userid = $completion->userid;
        if (!isset($user_completions[$userid])) {
            $user_completions[$userid] = [];
        }
        $user_completions[$userid][] = [
            'coursename' => $completion->coursename,
            'completion_date' => $completion->completion_date
        ];
    }
    return $user_completions;
}

// Determine export type
$exporttype = optional_param('exporttype', 'comprehensive', PARAM_ALPHA);

switch ($exporttype) {
    case 'simple':
        gamification_export_simple_leaderboard($manager, $context);
        break;
    case 'comprehensive':
    default:
        gamification_export_comprehensive_report($manager, $context);
        break;
}