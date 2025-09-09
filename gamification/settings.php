<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {  // Only allow site admins to access.
    $settings = new admin_settingpage(
        'block_gamification',
        get_string('pluginname', 'block_gamification')
    );

    if ($ADMIN->fulltree) {

        // ======================
        // Core XP
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_coursecompleted',
            get_string('xp_coursecompleted', 'block_gamification'),
            get_string('xp_coursecompleted_desc', 'block_gamification'),
            100, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_dailylogin',
            get_string('xp_dailylogin', 'block_gamification'),
            get_string('xp_dailylogin_desc', 'block_gamification'),
            5, PARAM_INT
        ));

        // ======================
        // Quiz
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_quizpass',
            get_string('xp_quizpass', 'block_gamification'),
            get_string('xp_quizpass_desc', 'block_gamification'),
            50, PARAM_INT
        ));

        // ======================
        // Forum
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_forumdiscussion',
            get_string('xp_forumdiscussion', 'block_gamification'),
            get_string('xp_forumdiscussion_desc', 'block_gamification'),
            10, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_forumpost',
            get_string('xp_forumpost', 'block_gamification'),
            get_string('xp_forumpost_desc', 'block_gamification'),
            5, PARAM_INT
        ));

        // ======================
        // Assignment
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_assignment',
            get_string('xp_assignment', 'block_gamification'),
            get_string('xp_assignment_desc', 'block_gamification'),
            20, PARAM_INT
        ));

        // ======================
        // Profile updates
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_profilepic',
            get_string('xp_profilepic', 'block_gamification'),
            get_string('xp_profilepic_desc', 'block_gamification'),
            15, PARAM_INT
        ));

        // ======================
        // Lesson
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_lesson',
            get_string('xp_lesson', 'block_gamification'),
            get_string('xp_lesson_desc', 'block_gamification'),
            25, PARAM_INT
        ));

        // ======================
        // Workshop (peer assessment)
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_peerassessment',
            get_string('xp_peerassessment', 'block_gamification'),
            get_string('xp_peerassessment_desc', 'block_gamification'),
            20, PARAM_INT
        ));

        // ======================
        // Glossary
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_glossary',
            get_string('xp_glossary', 'block_gamification'),
            get_string('xp_glossary_desc', 'block_gamification'),
            10, PARAM_INT
        ));

        // ======================
        // Database activity
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_database',
            get_string('xp_database', 'block_gamification'),
            get_string('xp_database_desc', 'block_gamification'),
            10, PARAM_INT
        ));

        // ======================
        // Wiki
        // ======================
        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_wiki',
            get_string('xp_wiki', 'block_gamification'),
            get_string('xp_wiki_desc', 'block_gamification'),
            15, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_gamification/xp_wikiupdate',
            get_string('xp_wikiupdate', 'block_gamification'),
            get_string('xp_wikiupdate_desc', 'block_gamification'),
            8, PARAM_INT
        ));
    }

    // Add this page under Plugins → Blocks
    $ADMIN->add('blocksettings', $settings);
}
