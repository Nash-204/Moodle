<?php
/**
 * Quiz invite bulk complete event
 *
 * @package    block_gamification
 */

namespace block_gamification\event;

defined('MOODLE_INTERNAL') || die();

class quiz_invite_bulk_complete extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'quiz';
    }
    
    public static function get_name() {
        return get_string('eventquizinvitebulkcomplete', 'block_gamification');
    }
    
    public function get_description() {
        $desc = "The user with id '{$this->userid}' completed sending quiz invitations for '{$this->other['quizname']}': ";
        $desc .= "{$this->other['sentcount']} sent out of {$this->other['totalusers']} users";
        if ($this->other['failedcount'] > 0) {
            $desc .= " ({$this->other['failedcount']} failed)";
        }
        $desc .= ".";
        return $desc;
    }
}