<?php
namespace block_gamification\event;
defined('MOODLE_INTERNAL') || die();

class quiz_email_sent extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'block_gamif_quiz_queue';
    }

    public static function get_name() {
        return get_string('eventquizemailsent', 'block_gamification');
    }

    public function get_description() {
        global $DB;
        
        $email = $this->other['recipient_email'];
        $quizid = $this->other['quizid'];

        $quiz = $DB->get_record('quiz', ['id' => $quizid], 'name', MUST_EXIST);
        $quizname = format_string($quiz->name);
        
        return "Quiz invitation email sent to {$email} for quiz: {$quizname}";
    }
}