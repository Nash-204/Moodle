<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for gamification block
 *
 * @param int $oldversion The version we are upgrading from
 * @return bool Result
 */
function xmldb_block_gamification_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101709) {

        // Define table block_gamif_quiz_queue to be created.
        $table = new xmldb_table('block_gamif_quiz_queue');

        // Adding fields to table block_gamif_quiz_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'quizid');
        $table->add_field('fromuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'courseid');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'fromuserid');
        $table->add_field('messagetext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'subject');
        $table->add_field('messagehtml', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'messagetext');
        $table->add_field('batch_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'messagehtml');
        $table->add_field('batch_number', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'batch_data');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending', 'batch_number');
        $table->add_field('processed_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0', 'status');
        $table->add_field('failed_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0', 'processed_count');
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null, 'failed_count');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'error_message');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timecreated');

        // Adding keys to table block_gamif_quiz_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('quizfk', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);

        // Adding indexes to table block_gamif_quiz_queue.
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for block_gamif_quiz_queue.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gamification savepoint reached.
        upgrade_block_savepoint(true, 2025101706, 'gamification');
    }

    return true;
}