<?php
namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user leaves a Google Meet meeting.
 *
 * @package   mod_gmtracker
 */
class meeting_left extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // read-like event
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'gmtracker';
    }

    public static function get_name() {
        return get_string('event_meeting_left', 'mod_gmtracker');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' left the meeting (gmtracker instance id '{$this->objectid}') after attending for {$this->other['userduration']} seconds.";
    }

    public function get_url() {
        return new \moodle_url('/mod/gmtracker/view.php', ['id' => $this->contextinstanceid]);
    }

    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['userduration'])) {
            throw new \coding_exception('Missing required "userduration" value in event.');
        }
        if (!isset($this->other['meetingduration'])) {
            throw new \coding_exception('Missing required "meetingduration" value in event.');
        }
    }
}
