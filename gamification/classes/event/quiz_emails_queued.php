<?php
namespace block_gamification\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz emails queued event for Gamification block
 *
 * @package   block_gamification
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_emails_queued extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'quiz';
    }

    public static function get_name() {
        return get_string('eventquizemailsqueued', 'block_gamification');
    }

    public function get_description() {
        $batches = $this->other['batches_created'];
        $subject = $this->other['subject'];
        $quizname = $this->other['quizname'];
        return "Quiz invitation emails for '{$quizname}' queued for background processing. Created {$batches} batch(es) with subject: {$subject}";
    }

    public function get_url() {
        return new \moodle_url('/mod/quiz/view.php', array('id' => $this->objectid));
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = $this->courseid;
        $logdata[] = 'gamification';
        $logdata[] = 'quiz emails queued';
        $logdata[] = 'mod/quiz/view.php?id=' . $this->objectid;
        $logdata[] = 'Queued ' . $this->other['batches_created'] . ' email batches for quiz: ' . $this->other['quizname'];
        $logdata[] = $this->contextinstanceid;
        return $logdata;
    }
}