<?php
/**
 * Training invite failed event
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

class training_invite_failed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }
    
    public static function get_name() {
        return get_string('eventtraininginvitefailed', 'mod_gmtracker');
    }
    
    public function get_description() {
        return "The user with id '{$this->userid}' failed to send training invitation for '{$this->other['gmtrackername']}' to user with id '{$this->relateduserid}': {$this->other['error']}";
    }
}