<?php
namespace block_gamification\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for processing Gamification quiz email queue
 */
class process_quiz_email_queue extends \core\task\scheduled_task {
    
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('taskprocessquizemailqueue', 'block_gamification');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $CFG, $DB;
        
        require_once($CFG->dirroot . '/blocks/gamification/lib.php');
        
        // Check if there are any pending emails before starting
        $pending_count = $DB->count_records('block_gamif_quiz_queue', ['status' => 'pending']);
        
        if ($pending_count === 0) {
            mtrace("No pending Gamification quiz emails to process.");
            return true;
        }
        
        // Log task start
        $this->log_task_start($pending_count);
        
        // Process email batches
        $processed = block_gamification_process_quiz_email_queue();
        
        // Clean up old queue records
        $cleaned = block_gamification_cleanup_quiz_email_queue();
        
        // Log task completion
        $this->log_task_complete($processed, $cleaned);
        
        return true;
    }
    
    /**
     * Log task start
     */
    private function log_task_start($pending_count) {
        \block_gamification\event\quiz_task_started::create([
            'context' => \context_system::instance(),
            'other' => [
                'taskname' => 'process_quiz_email_queue',
                'pending_batches' => $pending_count
            ]
        ])->trigger();
        
        mtrace("Gamification quiz email queue processing started at " . date('Y-m-d H:i:s'));
        mtrace("Found {$pending_count} pending email batch(es) to process.");
    }
    
    /**
     * Log task completion
     */
    private function log_task_complete($batches_processed, $records_cleaned) {
        \block_gamification\event\quiz_task_completed::create([
            'context' => \context_system::instance(),
            'other' => [
                'taskname' => 'process_quiz_email_queue',
                'batches_processed' => $batches_processed,
                'records_cleaned' => $records_cleaned
            ]
        ])->trigger();
        
        mtrace("Gamification quiz email queue processing completed.");
        mtrace("Processed: {$batches_processed} batch(es)");
        mtrace("Cleaned: {$records_cleaned} old record(s)");
    }
}