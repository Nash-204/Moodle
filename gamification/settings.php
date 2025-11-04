<?php
defined('MOODLE_INTERNAL') || die();

// Define settings page for the block.
$settings = new admin_settingpage(
    'block_gamification',
    get_string('pluginname', 'block_gamification')
);

if ($ADMIN->fulltree) {
    // Daily Login Section
    $settings->add(new admin_setting_heading(
        'block_gamification/dailylogin_section',
        get_string('dailyloginheading', 'block_gamification'),
        get_string('dailyloginheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_dailylogin',
        get_string('enable_dailylogin', 'block_gamification'),
        get_string('enable_dailylogin_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_dailylogin',
        get_string('xp_dailylogin', 'block_gamification'),
        get_string('xp_dailylogin_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Course Completed Section
    $settings->add(new admin_setting_heading(
        'block_gamification/coursecompleted_section',
        get_string('coursecompletedheading', 'block_gamification'),
        get_string('coursecompletedheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_coursecompleted',
        get_string('enable_coursecompleted', 'block_gamification'),
        get_string('enable_coursecompleted_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_coursecompleted',
        get_string('xp_coursecompleted', 'block_gamification'),
        get_string('xp_coursecompleted_desc', 'block_gamification'),
        100, PARAM_INT
    ));

    // Quiz Passed Section
    $settings->add(new admin_setting_heading(
        'block_gamification/quizpass_section',
        get_string('quizpassheading', 'block_gamification'),
        get_string('quizpassheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_quizpass',
        get_string('enable_quizpass', 'block_gamification'),
        get_string('enable_quizpass_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_quizpass',
        get_string('xp_quizpass', 'block_gamification'),
        get_string('xp_quizpass_desc', 'block_gamification'),
        50, PARAM_INT
    ));

    // Assignment Submitted Section
    $settings->add(new admin_setting_heading(
        'block_gamification/assignment_section',
        get_string('assignmentheading', 'block_gamification'),
        get_string('assignmentheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_assignment',
        get_string('enable_assignment', 'block_gamification'),
        get_string('enable_assignment_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_assignment',
        get_string('xp_assignment', 'block_gamification'),
        get_string('xp_assignment_desc', 'block_gamification'),
        20, PARAM_INT
    ));

    // Forum Activities Section
    $settings->add(new admin_setting_heading(
        'block_gamification/forum_section',
        get_string('forumheading', 'block_gamification'),
        get_string('forumheading_desc', 'block_gamification')
    ));
    
    // Forum Post
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_forumpost',
        get_string('enable_forumpost', 'block_gamification'),
        get_string('enable_forumpost_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_forumpost',
        get_string('xp_forumpost', 'block_gamification'),
        get_string('xp_forumpost_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Forum Discussion
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_forumdiscussion',
        get_string('enable_forumdiscussion', 'block_gamification'),
        get_string('enable_forumdiscussion_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_forumdiscussion',
        get_string('xp_forumdiscussion', 'block_gamification'),
        get_string('xp_forumdiscussion_desc', 'block_gamification'),
        10, PARAM_INT
    ));

    // Profile Section
    $settings->add(new admin_setting_heading(
        'block_gamification/profile_section',
        get_string('profileheading', 'block_gamification'),
        get_string('profileheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_profilepic',
        get_string('enable_profilepic', 'block_gamification'),
        get_string('enable_profilepic_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_profilepic',
        get_string('xp_profilepic', 'block_gamification'),
        get_string('xp_profilepic_desc', 'block_gamification'),
        15, PARAM_INT
    ));

    // Lesson Section
    $settings->add(new admin_setting_heading(
        'block_gamification/lesson_section',
        get_string('lessonheading', 'block_gamification'),
        get_string('lessonheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_lesson',
        get_string('enable_lesson', 'block_gamification'),
        get_string('enable_lesson_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_lesson',
        get_string('xp_lesson', 'block_gamification'),
        get_string('xp_lesson_desc', 'block_gamification'),
        25, PARAM_INT
    ));

    // Viewing Activities Section
    $settings->add(new admin_setting_heading(
        'block_gamification/viewing_section',
        get_string('viewingheading', 'block_gamification'),
        get_string('viewingheading_desc', 'block_gamification')
    ));
    
    // Course Viewed
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_courseview',
        get_string('enable_courseview', 'block_gamification'),
        get_string('enable_courseview_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_courseview',
        get_string('xp_courseview', 'block_gamification'),
        get_string('xp_courseview_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Resource Viewed 
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_resourceview',
        get_string('enable_resourceview', 'block_gamification'),
        get_string('enable_resourceview_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_resourceview',
        get_string('xp_resourceview', 'block_gamification'),
        get_string('xp_resourceview_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Interactive Activities Section
    $settings->add(new admin_setting_heading(
        'block_gamification/interactive_section',
        get_string('interactiveheading', 'block_gamification'),
        get_string('interactiveheading_desc', 'block_gamification')
    ));
    
    // Feedback Submitted
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_feedback',
        get_string('enable_feedback', 'block_gamification'),
        get_string('enable_feedback_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_feedback',
        get_string('xp_feedback', 'block_gamification'),
        get_string('xp_feedback_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Choice Answered
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_choice',
        get_string('enable_choice', 'block_gamification'),
        get_string('enable_choice_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_choice',
        get_string('xp_choice', 'block_gamification'),
        get_string('xp_choice_desc', 'block_gamification'),
        5, PARAM_INT
    ));

    // Teaching Activities Section
    $settings->add(new admin_setting_heading(
        'block_gamification/teaching_section',
        get_string('teachingheading', 'block_gamification'),
        get_string('teachingheading_desc', 'block_gamification')
    ));
    
    // Activity/Resource Added
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_activityadded',
        get_string('enable_activityadded', 'block_gamification'),
        get_string('enable_activityadded_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_activityadded',
        get_string('xp_activityadded', 'block_gamification'),
        get_string('xp_activityadded_desc', 'block_gamification'),
        10, PARAM_INT
    ));
    
    // Training Tracker Section
    $settings->add(new admin_setting_heading(
        'block_gamification/gmtracker_section',
        get_string('gmtrackerheading', 'block_gamification'),
        get_string('gmtrackerheading_desc', 'block_gamification')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_gamification/enable_gmtracker',
        get_string('enable_gmtracker', 'block_gamification'),
        get_string('enable_gmtracker_desc', 'block_gamification'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'block_gamification/xp_gmtracker',
        get_string('xp_gmtracker', 'block_gamification'),
        get_string('xp_gmtracker_desc', 'block_gamification'),
        100, PARAM_INT
    ));
}