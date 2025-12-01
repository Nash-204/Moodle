<?php
/**
 * Task started event for GMTracker
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Task started event class
 */
class task_started extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker_email_queue';
    }

    public static function get_name() {
        return get_string('eventtaskstarted', 'mod_gmtracker');
    }

    public function get_description() {
        $taskname = $this->other['taskname'];
        return "GMTracker email queue processing task '$taskname' started.";
    }

    public function get_url() {
        return new \moodle_url('/admin/tasklogs.php');
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'gmtracker';
        $logdata[] = 'task started';
        $logdata[] = 'admin/tasklogs.php';
        $logdata[] = $this->other['taskname'] . ' started';
        return $logdata;
    }
}