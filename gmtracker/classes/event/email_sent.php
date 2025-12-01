<?php
/**
 * Email sent event for GMTracker
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Email sent event class
 */
class email_sent extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }

    public static function get_name() {
        return get_string('eventemailsent', 'mod_gmtracker');
    }

    public function get_description() {
        $recipient = $this->other['recipient_email'];
        $subject = $this->other['subject'];
        return "The user with id '$this->userid' sent a GMTracker email with subject '$subject' to '$recipient'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/gmtracker/view.php', array('g' => $this->objectid));
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = $this->courseid;
        $logdata[] = 'gmtracker';
        $logdata[] = 'email sent';
        $logdata[] = 'view.php?g=' . $this->objectid;
        $logdata[] = $this->other['subject'] . ' to ' . $this->other['recipient_email'];
        $logdata[] = $this->contextinstanceid;
        return $logdata;
    }
}