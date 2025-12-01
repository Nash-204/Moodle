<?php
namespace block_gamification\event;
defined('MOODLE_INTERNAL') || die();

class quiz_task_completed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventquiztaskcompleted', 'block_gamification');
    }

    public function get_description() {
        $batches = $this->other['batches_processed'];
        $emails = $this->other['emails_sent'];
        $duration = $this->other['duration_seconds'];
        return "Quiz email processing completed: {$batches} batches, {$emails} emails sent, duration: {$duration}s";
    }
}