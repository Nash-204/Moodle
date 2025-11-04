<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class block_gamification_external extends external_api {

    public static function get_user_badges_by_email_parameters() {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'User email address')
        ]);
    }

    public static function get_user_badges_by_email($email) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_user_badges_by_email_parameters(), ['email' => $email]);

        require_once($CFG->dirroot . '/blocks/gamification/classes/badge_manager.php');

        // Find user by email
        $user = $DB->get_record('user', ['email' => $params['email'], 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found in Moodle',
                'email' => $params['email'],
                'badges' => []
            ];
        }

        try {
            // Get earned local badges
            $earnedLocal = \block_gamification\badge_manager::get_user_badges_detailed($user->id);
            
            // Get earned Moodle core badges
            $earnedMoodle = \block_gamification\badge_manager::get_earned_moodle_badges_detailed($user->id);

            // Combine both badge types
            $allEarnedBadges = array_merge($earnedLocal, $earnedMoodle);

            // Format response
            $formatted = [];
            // In your get_user_badges_by_email() method, change this:
            foreach ($allEarnedBadges as $badge) {
                $formatted[] = [
                    'badgecode' => $badge->badgecode ?? '',
                    'name' => $badge->display_name ?? $badge->name ?? 'Unknown Badge', // This will now use display_name first
                    'description' => $badge->description ?? '',
                    'image' => self::get_badge_image_url($badge),
                    'timeearned' => $badge->timeearned ?? null,
                    'date_earned' => !empty($badge->timeearned) ? date('Y-m-d', $badge->timeearned) : null,
                    'is_moodle_badge' => (bool)($badge->is_moodle_badge ?? false),
                ];
            }

            // Sort by most recent
            usort($formatted, fn($a, $b) => ($b['timeearned'] ?? 0) - ($a['timeearned'] ?? 0));

            return [
                'success' => true,
                'email' => $user->email,
                'userid' => $user->id,
                'fullname' => fullname($user),
                'total_badges' => count($formatted),
                'badges' => array_values($formatted),
            ];

        } catch (Exception $e) {
            error_log("Badge API Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching badges: ' . $e->getMessage(),
                'email' => $params['email'],
                'badges' => []
            ];
        }
    }


    /**
     * Helper method to get appropriate badge image URL
     */
    private static function get_badge_image_url($badge) {
        global $CFG;

        // For Moodle core badges
        if (!empty($badge->is_moodle_badge)) {
            $badgeid = null;
            
            if (!empty($badge->badgeid)) {
                $badgeid = $badge->badgeid;
            } else if (!empty($badge->badgecode) && strpos($badge->badgecode, 'moodle_badge_') === 0) {
                $badgeid = (int) str_replace('moodle_badge_', '', $badge->badgecode);
            }
            
            if ($badgeid) {
                // Get the context for badges - usually system context for site badges, course context for course badges
                $contextid = null;
                
                if (!empty($badge->courseid) && $badge->courseid > 0) {
                    // Course badge - get course context
                    $context = \context_course::instance($badge->courseid);
                    $contextid = $context->id;
                } else {
                    // Site badge - get system context
                    $context = \context_system::instance();
                    $contextid = $context->id;
                }
                
                // Standard Moodle badge image URL pattern
                return "{$CFG->wwwroot}/pluginfile.php/{$contextid}/badges/badgeimage/{$badgeid}/f1";
            }
        }

        // For local badges (your existing code)
        if (!empty($badge->image)) {
            $image = trim($badge->image);
            if (preg_match('#^https?://#', $image)) {
                return $image;
            }
            $image = ltrim($image, '/');
            $image = preg_replace('#^pix/#', '', $image);
            return "{$CFG->wwwroot}/blocks/gamification/pix/{$image}";
        }

        // Fallback for known badge codes
        $localBadgeImages = [
            'Leaderboard_Month' => 'monthly_leader.png',
            'Leaderboard_Annual' => 'annual_leader.png',
            'Badge_Collector' => 'collector.png',
        ];

        $imageName = $localBadgeImages[$badge->badgecode] ?? 'default_badge.png';
        return "{$CFG->wwwroot}/blocks/gamification/pix/{$imageName}";
    }


    public static function get_user_badges_by_email_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'message' => new external_value(PARAM_TEXT, 'Response message', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_EMAIL, 'User email'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'User full name', VALUE_OPTIONAL),
            'total_badges' => new external_value(PARAM_INT, 'Total number of earned badges'),
            'badges' => new external_multiple_structure(
                new external_single_structure([
                    'badgecode' => new external_value(PARAM_TEXT, 'Badge code'),
                    'name' => new external_value(PARAM_TEXT, 'Badge display name'),
                    'description' => new external_value(PARAM_TEXT, 'Badge description', VALUE_OPTIONAL),
                    'image' => new external_value(PARAM_URL, 'Badge image URL'),
                    'timeearned' => new external_value(PARAM_INT, 'Timestamp when earned', VALUE_OPTIONAL),
                    'date_earned' => new external_value(PARAM_TEXT, 'Formatted date when earned', VALUE_OPTIONAL),
                    'is_moodle_badge' => new external_value(PARAM_BOOL, 'Whether this is a Moodle core badge'),
                ])
            ),
        ]);
    }
}