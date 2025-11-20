<?php
/**
 * Quiz invite bulk start event
 *
 * @package    block_gamification
 */

namespace block_gamification\event;

defined('MOODLE_INTERNAL') || die();

class quiz_invite_bulk_start extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'quiz';
    }
    
    public static function get_name() {
        return get_string('eventquizinvitebulkstart', 'block_gamification');
    }
    
    public function get_description() {
        return "The user with id '{$this->userid}' started sending quiz invitations for '{$this->other['quizname']}' to {$this->other['totalusers']} users.";
    }
}