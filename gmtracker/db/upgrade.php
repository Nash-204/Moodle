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

    // Add incomplete field to gmtracker_attendance table.
    if ($oldversion < 2025101703) {
        
        // Define table gmtracker_attendance to be modified.
        $table = new xmldb_table('gmtracker_attendance');
        
        // Define field incomplete to be added to gmtracker_attendance.
        $field = new xmldb_field('incomplete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'duration');
        
        // Conditionally launch add field incomplete.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // GMTracker savepoint reached.
        upgrade_mod_savepoint(true, 2025101703, 'gmtracker');
    }

    // Add placeholder version to maintain sequence
    if ($oldversion < 2025101704) {
        // Any minor changes can go here, or just update version
        upgrade_mod_savepoint(true, 2025101704, 'gmtracker');
    }

    if ($oldversion < 2025101705) {

        // Define table gmtracker_email_queue to be created.
        $table = new xmldb_table('gmtracker_email_queue');

        // Adding fields to table gmtracker_email_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('gmtrackerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('messagetext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('messagehtml', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('batch_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('batch_number', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('processed_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('failed_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table gmtracker_email_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('gmtrackerfk', XMLDB_KEY_FOREIGN, ['gmtrackerid'], 'gmtracker', ['id']);

        // Conditionally launch create table for gmtracker_email_queue.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Note: Indexes are now defined in install.xml, so we don't need to create them here
        // The indexes (status_idx, timecreated_idx, courseid_idx) are in the XML file

        // Add timecreated field to gmtracker table
        $table_gmtracker = new xmldb_table('gmtracker');
        $field_timecreated = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field timecreated
        if (!$dbman->field_exists($table_gmtracker, $field_timecreated)) {
            $dbman->add_field($table_gmtracker, $field_timecreated);
            
            // Set timecreated values to timemodified for existing records with fallback
            $DB->execute("UPDATE {gmtracker} SET timecreated = COALESCE(timemodified, ?) WHERE timecreated IS NULL", [time()]);
        }

        // GMTracker savepoint reached.
        upgrade_mod_savepoint(true, 2025101705, 'gmtracker');
    }

    return true;
}