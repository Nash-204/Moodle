<?php
/**
 * Logging utility for gamification block
 *
 * @package    block_gamification
 */

namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

/**
 * Logger class for gamification events
 */
class logger {
    
    /**
     * Log bulk quiz invitation start
     */
    public static function log_quiz_invite_bulk_start($quiz, $course, $sentby, $totalusers) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'relateduserid' => $sentby,
            'other' => [
                'quizname' => $quiz->name,
                'totalusers' => $totalusers
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_bulk_start::create($params);
        $event->trigger();
    }
    
    /**
     * Log bulk quiz invitation completion
     */
    public static function log_quiz_invite_bulk_complete($quiz, $course, $sentby, $sentcount, $totalusers, $failedemails) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'relateduserid' => $sentby,
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
    
    /**
     * Log individual quiz invitation sent
     */
    public static function log_quiz_invite_sent($quiz, $course, $sentby, $recipientid) {
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
    
    /**
     * Log individual quiz invitation failure
     */
    public static function log_quiz_invite_failed($quiz, $course, $sentby, $recipientid, $error) {
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

    /**
     * Log quiz invitation queued for background processing
     */
    public static function log_quiz_invite_queued($quiz, $course, $sentby, $totalusers) {
        $context = \context_course::instance($course->id);
        $params = [
            'context' => $context,
            'objectid' => $quiz->id,
            'relateduserid' => $sentby,
            'other' => [
                'quizname' => $quiz->name,
                'totalusers' => $totalusers
            ]
        ];
        
        $event = \block_gamification\event\quiz_invite_queued::create($params);
        $event->trigger();
    }
}