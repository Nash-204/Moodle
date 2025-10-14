<?php
/**
 * Export leaderboard to CSV.
 *
 * @package    block_gamification
 * 
 **/
require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('block/gamification:export', $context);

// Dynamic filename with date
$filename = 'leaderboard-' . date('Y-m-d_H-i-s') . '.csv';

// Manager
$manager = new \block_gamification\leaderboard_manager();
$leaderboard = $manager->get_leaderboard(0); // 0 = all users

// Send headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output
$output = fopen('php://output', 'w');

// Add export date as first line
fputcsv($output, ['Export date: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // empty row for readability

// Header row
fputcsv($output, ['Rank', 'User', 'XP']);

// Rows
$ranknum = 1;
foreach ($leaderboard as $u) {
    fputcsv($output, [$ranknum, fullname($u), $u->xp]);
    $ranknum++;
}

fclose($output);
exit;
