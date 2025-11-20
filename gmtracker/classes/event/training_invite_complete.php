<?php
/**
 * Training invite complete event
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

class training_invite_complete extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }
    
    public static function get_name() {
        return get_string('eventtraininginvitecomplete', 'mod_gmtracker');
    }
    
    public function get_description() {
        $desc = "Training invitations for '{$this->other['gmtrackername']}' completed: ";
        $desc .= "{$this->other['sentcount']} sent out of {$this->other['totalusers']} users";
        if ($this->other['failedcount'] > 0) {
            $desc .= " ({$this->other['failedcount']} failed)";
        }
        $desc .= ".";
        return $desc;
    }
}