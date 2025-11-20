<?php
/**
 * Quiz invite failed event
 *
 * @package    block_gamification
 */

namespace block_gamification\event;

defined('MOODLE_INTERNAL') || die();

class quiz_invite_failed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'quiz';
    }
    
    public static function get_name() {
        return get_string('eventquizinvitefailed', 'block_gamification');
    }
    
    public function get_description() {
        return "The user with id '{$this->userid}' failed to send quiz invitation for '{$this->other['quizname']}' to user with id '{$this->relateduserid}': {$this->other['error']}";
    }
}