<?php
require_once("$CFG->dirroot/course/moodleform_mod.php");

class mod_gmtracker_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $USER;

        $mform = $this->_form;

        // Meeting type selection
        $mform->addElement('select', 'meetingtype', get_string('meetingtype', 'gmtracker'), [
            'online' => get_string('meetingtype_online', 'gmtracker'),
            'onsite' => get_string('meetingtype_onsite', 'gmtracker')
        ]);
        $mform->setType('meetingtype', PARAM_ALPHA);
        $mform->setDefault('meetingtype', 'online');
        $mform->addHelpButton('meetingtype', 'meetingtype', 'gmtracker');

        // Meeting name
        $mform->addElement('text', 'name', get_string('gmtrackername', 'gmtracker'), [
            'size' => '64',
            'placeholder' => get_string('gmtrackername_placeholder', 'gmtracker')
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'gmtrackername', 'gmtracker');

        // ADDED: Standard intro elements (description field)
        $this->standard_intro_elements(get_string('moduleintro', 'gmtracker'));

        // Location field (for onsite meetings) - MAKE IT REQUIRED
        $mform->addElement('text', 'location', get_string('location', 'gmtracker'), [
            'size' => '64',
            'placeholder' => get_string('location_placeholder', 'gmtracker')
        ]);
        $mform->setType('location', PARAM_TEXT);
        $mform->addHelpButton('location', 'location', 'gmtracker');
        
        // Use disabledIf instead of hideIf for better compatibility
        $mform->disabledIf('location', 'meetingtype', 'eq', 'online');

        // Google Meet link (only for online meetings)
        $mform->addElement('text', 'gmeetlink', get_string('gmeetlink', 'gmtracker'), [
            'size' => '64',
            'placeholder' => 'https://meet.google.com/abc-defg-hij'
        ]);
        $mform->setType('gmeetlink', PARAM_URL);
        $mform->addHelpButton('gmeetlink', 'gmeetlink', 'gmtracker');
        
        // Use disabledIf instead of hideIf
        $mform->disabledIf('gmeetlink', 'meetingtype', 'eq', 'onsite');

        // Host email - Set current user's email as default but allow editing
        $mform->addElement('text', 'hostemail', get_string('hostemail', 'gmtracker'), [
            'size' => '64',
            'placeholder' => 'instructor@university.edu'
        ]);
        $mform->setType('hostemail', PARAM_EMAIL);
        $mform->setDefault('hostemail', $USER->email); // Auto-populate with current user's email
        $mform->addRule('hostemail', null, 'required', null, 'client');
        $mform->addRule('hostemail', get_string('invalidemail', 'gmtracker'), 'email', null, 'client');
        $mform->addHelpButton('hostemail', 'hostemail', 'gmtracker');

        // Meeting date and time
        $mform->addElement('date_time_selector', 'meetingdate', get_string('meetingdate', 'gmtracker'));
        $mform->addRule('meetingdate', null, 'required', null, 'client');
        $mform->addHelpButton('meetingdate', 'meetingdate', 'gmtracker');

        // Duration
        $defaultduration = get_config('gmtracker', 'defaultduration');
        if (empty($defaultduration)) {
            $defaultduration = 60;
        }
        
        $mform->addElement('text', 'duration', get_string('duration', 'gmtracker'), [
            'size' => '10',
            'placeholder' => $defaultduration
        ]);
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', $defaultduration);
        $mform->addRule('duration', null, 'required', null, 'client');
        $mform->addRule('duration', get_string('duration_validation', 'gmtracker'), 'regex', '/^[1-9][0-9]*$/', 'client');
        $mform->addRule('duration', get_string('duration_max', 'gmtracker'), 'regex', '/^([1-9][0-9]{0,2}|1[0-5][0-9]{2}|1600)$/', 'client');
        $mform->addHelpButton('duration', 'duration', 'gmtracker');

        // Calendar integration
        $mform->addElement('advcheckbox', 'addtocalendar', get_string('addtocalendar', 'gmtracker'));
        $mform->setType('addtocalendar', PARAM_BOOL);
        $mform->setDefault('addtocalendar', 1);
        $mform->addHelpButton('addtocalendar', 'addtocalendar', 'gmtracker');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate meeting date is not in the past
        if ($data['meetingdate'] < time()) {
            $errors['meetingdate'] = get_string('meetingdate_past', 'gmtracker');
        }

        // Validate duration is reasonable
        if ($data['duration'] < 1 || $data['duration'] > 1440) {
            $errors['duration'] = get_string('duration_range', 'gmtracker');
        }

        // Validate Google Meet link only for online meetings
        if ($data['meetingtype'] === 'online') {
            if (empty(trim($data['gmeetlink']))) {
                $errors['gmeetlink'] = get_string('required');
            } elseif (!preg_match('/^https:\/\/meet\.google\.com\/[a-z]{3}-[a-z]{4}-[a-z]{3}$/i', $data['gmeetlink'])) {
                $errors['gmeetlink'] = get_string('gmeetlink_invalid_format', 'gmtracker');
            }
        }

        // Validate location for onsite meetings - MAKE IT REQUIRED
        if ($data['meetingtype'] === 'onsite') {
            if (empty(trim($data['location']))) {
                $errors['location'] = get_string('required');
            }
        }

        return $errors;
    }
}