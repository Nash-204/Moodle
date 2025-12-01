<?php
/**
 * Emails queued event for GMTracker
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Emails queued event class
 */
class emails_queued extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker';
    }

    public static function get_name() {
        return get_string('eventemailsqueued', 'mod_gmtracker');
    }

    public function get_description() {
        $batches = $this->other['batches_created'];
        $subject = $this->other['subject'];
        return "GMTracker emails queued for background processing. Created {$batches} batch(es) with subject: {$subject}";
    }

    public function get_url() {
        return new \moodle_url('/mod/gmtracker/view.php', array('g' => $this->objectid));
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = $this->courseid;
        $logdata[] = 'gmtracker';
        $logdata[] = 'emails queued';
        $logdata[] = 'view.php?g=' . $this->objectid;
        $logdata[] = 'Queued ' . $this->other['batches_created'] . ' email batches';
        $logdata[] = $this->contextinstanceid;
        return $logdata;
    }
}