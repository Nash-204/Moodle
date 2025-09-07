<?php
class block_gamification extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_gamification');
    }

    public function get_content() {
        global $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        require_once(__DIR__ . '/classes/leaderboard_manager.php');
        $xpmanager = new \block_gamification\leaderboard_manager();

        $userxp = $xpmanager->get_user_xp($USER->id);
        $users  = $xpmanager->get_leaderboard();

        $renderer = $PAGE->get_renderer('block_gamification');
        $leaderboardhtml = $renderer->render_leaderboard($users, $USER->id);

        $this->content->text = html_writer::div(
            get_string('yourxp', 'block_gamification') . ": <b>{$userxp}</b><br>" .
            $leaderboardhtml
        );

        $exporturl = new moodle_url('/blocks/gamification/index.php', ['action' => 'export']);
        $this->content->footer = html_writer::link(
            $exporturl,
            get_string('exportcsv', 'block_gamification'),
            ['class' => 'btn btn-primary']
        );

        return $this->content;
    }
}
