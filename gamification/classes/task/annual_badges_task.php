<?php
namespace block_gamification\task;

defined('MOODLE_INTERNAL') || die();

class annual_badges_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('annualbadgestask', 'block_gamification');
    }

    public function execute() {
        global $DB;

        // Award yearly badge to the top 1.
        \block_gamification\leaderboard_manager::check_scheduled_badges('year');

        // Rebuild Yearly Leaderboard
        $DB->delete_records('block_gamif_yearly'); // clears old snapshot

        $sql = "SELECT u.id AS userid, COALESCE(x.xp, 0) AS xp
                  FROM {user} u
             LEFT JOIN {block_gamification} x ON x.userid = u.id
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND u.id > 1
             ORDER BY xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC
                 LIMIT 10";
        $records = $DB->get_records_sql($sql);

        foreach ($records as $r) {
            $DB->insert_record('block_gamif_yearly', (object)[
                'userid' => $r->userid,
                'xp'     => $r->xp,
                'timecreated' => time()
            ]);
        }
    }
}
