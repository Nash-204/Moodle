<?php
defined('MOODLE_INTERNAL') || die();

class block_gamification_renderer extends plugin_renderer_base {

    public function render_leaderboard($users, $highlightid = null) {
        $rank = 1;

        $html = html_writer::start_div('gamification-container');
        $html .= html_writer::start_tag('table', ['class' => 'generaltable gamification-table']);

        // Header
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::tag('tr',
            html_writer::tag('th', get_string('rank', 'block_gamification')) .
            html_writer::tag('th', get_string('user', 'block_gamification')) .
            html_writer::tag('th', get_string('xp', 'block_gamification'))
        );
        $html .= html_writer::end_tag('thead');

        // Body
        $html .= html_writer::start_tag('tbody');
        foreach ($users as $u) {
            $fullname = fullname($u);
            $rowclass = ($highlightid && $highlightid == $u->id) ? 'highlight' : '';

            // Medal or number
            if ($rank == 1) {
                $rankdisplay = '🥇';
            } else if ($rank == 2) {
                $rankdisplay = '🥈';
            } else if ($rank == 3) {
                $rankdisplay = '🥉';
            } else {
                $rankdisplay = "<strong>{$rank}</strong>";
            }

            $html .= html_writer::tag('tr',
                html_writer::tag('td', $rankdisplay, ['class' => 'rank-col']) .
                html_writer::tag('td', $fullname) .
                html_writer::tag('td', $u->xp),
                ['class' => $rowclass]
            );

            $rank++;
        }
        $html .= html_writer::end_tag('tbody');

        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_div();

        return $html;
    }
}
