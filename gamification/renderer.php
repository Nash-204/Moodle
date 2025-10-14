<?php
defined('MOODLE_INTERNAL') || die();

class block_gamification_renderer extends plugin_renderer_base {

    // User summary: XP and Rank
    public function user_summary(int $xp, int $rank): string {
        $html  = html_writer::start_div('gamification-user-summary');

        // 🏆 Rank
        $html .= html_writer::start_div('summary-item');
        $html .= html_writer::div('🏆', 'summary-icon');
        $html .= html_writer::div(get_string('yourrank', 'block_gamification'), 'summary-label');
        
        // Show N/A if rank is 0, otherwise show the actual rank
        $rankDisplay = ($rank === 0) ? get_string('na', 'block_gamification') : number_format($rank);
        $html .= html_writer::div($rankDisplay, 'summary-value');
        $html .= html_writer::end_div();
        
        // ⭐ XP
        $html .= html_writer::start_div('summary-item');
        $html .= html_writer::div('⭐', 'summary-icon');
        $html .= html_writer::div(get_string('yourxp', 'block_gamification'), 'summary-label');
        $html .= html_writer::div(number_format($xp), 'summary-value');
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();
        return $html;
    }

    // Badges display
    public function render_badges(array $badges, bool $isadmin = false): string {
        global $PAGE;
        
        $html = html_writer::start_div('gamification-badges-container card p-3 mb-3 shadow-sm');
        $html .= html_writer::tag('h4', get_string('yourbadges', 'block_gamification'), ['class' => 'gamification-badges-title']);

        if ($isadmin) {
            $html .= html_writer::tag('small', get_string('allbadgespreview', 'block_gamification'), ['class' => 'text-muted']);
        }

        $html .= html_writer::start_div('gamification-badges-strip');

        foreach ($badges as $badge) {
            $earned = property_exists($badge, 'earned') ? (bool)$badge->earned : false;
            if ($isadmin) {
                $earned = true;
            }

            $isMoodleBadge = property_exists($badge, 'is_moodle_badge') ? (bool)$badge->is_moodle_badge : false;

            // Image loading
            if ($isMoodleBadge) {
                $badgeid = (int) str_replace('moodle_badge_', '', $badge->badgecode);
                $imgurl = self::get_simple_moodle_badge_image($badgeid);
            } else {
                $imgpath = !empty($badge->image) ? $badge->image : "pix/badges/{$badge->badgecode}.png";
                $imgurl = (new \moodle_url('/blocks/gamification/' . ltrim($imgpath, '/')))->out(false);
            }

            $classes = $earned ? 'badge-icon badge-earned' : 'badge-icon badge-locked';

            // Build tooltip content based on badge type
            $tooltipContent = [];
            
            if ($isMoodleBadge) {
                // Moodle core badges: Name + Description only (criteria removed)
                $tooltipContent[] = format_string($badge->name);
                
                if (!empty($badge->description)) {
                    $tooltipContent[] = format_string($badge->description);
                }
                
                // Add course/site info
                $badgeLocation = !empty($badge->coursename) ? 
                    format_string($badge->coursename) : 
                    get_string('sitebadge', 'block_gamification');
                $tooltipContent[] = get_string('location', 'block_gamification') . ': ' . $badgeLocation;
                
            } else {
                // Local badges: Name + Description only
                $tooltipContent[] = format_string($badge->name);
                
                if (!empty($badge->description)) {
                    $tooltipContent[] = format_string($badge->description);
                }
                
                // Add period info to tooltip if available
                if (!empty($badge->period) && $earned) {
                    if ($badge->badgecode === 'Leaderboard_Month') {
                        $date = \DateTime::createFromFormat('Y-m', $badge->period);
                        if ($date) {
                            $tooltipContent[] = get_string('awardedfor', 'block_gamification') . ': ' . $date->format('F Y');
                        }
                    } else if ($badge->badgecode === 'Leaderboard_Annual') {
                        $tooltipContent[] = get_string('awardedfor', 'block_gamification') . ': ' . $badge->period;
                    }
                }
                
                // Add earned date if available
                if (!empty($badge->timeearned) && $earned) {
                    $tooltipContent[] = get_string('earnedon', 'block_gamification') . ': ' . userdate($badge->timeearned, get_string('strftimedatefullshort', 'langconfig'));
                }
            }

            $tooltip = implode("\n", $tooltipContent);

            $html .= html_writer::start_div('badge-item badge-wrapper', ['title' => $tooltip]);

            $html .= html_writer::empty_tag('img', [
                'src' => $imgurl,
                'alt' => $badge->name,
                'class' => $classes,
                'onerror' => "this.src='" . (new \moodle_url('/blocks/gamification/pix/badges/default_badge.png'))->out() . "'"
            ]);

            if (!$earned && !$isadmin) {
                $html .= html_writer::div('🔒', 'badge-lock');
            }

            // Use display_name if available, otherwise use the standard name
            $badgeName = !empty($badge->display_name) ? format_string($badge->display_name) : format_string($badge->name);

            $html .= html_writer::tag('div', $badgeName, ['class' => 'badge-name']);
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Moodle badge image
     */
    private static function get_simple_moodle_badge_image(int $badgeid): string {
        global $CFG, $DB;
        
        // Direct approach - use Moodle's badge image URL pattern
        $contextid = self::get_badge_context_id($badgeid);
        if ($contextid) {
            $url = \moodle_url::make_pluginfile_url(
                $contextid,
                'badges',
                'badgeimage',
                $badgeid,
                '/',
                'f1'  // f1 = version 1 of the image
            );
            return $url->out();
        }
        
        // Fallback
        return (new \moodle_url('/blocks/gamification/pix/badges/default_badge.png'))->out(false);
    }

    /**
     * Helper to get context ID for a badge
     */
    private static function get_badge_context_id(int $badgeid): ?int {
        global $DB;
        
        $badge = $DB->get_record('badge', ['id' => $badgeid], 'courseid');
        if (!$badge) {
            return null;
        }
        
        if ($badge->courseid) {
            $context = \context_course::instance($badge->courseid);
        } else {
            $context = \context_system::instance();
        }
        
        return $context->id;
    }

    // Leaderboard display
    public function render_leaderboard(array $users, ?int $highlightid = null, string $type = 'realtime'): string {
        $html = '';

        // Title based on type
        switch ($type) {
            case 'month':
                $title = get_string('monthlyleaderboard', 'block_gamification');
                $nodata = get_string('nombmonthly', 'block_gamification');
                $typeclass = 'monthly';
                break;
            case 'year':
                $title = get_string('yearlyleaderboard', 'block_gamification');
                $nodata = get_string('nombyearly', 'block_gamification');
                $typeclass = 'yearly';
                break;
            default:
                $title = get_string('realtimeleaderboard', 'block_gamification');
                $nodata = get_string('noleaderboarddata', 'block_gamification');
                $typeclass = 'realtime';
                break;
        }

        $containerClass = 'gamification-leaderboard card p-3 mb-4 shadow-sm ' . $typeclass;
        
        // Wrapper with title
        $html .= html_writer::start_div($containerClass);
        $html .= html_writer::tag('h4', $title, ['class' => 'gamification-leaderboard-title']);

        // If no users, show fallback message
        if (empty($users)) {
            $html .= html_writer::div($nodata, 'text-muted text-center');
            $html .= html_writer::end_div();
            return $html;
        }

        // Leaderboard table
        $html .= html_writer::start_div('gamification-container');
        $html .= html_writer::start_tag('table', ['class' => 'generaltable gamification-table']);

        // Header with Groups column
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::tag('tr',
            html_writer::tag('th', get_string('rank', 'block_gamification')) .
            html_writer::tag('th', get_string('profile', 'block_gamification')) .
            html_writer::tag('th', get_string('user', 'block_gamification')) .
            html_writer::tag('th', get_string('groups', 'block_gamification')) . // New Groups column
            html_writer::tag('th', get_string('xp', 'block_gamification'))
        );
        $html .= html_writer::end_tag('thead');

        // Body
        $html .= html_writer::start_tag('tbody');
        $ranknum = 1;
        foreach ($users as $u) {
            $rowclass = ($highlightid && (int)$highlightid === (int)$u->id) ? 'highlight' : '';

            // Medal for top 3
            if ($ranknum === 1) {
                $rankdisplay = '🥇';
            } else if ($ranknum === 2) {
                $rankdisplay = '🥈';
            } else if ($ranknum === 3) {
                $rankdisplay = '🥉';
            } else {
                $rankdisplay = '<strong>' . $ranknum . '</strong>';
            }

            $userpic  = $this->output->user_picture($u, ['size' => 40, 'class' => 'avatar']);
            $fullname = fullname($u);
            $groups = !empty($u->groups) ? $u->groups : get_string('nogroup', 'block_gamification');

            $html .= html_writer::tag('tr',
                html_writer::tag('td', $rankdisplay, ['class' => 'rank-col']) .
                html_writer::tag('td', $userpic, ['class' => 'profile-col']) .
                html_writer::tag('td', $fullname, ['class' => 'name-col']) .
                html_writer::tag('td', $groups, ['class' => 'groups-col']) . 
                html_writer::tag('td', number_format((int)$u->xp), ['class' => 'xp-col']),
                ['class' => $rowclass]
            );

            $ranknum++;
        }
        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_div(); 
        $html .= html_writer::end_div(); 

        return $html;
    }

    // Give XP form (for teachers/admins)
    public function render_give_xp_form(): string {
        global $PAGE;

        if (!has_capability('block/gamification:givexp', $PAGE->context)) {
            return '';
        }

        $url     = new \moodle_url('/blocks/gamification/givexp.php');
        $ajaxurl = new \moodle_url('/blocks/gamification/ajax.php');

        $html  = html_writer::start_div('gamification-givexp-form');
        $html .= html_writer::start_tag('form', [
            'method'   => 'post',
            'action'   => $url,
            'onsubmit' => 'return validateXpForm(this);'
        ]);

        // === Row 1: User search + XP input ===
        $html .= html_writer::start_div('form-row');
        $html .= '<div class="autocomplete-wrapper">';
        $html .= '<input type="text" id="usersearch" placeholder="' .
            get_string('chooseuser', 'block_gamification') .
            '" class="xp-input" autocomplete="off">';
        $html .= '<input type="hidden" name="userid" id="userid">';
        $html .= '</div>';

        $html .= html_writer::empty_tag('input', [
            'type'        => 'number',
            'name'        => 'points',
            'min'         => '1',
            'class'       => 'xp-input xp-number',
            'placeholder' => get_string('enterpoints', 'block_gamification')
        ]);
        $html .= html_writer::end_div();

        // === Row 2: Buttons ===
        $html .= html_writer::start_div('form-row buttons');
        $html .= html_writer::empty_tag('input', [
            'type'  => 'submit',
            'name'  => 'action',
            'value' => get_string('givexp', 'block_gamification'),
            'class' => 'btn btn-primary'
        ]);
        $html .= html_writer::empty_tag('input', [
            'type'  => 'submit',
            'name'  => 'action',
            'value' => get_string('takexp', 'block_gamification'),
            'class' => 'btn btn-danger'
        ]);
        $html .= html_writer::end_div();

        // === Row 3: Quiz search + Difficulty ===
        $html .= html_writer::start_div('form-row');
        $html .= '<div class="autocomplete-wrapper">';
        $html .= '<input type="text" id="quizsearch" placeholder="' .
            get_string('selectquiz', 'block_gamification') .
            '" class="xp-input" autocomplete="off">';
        $html .= '<input type="hidden" name="quizid" id="quizid">';
        $html .= '</div>';

        $diffopts = [
            ''       => get_string('selectdifficulty', 'block_gamification'),
            'Easy'   => 'Easy',
            'Medium' => 'Medium',
            'Hard'   => 'Hard'
        ];
        
        $html .= html_writer::select($diffopts, 'quizdiff', '', false, [
            'id' => 'quizdiff',
            'class' => 'xp-select'
        ]);
        $html .= html_writer::end_div();

        $html .= html_writer::div(
            html_writer::empty_tag('input', [
                'type'  => 'submit',
                'name'  => 'action',
                'value' => get_string('savequizcategory', 'block_gamification'),
                'class' => 'btn btn-primary'
            ]),
            'form-row'
        );

        // === Row 4: Course search + Level ===
        $html .= html_writer::start_div('form-row');
        $html .= '<div class="autocomplete-wrapper">';
        $html .= '<input type="text" id="coursesearch" placeholder="' .
            get_string('selectcourse', 'block_gamification') .
            '" class="xp-input" autocomplete="off">';
        $html .= '<input type="hidden" name="courseid" id="courseid">';
        $html .= '</div>';

        $levelopts = [
            ''             => get_string('selectlevel', 'block_gamification'),
            'Beginner'     => 'Beginner',
            'Intermediate' => 'Intermediate',
            'Advance'      => 'Advance'
        ];
        $html .= html_writer::select($levelopts, 'courselevel', '', false, [
            'id' => 'courselevel',
            'class' => 'xp-select'
        ]);
        $html .= html_writer::end_div();

        $html .= html_writer::div(
            html_writer::empty_tag('input', [
                'type'  => 'submit',
                'name'  => 'action',
                'value' => get_string('savecoursecategory', 'block_gamification'),
                'class' => 'btn btn-primary'
            ]),
            'form-row'
        );

        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div();

        // === Validation Strings ===
        $val_user_points   = get_string('val_user_points', 'block_gamification');
        $val_user          = get_string('val_user', 'block_gamification');
        $val_points        = get_string('val_points', 'block_gamification');
        $confirm_takexp    = get_string('confirmtakexp', 'block_gamification');
        $takexp_label      = get_string('takexp', 'block_gamification');

        $val_quiz_select   = get_string('val_quiz_select', 'block_gamification');
        $val_quiz_value    = get_string('val_quiz_value', 'block_gamification');
        $val_quiz          = get_string('val_quiz', 'block_gamification');
        $val_course_select = get_string('val_course_select', 'block_gamification');
        $val_course_value  = get_string('val_course_value', 'block_gamification');
        $val_course        = get_string('val_course', 'block_gamification');
        $notify_success    = get_string('changessaved', 'block_gamification');

        // === JS Validation + Autocomplete ===
        $html .= "
        <script>
            let clickedButtonValue = '';

            document.querySelectorAll('.gamification-givexp-form input[type=\"submit\"]').forEach(btn => {
                btn.addEventListener('click', () => { clickedButtonValue = btn.value; });
            });

            function validateXpForm(form) {
                const errorBoxId = 'gamification-error-box';
                let errorBox = document.getElementById(errorBoxId);

                if (!errorBox) {
                    errorBox = document.createElement('div');
                    errorBox.id = errorBoxId;
                    errorBox.className = 'gamification-error-box';
                    // Add CSS for fade-out animation
                    errorBox.style.cssText = 'transition: all 0.3s ease !important;';
                    form.prepend(errorBox);
                }

                // Remove any existing animation classes
                errorBox.classList.remove('fade-out');

                let errorMessage = '';

                if (clickedButtonValue === 'Save Quiz Category') {
                    if (!form.quizid.value && !form.quizdiff.value) {
                        errorMessage = '{$val_quiz_select}';
                    } else if (!form.quizid.value || form.quizid.value === '0') {
                        errorMessage = '{$val_quiz}';
                    } else if (!form.quizdiff.value) {
                        errorMessage = '{$val_quiz_value}';
                    }

                } else if (clickedButtonValue === 'Save Course Category') {
                    if (!form.courseid.value && !form.courselevel.value) {
                        errorMessage = '{$val_course_select}';
                    } else if (!form.courseid.value || form.courseid.value === '0') {
                        errorMessage = '{$val_course}';
                    } else if (!form.courselevel.value) {
                        errorMessage = '{$val_course_value}';
                    }
                } else {
                    const user = form.userid.value;
                    const points = form.points.value.trim();

                    if (!user && !points) {
                        errorMessage = '{$val_user_points}';
                    } else if (!user) {
                        errorMessage = '{$val_user}';
                    } else if (!points || isNaN(points) || points <= 0) {
                        errorMessage = '{$val_points}';
                    }
                }

                if (errorMessage) {
                    errorBox.textContent = errorMessage;
                    
                    // Force show with multiple methods
                    errorBox.style.display = 'block';
                    errorBox.style.visibility = 'visible';
                    errorBox.style.opacity = '1';
                    errorBox.style.cssText += 'display: block !important; visibility: visible !important; opacity: 1 !important;';
                    
                    // Hide after 3 seconds using fade-out
                    setTimeout(() => {
                        errorBox.style.opacity = '0';
                        errorBox.style.visibility = 'hidden';
                        errorBox.style.display = 'none';
                        errorBox.style.cssText += 'opacity: 0 !important; visibility: hidden !important; display: none !important; transition: all 0.3s ease !important;';
                    }, 3000);
                    
                    return false;
                }

                if (clickedButtonValue === '{$takexp_label}') {
                    return confirm('{$confirm_takexp}');
                }
                return true;
            }

            function setupAutocomplete(inputId, hiddenId, type, callback) {
                const searchInput = document.getElementById(inputId);
                const hiddenField = document.getElementById(hiddenId);
                let suggestionBox = null;

                function debounce(func, wait) {
                    let timeout;
                    return function(...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                }

                const handleInput = debounce(function() {
                    const query = searchInput.value.trim();
                    if (suggestionBox) { suggestionBox.remove(); suggestionBox = null; }
                    if (query.length < 2) { hiddenField.value = ''; return; }

                    fetch('{$ajaxurl}?type=' + type + '&term=' + encodeURIComponent(query))
                        .then(res => res.json())
                        .then(items => {
                            if (!items.length) return;
                            suggestionBox = document.createElement('div');
                            suggestionBox.classList.add('autocomplete-suggestions');

                            items.forEach(i => {
                                const item = document.createElement('div');
                                item.textContent = i.name;
                                item.classList.add('autocomplete-item');
                                item.onclick = () => {
                                    searchInput.value = i.name;
                                    hiddenField.value = i.id;
                                    suggestionBox.remove();
                                    suggestionBox = null;
                                    if (callback) callback(i.id);
                                };
                                suggestionBox.appendChild(item);
                            });

                            searchInput.parentNode.appendChild(suggestionBox);
                        })
                        .catch(err => console.error('Error fetching ' + type + ':', err));
                }, 300);

                searchInput.addEventListener('input', handleInput);
                document.addEventListener('click', e => {
                    if (suggestionBox && !searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                        suggestionBox.remove();
                        suggestionBox = null;
                    }
                });
                searchInput.addEventListener('keydown', e => {
                    if (e.key === 'Escape' && suggestionBox) {
                        suggestionBox.remove();
                        suggestionBox = null;
                    }
                });
            }

            // === Fetch current difficulty/level when quiz/course selected ===
            function fetchCategory(type, id) {
                fetch('{$ajaxurl}?action=getcategory&type=' + type + '&id=' + id)
                    .then(res => res.json())
                    .then(data => {
                        if (type === 'quiz') {
                            document.getElementById('quizdiff').value = data.difficulty || 'Easy';
                        } else if (type === 'course') {
                            document.getElementById('courselevel').value = data.level || 'Beginner';
                        }
                    })
                    .catch(err => console.error('Error fetching category:', err));
            }

            // Init search bars
            setupAutocomplete('usersearch', 'userid', 'user', null);
            setupAutocomplete('quizsearch', 'quizid', 'quiz', (id) => fetchCategory('quiz', id));
            setupAutocomplete('coursesearch', 'courseid', 'course', (id) => fetchCategory('course', id));
        </script>";

        return $html;
    }

    // Toast notification JS
    public function render_toast_js(): string {
        return "
        <script>
        function showXpToast(message) {
            let toast = document.createElement('div');
            toast.className = 'gamification-toast';
            toast.innerHTML = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 100);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }
        </script>
        ";
    }
}