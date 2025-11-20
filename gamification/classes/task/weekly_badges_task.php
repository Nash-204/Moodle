<?php
namespace block_gamification\task;

defined('MOODLE_INTERNAL') || die();

class weekly_badges_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('weeklybadgestask', 'block_gamification');
    }

    public function execute() {
        \block_gamification\leaderboard_manager::check_scheduled_badges('week');
    }
}
