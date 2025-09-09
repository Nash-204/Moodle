<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

class leaderboard_manager {

    public function get_user_xp($userid) {
        global $DB;
        $record = $DB->get_record('block_gamification', ['userid' => $userid]);
        return $record ? (int)$record->xp : 0;
    }

    public function add_xp($userid, $points) {
        global $DB;

        if ($record = $DB->get_record('block_gamification', ['userid' => $userid])) {
            $record->xp = (int)$record->xp + (int)$points;

            // Prevent negative XP
            if ($record->xp < 0) {
                $record->xp = 0;
            }

            $DB->update_record('block_gamification', $record);
        } else {
            $xp = (int)$points;
            if ($xp < 0) {
                $xp = 0; // prevent new users starting negative
            }

            $DB->insert_record('block_gamification', [
                'userid' => $userid,
                'xp' => $xp
            ]);
        }
    }

    public function get_leaderboard($limit = 50) {
        global $DB;

        $sql = "
            SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt, u.email,
                   COALESCE(x.xp, 0) AS xp
              FROM {user} u
         LEFT JOIN {block_gamification} x ON x.userid = u.id
             WHERE u.deleted = 0
          ORDER BY xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC
        ";

        if ($limit > 0) {
            return $DB->get_records_sql($sql, [], 0, (int)$limit);
        } else {
            return $DB->get_records_sql($sql);
        }
    }

    /**
     * Get the exact same global rank as the leaderboard table.
     */
    public function get_user_rank($userid): int {
        global $DB;

        $sql = "
            SELECT u.id, COALESCE(x.xp, 0) AS xp
              FROM {user} u
         LEFT JOIN {block_gamification} x ON u.id = x.userid
             WHERE u.deleted = 0
          ORDER BY xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC
        ";
        $records = $DB->get_records_sql($sql);

        $rank = 1;
        foreach ($records as $u) {
            if ((int)$u->id === (int)$userid) {
                return $rank;
            }
            $rank++;
        }

        return 0; // Not found
    }

    /**
     * Rank within the visible leaderboard (truncated top N).
     */
    public function get_user_rank_from_leaderboard($userid, $limit = 50) {
        $leaderboard = $this->get_leaderboard($limit);

        $position = 1;
        foreach ($leaderboard as $u) {
            if ((int)$u->id === (int)$userid) {
                return $position;
            }
            $position++;
        }

        return 0;
    }
}
