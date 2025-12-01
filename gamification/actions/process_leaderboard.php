<?php
require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/gamification/classes/leaderboard_manager.php');

require_login();
require_sesskey();

$type = required_param('type', PARAM_ALPHA); // 'week', 'month', or 'year'
$redirect = optional_param('redirect', '/blocks/gamification/leaderboard.php', PARAM_LOCALURL);

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$leaderboard_manager = new \block_gamification\leaderboard_manager();

switch ($type) {
    case 'week':
        $leaderboard_manager->process_weekly_champion();
        \core\notification::success(get_string('weeklychampionawarded', 'block_gamification'));
        break;
        
    case 'month':
        $leaderboard_manager->generate_monthly_leaderboard();
        \core\notification::success(get_string('monthlyleadergenerated', 'block_gamification'));
        break;
        
    case 'year':
        $leaderboard_manager->generate_yearly_leaderboard();
        \core\notification::success(get_string('yearlyleadergenerated', 'block_gamification'));
        break;
        
    default:
        \core\notification::error(get_string('invalidtype', 'block_gamification'));
}

redirect(new moodle_url('/my'));