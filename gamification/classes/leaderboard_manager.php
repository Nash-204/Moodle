<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

class leaderboard_manager {
    // Get current XP for a user
    public function get_user_xp(int $userid): int {
        global $DB;
        if ($userid <= 0) {
            return 0;
        }
        $record = $DB->get_record('block_gamification', ['userid' => $userid]);
        return $record ? (int)$record->xp : 0;
    }

    // Add XP to a user 
    public function add_xp(int $userid, int $points): void {
        global $DB;
        if ($userid <= 0 || $points === 0) {
            return;
        }
        $record = $DB->get_record('block_gamification', ['userid' => $userid]);
        if ($record) {
            $record->xp = max(0, (int)$record->xp + $points);
            $DB->update_record('block_gamification', $record);
        } else {
            $DB->insert_record('block_gamification', (object)[
                'userid' => $userid,
                'xp' => max(0, $points),
            ]);
            self::check_realtime_badges($userid);
        }
    }

    // Get full leaderboard with unique groups from all courses - ONLY USERS WITH XP
    public function get_leaderboard(int $limit = 50): array {
        global $DB;
        $sql = "SELECT u.id, u.firstname, u.lastname, COALESCE(x.xp, 0) AS xp
                  FROM {user} u
             LEFT JOIN {block_gamification} x ON x.userid = u.id
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND u.id > 1
                   AND COALESCE(x.xp, 0) > 0  
             ORDER BY x.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $users = $DB->get_records_sql($sql, [], 0, $limit) ?: [];
        
        // Add unique groups information to each user from all courses
        foreach ($users as $user) {
            $user->groups = $this->get_user_unique_groups_across_courses($user->id);
        }
        
        return $users;
    }

    // Get full leaderboard including users with zero XP - with unique groups from all courses
    public function get_leaderboard_all_users(int $limit = 0): array {
        global $DB;
        $sql = "SELECT u.id, u.firstname, u.lastname, COALESCE(x.xp, 0) AS xp
                FROM {user} u
            LEFT JOIN {block_gamification} x ON x.userid = u.id
                WHERE u.deleted = 0 
                AND u.suspended = 0
                AND u.id > 1
            ORDER BY COALESCE(x.xp, 0) DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $users = $DB->get_records_sql($sql, [], 0, $limit) ?: [];
        
        // Add unique groups information to each user from all courses
        foreach ($users as $user) {
            $user->groups = $this->get_user_unique_groups_across_courses($user->id);
        }
        
        return $users;
    }

    // Get user's unique group names across all courses
    private function get_user_unique_groups_across_courses(int $userid): string {
        global $DB;
        
        if ($userid <= 0) {
            return '';
        }
        
        // Get all distinct group names the user belongs to across all courses
        $sql = "SELECT DISTINCT g.name
                FROM {groups} g
                JOIN {groups_members} gm ON g.id = gm.groupid
                WHERE gm.userid = ?
                ORDER BY g.name";
        
        $groupnames = $DB->get_fieldset_sql($sql, [$userid]);
        
        if (empty($groupnames)) {
            return get_string('nogroup', 'block_gamification');
        }
        
        return implode(', ', $groupnames);
    }

    // Get user rank - Return 0 (N/A) for users with no XP or not in leaderboard
    public function get_user_rank(int $userid): int {
        global $DB;
        if ($userid <= 0 || $userid == 1) {
            return 0; 
        }
        
        // First check if user has any XP
        $userXp = $this->get_user_xp($userid);
        if ($userXp <= 0) {
            return 0; 
        }
        
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {block_gamification} x ON u.id = x.userid
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND u.id > 1
                   AND COALESCE(x.xp, 0) > 0 
             ORDER BY x.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $records = $DB->get_records_sql($sql) ?: [];
        $rank = 1;
        foreach ($records as $r) {
            if ((int)$r->id === (int)$userid) {
                return $rank;
            }
            $rank++;
        }
        return 0; // 0 means N/A if user not found in ranked list
    }

    // Check if user should appear in leaderboard (has XP)
    public function user_has_xp(int $userid): bool {
        return $this->get_user_xp($userid) > 0;
    }

    // Check and award real-time badges (Top 10)
    public static function check_realtime_badges(int $userid) {
        global $DB;
        if ($userid <= 0) {
            return;
        }
        $sql = "SELECT userid FROM {block_gamification} ORDER BY xp DESC LIMIT 10";
        $top10 = $DB->get_fieldset_sql($sql) ?: [];
        if (in_array($userid, $top10)) {
            \block_gamification\badge_manager::award_badge_if_new($userid, 'Leaderboard_Top10');
        }
    }

    // Check and award scheduled badges (weekly, monthly, yearly)
    public static function check_scheduled_badges(string $period) {
        global $DB;
        $sql = "SELECT userid FROM {block_gamification} WHERE xp > 0 ORDER BY xp DESC LIMIT 1";
        $leaderid = $DB->get_field_sql($sql);
        if (!$leaderid) {
            return;
        }
        
        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        
        switch ($period) {
            case 'week':
                // Weekly badge without period tracking
                \block_gamification\badge_manager::award_badge_if_new($leaderid, 'Leaderboard_Week');
                break;
            case 'month':
                // Monthly badge with period tracking
                \block_gamification\badge_manager::award_monthly_leaderboard_champion($leaderid, $currentMonth, $currentYear);
                break;
            case 'year':
                // Annual badge with period tracking
                \block_gamification\badge_manager::award_annual_leaderboard_champion($leaderid, $currentYear);
                break;
        }
    }

    // Monthly snapshot leaderboard with unique groups - ONLY USERS WITH XP
    public function get_monthly_leaderboard(int $limit = 10): array {
        global $DB;
        $sql = "SELECT u.id, u.firstname, u.lastname, m.xp
                  FROM {block_gamif_monthly} m
                  JOIN {user} u ON u.id = m.userid
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND m.xp > 0  
              ORDER BY m.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $users = $DB->get_records_sql($sql, [], 0, $limit) ?: [];
        
        // Add unique groups information to each user from all courses
        foreach ($users as $user) {
            $user->groups = $this->get_user_unique_groups_across_courses($user->id);
        }
        
        return $users;
    }

    // Yearly snapshot leaderboard with unique groups - ONLY USERS WITH XP
    public function get_yearly_leaderboard(int $limit = 10): array {
        global $DB;
        $sql = "SELECT u.id, u.firstname, u.lastname, y.xp
                  FROM {block_gamif_yearly} y
                  JOIN {user} u ON u.id = y.userid
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND y.xp > 0 
              ORDER BY y.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $users = $DB->get_records_sql($sql, [], 0, $limit) ?: [];
        
        // Add unique groups information to each user from all courses
        foreach ($users as $user) {
            $user->groups = $this->get_user_unique_groups_across_courses($user->id);
        }
        
        return $users;
    }

    // Get monthly rank - Return 0 (N/A) for users with no monthly XP
    public function get_user_monthly_rank(int $userid): int {
        global $DB;
        if ($userid <= 0 || $userid == 1) {
            return 0; 
        }
        
        // First check if user has any monthly XP
        $sql = "SELECT xp FROM {block_gamif_monthly} WHERE userid = ?";
        $monthlyXp = $DB->get_field_sql($sql, [$userid]);
        if (!$monthlyXp || $monthlyXp <= 0) {
            return 0; 
        }
        
        $sql = "SELECT u.id
                  FROM {block_gamif_monthly} m
                  JOIN {user} u ON u.id = m.userid
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND m.xp > 0 
              ORDER BY m.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $records = $DB->get_records_sql($sql) ?: [];
        $rank = 1;
        foreach ($records as $r) {
            if ((int)$r->id === (int)$userid) {
                return $rank;
            }
            $rank++;
        }
        return 0; // 0 means N/A if user not found in ranked list
    }

    // Get yearly rank - Return 0 (N/A) for users with no yearly XP
    public function get_user_yearly_rank(int $userid): int {
        global $DB;
        if ($userid <= 0 || $userid == 1) {
            return 0; 
        }
        
        // First check if user has any yearly XP
        $sql = "SELECT xp FROM {block_gamif_yearly} WHERE userid = ?";
        $yearlyXp = $DB->get_field_sql($sql, [$userid]);
        if (!$yearlyXp || $yearlyXp <= 0) {
            return 0; 
        }
        
        $sql = "SELECT u.id
                  FROM {block_gamif_yearly} y
                  JOIN {user} u ON u.id = y.userid
                 WHERE u.deleted = 0 
                   AND u.suspended = 0
                   AND y.xp > 0  -- Only rank users with yearly XP
              ORDER BY y.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $records = $DB->get_records_sql($sql) ?: [];
        $rank = 1;
        foreach ($records as $r) {
            if ((int)$r->id === (int)$userid) {
                return $rank;
            }
            $rank++;
        }
        return 0; // 0 means N/A if user not found in ranked list
    }

    /**
     * Get historical monthly badges for a user
     */
    public function get_user_monthly_badges(int $userid): array {
        global $DB;
        
        if ($userid <= 0) {
            return [];
        }
        
        $sql = "SELECT badgecode, period, display_name, timeearned 
                FROM {block_gamif_badges} 
                WHERE userid = ? 
                AND badgecode = 'Leaderboard_Month'
                AND period IS NOT NULL
                ORDER BY period DESC";
        
        return $DB->get_records_sql($sql, [$userid]) ?: [];
    }

    /**
     * Get historical annual badges for a user
     */
    public function get_user_annual_badges(int $userid): array {
        global $DB;
        
        if ($userid <= 0) {
            return [];
        }
        
        $sql = "SELECT badgecode, period, display_name, timeearned 
                FROM {block_gamif_badges} 
                WHERE userid = ? 
                AND badgecode = 'Leaderboard_Annual'
                AND period IS NOT NULL
                ORDER BY period DESC";
        
        return $DB->get_records_sql($sql, [$userid]) ?: [];
    }

    /**
     * Check if user has monthly badge for specific period
     */
    public function has_monthly_badge_for_period(int $userid, int $month, int $year): bool {
        $period = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
        return \block_gamification\badge_manager::has_badge($userid, 'Leaderboard_Month', $period);
    }

    /**
     * Check if user has annual badge for specific year
     */
    public function has_annual_badge_for_year(int $userid, int $year): bool {
        $period = "{$year}";
        return \block_gamification\badge_manager::has_badge($userid, 'Leaderboard_Annual', $period);
    }
    
    /**
     * Award Weekly Leaderboard Champion badge to top user
     */
    public function process_weekly_champion(): void {
        $topusers = $this->get_top_users(1);
        if (empty($topusers)) {
            return;
        }

        $champion = reset($topusers);
        \block_gamification\badge_manager::award_badge_if_new($champion->id, 'Leaderboard_Week');
    }

    /**
     * Generate Monthly Leaderboard - Top 10 users
     */
    public function generate_monthly_leaderboard(): void {
        global $DB;

        // Clear previous monthly data
        $DB->delete_records('block_gamif_monthly');
        
        // Get current top 10 users from real-time leaderboard
        $topusers = $this->get_top_users(10);
        
        // Save to monthly table
        foreach ($topusers as $user) {
            $record = new \stdClass();
            $record->userid = $user->id;
            $record->xp = $user->xp;
            $record->timecreated = time();
            $DB->insert_record('block_gamif_monthly', $record);
        }

        // Award badge to monthly champion
        if (!empty($topusers)) {
            $champion = reset($topusers);
            $currentMonth = (int)date('n');
            $currentYear = (int)date('Y');
            \block_gamification\badge_manager::award_monthly_leaderboard_champion($champion->id, $currentMonth, $currentYear);
        }
    }

    /**
     * Generate Yearly Leaderboard - Top 5 users  
     */
    public function generate_yearly_leaderboard(): void {
        global $DB;

        // Clear previous yearly data
        $DB->delete_records('block_gamif_yearly');
        
        // Get current top 5 users from real-time leaderboard
        $topusers = $this->get_top_users(5);
        
        // Save to yearly table
        foreach ($topusers as $user) {
            $record = new \stdClass();
            $record->userid = $user->id;
            $record->xp = $user->xp;
            $record->timecreated = time();
            $DB->insert_record('block_gamif_yearly', $record);
        }

        // Award badge to yearly champion
        if (!empty($topusers)) {
            $champion = reset($topusers);
            $currentYear = (int)date('Y');
            \block_gamification\badge_manager::award_annual_leaderboard_champion($champion->id, $currentYear);
        }
    }

    /**
     * Helper to fetch top N users by XP from realtime leaderboard
     */
    private function get_top_users(int $limit = 10): array {
        global $DB;
        
        $sql = "
            SELECT u.id, u.firstname, u.lastname, COALESCE(g.xp, 0) as xp
            FROM {user} u
            LEFT JOIN {block_gamification} g ON g.userid = u.id
            WHERE u.deleted = 0 
            AND u.suspended = 0
            AND u.id > 1
            AND COALESCE(g.xp, 0) > 0
            ORDER BY COALESCE(g.xp, 0) DESC, u.lastname ASC, u.firstname ASC
            LIMIT " . $limit;
        
        return $DB->get_records_sql($sql) ?: [];
    }
}