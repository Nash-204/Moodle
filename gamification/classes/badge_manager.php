<?php
namespace block_gamification;

defined('MOODLE_INTERNAL') || die();

/**
 * Badge Manager for Gamification plugin.
 *
 * Handles awarding, fetching, verifying badges and notifications.
 */
class badge_manager {

    /**
     * Award a badge to a user if they don't already have it.
     * Sends a notification only when newly awarded.
     */
    public static function award_badge(int $userid, string $badgecode, ?string $period = null, ?string $displayName = null): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        // Check if user already has the badge for this specific period
        $params = ['userid' => $userid, 'badgecode' => $badgecode];
        if ($period !== null) {
            $params['period'] = $period;
        } else {
            // For badges without period, check for any record with this badgecode (including null periods)
            $existing = $DB->get_records('block_gamif_badges', ['userid' => $userid, 'badgecode' => $badgecode]);
            if (!empty($existing)) {
                return false;
            }
        }
        
        if ($DB->record_exists('block_gamif_badges', $params)) {
            return false;
        }

        // Insert new badge record
        $record = (object)[
            'userid'       => $userid,
            'badgecode'    => $badgecode,
            'timeearned'   => time(),
            'period'       => $period,
            'display_name' => $displayName,
        ];
        $DB->insert_record('block_gamif_badges', $record);

        // Send notification
        self::notify_badge_awarded($userid, $badgecode, $displayName);

        return true;
    }

    /**
     * Safe helper.
     */
    public static function award_badge_if_new(int $userid, string $badgecode) {
        return self::award_badge($userid, $badgecode);
    }

    /**
     * Award monthly leaderboard champion badge
     */
    public static function award_monthly_leaderboard_champion(int $userid, int $month, int $year): bool {
        $period = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
        $displayName = "Monthly Leaderboard Champion (" . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ")";
        
        return self::award_badge($userid, 'Leaderboard_Month', $period, $displayName);
    }

    /**
     * Award annual leaderboard champion badge  
     */
    public static function award_annual_leaderboard_champion(int $userid, int $year): bool {
        $period = "{$year}";
        $displayName = "Annual Leaderboard Champion ({$year})";
        
        return self::award_badge($userid, 'Leaderboard_Annual', $period, $displayName);
    }

    /**
     * Send notifications when a badge is awarded.
     */
    private static function notify_badge_awarded(int $userid, string $badgecode, ?string $displayName = null): void {
        global $USER, $DB;

        // Fetch badge definition for nicer messages
        $badge = $DB->get_record('block_gamif_badges_def',
            ['badgecode' => $badgecode], '*', IGNORE_MISSING);
        
        // If not found in local definitions, check Moodle core badges
        if (!$badge) {
            $badge = self::get_moodle_badge_by_code($badgecode);
        }
        
        // Use display name if provided, otherwise fall back to badge name
        $badgename = $displayName ?: ($badge ? ($badge->name ?? $badgecode) : $badgecode);

        // Toast notification
        if ($USER->id === $userid) {
            \core\notification::success("🏅 You earned the badge: {$badgename}!");
        }

        // Moodle notification
        $eventdata = new \core\message\message();
        $eventdata->component         = 'block_gamification';
        $eventdata->name              = 'badgenotification';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $userid;
        $eventdata->subject           = "You earned a new badge!";
        $eventdata->fullmessage       = "Congratulations! You earned the badge: {$badgename}.";
        $eventdata->fullmessageformat = FORMAT_MARKDOWN;
        $eventdata->fullmessagehtml   = "<p>🏅 Congratulations! You earned the <strong>{$badgename}</strong> badge.</p>";
        $eventdata->smallmessage      = "🏅 New badge: {$badgename}";
        $eventdata->notification      = 1;
        $eventdata->contexturl        = (new \moodle_url('/my'))->out(false);
        $eventdata->contexturlname    = get_string('myhome');
        message_send($eventdata);

        // Save preference for frontend toast fallback
        set_user_preference('block_gamification_toast',
            "🏅 You earned the badge: {$badgename}!",
            $userid
        );
    }

    /**
     * Return earned badges for a user from both local and Moodle core badges.
     */
    public static function get_user_badges(int $userid = 0): array {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $context = \context_system::instance();
        $isadmin = has_capability('block/gamification:previewbadges', $context);

        // Get all badge definitions (local + Moodle core)
        $allBadges = self::get_all_badge_definitions_with_moodle();

        // Get ALL earned local badges for the user
        $earnedLocal = $DB->get_records('block_gamif_badges', ['userid' => $userid]);

        // Get earned Moodle core badges
        $earnedMoodle = self::get_earned_moodle_badges($userid);
        
        // Auto-award Badge_Collector 
        $uniqueLocalBadges = array_unique(array_column($earnedLocal, 'badgecode'));
        $localBadgeCount = count($uniqueLocalBadges);
        if ($localBadgeCount >= 5 && !in_array('Badge_Collector', $uniqueLocalBadges)) {
            self::award_badge($userid, 'Badge_Collector');
            $earnedLocal[] = (object)[
                'badgecode' => 'Badge_Collector',
                'period' => null,
                'display_name' => null,
                'timeearned' => time()
            ];
        }

        // If admin in personal mode, return badges organized like regular users
        if ($isadmin) {
            $previewmode = optional_param('badge_preview', 1, PARAM_BOOL);
            
            if (!$previewmode) {
                // ADMIN PERSONAL MODE: Show only earned badges, organized like regular users
                return self::get_organized_user_badges($userid, $allBadges, $earnedLocal, $earnedMoodle);
            } else {
                // ADMIN PREVIEW MODE: Show all badges as "earned" for preview
                $allBadgesWithEarned = [];
                foreach ($allBadges as $badge) {
                    $badgeClone = clone $badge;
                    $badgeClone->earned = true; // Force all as earned for preview
                    $badgeClone->display_name = $badge->name;
                    $allBadgesWithEarned[] = $badgeClone;
                }
                return $allBadgesWithEarned;
            }
        }

        // REGULAR USER: Return organized badges
        return self::get_organized_user_badges($userid, $allBadges, $earnedLocal, $earnedMoodle);
    }

    /**
     * Helper method to organize badges for both regular users and admins in personal mode
     */
    private static function get_organized_user_badges(int $userid, array $allBadges, array $earnedLocal, array $earnedMoodle): array {
        // Separate period badges from regular badges
        $periodBadges = []; // For monthly/annual badges with periods
        $regularBadges = []; // For all other badges
        
        // Process EVERY earned badge
        foreach ($earnedLocal as $earned) {
            // Find the base definition for this badge
            $baseDefinition = null;
            foreach ($allBadges as $def) {
                if ($def->badgecode === $earned->badgecode) {
                    $baseDefinition = $def;
                    break;
                }
            }
            
            if ($baseDefinition) {
                $badge = clone $baseDefinition;
                $badge->earned = true;
                $badge->timeearned = $earned->timeearned;
                $badge->period = $earned->period;
                
                // Set display name - prioritize custom, then generate from period, then fallback
                if (!empty($earned->display_name)) {
                    $badge->display_name = $earned->display_name;
                } else if (!empty($earned->period)) {
                    if ($badge->badgecode === 'Leaderboard_Month') {
                        $date = \DateTime::createFromFormat('Y-m', $earned->period);
                        if ($date) {
                            $badge->display_name = "Monthly Leaderboard Champion (" . $date->format('F Y') . ")";
                        }
                    } else if ($badge->badgecode === 'Leaderboard_Annual') {
                        $badge->display_name = "Annual Leaderboard Champion (" . $earned->period . ")";
                    }
                }
                
                // Final fallback
                if (empty($badge->display_name)) {
                    $badge->display_name = $badge->name;
                }
                
                // Separate period badges from regular badges
                if (!empty($earned->period) && 
                    ($badge->badgecode === 'Leaderboard_Month' || $badge->badgecode === 'Leaderboard_Annual')) {
                    $periodBadges[] = $badge;
                } else {
                    $regularBadges[] = $badge;
                }
            }
        }
        
        // Now add Moodle badges that user has earned but aren't already in output
        foreach ($allBadges as $badgeDef) {
            $alreadyInOutput = false;
            foreach ($regularBadges as $outputBadge) {
                if ($outputBadge->badgecode === $badgeDef->badgecode) {
                    $alreadyInOutput = true;
                    break;
                }
            }
            foreach ($periodBadges as $outputBadge) {
                if ($outputBadge->badgecode === $badgeDef->badgecode) {
                    $alreadyInOutput = true;
                    break;
                }
            }
            
            if (!$alreadyInOutput && in_array($badgeDef->badgecode, $earnedMoodle)) {
                $badgeDef->earned = true;
                $badgeDef->display_name = $badgeDef->name;
                $regularBadges[] = $badgeDef;
            }
        }
        
        // Adds unearned badges that aren't already in output
        foreach ($allBadges as $badgeDef) {
            $alreadyInOutput = false;
            foreach ($regularBadges as $outputBadge) {
                if ($outputBadge->badgecode === $badgeDef->badgecode) {
                    $alreadyInOutput = true;
                    break;
                }
            }
            foreach ($periodBadges as $outputBadge) {
                if ($outputBadge->badgecode === $badgeDef->badgecode) {
                    $alreadyInOutput = true;
                    break;
                }
            }
            
            if (!$alreadyInOutput) {
                $badgeDef->earned = false;
                $badgeDef->display_name = $badgeDef->name;
                $regularBadges[] = $badgeDef;
            }
        }

        // Sort period badges by date (most recent first)
        usort($periodBadges, function($a, $b) {
            // Sort by period descending (most recent first)
            return strcmp($b->period ?? '', $a->period ?? '');
        });

        // Sort regular badges by time earned (most recent first)
        usort($regularBadges, function($a, $b) {
            $timeA = $a->timeearned ?? 0;
            $timeB = $b->timeearned ?? 0;
            
            // Put earned badges first, then sort by time
            if ($a->earned && !$b->earned) return -1;
            if (!$a->earned && $b->earned) return 1;
            
            return $timeB - $timeA; // Most recent first
        });

        // Combine earned badges first (period then regular), then unearned badges
        $earnedPeriodBadges = array_filter($periodBadges, function($badge) { return $badge->earned; });
        $earnedRegularBadges = array_filter($regularBadges, function($badge) { return $badge->earned; });
        $unearnedBadges = array_filter(array_merge($periodBadges, $regularBadges), function($badge) { return !$badge->earned; });

        return array_merge($earnedPeriodBadges, $earnedRegularBadges, $unearnedBadges);
    }

    // Get all badge definitions including Moodle core badges from all courses AND site-level badges.
    public static function get_all_badge_definitions_with_moodle(): array {
        global $DB;

        $localBadges = $DB->get_records('block_gamif_badges_def', null, 'id ASC') ?? [];
        
        // Get ALL Moodle core badges (both course and site-level)
        $moodleBadges = self::get_all_moodle_badges();
        
        // Combine local and Moodle badges
        $allBadges = [];
        
        // Add local badges first
        foreach ($localBadges as $badge) {
            $badge->is_moodle_badge = false;
            $badge->display_name = $badge->name;
            $allBadges[] = $badge;
        }
        
        // Add Moodle badges
        foreach ($moodleBadges as $badge) {
            $badge->is_moodle_badge = true;
            $badge->display_name = $badge->name;
            $allBadges[] = $badge;
        }
        
        return $allBadges;
    }

    /**
     * Get ALL Moodle core badges including site-level badges.
     * This includes badges from courses AND site-level badges.
     */
    private static function get_all_moodle_badges(): array {
        global $DB;

        $sql = "SELECT b.id, b.name, b.description, b.timecreated, b.timemodified,
                       CONCAT('moodle_badge_', b.id) as badgecode,
                       c.fullname as coursename,
                       b.courseid,
                       CASE 
                         WHEN b.courseid IS NULL THEN 'Site-level'
                         ELSE 'Course'
                       END as badgetype
                FROM {badge} b
                LEFT JOIN {course} c ON b.courseid = c.id
                WHERE b.status != 0 
                ORDER BY 
                  CASE WHEN b.courseid IS NULL THEN 0 ELSE 1 END, 
                  b.courseid, 
                  b.name";

        $badges = $DB->get_records_sql($sql);
        
        return $badges ? array_values($badges) : [];
    }

    /**
     * Get Moodle core badges earned by a user.
     */
    private static function get_earned_moodle_badges(int $userid): array {
        global $DB;

        $sql = "SELECT CONCAT('moodle_badge_', b.id) as badgecode
                FROM {badge_issued} bi
                JOIN {badge} b ON bi.badgeid = b.id
                WHERE bi.userid = ? AND b.status != 0";

        $earned = $DB->get_fieldset_sql($sql, [$userid]);
        
        return $earned ?: [];
    }

    /**
     * Get Moodle badge by code.
     */
    private static function get_moodle_badge_by_code(string $badgecode) {
        global $DB;

        if (strpos($badgecode, 'moodle_badge_') === 0) {
            $badgeid = (int) str_replace('moodle_badge_', '', $badgecode);
            
            $sql = "SELECT b.*, CONCAT('moodle_badge_', b.id) as badgecode,
                           c.fullname as coursename,
                           CASE 
                             WHEN b.courseid IS NULL THEN 'Site-level'
                             ELSE 'Course'
                           END as badgetype
                    FROM {badge} b
                    LEFT JOIN {course} c ON b.courseid = c.id
                    WHERE b.id = ? AND b.status != 0";
            
            return $DB->get_record_sql($sql, [$badgeid]);
        }
        
        return false;
    }

    /**
     * Check if a user has a badge.
     */
    public static function has_badge(int $userid, string $badgecode, ?string $period = null): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        // Check local badges
        $params = ['userid' => $userid, 'badgecode' => $badgecode];
        if ($period !== null) {
            $params['period'] = $period;
        } else {
            // For badges without specific period, check if any record exists
            $existing = $DB->get_records('block_gamif_badges', ['userid' => $userid, 'badgecode' => $badgecode]);
            return !empty($existing);
        }
        
        return $DB->record_exists('block_gamif_badges', $params);
    }

    /**
     * Return array of local badge definitions
     */
    public static function get_all_badge_definitions(): array {
        global $DB;
        $badges = $DB->get_records('block_gamif_badges_def', null, 'id ASC') ?? [];
        
        // Ensure display_name is set for all badges
        foreach ($badges as $badge) {
            if (empty($badge->display_name)) {
                $badge->display_name = $badge->name;
            }
        }
        
        return $badges;
    }

    /**
     * Get user's earned badges with full details including period info
     */
    public static function get_user_badges_detailed(int $userid = 0): array {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $sql = "SELECT bg.*, bd.name, bd.description, bd.image,
                    COALESCE(bg.display_name, bd.name) as display_name
                FROM {block_gamif_badges} bg
                LEFT JOIN {block_gamif_badges_def} bd ON bg.badgecode = bd.badgecode
                WHERE bg.userid = ?
                ORDER BY bg.timeearned DESC";

        return $DB->get_records_sql($sql, [$userid]) ?? [];
    }

    /**
     * Get detailed earned Moodle core badges for a user
     */
    public static function get_earned_moodle_badges_detailed(int $userid): array {
        global $DB;

        $sql = "SELECT bi.badgeid, bi.dateissued as timeearned, 
                    b.name, b.description, b.courseid,
                    CONCAT('moodle_badge_', b.id) as badgecode,
                    c.fullname as coursename,
                    CASE 
                        WHEN b.courseid IS NULL THEN 'Site-level'
                        ELSE 'Course'
                    END as badgetype,
                    1 as is_moodle_badge
                FROM {badge_issued} bi
                JOIN {badge} b ON bi.badgeid = b.id
                LEFT JOIN {course} c ON b.courseid = c.id
                WHERE bi.userid = ? AND b.status != 0
                ORDER BY bi.dateissued DESC";

        $badges = $DB->get_records_sql($sql, [$userid]);
        
        return $badges ? array_values($badges) : [];
    }

}

