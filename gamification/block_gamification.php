<?php
defined('MOODLE_INTERNAL') || die();

class block_gamification extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_gamification');
    }

    public function has_config(): bool {
        return true;
    }


    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $renderer = $this->page->get_renderer('block_gamification');
        $manager = new \block_gamification\leaderboard_manager();

        $xp = $manager->get_user_xp($USER->id);
        $rank = $manager->get_user_rank($USER->id);
        $leaderboard = $manager->get_leaderboard(20);

        $this->content->text  = $renderer->user_summary($xp, $rank);
        $this->content->text .= $renderer->render_leaderboard($leaderboard, $USER->id);
        $this->content->text .= $renderer->render_give_xp_form();
        $this->content->text .= $renderer->render_toast_js();

        // ✅ Toast notification if available
        $toastmsg = get_user_preferences('block_gamification_toast', '');
        if (!empty($toastmsg)) {
            $this->content->text .= "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showXpToast(" . json_encode($toastmsg) . ");
                    });
                </script>
            ";
            // Clear it so it only shows once
            unset_user_preference('block_gamification_toast', $USER->id);
        }

        return $this->content;
    }


    public function applicable_formats() {
        return ['site' => true, 'course-view' => true, 'my' => true];
    }
}
