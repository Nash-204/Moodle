<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

class leaderboard_manager {
    public function get_user_xp($userid) {
        global $DB;
        $record = $DB->get_record('block_gamification', ['userid' => $userid]);
        return $record ? $record->xp : 0;
    }

    public function add_xp($userid, $points) {
        global $DB;
        if ($record = $DB->get_record('block_gamification', ['userid' => $userid])) {
            $record->xp += $points;
            $DB->update_record('block_gamification', $record);
        } else {
            $DB->insert_record('block_gamification', ['userid' => $userid, 'xp' => $points]);
        }
    }

    public function get_leaderboard($limit = 50) {
        global $DB;

        $sql = "
            SELECT u.id, u.firstname, u.lastname, COALESCE(x.xp, 0) AS xp
            FROM {user} u
            LEFT JOIN {block_gamification} x ON u.id = x.userid
            WHERE u.deleted = 0
            ORDER BY xp DESC
            LIMIT {$limit}
        ";

        return $DB->get_records_sql($sql);
    }

}
