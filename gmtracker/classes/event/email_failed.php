<?php
/**
 * Email failed event for GMTracker
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Email failed event class
 */
class email_failed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }

    public static function get_name() {
        return get_string('eventemailfailed', 'mod_gmtracker');
    }

    public function get_description() {
        $recipient = $this->other['recipient_email'];
        $subject = $this->other['subject'];
        $error = isset($this->other['error']) ? $this->other['error'] : 'Unknown error';
        return "Failed to send GMTracker email with subject '$subject' to '$recipient'. Error: $error";
    }

    public function get_url() {
        return new \moodle_url('/mod/gmtracker/view.php', array('g' => $this->objectid));
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = $this->courseid;
        $logdata[] = 'gmtracker';
        $logdata[] = 'email failed';
        $logdata[] = 'view.php?g=' . $this->objectid;
        $logdata[] = $this->other['subject'] . ' to ' . $this->other['recipient_email'] . ' - Error: ' . $this->other['error'];
        $logdata[] = $this->contextinstanceid;
        return $logdata;
    }
}