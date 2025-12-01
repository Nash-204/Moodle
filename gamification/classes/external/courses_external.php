<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/gradelib.php");
require_once("$CFG->dirroot/grade/querylib.php");

class block_gamification_external_courses extends external_api {

    public static function get_user_course_info_by_email_parameters() {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'User email address')
        ]);
    }

    public static function get_user_course_info_by_email($email) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_user_course_info_by_email_parameters(), ['email' => $email]);

        $user = $DB->get_record('user', ['email' => $params['email'], 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'email_hash' => self::hash_email($params['email']),
                'courses' => []
            ];
        }

        // Get enrolled courses
        $courses = enrol_get_users_courses($user->id, true);
        $courseData = [];

        foreach ($courses as $course) {
            // Calculate course progress
            $progress = self::calculate_course_progress($course->id, $user->id);
            
            // Get completed activities with scores
            $completedActivities = self::get_completed_activities_with_scores($course->id, $user->id);
            
            // Last access info
            $lastAccess = $DB->get_record('user_lastaccess', [
                'userid' => $user->id, 
                'courseid' => $course->id
            ]);

            $courseData[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'lastaccess' => $lastAccess ? $lastAccess->timeaccess : null,
                'progress' => $progress,
                'completed_activities' => $completedActivities
            ];
        }

        return [
            'success' => true,
            'email_hash' => self::hash_email($user->email),
            'userid' => $user->id,
            'fullname' => fullname($user),
            'total_courses' => count($courseData),
            'courses' => $courseData
        ];
    }

    /**
     * Calculate course progress percentage
     */
    private static function calculate_course_progress($courseid, $userid) {
        global $DB;
        
        // Get total activities in course
        $totalActivities = $DB->count_records_sql("
            SELECT COUNT(cm.id)
            FROM {course_modules} cm
            WHERE cm.course = ? AND cm.deletioninprogress = 0
        ", [$courseid]);
        
        if ($totalActivities == 0) {
            return 0;
        }
        
        // Get completed activities
        $completedActivities = $DB->count_records_sql("
            SELECT COUNT(cmc.id)
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
            WHERE cm.course = ? AND cmc.userid = ? AND cmc.completionstate > 0
        ", [$courseid, $userid]);
        
        return round(($completedActivities / $totalActivities) * 100);
    }

    /**
     * Get completed activities with their scores
     */
    private static function get_completed_activities_with_scores($courseid, $userid) {
        global $DB;
        
        $completedActivities = [];
        
        // Get completed course modules
        $completions = $DB->get_records_sql("
            SELECT cmc.*, cm.module, cm.instance, m.name as modulename
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.course = ? AND cmc.userid = ? AND cmc.completionstate > 0
            ORDER BY cmc.timemodified DESC
        ", [$courseid, $userid]);
        
        foreach ($completions as $completion) {
            $activityName = self::get_activity_name($completion->modulename, $completion->instance);
            $score = self::get_activity_score($completion->modulename, $completion->instance, $userid, $courseid);
            
            $completedActivities[] = [
                'name' => $activityName,
                'type' => $completion->modulename,
                'score' => $score['grade'],
                'max_score' => $score['maxgrade'],
                'percentage' => $score['percentage'],
                'completed_date' => $completion->timemodified
            ];
        }
        
        return $completedActivities;
    }

    /**
     * Get activity name
     */
    private static function get_activity_name($modulename, $instance) {
        global $DB;
        
        $tableName = $modulename;
        $activity = $DB->get_record($tableName, ['id' => $instance]);
        
        if ($activity) {
            return $activity->name ?? 'Unknown Activity';
        }
        
        return 'Unknown Activity';
    }

    /**
     * Get activity score
     */
    private static function get_activity_score($modulename, $instance, $userid, $courseid) {
        global $DB;
        
        $grade = null;
        $maxgrade = null;
        $percentage = null;
        
        // Try to get grade from gradebook
        $gradeItem = $DB->get_record_sql("
            SELECT gg.finalgrade, gi.grademax
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE gi.courseid = ? 
            AND gi.itemmodule = ?
            AND gi.iteminstance = ?
            AND gg.userid = ?
            AND gg.finalgrade IS NOT NULL
        ", [$courseid, $modulename, $instance, $userid]);
        
        if ($gradeItem) {
            $grade = (float)$gradeItem->finalgrade;
            $maxgrade = (float)$gradeItem->grademax;
            if ($maxgrade > 0) {
                $percentage = round(($grade / $maxgrade) * 100, 2);
            }
        }
        
        return [
            'grade' => $grade,
            'maxgrade' => $maxgrade,
            'percentage' => $percentage
        ];
    }

    /**
     * Helper method to hash email for security
     */
    private static function hash_email($email) {
        global $CFG;
        // Use a secure hashing algorithm with a site-specific salt
        $salt = !empty($CFG->siteidentifier) ? $CFG->siteidentifier : 'moodle_default_salt';
        
        // Hash the email with the salt for additional security
        return hash('sha256', $email . $salt);
    }

    public static function get_user_course_info_by_email_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'message' => new external_value(PARAM_TEXT, 'Response message', VALUE_OPTIONAL),
            'email_hash' => new external_value(PARAM_RAW, 'Hashed email address for security'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'User full name', VALUE_OPTIONAL),
            'total_courses' => new external_value(PARAM_INT, 'Total number of enrolled courses'),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'lastaccess' => new external_value(PARAM_INT, 'Last time user accessed course', VALUE_OPTIONAL),
                    'progress' => new external_value(PARAM_INT, 'Course progress percentage'),
                    'completed_activities' => new external_multiple_structure(
                        new external_single_structure([
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'type' => new external_value(PARAM_TEXT, 'Activity type'),
                            'score' => new external_value(PARAM_FLOAT, 'Score obtained', VALUE_OPTIONAL),
                            'max_score' => new external_value(PARAM_FLOAT, 'Maximum possible score', VALUE_OPTIONAL),
                            'percentage' => new external_value(PARAM_FLOAT, 'Percentage score', VALUE_OPTIONAL),
                            'completed_date' => new external_value(PARAM_INT, 'Timestamp when completed'),
                        ])
                    )
                ])
            )
        ]);
    }
}