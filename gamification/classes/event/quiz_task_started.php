<?php
namespace block_gamification\event;
defined('MOODLE_INTERNAL') || die();

class quiz_task_started extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventquiztaskstarted', 'block_gamification');
    }

    public function get_description() {
        return "Quiz email processing task started";
    }
}