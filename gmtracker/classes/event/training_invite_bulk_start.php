<?php
/**
 * Training invite bulk start event
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

class training_invite_bulk_start extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }
    
    public static function get_name() {
        return get_string('eventtraininginvitebulkstart', 'mod_gmtracker');
    }
    
    public function get_description() {
        return "The user with id '{$this->userid}' started sending training invitations for '{$this->other['gmtrackername']}' to {$this->other['totalusers']} users.";
    }
}