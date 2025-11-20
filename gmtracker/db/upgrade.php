<?php
/**
 * GMTracker upgrade file
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute gmtracker upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_gmtracker_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade runs on first install
    if ($oldversion < 1) {
        // Initial installation - add any initial setup if needed
    }

    // Add incomplete field to gmtracker_attendance table.
    if ($oldversion < 2025101703) {
        
        // Define table gmtracker_attendance to be modified.
        $table = new xmldb_table('gmtracker_attendance');
        
        // Only proceed if the table exists (for safety)
        if ($dbman->table_exists($table)) {
            // Define field incomplete to be added to gmtracker_attendance.
            $field = new xmldb_field('incomplete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'duration');
            
            // Conditionally launch add field incomplete.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        
        // GMTracker savepoint reached.
        upgrade_mod_savepoint(true, 2025101703, 'gmtracker');
    }

    return true;
}