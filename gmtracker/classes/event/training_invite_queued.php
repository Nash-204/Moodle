<?php
/**
 * Training invite queued event
 *
 * @package    mod_gmtracker
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

class training_invite_queued extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }
    
    public static function get_name() {
        return get_string('eventtraininginvitequeued', 'mod_gmtracker');
    }
    
    public function get_description() {
        return "Training invitations for '{$this->other['gmtrackername']}' queued for background processing to {$this->other['totalusers']} users.";
    }
}