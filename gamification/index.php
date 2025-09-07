<?php
require('../../config.php');
require_login();

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'export') {
    global $DB;

    // Pull everyone with XP (null -> 0), sorted by XP desc. No LIMIT for CSV.
    $records = $DB->get_records_sql("
        SELECT u.id, u.firstname, u.lastname, COALESCE(x.xp, 0) AS xp
        FROM {user} u
        LEFT JOIN {block_gamification} x ON u.id = x.userid
        WHERE u.deleted = 0
        ORDER BY xp DESC
    ");

    // Headers (ensure no output has been sent before these headers!)
    $filename = "leaderboard-" . date('Ymd-His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");
    header('Pragma: public');
    header('Cache-Control: max-age=0');

    $out = fopen('php://output', 'w');
    // Optional BOM for Excel friendliness:
    // fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['Rank', 'Firstname', 'Lastname', 'XP']);

    $rank = 1;
    foreach ($records as $r) {
        fputcsv($out, [$rank, $r->firstname, $r->lastname, (int)$r->xp]);
        $rank++;
    }

    fclose($out);
    exit;
}
