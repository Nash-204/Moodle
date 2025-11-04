<?php
defined('MOODLE_INTERNAL') || die();

class block_gamification extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_gamification');
    }

    public function has_config(): bool {
        return true;
    }

    public function page_requirements() {
        global $PAGE;
        // Add CSS file for the block
        $PAGE->requires->css(new moodle_url('/blocks/gamification/styles.css'));
    }

    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $renderer = $this->page->get_renderer('block_gamification');
        $manager = new \block_gamification\leaderboard_manager();

        // User XP + Rank
        $xp   = $manager->get_user_xp($USER->id);
        $rank = $manager->get_user_rank($USER->id);

        // Leaderboards - reorder to show yearly first (most prestigious)
        $yearly   = $manager->get_yearly_leaderboard(5);
        $monthly  = $manager->get_monthly_leaderboard(10);
        $realtime = $manager->get_leaderboard(50);

        // Badges
        $badges   = \block_gamification\badge_manager::get_user_badges($USER->id);
        $context  = \context_system::instance();
        $isadminpreview = has_capability('block/gamification:previewbadges', $context);

        // Render content 
        $this->content->text  = $renderer->user_summary($xp, $rank);
        $this->content->text .= $renderer->render_badges($badges, $isadminpreview);

        // Yearly leaderboard (most prestigious - shown first)
        $this->content->text .= $renderer->render_leaderboard($yearly, $USER->id, 'year');

        // Monthly leaderboard 
        $this->content->text .= $renderer->render_leaderboard($monthly, $USER->id, 'month');

        // Real-time leaderboard (standard)
        $this->content->text .= $renderer->render_leaderboard($realtime, $USER->id, 'realtime');

        // Give XP form + toast JS
        $this->content->text .= $renderer->render_give_xp_form();
        $this->content->text .= $renderer->render_toast_js();

        // Toast message if set
        $toastmsg = get_user_preferences('block_gamification_toast', '');
        if (!empty($toastmsg)) {
            $this->content->text .= "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showXpToast(" . json_encode($toastmsg) . ");
                    });
                </script>
            ";
            unset_user_preference('block_gamification_toast', $USER->id);
        }

        return $this->content;
    }

    public function applicable_formats() {
        return ['site' => true, 'course-view' => true, 'my' => true];
    }
}