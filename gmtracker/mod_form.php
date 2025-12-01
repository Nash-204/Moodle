<?php
require_once("$CFG->dirroot/course/moodleform_mod.php");

class mod_gmtracker_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $USER, $DB, $COURSE;

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

        // Standard intro elements
        $this->standard_intro_elements(get_string('moduleintro', 'gmtracker'));

        // Location field (for onsite meetings)
        $mform->addElement('text', 'location', get_string('location', 'gmtracker'), [
            'size' => '64',
            'placeholder' => get_string('location_placeholder', 'gmtracker')
        ]);
        $mform->setType('location', PARAM_TEXT);
        $mform->addHelpButton('location', 'location', 'gmtracker');
        $mform->disabledIf('location', 'meetingtype', 'eq', 'online');

        // Google Meet link (only for online meetings)
        $mform->addElement('text', 'gmeetlink', get_string('gmeetlink', 'gmtracker'), [
            'size' => '64',
            'placeholder' => 'https://meet.google.com/abc-defg-hij'
        ]);
        $mform->setType('gmeetlink', PARAM_URL);
        $mform->addHelpButton('gmeetlink', 'gmeetlink', 'gmtracker');
        $mform->disabledIf('gmeetlink', 'meetingtype', 'eq', 'onsite');

        // Host email
        $mform->addElement('text', 'hostemail', get_string('hostemail', 'gmtracker'), [
            'size' => '64',
            'placeholder' => 'instructor@university.edu'
        ]);
        $mform->setType('hostemail', PARAM_EMAIL);
        $mform->setDefault('hostemail', $USER->email);
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

        // Email Invitation Settings
        $mform->addElement('header', 'emailsettings', get_string('emailinvitations', 'gmtracker'));
        
        // Enable email invitations
        $mform->addElement('advcheckbox', 'sendinvites', get_string('sendinvites', 'gmtracker'));
        $mform->setType('sendinvites', PARAM_BOOL);
        $mform->setDefault('sendinvites', 1);
        $mform->addHelpButton('sendinvites', 'sendinvites', 'gmtracker');

        // Recipient selection method
        $mform->addElement('select', 'recipient_type', get_string('recipienttype', 'gmtracker'), [
            'all' => get_string('allparticipants', 'gmtracker'),
            'groups' => get_string('selectgroups', 'gmtracker'),
            'users' => get_string('selectusers', 'gmtracker')
        ]);
        $mform->setType('recipient_type', PARAM_ALPHA);
        $mform->setDefault('recipient_type', 'all');
        $mform->addHelpButton('recipient_type', 'recipienttype', 'gmtracker');
        $mform->disabledIf('recipient_type', 'sendinvites', 'notchecked');

        // Get course groups
        $courseid = optional_param('course', 0, PARAM_INT);
        if (!$courseid && isset($COURSE->id)) {
            $courseid = $COURSE->id;
        }
        
        $groups = [];
        if ($courseid) {
            $groups = groups_get_all_groups($courseid);
            $groupoptions = [0 => get_string('allgroups', 'gmtracker')];
            foreach ($groups as $group) {
                $groupoptions[$group->id] = format_string($group->name);
            }
            
            $mform->addElement('select', 'selected_groups', get_string('selectedgroups', 'gmtracker'), $groupoptions);
            $mform->setType('selected_groups', PARAM_INT);
            $mform->setDefault('selected_groups', 0);
            $mform->addHelpButton('selected_groups', 'selectedgroups', 'gmtracker');
            $mform->disabledIf('selected_groups', 'sendinvites', 'notchecked');
            $mform->disabledIf('selected_groups', 'recipient_type', 'neq', 'groups');
        }

        // User selector using emails 
        $mform->addElement('textarea', 'selected_users', get_string('selectedusers', 'gmtracker'), [
            'rows' => 4,
            'cols' => 60,
            'placeholder' => get_string('selectedusers_placeholder', 'gmtracker')
        ]);
        $mform->setType('selected_users', PARAM_TEXT);
        $mform->addHelpButton('selected_users', 'selectedusers', 'gmtracker');
        $mform->disabledIf('selected_users', 'sendinvites', 'notchecked');
        $mform->disabledIf('selected_users', 'recipient_type', 'neq', 'users');

        // Add a helper to show enrolled users with emails
        if ($courseid) {
            $context = context_course::instance($courseid);
            $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.email, u.firstname, u.lastname', null, 0, 0, true);
            
            $user_list = [];
            foreach ($enrolled_users as $user) {
                if (!empty($user->email)) {
                    $user_list[] = $user->email . ' (' . $user->firstname . ' ' . $user->lastname . ')';
                }
            }
            
            if (!empty($user_list)) {
                $user_list_text = implode("\n", $user_list); // Remove the array_slice to show ALL users
                
                $mform->addElement('static', 'user_list_helper', get_string('availableusers', 'gmtracker'), 
                    '<div style="font-size: 12px; color: #666; margin-bottom: 10px;">' .
                    get_string('availableusers_help', 'gmtracker') . 
                    '</div><pre style="font-size: 11px; background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto; white-space: pre-wrap;">' . 
                    $user_list_text . 
                    '</pre>');
            }
        }

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

        // Validate location for onsite meetings
        if ($data['meetingtype'] === 'onsite') {
            if (empty(trim($data['location']))) {
                $errors['location'] = get_string('required');
            }
        }

        // Validate recipient selection if sending invites
        if (!empty($data['sendinvites'])) {
            if ($data['recipient_type'] === 'users') {
                if (empty(trim($data['selected_users']))) {
                    $errors['selected_users'] = get_string('selectatleastoneuser', 'gmtracker');
                } else {
                    // Validate email format
                    $emails = array_filter(array_map('trim', explode("\n", $data['selected_users'])));
                    $invalid_emails = [];
                    
                    foreach ($emails as $email) {
                        if (!validate_email($email)) {
                            $invalid_emails[] = $email;
                        }
                    }
                    
                    if (!empty($invalid_emails)) {
                        $errors['selected_users'] = get_string('invalidemails', 'gmtracker') . ': ' . implode(', ', $invalid_emails);
                    }
                }
            }
        }

        return $errors;
    }
}