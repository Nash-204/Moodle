<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Default meeting duration setting
    $settings->add(new admin_setting_configtext(
        'gmtracker/defaultduration',
        get_string('defaultduration', 'gmtracker'),
        get_string('defaultduration_desc', 'gmtracker'),
        60,
        PARAM_INT
    ));

    // Email notification setting
    $settings->add(new admin_setting_configcheckbox(
        'gmtracker/sendemailnotifications',
        get_string('sendemailnotifications', 'gmtracker'),
        get_string('sendemailnotifications_desc', 'gmtracker'),
        1  
    ));
}