<?php
namespace block_gamification\output;

defined('MOODLE_INTERNAL') || die();

class user_summary implements \renderable {
    public $xp;
    public $rank;

    public function __construct($xp, $rank) {
        $this->xp = $xp;
        $this->rank = $rank;
    }
}