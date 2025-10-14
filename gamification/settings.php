<?php
defined('MOODLE_INTERNAL') || die();

// Define settings page for the block.
$settings = new admin_settingpage(
    'block_gamification',
    get_string('pluginname', 'block_gamification')
);

if ($ADMIN->fulltree) {
    // Daily Login
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

    // Course Completed
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

    // Quiz Passed
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

    // Assignment Submitted
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

    // Profile Picture Update
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

    // Lesson Completed
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
}
