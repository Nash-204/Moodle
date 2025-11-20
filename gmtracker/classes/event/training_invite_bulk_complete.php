<?php
/**
 * Training invite bulk complete event
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

class training_invite_bulk_complete extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }
    
    public static function get_name() {
        return get_string('eventtraininginvitebulkcomplete', 'mod_gmtracker');
    }
    
    public function get_description() {
        $desc = "The user with id '{$this->userid}' completed sending training invitations for '{$this->other['gmtrackername']}': ";
        $desc .= "{$this->other['sentcount']} sent out of {$this->other['totalusers']} users";
        if ($this->other['failedcount'] > 0) {
            $desc .= " ({$this->other['failedcount']} failed)";
        }
        $desc .= ".";
        return $desc;
    }
}