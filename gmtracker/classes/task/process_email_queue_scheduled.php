<?php
/**
 * Scheduled task for processing GMTracker email queue
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gmtracker\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to process email queue
 */
class process_email_queue_scheduled extends \core\task\scheduled_task {
    
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('taskprocessemailqueue', 'mod_gmtracker');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $CFG, $DB;
        
        require_once($CFG->dirroot . '/mod/gmtracker/lib.php');
        
        // Check if there are any pending emails before starting
        $pending_count = $DB->count_records('gmtracker_email_queue', ['status' => 'pending']);
        
        if ($pending_count === 0) {
            mtrace("No pending GMTracker emails to process.");
            return true;
        }
        
        // Log task start
        $this->log_task_start($pending_count);
        
        // Process email batches
        $processed = gmtracker_process_email_queue();
        
        // Clean up old queue records
        $cleaned = gmtracker_cleanup_email_queue();
        
        // Log task completion
        $this->log_task_complete($processed, $cleaned);
        
        return true;
    }
    
    /**
     * Log task start
     */
    private function log_task_start($pending_count) {
        $event = \mod_gmtracker\event\task_started::create([
            'context' => \context_system::instance(),
            'other' => [
                'taskname' => 'process_email_queue_scheduled',
                'pending_batches' => $pending_count
            ]
        ]);
        $event->trigger();
        
        mtrace("GMTracker email queue processing started at " . date('Y-m-d H:i:s'));
        mtrace("Found {$pending_count} pending email batch(es) to process.");
    }
    
    /**
     * Log task completion
     */
    private function log_task_complete($batches_processed, $records_cleaned) {
        $event = \mod_gmtracker\event\task_completed::create([
            'context' => \context_system::instance(),
            'other' => [
                'taskname' => 'process_email_queue_scheduled',
                'batches_processed' => $batches_processed,
                'records_cleaned' => $records_cleaned
            ]
        ]);
        $event->trigger();
        
        mtrace("GMTracker email queue processing completed.");
        mtrace("Processed: {$batches_processed} batch(es)");
        mtrace("Cleaned: {$records_cleaned} old record(s)");
    }
}