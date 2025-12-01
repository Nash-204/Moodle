<?php
require_once("$CFG->libdir/formslib.php");

class quiz_invite_form extends moodleform {
    public function definition() {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;
        $quizid = $this->_customdata['quizid'];
        $courseid = $this->_customdata['courseid'];

        // Get quiz and course details
        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $course = $DB->get_record('course', ['id' => $courseid]);

        // Header with quiz info
        $mform->addElement('header', 'quizinfo', get_string('quizinfo', 'block_gamification'));
        $mform->addElement('static', 'quizname', get_string('quizname', 'block_gamification'), format_string($quiz->name));
        $mform->addElement('static', 'coursename', get_string('course'), format_string($course->fullname));

        // Recipient selection method
        $mform->addElement('select', 'recipient_type', get_string('sendinvitationsto', 'block_gamification'), [
            'all' => get_string('allparticipants', 'block_gamification'),
            'groups' => get_string('groupstoinvite', 'block_gamification'),
            'users' => get_string('userstoinvite', 'block_gamification')
        ]);
        $mform->setType('recipient_type', PARAM_ALPHA);
        $mform->setDefault('recipient_type', 'all');
        $mform->addHelpButton('recipient_type', 'recipienttype', 'block_gamification');

        // Get course groups
        $groups = groups_get_all_groups($courseid);
        $groupoptions = [0 => get_string('allgroups', 'block_gamification')];
        foreach ($groups as $group) {
            $groupoptions[$group->id] = format_string($group->name);
        }
        
        $mform->addElement('select', 'selected_groups', get_string('selectedgroups', 'block_gamification'), $groupoptions);
        $mform->setType('selected_groups', PARAM_INT);
        $mform->setDefault('selected_groups', 0);
        $mform->addHelpButton('selected_groups', 'selectedgroups', 'block_gamification');
        $mform->hideIf('selected_groups', 'recipient_type', 'neq', 'groups');

        // User selector - using textarea for email input
        $mform->addElement('textarea', 'selected_users', get_string('selectedusers', 'block_gamification'), [
            'rows' => 4,
            'cols' => 60,
            'placeholder' => get_string('selectedusers_placeholder', 'block_gamification')
        ]);
        $mform->setType('selected_users', PARAM_TEXT);
        $mform->addHelpButton('selected_users', 'selectedusers', 'block_gamification');
        $mform->hideIf('selected_users', 'recipient_type', 'neq', 'users');

        // Add a helper to show enrolled users with emails
        $context = context_course::instance($courseid);
        $enrolled_users = get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.id, u.email, u.firstname, u.lastname', null, 0, 0, true);
        
        $user_list = [];
        foreach ($enrolled_users as $user) {
            if (!empty($user->email)) {
                $user_list[] = $user->email . ' (' . $user->firstname . ' ' . $user->lastname . ')';
            }
        }
        
        if (!empty($user_list)) {
            $user_list_text = implode("\n", $user_list);
            
            $mform->addElement('static', 'user_list_helper', get_string('availableusers', 'block_gamification'), 
                '<div style="font-size: 12px; color: #666; margin-bottom: 10px;">' .
                get_string('availableusers_help', 'block_gamification') . 
                '</div><pre style="font-size: 11px; background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto; white-space: pre-wrap;">' . 
                $user_list_text . 
                '</pre>');
        }

        // Hidden fields
        $mform->addElement('hidden', 'quizid', $quizid);
        $mform->setType('quizid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        // Action buttons
        $this->add_action_buttons(true, get_string('sendinvitations', 'block_gamification'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['recipient_type'] === 'users') {
            if (empty(trim($data['selected_users']))) {
                $errors['selected_users'] = get_string('selectatleastoneuser', 'block_gamification');
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
                    $errors['selected_users'] = get_string('invalidemails', 'block_gamification') . ': ' . implode(', ', $invalid_emails);
                }
            }
        }

        return $errors;
    }
}