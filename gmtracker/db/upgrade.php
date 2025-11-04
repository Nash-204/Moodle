<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GMTracker upgrade file
 *
 * @package   mod_gmtracker
 * @copyright 2024 Your Name
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
    if ($oldversion < 2025101701) {
        
        // Define table gmtracker_attendance to be modified.
        $table = new xmldb_table('gmtracker_attendance');
        
        // Define field incomplete to be added to gmtracker_attendance.
        $field = new xmldb_field('incomplete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'duration');
        
        // Conditionally launch add field incomplete.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // GMTracker savepoint reached.
        upgrade_mod_savepoint(true, 2025101708, 'gmtracker');
    }

    return true;
}