<?php
/**
 * Task completed event for GMTracker
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Task completed event class
 */
class task_completed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gmtracker_email_queue';
    }

    public static function get_name() {
        return get_string('eventtaskcompleted', 'mod_gmtracker');
    }

    public function get_description() {
        $taskname = $this->other['taskname'];
        $batches = $this->other['batches_processed'];
        $cleaned = $this->other['records_cleaned'];
        return "GMTracker email queue processing task '$taskname' completed. Processed: $batches batches, Cleaned: $cleaned records.";
    }

    public function get_url() {
        return new \moodle_url('/admin/tasklogs.php');
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'gmtracker';
        $logdata[] = 'task completed';
        $logdata[] = 'admin/tasklogs.php';
        $logdata[] = $this->other['taskname'] . ' completed - Processed: ' . $this->other['batches_processed'] . ' batches, Cleaned: ' . $this->other['records_cleaned'] . ' records';
        return $logdata;
    }
}