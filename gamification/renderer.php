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
        global $PAGE, $USER;
        
        $html = html_writer::start_div('gamification-badges-container card p-3 mb-3 shadow-sm');
        $html .= html_writer::start_div('d-flex justify-content-between align-items-center');
        $html .= html_writer::tag('h4', get_string('yourbadges', 'block_gamification'), ['class' => 'gamification-badges-title mb-0']);
        
        // Add admin toggle switch
        if ($isadmin) {
            $previewmode = optional_param('badge_preview', 1, PARAM_BOOL); // Default to preview mode
            
            $html .= html_writer::start_div('admin-badge-toggle');
            $html .= html_writer::start_tag('div', ['class' => 'toggle-container']);
            
            // Preview mode label
            $html .= html_writer::span(get_string('previewmode', 'block_gamification'), 'toggle-label preview-label' . ($previewmode ? ' active' : ''));
            
            // Toggle switch
            $html .= html_writer::start_tag('label', ['class' => 'toggle-switch']);
            $html .= html_writer::checkbox(
                'badge_preview',
                '1',
                $previewmode,
                '',
                [
                    'id' => 'badge-preview-toggle',
                    'class' => 'toggle-checkbox',
                    'data-userid' => $USER->id
                ]
            );
            $html .= html_writer::span('', 'toggle-slider');
            $html .= html_writer::end_tag('label');
            
            // Personal mode label
            $html .= html_writer::span(get_string('mymode', 'block_gamification'), 'toggle-label personal-label' . (!$previewmode ? ' active' : ''));
            
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_div();
        }
        
        $html .= html_writer::end_div();

        if ($isadmin) {
            if ($previewmode) {
                $html .= html_writer::tag('small', get_string('allbadgespreview', 'block_gamification'), ['class' => 'text-muted d-block mt-2 admin-mode-text']);
            } else {
                $html .= html_writer::tag('small', get_string('mybadgesview', 'block_gamification'), ['class' => 'text-muted d-block mt-2 admin-mode-text']);
            }
        }

        $html .= html_writer::start_div('gamification-badges-strip');

        foreach ($badges as $badge) {
            // For admins in preview mode, all badges show as earned
            // For admins in personal mode and regular users, use actual earned status
            $actuallyEarned = property_exists($badge, 'earned') ? (bool)$badge->earned : false;
            
            // In preview mode, admins see all badges as earned
            // In personal mode, admins see only actually earned badges
            // Regular users always see actual earned status
            if ($isadmin) {
                $displayEarned = $previewmode ? true : $actuallyEarned;
            } else {
                $displayEarned = $actuallyEarned;
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

            $classes = $displayEarned ? 'badge-icon badge-earned' : 'badge-icon badge-locked';

            // Build tooltip content based on badge type
            $tooltipContent = [];
            
            if ($isMoodleBadge) {
                $tooltipContent[] = format_string($badge->name);
                
                if (!empty($badge->description)) {
                    $tooltipContent[] = format_string($badge->description);
                }
                
                $badgeLocation = !empty($badge->coursename) ? 
                    format_string($badge->coursename) : 
                    get_string('sitebadge', 'block_gamification');
                $tooltipContent[] = get_string('location', 'block_gamification') . ': ' . $badgeLocation;
                
            } else {
                $tooltipContent[] = format_string($badge->name);
                
                if (!empty($badge->description)) {
                    $tooltipContent[] = format_string($badge->description);
                }
                
                // Show period info if available and actually earned (not just in preview)
                if (!empty($badge->period) && $actuallyEarned) {
                    if ($badge->badgecode === 'Leaderboard_Month') {
                        $date = \DateTime::createFromFormat('Y-m', $badge->period);
                        if ($date) {
                            $tooltipContent[] = get_string('awardedfor', 'block_gamification') . ': ' . $date->format('F Y');
                        }
                    } else if ($badge->badgecode === 'Leaderboard_Annual') {
                        $tooltipContent[] = get_string('awardedfor', 'block_gamification') . ': ' . $badge->period;
                    }
                }
                
                // Show earned date if available and actually earned
                if (!empty($badge->timeearned) && $actuallyEarned) {
                    $tooltipContent[] = get_string('earnedon', 'block_gamification') . ': ' . userdate($badge->timeearned, get_string('strftimedatefullshort', 'langconfig'));
                }
            }

            // Add admin preview indicator to tooltip
            if ($isadmin && $previewmode && !$actuallyEarned) {
                $tooltipContent[] = get_string('previewmodeindicator', 'block_gamification');
            }

            $tooltip = implode("\n", $tooltipContent);

            $html .= html_writer::start_div('badge-item badge-wrapper', [
                'title' => $tooltip,
                'data-badge-id' => $badge->badgecode,
                'data-actually-earned' => $actuallyEarned ? '1' : '0'
            ]);

            $html .= html_writer::empty_tag('img', [
                'src' => $imgurl,
                'alt' => $badge->name,
                'class' => $classes,
                'onerror' => "this.src='" . (new \moodle_url('/blocks/gamification/pix/badges/default_badge.png'))->out() . "'"
            ]);

            // Show lock icon only if not display earned
            if (!$displayEarned) {
                $html .= html_writer::div('🔒', 'badge-lock');
            }

            // Use display_name if available, otherwise use the standard name
            $badgeName = !empty($badge->display_name) ? format_string($badge->display_name) : format_string($badge->name);

            $html .= html_writer::tag('div', $badgeName, ['class' => 'badge-name']);
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        // === JavaScript for Toggle Switch ===
        if ($isadmin) {
            $html .= "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggleSwitch = document.getElementById('badge-preview-toggle');
                
                if (toggleSwitch) {
                    // Set initial session storage based on current toggle state
                    const initialPreviewMode = toggleSwitch.checked;
                    sessionStorage.setItem('gamification_admin_preview_mode', initialPreviewMode ? '1' : '0');
                    
                    // Dispatch initial event
                    window.dispatchEvent(new CustomEvent('gamificationPreviewModeChange', {
                        detail: initialPreviewMode
                    }));
                    
                    toggleSwitch.addEventListener('change', function() {
                        const isPreviewMode = this.checked;
                        
                        // Store the mode in sessionStorage for immediate UI updates
                        sessionStorage.setItem('gamification_admin_preview_mode', isPreviewMode ? '1' : '0');
                        
                        // Trigger storage event to update other components
                        window.dispatchEvent(new StorageEvent('storage', {
                            key: 'gamification_admin_preview_mode',
                            newValue: isPreviewMode ? '1' : '0'
                        }));
                        
                        // Dispatch custom event
                        window.dispatchEvent(new CustomEvent('gamificationPreviewModeChange', {
                            detail: isPreviewMode
                        }));
                        
                        // Update all buttons immediately
                        updateAllAdminButtons();
                        
                        // Reload the page with the new mode
                        const url = new URL(window.location.href);
                        if (isPreviewMode) {
                            url.searchParams.set('badge_preview', '1');
                        } else {
                            url.searchParams.set('badge_preview', '0');
                        }
                        window.location.href = url.toString();
                    });
                    
                    // Load saved preference and set initial state
                    require(['core/ajax'], function(ajax) {
                        ajax.call([{
                            methodname: 'core_user_get_user_preferences',
                            args: { name: 'block_gamification_badge_preview' }
                        }])[0].then(function(result) {
                            if (result.preferences && result.preferences.block_gamification_badge_preview) {
                                const savedValue = result.preferences.block_gamification_badge_preview;
                                const isPreviewMode = savedValue === '1';
                                
                                // Update toggle to match saved preference
                                if (toggleSwitch.checked !== isPreviewMode) {
                                    toggleSwitch.checked = isPreviewMode;
                                    sessionStorage.setItem('gamification_admin_preview_mode', isPreviewMode ? '1' : '0');
                                    window.dispatchEvent(new CustomEvent('gamificationPreviewModeChange', {
                                        detail: isPreviewMode
                                    }));
                                }
                                
                                // Save the current state
                                ajax.call([{
                                    methodname: 'core_user_update_user_preferences',
                                    args: {
                                        preferences: [{
                                            type: 'block_gamification_badge_preview',
                                            value: isPreviewMode ? '1' : '0'
                                        }]
                                    }
                                }]);
                            }
                        });
                    });
                }
                
                // Update all admin buttons on page load
                function updateAllAdminButtons() {
                    const adminButtons = document.querySelectorAll('.gamification-admin-btn');
                    const buttonContainers = document.querySelectorAll('.admin-buttons-container');
                    const previewMode = sessionStorage.getItem('gamification_admin_preview_mode');
                    const isPreviewMode = previewMode !== '0'; // default to true if null or '1'
                    
                    // Hide/show the entire button containers
                    buttonContainers.forEach(container => {
                        if (!isPreviewMode) {
                            container.style.display = 'none';
                            container.style.visibility = 'hidden';
                            container.style.height = '0';
                            container.style.margin = '0';
                            container.style.padding = '0';
                            container.style.overflow = 'hidden';
                        } else {
                            container.style.display = 'block';
                            container.style.visibility = 'visible';
                            container.style.height = 'auto';
                            container.style.margin = '';
                            container.style.padding = '';
                            container.style.overflow = '';
                        }
                    });
                    
                    // Also hide individual buttons as backup
                    adminButtons.forEach(button => {
                        if (!isPreviewMode) {
                            button.style.display = 'none';
                            button.style.visibility = 'hidden';
                        } else {
                            button.style.display = 'inline-block';
                            button.style.visibility = 'visible';
                        }
                    });
                }
                
                // Initial update
                updateAllAdminButtons();
                window.addEventListener('load', updateAllAdminButtons);
                
                // Also update after a short delay to ensure everything is loaded
                setTimeout(updateAllAdminButtons, 100);
                setTimeout(updateAllAdminButtons, 500);
            });
            </script>";
        }

        return $html;
    }

    /**
     * Moodle badge image
     */
    private static function get_simple_moodle_badge_image(int $badgeid): string {
        global $CFG, $DB;
        
        // Get context ID
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
        global $USER, $OUTPUT;

        $html = '';

        // ====== Determine title, empty text, and CSS class ======
        switch ($type) {
            case 'month':
                $title = get_string('monthlyleaderboard', 'block_gamification');
                $nodata = get_string('nombmonthly', 'block_gamification');
                $typeclass = 'monthly';
                $buttonlabel = get_string('generatemonthlyleaderboard', 'block_gamification');
                break;

            case 'year':
                $title = get_string('yearlyleaderboard', 'block_gamification');
                $nodata = get_string('nombyearly', 'block_gamification');
                $typeclass = 'yearly';
                $buttonlabel = get_string('generateyearlyleaderboard', 'block_gamification');
                break;

            default:
                $title = get_string('realtimeleaderboard', 'block_gamification');
                $nodata = get_string('noleaderboarddata', 'block_gamification');
                $typeclass = 'realtime';
                $buttonlabel = get_string('awardweeklychampion', 'block_gamification');
                break;
        }

        $containerClass = 'gamification-leaderboard card p-3 mb-4 shadow-sm ' . $typeclass;

        // ====== Start wrapper ======
        $html .= html_writer::start_div($containerClass);

        // ====== Add title and admin-only button ======
        $isadmin = is_siteadmin($USER);
        $buttonhtml = '';

        if ($isadmin) {
            // For realtime leaderboard, use 'week' type for weekly champion award
            $actionType = ($type === 'realtime') ? 'week' : $type;
            
            $actionurl = new moodle_url('/blocks/gamification/actions/process_leaderboard.php', [
                'type' => $actionType,
                'sesskey' => sesskey()
            ]);

            $buttonhtml = html_writer::tag('a', $buttonlabel, [
                'href' => $actionurl,
                'class' => 'btn btn-primary gamification-admin-btn mt-1'
            ]);
        }

        // Centered title
        $html .= html_writer::start_div('text-center mb-1'); 
        $html .= html_writer::tag('h4', $title, ['class' => 'gamification-leaderboard-title m-0']);
        $html .= html_writer::end_div();

        // Admin button below title 
        if ($isadmin) {
            $html .= html_writer::start_div('text-center admin-buttons-container');
            $html .= $buttonhtml;
            $html .= html_writer::end_div();
        }

        // ====== If no users, show fallback ======
        if (empty($users)) {
            $html .= html_writer::div($nodata, 'text-muted text-center mt-3');
            $html .= html_writer::end_div();
            return $html;
        }

        // ====== Leaderboard table ======
        $html .= html_writer::start_div('gamification-container mt-3');
        $html .= html_writer::start_tag('table', ['class' => 'generaltable gamification-table']);

        // Table header
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::tag('tr',
            html_writer::tag('th', get_string('rank', 'block_gamification')) .
            html_writer::tag('th', get_string('profile', 'block_gamification')) .
            html_writer::tag('th', get_string('user', 'block_gamification')) .
            html_writer::tag('th', get_string('groups', 'block_gamification')) .
            html_writer::tag('th', get_string('xp', 'block_gamification'))
        );
        $html .= html_writer::end_tag('thead');

        // ====== Table body ======
        $html .= html_writer::start_tag('tbody');
        $ranknum = 1;
        foreach ($users as $u) {
            $rowclass = ($highlightid && (int)$highlightid === (int)$u->id) ? 'highlight' : '';

            // Medal display
            if ($ranknum === 1) {
                $rankdisplay = '🥇';
            } else if ($ranknum === 2) {
                $rankdisplay = '🥈';
            } else if ($ranknum === 3) {
                $rankdisplay = '🥉';
            } else {
                $rankdisplay = '<strong>' . $ranknum . '</strong>';
            }

            $userpic  = $OUTPUT->user_picture($u, ['size' => 40, 'class' => 'avatar']);
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

        // ====== Add JavaScript for button visibility control ======
        if ($isadmin) {
            // Gets the current preview mode from URL parameter
            $previewmode = optional_param('badge_preview', 1, PARAM_BOOL);
            
            $html .= "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize sessionStorage if not set
                function initializePreviewMode() {
                    const currentMode = sessionStorage.getItem('gamification_admin_preview_mode');
                    if (currentMode === null) {
                        // Set initial value based on URL parameter
                        const urlParams = new URLSearchParams(window.location.search);
                        const badgePreviewParam = urlParams.get('badge_preview');
                        const isPreviewMode = badgePreviewParam !== '0';
                        sessionStorage.setItem('gamification_admin_preview_mode', isPreviewMode ? '1' : '0');
                    }
                }
                
                function updateLeaderboardAdminButtons() {
                    const adminButtons = document.querySelectorAll('.gamification-leaderboard .gamification-admin-btn');
                    const buttonContainers = document.querySelectorAll('.gamification-leaderboard .admin-buttons-container');
                    const previewMode = sessionStorage.getItem('gamification_admin_preview_mode');
                    
                    let isPreviewMode = true; // default to preview mode
                    if (previewMode !== null) {
                        isPreviewMode = previewMode === '1';
                    }
                    
                    // Hide/show the entire button container
                    buttonContainers.forEach(container => {
                        if (!isPreviewMode) {
                            container.style.display = 'none';
                            container.style.visibility = 'hidden';
                            container.style.height = '0';
                            container.style.margin = '0';
                            container.style.padding = '0';
                            container.style.overflow = 'hidden';
                        } else {
                            container.style.display = 'block';
                            container.style.visibility = 'visible';
                            container.style.height = 'auto';
                            container.style.margin = '';
                            container.style.padding = '';
                            container.style.overflow = '';
                        }
                    });
                    
                    // Also hide individual buttons as backup
                    adminButtons.forEach(button => {
                        if (!isPreviewMode) {
                            button.style.display = 'none';
                            button.style.visibility = 'hidden';
                        } else {
                            button.style.display = 'inline-block';
                            button.style.visibility = 'visible';
                        }
                    });
                }
                
                // Initialize and update
                initializePreviewMode();
                updateLeaderboardAdminButtons();
                
                // Listen for storage events from badges section
                window.addEventListener('storage', function(e) {
                    if (e.key === 'gamification_admin_preview_mode') {
                        updateLeaderboardAdminButtons();
                    }
                });
                
                // Also listen for custom events
                window.addEventListener('gamificationPreviewModeChange', function(e) {
                    sessionStorage.setItem('gamification_admin_preview_mode', e.detail ? '1' : '0');
                    updateLeaderboardAdminButtons();
                });
                
                // Update when page fully loads
                window.addEventListener('load', function() {
                    updateLeaderboardAdminButtons();
                });
                
                // Periodic checks to ensure correct state
                setTimeout(updateLeaderboardAdminButtons, 100);
                setTimeout(updateLeaderboardAdminButtons, 500);
            });
            </script>";
        }

        $html .= html_writer::end_div(); // card wrapper

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

        // === User search + XP input ===
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

        // === Buttons ===
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

        // === Quiz search + Difficulty + Email ===
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

        // === Quiz Action Buttons ===
        $html .= html_writer::start_div('form-row quiz-buttons');
        $html .= html_writer::empty_tag('input', [
            'type'  => 'submit',
            'name'  => 'action',
            'value' => get_string('savequizcategory', 'block_gamification'),
            'class' => 'btn btn-primary'
        ]);
        
        // === Email Action Buttons ===
        $html .= html_writer::empty_tag('input', [
            'type'  => 'submit',
            'name'  => 'action',
            'value' => get_string('sendquizinvite', 'block_gamification'),
            'class' => 'btn btn-secondary',
            'id'    => 'email-invite-btn'
        ]); 
        $html .= html_writer::end_div();

        // === Course search + Level ===
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

        // Divider
        $html .= html_writer::tag('hr', '', ['class' => 'gamification-export-divider']);

        // === Export CSV Button ===
        $exporturl = new \moodle_url('/blocks/gamification/export.php');
        if (has_capability('block/gamification:export', $this->page->context)) {
            $html .= html_writer::start_div('form-row gamification-export-row');
            $html .= html_writer::link(
                $exporturl,
                get_string('exportcsv', 'block_gamification'),
                ['class' => 'btn btn-secondary']
            );
            $html .= html_writer::end_div();
        }

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
        
        // Validation string for email invite
        $val_quiz_email    = get_string('val_quiz_email', 'block_gamification');
        $send_invite_label = get_string('sendquizinvite', 'block_gamification');

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

                } else if (clickedButtonValue === '{$send_invite_label}') {
                    // Validation for email invite
                    if (!form.quizid.value || form.quizid.value === '0') {
                        errorMessage = '{$val_quiz_email}';
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