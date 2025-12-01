<?php
/**
 * Task definitions for GMTracker module
 *
 * @package   mod_gmtracker
 * @copyright 2025 Adrian Nash Semana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'mod_gmtracker\task\process_email_queue_scheduled',
        'blocking' => 0,
        'minute' => '*/2',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ]
];